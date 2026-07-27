<?php

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../utils/response.php';

loadEnv();

$cronSecret = env('CRON_SECRET');
$providedSecret = $_SERVER['HTTP_X_CRON_SECRET'] ?? $_GET['secret'] ?? '';

if (!$cronSecret || $providedSecret !== $cronSecret) {
    jsonError('Unauthorized', 401);
}

use CloudTasks\Services\ReminderService;

try {
    $results = ReminderService::processReminders();
    echo json_encode([
        'status' => 'ok',
        'timestamp' => date('c'),
        'results' => $results
    ]);
} catch (\Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
