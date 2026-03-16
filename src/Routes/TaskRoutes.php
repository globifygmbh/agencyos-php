<?php

declare(strict_types=1);

namespace AgencyOS\Routes;

use AgencyOS\Core\{Database, Security, Helpers, EmailService, Storage, Gamification};
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;

class TaskRoutes
{
    public static function register(App $app): void
    {
        // POST /api/tasks
        $app->post('/api/tasks', function (Request $request, Response $response) {
            $user = Security::getCurrentUser($request);
            $body = (array) $request->getParsedBody();

            if (empty($body['title'])) return self::error($response, 'Titel erforderlich', 400);

            // Auto-assign account manager from project
            $accountManager = $body['account_manager'] ?? null;
            if (!$accountManager && !empty($body['project_id'])) {
                $project = Database::fetchOne('SELECT created_by FROM projects WHERE id=?', [$body['project_id']]);
                $accountManager = $project['created_by'] ?? null;
            }

            $id = Helpers::uuid();
            Database::insert('tasks', [
                'id'               => $id,
                'title'            => $body['title'],
                'description'      => $body['description'] ?? null,
                'project_id'       => $body['project_id'] ?? null,
                'customer_id'      => $body['customer_id'] ?? null,
                'status_id'        => $body['status_id'] ?? self::getDefaultStatusId(),
                'priority'         => $body['priority'] ?? 'MEDIUM',
                'deadline'         => $body['deadline'] ?? null,
                'estimated_hours'  => $body['estimated_hours'] ?? null,
                'assigned_to'      => $body['assigned_to'] ?? null,
                'account_manager'  => $accountManager,
                'created_by'       => $user['id'],
                'is_recurring'     => (int) ($body['is_recurring'] ?? 0),
                'recurring_config' => isset($body['recurring_config']) ? json_encode($body['recurring_config']) : null,
                'tags'             => isset($body['tags']) ? json_encode($body['tags']) : null,
                'sort_order'       => 0,
                'created_at'       => Helpers::now(),
                'updated_at'       => Helpers::now(),
            ]);

            // Notify assigned user
            if (!empty($body['assigned_to']) && $body['assigned_to'] !== $user['id']) {
                $assignee = Database::fetchOne('SELECT * FROM users WHERE id=?', [$body['assigned_to']]);
                if ($assignee) {
                    Helpers::createNotification($body['assigned_to'], 'task_assigned', 'Neue Aufgabe',
                        'Du hast eine neue Aufgabe: ' . $body['title'], '/tasks/' . $id);
                    if (Helpers::shouldSendEmail($assignee, 'task_assigned')) {
                        $task = Database::fetchOne('SELECT * FROM tasks WHERE id=?', [$id]);
                        EmailService::sendTaskAssignmentEmail($assignee['email'], Helpers::userName($assignee), $task, Helpers::userName($user));
                    }
                }
            }

            $task = self::enrichTask(Database::fetchOne('SELECT * FROM tasks WHERE id=?', [$id]));
            return self::json($response, $task, 201);
        });

        // GET /api/tasks
        $app->get('/api/tasks', function (Request $request, Response $response) {
            $user   = Security::getCurrentUser($request);
            $params = $request->getQueryParams();

            $sql    = "SELECT t.* FROM tasks t WHERE t.is_archived = 0";
            $binds  = [];

            if (!empty($params['project_id'])) { $sql .= ' AND t.project_id=?'; $binds[] = $params['project_id']; }
            if (!empty($params['status_id']))  { $sql .= ' AND t.status_id=?';  $binds[] = $params['status_id']; }
            if (!empty($params['assigned_to'])){ $sql .= ' AND t.assigned_to=?';$binds[] = $params['assigned_to']; }
            if (!empty($params['customer_id'])){ $sql .= ' AND t.customer_id=?';$binds[] = $params['customer_id']; }

            $sql .= ' ORDER BY t.sort_order ASC, t.deadline ASC, t.created_at DESC';

            $tasks = Database::fetchAll($sql, $binds);
            return self::json($response, self::enrichTasksBatch($tasks));
        });

        // GET /api/tasks/my
        $app->get('/api/tasks/my', function (Request $request, Response $response) {
            $user   = Security::getCurrentUser($request);
            $tasks  = Database::fetchAll(
                "SELECT * FROM tasks WHERE assigned_to=? AND is_archived=0 ORDER BY deadline ASC, created_at DESC",
                [$user['id']]
            );
            return self::json($response, self::enrichTasksBatch($tasks));
        });

        // GET /api/tasks/{task_id}
        $app->get('/api/tasks/{task_id}', function (Request $request, Response $response, array $args) {
            Security::getCurrentUser($request);
            $task = Database::fetchOne('SELECT * FROM tasks WHERE id=?', [$args['task_id']]);
            if (!$task) return self::error($response, 'Aufgabe nicht gefunden', 404);

            $task     = self::enrichTask($task);
            $comments = Database::fetchAll('SELECT tc.*, u.first_name,u.last_name,u.profile_image,u.color FROM task_comments tc LEFT JOIN users u ON u.id=tc.user_id WHERE tc.task_id=? ORDER BY tc.created_at ASC', [$args['task_id']]);
            $files    = Database::fetchAll('SELECT * FROM task_files WHERE task_id=?', [$args['task_id']]);
            $task['comments'] = $comments;
            $task['files']    = $files;
            return self::json($response, $task);
        });

        // PUT /api/tasks/{task_id}
        $app->put('/api/tasks/{task_id}', function (Request $request, Response $response, array $args) {
            $user   = Security::getCurrentUser($request);
            $body   = (array) $request->getParsedBody();
            $taskId = $args['task_id'];

            $task = Database::fetchOne('SELECT * FROM tasks WHERE id=?', [$taskId]);
            if (!$task) return self::error($response, 'Aufgabe nicht gefunden', 404);

            $oldAssigned = $task['assigned_to'];
            $oldStatusId = $task['status_id'];

            $allowed = ['title','description','priority','deadline','estimated_hours','assigned_to','status_id','project_id','customer_id','account_manager','tags','is_recurring','recurring_config'];
            $updates = ['updated_at' => Helpers::now()];
            foreach ($allowed as $f) {
                if (array_key_exists($f, $body)) {
                    $val = $body[$f];
                    if (in_array($f, ['tags','recurring_config']) && is_array($val)) $val = json_encode($val);
                    $updates[$f] = $val;
                }
            }
            Database::update('tasks', $updates, ['id' => $taskId]);

            // Check if task just completed → gamification
            $newStatusId = $updates['status_id'] ?? $oldStatusId;
            if ($newStatusId !== $oldStatusId) {
                $newStatus = Database::fetchOne('SELECT * FROM task_statuses WHERE id=?', [$newStatusId]);
                if ($newStatus && $newStatus['is_done'] && $task['assigned_to']) {
                    Gamification::addPoints($task['assigned_to'], Gamification::POINTS['task_completed'], 'task_completed', '✅ Aufgabe erledigt: ' . $task['title']);
                    Gamification::checkTaskAchievements($task['assigned_to']);
                }
            }

            // Notify new assignee
            $newAssigned = $updates['assigned_to'] ?? $oldAssigned;
            if ($newAssigned && $newAssigned !== $oldAssigned && $newAssigned !== $user['id']) {
                $assignee = Database::fetchOne('SELECT * FROM users WHERE id=?', [$newAssigned]);
                if ($assignee) {
                    Helpers::createNotification($newAssigned, 'task_assigned', 'Neue Aufgabe', 'Du hast eine neue Aufgabe: ' . $task['title'], '/tasks/' . $taskId);
                    if (Helpers::shouldSendEmail($assignee, 'task_assigned')) {
                        EmailService::sendTaskAssignmentEmail($assignee['email'], Helpers::userName($assignee), array_merge($task, $updates), Helpers::userName($user));
                    }
                }
            }

            return self::json($response, self::enrichTask(Database::fetchOne('SELECT * FROM tasks WHERE id=?', [$taskId])));
        });

        // DELETE /api/tasks/{task_id}
        $app->delete('/api/tasks/{task_id}', function (Request $request, Response $response, array $args) {
            $user   = Security::getCurrentUser($request);
            $taskId = $args['task_id'];
            $task   = Database::fetchOne('SELECT * FROM tasks WHERE id=?', [$taskId]);
            if (!$task) return self::error($response, 'Aufgabe nicht gefunden', 404);

            // Delete associated files from storage
            $files = Database::fetchAll('SELECT * FROM task_files WHERE task_id=?', [$taskId]);
            foreach ($files as $f) Storage::delete($f['file_url']);

            Database::delete('tasks', ['id' => $taskId]);
            Helpers::createAuditLog($user['id'], Helpers::userName($user), 'delete_task', 'task', $taskId, ['title' => $task['title']]);
            return self::json($response, ['message' => 'Aufgabe gelöscht']);
        });

        // PUT /api/tasks/reorder
        $app->put('/api/tasks/reorder', function (Request $request, Response $response) {
            Security::getCurrentUser($request);
            $items = (array) $request->getParsedBody();
            foreach ($items as $index => $item) {
                if (!empty($item['id'])) {
                    $upd = ['sort_order' => $index, 'updated_at' => Helpers::now()];
                    if (!empty($item['status_id'])) $upd['status_id'] = $item['status_id'];
                    Database::update('tasks', $upd, ['id' => $item['id']]);
                }
            }
            return self::json($response, ['message' => 'Reihenfolge gespeichert']);
        });

        // PUT /api/tasks/{task_id}/archive
        $app->put('/api/tasks/{task_id}/archive', function (Request $request, Response $response, array $args) {
            Security::getCurrentUser($request);
            Database::update('tasks', ['is_archived' => 1, 'archived_at' => Helpers::now(), 'updated_at' => Helpers::now()], ['id' => $args['task_id']]);
            return self::json($response, ['message' => 'Archiviert']);
        });

        // PUT /api/tasks/{task_id}/unarchive
        $app->put('/api/tasks/{task_id}/unarchive', function (Request $request, Response $response, array $args) {
            Security::getCurrentUser($request);
            Database::update('tasks', ['is_archived' => 0, 'archived_at' => null, 'updated_at' => Helpers::now()], ['id' => $args['task_id']]);
            return self::json($response, ['message' => 'Aus Archiv wiederhergestellt']);
        });

        // POST /api/tasks/{task_id}/comments
        $app->post('/api/tasks/{task_id}/comments', function (Request $request, Response $response, array $args) {
            $user   = Security::getCurrentUser($request);
            $body   = (array) $request->getParsedBody();
            $taskId = $args['task_id'];

            if (empty($body['content'])) return self::error($response, 'Inhalt erforderlich', 400);

            $task = Database::fetchOne('SELECT * FROM tasks WHERE id=?', [$taskId]);
            if (!$task) return self::error($response, 'Aufgabe nicht gefunden', 404);

            $id = Helpers::uuid();
            Database::insert('task_comments', [
                'id'         => $id,
                'task_id'    => $taskId,
                'user_id'    => $user['id'],
                'content'    => $body['content'],
                'created_at' => Helpers::now(),
            ]);

            // Notify task owner / account manager
            $notifyIds = array_filter(array_unique([$task['assigned_to'], $task['account_manager']]), fn($x) => $x && $x !== $user['id']);
            foreach ($notifyIds as $uid) {
                Helpers::createNotification($uid, 'task_comment', 'Neuer Kommentar', Helpers::userName($user) . ' kommentierte: ' . $task['title'], '/tasks/' . $taskId);
                $recipient = Database::fetchOne('SELECT * FROM users WHERE id=?', [$uid]);
                if ($recipient && Helpers::shouldSendEmail($recipient, 'task_comment')) {
                    EmailService::sendTaskCommentEmail($recipient['email'], Helpers::userName($recipient), $task, Helpers::userName($user), $body['content']);
                }
            }

            $comment = Database::fetchOne('SELECT tc.*,u.first_name,u.last_name,u.profile_image,u.color FROM task_comments tc LEFT JOIN users u ON u.id=tc.user_id WHERE tc.id=?', [$id]);
            return self::json($response, $comment, 201);
        });

        // DELETE /api/tasks/{task_id}/comments/{comment_id}
        $app->delete('/api/tasks/{task_id}/comments/{comment_id}', function (Request $request, Response $response, array $args) {
            $user      = Security::getCurrentUser($request);
            $commentId = $args['comment_id'];
            $comment   = Database::fetchOne('SELECT * FROM task_comments WHERE id=?', [$commentId]);
            if (!$comment) return self::error($response, 'Kommentar nicht gefunden', 404);

            if ($comment['user_id'] !== $user['id'] && $user['role'] !== 'CHEF') {
                return self::error($response, 'Keine Berechtigung', 403);
            }
            Database::delete('task_comments', ['id' => $commentId]);
            return self::json($response, ['message' => 'Kommentar gelöscht']);
        });

        // POST /api/tasks/{task_id}/files
        $app->post('/api/tasks/{task_id}/files', function (Request $request, Response $response, array $args) {
            $user   = Security::getCurrentUser($request);
            $taskId = $args['task_id'];
            $files  = $request->getUploadedFiles();

            if (empty($files['file'])) return self::error($response, 'Keine Datei', 400);
            $task = Database::fetchOne('SELECT * FROM tasks WHERE id=?', [$taskId]);
            if (!$task) return self::error($response, 'Aufgabe nicht gefunden', 404);

            $stored = Storage::store($files['file'], 'files');
            $id     = Helpers::uuid();
            Database::insert('task_files', [
                'id'          => $id,
                'task_id'     => $taskId,
                'file_url'    => $stored['url'],
                'file_name'   => $stored['name'],
                'file_size'   => $stored['size'],
                'file_type'   => $stored['type'],
                'uploaded_by' => $user['id'],
                'created_at'  => Helpers::now(),
            ]);

            // Notify account manager
            if ($task['account_manager'] && $task['account_manager'] !== $user['id']) {
                Helpers::createNotification($task['account_manager'], 'task_file', 'Datei hochgeladen', Helpers::userName($user) . ' hat eine Datei zu ' . $task['title'] . ' hochgeladen', '/tasks/' . $taskId);
            }

            return self::json($response, ['id' => $id, 'file_url' => $stored['url'], 'file_name' => $stored['name']], 201);
        });

        // DELETE /api/tasks/{task_id}/files/{file_id}
        $app->delete('/api/tasks/{task_id}/files/{file_id}', function (Request $request, Response $response, array $args) {
            $user   = Security::getCurrentUser($request);
            $file   = Database::fetchOne('SELECT * FROM task_files WHERE id=? AND task_id=?', [$args['file_id'], $args['task_id']]);
            if (!$file) return self::error($response, 'Datei nicht gefunden', 404);

            if ($file['uploaded_by'] !== $user['id'] && $user['role'] !== 'CHEF') {
                return self::error($response, 'Keine Berechtigung', 403);
            }
            Storage::delete($file['file_url']);
            Database::delete('task_files', ['id' => $args['file_id']]);
            return self::json($response, ['message' => 'Datei gelöscht']);
        });
    }

    private static function getDefaultStatusId(): ?string
    {
        $s = Database::fetchOne('SELECT id FROM task_statuses WHERE is_default=1 LIMIT 1');
        return $s ? $s['id'] : null;
    }

    private static function enrichTask(?array $task): ?array
    {
        if (!$task) return null;
        if ($task['tags'])             $task['tags']             = Helpers::jsonDecode($task['tags'], []);
        if ($task['recurring_config']) $task['recurring_config'] = Helpers::jsonDecode($task['recurring_config'], null);

        $status  = $task['status_id'] ? Database::fetchOne('SELECT * FROM task_statuses WHERE id=?', [$task['status_id']]) : null;
        $task['status']  = $status;

        $assigned = $task['assigned_to'] ? Database::fetchOne('SELECT id,first_name,last_name,profile_image,color FROM users WHERE id=?', [$task['assigned_to']]) : null;
        $task['assignee'] = $assigned;

        return $task;
    }

    private static function enrichTasksBatch(array $tasks): array
    {
        if (empty($tasks)) return [];

        // Fetch all users at once
        $userIds   = array_unique(array_filter(array_merge(
            array_column($tasks, 'assigned_to'),
            array_column($tasks, 'account_manager'),
            array_column($tasks, 'created_by')
        )));
        $statusIds = array_unique(array_filter(array_column($tasks, 'status_id')));

        $users    = [];
        $statuses = [];

        if ($userIds) {
            $placeholders = implode(',', array_fill(0, count($userIds), '?'));
            foreach (Database::fetchAll("SELECT id,first_name,last_name,profile_image,color,username FROM users WHERE id IN ($placeholders)", array_values($userIds)) as $u) {
                $users[$u['id']] = $u;
            }
        }
        if ($statusIds) {
            $placeholders = implode(',', array_fill(0, count($statusIds), '?'));
            foreach (Database::fetchAll("SELECT * FROM task_statuses WHERE id IN ($placeholders)", array_values($statusIds)) as $s) {
                $statuses[$s['id']] = $s;
            }
        }

        return array_map(function ($task) use ($users, $statuses) {
            if ($task['tags'])             $task['tags']             = Helpers::jsonDecode($task['tags'], []);
            if ($task['recurring_config']) $task['recurring_config'] = Helpers::jsonDecode($task['recurring_config'], null);
            $task['status']   = $statuses[$task['status_id']] ?? null;
            $task['assignee'] = $users[$task['assigned_to']] ?? null;
            return $task;
        }, $tasks);
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
