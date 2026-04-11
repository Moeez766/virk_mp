<?php
// templates/header.php — included at the top of every page
// Expects $conn, sessions.php, and functions.php to be already loaded.

// Determine active nav item
$current_page = basename($_SERVER['PHP_SELF']);

$cart_count = 0;
$notif_count = 0;

if (isLoggedIn()) {
    $cart_count = getCartCount($conn, $_SESSION['user_id']);
    $notif_count = getNotificationCount($conn, $_SESSION['user_id']);
}

// Resolve correct path prefix for assets depending on nesting depth
// Pages in root: assets are at assets/
// (No subdirectory pages in this project by design)
$asset = 'assets/';

// Page title — set before including header if needed
if (!isset($page_title))
    $page_title = 'Omnes MarketPlace';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> – Omnes MarketPlace</title>
    <meta name="description" content="Omnes MarketPlace — Buy, negotiate, and bid on unique items from our curated collection.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="<?php echo $asset; ?>styles/main.css">
</head>

<body>

    <header class="site-header" id="site-header">
        <div class="container header-inner">

            <!-- Logo -->
            <a href="index.php" class="site-logo">
                <div class="logo-icon">🛒</div>
                Omnes <span>Market</span>Place
            </a>

            <!-- Primary Navigation -->
            <nav class="primary-nav" aria-label="Main navigation">
                <a href="index.php" class="nav-link <?php echo $current_page === 'index.php' ? 'active' : ''; ?>">
                    Home
                </a>
                <a href="products.php" class="nav-link <?php echo $current_page === 'products.php' ? 'active' : ''; ?>">
                    Browse All
                </a>
                    <?php if (isLoggedIn()): ?>
                    <a href="notifications.php" class="nav-link <?php echo $current_page === 'notifications.php' ? 'active' : ''; ?>">
                        Notifications
                                <?php if ($notif_count > 0): ?>
                            <span class="nav-badge"><?php echo $notif_count; ?></span>
                                <?php endif; ?>
                    </a>
                    <a href="cart.php" class="nav-link <?php echo $current_page === 'cart.php' ? 'active' : ''; ?>">
                        Cart
                                <?php if ($cart_count > 0): ?>
                            <span class="nav-badge"><?php echo $cart_count; ?></span>
                                <?php endif; ?>
                    </a>
                    <a href="account.php" class="nav-link <?php echo $current_page === 'account.php' ? 'active' : ''; ?>">
                                <?php echo htmlspecialchars($_SESSION['username']); ?>
                    </a>
                            <?php if (isAdmin()): ?>
                        <a href="admin.php" class="nav-link <?php echo $current_page === 'admin.php' ? 'active' : ''; ?>">
                            Admin
                        </a>
                            <?php endif; ?>
                            <?php if (isSeller()): ?>
                        <a href="seller.php" class="nav-link <?php echo $current_page === 'seller.php' ? 'active' : ''; ?>">
                            My Listings
                        </a>
                            <?php endif; ?>
                    <?php endif; ?>
            </nav>

            <!-- Auth buttons or logout -->
            <div class="header-auth">
                    <?php if (isLoggedIn()): ?>
                    <a href="includes/logout.php" class="btn-header-login">Log Out</a>
                    <?php else: ?>
                    <a href="login.php" class="btn-header-login">Log In</a>
                    <a href="register.php" class="btn-header-register">Register</a>
                    <?php endif; ?>
            </div>

            <!-- Mobile hamburger -->
            <button class="hamburger" id="hamburger" aria-label="Toggle menu" aria-expanded="false">
                <span></span>
                <span></span>
                <span></span>
            </button>

        </div>
    </header>

    <!-- Flash message -->
    <?php
    $flash = getFlash();
    if ($flash):
        ?>
        <div class="container" style="padding-top:16px;">
            <div class="alert alert-<?php echo htmlspecialchars($flash['type']); ?>" data-auto-dismiss>
                        <?php echo htmlspecialchars($flash['message']); ?>
            </div>
        </div>
        <?php endif; ?>

    <main class="main-content">