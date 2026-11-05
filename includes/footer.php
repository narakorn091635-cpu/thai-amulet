<?php
declare(strict_types=1);

/**
 * Shared Site Footer Template.
 *
 * File: includes/footer.php
 * Mutation Log: 2026-11-04 - Created footer template loading main.js and auction.js via relative paths.
 */

require_once __DIR__ . '/../apps/config/app_config.php';

if (!function_exists('render_site_footer')) {
    /**
     * Renders the closing `<main>`, `<footer>`, and Vanilla JS script tags.
     *
     * @param string $relative_root   Relative path prefix to project root (e.g., '.' or '..').
     * @param bool   $include_auction Whether to include `js/auction.js` on the current page.
     * @return void
     */
    function render_site_footer(string $relative_root = '.', bool $include_auction = false): void
    {
        log_event('render_site_footer', 'START', [
            'relative_root' => $relative_root,
            'include_auction' => $include_auction,
        ]);

        $app_config = get_app_config('app');
        $site_name = (string)$app_config['name'];
        $current_year = (int)date('Y');
        ?>
</main>
<footer class="site_footer">
    <div class="container">
        <p>&copy; <?php echo $current_year; ?> <?php echo htmlspecialchars($site_name, ENT_QUOTES, 'UTF-8'); ?> — ศูนย์รวมพระเครื่องยอดนิยมและประมูลพระเครื่องมาตรฐานสากล</p>
    </div>
</footer>
<script src="<?php echo htmlspecialchars($relative_root, ENT_QUOTES, 'UTF-8'); ?>/js/main.js"></script>
<?php if ($include_auction): ?>
<script src="<?php echo htmlspecialchars($relative_root, ENT_QUOTES, 'UTF-8'); ?>/js/auction.js"></script>
<?php endif; ?>
</body>
</html>
        <?php
        log_event('render_site_footer', 'FINISH', ['status' => 'rendered']);
    }
}

