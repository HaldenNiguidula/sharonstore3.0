<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
if (($argv[1] ?? '') === '--worker') {
    putenv('CRON_SECRET=' . ($argv[2] ?? ''));
    $_SERVER['HTTP_AUTHORIZATION'] = $argv[3] ?? '';
    $_SERVER['REQUEST_METHOD'] = $argv[4] ?? 'GET';
    register_shutdown_function(static function (): void { fwrite(STDERR, (string)http_response_code()); });
    require __DIR__ . '/../api/backup_cron.php';
    exit;
}
foreach ([['secret', '', 'GET', 401], ['secret', 'Bearer wrong', 'GET', 401], ['', 'Bearer ', 'GET', 401], ['secret', 'Bearer secret', 'POST', 405]] as [$secret, $header, $method, $expected]) {
    $child = proc_open([PHP_BINARY, __FILE__, '--worker', $secret, $header, $method], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
    $body = stream_get_contents($pipes[1]); $status = stream_get_contents($pipes[2]);
    fclose($pipes[1]); fclose($pipes[2]);
    if (proc_close($child) !== 0 || $status !== (string)$expected) throw new RuntimeException('Cron authentication did not reject safely: ' . $status . $body);
}
echo "PASS cron rejects missing/wrong/unconfigured secrets and unsupported methods before database access\n";
