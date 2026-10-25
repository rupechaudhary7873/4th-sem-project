<?php
require_once '../includes/auth.php';
$page_title = 'Dashboard';
$active_nav = 'dashboard';

$current_month = date('m');
$current_year = date('Y');

$stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) as total FROM income WHERE user_id = ?");
$stmt->execute([$user_id]);
$total_income = $stmt->fetch()['total'];

$stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) as total FROM expenses WHERE user_id = ?");
$stmt->execute([$user_id]);
$total_expenses = $stmt->fetch()['total'];

$total_savings = $total_income - $total_expenses;

$stmt = $pdo->prepare("SELECT COALESCE(SUM(i.amount), 0) as total FROM income i WHERE user_id = ? AND MONTH(i.date) = ? AND YEAR(i.date) = ?");
$stmt->execute([$user_id, $current_month, $current_year]);
$monthly_income = $stmt->fetch()['total'];

$stmt = $pdo->prepare("SELECT COALESCE(SUM(e.amount), 0) as total FROM expenses e WHERE user_id = ? AND MONTH(e.date) = ? AND YEAR(e.date) = ?");
$stmt->execute([$user_id, $current_month, $current_year]);
$monthly_expenses = $stmt->fetch()['total'];

$stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) as total FROM budgets WHERE user_id = ? AND month = ? AND year = ?");
$stmt->execute([$user_id, $current_month, $current_year]);
$total_budget = $stmt->fetch()['total'];

$stmt = $pdo->prepare("
    SELECT i.id, i.amount, i.description, i.date, c.name as category, 'income' as type
    FROM income i
    JOIN categories c ON i.category_id = c.id
    WHERE i.user_id = ?
    UNION ALL
    SELECT e.id, e.amount, e.description, e.date, c.name as category, 'expense' as type
    FROM expenses e
    JOIN categories c ON e.category_id = c.id
    WHERE e.user_id = ?
    ORDER BY date DESC
    LIMIT 10
");
$stmt->execute([$user_id, $user_id]);
$recent_transactions = $stmt->fetchAll();

$stmt = $pdo->prepare("
    SELECT c.name, b.amount as budget_amount,
           COALESCE(SUM(e.amount), 0) as spent
    FROM budgets b
    JOIN categories c ON b.category_id = c.id
    LEFT JOIN expenses e ON e.category_id = c.id AND e.user_id = b.user_id
        AND MONTH(e.date) = b.month AND YEAR(e.date) = b.year
    WHERE b.user_id = ? AND b.month = ? AND b.year = ?
    GROUP BY b.id, c.name, b.amount
");
$stmt->execute([$user_id, $current_month, $current_year]);
$budgets = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? AND is_read = 0 ORDER BY created_at DESC LIMIT 5");
$stmt->execute([$user_id]);
$notifications = $stmt->fetchAll();

$monthly_savings = $monthly_income - $monthly_expenses;

// ---------- Analytics data ----------
$load_charts = true;

$monthly_trend = [];
$running_balance = 0;

$window = strtotime('-5 months', strtotime(date('Y-m-01')));
$window_start = date('Y-m-01', $window);

$stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM income WHERE user_id = ? AND date < ?");
$stmt->execute([$user_id, $window_start]);
$running_balance += (float)$stmt->fetchColumn();
$stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM expenses WHERE user_id = ? AND date < ?");
$stmt->execute([$user_id, $window_start]);
$running_balance -= (float)$stmt->fetchColumn();

for ($i = 5; $i >= 0; $i--) {
    $trend_month = date('m', strtotime("-$i months"));
    $trend_year = date('Y', strtotime("-$i months"));

    $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM income WHERE user_id = ? AND MONTH(date) = ? AND YEAR(date) = ?");
    $stmt->execute([$user_id, (int)$trend_month, (int)$trend_year]);
    $trend_income = (float)$stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM expenses WHERE user_id = ? AND MONTH(date) = ? AND YEAR(date) = ?");
    $stmt->execute([$user_id, (int)$trend_month, (int)$trend_year]);
    $trend_expense = (float)$stmt->fetchColumn();

    $running_balance += $trend_income - $trend_expense;

    $monthly_trend[] = [
        'label'   => date('M y', mktime(0, 0, 0, $trend_month, 1, $trend_year)),
        'income'  => $trend_income,
        'expense' => $trend_expense,
        'balance' => $running_balance,
    ];
}

function dashChartColors($rows, $palette) {
    $colors = [];
    $count = count($palette);
    foreach ($rows as $i => $row) {
        $colors[] = $palette[$i % $count];
    }
    return $colors;
}

$chart_palette = ['#2563EB', '#059669', '#D97706', '#DC2626', '#8B5CF6', '#0EA5E9', '#14B8A6', '#EC4899', '#F59E0B', '#6366F1'];

$stmt = $pdo->prepare("
    SELECT c.name, COALESCE(SUM(e.amount), 0) as total
    FROM expenses e
    JOIN categories c ON e.category_id = c.id
    WHERE e.user_id = ? AND MONTH(e.date) = ? AND YEAR(e.date) = ?
    GROUP BY c.name
    ORDER BY total DESC
");
$stmt->execute([$user_id, (int)$current_month, (int)$current_year]);
$expense_cats = $stmt->fetchAll();

$stmt = $pdo->prepare("
    SELECT c.name, COALESCE(SUM(i.amount), 0) as total
    FROM income i
    JOIN categories c ON i.category_id = c.id
    WHERE i.user_id = ? AND MONTH(i.date) = ? AND YEAR(i.date) = ?
    GROUP BY c.name
    ORDER BY total DESC
");
$stmt->execute([$user_id, (int)$current_month, (int)$current_year]);
$income_cats = $stmt->fetchAll();

$chart_data['balance'] = [
    'type' => 'line',
    'data' => [
        'labels' => array_column($monthly_trend, 'label'),
        'datasets' => [[
            'label' => 'Balance',
            'data' => array_column($monthly_trend, 'balance'),
            'borderColor' => '#2563EB',
            'backgroundColor' => 'rgba(37, 99, 235, 0.12)',
            'fill' => true,
            'tension' => 0.35,
            'borderWidth' => 2,
            'pointRadius' => 4,
            'pointBackgroundColor' => '#2563EB',
        ]],
    ],
    'options' => ['plugins' => ['legend' => ['display' => false]]],
];

$chart_data['trend'] = [
    'type' => 'bar',
    'data' => [
        'labels' => array_column($monthly_trend, 'label'),
        'datasets' => [
            [
                'label' => 'Income',
                'data' => array_column($monthly_trend, 'income'),
                'backgroundColor' => 'rgba(5, 150, 105, 0.75)',
                'borderRadius' => 4,
                'maxBarThickness' => 22,
            ],
            [
                'label' => 'Expenses',
                'data' => array_column($monthly_trend, 'expense'),
                'backgroundColor' => 'rgba(220, 38, 38, 0.75)',
                'borderRadius' => 4,
                'maxBarThickness' => 22,
            ],
        ],
    ],
];

$chart_data['expense_cats'] = [
    'type' => 'doughnut',
    'data' => [
        'labels' => array_column($expense_cats, 'name'),
        'datasets' => [[
            'data' => array_column($expense_cats, 'total'),
            'backgroundColor' => dashChartColors($expense_cats, $chart_palette),
            'borderColor' => '#FFFFFF',
            'borderWidth' => 2,
        ]],
    ],
    'options' => ['plugins' => ['legend' => ['position' => 'right']]],
];

$chart_data['income_cats'] = [
    'type' => 'doughnut',
    'data' => [
        'labels' => array_column($income_cats, 'name'),
        'datasets' => [[
            'data' => array_column($income_cats, 'total'),
            'backgroundColor' => dashChartColors($income_cats, $chart_palette),
            'borderColor' => '#FFFFFF',
            'borderWidth' => 2,
        ]],
    ],
    'options' => ['plugins' => ['legend' => ['position' => 'right']]],
];

require_once '../includes/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Dashboard Overview</h1>
        <p class="page-subtitle">Welcome back. Here's a summary of your financial health.</p>
    </div>
    <button type="button" class="btn btn-outline balance-toggle-btn" data-toggle-balance aria-pressed="false">
        <span class="material-symbols-outlined" data-balance-toggle-icon>visibility_off</span>
        <span data-balance-toggle-label>Hide Balance</span>
    </button>
</div>

<!-- Stat Cards -->
<section class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon income"><i class="fa-solid fa-file-invoice-dollar"></i></div>
        <div>
            <div class="stat-label">Total Income</div>
            <div class="stat-value sensitive-amount" data-sensitive-amount>₹<?php echo number_format($total_income, 2); ?></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon expense"></div>
        <div>
            <div class="stat-label">Total Expenses</div>
            <div class="stat-value sensitive-amount" data-sensitive-amount>₹<?php echo number_format($total_expenses, 2); ?></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon primary"><i class="fa-solid fa-piggy-bank"></i></div>
        <div>
            <div class="stat-label">Total Savings</div>
            <div class="stat-value sensitive-amount" data-sensitive-amount>₹<?php echo number_format($total_savings, 2); ?></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon primary"></div>
        <div>
            <div class="stat-label">Monthly Savings</div>
            <div class="stat-value sensitive-amount" data-sensitive-amount>₹<?php echo number_format($monthly_savings, 2); ?></div>
        </div>
    </div>
</section>

<!-- Monthly Overview + Income vs Expenses -->
<section class="grid-2">
    <div class="card">
        <div class="card-header">
            <div>
                <h2 class="card-title">Monthly Overview</h2>
                <div class="card-subtitle"><?php echo date('F Y'); ?></div>
            </div>
        </div>
        <div class="list-rows">
            <div class="list-row">
                <span class="row-label">Income</span>
                <span class="row-value" style="color: var(--income-green);">₹<?php echo number_format($monthly_income, 2); ?></span>
            </div>
            <div class="list-row">
                <span class="row-label">Expenses</span>
                <span class="row-value" style="color: var(--expense-red);">₹<?php echo number_format($monthly_expenses, 2); ?></span>
            </div>
            <div class="list-row">
                <span class="row-label">Budget Limit</span>
                <span class="row-value">₹<?php echo number_format($total_budget, 2); ?></span>
            </div>
            <div class="list-row">
                <span class="row-label">Remaining</span>
                <span class="row-value" style="color: var(--primary);">₹<?php echo number_format($total_budget - $monthly_expenses, 2); ?></span>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Income vs Expenses</h2>
        </div>
        <div class="card-padded">
            <?php $max_val = max($monthly_income, $monthly_expenses, 1); ?>
            <div class="mb-md">
                <div class="flex justify-between mb-sm">
                    <span class="stat-label">Income</span>
                    <span class="stat-label">₹<?php echo number_format($monthly_income, 2); ?></span>
                </div>
                <div class="progress">
                    <div class="progress-fill fill-income" style="width: <?php echo ($monthly_income / $max_val) * 100; ?>%;"></div>
                </div>
            </div>
            <div class="mb-md">
                <div class="flex justify-between mb-sm">
                    <span class="stat-label">Expenses</span>
                    <span class="stat-label">₹<?php echo number_format($monthly_expenses, 2); ?></span>
                </div>
                <div class="progress">
                    <div class="progress-fill fill-expense" style="width: <?php echo ($monthly_expenses / $max_val) * 100; ?>%;"></div>
                </div>
            </div>
            <?php $income_used = $monthly_income > 0 ? ($monthly_expenses / $monthly_income) * 100 : 0; ?>
            <div class="card-subtitle" style="margin-top:8px;">
                You have used <?php echo number_format($income_used, 0); ?>% of your total income this month. Keep an eye on discretionary spending to hit your savings goal.
            </div>
        </div>
    </div>
</section>

<!-- Budget Overview -->
<section class="mb-lg">
    <h2 class="card-title mb-md">Budget Overview</h2>
    <div class="budget-grid">
        <?php if (!empty($budgets)): ?>
            <?php foreach ($budgets as $budget):
                $spent = $budget['spent'];
                $budget_amt = $budget['budget_amount'];
                $percentage = $budget_amt > 0 ? ($spent / $budget_amt) * 100 : 0;
                $status_class = $percentage >= 100 ? 'expense' : ($percentage >= 80 ? 'warning' : 'income');
                $fill_class = $percentage >= 100 ? 'fill-expense' : ($percentage >= 80 ? 'fill-warning' : 'fill-income');
                $status_text = $percentage >= 100 ? 'Exceeded' : ($percentage >= 80 ? 'Warning' : 'On Track');
            ?>
            <div class="budget-card">
                <div class="budget-card-header">
                    <h3 class="budget-name"><?php echo htmlspecialchars($budget['name']); ?></h3>
                    <span class="badge badge-<?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                </div>
                <p class="text-muted" style="font-size:14px;">₹<?php echo number_format($spent, 2); ?> / ₹<?php echo number_format($budget_amt, 2); ?></p>
                <div class="progress">
                    <div class="progress-fill <?php echo $fill_class; ?>" style="width: <?php echo min($percentage, 100); ?>%;"></div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="card card-padded" style="grid-column: 1 / -1; text-align:center;">
                <p class="text-muted">No budgets set for this month. <a href="<?php echo BASE_URL; ?>/budget">Set a budget</a> to track your spending.</p>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- Analytics Charts -->
<section class="mb-lg">
    <h2 class="card-title mb-md">Analytics</h2>
    <div class="chart-grid-2">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title" style="font-size:16px;">Balance Trend</h3>
                <span class="card-subtitle">Last 6 months</span>
            </div>
            <div class="card-padded">
                <div class="chart-canvas">
                    <canvas data-chart data-chart-data="chartBalance"></canvas>
                </div>
            </div>
        </div>
        <div class="card">
            <div class="card-header">
                <h3 class="card-title" style="font-size:16px;">Income vs Expenses</h3>
                <span class="card-subtitle">Last 6 months</span>
            </div>
            <div class="card-padded">
                <div class="chart-canvas">
                    <canvas data-chart data-chart-data="chartTrend"></canvas>
                </div>
            </div>
        </div>
    </div>
    <div class="chart-grid-2">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title" style="font-size:16px;">Expense Breakdown</h3>
                <span class="card-subtitle"><?php echo date('F Y'); ?></span>
            </div>
            <div class="card-padded">
                <?php if (empty($expense_cats)): ?>
                    <p class="text-muted text-center">No expense data for this month.</p>
                <?php else: ?>
                <div class="chart-canvas-sm">
                    <canvas data-chart data-chart-data="chartExpenseCats"></canvas>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <div class="card">
            <div class="card-header">
                <h3 class="card-title" style="font-size:16px;">Income Breakdown</h3>
                <span class="card-subtitle"><?php echo date('F Y'); ?></span>
            </div>
            <div class="card-padded">
                <?php if (empty($income_cats)): ?>
                    <p class="text-muted text-center">No income data for this month.</p>
                <?php else: ?>
                <div class="chart-canvas-sm">
                    <canvas data-chart data-chart-data="chartIncomeCats"></canvas>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<!-- Recent Transactions -->
<section>
    <h2 class="card-title mb-md">Recent Transactions</h2>
    <div class="card">
        <?php if (empty($recent_transactions)): ?>
            <div class="card-padded text-center">
                <p class="text-muted">No transactions yet. Start by adding your first income or expense!</p>
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
                        <th>Type</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_transactions as $t): ?>
                    <tr>
                        <td><?php echo date('d M Y', strtotime($t['date'])); ?></td>
                        <td><?php echo htmlspecialchars($t['category']); ?></td>
                        <td><?php echo htmlspecialchars($t['description'] ?? '-'); ?></td>
                        <td class="text-right amount-<?php echo $t['type'] === 'income' ? 'income' : 'expense'; ?>">
                            <?php echo $t['type'] === 'income' ? '+' : '-'; ?>₹<?php echo number_format($t['amount'], 2); ?>
                        </td>
                        <td>
                            <span class="badge badge-<?php echo $t['type'] === 'income' ? 'income' : 'expense'; ?>">
                                <?php echo ucfirst($t['type']); ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</section>

<?php if (!empty($notifications)): ?>
<section class="mt-lg">
    <h2 class="card-title mb-md">Notifications</h2>
    <div class="card">
        <ul class="notification-list">
            <?php foreach ($notifications as $notif): ?>
            <li>
                <span class="notif-icon <?php echo $notif['type'] === 'warning' ? 'warning' : ($notif['type'] === 'success' ? 'success' : 'info'); ?>">
                    <span class="material-symbols-outlined">
                        <?php echo $notif['type'] === 'warning' ? 'warning' : ($notif['type'] === 'success' ? 'check_circle' : 'info'); ?>
                    </span>
                </span>
                <?php echo htmlspecialchars($notif['message']); ?>
                <span class="notif-date"><?php echo date('d M Y', strtotime($notif['created_at'])); ?></span>
            </li>
            <?php endforeach; ?>
        </ul>
    </div>
</section>
<?php endif; ?>

<script type="application/json" id="chartBalance"><?php echo json_encode($chart_data['balance']); ?></script>
<script type="application/json" id="chartTrend"><?php echo json_encode($chart_data['trend']); ?></script>
<?php if (!empty($expense_cats)): ?>
<script type="application/json" id="chartExpenseCats"><?php echo json_encode($chart_data['expense_cats']); ?></script>
<?php endif; ?>
<?php if (!empty($income_cats)): ?>
<script type="application/json" id="chartIncomeCats"><?php echo json_encode($chart_data['income_cats']); ?></script>
<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>