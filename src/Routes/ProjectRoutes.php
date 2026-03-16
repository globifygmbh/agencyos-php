<?php

declare(strict_types=1);

namespace AgencyOS\Routes;

use AgencyOS\Core\{Database, Security, Helpers, EmailService, Storage, Gamification};
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;

class ProjectRoutes
{
    public static function register(App $app): void
    {
        // POST /api/projects
        $app->post('/api/projects', function (Request $request, Response $response) {
            $user = Security::getCurrentUser($request);
            $body = (array) $request->getParsedBody();

            if (empty($body['name'])) return self::error($response, 'Name erforderlich', 400);

            $id = Helpers::uuid();
            Database::insert('projects', [
                'id'          => $id,
                'name'        => $body['name'],
                'description' => $body['description'] ?? null,
                'customer_id' => $body['customer_id'] ?? null,
                'status'      => $body['status'] ?? 'active',
                'deadline'    => $body['deadline'] ?? null,
                'budget'      => $body['budget'] ?? null,
                'color'       => $body['color'] ?? '#3B82F6',
                'created_by'  => $user['id'],
                'created_at'  => Helpers::now(),
                'updated_at'  => Helpers::now(),
            ]);

            // Add creator as member
            Database::insert('project_members', ['project_id' => $id, 'user_id' => $user['id']]);

            // Add other team members
            $members = $body['team_members'] ?? [];
            foreach ($members as $memberId) {
                if ($memberId !== $user['id']) {
                    Database::insert('project_members', ['project_id' => $id, 'user_id' => $memberId]);
                    // Notify
                    Helpers::createNotification($memberId, 'project_added', 'Zu Projekt hinzugefügt', 'Du wurdest zum Projekt "' . $body['name'] . '" hinzugefügt', '/projects/' . $id);
                    $member = Database::fetchOne('SELECT * FROM users WHERE id=?', [$memberId]);
                    if ($member && Helpers::shouldSendEmail($member, 'project_added')) {
                        EmailService::sendProjectAddedEmail($member['email'], Helpers::userName($member), ['name' => $body['name'], 'id' => $id]);
                    }
                }
            }

            return self::json($response, self::getProjectWithDetails($id), 201);
        });

        // GET /api/projects
        $app->get('/api/projects', function (Request $request, Response $response) {
            $user   = Security::getCurrentUser($request);
            $params = $request->getQueryParams();

            $sql   = "SELECT p.* FROM projects p JOIN project_members pm ON pm.project_id=p.id WHERE pm.user_id=? AND p.is_archived=0";
            $binds = [$user['id']];
            if (!empty($params['status'])) { $sql .= ' AND p.status=?'; $binds[] = $params['status']; }
            $sql .= ' ORDER BY p.updated_at DESC';

            $projects = Database::fetchAll($sql, $binds);
            return self::json($response, array_map(fn($p) => self::getProjectWithDetails($p['id']), $projects));
        });

        // GET /api/projects/{project_id}
        $app->get('/api/projects/{project_id}', function (Request $request, Response $response, array $args) {
            Security::getCurrentUser($request);
            $project = self::getProjectWithDetails($args['project_id']);
            if (!$project) return self::error($response, 'Projekt nicht gefunden', 404);
            return self::json($response, $project);
        });

        // PUT /api/projects/{project_id}
        $app->put('/api/projects/{project_id}', function (Request $request, Response $response, array $args) {
            $user      = Security::getCurrentUser($request);
            $body      = (array) $request->getParsedBody();
            $projectId = $args['project_id'];

            $project = Database::fetchOne('SELECT * FROM projects WHERE id=?', [$projectId]);
            if (!$project) return self::error($response, 'Projekt nicht gefunden', 404);

            $allowed = ['name','description','status','deadline','budget','color','customer_id'];
            $updates = ['updated_at' => Helpers::now()];
            foreach ($allowed as $f) {
                if (array_key_exists($f, $body)) $updates[$f] = $body[$f];
            }

            // Handle archiving
            if (!empty($body['is_archived'])) {
                $updates['is_archived'] = 1;
                $updates['archived_at'] = Helpers::now();
            }

            Database::update('projects', $updates, ['id' => $projectId]);

            // Update team members
            if (isset($body['team_members']) && is_array($body['team_members'])) {
                $existing = array_column(Database::fetchAll('SELECT user_id FROM project_members WHERE project_id=?', [$projectId]), 'user_id');
                $new      = $body['team_members'];

                $toAdd    = array_diff($new, $existing);
                $toRemove = array_diff($existing, $new);

                foreach ($toAdd as $uid) {
                    Database::insert('project_members', ['project_id' => $projectId, 'user_id' => $uid]);
                    Helpers::createNotification($uid, 'project_added', 'Zu Projekt hinzugefügt', 'Du wurdest zum Projekt "' . $project['name'] . '" hinzugefügt', '/projects/' . $projectId);
                }
                foreach ($toRemove as $uid) {
                    Database::delete('project_members', ['project_id' => $projectId, 'user_id' => $uid]);
                }
            }

            return self::json($response, self::getProjectWithDetails($projectId));
        });

        // POST /api/projects/{project_id}/milestones
        $app->post('/api/projects/{project_id}/milestones', function (Request $request, Response $response, array $args) {
            $user      = Security::getCurrentUser($request);
            $body      = (array) $request->getParsedBody();
            $projectId = $args['project_id'];

            if (empty($body['title'])) return self::error($response, 'Titel erforderlich', 400);

            $maxOrder = Database::fetchOne('SELECT MAX(sort_order) as m FROM project_milestones WHERE project_id=?', [$projectId])['m'] ?? 0;
            $id       = Helpers::uuid();
            Database::insert('project_milestones', [
                'id'          => $id,
                'project_id'  => $projectId,
                'title'       => $body['title'],
                'description' => $body['description'] ?? null,
                'due_date'    => $body['due_date'] ?? null,
                'sort_order'  => ((int)$maxOrder) + 1,
                'created_at'  => Helpers::now(),
            ]);
            return self::json($response, Database::fetchOne('SELECT * FROM project_milestones WHERE id=?', [$id]), 201);
        });

        // PUT /api/projects/{project_id}/milestones/{milestone_id}/complete
        $app->put('/api/projects/{project_id}/milestones/{milestone_id}/complete', function (Request $request, Response $response, array $args) {
            $user        = Security::getCurrentUser($request);
            $milestoneId = $args['milestone_id'];
            $projectId   = $args['project_id'];

            $milestone = Database::fetchOne('SELECT * FROM project_milestones WHERE id=? AND project_id=?', [$milestoneId, $projectId]);
            if (!$milestone) return self::error($response, 'Meilenstein nicht gefunden', 404);

            Database::update('project_milestones', [
                'is_completed' => 1,
                'completed_at' => Helpers::now(),
                'completed_by' => $user['id'],
            ], ['id' => $milestoneId]);

            // Gamification
            Gamification::addPoints($user['id'], Gamification::POINTS['milestone'], 'milestone', '🏁 Meilenstein erreicht: ' . $milestone['title']);
            Gamification::checkProjectAchievements($user['id']);

            // Notify project members
            $members = Database::fetchAll('SELECT user_id FROM project_members WHERE project_id=?', [$projectId]);
            foreach ($members as $m) {
                if ($m['user_id'] !== $user['id']) {
                    Helpers::createNotification($m['user_id'], 'milestone_complete', 'Meilenstein erreicht', $milestone['title'], '/projects/' . $projectId);
                }
            }

            return self::json($response, Database::fetchOne('SELECT * FROM project_milestones WHERE id=?', [$milestoneId]));
        });

        // PUT /api/projects/{project_id}/milestones/{milestone_id}/reopen
        $app->put('/api/projects/{project_id}/milestones/{milestone_id}/reopen', function (Request $request, Response $response, array $args) {
            Security::getCurrentUser($request);
            Database::update('project_milestones', ['is_completed' => 0, 'completed_at' => null, 'completed_by' => null], ['id' => $args['milestone_id']]);
            return self::json($response, ['message' => 'Meilenstein wiedereröffnet']);
        });

        // GET /api/projects/{project_id}/time-entries
        $app->get('/api/projects/{project_id}/time-entries', function (Request $request, Response $response, array $args) {
            Security::getCurrentUser($request);
            $entries = Database::fetchAll(
                "SELECT te.*, u.first_name,u.last_name,u.color,u.profile_image FROM time_entries te JOIN users u ON u.id=te.user_id WHERE te.project_id=? ORDER BY te.start_time DESC",
                [$args['project_id']]
            );

            // Group by user
            $byUser = [];
            foreach ($entries as $e) {
                $uid = $e['user_id'];
                if (!isset($byUser[$uid])) {
                    $byUser[$uid] = ['user_id' => $uid, 'name' => trim($e['first_name'] . ' ' . $e['last_name']), 'total_seconds' => 0, 'entries' => []];
                }
                $byUser[$uid]['total_seconds'] += (int) $e['duration'];
                $byUser[$uid]['entries'][] = $e;
            }
            return self::json($response, array_values($byUser));
        });

        // POST /api/projects/{project_id}/files
        $app->post('/api/projects/{project_id}/files', function (Request $request, Response $response, array $args) {
            $user      = Security::getCurrentUser($request);
            $projectId = $args['project_id'];
            $files     = $request->getUploadedFiles();

            if (empty($files['file'])) return self::error($response, 'Keine Datei', 400);

            $project = Database::fetchOne('SELECT * FROM projects WHERE id=?', [$projectId]);
            if (!$project) return self::error($response, 'Projekt nicht gefunden', 404);

            $stored = Storage::store($files['file'], 'files');
            $id     = Helpers::uuid();
            Database::insert('project_files', [
                'id'          => $id,
                'project_id'  => $projectId,
                'file_url'    => $stored['url'],
                'file_name'   => $stored['name'],
                'file_size'   => $stored['size'],
                'file_type'   => $stored['type'],
                'uploaded_by' => $user['id'],
                'created_at'  => Helpers::now(),
            ]);
            return self::json($response, ['id' => $id, 'file_url' => $stored['url'], 'file_name' => $stored['name']], 201);
        });

        // DELETE /api/projects/{project_id}/files/{file_id}
        $app->delete('/api/projects/{project_id}/files/{file_id}', function (Request $request, Response $response, array $args) {
            $user = Security::getCurrentUser($request);
            Security::requireRole($user, ['CHEF', 'ACCOUNT_MANAGER']);

            $file = Database::fetchOne('SELECT * FROM project_files WHERE id=? AND project_id=?', [$args['file_id'], $args['project_id']]);
            if (!$file) return self::error($response, 'Datei nicht gefunden', 404);

            Storage::delete($file['file_url']);
            Database::delete('project_files', ['id' => $args['file_id']]);
            return self::json($response, ['message' => 'Datei gelöscht']);
        });
    }

    private static function getProjectWithDetails(string $projectId): ?array
    {
        $project = Database::fetchOne('SELECT * FROM projects WHERE id=?', [$projectId]);
        if (!$project) return null;

        // Team members
        $members = Database::fetchAll(
            'SELECT u.id,u.first_name,u.last_name,u.profile_image,u.color,u.role FROM project_members pm JOIN users u ON u.id=pm.user_id WHERE pm.project_id=?',
            [$projectId]
        );
        $project['team_members'] = $members;

        // Milestones
        $milestones = Database::fetchAll('SELECT * FROM project_milestones WHERE project_id=? ORDER BY sort_order', [$projectId]);
        $project['milestones'] = $milestones;

        // Progress
        $total    = count($milestones);
        $done     = count(array_filter($milestones, fn($m) => $m['is_completed']));
        $project['progress'] = $total > 0 ? round(($done / $total) * 100) : 0;

        // Files
        $project['files'] = Database::fetchAll('SELECT * FROM project_files WHERE project_id=? ORDER BY created_at DESC', [$projectId]);

        // Total time
        $time = Database::fetchOne('SELECT COALESCE(SUM(duration),0) as s FROM time_entries WHERE project_id=?', [$projectId]);
        $project['total_seconds'] = (int) ($time['s'] ?? 0);

        // Customer
        if ($project['customer_id']) {
            $project['customer'] = Database::fetchOne('SELECT id,name,logo_url,color FROM customers WHERE id=?', [$project['customer_id']]);
        }

        return $project;
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
