<?php
require_once '../includes/admin_auth.php';
$page_title = 'Manage Default Categories';
$active_nav = 'categories';

$message = '';
$message_type = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add'])) {
        $name = sanitize($_POST['name']);
        $type = $_POST['type'];

        if ($name !== '' && in_array($type, ['income', 'expense'])) {
            $stmt = $pdo->prepare("INSERT INTO categories (user_id, name, type) VALUES (0, ?, ?)");
            $stmt->execute([$name, $type]);
            $message = "Category added successfully.";
        } else {
            $message = "Please fill in all fields.";
            $message_type = 'danger';
        }
    }

    if (isset($_POST['delete'])) {
        $id = (int)$_POST['id'];
        $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ? AND user_id = 0");
        $stmt->execute([$id]);
        if ($stmt->rowCount() > 0) {
            $message = "Category deleted successfully.";
        } else {
            $message = "Category not found or could not be deleted.";
            $message_type = 'danger';
        }
    }

    if (isset($_POST['edit'])) {
        $id = (int)$_POST['id'];
        $name = sanitize($_POST['name']);
        $type = $_POST['type'];

        if ($name !== '' && in_array($type, ['income', 'expense'])) {
            $stmt = $pdo->prepare("UPDATE categories SET name = ?, type = ? WHERE id = ? AND user_id = 0");
            $stmt->execute([$name, $type, $id]);
            if ($stmt->rowCount() > 0) {
                $message = "Category updated successfully.";
            } else {
                $message = "Category not found or no changes made.";
                $message_type = 'danger';
            }
        }
    }
}

$default_categories = $pdo->query("SELECT * FROM categories WHERE user_id = 0 ORDER BY type, name")->fetchAll();
?>
<?php require_once '../includes/header.php'; ?>

<?php if ($message !== ''): ?>
<div class="alert alert-<?php echo $message_type; ?>"><?php echo $message; ?></div>
<?php endif; ?>

<div class="page-header">
    <div>
        <h1 class="page-title">Manage Default Categories</h1>
        <p class="page-subtitle">Add, edit, or remove default categories shared by all users.</p>
    </div>
    <a href="<?php echo BASE_URL; ?>/admin/dashboard" class="btn btn-outline btn-sm">
        <span class="material-symbols-outlined" style="font-size:16px;">arrow_back</span>
        Back to Dashboard
    </a>
</div>

<div class="grid-categories">
    <section class="card">
        <div class="card-header">
            <h2 class="card-title">Add New Default Category</h2>
        </div>
        <div class="card-padded">
            <form method="POST">
                <div class="form-group">
                    <label for="name">Category Name</label>
                    <input type="text" id="name" name="name" placeholder="e.g. Shopping" required>
                </div>
                <div class="form-group">
                    <label for="type">Type</label>
                    <select id="type" name="type" required>
                        <option value="income">Income</option>
                        <option value="expense">Expense</option>
                    </select>
                </div>
                <button type="submit" name="add" class="btn btn-primary mt-sm" style="width:100%;">Add Category</button>
            </form>
        </div>
    </section>

    <section class="card">
        <div class="card-header">
            <h2 class="card-title">All Default Categories</h2>
        </div>
        <?php if (count($default_categories) > 0): ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Type</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($default_categories as $cat): ?>
                    <tr>
                        <td class="text-muted" style="font-size:12px;">#<?php echo $cat['id']; ?></td>
                        <td><?php echo htmlspecialchars($cat['name']); ?></td>
                        <td>
                            <span class="badge <?php echo $cat['type'] === 'income' ? 'badge-income' : 'badge-expense'; ?>">
                                <?php echo ucfirst($cat['type']); ?>
                            </span>
                        </td>
                        <td class="text-right">
                            <div class="flex gap-sm justify-end">
                                <button class="btn-delete-table" data-open-modal="editModal" onclick="editCategory(<?php echo $cat['id']; ?>, '<?php echo htmlspecialchars(addslashes($cat['name'])); ?>', '<?php echo htmlspecialchars(addslashes($cat['type'])); ?>')">
                                    <span class="material-symbols-outlined">edit</span>
                                </button>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this category? It may affect existing records.');">
                                    <input type="hidden" name="id" value="<?php echo $cat['id']; ?>">
                                    <button type="submit" name="delete" class="btn-delete-table">
                                        <span class="material-symbols-outlined">delete</span>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="card-padded text-center"><p class="text-muted">No default categories found.</p></div>
        <?php endif; ?>
    </section>
</div>

<div class="modal-overlay hidden" id="editModal">
    <div class="modal-card" style="max-width: 420px;">
        <div class="modal-header">
            <h3 class="modal-title">Edit Category</h3>
            <button type="button" class="modal-close" data-close-modal="editModal"><span class="material-symbols-outlined">close</span></button>
        </div>
        <div class="modal-body">
            <form method="POST">
                <input type="hidden" name="id" id="edit-id">
                <div class="form-group">
                    <label for="edit-name">Category Name</label>
                    <input type="text" name="name" id="edit-name" required>
                </div>
                <div class="form-group">
                    <label for="edit-type">Type</label>
                    <select name="type" id="edit-type" required>
                        <option value="income">Income</option>
                        <option value="expense">Expense</option>
                    </select>
                </div>
                <div class="flex gap-md mt-md" style="justify-content: flex-end;">
                    <button type="button" class="btn btn-ghost" data-close-modal="editModal">Cancel</button>
                    <button type="submit" name="edit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editCategory(id, name, type) {
    document.getElementById('edit-id').value = id;
    document.getElementById('edit-name').value = name;
    document.getElementById('edit-type').value = type;
}
</script>
<?php require_once '../includes/footer.php'; ?>