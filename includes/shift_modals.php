<?php
// ============================================================
// Shared Shift Modals & Logic — Sharon Store System
// Included in pos.php, stock-monitor.php, etc.
// ============================================================
?>
<!-- ══ START SHIFT MODAL ═══════════════════════════════════════ -->
<div class="modal fade" id="startShiftModal" data-bs-backdrop="static" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 420px;">
        <div class="modal-content border-0 shadow-lg text-start" style="border-radius: 16px; overflow: hidden; background: #ffffff;">
            <div class="modal-header bg-light border-0 py-3 px-4">
                <h5 class="modal-title fw-bold" style="color: #1e293b;"><i class="fa-solid fa-key text-primary me-2"></i>Manager Authorization</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" onclick="closeModal('startShiftModal')"></button>
            </div>
            <form id="startShiftForm">
                <div class="modal-body p-4">
                    <p class="text-muted mb-4" style="font-size: 0.92rem;">A Manager PIN is required to open the register / unlock operations. Enter starting petty cash below.</p>
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-dark">Manager PIN <span class="text-danger">*</span></label>
                        <input type="password" class="form-control form-control-lg text-center fw-bold" id="startPin" required maxlength="4" placeholder="****" autocomplete="off">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-dark">Starting Cash (₱) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" class="form-control form-control-lg text-end" id="startCash" required placeholder="0.00">
                    </div>
                </div>
                <div class="modal-footer border-0 bg-light py-3 px-4 d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-secondary fw-bold" data-bs-dismiss="modal" onclick="closeModal('startShiftModal')" style="border-radius: 8px;">Cancel</button>
                    <button type="submit" class="btn btn-primary fw-bold" id="btnStartShift" style="border-radius: 8px;">Open Shift</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ══ END SHIFT MODAL ═════════════════════════════════════════ -->
<div class="modal fade" id="endShiftModal" data-bs-backdrop="static" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 420px;">
        <div class="modal-content border-0 shadow-lg text-start" style="border-radius: 16px; overflow: hidden; background: #ffffff;">
            <div class="modal-header bg-light border-0 py-3 px-4">
                <h5 class="modal-title fw-bold" style="color: #1e293b;"><i class="fa-solid fa-lock text-danger me-2"></i>Close Shift</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" onclick="closeModal('endShiftModal')"></button>
            </div>
            <form id="endShiftForm">
                <div class="modal-body p-4">
                    <p class="text-muted mb-4" style="font-size: 0.92rem;">A Manager PIN is required to close the drawer. Please count the physical cash in the drawer and enter it below.</p>
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-dark">Manager PIN <span class="text-danger">*</span></label>
                        <input type="password" class="form-control form-control-lg text-center fw-bold" id="endPin" required maxlength="4" placeholder="****" autocomplete="off">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-dark">Counted Drawer Cash (₱) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" class="form-control form-control-lg text-end" id="endCash" required placeholder="0.00">
                    </div>
                </div>
                <div class="modal-footer border-0 bg-light py-3 px-4 d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-secondary fw-bold" data-bs-dismiss="modal" onclick="closeModal('endShiftModal')" style="border-radius: 8px;">Cancel</button>
                    <button type="submit" class="btn btn-danger fw-bold" id="btnEndShift" style="border-radius: 8px;">Close Drawer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ══ SHIFT SUMMARY MODAL (Variance / Receipt) ═══════════════ -->
<div class="modal fade" id="shiftSummaryModal" data-bs-backdrop="static" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 420px;">
        <div class="modal-content border-0 shadow-lg text-center" style="border-radius: 16px; overflow: hidden; background: #ffffff;">
            <div class="modal-header bg-light border-0 py-3 px-4">
                <h5 class="modal-title fw-bold" style="color: #1e293b;"><i class="fa-solid fa-file-invoice-dollar text-success me-2"></i>Drawer Closed</h5>
            </div>
            <div class="modal-body p-4 text-center">
                <h4 class="mb-4 fw-bold">Drawer successfully closed!</h4>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted fw-bold">Expected Cash:</span>
                    <span class="fw-bold" id="sumExpected">₱0.00</span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted fw-bold">Actual Counted:</span>
                    <span class="fw-bold" id="sumActual">₱0.00</span>
                </div>
                <hr>
                <div class="d-flex justify-content-between mb-3">
                    <span class="text-muted fw-bold">Variance:</span>
                    <span class="fw-bold fs-5" id="sumVariance">₱0.00</span>
                </div>
                <button type="button" class="btn btn-primary w-100 fw-bold mt-2" style="border-radius: 8px;" onclick="location.reload()">Okay</button>
            </div>
        </div>
    </div>
</div>

<!-- ══ LIVE SHIFT VIEW MODAL (Active Shift) ═════════════════════ -->
<div class="modal fade" id="liveShiftModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 440px;">
        <div class="modal-content border-0 shadow-lg text-start" style="border-radius: 16px; overflow: hidden; background: #ffffff;">
            <div class="modal-header bg-light border-0 py-3 px-4 justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-2">
                    <h5 class="modal-title fw-bold mb-0" style="color: #1e293b;"><i class="fa-solid fa-list-check text-primary me-2"></i>Live summary</h5>
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle fw-bold" style="font-size:0.72rem;">Active Shift</span>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div id="liveShiftActiveContent">
                    <div class="d-flex justify-content-between mb-2 border-bottom pb-2">
                        <span class="text-muted fw-bold">Cashier on Shift:</span>
                        <span class="fw-bold text-dark" id="liveCashierName">--</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted fw-bold">Shift Started:</span>
                        <span class="fw-bold" id="liveShiftStarted">--</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted fw-bold">Starting Cash:</span>
                        <span class="fw-bold" id="liveStartingCash">₱0.00</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted fw-bold">Shift Transactions:</span>
                        <span class="fw-bold text-primary" id="liveTxnCount">0</span>
                    </div>
                    <div class="d-flex justify-content-between mb-3">
                        <span class="text-muted fw-bold">Shift Total Sales:</span>
                        <span class="fw-bold text-success" id="liveTotalSales">₱0.00</span>
                    </div>
                    <div class="alert alert-primary text-center mb-0 py-2" style="border-radius: 10px;">
                        <span class="d-block text-muted fw-bold" style="font-size:0.8rem;">EXPECTED DRAWER CASH</span>
                        <span class="fw-bold fs-3" id="liveExpectedCash">₱0.00</span>
                    </div>
                </div>
                <div id="liveShiftEmptyContent" style="display:none;" class="text-center py-4">
                    <i class="fa-solid fa-clock-rotate-left fa-3x text-muted opacity-50 mb-3 d-block"></i>
                    <h6 class="fw-bold text-dark mb-1">No Active Shift</h6>
                    <p class="text-muted small mb-0">There is currently no cashier shift open.<br>Shift transactions and drawer cash will be tracked once a shift begins.</p>
                </div>
            </div>
            <div class="modal-footer border-0 bg-light py-2 px-4 justify-content-end">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- ══ TODAY'S SUMMARY MODAL (Whole Day Storewide) ════════════ -->
<div class="modal fade" id="todaySummaryModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 440px;">
        <div class="modal-content border-0 shadow-lg text-start" style="border-radius: 16px; overflow: hidden; background: #ffffff;">
            <div class="modal-header bg-light border-0 py-3 px-4 justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-2">
                    <h5 class="modal-title fw-bold mb-0" style="color: #1e293b;"><i class="fa-solid fa-calendar-day text-success me-2"></i>Today's Summary</h5>
                    <span class="badge bg-success-subtle text-success border border-success-subtle fw-bold" style="font-size:0.72rem;">Whole Day</span>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="d-flex justify-content-between mb-2 border-bottom pb-2">
                    <span class="text-muted fw-bold">Date:</span>
                    <span class="fw-bold text-dark" id="todaySummaryDate">--</span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted fw-bold">Store Scope:</span>
                    <span class="text-dark fw-bold">All Cashiers / Storewide</span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted fw-bold">Total Transactions:</span>
                    <span class="fw-bold text-primary" id="todaySummaryTxnCount">0</span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted fw-bold">Average Sale:</span>
                    <span class="fw-bold" id="todaySummaryAvgSale">₱0.00</span>
                </div>
                <div class="d-flex justify-content-between mb-3">
                    <span class="text-muted fw-bold">Total Cash Received:</span>
                    <span class="fw-bold text-dark" id="todaySummaryTendered">₱0.00</span>
                </div>
                <div class="alert text-center mb-0 py-3" style="border-radius: 12px; background:#e6faf4; border:1.5px solid #b2edd8;">
                    <span class="d-block text-muted fw-bold mb-1" style="font-size:0.75rem; letter-spacing:0.5px;">TOTAL SALES TODAY</span>
                    <span class="fw-bold fs-2 text-success" id="todaySummaryTotalSales">₱0.00</span>
                </div>
            </div>
            <div class="modal-footer border-0 bg-light py-2 px-4 justify-content-between">
                <a href="/sharonstore3.0/reports.php" class="btn btn-sm btn-outline-primary fw-bold">
                    <i class="fa-solid fa-chart-line me-1"></i>View Full Reports
                </a>
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- ══ SHARED SHIFT JAVASCRIPT LOGIC ═══════════════════════════ -->
<script>
(function() {
    window.getModal = function(id) {
        const el = document.getElementById(id);
        if (!el || typeof bootstrap === 'undefined') return null;
        return bootstrap.Modal.getOrCreateInstance(el);
    };

    window.closeModal = function(id) {
        const el = document.getElementById(id);
        if (!el) return;
        try {
            if (typeof bootstrap !== 'undefined') {
                const inst = bootstrap.Modal.getInstance(el) || bootstrap.Modal.getOrCreateInstance(el);
                if (inst) inst.hide();
            }
        } catch (e) {
            console.error('Error hiding modal with bootstrap', e);
        }
        setTimeout(() => {
            el.classList.remove('show');
            el.style.display = 'none';
            el.setAttribute('aria-hidden', 'true');
            el.removeAttribute('aria-modal');
            document.querySelectorAll('.modal-backdrop').forEach(b => b.remove());
            document.body.classList.remove('modal-open');
            document.body.style.removeProperty('padding-right');
            document.body.style.removeProperty('overflow');
        }, 150);
    };

    window.shiftModals = {
        get start() { return window.getModal('startShiftModal'); },
        get end() { return window.getModal('endShiftModal'); },
        get summary() { return window.getModal('shiftSummaryModal'); },
        get live() { return window.getModal('liveShiftModal'); },
        get today() { return window.getModal('todaySummaryModal'); }
    };

    window.openStartShiftModal = function() {
        const m = window.shiftModals.start;
        if (m) m.show();
    };

    window.openEndShiftModal = function() {
        const m = window.shiftModals.end;
        if (m) m.show();
    };

    const isAdmin = <?= isAdmin() ? 'true' : 'false' ?>;

    window.updateProfileAccess = function(hasShift) {
        const userProfileBtn = document.getElementById('userProfileBtn');
        const endShiftItem   = document.getElementById('headerEndShiftItem');
        const endShiftDiv    = document.getElementById('headerEndShiftDivider');
        const posEndBtn      = document.getElementById('posEndShiftBtn');
        const shiftOverlay   = document.getElementById('shiftOverlay');
        const shiftLockCont  = document.getElementById('shiftLockContainer');
        const stockContent   = document.getElementById('stockMonitorContent');

        if (isAdmin) {
            if (endShiftItem)  endShiftItem.style.display  = 'none';
            if (endShiftDiv)   endShiftDiv.style.display   = 'none';
            if (posEndBtn)     posEndBtn.style.display     = 'none';
            if (shiftOverlay)  shiftOverlay.style.display  = 'none';
            if (shiftLockCont) shiftLockCont.style.display = 'none';
            if (stockContent)  stockContent.style.display  = 'block';
            return;
        }

        if (hasShift) {
            // Unlock user profile dropdown
            if (userProfileBtn) {
                userProfileBtn.disabled = false;
                userProfileBtn.style.pointerEvents = 'auto';
                userProfileBtn.style.opacity = '1';
                userProfileBtn.style.cursor = 'pointer';
                userProfileBtn.setAttribute('data-bs-toggle', 'dropdown');
                userProfileBtn.removeAttribute('title');
            }
            if (endShiftItem) endShiftItem.style.display = '';
            if (endShiftDiv)  endShiftDiv.style.display  = '';
            if (posEndBtn)    posEndBtn.style.display    = '';

            // Hide shift locks & show main content
            if (shiftOverlay)  shiftOverlay.style.display  = 'none';
            if (shiftLockCont) shiftLockCont.style.display = 'none';
            if (stockContent)  stockContent.style.display  = 'block';

            // IMMEDIATELY REMOVE ALL LOCK SIGNS ON THE PAGE WITHOUT RELOAD
            document.querySelectorAll('.ss-nav-item .fa-lock, .ss-shift-lock-icon').forEach(icon => {
                icon.remove();
            });

            // Ensure active dot indicator is present on current active nav item
            document.querySelectorAll('.ss-nav-item.active').forEach(item => {
                if (!item.querySelector('.ss-nav-active-dot')) {
                    const dot = document.createElement('span');
                    dot.className = 'ss-nav-active-dot ms-auto';
                    item.appendChild(dot);
                }
            });

            // Trigger stock monitor loader if present
            if (typeof window.loadAll === 'function') {
                window.loadAll();
            }
        } else {
            // Lock user profile dropdown
            if (userProfileBtn) {
                userProfileBtn.disabled = true;
                userProfileBtn.style.pointerEvents = 'none';
                userProfileBtn.style.opacity = '0.55';
                userProfileBtn.style.cursor = 'not-allowed';
                userProfileBtn.removeAttribute('data-bs-toggle');
                userProfileBtn.setAttribute('title', 'Shift not started — Manager authorization required');
                if (window.bootstrap) {
                    const dd = bootstrap.Dropdown.getInstance(userProfileBtn);
                    if (dd) dd.hide();
                }
            }
            if (endShiftItem) endShiftItem.style.display = 'none';
            if (endShiftDiv)  endShiftDiv.style.display  = 'none';
            if (posEndBtn)    posEndBtn.style.display    = 'none';

            // Show shift lock
            if (shiftOverlay)  shiftOverlay.style.display  = 'flex';
            if (shiftLockCont) shiftLockCont.style.display = 'flex';
            if (stockContent)  stockContent.style.display  = 'none';

            // IMMEDIATELY RESTORE LOCK SIGNS ON SIDEBAR ITEMS WITHOUT RELOAD
            document.querySelectorAll('.ss-nav-item').forEach(item => {
                const dot = item.querySelector('.ss-nav-active-dot');
                if (dot) dot.remove();
                if (!item.querySelector('.fa-lock') && !item.querySelector('.ss-shift-lock-icon')) {
                    const lock = document.createElement('i');
                    lock.className = 'fa-solid fa-lock text-muted ms-auto ss-shift-lock-icon';
                    lock.style.fontSize = '0.75rem';
                    lock.title = 'Shift not started';
                    item.appendChild(lock);
                }
            });
        }
    };

    window.checkShiftStatus = async function() {
        if (isAdmin) {
            window.updateProfileAccess(true);
            return;
        }

        try {
            const res = await fetch('/sharonstore3.0/api/shifts.php?action=check');
            const json = await res.json();
            if (json.success) {
                window.currentShiftId = json.data.shift_id;
                window.updateProfileAccess(true);
            } else {
                window.currentShiftId = null;
                window.updateProfileAccess(false);
            }
        } catch (e) {
            console.error('Failed to check shift status', e);
            window.updateProfileAccess(false);
        }
    };

    document.addEventListener('DOMContentLoaded', () => {
        window.checkShiftStatus();

        // Start Shift submit
        const startForm = document.getElementById('startShiftForm');
        if (startForm) {
            startForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                const btn = document.getElementById('btnStartShift');
                if (btn) btn.disabled = true;

                const fd = new FormData();
                fd.append('action', 'start');
                fd.append('pin', document.getElementById('startPin').value);
                fd.append('starting_cash', document.getElementById('startCash').value);

                try {
                    const res = await fetch('/sharonstore3.0/api/shifts.php', { method: 'POST', body: fd });
                    const json = await res.json();
                    if (json.success) {
                        window.currentShiftId = json.data.shift_id;
                        window.closeModal('startShiftModal');
                        window.updateProfileAccess(true);
                        showToast('Shift opened successfully.', 'success');
                    } else if (json.message && json.message.toLowerCase().includes('already have an open shift')) {
                        window.closeModal('startShiftModal');
                        window.updateProfileAccess(true);
                        showToast('Shift is already active.', 'info');
                    } else {
                        showToast(json.message || 'Failed to start shift.', 'error');
                    }
                } catch (err) {
                    showToast('Error connecting to server.', 'error');
                }
                if (btn) btn.disabled = false;
            });
        }

        // End Shift submit
        const endForm = document.getElementById('endShiftForm');
        if (endForm) {
            endForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                const btn = document.getElementById('btnEndShift');
                if (btn) btn.disabled = true;

                const fd = new FormData();
                fd.append('action', 'end');
                fd.append('pin', document.getElementById('endPin').value);
                fd.append('actual_cash', document.getElementById('endCash').value);

                try {
                    const res = await fetch('/sharonstore3.0/api/shifts.php', { method: 'POST', body: fd });
                    const json = await res.json();
                    if (json.success) {
                        window.closeModal('endShiftModal');
                        window.currentShiftId = null;
                        window.updateProfileAccess(false);

                        const expEl = document.getElementById('sumExpected');
                        const actEl = document.getElementById('sumActual');
                        const varEl = document.getElementById('sumVariance');
                        if (expEl) expEl.textContent = '₱' + parseFloat(json.data.expected_cash).toLocaleString('en-US', {minimumFractionDigits:2});
                        if (actEl) actEl.textContent = '₱' + parseFloat(json.data.actual_cash).toLocaleString('en-US', {minimumFractionDigits:2});

                        if (varEl) {
                            const variance = parseFloat(json.data.short_over);
                            varEl.textContent = '₱' + variance.toLocaleString('en-US', {minimumFractionDigits:2});
                            if (variance < 0) {
                                varEl.className = 'fw-bold fs-5 text-danger';
                            } else if (variance > 0) {
                                varEl.className = 'fw-bold fs-5 text-success';
                            } else {
                                varEl.className = 'fw-bold fs-5 text-muted';
                            }
                        }

                        const sumModal = window.shiftModals.summary;
                        if (sumModal) sumModal.show();
                    } else {
                        showToast(json.message || 'Failed to end shift.', 'error');
                        if (btn) btn.disabled = false;
                    }
                } catch (err) {
                    showToast('Error connecting to server.', 'error');
                    if (btn) btn.disabled = false;
                }
            });
        }
    });

    // Also re-check when switching tabs back to this window
    window.addEventListener('focus', () => {
        window.checkShiftStatus();
    });

    // Live summary: Active Shift
    window.viewShiftSummary = async function() {
        try {
            const res = await fetch('/sharonstore3.0/api/shifts.php?action=summary');
            const json = await res.json();

            const activeCont  = document.getElementById('liveShiftActiveContent');
            const emptyCont   = document.getElementById('liveShiftEmptyContent');
            const cashierEl   = document.getElementById('liveCashierName');
            const startedEl   = document.getElementById('liveShiftStarted');
            const startCashEl = document.getElementById('liveStartingCash');
            const txnCountEl  = document.getElementById('liveTxnCount');
            const salesEl     = document.getElementById('liveTotalSales');
            const expectedEl  = document.getElementById('liveExpectedCash');

            if (json.success && json.data && json.data.has_active_shift) {
                if (activeCont) activeCont.style.display = '';
                if (emptyCont)  emptyCont.style.display  = 'none';

                if (cashierEl) cashierEl.textContent = json.data.cashier_name || 'Active Cashier';
                if (startedEl) {
                    const timeStr = json.data.start_time 
                        ? new Date(json.data.start_time.replace(/-/g, '/')).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})
                        : '--';
                    startedEl.textContent = timeStr;
                }
                if (startCashEl) {
                    startCashEl.textContent = '₱' + parseFloat(json.data.starting_cash || 0).toLocaleString('en-US', {minimumFractionDigits:2});
                }
                if (txnCountEl) {
                    txnCountEl.textContent = json.data.txn_count || 0;
                }
                if (salesEl) {
                    salesEl.textContent = '₱' + parseFloat(json.data.total_sales || 0).toLocaleString('en-US', {minimumFractionDigits:2});
                }
                if (expectedEl) {
                    expectedEl.textContent = '₱' + parseFloat(json.data.expected_cash || 0).toLocaleString('en-US', {minimumFractionDigits:2});
                }
            } else {
                if (activeCont) activeCont.style.display = 'none';
                if (emptyCont)  emptyCont.style.display  = '';
            }

            const lm = window.shiftModals ? window.shiftModals.live : null;
            if (lm) {
                lm.show();
            } else {
                const el = document.getElementById('liveShiftModal');
                if (el && typeof bootstrap !== 'undefined') new bootstrap.Modal(el).show();
            }
        } catch (err) {
            console.error(err);
            showToast('Error retrieving live summary.', 'error');
        }
    };

    // Today's summary: Whole Day Storewide
    window.viewTodaySummary = async function() {
        try {
            const res = await fetch('/sharonstore3.0/api/transactions.php?action=daily_summary');
            const json = await res.json();

            if (json && json.success) {
                const dateEl   = document.getElementById('todaySummaryDate');
                const countEl  = document.getElementById('todaySummaryTxnCount');
                const avgEl    = document.getElementById('todaySummaryAvgSale');
                const tendEl   = document.getElementById('todaySummaryTendered');
                const salesEl  = document.getElementById('todaySummaryTotalSales');

                if (dateEl)  dateEl.textContent  = json.date_fmt || json.date || '--';
                if (countEl) countEl.textContent = json.transaction_count || 0;
                if (avgEl)   avgEl.textContent   = json.avg_sale_fmt || ('₱' + parseFloat(json.avg_sale || 0).toFixed(2));
                if (tendEl)  tendEl.textContent  = json.total_tendered_fmt || ('₱' + parseFloat(json.total_tendered || 0).toFixed(2));
                if (salesEl) salesEl.textContent = json.total_sales_fmt || ('₱' + parseFloat(json.total_sales || 0).toFixed(2));

                const tm = window.shiftModals ? window.shiftModals.today : null;
                if (tm) {
                    tm.show();
                } else {
                    const el = document.getElementById('todaySummaryModal');
                    if (el && typeof bootstrap !== 'undefined') new bootstrap.Modal(el).show();
                }
            } else {
                showToast(json.message || 'Unable to retrieve today\'s summary.', 'warning');
            }
        } catch (err) {
            console.error(err);
            showToast('Error retrieving today\'s summary.', 'error');
        }
    };
})();
</script>
