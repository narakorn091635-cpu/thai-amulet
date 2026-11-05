<?php
declare(strict_types=1);

/**
 * User Wishlist & Followed Monks Feed View.
 *
 * File: wishlist.php
 * Mutation Log: 2026-11-05 - Built Wishlist items and followed monks feeds.
 */

require_once __DIR__ . '/shared/utils/session_init.php';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/footer.php';
require_once __DIR__ . '/shared/services/auth_guard.php';
require_once __DIR__ . '/shared/services/wishlist_service.php';
require_once __DIR__ . '/shared/services/amulet_service.php';

require_login('.');

$user_id = (int)$_SESSION['user_id'];
$wishlist_items = get_user_wishlist_amulets($user_id);
$followed_monks = get_user_followed_monks($user_id);

render_site_header('พระที่ถูกใจ & เกจิอาจารย์ที่ติดตาม (Wishlist & Followed Monks)', '.');
?>

<div style="max-width: 1100px; margin: 0 auto; padding-bottom: 3rem;">
    <div style="margin-bottom: 2rem;">
        <h1 style="color: var(--gold-accent); margin-bottom: 0.35rem;">❤️ พระเครื่องที่ถูกใจ & เกจิที่ติดตาม</h1>
        <p style="color: var(--text-light); opacity: 0.85; font-size: 0.95rem;">
            รายการพระเครื่องที่คุณกดบันทึกไว้ และรายชื่อเกจิอาจารย์ที่คุณกดติดตามรับการแจ้งเตือน
        </p>
    </div>

    <!-- Section 1: Followed Monks Badges -->
    <div style="background: var(--bg-card); border: 1px solid var(--gold-border); border-radius: var(--radius-lg); padding: 1.25rem; margin-bottom: 2.5rem;">
        <h3 style="color: var(--gold-bright); font-size: 1.1rem; margin-bottom: 0.75rem; display: flex; align-items: center; gap: 0.5rem;">
            📿 เกจิอาจารย์ที่คุณติดตาม (Followed Monks)
        </h3>
        <?php if (empty($followed_monks)): ?>
            <p style="font-size: 0.85rem; color: var(--text-light); opacity: 0.75; margin: 0;">
                คุณยังไม่ได้กดติดตามเกจิอาจารย์ท่านใด (สามารถกดติดตามได้ที่หน้ารายละเอียดพระเครื่อง)
            </p>
        <?php else: ?>
            <div style="display: flex; gap: 0.75rem; flex-wrap: wrap;">
                <?php foreach ($followed_monks as $m): ?>
                    <div style="background: #1f0004; border: 1.5px solid var(--gold-accent); border-radius: 999px; padding: 0.4rem 1rem; display: flex; align-items: center; gap: 0.5rem;">
                        <span style="font-size: 0.9rem; color: #fff; font-weight: bold;"><?php echo htmlspecialchars((string)$m['monk_name']); ?></span>
                        <a href="index.php?monk_name=<?php echo urlencode((string)$m['monk_name']); ?>" style="font-size: 0.75rem; color: var(--gold-bright); text-decoration: underline;">
                            ดูพระทั้งหมด ↗
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Section 2: Wishlist Grid -->
    <div>
        <h2 style="color: var(--gold-accent); font-size: 1.25rem; margin-bottom: 1.25rem;">
            📦 พระเครื่องที่คุณบันทึกไว้ (<?php echo count($wishlist_items); ?> รายการ)
        </h2>

        <?php if (empty($wishlist_items)): ?>
            <div style="text-align: center; padding: 3rem 1rem; background: var(--bg-card); border: 1px dashed var(--gold-muted); border-radius: var(--radius-lg);">
                <div style="font-size: 3rem; margin-bottom: 0.5rem;">❤️</div>
                <h3 style="color: var(--gold-accent);">ยังไม่มีรายการที่บันทึกไว้</h3>
                <p style="color: var(--text-light); opacity: 0.8; margin-bottom: 1.5rem;">คลิกปุ่มหัวใจในหน้าพระเครื่อง เพื่อบันทึกเก็บไว้ดูย้อนหลัง</p>
                <a href="index.php" class="btn btn_gold">ไปที่ตลาดพระเครื่อง</a>
            </div>
        <?php else: ?>
            <div class="amulet_grid">
                <?php foreach ($wishlist_items as $item): ?>
                    <div class="amulet_card">
                        <div style="height: 200px; overflow: hidden; background: #1a0003; position: relative;">
                            <img src="<?php echo htmlspecialchars((string)$item['image_path']); ?>" alt="Amulet" style="width: 100%; height: 100%; object-fit: cover;">
                        </div>
                        <div class="amulet_card_body">
                            <h3 class="amulet_title" style="font-size: 1rem;"><?php echo htmlspecialchars((string)$item['name']); ?></h3>
                            <p class="amulet_meta" style="font-size: 0.85rem;">เกจิ: <strong><?php echo htmlspecialchars((string)$item['monk_name']); ?></strong></p>
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 0.75rem;">
                                <span class="amulet_price" style="font-size: 1.15rem;"><?php echo number_format((float)$item['price'], 2); ?> ฿</span>
                                <a href="amulet_detail.php?id=<?php echo (int)$item['id']; ?>" class="btn btn_gold" style="font-size: 0.8rem; padding: 0.35rem 0.75rem;">ดูรายละเอียด</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
render_site_footer('.', false);
?>
