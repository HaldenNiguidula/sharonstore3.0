<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }

// Run only against a disposable test database; all fixtures are connection-local.
function currentRole(): string { return $GLOBALS['role']; }
function respond(bool $success, string $message = '', mixed $data = null): never {
    echo json_encode(['success' => $success, 'data' => $data]);
    exit;
}
if (($argv[1] ?? '') === '--worker') {
    $db = new PDO((string)getenv('TEST_DB_DSN'), getenv('TEST_DB_USER') ?: 'root', getenv('TEST_DB_PASS') ?: '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    if (!str_starts_with((string)$db->query('SELECT DATABASE()')->fetchColumn(), 'sharonstore_test_')) {
        throw new RuntimeException('Refusing a non-test database.');
    }
    $role = $argv[2];
    $local = $argv[3] === 'local';
    $db->exec("SET time_zone = '" . ($local ? '+08:00' : '+00:00') . "'");
    $db->exec('CREATE TEMPORARY TABLE tbl_users (user_id INT, full_name VARCHAR(80))');
    $db->exec('CREATE TEMPORARY TABLE tbl_shifts (shift_id INT, cashier_id INT, status VARCHAR(20), start_time DATETIME, starting_cash DECIMAL(12,2))');
    $db->exec('CREATE TEMPORARY TABLE tbl_transactions (shift_id INT, total_amount DECIMAL(12,2), status VARCHAR(20))');
    $db->exec("INSERT INTO tbl_users VALUES (2,'Own Cashier'),(3,'Other Cashier')");
    if ($argv[3] !== 'empty') {
        $ownTime = $local ? '2026-11-08 18:00:00' : '2026-11-08 10:00:00';
        $otherTime = $local ? '2026-11-08 18:26:57' : '2026-11-08 10:26:57';
        $insert = $db->prepare("INSERT INTO tbl_shifts VALUES (1,2,'open',?,100),(2,3,'open',?,500)");
        $insert->execute([$ownTime, $otherTime]);
        $db->exec("INSERT INTO tbl_transactions VALUES (1,12.50,'completed'),(2,25,'completed'),(2,99,'voided')");
    }
    $action = 'summary'; $userId = 2;
    $source = file_get_contents(__DIR__ . '/../api/shifts.php');
    $start = strpos($source, 'if ($action === \'summary\')');
    if ($start === false) throw new RuntimeException('Summary route not found.');
    // Execute the real summary route with isolated tables and the selected role.
    eval(substr($source, $start));
}

foreach ([['admin','utc'],['admin','local'],['cashier','utc'],['cashier','local'],['cashier','empty']] as [$role, $zone]) {
    $child = proc_open([PHP_BINARY, __FILE__, '--worker', $role, $zone], [1 => ['pipe','w'], 2 => ['pipe','w']], $pipes);
    $output = stream_get_contents($pipes[1]); $errors = stream_get_contents($pipes[2]);
    fclose($pipes[1]); fclose($pipes[2]);
    if (proc_close($child) !== 0) throw new RuntimeException($errors);
    $result = json_decode($output, true, 512, JSON_THROW_ON_ERROR);
    $data = $result['data'];
    if (!$result['success']) throw new RuntimeException('Summary failed.');
    if ($zone === 'empty') {
        if ($data['has_active_shift']) throw new RuntimeException('Empty summary reports a shift.');
    } else {
        $admin = $role === 'admin';
        $expectedTime = (new DateTimeImmutable($admin ? '2026-11-08T10:26:57Z' : '2026-11-08T10:00:00Z'))->getTimestamp();
        if ($data['start_time_unix'] !== $expectedTime || $data['shift_id'] !== ($admin ? 2 : 1)
            || $data['txn_count'] !== 1 || $data['expected_cash'] !== ($admin ? 525 : 112.5)) {
            throw new RuntimeException("Incorrect time, role scope or totals: $output");
        }
    }
    echo "PASS shift summary $role / $zone\n";
}
