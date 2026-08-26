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
?>
<!-- ══ TOPBAR ══════════════════════════════════════════════ -->
<header class="ss-topbar" id="appTopbar">

    <!-- Hamburger (mobile) -->
    <button class="btn ss-hamburger me-2 p-0" id="sidebarToggleBtn"
            style="width:36px;height:36px;align-items:center;justify-content:center;"
            aria-label="Toggle menu">
        <i class="fa-solid fa-bars" style="font-size:15px;"></i>
    </button>

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

        <!-- User Dropdown -->
        <div class="dropdown">
            <button class="btn ss-user-btn dropdown-toggle d-flex align-items-center gap-2 border-0"
                    type="button" data-bs-toggle="dropdown" aria-expanded="false">
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
            <ul class="dropdown-menu dropdown-menu-end ss-dropdown-menu">
                <li class="px-3 py-2">
                    <div class="d-flex align-items-center gap-2">
                        <div class="ss-avatar" style="width:36px;height:36px;">
                            <?= htmlspecialchars($__initials) ?>
                        </div>
                        <div class="lh-sm">
                            <div style="font-size:0.88rem;font-weight:700;color:#2C2D2D;">
                                <?= htmlspecialchars($__fullName) ?>
                            </div>
                            <div style="font-size:0.72rem;color:#555657;">
                                <?= ucfirst(htmlspecialchars($__role)) ?> Account
                            </div>
                        </div>
                    </div>
                </li>
                <li><hr class="dropdown-divider ss-divider"></li>
                <?php if ($__role === 'admin'): ?>
                <li>
                    <a href="/sharonstore3.0/dashboard.php" class="dropdown-item ss-dropdown-item">
                        <i class="fa-solid fa-chart-pie me-2" style="color:#0abf8a;width:16px;"></i>Dashboard
                    </a>
                </li>
                <li>
                    <a href="/sharonstore3.0/inventory.php" class="dropdown-item ss-dropdown-item">
                        <i class="fa-solid fa-boxes-stacked me-2" style="color:#3b82f6;width:16px;"></i>Inventory
                    </a>
                </li>
                <?php else: ?>
                <li>
                    <a href="/sharonstore3.0/pos.php" class="dropdown-item ss-dropdown-item">
                        <i class="fa-solid fa-cash-register me-2" style="color:#0abf8a;width:16px;"></i>Point of Sale
                    </a>
                </li>
                <?php endif; ?>
                <li><hr class="dropdown-divider ss-divider"></li>
                <li>
                    <a href="/sharonstore3.0/logout.php" class="dropdown-item ss-dropdown-item ss-logout-item"
                       onclick="return confirmLogout(event)">
                        <i class="fa-solid fa-right-from-bracket me-2" style="color:#f87171;width:16px;"></i>Sign Out
                    </a>
                </li>
            </ul>
        </div>

        <!-- Logout Button (visible on smaller screens) -->
        <a href="/sharonstore3.0/logout.php" class="ss-btn-logout d-none d-sm-flex"
           onclick="return confirmLogout(event)" title="Sign Out">
            <i class="fa-solid fa-right-from-bracket"></i>
            <span class="d-none d-lg-inline">Sign Out</span>
        </a>

    </div>
</header>
