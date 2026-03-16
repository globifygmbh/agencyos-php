<?php

declare(strict_types=1);

namespace AgencyOS\Routes;

use AgencyOS\Core\{Database, Security, Helpers, EmailService, Storage};
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;

class AdminRoutes
{
    public static function register(App $app): void
    {
        // GET /api/settings  (public - for login page branding)
        $app->get('/api/settings', function (Request $request, Response $response) {
            $s = Helpers::getSystemSettings();
            return self::json($response, [
                'company_name'    => $s['company_name'] ?? 'AgencyOS',
                'company_logo'    => $s['company_logo'] ?? null,
                'primary_color'   => $s['primary_color'] ?? '#3B82F6',
                'secondary_color' => $s['secondary_color'] ?? '#1E40AF',
                'app_url'         => $s['app_url'] ?? null,
            ]);
        });

        // GET /api/admin/settings  (authenticated)
        $app->get('/api/admin/settings', function (Request $request, Response $response) {
            $user = Security::getCurrentUser($request);
            Security::requireRole($user, 'CHEF');
            return self::json($response, Helpers::getSystemSettings());
        });

        // PUT /api/admin/settings
        $app->put('/api/admin/settings', function (Request $request, Response $response) {
            $user = Security::getCurrentUser($request);
            Security::requireRole($user, 'CHEF');
            $body = (array) $request->getParsedBody();

            $allowed = ['company_name','primary_color','secondary_color','app_url','frontend_url'];
            $updates = [];
            foreach ($allowed as $f) {
                if (array_key_exists($f, $body)) $updates[$f] = $body[$f];
            }
            if (isset($body['activity_types'])) {
                $updates['activity_types'] = json_encode($body['activity_types']);
            }
            if (!empty($updates)) {
                $updates['updated_at'] = Helpers::now();
                Database::update('system_settings', $updates, ['id' => 'default']);
            }

            Helpers::createAuditLog($user['id'], Helpers::userName($user), 'update_settings', 'system_settings', 'default');
            return self::json($response, Helpers::getSystemSettings());
        });

        // POST /api/admin/settings/logo
        $app->post('/api/admin/settings/logo', function (Request $request, Response $response) {
            $user  = Security::getCurrentUser($request);
            Security::requireRole($user, 'CHEF');
            $files = $request->getUploadedFiles();
            if (empty($files['file'])) return self::error($response, 'Keine Datei', 400);

            $stored = Storage::store($files['file'], 'logos');
            Database::update('system_settings', ['company_logo' => $stored['url']], ['id' => 'default']);
            return self::json($response, ['logo_url' => $stored['url']]);
        });

        // GET /api/task-statuses
        $app->get('/api/task-statuses', function (Request $request, Response $response) {
            Security::getCurrentUser($request);
            $statuses = Database::fetchAll('SELECT * FROM task_statuses ORDER BY sort_order');
            return self::json($response, $statuses);
        });

        // POST /api/admin/task-statuses
        $app->post('/api/admin/task-statuses', function (Request $request, Response $response) {
            $user = Security::getCurrentUser($request);
            Security::requireRole($user, 'CHEF');
            $body = (array) $request->getParsedBody();
            if (empty($body['name'])) return self::error($response, 'Name erforderlich', 400);

            $maxOrder = Database::fetchOne('SELECT MAX(sort_order) as m FROM task_statuses')['m'] ?? 0;
            $id = Helpers::uuid();
            Database::insert('task_statuses', [
                'id'         => $id,
                'name'       => $body['name'],
                'color'      => $body['color'] ?? '#6B7280',
                'emoji'      => $body['emoji'] ?? null,
                'sort_order' => ((int)$maxOrder) + 1,
                'is_default' => 0,
                'is_done'    => (int) ($body['is_done'] ?? 0),
                'created_at' => Helpers::now(),
            ]);
            return self::json($response, Database::fetchOne('SELECT * FROM task_statuses WHERE id=?', [$id]), 201);
        });

        // PUT /api/admin/task-statuses/{status_id}
        $app->put('/api/admin/task-statuses/{status_id}', function (Request $request, Response $response, array $args) {
            $user = Security::getCurrentUser($request);
            Security::requireRole($user, 'CHEF');
            $body = (array) $request->getParsedBody();
            $sid  = $args['status_id'];

            $updates = [];
            foreach (['name','color','emoji','is_done','sort_order'] as $f) {
                if (array_key_exists($f, $body)) $updates[$f] = $body[$f];
            }
            if (!empty($updates)) Database::update('task_statuses', $updates, ['id' => $sid]);
            return self::json($response, Database::fetchOne('SELECT * FROM task_statuses WHERE id=?', [$sid]));
        });

        // DELETE /api/admin/task-statuses/{status_id}
        $app->delete('/api/admin/task-statuses/{status_id}', function (Request $request, Response $response, array $args) {
            $user = Security::getCurrentUser($request);
            Security::requireRole($user, 'CHEF');
            $sid = $args['status_id'];

            $inUse = Database::fetchOne('SELECT id FROM tasks WHERE status_id=? LIMIT 1', [$sid]);
            if ($inUse) return self::error($response, 'Status wird noch verwendet', 400);

            Database::delete('task_statuses', ['id' => $sid]);
            return self::json($response, ['message' => 'Gelöscht']);
        });

        // PUT /api/admin/task-statuses/reorder
        $app->put('/api/admin/task-statuses/reorder', function (Request $request, Response $response) {
            $user  = Security::getCurrentUser($request);
            Security::requireRole($user, 'CHEF');
            $items = (array) $request->getParsedBody();
            foreach ($items as $index => $item) {
                if (!empty($item['id'])) {
                    Database::update('task_statuses', ['sort_order' => $index], ['id' => $item['id']]);
                }
            }
            return self::json($response, ['message' => 'Reihenfolge gespeichert']);
        });

        // POST /api/admin/users/{user_id}/invite
        $app->post('/api/admin/users/{user_id}/invite', function (Request $request, Response $response, array $args) {
            $me   = Security::getCurrentUser($request);
            Security::requireRole($me, 'CHEF');
            $uid  = $args['user_id'];
            $user = Database::fetchOne('SELECT * FROM users WHERE id=?', [$uid]);
            if (!$user) return self::error($response, 'User nicht gefunden', 404);

            $token   = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+7 days'));
            Database::update('users', [
                'invitation_token'   => $token,
                'invitation_expires' => $expires,
                'setup_completed'    => 0,
            ], ['id' => $uid]);

            $setupLink = Helpers::getAppUrl() . '/setup?token=' . $token;
            EmailService::sendSetupInvitationEmail($user['email'], Helpers::userName($user), $setupLink);

            return self::json($response, ['setup_link' => $setupLink]);
        });

        // POST /api/admin/users/{user_id}/reset-password
        $app->post('/api/admin/users/{user_id}/reset-password', function (Request $request, Response $response, array $args) {
            $me   = Security::getCurrentUser($request);
            Security::requireRole($me, 'CHEF');
            $uid  = $args['user_id'];
            $user = Database::fetchOne('SELECT * FROM users WHERE id=?', [$uid]);
            if (!$user) return self::error($response, 'User nicht gefunden', 404);

            $newPw = substr(bin2hex(random_bytes(8)), 0, 12);
            Database::update('users', ['password_hash' => Security::hashPassword($newPw), 'updated_at' => Helpers::now()], ['id' => $uid]);
            EmailService::sendPasswordResetEmail($user['email'], Helpers::userName($user), $newPw);

            Helpers::createAuditLog($me['id'], Helpers::userName($me), 'reset_password', 'user', $uid);
            return self::json($response, ['message' => 'Passwort zurückgesetzt und per E-Mail versandt']);
        });

        // GET /api/admin/audit-logs
        $app->get('/api/admin/audit-logs', function (Request $request, Response $response) {
            $user = Security::getCurrentUser($request);
            Security::requireRole($user, 'CHEF');
            $params      = $request->getQueryParams();
            $entityType  = $params['entity_type'] ?? null;
            $filterUser  = $params['user_id'] ?? null;
            $limit       = min((int) ($params['limit'] ?? 50), 200);

            $sql    = 'SELECT * FROM audit_logs WHERE 1=1';
            $binds  = [];
            if ($entityType) { $sql .= ' AND entity_type = ?'; $binds[] = $entityType; }
            if ($filterUser) { $sql .= ' AND user_id = ?';     $binds[] = $filterUser; }
            $sql .= ' ORDER BY created_at DESC LIMIT ' . $limit;

            return self::json($response, Database::fetchAll($sql, $binds));
        });

        // GET /api/admin/permissions
        $app->get('/api/admin/permissions', function (Request $request, Response $response) {
            $user = Security::getCurrentUser($request);
            Security::requireRole($user, 'CHEF');
            $s    = Helpers::getSystemSettings();
            $perms = Helpers::jsonDecode($s['permissions'] ?? null, self::defaultPermissions());
            return self::json($response, $perms);
        });

        // PUT /api/admin/permissions
        $app->put('/api/admin/permissions', function (Request $request, Response $response) {
            $user  = Security::getCurrentUser($request);
            Security::requireRole($user, 'CHEF');
            $body  = (array) $request->getParsedBody();
            Database::update('system_settings', ['permissions' => json_encode($body)], ['id' => 'default']);
            return self::json($response, $body);
        });

        // GET /api/admin/activity-types
        $app->get('/api/admin/activity-types', function (Request $request, Response $response) {
            Security::getCurrentUser($request);
            $s     = Helpers::getSystemSettings();
            $types = Helpers::jsonDecode($s['activity_types'] ?? null, self::defaultActivityTypes());
            return self::json($response, $types);
        });
    }

    private static function defaultPermissions(): array
    {
        return [
            'CHEF'            => ['all'],
            'ACCOUNT_MANAGER' => ['tasks', 'projects', 'customers', 'time_entries', 'calendar', 'chat', 'benefits', 'vacation'],
            'EMPLOYEE'        => ['tasks', 'time_entries', 'calendar', 'chat', 'benefits', 'vacation'],
            'BUCHHALTUNG'     => ['tasks', 'time_entries', 'tools'],
        ];
    }

    private static function defaultActivityTypes(): array
    {
        return ['Entwicklung', 'Design', 'Meeting', 'Konzeption', 'Präsentation', 'Verwaltung', 'Kundengespräch', 'Recherche'];
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
