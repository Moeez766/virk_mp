<?php
// login.php
require_once 'includes/sessions.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header("Location: index.php");
    exit();
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email))
        $errors[] = 'Email is required.';
    if (empty($password))
        $errors[] = 'Password is required.';

    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        // NOTE: In this demo, passwords are stored as bcrypt hashes.
        // The seed data uses a known hash; for a real system use password_hash() on registration.
        // For demo purposes, we also allow plain-text match for ease of testing.
        if ($user && (password_verify($password, $user['password_hash']) || $password === $user['password_hash'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['email'] = $user['email'];

            setFlash('success', 'Welcome back, ' . $user['first_name'] . '!');

            // Redirect based on role
            if ($user['role'] === 'admin') {
                header("Location: admin.php");
                exit();
            }
            if ($user['role'] === 'seller') {
                header("Location: seller.php");
                exit();
            }
            header("Location: index.php");
            exit();
        } else {
            $errors[] = 'Invalid email or password.';
        }
    }
}

$page_title = 'Log In';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log In – Omnes MarketPlace</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="assets/styles/main.css">
</head>

<body>

    <div class="auth-wrapper">
        <div class="auth-card">
            <div class="auth-logo">
                <a href="index.php" class="site-logo" style="justify-content:center;">
                    <img src="http://localhost/omnes_mp/assets/images/OMNES_ICON_PNG.png" alt="Omnes Icon" class="logo-icon">
                    <!-- <div class="logo-icon">🛒</div> -->
                    Omnes <span>Market</span>Place
                </a>
                <h2>Welcome Back</h2>
                <p>Sign in to your account to continue</p>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <?php foreach ($errors as $e): ?>
                        <div><?php echo htmlspecialchars($e); ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form method="POST" novalidate>
                <div class="form-group">
                    <label class="form-label" for="email">Email Address</label>
                    <input type="email" id="email" name="email" class="form-control" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" placeholder="you@example.com" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="password">
                        Password
                        <button type="button" class="toggle-password" data-target="password" style="float:right;background:none;border:none;color:var(--purple-500);font-size:0.8rem;cursor:pointer;">
                            👁 Show
                        </button>
                    </label>
                    <input type="password" id="password" name="password" class="form-control" placeholder="••••••••" required>
                </div>

                <button type="submit" class="btn btn-primary btn-full btn-lg">Log In</button>
            </form>

            <!-- Demo credentials hint
            <div style="margin-top:24px;padding:14px;background:var(--purple-50);border-radius:var(--radius-sm);font-size:0.8rem;color:var(--gray-600);">
                <strong>Demo Credentials:</strong><br>
                Admin: admin@omnesmp.com / Admin@1234<br>
                Seller: seller@omnesmp.com / Seller@1234<br>
                Buyer: buyer@omnesmp.com / Buyer@1234
            </div> -->

            <div class="auth-divider"><span>New to Omnes?</span></div>

            <a href="register.php" class="btn btn-secondary btn-full">Create an Account</a>
            <div style="text-align:center;margin-top:16px;">
                <a href="index.php" class="text-small text-muted">← Back to Home</a>
            </div>
        </div>
    </div>

    <script src="assets/scripts/main.js"></script>
</body>

</html>