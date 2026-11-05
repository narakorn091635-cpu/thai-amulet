<?php
declare(strict_types=1);

/**
 * User Order History View.
 *
 * File: orders.php
 * Mutation Log: 2026-11-04 - Created user order history and status tracker for Module 1.
 */

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/db_connect.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$pdo = get_db_connection();
$user_id = (int)$_SESSION['user_id'];

$stmt = $pdo->prepare("
    SELECT o.*, 
           (SELECT COUNT(*) FROM order_items oi WHERE oi.order_id = o.id) AS item_count
    FROM orders o
    WHERE o.user_id = ?
    ORDER BY o.id DESC
");
$stmt->execute([$user_id]);
$orders = $stmt->fetchAll();

render_site_header('ประวัติการเช่าบูชาพระเครื่อง', '.');
?>

<div style="margin-bottom: 2rem;">
    <h1 style="color: var(--gold-accent);">ประวัติการเช่าบูชาพระเครื่อง</h1>
    <p style="color: var(--text-light); opacity: 0.9;">ติดตามสถานะการตรวจสอบสลิปและการจัดส่งพัสดุ</p>
</div>

<?php if (empty($orders)): ?>
    <div style="text-align: center; padding: 4rem 1rem; background-color: var(--bg-card); border: 1px dashed var(--gold-border); border-radius: var(--radius-lg);">
        <h3 style="color: var(--gold-accent); margin-bottom: 0.5rem;">ยังไม่มีประวัติการเช่าบูชา</h3>
        <p style="color: var(--text-light); margin-bottom: 1.5rem;">ท่านสามารถเลือกชมพระเครื่องและทำรายการเช่าได้ทันที</p>
        <a href="index.php" class="btn btn_gold">ไปที่ตลาดพระเครื่อง</a>
    </div>
<?php else: ?>
    <div style="background-color: var(--bg-card); border: 1px solid var(--gold-border); border-radius: var(--radius-lg); overflow: hidden;">
        <table class="data_table">
            <thead>
                <tr>
                    <th>รหัสคำสั่งซื้อ</th>
                    <th>วันที่ทำรายการ</th>
                    <th>ยอดรวม</th>
                    <th>สถานะคำสั่งซื้อ</th>
                    <th>เลขพัสดุจัดส่ง</th>
                    <th>หลักฐานสลิป</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $o): ?>
                    <tr>
                        <td style="font-weight: 700; color: var(--gold-accent);">#<?php echo (int)$o['id']; ?></td>
                        <td><?php echo htmlspecialchars((string)$o['created_at'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td style="font-weight: 700; color: var(--gold-bright);"><?php echo number_format((float)$o['total_amount'], 2); ?> ฿</td>
                        <td>
                            <?php
                            $status = (string)$o['order_status'];
                            if ($status === 'pending_slip_verify') {
                                echo '<span style="background: rgba(250, 173, 20, 0.2); border: 1px solid var(--warning-color); color: var(--warning-color); padding: 0.2rem 0.6rem; border-radius: 4px; font-size: 0.85rem;">รอตรวจสอบสลิป</span>';
                            } elseif ($status === 'preparing_shipment') {
                                echo '<span style="background: rgba(24, 144, 255, 0.2); border: 1px solid #40a9ff; color: #40a9ff; padding: 0.2rem 0.6rem; border-radius: 4px; font-size: 0.85rem;">เตรียมจัดส่ง</span>';
                            } elseif ($status === 'shipped') {
                                echo '<span style="background: rgba(82, 196, 26, 0.2); border: 1px solid var(--success-color); color: var(--success-color); padding: 0.2rem 0.6rem; border-radius: 4px; font-size: 0.85rem;">จัดส่งแล้ว</span>';
                            } else {
                                echo '<span style="background: rgba(255, 77, 79, 0.2); border: 1px solid var(--danger-color); color: var(--danger-color); padding: 0.2rem 0.6rem; border-radius: 4px; font-size: 0.85rem;">ยกเลิกแล้ว</span>';
                            }
                            ?>
                        </td>
                        <td>
                            <?php if (!empty($o['tracking_number'])): ?>
                                <strong style="color: var(--gold-accent); letter-spacing: 1px;"><?php echo htmlspecialchars((string)$o['tracking_number'], ENT_QUOTES, 'UTF-8'); ?></strong>
                            <?php else: ?>
                                <span style="opacity: 0.6;">- อยู่ระหว่างเตรียมการ -</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!empty($o['slip_image_path'])): ?>
                                <a href="<?php echo htmlspecialchars((string)$o['slip_image_path'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank" style="color: var(--gold-accent); text-decoration: underline; font-size: 0.85rem;">
                                    ดูสลิปที่แนบ ↗
                                </a>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php
render_site_footer('.', false);
?>

