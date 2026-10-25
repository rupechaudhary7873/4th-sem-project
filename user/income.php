<?php
require_once '../includes/auth.php';
$page_title = 'Income Management';
$active_nav = 'income';

$message = '';
$message_type = '';

$categories = $pdo->prepare("SELECT * FROM categories WHERE user_id = ? AND type = 'income' ORDER BY name");
$categories->execute([$user_id]);
$income_categories = $categories->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_income'])) {
        $category_id = (int)$_POST['category_id'];
        $amount = (float)$_POST['amount'];
        $description = sanitize($_POST['description']);
        $date = sanitize($_POST['date']);

        if ($category_id > 0 && $amount > 0 && isValidDate($date)) {
            $stmt = $pdo->prepare("INSERT INTO income (user_id, category_id, amount, description, date) VALUES (?, ?, ?, ?, ?)");
            if ($stmt->execute([$user_id, $category_id, $amount, $description, $date])) {
                $message = 'Income added successfully!';
                $message_type = 'success';
            } else {
                $message = 'Failed to add income';
                $message_type = 'danger';
            }
        } else {
            $message = 'Please fill all required fields correctly';
            $message_type = 'danger';
        }
    } elseif (isset($_POST['edit_income'])) {
        $id = (int)$_POST['id'];
        $category_id = (int)$_POST['category_id'];
        $amount = (float)$_POST['amount'];
        $description = sanitize($_POST['description']);
        $date = sanitize($_POST['date']);

        $stmt = $pdo->prepare("UPDATE income SET category_id = ?, amount = ?, description = ?, date = ? WHERE id = ? AND user_id = ?");
        if ($category_id > 0 && $amount > 0 && isValidDate($date) && $stmt->execute([$category_id, $amount, $description, $date, $id, $user_id])) {
            $message = 'Income updated successfully!';
            $message_type = 'success';
        } else {
            $message = 'Failed to update income';
            $message_type = 'danger';
        }
    } elseif (isset($_POST['delete_income'])) {
        $id = (int)$_POST['id'];
        $stmt = $pdo->prepare("DELETE FROM income WHERE id = ? AND user_id = ?");
        if ($stmt->execute([$id, $user_id])) {
            $message = 'Income deleted successfully!';
            $message_type = 'success';
        } else {
            $message = 'Failed to delete income';
            $message_type = 'danger';
        }
    }
}

list($total_income, $page_count, $page, $per_page, $offset) = paginate($pdo, 'income', $user_id);

$stmt = $pdo->prepare("
    SELECT i.*, c.name as category_name
    FROM income i
    JOIN categories c ON i.category_id = c.id
    WHERE i.user_id = ?
    ORDER BY i.date DESC
    LIMIT ? OFFSET ?
");
$stmt->bindValue(1, $user_id, PDO::PARAM_INT);
$stmt->bindValue(2, $per_page, PDO::PARAM_INT);
$stmt->bindValue(3, $offset, PDO::PARAM_INT);
$stmt->execute();
$income_list = $stmt->fetchAll();

require_once '../includes/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Income Management</h1>
        <p class="page-subtitle">Track and manage all your revenue streams.</p>
    </div>
    <button class="btn btn-primary" data-open-modal="addModal">
        <span class="material-symbols-outlined">add</span>
        Add Income
    </button>
</div>

<?php if ($message): ?>
    <div class="alert alert-<?php echo $message_type; ?>"><?php echo $message; ?></div>
<?php endif; ?>

<div class="card">
    <?php if (empty($income_list)): ?>
        <div class="card-padded text-center">
            <p class="text-muted">No income records found. Click "Add Income" to get started!</p>
        </div>
    <?php else: ?>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Category</th>
                    <th>Description</th>
                    <th class="text-right">Amount</th>
                    <th class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($income_list as $income): ?>
                <tr>
                    <td><?php echo date('d M Y', strtotime($income['date'])); ?></td>
                    <td>
                        <span class="badge <?php echo categoryBadge($income['category_name'], 'income'); ?>"><?php echo htmlspecialchars($income['category_name']); ?></span>
                    </td>
                    <td><?php echo htmlspecialchars($income['description'] ?? '-'); ?></td>
                    <td class="text-right amount-income">+₹<?php echo number_format($income['amount'], 2); ?></td>
                    <td class="text-center">
                        <div class="flex justify-center gap-sm">
                            <button class="btn btn-icon btn-outline" title="Edit" onclick="editIncome(<?php echo $income['id']; ?>, '<?php echo htmlspecialchars(addslashes($income['category_name'])); ?>', <?php echo $income['category_id']; ?>, <?php echo $income['amount']; ?>, '<?php echo htmlspecialchars(addslashes($income['description'] ?? '')); ?>', '<?php echo htmlspecialchars(addslashes($income['date'])); ?>')">
                                <span class="material-symbols-outlined">edit</span>
                            </button>
                            <form method="POST" onsubmit="return confirmDelete('Delete this income?')">
                                <input type="hidden" name="id" value="<?php echo $income['id']; ?>">
                                <button type="submit" name="delete_income" class="btn btn-icon btn-danger" title="Delete">
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
    <div class="table-footer">
        <?php renderPagination($page, $page_count, $total_income, $per_page, $offset); ?>
    </div>
    <?php endif; ?>
</div>

<!-- Add Modal -->
<div id="addModal" class="modal-overlay hidden">
    <div class="modal-card">
        <div class="modal-header">
            <h2 class="modal-title">Add New Income</h2>
            <button type="button" class="modal-close" data-close-modal="addModal">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        <form method="POST" onsubmit="return validateForm('addIncomeForm')" id="addIncomeForm">
            <div class="modal-body">
                <div class="form-group">
                    <label for="add_category">Category *</label>
                    <select id="add_category" name="category_id" required>
                        <option value="">Select Category</option>
                        <?php foreach ($income_categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="add_amount">Amount (₹) *</label>
                    <input type="number" id="add_amount" name="amount" step="0.01" min="0.01" placeholder="0.00" required onblur="validateAmount(this)">
                </div>
                <div class="form-group">
                    <label for="add_description">Description</label>
                    <textarea id="add_description" name="description" rows="3" placeholder="e.g. October Salary"></textarea>
                </div>
                <div class="form-group">
                    <label for="add_date">Date *</label>
                    <input type="date" id="add_date" name="date" value="<?php echo date('Y-m-d'); ?>" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" data-close-modal="addModal">Cancel</button>
                <button type="submit" name="add_income" class="btn btn-primary">Save Income</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Modal -->
<div id="editModal" class="modal-overlay hidden">
    <div class="modal-card">
        <div class="modal-header">
            <h2 class="modal-title">Edit Income</h2>
            <button type="button" class="modal-close" data-close-modal="editModal">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        <form method="POST" id="editIncomeForm" onsubmit="return validateForm('editIncomeForm')">
            <input type="hidden" name="id" id="edit_id">
            <div class="modal-body">
                <div class="form-group">
                    <label for="edit_category">Category *</label>
                    <select id="edit_category" name="category_id" required>
                        <option value="">Select Category</option>
                        <?php foreach ($income_categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="edit_amount">Amount (₹) *</label>
                    <input type="number" id="edit_amount" name="amount" step="0.01" min="0.01" required>
                </div>
                <div class="form-group">
                    <label for="edit_description">Description</label>
                    <textarea id="edit_description" name="description" rows="3"></textarea>
                </div>
                <div class="form-group">
                    <label for="edit_date">Date *</label>
                    <input type="date" id="edit_date" name="date" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" data-close-modal="editModal">Cancel</button>
                <button type="submit" name="edit_income" class="btn btn-primary">Update Income</button>
            </div>
        </form>
    </div>
</div>

<script>
function editIncome(id, categoryName, categoryId, amount, description, date) {
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_category').value = categoryId;
    document.getElementById('edit_amount').value = amount;
    document.getElementById('edit_description').value = description;
    document.getElementById('edit_date').value = date;
    document.getElementById('editModal').classList.remove('hidden');
}
</script>

<?php require_once '../includes/footer.php'; ?>