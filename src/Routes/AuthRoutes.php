<?php

declare(strict_types=1);

namespace AgencyOS\Routes;

use AgencyOS\Core\{Database, Security, Gamification, Helpers, EmailService};
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;

class AuthRoutes
{
    public static function register(App $app): void
    {
        // POST /api/auth/login
        $app->post('/api/auth/login', function (Request $request, Response $response) {
            $body     = (array) $request->getParsedBody();
            $login    = trim($body['username'] ?? $body['email'] ?? '');
            $password = $body['password'] ?? '';

            if (!$login || !$password) {
                return self::error($response, 'Benutzername/Email und Passwort erforderlich', 400);
            }

            // Find user by username or email
            $user = Database::fetchOne(
                'SELECT * FROM `users` WHERE (`username` = ? OR `email` = ?) LIMIT 1',
                [$login, $login]
            );

            if (!$user || !Security::verifyPassword($password, $user['password_hash'])) {
                return self::error($response, 'Ungültige Anmeldedaten', 401);
            }

            if (!$user['is_active']) {
                return self::error($response, 'Account ist deaktiviert', 403);
            }

            // Update last login
            Database::update('users', ['last_login' => Helpers::now()], ['id' => $user['id']]);

            // Daily login gamification
            Gamification::checkDailyLogin($user['id']);

            // Audit log
            Helpers::createAuditLog($user['id'], Helpers::userName($user), 'login', 'user', $user['id']);

            $token = Security::createAccessToken($user['id']);

            return self::json($response, [
                'access_token' => $token,
                'token_type'   => 'bearer',
                'user'         => self::safeUser($user),
            ]);
        });

        // GET /api/auth/me
        $app->get('/api/auth/me', function (Request $request, Response $response) {
            $user = Security::getCurrentUser($request);
            return self::json($response, self::safeUser($user));
        });

        // PUT /api/auth/password
        $app->put('/api/auth/password', function (Request $request, Response $response) {
            $user = Security::getCurrentUser($request);
            $body = (array) $request->getParsedBody();

            $oldPw  = $body['old_password'] ?? '';
            $newPw  = $body['new_password'] ?? '';

            if (!$oldPw || !$newPw) {
                return self::error($response, 'Altes und neues Passwort erforderlich', 400);
            }

            if (!Security::verifyPassword($oldPw, $user['password_hash'])) {
                return self::error($response, 'Altes Passwort ist falsch', 400);
            }

            if (strlen($newPw) < 6) {
                return self::error($response, 'Neues Passwort muss mindestens 6 Zeichen haben', 400);
            }

            Database::update('users', [
                'password_hash' => Security::hashPassword($newPw),
                'updated_at'    => Helpers::now(),
            ], ['id' => $user['id']]);

            Helpers::createAuditLog($user['id'], Helpers::userName($user), 'password_change', 'user', $user['id']);

            return self::json($response, ['message' => 'Passwort erfolgreich geändert']);
        });

        // POST /api/auth/setup  - Account setup via invitation token
        $app->post('/api/auth/setup', function (Request $request, Response $response) {
            $body  = (array) $request->getParsedBody();
            $token = $body['token'] ?? '';
            $pw    = $body['password'] ?? '';
            $name  = $body['first_name'] ?? '';

            if (!$token || !$pw) {
                return self::error($response, 'Token und Passwort erforderlich', 400);
            }

            $user = Database::fetchOne(
                'SELECT * FROM `users` WHERE `invitation_token` = ? LIMIT 1',
                [$token]
            );

            if (!$user) {
                return self::error($response, 'Ungültiger oder abgelaufener Token', 400);
            }

            if ($user['invitation_expires'] && strtotime($user['invitation_expires']) < time()) {
                return self::error($response, 'Token abgelaufen', 400);
            }

            $updates = [
                'password_hash'      => Security::hashPassword($pw),
                'invitation_token'   => null,
                'invitation_expires' => null,
                'setup_completed'    => 1,
                'is_active'          => 1,
                'updated_at'         => Helpers::now(),
            ];
            if ($name) $updates['first_name'] = $name;

            Database::update('users', $updates, ['id' => $user['id']]);

            $accessToken = Security::createAccessToken($user['id']);
            return self::json($response, [
                'access_token' => $accessToken,
                'token_type'   => 'bearer',
                'user'         => self::safeUser(array_merge($user, $updates)),
            ]);
        });
    }

    private static function safeUser(array $user): array
    {
        unset($user['password_hash'], $user['invitation_token']);
        return $user;
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
