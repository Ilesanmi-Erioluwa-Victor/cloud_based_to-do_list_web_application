<?php

namespace CloudTasks\Controllers;

use CloudTasks\Models\Task;
use CloudTasks\Models\TaskList;

class ExportController
{
    public static function export(): void
    {
        $userId = getCurrentUserId();
        $format = $_GET['format'] ?? 'json';

        $lists = TaskList::findByUser($userId);
        $tasks = Task::findByUser($userId);

        $data = [
            'exportedAt' => date('c'),
            'lists' => $lists,
            'tasks' => $tasks
        ];

        if ($format === 'csv') {
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="cloudtasks-export-' . date('Y-m-d') . '.csv"');

            $output = fopen('php://output', 'w');
            fputcsv($output, ['Title', 'Description', 'Priority', 'Status', 'List', 'Due At', 'Completed At', 'Is Recurring', 'Recurrence Rule']);

            foreach ($tasks as $task) {
                $listName = '';
                foreach ($lists as $list) {
                    if ($list['_id'] === ($task['taskListId'] ?? '')) {
                        $listName = $list['name'];
                        break;
                    }
                }

                fputcsv($output, [
                    $task['title'],
                    $task['description'] ?? '',
                    $task['priority'] ?? 'medium',
                    $task['status'] ?? 'pending',
                    $listName,
                    $task['dueAt'] ?? '',
                    $task['completedAt'] ?? '',
                    $task['isRecurring'] ? 'Yes' : 'No',
                    $task['recurrenceRule'] ?? ''
                ]);
            }

            fclose($output);
            exit;
        }

        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="cloudtasks-export-' . date('Y-m-d') . '.json"');
        echo json_encode($data, JSON_PRETTY_PRINT);
        exit;
    }
}
