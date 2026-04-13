<?php
// checkout.php
require_once 'includes/sessions.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

requireLogin();
$page_title = 'Checkout';

$items = getCartItems($conn, $_SESSION['user_id']);
if (empty($items)) {
    setFlash('info', 'Your cart is empty.');
    header("Location: cart.php");
    exit();
}
$subtotal = array_sum(array_column($items, 'price'));

// Get buyer info for pre-fill
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param('i', $_SESSION['user_id']);
$stmt->execute();
$buyer = $stmt->get_result()->fetch_assoc();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect delivery info
    $delivery = [
        'first_name' => sanitize($_POST['first_name'] ?? ''),
        'last_name' => sanitize($_POST['last_name'] ?? ''),
        'address1' => sanitize($_POST['address1'] ?? ''),
        'address2' => sanitize($_POST['address2'] ?? ''),
        'city' => sanitize($_POST['city'] ?? ''),
        'postal_code' => sanitize($_POST['postal_code'] ?? ''),
        'country' => sanitize($_POST['country'] ?? ''),
        'phone' => sanitize($_POST['phone'] ?? ''),
    ];
    $payment = [
        'type' => sanitize($_POST['card_type'] ?? ''),
        'card_number' => sanitize($_POST['card_number'] ?? ''),
        'card_name' => sanitize($_POST['card_name'] ?? ''),
        'expiry' => sanitize($_POST['expiry'] ?? ''),
        'cvv' => sanitize($_POST['cvv'] ?? ''),
    ];

    // Basic validation
    foreach (['first_name', 'last_name', 'address1', 'city', 'postal_code', 'country'] as $field) {
        if (empty($delivery[$field])) {
            $errors[] = ucwords(str_replace('_', ' ', $field)) . ' is required.';
        }
    }
    if (empty($payment['type']))
        $errors[] = 'Please select a payment method.';
    if (strlen(preg_replace('/\s/', '', $payment['card_number'])) < 12)
        $errors[] = 'Please enter a valid card number.';
    if (empty($payment['card_name']))
        $errors[] = 'Name on card is required.';
    if (empty($payment['expiry']))
        $errors[] = 'Expiry date is required.';
    if (strlen($payment['cvv']) < 3)
        $errors[] = 'CVV must be 3-4 digits.';

    // Simulate payment validation against DB (project requirement)
    // In a real system this would call a payment gateway
    if (empty($errors)) {
        $conn->begin_transaction();
        try {
            foreach ($items as $item) {
                // Insert order
                $stmt2 = $conn->prepare(
                    "INSERT INTO orders (buyer_id, product_id, final_price, purchase_type, payment_method, card_last4)
                    VALUES (?,?,?,?,?,?)"
                );
                $last4 = substr(preg_replace('/\s/', '', $payment['card_number']), -4);
                $purchase_type = $item['sale_type'];
                $card_type = strtolower($payment['type']);
                $stmt2->bind_param(
                    'iidsss',
                    $_SESSION['user_id'],
                    $item['product_id'],
                    $item['price'],
                    $purchase_type,
                    $card_type,
                    $last4
                );
                $stmt2->execute();

                // Mark product as sold
                $stmt3 = $conn->prepare("UPDATE products SET is_sold = 1 WHERE id = ?");
                $stmt3->bind_param('i', $item['product_id']);
                $stmt3->execute();

                // Remove from cart
                $stmt4 = $conn->prepare("DELETE FROM cart WHERE id = ?");
                $stmt4->bind_param('i', $item['id']);
                $stmt4->execute();
            }
            $conn->commit();
            setFlash('success', '🎉 Order placed! A confirmation has been sent to ' . $buyer['email']);
            header("Location: account.php");
            exit();
        } catch (Exception $e) {
            $conn->rollback();
            $errors[] = 'Payment processing failed. Please try again.';
        }
    }
}

require_once 'templates/header.php';
?>

<div class="page-banner">
    <div class="container">
        <h1>Checkout</h1>
        <p>Complete your purchase securely</p>
    </div>
</div>

<div class="container">

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <?php foreach ($errors as $e): ?>
                <div><?php echo htmlspecialchars($e); ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="POST" novalidate>
        <div style="display:grid;grid-template-columns:1fr 380px;gap:32px;align-items:start;">

            <!-- Left: Forms -->
            <div>
                <!-- Delivery -->
                <div class="card card-body" style="margin-bottom:24px;">
                    <h3 style="margin-bottom:20px;">📦 Delivery Information</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">First Name</label>
                            <input type="text" name="first_name" class="form-control" value="<?php echo htmlspecialchars($buyer['first_name'] ?? ''); ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Last Name</label>
                            <input type="text" name="last_name" class="form-control" value="<?php echo htmlspecialchars($buyer['last_name'] ?? ''); ?>" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Address Line 1</label>
                        <input type="text" name="address1" class="form-control" value="<?php echo htmlspecialchars($buyer['address_line1'] ?? ''); ?>" placeholder="12 Rue de la Paix" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Address Line 2 <span class="text-muted">(optional)</span></label>
                        <input type="text" name="address2" class="form-control" value="<?php echo htmlspecialchars($buyer['address_line2'] ?? ''); ?>" placeholder="Apt 3B">
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">City</label>
                            <input type="text" name="city" class="form-control" value="<?php echo htmlspecialchars($buyer['city'] ?? ''); ?>" placeholder="Paris" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Postal Code</label>
                            <input type="text" name="postal_code" class="form-control" value="<?php echo htmlspecialchars($buyer['postal_code'] ?? ''); ?>" placeholder="75001" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Country</label>
                            <input type="text" name="country" class="form-control" value="<?php echo htmlspecialchars($buyer['country'] ?? 'France'); ?>" placeholder="France" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Phone Number</label>
                            <input type="tel" name="phone" class="form-control" value="<?php echo htmlspecialchars($buyer['phone'] ?? ''); ?>" placeholder="+33 6 12 34 56 78">
                        </div>
                    </div>
                </div>

                <!-- Payment -->
                <div class="card card-body">
                    <h3 style="margin-bottom:20px;">💳 Payment Information</h3>
                    <p class="text-small text-muted" style="margin-bottom:16px;">
                        Your payment information is encrypted and secure.
                    </p>

                    <div class="form-group">
                        <label class="form-label">Payment Method</label>
                        <select name="card_type" class="form-control" required>
                            <option value="">Select payment method</option>
                            <option value="visa">💳 Visa</option>
                            <option value="mastercard">💳 MasterCard</option>
                            <option value="amex">💳 American Express</option>
                            <option value="paypal">🅿 PayPal</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Card Number</label>
                        <input type="text" name="card_number" class="form-control" placeholder="•••• •••• •••• ••••" maxlength="19" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Name on Card</label>
                        <input type="text" name="card_name" class="form-control" value="<?php echo htmlspecialchars(($buyer['first_name'] ?? '') . ' ' . ($buyer['last_name'] ?? '')); ?>" placeholder="MARIE DUPONT" required>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Expiry Date</label>
                            <input type="text" name="expiry" class="form-control" placeholder="MM/YY" maxlength="5" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">CVV / Security Code</label>
                            <input type="text" name="cvv" class="form-control" placeholder="•••" maxlength="4" required>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right: Summary -->
            <div class="cart-summary" style="position:sticky;top:calc(var(--nav-height) + 24px);">
                <h3 style="margin-bottom:20px;">Order Review</h3>

                <?php foreach ($items as $item): ?>
                    <div class="cart-total-row">
                        <span><?php echo htmlspecialchars(truncateText($item['name'], 28)); ?></span>
                        <span><?php echo formatPrice($item['price']); ?></span>
                    </div>
                <?php endforeach; ?>

                <div class="cart-total-row" style="margin-top:8px;padding-top:16px;border-top:2px solid var(--purple-200);">
                    <span>Shipping</span>
                    <span class="text-small" style="color:var(--success);font-weight:600;">Free</span>
                </div>

                <div class="cart-total-row">
                    <span class="cart-grand-total">Total</span>
                    <span class="cart-grand-total"><?php echo formatPrice($subtotal); ?></span>
                </div>

                <button type="submit" class="btn btn-gold btn-full btn-lg" style="margin-top:20px;">
                    ✓ Place Order — <?php echo formatPrice($subtotal); ?>
                </button>

                <p class="text-small text-muted text-center" style="margin-top:12px;">
                    🔒 Secure payment. By placing your order, you agree to our terms.
                </p>

                <a href="cart.php" class="btn btn-secondary btn-full" style="margin-top:8px;">← Back to Cart</a>
            </div>

        </div>
    </form>
</div>

<?php require_once 'templates/footer.php'; ?>