<?php

declare(strict_types=1);

namespace AgencyOS\Routes;

use AgencyOS\Core\{Database, Security, Helpers, Storage};
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;

class MiscRoutes
{
    public static function register(App $app): void
    {
        // ---- TOOLS ----

        // GET /api/tools
        $app->get('/api/tools', function (Request $request, Response $response) {
            $user  = Security::getCurrentUser($request);
            Security::requireRole($user, ['CHEF', 'BUCHHALTUNG']);
            $tools = Database::fetchAll('SELECT * FROM tools ORDER BY name');

            // Mask passwords for non-CHEF
            if ($user['role'] !== 'CHEF') {
                foreach ($tools as &$t) { $t['password'] = '***'; }
            }
            return self::json($response, $tools);
        });

        // POST /api/tools
        $app->post('/api/tools', function (Request $request, Response $response) {
            $user = Security::getCurrentUser($request);
            Security::requireRole($user, ['CHEF', 'BUCHHALTUNG']);
            $body = (array) $request->getParsedBody();
            if (empty($body['name'])) return self::error($response, 'Name erforderlich', 400);

            $id = Helpers::uuid();
            Database::insert('tools', ['id' => $id, 'name' => $body['name'], 'url' => $body['url'] ?? '', 'username' => $body['username'] ?? '', 'password' => $body['password'] ?? '', 'notes' => $body['notes'] ?? null, 'category' => $body['category'] ?? 'general', 'created_by' => $user['id'], 'created_at' => Helpers::now(), 'updated_at' => Helpers::now()]);
            return self::json($response, Database::fetchOne('SELECT * FROM tools WHERE id=?', [$id]), 201);
        });

        // PUT /api/tools/{tool_id}
        $app->put('/api/tools/{tool_id}', function (Request $request, Response $response, array $args) {
            $user = Security::getCurrentUser($request);
            Security::requireRole($user, ['CHEF', 'BUCHHALTUNG']);
            $body    = (array) $request->getParsedBody();
            $allowed = ['name','url','username','password','notes','category'];
            $updates = ['updated_at' => Helpers::now()];
            foreach ($allowed as $f) {
                if (array_key_exists($f, $body)) $updates[$f] = $body[$f];
            }
            Database::update('tools', $updates, ['id' => $args['tool_id']]);
            return self::json($response, Database::fetchOne('SELECT * FROM tools WHERE id=?', [$args['tool_id']]));
        });

        // DELETE /api/tools/{tool_id}
        $app->delete('/api/tools/{tool_id}', function (Request $request, Response $response, array $args) {
            $user = Security::getCurrentUser($request);
            Security::requireRole($user, ['CHEF', 'BUCHHALTUNG']);
            Database::delete('tools', ['id' => $args['tool_id']]);
            return self::json($response, ['message' => 'Tool gelöscht']);
        });

        // ---- PERSONAL PASSWORDS ----

        // GET /api/personal-passwords
        $app->get('/api/personal-passwords', function (Request $request, Response $response) {
            $user = Security::getCurrentUser($request);
            return self::json($response, Database::fetchAll('SELECT * FROM personal_passwords WHERE user_id=? ORDER BY title', [$user['id']]));
        });

        // POST /api/personal-passwords
        $app->post('/api/personal-passwords', function (Request $request, Response $response) {
            $user = Security::getCurrentUser($request);
            $body = (array) $request->getParsedBody();
            if (empty($body['title'])) return self::error($response, 'Titel erforderlich', 400);

            $id = Helpers::uuid();
            Database::insert('personal_passwords', ['id' => $id, 'user_id' => $user['id'], 'title' => $body['title'], 'username' => $body['username'] ?? '', 'password' => $body['password'] ?? '', 'url' => $body['url'] ?? '', 'notes' => $body['notes'] ?? null, 'created_at' => Helpers::now(), 'updated_at' => Helpers::now()]);
            return self::json($response, Database::fetchOne('SELECT * FROM personal_passwords WHERE id=?', [$id]), 201);
        });

        // PUT /api/personal-passwords/{pw_id}
        $app->put('/api/personal-passwords/{pw_id}', function (Request $request, Response $response, array $args) {
            $user    = Security::getCurrentUser($request);
            $body    = (array) $request->getParsedBody();
            $allowed = ['title','username','password','url','notes'];
            $updates = ['updated_at' => Helpers::now()];
            foreach ($allowed as $f) {
                if (array_key_exists($f, $body)) $updates[$f] = $body[$f];
            }
            Database::update('personal_passwords', $updates, ['id' => $args['pw_id'], 'user_id' => $user['id']]);
            return self::json($response, Database::fetchOne('SELECT * FROM personal_passwords WHERE id=?', [$args['pw_id']]));
        });

        // DELETE /api/personal-passwords/{pw_id}
        $app->delete('/api/personal-passwords/{pw_id}', function (Request $request, Response $response, array $args) {
            $user = Security::getCurrentUser($request);
            Database::delete('personal_passwords', ['id' => $args['pw_id'], 'user_id' => $user['id']]);
            return self::json($response, ['message' => 'Gelöscht']);
        });

        // ---- NOTES ----

        // GET /api/notes
        $app->get('/api/notes', function (Request $request, Response $response) {
            $user = Security::getCurrentUser($request);
            return self::json($response, Database::fetchAll('SELECT * FROM notes WHERE user_id=? ORDER BY is_pinned DESC,updated_at DESC', [$user['id']]));
        });

        // POST /api/notes
        $app->post('/api/notes', function (Request $request, Response $response) {
            $user = Security::getCurrentUser($request);
            $body = (array) $request->getParsedBody();
            $id   = Helpers::uuid();
            Database::insert('notes', ['id' => $id, 'user_id' => $user['id'], 'title' => $body['title'] ?? '', 'content' => $body['content'] ?? null, 'color' => $body['color'] ?? '#FFFFFF', 'is_pinned' => (int)($body['is_pinned'] ?? 0), 'created_at' => Helpers::now(), 'updated_at' => Helpers::now()]);
            return self::json($response, Database::fetchOne('SELECT * FROM notes WHERE id=?', [$id]), 201);
        });

        // PUT /api/notes/{note_id}
        $app->put('/api/notes/{note_id}', function (Request $request, Response $response, array $args) {
            $user    = Security::getCurrentUser($request);
            $body    = (array) $request->getParsedBody();
            $allowed = ['title','content','color','is_pinned'];
            $updates = ['updated_at' => Helpers::now()];
            foreach ($allowed as $f) {
                if (array_key_exists($f, $body)) $updates[$f] = $body[$f];
            }
            Database::update('notes', $updates, ['id' => $args['note_id'], 'user_id' => $user['id']]);
            return self::json($response, Database::fetchOne('SELECT * FROM notes WHERE id=?', [$args['note_id']]));
        });

        // DELETE /api/notes/{note_id}
        $app->delete('/api/notes/{note_id}', function (Request $request, Response $response, array $args) {
            $user = Security::getCurrentUser($request);
            Database::delete('notes', ['id' => $args['note_id'], 'user_id' => $user['id']]);
            return self::json($response, ['message' => 'Notiz gelöscht']);
        });

        // ---- NOTIFICATIONS ----

        // GET /api/notifications
        $app->get('/api/notifications', function (Request $request, Response $response) {
            $user   = Security::getCurrentUser($request);
            $params = $request->getQueryParams();
            $limit  = min((int)($params['limit'] ?? 30), 100);
            $notifs = Database::fetchAll('SELECT * FROM notifications WHERE user_id=? ORDER BY created_at DESC LIMIT ?', [$user['id'], $limit]);
            $unread = (int) (Database::fetchOne('SELECT COUNT(*) as c FROM notifications WHERE user_id=? AND is_read=0', [$user['id']])['c'] ?? 0);
            return self::json($response, ['notifications' => $notifs, 'unread_count' => $unread]);
        });

        // PUT /api/notifications/{notif_id}/read
        $app->put('/api/notifications/{notif_id}/read', function (Request $request, Response $response, array $args) {
            $user = Security::getCurrentUser($request);
            Database::update('notifications', ['is_read' => 1], ['id' => $args['notif_id'], 'user_id' => $user['id']]);
            return self::json($response, ['message' => 'Gelesen']);
        });

        // PUT /api/notifications/read-all
        $app->put('/api/notifications/read-all', function (Request $request, Response $response) {
            $user = Security::getCurrentUser($request);
            Database::execute('UPDATE notifications SET is_read=1 WHERE user_id=?', [$user['id']]);
            return self::json($response, ['message' => 'Alle als gelesen markiert']);
        });

        // DELETE /api/notifications/{notif_id}
        $app->delete('/api/notifications/{notif_id}', function (Request $request, Response $response, array $args) {
            $user = Security::getCurrentUser($request);
            Database::delete('notifications', ['id' => $args['notif_id'], 'user_id' => $user['id']]);
            return self::json($response, ['message' => 'Gelöscht']);
        });

        // ---- WEATHER ----

        // GET /api/weather
        $app->get('/api/weather', function (Request $request, Response $response) {
            Security::getCurrentUser($request);
            $params = $request->getQueryParams();
            $city   = $params['city'] ?? 'Vienna';

            $coords = self::getCityCoords($city);
            $url    = "https://api.open-meteo.com/v1/forecast?latitude={$coords['lat']}&longitude={$coords['lon']}&current_weather=true&hourly=relativehumidity_2m&timezone=Europe/Vienna";

            $ch = curl_init($url);
            curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 5]);
            $body     = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode !== 200) return self::error($response, 'Wetter nicht verfügbar', 503);

            $data = json_decode((string)$body, true);
            $cw   = $data['current_weather'] ?? [];
            return self::json($response, [
                'city'        => $city,
                'temperature' => $cw['temperature'] ?? null,
                'windspeed'   => $cw['windspeed'] ?? null,
                'weathercode' => $cw['weathercode'] ?? null,
                'description' => self::weatherDescription((int)($cw['weathercode'] ?? 0)),
            ]);
        });

        // ---- FILE UPLOAD ----

        // POST /api/upload
        $app->post('/api/upload', function (Request $request, Response $response) {
            $user  = Security::getCurrentUser($request);
            $files = $request->getUploadedFiles();
            $body  = (array) $request->getParsedBody();

            if (empty($files['file'])) return self::error($response, 'Keine Datei', 400);
            $stored = Storage::store($files['file'], 'files');

            $id = Helpers::uuid();

            // Optional: link to task
            if (!empty($body['task_id'])) {
                Database::insert('task_files', ['id' => $id, 'task_id' => $body['task_id'], 'file_url' => $stored['url'], 'file_name' => $stored['name'], 'file_size' => $stored['size'], 'file_type' => $stored['type'], 'uploaded_by' => $user['id'], 'created_at' => Helpers::now()]);
            }

            return self::json($response, ['url' => $stored['url'], 'name' => $stored['name'], 'size' => $stored['size'], 'type' => $stored['type']]);
        });
    }

    private static function getCityCoords(string $city): array
    {
        $cities = [
            'Vienna' => ['lat' => 48.2082, 'lon' => 16.3738],
            'Wien'   => ['lat' => 48.2082, 'lon' => 16.3738],
            'Graz'   => ['lat' => 47.0707, 'lon' => 15.4395],
            'Linz'   => ['lat' => 48.3069, 'lon' => 14.2858],
            'Salzburg'=>['lat' => 47.8095, 'lon' => 13.0550],
            'Berlin' => ['lat' => 52.5200, 'lon' => 13.4050],
            'Munich' => ['lat' => 48.1351, 'lon' => 11.5820],
            'München'=> ['lat' => 48.1351, 'lon' => 11.5820],
            'Zurich' => ['lat' => 47.3769, 'lon' => 8.5417],
            'Zürich' => ['lat' => 47.3769, 'lon' => 8.5417],
        ];
        return $cities[$city] ?? $cities['Vienna'];
    }

    private static function weatherDescription(int $code): string
    {
        return match(true) {
            $code === 0        => 'Klar',
            in_array($code, [1,2,3]) => 'Teilweise bewölkt',
            in_array($code, [45,48]) => 'Nebel',
            in_array($code, [51,53,55]) => 'Nieselregen',
            in_array($code, [61,63,65]) => 'Regen',
            in_array($code, [71,73,75]) => 'Schnee',
            in_array($code, [80,81,82]) => 'Regenschauer',
            in_array($code, [95,96,99]) => 'Gewitter',
            default => 'Unbekannt',
        };
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
