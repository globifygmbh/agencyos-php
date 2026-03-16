<?php

declare(strict_types=1);

namespace AgencyOS\Routes;

use AgencyOS\Core\{Database, Security, Helpers, Storage};
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;

class BenefitRoutes
{
    public static function register(App $app): void
    {
        // GET /api/benefits  (active, non-expired)
        $app->get('/api/benefits', function (Request $request, Response $response) {
            Security::getCurrentUser($request);
            $today    = date('Y-m-d');
            $benefits = Database::fetchAll(
                "SELECT * FROM benefits WHERE is_active=1 AND (valid_until IS NULL OR valid_until >= ?) ORDER BY title ASC",
                [$today]
            );
            return self::json($response, $benefits);
        });

        // GET /api/benefits/all  (admin – all regardless of status)
        $app->get('/api/benefits/all', function (Request $request, Response $response) {
            $user = Security::getCurrentUser($request);
            Security::requireRole($user, 'CHEF');
            return self::json($response, Database::fetchAll('SELECT * FROM benefits ORDER BY created_at DESC'));
        });

        // POST /api/benefits
        $app->post('/api/benefits', function (Request $request, Response $response) {
            $user = Security::getCurrentUser($request);
            Security::requireRole($user, 'CHEF');
            $body = (array) $request->getParsedBody();

            if (empty($body['title'])) return self::error($response, 'Titel erforderlich', 400);

            $id = Helpers::uuid();
            Database::insert('benefits', [
                'id'          => $id,
                'title'       => $body['title'],
                'description' => $body['description'] ?? null,
                'discount'    => $body['discount'] ?? '',
                'category'    => $body['category'] ?? 'general',
                'location'    => $body['location'] ?? '',
                'terms'       => $body['terms'] ?? null,
                'valid_from'  => $body['valid_from'] ?? null,
                'valid_until' => $body['valid_until'] ?? null,
                'is_active'   => 1,
                'created_by'  => $user['id'],
                'created_at'  => Helpers::now(),
                'updated_at'  => Helpers::now(),
            ]);
            return self::json($response, Database::fetchOne('SELECT * FROM benefits WHERE id=?', [$id]), 201);
        });

        // PUT /api/benefits/{benefit_id}
        $app->put('/api/benefits/{benefit_id}', function (Request $request, Response $response, array $args) {
            $user = Security::getCurrentUser($request);
            Security::requireRole($user, 'CHEF');
            $body = (array) $request->getParsedBody();
            $bid  = $args['benefit_id'];

            $allowed = ['title','description','discount','category','location','terms','valid_from','valid_until','is_active'];
            $updates = ['updated_at' => Helpers::now()];
            foreach ($allowed as $f) {
                if (array_key_exists($f, $body)) $updates[$f] = $body[$f];
            }
            Database::update('benefits', $updates, ['id' => $bid]);
            return self::json($response, Database::fetchOne('SELECT * FROM benefits WHERE id=?', [$bid]));
        });

        // DELETE /api/benefits/{benefit_id}
        $app->delete('/api/benefits/{benefit_id}', function (Request $request, Response $response, array $args) {
            $user = Security::getCurrentUser($request);
            Security::requireRole($user, 'CHEF');
            $bid  = $args['benefit_id'];
            $b    = Database::fetchOne('SELECT logo_url FROM benefits WHERE id=?', [$bid]);
            if ($b && $b['logo_url']) Storage::delete($b['logo_url']);
            Database::delete('benefits', ['id' => $bid]);
            return self::json($response, ['message' => 'Vorteil gelöscht']);
        });

        // POST /api/benefits/{benefit_id}/logo
        $app->post('/api/benefits/{benefit_id}/logo', function (Request $request, Response $response, array $args) {
            $user  = Security::getCurrentUser($request);
            Security::requireRole($user, 'CHEF');
            $files = $request->getUploadedFiles();
            if (empty($files['file'])) return self::error($response, 'Keine Datei', 400);

            // Remove old logo
            $b = Database::fetchOne('SELECT logo_url FROM benefits WHERE id=?', [$args['benefit_id']]);
            if ($b && $b['logo_url']) Storage::delete($b['logo_url']);

            $stored = Storage::store($files['file'], 'benefits');
            Database::update('benefits', ['logo_url' => $stored['url']], ['id' => $args['benefit_id']]);
            return self::json($response, ['logo_url' => $stored['url']]);
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
