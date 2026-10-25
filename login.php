<?php
require_once 'config/database.php';

if (isLoggedIn()) {
    header('Location: ' . BASE_URL . '/dashboard');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username']);
    $password = $_POST['password'];

    if (empty($username) || empty($password)) {
        $error = 'Please enter username and password';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            header('Location: ' . BASE_URL . '/dashboard');
            exit();
        } else {
            $error = 'Invalid username or password';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Budget Manager</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
</head>
<body class="auth-body">
    <main class="auth-card auth-card-login">
        <div class="auth-header">
            <h1 class="auth-title auth-title-inline">
                <span class="material-symbols-outlined auth-title-icon">account_balance</span>
                Budget Manager
            </h1>
            <p class="auth-subtitle">Sign in to manage your personal finances</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <form id="loginForm" method="POST" action="" onsubmit="return validateForm('loginForm')">
            <div class="form-group">
                <label for="username">Username or Email</label>
                <div class="input-icon">
                    <span class="material-symbols-outlined">person</span>
                    <input type="text" id="username" name="username" placeholder="Enter your username or email" required>
                </div>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <div class="input-icon has-toggle">
                    <span class="material-symbols-outlined">lock</span>
                    <input type="password" id="password" name="password" placeholder="Enter your password" required>
                    <button type="button" class="input-toggle-btn" onclick="togglePasswordVisibility()">
                        <span class="material-symbols-outlined" id="password-toggle-icon">visibility_off</span>
                    </button>
                </div>
            </div>

            <div class="auth-actions">
                <label class="checkbox-label">
                    <input type="checkbox" name="remember_me" id="remember_me">
                    Remember me
                </label>
                <a href="#" class="auth-forgot">Forgot password?</a>
            </div>

            <button type="submit" class="btn btn-primary btn-lg">Login
            </button>
        </form>

        <div class="auth-links">
            Don't have an account? <a href="<?php echo BASE_URL; ?>/register">Register here</a>
        </div>
    </main>
       <script>
        function togglePasswordVisibility() {
            const input = document.getElementById('password');
            const icon = document.getElementById('password-toggle-icon');
            if (input.type === 'password') {
                input.type = 'text';
                icon.textContent = 'visibility';
            } else {
                input.type = 'password';
                icon.textContent = 'visibility_off';
            }
        }
    </script>
    <script src="<?php echo BASE_URL; ?>/assets/js/script.js"></script>
</body>
</html>
