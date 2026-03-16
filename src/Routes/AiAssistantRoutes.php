<?php

declare(strict_types=1);

namespace AgencyOS\Routes;

use AgencyOS\Core\{Database, Security, Helpers};
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;

class AiAssistantRoutes
{
    // Supported action types
    const ACTIONS = [
        'create_task', 'create_event', 'create_project', 'log_time',
        'request_vacation', 'set_reminder', 'search_tasks', 'search_users',
        'get_status', 'get_schedule', 'get_workload', 'get_points',
        'get_achievements', 'complete_task', 'assign_task', 'change_deadline',
        'create_note',
    ];

    public static function register(App $app): void
    {
        // POST /api/ai-assistant/process
        $app->post('/api/ai-assistant/process', function (Request $request, Response $response) {
            $user = Security::getCurrentUser($request);
            $body = (array) $request->getParsedBody();
            $text = trim($body['text'] ?? '');

            if (!$text) return self::error($response, 'Text erforderlich', 400);

            $apiKey = \AgencyOS\Core\Config::openaiKey() ?: \AgencyOS\Core\Config::anthropicKey();
            if (!$apiKey) {
                return self::json($response, ['action' => 'unknown', 'message' => 'KI-Integration nicht konfiguriert.', 'original_text' => $text]);
            }

            $users    = Database::fetchAll('SELECT id,first_name,last_name,username FROM users WHERE is_active=1');
            $userList = implode(', ', array_map(fn($u) => ($u['first_name'] . ' ' . $u['last_name']), $users));

            $systemPrompt = "Du bist ein intelligenter Assistent für AgencyOS (deutsches Projektmanagement-Tool). Analysiere den Text und extrahiere die beabsichtigte Aktion sowie Parameter.\n\nVerfügbare Aktionen: " . implode(', ', self::ACTIONS) . "\n\nTeam-Mitglieder: $userList\n\nHeutiges Datum: " . date('Y-m-d') . "\n\nAntworte NUR mit validem JSON in diesem Format:\n{\"action\": \"action_name\", \"params\": {...}, \"confidence\": 0.0-1.0, \"message\": \"Kurze Bestätigung auf Deutsch\"}\n\nDatum-Format: YYYY-MM-DD. Relative Daten ('morgen', 'nächste Woche') in absolute Daten umwandeln.";

            $parsed = self::callOpenAI($apiKey, $systemPrompt, $text);
            if (!$parsed) {
                return self::json($response, ['action' => 'unknown', 'message' => 'Konnte den Text nicht verarbeiten.', 'original_text' => $text]);
            }

            return self::json($response, array_merge($parsed, ['original_text' => $text]));
        });

        // POST /api/ai-assistant/execute
        $app->post('/api/ai-assistant/execute', function (Request $request, Response $response) {
            $user   = Security::getCurrentUser($request);
            $body   = (array) $request->getParsedBody();
            $action = $body['action'] ?? '';
            $params = $body['params'] ?? [];

            $result = match($action) {
                'create_task'     => self::executeCreateTask($user, $params),
                'create_event'    => self::executeCreateEvent($user, $params),
                'create_note'     => self::executeCreateNote($user, $params),
                'log_time'        => self::executeLogTime($user, $params),
                'request_vacation'=> self::executeRequestVacation($user, $params),
                'get_status'      => self::executeGetStatus($user),
                'get_points'      => self::executeGetPoints($user),
                'get_achievements'=> self::executeGetAchievements($user),
                default           => ['success' => false, 'message' => "Aktion '$action' wird nicht unterstützt."],
            };

            return self::json($response, $result);
        });

        // POST /api/ai-assistant/transcribe  (audio → text via Whisper)
        $app->post('/api/ai-assistant/transcribe', function (Request $request, Response $response) {
            $user  = Security::getCurrentUser($request);
            $files = $request->getUploadedFiles();
            if (empty($files['audio'])) return self::error($response, 'Keine Audio-Datei', 400);

            $apiKey = \AgencyOS\Core\Config::openaiKey();
            if (!$apiKey) return self::error($response, 'OpenAI nicht konfiguriert', 503);

            // Save temp file
            $tmp = sys_get_temp_dir() . '/' . Helpers::uuid() . '.webm';
            $files['audio']->moveTo($tmp);

            try {
                $text = self::transcribeAudio($tmp, $apiKey);
                return self::json($response, ['text' => $text]);
            } finally {
                if (file_exists($tmp)) unlink($tmp);
            }
        });
    }

    // -------------------------------------------------------
    // AI call helpers
    // -------------------------------------------------------

    private static function callOpenAI(string $apiKey, string $system, string $userText): ?array
    {
        $isAnthropic = str_starts_with($apiKey, 'sk-ant-');

        if ($isAnthropic) {
            $payload = json_encode([
                'model'      => 'claude-haiku-4-5-20251001',
                'max_tokens' => 500,
                'system'     => $system,
                'messages'   => [['role' => 'user', 'content' => $userText]],
            ]);
            $url     = 'https://api.anthropic.com/v1/messages';
            $headers = ['Content-Type: application/json', "x-api-key: $apiKey", 'anthropic-version: 2023-06-01'];
        } else {
            $payload = json_encode([
                'model'       => 'gpt-4o-mini',
                'max_tokens'  => 500,
                'messages'    => [
                    ['role' => 'system', 'content' => $system],
                    ['role' => 'user',   'content' => $userText],
                ],
            ]);
            $url     = 'https://api.openai.com/v1/chat/completions';
            $headers = ['Content-Type: application/json', "Authorization: Bearer $apiKey"];
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true, CURLOPT_POSTFIELDS => $payload, CURLOPT_HTTPHEADER => $headers, CURLOPT_TIMEOUT => 15]);
        $res  = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($code !== 200) { error_log('[AI] HTTP ' . $code . ': ' . $res); return null; }

        $data    = json_decode((string)$res, true);
        $content = $isAnthropic
            ? ($data['content'][0]['text'] ?? '')
            : ($data['choices'][0]['message']['content'] ?? '');

        // Strip possible markdown code fences
        $content = preg_replace('/^```json\s*/i', '', trim($content));
        $content = preg_replace('/\s*```$/', '', $content);

        return json_decode($content, true);
    }

    private static function transcribeAudio(string $filePath, string $apiKey): string
    {
        $ch = curl_init('https://api.openai.com/v1/audio/transcriptions');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => ['file' => new \CURLFile($filePath), 'model' => 'whisper-1', 'language' => 'de'],
            CURLOPT_HTTPHEADER     => ["Authorization: Bearer $apiKey"],
            CURLOPT_TIMEOUT        => 30,
        ]);
        $res  = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($code !== 200) throw new \RuntimeException('Transcription failed: ' . $res);
        $data = json_decode((string)$res, true);
        return $data['text'] ?? '';
    }

    // -------------------------------------------------------
    // Execute actions
    // -------------------------------------------------------

    private static function executeCreateTask(array $user, array $params): array
    {
        if (empty($params['title'])) return ['success' => false, 'message' => 'Kein Titel angegeben.'];

        $statusId = null;
        $first    = Database::fetchOne('SELECT id FROM task_statuses WHERE is_done=0 ORDER BY sort_order LIMIT 1');
        if ($first) $statusId = $first['id'];

        $assignedTo = null;
        if (!empty($params['assigned_to'])) {
            $u = Database::fetchOne('SELECT id FROM users WHERE first_name LIKE ? OR last_name LIKE ? OR username LIKE ? LIMIT 1', ["%{$params['assigned_to']}%", "%{$params['assigned_to']}%", "%{$params['assigned_to']}%"]);
            if ($u) $assignedTo = $u['id'];
        }

        $id = Helpers::uuid();
        Database::insert('tasks', [
            'id'          => $id,
            'title'       => $params['title'],
            'description' => $params['description'] ?? null,
            'deadline'    => $params['deadline'] ?? null,
            'priority'    => strtoupper($params['priority'] ?? 'MEDIUM'),
            'assigned_to' => $assignedTo ?? $user['id'],
            'status_id'   => $statusId,
            'created_by'  => $user['id'],
            'created_at'  => Helpers::now(),
            'updated_at'  => Helpers::now(),
        ]);

        return ['success' => true, 'message' => "✅ Aufgabe \"" . $params['title'] . "\" erstellt!", 'id' => $id];
    }

    private static function executeCreateEvent(array $user, array $params): array
    {
        if (empty($params['title']) || empty($params['start_date'])) {
            return ['success' => false, 'message' => 'Titel und Datum erforderlich.'];
        }

        $id = Helpers::uuid();
        Database::insert('calendar_events', [
            'id'         => $id,
            'user_id'    => $user['id'],
            'title'      => $params['title'],
            'start_date' => $params['start_date'],
            'end_date'   => $params['end_date'] ?? $params['start_date'],
            'all_day'    => (int)($params['all_day'] ?? 0),
            'color'      => '#3B82F6',
            'category'   => 'general',
            'created_at' => Helpers::now(),
            'updated_at' => Helpers::now(),
        ]);

        return ['success' => true, 'message' => "📅 Termin \"" . $params['title'] . "\" erstellt!", 'id' => $id];
    }

    private static function executeCreateNote(array $user, array $params): array
    {
        $id = Helpers::uuid();
        Database::insert('notes', [
            'id'         => $id,
            'user_id'    => $user['id'],
            'title'      => $params['title'] ?? 'Neue Notiz',
            'content'    => $params['content'] ?? '',
            'created_at' => Helpers::now(),
            'updated_at' => Helpers::now(),
        ]);
        return ['success' => true, 'message' => "📝 Notiz erstellt!", 'id' => $id];
    }

    private static function executeLogTime(array $user, array $params): array
    {
        $duration = isset($params['hours']) ? ((float)$params['hours'] * 3600) : 3600;
        $id       = Helpers::uuid();
        Database::insert('time_entries', [
            'id'          => $id,
            'user_id'     => $user['id'],
            'description' => $params['description'] ?? 'Via KI-Assistent',
            'start_time'  => $params['date'] ? ($params['date'] . ' 09:00:00') : Helpers::now(),
            'duration'    => (int)$duration,
            'created_at'  => Helpers::now(),
            'updated_at'  => Helpers::now(),
        ]);
        $h = round($duration / 3600, 1);
        return ['success' => true, 'message' => "⏱️ {$h} Stunden wurden eingetragen!", 'id' => $id];
    }

    private static function executeRequestVacation(array $user, array $params): array
    {
        if (empty($params['start_date']) || empty($params['end_date'])) {
            return ['success' => false, 'message' => 'Start- und Enddatum erforderlich.'];
        }
        $start = new \DateTimeImmutable($params['start_date']);
        $end   = new \DateTimeImmutable($params['end_date']);
        $days  = Helpers::countBusinessDays($start, $end);
        $id    = Helpers::uuid();
        Database::insert('vacations', ['id' => $id, 'user_id' => $user['id'], 'start_date' => $params['start_date'], 'end_date' => $params['end_date'], 'days' => $days, 'status' => 'pending', 'created_at' => Helpers::now(), 'updated_at' => Helpers::now()]);
        return ['success' => true, 'message' => "🌴 Urlaubsantrag für $days Tage eingereicht!", 'id' => $id];
    }

    private static function executeGetStatus(array $user): array
    {
        $tasks  = (int)(Database::fetchOne("SELECT COUNT(*) as c FROM tasks t LEFT JOIN task_statuses ts ON ts.id=t.status_id WHERE t.assigned_to=? AND (ts.is_done=0 OR ts.id IS NULL) AND t.is_archived=0", [$user['id']])['c'] ?? 0);
        $todaySecs = (float)(Database::fetchOne("SELECT COALESCE(SUM(duration),0) as s FROM time_entries WHERE user_id=? AND DATE(start_time)=CURDATE()", [$user['id']])['s'] ?? 0);
        $h = round($todaySecs / 3600, 1);
        return ['success' => true, 'message' => "📊 Du hast $tasks offene Aufgaben und heute $h Stunden gearbeitet."];
    }

    private static function executeGetPoints(array $user): array
    {
        $pts   = \AgencyOS\Core\Gamification::getUserPoints($user['id']);
        $level = \AgencyOS\Core\Gamification::getUserLevel($pts);
        return ['success' => true, 'message' => "⭐ Du hast $pts Punkte und bist auf Level {$level['level']}!"];
    }

    private static function executeGetAchievements(array $user): array
    {
        $earned = (int)(Database::fetchOne('SELECT COUNT(*) as c FROM user_achievements WHERE user_id=?', [$user['id']])['c'] ?? 0);
        return ['success' => true, 'message' => "🏆 Du hast $earned Achievements freigeschaltet!"];
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
