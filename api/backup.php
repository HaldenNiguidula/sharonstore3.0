<?php
// ============================================================
// Backup API — Sharon Store System
// Supports Local Backup & Google Drive Cloud Backup (Apps Script Webhook)
// ============================================================
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/audit.php';

requireAdmin();

$backupDir = __DIR__ . '/../backups/';
if (!is_dir($backupDir)) mkdir($backupDir, 0755, true);

$db = getDB();
$userId = currentUserId();
$action = $_GET['action'] ?? $_POST['action'] ?? '';

// ── Settings Helpers ──────────────────────────────────────────
function getSetting(PDO $db, string $key, string $default = ''): string {
    $stmt = $db->prepare("SELECT setting_value FROM tbl_settings WHERE setting_key = ?");
    $stmt->execute([$key]);
    $val = $stmt->fetchColumn();
    return $val !== false && $val !== null ? (string)$val : $default;
}

function setSetting(PDO $db, string $key, string $value): void {
    try {
        $db->exec("ALTER TABLE tbl_settings MODIFY setting_value TEXT NULL");
    } catch (\Throwable $e) {}
    $stmt = $db->prepare("INSERT INTO tbl_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
    $stmt->execute([$key, $value]);
}

// ── Generator Helper ──────────────────────────────────────────
function generateBackupFile(string $backupDir, PDO $db, string $prefix = 'sharonstore_backup_'): string {
    $filename = $prefix . date('Y-m-d_H-i-s') . '.sql';
    $filepath = $backupDir . $filename;

    // Try mysqldump first
    $mysqldumpBin = file_exists('C:\\xampp\\mysql\\bin\\mysqldump.exe') ? 'C:\\xampp\\mysql\\bin\\mysqldump.exe' : 'mysqldump';
    $cmd = "\"{$mysqldumpBin}\" -u root --no-tablespaces sharonstore_db > " . escapeshellarg($filepath) . " 2>&1";
    $output = [];
    exec($cmd, $output, $code);

    if ($code !== 0 || !file_exists($filepath) || filesize($filepath) < 100) {
        // Fallback: PHP-based dump using PDO
        $content = "-- Sharon Store Database Backup\n-- Generated: " . date('Y-m-d H:i:s') . "\n-- =============================================\n\n";
        $content .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

        $tables = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        foreach ($tables as $table) {
            $create = $db->query("SHOW CREATE TABLE `{$table}`")->fetch();
            $content .= "DROP TABLE IF EXISTS `{$table}`;\n";
            $content .= $create['Create Table'] . ";\n\n";

            $rows = $db->query("SELECT * FROM `{$table}`")->fetchAll();
            if ($rows) {
                $cols = '`' . implode('`,`', array_keys($rows[0])) . '`';
                $content .= "INSERT INTO `{$table}` ({$cols}) VALUES\n";
                $vals = [];
                foreach ($rows as $row) {
                    $escaped = array_map(fn($v) => $v === null ? 'NULL' : $db->quote((string)$v), $row);
                    $vals[] = '(' . implode(',', $escaped) . ')';
                }
                $content .= implode(",\n", $vals) . ";\n\n";
            }
        }
        $content .= "SET FOREIGN_KEY_CHECKS=1;\n";
        file_put_contents($filepath, $content);
    }

    return $filepath;
}

// ── Restore Helper ────────────────────────────────────────────
function restoreDatabaseFromFile(string $filepath, PDO $db): array {
    if (!file_exists($filepath) || filesize($filepath) === 0) {
        return ['success' => false, 'message' => 'SQL backup file not found or empty.'];
    }

    // Attempt 1: MySQL CLI binary execution (fastest and most reliable)
    $mysqlBin = file_exists('C:\\xampp\\mysql\\bin\\mysql.exe') ? 'C:\\xampp\\mysql\\bin\\mysql.exe' : 'mysql';
    $cmd = "\"{$mysqlBin}\" -u root sharonstore_db < " . escapeshellarg($filepath) . " 2>&1";
    $output = [];
    $code = 1;
    exec($cmd, $output, $code);

    if ($code === 0) {
        return ['success' => true, 'message' => 'Database successfully restored.'];
    }

    // Attempt 2: PDO multi-query execution fallback
    try {
        $sql = file_get_contents($filepath);
        if ($sql === false || trim($sql) === '') {
            return ['success' => false, 'message' => 'Could not read SQL file contents.'];
        }

        $db->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);
        $db->exec("SET FOREIGN_KEY_CHECKS = 0;");
        $db->exec("SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';");
        $db->exec($sql);
        $db->exec("SET FOREIGN_KEY_CHECKS = 1;");

        return ['success' => true, 'message' => 'Database successfully restored.'];
    } catch (\Throwable $e) {
        try { $db->exec("SET FOREIGN_KEY_CHECKS = 1;"); } catch (\Throwable $ignore) {}
        return [
            'success' => false,
            'message' => 'Restoration error: ' . $e->getMessage()
        ];
    }
}

// ── Google Drive Webhook Helper ───────────────────────────────
function sendToGoogleDrive(string $webhookUrl, string $filename, string $filepath): array {
    $webhookUrl = trim($webhookUrl);
    if (!filter_var($webhookUrl, FILTER_VALIDATE_URL) || !str_contains($webhookUrl, 'script.google.com')) {
        return [
            'success' => false,
            'message' => 'Invalid Google Apps Script Webhook URL. It must start with https://script.google.com/'
        ];
    }

    if (str_ends_with($webhookUrl, '/dev')) {
        return [
            'success' => false,
            'message' => 'Your Webhook URL ends with "/dev" (Test deployment), which is private and causes Error 403. In Google Apps Script, click "Deploy" > "New deployment" > Select type "Web app" and copy the URL that ends with "/exec".'
        ];
    }

    if (!file_exists($filepath) || filesize($filepath) === 0) {
        return ['success' => false, 'message' => 'Backup file not found or empty.'];
    }

    $payload = json_encode([
        'filename' => $filename,
        'size'     => filesize($filepath),
        'base64'   => base64_encode((string)file_get_contents($filepath)),
    ]);

    $ch = curl_init($webhookUrl);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Accept: application/json'
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true, // Necessary because Google Apps Script redirects with 302
        CURLOPT_TIMEOUT        => 90,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_USERAGENT      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
    ]);

    $raw = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr = curl_error($ch);
    curl_close($ch);

    if ($curlErr) {
        return ['success' => false, 'message' => 'Network error connecting to Google: ' . $curlErr];
    }

    if ($httpCode === 403) {
        return [
            'success' => false,
            'message' => 'Google returned Error 403 (Forbidden): Your Google Web App is not public. Fix: In script.google.com, click "Deploy" > "Manage deployments" > Edit (pencil icon) > set "Who has access" to "Anyone" (not "Only myself") > click "Deploy".'
        ];
    }

    if ($httpCode === 401) {
        return [
            'success' => false,
            'message' => 'Google returned Error 401 (Unauthorized): Access is restricted. Please make sure "Who has access" is set to "Anyone" in your Google Apps Script deployment settings.'
        ];
    }

    if ($httpCode !== 200 && $httpCode !== 302) {
        return ['success' => false, 'message' => "Google returned HTTP status code: {$httpCode}."];
    }

    $res = json_decode((string)$raw, true);
    if (is_array($res) && isset($res['status']) && $res['status'] === 'success') {
        return [
            'success'  => true,
            'message'  => $res['message'] ?? 'Uploaded to Google Drive successfully!',
            'file_id'  => $res['fileId'] ?? '',
            'file_url' => $res['fileUrl'] ?? '',
            'filename' => $filename
        ];
    }

    $errMsg = is_array($res) && isset($res['message']) ? $res['message'] : substr((string)$raw, 0, 200);
    return ['success' => false, 'message' => 'Google Drive Error: ' . $errMsg];
}

// ── Download / Generate Backup ────────────────────────────────
if ($action === 'download') {
    $requestedFile = basename($_GET['file'] ?? '');
    if ($requestedFile !== '' && str_ends_with($requestedFile, '.sql')) {
        $filepath = $backupDir . $requestedFile;
        if (!file_exists($filepath)) {
            http_response_code(404);
            die('Backup file not found.');
        }
        $filename = $requestedFile;
    } else {
        $filepath = generateBackupFile($backupDir, $db);
        $filename = basename($filepath);

        logAudit($userId, 'DATABASE_BACKUP', 'Backup', "Database backup created: {$filename}");

        // If auto-upload to Google Drive is enabled, push in background
        $autoUpload = getSetting($db, 'gdrive_auto_backup', '0');
        $webhookUrl = getSetting($db, 'gdrive_webhook_url', '');
        if ($autoUpload === '1' && !empty($webhookUrl)) {
            try {
                $gRes = sendToGoogleDrive($webhookUrl, $filename, $filepath);
                if ($gRes['success']) {
                    logAudit($userId, 'GOOGLE_DRIVE_BACKUP', 'Backup', "Auto-uploaded backup to Google Drive: {$filename}");
                }
            } catch (\Throwable $e) {}
        }
    }

    // Send file download to browser
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . filesize($filepath));
    header('Cache-Control: no-cache');
    readfile($filepath);
    exit();
}

// ── List Backups ──────────────────────────────────────────────
if ($action === 'list') {
    header('Content-Type: application/json');
    $files = glob($backupDir . '*.sql') ?: [];
    rsort($files);
    $data = array_map(fn($f) => [
        'filename' => basename($f),
        'size'     => filesize($f),
        'size_fmt' => number_format(filesize($f) / 1024, 1) . ' KB',
        'created'  => date('Y-m-d H:i:s', filemtime($f)),
    ], $files);
    echo json_encode(['success' => true, 'data' => $data]);
    exit();
}

// ── Delete Backup ─────────────────────────────────────────────
if ($action === 'delete') {
    header('Content-Type: application/json');
    $fn = basename($_POST['filename'] ?? '');
    if (!$fn || !str_ends_with($fn, '.sql')) {
        echo json_encode(['success' => false, 'message' => 'Invalid filename.']);
        exit();
    }
    $path = $backupDir . $fn;
    if (file_exists($path)) {
        unlink($path);
        logAudit($userId, 'DELETE_BACKUP', 'Backup', "Deleted backup: {$fn}");
        echo json_encode(['success' => true, 'message' => 'Backup deleted.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'File not found.']);
    }
    exit();
}

// ── Restore / Import Backup ───────────────────────────────────
if ($action === 'restore') {
    header('Content-Type: application/json');

    $source = $_POST['source'] ?? 'history';
    $targetFilepath = '';
    $originalName   = '';

    if ($source === 'upload') {
        if (!isset($_FILES['backup_file']) || $_FILES['backup_file']['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['success' => false, 'message' => 'Please select a valid .sql backup file to upload.']);
            exit();
        }

        $uploadName = $_FILES['backup_file']['name'];
        if (!str_ends_with(strtolower($uploadName), '.sql')) {
            echo json_encode(['success' => false, 'message' => 'Only .sql files are allowed for database import.']);
            exit();
        }

        if ($_FILES['backup_file']['size'] > 100 * 1024 * 1024) {
            echo json_encode(['success' => false, 'message' => 'Uploaded file is too large (max 100MB).']);
            exit();
        }

        $cleanBase = preg_replace('/[^a-zA-Z0-9_\.-]/', '_', pathinfo($uploadName, PATHINFO_FILENAME));
        $destFilename = 'imported_' . date('Y-m-d_H-i-s') . '_' . $cleanBase . '.sql';
        $targetFilepath = $backupDir . $destFilename;

        if (!move_uploaded_file($_FILES['backup_file']['tmp_name'], $targetFilepath)) {
            echo json_encode(['success' => false, 'message' => 'Failed to save uploaded backup file on server.']);
            exit();
        }
        $originalName = $destFilename;
    } else {
        // Restoring from server backup history
        $fn = basename($_POST['filename'] ?? '');
        if (!$fn || !str_ends_with($fn, '.sql')) {
            echo json_encode(['success' => false, 'message' => 'Invalid backup filename.']);
            exit();
        }
        $targetFilepath = $backupDir . $fn;
        $originalName   = $fn;

        if (!file_exists($targetFilepath)) {
            echo json_encode(['success' => false, 'message' => 'Selected backup file does not exist on server.']);
            exit();
        }
    }

    // 1. Automatic Safety Snapshot before restoring
    $safetyPath = generateBackupFile($backupDir, $db, 'sharonstore_safety_pre_restore_');
    $safetyFilename = basename($safetyPath);

    // 2. Perform restoration
    $restoreResult = restoreDatabaseFromFile($targetFilepath, $db);

    if ($restoreResult['success']) {
        logAudit($userId, 'RESTORE_DATABASE', 'Backup', "Database restored from: {$originalName}. Safety backup created: {$safetyFilename}");
        echo json_encode([
            'success'       => true,
            'message'       => "Database successfully restored from {$originalName}!",
            'safety_backup' => $safetyFilename,
            'restored_file' => $originalName
        ]);
    } else {
        logAudit($userId, 'RESTORE_FAILED', 'Backup', "Database restore failed for: {$originalName}. Error: " . $restoreResult['message']);
        echo json_encode([
            'success'       => false,
            'message'       => $restoreResult['message'],
            'safety_backup' => $safetyFilename
        ]);
    }
    exit();
}

// ── Get Cloud Settings ────────────────────────────────────────
if ($action === 'get_settings') {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'data' => [
            'webhook_url' => getSetting($db, 'gdrive_webhook_url', ''),
            'auto_backup' => getSetting($db, 'gdrive_auto_backup', '0'),
        ]
    ]);
    exit();
}

// ── Save Cloud Settings ───────────────────────────────────────
if ($action === 'save_settings') {
    header('Content-Type: application/json');
    $webhook = trim($_POST['webhook_url'] ?? '');
    $auto    = trim($_POST['auto_backup'] ?? '0') === '1' ? '1' : '0';

    if (!empty($webhook) && (!filter_var($webhook, FILTER_VALIDATE_URL) || !str_contains($webhook, 'script.google.com'))) {
        echo json_encode(['success' => false, 'message' => 'Please enter a valid Google Apps Script Web App URL (starts with https://script.google.com/).']);
        exit();
    }

    setSetting($db, 'gdrive_webhook_url', $webhook);
    setSetting($db, 'gdrive_auto_backup', $auto);
    logAudit($userId, 'BACKUP_SETTINGS', 'Backup', "Updated Google Drive backup settings");

    echo json_encode(['success' => true, 'message' => 'Google Drive settings saved successfully.']);
    exit();
}

// ── Upload to Google Drive (Manual or from List) ──────────────
if ($action === 'upload_gdrive') {
    header('Content-Type: application/json');
    $webhookUrl = getSetting($db, 'gdrive_webhook_url', '');
    if (empty($webhookUrl)) {
        echo json_encode([
            'success' => false,
            'message' => 'Google Drive is not configured yet. Please click "Configure Google Drive" to connect your account.'
        ]);
        exit();
    }

    $fn = basename($_POST['filename'] ?? '');
    if (!empty($fn)) {
        // Uploading an existing backup file from the list
        $filepath = $backupDir . $fn;
        $filename = $fn;
    } else {
        // Generating a fresh backup and uploading
        $filepath = generateBackupFile($backupDir, $db);
        $filename = basename($filepath);
        logAudit($userId, 'DATABASE_BACKUP', 'Backup', "Database backup created: {$filename}");
    }

    if (!file_exists($filepath)) {
        echo json_encode(['success' => false, 'message' => 'Backup file could not be found.']);
        exit();
    }

    $uploadResult = sendToGoogleDrive($webhookUrl, $filename, $filepath);
    if ($uploadResult['success']) {
        logAudit($userId, 'GOOGLE_DRIVE_BACKUP', 'Backup', "Uploaded backup to Google Drive: {$filename}");
    }

    echo json_encode($uploadResult);
    exit();
}

// ── Test Connection ───────────────────────────────────────────
if ($action === 'test_gdrive') {
    header('Content-Type: application/json');
    $testUrl = trim($_POST['webhook_url'] ?? '') ?: getSetting($db, 'gdrive_webhook_url', '');

    if (empty($testUrl)) {
        echo json_encode(['success' => false, 'message' => 'Please enter a Google Apps Script Web App URL to test.']);
        exit();
    }

    $testFile = $backupDir . 'sharonstore_ping.tmp';
    file_put_contents($testFile, "-- Sharon Store Connection Test\nSELECT 1;\n");

    $result = sendToGoogleDrive($testUrl, 'sharonstore_test_ping.sql', $testFile);
    if (file_exists($testFile)) unlink($testFile);

    echo json_encode($result);
    exit();
}

header('Content-Type: application/json');
echo json_encode(['success' => false, 'message' => 'Unknown action.']);
