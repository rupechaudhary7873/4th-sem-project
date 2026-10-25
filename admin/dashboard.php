<?php
require_once '../includes/admin_auth.php';
$page_title = 'Admin Dashboard';
$active_nav = 'dashboard';

$total_users = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$total_income = $pdo->query("SELECT COALESCE(SUM(amount), 0) FROM income")->fetchColumn();
$total_expenses = $pdo->query("SELECT COALESCE(SUM(amount), 0) FROM expenses")->fetchColumn();
$total_categories = $pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();
$recent_users = $pdo->query("SELECT id, username, email, role, created_at FROM users ORDER BY created_at DESC LIMIT 5")->fetchAll();
$recent_income = $pdo->query("SELECT i.*, c.name AS category_name, u.username FROM income i JOIN categories c ON i.category_id = c.id JOIN users u ON i.user_id = u.id ORDER BY i.created_at DESC LIMIT 5")->fetchAll();
$recent_expenses = $pdo->query("SELECT e.*, c.name AS category_name, u.username FROM expenses e JOIN categories c ON e.category_id = c.id JOIN users u ON e.user_id = u.id ORDER BY e.created_at DESC LIMIT 5")->fetchAll();
?>
<?php require_once '../includes/header.php'; ?>

<div class="page-header">
    <div>
        <h1 class="page-title">Admin Dashboard</h1>
        <p class="page-subtitle">Overview of platform metrics and recent activity.</p>
    </div>
</div>

<!-- Stat Cards -->
<section class="stats-grid">
    <a href="<?php echo BASE_URL; ?>/admin/users" class="stat-card">
        <div class="stat-icon primary"><span class="material-symbols-outlined">group</span></div>
        <div>
            <div class="stat-label">Total Users</div>
            <div class="stat-value"><?php echo $total_users; ?></div>
        </div>
    </a>
    <div class="stat-card">
        <div class="stat-icon income"><span class="material-symbols-outlined">payments</span></div>
        <div>
            <div class="stat-label">Total Income</div>
            <div class="stat-value">₹<?php echo number_format($total_income, 2); ?></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon expense"><span class="material-symbols-outlined">receipt_long</span></div>
        <div>
            <div class="stat-label">Total Expenses</div>
            <div class="stat-value">₹<?php echo number_format($total_expenses, 2); ?></div>
        </div>
    </div>
    <a href="<?php echo BASE_URL; ?>/admin/categories" class="stat-card">
        <div class="stat-icon primary"><span class="material-symbols-outlined">folder</span></div>
        <div>
            <div class="stat-label">Total Categories</div>
            <div class="stat-value"><?php echo $total_categories; ?></div>
        </div>
    </a>
</section>

<!-- Recent Users Table -->
<section class="card">
    <div class="card-header">
        <h2 class="card-title">Recent Users</h2>
        <a href="<?php echo BASE_URL; ?>/admin/users" class="text-primary" style="font-size:12px; font-weight:600;">VIEW ALL</a>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Joined</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recent_users as $u): ?>
                <tr>
                    <td class="text-muted" style="font-size:12px;">#<?php echo $u['id']; ?></td>
                    <td>
                        <div class="user-cell">
                            <span class="avatar-sm <?php echo $u['role'] === 'admin' ? 'admin' : ''; ?>"><?php echo strtoupper(substr($u['username'], 0, 1)); ?></span>
                            <span><?php echo htmlspecialchars($u['username']); ?></span>
                        </div>
                    </td>
                    <td class="text-muted"><?php echo htmlspecialchars($u['email']); ?></td>
                    <td>
                        <span class="badge <?php echo $u['role'] === 'admin' ? 'badge-admin' : 'badge-user'; ?>">
                            <?php echo ucfirst($u['role']); ?>
                        </span>
                    </td>
                    <td class="text-muted"><?php echo date('M d, Y', strtotime($u['created_at'])); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<!-- Recent Income / Expenses -->
<section class="grid-2">
    <div class="card">
        <div class="card-header" style="background: rgba(5,150,105,0.05);">
            <h2 class="card-title" style="color: var(--income-green);">
                <span class="material-symbols-outlined" style="vertical-align: middle;">trending_up</span>
                Recent Income
            </h2>
        </div>
        <?php if (count($recent_income) > 0): ?>
        <ul class="notification-list">
            <?php foreach ($recent_income as $i): ?>
            <li>
                <span class="notif-icon success"><span class="material-symbols-outlined">payments</span></span>
                <div>
                    <div style="font-weight:600; font-size:14px;"><?php echo htmlspecialchars($i['username']); ?></div>
                    <div class="text-muted" style="font-size:13px;"><?php echo htmlspecialchars($i['category_name']); ?> - <?php echo date('M d, Y', strtotime($i['date'])); ?></div>
                </div>
                <span class="notif-date" style="color:var(--income-green); font-weight:600;">+₹<?php echo number_format($i['amount'], 2); ?></span>
            </li>
            <?php endforeach; ?>
        </ul>
        <?php else: ?>
        <div class="card-padded text-center"><p class="text-muted">No income records yet.</p></div>
        <?php endif; ?>
    </div>

    <div class="card">
        <div class="card-header" style="background: rgba(220,38,38,0.05);">
            <h2 class="card-title" style="color: var(--expense-red);">
                <span class="material-symbols-outlined" style="vertical-align: middle;">trending_down</span>
                Recent Expenses
            </h2>
        </div>
        <?php if (count($recent_expenses) > 0): ?>
        <ul class="notification-list">
            <?php foreach ($recent_expenses as $e): ?>
            <li>
                <span class="notif-icon warning"><span class="material-symbols-outlined">shopping_cart</span></span>
                <div>
                    <div style="font-weight:600; font-size:14px;"><?php echo htmlspecialchars($e['username']); ?></div>
                    <div class="text-muted" style="font-size:13px;"><?php echo htmlspecialchars($e['category_name']); ?> - <?php echo date('M d, Y', strtotime($e['date'])); ?></div>
                </div>
                <span class="notif-date" style="color:var(--expense-red); font-weight:600;">-₹<?php echo number_format($e['amount'], 2); ?></span>
            </li>
            <?php endforeach; ?>
        </ul>
        <?php else: ?>
        <div class="card-padded text-center"><p class="text-muted">No expense records yet.</p></div>
        <?php endif; ?>
    </div>
</section>

<?php require_once '../includes/footer.php'; ?>