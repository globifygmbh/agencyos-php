<?php

declare(strict_types=1);

namespace AgencyOS\Routes;

use AgencyOS\Core\{Database, Security, Helpers, EmailService};
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;

class CalendarRoutes
{
    public static function register(App $app): void
    {
        // GET /api/calendar/events
        $app->get('/api/calendar/events', function (Request $request, Response $response) {
            $user   = Security::getCurrentUser($request);
            $params = $request->getQueryParams();
            $uid    = $user['id'];

            $dateFrom = $params['date_from'] ?? date('Y-m-01');
            $dateTo   = $params['date_to']   ?? date('Y-m-t');

            // Own events + events where user is participant
            $sql   = "SELECT DISTINCT e.* FROM calendar_events e LEFT JOIN event_participants ep ON ep.event_id=e.id WHERE (e.user_id=? OR ep.user_id=?)";
            $binds = [$uid, $uid];

            // Also include team events (approved vacations as read-only)
            $events    = Database::fetchAll($sql, $binds);
            $expanded  = [];

            foreach ($events as $event) {
                $participants = Database::fetchAll(
                    'SELECT u.id,u.first_name,u.last_name,u.color FROM event_participants ep JOIN users u ON u.id=ep.user_id WHERE ep.event_id=?',
                    [$event['id']]
                );
                $event['participants'] = $participants;

                if ($event['recurring_rule'] && $event['is_recurring']) {
                    $instances = self::expandRecurring($event, $dateFrom, $dateTo);
                    $expanded  = array_merge($expanded, $instances);
                } else {
                    if ($event['start_date'] <= $dateTo . ' 23:59:59' && $event['end_date'] >= $dateFrom) {
                        $expanded[] = $event;
                    }
                }
            }

            return self::json($response, $expanded);
        });

        // POST /api/calendar/events
        $app->post('/api/calendar/events', function (Request $request, Response $response) {
            $user = Security::getCurrentUser($request);
            $body = (array) $request->getParsedBody();

            if (empty($body['title']) || empty($body['start_date'])) {
                return self::error($response, 'Titel und Startdatum erforderlich', 400);
            }

            $id = Helpers::uuid();
            Database::insert('calendar_events', [
                'id'             => $id,
                'user_id'        => $user['id'],
                'title'          => $body['title'],
                'description'    => $body['description'] ?? null,
                'location'       => $body['location'] ?? null,
                'start_date'     => $body['start_date'],
                'end_date'       => $body['end_date'] ?? $body['start_date'],
                'all_day'        => (int) ($body['all_day'] ?? 0),
                'color'          => $body['color'] ?? '#3B82F6',
                'category'       => $body['category'] ?? 'general',
                'is_recurring'   => (int) ($body['is_recurring'] ?? 0),
                'recurring_rule' => isset($body['recurring_rule']) ? json_encode($body['recurring_rule']) : null,
                'created_at'     => Helpers::now(),
                'updated_at'     => Helpers::now(),
            ]);

            // Add participants
            $participants = $body['participant_ids'] ?? [];
            foreach ($participants as $pid) {
                Database::execute('INSERT IGNORE INTO event_participants (event_id,user_id) VALUES (?,?)', [$id, $pid]);
                Helpers::createNotification($pid, 'event_invite', 'Termin-Einladung', 'Du wurdest zu "' . $body['title'] . '" eingeladen', '/calendar');
                $participant = Database::fetchOne('SELECT * FROM users WHERE id=?', [$pid]);
                if ($participant && Helpers::shouldSendEmail($participant, 'event_invite')) {
                    self::sendEventInviteEmail($participant, $body['title'], $body['start_date']);
                }
            }

            return self::json($response, Database::fetchOne('SELECT * FROM calendar_events WHERE id=?', [$id]), 201);
        });

        // PUT /api/calendar/events/{event_id}
        $app->put('/api/calendar/events/{event_id}', function (Request $request, Response $response, array $args) {
            $user    = Security::getCurrentUser($request);
            $body    = (array) $request->getParsedBody();
            $eventId = $args['event_id'];

            $event = Database::fetchOne('SELECT * FROM calendar_events WHERE id=?', [$eventId]);
            if (!$event) return self::error($response, 'Termin nicht gefunden', 404);

            $allowed = ['title','description','location','start_date','end_date','all_day','color','category','is_recurring','recurring_rule'];
            $updates = ['updated_at' => Helpers::now()];
            foreach ($allowed as $f) {
                if (array_key_exists($f, $body)) {
                    $updates[$f] = is_array($body[$f]) ? json_encode($body[$f]) : $body[$f];
                }
            }
            Database::update('calendar_events', $updates, ['id' => $eventId]);

            // Update participants
            if (isset($body['participant_ids'])) {
                Database::execute('DELETE FROM event_participants WHERE event_id=?', [$eventId]);
                foreach ($body['participant_ids'] as $pid) {
                    Database::execute('INSERT IGNORE INTO event_participants (event_id,user_id) VALUES (?,?)', [$eventId, $pid]);
                }
            }

            return self::json($response, Database::fetchOne('SELECT * FROM calendar_events WHERE id=?', [$eventId]));
        });

        // DELETE /api/calendar/events/{event_id}
        $app->delete('/api/calendar/events/{event_id}', function (Request $request, Response $response, array $args) {
            $user    = Security::getCurrentUser($request);
            $eventId = $args['event_id'];
            $event   = Database::fetchOne('SELECT * FROM calendar_events WHERE id=?', [$eventId]);
            if (!$event) return self::error($response, 'Termin nicht gefunden', 404);

            if ($event['user_id'] !== $user['id'] && $user['role'] !== 'CHEF') {
                return self::error($response, 'Keine Berechtigung', 403);
            }
            Database::delete('calendar_events', ['id' => $eventId]);
            return self::json($response, ['message' => 'Termin gelöscht']);
        });

        // GET /api/calendar/settings
        $app->get('/api/calendar/settings', function (Request $request, Response $response) {
            $user = Security::getCurrentUser($request);
            $settings = Database::fetchOne('SELECT * FROM user_calendar_settings WHERE user_id=?', [$user['id']]);
            if (!$settings) return self::json($response, ['user_id' => $user['id'], 'google_connected' => false, 'ical_calendars' => []]);

            $settings['google_connected'] = (bool) $settings['google_connected'];
            if ($settings['google_tokens']) $settings['google_tokens'] = '***'; // mask
            $settings['ical_calendars'] = Helpers::jsonDecode($settings['ical_calendars'] ?? null, []);
            return self::json($response, $settings);
        });

        // PUT /api/calendar/settings
        $app->put('/api/calendar/settings', function (Request $request, Response $response) {
            $user    = Security::getCurrentUser($request);
            $body    = (array) $request->getParsedBody();
            $exists  = Database::fetchOne('SELECT user_id FROM user_calendar_settings WHERE user_id=?', [$user['id']]);

            $data = [];
            if (isset($body['ical_calendars'])) $data['ical_calendars'] = json_encode($body['ical_calendars']);

            if ($exists) {
                if (!empty($data)) Database::update('user_calendar_settings', $data, ['user_id' => $user['id']]);
            } else {
                $data['user_id'] = $user['id'];
                Database::insert('user_calendar_settings', $data);
            }
            return self::json($response, ['message' => 'Einstellungen gespeichert']);
        });
    }

    private static function expandRecurring(array $event, string $dateFrom, string $dateTo): array
    {
        $rule     = Helpers::jsonDecode($event['recurring_rule'], []);
        $freq     = $rule['frequency'] ?? 'weekly';
        $interval = max(1, (int) ($rule['interval'] ?? 1));
        $endDate  = $rule['end_date'] ?? null;

        $current    = new \DateTimeImmutable($event['start_date']);
        $eventEnd   = new \DateTimeImmutable($event['end_date']);
        $rangeFrom  = new \DateTimeImmutable($dateFrom);
        $rangeTo    = new \DateTimeImmutable($dateTo);
        $limitDate  = $endDate ? new \DateTimeImmutable($endDate) : $rangeTo;
        $duration   = $current->diff($eventEnd);

        $instances  = [];
        $count      = 0;

        while ($current <= min($rangeTo, $limitDate) && $count < 52) {
            if ($current >= $rangeFrom) {
                $inst              = $event;
                $inst['id']        = $event['id'] . '_' . $current->format('Ymd');
                $inst['start_date']= $current->format('Y-m-d H:i:s');
                $inst['end_date']  = $current->add($duration)->format('Y-m-d H:i:s');
                $instances[]       = $inst;
            }
            $current = match($freq) {
                'daily'   => $current->modify("+$interval day"),
                'weekly'  => $current->modify("+$interval week"),
                'monthly' => $current->modify("+$interval month"),
                'yearly'  => $current->modify("+$interval year"),
                default   => $current->modify("+$interval week"),
            };
            $count++;
        }
        return $instances;
    }

    private static function sendEventInviteEmail(array $user, string $eventTitle, string $startDate): void
    {
        $appUrl = Helpers::getAppUrl();
        $date   = date('d.m.Y H:i', strtotime($startDate));
        $name   = Helpers::userName($user);
        $title  = htmlspecialchars($eventTitle);
        $body   = "
<h2 style='margin-top:0;color:#111827;'>Termin-Einladung 📅</h2>
<p>Du wurdest zu folgendem Termin eingeladen:</p>
<div style='background:#f9fafb;border-radius:8px;padding:16px;'>
  <h3 style='margin:0 0 4px;'>$title</h3>
  <p style='color:#6b7280;margin:0;'>📅 $date</p>
</div>
<p><a href='$appUrl/calendar' style='background:#3B82F6;color:#fff;padding:10px 20px;border-radius:8px;text-decoration:none;font-weight:600;'>Kalender öffnen →</a></p>";

        // Minimal inline send
        $from     = \AgencyOS\Core\Config::mailFrom();
        $fromName = \AgencyOS\Core\Config::mailFromName();
        $apiKey   = \AgencyOS\Core\Config::resendKey();
        if (!$apiKey) return;

        $payload = json_encode(['from' => "$fromName <$from>", 'to' => [$user['email']], 'subject' => "Termin-Einladung: $title", 'html' => $body]);
        $ch = curl_init('https://api.resend.com/emails');
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true, CURLOPT_POSTFIELDS => $payload, CURLOPT_HTTPHEADER => ['Content-Type: application/json', "Authorization: Bearer $apiKey"], CURLOPT_TIMEOUT => 5]);
        curl_exec($ch);
        curl_close($ch);
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
