<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/db.php';

requireAdmin();
header('Content-Type: application/json');

function respond(bool $s, string $m = '', mixed $d = null) {
    echo json_encode(['success' => $s, 'message' => $m, 'data' => $d]);
    exit;
}

$action = trim($_POST['action'] ?? $_GET['action'] ?? '');
$db = getDB();

if ($action === 'add') {
    $company = trim($_POST['company_name'] ?? '');
    $contact = trim($_POST['contact_person'] ?? '');
    $phone   = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');

    if ($company === '') respond(false, 'Company name is required.');

    $stmt = $db->prepare("INSERT INTO tbl_suppliers (company_name, contact_person, phone, address, is_active) VALUES (?, ?, ?, ?, 1)");
    if ($stmt->execute([$company, $contact, $phone, $address])) {
        respond(true, 'Supplier added successfully.');
    } else {
        respond(false, 'Failed to add supplier.');
    }
}

if ($action === 'update') {
    $id      = (int)($_POST['supplier_id'] ?? 0);
    $company = trim($_POST['company_name'] ?? '');
    $contact = trim($_POST['contact_person'] ?? '');
    $phone   = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');

    if ($id <= 0) respond(false, 'Invalid supplier ID.');
    if ($company === '') respond(false, 'Company name is required.');

    $stmt = $db->prepare("UPDATE tbl_suppliers SET company_name=?, contact_person=?, phone=?, address=? WHERE supplier_id=? AND is_active=1");
    if ($stmt->execute([$company, $contact, $phone, $address, $id])) {
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
        respond(true, 'Supplier deleted successfully.');
    } else {
        respond(false, 'Failed to delete supplier.');
    }
}

respond(false, 'Invalid action.');
