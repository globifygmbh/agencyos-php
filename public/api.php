<?php

declare(strict_types=1);

// ============================================================
// AgencyOS – Slim API Entry Point
// Wird von public/index.php für /api/* aufgerufen
// ============================================================

use AgencyOS\Core\{Config, Database};
use Slim\Factory\AppFactory;
use Slim\Exception\HttpNotFoundException;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

// Autoload + Config bereits geladen durch index.php
// Falls direkt aufgerufen:
if (!class_exists(\AgencyOS\Core\Config::class)) {
    require dirname(__DIR__) . '/vendor/autoload.php';
    \AgencyOS\Core\Config::load();
}

$app = AppFactory::create();

// ============================================================
// Error Handler
// ============================================================
$errorMiddleware = $app->addErrorMiddleware(
    Config::get('APP_DEBUG', 'false') === 'true',
    true,
    true
);

$errorMiddleware->setDefaultErrorHandler(function (
    Request $request,
    \Throwable $exception,
    bool $displayErrorDetails
) use ($app): Response {
    $statusCode = 500;
    $message    = 'Interner Serverfehler';

    if ($exception instanceof HttpNotFoundException) {
        $statusCode = 404;
        $message    = 'Route nicht gefunden';
    } elseif ($exception instanceof \RuntimeException) {
        $code = $exception->getCode();
        if ($code >= 400 && $code < 600) {
            $statusCode = $code;
        }
        $message = $exception->getMessage();
    }

    $response = $app->getResponseFactory()->createResponse($statusCode);
    $response->getBody()->write(json_encode([
        'detail' => $message,
    ], JSON_UNESCAPED_UNICODE));
    return $response->withHeader('Content-Type', 'application/json');
});

// ============================================================
// Alle Route-Klassen registrieren
// ============================================================
\AgencyOS\Routes\AuthRoutes::register($app);
\AgencyOS\Routes\UserRoutes::register($app);
\AgencyOS\Routes\DashboardRoutes::register($app);
\AgencyOS\Routes\AdminRoutes::register($app);
\AgencyOS\Routes\TaskRoutes::register($app);
\AgencyOS\Routes\ProjectRoutes::register($app);
\AgencyOS\Routes\CustomerRoutes::register($app);
\AgencyOS\Routes\TimeEntryRoutes::register($app);
\AgencyOS\Routes\CalendarRoutes::register($app);
\AgencyOS\Routes\VacationRoutes::register($app);
\AgencyOS\Routes\ChatRoutes::register($app);
\AgencyOS\Routes\AchievementRoutes::register($app);
\AgencyOS\Routes\ChallengeRoutes::register($app);
\AgencyOS\Routes\BenefitRoutes::register($app);
\AgencyOS\Routes\FormRoutes::register($app);
\AgencyOS\Routes\QuestionnaireRoutes::register($app);
\AgencyOS\Routes\AiAssistantRoutes::register($app);
\AgencyOS\Routes\MiscRoutes::register($app);
\AgencyOS\Routes\RecurringTaskRoutes::register($app);

$app->run();
