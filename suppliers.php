<?php
require_once __DIR__ . '/includes/auth_check.php';
requireAdmin(); // RESTRICTED TO ADMIN/OWNER
require_once __DIR__ . '/includes/db.php';

$pageTitle = 'Suppliers';
$db = getDB();
$stmt = $db->query("SELECT * FROM tbl_suppliers ORDER BY company_name ASC");
$suppliers = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Suppliers - Sharon Store</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css?v=3">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="/sharonstore3.0/assets/css/style.css?v=17">
<style>
.supplier-card {
    border: none;
    border-radius: var(--r);
    box-shadow: var(--sh-sm);
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    height: 100%;
}
.supplier-card:hover {
    transform: translateY(-3px);
    box-shadow: var(--sh-md);
}
.supplier-icon-wrap {
    width: 60px;
    height: 60px;
    border-radius: 16px;
    background: var(--accent-lt);
    color: var(--accent);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    margin-bottom: 1rem;
}
.supplier-name {
    font-size: 1.25rem;
    font-weight: 800;
    color: var(--txt);
    margin-bottom: 0.25rem;
}
.supplier-contact {
    font-size: 0.9rem;
    color: var(--txt-muted);
    font-weight: 600;
    margin-bottom: 1rem;
}
.contact-btn {
    border-radius: 8px;
    font-weight: 700;
    padding: 0.5rem 1rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    flex: 1;
}
.btn-whatsapp {
    background-color: #25D366;
    color: white;
    border: none;
}
.btn-whatsapp:hover {
    background-color: #128C7E;
    color: white;
}
@media(max-width:767px){
  .page-header{flex-direction:column!important;align-items:flex-start!important;gap:10px!important;}
}
</style>
</head>
<body>
<div class="app-wrapper">
    <?php require_once __DIR__ . '/includes/sidebar.php'; ?>
    <div class="main-content">
        <?php require_once __DIR__ . '/includes/header.php'; ?>

        <div class="page-header d-flex justify-content-between align-items-center">
            <div>
                <ul class="breadcrumb-custom"><li><a href="/sharonstore3.0/dashboard.php">Home</a></li><li class="active">Suppliers</li></ul>
                <h2 class="page-header-title"><i class="fa-solid fa-truck-field me-2 text-emerald"></i>Manage Suppliers</h2>
                <p class="page-header-subtitle">View and contact your product suppliers directly.</p>
            </div>
            <!-- <button class="btn btn-primary"><i class="fa-solid fa-plus me-2"></i>Add Supplier</button> -->
        </div>

        <div class="row g-3 mt-2">
            <?php if (empty($suppliers)): ?>
                <div class="col-12 text-center p-5">
                    <i class="fa-solid fa-truck-fast fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">No suppliers found.</h5>
                </div>
            <?php else: ?>
                <?php foreach ($suppliers as $s): 
                    $cleanPhone = preg_replace('/[^0-9]/', '', $s['phone']);
                ?>
                <div class="col-12 col-md-6 col-xl-4">
                    <div class="card supplier-card p-4">
                        <div class="d-flex align-items-start justify-content-between">
                            <div class="supplier-icon-wrap">
                                <i class="fa-solid fa-building"></i>
                            </div>
                            <span class="badge badge-success">Active</span>
                        </div>
                        
                        <div class="supplier-name"><?= htmlspecialchars($s['company_name']) ?></div>
                        <div class="supplier-contact">
                            <i class="fa-solid fa-user me-2"></i><?= htmlspecialchars($s['contact_person']) ?>
                        </div>
                        
                        <div class="mb-4" style="font-size:0.9rem; color:#64748b;">
                            <div class="mb-2"><i class="fa-solid fa-phone me-2 text-muted"></i><?= htmlspecialchars($s['phone']) ?></div>
                            <div><i class="fa-solid fa-location-dot me-2 text-muted"></i><?= htmlspecialchars($s['address']) ?></div>
                        </div>

                        <div class="d-flex gap-2 mt-auto">
                            <a href="tel:<?= htmlspecialchars($s['phone']) ?>" class="btn btn-primary contact-btn">
                                <i class="fa-solid fa-phone"></i> Call
                            </a>
                            <a href="sms:<?= htmlspecialchars($s['phone']) ?>" class="btn btn-secondary contact-btn">
                                <i class="fa-solid fa-comment-sms"></i> Text
                            </a>
                            <a href="https://wa.me/<?= $cleanPhone ?>" target="_blank" class="btn btn-whatsapp contact-btn">
                                <i class="fa-brands fa-whatsapp"></i> Chat
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
<script src="/sharonstore3.0/assets/js/app.js"></script>
</body>
</html>
