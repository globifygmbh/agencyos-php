<?php

declare(strict_types=1);

namespace AgencyOS\Core;

class Config
{
    private static array $settings = [];

    public static function load(): void
    {
        $envFile = dirname(__DIR__, 2) . '/.env';
        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (str_starts_with(trim($line), '#')) continue;
                if (!str_contains($line, '=')) continue;
                [$key, $value] = explode('=', $line, 2);
                $key   = trim($key);
                $value = trim($value);
                // Strip surrounding quotes
                if (preg_match('/^"(.*)"$/', $value, $m) || preg_match("/^'(.*)'$/", $value, $m)) {
                    $value = $m[1];
                }
                $_ENV[$key]    = $value;
                $_SERVER[$key] = $value;
                putenv("$key=$value");
            }
        }
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $value = getenv($key);
        if ($value === false) {
            $value = $_ENV[$key] ?? $default;
        }
        return $value !== false ? $value : $default;
    }

    // -------------------------------------------------------
    // Convenience accessors
    // -------------------------------------------------------
    public static function dbHost(): string      { return self::get('DB_HOST', 'localhost'); }
    public static function dbPort(): int         { return (int) self::get('DB_PORT', 3306); }
    public static function dbName(): string      { return self::get('DB_NAME', 'agencyos'); }
    public static function dbUser(): string      { return self::get('DB_USER', 'root'); }
    public static function dbPass(): string      { return self::get('DB_PASS', ''); }

    public static function jwtSecret(): string  { return self::get('JWT_SECRET', 'changeme-secret'); }
    public static function jwtAlgo(): string    { return self::get('JWT_ALGORITHM', 'HS256'); }
    public static function jwtExpDays(): int    { return (int) self::get('ACCESS_TOKEN_EXPIRE_DAYS', 30); }

    public static function appUrl(): string     { return rtrim((string)self::get('APP_URL', 'http://localhost'), '/'); }
    public static function frontendUrl(): string{ return rtrim((string)self::get('FRONTEND_URL', 'http://localhost:3000'), '/'); }

    public static function resendKey(): string  { return self::get('RESEND_API_KEY', ''); }
    public static function mailFrom(): string   { return self::get('MAIL_FROM', 'noreply@agencyos.local'); }
    public static function mailFromName(): string { return self::get('MAIL_FROM_NAME', 'AgencyOS'); }

    public static function openaiKey(): string  { return self::get('OPENAI_API_KEY', ''); }
    public static function anthropicKey(): string { return self::get('ANTHROPIC_API_KEY', ''); }

    public static function uploadDir(): string  {
        return dirname(__DIR__, 2) . '/' . trim((string)self::get('UPLOAD_DIR', 'uploads'), '/');
    }
    public static function maxFileSize(): int   { return (int) self::get('MAX_FILE_SIZE', 5242880); }

    public static function corsOrigins(): array {
        $origins = self::get('CORS_ORIGINS', 'http://localhost:3000');
        return array_map('trim', explode(',', (string)$origins));
    }
}
