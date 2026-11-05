<?php
declare(strict_types=1);

/**
 * Structured Logger Utility for Backend & Fullstack Events.
 *
 * File: shared/utils/logger.php
 * Mutation Log: 2026-11-04 - Created structured logger with correlation IDs and entry/exit tracking.
 */

if (!function_exists('get_correlation_id')) {
    /**
     * Retrieves or generates a unique correlation ID for the current request lifecycle.
     *
     * @return string Unique request correlation identifier.
     */
    function get_correlation_id(): string
    {
        static $correlation_id = null;
        if ($correlation_id === null) {
            $header_id = $_SERVER['HTTP_X_CORRELATION_ID'] ?? '';
            $correlation_id = !empty($header_id)
                ? preg_replace('/[^a-zA-Z0-9\-_]/', '', (string)$header_id)
                : 'req_' . bin2hex(random_bytes(6));
        }
        return $correlation_id;
    }
}

if (!function_exists('log_event')) {
    /**
     * Writes a structured log entry to the application log or PHP error log.
     *
     * @param string $function_name Name of the function or module emitting the event.
     * @param string $phase         Execution phase ('START', 'FINISH', 'MUTATION', 'ERROR', 'INFO').
     * @param array<string, mixed> $context Contextual metadata for observability.
     * @return void
     */
    function log_event(string $function_name, string $phase, array $context = []): void
    {
        $timestamp = gmdate('Y-m-d\TH:i:s\Z');
        $correlation_id = get_correlation_id();
        $payload = json_encode([
            'timestamp' => $timestamp,
            'correlation_id' => $correlation_id,
            'function' => $function_name,
            'phase' => $phase,
            'context' => $context,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        error_log("[AMULET_APP] {$payload}");
    }
}

