<?php

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

function getJwtSecret(): string
{
    $secret = env('JWT_SECRET');
    if (!$secret) {
        throw new RuntimeException('JWT_SECRET environment variable is not set');
    }
    return hash('sha256', $secret, true);
}

function generateJWT(array $payload): string
{
    $payload['iat'] = time();
    $payload['exp'] = time() + 86400 * 7;
    return JWT::encode($payload, getJwtSecret(), 'HS256');
}

function decodeJWT(string $token): object
{
    $key = new Key(getJwtSecret(), 'HS256');
    return JWT::decode($token, $key);
}

function getAuthToken(): ?string
{
    $header = $_SERVER['HTTP_AUTHORIZATION']
        ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
        ?? '';
    if (preg_match('/Bearer\s+(.+)$/i', $header, $matches)) {
        return $matches[1];
    }
    return $_COOKIE['auth_token'] ?? null;
}

function requireAuth(): object
{
    $token = getAuthToken();
    if (!$token) {
        jsonError('Authentication required', 401);
    }
    try {
        return decodeJWT($token);
    } catch (\Exception $e) {
        jsonError('Invalid or expired token', 401);
    }
}

function getCurrentUserId(): string
{
    return requireAuth()->sub;
}
