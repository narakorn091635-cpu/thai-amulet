<?php
declare(strict_types=1);

/**
 * User Login Page.
 *
 * File: login.php
 * Mutation Log: 2026-11-04 - Refactored login page to use password_verify, set $_SESSION['role'], and link to register.php.
 */

require_once __DIR__ . '/shared/utils/session_init.php';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/shared/services/auth_service.php';
require_once __DIR__ . '/shared/services/auth_guard.php';

// If already logged in, redirect to dashboard or home
if (is_logged_in()) {
    if (is_admin()) {
        header('Location: admin/index.php');
    } else {
        header('Location: index.php');
    }
    exit;
}

$error_msg = null;
$success_msg = $_SESSION['flash_success'] ?? null;
unset($_SESSION['flash_success']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identity = trim((string)($_POST['identity'] ?? ''));
    $password = (string)($_POST['password'] ?? '');

    if ($identity === '' || $password === '') {
        $error_msg = 'กรุณากรอกชื่อผู้ใช้/อีเมล และรหัสผ่าน';
    } else {
        $user = authenticate_user($identity, $password);
        if ($user) {
            // Set user data in $_SESSION as specified in Phase 2
            $_SESSION['user_id'] = (int)$user['id'];
            $_SESSION['username'] = (string)$user['username'];
            $_SESSION['full_name'] = (string)$user['full_name'];
            $_SESSION['role'] = (string)$user['role']; // Explicitly store 'admin' or 'user'
            $_SESSION['user_role'] = (string)$user['role'];

            $redirect = $_SESSION['redirect_after_login'] ?? ($user['role'] === 'admin' ? 'admin/index.php' : 'index.php');
            unset($_SESSION['redirect_after_login']);
            header("Location: {$redirect}");
            exit;
        } else {
            $error_msg = 'ชื่อผู้ใช้/อีเมล หรือรหัสผ่านไม่ถูกต้อง';
        }
    }
}

render_site_header('เข้าสู่ระบบสมาชิก (Member Login)', '.');
?>

<div style="max-width: 480px; margin: 2.5rem auto; background-color: var(--bg-card); border: 1px solid var(--gold-border); border-radius: var(--radius-lg); padding: 2rem; box-shadow: var(--shadow-gold);">
    <div style="text-align: center; margin-bottom: 1.5rem;">
        <span style="font-size: 3.5rem;">⚜️</span>
        <h2 style="color: var(--gold-accent); margin-top: 0.5rem;">เข้าสู่ระบบสมาชิก</h2>
        <p style="font-size: 0.9rem; color: var(--text-light); opacity: 0.85;">ตลาดพระเครื่องแท้และระบบประมูลออนไลน์</p>
    </div>

    <?php if ($success_msg): ?>
        <div style="background-color: rgba(82, 196, 26, 0.2); border: 1px solid var(--success-color); color: #73d13d; padding: 0.75rem 1rem; border-radius: var(--radius-md); margin-bottom: 1.5rem; font-size: 0.95rem;">
            ✓ <?php echo htmlspecialchars($success_msg, ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>

    <?php if ($error_msg): ?>
        <div style="background-color: rgba(255, 77, 79, 0.2); border: 1px solid var(--danger-color); color: #ff7875; padding: 0.75rem 1rem; border-radius: var(--radius-md); margin-bottom: 1.5rem; font-size: 0.95rem;">
            ⚠️ <?php echo htmlspecialchars($error_msg, ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="login.php">
        <div style="margin-bottom: 1.25rem;">
            <label for="identity" style="display: block; font-size: 0.9rem; color: var(--gold-accent); margin-bottom: 0.35rem;">ชื่อผู้ใช้ หรือ อีเมล</label>
            <input type="text" id="identity" name="identity" required class="form_control" style="width: 100%;"
                   placeholder="เช่น admin หรือ user1" value="<?php echo htmlspecialchars($_POST['identity'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
        </div>

        <div style="margin-bottom: 1.5rem;">
            <label for="password" style="display: block; font-size: 0.9rem; color: var(--gold-accent); margin-bottom: 0.35rem;">รหัสผ่าน</label>
            <input type="password" id="password" name="password" required class="form_control" style="width: 100%;" placeholder="••••••••">
        </div>

        <button type="submit" class="btn btn_gold" style="width: 100%; padding: 0.85rem; font-size: 1.05rem;">
            เข้าสู่ระบบ &rarr;
        </button>
    </form>

    <div style="margin-top: 1.5rem; padding-top: 1.25rem; border-top: 1px solid var(--gold-muted); text-align: center;">
        <p style="color: var(--text-light); font-size: 0.95rem;">
            ยังไม่มีบัญชีสมาชิก? <a href="register.php" style="color: var(--gold-accent); font-weight: 700; text-decoration: underline;">สมัครสมาชิกที่นี่</a>
        </p>
        <div style="margin-top: 1rem; font-size: 0.85rem; color: var(--gold-muted); background: rgba(0,0,0,0.25); padding: 0.5rem; border-radius: 4px;">
            บัญชีทดสอบระบบ: <strong>admin</strong> / <strong>admin1234</strong> หรือ <strong>user1</strong> / <strong>user1234</strong>
        </div>
    </div>
</div>

<?php
render_site_footer('.', false);
?>
