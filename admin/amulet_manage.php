<?php
declare(strict_types=1);

/**
 * Admin Amulet CRUD, Image Upload, and 6-Attribute Status Manager (Module 3 & 4).
 *
 * File: admin/amulet_manage.php
 * Mutation Log: 2026-11-04 - Enhanced with image upload to /images/uploads/ and real-time Radar Chart preview.
 */

require_once __DIR__ . '/../shared/services/auth_guard.php';
require_admin('..'); // Protect area checking $_SESSION['role'] === 'admin'

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../shared/services/amulet_service.php';

$pdo = get_db_connection();
$categories = get_amulet_categories();
$action = $_GET['action'] ?? 'list';
$edit_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$msg = null;
$error = null;

// Handle Delete Amulet
if ($action === 'delete' && $edit_id > 0) {
    try {
        $del = $pdo->prepare("DELETE FROM amulets WHERE id = ?");
        $del->execute([$edit_id]);
        $msg = "ลบรายการพระเครื่อง #{$edit_id} เรียบร้อยแล้ว";
        $action = 'list';
    } catch (Exception $e) {
        $error = "ไม่สามารถลบพระเครื่องรายการนี้ได้ เนื่องจากมีประวัติคำสั่งซื้อหรือการประมูลผูกอยู่";
        $action = 'list';
    }
}

// Fetch single record if editing
$edit_amulet = null;
if ($action === 'edit' && $edit_id > 0) {
    $edit_amulet = get_amulet_by_id($edit_id);
    if (!$edit_amulet) {
        $error = "ไม่พบข้อมูลพระเครื่อง #{$edit_id}";
        $action = 'list';
    }
}

// Handle Form Submission: Create or Update (With Image Upload)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim((string)($_POST['name'] ?? ''));
    $category_id = (int)($_POST['category_id'] ?? 1);
    $temple_name = trim((string)($_POST['temple_name'] ?? ''));
    $monk_name = trim((string)($_POST['monk_name'] ?? ''));
    $year_created = trim((string)($_POST['year_created'] ?? ''));
    $material = trim((string)($_POST['material'] ?? ''));
    $description = trim((string)($_POST['description'] ?? ''));
    $price = (float)($_POST['price'] ?? 0.0);
    $stock_qty = (int)($_POST['stock_qty'] ?? 1);
    $listing_type = ($_POST['listing_type'] ?? '') === 'auction' ? 'auction' : 'marketplace';

    // 6 Status Attributes (Enforced Range: min 1, max 10)
    $stat_protection = max(1, min(10, (int)($_POST['stat_protection'] ?? 8)));
    $stat_kindness = max(1, min(10, (int)($_POST['stat_kindness'] ?? 8)));
    $stat_charm = max(1, min(10, (int)($_POST['stat_charm'] ?? 7)));
    $stat_evasion = max(1, min(10, (int)($_POST['stat_evasion'] ?? 8)));
    $stat_invincibility = max(1, min(10, (int)($_POST['stat_invincibility'] ?? 7)));
    $stat_wealth = max(1, min(10, (int)($_POST['stat_wealth'] ?? 8)));

    // Preserve existing image by default if editing
    $image_path = $edit_amulet['image_path'] ?? null;

    // Process Image Upload to /images/uploads/
    if (!empty($_FILES['amulet_image']['name']) && $_FILES['amulet_image']['error'] === UPLOAD_ERR_OK) {
        $file_tmp = $_FILES['amulet_image']['tmp_name'];
        $file_name = $_FILES['amulet_image']['name'];
        $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $allowed_exts = ['jpg', 'jpeg', 'png', 'webp'];

        // MIME type validation
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $file_tmp);
        finfo_close($finfo);
        $allowed_mimes = ['image/jpeg', 'image/png', 'image/webp'];

        if (!in_array($ext, $allowed_exts, true) || !in_array($mime_type, $allowed_mimes, true)) {
            $error = 'ไฟล์รูปภาพไม่ถูกต้องหรือไฟล์ไม่ปลอดภัย รองรับเฉพาะรูปภาพ JPG, PNG, WEBP เท่านั้น';
        } else {
            $upload_dir = __DIR__ . '/../images/uploads';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            $new_filename = 'amulet_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            $destination = $upload_dir . '/' . $new_filename;

            if (move_uploaded_file($file_tmp, $destination)) {
                $image_path = 'images/uploads/' . $new_filename;
            } else {
                $error = 'ไม่สามารถบันทึกไฟล์รูปภาพพระเครื่องได้';
            }
        }
    }

    if ($name === '' || $temple_name === '' || $monk_name === '') {
        $error = 'กรุณากรอกชื่อพระเครื่อง วัด และเกจิอาจารย์ให้ครบถ้วน';
    } elseif (!$error) {
        if ($edit_id > 0) {
            // Update Existing Amulet
            $stmt = $pdo->prepare("
                UPDATE amulets SET
                    category_id = ?, name = ?, temple_name = ?, monk_name = ?, year_created = ?,
                    material = ?, description = ?, price = ?, stock_qty = ?, listing_type = ?,
                    image_path = ?, stat_protection = ?, stat_kindness = ?, stat_charm = ?,
                    stat_evasion = ?, stat_invincibility = ?, stat_wealth = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $category_id, $name, $temple_name, $monk_name, $year_created,
                $material, $description, $price, $stock_qty, $listing_type,
                $image_path, $stat_protection, $stat_kindness, $stat_charm,
                $stat_evasion, $stat_invincibility, $stat_wealth, $edit_id
            ]);

            // Sync auction table if listing_type is auction
            if ($listing_type === 'auction') {
                $auction_start_price = $price > 0 ? $price : 1000.0;
                $min_inc = max(50.0, round($auction_start_price * 0.05, -1));
                $now_str = date('Y-m-d H:i:s');
                $end_str = date('Y-m-d H:i:s', strtotime('+3 days'));

                $auc_stmt = $pdo->prepare("
                    INSERT INTO auctions (amulet_id, start_price, current_price, min_increment, start_time, end_time, status)
                    VALUES (?, ?, ?, ?, ?, ?, 'active')
                    ON DUPLICATE KEY UPDATE status = 'active'
                ");
                $auc_stmt->execute([$edit_id, $auction_start_price, $auction_start_price, $min_inc, $now_str, $end_str]);
            }

            $msg = "อัปเดตข้อมูลพระเครื่อง #{$edit_id} และสแตตัสพุทธคุณเรียบร้อยแล้ว";
            $action = 'list';
        } else {
            // Insert New Amulet
            $stmt = $pdo->prepare("
                INSERT INTO amulets (
                    category_id, name, temple_name, monk_name, year_created, material, description,
                    price, stock_qty, listing_type, image_path,
                    stat_protection, stat_kindness, stat_charm, stat_evasion,
                    stat_invincibility, stat_wealth
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $category_id, $name, $temple_name, $monk_name, $year_created, $material, $description,
                $price, $stock_qty, $listing_type, $image_path,
                $stat_protection, $stat_kindness, $stat_charm, $stat_evasion,
                $stat_invincibility, $stat_wealth
            ]);
            $new_amulet_id = (int)$pdo->lastInsertId();

            // If listing_type is auction, automatically create active auction room
            if ($listing_type === 'auction') {
                $auction_start_price = $price > 0 ? $price : 1000.0;
                $min_inc = max(50.0, round($auction_start_price * 0.05, -1)); // 5% minimum increment
                $now_str = date('Y-m-d H:i:s');
                $end_str = date('Y-m-d H:i:s', strtotime('+3 days'));
                
                $auc_stmt = $pdo->prepare("
                    INSERT INTO auctions (amulet_id, start_price, current_price, min_increment, start_time, end_time, status)
                    VALUES (?, ?, ?, ?, ?, ?, 'active')
                    ON DUPLICATE KEY UPDATE start_price = VALUES(start_price), current_price = VALUES(current_price), status = 'active'
                ");
                $auc_stmt->execute([$new_amulet_id, $auction_start_price, $auction_start_price, $min_inc, $now_str, $end_str]);
            }

            $msg = "เพิ่มรายการพระเครื่อง #{$new_amulet_id} พร้อมบันทึกภาพถ่ายและสแตตัสพุทธคุณสำเร็จ" . ($listing_type === 'auction' ? " (เปิดห้องประมูลเรียบร้อยแล้ว)" : "");
            $action = 'list';
        }
    }
}

// Fetch all amulets for listing
$amulet_list = $pdo->query("
    SELECT a.*, c.name AS category_name
    FROM amulets a
    JOIN categories c ON a.category_id = c.id
    ORDER BY a.id DESC
")->fetchAll();

render_site_header('จัดการพระเครื่อง & สแตตัส (Amulet CMS)', '..');
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
    <div>
        <h1 style="color: var(--gold-accent);">จัดการพระเครื่อง & สแตตัสพุทธคุณ</h1>
        <p style="color: var(--text-light); opacity: 0.9;">เพิ่ม ลบ แก้ไข พระเครื่อง, อัปโหลดภาพถ่ายลง <code>/images/uploads/</code> และปรับค่าพลัง 6 ด้าน (1-10)</p>
    </div>
    <div style="display: flex; gap: 0.5rem;">
        <a href="index.php" class="btn btn_outline">&larr; กลับแดชบอร์ด</a>
        <?php if ($action !== 'create'): ?>
            <a href="amulet_manage.php?action=create" class="btn btn_gold">+ เพิ่มพระเครื่องใหม่</a>
        <?php endif; ?>
    </div>
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

<?php if ($action === 'create' || $action === 'edit'): ?>
    <?php
    $cur_protection = (int)($edit_amulet['stat_protection'] ?? 8);
    $cur_kindness = (int)($edit_amulet['stat_kindness'] ?? 8);
    $cur_charm = (int)($edit_amulet['stat_charm'] ?? 7);
    $cur_evasion = (int)($edit_amulet['stat_evasion'] ?? 8);
    $cur_invincibility = (int)($edit_amulet['stat_invincibility'] ?? 7);
    $cur_wealth = (int)($edit_amulet['stat_wealth'] ?? 8);
    ?>

    <!-- Form: Add / Edit Amulet with Image Upload & Live Radar Preview -->
    <div style="background-color: var(--bg-card); border: 1px solid var(--gold-border); border-radius: var(--radius-lg); padding: 2rem; max-width: 1000px; margin: 0 auto;">
        <h2 style="color: var(--gold-accent); margin-bottom: 1.5rem;">
            <?php echo $action === 'edit' ? 'แก้ไขข้อมูลพระเครื่อง #' . $edit_id : 'เพิ่มรายการพระเครื่องใหม่ (Add Amulet)'; ?>
        </h2>

        <form method="POST" action="amulet_manage.php<?php echo $action === 'edit' ? '?action=edit&id=' . $edit_id : '?action=create'; ?>" enctype="multipart/form-data">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.25rem; margin-bottom: 1.5rem;">
                <div>
                    <label style="display: block; font-size: 0.9rem; color: var(--gold-accent); margin-bottom: 0.35rem;">ชื่อพระเครื่อง *</label>
                    <input type="text" name="name" required class="form_control" style="width: 100%;"
                           placeholder="เช่น พระสมเด็จวัดระฆัง พิมพ์ใหญ่"
                           value="<?php echo htmlspecialchars((string)($edit_amulet['name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                </div>

                <div>
                    <label style="display: block; font-size: 0.9rem; color: var(--gold-accent); margin-bottom: 0.35rem;">หมวดหมู่ *</label>
                    <select name="category_id" class="form_control" style="width: 100%;">
                        <?php foreach ($categories as $c): ?>
                            <option value="<?php echo (int)$c['id']; ?>" <?php echo ((int)($edit_amulet['category_id'] ?? 1) === (int)$c['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars((string)$c['name'], ENT_QUOTES, 'UTF-8'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label style="display: block; font-size: 0.9rem; color: var(--gold-accent); margin-bottom: 0.35rem;">วัดที่จัดสร้าง *</label>
                    <input type="text" name="temple_name" required class="form_control" style="width: 100%;"
                           placeholder="เช่น วัดระฆังโฆสิตาราม"
                           value="<?php echo htmlspecialchars((string)($edit_amulet['temple_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                </div>

                <div>
                    <label style="display: block; font-size: 0.9rem; color: var(--gold-accent); margin-bottom: 0.35rem;">เกจิอาจารย์ผู้ปลุกเสก *</label>
                    <input type="text" name="monk_name" required class="form_control" style="width: 100%;"
                           placeholder="เช่น สมเด็จพระพุฒาจารย์ (โต พรหมรังสี)"
                           value="<?php echo htmlspecialchars((string)($edit_amulet['monk_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                </div>

                <div>
                    <label style="display: block; font-size: 0.9rem; color: var(--gold-accent); margin-bottom: 0.35rem;">ปีที่จัดสร้าง (พ.ศ.)</label>
                    <input type="text" name="year_created" class="form_control" style="width: 100%;"
                           placeholder="เช่น 2410"
                           value="<?php echo htmlspecialchars((string)($edit_amulet['year_created'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                </div>

                <div>
                    <label style="display: block; font-size: 0.9rem; color: var(--gold-accent); margin-bottom: 0.35rem;">เนื้อมวลสาร / วัสดุ</label>
                    <input type="text" name="material" class="form_control" style="width: 100%;"
                           placeholder="เช่น เนื้อผงพุทธคุณ, เนื้อทองแดง"
                           value="<?php echo htmlspecialchars((string)($edit_amulet['material'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                </div>

                <div>
                    <label style="display: block; font-size: 0.9rem; color: var(--gold-accent); margin-bottom: 0.35rem;">ราคาบูชา (บาท)</label>
                    <input type="number" step="100" min="0" name="price" class="form_control" style="width: 100%;"
                           value="<?php echo (float)($edit_amulet['price'] ?? 1000.0); ?>">
                </div>

                <div>
                    <label style="display: block; font-size: 0.9rem; color: var(--gold-accent); margin-bottom: 0.35rem;">จำนวนสต็อก (องค์)</label>
                    <input type="number" min="0" name="stock_qty" class="form_control" style="width: 100%;"
                           value="<?php echo (int)($edit_amulet['stock_qty'] ?? 1); ?>">
                </div>

                <div>
                    <label style="display: block; font-size: 0.9rem; color: var(--gold-accent); margin-bottom: 0.35rem;">ประเภทการวางจำหน่าย</label>
                    <select name="listing_type" class="form_control" style="width: 100%;">
                        <option value="marketplace" <?php echo (($edit_amulet['listing_type'] ?? '') === 'marketplace') ? 'selected' : ''; ?>>เช่าบูชาปกติ (Marketplace)</option>
                        <option value="auction" <?php echo (($edit_amulet['listing_type'] ?? '') === 'auction') ? 'selected' : ''; ?>>เปิดห้องประมูล (Auction)</option>
                    </select>
                </div>

                <!-- File Upload Logic -->
                <div>
                    <label style="display: block; font-size: 0.9rem; color: var(--gold-accent); margin-bottom: 0.35rem;">รูปภาพพระเครื่อง (บันทึกลง <code>/images/uploads/</code>)</label>
                    <input type="file" name="amulet_image" accept="image/*" class="form_control" style="width: 100%;">
                    <?php if (!empty($edit_amulet['image_path'])): ?>
                        <div style="margin-top: 0.5rem; font-size: 0.85rem; color: var(--text-light);">
                            รูปปัจจุบัน: <a href="../<?php echo htmlspecialchars((string)$edit_amulet['image_path'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank" style="color: var(--gold-accent); text-decoration: underline;">คลิกเพื่อดูภาพเดิม ↗</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div style="margin-bottom: 1.5rem;">
                <label style="display: block; font-size: 0.9rem; color: var(--gold-accent); margin-bottom: 0.35rem;">คำอธิบายและประวัติพุทธคุณพระเครื่อง</label>
                <textarea name="description" rows="3" class="form_control" style="width: 100%;"><?php echo htmlspecialchars((string)($edit_amulet['description'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
            </div>

            <!-- 6 Number Inputs (min 1, max 10) with Live Radar Chart Preview -->
            <div style="background-color: var(--bg-darker); border: 1px solid var(--gold-border); border-radius: var(--radius-md); padding: 1.5rem; margin-bottom: 1.75rem;">
                <h3 style="color: var(--gold-accent); font-size: 1.15rem; margin-bottom: 0.5rem;">
                    ⚜️ กำหนดค่าพลังพุทธคุณ 6 ด้าน (Input min 1, max 10)
                </h3>
                <p style="font-size: 0.85rem; color: var(--text-light); margin-bottom: 1.25rem; opacity: 0.85;">
                    ปรับคะแนนเพื่อวาดกราฟ Radar Chart หกเหลี่ยมสีทอง (#E9D793) ตัดพื้นหลังสีแดงเข้ม (#5e030f)
                </p>

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem; align-items: center;">
                    <!-- 6 Attribute Number Inputs -->
                    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem;">
                        <div>
                            <label style="display: block; font-size: 0.85rem; color: var(--gold-bright); margin-bottom: 0.25rem;">1. พุทธคุณ (Protection)</label>
                            <input type="number" id="input_stat_protection" name="stat_protection" min="1" max="10" required class="form_control stat_input" style="width: 100%;"
                                   value="<?php echo $cur_protection; ?>" oninput="update_live_radar_preview();">
                        </div>
                        <div>
                            <label style="display: block; font-size: 0.85rem; color: var(--gold-bright); margin-bottom: 0.25rem;">2. เมตตามหานิยม (Kindness)</label>
                            <input type="number" id="input_stat_kindness" name="stat_kindness" min="1" max="10" required class="form_control stat_input" style="width: 100%;"
                                   value="<?php echo $cur_kindness; ?>" oninput="update_live_radar_preview();">
                        </div>
                        <div>
                            <label style="display: block; font-size: 0.85rem; color: var(--gold-bright); margin-bottom: 0.25rem;">3. มหาเสน่ห์ (Charm)</label>
                            <input type="number" id="input_stat_charm" name="stat_charm" min="1" max="10" required class="form_control stat_input" style="width: 100%;"
                                   value="<?php echo $cur_charm; ?>" oninput="update_live_radar_preview();">
                        </div>
                        <div>
                            <label style="display: block; font-size: 0.85rem; color: var(--gold-bright); margin-bottom: 0.25rem;">4. แคล้วคลาด (Evasion)</label>
                            <input type="number" id="input_stat_evasion" name="stat_evasion" min="1" max="10" required class="form_control stat_input" style="width: 100%;"
                                   value="<?php echo $cur_evasion; ?>" oninput="update_live_radar_preview();">
                        </div>
                        <div>
                            <label style="display: block; font-size: 0.85rem; color: var(--gold-bright); margin-bottom: 0.25rem;">5. คงกระพันชาตรี (Invincibility)</label>
                            <input type="number" id="input_stat_invincibility" name="stat_invincibility" min="1" max="10" required class="form_control stat_input" style="width: 100%;"
                                   value="<?php echo $cur_invincibility; ?>" oninput="update_live_radar_preview();">
                        </div>
                        <div>
                            <label style="display: block; font-size: 0.85rem; color: var(--gold-bright); margin-bottom: 0.25rem;">6. โชคลาภ (Wealth)</label>
                            <input type="number" id="input_stat_wealth" name="stat_wealth" min="1" max="10" required class="form_control stat_input" style="width: 100%;"
                                   value="<?php echo $cur_wealth; ?>" oninput="update_live_radar_preview();">
                        </div>
                    </div>

                    <!-- Live Radar Chart Canvas Preview Target -->
                    <div style="text-align: center; background: var(--bg-dark); border: 1px solid var(--gold-border); border-radius: var(--radius-md); padding: 1rem;">
                        <span style="font-size: 0.85rem; color: var(--gold-accent); display: block; margin-bottom: 0.5rem; font-weight: bold;">
                            👁️ ตัวอย่างกราฟสแตตัสแบบ Real-time
                        </span>
                        <canvas id="admin_preview_radar" width="340" height="340" style="max-width: 100%; height: auto;"></canvas>
                    </div>
                </div>
            </div>

            <div style="display: flex; gap: 1rem;">
                <button type="submit" class="btn btn_gold" style="padding: 0.85rem 2.5rem; font-size: 1.05rem;">
                    บันทึกข้อมูลพระเครื่อง
                </button>
                <a href="amulet_manage.php" class="btn btn_outline">ยกเลิก</a>
            </div>
        </form>
    </div>

    <script>
    function update_live_radar_preview() {
        const p = Number(document.getElementById('input_stat_protection').value) || 1;
        const k = Number(document.getElementById('input_stat_kindness').value) || 1;
        const c = Number(document.getElementById('input_stat_charm').value) || 1;
        const e = Number(document.getElementById('input_stat_evasion').value) || 1;
        const i = Number(document.getElementById('input_stat_invincibility').value) || 1;
        const w = Number(document.getElementById('input_stat_wealth').value) || 1;

        const canvas = document.getElementById('admin_preview_radar');
        if (canvas && typeof draw_amulet_radar_chart === 'function') {
            draw_amulet_radar_chart(canvas, [p, k, c, e, i, w]);
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        update_live_radar_preview();
    });
    </script>
<?php else: ?>
    <!-- Amulet List Table -->
    <div style="background-color: var(--bg-card); border: 1px solid var(--gold-border); border-radius: var(--radius-lg); overflow: hidden;">
        <table class="data_table">
            <thead>
                <tr>
                    <th>รูปภาพ</th>
                    <th>ID / ชื่อพระเครื่อง</th>
                    <th>หมวดหมู่</th>
                    <th>วัด / เกจิอาจารย์</th>
                    <th>ราคา</th>
                    <th>ประเภท</th>
                    <th>สแตตัส 6 ด้าน</th>
                    <th>จัดการ</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($amulet_list)): ?>
                    <tr><td colspan="8" style="text-align: center;">ยังไม่มีรายการพระเครื่องในระบบ กดปุ่ม + เพิ่มพระเครื่องใหม่ เพื่อเริ่มต้น</td></tr>
                <?php else: ?>
                    <?php foreach ($amulet_list as $a): ?>
                        <tr>
                            <td style="width: 80px; text-align: center;">
                                <?php if (!empty($a['image_path'])): ?>
                                    <img src="../<?php echo htmlspecialchars((string)$a['image_path'], ENT_QUOTES, 'UTF-8'); ?>"
                                         alt="Amulet" style="width: 60px; height: 60px; object-fit: cover; border-radius: 4px; border: 1px solid var(--gold-border);">
                                <?php else: ?>
                                    <div style="width: 60px; height: 60px; display: flex; align-items: center; justify-content: center; background: #2a0005; border: 1px dashed var(--gold-muted); border-radius: 4px; font-size: 1.5rem; color: var(--gold-accent);">
                                        ⚜️
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong style="color: var(--gold-accent); font-size: 1.05rem;">
                                    #<?php echo (int)$a['id']; ?> <?php echo htmlspecialchars((string)$a['name'], ENT_QUOTES, 'UTF-8'); ?>
                                </strong>
                                <div style="font-size: 0.8rem; color: var(--text-light); opacity: 0.8;">
                                    สร้างปี: <?php echo htmlspecialchars((string)($a['year_created'] ?? 'ไม่ระบุ'), ENT_QUOTES, 'UTF-8'); ?> | มวลสาร: <?php echo htmlspecialchars((string)($a['material'] ?? 'ไม่ระบุ'), ENT_QUOTES, 'UTF-8'); ?>
                                </div>
                            </td>
                            <td><?php echo htmlspecialchars((string)$a['category_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td>
                                <?php echo htmlspecialchars((string)$a['temple_name'], ENT_QUOTES, 'UTF-8'); ?><br>
                                <span style="font-size: 0.85rem; color: var(--gold-muted);"><?php echo htmlspecialchars((string)$a['monk_name'], ENT_QUOTES, 'UTF-8'); ?></span>
                            </td>
                            <td style="font-weight: 700; color: var(--gold-bright); font-size: 1.1rem;">
                                <?php echo number_format((float)$a['price'], 2); ?> ฿
                            </td>
                            <td>
                                <?php echo $a['listing_type'] === 'auction' ? '<span style="background: rgba(250, 173, 20, 0.2); color: #faad14; padding: 0.2rem 0.5rem; border-radius: 4px; font-size: 0.8rem;">ประมูล</span>' : '<span style="background: rgba(82, 196, 26, 0.2); color: #52c41a; padding: 0.2rem 0.5rem; border-radius: 4px; font-size: 0.8rem;">ตลาดเช่า</span>'; ?>
                            </td>
                            <td style="font-size: 0.8rem; font-family: monospace; line-height: 1.4;">
                                พุท:<strong><?php echo $a['stat_protection']; ?></strong> | เมต:<strong><?php echo $a['stat_kindness']; ?></strong> | เสน่ห์:<strong><?php echo $a['stat_charm']; ?></strong><br>
                                แคล้ว:<strong><?php echo $a['stat_evasion']; ?></strong> | คง:<strong><?php echo $a['stat_invincibility']; ?></strong> | โชค:<strong><?php echo $a['stat_wealth']; ?></strong>
                            </td>
                            <td>
                                <div style="display: flex; gap: 0.35rem;">
                                    <a href="amulet_manage.php?action=edit&id=<?php echo (int)$a['id']; ?>" class="btn btn_outline" style="padding: 0.35rem 0.65rem; font-size: 0.85rem;">แก้ไข</a>
                                    <a href="amulet_manage.php?action=delete&id=<?php echo (int)$a['id']; ?>" class="btn btn_outline"
                                       style="color: var(--danger-color); border-color: var(--danger-color); padding: 0.35rem 0.65rem; font-size: 0.85rem;"
                                       onclick="return confirm('ยืนยันลบรายการพระเครื่อง #<?php echo $a['id']; ?> หรือไม่?');">ลบ</a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php
render_site_footer('..', false);
?>
