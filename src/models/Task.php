<?php

namespace CloudTasks\Models;

use MongoDB\BSON\UTCDateTime;
use MongoDB\BSON\ObjectId;

class Task
{
    public static function create(array $data): string
    {
        $collection = getCollection('tasks');
        $data['status'] = $data['status'] ?? 'pending';
        $data['priority'] = $data['priority'] ?? 'medium';
        $data['subtasks'] = $data['subtasks'] ?? [];
        $data['attachments'] = $data['attachments'] ?? [];
        $data['isRecurring'] = $data['isRecurring'] ?? false;
        $data['position'] = $data['position'] ?? 0;
        $data['createdAt'] = new UTCDateTime();
        $data['updatedAt'] = new UTCDateTime();
        $result = $collection->insertOne($data);
        return (string)$result->getInsertedId();
    }

    public static function findById(string $id): ?array
    {
        $collection = getCollection('tasks');
        $doc = $collection->findOne(['_id' => new ObjectId($id)]);
        if (!$doc) return null;
        return self::serialize($doc);
    }

    public static function findByUser(string $userId, array $filters = [], array $sort = []): array
    {
        $collection = getCollection('tasks');
        $query = array_merge(['userId' => $userId, 'deletedAt' => null], $filters);

        if (empty($sort)) {
            $sort = ['position' => 1, 'createdAt' => -1];
        }

        $cursor = $collection->find($query, ['sort' => $sort]);
        $tasks = [];
        foreach ($cursor as $doc) {
            $tasks[] = self::serialize($doc);
        }
        return $tasks;
    }

    public static function findTrashed(string $userId): array
    {
        $collection = getCollection('tasks');
        $cursor = $collection->find(
            ['userId' => $userId, 'deletedAt' => ['$ne' => null]],
            ['sort' => ['deletedAt' => -1]]
        );
        $tasks = [];
        foreach ($cursor as $doc) {
            $tasks[] = self::serialize($doc);
        }
        return $tasks;
    }

    public static function updateById(string $id, array $data): void
    {
        $collection = getCollection('tasks');
        $data['updatedAt'] = new UTCDateTime();
        $collection->updateOne(
            ['_id' => new ObjectId($id)],
            ['$set' => $data]
        );
    }

    public static function softDelete(string $id): void
    {
        $collection = getCollection('tasks');
        $collection->updateOne(
            ['_id' => new ObjectId($id)],
            ['$set' => ['deletedAt' => new UTCDateTime(), 'updatedAt' => new UTCDateTime()]]
        );
    }

    public static function restore(string $id): void
    {
        $collection = getCollection('tasks');
        $collection->updateOne(
            ['_id' => new ObjectId($id)],
            ['$set' => ['deletedAt' => null, 'updatedAt' => new UTCDateTime()]]
        );
    }

    public static function permanentDelete(string $id): void
    {
        $collection = getCollection('tasks');
        $collection->deleteOne(['_id' => new ObjectId($id)]);
    }

    public static function setCompleted(string $id, bool $completed): void
    {
        $collection = getCollection('tasks');
        $data = [
            'status' => $completed ? 'completed' : 'pending',
            'completedAt' => $completed ? new UTCDateTime() : null,
            'updatedAt' => new UTCDateTime()
        ];
        $collection->updateOne(
            ['_id' => new ObjectId($id)],
            ['$set' => $data]
        );
    }

    public static function countByUser(string $userId, array $extraConditions = []): int
    {
        $collection = getCollection('tasks');
        $query = array_merge(['userId' => $userId, 'deletedAt' => null], $extraConditions);
        return $collection->countDocuments($query);
    }

    public static function countByList(string $listId): int
    {
        $collection = getCollection('tasks');
        return $collection->countDocuments([
            'taskListId' => $listId,
            'deletedAt' => null
        ]);
    }

    private static function serialize($doc): array
    {
        $arr = $doc->jsonSerialize();
        $arr['_id'] = (string)$arr['_id'];
        return $arr;
    }
}
