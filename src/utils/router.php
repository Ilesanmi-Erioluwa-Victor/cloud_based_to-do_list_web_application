<?php

function route(string $method, string $path, callable $handler): void
{
    static $routes = [];
    $routes[] = compact('method', 'path', 'handler');
}

function dispatch(): void
{
    $method = $_SERVER['REQUEST_METHOD'];
    $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $uri = rtrim($uri, '/') ?: '/';

    $routes = $GLOBALS['__routes'] ?? [];
    foreach ($routes as $route) {
        $pattern = preg_replace('/\/:([a-zA-Z_]+)/', '/(?P<$1>[^/]+)', $route['path']);
        $pattern = '#^' . $pattern . '$#';

        if ($route['method'] === $method && preg_match($pattern, $uri, $matches)) {
            $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
            call_user_func($route['handler'], $params);
            return;
        }
    }

    jsonError('Not found', 404);
}

function registerRoutes(): void
{
    $routes = $GLOBALS['__routes'] ?? [];
}
