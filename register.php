<?php
// register.php
require_once 'includes/sessions.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

if (isLoggedIn()) { header("Location: index.php"); exit(); }

$errors = [];
$data   = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data['username']   = sanitize($_POST['username']   ?? '');
    $data['email']      = sanitize($_POST['email']      ?? '');
    $data['first_name'] = sanitize($_POST['first_name'] ?? '');
    $data['last_name']  = sanitize($_POST['last_name']  ?? '');
    $password           = $_POST['password']  ?? '';
    $confirm            = $_POST['confirm']   ?? '';

    // Validate
    if (empty($data['username']))   $errors[] = 'Username is required.';
    if (empty($data['email']))      $errors[] = 'Email is required.';
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Please enter a valid email.';
    if (empty($data['first_name'])) $errors[] = 'First name is required.';
    if (empty($data['last_name']))  $errors[] = 'Last name is required.';
    if (strlen($password) < 6)     $errors[] = 'Password must be at least 6 characters.';
    if ($password !== $confirm)    $errors[] = 'Passwords do not match.';

    if (empty($errors)) {
        // Check unique username/email
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
        $stmt->bind_param('ss', $data['email'], $data['username']);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $errors[] = 'That email or username is already registered. Please log in.';
        }
    }

    if (empty($errors)) {
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $conn->prepare(
            "INSERT INTO users (username, email, password_hash, role, first_name, last_name) VALUES (?,?,?,'buyer',?,?)"
        );
        $stmt->bind_param('sssss', $data['username'], $data['email'], $hash, $data['first_name'], $data['last_name']);

        if ($stmt->execute()) {
            $user_id = $conn->insert_id;
            $_SESSION['user_id']  = $user_id;
            $_SESSION['username'] = $data['username'];
            $_SESSION['role']     = 'buyer';
            $_SESSION['email']    = $data['email'];
            setFlash('success', 'Account created! Welcome to Omnes MarketPlace, ' . $data['first_name'] . '!');
            header("Location: index.php");
            exit();
        } else {
            $errors[] = 'Registration failed. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account – Omnes MarketPlace</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="assets/styles/main.css">
</head>
<body>

<div class="auth-wrapper">
    <div class="auth-card" style="max-width:560px;">
        <div class="auth-logo">
            <a href="index.php" class="site-logo" style="justify-content:center;">
                <div class="logo-icon">🛒</div>
                Omnes <span>Market</span>Place
            </a>
            <h2>Create Account</h2>
            <p>Join as a buyer — free, no credit card required</p>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <?php foreach ($errors as $e): ?>
                    <div><?php echo htmlspecialchars($e); ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="POST" novalidate>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="first_name">First Name</label>
                    <input type="text" id="first_name" name="first_name" class="form-control"
                           value="<?php echo htmlspecialchars($data['first_name'] ?? ''); ?>"
                           placeholder="Marie" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="last_name">Last Name</label>
                    <input type="text" id="last_name" name="last_name" class="form-control"
                           value="<?php echo htmlspecialchars($data['last_name'] ?? ''); ?>"
                           placeholder="Dupont" required>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="username">Username</label>
                <input type="text" id="username" name="username" class="form-control"
                       value="<?php echo htmlspecialchars($data['username'] ?? ''); ?>"
                       placeholder="marie_dupont" required>
            </div>

            <div class="form-group">
                <label class="form-label" for="email">Email Address</label>
                <input type="email" id="email" name="email" class="form-control"
                       value="<?php echo htmlspecialchars($data['email'] ?? ''); ?>"
                       placeholder="marie@example.com" required>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="password">Password</label>
                    <input type="password" id="password" name="password" class="form-control"
                           placeholder="Min. 6 characters" required minlength="6">
                </div>
                <div class="form-group">
                    <label class="form-label" for="confirm">Confirm Password</label>
                    <input type="password" id="confirm" name="confirm" class="form-control"
                           placeholder="Repeat password" required>
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-full btn-lg">Create Account</button>
        </form>

        <div class="auth-divider"><span>Already have an account?</span></div>
        <a href="login.php" class="btn btn-secondary btn-full">Log In</a>
        <div style="text-align:center;margin-top:16px;">
            <a href="index.php" class="text-small text-muted">← Back to Home</a>
        </div>
    </div>
</div>

<script src="assets/scripts/main.js"></script>
</body>
</html>
