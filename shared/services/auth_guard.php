<?php
declare(strict_types=1);

/**
 * Authentication Guards & Session Protection Middleware.
 *
 * File: shared/services/auth_guard.php
 * Mutation Log: 2026-11-04 - Created auth guard middleware with consistent $_SESSION['role'] check.
 */

require_once __DIR__ . '/../utils/session_init.php';
require_once __DIR__ . '/../utils/logger.php';

if (!function_exists('is_logged_in')) {
    /**
     * Checks whether there is an active authenticated user session.
     *
     * @return bool True if logged in, false otherwise.
     */
    function is_logged_in(): bool
    {
        return !empty($_SESSION['user_id']);
    }
}

if (!function_exists('is_admin')) {
    /**
     * Checks whether the current authenticated user has an 'admin' role.
     * Checks both $_SESSION['role'] and $_SESSION['user_role'] for compatibility.
     *
     * @return bool True if admin, false otherwise.
     */
    function is_admin(): bool
    {
        $role = $_SESSION['role'] ?? ($_SESSION['user_role'] ?? '');
        return $role === 'admin';
    }
}

if (!function_exists('require_login')) {
    /**
     * Enforces user authentication; redirects to login page if unauthenticated.
     *
     * @param string $relative_root Relative path prefix to root (e.g., '.' or '..').
     * @return void
     */
    function require_login(string $relative_root = '.'): void
    {
        log_event('require_login', 'START', ['is_logged_in' => is_logged_in()]);

        if (!is_logged_in()) {
            $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'] ?? "{$relative_root}/index.php";
            log_event('require_login', 'MUTATION', ['action' => 'redirect_to_login']);
            header("Location: {$relative_root}/login.php");
            exit;
        }

        log_event('require_login', 'FINISH', ['user_id' => $_SESSION['user_id']]);
    }
}

if (!function_exists('require_admin')) {
    /**
     * Enforces administrator privileges; verifies $_SESSION['role'] === 'admin'.
     *
     * @param string $relative_root Relative path prefix to root (e.g., '..').
     * @return void
     */
    function require_admin(string $relative_root = '..'): void
    {
        log_event('require_admin', 'START', [
            'is_logged_in' => is_logged_in(),
            'is_admin' => is_admin(),
        ]);

        if (!is_logged_in()) {
            $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'] ?? "{$relative_root}/admin/index.php";
            log_event('require_admin', 'MUTATION', ['action' => 'redirect_unauthenticated']);
            header("Location: {$relative_root}/login.php");
            exit;
        }

        if (!is_admin()) {
            log_event('require_admin', 'ERROR', [
                'user_id' => $_SESSION['user_id'] ?? null,
                'role' => $_SESSION['role'] ?? null,
                'reason' => 'Access denied: Insufficient privileges',
            ]);
            http_response_code(403);
            echo "<!DOCTYPE html><html lang='th'><head><meta charset='UTF-8'><title>403 เข้าถึงไม่ได้</title><link rel='stylesheet' href='{$relative_root}/css/style.css'></head><body>";
            echo "<div style='text-align:center; padding: 5rem 1rem;'><h1 style='color:var(--danger-color);'>403 Forbidden</h1><p style='color:var(--text-light);'>เฉพาะผู้ดูแลระบบ (Admin) เท่านั้นที่สามารถเข้าถึงส่วนนี้ได้</p><a href='{$relative_root}/index.php' class='btn btn_gold' style='margin-top:1.5rem;'>กลับหน้าหลัก</a></div>";
            echo "</body></html>";
            exit;
        }

        log_event('require_admin', 'FINISH', ['admin_user_id' => $_SESSION['user_id']]);
    }
}

