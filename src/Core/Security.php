<?php

declare(strict_types=1);

namespace AgencyOS\Core;

use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * Eigenes Token-System – kein JWT, kein externe Library.
 * Token = 64 Hex-Zeichen (32 Byte Zufalls-Entropie), 30 Tage gültig.
 * Gespeichert in der `sessions`-Tabelle (MySQL).
 */
class Security
{
    const TOKEN_DAYS = 30;

    // -------------------------------------------------------
    // Passwort-Hashing (bcrypt, PHP-intern)
    // -------------------------------------------------------

    public static function hashPassword(string $plain): string
    {
        return password_hash($plain, PASSWORD_BCRYPT, ['cost' => 10]);
    }

    public static function verifyPassword(string $plain, string $hash): bool
    {
        return password_verify($plain, $hash);
    }

    // -------------------------------------------------------
    // Token erstellen und in DB speichern
    // -------------------------------------------------------

    public static function createAccessToken(string $userId, ?Request $request = null): string
    {
        // Altes Token für diesen User aufräumen (optional – max. 5 aktive Sessions)
        self::pruneExpired($userId);

        $token     = bin2hex(random_bytes(32)); // 64 Zeichen
        $expiresAt = date('Y-m-d H:i:s', time() + self::TOKEN_DAYS * 86400);

        Database::insert('sessions', [
            'token'      => $token,
            'user_id'    => $userId,
            'expires_at' => $expiresAt,
            'ip_address' => $request ? self::clientIp($request) : null,
            'user_agent' => $request ? mb_substr($request->getHeaderLine('User-Agent'), 0, 500) : null,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        return $token;
    }

    // -------------------------------------------------------
    // Token auflösen → User laden
    // -------------------------------------------------------

    public static function getUserByToken(string $token): ?array
    {
        if (strlen($token) !== 64 || !ctype_xdigit($token)) return null;

        $session = Database::fetchOne(
            'SELECT * FROM sessions WHERE token = ? AND expires_at > NOW()',
            [$token]
        );
        if (!$session) return null;

        return Database::fetchOne('SELECT * FROM users WHERE id = ?', [$session['user_id']]);
    }

    // -------------------------------------------------------
    // Token aus Request lesen (Authorization: Bearer <token>)
    // -------------------------------------------------------

    public static function getBearerToken(Request $request): ?string
    {
        $header = $request->getHeaderLine('Authorization');
        if (preg_match('/Bearer\s+([0-9a-f]{64})/i', $header, $m)) {
            return $m[1];
        }
        // Fallback: Cookie (für same-origin requests)
        $cookies = $request->getCookieParams();
        return $cookies['agencyos_token'] ?? null;
    }

    // -------------------------------------------------------
    // Haupt-Auth-Middleware: gibt User zurück oder wirft Exception
    // -------------------------------------------------------

    public static function getCurrentUser(Request $request): array
    {
        $token = self::getBearerToken($request);
        if (!$token) {
            throw new \RuntimeException('Nicht authentifiziert', 401);
        }

        $user = self::getUserByToken($token);
        if (!$user) {
            throw new \RuntimeException('Token ungültig oder abgelaufen', 401);
        }
        if (!$user['is_active']) {
            throw new \RuntimeException('Account ist deaktiviert', 403);
        }
        return $user;
    }

    // -------------------------------------------------------
    // Rollen-Prüfung
    // -------------------------------------------------------

    public static function requireRole(array $user, array|string $roles): void
    {
        if (!in_array($user['role'], (array) $roles)) {
            throw new \RuntimeException('Keine Berechtigung', 403);
        }
    }

    // -------------------------------------------------------
    // Token widerrufen (Logout)
    // -------------------------------------------------------

    public static function revokeToken(string $token): void
    {
        Database::execute('DELETE FROM sessions WHERE token = ?', [$token]);
    }

    public static function revokeAllUserTokens(string $userId): void
    {
        Database::execute('DELETE FROM sessions WHERE user_id = ?', [$userId]);
    }

    // -------------------------------------------------------
    // Housekeeping
    // -------------------------------------------------------

    /** Abgelaufene Sessions löschen (läuft bei jedem neuen Token automatisch mit) */
    private static function pruneExpired(string $userId): void
    {
        // Globale abgelaufene Sessions aufräumen
        Database::execute('DELETE FROM sessions WHERE expires_at < NOW()');

        // Pro User max. 10 aktive Sessions – älteste löschen
        $count = (int) (Database::fetchOne(
            'SELECT COUNT(*) as c FROM sessions WHERE user_id = ?',
            [$userId]
        )['c'] ?? 0);

        if ($count >= 10) {
            Database::execute(
                'DELETE FROM sessions WHERE user_id = ? ORDER BY created_at ASC LIMIT ?',
                [$userId, $count - 9]
            );
        }
    }

    private static function clientIp(Request $request): string
    {
        $params = $request->getServerParams();
        foreach (['HTTP_X_FORWARDED_FOR', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'] as $key) {
            if (!empty($params[$key])) {
                return explode(',', (string) $params[$key])[0];
            }
        }
        return 'unknown';
    }
}
