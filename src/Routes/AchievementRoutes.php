<?php

declare(strict_types=1);

namespace AgencyOS\Routes;

use AgencyOS\Core\{Database, Security, Helpers, Gamification};
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;

class AchievementRoutes
{
    public static function register(App $app): void
    {
        // GET /api/achievements
        $app->get('/api/achievements', function (Request $request, Response $response) {
            $user         = Security::getCurrentUser($request);
            $uid          = $user['id'];
            $achievements = Database::fetchAll('SELECT * FROM achievements ORDER BY category,name');
            $earned       = array_column(Database::fetchAll('SELECT achievement_id,earned_at FROM user_achievements WHERE user_id=?', [$uid]), 'earned_at', 'achievement_id');

            $result = [];
            foreach ($achievements as $a) {
                if ($a['is_hidden'] && !isset($earned[$a['id']])) continue;
                $progress = Gamification::getAchievementProgress($uid, $a['id']);
                $result[] = array_merge($a, [
                    'is_earned'  => isset($earned[$a['id']]),
                    'earned_at'  => $earned[$a['id']] ?? null,
                    'progress'   => $progress,
                    'percentage' => $a['requirement'] > 0 ? min(100, round(($progress / $a['requirement']) * 100)) : 100,
                ]);
            }
            return self::json($response, $result);
        });

        // GET /api/achievements/my
        $app->get('/api/achievements/my', function (Request $request, Response $response) {
            $user = Security::getCurrentUser($request);
            $list = Database::fetchAll(
                'SELECT a.*,ua.earned_at FROM user_achievements ua JOIN achievements a ON a.id=ua.achievement_id WHERE ua.user_id=? ORDER BY ua.earned_at DESC',
                [$user['id']]
            );
            return self::json($response, $list);
        });

        // GET /api/achievements/user/{user_id}
        $app->get('/api/achievements/user/{user_id}', function (Request $request, Response $response, array $args) {
            Security::getCurrentUser($request);
            $list = Database::fetchAll(
                'SELECT a.*,ua.earned_at FROM user_achievements ua JOIN achievements a ON a.id=ua.achievement_id WHERE ua.user_id=? AND a.is_hidden=0 ORDER BY ua.earned_at DESC',
                [$args['user_id']]
            );
            return self::json($response, $list);
        });

        // POST /api/achievements/check
        $app->post('/api/achievements/check', function (Request $request, Response $response) {
            $user = Security::getCurrentUser($request);
            $all  = Database::fetchAll('SELECT id FROM achievements');
            $new  = [];
            foreach ($all as $a) {
                if (Gamification::checkAndAwardAchievement($user['id'], $a['id'])) {
                    $new[] = $a['id'];
                }
            }
            return self::json($response, ['newly_earned' => $new]);
        });

        // GET /api/achievements/stats
        $app->get('/api/achievements/stats', function (Request $request, Response $response) {
            $user   = Security::getCurrentUser($request);
            $uid    = $user['id'];
            $total  = (int) (Database::fetchOne('SELECT COUNT(*) as c FROM achievements WHERE is_hidden=0')['c'] ?? 0);
            $earned = (int) (Database::fetchOne('SELECT COUNT(*) as c FROM user_achievements WHERE user_id=?', [$uid])['c'] ?? 0);
            $points = Gamification::getUserPoints($uid);
            $level  = Gamification::getUserLevel($points);

            return self::json($response, array_merge($level, [
                'total_achievements'  => $total,
                'earned_achievements' => $earned,
                'completion_rate'     => $total > 0 ? round(($earned / $total) * 100, 1) : 0,
            ]));
        });
    }

    private static function json(Response $response, mixed $data, int $status = 200): Response
    {
        $response->getBody()->write(json_encode($data, JSON_UNESCAPED_UNICODE));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($status);
    }
}
