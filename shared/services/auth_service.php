<?php
declare(strict_types=1);

/**
 * Authentication Service & User Session Management.
 *
 * File: shared/services/auth_service.php
 * Mutation Log: 2026-11-04 - Created user registration and login functions for Module 4.
 */

require_once __DIR__ . '/../utils/logger.php';
require_once __DIR__ . '/../../includes/db_connect.php';

if (!function_exists('authenticate_user')) {
    /**
     * Authenticates a user by username/email and plaintext password.
     *
     * @param string $identity Username or email address.
     * @param string $password Plaintext password.
     * @return array<string, mixed>|null User array without password_hash if authenticated, or null.
     */
    function authenticate_user(string $identity, string $password): ?array
    {
        log_event('authenticate_user', 'START', ['identity' => $identity]);
        $pdo = get_db_connection();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ? LIMIT 1");
        $stmt->execute([$identity, $identity]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, (string)$user['password_hash'])) {
            unset($user['password_hash']);
            log_event('authenticate_user', 'FINISH', ['user_id' => $user['id'], 'role' => $user['role']]);
            return $user;
        }

        log_event('authenticate_user', 'FINISH', ['status' => 'failed']);
        return null;
    }
}

if (!function_exists('register_user')) {
    /**
     * Registers a new user account.
     *
     * @param string $username Unique username.
     * @param string $email    Unique email.
     * @param string $password Plaintext password.
     * @param string $fullname Full Thai name.
     * @param string $phone    Contact phone number.
     * @param string $address  Shipping address.
     * @return int Inserted user ID.
     * @throws Exception If username or email is already taken.
     */
    function register_user(string $username, string $email, string $password, string $fullname, string $phone = '', string $address = ''): int
    {
        log_event('register_user', 'START', ['username' => $username, 'email' => $email]);
        $pdo = get_db_connection();

        $check = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $check->execute([$username, $email]);
        if ($check->fetch()) {
            throw new Exception('ชื่อผู้ใช้หรืออีเมลนี้มีอยู่ในระบบแล้ว');
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("
            INSERT INTO users (username, email, password_hash, full_name, phone, address, role)
            VALUES (?, ?, ?, ?, ?, ?, 'user')
        ");
        $stmt->execute([$username, $email, $hash, $fullname, $phone, $address]);
        $user_id = (int)$pdo->lastInsertId();

        log_event('register_user', 'FINISH', ['user_id' => $user_id]);
        return $user_id;
    }
}

