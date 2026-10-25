/* ============================================================
   app.js — Shared Utilities — Sharon Store System
   ============================================================ */

/* ── Toast Notifications ────────────────────────────────────── */
function showToast(message, type = 'info', duration = 4000) {
    const icons = {
        success: 'fa-circle-check',
        error:   'fa-circle-xmark',
        warning: 'fa-triangle-exclamation',
        info:    'fa-circle-info',
    };
    const container = document.getElementById('toastContainer');
    if (!container) return;

    const toast = document.createElement('div');
    toast.className = `ss-toast ${type}`;
    toast.innerHTML = `
        <i class="fa-solid ${icons[type] || icons.info} ss-toast-icon ${type}"></i>
        <span class="ss-toast-msg">${escHtml(message)}</span>
        <button class="ss-toast-close" onclick="this.parentElement.remove()">
            <i class="fa-solid fa-xmark"></i>
        </button>`;

    container.appendChild(toast);
    setTimeout(() => toast.style.opacity = '0', duration - 400);
    setTimeout(() => toast.remove(), duration);
}

/* ── Safe HTML Escape ───────────────────────────────────────── */
function escHtml(s) {
    const d = document.createElement('div');
    d.textContent = String(s ?? '');
    return d.innerHTML;
}
// Alias used across pages
function esc(s) { return escHtml(s); }

/* ── Number Formatting ──────────────────────────────────────── */
function formatPeso(v) {
    return '&#8369;' + parseFloat(v || 0).toLocaleString('en-PH', {
        minimumFractionDigits: 2, maximumFractionDigits: 2,
    });
}

/* ── Animated Counter ───────────────────────────────────────── */
function animateCounter(elementId, target, isCurrency = false) {
    const el = document.getElementById(elementId);
    if (!el) return;
    const duration = 900;
    const steps    = 45;
    const stepMs   = duration / steps;
    let   frame    = 0;

    const interval = setInterval(() => {
        frame++;
        const progress = frame / steps;
        const ease     = 1 - Math.pow(1 - progress, 3);   // ease-out cubic
        const current  = target * ease;

        if (isCurrency) {
            el.textContent = '\u20b1' + current.toLocaleString('en-PH', {
                minimumFractionDigits: 2, maximumFractionDigits: 2,
            });
        } else {
            el.textContent = Math.round(current).toLocaleString();
        }

        if (frame >= steps) {
            clearInterval(interval);
            el.textContent = isCurrency
                ? '&#8369;' + parseFloat(target).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
                : Math.round(target).toLocaleString();
        }
    }, stepMs);
}

/* ── Debounce ───────────────────────────────────────────────── */
function debounce(fn, delay = 300) {
    let timer;
    return (...args) => {
        clearTimeout(timer);
        timer = setTimeout(() => fn(...args), delay);
    };
}

/* ── API fetch wrapper ──────────────────────────────────────── */
async function apiFetch(url, options = {}) {
    const r = await fetch(url, {
        headers: { 'Accept': 'application/json', ...(options.headers || {}) },
        ...options,
    });
    if (!r.ok) throw new Error(`HTTP ${r.status}`);
    return r.json();
}

/* ── Live Clock Updater ─────────────────────────────────────── */
(function startLiveClock() {
    const timeEl = document.getElementById('liveTime');
    const dateEl = document.getElementById('liveDate');
    if (!timeEl && !dateEl) return;

    const days   = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
    const months = ['January','February','March','April','May','June','July','August','September','October','November','December'];

    function tick() {
        const now  = new Date();
        let   h    = now.getHours();
        const m    = String(now.getMinutes()).padStart(2, '0');
        const s    = String(now.getSeconds()).padStart(2, '0');
        const ampm = h >= 12 ? 'PM' : 'AM';
        h = h % 12 || 12;

        if (timeEl) timeEl.textContent = `${String(h).padStart(2,'0')}:${m}:${s} ${ampm}`;
        if (dateEl) dateEl.textContent = `${days[now.getDay()]}, ${months[now.getMonth()]} ${now.getDate()}, ${now.getFullYear()}`;
    }
    tick();
    setInterval(tick, 1000);
})();

/* ── Logout Confirmation (Modal) ────────────────────────────── */
function confirmLogout(e) {
    if (e) {
        e.preventDefault();
        e.stopPropagation();
    }
    const modalEl = document.getElementById('logoutModal');
    if (modalEl && window.bootstrap) {
        const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        modal.show();
    }
    return false;
}

/* ── Sidebar Toggle (mobile) ────────────────────────────────── */
document.addEventListener('DOMContentLoaded', function () {
    const btn     = document.getElementById('sidebarToggleBtn');
    const sidebar = document.getElementById('appSidebar');
    const overlay = document.getElementById('sidebarOverlay');

    if (btn && sidebar) {
        btn.addEventListener('click', () => {
            sidebar.classList.toggle('show');
            if (overlay) overlay.classList.toggle('show');
        });
    }
    if (overlay) {
        overlay.addEventListener('click', () => {
            if (sidebar) sidebar.classList.remove('show');
            overlay.classList.remove('show');
        });
    }
    window.addEventListener('resize', () => {
        if (window.innerWidth >= 768) {
            if (sidebar) sidebar.classList.remove('show');
            if (overlay) overlay.classList.remove('show');
        }
    });
});

/* ── Universal Table Header Sorting ────────────────────────── */
function makeTableSortable(table) {
    if (!table) return;
    if (table.dataset.serverSort === 'true' || table.id === 'inventoryTable') return;
    const thead = table.querySelector('thead');
    if (!thead) return;

    const headerRows = thead.querySelectorAll('tr');
    if (!headerRows.length) return;
    const lastRow = headerRows[headerRows.length - 1];
    const ths = lastRow.querySelectorAll('th');

    ths.forEach((th, colIdx) => {
        const text = th.textContent.replace(/[\n\r]+/g, ' ').trim().toLowerCase();
        const isAction = text === 'actions' || text === 'action' || th.classList.contains('no-sort') || th.dataset.noSort === 'true' || (!text && th.children.length === 0);

        if (isAction) {
            th.classList.remove('sortable-th');
            return;
        }

        th.classList.add('sortable-th');
        if (!th.hasAttribute('title')) th.setAttribute('title', 'Click to sort');

        // Ensure sort icon exists
        let icon = th.querySelector('.sort-icon');
        if (!icon) {
            icon = document.createElement('i');
            icon.className = 'fa-solid fa-sort sort-icon no-print';
            th.appendChild(icon);
        }

        if (!th.dataset.sortClickBound) {
            th.dataset.sortClickBound = 'true';
            th.addEventListener('click', (e) => {
                if (e.target.closest('button, input, select, a, textarea')) return;
                sortTableByColumn(table, th, colIdx);
            });
        }
    });

    table.dataset.sortEngineAttached = 'true';
}

function sortTableByColumn(table, th, colIdx) {
    const tbody = table.querySelector('tbody') || table.tBodies[0];
    if (!tbody) return;

    // Filter rows that are data rows (exclude colspan loading/empty rows)
    const rows = Array.from(tbody.querySelectorAll('tr')).filter(tr => {
        const firstTd = tr.querySelector('td');
        if (!firstTd) return false;
        if (firstTd.hasAttribute('colspan')) return false;
        if (tr.classList.contains('no-sort-row') || tr.classList.contains('empty-row')) return false;
        if (tr.id === 'noShiftsRow' || tr.id === 'noDataRow') return false;
        return true;
    });

    if (rows.length < 2) return;

    const isAsc = th.classList.contains('sorted-asc');
    const newDirection = isAsc ? 'desc' : 'asc';

    // Reset all headers in this table's thead
    const thead = table.querySelector('thead');
    const allThs = thead ? thead.querySelectorAll('th') : table.querySelectorAll('th');
    allThs.forEach(otherTh => {
        otherTh.classList.remove('sorted-asc', 'sorted-desc');
        const icon = otherTh.querySelector('.sort-icon');
        if (icon) {
            icon.className = 'fa-solid fa-sort sort-icon no-print';
            icon.style.color = '';
            icon.style.opacity = '';
        }
    });

    th.classList.add(newDirection === 'asc' ? 'sorted-asc' : 'sorted-desc');
    const activeIcon = th.querySelector('.sort-icon');
    if (activeIcon) {
        activeIcon.className = `fa-solid ${newDirection === 'asc' ? 'fa-sort-up' : 'fa-sort-down'} sort-icon no-print`;
        activeIcon.style.color = 'var(--accent)';
        activeIcon.style.opacity = '1';
    }

    function getVal(tr) {
        const td = tr.children[colIdx];
        if (!td) return '';
        if (td.dataset.sortValue !== undefined) return td.dataset.sortValue;
        const innerVal = td.querySelector('[data-sort-value]');
        if (innerVal && innerVal.dataset.sortValue !== undefined) return innerVal.dataset.sortValue;
        return td.textContent.trim();
    }

    function parseSortValue(raw) {
        if (raw === null || raw === undefined) return { type: 'empty', val: '' };
        const clean = String(raw).trim();
        if (!clean || clean === '—' || clean === '-' || clean === 'n/a' || clean === 'none') {
            return { type: 'empty', val: '' };
        }

        // 1. Numeric check: IDs (#0012, #1), Currency (₱1,250.00, +₱50, -₱20.50), Quantities (15 pcs, 2.5 kg), Percentages
        const numMatch = clean.match(/^#?\s*([-+]?)\s*[₱$€¥]?\s*([0-9]{1,3}(?:,[0-9]{3})*(?:\.[0-9]+)?|[0-9]+(?:\.[0-9]+)?)\s*(?:[a-zA-Z%]+)?$/);
        if (numMatch) {
            const sign = numMatch[1] === '-' ? -1 : 1;
            const numStr = numMatch[2].replace(/,/g, '');
            const parsedNum = parseFloat(numStr) * sign;
            if (!isNaN(parsedNum)) {
                return { type: 'number', val: parsedNum };
            }
        }

        // 2. Date check: ISO format YYYY-MM-DD or datetime YYYY-MM-DD HH:MM:SS
        const isoMatch = clean.match(/\b\d{4}-\d{2}-\d{2}(?:[ T]\d{2}:\d{2}(?::\d{2})?)?\b/);
        if (isoMatch) {
            const parsedD = Date.parse(isoMatch[0].replace(' ', 'T'));
            if (!isNaN(parsedD)) return { type: 'date', val: parsedD };
        }

        // 3. Readable Month Date: e.g. Oct 15, 2026, October 15, 2026, Oct 15, 2026 10:30 AM
        const readableDateMatch = clean.match(/\b(?:Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)[a-z]* \d{1,2},? \d{4}(?: \d{1,2}:\d{2}(?::\d{2})?(?: [AP]M)?)?/i);
        if (readableDateMatch) {
            const parsedD = Date.parse(readableDateMatch[0]);
            if (!isNaN(parsedD)) return { type: 'date', val: parsedD };
        }

        // General Date parse if looks like a date string
        if (clean.length >= 8 && clean.length <= 40 && !/^[a-zA-Z\s]+$/.test(clean)) {
            const parsedD = Date.parse(clean);
            if (!isNaN(parsedD) && parsedD > 315532800000) {
                return { type: 'date', val: parsedD };
            }
        }

        return { type: 'string', val: clean.toLowerCase() };
    }

    rows.sort((a, b) => {
        const valA = parseSortValue(getVal(a));
        const valB = parseSortValue(getVal(b));

        // Always push empty/dash values to the bottom regardless of sort direction
        if (valA.type === 'empty' && valB.type !== 'empty') return 1;
        if (valB.type === 'empty' && valA.type !== 'empty') return -1;
        if (valA.type === 'empty' && valB.type === 'empty') return 0;

        let cmp = 0;
        if (valA.type === 'number' && valB.type === 'number') {
            cmp = valA.val - valB.val;
        } else if (valA.type === 'date' && valB.type === 'date') {
            cmp = valA.val - valB.val;
        } else {
            cmp = String(valA.val).localeCompare(String(valB.val), undefined, { numeric: true, sensitivity: 'base' });
        }

        return newDirection === 'asc' ? cmp : -cmp;
    });

    // Re-append rows in sorted order
    rows.forEach(r => tbody.appendChild(r));
}

function initGlobalTableSorting() {
    const tables = document.querySelectorAll('table.table, table.table-custom, table.table-report');
    tables.forEach(t => makeTableSortable(t));
}

// Auto-run on DOM ready and observe for dynamic table additions
document.addEventListener('DOMContentLoaded', () => {
    initGlobalTableSorting();

    // Observe AJAX-rendered tables or headers
    const observer = new MutationObserver(() => {
        const tables = document.querySelectorAll('table.table, table.table-custom, table.table-report');
        tables.forEach(t => {
            const hasUnboundTh = t.querySelector('thead th:not([data-sort-click-bound="true"])');
            if (t.dataset.sortEngineAttached !== 'true' || hasUnboundTh) {
                makeTableSortable(t);
            }
        });
    });
    observer.observe(document.body, { childList: true, subtree: true });
});

window.makeTableSortable = makeTableSortable;
window.sortTableByColumn = sortTableByColumn;
window.initGlobalTableSorting = initGlobalTableSorting;

