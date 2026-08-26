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

/* ── Logout Confirmation ────────────────────────────────────── */
function confirmLogout(e) {
    if (!confirm('Are you sure you want to sign out?')) {
        e.preventDefault();
        return false;
    }
    return true;
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
        if (window.innerWidth >= 992) {
            if (sidebar) sidebar.classList.remove('show');
            if (overlay) overlay.classList.remove('show');
        }
    });
});
