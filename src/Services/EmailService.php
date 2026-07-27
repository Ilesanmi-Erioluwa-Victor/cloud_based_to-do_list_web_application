<?php

namespace CloudTasks\Services;

class EmailService
{
    private static function send(string $to, string $subject, string $htmlContent): bool
    {
        $apiKey = env('BREVO_API_KEY');
        if (!$apiKey) {
            return false;
        }

        $data = [
            'sender' => ['name' => 'CloudTasks', 'email' => 'noreply@cloudtasks.app'],
            'to' => [['email' => $to]],
            'subject' => $subject,
            'htmlContent' => $htmlContent
        ];

        $ch = curl_init('https://api.brevo.com/v3/smtp/email');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'api-key: ' . $apiKey
            ],
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $httpCode >= 200 && $httpCode < 300;
    }

    public static function sendVerificationEmail(string $to, string $name, string $token): bool
    {
        $appUrl = env('APP_URL', 'http://localhost:8000');
        $link = $appUrl . '/verify-email?token=' . urlencode($token);
        $html = "<h1>Welcome to CloudTasks!</h1><p>Hi {$name},</p><p>Please verify your email by clicking the link below:</p><p><a href=\"{$link}\">Verify Email</a></p><p>This link expires in 24 hours.</p>";
        return self::send($to, 'Verify your CloudTasks account', $html);
    }

    public static function sendPasswordResetEmail(string $to, string $name, string $token): bool
    {
        $appUrl = env('APP_URL', 'http://localhost:8000');
        $link = $appUrl . '/reset-password?token=' . urlencode($token);
        $html = "<h1>Password Reset</h1><p>Hi {$name},</p><p>Click the link below to reset your password:</p><p><a href=\"{$link}\">Reset Password</a></p><p>This link expires in 1 hour.</p>";
        return self::send($to, 'Reset your CloudTasks password', $html);
    }

    public static function sendReminderEmail(string $to, string $name, string $taskTitle, string $dueAt, string $type): bool
    {
        $labels = [
            '24h_before' => 'due in less than 24 hours',
            '1h_before' => 'due in less than 1 hour',
            'overdue' => 'overdue'
        ];
        $label = $labels[$type] ?? 'upcoming';
        $html = "<h1>Task Reminder</h1><p>Hi {$name},</p><p>Your task \"<strong>{$taskTitle}</strong>\" is {$label} (due: {$dueAt}).</p><p><a href=\"" . env('APP_URL') . "\">View in CloudTasks</a></p>";
        return self::send($to, "Task Reminder: {$taskTitle}", $html);
    }

    public static function sendOverdueDigest(string $to, string $name, array $tasks): bool
    {
        $html = "<h1>Overdue Tasks Digest</h1><p>Hi {$name},</p><p>You have " . count($tasks) . " overdue task(s):</p><ul>";
        foreach ($tasks as $task) {
            $html .= "<li><strong>{$task['title']}</strong> (due: {$task['dueAt']})</li>";
        }
        $html .= "</ul><p><a href=\"" . env('APP_URL') . "\">View in CloudTasks</a></p>";
        return self::send($to, 'Overdue Tasks Digest', $html);
    }
}
