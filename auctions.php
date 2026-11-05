<?php
declare(strict_types=1);

/**
 * Real-time Auction Listings Page (Module 2).
 *
 * File: auctions.php
 * Mutation Log: 2026-11-04 - Created Live Auction Catalog with JavaScript Countdown Timer.
 */

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/shared/services/auction_service.php';

$auctions = get_active_auctions();

render_site_header('ห้องประมูลพระเครื่องยอดนิยม (Live Auction)', '.');
?>

<div style="margin-bottom: 2rem; text-align: center;">
    <h1 style="color: var(--gold-accent); margin-bottom: 0.5rem;">⚜️ ห้องประมูลพระเครื่องยอดนิยม</h1>
    <p style="color: var(--text-light); opacity: 0.9;">ร่วมประมูลพระเครื่องแท้แบบเรียลไทม์ พร้อมระบบป้องกันการปาดหน้า (Anti-Snipe Protection)</p>
</div>

<?php if (empty($auctions)): ?>
    <div style="text-align: center; padding: 4rem 1rem; background-color: var(--bg-card); border: 1px dashed var(--gold-border); border-radius: var(--radius-lg);">
        <h3 style="color: var(--gold-accent); margin-bottom: 0.5rem;">ขณะนี้ยังไม่มีรายการพระเครื่องที่เปิดประมูล</h3>
        <p style="color: var(--text-light); margin-bottom: 1.5rem;">ท่านสามารถแวะชมรายการพระเครื่องในหมวดตลาดเช่าบูชาปกติได้</p>
        <a href="index.php" class="btn btn_gold">ไปที่ตลาดพระเครื่อง</a>
    </div>
<?php else: ?>
    <div class="amulet_grid">
        <?php foreach ($auctions as $auc): ?>
            <?php
            $is_active = $auc['status'] === 'active' && strtotime((string)$auc['end_time']) > time();
            $min_bid_next = (float)$auc['current_price'] + (float)$auc['min_increment'];
            ?>
            <div class="amulet_card">
                <div style="position: relative; overflow: hidden; background: #2a0005;">
                    <span style="position: absolute; top: 12px; left: 12px; background: rgba(94, 3, 15, 0.85); color: var(--gold-accent); border: 1px solid var(--gold-border); padding: 0.2rem 0.6rem; border-radius: 4px; font-size: 0.75rem; font-weight: bold; z-index: 2;">
                        ประมูล: <?php echo htmlspecialchars((string)$auc['category_name'], ENT_QUOTES, 'UTF-8'); ?>
                    </span>
                    <?php if (!empty($auc['image_path'])): ?>
                        <div style="height: 220px; overflow: hidden; display: flex; align-items: center; justify-content: center; background: #1a0003;">
                            <img src="<?php echo htmlspecialchars((string)$auc['image_path'], ENT_QUOTES, 'UTF-8'); ?>"
                                 alt="<?php echo htmlspecialchars((string)$auc['amulet_name'], ENT_QUOTES, 'UTF-8'); ?>"
                                 style="width: 100%; height: 100%; object-fit: cover;"
                                 onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                            <div style="display: none; height: 220px; width: 100%; align-items: center; justify-content: center; font-size: 4rem; color: var(--gold-accent); background: linear-gradient(180deg, #3b0109 0%, #1f0004 100%);">
                                ⚜️
                            </div>
                        </div>
                    <?php else: ?>
                        <div style="height: 220px; display: flex; align-items: center; justify-content: center; font-size: 4rem; color: var(--gold-accent); background: linear-gradient(180deg, #3b0109 0%, #1f0004 100%);">
                            ⚜️
                        </div>
                    <?php endif; ?>
                </div>

                <div class="amulet_card_body">
                    <div>
                        <h3 class="amulet_title"><?php echo htmlspecialchars((string)$auc['amulet_name'], ENT_QUOTES, 'UTF-8'); ?></h3>
                        <p class="amulet_meta" style="margin-top: 0.25rem;">
                            วัด: <strong><?php echo htmlspecialchars((string)$auc['temple_name'], ENT_QUOTES, 'UTF-8'); ?></strong>
                        </p>
                        <p class="amulet_meta">
                            เกจิ: <strong><?php echo htmlspecialchars((string)$auc['monk_name'], ENT_QUOTES, 'UTF-8'); ?></strong>
                        </p>
                    </div>

                    <!-- Countdown Timer Element -->
                    <div style="margin: 0.75rem 0;">
                        <span style="font-size: 0.8rem; color: var(--text-light); display: block; margin-bottom: 0.25rem;">เวลาที่เหลืออยู่:</span>
                        <div class="auction_timer" data-auction-end="<?php echo htmlspecialchars(date('c', strtotime((string)$auc['end_time'])), ENT_QUOTES, 'UTF-8'); ?>">
                            คำนวณเวลา...
                        </div>
                    </div>

                    <div style="display: flex; justify-content: space-between; align-items: flex-end; pt: 0.75rem; border-top: 1px solid var(--gold-muted);">
                        <div>
                            <span style="font-size: 0.8rem; color: var(--text-light); display: block;">ราคาสูงสุดขณะนี้</span>
                            <span class="amulet_price"><?php echo number_format((float)$auc['current_price'], 2); ?> ฿</span>
                            <div style="font-size: 0.75rem; color: var(--gold-bright);">
                                ผู้เสนอ: <?php echo htmlspecialchars((string)($auc['highest_bidder_name'] ?? 'ยังไม่มีผู้เสนอ'), ENT_QUOTES, 'UTF-8'); ?>
                            </div>
                        </div>

                        <a href="auction_detail.php?id=<?php echo (int)$auc['id']; ?>" class="btn btn_gold" style="padding: 0.6rem 1rem; font-size: 0.9rem;">
                            ร่วมประมูล &rarr;
                        </a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php
render_site_footer('.', true);
?>

