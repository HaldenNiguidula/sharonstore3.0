<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
if (($argv[1] ?? '') === '--worker') {
    // An unreachable database must not prevent this public clock from working.
    putenv('DB_HOST=127.0.0.1'); putenv('DB_PORT=1');
    $_GET = ['action' => 'server_time'];
    $_SERVER['REQUEST_METHOD'] = $argv[2];
    http_response_code(200);
    register_shutdown_function(static function (): void { fwrite(STDERR, (string)http_response_code()); });
    require __DIR__ . '/../api/index.php';
    exit;
}
$functions = glob(__DIR__ . '/../api/*.php');
if (count($functions) > 12) throw new RuntimeException('Deployment exceeds twelve PHP functions.');
foreach (['GET' => 200, 'POST' => 405] as $method => $expected) {
    $child = proc_open([PHP_BINARY, __FILE__, '--worker', $method], [1 => ['pipe','w'], 2 => ['pipe','w']], $pipes);
    $body = stream_get_contents($pipes[1]); $status = stream_get_contents($pipes[2]);
    fclose($pipes[1]); fclose($pipes[2]);
    if (proc_close($child) !== 0 || $status !== (string)$expected) throw new RuntimeException('Clock routing failed: ' . $status . $body);
    if ($method === 'GET') {
        $clock = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        if (!$clock['success'] || $clock['timezone'] !== 'Asia/Manila' || abs($clock['unix_ms'] - microtime(true) * 1000) > 3000) {
            throw new RuntimeException('Clock response is invalid.');
        }
    }
}
echo 'PASS ' . count($functions) . " PHP functions; router clock returns current PHT metadata without DB access; POST rejected\n";
