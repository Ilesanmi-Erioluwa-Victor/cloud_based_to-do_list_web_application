<?php

function rateLimit(string $key, int $maxAttempts = 5, int $windowSeconds = 300): void
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $rateKey = "rate_limit:{$key}:{$ip}";
    $cache = [];

    $cacheFile = sys_get_temp_dir() . '/' . md5($rateKey) . '.json';
    if (file_exists($cacheFile)) {
        $cache = json_decode(file_get_contents($cacheFile), true) ?? [];
    }

    $now = time();
    $cache = array_filter($cache, fn($t) => $t > $now - $windowSeconds);

    if (count($cache) >= $maxAttempts) {
        jsonError('Too many attempts. Please try again later.', 429);
    }

    $cache[] = $now;
    file_put_contents($cacheFile, json_encode($cache));
}
