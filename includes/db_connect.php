<?php
declare(strict_types=1);

/**
 * Primary Database Connection Entry Point.
 *
 * File: includes/db_connect.php
 * Mutation Log: 2026-11-04 - Created PDO connection bootstrapper delegating to shared/database/pdo_manager.php.
 */

require_once __DIR__ . '/../apps/config/app_config.php';
require_once __DIR__ . '/../shared/database/pdo_manager.php';

if (!function_exists('get_db_connection')) {
    /**
     * Returns a singleton PDO database connection for the active request.
     *
     * @return PDO Active PDO database connection.
     * @throws PDOException When the MySQL server is unreachable or credentials fail.
     */
    function get_db_connection(): PDO
    {
        static $pdo_instance = null;

        log_event('get_db_connection', 'START', ['cached' => $pdo_instance !== null]);

        if ($pdo_instance === null) {
            $db_config = get_app_config('db');
            $pdo_instance = create_pdo_connection($db_config, true);
        }

        log_event('get_db_connection', 'FINISH', ['status' => 'ready']);
        return $pdo_instance;
    }
}

// Provide $pdo in scope for legacy/direct include patterns when MySQL is running
$pdo = null;
try {
    if (getenv('SKIP_DB_AUTOCONNECT') !== '1') {
        $pdo = get_db_connection();
    }
} catch (PDOException $db_error) {
    // Logged inside create_pdo_connection; allow pages to render graceful diagnostics if DB is offline
    log_event('db_connect_bootstrap', 'ERROR', ['message' => $db_error->getMessage()]);
}

