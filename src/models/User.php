<?php

namespace CloudTasks\Models;

use MongoDB\BSON\UTCDateTime;
use MongoDB\BSON\ObjectId;

class User
{
    public static function create(array $data): string
    {
        $collection = getCollection('users');
        $data['passwordHash'] = password_hash($data['password'], PASSWORD_BCRYPT);
        unset($data['password']);
        $data['isEmailVerified'] = false;
        $data['timezone'] = $data['timezone'] ?? 'Africa/Lagos';
        $data['themePreference'] = $data['themePreference'] ?? 'light';
        $data['createdAt'] = new UTCDateTime();
        $data['updatedAt'] = new UTCDateTime();
        $result = $collection->insertOne($data);
        return (string)$result->getInsertedId();
    }

    public static function findByEmail(string $email): ?array
    {
        $collection = getCollection('users');
        return $collection->findOne(['email' => $email])?->jsonSerialize();
    }

    public static function findById(string $id): ?array
    {
        $collection = getCollection('users');
        $doc = $collection->findOne(['_id' => new ObjectId($id)]);
        return $doc ? self::serialize($doc) : null;
    }

    public static function updateById(string $id, array $data): void
    {
        $collection = getCollection('users');
        $data['updatedAt'] = new UTCDateTime();
        $collection->updateOne(
            ['_id' => new ObjectId($id)],
            ['$set' => $data]
        );
    }

    public static function softDelete(string $id): void
    {
        $collection = getCollection('users');
        $collection->updateOne(
            ['_id' => new ObjectId($id)],
            ['$set' => ['deletedAt' => new UTCDateTime(), 'updatedAt' => new UTCDateTime()]]
        );
    }

    private static function serialize($doc): array
    {
        $arr = $doc->jsonSerialize();
        $arr['_id'] = (string)$arr['_id'];
        return $arr;
    }
}
