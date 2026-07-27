<?php

namespace CloudTasks\Controllers;

use CloudTasks\Models\Task;

class SubtaskController
{
    public static function store(array $params): void
    {
        $userId = getCurrentUserId();
        $taskId = $params['id'];

        $task = Task::findById($taskId);
        if (!$task || $task['userId'] !== $userId) {
            jsonError('Task not found', 404);
        }

        $data = json_decode(file_get_contents('php://input'), true);
        if (empty($data['title'])) {
            jsonError('Subtask title is required');
        }

        $subtasks = $task['subtasks'] ?? [];
        $subtaskId = (string)(new \MongoDB\BSON\ObjectId());
        $subtasks[] = [
            '_id' => $subtaskId,
            'title' => $data['title'],
            'isCompleted' => false,
            'position' => count($subtasks)
        ];

        Task::updateById($taskId, ['subtasks' => $subtasks]);
        $task = Task::findById($taskId);
        jsonResponse($task);
    }

    public static function update(array $params): void
    {
        $userId = getCurrentUserId();
        $taskId = $params['id'];
        $subtaskId = $params['subtaskId'];

        $task = Task::findById($taskId);
        if (!$task || $task['userId'] !== $userId) {
            jsonError('Task not found', 404);
        }

        $data = json_decode(file_get_contents('php://input'), true);
        $subtasks = $task['subtasks'] ?? [];

        foreach ($subtasks as &$subtask) {
            if ($subtask['_id'] === $subtaskId) {
                if (isset($data['title'])) {
                    $subtask['title'] = $data['title'];
                }
                if (isset($data['isCompleted'])) {
                    $subtask['isCompleted'] = (bool)$data['isCompleted'];
                }
                if (isset($data['position'])) {
                    $subtask['position'] = (int)$data['position'];
                }
                break;
            }
        }

        Task::updateById($taskId, ['subtasks' => $subtasks]);
        $task = Task::findById($taskId);
        jsonResponse($task);
    }

    public static function destroy(array $params): void
    {
        $userId = getCurrentUserId();
        $taskId = $params['id'];
        $subtaskId = $params['subtaskId'];

        $task = Task::findById($taskId);
        if (!$task || $task['userId'] !== $userId) {
            jsonError('Task not found', 404);
        }

        $subtasks = array_filter($task['subtasks'] ?? [], fn($s) => $s['_id'] !== $subtaskId);
        Task::updateById($taskId, ['subtasks' => array_values($subtasks)]);

        $task = Task::findById($taskId);
        jsonResponse($task);
    }
}
