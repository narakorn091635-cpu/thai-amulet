<?php
declare(strict_types=1);

/**
 * Personal Amulet Collection Showcase & Live Portfolio Valuation Dashboard.
 *
 * File: collection_showcase.php
 * Mutation Log: 2026-11-04 - Built showcase vault with portfolio stats, asset growth %, and PSA badges.
 */

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/footer.php';
require_once __DIR__ . '/shared/services/auth_guard.php';
require_once __DIR__ . '/shared/services/collection_service.php';
require_once __DIR__ . '/shared/services/amulet_service.php';

require_login('.'); // Ensure user is signed in

$user_id = (int)($_SESSION['user_id'] ?? 0);
$username = (string)($_SESSION['username'] ?? 'นักสะสม');
$portfolio = get_user_collection_portfolio($user_id);

$all_amulets = search_marketplace_amulets();

// Handle Add to Collection
$action_msg = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_collection') {
    $add_amulet_id = (int)($_POST['amulet_id'] ?? 0);
    $acq_price = (float)($_POST['acquisition_price'] ?? 0.0);
    $notes = trim((string)($_POST['personal_notes'] ?? ''));

    if ($add_amulet_id > 0) {
        add_amulet_to_collection($user_id, $add_amulet_id, $acq_price, $notes);
        $action_msg = "เพิ่มพระเครื่องเข้ากรุส่วนตัวเรียบร้อยแล้ว!";
        // Refresh portfolio
        $portfolio = get_user_collection_portfolio($user_id);
    }
}

render_site_header("กรุพระส่วนตัว & พอร์ตโฟลิโอ | {$username}", '.');
?>

<div style="max-width: 1200px; margin: 0 auto; padding-bottom: 3rem;">
    <!-- Dashboard Header -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
        <div>
            <span style="font-size: 0.85rem; color: var(--gold-bright); letter-spacing: 1px; font-weight: 700; text-transform: uppercase;">
                PERSONAL VAULT & ASSET PORTFOLIO
            </span>
            <h1 style="color: var(--gold-accent); margin-top: 0.2rem;">
                ⚜️ กรุพระเครื่องส่วนตัวของ คุณ<?php echo htmlspecialchars($username, ENT_QUOTES, 'UTF-8'); ?>
            </h1>
            <p style="color: var(--text-light); opacity: 0.85; font-size: 0.95rem;">
                รวบรวมของสะสมล้ำค่า คำนวณมูลค่ารวมของพอร์ตโฟลิโอขยับตามราคาตลาดจริงแบบเรียลไทม์
            </p>
        </div>
        <div>
            <button onclick="document.getElementById('add_collection_modal').style.display='flex';" class="btn btn_gold" style="padding: 0.65rem 1.25rem;">
                + นำพระเข้ากรุสะสม
            </button>
        </div>
    </div>

    <?php if ($action_msg): ?>
        <div style="background: rgba(82, 196, 26, 0.2); border: 1px solid var(--success-color); color: #73d13d; padding: 0.75rem 1.25rem; border-radius: var(--radius-md); margin-bottom: 1.5rem;">
            ✓ <?php echo htmlspecialchars($action_msg, ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>

    <!-- 4 Portfolio Metric Cards -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1.25rem; margin-bottom: 2.5rem;">
        <!-- Card 1: Total Market Valuation -->
        <div style="background: linear-gradient(135deg, #3b0109 0%, #1f0004 100%); border: 2px solid var(--gold-accent); border-radius: var(--radius-lg); padding: 1.25rem; box-shadow: var(--shadow-gold);">
            <span style="font-size: 0.8rem; color: var(--gold-bright); font-weight: 600; text-transform: uppercase;">
                มูลค่าพอร์ตโฟลิโอรวม (Market Value)
            </span>
            <div style="font-size: 2rem; font-weight: 900; color: #fff; margin: 0.4rem 0;">
                <?php echo number_format($portfolio['total_market_value'], 2); ?> ฿
            </div>
            <span style="font-size: 0.8rem; color: var(--gold-accent);">
                คำนวณตามดัชนีราคาตลาดกลางพระเครื่องล่าสุด
            </span>
        </div>

        <!-- Card 2: Total Acquisition Cost -->
        <div style="background: var(--bg-card); border: 1px solid var(--gold-border); border-radius: var(--radius-lg); padding: 1.25rem;">
            <span style="font-size: 0.8rem; color: var(--text-light); opacity: 0.8; font-weight: 600; text-transform: uppercase;">
                ต้นทุนรวมที่เช่ามา (Total Cost)
            </span>
            <div style="font-size: 1.8rem; font-weight: 800; color: var(--gold-accent); margin: 0.4rem 0;">
                <?php echo number_format($portfolio['total_acquisition_cost'], 2); ?> ฿
            </div>
            <span style="font-size: 0.8rem; color: var(--text-light); opacity: 0.75;">
                ยอดเงินลงทุนตั้งต้นสะสม
            </span>
        </div>

        <!-- Card 3: Unrealized Capital Gain/Loss -->
        <div style="background: var(--bg-card); border: 1px solid var(--gold-border); border-radius: var(--radius-lg); padding: 1.25rem;">
            <span style="font-size: 0.8rem; color: var(--text-light); opacity: 0.8; font-weight: 600; text-transform: uppercase;">
                ผลตอบแทนประเมิน (Capital Gain)
            </span>
            <div style="font-size: 1.8rem; font-weight: 800; color: <?php echo $portfolio['net_gain'] >= 0 ? '#73d13d' : '#ff4d4f'; ?>; margin: 0.4rem 0;">
                <?php echo ($portfolio['net_gain'] >= 0 ? '+' : '') . number_format($portfolio['net_gain'], 2); ?> ฿
            </div>
            <span style="font-size: 0.85rem; font-weight: 700; color: <?php echo $portfolio['net_gain_pct'] >= 0 ? '#73d13d' : '#ff4d4f'; ?>;">
                <?php echo ($portfolio['net_gain_pct'] >= 0 ? '▲ +' : '▼ ') . $portfolio['net_gain_pct']; ?>% จากต้นทุน
            </span>
        </div>

        <!-- Card 4: Total Vault Items -->
        <div style="background: var(--bg-card); border: 1px solid var(--gold-border); border-radius: var(--radius-lg); padding: 1.25rem;">
            <span style="font-size: 0.8rem; color: var(--text-light); opacity: 0.8; font-weight: 600; text-transform: uppercase;">
                จำนวนพระในกรุ (Collection Size)
            </span>
            <div style="font-size: 1.8rem; font-weight: 800; color: var(--gold-bright); margin: 0.4rem 0;">
                <?php echo $portfolio['total_items']; ?> องค์
            </div>
            <span style="font-size: 0.8rem; color: var(--text-light); opacity: 0.75;">
                พร้อมสถานะและใบรับรองของแท้
            </span>
        </div>
    </div>

    <!-- Portfolio Asset List -->
    <div style="background-color: var(--bg-card); border: 1px solid var(--gold-border); border-radius: var(--radius-lg); padding: 1.5rem; margin-bottom: 2rem;">
        <h2 style="color: var(--gold-accent); font-size: 1.25rem; margin-bottom: 1.25rem;">
            📦 บัญชีรายการพระเครื่องในกรุสะสม
        </h2>

        <?php if (empty($portfolio['items'])): ?>
            <div style="text-align: center; padding: 3rem 1rem;">
                <div style="font-size: 3rem; margin-bottom: 0.5rem;">🏺</div>
                <h3 style="color: var(--gold-accent);">กรุพระของคุณยังว่างอยู่</h3>
                <p style="color: var(--text-light); opacity: 0.8; margin-bottom: 1.5rem;">กดปุ่ม "+ นำพระเข้ากรุสะสม" เพื่อเริ่มต้นจัดทำพอร์ตโฟลิโอพระเครื่องของคุณ</p>
            </div>
        <?php else: ?>
            <div class="amulet_grid">
                <?php foreach ($portfolio['items'] as $item): ?>
                    <div class="amulet_card" style="background: #2a0005;">
                        <div style="position: relative; overflow: hidden;">
                            <?php if (!empty($item['cert_number'])): ?>
                                <span style="position: absolute; top: 10px; right: 10px; background: #000; color: var(--gold-accent); border: 1px solid var(--gold-accent); padding: 0.2rem 0.6rem; border-radius: 4px; font-size: 0.75rem; font-weight: bold; z-index: 2;">
                                    🛡️ <?php echo htmlspecialchars((string)$item['grade_label'], ENT_QUOTES, 'UTF-8'); ?>
                                </span>
                            <?php endif; ?>

                            <div style="height: 200px; display: flex; align-items: center; justify-content: center; background: #1a0003;">
                                <?php if (!empty($item['image_path'])): ?>
                                    <img src="<?php echo htmlspecialchars((string)$item['image_path'], ENT_QUOTES, 'UTF-8'); ?>"
                                         alt="Amulet" style="width: 100%; height: 100%; object-fit: cover;">
                                <?php else: ?>
                                    <span style="font-size: 3.5rem; color: var(--gold-accent);">⚜️</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="amulet_card_body">
                            <h3 class="amulet_title" style="font-size: 1.05rem;"><?php echo htmlspecialchars((string)$item['amulet_name'], ENT_QUOTES, 'UTF-8'); ?></h3>
                            <p class="amulet_meta" style="font-size: 0.82rem;">
                                วัด: <strong><?php echo htmlspecialchars((string)$item['temple_name'], ENT_QUOTES, 'UTF-8'); ?></strong> (จ.<?php echo htmlspecialchars((string)($item['province'] ?? 'อยุธยา'), ENT_QUOTES, 'UTF-8'); ?>)
                            </p>
                            <p class="amulet_meta" style="font-size: 0.82rem;">
                                เกจิ: <strong><?php echo htmlspecialchars((string)$item['monk_name'], ENT_QUOTES, 'UTF-8'); ?></strong>
                            </p>

                            <!-- Financial Breakdown Box -->
                            <div style="background: rgba(0,0,0,0.35); border-radius: 6px; padding: 0.75rem; margin-top: 0.75rem; font-size: 0.82rem;">
                                <div style="display: flex; justify-content: space-between; margin-bottom: 0.25rem;">
                                    <span style="color: var(--text-light); opacity: 0.8;">ต้นทุนที่ได้มา:</span>
                                    <strong style="color: var(--text-light);"><?php echo number_format((float)$item['acquisition_price'], 2); ?> ฿</strong>
                                </div>
                                <div style="display: flex; justify-content: space-between; margin-bottom: 0.25rem;">
                                    <span style="color: var(--gold-bright);">มูลค่าตลาดปัจจุบัน:</span>
                                    <strong style="color: var(--gold-accent);"><?php echo number_format((float)$item['market_value'], 2); ?> ฿</strong>
                                </div>
                                <div style="display: flex; justify-content: space-between; border-top: 1px dashed rgba(233,215,147,0.3); padding-top: 0.35rem; margin-top: 0.35rem;">
                                    <span style="color: var(--text-light); opacity: 0.8;">ผลตอบแทน:</span>
                                    <strong style="color: <?php echo $item['gain_loss'] >= 0 ? '#73d13d' : '#ff4d4f'; ?>;">
                                        <?php echo ($item['gain_loss'] >= 0 ? '+' : '') . number_format((float)$item['gain_loss'], 2); ?> ฿ (<?php echo ($item['gain_loss_pct'] >= 0 ? '+' : '') . $item['gain_loss_pct']; ?>%)
                                    </strong>
                                </div>
                            </div>

                            <div style="margin-top: 0.75rem; display: flex; gap: 0.5rem;">
                                <a href="amulet_detail.php?id=<?php echo (int)$item['amulet_id']; ?>" class="btn btn_outline" style="flex: 1; font-size: 0.8rem; padding: 0.4rem; text-align: center;">
                                    ดูสแตตัส
                                </a>
                                <?php if (!empty($item['cert_number'])): ?>
                                    <a href="verify_cert.php?cert_no=<?php echo urlencode((string)$item['cert_number']); ?>" class="btn btn_gold" style="flex: 1; font-size: 0.8rem; padding: 0.4rem; text-align: center;">
                                        ใบเซอร์
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Add to Collection Modal -->
<div id="add_collection_modal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.75); z-index: 9999; align-items: center; justify-content: center; padding: 1rem;">
    <div style="background: var(--bg-card); border: 2px solid var(--gold-accent); border-radius: var(--radius-lg); max-width: 500px; width: 100%; padding: 2rem; box-shadow: var(--shadow-gold);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
            <h3 style="color: var(--gold-accent); margin: 0;">🏺 นำพระเครื่องเข้ากรุสะสม</h3>
            <button onclick="document.getElementById('add_collection_modal').style.display='none';" style="background: transparent; border: none; color: #fff; font-size: 1.5rem; cursor: pointer;">&times;</button>
        </div>

        <form method="POST" action="collection_showcase.php">
            <input type="hidden" name="action" value="add_collection">

            <div style="margin-bottom: 1rem;">
                <label style="display: block; font-size: 0.85rem; color: var(--gold-accent); margin-bottom: 0.35rem;">เลือกพระเครื่องในระบบ</label>
                <select name="amulet_id" required class="form_control" style="width: 100%;">
                    <?php foreach ($all_amulets as $am): ?>
                        <option value="<?php echo (int)$am['id']; ?>">
                            #<?php echo (int)$am['id']; ?> <?php echo htmlspecialchars((string)$am['name'], ENT_QUOTES, 'UTF-8'); ?> (<?php echo htmlspecialchars((string)$am['monk_name'], ENT_QUOTES, 'UTF-8'); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="margin-bottom: 1rem;">
                <label style="display: block; font-size: 0.85rem; color: var(--gold-accent); margin-bottom: 0.35rem;">ราคาต้นทุนที่ได้มา (บาท)</label>
                <input type="number" step="100" min="0" name="acquisition_price" required class="form_control" style="width: 100%;" placeholder="เช่น 25000">
            </div>

            <div style="margin-bottom: 1.5rem;">
                <label style="display: block; font-size: 0.85rem; color: var(--gold-accent); margin-bottom: 0.35rem;">บันทึกความทรงจำ / ที่มาขององค์พระ</label>
                <textarea name="personal_notes" rows="2" class="form_control" style="width: 100%;" placeholder="เช่น ได้รับมอบมาจากคุณปู่, เช่าบูชาจากวัดโดยตรง..."></textarea>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 0.5rem;">
                <button type="button" onclick="document.getElementById('add_collection_modal').style.display='none';" class="btn btn_outline">ยกเลิก</button>
                <button type="submit" class="btn btn_gold">บันทึกเข้ากรุพระ</button>
            </div>
        </form>
    </div>
</div>

<?php
render_site_footer('.', false);
?>
