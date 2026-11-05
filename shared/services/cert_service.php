<?php
declare(strict_types=1);

/**
 * Certificate Service - PSA-Style Grading & Anti-Counterfeit Verification Engine.
 *
 * File: shared/services/cert_service.php
 * Mutation Log: 2026-11-04 - Created PSA grading verification, zoom defect inspection, and QR generation.
 */

require_once __DIR__ . '/../utils/logger.php';
require_once __DIR__ . '/../../includes/db_connect.php';

if (!function_exists('get_certificate_by_cert_number')) {
    /**
     * Retrieves full certificate details with associated amulet and current owner data.
     *
     * @param string $cert_number Unique PSA certificate serial number.
     * @return array<string, mixed>|null Certificate record or null if not found.
     */
    function get_certificate_by_cert_number(string $cert_number): ?array
    {
        $clean_no = trim($cert_number);
        log_event('get_certificate_by_cert_number', 'START', ['cert_number' => $clean_no]);

        $pdo = get_db_connection();
        $stmt = $pdo->prepare("
            SELECT c.*, 
                   a.name AS amulet_name, a.temple_name, a.monk_name, a.year_created, a.material, 
                   a.description AS amulet_description, a.image_path AS amulet_image_path,
                   cat.name AS category_name,
                   u.username AS owner_username, u.email AS owner_email
            FROM certificates c
            JOIN amulets a ON c.amulet_id = a.id
            JOIN categories cat ON a.category_id = cat.id
            LEFT JOIN users u ON c.current_owner_id = u.id
            WHERE c.cert_number = ?
            LIMIT 1
        ");
        $stmt->execute([$clean_no]);
        $record = $stmt->fetch();

        log_event('get_certificate_by_cert_number', 'FINISH', ['found' => $record !== false]);
        return $record ?: null;
    }
}

if (!function_exists('get_certificate_by_amulet_id')) {
    /**
     * Retrieves active certificate for a specific amulet.
     *
     * @param int $amulet_id Amulet primary key.
     * @return array<string, mixed>|null Certificate record or null.
     */
    function get_certificate_by_amulet_id(int $amulet_id): ?array
    {
        log_event('get_certificate_by_amulet_id', 'START', ['amulet_id' => $amulet_id]);

        $pdo = get_db_connection();
        $stmt = $pdo->prepare("
            SELECT c.*, u.username AS owner_username
            FROM certificates c
            LEFT JOIN users u ON c.current_owner_id = u.id
            WHERE c.amulet_id = ? AND c.status = 'active'
            ORDER BY c.id DESC
            LIMIT 1
        ");
        $stmt->execute([$amulet_id]);
        $record = $stmt->fetch();

        log_event('get_certificate_by_amulet_id', 'FINISH', ['found' => $record !== false]);
        return $record ?: null;
    }
}

if (!function_exists('generate_cert_qr_url')) {
    /**
     * Generates a dynamic QR Code image URL for the certificate verification page.
     *
     * @param string $cert_number Unique PSA certificate serial number.
     * @param int    $size Pixel size of the QR code (default 180).
     * @return string Valid QR code image URL.
     */
    function generate_cert_qr_url(string $cert_number, int $size = 180): string
    {
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost:8000';
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $verify_url = "{$protocol}://{$host}/verify_cert.php?cert_no=" . urlencode($cert_number);

        // High reliability QR code generation API
        return "https://api.qrserver.com/v1/create-qr-code/?size={$size}x{$size}&data=" . urlencode($verify_url);
    }
}
