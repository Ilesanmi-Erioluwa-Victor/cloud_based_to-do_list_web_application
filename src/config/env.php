<?php

use Dotenv\Dotenv;

function loadEnv(): void
{
    $root = dirname(__DIR__, 2);
    if (file_exists($root . '/.env')) {
        $dotenv = Dotenv::createImmutable($root);
        $dotenv->load();
    }
}

function env(string $key, mixed $default = null): mixed
{
    return $_ENV[$key] ?? $_SERVER[$key] ?? $default;
}
