<?php
require_once '../includes/admin_auth.php';
$page_title = 'Manage Users';
$active_nav = 'users';

$message = '';

$current_admin_user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $user_id = (int)$_POST['user_id'];

    if ($_POST['action'] === 'delete' && $user_id !== $current_admin_user_id) {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $message = "User deleted successfully.";
    }

    if ($_POST['action'] === 'toggle_role') {
        $stmt = $pdo->prepare("SELECT id, username, email, password, role FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $u = $stmt->fetch();
        if ($u) {
            $new_role = $u['role'] === 'admin' ? 'user' : 'admin';
            $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
            $stmt->execute([$new_role, $user_id]);
            $message = "User role updated to " . $new_role . ".";

            if ($new_role === 'admin') {
                $pdo->prepare("INSERT IGNORE INTO admins (full_name, username, email, password) VALUES (?, ?, ?, ?)")
                    ->execute([$u['username'], $u['username'], $u['email'], $u['password']]);
            } else {
                $current_admin_id = isset($_SESSION['admin_id']) ? (int)$_SESSION['admin_id'] : 0;
                $pdo->prepare("DELETE FROM admins WHERE (username = ? OR email = ?) AND id != ?")
                    ->execute([$u['username'], $u['email'], $current_admin_id]);
            }
        }
    }
}

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$role_filter = isset($_GET['role']) ? $_GET['role'] : '';

$sql = "SELECT COUNT(*) FROM users WHERE 1=1";
$params = [];

if ($search !== '') {
    $sql .= " AND (username LIKE ? OR email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($role_filter !== '') {
    $sql .= " AND role = ?";
    $params[] = $role_filter;
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$total_users = (int)$stmt->fetchColumn();

$per_page = 8;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$page_count = max(1, (int)ceil($total_users / $per_page));
$page = min($page, $page_count);
$offset = ($page - 1) * $per_page;

$sql = "SELECT id, username, email, role, created_at FROM users WHERE 1=1";
$params = [];
$param_types = [];

if ($search !== '') {
    $sql .= " AND (username LIKE ? OR email LIKE ?)";
    $params[] = "%$search%";
    $param_types[] = PDO::PARAM_STR;
    $params[] = "%$search%";
    $param_types[] = PDO::PARAM_STR;
}

if ($role_filter !== '') {
    $sql .= " AND role = ?";
    $params[] = $role_filter;
    $param_types[] = PDO::PARAM_STR;
}

$sql .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
$params[] = $per_page;
$param_types[] = PDO::PARAM_INT;
$params[] = $offset;
$param_types[] = PDO::PARAM_INT;
$stmt = $pdo->prepare($sql);
foreach ($params as $i => $p) {
    $stmt->bindValue($i + 1, $p, $param_types[$i]);
}
$stmt->execute();
$users = $stmt->fetchAll();
?>
<?php require_once '../includes/header.php'; ?>

<?php if ($message !== ''): ?>
<div class="alert alert-success"><?php echo $message; ?></div>
<?php endif; ?>

<div class="page-header">
    <div>
        <h1 class="page-title">Manage Users</h1>
        <p class="page-subtitle">Search, promote, or remove registered users.</p>
    </div>
    <form method="GET" class="search-box">
        <span class="material-symbols-outlined" style="color:var(--outline); font-size:20px;">search</span>
        <input type="text" name="search" placeholder="Search Username or Email" value="<?php echo htmlspecialchars($search); ?>">
        <select name="role" style="width:auto; min-width:130px;">
            <option value="">All Roles</option>
            <option value="admin" <?php echo $role_filter === 'admin' ? 'selected' : ''; ?>>Admin</option>
            <option value="user" <?php echo $role_filter === 'user' ? 'selected' : ''; ?>>User</option>
        </select>
        <button type="submit" class="btn btn-primary btn-sm">Filter</button>
    </form>
</div>

<section class="card">
    <?php if (count($users) > 0): ?>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Joined</th>
                    <th class="text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
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
                    <td class="text-right">
                        <div class="flex gap-sm justify-end">
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                <input type="hidden" name="action" value="toggle_role">
                                <button type="submit" class="btn btn-outline btn-sm">
                                    <?php echo $u['role'] === 'admin' ? 'Revoke Admin' : 'Make Admin'; ?>
                                </button>
                            </form>
                            <?php if ((int)$u['id'] !== (int)$_SESSION['user_id']): ?>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this user? This will also delete all their data.');">
                                <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                <input type="hidden" name="action" value="delete">
                                <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                            </form>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="table-footer">
        <span>Showing <?php echo $total_users === 0 ? 0 : $offset + 1; ?> to <?php echo min($offset + $per_page, $total_users); ?> of <?php echo $total_users; ?> users</span>
        <div class="pagination">
            <?php $qs = $_GET; unset($qs['page']); ?>
            <a class="page-btn <?php echo $page <= 1 ? 'disabled' : ''; ?>" href="?<?php echo http_build_query(array_merge($qs, ['page' => $page - 1])); ?>">Prev</a>
            <?php for ($i = 1; $i <= $page_count; $i++): ?>
            <a class="page-btn <?php echo $i === $page ? 'active' : ''; ?>" href="?<?php echo http_build_query(array_merge($qs, ['page' => $i])); ?>"><?php echo $i; ?></a>
            <?php endfor; ?>
            <a class="page-btn <?php echo $page >= $page_count ? 'disabled' : ''; ?>" href="?<?php echo http_build_query(array_merge($qs, ['page' => $page + 1])); ?>">Next</a>
        </div>
    </div>
    <?php else: ?>
    <div class="card-padded text-center"><p class="text-muted">No users found.</p></div>
    <?php endif; ?>
</section>

<?php require_once '../includes/footer.php'; ?>