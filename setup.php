<?php
// ============================================================
// setup.php — One-time setup: imports DB and fixes passwords
// Run this ONCE at: http://localhost/sharonstore3.0/setup.php
// DELETE this file after running!
// ============================================================

// Prevent running in production accidentally
$secret = $_GET['key'] ?? '';
if ($secret !== 'sharonstore2024setup') {
    die('<h2>Access Denied. Add ?key=sharonstore2024setup to the URL.</h2>');
}

error_reporting(E_ALL);
ini_set('display_errors', 1);

$host   = 'localhost';
$user   = 'root';
$pass   = '';
$dbName = 'sharonstore_db';

echo '<html><head><title>Sharon Store Setup</title>
<style>
body { font-family: monospace; background:#0a1628; color:#e2e8f0; padding:30px; line-height:1.8; }
h2 { color: #10b981; }
.ok  { color: #10b981; }
.err { color: #f87171; }
.warn { color: #f59e0b; }
pre  { background:#112240; padding:16px; border-radius:8px; border:1px solid rgba(16,185,129,0.2); }
.done { font-size:1.4rem; font-weight:bold; color:#10b981; }
</style></head><body>';

echo '<h2>🌿 Sharon Store — Database Setup</h2>';
echo '<pre>';

// ── Step 1: Connect to MySQL ──────────────────────────────────
try {
    $pdo = new PDO("mysql:host={$host};charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    echo '<span class="ok">✓ Connected to MySQL</span>' . PHP_EOL;
} catch (PDOException $e) {
    echo '<span class="err">✗ MySQL Connection Failed: ' . htmlspecialchars($e->getMessage()) . '</span>';
    die('</pre></body></html>');
}

// ── Step 2: Create/Select Database ───────────────────────────
try {
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `{$dbName}`");
    echo '<span class="ok">✓ Database selected: ' . $dbName . '</span>' . PHP_EOL;
} catch (PDOException $e) {
    echo '<span class="err">✗ DB creation failed: ' . htmlspecialchars($e->getMessage()) . '</span>';
    die('</pre></body></html>');
}

// ── Step 3: Import SQL file ───────────────────────────────────
$sqlFile = __DIR__ . '/sql/sharonstore.sql';
if (!file_exists($sqlFile)) {
    echo '<span class="err">✗ SQL file not found: ' . $sqlFile . '</span>';
    die('</pre></body></html>');
}

$sql = file_get_contents($sqlFile);
echo '<span class="ok">✓ SQL file loaded (' . number_format(strlen($sql)) . ' bytes)</span>' . PHP_EOL;

// Split statements (handle DELIMITER changes for stored procedures)
$pdo->exec("SET FOREIGN_KEY_CHECKS = 0");

// Execute statement by statement (handle DELIMITER $$ blocks)
$lines     = explode("\n", $sql);
$statement = '';
$delimiter = ';';
$errors    = 0;
$executed  = 0;

foreach ($lines as $line) {
    $trimmed = trim($line);

    // Handle DELIMITER change
    if (preg_match('/^DELIMITER\s+(\S+)/i', $trimmed, $m)) {
        $delimiter = $m[1];
        continue;
    }

    $statement .= $line . "\n";

    // Check if statement ends with current delimiter
    if (str_ends_with(rtrim($statement), $delimiter)) {
        $stmt = rtrim($statement);
        // Remove trailing delimiter
        $stmt = substr($stmt, 0, strrpos($stmt, $delimiter));
        $stmt = trim($stmt);

        if ($stmt !== '' && !preg_match('/^--/', $stmt)) {
            try {
                $pdo->exec($stmt);
                $executed++;
            } catch (PDOException $e) {
                $msg = $e->getMessage();
                // Ignore "already exists" style warnings
                if (!str_contains($msg, '1050') && !str_contains($msg, '1007')) {
                    echo '<span class="warn">⚠ ' . htmlspecialchars(substr($msg, 0, 120)) . '</span>' . PHP_EOL;
                    $errors++;
                }
            }
        }
        $statement = '';
        $delimiter = ';';
    }
}

$pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
echo '<span class="ok">✓ SQL imported. Executed: ' . $executed . ' statements. Errors: ' . ($errors > 0 ? '<span class="err">' . $errors . '</span>' : '0') . '</span>' . PHP_EOL;

// ── Step 4: Fix / Insert users with correct bcrypt hashes ─────
echo PHP_EOL . '── Fixing user passwords ────────────────────────────' . PHP_EOL;

$users = [
    ['username' => 'admin',    'password' => 'admin123',   'full_name' => 'Sharon Dela Cruz', 'role' => 'admin'],
    ['username' => 'cashier1', 'password' => 'cashier123', 'full_name' => 'Maria Santos',      'role' => 'cashier'],
    ['username' => 'cashier2', 'password' => 'cashier123', 'full_name' => 'Jose Reyes',        'role' => 'cashier'],
];

$pdo->exec("DELETE FROM tbl_users WHERE username IN ('admin','cashier1','cashier2')");

$ins = $pdo->prepare("INSERT INTO tbl_users (username, password, full_name, role, is_active) VALUES (?,?,?,?,1)");
foreach ($users as $u) {
    $hash = password_hash($u['password'], PASSWORD_BCRYPT, ['cost' => 12]);
    $ins->execute([$u['username'], $hash, $u['full_name'], $u['role']]);
    echo '<span class="ok">✓ User created: ' . $u['username'] . ' / ' . $u['password'] . '</span>' . PHP_EOL;
}

// ── Step 5: Verify tables exist ───────────────────────────────
echo PHP_EOL . '── Verifying tables ─────────────────────────────────' . PHP_EOL;
$tables = ['tbl_users','tbl_categories','tbl_inventory','tbl_transactions','tbl_transaction_details','tbl_audit_logs','tbl_forecasts'];
foreach ($tables as $t) {
    try {
        $cnt = $pdo->query("SELECT COUNT(*) FROM `{$t}`")->fetchColumn();
        echo '<span class="ok">✓ ' . $t . ' (' . $cnt . ' rows)</span>' . PHP_EOL;
    } catch (PDOException $e) {
        echo '<span class="err">✗ ' . $t . ' — MISSING!</span>' . PHP_EOL;
    }
}

echo PHP_EOL . '<span class="done">🎉 Setup Complete!</span>' . PHP_EOL . PHP_EOL;
echo 'You can now log in at: <a style="color:#10b981" href="/sharonstore3.0/index.php">http://localhost/sharonstore3.0/index.php</a>' . PHP_EOL;
echo PHP_EOL . '<span class="warn">⚠ IMPORTANT: Delete this file (setup.php) after setup is complete!</span>';

echo '</pre></body></html>';
