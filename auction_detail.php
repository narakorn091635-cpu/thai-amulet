<?php
declare(strict_types=1);

/**
 * Auction Detail, Live AJAX Bidding, Anti-Snipe & Status Graphic View.
 *
 * File: auction_detail.php
 * Mutation Log: 2026-11-04 - Created live auction interface with AJAX Fetch API, 1-min Anti-Snipe, and Bid History.
 */

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/footer.php';
require_once __DIR__ . '/shared/services/auction_service.php';

$auction_id = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : 0;
$auction = get_auction_by_id($auction_id);

if (!$auction) {
    render_site_header('ไม่พบรายการประมูล', '.');
    echo '<div style="text-align: center; padding: 4rem 1rem;">';
    echo '<h2 style="color: var(--gold-accent);">ไม่พบรายการประมูลพระเครื่องนี้</h2>';
    echo '<a href="auctions.php" class="btn btn_gold" style="margin-top: 1.5rem;">กลับหน้ารายการประมูล</a>';
    echo '</div>';
    render_site_footer('.', false);
    exit;
}

$bids = get_auction_bid_history($auction_id, 20);
$stat_scores = [
    (int)$auction['stat_protection'],
    (int)$auction['stat_kindness'],
    (int)$auction['stat_charm'],
    (int)$auction['stat_evasion'],
    (int)$auction['stat_invincibility'],
    (int)$auction['stat_wealth'],
];
$stat_labels = ['พุทธคุณ', 'เมตตามหานิยม', 'มหาเสน่ห์', 'แคล้วคลาด', 'คงกระพันชาตรี', 'โชคลาภ'];

$current_price = (float)$auction['current_price'];
$min_increment = (float)$auction['min_increment'];
$min_next_bid = $current_price + $min_increment;
$is_logged_in = !empty($_SESSION['user_id']);

render_site_header('ห้องประมูล: ' . htmlspecialchars((string)$auction['amulet_name'], ENT_QUOTES, 'UTF-8'), '.');
?>

<div style="margin-bottom: 1.5rem;">
    <a href="auctions.php" style="font-size: 0.9rem; color: var(--gold-accent);">&larr; กลับหน้ารายการประมูลทั้งหมด</a>
</div>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 2rem; align-items: start;">
    <!-- Column 1: Amulet Details & Bidding Interface -->
    <div style="background-color: var(--bg-card); border: 1px solid var(--gold-border); border-radius: var(--radius-lg); padding: 1.5rem;">
        <div style="height: 260px; overflow: hidden; display: flex; align-items: center; justify-content: center; background: linear-gradient(180deg, #3b0109 0%, #1f0004 100%); border-radius: var(--radius-md); border: 1px solid var(--gold-muted); margin-bottom: 1.5rem;">
            <?php if (!empty($auction['image_path'])): ?>
                <img src="<?php echo htmlspecialchars((string)$auction['image_path'], ENT_QUOTES, 'UTF-8'); ?>"
                     alt="<?php echo htmlspecialchars((string)$auction['amulet_name'], ENT_QUOTES, 'UTF-8'); ?>"
                     style="width: 100%; height: 100%; object-fit: contain;"
                     onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                <div style="display: none; height: 100%; width: 100%; align-items: center; justify-content: center; font-size: 5rem; color: var(--gold-accent);">
                    ⚜️
                </div>
            <?php else: ?>
                <div style="font-size: 5rem; color: var(--gold-accent);">
                    ⚜️
                </div>
            <?php endif; ?>
        </div>

        <h1 style="color: var(--gold-accent); font-size: 1.5rem; margin-bottom: 0.5rem;">
            <?php echo htmlspecialchars((string)$auction['amulet_name'], ENT_QUOTES, 'UTF-8'); ?>
        </h1>
        <p style="color: var(--text-light); font-size: 0.95rem; margin-bottom: 1.5rem;">
            วัด: <strong><?php echo htmlspecialchars((string)$auction['temple_name'], ENT_QUOTES, 'UTF-8'); ?></strong> |
            เกจิ: <strong><?php echo htmlspecialchars((string)$auction['monk_name'], ENT_QUOTES, 'UTF-8'); ?></strong> |
            ปี: <?php echo htmlspecialchars((string)($auction['year_created'] ?? 'ไม่ระบุ'), ENT_QUOTES, 'UTF-8'); ?>
        </p>

        <!-- Countdown Timer & Anti-Snipe Alert -->
        <div style="background-color: var(--bg-darker); border: 2px solid var(--gold-accent); border-radius: var(--radius-md); padding: 1.25rem; margin-bottom: 1.5rem; text-align: center; position: relative;">
            <div style="display: flex; justify-content: center; align-items: center; gap: 0.5rem; margin-bottom: 0.25rem;">
                <span style="font-size: 0.85rem; color: var(--text-light);">เวลานับถอยหลังการประมูล:</span>
                <span id="firebase_live_badge" style="display: inline-flex; align-items: center; gap: 0.35rem; font-size: 0.7rem; font-weight: 700; color: #ff7875; background: rgba(255, 77, 79, 0.15); border: 1px solid rgba(255, 77, 79, 0.4); padding: 0.15rem 0.5rem; border-radius: 999px;">
                    <span style="width: 7px; height: 7px; background: #ff4d4f; border-radius: 50%; display: inline-block; animation: pulse 1.5s infinite;"></span>
                    FIREBASE REAL-TIME LIVE
                </span>
            </div>
            <div id="live_auction_timer" class="auction_timer" style="font-size: 1.8rem; padding: 0.6rem;" data-auction-end="<?php echo htmlspecialchars(date('c', strtotime((string)$auction['end_time'])), ENT_QUOTES, 'UTF-8'); ?>">
                คำนวณเวลา...
            </div>
            <p style="font-size: 0.8rem; color: var(--gold-bright); margin-top: 0.5rem;">
                🛡️ ระบบ Anti-Snipe: เสนอราคาในช่วง 1 นาทีสุดท้าย เวลาจะบวกเพิ่ม 3 นาทีอัตโนมัติ
            </p>
        </div>

        <!-- Bidding Control Box -->
        <div style="background-color: var(--bg-darker); border: 1px solid var(--gold-border); border-radius: var(--radius-md); padding: 1.25rem; margin-bottom: 1.5rem;">
            <div style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 1rem;">
                <div>
                    <span style="font-size: 0.85rem; color: var(--text-light); display: block;">ราคาสูงสุดปัจจุบัน</span>
                    <span id="display_current_price" style="font-size: 2rem; font-weight: 700; color: var(--gold-accent);">
                        <?php echo number_format($current_price, 2); ?> ฿
                    </span>
                </div>
                <div style="text-align: right;">
                    <span style="font-size: 0.85rem; color: var(--text-light); display: block;">ราคาเสนอขั้นต่ำถัดไป</span>
                    <span id="display_min_next_bid" style="font-size: 1.2rem; font-weight: 700; color: var(--gold-bright);">
                        <?php echo number_format($min_next_bid, 2); ?> ฿
                    </span>
                </div>
            </div>

            <!-- Dynamic Notification Box for AJAX results -->
            <div id="bidding_notice" style="display: none; padding: 0.75rem 1rem; border-radius: var(--radius-md); margin-bottom: 1rem; font-size: 0.9rem;"></div>

            <?php if ($is_logged_in): ?>
                <form id="ajax_bidding_form" onsubmit="handle_submit_bid(event);" style="margin: 0;">
                    <input type="hidden" id="bid_auction_id" value="<?php echo (int)$auction['id']; ?>">
                    <div style="display: flex; gap: 0.5rem;">
                        <input type="number" id="bid_input_amount" class="form_control" style="flex: 1; font-size: 1.15rem; font-weight: 700;"
                               min="<?php echo $min_next_bid; ?>" step="<?php echo $min_increment; ?>" value="<?php echo $min_next_bid; ?>" required>
                        <button type="submit" id="btn_submit_bid" class="btn btn_gold" style="padding: 0.75rem 1.75rem; font-size: 1.05rem;">
                            เคาะประมูล (Bid)
                        </button>
                    </div>
                </form>
            <?php else: ?>
                <div style="text-align: center; padding: 1rem; background: rgba(0,0,0,0.3); border-radius: var(--radius-md);">
                    <p style="color: var(--text-light); margin-bottom: 0.75rem;">กรุณาเข้าสู่ระบบก่อนทำการเคาะราคาประมูล</p>
                    <a href="login.php" class="btn btn_gold">เข้าสู่ระบบเพื่อร่วมประมูล</a>
                </div>
            <?php endif; ?>
        </div>

        <!-- Real-time Bid History Table -->
        <h3 style="color: var(--gold-accent); margin-bottom: 0.75rem;">📜 ประวัติการเคาะราคา (Bid History)</h3>
        <div style="overflow-x: auto;">
            <table class="data_table" id="bid_history_table">
                <thead>
                    <tr>
                        <th>ผู้ประมูล</th>
                        <th>ราคาที่เสนอ</th>
                        <th>เวลาที่เคาะ</th>
                        <th>หมายเหตุ</th>
                    </tr>
                </thead>
                <tbody id="bid_history_tbody">
                    <?php if (empty($bids)): ?>
                        <tr><td colspan="4" style="text-align: center;">ยังไม่มีประวัติการเสนอราคา เริ่มเคาะเป็นคนแรกเลย!</td></tr>
                    <?php else: ?>
                        <?php foreach ($bids as $b): ?>
                            <tr>
                                <td style="font-weight: 700; color: var(--gold-accent);"><?php echo htmlspecialchars((string)$b['username'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td style="font-weight: 700; color: var(--gold-bright);"><?php echo number_format((float)$b['bid_amount'], 2); ?> ฿</td>
                                <td style="font-size: 0.85rem;"><?php echo htmlspecialchars((string)$b['created_at'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td>
                                    <?php if (!empty($b['triggered_anti_snipe'])): ?>
                                        <span style="color: var(--danger-color); font-size: 0.8rem; font-weight: bold;">+3 นาที (Anti-Snipe)</span>
                                    <?php else: ?>
                                        <span style="opacity: 0.6;">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Column 2: Module 3 Hexagonal Radar Chart Graphic -->
    <div class="radar_chart_container">
        <h2 style="color: var(--gold-accent); font-size: 1.35rem; margin-bottom: 0.5rem; text-align: center;">
            ⚜️ ค่าพลังพุทธคุณ 6 ด้าน (Amulet Status)
        </h2>
        <p style="font-size: 0.85rem; color: var(--text-light); text-align: center; margin-bottom: 1.25rem; opacity: 0.85;">
            วิเคราะห์ระดับพลังและพุทธคุณพระเครื่องตามคะแนน 1-10
        </p>

        <canvas id="auction_radar_canvas"
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

<script type="module">
import { db } from "./js/firebase_config.js";
import { doc, onSnapshot, setDoc } from "https://www.gstatic.com/firebasejs/10.8.0/firebase-firestore.js";

const auctionId = "<?php echo (int)$auction['id']; ?>";
const minIncrement = <?php echo (float)$min_increment; ?>;
const auctionDocRef = doc(db, "auctions", auctionId);

// 1. Setup Live Realtime Listener (ฟังเสียงความเปลี่ยนแปลงจาก Firebase ทันทีที่ใครก็ตามเคาะราคา)
onSnapshot(auctionDocRef, (docSnap) => {
    if (docSnap.exists()) {
        const data = docSnap.data();
        if (data.current_price) {
            const newPrice = Number(data.current_price);
            const nextMin = newPrice + minIncrement;
            
            // อัปเดตราคาสดบนหน้าจอทันที ไม่ต้องรีเฟรช
            const priceEl = document.getElementById('display_current_price');
            const minEl = document.getElementById('display_min_next_bid');
            const inputEl = document.getElementById('bid_input_amount');
            
            if (priceEl) priceEl.textContent = newPrice.toLocaleString('th-TH', { minimumFractionDigits: 2 }) + ' ฿';
            if (minEl) minEl.textContent = nextMin.toLocaleString('th-TH', { minimumFractionDigits: 2 }) + ' ฿';
            if (inputEl) {
                inputEl.setAttribute('min', nextMin);
                if (Number(inputEl.value) < nextMin) {
                    inputEl.value = nextMin;
                }
            }

            // ถ้ามี Anti-Snipe ยืดเวลา
            if (data.end_time) {
                const timerEl = document.getElementById('live_auction_timer');
                if (timerEl) {
                    timerEl.setAttribute('data-auction-end', data.end_time);
                }
            }

            // เพิ่มประวัติคนเคาะล่าสุดในตารางแบบเรียลไทม์
            if (data.latest_bidder) {
                const tbody = document.getElementById('bid_history_tbody');
                if (tbody && !document.getElementById(`bid_firebase_${data.last_bid_timestamp}`)) {
                    const tr = document.createElement('tr');
                    tr.id = `bid_firebase_${data.last_bid_timestamp}`;
                    tr.style.backgroundColor = 'rgba(233, 215, 147, 0.15)';
                    tr.innerHTML = `
                        <td style="font-weight: 700; color: var(--gold-accent);">⚡ ${data.latest_bidder}</td>
                        <td style="font-weight: 700; color: var(--gold-bright);">${newPrice.toLocaleString('th-TH', { minimumFractionDigits: 2 })} ฿</td>
                        <td style="font-size: 0.85rem; color: #73d13d;">เรียลไทม์สด</td>
                        <td>${data.anti_snipe ? '<span style="color: var(--danger-color); font-weight: bold;">+3 นาที</span>' : '-'}</td>
                    `;
                    tbody.insertBefore(tr, tbody.firstChild);
                }
            }
        }
    }
});

// 2. Form submission handler
window.handle_submit_bid = async function(event) {
    event.preventDefault();
    const btn = document.getElementById('btn_submit_bid');
    const notice = document.getElementById('bidding_notice');
    const input = document.getElementById('bid_input_amount');
    const auction_id = document.getElementById('bid_auction_id').value;
    const bid_amount = input.value;
    const min_required = input.getAttribute('min');

    btn.disabled = true;
    btn.textContent = 'กำลังส่งราคา...';
    notice.style.display = 'none';

    try {
        const result = await submit_auction_bid(auction_id, bid_amount, min_required, 'api_place_bid.php');
        
        notice.style.display = 'block';
        notice.style.background = 'rgba(82, 196, 26, 0.2)';
        notice.style.border = '1px solid var(--success-color)';
        notice.style.color = '#73d13d';
        notice.textContent = '✓ ' + result.message;

        // Push ไปยัง Firebase Cloud Firestore เพื่อกระจายไปยังหน้าจอของทุกคนแบบ Real-time ทันที
        await setDoc(auctionDocRef, {
            current_price: Number(result.current_price),
            end_time: result.end_time || null,
            latest_bidder: result.username || "สมาชิก",
            last_bid_timestamp: Date.now(),
            anti_snipe: result.anti_snipe_extended || false
        }, { merge: true });

    } catch (err) {
        notice.style.display = 'block';
        notice.style.background = 'rgba(255, 77, 79, 0.2)';
        notice.style.border = '1px solid var(--danger-color)';
        notice.style.color = '#ff7875';
        notice.textContent = '⚠️ ' + err.message;
    } finally {
        btn.disabled = false;
        btn.textContent = 'เคาะประมูล (Bid)';
    }
};
</script>

<?php
render_site_footer('.', true);
?>

