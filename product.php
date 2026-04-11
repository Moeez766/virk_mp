<?php
// product.php — Single product detail with purchase actions
require_once 'includes/sessions.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) { header("Location: products.php"); exit(); }

$product = getProductById($conn, $id);
if (!$product) {
    setFlash('danger', 'Product not found.');
    header("Location: products.php");
    exit();
}

$page_title = htmlspecialchars($product['name']);

// ── Handle POST actions ──────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isLoggedIn()) {
    $action = $_POST['action'] ?? '';

    // ─ Add to cart (Buy It Now) ─────────────────────────────
    if ($action === 'add_to_cart' && $product['sale_type'] === 'buy_now') {
        // Check not already in cart
        $stmt = $conn->prepare("SELECT id FROM cart WHERE user_id = ? AND product_id = ?");
        $stmt->bind_param('ii', $_SESSION['user_id'], $id);
        $stmt->execute();
        if ($stmt->get_result()->num_rows === 0) {
            $stmt2 = $conn->prepare("INSERT INTO cart (user_id, product_id) VALUES (?,?)");
            $stmt2->bind_param('ii', $_SESSION['user_id'], $id);
            $stmt2->execute();
            setFlash('success', 'Item added to your cart!');
        } else {
            setFlash('info', 'This item is already in your cart.');
        }
        header("Location: product.php?id=$id");
        exit();
    }

    // ─ Place a bid (Best Offer) ─────────────────────────────
    if ($action === 'place_bid' && $product['sale_type'] === 'best_offer') {
        $max_bid = (float)($_POST['max_bid'] ?? 0);

        // Check auction is still open
        $now = new DateTime();
        $end = new DateTime($product['auction_end']);

        if ($now > $end) {
            setFlash('danger', 'This auction has already ended.');
        } elseif ($max_bid <= 0) {
            setFlash('danger', 'Please enter a valid bid amount.');
        } else {
            // Get current highest bid
            $stmt = $conn->prepare("SELECT MAX(bid_amount) AS top FROM bids WHERE product_id = ?");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $top_row   = $stmt->get_result()->fetch_assoc();
            $top_bid   = (float)($top_row['top'] ?? 0);
            $bid_amount = $top_bid > 0 ? $top_bid + 1 : $product['price'];

            if ($max_bid <= $top_bid) {
                setFlash('danger', 'Your max bid must exceed the current highest bid of ' . formatPrice($top_bid));
            } else {
                $stmt2 = $conn->prepare("INSERT INTO bids (product_id, bidder_id, bid_amount, max_amount) VALUES (?,?,?,?)");
                $stmt2->bind_param('iidd', $id, $_SESSION['user_id'], $bid_amount, $max_bid);
                $stmt2->execute();

                // Notify seller
                $msg  = 'A new bid of ' . formatPrice($bid_amount) . ' has been placed on "' . $product['name'] . '".';
                $stmt3 = $conn->prepare("INSERT INTO notifications (user_id, message, link) VALUES (?,?,?)");
                $link  = 'product.php?id=' . $id;
                $stmt3->bind_param('iss', $product['seller_id'], $msg, $link);
                $stmt3->execute();

                setFlash('success', 'Your bid of ' . formatPrice($bid_amount) . ' has been placed!');
            }
        }
        header("Location: product.php?id=$id");
        exit();
    }

    // ─ Make a negotiation offer ─────────────────────────────
    if ($action === 'negotiate' && $product['sale_type'] === 'negotiation') {
        $offer = (float)($_POST['offer_amount'] ?? 0);

        if ($offer <= 0) {
            setFlash('danger', 'Please enter a valid offer amount.');
        } else {
            // Count existing rounds for this buyer + product
            $stmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM negotiations WHERE product_id = ? AND buyer_id = ?");
            $stmt->bind_param('ii', $id, $_SESSION['user_id']);
            $stmt->execute();
            $cnt_row = $stmt->get_result()->fetch_assoc();
            $rounds_used = (int)$cnt_row['cnt'];

            if ($rounds_used >= 5) {
                setFlash('danger', 'You have reached the maximum of 5 negotiation rounds for this item.');
            } else {
                $round = $rounds_used + 1;
                $stmt2 = $conn->prepare("INSERT INTO negotiations (product_id, buyer_id, round, buyer_offer) VALUES (?,?,?,?)");
                $stmt2->bind_param('iiid', $id, $_SESSION['user_id'], $round, $offer);
                $stmt2->execute();

                // Notify seller
                $msg  = $_SESSION['username'] . ' made an offer of ' . formatPrice($offer) . ' on "' . $product['name'] . '". Round ' . $round . '/5.';
                $stmt3 = $conn->prepare("INSERT INTO notifications (user_id, message, link) VALUES (?,?,?)");
                $link  = 'seller.php?action=negotiate&product_id=' . $id;
                $stmt3->bind_param('iss', $product['seller_id'], $msg, $link);
                $stmt3->execute();

                setFlash('success', 'Your offer of ' . formatPrice($offer) . ' has been sent to the seller (round ' . $round . '/5).');
            }
        }
        header("Location: product.php?id=$id");
        exit();
    }
}

// Load additional data
$highest_bid  = null;
$bid_count    = 0;
$my_rounds    = 0;
$negotiations = [];

if ($product['sale_type'] === 'best_offer') {
    $stmt = $conn->prepare("SELECT MAX(bid_amount) AS top, COUNT(*) AS cnt FROM bids WHERE product_id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $bid_data    = $stmt->get_result()->fetch_assoc();
    $highest_bid = $bid_data['top'];
    $bid_count   = (int)$bid_data['cnt'];
}

if ($product['sale_type'] === 'negotiation' && isLoggedIn()) {
    $stmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM negotiations WHERE product_id = ? AND buyer_id = ?");
    $stmt->bind_param('ii', $id, $_SESSION['user_id']);
    $stmt->execute();
    $my_rounds = (int)$stmt->get_result()->fetch_assoc()['cnt'];
    $negotiations = getNegotiations($conn, $id);
}

$img = !empty($product['image']) ? 'assets/uploads/' . htmlspecialchars($product['image']) : null;
$placeholder_icons = ['rare'=>'💎','high_end'=>'🪑','regular'=>'🛍️'];
$icon = $placeholder_icons[$product['category']] ?? '📦';

require_once 'templates/header.php';
?>

<div class="container">
    <!-- Breadcrumb -->
    <nav style="margin-bottom:24px;font-size:0.85rem;color:var(--gray-400);">
        <a href="index.php">Home</a> &rsaquo;
        <a href="products.php">Browse All</a> &rsaquo;
        <a href="products.php?cat=<?php echo htmlspecialchars($product['category']); ?>"><?php echo getCategoryLabel($product['category']); ?></a> &rsaquo;
        <span><?php echo htmlspecialchars($product['name']); ?></span>
    </nav>

    <div class="product-detail-grid">

        <!-- Left: Image -->
        <div>
            <div class="product-detail-img">
                <?php if ($img): ?>
                    <img src="<?php echo $img; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                <?php else: ?>
                    <div style="display:flex;align-items:center;justify-content:center;height:100%;font-size:5rem;color:var(--purple-300);">
                        <?php echo $icon; ?>
                    </div>
                <?php endif; ?>
            </div>

            <?php if ($product['video_url']): ?>
            <div style="margin-top:20px;">
                <h4 style="margin-bottom:12px;">Product Video</h4>
                <video controls style="width:100%;border-radius:var(--radius-md);">
                    <source src="<?php echo htmlspecialchars($product['video_url']); ?>">
                </video>
            </div>
            <?php endif; ?>

            <!-- Description -->
            <div style="background:var(--white);border-radius:var(--radius-md);padding:28px;margin-top:24px;box-shadow:var(--shadow-sm);">
                <h3 style="margin-bottom:16px;">Description</h3>
                <p><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>

                <?php if ($product['defects']): ?>
                    <h4 style="margin-top:20px;margin-bottom:8px;color:var(--warning);">Known Defects / Condition Notes</h4>
                    <p><?php echo nl2br(htmlspecialchars($product['defects'])); ?></p>
                <?php endif; ?>

                <div style="margin-top:20px;display:flex;gap:8px;flex-wrap:wrap;">
                    <span class="badge badge-<?php echo str_replace('_','-',$product['category']); ?>">
                        <?php echo getCategoryLabel($product['category']); ?>
                    </span>
                    <span class="badge badge-<?php echo str_replace('_','-',$product['sale_type']); ?>">
                        <?php echo getSaleTypeLabel($product['sale_type']); ?>
                    </span>
                </div>

                <?php if ($product['seller_name']): ?>
                    <p class="text-small text-muted" style="margin-top:16px;">
                        Listed by <strong><?php echo htmlspecialchars($product['seller_name']); ?></strong>
                    </p>
                <?php endif; ?>
            </div>

            <!-- Negotiation thread (if applicable) -->
            <?php if ($product['sale_type'] === 'negotiation' && isLoggedIn() && !empty($negotiations)): ?>
            <div style="background:var(--white);border-radius:var(--radius-md);padding:28px;margin-top:24px;box-shadow:var(--shadow-sm);">
                <h3 style="margin-bottom:16px;">Negotiation History (<?php echo $my_rounds; ?>/5 rounds used)</h3>
                <div class="negotiation-thread">
                    <?php foreach (array_reverse($negotiations) as $neg):
                        if ((int)$neg['buyer_id'] !== (int)$_SESSION['user_id']) continue;
                    ?>
                        <div class="msg-bubble msg-buyer">
                            <div class="msg-label">Your Offer — Round <?php echo (int)$neg['round']; ?></div>
                            <?php echo formatPrice($neg['buyer_offer']); ?>
                            <div style="font-size:0.7rem;opacity:0.7;margin-top:4px;"><?php echo date('d M Y, H:i', strtotime($neg['created_at'])); ?></div>
                        </div>
                        <?php if ($neg['seller_reply']): ?>
                        <div class="msg-bubble msg-seller">
                            <div class="msg-label">Seller's Counter-Offer</div>
                            <?php echo formatPrice($neg['seller_reply']); ?>
                            <span class="badge badge-<?php echo $neg['status']; ?>" style="margin-left:6px;">
                                <?php echo ucfirst($neg['status']); ?>
                            </span>
                        </div>
                        <?php elseif ($neg['status'] === 'pending'): ?>
                        <div class="msg-bubble msg-seller">
                            <div class="msg-label">Seller</div>
                            <em style="color:var(--gray-400);">Awaiting seller response…</em>
                        </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Right: Purchase Panel -->
        <div class="product-detail-side">
            <h1 style="font-size:1.8rem;"><?php echo htmlspecialchars($product['name']); ?></h1>

            <div class="detail-price"><?php echo formatPrice($product['price']); ?></div>

            <div class="detail-meta">
                <span class="badge badge-<?php echo str_replace('_','-',$product['category']); ?>">
                    <?php echo getCategoryLabel($product['category']); ?>
                </span>
                <span class="badge badge-<?php echo str_replace('_','-',$product['sale_type']); ?>">
                    <?php echo getSaleTypeLabel($product['sale_type']); ?>
                </span>
                <?php if ($product['is_sold']): ?>
                    <span class="badge" style="background:var(--danger);color:var(--white);">Sold</span>
                <?php endif; ?>
            </div>

            <?php if ($product['is_sold']): ?>
                <div class="alert alert-warning">This item has been sold.</div>

            <?php elseif (!isLoggedIn()): ?>
                <div style="background:var(--purple-50);border:1.5px solid var(--purple-200);border-radius:var(--radius-md);padding:24px;text-align:center;">
                    <p style="margin-bottom:16px;">You must be logged in to purchase items.</p>
                    <a href="login.php" class="btn btn-primary btn-full">Log In</a>
                    <a href="register.php" class="btn btn-secondary btn-full" style="margin-top:8px;">Create Account</a>
                </div>

            <?php elseif ($product['sale_type'] === 'buy_now'): ?>
                <!-- BUY IT NOW -->
                <div style="background:var(--white);border-radius:var(--radius-md);padding:24px;box-shadow:var(--shadow-sm);">
                    <h4 style="margin-bottom:8px;">⚡ Buy It Now</h4>
                    <p class="text-muted text-small" style="margin-bottom:16px;">
                        Instant purchase at the listed price. No waiting, no bidding.
                    </p>
                    <form method="POST">
                        <input type="hidden" name="action" value="add_to_cart">
                        <button type="submit" class="btn btn-gold btn-full btn-lg">Add to Cart — <?php echo formatPrice($product['price']); ?></button>
                    </form>
                </div>

            <?php elseif ($product['sale_type'] === 'best_offer'): ?>
                <!-- BEST OFFER / AUCTION -->
                <?php
                $auction_open = false;
                if ($product['auction_end']) {
                    $auction_open = new DateTime() < new DateTime($product['auction_end']);
                }
                ?>
                <div style="background:var(--white);border-radius:var(--radius-md);padding:24px;box-shadow:var(--shadow-sm);">
                    <h4 style="margin-bottom:8px;">🔨 Live Auction</h4>

                    <?php if ($product['auction_end']): ?>
                        <div class="auction-timer" data-end="<?php echo htmlspecialchars($product['auction_end']); ?>">
                            <div class="timer-segment"><div class="timer-value t-days">--</div><div class="timer-label">Days</div></div>
                            <div class="timer-sep">:</div>
                            <div class="timer-segment"><div class="timer-value t-hours">--</div><div class="timer-label">Hrs</div></div>
                            <div class="timer-sep">:</div>
                            <div class="timer-segment"><div class="timer-value t-mins">--</div><div class="timer-label">Min</div></div>
                            <div class="timer-sep">:</div>
                            <div class="timer-segment"><div class="timer-value t-secs">--</div><div class="timer-label">Sec</div></div>
                        </div>
                    <?php endif; ?>

                    <div style="margin:16px 0;padding:12px;background:var(--gray-100);border-radius:var(--radius-sm);">
                        <?php if ($highest_bid): ?>
                            <p class="text-small" style="color:var(--danger);font-weight:600;">
                                Current highest bid: <?php echo formatPrice($highest_bid); ?>
                                (<?php echo $bid_count; ?> bid<?php echo $bid_count != 1 ? 's' : ''; ?>)
                            </p>
                        <?php else: ?>
                            <p class="text-small text-muted">No bids yet. Starting price: <?php echo formatPrice($product['price']); ?></p>
                        <?php endif; ?>
                    </div>

                    <?php if ($auction_open): ?>
                    <form method="POST" id="bid-form"
                          data-current-top="<?php echo (float)($highest_bid ?? $product['price']); ?>">
                        <input type="hidden" name="action" value="place_bid">
                        <div class="form-group">
                            <label class="form-label" for="max_bid">Your Maximum Bid (€)</label>
                            <input type="number" id="max_bid" name="max_bid" class="form-control"
                                   step="0.01" min="<?php echo ($highest_bid ?? $product['price']) + 0.01; ?>"
                                   placeholder="e.g. <?php echo (float)($highest_bid ?? $product['price']) + 100; ?>">
                            <p class="text-small text-muted" style="margin-top:4px;">
                                We'll automatically bid on your behalf up to this amount.
                            </p>
                        </div>
                        <button type="submit" class="btn btn-primary btn-full">Place Bid</button>
                    </form>
                    <?php else: ?>
                        <div class="alert alert-warning mb-0">This auction has ended.</div>
                    <?php endif; ?>
                </div>

            <?php elseif ($product['sale_type'] === 'negotiation'): ?>
                <!-- NEGOTIATION -->
                <div style="background:var(--white);border-radius:var(--radius-md);padding:24px;box-shadow:var(--shadow-sm);">
                    <h4 style="margin-bottom:8px;">🤝 Negotiate a Price</h4>
                    <p class="text-muted text-small" style="margin-bottom:12px;">
                        You can negotiate up to 5 rounds. You are legally obligated to buy if the seller accepts your offer.
                    </p>

                    <div style="padding:10px 14px;background:var(--gray-100);border-radius:var(--radius-sm);margin-bottom:16px;font-size:0.85rem;">
                        Rounds used: <strong><?php echo $my_rounds; ?></strong> / 5
                    </div>

                    <?php if ($my_rounds >= 5): ?>
                        <div class="alert alert-warning mb-0">You have used all 5 negotiation rounds for this item.</div>
                    <?php else: ?>
                    <form method="POST" id="negotiate-form"
                          data-listed-price="<?php echo (float)$product['price']; ?>">
                        <input type="hidden" name="action" value="negotiate">
                        <div class="form-group">
                            <label class="form-label" for="offer_amount">Your Offer (€)</label>
                            <input type="number" id="offer_amount" name="offer_amount" class="form-control"
                                   step="0.01" min="1"
                                   placeholder="e.g. <?php echo round($product['price'] * 0.8, 0); ?>">
                        </div>
                        <div style="margin-bottom:16px;">
                            <label style="display:flex;align-items:flex-start;gap:10px;cursor:pointer;font-size:0.85rem;color:var(--gray-600);">
                                <input type="checkbox" required style="margin-top:3px;flex-shrink:0;">
                                I understand that if the seller accepts my offer, I am legally obligated to complete the purchase.
                            </label>
                        </div>
                        <button type="submit" class="btn btn-primary btn-full">Send Offer — Round <?php echo $my_rounds + 1; ?>/5</button>
                    </form>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <!-- Seller info -->
            <?php if ($product['seller_name']): ?>
            <div style="margin-top:16px;padding:16px;background:var(--gray-100);border-radius:var(--radius-sm);font-size:0.85rem;">
                🏪 Sold by <strong><?php echo htmlspecialchars($product['seller_name']); ?></strong>
            </div>
            <?php endif; ?>

        </div><!-- /.product-detail-side -->
    </div><!-- /.product-detail-grid -->
</div>

<?php require_once 'templates/footer.php'; ?>
