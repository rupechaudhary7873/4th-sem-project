<?php
require_once __DIR__ . '/../config/database.php';

$pdo->exec("CREATE TABLE IF NOT EXISTS admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

if (isset($_SESSION['admin_id'])) {
    header('Location: ' . BASE_URL . '/admin/dashboard');
    exit();
}

$stmt = $pdo->query("SELECT COUNT(*) FROM admins");
if ($stmt->fetchColumn() == 0) {
    $hash = password_hash('admin123', PASSWORD_DEFAULT);
    $pdo->prepare("INSERT INTO admins (full_name, username, email, password) VALUES (?, ?, ?, ?)")
        ->execute(['Administrator', 'admin', 'admin@example.com', $hash]);
    $first_run = true;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if (empty($username) || empty($password)) {
        $error = 'Please enter username and password.';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ? OR email = ?");
        $stmt->execute([$username, $username]);
        $admin = $stmt->fetch();

        if ($admin && password_verify($password, $admin['password'])) {
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_username'] = $admin['username'];
            $_SESSION['admin_name'] = $admin['full_name'];

            $link = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ? ORDER BY id ASC LIMIT 1");
            $link->execute([$admin['username'], $admin['email']]);
            $linked_user = $link->fetch();
            if ($linked_user) {
                $_SESSION['user_id'] = (int)$linked_user['id'];
            }

            header('Location: ' . BASE_URL . '/admin/dashboard');
            exit();
        } else {
            $error = 'Invalid admin credentials.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Budget Manager</title>
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
                <span class="material-symbols-outlined">admin_panel_settings</span>
            </div>
            <h1 class="auth-title">Admin Panel</h1>
            <p class="auth-subtitle">Authorized personnel only</p>
        </div>

        <?php if (isset($first_run) && $first_run): ?>
        <div class="alert alert-info">
            Default admin account created.<br>
            <strong>Username:</strong> admin<br>
            <strong>Password:</strong> admin123
        </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="username">Username / Email</label>
                <div class="input-icon">
                    <span class="material-symbols-outlined">person</span>
                    <input type="text" id="username" name="username" placeholder="admin@budgetmanager.com" required>
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

            <button type="submit" class="btn btn-primary btn-lg">
                Login
                <span class="material-symbols-outlined">arrow_forward</span>
            </button>
        </form>

        <div class="auth-footer">
            <a href="<?php echo BASE_URL; ?>/login" style="display:inline-flex; align-items:center; gap:6px; font-size:14px;">
                <span class="material-symbols-outlined" style="font-size:16px;">arrow_back</span>
                Back to User Login
            </a>
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
