<?php

declare(strict_types=1);

namespace AgencyOS\Core;

class Gamification
{
    const POINTS = [
        'daily_login'      => 1,
        'task_completed'   => 5,
        'weekly_hours'     => 10,
        'birthday'         => 30,
        'christmas'        => 15,
        'milestone'        => 10,
        'vacation_approved'=> 5,
        'first_task'       => 10,
    ];

    public static function getUserPoints(string $userId): int
    {
        $row = Database::fetchOne(
            'SELECT COALESCE(SUM(`points`), 0) AS total FROM `point_transactions` WHERE `user_id` = ?',
            [$userId]
        );
        return (int) ($row['total'] ?? 0);
    }

    public static function addPoints(
        string $userId,
        int $points,
        string $type,
        string $reason = '',
        ?string $rewardId = null,
        ?string $createdBy = null
    ): void {
        Database::insert('point_transactions', [
            'id'         => Helpers::uuid(),
            'user_id'    => $userId,
            'points'     => $points,
            'type'       => $type,
            'reason'     => $reason,
            'reward_id'  => $rewardId,
            'created_by' => $createdBy,
            'created_at' => Helpers::now(),
        ]);

        // Check level achievements after adding points
        self::checkLevelAchievements($userId);
    }

    public static function checkDailyLogin(string $userId): bool
    {
        $today = date('Y-m-d');
        $existing = Database::fetchOne(
            "SELECT id FROM point_transactions WHERE user_id = ? AND type = 'daily_login' AND DATE(created_at) = ?",
            [$userId, $today]
        );
        if ($existing) return false;

        self::addPoints($userId, self::POINTS['daily_login'], 'daily_login', '🌟 Täglicher Login');
        return true;
    }

    public static function checkAndAwardAchievement(string $userId, string $achievementId): bool
    {
        // Already earned?
        $existing = Database::fetchOne(
            'SELECT id FROM user_achievements WHERE user_id = ? AND achievement_id = ?',
            [$userId, $achievementId]
        );
        if ($existing) return false;

        $achievement = Database::fetchOne(
            'SELECT * FROM achievements WHERE id = ?',
            [$achievementId]
        );
        if (!$achievement) return false;

        // Check if requirement is met
        $progress = self::getAchievementProgress($userId, $achievementId);
        if ($progress < $achievement['requirement']) return false;

        // Award
        Database::insert('user_achievements', [
            'id'             => Helpers::uuid(),
            'user_id'        => $userId,
            'achievement_id' => $achievementId,
            'earned_at'      => Helpers::now(),
        ]);

        self::addPoints($userId, $achievement['points'], 'achievement', $achievement['name']);

        Helpers::createNotification(
            $userId,
            'achievement',
            '🏆 Achievement freigeschaltet!',
            $achievement['icon'] . ' ' . $achievement['name'] . ': ' . $achievement['description'],
            '/achievements'
        );

        return true;
    }

    public static function getAchievementProgress(string $userId, string $achievementId): int
    {
        return match($achievementId) {
            'first_task', 'task_machine', 'task_100' => (int) (Database::fetchOne(
                "SELECT COUNT(*) as c FROM tasks WHERE assigned_to = ? AND status_id IN (SELECT id FROM task_statuses WHERE is_done = 1)",
                [$userId]
            )['c'] ?? 0),
            'deadline_hero' => (int) (Database::fetchOne(
                "SELECT COUNT(*) as c FROM tasks WHERE assigned_to = ? AND status_id IN (SELECT id FROM task_statuses WHERE is_done = 1) AND deadline IS NOT NULL AND updated_at <= deadline",
                [$userId]
            )['c'] ?? 0),
            'focus_pro' => (int) (Database::fetchOne(
                "SELECT COUNT(*) as c FROM tasks WHERE assigned_to = ? AND priority = 'HIGH' AND status_id IN (SELECT id FROM task_statuses WHERE is_done = 1)",
                [$userId]
            )['c'] ?? 0),
            'first_project', 'milestone_master' => (int) (Database::fetchOne(
                "SELECT COUNT(*) as c FROM project_milestones WHERE completed_by = ?",
                [$userId]
            )['c'] ?? 0),
            'time_tracker' => (int) (Database::fetchOne(
                "SELECT COUNT(*) as c FROM time_entries WHERE user_id = ?",
                [$userId]
            )['c'] ?? 0),
            'vacation_planner' => (int) (Database::fetchOne(
                "SELECT COUNT(*) as c FROM vacations WHERE user_id = ?",
                [$userId]
            )['c'] ?? 0),
            'communicator' => (int) (Database::fetchOne(
                "SELECT COUNT(*) as c FROM messages WHERE user_id = ?",
                [$userId]
            )['c'] ?? 0),
            'motivator' => (int) (Database::fetchOne(
                "SELECT COUNT(*) as c FROM message_reactions WHERE user_id = ?",
                [$userId]
            )['c'] ?? 0),
            'team_player' => (int) (Database::fetchOne(
                "SELECT COUNT(DISTINCT project_id) as c FROM project_members WHERE user_id = ?",
                [$userId]
            )['c'] ?? 0),
            'level_5'  => self::getUserPoints($userId) >= 500  ? 500  : self::getUserPoints($userId),
            'level_10' => self::getUserPoints($userId) >= 1000 ? 1000 : self::getUserPoints($userId),
            default    => 0,
        };
    }

    public static function checkTaskAchievements(string $userId): void
    {
        foreach (['first_task', 'task_machine', 'task_100', 'deadline_hero', 'focus_pro', 'silent_hero'] as $id) {
            self::checkAndAwardAchievement($userId, $id);
        }
    }

    public static function checkProjectAchievements(string $userId): void
    {
        foreach (['first_project', 'milestone_master'] as $id) {
            self::checkAndAwardAchievement($userId, $id);
        }
    }

    private static function checkLevelAchievements(string $userId): void
    {
        $pts = self::getUserPoints($userId);
        if ($pts >= 500)  self::checkAndAwardAchievement($userId, 'level_5');
        if ($pts >= 1000) self::checkAndAwardAchievement($userId, 'level_10');
    }

    public static function getUserLevel(int $points): array
    {
        $level      = max(1, (int) floor($points / 100) + 1);
        $levelStart = ($level - 1) * 100;
        $levelEnd   = $level * 100;
        return [
            'level'    => $level,
            'points'   => $points,
            'next_level_points' => $levelEnd,
            'progress' => $levelEnd > $levelStart
                ? round((($points - $levelStart) / ($levelEnd - $levelStart)) * 100, 1)
                : 100,
        ];
    }
}
