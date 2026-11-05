<?php
declare(strict_types=1);

/**
 * Firebase Cloud Migration & Sync Utility View.
 *
 * File: firebase_sync.php
 * Mutation Log: 2026-11-05 - Built interactive one-click Firebase sync runner using client SDK.
 */

require_once __DIR__ . '/shared/utils/session_init.php';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/footer.php';

$json_path = __DIR__ . '/database/firebase_data.json';
$raw_json = file_exists($json_path) ? file_get_contents($json_path) : '{}';

render_site_header('นำเข้าข้อมูลสู่ Firebase Cloud (Firebase Sync Tool)', '.');
?>

<div style="max-width: 900px; margin: 0 auto; padding-bottom: 3rem;">
    <!-- Header -->
    <div style="background-color: var(--bg-card); border: 2px solid var(--gold-accent); border-radius: var(--radius-lg); padding: 1.75rem; margin-bottom: 2rem; box-shadow: var(--shadow-gold);">
        <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.5rem;">
            <span style="font-size: 2.2rem;">🔥</span>
            <div>
                <h1 style="color: var(--gold-accent); font-size: 1.6rem; margin: 0;">
                    Firebase Database Sync & Migration Tool
                </h1>
                <span style="font-size: 0.85rem; color: var(--gold-bright);">
                    Project ID: <code>amulet-c509e</code>
                </span>
            </div>
        </div>
        <p style="color: var(--text-light); opacity: 0.9; font-size: 0.95rem; margin-top: 0.5rem;">
            เครื่องมือซิงค์ข้อมูลจากฐานข้อมูลของระบบ (พระเครื่อง, ใบเซอร์ PSA, หมวดหมู่, ประวัติราคาตลาด) ขึ้นสู่ Firebase Cloud Firestore แบบ 1-Click อัตโนมัติ
        </p>

        <div style="display: flex; gap: 0.75rem; margin-top: 1.25rem; flex-wrap: wrap;">
            <button id="btn_sync_firestore" class="btn btn_gold" style="padding: 0.75rem 1.5rem; font-weight: 700; font-size: 1rem;">
                🚀 เริ่มซิงค์ข้อมูลขึ้น Cloud Firestore
            </button>
            <a href="database/firebase_data.json" download="firebase_data.json" class="btn btn_outline" style="padding: 0.75rem 1.25rem; font-size: 0.95rem;">
                📥 ดาวน์โหลดไฟล์ JSON สำรอง
            </a>
        </div>
    </div>

    <!-- Status & Progress Console -->
    <div style="background: #111; border: 1px solid var(--gold-border); border-radius: var(--radius-lg); padding: 1.25rem;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem;">
            <span style="font-size: 0.85rem; color: var(--gold-bright); font-weight: 700; text-transform: uppercase;">
                📋 Sync Execution Log
            </span>
            <span id="sync_status_badge" style="font-size: 0.75rem; background: rgba(233,215,147,0.2); color: var(--gold-accent); border: 1px solid var(--gold-muted); padding: 0.2rem 0.6rem; border-radius: 999px;">
                พร้อมทำงาน
            </span>
        </div>

        <div id="sync_log_output" style="height: 280px; overflow-y: auto; background: #000; border: 1px solid rgba(255,255,255,0.1); border-radius: 6px; padding: 1rem; font-family: monospace; font-size: 0.85rem; color: #a0ffa0; line-height: 1.6;">
            [READY] ระบบเตรียมข้อมูล JSON ทั้งหมด 8 ตารางหลักเรียบร้อยแล้ว<br>
            กดปุ่ม "เริ่มซิงค์ข้อมูลขึ้น Cloud Firestore" ด้านบน เพื่อส่งข้อมูลขึ้น Firebase
        </div>
    </div>
</div>

<!-- Hidden payload from PHP exported JSON -->
<script id="firebase_payload" type="application/json">
<?php echo $raw_json; ?>
</script>

<script type="module">
import { db } from "./js/firebase_config.js";
import { doc, setDoc } from "https://www.gstatic.com/firebasejs/10.8.0/firebase-firestore.js";

const btnSync = document.getElementById('btn_sync_firestore');
const logEl = document.getElementById('sync_log_output');
const badgeEl = document.getElementById('sync_status_badge');
const payloadEl = document.getElementById('firebase_payload');

function appendLog(msg, color = '#a0ffa0') {
    const time = new Date().toLocaleTimeString();
    logEl.innerHTML += `<div style="color: ${color};">[${time}] ${msg}</div>`;
    logEl.scrollTop = logEl.scrollHeight;
}

btnSync.addEventListener('click', async () => {
    btnSync.disabled = true;
    btnSync.textContent = '⏳ กำลังซิงค์ข้อมูลขึ้น Firebase...';
    badgeEl.textContent = 'กำลังประมวลผล';
    badgeEl.style.color = '#faad14';
    badgeEl.style.borderColor = '#faad14';

    let data = {};
    try {
        data = JSON.parse(payloadEl.textContent);
    } catch (e) {
        appendLog('❌ แปลงข้อมูล JSON ไม่สำเร็จ: ' + e.message, '#ff4d4f');
        btnSync.disabled = false;
        return;
    }

    const collections = Object.keys(data);
    appendLog(`🔥 เริ่มต้นซิงค์ทั้งหมด ${collections.length} Collections เข้าโปรเจกต์ amulet-c509e...`, '#ffd666');

    let totalDocs = 0;
    let successCount = 0;

    for (const colName of collections) {
        const records = data[colName];
        const docKeys = Object.keys(records);
        appendLog(`📂 กำลังนำเข้า Collection [${colName}] ทั้งหมด ${docKeys.length} รายการ...`);

        for (const docId of docKeys) {
            totalDocs++;
            const itemData = records[docId];
            try {
                // Write each record as document into Firestore
                await setDoc(doc(db, colName, String(docId)), itemData);
                successCount++;
                appendLog(`  ✓ [${colName}] ID #${docId} บันทึกสำเร็จ`);
            } catch (err) {
                appendLog(`  ⚠️ ไม่สามารถบันทึก [${colName}] ID #${docId}: ${err.message}`, '#ff7875');
            }
        }
    }

    appendLog(`🎉 ซิงค์เสร็จสิ้น! บันทึกสำเร็จ ${successCount} จาก ${totalDocs} รายการ ขึ้น Cloud Firestore เรียบร้อย`, '#73d13d');
    badgeEl.textContent = 'เสร็จสมบูรณ์';
    badgeEl.style.color = '#73d13d';
    badgeEl.style.borderColor = '#52c41a';
    btnSync.disabled = false;
    btnSync.textContent = '✓ ซิงค์ข้อมูลเรียบร้อยแล้ว';
});
</script>

<?php
render_site_footer('.', false);
?>
