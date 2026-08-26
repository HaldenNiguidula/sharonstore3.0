<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Backup — Sharon Store</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css?v=3">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="/sharonstore3.0/assets/css/style.css?v=16">
    <?php
    require_once __DIR__ . '/includes/auth_check.php';
    requireAdmin();
    $pageTitle = 'Database Backup';
    ?>
<style>
@media(max-width:767px){
  .page-header{flex-direction:column!important;align-items:flex-start!important;gap:10px!important;}
  .page-header .btn{width:100%;}
  .row > .col-md-6,.row > .col-md-4,.row > .col-md-8{width:100%!important;}
  .d-flex.gap-2,.d-flex.gap-3{flex-wrap:wrap!important;}
  .card-body{padding:14px!important;}
}
@media(max-width:575px){
  .card-header h5{font-size:0.88rem!important;}
}
</style>
</head>
<body>
<div class="app-wrapper">
    <?php require_once __DIR__ . '/includes/sidebar.php'; ?>
    <div class="main-content">
        <?php require_once __DIR__ . '/includes/header.php'; ?>

        <div class="page-header">
            <ul class="breadcrumb-custom"><li><a href="/sharonstore3.0/dashboard.php">Home</a></li><li class="active">Database Backup</li></ul>
            <h1 class="page-header-title"><i class="fa-solid fa-database me-2 text-emerald"></i>Database Backup</h1>
            <p class="page-header-subtitle">Create and manage backups of the Sharon Store database to protect your business data.</p>
        </div>

        <div class="row g-4">
            <!-- Info + Backup Button -->
            <div class="col-12 col-lg-5">
                <div class="card">
                    <div class="card-header"><h5><i class="fa-solid fa-shield-halved me-2 text-emerald"></i>Create Backup</h5></div>
                    <div class="card-body">
                        <div class="alert-custom mb-4" style="background:rgba(16,185,129,0.08); border:1px solid rgba(16,185,129,0.2); border-radius:12px; padding:16px;">
                            <h6 style="color:#10b981;"><i class="fa-solid fa-circle-info me-2"></i>About Database Backup</h6>
                            <ul class="text-muted small mb-0" style="padding-left:1.2rem;">
                                <li>Creates a full SQL dump of all tables and data</li>
                                <li>Includes inventory, transactions, users, and audit logs</li>
                                <li>File is downloaded directly to your computer</li>
                                <li><strong>Recommended:</strong> Backup daily before closing</li>
                            </ul>
                        </div>

                        <div class="alert-custom mb-4" style="background:rgba(245,158,11,0.08); border:1px solid rgba(245,158,11,0.2); border-radius:12px; padding:16px;">
                            <h6 style="color:#f59e0b;"><i class="fa-solid fa-triangle-exclamation me-2"></i>Recommendation</h6>
                            <p class="text-muted small mb-0">Store backup files in a secure external drive or cloud storage. Local backups may be lost if the computer fails.</p>
                        </div>

                        <button class="btn btn-primary w-100" id="btnBackup" style="height:56px; font-size:1rem;">
                            <i class="fa-solid fa-download me-2"></i>Download Backup Now
                        </button>
                        <div class="text-muted small text-center mt-2">Backup will be downloaded as a .sql file</div>
                    </div>
                </div>
            </div>

            <!-- Backup Files List -->
            <div class="col-12 col-lg-7">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5><i class="fa-solid fa-folder-open me-2 text-emerald"></i>Backup History</h5>
                        <button class="btn btn-sm btn-secondary" onclick="loadBackups()"><i class="fa-solid fa-rotate-right me-1"></i>Refresh</button>
                    </div>
                    <div class="card-body p-0" id="backupList">
                        <div class="chart-loading p-4"><div class="loading-spinner"></div></div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
<div class="toast-container position-fixed bottom-0 end-0 p-3" id="toastContainer"></div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
<script src="/sharonstore3.0/assets/js/app.js"></script>
<script>
async function loadBackups() {
    const container = document.getElementById('backupList');
    const r = await fetch('/sharonstore3.0/api/backup.php?action=list');
    const j = await r.json();
    if (!j.success || !j.data.length) {
        container.innerHTML = '<div class="empty-state p-5 text-center"><i class="fa-solid fa-box-open fa-2x text-muted mb-3"></i><p class="text-muted">No backups yet. Click "Download Backup Now" to create your first backup.</p></div>';
        return;
    }
    let html = '<div class="table-responsive"><table class="table table-custom mb-0"><thead><tr><th>Filename</th><th>Size</th><th>Created</th><th>Actions</th></tr></thead><tbody>';
    j.data.forEach(f => {
        html += `<tr>
            <td><i class="fa-solid fa-file-code me-2 text-emerald"></i><span style="font-family:monospace; font-size:0.8rem;">${esc(f.filename)}</span></td>
            <td class="text-muted">${f.size_fmt}</td>
            <td class="text-muted small">${f.created}</td>
            <td>
                <div class="d-flex gap-1">
                    <a href="/sharonstore3.0/api/backup.php?action=download&file=${encodeURIComponent(f.filename)}" class="btn btn-sm btn-outline-primary" title="Download">
                        <i class="fa-solid fa-download"></i>
                    </a>
                    <button class="btn btn-sm btn-danger" onclick="deleteBackup('${esc(f.filename)}')" title="Delete">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                </div>
            </td>
        </tr>`;
    });
    html += '</tbody></table></div>';
    container.innerHTML = html;
}

document.getElementById('btnBackup').addEventListener('click', async function() {
    this.disabled = true;
    this.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Creating backup…';
    showToast('Generating backup, please wait…', 'info');
    window.location.href = '/sharonstore3.0/api/backup.php?action=download';
    setTimeout(() => {
        this.disabled = false;
        this.innerHTML = '<i class="fa-solid fa-download me-2"></i>Download Backup Now';
        showToast('Backup downloaded successfully!', 'success');
        loadBackups();
    }, 3000);
});

async function deleteBackup(filename) {
    if (!confirm(`Delete backup: ${filename}?`)) return;
    const body = new FormData();
    body.append('action', 'delete');
    body.append('filename', filename);
    const r = await fetch('/sharonstore3.0/api/backup.php', {method:'POST', body});
    const j = await r.json();
    if (j.success) { showToast(j.message, 'success'); loadBackups(); }
    else showToast(j.message, 'error');
}


loadBackups();
</script>
</body>
</html>
