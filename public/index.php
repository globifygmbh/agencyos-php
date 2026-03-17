<?php
declare(strict_types=1);

// ============================================================
// AgencyOS – Einziger Entry Point
// /api/*  → PHP REST API (Slim)
// alles andere → PHP Views (Templates)
// ============================================================

require dirname(__DIR__) . '/vendor/autoload.php';

use AgencyOS\Core\{Config, Database, Security};

Config::load();

$uri    = $_SERVER['REQUEST_URI'] ?? '/';
$path   = strtok($uri, '?');

// ============================================================
// API-REQUESTS → Slim-App
// ============================================================
if (str_starts_with($path, '/api')) {
    require __DIR__ . '/api.php';
    exit;
}

// ============================================================
// VIEWS – PHP-Templates
// ============================================================

// Hilfsfunktion: aktuellen User aus Cookie laden
function getCurrentUser(): ?array {
    $token = $_COOKIE['agencyos_token'] ?? '';
    if (!$token) return null;
    return Security::getUserByToken($token);
}

// Login/Logout ohne Auth
if ($path === '/login') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $login    = trim($_POST['login'] ?? '');
        $password = $_POST['password'] ?? '';

        $user = Database::fetchOne(
            'SELECT * FROM `users` WHERE (`username`=? OR `email`=?) AND `is_active`=1',
            [$login, $login]
        );

        if ($user && Security::verifyPassword($password, $user['password_hash'])) {
            // Token erstellen & als Cookie setzen
            $token = Security::createAccessToken($user['id']);
            setcookie('agencyos_token', $token, [
                'expires'  => time() + 30 * 86400,
                'path'     => '/',
                'httponly' => true,
                'samesite' => 'Lax',
                'secure'   => isset($_SERVER['HTTPS']),
            ]);
            Database::update('users', ['last_login' => date('Y-m-d H:i:s')], ['id' => $user['id']]);
            header('Location: /');
            exit;
        }
        $loginError = 'Ungültige Anmeldedaten';
    }
    require dirname(__DIR__) . '/views/login.php';
    exit;
}

if ($path === '/logout') {
    $token = $_COOKIE['agencyos_token'] ?? '';
    if ($token) Security::revokeToken($token);
    setcookie('agencyos_token', '', ['expires' => time() - 3600, 'path' => '/']);
    header('Location: /login');
    exit;
}

// Alle anderen Seiten → Auth prüfen
$currentUser = getCurrentUser();
if (!$currentUser) {
    header('Location: /login');
    exit;
}

// Route → View
$viewMap = [
    '/'               => 'dashboard',
    '/dashboard'      => 'dashboard',
    '/tasks'          => 'tasks',
    '/projects'       => 'projects',
    '/customers'      => 'customers',
    '/time'           => 'time',
    '/calendar'       => 'calendar',
    '/chat'           => 'chat',
    '/vacation'       => 'vacation',
    '/achievements'   => 'achievements',
    '/benefits'       => 'benefits',
    '/forms'          => 'forms',
    '/ai'             => 'ai',
    '/team'           => 'team',
    '/admin'          => 'admin',
    '/profile'        => 'profile',
];

$viewName = $viewMap[$path] ?? null;

// Dynamische Routen (z.B. /projects/123)
if (!$viewName && preg_match('#^/projects/([^/]+)$#', $path, $m)) {
    $routeParam = $m[1]; $viewName = 'project_detail';
}
if (!$viewName && preg_match('#^/customers/([^/]+)$#', $path, $m)) {
    $routeParam = $m[1]; $viewName = 'customer_detail';
}

if (!$viewName) {
    http_response_code(404);
    echo '<!DOCTYPE html><html><body style="font:16px sans-serif;padding:40px"><h1>404 – Seite nicht gefunden</h1><a href="/">← Zurück</a></body></html>';
    exit;
}

$viewFile = dirname(__DIR__) . '/views/' . $viewName . '.php';
if (!file_exists($viewFile)) {
    http_response_code(404);
    echo '404 View nicht gefunden: ' . htmlspecialchars($viewName);
    exit;
}

require $viewFile;
