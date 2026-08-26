<?php
// ============================================================
// Backup API — Sharon Store System
// ============================================================
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/audit.php';

requireAdmin();

$backupDir = __DIR__ . '/../backups/';
if (!is_dir($backupDir)) mkdir($backupDir, 0755, true);

$action = $_GET['action'] ?? $_POST['action'] ?? '';

// ── Download / Generate Backup ────────────────────────────────
if ($action === 'download') {
    $filename  = 'sharonstore_backup_' . date('Y-m-d_H-i-s') . '.sql';
    $filepath  = $backupDir . $filename;

    // Try mysqldump first
    $cmd    = "mysqldump -u root --no-tablespaces sharonstore_db > " . escapeshellarg($filepath) . " 2>&1";
    $output = [];
    exec($cmd, $output, $code);

    if ($code !== 0 || !file_exists($filepath) || filesize($filepath) < 100) {
        // Fallback: PHP-based dump using PDO
        $db      = getDB();
        $content = "-- Sharon Store Database Backup\n-- Generated: " . date('Y-m-d H:i:s') . "\n-- =============================================\n\n";
        $content .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

        $tables = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        foreach ($tables as $table) {
            // CREATE TABLE
            $create = $db->query("SHOW CREATE TABLE `{$table}`")->fetch();
            $content .= "DROP TABLE IF EXISTS `{$table}`;\n";
            $content .= $create['Create Table'] . ";\n\n";

            // INSERT DATA
            $rows = $db->query("SELECT * FROM `{$table}`")->fetchAll();
            if ($rows) {
                $cols    = '`' . implode('`,`', array_keys($rows[0])) . '`';
                $content .= "INSERT INTO `{$table}` ({$cols}) VALUES\n";
                $vals    = [];
                foreach ($rows as $row) {
                    $escaped = array_map(fn($v) => $v === null ? 'NULL' : $db->quote($v), $row);
                    $vals[]  = '(' . implode(',', $escaped) . ')';
                }
                $content .= implode(",\n", $vals) . ";\n\n";
            }
        }
        $content .= "SET FOREIGN_KEY_CHECKS=1;\n";
        file_put_contents($filepath, $content);
    }

    logAudit(currentUserId(), 'DATABASE_BACKUP', 'Backup', "Database backup created: {$filename}");

    // Send file download
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
        logAudit(currentUserId(), 'DELETE_BACKUP', 'Backup', "Deleted backup: {$fn}");
        echo json_encode(['success' => true, 'message' => 'Backup deleted.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'File not found.']);
    }
    exit();
}

header('Content-Type: application/json');
echo json_encode(['success' => false, 'message' => 'Unknown action.']);
