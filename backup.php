<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Backup - Sharon Store</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css?v=3">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="/sharonstore3.0/assets/css/style.css?v=18">
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
.code-box {
    background: #0f172a;
    color: #38bdf8;
    padding: 12px;
    border-radius: 8px;
    font-family: 'Roboto Mono', monospace;
    font-size: 0.76rem;
    max-height: 180px;
    overflow-y: auto;
    position: relative;
}
.copy-badge-btn {
    position: absolute;
    top: 8px;
    right: 8px;
    font-size: 0.7rem;
    padding: 3px 8px;
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
                <ul class="breadcrumb-custom"><li><a href="/sharonstore3.0/dashboard.php">Home</a></li><li class="active">Database Backup</li></ul>
                <h1 class="page-header-title"><i class="fa-solid fa-database me-2 text-emerald"></i>Database Backup</h1>
                <p class="page-header-subtitle">Create, download, and sync backups of the Sharon Store database locally and to Google Drive.</p>
            </div>
        </div>

        <div class="row g-4">
            <!-- Left Column: Actions & Cloud Card -->
            <div class="col-12 col-lg-5 d-flex flex-column gap-4">
                
                <!-- Google Drive Cloud Card -->
                <div class="card shadow-sm border-0" style="border-radius: 14px; overflow: hidden;">
                    <div class="card-header d-flex justify-content-between align-items-center bg-white py-3 border-bottom">
                        <div class="d-flex align-items-center gap-2">
                            <i class="fa-brands fa-google-drive fa-lg" style="color: #4285F4;"></i>
                            <h5 class="mb-0 fw-bold" style="font-size: 0.95rem; color: #1e293b;">Google Drive Cloud Backup</h5>
                        </div>
                        <span id="gdriveStatusBadge" class="badge bg-secondary" style="font-size: 0.72rem;">Checking...</span>
                    </div>
                    <div class="card-body p-4">
                        <p class="text-muted small mb-3">
                            Directly upload database snapshots to your Google Drive account under the <strong style="color:#334155;">SharonStore_Backups</strong> folder for automatic off-site disaster recovery.
                        </p>

                        <div class="d-flex flex-column gap-2">
                            <button class="btn btn-primary w-100 fw-bold py-2 d-flex align-items-center justify-content-center gap-2" id="btnUploadGdrive" style="border-radius: 9px;">
                                <i class="fa-solid fa-cloud-arrow-up"></i>
                                <span>Upload Backup to Google Drive</span>
                            </button>
                            <button class="btn btn-outline-secondary w-100 fw-bold py-2 d-flex align-items-center justify-content-center gap-2" id="btnConfigGdrive" style="border-radius: 9px;">
                                <i class="fa-solid fa-gear"></i>
                                <span>Configure Google Drive Integration</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Local Download Card -->
                <div class="card shadow-sm border-0" style="border-radius: 14px; overflow: hidden;">
                    <div class="card-header bg-white py-3 border-bottom">
                        <h5 class="mb-0 fw-bold" style="font-size: 0.95rem; color: #1e293b;">
                            <i class="fa-solid fa-hard-drive me-2 text-emerald"></i>Local PC Backup
                        </h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="alert-custom mb-3" style="background:rgba(16,185,129,0.08); border:1px solid rgba(16,185,129,0.2); border-radius:10px; padding:12px;">
                            <ul class="text-muted small mb-0 ps-3">
                                <li>Full SQL export of all tables (Inventory, Sales, Users, Audits)</li>
                                <li>Downloads directly to your computer as a <code>.sql</code> file</li>
                                <li><strong>Recommended:</strong> Backup daily before closing store</li>
                            </ul>
                        </div>

                        <button class="btn btn-outline-primary w-100 fw-bold py-2" id="btnBackup" style="border-radius: 9px;">
                            <i class="fa-solid fa-download me-2"></i>Download Backup to PC
                        </button>
                    </div>
                </div>

            </div>

            <!-- Right Column: Backup Files List -->
            <div class="col-12 col-lg-7">
                <div class="card shadow-sm border-0" style="border-radius: 14px; overflow: hidden;">
                    <div class="card-header d-flex justify-content-between align-items-center bg-white py-3 border-bottom">
                        <h5 class="mb-0 fw-bold" style="font-size: 0.95rem; color: #1e293b;">
                            <i class="fa-solid fa-folder-open me-2 text-emerald"></i>Server Backup History
                        </h5>
                        <button class="btn btn-sm btn-secondary" onclick="loadBackups()"><i class="fa-solid fa-rotate-right me-1"></i>Refresh</button>
                    </div>
                    <div class="card-body p-0" id="backupList">
                        <div class="chart-loading p-5 text-center"><div class="loading-spinner mx-auto mb-2"></div>Loading backups...</div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- ══ GOOGLE DRIVE CONFIGURATION MODAL ═══════════════════════ -->
<div class="modal fade" id="gdriveConfigModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px; overflow: hidden;">
            <div class="modal-header py-3 px-4 bg-light border-bottom">
                <h5 class="modal-title fw-bold" style="font-size: 1.1rem; color: #1e293b;">
                    <i class="fa-brands fa-google-drive me-2" style="color: #4285F4;"></i>Google Drive Setup &amp; Webhook
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-4">
                    <h6 class="fw-bold mb-2 text-dark"><i class="fa-solid fa-circle-question text-primary me-2"></i>How to connect your personal Google Drive (3 steps):</h6>
                    <ol class="small text-muted ps-3 mb-0" style="line-height: 1.6;">
                        <li>Open <a href="https://script.google.com/home/start" target="_blank" class="fw-bold text-decoration-none">script.google.com</a> and click <strong>"New project"</strong>.</li>
                        <li>Delete any template code and paste the script below:</li>
                    </ol>
                </div>

                <div class="code-box mb-3">
                    <button class="btn btn-light btn-sm copy-badge-btn" id="btnCopyScript" onclick="copyAppsScript()">
                        <i class="fa-regular fa-copy me-1"></i>Copy Script
                    </button>
                    <pre class="mb-0" id="appsScriptCode"><code>function doGet(e) {
  return ContentService.createTextOutput(JSON.stringify({
    status: "success",
    message: "Sharon Store Google Apps Script Webhook is active and reachable!"
  })).setMimeType(ContentService.MimeType.JSON);
}

function doPost(e) {
  try {
    var data = JSON.parse(e.postData.contents);
    var filename = data.filename || ("sharonstore_backup_" + Utilities.formatDate(new Date(), "GMT+8", "yyyy-MM-dd_HH-mm-ss") + ".sql");
    var folderName = "SharonStore_Backups";
    var folders = DriveApp.getFoldersByName(folderName);
    var targetFolder = folders.hasNext() ? folders.next() : DriveApp.createFolder(folderName);
    var blob = Utilities.newBlob(Utilities.base64Decode(data.base64), "application/sql", filename);
    var file = targetFolder.createFile(blob);
    return ContentService.createTextOutput(JSON.stringify({
      status: "success",
      message: "Backup uploaded to Google Drive successfully!",
      fileId: file.getId(),
      fileUrl: file.getUrl(),
      fileName: filename
    })).setMimeType(ContentService.MimeType.JSON);
  } catch (err) {
    return ContentService.createTextOutput(JSON.stringify({
      status: "error",
      message: err.toString()
    })).setMimeType(ContentService.MimeType.JSON);
  }
}</code></pre>
                </div>

                <div class="alert alert-warning py-2 px-3 small mb-3 border-0" style="background:#fffbeb; color:#92400e; border-left:4px solid #f59e0b !important; border-radius: 8px;">
                    <strong><i class="fa-solid fa-triangle-exclamation me-1"></i>Crucial Step to Avoid Error 403:</strong><br>
                    When deploying in Google Apps Script, you <strong>MUST</strong> set <strong>"Who has access"</strong> to <strong>"Anyone"</strong> (do not leave as "Only myself"). Ensure your Web App URL ends with <code>/exec</code> (not <code>/dev</code>).
                </div>

                <div class="mb-4">
                    <ol start="3" class="small text-muted ps-3 mb-0" style="line-height: 1.6;">
                        <li>Click <strong>Deploy &gt; New deployment &gt; Select type: Web app</strong>:
                            <ul class="ps-3 mt-1">
                                <li>Description: <em>Sharon Store Backup</em></li>
                                <li>Execute as: <strong>Me (your google account)</strong></li>
                                <li>Who has access: <strong class="text-danger" style="background:rgba(239,68,68,0.1); padding:1px 6px; border-radius:4px;">Anyone</strong> (Required!)</li>
                            </ul>
                        </li>
                        <li>Click <strong>Deploy</strong>, authorize access, copy the <strong>Web app URL</strong>, and paste it below:</li>
                    </ol>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold small text-dark">Google Apps Script Web App URL <span class="text-danger">*</span></label>
                    <input type="url" id="gdriveWebhookInput" class="form-control" placeholder="https://script.google.com/macros/s/AKfycbx.../exec" required>
                    <div id="urlWarning" class="d-none text-danger small mt-1 fw-bold"><i class="fa-solid fa-triangle-exclamation me-1"></i>Your URL ends with <code>/dev</code> (Test deployment). Please use the Web App URL that ends with <code>/exec</code> instead!</div>
                    <div class="form-text small">Must start with <code>https://script.google.com/macros/s/</code> and end with <code>/exec</code></div>
                </div>

                <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox" id="gdriveAutoBackup">
                    <label class="form-check-label small fw-bold text-dark" for="gdriveAutoBackup">
                        Automatically upload a copy to Google Drive whenever a backup is created
                    </label>
                </div>

                <div id="testConnResult" class="d-none alert py-2 small mb-0"></div>
            </div>
            <div class="modal-footer py-2 px-4 bg-light border-top d-flex justify-content-between">
                <button type="button" class="btn btn-outline-info btn-sm fw-bold" id="btnTestGdrive">
                    <i class="fa-solid fa-vial me-1"></i>Test Connection
                </button>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary btn-sm fw-bold px-3" id="btnSaveGdrive">
                        <i class="fa-solid fa-save me-1"></i>Save Settings
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ══ DELETE BACKUP CONFIRMATION MODAL ═══════════════════════ -->
<div class="modal fade" id="deleteBackupModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 400px;">
        <div class="modal-content border-0 shadow text-center" style="border-radius: 16px; overflow: hidden; background: #ffffff;">
            <div class="modal-body p-4">
                <div class="mx-auto mb-3 d-flex align-items-center justify-content-center"
                     style="width: 60px; height: 60px; border-radius: 50%; background: #fee2e2; color: #ef4444; font-size: 24px;">
                    <i class="fa-solid fa-trash"></i>
                </div>
                <h5 class="fw-bold mb-1" style="color: #1e293b;">Delete Backup File?</h5>
                <p class="text-muted small mb-3">Are you sure you want to delete this backup file? This action cannot be undone.</p>
                <div class="p-2 mb-3 bg-light rounded text-truncate" id="deleteFilenamePreview" style="font-family: monospace; font-size: 0.8rem; color: #334155;"></div>
                <div class="d-flex gap-2 justify-content-center">
                    <button type="button" class="btn btn-secondary flex-fill fw-bold py-2" data-bs-dismiss="modal" style="border-radius: 8px;">Cancel</button>
                    <button type="button" class="btn btn-danger flex-fill fw-bold py-2" id="btnConfirmDelete" style="border-radius: 8px;">Delete</button>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="toast-container position-fixed bottom-0 end-0 p-3" id="toastContainer"></div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
<script src="/sharonstore3.0/assets/js/app.js?v=25"></script>
<script>
let deleteTargetFilename = null;
const configModal = new bootstrap.Modal(document.getElementById('gdriveConfigModal'));
const deleteModal = new bootstrap.Modal(document.getElementById('deleteBackupModal'));

// ── Load Backups ──────────────────────────────────────────────
async function loadBackups() {
    const container = document.getElementById('backupList');
    try {
        const r = await fetch('/sharonstore3.0/api/backup.php?action=list');
        const j = await r.json();
        if (!j.success || !j.data.length) {
            container.innerHTML = '<div class="empty-state p-5 text-center"><i class="fa-solid fa-box-open fa-2x text-muted mb-3"></i><p class="text-muted">No backups yet. Click "Download Backup to PC" or "Upload to Google Drive" to create your first backup.</p></div>';
            return;
        }
        let html = '<div class="table-responsive"><table class="table table-custom mb-0"><thead><tr><th>Filename</th><th>Size</th><th>Created</th><th style="width:130px;">Actions</th></tr></thead><tbody>';
        j.data.forEach(f => {
            html += `<tr>
                <td><i class="fa-solid fa-file-code me-2 text-emerald"></i><span style="font-family:monospace; font-size:0.8rem;">${esc(f.filename)}</span></td>
                <td class="text-muted small">${f.size_fmt}</td>
                <td class="text-muted small">${f.created}</td>
                <td>
                    <div class="d-flex gap-1">
                        <a href="/sharonstore3.0/api/backup.php?action=download&file=${encodeURIComponent(f.filename)}" class="btn btn-sm btn-outline-primary" title="Download to PC">
                            <i class="fa-solid fa-download"></i>
                        </a>
                        <button class="btn btn-sm btn-outline-info" onclick="uploadSpecificToGdrive('${esc(f.filename)}')" title="Upload this backup to Google Drive">
                            <i class="fa-brands fa-google-drive"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-danger" onclick="openDeleteModal('${esc(f.filename)}')" title="Delete">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>`;
        });
        html += '</tbody></table></div>';
        container.innerHTML = html;
    } catch (e) {
        container.innerHTML = '<div class="p-4 text-center text-danger">Failed to load backup list.</div>';
    }
}

// ── Google Drive Settings Status ──────────────────────────────
async function checkGdriveStatus() {
    const badge = document.getElementById('gdriveStatusBadge');
    try {
        const r = await fetch('/sharonstore3.0/api/backup.php?action=get_settings');
        const j = await r.json();
        if (j.success && j.data.webhook_url) {
            badge.className = 'badge bg-success';
            badge.innerHTML = '<i class="fa-solid fa-check me-1"></i>Connected';
            document.getElementById('gdriveWebhookInput').value = j.data.webhook_url;
            document.getElementById('gdriveAutoBackup').checked = j.data.auto_backup === '1';
        } else {
            badge.className = 'badge bg-secondary';
            badge.innerHTML = '<i class="fa-solid fa-circle-exclamation me-1"></i>Not Configured';
        }
    } catch (e) {
        badge.className = 'badge bg-secondary';
        badge.textContent = 'Offline';
    }
}

// ── Open Config Modal ─────────────────────────────────────────
document.getElementById('btnConfigGdrive').addEventListener('click', () => {
    document.getElementById('testConnResult').classList.add('d-none');
    configModal.show();
});

// ── Save Google Drive Settings ────────────────────────────────
document.getElementById('btnSaveGdrive').addEventListener('click', async function () {
    const webhook = document.getElementById('gdriveWebhookInput').value.trim();
    const auto    = document.getElementById('gdriveAutoBackup').checked ? '1' : '0';

    if (webhook && !webhook.startsWith('https://script.google.com/')) {
        showToast('Webhook URL must start with https://script.google.com/', 'error');
        return;
    }

    const fd = new FormData();
    fd.append('webhook_url', webhook);
    fd.append('auto_backup', auto);

    this.disabled = true;
    try {
        const r = await fetch('/sharonstore3.0/api/backup.php?action=save_settings', { method: 'POST', body: fd });
        const j = await r.json();
        if (j.success) {
            showToast(j.message, 'success');
            configModal.hide();
            checkGdriveStatus();
        } else {
            showToast(j.message, 'error');
        }
    } catch (e) {
        showToast('Error saving settings.', 'error');
    }
    this.disabled = false;
});

// Live URL Warning for /dev
document.getElementById('gdriveWebhookInput').addEventListener('input', function() {
    const val = this.value.trim();
    const warn = document.getElementById('urlWarning');
    if (val.endsWith('/dev')) {
        warn.classList.remove('d-none');
    } else {
        warn.classList.add('d-none');
    }
});

// ── Test Connection ───────────────────────────────────────────
document.getElementById('btnTestGdrive').addEventListener('click', async function () {
    const webhook = document.getElementById('gdriveWebhookInput').value.trim();
    const resBox = document.getElementById('testConnResult');
    if (!webhook) {
        resBox.className = 'alert alert-danger py-2 small mb-0';
        resBox.textContent = 'Please enter a Webhook URL first.';
        resBox.classList.remove('d-none');
        return;
    }
    if (webhook.endsWith('/dev')) {
        resBox.className = 'alert alert-danger py-2 small mb-0';
        resBox.innerHTML = '<i class="fa-solid fa-triangle-exclamation me-1"></i>URL ends with <code>/dev</code> (Test deployment). Please copy the Web App URL that ends with <code>/exec</code> from <strong>Deploy &gt; New deployment</strong>.';
        resBox.classList.remove('d-none');
        return;
    }

    this.disabled = true;
    this.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Testing...';
    resBox.className = 'alert alert-info py-2 small mb-0';
    resBox.textContent = 'Pinging Google Drive endpoint...';
    resBox.classList.remove('d-none');

    const fd = new FormData();
    fd.append('webhook_url', webhook);

    try {
        const r = await fetch('/sharonstore3.0/api/backup.php?action=test_gdrive', { method: 'POST', body: fd });
        const j = await r.json();
        if (j.success) {
            resBox.className = 'alert alert-success py-2 small mb-0';
            resBox.innerHTML = '<i class="fa-solid fa-circle-check me-1"></i>Connection successful! Google Drive folder <strong>SharonStore_Backups</strong> is ready.';
        } else {
            resBox.className = 'alert alert-danger py-2 small mb-0';
            resBox.textContent = j.message || 'Connection test failed.';
        }
    } catch (e) {
        resBox.className = 'alert alert-danger py-2 small mb-0';
        resBox.textContent = 'Network error testing Google connection.';
    }
    this.disabled = false;
    this.innerHTML = '<i class="fa-solid fa-vial me-1"></i>Test Connection';
});

// ── Upload Backup to Google Drive ─────────────────────────────
document.getElementById('btnUploadGdrive').addEventListener('click', async function () {
    this.disabled = true;
    this.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Uploading to Google Drive...';
    showToast('Creating backup and sending to Google Drive...', 'info');

    try {
        const r = await fetch('/sharonstore3.0/api/backup.php?action=upload_gdrive', { method: 'POST' });
        const j = await r.json();
        if (j.success) {
            showToast('Backup successfully uploaded to Google Drive!', 'success');
            loadBackups();
        } else {
            showToast(j.message, 'error');
            if (j.message.includes('not configured')) configModal.show();
        }
    } catch (e) {
        showToast('Failed to connect to server.', 'error');
    }
    this.disabled = false;
    this.innerHTML = '<i class="fa-solid fa-cloud-arrow-up me-2"></i>Upload Backup to Google Drive';
});

// ── Upload Specific File from History ─────────────────────────
async function uploadSpecificToGdrive(filename) {
    showToast(`Uploading ${filename} to Google Drive...`, 'info');
    const fd = new FormData();
    fd.append('filename', filename);

    try {
        const r = await fetch('/sharonstore3.0/api/backup.php?action=upload_gdrive', { method: 'POST', body: fd });
        const j = await r.json();
        if (j.success) {
            showToast(`Uploaded ${filename} to Google Drive!`, 'success');
        } else {
            showToast(j.message, 'error');
            if (j.message.includes('not configured')) configModal.show();
        }
    } catch (e) {
        showToast('Error uploading file to Google Drive.', 'error');
    }
}

// ── Local Download Backup ─────────────────────────────────────
document.getElementById('btnBackup').addEventListener('click', function () {
    this.disabled = true;
    this.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Exporting SQL...';
    showToast('Generating database backup...', 'info');
    window.location.href = '/sharonstore3.0/api/backup.php?action=download';
    setTimeout(() => {
        this.disabled = false;
        this.innerHTML = '<i class="fa-solid fa-download me-2"></i>Download Backup to PC';
        showToast('Backup downloaded successfully!', 'success');
        loadBackups();
    }, 2500);
});

// ── Delete Backup via Modal ───────────────────────────────────
function openDeleteModal(filename) {
    deleteTargetFilename = filename;
    document.getElementById('deleteFilenamePreview').textContent = filename;
    deleteModal.show();
}

document.getElementById('btnConfirmDelete').addEventListener('click', async function () {
    if (!deleteTargetFilename) return;
    this.disabled = true;
    this.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Deleting...';

    const body = new FormData();
    body.append('action', 'delete');
    body.append('filename', deleteTargetFilename);

    try {
        const r = await fetch('/sharonstore3.0/api/backup.php', { method: 'POST', body });
        const j = await r.json();
        if (j.success) {
            showToast(j.message, 'success');
            deleteModal.hide();
            loadBackups();
        } else {
            showToast(j.message, 'error');
        }
    } catch (e) {
        showToast('Error deleting backup.', 'error');
    }
    this.disabled = false;
    this.innerHTML = 'Delete';
});

// ── Copy Script Helper ────────────────────────────────────────
function copyAppsScript() {
    const code = document.getElementById('appsScriptCode').textContent;
    navigator.clipboard.writeText(code).then(() => {
        const btn = document.getElementById('btnCopyScript');
        btn.innerHTML = '<i class="fa-solid fa-check text-success me-1"></i>Copied!';
        setTimeout(() => { btn.innerHTML = '<i class="fa-regular fa-copy me-1"></i>Copy Script'; }, 2000);
    });
}

// Initial Loads
loadBackups();
checkGdriveStatus();
</script>
</body>
</html>
