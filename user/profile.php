<?php
require_once '../includes/auth.php';
$page_title = 'My Profile';
$active_nav = 'profile';

$stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'profile_pic'");
if (!$stmt->fetch()) {
    $pdo->exec("ALTER TABLE users ADD COLUMN profile_pic VARCHAR(255) DEFAULT NULL AFTER email");
    $user = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $user->execute([$user_id]);
    $user = $user->fetch();
}

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_profile'])) {
    $new_username = trim($_POST['username']);
    $errors = [];

    if ($new_username === '') {
        $errors[] = 'Username cannot be empty.';
    } elseif (strlen($new_username) < 3) {
        $errors[] = 'Username must be at least 3 characters.';
    } elseif ($new_username !== $user['username']) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
        $stmt->execute([$new_username, $user_id]);
        if ($stmt->fetch()) {
            $errors[] = 'Username already taken.';
        }
    }

    $pic_filename = $user['profile_pic'];
    if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['profile_pic'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];

        if (!in_array($ext, $allowed)) {
            $errors[] = 'Only JPG, PNG, and GIF files are allowed.';
        } elseif ($file['size'] > 2 * 1024 * 1024) {
            $errors[] = 'File size must be under 2MB.';
        } else {
            if ($pic_filename && file_exists(__DIR__ . '/../uploads/profiles/' . $pic_filename)) {
                unlink(__DIR__ . '/../uploads/profiles/' . $pic_filename);
            }
            $pic_filename = $user_id . '_' . time() . '.' . $ext;
            move_uploaded_file($file['tmp_name'], __DIR__ . '/../uploads/profiles/' . $pic_filename);
        }
    } elseif (isset($_POST['remove_pic'])) {
        if ($pic_filename && file_exists(__DIR__ . '/../uploads/profiles/' . $pic_filename)) {
            unlink(__DIR__ . '/../uploads/profiles/' . $pic_filename);
        }
        $pic_filename = null;
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("UPDATE users SET username = ?, profile_pic = ? WHERE id = ?");
        $stmt->execute([$new_username, $pic_filename, $user_id]);
        $_SESSION['username'] = $new_username;
        $user['username'] = $new_username;
        $user['profile_pic'] = $pic_filename;
        header('Location: ' . BASE_URL . '/profile');
        exit();
    } else {
        $message = implode('<br>', $errors);
        $message_type = 'danger';
    }
}

$editing = isset($_GET['edit']) || ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_profile']));
require_once '../includes/header.php';
?>

<?php if ($message): ?>
    <div class="alert alert-<?php echo $message_type; ?>"><?php echo $message; ?></div>
<?php endif; ?>

<div class="profile-layout">
    <!-- Sidebar -->
    <aside>
        <div class="profile-sidebar-card">
            <div class="profile-avatar-lg">
                <?php if (!empty($user['profile_pic'])): ?>
                <img src="<?php echo BASE_URL; ?>/uploads/profiles/<?php echo $user['profile_pic']; ?>" alt="Profile">
                <?php else: ?>
                <div class="profile-avatar-fallback"><?php echo strtoupper($user['username'][0]); ?></div>
                <?php endif; ?>
            </div>
            <div>
                <div class="profile-name"><?php echo htmlspecialchars($user['username']); ?></div>
                <span class="badge badge-primary mt-sm"><?php echo ucfirst($user['role']); ?></span>
            </div>
            <p class="profile-since">Managing finances with Budget Manager since <?php echo date('Y', strtotime($user['created_at'])); ?>.</p>
        </div>

        <div class="card" style="margin-bottom:0; padding:8px;">
            <nav class="profile-nav">
                <button class="profile-nav-item active" onclick="setActiveNav(this)" type="button">
                    <span class="material-symbols-outlined">person</span>
                    Personal Information
                </button>
                <a class="profile-nav-item" href="<?php echo BASE_URL; ?>/profile?edit=1">
                    <span class="material-symbols-outlined">settings</span>
                    Account Settings
                </a>
                <a class="profile-nav-item" href="#">
                    <span class="material-symbols-outlined">notifications</span>
                    Notifications
                </a>
                <a class="profile-nav-item" href="#">
                    <span class="material-symbols-outlined">security</span>
                    Security
                </a>
            </nav>
        </div>
    </aside>

    <!-- Content -->
    <div>
        <?php if ($editing): ?>
        <section class="card">
            <div class="card-header">
                <h2 class="card-title">Edit Profile</h2>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <div class="card-padded" style="max-width: 640px;">
                    <div class="form-group">
                        <label>Profile Picture</label>
                        <div class="flex items-center gap-md">
                            <div class="profile-avatar-lg" style="width:64px; height:64px; border:none;">
                                <?php if (!empty($user['profile_pic'])): ?>
                                <img src="<?php echo BASE_URL; ?>/uploads/profiles/<?php echo $user['profile_pic']; ?>" alt="Profile">
                                <?php else: ?>
                                <div class="profile-avatar-fallback" style="font-size:24px;"><?php echo strtoupper($user['username'][0]); ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="flex flex-col gap-sm">
                                <input type="file" name="profile_pic" accept="image/jpeg,image/png,image/gif">
                                <span class="text-muted" style="font-size:13px;">JPG, PNG or GIF. Max size 2MB.</span>
                            </div>
                        </div>
                        <?php if (!empty($user['profile_pic'])): ?>
                        <label class="checkbox-label mt-md">
                            <input type="checkbox" name="remove_pic" value="1"> Remove current picture
                        </label>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required minlength="3">
                    </div>

                    <div class="flex gap-md mt-md" style="justify-content: flex-end;">
                        <a href="<?php echo BASE_URL; ?>/profile" class="btn btn-ghost">Cancel</a>
                        <button type="submit" name="edit_profile" class="btn btn-primary">Save Changes</button>
                    </div>
                </div>
            </form>
        </section>

        <?php else: ?>
        <section class="card">
            <div class="card-header">
                <h2 class="card-title">Personal Information</h2>
                <a href="<?php echo BASE_URL; ?>/profile?edit=1" class="btn btn-outline btn-sm">
                    <span class="material-symbols-outlined" style="font-size:16px;">edit</span>
                    Edit
                </a>
            </div>
            <div class="profile-info-grid">
                <div class="info-field">
                    <label>Username</label>
                    <div class="info-value"><?php echo htmlspecialchars($user['username']); ?></div>
                </div>
                <div class="info-field">
                    <label>Email</label>
                    <div class="info-value"><?php echo htmlspecialchars($user['email']); ?></div>
                </div>
                <div class="info-field">
                    <label>Role</label>
                    <div class="info-value">
                        <span class="badge badge-primary"><?php echo ucfirst($user['role']); ?></span>
                    </div>
                </div>
                <div class="info-field">
                    <label>Member Since</label>
                    <div class="info-value"><?php echo date('d F Y', strtotime($user['created_at'])); ?></div>
                </div>
            </div>
        </section>
        <?php endif; ?>
    </div>
</div>

<script>
function setActiveNav(el) {
    document.querySelectorAll('.profile-nav-item').forEach(function(item) {
        item.classList.remove('active');
    });
    el.classList.add('active');
}
<?php if ($editing): ?>
document.addEventListener('DOMContentLoaded', function() {
    var items = document.querySelectorAll('.profile-nav-item');
    items.forEach(function(item) {
        item.classList.remove('active');
    });
    if (items.length >= 2) items[1].classList.add('active');
});
<?php endif; ?>
</script>

<?php require_once '../includes/footer.php'; ?>