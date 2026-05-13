<?php
session_start();
include "db.php";
include "manager_auth.php";

// Quick summary numbers for report cards
$today        = date('Y-m-d');
$thisMonth    = date('Y-m');
$thisYear     = date('Y');

$ordersToday  = mysqli_fetch_row(mysqli_query($conn,"SELECT COUNT(*) FROM orders WHERE DATE(order_date)='$today'"))[0];
$ordersMonth  = mysqli_fetch_row(mysqli_query($conn,"SELECT COUNT(*) FROM orders WHERE DATE_FORMAT(order_date,'%Y-%m')='$thisMonth'"))[0];
$ordersYear   = mysqli_fetch_row(mysqli_query($conn,"SELECT COUNT(*) FROM orders WHERE YEAR(order_date)='$thisYear'"))[0];
$lowStock     = mysqli_fetch_row(mysqli_query($conn,"SELECT COUNT(*) FROM stock WHERE quantity < 20"))[0];
$totalFast    = mysqli_fetch_row(mysqli_query($conn,"SELECT COUNT(*) FROM fastener"))[0];
$totalSup     = mysqli_fetch_row(mysqli_query($conn,"SELECT COUNT(*) FROM supplier"))[0];
$invValue     = mysqli_fetch_row(mysqli_query($conn,"SELECT COALESCE(SUM(f.unit_price*s.quantity),0) FROM stock s JOIN fastener f ON s.fastener_id=f.id"))[0];
$allOrders    = mysqli_fetch_row(mysqli_query($conn,"SELECT COUNT(*) FROM orders"))[0];
$pendingCnt   = mysqli_fetch_row(mysqli_query($conn,"SELECT COUNT(*) FROM orders WHERE status='Pending'"))[0];
$deliveredCnt = mysqli_fetch_row(mysqli_query($conn,"SELECT COUNT(*) FROM orders WHERE status='Delivered'"))[0];

$monthlyChartRes = mysqli_query($conn,"
    SELECT DATE_FORMAT(order_date,'%b %Y') AS mo, COUNT(*) AS cnt
    FROM orders
    WHERE order_date >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY YEAR(order_date), MONTH(order_date)
    ORDER BY YEAR(order_date), MONTH(order_date)
");
$monthLabels = []; $monthCounts = [];
while($r = mysqli_fetch_assoc($monthlyChartRes)) { $monthLabels[] = $r['mo']; $monthCounts[] = $r['cnt']; }

$topRes = mysqli_query($conn,"
    SELECT f.name, s.quantity FROM stock s
    JOIN fastener f ON s.fastener_id=f.id
    ORDER BY s.quantity DESC LIMIT 5
");
$topNames = []; $topQtys = [];
while($r = mysqli_fetch_assoc($topRes)) { $topNames[] = $r['name']; $topQtys[] = $r['quantity']; }

$supplierOrderRes = mysqli_query($conn,"
    SELECT s.name, COUNT(*) AS cnt
    FROM orders o
    JOIN supplier s ON o.supplier_id=s.id
    GROUP BY s.id, s.name
    ORDER BY cnt DESC, s.name ASC
    LIMIT 6
");
$supplierNames = []; $supplierCounts = [];
while($r = mysqli_fetch_assoc($supplierOrderRes)) { $supplierNames[] = $r['name']; $supplierCounts[] = (int)$r['cnt']; }

$valueRes = mysqli_query($conn,"
    SELECT f.name, COALESCE(f.unit_price * s.quantity,0) AS value
    FROM stock s
    JOIN fastener f ON s.fastener_id=f.id
    ORDER BY value DESC
    LIMIT 6
");
$valueNames = []; $valueTotals = [];
while($r = mysqli_fetch_assoc($valueRes)) { $valueNames[] = $r['name']; $valueTotals[] = round((float)$r['value'], 2); }

$typeRes = mysqli_query($conn,"
    SELECT COALESCE(NULLIF(TRIM(type),''),'Unspecified') AS type_name, COUNT(*) AS cnt
    FROM fastener
    GROUP BY type_name
    ORDER BY cnt DESC, type_name ASC
    LIMIT 6
");
$typeNames = []; $typeCounts = [];
while($r = mysqli_fetch_assoc($typeRes)) { $typeNames[] = $r['type_name']; $typeCounts[] = (int)$r['cnt']; }

$healthyStock = mysqli_fetch_row(mysqli_query($conn,"SELECT COUNT(*) FROM stock WHERE quantity >= 20"))[0];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Reports – Manager – Bolt Base</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&family=DM+Mono:wght@500&display=swap" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<style>
:root {
    --bg-from:#1a3a6b;
    --bg-to:#2d5fa6;
    --text:#fff;
    --muted:rgba(255,255,255,0.65);
    --gold:#ffd700;
    --gold-bg:rgba(255,215,0,0.15);
    --gold-br:rgba(255,215,0,0.40);
    --card-bg:rgba(0,0,0,0.35);
    --card-bdr:rgba(255,255,255,0.16);
    --thead-bg:rgba(0,0,0,0.45);
    --row-bdr:rgba(255,255,255,0.10);
    --row-hover:rgba(255,255,255,0.06);
}
body.dark {
    --bg-from:#0f172a;
    --bg-to:#1e293b;
    --card-bg:rgba(0,0,0,0.55);
    --card-bdr:rgba(255,255,255,0.10);
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
}
.navbar {
    display:flex;
    justify-content:space-between;
    align-items:center;
    padding:14px 28px;
    background:rgba(0,0,0,0.40);
    backdrop-filter:blur(14px);
    border-bottom:1px solid rgba(255,255,255,0.12);
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
}
.logo-icon {
    width:32px;
    height:32px;
    background:var(--gold-bg);
    border:1.5px solid var(--gold);
    border-radius:8px;
    display:flex;
    align-items:center;
    justify-content:center;
}
.role-chip {
    background:rgba(26,58,107,0.60);
    border:1px solid rgba(100,160,255,0.40);
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
    color:rgba(255,255,255,0.75);
    padding:6px 12px;
    border-radius:8px;
    text-decoration:none;
    font-size:13px;
    font-weight:500;
    transition:0.2s;
}
.nav-links a:hover {
    background:rgba(255,255,255,0.12);
    color:#fff;
}
.nav-links a.active {
    background:rgba(255,255,255,0.16);
    color:#fff;
}
.nav-right {
    display:flex;
    align-items:center;
    gap:8px;
    flex-shrink:0;
}
.pill {
    background:rgba(0,0,0,0.30);
    border:1px solid rgba(255,255,255,0.14);
    border-radius:20px;
    padding:5px 11px;
    font-size:12px;
    display:flex;
    align-items:center;
    gap:5px;
}
.pill i {
    font-size:10px;
    opacity:0.7;
}
.icon-btn {
    width:32px;
    height:32px;
    background:rgba(0,0,0,0.30);
    border:1px solid rgba(255,255,255,0.14);
    border-radius:8px;
    cursor:pointer;
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:13px;
    color:var(--text);
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
}
.logout-btn:hover {
    background:#ffe040;
}
.main {
    padding:28px 28px 60px;
    max-width:1200px;
    margin:0 auto;
}
h1 {
    font-size:22px;
    font-weight:700;
    margin-bottom:4px;
}
.sub {
    font-size:13px;
    color:var(--muted);
    margin-bottom:28px;
}
/* Section heading */
.sec-head {
    font-size:13px;
    font-weight:700;
    text-transform:uppercase;
    letter-spacing:1px;
    color:var(--muted);
    margin:28px 0 12px;
    display:flex;
    align-items:center;
    gap:8px;
}
.sec-head::before {
    content:'';
    width:18px;
    height:2px;
    background:var(--gold);
    border-radius:2px;
}
/* Report cards grid */
.reports-grid {
    display:grid;
    grid-template-columns:repeat(auto-fill,minmax(240px,1fr));
    gap:14px;
}
.report-card {
    background:var(--card-bg);
    border:1px solid var(--card-bdr);
    border-radius:14px;
    padding:20px;
    display:flex;
    flex-direction:column;
    gap:10px;
    transition:transform 0.2s,border-color 0.2s;
}
.report-card:hover {
    transform:translateY(-2px);
    border-color:rgba(255,215,0,0.40);
}
.report-card-icon {
    width:44px;
    height:44px;
    border-radius:11px;
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:18px;
}
.report-card h3 {
    font-size:14px;
    font-weight:600;
}
.report-card p {
    font-size:12px;
    color:var(--muted);
}
.report-card .stat {
    font-size:24px;
    font-weight:700;
    color:var(--gold);
}
.btn-dl {
    background:var(--gold);
    color:#1a0a00;
    border:none;
    border-radius:8px;
    padding:9px 16px;
    font-size:12px;
    font-weight:700;
    cursor:pointer;
    text-decoration:none;
    display:inline-flex;
    align-items:center;
    gap:7px;
    transition:0.2s;
    margin-top:4px;
}
.btn-dl:hover {
    background:#ffe040;
}
/* Custom date range */
.range-card {
    background:var(--card-bg);
    border:1px solid var(--card-bdr);
    border-radius:14px;
    padding:22px 24px;
    margin-top:4px;
}
.range-card h3 {
    font-size:14px;
    font-weight:600;
    margin-bottom:16px;
    color:var(--gold);
}
.range-form {
    display:flex;
    flex-wrap:wrap;
    gap:12px;
    align-items:flex-end;
}
.range-field {
    display:flex;
    flex-direction:column;
    gap:6px;
}
.range-field label {
    font-size:11px;
    font-weight:700;
    text-transform:uppercase;
    letter-spacing:0.8px;
    color:var(--muted);
}
.range-field input,
.range-field select {
    background:rgba(0,0,0,0.35);
    border:1px solid rgba(255,255,255,0.25);
    border-radius:8px;
    padding:9px 13px;
    color:#fff;
    font-family:inherit;
    font-size:13px;
    outline:none;
}
.range-field input:focus,
.range-field select:focus {
    border-color:var(--gold);
}
.table-card {
    background:var(--card-bg);
    border:1px solid var(--card-bdr);
    border-radius:14px;
    overflow:hidden;
    margin-top:4px;
}
.table-card-header {
    padding:16px 22px;
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
thead tr {
    background:var(--thead-bg);
}
th {
    padding:11px 16px;
    text-align:left;
    font-size:11px;
    font-weight:700;
    text-transform:uppercase;
    letter-spacing:1px;
    color:rgba(255,255,255,0.85);
}
td {
    padding:12px 16px;
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
    padding:3px 9px;
    border-radius:20px;
    font-size:11px;
    font-weight:700;
}
.badge.g {
    background:rgba(74,222,128,0.15);
    border:1px solid rgba(74,222,128,0.35);
    color:#4ade80;
}
.badge.y {
    background:rgba(251,191,36,0.15);
    border:1px solid rgba(251,191,36,0.35);
    color:#fbbf24;
}
.charts-row {
    display:grid;
    grid-template-columns:1fr 1fr 0.7fr;
    gap:14px;
    margin-bottom:14px;
}
.charts-row.extra {
    grid-template-columns:repeat(2,minmax(0,1fr));
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
    margin-bottom:18px;
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
:root {
    --bg-from:#dff3ff;
    --bg-to:#b9e3ff;
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
body.dark {
    --bg-from:#dff3ff;
    --bg-to:#b9e3ff;
    --card-bg:rgba(255,255,255,0.82);
    --card-bdr:#000;
}
.navbar,
.pill,
.icon-btn,
.logout-btn,
.report-card,
.btn-dl,
.range-card,
.table-card,
.chart-card,
.range-field input,
.range-field select {
    border-color:#000!important;
}
.navbar {
    background:rgba(223,243,255,0.92);
}
.nav-links a,
.pill,
td,
th {
    color:#071827;
}
.nav-links a:hover,
.nav-links a.active,
.icon-btn:hover,
.logout-btn:hover,
.btn-dl:hover {
    background:#073b78!important;
    color:#fff!important;
    border-color:#000!important;
    text-decoration:none;
}
.report-card:hover,
.range-card:hover,
.table-card:hover,
.chart-card:hover {
    background:#073b78!important;
    border-color:#000!important;
    color:#fff;
}
.report-card:hover *,
.range-card:hover *,
.table-card:hover *,
.chart-card:hover * {
    color:#fff!important;
}
@media(max-width:900px) {
    .charts-row,
    .charts-row.extra {
        grid-template-columns:1fr;
    }
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
body.dark {
    --text: #f8fadf;
    --muted: #d4de95;
    --gold: var(--manager-light);
    --card-bg: #343820;
    --input-bg: #2f331f;
    --thead-bg: #262a17;
    --row-bdr: rgba(0, 0, 0, 0.45);
    --row-hover: #4a502f;
    background: linear-gradient(135deg, #2f331f 0%, #3d4127 48%, #636b2f 100%) !important;
    color: #f8fadf !important;
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
body.dark .page-title h1,
body.dark .page-header h1,
body.dark h1 {
    color: #f8fadf !important;
}
body.dark .page-title p,
body.dark .page-header p,
body.dark .sub,
body.dark .sec-label,
body.dark .sec-head {
    color: #d4de95 !important;
}
.report-card,
.range-card,
.chart-card,
.table-card {
    background: var(--manager-panel) !important;
    border-color: var(--manager-border) !important;
    color: var(--manager-dark) !important;
    box-shadow: 0 8px 18px rgba(61, 65, 39, 0.12);
}
.report-card:hover,
.range-card:hover,
.chart-card:hover,
.table-card:hover {
    background: var(--manager-panel-soft) !important;
    color: var(--manager-dark) !important;
}
.report-card h3,
.chart-card h3,
.table-card-header h3,
td {
    color: var(--manager-dark) !important;
}
.report-card p,
.range-field label,
.id-cell {
    color: var(--manager-muted) !important;
}
.range-field input,
.range-field select {
    background: var(--input-bg) !important;
    border-color: var(--manager-border) !important;
    color: var(--manager-dark) !important;
}
.report-card-icon {
    background: var(--manager-light) !important;
    border-color: var(--manager-border) !important;
    color: var(--manager-dark) !important;
}
.chart-card h3::before,
.table-card-header h3::before {
    background: var(--manager-olive) !important;
}
th {
    background: var(--thead-bg) !important;
    color: var(--manager-dark) !important;
}
.pending-badge,
.badge.pending {
    background: #fff1b8 !important;
    color: #3d2f00 !important;
    border-color: #000 !important;
}
.deliv-badge,
.badge.delivered {
    background: #dceec2 !important;
    color: #20360e !important;
    border-color: #000 !important;
}
.low-badge {
    background: #ffe0d7 !important;
    color: #5b170d !important;
    border-color: #000 !important;
}
body.dark .report-card,
body.dark .range-card,
body.dark .chart-card,
body.dark .table-card {
    background: #343820 !important;
    color: #f8fadf !important;
}
body.dark .report-card h3,
body.dark .chart-card h3,
body.dark .table-card-header h3,
body.dark td {
    color: #f8fadf !important;
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
body.dark .stat-card:hover,
body.dark .report-card:hover,
body.dark .range-card:hover,
body.dark .chart-card:hover,
body.dark .table-card:hover,
body.dark .readonly-notice:hover,
body.dark tbody tr:hover {
    background: #4a502f !important;
    color: #f8fadf !important;
}
body.dark .stat-card:hover *,
body.dark .report-card:hover *,
body.dark .range-card:hover *,
body.dark .chart-card:hover *,
body.dark .table-card:hover *,
body.dark .readonly-notice:hover * {
    color: #f8fadf !important;
}
</style>
<link rel="stylesheet" href="dark_mode_fix.css">

</head>
<body>
<div class="navbar">
    <div class="logo">
        <div class="logo-icon"><i class="fa-solid fa-screwdriver-wrench" style="color:var(--gold);font-size:13px"></i></div>
        BOLT BASE &nbsp;<span class="role-chip"><i class="fa fa-user-tie"></i> Manager</span>
    </div>
    <div class="nav-links">
        <a href="manager_dashboard.php">Dashboard</a>
        <a href="manager_fasteners.php">Fasteners</a>
        <a href="manager_inventory.php">Inventory</a>
        <a href="manager_suppliers.php">Suppliers</a>
        <a href="manager_orders.php">Orders</a>
        <a href="manager_reports.php" class="active">Reports</a>
    </div>
    <div class="nav-right">
        <div class="pill"><i class="fa fa-calendar"></i><span id="dateStr"></span></div>
        <div class="pill"><i class="fa fa-user-tie"></i><?= htmlspecialchars($_SESSION['username']) ?></div>
        <div class="icon-btn" onclick="toggleDark()"><i class="fa-solid fa-moon"></i></div>
        <a href="logout.php" class="logout-btn">Logout</a>
    </div>
</div>

<div class="main">
    <h1><i class="fa fa-file-chart-column" style="color:var(--gold)"></i> Reports Center</h1>
    <p class="sub">Generate and download PDF reports — daily, monthly, yearly or custom range</p>

    <div class="sec-head"><i class="fa fa-chart-bar"></i> Visual Analytics</div>
    <div class="charts-row">
        <div class="chart-card">
            <h3>Orders - Last 6 Months</h3>
            <canvas id="monthChart" height="200"></canvas>
        </div>
        <div class="chart-card">
            <h3>Top 5 Fasteners by Stock</h3>
            <canvas id="topChart" height="200"></canvas>
        </div>
        <div class="chart-card">
            <h3>Order Status</h3>
            <canvas id="statusChart" height="200"></canvas>
        </div>
    </div>
    <div class="charts-row extra">
        <div class="chart-card">
            <h3>Orders by Supplier</h3>
            <canvas id="supplierChart" height="190"></canvas>
        </div>
        <div class="chart-card">
            <h3>Inventory Value by Fastener</h3>
            <canvas id="valueChart" height="190"></canvas>
        </div>
        <div class="chart-card">
            <h3>Fastener Type Mix</h3>
            <canvas id="typeChart" height="190"></canvas>
        </div>
        <div class="chart-card">
            <h3>Stock Health</h3>
            <canvas id="stockHealthChart" height="190"></canvas>
        </div>
    </div>

    <!-- Time-based Order Reports -->
    <div class="sec-head"><i class="fa fa-cart-shopping"></i> Order Reports</div>
    <div class="reports-grid">
        <div class="report-card">
            <div class="report-card-icon" style="background:rgba(96,165,250,0.18);color:#60a5fa"><i class="fa fa-calendar-day"></i></div>
            <h3>Today's Orders</h3>
            <p>Orders placed on <?= date('d M Y') ?></p>
            <div class="stat"><?= $ordersToday ?></div>
            <a href="manager_report_export.php?type=daily" class="btn-dl"><i class="fa fa-file-pdf"></i> Download</a>
        </div>
        <div class="report-card">
            <div class="report-card-icon" style="background:rgba(167,139,250,0.18);color:#a78bfa"><i class="fa fa-calendar-week"></i></div>
            <h3>This Month's Orders</h3>
            <p><?= date('F Y') ?> order summary</p>
            <div class="stat"><?= $ordersMonth ?></div>
            <a href="manager_report_export.php?type=monthly" class="btn-dl"><i class="fa fa-file-pdf"></i> Download</a>
        </div>
        <div class="report-card">
            <div class="report-card-icon" style="background:rgba(251,191,36,0.18);color:#fbbf24"><i class="fa fa-calendar"></i></div>
            <h3>This Year's Orders</h3>
            <p><?= date('Y') ?> annual order report</p>
            <div class="stat"><?= $ordersYear ?></div>
            <a href="manager_report_export.php?type=yearly" class="btn-dl"><i class="fa fa-file-pdf"></i> Download</a>
        </div>
    </div>

    <!-- Inventory & Stock Reports -->
    <div class="sec-head"><i class="fa fa-boxes-stacked"></i> Inventory Reports</div>
    <div class="reports-grid">
        <div class="report-card">
            <div class="report-card-icon" style="background:rgba(74,222,128,0.18);color:#4ade80"><i class="fa fa-boxes-stacked"></i></div>
            <h3>Full Inventory</h3>
            <p>Current stock levels for all fasteners</p>
            <div class="stat">₹<?= number_format($invValue, 0) ?></div>
            <a href="manager_report_export.php?type=inventory" class="btn-dl"><i class="fa fa-file-pdf"></i> Download</a>
        </div>
        <div class="report-card">
            <div class="report-card-icon" style="background:rgba(255,107,107,0.18);color:#ff6b6b"><i class="fa fa-triangle-exclamation"></i></div>
            <h3>Low Stock Alert</h3>
            <p>Items below minimum (20 units)</p>
            <div class="stat" style="color:#ff6b6b"><?= $lowStock ?></div>
            <a href="manager_report_export.php?type=low_stock" class="btn-dl"><i class="fa fa-file-pdf"></i> Download</a>
        </div>
        <div class="report-card">
            <div class="report-card-icon" style="background:rgba(255,215,0,0.18);color:var(--gold)"><i class="fa fa-screwdriver-wrench"></i></div>
            <h3>Fastener Catalog</h3>
            <p>Full catalog with prices and specs</p>
            <div class="stat"><?= $totalFast ?></div>
            <a href="manager_report_export.php?type=fasteners" class="btn-dl"><i class="fa fa-file-pdf"></i> Download</a>
        </div>
        <div class="report-card">
            <div class="report-card-icon" style="background:rgba(96,165,250,0.18);color:#60a5fa"><i class="fa fa-industry"></i></div>
            <h3>Supplier List</h3>
            <p>All suppliers with contact details</p>
            <div class="stat"><?= $totalSup ?></div>
            <a href="manager_report_export.php?type=supplier" class="btn-dl"><i class="fa fa-file-pdf"></i> Download</a>
        </div>
        <div class="report-card">
            <div class="report-card-icon" style="background:rgba(167,139,250,0.18);color:#a78bfa"><i class="fa fa-cart-shopping"></i></div>
            <h3>All Orders</h3>
            <p>Complete order history export</p>
            <div class="stat"><?= mysqli_fetch_row(mysqli_query($conn,"SELECT COUNT(*) FROM orders"))[0] ?></div>
            <a href="manager_report_export.php?type=orders" class="btn-dl"><i class="fa fa-file-pdf"></i> Download</a>
        </div>
    </div>

</div>
<script>
function tick(){const n=new Date();document.getElementById('dateStr').textContent=n.toLocaleDateString('en-IN');}
setInterval(tick,1000);tick();
function toggleDark(){document.body.classList.toggle('dark');localStorage.setItem('boltTheme',document.body.classList.contains('dark')?'dark':'light');updateChartsTheme();}
if(localStorage.getItem('boltTheme')==='dark')document.body.classList.add('dark');

function chartTheme(){
    const dark=document.body.classList.contains('dark');
    return {
        tick: dark ? '#fffde7' : '#3D4127',
        grid: dark ? 'rgba(233,239,184,0.18)' : 'rgba(61,65,39,0.14)',
        monthBg: dark ? 'rgba(212,222,149,0.78)' : 'rgba(99,107,47,0.68)',
        monthBorder: dark ? '#D4DE95' : '#636B2F',
        topBg: dark ? 'rgba(186,192,149,0.82)' : 'rgba(61,65,39,0.68)',
        topBorder: dark ? '#BAC095' : '#3D4127',
        pending: dark ? 'rgba(255,224,102,0.86)' : 'rgba(251,191,36,0.75)',
        delivered: dark ? 'rgba(174,213,129,0.86)' : 'rgba(74,222,128,0.75)',
        supplierBg: dark ? 'rgba(248,250,223,0.78)' : 'rgba(99,107,47,0.62)',
        supplierBorder: dark ? '#F8FADF' : '#636B2F',
        valueBg: dark ? 'rgba(212,222,149,0.86)' : 'rgba(37,41,20,0.70)',
        valueBorder: dark ? '#D4DE95' : '#252914',
        typePalette: dark
            ? ['#d4de95','#bac095','#f8fadf','#8e9862','#fff1b8','#dceec2']
            : ['#636b2f','#252914','#bac095','#d4de95','#8a6f24','#7a3b24'],
        low: dark ? 'rgba(255,196,176,0.88)' : 'rgba(255,107,107,0.76)',
        healthy: dark ? 'rgba(186,192,149,0.88)' : 'rgba(99,107,47,0.76)'
    };
}
function axisOptions(t){
    return {
        x:{ticks:{color:t.tick,font:{size:11,weight:'600'}},grid:{color:t.grid},beginAtZero:true},
        y:{ticks:{color:t.tick,font:{size:11,weight:'600'}},grid:{color:t.grid},beginAtZero:true}
    };
}
const theme=chartTheme();
const monthChart=new Chart(document.getElementById('monthChart'),{
    type:'bar',
    data:{labels:<?= json_encode($monthLabels) ?>,datasets:[{label:'Orders',data:<?= json_encode($monthCounts) ?>,backgroundColor:theme.monthBg,borderColor:theme.monthBorder,borderWidth:1.5,borderRadius:6}]},
    options:{plugins:{legend:{display:false}},scales:{x:{ticks:{color:theme.tick,font:{size:11,weight:'600'}},grid:{color:theme.grid}},y:{ticks:{color:theme.tick,font:{size:11,weight:'600'}},grid:{color:theme.grid},beginAtZero:true}}}
});
const topChart=new Chart(document.getElementById('topChart'),{
    type:'bar',
    data:{labels:<?= json_encode($topNames) ?>,datasets:[{label:'Stock',data:<?= json_encode($topQtys) ?>,backgroundColor:theme.topBg,borderColor:theme.topBorder,borderWidth:1.5,borderRadius:6}]},
    options:{indexAxis:'y',plugins:{legend:{display:false}},scales:{x:{ticks:{color:theme.tick,font:{size:11,weight:'600'}},grid:{color:theme.grid},beginAtZero:true},y:{ticks:{color:theme.tick,font:{size:10,weight:'600'}},grid:{color:theme.grid}}}}
});
const statusChart=new Chart(document.getElementById('statusChart'),{
    type:'doughnut',
    data:{labels:['Pending','Delivered'],datasets:[{data:[<?= $pendingCnt ?>,<?= $deliveredCnt ?>],backgroundColor:[theme.pending,theme.delivered],borderColor:['#fbbf24','#16a34a'],borderWidth:1.5}]},
    options:{cutout:'68%',plugins:{legend:{labels:{color:theme.tick,font:{size:12,weight:'600'}}}}}
});
const supplierChart=new Chart(document.getElementById('supplierChart'),{
    type:'bar',
    data:{labels:<?= json_encode($supplierNames) ?>,datasets:[{label:'Orders',data:<?= json_encode($supplierCounts) ?>,backgroundColor:theme.supplierBg,borderColor:theme.supplierBorder,borderWidth:1.5,borderRadius:6}]},
    options:{plugins:{legend:{display:false}},scales:axisOptions(theme)}
});
const valueChart=new Chart(document.getElementById('valueChart'),{
    type:'bar',
    data:{labels:<?= json_encode($valueNames) ?>,datasets:[{label:'Inventory Value',data:<?= json_encode($valueTotals) ?>,backgroundColor:theme.valueBg,borderColor:theme.valueBorder,borderWidth:1.5,borderRadius:6}]},
    options:{indexAxis:'y',plugins:{legend:{display:false},tooltip:{callbacks:{label:(ctx)=>'Rs. '+Number(ctx.raw || 0).toLocaleString('en-IN')}}},scales:{x:{ticks:{color:theme.tick,font:{size:11,weight:'600'},callback:(v)=>'Rs. '+Number(v).toLocaleString('en-IN')},grid:{color:theme.grid},beginAtZero:true},y:{ticks:{color:theme.tick,font:{size:10,weight:'600'}},grid:{color:theme.grid}}}}
});
const typeChart=new Chart(document.getElementById('typeChart'),{
    type:'polarArea',
    data:{labels:<?= json_encode($typeNames) ?>,datasets:[{data:<?= json_encode($typeCounts) ?>,backgroundColor:theme.typePalette,borderColor:'#111',borderWidth:1}]},
    options:{plugins:{legend:{position:'right',labels:{color:theme.tick,font:{size:11,weight:'600'}}}},scales:{r:{ticks:{display:false},grid:{color:theme.grid}}}}
});
const stockHealthChart=new Chart(document.getElementById('stockHealthChart'),{
    type:'doughnut',
    data:{labels:['Healthy Stock','Low Stock'],datasets:[{data:[<?= (int)$healthyStock ?>,<?= (int)$lowStock ?>],backgroundColor:[theme.healthy,theme.low],borderColor:['#636b2f','#7a3b24'],borderWidth:1.5}]},
    options:{cutout:'66%',plugins:{legend:{labels:{color:theme.tick,font:{size:12,weight:'600'}}}}}
});
function updateChartsTheme(){
    const t=chartTheme();
    monthChart.data.datasets[0].backgroundColor=t.monthBg;
    monthChart.data.datasets[0].borderColor=t.monthBorder;
    topChart.data.datasets[0].backgroundColor=t.topBg;
    topChart.data.datasets[0].borderColor=t.topBorder;
    statusChart.data.datasets[0].backgroundColor=[t.pending,t.delivered];
    supplierChart.data.datasets[0].backgroundColor=t.supplierBg;
    supplierChart.data.datasets[0].borderColor=t.supplierBorder;
    valueChart.data.datasets[0].backgroundColor=t.valueBg;
    valueChart.data.datasets[0].borderColor=t.valueBorder;
    typeChart.data.datasets[0].backgroundColor=t.typePalette;
    stockHealthChart.data.datasets[0].backgroundColor=[t.healthy,t.low];
    [monthChart,topChart,supplierChart,valueChart].forEach(chart=>{
        chart.options.scales.x.ticks.color=t.tick;
        chart.options.scales.x.grid.color=t.grid;
        chart.options.scales.y.ticks.color=t.tick;
        chart.options.scales.y.grid.color=t.grid;
        chart.update();
    });
    statusChart.options.plugins.legend.labels.color=t.tick;
    typeChart.options.plugins.legend.labels.color=t.tick;
    typeChart.options.scales.r.grid.color=t.grid;
    stockHealthChart.options.plugins.legend.labels.color=t.tick;
    statusChart.update();
    typeChart.update();
    stockHealthChart.update();
}
</script>
</body>
</html>

