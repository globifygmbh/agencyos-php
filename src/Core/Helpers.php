<?php

declare(strict_types=1);

namespace AgencyOS\Core;

use Ramsey\Uuid\Uuid;

class Helpers
{
    public static function uuid(): string
    {
        return Uuid::uuid4()->toString();
    }

    public static function nowIso(): string
    {
        return (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->format('Y-m-d\TH:i:s\Z');
    }

    public static function now(): string
    {
        return (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->format('Y-m-d H:i:s');
    }

    /**
     * JSON-decode a DB column value (JSON stored as string).
     */
    public static function jsonDecode(?string $value, mixed $default = null): mixed
    {
        if ($value === null || $value === '') return $default;
        $decoded = json_decode($value, true);
        return ($decoded === null && json_last_error() !== JSON_ERROR_NONE) ? $default : $decoded;
    }

    /**
     * Create an in-app notification.
     */
    public static function createNotification(
        string $userId,
        string $type,
        string $title,
        string $message = '',
        ?string $link = null,
        array $data = []
    ): void {
        Database::insert('notifications', [
            'id'         => self::uuid(),
            'user_id'    => $userId,
            'type'       => $type,
            'title'      => $title,
            'message'    => $message,
            'link'       => $link,
            'is_read'    => 0,
            'data'       => json_encode($data),
            'created_at' => self::now(),
        ]);
    }

    /**
     * Create an audit log entry.
     */
    public static function createAuditLog(
        ?string $userId,
        string $userName,
        string $action,
        string $entityType = '',
        ?string $entityId = null,
        array $details = []
    ): void {
        Database::insert('audit_logs', [
            'id'          => self::uuid(),
            'user_id'     => $userId,
            'user_name'   => $userName,
            'action'      => $action,
            'entity_type' => $entityType,
            'entity_id'   => $entityId,
            'details'     => json_encode($details),
            'created_at'  => self::now(),
        ]);
    }

    /**
     * Retrieve system settings row.
     */
    public static function getSystemSettings(): array
    {
        $row = Database::fetchOne("SELECT * FROM `system_settings` WHERE `id` = 'default'");
        return $row ?? [];
    }

    /**
     * Get the application base URL (DB > ENV > fallback).
     */
    public static function getAppUrl(): string
    {
        $settings = self::getSystemSettings();
        if (!empty($settings['app_url'])) {
            return rtrim($settings['app_url'], '/');
        }
        return Config::appUrl();
    }

    /**
     * Check if user prefers to receive email for a given notification type.
     */
    public static function shouldSendEmail(array $user, string $notificationType): bool
    {
        $prefs = self::jsonDecode($user['notification_prefs'] ?? null, []);
        if (empty($prefs)) return true;
        return (bool) ($prefs[$notificationType] ?? true);
    }

    /**
     * Whether a time entry is locked (>60 days old or manually locked).
     */
    public static function isTimeEntryLocked(array $entry, array $currentUser): bool
    {
        if ($currentUser['role'] === 'CHEF') return false;
        if ($entry['is_locked']) return true;
        try {
            $created = new \DateTimeImmutable($entry['start_time']);
            $now     = new \DateTimeImmutable('now');
            return $created->diff($now)->days > 60;
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Parse an uploaded file and return [tmp_path, original_name, mime_type, size].
     */
    public static function getUploadedFile(array $files, string $field): ?array
    {
        if (empty($files[$field])) return null;
        $f = $files[$field];
        if (is_array($f)) {
            // PSR-7 UploadedFile
            return null; // handled in route directly
        }
        return null;
    }

    /**
     * Sanitize file name.
     */
    public static function sanitizeFileName(string $name): string
    {
        $name = preg_replace('/[^\w\-._]/', '_', $name);
        return ltrim($name, '.');
    }

    /**
     * Check business day (Mon-Fri).
     */
    public static function isBusinessDay(\DateTimeInterface $date): bool
    {
        $dow = (int) $date->format('N'); // 1=Mon, 7=Sun
        return $dow <= 5;
    }

    /**
     * Count business days between two dates (inclusive).
     */
    public static function countBusinessDays(\DateTimeInterface $start, \DateTimeInterface $end): int
    {
        $count   = 0;
        $current = \DateTimeImmutable::createFromInterface($start);
        $end     = \DateTimeImmutable::createFromInterface($end);
        while ($current <= $end) {
            if (self::isBusinessDay($current)) $count++;
            $current = $current->modify('+1 day');
        }
        return $count;
    }

    /**
     * User display name helper.
     */
    public static function userName(array $user): string
    {
        $full = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
        return $full ?: ($user['username'] ?? '');
    }
}
