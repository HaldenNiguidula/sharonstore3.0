<?php
// ============================================================
// Vercel Serverless Entrypoint Router — Sharon Store
// ============================================================
ini_set('display_errors', '1');
error_reporting(E_ALL);

$rootDir = dirname(__DIR__);
$file = $_GET['file'] ?? '';

if (empty($file) || $file === '/' || $file === 'index') {
    $file = 'index.php';
}

if (($q = strpos($file, '?')) !== false) {
    $file = substr($file, 0, $q);
}

$file = ltrim($file, '/');

if (str_starts_with($file, 'sharonstore3.0/')) {
    $file = substr($file, strlen('sharonstore3.0/'));
}

if (!str_ends_with($file, '.php') && !str_contains($file, '.')) {
    $file .= '.php';
}

if (str_starts_with($file, 'api/')) {
    $target = $rootDir . '/api/' . basename(substr($file, 4));
} else {
    $target = $rootDir . '/' . basename($file);
}

if (file_exists($target)) {
    chdir(dirname($target));
    require $target;
    exit();
}

http_response_code(404);
header('Content-Type: text/html; charset=utf-8');
echo "<h1>404 Not Found</h1>";
echo "<p>Could not locate: " . htmlspecialchars($file) . "</p>";
