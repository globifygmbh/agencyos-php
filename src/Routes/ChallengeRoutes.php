<?php

declare(strict_types=1);

namespace AgencyOS\Routes;

use AgencyOS\Core\{Database, Security, Helpers, Gamification};
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;

class ChallengeRoutes
{
    public static function register(App $app): void
    {
        // POST /api/challenges/rewards  (CHEF only)
        $app->post('/api/challenges/rewards', function (Request $request, Response $response) {
            $user = Security::getCurrentUser($request);
            Security::requireRole($user, 'CHEF');
            $body = (array) $request->getParsedBody();

            if (empty($body['title']) || !isset($body['points_required'])) {
                return self::error($response, 'Titel und Punkte erforderlich', 400);
            }

            $id = Helpers::uuid();
            Database::insert('rewards', [
                'id'              => $id,
                'title'           => $body['title'],
                'description'     => $body['description'] ?? null,
                'points_required' => (int) $body['points_required'],
                'icon'            => $body['icon'] ?? '🎁',
                'is_active'       => 1,
                'created_by'      => $user['id'],
                'created_at'      => Helpers::now(),
                'updated_at'      => Helpers::now(),
            ]);
            return self::json($response, Database::fetchOne('SELECT * FROM rewards WHERE id=?', [$id]), 201);
        });

        // GET /api/challenges/rewards
        $app->get('/api/challenges/rewards', function (Request $request, Response $response) {
            Security::getCurrentUser($request);
            return self::json($response, Database::fetchAll('SELECT * FROM rewards WHERE is_active=1 ORDER BY points_required ASC'));
        });

        // DELETE /api/challenges/rewards/{reward_id}
        $app->delete('/api/challenges/rewards/{reward_id}', function (Request $request, Response $response, array $args) {
            $user = Security::getCurrentUser($request);
            Security::requireRole($user, 'CHEF');
            Database::delete('rewards', ['id' => $args['reward_id']]);
            return self::json($response, ['message' => 'Belohnung gelöscht']);
        });

        // GET /api/challenges/my-points
        $app->get('/api/challenges/my-points', function (Request $request, Response $response) {
            $user   = Security::getCurrentUser($request);
            $uid    = $user['id'];
            $points = Gamification::getUserPoints($uid);
            $level  = Gamification::getUserLevel($points);

            $recent = Database::fetchAll(
                'SELECT * FROM point_transactions WHERE user_id=? ORDER BY created_at DESC LIMIT 20',
                [$uid]
            );
            $redemptions = Database::fetchAll(
                'SELECT rr.*,r.title,r.icon FROM reward_redemptions rr JOIN rewards r ON r.id=rr.reward_id WHERE rr.user_id=? ORDER BY rr.created_at DESC',
                [$uid]
            );

            return self::json($response, array_merge($level, [
                'recent_transactions' => $recent,
                'redemptions'         => $redemptions,
            ]));
        });

        // POST /api/challenges/redeem/{reward_id}
        $app->post('/api/challenges/redeem/{reward_id}', function (Request $request, Response $response, array $args) {
            $user     = Security::getCurrentUser($request);
            $uid      = $user['id'];
            $rewardId = $args['reward_id'];

            $reward = Database::fetchOne('SELECT * FROM rewards WHERE id=? AND is_active=1', [$rewardId]);
            if (!$reward) return self::error($response, 'Belohnung nicht gefunden', 404);

            $points = Gamification::getUserPoints($uid);
            if ($points < $reward['points_required']) {
                return self::error($response, "Nicht genug Punkte. Verfügbar: $points, Benötigt: {$reward['points_required']}", 400);
            }

            // Deduct points
            Gamification::addPoints($uid, -$reward['points_required'], 'redemption', 'Eingelöst: ' . $reward['title'], $rewardId);

            // Record redemption
            Database::insert('reward_redemptions', [
                'id'        => Helpers::uuid(),
                'user_id'   => $uid,
                'reward_id' => $rewardId,
                'points'    => $reward['points_required'],
                'created_at'=> Helpers::now(),
            ]);

            // Notify admins
            $admins = Database::fetchAll('SELECT id FROM users WHERE role=? AND is_active=1', ['CHEF']);
            foreach ($admins as $admin) {
                Helpers::createNotification($admin['id'], 'reward_redeemed', 'Belohnung eingelöst', Helpers::userName($user) . ' hat "' . $reward['title'] . '" eingelöst', '/admin/challenges');
            }

            return self::json($response, ['message' => 'Belohnung erfolgreich eingelöst!', 'reward' => $reward]);
        });

        // POST /api/challenges/award-points  (CHEF only)
        $app->post('/api/challenges/award-points', function (Request $request, Response $response) {
            $user = Security::getCurrentUser($request);
            Security::requireRole($user, 'CHEF');
            $body = (array) $request->getParsedBody();

            if (empty($body['user_id']) || !isset($body['points'])) {
                return self::error($response, 'user_id und points erforderlich', 400);
            }

            $target = Database::fetchOne('SELECT * FROM users WHERE id=?', [$body['user_id']]);
            if (!$target) return self::error($response, 'User nicht gefunden', 404);

            Gamification::addPoints($body['user_id'], (int)$body['points'], 'manual', $body['reason'] ?? 'Manuell vergeben', null, $user['id']);

            $sign = ((int)$body['points']) >= 0 ? '+' : '';
            Helpers::createNotification($body['user_id'], 'points_awarded', 'Punkte erhalten!', "{$sign}{$body['points']} Punkte wurden dir vergeben" . (empty($body['reason']) ? '' : ': ' . $body['reason']), '/challenges');

            return self::json($response, ['message' => 'Punkte vergeben', 'points' => (int)$body['points']]);
        });

        // GET /api/challenges/leaderboard
        $app->get('/api/challenges/leaderboard', function (Request $request, Response $response) {
            Security::getCurrentUser($request);
            $users = Database::fetchAll('SELECT id,first_name,last_name,color,profile_image FROM users WHERE is_active=1');

            $board = [];
            foreach ($users as $u) {
                $pts = Gamification::getUserPoints($u['id']);
                $board[] = array_merge($u, ['points' => $pts, 'level' => Gamification::getUserLevel($pts)['level']]);
            }

            usort($board, fn($a, $b) => $b['points'] <=> $a['points']);
            foreach ($board as $i => &$entry) {
                $entry['rank'] = $i + 1;
            }

            return self::json($response, $board);
        });

        // GET /api/challenges/admin/overview
        $app->get('/api/challenges/admin/overview', function (Request $request, Response $response) {
            $user = Security::getCurrentUser($request);
            Security::requireRole($user, 'CHEF');

            $users = Database::fetchAll('SELECT id,first_name,last_name,color FROM users WHERE is_active=1');
            $result = [];
            foreach ($users as $u) {
                $pts   = Gamification::getUserPoints($u['id']);
                $txns  = Database::fetchAll('SELECT * FROM point_transactions WHERE user_id=? ORDER BY created_at DESC LIMIT 10', [$u['id']]);
                $redms = Database::fetchAll('SELECT rr.*,r.title,r.icon FROM reward_redemptions rr JOIN rewards r ON r.id=rr.reward_id WHERE rr.user_id=? ORDER BY rr.created_at DESC', [$u['id']]);
                $result[] = array_merge($u, ['points' => $pts, 'transactions' => $txns, 'redemptions' => $redms]);
            }
            return self::json($response, $result);
        });

        // POST /api/challenges/check-daily-login
        $app->post('/api/challenges/check-daily-login', function (Request $request, Response $response) {
            $user    = Security::getCurrentUser($request);
            $awarded = Gamification::checkDailyLogin($user['id']);
            return self::json($response, ['awarded' => $awarded, 'points' => $awarded ? Gamification::POINTS['daily_login'] : 0]);
        });

        // GET /api/challenges/my-redemptions
        $app->get('/api/challenges/my-redemptions', function (Request $request, Response $response) {
            $user = Security::getCurrentUser($request);
            $redemptions = Database::fetchAll(
                'SELECT rr.*,r.title,r.icon,r.description FROM reward_redemptions rr JOIN rewards r ON r.id=rr.reward_id WHERE rr.user_id=? ORDER BY rr.created_at DESC',
                [$user['id']]
            );
            return self::json($response, $redemptions);
        });

        // GET /api/challenges/admin/redemptions  (CHEF: all pending redemptions)
        $app->get('/api/challenges/admin/redemptions', function (Request $request, Response $response) {
            $user = Security::getCurrentUser($request);
            Security::requireRole($user, 'CHEF');
            $redemptions = Database::fetchAll(
                "SELECT rr.*,r.title,r.icon,r.points_required,u.first_name,u.last_name,u.color
                 FROM reward_redemptions rr
                 JOIN rewards r ON r.id=rr.reward_id
                 JOIN users u ON u.id=rr.user_id
                 ORDER BY rr.created_at DESC"
            );
            return self::json($response, $redemptions);
        });

        // PUT /api/challenges/redemptions/{redemption_id}  (CHEF: approve/reject)
        $app->put('/api/challenges/redemptions/{redemption_id}', function (Request $request, Response $response, array $args) {
            $user = Security::getCurrentUser($request);
            Security::requireRole($user, 'CHEF');
            $body   = (array) $request->getParsedBody();
            $status = $body['status'] ?? 'approved';
            $note   = $body['note'] ?? '';

            $redemption = Database::fetchOne('SELECT rr.*,r.title FROM reward_redemptions rr JOIN rewards r ON r.id=rr.reward_id WHERE rr.id=?', [$args['redemption_id']]);
            if (!$redemption) return self::error($response, 'Einlösung nicht gefunden', 404);

            Database::update('reward_redemptions', [
                'status'       => $status,
                'admin_note'   => $note,
                'processed_by' => $user['id'],
                'processed_at' => Helpers::now(),
            ], ['id' => $args['redemption_id']]);

            $icon = $status === 'approved' ? '✅' : '❌';
            $label = $status === 'approved' ? 'genehmigt' : 'abgelehnt';
            Helpers::createNotification($redemption['user_id'], 'redemption_' . $status, "$icon Einlösung $label", 'Deine Einlösung für "' . $redemption['title'] . '" wurde ' . $label, '/challenges');

            return self::json($response, ['message' => "Einlösung $label"]);
        });

        // GET /api/challenges/point-rules
        $app->get('/api/challenges/point-rules', function (Request $request, Response $response) {
            Security::getCurrentUser($request);
            $rules = [
                ['emoji' => '🌟', 'action' => 'Täglicher Login',             'points' => Gamification::POINTS['daily_login']],
                ['emoji' => '✅', 'action' => 'Aufgabe erledigt',              'points' => Gamification::POINTS['task_completed']],
                ['emoji' => '⏱️', 'action' => 'Wochenstunden erreicht',       'points' => Gamification::POINTS['weekly_hours']],
                ['emoji' => '🏁', 'action' => 'Meilenstein erreicht',          'points' => Gamification::POINTS['milestone']],
                ['emoji' => '🎂', 'action' => 'Geburtstag',                   'points' => Gamification::POINTS['birthday']],
                ['emoji' => '🎄', 'action' => 'Weihnachten',                  'points' => Gamification::POINTS['christmas']],
                ['emoji' => '🌴', 'action' => 'Urlaub genehmigt',             'points' => Gamification::POINTS['vacation_approved']],
                ['emoji' => '🏆', 'action' => 'Achievement freigeschaltet',    'points' => '(variiert)'],
            ];
            return self::json($response, $rules);
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
