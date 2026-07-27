<?php

namespace CloudTasks\Models;

use MongoDB\BSON\UTCDateTime;
use MongoDB\BSON\ObjectId;

class ReminderLog
{
    public static function create(array $data): string
    {
        $collection = getCollection('reminderLogs');
        $data['sentAt'] = new UTCDateTime();
        $result = $collection->insertOne($data);
        return (string)$result->getInsertedId();
    }

    public static function exists(string $taskId, string $reminderType): bool
    {
        $collection = getCollection('reminderLogs');
        $count = $collection->countDocuments([
            'taskId' => $taskId,
            'reminderType' => $reminderType
        ]);
        return $count > 0;
    }
}
