<?php
declare(strict_types=1);

/**
 * Public PSA-Style Certificate & QR Verification Page (Anti-Counterfeit Protection).
 *
 * File: verify_cert.php
 * Mutation Log: 2026-11-04 - Built verification view with grade slab, zoom defects inspection, and provenance tracking.
 */

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/footer.php';
require_once __DIR__ . '/shared/services/cert_service.php';

$cert_no = trim((string)($_GET['cert_no'] ?? ''));
$cert = null;

if ($cert_no !== '') {
    $cert = get_certificate_by_cert_number($cert_no);
}

render_site_header('ระบบตรวจสอบใบเซอร์พระแท้ (QR Verification)', '.');
?>

<div style="max-width: 900px; margin: 0 auto; padding-bottom: 3rem;">
    <!-- Search Bar by Certificate Number -->
    <div style="background-color: var(--bg-card); border: 1px solid var(--gold-border); border-radius: var(--radius-lg); padding: 1.5rem; margin-bottom: 2rem;">
        <h2 style="color: var(--gold-accent); font-size: 1.35rem; margin-bottom: 0.5rem; text-align: center;">
            🛡️ ระบบตรวจสอบใบเซอร์พระแท้ & เกรดมาตรฐานสากล (Amulet Grading Verification)
        </h2>
        <p style="text-align: center; color: var(--text-light); opacity: 0.9; font-size: 0.9rem; margin-bottom: 1.25rem;">
            ป้องกันการสวมรอยพระแท้ด้วยระบบ QR Code ประจำองค์ ตรวจสอบประวัติ เจ้าของปัจจุบัน และภาพซูมจุดตำหนิพิมพ์
        </p>

        <form method="GET" action="verify_cert.php" style="display: flex; gap: 0.5rem; max-width: 600px; margin: 0 auto;">
            <input type="text" name="cert_no" required class="form_control" style="flex: 1;"
                   placeholder="ระบุเลขที่ใบเซอร์ เช่น PSA-AMU-2026-0005"
                   value="<?php echo htmlspecialchars($cert_no, ENT_QUOTES, 'UTF-8'); ?>">
            <button type="submit" class="btn btn_gold" style="white-space: nowrap;">🔍 ตรวจสอบพระแท้</button>
        </form>
    </div>

    <?php if ($cert_no !== '' && !$cert): ?>
        <div style="text-align: center; padding: 3rem 1.5rem; background: var(--bg-card); border: 1px dashed var(--danger-color); border-radius: var(--radius-lg);">
            <div style="font-size: 3.5rem; margin-bottom: 0.5rem;">⚠️</div>
            <h3 style="color: var(--danger-color); margin-bottom: 0.5rem;">ไม่พบข้อมูลใบรับรองหมายเลข <?php echo htmlspecialchars($cert_no, ENT_QUOTES, 'UTF-8'); ?></h3>
            <p style="color: var(--text-light); opacity: 0.9;">โปรดตรวจสอบหมายเลขอีกครั้ง หรือระวังการแอบอ้างใบรับรองพระแท้ปลอม</p>
        </div>
    <?php elseif ($cert): ?>
        <?php 
            $qr_code_url = generate_cert_qr_url((string)$cert['cert_number'], 200);
            $grade_score = (float)$cert['grade_score'];
            $is_gem_mint = $grade_score >= 9.5;
        ?>

        <!-- PSA Style Certified Slab Card -->
        <div style="background: linear-gradient(180deg, #1f0004 0%, #3b0109 50%, #1f0004 100%); border: 3px solid var(--gold-accent); border-radius: var(--radius-lg); box-shadow: 0 8px 32px rgba(0,0,0,0.6); overflow: hidden; margin-bottom: 2rem;">
            
            <!-- Top Header Badge: PSA Style Grading Header -->
            <div style="background: linear-gradient(90deg, #111 0%, #2a0005 50%, #111 100%); border-bottom: 2px solid var(--gold-accent); padding: 1.25rem 1.5rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
                <div>
                    <span style="font-size: 0.75rem; letter-spacing: 1.5px; text-transform: uppercase; color: var(--gold-bright); font-weight: 700;">
                        AUTHENTICATED & GRADED
                    </span>
                    <h3 style="color: #fff; margin: 0.2rem 0; font-size: 1.2rem;">
                        <?php echo htmlspecialchars((string)$cert['issuer_org'], ENT_QUOTES, 'UTF-8'); ?>
                    </h3>
                    <span style="font-size: 0.85rem; color: var(--gold-accent);">
                        เลขรหัสใบเซอร์: <strong><?php echo htmlspecialchars((string)$cert['cert_number'], ENT_QUOTES, 'UTF-8'); ?></strong>
                    </span>
                </div>

                <!-- Grading Badge -->
                <div style="background: #000; border: 2px solid <?php echo $is_gem_mint ? '#E9D793' : '#faad14'; ?>; border-radius: 8px; padding: 0.5rem 1.25rem; text-align: center;">
                    <span style="font-size: 0.75rem; color: #fff; display: block; font-weight: 600;">GRADE SCORE</span>
                    <div style="font-size: 2.2rem; font-weight: 900; color: <?php echo $is_gem_mint ? '#E9D793' : '#faad14'; ?>; line-height: 1;">
                        <?php echo number_format($grade_score, 1); ?>
                    </div>
                    <span style="font-size: 0.75rem; color: #73d13d; font-weight: bold; letter-spacing: 1px;">
                        <?php echo htmlspecialchars((string)$cert['grade_label'], ENT_QUOTES, 'UTF-8'); ?>
                    </span>
                </div>
            </div>

            <!-- Body Details -->
            <div style="padding: 1.5rem; display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.5rem; align-items: center;">
                
                <!-- Amulet Photo & QR Code -->
                <div style="display: flex; gap: 1rem; align-items: center; justify-content: center; flex-direction: column;">
                    <div style="width: 220px; height: 260px; overflow: hidden; border-radius: var(--radius-md); border: 2px solid var(--gold-border); background: #000;">
                        <?php if (!empty($cert['amulet_image_path'])): ?>
                            <img src="<?php echo htmlspecialchars((string)$cert['amulet_image_path'], ENT_QUOTES, 'UTF-8'); ?>"
                                 alt="Amulet" style="width: 100%; height: 100%; object-fit: contain;">
                        <?php else: ?>
                            <div style="height: 100%; display: flex; align-items: center; justify-content: center; font-size: 5rem; color: var(--gold-accent);">⚜️</div>
                        <?php endif; ?>
                    </div>

                    <!-- QR Code with Scanner Link -->
                    <div style="display: flex; align-items: center; gap: 0.75rem; background: rgba(0,0,0,0.4); padding: 0.75rem; border-radius: 8px; border: 1px dashed var(--gold-muted);">
                        <img src="<?php echo htmlspecialchars($qr_code_url, ENT_QUOTES, 'UTF-8'); ?>"
                             alt="Certificate QR Code" style="width: 85px; height: 85px; background: #fff; padding: 4px; border-radius: 4px;">
                        <div>
                            <span style="font-size: 0.75rem; color: var(--gold-accent); display: block; font-weight: 700;">QR VERIFIED CODE</span>
                            <span style="font-size: 0.7rem; color: var(--text-light); opacity: 0.8; display: block;">สแกนเพื่อตรวจสอบของแท้จากมือถือได้ทันที</span>
                        </div>
                    </div>
                </div>

                <!-- Provenance & Official Certificate Specifications -->
                <div>
                    <h2 style="color: var(--gold-accent); font-size: 1.4rem; margin-bottom: 0.75rem;">
                        <?php echo htmlspecialchars((string)$cert['amulet_name'], ENT_QUOTES, 'UTF-8'); ?>
                    </h2>

                    <!-- Anti-Counterfeit Owner Provenance Card -->
                    <div style="background: rgba(82, 196, 26, 0.15); border: 1px solid var(--success-color); border-radius: var(--radius-md); padding: 0.85rem; margin-bottom: 1rem;">
                        <span style="font-size: 0.75rem; color: #73d13d; font-weight: 700; display: block; text-transform: uppercase;">
                            ✓ ผู้ครอบครองปัจจุบันที่ได้รับการยืนยัน (Current Provenance Owner)
                        </span>
                        <div style="font-size: 1.15rem; font-weight: 700; color: #fff; margin-top: 0.2rem;">
                            👤 <?php echo htmlspecialchars((string)$cert['current_owner_name'], ENT_QUOTES, 'UTF-8'); ?>
                        </div>
                        <span style="font-size: 0.75rem; color: #e6f7ff; opacity: 0.85;">
                            ป้องกันการสวมรอยพระแท้ หากผู้ขายไม่ใช่ชื่อนี้ โปรดระวังการแอบอ้างภาพถ่าย
                        </span>
                    </div>

                    <div style="background: rgba(0,0,0,0.3); border-radius: var(--radius-md); padding: 1rem; font-size: 0.9rem; color: var(--text-light); line-height: 1.8;">
                        <p><strong>หมวดหมู่พระ:</strong> <?php echo htmlspecialchars((string)$cert['category_name'], ENT_QUOTES, 'UTF-8'); ?></p>
                        <p><strong>วัด:</strong> <?php echo htmlspecialchars((string)$cert['temple_name'], ENT_QUOTES, 'UTF-8'); ?></p>
                        <p><strong>เกจิอาจารย์:</strong> <?php echo htmlspecialchars((string)$cert['monk_name'], ENT_QUOTES, 'UTF-8'); ?></p>
                        <p><strong>ปีสร้าง:</strong> <?php echo htmlspecialchars((string)($cert['year_created'] ?? 'ไม่ระบุ'), ENT_QUOTES, 'UTF-8'); ?></p>
                        <p><strong>วันที่ออกใบรับรอง:</strong> <?php echo htmlspecialchars(date('d/m/Y', strtotime((string)$cert['issue_date'])), ENT_QUOTES, 'UTF-8'); ?></p>
                        <p><strong>ผู้เชี่ยวชาญผู้ตรวจประเมิน:</strong> <?php echo htmlspecialchars((string)$cert['inspector_name'], ENT_QUOTES, 'UTF-8'); ?></p>
                        <p style="word-break: break-all; margin-top: 0.5rem; font-size: 0.75rem; opacity: 0.7;">
                            <strong>Security Signature Hash:</strong> <code><?php echo htmlspecialchars((string)$cert['security_hash'], ENT_QUOTES, 'UTF-8'); ?></code>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section 2: Defect Inspection & High-Magnification Zoom (จุดตำหนิพิสูจน์แท้) -->
        <div style="background-color: var(--bg-card); border: 1px solid var(--gold-border); border-radius: var(--radius-lg); padding: 1.5rem;">
            <div style="text-align: center; margin-bottom: 1.5rem;">
                <h3 style="color: var(--gold-accent); font-size: 1.25rem; margin-bottom: 0.35rem;">
                    🔬 ภาพถ่ายซูมรอยตำหนิและจุดชี้ขาดพระแท้ (Micro Defect & Authentication Inspection)
                </h3>
                <p style="font-size: 0.85rem; color: var(--text-light); opacity: 0.85;">
                    เปรียบเทียบรอยตัด รอยรานธรรมชาติ โค้ดแท้ และมวลสาร เพื่อความมั่นใจ 100% ว่าตรงกับองค์จริง
                </p>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.5rem;">
                <!-- Defect 1 -->
                <div style="background: var(--bg-darker); border: 1px solid var(--gold-muted); border-radius: var(--radius-md); padding: 1rem; text-align: center;">
                    <div style="height: 220px; overflow: hidden; border-radius: 6px; border: 1px solid rgba(233,215,147,0.3); background: #000; margin-bottom: 0.75rem; position: relative;">
                        <?php if (!empty($cert['defect_zoom_image_1'])): ?>
                            <img src="<?php echo htmlspecialchars((string)$cert['defect_zoom_image_1'], ENT_QUOTES, 'UTF-8'); ?>"
                                 alt="Defect 1" style="width: 100%; height: 100%; object-fit: cover; transform: scale(1.4); transition: transform 0.3s;"
                                 onmouseover="this.style.transform='scale(2.2)'" onmouseout="this.style.transform='scale(1.4)'">
                        <?php else: ?>
                            <div style="height: 100%; display: flex; align-items: center; justify-content: center; color: var(--gold-accent);">🔬 ซูมตำหนิ 1</div>
                        <?php endif; ?>
                    </div>
                    <span style="font-size: 0.75rem; color: var(--gold-bright); font-weight: 700; display: block;">จุดสังเกตที่ 1 (นำเมาส์ชี้เพื่อซูมขยาย)</span>
                    <strong style="font-size: 0.95rem; color: #fff;"><?php echo htmlspecialchars((string)($cert['defect_title_1'] ?? 'มวลสารแท้'), ENT_QUOTES, 'UTF-8'); ?></strong>
                </div>

                <!-- Defect 2 -->
                <div style="background: var(--bg-darker); border: 1px solid var(--gold-muted); border-radius: var(--radius-md); padding: 1rem; text-align: center;">
                    <div style="height: 220px; overflow: hidden; border-radius: 6px; border: 1px solid rgba(233,215,147,0.3); background: #000; margin-bottom: 0.75rem; position: relative;">
                        <?php if (!empty($cert['defect_zoom_image_2'])): ?>
                            <img src="<?php echo htmlspecialchars((string)$cert['defect_zoom_image_2'], ENT_QUOTES, 'UTF-8'); ?>"
                                 alt="Defect 2" style="width: 100%; height: 100%; object-fit: cover; transform: scale(1.4); transition: transform 0.3s;"
                                 onmouseover="this.style.transform='scale(2.2)'" onmouseout="this.style.transform='scale(1.4)'">
                        <?php else: ?>
                            <div style="height: 100%; display: flex; align-items: center; justify-content: center; color: var(--gold-accent);">🔬 ซูมตำหนิ 2</div>
                        <?php endif; ?>
                    </div>
                    <span style="font-size: 0.75rem; color: var(--gold-bright); font-weight: 700; display: block;">จุดสังเกตที่ 2 (นำเมาส์ชี้เพื่อซูมขยาย)</span>
                    <strong style="font-size: 0.95rem; color: #fff;"><?php echo htmlspecialchars((string)($cert['defect_title_2'] ?? 'รอยตัดขอบข้าง'), ENT_QUOTES, 'UTF-8'); ?></strong>
                </div>
            </div>

            <div style="margin-top: 1.5rem; text-align: center;">
                <a href="amulet_detail.php?id=<?php echo (int)$cert['amulet_id']; ?>" class="btn btn_gold" style="padding: 0.6rem 1.5rem;">
                    ดูรายละเอียดพระเครื่ององค์นี้ในหน้าร้าน &rarr;
                </a>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php
render_site_footer('.', false);
?>
