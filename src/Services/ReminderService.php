<?php

namespace CloudTasks\Services;

use CloudTasks\Models\ReminderLog;
use CloudTasks\Models\User;
use CloudTasks\Models\Task;

class ReminderService
{
    public static function processReminders(): array
    {
        $results = ['sent' => 0, 'failed' => 0, 'overdue_digests' => 0, 'errors' => []];
        $tasksCollection = getCollection('tasks');
        $usersCollection = getCollection('users');
        $now = new \MongoDB\BSON\UTCDateTime();
        $nowTs = time();

        $tasks = $tasksCollection->find([
            'deletedAt' => null,
            'status' => ['$ne' => 'completed'],
            'dueAt' => ['$ne' => null]
        ]);

        $overdueByUser = [];

        foreach ($tasks as $task) {
            $task = $task->jsonSerialize();
            $taskId = (string)$task['_id'];
            $dueAt = $task['dueAt']->toDateTime()->getTimestamp();

            $user = $usersCollection->findOne(['_id' => new \MongoDB\BSON\ObjectId($task['userId'])]);
            if (!$user) continue;

            $userArr = $user->jsonSerialize();
            $timezone = $userArr['timezone'] ?? 'Africa/Lagos';

            try {
                $userTz = new \DateTimeZone($timezone);
                $dueLocal = new \DateTime('@' . $dueAt);
                $dueLocal->setTimezone($userTz);
                $nowLocal = new \DateTime('now', $userTz);
                $diffHours = ($dueLocal->getTimestamp() - $nowLocal->getTimestamp()) / 3600;
            } catch (\Exception $e) {
                continue;
            }

            $userEmail = $userArr['email'] ?? '';
            $userName = $userArr['name'] ?? '';
            $emailVerified = $userArr['isEmailVerified'] ?? false;

            if (!$emailVerified || !$userEmail) continue;

            $dueAtStr = $dueLocal->format('Y-m-d H:i');

            if ($diffHours <= 24 && $diffHours > 1 && !ReminderLog::exists($taskId, '24h_before')) {
                $ok = EmailService::sendReminderEmail($userEmail, $userName, $task['title'], $dueAtStr, '24h_before');
                ReminderLog::create(['taskId' => $taskId, 'reminderType' => '24h_before', 'status' => $ok ? 'sent' : 'failed']);
                $ok ? $results['sent']++ : $results['failed']++;
            }

            if ($diffHours <= 1 && $diffHours > 0 && !ReminderLog::exists($taskId, '1h_before')) {
                $ok = EmailService::sendReminderEmail($userEmail, $userName, $task['title'], $dueAtStr, '1h_before');
                ReminderLog::create(['taskId' => $taskId, 'reminderType' => '1h_before', 'status' => $ok ? 'sent' : 'failed']);
                $ok ? $results['sent']++ : $results['failed']++;
            }

            if ($diffHours <= 0 && !ReminderLog::exists($taskId, 'overdue')) {
                $overdueByUser[$task['userId']][] = [
                    'taskId' => $taskId,
                    'title' => $task['title'],
                    'dueAt' => $dueAtStr
                ];
            }
        }

        foreach ($overdueByUser as $userId => $overdueTasks) {
            $userArr = $usersCollection->findOne(['_id' => new \MongoDB\BSON\ObjectId($userId)])->jsonSerialize();
            if (empty($userArr['email']) || !($userArr['isEmailVerified'] ?? false)) continue;

            $ok = EmailService::sendOverdueDigest($userArr['email'], $userArr['name'] ?? '', $overdueTasks);
            foreach ($overdueTasks as $ot) {
                ReminderLog::create(['taskId' => $ot['taskId'], 'reminderType' => 'overdue', 'status' => $ok ? 'sent' : 'failed']);
            }
            $ok ? $results['overdue_digests']++ : $results['failed']++;
        }

        return $results;
    }
}
