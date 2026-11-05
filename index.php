<?php
declare(strict_types=1);

/**
 * Amulet Marketplace Home & Catalog Filter View.
 *
 * File: index.php
 * Mutation Log: 2026-11-05 - Redesigned with Royal Siam Gold Hero Section & Premium Glassmorphic Cards.
 */

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/shared/services/amulet_service.php';
require_once __DIR__ . '/shared/services/wishlist_service.php';

$current_user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
$category_id = isset($_GET['category_id']) && is_numeric($_GET['category_id']) ? (int)$_GET['category_id'] : null;
$keyword = isset($_GET['keyword']) ? (string)$_GET['keyword'] : null;
$monk_name = isset($_GET['monk_name']) ? (string)$_GET['monk_name'] : null;
$province = isset($_GET['province']) ? (string)$_GET['province'] : null;
$material_type = isset($_GET['material_type']) ? (string)$_GET['material_type'] : null;
$model_edition = isset($_GET['model_edition']) ? (string)$_GET['model_edition'] : null;

$categories = get_amulet_categories();
$amulets = search_marketplace_amulets(
    $category_id,
    $keyword,
    $monk_name,
    null,
    $province,
    $material_type,
    $model_edition
);

$has_active_filters = !empty($category_id) || !empty($keyword) || !empty($monk_name) || !empty($province) || !empty($material_type) || !empty($model_edition);

render_site_header('ศูนย์รวมพระเครื่องยอดนิยม (Amulet Marketplace)', '.');
?>

<!-- Luxury Hero Showcase Section -->
<section class="hero_section">
    <div class="hero_badge">
        <span>✨</span> Siam Authentic Sacred Collection <span>✨</span>
    </div>
    <h1 class="hero_title">ศูนย์รวมพระเครื่องและวัตถุมงคลยอดนิยม</h1>
    <p class="hero_subtitle">
        เช่าบูชาพระแท้มาตรฐานสากล ตรวจสอบประวัติได้ด้วยระบบสแกน PSA Certificate พร้อมรับประกันความแท้ตลอดชีพ
    </p>

    <div class="hero_stats">
        <div class="hero_stat_item">
            <strong>100%</strong>
            <span>รับประกันพระแท้</span>
        </div>
        <div class="hero_stat_item">
            <strong>PSA GRADE</strong>
            <span>มาตรฐานสากล</span>
        </div>
        <div class="hero_stat_item">
            <strong>LIVE</strong>
            <span>ประมูลเรียลไทม์</span>
        </div>
        <div class="hero_stat_item">
            <strong>PROVENANCE</strong>
            <span>โอนกรรมสิทธิ์ทันที</span>
        </div>
    </div>
</section>

<!-- Deep Multi-Faceted Filter Bar -->
<form method="GET" action="index.php" class="filter_panel">
    <div class="filter_header">
        <div style="display: flex; align-items: center; gap: 0.6rem;">
            <span style="font-size: 1.25rem;">🔍</span>
            <span style="font-weight: 700; color: var(--gold-accent); font-size: 1.05rem; letter-spacing: 0.5px;">
                ระบบค้นหา & คัดกรองสเปกพระเครื่องเชิงลึก
            </span>
        </div>
        <?php if ($has_active_filters): ?>
            <a href="index.php" class="btn btn_outline" style="font-size: 0.8rem; padding: 0.35rem 0.85rem;">✕ ล้างตัวกรองทั้งหมด</a>
        <?php endif; ?>
    </div>

    <!-- Row 1: Keyword, Category, Material -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1.25rem; margin-bottom: 1.25rem;">
        <div>
            <label for="keyword" style="display: block; font-size: 0.85rem; margin-bottom: 0.45rem; color: var(--gold-accent); font-weight: 500;">คำค้นหาทั่วไป (ชื่อพระ, วัด, เกจิ)</label>
            <input type="text" id="keyword" name="keyword" class="form_control" style="width: 100%;"
                   placeholder="เช่น หลวงพ่อรวย, พระสมเด็จ..."
                   value="<?php echo htmlspecialchars($keyword ?? '', ENT_QUOTES, 'UTF-8'); ?>">
        </div>

        <div>
            <label for="category_id" style="display: block; font-size: 0.85rem; margin-bottom: 0.45rem; color: var(--gold-accent); font-weight: 500;">หมวดหมู่พระเครื่อง</label>
            <select id="category_id" name="category_id" class="form_control" style="width: 100%;">
                <option value="">-- ทั้งหมดทุกหมวดหมู่ --</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo (int)$cat['id']; ?>" <?php echo $category_id === (int)$cat['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars((string)$cat['name'], ENT_QUOTES, 'UTF-8'); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <label for="material_type" style="display: block; font-size: 0.85rem; margin-bottom: 0.45rem; color: var(--gold-accent); font-weight: 500;">เนื้อพระ (สเปกวัสดุมวลสาร)</label>
            <select id="material_type" name="material_type" class="form_control" style="width: 100%;">
                <option value="">-- ทุกประเภทเนื้อพระ --</option>
                <option value="powder" <?php echo $material_type === 'powder' ? 'selected' : ''; ?>>เนื้อผง / พุทธคุณ / เกสร</option>
                <option value="clay" <?php echo $material_type === 'clay' ? 'selected' : ''; ?>>เนื้อดิน / ดินเผาโบราณ</option>
                <option value="chin" <?php echo $material_type === 'chin' ? 'selected' : ''; ?>>เนื้อชิน / ชินเงิน / ชินเขียว</option>
                <option value="metal" <?php echo $material_type === 'metal' ? 'selected' : ''; ?>>เนื้อโลหะ / ทองแดง / กะไหล่ทอง / นวโลหะ</option>
                <option value="other" <?php echo $material_type === 'other' ? 'selected' : ''; ?>>เครื่องราง / เชือกถัก / อื่นๆ</option>
            </select>
        </div>
    </div>

    <!-- Row 2: Monk Name, Province, Edition -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.25rem; margin-bottom: 1.5rem;">
        <div>
            <label for="monk_name" style="display: block; font-size: 0.85rem; margin-bottom: 0.45rem; color: var(--gold-accent); font-weight: 500;">เกจิอาจารย์</label>
            <input type="text" id="monk_name" name="monk_name" class="form_control" style="width: 100%;"
                   placeholder="เช่น หลวงปู่ดู่, หลวงพ่อกลั่น, หลวงปู่บุญ"
                   value="<?php echo htmlspecialchars($monk_name ?? '', ENT_QUOTES, 'UTF-8'); ?>">
        </div>

        <div>
            <label for="province" style="display: block; font-size: 0.85rem; margin-bottom: 0.45rem; color: var(--gold-accent); font-weight: 500;">จังหวัดที่ตั้งวัด</label>
            <input type="text" id="province" name="province" class="form_control" style="width: 100%;"
                   placeholder="เช่น กรุงเทพมหานคร, พระนครศรีอยุธยา"
                   value="<?php echo htmlspecialchars($province ?? '', ENT_QUOTES, 'UTF-8'); ?>">
        </div>

        <div>
            <label for="model_edition" style="display: block; font-size: 0.85rem; margin-bottom: 0.45rem; color: var(--gold-accent); font-weight: 500;">รุ่น / พิมพ์ทรงเจาะจง</label>
            <input type="text" id="model_edition" name="model_edition" class="form_control" style="width: 100%;"
                   placeholder="เช่น พิมพ์ใหญ่, รุ่นแรก, เปิดโลก"
                   value="<?php echo htmlspecialchars($model_edition ?? '', ENT_QUOTES, 'UTF-8'); ?>">
        </div>
    </div>

    <div style="display: flex; justify-content: flex-end; gap: 0.75rem;">
        <button type="submit" class="btn btn_gold" style="padding: 0.7rem 2.2rem;">
            🔍 กรองค้นหาสเปกพระเครื่อง
        </button>
    </div>
</form>

<!-- Amulet Catalog Grid -->
<?php if (empty($amulets)): ?>
    <div style="text-align: center; padding: 4rem 1.5rem; background: var(--bg-card); border: 1px dashed var(--gold-border); border-radius: var(--radius-lg);">
        <div style="font-size: 3rem; margin-bottom: 1rem;">⚜️</div>
        <h3 style="color: var(--gold-accent); margin-bottom: 0.5rem; font-family: var(--font-heading);">ไม่พบรายการพระเครื่องตามเงื่อนไข</h3>
        <p style="color: var(--text-muted); margin-bottom: 1.5rem;">ท่านสามารถลองค้นหาด้วยคำอื่น หรือกดล้างการค้นหาเพื่อดูรายการทั้งหมด</p>
        <a href="index.php" class="btn btn_gold">ดูพระเครื่องทั้งหมด</a>
    </div>
<?php else: ?>
    <div class="amulet_grid">
        <?php foreach ($amulets as $item): ?>
            <?php 
                $is_fav = ($current_user_id > 0) ? is_amulet_in_wishlist($current_user_id, (int)$item['id']) : false;
            ?>
            <div class="amulet_card">
                <div class="amulet_card_media">
                    <span class="amulet_cat_badge">
                        <?php echo htmlspecialchars((string)$item['category_name'], ENT_QUOTES, 'UTF-8'); ?>
                    </span>

                    <button type="button" class="amulet_quick_wish" 
                            title="<?php echo $is_fav ? 'นำออกจากที่ชอบ' : 'บันทึกเป็นพระที่ชอบ'; ?>"
                            onclick="handleQuickWishlist(event, <?php echo (int)$item['id']; ?>, this)">
                        <?php echo $is_fav ? '❤️' : '🤍'; ?>
                    </button>

                    <?php if (!empty($item['image_path'])): ?>
                        <img src="<?php echo htmlspecialchars((string)$item['image_path'], ENT_QUOTES, 'UTF-8'); ?>"
                             alt="<?php echo htmlspecialchars((string)$item['name'], ENT_QUOTES, 'UTF-8'); ?>"
                             onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                        <div style="display: none; height: 100%; width: 100%; align-items: center; justify-content: center; font-size: 4rem; color: var(--gold-accent);">
                            ⚜️
                        </div>
                    <?php else: ?>
                        <div style="height: 100%; width: 100%; display: flex; align-items: center; justify-content: center; font-size: 4rem; color: var(--gold-accent);">
                            ⚜️
                        </div>
                    <?php endif; ?>
                </div>

                <div class="amulet_card_body">
                    <div>
                        <h3 class="amulet_title" title="<?php echo htmlspecialchars((string)$item['name'], ENT_QUOTES, 'UTF-8'); ?>">
                            <?php echo htmlspecialchars((string)$item['name'], ENT_QUOTES, 'UTF-8'); ?>
                        </h3>
                        <div class="amulet_specs">
                            <div class="amulet_spec_row">
                                <span>🏛️</span>
                                <div>วัด: <strong><?php echo htmlspecialchars((string)$item['temple_name'], ENT_QUOTES, 'UTF-8'); ?></strong></div>
                            </div>
                            <div class="amulet_spec_row">
                                <span>🙏</span>
                                <div>เกจิ: <strong><?php echo htmlspecialchars((string)$item['monk_name'], ENT_QUOTES, 'UTF-8'); ?></strong></div>
                            </div>
                            <div class="amulet_spec_row">
                                <span>🏷️</span>
                                <div>พิมพ์: <strong><?php echo htmlspecialchars((string)($item['model_edition'] ?? 'มาตรฐาน'), ENT_QUOTES, 'UTF-8'); ?></strong> (จ.<?php echo htmlspecialchars((string)($item['province'] ?? 'อยุธยา'), ENT_QUOTES, 'UTF-8'); ?>)</div>
                            </div>
                            <div class="amulet_spec_row">
                                <span>⏳</span>
                                <div>ปีสร้าง: <strong><?php echo htmlspecialchars((string)($item['year_created'] ?? 'ไม่ระบุ'), ENT_QUOTES, 'UTF-8'); ?></strong></div>
                            </div>
                        </div>
                    </div>

                    <div class="amulet_footer">
                        <div>
                            <span style="font-size: 0.75rem; color: var(--text-muted); display: block; text-transform: uppercase; letter-spacing: 0.5px;">ราคาเช่าบูชา</span>
                            <span class="amulet_price_tag"><?php echo number_format((float)$item['price'], 2); ?> ฿</span>
                        </div>
                        <div style="display: flex; gap: 0.45rem;">
                            <a href="amulet_detail.php?id=<?php echo (int)$item['id']; ?>" class="btn btn_outline" style="padding: 0.45rem 0.75rem; font-size: 0.82rem;">
                                สแตตัส 📊
                            </a>
                            <form method="POST" action="cart.php" style="margin: 0;">
                                <input type="hidden" name="action" value="add">
                                <input type="hidden" name="amulet_id" value="<?php echo (int)$item['id']; ?>">
                                <button type="submit" class="btn btn_gold" style="padding: 0.45rem 0.85rem; font-size: 0.82rem;">
                                    + บูชา
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<script>
/**
 * Handle quick toggle wishlist on card button.
 */
function handleQuickWishlist(event, amuletId, btnElem) {
    event.stopPropagation();
    event.preventDefault();

    fetch('api_wishlist.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            action: 'toggle_wishlist',
            amulet_id: amuletId
        })
    })
    .then(res => res.json())
    .then(data => {
        if (!data.success) {
            if (data.message && data.message.includes('เข้าสู่ระบบ')) {
                if (confirm('กรุณาเข้าสู่ระบบก่อนบันทึกรายการที่ชอบ\nคุณต้องการไปหน้าเข้าสู่ระบบหรือไม่?')) {
                    window.location.href = 'login.php';
                }
                return;
            }
            alert(data.message || 'เกิดข้อผิดพลาด');
            return;
        }

        if (data.is_in_wishlist) {
            btnElem.textContent = '❤️';
            btnElem.style.borderColor = '#ff4d4f';
            btnElem.style.background = 'rgba(255, 77, 79, 0.25)';
        } else {
            btnElem.textContent = '🤍';
            btnElem.style.borderColor = 'rgba(233, 215, 147, 0.3)';
            btnElem.style.background = 'rgba(14, 1, 3, 0.85)';
        }
    })
    .catch(err => {
        console.error('Error toggling wishlist:', err);
    });
}
</script>

<?php
render_site_footer('.', false);
?>

<?php
render_site_footer('.', false);
?>

