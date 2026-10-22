<?php
// ============================================================
// Database Connection — Sharon Store System
// ============================================================
$rawHost = getenv('DB_HOST');
$rawPort = getenv('DB_PORT');
$rawUser = getenv('DB_USER');
$rawPass = getenv('DB_PASS');
$rawName = getenv('DB_NAME');

define('DB_HOST', $rawHost !== false ? trim($rawHost, " \t\n\r\0\x0B\"'") : 'localhost');
define('DB_PORT', $rawPort !== false ? trim($rawPort, " \t\n\r\0\x0B\"'") : '3306');
define('DB_USER', $rawUser !== false ? trim($rawUser, " \t\n\r\0\x0B\"'") : 'root');
define('DB_PASS', $rawPass !== false ? trim($rawPass, " \t\n\r\0\x0B\"'") : '');
define('DB_NAME', $rawName !== false ? trim($rawName, " \t\n\r\0\x0B\"'") : 'sharonstore_db');
define('DB_CHARSET', getenv('DB_CHARSET') ? trim(getenv('DB_CHARSET'), " \t\n\r\0\x0B\"'") : 'utf8mb4');

function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        // Remote DBs (TiDB, Aiven, Railway, etc.) require SSL
        if (DB_HOST !== 'localhost' && DB_HOST !== '127.0.0.1') {
            $ca = __DIR__ . '/cacert.pem';
            if (file_exists($ca) && defined('PDO::MYSQL_ATTR_SSL_CA')) {
                $options[PDO::MYSQL_ATTR_SSL_CA] = $ca;
            }
            if (defined('PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT')) {
                $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
            }
        }
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
            ensureSchema($pdo);
        } catch (PDOException $e) {
            http_response_code(500);
            if (!empty($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json')) {
                die(json_encode(['success' => false, 'message' => 'Database connection failed: ' . $e->getMessage()]));
            }
            ?>
            <!DOCTYPE html>
            <html lang="en">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>Database Connection — Sharon Store</title>
                <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
            </head>
            <body class="bg-light p-4">
                <div class="card shadow-sm mx-auto mt-4" style="max-width: 600px; border-radius: 14px;">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center gap-3 mb-3 text-danger">
                            <span style="font-size: 2rem;">⚠️</span>
                            <div>
                                <h4 class="mb-0 fw-bold">Database Connection Required</h4>
                                <small class="text-muted">Sharon Store Management System</small>
                            </div>
                        </div>
                        <p class="text-secondary mb-3">
                            Could not connect to MySQL database at <code><?= htmlspecialchars(DB_HOST) ?></code>:
                        </p>
                        <div class="alert alert-danger py-2 px-3 small font-monospace mb-4">
                            <?= htmlspecialchars($e->getMessage()) ?>
                        </div>
                        <?php if (getenv('VERCEL') || !empty($_SERVER['VERCEL'])): ?>
                        <div class="alert alert-info py-2 px-3 small">
                            <strong>Running on Vercel:</strong> Because Vercel serverless microVMs do not have a local MySQL server, please add your remote cloud MySQL credentials (e.g. from TiDB Cloud, Aiven, or Railway) in <strong>Vercel Dashboard &rarr; Project Settings &rarr; Environment Variables</strong>:
                            <ul class="mb-1 mt-2">
                                <li><code>DB_HOST</code> (cloud MySQL host)</li>
                                <li><code>DB_PORT</code> (default 3306)</li>
                                <li><code>DB_USER</code> (username)</li>
                                <li><code>DB_PASS</code> (password)</li>
                                <li><code>DB_NAME</code> (database name)</li>
                            </ul>
                        </div>
                        <?php else: ?>
                        <div class="alert alert-secondary py-2 px-3 small">
                            Ensure MySQL is running in your local XAMPP Control Panel and database <code>sharonstore_db</code> is created.
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </body>
            </html>
            <?php
            exit();
        }
    }
    return $pdo;
}

function getDailyManagerPIN(PDO $db): string {
    $today = date('Y-m-d');
    
    // Check if we have a PIN generated for today
    $stmt = $db->prepare("SELECT setting_value FROM tbl_settings WHERE setting_key = 'pin_date'");
    $stmt->execute();
    $pinDate = $stmt->fetchColumn();

    if ($pinDate === $today) {
        $stmt = $db->prepare("SELECT setting_value FROM tbl_settings WHERE setting_key = 'daily_pin'");
        $stmt->execute();
        return $stmt->fetchColumn() ?: '0000';
    }

    // Generate a new 4-digit PIN for today
    $newPin = str_pad((string)mt_rand(1000, 9999), 4, '0', STR_PAD_LEFT);

    // Save/Update settings
    $db->prepare("REPLACE INTO tbl_settings (setting_key, setting_value) VALUES ('pin_date', ?)")->execute([$today]);
    $db->prepare("REPLACE INTO tbl_settings (setting_key, setting_value) VALUES ('daily_pin', ?)")->execute([$newPin]);

    return $newPin;
}

// ============================================================
// Database Session Handler for Serverless Environments
// ============================================================
class DbSessionHandler implements SessionHandlerInterface {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function open(string $path, string $name): bool {
        return true;
    }

    public function close(): bool {
        return true;
    }

    public function read(string $id): string|false {
        try {
            $stmt = $this->pdo->prepare("SELECT data FROM tbl_sessions WHERE id = ? AND last_activity > ? LIMIT 1");
            $stmt->execute([$id, time() - 604800]); // 7 days validity
            $val = $stmt->fetchColumn();
            return $val !== false ? (string)$val : '';
        } catch (Throwable $e) {
            return '';
        }
    }

    public function write(string $id, string $data): bool {
        try {
            $stmt = $this->pdo->prepare("REPLACE INTO tbl_sessions (id, data, last_activity) VALUES (?, ?, ?)");
            return $stmt->execute([$id, $data, time()]);
        } catch (Throwable $e) {
            return false;
        }
    }

    public function destroy(string $id): bool {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM tbl_sessions WHERE id = ?");
            return $stmt->execute([$id]);
        } catch (Throwable $e) {
            return false;
        }
    }

    public function gc(int $max_lifetime): int|false {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM tbl_sessions WHERE last_activity < ?");
            $stmt->execute([time() - $max_lifetime]);
            return $stmt->rowCount();
        } catch (Throwable $e) {
            return false;
        }
    }
}

function startAppSession(): void {
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    ini_set('session.gc_maxlifetime', '604800'); // 7 days
    $isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

    session_set_cookie_params([
        'lifetime' => 604800,
        'path'     => '/',
        'secure'   => $isSecure,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);

    try {
        $pdo = getDB();
        $handler = new DbSessionHandler($pdo);
        session_set_save_handler($handler, true);
    } catch (Throwable $e) {
        if (is_dir('/tmp') && is_writable('/tmp')) {
            session_save_path('/tmp');
        }
    }

    @session_start();
}

function importSeedDataFromDump(PDO $pdo): void {
    $sqlFile = dirname(__DIR__) . '/sql/sharonstore.sql';
    if (!file_exists($sqlFile)) {
        return;
    }
    $content = file_get_contents($sqlFile);
    if (!$content) {
        return;
    }

    try {
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
    } catch (Throwable $e) {}

    $lines = explode("\n", $content);
    $current = '';

    foreach ($lines as $line) {
        $trimmed = trim($line);
        if ($trimmed === '' || str_starts_with($trimmed, '--') || str_starts_with($trimmed, '/*')) {
            continue;
        }
        if (str_starts_with($trimmed, 'LOCK TABLES') || str_starts_with($trimmed, 'UNLOCK TABLES')) {
            continue;
        }
        $current .= $line . "\n";
        if (str_ends_with($trimmed, ';')) {
            $stmt = trim($current);
            $current = '';

            if (stripos($stmt, 'DROP TABLE') === 0) {
                continue;
            }
            if (stripos($stmt, 'CREATE TABLE') === 0) {
                $stmt = preg_replace('/^CREATE TABLE\s+/i', 'CREATE TABLE IF NOT EXISTS ', $stmt);
            }
            if (stripos($stmt, 'INSERT INTO') === 0) {
                $stmt = 'INSERT IGNORE INTO ' . substr($stmt, 11);
            }

            try {
                $pdo->exec($stmt);
            } catch (Throwable $t) {
                // Ignore query warnings or duplicate entries
            }
        }
    }

    try {
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
    } catch (Throwable $e) {}
}

function ensureSchema(PDO $pdo): void {
    static $checked = false;
    if ($checked) return;
    $checked = true;

    try {
        // 1. Ensure tbl_sessions table exists for serverless session persistence
        $pdo->exec("CREATE TABLE IF NOT EXISTS `tbl_sessions` (
            `id` varchar(128) NOT NULL PRIMARY KEY,
            `data` mediumtext NOT NULL,
            `last_activity` int unsigned NOT NULL,
            INDEX (`last_activity`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

        // 2. Check if inventory or transactions have data
        $invCount = 0;
        try {
            $invCount = (int)$pdo->query("SELECT COUNT(*) FROM tbl_inventory")->fetchColumn();
        } catch (Throwable $e) {
            $invCount = 0;
        }

        $txnCount = 0;
        try {
            $txnCount = (int)$pdo->query("SELECT COUNT(*) FROM tbl_transactions")->fetchColumn();
        } catch (Throwable $e) {
            $txnCount = 0;
        }

        // If either inventory or transactions is empty, import all data from the dump!
        if ($invCount < 10 || $txnCount === 0) {
            importSeedDataFromDump($pdo);
        }
    } catch (Throwable $t) {
        error_log('ensureSchema warning: ' . $t->getMessage());
    }
}
