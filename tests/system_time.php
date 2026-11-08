<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
date_default_timezone_set('Europe/Berlin');
require_once __DIR__ . '/../includes/time.php';
require_once __DIR__ . '/../includes/sales_reports.php';
require_once __DIR__ . '/../includes/forecast_history.php';
$root = dirname(__DIR__);
function expectTime(bool $condition, string $message): void { if (!$condition) throw new RuntimeException($message); }
function timeSourceFunction(string $path, string $name): string {
    $tokens = token_get_all(file_get_contents($path)); $capture = false; $depth = 0; $opened = false; $code = '';
    foreach ($tokens as $i => $token) {
        if (!$capture && is_array($token) && $token[0] === T_FUNCTION) {
            $j = $i + 1;
            while (isset($tokens[$j]) && is_array($tokens[$j]) && $tokens[$j][0] === T_WHITESPACE) $j++;
            if (is_array($tokens[$j]) && $tokens[$j][1] === $name) $capture = true;
        }
        if (!$capture) continue;
        $code .= is_array($token) ? $token[1] : $token;
        if ($token === '{') { $depth++; $opened = true; }
        if (is_array($token) && in_array($token[0], [T_CURLY_OPEN, T_DOLLAR_OPEN_CURLY_BRACES], true)) $depth++;
        if ($token === '}' && --$depth === 0 && $opened) return $code;
    }
    throw new RuntimeException('Function not found: ' . $name);
}
class TimeResponse extends RuntimeException { public function __construct(public array $data) { parent::__construct('Captured response'); } }
function jsonOut(array $data, int $status = 200): never { throw new TimeResponse($data); }
function respond(bool $success, string $message = '', mixed $data = null): never { throw new TimeResponse(['success' => $success, 'data' => $data]); }
function getDB(): PDO { return $GLOBALS['db']; }
function currentUserId(): int { return 1; }
function currentUserName(): string { return 'Time Test'; }
function isAdmin(): bool { return false; }
function logAudit(?int $user, string $action, string $module, string $description): void {}
function timeCall(string $function, array $args = []): array {
    try { $function(...$args); } catch (TimeResponse $response) { return $response->data; }
    throw new RuntimeException('No response');
}
expectTime(date_default_timezone_get() === 'Asia/Manila', 'PHP timezone did not initialize');
$dsn = (string)getenv('TEST_DB_DSN');
if ($dsn === '') throw new RuntimeException('TEST_DB_DSN must select an isolated sharonstore_test_ database.');
$db = new PDO($dsn, getenv('TEST_DB_USER') ?: 'root', getenv('TEST_DB_PASS') ?: '', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES => false
]);
if (!str_starts_with((string)$db->query('SELECT DATABASE()')->fetchColumn(), 'sharonstore_test_')) throw new RuntimeException('Refusing a non-test database.');
foreach ([
    'tbl_users (user_id INT PRIMARY KEY, full_name VARCHAR(80), username VARCHAR(80) DEFAULT \'test\', role VARCHAR(20) DEFAULT \'cashier\', is_active INT DEFAULT 1, created_at TIMESTAMP NULL)',
    'tbl_categories (category_id INT PRIMARY KEY, category_name VARCHAR(80))',
    'tbl_inventory (item_id INT PRIMARY KEY, item_name VARCHAR(80), category_id INT, price DECIMAL(12,2), stock_qty DECIMAL(12,2), low_stock_threshold INT, expiry_date DATE NULL, is_active INT, unit VARCHAR(20))',
    'tbl_shifts (shift_id INT PRIMARY KEY, cashier_id INT, status VARCHAR(20))',
    'tbl_transactions (transaction_id INT AUTO_INCREMENT PRIMARY KEY, transaction_code VARCHAR(80) UNIQUE, user_id INT, shift_id INT NULL, total_amount DECIMAL(12,2), amount_tendered DECIMAL(12,2), change_amount DECIMAL(12,2), transaction_date DATETIME, status VARCHAR(20) DEFAULT \'completed\')',
    'tbl_transaction_details (detail_id INT AUTO_INCREMENT PRIMARY KEY, transaction_id INT, item_id INT, quantity DECIMAL(12,2), unit_price DECIMAL(12,2), subtotal DECIMAL(12,2))'
] as $ddl) $db->exec('CREATE TEMPORARY TABLE ' . $ddl . ' ENGINE=InnoDB');
$db->exec("INSERT INTO tbl_users (user_id,full_name) VALUES (1,'Time Test')");
$db->exec("INSERT INTO tbl_categories VALUES (1,'Test')");
$db->exec("INSERT INTO tbl_inventory VALUES (1,'Test Product',1,10,100,5,NULL,1,'pieces')");
$db->exec("INSERT INTO tbl_shifts VALUES (1,1,'open')");
foreach (['peso','handleDailySummary','handleCompleteSale','handleRecentTransactions'] as $name) eval(timeSourceFunction($root . '/api/transactions.php', $name));
foreach (['getDashboardStats','getDashboardSalesTrend','getDashboardCategorySales'] as $name) eval(timeSourceFunction($root . '/api/dashboard.php', $name));
$forecastSource = file_get_contents($root . '/api/forecasting.php');
preg_match('/\$histStmt = \$db->prepare\("[\s\S]*?"\);/', $forecastSource, $forecastQuery);
$reportSource = file_get_contents($root . '/api/reports.php');
$whereStart = strpos($reportSource, '    $where =');
$whereEnd = strpos($reportSource, '    try {', $whereStart);
$reportBuilder = substr($reportSource, $whereStart, $whereEnd - $whereStart);
$accountSource = file_get_contents($root . '/api/accounts.php');
$listStart = strpos($accountSource, 'if ($action === \'list\')');
$listEnd = strpos($accountSource, 'if ($action === \'get\')');
$accountList = substr($accountSource, $listStart, $listEnd - $listStart);

foreach (['+00:00', '+08:00'] as $nativeZone) {
    $db->exec("SET time_zone = '{$nativeZone}'");
    foreach (['2026-11-08T16:00:00Z', '2026-11-30T16:00:00Z', '2026-01-04T16:00:00Z', '2026-12-31T16:00:00Z'] as $boundary) {
        $instant = (new DateTimeImmutable($boundary))->getTimestamp();
        $db->exec('SET timestamp = ' . ($instant + 1800));
        $db->exec('DELETE FROM tbl_transaction_details'); $db->exec('DELETE FROM tbl_transactions');
        $insert = $db->prepare("INSERT INTO tbl_transactions (transaction_code,user_id,total_amount,amount_tendered,change_amount,transaction_date) VALUES (?,1,?,?,0,FROM_UNIXTIME(?))");
        foreach ([[$instant - 60, 10], [$instant + 60, 20]] as $i => [$timestamp, $amount]) {
            $insert->execute(['TIME-' . $i, $amount, $amount, $timestamp]);
            $id = (int)$db->lastInsertId();
            $db->exec("INSERT INTO tbl_transaction_details (transaction_id,item_id,quantity,unit_price,subtotal) VALUES ({$id},1,{$amount},1,{$amount})");
        }
        $stored = $db->query('SELECT transaction_date, UNIX_TIMESTAMP(transaction_date) AS instant FROM tbl_transactions ORDER BY transaction_id')->fetchAll();
        expectTime((int)$stored[1]['instant'] === $instant + 60, 'Stored instant changed');
        $db->exec('UPDATE tbl_users SET created_at = FROM_UNIXTIME(' . ($instant + 60) . ') WHERE user_id = 1');
        $action = 'list';
        try { eval($accountList); } catch (TimeResponse $response) {
            expectTime($response->data['data'][0]['created_at'] === appTime($instant + 60)->format('Y-m-d H:i:s'), 'Account creation date is not PHT');
        }
        expectTime($db->query('SELECT ' . appTodaySql())->fetchColumn() === appTime($instant)->format('Y-m-d'), 'Database business date differs from PHT');
        expectTime((float)getDashboardStats($db)['today_revenue'] === 20.0, 'Dashboard used native database midnight');
        expectTime((float)timeCall('handleDailySummary')['total_sales'] === 20.0, 'Daily summary used native database midnight');
        $trend = getDashboardSalesTrend($db);
        expectTime($trend['labels'] === [appTime($instant - 60)->format('Y-m-d'), appTime($instant + 60)->format('Y-m-d')], 'Dashboard dates did not split at PHT midnight');
        foreach (['daily' => 'Y-m-d', 'weekly' => 'o-W', 'monthly' => 'Y-m'] as $type => $format) {
            $samePeriod = appTime($instant - 60)->format($format) === appTime($instant + 60)->format($format);
            $expected = $samePeriod ? 30.0 : 20.0;
            $rows = salesReportRows($db, $type, appTime($instant));
            expectTime($rows[0]['total'] === $expected, 'Wrong PHT report total for ' . $type);
            $raw_date = $rows[0]['raw_date'];
            eval($reportBuilder);
            $stmt = $db->prepare($sql); $stmt->execute($params);
            $breakdown = $stmt->fetchAll();
            expectTime((float)$breakdown[0]['total_revenue'] === $expected, 'Item breakdown disagrees with report');
        }
        $periods = 3; $monthsBack = 2; $itemId = 1;
        $localTransactionTime = appLocalSql('t.transaction_date');
        eval($forecastQuery[0]); $histStmt->execute([':id' => $itemId]);
        $history = forecastHistory($histStmt->fetchAll(), $periods, appTime($instant));
        $sameMonth = appTime($instant - 60)->format('Y-m') === appTime($instant)->format('Y-m');
        expectTime($history[2]['qty_sold'] === ($sameMonth ? 30.0 : 20.0), 'Forecast grouped by native database month');
        expectTime(array_sum(array_column($history, 'qty_sold')) === 30.0, 'Forecast history lost a sale at month boundary');
        expectTime($db->query('SELECT @@session.time_zone')->fetchColumn() === $nativeZone, 'Application changed database storage timezone');
    }
    $db->exec('SET timestamp = 0');
    $sale = timeCall('handleCompleteSale', [['items' => [['item_id' => 1, 'quantity' => 1]], 'amount_tendered' => 10]]);
    expectTime($sale['success'] && str_ends_with($sale['transaction_date'], '+08:00'), 'Receipt has no explicit Philippine offset');
    $stmt = $db->prepare('SELECT UNIX_TIMESTAMP(transaction_date) FROM tbl_transactions WHERE transaction_id = ?');
    $stmt->execute([$sale['transaction_id']]);
    expectTime((int)$stmt->fetchColumn() === $sale['transaction_time_unix'], 'Receipt and stored sale timestamps differ');
    expectTime(strtotime($sale['transaction_date']) === $sale['transaction_time_unix'], 'Receipt ISO date differs from saved instant');
    $recent = timeCall('handleRecentTransactions');
    expectTime(str_ends_with($recent['transactions'][0]['transaction_date'], '+08:00'), 'Recent sale timestamp lacks timezone');
    echo "PASS PHT midnight/week/month/year, Dashboard, summaries, reports/breakdowns, forecast calendar and saved receipt time on {$nativeZone} database\n";
}
