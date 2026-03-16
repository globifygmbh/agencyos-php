<?php

declare(strict_types=1);

namespace AgencyOS\Core;

class EmailService
{
    private static function send(string $to, string $toName, string $subject, string $html): array
    {
        $apiKey = Config::resendKey();
        if (!$apiKey) {
            error_log('[EmailService] No RESEND_API_KEY configured – email not sent.');
            return ['success' => false, 'error' => 'No API key'];
        }

        $from    = Config::mailFrom();
        $fromName = Config::mailFromName();
        $payload = json_encode([
            'from'    => "$fromName <$from>",
            'to'      => ["$toName <$to>"],
            'subject' => $subject,
            'html'    => $html,
        ]);

        $ch = curl_init('https://api.resend.com/emails');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                "Authorization: Bearer $apiKey",
            ],
            CURLOPT_TIMEOUT        => 10,
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $result = json_decode((string)$response, true);
        return ['success' => $httpCode === 200, 'response' => $result];
    }

    private static function baseTemplate(string $content, string $title = 'AgencyOS'): string
    {
        $settings = Helpers::getSystemSettings();
        $company  = $settings['company_name'] ?? 'AgencyOS';
        $color    = $settings['primary_color'] ?? '#3B82F6';
        $logo     = $settings['company_logo'] ?? '';
        $logoHtml = $logo ? "<img src=\"$logo\" alt=\"$company\" style=\"max-height:50px;\" />" : "<strong style='font-size:22px;color:#fff;'>$company</strong>";
        $appUrl   = Helpers::getAppUrl();

        return "<!DOCTYPE html>
<html lang='de'>
<head><meta charset='UTF-8'><meta name='viewport' content='width=device-width,initial-scale=1'><title>$title</title></head>
<body style='margin:0;padding:0;background:#f3f4f6;font-family:Inter,Arial,sans-serif;'>
<table width='100%' cellpadding='0' cellspacing='0' style='background:#f3f4f6;padding:24px 0;'>
  <tr><td align='center'>
    <table width='600' cellpadding='0' cellspacing='0' style='background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,0.08);'>
      <tr><td style='background:$color;padding:24px 32px;'>$logoHtml</td></tr>
      <tr><td style='padding:32px;'>$content</td></tr>
      <tr><td style='padding:16px 32px;background:#f9fafb;border-top:1px solid #e5e7eb;font-size:12px;color:#9ca3af;text-align:center;'>
        &copy; " . date('Y') . " $company &bull; <a href='$appUrl' style='color:#9ca3af;'>$appUrl</a>
      </td></tr>
    </table>
  </td></tr>
</table>
</body></html>";
    }

    // -------------------------------------------------------
    // WELCOME / ONBOARDING
    // -------------------------------------------------------
    public static function sendWelcomeEmail(string $email, string $name, string $username, string $password): array
    {
        $appUrl = Helpers::getAppUrl();
        $html   = self::baseTemplate("
<h2 style='color:#111827;margin-top:0;'>Willkommen bei AgencyOS, $name! 🎉</h2>
<p style='color:#374151;'>Dein Account wurde erstellt. Hier sind deine Zugangsdaten:</p>
<table style='background:#f9fafb;border-radius:8px;padding:16px;width:100%;border-collapse:collapse;'>
  <tr><td style='padding:6px 12px;color:#6b7280;font-size:14px;'>Benutzername</td><td style='padding:6px 12px;font-weight:600;'>$username</td></tr>
  <tr><td style='padding:6px 12px;color:#6b7280;font-size:14px;'>Passwort</td><td style='padding:6px 12px;font-weight:600;font-family:monospace;'>$password</td></tr>
</table>
<p style='margin-top:24px;'><a href='$appUrl' style='background:#3B82F6;color:#fff;padding:12px 24px;border-radius:8px;text-decoration:none;font-weight:600;'>Jetzt einloggen →</a></p>
<p style='color:#9ca3af;font-size:13px;margin-top:24px;'>Bitte ändere dein Passwort nach dem ersten Login.</p>", 'Willkommen');
        return self::send($email, $name, 'Willkommen bei AgencyOS 🚀', $html);
    }

    public static function sendSetupInvitationEmail(string $email, string $name, string $setupLink): array
    {
        $html = self::baseTemplate("
<h2 style='color:#111827;margin-top:0;'>Du wurdest eingeladen! 🎉</h2>
<p style='color:#374151;'>Du wurdest zu AgencyOS eingeladen. Klicke auf den Button um deinen Account einzurichten:</p>
<p style='margin-top:24px;'><a href='$setupLink' style='background:#10B981;color:#fff;padding:12px 24px;border-radius:8px;text-decoration:none;font-weight:600;'>Account einrichten →</a></p>
<p style='color:#9ca3af;font-size:13px;'>Dieser Link ist 7 Tage gültig.</p>", 'Einladung');
        return self::send($email, $name, 'Du wurdest zu AgencyOS eingeladen', $html);
    }

    public static function sendPasswordResetEmail(string $email, string $name, string $newPassword): array
    {
        $appUrl = Helpers::getAppUrl();
        $html   = self::baseTemplate("
<h2 style='color:#111827;margin-top:0;'>Passwort zurückgesetzt 🔐</h2>
<p style='color:#374151;'>Dein Passwort wurde zurückgesetzt. Dein neues temporäres Passwort:</p>
<div style='background:#f9fafb;border-radius:8px;padding:16px;font-family:monospace;font-size:18px;font-weight:600;letter-spacing:2px;text-align:center;'>$newPassword</div>
<p style='margin-top:24px;'><a href='$appUrl' style='background:#3B82F6;color:#fff;padding:12px 24px;border-radius:8px;text-decoration:none;font-weight:600;'>Jetzt einloggen →</a></p>
<p style='color:#9ca3af;font-size:13px;'>Bitte ändere dein Passwort nach dem Login.</p>", 'Passwort zurückgesetzt');
        return self::send($email, $name, 'Dein Passwort wurde zurückgesetzt', $html);
    }

    // -------------------------------------------------------
    // TASKS
    // -------------------------------------------------------
    public static function sendTaskAssignmentEmail(string $email, string $name, array $task, string $assignerName): array
    {
        $appUrl  = Helpers::getAppUrl();
        $title   = htmlspecialchars($task['title'] ?? 'Aufgabe');
        $deadline = isset($task['deadline']) ? date('d.m.Y', strtotime($task['deadline'])) : 'Kein Datum';
        $priority = $task['priority'] ?? 'MEDIUM';
        $html    = self::baseTemplate("
<h2 style='color:#111827;margin-top:0;'>Neue Aufgabe zugewiesen 📋</h2>
<p style='color:#374151;'>$assignerName hat dir eine neue Aufgabe zugewiesen:</p>
<div style='background:#f9fafb;border-radius:8px;padding:16px;margin:16px 0;'>
  <h3 style='margin:0 0 8px;color:#111827;'>$title</h3>
  <p style='color:#6b7280;font-size:14px;margin:4px 0;'>📅 Deadline: $deadline</p>
  <p style='color:#6b7280;font-size:14px;margin:4px 0;'>🎯 Priorität: $priority</p>
</div>
<p><a href='$appUrl/tasks' style='background:#3B82F6;color:#fff;padding:10px 20px;border-radius:8px;text-decoration:none;font-weight:600;'>Aufgabe ansehen →</a></p>", 'Neue Aufgabe');
        return self::send($email, $name, "Neue Aufgabe: $title", $html);
    }

    public static function sendTaskDeadlineReminderEmail(string $email, string $name, array $task): array
    {
        $appUrl   = Helpers::getAppUrl();
        $title    = htmlspecialchars($task['title'] ?? 'Aufgabe');
        $deadline = isset($task['deadline']) ? date('d.m.Y', strtotime($task['deadline'])) : '';
        $html     = self::baseTemplate("
<h2 style='color:#111827;margin-top:0;'>⏰ Deadline Erinnerung</h2>
<p style='color:#374151;'>Die folgende Aufgabe hat bald ihre Deadline:</p>
<div style='background:#fef3c7;border-left:4px solid #f59e0b;border-radius:8px;padding:16px;margin:16px 0;'>
  <h3 style='margin:0 0 4px;color:#92400e;'>$title</h3>
  <p style='color:#92400e;font-size:14px;margin:0;'>📅 Deadline: $deadline</p>
</div>
<p><a href='$appUrl/tasks' style='background:#F59E0B;color:#fff;padding:10px 20px;border-radius:8px;text-decoration:none;font-weight:600;'>Aufgabe öffnen →</a></p>", 'Deadline Erinnerung');
        return self::send($email, $name, "⏰ Deadline morgen: $title", $html);
    }

    public static function sendTaskCommentEmail(string $email, string $name, array $task, string $commenterName, string $comment): array
    {
        $appUrl  = Helpers::getAppUrl();
        $title   = htmlspecialchars($task['title'] ?? 'Aufgabe');
        $comment = htmlspecialchars($comment);
        $html    = self::baseTemplate("
<h2 style='color:#111827;margin-top:0;'>Neuer Kommentar 💬</h2>
<p style='color:#374151;'>$commenterName hat einen Kommentar zu <strong>$title</strong> hinterlassen:</p>
<div style='background:#f9fafb;border-left:4px solid #3B82F6;border-radius:8px;padding:16px;margin:16px 0;font-style:italic;color:#374151;'>$comment</div>
<p><a href='$appUrl/tasks' style='background:#3B82F6;color:#fff;padding:10px 20px;border-radius:8px;text-decoration:none;font-weight:600;'>Antworten →</a></p>", 'Kommentar');
        return self::send($email, $name, "Neuer Kommentar zu: $title", $html);
    }

    // -------------------------------------------------------
    // PROJECTS
    // -------------------------------------------------------
    public static function sendProjectAddedEmail(string $email, string $name, array $project): array
    {
        $appUrl  = Helpers::getAppUrl();
        $title   = htmlspecialchars($project['name'] ?? 'Projekt');
        $html    = self::baseTemplate("
<h2 style='color:#111827;margin-top:0;'>Zu Projekt hinzugefügt 🚀</h2>
<p style='color:#374151;'>Du wurdest dem Projekt <strong>$title</strong> hinzugefügt.</p>
<p><a href='$appUrl/projects' style='background:#3B82F6;color:#fff;padding:10px 20px;border-radius:8px;text-decoration:none;font-weight:600;'>Projekt ansehen →</a></p>", 'Projekt');
        return self::send($email, $name, "Du wurdest zu Projekt \"$title\" hinzugefügt", $html);
    }

    // -------------------------------------------------------
    // VACATION
    // -------------------------------------------------------
    public static function sendVacationRequestEmail(string $managerEmail, string $managerName, array $vacation, string $requesterName): array
    {
        $appUrl = Helpers::getAppUrl();
        $from   = date('d.m.Y', strtotime($vacation['start_date']));
        $to     = date('d.m.Y', strtotime($vacation['end_date']));
        $days   = $vacation['days'] ?? 0;
        $html   = self::baseTemplate("
<h2 style='color:#111827;margin-top:0;'>Urlaubsantrag eingereicht 🌴</h2>
<p style='color:#374151;'><strong>$requesterName</strong> hat einen Urlaubsantrag eingereicht:</p>
<div style='background:#f9fafb;border-radius:8px;padding:16px;margin:16px 0;'>
  <p style='margin:4px 0;color:#374151;'>📅 Von: <strong>$from</strong></p>
  <p style='margin:4px 0;color:#374151;'>📅 Bis: <strong>$to</strong></p>
  <p style='margin:4px 0;color:#374151;'>📆 Tage: <strong>$days Arbeitstage</strong></p>
</div>
<p><a href='$appUrl/vacations' style='background:#10B981;color:#fff;padding:10px 20px;border-radius:8px;text-decoration:none;font-weight:600;'>Jetzt genehmigen →</a></p>", 'Urlaubsantrag');
        return self::send($managerEmail, $managerName, "Urlaubsantrag von $requesterName", $html);
    }

    public static function sendVacationApprovedEmail(string $email, string $name, array $vacation): array
    {
        $from = date('d.m.Y', strtotime($vacation['start_date']));
        $to   = date('d.m.Y', strtotime($vacation['end_date']));
        $html = self::baseTemplate("
<h2 style='color:#111827;margin-top:0;'>Urlaub genehmigt ✅</h2>
<p style='color:#374151;'>Dein Urlaubsantrag wurde genehmigt! 🎉</p>
<div style='background:#d1fae5;border-radius:8px;padding:16px;margin:16px 0;'>
  <p style='margin:4px 0;color:#065f46;'>📅 Von: <strong>$from</strong> bis <strong>$to</strong></p>
</div>
<p style='color:#6b7280;'>Genieße deinen wohlverdienten Urlaub! 🌴</p>", 'Urlaub genehmigt');
        return self::send($email, $name, '✅ Dein Urlaub wurde genehmigt!', $html);
    }

    public static function sendVacationRejectedEmail(string $email, string $name, array $vacation, string $reason = ''): array
    {
        $from   = date('d.m.Y', strtotime($vacation['start_date']));
        $to     = date('d.m.Y', strtotime($vacation['end_date']));
        $reasonHtml = $reason ? "<p style='color:#6b7280;'>Begründung: $reason</p>" : '';
        $html   = self::baseTemplate("
<h2 style='color:#111827;margin-top:0;'>Urlaubsantrag abgelehnt ❌</h2>
<p style='color:#374151;'>Leider wurde dein Urlaubsantrag abgelehnt.</p>
<div style='background:#fee2e2;border-radius:8px;padding:16px;margin:16px 0;'>
  <p style='margin:4px 0;color:#991b1b;'>📅 Von: <strong>$from</strong> bis <strong>$to</strong></p>
</div>
$reasonHtml", 'Urlaub abgelehnt');
        return self::send($email, $name, 'Dein Urlaubsantrag wurde abgelehnt', $html);
    }

    // -------------------------------------------------------
    // TIME TRACKING
    // -------------------------------------------------------
    public static function sendWeeklyTimeReport(string $email, string $name, float $actualHours, float $targetHours): array
    {
        $diff      = round($actualHours - $targetHours, 1);
        $diffColor = $diff >= 0 ? '#10B981' : '#EF4444';
        $diffText  = $diff >= 0 ? "+$diff Std. Überstunden" : abs($diff) . ' Std. Minusstunden';
        $html      = self::baseTemplate("
<h2 style='color:#111827;margin-top:0;'>📊 Wochenbericht</h2>
<p style='color:#374151;'>Deine Zeiterfassung für diese Woche:</p>
<div style='background:#f9fafb;border-radius:8px;padding:16px;margin:16px 0;'>
  <p style='margin:4px 0;'>✅ Gearbeitet: <strong>" . round($actualHours, 1) . " Stunden</strong></p>
  <p style='margin:4px 0;'>🎯 Ziel: <strong>" . round($targetHours, 1) . " Stunden</strong></p>
  <p style='margin:4px 0;color:$diffColor;font-weight:600;'>$diffText</p>
</div>", 'Wochenbericht');
        return self::send($email, $name, '📊 Dein Wochenbericht', $html);
    }

    // -------------------------------------------------------
    // CHAT
    // -------------------------------------------------------
    public static function sendChatNotificationEmail(string $email, string $name, string $senderName, string $message): array
    {
        $appUrl = Helpers::getAppUrl();
        $msg    = htmlspecialchars(mb_substr($message, 0, 200));
        $html   = self::baseTemplate("
<h2 style='color:#111827;margin-top:0;'>Neue Nachricht 💬</h2>
<p style='color:#374151;'><strong>$senderName</strong> hat dir geschrieben:</p>
<div style='background:#f9fafb;border-left:4px solid #3B82F6;border-radius:8px;padding:16px;margin:16px 0;font-style:italic;'>\"$msg\"</div>
<p><a href='$appUrl/chat' style='background:#3B82F6;color:#fff;padding:10px 20px;border-radius:8px;text-decoration:none;font-weight:600;'>Antworten →</a></p>", 'Neue Nachricht');
        return self::send($email, $name, "Neue Nachricht von $senderName", $html);
    }

    // -------------------------------------------------------
    // CONTENT PLAN
    // -------------------------------------------------------
    public static function sendContentStatusEmail(string $email, string $name, array $post, string $newStatus): array
    {
        $appUrl  = Helpers::getAppUrl();
        $title   = htmlspecialchars($post['title'] ?? 'Post');
        $statusMap = ['approved' => ['✅ Genehmigt', '#10B981'], 'revision' => ['✏️ Überarbeitung gewünscht', '#F59E0B']];
        [$label, $color] = $statusMap[$newStatus] ?? [$newStatus, '#6B7280'];
        $html    = self::baseTemplate("
<h2 style='color:#111827;margin-top:0;'>Content Status Update</h2>
<p style='color:#374151;'>Status für <strong>$title</strong> wurde geändert:</p>
<div style='background:#f9fafb;border-radius:8px;padding:16px;margin:16px 0;'>
  <span style='background:$color;color:#fff;padding:4px 12px;border-radius:20px;font-size:14px;font-weight:600;'>$label</span>
</div>
<p><a href='$appUrl/customers' style='background:#3B82F6;color:#fff;padding:10px 20px;border-radius:8px;text-decoration:none;font-weight:600;'>Ansehen →</a></p>", 'Content Update');
        return self::send($email, $name, "Content Update: $title", $html);
    }

    // -------------------------------------------------------
    // EXPENSE REPORTS
    // -------------------------------------------------------
    public static function sendExpenseStatusEmail(string $email, string $name, array $expense, string $status, string $comment = ''): array
    {
        $title    = htmlspecialchars($expense['title'] ?? 'Spesenbericht');
        $approved = $status === 'approved';
        $icon     = $approved ? '✅' : '❌';
        $label    = $approved ? 'Genehmigt' : 'Abgelehnt';
        $color    = $approved ? '#10B981' : '#EF4444';
        $commentHtml = $comment ? "<p style='color:#6b7280;margin-top:12px;'>Kommentar: " . htmlspecialchars($comment) . "</p>" : '';
        $html     = self::baseTemplate("
<h2 style='color:#111827;margin-top:0;'>$icon Spesenbericht $label</h2>
<p style='color:#374151;'>Dein Spesenbericht <strong>$title</strong> wurde bearbeitet:</p>
<div style='background:#f9fafb;border-radius:8px;padding:16px;margin:16px 0;'>
  <span style='background:$color;color:#fff;padding:4px 12px;border-radius:20px;font-size:14px;font-weight:600;'>$label</span>
  $commentHtml
</div>", 'Spesenbericht');
        return self::send($email, $name, "$icon Spesenbericht $label: $title", $html);
    }

    // -------------------------------------------------------
    // CUSTOMER PORTAL
    // -------------------------------------------------------
    public static function sendCustomerPortalEmail(string $email, string $name, string $portalUrl, ?string $password = null): array
    {
        $pwdHtml = $password ? "<p style='color:#374151;'>🔑 Passwort: <strong style='font-family:monospace;'>$password</strong></p>" : '';
        $html    = self::baseTemplate("
<h2 style='color:#111827;margin-top:0;'>Ihr Kundenportal ist bereit 🎉</h2>
<p style='color:#374151;'>Hier ist der Link zu Ihrem persönlichen Portal:</p>
$pwdHtml
<p><a href='$portalUrl' style='background:#3B82F6;color:#fff;padding:12px 24px;border-radius:8px;text-decoration:none;font-weight:600;'>Portal öffnen →</a></p>", 'Kundenportal');
        return self::send($email, $name, 'Ihr AgencyOS Kundenportal', $html);
    }
}
