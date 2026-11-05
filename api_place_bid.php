<?php
declare(strict_types=1);

/**
 * AJAX JSON Bidding API Endpoint (Module 2).
 *
 * File: api_place_bid.php
 * Mutation Log: 2026-11-04 - Created AJAX Fetch API endpoint for live bidding and Anti-Snipe.
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/shared/utils/session_init.php';
require_once __DIR__ . '/shared/services/auction_service.php';

// Authentication Check
if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'กรุณาเข้าสู่ระบบก่อนทำการประมูลพระเครื่อง',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$raw_input = file_get_contents('php://input');
$data = json_decode($raw_input, true);

if (!is_array($data) || empty($data['auction_id']) || empty($data['bid_amount'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'ข้อมูลการเสนอราคาไม่ถูกต้องครบถ้วน',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$auction_id = (int)$data['auction_id'];
$bid_amount = (float)$data['bid_amount'];

try {
    $result = process_auction_bid($auction_id, $user_id, $bid_amount);
    $result['user_id'] = $user_id;
    $result['username'] = $_SESSION['username'] ?? 'สมาชิก';
    $result['timestamp'] = date('c');
    echo json_encode($result, JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    http_response_code(422);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
}

