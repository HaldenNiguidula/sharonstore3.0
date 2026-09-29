<?php
// ============================================================
// Sidebar Navigation Include — Sharon Store System
// Both Admin and Cashier have access to POS
// Audit Trail removed from admin menu
// ============================================================
require_once __DIR__ . '/auth_check.php';
requireLogin();

$__role        = currentRole();
$__fullName    = currentUserName();
$__initials    = implode('', array_map(fn($w) => strtoupper($w[0]), array_filter(explode(' ', $__fullName))));
$__initials    = substr($__initials, 0, 2);
$__currentPage = basename($_SERVER['PHP_SELF']);

function sidebarActive(string $page, string $current): string {
    return ($page === $current) ? 'active' : '';
}
?>
<!-- Sidebar Overlay (mobile) -->
<div class="ss-sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

<nav class="ss-sidebar d-flex flex-column" id="appSidebar">

    <!-- Brand -->
    <div class="ss-brand d-flex align-items-center gap-3 px-4 py-3">
        <div class="ss-brand-icon">
            <i class="fa-solid fa-leaf"></i>
        </div>
        <div class="lh-sm">
            <div class="ss-brand-name">Sharon Store</div>
            <div class="ss-brand-sub">POS &amp; Inventory</div>
        </div>
    </div>

    <hr class="ss-sidebar-divider my-0">

    <!-- Navigation -->
    <div class="ss-nav-scroll flex-grow-1 overflow-auto py-2">
        <ul class="ss-nav-list list-unstyled mb-0 px-2">

            <?php if ($__role === 'admin'): ?>
            <!-- ===== ADMIN NAV ===== -->
            <li><span class="ss-nav-section-label">Main Menu</span></li>

            <li>
                <a href="/sharonstore3.0/dashboard.php"
                   class="ss-nav-item <?= sidebarActive('dashboard.php', $__currentPage) ?>">
                    <span class="ss-nav-icon"><i class="fa-solid fa-chart-pie"></i></span>
                    <span class="ss-nav-label">Dashboard</span>
                    <?php if ($__currentPage === 'dashboard.php'): ?>
                    <span class="ss-nav-active-dot ms-auto"></span>
                    <?php endif; ?>
                </a>
            </li>

            <li>
                <a href="/sharonstore3.0/pos.php"
                   class="ss-nav-item <?= sidebarActive('pos.php', $__currentPage) ?>">
                    <span class="ss-nav-icon"><i class="fa-solid fa-cash-register"></i></span>
                    <span class="ss-nav-label">Point of Sale</span>
                    <?php if ($__currentPage === 'pos.php'): ?>
                    <span class="ss-nav-active-dot ms-auto"></span>
                    <?php endif; ?>
                </a>
            </li>

            <li>
                <a href="/sharonstore3.0/inventory.php"
                   class="ss-nav-item <?= sidebarActive('inventory.php', $__currentPage) ?>">
                    <span class="ss-nav-icon"><i class="fa-solid fa-boxes-stacked"></i></span>
                    <span class="ss-nav-label">Inventory</span>
                    <?php if ($__currentPage === 'inventory.php'): ?>
                    <span class="ss-nav-active-dot ms-auto"></span>
                    <?php endif; ?>
                </a>
            </li>

            <li>
                <a href="/sharonstore3.0/forecasting.php"
                   class="ss-nav-item <?= sidebarActive('forecasting.php', $__currentPage) ?>">
                    <span class="ss-nav-icon"><i class="fa-solid fa-chart-line"></i></span>
                    <span class="ss-nav-label">Sales Forecasting</span>
                    <?php if ($__currentPage === 'forecasting.php'): ?>
                    <span class="ss-nav-active-dot ms-auto"></span>
                    <?php endif; ?>
                </a>
            </li>

            <li>
                <a href="/sharonstore3.0/stock-monitor.php"
                   class="ss-nav-item <?= sidebarActive('stock-monitor.php', $__currentPage) ?>">
                    <span class="ss-nav-icon"><i class="fa-solid fa-magnifying-glass-chart"></i></span>
                    <span class="ss-nav-label">Stock Monitor</span>
                    <?php if ($__currentPage === 'stock-monitor.php'): ?>
                    <span class="ss-nav-active-dot ms-auto"></span>
                    <?php endif; ?>
                </a>
            </li>

            <li><span class="ss-nav-section-label">Administration</span></li>

            <li>
                <a href="/sharonstore3.0/suppliers.php"
                   class="ss-nav-item <?= sidebarActive('suppliers.php', $__currentPage) ?>">
                    <span class="ss-nav-icon"><i class="fa-solid fa-truck-field"></i></span>
                    <span class="ss-nav-label">Suppliers</span>
                    <?php if ($__currentPage === 'suppliers.php'): ?>
                    <span class="ss-nav-active-dot ms-auto"></span>
                    <?php endif; ?>
                </a>
            </li>

            <li>
                <a href="/sharonstore3.0/reports.php"
                   class="ss-nav-item <?= sidebarActive('reports.php', $__currentPage) ?>">
                    <span class="ss-nav-icon"><i class="fa-solid fa-chart-line"></i></span>
                    <span class="ss-nav-label">Sales Reports</span>
                    <?php if ($__currentPage === 'reports.php'): ?>
                    <span class="ss-nav-active-dot ms-auto"></span>
                    <?php endif; ?>
                </a>
            </li>

            <li>
                <a href="/sharonstore3.0/accounts.php"
                   class="ss-nav-item <?= sidebarActive('accounts.php', $__currentPage) ?>">
                    <span class="ss-nav-icon"><i class="fa-solid fa-users-gear"></i></span>
                    <span class="ss-nav-label">Manage Accounts</span>
                    <?php if ($__currentPage === 'accounts.php'): ?>
                    <span class="ss-nav-active-dot ms-auto"></span>
                    <?php endif; ?>
                </a>
            </li>

            <li>
                <a href="/sharonstore3.0/backup.php"
                   class="ss-nav-item <?= sidebarActive('backup.php', $__currentPage) ?>">
                    <span class="ss-nav-icon"><i class="fa-solid fa-database"></i></span>
                    <span class="ss-nav-label">Database Backup</span>
                    <?php if ($__currentPage === 'backup.php'): ?>
                    <span class="ss-nav-active-dot ms-auto"></span>
                    <?php endif; ?>
                </a>
            </li>

            <?php else: ?>
            <!-- ===== CASHIER NAV ===== -->
            <li><span class="ss-nav-section-label">Operations</span></li>

            <li>
                <a href="/sharonstore3.0/pos.php"
                   class="ss-nav-item <?= sidebarActive('pos.php', $__currentPage) ?>">
                    <span class="ss-nav-icon"><i class="fa-solid fa-cash-register"></i></span>
                    <span class="ss-nav-label">Point of Sale</span>
                    <?php if ($__currentPage === 'pos.php'): ?>
                    <span class="ss-nav-active-dot ms-auto"></span>
                    <?php endif; ?>
                </a>
            </li>

            <li>
                <a href="/sharonstore3.0/stock-monitor.php"
                   class="ss-nav-item <?= sidebarActive('stock-monitor.php', $__currentPage) ?>">
                    <span class="ss-nav-icon"><i class="fa-solid fa-magnifying-glass-chart"></i></span>
                    <span class="ss-nav-label">Stock Monitor</span>
                    <?php if ($__currentPage === 'stock-monitor.php'): ?>
                    <span class="ss-nav-active-dot ms-auto"></span>
                    <?php endif; ?>
                </a>
            </li>
            <?php endif; ?>

        </ul>
    </div>

    <!-- Footer user card -->
    <div class="ss-sidebar-footer px-3 py-3">
        <hr class="ss-sidebar-divider mt-0 mb-3">
        <div class="d-flex align-items-center gap-2">
            <div class="ss-avatar ss-avatar-sm"><?= htmlspecialchars($__initials) ?></div>
            <div class="flex-grow-1 lh-sm overflow-hidden">
                <div class="ss-footer-name text-truncate"><?= htmlspecialchars($__fullName) ?></div>
                <div class="ss-footer-role"><?= ucfirst(htmlspecialchars($__role)) ?></div>
            </div>
            <a href="/sharonstore3.0/logout.php" class="ss-logout-btn"
               title="Sign Out" onclick="return confirm('Sign out?')">
                <i class="fa-solid fa-right-from-bracket"></i>
            </a>
        </div>
    </div>

</nav>

<script>
function closeSidebar() {
    const sidebar = document.getElementById('appSidebar');
    const overlay = document.getElementById('sidebarOverlay');
    if (sidebar) sidebar.classList.remove('show');
    if (overlay) overlay.classList.remove('show');
}
window.addEventListener('resize', function () {
    if (window.innerWidth >= 992) closeSidebar();
});
</script>
