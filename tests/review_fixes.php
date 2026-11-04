<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__ . '/../includes/inventory_stock.php';
require_once __DIR__ . '/../includes/account_access.php';
require_once __DIR__ . '/../includes/supplier_categories.php';
require_once __DIR__ . '/../includes/sales_reports.php';

function expectFix(bool $ok, string $message): void {
    if (!$ok) throw new RuntimeException($message);
}
function testConnection(): PDO {
    $dsn = (string)getenv('TEST_DB_DSN');
    if ($dsn === '') throw new RuntimeException('TEST_DB_DSN must name an isolated sharonstore_test_ database.');
    $db = new PDO($dsn, getenv('TEST_DB_USER') ?: 'root', getenv('TEST_DB_PASS') ?: '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES => false]);
    if (!str_starts_with((string)$db->query('SELECT DATABASE()')->fetchColumn(), 'sharonstore_test_')) throw new RuntimeException('Refusing a non-test database.');
    return $db;
}
$db = testConnection();
if (($argv[1] ?? '') === '--worker') {
    echo $db->query('SELECT CONNECTION_ID()')->fetchColumn() . "\n";
    fflush(STDOUT);
    if ($argv[2] === 'stock') {
        echo json_encode(adjustInventoryStock($db, 1, 'add', 5));
    } else {
        try {
            $db->beginTransaction();
            $locked = lockAccountForChange($db, 2, 1);
            guardAccountAccess($locked, 2, 'cashier', 1);
            $db->exec("UPDATE tbl_users SET role='cashier' WHERE user_id=1");
            $db->commit();
            echo 'CHANGED';
        } catch (RuntimeException $e) {
            if ($db->inTransaction()) $db->rollBack();
            echo 'DENIED';
        }
    }
    exit;
}
function workerWaitingOnLock(PDO $db, int $id): bool {
    $stmt = $db->prepare('SELECT INFO FROM information_schema.PROCESSLIST WHERE ID = ?');
    for ($i = 0; $i < 60; $i++) {
        $stmt->execute([$id]);
        $query = $stmt->fetchColumn();
        if (is_string($query) && str_contains(strtoupper($query), 'FOR UPDATE')) return true;
        usleep(25000);
    }
    return false;
}
function startFixWorker(string $scenario): array {
    $child = proc_open([PHP_BINARY, __FILE__, '--worker', $scenario], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
    if (!is_resource($child)) throw new RuntimeException('Could not start test worker.');
    stream_set_timeout($pipes[1], 10);
    $id = (int)fgets($pipes[1]);
    return [$child, $pipes, $id];
}
function finishFixWorker($child, array $pipes): string {
    $out = stream_get_contents($pipes[1]); $errors = stream_get_contents($pipes[2]);
    fclose($pipes[1]); fclose($pipes[2]);
    expectFix(proc_close($child) === 0, 'Worker failed: ' . $errors);
    return $out;
}

// These tables are confined to the explicitly selected disposable test database.
$db->exec('CREATE TABLE IF NOT EXISTS tbl_inventory (item_id INT PRIMARY KEY, item_name VARCHAR(100), stock_qty DECIMAL(12,2), is_active INT) ENGINE=InnoDB');
$db->exec('DELETE FROM tbl_inventory');
$db->exec("INSERT INTO tbl_inventory VALUES (1,'Concurrent stock',10,1)");
$db->beginTransaction();
try {
    $db->exec('UPDATE tbl_inventory SET stock_qty=stock_qty-2 WHERE item_id=1');
    [$child, $pipes, $id] = startFixWorker('stock');
    $waited = workerWaitingOnLock($db, $id);
    $db->commit();
    $result = json_decode(finishFixWorker($child, $pipes), true, 512, JSON_THROW_ON_ERROR);
    expectFix($waited && (float)$result['previous_qty'] === 8.0 && (float)$result['new_qty'] === 13.0, 'Adjustment lost a concurrent sale.');
} finally { if ($db->inTransaction()) $db->rollBack(); }
expectFix((float)adjustInventoryStock($db, 1, 'subtract', 20)['new_qty'] === 0.0, 'Subtract allowed negative stock.');
expectFix((float)adjustInventoryStock($db, 1, 'set', 2.5)['new_qty'] === 2.5, 'Fractional stock was lost.');
try { adjustInventoryStock($db, 99, 'add', 1); throw new LogicException('Missing item accepted.'); }
catch (RuntimeException $e) { expectFix(!$db->inTransaction(), 'Failed adjustment leaked a transaction.'); }
echo "PASS overlapping sale and stock adjustment, subtract/set, missing item rollback\n";

$db->exec('CREATE TABLE IF NOT EXISTS tbl_users (user_id INT PRIMARY KEY, username VARCHAR(50), role VARCHAR(20), is_active INT) ENGINE=InnoDB');
$db->exec('DELETE FROM tbl_users');
$db->exec("INSERT INTO tbl_users VALUES (1,'admin1','admin',1),(2,'admin2','admin',1),(3,'cashier','cashier',1)");
foreach ([['cashier', 1, false], ['admin', 0, false], ['admin', 1, true]] as [$role, $active, $deleting]) {
    $db->beginTransaction();
    try {
        $locked = lockAccountForChange($db, 1, 1);
        try { guardAccountAccess($locked, 1, $role, $active, $deleting); throw new LogicException('Self-lockout accepted.'); }
        catch (RuntimeException $e) { expectFix(str_contains($e->getMessage(), 'own administrator'), 'Wrong self-access failure.'); }
    } finally { $db->rollBack(); }
}
$db->beginTransaction();
try {
    $locked = lockAccountForChange($db, 1, 2);
    guardAccountAccess($locked, 1, 'cashier', 1);
    $db->exec("UPDATE tbl_users SET role='cashier' WHERE user_id=2");
    [$child, $pipes, $id] = startFixWorker('admin');
    $waited = workerWaitingOnLock($db, $id);
    $db->commit();
    expectFix($waited && finishFixWorker($child, $pipes) === 'DENIED', 'Competing changes removed both administrators.');
} finally { if ($db->inTransaction()) $db->rollBack(); }
expectFix((int)$db->query("SELECT COUNT(*) FROM tbl_users WHERE role='admin' AND is_active=1")->fetchColumn() === 1, 'No active administrator remains.');
$db->beginTransaction();
try {
    $locked = lockAccountForChange($db, 1, 3);
    guardAccountAccess($locked, 1, 'cashier', 0);
    guardAccountAccess($locked, 1, 'cashier', 1, true);
} finally { $db->rollBack(); }
echo "PASS self-deactivation/demotion/delete blocked, concurrent administrator changes, ordinary cashier changes\n";

$known = ['Snacks, Biscuits & Sweets', 'Rice', 'Rice, Grains & Staples', 'Beverages & Refreshments'];
$legacy = 'Snacks, Biscuits & Sweets, Beverages & Refreshments, Rice, Grains & Staples';
expectFix(supplierCategories($legacy, $known) === [$known[0], $known[3], $known[2]], 'Legacy full category names were split.');
$categories = [$known[0], 'Custom, with commas', "Farmer's \"Fresh\" Goods", $known[0]];
$saved = supplierCategoriesForSave(['categories' => $categories], $known);
expectFix(supplierCategories($saved, $known) === array_slice($categories, 0, 3), 'JSON categories did not round trip.');
expectFix(supplierCategoriesForSave(['categories' => []], $known) === null, 'Categories could not be cleared.');
try { supplierCategoriesForSave(['categories' => [str_repeat('x', 501)]], $known); throw new LogicException('Oversized categories accepted.'); }
catch (InvalidArgumentException $e) {}
echo "PASS legacy categories, comma/quote names, multiple category round trip and clearing\n";

$db->exec('CREATE TABLE IF NOT EXISTS tbl_transactions (transaction_id INT AUTO_INCREMENT PRIMARY KEY, transaction_date DATETIME, total_amount DECIMAL(12,2), status VARCHAR(20)) ENGINE=InnoDB');
$db->exec('DELETE FROM tbl_transactions');
$db->exec("INSERT INTO tbl_transactions (transaction_date,total_amount,status) VALUES
 ('2026-10-05 23:59:59',100,'completed'),('2026-10-06',2,'completed'),('2026-11-04 23:59:59',3,'completed'),
 ('2026-11-05',500,'completed'),('2026-11-01',1000,'voided')");
$daily = salesReportRows($db, 'daily', new DateTimeImmutable('2026-11-04'));
expectFix(count($daily) === 30 && $daily[0]['raw_date'] === '2026-11-04' && $daily[29]['raw_date'] === '2026-10-06', 'Wrong calendar window.');
expectFix(array_sum(array_column($daily, 'total')) === 5.0 && $daily[1]['total'] === 0.0, 'Old/future/voided sales included or zero day missing.');
$db->exec('DELETE FROM tbl_transactions');
$db->exec("INSERT INTO tbl_transactions (transaction_date,total_amount,status) VALUES ('2025-12-29',2,'completed'),('2026-01-01',3,'completed')");
$weekly = salesReportRows($db, 'weekly', new DateTimeImmutable('2026-01-05'));
expectFix(count($weekly) === 12 && $weekly[1]['raw_date'] === '2026-01' && $weekly[1]['total'] === 5.0, 'Year boundary split a calendar week.');
$monthly = salesReportRows($db, 'monthly', new DateTimeImmutable('2026-01-05'));
expectFix(count($monthly) === 12 && $monthly[11]['raw_date'] === '2025-02' && array_sum(array_column($monthly, 'total')) === 5.0, 'Wrong monthly window.');
echo "PASS 30 calendar days, zero sales, date cutoffs, void exclusion, ISO week/year and 12-month windows\n";
