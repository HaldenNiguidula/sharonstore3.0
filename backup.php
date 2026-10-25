<?php
require_once __DIR__ . '/includes/auth_check.php';
requireAdmin();
$pageTitle = 'Database Backup';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Backup - Sharon Store</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css?v=3">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="/sharonstore3.0/assets/css/style.css?v=42">
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
.step-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 18px 20px;
    margin-bottom: 16px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.03);
}
.step-header {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 10px;
}
.step-badge {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    background: #047857;
    color: #ffffff;
    font-weight: 800;
    font-size: 0.85rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.step-title {
    font-size: 0.95rem;
    font-weight: 700;
    color: #0f172a;
    margin: 0;
}
.step-card code {
    background: #e2e8f0 !important;
    color: #0f172a !important;
    font-weight: 600;
    padding: 2px 7px;
    border-radius: 4px;
    font-size: 0.82em;
}
.code-editor-box {
    background: #0f172a;
    border-radius: 10px;
    border: 1px solid #334155;
    overflow: hidden;
    margin-bottom: 12px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.12);
}
.code-editor-header {
    background: #1e293b;
    padding: 8px 14px;
    border-bottom: 1px solid #334155;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.code-editor-dots {
    display: flex;
    align-items: center;
    gap: 6px;
}
.code-dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    display: inline-block;
}
.code-dot.red { background: #ef4444; }
.code-dot.yellow { background: #f59e0b; }
.code-dot.green { background: #10b981; }
.code-editor-filename {
    color: #94a3b8;
    font-size: 0.78rem;
    font-family: 'Consolas', 'Courier New', monospace;
    font-weight: 600;
    margin-left: 6px;
}
.code-editor-body {
    padding: 14px 16px;
    max-height: 250px;
    overflow-y: auto;
    background: #0f172a;
}
.code-editor-body pre,
.code-editor-body code,
.code-box pre,
.code-box code {
    background: transparent !important;
    background-color: transparent !important;
    color: #f8fafc !important;
    padding: 0 !important;
    border: none !important;
    border-radius: 0 !important;
    font-family: 'Consolas', 'Courier New', 'Fira Code', monospace !important;
    font-size: 0.84rem !important;
    line-height: 1.6 !important;
    box-shadow: none !important;
    display: block !important;
    white-space: pre !important;
}
.code-kw { color: #f472b6 !important; font-weight: 600; }
.code-fn { color: #60a5fa !important; font-weight: 600; }
.code-str { color: #34d399 !important; }
.code-cm { color: #94a3b8 !important; font-style: italic; }
.code-obj { color: #38bdf8 !important; }
.code-num { color: #fbbf24 !important; }
.deploy-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    border-radius: 8px;
    overflow: hidden;
    border: 1px solid #cbd5e1;
    margin-top: 10px;
}
.deploy-table td {
    padding: 8px 12px;
    font-size: 0.85rem;
    border-bottom: 1px solid #e2e8f0;
}
.deploy-table tr:last-child td {
    border-bottom: none;
}
.deploy-table td:first-child {
    font-weight: 700;
    color: #334155;
    width: 150px;
    background: #f8fafc;
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

                <!-- Import & Restore Card -->
                <div class="card shadow-sm border-0" style="border-radius: 14px; overflow: hidden;">
                    <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center gap-2">
                            <i class="fa-solid fa-clock-rotate-left fa-lg" style="color: #f59e0b;"></i>
                            <h5 class="mb-0 fw-bold" style="font-size: 0.95rem; color: #1e293b;">Import &amp; Restore Database</h5>
                        </div>
                        <span class="badge bg-warning text-dark fw-bold" style="font-size: 0.68rem;">Admin Only</span>
                    </div>
                    <div class="card-body p-4">
                        <p class="text-muted small mb-3">
                            Restore database tables from an external <code>.sql</code> backup file (downloaded from Google Drive or copied from a flash drive).
                        </p>

                        <form id="uploadRestoreForm" onsubmit="event.preventDefault(); handleUploadRestorePrompt();">
                            <div class="mb-3">
                                <label class="form-label small fw-bold text-dark">Select .sql Backup File</label>
                                <input type="file" id="importSqlFile" accept=".sql" class="form-control form-control-sm" required style="border-radius: 8px;">
                            </div>
                            <button type="submit" class="btn btn-outline-warning w-100 fw-bold py-2 d-flex align-items-center justify-content-center gap-2" id="btnUploadRestore" style="border-radius: 9px; color: #b45309; border-color: #f59e0b;">
                                <i class="fa-solid fa-file-import"></i>
                                <span>Upload &amp; Restore Backup</span>
                            </button>
                        </form>
                    </div>
                </div>

            </div>

            <!-- Right Column: Backup Files List -->
            <div class="col-12 col-lg-7">
                <div class="card shadow-sm border-0" style="border-radius: 14px; overflow: hidden;">
                    <div class="card-header d-flex justify-content-between align-items-center bg-white py-3 border-bottom">
                        <h5 class="mb-0 fw-bold d-flex align-items-center gap-2" style="font-size: 0.95rem; color: #1e293b;">
                            <i class="fa-solid fa-folder-open text-emerald"></i>
                            <span>Server Backup History</span>
                            <span class="badge bg-light text-muted border fw-normal" style="font-size: 0.7rem;">Latest 5 Backups</span>
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
                <div class="d-flex align-items-center gap-2">
                    <div style="width: 36px; height: 36px; border-radius: 8px; background: rgba(66, 133, 244, 0.12); display: flex; align-items: center; justify-content: center;">
                        <i class="fa-brands fa-google-drive fs-5" style="color: #4285F4;"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold mb-0" style="font-size: 1.1rem; color: #1e293b;">Google Drive Setup &amp; Webhook</h5>
                        <small class="text-muted">Connect your personal Google Drive for automated off-site cloud backups</small>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4" style="max-height: calc(85vh - 120px); overflow-y: auto;">
                
                <!-- STEP 1 -->
                <div class="step-card">
                    <div class="step-header">
                        <span class="step-badge">1</span>
                        <h6 class="step-title">Create a New Google Apps Script Project</h6>
                    </div>
                    <p class="text-muted small mb-2 ps-4" style="line-height: 1.6;">
                        Sign in to your Google Account and create a blank Apps Script project.
                    </p>
                    <div class="ps-4">
                        <a href="https://script.google.com/home/start" target="_blank" class="btn btn-outline-primary btn-sm fw-bold">
                            <i class="fa-solid fa-arrow-up-right-from-square me-1"></i>Open Google Apps Script (script.google.com)
                        </a>
                        <span class="small text-muted ms-2">&rarr; Click <strong>"New project"</strong></span>
                    </div>
                </div>

                <!-- STEP 2 -->
                <div class="step-card">
                    <div class="step-header">
                        <span class="step-badge">2</span>
                        <h6 class="step-title">Paste the Backup Automation Script</h6>
                    </div>
                    <p class="text-muted small mb-2 ps-4" style="line-height: 1.6;">
                        Erase any existing code in the editor (e.g. <code>function myFunction() {...}</code>) and replace it with the script below:
                    </p>
                    <div class="ps-4">
                        <div class="code-editor-box">
                            <div class="code-editor-header">
                                <div class="code-editor-dots">
                                    <span class="code-dot red"></span>
                                    <span class="code-dot yellow"></span>
                                    <span class="code-dot green"></span>
                                    <span class="code-editor-filename"><i class="fa-brands fa-google me-1 text-primary"></i>Code.gs</span>
                                </div>
                                <button type="button" class="btn btn-sm btn-light py-1 px-3 fw-bold shadow-sm" id="btnCopyScript" onclick="copyAppsScript()">
                                    <i class="fa-regular fa-copy me-1"></i>Copy Script
                                </button>
                            </div>
                            <div class="code-editor-body">
                                <pre class="mb-0" id="appsScriptCode"><code><span class="code-kw">function</span> <span class="code-fn">doGet</span>(e) {
  <span class="code-kw">return</span> <span class="code-obj">ContentService</span>.<span class="code-fn">createTextOutput</span>(<span class="code-obj">JSON</span>.<span class="code-fn">stringify</span>({
    status: <span class="code-str">"success"</span>,
    message: <span class="code-str">"Sharon Store Google Apps Script Webhook is active and reachable!"</span>
  })).<span class="code-fn">setMimeType</span>(<span class="code-obj">ContentService</span>.<span class="code-obj">MimeType</span>.<span class="code-obj">JSON</span>);
}

<span class="code-kw">function</span> <span class="code-fn">doPost</span>(e) {
  <span class="code-kw">try</span> {
    <span class="code-kw">var</span> data = <span class="code-obj">JSON</span>.<span class="code-fn">parse</span>(e.postData.contents);
    <span class="code-kw">var</span> filename = data.filename || (<span class="code-str">"sharonstore_backup_"</span> + <span class="code-obj">Utilities</span>.<span class="code-fn">formatDate</span>(<span class="code-kw">new</span> <span class="code-obj">Date</span>(), <span class="code-str">"GMT+8"</span>, <span class="code-str">"yyyy-MM-dd_HH-mm-ss"</span>) + <span class="code-str">".sql"</span>);
    <span class="code-kw">var</span> folderName = <span class="code-str">"SharonStore_Backups"</span>;
    <span class="code-kw">var</span> folders = <span class="code-obj">DriveApp</span>.<span class="code-fn">getFoldersByName</span>(folderName);
    <span class="code-kw">var</span> targetFolder = folders.<span class="code-fn">hasNext</span>() ? folders.<span class="code-fn">next</span>() : <span class="code-obj">DriveApp</span>.<span class="code-fn">createFolder</span>(folderName);
    <span class="code-kw">var</span> blob = <span class="code-obj">Utilities</span>.<span class="code-fn">newBlob</span>(<span class="code-obj">Utilities</span>.<span class="code-fn">base64Decode</span>(data.base64), <span class="code-str">"application/sql"</span>, filename);
    <span class="code-kw">var</span> file = targetFolder.<span class="code-fn">createFile</span>(blob);

    <span class="code-cm">// Auto-prune: Keep only the 5 most recent backups in Google Drive</span>
    <span class="code-kw">var</span> maxBackups = data.max_files || <span class="code-num">5</span>;
    <span class="code-kw">var</span> backupFiles = [];
    <span class="code-kw">var</span> files = targetFolder.<span class="code-fn">getFiles</span>();
    <span class="code-kw">while</span> (files.<span class="code-fn">hasNext</span>()) {
      <span class="code-kw">var</span> f = files.<span class="code-fn">next</span>();
      <span class="code-kw">if</span> (f.<span class="code-fn">getName</span>().<span class="code-fn">indexOf</span>(<span class="code-str">'.sql'</span>) !== -<span class="code-num">1</span>) {
        backupFiles.<span class="code-fn">push</span>({
          file: f,
          created: f.<span class="code-fn">getDateCreated</span>().<span class="code-fn">getTime</span>()
        });
      }
    }

    <span class="code-cm">// Sort newest to oldest</span>
    backupFiles.<span class="code-fn">sort</span>(<span class="code-kw">function</span>(a, b) {
      <span class="code-kw">return</span> b.created - a.created;
    });

    <span class="code-cm">// Move older backups beyond 5 to Google Drive Trash</span>
    <span class="code-kw">var</span> prunedCount = <span class="code-num">0</span>;
    <span class="code-kw">for</span> (<span class="code-kw">var</span> i = maxBackups; i &lt; backupFiles.length; i++) {
      backupFiles[i].file.<span class="code-fn">setTrashed</span>(<span class="code-kw">true</span>);
      prunedCount++;
    }

    <span class="code-kw">return</span> <span class="code-obj">ContentService</span>.<span class="code-fn">createTextOutput</span>(<span class="code-obj">JSON</span>.<span class="code-fn">stringify</span>({
      status: <span class="code-str">"success"</span>,
      message: <span class="code-str">"Backup uploaded to Google Drive!"</span> + (prunedCount &gt; <span class="code-num">0</span> ? <span class="code-str">" (Auto-cleaned "</span> + prunedCount + <span class="code-str">" older backup"</span> + (prunedCount &gt; <span class="code-num">1</span> ? <span class="code-str">"s"</span> : <span class="code-str">""</span>) + <span class="code-str">", keeping latest 5)"</span> : <span class="code-str">""</span>),
      fileId: file.<span class="code-fn">getId</span>(),
      fileUrl: file.<span class="code-fn">getUrl</span>(),
      fileName: filename,
      pruned: prunedCount
    })).<span class="code-fn">setMimeType</span>(<span class="code-obj">ContentService</span>.<span class="code-obj">MimeType</span>.<span class="code-obj">JSON</span>);
  } <span class="code-kw">catch</span> (err) {
    <span class="code-kw">return</span> <span class="code-obj">ContentService</span>.<span class="code-fn">createTextOutput</span>(<span class="code-obj">JSON</span>.<span class="code-fn">stringify</span>({
      status: <span class="code-str">"error"</span>,
      message: err.<span class="code-fn">toString</span>()
    })).<span class="code-fn">setMimeType</span>(<span class="code-obj">ContentService</span>.<span class="code-obj">MimeType</span>.<span class="code-obj">JSON</span>);
  }
}</code></pre>
                            </div>
                        </div>
                        <div class="alert alert-success py-2 px-3 small mb-0 border-0 d-flex align-items-center gap-2" style="background:#ecfdf5; color:#065f46; border-left:4px solid #10b981 !important; border-radius: 8px;">
                            <i class="fa-solid fa-clock-rotate-left fs-6"></i>
                            <div><strong>Automatic 5-File Retention:</strong> Automatically saves files into a <code>SharonStore_Backups</code> folder and keeps the latest 5 backups to conserve Google Drive space.</div>
                        </div>
                    </div>
                </div>

                <!-- STEP 3 -->
                <div class="step-card">
                    <div class="step-header">
                        <span class="step-badge">3</span>
                        <h6 class="step-title">Deploy as a Web App (Important Settings)</h6>
                    </div>
                    <p class="text-muted small mb-2 ps-4" style="line-height: 1.6;">
                        In the Google Apps Script top toolbar, click <strong>Deploy</strong> &gt; <strong>New deployment</strong>, click the gear icon &gt; select <strong>Web app</strong>, and configure with these exact values:
                    </p>
                    <div class="ps-4">
                        <table class="deploy-table">
                            <tr>
                                <td>Description</td>
                                <td><code>Sharon Store Backup</code></td>
                            </tr>
                            <tr>
                                <td>Execute as</td>
                                <td><strong>Me (your google account email)</strong></td>
                            </tr>
                            <tr>
                                <td>Who has access</td>
                                <td>
                                    <span class="badge bg-danger text-white px-2 py-1 fw-bold fs-7">Anyone</span>
                                    <span class="text-danger small ms-2 fw-semibold"><i class="fa-solid fa-triangle-exclamation me-1"></i>Required! Setting to "Only myself" causes 403 Forbidden errors.</span>
                                </td>
                            </tr>
                        </table>
                        <div class="text-muted small mt-2">
                            Click <strong>Deploy</strong>, grant permissions (click <em>Advanced &gt; Go to Sharon Store Backup (unsafe)</em> if prompted), and copy the resulting <strong>Web app URL</strong>.
                        </div>
                    </div>
                </div>

                <!-- STEP 4 -->
                <div class="step-card mb-2">
                    <div class="step-header">
                        <span class="step-badge">4</span>
                        <h6 class="step-title">Paste Web App URL &amp; Connect</h6>
                    </div>
                    <div class="ps-4">
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-dark">Google Apps Script Web App URL <span class="text-danger">*</span></label>
                            <input type="url" id="gdriveWebhookInput" class="form-control" placeholder="https://script.google.com/macros/s/AKfycbx.../exec" required>
                            <div id="urlWarning" class="d-none text-danger small mt-1 fw-bold">
                                <i class="fa-solid fa-triangle-exclamation me-1"></i>Warning: Your URL ends with <code>/dev</code> (Test deployment). Please use the production Web App URL that ends with <code>/exec</code> instead!
                            </div>
                            <div class="form-text small text-muted">The URL must start with <code>https://script.google.com/macros/s/</code> and end with <code>/exec</code></div>
                        </div>

                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" id="gdriveAutoBackup">
                            <label class="form-check-label small fw-bold text-dark" for="gdriveAutoBackup">
                                Automatically upload a copy to Google Drive whenever a backup is created
                            </label>
                        </div>

                        <div id="testConnResult" class="d-none alert py-2 small mb-0"></div>
                    </div>
                </div>

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

<!-- ══ RESTORE BACKUP CONFIRMATION MODAL ═══════════════════════ -->
<div class="modal fade" id="restoreBackupModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 440px;">
        <div class="modal-content border-0 shadow-lg text-center" style="border-radius: 16px; overflow: hidden; background: #ffffff;">
            <div class="modal-body p-4 pt-4">
                <div class="mx-auto mb-3 d-flex align-items-center justify-content-center"
                     style="width: 68px; height: 68px; border-radius: 50%; background: #fef3c7; color: #d97706; font-size: 28px; box-shadow: 0 4px 14px rgba(217,119,6,0.18);">
                    <i class="fa-solid fa-rotate-left"></i>
                </div>
                <h5 class="fw-bold mb-1" style="color: #1e293b; font-size: 1.25rem;">Restore Database?</h5>
                <p class="text-muted small mb-3" style="line-height: 1.5;">
                    Restoring will overwrite current tables and return the database to this backup snapshot.
                </p>

                <div class="p-2 mb-3 bg-light rounded text-truncate text-start d-flex align-items-center justify-content-between" style="border: 1px solid #e2e8f0;">
                    <div class="text-truncate me-2" style="font-family: monospace; font-size: 0.8rem; color: #334155;">
                        <i class="fa-solid fa-file-code me-1 text-warning"></i><span id="restoreTargetFilename"></span>
                    </div>
                    <span class="badge bg-secondary" id="restoreSourceBadge" style="font-size: 0.68rem;"></span>
                </div>

                <div class="alert alert-info py-2 px-3 small text-start mb-3 border-0" style="background:#eff6ff; color:#1e40af; border-left:4px solid #3b82f6 !important; border-radius: 8px;">
                    <i class="fa-solid fa-shield-halved me-1"></i>
                    <strong>Automatic Safety Snapshot:</strong> A full backup of your current database will be automatically created before restoring, so you never lose any data.
                </div>

                <div id="restoreStatusAlert" class="d-none alert py-2 small mb-3"></div>

                <div class="d-flex gap-2 justify-content-center">
                    <button type="button" class="btn btn-secondary flex-fill fw-bold py-2" data-bs-dismiss="modal" id="btnCancelRestore" style="border-radius: 8px;">Cancel</button>
                    <button type="button" class="btn btn-warning flex-fill fw-bold py-2" id="btnConfirmRestore" style="border-radius: 8px; color: #78350f;">
                        <i class="fa-solid fa-rotate-left me-1"></i>Restore Now
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
<script src="/sharonstore3.0/assets/js/app.js?v=35"></script>
<script>
let deleteTargetFilename = null;
let restoreTargetFilename = null;
let restoreTargetSource = 'history';
let restoreUploadFile = null;

const configModal  = new bootstrap.Modal(document.getElementById('gdriveConfigModal'));
const deleteModal  = new bootstrap.Modal(document.getElementById('deleteBackupModal'));
const restoreModal = new bootstrap.Modal(document.getElementById('restoreBackupModal'));

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
        let html = '<div class="table-responsive"><table class="table table-custom mb-0"><thead><tr><th class="sortable-th">Filename <i class="fa-solid fa-sort sort-icon no-print"></i></th><th class="sortable-th">Size <i class="fa-solid fa-sort sort-icon no-print"></i></th><th class="sortable-th">Created <i class="fa-solid fa-sort sort-icon no-print"></i></th><th class="no-sort" style="width:160px;">Actions</th></tr></thead><tbody>';
        j.data.forEach(f => {
            html += `<tr>
                <td data-sort-value="${esc(f.filename)}"><i class="fa-solid fa-file-code me-2 text-emerald"></i><span style="font-family:monospace; font-size:0.8rem;">${esc(f.filename)}</span></td>
                <td class="text-muted small" data-sort-value="${f.size || 0}">${f.size_fmt}</td>
                <td class="text-muted small" data-sort-value="${f.created || ''}">${f.created}</td>
                <td>
                    <div class="d-flex gap-1">
                        <a href="/sharonstore3.0/api/backup.php?action=download&file=${encodeURIComponent(f.filename)}" class="btn btn-sm btn-outline-primary" title="Download to PC">
                            <i class="fa-solid fa-download"></i>
                        </a>
                        <button class="btn btn-sm btn-outline-info" onclick="uploadSpecificToGdrive('${esc(f.filename)}')" title="Upload this backup to Google Drive">
                            <i class="fa-brands fa-google-drive"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-warning" onclick="openRestoreModal('${esc(f.filename)}', 'history')" title="Restore Database to this snapshot">
                            <i class="fa-solid fa-rotate-left"></i>
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

// ── Restore Backup Handlers ───────────────────────────────────
function openRestoreModal(filename, source = 'history', fileObj = null) {
    restoreTargetFilename = filename;
    restoreTargetSource = source;
    restoreUploadFile = fileObj;

    document.getElementById('restoreTargetFilename').textContent = filename;
    document.getElementById('restoreStatusAlert').classList.add('d-none');
    document.getElementById('restoreSourceBadge').textContent = source === 'upload' ? 'Local Upload' : 'Server Snapshot';

    const btn = document.getElementById('btnConfirmRestore');
    btn.disabled = false;
    btn.innerHTML = '<i class="fa-solid fa-rotate-left me-1"></i>Restore Now';

    restoreModal.show();
}

function handleUploadRestorePrompt() {
    const fileInput = document.getElementById('importSqlFile');
    if (!fileInput.files || !fileInput.files[0]) {
        showToast('Please select a .sql backup file.', 'warning');
        return;
    }
    const file = fileInput.files[0];
    if (!file.name.toLowerCase().endsWith('.sql')) {
        showToast('Only .sql files can be restored.', 'error');
        return;
    }
    openRestoreModal(file.name, 'upload', file);
}

document.getElementById('btnConfirmRestore').addEventListener('click', async function () {
    this.disabled = true;
    this.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Restoring Database...';

    const statusBox = document.getElementById('restoreStatusAlert');
    statusBox.className = 'alert alert-info py-2 small mb-3';
    statusBox.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Creating automatic safety snapshot &amp; importing tables... Please wait.';
    statusBox.classList.remove('d-none');

    const fd = new FormData();
    fd.append('action', 'restore');
    fd.append('source', restoreTargetSource);

    if (restoreTargetSource === 'upload') {
        fd.append('backup_file', restoreUploadFile);
    } else {
        fd.append('filename', restoreTargetFilename);
    }

    try {
        const r = await fetch('/sharonstore3.0/api/backup.php', { method: 'POST', body: fd });
        const j = await r.json();

        if (j.success) {
            statusBox.className = 'alert alert-success py-2 small mb-3';
            statusBox.innerHTML = `<i class="fa-solid fa-circle-check me-1"></i><strong>${esc(j.message)}</strong><br><span style="font-size:0.75rem;">Automatic safety snapshot created: <code>${esc(j.safety_backup)}</code></span>`;
            showToast('Database successfully restored!', 'success');

            const fileInput = document.getElementById('importSqlFile');
            if (fileInput) fileInput.value = '';

            setTimeout(() => {
                restoreModal.hide();
                loadBackups();
            }, 2500);
        } else {
            statusBox.className = 'alert alert-danger py-2 small mb-3';
            statusBox.innerHTML = `<i class="fa-solid fa-triangle-exclamation me-1"></i>${esc(j.message)}`;
            showToast(j.message || 'Restoration failed.', 'error');
            this.disabled = false;
            this.innerHTML = '<i class="fa-solid fa-rotate-left me-1"></i>Retry Restore';
        }
    } catch (e) {
        statusBox.className = 'alert alert-danger py-2 small mb-3';
        statusBox.textContent = 'Server or network error during restoration.';
        showToast('Restoration network error.', 'error');
        this.disabled = false;
        this.innerHTML = '<i class="fa-solid fa-rotate-left me-1"></i>Retry Restore';
    }
});

// ── Copy Script Helper ────────────────────────────────────────
function copyAppsScript() {
    const code = document.getElementById('appsScriptCode').textContent;
    const btn = document.getElementById('btnCopyScript');
    const onCopied = () => {
        if (btn) btn.innerHTML = '<i class="fa-solid fa-check text-success me-1"></i>Copied!';
        if (typeof showToast === 'function') showToast('Apps Script code copied to clipboard!', 'success');
        setTimeout(() => { if (btn) btn.innerHTML = '<i class="fa-regular fa-copy me-1"></i>Copy Script'; }, 2000);
    };

    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(code).then(onCopied).catch(() => {
            fallbackCopy(code, onCopied);
        });
    } else {
        fallbackCopy(code, onCopied);
    }
}

function fallbackCopy(text, cb) {
    const ta = document.createElement('textarea');
    ta.value = text;
    ta.style.position = 'fixed';
    ta.style.opacity = '0';
    document.body.appendChild(ta);
    ta.select();
    try {
        document.execCommand('copy');
        cb();
    } catch (e) {
        if (typeof showToast === 'function') showToast('Please select and copy the code manually.', 'warning');
    }
    document.body.removeChild(ta);
}

// Initial Loads
loadBackups();
checkGdriveStatus();
</script>
</body>
</html>
