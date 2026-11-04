<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }

// Run with C:\xampp\php\php.exe tests/regression.php.
// All fixtures use connection-local TEMPORARY tables; store tables are never modified.
$root = dirname(__DIR__);
function sourceFunction(string $path, string $name): string {
    $tokens = token_get_all(file_get_contents($path));
    $capture = false; $depth = 0; $opened = false; $code = '';
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
    throw new RuntimeException("Function not found: $name");
}
function getDB(): PDO { return $GLOBALS['fixture']; }
function currentUserId(): int { return 1; }
function isAdmin(): bool { return ($GLOBALS['scenario'] ?? '') === 'void'; }
function currentUserName(): string { return 'Test Cashier'; }
function logAudit(?int $id, string $action, string $module, string $description): void {}
function check(bool $ok, string $message): void {
    if (!$ok) throw new RuntimeException($message);
}

if (($argv[1] ?? '') === '--worker') {
    $scenario = $argv[2];
    $fixture = new PDO(getenv('TEST_DB_DSN') ?: 'mysql:host=127.0.0.1;dbname=sharonstore_db;charset=utf8mb4', 'root', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES => false]);
    foreach ([
        'tbl_users (user_id INT PRIMARY KEY, username VARCHAR(80), full_name VARCHAR(80), role VARCHAR(20), is_active INT)',
        'tbl_inventory (item_id INT PRIMARY KEY, item_name VARCHAR(80), stock_qty DECIMAL(12,2), price DECIMAL(12,2), unit VARCHAR(20), is_active INT, expiry_date DATE NULL)',
        'tbl_shifts (shift_id INT PRIMARY KEY, cashier_id INT, status VARCHAR(20))',
        'tbl_transactions (transaction_id INT AUTO_INCREMENT PRIMARY KEY, transaction_code VARCHAR(80) UNIQUE, user_id INT, shift_id INT NULL, total_amount DECIMAL(12,2), amount_tendered DECIMAL(12,2), change_amount DECIMAL(12,2), transaction_date DATETIME, status VARCHAR(20) DEFAULT \'completed\')',
        'tbl_transaction_details (detail_id INT AUTO_INCREMENT PRIMARY KEY, transaction_id INT, item_id INT, quantity DECIMAL(12,2), unit_price DECIMAL(12,2), subtotal DECIMAL(12,2))',
        'tbl_sessions (id VARCHAR(128) PRIMARY KEY, data MEDIUMTEXT, last_activity INT)',
    ] as $table) $fixture->exec('CREATE TEMPORARY TABLE ' . $table . ' ENGINE=InnoDB');
    $fixture->exec("INSERT INTO tbl_users VALUES (1,'cashier','Test Cashier','cashier',1)");
    $fixture->exec("INSERT INTO tbl_inventory VALUES (1,'Test Item',5,10,'pieces',1,NULL)");
    if ($scenario === 'expired') $fixture->exec("UPDATE tbl_inventory SET expiry_date = DATE_SUB(CURDATE(), INTERVAL 1 DAY)");
    if ($scenario === 'expires_today') $fixture->exec("UPDATE tbl_inventory SET expiry_date = CURDATE()");
    if ($scenario !== 'no_shift') $fixture->exec("INSERT INTO tbl_shifts VALUES (1,1,'open')");
    if (str_starts_with($scenario, 'auth_')) {
        $_SESSION = ['user_id' => 1, 'username' => 'cashier', 'role' => 'admin'];
        if ($scenario === 'auth_disabled') $fixture->exec('UPDATE tbl_users SET is_active=0');
        if ($scenario === 'auth_missing') $fixture->exec('DELETE FROM tbl_users');
        eval(sourceFunction($root . '/includes/auth_check.php', 'syncSessionUser'));
        syncSessionUser();
        check($scenario === 'auth_demoted' ? $_SESSION['role'] === 'cashier' : !isset($_SESSION['user_id']), 'Account access was not refreshed');
        echo 'PASS'; exit;
    }
    if ($scenario === 'restore') {
        eval(sourceFunction($root . '/includes/backup.php', 'restoreDatabaseFromFile'));
        $file = tempnam(sys_get_temp_dir(), 'restore_test_');
        try {
            file_put_contents($file, "UPDATE tbl_inventory SET stock_qty=4;\nINVALID SQL;\n");
            check(!restoreDatabaseFromFile($file, $fixture)['success'], 'Partial restore reported success');
            file_put_contents($file, "UPDATE tbl_inventory SET stock_qty=5;\n");
            check(restoreDatabaseFromFile($file, $fixture)['success'], 'Valid restore failed');
            file_put_contents($file, '-- empty backup');
            check(!restoreDatabaseFromFile($file, $fixture)['success'], 'Empty restore reported success');
        } finally { unlink($file); }
        echo 'PASS'; exit;
    }
    eval(sourceFunction($root . '/api/transactions.php', 'jsonOut'));
    if ($scenario === 'void') {
        // Execute the endpoint's conditional claim twice, as competing voids do.
        $fixture->exec("INSERT INTO tbl_transactions (transaction_code,total_amount,transaction_date) VALUES ('CLAIM',10,NOW())");
        preg_match('/"(UPDATE tbl_transactions SET status = \'voided\'[^"\n]+)"/', sourceFunction($root . '/api/transactions.php', 'handleVoidTransaction'), $claimSql);
        $claim = $fixture->prepare($claimSql[1]);
        $claim->execute([':id'=>1]);
        check($claim->rowCount() === 1, 'First void could not claim transaction');
        $claim->execute([':id'=>1]);
        check($claim->rowCount() === 0, 'Second void claimed transaction again');
        $fixture->exec('DELETE FROM tbl_transactions');
        $fixture->exec("INSERT INTO tbl_transactions (transaction_code,total_amount,transaction_date,status) VALUES ('TEST',10,NOW(),'voided')");
        eval(sourceFunction($root . '/api/transactions.php', 'handleVoidTransaction'));
        register_shutdown_function(function () use ($fixture) { check((float)$fixture->query('SELECT stock_qty FROM tbl_inventory')->fetchColumn() === 5.0, 'Double void changed stock'); });
        handleVoidTransaction(['transaction_id' => (int)$fixture->lastInsertId()]);
    }
    eval(sourceFunction($root . '/api/transactions.php', 'handleCompleteSale'));
    register_shutdown_function(function () use ($fixture, $scenario) {
        if ($fixture->inTransaction()) $fixture->rollBack();
        $expected = $scenario === 'price' ? 4.0 : 5.0;
        check((float)$fixture->query('SELECT stock_qty FROM tbl_inventory')->fetchColumn() === $expected, 'Unexpected stock after checkout');
        check((int)$fixture->query('SELECT COUNT(*) FROM tbl_transactions')->fetchColumn() === ($scenario === 'price' ? 1 : 0), 'Failed checkout left sale records');
    });
    $items = [['item_id'=>1, 'quantity'=>1, 'unit_price'=>0]];
    if ($scenario === 'duplicate') $items = [['item_id'=>1,'quantity'=>3], ['item_id'=>1,'quantity'=>3]];
    if ($scenario === 'insufficient') $items[0]['quantity'] = 6;
    handleCompleteSale(['items'=>$items,'amount_tendered'=>100]);
}

require $root . '/includes/forecast_history.php';
$history = forecastHistory([['month_key'=>'2026-08','qty_sold'=>30], ['month_key'=>'2026-10','qty_sold'=>30], ['month_key'=>'2020-01','qty_sold'=>900]], 3, new DateTimeImmutable('2026-10-31'));
check(array_column($history, 'qty_sold') === [30.0,0.0,30.0], 'Forecast must include zero months and exclude old history');
check(array_sum(array_column($history, 'qty_sold')) / 3 === 20.0, 'Wrong SMA');
check(array_column(forecastHistory([], 3, new DateTimeImmutable('2026-01-31')), 'month_key') === ['2025-11','2025-12','2026-01'], 'Year boundary failed');
echo "PASS forecast gaps, empty history, month/year boundaries\n";
foreach (['price','duplicate','insufficient','expired','expires_today','no_shift','void','auth_disabled','auth_demoted','auth_missing','restore'] as $scenario) {
    $process = proc_open([PHP_BINARY, __FILE__, '--worker', $scenario], [1=>['pipe','w'], 2=>['pipe','w']], $pipes);
    $output = stream_get_contents($pipes[1]); $errors = stream_get_contents($pipes[2]);
    fclose($pipes[1]); fclose($pipes[2]);
    check(proc_close($process) === 0, "$scenario failed: $errors $output");
    if ($scenario === 'price') {
        $data = json_decode($output, true, 512, JSON_THROW_ON_ERROR);
        check($data['success'] && (float)$data['grand_total'] === 10.0 && (float)$data['items'][0]['unit_price'] === 10.0, 'Browser price was accepted');
    } elseif (in_array($scenario, ['duplicate','insufficient','expired','expires_today','no_shift','void'], true)) {
        check(json_decode($output, true, 512, JSON_THROW_ON_ERROR)['success'] === false, "$scenario should be rejected");
    } else check($output === 'PASS', "$scenario failed: $output");
    echo "PASS $scenario\n";
}
