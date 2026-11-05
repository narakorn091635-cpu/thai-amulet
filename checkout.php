<?php
declare(strict_types=1);

/**
 * Checkout & Bank Slip Upload View.
 *
 * File: checkout.php
 * Mutation Log: 2026-11-04 - Created Checkout and Slip Upload logic for Module 1.
 */

require_once __DIR__ . '/shared/utils/session_init.php';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/shared/services/amulet_service.php';
require_once __DIR__ . '/includes/db_connect.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect to login if user is not authenticated
if (empty($_SESSION['user_id'])) {
    $_SESSION['redirect_after_login'] = 'checkout.php';
    header('Location: login.php');
    exit;
}

if (empty($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    header('Location: index.php');
    exit;
}

$pdo = get_db_connection();
$user_id = (int)$_SESSION['user_id'];
$error_message = null;
$success_order_id = null;

// Calculate Cart Totals
$cart_items = [];
$grand_total = 0.0;
foreach ($_SESSION['cart'] as $id => $quantity) {
    $item = get_amulet_by_id((int)$id);
    if ($item) {
        $subtotal = (float)$item['price'] * (int)$quantity;
        $grand_total += $subtotal;
        $cart_items[] = [
            'amulet' => $item,
            'quantity' => (int)$quantity,
            'unit_price' => (float)$item['price'],
            'subtotal' => $subtotal,
        ];
    }
}

// Handle Order Placement with Slip Upload
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $shipping_address = trim((string)($_POST['shipping_address'] ?? ''));

    if ($shipping_address === '') {
        $error_message = 'กรุณาระบุที่อยู่สำหรับจัดส่งพัสดุ';
    } elseif (empty($_FILES['slip_image']['name']) || $_FILES['slip_image']['error'] !== UPLOAD_ERR_OK) {
        $error_message = 'กรุณาอัปโหลดรูปภาพสลิปการโอนเงินเพื่อยืนยันคำสั่งซื้อ';
    } else {
        $file_tmp = $_FILES['slip_image']['tmp_name'];
        $file_name = $_FILES['slip_image']['name'];
        $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $allowed_exts = ['jpg', 'jpeg', 'png', 'webp'];

        // MIME type validation for bulletproof security against polyglot files
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $file_tmp);
        finfo_close($finfo);
        $allowed_mimes = ['image/jpeg', 'image/png', 'image/webp'];

        if (!in_array($ext, $allowed_exts, true) || !in_array($mime_type, $allowed_mimes, true)) {
            $error_message = 'ประเภทไฟล์ไม่ถูกต้องหรือไฟล์ไม่ปลอดภัย รองรับเฉพาะรูปภาพ JPG, PNG, WEBP เท่านั้น';
        } else {
            $upload_dir = __DIR__ . '/images/uploads';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            $target_file = 'slip_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            $destination = $upload_dir . '/' . $target_file;
            $relative_slip_path = 'images/uploads/' . $target_file;

            if (move_uploaded_file($file_tmp, $destination)) {
                try {
                    $pdo->beginTransaction();

                    $stmt = $pdo->prepare("
                        INSERT INTO orders (user_id, total_amount, slip_image_path, shipping_address, order_status)
                        VALUES (?, ?, ?, ?, 'pending_slip_verify')
                    ");
                    $stmt->execute([$user_id, $grand_total, $relative_slip_path, $shipping_address]);
                    $order_id = (int)$pdo->lastInsertId();

                    $item_stmt = $pdo->prepare("
                        INSERT INTO order_items (order_id, amulet_id, quantity, unit_price)
                        VALUES (?, ?, ?, ?)
                    ");
                    foreach ($cart_items as $entry) {
                        $item_stmt->execute([
                            $order_id,
                            (int)$entry['amulet']['id'],
                            (int)$entry['quantity'],
                            (float)$entry['unit_price'],
                        ]);
                    }

                    $pdo->commit();
                    $_SESSION['cart'] = [];
                    $success_order_id = $order_id;
                } catch (Exception $e) {
                    $pdo->rollBack();
                    $error_message = 'เกิดข้อผิดพลาดในการบันทึกคำสั่งซื้อ: ' . $e->getMessage();
                }
            } else {
                $error_message = 'ไม่สามารถบันทึกไฟล์สลิปได้ กรุณาลองใหม่อีกครั้ง';
            }
        }
    }
}

render_site_header('แจ้งชำระเงินและสั่งเช่าพระ', '.');
?>

<div style="margin-bottom: 2rem;">
    <h1 style="color: var(--gold-accent);">แจ้งชำระเงินและยืนยันการเช่าพระ</h1>
    <p style="color: var(--text-light); opacity: 0.9;">กรุณาโอนเงินตามยอดรวมและแนบสลิปเพื่อส่งให้แอดมินตรวจสอบ</p>
</div>

<?php if ($success_order_id !== null): ?>
    <div style="background-color: var(--bg-card); border: 2px solid var(--gold-accent); border-radius: var(--radius-lg); padding: 3rem 1.5rem; text-align: center; max-width: 650px; margin: 0 auto;">
        <div style="font-size: 3.5rem; margin-bottom: 1rem;">🙏 ⚜️</div>
        <h2 style="color: var(--gold-accent); margin-bottom: 0.75rem;">บันทึกคำสั่งเช่าพระเครื่องสำเร็จ!</h2>
        <p style="font-size: 1.1rem; color: var(--text-light); margin-bottom: 1rem;">
            หมายเลขคำสั่งเช่า: <strong>#<?php echo $success_order_id; ?></strong>
        </p>
        <p style="color: var(--gold-bright); background: rgba(0,0,0,0.3); padding: 0.75rem; border-radius: var(--radius-md); display: inline-block; margin-bottom: 2rem;">
            สถานะปัจจุบัน: <strong>รอตรวจสอบสลิป (Pending Verification)</strong>
        </p>
        <div style="display: flex; gap: 1rem; justify-content: center;">
            <a href="orders.php" class="btn btn_gold">ดูประวัติการสั่งเช่า</a>
            <a href="index.php" class="btn btn_outline">กลับไปหน้าแรก</a>
        </div>
    </div>
<?php else: ?>
    <?php if ($error_message): ?>
        <div style="background-color: rgba(255, 77, 79, 0.2); border: 1px solid var(--danger-color); color: #ff7875; padding: 1rem; border-radius: var(--radius-md); margin-bottom: 1.5rem;">
            ⚠️ <?php echo htmlspecialchars($error_message, ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 2rem; align-items: start;">
        <!-- Left Column: Bank Account Details & Slip Upload Form -->
        <div style="background-color: var(--bg-card); border: 1px solid var(--gold-border); border-radius: var(--radius-lg); padding: 1.5rem;">
            <h3 style="color: var(--gold-accent); margin-bottom: 1rem;">ข้อมูลบัญชีสำหรับโอนเงินบูชา</h3>
            <div style="background-color: var(--bg-darker); border: 1px dashed var(--gold-border); border-radius: var(--radius-md); padding: 1.25rem; margin-bottom: 1.5rem;">
                <p style="color: var(--text-light); margin-bottom: 0.35rem;"><strong>ธนาคาร:</strong> กสิกรไทย (KBANK)</p>
                <p style="color: var(--text-light); margin-bottom: 0.35rem;"><strong>เลขที่บัญชี:</strong> <span style="color: var(--gold-accent); font-weight: 700; font-size: 1.15rem;">123-4-56789-0</span></p>
                <p style="color: var(--text-light); margin-bottom: 0.35rem;"><strong>ชื่อบัญชี:</strong> บจก. สยามพุทธคุณมหาบารมี</p>
                <p style="color: var(--gold-bright); margin-top: 0.5rem; font-size: 0.9rem;">* กรุณาโอนเงินยอดตรงกับยอดชำระทั้งสิ้น</p>
            </div>

            <form method="POST" action="checkout.php" enctype="multipart/form-data">
                <div style="margin-bottom: 1.25rem;">
                    <label for="shipping_address" style="display: block; font-size: 0.9rem; color: var(--gold-accent); margin-bottom: 0.35rem;">ที่อยู่จัดส่งพระเครื่อง (พร้อมเบอร์ติดต่อ)</label>
                    <textarea id="shipping_address" name="shipping_address" class="form_control" rows="4" style="width: 100%;" required placeholder="ระบุชื่อ-นามสกุล, บ้านเลขที่, แขวง/ตำบล, เขต/อำเภอ, จังหวัด, รหัสไปรษณีย์ และเบอร์โทรศัพท์"><?php echo htmlspecialchars($_POST['shipping_address'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
                </div>

                <div style="margin-bottom: 1.75rem;">
                    <label for="slip_image" style="display: block; font-size: 0.9rem; color: var(--gold-accent); margin-bottom: 0.35rem;">แนบรูปสลิปหลักฐานการโอนเงิน (JPG / PNG)</label>
                    <input type="file" id="slip_image" name="slip_image" accept="image/*" class="form_control" style="width: 100%;" required>
                </div>

                <button type="submit" class="btn btn_gold" style="width: 100%; padding: 0.9rem; font-size: 1.1rem;">
                    ยืนยันการชำระเงินและส่งคำสั่งซื้อ
                </button>
            </form>
        </div>

        <!-- Right Column: Order Summary -->
        <div style="background-color: var(--bg-card); border: 1px solid var(--gold-border); border-radius: var(--radius-lg); padding: 1.5rem;">
            <h3 style="color: var(--gold-accent); margin-bottom: 1rem;">สรุปรายการเช่าบูชา</h3>
            <div style="margin-bottom: 1.5rem;">
                <?php foreach ($cart_items as $item): ?>
                    <div style="display: flex; justify-content: space-between; padding: 0.75rem 0; border-bottom: 1px solid var(--gold-muted);">
                        <div>
                            <div style="font-weight: 700; color: var(--text-light);"><?php echo htmlspecialchars((string)$item['amulet']['name'], ENT_QUOTES, 'UTF-8'); ?></div>
                            <div style="font-size: 0.85rem; color: var(--gold-accent); opacity: 0.85;">จำนวน: <?php echo (int)$item['quantity']; ?> องค์</div>
                        </div>
                        <div style="font-weight: 700; color: var(--gold-accent);">
                            <?php echo number_format((float)$item['subtotal'], 2); ?> ฿
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div style="display: flex; justify-content: space-between; align-items: center; pt: 1rem; border-top: 2px solid var(--gold-accent);">
                <span style="font-size: 1.1rem; color: var(--text-light); font-weight: 700;">ยอดชำระทั้งสิ้น</span>
                <span style="font-size: 1.8rem; color: var(--gold-accent); font-weight: 700;"><?php echo number_format($grand_total, 2); ?> ฿</span>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php
render_site_footer('.', false);
?>

