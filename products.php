<?php
// products.php — Browse All products with category/sale-type filtering
require_once 'includes/sessions.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

$page_title = 'Browse All';

// Read filter params from URL (server-side fallback)
$cat_filter = isset($_GET['cat']) ? sanitize($_GET['cat']) : null;
$sale_filter = isset($_GET['sale']) ? sanitize($_GET['sale']) : null;

// Validate allowed values
$allowed_cats = ['rare', 'high_end', 'regular'];
$allowed_sales = ['buy_now', 'negotiation', 'best_offer'];

if (!in_array($cat_filter, $allowed_cats))
    $cat_filter = null;
if (!in_array($sale_filter, $allowed_sales))
    $sale_filter = null;

$products = getProducts($conn, $cat_filter, $sale_filter);

require_once 'templates/header.php';
?>

<!-- Page Banner -->
<div class="page-banner">
    <div class="container">
        <h1>Browse All Items</h1>
        <p>Explore our full collection — Rare antiques, High-end pieces, and Regular everyday items.</p>
    </div>
</div>

<div class="container">

    <!-- Filter Bar -->
    <div class="filter-bar">
        <span style="font-size:0.8rem;font-weight:600;color:var(--gray-400);text-transform:uppercase;letter-spacing:0.08em;margin-right:8px;">Category:</span>
        <button class="filter-btn <?php echo !$cat_filter ? 'active' : ''; ?>" data-all>All</button>
        <button class="filter-btn <?php echo $cat_filter === 'rare' ? 'active' : ''; ?>" data-cat="rare">✦ Rare</button>
        <button class="filter-btn <?php echo $cat_filter === 'high_end' ? 'active' : ''; ?>" data-cat="high_end">◈ High-End</button>
        <button class="filter-btn <?php echo $cat_filter === 'regular' ? 'active' : ''; ?>" data-cat="regular">● Regular</button>

        <span style="width:1px;height:24px;background:var(--gray-200);margin:0 8px;"></span>
        <span style="font-size:0.8rem;font-weight:600;color:var(--gray-400);text-transform:uppercase;letter-spacing:0.08em;margin-right:8px;">Sale Type:</span>
        <button class="filter-btn <?php echo $sale_filter === 'buy_now' ? 'active' : ''; ?>" data-sale="buy_now">⚡ Buy It Now</button>
        <button class="filter-btn <?php echo $sale_filter === 'negotiation' ? 'active' : ''; ?>" data-sale="negotiation">🤝 Negotiation</button>
        <button class="filter-btn <?php echo $sale_filter === 'best_offer' ? 'active' : ''; ?>" data-sale="best_offer">🔨 Best Offer</button>
    </div>

    <!-- Results count -->
    <p class="text-muted text-small mb-24">
        Showing <strong><?php echo count($products); ?></strong> item<?php echo count($products) != 1 ? 's' : ''; ?>
        <?php if ($cat_filter || $sale_filter): ?>
            — filtered by
            <?php if ($cat_filter)
                echo '<strong>' . getCategoryLabel($cat_filter) . '</strong> '; ?>
            <?php if ($sale_filter)
                echo '<strong>' . getSaleTypeLabel($sale_filter) . '</strong>'; ?>
        <?php endif; ?>
    </p>

    <!-- Products Grid -->
    <?php if (empty($products)): ?>
        <div style="text-align:center;padding:80px 0;">
            <div style="font-size:3rem;margin-bottom:16px;">🔍</div>
            <h3>No items found</h3>
            <p class="text-muted" style="margin-top:8px;">Try removing some filters to see more results.</p>
            <a href="products.php" class="btn btn-primary" style="margin-top:20px;">Clear Filters</a>
        </div>
    <?php else: ?>
        <div class="products-grid">
            <?php foreach ($products as $item):
                $img = !empty($item['image']) ? 'assets/uploads/' . htmlspecialchars($item['image']) : null;
                $placeholder_icons = ['rare' => '💎', 'high_end' => '🪑', 'regular' => '🛍️'];
                $icon = $placeholder_icons[$item['category']] ?? '📦';
                ?>
                <div class="card product-card product-card-wrap" data-category="<?php echo htmlspecialchars($item['category']); ?>" data-sale-type="<?php echo htmlspecialchars($item['sale_type']); ?>">

                    <div class="product-card-img">
                        <?php if ($img): ?>
                            <img src="<?php echo $img; ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" loading="lazy">
                        <?php else: ?>
                            <div class="placeholder-icon"><?php echo $icon; ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="card-body">
                        <div class="product-meta">
                            <span class="badge badge-<?php echo str_replace('_', '-', $item['category']); ?>">
                                <?php echo getCategoryLabel($item['category']); ?>
                            </span>
                            <span class="badge badge-<?php echo str_replace('_', '-', $item['sale_type']); ?>">
                                <?php echo getSaleTypeLabel($item['sale_type']); ?>
                            </span>
                        </div>

                        <h3 class="product-name"><?php echo htmlspecialchars($item['name']); ?></h3>
                        <p class="product-description"><?php echo truncateText(htmlspecialchars($item['description'])); ?></p>
                        <div class="product-price"><?php echo formatPrice($item['price']); ?></div>

                        <?php if ($item['sale_type'] === 'best_offer' && $item['auction_end']): ?>
                            <div class="auction-timer" data-end="<?php echo htmlspecialchars($item['auction_end']); ?>" style="margin-bottom:0;">
                                <div class="timer-segment">
                                    <div class="timer-value t-days" style="font-size:1.1rem;">--</div>
                                    <div class="timer-label">D</div>
                                </div>
                                <div class="timer-sep" style="font-size:1rem;">:</div>
                                <div class="timer-segment">
                                    <div class="timer-value t-hours" style="font-size:1.1rem;">--</div>
                                    <div class="timer-label">H</div>
                                </div>
                                <div class="timer-sep" style="font-size:1rem;">:</div>
                                <div class="timer-segment">
                                    <div class="timer-value t-mins" style="font-size:1.1rem;">--</div>
                                    <div class="timer-label">M</div>
                                </div>
                                <div class="timer-sep" style="font-size:1rem;">:</div>
                                <div class="timer-segment">
                                    <div class="timer-value t-secs" style="font-size:1.1rem;">--</div>
                                    <div class="timer-label">S</div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="card-footer">
                        <a href="product.php?id=<?php echo (int) $item['id']; ?>" class="btn btn-primary btn-full btn-sm">
                            <?php
                            if ($item['sale_type'] === 'buy_now')
                                echo 'Buy Now';
                            elseif ($item['sale_type'] === 'negotiation')
                                echo 'Make an Offer';
                            else
                                echo 'Place a Bid';
                            ?>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</div>

<?php require_once 'templates/footer.php'; ?>