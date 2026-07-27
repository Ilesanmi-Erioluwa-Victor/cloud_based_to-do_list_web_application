<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/config/env.php';
require_once __DIR__ . '/../src/config/db.php';
require_once __DIR__ . '/../src/utils/response.php';
require_once __DIR__ . '/../src/utils/router.php';
require_once __DIR__ . '/../src/middleware/auth.php';
require_once __DIR__ . '/../src/middleware/csrf.php';
require_once __DIR__ . '/../src/middleware/rateLimit.php';

loadEnv();

session_start();

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = rtrim($uri, '/') ?: '/';

$routes = [
    // Serve frontend pages
    '/' => ['GET' => function () { require __DIR__ . '/views/app.php'; }],
    '/login' => ['GET' => function () { require __DIR__ . '/views/login.php'; }],
    '/register' => ['GET' => function () { require __DIR__ . '/views/register.php'; }],
    '/verify-email' => ['GET' => function () { require __DIR__ . '/views/verify-email.php'; }],
    '/reset-password' => ['GET' => function () { require __DIR__ . '/views/reset-password.php'; }],
    '/forgot-password' => ['GET' => function () { require __DIR__ . '/views/forgot-password.php'; }],

    // API routes
    '/api/auth/register' => ['POST' => function () { \CloudTasks\Controllers\AuthController::register(); }],
    '/api/auth/login' => ['POST' => function () { \CloudTasks\Controllers\AuthController::login(); }],
    '/api/auth/logout' => ['POST' => function () { \CloudTasks\Controllers\AuthController::logout(); }],
    '/api/auth/logout-all-devices' => ['POST' => function () { \CloudTasks\Controllers\AuthController::logoutAllDevices(); }],
    '/api/auth/verify-email' => ['POST' => function () { \CloudTasks\Controllers\AuthController::verifyEmail(); }],
    '/api/auth/forgot-password' => ['POST' => function () { \CloudTasks\Controllers\AuthController::forgotPassword(); }],
    '/api/auth/reset-password' => ['POST' => function () { \CloudTasks\Controllers\AuthController::resetPassword(); }],
    '/api/users/me' => [
        'GET' => function () { \CloudTasks\Controllers\AuthController::getMe(); },
        'PATCH' => function () { \CloudTasks\Controllers\AuthController::updateMe(); }
    ],
    '/api/lists' => [
        'GET' => function () { \CloudTasks\Controllers\TaskListController::index(); },
        'POST' => function () { \CloudTasks\Controllers\TaskListController::store(); }
    ],
    '/api/tasks/trashed' => ['GET' => function () { \CloudTasks\Controllers\TaskController::trashed(); }],
    '/api/tasks' => [
        'GET' => function () { \CloudTasks\Controllers\TaskController::index(); },
        'POST' => function () { \CloudTasks\Controllers\TaskController::store(); }
    ],
    '/api/dashboard/stats' => ['GET' => function () { \CloudTasks\Controllers\DashboardController::stats(); }],
    '/api/export' => ['GET' => function () { \CloudTasks\Controllers\ExportController::export(); }],
];

$paramRoutes = [
    '/api/lists/:id' => [
        'PATCH' => function ($params) { \CloudTasks\Controllers\TaskListController::update($params); },
        'DELETE' => function ($params) { \CloudTasks\Controllers\TaskListController::destroy($params); }
    ],
    '/api/tasks/:id' => [
        'GET' => function ($params) { \CloudTasks\Controllers\TaskController::show($params); },
        'PATCH' => function ($params) { \CloudTasks\Controllers\TaskController::update($params); },
        'DELETE' => function ($params) { \CloudTasks\Controllers\TaskController::destroy($params); }
    ],
    '/api/tasks/:id/restore' => ['POST' => function ($params) { \CloudTasks\Controllers\TaskController::restore($params); }],
    '/api/tasks/:id/permanent' => ['DELETE' => function ($params) { \CloudTasks\Controllers\TaskController::permanentDelete($params); }],
    '/api/tasks/:id/complete' => ['PATCH' => function ($params) { \CloudTasks\Controllers\TaskController::toggleComplete($params); }],
    '/api/tasks/:id/subtasks' => ['POST' => function ($params) { \CloudTasks\Controllers\SubtaskController::store($params); }],
    '/api/tasks/:id/subtasks/:subtaskId' => [
        'PATCH' => function ($params) { \CloudTasks\Controllers\SubtaskController::update($params); },
        'DELETE' => function ($params) { \CloudTasks\Controllers\SubtaskController::destroy($params); }
    ],
    '/api/tasks/:id/attachments' => ['POST' => function ($params) { \CloudTasks\Controllers\AttachmentController::store($params); }],
    '/api/tasks/:id/attachments/:publicId' => ['DELETE' => function ($params) { \CloudTasks\Controllers\AttachmentController::destroy($params); }],
];

function matchRoute(string $uri, array $paramRoutes): ?array
{
    foreach ($paramRoutes as $pattern => $handlers) {
        $regex = preg_replace('/\/:([a-zA-Z_]+)/', '/(?P<$1>[^/]+)', $pattern);
        $regex = '#^' . $regex . '$#';
        if (preg_match($regex, $uri, $matches)) {
            $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
            return ['handlers' => $handlers, 'params' => $params];
        }
    }
    return null;
}

// Static file serving for assets
$assetPath = __DIR__ . '/assets' . $uri;
if (preg_match('#^/assets/#', $uri) && file_exists($assetPath)) {
    $ext = pathinfo($assetPath, PATHINFO_EXTENSION);
    $mimeTypes = [
        'css' => 'text/css',
        'js' => 'application/javascript',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif' => 'image/gif',
        'svg' => 'image/svg+xml',
        'ico' => 'image/x-icon',
    ];
    header('Content-Type: ' . ($mimeTypes[$ext] ?? 'application/octet-stream'));
    readfile($assetPath);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

// Handle OPTIONS preflight
if ($method === 'OPTIONS') {
    header('Access-Control-Allow-Methods: GET, POST, PATCH, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-CSRF-Token');
    exit;
}

// Check static routes first
if (isset($routes[$uri]) && isset($routes[$uri][$method])) {
    $routes[$uri][$method]();
    exit;
}

// Check param routes
$matched = matchRoute($uri, $paramRoutes);
if ($matched && isset($matched['handlers'][$method])) {
    $matched['handlers'][$method]($matched['params']);
    exit;
}

// Check for GET-only routes
if ($method === 'GET' && isset($routes[$uri])) {
    $routes[$uri]['GET']();
    exit;
}

jsonError('Not found', 404);
