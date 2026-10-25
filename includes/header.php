<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Budget Manager - <?php echo $page_title ?? 'Dashboard'; ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
    <?php if (!empty($load_charts)): ?>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
    <?php endif; ?>
    <script src="https://kit.fontawesome.com/d18ac973c7.js" crossorigin="anonymous"></script>
</head>
<body>
    <?php if (isLoggedIn() || isset($_SESSION['admin_id'])): ?>
    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-brand">
                <a href="<?php echo isset($_SESSION['admin_id']) ? BASE_URL . '/admin/dashboard' : BASE_URL . '/dashboard'; ?>">Budget Manager</a>
            </div>
            <ul class="nav-menu" id="navMenu">
                <?php if (isset($_SESSION['admin_id'])): ?>
                <li><a class="<?php echo ($active_nav ?? '') === 'dashboard' ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/admin/dashboard">Dashboard</a></li>
                <li><a class="<?php echo ($active_nav ?? '') === 'users' ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/admin/users">Users</a></li>
                <li><a class="<?php echo ($active_nav ?? '') === 'categories' ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/admin/categories">Categories</a></li>
                <?php else: ?>
                <li><a class="<?php echo ($active_nav ?? '') === 'dashboard' ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/dashboard">Dashboard</a></li>
                <li><a class="<?php echo ($active_nav ?? '') === 'income' ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/income">Income</a></li>
                <li><a class="<?php echo ($active_nav ?? '') === 'expenses' ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/expenses">Expenses</a></li>
                <li><a class="<?php echo ($active_nav ?? '') === 'budget' ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/budget">Budgets</a></li>
                <li><a class="<?php echo ($active_nav ?? '') === 'reports' ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/reports">Reports</a></li>
                <?php endif; ?>
                <li class="nav-menu-sep" aria-hidden="true"></li>
                <?php if (!isset($_SESSION['admin_id'])): ?>
                <li><a class="nav-action-item <?php echo ($active_nav ?? '') === 'profile' ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/profile">
                    <span class="material-symbols-outlined">person</span>
                    <span>Profile</span>
                </a></li>
                <?php endif; ?>
                <li><a class="nav-action-item" href="<?php echo BASE_URL; ?>/logout">
                    <span class="material-symbols-outlined">logout</span>
                    <span>Logout</span>
                </a></li>
            </ul>
            <div class="hamburger" id="hamburger">
                <span></span>
                <span></span>
                <span></span>
            </div>
        </div>
    </nav>
    <?php endif; ?>
    <div class="container">
