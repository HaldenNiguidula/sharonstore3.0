<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__ . '/../includes/scheduled_backup.php';

function expectBackup(bool $condition, string $message): void {
    if (!$condition) throw new RuntimeException($message);
}

$dsn = (string)getenv('TEST_DB_DSN');
if ($dsn === '') throw new RuntimeException('Set TEST_DB_DSN to an isolated sharonstore_test_ database.');
$db = new PDO($dsn, getenv('TEST_DB_USER') ?: 'root', getenv('TEST_DB_PASS') ?: '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES => false]);
$name = (string)$db->query('SELECT DATABASE()')->fetchColumn();
if (!str_starts_with($name, 'sharonstore_test_')) throw new RuntimeException('Refusing to test against a non-test database.');
$db->exec('CREATE TABLE IF NOT EXISTS tbl_settings (setting_key VARCHAR(50) PRIMARY KEY, setting_value TEXT NULL) ENGINE=InnoDB');
$db->exec('DELETE FROM tbl_settings');
$folder = sys_get_temp_dir() . '/sharonstore_test_' . bin2hex(random_bytes(8));
mkdir($folder, 0700);
$exports = 0; $uploads = 0;
$export = function (PDO $connection, string $source) use ($folder, &$exports): string {
    $exports++;
    $path = $folder . '/sharonstore_auto_' . $source . '_' . sprintf('%03d', $exports) . '.sql';
    file_put_contents($path, '-- test export');
    return $path;
};
$upload = function (string $url, string $filename, string $path) use (&$uploads): array {
    $uploads++; expectBackup(is_file($path), 'Upload did not receive a complete export');
    return ['success' => true];
};
try {
    $one = runScheduledBackup($db, 'local', $export, $upload);
    $two = runScheduledBackup($db, 'local', $export, $upload);
    expectBackup($one['success'] && !$one['skipped'] && $two['skipped'] && $exports === 1, 'Repeated invocation made a duplicate daily backup');
    echo "PASS daily success and idempotency\n";

    scheduledBackupState($db, 'daily_local_lock', (time() + 100) . ':other-worker');
    expectBackup(runScheduledBackup($db, 'local', $export, $upload)['skipped'] && $exports === 1, 'Concurrent worker bypassed lease');
    scheduledBackupState($db, 'daily_local_lock', (time() - 100) . ':expired-worker');
    scheduledBackupState($db, 'daily_local_period', '');
    expectBackup(!runScheduledBackup($db, 'local', $export, $upload)['skipped'], 'Expired lease did not recover');
    echo "PASS active lease exclusion and interrupted-job recovery\n";

    scheduledBackupState($db, 'daily_local_period', '');
    try { runScheduledBackup($db, 'local', static function (): never { throw new RuntimeException('simulated export failure'); }); throw new LogicException('Failure was hidden'); }
    catch (RuntimeException $e) { expectBackup($e->getMessage() === 'simulated export failure', 'Wrong failure'); }
    expectBackup(getSetting($db, 'daily_local_period') === '' && getSetting($db, 'daily_local_lock') === '0', 'Failed export marked complete or leaked lease');
    expectBackup(!runScheduledBackup($db, 'local', $export, $upload)['skipped'], 'Failed export could not retry');
    echo "PASS failed export remains retryable\n";

    try { runScheduledBackup($db, 'vercel', $export, $upload); throw new LogicException('Missing durable storage was accepted'); }
    catch (RuntimeException $e) { expectBackup(str_contains($e->getMessage(), 'Configure Google Drive'), 'Wrong cloud configuration failure'); }
    scheduledBackupState($db, 'gdrive_webhook_url', 'https://script.google.com/test-fixture');
    try { runScheduledBackup($db, 'vercel', $export, static fn() => ['success' => false, 'message' => 'simulated upload failure']); throw new LogicException('Upload failure was hidden'); }
    catch (RuntimeException $e) { expectBackup(str_contains($e->getMessage(), 'simulated upload failure'), 'Wrong upload failure'); }
    expectBackup(getSetting($db, 'daily_vercel_period') === '', 'Failed upload was recorded as a durable backup');
    expectBackup(!runScheduledBackup($db, 'vercel', $export, $upload)['skipped'] && $uploads === 1, 'Cloud upload retry failed');
    echo "PASS required cloud storage, upload failure and successful retry\n";

    file_put_contents($folder . '/manual.sql', 'preserve');
    file_put_contents($folder . '/sharonstore_safety_pre_restore.sql', 'preserve');
    for ($i = 0; $i < 8; $i++) file_put_contents($folder . '/sharonstore_auto_local_2026-01-' . sprintf('%02d', $i + 1) . '.sql', '-- fixture');
    pruneScheduledBackups($folder);
    expectBackup(count(glob($folder . '/sharonstore_auto_local_*.sql')) === 5 && is_file($folder . '/manual.sql') && is_file($folder . '/sharonstore_safety_pre_restore.sql'), 'Retention touched unrelated backups');
    echo "PASS five daily backups retained; manual and safety files preserved\n";

    $db->exec('CREATE TABLE IF NOT EXISTS sample_rows (id INT PRIMARY KEY, description TEXT, amount DECIMAL(10,2), optional_value TEXT NULL) ENGINE=InnoDB');
    $db->exec('DELETE FROM sample_rows');
    $db->prepare('INSERT INTO sample_rows VALUES (?, ?, ?, ?)')->execute([1, "quote ' slash \\ newline\nsemi; unicode café", '12.34', null]);
    $db->exec('CREATE TABLE IF NOT EXISTS tbl_sessions (id VARCHAR(128) PRIMARY KEY, data TEXT, last_activity INT) ENGINE=InnoDB');
    $db->exec("INSERT IGNORE INTO tbl_sessions VALUES ('test', 'ephemeral-secret', 123)");
    $path = generateScheduledBackupFile($db, 'vercel');
    $dump = file_get_contents($path);
    expectBackup(str_contains($dump, 'sample_rows') && !str_contains($dump, 'ephemeral-secret') && !str_contains($dump, 'DROP TABLE IF EXISTS `tbl_sessions`'), 'Export incomplete or session data leaked');
    $db->exec('DELETE FROM sample_rows');
    $restored = restoreDatabaseFromFile($path, $db);
    unlink($path);
    expectBackup($restored['success'], 'Generated export did not restore');
    $row = $db->query('SELECT * FROM sample_rows')->fetch();
    expectBackup($row['description'] === "quote ' slash \\ newline\nsemi; unicode café" && $row['amount'] === '12.34' && $row['optional_value'] === null, 'Restored data differs from source');
    expectBackup(count(glob($folder . '/*.partial')) === 0, 'Incomplete file published');
    echo "PASS real SQL export/restore round trip, escaping, decimals, nulls and session exclusion\n";

    $failing = new class($db) extends PDO {
        public function __construct(private PDO $connection) {}
        public function exec(string $statement): int|false { return $this->connection->exec($statement); }
        public function beginTransaction(): bool { return $this->connection->beginTransaction(); }
        public function inTransaction(): bool { return $this->connection->inTransaction(); }
        public function rollBack(): bool { return $this->connection->rollBack(); }
        public function query(string $query, ?int $fetchMode = null, mixed ...$fetchModeArgs): PDOStatement|false {
            if (str_contains($query, 'SELECT * FROM `sample_rows`')) throw new RuntimeException('simulated table read failure');
            return $this->connection->query($query);
        }
        public function quote(string $string, int $type = PDO::PARAM_STR): string|false { return $this->connection->quote($string, $type); }
    };
    $before = glob(sys_get_temp_dir() . '/sharonstore_backups/sharonstore_auto_vercel_*') ?: [];
    try { generateScheduledBackupFile($failing, 'vercel'); throw new LogicException('Table read failure was hidden'); }
    catch (RuntimeException $e) { expectBackup($e->getMessage() === 'simulated table read failure', 'Wrong export failure'); }
    $after = glob(sys_get_temp_dir() . '/sharonstore_backups/sharonstore_auto_vercel_*') ?: [];
    expectBackup($before === $after && !$db->inTransaction(), 'Failed export left a partial file, published an incomplete backup or leaked a transaction');
    echo "PASS mid-export table failure rolls back and removes partial file\n";

    $beforeManual = glob($folder . '/*');
    try { generateBackupFile($folder . '/', $failing); throw new LogicException('Manual backup hid a table read failure'); }
    catch (RuntimeException $e) { expectBackup($e->getMessage() === 'simulated table read failure', 'Wrong manual backup failure'); }
    expectBackup($beforeManual === glob($folder . '/*') && !$db->inTransaction(), 'Failed manual backup left a file, pruned an existing backup, or leaked a transaction');
    $manual = generateBackupFile($folder . '/', $db, 'sharonstore_safety_pre_restore_');
    expectBackup(str_contains(file_get_contents($manual), 'INSERT INTO `sample_rows`') && is_file($folder . '/manual.sql'), 'Safety snapshot lost data or pruned the restore target');
    echo "PASS manual export rejects incomplete backups; complete safety snapshot preserves restore files\n";
} finally {
    foreach (glob($folder . '/*') ?: [] as $file) unlink($file);
    rmdir($folder);
}
