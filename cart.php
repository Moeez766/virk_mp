<?php
// cart.php
require_once 'includes/sessions.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

requireLogin();
$page_title = 'Cart';

// Handle remove from cart
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action     = $_POST['action']     ?? '';
    $cart_id    = (int)($_POST['cart_id'] ?? 0);
    $product_id = (int)($_POST['product_id'] ?? 0);

    if ($action === 'remove' && $cart_id) {
        $stmt = $conn->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
        $stmt->bind_param('ii', $cart_id, $_SESSION['user_id']);
        $stmt->execute();
        setFlash('info', 'Item removed from cart.');
    }

    if ($action === 'checkout') {
        header("Location: checkout.php");
        exit();
    }

    header("Location: cart.php");
    exit();
}

$items    = getCartItems($conn, $_SESSION['user_id']);
$subtotal = array_sum(array_column($items, 'price'));

require_once 'templates/header.php';
?>

<div class="page-banner">
    <div class="container">
        <h1>Your Cart</h1>
        <p><?php echo count($items); ?> item<?php echo count($items) != 1 ? 's' : ''; ?> in your cart</p>
    </div>
</div>

<div class="container">

    <?php if (empty($items)): ?>
        <div style="text-align:center;padding:80px 0;">
            <div style="font-size:4rem;margin-bottom:16px;">🛒</div>
            <h3>Your cart is empty</h3>
            <p class="text-muted" style="margin-top:8px;">Start browsing to add items to your cart.</p>
            <a href="products.php" class="btn btn-primary" style="margin-top:20px;">Browse All Items</a>
        </div>
    <?php else: ?>

        <div style="display:grid;grid-template-columns:1fr 340px;gap:32px;align-items:start;">

            <!-- Cart Items -->
            <div>
                <?php foreach ($items as $item):
                    $img = !empty($item['image']) ? 'assets/uploads/' . htmlspecialchars($item['image']) : null;
                    $placeholder_icons = ['rare'=>'💎','high_end'=>'🪑','regular'=>'🛍️'];
                    $icon = $placeholder_icons[$item['category']] ?? '📦';
                ?>
                <div class="cart-item">
                    <div class="cart-item-img">
                        <?php if ($img): ?>
                            <img src="<?php echo $img; ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                        <?php else: ?>
                            <div style="display:flex;align-items:center;justify-content:center;height:100%;font-size:2rem;"><?php echo $icon; ?></div>
                        <?php endif; ?>
                    </div>

                    <div>
                        <h4 style="margin-bottom:4px;">
                            <a href="product.php?id=<?php echo (int)$item['product_id']; ?>">
                                <?php echo htmlspecialchars($item['name']); ?>
                            </a>
                        </h4>
                        <div style="display:flex;gap:6px;margin-bottom:8px;">
                            <span class="badge badge-<?php echo str_replace('_','-',$item['category']); ?>">
                                <?php echo getCategoryLabel($item['category']); ?>
                            </span>
                            <span class="badge badge-<?php echo str_replace('_','-',$item['sale_type']); ?>">
                                <?php echo getSaleTypeLabel($item['sale_type']); ?>
                            </span>
                        </div>
                        <div class="product-price" style="font-size:1rem;"><?php echo formatPrice($item['price']); ?></div>
                    </div>

                    <div style="text-align:right;">
                        <form method="POST">
                            <input type="hidden" name="action"     value="remove">
                            <input type="hidden" name="cart_id"    value="<?php echo (int)$item['id']; ?>">
                            <input type="hidden" name="product_id" value="<?php echo (int)$item['product_id']; ?>">
                            <button type="submit" class="btn btn-sm btn-danger"
                                    data-confirm="Remove this item from your cart?">
                                ✕ Remove
                            </button>
                        </form>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Order Summary -->
            <div class="cart-summary">
                <h3 style="margin-bottom:20px;">Order Summary</h3>

                <?php foreach ($items as $item): ?>
                <div class="cart-total-row">
                    <span><?php echo htmlspecialchars(truncateText($item['name'], 30)); ?></span>
                    <span><?php echo formatPrice($item['price']); ?></span>
                </div>
                <?php endforeach; ?>

                <div class="cart-total-row" style="margin-top:8px;padding-top:16px;border-top:2px solid var(--purple-200);">
                    <span class="cart-grand-total">Total</span>
                    <span class="cart-grand-total"><?php echo formatPrice($subtotal); ?></span>
                </div>

                <form method="POST" style="margin-top:20px;">
                    <input type="hidden" name="action" value="checkout">
                    <button type="submit" class="btn btn-gold btn-full btn-lg">
                        Proceed to Checkout
                    </button>
                </form>

                <a href="products.php" class="btn btn-secondary btn-full" style="margin-top:10px;">
                    Continue Shopping
                </a>
            </div>

        </div>

    <?php endif; ?>
</div>

<?php require_once 'templates/footer.php'; ?>
