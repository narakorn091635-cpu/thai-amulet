<?php
declare(strict_types=1);

/**
 * Shopping Cart & Quantity Management View.
 *
 * File: cart.php
 * Mutation Log: 2026-11-04 - Built Shopping Cart for Module 1.
 */

require_once __DIR__ . '/shared/utils/session_init.php';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/footer.php';
require_once __DIR__ . '/shared/services/amulet_service.php';

if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Handle Cart Actions: Add, Update, Remove
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $amulet_id = isset($_POST['amulet_id']) ? (int)$_POST['amulet_id'] : 0;

    if ($action === 'add' && $amulet_id > 0) {
        $amulet = get_amulet_by_id($amulet_id);
        if ($amulet && (int)$amulet['stock_qty'] > 0) {
            $_SESSION['cart'][$amulet_id] = ($_SESSION['cart'][$amulet_id] ?? 0) + 1;
        }
        header('Location: cart.php');
        exit;
    }

    if ($action === 'remove' && $amulet_id > 0) {
        unset($_SESSION['cart'][$amulet_id]);
        header('Location: cart.php');
        exit;
    }

    if ($action === 'update' && $amulet_id > 0) {
        $qty = max(1, (int)($_POST['quantity'] ?? 1));
        $amulet = get_amulet_by_id($amulet_id);
        if ($amulet) {
            $_SESSION['cart'][$amulet_id] = min($qty, (int)$amulet['stock_qty']);
        }
        header('Location: cart.php');
        exit;
    }
}

// Fetch Cart Item Details
$cart_items = [];
$grand_total = 0.0;

foreach ($_SESSION['cart'] as $id => $quantity) {
    $item = get_amulet_by_id((int)$id);
    if ($item) {
        $subtotal = (float)$item['price'] * (int)$quantity;
        $grand_total += $subtotal;
        $cart_items[] = [
            'amulet' => $item,
            'quantity' => (int)$quantity,
            'subtotal' => $subtotal,
        ];
    }
}

render_site_header('ตะกร้าเช่าบูชาพระเครื่อง', '.');
?>

<div style="margin-bottom: 2rem;">
    <h1 style="color: var(--gold-accent);">ตะกร้าเช่าบูชาพระเครื่อง</h1>
    <p style="color: var(--text-light); opacity: 0.9;">ตรวจสอบรายการพระเครื่องก่อนดำเนินการแจ้งชำระเงิน</p>
</div>

<?php if (empty($cart_items)): ?>
    <div style="text-align: center; padding: 4rem 1rem; background-color: var(--bg-card); border: 1px dashed var(--gold-border); border-radius: var(--radius-lg);">
        <h3 style="color: var(--gold-accent); margin-bottom: 0.5rem;">ยังไม่มีรายการพระเครื่องในตะกร้า</h3>
        <p style="color: var(--text-light); margin-bottom: 1.5rem;">เลือกชมพระเครื่องพุทธคุณสูงในตลาดเช่าพระเพื่อเริ่มบูชา</p>
        <a href="index.php" class="btn btn_gold">ไปที่ตลาดเช่าพระ</a>
    </div>
<?php else: ?>
    <div style="background-color: var(--bg-card); border: 1px solid var(--gold-border); border-radius: var(--radius-lg); overflow: hidden; margin-bottom: 2rem;">
        <table class="data_table">
            <thead>
                <tr>
                    <th>รายการพระเครื่อง</th>
                    <th>ราคาต่อองค์</th>
                    <th style="width: 140px;">จำนวน</th>
                    <th>ยอดรวมย่อย</th>
                    <th>จัดการ</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($cart_items as $entry): ?>
                    <?php $amulet = $entry['amulet']; ?>
                    <tr>
                        <td style="display: flex; gap: 0.75rem; align-items: center;">
                            <?php if (!empty($amulet['image_path'])): ?>
                                <img src="<?php echo htmlspecialchars((string)$amulet['image_path'], ENT_QUOTES, 'UTF-8'); ?>"
                                     alt="Amulet" style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px; border: 1px solid var(--gold-border);"
                                     onerror="this.style.display='none';">
                            <?php endif; ?>
                            <div>
                                <a href="amulet_detail.php?id=<?php echo (int)$amulet['id']; ?>" style="font-weight: 700; color: var(--gold-accent);">
                                    <?php echo htmlspecialchars((string)$amulet['name'], ENT_QUOTES, 'UTF-8'); ?>
                                </a>
                                <div style="font-size: 0.85rem; color: var(--text-light); opacity: 0.85;">
                                    วัด: <?php echo htmlspecialchars((string)$amulet['temple_name'], ENT_QUOTES, 'UTF-8'); ?> |
                                    เกจิ: <?php echo htmlspecialchars((string)$amulet['monk_name'], ENT_QUOTES, 'UTF-8'); ?>
                                </div>
                            </div>
                        </td>
                        <td><?php echo number_format((float)$amulet['price'], 2); ?> ฿</td>
                        <td>
                            <form method="POST" action="cart.php" style="display: flex; gap: 0.35rem; align-items: center; margin: 0;">
                                <input type="hidden" name="action" value="update">
                                <input type="hidden" name="amulet_id" value="<?php echo (int)$amulet['id']; ?>">
                                <input type="number" name="quantity" min="1" max="<?php echo (int)$amulet['stock_qty']; ?>"
                                       value="<?php echo (int)$entry['quantity']; ?>" class="form_control" style="width: 65px; padding: 0.35rem 0.5rem; text-align: center;">
                                <button type="submit" class="btn btn_outline" style="padding: 0.35rem 0.6rem; font-size: 0.8rem;">ปรับ</button>
                            </form>
                        </td>
                        <td style="font-weight: 700; color: var(--gold-accent);">
                            <?php echo number_format((float)$entry['subtotal'], 2); ?> ฿
                        </td>
                        <td>
                            <form method="POST" action="cart.php" style="margin: 0;">
                                <input type="hidden" name="action" value="remove">
                                <input type="hidden" name="amulet_id" value="<?php echo (int)$amulet['id']; ?>">
                                <button type="submit" class="btn btn_outline" style="color: var(--danger-color); border-color: var(--danger-color); padding: 0.35rem 0.75rem; font-size: 0.8rem;">
                                    ลบออก
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1.5rem; background-color: var(--bg-card); border: 1px solid var(--gold-border); border-radius: var(--radius-lg); padding: 1.5rem;">
        <a href="index.php" class="btn btn_outline">&larr; เลือกชมพระเครื่องต่อ</a>

        <div style="display: flex; align-items: center; gap: 2rem;">
            <div>
                <span style="font-size: 0.9rem; color: var(--text-light); display: block;">ยอดชำระทั้งสิ้น</span>
                <span style="font-size: 1.8rem; font-weight: 700; color: var(--gold-accent);"><?php echo number_format($grand_total, 2); ?> ฿</span>
            </div>
            <a href="checkout.php" class="btn btn_gold" style="padding: 0.85rem 2rem; font-size: 1.1rem;">
                ดำเนินการแจ้งชำระเงิน (Checkout) &rarr;
            </a>
        </div>
    </div>
<?php endif; ?>

<?php
render_site_footer('.', false);
?>

