<?php
// ============================================================
// Backup API — Sharon Store System
// Supports Local Backup & Google Drive Cloud Backup (Apps Script Webhook)
// Compatible with both Local XAMPP and Cloud / Serverless (Vercel / TiDB)
// ============================================================
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/audit.php';

requireAdmin();

require_once __DIR__ . '/../includes/backup.php';

$backupDir = getBackupDir();
$db = getDB();
$userId = currentUserId();
$action = $_GET['action'] ?? $_POST['action'] ?? '';

if ($action === 'schedule_status') {
    header('Content-Type: application/json');
    $source = getenv('VERCEL') ? 'vercel' : 'local';
    $prefix = 'daily_' . $source . '_';
    $success = (int)getSetting($db, $prefix . 'success', '0');
    $failure = (int)getSetting($db, $prefix . 'failure', '0');
    $format = static fn(int $time) => $time > 0 ? (new DateTimeImmutable('@' . $time))->setTimezone(new DateTimeZone(APP_TIMEZONE))->format('M d, Y g:i A') : null;
    echo json_encode(['success' => true, 'data' => [
        'source' => $source,
        'scheduler_ready' => $source === 'vercel' ? (string)getenv('CRON_SECRET') !== '' : is_file(__DIR__ . '/../backups/.daily-schedule.json'),
        'schedule' => 'Every day at 3:00 AM (Singapore time)',
        'last_success' => $format($success),
        'last_failure' => $format($failure),
        'last_error' => getSetting($db, $prefix . 'error'),
        'filename' => getSetting($db, $prefix . 'file'),
        'cloud_configured' => $source !== 'vercel' || trim((string)(getenv('BACKUP_GDRIVE_WEBHOOK_URL') ?: getSetting($db, 'gdrive_webhook_url'))) !== '',
    ]]);
    exit;
}

// ── Download / Generate Backup ────────────────────────────────
if ($action === 'download') {
    $requestedFile = basename($_GET['file'] ?? '');
    if ($requestedFile !== '' && str_ends_with($requestedFile, '.sql')) {
        $filepath = $backupDir . $requestedFile;
        if (!file_exists($filepath)) {
            $alt = __DIR__ . '/../backups/' . $requestedFile;
            if (file_exists($alt)) {
                $filepath = $alt;
            } else {
                http_response_code(404);
                die('Backup file not found.');
            }
        }
        $filename = $requestedFile;
    } else {
        try {
            $filepath = generateBackupFile($backupDir, $db);
        } catch (Throwable $e) {
            error_log('Manual backup failed: ' . $e->getMessage());
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Backup failed. No incomplete backup was saved. Please try again.']);
            exit;
        }
        $filename = basename($filepath);

        logAudit($userId, 'DATABASE_BACKUP', 'Backup', "Database backup created: {$filename}");

        // If auto-upload to Google Drive is enabled, push
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

    // Clean any prior output buffers before sending binary file
    while (ob_get_level()) {
        ob_end_clean();
    }

    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . filesize($filepath));
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
    readfile($filepath);
    exit();
}

// ── List Backups ──────────────────────────────────────────────
if ($action === 'list') {
    header('Content-Type: application/json');
    pruneServerBackups($backupDir, 5);

    $dirs = array_unique([$backupDir, __DIR__ . '/../backups/']);
    $filesMap = [];
    foreach ($dirs as $d) {
        if (@is_dir($d)) {
            $glob = glob(rtrim($d, '/\\') . '/*.sql') ?: [];
            foreach ($glob as $f) {
                $filesMap[basename($f)] = $f;
            }
        }
    }
    $data = [];
    foreach ($filesMap as $fn => $fp) {
        $created = date('Y-m-d H:i:s', filemtime($fp));
        // Prefer filename timestamp over git/container filemtime metadata
        if (preg_match('/(\d{4}-\d{2}-\d{2})_(\d{2}-\d{2}-\d{2})/', $fn, $m)) {
            $created = $m[1] . ' ' . str_replace('-', ':', $m[2]);
        }
        $data[] = [
            'filename' => $fn,
            'size'     => filesize($fp),
            'size_fmt' => number_format(filesize($fp) / 1024, 1) . ' KB',
            'created'  => $created,
        ];
    }
    // Sort descending by created date (newest first)
    usort($data, fn($a, $b) => strcmp($b['created'], $a['created']));

    // Always cap the list to the 5 most recent backups
    $data = array_slice($data, 0, 5);

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
    $deleted = false;
    foreach ([$backupDir . $fn, __DIR__ . '/../backups/' . $fn] as $p) {
        if (file_exists($p)) {
            @unlink($p);
            $deleted = true;
        }
    }
    if ($deleted) {
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
            // Fallback: read directly from temporary uploaded path
            $targetFilepath = $_FILES['backup_file']['tmp_name'];
        }
        $originalName = $uploadName;
    } else {
        // Restoring from server backup history
        $fn = basename($_POST['filename'] ?? '');
        if (!$fn || !str_ends_with($fn, '.sql')) {
            echo json_encode(['success' => false, 'message' => 'Invalid backup filename.']);
            exit();
        }
        $targetFilepath = $backupDir . $fn;
        if (!file_exists($targetFilepath)) {
            $alt = __DIR__ . '/../backups/' . $fn;
            if (file_exists($alt)) {
                $targetFilepath = $alt;
            } else {
                echo json_encode(['success' => false, 'message' => 'Selected backup file does not exist on server.']);
                exit();
            }
        }
        $originalName = $fn;
    }

    // 1. Automatic Safety Snapshot before restoring
    $safetyFilename = 'skipped';
    try {
        $safetyPath = generateBackupFile($backupDir, $db, 'sharonstore_safety_pre_restore_');
        $safetyFilename = basename($safetyPath);
    } catch (\Throwable $e) {
        error_log("Safety snapshot error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Restore cancelled because a complete safety backup could not be created. Please try again.']);
        exit;
    }

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
        if (!file_exists($filepath)) {
            $alt = __DIR__ . '/../backups/' . $fn;
            if (file_exists($alt)) {
                $filepath = $alt;
            }
        }
        $filename = $fn;
    } else {
        // Generating a fresh backup and uploading
        try {
            $filepath = generateBackupFile($backupDir, $db);
        } catch (Throwable $e) {
            error_log('Cloud backup export failed: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Backup failed. No incomplete backup was uploaded. Please try again.']);
            exit;
        }
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
    @file_put_contents($testFile, "-- Sharon Store Connection Test\nSELECT 1;\n");

    $result = sendToGoogleDrive($testUrl, 'sharonstore_test_ping.sql', $testFile);
    if (file_exists($testFile)) @unlink($testFile);

    echo json_encode($result);
    exit();
}

header('Content-Type: application/json');
echo json_encode(['success' => false, 'message' => 'Unknown action.']);
