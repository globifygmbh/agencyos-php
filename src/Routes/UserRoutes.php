<?php

declare(strict_types=1);

namespace AgencyOS\Routes;

use AgencyOS\Core\{Database, Security, Helpers, EmailService, Storage};
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;

class UserRoutes
{
    public static function register(App $app): void
    {
        // GET /api/users
        $app->get('/api/users', function (Request $request, Response $response) {
            Security::getCurrentUser($request);
            $users = Database::fetchAll(
                'SELECT id,username,email,first_name,last_name,role,is_active,profile_image,color,
                        position,phone,birthday,weekly_hours,vacation_days,vacation_days_used,
                        start_date,last_login,last_active,created_at
                 FROM `users` WHERE `is_active` = 1 ORDER BY first_name, last_name'
            );
            return self::json($response, $users);
        });

        // GET /api/users/me/menu-items
        $app->get('/api/users/me/menu-items', function (Request $request, Response $response) {
            $user  = Security::getCurrentUser($request);
            $items = Database::fetchAll(
                'SELECT * FROM `custom_menu_items` WHERE `user_id` = ? ORDER BY sort_order',
                [$user['id']]
            );
            return self::json($response, $items);
        });

        // POST /api/users/me/menu-items
        $app->post('/api/users/me/menu-items', function (Request $request, Response $response) {
            $user = Security::getCurrentUser($request);
            $body = (array) $request->getParsedBody();

            if (empty($body['title']) || empty($body['url'])) {
                return self::error($response, 'Titel und URL erforderlich', 400);
            }

            $id = Helpers::uuid();
            Database::insert('custom_menu_items', [
                'id'         => $id,
                'user_id'    => $user['id'],
                'title'      => $body['title'],
                'url'        => $body['url'],
                'icon'       => $body['icon'] ?? null,
                'sort_order' => 0,
                'created_at' => Helpers::now(),
            ]);
            return self::json($response, ['id' => $id], 201);
        });

        // PUT /api/users/me/menu-items  (reorder)
        $app->put('/api/users/me/menu-items', function (Request $request, Response $response) {
            $user  = Security::getCurrentUser($request);
            $items = (array) $request->getParsedBody();
            foreach ($items as $index => $item) {
                if (!empty($item['id'])) {
                    Database::update('custom_menu_items', ['sort_order' => $index], ['id' => $item['id'], 'user_id' => $user['id']]);
                }
            }
            return self::json($response, ['message' => 'Reihenfolge gespeichert']);
        });

        // PUT /api/users/me/menu-items/{item_id}
        $app->put('/api/users/me/menu-items/{item_id}', function (Request $request, Response $response, array $args) {
            $user   = Security::getCurrentUser($request);
            $body   = (array) $request->getParsedBody();
            $itemId = $args['item_id'];

            $updates = [];
            if (isset($body['title'])) $updates['title'] = $body['title'];
            if (isset($body['url']))   $updates['url']   = $body['url'];
            if (isset($body['icon']))  $updates['icon']  = $body['icon'];
            if (empty($updates)) return self::error($response, 'Keine Änderungen', 400);

            Database::update('custom_menu_items', $updates, ['id' => $itemId, 'user_id' => $user['id']]);
            return self::json($response, ['message' => 'Aktualisiert']);
        });

        // DELETE /api/users/me/menu-items/{item_id}
        $app->delete('/api/users/me/menu-items/{item_id}', function (Request $request, Response $response, array $args) {
            $user = Security::getCurrentUser($request);
            Database::delete('custom_menu_items', ['id' => $args['item_id'], 'user_id' => $user['id']]);
            return self::json($response, ['message' => 'Gelöscht']);
        });

        // GET /api/users/{user_id}
        $app->get('/api/users/{user_id}', function (Request $request, Response $response, array $args) {
            Security::getCurrentUser($request);
            $user = Database::fetchOne(
                'SELECT id,username,email,first_name,last_name,role,is_active,profile_image,color,
                        position,phone,birthday,weekly_hours,vacation_days,vacation_days_used,
                        start_date,last_login,last_active,created_at
                 FROM `users` WHERE `id` = ?',
                [$args['user_id']]
            );
            if (!$user) return self::error($response, 'User nicht gefunden', 404);
            return self::json($response, $user);
        });

        // POST /api/users  (CHEF only)
        $app->post('/api/users', function (Request $request, Response $response) {
            $me = Security::getCurrentUser($request);
            Security::requireRole($me, 'CHEF');

            $body = (array) $request->getParsedBody();
            if (empty($body['email']) || empty($body['username'])) {
                return self::error($response, 'Email und Benutzername erforderlich', 400);
            }

            // Check duplicate
            $exists = Database::fetchOne('SELECT id FROM users WHERE email = ? OR username = ?', [$body['email'], $body['username']]);
            if ($exists) return self::error($response, 'Email oder Benutzername bereits vergeben', 400);

            $password = $body['password'] ?? bin2hex(random_bytes(6));
            $id       = Helpers::uuid();

            Database::insert('users', [
                'id'           => $id,
                'username'     => $body['username'],
                'email'        => strtolower($body['email']),
                'password_hash'=> Security::hashPassword($password),
                'first_name'   => $body['first_name'] ?? '',
                'last_name'    => $body['last_name'] ?? '',
                'role'         => $body['role'] ?? 'EMPLOYEE',
                'is_active'    => 1,
                'color'        => $body['color'] ?? '#3B82F6',
                'position'     => $body['position'] ?? '',
                'phone'        => $body['phone'] ?? '',
                'birthday'     => $body['birthday'] ?? null,
                'weekly_hours' => $body['weekly_hours'] ?? 40.00,
                'vacation_days'=> $body['vacation_days'] ?? 28,
                'setup_completed' => 1,
                'created_at'   => Helpers::now(),
                'updated_at'   => Helpers::now(),
            ]);

            // Send welcome email
            $name = trim(($body['first_name'] ?? '') . ' ' . ($body['last_name'] ?? '')) ?: $body['username'];
            EmailService::sendWelcomeEmail($body['email'], $name, $body['username'], $password);

            Helpers::createAuditLog($me['id'], Helpers::userName($me), 'create_user', 'user', $id, ['username' => $body['username']]);

            $created = Database::fetchOne('SELECT id,username,email,first_name,last_name,role,is_active,color FROM users WHERE id = ?', [$id]);
            return self::json($response, $created, 201);
        });

        // PUT /api/users/{user_id}
        $app->put('/api/users/{user_id}', function (Request $request, Response $response, array $args) {
            $me   = Security::getCurrentUser($request);
            $body = (array) $request->getParsedBody();
            $uid  = $args['user_id'];

            // Can update self or CHEF can update anyone
            if ($me['id'] !== $uid && $me['role'] !== 'CHEF') {
                return self::error($response, 'Keine Berechtigung', 403);
            }

            $allowed = ['first_name','last_name','phone','position','birthday','weekly_hours',
                        'vacation_days','color','chat_theme','notification_prefs'];
            if ($me['role'] === 'CHEF') {
                $allowed = array_merge($allowed, ['role','is_active','username','email','vacation_days_used']);
            }

            $updates = [];
            foreach ($allowed as $field) {
                if (array_key_exists($field, $body)) {
                    $val = $body[$field];
                    if (in_array($field, ['notification_prefs','chat_settings']) && is_array($val)) {
                        $val = json_encode($val);
                    }
                    if ($field === 'email') $val = strtolower($val);
                    $updates[$field] = $val;
                }
            }
            if (empty($updates)) return self::error($response, 'Keine Änderungen', 400);

            $updates['updated_at'] = Helpers::now();
            Database::update('users', $updates, ['id' => $uid]);

            $updated = Database::fetchOne('SELECT id,username,email,first_name,last_name,role,is_active,profile_image,color,position,phone,birthday,weekly_hours,vacation_days FROM users WHERE id = ?', [$uid]);
            return self::json($response, $updated);
        });

        // GET /api/users/{user_id}/workload
        $app->get('/api/users/{user_id}/workload', function (Request $request, Response $response, array $args) {
            Security::getCurrentUser($request);
            $uid  = $args['user_id'];
            $user = Database::fetchOne('SELECT * FROM users WHERE id = ?', [$uid]);
            if (!$user) return self::error($response, 'User nicht gefunden', 404);

            $weeklyHours = (float) ($user['weekly_hours'] ?? 40);

            // Count open tasks
            $openTasks = Database::fetchAll(
                "SELECT t.*, ts.is_done FROM tasks t
                 LEFT JOIN task_statuses ts ON ts.id = t.status_id
                 WHERE t.assigned_to = ? AND (ts.is_done = 0 OR ts.id IS NULL) AND t.is_archived = 0",
                [$uid]
            );

            $totalEstimated = 0;
            foreach ($openTasks as $task) {
                $hrs  = (float) ($task['estimated_hours'] ?? 0);
                $mult = match($task['priority'] ?? 'MEDIUM') { 'HIGH' => 1.5, 'LOW' => 0.8, default => 1.0 };
                $totalEstimated += $hrs * $mult;
            }

            $workloadPct = $weeklyHours > 0 ? round(($totalEstimated / $weeklyHours) * 100, 1) : 0;
            $status      = match(true) {
                $workloadPct >= 120 => 'overloaded',
                $workloadPct >= 90  => 'at_capacity',
                $workloadPct >= 60  => 'busy',
                $workloadPct >= 30  => 'moderate',
                default             => 'available',
            };

            return self::json($response, [
                'user_id'           => $uid,
                'weekly_capacity'   => $weeklyHours,
                'estimated_hours'   => round($totalEstimated, 1),
                'workload_percent'  => $workloadPct,
                'status'            => $status,
                'open_task_count'   => count($openTasks),
            ]);
        });

        // GET /api/team/workload-overview
        $app->get('/api/team/workload-overview', function (Request $request, Response $response) {
            Security::getCurrentUser($request);
            $users = Database::fetchAll('SELECT id,first_name,last_name,username,weekly_hours,color,profile_image FROM users WHERE is_active=1');

            $overview = [];
            foreach ($users as $u) {
                $weeklyHours = (float) ($u['weekly_hours'] ?? 40);
                $openCount   = (int) (Database::fetchOne(
                    "SELECT COUNT(*) as c FROM tasks t LEFT JOIN task_statuses ts ON ts.id=t.status_id WHERE t.assigned_to=? AND (ts.is_done=0 OR ts.id IS NULL) AND t.is_archived=0",
                    [$u['id']]
                )['c'] ?? 0);

                $overview[] = array_merge($u, ['open_tasks' => $openCount, 'weekly_hours' => $weeklyHours]);
            }
            return self::json($response, $overview);
        });

        // POST /api/users/{user_id}/avatar  (upload profile image)
        $app->post('/api/users/{user_id}/avatar', function (Request $request, Response $response, array $args) {
            $me  = Security::getCurrentUser($request);
            $uid = $args['user_id'];

            if ($me['id'] !== $uid && $me['role'] !== 'CHEF') {
                return self::error($response, 'Keine Berechtigung', 403);
            }

            $files = $request->getUploadedFiles();
            if (empty($files['file'])) return self::error($response, 'Keine Datei', 400);

            $stored = Storage::store($files['file'], 'avatars');
            Database::update('users', ['profile_image' => $stored['url']], ['id' => $uid]);

            return self::json($response, ['profile_image' => $stored['url']]);
        });
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
