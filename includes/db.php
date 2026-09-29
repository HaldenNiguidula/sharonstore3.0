<?php
// ============================================================
// Database Connection — Sharon Store System
// ============================================================
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'sharonstore_db');
define('DB_CHARSET', 'utf8mb4');

function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            http_response_code(500);
            die(json_encode(['success' => false, 'message' => 'Database connection failed: ' . $e->getMessage()]));
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
