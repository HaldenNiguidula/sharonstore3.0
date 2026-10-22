<?php
// ============================================================
// Database Connection — Sharon Store System
// ============================================================
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : '');
define('DB_NAME', getenv('DB_NAME') ?: 'sharonstore_db');
define('DB_CHARSET', getenv('DB_CHARSET') ?: 'utf8mb4');

function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        if (defined('PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT') && getenv('DB_SSL_NO_VERIFY')) {
            $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
        }
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
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
