<?php

namespace CloudTasks\Services;

use Cloudinary\Cloudinary;
use Cloudinary\Api\Upload\UploadApi;

class CloudinaryService
{
    private static ?Cloudinary $cloudinary = null;

    private static function getInstance(): Cloudinary
    {
        if (self::$cloudinary === null) {
            self::$cloudinary = new Cloudinary([
                'cloud' => [
                    'cloud_name' => env('CLOUDINARY_CLOUD_NAME'),
                    'api_key' => env('CLOUDINARY_API_KEY'),
                    'api_secret' => env('CLOUDINARY_API_SECRET'),
                ],
                'url' => ['secure' => true]
            ]);
        }
        return self::$cloudinary;
    }

    public static function upload(string $filePath, array $options = []): ?array
    {
        try {
            $result = self::getInstance()->uploadApi()->upload($filePath, $options);
            return [
                'url' => $result['secure_url'],
                'publicId' => $result['public_id'],
                'fileName' => basename($filePath),
                'fileSize' => filesize($filePath),
                'uploadedAt' => date('c')
            ];
        } catch (\Exception $e) {
            return null;
        }
    }

    public static function delete(string $publicId): bool
    {
        try {
            self::getInstance()->uploadApi()->destroy($publicId);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public static function getSignedUploadParams(): array
    {
        $timestamp = time();
        $params = [
            'timestamp' => $timestamp
        ];
        $apiSecret = env('CLOUDINARY_API_SECRET');
        $signature = self::generateSignature($params, $apiSecret);
        return [
            'timestamp' => $timestamp,
            'signature' => $signature,
            'api_key' => env('CLOUDINARY_API_KEY'),
            'cloud_name' => env('CLOUDINARY_CLOUD_NAME')
        ];
    }

    private static function generateSignature(array $params, string $secret): string
    {
        ksort($params);
        $stringToSign = '';
        foreach ($params as $key => $value) {
            $stringToSign .= $key . '=' . $value . '&';
        }
        $stringToSign = rtrim($stringToSign, '&');
        return sha1($stringToSign . $secret);
    }
}
