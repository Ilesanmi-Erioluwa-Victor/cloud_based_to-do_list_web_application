<?php

namespace CloudTasks\Models;

use MongoDB\BSON\UTCDateTime;
use MongoDB\BSON\ObjectId;

class TaskList
{
    public static function create(array $data): string
    {
        $collection = getCollection('taskLists');
        $data['position'] = $data['position'] ?? 0;
        $data['createdAt'] = new UTCDateTime();
        $data['updatedAt'] = new UTCDateTime();
        $result = $collection->insertOne($data);
        return (string)$result->getInsertedId();
    }

    public static function findByUser(string $userId): array
    {
        $collection = getCollection('taskLists');
        $cursor = $collection->find(
            ['userId' => $userId],
            ['sort' => ['position' => 1]]
        );
        $lists = [];
        foreach ($cursor as $doc) {
            $arr = $doc->jsonSerialize();
            $arr['_id'] = (string)$arr['_id'];
            $lists[] = $arr;
        }
        return $lists;
    }

    public static function findById(string $id): ?array
    {
        $collection = getCollection('taskLists');
        $doc = $collection->findOne(['_id' => new ObjectId($id)]);
        if (!$doc) return null;
        $arr = $doc->jsonSerialize();
        $arr['_id'] = (string)$arr['_id'];
        return $arr;
    }

    public static function updateById(string $id, array $data): void
    {
        $collection = getCollection('taskLists');
        $data['updatedAt'] = new UTCDateTime();
        $collection->updateOne(
            ['_id' => new ObjectId($id)],
            ['$set' => $data]
        );
    }

    public static function deleteById(string $id): void
    {
        $collection = getCollection('taskLists');
        $collection->deleteOne(['_id' => new ObjectId($id)]);
    }
}
