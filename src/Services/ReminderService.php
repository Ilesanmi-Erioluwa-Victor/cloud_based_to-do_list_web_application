<?php

namespace CloudTasks\Services;

use CloudTasks\Models\ReminderLog;
use CloudTasks\Models\User;
use CloudTasks\Models\Task;
use MongoDB\BSON\ObjectId;

function docToArray($doc): array
{
    $arr = (array)$doc->jsonSerialize();
    if (isset($arr['_id']) && $arr['_id'] instanceof ObjectId) {
        $arr['_id'] = (string)$arr['_id'];
    }
    return $arr;
}

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

        foreach ($tasks as $taskDoc) {
            $task = docToArray($taskDoc);
            $taskId = $task['_id'];
            $dueAt = $task['dueAt'] instanceof \MongoDB\BSON\UTCDateTime
                ? $task['dueAt']->toDateTime()->getTimestamp()
                : strtotime($task['dueAt']);

            $userDoc = $usersCollection->findOne(['_id' => new ObjectId($task['userId'])]);
            if (!$userDoc) continue;

            $userArr = docToArray($userDoc);
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
            $userDoc = $usersCollection->findOne(['_id' => new ObjectId($userId)]);
            if (!$userDoc) continue;
            $userArr = docToArray($userDoc);
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
