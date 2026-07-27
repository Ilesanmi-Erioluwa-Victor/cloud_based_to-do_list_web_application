<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/config/env.php';
require_once __DIR__ . '/../src/config/db.php';

loadEnv();

echo "Connecting to MongoDB...\n";
$db = getDB();

// ── Tasks ──────────────────────────────────────────────────────────────
$tasks = $db->selectCollection('tasks');
echo "\nCreating indexes for 'tasks':\n";

$tasks->createIndex(
    ['userId' => 1, 'deletedAt' => 1, 'position' => 1, 'createdAt' => -1],
    ['name' => 'user_deleted_position_created']
);
echo "  ✓ user_deleted_position_created\n";

$tasks->createIndex(
    ['userId' => 1, 'deletedAt' => 1, 'status' => 1, 'dueAt' => -1],
    ['name' => 'user_deleted_status_due']
);
echo "  ✓ user_deleted_status_due\n";

$tasks->createIndex(
    ['userId' => 1, 'deletedAt' => 1, 'taskListId' => 1],
    ['name' => 'user_deleted_list']
);
echo "  ✓ user_deleted_list\n";

$tasks->createIndex(
    ['userId' => 1, 'deletedAt' => 1, 'priority' => 1],
    ['name' => 'user_deleted_priority']
);
echo "  ✓ user_deleted_priority\n";

$tasks->createIndex(
    ['userId' => 1, 'deletedAt' => 1, 'status' => 1, 'completedAt' => -1],
    ['name' => 'user_deleted_status_completed']
);
echo "  ✓ user_deleted_status_completed\n";

// ── Users ──────────────────────────────────────────────────────────────
$users = $db->selectCollection('users');
echo "\nCreating indexes for 'users':\n";

try {
    $users->createIndex(
        ['email' => 1],
        ['name' => 'email_unique', 'unique' => true]
    );
    echo "  ✓ email_unique\n";
} catch (Exception $e) {
    // May already exist from initial setup; drop and recreate
    $users->dropIndex('email_unique');
    $users->createIndex(
        ['email' => 1],
        ['name' => 'email_unique', 'unique' => true]
    );
    echo "  ✓ email_unique (recreated)\n";
}

// ── Sessions ───────────────────────────────────────────────────────────
$sessions = $db->selectCollection('sessions');
echo "\nCreating indexes for 'sessions':\n";

$sessions->createIndex(
    ['tokenHash' => 1],
    ['name' => 'token_hash']
);
echo "  ✓ token_hash\n";

$sessions->createIndex(
    ['userId' => 1],
    ['name' => 'user_id']
);
echo "  ✓ user_id\n";

$sessions->createIndex(
    ['expiresAt' => 1],
    ['name' => 'expires_ttl', 'expireAfterSeconds' => 0]
);
echo "  ✓ expires_ttl (auto-delete expired sessions)\n";

// ── Task Lists ─────────────────────────────────────────────────────────
$taskLists = $db->selectCollection('taskLists');
echo "\nCreating indexes for 'taskLists':\n";

$taskLists->createIndex(
    ['userId' => 1, 'position' => 1],
    ['name' => 'user_position']
);
echo "  ✓ user_position\n";

// ── Reminder Logs ──────────────────────────────────────────────────────
$reminderLogs = $db->selectCollection('reminderLogs');
echo "\nCreating indexes for 'reminderLogs':\n";

$reminderLogs->createIndex(
    ['taskId' => 1, 'reminderType' => 1],
    ['name' => 'task_reminder_type']
);
echo "  ✓ task_reminder_type\n";

$reminderLogs->createIndex(
    ['sentAt' => 1],
    ['name' => 'sent_ttl', 'expireAfterSeconds' => 7776000]
);
echo "  ✓ sent_ttl (auto-delete logs after 90 days)\n";

echo "\nAll indexes created successfully.\n";
