<?php
require_once __DIR__ . '/../config/database.php';

if (!isLoggedIn()) {
    header('Location: ' . BASE_URL . '/login');
    exit();
}

$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    session_destroy();
    header('Location: ' . BASE_URL . '/login');
    exit();
}
