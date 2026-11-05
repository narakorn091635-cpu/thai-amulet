<?php
declare(strict_types=1);

/**
 * Admin Panel Dashboard & Navigation.
 *
 * File: admin/index.php
 * Mutation Log: 2026-11-04 - Enforced $_SESSION['role'] === 'admin' via require_admin().
 */

require_once __DIR__ . '/../shared/services/auth_guard.php';
require_admin('..'); // Enforces strict $_SESSION['role'] === 'admin' check

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/db_connect.php';

$pdo = get_db_connection();

// Metrics
$total_amulets = (int)$pdo->query("SELECT COUNT(*) FROM amulets")->fetchColumn();
$active_auctions = (int)$pdo->query("SELECT COUNT(*) FROM auctions WHERE status = 'active'")->fetchColumn();
$pending_slips = (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE order_status = 'pending_slip_verify'")->fetchColumn();
$total_orders = (int)$pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();

render_site_header('ระบบจัดการหลังบ้าน (Admin CMS)', '..');
?>

<div style="margin-bottom: 2rem;">
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
        <div>
            <h1 style="color: var(--gold-accent);">ระบบจัดการหลังบ้าน (Admin CMS)</h1>
            <p style="color: var(--text-light); opacity: 0.9;">
                ยินดีต้อนรับผู้ดูแลระบบ: <strong><?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin', ENT_QUOTES, 'UTF-8'); ?></strong>
                (สิทธิ์: <span style="color: var(--gold-bright); font-weight: bold;"><?php echo htmlspecialchars($_SESSION['role'] ?? 'admin', ENT_QUOTES, 'UTF-8'); ?></span>)
            </p>
        </div>
        <a href="../index.php" class="btn btn_outline">&larr; ดูหน้าบ้าน (Storefront)</a>
    </div>
</div>

<!-- Dashboard Stats Cards -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1.5rem; margin-bottom: 2.5rem;">
    <div style="background-color: var(--bg-card); border: 1px solid var(--gold-border); border-radius: var(--radius-lg); padding: 1.5rem;">
        <span style="font-size: 0.85rem; color: var(--text-light);">พระเครื่องทั้งหมด</span>
        <div style="font-size: 2.2rem; font-weight: 700; color: var(--gold-accent); margin-top: 0.25rem;">
            <?php echo $total_amulets; ?>
        </div>
        <a href="amulet_manage.php" style="font-size: 0.85rem; color: var(--gold-bright); text-decoration: underline;">จัดการพระเครื่อง &rarr;</a>
    </div>

    <div style="background-color: var(--bg-card); border: 1px solid var(--gold-border); border-radius: var(--radius-lg); padding: 1.5rem;">
        <span style="font-size: 0.85rem; color: var(--text-light);">รายการประมูลที่กำลังดำเนินอยู่</span>
        <div style="font-size: 2.2rem; font-weight: 700; color: #52c41a; margin-top: 0.25rem;">
            <?php echo $active_auctions; ?>
        </div>
        <a href="auction_manage.php" style="font-size: 0.85rem; color: var(--gold-bright); text-decoration: underline;">จัดการห้องประมูล &rarr;</a>
    </div>

    <div style="background-color: var(--bg-card); border: 1px solid var(--gold-border); border-radius: var(--radius-lg); padding: 1.5rem;">
        <span style="font-size: 0.85rem; color: var(--text-light);">สลิปที่รอการตรวจสอบ</span>
        <div style="font-size: 2.2rem; font-weight: 700; color: #faad14; margin-top: 0.25rem;">
            <?php echo $pending_slips; ?>
        </div>
        <a href="order_manage.php" style="font-size: 0.85rem; color: var(--gold-bright); text-decoration: underline;">ตรวจสอบสลิปโอนเงิน &rarr;</a>
    </div>

    <div style="background-color: var(--bg-card); border: 1px solid var(--gold-border); border-radius: var(--radius-lg); padding: 1.5rem;">
        <span style="font-size: 0.85rem; color: var(--text-light);">คำสั่งเช่าทั้งหมด</span>
        <div style="font-size: 2.2rem; font-weight: 700; color: var(--gold-accent); margin-top: 0.25rem;">
            <?php echo $total_orders; ?>
        </div>
        <a href="order_manage.php" style="font-size: 0.85rem; color: var(--gold-bright); text-decoration: underline;">ดูคำสั่งซื้อทั้งหมด &rarr;</a>
    </div>
</div>

<!-- Quick Actions -->
<div style="background-color: var(--bg-card); border: 1px solid var(--gold-border); border-radius: var(--radius-lg); padding: 1.75rem;">
    <h3 style="color: var(--gold-accent); margin-bottom: 1.25rem;">เมนูลัดการจัดการระบบ (Admin Actions)</h3>
    <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
        <a href="amulet_manage.php?action=create" class="btn btn_gold">+ เพิ่มรายการพระเครื่องใหม่ (อัปโหลดรูปภาพ & สแตตัส 1-10)</a>
        <a href="auction_manage.php" class="btn btn_outline">ตั้งค่าและเปิดห้องประมูล</a>
        <a href="order_manage.php" class="btn btn_outline">อนุมัติสลิปและใส่เลขพัสดุจัดส่ง</a>
    </div>
</div>

<?php
render_site_footer('..', false);
?>
