<?php
// ============================================================
// Header/Topbar — Sharon Store System
// ============================================================
require_once __DIR__ . '/auth_check.php';
requireLogin();

$__fullName = currentUserName();
$__role     = currentRole();
$__initials = implode('', array_map(
    fn($w) => strtoupper($w[0]),
    array_filter(explode(' ', $__fullName))
));
$__initials = substr($__initials, 0, 2);
$pageTitle  = $pageTitle ?? 'Sharon Store';

$__hasOpenShift = true;
if ($__role === 'cashier') {
    require_once __DIR__ . '/db.php';
    try {
        $__db = getDB();
        $__stmt = $__db->prepare("SELECT shift_id FROM tbl_shifts WHERE cashier_id = ? AND status = 'open' LIMIT 1");
        $__stmt->execute([currentUserId()]);
        $__hasOpenShift = (bool)$__stmt->fetch();
    } catch (\Throwable $e) {
        $__hasOpenShift = false;
    }
}
?>
<!-- ══ TOPBAR ══════════════════════════════════════════════ -->
<header class="ss-topbar" id="appTopbar">

    <?php if (empty($hideHamburger)): ?>
    <!-- Hamburger (mobile only: hidden when sidebar is visible on desktop / >=768px) -->
    <button class="btn ss-hamburger d-md-none me-2 p-0" id="sidebarToggleBtn"
            type="button"
            style="width:36px;height:36px;min-width:36px;border-radius:8px;"
            aria-label="Toggle menu">
        <i class="fa-solid fa-bars" style="font-size:16px;"></i>
    </button>
    <?php endif; ?>

    <!-- Page Title -->
    <div class="ss-page-title">
        <span class="ss-title-text"><?= htmlspecialchars($pageTitle) ?></span>
    </div>

    <div class="ms-auto d-flex align-items-center gap-3">

        <!-- Live Clock -->
        <div class="d-none d-md-flex flex-column align-items-end lh-sm">
            <span class="ss-time-display" id="liveTime">--:--:-- --</span>
            <span class="ss-date-display" id="liveDate">Loading…</span>
        </div>

        <div class="ss-vr d-none d-md-block"></div>

        <?php if (isset($pageTitle) && in_array($pageTitle, ['Point of Sale', 'Stock Monitoring'])): ?>
        <!-- Quick Live summary Shortcut (Active Shift) -->
        <button type="button" class="btn btn-sm btn-outline-primary d-flex align-items-center gap-1 py-1 px-2"
                id="topbarLiveSummaryBtn" onclick="if(typeof viewShiftSummary === 'function') viewShiftSummary();" title="View Live summary (Active Shift)"
                style="border-radius:8px;font-size:0.8rem;font-weight:700;">
            <i class="fa-solid fa-list-check"></i>
            <span class="d-none d-sm-inline">Live summary</span>
            <span class="d-inline d-sm-none">Live summary</span>
        </button>
        <?php endif; ?>

        <!-- User Dropdown -->
        <div class="dropdown" id="userProfileDropdownContainer">
            <button class="btn ss-user-btn dropdown-toggle d-flex align-items-center gap-2 border-0"
                    type="button"
                    id="userProfileBtn"
                    <?= ($__role === 'cashier' && !$__hasOpenShift) ? 'disabled style="pointer-events:none;opacity:0.55;cursor:not-allowed;" title="Shift not started — Manager authorization required"' : 'data-bs-toggle="dropdown" aria-expanded="false"' ?>>
                <div class="ss-avatar" style="width:30px;height:30px;font-size:0.72rem;flex-shrink:0;">
                    <?= htmlspecialchars($__initials) ?>
                </div>
                <div class="d-none d-md-flex flex-column align-items-start lh-sm">
                    <span class="ss-user-name"><?= htmlspecialchars($__fullName) ?></span>
                    <span class="badge bg-<?= $__role === 'admin' ? 'emerald' : 'secondary' ?> ss-role-badge">
                        <?= ucfirst(htmlspecialchars($__role)) ?>
                    </span>
                </div>
            </button>
            <ul class="dropdown-menu dropdown-menu-end ss-dropdown-menu shadow-lg" id="userProfileDropdownMenu" style="background:#ffffff; border:1px solid #cbd5e1; min-width:210px; border-radius:12px; padding:8px;">
                <li class="px-3 py-2">
                    <div class="d-flex align-items-center gap-2">
                        <div class="ss-avatar" style="width:36px;height:36px;">
                            <?= htmlspecialchars($__initials) ?>
                        </div>
                        <div class="lh-sm">
                            <div style="font-size:0.88rem;font-weight:700;color:#1e293b;">
                                <?= htmlspecialchars($__fullName) ?>
                            </div>
                            <div style="font-size:0.75rem;font-weight:600;color:#64748b;">
                                <?= ucfirst(htmlspecialchars($__role)) ?> Account
                            </div>
                        </div>
                    </div>
                </li>
                <li><hr class="dropdown-divider ss-divider my-2"></li>
                <?php if ($__role === 'admin'): ?>
                <li>
                    <a href="/sharonstore3.0/dashboard.php" class="dropdown-item ss-dropdown-item py-2">
                        <i class="fa-solid fa-chart-pie me-2" style="color:#0abf8a;width:16px;"></i>Dashboard
                    </a>
                </li>
                <li>
                    <a href="/sharonstore3.0/inventory.php" class="dropdown-item ss-dropdown-item py-2">
                        <i class="fa-solid fa-boxes-stacked me-2" style="color:#3b82f6;width:16px;"></i>Inventory
                    </a>
                </li>
                <?php else: ?>
                <li>
                    <a href="/sharonstore3.0/pos.php" class="dropdown-item ss-dropdown-item py-2">
                        <i class="fa-solid fa-cash-register me-2" style="color:#0abf8a;width:16px;"></i>Point of Sale
                    </a>
                </li>
                <li>
                    <a href="/sharonstore3.0/stock-monitor.php" class="dropdown-item ss-dropdown-item py-2">
                        <i class="fa-solid fa-magnifying-glass-chart me-2" style="color:#0284c7;width:16px;"></i>Stock Monitoring
                    </a>
                </li>
                <?php endif; ?>
                <?php if (isset($pageTitle) && in_array($pageTitle, ['Point of Sale', 'Stock Monitoring'])): ?>
                <li id="headerLiveSummaryItem">
                    <a href="#" class="dropdown-item ss-dropdown-item py-2" onclick="if(typeof viewShiftSummary === 'function') viewShiftSummary(); return false;">
                        <i class="fa-solid fa-list-check me-2" style="color:#0abf8a;width:16px;"></i>Live summary
                    </a>
                </li>
                <?php if ($__role === 'cashier'): ?>
                <li id="headerEndShiftDivider" style="<?= !$__hasOpenShift ? 'display:none;' : '' ?>">
                    <hr class="dropdown-divider ss-divider my-2">
                </li>
                <li id="headerEndShiftItem" style="<?= !$__hasOpenShift ? 'display:none;' : '' ?>">
                    <a href="#" class="dropdown-item ss-dropdown-item py-2" onclick="if(typeof openEndShiftModal==='function'){openEndShiftModal();}else if(window.shiftModals&&window.shiftModals.end){window.shiftModals.end.show();}return false;">
                        <i class="fa-solid fa-lock me-2" style="color:#ef4444;width:16px;"></i>End Shift
                    </a>
                </li>
                <?php endif; ?>
                <?php endif; ?>
                <li><hr class="dropdown-divider ss-divider my-2"></li>
                <li>
                    <button type="button" class="dropdown-item ss-dropdown-item ss-logout-item text-danger border-0 bg-transparent w-100 text-start py-2"
                            data-bs-toggle="modal" data-bs-target="#logoutModal">
                        <i class="fa-solid fa-right-from-bracket me-2" style="color:#ef4444;width:16px;"></i>Sign Out
                    </button>
                </li>
            </ul>
        </div>

        <!-- Logout Button (visible on smaller screens) -->
        <button type="button" class="ss-btn-logout d-none d-sm-flex border-0"
                data-bs-toggle="modal" data-bs-target="#logoutModal" title="Sign Out">
            <i class="fa-solid fa-right-from-bracket"></i>
            <span class="d-none d-lg-inline">Sign Out</span>
        </button>

    </div>
</header>

<!-- ══ LOGOUT CONFIRMATION MODAL ═══════════════════════════ -->
<div class="modal fade" id="logoutModal" tabindex="-1" aria-labelledby="logoutModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 390px;">
        <div class="modal-content border-0 shadow-lg text-center" style="border-radius: 16px; overflow: hidden; background: #ffffff;">
            <div class="modal-body p-4 pt-4">
                <div class="mx-auto mb-3 d-flex align-items-center justify-content-center"
                     style="width: 64px; height: 64px; border-radius: 50%; background: #fee2e2; color: #ef4444; font-size: 26px;">
                    <i class="fa-solid fa-arrow-right-from-bracket"></i>
                </div>
                <h5 class="fw-bold mb-1" id="logoutModalLabel" style="color: #1e293b; font-size: 1.25rem;">Sign Out</h5>
                <p class="text-muted mb-4" style="font-size: 0.9rem;">
                    Are you sure you want to sign out, <strong style="color: #334155;"><?= htmlspecialchars($__fullName) ?></strong>?
                </p>
                <div class="d-flex gap-2 justify-content-center">
                    <button type="button" class="btn btn-secondary flex-fill fw-bold py-2" data-bs-dismiss="modal" style="border-radius: 8px;">
                        <i class="fa-solid fa-xmark me-1"></i>Cancel
                    </button>
                    <a href="/sharonstore3.0/logout.php" class="btn btn-danger flex-fill fw-bold py-2" style="border-radius: 8px;">
                        <i class="fa-solid fa-right-from-bracket me-1"></i>Sign Out
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
