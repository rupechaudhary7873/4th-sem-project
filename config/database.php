<?php
session_start();

define('DB_HOST', getenv('BUDGET_DB_HOST') ?: 'localhost');
define('DB_NAME', 'budget_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_PORT', getenv('BUDGET_DB_PORT') ?: 3306);

$dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
if (getenv('BUDGET_DB_PORT')) {
    $dsn .= ";port=" . DB_PORT;
}

try {
    $pdo = new PDO(
        $dsn,
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

$base_path = str_replace($_SERVER['DOCUMENT_ROOT'], '', dirname(__DIR__));
define('BASE_URL', rtrim(str_replace('\\', '/', $base_path), '/'));

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function redirectIfNotLoggedIn() {
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . '/login');
        exit();
    }
}

function sanitize($input) {
    return strip_tags(trim((string)$input));
}

function isValidDate($date, $format = 'Y-m-d') {
    if (!is_string($date) || $date === '') return false;
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}

// Color-coded category pill badge classes, matching the reference mockups
function categoryBadge($name, $type = 'expense') {
    if ($type === 'income') {
        $map = [
            'salary' => 'primary',
            'bonus' => 'primary',
            'business' => 'primary',
            'investment' => 'income',
            'freelance' => 'warning',
            'other income' => 'secondary',
            'dividend' => 'income',
        ];
    } else {
        $map = [
            'food' => 'primary',
            'dining' => 'primary',
            'transportation' => 'warning',
            'housing' => 'primary',
            'utilities' => 'warning',
            'entertainment' => 'expense',
            'shopping' => 'warning',
            'rent' => 'primary',
            'healthcare' => 'expense',
            'education' => 'secondary',
            'other expense' => 'user',
        ];
    }
    $key = strtolower(trim($name));
    foreach ($map as $needle => $class) {
        if (strpos($key, $needle) !== false) {
            return 'badge-' . $class;
        }
    }
    return $type === 'income' ? 'badge-primary' : 'badge-expense';
}

// Pagination helper: returns [total_count, page_count, page, per_page, offset]
function paginate($pdo, $table, $user_id = null) {
    $per_page = 8;
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;

    if ($user_id !== null) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM $table WHERE user_id = ?");
        $stmt->execute([$user_id]);
    } else {
        $stmt = $pdo->query("SELECT COUNT(*) FROM $table");
    }
    $total = (int)$stmt->fetchColumn();
    $page_count = max(1, (int)ceil($total / $per_page));
    $page = min($page, $page_count);
    $offset = ($page - 1) * $per_page;

    return [$total, $page_count, $page, $per_page, $offset];
}

// Render a pagination footer block
function renderPagination($page, $page_count, $total, $per_page, $startFrom) {
    $start = $total === 0 ? 0 : $startFrom + 1;
    $end = min($startFrom + $per_page, $total);

    $qs = $_GET;
    $build = function ($p) use ($qs) {
        $qs['page'] = $p;
        return '?' . http_build_query($qs);
    };

    echo '<div class="table-footer">';
    echo '<span class="font-body-sm text-body-sm text-on-surface-variant">Showing ' . $start . ' to ' . $end . ' of ' . $total . ' entries</span>';
    echo '<div class="pagination">';
    echo '<a class="page-btn ' . ($page <= 1 ? 'disabled' : '') . '" href="' . htmlspecialchars($build($page - 1)) . '">Prev</a>';
    for ($i = 1; $i <= $page_count; $i++) {
        echo '<a class="page-btn ' . ($i === $page ? 'active' : '') . '" href="' . htmlspecialchars($build($i)) . '">' . $i . '</a>';
    }
    echo '<a class="page-btn ' . ($page >= $page_count ? 'disabled' : '') . '" href="' . htmlspecialchars($build($page + 1)) . '">Next</a>';
    echo '</div>';
    echo '</div>';
}
