<?php
require_once '../includes/auth.php';
$page_title = 'Expense Management';
$active_nav = 'expenses';

$message = '';
$message_type = '';

$categories = $pdo->prepare("SELECT * FROM categories WHERE user_id = ? AND type = 'expense' ORDER BY name");
$categories->execute([$user_id]);
$expense_categories = $categories->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_expense'])) {
        $category_id = (int)$_POST['category_id'];
        $amount = (float)$_POST['amount'];
        $description = sanitize($_POST['description']);
        $date = sanitize($_POST['date']);

        if ($category_id > 0 && $amount > 0 && isValidDate($date)) {
            $stmt = $pdo->prepare("INSERT INTO expenses (user_id, category_id, amount, description, date) VALUES (?, ?, ?, ?, ?)");
            if ($stmt->execute([$user_id, $category_id, $amount, $description, $date])) {
                $expense_id = $pdo->lastInsertId();

                $month = date('m', strtotime($date));
                $year = date('Y', strtotime($date));

                $budget_check = $pdo->prepare("
                    SELECT b.id, b.amount,
                           COALESCE(SUM(e.amount), 0) as total_spent
                    FROM budgets b
                    LEFT JOIN expenses e ON e.category_id = b.category_id
                        AND e.user_id = b.user_id
                        AND MONTH(e.date) = b.month
                        AND YEAR(e.date) = b.year
                        AND e.id <= ?
                    WHERE b.user_id = ? AND b.category_id = ?
                        AND b.month = ? AND b.year = ?
                    GROUP BY b.id, b.amount
                ");
                $budget_check->execute([$expense_id, $user_id, $category_id, $month, $year]);
                $budget_data = $budget_check->fetch();

                if ($budget_data) {
                    $budget_amt = $budget_data['amount'];
                    $spent = $budget_data['total_spent'];
                    $percentage = ($spent / $budget_amt) * 100;

                    if ($percentage >= 100) {
                        $cat_name = $pdo->prepare("SELECT name FROM categories WHERE id = ?");
                        $cat_name->execute([$category_id]);
                        $cat = $cat_name->fetch();

                        $notif_msg = "Budget exceeded for {$cat['name']}! Spent ₹" . number_format($spent, 2) . " of ₹" . number_format($budget_amt, 2);
                        $notif = $pdo->prepare("INSERT INTO notifications (user_id, message, type) VALUES (?, ?, 'warning')");
                        $notif->execute([$user_id, $notif_msg]);
                    } elseif ($percentage >= 80) {
                        $cat_name = $pdo->prepare("SELECT name FROM categories WHERE id = ?");
                        $cat_name->execute([$category_id]);
                        $cat = $cat_name->fetch();

                        $notif_msg = "Budget warning for {$cat['name']}: " . number_format($percentage, 1) . "% used (₹" . number_format($spent, 2) . " of ₹" . number_format($budget_amt, 2) . ")";
                        $notif = $pdo->prepare("INSERT INTO notifications (user_id, message, type) VALUES (?, ?, 'warning')");
                        $notif->execute([$user_id, $notif_msg]);
                    }
                }

                $message = 'Expense added successfully!';
                $message_type = 'success';
            } else {
                $message = 'Failed to add expense';
                $message_type = 'danger';
            }
        } else {
            $message = 'Please fill all required fields correctly';
            $message_type = 'danger';
        }
    } elseif (isset($_POST['edit_expense'])) {
        $id = (int)$_POST['id'];
        $category_id = (int)$_POST['category_id'];
        $amount = (float)$_POST['amount'];
        $description = sanitize($_POST['description']);
        $date = sanitize($_POST['date']);

        $stmt = $pdo->prepare("UPDATE expenses SET category_id = ?, amount = ?, description = ?, date = ? WHERE id = ? AND user_id = ?");
        if ($category_id > 0 && $amount > 0 && isValidDate($date) && $stmt->execute([$category_id, $amount, $description, $date, $id, $user_id])) {
            $message = 'Expense updated successfully!';
            $message_type = 'success';
        } else {
            $message = 'Failed to update expense';
            $message_type = 'danger';
        }
    } elseif (isset($_POST['delete_expense'])) {
        $id = (int)$_POST['id'];
        $stmt = $pdo->prepare("DELETE FROM expenses WHERE id = ? AND user_id = ?");
        if ($stmt->execute([$id, $user_id])) {
            $message = 'Expense deleted successfully!';
            $message_type = 'success';
        } else {
            $message = 'Failed to delete expense';
            $message_type = 'danger';
        }
    }
}

list($total_expenses, $page_count, $page, $per_page, $offset) = paginate($pdo, 'expenses', $user_id);

$stmt = $pdo->prepare("
    SELECT e.*, c.name as category_name
    FROM expenses e
    JOIN categories c ON e.category_id = c.id
    WHERE e.user_id = ?
    ORDER BY e.date DESC
    LIMIT ? OFFSET ?
");
$stmt->bindValue(1, $user_id, PDO::PARAM_INT);
$stmt->bindValue(2, $per_page, PDO::PARAM_INT);
$stmt->bindValue(3, $offset, PDO::PARAM_INT);
$stmt->execute();
$expense_list = $stmt->fetchAll();

$current_month = date('m');
$current_year = date('Y');
$last_month = $current_month == 1 ? 12 : $current_month - 1;
$last_month_year = $current_month == 1 ? $current_year - 1 : $current_year;

$stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) as total FROM expenses WHERE user_id = ? AND MONTH(date) = ? AND YEAR(date) = ?");
$stmt->execute([$user_id, $current_month, $current_year]);
$this_month_expenses = $stmt->fetch()['total'];

$stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) as total FROM expenses WHERE user_id = ? AND MONTH(date) = ? AND YEAR(date) = ?");
$stmt->execute([$user_id, $last_month, $last_month_year]);
$last_month_expenses = $stmt->fetch()['total'];

$stmt = $pdo->prepare("
    SELECT c.name, COALESCE(SUM(e.amount), 0) as total
    FROM expenses e
    JOIN categories c ON e.category_id = c.id
    WHERE e.user_id = ? AND MONTH(e.date) = ? AND YEAR(e.date) = ?
    GROUP BY c.name
    ORDER BY total DESC
    LIMIT 1
");
$stmt->execute([$user_id, $current_month, $current_year]);
$largest = $stmt->fetch();

$vs_last_month = 0;
if ($last_month_expenses > 0) {
    $vs_last_month = (($this_month_expenses - $last_month_expenses) / $last_month_expenses) * 100;
}

require_once '../includes/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Expense Management</h1>
        <p class="page-subtitle">Track and manage your outgoing funds.</p>
    </div>
    <button class="btn btn-primary" data-open-modal="addModal">
        <span class="material-symbols-outlined">add</span>
        Add Expense
    </button>
</div>

<?php if ($message): ?>
    <div class="alert alert-<?php echo $message_type; ?>"><?php echo $message; ?></div>
<?php endif; ?>

<!-- Summary Cards -->
<section class="summary-grid">
    <div class="summary-card">
        <div class="stat-icon expense">
            <span class="material-symbols-outlined">receipt_long</span>
        </div>
        <div>
            <div class="summary-label">Total Expenses This Month</div>
            <div class="summary-value">₹<?php echo number_format($this_month_expenses, 2); ?></div>
        </div>
    </div>
    <div class="summary-card">
        <div class="stat-icon warning">
            <span class="material-symbols-outlined">pie_chart</span>
        </div>
        <div>
            <div class="summary-label">Largest Category</div>
            <div class="summary-value"><?php echo $largest ? htmlspecialchars($largest['name']) : '-'; ?></div>
        </div>
    </div>
    <div class="summary-card">
        <div class="stat-icon primary">
            <span class="material-symbols-outlined">trending_down</span>
        </div>
        <div>
            <div class="summary-label">vs Last Month</div>
            <div class="summary-value" style="color: <?php echo $vs_last_month <= 0 ? 'var(--income-green)' : 'var(--expense-red)'; ?>;">
                <?php echo ($vs_last_month <= 0 ? '' : '+') . number_format($vs_last_month, 1); ?>%
            </div>
        </div>
    </div>
</section>

<div class="card">
    <?php if (empty($expense_list)): ?>
        <div class="card-padded text-center">
            <p class="text-muted">No expense records found. Click "Add Expense" to get started!</p>
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
                <?php foreach ($expense_list as $expense): ?>
                <tr class="row-group">
                    <td><?php echo date('d M Y', strtotime($expense['date'])); ?></td>
                    <td>
                        <span class="badge <?php echo categoryBadge($expense['category_name'], 'expense'); ?>"><?php echo htmlspecialchars($expense['category_name']); ?></span>
                    </td>
                    <td><?php echo htmlspecialchars($expense['description'] ?? '-'); ?></td>
                    <td class="text-right amount-expense">-₹<?php echo number_format($expense['amount'], 2); ?></td>
                    <td class="text-center">
                        <div class="flex justify-center gap-sm row-actions">
                            <button class="btn btn-icon btn-outline" title="Edit" onclick="editExpense(<?php echo $expense['id']; ?>, <?php echo $expense['category_id']; ?>, <?php echo $expense['amount']; ?>, '<?php echo htmlspecialchars(addslashes($expense['description'] ?? '')); ?>', '<?php echo htmlspecialchars(addslashes($expense['date'])); ?>')">
                                <span class="material-symbols-outlined">edit</span>
                            </button>
                            <form method="POST" onsubmit="return confirmDelete('Delete this expense?')">
                                <input type="hidden" name="id" value="<?php echo $expense['id']; ?>">
                                <button type="submit" name="delete_expense" class="btn btn-icon btn-danger" title="Delete">
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
        <?php renderPagination($page, $page_count, $total_expenses, $per_page, $offset); ?>
    </div>
    <?php endif; ?>
</div>

<!-- Add Modal -->
<div id="addModal" class="modal-overlay hidden">
    <div class="modal-card">
        <div class="modal-header">
            <h2 class="modal-title">Add New Expense</h2>
            <button type="button" class="modal-close" data-close-modal="addModal">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        <form method="POST" id="addExpenseForm" onsubmit="return validateForm('addExpenseForm')">
            <div class="modal-body">
                <div class="form-group">
                    <label for="add_category">Category *</label>
                    <select id="add_category" name="category_id" required>
                        <option value="">Select Category</option>
                        <?php foreach ($expense_categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="add_amount">Amount (₹) *</label>
                    <input type="number" id="add_amount" name="amount" step="0.01" min="0.01" placeholder="0.00" required>
                </div>
                <div class="form-group">
                    <label for="add_description">Description</label>
                    <textarea id="add_description" name="description" rows="3" placeholder="What was this for?"></textarea>
                </div>
                <div class="form-group">
                    <label for="add_date">Date *</label>
                    <input type="date" id="add_date" name="date" value="<?php echo date('Y-m-d'); ?>" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" data-close-modal="addModal">Cancel</button>
                <button type="submit" name="add_expense" class="btn btn-primary">Save Expense</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Modal -->
<div id="editModal" class="modal-overlay hidden">
    <div class="modal-card">
        <div class="modal-header">
            <h2 class="modal-title">Edit Expense</h2>
            <button type="button" class="modal-close" data-close-modal="editModal">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        <form method="POST" id="editExpenseForm" onsubmit="return validateForm('editExpenseForm')">
            <input type="hidden" name="id" id="edit_id">
            <div class="modal-body">
                <div class="form-group">
                    <label for="edit_category">Category *</label>
                    <select id="edit_category" name="category_id" required>
                        <option value="">Select Category</option>
                        <?php foreach ($expense_categories as $cat): ?>
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
                <button type="submit" name="edit_expense" class="btn btn-primary">Update Expense</button>
            </div>
        </form>
    </div>
</div>

<script>
function editExpense(id, categoryId, amount, description, date) {
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_category').value = categoryId;
    document.getElementById('edit_amount').value = amount;
    document.getElementById('edit_description').value = description;
    document.getElementById('edit_date').value = date;
    document.getElementById('editModal').classList.remove('hidden');
}
</script>

<?php require_once '../includes/footer.php'; ?>