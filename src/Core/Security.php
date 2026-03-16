<?php

declare(strict_types=1);

namespace AgencyOS\Core;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;
use Psr\Http\Message\ServerRequestInterface as Request;

class Security
{
    public static function hashPassword(string $plain): string
    {
        return password_hash($plain, PASSWORD_BCRYPT, ['cost' => 10]);
    }

    public static function verifyPassword(string $plain, string $hash): bool
    {
        return password_verify($plain, $hash);
    }

    public static function createAccessToken(string $userId, ?int $expireDays = null): string
    {
        $days = $expireDays ?? Config::jwtExpDays();
        $payload = [
            'sub' => $userId,
            'iat' => time(),
            'exp' => time() + ($days * 86400),
        ];
        return JWT::encode($payload, Config::jwtSecret(), Config::jwtAlgo());
    }

    public static function decodeToken(string $token): ?object
    {
        try {
            return JWT::decode($token, new Key(Config::jwtSecret(), Config::jwtAlgo()));
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Extract Bearer token from Authorization header.
     */
    public static function getBearerToken(Request $request): ?string
    {
        $header = $request->getHeaderLine('Authorization');
        if (preg_match('/Bearer\s+(.+)/i', $header, $matches)) {
            return trim($matches[1]);
        }
        return null;
    }

    /**
     * Authenticate request and return current user array.
     * Throws \RuntimeException on failure (caught by middleware).
     */
    public static function getCurrentUser(Request $request): array
    {
        $token = self::getBearerToken($request);
        if (!$token) {
            throw new \RuntimeException('Not authenticated', 401);
        }
        $payload = self::decodeToken($token);
        if (!$payload || empty($payload->sub)) {
            throw new \RuntimeException('Invalid token', 401);
        }
        $user = Database::fetchOne('SELECT * FROM `users` WHERE `id` = ?', [$payload->sub]);
        if (!$user) {
            throw new \RuntimeException('User not found', 401);
        }
        if (!$user['is_active']) {
            throw new \RuntimeException('Account deactivated', 403);
        }
        return $user;
    }

    public static function requireRole(array $user, array|string $roles): void
    {
        $roles = (array) $roles;
        if (!in_array($user['role'], $roles)) {
            throw new \RuntimeException('Forbidden', 403);
        }
    }
}
