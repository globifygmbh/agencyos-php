<?php

declare(strict_types=1);

namespace AgencyOS\Routes;

use AgencyOS\Core\{Database, Security, Helpers, EmailService, Storage};
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;

class FormRoutes
{
    public static function register(App $app): void
    {
        // ---- SHOOTING DOCS ----

        // GET /api/shooting-docs
        $app->get('/api/shooting-docs', function (Request $request, Response $response) {
            $user   = Security::getCurrentUser($request);
            $params = $request->getQueryParams();

            $sql   = 'SELECT sd.*,c.name as customer_name FROM shooting_docs sd LEFT JOIN customers c ON c.id=sd.customer_id WHERE 1=1';
            $binds = [];

            if (!in_array($user['role'], ['CHEF', 'ACCOUNT_MANAGER'])) {
                $sql .= ' AND sd.user_id=?';
                $binds[] = $user['id'];
            }
            if (!empty($params['customer_id'])) { $sql .= ' AND sd.customer_id=?'; $binds[] = $params['customer_id']; }
            $sql .= ' ORDER BY sd.date DESC,sd.created_at DESC';

            $docs = Database::fetchAll($sql, $binds);
            foreach ($docs as &$doc) {
                $doc['team_members']    = Helpers::jsonDecode($doc['team_members'], []);
                $doc['social_contacts'] = Helpers::jsonDecode($doc['social_contacts'], []);
            }
            return self::json($response, $docs);
        });

        // POST /api/shooting-docs
        $app->post('/api/shooting-docs', function (Request $request, Response $response) {
            $user = Security::getCurrentUser($request);
            $body = (array) $request->getParsedBody();
            if (empty($body['title'])) return self::error($response, 'Titel erforderlich', 400);

            $id = Helpers::uuid();
            Database::insert('shooting_docs', [
                'id'              => $id,
                'user_id'         => $user['id'],
                'customer_id'     => $body['customer_id'] ?? null,
                'title'           => $body['title'],
                'date'            => $body['date'] ?? null,
                'location'        => $body['location'] ?? '',
                'start_time'      => $body['start_time'] ?? null,
                'end_time'        => $body['end_time'] ?? null,
                'description'     => $body['description'] ?? null,
                'team_members'    => isset($body['team_members']) ? json_encode($body['team_members']) : null,
                'social_contacts' => isset($body['social_contacts']) ? json_encode($body['social_contacts']) : null,
                'status'          => $body['status'] ?? 'draft',
                'created_by'      => $user['id'],
                'created_at'      => Helpers::now(),
                'updated_at'      => Helpers::now(),
            ]);
            return self::json($response, Database::fetchOne('SELECT * FROM shooting_docs WHERE id=?', [$id]), 201);
        });

        // GET /api/shooting-docs/{doc_id}
        $app->get('/api/shooting-docs/{doc_id}', function (Request $request, Response $response, array $args) {
            Security::getCurrentUser($request);
            $doc = Database::fetchOne('SELECT * FROM shooting_docs WHERE id=?', [$args['doc_id']]);
            if (!$doc) return self::error($response, 'Dokument nicht gefunden', 404);
            $doc['team_members']    = Helpers::jsonDecode($doc['team_members'], []);
            $doc['social_contacts'] = Helpers::jsonDecode($doc['social_contacts'], []);
            return self::json($response, $doc);
        });

        // PUT /api/shooting-docs/{doc_id}
        $app->put('/api/shooting-docs/{doc_id}', function (Request $request, Response $response, array $args) {
            $user    = Security::getCurrentUser($request);
            $body    = (array) $request->getParsedBody();
            $allowed = ['title','date','location','start_time','end_time','description','status','customer_id'];
            $updates = ['updated_at' => Helpers::now()];
            foreach ($allowed as $f) {
                if (array_key_exists($f, $body)) $updates[$f] = $body[$f];
            }
            if (isset($body['team_members']))    $updates['team_members']    = json_encode($body['team_members']);
            if (isset($body['social_contacts'])) $updates['social_contacts'] = json_encode($body['social_contacts']);
            Database::update('shooting_docs', $updates, ['id' => $args['doc_id']]);
            return self::json($response, Database::fetchOne('SELECT * FROM shooting_docs WHERE id=?', [$args['doc_id']]));
        });

        // DELETE /api/shooting-docs/{doc_id}
        $app->delete('/api/shooting-docs/{doc_id}', function (Request $request, Response $response, array $args) {
            $user = Security::getCurrentUser($request);
            Security::requireRole($user, ['CHEF', 'ACCOUNT_MANAGER']);
            Database::delete('shooting_docs', ['id' => $args['doc_id']]);
            return self::json($response, ['message' => 'Dokument gelöscht']);
        });

        // ---- EXPENSE REPORTS ----

        // GET /api/expense-reports
        $app->get('/api/expense-reports', function (Request $request, Response $response) {
            $user   = Security::getCurrentUser($request);
            $params = $request->getQueryParams();

            $sql   = 'SELECT er.*,u.first_name,u.last_name FROM expense_reports er JOIN users u ON u.id=er.user_id WHERE 1=1';
            $binds = [];

            if (!in_array($user['role'], ['CHEF', 'ACCOUNT_MANAGER'])) {
                $sql .= ' AND er.user_id=?';
                $binds[] = $user['id'];
            }
            if (!empty($params['status'])) { $sql .= ' AND er.status=?'; $binds[] = $params['status']; }
            $sql .= ' ORDER BY er.created_at DESC';

            $reports = Database::fetchAll($sql, $binds);
            foreach ($reports as &$r) {
                $r['items'] = Helpers::jsonDecode($r['items'], []);
            }
            return self::json($response, $reports);
        });

        // POST /api/expense-reports
        $app->post('/api/expense-reports', function (Request $request, Response $response) {
            $user = Security::getCurrentUser($request);
            $body = (array) $request->getParsedBody();
            if (empty($body['title'])) return self::error($response, 'Titel erforderlich', 400);

            $id = Helpers::uuid();
            Database::insert('expense_reports', [
                'id'          => $id,
                'user_id'     => $user['id'],
                'title'       => $body['title'],
                'description' => $body['description'] ?? null,
                'amount'      => (float) ($body['amount'] ?? 0),
                'currency'    => $body['currency'] ?? 'EUR',
                'category'    => $body['category'] ?? 'general',
                'date'        => $body['date'] ?? null,
                'items'       => isset($body['items']) ? json_encode($body['items']) : null,
                'status'      => 'submitted',
                'created_at'  => Helpers::now(),
                'updated_at'  => Helpers::now(),
            ]);

            // Notify admins
            $admins = Database::fetchAll('SELECT id FROM users WHERE role=? AND is_active=1', ['CHEF']);
            foreach ($admins as $admin) {
                Helpers::createNotification($admin['id'], 'expense_submitted', 'Spesenbericht eingereicht', Helpers::userName($user) . ' hat einen Spesenbericht eingereicht', '/admin/expenses');
            }

            return self::json($response, Database::fetchOne('SELECT * FROM expense_reports WHERE id=?', [$id]), 201);
        });

        // PUT /api/expense-reports/{report_id}
        $app->put('/api/expense-reports/{report_id}', function (Request $request, Response $response, array $args) {
            $user     = Security::getCurrentUser($request);
            $body     = (array) $request->getParsedBody();
            $reportId = $args['report_id'];

            $report = Database::fetchOne('SELECT * FROM expense_reports WHERE id=?', [$reportId]);
            if (!$report) return self::error($response, 'Bericht nicht gefunden', 404);
            if ($report['user_id'] !== $user['id'] && $user['role'] !== 'CHEF') return self::error($response, 'Keine Berechtigung', 403);
            if ($report['status'] !== 'submitted' && $user['role'] !== 'CHEF') return self::error($response, 'Bereits bearbeitet', 400);

            $allowed = ['title','description','amount','currency','category','date'];
            $updates = ['updated_at' => Helpers::now()];
            foreach ($allowed as $f) {
                if (array_key_exists($f, $body)) $updates[$f] = $body[$f];
            }
            if (isset($body['items'])) $updates['items'] = json_encode($body['items']);

            Database::update('expense_reports', $updates, ['id' => $reportId]);
            return self::json($response, Database::fetchOne('SELECT * FROM expense_reports WHERE id=?', [$reportId]));
        });

        // DELETE /api/expense-reports/{report_id}
        $app->delete('/api/expense-reports/{report_id}', function (Request $request, Response $response, array $args) {
            $user     = Security::getCurrentUser($request);
            $reportId = $args['report_id'];
            $report   = Database::fetchOne('SELECT * FROM expense_reports WHERE id=?', [$reportId]);
            if (!$report) return self::error($response, 'Nicht gefunden', 404);

            $canDelete = ($user['role'] === 'CHEF') || ($report['user_id'] === $user['id'] && $report['status'] === 'submitted');
            if (!$canDelete) return self::error($response, 'Keine Berechtigung', 403);

            if ($report['receipt_url']) Storage::delete($report['receipt_url']);
            Database::delete('expense_reports', ['id' => $reportId]);
            return self::json($response, ['message' => 'Bericht gelöscht']);
        });

        // PUT /api/expense-reports/{report_id}/approve
        $app->put('/api/expense-reports/{report_id}/approve', function (Request $request, Response $response, array $args) {
            $user     = Security::getCurrentUser($request);
            Security::requireRole($user, 'CHEF');
            $reportId = $args['report_id'];
            $body     = (array) $request->getParsedBody();
            $comment  = $body['comment'] ?? '';

            $report = Database::fetchOne('SELECT * FROM expense_reports WHERE id=?', [$reportId]);
            if (!$report) return self::error($response, 'Nicht gefunden', 404);

            Database::update('expense_reports', ['status' => 'approved', 'admin_comment' => $comment, 'approved_by' => $user['id'], 'approved_at' => Helpers::now(), 'updated_at' => Helpers::now()], ['id' => $reportId]);

            $employee = Database::fetchOne('SELECT * FROM users WHERE id=?', [$report['user_id']]);
            if ($employee) {
                Helpers::createNotification($employee['id'], 'expense_approved', '✅ Spesenbericht genehmigt', $report['title'], '/expenses');
                if (Helpers::shouldSendEmail($employee, 'expense_approved')) {
                    EmailService::sendExpenseStatusEmail($employee['email'], Helpers::userName($employee), $report, 'approved', $comment);
                }
            }

            return self::json($response, Database::fetchOne('SELECT * FROM expense_reports WHERE id=?', [$reportId]));
        });

        // PUT /api/expense-reports/{report_id}/reject
        $app->put('/api/expense-reports/{report_id}/reject', function (Request $request, Response $response, array $args) {
            $user     = Security::getCurrentUser($request);
            Security::requireRole($user, 'CHEF');
            $reportId = $args['report_id'];
            $body     = (array) $request->getParsedBody();
            $comment  = $body['comment'] ?? '';

            $report = Database::fetchOne('SELECT * FROM expense_reports WHERE id=?', [$reportId]);
            if (!$report) return self::error($response, 'Nicht gefunden', 404);

            Database::update('expense_reports', ['status' => 'rejected', 'admin_comment' => $comment, 'rejected_at' => Helpers::now(), 'updated_at' => Helpers::now()], ['id' => $reportId]);

            $employee = Database::fetchOne('SELECT * FROM users WHERE id=?', [$report['user_id']]);
            if ($employee) {
                Helpers::createNotification($employee['id'], 'expense_rejected', '❌ Spesenbericht abgelehnt', $report['title'], '/expenses');
                if (Helpers::shouldSendEmail($employee, 'expense_rejected')) {
                    EmailService::sendExpenseStatusEmail($employee['email'], Helpers::userName($employee), $report, 'rejected', $comment);
                }
            }

            return self::json($response, Database::fetchOne('SELECT * FROM expense_reports WHERE id=?', [$reportId]));
        });

        // POST /api/expense-reports/{report_id}/receipt
        $app->post('/api/expense-reports/{report_id}/receipt', function (Request $request, Response $response, array $args) {
            $user     = Security::getCurrentUser($request);
            $reportId = $args['report_id'];
            $files    = $request->getUploadedFiles();
            if (empty($files['file'])) return self::error($response, 'Keine Datei', 400);

            $stored = Storage::store($files['file'], 'reports');
            Database::update('expense_reports', ['receipt_url' => $stored['url']], ['id' => $reportId]);
            return self::json($response, ['receipt_url' => $stored['url']]);
        });

        // GET /api/admin/forms-stats
        $app->get('/api/admin/forms-stats', function (Request $request, Response $response) {
            $user = Security::getCurrentUser($request);
            Security::requireRole($user, 'CHEF');

            $totalDocs      = (int)(Database::fetchOne('SELECT COUNT(*) as c FROM shooting_docs')['c'] ?? 0);
            $totalExpenses  = (int)(Database::fetchOne('SELECT COUNT(*) as c FROM expense_reports')['c'] ?? 0);
            $approvedExpenses = (int)(Database::fetchOne("SELECT COUNT(*) as c FROM expense_reports WHERE status='approved'")['c'] ?? 0);
            $totalAmount    = (float)(Database::fetchOne("SELECT COALESCE(SUM(amount),0) as s FROM expense_reports WHERE status='approved'")['s'] ?? 0);

            return self::json($response, [
                'shooting_docs'     => $totalDocs,
                'expense_reports'   => $totalExpenses,
                'approved_expenses' => $approvedExpenses,
                'total_approved_amount' => $totalAmount,
            ]);
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
