<?php
session_start();
include "db.php";
include "manager_auth.php";

$totalFasteners = mysqli_fetch_row(mysqli_query($conn,"SELECT COUNT(*) FROM fastener"))[0];
$totalSuppliers = mysqli_fetch_row(mysqli_query($conn,"SELECT COUNT(*) FROM supplier"))[0];
$totalStock     = mysqli_fetch_row(mysqli_query($conn,"SELECT COALESCE(SUM(quantity),0) FROM stock"))[0];
$totalOrders    = mysqli_fetch_row(mysqli_query($conn,"SELECT COUNT(*) FROM orders"))[0];
$lowStockItems  = mysqli_fetch_row(mysqli_query($conn,"SELECT COUNT(*) FROM stock WHERE quantity < 20"))[0];
$pendingOrders  = mysqli_fetch_row(mysqli_query($conn,"SELECT COUNT(*) FROM orders WHERE status='Pending'"))[0];
$deliveredOrders= mysqli_fetch_row(mysqli_query($conn,"SELECT COUNT(*) FROM orders WHERE status='Delivered'"))[0];
$inventoryValue = mysqli_fetch_row(mysqli_query($conn,"SELECT COALESCE(SUM(f.unit_price * s.quantity),0) FROM stock s JOIN fastener f ON s.fastener_id=f.id"))[0];

$monthlyRes = mysqli_query($conn,"
    SELECT DATE_FORMAT(order_date,'%b %Y') AS mo, COUNT(*) AS cnt
    FROM orders
    WHERE order_date >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY YEAR(order_date), MONTH(order_date)
    ORDER BY YEAR(order_date), MONTH(order_date)
");
$monthLabels = []; $monthCounts = [];
while($r = mysqli_fetch_assoc($monthlyRes)) { $monthLabels[] = $r['mo']; $monthCounts[] = $r['cnt']; }

$topRes = mysqli_query($conn,"
    SELECT f.name, s.quantity FROM stock s
    JOIN fastener f ON s.fastener_id=f.id
    ORDER BY s.quantity DESC LIMIT 5
");
$topNames = []; $topQtys = [];
while($r = mysqli_fetch_assoc($topRes)) { $topNames[] = $r['name']; $topQtys[] = $r['quantity']; }

$recentOrders = mysqli_query($conn,"
    SELECT o.order_id, f.name AS fname, s.name AS sname, o.quantity, o.status, o.order_date
    FROM orders o
    JOIN fastener f ON o.fastener_id=f.id
    JOIN supplier s ON o.supplier_id=s.id
    ORDER BY o.order_date DESC LIMIT 5
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manager Dashboard – Bolt Base</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=DM+Mono:wght@500&display=swap" rel="stylesheet">
<style>
:root {
    --bg-from: #0d1f3c;
    --bg-to:   #1a3a6b;
    --accent:  #2d5fa6;
    --text:    #ffffff;
    --muted:   rgba(255,255,255,0.60);
    --gold:    #f5c842;
    --gold-bg: rgba(245,200,66,0.14);
    --gold-br: rgba(245,200,66,0.38);
    --danger:  #e05c5c;
    --success: #4ade80;
    --card-bg: rgba(255,255,255,0.06);
    --card-bdr:rgba(255,255,255,0.12);
    --thead-bg:rgba(0,0,0,0.40);
    --row-bdr: rgba(255,255,255,0.08);
    --row-hover:rgba(255,255,255,0.05);
    --input-bg: rgba(255,255,255,0.08);
    --input-bdr:rgba(255,255,255,0.18);
}
*,
*::before,
*::after {
    box-sizing:border-box;
    margin:0;
    padding:0;
}
body {
    font-family:'DM Sans','Segoe UI',sans-serif;
    background:linear-gradient(135deg,var(--bg-from) 0%,var(--bg-to) 100%);
    color:var(--text);
    min-height:100vh;
    transition:background 0.35s;
}
/* ── Navbar ── */
.navbar {
    display:flex;
    justify-content:space-between;
    align-items:center;
    padding:14px 28px;
    background:rgba(0,0,0,0.45);
    backdrop-filter:blur(16px);
    border-bottom:1px solid rgba(255,255,255,0.10);
    position:sticky;
    top:0;
    z-index:200;
    gap:16px;
}
.logo {
    font-size:18px;
    font-weight:700;
    display:flex;
    align-items:center;
    gap:9px;
    white-space:nowrap;
    letter-spacing:0.5px;
}
.logo-icon {
    width:34px;
    height:34px;
    background:var(--gold-bg);
    border:1.5px solid var(--gold);
    border-radius:9px;
    display:flex;
    align-items:center;
    justify-content:center;
}
.role-chip {
    background:rgba(45,95,166,0.55);
    border:1px solid rgba(100,160,255,0.35);
    color:#93c5fd;
    border-radius:20px;
    padding:4px 12px;
    font-size:11px;
    font-weight:700;
    text-transform:uppercase;
    letter-spacing:1px;
}
.nav-links {
    display:flex;
    gap:2px;
}
.nav-links a {
    color:rgba(255,255,255,0.70);
    padding:6px 12px;
    border-radius:8px;
    text-decoration:none;
    font-size:13px;
    font-weight:500;
    transition:0.2s;
    white-space:nowrap;
}
.nav-links a:hover {
    background:rgba(255,255,255,0.10);
    color:#fff;
}
.nav-links a.active {
    background:rgba(255,255,255,0.14);
    color:#fff;
}
.nav-right {
    display:flex;
    align-items:center;
    gap:8px;
    flex-shrink:0;
}
.pill {
    background:rgba(255,255,255,0.08);
    border:1px solid rgba(255,255,255,0.12);
    border-radius:20px;
    padding:5px 11px;
    font-size:12px;
    display:flex;
    align-items:center;
    gap:5px;
    white-space:nowrap;
}
.pill i {
    font-size:10px;
    opacity:0.65;
}
.icon-btn {
    width:32px;
    height:32px;
    background:rgba(255,255,255,0.08);
    border:1px solid rgba(255,255,255,0.12);
    border-radius:8px;
    cursor:pointer;
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:13px;
    color:var(--text);
    transition:0.2s;
}
.icon-btn:hover {
    background:rgba(255,255,255,0.15);
}
.logout-btn {
    background:var(--gold);
    color:#1a0a00;
    border:none;
    border-radius:8px;
    padding:6px 14px;
    font-size:13px;
    font-weight:700;
    cursor:pointer;
    text-decoration:none;
    transition:0.2s;
    white-space:nowrap;
}
.logout-btn:hover {
    background:#ffe040;
}
/* ── Main ── */
.main {
    padding:28px 28px 60px;
    max-width:1320px;
    margin:0 auto;
}
.page-header {
    display:flex;
    align-items:center;
    justify-content:space-between;
    margin-bottom:24px;
    flex-wrap:wrap;
    gap:12px;
}
.page-header h1 {
    font-size:22px;
    font-weight:700;
    display:flex;
    align-items:center;
    gap:10px;
}
.page-header p {
    font-size:13px;
    color:var(--muted);
    margin-top:3px;
}
.btn-report {
    background:var(--gold);
    color:#1a0a00;
    border:none;
    border-radius:9px;
    padding:10px 18px;
    font-size:13px;
    font-weight:700;
    cursor:pointer;
    text-decoration:none;
    display:inline-flex;
    align-items:center;
    gap:7px;
    transition:0.2s;
}
.btn-report:hover {
    background:#ffe040;
}
/* ── Stats Grid ── */
.stats-grid {
    display:grid;
    grid-template-columns:repeat(auto-fill,minmax(185px,1fr));
    gap:14px;
    margin-bottom:22px;
}
.stat-card {
    background:var(--card-bg);
    border:1px solid var(--card-bdr);
    border-radius:14px;
    padding:20px 18px;
    display:flex;
    flex-direction:column;
    gap:8px;
    transition:transform 0.2s, border-color 0.2s;
}
.stat-card:hover {
    transform:translateY(-2px);
    border-color:rgba(245,200,66,0.30);
}
.stat-icon {
    width:42px;
    height:42px;
    border-radius:11px;
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:16px;
}
.stat-label {
    font-size:11px;
    font-weight:600;
    text-transform:uppercase;
    letter-spacing:0.8px;
    color:var(--muted);
}
.stat-value {
    font-size:28px;
    font-weight:700;
    line-height:1;
}
.stat-sub {
    font-size:11px;
    color:var(--muted);
}
/* ── Quick Reports Row ── */
.reports-row {
    display:grid;
    grid-template-columns:repeat(auto-fill,minmax(205px,1fr));
    gap:12px;
    margin-bottom:22px;
}
.report-btn-card {
    background:var(--card-bg);
    border:1px solid var(--card-bdr);
    border-radius:12px;
    padding:16px 18px;
    text-decoration:none;
    color:#fff;
    display:flex;
    align-items:center;
    gap:12px;
    font-size:13px;
    font-weight:600;
    transition:0.2s;
}
.report-btn-card:hover {
    background:rgba(245,200,66,0.12);
    border-color:rgba(245,200,66,0.38);
}
.report-btn-card i {
    font-size:19px;
    color:var(--gold);
    width:22px;
    text-align:center;
}
/* ── Charts ── */
.charts-row {
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:14px;
    margin-bottom:22px;
}
.chart-card {
    background:var(--card-bg);
    border:1px solid var(--card-bdr);
    border-radius:14px;
    padding:22px 20px;
}
.chart-card h3 {
    font-size:13px;
    font-weight:600;
    margin-bottom:16px;
    color:rgba(255,255,255,0.88);
    display:flex;
    align-items:center;
    gap:8px;
}
.chart-card h3::before {
    content:'';
    width:3px;
    height:14px;
    background:var(--gold);
    border-radius:2px;
    display:inline-block;
}
/* ── Table ── */
.table-card {
    background:var(--card-bg);
    border:1px solid var(--card-bdr);
    border-radius:14px;
    overflow:hidden;
}
.table-card-header {
    padding:16px 20px;
    border-bottom:1px solid var(--card-bdr);
    display:flex;
    align-items:center;
    justify-content:space-between;
}
.table-card-header h3 {
    font-size:13px;
    font-weight:600;
    display:flex;
    align-items:center;
    gap:8px;
}
.table-card-header h3::before {
    content:'';
    width:3px;
    height:14px;
    background:var(--gold);
    border-radius:2px;
}
table {
    width:100%;
    border-collapse:collapse;
    font-size:13px;
}
th {
    padding:11px 16px;
    text-align:left;
    font-size:11px;
    font-weight:700;
    text-transform:uppercase;
    letter-spacing:1px;
    color:rgba(255,255,255,0.80);
    background:var(--thead-bg);
}
td {
    padding:13px 16px;
    border-bottom:1px solid var(--row-bdr);
    color:#fff;
    font-weight:500;
}
tbody tr:last-child td {
    border-bottom:none;
}
tbody tr:hover {
    background:var(--row-hover);
}
.badge {
    display:inline-flex;
    align-items:center;
    gap:4px;
    padding:3px 10px;
    border-radius:999px;
    font-size:11px;
    font-weight:700;
}
.badge.pending {
    background:rgba(251,191,36,0.18);
    border:1px solid rgba(251,191,36,0.40);
    color:#fbbf24;
}
.badge.delivered {
    background:rgba(74,222,128,0.18);
    border:1px solid rgba(74,222,128,0.40);
    color:#4ade80;
}
.view-all {
    font-size:12px;
    color:var(--gold);
    text-decoration:none;
    font-weight:600;
}
.view-all:hover {
    text-decoration:underline;
}
/* ── Section label ── */
.sec-label {
    font-size:11px;
    font-weight:700;
    text-transform:uppercase;
    letter-spacing:1.2px;
    color:var(--muted);
    margin:22px 0 13px;
    display:flex;
    align-items:center;
    gap:8px;
}
.sec-label::before {
    content:'';
    width:16px;
    height:2px;
    background:var(--gold);
    border-radius:2px;
}
:root {
    --bg-from:#dff3ff;
    --bg-to:#b9e3ff;
    --accent:#0b4f8a;
    --text:#071827;
    --muted:#315166;
    --gold:#0b4f8a;
    --gold-bg:#d8efff;
    --gold-br:#000;
    --card-bg:rgba(255,255,255,0.82);
    --card-bdr:#000;
    --thead-bg:#c7e8fb;
    --row-bdr:#000;
    --row-hover:#d5ecff;
    --input-bg:#fff;
    --input-bdr:#000;
}
.navbar,
.pill,
.icon-btn,
.logout-btn,
.btn-report,
.stat-card,
.report-btn-card,
.table-card,
.search-box,
.readonly-notice {
    border-color:#000!important;
}
.navbar {
    background:rgba(223,243,255,0.92);
}
.nav-links a,
.pill,
.report-btn-card,
td,
th {
    color:#071827;
}
.nav-links a:hover,
.nav-links a.active,
.icon-btn:hover,
.logout-btn:hover,
.btn-report:hover,
.report-btn-card:hover,
.view-all:hover {
    background:#073b78!important;
    color:#fff!important;
    border-color:#000!important;
    text-decoration:none;
}
.stat-card:hover,
.table-card:hover {
    background:#073b78!important;
    border-color:#000!important;
    color:#fff;
}
.stat-card:hover *,
.table-card:hover * {
    color:#fff!important;
}
/* Page manager theme overrides */
:root {
    --manager-olive: #636b2f;
    --manager-sage: #bac095;
    --manager-light: #d4de95;
    --manager-dark: #252914;
    --manager-panel: #fbfcf3;
    --manager-panel-soft: #f4f7df;
    --manager-muted: #3f472a;
    --manager-border: #111;
    --bg-from: #eef2cf;
    --bg-to: var(--manager-sage);
    --text: var(--manager-dark);
    --muted: var(--manager-muted);
    --gold: var(--manager-olive);
    --gold-bg: var(--manager-light);
    --gold-br: var(--manager-border);
    --card-bg: var(--manager-panel);
    --card-bdr: var(--manager-border);
    --input-bg: #fff;
    --input-bdr: var(--manager-border);
    --thead-bg: #e9edc6;
    --row-bdr: rgba(17, 17, 17, 0.22);
    --row-hover: #f1f5d6;
}
body {
    background: linear-gradient(135deg, #f7f9e8 0%, #e7edbd 48%, var(--manager-sage) 100%) !important;
    color: var(--manager-dark) !important;
    overflow-x: hidden;
}
.navbar {
    background: linear-gradient(90deg, var(--manager-dark) 0%, var(--manager-olive) 100%) !important;
    border-bottom: 1px solid var(--manager-border) !important;
    box-shadow: 0 8px 22px rgba(61, 65, 39, 0.24);
    justify-content: flex-start !important;
    flex-wrap: nowrap !important;
    gap: 10px !important;
    padding: 10px 18px !important;
    overflow-x: auto;
}
.logo {
    flex: 0 0 auto;
    color: #fff !important;
    font-weight: 800 !important;
}
.navbar .logo,
.navbar .logo *:not(.role-chip):not(.role-chip *) {
    color: #fff !important;
}
.logo-icon,
.page-title-icon,
.report-card-icon,
.stat-icon {
    background: var(--manager-light) !important;
    border-color: var(--manager-border) !important;
    color: #000 !important;
}
.logo-icon i,
.page-title-icon i,
.report-card-icon i,
.stat-icon i,
.table-card-header i,
.btn-export i,
.btn-dl i,
.btn-report i,
.report-btn-card i,
.count-badge i,
.low-badge i,
.pending-badge i,
.deliv-badge i {
    color: #000 !important;
}
.role-chip {
    background: rgba(212, 222, 149, 0.22) !important;
    border-color: var(--manager-border) !important;
    color: #f6ffd5 !important;
}
.nav-links {
    flex: 0 0 auto;
    flex-wrap: nowrap !important;
    gap: 3px !important;
}
.nav-links a {
    color: rgba(255, 255, 255, 0.84) !important;
    border: 1px solid #000 !important;
    padding: 6px 9px !important;
    font-size: 12px !important;
    white-space: nowrap;
}
.nav-links a:hover,
.nav-links a.active {
    background: rgba(212, 222, 149, 0.20) !important;
    color: #fff !important;
}
.nav-right {
    flex: 0 0 auto;
    margin-left: auto;
    gap: 5px !important;
}
.navbar .pill,
.navbar .icon-btn {
    color: #fff !important;
    background: rgba(255, 255, 255, 0.08) !important;
    border-color: var(--manager-border) !important;
}
.main {
    width: min(100%, 1280px) !important;
    padding: 16px clamp(10px, 2vw, 22px) 32px !important;
    overflow-x: hidden;
}
.page-title h1,
.page-header h1,
h1 {
    color: var(--manager-dark) !important;
}
.page-title p,
.page-header p,
.sub,
.sec-label,
.sec-head {
    color: var(--manager-muted) !important;
}
.logout-btn,
.btn-report,
.btn-export,
.btn-dl,
.report-btn-card,
.filter-btn,
button,
input[type="button"],
input[type="submit"],
a[class*="btn"] {
    background: var(--manager-olive) !important;
    border: 1px solid var(--manager-border) !important;
    color: #fff !important;
    font-weight: 700;
}
.logout-btn:hover,
.btn-report:hover,
.btn-export:hover,
.btn-dl:hover,
.report-btn-card:hover,
.filter-btn:hover,
.filter-btn.active,
button:hover,
input[type="button"]:hover,
input[type="submit"]:hover,
a[class*="btn"]:hover {
    background: var(--manager-light) !important;
    color: var(--manager-dark) !important;
    border-color: var(--manager-border) !important;
    box-shadow: 0 5px 14px rgba(99, 107, 47, 0.18) !important;
    text-decoration: none;
}
.stat-card,
.report-btn-card,
.chart-card,
.table-card {
    background: var(--manager-panel) !important;
    border-color: var(--manager-border) !important;
    color: var(--manager-dark) !important;
    box-shadow: 0 8px 18px rgba(61, 65, 39, 0.12);
}
.stat-card:hover,
.report-btn-card:hover,
.chart-card:hover,
.table-card:hover {
    background: var(--manager-panel-soft) !important;
    color: var(--manager-dark) !important;
    box-shadow: 0 12px 26px rgba(61, 65, 39, 0.18);
}
.stat-card {
    position: relative;
    overflow: hidden;
}
.stat-card::before {
    content: '';
    position: absolute;
    top: -22px;
    right: -22px;
    width: 84px;
    height: 84px;
    border-radius: 50%;
    background: var(--manager-olive);
    opacity: 0.28;
    pointer-events: none;
}
.stat-card:nth-child(1)::before {
    background: var(--manager-olive);
}
.stat-card:nth-child(2)::before {
    background: #4d5528;
}
.stat-card:nth-child(3)::before {
    background: var(--manager-sage);
    opacity: 0.42;
}
.stat-card:nth-child(4)::before {
    background: var(--manager-light);
    opacity: 0.48;
}
.stat-card:nth-child(5)::before {
    background: #7a3b24;
    opacity: 0.30;
}
.stat-card:nth-child(6)::before {
    background: var(--manager-dark);
    opacity: 0.26;
}
.stat-card > * {
    position: relative;
    z-index: 1;
}
.stat-value,
.table-card-header h3,
.chart-card h3,
td {
    color: var(--manager-dark) !important;
}
.stat-label,
.stat-sub,
.id-cell {
    color: var(--manager-muted) !important;
}
.table-card-header h3::before,
.chart-card h3::before,
.sec-label::before {
    background: var(--manager-olive) !important;
}
th {
    background: var(--thead-bg) !important;
    color: var(--manager-dark) !important;
}
.badge.pending {
    background: #fff1b8 !important;
    color: #3d2f00 !important;
    border-color: #000 !important;
}
.badge.delivered {
    background: #dceec2 !important;
    color: #20360e !important;
    border-color: #000 !important;
}
/* Manager hover readability fixes */
.stat-card:hover,
.report-card:hover,
.range-card:hover,
.chart-card:hover,
.table-card:hover,
.readonly-notice:hover {
    background: #e4eabd !important;
    color: var(--manager-dark) !important;
    border-color: var(--manager-border) !important;
}
.stat-card:hover *,
.report-card:hover *,
.range-card:hover *,
.chart-card:hover *,
.table-card:hover *,
.readonly-notice:hover * {
    color: var(--manager-dark) !important;
}
tbody tr:hover {
    background: #e4eabd !important;
}
.logout-btn:hover,
.btn-report:hover,
.btn-export:hover,
.btn-dl:hover,
.report-btn-card:hover,
.filter-btn:hover,
.filter-btn.active,
button:hover,
input[type="button"]:hover,
input[type="submit"]:hover,
a[class*="btn"]:hover {
    background: #4d5528 !important;
    color: #fff !important;
    border-color: var(--manager-border) !important;
    box-shadow: 0 5px 14px rgba(37, 41, 20, 0.22) !important;
    text-decoration: none !important;
}
.logout-btn:hover *,
.btn-report:hover *,
.btn-export:hover *,
.btn-dl:hover *,
.report-btn-card:hover *,
.filter-btn:hover *,
.filter-btn.active *,
button:hover *,
input[type="button"]:hover *,
input[type="submit"]:hover *,
a[class*="btn"]:hover * {
    color: #fff !important;
}
/* Embedded dark-mode contrast layer */
/* Final dark-mode contrast layer loaded after page inline styles. */
body.dark {
    --text: #f8fafc !important;
    --muted: #cbd5e1 !important;
    --gold: #facc15 !important;
    --gold-bg: rgba(250, 204, 21, 0.16) !important;
    --gold-br: rgba(250, 204, 21, 0.55) !important;
    --card-bg: #1f2937 !important;
    --card-bdr: #94a3b8 !important;
    --input-bg: #0f172a !important;
    --input-bdr: #94a3b8 !important;
    --thead-bg: #334155 !important;
    --row-bdr: rgba(203, 213, 225, 0.28) !important;
    --row-hover: #334155 !important;
    background: linear-gradient(135deg, #0f172a 0%, #1e293b 52%, #312e81 100%) !important;
    color: #f8fafc !important;
}

body.dark .navbar {
    background: linear-gradient(90deg, #020617 0%, #1e293b 100%) !important;
    border-bottom-color: #64748b !important;
}

body.dark .logo,
body.dark .navbar .logo,
body.dark .navbar a,
body.dark .navbar .pill,
body.dark .navbar .icon-btn,
body.dark .role-chip {
    color: #f8fafc !important;
}

body.dark .nav-links a:hover,
body.dark .nav-links a.active {
    background: rgba(250, 204, 21, 0.18) !important;
    color: #fef9c3 !important;
    border-color: #facc15 !important;
}

body.dark .page-title h1,
body.dark .page-header h1,
body.dark h1,
body.dark h2,
body.dark h3,
body.dark h4,
body.dark .form-card-title,
body.dark .table-card-header h3,
body.dark .chart-card h3,
body.dark .report-card h3,
body.dark .stat-value,
body.dark .name-cell,
body.dark .price-cell,
body.dark td,
body.dark th,
body.dark label,
body.dark .field label {
    color: #f8fafc !important;
}

body.dark .page-title p,
body.dark .page-header p,
body.dark .stat-label,
body.dark .stat-sub,
body.dark .id-cell,
body.dark .desc-cell,
body.dark .sub,
body.dark .sec-label,
body.dark .sec-head,
body.dark .empty-state,
body.dark small {
    color: #cbd5e1 !important;
}

body.dark .table-card,
body.dark .form-card,
body.dark .history-card,
body.dark .stat-card,
body.dark .action-card,
body.dark .report-card,
body.dark .report-btn-card,
body.dark .chart-card,
body.dark .range-card,
body.dark .readonly-notice,
body.dark .alert,
body.dark .datetime-cell {
    background: #1f2937 !important;
    border-color: #94a3b8 !important;
    color: #f8fafc !important;
    box-shadow: 0 10px 24px rgba(2, 6, 23, 0.28) !important;
}

body.dark .table-card *,
body.dark .form-card *,
body.dark .history-card *,
body.dark .stat-card *,
body.dark .action-card *,
body.dark .report-card *,
body.dark .report-btn-card *,
body.dark .chart-card *,
body.dark .range-card *,
body.dark .readonly-notice *,
body.dark .datetime-cell * {
    color: inherit;
}

body.dark thead tr,
body.dark th {
    background: #334155 !important;
    color: #f8fafc !important;
}

body.dark tbody tr:hover,
body.dark .table-card:hover,
body.dark .form-card:hover,
body.dark .history-card:hover,
body.dark .stat-card:hover,
body.dark .action-card:hover,
body.dark .report-card:hover,
body.dark .report-btn-card:hover,
body.dark .chart-card:hover,
body.dark .range-card:hover,
body.dark .readonly-notice:hover {
    background: #334155 !important;
    color: #f8fafc !important;
}

body.dark tbody tr:hover *,
body.dark .table-card:hover *,
body.dark .form-card:hover *,
body.dark .history-card:hover *,
body.dark .stat-card:hover *,
body.dark .action-card:hover *,
body.dark .report-card:hover *,
body.dark .report-btn-card:hover *,
body.dark .chart-card:hover *,
body.dark .range-card:hover *,
body.dark .readonly-notice:hover * {
    color: #f8fafc !important;
}

body.dark input,
body.dark select,
body.dark textarea,
body.dark .search-box,
body.dark .field input,
body.dark .field select,
body.dark .field textarea,
body.dark .file-label {
    background: #0f172a !important;
    border-color: #94a3b8 !important;
    color: #f8fafc !important;
}

body.dark input::placeholder,
body.dark textarea::placeholder,
body.dark .search-box::placeholder {
    color: #94a3b8 !important;
}

body.dark select option {
    background: #0f172a !important;
    color: #f8fafc !important;
}

body.dark .btn-primary,
body.dark .btn-add,
body.dark .btn-export,
body.dark .btn-download,
body.dark .btn-report,
body.dark .btn-dl,
body.dark .logout-btn,
body.dark button,
body.dark input[type="button"],
body.dark input[type="submit"],
body.dark a[class*="btn"] {
    background: #facc15 !important;
    border-color: #fef08a !important;
    color: #111827 !important;
}

body.dark .btn-primary *,
body.dark .btn-add *,
body.dark .btn-export *,
body.dark .btn-download *,
body.dark .btn-report *,
body.dark .btn-dl *,
body.dark .logout-btn *,
body.dark button *,
body.dark a[class*="btn"] * {
    color: #111827 !important;
}

body.dark .btn-primary:hover,
body.dark .btn-add:hover,
body.dark .btn-export:hover,
body.dark .btn-download:hover,
body.dark .btn-report:hover,
body.dark .btn-dl:hover,
body.dark .logout-btn:hover,
body.dark button:hover,
body.dark input[type="button"]:hover,
body.dark input[type="submit"]:hover,
body.dark a[class*="btn"]:hover {
    background: #fde68a !important;
    color: #111827 !important;
}

body.dark .btn-cancel,
body.dark .filter-btn,
body.dark .icon-btn,
body.dark .toggle-btn {
    background: #334155 !important;
    border-color: #94a3b8 !important;
    color: #f8fafc !important;
}

body.dark .btn-cancel *,
body.dark .filter-btn *,
body.dark .icon-btn *,
body.dark .toggle-btn * {
    color: #f8fafc !important;
}

body.dark .filter-btn.active,
body.dark .filter-btn:hover {
    background: #facc15 !important;
    color: #111827 !important;
}

body.dark .filter-btn.active *,
body.dark .filter-btn:hover * {
    color: #111827 !important;
}

body.dark .count-badge,
body.dark .type-pill,
body.dark .role-chip {
    background: #334155 !important;
    border-color: #94a3b8 !important;
    color: #f8fafc !important;
}

body.dark .pending-badge,
body.dark .badge.pending,
body.dark .status-badge.pending {
    background: #fef3c7 !important;
    border-color: #f59e0b !important;
    color: #451a03 !important;
}

body.dark .deliv-badge,
body.dark .badge.delivered,
body.dark .status-badge.delivered,
body.dark .stock-status.ok,
body.dark .stock-ok {
    background: #dcfce7 !important;
    border-color: #22c55e !important;
    color: #052e16 !important;
}

body.dark .low-badge,
body.dark .stock-status.low,
body.dark .stock-low,
body.dark .status-badge.not-delivered,
body.dark .alert.error {
    background: #fee2e2 !important;
    border-color: #ef4444 !important;
    color: #450a0a !important;
}

body.dark .alert.success {
    background: #dcfce7 !important;
    border-color: #22c55e !important;
    color: #052e16 !important;
}

/* Comprehensive white text color for all elements in dark mode */
body.dark,
body.dark * {
    color: #f8fafc !important;
}

/* Exception: Keep specific badge colors */
body.dark .pending-badge,
body.dark .badge.pending,
body.dark .status-badge.pending,
body.dark .pending-badge *,
body.dark .badge.pending *,
body.dark .status-badge.pending * {
    color: #451a03 !important;
}

body.dark .deliv-badge,
body.dark .badge.delivered,
body.dark .status-badge.delivered,
body.dark .stock-status.ok,
body.dark .stock-ok,
body.dark .deliv-badge *,
body.dark .badge.delivered *,
body.dark .status-badge.delivered *,
body.dark .stock-status.ok *,
body.dark .stock-ok * {
    color: #052e16 !important;
}

body.dark .low-badge,
body.dark .stock-status.low,
body.dark .stock-low,
body.dark .status-badge.not-delivered,
body.dark .alert.error,
body.dark .low-badge *,
body.dark .stock-status.low *,
body.dark .stock-low *,
body.dark .status-badge.not-delivered *,
body.dark .alert.error * {
    color: #450a0a !important;
}

body.dark .alert.success,
body.dark .alert.success * {
    color: #052e16 !important;
}

/* Button text colors - keep them for visibility */
body.dark .btn-primary,
body.dark .btn-add,
body.dark .btn-export,
body.dark .btn-download,
body.dark .btn-report,
body.dark .btn-dl,
body.dark .logout-btn,
body.dark button,
body.dark input[type="button"],
body.dark input[type="submit"],
body.dark a[class*="btn"],
body.dark .btn-primary *,
body.dark .btn-add *,
body.dark .btn-export *,
body.dark .btn-download *,
body.dark .btn-report *,
body.dark .btn-dl *,
body.dark .logout-btn *,
body.dark button *,
body.dark a[class*="btn"] * {
    color: #111827 !important;
}
</style>
</head>
<body>
<div class="navbar">
    <div class="logo">
        <div class="logo-icon"><i class="fa-solid fa-screwdriver-wrench" style="color:var(--gold);font-size:13px"></i></div>
        BOLT BASE &nbsp;<span class="role-chip"><i class="fa fa-user-tie"></i> Manager</span>
    </div>
    <div class="nav-links">
        <a href="manager_dashboard.php" class="active">Dashboard</a>
        <a href="manager_fasteners.php">Fasteners</a>
        <a href="manager_inventory.php">Inventory</a>
        <a href="manager_suppliers.php">Suppliers</a>
        <a href="manager_orders.php">Orders</a>
        <a href="manager_reports.php">Reports</a>
    </div>
    <div class="nav-right">
        <div class="pill"><i class="fa fa-calendar"></i><span id="dateStr"></span></div>
        <div class="pill"><i class="fa fa-user-tie"></i><?= htmlspecialchars($_SESSION['username']) ?></div>
        <div class="icon-btn" onclick="toggleDark()" title="Toggle dark mode"><i class="fa-solid fa-moon"></i></div>
        <a href="logout.php" class="logout-btn">Logout</a>
    </div>
</div>

<div class="main">
    <div class="page-header">
        <div>
            <h1><i class="fa fa-chart-line" style="color:var(--gold)"></i> Manager Overview</h1>
            <p>Read-only dashboard — full visibility of inventory, suppliers and orders</p>
        </div>
        <a href="manager_reports.php" class="btn-report"><i class="fa fa-file-pdf"></i> Generate Reports</a>
    </div>

    <!-- Stats -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon" style="background:rgba(245,200,66,0.16);color:var(--gold)"><i class="fa fa-screwdriver-wrench"></i></div>
            <div class="stat-label">Total Fasteners</div>
            <div class="stat-value"><?= $totalFasteners ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background:rgba(96,165,250,0.16);color:#60a5fa"><i class="fa fa-industry"></i></div>
            <div class="stat-label">Suppliers</div>
            <div class="stat-value"><?= $totalSuppliers ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background:rgba(74,222,128,0.16);color:#4ade80"><i class="fa fa-boxes-stacked"></i></div>
            <div class="stat-label">Total Stock</div>
            <div class="stat-value"><?= number_format($totalStock) ?></div>
            <div class="stat-sub">units in inventory</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background:rgba(251,191,36,0.16);color:#fbbf24"><i class="fa fa-cart-shopping"></i></div>
            <div class="stat-label">Total Orders</div>
            <div class="stat-value"><?= $totalOrders ?></div>
            <div class="stat-sub"><?= $pendingOrders ?> pending</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background:rgba(224,92,92,0.16);color:#e05c5c"><i class="fa fa-triangle-exclamation"></i></div>
            <div class="stat-label">Low Stock</div>
            <div class="stat-value" style="color:#e05c5c"><?= $lowStockItems ?></div>
            <div class="stat-sub">below 20 units</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background:rgba(167,139,250,0.16);color:#a78bfa"><i class="fa fa-indian-rupee-sign"></i></div>
            <div class="stat-label">Inventory Value</div>
            <div class="stat-value" style="font-size:20px">₹<?= number_format($inventoryValue, 0) ?></div>
        </div>
    </div>

    <!-- Quick Report Buttons -->
    <div class="sec-label"><i class="fa fa-file-pdf"></i> Quick PDF Exports</div>
    <div class="reports-row">
        <a href="manager_report_export.php?type=daily" class="report-btn-card"><i class="fa fa-calendar-day"></i> Download</a>
        <a href="manager_report_export.php?type=monthly" class="report-btn-card"><i class="fa fa-calendar-week"></i> Download</a>
        <a href="manager_report_export.php?type=yearly" class="report-btn-card"><i class="fa fa-calendar"></i> Download</a>
        <a href="manager_report_export.php?type=low_stock" class="report-btn-card"><i class="fa fa-triangle-exclamation"></i> Download</a>
        <a href="manager_report_export.php?type=inventory" class="report-btn-card"><i class="fa fa-boxes-stacked"></i> Download</a>
        <a href="manager_report_export.php?type=supplier" class="report-btn-card"><i class="fa fa-industry"></i> Download</a>
    </div>

    <!-- Recent Orders -->
    <div class="table-card">
        <div class="table-card-header">
            <h3>Recent Orders</h3>
            <a href="manager_orders.php" class="view-all">View All <i class="fa fa-arrow-right"></i></a>
        </div>
        <table>
            <thead><tr><th>#</th><th>Fastener</th><th>Supplier</th><th>Qty</th><th>Date</th><th>Status</th></tr></thead>
            <tbody>
            <?php $i=1; while($row = mysqli_fetch_assoc($recentOrders)): ?>
            <tr>
                <td style="font-family:'DM Mono',monospace;font-size:12px;color:var(--muted)"><?= $row['order_id'] ?></td>
                <td style="font-weight:600"><?= htmlspecialchars($row['fname']) ?></td>
                <td><?= htmlspecialchars($row['sname']) ?></td>
                <td style="font-family:'DM Mono',monospace"><?= $row['quantity'] ?></td>
                <td style="font-family:'DM Mono',monospace;font-size:12px"><?= date('d M Y', strtotime($row['order_date'])) ?></td>
                <td><span class="badge <?= strtolower($row['status']) ?>"><?= $row['status'] ?></span></td>
            </tr>
            <?php $i++; endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function tick() {
    const n = new Date();
    document.getElementById('dateStr').textContent = n.toLocaleDateString('en-IN');
}
setInterval(tick, 1000); tick();
function toggleDark() {
    document.body.classList.toggle('dark');
    localStorage.setItem('boltTheme', document.body.classList.contains('dark') ? 'dark' : 'light');
}
if(localStorage.getItem('boltTheme') === 'dark') document.body.classList.add('dark');

</script>
</body>
</html>

