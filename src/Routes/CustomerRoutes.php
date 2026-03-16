<?php

declare(strict_types=1);

namespace AgencyOS\Routes;

use AgencyOS\Core\{Database, Security, Helpers, EmailService, Storage};
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;

class CustomerRoutes
{
    public static function register(App $app): void
    {
        // POST /api/customers
        $app->post('/api/customers', function (Request $request, Response $response) {
            $user = Security::getCurrentUser($request);
            Security::requireRole($user, ['CHEF', 'ACCOUNT_MANAGER']);

            $body = (array) $request->getParsedBody();
            if (empty($body['name'])) return self::error($response, 'Name erforderlich', 400);

            $id = Helpers::uuid();
            Database::insert('customers', [
                'id'              => $id,
                'name'            => $body['name'],
                'company'         => $body['company'] ?? '',
                'email'           => $body['email'] ?? '',
                'phone'           => $body['phone'] ?? '',
                'website'         => $body['website'] ?? '',
                'address'         => $body['address'] ?? null,
                'notes'           => $body['notes'] ?? null,
                'color'           => $body['color'] ?? '#3B82F6',
                'account_manager' => $body['account_manager'] ?? $user['id'],
                'created_by'      => $user['id'],
                'created_at'      => Helpers::now(),
                'updated_at'      => Helpers::now(),
            ]);

            // Add shared users
            if (!empty($body['shared_users']) && is_array($body['shared_users'])) {
                foreach ($body['shared_users'] as $uid) {
                    Database::execute('INSERT IGNORE INTO customer_access (customer_id,user_id) VALUES (?,?)', [$id, $uid]);
                }
            }

            Helpers::createAuditLog($user['id'], Helpers::userName($user), 'create_customer', 'customer', $id, ['name' => $body['name']]);
            return self::json($response, self::enrichCustomer(Database::fetchOne('SELECT * FROM customers WHERE id=?', [$id])), 201);
        });

        // GET /api/customers
        $app->get('/api/customers', function (Request $request, Response $response) {
            $user   = Security::getCurrentUser($request);
            $params = $request->getQueryParams();

            if (in_array($user['role'], ['CHEF', 'BUCHHALTUNG'])) {
                $sql   = 'SELECT * FROM customers WHERE is_archived=0 ORDER BY name';
                $binds = [];
            } else {
                $sql   = 'SELECT DISTINCT c.* FROM customers c LEFT JOIN customer_access ca ON ca.customer_id=c.id WHERE (c.account_manager=? OR c.created_by=? OR ca.user_id=?) AND c.is_archived=0 ORDER BY c.name';
                $binds = [$user['id'], $user['id'], $user['id']];
            }

            if (!empty($params['archived'])) {
                $sql   = str_replace('is_archived=0', 'is_archived=1', $sql);
            }

            $customers = Database::fetchAll($sql, $binds);
            return self::json($response, array_map(fn($c) => self::enrichCustomer($c), $customers));
        });

        // GET /api/customers/{customer_id}
        $app->get('/api/customers/{customer_id}', function (Request $request, Response $response, array $args) {
            Security::getCurrentUser($request);
            $c = Database::fetchOne('SELECT * FROM customers WHERE id=?', [$args['customer_id']]);
            if (!$c) return self::error($response, 'Kunde nicht gefunden', 404);
            return self::json($response, self::enrichCustomer($c));
        });

        // PUT /api/customers/{customer_id}
        $app->put('/api/customers/{customer_id}', function (Request $request, Response $response, array $args) {
            $user = Security::getCurrentUser($request);
            $body = (array) $request->getParsedBody();
            $cid  = $args['customer_id'];

            $c = Database::fetchOne('SELECT * FROM customers WHERE id=?', [$cid]);
            if (!$c) return self::error($response, 'Kunde nicht gefunden', 404);

            $allowed = ['name','company','email','phone','website','address','notes','color','account_manager'];
            $updates = ['updated_at' => Helpers::now()];
            foreach ($allowed as $f) {
                if (array_key_exists($f, $body)) $updates[$f] = $body[$f];
            }
            Database::update('customers', $updates, ['id' => $cid]);
            return self::json($response, self::enrichCustomer(Database::fetchOne('SELECT * FROM customers WHERE id=?', [$cid])));
        });

        // DELETE /api/customers/{customer_id}
        $app->delete('/api/customers/{customer_id}', function (Request $request, Response $response, array $args) {
            $user = Security::getCurrentUser($request);
            Security::requireRole($user, 'CHEF');
            $cid  = $args['customer_id'];
            $c    = Database::fetchOne('SELECT * FROM customers WHERE id=?', [$cid]);
            if (!$c) return self::error($response, 'Kunde nicht gefunden', 404);

            if ($c['logo_url']) Storage::delete($c['logo_url']);
            Database::delete('customers', ['id' => $cid]);
            Helpers::createAuditLog($user['id'], Helpers::userName($user), 'delete_customer', 'customer', $cid, ['name' => $c['name']]);
            return self::json($response, ['message' => 'Kunde gelöscht']);
        });

        // PUT /api/customers/{customer_id}/archive
        $app->put('/api/customers/{customer_id}/archive', function (Request $request, Response $response, array $args) {
            Security::getCurrentUser($request);
            Database::update('customers', ['is_archived' => 1, 'archived_at' => Helpers::now(), 'status' => 'archived'], ['id' => $args['customer_id']]);
            return self::json($response, ['message' => 'Archiviert']);
        });

        // PUT /api/customers/{customer_id}/unarchive
        $app->put('/api/customers/{customer_id}/unarchive', function (Request $request, Response $response, array $args) {
            Security::getCurrentUser($request);
            Database::update('customers', ['is_archived' => 0, 'archived_at' => null, 'status' => 'active'], ['id' => $args['customer_id']]);
            return self::json($response, ['message' => 'Wiederhergestellt']);
        });

        // POST /api/customers/{customer_id}/logos
        $app->post('/api/customers/{customer_id}/logos', function (Request $request, Response $response, array $args) {
            Security::getCurrentUser($request);
            $files = $request->getUploadedFiles();
            if (empty($files['file'])) return self::error($response, 'Keine Datei', 400);

            $stored = Storage::store($files['file'], 'logos');
            Database::update('customers', ['logo_url' => $stored['url']], ['id' => $args['customer_id']]);
            return self::json($response, ['logo_url' => $stored['url']]);
        });

        // ---- PUBLIC PAGE ----

        // GET /api/customers/{customer_id}/public-page
        $app->get('/api/customers/{customer_id}/public-page', function (Request $request, Response $response, array $args) {
            Security::getCurrentUser($request);
            $cid = $args['customer_id'];
            $page = Database::fetchOne('SELECT * FROM public_page_settings WHERE customer_id=?', [$cid]);
            $links = Database::fetchAll('SELECT * FROM public_page_links WHERE customer_id=? ORDER BY sort_order', [$cid]);
            return self::json($response, ['settings' => $page, 'links' => $links]);
        });

        // PUT /api/customers/{customer_id}/public-page
        $app->put('/api/customers/{customer_id}/public-page', function (Request $request, Response $response, array $args) {
            Security::getCurrentUser($request);
            $cid  = $args['customer_id'];
            $body = (array) $request->getParsedBody();

            $existing = Database::fetchOne('SELECT id FROM public_page_settings WHERE customer_id=?', [$cid]);
            $data = [
                'is_enabled'        => (int) ($body['is_enabled'] ?? 0),
                'password'          => $body['password'] ?? null,
                'title'             => $body['title'] ?? null,
                'description'       => $body['description'] ?? null,
                'expires_at'        => $body['expires_at'] ?? null,
                'show_content_plan' => (int) ($body['show_content_plan'] ?? 0),
                'show_reports'      => (int) ($body['show_reports'] ?? 0),
            ];
            if ($existing) {
                Database::update('public_page_settings', $data, ['customer_id' => $cid]);
            } else {
                $data['id']          = Helpers::uuid();
                $data['customer_id'] = $cid;
                Database::insert('public_page_settings', $data);
            }
            return self::json($response, $data);
        });

        // POST /api/customers/{customer_id}/public-page/links
        $app->post('/api/customers/{customer_id}/public-page/links', function (Request $request, Response $response, array $args) {
            Security::getCurrentUser($request);
            $body = (array) $request->getParsedBody();
            if (empty($body['title']) || empty($body['url'])) return self::error($response, 'Titel und URL erforderlich', 400);

            $id = Helpers::uuid();
            Database::insert('public_page_links', ['id' => $id, 'customer_id' => $args['customer_id'], 'title' => $body['title'], 'url' => $body['url'], 'icon' => $body['icon'] ?? null, 'sort_order' => 0, 'created_at' => Helpers::now()]);
            return self::json($response, Database::fetchOne('SELECT * FROM public_page_links WHERE id=?', [$id]), 201);
        });

        // PUT /api/customers/{customer_id}/public-page/links/{link_id}
        $app->put('/api/customers/{customer_id}/public-page/links/{link_id}', function (Request $request, Response $response, array $args) {
            Security::getCurrentUser($request);
            $body    = (array) $request->getParsedBody();
            $updates = array_intersect_key($body, array_flip(['title','url','icon','sort_order']));
            Database::update('public_page_links', $updates, ['id' => $args['link_id']]);
            return self::json($response, Database::fetchOne('SELECT * FROM public_page_links WHERE id=?', [$args['link_id']]));
        });

        // DELETE /api/customers/{customer_id}/public-page/links/{link_id}
        $app->delete('/api/customers/{customer_id}/public-page/links/{link_id}', function (Request $request, Response $response, array $args) {
            Security::getCurrentUser($request);
            Database::delete('public_page_links', ['id' => $args['link_id']]);
            return self::json($response, ['message' => 'Link gelöscht']);
        });

        // ---- CONTENT PLAN ----

        // GET /api/customers/{customer_id}/content-plan
        $app->get('/api/customers/{customer_id}/content-plan', function (Request $request, Response $response, array $args) {
            Security::getCurrentUser($request);
            $params = $request->getQueryParams();
            $sql    = 'SELECT cp.*,u.first_name,u.last_name FROM content_plan cp LEFT JOIN users u ON u.id=cp.created_by WHERE cp.customer_id=?';
            $binds  = [$args['customer_id']];
            if (!empty($params['month'])) { $sql .= ' AND DATE_FORMAT(cp.publish_date,"%Y-%m")=?'; $binds[] = $params['month']; }
            $sql .= ' ORDER BY cp.publish_date ASC';
            $posts = Database::fetchAll($sql, $binds);
            foreach ($posts as &$post) {
                $post['media'] = Database::fetchAll('SELECT * FROM content_plan_media WHERE post_id=?', [$post['id']]);
            }
            return self::json($response, $posts);
        });

        // POST /api/customers/{customer_id}/content-plan
        $app->post('/api/customers/{customer_id}/content-plan', function (Request $request, Response $response, array $args) {
            $user = Security::getCurrentUser($request);
            $body = (array) $request->getParsedBody();
            if (empty($body['title'])) return self::error($response, 'Titel erforderlich', 400);

            $id = Helpers::uuid();
            Database::insert('content_plan', [
                'id'           => $id,
                'customer_id'  => $args['customer_id'],
                'title'        => $body['title'],
                'description'  => $body['description'] ?? null,
                'platform'     => $body['platform'] ?? '',
                'status'       => 'draft',
                'publish_date' => $body['publish_date'] ?? null,
                'created_by'   => $user['id'],
                'created_at'   => Helpers::now(),
                'updated_at'   => Helpers::now(),
            ]);
            return self::json($response, Database::fetchOne('SELECT * FROM content_plan WHERE id=?', [$id]), 201);
        });

        // PUT /api/customers/{customer_id}/content-plan/{post_id}
        $app->put('/api/customers/{customer_id}/content-plan/{post_id}', function (Request $request, Response $response, array $args) {
            $user   = Security::getCurrentUser($request);
            $body   = (array) $request->getParsedBody();
            $postId = $args['post_id'];
            $allowed = ['title','description','platform','status','publish_date','feedback'];
            $updates = ['updated_at' => Helpers::now()];
            foreach ($allowed as $f) {
                if (array_key_exists($f, $body)) $updates[$f] = $body[$f];
            }
            Database::update('content_plan', $updates, ['id' => $postId]);
            return self::json($response, Database::fetchOne('SELECT * FROM content_plan WHERE id=?', [$postId]));
        });

        // DELETE /api/customers/{customer_id}/content-plan/{post_id}
        $app->delete('/api/customers/{customer_id}/content-plan/{post_id}', function (Request $request, Response $response, array $args) {
            $user   = Security::getCurrentUser($request);
            $postId = $args['post_id'];
            $post   = Database::fetchOne('SELECT * FROM content_plan WHERE id=?', [$postId]);
            if (!$post) return self::error($response, 'Post nicht gefunden', 404);

            $media = Database::fetchAll('SELECT * FROM content_plan_media WHERE post_id=?', [$postId]);
            foreach ($media as $m) Storage::delete($m['file_url']);

            Database::delete('content_plan', ['id' => $postId]);
            return self::json($response, ['message' => 'Gelöscht']);
        });

        // POST /api/customers/{customer_id}/content-plan/{post_id}/media
        $app->post('/api/customers/{customer_id}/content-plan/{post_id}/media', function (Request $request, Response $response, array $args) {
            Security::getCurrentUser($request);
            $files = $request->getUploadedFiles();
            if (empty($files['file'])) return self::error($response, 'Keine Datei', 400);

            $stored = Storage::store($files['file'], 'content');
            $id     = Helpers::uuid();
            Database::insert('content_plan_media', ['id' => $id, 'post_id' => $args['post_id'], 'file_url' => $stored['url'], 'file_name' => $stored['name'], 'file_type' => $stored['type'], 'created_at' => Helpers::now()]);
            return self::json($response, ['id' => $id, 'file_url' => $stored['url']], 201);
        });

        // ---- REPORTS ----

        // GET /api/customers/{customer_id}/reports
        $app->get('/api/customers/{customer_id}/reports', function (Request $request, Response $response, array $args) {
            Security::getCurrentUser($request);
            return self::json($response, Database::fetchAll('SELECT * FROM customer_reports WHERE customer_id=? ORDER BY created_at DESC', [$args['customer_id']]));
        });

        // POST /api/customers/{customer_id}/reports
        $app->post('/api/customers/{customer_id}/reports', function (Request $request, Response $response, array $args) {
            $user = Security::getCurrentUser($request);
            $body = (array) $request->getParsedBody();
            if (empty($body['title'])) return self::error($response, 'Titel erforderlich', 400);

            $id = Helpers::uuid();
            Database::insert('customer_reports', ['id' => $id, 'customer_id' => $args['customer_id'], 'title' => $body['title'], 'content' => $body['content'] ?? null, 'month' => $body['month'] ?? null, 'created_by' => $user['id'], 'created_at' => Helpers::now(), 'updated_at' => Helpers::now()]);
            return self::json($response, Database::fetchOne('SELECT * FROM customer_reports WHERE id=?', [$id]), 201);
        });

        // DELETE /api/customers/{customer_id}/reports/{report_id}
        $app->delete('/api/customers/{customer_id}/reports/{report_id}', function (Request $request, Response $response, array $args) {
            Security::getCurrentUser($request);
            Database::delete('customer_reports', ['id' => $args['report_id']]);
            return self::json($response, ['message' => 'Bericht gelöscht']);
        });

        // ---- CREDENTIALS ----

        // GET /api/customers/{customer_id}/credentials
        $app->get('/api/customers/{customer_id}/credentials', function (Request $request, Response $response, array $args) {
            $user = Security::getCurrentUser($request);
            $cid  = $args['customer_id'];

            $creds = Database::fetchAll('SELECT * FROM customer_credentials WHERE customer_id=? ORDER BY title', [$cid]);
            $result = [];
            foreach ($creds as $cred) {
                $hasAccess = $cred['is_visible_to_all'] || in_array($user['role'], ['CHEF', 'ACCOUNT_MANAGER']) || $cred['created_by'] === $user['id'];
                if (!$hasAccess) {
                    $vis = Database::fetchOne('SELECT credential_id FROM credential_visibility WHERE credential_id=? AND user_id=?', [$cred['id'], $user['id']]);
                    $hasAccess = (bool) $vis;
                }
                if ($hasAccess) {
                    $cred['visible_to'] = array_column(Database::fetchAll('SELECT user_id FROM credential_visibility WHERE credential_id=?', [$cred['id']]), 'user_id');
                    $result[] = $cred;
                }
            }
            return self::json($response, $result);
        });

        // POST /api/customers/{customer_id}/credentials
        $app->post('/api/customers/{customer_id}/credentials', function (Request $request, Response $response, array $args) {
            $user = Security::getCurrentUser($request);
            $body = (array) $request->getParsedBody();
            if (empty($body['title'])) return self::error($response, 'Titel erforderlich', 400);

            $id = Helpers::uuid();
            Database::insert('customer_credentials', ['id' => $id, 'customer_id' => $args['customer_id'], 'title' => $body['title'], 'category' => $body['category'] ?? 'general', 'username' => $body['username'] ?? '', 'password' => $body['password'] ?? '', 'url' => $body['url'] ?? '', 'notes' => $body['notes'] ?? null, 'created_by' => $user['id'], 'is_visible_to_all' => (int)($body['is_visible_to_all'] ?? 0), 'created_at' => Helpers::now(), 'updated_at' => Helpers::now()]);

            Helpers::createAuditLog($user['id'], Helpers::userName($user), 'create_credential', 'credential', $id, ['title' => $body['title']]);
            return self::json($response, Database::fetchOne('SELECT * FROM customer_credentials WHERE id=?', [$id]), 201);
        });

        // PUT /api/customers/{customer_id}/credentials/{credential_id}
        $app->put('/api/customers/{customer_id}/credentials/{credential_id}', function (Request $request, Response $response, array $args) {
            $user    = Security::getCurrentUser($request);
            $body    = (array) $request->getParsedBody();
            $credId  = $args['credential_id'];
            $allowed = ['title','category','username','password','url','notes','is_visible_to_all'];
            $updates = ['updated_at' => Helpers::now()];
            foreach ($allowed as $f) {
                if (array_key_exists($f, $body)) $updates[$f] = $body[$f];
            }
            Database::update('customer_credentials', $updates, ['id' => $credId]);
            Helpers::createAuditLog($user['id'], Helpers::userName($user), 'update_credential', 'credential', $credId);
            return self::json($response, Database::fetchOne('SELECT * FROM customer_credentials WHERE id=?', [$credId]));
        });

        // DELETE /api/customers/{customer_id}/credentials/{credential_id}
        $app->delete('/api/customers/{customer_id}/credentials/{credential_id}', function (Request $request, Response $response, array $args) {
            $user   = Security::getCurrentUser($request);
            $credId = $args['credential_id'];
            Database::delete('customer_credentials', ['id' => $credId]);
            Helpers::createAuditLog($user['id'], Helpers::userName($user), 'delete_credential', 'credential', $credId);
            return self::json($response, ['message' => 'Gelöscht']);
        });

        // PUT /api/customers/{customer_id}/credentials/{credential_id}/visibility
        $app->put('/api/customers/{customer_id}/credentials/{credential_id}/visibility', function (Request $request, Response $response, array $args) {
            Security::getCurrentUser($request);
            $body   = (array) $request->getParsedBody();
            $credId = $args['credential_id'];
            $users  = $body['user_ids'] ?? [];

            Database::execute('DELETE FROM credential_visibility WHERE credential_id=?', [$credId]);
            foreach ($users as $uid) {
                Database::execute('INSERT IGNORE INTO credential_visibility (credential_id,user_id) VALUES (?,?)', [$credId, $uid]);
            }
            return self::json($response, ['message' => 'Sichtbarkeit aktualisiert']);
        });

        // GET /api/credential-categories
        $app->get('/api/credential-categories', function (Request $request, Response $response) {
            Security::getCurrentUser($request);
            return self::json($response, ['social_media','website','hosting','email','crm','analytics','design','seo','ads','sonstiges']);
        });

        // ---- PUBLIC ROUTES ----

        // GET /api/public/customer/{customer_id}
        $app->get('/api/public/customer/{customer_id}', function (Request $request, Response $response, array $args) {
            $cid      = $args['customer_id'];
            $customer = Database::fetchOne('SELECT * FROM customers WHERE id=?', [$cid]);
            if (!$customer) return self::error($response, 'Nicht gefunden', 404);

            $page = Database::fetchOne('SELECT * FROM public_page_settings WHERE customer_id=?', [$cid]);
            if (!$page || !$page['is_enabled']) return self::error($response, 'Seite nicht verfügbar', 404);
            if ($page['expires_at'] && strtotime($page['expires_at']) < time()) return self::error($response, 'Link abgelaufen', 410);

            // Password check
            $params = $request->getQueryParams();
            if ($page['password']) {
                $pw = $params['password'] ?? ($request->getParsedBody()['password'] ?? '');
                if ($pw !== $page['password']) {
                    return self::json($response, ['requires_password' => true], 401);
                }
            }

            $links = Database::fetchAll('SELECT * FROM public_page_links WHERE customer_id=? ORDER BY sort_order', [$cid]);
            return self::json($response, [
                'customer' => ['name' => $customer['name'], 'logo_url' => $customer['logo_url'], 'color' => $customer['color']],
                'settings' => $page,
                'links'    => $links,
            ]);
        });

        // GET /api/public/customer/{customer_id}/content-plan
        $app->get('/api/public/customer/{customer_id}/content-plan', function (Request $request, Response $response, array $args) {
            $cid  = $args['customer_id'];
            $page = Database::fetchOne('SELECT * FROM public_page_settings WHERE customer_id=?', [$cid]);
            if (!$page || !$page['show_content_plan']) return self::error($response, 'Nicht verfügbar', 404);

            $posts = Database::fetchAll('SELECT * FROM content_plan WHERE customer_id=? ORDER BY publish_date ASC', [$cid]);
            foreach ($posts as &$post) {
                $post['media'] = Database::fetchAll('SELECT * FROM content_plan_media WHERE post_id=?', [$post['id']]);
                unset($post['created_by']);
            }
            return self::json($response, $posts);
        });

        // POST /api/public/customer/{customer_id}/content-plan/{post_id}/feedback
        $app->post('/api/public/customer/{customer_id}/content-plan/{post_id}/feedback', function (Request $request, Response $response, array $args) {
            $body   = (array) $request->getParsedBody();
            $postId = $args['post_id'];
            $status = $body['status'] ?? 'approved'; // approved|revision
            $fb     = $body['feedback'] ?? '';

            Database::update('content_plan', ['status' => $status, 'feedback' => $fb, 'updated_at' => Helpers::now()], ['id' => $postId]);
            return self::json($response, ['message' => 'Feedback gespeichert']);
        });

        // GET /api/public/customer/{customer_id}/reports
        $app->get('/api/public/customer/{customer_id}/reports', function (Request $request, Response $response, array $args) {
            $cid  = $args['customer_id'];
            $page = Database::fetchOne('SELECT * FROM public_page_settings WHERE customer_id=?', [$cid]);
            if (!$page || !$page['show_reports']) return self::error($response, 'Nicht verfügbar', 404);

            return self::json($response, Database::fetchAll('SELECT id,title,month,created_at FROM customer_reports WHERE customer_id=? ORDER BY created_at DESC', [$cid]));
        });
    }

    private static function enrichCustomer(?array $c): ?array
    {
        if (!$c) return null;
        $c['shared_users'] = array_column(Database::fetchAll('SELECT u.id,u.first_name,u.last_name,u.color FROM customer_access ca JOIN users u ON u.id=ca.user_id WHERE ca.customer_id=?', [$c['id']]), null, 'id');
        $c['shared_users'] = array_values($c['shared_users']);
        if ($c['account_manager']) {
            $c['account_manager_user'] = Database::fetchOne('SELECT id,first_name,last_name,color FROM users WHERE id=?', [$c['account_manager']]);
        }
        return $c;
    }

    private static function json(Response $response, mixed $data, int $status = 200): Response
    {
        $response->getBody()->write(json_encode($data, JSON_UNESCAPED_UNICODE));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($status);
    }

    private static function error(Response $response, string $msg, int $status = 400): Response
    {
        return self::json($response, ['detail' => $msg], $status);
    }
}
