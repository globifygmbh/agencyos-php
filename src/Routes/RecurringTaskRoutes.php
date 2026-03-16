<?php

declare(strict_types=1);

namespace AgencyOS\Routes;

use AgencyOS\Core\{Database, Security, Helpers};
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;

class RecurringTaskRoutes
{
    public static function register(App $app): void
    {
        // POST /api/tasks/process-recurring  (manual trigger)
        $app->post('/api/tasks/process-recurring', function (Request $request, Response $response) {
            $user = Security::getCurrentUser($request);
            Security::requireRole($user, 'CHEF');
            $count = self::processRecurringTasks();
            return self::json($response, ['message' => "Wiederkehrende Aufgaben verarbeitet", 'created' => $count]);
        });

        // POST /api/tasks/auto-archive  (manual trigger)
        $app->post('/api/tasks/auto-archive', function (Request $request, Response $response) {
            $user = Security::getCurrentUser($request);
            Security::requireRole($user, 'CHEF');
            $count = self::autoArchiveCompletedTasks();
            return self::json($response, ['message' => "Aufgaben archiviert", 'archived' => $count]);
        });
    }

    /**
     * Create next instances of recurring tasks when current one is completed.
     */
    public static function processRecurringTasks(): int
    {
        $created = 0;

        // Find completed recurring tasks that haven't spawned a next instance
        $doneTasks = Database::fetchAll(
            "SELECT t.* FROM tasks t
             JOIN task_statuses ts ON ts.id = t.status_id
             WHERE t.is_recurring = 1 AND ts.is_done = 1
               AND t.recurring_config IS NOT NULL
               AND NOT EXISTS (
                 SELECT 1 FROM tasks t2 WHERE t2.parent_task_id = t.id
               )",
        );

        foreach ($doneTasks as $task) {
            $config   = Helpers::jsonDecode($task['recurring_config'], []);
            $interval = $config['interval'] ?? 'weekly';

            // Calculate next due date
            $baseDate = $task['deadline'] ? new \DateTimeImmutable($task['deadline']) : new \DateTimeImmutable();
            $nextDate = match($interval) {
                'daily'    => $baseDate->modify('+1 day'),
                'weekly'   => $baseDate->modify('+1 week'),
                'biweekly' => $baseDate->modify('+2 weeks'),
                'monthly'  => $baseDate->modify('+1 month'),
                default    => $baseDate->modify('+1 week'),
            };

            // Get first available status (not done)
            $firstStatus = Database::fetchOne('SELECT id FROM task_statuses WHERE is_done=0 ORDER BY sort_order LIMIT 1');
            $newStatusId  = $firstStatus ? $firstStatus['id'] : $task['status_id'];

            $newId = Helpers::uuid();
            Database::insert('tasks', [
                'id'               => $newId,
                'title'            => $task['title'],
                'description'      => $task['description'],
                'project_id'       => $task['project_id'],
                'customer_id'      => $task['customer_id'],
                'status_id'        => $newStatusId,
                'priority'         => $task['priority'],
                'deadline'         => $nextDate->format('Y-m-d H:i:s'),
                'estimated_hours'  => $task['estimated_hours'],
                'assigned_to'      => $task['assigned_to'],
                'account_manager'  => $task['account_manager'],
                'created_by'       => $task['created_by'],
                'is_recurring'     => 1,
                'recurring_config' => $task['recurring_config'],
                'parent_task_id'   => $task['id'],
                'tags'             => $task['tags'],
                'created_at'       => Helpers::now(),
                'updated_at'       => Helpers::now(),
            ]);

            // Notify assigned user
            if ($task['assigned_to']) {
                Helpers::createNotification(
                    $task['assigned_to'],
                    'task_assigned',
                    'Wiederkehrende Aufgabe',
                    'Neue Instanz: ' . $task['title'] . ' (fällig am ' . $nextDate->format('d.m.Y') . ')',
                    '/tasks/' . $newId
                );
            }
            $created++;
        }

        return $created;
    }

    /**
     * Archive tasks completed more than 3 days ago.
     */
    public static function autoArchiveCompletedTasks(): int
    {
        $threshold = date('Y-m-d H:i:s', strtotime('-3 days'));

        $tasks = Database::fetchAll(
            "SELECT t.id FROM tasks t
             JOIN task_statuses ts ON ts.id = t.status_id
             WHERE ts.is_done = 1 AND t.is_archived = 0 AND t.updated_at < ?",
            [$threshold]
        );

        foreach ($tasks as $task) {
            Database::update('tasks', ['is_archived' => 1, 'archived_at' => Helpers::now()], ['id' => $task['id']]);
        }

        return count($tasks);
    }

    private static function json(Response $response, mixed $data, int $status = 200): Response
    {
        $response->getBody()->write(json_encode($data, JSON_UNESCAPED_UNICODE));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($status);
    }
}
