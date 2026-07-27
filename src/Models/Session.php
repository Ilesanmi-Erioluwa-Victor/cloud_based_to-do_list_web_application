<?php

namespace CloudTasks\Models;

use MongoDB\BSON\UTCDateTime;
use MongoDB\BSON\ObjectId;

class Session
{
    public static function create(string $userId, string $userAgent = '', string $ipAddress = ''): string
    {
        $collection = getCollection('sessions');
        $token = bin2hex(random_bytes(64));
        $data = [
            'userId' => $userId,
            'tokenHash' => hash('sha256', $token),
            'createdAt' => new UTCDateTime(),
            'expiresAt' => new UTCDateTime(time() + 86400 * 7),
            'userAgent' => $userAgent,
            'ipAddress' => $ipAddress
        ];
        $collection->insertOne($data);
        return $token;
    }

    public static function validate(string $token): ?array
    {
        $collection = getCollection('sessions');
        $hash = hash('sha256', $token);
        $doc = $collection->findOne([
            'tokenHash' => $hash,
            'expiresAt' => ['$gt' => new UTCDateTime()]
        ]);
        return $doc ? self::toArray($doc) : null;
    }

    public static function deleteByToken(string $token): void
    {
        $collection = getCollection('sessions');
        $collection->deleteOne(['tokenHash' => hash('sha256', $token)]);
    }

    public static function deleteAllForUser(string $userId): void
    {
        $collection = getCollection('sessions');
        $collection->deleteMany(['userId' => $userId]);
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
