<?php
declare(strict_types=1);

/**
 * User Member: Submit New Amulet for Rent or Auction.
 *
 * File: member_add_amulet.php
 * Mutation Log: 2026-11-05 - Built member listing submission form with radar power stats & image upload.
 */

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/footer.php';
require_once __DIR__ . '/shared/services/auth_guard.php';
require_once __DIR__ . '/shared/services/amulet_service.php';

require_login('.');

$user_id = (int)($_SESSION['user_id'] ?? 0);
$username = (string)($_SESSION['username'] ?? 'สมาชิก');
$categories = get_amulet_categories();

$error_msg = null;
$success_msg = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim((string)($_POST['name'] ?? ''));
    $category_id = (int)($_POST['category_id'] ?? 1);
    $temple_name = trim((string)($_POST['temple_name'] ?? ''));
    $monk_name = trim((string)($_POST['monk_name'] ?? ''));
    $province = trim((string)($_POST['province'] ?? 'กรุงเทพมหานคร'));
    $year_created = trim((string)($_POST['year_created'] ?? ''));
    $model_edition = trim((string)($_POST['model_edition'] ?? ''));
    $material_type = trim((string)($_POST['material_type'] ?? 'metal'));
    $material = trim((string)($_POST['material'] ?? ''));
    $price = (float)($_POST['price'] ?? 0);
    $stock_qty = max(1, (int)($_POST['stock_qty'] ?? 1));
    $listing_type = in_array($_POST['listing_type'] ?? '', ['marketplace', 'auction'], true) ? (string)$_POST['listing_type'] : 'marketplace';
    $description = trim((string)($_POST['description'] ?? ''));

    // Status Radar Scores (1 to 10)
    $stat_protection = min(10, max(1, (int)($_POST['stat_protection'] ?? 8)));
    $stat_kindness = min(10, max(1, (int)($_POST['stat_kindness'] ?? 8)));
    $stat_charm = min(10, max(1, (int)($_POST['stat_charm'] ?? 7)));
    $stat_evasion = min(10, max(1, (int)($_POST['stat_evasion'] ?? 8)));
    $stat_invincibility = min(10, max(1, (int)($_POST['stat_invincibility'] ?? 7)));
    $stat_wealth = min(10, max(1, (int)($_POST['stat_wealth'] ?? 8)));

    if ($name === '' || $temple_name === '' || $monk_name === '' || $price <= 0) {
        $error_msg = 'กรุณากรอกชื่อพระเครื่อง, วัด, เกจิอาจารย์ และราคาปล่อยเช่าให้ถูกต้อง';
    } else {
        $image_path = 'images/sample_somdej.png'; // default fallback

        // Handle Image Upload
        if (!empty($_FILES['amulet_image']['name']) && $_FILES['amulet_image']['error'] === UPLOAD_ERR_OK) {
            $file_tmp = $_FILES['amulet_image']['tmp_name'];
            $file_name = $_FILES['amulet_image']['name'];
            $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            $allowed_exts = ['jpg', 'jpeg', 'png', 'webp'];

            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime_type = finfo_file($finfo, $file_tmp);
            finfo_close($finfo);
            $allowed_mimes = ['image/jpeg', 'image/png', 'image/webp'];

            if (!in_array($ext, $allowed_exts, true) || !in_array($mime_type, $allowed_mimes, true)) {
                $error_msg = 'ประเภทไฟล์ไม่ถูกต้อง รองรับเฉพาะรูปภาพ JPG, PNG, WEBP เท่านั้น';
            } else {
                $upload_dir = __DIR__ . '/images/uploads';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                $new_filename = 'user_amulet_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                $destination = $upload_dir . '/' . $new_filename;

                if (move_uploaded_file($file_tmp, $destination)) {
                    $image_path = 'images/uploads/' . $new_filename;
                }
            }
        }

        if (!$error_msg) {
            $pdo = get_db_connection();
            $stmt = $pdo->prepare("
                INSERT INTO amulets (
                    category_id, name, temple_name, province, monk_name, year_created,
                    model_edition, material, material_type, description, price, stock_qty,
                    listing_type, image_path, stat_protection, stat_kindness, stat_charm,
                    stat_evasion, stat_invincibility, stat_wealth, seller_id, is_active
                ) VALUES (
                    ?, ?, ?, ?, ?, ?,
                    ?, ?, ?, ?, ?, ?,
                    ?, ?, ?, ?, ?,
                    ?, ?, ?, ?, 1
                )
            ");
            $stmt->execute([
                $category_id, $name, $temple_name, $province, $monk_name, $year_created,
                $model_edition, $material, $material_type, $description, $price, $stock_qty,
                $listing_type, $image_path, $stat_protection, $stat_kindness, $stat_charm,
                $stat_evasion, $stat_invincibility, $stat_wealth, $user_id
            ]);
            $new_amulet_id = (int)$pdo->lastInsertId();

            // Automatically add to user personal collection vault
            require_once __DIR__ . '/shared/services/collection_service.php';
            add_amulet_to_collection($user_id, $new_amulet_id, $price, 'ลงปล่อยเช่าโดย ' . $username);

            $success_msg = "ลงประกาศปล่อยพระเครื่อง '{$name}' สำเร็จเรียบร้อยแล้ว!";
        }
    }
}

render_site_header('ลงประกาศปล่อยพระเครื่อง / นำพระเข้ากรุ', '.');
?>

<div style="max-width: 800px; margin: 0 auto; padding-bottom: 3rem;">
    <div style="margin-bottom: 1.5rem;">
        <a href="collection_showcase.php" style="color: var(--gold-accent); font-size: 0.9rem;">&larr; กลับหน้ารวมกรุพระของฉัน</a>
    </div>

    <div style="background: var(--bg-card); border: 1.5px solid var(--gold-border); border-radius: var(--radius-lg); padding: 2rem; box-shadow: var(--shadow-gold);">
        <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 1.5rem; border-bottom: 1px solid var(--gold-muted); padding-bottom: 1rem;">
            <span style="font-size: 2.2rem;">⚜️</span>
            <div>
                <h1 style="color: var(--gold-accent); font-size: 1.6rem; margin: 0;">ลงประกาศปล่อยเช่าพระเครื่อง</h1>
                <p style="color: var(--text-muted); font-size: 0.85rem; margin: 0.2rem 0 0;">
                    สมาชิกสามารถนำพระเครื่องเข้าสู่ตลาดเพื่อปล่อยเช่า หรือเก็บสะสมในกรุพระส่วนตัวได้ทันที
                </p>
            </div>
        </div>

        <?php if ($success_msg): ?>
            <div style="background: rgba(82, 196, 26, 0.2); border: 1px solid var(--success-color); color: #73d13d; padding: 1rem; border-radius: var(--radius-md); margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: center;">
                <span>✓ <?php echo htmlspecialchars($success_msg, ENT_QUOTES, 'UTF-8'); ?></span>
                <a href="index.php" class="btn btn_gold" style="font-size: 0.8rem; padding: 0.35rem 0.85rem;">ดูในตลาดพระ ↗</a>
            </div>
        <?php endif; ?>

        <?php if ($error_msg): ?>
            <div style="background: rgba(255, 77, 79, 0.2); border: 1px solid var(--danger-color); color: #ff7875; padding: 1rem; border-radius: var(--radius-md); margin-bottom: 1.5rem;">
                ✕ <?php echo htmlspecialchars($error_msg, ENT_QUOTES, 'UTF-8'); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="member_add_amulet.php" enctype="multipart/form-data">
            <!-- Row 1: Name & Category -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1rem; margin-bottom: 1.25rem;">
                <div>
                    <label style="display: block; font-size: 0.85rem; margin-bottom: 0.4rem; color: var(--gold-bright);">ชื่อองค์พระเครื่อง <span style="color: var(--danger-color);">*</span></label>
                    <input type="text" name="name" class="form_control" style="width: 100%;" placeholder="เช่น พระสมเด็จวัดระฆัง, เหรียญหลวงพ่อรวย" required>
                </div>
                <div>
                    <label style="display: block; font-size: 0.85rem; margin-bottom: 0.4rem; color: var(--gold-bright);">หมวดหมู่พระเครื่อง</label>
                    <select name="category_id" class="form_control" style="width: 100%;">
                        <?php foreach ($categories as $c): ?>
                            <option value="<?php echo (int)$c['id']; ?>"><?php echo htmlspecialchars((string)$c['name'], ENT_QUOTES, 'UTF-8'); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- Row 2: Temple & Monk -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1rem; margin-bottom: 1.25rem;">
                <div>
                    <label style="display: block; font-size: 0.85rem; margin-bottom: 0.4rem; color: var(--gold-bright);">วัดที่จัดสร้าง <span style="color: var(--danger-color);">*</span></label>
                    <input type="text" name="temple_name" class="form_control" style="width: 100%;" placeholder="เช่น วัดระฆังโฆสิตาราม, วัดตะโก" required>
                </div>
                <div>
                    <label style="display: block; font-size: 0.85rem; margin-bottom: 0.4rem; color: var(--gold-bright);">เกจิอาจารย์ผู้ปลุกเสก <span style="color: var(--danger-color);">*</span></label>
                    <input type="text" name="monk_name" class="form_control" style="width: 100%;" placeholder="เช่น สมเด็จพระพุฒาจารย์ (โต), หลวงพ่อรวย" required>
                </div>
            </div>

            <!-- Row 3: Province, Edition, Year -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem; margin-bottom: 1.25rem;">
                <div>
                    <label style="display: block; font-size: 0.85rem; margin-bottom: 0.4rem; color: var(--gold-bright);">จังหวัด</label>
                    <input type="text" name="province" class="form_control" style="width: 100%;" placeholder="เช่น กรุงเทพฯ, อยุธยา" value="กรุงเทพมหานคร">
                </div>
                <div>
                    <label style="display: block; font-size: 0.85rem; margin-bottom: 0.4rem; color: var(--gold-bright);">รุ่น / พิมพ์ทรง</label>
                    <input type="text" name="model_edition" class="form_control" style="width: 100%;" placeholder="เช่น พิมพ์ใหญ่, รุ่นแรก">
                </div>
                <div>
                    <label style="display: block; font-size: 0.85rem; margin-bottom: 0.4rem; color: var(--gold-bright);">ปีสร้าง (พ.ศ.)</label>
                    <input type="text" name="year_created" class="form_control" style="width: 100%;" placeholder="เช่น 2410, 2556">
                </div>
            </div>

            <!-- Row 4: Material & Price -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1.25rem;">
                <div>
                    <label style="display: block; font-size: 0.85rem; margin-bottom: 0.4rem; color: var(--gold-bright);">สเปกเนื้อพระ</label>
                    <select name="material_type" class="form_control" style="width: 100%;">
                        <option value="powder">เนื้อผง / พุทธคุณ / เกสร</option>
                        <option value="clay">เนื้อดิน / ดินเผาโบราณ</option>
                        <option value="chin">เนื้อชิน / ชินเงิน</option>
                        <option value="metal" selected>เนื้อโลหะ / ทองแดง / กะไหล่ทอง</option>
                        <option value="other">เครื่องราง / อื่นๆ</option>
                    </select>
                </div>
                <div>
                    <label style="display: block; font-size: 0.85rem; margin-bottom: 0.4rem; color: var(--gold-bright);">ราคาเช่าบูชา (บาท) <span style="color: var(--danger-color);">*</span></label>
                    <input type="number" step="100" name="price" class="form_control" style="width: 100%; font-weight: bold; color: var(--gold-accent);" placeholder="เช่น 25000" required>
                </div>
                <div>
                    <label style="display: block; font-size: 0.85rem; margin-bottom: 0.4rem; color: var(--gold-bright);">ประเภทการปล่อย</label>
                    <select name="listing_type" class="form_control" style="width: 100%;">
                        <option value="marketplace">ตลาดเช่าบูชาทั่วไป</option>
                        <option value="auction">เปิดประมูลพระเครื่อง</option>
                    </select>
                </div>
            </div>

            <!-- Image Upload -->
            <div style="margin-bottom: 1.5rem; background: rgba(0,0,0,0.3); padding: 1.25rem; border-radius: var(--radius-md); border: 1px dashed var(--gold-border);">
                <label style="display: block; font-size: 0.9rem; font-weight: 600; margin-bottom: 0.5rem; color: var(--gold-accent);">
                    📸 อัปโหลดรูปภาพองค์พระ (ด้านหน้า/หลัง คมชัด)
                </label>
                <input type="file" name="amulet_image" accept="image/jpeg,image/png,image/webp" class="form_control" style="width: 100%;">
                <span style="font-size: 0.75rem; color: var(--text-muted); display: block; margin-top: 0.35rem;">
                    รองรับไฟล์ JPG, PNG, WEBP (ขนาดไม่เกิน 5MB)
                </span>
            </div>

            <!-- Description -->
            <div style="margin-bottom: 1.5rem;">
                <label style="display: block; font-size: 0.85rem; margin-bottom: 0.4rem; color: var(--gold-bright);">ประวัติและรายละเอียดเพิ่มเติม</label>
                <textarea name="description" class="form_control" rows="3" style="width: 100%;" placeholder="อธิบายสภาพองค์พระ พุทธคุณ หรือจุดตำหนิที่น่าสนใจ..."></textarea>
            </div>

            <!-- Power Radar Stats (คะแนนพุทธคุณ 1-10) -->
            <div style="background: rgba(233, 215, 147, 0.05); border: 1px solid var(--gold-muted); border-radius: var(--radius-md); padding: 1.25rem; margin-bottom: 1.5rem;">
                <h4 style="color: var(--gold-accent); margin-bottom: 0.75rem; font-size: 0.95rem;">
                    ⚡ ค่าพลังพุทธคุณ 6 ด้าน (สเกล 1 ถึง 10)
                </h4>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(110px, 1fr)); gap: 0.75rem;">
                    <div>
                        <label style="font-size: 0.75rem; color: var(--text-muted); display: block;">พุทธคุณ</label>
                        <input type="number" min="1" max="10" name="stat_protection" value="8" class="form_control" style="width: 100%; text-align: center;">
                    </div>
                    <div>
                        <label style="font-size: 0.75rem; color: var(--text-muted); display: block;">เมตตา</label>
                        <input type="number" min="1" max="10" name="stat_kindness" value="8" class="form_control" style="width: 100%; text-align: center;">
                    </div>
                    <div>
                        <label style="font-size: 0.75rem; color: var(--text-muted); display: block;">เสน่ห์</label>
                        <input type="number" min="1" max="10" name="stat_charm" value="7" class="form_control" style="width: 100%; text-align: center;">
                    </div>
                    <div>
                        <label style="font-size: 0.75rem; color: var(--text-muted); display: block;">แคล้วคลาด</label>
                        <input type="number" min="1" max="10" name="stat_evasion" value="8" class="form_control" style="width: 100%; text-align: center;">
                    </div>
                    <div>
                        <label style="font-size: 0.75rem; color: var(--text-muted); display: block;">คงกระพัน</label>
                        <input type="number" min="1" max="10" name="stat_invincibility" value="7" class="form_control" style="width: 100%; text-align: center;">
                    </div>
                    <div>
                        <label style="font-size: 0.75rem; color: var(--text-muted); display: block;">โชคลาภ</label>
                        <input type="number" min="1" max="10" name="stat_wealth" value="8" class="form_control" style="width: 100%; text-align: center;">
                    </div>
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 0.75rem;">
                <a href="collection_showcase.php" class="btn btn_outline">ยกเลิก</a>
                <button type="submit" class="btn btn_gold" style="padding: 0.7rem 2rem; font-weight: 700;">
                    ✓ ยืนยันการลงประกาศปล่อยพระ
                </button>
            </div>
        </form>
    </div>
</div>

<?php
render_site_footer('.', false);
?>
