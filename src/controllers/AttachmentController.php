<?php

namespace CloudTasks\Controllers;

use CloudTasks\Models\Task;
use CloudTasks\Services\CloudinaryService;

class AttachmentController
{
    public static function store(array $params): void
    {
        $userId = getCurrentUserId();
        $taskId = $params['id'];

        $task = Task::findById($taskId);
        if (!$task || $task['userId'] !== $userId) {
            jsonError('Task not found', 404);
        }

        if (!isset($_FILES['file'])) {
            jsonError('No file uploaded');
        }

        $file = $_FILES['file'];
        $maxSize = 10 * 1024 * 1024;
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf', 'text/plain', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];

        if ($file['size'] > $maxSize) {
            jsonError('File size exceeds 10MB limit');
        }

        if (!in_array($file['type'], $allowedTypes)) {
            jsonError('File type not allowed');
        }

        $result = CloudinaryService::upload($file['tmp_name'], [
            'folder' => 'cloudtasks/tasks/' . $taskId
        ]);

        if (!$result) {
            jsonError('Failed to upload file to Cloudinary');
        }

        $attachments = $task['attachments'] ?? [];
        $attachments[] = $result;
        Task::updateById($taskId, ['attachments' => $attachments]);

        $task = Task::findById($taskId);
        jsonResponse($task);
    }

    public static function destroy(array $params): void
    {
        $userId = getCurrentUserId();
        $taskId = $params['id'];
        $publicId = $params['publicId'];

        $task = Task::findById($taskId);
        if (!$task || $task['userId'] !== $userId) {
            jsonError('Task not found', 404);
        }

        CloudinaryService::delete($publicId);

        $attachments = array_filter($task['attachments'] ?? [], fn($a) => $a['publicId'] !== $publicId);
        Task::updateById($taskId, ['attachments' => array_values($attachments)]);

        $task = Task::findById($taskId);
        jsonResponse($task);
    }
}
