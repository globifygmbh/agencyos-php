<?php

/**
 * AgencyOS Scheduler – runs via cron
 *
 * Add to crontab:
 *   * * * * * php /path/to/agencyos-php/scheduler.php >> /var/log/agencyos-cron.log 2>&1
 */

declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

use AgencyOS\Core\{Config, Database, Helpers, EmailService};
use AgencyOS\Routes\RecurringTaskRoutes;

Config::load();

$hour  = (int) date('H');
$min   = (int) date('i');
$dow   = (int) date('N'); // 1=Mon, 5=Fri
$month = (int) date('m');
$day   = (int) date('d');

// ---- 08:00 daily: deadline reminders ----
if ($hour === 8 && $min === 0) {
    $tomorrow = date('Y-m-d', strtotime('+1 day'));
    $tasks    = Database::fetchAll(
        "SELECT t.*,u.email,u.first_name,u.last_name,u.notification_prefs FROM tasks t
         JOIN users u ON u.id=t.assigned_to
         LEFT JOIN task_statuses ts ON ts.id=t.status_id
         WHERE DATE(t.deadline)=? AND (ts.is_done=0 OR ts.id IS NULL) AND t.is_archived=0",
        [$tomorrow]
    );
    foreach ($tasks as $task) {
        if (Helpers::shouldSendEmail($task, 'task_deadline')) {
            EmailService::sendTaskDeadlineReminderEmail($task['email'], Helpers::userName($task), $task);
        }
    }
    echo date('Y-m-d H:i:s') . " - Deadline reminders sent: " . count($tasks) . "\n";
}

// ---- Friday 17:00: weekly time reports ----
if ($dow === 5 && $hour === 17 && $min === 0) {
    $weekStart = date('Y-m-d', strtotime('monday this week'));
    $weekEnd   = date('Y-m-d', strtotime('sunday this week'));
    $users     = Database::fetchAll('SELECT * FROM users WHERE is_active=1');
    foreach ($users as $user) {
        $actual  = (float)(Database::fetchOne(
            'SELECT COALESCE(SUM(duration),0) as s FROM time_entries WHERE user_id=? AND DATE(start_time) BETWEEN ? AND ?',
            [$user['id'], $weekStart, $weekEnd]
        )['s'] ?? 0) / 3600;
        $target  = (float)($user['weekly_hours'] ?? 40);
        if (Helpers::shouldSendEmail($user, 'weekly_report')) {
            EmailService::sendWeeklyTimeReport($user['email'], Helpers::userName($user), $actual, $target);
        }
    }
    echo date('Y-m-d H:i:s') . " - Weekly reports sent.\n";
}

// ---- Jan 1st 00:00: refill vacation days ----
if ($month === 1 && $day === 1 && $hour === 0 && $min === 0) {
    Database::execute('UPDATE users SET vacation_days_used=0, vacation_days_carry=0 WHERE is_active=1');
    echo date('Y-m-d H:i:s') . " - Vacation days refilled.\n";
}

// ---- Every 5 min: process recurring tasks ----
if ($min % 5 === 0) {
    $count = RecurringTaskRoutes::processRecurringTasks();
    if ($count > 0) echo date('Y-m-d H:i:s') . " - Recurring tasks created: $count\n";
}

// ---- Birthday points (daily at 08:01) ----
if ($hour === 8 && $min === 1) {
    $users = Database::fetchAll("SELECT * FROM users WHERE is_active=1 AND birthday IS NOT NULL AND DATE_FORMAT(birthday,'%m-%d')=DATE_FORMAT(NOW(),'%m-%d')");
    foreach ($users as $u) {
        // Check not already awarded today
        $today    = date('Y-m-d');
        $existing = Database::fetchOne("SELECT id FROM point_transactions WHERE user_id=? AND type='birthday' AND DATE(created_at)=?", [$u['id'], $today]);
        if (!$existing) {
            \AgencyOS\Core\Gamification::addPoints($u['id'], \AgencyOS\Core\Gamification::POINTS['birthday'], 'birthday', '🎂 Herzlichen Glückwunsch zum Geburtstag!');
            Helpers::createNotification($u['id'], 'birthday', '🎂 Alles Gute zum Geburtstag!', 'Das Team wünscht dir alles Gute!', '/dashboard');
        }
    }
}
