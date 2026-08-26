<?php
// ============================================================
// Auth Check — Sharon Store System
// ============================================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function requireLogin(): void {
    if (!isset($_SESSION['user_id'])) {
        // If it's an API/AJAX request, return JSON 401 instead of redirect
        $isAjax = (
            (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') ||
            (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json')) ||
            str_contains($_SERVER['REQUEST_URI'] ?? '', '/api/')
        );
        if ($isAjax) {
            header('Content-Type: application/json');
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Session expired. Please log in again.']);
            exit();
        }
        header('Location: /sharonstore3.0/index.php');
        exit();
    }
}

function requireAdmin(): void {
    requireLogin();
    if ($_SESSION['role'] !== 'admin') {
        header('Location: /sharonstore3.0/pos.php');
        exit();
    }
}

function requireCashier(): void {
    requireLogin();
}

function isAdmin(): bool {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function currentUserId(): int {
    return (int)($_SESSION['user_id'] ?? 0);
}

function currentUserName(): string {
    return $_SESSION['full_name'] ?? 'Unknown';
}

function currentRole(): string {
    return $_SESSION['role'] ?? 'cashier';
}
