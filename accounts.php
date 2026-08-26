<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Accounts — Sharon Store</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css?v=3">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="/sharonstore3.0/assets/css/style.css?v=14">
    <?php
    require_once __DIR__ . '/includes/auth_check.php';
    requireAdmin();
    $pageTitle = 'Manage Accounts';
    $currentUserId = currentUserId();
    ?>
<style>
@media(max-width:767px){
  .page-header{flex-direction:column!important;align-items:flex-start!important;gap:10px!important;}
  .page-header .btn{width:100%;}
  .d-flex.gap-2,.d-flex.gap-3{flex-wrap:wrap!important;}
  .modal-dialog{margin:8px!important;max-width:calc(100vw - 16px)!important;}
  .modal-body .row > [class*='col-']{width:100%!important;}
}
@media(max-width:575px){
  .stat-value{font-size:1.15rem!important;}
  .card-body{padding:12px!important;}
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
                <ul class="breadcrumb-custom"><li><a href="/sharonstore3.0/dashboard.php">Home</a></li><li class="active">Accounts</li></ul>
                <h1 class="page-header-title"><i class="fa-solid fa-users-gear me-2 text-emerald"></i>Manage Accounts</h1>
                <p class="page-header-subtitle">Create and manage system user accounts for admin and cashier roles.</p>
            </div>
            <button class="btn btn-primary" id="btnAddUser"><i class="fa-solid fa-user-plus me-2"></i>Add Account</button>
        </div>

        <div class="card">
            <div class="card-header d-flex align-items-center"><h5 class="mb-0"><i class="fa-solid fa-users me-2 text-emerald"></i>User Accounts</h5>
                <div class="ms-auto">
                    <div class="input-group input-group-sm" style="max-width:220px;">
                        <span class="input-group-text"><i class="fa-solid fa-search"></i></span>
                        <input type="text" id="accountSearch" class="form-control" placeholder="Search name or username…">
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-custom mb-0">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Username</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="usersBody">
                            <tr><td colspan="6" class="text-center py-5"><div class="loading-spinner mx-auto mb-2"></div>Loading…</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add/Edit Modal -->
<div class="modal fade" id="userModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="userModalTitle"><i class="fa-solid fa-user-plus me-2 text-emerald"></i>Add Account</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="userForm" novalidate>
                <div class="modal-body">
                    <input type="hidden" id="fUserId">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Full Name <span class="text-danger">*</span></label>
                            <input type="text" id="fFullName" class="form-control" placeholder="e.g. Maria Santos" required>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label">Username <span class="text-danger">*</span></label>
                            <input type="text" id="fUsername" class="form-control" placeholder="e.g. cashier3" required autocomplete="off">
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label">Role <span class="text-danger">*</span></label>
                            <select id="fRole" class="form-select" required>
                                <option value="cashier">Cashier</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label" id="pwdLabel">Password <span class="text-danger">*</span></label>
                            <input type="password" id="fPassword" class="form-control" placeholder="Min. 6 characters" autocomplete="new-password">
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label">Confirm Password</label>
                            <input type="password" id="fConfirm" class="form-control" placeholder="Re-enter password" autocomplete="new-password">
                        </div>
                        <div class="col-12" id="activeToggleRow" style="display:none;">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="fIsActive" checked>
                                <label class="form-check-label" for="fIsActive">Account Active</label>
                            </div>
                        </div>
                    </div>
                    <div class="alert alert-danger mt-3 d-none" id="userFormError"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="userFormSubmit"><i class="fa-solid fa-save me-2"></i>Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Modal -->
<div class="modal fade" id="deleteUserModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-danger"><i class="fa-solid fa-trash me-2"></i>Delete Account</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Delete account for <strong id="delUserName"></strong>?</p>
                <p class="text-muted small mb-0">This cannot be undone.</p>
                <input type="hidden" id="delUserId">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="btnDelConfirm">Delete</button>
            </div>
        </div>
    </div>
</div>

<div class="toast-container position-fixed bottom-0 end-0 p-3" id="toastContainer"></div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
<script src="/sharonstore3.0/assets/js/app.js"></script>
<script>
const CURRENT_USER_ID = <?= $currentUserId ?>;
const userModal  = new bootstrap.Modal('#userModal');
const delModal   = new bootstrap.Modal('#deleteUserModal');

async function loadUsers() {
    const r = await fetch('/sharonstore3.0/api/accounts.php?action=list');
    const j = await r.json();
    if (!j.success) { showToast('Failed to load users.', 'error'); return; }

    let html = '';
    j.data.forEach(u => {
        const isMe   = parseInt(u.user_id) === CURRENT_USER_ID;
        const initials = u.full_name.split(' ').map(w=>w[0]?.toUpperCase()||'').join('').substring(0,2);
        const roleBadge = u.role === 'admin'
            ? '<span class="badge badge-emerald"><i class="fa-solid fa-crown me-1"></i>Admin</span>'
            : '<span class="badge badge-secondary"><i class="fa-solid fa-cash-register me-1"></i>Cashier</span>';
        const statusBadge = u.is_active
            ? '<span class="badge badge-success">Active</span>'
            : '<span class="badge badge-danger">Inactive</span>';
        html += `<tr>
            <td>
                <div class="d-flex align-items-center gap-3">
                    <div class="ss-avatar" style="width:36px;height:36px;font-size:0.85rem;">${initials}</div>
                    <span class="fw-600">${esc(u.full_name)}${isMe?' <span class="badge badge-blue ms-1">You</span>':''}</span>
                </div>
            </td>
            <td><code>${esc(u.username)}</code></td>
            <td>${roleBadge}</td>
            <td>${statusBadge}</td>
            <td class="text-muted small">${u.created_at.substring(0,10)}</td>
            <td>
                <div class="d-flex gap-1">
                    <button class="btn btn-sm btn-outline-primary" onclick="openEdit(${u.user_id})"><i class="fa-solid fa-pen"></i></button>
                    ${!isMe ? `<button class="btn btn-sm btn-secondary" onclick="toggleActive(${u.user_id})" title="${u.is_active?'Deactivate':'Activate'}">
                        <i class="fa-solid ${u.is_active?'fa-user-slash':'fa-user-check'}"></i></button>
                    <button class="btn btn-sm btn-danger" onclick="openDelete(${u.user_id},'${esc(u.full_name)}')"><i class="fa-solid fa-trash"></i></button>` : ''}
                </div>
            </td>
        </tr>`;
    });
    document.getElementById('usersBody').innerHTML = html || '<tr><td colspan="6" class="text-center py-4 text-muted">No users found.</td></tr>';
}

document.getElementById('btnAddUser').addEventListener('click', () => {
    document.getElementById('userModalTitle').innerHTML = '<i class="fa-solid fa-user-plus me-2 text-emerald"></i>Add Account';
    document.getElementById('userForm').reset();
    document.getElementById('fUserId').value = '';
    document.getElementById('pwdLabel').innerHTML = 'Password <span class="text-danger">*</span>';
    document.getElementById('activeToggleRow').style.display = 'none';
    document.getElementById('userFormError').classList.add('d-none');
    userModal.show();
});

async function openEdit(id) {
    const r = await fetch(`/sharonstore3.0/api/accounts.php?action=get&user_id=${id}`);
    const j = await r.json();
    if (!j.success) { showToast('Failed to load user.','error'); return; }
    const u = j.data;
    document.getElementById('userModalTitle').innerHTML = '<i class="fa-solid fa-pen me-2 text-emerald"></i>Edit Account';
    document.getElementById('fUserId').value     = u.user_id;
    document.getElementById('fFullName').value   = u.full_name;
    document.getElementById('fUsername').value   = u.username;
    document.getElementById('fRole').value       = u.role;
    document.getElementById('fPassword').value   = '';
    document.getElementById('fConfirm').value    = '';
    document.getElementById('fIsActive').checked = !!parseInt(u.is_active);
    document.getElementById('pwdLabel').innerHTML = 'New Password <small class="text-muted">(leave blank to keep)</small>';
    document.getElementById('activeToggleRow').style.display = '';
    document.getElementById('userFormError').classList.add('d-none');
    userModal.show();
}

document.getElementById('userForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const btn    = document.getElementById('userFormSubmit');
    const userId = document.getElementById('fUserId').value;
    const isEdit = !!userId;
    const body   = new FormData();
    body.append('action',           isEdit ? 'update' : 'create');
    if (isEdit) body.append('user_id', userId);
    body.append('full_name',        document.getElementById('fFullName').value);
    body.append('username',         document.getElementById('fUsername').value);
    body.append('role',             document.getElementById('fRole').value);
    body.append('new_password',     document.getElementById('fPassword').value);
    body.append('password',         document.getElementById('fPassword').value);
    body.append('confirm_password', document.getElementById('fConfirm').value);
    if (isEdit) body.append('is_active', document.getElementById('fIsActive').checked ? '1' : '0');

    btn.disabled = true; btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Saving…';
    const r = await fetch('/sharonstore3.0/api/accounts.php', {method:'POST', body});
    const j = await r.json();
    btn.disabled = false; btn.innerHTML = '<i class="fa-solid fa-save me-2"></i>Save';
    if (j.success) { userModal.hide(); showToast(j.message,'success'); loadUsers(); }
    else { const e=document.getElementById('userFormError'); e.textContent=j.message; e.classList.remove('d-none'); }
});

async function toggleActive(id) {
    const body = new FormData(); body.append('action','toggle_active'); body.append('user_id',id);
    const r = await fetch('/sharonstore3.0/api/accounts.php',{method:'POST',body});
    const j = await r.json();
    if (j.success) { showToast('Account status updated.','success'); loadUsers(); }
    else showToast(j.message,'error');
}

function openDelete(id, name) {
    document.getElementById('delUserId').value    = id;
    document.getElementById('delUserName').textContent = name;
    delModal.show();
}

document.getElementById('btnDelConfirm').addEventListener('click', async () => {
    const body = new FormData(); body.append('action','delete'); body.append('user_id',document.getElementById('delUserId').value);
    const r = await fetch('/sharonstore3.0/api/accounts.php',{method:'POST',body});
    const j = await r.json();
    if (j.success) { delModal.hide(); showToast(j.message,'success'); loadUsers(); }
    else showToast(j.message,'error');
});


document.getElementById('accountSearch').addEventListener('input', function() {
    const q = this.value.trim().toLowerCase();
    document.querySelectorAll('#usersBody tr').forEach(tr => {
        tr.style.display = q === '' || tr.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
});

loadUsers();
</script>
</body>
</html>
