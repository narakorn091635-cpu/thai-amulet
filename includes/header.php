<?php
declare(strict_types=1);

/**
 * Shared Site Header Template.
 *
 * File: includes/header.php
 * Mutation Log: 2026-11-04 - Standardized centralized session initialization.
 */

require_once __DIR__ . '/../shared/utils/session_init.php';
require_once __DIR__ . '/../apps/config/app_config.php';
require_once __DIR__ . '/../shared/services/auth_guard.php';
require_once __DIR__ . '/footer.php';

if (!function_exists('render_site_header')) {
    /**
     * Renders the HTML5 document head and top navigation bar.
     *
     * @param string $page_title    Title of the current page to display in `<title>`.
     * @param string $relative_root Relative path prefix to project root (e.g., '.' or '..').
     * @return void
     */
    function render_site_header(string $page_title = '', string $relative_root = '.'): void
    {
        log_event('render_site_header', 'START', [
            'page_title' => $page_title,
            'relative_root' => $relative_root,
        ]);

        $app_config = get_app_config('app');
        $site_name = (string)$app_config['name'];
        $full_title = $page_title !== '' ? "{$page_title} | {$site_name}" : $site_name;
        $correlation_id = get_correlation_id();
        $cart_count = isset($_SESSION['cart']) && is_array($_SESSION['cart'])
            ? array_sum($_SESSION['cart'])
            : 0;
        $is_user_logged = is_logged_in();
        $is_user_admin = is_admin();
        $user_id = (int)($_SESSION['user_id'] ?? 0);

        // Fetch notifications & unread count
        require_once __DIR__ . '/../shared/services/notification_service.php';
        $unread_notifs = $user_id > 0 ? get_unread_notification_count($user_id) : 0;
        $user_notifs = $user_id > 0 ? get_user_notifications($user_id, 5) : [];

        ?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="x-correlation-id" content="<?php echo htmlspecialchars($correlation_id, ENT_QUOTES, 'UTF-8'); ?>">
    <title><?php echo htmlspecialchars($full_title, ENT_QUOTES, 'UTF-8'); ?></title>
    <!-- Google Fonts: Prompt & Cinzel for Luxury Thai Amulet Aesthetics -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@600;700;800&family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($relative_root, ENT_QUOTES, 'UTF-8'); ?>/css/style.css">
</head>
<body data-relative-root="<?php echo htmlspecialchars($relative_root, ENT_QUOTES, 'UTF-8'); ?>">
<header class="site_header">
    <div class="container navbar">
        <a href="<?php echo htmlspecialchars($relative_root, ENT_QUOTES, 'UTF-8'); ?>/index.php" class="brand_logo">
            <span class="brand_emblem">⚜️</span>
            <div class="brand_text_wrap">
                <span class="brand_name"><?php echo htmlspecialchars($site_name, ENT_QUOTES, 'UTF-8'); ?></span>
                <span class="brand_tagline">SIAM SACRED AMULET & LIVE AUCTION</span>
            </div>
        </a>
        <nav aria-label="Main Navigation">
            <ul class="nav_links">
                <li><a href="<?php echo htmlspecialchars($relative_root, ENT_QUOTES, 'UTF-8'); ?>/index.php">ตลาดเช่าพระ</a></li>
                <li><a href="<?php echo htmlspecialchars($relative_root, ENT_QUOTES, 'UTF-8'); ?>/auctions.php">ประมูลพระ</a></li>
                <li><a href="<?php echo htmlspecialchars($relative_root, ENT_QUOTES, 'UTF-8'); ?>/leaderboard.php" style="color: var(--gold-bright);">🏆 ทำเนียบพระแชมป์</a></li>
                <li><a href="<?php echo htmlspecialchars($relative_root, ENT_QUOTES, 'UTF-8'); ?>/verify_cert.php">🛡️ ตรวจใบเซอร์</a></li>
                <?php if ($is_user_logged): ?>
                    <li><a href="<?php echo htmlspecialchars($relative_root, ENT_QUOTES, 'UTF-8'); ?>/collection_showcase.php" style="color: #73d13d;">🏺 กรุพระของฉัน</a></li>
                    <li><a href="<?php echo htmlspecialchars($relative_root, ENT_QUOTES, 'UTF-8'); ?>/wishlist.php" style="color: #ffd666;">❤️ พระที่ถูกใจ</a></li>
                    <!-- Notification Bell Dropdown -->
                    <li style="position: relative;">
                        <a href="javascript:void(0);" onclick="const d = document.getElementById('notif_dropdown'); d.style.display = d.style.display === 'block' ? 'none' : 'block';" style="position: relative; display: flex; align-items: center; gap: 0.25rem;">
                            🔔
                            <?php if ($unread_notifs > 0): ?>
                                <span style="background: #ff4d4f; color: #fff; font-size: 0.7rem; font-weight: bold; border-radius: 999px; padding: 0.1rem 0.45rem; line-height: 1;">
                                    <?php echo $unread_notifs; ?>
                                </span>
                            <?php endif; ?>
                        </a>
                        <div id="notif_dropdown" style="display: none; position: absolute; right: 0; top: 130%; width: 320px; background: #2a0006; border: 1.5px solid var(--gold-accent); border-radius: 8px; box-shadow: var(--shadow-gold); z-index: 9999; padding: 0.75rem;">
                            <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--gold-muted); padding-bottom: 0.5rem; margin-bottom: 0.5rem;">
                                <strong style="color: var(--gold-accent); font-size: 0.9rem;">การแจ้งเตือน (Notifications)</strong>
                                <span style="font-size: 0.75rem; color: var(--text-light); opacity: 0.7;">ล่าสุด</span>
                            </div>
                            <?php if (empty($user_notifs)): ?>
                                <p style="font-size: 0.8rem; color: var(--text-light); opacity: 0.7; text-align: center; margin: 1rem 0;">ไม่มีการแจ้งเตือนใหม่</p>
                            <?php else: ?>
                                <div style="display: flex; flex-direction: column; gap: 0.5rem; max-height: 240px; overflow-y: auto;">
                                    <?php foreach ($user_notifs as $nt): ?>
                                        <a href="<?php echo htmlspecialchars((string)($nt['link_url'] ?? '#'), ENT_QUOTES, 'UTF-8'); ?>" style="display: block; padding: 0.5rem; background: rgba(0,0,0,0.3); border-radius: 4px; text-decoration: none; border-left: 3px solid <?php echo $nt['is_read'] ? 'var(--gold-muted)' : '#ff4d4f'; ?>;">
                                            <div style="font-size: 0.82rem; font-weight: bold; color: var(--gold-bright);"><?php echo htmlspecialchars((string)$nt['title'], ENT_QUOTES, 'UTF-8'); ?></div>
                                            <div style="font-size: 0.75rem; color: var(--text-light); opacity: 0.85; margin-top: 0.15rem;"><?php echo htmlspecialchars((string)$nt['message'], ENT_QUOTES, 'UTF-8'); ?></div>
                                            <div style="font-size: 0.65rem; color: var(--gold-accent); margin-top: 0.25rem;"><?php echo date('d/m H:i', strtotime((string)$nt['created_at'])); ?></div>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </li>
                <?php endif; ?>
                <li>
                    <a href="<?php echo htmlspecialchars($relative_root, ENT_QUOTES, 'UTF-8'); ?>/cart.php">
                        ตะกร้าเช่าบูชา (<span id="nav_cart_count"><?php echo (int)$cart_count; ?></span>)
                    </a>
                </li>
                <?php if ($is_user_admin): ?>
                    <li><a href="<?php echo htmlspecialchars($relative_root, ENT_QUOTES, 'UTF-8'); ?>/admin/index.php" style="color: #faad14;">★ ระบบหลังบ้าน (Admin CMS)</a></li>
                <?php endif; ?>
                <?php if ($is_user_logged): ?>
                    <li><a href="<?php echo htmlspecialchars($relative_root, ENT_QUOTES, 'UTF-8'); ?>/orders.php">ประวัติการเช่า</a></li>
                    <li><a href="<?php echo htmlspecialchars($relative_root, ENT_QUOTES, 'UTF-8'); ?>/logout.php" class="btn btn_outline">ออกจากระบบ (<?php echo htmlspecialchars($_SESSION['username'] ?? '', ENT_QUOTES, 'UTF-8'); ?>)</a></li>
                <?php else: ?>
                    <li><a href="<?php echo htmlspecialchars($relative_root, ENT_QUOTES, 'UTF-8'); ?>/login.php" class="btn btn_outline">เข้าสู่ระบบ</a></li>
                    <li><a href="<?php echo htmlspecialchars($relative_root, ENT_QUOTES, 'UTF-8'); ?>/register.php" class="btn btn_gold">สมัครสมาชิก</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
</header>
<main class="main_content container">
        <?php
        log_event('render_site_header', 'FINISH', ['full_title' => $full_title]);
    }
}
