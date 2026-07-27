<?php

namespace CloudTasks\Controllers;

use CloudTasks\Models\Task;

class DashboardController
{
    public static function stats(): void
    {
        $userId = getCurrentUserId();
        $todayStart = new \MongoDB\BSON\UTCDateTime(strtotime('today') * 1000);
        $todayEnd = new \MongoDB\BSON\UTCDateTime(strtotime('tomorrow') * 1000 - 1);
        $weekAgo = new \MongoDB\BSON\UTCDateTime(strtotime('-7 days') * 1000);

        $totalTasks = Task::countByUser($userId);
        $completedTasks = Task::countByUser($userId, ['status' => 'completed']);
        $pendingTasks = Task::countByUser($userId, ['status' => 'pending']);
        $inProgressTasks = Task::countByUser($userId, ['status' => 'in_progress']);
        $overdueTasks = Task::countByUser($userId, [
            'dueAt' => ['$lt' => $todayStart],
            'status' => ['$ne' => 'completed']
        ]);
        $upcomingTasks = Task::countByUser($userId, [
            'dueAt' => ['$gte' => $todayEnd, '$lte' => new \MongoDB\BSON\UTCDateTime(strtotime('+7 days') * 1000)],
            'status' => ['$ne' => 'completed']
        ]);

        $completedToday = Task::countByUser($userId, [
            'status' => 'completed',
            'completedAt' => ['$gte' => $todayStart, '$lte' => $todayEnd]
        ]);

        $completedThisWeek = Task::countByUser($userId, [
            'status' => 'completed',
            'completedAt' => ['$gte' => $weekAgo]
        ]);

        $completions7 = [];
        for ($i = 6; $i >= 0; $i--) {
            $dayStart = new \MongoDB\BSON\UTCDateTime(strtotime("-{$i} days") * 1000);
            $dayEnd = new \MongoDB\BSON\UTCDateTime(strtotime("-" . ($i - 1) . " days") * 1000 - 1);
            $count = Task::countByUser($userId, [
                'status' => 'completed',
                'completedAt' => ['$gte' => $dayStart, '$lte' => $dayEnd]
            ]);
            $completions7[] = [
                'date' => date('Y-m-d', strtotime("-{$i} days")),
                'count' => $count
            ];
        }

        $completions30 = [];
        for ($i = 29; $i >= 0; $i--) {
            $dayStart = new \MongoDB\BSON\UTCDateTime(strtotime("-{$i} days") * 1000);
            $dayEnd = new \MongoDB\BSON\UTCDateTime(strtotime("-" . ($i - 1) . " days") * 1000 - 1);
            $count = Task::countByUser($userId, [
                'status' => 'completed',
                'completedAt' => ['$gte' => $dayStart, '$lte' => $dayEnd]
            ]);
            $completions30[] = [
                'date' => date('Y-m-d', strtotime("-{$i} days")),
                'count' => $count
            ];
        }

        $streak = 0;
        $checkDate = new \DateTime('today');
        while (true) {
            $dayStart = new \MongoDB\BSON\UTCDateTime($checkDate->getTimestamp() * 1000);
            $dayEnd = new \MongoDB\BSON\UTCDateTime(($checkDate->getTimestamp() + 86400) * 1000 - 1);
            $count = Task::countByUser($userId, [
                'status' => 'completed',
                'completedAt' => ['$gte' => $dayStart, '$lte' => $dayEnd]
            ]);
            if ($count > 0) {
                $streak++;
                $checkDate->modify('-1 day');
            } else {
                break;
            }
        }

        jsonResponse([
            'totalTasks' => $totalTasks,
            'completedTasks' => $completedTasks,
            'pendingTasks' => $pendingTasks,
            'inProgressTasks' => $inProgressTasks,
            'overdueTasks' => $overdueTasks,
            'upcomingTasks' => $upcomingTasks,
            'completedToday' => $completedToday,
            'completedThisWeek' => $completedThisWeek,
            'currentStreak' => $streak,
            'completions7' => $completions7,
            'completions30' => $completions30
        ]);
    }
}
