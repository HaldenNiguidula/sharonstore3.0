<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }

// No store connection: route checks use child processes and logging uses SQLite memory.
if (($argv[1] ?? '') === '--route') {
    $role = $argv[3] ?? '';
    $_SESSION = $role === '' ? [] : ['user_id' => 1, 'role' => $role];
    register_shutdown_function(static function (): void {
        fwrite(STDERR, (string)http_response_code());
    });
    require dirname(__DIR__) . '/' . $argv[2];
    exit;
}

function expectAudit(bool $ok, string $message): void {
    if (!$ok) throw new RuntimeException($message);
}

foreach (['audit.php', 'api/audit_logs.php'] as $route) {
    foreach (['', 'cashier', 'admin'] as $role) {
        $child = proc_open([PHP_BINARY, __FILE__, '--route', $route, $role],
            [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        if (!is_resource($child)) throw new RuntimeException('Could not start route check.');
        $body = stream_get_contents($pipes[1]);
        $status = stream_get_contents($pipes[2]);
        fclose($pipes[1]); fclose($pipes[2]);
        expectAudit(proc_close($child) === 0 && $status === '404', 'Audit route remains accessible: ' . $route);
        $expected = $route === 'audit.php' ? 'Not Found' : ['success' => false, 'message' => 'Not Found'];
        expectAudit(($route === 'audit.php' ? $body : json_decode($body, true)) === $expected,
            'Disabled route exposed unexpected content: ' . $route);
    }
}
echo "PASS audit viewer and reader return 404 for anonymous, cashier and admin requests\n";

$fixture = new PDO('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$fixture->exec('CREATE TABLE tbl_users (user_id INTEGER PRIMARY KEY)');
$fixture->exec('INSERT INTO tbl_users VALUES (1)');
$fixture->exec('CREATE TABLE tbl_audit_logs (
    log_id INTEGER PRIMARY KEY, user_id INTEGER NULL, action TEXT, module TEXT,
    description TEXT, ip_address TEXT, log_time TEXT DEFAULT CURRENT_TIMESTAMP
)');
function getDB(): PDO { return $GLOBALS['fixture']; }

// Exercise the actual production logger with an isolated connection.
$logger = file_get_contents(__DIR__ . '/../includes/audit.php');
$functionStart = strpos($logger, 'function logAudit(');
expectAudit($functionStart !== false, 'Production audit logger was not found.');
eval(substr($logger, $functionStart));
$_SERVER['REMOTE_ADDR'] = '192.0.2.15';
logAudit(1, 'COMPLETE_SALE', 'POS', "Test receipt with apostrophe: owner's item");
logAudit(null, 'REGISTER', 'Auth', 'Pending registration');
logAudit(999, 'LOGIN', 'Auth', 'Unavailable account');
$rows = $fixture->query('SELECT * FROM tbl_audit_logs ORDER BY log_id')->fetchAll(PDO::FETCH_ASSOC);
expectAudit(count($rows) === 3, 'Background recording did not retain all events.');
expectAudit((int)$rows[0]['user_id'] === 1 && $rows[0]['action'] === 'COMPLETE_SALE'
    && $rows[0]['module'] === 'POS' && $rows[0]['description'] === "Test receipt with apostrophe: owner's item"
    && $rows[0]['ip_address'] === '192.0.2.15' && $rows[0]['log_time'] !== '',
    'Background record lost event details.');
expectAudit($rows[1]['user_id'] === null && $rows[2]['user_id'] === null,
    'Anonymous or unavailable users were not recorded safely.');
echo "PASS background logger retains event, user, IP address and timestamp without viewer access\n";
