<?php
require_once __DIR__ . '/includes/auth_check.php';
requireAdmin(); // RESTRICTED TO ADMIN/OWNER
require_once __DIR__ . '/includes/db.php';

$pageTitle = 'Suppliers';
$db = getDB();
$stmt = $db->query("SELECT * FROM tbl_suppliers WHERE is_active = 1 ORDER BY company_name ASC");
$suppliers = $stmt->fetchAll();

// Fetch product categories from tbl_categories
$catStmt = $db->query("SELECT category_name FROM tbl_categories ORDER BY category_name ASC");
$storeCategories = $catStmt->fetchAll(PDO::FETCH_COLUMN);

// Extract all categories used across active suppliers (supporting multiple per supplier)
$allSupplierCategories = [];
foreach ($suppliers as $sup) {
    if (!empty($sup['category'])) {
        $parts = array_map('trim', explode(',', $sup['category']));
        foreach ($parts as $p) {
            if ($p !== '') $allSupplierCategories[] = $p;
        }
    }
}

// Combined unique categories for filtering and selection
$allCategories = array_values(array_unique(array_merge($storeCategories, $allSupplierCategories)));
sort($allCategories, SORT_NATURAL | SORT_FLAG_CASE);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Suppliers - Sharon Store</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css?v=3">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="/sharonstore3.0/assets/css/style.css?v=40">
<style>
.supplier-card {
    border: none;
    border-radius: var(--r);
    box-shadow: var(--sh-sm);
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    height: 100%;
    position: relative;
    background: var(--surface);
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
    margin-bottom: 0.85rem;
}
.supplier-name {
    font-size: 1.25rem;
    font-weight: 800;
    color: var(--txt);
    margin-bottom: 0.25rem;
}
.supplier-category-badge {
    display: inline-flex;
    align-items: center;
    font-size: 0.75rem;
    font-weight: 700;
    padding: 3px 10px;
    border-radius: 20px;
    background: var(--accent-lt);
    color: var(--accent-dk);
    border: 1px solid var(--accent-bd);
    letter-spacing: 0.02em;
    cursor: pointer;
    transition: all 0.15s ease;
}
.supplier-category-badge:hover {
    background: var(--accent);
    color: #fff;
    border-color: var(--accent);
}
.supplier-category-badge.empty {
    background: var(--surface-2);
    color: var(--txt-muted);
    border: 1px dashed var(--bd);
    font-weight: 600;
    cursor: default;
}
.supplier-category-badge.empty:hover {
    background: var(--surface-2);
    color: var(--txt-muted);
    border-color: var(--bd);
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
.filter-card {
    background: var(--surface);
    border: none;
    border-radius: var(--r);
    box-shadow: var(--sh-sm);
}
.category-row {
    background: #f8fafc;
    border: 1px solid var(--bd);
    border-radius: 8px;
    padding: 8px 10px;
    transition: all 0.2s;
}
.category-row:hover {
    border-color: var(--accent-bd);
    background: #ffffff;
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

        <div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3">
            <div>
                <ul class="breadcrumb-custom"><li><a href="/sharonstore3.0/dashboard.php">Home</a></li><li class="active">Suppliers</li></ul>
                <h2 class="page-header-title"><i class="fa-solid fa-truck-field me-2 text-emerald"></i>Manage Suppliers</h2>
                <p class="page-header-subtitle">View, categorize, and contact your wholesale product suppliers directly.</p>
            </div>
            <div>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#supplierModal" onclick="openAddModal()">
                    <i class="fa-solid fa-plus me-2"></i>Add Supplier
                </button>
            </div>
        </div>

        <!-- Filter & Search Toolbar -->
        <?php if (!empty($suppliers)): ?>
        <div class="card filter-card p-3 mb-3">
            <div class="row g-2 align-items-center">
                <div class="col-12 col-md-5">
                    <div class="input-group">
                        <span class="input-group-text bg-transparent border-end-0"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                        <input type="text" id="supplierSearch" class="form-control border-start-0" placeholder="Search suppliers, contacts, or categories..." oninput="applyFilters()">
                    </div>
                </div>
                <div class="col-12 col-md-4">
                    <div class="input-group">
                        <span class="input-group-text bg-transparent"><i class="fa-solid fa-filter text-muted"></i></span>
                        <select id="categoryFilter" class="form-select" onchange="applyFilters()">
                            <option value="">All Categories (<?= count($suppliers) ?>)</option>
                            <?php foreach ($allCategories as $cat): 
                                $catCount = 0;
                                foreach ($suppliers as $sup) {
                                    $cList = array_map('trim', explode(',', $sup['category'] ?? ''));
                                    if (in_array(strtolower($cat), array_map('strtolower', $cList))) {
                                        $catCount++;
                                    }
                                }
                            ?>
                                <option value="<?= htmlspecialchars($cat) ?>"><?= htmlspecialchars($cat) ?> (<?= $catCount ?>)</option>
                            <?php endforeach; ?>
                            <?php 
                            $uncatCount = count(array_filter($suppliers, fn($sup) => empty(trim($sup['category'] ?? ''))));
                            if ($uncatCount > 0):
                            ?>
                                <option value="__uncategorized__">Uncategorized (<?= $uncatCount ?>)</option>
                            <?php endif; ?>
                        </select>
                    </div>
                </div>
                <div class="col-12 col-md-3 text-md-end text-muted small d-flex justify-content-between justify-content-md-end align-items-center gap-2">
                    <span id="filterStats"><strong id="visibleCount"><?= count($suppliers) ?></strong> of <?= count($suppliers) ?> supplier(s)</span>
                    <button type="button" class="btn btn-outline-secondary btn-sm py-1 px-2 d-none" id="clearFilterBtn" onclick="resetFilters()" title="Reset filters">
                        <i class="fa-solid fa-rotate-left me-1"></i>Reset
                    </button>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <div class="row g-3 mt-1" id="supplierListContainer">
            <?php if (empty($suppliers)): ?>
                <div class="col-12 text-center p-5">
                    <i class="fa-solid fa-truck-fast fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">No suppliers found.</h5>
                    <p class="text-muted small">Click "Add Supplier" above to register your first supplier.</p>
                </div>
            <?php else: ?>
                <?php foreach ($suppliers as $s): 
                    $cleanPhone = preg_replace('/[^0-9]/', '', $s['phone'] ?? '');
                    $jData = htmlspecialchars(json_encode($s), ENT_QUOTES, 'UTF-8');
                    $catList = array_values(array_filter(array_map('trim', explode(',', $s['category'] ?? ''))));
                ?>
                <div class="col-12 col-md-6 col-xl-4 supplier-card-col"
                     data-company="<?= htmlspecialchars(strtolower($s['company_name'] ?? '')) ?>"
                     data-contact="<?= htmlspecialchars(strtolower($s['contact_person'] ?? '')) ?>"
                     data-categories='<?= htmlspecialchars(json_encode(array_map('strtolower', $catList)), ENT_QUOTES, 'UTF-8') ?>'
                     data-address="<?= htmlspecialchars(strtolower($s['address'] ?? '')) ?>">
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

                        <!-- Supplier Category Information Badges (Multiple Supported) -->
                        <div class="mb-2 d-flex flex-wrap gap-1 align-items-center">
                            <?php if (!empty($catList)): ?>
                                <?php foreach ($catList as $c): ?>
                                    <span class="supplier-category-badge" title="Filter by <?= htmlspecialchars($c) ?>" onclick="filterByCategory('<?= htmlspecialchars(addslashes($c)) ?>')">
                                        <i class="fa-solid fa-tag me-1 text-emerald"></i><?= htmlspecialchars($c) ?>
                                    </span>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <span class="supplier-category-badge empty" title="No category assigned">
                                    <i class="fa-solid fa-tag me-1"></i>Uncategorized
                                </span>
                            <?php endif; ?>
                        </div>

                        <div class="supplier-contact">
                            <i class="fa-solid fa-user me-2"></i><?= htmlspecialchars($s['contact_person'] ?: 'No contact person specified') ?>
                        </div>
                        
                        <div class="mb-4" style="font-size:0.9rem; color:#64748b;">
                            <div class="mb-2"><i class="fa-solid fa-phone me-2 text-muted"></i><?= htmlspecialchars($s['phone'] ?: 'N/A') ?></div>
                            <div><i class="fa-solid fa-location-dot me-2 text-muted"></i><?= htmlspecialchars($s['address'] ?: 'N/A') ?></div>
                        </div>

                        <div class="d-flex gap-2 mt-auto">
                            <?php if (!empty($cleanPhone)): ?>
                                <a href="tel:<?= htmlspecialchars($s['phone']) ?>" class="btn btn-primary contact-btn">
                                    <i class="fa-solid fa-phone"></i> Call
                                </a>
                                <a href="sms:<?= htmlspecialchars($s['phone']) ?>" class="btn btn-secondary contact-btn">
                                    <i class="fa-solid fa-comment-sms"></i> Text
                                </a>
                                <a href="https://wa.me/<?= $cleanPhone ?>" target="_blank" class="btn btn-whatsapp contact-btn">
                                    <i class="fa-brands fa-whatsapp"></i> Chat
                                </a>
                            <?php else: ?>
                                <span class="text-muted small fst-italic py-2">No phone number on record</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>

                <div id="noMatchMessage" class="col-12 text-center p-5 d-none">
                    <i class="fa-solid fa-filter-circle-xmark fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">No suppliers match your search or filter.</h5>
                    <button class="btn btn-sm btn-outline-primary mt-2" onclick="resetFilters()">
                        <i class="fa-solid fa-rotate-left me-1"></i>Clear Filters
                    </button>
                </div>
            <?php endif; ?>
        </div>

    </div>
</div>

<!-- Add/Edit Supplier Modal -->
<div class="modal fade" id="supplierModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
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
                        <input type="text" name="company_name" id="company_name" class="form-control" required placeholder="e.g. San Miguel Foods">
                    </div>

                    <!-- Multiple Supplier Categories Option -->
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label fw-bold mb-0">Supplier Categories</label>
                            <button type="button" class="btn btn-sm text-white fw-bold d-inline-flex align-items-center gap-1 shadow-sm px-3 py-1"
                                    id="btnAddCategoryRow"
                                    onclick="addCategoryRow()"
                                    style="background:#0abf8a; border:1px solid #089e72; border-radius:8px; font-size:0.84rem; transition:all 0.15s ease;"
                                    onmouseover="this.style.background='#089e72'"
                                    onmouseout="this.style.background='#0abf8a'">
                                <i class="fa-solid fa-plus me-1"></i>Add Another
                            </button>
                        </div>
                        <small class="text-muted d-block mb-2">Assign one or multiple product categories supplied by this vendor.</small>
                        
                        <div id="categoryRowsContainer" class="d-flex flex-column gap-2">
                            <!-- Category rows populated dynamically -->
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Contact Person</label>
                        <input type="text" name="contact_person" id="contact_person" class="form-control" placeholder="e.g. Juan dela Cruz">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Phone Number</label>
                        <input type="text" name="phone" id="phone" class="form-control" placeholder="e.g. +639171234567">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Address</label>
                        <textarea name="address" id="address" class="form-control" rows="3" placeholder="e.g. Warehouse 4, Manila, Philippines"></textarea>
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
<script src="/sharonstore3.0/assets/js/app.js?v=35"></script>
<script>
const supplierModal = new bootstrap.Modal(document.getElementById('supplierModal'));

// Categories available from server
const storeCategories = <?= json_encode($storeCategories) ?>;
const customExistingCategories = <?= json_encode(array_values(array_diff($allCategories, $storeCategories))) ?>;

function getCategorySelectOptions(selectedVal) {
    let html = '<option value="">-- Select Category --</option>';
    html += '<optgroup label="Store Product Categories">';
    storeCategories.forEach(c => {
        html += `<option value="${esc(c)}">${esc(c)}</option>`;
    });
    html += '</optgroup>';
    if (customExistingCategories.length) {
        html += '<optgroup label="Other Supplier Categories">';
        customExistingCategories.forEach(c => {
            html += `<option value="${esc(c)}">${esc(c)}</option>`;
        });
        html += '</optgroup>';
    }
    html += '<option value="__custom__">+ Enter Custom Category...</option>';
    return html;
}

function addCategoryRow(initialVal = '') {
    const container = document.getElementById('categoryRowsContainer');
    const rowId = 'catRow_' + Date.now() + '_' + Math.floor(Math.random() * 1000);
    const row = document.createElement('div');
    row.className = 'category-row';
    row.id = rowId;
    row.innerHTML = `
        <div class="d-flex align-items-center gap-2">
            <span class="text-muted"><i class="fa-solid fa-layer-group"></i></span>
            <select class="form-select form-select-sm cat-row-select" onchange="onCategoryRowSelectChange(this)">
                ${getCategorySelectOptions(initialVal)}
            </select>
            <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeCategoryRow('${rowId}')" title="Remove category">
                <i class="fa-solid fa-trash"></i>
            </button>
        </div>
        <div class="cat-custom-wrap mt-2 d-none">
            <div class="input-group input-group-sm">
                <span class="input-group-text"><i class="fa-solid fa-pen"></i></span>
                <input type="text" class="form-control cat-custom-input" placeholder="Type custom category name (e.g. Fresh Meats)..." maxlength="100">
                <button type="button" class="btn btn-outline-secondary" onclick="cancelCustomInRow('${rowId}')" title="Choose from list">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
        </div>
    `;
    container.appendChild(row);

    const sel = row.querySelector('.cat-row-select');
    const customWrap = row.querySelector('.cat-custom-wrap');
    const customInp = row.querySelector('.cat-custom-input');

    if (initialVal) {
        let matched = false;
        for (let i = 0; i < sel.options.length; i++) {
            if (sel.options[i].value && sel.options[i].value.toLowerCase() === initialVal.toLowerCase()) {
                sel.selectedIndex = i;
                matched = true;
                break;
            }
        }
        if (!matched) {
            sel.value = '__custom__';
            customWrap.classList.remove('d-none');
            customInp.value = initialVal;
        }
    }
}

function removeCategoryRow(rowId) {
    const row = document.getElementById(rowId);
    if (!row) return;
    const container = document.getElementById('categoryRowsContainer');
    if (container.children.length > 1) {
        row.remove();
    } else {
        // If it is the only row, reset it to empty
        const sel = row.querySelector('.cat-row-select');
        const customWrap = row.querySelector('.cat-custom-wrap');
        const customInp = row.querySelector('.cat-custom-input');
        sel.value = '';
        customWrap.classList.add('d-none');
        customInp.value = '';
    }
}

function onCategoryRowSelectChange(sel) {
    const row = sel.closest('.category-row');
    const customWrap = row.querySelector('.cat-custom-wrap');
    const customInp = row.querySelector('.cat-custom-input');
    if (sel.value === '__custom__') {
        customWrap.classList.remove('d-none');
        customInp.focus();
    } else {
        customWrap.classList.add('d-none');
        customInp.value = '';
    }
}

function cancelCustomInRow(rowId) {
    const row = document.getElementById(rowId);
    if (!row) return;
    const sel = row.querySelector('.cat-row-select');
    const customWrap = row.querySelector('.cat-custom-wrap');
    const customInp = row.querySelector('.cat-custom-input');
    sel.value = '';
    customWrap.classList.add('d-none');
    customInp.value = '';
}

function openAddModal() {
    document.getElementById('modalTitle').textContent = 'Add Supplier';
    document.getElementById('formAction').value = 'add';
    document.getElementById('supplier_id').value = '';
    document.getElementById('supplierForm').reset();
    document.getElementById('categoryRowsContainer').innerHTML = '';
    addCategoryRow(); // Initial empty category row
}

function openEditModal(data) {
    document.getElementById('modalTitle').textContent = 'Edit Supplier';
    document.getElementById('formAction').value = 'update';
    document.getElementById('supplier_id').value = data.supplier_id;
    document.getElementById('company_name').value = data.company_name || '';
    document.getElementById('contact_person').value = data.contact_person || '';
    document.getElementById('phone').value = data.phone || '';
    document.getElementById('address').value = data.address || '';

    // Handle multiple categories population
    const container = document.getElementById('categoryRowsContainer');
    container.innerHTML = '';
    const cats = (data.category || '').split(',').map(s => s.trim()).filter(Boolean);
    if (cats.length > 0) {
        cats.forEach(c => addCategoryRow(c));
    } else {
        addCategoryRow();
    }

    supplierModal.show();
}

document.getElementById('supplierForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const btn = document.getElementById('saveBtn');
    btn.disabled = true;
    btn.textContent = 'Saving...';

    const formData = new FormData(e.target);

    // Compute multiple categories value
    const rows = document.querySelectorAll('#categoryRowsContainer .category-row');
    const selectedCategories = [];
    rows.forEach(r => {
        const sel = r.querySelector('.cat-row-select').value;
        const custom = r.querySelector('.cat-custom-input').value.trim();
        let val = '';
        if (sel === '__custom__') {
            val = custom;
        } else if (sel) {
            val = sel;
        }
        if (val && !selectedCategories.some(c => c.toLowerCase() === val.toLowerCase())) {
            selectedCategories.push(val);
        }
    });

    formData.set('category', selectedCategories.join(', '));

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

// Client-side search and category filtering
function applyFilters() {
    const searchVal = (document.getElementById('supplierSearch')?.value || '').toLowerCase().trim();
    const catVal = (document.getElementById('categoryFilter')?.value || '').toLowerCase().trim();
    const cards = document.querySelectorAll('.supplier-card-col');
    let visibleCount = 0;

    cards.forEach(card => {
        const cName = card.getAttribute('data-company') || '';
        const cContact = card.getAttribute('data-contact') || '';
        const cAddress = card.getAttribute('data-address') || '';
        let cCategories = [];
        try {
            cCategories = JSON.parse(card.getAttribute('data-categories') || '[]');
        } catch(e) {}

        const matchesSearch = !searchVal || 
            cName.includes(searchVal) || 
            cContact.includes(searchVal) || 
            cCategories.some(cat => cat.includes(searchVal)) || 
            cAddress.includes(searchVal);

        let matchesCat = true;
        if (catVal === '__uncategorized__') {
            matchesCat = (cCategories.length === 0);
        } else if (catVal) {
            matchesCat = cCategories.includes(catVal);
        }

        if (matchesSearch && matchesCat) {
            card.classList.remove('d-none');
            visibleCount++;
        } else {
            card.classList.add('d-none');
        }
    });

    const visibleEl = document.getElementById('visibleCount');
    if (visibleEl) visibleEl.textContent = visibleCount;

    const noMatchMsg = document.getElementById('noMatchMessage');
    if (noMatchMsg) {
        if (visibleCount === 0 && cards.length > 0) {
            noMatchMsg.classList.remove('d-none');
        } else {
            noMatchMsg.classList.add('d-none');
        }
    }

    const clearBtn = document.getElementById('clearFilterBtn');
    if (clearBtn) {
        if (searchVal || catVal) {
            clearBtn.classList.remove('d-none');
        } else {
            clearBtn.classList.add('d-none');
        }
    }
}

function filterByCategory(cat) {
    const catFilter = document.getElementById('categoryFilter');
    if (!catFilter) return;

    let matched = false;
    for (let opt of catFilter.options) {
        if (opt.value && opt.value.toLowerCase() === cat.toLowerCase()) {
            catFilter.value = opt.value;
            matched = true;
            break;
        }
    }
    if (!matched) {
        // If exact option text wasn't found, use search bar
        document.getElementById('supplierSearch').value = cat;
    }
    applyFilters();
    catFilter.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

function resetFilters() {
    const searchInp = document.getElementById('supplierSearch');
    const catFilter = document.getElementById('categoryFilter');
    if (searchInp) searchInp.value = '';
    if (catFilter) catFilter.value = '';
    applyFilters();
}
</script>
</body>
</html>
