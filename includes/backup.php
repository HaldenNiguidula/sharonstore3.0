<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

/**
 * Returns a guaranteed writable directory for backups.
 * On local dev (XAMPP), uses project /backups/ directory.
 * On serverless (Vercel/Lambda where project root is read-only), falls back to sys temp dir.
 */
function getBackupDir(): string {
    $projectBackup = __DIR__ . '/../backups/';
    if (!@is_dir($projectBackup)) {
        @mkdir($projectBackup, 0777, true);
    }
    if (@is_dir($projectBackup) && @is_writable($projectBackup)) {
        return rtrim($projectBackup, '/\\') . DIRECTORY_SEPARATOR;
    }
    $tmpBackup = rtrim(sys_get_temp_dir(), '/\\') . DIRECTORY_SEPARATOR . 'sharonstore_backups' . DIRECTORY_SEPARATOR;
    if (!@is_dir($tmpBackup)) {
        @mkdir($tmpBackup, 0777, true);
    }
    return $tmpBackup;
}


// ── Settings Helpers ──────────────────────────────────────────
function getSetting(PDO $db, string $key, string $default = ''): string {
    try {
        $stmt = $db->prepare("SELECT setting_value FROM tbl_settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        $val = $stmt->fetchColumn();
        return $val !== false && $val !== null ? (string)$val : $default;
    } catch (\Throwable $e) {
        return $default;
    }
}

function setSetting(PDO $db, string $key, string $value): void {
    try {
        $db->exec("ALTER TABLE tbl_settings MODIFY setting_value TEXT NULL");
    } catch (\Throwable $e) {}
    $stmt = $db->prepare("INSERT INTO tbl_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
    $stmt->execute([$key, $value]);
}

/**
 * Prunes server backup files so only the newest $max files are kept.
 * Excess files that are writable are automatically deleted from disk.
 */
function pruneServerBackups(string $backupDir, int $max = 5): void {
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
    $items = [];
    foreach ($filesMap as $fn => $fp) {
        $created = date('Y-m-d H:i:s', filemtime($fp));
        if (preg_match('/(\d{4}-\d{2}-\d{2})_(\d{2}-\d{2}-\d{2})/', $fn, $m)) {
            $created = $m[1] . ' ' . str_replace('-', ':', $m[2]);
        }
        $items[] = [
            'filename' => $fn,
            'filepath' => $fp,
            'created'  => $created
        ];
    }
    usort($items, fn($a, $b) => strcmp($b['created'], $a['created']));
    if (count($items) > $max) {
        $extra = array_slice($items, $max);
        foreach ($extra as $ex) {
            if (@is_writable($ex['filepath'])) {
                @unlink($ex['filepath']);
            }
        }
    }
}

// ── Generator Helper ──────────────────────────────────────────
/**
 * Generates a full database backup SQL file using pure PDO.
 * Works seamlessly across both local MySQL and remote Cloud MySQL (TiDB Cloud, etc.)
 * without requiring any external mysqldump binary.
 */
function generateBackupFile(string $backupDir, PDO $db, string $prefix = 'sharonstore_backup_'): string {
    $filename = $prefix . date('Y-m-d_H-i-s') . '.sql';
    $filepath = $backupDir . $filename;

    $fp = @fopen($filepath, 'w');
    if (!$fp) {
        throw new \RuntimeException("Unable to open backup file for writing: " . $filepath);
    }

    fwrite($fp, "-- Sharon Store Database Backup\n");
    fwrite($fp, "-- Generated: " . date('Y-m-d H:i:s') . "\n");
    fwrite($fp, "-- Database: " . DB_NAME . "\n");
    fwrite($fp, "-- Host: " . DB_HOST . "\n");
    fwrite($fp, "-- =============================================\n\n");
    fwrite($fp, "SET FOREIGN_KEY_CHECKS = 0;\n");
    fwrite($fp, "SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';\n\n");

    try {
        $tables = $db->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'")->fetchAll(PDO::FETCH_NUM);
    } catch (\Throwable $e) {
        $tables = [];
    }
    if (empty($tables)) {
        $tables = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_NUM);
    }

    foreach ($tables as $tRow) {
        $table = $tRow[0];

        // Fetch CREATE TABLE DDL
        try {
            $createRow = $db->query("SHOW CREATE TABLE `{$table}`")->fetch(PDO::FETCH_NUM);
        } catch (\Throwable $e) {
            continue;
        }

        if ($createRow && isset($createRow[1])) {
            fwrite($fp, "DROP TABLE IF EXISTS `{$table}`;\n");
            fwrite($fp, $createRow[1] . ";\n\n");
        }

        // Do not dump ephemeral sessions data to keep dumps clean and safe
        if ($table === 'tbl_sessions') {
            continue;
        }

        // Stream table rows in batches
        try {
            $stmt = $db->query("SELECT * FROM `{$table}`");
            $chunk = [];
            $cols = null;

            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                if ($cols === null) {
                    $cols = '`' . implode('`,`', array_keys($row)) . '`';
                }
                $escaped = array_map(function($v) use ($db) {
                    if ($v === null) return 'NULL';
                    return $db->quote((string)$v);
                }, $row);
                $chunk[] = '(' . implode(',', $escaped) . ')';

                if (count($chunk) >= 100) {
                    fwrite($fp, "INSERT INTO `{$table}` ({$cols}) VALUES\n" . implode(",\n", $chunk) . ";\n\n");
                    $chunk = [];
                }
            }
            if (!empty($chunk)) {
                fwrite($fp, "INSERT INTO `{$table}` ({$cols}) VALUES\n" . implode(",\n", $chunk) . ";\n\n");
            }
        } catch (\Throwable $e) {
            // Continue with other tables if one fails
        }
    }

    fwrite($fp, "SET FOREIGN_KEY_CHECKS = 1;\n");
    fclose($fp);

    pruneServerBackups($backupDir, 5);

    return $filepath;
}

// ── Restore Helper ────────────────────────────────────────────
/**
 * Restores database from a SQL backup file using a robust PDO streaming parser.
 * Handles multiline statements, comments, string literals with semicolons,
 * and skips TiDB-incompatible statements like LOCK TABLES.
 */
function restoreDatabaseFromFile(string $filepath, PDO $db): array {
    if (!file_exists($filepath) || filesize($filepath) === 0) {
        return ['success' => false, 'message' => 'SQL backup file not found or empty.'];
    }

    $handle = @fopen($filepath, 'r');
    if (!$handle) {
        return ['success' => false, 'message' => 'Could not open SQL file for reading.'];
    }

    try { $db->exec("SET FOREIGN_KEY_CHECKS = 0;"); } catch (\Throwable $e) {}
    try { $db->exec("SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';"); } catch (\Throwable $e) {}

    $query = '';
    $inString = false;
    $stringChar = '';
    $inBlockComment = false;
    $executed = 0;
    $errors = [];

    while (($line = fgets($handle)) !== false) {
        $len = strlen($line);
        $i = 0;

        while ($i < $len) {
            $char = $line[$i];
            $next = $i + 1 < $len ? $line[$i + 1] : '';

            // Handle block comments /* ... */
            if ($inBlockComment) {
                if ($char === '*' && $next === '/') {
                    $inBlockComment = false;
                    $i += 2;
                    continue;
                }
                $i++;
                continue;
            }

            // Handle strings '...' and "..."
            if ($inString) {
                $query .= $char;
                if ($char === '\\') {
                    if ($i + 1 < $len) {
                        $i++;
                        $query .= $line[$i];
                    }
                } elseif ($char === $stringChar) {
                    if ($next === $stringChar) {
                        $i++;
                        $query .= $line[$i];
                    } else {
                        $inString = false;
                        $stringChar = '';
                    }
                }
                $i++;
                continue;
            }

            // Outside strings and block comments:
            if ($char === '/' && $next === '*') {
                $inBlockComment = true;
                $i += 2;
                continue;
            }

            if (($char === '-' && $next === '-') || $char === '#') {
                break; // Skip comment till end of line
            }

            if ($char === "'" || $char === '"') {
                $inString = true;
                $stringChar = $char;
                $query .= $char;
                $i++;
                continue;
            }

            if ($char === ';') {
                $stmt = trim($query);
                $query = '';
                $i++;

                if ($stmt === '') continue;

                $upper = strtoupper($stmt);
                // Skip TiDB unsupported statements
                if (str_starts_with($upper, 'LOCK TABLES') || str_starts_with($upper, 'UNLOCK TABLES')) {
                    continue;
                }
                // Don't drop or overwrite active tbl_sessions so admin doesn't get kicked out
                if (preg_match('/^DROP\s+TABLE\s+(IF\s+EXISTS\s+)?`?tbl_sessions`?/i', $stmt)) {
                    continue;
                }
                if (preg_match('/^CREATE\s+TABLE\s+(IF\s+NOT\s+EXISTS\s+)?`?tbl_sessions`?/i', $stmt)) {
                    continue;
                }

                try {
                    $db->exec($stmt);
                    $executed++;
                } catch (\Throwable $e) {
                    if (stripos($stmt, 'CREATE TABLE') === 0) {
                        try {
                            $stripped = preg_replace('/,\s*CONSTRAINT\s+[`\w]+\s+FOREIGN\s+KEY\s*\([^)]+\)\s*REFERENCES\s+[`\w]+\s*\([^)]+\)(\s+ON\s+(DELETE|UPDATE)\s+[A-Z\s]+)*/i', '', $stmt);
                            $stripped = preg_replace('/,(\s*\))/m', '$1', $stripped);
                            $db->exec($stripped);
                            $executed++;
                            continue;
                        } catch (\Throwable $e2) {}
                    }
                    $errMsg = $e->getMessage();
                    if (!str_contains($errMsg, "doesn't support LOCK")) {
                        $errors[] = substr($errMsg, 0, 150);
                    }
                }
                continue;
            }

            $query .= $char;
            $i++;
        }
    }

    $stmt = trim($query);
    if ($stmt !== '') {
        $upper = strtoupper($stmt);
        if (!str_starts_with($upper, 'LOCK TABLES') && !str_starts_with($upper, 'UNLOCK TABLES')) {
            try {
                $db->exec($stmt);
                $executed++;
            } catch (\Throwable $e) {
                $errors[] = substr($e->getMessage(), 0, 150);
            }
        }
    }

    fclose($handle);

    try { $db->exec("SET FOREIGN_KEY_CHECKS = 1;"); } catch (\Throwable $e) {}

    // Ensure tbl_sessions structure is intact
    try {
        $db->exec("CREATE TABLE IF NOT EXISTS `tbl_sessions` (
            `id` VARCHAR(128) NOT NULL PRIMARY KEY,
            `data` MEDIUMTEXT NOT NULL,
            `last_activity` INT UNSIGNED NOT NULL,
            INDEX (`last_activity`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
    } catch (\Throwable $e) {}

    if (!empty($errors) || $executed === 0) {
        return [
            'success' => false,
            'message' => 'Restore incomplete; some data may have changed. Use the safety backup to recover. ' . ($errors ? implode('; ', array_slice($errors, 0, 3)) : 'No SQL statements were executed.')
        ];
    }

    return [
        'success' => true,
        'message' => "Database successfully restored ({$executed} statements executed)."
    ];
}

// ── Google Drive Webhook Helper ───────────────────────────────
function sendToGoogleDrive(string $webhookUrl, string $filename, string $filepath): array {
    $webhookUrl = trim($webhookUrl);
    $url = parse_url($webhookUrl);
    if (!filter_var($webhookUrl, FILTER_VALIDATE_URL) || !is_array($url) || ($url['scheme'] ?? '') !== 'https' || strtolower($url['host'] ?? '') !== 'script.google.com' || isset($url['user']) || isset($url['pass'])) {
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
        'filename'  => $filename,
        'size'      => filesize($filepath),
        'base64'    => base64_encode((string)file_get_contents($filepath)),
        'max_files' => 5,
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
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_USERAGENT      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
    ]);
    $ca = __DIR__ . '/cacert.pem';
    if (is_readable($ca)) curl_setopt($ch, CURLOPT_CAINFO, $ca);

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

