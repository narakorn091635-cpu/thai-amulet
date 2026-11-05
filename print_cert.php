<?php
declare(strict_types=1);

/**
 * Printable Official PSA-Style Certificate Document (High Quality Print & Frame).
 *
 * File: print_cert.php
 * Mutation Log: 2026-11-05 - Built print-ready physical certificate layout with security guilloche border and QR seal.
 */

require_once __DIR__ . '/shared/utils/session_init.php';
require_once __DIR__ . '/shared/services/cert_service.php';

$cert_no = trim((string)($_GET['cert_no'] ?? ''));
$cert = get_certificate_by_cert_number($cert_no);

if (!$cert) {
    die("ไม่พบข้อมูลใบรับรองหมายเลขนี้");
}

$qr_url = generate_cert_qr_url((string)$cert['cert_number'], 160);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>ใบรับรองพระแท้มาตรฐานสากล - <?php echo htmlspecialchars($cert['cert_number']); ?></title>
    <style>
        @page {
            size: A4 portrait;
            margin: 1.5cm;
        }
        body {
            font-family: 'Sarabun', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            margin: 0;
            padding: 0;
            background: #fff;
            color: #222;
        }
        .cert-container {
            border: 8px double #B38F4D;
            padding: 30px;
            background: #fffcf5;
            position: relative;
            box-sizing: border-box;
            min-height: 95vh;
        }
        .cert-watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 15rem;
            color: rgba(179, 143, 77, 0.04);
            pointer-events: none;
            user-select: none;
        }
        .header-title {
            text-align: center;
            border-bottom: 2px solid #B38F4D;
            padding-bottom: 15px;
            margin-bottom: 25px;
        }
        .grade-badge {
            display: inline-block;
            background: #111;
            color: #F5E7B2;
            padding: 8px 24px;
            border-radius: 6px;
            font-size: 1.5rem;
            font-weight: bold;
            border: 2px solid #B38F4D;
        }
        .details-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        .details-table td {
            padding: 10px;
            border-bottom: 1px dotted #ccc;
            font-size: 1.05rem;
        }
        .no-print {
            text-align: center;
            padding: 15px;
            background: #eee;
        }
        @media print {
            .no-print { display: none; }
            .cert-container { border: 8px double #B38F4D !important; }
        }
    </style>
</head>
<body>

<div class="no-print">
    <button onclick="window.print();" style="padding: 10px 25px; font-size: 1rem; font-weight: bold; background: #B38F4D; color: #fff; border: none; border-radius: 4px; cursor: pointer;">
        🖨️ สั่งพิมพ์ใบรับรองพระแท้ (Print Official Certificate)
    </button>
</div>

<div class="cert-container">
    <div class="cert-watermark">⚜️</div>

    <div class="header-title">
        <div style="font-size: 2.2rem; color: #B38F4D; font-weight: bold; letter-spacing: 1px;">
            ⚜️ สยามพุทธคุณ มาตรฐานพระแท้สากล ⚜️
        </div>
        <div style="font-size: 1.1rem; color: #666; margin-top: 5px;">
            SIAM AMULET AUTHENTICATION & CERTIFIED GRADING SLAB
        </div>
        <div style="margin-top: 15px;">
            <span class="grade-badge">
                <?php echo htmlspecialchars($cert['grade_label']); ?> (SCORE: <?php echo number_format((float)$cert['grade_score'], 1); ?>/10)
            </span>
        </div>
    </div>

    <div style="display: flex; gap: 30px; align-items: center; margin-bottom: 20px;">
        <div style="width: 200px; height: 250px; border: 2px solid #B38F4D; padding: 4px; background: #fff; text-align: center;">
            <img src="<?php echo htmlspecialchars($cert['amulet_image_path']); ?>" alt="Amulet" style="width: 100%; height: 100%; object-fit: contain;">
        </div>
        <div style="flex: 1;">
            <table class="details-table">
                <tr>
                    <td style="width: 35%; font-weight: bold; color: #775317;">เลขที่ใบรับรอง (Serial No.):</td>
                    <td style="font-weight: bold; font-size: 1.2rem;"><?php echo htmlspecialchars($cert['cert_number']); ?></td>
                </tr>
                <tr>
                    <td style="font-weight: bold; color: #775317;">ชื่อพระเครื่อง:</td>
                    <td style="font-weight: bold;"><?php echo htmlspecialchars($cert['amulet_name']); ?></td>
                </tr>
                <tr>
                    <td style="font-weight: bold; color: #775317;">วัดที่สร้าง:</td>
                    <td><?php echo htmlspecialchars($cert['temple_name']); ?></td>
                </tr>
                <tr>
                    <td style="font-weight: bold; color: #775317;">เกจิอาจารย์:</td>
                    <td><?php echo htmlspecialchars($cert['monk_name']); ?></td>
                </tr>
                <tr>
                    <td style="font-weight: bold; color: #775317;">ผู้ครอบครองปัจจุบัน (Provenance):</td>
                    <td style="font-weight: bold; color: #2e7d32; font-size: 1.1rem;">👤 <?php echo htmlspecialchars($cert['current_owner_name']); ?></td>
                </tr>
                <tr>
                    <td style="font-weight: bold; color: #775317;">วันที่ออกใบเซอร์:</td>
                    <td><?php echo date('d/m/Y', strtotime((string)$cert['issue_date'])); ?></td>
                </tr>
            </table>
        </div>
    </div>

    <!-- QR Code & Seals -->
    <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-top: 40px; border-top: 2px solid #B38F4D; padding-top: 20px;">
        <div style="display: flex; gap: 15px; align-items: center;">
            <img src="<?php echo htmlspecialchars($qr_url); ?>" alt="QR Verification" style="width: 100px; height: 100px; border: 1px solid #ccc; padding: 3px;">
            <div style="font-size: 0.85rem; color: #555;">
                <strong>สแกนเพื่อตรวจสอบของแท้:</strong><br>
                ลิงก์สู่ฐานข้อมูลออนไลน์ป้องกันการสวมรอย<br>
                Security Hash: <code><?php echo substr($cert['security_hash'], 0, 16); ?>...</code>
            </div>
        </div>

        <div style="text-align: center;">
            <div style="border-bottom: 1px solid #222; width: 220px; margin-bottom: 5px;"></div>
            <div style="font-size: 0.95rem; font-weight: bold;"><?php echo htmlspecialchars($cert['inspector_name']); ?></div>
            <div style="font-size: 0.85rem; color: #666;">ประธานคณะกรรมการผู้ทรงคุณวุฒิ</div>
        </div>
    </div>
</div>

</body>
</html>
