<?php
declare(strict_types=1);

/**
 * Amulet & Collector Leaderboards & Hall of Fame (ทำเนียบเซียน & พระแชมป์).
 *
 * File: leaderboard.php
 * Mutation Log: 2026-11-04 - Built dual leaderboard with Amulet Rankings, Collector Rankings, and Hall of Fame.
 */

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/footer.php';
require_once __DIR__ . '/shared/services/leaderboard_service.php';

$active_tab = $_GET['tab'] ?? 'amulets';
$sacred_prop = $_GET['prop'] ?? 'wealth';

$top_graded = get_top_graded_masterpieces(5);
$val_bids = get_top_value_and_bids();
$top_properties = get_top_by_sacred_properties($sacred_prop, 5);
$trending = get_trending_amulets(5);
$collectors = get_collector_leaderboard();

render_site_header('ทำเนียบพระแชมป์ & เซียนพระ (Leaderboard & Hall of Fame)', '.');
?>

<div style="max-width: 1200px; margin: 0 auto; padding-bottom: 3rem;">
    <!-- Title Banner -->
    <div style="text-align: center; margin-bottom: 2rem;">
        <span style="font-size: 0.85rem; color: var(--gold-bright); font-weight: 700; letter-spacing: 2px; text-transform: uppercase;">
            RANKING & HALL OF FAME
        </span>
        <h1 style="color: var(--gold-accent); margin-top: 0.35rem; font-size: 2.2rem;">
            🏆 ทำเนียบพระแชมป์ & หอเกียรติยศเซียนพระ
        </h1>
        <p style="color: var(--text-light); opacity: 0.85; max-width: 650px; margin: 0.5rem auto 1.5rem auto;">
            จัดอันดับสุดยอดพระเครื่องที่ผ่านการประเมินเกรด มูลค่าสูงสุด และทำเนียบนักสะสมพระเครื่องแถวหน้าของวงการ
        </p>

        <!-- Main Switcher Tabs -->
        <div style="display: inline-flex; background: var(--bg-card); border: 1px solid var(--gold-border); border-radius: 999px; padding: 0.35rem; gap: 0.5rem;">
            <a href="leaderboard.php?tab=amulets"
               style="padding: 0.6rem 1.75rem; border-radius: 999px; font-weight: 700; font-size: 0.95rem; text-decoration: none; <?php echo $active_tab === 'amulets' ? 'background: var(--gold-accent); color: #1a0003;' : 'color: var(--text-light);'; ?>">
                ⚜️ จัดอันดับฝั่งองค์พระ (Amulets)
            </a>
            <a href="leaderboard.php?tab=collectors"
               style="padding: 0.6rem 1.75rem; border-radius: 999px; font-weight: 700; font-size: 0.95rem; text-decoration: none; <?php echo $active_tab === 'collectors' ? 'background: var(--gold-accent); color: #1a0003;' : 'color: var(--text-light);'; ?>">
                👑 ทำเนียบเซียน & หอเกียรติยศ (Collectors)
            </a>
        </div>
    </div>

    <?php if ($active_tab === 'amulets'): ?>
        <!-- ======================= AMULET LEADERBOARD ======================= -->

        <!-- Section 1: Top Graded Masterpieces (ทำเนียบพระแชมป์เกรด PSA) -->
        <div style="background: linear-gradient(180deg, #2a0006 0%, #1a0003 100%); border: 2px solid var(--gold-accent); border-radius: var(--radius-lg); padding: 1.5rem; margin-bottom: 2.5rem; box-shadow: var(--shadow-gold);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem; flex-wrap: wrap; gap: 0.5rem;">
                <div>
                    <h2 style="color: var(--gold-accent); font-size: 1.35rem; margin: 0;">
                        🎖️ ทำเนียบพระแชมป์ (Top Graded Masterpieces)
                    </h2>
                    <p style="font-size: 0.85rem; color: var(--text-light); opacity: 0.8; margin-top: 0.2rem;">
                        พระเครื่องที่ผ่านการรับรองและได้เกรดความสมบูรณ์สูงสุด (PSA 1-10) ชี้ขาดด้วยความหายาก
                    </p>
                </div>
                <span style="font-size: 0.8rem; background: #000; color: #73d13d; border: 1px solid var(--success-color); padding: 0.25rem 0.75rem; border-radius: 999px; font-weight: 700;">
                    ✓ CERTIFIED SLABS ONLY
                </span>
            </div>

            <div class="amulet_grid">
                <?php foreach ($top_graded as $idx => $m): ?>
                    <div class="amulet_card" style="border-color: <?php echo $idx === 0 ? 'var(--gold-accent)' : 'var(--gold-muted)'; ?>;">
                        <div style="position: relative; overflow: hidden; background: #1a0003; height: 180px;">
                            <span style="position: absolute; top: 8px; left: 8px; background: <?php echo $idx === 0 ? '#faad14' : '#111'; ?>; color: <?php echo $idx === 0 ? '#000' : '#fff'; ?>; font-weight: 900; font-size: 0.8rem; width: 26px; height: 26px; display: flex; align-items: center; justify-content: center; border-radius: 50%; z-index: 2;">
                                #<?php echo $idx + 1; ?>
                            </span>

                            <span style="position: absolute; top: 8px; right: 8px; background: #000; color: var(--gold-bright); border: 1px solid var(--gold-accent); font-weight: 800; font-size: 0.75rem; padding: 0.2rem 0.5rem; border-radius: 4px; z-index: 2;">
                                GRADE <?php echo number_format((float)$m['grade_score'], 1); ?>
                            </span>

                            <img src="<?php echo htmlspecialchars((string)$m['image_path'], ENT_QUOTES, 'UTF-8'); ?>"
                                 alt="Amulet" style="width: 100%; height: 100%; object-fit: cover;">
                        </div>

                        <div class="amulet_card_body">
                            <h3 class="amulet_title" style="font-size: 1rem;"><?php echo htmlspecialchars((string)$m['name'], ENT_QUOTES, 'UTF-8'); ?></h3>
                            <p class="amulet_meta" style="font-size: 0.8rem;">
                                เกจิ: <strong><?php echo htmlspecialchars((string)$m['monk_name'], ENT_QUOTES, 'UTF-8'); ?></strong>
                            </p>
                            <p class="amulet_meta" style="font-size: 0.8rem; color: #73d13d;">
                                ผู้ครอบครอง: <strong><?php echo htmlspecialchars((string)$m['current_owner_name'], ENT_QUOTES, 'UTF-8'); ?></strong>
                            </p>
                            <div style="margin-top: 0.75rem;">
                                <a href="verify_cert.php?cert_no=<?php echo urlencode((string)$m['cert_number']); ?>"
                                   class="btn btn_gold" style="width: 100%; display: block; text-align: center; font-size: 0.8rem; padding: 0.4rem;">
                                    ตรวจใบเซอร์ & ซูมตำหนิ ↗
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Section 2: Top Value & Most Bids (มูลค่าและสถิติประมูลสูงสุด) -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 1.5rem; margin-bottom: 2.5rem;">
            <!-- Top Value -->
            <div style="background-color: var(--bg-card); border: 1px solid var(--gold-border); border-radius: var(--radius-lg); padding: 1.5rem;">
                <h3 style="color: var(--gold-accent); font-size: 1.15rem; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
                    💎 พระเครื่องมูลค่าสูงสุด (Top Market Value)
                </h3>
                <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                    <?php foreach ($val_bids['top_value'] as $i => $v): ?>
                        <div style="display: flex; align-items: center; justify-content: space-between; padding: 0.6rem 0.8rem; background: var(--bg-darker); border-radius: var(--radius-md); border-left: 3px solid var(--gold-accent);">
                            <div style="display: flex; align-items: center; gap: 0.6rem;">
                                <span style="font-weight: 800; color: var(--gold-bright); font-size: 0.9rem;">#<?php echo $i + 1; ?></span>
                                <div>
                                    <a href="amulet_detail.php?id=<?php echo (int)$v['id']; ?>" style="font-weight: 700; font-size: 0.9rem; color: #fff;">
                                        <?php echo htmlspecialchars((string)$v['name'], ENT_QUOTES, 'UTF-8'); ?>
                                    </a>
                                    <div style="font-size: 0.75rem; color: var(--text-light); opacity: 0.7;">
                                        วัด: <?php echo htmlspecialchars((string)$v['temple_name'], ENT_QUOTES, 'UTF-8'); ?>
                                    </div>
                                </div>
                            </div>
                            <span style="font-weight: 800; color: var(--gold-accent); font-size: 1rem;">
                                <?php echo number_format((float)$v['benchmark_val'], 2); ?> ฿
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Most Heated Bids -->
            <div style="background-color: var(--bg-card); border: 1px solid var(--gold-border); border-radius: var(--radius-lg); padding: 1.5rem;">
                <h3 style="color: var(--gold-accent); font-size: 1.15rem; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
                    🔥 สู้ราคาประมูลดุเดือดที่สุด (Most Heated Bids)
                </h3>
                <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                    <?php if (empty($val_bids['most_bids'])): ?>
                        <p style="color: var(--text-light); opacity: 0.7; font-size: 0.85rem;">ยังไม่มีประวัติการประมูล</p>
                    <?php else: ?>
                        <?php foreach ($val_bids['most_bids'] as $i => $b): ?>
                            <div style="display: flex; align-items: center; justify-content: space-between; padding: 0.6rem 0.8rem; background: var(--bg-darker); border-radius: var(--radius-md); border-left: 3px solid #ff4d4f;">
                                <div style="display: flex; align-items: center; gap: 0.6rem;">
                                    <span style="font-weight: 800; color: #ff7875; font-size: 0.9rem;">#<?php echo $i + 1; ?></span>
                                    <div>
                                        <a href="auction_detail.php?id=<?php echo (int)$b['auction_id']; ?>" style="font-weight: 700; font-size: 0.9rem; color: #fff;">
                                            <?php echo htmlspecialchars((string)$b['name'], ENT_QUOTES, 'UTF-8'); ?>
                                        </a>
                                        <div style="font-size: 0.75rem; color: #ff7875;">
                                            เคาะสู้ราคาไปแล้ว <strong><?php echo (int)$b['total_bids_count']; ?></strong> ครั้ง
                                        </div>
                                    </div>
                                </div>
                                <span style="font-weight: 800; color: var(--gold-accent); font-size: 1rem;">
                                    <?php echo number_format((float)$b['current_price'], 2); ?> ฿
                                </span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Section 3: Top by Sacred Properties (ท็อปชาร์ตตามสายสรรพคุณ) -->
        <div style="background-color: var(--bg-card); border: 1px solid var(--gold-border); border-radius: var(--radius-lg); padding: 1.5rem; margin-bottom: 2.5rem;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem; flex-wrap: wrap; gap: 1rem;">
                <div>
                    <h2 style="color: var(--gold-accent); font-size: 1.25rem; margin: 0;">
                        ⚡ ท็อปชาร์ตตามสายสรรพคุณ (Top by Sacred Properties)
                    </h2>
                    <p style="font-size: 0.85rem; color: var(--text-light); opacity: 0.8; margin-top: 0.2rem;">
                        แยกกระดานตามสายพลังพุทธคุณเฉพาะทางที่ผู้ศรัทธาแสวงหา
                    </p>
                </div>
                <!-- Property Sub Tabs -->
                <div style="display: flex; gap: 0.4rem;">
                    <a href="leaderboard.php?tab=amulets&prop=wealth" class="btn <?php echo $sacred_prop === 'wealth' ? 'btn_gold' : 'btn_outline'; ?>" style="font-size: 0.8rem; padding: 0.35rem 0.75rem;">
                        💰 สายโชคลาภ
                    </a>
                    <a href="leaderboard.php?tab=amulets&prop=invulnerable" class="btn <?php echo $sacred_prop === 'invulnerable' ? 'btn_gold' : 'btn_outline'; ?>" style="font-size: 0.8rem; padding: 0.35rem 0.75rem;">
                        🛡️ สายคงกระพัน
                    </a>
                    <a href="leaderboard.php?tab=amulets&prop=kindness" class="btn <?php echo $sacred_prop === 'kindness' ? 'btn_gold' : 'btn_outline'; ?>" style="font-size: 0.8rem; padding: 0.35rem 0.75rem;">
                        🌸 สายเมตตามหานิยม
                    </a>
                </div>
            </div>

            <div class="amulet_grid">
                <?php foreach ($top_properties as $p): ?>
                    <div class="amulet_card">
                        <div style="height: 180px; overflow: hidden; background: #1a0003;">
                            <img src="<?php echo htmlspecialchars((string)$p['image_path'], ENT_QUOTES, 'UTF-8'); ?>"
                                 alt="Amulet" style="width: 100%; height: 100%; object-fit: cover;">
                        </div>
                        <div class="amulet_card_body">
                            <h3 class="amulet_title" style="font-size: 0.95rem;"><?php echo htmlspecialchars((string)$p['name'], ENT_QUOTES, 'UTF-8'); ?></h3>
                            <p class="amulet_meta" style="font-size: 0.8rem;">เกจิ: <strong><?php echo htmlspecialchars((string)$p['monk_name'], ENT_QUOTES, 'UTF-8'); ?></strong></p>
                            <div style="margin-top: 0.5rem; display: flex; justify-content: space-between; align-items: center;">
                                <span class="amulet_price" style="font-size: 1.1rem;"><?php echo number_format((float)$p['price'], 2); ?> ฿</span>
                                <a href="amulet_detail.php?id=<?php echo (int)$p['id']; ?>" class="btn btn_outline" style="font-size: 0.75rem; padding: 0.3rem 0.6rem;">ดูสแตตัส</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Section 4: Trending Amulets (พระยอดฮิตมาแรง) -->
        <div style="background-color: var(--bg-card); border: 1px solid var(--gold-border); border-radius: var(--radius-lg); padding: 1.5rem;">
            <h2 style="color: var(--gold-accent); font-size: 1.25rem; margin-bottom: 0.35rem;">
                🚀 พระยอดฮิตมาแรง (Trending Amulets)
            </h2>
            <p style="font-size: 0.85rem; color: var(--text-light); opacity: 0.8; margin-bottom: 1.25rem;">
                คำนวณจากสถิติยอดผู้เข้าชมจริงและจำนวนผู้กดบันทึกเข้า Wishlist
            </p>

            <div class="amulet_grid">
                <?php foreach ($trending as $t): ?>
                    <div class="amulet_card">
                        <div style="height: 180px; overflow: hidden; background: #1a0003;">
                            <img src="<?php echo htmlspecialchars((string)$t['image_path'], ENT_QUOTES, 'UTF-8'); ?>"
                                 alt="Amulet" style="width: 100%; height: 100%; object-fit: cover;">
                        </div>
                        <div class="amulet_card_body">
                            <h3 class="amulet_title" style="font-size: 0.95rem;"><?php echo htmlspecialchars((string)$t['name'], ENT_QUOTES, 'UTF-8'); ?></h3>
                            <div style="display: flex; gap: 0.5rem; font-size: 0.75rem; color: var(--gold-bright); margin-top: 0.25rem;">
                                <span>👁️ <?php echo number_format((int)$t['view_count']); ?> วิว</span>
                                <span>❤️ <?php echo number_format((int)$t['wishlist_count']); ?> บุ๊กมาร์ก</span>
                            </div>
                            <div style="margin-top: 0.75rem;">
                                <a href="amulet_detail.php?id=<?php echo (int)$t['id']; ?>" class="btn btn_gold" style="width: 100%; display: block; text-align: center; font-size: 0.8rem; padding: 0.35rem;">
                                    เข้าชมองค์นี้
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

    <?php else: ?>
        <!-- ======================= COLLECTOR LEADERBOARD & HALL OF FAME ======================= -->

        <!-- Section 1: All-Time Hall of Fame (หอเกียรติยศเซียนพระตลอดกาล) -->
        <div style="background: linear-gradient(135deg, #1f0004 0%, #3b0109 50%, #1a0003 100%); border: 3px solid var(--gold-accent); border-radius: var(--radius-lg); padding: 2rem; margin-bottom: 2.5rem; box-shadow: 0 8px 32px rgba(0,0,0,0.7); text-align: center;">
            <span style="font-size: 0.85rem; color: var(--gold-bright); letter-spacing: 2px; text-transform: uppercase; font-weight: 800;">
                ★ ALL-TIME HALL OF FAME ★
            </span>
            <h2 style="color: #fff; font-size: 1.8rem; margin: 0.4rem 0 0.8rem 0;">
                🏛️ หอเกียรติยศเซียนพระตลอดกาล (Hall of Fame)
            </h2>
            <p style="color: var(--text-light); opacity: 0.85; max-width: 600px; margin: 0 auto 2rem auto; font-size: 0.9rem;">
                กระดานอันดับเกียรติยศระดับตำนานที่ไม่มีวันถูกรีเซ็ต บันทึกประวัติศาสตร์สุดยอดเซียนพระผู้สร้างคุณูปการแก่ระบบ
            </p>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.5rem; text-align: left;">
                <?php foreach ($collectors['top_traders'] as $tr): ?>
                    <div style="background: rgba(0,0,0,0.45); border: 1.5px solid var(--gold-accent); border-radius: var(--radius-md); padding: 1.25rem;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem;">
                            <span style="font-size: 0.75rem; background: var(--gold-accent); color: #000; font-weight: 800; padding: 0.2rem 0.6rem; border-radius: 4px;">
                                <?php echo htmlspecialchars((string)($tr['hall_of_fame_year'] ?? 'สมาชิกกิตติมศักดิ์'), ENT_QUOTES, 'UTF-8'); ?>
                            </span>
                            <span style="color: #ffd666; font-size: 1.2rem;">⚜️</span>
                        </div>
                        <h3 style="color: #fff; font-size: 1.15rem; margin-bottom: 0.25rem;">
                            <?php echo htmlspecialchars((string)$tr['full_name'], ENT_QUOTES, 'UTF-8'); ?>
                        </h3>
                        <div style="font-size: 0.85rem; color: var(--gold-bright); font-weight: 700; margin-bottom: 0.75rem;">
                            🎖️ <?php echo htmlspecialchars((string)$tr['honor_title'], ENT_QUOTES, 'UTF-8'); ?>
                        </div>
                        <div style="border-top: 1px dashed rgba(233,215,147,0.3); padding-top: 0.5rem; font-size: 0.8rem; color: var(--text-light); opacity: 0.85; display: flex; justify-content: space-between;">
                            <span>การซื้อขายสำเร็จ: <strong><?php echo (int)$tr['successful_deals']; ?> ครั้ง</strong></span>
                            <span>คะแนนรีวิว: <strong style="color: #73d13d;">★ <?php echo number_format((float)$tr['rating_score'], 1); ?></strong></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Section 2: เจ้ากรมพระ (Highest Portfolio Value) & นักสะสมของแรร์ (Top Quality) -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(340px, 1fr)); gap: 1.5rem;">
            <!-- เจ้ากรมพระ -->
            <div style="background-color: var(--bg-card); border: 1px solid var(--gold-border); border-radius: var(--radius-lg); padding: 1.5rem;">
                <h3 style="color: var(--gold-accent); font-size: 1.15rem; margin-bottom: 0.35rem; display: flex; align-items: center; gap: 0.4rem;">
                    👑 เจ้ากรมพระ (Highest Portfolio Value)
                </h3>
                <p style="font-size: 0.8rem; color: var(--text-light); opacity: 0.75; margin-bottom: 1.25rem;">
                    จัดอันดับผู้ถือครองพอร์ตโฟลิโอพระเครื่องมูลค่าประเมินรวมสูงที่สุด
                </p>

                <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                    <?php foreach ($collectors['top_portfolio'] as $idx => $user): ?>
                        <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.75rem; background: var(--bg-darker); border-radius: var(--radius-md); border-left: 3px solid var(--gold-accent);">
                            <div>
                                <div style="display: flex; align-items: center; gap: 0.5rem;">
                                    <span style="font-weight: 800; color: var(--gold-bright);">#<?php echo $idx + 1; ?></span>
                                    <strong style="color: #fff; font-size: 0.95rem;"><?php echo htmlspecialchars((string)$user['full_name'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                </div>
                                <div style="font-size: 0.75rem; color: var(--gold-accent); margin-top: 0.15rem;">
                                    ฉายา: <?php echo htmlspecialchars((string)($user['honor_title'] ?? 'นักสะสมพระเครื่อง'), ENT_QUOTES, 'UTF-8'); ?>
                                </div>
                            </div>
                            <div style="text-align: right;">
                                <span style="font-weight: 800; color: #73d13d; font-size: 1.05rem; display: block;">
                                    <?php echo number_format((float)$user['portfolio_market_value'], 2); ?> ฿
                                </span>
                                <span style="font-size: 0.7rem; color: var(--text-light); opacity: 0.7;">
                                    มีในกรุ <?php echo (int)$user['total_items_count']; ?> องค์
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- นักสะสมของแรร์ -->
            <div style="background-color: var(--bg-card); border: 1px solid var(--gold-border); border-radius: var(--radius-lg); padding: 1.5rem;">
                <h3 style="color: var(--gold-accent); font-size: 1.15rem; margin-bottom: 0.35rem; display: flex; align-items: center; gap: 0.4rem;">
                    💎 นักสะสมของแรร์ (Top Quality Collector)
                </h3>
                <p style="font-size: 0.8rem; color: var(--text-light); opacity: 0.75; margin-bottom: 1.25rem;">
                    ตัดสินจากคะแนนเกรดความสมบูรณ์พระแท้ (PSA) สภาพสวยเดิม ไม่เน้นปริมาณ
                </p>

                <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                    <?php foreach ($collectors['top_quality'] as $idx => $q): ?>
                        <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.75rem; background: var(--bg-darker); border-radius: var(--radius-md); border-left: 3px solid #73d13d;">
                            <div>
                                <div style="display: flex; align-items: center; gap: 0.5rem;">
                                    <span style="font-weight: 800; color: #73d13d;">#<?php echo $idx + 1; ?></span>
                                    <strong style="color: #fff; font-size: 0.95rem;"><?php echo htmlspecialchars((string)$q['full_name'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                </div>
                                <div style="font-size: 0.75rem; color: var(--gold-bright); margin-top: 0.15rem;">
                                    ฉายา: <?php echo htmlspecialchars((string)($q['honor_title'] ?? 'สายแรร์คัดสวย'), ENT_QUOTES, 'UTF-8'); ?>
                                </div>
                            </div>
                            <div style="text-align: right;">
                                <span style="font-weight: 900; color: var(--gold-accent); font-size: 1.1rem; display: block;">
                                    เกรดสูงสุด <?php echo number_format((float)$q['highest_grade'], 1); ?>
                                </span>
                                <span style="font-size: 0.7rem; color: #73d13d;">
                                    ผ่านเกรดแล้ว <?php echo (int)$q['certified_count']; ?> องค์
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

    <?php endif; ?>
</div>

<?php
render_site_footer('.', false);
?>
