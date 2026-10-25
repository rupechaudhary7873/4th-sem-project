<?php
require_once 'config/database.php';
unset($_SESSION['admin_id'], $_SESSION['admin_username'], $_SESSION['admin_name']);
session_destroy();
header('Location: ' . BASE_URL . '/login');
exit();
