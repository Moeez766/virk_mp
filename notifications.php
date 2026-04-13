<?php
// notifications.php
require_once 'includes/sessions.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

requireLogin();
$page_title = 'Notifications';

// Mark all as read
$conn->query("UPDATE notifications SET is_read = 1 WHERE user_id = " . (int) $_SESSION['user_id']);

// Fetch all notifications
$stmt = $conn->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC");
$stmt->bind_param('i', $_SESSION['user_id']);
$stmt->execute();
$notifs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Handle save notification preference
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_pref') {
    $keyword = sanitize($_POST['keyword'] ?? '');
    $category = sanitize($_POST['category'] ?? '');
    $sale_type = sanitize($_POST['sale_type'] ?? '');
    $max_price = (float) ($_POST['max_price'] ?? 0);

    $allowed_cats = ['', 'rare', 'high_end', 'regular'];
    $allowed_sales = ['', 'buy_now', 'negotiation', 'best_offer'];
    if (!in_array($category, $allowed_cats))
        $category = '';
    if (!in_array($sale_type, $allowed_sales))
        $sale_type = '';

    $cat_val = $category ?: null;
    $sale_val = $sale_type ?: null;
    $max_val = $max_price > 0 ? $max_price : null;

    $stmt2 = $conn->prepare(
        "INSERT INTO notification_prefs (user_id, keyword, category, max_price, sale_type) VALUES (?,?,?,?,?)"
    );
    $stmt2->bind_param('issds', $_SESSION['user_id'], $keyword, $cat_val, $max_val, $sale_val);
    $stmt2->execute();
    setFlash('success', 'Notification preference saved!');
    header("Location: notifications.php");
    exit();
}

// Fetch current preferences
$stmt3 = $conn->prepare("SELECT * FROM notification_prefs WHERE user_id = ? ORDER BY created_at DESC");
$stmt3->bind_param('i', $_SESSION['user_id']);
$stmt3->execute();
$prefs = $stmt3->get_result()->fetch_all(MYSQLI_ASSOC);

require_once 'templates/header.php';
?>

<div class="page-banner">
    <div class="container">
        <h1>Notifications</h1>
        <p>Stay updated on bids, negotiations, and new arrivals</p>
    </div>
</div>

<div class="container">
    <div style="display:grid;grid-template-columns:1fr 360px;gap:32px;align-items:start;">

        <!-- Notification feed -->
        <div>
            <h3 style="margin-bottom:20px;">Recent Notifications</h3>

            <?php if (empty($notifs)): ?>
                <div class="card card-body text-center">
                    <div style="font-size:2.5rem;margin-bottom:12px;">🔔</div>
                    <p class="text-muted">No notifications yet.</p>
                </div>
            <?php else: ?>
                <?php foreach ($notifs as $n): ?>
                    <div style="display:flex;gap:14px;align-items:flex-start;background:var(--white);padding:18px 20px;border-radius:var(--radius-md);box-shadow:var(--shadow-sm);margin-bottom:12px;">
                        <div style="width:40px;height:40px;border-radius:50%;background:var(--purple-100);display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:1.2rem;">
                            🔔
                        </div>
                        <div style="flex:1;">
                            <p style="margin-bottom:4px;font-size:0.9rem;"><?php echo htmlspecialchars($n['message']); ?></p>
                            <div class="text-small text-muted"><?php echo date('d M Y, H:i', strtotime($n['created_at'])); ?></div>
                        </div>
                        <?php if ($n['link']): ?>
                            <a href="<?php echo htmlspecialchars($n['link']); ?>" class="btn btn-sm btn-secondary">View</a>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Alert preferences -->
        <div>
            <div class="card card-body" style="margin-bottom:24px;">
                <h4 style="margin-bottom:16px;">🔍 Set Up Item Alerts</h4>
                <p class="text-small text-muted" style="margin-bottom:16px;">
                    Get notified when a new item matching your criteria is listed.
                </p>

                <form method="POST" novalidate>
                    <input type="hidden" name="action" value="save_pref">

                    <div class="form-group">
                        <label class="form-label">Keyword (optional)</label>
                        <input type="text" name="keyword" class="form-control" placeholder="e.g. vase, rug, lamp">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Category</label>
                        <select name="category" class="form-control">
                            <option value="">Any category</option>
                            <option value="rare">✦ Rare</option>
                            <option value="high_end">◈ High-End</option>
                            <option value="regular">● Regular</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Sale Type</label>
                        <select name="sale_type" class="form-control">
                            <option value="">Any sale type</option>
                            <option value="buy_now">Buy It Now</option>
                            <option value="negotiation">Negotiation</option>
                            <option value="best_offer">Best Offer (Auction)</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Max Price (€)</label>
                        <input type="number" name="max_price" class="form-control" step="0.01" min="0" placeholder="Leave blank for any price">
                    </div>

                    <button type="submit" class="btn btn-primary btn-full">Save Alert</button>
                </form>
            </div>

            <!-- Existing preferences -->
            <?php if (!empty($prefs)): ?>
                <div class="card card-body">
                    <h4 style="margin-bottom:16px;">Your Active Alerts</h4>
                    <?php foreach ($prefs as $pref): ?>
                        <div style="padding:10px 0;border-bottom:1px solid var(--gray-100);font-size:0.85rem;">
                            <?php if ($pref['keyword']): ?>
                                <span>🔍 "<?php echo htmlspecialchars($pref['keyword']); ?>"</span>
                            <?php endif; ?>
                            <?php if ($pref['category']): ?>
                                <span class="badge badge-<?php echo str_replace('_', '-', $pref['category']); ?>" style="margin-left:4px;">
                                    <?php echo getCategoryLabel($pref['category']); ?>
                                </span>
                            <?php endif; ?>
                            <?php if ($pref['sale_type']): ?>
                                <span class="badge badge-<?php echo str_replace('_', '-', $pref['sale_type']); ?>" style="margin-left:4px;">
                                    <?php echo getSaleTypeLabel($pref['sale_type']); ?>
                                </span>
                            <?php endif; ?>
                            <?php if ($pref['max_price']): ?>
                                <span class="text-muted"> · max <?php echo formatPrice($pref['max_price']); ?></span>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

    </div>
</div>

<?php require_once 'templates/footer.php'; ?>