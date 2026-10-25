<?php
require_once 'config/database.php';

if (isLoggedIn()) {
    header('Location: ' . BASE_URL . '/dashboard');
    exit();
}

$error = '';
$success = '';

$default_categories_list = [
    ['Salary', 'income'],
    ['Freelance', 'income'],
    ['Investment', 'income'],
    ['Business', 'income'],
    ['Other Income', 'income'],
    ['Food & Dining', 'expense'],
    ['Transportation', 'expense'],
    ['Shopping', 'expense'],
    ['Entertainment', 'expense'],
    ['Bills & Utilities', 'expense'],
    ['Rent', 'expense'],
    ['Healthcare', 'expense'],
    ['Education', 'expense'],
    ['Other Expense', 'expense'],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username']);
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = 'All fields are required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match';
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);

        if ($stmt->fetch()) {
            $error = 'Username or email already exists';
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
            if ($stmt->execute([$username, $email, $hashed_password])) {
                $user_id = $pdo->lastInsertId();

                $insert_cat = $pdo->prepare("INSERT INTO categories (user_id, name, type) VALUES (?, ?, ?)");
                foreach ($default_categories_list as $cat) {
                    $insert_cat->execute([$user_id, $cat[0], $cat[1]]);
                }

                $success = 'Registration successful! You can now login.';
            } else {
                $error = 'Registration failed. Please try again.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Budget Manager</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
</head>
<body class="auth-body">
    <main class="auth-card">
        <div class="auth-header">
            <div class="auth-logo">
                <span class="material-symbols-outlined">account_balance_wallet</span>
            </div>
            <h1 class="auth-title">Budget Manager</h1>
            <p class="auth-subtitle">Create your account</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <form id="registerForm" method="POST" action="" onsubmit="return validateForm('registerForm')">
            <div class="form-group">
                <label for="username">Username</label>
                <div class="input-icon">
                    <span class="material-symbols-outlined">person</span>
                    <input type="text" id="username" name="username" placeholder="e.g. alexsmith" required>
                </div>
            </div>
            <div class="form-group">
                <label for="email">Email</label>
                <div class="input-icon">
                    <span class="material-symbols-outlined">mail</span>
                    <input type="email" id="email" name="email" placeholder="alex@example.com" required onblur="validateEmail(this)">
                </div>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <div class="input-icon">
                    <span class="material-symbols-outlined">lock</span>
                    <input type="password" id="password" name="password" placeholder="Minimum 6 characters" required minlength="6">
                </div>
            </div>
            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <div class="input-icon">
                    <span class="material-symbols-outlined">lock_reset</span>
                    <input type="password" id="confirm_password" name="confirm_password" placeholder="Re-enter your password" required onblur="validatePasswordMatch('password', 'confirm_password')">
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-lg">
                Register
                <span class="material-symbols-outlined">arrow_forward</span>
            </button>
        </form>

        <div class="auth-links">
            Already have an account? <a href="<?php echo BASE_URL; ?>/login">Login here</a>
        </div>
    </main>
    <script src="<?php echo BASE_URL; ?>/assets/js/script.js"></script>
</body>
</html>
