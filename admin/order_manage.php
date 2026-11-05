<?php
declare(strict_types=1);

/**
 * Admin Order & Slip Verification Management (Module 1 & 4).
 *
 * File: admin/order_manage.php
 * Mutation Log: 2026-11-04 - Enforced require_admin() auth guard.
 */

require_once __DIR__ . '/../shared/services/auth_guard.php';
require_admin('..'); // Protect area checking $_SESSION['role'] === 'admin'

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../shared/services/notification_service.php';

$pdo = get_db_connection();
$msg = null;
$error = null;

// Handle Status Updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'], $_POST['order_status'])) {
    $order_id = (int)$_POST['order_id'];
    $new_status = (string)$_POST['order_status'];
    $tracking_number = trim((string)($_POST['tracking_number'] ?? ''));

    $valid_statuses = ['pending_slip_verify', 'preparing_shipment', 'shipped', 'cancelled'];
    if (in_array($new_status, $valid_statuses, true)) {
        $stmt = $pdo->prepare("
            UPDATE orders
            SET order_status = ?, tracking_number = ?
            WHERE id = ?
        ");
        $stmt->execute([$new_status, $tracking_number !== '' ? $tracking_number : null, $order_id]);

        // Feature 2: Smart Provenance Transfer & Order Notification
        if ($new_status === 'preparing_shipment' || $new_status === 'shipped') {
            // Find order details and buyer
            $ord_info = $pdo->prepare("SELECT user_id FROM orders WHERE id = ?");
            $ord_info->execute([$order_id]);
            $buyer_id = (int)$ord_info->fetchColumn();

            $buyer_info = $pdo->prepare("SELECT full_name, username FROM users WHERE id = ?");
            $buyer_info->execute([$buyer_id]);
            $buyer = $buyer_info->fetch();
            $buyer_name = $buyer ? $buyer['full_name'] : 'ผู้ครอบครองใหม่';

            // Find all amulet items in this order
            $items_stmt = $pdo->prepare("SELECT amulet_id FROM order_items WHERE order_id = ?");
            $items_stmt->execute([$order_id]);
            $amulet_ids = $items_stmt->fetchAll(PDO::FETCH_COLUMN);

            $transferred_certs = [];
            foreach ($amulet_ids as $a_id) {
                // Check if this amulet has an active PSA certificate
                $cert_stmt = $pdo->prepare("SELECT id, cert_number, current_owner_name FROM certificates WHERE amulet_id = ? AND status = 'active'");
                $cert_stmt->execute([$a_id]);
                $cert = $cert_stmt->fetch();

                if ($cert) {
                    $old_owner = $cert['current_owner_name'];
                    // Transfer certificate ownership to the buyer!
                    $upd_cert = $pdo->prepare("
                        UPDATE certificates 
                        SET current_owner_id = ?, current_owner_name = ?
                        WHERE id = ?
                    ");
                    $upd_cert->execute([$buyer_id, $buyer_name, $cert['id']]);

                    // Record in Provenance Transfer Log
                    $log_trans = $pdo->prepare("
                        INSERT INTO provenance_transfers (certificate_id, from_owner_name, to_user_id, to_owner_name, order_id, transfer_reason)
                        VALUES (?, ?, ?, ?, ?, 'ชำระเงินเช่าบูชาเสร็จสมบูรณ์ผ่านคำสั่งซื้อ #{$order_id}')
                    ");
                    $log_trans->execute([$cert['id'], $old_owner, $buyer_id, $buyer_name, $order_id]);
                    $transferred_certs[] = $cert['cert_number'];
                }
            }

            // Send notification to buyer
            $cert_msg = !empty($transferred_certs) ? " และระบบได้โอนสิทธิ์ความเป็นเจ้าของในใบเซอร์ " . implode(', ', $transferred_certs) . " เป็นชื่อท่านเรียบร้อยแล้ว!" : "";
            create_user_notification(
                $buyer_id,
                'order_status',
                "✓ สลิปคำสั่งซื้อ #{$order_id} ได้รับการอนุมัติแล้ว",
                "ยอดชำระเงินของท่านได้รับการยืนยันแล้ว สถานะปรับเป็น: " . ($new_status === 'shipped' ? 'จัดส่งแล้ว' : 'กำลังเตรียมจัดส่ง') . $cert_msg,
                "orders.php"
            );
        }

        $msg = "อัปเดตสถานะคำสั่งซื้อ #{$order_id} เรียบร้อยแล้ว" . (!empty($transferred_certs) ? " (โอนสิทธิ์ใบเซอร์ PSA ให้ผู้ซื้อสำเร็จ)" : "");
    } else {
        $error = "สถานะคำสั่งซื้อไม่ถูกต้อง";
    }
}

// Fetch all orders with user and items count
$stmt = $pdo->query("
    SELECT o.*, u.username, u.full_name, u.phone,
           (SELECT COUNT(*) FROM order_items oi WHERE oi.order_id = o.id) AS item_count
    FROM orders o
    JOIN users u ON o.user_id = u.id
    ORDER BY o.id DESC
");
$orders = $stmt->fetchAll();

render_site_header('จัดการคำสั่งซื้อ & ตรวจสอบสลิป (Order Management)', '..');
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
    <div>
        <h1 style="color: var(--gold-accent);">จัดการคำสั่งซื้อ & ตรวจสอบสลิป</h1>
        <p style="color: var(--text-light); opacity: 0.9;">กระบวนการเปลี่ยนสถานะ: รอตรวจสอบสลิป &rarr; เตรียมจัดส่ง &rarr; จัดส่งแล้วพร้อมเลขพัสดุ</p>
    </div>
    <a href="index.php" class="btn btn_outline">&larr; กลับแดชบอร์ด</a>
</div>

<?php if ($msg): ?>
    <div style="background: rgba(82,196,26,0.2); border: 1px solid var(--success-color); color: #73d13d; padding: 0.75rem 1rem; border-radius: var(--radius-md); margin-bottom: 1.5rem;">
        ✓ <?php echo htmlspecialchars($msg, ENT_QUOTES, 'UTF-8'); ?>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div style="background: rgba(255,77,79,0.2); border: 1px solid var(--danger-color); color: #ff7875; padding: 0.75rem 1rem; border-radius: var(--radius-md); margin-bottom: 1.5rem;">
        ⚠️ <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
    </div>
<?php endif; ?>

<div style="background-color: var(--bg-card); border: 1px solid var(--gold-border); border-radius: var(--radius-lg); overflow: hidden;">
    <table class="data_table">
        <thead>
            <tr>
                <th>คำสั่งซื้อ</th>
                <th>ผู้สั่งเช่า</th>
                <th>ยอดเงิน</th>
                <th>หลักฐานสลิปโอนเงิน</th>
                <th>สถานะ & ดำเนินการ</th>
                <th>ที่อยู่จัดส่ง</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($orders)): ?>
                <tr><td colspan="6" style="text-align: center;">ยังไม่มีคำสั่งเช่าพระเครื่องในระบบ</td></tr>
            <?php else: ?>
                <?php foreach ($orders as $o): ?>
                    <tr>
                        <td>
                            <strong style="color: var(--gold-accent);">#<?php echo (int)$o['id']; ?></strong><br>
                            <span style="font-size: 0.8rem; opacity: 0.8;"><?php echo htmlspecialchars((string)$o['created_at'], ENT_QUOTES, 'UTF-8'); ?></span>
                        </td>
                        <td>
                            <strong><?php echo htmlspecialchars((string)$o['full_name'], ENT_QUOTES, 'UTF-8'); ?></strong><br>
                            <span style="font-size: 0.85rem; color: var(--gold-muted);"><?php echo htmlspecialchars((string)$o['phone'], ENT_QUOTES, 'UTF-8'); ?></span>
                        </td>
                        <td style="font-weight: 700; color: var(--gold-bright); font-size: 1.1rem;">
                            <?php echo number_format((float)$o['total_amount'], 2); ?> ฿
                        </td>
                        <td>
                            <?php if (!empty($o['slip_image_path'])): ?>
                                <a href="../<?php echo htmlspecialchars((string)$o['slip_image_path'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank" style="color: var(--gold-accent); text-decoration: underline; font-weight: bold; font-size: 0.9rem;">
                                    🔍 ดูสลิปโอนเงิน ↗
                                </a>
                            <?php else: ?>
                                <span style="opacity: 0.5;">ไม่มีสลิป</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <form method="POST" action="order_manage.php" style="margin: 0; display: flex; flex-direction: column; gap: 0.4rem;">
                                <input type="hidden" name="order_id" value="<?php echo (int)$o['id']; ?>">
                                <select name="order_status" class="form_control" style="font-size: 0.85rem; padding: 0.35rem 0.5rem;">
                                    <option value="pending_slip_verify" <?php echo $o['order_status'] === 'pending_slip_verify' ? 'selected' : ''; ?>>รอตรวจสอบสลิป</option>
                                    <option value="preparing_shipment" <?php echo $o['order_status'] === 'preparing_shipment' ? 'selected' : ''; ?>>เตรียมจัดส่ง</option>
                                    <option value="shipped" <?php echo $o['order_status'] === 'shipped' ? 'selected' : ''; ?>>จัดส่งแล้ว</option>
                                    <option value="cancelled" <?php echo $o['order_status'] === 'cancelled' ? 'selected' : ''; ?>>ยกเลิกคำสั่งซื้อ</option>
                                </select>
                                <input type="text" name="tracking_number" placeholder="เลขพัสดุ (เช่น TH123456)"
                                       value="<?php echo htmlspecialchars((string)($o['tracking_number'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                       class="form_control" style="font-size: 0.85rem; padding: 0.35rem 0.5rem;">
                                <button type="submit" class="btn btn_gold" style="padding: 0.35rem 0.6rem; font-size: 0.8rem;">
                                    อัปเดตสถานะ
                                </button>
                            </form>
                        </td>
                        <td style="font-size: 0.85rem; max-width: 220px; line-height: 1.4;">
                            <?php echo nl2br(htmlspecialchars((string)$o['shipping_address'], ENT_QUOTES, 'UTF-8')); ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php
render_site_footer('..', false);
?>
