// ============================================================
// pos.js  -  Point of Sale  (Barcode + Manual Search)
// Sharon Store System  |  Admin & Cashier
// ============================================================
'use strict';

function esc(s) {
    var d = document.createElement('div');
    d.textContent = String(s == null ? '' : s);
    return d.innerHTML;
}

function _toast(msg, type) {
    if (typeof showToast === 'function') { showToast(msg, type || 'info'); }
    else { console[type === 'error' ? 'error' : 'log']('[POS]', msg); }
}

var cart      = [];
var allItems  = [];
var receiptM  = null;
var srchTimer = null;
var VAT_RATE  = 12 / 112;

document.addEventListener('DOMContentLoaded', function () {
    receiptM = new bootstrap.Modal(document.getElementById('receiptModal'));
    restoreCart();
    bindEvents();
    updateTxnCode();
    tickClock();
    setInterval(tickClock, 1000);
    loadDailySummary();
    loadAllItems();
    focusScanner();
});

function tickClock() {
    var now  = new Date();
    var time = now.toLocaleTimeString('en-PH', {hour:'2-digit', minute:'2-digit', second:'2-digit'});
    var date = now.toLocaleDateString('en-PH', {weekday:'short', month:'short', day:'numeric', year:'numeric'});
    var te = document.getElementById('posTime');
    var de = document.getElementById('posDate');
    if (te) te.textContent = time;
    if (de) de.textContent = date;
}

function switchTab(tab) {
    var bPanel = document.getElementById('panelBarcode');
    var mPanel = document.getElementById('panelManual');
    var bTab   = document.getElementById('tabBarcode');
    var mTab   = document.getElementById('tabManual');
    if (tab === 'barcode') {
        bPanel.style.display = '';
        mPanel.style.display = 'none';
        bTab.classList.add('active');
        mTab.classList.remove('active');
        focusScanner();
    } else {
        bPanel.style.display = 'none';
        mPanel.style.display = 'flex';
        bTab.classList.remove('active');
        mTab.classList.add('active');
        if (allItems.length > 0) { renderGrid(allItems); } else { loadAllItems(); }
        setTimeout(function () { var si = document.getElementById('itemSearchInput'); if (si) si.focus(); }, 80);
    }
}
window.switchTab = switchTab;

function loadAllItems() {
    var g = document.getElementById('itemGrid');
    if (g) {
        g.innerHTML = '<div style="grid-column:1/-1;text-align:center;padding:20px;color:#555657;">'
            + '<div class="loading-spinner" style="margin:0 auto 8px;"></div>Loading products...</div>';
    }
    fetch('/sharonstore3.0/api/transactions.php?action=search_items&limit=200', { headers: {'Accept':'application/json'} })
    .then(function (r) { if (r.status === 401) throw new Error('session'); if (!r.ok) throw new Error('HTTP ' + r.status); return r.json(); })
    .then(function (j) { if (!j.success) throw new Error(j.message || 'API error'); allItems = j.items || []; renderGrid(allItems); })
    .catch(function (err) {
        var msg = err.message === 'session'
            ? 'Session expired. <a href="/sharonstore3.0/index.php" style="color:#0abf8a;font-weight:700;">Log in again</a>.'
            : 'Could not load products. <button onclick="loadAllItems()" style="padding:4px 14px;margin-top:6px;background:#e6faf4;border:1.5px solid #b2edd8;color:#0abf8a;border-radius:6px;cursor:pointer;font-weight:700;">&#8635; Retry</button>';
        if (g) g.innerHTML = '<div style="grid-column:1/-1;text-align:center;padding:20px;color:#ef4444;font-weight:600;">' + msg + '</div>';
    });
}

function renderGrid(items) {
    var g = document.getElementById('itemGrid');
    if (!g) return;
    if (!items || items.length === 0) {
        g.innerHTML = '<div style="grid-column:1/-1;text-align:center;padding:24px;color:#555657;">'
            + '<i class="fa-solid fa-box-open" style="font-size:2rem;opacity:0.3;display:block;margin-bottom:8px;color:#888888;"></i>No products found.</div>';
        return;
    }
    var html = '';
    for (var i = 0; i < items.length; i++) {
        var item  = items[i];
        var oos   = parseFloat(item.stock_qty) <= 0;
        var price = parseFloat(item.price).toFixed(2);
        var stock = oos
            ? '<span style="color:#ef4444;font-size:0.62rem;font-weight:700;">Out of stock</span>'
            : '<span style="color:#0abf8a;font-size:0.62rem;font-weight:600;">Stock: ' + parseFloat(item.stock_qty) + ' ' + esc(item.unit) + '</span>';
        var click = oos ? '' : ' onclick="addById(' + item.item_id + ')"';
        html += '<div class="pcard' + (oos ? ' oos' : '') + '"' + click + '>'
            + '<div class="pcard-cat">' + esc(item.category_name) + '</div>'
            + '<div class="pcard-name">' + esc(item.item_name) + '</div>'
            + '<div class="pcard-price">&#8369;' + price + '</div>'
            + '<div class="pcard-stock">' + stock + '</div>'
            + '</div>';
    }
    g.innerHTML = html;
}

function addById(itemId) {
    var id = Number(itemId), item = null;
    for (var i = 0; i < allItems.length; i++) { if (Number(allItems[i].item_id) === id) { item = allItems[i]; break; } }
    if (!item)                           { _toast('Item not found.', 'error'); return; }
    if (parseFloat(item.stock_qty) <= 0) { _toast(item.item_name + ' is out of stock.', 'warning'); return; }
    addToCart(item);
}
window.addById = addById;

function clearSearch() {
    var si = document.getElementById('itemSearchInput');
    if (si) { si.value = ''; si.focus(); }
    renderGrid(allItems);
}
window.clearSearch = clearSearch;

function bindEvents() {
    var scanEl = document.getElementById('scannerInput');
    scanEl.addEventListener('keydown', function (e) {
        if (e.key !== 'Enter') return;
        e.preventDefault();
        var bc = scanEl.value.trim();
        if (bc) { lookupBarcode(bc); scanEl.value = ''; }
    });
    scanEl.addEventListener('focus', function () {
        document.getElementById('scannerDot').style.background = '#34d399';
        document.getElementById('scannerStatus').textContent   = 'Scanner ready - scan now';
    });
    scanEl.addEventListener('blur', function () {
        document.getElementById('scannerDot').style.background = '#4d6a85';
        document.getElementById('scannerStatus').textContent   = 'Press F2 to focus scanner';
    });

    document.getElementById('itemSearchInput').addEventListener('input', function () {
        clearTimeout(srchTimer);
        var q = this.value.trim().toLowerCase();
        srchTimer = setTimeout(function () {
            if (!q) { renderGrid(allItems); return; }
            var local = allItems.filter(function (i) {
                return i.item_name.toLowerCase().indexOf(q) >= 0
                    || (i.barcode || '').toLowerCase().indexOf(q) >= 0
                    || i.category_name.toLowerCase().indexOf(q) >= 0;
            });
            renderGrid(local);
            fetch('/sharonstore3.0/api/transactions.php?action=search_items&q=' + encodeURIComponent(q), { headers: {'Accept':'application/json'} })
            .then(function (r) { return r.ok ? r.json() : null; })
            .then(function (j) { if (j && j.success) renderGrid(j.items); })
            .catch(function () {});
        }, 280);
    });

    function doClear() {
        if (cart.length && confirm('Clear all items from cart?')) {
            cart = [];
            renderCart();
            saveCart();
            // Reset tendered so next sale starts clean
            document.getElementById('amountTendered').value = '';
            calcChange();
        }
    }
    document.getElementById('btnClearCart').addEventListener('click', doClear);
    document.getElementById('btnClearCartBottom').addEventListener('click', doClear);
    document.getElementById('amountTendered').addEventListener('input', calcChange);
    document.getElementById('btnCheckout').addEventListener('click', completeSale);
    document.getElementById('btnNewSale').addEventListener('click', function () {
        receiptM.hide();
        cart = []; renderCart(); saveCart();
        document.getElementById('amountTendered').value = '';
        calcChange(); updateTxnCode(); loadAllItems(); focusScanner();
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'F2')  { e.preventDefault(); switchTab('barcode'); }
        if (e.key === 'F3')  { e.preventDefault(); switchTab('manual'); }
        if (e.key === 'F10') { e.preventDefault(); var btn = document.getElementById('btnCheckout'); if (!btn.disabled) completeSale(); }
        if (e.key === 'Escape') { if (cart.length && confirm('Clear cart?')) { cart = []; renderCart(); saveCart(); } }
    });
}

function lookupBarcode(barcode) {
    fetch('/sharonstore3.0/api/transactions.php?action=barcode_lookup&barcode=' + encodeURIComponent(barcode), { headers: {'Accept':'application/json'} })
    .then(function (r) { if (r.status === 401) throw new Error('session'); return r.json(); })
    .then(function (j) {
        if (j.out_of_stock) { _toast('Out of stock: ' + j.item.item_name, 'warning'); return; }
        if (!j.success)     { _toast(j.message || 'Item not found.', 'error'); return; }
        addToCart(j.item); focusScanner();
    })
    .catch(function (err) {
        if (err.message === 'session') _toast('Session expired. Please log in.', 'error');
        else _toast('Barcode lookup failed.', 'error');
    });
}

function addToCart(item) {
    var existing = null;
    for (var i = 0; i < cart.length; i++) { if (Number(cart[i].item_id) === Number(item.item_id)) { existing = cart[i]; break; } }
    if (existing) {
        if (existing.quantity >= parseFloat(item.stock_qty)) { _toast('Only ' + item.stock_qty + ' ' + item.unit + ' in stock.', 'warning'); return; }
        existing.quantity++;
    } else {
        var entry = {}; for (var k in item) { entry[k] = item[k]; } entry.quantity = 1; cart.push(entry);
    }
    renderCart(); saveCart(); _toast(item.item_name + ' added.', 'success');
}

function removeFromCart(itemId) {
    var id = Number(itemId);
    cart = cart.filter(function (c) { return Number(c.item_id) !== id; });
    renderCart(); saveCart();
}

function updateQty(itemId, rawQty) {
    var id = Number(itemId), qty = parseFloat(rawQty), item = null;
    for (var i = 0; i < cart.length; i++) { if (Number(cart[i].item_id) === id) { item = cart[i]; break; } }
    if (!item) return;
    if (isNaN(qty) || qty <= 0) { removeFromCart(id); return; }
    var max = parseFloat(item.stock_qty);
    if (qty > max) { _toast('Only ' + max + ' ' + item.unit + ' in stock.', 'warning'); qty = max; }
    item.quantity = qty; renderCart(); saveCart();
}

window.POS = {
    remove:    function (id)    { removeFromCart(id); },
    changeQty: function (id, d) { for (var i = 0; i < cart.length; i++) { if (Number(cart[i].item_id) === Number(id)) { updateQty(id, cart[i].quantity + d); break; } } },
    setQty:    function (id, v) { updateQty(id, parseFloat(v)); }
};

function renderCart() {
    var empty = cart.length === 0;
    document.getElementById('cartEmpty').style.display = empty ? 'flex' : 'none';
    document.getElementById('cartTable').style.display = empty ? 'none' : 'table';
    document.getElementById('cartBadge').textContent   = cart.reduce(function (s, c) { return s + c.quantity; }, 0);
    if (!empty) {
        var rows = '';
        for (var i = 0; i < cart.length; i++) {
            var item = cart[i], price = parseFloat(item.price), qty = parseFloat(item.quantity), sub = (price * qty).toFixed(2);
            rows += '<tr>'
                + '<td><div style="font-size:0.84rem;font-weight:700;color:#2C2D2D;line-height:1.3;">' + esc(item.item_name) + '</div>'
                + '<div style="font-size:0.7rem;color:#555657;margin-top:2px;">&#8369;' + price.toFixed(2) + ' / ' + esc(item.unit) + '</div></td>'
                + '<td style="text-align:center;"><div class="qty-wrap">'
                + '<button class="qty-btn" onclick="POS.changeQty(' + item.item_id + ',-1)"><i class="fa-solid fa-minus"></i></button>'
                + '<input class="qty-inp" type="number" value="' + qty + '" min="1" step="1" onchange="POS.setQty(' + item.item_id + ',this.value)" onclick="this.select()">'
                + '<button class="qty-btn" onclick="POS.changeQty(' + item.item_id + ',1)"><i class="fa-solid fa-plus"></i></button>'
                + '</div></td>'
                + '<td style="text-align:right;color:#4a4b4b;font-weight:600;">&#8369;' + price.toFixed(2) + '</td>'
                + '<td style="text-align:right;color:#0abf8a;font-weight:800;">&#8369;' + sub + '</td>'
                + '<td><button class="rm-btn" onclick="POS.remove(' + item.item_id + ')"><i class="fa-solid fa-xmark"></i></button></td>'
                + '</tr>';
        }
        document.getElementById('cartRows').innerHTML = rows;
    }
    calcTotals();
}

function calcTotals() {
    var total = 0;
    for (var i = 0; i < cart.length; i++) { total += parseFloat(cart[i].price) * parseFloat(cart[i].quantity); }
    var vat = total * VAT_RATE;
    document.getElementById('dispSubtotal').textContent = '\u20b1' + (total - vat).toFixed(2);
    document.getElementById('dispVAT').textContent      = '\u20b1' + vat.toFixed(2);
    document.getElementById('dispTotal').textContent    = '\u20b1' + total.toFixed(2);
    document.getElementById('dispTotal').dataset.total  = total;
    calcChange();
}

function calcChange() {
    var total    = parseFloat(document.getElementById('dispTotal').dataset.total || 0);
    var tendered = parseFloat(document.getElementById('amountTendered').value) || 0;
    var change   = tendered - total;
    var chEl     = document.getElementById('dispChange');
    chEl.textContent = '\u20b1' + Math.max(0, change).toFixed(2);
    chEl.style.color = change >= 0 ? '#0abf8a' : '#ef4444';
    document.getElementById('btnCheckout').disabled = (cart.length === 0 || total <= 0 || tendered < total);
}

function setTendered(amount) {
    // Accumulate — each click ADDS the denomination to the current amount
    // (simulates inserting bills one at a time into the register)
    var inp      = document.getElementById('amountTendered');
    var current  = parseFloat(inp.value) || 0;
    inp.value    = (current + parseFloat(amount)).toFixed(2);
    inp.focus();
    calcChange();
}
window.setTendered = setTendered;

function setExact() {
    // Set tendered to exactly the total due
    var total = parseFloat(document.getElementById('dispTotal').dataset.total || 0);
    var inp   = document.getElementById('amountTendered');
    inp.value = total.toFixed(2);
    inp.focus();
    calcChange();
}
window.setExact = setExact;

function clearTendered() {
    // Reset tendered to 0 so next denomination click starts fresh
    var inp   = document.getElementById('amountTendered');
    inp.value = '';
    inp.focus();
    calcChange();
}
window.clearTendered = clearTendered;

function updateTxnCode() {
    var n = new Date();
    var ymd = n.getFullYear() + String(n.getMonth()+1).padStart(2,'0') + String(n.getDate()).padStart(2,'0');
    document.getElementById('txnCodePreview').textContent = 'TXN-' + ymd + '-XXXX';
}

function completeSale() {
    if (!cart.length) { _toast('Cart is empty.', 'warning'); return; }
    var total    = parseFloat(document.getElementById('dispTotal').dataset.total || 0);
    var tendered = parseFloat(document.getElementById('amountTendered').value) || 0;
    if (tendered < total) { _toast('Amount tendered is not enough.', 'warning'); return; }
    var btn = document.getElementById('btnCheckout');
    btn.disabled  = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Processing...';
    fetch('/sharonstore3.0/api/transactions.php', {
        method: 'POST',
        headers: {'Content-Type':'application/json','Accept':'application/json'},
        body: JSON.stringify({ action:'complete_sale', amount_tendered:tendered,
            items: cart.map(function(c){ return {item_id:c.item_id, quantity:c.quantity, unit_price:parseFloat(c.price)}; }) })
    })
    .then(function (r) { if (r.status === 401) throw new Error('session'); return r.json(); })
    .then(function (j) { if (j.success) { showReceipt(j); loadDailySummary(); } else { _toast(j.message || 'Sale failed.', 'error'); } })
    .catch(function (err) { if (err.message === 'session') _toast('Session expired.', 'error'); else _toast('Connection error. Try again.', 'error'); })
    .finally(function () {
        btn.disabled  = false;
        btn.innerHTML = '<i class="fa-solid fa-check-circle"></i> Complete Sale <kbd class="pos-kbd">F10</kbd>';
    });
}

// ── Receipt: 58mm thermal, all-black, 5mm side margins ────────
function showReceipt(data) {
    var now     = new Date();   // real-time local clock when receipt opens
    var dateStr = now.toLocaleDateString('en-PH', {weekday:'short', year:'numeric', month:'short', day:'numeric'});
    var timeStr = now.toLocaleTimeString('en-PH', {hour:'2-digit', minute:'2-digit', second:'2-digit'});


    // 32-char separator fits 48mm usable width (58mm - 5mm margins each side)
    var SEP  = '--------------------------------';
    var SEP2 = '================================';

    // All styles use color:#000 — no greys, no greens — thermal prints black only
    var S  = 'color:#000;font-size:9pt;font-family:Courier New,Courier,monospace;';
    var SB = 'color:#000;font-size:9pt;font-weight:700;font-family:Courier New,Courier,monospace;';
    var SM = 'color:#000;font-size:8pt;font-family:Courier New,Courier,monospace;';

    var rows = '';
    for (var i = 0; i < data.items.length; i++) {
        var it = data.items[i];
        rows += '<tr>'
            + '<td colspan="2" style="' + SB + 'padding:3px 0 0;word-break:break-word;">' + esc(it.item_name) + '</td>'
            + '</tr><tr>'
            + '<td style="' + SM + 'padding:0 0 5px 0;">' + it.quantity + ' x &#8369;' + parseFloat(it.unit_price).toFixed(2) + '</td>'
            + '<td style="' + SB + 'text-align:right;padding:0 0 5px;">&#8369;' + parseFloat(it.subtotal).toFixed(2) + '</td>'
            + '</tr>';
    }

    var html = ''
        + '<div style="text-align:center;margin-bottom:8px;">'
        +   '<div style="' + SB + 'font-size:13pt;letter-spacing:1px;">SHARON STORE</div>'
        +   '<div style="' + SM + '">Pampang Public Market</div>'
        +   '<div style="' + SM + '">Angeles City, Pampanga</div>'
        +   '<div style="' + SM + '">Tel: (045) 123-4567</div>'
        + '</div>'
        + '<div style="' + SM + 'text-align:center;margin:4px 0;">' + SEP + '</div>'
        + '<div style="' + S + 'margin-bottom:4px;">'
        +   '<div style="display:flex;justify-content:space-between;"><span>TXN#</span><span style="font-weight:700;">' + esc(data.transaction_code) + '</span></div>'
        +   '<div style="display:flex;justify-content:space-between;"><span>Date</span><span>' + dateStr + '</span></div>'
        +   '<div style="display:flex;justify-content:space-between;"><span>Time</span><span>' + timeStr + '</span></div>'
        +   '<div style="display:flex;justify-content:space-between;"><span>Cashier</span><span>' + esc(data.cashier_name) + '</span></div>'
        + '</div>'
        + '<div style="' + SM + 'text-align:center;margin:4px 0;">' + SEP + '</div>'
        + '<table style="width:100%;border-collapse:collapse;' + S + 'margin-bottom:4px;">'
        +   '<thead><tr>'
        +     '<th style="' + SB + 'text-align:left;padding-bottom:3px;font-size:8pt;">ITEM</th>'
        +     '<th style="' + SB + 'text-align:right;padding-bottom:3px;font-size:8pt;">AMT</th>'
        +   '</tr></thead>'
        +   '<tbody>' + rows + '</tbody>'
        + '</table>'
        + '<div style="' + SM + 'text-align:center;margin:4px 0;">' + SEP2 + '</div>'
        + '<div style="' + S + '">'
        +   '<div style="display:flex;justify-content:space-between;margin-bottom:2px;"><span>Subtotal (VAT excl.)</span><span>&#8369;' + (parseFloat(data.subtotal)*(100/112)).toFixed(2) + '</span></div>'
        +   '<div style="display:flex;justify-content:space-between;margin-bottom:5px;"><span>VAT 12%</span><span>&#8369;' + parseFloat(data.vat_amount).toFixed(2) + '</span></div>'
        +   '<div style="display:flex;justify-content:space-between;' + SB + 'font-size:11pt;border-top:2px solid #000;border-bottom:1px solid #000;padding:3px 0;margin-bottom:5px;"><span>TOTAL</span><span>&#8369;' + parseFloat(data.grand_total).toFixed(2) + '</span></div>'
        +   '<div style="display:flex;justify-content:space-between;margin-bottom:2px;"><span>Cash Tendered</span><span>&#8369;' + parseFloat(data.amount_tendered).toFixed(2) + '</span></div>'
        +   '<div style="display:flex;justify-content:space-between;' + SB + 'font-size:10pt;border-top:1px solid #000;padding-top:3px;"><span>CHANGE</span><span>&#8369;' + parseFloat(data.change_amount).toFixed(2) + '</span></div>'
        + '</div>'
        + '<div style="' + SM + 'text-align:center;margin:6px 0 0;">' + SEP + '</div>'
        + '<div style="text-align:center;' + SM + 'margin-top:5px;">'
        +   '<div>Thank you for your purchase!</div>'
        +   '<div style="margin-top:2px;">Please come again.</div>'
        +   '<div style="margin-top:5px;font-size:7pt;">** OFFICIAL RECEIPT **</div>'
        + '</div>';

    document.getElementById('receiptContent').innerHTML = html;
    receiptM.show();
    // Auto-print receipt after a short delay to ensure modal is rendered
    setTimeout(function() { window.print(); }, 500);
}

function loadDailySummary() {
    fetch('/sharonstore3.0/api/transactions.php?action=daily_summary', { headers: {'Accept':'application/json'} })
    .then(function (r) { return r.ok ? r.json() : null; })
    .then(function (j) {
        if (j && j.success) {
            document.getElementById('todayTxCount').textContent = j.transaction_count;
            document.getElementById('todayTxTotal').textContent = j.total_sales_fmt;
        }
    })
    .catch(function () {});
}

function saveCart()    { try { localStorage.setItem('ss_pos_cart', JSON.stringify(cart)); } catch (e) {} }
function restoreCart() { try { var r = localStorage.getItem('ss_pos_cart'); if (r) { cart = JSON.parse(r); renderCart(); } } catch (e) { cart = []; } }
function focusScanner(){ setTimeout(function () { var el = document.getElementById('scannerInput'); if (el) el.focus(); }, 80); }
