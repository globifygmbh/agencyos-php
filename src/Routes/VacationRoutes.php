<?php

declare(strict_types=1);

namespace AgencyOS\Routes;

use AgencyOS\Core\{Database, Security, Helpers, EmailService, Gamification};
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;

class VacationRoutes
{
    public static function register(App $app): void
    {
        // POST /api/vacations
        $app->post('/api/vacations', function (Request $request, Response $response) {
            $user = Security::getCurrentUser($request);
            $body = (array) $request->getParsedBody();

            if (empty($body['start_date']) || empty($body['end_date'])) {
                return self::error($response, 'Start- und Enddatum erforderlich', 400);
            }

            $start = new \DateTimeImmutable($body['start_date']);
            $end   = new \DateTimeImmutable($body['end_date']);
            if ($start > $end) return self::error($response, 'Startdatum muss vor Enddatum liegen', 400);

            $days = Helpers::countBusinessDays($start, $end);

            // Check remaining days
            $remaining = (int)($user['vacation_days'] ?? 28) - (int)($user['vacation_days_used'] ?? 0);
            if ($days > $remaining) {
                return self::error($response, "Nicht genug Urlaubstage. Verfügbar: $remaining, Beantragt: $days", 400);
            }

            $id = Helpers::uuid();
            Database::insert('vacations', [
                'id'         => $id,
                'user_id'    => $user['id'],
                'start_date' => $body['start_date'],
                'end_date'   => $body['end_date'],
                'days'       => $days,
                'reason'     => $body['reason'] ?? null,
                'status'     => 'pending',
                'created_at' => Helpers::now(),
                'updated_at' => Helpers::now(),
            ]);

            // Notify managers (CHEF role)
            $managers = Database::fetchAll('SELECT * FROM users WHERE role=? AND is_active=1', ['CHEF']);
            $requesterName = Helpers::userName($user);
            foreach ($managers as $mgr) {
                Helpers::createNotification($mgr['id'], 'vacation_request', 'Urlaubsantrag', "$requesterName hat Urlaub beantragt: {$body['start_date']} - {$body['end_date']}", '/vacations');
                if (Helpers::shouldSendEmail($mgr, 'vacation_request')) {
                    EmailService::sendVacationRequestEmail($mgr['email'], Helpers::userName($mgr), ['start_date' => $body['start_date'], 'end_date' => $body['end_date'], 'days' => $days], $requesterName);
                }
            }

            // Achievement
            Gamification::checkAndAwardAchievement($user['id'], 'vacation_planner');

            return self::json($response, Database::fetchOne('SELECT * FROM vacations WHERE id=?', [$id]), 201);
        });

        // GET /api/vacations
        $app->get('/api/vacations', function (Request $request, Response $response) {
            $user   = Security::getCurrentUser($request);
            $params = $request->getQueryParams();

            $sql   = 'SELECT v.*,u.first_name,u.last_name,u.color,u.profile_image FROM vacations v JOIN users u ON u.id=v.user_id WHERE 1=1';
            $binds = [];

            // Non-admins see only their own
            if (!in_array($user['role'], ['CHEF', 'ACCOUNT_MANAGER'])) {
                $sql .= ' AND v.user_id=?';
                $binds[] = $user['id'];
            }

            if (!empty($params['status']))  { $sql .= ' AND v.status=?';    $binds[] = $params['status']; }
            if (!empty($params['user_id'])) { $sql .= ' AND v.user_id=?';   $binds[] = $params['user_id']; }
            $sql .= ' ORDER BY v.start_date DESC';

            return self::json($response, Database::fetchAll($sql, $binds));
        });

        // GET /api/vacations/calendar
        $app->get('/api/vacations/calendar', function (Request $request, Response $response) {
            Security::getCurrentUser($request);
            $params = $request->getQueryParams();
            $month  = $params['month'] ?? date('m');
            $year   = $params['year']  ?? date('Y');

            $firstDay = sprintf('%04d-%02d-01', $year, $month);
            $lastDay  = date('Y-m-t', strtotime($firstDay));

            // Approved vacations this month
            $vacations = Database::fetchAll(
                "SELECT v.*,u.first_name,u.last_name,u.color FROM vacations v JOIN users u ON u.id=v.user_id WHERE v.status='approved' AND v.start_date<=? AND v.end_date>=?",
                [$lastDay, $firstDay]
            );

            // Birthdays
            $birthdays = Database::fetchAll(
                "SELECT id,first_name,last_name,birthday,color FROM users WHERE is_active=1 AND birthday IS NOT NULL AND DATE_FORMAT(birthday,'%m')=?",
                [str_pad($month, 2, '0', STR_PAD_LEFT)]
            );

            return self::json($response, ['vacations' => $vacations, 'birthdays' => $birthdays]);
        });

        // PUT /api/vacations/{vacation_id}/approve
        $app->put('/api/vacations/{vacation_id}/approve', function (Request $request, Response $response, array $args) {
            $user       = Security::getCurrentUser($request);
            Security::requireRole($user, 'CHEF');
            $vacationId = $args['vacation_id'];

            $vacation = Database::fetchOne('SELECT * FROM vacations WHERE id=?', [$vacationId]);
            if (!$vacation) return self::error($response, 'Urlaub nicht gefunden', 404);
            if ($vacation['status'] !== 'pending') return self::error($response, 'Urlaub ist nicht mehr pending', 400);

            Database::update('vacations', [
                'status'      => 'approved',
                'approved_by' => $user['id'],
                'approved_at' => Helpers::now(),
                'updated_at'  => Helpers::now(),
            ], ['id' => $vacationId]);

            // Update vacation days used
            Database::execute('UPDATE users SET vacation_days_used = vacation_days_used + ? WHERE id = ?', [$vacation['days'], $vacation['user_id']]);

            // Create time entries for each vacation day
            $start   = new \DateTimeImmutable($vacation['start_date']);
            $end     = new \DateTimeImmutable($vacation['end_date']);
            $emp     = Database::fetchOne('SELECT * FROM users WHERE id=?', [$vacation['user_id']]);
            $dailySecs = $emp ? (((float)$emp['weekly_hours'] / 5) * 3600) : 28800; // default 8h

            $current = $start;
            while ($current <= $end) {
                if (Helpers::isBusinessDay($current)) {
                    $dateStr = $current->format('Y-m-d');
                    Database::insert('time_entries', [
                        'id'            => Helpers::uuid(),
                        'user_id'       => $vacation['user_id'],
                        'description'   => 'Urlaub',
                        'start_time'    => $dateStr . ' 08:00:00',
                        'end_time'      => $dateStr . ' 16:00:00',
                        'duration'      => (int) $dailySecs,
                        'activity_type' => 'Urlaub',
                        'is_billable'   => 0,
                        'created_at'    => Helpers::now(),
                        'updated_at'    => Helpers::now(),
                    ]);

                    // Also create calendar event
                    Database::insert('calendar_events', [
                        'id'         => Helpers::uuid(),
                        'user_id'    => $vacation['user_id'],
                        'title'      => 'Urlaub',
                        'start_date' => $dateStr . ' 00:00:00',
                        'end_date'   => $dateStr . ' 23:59:59',
                        'all_day'    => 1,
                        'color'      => '#10B981',
                        'category'   => 'vacation',
                        'vacation_id'=> $vacationId,
                        'created_at' => Helpers::now(),
                        'updated_at' => Helpers::now(),
                    ]);
                }
                $current = $current->modify('+1 day');
            }

            // Notify employee
            $employee = Database::fetchOne('SELECT * FROM users WHERE id=?', [$vacation['user_id']]);
            if ($employee) {
                Helpers::createNotification($employee['id'], 'vacation_approved', 'Urlaub genehmigt ✅', "Dein Urlaub vom {$vacation['start_date']} bis {$vacation['end_date']} wurde genehmigt", '/vacations');
                if (Helpers::shouldSendEmail($employee, 'vacation_approved')) {
                    EmailService::sendVacationApprovedEmail($employee['email'], Helpers::userName($employee), $vacation);
                }
            }

            Gamification::addPoints($vacation['user_id'], Gamification::POINTS['vacation_approved'], 'vacation', '🌴 Urlaub genehmigt');

            return self::json($response, Database::fetchOne('SELECT * FROM vacations WHERE id=?', [$vacationId]));
        });

        // PUT /api/vacations/{vacation_id}/reject
        $app->put('/api/vacations/{vacation_id}/reject', function (Request $request, Response $response, array $args) {
            $user       = Security::getCurrentUser($request);
            Security::requireRole($user, 'CHEF');
            $vacationId = $args['vacation_id'];
            $body       = (array) $request->getParsedBody();
            $reason     = $body['reason'] ?? '';

            $vacation = Database::fetchOne('SELECT * FROM vacations WHERE id=?', [$vacationId]);
            if (!$vacation) return self::error($response, 'Urlaub nicht gefunden', 404);

            Database::update('vacations', [
                'status'        => 'rejected',
                'reject_reason' => $reason,
                'rejected_at'   => Helpers::now(),
                'updated_at'    => Helpers::now(),
            ], ['id' => $vacationId]);

            $employee = Database::fetchOne('SELECT * FROM users WHERE id=?', [$vacation['user_id']]);
            if ($employee) {
                Helpers::createNotification($employee['id'], 'vacation_rejected', 'Urlaub abgelehnt', "Dein Urlaub wurde leider abgelehnt", '/vacations');
                if (Helpers::shouldSendEmail($employee, 'vacation_rejected')) {
                    EmailService::sendVacationRejectedEmail($employee['email'], Helpers::userName($employee), $vacation, $reason);
                }
            }

            return self::json($response, Database::fetchOne('SELECT * FROM vacations WHERE id=?', [$vacationId]));
        });

        // POST /api/admin/vacations/refill (annual reset)
        $app->post('/api/admin/vacations/refill', function (Request $request, Response $response) {
            $user = Security::getCurrentUser($request);
            Security::requireRole($user, 'CHEF');

            Database::execute('UPDATE users SET vacation_days_used=0, vacation_days_carry=0 WHERE is_active=1');
            Helpers::createAuditLog($user['id'], Helpers::userName($user), 'vacation_refill', 'system', null);
            return self::json($response, ['message' => 'Urlaubstage zurückgesetzt']);
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
