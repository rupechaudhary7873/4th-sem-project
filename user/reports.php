<?php
require_once '../includes/auth.php';
$page_title = 'Financial Reports';
$active_nav = 'reports';
$load_charts = true;

$report_type = isset($_GET['type']) ? $_GET['type'] : 'monthly';

$year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');
$month = isset($_GET['month']) ? (int)$_GET['month'] : date('m');
$day = isset($_GET['day']) ? sanitize($_GET['day']) : date('Y-m-d');

if ($report_type === 'daily') {
    $stmt_inc = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) as total FROM income WHERE user_id = ? AND date = ?");
    $stmt_inc->execute([$user_id, $day]);
    $daily_income = $stmt_inc->fetch()['total'];

    $stmt_exp = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) as total FROM expenses WHERE user_id = ? AND date = ?");
    $stmt_exp->execute([$user_id, $day]);
    $daily_expense = $stmt_exp->fetch()['total'];

    $stmt = $pdo->prepare("
        SELECT 'income' as type, i.id, i.amount, i.description, i.date, c.name as category
        FROM income i JOIN categories c ON i.category_id = c.id
        WHERE i.user_id = ? AND i.date = ?
        UNION ALL
        SELECT 'expense' as type, e.id, e.amount, e.description, e.date, c.name as category
        FROM expenses e JOIN categories c ON e.category_id = c.id
        WHERE e.user_id = ? AND e.date = ?
        ORDER BY type, amount DESC
    ");
    $stmt->execute([$user_id, $day, $user_id, $day]);
    $transactions = $stmt->fetchAll();
} elseif ($report_type === 'monthly') {
    $stmt_inc = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) as total FROM income WHERE user_id = ? AND MONTH(date) = ? AND YEAR(date) = ?");
    $stmt_inc->execute([$user_id, $month, $year]);
    $monthly_income = $stmt_inc->fetch()['total'];

    $stmt_exp = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) as total FROM expenses WHERE user_id = ? AND MONTH(date) = ? AND YEAR(date) = ?");
    $stmt_exp->execute([$user_id, $month, $year]);
    $monthly_expense = $stmt_exp->fetch()['total'];

    $stmt = $pdo->prepare("
        SELECT c.name, COALESCE(SUM(e.amount), 0) as total
        FROM expenses e
        JOIN categories c ON e.category_id = c.id
        WHERE e.user_id = ? AND MONTH(e.date) = ? AND YEAR(e.date) = ?
        GROUP BY c.name
        ORDER BY total DESC
    ");
    $stmt->execute([$user_id, $month, $year]);
    $expense_breakdown = $stmt->fetchAll();

    $stmt = $pdo->prepare("
        SELECT c.name, COALESCE(SUM(i.amount), 0) as total
        FROM income i
        JOIN categories c ON i.category_id = c.id
        WHERE i.user_id = ? AND MONTH(i.date) = ? AND YEAR(i.date) = ?
        GROUP BY c.name
        ORDER BY total DESC
    ");
    $stmt->execute([$user_id, $month, $year]);
    $income_breakdown = $stmt->fetchAll();

    $monthly_trend = [];
    $running_balance = 0;
    for ($i = 5; $i >= 0; $i--) {
        $trend_ts = strtotime("-$i months", mktime(0, 0, 0, $month, 1, $year));
        $trend_month = (int)date('m', $trend_ts);
        $trend_year = (int)date('Y', $trend_ts);

        $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM income WHERE user_id = ? AND MONTH(date) = ? AND YEAR(date) = ?");
        $stmt->execute([$user_id, $trend_month, $trend_year]);
        $trend_income = (float)$stmt->fetchColumn();

        $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM expenses WHERE user_id = ? AND MONTH(date) = ? AND YEAR(date) = ?");
        $stmt->execute([$user_id, $trend_month, $trend_year]);
        $trend_expense = (float)$stmt->fetchColumn();

        $running_balance += $trend_income - $trend_expense;

        $monthly_trend[] = [
            'label'   => date('M y', $trend_ts),
            'income'  => $trend_income,
            'expense' => $trend_expense,
            'balance' => $running_balance,
        ];
    }

    $chart_monthly['trend'] = [
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

    $chart_monthly['balance'] = [
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
} else {
    $stmt_inc = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) as total FROM income WHERE user_id = ? AND YEAR(date) = ?");
    $stmt_inc->execute([$user_id, $year]);
    $yearly_income = $stmt_inc->fetch()['total'];

    $stmt_exp = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) as total FROM expenses WHERE user_id = ? AND YEAR(date) = ?");
    $stmt_exp->execute([$user_id, $year]);
    $yearly_expense = $stmt_exp->fetch()['total'];

    $monthly_data = [];
    for ($m = 1; $m <= 12; $m++) {
        $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) as total FROM income WHERE user_id = ? AND MONTH(date) = ? AND YEAR(date) = ?");
        $stmt->execute([$user_id, $m, $year]);
        $inc = $stmt->fetch()['total'];

        $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) as total FROM expenses WHERE user_id = ? AND MONTH(date) = ? AND YEAR(date) = ?");
        $stmt->execute([$user_id, $m, $year]);
        $exp = $stmt->fetch()['total'];

        $monthly_data[$m] = ['income' => $inc, 'expense' => $exp, 'savings' => $inc - $exp];
    }

    $yearly_labels = [];
    $yearly_incomes = [];
    $yearly_expenses = [];
    $yearly_savings = [];
    foreach ($monthly_data as $m => $d) {
        $yearly_labels[] = date('M', mktime(0, 0, 0, $m, 1));
        $yearly_incomes[] = $d['income'];
        $yearly_expenses[] = $d['expense'];
        $yearly_savings[] = $d['savings'];
    }

    $chart_yearly['trend'] = [
        'type' => 'bar',
        'data' => [
            'labels' => $yearly_labels,
            'datasets' => [
                [
                    'label' => 'Income',
                    'data' => $yearly_incomes,
                    'backgroundColor' => 'rgba(5, 150, 105, 0.75)',
                    'borderRadius' => 4,
                    'maxBarThickness' => 20,
                ],
                [
                    'label' => 'Expenses',
                    'data' => $yearly_expenses,
                    'backgroundColor' => 'rgba(220, 38, 38, 0.75)',
                    'borderRadius' => 4,
                    'maxBarThickness' => 20,
                ],
                [
                    'label' => 'Savings',
                    'type' => 'line',
                    'data' => $yearly_savings,
                    'borderColor' => '#2563EB',
                    'backgroundColor' => '#2563EB',
                    'tension' => 0.35,
                    'borderWidth' => 2,
                    'pointRadius' => 3,
                ],
            ],
        ],
    ];
}

if (isset($_GET['download']) && $_GET['download'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="report_' . $report_type . '_' . date('Y-m-d') . '.csv"');
    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

    if ($report_type === 'daily') {
        fputcsv($output, ['Category', 'Description', 'Amount', 'Type']);
        foreach ($transactions as $t) {
            fputcsv($output, [$t['category'], $t['description'] ?? '-', $t['amount'], $t['type']]);
        }
    } elseif ($report_type === 'monthly') {
        fputcsv($output, ['Income Category', 'Amount']);
        foreach ($income_breakdown as $item) {
            fputcsv($output, [$item['name'], $item['total']]);
        }
        fputcsv($output, []);
        fputcsv($output, ['Expense Category', 'Amount']);
        foreach ($expense_breakdown as $item) {
            fputcsv($output, [$item['name'], $item['total']]);
        }
    } else {
        fputcsv($output, ['Month', 'Income', 'Expenses', 'Savings']);
        foreach ($monthly_data as $m => $data) {
            fputcsv($output, [date('F', mktime(0, 0, 0, $m, 1)), $data['income'], $data['expense'], $data['savings']]);
        }
    }

    fclose($output);
    exit();
}

require_once '../includes/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Financial Reports</h1>
        <p class="page-subtitle">Detailed breakdown of your financial activities.</p>
    </div>
    <div class="flex flex-wrap items-center gap-md">
        <div class="tab-group">
            <a class="tab-btn <?php echo $report_type === 'daily' ? 'active' : ''; ?>" href="?type=daily&day=<?php echo $day; ?>">Daily</a>
            <a class="tab-btn <?php echo $report_type === 'monthly' ? 'active' : ''; ?>" href="?type=monthly&month=<?php echo $month; ?>&year=<?php echo $year; ?>">Monthly</a>
            <a class="tab-btn <?php echo $report_type === 'yearly' ? 'active' : ''; ?>" href="?type=yearly&year=<?php echo $year; ?>">Yearly</a>
        </div>
        <?php if ($report_type === 'daily'): ?>
            <a class="btn btn-primary" href="?type=daily&day=<?php echo $day; ?>&download=csv">
                <span class="material-symbols-outlined">download</span>
                Download CSV
            </a>
        <?php elseif ($report_type === 'monthly'): ?>
            <a class="btn btn-primary" href="?type=monthly&month=<?php echo $month; ?>&year=<?php echo $year; ?>&download=csv">
                <span class="material-symbols-outlined">download</span>
                Download CSV
            </a>
        <?php else: ?>
            <a class="btn btn-primary" href="?type=yearly&year=<?php echo $year; ?>&download=csv">
                <span class="material-symbols-outlined">download</span>
                Download CSV
            </a>
        <?php endif; ?>
    </div>
</div>

<!-- Filter -->
<form method="GET" style="margin-bottom: 24px;">
    <input type="hidden" name="type" value="<?php echo $report_type; ?>">
    <div class="search-box">
        <?php if ($report_type === 'daily'): ?>
            <label style="font-size:13px; color:var(--on-surface-variant);">Date:</label>
            <input type="date" name="day" value="<?php echo htmlspecialchars($day); ?>" onchange="this.form.submit()">
        <?php elseif ($report_type === 'monthly'): ?>
            <label style="font-size:13px; color:var(--on-surface-variant);">Month:</label>
            <select name="month" onchange="this.form.submit()">
                <?php for ($m = 1; $m <= 12; $m++): ?>
                    <option value="<?php echo $m; ?>" <?php echo $month == $m ? 'selected' : ''; ?>>
                        <?php echo date('F', mktime(0, 0, 0, $m, 1)); ?>
                    </option>
                <?php endfor; ?>
            </select>
            <label style="font-size:13px; color:var(--on-surface-variant);">Year:</label>
            <select name="year" onchange="this.form.submit()">
                <?php for ($y = date('Y'); $y >= 2020; $y--): ?>
                    <option value="<?php echo $y; ?>" <?php echo $year == $y ? 'selected' : ''; ?>><?php echo $y; ?></option>
                <?php endfor; ?>
            </select>
        <?php else: ?>
            <label style="font-size:13px; color:var(--on-surface-variant);">Year:</label>
            <select name="year" onchange="this.form.submit()">
                <?php for ($y = date('Y'); $y >= 2020; $y--): ?>
                    <option value="<?php echo $y; ?>" <?php echo $year == $y ? 'selected' : ''; ?>><?php echo $y; ?></option>
                <?php endfor; ?>
            </select>
        <?php endif; ?>
    </div>
</form>

<?php if ($report_type === 'daily'): ?>
    <?php $savings = $daily_income - $daily_expense; ?>
    <!-- Daily Summary -->
    <section class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon income"><span class="material-symbols-outlined">arrow_downward</span></div>
            <div>
                <div class="stat-label">Income Today</div>
                <div class="stat-value">₹<?php echo number_format($daily_income, 2); ?></div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon expense"><span class="material-symbols-outlined">arrow_upward</span></div>
            <div>
                <div class="stat-label">Expenses Today</div>
                <div class="stat-value">₹<?php echo number_format($daily_expense, 2); ?></div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon primary"><span class="material-symbols-outlined">savings</span></div>
            <div>
                <div class="stat-label">Net Today</div>
                <div class="stat-value" style="color: <?php echo $savings >= 0 ? 'var(--income-green)' : 'var(--expense-red)'; ?>;">
                    <?php echo $savings >= 0 ? '+' : '-'; ?>₹<?php echo number_format(abs($savings), 2); ?>
                </div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon warning"><span class="material-symbols-outlined">receipt_long</span></div>
            <div>
                <div class="stat-label">Transactions</div>
                <div class="stat-value"><?php echo count($transactions); ?></div>
            </div>
        </div>
    </section>

    <!-- Daily Transactions -->
    <h2 class="card-title mb-md">Today's Transactions (<?php echo date('d F Y', strtotime($day)); ?>)</h2>
    <div class="card">
        <?php if (empty($transactions)): ?>
            <div class="card-padded text-center">
                <p class="text-muted">No transactions on this day.</p>
            </div>
        <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Category</th>
                        <th>Description</th>
                        <th>Type</th>
                        <th class="text-right">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($transactions as $t): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($t['category']); ?></td>
                        <td><?php echo htmlspecialchars($t['description'] ?? '-'); ?></td>
                        <td>
                            <span class="badge badge-<?php echo $t['type'] === 'income' ? 'income' : 'expense'; ?>">
                                <?php echo ucfirst($t['type']); ?>
                            </span>
                        </td>
                        <td class="text-right amount-<?php echo $t['type'] === 'income' ? 'income' : 'expense'; ?>">
                            <?php echo $t['type'] === 'income' ? '+' : '-'; ?>₹<?php echo number_format($t['amount'], 2); ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

<?php elseif ($report_type === 'monthly'): ?>
    <?php $savings = $monthly_income - $monthly_expense; ?>
    <?php $savings_rate = $monthly_income > 0 ? ($savings / $monthly_income) * 100 : 0; ?>
    <!-- Monthly Summary -->
    <section class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon income"><span class="material-symbols-outlined">arrow_downward</span></div>
            <div>
                <div class="stat-label">Total Income (<?php echo date('M', mktime(0, 0, 0, $month, 1)); ?>)</div>
                <div class="stat-value">₹<?php echo number_format($monthly_income, 2); ?></div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon expense"><span class="material-symbols-outlined">arrow_upward</span></div>
            <div>
                <div class="stat-label">Total Expenses (<?php echo date('M', mktime(0, 0, 0, $month, 1)); ?>)</div>
                <div class="stat-value">₹<?php echo number_format($monthly_expense, 2); ?></div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon primary"><span class="material-symbols-outlined">savings</span></div>
            <div>
                <div class="stat-label">Total Savings</div>
                <div class="stat-value" style="color: <?php echo $savings >= 0 ? 'var(--income-green)' : 'var(--expense-red)'; ?>;">
                    <?php echo $savings >= 0 ? '+' : '-'; ?>₹<?php echo number_format(abs($savings), 2); ?>
                </div>
            </div>
        </div>
        <div class="stat-card flex-col" style="align-items: stretch;">
            <div class="flex justify-between mb-sm">
                <div class="stat-label">Savings Rate</div>
                <span class="stat-label" style="color:var(--income-green);"><?php echo number_format($savings_rate, 1); ?>%</span>
            </div>
            <div class="stat-value" style="font-size:20px;"><?php echo number_format($savings_rate, 1); ?>%</div>
            <div class="progress progress-sm mt-sm">
                <div class="progress-fill fill-income" style="width: <?php echo min($savings_rate, 100); ?>%;"></div>
            </div>
        </div>
    </section>

    <!-- Income vs Expenses + Breakdowns -->
    <div class="grid-2">
        <div class="card">
            <div class="card-header"><h2 class="card-title">Income vs Expenses</h2></div>
            <div class="card-padded">
                <?php $max_val = max($monthly_income, $monthly_expense, 1); ?>
                <div class="chart-bar">
                    <div class="bar-label">Income</div>
                    <div class="bar-track">
                        <div class="bar-fill income-bar" style="width: <?php echo ($monthly_income / $max_val) * 100; ?>%;">
                            ₹<?php echo number_format($monthly_income, 2); ?>
                        </div>
                    </div>
                </div>
                <div class="chart-bar">
                    <div class="bar-label">Expenses</div>
                    <div class="bar-track">
                        <div class="bar-fill expense-bar" style="width: <?php echo ($monthly_expense / $max_val) * 100; ?>%;">
                            ₹<?php echo number_format($monthly_expense, 2); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h2 class="card-title">Savings Overview</h2></div>
            <div class="card-padded">
                <div class="list-rows" style="padding:0;">
                    <div class="list-row">
                        <span class="row-label">Income</span>
                        <span class="row-value" style="color:var(--income-green);">₹<?php echo number_format($monthly_income, 2); ?></span>
                    </div>
                    <div class="list-row">
                        <span class="row-label">Expenses</span>
                        <span class="row-value" style="color:var(--expense-red);">₹<?php echo number_format($monthly_expense, 2); ?></span>
                    </div>
                    <div class="list-row">
                        <span class="row-label">Savings</span>
                        <span class="row-value" style="color:<?php echo $savings >= 0 ? 'var(--income-green)' : 'var(--expense-red)'; ?>;">₹<?php echo number_format($savings, 2); ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="grid-2">
        <div class="card">
            <div class="card-header"><h2 class="card-title">Income Breakdown</h2></div>
            <div class="card-padded">
                <?php if (empty($income_breakdown)): ?>
                    <p class="text-muted text-center">No income data</p>
                <?php else: ?>
                    <?php $max_inc = max(array_column($income_breakdown, 'total'), [1])[0]; ?>
                    <?php foreach ($income_breakdown as $item): ?>
                    <div class="chart-bar">
                        <div class="bar-label"><?php echo htmlspecialchars($item['name']); ?></div>
                        <div class="bar-track">
                            <div class="bar-fill income-bar" style="width: <?php echo ($item['total'] / $max_inc) * 100; ?>%;">
                                ₹<?php echo number_format($item['total'], 2); ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h2 class="card-title">Expense Breakdown</h2></div>
            <div class="card-padded">
                <?php if (empty($expense_breakdown)): ?>
                    <p class="text-muted text-center">No expense data</p>
                <?php else: ?>
                    <?php $max_exp = max(array_column($expense_breakdown, 'total'), [1])[0]; ?>
                    <?php foreach ($expense_breakdown as $item): ?>
                    <div class="chart-bar">
                        <div class="bar-label"><?php echo htmlspecialchars($item['name']); ?></div>
                        <div class="bar-track">
                            <div class="bar-fill expense-bar" style="width: <?php echo ($item['total'] / $max_exp) * 100; ?>%;">
                                ₹<?php echo number_format($item['total'], 2); ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="chart-grid-2">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title" style="font-size:16px;">Income vs Expenses Trend</h3>
                <span class="card-subtitle">Last 6 months ending <?php echo date('M Y', mktime(0, 0, 0, $month, 1, $year)); ?></span>
            </div>
            <div class="card-padded">
                <div class="chart-canvas">
                    <canvas data-chart data-chart-data="chartMonthlyTrend"></canvas>
                </div>
            </div>
        </div>
        <div class="card">
            <div class="card-header">
                <h3 class="card-title" style="font-size:16px;">Running Balance</h3>
                <span class="card-subtitle">Cumulative net of the same period</span>
            </div>
            <div class="card-padded">
                <div class="chart-canvas">
                    <canvas data-chart data-chart-data="chartMonthlyBalance"></canvas>
                </div>
            </div>
        </div>
    </div>

<?php else: ?>
    <?php $savings = $yearly_income - $yearly_expense; ?>
    <!-- Yearly Summary -->
    <section class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon income"><span class="material-symbols-outlined">arrow_downward</span></div>
            <div>
                <div class="stat-label">YTD Income</div>
                <div class="stat-value">₹<?php echo number_format($yearly_income, 2); ?></div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon expense"><span class="material-symbols-outlined">arrow_upward</span></div>
            <div>
                <div class="stat-label">YTD Expenses</div>
                <div class="stat-value">₹<?php echo number_format($yearly_expense, 2); ?></div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon primary"><span class="material-symbols-outlined">savings</span></div>
            <div>
                <div class="stat-label">YTD Savings</div>
                <div class="stat-value" style="color: <?php echo $savings >= 0 ? 'var(--income-green)' : 'var(--expense-red)'; ?>;">
                    <?php echo $savings >= 0 ? '+' : '-'; ?>₹<?php echo number_format(abs($savings), 2); ?>
                </div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon warning"><span class="material-symbols-outlined">timeline</span></div>
            <div>
                <div class="stat-label">Avg Monthly Savings</div>
                <div class="stat-value">₹<?php echo number_format($savings / 12, 2); ?></div>
            </div>
        </div>
    </section>

    <!-- Yearly Table -->
    <h2 class="card-title mb-md">Monthly Breakdown (<?php echo $year; ?>)</h2>
    <div class="card">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Month</th>
                        <th class="text-right">Income</th>
                        <th class="text-right">Expenses</th>
                        <th class="text-right">Savings</th>
                        <th class="text-right">Savings Rate</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($monthly_data as $m => $data): ?>
                    <tr>
                        <td><strong><?php echo date('F', mktime(0, 0, 0, $m, 1)); ?></strong></td>
                        <td class="text-right amount-income">₹<?php echo number_format($data['income'], 2); ?></td>
                        <td class="text-right amount-expense">₹<?php echo number_format($data['expense'], 2); ?></td>
                        <td class="text-right amount-<?php echo $data['savings'] >= 0 ? 'income' : 'expense'; ?>">
                            ₹<?php echo number_format($data['savings'], 2); ?>
                        </td>
                        <td class="text-right">
                            <?php if ($data['income'] > 0): ?>
                                <span class="badge <?php echo ($data['savings'] / $data['income']) >= 0.2 ? 'badge-income' : 'badge-warning'; ?>">
                                    <?php echo number_format(($data['savings'] / $data['income']) * 100, 1); ?>%
                                </span>
                            <?php else: ?>
                                <span class="badge badge-user">-</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
</tbody>
        </table>
    </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title" style="font-size:16px;">Monthly Income vs Expenses (<?php echo $year; ?>)</h3>
        </div>
        <div class="card-padded">
            <div class="chart-canvas">
                <canvas data-chart data-chart-data="chartYearlyTrend"></canvas>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php if ($report_type === 'monthly'): ?>
<script type="application/json" id="chartMonthlyTrend"><?php echo json_encode($chart_monthly['trend']); ?></script>
<script type="application/json" id="chartMonthlyBalance"><?php echo json_encode($chart_monthly['balance']); ?></script>
<?php elseif ($report_type === 'yearly'): ?>
<script type="application/json" id="chartYearlyTrend"><?php echo json_encode($chart_yearly['trend']); ?></script>
<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>