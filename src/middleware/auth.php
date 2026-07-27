<?php

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

function generateJWT(array $payload): string
{
    $secret = env('JWT_SECRET');
    if (!$secret) {
        throw new RuntimeException('JWT_SECRET environment variable is not set');
    }
    $payload['iat'] = time();
    $payload['exp'] = time() + 86400 * 7;
    return JWT::encode($payload, $secret, 'HS256');
}

function decodeJWT(string $token): object
{
    $secret = env('JWT_SECRET');
    if (!$secret) {
        throw new RuntimeException('JWT_SECRET environment variable is not set');
    }
    return JWT::decode($token, new Key($secret, 'HS256'));
}

function getAuthToken(): ?string
{
    $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
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
