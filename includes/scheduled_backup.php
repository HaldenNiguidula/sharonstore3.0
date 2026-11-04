<?php
declare(strict_types=1);

require_once __DIR__ . '/backup.php';

function scheduledBackupState(PDO $db, string $key, string $value): void {
    $stmt = $db->prepare('INSERT INTO tbl_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)');
    $stmt->execute([$key, $value]);
}

/** Daily, independent of login/browser activity. State and leases survive serverless invocations. */
function runScheduledBackup(PDO $db, string $source, ?callable $exporter = null, ?callable $uploader = null): array {
    if (!in_array($source, ['local', 'vercel'], true)) throw new InvalidArgumentException('Invalid backup source.');
    $prefix = 'daily_' . $source . '_';
    $day = (new DateTimeImmutable('now', new DateTimeZone('Asia/Singapore')))->format('Y-m-d');
    $lockKey = $prefix . 'lock';
    $claim = (time() + 1800) . ':' . bin2hex(random_bytes(16));
    // Compare-and-set a short lease using the existing settings table (MySQL and TiDB).
    $db->prepare('INSERT IGNORE INTO tbl_settings (setting_key, setting_value) VALUES (?, ?)')->execute([$lockKey, '0']);
    $lock = $db->prepare("UPDATE tbl_settings SET setting_value = ? WHERE setting_key = ? AND (setting_value IS NULL OR CAST(SUBSTRING_INDEX(setting_value, ':', 1) AS UNSIGNED) < UNIX_TIMESTAMP())");
    $lock->execute([$claim, $lockKey]);
    if ($lock->rowCount() !== 1) return ['success' => true, 'skipped' => true, 'message' => 'A daily backup is already running.'];

    try {
        if (getSetting($db, $prefix . 'period') === $day) {
            return ['success' => true, 'skipped' => true, 'message' => 'Today’s daily backup is already complete.'];
        }
        $webhook = trim((string)(getenv('BACKUP_GDRIVE_WEBHOOK_URL') ?: getSetting($db, 'gdrive_webhook_url')));
        $upload = $source === 'vercel' || ($webhook !== '' && getSetting($db, 'gdrive_auto_backup') === '1');
        if ($source === 'vercel' && $webhook === '') {
            throw new RuntimeException('Configure Google Drive in Database Backup before enabling cloud backups. Vercel temporary files are not durable backups.');
        }
        $path = ($exporter ?? 'generateScheduledBackupFile')($db, $source);
        $cloud = null;
        if ($upload) {
            $cloud = ($uploader ?? 'sendToGoogleDrive')($webhook, basename($path), $path);
            if (empty($cloud['success'])) throw new RuntimeException('Google Drive upload failed: ' . ($cloud['message'] ?? 'Unknown error'));
        }
        $completed = (string)time();
        scheduledBackupState($db, $prefix . 'success', $completed);
        scheduledBackupState($db, $prefix . 'file', basename($path));
        scheduledBackupState($db, $prefix . 'error', '');
        // Mark the period only once the required durable destination has acknowledged success.
        scheduledBackupState($db, $prefix . 'period', $day);
        if ($source === 'local') pruneScheduledBackups(dirname($path));
        elseif (is_file($path)) unlink($path);
        return ['success' => true, 'skipped' => false, 'filename' => basename($path), 'message' => $cloud ? 'Daily backup saved to Google Drive.' : 'Daily backup saved locally.'];
    } catch (Throwable $e) {
        scheduledBackupState($db, $prefix . 'error', substr($e->getMessage(), 0, 250));
        scheduledBackupState($db, $prefix . 'failure', (string)time());
        throw $e;
    } finally {
        $db->prepare('UPDATE tbl_settings SET setting_value = ? WHERE setting_key = ? AND setting_value = ?')->execute(['0', $lockKey, $claim]);
    }
}

/** Publish only complete, consistent exports; never prune existing manual/safety backups. */
function generateScheduledBackupFile(PDO $db, string $source): string {
    $dir = $source === 'vercel' ? sys_get_temp_dir() . '/sharonstore_backups' : rtrim(getBackupDir(), '/\\');
    if (!is_dir($dir) && !mkdir($dir, 0700, true) && !is_dir($dir)) throw new RuntimeException('Cannot create backup directory.');
    $stamp = (new DateTimeImmutable('now', new DateTimeZone('Asia/Singapore')))->format('Y-m-d_H-i-s');
    $target = $dir . '/sharonstore_auto_' . $source . '_' . $stamp . '_' . bin2hex(random_bytes(4)) . '.sql';
    $partial = $target . '.partial';
    $file = fopen($partial, 'xb');
    if (!$file) throw new RuntimeException('Cannot create daily backup file.');
    $write = static function (string $text) use ($file): void {
        $remaining = strlen($text); $offset = 0;
        while ($remaining > 0) {
            $written = fwrite($file, substr($text, $offset));
            if ($written === false || $written === 0) throw new RuntimeException('Daily backup disk write failed.');
            $offset += $written; $remaining -= $written;
        }
    };
    try {
        $db->exec('SET TRANSACTION ISOLATION LEVEL REPEATABLE READ');
        $db->beginTransaction();
        $tables = $db->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'")->fetchAll(PDO::FETCH_NUM);
        if (!$tables) throw new RuntimeException('No database tables found to back up.');
        $write("-- Sharon Store automatic daily backup\n-- Generated: {$stamp} Asia/Singapore\nSET FOREIGN_KEY_CHECKS = 0;\nSET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';\n\n");
        foreach ($tables as $row) {
            $table = (string)$row[0];
            if ($table === 'tbl_sessions') continue;
            $quoted = '`' . str_replace('`', '``', $table) . '`';
            $ddl = $db->query('SHOW CREATE TABLE ' . $quoted)->fetch(PDO::FETCH_NUM);
            if (!$ddl || empty($ddl[1])) throw new RuntimeException('Missing table definition: ' . $table);
            $write("DROP TABLE IF EXISTS {$quoted};\n{$ddl[1]};\n");
            $stmt = $db->query('SELECT * FROM ' . $quoted);
            while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $columns = implode(',', array_map(static fn($name) => '`' . str_replace('`', '``', $name) . '`', array_keys($data)));
                $values = implode(',', array_map(static fn($v) => $v === null ? 'NULL' : $db->quote((string)$v), $data));
                $write("INSERT INTO {$quoted} ({$columns}) VALUES ({$values});\n");
            }
            $write("\n");
        }
        $write("SET FOREIGN_KEY_CHECKS = 1;\n");
        $db->commit();
        if (!fflush($file)) throw new RuntimeException('Could not flush daily backup to disk.');
        fclose($file); $file = null;
        if (!rename($partial, $target)) throw new RuntimeException('Could not publish daily backup file.');
        return $target;
    } catch (Throwable $e) {
        if ($db->inTransaction()) $db->rollBack();
        if (is_resource($file)) fclose($file);
        if (is_file($partial)) unlink($partial);
        throw $e;
    }
}

function pruneScheduledBackups(string $directory): void {
    $files = glob(rtrim($directory, '/\\') . '/sharonstore_auto_local_*.sql') ?: [];
    rsort($files, SORT_STRING);
    foreach (array_slice($files, 5) as $file) if (is_file($file)) unlink($file);
}
