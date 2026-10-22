<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/audit.php';

requireAdmin();
header('Content-Type: application/json');

function respond(bool $s, string $m = '', mixed $d = null) {
    echo json_encode(['success' => $s, 'message' => $m, 'data' => $d]);
    exit;
}

$action = trim($_POST['action'] ?? $_GET['action'] ?? '');
$db = getDB();
$userId = currentUserId();

if ($action === 'list') {
    $stmt = $db->query("SELECT * FROM tbl_suppliers WHERE is_active = 1 ORDER BY company_name ASC");
    respond(true, 'OK', $stmt->fetchAll());
}

if ($action === 'add') {
    $company  = trim($_POST['company_name'] ?? '');
    $contact  = trim($_POST['contact_person'] ?? '');
    $phone    = trim($_POST['phone'] ?? '');
    $address  = trim($_POST['address'] ?? '');

    $category = trim($_POST['category'] ?? '');
    if (isset($_POST['categories'])) {
        $catsRaw = is_array($_POST['categories']) ? $_POST['categories'] : [$_POST['categories']];
        $clean = array_filter(array_map('trim', $catsRaw), fn($c) => $c !== '');
        if (!empty($clean)) {
            $category = implode(', ', array_unique($clean));
        }
    }

    if ($company === '') respond(false, 'Company name is required.');

    $stmt = $db->prepare("INSERT INTO tbl_suppliers (company_name, contact_person, category, phone, address, is_active) VALUES (?, ?, ?, ?, ?, 1)");
    if ($stmt->execute([$company, $contact, $category !== '' ? $category : null, $phone, $address])) {
        $newId = (int)$db->lastInsertId();
        logAudit($userId, 'ADD_SUPPLIER', 'Suppliers', "Added supplier #{$newId}: {$company}" . ($category !== '' ? " [Categories: {$category}]" : ''));
        respond(true, 'Supplier added successfully.', ['supplier_id' => $newId]);
    } else {
        respond(false, 'Failed to add supplier.');
    }
}

if ($action === 'update') {
    $id       = (int)($_POST['supplier_id'] ?? 0);
    $company  = trim($_POST['company_name'] ?? '');
    $contact  = trim($_POST['contact_person'] ?? '');
    $phone    = trim($_POST['phone'] ?? '');
    $address  = trim($_POST['address'] ?? '');

    $category = trim($_POST['category'] ?? '');
    if (isset($_POST['categories'])) {
        $catsRaw = is_array($_POST['categories']) ? $_POST['categories'] : [$_POST['categories']];
        $clean = array_filter(array_map('trim', $catsRaw), fn($c) => $c !== '');
        if (!empty($clean)) {
            $category = implode(', ', array_unique($clean));
        }
    }

    if ($id <= 0) respond(false, 'Invalid supplier ID.');
    if ($company === '') respond(false, 'Company name is required.');

    $stmt = $db->prepare("UPDATE tbl_suppliers SET company_name=?, contact_person=?, category=?, phone=?, address=? WHERE supplier_id=? AND is_active=1");
    if ($stmt->execute([$company, $contact, $category !== '' ? $category : null, $phone, $address, $id])) {
        logAudit($userId, 'UPDATE_SUPPLIER', 'Suppliers', "Updated supplier #{$id}: {$company}" . ($category !== '' ? " [Categories: {$category}]" : ''));
        respond(true, 'Supplier updated successfully.');
    } else {
        respond(false, 'Failed to update supplier.');
    }
}

if ($action === 'delete') {
    $id = (int)($_POST['supplier_id'] ?? 0);
    if ($id <= 0) respond(false, 'Invalid supplier ID.');

    // Soft delete
    $stmt = $db->prepare("UPDATE tbl_suppliers SET is_active = 0 WHERE supplier_id = ?");
    if ($stmt->execute([$id])) {
        logAudit($userId, 'DELETE_SUPPLIER', 'Suppliers', "Soft-deleted supplier #{$id}");
        respond(true, 'Supplier deleted successfully.');
    } else {
        respond(false, 'Failed to delete supplier.');
    }
}

respond(false, 'Invalid action.');
