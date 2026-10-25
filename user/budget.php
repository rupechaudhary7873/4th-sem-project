<?php
require_once '../includes/auth.php';
$page_title = 'Budget Management';
$active_nav = 'budget';

$message = '';
$message_type = '';

$current_month = date('m');
$current_year = date('Y');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['set_budget'])) {
        $category_id = (int)$_POST['category_id'];
        $amount = (float)$_POST['amount'];
        $month = (int)$_POST['month'];
        $year = (int)$_POST['year'];

        if ($category_id > 0 && $amount > 0 && $month >= 1 && $month <= 12 && $year >= 2020) {
            $check = $pdo->prepare("SELECT id FROM budgets WHERE user_id = ? AND category_id = ? AND month = ? AND year = ?");
            $check->execute([$user_id, $category_id, $month, $year]);

            if ($check->fetch()) {
                $stmt = $pdo->prepare("UPDATE budgets SET amount = ? WHERE user_id = ? AND category_id = ? AND month = ? AND year = ?");
                $stmt->execute([$amount, $user_id, $category_id, $month, $year]);
                $message = 'Budget updated successfully!';
                $message_type = 'success';
            } else {
                $stmt = $pdo->prepare("INSERT INTO budgets (user_id, category_id, month, year, amount) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$user_id, $category_id, $month, $year, $amount]);
                $message = 'Budget set successfully!';
                $message_type = 'success';
            }
        } else {
            $message = 'Please fill all required fields correctly';
            $message_type = 'danger';
        }
    } elseif (isset($_POST['delete_budget'])) {
        $id = (int)$_POST['id'];
        $stmt = $pdo->prepare("DELETE FROM budgets WHERE id = ? AND user_id = ?");
        if ($stmt->execute([$id, $user_id])) {
            $message = 'Budget deleted successfully!';
            $message_type = 'success';
        } else {
            $message = 'Failed to delete budget';
            $message_type = 'danger';
        }
    }
}

$filter_month = isset($_GET['month']) ? (int)$_GET['month'] : $current_month;
$filter_year = isset($_GET['year']) ? (int)$_GET['year'] : $current_year;

$stmt = $pdo->prepare("
    SELECT b.*, c.name as category_name,
           COALESCE(SUM(e.amount), 0) as spent,
           c.type
    FROM budgets b
    JOIN categories c ON b.category_id = c.id
    LEFT JOIN expenses e ON e.category_id = b.category_id
        AND e.user_id = b.user_id
        AND MONTH(e.date) = b.month
        AND YEAR(e.date) = b.year
    WHERE b.user_id = ? AND b.month = ? AND b.year = ?
    GROUP BY b.id, c.name, b.amount, c.type
    ORDER BY c.name
");
$stmt->execute([$user_id, $filter_month, $filter_year]);
$budgets = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT * FROM categories WHERE user_id = ? AND type = 'expense' ORDER BY name");
$stmt->execute([$user_id]);
$expense_categories = $stmt->fetchAll();

$total_budget = 0;
$total_spent = 0;
foreach ($budgets as $b) {
    $total_budget += $b['amount'];
    $total_spent += $b['spent'];
}

$overall_pct = $total_budget > 0 ? ($total_spent / $total_budget) * 100 : 0;

require_once '../includes/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Budget Management</h1>
        <p class="page-subtitle">Track and manage your spending limits for <?php echo date('F Y', mktime(0, 0, 0, $filter_month, 1, $filter_year)); ?>.</p>
    </div>
    <button class="btn btn-primary" data-open-modal="addModal">
        <span class="material-symbols-outlined">add</span>
        Set Budget
    </button>
</div>

<?php if ($message): ?>
    <div class="alert alert-<?php echo $message_type; ?>"><?php echo $message; ?></div>
<?php endif; ?>

<!-- Filter -->
<form method="GET" style="margin-bottom: 24px;">
    <div class="search-box">
        <label style="font-size:13px; color:var(--on-surface-variant);">Month:</label>
        <select name="month">
            <?php for ($m = 1; $m <= 12; $m++): ?>
                <option value="<?php echo $m; ?>" <?php echo $filter_month == $m ? 'selected' : ''; ?>>
                    <?php echo date('F', mktime(0, 0, 0, $m, 1)); ?>
                </option>
            <?php endfor; ?>
        </select>
        <label style="font-size:13px; color:var(--on-surface-variant);">Year:</label>
        <select name="year">
            <?php for ($y = date('Y'); $y >= 2020; $y--): ?>
                <option value="<?php echo $y; ?>" <?php echo $filter_year == $y ? 'selected' : ''; ?>><?php echo $y; ?></option>
            <?php endfor; ?>
        </select>
        <button type="submit" class="btn btn-outline btn-sm">Filter</button>
    </div>
</form>

<?php if (!empty($budgets)): ?>
<!-- Summary Bar -->
<section class="summary-grid" style="grid-template-columns: repeat(4, 1fr);">
    <div class="summary-card">
        <div class="stat-icon primary">
            <span class="material-symbols-outlined">account_balance</span>
        </div>
        <div>
            <div class="summary-label">Total Budget</div>
            <div class="summary-value">₹<?php echo number_format($total_budget, 2); ?></div>
        </div>
    </div>
    <div class="summary-card">
        <div class="stat-icon expense">
            <span class="material-symbols-outlined">shopping_cart</span>
        </div>
        <div>
            <div class="summary-label">Total Spent</div>
            <div class="summary-value">₹<?php echo number_format($total_spent, 2); ?></div>
        </div>
    </div>
    <div class="summary-card">
        <div class="stat-icon income">
            <span class="material-symbols-outlined">savings</span>
        </div>
        <div>
            <div class="summary-label">Remaining</div>
            <div class="summary-value">₹<?php echo number_format($total_budget - $total_spent, 2); ?></div>
        </div>
    </div>
    <div class="summary-card flex-col" style="align-items: stretch;">
        <div class="flex justify-between mb-sm">
            <div class="summary-label">Usage %</div>
            <span class="stat-label" style="color: <?php echo $overall_pct >= 100 ? 'var(--expense-red)' : ($overall_pct >= 80 ? 'var(--warning-amber)' : 'var(--income-green)'); ?>;">
                <?php echo number_format($overall_pct, 1); ?>%
            </span>
        </div>
        <div class="progress">
            <div class="progress-fill <?php echo $overall_pct >= 100 ? 'fill-expense' : ($overall_pct >= 80 ? 'fill-warning' : 'fill-income'); ?>" style="width: <?php echo min($overall_pct, 100); ?>%;"></div>
        </div>
        <div class="text-right mt-sm">
            <span class="badge <?php echo $overall_pct >= 100 ? 'badge-expense' : ($overall_pct >= 80 ? 'badge-warning' : 'badge-income'); ?>">
                <?php echo $overall_pct >= 100 ? 'Exceeded' : ($overall_pct >= 80 ? 'Warning' : 'On Track'); ?>
            </span>
        </div>
    </div>
</section>
<?php endif; ?>

<?php if (empty($budgets)): ?>
    <div class="card card-padded text-center">
        <p class="text-muted">No budgets set for this period. Click "Set Budget" to create one!</p>
    </div>
<?php else: ?>
<div class="budget-grid" style="grid-template-columns: repeat(3, 1fr);">
    <?php foreach ($budgets as $budget):
        $spent = $budget['spent'];
        $budget_amt = $budget['amount'];
        $percentage = $budget_amt > 0 ? ($spent / $budget_amt) * 100 : 0;
        $remaining = $budget_amt - $spent;

        if ($percentage >= 100) {
            $status_class = 'expense';
            $fill_class = 'fill-expense';
            $status_text = 'Exceeded';
        } elseif ($percentage >= 80) {
            $status_class = 'warning';
            $fill_class = 'fill-warning';
            $status_text = 'Warning';
        } else {
            $status_class = 'income';
            $fill_class = 'fill-income';
            $status_text = 'On Track';
        }
    ?>
    <article class="budget-card">
        <div class="budget-card-header">
            <div class="budget-card-name">
                <div class="budget-emoji"><?php echo $fill_class === 'fill-expense' ? '🚨' : ($fill_class === 'fill-warning' ? '⚠️' : '✅'); ?></div>
                <h3 class="budget-name"><?php echo htmlspecialchars($budget['category_name']); ?></h3>
            </div>
            <span class="badge badge-<?php echo $status_class; ?>"><?php echo $status_text; ?></span>
        </div>
        <div class="budget-amounts">
            <div>
                <p class="budget-label">Spent / Budget</p>
                <p class="budget-value"><strong style="color: <?php echo $percentage >= 100 ? 'var(--expense-red)' : 'var(--on-surface)'; ?>;">₹<?php echo number_format($spent, 2); ?></strong> / ₹<?php echo number_format($budget_amt, 2); ?></p>
            </div>
            <div class="text-right">
                <p class="budget-label">Remaining</p>
                <p class="budget-value" style="font-weight:700; color: <?php echo $remaining < 0 ? 'var(--expense-red)' : 'var(--on-surface)'; ?>;">₹<?php echo number_format($remaining, 2); ?></p>
            </div>
        </div>
        <div>
            <div class="flex justify-between mb-sm">
                <span class="stat-label" style="color: <?php echo $percentage >= 100 ? 'var(--expense-red)' : 'var(--on-surface-variant)'; ?>;"><?php echo number_format($percentage, 1); ?>% Used</span>
            </div>
            <div class="progress">
                <div class="progress-fill <?php echo $fill_class; ?>" style="width: <?php echo min($percentage, 100); ?>%;"></div>
            </div>
        </div>
        <div class="flex justify-end">
            <form method="POST" onsubmit="return confirmDelete('Delete this budget?')">
                <input type="hidden" name="id" value="<?php echo $budget['id']; ?>">
                <button type="submit" name="delete_budget" class="btn btn-danger btn-sm">Delete</button>
            </form>
        </div>
    </article>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Set Monthly Budget Modal -->
<div id="addModal" class="modal-overlay hidden">
    <div class="modal-card">
        <div class="modal-header">
            <h2 class="modal-title">Set Monthly Budget</h2>
            <button type="button" class="modal-close" data-close-modal="addModal">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        <form method="POST" id="budgetForm" onsubmit="return validateForm('budgetForm')">
            <div class="modal-body">
                <div class="form-group">
                    <label for="category_id">Category *</label>
                    <select id="category_id" name="category_id" required>
                        <option value="">Select a category</option>
                        <?php foreach ($expense_categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="amount">Amount (₹) *</label>
                    <input type="number" id="amount" name="amount" step="0.01" min="0.01" placeholder="e.g. 5000" required>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="month">Month *</label>
                        <select id="month" name="month" required>
                            <?php for ($m = 1; $m <= 12; $m++): ?>
                                <option value="<?php echo $m; ?>" <?php echo $m == $current_month ? 'selected' : ''; ?>>
                                    <?php echo date('F', mktime(0, 0, 0, $m, 1)); ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="year">Year *</label>
                        <select id="year" name="year" required>
                            <?php for ($y = date('Y'); $y <= date('Y') + 1; $y++): ?>
                                <option value="<?php echo $y; ?>" <?php echo $y == $current_year ? 'selected' : ''; ?>><?php echo $y; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" data-close-modal="addModal">Cancel</button>
                <button type="submit" name="set_budget" class="btn btn-primary">Save Budget</button>
            </div>
        </form>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>