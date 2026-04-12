<?php
// templates/footer.php — included at the bottom of every page
?>
</main><!-- /.main-content -->

<footer class="site-footer">
    <div class="container">
        <div class="footer-grid">

            <!-- Brand -->
            <div class="footer-brand">
                <a href="index.php" class="site-logo" style="margin-bottom:12px;">
                    <img src="assets/images/OMNES_ICON_PNG.png" alt="Omnes Icon" class="logo-icon">
                    Omnes <span>Market</span>Place
                </a>
                <p>Your curated marketplace for Regular, High-End, and Rare collectibles. Buy it now, negotiate, or bid.</p>
            </div>

            <!-- Shop links -->
            <div>
                <p class="footer-heading">Shop</p>

                <div class="footer-links">
                    <a href="about.php">About Us</a>
                    <a href="products.php">Browse All</a>
                    <a href="products.php?cat=rare">Rare Items</a>
                    <a href="products.php?cat=high_end">High-End</a>
                    <a href="products.php?cat=regular">Regular Items</a>
                    <a href="products.php?sale=best_offer">Live Auctions</a>
                </div>
            </div>

            <!-- Account links -->
            <div>
                <p class="footer-heading">Account</p>
                <div class="footer-links">
                    <?php if (isLoggedIn()): ?>
                        <a href="account.php">My Account</a>
                        <a href="cart.php">Cart</a>
                        <a href="notifications.php">Notifications</a>
                        <a href="includes/logout.php">Log Out</a>
                    <?php else: ?>
                        <a href="login.php">Log In</a>
                        <a href="register.php">Create Account</a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Contact -->
            <div>
                <p class="footer-heading">Contact</p>
                <div class="footer-links">
                    <a href="mailto:contact@omnesmp.com">contact@omnesmp.com</a>
                    <a href="tel:+33123456789">+33 1 23 45 67 89</a>
                    <span style="color:var(--purple-300);font-size:0.875rem;">
                        12 Rue du Commerce<br>75015 Paris, France
                    </span>
                </div>
            </div>

        </div>

        <div class="footer-bottom">
            <span>&copy; <?php echo date('Y'); ?> Omnes MarketPlace. All rights reserved.</span>
            <span>Web Development B1 2026 Project</span>
        </div>
    </div>
</footer>

<script src="assets/scripts/main.js"></script>
<?php if (!empty($extra_scripts)):
    foreach ($extra_scripts as $s): ?>
        <script src="<?php echo htmlspecialchars($s); ?>"></script>
    <?php endforeach; endif; ?>
</body>

</html>