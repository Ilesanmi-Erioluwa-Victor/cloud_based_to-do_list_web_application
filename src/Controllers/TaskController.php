<?php

namespace CloudTasks\Controllers;

use CloudTasks\Models\Task;

class TaskController
{
    public static function index(): void
    {
        $userId = getCurrentUserId();
        $filters = [];
        $sort = [];

        if (!empty($_GET['list'])) {
            $filters['taskListId'] = $_GET['list'];
        }

        if (!empty($_GET['status']) && $_GET['status'] !== 'all') {
            $filters['status'] = $_GET['status'];
        }

        if (!empty($_GET['priority']) && $_GET['priority'] !== 'all') {
            $filters['priority'] = $_GET['priority'];
        }

        if (!empty($_GET['view'])) {
            $now = new \MongoDB\BSON\UTCDateTime();
            $todayStart = new \MongoDB\BSON\UTCDateTime(strtotime('today') * 1000);
            $todayEnd = new \MongoDB\BSON\UTCDateTime(strtotime('tomorrow') * 1000 - 1);
            $weekEnd = new \MongoDB\BSON\UTCDateTime(strtotime('+7 days') * 1000);

            switch ($_GET['view']) {
                case 'today':
                    $filters['dueAt'] = ['$gte' => $todayStart, '$lte' => $todayEnd];
                    break;
                case 'upcoming':
                    $filters['dueAt'] = ['$gte' => $todayEnd, '$lte' => $weekEnd];
                    $filters['status'] = ['$ne' => 'completed'];
                    break;
                case 'overdue':
                    $filters['dueAt'] = ['$lt' => $todayStart];
                    $filters['status'] = ['$ne' => 'completed'];
                    break;
                case 'completed':
                    $filters['status'] = 'completed';
                    break;
            }
        }

        if (!empty($_GET['search'])) {
            $filters['$or'] = [
                ['title' => ['$regex' => $_GET['search'], '$options' => 'i']],
                ['description' => ['$regex' => $_GET['search'], '$options' => 'i']]
            ];
        }

        if (!empty($_GET['sort'])) {
            $order = ($_GET['order'] ?? 'asc') === 'desc' ? -1 : 1;
            $sort[$_GET['sort']] = $order;
        }

        $tasks = Task::findByUser($userId, $filters, $sort);
        jsonResponse($tasks);
    }

    public static function store(): void
    {
        $userId = getCurrentUserId();
        $data = json_decode(file_get_contents('php://input'), true);

        if (empty($data['title'])) {
            jsonError('Task title is required');
        }

        $taskData = [
            'userId' => $userId,
            'title' => $data['title'],
            'description' => $data['description'] ?? '',
            'priority' => $data['priority'] ?? 'medium',
            'taskListId' => $data['taskListId'] ?? null,
            'position' => $data['position'] ?? 0,
            'dueAt' => !empty($data['dueAt']) ? new \MongoDB\BSON\UTCDateTime(strtotime($data['dueAt']) * 1000) : null
        ];

        $taskId = Task::create($taskData);
        $task = Task::findById($taskId);
        jsonResponse($task, 201);
    }

    public static function show(array $params): void
    {
        $userId = getCurrentUserId();
        $task = Task::findById($params['id']);
        if (!$task || $task['userId'] !== $userId) {
            jsonError('Task not found', 404);
        }
        jsonResponse($task);
    }

    public static function update(array $params): void
    {
        $userId = getCurrentUserId();
        $taskId = $params['id'];

        $task = Task::findById($taskId);
        if (!$task || $task['userId'] !== $userId) {
            jsonError('Task not found', 404);
        }

        $data = json_decode(file_get_contents('php://input'), true);
        $allowed = ['title', 'description', 'priority', 'status', 'taskListId', 'position', 'isRecurring', 'recurrenceRule'];

        $update = array_intersect_key($data, array_flip($allowed));

        if (isset($data['dueAt'])) {
            $update['dueAt'] = !empty($data['dueAt']) ? new \MongoDB\BSON\UTCDateTime(strtotime($data['dueAt']) * 1000) : null;
        }

        if (!empty($update)) {
            Task::updateById($taskId, $update);
        }

        $task = Task::findById($taskId);
        jsonResponse($task);
    }

    public static function destroy(array $params): void
    {
        $userId = getCurrentUserId();
        $taskId = $params['id'];

        $task = Task::findById($taskId);
        if (!$task || $task['userId'] !== $userId) {
            jsonError('Task not found', 404);
        }

        Task::softDelete($taskId);
        jsonResponse(['message' => 'Task moved to trash']);
    }

    public static function restore(array $params): void
    {
        $userId = getCurrentUserId();
        $taskId = $params['id'];

        $task = Task::findById($taskId);
        if (!$task) {
            $collection = getCollection('tasks');
            $doc = $collection->findOne(['_id' => new \MongoDB\BSON\ObjectId($taskId)]);
            if (!$doc || $doc->jsonSerialize()['userId'] !== $userId) {
                jsonError('Task not found', 404);
            }
        }

        Task::restore($taskId);
        $task = Task::findById($taskId);
        jsonResponse($task);
    }

    public static function permanentDelete(array $params): void
    {
        $userId = getCurrentUserId();
        $taskId = $params['id'];

        $task = Task::findById($taskId);
        if (!$task || $task['userId'] !== $userId) {
            jsonError('Task not found', 404);
        }

        Task::permanentDelete($taskId);
        jsonResponse(['message' => 'Task permanently deleted']);
    }

    public static function toggleComplete(array $params): void
    {
        $userId = getCurrentUserId();
        $taskId = $params['id'];

        $task = Task::findById($taskId);
        if (!$task || $task['userId'] !== $userId) {
            jsonError('Task not found', 404);
        }

        $data = json_decode(file_get_contents('php://input'), true);
        $completed = $data['completed'] ?? !($task['status'] === 'completed');

        Task::setCompleted($taskId, $completed);

        $task = Task::findById($taskId);
        jsonResponse($task);
    }

    public static function trashed(): void
    {
        $userId = getCurrentUserId();
        $tasks = Task::findTrashed($userId);
        jsonResponse($tasks);
    }
}
