<?php
declare(strict_types=1);

/**
 * Admin Auction & Timer Management (Module 2 & 4).
 *
 * File: admin/auction_manage.php
 * Mutation Log: 2026-11-04 - Enforced require_admin() auth guard.
 */

require_once __DIR__ . '/../shared/services/auth_guard.php';
require_admin('..'); // Protect area checking $_SESSION['role'] === 'admin'

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/db_connect.php';

$pdo = get_db_connection();
$msg = null;
$error = null;

// Handle New Auction Creation or Status Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $amulet_id = (int)($_POST['amulet_id'] ?? 0);
        $start_price = (float)($_POST['start_price'] ?? 1000.0);
        $min_increment = (float)($_POST['min_increment'] ?? 100.0);
        $duration_minutes = (int)($_POST['duration_minutes'] ?? 60);

        if ($amulet_id <= 0 || $start_price <= 0) {
            $error = 'กรุณาเลือกพระเครื่องและระบุราคาเริ่มต้นประมูล';
        } else {
            $start_time = gmdate('Y-m-d H:i:s');
            $end_time = gmdate('Y-m-d H:i:s', time() + ($duration_minutes * 60));

            // Mark amulet as auction type
            $pdo->prepare("UPDATE amulets SET listing_type = 'auction' WHERE id = ?")->execute([$amulet_id]);

            $stmt = $pdo->prepare("
                INSERT INTO auctions (amulet_id, start_price, current_price, min_increment, start_time, end_time, status)
                VALUES (?, ?, ?, ?, ?, ?, 'active')
            ");
            $stmt->execute([$amulet_id, $start_price, $start_price, $min_increment, $start_time, $end_time]);
            $msg = 'เปิดห้องประมูลพระเครื่องเรียบร้อยแล้ว';
        }
    } elseif ($action === 'close') {
        $auction_id = (int)($_POST['auction_id'] ?? 0);
        if ($auction_id > 0) {
            $pdo->prepare("UPDATE auctions SET status = 'ended' WHERE id = ?")->execute([$auction_id]);
            $msg = "ปิดการประมูลห้อง #{$auction_id} เรียบร้อยแล้ว";
        }
    }
}

// Available amulets for auction
$available_amulets = $pdo->query("
    SELECT a.id, a.name, a.temple_name, a.monk_name, a.price
    FROM amulets a
    LEFT JOIN auctions auc ON a.id = auc.amulet_id AND auc.status = 'active'
    WHERE auc.id IS NULL
    ORDER BY a.id DESC
")->fetchAll();

// Active and past auctions
$auction_list = $pdo->query("
    SELECT auc.*, a.name AS amulet_name, u.username AS highest_bidder_name,
           (SELECT COUNT(*) FROM bids b WHERE b.auction_id = auc.id) AS total_bids
    FROM auctions auc
    JOIN amulets a ON auc.amulet_id = a.id
    LEFT JOIN users u ON auc.highest_bidder_id = u.id
    ORDER BY auc.id DESC
")->fetchAll();

render_site_header('จัดการห้องประมูล & ตั้งค่าเวลา (Auction CMS)', '..');
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
    <div>
        <h1 style="color: var(--gold-accent);">จัดการห้องประมูล & ตั้งค่าเวลา</h1>
        <p style="color: var(--text-light); opacity: 0.9;">เปิดรอบประมูล, กำหนดราคาเริ่มต้น, ขั้นต่ำการเคาะ, และเวลานับถอยหลัง</p>
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

<!-- Create New Auction Box -->
<div style="background-color: var(--bg-card); border: 1px solid var(--gold-border); border-radius: var(--radius-lg); padding: 1.5rem; margin-bottom: 2rem;">
    <h3 style="color: var(--gold-accent); margin-bottom: 1rem;">+ เปิดห้องประมูลใหม่</h3>
    <form method="POST" action="auction_manage.php" style="margin: 0;">
        <input type="hidden" name="action" value="create">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1rem; align-items: flex-end;">
            <div>
                <label style="display: block; font-size: 0.85rem; color: var(--gold-accent); margin-bottom: 0.25rem;">เลือกพระเครื่องที่ต้องการเปิดประมูล</label>
                <select name="amulet_id" required class="form_control" style="width: 100%;">
                    <option value="">-- เลือกรายการพระเครื่อง --</option>
                    <?php foreach ($available_amulets as $am): ?>
                        <option value="<?php echo (int)$am['id']; ?>">
                            #<?php echo $am['id']; ?> <?php echo htmlspecialchars((string)$am['name'], ENT_QUOTES, 'UTF-8'); ?> (<?php echo htmlspecialchars((string)$am['temple_name'], ENT_QUOTES, 'UTF-8'); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label style="display: block; font-size: 0.85rem; color: var(--gold-accent); margin-bottom: 0.25rem;">ราคาเริ่มต้น (บาท)</label>
                <input type="number" name="start_price" step="100" min="100" value="1000" required class="form_control" style="width: 100%;">
            </div>

            <div>
                <label style="display: block; font-size: 0.85rem; color: var(--gold-accent); margin-bottom: 0.25rem;">ขั้นต่ำเพิ่มครั้งละ (บาท)</label>
                <input type="number" name="min_increment" step="50" min="50" value="100" required class="form_control" style="width: 100%;">
            </div>

            <div>
                <label style="display: block; font-size: 0.85rem; color: var(--gold-accent); margin-bottom: 0.25rem;">ระยะเวลาประมูล (นาที)</label>
                <select name="duration_minutes" class="form_control" style="width: 100%;">
                    <option value="15">15 นาที (ประมูลด่วน)</option>
                    <option value="60" selected>1 ชั่วโมง (มาตรฐาน)</option>
                    <option value="180">3 ชั่วโมง</option>
                    <option value="1440">24 ชั่วโมง (1 วัน)</option>
                    <option value="4320">3 วัน</option>
                </select>
            </div>

            <div>
                <button type="submit" class="btn btn_gold" style="width: 100%; padding: 0.75rem;">
                    เปิดห้องประมูล
                </button>
            </div>
        </div>
    </form>
</div>

<!-- Auction List Table -->
<div style="background-color: var(--bg-card); border: 1px solid var(--gold-border); border-radius: var(--radius-lg); overflow: hidden;">
    <table class="data_table">
        <thead>
            <tr>
                <th>ห้องประมูล</th>
                <th>พระเครื่อง</th>
                <th>ราคาปัจจุบัน</th>
                <th>ผู้เสนอราคาสูงสุด</th>
                <th>เคาะแล้ว (ครั้ง)</th>
                <th>เวลาสิ้นสุด</th>
                <th>Anti-Snipe</th>
                <th>สถานะ / จัดการ</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($auction_list)): ?>
                <tr><td colspan="8" style="text-align: center;">ยังไม่มีรายการประมูลพระเครื่อง</td></tr>
            <?php else: ?>
                <?php foreach ($auction_list as $auc): ?>
                    <tr>
                        <td style="font-weight: 700; color: var(--gold-accent);">#<?php echo (int)$auc['id']; ?></td>
                        <td>
                            <strong><?php echo htmlspecialchars((string)$auc['amulet_name'], ENT_QUOTES, 'UTF-8'); ?></strong>
                        </td>
                        <td style="font-weight: 700; color: var(--gold-bright); font-size: 1.1rem;">
                            <?php echo number_format((float)$auc['current_price'], 2); ?> ฿
                        </td>
                        <td>
                            <?php echo htmlspecialchars((string)($auc['highest_bidder_name'] ?? 'ยังไม่มีผู้เสนอ'), ENT_QUOTES, 'UTF-8'); ?>
                        </td>
                        <td><?php echo (int)$auc['total_bids']; ?> ครั้ง</td>
                        <td style="font-size: 0.85rem;">
                            <?php echo htmlspecialchars((string)$auc['end_time'], ENT_QUOTES, 'UTF-8'); ?>
                        </td>
                        <td>
                            <?php if ((int)$auc['anti_snipe_count'] > 0): ?>
                                <span style="color: var(--danger-color); font-weight: bold;">บวก <?php echo (int)$auc['anti_snipe_count'] * 3; ?> นาที</span>
                            <?php else: ?>
                                <span style="opacity: 0.6;">0 ครั้ง</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($auc['status'] === 'active'): ?>
                                <form method="POST" action="auction_manage.php" style="margin: 0; display: inline;">
                                    <input type="hidden" name="action" value="close">
                                    <input type="hidden" name="auction_id" value="<?php echo (int)$auc['id']; ?>">
                                    <button type="submit" class="btn btn_outline" style="color: var(--danger-color); border-color: var(--danger-color); padding: 0.3rem 0.6rem; font-size: 0.8rem;" onclick="return confirm('ยืนยันปิดการประมูลห้องนี้ทันทีหรือไม่?');">
                                        ปิดประมูล
                                    </button>
                                </form>
                            <?php else: ?>
                                <span style="opacity: 0.6;">ปิดแล้ว</span>
                            <?php endif; ?>
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
