<?php

declare(strict_types=1);

// ============================================================
// AgencyOS – PHP Backend Entry Point
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
use Slim\Routing\RouteCollectorProxy;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface;

// ---- Autoload ----
require dirname(__DIR__) . '/vendor/autoload.php';

// ---- Load .env ----
Config::load();

// ---- Create Slim app ----
$app = AppFactory::create();

// ---- Error handling ----
$app->addRoutingMiddleware();

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

// ---- CORS middleware ----
$app->add(function (Request $request, RequestHandlerInterface $handler): Response {
    $origin       = $request->getHeaderLine('Origin');
    $allowedOrigins = Config::corsOrigins();
    $allowOrigin  = in_array($origin, $allowedOrigins) ? $origin : ($allowedOrigins[0] ?? '*');

    if ($request->getMethod() === 'OPTIONS') {
        $response = new \Slim\Psr7\Response();
        return $response
            ->withHeader('Access-Control-Allow-Origin', $allowOrigin)
            ->withHeader('Access-Control-Allow-Methods', 'GET,POST,PUT,DELETE,PATCH,OPTIONS')
            ->withHeader('Access-Control-Allow-Headers', 'Content-Type,Authorization,X-Requested-With')
            ->withHeader('Access-Control-Allow-Credentials', 'true')
            ->withStatus(200);
    }

    $response = $handler->handle($request);
    return $response
        ->withHeader('Access-Control-Allow-Origin', $allowOrigin)
        ->withHeader('Access-Control-Allow-Methods', 'GET,POST,PUT,DELETE,PATCH,OPTIONS')
        ->withHeader('Access-Control-Allow-Headers', 'Content-Type,Authorization,X-Requested-With')
        ->withHeader('Access-Control-Allow-Credentials', 'true');
});

// ---- JSON body parser ----
$app->addBodyParsingMiddleware();

// ---- Health check ----
$app->get('/api/health', function (Request $request, Response $response): Response {
    $response->getBody()->write(json_encode(['status' => 'ok', 'app' => 'AgencyOS PHP']));
    return $response->withHeader('Content-Type', 'application/json');
});

$app->get('/', function (Request $request, Response $response): Response {
    $response->getBody()->write(json_encode(['status' => 'ok', 'app' => 'AgencyOS PHP']));
    return $response->withHeader('Content-Type', 'application/json');
});

// ---- File serving ----
$app->get('/api/uploads/{subdir}/{filename}', function (Request $request, Response $response, array $args): Response {
    $subdir   = preg_replace('/[^a-zA-Z0-9_\-]/', '', $args['subdir']);
    $filename = basename($args['filename']);
    $path     = AgencyOS\Core\Config::uploadDir() . '/' . $subdir . '/' . $filename;

    if (!file_exists($path)) {
        $response->getBody()->write(json_encode(['detail' => 'Datei nicht gefunden']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
    }

    $mime = AgencyOS\Core\Storage::getMimeType($path);
    $response->getBody()->write((string)file_get_contents($path));
    return $response
        ->withHeader('Content-Type', $mime)
        ->withHeader('Cache-Control', 'public, max-age=86400')
        ->withStatus(200);
});

$app->get('/api/files/{filename}', function (Request $request, Response $response, array $args): Response {
    $filename = basename($args['filename']);
    $path     = AgencyOS\Core\Config::uploadDir() . '/files/' . $filename;

    if (!file_exists($path)) {
        $response->getBody()->write(json_encode(['detail' => 'Datei nicht gefunden']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
    }

    $mime = AgencyOS\Core\Storage::getMimeType($path);
    $response->getBody()->write((string)file_get_contents($path));
    return $response->withHeader('Content-Type', $mime)->withStatus(200);
});

// ---- Register all routes ----
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

// ---- Run ----
$app->run();
