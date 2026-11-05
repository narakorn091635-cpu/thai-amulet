<?php
declare(strict_types=1);

/**
 * User Registration Page.
 *
 * File: register.php
 * Mutation Log: 2026-11-04 - Created standalone register.php with validation, password_hash, and session setup.
 */

require_once __DIR__ . '/shared/utils/session_init.php';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/shared/services/auth_service.php';
require_once __DIR__ . '/shared/services/auth_guard.php';

// Redirect if already logged in
if (is_logged_in()) {
    header('Location: index.php');
    exit;
}

$error_msg = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim((string)($_POST['username'] ?? ''));
    $email = trim((string)($_POST['email'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    $password_confirm = (string)($_POST['password_confirm'] ?? '');
    $fullname = trim((string)($_POST['fullname'] ?? ''));
    $phone = trim((string)($_POST['phone'] ?? ''));
    $address = trim((string)($_POST['address'] ?? ''));

    // Validation
    if ($username === '' || $email === '' || $password === '' || $fullname === '') {
        $error_msg = 'กรุณากรอกข้อมูลที่จำเป็นให้ครบถ้วน';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_msg = 'รูปแบบอีเมลไม่ถูกต้อง';
    } elseif (strlen($password) < 6) {
        $error_msg = 'รหัสผ่านต้องมีความยาวอย่างน้อย 6 ตัวอักษร';
    } elseif ($password !== $password_confirm) {
        $error_msg = 'รหัสผ่านยืนยันไม่ตรงกับรหัสผ่านที่ตั้งไว้';
    } else {
        try {
            $new_user_id = register_user($username, $email, $password, $fullname, $phone, $address);

            // Auto-login or set flash and redirect to login
            $_SESSION['user_id'] = $new_user_id;
            $_SESSION['username'] = $username;
            $_SESSION['full_name'] = $fullname;
            $_SESSION['role'] = 'user';
            $_SESSION['user_role'] = 'user';

            header('Location: index.php');
            exit;
        } catch (Exception $e) {
            $error_msg = $e->getMessage();
        }
    }
}

render_site_header('สมัครสมาชิกใหม่ (Register)', '.');
?>

<div style="max-width: 560px; margin: 2rem auto; background-color: var(--bg-card); border: 1px solid var(--gold-border); border-radius: var(--radius-lg); padding: 2rem; box-shadow: var(--shadow-gold);">
    <div style="text-align: center; margin-bottom: 1.5rem;">
        <span style="font-size: 3.5rem;">⚜️</span>
        <h2 style="color: var(--gold-accent); margin-top: 0.5rem;">สมัครสมาชิกใหม่</h2>
        <p style="font-size: 0.9rem; color: var(--text-light); opacity: 0.85;">สร้างบัญชีเพื่อเข้าร่วมประมูลและเช่าพระเครื่อง</p>
    </div>

    <?php if ($error_msg): ?>
        <div style="background-color: rgba(255, 77, 79, 0.2); border: 1px solid var(--danger-color); color: #ff7875; padding: 0.75rem 1rem; border-radius: var(--radius-md); margin-bottom: 1.5rem; font-size: 0.95rem;">
            ⚠️ <?php echo htmlspecialchars($error_msg, ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="register.php">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1rem; margin-bottom: 1rem;">
            <div>
                <label for="username" style="display: block; font-size: 0.9rem; color: var(--gold-accent); margin-bottom: 0.35rem;">ชื่อผู้ใช้ (Username) *</label>
                <input type="text" id="username" name="username" required class="form_control" style="width: 100%;"
                       placeholder="เช่น somchai99" value="<?php echo htmlspecialchars($_POST['username'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
            </div>

            <div>
                <label for="email" style="display: block; font-size: 0.9rem; color: var(--gold-accent); margin-bottom: 0.35rem;">อีเมล (Email) *</label>
                <input type="email" id="email" name="email" required class="form_control" style="width: 100%;"
                       placeholder="example@mail.com" value="<?php echo htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
            </div>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1rem; margin-bottom: 1rem;">
            <div>
                <label for="password" style="display: block; font-size: 0.9rem; color: var(--gold-accent); margin-bottom: 0.35rem;">รหัสผ่าน (อย่างน้อย 6 ตัว) *</label>
                <input type="password" id="password" name="password" required minlength="6" class="form_control" style="width: 100%;" placeholder="••••••••">
            </div>

            <div>
                <label for="password_confirm" style="display: block; font-size: 0.9rem; color: var(--gold-accent); margin-bottom: 0.35rem;">ยืนยันรหัสผ่านอีกครั้ง *</label>
                <input type="password" id="password_confirm" name="password_confirm" required minlength="6" class="form_control" style="width: 100%;" placeholder="••••••••">
            </div>
        </div>

        <div style="margin-bottom: 1rem;">
            <label for="fullname" style="display: block; font-size: 0.9rem; color: var(--gold-accent); margin-bottom: 0.35rem;">ชื่อ - นามสกุลจริง *</label>
            <input type="text" id="fullname" name="fullname" required class="form_control" style="width: 100%;"
                   placeholder="เช่น นายสมชาย สายบุญ" value="<?php echo htmlspecialchars($_POST['fullname'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
        </div>

        <div style="margin-bottom: 1rem;">
            <label for="phone" style="display: block; font-size: 0.9rem; color: var(--gold-accent); margin-bottom: 0.35rem;">เบอร์โทรศัพท์ติดต่อ</label>
            <input type="tel" id="phone" name="phone" class="form_control" style="width: 100%;"
                   placeholder="08X-XXX-XXXX" value="<?php echo htmlspecialchars($_POST['phone'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
        </div>

        <div style="margin-bottom: 1.5rem;">
            <label for="address" style="display: block; font-size: 0.9rem; color: var(--gold-accent); margin-bottom: 0.35rem;">ที่อยู่จัดส่งพัสดุ</label>
            <textarea id="address" name="address" rows="3" class="form_control" style="width: 100%;"
                      placeholder="ระบุบ้านเลขที่ ซอย ถนน ตำบล อำเภอ จังหวัด รหัสไปรษณีย์"><?php echo htmlspecialchars($_POST['address'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
        </div>

        <button type="submit" class="btn btn_gold" style="width: 100%; padding: 0.85rem; font-size: 1.05rem;">
            ยืนยันสมัครสมาชิก &rarr;
        </button>
    </form>

    <div style="margin-top: 1.5rem; padding-top: 1.25rem; border-top: 1px solid var(--gold-muted); text-align: center;">
        <p style="color: var(--text-light); font-size: 0.95rem;">
            มีบัญชีสมาชิกอยู่แล้ว? <a href="login.php" style="color: var(--gold-accent); font-weight: 700; text-decoration: underline;">เข้าสู่ระบบที่นี่</a>
        </p>
    </div>
</div>

<?php
render_site_footer('.', false);
?>

