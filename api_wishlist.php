<?php
declare(strict_types=1);

/**
 * AJAX API: Toggle Wishlist & Follow Monk.
 *
 * File: api_wishlist.php
 * Mutation Log: 2026-11-05 - Created AJAX endpoint for wishlist and monk follow toggles.
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/shared/utils/session_init.php';
require_once __DIR__ . '/shared/services/wishlist_service.php';

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'กรุณาเข้าสู่ระบบก่อนทำรายการ'], JSON_UNESCAPED_UNICODE);
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$raw = file_get_contents('php://input');
$json_data = json_decode($raw, true);
$data = is_array($json_data) ? $json_data : $_POST;

$action = $data['action'] ?? ($_POST['action'] ?? '');

if ($action === 'toggle_wishlist' && !empty($data['amulet_id'])) {
    $amulet_id = (int)$data['amulet_id'];
    $is_added = toggle_amulet_wishlist($user_id, $amulet_id);
    echo json_encode([
        'success' => true,
        'added' => $is_added,
        'is_in_wishlist' => $is_added,
        'message' => $is_added ? 'บันทึกเข้าพระที่ถูกใจแล้ว' : 'นำออกจากพระที่ถูกใจแล้ว',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($action === 'toggle_follow_monk' && !empty($data['monk_name'])) {
    $monk_name = (string)$data['monk_name'];
    $is_followed = toggle_follow_monk($user_id, $monk_name);
    echo json_encode([
        'success' => true,
        'followed' => $is_followed,
        'is_following' => $is_followed,
        'message' => $is_followed ? "ติดตาม {$monk_name} เรียบร้อยแล้ว" : "ยกเลิกการติดตาม {$monk_name} แล้ว",
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid action'], JSON_UNESCAPED_UNICODE);
