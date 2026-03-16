<?php

declare(strict_types=1);

// ============================================================
// AgencyOS – Single Entry Point
// Dient sowohl die PHP REST API (/api/*) als auch
// das React-Frontend (alle anderen Pfade → index.html).
// Eine App, eine URL, kein CORS-Problem.
// ============================================================

use AgencyOS\Core\Config;
use AgencyOS\Routes\{
    AuthRoutes, UserRoutes, DashboardRoutes, AdminRoutes,
    TaskRoutes, ProjectRoutes, CustomerRoutes, TimeEntryRoutes,
    CalendarRoutes, VacationRoutes, ChatRoutes,
    AchievementRoutes, ChallengeRoutes, BenefitRoutes,
    FormRoutes, QuestionnaireRoutes, AiAssistantRoutes,
    MiscRoutes, RecurringTaskRoutes
};

use Slim\Factory\AppFactory;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface;

require dirname(__DIR__) . '/vendor/autoload.php';

Config::load();

// ------------------------------------------------------------
// Pfad ermitteln
// ------------------------------------------------------------
$uri = $_SERVER['REQUEST_URI'] ?? '/';
$path = parse_url($uri, PHP_URL_PATH);

// ------------------------------------------------------------
// FRONTEND BEDIENEN — alle Pfade außer /api/*
// Wenn eine React-Build vorhanden ist, wird index.html gesendet.
// ------------------------------------------------------------
$frontendBuild = dirname(__DIR__) . '/frontend/build';
$isApiRequest  = str_starts_with($path, '/api') || $path === '/';

if (!$isApiRequest) {
    // Direkte Datei? (CSS, JS, Bilder aus React-Build)
    $file = $frontendBuild . $path;
    if (file_exists($file) && is_file($file)) {
        $ext  = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        $mime = match($ext) {
            'js'    => 'application/javascript',
            'css'   => 'text/css',
            'html'  => 'text/html; charset=utf-8',
            'json'  => 'application/json',
            'png'   => 'image/png',
            'jpg','jpeg' => 'image/jpeg',
            'svg'   => 'image/svg+xml',
            'ico'   => 'image/x-icon',
            'woff'  => 'font/woff',
            'woff2' => 'font/woff2',
            'ttf'   => 'font/ttf',
            'webmanifest' => 'application/manifest+json',
            default => 'application/octet-stream',
        };
        header('Content-Type: ' . $mime);
        header('Cache-Control: public, max-age=31536000');
        readfile($file);
        exit;
    }

    // Alles andere → React index.html (SPA-Routing)
    $indexHtml = $frontendBuild . '/index.html';
    if (file_exists($indexHtml)) {
        header('Content-Type: text/html; charset=utf-8');
        readfile($indexHtml);
        exit;
    }

    // Kein Frontend-Build vorhanden → hilfreiche Nachricht
    header('Content-Type: application/json');
    echo json_encode([
        'message' => 'AgencyOS läuft. Kein Frontend-Build gefunden.',
        'hint'    => 'Baue das React-Frontend und lege die Dateien in /frontend/build/ ab.',
        'api'     => '/api/health',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// ------------------------------------------------------------
// SLIM APP für /api/*
// ------------------------------------------------------------
$app = AppFactory::create();

$app->addRoutingMiddleware();

// ---- Fehlerbehandlung ----
$errorMiddleware = $app->addErrorMiddleware(
    displayErrorDetails: Config::get('APP_ENV', 'production') !== 'production',
    logErrors:           true,
    logErrorDetails:     true
);

$errorMiddleware->setDefaultErrorHandler(function (
    Request $request,
    \Throwable $exception,
    bool $displayErrorDetails
) use ($app): Response {
    $response = $app->getResponseFactory()->createResponse();
    $code     = 500;
    $msg      = 'Internal Server Error';

    if ($exception instanceof \RuntimeException) {
        $code = in_array($exception->getCode(), [400, 401, 403, 404, 409, 410, 413, 422, 503])
            ? $exception->getCode() : 400;
        $msg  = $exception->getMessage();
    }

    $response->getBody()->write(json_encode(['detail' => $msg], JSON_UNESCAPED_UNICODE));
    return $response->withHeader('Content-Type', 'application/json')->withStatus((int) $code);
});

// ---- Body Parser (JSON + Form) ----
$app->addBodyParsingMiddleware();

// ---- Kein CORS nötig — gleiche Origin! ----
// (Falls externes Frontend trotzdem gebraucht wird, hier einkommentieren)
/*
$app->add(function (Request $request, RequestHandlerInterface $handler): Response {
    $response = $handler->handle($request);
    return $response
        ->withHeader('Access-Control-Allow-Origin', '*')
        ->withHeader('Access-Control-Allow-Methods', 'GET,POST,PUT,DELETE,OPTIONS')
        ->withHeader('Access-Control-Allow-Headers', 'Content-Type,Authorization');
});
*/

// ---- Health / Root ----
$app->get('/', function (Request $request, Response $response): Response {
    $response->getBody()->write(json_encode(['status' => 'ok', 'app' => 'AgencyOS PHP']));
    return $response->withHeader('Content-Type', 'application/json');
});

$app->get('/api/health', function (Request $request, Response $response): Response {
    $response->getBody()->write(json_encode(['status' => 'ok', 'app' => 'AgencyOS PHP', 'time' => date('c')]));
    return $response->withHeader('Content-Type', 'application/json');
});

// ---- Statische Uploads ausliefern ----
$app->get('/api/uploads/{subdir}/{filename}', function (Request $request, Response $response, array $args): Response {
    $subdir  = preg_replace('/[^a-zA-Z0-9_\-]/', '', $args['subdir']);
    $fname   = basename($args['filename']);
    $path    = AgencyOS\Core\Config::uploadDir() . '/' . $subdir . '/' . $fname;

    if (!file_exists($path)) {
        $response->getBody()->write(json_encode(['detail' => 'Datei nicht gefunden']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
    }
    $mime = AgencyOS\Core\Storage::getMimeType($path);
    $response->getBody()->write((string) file_get_contents($path));
    return $response->withHeader('Content-Type', $mime)->withHeader('Cache-Control', 'public, max-age=86400');
});

$app->get('/api/files/{filename}', function (Request $request, Response $response, array $args): Response {
    $fname = basename($args['filename']);
    $path  = AgencyOS\Core\Config::uploadDir() . '/files/' . $fname;
    if (!file_exists($path)) {
        $response->getBody()->write(json_encode(['detail' => 'Datei nicht gefunden']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
    }
    $mime = AgencyOS\Core\Storage::getMimeType($path);
    $response->getBody()->write((string) file_get_contents($path));
    return $response->withHeader('Content-Type', $mime);
});

// ---- Alle Route-Module registrieren ----
AuthRoutes::register($app);
UserRoutes::register($app);
DashboardRoutes::register($app);
AdminRoutes::register($app);
TaskRoutes::register($app);
ProjectRoutes::register($app);
CustomerRoutes::register($app);
TimeEntryRoutes::register($app);
CalendarRoutes::register($app);
VacationRoutes::register($app);
ChatRoutes::register($app);
AchievementRoutes::register($app);
ChallengeRoutes::register($app);
BenefitRoutes::register($app);
FormRoutes::register($app);
QuestionnaireRoutes::register($app);
AiAssistantRoutes::register($app);
MiscRoutes::register($app);
RecurringTaskRoutes::register($app);

$app->run();
