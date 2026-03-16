<?php

declare(strict_types=1);

namespace AgencyOS\Routes;

use AgencyOS\Core\{Database, Security, Helpers, EmailService, Storage, Gamification};
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;

class ChatRoutes
{
    public static function register(App $app): void
    {
        // GET /api/conversations
        $app->get('/api/conversations', function (Request $request, Response $response) {
            $user = Security::getCurrentUser($request);
            $uid  = $user['id'];

            $convs = Database::fetchAll(
                "SELECT c.* FROM conversations c JOIN conversation_participants cp ON cp.conversation_id=c.id WHERE cp.user_id=? AND c.is_archived=0 ORDER BY c.updated_at DESC",
                [$uid]
            );

            $result = [];
            foreach ($convs as $conv) {
                $participants = Database::fetchAll(
                    'SELECT u.id,u.first_name,u.last_name,u.profile_image,u.color,u.last_active FROM conversation_participants cp JOIN users u ON u.id=cp.user_id WHERE cp.conversation_id=?',
                    [$conv['id']]
                );
                $lastMsg = Database::fetchOne(
                    'SELECT * FROM messages WHERE conversation_id=? AND is_deleted=0 ORDER BY created_at DESC LIMIT 1',
                    [$conv['id']]
                );
                $unread = (int) (Database::fetchOne(
                    "SELECT COUNT(*) as c FROM messages m WHERE m.conversation_id=? AND m.user_id!=? AND m.is_deleted=0 AND m.id NOT IN (SELECT message_id FROM message_reads WHERE user_id=?)",
                    [$conv['id'], $uid, $uid]
                )['c'] ?? 0);

                $conv['participants'] = $participants;
                $conv['last_message'] = $lastMsg;
                $conv['unread_count'] = $unread;
                $result[] = $conv;
            }
            return self::json($response, $result);
        });

        // POST /api/conversations
        $app->post('/api/conversations', function (Request $request, Response $response) {
            $user = Security::getCurrentUser($request);
            $body = (array) $request->getParsedBody();
            $type = $body['type'] ?? 'private';
            $uid  = $user['id'];

            // Prevent duplicate private convs
            if ($type === 'private' && !empty($body['participant_id'])) {
                $otherId = $body['participant_id'];
                $existing = Database::fetchOne(
                    "SELECT c.id FROM conversations c
                     JOIN conversation_participants cp1 ON cp1.conversation_id=c.id AND cp1.user_id=?
                     JOIN conversation_participants cp2 ON cp2.conversation_id=c.id AND cp2.user_id=?
                     WHERE c.type='private' LIMIT 1",
                    [$uid, $otherId]
                );
                if ($existing) return self::json($response, ['id' => $existing['id']]);
            }

            $id = Helpers::uuid();
            Database::insert('conversations', [
                'id'         => $id,
                'type'       => $type,
                'name'       => $body['name'] ?? null,
                'created_by' => $uid,
                'created_at' => Helpers::now(),
                'updated_at' => Helpers::now(),
            ]);

            // Add creator
            Database::insert('conversation_participants', ['conversation_id' => $id, 'user_id' => $uid]);

            // Add other participants
            $others = $body['participant_ids'] ?? [];
            if (!empty($body['participant_id'])) $others[] = $body['participant_id'];
            foreach (array_unique($others) as $pid) {
                if ($pid !== $uid) {
                    Database::insert('conversation_participants', ['conversation_id' => $id, 'user_id' => $pid]);
                }
            }

            return self::json($response, ['id' => $id], 201);
        });

        // GET /api/conversations/{conv_id}/messages
        $app->get('/api/conversations/{conv_id}/messages', function (Request $request, Response $response, array $args) {
            $user   = Security::getCurrentUser($request);
            $convId = $args['conv_id'];
            $params = $request->getQueryParams();
            $limit  = min((int)($params['limit'] ?? 50), 200);
            $offset = (int)($params['offset'] ?? 0);

            // Verify participant
            $isMember = Database::fetchOne('SELECT 1 FROM conversation_participants WHERE conversation_id=? AND user_id=?', [$convId, $user['id']]);
            if (!$isMember) return self::error($response, 'Keine Berechtigung', 403);

            $messages = Database::fetchAll(
                'SELECT m.*,u.first_name,u.last_name,u.profile_image,u.color FROM messages m LEFT JOIN users u ON u.id=m.user_id WHERE m.conversation_id=? ORDER BY m.created_at DESC LIMIT ? OFFSET ?',
                [$convId, $limit, $offset]
            );

            foreach ($messages as &$msg) {
                $msg['reactions'] = Database::fetchAll('SELECT * FROM message_reactions WHERE message_id=?', [$msg['id']]);
                // Mark as read
                if ($msg['user_id'] !== $user['id']) {
                    Database::execute('INSERT IGNORE INTO message_reads (message_id,user_id,read_at) VALUES (?,?,?)', [$msg['id'], $user['id'], Helpers::now()]);
                }
            }

            return self::json($response, array_reverse($messages));
        });

        // POST /api/conversations/{conv_id}/messages
        $app->post('/api/conversations/{conv_id}/messages', function (Request $request, Response $response, array $args) {
            $user   = Security::getCurrentUser($request);
            $convId = $args['conv_id'];
            $body   = (array) $request->getParsedBody();

            $isMember = Database::fetchOne('SELECT 1 FROM conversation_participants WHERE conversation_id=? AND user_id=?', [$convId, $user['id']]);
            if (!$isMember) return self::error($response, 'Keine Berechtigung', 403);

            $id = Helpers::uuid();
            Database::insert('messages', [
                'id'              => $id,
                'conversation_id' => $convId,
                'user_id'         => $user['id'],
                'content'         => $body['content'] ?? '',
                'type'            => $body['type'] ?? 'text',
                'reply_to'        => $body['reply_to'] ?? null,
                'created_at'      => Helpers::now(),
            ]);

            // Update conversation timestamp
            Database::update('conversations', ['updated_at' => Helpers::now()], ['id' => $convId]);

            // Notify other participants
            $participants = Database::fetchAll(
                'SELECT u.* FROM conversation_participants cp JOIN users u ON u.id=cp.user_id WHERE cp.conversation_id=? AND cp.user_id!=?',
                [$convId, $user['id']]
            );
            $senderName = Helpers::userName($user);
            $preview    = mb_substr($body['content'] ?? '', 0, 100);
            foreach ($participants as $p) {
                Helpers::createNotification($p['id'], 'chat_message', "Neue Nachricht von $senderName", $preview, '/chat/' . $convId);
                // Email only if offline (last_active > 5 min ago)
                $lastActive = $p['last_active'] ? strtotime($p['last_active']) : 0;
                if ((time() - $lastActive) > 300 && Helpers::shouldSendEmail($p, 'chat_message')) {
                    EmailService::sendChatNotificationEmail($p['email'], Helpers::userName($p), $senderName, $body['content'] ?? '');
                }
            }

            // Achievement
            Gamification::checkAndAwardAchievement($user['id'], 'communicator');

            $msg = Database::fetchOne('SELECT m.*,u.first_name,u.last_name,u.profile_image,u.color FROM messages m LEFT JOIN users u ON u.id=m.user_id WHERE m.id=?', [$id]);
            return self::json($response, $msg, 201);
        });

        // PUT /api/messages/{message_id}  (edit)
        $app->put('/api/messages/{message_id}', function (Request $request, Response $response, array $args) {
            $user  = Security::getCurrentUser($request);
            $msgId = $args['message_id'];
            $body  = (array) $request->getParsedBody();

            $msg = Database::fetchOne('SELECT * FROM messages WHERE id=?', [$msgId]);
            if (!$msg) return self::error($response, 'Nachricht nicht gefunden', 404);
            if ($msg['user_id'] !== $user['id']) return self::error($response, 'Keine Berechtigung', 403);

            Database::update('messages', ['content' => $body['content'] ?? $msg['content'], 'is_edited' => 1, 'edited_at' => Helpers::now()], ['id' => $msgId]);
            return self::json($response, Database::fetchOne('SELECT * FROM messages WHERE id=?', [$msgId]));
        });

        // DELETE /api/messages/{message_id}
        $app->delete('/api/messages/{message_id}', function (Request $request, Response $response, array $args) {
            $user  = Security::getCurrentUser($request);
            $msgId = $args['message_id'];
            $msg   = Database::fetchOne('SELECT * FROM messages WHERE id=?', [$msgId]);
            if (!$msg) return self::error($response, 'Nicht gefunden', 404);
            if ($msg['user_id'] !== $user['id'] && $user['role'] !== 'CHEF') return self::error($response, 'Keine Berechtigung', 403);

            Database::update('messages', ['is_deleted' => 1, 'deleted_at' => Helpers::now(), 'content' => ''], ['id' => $msgId]);
            return self::json($response, ['message' => 'Nachricht gelöscht']);
        });

        // POST /api/messages/{message_id}/reactions
        $app->post('/api/messages/{message_id}/reactions', function (Request $request, Response $response, array $args) {
            $user  = Security::getCurrentUser($request);
            $msgId = $args['message_id'];
            $body  = (array) $request->getParsedBody();
            $emoji = $body['emoji'] ?? '👍';

            // Toggle reaction
            $existing = Database::fetchOne('SELECT id FROM message_reactions WHERE message_id=? AND user_id=? AND emoji=?', [$msgId, $user['id'], $emoji]);
            if ($existing) {
                Database::delete('message_reactions', ['id' => $existing['id']]);
                return self::json($response, ['action' => 'removed']);
            }

            Database::insert('message_reactions', ['id' => Helpers::uuid(), 'message_id' => $msgId, 'user_id' => $user['id'], 'emoji' => $emoji, 'created_at' => Helpers::now()]);
            Gamification::checkAndAwardAchievement($user['id'], 'motivator');
            return self::json($response, ['action' => 'added'], 201);
        });

        // POST /api/conversations/{conv_id}/upload
        $app->post('/api/conversations/{conv_id}/upload', function (Request $request, Response $response, array $args) {
            $user   = Security::getCurrentUser($request);
            $convId = $args['conv_id'];
            $files  = $request->getUploadedFiles();

            if (empty($files['file'])) return self::error($response, 'Keine Datei', 400);

            $stored  = Storage::store($files['file'], 'chat');
            $msgType = str_starts_with($stored['type'], 'image/') ? 'image' : (str_starts_with($stored['type'], 'audio/') ? 'voice' : 'file');

            $id = Helpers::uuid();
            Database::insert('messages', [
                'id'              => $id,
                'conversation_id' => $convId,
                'user_id'         => $user['id'],
                'content'         => '',
                'type'            => $msgType,
                'file_url'        => $stored['url'],
                'file_name'       => $stored['name'],
                'file_type'       => $stored['type'],
                'created_at'      => Helpers::now(),
            ]);
            Database::update('conversations', ['updated_at' => Helpers::now()], ['id' => $convId]);

            return self::json($response, Database::fetchOne('SELECT * FROM messages WHERE id=?', [$id]), 201);
        });

        // PUT /api/conversations/{conv_id}/archive
        $app->put('/api/conversations/{conv_id}/archive', function (Request $request, Response $response, array $args) {
            Security::getCurrentUser($request);
            Database::update('conversations', ['is_archived' => 1], ['id' => $args['conv_id']]);
            return self::json($response, ['message' => 'Archiviert']);
        });

        // PUT /api/conversations/{conv_id}/unarchive
        $app->put('/api/conversations/{conv_id}/unarchive', function (Request $request, Response $response, array $args) {
            Security::getCurrentUser($request);
            Database::update('conversations', ['is_archived' => 0], ['id' => $args['conv_id']]);
            return self::json($response, ['message' => 'Wiederhergestellt']);
        });

        // PUT /api/users/me/presence  (update last_active)
        $app->put('/api/users/me/presence', function (Request $request, Response $response) {
            $user = Security::getCurrentUser($request);
            Database::update('users', ['last_active' => Helpers::now()], ['id' => $user['id']]);
            return self::json($response, ['status' => 'online']);
        });

        // GET /api/chat/settings
        $app->get('/api/chat/settings', function (Request $request, Response $response) {
            $user     = Security::getCurrentUser($request);
            $settings = Helpers::jsonDecode($user['chat_settings'] ?? null, []);
            return self::json($response, array_merge(['theme' => $user['chat_theme'] ?? 'default'], $settings));
        });

        // PUT /api/chat/settings
        $app->put('/api/chat/settings', function (Request $request, Response $response) {
            $user = Security::getCurrentUser($request);
            $body = (array) $request->getParsedBody();

            $updates = [];
            if (isset($body['theme'])) $updates['chat_theme'] = $body['theme'];

            $settingsFields = ['notifications_enabled','sound_enabled','bubble_position','sidebar_open'];
            $settings = Helpers::jsonDecode($user['chat_settings'] ?? null, []);
            foreach ($settingsFields as $f) {
                if (array_key_exists($f, $body)) $settings[$f] = $body[$f];
            }
            $updates['chat_settings'] = json_encode($settings);

            Database::update('users', $updates, ['id' => $user['id']]);
            return self::json($response, ['message' => 'Einstellungen gespeichert']);
        });

        // POST /api/conversations/{conv_id}/video-call
        $app->post('/api/conversations/{conv_id}/video-call', function (Request $request, Response $response, array $args) {
            $user    = Security::getCurrentUser($request);
            $convId  = $args['conv_id'];
            $callId  = Helpers::uuid();

            $participants = Database::fetchAll(
                'SELECT u.* FROM conversation_participants cp JOIN users u ON u.id=cp.user_id WHERE cp.conversation_id=? AND cp.user_id!=?',
                [$convId, $user['id']]
            );
            foreach ($participants as $p) {
                Helpers::createNotification($p['id'], 'video_call', 'Eingehender Videoanruf', Helpers::userName($user) . ' ruft an...', '/chat/' . $convId . '?call=' . $callId);
            }

            return self::json($response, ['call_id' => $callId]);
        });
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
