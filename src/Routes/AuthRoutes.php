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

            Database::update('users', ['last_login' => Helpers::now()], ['id' => $user['id']]);
            Gamification::checkDailyLogin($user['id']);
            Helpers::createAuditLog($user['id'], Helpers::userName($user), 'login', 'user', $user['id']);

            // Eigenes Token erzeugen (kein JWT)
            $token = Security::createAccessToken($user['id'], $request);

            return self::json($response, [
                'access_token' => $token,
                'token_type'   => 'bearer',
                'expires_in'   => Security::TOKEN_DAYS * 86400,
                'user'         => self::safeUser($user),
            ]);
        });

        // POST /api/auth/logout
        $app->post('/api/auth/logout', function (Request $request, Response $response) {
            $token = Security::getBearerToken($request);
            if ($token) Security::revokeToken($token);
            return self::json($response, ['message' => 'Erfolgreich abgemeldet']);
        });

        // POST /api/auth/logout-all  (alle Sessions widerrufen)
        $app->post('/api/auth/logout-all', function (Request $request, Response $response) {
            $user = Security::getCurrentUser($request);
            Security::revokeAllUserTokens($user['id']);
            return self::json($response, ['message' => 'Alle Sessions beendet']);
        });

        // GET /api/auth/me
        $app->get('/api/auth/me', function (Request $request, Response $response) {
            $user = Security::getCurrentUser($request);
            return self::json($response, self::safeUser($user));
        });

        // GET /api/auth/sessions  (eigene aktive Sessions anzeigen)
        $app->get('/api/auth/sessions', function (Request $request, Response $response) {
            $user     = Security::getCurrentUser($request);
            $sessions = Database::fetchAll(
                'SELECT token, ip_address, user_agent, created_at, expires_at FROM sessions WHERE user_id = ? AND expires_at > NOW() ORDER BY created_at DESC',
                [$user['id']]
            );
            // Token nur die ersten 8 Zeichen zeigen
            foreach ($sessions as &$s) {
                $s['token'] = substr($s['token'], 0, 8) . '…';
            }
            return self::json($response, $sessions);
        });

        // PUT /api/auth/password
        $app->put('/api/auth/password', function (Request $request, Response $response) {
            $user = Security::getCurrentUser($request);
            $body = (array) $request->getParsedBody();

            $oldPw = $body['old_password'] ?? '';
            $newPw = $body['new_password'] ?? '';

            if (!$oldPw || !$newPw) {
                return self::error($response, 'Altes und neues Passwort erforderlich', 400);
            }
            if (!Security::verifyPassword($oldPw, $user['password_hash'])) {
                return self::error($response, 'Altes Passwort ist falsch', 400);
            }
            if (strlen($newPw) < 6) {
                return self::error($response, 'Mindestens 6 Zeichen', 400);
            }

            Database::update('users', [
                'password_hash' => Security::hashPassword($newPw),
                'updated_at'    => Helpers::now(),
            ], ['id' => $user['id']]);

            // Alle anderen Sessions widerrufen (außer aktuelle)
            $currentToken = Security::getBearerToken($request);
            Database::execute(
                'DELETE FROM sessions WHERE user_id = ? AND token != ?',
                [$user['id'], $currentToken]
            );

            Helpers::createAuditLog($user['id'], Helpers::userName($user), 'password_change', 'user', $user['id']);
            return self::json($response, ['message' => 'Passwort erfolgreich geändert']);
        });

        // POST /api/auth/setup  (Account-Setup via Einladungs-Token)
        $app->post('/api/auth/setup', function (Request $request, Response $response) {
            $body  = (array) $request->getParsedBody();
            $setupToken = $body['token'] ?? '';
            $pw    = $body['password'] ?? '';

            if (!$setupToken || !$pw) {
                return self::error($response, 'Token und Passwort erforderlich', 400);
            }
            if (strlen($pw) < 6) {
                return self::error($response, 'Mindestens 6 Zeichen', 400);
            }

            $user = Database::fetchOne(
                'SELECT * FROM `users` WHERE `invitation_token` = ? LIMIT 1',
                [$setupToken]
            );
            if (!$user) {
                return self::error($response, 'Ungültiger oder abgelaufener Einladungs-Link', 400);
            }
            if ($user['invitation_expires'] && strtotime($user['invitation_expires']) < time()) {
                return self::error($response, 'Einladungs-Link abgelaufen', 400);
            }

            $firstName = $body['first_name'] ?? $user['first_name'];
            Database::update('users', [
                'password_hash'      => Security::hashPassword($pw),
                'first_name'         => $firstName,
                'invitation_token'   => null,
                'invitation_expires' => null,
                'setup_completed'    => 1,
                'is_active'          => 1,
                'updated_at'         => Helpers::now(),
            ], ['id' => $user['id']]);

            $accessToken = Security::createAccessToken($user['id'], $request);
            $freshUser   = Database::fetchOne('SELECT * FROM users WHERE id = ?', [$user['id']]);

            return self::json($response, [
                'access_token' => $accessToken,
                'token_type'   => 'bearer',
                'expires_in'   => Security::TOKEN_DAYS * 86400,
                'user'         => self::safeUser($freshUser),
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
