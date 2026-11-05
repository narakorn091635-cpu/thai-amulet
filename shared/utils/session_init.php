<?php
declare(strict_types=1);

/**
 * Global Session Initializer Helper.
 *
 * File: shared/utils/session_init.php
 * Mutation Log: 2026-11-04 - Created centralized session initializer ensuring consistent session_save_path across all endpoints.
 */

if (!function_exists('ensure_app_session_started')) {
    /**
     * Initializes the PHP session using the workspace sessions directory.
     *
     * @return void
     */
    function ensure_app_session_started(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            $sessions_dir = dirname(__DIR__, 2) . '/sessions';
            if (!is_dir($sessions_dir)) {
                @mkdir($sessions_dir, 0777, true);
            }
            if (is_dir($sessions_dir) && is_writable($sessions_dir)) {
                session_save_path($sessions_dir);
            }
            session_start();
        }
    }
}

// Auto-boot session when included
ensure_app_session_started();
