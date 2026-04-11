<?php
// admin.php — Administrator dashboard
require_once 'includes/sessions.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

requireAdmin();
$page_title = 'Admin Dashboard';

// ── Handle POST actions ──────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Add seller
    if ($action === 'add_seller') {
        $username   = sanitize($_POST['username']   ?? '');
        $email      = sanitize($_POST['email']      ?? '');
        $first_name = sanitize($_POST['first_name'] ?? '');
        $last_name  = sanitize($_POST['last_name']  ?? '');
        $password   = $_POST['password'] ?? 'Seller@1234'; // default pw

        if ($username && $email) {
            // Check unique
            $check = $conn->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
            $check->bind_param('ss', $email, $username);
            $check->execute();
            if ($check->get_result()->num_rows > 0) {
                setFlash('danger', 'Email or username already exists.');
            } else {
                $hash = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $conn->prepare(
                    "INSERT INTO users (username, email, password_hash, role, first_name, last_name) VALUES (?,?,?,'seller',?,?)"
                );
                $stmt->bind_param('sssss', $username, $email, $hash, $first_name, $last_name);
                $stmt->execute();
                setFlash('success', "Seller account created for $username.");
            }
        } else {
            setFlash('danger', 'Username and email are required.');
        }
    }

    // Remove seller
    if ($action === 'remove_seller') {
        $seller_id = (int)($_POST['seller_id'] ?? 0);
        if ($seller_id && $seller_id !== (int)$_SESSION['user_id']) {
            $stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND role = 'seller'");
            $stmt->bind_param('i', $seller_id);
            $stmt->execute();
            setFlash('success', 'Seller removed.');
        }
    }

    // Mark item as sold / delete listing
    if ($action === 'delete_product') {
        $product_id = (int)($_POST['product_id'] ?? 0);
        if ($product_id) {
            $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
            $stmt->bind_param('i', $product_id);
            $stmt->execute();
            setFlash('success', 'Product removed.');
        }
    }

    // Award auction winner
    if ($action === 'award_auction') {
        $product_id = (int)($_POST['product_id'] ?? 0);
        if ($product_id) {
            // Get highest bidder
            $stmt = $conn->prepare(
                "SELECT * FROM bids WHERE product_id = ? ORDER BY bid_amount DESC LIMIT 1"
            );
            $stmt->bind_param('i', $product_id);
            $stmt->execute();
            $winner = $stmt->get_result()->fetch_assoc();

            if ($winner) {
                // Mark product sold
                $conn->prepare("UPDATE products SET is_sold = 1 WHERE id = ?")->bind_param('i', $product_id);
                $conn->prepare("UPDATE products SET is_sold = 1 WHERE id = $product_id")->execute();

                // Mark winning bid
                $conn->query("UPDATE bids SET is_winner = 1 WHERE id = " . (int)$winner['id']);

                // Notify winner
                $msg  = 'Congratulations! You won the auction for "' . $product_id . '" with a bid of €' . number_format($winner['bid_amount'], 2) . '.';
                $link = 'product.php?id=' . $product_id;
                $stmt2 = $conn->prepare("INSERT INTO notifications (user_id, message, link) VALUES (?,?,?)");
                $stmt2->bind_param('iss', $winner['bidder_id'], $msg, $link);
                $stmt2->execute();

                setFlash('success', 'Auction awarded to user #' . $winner['bidder_id'] . ' for €' . number_format($winner['bid_amount'], 2));
            } else {
                setFlash('warning', 'No bids found for this auction.');
            }
        }
    }

    // Accept negotiation
    if ($action === 'accept_negotiation') {
        $neg_id     = (int)($_POST['neg_id']     ?? 0);
        $product_id = (int)($_POST['product_id'] ?? 0);
        if ($neg_id) {
            $conn->query("UPDATE negotiations SET status = 'accepted' WHERE id = $neg_id");
            $conn->query("UPDATE products SET is_sold = 1 WHERE id = $product_id");

            // Notify buyer
            $stmt = $conn->prepare("SELECT buyer_id, buyer_offer FROM negotiations WHERE id = ?");
            $stmt->bind_param('i', $neg_id);
            $stmt->execute();
            $neg = $stmt->get_result()->fetch_assoc();
            if ($neg) {
                $msg  = 'Your negotiation offer of €' . number_format($neg['buyer_offer'], 2) . ' was accepted! Please complete checkout.';
                $link = 'cart.php';
                $stmt2 = $conn->prepare("INSERT INTO notifications (user_id, message, link) VALUES (?,?,?)");
                $stmt2->bind_param('iss', $neg['buyer_id'], $msg, $link);
                $stmt2->execute();
            }
            setFlash('success', 'Offer accepted and buyer notified.');
        }
    }

    header("Location: admin.php");
    exit();
}

// ── Gather stats ─────────────────────────────────────────────
$stats = [];
$stats['products'] = $conn->query("SELECT COUNT(*) AS c FROM products WHERE is_sold = 0")->fetch_assoc()['c'];
$stats['sold']     = $conn->query("SELECT COUNT(*) AS c FROM orders")->fetch_assoc()['c'];
$stats['buyers']   = $conn->query("SELECT COUNT(*) AS c FROM users WHERE role = 'buyer'")->fetch_assoc()['c'];
$stats['sellers']  = $conn->query("SELECT COUNT(*) AS c FROM users WHERE role = 'seller'")->fetch_assoc()['c'];
$stats['revenue']  = $conn->query("SELECT COALESCE(SUM(final_price),0) AS s FROM orders")->fetch_assoc()['s'];

$sellers      = getUsersByRole($conn, 'seller');
$all_products = getProducts($conn);
$auctions     = getActiveAuctions($conn);

// Pending negotiations (most recent per product)
$neg_sql = "SELECT n.*, p.name AS product_name, p.price AS listed_price, u.username AS buyer_name
            FROM negotiations n
            JOIN products p ON n.product_id = p.id
            JOIN users u ON n.buyer_id = u.id
            WHERE n.status = 'pending'
            ORDER BY n.created_at DESC";
$pending_negs = $conn->query($neg_sql)->fetch_all(MYSQLI_ASSOC);

require_once 'templates/header.php';
?>

<div class="page-banner">
    <div class="container">
        <h1>Admin Dashboard</h1>
        <p>Manage sellers, products, auctions, and negotiations</p>
    </div>
</div>

<div class="container">

    <!-- Stat cards -->
    <div class="stat-cards">
        <div class="stat-card">
            <div class="stat-value"><?php echo $stats['products']; ?></div>
            <div class="stat-label">Active Listings</div>
        </div>
        <div class="stat-card" style="border-color:var(--success);">
            <div class="stat-value"><?php echo $stats['sold']; ?></div>
            <div class="stat-label">Items Sold</div>
        </div>
        <div class="stat-card" style="border-color:var(--gold);">
            <div class="stat-value">€<?php echo number_format($stats['revenue'], 0); ?></div>
            <div class="stat-label">Total Revenue</div>
        </div>
        <div class="stat-card" style="border-color:var(--info);">
            <div class="stat-value"><?php echo $stats['buyers']; ?></div>
            <div class="stat-label">Buyers</div>
        </div>
        <div class="stat-card" style="border-color:var(--lavender);">
            <div class="stat-value"><?php echo $stats['sellers']; ?></div>
            <div class="stat-label">Sellers</div>
        </div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:32px;align-items:start;">

        <!-- ── Sellers management ──────────────────────────── -->
        <section>
            <h3 style="margin-bottom:20px;">👥 Seller Accounts</h3>

            <!-- Add seller form -->
            <div class="card card-body" style="margin-bottom:24px;">
                <h4 style="margin-bottom:16px;">Add New Seller</h4>
                <form method="POST" novalidate>
                    <input type="hidden" name="action" value="add_seller">
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">First Name</label>
                            <input type="text" name="first_name" class="form-control" placeholder="Sophie">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Last Name</label>
                            <input type="text" name="last_name" class="form-control" placeholder="Martin">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Username</label>
                        <input type="text" name="username" class="form-control" placeholder="sophie_m" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" placeholder="sophie@example.com" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Password <span class="text-muted">(default: Seller@1234)</span></label>
                        <input type="password" name="password" class="form-control" placeholder="Leave blank for default">
                    </div>
                    <button type="submit" class="btn btn-primary">Add Seller Account</button>
                </form>
            </div>

            <!-- Seller list -->
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Since</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($sellers)): ?>
                        <tr><td colspan="4" class="text-center text-muted">No sellers yet.</td></tr>
                        <?php else: ?>
                        <?php foreach ($sellers as $s): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($s['first_name'] . ' ' . $s['last_name']); ?></strong>
                                <br><span class="text-muted text-small">@<?php echo htmlspecialchars($s['username']); ?></span>
                            </td>
                            <td class="text-small"><?php echo htmlspecialchars($s['email']); ?></td>
                            <td class="text-small"><?php echo date('d M Y', strtotime($s['created_at'])); ?></td>
                            <td>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="action"    value="remove_seller">
                                    <input type="hidden" name="seller_id" value="<?php echo (int)$s['id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-danger"
                                            data-confirm="Remove this seller? Their listings will also be deleted.">
                                        Remove
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- ── Pending negotiations ───────────────────────── -->
        <section>
            <h3 style="margin-bottom:20px;">🤝 Pending Negotiations</h3>
            <?php if (empty($pending_negs)): ?>
                <div class="card card-body text-center text-muted">No pending negotiations.</div>
            <?php else: ?>
                <?php foreach ($pending_negs as $neg): ?>
                <div style="background:var(--white);border-radius:var(--radius-md);padding:18px;box-shadow:var(--shadow-sm);margin-bottom:12px;">
                    <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:10px;">
                        <div>
                            <div style="font-weight:600;"><?php echo htmlspecialchars($neg['product_name']); ?></div>
                            <div class="text-small text-muted">Buyer: @<?php echo htmlspecialchars($neg['buyer_name']); ?> · Round <?php echo (int)$neg['round']; ?>/5</div>
                        </div>
                        <div style="text-align:right;">
                            <div class="text-small text-muted">Listed: <?php echo formatPrice($neg['listed_price']); ?></div>
                            <div style="font-weight:700;color:var(--purple-600);">Offer: <?php echo formatPrice($neg['buyer_offer']); ?></div>
                        </div>
                    </div>
                    <form method="POST">
                        <input type="hidden" name="action"     value="accept_negotiation">
                        <input type="hidden" name="neg_id"     value="<?php echo (int)$neg['id']; ?>">
                        <input type="hidden" name="product_id" value="<?php echo (int)$neg['product_id']; ?>">
                        <button type="submit" class="btn btn-sm" style="background:var(--success);color:var(--white);"
                                data-confirm="Accept this offer of <?php echo formatPrice($neg['buyer_offer']); ?>?">
                            ✓ Accept Offer
                        </button>
                    </form>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>

    </div><!-- /2-col grid -->

    <!-- ── Live Auctions ─────────────────────────────────────── -->
    <section style="margin-top:40px;">
        <h3 style="margin-bottom:20px;">🔨 Live Auctions — Bid Supervision</h3>
        <?php if (empty($auctions)): ?>
            <div class="card card-body text-center text-muted">No active auctions.</div>
        <?php else: ?>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Starting Price</th>
                        <th>Highest Bid</th>
                        <th>Bids</th>
                        <th>Ends</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($auctions as $a): ?>
                    <tr>
                        <td>
                            <a href="product.php?id=<?php echo (int)$a['id']; ?>">
                                <?php echo htmlspecialchars($a['name']); ?>
                            </a>
                        </td>
                        <td><?php echo formatPrice($a['price']); ?></td>
                        <td>
                            <?php if ($a['highest_bid']): ?>
                                <strong style="color:var(--danger);"><?php echo formatPrice($a['highest_bid']); ?></strong>
                            <?php else: ?>
                                <span class="text-muted">No bids</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo (int)$a['bid_count']; ?></td>
                        <td class="text-small"><?php echo date('d M Y, H:i', strtotime($a['auction_end'])); ?></td>
                        <td>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="action"     value="award_auction">
                                <input type="hidden" name="product_id" value="<?php echo (int)$a['id']; ?>">
                                <button type="submit" class="btn btn-sm btn-gold"
                                        data-confirm="Award this auction to the highest bidder?">
                                    Award Winner
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </section>

    <!-- ── All Products ──────────────────────────────────────── -->
    <section style="margin-top:40px;margin-bottom:40px;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
            <h3>📦 All Listings (<?php echo count($all_products); ?>)</h3>
            <a href="seller.php" class="btn btn-primary btn-sm">+ Add New Product</a>
        </div>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Category</th>
                        <th>Sale Type</th>
                        <th>Price</th>
                        <th>Seller</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($all_products as $p): ?>
                    <tr>
                        <td>
                            <a href="product.php?id=<?php echo (int)$p['id']; ?>">
                                <?php echo htmlspecialchars($p['name']); ?>
                            </a>
                        </td>
                        <td><span class="badge badge-<?php echo str_replace('_','-',$p['category']); ?>"><?php echo getCategoryLabel($p['category']); ?></span></td>
                        <td><span class="badge badge-<?php echo str_replace('_','-',$p['sale_type']); ?>"><?php echo getSaleTypeLabel($p['sale_type']); ?></span></td>
                        <td><?php echo formatPrice($p['price']); ?></td>
                        <td class="text-small"><?php echo htmlspecialchars($p['seller_name'] ?? '—'); ?></td>
                        <td>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="action"     value="delete_product">
                                <input type="hidden" name="product_id" value="<?php echo (int)$p['id']; ?>">
                                <button type="submit" class="btn btn-sm btn-danger"
                                        data-confirm="Permanently delete this listing?">
                                    Delete
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>

</div><!-- /.container -->

<?php require_once 'templates/footer.php'; ?>
