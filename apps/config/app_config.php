<?php
declare(strict_types=1);

/**
 * Dynamic Application & Database Configuration Loader.
 *
 * File: apps/config/app_config.php
 * Mutation Log: 2026-11-04 - Created dynamic configuration loader using environment variables with sensible defaults.
 */

require_once __DIR__ . '/../../shared/utils/logger.php';

if (!function_exists('get_app_config')) {
    /**
     * Loads and returns the dynamic configuration dictionary from environment variables.
     *
     * @param string|null $section Optional configuration section key ('db', 'app', 'auction', 'theme').
     * @return array<string, mixed> Configuration values for the requested section or full config array.
     * @throws InvalidArgumentException When an unknown configuration section is requested.
     */
    function get_app_config(?string $section = null): array
    {
        log_event('get_app_config', 'START', ['section' => $section]);

        $config = [
            'db' => [
                'driver' => getenv('DB_DRIVER') ?: 'mysql',
                'host' => getenv('DB_HOST') ?: '127.0.0.1',
                'port' => (int)(getenv('DB_PORT') ?: 3306),
                'database' => getenv('DB_DATABASE') ?: 'thai_amulet_db',
                'username' => getenv('DB_USERNAME') ?: 'root',
                'password' => getenv('DB_PASSWORD') !== false ? (string)getenv('DB_PASSWORD') : '',
                'charset' => getenv('DB_CHARSET') ?: 'utf8mb4',
                'collation' => getenv('DB_COLLATION') ?: 'utf8mb4_unicode_ci',
            ],
            'app' => [
                'name' => getenv('APP_NAME') ?: 'สยามพุทธคุณ (Siam Amulet & Auction)',
                'env' => getenv('APP_ENV') ?: 'development',
                'timezone' => getenv('APP_TIMEZONE') ?: 'Asia/Bangkok',
                'upload_dir' => getenv('APP_UPLOAD_DIR') ?: 'images/uploads',
            ],
            'auction' => [
                'anti_snipe_window_seconds' => (int)(getenv('ANTI_SNIPE_WINDOW_SECONDS') ?: 60),
                'anti_snipe_extend_seconds' => (int)(getenv('ANTI_SNIPE_EXTEND_SECONDS') ?: 180),
                'default_min_increment' => (float)(getenv('DEFAULT_MIN_BID_INCREMENT') ?: 100.00),
            ],
            'theme' => [
                'bg_dark' => getenv('THEME_BG_DARK') ?: '#5e030f',
                'gold_accent' => getenv('THEME_GOLD_ACCENT') ?: '#E9D793',
                'text_light' => getenv('THEME_TEXT_LIGHT') ?: '#ffffff',
                'text_dark' => getenv('THEME_TEXT_DARK') ?: '#333333',
            ],
        ];

        if ($section !== null) {
            if (!array_key_exists($section, $config)) {
                log_event('get_app_config', 'ERROR', ['invalid_section' => $section]);
                throw new InvalidArgumentException("Unknown configuration section: {$section}");
            }
            log_event('get_app_config', 'FINISH', ['section' => $section]);
            return $config[$section];
        }

        log_event('get_app_config', 'FINISH', ['section' => 'all']);
        return $config;
    }
}

