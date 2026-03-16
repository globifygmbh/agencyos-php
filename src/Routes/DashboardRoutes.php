<?php

declare(strict_types=1);

namespace AgencyOS\Routes;

use AgencyOS\Core\{Database, Security, Helpers};
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;

class DashboardRoutes
{
    public static function register(App $app): void
    {
        // GET /api/dashboard
        $app->get('/api/dashboard', function (Request $request, Response $response) {
            $user  = Security::getCurrentUser($request);
            $uid   = $user['id'];
            $today = date('Y-m-d');
            $now   = new \DateTimeImmutable('now');

            // Tasks (up to 5 upcoming incomplete)
            $tasks = Database::fetchAll(
                "SELECT t.*, ts.name as status_name, ts.color as status_color, ts.is_done
                 FROM tasks t
                 LEFT JOIN task_statuses ts ON ts.id = t.status_id
                 WHERE t.assigned_to = ? AND (ts.is_done = 0 OR ts.id IS NULL) AND t.is_archived = 0
                 ORDER BY t.deadline ASC, t.created_at DESC
                 LIMIT 5",
                [$uid]
            );

            // Time today
            $todayTime = Database::fetchOne(
                "SELECT COALESCE(SUM(duration),0) as secs FROM time_entries WHERE user_id = ? AND DATE(start_time) = ?",
                [$uid, $today]
            );
            $weekStart  = date('Y-m-d', strtotime('monday this week'));
            $weekEnd    = date('Y-m-d', strtotime('sunday this week'));
            $weekTime   = Database::fetchOne(
                "SELECT COALESCE(SUM(duration),0) as secs FROM time_entries WHERE user_id = ? AND DATE(start_time) BETWEEN ? AND ?",
                [$uid, $weekStart, $weekEnd]
            );

            // Vacations this week
            $vacations = Database::fetchAll(
                "SELECT v.*, u.first_name, u.last_name, u.profile_image
                 FROM vacations v
                 JOIN users u ON u.id = v.user_id
                 WHERE v.status = 'approved' AND v.end_date >= ? AND v.start_date <= ?",
                [$weekStart, $weekEnd]
            );

            // Notes (3 recent)
            $notes = Database::fetchAll(
                'SELECT * FROM notes WHERE user_id = ? ORDER BY updated_at DESC LIMIT 3',
                [$uid]
            );

            // Upcoming calendar events (next 7 days)
            $next7 = date('Y-m-d', strtotime('+7 days'));
            $events = Database::fetchAll(
                "SELECT e.* FROM calendar_events e
                 LEFT JOIN event_participants ep ON ep.event_id = e.id AND ep.user_id = ?
                 WHERE (e.user_id = ? OR ep.user_id = ?)
                   AND DATE(e.start_date) BETWEEN ? AND ?
                 ORDER BY e.start_date ASC LIMIT 10",
                [$uid, $uid, $uid, $today, $next7]
            );

            // Special events
            $specials = [];
            $dow = (int) $now->format('N'); // 1=Mon
            if ($dow === 1) $specials[] = 'monday';
            if ($now->format('d') === '01') $specials[] = 'month_start';
            if ($now->format('m-d') === '12-24') $specials[] = 'christmas_eve';
            if ($now->format('m-d') === '12-31') $specials[] = 'new_years_eve';

            // Birthday check
            $bdUser = Database::fetchOne(
                "SELECT id,first_name,last_name FROM users WHERE DATE_FORMAT(birthday,'%m-%d') = DATE_FORMAT(NOW(),'%m-%d') AND id != ? AND is_active = 1",
                [$uid]
            );
            if ($bdUser) $specials[] = 'colleague_birthday';

            // Own birthday
            if ($user['birthday'] && date('m-d', strtotime($user['birthday'])) === date('m-d')) {
                $specials[] = 'own_birthday';
            }

            return self::json($response, [
                'tasks'          => $tasks,
                'time_today'     => (int) ($todayTime['secs'] ?? 0),
                'time_week'      => (int) ($weekTime['secs'] ?? 0),
                'vacations'      => $vacations,
                'notes'          => $notes,
                'calendar_events'=> $events,
                'specials'       => $specials,
            ]);
        });

        // GET /api/stats/year-review
        $app->get('/api/stats/year-review', function (Request $request, Response $response) {
            $user = Security::getCurrentUser($request);
            $uid  = $user['id'];
            $year = date('Y');

            $totalHours = (float) (Database::fetchOne(
                "SELECT COALESCE(SUM(duration),0) as s FROM time_entries WHERE user_id = ? AND YEAR(start_time) = ?",
                [$uid, $year]
            )['s'] ?? 0) / 3600;

            $completedTasks = (int) (Database::fetchOne(
                "SELECT COUNT(*) as c FROM tasks t JOIN task_statuses ts ON ts.id=t.status_id WHERE t.assigned_to=? AND ts.is_done=1 AND YEAR(t.updated_at)=?",
                [$uid, $year]
            )['c'] ?? 0);

            $milestones = (int) (Database::fetchOne(
                "SELECT COUNT(*) as c FROM project_milestones WHERE completed_by=? AND YEAR(completed_at)=?",
                [$uid, $year]
            )['c'] ?? 0);

            $vacationDays = (int) (Database::fetchOne(
                "SELECT COALESCE(SUM(days),0) as d FROM vacations WHERE user_id=? AND status='approved' AND YEAR(start_date)=?",
                [$uid, $year]
            )['d'] ?? 0);

            $points = (int) (Database::fetchOne(
                "SELECT COALESCE(SUM(points),0) as p FROM point_transactions WHERE user_id=? AND YEAR(created_at)=?",
                [$uid, $year]
            )['p'] ?? 0);

            $workingDays = (int) (Database::fetchOne(
                "SELECT COUNT(DISTINCT DATE(start_time)) as d FROM time_entries WHERE user_id=? AND YEAR(start_time)=?",
                [$uid, $year]
            )['d'] ?? 0);

            return self::json($response, [
                'year'            => (int) $year,
                'total_hours'     => round($totalHours, 1),
                'completed_tasks' => $completedTasks,
                'milestones'      => $milestones,
                'vacation_days'   => $vacationDays,
                'challenge_points'=> $points,
                'working_days'    => $workingDays,
            ]);
        });
    }

    private static function json(Response $response, mixed $data, int $status = 200): Response
    {
        $response->getBody()->write(json_encode($data, JSON_UNESCAPED_UNICODE));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($status);
    }
}
