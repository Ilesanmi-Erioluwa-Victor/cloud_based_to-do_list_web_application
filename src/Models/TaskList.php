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
            $lists[] = self::toArray($doc);
        }
        return $lists;
    }

    public static function findById(string $id): ?array
    {
        $collection = getCollection('taskLists');
        $doc = $collection->findOne(['_id' => new ObjectId($id)]);
        return $doc ? self::toArray($doc) : null;
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

    private static function toArray($doc): array
    {
        $arr = (array)$doc->jsonSerialize();
        if (isset($arr['_id']) && $arr['_id'] instanceof ObjectId) {
            $arr['_id'] = (string)$arr['_id'];
        }
        return $arr;
    }
}
