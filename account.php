<?php
// account.php
require_once 'includes/sessions.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

requireLogin();
$page_title = 'My Account';

// Fetch user info
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param('i', $_SESSION['user_id']);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Fetch order history
$stmt2 = $conn->prepare(
    "SELECT o.*, p.name, p.image, p.category 
     FROM orders o 
     JOIN products p ON o.product_id = p.id 
     WHERE o.buyer_id = ? 
     ORDER BY o.ordered_at DESC"
);
$stmt2->bind_param('i', $_SESSION['user_id']);
$stmt2->execute();
$orders = $stmt2->get_result()->fetch_all(MYSQLI_ASSOC);

// Fetch active bids
$stmt3 = $conn->prepare(
    "SELECT b.*, p.name, p.price, p.auction_end, p.is_sold 
     FROM bids b 
     JOIN products p ON b.product_id = p.id 
     WHERE b.bidder_id = ? 
     ORDER BY b.placed_at DESC 
     LIMIT 10"
);
$stmt3->bind_param('i', $_SESSION['user_id']);
$stmt3->execute();
$bids = $stmt3->get_result()->fetch_all(MYSQLI_ASSOC);

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    $first_name   = sanitize($_POST['first_name']   ?? '');
    $last_name    = sanitize($_POST['last_name']    ?? '');
    $phone        = sanitize($_POST['phone']        ?? '');
    $address1     = sanitize($_POST['address_line1']?? '');
    $city         = sanitize($_POST['city']         ?? '');
    $postal_code  = sanitize($_POST['postal_code']  ?? '');
    $country      = sanitize($_POST['country']      ?? '');

    $stmt4 = $conn->prepare(
        "UPDATE users SET first_name=?, last_name=?, phone=?, address_line1=?, city=?, postal_code=?, country=? WHERE id=?"
    );
    $stmt4->bind_param('sssssssi', $first_name, $last_name, $phone, $address1, $city, $postal_code, $country, $_SESSION['user_id']);
    $stmt4->execute();
    setFlash('success', 'Profile updated successfully.');
    header("Location: account.php");
    exit();
}

require_once 'templates/header.php';
?>

<div class="page-banner">
    <div class="container">
        <h1>My Account</h1>
        <p>Manage your profile, view orders, and track bids</p>
    </div>
</div>

<div class="container">

    <!-- Tab navigation (simple anchor-based) -->
    <div class="filter-bar" style="margin-bottom:32px;">
        <a href="#profile"  class="filter-btn active">👤 Profile</a>
        <a href="#orders"   class="filter-btn">📦 Orders (<?php echo count($orders); ?>)</a>
        <a href="#bids"     class="filter-btn">🔨 My Bids (<?php echo count($bids); ?>)</a>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:32px;align-items:start;">

        <!-- Profile form -->
        <section id="profile">
            <div class="card card-body">
                <h3 style="margin-bottom:24px;">Profile Information</h3>

                <div style="display:flex;align-items:center;gap:16px;margin-bottom:24px;padding-bottom:24px;border-bottom:1px solid var(--gray-200);">
                    <div style="width:64px;height:64px;border-radius:50%;background:var(--purple-600);display:flex;align-items:center;justify-content:center;font-size:1.6rem;color:var(--white);font-weight:700;">
                        <?php echo strtoupper(substr($user['username'], 0, 1)); ?>
                    </div>
                    <div>
                        <div style="font-weight:700;font-size:1.1rem;"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></div>
                        <div class="text-muted text-small"><?php echo htmlspecialchars($user['email']); ?></div>
                        <div style="margin-top:4px;">
                            <span class="badge badge-high-end"><?php echo ucfirst($user['role']); ?></span>
                        </div>
                    </div>
                </div>

                <form method="POST" novalidate>
                    <input type="hidden" name="action" value="update_profile">
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">First Name</label>
                            <input type="text" name="first_name" class="form-control"
                                   value="<?php echo htmlspecialchars($user['first_name'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Last Name</label>
                            <input type="text" name="last_name" class="form-control"
                                   value="<?php echo htmlspecialchars($user['last_name'] ?? ''); ?>">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Phone</label>
                        <input type="tel" name="phone" class="form-control"
                               value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>"
                               placeholder="+33 6 …">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Address</label>
                        <input type="text" name="address_line1" class="form-control"
                               value="<?php echo htmlspecialchars($user['address_line1'] ?? ''); ?>">
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">City</label>
                            <input type="text" name="city" class="form-control"
                                   value="<?php echo htmlspecialchars($user['city'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Postal Code</label>
                            <input type="text" name="postal_code" class="form-control"
                                   value="<?php echo htmlspecialchars($user['postal_code'] ?? ''); ?>">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Country</label>
                        <input type="text" name="country" class="form-control"
                               value="<?php echo htmlspecialchars($user['country'] ?? ''); ?>">
                    </div>
                    <button type="submit" class="btn btn-primary btn-full">Save Changes</button>
                </form>
            </div>
        </section>

        <!-- Orders + bids -->
        <div>
            <!-- Order history -->
            <section id="orders" style="margin-bottom:32px;">
                <h3 style="margin-bottom:20px;">📦 Order History</h3>
                <?php if (empty($orders)): ?>
                    <div class="card card-body text-center text-muted">No orders yet.</div>
                <?php else: ?>
                    <?php foreach ($orders as $order):
                        $img = !empty($order['image']) ? 'assets/uploads/' . htmlspecialchars($order['image']) : null;
                    ?>
                    <div style="display:flex;gap:16px;align-items:center;background:var(--white);padding:16px;border-radius:var(--radius-md);box-shadow:var(--shadow-sm);margin-bottom:12px;">
                        <div style="width:56px;height:56px;border-radius:var(--radius-sm);background:var(--gray-100);flex-shrink:0;display:flex;align-items:center;justify-content:center;overflow:hidden;font-size:1.5rem;">
                            <?php if ($img): ?>
                                <img src="<?php echo $img; ?>" alt="" style="width:100%;height:100%;object-fit:cover;">
                            <?php else: echo '📦'; endif; ?>
                        </div>
                        <div style="flex:1;min-width:0;">
                            <div style="font-weight:600;font-size:0.9rem;"><?php echo htmlspecialchars($order['name']); ?></div>
                            <div class="text-small text-muted"><?php echo date('d M Y', strtotime($order['ordered_at'])); ?> · <?php echo ucfirst($order['payment_method']); ?> ····<?php echo htmlspecialchars($order['card_last4']); ?></div>
                        </div>
                        <div style="font-weight:700;color:var(--purple-600);"><?php echo formatPrice($order['final_price']); ?></div>
                        <span class="badge" style="background:var(--success);color:var(--white);">✓</span>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </section>

            <!-- Active bids -->
            <section id="bids">
                <h3 style="margin-bottom:20px;">🔨 My Bids</h3>
                <?php if (empty($bids)): ?>
                    <div class="card card-body text-center text-muted">No active bids.</div>
                <?php else: ?>
                    <?php foreach ($bids as $bid): ?>
                    <div style="background:var(--white);padding:16px;border-radius:var(--radius-md);box-shadow:var(--shadow-sm);margin-bottom:12px;">
                        <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:8px;">
                            <div>
                                <a href="product.php?id=<?php echo (int)$bid['product_id']; ?>" style="font-weight:600;font-size:0.9rem;">
                                    <?php echo htmlspecialchars($bid['name']); ?>
                                </a>
                                <div class="text-small text-muted"><?php echo date('d M Y, H:i', strtotime($bid['placed_at'])); ?></div>
                            </div>
                            <?php if ($bid['is_sold']): ?>
                                <span class="badge" style="background:var(--danger);color:var(--white);">Sold</span>
                            <?php else: ?>
                                <span class="badge badge-best-offer">Active</span>
                            <?php endif; ?>
                        </div>
                        <div class="text-small">
                            Your bid: <strong><?php echo formatPrice($bid['bid_amount']); ?></strong>
                            · Max: <strong><?php echo formatPrice($bid['max_amount']); ?></strong>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </section>
        </div>

    </div>
</div>

<?php require_once 'templates/footer.php'; ?>
