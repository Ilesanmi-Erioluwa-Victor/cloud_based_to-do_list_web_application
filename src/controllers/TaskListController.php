<?php

namespace CloudTasks\Controllers;

use CloudTasks\Models\TaskList;
use CloudTasks\Models\Task;

class TaskListController
{
    public static function index(): void
    {
        $userId = getCurrentUserId();
        $lists = TaskList::findByUser($userId);
        jsonResponse($lists);
    }

    public static function store(): void
    {
        $userId = getCurrentUserId();
        $data = json_decode(file_get_contents('php://input'), true);

        if (empty($data['name'])) {
            jsonError('List name is required');
        }

        $listId = TaskList::create([
            'userId' => $userId,
            'name' => $data['name'],
            'color' => $data['color'] ?? '#4A90D9',
            'position' => $data['position'] ?? 0
        ]);

        $list = TaskList::findById($listId);
        jsonResponse($list, 201);
    }

    public static function update(array $params): void
    {
        $userId = getCurrentUserId();
        $listId = $params['id'];

        $list = TaskList::findById($listId);
        if (!$list || $list['userId'] !== $userId) {
            jsonError('List not found', 404);
        }

        $data = json_decode(file_get_contents('php://input'), true);
        $allowed = ['name', 'color', 'position'];
        $update = array_intersect_key($data, array_flip($allowed));

        if (!empty($update)) {
            TaskList::updateById($listId, $update);
        }

        $list = TaskList::findById($listId);
        jsonResponse($list);
    }

    public static function destroy(array $params): void
    {
        $userId = getCurrentUserId();
        $listId = $params['id'];

        $list = TaskList::findById($listId);
        if (!$list || $list['userId'] !== $userId) {
            jsonError('List not found', 404);
        }

        $taskCount = Task::countByList($listId);
        if ($taskCount > 0) {
            jsonError('Cannot delete a list that still has tasks. Move or delete the tasks first.', 409);
        }

        TaskList::deleteById($listId);
        jsonResponse(['message' => 'List deleted']);
    }
}
