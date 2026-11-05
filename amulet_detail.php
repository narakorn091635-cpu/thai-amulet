<?php
declare(strict_types=1);

/**
 * Amulet Detail View & Module 3 Radar Chart Visualizer.
 *
 * File: amulet_detail.php
 * Mutation Log: 2026-11-04 - Implemented full amulet detail view with 6-axis Radar Chart canvas.
 */

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/footer.php';
require_once __DIR__ . '/shared/services/amulet_service.php';
require_once __DIR__ . '/shared/services/cert_service.php';
require_once __DIR__ . '/shared/services/wishlist_service.php';

$current_user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
$amulet_id = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : 0;
$amulet = get_amulet_by_id($amulet_id);

if (!$amulet) {
    render_site_header('ไม่พบพระเครื่อง', '.');
    echo '<div style="text-align: center; padding: 4rem 1rem;">';
    echo '<h2 style="color: var(--gold-accent);">ไม่พบข้อมูลพระเครื่องรายการนี้</h2>';
    echo '<a href="index.php" class="btn btn_gold" style="margin-top: 1.5rem;">กลับหน้ารายการพระ</a>';
    echo '</div>';
    render_site_footer('.', false);
    exit;
}

$stat_scores = [
    (int)$amulet['stat_protection'],
    (int)$amulet['stat_kindness'],
    (int)$amulet['stat_charm'],
    (int)$amulet['stat_evasion'],
    (int)$amulet['stat_invincibility'],
    (int)$amulet['stat_wealth'],
];

$stat_labels = [
    'พุทธคุณ',
    'เมตตามหานิยม',
    'มหาเสน่ห์',
    'แคล้วคลาด',
    'คงกระพันชาตรี',
    'โชคลาภ',
];

// Fetch historical market prices & real-time comparison analytics
$market_prices = get_amulet_market_price_history($amulet_id);
$price_comparison = calculate_market_price_comparison((float)$amulet['price'], $market_prices);

// Fetch PSA Certificate & Verification Details
$certificate = get_certificate_by_amulet_id($amulet_id);

// Check Wishlist and Monk Follow Status
$is_favorited = $current_user_id > 0 ? is_amulet_in_wishlist($current_user_id, $amulet_id) : false;
$is_monk_followed = ($current_user_id > 0 && !empty($amulet['monk_name'])) ? is_following_monk($current_user_id, (string)$amulet['monk_name']) : false;

render_site_header(htmlspecialchars((string)$amulet['name'], ENT_QUOTES, 'UTF-8'), '.');
?>

<div style="margin-bottom: 1.5rem;">
    <a href="index.php" style="font-size: 0.9rem; color: var(--gold-accent);">&larr; กลับหน้ารายการพระเครื่อง</a>
</div>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 2rem; align-items: start;">
    <!-- Column 1: Amulet Info & Artwork -->
    <div style="background-color: var(--bg-card); border: 1px solid var(--gold-border); border-radius: var(--radius-lg); padding: 1.5rem;">
        <div style="height: 280px; overflow: hidden; display: flex; align-items: center; justify-content: center; background: linear-gradient(180deg, #3b0109 0%, #1f0004 100%); border-radius: var(--radius-md); border: 1px solid var(--gold-muted); margin-bottom: 1.5rem;">
            <?php if (!empty($amulet['image_path'])): ?>
                <img src="<?php echo htmlspecialchars((string)$amulet['image_path'], ENT_QUOTES, 'UTF-8'); ?>"
                     alt="<?php echo htmlspecialchars((string)$amulet['name'], ENT_QUOTES, 'UTF-8'); ?>"
                     style="width: 100%; height: 100%; object-fit: contain;"
                     onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                <div style="display: none; height: 100%; width: 100%; align-items: center; justify-content: center; font-size: 6rem; color: var(--gold-accent);">
                    ⚜️
                </div>
            <?php else: ?>
                <div style="font-size: 6rem; color: var(--gold-accent);">
                    ⚜️
                </div>
            <?php endif; ?>
        </div>

        <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem; margin-bottom: 0.75rem;">
            <h1 style="color: var(--gold-accent); font-size: 1.5rem; margin: 0;">
                <?php echo htmlspecialchars((string)$amulet['name'], ENT_QUOTES, 'UTF-8'); ?>
            </h1>
            <button id="btn_toggle_wishlist" type="button" 
                    onclick="handleToggleWishlist(<?php echo $amulet_id; ?>)"
                    style="background: <?php echo $is_favorited ? 'rgba(255, 77, 79, 0.2)' : 'rgba(255,255,255,0.06)'; ?>; 
                           border: 1px solid <?php echo $is_favorited ? '#ff4d4f' : 'var(--gold-border)'; ?>; 
                           color: <?php echo $is_favorited ? '#ff4d4f' : 'var(--gold-accent)'; ?>; 
                           padding: 0.4rem 0.8rem; border-radius: 999px; cursor: pointer; display: flex; align-items: center; gap: 0.35rem; font-size: 0.85rem; font-weight: 600; transition: all 0.2s ease;">
                <span id="wishlist_icon"><?php echo $is_favorited ? '❤️' : '🤍'; ?></span>
                <span id="wishlist_text"><?php echo $is_favorited ? 'บันทึกแล้ว' : 'เซฟลง Wishlist'; ?></span>
            </button>
        </div>

        <?php if ($certificate): ?>
            <!-- Certified PSA Verification Badge -->
            <div style="background: linear-gradient(90deg, #1f0004 0%, #2a0006 100%); border: 1.5px solid var(--gold-accent); border-radius: var(--radius-md); padding: 0.85rem 1rem; margin-bottom: 1.25rem; display: flex; justify-content: space-between; align-items: center; gap: 0.75rem;">
                <div style="display: flex; align-items: center; gap: 0.75rem;">
                    <span style="font-size: 1.8rem;">🛡️</span>
                    <div>
                        <div style="display: flex; align-items: center; gap: 0.4rem;">
                            <span style="font-size: 0.75rem; font-weight: 700; color: #73d13d; background: rgba(82, 196, 26, 0.2); border: 1px solid #52c41a; padding: 0.1rem 0.5rem; border-radius: 4px;">
                                VERIFIED PSA GRADE <?php echo number_format((float)$certificate['grade_score'], 1); ?>
                            </span>
                        </div>
                        <div style="font-size: 0.82rem; color: var(--text-light); margin-top: 0.2rem;">
                            เลขใบเซอร์: <strong><?php echo htmlspecialchars((string)$certificate['cert_number'], ENT_QUOTES, 'UTF-8'); ?></strong>
                        </div>
                    </div>
                </div>
                <div style="display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap;">
                    <a href="verify_cert.php?cert_no=<?php echo urlencode((string)$certificate['cert_number']); ?>"
                       class="btn btn_gold" style="font-size: 0.8rem; padding: 0.4rem 0.75rem; white-space: nowrap;">
                        สแกน QR / ตรวจสอบ ↗
                    </a>
                    <a href="print_cert.php?cert_no=<?php echo urlencode((string)$certificate['cert_number']); ?>"
                       target="_blank"
                       style="font-size: 0.8rem; padding: 0.4rem 0.75rem; white-space: nowrap; background: rgba(0,0,0,0.4); border: 1px solid var(--gold-accent); color: var(--gold-bright); border-radius: var(--radius-sm); text-decoration: none; display: inline-flex; align-items: center; gap: 0.3rem;">
                        🖨️ พิมพ์ใบเซอร์
                    </a>
                </div>
            </div>
        <?php endif; ?>

        <div style="background: rgba(0,0,0,0.25); border-radius: var(--radius-md); padding: 1rem; margin-bottom: 1.5rem; font-size: 0.95rem; color: var(--text-light);">
            <p style="margin-bottom: 0.4rem;"><strong>หมวดหมู่:</strong> <?php echo htmlspecialchars((string)$amulet['category_name'], ENT_QUOTES, 'UTF-8'); ?></p>
            <p style="margin-bottom: 0.4rem;"><strong>วัด:</strong> <?php echo htmlspecialchars((string)$amulet['temple_name'], ENT_QUOTES, 'UTF-8'); ?></p>
            <p style="margin-bottom: 0.4rem; display: flex; justify-content: space-between; align-items: center;">
                <span><strong>เกจิอาจารย์:</strong> <?php echo htmlspecialchars((string)$amulet['monk_name'], ENT_QUOTES, 'UTF-8'); ?></span>
                <?php if (!empty($amulet['monk_name'])): ?>
                    <button id="btn_follow_monk" type="button"
                            onclick="handleToggleFollowMonk('<?php echo htmlspecialchars(addslashes((string)$amulet['monk_name']), ENT_QUOTES, 'UTF-8'); ?>')"
                            style="background: <?php echo $is_monk_followed ? 'rgba(82, 196, 26, 0.2)' : 'rgba(233,215,147,0.1)'; ?>;
                                   border: 1px solid <?php echo $is_monk_followed ? '#52c41a' : 'var(--gold-border)'; ?>;
                                   color: <?php echo $is_monk_followed ? '#73d13d' : 'var(--gold-bright)'; ?>;
                                   padding: 0.2rem 0.6rem; border-radius: 4px; cursor: pointer; font-size: 0.75rem; font-weight: 600; transition: all 0.2s ease;">
                        <span id="follow_monk_text"><?php echo $is_monk_followed ? '✓ ติดตามแล้ว' : '+ ติดตามเกจิ'; ?></span>
                    </button>
                <?php endif; ?>
            </p>
            <p style="margin-bottom: 0.4rem;"><strong>ปีสร้าง:</strong> <?php echo htmlspecialchars((string)($amulet['year_created'] ?? 'ไม่ระบุ'), ENT_QUOTES, 'UTF-8'); ?></p>
            <p style="margin-bottom: 0.4rem;"><strong>มวลสาร/เนื้อ:</strong> <?php echo htmlspecialchars((string)($amulet['material'] ?? 'ไม่ระบุ'), ENT_QUOTES, 'UTF-8'); ?></p>
            <p style="margin-bottom: 0;"><strong>สถานะคงเหลือ:</strong> <?php echo (int)$amulet['stock_qty'] > 0 ? (int)$amulet['stock_qty'] . ' องค์' : 'หมดแล้ว'; ?></p>
        </div>

        <div style="margin-bottom: 1.5rem;">
            <h4 style="color: var(--gold-accent); margin-bottom: 0.5rem;">ประวัติและความเป็นมา</h4>
            <p style="color: var(--text-light); line-height: 1.7; opacity: 0.95; font-size: 0.95rem;">
                <?php echo nl2br(htmlspecialchars((string)$amulet['description'], ENT_QUOTES, 'UTF-8')); ?>
            </p>
        </div>

        <!-- Market Price Benchmark & Real Comparison Card -->
        <div style="background: linear-gradient(180deg, #2a0006 0%, #1a0003 100%); border: 1px solid var(--gold-border); border-radius: var(--radius-md); padding: 1.25rem; margin-bottom: 1.5rem;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem; flex-wrap: wrap; gap: 0.5rem;">
                <h4 style="color: var(--gold-accent); margin: 0; font-size: 1rem; display: flex; align-items: center; gap: 0.4rem;">
                    📈 เปรียบเทียบราคาตลาดจริง (Market Price Benchmark)
                </h4>
                <?php if ($price_comparison['has_data']): ?>
                    <span style="font-size: 0.75rem; font-weight: 700; padding: 0.25rem 0.65rem; border-radius: 999px; background: <?php echo $price_comparison['diff'] <= 0 ? 'rgba(82, 196, 26, 0.2)' : 'rgba(250, 173, 20, 0.2)'; ?>; color: <?php echo $price_comparison['diff'] <= 0 ? '#73d13d' : '#ffd666'; ?>; border: 1px solid <?php echo $price_comparison['diff'] <= 0 ? '#52c41a' : '#faad14'; ?>;">
                        <?php echo htmlspecialchars($price_comparison['badge_text'], ENT_QUOTES, 'UTF-8'); ?>
                    </span>
                <?php endif; ?>
            </div>

            <?php if ($price_comparison['has_data']): ?>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(130px, 1fr)); gap: 0.75rem; margin-bottom: 1rem;">
                    <div style="background: rgba(0,0,0,0.3); border-radius: var(--radius-md); padding: 0.75rem; border: 1px solid rgba(233,215,147,0.15);">
                        <span style="font-size: 0.75rem; color: var(--text-light); opacity: 0.8; display: block;">ราคาตั้งบูชา (ปัจจุบัน)</span>
                        <strong style="font-size: 1.15rem; color: var(--gold-accent);"><?php echo number_format((float)$amulet['price'], 2); ?> ฿</strong>
                    </div>
                    <div style="background: rgba(0,0,0,0.3); border-radius: var(--radius-md); padding: 0.75rem; border: 1px solid rgba(233,215,147,0.15);">
                        <span style="font-size: 0.75rem; color: var(--text-light); opacity: 0.8; display: block;">ราคากลางตลาดพระ (วันนี้)</span>
                        <strong style="font-size: 1.15rem; color: #73d13d;"><?php echo number_format((float)$price_comparison['market_price'], 2); ?> ฿</strong>
                    </div>
                    <div style="background: rgba(0,0,0,0.3); border-radius: var(--radius-md); padding: 0.75rem; border: 1px solid rgba(233,215,147,0.15);">
                        <span style="font-size: 0.75rem; color: var(--text-light); opacity: 0.8; display: block;">ส่วนต่างราคา</span>
                        <strong style="font-size: 1.15rem; color: <?php echo $price_comparison['diff'] <= 0 ? '#73d13d' : '#ff7875'; ?>;">
                            <?php echo ($price_comparison['diff'] > 0 ? '+' : '') . number_format((float)$price_comparison['diff'], 2); ?> ฿
                        </strong>
                    </div>
                </div>

                <!-- Historical Price Timeline Bar -->
                <div>
                    <span style="font-size: 0.8rem; color: var(--gold-bright); font-weight: 600; display: block; margin-bottom: 0.5rem;">
                        🕒 ไทม์ไลน์ราคาย้อนหลัง & ราคาประเมินจริง:
                    </span>
                    <div style="display: flex; flex-direction: column; gap: 0.4rem; font-size: 0.82rem;">
                        <?php foreach ($market_prices as $hist): ?>
                            <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.35rem 0.6rem; background: rgba(0,0,0,0.2); border-radius: 4px; border-left: 3px solid var(--gold-accent);">
                                <div>
                                    <span style="color: var(--text-light); font-weight: 600;"><?php echo htmlspecialchars((string)$hist['period_label'], ENT_QUOTES, 'UTF-8'); ?></span>
                                    <span style="color: var(--text-light); opacity: 0.65; font-size: 0.75rem; margin-left: 0.5rem;">(อ้างอิง: <?php echo htmlspecialchars((string)$hist['price_source'], ENT_QUOTES, 'UTF-8'); ?>)</span>
                                </div>
                                <span style="color: var(--gold-accent); font-weight: 700;"><?php echo number_format((float)$hist['estimated_price'], 2); ?> ฿</span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php else: ?>
                <p style="font-size: 0.85rem; color: var(--text-light); opacity: 0.8; margin: 0;">
                    ยังไม่มีข้อมูลราคากลางอ้างอิงสำหรับพระเครื่องรุ่นนี้
                </p>
            <?php endif; ?>
        </div>

        <div style="display: flex; justify-content: space-between; align-items: center; pt: 1rem; border-top: 1px solid var(--gold-muted);">
            <div>
                <span style="font-size: 0.85rem; color: var(--text-light);">ราคาเช่าบูชา</span>
                <div style="font-size: 1.8rem; font-weight: 700; color: var(--gold-accent);">
                    <?php echo number_format((float)$amulet['price'], 2); ?> ฿
                </div>
            </div>

            <form method="POST" action="cart.php" style="margin: 0;">
                <input type="hidden" name="action" value="add">
                <input type="hidden" name="amulet_id" value="<?php echo (int)$amulet['id']; ?>">
                <button type="submit" class="btn btn_gold" style="padding: 0.75rem 1.75rem; font-size: 1.05rem;">
                    เช่าบูชาองค์นี้
                </button>
            </form>
        </div>
    </div>

    <!-- Column 2: Module 3 Amulet Status Radar Chart Graphic -->
    <div class="radar_chart_container">
        <h2 style="color: var(--gold-accent); font-size: 1.35rem; margin-bottom: 0.5rem; text-align: center;">
            ⚜️ ค่าพลังพุทธคุณ 6 ด้าน (Amulet Status)
        </h2>
        <p style="font-size: 0.85rem; color: var(--text-light); text-align: center; margin-bottom: 1.25rem; opacity: 0.85;">
            วิเคราะห์ระดับพลังและพุทธคุณพระเครื่องตามคะแนน 1-10
        </p>

        <!-- HTML5 Canvas Render Target for Radar Chart Engine in js/main.js -->
        <canvas id="amulet_radar_canvas"
                width="420"
                height="420"
                class="radar_canvas"
                data-radar-scores="<?php echo htmlspecialchars((string)json_encode($stat_scores), ENT_QUOTES, 'UTF-8'); ?>"
                data-radar-labels="<?php echo htmlspecialchars((string)json_encode($stat_labels), ENT_QUOTES, 'UTF-8'); ?>">
        </canvas>

        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 0.85rem; width: 100%; margin-top: 1.5rem;">
            <?php foreach ($stat_labels as $index => $label): ?>
                <?php 
                    $score = (int)$stat_scores[$index];
                    $percent = min(100, max(10, $score * 10));
                ?>
                <div style="background-color: var(--bg-darker); border: 1px solid var(--gold-muted); border-radius: var(--radius-md); padding: 0.65rem 0.85rem;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.35rem;">
                        <span style="font-size: 0.85rem; color: var(--text-light);"><?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?></span>
                        <span style="font-weight: 700; color: var(--gold-accent); font-size: 0.95rem;"><?php echo $score; ?> / 10</span>
                    </div>
                    <!-- Status Power Progress Bar -->
                    <div style="width: 100%; height: 8px; background: rgba(0, 0, 0, 0.45); border-radius: 4px; overflow: hidden; border: 1px solid rgba(233, 215, 147, 0.25);">
                        <div style="width: <?php echo $percent; ?>%; height: 100%; background: linear-gradient(90deg, #bfa15f 0%, #E9D793 100%); border-radius: 4px; box-shadow: 0 0 8px rgba(233, 215, 147, 0.5);"></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<script>
/**
 * Handle AJAX Toggle Wishlist for this amulet.
 *
 * @param {number} amuletId
 */
function handleToggleWishlist(amuletId) {
    const btn = document.getElementById('btn_toggle_wishlist');
    const icon = document.getElementById('wishlist_icon');
    const text = document.getElementById('wishlist_text');

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
                if (confirm('กรุณาเข้าสู่ระบบเพื่อบันทึกพระเครื่องลงรายการที่ชอบ\nคุณต้องการไปหน้าเข้าสู่ระบบหรือไม่?')) {
                    window.location.href = 'login.php';
                }
                return;
            }
            alert(data.message || 'เกิดข้อผิดพลาดในการบันทึก');
            return;
        }

        if (data.is_in_wishlist) {
            btn.style.background = 'rgba(255, 77, 79, 0.2)';
            btn.style.borderColor = '#ff4d4f';
            btn.style.color = '#ff4d4f';
            icon.textContent = '❤️';
            text.textContent = 'บันทึกแล้ว';
        } else {
            btn.style.background = 'rgba(255,255,255,0.06)';
            btn.style.borderColor = 'var(--gold-border)';
            btn.style.color = 'var(--gold-accent)';
            icon.textContent = '🤍';
            text.textContent = 'เซฟลง Wishlist';
        }
    })
    .catch(err => {
        console.error('Error toggling wishlist:', err);
    });
}

/**
 * Handle AJAX Follow/Unfollow Monk.
 *
 * @param {string} monkName
 */
function handleToggleFollowMonk(monkName) {
    const btn = document.getElementById('btn_follow_monk');
    const text = document.getElementById('follow_monk_text');

    fetch('api_wishlist.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            action: 'toggle_follow_monk',
            monk_name: monkName
        })
    })
    .then(res => res.json())
    .then(data => {
        if (!data.success) {
            if (data.message && data.message.includes('เข้าสู่ระบบ')) {
                if (confirm('กรุณาเข้าสู่ระบบเพื่อติดตามเกจิอาจารย์\nคุณต้องการไปหน้าเข้าสู่ระบบหรือไม่?')) {
                    window.location.href = 'login.php';
                }
                return;
            }
            alert(data.message || 'เกิดข้อผิดพลาด');
            return;
        }

        if (data.is_following) {
            btn.style.background = 'rgba(82, 196, 26, 0.2)';
            btn.style.borderColor = '#52c41a';
            btn.style.color = '#73d13d';
            text.textContent = '✓ ติดตามแล้ว';
        } else {
            btn.style.background = 'rgba(233,215,147,0.1)';
            btn.style.borderColor = 'var(--gold-border)';
            btn.style.color = 'var(--gold-bright)';
            text.textContent = '+ ติดตามเกจิ';
        }
    })
    .catch(err => {
        console.error('Error following monk:', err);
    });
}
</script>

<?php
render_site_footer('.', false);
?>

