<?php

namespace CloudTasks\Services;

use CloudTasks\Models\Task;

class RecurrenceService
{
    public static function generateNextOccurrence(string $taskId): ?string
    {
        $task = Task::findById($taskId);
        if (!$task || !$task['isRecurring'] || empty($task['recurrenceRule'])) {
            return null;
        }

        $dueAt = $task['dueAt'];
        if (!$dueAt) return null;

        if ($dueAt instanceof \MongoDB\BSON\UTCDateTime) {
            $timestamp = $dueAt->toDateTime()->getTimestamp();
        } else {
            $timestamp = strtotime((string)$dueAt);
        }

        $interval = match ($task['recurrenceRule']) {
            'daily' => '+1 day',
            'weekly' => '+1 week',
            'monthly' => '+1 month',
            default => null
        };

        if (!$interval) return null;

        $nextDueAt = date('c', strtotime($interval, $timestamp));

        $recurrenceParentId = $task['recurrenceParentId'] ?? $task['_id'];

        $newTaskId = Task::create([
            'userId' => $task['userId'],
            'taskListId' => $task['taskListId'],
            'title' => $task['title'],
            'description' => $task['description'],
            'priority' => $task['priority'],
            'dueAt' => $nextDueAt,
            'isRecurring' => true,
            'recurrenceRule' => $task['recurrenceRule'],
            'recurrenceParentId' => $recurrenceParentId,
            'subtasks' => array_map(function($st) {
                return [
                    '_id' => (string)(new \MongoDB\BSON\ObjectId()),
                    'title' => $st['title'],
                    'isCompleted' => false,
                    'position' => $st['position'] ?? 0
                ];
            }, $task['subtasks'] ?? [])
        ]);

        return $newTaskId;
    }
}
