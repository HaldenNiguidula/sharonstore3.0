<?php
require_once __DIR__ . '/includes/auth_check.php';
requireAdmin(); // RESTRICTED TO ADMIN/OWNER
require_once __DIR__ . '/includes/db.php';

$pageTitle = 'Suppliers';
$db = getDB();
$stmt = $db->query("SELECT * FROM tbl_suppliers WHERE is_active = 1 ORDER BY company_name ASC");
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
    position: relative;
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
.action-btns {
    position: absolute;
    top: 20px;
    right: 20px;
}
.action-btns button {
    background: none;
    border: none;
    color: var(--txt-muted);
    padding: 5px;
    margin-left: 5px;
    transition: color 0.2s;
}
.action-btns button:hover.edit-btn { color: var(--accent); }
.action-btns button:hover.delete-btn { color: #ef4444; }
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

        <div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3">
            <div>
                <ul class="breadcrumb-custom"><li><a href="/sharonstore3.0/dashboard.php">Home</a></li><li class="active">Suppliers</li></ul>
                <h2 class="page-header-title"><i class="fa-solid fa-truck-field me-2 text-emerald"></i>Manage Suppliers</h2>
                <p class="page-header-subtitle">View and contact your product suppliers directly.</p>
            </div>
            <div>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#supplierModal" onclick="openAddModal()">
                    <i class="fa-solid fa-plus me-2"></i>Add Supplier
                </button>
            </div>
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
                    $jData = htmlspecialchars(json_encode($s), ENT_QUOTES, 'UTF-8');
                ?>
                <div class="col-12 col-md-6 col-xl-4">
                    <div class="card supplier-card p-4">
                        <div class="action-btns">
                            <button class="edit-btn" title="Edit Supplier" onclick="openEditModal(<?= $jData ?>)"><i class="fa-solid fa-pencil"></i></button>
                            <button class="delete-btn" title="Delete Supplier" onclick="confirmDelete(<?= $s['supplier_id'] ?>)"><i class="fa-solid fa-trash"></i></button>
                        </div>
                        
                        <div class="d-flex align-items-start justify-content-between">
                            <div class="supplier-icon-wrap">
                                <i class="fa-solid fa-building"></i>
                            </div>
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

<!-- Add/Edit Supplier Modal -->
<div class="modal fade" id="supplierModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header border-0 bg-light">
                <h5 class="modal-title fw-bold" id="modalTitle">Add Supplier</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="supplierForm">
                <div class="modal-body p-4">
                    <input type="hidden" name="action" id="formAction" value="add">
                    <input type="hidden" name="supplier_id" id="supplier_id" value="">
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Company Name <span class="text-danger">*</span></label>
                        <input type="text" name="company_name" id="company_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Contact Person</label>
                        <input type="text" name="contact_person" id="contact_person" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Phone Number</label>
                        <input type="text" name="phone" id="phone" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Address</label>
                        <textarea name="address" id="address" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4 fw-bold" id="saveBtn">Save Supplier</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="/sharonstore3.0/assets/js/app.js"></script>
<script>
const supplierModal = new bootstrap.Modal(document.getElementById('supplierModal'));

function openAddModal() {
    document.getElementById('modalTitle').textContent = 'Add Supplier';
    document.getElementById('formAction').value = 'add';
    document.getElementById('supplier_id').value = '';
    document.getElementById('supplierForm').reset();
}

function openEditModal(data) {
    document.getElementById('modalTitle').textContent = 'Edit Supplier';
    document.getElementById('formAction').value = 'update';
    document.getElementById('supplier_id').value = data.supplier_id;
    document.getElementById('company_name').value = data.company_name;
    document.getElementById('contact_person').value = data.contact_person;
    document.getElementById('phone').value = data.phone;
    document.getElementById('address').value = data.address;
    supplierModal.show();
}

document.getElementById('supplierForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const btn = document.getElementById('saveBtn');
    btn.disabled = true;
    btn.textContent = 'Saving...';

    const formData = new FormData(e.target);
    try {
        const res = await fetch('/sharonstore3.0/api/suppliers.php', {
            method: 'POST',
            body: formData
        });
        const json = await res.json();
        if (json.success) {
            supplierModal.hide();
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: json.message,
                timer: 1500,
                showConfirmButton: false
            }).then(() => location.reload());
        } else {
            Swal.fire('Error', json.message, 'error');
            btn.disabled = false;
            btn.textContent = 'Save Supplier';
        }
    } catch (err) {
        console.error(err);
        Swal.fire('Error', 'A network error occurred.', 'error');
        btn.disabled = false;
        btn.textContent = 'Save Supplier';
    }
});

function confirmDelete(id) {
    Swal.fire({
        title: 'Delete Supplier?',
        text: "This supplier will be removed from the list.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, delete it!'
    }).then(async (result) => {
        if (result.isConfirmed) {
            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('supplier_id', id);
            
            const res = await fetch('/sharonstore3.0/api/suppliers.php', {
                method: 'POST',
                body: formData
            });
            const json = await res.json();
            if (json.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Deleted!',
                    text: 'Supplier has been deleted.',
                    timer: 1500,
                    showConfirmButton: false
                }).then(() => location.reload());
            } else {
                Swal.fire('Error', json.message, 'error');
            }
        }
    });
}
</script>
</body>
</html>
