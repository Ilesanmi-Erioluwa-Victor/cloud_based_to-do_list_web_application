<?php

use MongoDB\Client;
use MongoDB\Database;

function getDB(): Database
{
    static $db = null;
    if ($db === null) {
        $uri = env('MONGODB_URI');
        if (!$uri) {
            throw new RuntimeException('MONGODB_URI environment variable is not set');
        }
        $client = new Client($uri);
        $db = $client->selectDatabase('cloudtasks');
    }
    return $db;
}

function getCollection(string $name): MongoDB\Collection
{
    return getDB()->selectCollection($name);
}
