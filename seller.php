<?php
// seller.php — Seller dashboard: list items, handle negotiations
require_once 'includes/sessions.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

requireLogin();
if (!isSeller()) {
    setFlash('danger', 'You need a seller account to access this page.');
    header("Location: index.php");
    exit();
}

$page_title = 'My Listings';

// ── Handle POST actions ──────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // ─ Add product ─────────────────────────────────────────
    if ($action === 'add_product') {
        $name = sanitize($_POST['name'] ?? '');
        $description = sanitize($_POST['description'] ?? '');
        $defects = sanitize($_POST['defects'] ?? '');
        $price = (float) ($_POST['price'] ?? 0);
        $category = sanitize($_POST['category'] ?? '');
        $sale_type = sanitize($_POST['sale_type'] ?? '');
        $auction_start = sanitize($_POST['auction_start'] ?? '');
        $auction_end = sanitize($_POST['auction_end'] ?? '');

        $allowed_cats = ['rare', 'high_end', 'regular'];
        $allowed_sales = ['buy_now', 'negotiation', 'best_offer'];

        $errors = [];
        if (empty($name))
            $errors[] = 'Product name is required.';
        if ($price <= 0)
            $errors[] = 'Price must be greater than 0.';
        if (!in_array($category, $allowed_cats))
            $errors[] = 'Invalid category.';
        if (!in_array($sale_type, $allowed_sales))
            $errors[] = 'Invalid sale type.';
        if ($sale_type === 'best_offer' && empty($auction_end))
            $errors[] = 'Auction end date is required for Best Offer items.';

        // Handle image upload
        $image_filename = null;
        if (!empty($_FILES['product_image']['name'])) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
            $file_type = $_FILES['product_image']['type'];
            $file_size = $_FILES['product_image']['size'];

            if (!in_array($file_type, $allowed_types)) {
                $errors[] = 'Only JPG, PNG, WebP, GIF images are allowed.';
            } elseif ($file_size > 5 * 1024 * 1024) {
                $errors[] = 'Image must be under 5MB.';
            } else {
                $ext = pathinfo($_FILES['product_image']['name'], PATHINFO_EXTENSION);
                $image_filename = 'product_' . uniqid() . '.' . $ext;
                $dest = __DIR__ . '/assets/uploads/' . $image_filename;
                if (!move_uploaded_file($_FILES['product_image']['tmp_name'], $dest)) {
                    $errors[] = 'Failed to upload image.';
                    $image_filename = null;
                }
            }
        }

        if (empty($errors)) {
            $as = $auction_start ?: null;
            $ae = $auction_end ?: null;
            $stmt = $conn->prepare(
                "INSERT INTO products (seller_id, name, description, defects, price, category, sale_type, image, auction_start, auction_end)
                 VALUES (?,?,?,?,?,?,?,?,?,?)"
            );
            $stmt->bind_param(
                'isssdsssss',
                $_SESSION['user_id'],
                $name,
                $description,
                $defects,
                $price,
                $category,
                $sale_type,
                $image_filename,
                $as,
                $ae
            );
            $stmt->execute();
            setFlash('success', "\"$name\" has been listed successfully.");
        } else {
            foreach ($errors as $e) {
                setFlash('danger', $e);
            }
        }
    }

    // ─ Remove product ───────────────────────────────────────
    if ($action === 'delete_product') {
        $product_id = (int) ($_POST['product_id'] ?? 0);
        // Only allow seller to delete their own listings
        $stmt = $conn->prepare("DELETE FROM products WHERE id = ? AND seller_id = ?");
        $stmt->bind_param('ii', $product_id, $_SESSION['user_id']);
        $stmt->execute();
        setFlash('info', 'Listing removed.');
    }

    // ─ Send counter-offer (negotiation) ────────────────────
    if ($action === 'counter_offer') {
        $neg_id = (int) ($_POST['neg_id'] ?? 0);
        $counter = (float) ($_POST['counter'] ?? 0);
        $product_id = (int) ($_POST['product_id'] ?? 0);

        if ($neg_id && $counter > 0) {
            $stmt = $conn->prepare("UPDATE negotiations SET seller_reply = ?, status = 'countered' WHERE id = ?");
            $stmt->bind_param('di', $counter, $neg_id);
            $stmt->execute();

            // Notify buyer
            $neg_stmt = $conn->prepare("SELECT buyer_id FROM negotiations WHERE id = ?");
            $neg_stmt->bind_param('i', $neg_id);
            $neg_stmt->execute();
            $neg_row = $neg_stmt->get_result()->fetch_assoc();
            if ($neg_row) {
                $msg = 'The seller has countered with €' . number_format($counter, 2) . '. Check your negotiation thread.';
                $link = 'product.php?id=' . $product_id;
                $stmt2 = $conn->prepare("INSERT INTO notifications (user_id, message, link) VALUES (?,?,?)");
                $stmt2->bind_param('iss', $neg_row['buyer_id'], $msg, $link);
                $stmt2->execute();
            }
            setFlash('success', 'Counter-offer of €' . number_format($counter, 2) . ' sent.');
        }
    }

    header("Location: seller.php");
    exit();
}

// ── Fetch seller's listings ──────────────────────────────────
$stmt = $conn->prepare(
    "SELECT * FROM products WHERE seller_id = ? ORDER BY created_at DESC"
);
$stmt->bind_param('i', $_SESSION['user_id']);
$stmt->execute();
$my_products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// ── Fetch pending negotiations for seller's items ────────────
$negs_sql = "SELECT n.*, p.name AS product_name, p.price AS listed_price, p.seller_id,
                    u.username AS buyer_name
             FROM negotiations n
             JOIN products p ON n.product_id = p.id
             JOIN users u ON n.buyer_id = u.id
             WHERE p.seller_id = ? AND n.status = 'pending'
             ORDER BY n.created_at DESC";
$stmt2 = $conn->prepare($negs_sql);
$stmt2->bind_param('i', $_SESSION['user_id']);
$stmt2->execute();
$pending_negs = $stmt2->get_result()->fetch_all(MYSQLI_ASSOC);

require_once 'templates/header.php';
?>

<div class="page-banner">
    <div class="container">
        <h1>My Listings</h1>
        <p>Manage your products and respond to buyer negotiations</p>
    </div>
</div>

<div class="container">
    <div style="display:grid;grid-template-columns:1fr 400px;gap:32px;align-items:start;">

        <!-- Left: Product list + negotiations -->
        <div>

            <!-- Active listings -->
            <h3 style="margin-bottom:20px;">
                My Products (<?php echo count($my_products); ?>)
            </h3>

            <?php if (empty($my_products)): ?>
                <div class="card card-body text-center text-muted" style="margin-bottom:32px;">
                    No listings yet. Add your first product →
                </div>
            <?php else: ?>
                <div class="table-wrapper" style="margin-bottom:40px;">
                    <table>
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th>Category</th>
                                <th>Sale Type</th>
                                <th>Price</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($my_products as $p): ?>
                                <tr>
                                    <td>
                                        <a href="product.php?id=<?php echo (int) $p['id']; ?>">
                                            <?php echo htmlspecialchars($p['name']); ?>
                                        </a>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?php echo str_replace('_', '-', $p['category']); ?>">
                                            <?php echo getCategoryLabel($p['category']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?php echo str_replace('_', '-', $p['sale_type']); ?>">
                                            <?php echo getSaleTypeLabel($p['sale_type']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo formatPrice($p['price']); ?></td>
                                    <td>
                                        <?php if ($p['is_sold']): ?>
                                            <span class="badge" style="background:var(--success);color:var(--white);">Sold</span>
                                        <?php else: ?>
                                            <span class="badge badge-buy-now">Available</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="action" value="delete_product">
                                            <input type="hidden" name="product_id" value="<?php echo (int) $p['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-danger" data-confirm="Remove this listing?">Remove</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

            <!-- Pending negotiations -->
            <h3 style="margin-bottom:20px;">🤝 Buyer Offers (<?php echo count($pending_negs); ?>)</h3>

            <?php if (empty($pending_negs)): ?>
                <div class="card card-body text-center text-muted">No pending offers.</div>
            <?php else: ?>
                <?php foreach ($pending_negs as $neg): ?>
                    <div style="background:var(--white);border-radius:var(--radius-md);padding:20px;box-shadow:var(--shadow-sm);margin-bottom:16px;">
                        <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:14px;">
                            <div>
                                <div style="font-weight:700;"><?php echo htmlspecialchars($neg['product_name']); ?></div>
                                <div class="text-small text-muted">
                                    Buyer: <strong>@<?php echo htmlspecialchars($neg['buyer_name']); ?></strong>
                                    · Round <?php echo (int) $neg['round']; ?>/5
                                    · <?php echo date('d M Y, H:i', strtotime($neg['created_at'])); ?>
                                </div>
                            </div>
                            <div style="text-align:right;">
                                <div class="text-small text-muted">Listed: <?php echo formatPrice($neg['listed_price']); ?></div>
                                <div style="font-size:1.2rem;font-weight:700;color:var(--purple-600);">
                                    Offer: <?php echo formatPrice($neg['buyer_offer']); ?>
                                </div>
                            </div>
                        </div>

                        <div style="display:flex;gap:10px;flex-wrap:wrap;">
                            <!-- Accept -->
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="action" value="accept_negotiation">
                                <input type="hidden" name="neg_id" value="<?php echo (int) $neg['id']; ?>">
                                <input type="hidden" name="product_id" value="<?php echo (int) $neg['product_id']; ?>">
                                <button type="submit" class="btn btn-sm" style="background:var(--success);color:var(--white);" data-confirm="Accept offer of <?php echo formatPrice($neg['buyer_offer']); ?>?">
                                    ✓ Accept
                                </button>
                            </form>

                            <!-- Counter-offer -->
                            <form method="POST" style="display:inline-flex;gap:8px;align-items:center;">
                                <input type="hidden" name="action" value="counter_offer">
                                <input type="hidden" name="neg_id" value="<?php echo (int) $neg['id']; ?>">
                                <input type="hidden" name="product_id" value="<?php echo (int) $neg['product_id']; ?>">
                                <input type="number" name="counter" class="form-control" style="width:120px;padding:7px 10px;font-size:0.85rem;" step="0.01" min="0.01" placeholder="€ counter" required>
                                <button type="submit" class="btn btn-sm btn-secondary">Counter →</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

        </div>

        <!-- Right: Add product form -->
        <div style="position:sticky;top:calc(var(--nav-height) + 24px);">
            <div class="card card-body">
                <h3 style="margin-bottom:20px;">+ List a New Item</h3>

                <form method="POST" enctype="multipart/form-data" novalidate>
                    <input type="hidden" name="action" value="add_product">

                    <div class="form-group">
                        <label class="form-label">Product Name</label>
                        <input type="text" name="name" class="form-control" placeholder="e.g. Hand-Blown Glass Vase" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" placeholder="Describe the item in detail…"></textarea>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Defects / Condition Notes <span class="text-muted">(optional)</span></label>
                        <textarea name="defects" class="form-control" style="min-height:60px;" placeholder="Any defects or special conditions…"></textarea>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Category</label>
                            <select name="category" class="form-control" required>
                                <option value="">Select…</option>
                                <option value="regular">● Regular</option>
                                <option value="high_end">◈ High-End</option>
                                <option value="rare">✦ Rare</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Sale Type</label>
                            <select name="sale_type" class="form-control" required id="sale_type_select">
                                <option value="">Select…</option>
                                <option value="buy_now">⚡ Buy It Now</option>
                                <option value="negotiation">🤝 Negotiation</option>
                                <option value="best_offer">🔨 Best Offer</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Price (€)</label>
                        <input type="number" name="price" class="form-control" step="0.01" min="0.01" placeholder="0.00" required>
                    </div>

                    <!-- Auction dates (shown only for best_offer) -->
                    <div id="auction_dates" style="display:none;">
                        <div class="form-column">
                            <div class="form-group">
                                <label class="form-label">Auction Start</label>
                                <input type="datetime-local" name="auction_start" class="form-control">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Auction End</label>
                                <input type="datetime-local" name="auction_end" class="form-control">
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Product Image <span class="text-muted">(JPG/PNG, max 5MB)</span></label>
                        <input type="file" id="product_image" name="product_image" class="form-control" accept="image/*">
                        <img id="image_preview" src="" alt="Preview" class="hidden" style="margin-top:10px;width:100%;border-radius:var(--radius-sm);max-height:160px;object-fit:cover;">
                    </div>

                    <button type="submit" class="btn btn-primary btn-full btn-lg">List Item for Sale</button>
                </form>
            </div>
        </div>

    </div>
</div>

<?php
$extra_scripts = ['assets/scripts/seller.js'];
require_once 'templates/footer.php';
?>