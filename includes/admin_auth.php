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
    $stmt = $pdo->prepare("SELECT * FROM admins WHERE id = ?");
    $stmt->execute([$_SESSION['admin_id']]);
    $admin = $stmt->fetch();

    if (!$admin) {
        unset($_SESSION['admin_id'], $_SESSION['admin_username'], $_SESSION['admin_name']);
        header('Location: ' . BASE_URL . '/admin/login');
        exit();
    }
} elseif (isLoggedIn()) {
    $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ?");
    $stmt->execute([$_SESSION['username']]);
    $admin = $stmt->fetch();

    if (!$admin) {
        require_once __DIR__ . '/auth.php';
        if ($user && $user['role'] === 'admin') {
            $pdo->prepare("INSERT IGNORE INTO admins (full_name, username, email, password) VALUES (?, ?, ?, ?)")
                ->execute([$user['username'], $user['username'], $user['email'], $user['password']]);
            $admin_id = $pdo->lastInsertId();
            if ($admin_id) {
                $admin = ['id' => $admin_id, 'full_name' => $user['username'], 'username' => $user['username']];
            }
        }
    }

    if ($admin) {
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_username'] = $admin['username'];
        $_SESSION['admin_name'] = $admin['full_name'];
    } else {
        header('Location: ' . BASE_URL . '/admin/login');
        exit();
    }
} else {
    header('Location: ' . BASE_URL . '/admin/login');
    exit();
}
