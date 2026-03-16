<?php

declare(strict_types=1);

namespace AgencyOS\Routes;

use AgencyOS\Core\{Database, Security, Helpers};
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;

class QuestionnaireRoutes
{
    public static function register(App $app): void
    {
        // GET /api/questionnaire/me
        $app->get('/api/questionnaire/me', function (Request $request, Response $response) {
            $user = Security::getCurrentUser($request);
            $q    = Database::fetchOne('SELECT * FROM questionnaires WHERE user_id=?', [$user['id']]);

            if (!$q) {
                return self::json($response, ['completed' => false, 'user_id' => $user['id']]);
            }

            // Decode JSON fields
            foreach (['branches','strengths','weaknesses','work_values','learning_goals','social_media','favorite_profiles','hobbies'] as $f) {
                $q[$f] = Helpers::jsonDecode($q[$f] ?? null, []);
            }
            $q['completed'] = true;
            return self::json($response, $q);
        });

        // POST /api/questionnaire
        $app->post('/api/questionnaire', function (Request $request, Response $response) {
            $user = Security::getCurrentUser($request);
            $body = (array) $request->getParsedBody();

            $jsonFields = ['branches','strengths','weaknesses','work_values','learning_goals','social_media','favorite_profiles','hobbies'];
            $data = ['updated_at' => Helpers::now()];
            foreach ($jsonFields as $f) {
                if (array_key_exists($f, $body)) {
                    $data[$f] = is_array($body[$f]) ? json_encode($body[$f]) : $body[$f];
                }
            }

            $existing = Database::fetchOne('SELECT id FROM questionnaires WHERE user_id=?', [$user['id']]);
            if ($existing) {
                Database::update('questionnaires', $data, ['user_id' => $user['id']]);
            } else {
                $data['id']           = Helpers::uuid();
                $data['user_id']      = $user['id'];
                $data['submitted_at'] = Helpers::now();
                Database::insert('questionnaires', $data);
            }

            return self::json($response, ['message' => 'Fragebogen gespeichert', 'completed' => true]);
        });

        // GET /api/admin/questionnaires  (CHEF only)
        $app->get('/api/admin/questionnaires', function (Request $request, Response $response) {
            $user = Security::getCurrentUser($request);
            Security::requireRole($user, 'CHEF');

            $questionnaires = Database::fetchAll('SELECT q.*,u.first_name,u.last_name,u.email,u.profile_image,u.color,u.role FROM questionnaires q JOIN users u ON u.id=q.user_id ORDER BY q.submitted_at DESC');

            foreach ($questionnaires as &$q) {
                foreach (['branches','strengths','weaknesses','work_values','learning_goals','social_media','favorite_profiles','hobbies'] as $f) {
                    $q[$f] = Helpers::jsonDecode($q[$f] ?? null, []);
                }
            }
            return self::json($response, $questionnaires);
        });
    }

    private static function json(Response $response, mixed $data, int $status = 200): Response
    {
        $response->getBody()->write(json_encode($data, JSON_UNESCAPED_UNICODE));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($status);
    }
}
