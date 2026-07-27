<?php

namespace CloudTasks\Controllers;

use CloudTasks\Models\User;
use CloudTasks\Models\Session;

class AuthController
{
    public static function register(): void
    {
        $data = json_decode(file_get_contents('php://input'), true);

        if (empty($data['name']) || empty($data['email']) || empty($data['password'])) {
            jsonError('Name, email, and password are required');
        }

        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            jsonError('Invalid email format');
        }

        if (strlen($data['password']) < 6) {
            jsonError('Password must be at least 6 characters');
        }

        $existing = User::findByEmail($data['email']);
        if ($existing) {
            jsonError('Email already registered');
        }

        $userId = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'timezone' => $data['timezone'] ?? 'Africa/Lagos'
        ]);

        $token = generateJWT(['sub' => $userId]);

        jsonResponse([
            'message' => 'Registration successful. Please verify your email.',
            'userId' => $userId,
            'token' => $token
        ], 201);
    }

    public static function login(): void
    {
        $data = json_decode(file_get_contents('php://input'), true);

        if (empty($data['email']) || empty($data['password'])) {
            jsonError('Email and password are required');
        }

        $user = User::findByEmail($data['email']);
        if (!$user || !password_verify($data['password'], $user['passwordHash'])) {
            jsonError('Invalid email or password', 401);
        }

        if (isset($user['deletedAt']) && $user['deletedAt'] !== null) {
            jsonError('Account has been deleted', 401);
        }

        $token = generateJWT(['sub' => $user['_id']]);

        setcookie('auth_token', $token, [
            'expires' => time() + 86400 * 7,
            'path' => '/',
            'secure' => true,
            'httponly' => true,
            'samesite' => 'Strict'
        ]);

        jsonResponse([
            'message' => 'Login successful',
            'token' => $token,
            'user' => [
                'id' => $user['_id'],
                'name' => $user['name'],
                'email' => $user['email'],
                'timezone' => $user['timezone'],
                'themePreference' => $user['themePreference'],
                'isEmailVerified' => $user['isEmailVerified']
            ]
        ]);
    }

    public static function logout(): void
    {
        setcookie('auth_token', '', [
            'expires' => time() - 3600,
            'path' => '/',
            'secure' => true,
            'httponly' => true,
            'samesite' => 'Strict'
        ]);
        jsonResponse(['message' => 'Logged out successfully']);
    }

    public static function logoutAllDevices(): void
    {
        $userId = getCurrentUserId();
        Session::deleteAllForUser($userId);
        setcookie('auth_token', '', [
            'expires' => time() - 3600,
            'path' => '/',
            'secure' => true,
            'httponly' => true,
            'samesite' => 'Strict'
        ]);
        jsonResponse(['message' => 'Logged out of all devices']);
    }

    public static function verifyEmail(): void
    {
        $data = json_decode(file_get_contents('php://input'), true);
        if (empty($data['token'])) {
            jsonError('Verification token is required');
        }

        $userId = getCurrentUserId();
        $user = User::findById($userId);
        if (!$user) {
            jsonError('User not found', 404);
        }

        $storedToken = $_SESSION['email_verification_token'] ?? '';
        if (!hash_equals($storedToken, $data['token'])) {
            jsonError('Invalid verification token', 401);
        }

        User::updateById($userId, ['isEmailVerified' => true]);
        unset($_SESSION['email_verification_token']);

        jsonResponse(['message' => 'Email verified successfully']);
    }

    public static function forgotPassword(): void
    {
        $data = json_decode(file_get_contents('php://input'), true);
        if (empty($data['email'])) {
            jsonError('Email is required');
        }

        $user = User::findByEmail($data['email']);
        if (!$user) {
            jsonResponse(['message' => 'If the email exists, a reset link has been sent']);
        }

        $resetToken = bin2hex(random_bytes(32));
        $_SESSION['password_reset_token'] = $resetToken;
        $_SESSION['password_reset_email'] = $data['email'];
        $_SESSION['password_reset_expires'] = time() + 3600;

        jsonResponse([
            'message' => 'If the email exists, a reset link has been sent',
            'resetToken' => $resetToken
        ]);
    }

    public static function resetPassword(): void
    {
        $data = json_decode(file_get_contents('php://input'), true);
        if (empty($data['token']) || empty($data['password'])) {
            jsonError('Token and new password are required');
        }

        if (($_SESSION['password_reset_token'] ?? '') !== $data['token']) {
            jsonError('Invalid reset token', 401);
        }

        if (($_SESSION['password_reset_expires'] ?? 0) < time()) {
            jsonError('Reset token has expired', 401);
        }

        $email = $_SESSION['password_reset_email'] ?? '';
        $user = User::findByEmail($email);
        if (!$user) {
            jsonError('User not found', 404);
        }

        User::updateById($user['_id'], ['passwordHash' => password_hash($data['password'], PASSWORD_BCRYPT)]);

        unset($_SESSION['password_reset_token'], $_SESSION['password_reset_email'], $_SESSION['password_reset_expires']);

        jsonResponse(['message' => 'Password reset successfully']);
    }

    public static function getMe(): void
    {
        $userId = getCurrentUserId();
        $user = User::findById($userId);
        if (!$user) {
            jsonError('User not found', 404);
        }

        jsonResponse([
            'id' => $user['_id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'timezone' => $user['timezone'],
            'themePreference' => $user['themePreference'],
            'isEmailVerified' => $user['isEmailVerified']
        ]);
    }

    public static function updateMe(): void
    {
        $userId = getCurrentUserId();
        $data = json_decode(file_get_contents('php://input'), true);

        $allowed = ['name', 'timezone', 'themePreference'];
        $update = array_intersect_key($data, array_flip($allowed));

        if (!empty($update)) {
            User::updateById($userId, $update);
        }

        jsonResponse(['message' => 'Profile updated']);
    }
}
