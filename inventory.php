<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Management - Sharon Store</title>
    <meta name="description" content="Manage grocery inventory add, update, and track stock levels.">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css?v=3">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="/sharonstore3.0/assets/css/style.css?v=17">
    <?php
    require_once __DIR__ . '/includes/auth_check.php';
    requireAdmin();
    $pageTitle = 'Inventory Management';
    ?>
<style>
@media(max-width:767px){
  .page-header{flex-direction:column!important;align-items:flex-start!important;}
  .page-header .btn{width:100%;}
  #invStats .col-6{padding:6px!important;}
  .stat-card{padding:14px!important;}
  .stat-value{font-size:1.2rem!important;}
}
</style>
</head>
<body>
<div class="app-wrapper">
    <?php require_once __DIR__ . '/includes/sidebar.php'; ?>
    <div class="main-content">
        <?php require_once __DIR__ . '/includes/header.php'; ?>

        <!-- Page Header -->
        <div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3">
            <div>
                <ul class="breadcrumb-custom"><li><a href="/sharonstore3.0/dashboard.php">Home</a></li><li class="active">Inventory</li></ul>
                <h1 class="page-header-title"><i class="fa-solid fa-boxes-stacked me-2 text-emerald"></i>Inventory Management</h1>
                <p class="page-header-subtitle">Add, update, and monitor stock levels for all grocery products.</p>
            </div>
            <button class="btn btn-primary" id="btnAddItem">
                <i class="fa-solid fa-plus me-2"></i>Add Item
            </button>
        </div>

        <!-- Stat Cards -->
        <div class="row g-3 mb-4" id="invStats">
            <div class="col-6 col-md-3">
                <div class="stat-card">
                    <div class="stat-icon emerald"><i class="fa-solid fa-boxes-stacked"></i></div>
                    <div class="stat-value" id="sTotal"></div>
                    <div class="stat-label">Total Items</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card">
                    <div class="stat-icon blue"><i class="fa-solid fa-peso-sign"></i></div>
                    <div class="stat-value" id="sValue"></div>
                    <div class="stat-label">Stock Value</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card">
                    <div class="stat-icon amber"><i class="fa-solid fa-triangle-exclamation"></i></div>
                    <div class="stat-value text-warning" id="sLow"></div>
                    <div class="stat-label">Low Stock</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card">
                    <div class="stat-icon red"><i class="fa-solid fa-ban"></i></div>
                    <div class="stat-value text-danger" id="sOut"></div>
                    <div class="stat-label">Out of Stock</div>
                </div>
            </div>
        </div>

        <!-- Search + Filter -->
        <div class="card mb-3">
            <div class="card-body py-3">
                <div class="row g-3 align-items-center" style="flex-wrap:wrap;">
                    <div class="col-12 col-md-5">
                        <div class="input-group">
                            <span class="input-group-text"><i class="fa-solid fa-search text-muted"></i></span>
                            <input type="text" id="searchInput" class="form-control" placeholder="Search by item name or barcode...">
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <select id="categoryFilter" class="form-select">
                            <option value="">All Categories</option>
                        </select>
                    </div>
                    <div class="col-6 col-md-2">
                        <select id="statusFilter" class="form-select">
                            <option value="">All Status</option>
                            <option value="ok">In Stock</option>
                            <option value="low">Low Stock</option>
                            <option value="out">Out of Stock</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-2 d-flex gap-2">
                        <button class="btn btn-secondary flex-fill" id="btnReset">Reset</button>
                        <button class="btn btn-outline-primary" id="btnManageCats" title="Manage Categories"
                                data-bs-toggle="modal" data-bs-target="#manageCatsModal">
                            <i class="fa-solid fa-tags"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Inventory Table -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fa-solid fa-table me-2 text-emerald"></i>Product List</h5>
                <span class="badge badge-emerald" id="itemCount">0 items</span>
            </div>
            <div class="card-body p-0">
                <div style="overflow-x:auto;-webkit-overflow-scrolling:touch;">
                    <table class="table table-custom mb-0" id="inventoryTable" style="min-width:900px;">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Barcode</th>
                                <th>Item Name</th>
                                <th>Category</th>
                                <th>Unit</th>
                                <th>Sell Price</th>
                                <th>Cost</th>
                                <th>Stock</th>
                                <th>Expiry</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="inventoryBody">
                            <tr><td colspan="11" class="text-center py-5">
                                <div class="loading-spinner mx-auto mb-2"></div>Loading inventory
                            </td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer d-flex justify-content-between align-items-center">
                <div class="text-muted small" id="pageInfo"></div>
                <div class="d-flex gap-2" id="pagination"></div>
            </div>
        </div>

    </div><!-- /main-content -->
</div>

<!-- -- Add/Edit Item Modal -- -->
<div class="modal fade" id="itemModal" tabindex="-1" aria-labelledby="itemModalTitle">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="itemModalTitle"><i class="fa-solid fa-box me-2 text-emerald"></i>Add Item</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="itemForm" novalidate>
                <div class="modal-body">
                    <input type="hidden" id="fItemId">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Barcode</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fa-solid fa-barcode"></i></span>
                                <input type="text" id="fBarcode" class="form-control" placeholder="Scan or type barcode, then press Enter">
                                <button type="button" class="btn btn-secondary" id="btnScanBarcode" title="Click then scan">
                                    <i class="fa-solid fa-qrcode me-1"></i>Scan
                                </button>
                            </div>
                            <div id="barcodeLookupStatus" style="margin-top:5px;font-size:0.78rem;font-weight:600;display:none;"></div>
                        </div>
                        <div class="col-12 col-md-8">
                            <label class="form-label">Item Name <span class="text-danger">*</span></label>
                            <input type="text" id="fItemName" class="form-control" placeholder="e.g. Ligo Sardines 155g" required>
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label">Category <span class="text-danger">*</span></label>
                            <select id="fCategory" class="form-select" required>
                                <option value="">Select</option>
                            </select>
                            <!-- Inline new-category row (hidden by default) -->
                            <div id="newCatRow" class="d-none mt-2">
                                <div class="input-group input-group-sm">
                                    <input type="text" id="newCatName" class="form-control"
                                           placeholder="New category name" maxlength="80"
                                           style="border-color:rgba(16,185,129,0.5);">
                                    <button type="button" class="btn btn-success btn-sm" id="btnCreateCat"
                                            style="white-space:nowrap;">
                                        <i class="fa-solid fa-plus me-1"></i>Create
                                    </button>
                                    <button type="button" class="btn btn-secondary btn-sm" id="btnCancelCat">
                                        <i class="fa-solid fa-xmark"></i>
                                    </button>
                                </div>
                                <div id="newCatMsg" class="mt-1" style="font-size:0.78rem;"></div>
                            </div>
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label">Unit <span class="text-danger">*</span></label>
                            <select id="fUnit" class="form-select" required>
                                <option value="pieces">Pieces</option>
                                <option value="packs">Packs</option>
                                <option value="kg">Kilograms (kg)</option>
                            </select>
                        </div>
                        <div class="col-6 col-md-4">
                            <label class="form-label">Selling Price (&#8369;) <span class="text-danger">*</span></label>
                            <input type="number" id="fPrice" class="form-control" placeholder="0.00" min="0" step="0.01" required>
                        </div>
                        <div class="col-6 col-md-4">
                            <label class="form-label">Cost Price (&#8369;) <span class="text-danger">*</span></label>
                            <input type="number" id="fCostPrice" class="form-control" placeholder="0.00" min="0" step="0.01" required>
                        </div>
                        <div class="col-6 col-md-4">
                            <label class="form-label">Stock Quantity <span class="text-danger">*</span></label>
                            <input type="number" id="fStockQty" class="form-control" placeholder="0" min="0" step="0.01" required>
                        </div>
                        <div class="col-6 col-md-4">
                            <label class="form-label">Low Stock Threshold <span class="text-danger">*</span></label>
                            <input type="number" id="fThreshold" class="form-control" placeholder="10" min="0" step="1" required>
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label">Expiry Date <small class="text-muted">(optional)</small></label>
                            <input type="date" id="fExpiry" class="form-control">
                        </div>
                    </div>
                    <div class="alert alert-danger mt-3 d-none" id="itemFormError"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="itemFormSubmit">
                        <i class="fa-solid fa-save me-2"></i>Save Item
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- -- Stock Adjust Modal -- -->
<div class="modal fade" id="adjustModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fa-solid fa-sliders me-2 text-emerald"></i>Adjust Stock</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small mb-3" id="adjustItemName"></p>
                <div class="mb-3">
                    <label class="form-label">Adjustment Type</label>
                    <select id="adjType" class="form-select">
                        <option value="add">Add Stock (+)</option>
                        <option value="subtract">Remove Stock (-)</option>
                        <option value="set">Set Exact Quantity</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Quantity</label>
                    <input type="number" id="adjQty" class="form-control" placeholder="0" min="0" step="0.01">
                </div>
                <div class="mb-3">
                    <label class="form-label">Reason <small class="text-muted">(optional)</small></label>
                    <input type="text" id="adjReason" class="form-control" placeholder="e.g. Delivery received">
                </div>
                <input type="hidden" id="adjItemId">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="btnAdjustConfirm">Apply</button>
            </div>
        </div>
    </div>
</div>

<!-- -- Delete Modal -- -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-danger"><i class="fa-solid fa-trash me-2"></i>Delete Item</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete <strong id="deleteItemName"></strong>?</p>
                <p class="text-muted small mb-0">This action cannot be undone.</p>
                <input type="hidden" id="deleteItemId">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="btnDeleteConfirm">Delete</button>
            </div>
        </div>
    </div>
</div>

<div class="toast-container position-fixed bottom-0 end-0 p-3" id="toastContainer"></div>
<!-- ---- Manage Categories Modal ---- -->
<div class="modal fade" id="manageCatsModal" tabindex="-1" aria-labelledby="manageCatsModalTitle">
<div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" style="max-width:520px;">
<div class="modal-content">
    <div class="modal-header">
        <h5 class="modal-title" id="manageCatsModalTitle">
            <i class="fa-solid fa-tags me-2 text-emerald"></i>Manage Categories
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
    </div>
    <div class="modal-body p-0">
        <table class="table table-custom mb-0" id="catTable">
            <thead>
                <tr>
                    <th>Category</th>
                    <th class="text-center">Products</th>
                    <th class="text-center">In Stock</th>
                    <th class="text-end">Action</th>
                </tr>
            </thead>
            <tbody id="catTableBody">
                <tr><td colspan="4" class="text-center py-4">
                    <div class="loading-spinner mx-auto mb-2"></div>Loading
                </td></tr>
            </tbody>
        </table>
    </div>
    <div class="modal-footer">
        <small class="text-muted me-auto">
            <i class="fa-solid fa-circle-info me-1"></i>
            Categories with stocked products require reassignment before deletion.
        </small>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
    </div>
</div></div></div>

<!-- ---- Delete Category Confirm Modal ---- -->
<div class="modal fade" id="deleteCatModal" tabindex="-1">
<div class="modal-dialog modal-dialog-centered" style="max-width:480px;">
<div class="modal-content">
    <div class="modal-header">
        <h5 class="modal-title" id="deleteCatTitle">
            <i class="fa-solid fa-trash me-2 text-danger"></i>Delete Category
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
    </div>
    <div class="modal-body" id="deleteCatBody"></div>
    <div class="modal-footer" id="deleteCatFooter"></div>
</div></div></div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
<script src="/sharonstore3.0/assets/js/app.js"></script>
<script>
let currentPage  = 1;
let categories   = [];
const itemModal  = new bootstrap.Modal('#itemModal');
const adjModal   = new bootstrap.Modal('#adjustModal');
const delModal   = new bootstrap.Modal('#deleteModal');

// -- Load Categories -------------------------------------------
async function loadCategories() {
    const r = await fetch('/sharonstore3.0/api/inventory.php?action=categories');
    const j = await r.json();
    if (!j.success) return;
    categories = j.data;

    const catSel = document.getElementById('categoryFilter');
    const fCat   = document.getElementById('fCategory');

    // Reset to base option only
    fCat.innerHTML = '<option value="">Select</option>';

    j.data.forEach(c => {
        const o1 = `<option value="${c.category_id}">${esc(c.category_name)}</option>`;
        catSel.innerHTML += o1;
        fCat.innerHTML   += o1;
    });

    // Append sentinel at bottom of form select
    fCat.innerHTML += `<option value="__new__" style="color:#10b981;font-weight:700;">+ New Category</option>`;
}

// -- New Category Inline Creator -------------------------------
function bindNewCategory() {
    const fCat      = document.getElementById('fCategory');
    const row       = document.getElementById('newCatRow');
    const nameInp   = document.getElementById('newCatName');
    const btnCreate = document.getElementById('btnCreateCat');
    const btnCancel = document.getElementById('btnCancelCat');
    const msg       = document.getElementById('newCatMsg');

    // Show/hide inline row when sentinel selected
    fCat.addEventListener('change', () => {
        if (fCat.value === '__new__') {
            row.classList.remove('d-none');
            nameInp.value = '';
            msg.textContent = '';
            nameInp.focus();
        } else {
            row.classList.add('d-none');
            msg.textContent = '';
        }
    });

    // Cancel  hide row and reset select
    btnCancel.addEventListener('click', () => {
        row.classList.add('d-none');
        fCat.value = '';
        nameInp.value = '';
        msg.textContent = '';
    });

    // Enter key in name field triggers create
    nameInp.addEventListener('keydown', e => { if (e.key === 'Enter') { e.preventDefault(); btnCreate.click(); } });

    // Create button
    btnCreate.addEventListener('click', async () => {
        const name = nameInp.value.trim();
        if (!name) { showMsg('Please enter a category name.', 'danger'); return; }

        btnCreate.disabled = true;
        btnCreate.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
        msg.textContent = '';

        try {
            const fd = new FormData();
            fd.append('action', 'add_category');
            fd.append('category_name', name);
            const r = await fetch('/sharonstore3.0/api/inventory.php', { method: 'POST', body: fd });
            const j = await r.json();

            if (j.success) {
                const cat = j.data;
                // Add to categories array
                categories.push(cat);

                // Add to both selects
                const fCatEl  = document.getElementById('fCategory');
                const catSel  = document.getElementById('categoryFilter');
                const newOpt  = `<option value="${cat.category_id}">${esc(cat.category_name)}</option>`;

                // Insert before the sentinel in form select
                const sentinel = fCatEl.querySelector('option[value="__new__"]');
                sentinel.insertAdjacentHTML('beforebegin', newOpt);

                // Add to filter select
                catSel.innerHTML += newOpt;

                // Auto-select the new category
                fCatEl.value = cat.category_id;
                row.classList.add('d-none');
                nameInp.value = '';
                showToast(`Category "${cat.category_name}" created!`, 'success');
            } else {
                showMsg(j.message || 'Failed to create category.', 'danger');
            }
        } catch (err) {
            showMsg('Network error. Try again.', 'danger');
        } finally {
            btnCreate.disabled = false;
            btnCreate.innerHTML = '<i class="fa-solid fa-plus me-1"></i>Create';
        }

        function showMsg(text, type) {
            msg.innerHTML = `<span class="text-${type}">${text}</span>`;
        }
    });
}


// -- Load Stats ------------------------------------------------
async function loadStats() {
    const r = await fetch('/sharonstore3.0/api/inventory.php?action=stats');
    const j = await r.json();
    if (!j.success) return;
    const d = j.data;
    document.getElementById('sTotal').textContent  = parseInt(d.total_items).toLocaleString();
    document.getElementById('sValue').textContent  = '\u20b1' + parseFloat(d.total_value).toLocaleString('en-PH',{minimumFractionDigits:2});
    document.getElementById('sLow').textContent    = parseInt(d.low_stock).toLocaleString();
    document.getElementById('sOut').textContent    = parseInt(d.out_of_stock).toLocaleString();
}

// -- Load Items ------------------------------------------------
async function loadItems(page = 1) {
    currentPage = page;
    const q    = document.getElementById('searchInput').value.trim();
    const cat  = document.getElementById('categoryFilter').value;
    const stat = document.getElementById('statusFilter').value;
    let url    = `/sharonstore3.0/api/inventory.php?action=list&page=${page}`;
    if (q)    url += `&q=${encodeURIComponent(q)}`;
    if (cat)  url += `&category_id=${cat}`;

    document.getElementById('inventoryBody').innerHTML = `<tr><td colspan="11" class="text-center py-5"><div class="loading-spinner mx-auto mb-2"></div>Loading</td></tr>`;

    const r = await fetch(url);
    const j = await r.json();
    if (!j.success) { showToast('Failed to load inventory.', 'error'); return; }

    let items = j.data.items;
    if (stat) items = items.filter(i => i.stock_status === stat);

    document.getElementById('itemCount').textContent = `${j.data.total} items`;
    document.getElementById('pageInfo').textContent  = `Page ${j.data.page} of ${j.data.total_pages}`;

    if (!items.length) {
        document.getElementById('inventoryBody').innerHTML = `<tr><td colspan="11" class="text-center py-5"><i class="fa-solid fa-box-open fa-2x text-muted mb-2"></i><br>No items found.</td></tr>`;
    } else {
        let html = '';
        items.forEach((item, idx) => {
            const rowCls = item.stock_status === 'out' ? 'table-row-danger' : item.stock_status === 'low' ? 'table-row-warning' : '';
            const sBadge = item.stock_status === 'out'
                ? '<span class="badge badge-danger">Out of Stock</span>'
                : item.stock_status === 'low'
                ? '<span class="badge badge-warning">Low Stock</span>'
                : '<span class="badge badge-success">In Stock</span>';

            // Safe local-date expiry parse (avoid UTC off-by-one)
            let expHtml = '<span style="color:#888888;">\u2014</span>';
            if (item.expiry_date) {
                const [ey,em,ed] = item.expiry_date.split('-').map(Number);
                const expDate = new Date(ey, em-1, ed);
                const days = Math.ceil((expDate - new Date()) / 86400000);
                const col = days <= 0 ? '#b91c1c' : days <= 30 ? '#b45309' : '#4a5568';
                const label = days <= 0 ? ' (EXPIRED)' : days <= 30 ? ` (${days}d)` : '';
                expHtml = `<span style="color:${col};font-weight:${days<=30?700:500};">${item.expiry_date}${label}</span>`;
            }

            html += `<tr class="${rowCls}">
                <td style="color:#555657;font-size:0.8rem;">${((page-1)*20)+idx+1}</td>
                <td><code style="font-size:0.78rem;">${esc(item.barcode||'\u2014')}</code></td>
                <td style="font-weight:700;color:#2C2D2D;">${esc(item.item_name)}</td>
                <td><span class="badge badge-secondary">${esc(item.category_name)}</span></td>
                <td style="color:#4a4b4b;">${item.unit}</td>
                <td style="color:#0abf8a;font-weight:700;">&#8369;${parseFloat(item.price).toFixed(2)}</td>
                <td style="color:#555657;">&#8369;${parseFloat(item.cost_price).toFixed(2)}</td>
                <td style="font-weight:700;color:#2C2D2D;">${parseFloat(item.stock_qty)} ${item.unit}</td>
                <td>${expHtml}</td>
                <td>${sBadge}</td>
                <td>
                    <div class="d-flex gap-1">
                        <button class="btn btn-sm btn-secondary" title="Adjust Stock" onclick="openAdjust(${item.item_id},'${esc(item.item_name)}')"><i class="fa-solid fa-sliders"></i></button>
                        <button class="btn btn-sm btn-outline-primary" title="Edit" onclick="openEdit(${item.item_id})"><i class="fa-solid fa-pen"></i></button>
                        <button class="btn btn-sm btn-danger" title="Delete" onclick="openDelete(${item.item_id},'${esc(item.item_name)}')"><i class="fa-solid fa-trash"></i></button>
                    </div>
                </td>
            </tr>`;
        });
        document.getElementById('inventoryBody').innerHTML = html;
    }

    // Pagination
    let pg = '';
    if (j.data.total_pages > 1) {
        if (page > 1) pg += `<button class="btn btn-sm btn-secondary" onclick="loadItems(${page-1})"><i class="fa-solid fa-chevron-left"></i></button>`;
        for (let p = Math.max(1,page-2); p <= Math.min(j.data.total_pages, page+2); p++) {
            pg += `<button class="btn btn-sm ${p===page?'btn-primary':'btn-secondary'}" onclick="loadItems(${p})">${p}</button>`;
        }
        if (page < j.data.total_pages) pg += `<button class="btn btn-sm btn-secondary" onclick="loadItems(${page+1})"><i class="fa-solid fa-chevron-right"></i></button>`;
    }
    document.getElementById('pagination').innerHTML = pg;
}



// -- Add Item --------------------------------------------------
document.getElementById('btnAddItem').addEventListener('click', () => {
    document.getElementById('itemModalTitle').innerHTML = '<i class="fa-solid fa-plus me-2 text-emerald"></i>Add New Item';
    document.getElementById('itemForm').reset();
    document.getElementById('fItemId').value = '';
    document.getElementById('itemFormError').classList.add('d-none');
    document.getElementById('barcodeLookupStatus').style.display = 'none';
    itemModal.show();
    // Auto-focus barcode field so user can start scanning immediately
    setTimeout(() => document.getElementById('fBarcode').focus(), 300);
});

// -- Edit Item -------------------------------------------------
async function openEdit(id) {
    const r = await fetch(`/sharonstore3.0/api/inventory.php?action=get&item_id=${id}`);
    const j = await r.json();
    if (!j.success) { showToast('Failed to load item.', 'error'); return; }
    const d = j.data;
    document.getElementById('itemModalTitle').innerHTML = '<i class="fa-solid fa-pen me-2 text-emerald"></i>Edit Item';
    document.getElementById('fItemId').value    = d.item_id;
    document.getElementById('fBarcode').value   = d.barcode   || '';
    document.getElementById('fItemName').value  = d.item_name;
    document.getElementById('fCategory').value  = d.category_id;
    document.getElementById('fUnit').value      = d.unit;
    document.getElementById('fPrice').value     = d.price;
    document.getElementById('fCostPrice').value = d.cost_price;
    document.getElementById('fStockQty').value  = d.stock_qty;
    document.getElementById('fThreshold').value = d.low_stock_threshold;
    document.getElementById('fExpiry').value    = d.expiry_date || '';
    document.getElementById('itemFormError').classList.add('d-none');
    itemModal.show();
}

// -- Submit Add/Edit -------------------------------------------
document.getElementById('itemForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const btn    = document.getElementById('itemFormSubmit');
    const itemId = document.getElementById('fItemId').value;
    const isEdit = !!itemId;

    const body = new FormData();
    body.append('action',              isEdit ? 'update' : 'add');
    if (isEdit) body.append('item_id', itemId);
    body.append('barcode',             document.getElementById('fBarcode').value.trim());
    body.append('item_name',           document.getElementById('fItemName').value.trim());
    body.append('category_id',         document.getElementById('fCategory').value);
    body.append('unit',                document.getElementById('fUnit').value);
    body.append('price',               document.getElementById('fPrice').value);
    body.append('cost_price',          document.getElementById('fCostPrice').value);
    body.append('stock_qty',           document.getElementById('fStockQty').value);
    body.append('low_stock_threshold', document.getElementById('fThreshold').value);
    body.append('expiry_date',         document.getElementById('fExpiry').value);

    btn.disabled = true; btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Saving';
    const r = await fetch('/sharonstore3.0/api/inventory.php', { method: 'POST', body });
    const j = await r.json();
    btn.disabled = false; btn.innerHTML = '<i class="fa-solid fa-save me-2"></i>Save Item';

    if (j.success) {
        itemModal.hide();
        showToast(j.message, 'success');
        loadItems(currentPage);
        loadStats();
    } else {
        const errEl = document.getElementById('itemFormError');
        errEl.textContent = j.message;
        errEl.classList.remove('d-none');
    }
});

// -- Adjust Stock ----------------------------------------------
function openAdjust(id, name) {
    document.getElementById('adjItemId').value = id;
    document.getElementById('adjustItemName').textContent = name;
    document.getElementById('adjQty').value = '';
    document.getElementById('adjReason').value = '';
    adjModal.show();
}

document.getElementById('btnAdjustConfirm').addEventListener('click', async () => {
    const body = new FormData();
    body.append('action',      'adjust_stock');
    body.append('item_id',     document.getElementById('adjItemId').value);
    body.append('adjust_type', document.getElementById('adjType').value);
    body.append('adjust_qty',  document.getElementById('adjQty').value);
    body.append('reason',      document.getElementById('adjReason').value);
    const r = await fetch('/sharonstore3.0/api/inventory.php', { method: 'POST', body });
    const j = await r.json();
    if (j.success) { adjModal.hide(); showToast(`Stock adjusted. New qty: ${j.data.new_qty}`, 'success'); loadItems(currentPage); loadStats(); }
    else showToast(j.message, 'error');
});

// -- Delete ----------------------------------------------------
function openDelete(id, name) {
    document.getElementById('deleteItemId').value = id;
    document.getElementById('deleteItemName').textContent = name;
    delModal.show();
}

document.getElementById('btnDeleteConfirm').addEventListener('click', async () => {
    const body = new FormData();
    body.append('action',  'delete');
    body.append('item_id', document.getElementById('deleteItemId').value);
    const r = await fetch('/sharonstore3.0/api/inventory.php', { method: 'POST', body });
    const j = await r.json();
    if (j.success) { delModal.hide(); showToast(j.message, 'success'); loadItems(currentPage); loadStats(); }
    else showToast(j.message, 'error');
});

// -- Barcode Auto-Lookup ----------------------------------------
// Looks up barcode via API and auto-fills form fields if found.
// Leaves selling price and expiry empty — user must set those manually.
async function lookupBarcode() {
    const bc = document.getElementById('fBarcode').value.trim();
    const statusEl = document.getElementById('barcodeLookupStatus');

    if (!bc) {
        statusEl.style.display = 'none';
        return;
    }

    // Only lookup when adding (not editing)
    const isEditing = !!document.getElementById('fItemId').value;
    if (isEditing) return;

    statusEl.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i>Looking up barcode...';
    statusEl.style.color = '#888888';
    statusEl.style.display = 'block';

    try {
        const r = await fetch('/sharonstore3.0/api/inventory.php?action=barcode_lookup&barcode=' + encodeURIComponent(bc));
        const j = await r.json();

        if (j.success && j.data) {
            const d = j.data;
            // Auto-fill fields from existing product (EXCEPT price and expiry)
            document.getElementById('fItemName').value  = d.item_name  || '';
            document.getElementById('fUnit').value      = d.unit       || 'pieces';
            document.getElementById('fCostPrice').value = d.cost_price || '';
            document.getElementById('fStockQty').value  = d.stock_qty  || '';
            document.getElementById('fThreshold').value = d.low_stock_threshold || '10';

            // Set category dropdown if the category exists
            if (d.category_id) {
                const catSelect = document.getElementById('fCategory');
                // Check if the option exists before setting
                const optExists = Array.from(catSelect.options).some(o => o.value == d.category_id);
                if (optExists) catSelect.value = d.category_id;
            }

            // Leave price and expiry empty — user fills these
            document.getElementById('fPrice').value  = '';
            document.getElementById('fExpiry').value = '';

            // Focus selling price so user can fill it immediately
            document.getElementById('fPrice').focus();

            statusEl.innerHTML = '<i class="fa-solid fa-check-circle me-1" style="color:#059669;"></i>'
                + '<span style="color:#059669;">Found: ' + escHtml(d.item_name) + '</span>'
                + ' <span style="color:#888;font-weight:400;">— set selling price & expiry</span>';
            statusEl.style.display = 'block';
        } else {
            // Barcode not in system — this is a brand new product
            statusEl.innerHTML = '<i class="fa-solid fa-circle-plus me-1" style="color:#2563eb;"></i>'
                + '<span style="color:#2563eb;">New barcode — fill in product details below</span>';
            statusEl.style.display = 'block';

            // Focus item name so user can start typing
            document.getElementById('fItemName').focus();
        }
    } catch (err) {
        statusEl.innerHTML = '<i class="fa-solid fa-triangle-exclamation me-1" style="color:#dc2626;"></i>'
            + '<span style="color:#dc2626;">Lookup failed — fill in details manually</span>';
        statusEl.style.display = 'block';
    }
}

// Escape HTML for safe display
function escHtml(str) {
    const d = document.createElement('div');
    d.textContent = str;
    return d.innerHTML;
}

// Trigger lookup on Enter key (USB barcode scanners send Enter after barcode)
document.getElementById('fBarcode').addEventListener('keydown', function(e) {
    if (e.key === 'Enter') {
        e.preventDefault();  // Prevent form submission
        lookupBarcode();
    }
});

// Scan button: focus the barcode field + trigger lookup if barcode already entered
document.getElementById('btnScanBarcode').addEventListener('click', () => {
    const bc = document.getElementById('fBarcode');
    bc.focus();
    if (bc.value.trim()) {
        lookupBarcode();
    } else {
        showToast('Scanner ready \u2014 scan your barcode.', 'info');
    }
});

// Clear the status when barcode field is cleared
document.getElementById('fBarcode').addEventListener('input', function() {
    if (!this.value.trim()) {
        document.getElementById('barcodeLookupStatus').style.display = 'none';
    }
});

// -- Search / filter -------------------------------------------
let searchTimer;
document.getElementById('searchInput').addEventListener('input', () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => loadItems(1), 400);
});
document.getElementById('categoryFilter').addEventListener('change', () => loadItems(1));
document.getElementById('statusFilter').addEventListener('change',   () => loadItems(1));
document.getElementById('btnReset').addEventListener('click', () => {
    document.getElementById('searchInput').value   = '';
    document.getElementById('categoryFilter').value = '';
    document.getElementById('statusFilter').value   = '';
    loadItems(1);
});

// -- Init ------------------------------------------------------
loadCategories();
loadStats();
loadItems(1);
bindNewCategory();

// -- Manage Categories Modal -----------------------------------
const manageCatsModal = new bootstrap.Modal('#manageCatsModal');
const deleteCatModal  = new bootstrap.Modal('#deleteCatModal');

// Load table whenever the Manage modal opens
document.getElementById('manageCatsModal').addEventListener('show.bs.modal', loadCatTable);

async function loadCatTable() {
    const tbody = document.getElementById('catTableBody');
    tbody.innerHTML = '<tr><td colspan="4" class="text-center py-4"><div class="loading-spinner mx-auto mb-2"></div>Loading</td></tr>';

    const r = await fetch('/sharonstore3.0/api/inventory.php?action=categories_with_counts');
    const j = await r.json();
    if (!j.success) { tbody.innerHTML = `<tr><td colspan="4" class="text-center text-danger py-3">${j.message}</td></tr>`; return; }

    if (!j.data.length) {
        tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted py-3">No categories found.</td></tr>';
        return;
    }

    tbody.innerHTML = j.data.map(c => {
        const total   = parseInt(c.total_items)   || 0;
        const stocked = parseInt(c.stocked_items) || 0;

        // Colour-code status pill
        let pill, rowClass = '';
        if (total === 0) {
            pill = `<span class="badge badge-emerald">Empty</span>`;
        } else if (stocked === 0) {
            pill = `<span class="badge badge-warning">No Stock</span>`;
            rowClass = 'style="opacity:.85"';
        } else {
            pill = `<span class="badge badge-danger">${stocked} stocked</span>`;
        }

        // Disable delete for "Uncategorized" safety guard (optional UX choice)
        const isUncategorized = c.category_name === 'Uncategorized';
        const delBtn = isUncategorized
            ? `<button class="btn btn-sm" style="opacity:.35;" disabled title="Cannot delete Uncategorized"><i class="fa-solid fa-trash"></i></button>`
            : `<button class="btn btn-sm btn-outline-danger" onclick="promptDeleteCat(${c.category_id},'${esc(c.category_name)}',${total},${stocked})" title="Delete"><i class="fa-solid fa-trash"></i></button>`;

        return `<tr ${rowClass}>
            <td><strong>${esc(c.category_name)}</strong></td>
            <td class="text-center">${total}</td>
            <td class="text-center">${pill}</td>
            <td class="text-end">${delBtn}</td>
        </tr>`;
    }).join('');
}

// -- Delete Category  smart 3-tier prompt ----------------------
async function promptDeleteCat(catId, catName, totalItems, stockedItems) {
    const body   = document.getElementById('deleteCatBody');
    const footer = document.getElementById('deleteCatFooter');
    document.getElementById('deleteCatTitle').innerHTML =
        `<i class="fa-solid fa-trash me-2 text-danger"></i>Delete "${catName}"`;

    // -- TIER 1: No items ? auto-delete immediately -------------
    if (totalItems === 0) {
        body.innerHTML = `
            <div class="text-center py-2">
                <i class="fa-solid fa-circle-check fa-3x text-success mb-3"></i>
                <p>This category has <strong>no products</strong> and can be safely deleted.</p>
            </div>`;
        footer.innerHTML = `
            <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button class="btn btn-danger" id="btnConfirmDelCat">
                <i class="fa-solid fa-trash me-1"></i>Delete Now
            </button>`;
        deleteCatModal.show();
        document.getElementById('btnConfirmDelCat').onclick = () => execDeleteCat(catId, 0);
        return;
    }

    // -- Fetch full item list for deeper detail -----------------
    body.innerHTML = `<div class="text-center py-3"><div class="loading-spinner mx-auto"></div></div>`;
    footer.innerHTML = `<button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>`;
    deleteCatModal.show();

    const r = await fetch(`/sharonstore3.0/api/inventory.php?action=check_category&category_id=${catId}`);
    const j = await r.json();
    if (!j.success) { body.innerHTML = `<div class="alert alert-danger">${j.message}</div>`; return; }

    const d = j.data;

    // -- TIER 2: Items exist but none have stock -----------------
    if (d.stocked_count === 0) {
        body.innerHTML = `
            <div class="alert alert-warning mb-3">
                <i class="fa-solid fa-triangle-exclamation me-2"></i>
                <strong>${d.unstocked_count} product(s)</strong> are in this category but have <strong>0 stock</strong>.
            </div>
            <p>They will be automatically moved to <strong>"Uncategorized"</strong> and this category will be removed.</p>
            <p class="text-muted" style="font-size:.85rem;">You can reassign them later from the inventory table.</p>`;
        footer.innerHTML = `
            <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button class="btn btn-warning text-dark fw-bold" id="btnConfirmDelCat">
                <i class="fa-solid fa-arrow-right-arrow-left me-1"></i>Move & Delete
            </button>`;
        document.getElementById('btnConfirmDelCat').onclick = () => execDeleteCat(catId, 0);
        return;
    }

    // -- TIER 3: Stocked items exist ? forced reassign ----------
    // Build reassign dropdown (all categories except this one)
    const otherCats = categories.filter(c => c.category_id != catId);
    const opts = otherCats.map(c => `<option value="${c.category_id}">${esc(c.category_name)}</option>`).join('');

    // Show stocked items list (max 5 shown, rest collapsed)
    const listItems = d.stocked_items.slice(0, 5).map(it =>
        `<li class="list-group-item list-group-item-sm d-flex justify-content-between py-1">
            <span>${esc(it.item_name)}</span>
            <span class="badge badge-danger">${it.stock_qty} in stock</span>
        </li>`
    ).join('');
    const moreNote = d.stocked_count > 5
        ? `<li class="list-group-item text-muted text-center py-1" style="font-size:.8rem;">and ${d.stocked_count - 5} more</li>`
        : '';

    body.innerHTML = `
        <div class="alert alert-danger mb-3">
            <i class="fa-solid fa-circle-exclamation me-2"></i>
            <strong>${d.stocked_count} product(s)</strong> in this category still have stock.
            You must reassign them before deleting.
        </div>
        <ul class="list-group list-group-flush mb-3 rounded" style="max-height:180px;overflow-y:auto;border:1px solid rgba(248,113,113,.3);">
            ${listItems}${moreNote}
        </ul>
        <label class="form-label fw-semibold">
            <i class="fa-solid fa-arrow-right-arrow-left me-1 text-emerald"></i>
            Reassign all ${d.total_items} product(s) to:
        </label>
        ${otherCats.length
            ? `<select id="reassignCatSelect" class="form-select">${opts}</select>
               <small class="text-muted mt-1 d-block">All products (stocked and 0-stock) will move to the selected category.</small>`
            : `<div class="alert alert-secondary">No other categories available. <a href="#" data-bs-dismiss="modal" onclick="setTimeout(()=>document.getElementById('btnAddItem').click(),300)">Create one first.</a></div>`
        }`;

    footer.innerHTML = `
        <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        ${otherCats.length ? `<button class="btn btn-danger fw-bold" id="btnConfirmDelCat">
            <i class="fa-solid fa-trash me-1"></i>Reassign & Delete
        </button>` : ''}`;

    if (otherCats.length) {
        document.getElementById('btnConfirmDelCat').onclick = () => {
            const sel = document.getElementById('reassignCatSelect');
            execDeleteCat(catId, parseInt(sel.value));
        };
    }
}
window.promptDeleteCat = promptDeleteCat;

// -- Execute deletion POST --------------------------------------
async function execDeleteCat(catId, reassignTo) {
    const btn = document.getElementById('btnConfirmDelCat');
    if (btn) { btn.disabled = true; btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Deleting'; }

    const fd = new FormData();
    fd.append('action', 'delete_category');
    fd.append('category_id', catId);
    if (reassignTo) fd.append('reassign_to', reassignTo);

    try {
        const r = await fetch('/sharonstore3.0/api/inventory.php', { method: 'POST', body: fd });
        const j = await r.json();

        if (j.success) {
            deleteCatModal.hide();

            // Remove from local categories array
            categories = categories.filter(c => c.category_id != catId);

            // Reload selects & table
            await loadCategories();
            loadCatTable();
            loadItems(currentPage);
            loadStats();

            let detail = '';
            if (j.data.items_moved > 0) detail = ` ${j.data.items_moved} product(s) were reassigned.`;
            showToast(j.message + detail, 'success');
        } else {
            showToast(j.message || 'Delete failed.', 'error');
            if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fa-solid fa-trash me-1"></i>Delete'; }
        }
    } catch (e) {
        showToast('Network error. Try again.', 'error');
        if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fa-solid fa-trash me-1"></i>Delete'; }
    }
}


</script>
</body>
</html>
