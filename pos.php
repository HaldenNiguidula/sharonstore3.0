<?php
// ============================================================
// POS Page � Sharon Store System
// Accessible by both Admin and Cashier
// ============================================================
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/includes/auth_check.php';
requireLogin();

$pageTitle    = 'Point of Sale';
$userFullName = currentUserName();
$userRole     = currentRole();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>POS � Sharon Store</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="/sharonstore3.0/assets/css/style.css?v=14">
<style>
/* ============================================================
   POS-specific styles � Light Theme aligned to system design
   ============================================================ */
*,*::before,*::after{box-sizing:border-box;}
html,body{height:100%;overflow:hidden;}
body{font-family:'Nunito',system-ui,sans-serif;background:#f0f4f8;color:#2C2D2D;}

/* POS body below topbar */
.pos-body{display:flex;flex:1;min-height:0;overflow:hidden;}

/* Left panel � cart side */
.pos-left{
    display:flex;flex-direction:column;
    flex:1;min-width:0;
    background:#f0f4f8;
    border-right:1px solid #e2e8f0;
    overflow:hidden;
}

/* Tab bar */
.pos-tabs{
    flex-shrink:0;
    display:flex;gap:0;
    background:#ffffff;
    border-bottom:1px solid #e2e8f0;
    padding:0 16px;
}
.pos-tab{
    padding:11px 16px;
    font-size:0.82rem;font-weight:700;
    border:none;background:transparent;
    color:#888888;
    border-bottom:2px solid transparent;
    cursor:pointer;transition:all 0.18s;
    font-family:'Nunito',sans-serif;
    white-space:nowrap;
}
.pos-tab.active{color:#0abf8a;border-bottom-color:#0abf8a;}
.pos-tab:hover:not(.active){color:#2C2D2D;}

/* Input panels */
.pos-input-panel{
    flex-shrink:0;
    padding:12px 16px;
    background:#ffffff;
    border-bottom:1px solid #e2e8f0;
}

/* Manual search panel � scrollable, never clips content */
#panelManual{
    max-height:55vh;   /* taller so more cards are visible */
    overflow:visible;  /* let the grid handle its own scroll */
    display:flex;
    flex-direction:column;
    padding:12px 16px;
}
#panelManual > .d-flex{ flex-shrink:0; }  /* search bar row stays fixed */
.product-grid{
    display:grid;
    grid-template-columns:repeat(auto-fill,minmax(120px,1fr));
    gap:8px;
    flex:1;
    overflow-y:auto;
    overflow-x:hidden;
    min-height:100px;
    padding-right:4px;
    margin-top:10px;
    align-content:start;   /* cards stick to top, no stretching */
}
/* Product cards — fixed height so all rows are uniform */
.pcard{
    display:flex;
    flex-direction:column;
    justify-content:space-between;
    background:#ffffff;
    border:1.5px solid #e2e8f0;
    border-radius:10px;
    padding:10px 8px;
    cursor:pointer;
    text-align:center;
    user-select:none;
    height:108px;          /* fixed height = all cards same size */
    transition:background 0.16s, border-color 0.16s, box-shadow 0.16s;
    overflow:hidden;
}
.pcard:hover{
    background:#f0fdf8;
    border-color:#0abf8a;
    box-shadow:0 3px 12px rgba(10,191,138,0.14);
    transform:translateY(-1px);
}
.pcard.oos{opacity:0.4;cursor:not-allowed;pointer-events:none;}
.pcard-cat  {font-size:0.55rem;color:#888888;overflow:hidden;white-space:nowrap;text-overflow:ellipsis;text-transform:uppercase;letter-spacing:0.3px;flex-shrink:0;}
.pcard-name {font-size:0.76rem;font-weight:700;color:#2C2D2D;line-height:1.3;
             display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;
             overflow:hidden;flex:1;margin:3px 0;}
.pcard-price{font-size:0.85rem;font-weight:800;color:#0abf8a;flex-shrink:0;}
.pcard-stock{font-size:0.58rem;color:#888888;flex-shrink:0;}


/* Cart header */
.pos-cart-header{
    flex-shrink:0;
    display:flex;align-items:center;justify-content:space-between;
    padding:10px 16px;
    background:#ffffff;
    border-bottom:1px solid #e2e8f0;
}

/* Cart body */
.pos-cart-body{
    flex:1 1 0;
    overflow-y:auto;
    overflow-x:hidden;
    min-height:120px;
    background:#ffffff;
}

/* Cart empty state */
.pos-cart-empty{
    display:flex;flex-direction:column;
    align-items:center;justify-content:center;
    height:100%;min-height:160px;
    text-align:center;padding:24px;
}

/* Cart table */
.pos-cart-table{
    width:100%;border-collapse:collapse;
    font-size:0.845rem;
}
.pos-cart-table thead th{
    padding:9px 12px;
    background:#f8fafc;
    color:#4a4b4b;font-size:0.7rem;font-weight:700;
    text-transform:uppercase;letter-spacing:0.05em;
    border-bottom:2px solid #e2e8f0;
    position:sticky;top:0;z-index:2;
}
.pos-cart-table tbody td{
    padding:10px 12px;
    color:#2C2D2D;
    border-bottom:1px solid #f1f5f9;
    vertical-align:middle;
}
.pos-cart-table tbody tr:last-child td{border-bottom:none;}
.pos-cart-table tbody tr:hover td{background:#f0fdf8;}

/* Qty controls */
.qty-wrap{display:flex;align-items:center;gap:4px;justify-content:center;}
.qty-btn{
    width:26px;height:26px;flex-shrink:0;
    background:#f1f5f9;
    border:1px solid #e2e8f0;
    border-radius:6px;color:#4a4b4b;
    cursor:pointer;font-size:0.72rem;
    display:flex;align-items:center;justify-content:center;
    padding:0;transition:background 0.15s;
}
.qty-btn:hover{background:#e2e8f0;color:#2C2D2D;}
.qty-inp{
    width:48px;height:26px;text-align:center;
    background:#f8fafc;
    border:1px solid #e2e8f0;
    border-radius:6px;color:#2C2D2D;
    font-size:0.82rem;font-weight:700;
    padding:0 2px;font-family:'Nunito',sans-serif;
}
.qty-inp:focus{outline:none;border-color:#0abf8a;box-shadow:0 0 0 2px rgba(10,191,138,0.15);}
.rm-btn{
    width:28px;height:28px;
    background:#fff5f5;
    border:1px solid #fecaca;
    border-radius:6px;color:#ef4444;
    cursor:pointer;font-size:0.78rem;
    display:flex;align-items:center;justify-content:center;
    padding:0;transition:background 0.15s;
}
.rm-btn:hover{background:#fee2e2;color:#dc2626;}

/* Right panel � checkout */
.pos-right{
    width:340px;min-width:280px;max-width:380px;
    flex-shrink:0;
    display:flex;flex-direction:column;
    background:#ffffff;
    border-left:1px solid #e2e8f0;
    box-shadow:-2px 0 10px rgba(0,0,0,0.05);
    overflow-y:auto;overflow-x:hidden;
}
.pos-right-section{
    flex-shrink:0;padding:14px 16px;
    border-bottom:1px solid #e2e8f0;
}
.pos-label{
    font-size:0.68rem;font-weight:700;
    color:#888888;text-transform:uppercase;
    letter-spacing:0.06em;margin-bottom:6px;display:block;
}
.pos-txn-code{font-size:0.85rem;font-weight:800;color:#0abf8a;font-family:'Roboto Mono',monospace;}
.pos-total-row{display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;}
.pos-total-label{font-size:0.845rem;color:#4a4b4b;font-weight:500;}
.pos-total-val{font-size:0.9rem;font-weight:700;color:#2C2D2D;}
.pos-grand-label{font-size:0.9rem;font-weight:800;color:#2C2D2D;text-transform:uppercase;}
.pos-grand-val{font-size:1.65rem;font-weight:900;color:#0abf8a;letter-spacing:-0.5px;}
.pos-tendered-inp{
    width:100%;height:54px;padding:0 14px;
    background:#f8fafc;
    border:2px solid #e2e8f0;
    border-radius:10px;color:#2C2D2D;
    font-size:1.4rem;font-weight:800;text-align:center;
    font-family:'Nunito',sans-serif;
    outline:none;transition:border-color 0.2s;
}
.pos-tendered-inp:focus{border-color:#0abf8a;box-shadow:0 0 0 3px rgba(10,191,138,0.15);}
.pos-quick-btns{display:flex;gap:5px;flex-wrap:wrap;margin-top:8px;}
.pos-qbtn{
    flex:1;min-width:42px;padding:7px 4px;
    background:#f8fafc;
    border:1.5px solid #e2e8f0;
    border-radius:7px;color:#2C2D2D;
    font-size:0.76rem;font-weight:700;
    cursor:pointer;text-align:center;
    transition:all 0.15s;
    font-family:'Nunito',sans-serif;
}
.pos-qbtn:hover{background:#e6faf4;border-color:#0abf8a;color:#0abf8a;}
.pos-change-label{font-size:0.88rem;color:#089970;font-weight:700;}
.pos-change-val{font-size:1.3rem;font-weight:900;color:#0abf8a;}
.pos-btn-checkout{
    width:100%;height:52px;
    background:linear-gradient(135deg,#0abf8a,#089970);
    border:none;border-radius:10px;color:#fff;
    font-size:0.95rem;font-weight:800;
    font-family:'Nunito',sans-serif;cursor:pointer;
    display:flex;align-items:center;justify-content:center;gap:8px;
    transition:all 0.2s;
    box-shadow:0 4px 14px rgba(10,191,138,0.35);
}
.pos-btn-checkout:hover:not(:disabled){opacity:0.92;transform:translateY(-1px);box-shadow:0 6px 20px rgba(10,191,138,0.45);}
.pos-btn-checkout:disabled{opacity:0.38;cursor:not-allowed;transform:none;box-shadow:none;}
.pos-btn-clear{
    width:100%;height:36px;margin-top:6px;
    background:#f8fafc;
    border:1.5px solid #e2e8f0;
    border-radius:8px;color:#4a4b4b;
    font-size:0.8rem;font-weight:700;
    font-family:'Nunito',sans-serif;cursor:pointer;
    display:flex;align-items:center;justify-content:center;gap:6px;
    transition:all 0.15s;
}
.pos-btn-clear:hover{background:#fee2e2;border-color:#fecaca;color:#ef4444;}
.pos-summary-nums{display:flex;justify-content:space-around;margin-top:4px;}
.pos-summary-num{font-size:1.1rem;font-weight:800;color:#2C2D2D;text-align:center;}
.pos-summary-lbl{font-size:0.65rem;color:#888888;text-align:center;}
.pos-hints{font-size:0.65rem;color:#888888;line-height:1.8;}
.pos-kbd{
    font-size:0.58rem;padding:1px 5px;
    background:#f1f5f9;
    border:1px solid #e2e8f0;
    border-radius:3px;color:#4a4b4b;
}

/* Receipt modal � 58mm thermal receipt width */
.receipt-modal .modal-dialog{
    max-width: calc(58mm + 32px) !important;  /* 58mm content + padding */
    width:     calc(58mm + 32px) !important;
    margin-left: auto !important;
    margin-right: auto !important;
}
.receipt-modal .modal-content{
    background:#fff!important;color:#111!important;
    border-radius:10px!important;
    width: 100% !important;
}
.receipt-modal .modal-header{
    border-bottom:1px solid #e5e7eb!important;
    padding:10px 14px!important;
}
.receipt-modal .modal-title{
    color:#111!important;font-weight:800!important;font-size:0.88rem!important;
}
.receipt-modal .modal-footer{
    background:#f9fafb!important;border-top:1px solid #e5e7eb!important;
    padding:10px 14px!important;
}
#receiptContent{
    width: 58mm !important;
    min-width: 58mm !important;
    max-width: 58mm !important;
    margin: 0 auto !important;
    font-family:'Courier New',Courier,monospace !important;
    font-size: 9pt !important;
    line-height: 1.4 !important;
    color: #000 !important;          /* thermal: always black */
    word-wrap: break-word !important;
    overflow-wrap: break-word !important;
    white-space: normal !important;
    padding: 10px 5mm !important;    /* 5mm side margins inside 58mm roll */
}
/* Force every child element inside receipt to black for thermal */
#receiptContent * {
    color: #000 !important;
}

/* Print � exact 58mm thermal roll */
@media print{
    body * { visibility: hidden !important; }
    #receiptContent, #receiptContent * { visibility: visible !important; }
    #receiptContent {
        position: fixed !important;
        top: 0 !important;
        left: 0 !important;
        width: 58mm !important;
        min-width: 58mm !important;
        max-width: 58mm !important;
        padding: 4mm !important;
        margin: 0 !important;
        font-family: 'Courier New', Courier, monospace !important;
        font-size: 8pt !important;
        line-height: 1.35 !important;
        color: #000 !important;
        background: #fff !important;
        border: none !important;
        box-shadow: none !important;
        overflow: visible !important;
        max-height: none !important;
    }
    @page {
        size: 58mm auto;   /* 58mm wide, auto-height (continuous roll) */
        margin: 0;
    }
}

/* -- POS Responsive ----------------------------------------- */
@media (min-width: 1400px) {
    .pos-right { width: 400px; max-width: 400px; }
    .product-grid { grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); }
    .pos-grand-val { font-size: 1.9rem; }
}

/* Small desktop / tablet landscape (992-1199px) */
@media (max-width: 1199px) and (min-width: 992px) {
    .pos-right { width: 300px; min-width: 280px; }
    .pos-grand-val { font-size: 1.45rem; }
    .product-grid { grid-template-columns: repeat(auto-fill, minmax(130px, 1fr)); }
    #panelManual { max-height: 38vh; }
}

/* Tablet portrait (768-991px): side-by-side but compressed */
@media (max-width: 991px) and (min-width: 768px) {
    .pos-topbar { height: 50px; padding: 0 14px; }
    .pos-right { width: 280px; min-width: 260px; }
    .pos-grand-val { font-size: 1.35rem; }
    .pos-tendered-inp { height: 46px; font-size: 1.2rem; }
    .product-grid { grid-template-columns: repeat(auto-fill, minmax(120px, 1fr)); }
    #panelManual { max-height: 36vh; }
    .pos-right-section { padding: 10px 13px; }
    .pcard { padding: 8px 6px; }
    .pcard-name { font-size: 0.72rem; }
    .pcard-price { font-size: 0.8rem; }
}

/* Mobile (< 768px): stack vertically */
@media (max-width: 767px) {
    /* Full-height body becomes scrollable column */
    .pos-body {
        flex-direction: column;
        overflow-y: auto;
        overflow-x: hidden;
    }

    /* Left panel (cart): natural height, no overflow:hidden */
    .pos-left {
        flex: none;
        border-right: none;
        border-bottom: 1px solid #e2e8f0;
        overflow: visible;
        min-height: 0;
    }

    /* Manual panel: shorter on mobile */
    #panelManual { max-height: 220px; }
    .product-grid {
        grid-template-columns: repeat(auto-fill, minmax(110px, 1fr));
        max-height: 160px;
    }

    /* Cart body: fixed height, scrollable */
    .pos-cart-body {
        flex: none;
        height: 240px;
        min-height: 0;
    }

    /* Right panel (checkout): full width below */
    .pos-right {
        width: 100%;
        max-width: 100%;
        min-width: 0;
        flex: none;
        overflow-y: visible;
    }
    .pos-right-section { padding: 10px 14px; }

    /* Compact topbar */
    .pos-topbar { height: 48px; padding: 0 12px; gap: 8px; }
    .pos-topbar-brand { font-size: 0.82rem; }
    .pos-topbar-brand-icon { width: 26px; height: 26px; font-size: 11px; }

    /* Smaller checkout numbers */
    .pos-grand-val { font-size: 1.35rem; }
    .pos-tendered-inp { height: 46px; font-size: 1.2rem; }
    .pos-change-val { font-size: 1.1rem; }
    .pos-btn-checkout { height: 46px; font-size: 0.88rem; }

    /* Quick tender buttons: 3 per row */
    .pos-qbtn { min-width: 36px; font-size: 0.72rem; padding: 5px 3px; }

    /* Product cards smaller */
    .pcard { padding: 7px 5px; }
    .pcard-name { font-size: 0.7rem; min-height: 1.8em; }
    .pcard-price { font-size: 0.78rem; }
}

/* Small phone (< 576px) */
@media (max-width: 575px) {
    .pos-topbar-user { display: none; } /* hide user name, save space */
    .pos-hints { display: none; }       /* hide keyboard shortcuts */
    .product-grid {
        grid-template-columns: repeat(auto-fill, minmax(95px, 1fr));
    }
    .pos-cart-body { height: 200px; }
    #panelManual { max-height: 200px; }
    .pos-grand-val { font-size: 1.2rem; }
    .pos-tendered-inp { height: 42px; font-size: 1.1rem; }

    /* Cart table columns: hide price column on tiny screens */
    .pos-cart-table th:nth-child(3),
    .pos-cart-table td:nth-child(3) { display: none; }
}

/* Extra small (< 375px) */
@media (max-width: 374px) {
    .pos-grand-val { font-size: 1.05rem; }
    .pos-quick-btns { gap: 3px; }
    .pos-qbtn { font-size: 0.68rem; padding: 4px 2px; min-width: 30px; }
    .product-grid { grid-template-columns: repeat(3, 1fr); }
}

</style>
</head>
<body>

<div class="app-wrapper" style="height:100vh;max-height:100vh;overflow:hidden;">
    <?php require_once __DIR__ . '/includes/sidebar.php'; ?>

    <div class="main-content" style="padding:0;display:flex;flex-direction:column;overflow:hidden;height:100%;">

        <!-- Shared topbar (same as all other pages) -->
        <?php require_once __DIR__ . '/includes/header.php'; ?>

        <!-- POS Body -->
        <div class="pos-body" style="flex:1;min-height:0;">

            <!-- --- LEFT: Cart Panel --- -->
            <div class="pos-left">

                <!-- Tabs -->
                <div class="pos-tabs">
                    <button class="pos-tab active" id="tabBarcode" onclick="switchTab('barcode')">
                        <i class="fa-solid fa-barcode me-1"></i>Barcode Scan
                    </button>
                    <button class="pos-tab" id="tabManual" onclick="switchTab('manual')">
                        <i class="fa-solid fa-magnifying-glass me-1"></i>Manual Search
                        <kbd class="pos-kbd ms-1">F3</kbd>
                    </button>
                </div>

                <!-- Barcode Panel -->
                <div id="panelBarcode" class="pos-input-panel">
                    <div style="display:flex;gap:8px;align-items:center;">
                        <div style="position:relative;flex:1;">
                            <i class="fa-solid fa-barcode"
                               style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#0abf8a;pointer-events:none;"></i>
                            <input type="text" id="scannerInput" class="form-control"
                                   style="padding-left:36px;font-size:0.95rem;font-weight:600;"
                                   placeholder="Scan barcode or type and press Enter�"
                                   autocomplete="off" autocorrect="off" spellcheck="false">
                        </div>
                        <button class="btn btn-secondary"
                                onclick="document.getElementById('scannerInput').focus()"
                                title="Focus scanner (F2)">
                            <i class="fa-solid fa-crosshairs"></i>
                        </button>
                    </div>
                    <div style="display:flex;align-items:center;gap:7px;margin-top:8px;">
                        <div id="scannerDot" style="width:8px;height:8px;border-radius:50%;background:#cbd5e1;transition:background 0.2s;flex-shrink:0;"></div>
                        <span id="scannerStatus" style="font-size:0.72rem;color:#888888;">
                            Press <kbd class="pos-kbd">F2</kbd> to focus scanner
                        </span>
                    </div>
                </div>

                <!-- Manual Search Panel -->
                <div id="panelManual" style="display:none; background:#ffffff; border-bottom:1px solid #e2e8f0;">

                    <div style="display:flex;gap:8px;align-items:center;">
                        <div style="position:relative;flex:1;">
                            <i class="fa-solid fa-search"
                               style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#888888;pointer-events:none;"></i>
                            <input type="text" id="itemSearchInput" class="form-control"
                                   style="padding-left:36px;"
                                   placeholder="Search by product name, barcode, or category�"
                                   autocomplete="off">
                        </div>
                        <button class="btn btn-secondary" onclick="clearSearch()" title="Clear search">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                    <!-- Product grid � items rendered here by JS -->
                    <div id="itemGrid" class="product-grid">
                        <div style="grid-column:1/-1;text-align:center;padding:20px;color:#888888;">
                            <div class="loading-spinner" style="margin:0 auto 8px;"></div>
                            Loading products�
                        </div>
                    </div>
                </div>

                <!-- Cart Header -->
                <div class="pos-cart-header">
                    <div style="display:flex;align-items:center;gap:8px;">
                        <i class="fa-solid fa-cart-shopping" style="color:#0abf8a;"></i>
                        <span style="font-weight:700;color:#2C2D2D;font-size:0.9rem;">Cart</span>
                        <span id="cartBadge" class="badge badge-emerald">0</span>
                    </div>
                    <button class="btn btn-sm btn-secondary" id="btnClearCart">
                        <i class="fa-solid fa-trash me-1"></i>Clear All
                    </button>
                </div>

                <!-- Cart Body (scrollable) -->
                <div class="pos-cart-body">

                    <!-- Empty state -->
                    <div id="cartEmpty" style="display:flex;flex-direction:column;
                        align-items:center;justify-content:center;
                        min-height:160px;text-align:center;padding:28px;">
                        <i class="fa-solid fa-barcode"
                           style="font-size:2.8rem;opacity:0.18;margin-bottom:12px;color:#0abf8a;"></i>
                        <div style="font-size:0.95rem;font-weight:700;color:#2C2D2D;margin-bottom:4px;">
                            Cart is empty
                        </div>
                        <div style="font-size:0.78rem;color:#888888;">
                            Scan a barcode or use Manual Search
                        </div>
                    </div>

                    <!-- Cart table � each row is a proper <tr>, no overlap possible -->
                    <table id="cartTable" class="pos-cart-table" style="display:none;">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th style="width:130px;text-align:center;">Qty</th>
                                <th style="text-align:right;">Price</th>
                                <th style="text-align:right;">Subtotal</th>
                                <th style="width:40px;"></th>
                            </tr>
                        </thead>
                        <tbody id="cartRows"></tbody>
                    </table>

                </div><!-- /cart-body -->
            </div><!-- /pos-left -->

            <!-- --- RIGHT: Checkout Panel --- -->
            <div class="pos-right">

                <!-- TXN code -->
                <div class="pos-right-section">
                    <span class="pos-label"><i class="fa-solid fa-receipt me-1"></i>Transaction</span>
                    <div class="pos-txn-code" id="txnCodePreview">TXN---------</div>
                </div>

                <!-- Totals -->
                <div class="pos-right-section">
                    <div class="pos-total-row">
                        <span class="pos-total-label">Subtotal (excl. VAT)</span>
                        <span class="pos-total-val" id="dispSubtotal">&#8369;0.00</span>
                    </div>
                    <div class="pos-total-row">
                        <span class="pos-total-label">VAT (12% incl.)</span>
                        <span class="pos-total-val" id="dispVAT" style="color:#888888;">&#8369;0.00</span>
                    </div>
                    <div style="border-top:1.5px solid #e2e8f0;padding-top:10px;margin-top:4px;
                                display:flex;justify-content:space-between;align-items:center;">
                        <span class="pos-grand-label">TOTAL</span>
                        <span class="pos-grand-val" id="dispTotal" data-total="0">&#8369;0.00</span>
                    </div>
                </div>

                <!-- Tendered -->
                <div class="pos-right-section">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;">
                        <span class="pos-label" style="margin-bottom:0;">Amount Tendered (&#8369;)</span>
                        <button onclick="clearTendered()" title="Reset to zero"
                                style="font-size:0.7rem;font-weight:700;padding:3px 9px;border-radius:6px;
                                       background:#fff5f5;border:1.5px solid #fecaca;color:#ef4444;cursor:pointer;
                                       transition:all 0.15s;" onmouseover="this.style.background='#fee2e2'" onmouseout="this.style.background='#fff5f5'">
                            ? Clear
                        </button>
                    </div>
                    <input type="number" id="amountTendered" class="pos-tendered-inp"
                           placeholder="0.00" min="0" step="0.01">
                    <!-- Quick denomination buttons � each click ADDS to the tendered amount -->
                    <div style="margin-top:7px;margin-bottom:4px;">
                        <span style="font-size:0.63rem;font-weight:700;color:#888888;text-transform:uppercase;letter-spacing:0.5px;">
                            Quick Bills � tap to add
                        </span>
                    </div>
                    <div class="pos-quick-btns">
                        <button class="pos-qbtn" onclick="setTendered(20)">&#8369;20</button>
                        <button class="pos-qbtn" onclick="setTendered(50)">&#8369;50</button>
                        <button class="pos-qbtn" onclick="setTendered(100)">&#8369;100</button>
                        <button class="pos-qbtn" onclick="setTendered(200)">&#8369;200</button>
                        <button class="pos-qbtn" onclick="setTendered(500)">&#8369;500</button>
                        <button class="pos-qbtn" id="btnExact" onclick="setExact()"
                                style="flex:2;background:#e6faf4;border-color:#b2edd8;color:#0abf8a;font-weight:800;">
                            ? Exact
                        </button>
                    </div>
                </div>


                <!-- Change -->
                <div class="pos-right-section" style="background:#f0fdf8;">
                    <div style="display:flex;justify-content:space-between;align-items:center;">
                        <span class="pos-change-label">Change</span>
                        <span class="pos-change-val" id="dispChange">&#8369;0.00</span>
                    </div>
                </div>

                <!-- Actions -->
                <div class="pos-right-section">
                    <button id="btnCheckout" class="pos-btn-checkout" disabled>
                        <i class="fa-solid fa-check-circle"></i>
                        Complete Sale
                        <kbd class="pos-kbd">F10</kbd>
                    </button>
                    <button class="pos-btn-clear" id="btnClearCartBottom">
                        <i class="fa-solid fa-xmark"></i>Clear Cart
                        <kbd class="pos-kbd">Esc</kbd>
                    </button>
                </div>

                <!-- Daily Summary -->
                <div class="pos-right-section">
                    <span class="pos-label"><i class="fa-solid fa-calendar-day me-1"></i>Today's Summary</span>
                    <div class="pos-summary-nums">
                        <div>
                            <div class="pos-summary-num" id="todayTxCount">�</div>
                            <div class="pos-summary-lbl">Transactions</div>
                        </div>
                        <div style="border-left:1px solid #e2e8f0;"></div>
                        <div>
                            <div class="pos-summary-num" id="todayTxTotal" style="color:#0abf8a;">�</div>
                            <div class="pos-summary-lbl">Total Sales</div>
                        </div>
                    </div>
                </div>

                <!-- Keyboard shortcuts -->
                <div class="pos-right-section" style="border-bottom:none;">
                    <div class="pos-hints">
                        <kbd class="pos-kbd">F2</kbd> Focus scanner &nbsp;
                        <kbd class="pos-kbd">F3</kbd> Manual search &nbsp;
                        <kbd class="pos-kbd">F10</kbd> Complete sale &nbsp;
                        <kbd class="pos-kbd">Esc</kbd> Clear cart
                    </div>
                </div>

            </div><!-- /pos-right -->
        </div><!-- /pos-body -->
    </div><!-- /main-content -->
</div><!-- /app-wrapper -->

<!-- Receipt Modal -->
<div class="modal fade receipt-modal" id="receiptModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fa-solid fa-receipt me-2" style="color:#0abf8a;"></i>Sale Receipt
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <div id="receiptContent"
                     style="max-height:65vh;overflow-y:auto;">
                </div>
            </div>
            <div class="modal-footer gap-2">
                <button class="btn btn-secondary btn-sm" onclick="window.print()">
                    <i class="fa-solid fa-print me-1"></i>Print
                </button>
                <button class="btn btn-primary btn-sm flex-fill" id="btnNewSale">
                    <i class="fa-solid fa-plus me-1"></i>New Sale
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Toast container -->
<div class="toast-container position-fixed bottom-0 end-0 p-3" id="toastContainer"></div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
<script src="/sharonstore3.0/assets/js/app.js"></script>
<script src="/sharonstore3.0/assets/js/pos.js"></script>
</body>
</html>
