<?php
// ============================================================
// Accounts API — Sharon Store System
// ============================================================
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/audit.php';

requireAdmin();
header('Content-Type: application/json');

function respond(bool $s, string $m = '', mixed $d = null): never {
    echo json_encode(['success' => $s, 'message' => $m, 'data' => $d]);
    exit();
}

$action = trim($_POST['action'] ?? $_GET['action'] ?? '');
$db     = getDB();
$userId = currentUserId();

// ── List ──────────────────────────────────────────────────────
if ($action === 'list') {
    $stmt = $db->query("SELECT user_id, username, full_name, role, is_active, created_at FROM tbl_users ORDER BY created_at DESC");
    respond(true, 'OK', $stmt->fetchAll());
}

// ── Get ───────────────────────────────────────────────────────
if ($action === 'get') {
    $id = (int)($_GET['user_id'] ?? 0);
    if (!$id) respond(false, 'user_id required.', null);
    $stmt = $db->prepare("SELECT user_id, username, full_name, role, is_active FROM tbl_users WHERE user_id = :id");
    $stmt->execute([':id' => $id]);
    $u = $stmt->fetch();
    if (!$u) respond(false, 'User not found.', null);
    respond(true, 'OK', $u);
}

// ── Create ────────────────────────────────────────────────────
if ($action === 'create') {
    $fullName = trim($_POST['full_name'] ?? '');
    $username = trim($_POST['username']  ?? '');
    $password = trim($_POST['password']  ?? '');
    $confirm  = trim($_POST['confirm_password'] ?? '');
    $role     = trim($_POST['role']      ?? 'cashier');

    $errors = [];
    if ($fullName === '')   $errors[] = 'Full name is required.';
    if ($username === '')   $errors[] = 'Username is required.';
    if ($password === '')   $errors[] = 'Password is required.';
    if (strlen($password) < 6) $errors[] = 'Password must be at least 6 characters.';
    if ($password !== $confirm) $errors[] = 'Passwords do not match.';
    if (!in_array($role, ['admin','cashier'], true)) $errors[] = 'Invalid role.';

    if (!$errors) {
        $chk = $db->prepare("SELECT COUNT(*) FROM tbl_users WHERE username = :u");
        $chk->execute([':u' => $username]);
        if ((int)$chk->fetchColumn() > 0) $errors[] = 'Username already exists.';
    }

    if ($errors) respond(false, implode(' ', $errors), null);

    $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    $ins  = $db->prepare("INSERT INTO tbl_users (username, password, full_name, role, is_active) VALUES (:u,:p,:n,:r,1)");
    $ins->execute([':u'=>$username, ':p'=>$hash, ':n'=>$fullName, ':r'=>$role]);
    $newId = (int)$db->lastInsertId();

    logAudit($userId, 'CREATE_USER', 'Accounts', "Created user #{$newId}: {$username} ({$role})");
    respond(true, "Account for {$fullName} created successfully.", ['user_id' => $newId]);
}

// ── Update ────────────────────────────────────────────────────
if ($action === 'update') {
    $targetId = (int)($_POST['user_id']    ?? 0);
    $fullName = trim($_POST['full_name']   ?? '');
    $username = trim($_POST['username']    ?? '');
    $role     = trim($_POST['role']        ?? 'cashier');
    $isActive = (int)($_POST['is_active']  ?? 1);
    $newPwd   = trim($_POST['new_password']?? '');

    $errors = [];
    if (!$targetId)    $errors[] = 'User ID required.';
    if ($fullName==='') $errors[] = 'Full name required.';
    if ($username==='') $errors[] = 'Username required.';
    if (!in_array($role, ['admin','cashier'], true)) $errors[] = 'Invalid role.';
    if ($newPwd !== '' && strlen($newPwd) < 6) $errors[] = 'Password must be at least 6 characters.';

    if (!$errors) {
        $chk = $db->prepare("SELECT COUNT(*) FROM tbl_users WHERE username = :u AND user_id != :id");
        $chk->execute([':u'=>$username, ':id'=>$targetId]);
        if ((int)$chk->fetchColumn() > 0) $errors[] = 'Username already taken.';
    }
    if ($errors) respond(false, implode(' ', $errors), null);

    if ($newPwd !== '') {
        $hash = password_hash($newPwd, PASSWORD_BCRYPT, ['cost' => 12]);
        $db->prepare("UPDATE tbl_users SET username=:u, full_name=:n, role=:r, is_active=:a, password=:p WHERE user_id=:id")
           ->execute([':u'=>$username,':n'=>$fullName,':r'=>$role,':a'=>$isActive,':p'=>$hash,':id'=>$targetId]);
    } else {
        $db->prepare("UPDATE tbl_users SET username=:u, full_name=:n, role=:r, is_active=:a WHERE user_id=:id")
           ->execute([':u'=>$username,':n'=>$fullName,':r'=>$role,':a'=>$isActive,':id'=>$targetId]);
    }

    logAudit($userId, 'UPDATE_USER', 'Accounts', "Updated user #{$targetId}: {$username}");
    respond(true, 'Account updated successfully.');
}

// ── Toggle Active ─────────────────────────────────────────────
if ($action === 'toggle_active') {
    $targetId = (int)($_POST['user_id'] ?? 0);
    if (!$targetId) respond(false, 'user_id required.');
    if ($targetId === $userId) respond(false, 'Cannot deactivate your own account.');

    $chk = $db->prepare("SELECT is_active, username FROM tbl_users WHERE user_id = :id");
    $chk->execute([':id'=>$targetId]);
    $u = $chk->fetch();
    if (!$u) respond(false, 'User not found.');

    $newStatus = $u['is_active'] ? 0 : 1;
    $db->prepare("UPDATE tbl_users SET is_active=:a WHERE user_id=:id")->execute([':a'=>$newStatus, ':id'=>$targetId]);

    $action_str = $newStatus ? 'ACTIVATE_USER' : 'DEACTIVATE_USER';
    logAudit($userId, $action_str, 'Accounts', "Set user #{$targetId} ({$u['username']}) active={$newStatus}");
    respond(true, 'Account status updated.', ['is_active' => $newStatus]);
}

// ── Delete ────────────────────────────────────────────────────
if ($action === 'delete') {
    $targetId = (int)($_POST['user_id'] ?? 0);
    if (!$targetId) respond(false, 'user_id required.');
    if ($targetId === $userId) respond(false, 'Cannot delete your own account.');

    $chk = $db->prepare("SELECT username FROM tbl_users WHERE user_id = :id");
    $chk->execute([':id'=>$targetId]);
    $u = $chk->fetch();
    if (!$u) respond(false, 'User not found.');

    $db->prepare("DELETE FROM tbl_users WHERE user_id = :id")->execute([':id'=>$targetId]);
    logAudit($userId, 'DELETE_USER', 'Accounts', "Deleted user #{$targetId}: {$u['username']}");
    respond(true, 'Account deleted.');
}

respond(false, 'Unknown action.');
