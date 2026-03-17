<?php

declare(strict_types=1);

namespace AgencyOS\Routes;

use AgencyOS\Core\{Database, Security, Helpers, Gamification};
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;

class TimeEntryRoutes
{
    public static function register(App $app): void
    {
        // ── /api/time/* aliases (used by frontend) ──────────────────────────

        // GET /api/time/active – running timer (no end_time)
        $app->get('/api/time/active', function (Request $request, Response $response) {
            $user  = Security::getCurrentUser($request);
            $entry = Database::fetchOne(
                'SELECT te.*,p.name as project_name,c.name as customer_name FROM time_entries te LEFT JOIN projects p ON p.id=te.project_id LEFT JOIN customers c ON c.id=te.customer_id WHERE te.user_id=? AND te.end_time IS NULL AND te.duration=0 ORDER BY te.start_time DESC LIMIT 1',
                [$user['id']]
            );
            return self::json($response, $entry ?: (object)[]);
        });

        // POST /api/time/start – start a new timer
        $app->post('/api/time/start', function (Request $request, Response $response) {
            $user = Security::getCurrentUser($request);
            $body = (array) $request->getParsedBody();

            // Stop any running timer first
            $running = Database::fetchOne('SELECT id FROM time_entries WHERE user_id=? AND end_time IS NULL AND duration=0', [$user['id']]);
            if ($running) {
                $now  = Helpers::now();
                $secs = (int) (strtotime($now) - strtotime(Database::fetchOne('SELECT start_time FROM time_entries WHERE id=?', [$running['id']])['start_time']));
                Database::update('time_entries', ['end_time' => $now, 'duration' => $secs, 'updated_at' => $now], ['id' => $running['id']]);
            }

            $id = Helpers::uuid();
            Database::insert('time_entries', [
                'id'            => $id,
                'user_id'       => $user['id'],
                'description'   => $body['description'] ?? null,
                'project_id'    => $body['project_id'] ?? null,
                'customer_id'   => $body['customer_id'] ?? null,
                'start_time'    => $body['start_time'] ?? Helpers::now(),
                'end_time'      => null,
                'duration'      => 0,
                'is_billable'   => 1,
                'created_at'    => Helpers::now(),
                'updated_at'    => Helpers::now(),
            ]);
            return self::json($response, Database::fetchOne('SELECT * FROM time_entries WHERE id=?', [$id]), 201);
        });

        // POST /api/time/{id}/stop – stop a running timer
        $app->post('/api/time/{id}/stop', function (Request $request, Response $response, array $args) {
            $user  = Security::getCurrentUser($request);
            $entry = Database::fetchOne('SELECT * FROM time_entries WHERE id=?', [$args['id']]);
            if (!$entry) return self::error($response, 'Timer nicht gefunden', 404);
            if ($entry['user_id'] !== $user['id']) return self::error($response, 'Keine Berechtigung', 403);

            $body    = (array) $request->getParsedBody();
            $now     = Helpers::now();
            $secs    = (int) (strtotime($now) - strtotime($entry['start_time']));
            $updates = [
                'end_time'      => $now,
                'duration'      => max(1, $secs),
                'updated_at'    => $now,
            ];
            if (!empty($body['customer_id']))    $updates['customer_id']    = $body['customer_id'];
            if (!empty($body['project_id']))     $updates['project_id']     = $body['project_id'];
            if (!empty($body['activity_type']))  $updates['activity_type']  = $body['activity_type'];
            if (!empty($body['description']))    $updates['description']    = $body['description'];
            if (isset($body['is_pause']) && $body['is_pause']) $updates['description'] = '[Pause] ' . ($updates['description'] ?? '');

            Database::update('time_entries', $updates, ['id' => $args['id']]);
            Gamification::checkAndAwardAchievement($user['id'], 'time_tracker');
            return self::json($response, Database::fetchOne('SELECT * FROM time_entries WHERE id=?', [$args['id']]));
        });

        // DELETE /api/time/{id}/cancel – cancel without saving
        $app->delete('/api/time/{id}/cancel', function (Request $request, Response $response, array $args) {
            $user  = Security::getCurrentUser($request);
            $entry = Database::fetchOne('SELECT * FROM time_entries WHERE id=?', [$args['id']]);
            if (!$entry) return self::error($response, 'Timer nicht gefunden', 404);
            if ($entry['user_id'] !== $user['id']) return self::error($response, 'Keine Berechtigung', 403);
            Database::delete('time_entries', ['id' => $args['id']]);
            return self::json($response, ['message' => 'Timer abgebrochen']);
        });

        // GET /api/time – alias for time entries (supports ?date=YYYY-MM-DD)
        $app->get('/api/time', function (Request $request, Response $response) {
            $user   = Security::getCurrentUser($request);
            $params = $request->getQueryParams();
            $sql    = 'SELECT te.*,t.title as task_title,p.name as project_name,c.name as customer_name FROM time_entries te LEFT JOIN tasks t ON t.id=te.task_id LEFT JOIN projects p ON p.id=te.project_id LEFT JOIN customers c ON c.id=te.customer_id WHERE te.user_id=? AND (te.end_time IS NOT NULL OR te.duration > 0)';
            $binds  = [$user['id']];
            if (!empty($params['date'])) { $sql .= ' AND DATE(te.start_time)=?'; $binds[] = $params['date']; }
            if (!empty($params['date_from'])) { $sql .= ' AND DATE(te.start_time)>=?'; $binds[] = $params['date_from']; }
            if (!empty($params['date_to']))   { $sql .= ' AND DATE(te.start_time)<=?'; $binds[] = $params['date_to']; }
            if (!empty($params['project_id'])){ $sql .= ' AND te.project_id=?'; $binds[] = $params['project_id']; }
            $sql .= ' ORDER BY te.start_time DESC LIMIT ' . (int)($params['limit'] ?? 100);
            return self::json($response, Database::fetchAll($sql, $binds));
        });

        // POST /api/time – create entry (alias)
        $app->post('/api/time', function (Request $request, Response $response) {
            $user = Security::getCurrentUser($request);
            $body = (array) $request->getParsedBody();
            if (empty($body['start_time'])) return self::error($response, 'Startzeit erforderlich', 400);
            $duration = $body['end_time'] ? max(0, (int)(strtotime($body['end_time']) - strtotime($body['start_time']))) : (int)($body['duration'] ?? 0);
            $id = Helpers::uuid();
            Database::insert('time_entries', [
                'id' => $id, 'user_id' => $user['id'],
                'description' => $body['description'] ?? null, 'project_id' => $body['project_id'] ?? null,
                'customer_id' => $body['customer_id'] ?? null, 'start_time' => $body['start_time'],
                'end_time' => $body['end_time'] ?? null, 'duration' => $duration,
                'is_billable' => 1, 'activity_type' => $body['activity_type'] ?? null,
                'created_at' => Helpers::now(), 'updated_at' => Helpers::now(),
            ]);
            return self::json($response, Database::fetchOne('SELECT * FROM time_entries WHERE id=?', [$id]), 201);
        });

        // DELETE /api/time/{id} – delete entry (alias)
        $app->delete('/api/time/{id}', function (Request $request, Response $response, array $args) {
            $user  = Security::getCurrentUser($request);
            $entry = Database::fetchOne('SELECT * FROM time_entries WHERE id=?', [$args['id']]);
            if (!$entry) return self::error($response, 'Eintrag nicht gefunden', 404);
            if ($entry['user_id'] !== $user['id'] && $user['role'] !== 'CHEF') return self::error($response, 'Keine Berechtigung', 403);
            Database::delete('time_entries', ['id' => $args['id']]);
            return self::json($response, ['message' => 'Eintrag gelöscht']);
        });

        // GET /api/time/stats – alias
        $app->get('/api/time/stats', function (Request $request, Response $response) {
            $user = Security::getCurrentUser($request);
            $uid  = $user['id'];
            $today = date('Y-m-d');
            $weekStart = date('Y-m-d', strtotime('monday this week'));
            $weekEnd   = date('Y-m-d', strtotime('sunday this week'));
            $monthStart = date('Y-m-01');
            $monthEnd   = date('Y-m-t');
            return self::json($response, [
                'today' => (int)(Database::fetchOne('SELECT COALESCE(SUM(duration),0) as s FROM time_entries WHERE user_id=? AND DATE(start_time)=?', [$uid,$today])['s'] ?? 0),
                'week'  => (int)(Database::fetchOne('SELECT COALESCE(SUM(duration),0) as s FROM time_entries WHERE user_id=? AND DATE(start_time) BETWEEN ? AND ?', [$uid,$weekStart,$weekEnd])['s'] ?? 0),
                'month' => (int)(Database::fetchOne('SELECT COALESCE(SUM(duration),0) as s FROM time_entries WHERE user_id=? AND DATE(start_time) BETWEEN ? AND ?', [$uid,$monthStart,$monthEnd])['s'] ?? 0),
            ]);
        });

        // GET /api/time/export – CSV alias
        $app->get('/api/time/export', function (Request $request, Response $response) {
            $user   = Security::getCurrentUser($request);
            $params = $request->getQueryParams();
            $sql    = 'SELECT te.*,t.title as task_title,p.name as project_name,c.name as customer_name FROM time_entries te LEFT JOIN tasks t ON t.id=te.task_id LEFT JOIN projects p ON p.id=te.project_id LEFT JOIN customers c ON c.id=te.customer_id WHERE te.user_id=?';
            $binds  = [$user['id']];
            if (!empty($params['date_from'])) { $sql .= ' AND DATE(te.start_time)>=?'; $binds[] = $params['date_from']; }
            if (!empty($params['date_to']))   { $sql .= ' AND DATE(te.start_time)<=?'; $binds[] = $params['date_to']; }
            $sql .= ' ORDER BY te.start_time ASC';
            $entries = Database::fetchAll($sql, $binds);
            $csv = "Datum,Start,Ende,Dauer (Std),Aufgabe,Projekt,Kunde,Beschreibung,Typ\n";
            foreach ($entries as $e) {
                $csv .= '"' . implode('","', [
                    date('d.m.Y', strtotime($e['start_time'])),
                    date('H:i', strtotime($e['start_time'])),
                    $e['end_time'] ? date('H:i', strtotime($e['end_time'])) : '',
                    round((float)$e['duration'] / 3600, 2),
                    $e['task_title'] ?? '', $e['project_name'] ?? '', $e['customer_name'] ?? '',
                    $e['description'] ?? '', $e['activity_type'] ?? '',
                ]) . '"' . "\n";
            }
            $response->getBody()->write($csv);
            return $response->withHeader('Content-Type', 'text/csv; charset=utf-8')
                ->withHeader('Content-Disposition', 'attachment; filename="zeiterfassung.csv"');
        });

        // ── /api/time-entries/* (original routes) ───────────────────────────

        // POST /api/time-entries
        $app->post('/api/time-entries', function (Request $request, Response $response) {
            $user = Security::getCurrentUser($request);
            $body = (array) $request->getParsedBody();

            if (empty($body['start_time'])) return self::error($response, 'Startzeit erforderlich', 400);

            $duration = (int) ($body['duration'] ?? 0);
            if ($duration > 86400) return self::error($response, 'Maximale Dauer: 24 Stunden', 400);

            $id = Helpers::uuid();
            Database::insert('time_entries', [
                'id'            => $id,
                'user_id'       => $user['id'],
                'task_id'       => $body['task_id'] ?? null,
                'project_id'    => $body['project_id'] ?? null,
                'customer_id'   => $body['customer_id'] ?? null,
                'description'   => $body['description'] ?? null,
                'start_time'    => $body['start_time'],
                'end_time'      => $body['end_time'] ?? null,
                'duration'      => $duration,
                'pause_time'    => (int) ($body['pause_time'] ?? 0),
                'is_billable'   => (int) ($body['is_billable'] ?? 1),
                'activity_type' => $body['activity_type'] ?? null,
                'created_at'    => Helpers::now(),
                'updated_at'    => Helpers::now(),
            ]);

            // Achievement check
            Gamification::checkAndAwardAchievement($user['id'], 'time_tracker');

            return self::json($response, Database::fetchOne('SELECT * FROM time_entries WHERE id=?', [$id]), 201);
        });

        // GET /api/time-entries
        $app->get('/api/time-entries', function (Request $request, Response $response) {
            $user   = Security::getCurrentUser($request);
            $params = $request->getQueryParams();

            $sql   = 'SELECT te.*,t.title as task_title,p.name as project_name,c.name as customer_name FROM time_entries te LEFT JOIN tasks t ON t.id=te.task_id LEFT JOIN projects p ON p.id=te.project_id LEFT JOIN customers c ON c.id=te.customer_id WHERE te.user_id=?';
            $binds = [$user['id']];

            if (!empty($params['date_from'])) { $sql .= ' AND DATE(te.start_time) >= ?'; $binds[] = $params['date_from']; }
            if (!empty($params['date_to']))   { $sql .= ' AND DATE(te.start_time) <= ?'; $binds[] = $params['date_to']; }
            if (!empty($params['task_id']))   { $sql .= ' AND te.task_id=?';             $binds[] = $params['task_id']; }
            if (!empty($params['project_id'])){ $sql .= ' AND te.project_id=?';          $binds[] = $params['project_id']; }

            $sql .= ' ORDER BY te.start_time DESC';
            return self::json($response, Database::fetchAll($sql, $binds));
        });

        // PUT /api/time-entries/{entry_id}
        $app->put('/api/time-entries/{entry_id}', function (Request $request, Response $response, array $args) {
            $user    = Security::getCurrentUser($request);
            $entryId = $args['entry_id'];
            $entry   = Database::fetchOne('SELECT * FROM time_entries WHERE id=?', [$entryId]);

            if (!$entry) return self::error($response, 'Eintrag nicht gefunden', 404);
            if ($entry['user_id'] !== $user['id'] && $user['role'] !== 'CHEF') return self::error($response, 'Keine Berechtigung', 403);
            if (Helpers::isTimeEntryLocked($entry, $user)) return self::error($response, 'Eintrag ist gesperrt (> 60 Tage)', 403);

            $body    = (array) $request->getParsedBody();
            $allowed = ['description','start_time','end_time','duration','pause_time','is_billable','activity_type','task_id','project_id','customer_id'];
            $updates = ['updated_at' => Helpers::now()];
            foreach ($allowed as $f) {
                if (array_key_exists($f, $body)) $updates[$f] = $body[$f];
            }

            Database::update('time_entries', $updates, ['id' => $entryId]);
            Helpers::createAuditLog($user['id'], Helpers::userName($user), 'update_time_entry', 'time_entry', $entryId);

            return self::json($response, Database::fetchOne('SELECT * FROM time_entries WHERE id=?', [$entryId]));
        });

        // DELETE /api/time-entries/{entry_id}
        $app->delete('/api/time-entries/{entry_id}', function (Request $request, Response $response, array $args) {
            $user    = Security::getCurrentUser($request);
            $entryId = $args['entry_id'];
            $entry   = Database::fetchOne('SELECT * FROM time_entries WHERE id=?', [$entryId]);

            if (!$entry) return self::error($response, 'Eintrag nicht gefunden', 404);
            if ($entry['user_id'] !== $user['id'] && $user['role'] !== 'CHEF') return self::error($response, 'Keine Berechtigung', 403);
            if (Helpers::isTimeEntryLocked($entry, $user)) return self::error($response, 'Eintrag ist gesperrt', 403);

            Database::delete('time_entries', ['id' => $entryId]);
            return self::json($response, ['message' => 'Eintrag gelöscht']);
        });

        // GET /api/time-entries/stats
        $app->get('/api/time-entries/stats', function (Request $request, Response $response) {
            $user   = Security::getCurrentUser($request);
            $uid    = $user['id'];
            $target = (float) ($user['weekly_hours'] ?? 40) * 3600;

            $weekStart = date('Y-m-d', strtotime('monday this week'));
            $weekEnd   = date('Y-m-d', strtotime('sunday this week'));

            $weekActual = (float) (Database::fetchOne(
                'SELECT COALESCE(SUM(duration),0) as s FROM time_entries WHERE user_id=? AND DATE(start_time) BETWEEN ? AND ?',
                [$uid, $weekStart, $weekEnd]
            )['s'] ?? 0);

            $diff  = $weekActual - $target;
            $today = date('Y-m-d');
            $todayActual = (float) (Database::fetchOne(
                'SELECT COALESCE(SUM(duration),0) as s FROM time_entries WHERE user_id=? AND DATE(start_time)=?',
                [$uid, $today]
            )['s'] ?? 0);

            return self::json($response, [
                'week_target'  => $target,
                'week_actual'  => $weekActual,
                'week_diff'    => $diff,
                'today_actual' => $todayActual,
                'is_overtime'  => $diff > 0,
            ]);
        });

        // GET /api/time-entries/alltime-balance
        $app->get('/api/time-entries/alltime-balance', function (Request $request, Response $response) {
            $user     = Security::getCurrentUser($request);
            $uid      = $user['id'];
            $startDate = $user['start_date'] ?? date('Y-01-01');

            // Group by month
            $entries = Database::fetchAll(
                "SELECT DATE_FORMAT(start_time,'%Y-%m') as month, SUM(duration) as actual_secs FROM time_entries WHERE user_id=? AND DATE(start_time) >= ? GROUP BY month ORDER BY month",
                [$uid, $startDate]
            );

            $weeklyHoursPerDay = ((float)($user['weekly_hours'] ?? 40)) / 5;
            $balance = 0;
            $months  = [];
            foreach ($entries as $e) {
                $daysInMonth = (int) date('t', strtotime($e['month'] . '-01'));
                $workDays    = 0;
                for ($d = 1; $d <= $daysInMonth; $d++) {
                    $dt = new \DateTimeImmutable($e['month'] . sprintf('-%02d', $d));
                    if (Helpers::isBusinessDay($dt)) $workDays++;
                }
                $target   = $workDays * $weeklyHoursPerDay * 3600;
                $diff     = (float)$e['actual_secs'] - $target;
                $balance += $diff;
                $months[] = [
                    'month'       => $e['month'],
                    'actual_secs' => (float)$e['actual_secs'],
                    'target_secs' => $target,
                    'diff_secs'   => $diff,
                ];
            }

            return self::json($response, ['balance_secs' => $balance, 'months' => $months]);
        });

        // GET /api/time-entries/export
        $app->get('/api/time-entries/export', function (Request $request, Response $response) {
            $user   = Security::getCurrentUser($request);
            $params = $request->getQueryParams();

            $sql   = 'SELECT te.*,t.title as task_title,p.name as project_name,c.name as customer_name,u.first_name,u.last_name FROM time_entries te LEFT JOIN tasks t ON t.id=te.task_id LEFT JOIN projects p ON p.id=te.project_id LEFT JOIN customers c ON c.id=te.customer_id JOIN users u ON u.id=te.user_id WHERE te.user_id=?';
            $binds = [$user['id']];
            if (!empty($params['date_from'])) { $sql .= ' AND DATE(te.start_time)>=?'; $binds[] = $params['date_from']; }
            if (!empty($params['date_to']))   { $sql .= ' AND DATE(te.start_time)<=?'; $binds[] = $params['date_to']; }
            $sql .= ' ORDER BY te.start_time ASC';
            $entries = Database::fetchAll($sql, $binds);

            $csv = "Datum,Start,Ende,Dauer (Std),Aufgabe,Projekt,Kunde,Beschreibung,Verrechenbar,Typ\n";
            foreach ($entries as $e) {
                $date     = date('d.m.Y', strtotime($e['start_time']));
                $start    = date('H:i', strtotime($e['start_time']));
                $end      = $e['end_time'] ? date('H:i', strtotime($e['end_time'])) : '';
                $hours    = round((float)$e['duration'] / 3600, 2);
                $billable = $e['is_billable'] ? 'Ja' : 'Nein';
                $csv     .= '"' . implode('","', [$date, $start, $end, $hours, $e['task_title'] ?? '', $e['project_name'] ?? '', $e['customer_name'] ?? '', $e['description'] ?? '', $billable, $e['activity_type'] ?? '']) . '"' . "\n";
            }

            $response->getBody()->write($csv);
            return $response
                ->withHeader('Content-Type', 'text/csv; charset=utf-8')
                ->withHeader('Content-Disposition', 'attachment; filename="zeiterfassung.csv"')
                ->withStatus(200);
        });

        // GET /api/admin/time-stats
        $app->get('/api/admin/time-stats', function (Request $request, Response $response) {
            $user = Security::getCurrentUser($request);
            Security::requireRole($user, 'CHEF');

            $users = Database::fetchAll('SELECT id,first_name,last_name,weekly_hours FROM users WHERE is_active=1');
            $result = [];
            foreach ($users as $u) {
                $total = (float) (Database::fetchOne('SELECT COALESCE(SUM(duration),0) as s FROM time_entries WHERE user_id=?', [$u['id']])['s'] ?? 0);
                $result[] = [
                    'user_id'      => $u['id'],
                    'name'         => Helpers::userName($u),
                    'total_hours'  => round($total / 3600, 1),
                    'weekly_hours' => (float) $u['weekly_hours'],
                ];
            }
            return self::json($response, $result);
        });

        // GET /api/tasks/{task_id}/time-per-user
        $app->get('/api/tasks/{task_id}/time-per-user', function (Request $request, Response $response, array $args) {
            Security::getCurrentUser($request);
            $entries = Database::fetchAll(
                'SELECT te.user_id,u.first_name,u.last_name,u.color,SUM(te.duration) as total_secs FROM time_entries te JOIN users u ON u.id=te.user_id WHERE te.task_id=? GROUP BY te.user_id,u.first_name,u.last_name,u.color',
                [$args['task_id']]
            );
            return self::json($response, $entries);
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
