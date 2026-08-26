<?php
// logout.php
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/audit.php';

if (isset($_SESSION['user_id'])) {
    logAudit($_SESSION['user_id'], 'LOGOUT', 'Auth', "User {$_SESSION['username']} logged out.");
}

session_destroy();
header('Location: /sharonstore3.0/index.php');
exit();
