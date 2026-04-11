<?php
// index.php — Homepage
require_once 'includes/sessions.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

$page_title = 'Home';

// Fetch selections: 3 from each category for homepage showcase
$rare_items    = getProducts($conn, 'rare',    null, 3);
$highend_items = getProducts($conn, 'high_end', null, 3);
$regular_items = getProducts($conn, 'regular',  null, 6);
$live_auctions = getActiveAuctions($conn);
array_splice($live_auctions, 3); // show max 3 on homepage

require_once 'templates/header.php';
?>

<!-- ── HERO ─────────────────────────────────────────────────── -->
<section class="hero">
    <div class="container">
        <div class="hero-content">
            <div class="hero-eyebrow">✦ Selection of the Day</div>
            <h1>
                Discover <em>Extraordinary</em><br>
                Items for Every Budget
            </h1>
            <p>
                From everyday essentials to rare collector's pieces — shop, negotiate,
                or place your best offer on Omnes MarketPlace.
            </p>
            <div class="hero-cta">
                <a href="products.php" class="btn btn-gold btn-lg">Browse All Items</a>
                <a href="products.php?sale=best_offer" class="btn btn-secondary btn-lg"
                   style="color:var(--white);border-color:rgba(255,255,255,0.3);">
                    Live Auctions
                </a>
            </div>
        </div>
    </div>
</section>

<!-- ── CATEGORY QUICK-LINKS ──────────────────────────────────── -->
<section style="background:var(--white);padding:32px 0;border-bottom:1px solid var(--gray-200);">
    <div class="container d-flex gap-16 flex-wrap align-center justify-between">
        <div style="display:flex;gap:12px;flex-wrap:wrap;">
            <a href="products.php?cat=rare"     class="btn btn-sm" style="background:var(--purple-800);color:var(--gold-light);">✦ Rare Items</a>
            <a href="products.php?cat=high_end" class="btn btn-sm btn-primary">◈ High-End</a>
            <a href="products.php?cat=regular"  class="btn btn-sm btn-secondary">● Regular</a>
        </div>
        <div style="display:flex;gap:12px;flex-wrap:wrap;">
            <a href="products.php?sale=best_offer"  class="btn btn-sm" style="background:var(--danger);color:var(--white);">🔨 Live Auctions</a>
            <a href="products.php?sale=negotiation" class="btn btn-sm" style="background:var(--warning);color:var(--purple-900);">🤝 Negotiate</a>
            <a href="products.php?sale=buy_now"     class="btn btn-sm" style="background:var(--success);color:var(--white);">⚡ Buy It Now</a>
        </div>
    </div>
</section>

<div class="container">

    <!-- ── LIVE AUCTIONS ──────────────────────────────────────── -->
    <?php if (!empty($live_auctions)): ?>
    <section style="margin-top:60px;">
        <div class="section-title">
            <div>
                <p class="section-label">🔨 Best Offer</p>
                <h2>Live Auctions</h2>
            </div>
            <div class="section-title-line"></div>
            <a href="products.php?sale=best_offer" class="btn btn-sm btn-secondary" style="white-space:nowrap;">View All</a>
        </div>

        <div class="products-grid">
            <?php foreach ($live_auctions as $item):
                $img = !empty($item['image']) ? 'assets/uploads/' . htmlspecialchars($item['image']) : null;
            ?>
            <div class="card product-card product-card-wrap"
                 data-category="<?php echo htmlspecialchars($item['category']); ?>"
                 data-sale-type="<?php echo htmlspecialchars($item['sale_type']); ?>">

                <div class="product-card-img">
                    <?php if ($img): ?>
                        <img src="<?php echo $img; ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" loading="lazy">
                    <?php else: ?>
                        <div class="placeholder-icon">🏺</div>
                    <?php endif; ?>
                </div>

                <div class="card-body">
                    <div class="product-meta">
                        <span class="badge badge-rare"><?php echo getCategoryLabel($item['category']); ?></span>
                        <span class="badge badge-best-offer">Live Auction</span>
                    </div>
                    <h3 class="product-name"><?php echo htmlspecialchars($item['name']); ?></h3>
                    <p class="product-description"><?php echo truncateText(htmlspecialchars($item['description'])); ?></p>
                    <div class="product-price"><?php echo formatPrice($item['price']); ?> <span class="text-small text-muted">starting</span></div>

                    <?php if ($item['highest_bid']): ?>
                        <p class="text-small" style="color:var(--danger);font-weight:600;margin-bottom:8px;">
                            Top bid: <?php echo formatPrice($item['highest_bid']); ?>
                            (<?php echo (int)$item['bid_count']; ?> bid<?php echo $item['bid_count'] != 1 ? 's' : ''; ?>)
                        </p>
                    <?php endif; ?>

                    <!-- Countdown timer -->
                    <?php if ($item['auction_end']): ?>
                    <div class="auction-timer" data-end="<?php echo htmlspecialchars($item['auction_end']); ?>">
                        <div class="timer-segment"><div class="timer-value t-days">--</div><div class="timer-label">Days</div></div>
                        <div class="timer-sep">:</div>
                        <div class="timer-segment"><div class="timer-value t-hours">--</div><div class="timer-label">Hrs</div></div>
                        <div class="timer-sep">:</div>
                        <div class="timer-segment"><div class="timer-value t-mins">--</div><div class="timer-label">Min</div></div>
                        <div class="timer-sep">:</div>
                        <div class="timer-segment"><div class="timer-value t-secs">--</div><div class="timer-label">Sec</div></div>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="card-footer">
                    <a href="product.php?id=<?php echo (int)$item['id']; ?>" class="btn btn-primary btn-full btn-sm">
                        Place a Bid
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- ── RARE ITEMS ─────────────────────────────────────────── -->
    <?php if (!empty($rare_items)): ?>
    <section style="margin-top:60px;">
        <div class="section-title">
            <div>
                <p class="section-label">Collectors & Antiques</p>
                <h2>Rare Items</h2>
            </div>
            <div class="section-title-line"></div>
            <a href="products.php?cat=rare" class="btn btn-sm btn-secondary" style="white-space:nowrap;">View All</a>
        </div>
        <div class="products-grid">
            <?php foreach ($rare_items as $item):
                $img = !empty($item['image']) ? 'assets/uploads/' . htmlspecialchars($item['image']) : null;
            ?>
            <div class="card product-card">
                <div class="product-card-img">
                    <?php if ($img): ?>
                        <img src="<?php echo $img; ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" loading="lazy">
                    <?php else: ?>
                        <div class="placeholder-icon">💎</div>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <div class="product-meta">
                        <span class="badge badge-rare"><?php echo getCategoryLabel($item['category']); ?></span>
                        <span class="badge badge-<?php echo str_replace('_','-',$item['sale_type']); ?>">
                            <?php echo getSaleTypeLabel($item['sale_type']); ?>
                        </span>
                    </div>
                    <h3 class="product-name"><?php echo htmlspecialchars($item['name']); ?></h3>
                    <p class="product-description"><?php echo truncateText(htmlspecialchars($item['description'])); ?></p>
                    <div class="product-price"><?php echo formatPrice($item['price']); ?></div>
                </div>
                <div class="card-footer">
                    <a href="product.php?id=<?php echo (int)$item['id']; ?>" class="btn btn-primary btn-full btn-sm">View Item</a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- ── HIGH-END ITEMS ────────────────────────────────────── -->
    <?php if (!empty($highend_items)): ?>
    <section style="margin-top:60px;">
        <div class="section-title">
            <div>
                <p class="section-label">Premium Artisanal</p>
                <h2>High-End Selection</h2>
            </div>
            <div class="section-title-line"></div>
            <a href="products.php?cat=high_end" class="btn btn-sm btn-secondary" style="white-space:nowrap;">View All</a>
        </div>
        <div class="products-grid">
            <?php foreach ($highend_items as $item):
                $img = !empty($item['image']) ? 'assets/uploads/' . htmlspecialchars($item['image']) : null;
            ?>
            <div class="card product-card">
                <div class="product-card-img">
                    <?php if ($img): ?>
                        <img src="<?php echo $img; ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" loading="lazy">
                    <?php else: ?>
                        <div class="placeholder-icon">🪑</div>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <div class="product-meta">
                        <span class="badge badge-high-end"><?php echo getCategoryLabel($item['category']); ?></span>
                        <span class="badge badge-<?php echo str_replace('_','-',$item['sale_type']); ?>">
                            <?php echo getSaleTypeLabel($item['sale_type']); ?>
                        </span>
                    </div>
                    <h3 class="product-name"><?php echo htmlspecialchars($item['name']); ?></h3>
                    <p class="product-description"><?php echo truncateText(htmlspecialchars($item['description'])); ?></p>
                    <div class="product-price"><?php echo formatPrice($item['price']); ?></div>
                </div>
                <div class="card-footer">
                    <a href="product.php?id=<?php echo (int)$item['id']; ?>" class="btn btn-primary btn-full btn-sm">View Item</a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- ── REGULAR ITEMS ─────────────────────────────────────── -->
    <?php if (!empty($regular_items)): ?>
    <section style="margin-top:60px;margin-bottom:40px;">
        <div class="section-title">
            <div>
                <p class="section-label">Modern Essentials</p>
                <h2>Regular Items</h2>
            </div>
            <div class="section-title-line"></div>
            <a href="products.php?cat=regular" class="btn btn-sm btn-secondary" style="white-space:nowrap;">View All</a>
        </div>
        <div class="products-grid">
            <?php foreach ($regular_items as $item):
                $img = !empty($item['image']) ? 'assets/uploads/' . htmlspecialchars($item['image']) : null;
            ?>
            <div class="card product-card">
                <div class="product-card-img">
                    <?php if ($img): ?>
                        <img src="<?php echo $img; ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" loading="lazy">
                    <?php else: ?>
                        <div class="placeholder-icon">🛍️</div>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <div class="product-meta">
                        <span class="badge badge-regular"><?php echo getCategoryLabel($item['category']); ?></span>
                        <span class="badge badge-buy-now">Buy It Now</span>
                    </div>
                    <h3 class="product-name"><?php echo htmlspecialchars($item['name']); ?></h3>
                    <p class="product-description"><?php echo truncateText(htmlspecialchars($item['description'])); ?></p>
                    <div class="product-price"><?php echo formatPrice($item['price']); ?></div>
                </div>
                <div class="card-footer">
                    <a href="product.php?id=<?php echo (int)$item['id']; ?>" class="btn btn-primary btn-full btn-sm">Buy Now</a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- ── ABOUT / CONTACT STRIP ─────────────────────────────── -->
    <section style="background:var(--purple-900);border-radius:var(--radius-xl);padding:60px 48px;margin-bottom:16px;display:grid;grid-template-columns:1fr 1fr;gap:48px;align-items:center;">
        <div>
            <p class="section-label" style="color:var(--gold);">About Us</p>
            <h2 style="color:var(--white);margin-bottom:16px;">The Omnes MarketPlace Story</h2>
            <p style="color:var(--purple-200);">
                We're a community-driven marketplace created for the Omnes Education family.
                Whether you're hunting for a rare antique or everyday home essentials,
                our platform lets you buy instantly, negotiate fairly, or win through auction.
            </p>
            <div style="margin-top:24px;display:flex;flex-direction:column;gap:8px;color:var(--purple-300);font-size:0.9rem;">
                <span>📧 contact@omnesmp.com</span>
                <span>📞 +33 1 23 45 67 89</span>
                <span>📍 12 Rue du Commerce, 75015 Paris, France</span>
            </div>
        </div>
        <div style="display:flex;flex-direction:column;gap:20px;">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                <div style="background:rgba(255,255,255,0.07);border-radius:var(--radius-md);padding:24px;text-align:center;">
                    <div style="font-size:2rem;font-weight:700;color:var(--gold);font-family:var(--font-display);">45+</div>
                    <div style="color:var(--purple-200);font-size:0.8rem;margin-top:4px;">Items for Sale</div>
                </div>
                <div style="background:rgba(255,255,255,0.07);border-radius:var(--radius-md);padding:24px;text-align:center;">
                    <div style="font-size:2rem;font-weight:700;color:var(--gold);font-family:var(--font-display);">3</div>
                    <div style="color:var(--purple-200);font-size:0.8rem;margin-top:4px;">Sale Methods</div>
                </div>
                <div style="background:rgba(255,255,255,0.07);border-radius:var(--radius-md);padding:24px;text-align:center;">
                    <div style="font-size:2rem;font-weight:700;color:var(--gold);font-family:var(--font-display);">100%</div>
                    <div style="color:var(--purple-200);font-size:0.8rem;margin-top:4px;">Secure Payments</div>
                </div>
                <div style="background:rgba(255,255,255,0.07);border-radius:var(--radius-md);padding:24px;text-align:center;">
                    <div style="font-size:2rem;font-weight:700;color:var(--gold);font-family:var(--font-display);">Live</div>
                    <div style="color:var(--purple-200);font-size:0.8rem;margin-top:4px;">Auctions Daily</div>
                </div>
            </div>
        </div>
    </section>

</div><!-- /.container -->

<?php require_once 'templates/footer.php'; ?>
