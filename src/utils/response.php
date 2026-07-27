<?php

use MongoDB\BSON\UTCDateTime;
use MongoDB\BSON\ObjectId;

function convertBSON(mixed $value): mixed
{
    if ($value instanceof UTCDateTime) {
        return $value->toDateTime()->format('c');
    }
    if ($value instanceof ObjectId) {
        return (string)$value;
    }
    if (is_array($value)) {
        $result = [];
        foreach ($value as $k => $v) {
            $result[$k] = convertBSON($v);
        }
        return $result;
    }
    if (is_object($value)) {
        $arr = (array)$value;
        $result = [];
        foreach ($arr as $k => $v) {
            $result[$k] = convertBSON($v);
        }
        return $result;
    }
    return $value;
}

function jsonResponse(mixed $data, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode(convertBSON($data), JSON_UNESCAPED_SLASHES);
    exit;
}

function jsonError(string $message, int $status = 400): void
{
    jsonResponse(['error' => $message], $status);
}
