<?php
session_start();
include "db.php";
include "manager_auth.php";

$result = mysqli_query($conn,"SELECT * FROM fastener ORDER BY id ASC");
$rows = [];
while($r = mysqli_fetch_assoc($result)) $rows[] = $r;
usort($rows, fn($a,$b) => $a['id'] - $b['id']);
$totalCount = count($rows);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Fasteners – Manager – Bolt Base</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&family=DM+Mono:wght@500&display=swap" rel="stylesheet">
<style>
:root {
    --bg-from:#1a3a6b;
    --bg-to:#2d5fa6;
    --text:#ffffff;
    --muted:rgba(255,255,255,0.65);
    --gold:#ffd700;
    --gold-bg:rgba(255,215,0,0.15);
    --gold-br:rgba(255,215,0,0.40);
    --card-bg:rgba(0,0,0,0.35);
    --card-bdr:rgba(255,255,255,0.16);
    --input-bg:rgba(0,0,0,0.35);
    --input-bdr:rgba(255,255,255,0.25);
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
    padding:28px 28px 50px;
    max-width:1300px;
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
.page-title {
    display:flex;
    align-items:center;
    gap:12px;
}
.page-title-icon {
    width:46px;
    height:46px;
    background:var(--gold-bg);
    border:1.5px solid var(--gold-br);
    border-radius:12px;
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:20px;
}
.page-title h1 {
    font-size:22px;
    font-weight:600;
    margin-bottom:2px;
}
.page-title p {
    font-size:13px;
    color:var(--muted);
}
.count-badge {
    background:var(--gold-bg);
    border:1px solid var(--gold-br);
    color:var(--gold);
    border-radius:20px;
    padding:5px 14px;
    font-size:13px;
    font-weight:600;
}
.btn-export {
    background:rgba(255,255,255,0.12);
    color:#fff;
    border:1px solid rgba(255,255,255,0.18);
    border-radius:8px;
    padding:9px 16px;
    font-size:13px;
    font-weight:700;
    cursor:pointer;
    text-decoration:none;
    display:flex;
    align-items:center;
    gap:7px;
    transition:0.2s;
}
.btn-export:hover {
    background:rgba(255,255,255,0.20);
}
.readonly-notice {
    background:rgba(96,165,250,0.12);
    border:1px solid rgba(96,165,250,0.30);
    color:#93c5fd;
    border-radius:10px;
    padding:10px 16px;
    font-size:13px;
    margin-bottom:18px;
    display:flex;
    align-items:center;
    gap:8px;
}
.table-card {
    background:var(--card-bg);
    border:1px solid var(--card-bdr);
    border-radius:16px;
    overflow:hidden;
}
.table-card-header {
    padding:16px 22px;
    border-bottom:1px solid var(--card-bdr);
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:12px;
}
.table-card-header h3 {
    font-size:14px;
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
.search-box {
    background:var(--input-bg);
    border:1px solid var(--input-bdr);
    border-radius:8px;
    padding:7px 13px;
    color:#fff;
    font-family:inherit;
    font-size:13px;
    outline:none;
    width:220px;
    transition:border-color 0.2s;
}
.search-box::placeholder {
    color:rgba(255,255,255,0.40);
}
.search-box:focus {
    border-color:var(--gold);
}
.table-scroll {
    overflow-x:auto;
}
table {
    width:100%;
    border-collapse:collapse;
    font-size:13.5px;
    min-width:900px;
}
thead tr {
    background:var(--thead-bg);
}
th {
    padding:12px 16px;
    text-align:left;
    font-size:11px;
    font-weight:700;
    text-transform:uppercase;
    letter-spacing:1px;
    color:rgba(255,255,255,0.90);
}
td {
    padding:13px 16px;
    border-bottom:1px solid var(--row-bdr);
    color:#fff;
    font-weight:500;
    vertical-align:middle;
}
tbody tr:last-child td {
    border-bottom:none;
}
tbody tr:hover {
    background:var(--row-hover);
}
.id-cell {
    font-family:'DM Mono',monospace;
    font-size:12px;
    color:rgba(255,255,255,0.65);
}
.name-cell {
    font-weight:600;
    font-size:14px;
}
.part-cell {
    font-family:'DM Mono',monospace;
    font-size:12px;
    color:var(--muted);
    word-break:break-word;
}
.type-pill {
    background:rgba(255,215,0,0.18);
    border:1px solid rgba(255,215,0,0.40);
    color:var(--gold);
    border-radius:20px;
    padding:4px 12px;
    font-size:12px;
    font-weight:600;
}
.size-cell {
    font-family:'DM Mono',monospace;
    font-size:13px;
}
.price-cell {
    font-weight:700;
    font-size:14px;
}
.thumb {
    width:46px;
    height:46px;
    object-fit:cover;
    border-radius:8px;
    border:1px solid var(--card-bdr);
}
.no-img {
    width:46px;
    height:46px;
    border-radius:8px;
    background:rgba(255,255,255,0.07);
    border:1px dashed rgba(255,255,255,0.20);
    display:flex;
    align-items:center;
    justify-content:center;
    color:rgba(255,255,255,0.25);
    font-size:16px;
}
.empty-state {
    text-align:center;
    padding:48px 20px;
    color:rgba(255,255,255,0.45);
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
    --input-bg:#fff;
    --input-bdr:#000;
    --thead-bg:#c7e8fb;
    --row-bdr:#000;
    --row-hover:#d5ecff;
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
.btn-export,
.count-badge,
.readonly-notice,
.table-card,
.search-box,
.page-title-icon,
.type-pill,
.thumb,
.no-img {
    border-color:#000!important;
}
.navbar {
    background:rgba(223,243,255,0.92);
}
.nav-links a,
.pill,
td,
th,
.btn-export {
    color:#071827;
}
.nav-links a:hover,
.nav-links a.active,
.icon-btn:hover,
.logout-btn:hover,
.btn-export:hover {
    background:#073b78!important;
    color:#fff!important;
    border-color:#000!important;
    text-decoration:none;
}
.table-card:hover,
.readonly-notice:hover {
    background:#073b78!important;
    border-color:#000!important;
    color:#fff;
}
.table-card:hover *,
.readonly-notice:hover * {
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
.table-card,
.readonly-notice {
    background: var(--manager-panel) !important;
    border-color: var(--manager-border) !important;
    color: var(--manager-dark) !important;
    box-shadow: 0 8px 18px rgba(61, 65, 39, 0.12);
}
.table-card:hover,
.readonly-notice:hover {
    background: var(--manager-panel-soft) !important;
    color: var(--manager-dark) !important;
}
.table-card-header {
    border-bottom-color: var(--manager-border) !important;
}
.table-card-header h3,
.name-cell,
.price-cell,
td {
    color: var(--manager-dark) !important;
}
.table-card-header h3::before {
    background: var(--manager-olive) !important;
}
th {
    background: var(--thead-bg) !important;
    color: var(--manager-dark) !important;
}
td {
    border-bottom-color: var(--row-bdr) !important;
}
tbody tr:hover {
    background: var(--row-hover) !important;
}
.id-cell,
.desc-cell,
.readonly-notice {
    color: var(--manager-muted) !important;
}
.search-box,
input,
select {
    background: var(--input-bg) !important;
    border-color: var(--manager-border) !important;
    color: var(--manager-dark) !important;
}
.count-badge,
.type-pill {
    background: var(--manager-light) !important;
    border-color: var(--manager-border) !important;
    color: var(--manager-dark) !important;
    font-weight: 800 !important;
}
.pending-badge,
.badge.pending {
    background: #fff1b8 !important;
    color: #3d2f00 !important;
    border-color: var(--manager-border) !important;
}
.deliv-badge,
.badge.delivered,
.stock-ok {
    background: #dceec2 !important;
    color: #20360e !important;
    border-color: var(--manager-border) !important;
}
.low-badge,
.stock-low {
    background: #ffe0d7 !important;
    color: #5b170d !important;
    border-color: var(--manager-border) !important;
}
.thumb,
.no-img {
    border-color: var(--manager-border) !important;
}
body.dark .table-card,
body.dark .readonly-notice {
    background: #343820 !important;
    color: #f8fadf !important;
}
body.dark .table-card-header h3,
body.dark .name-cell,
body.dark .price-cell,
body.dark td {
    color: #f8fadf !important;
}
body.dark .id-cell,
body.dark .desc-cell {
    color: #d4de95 !important;
}
body.dark .search-box,
body.dark input,
body.dark select {
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
        <a href="manager_fasteners.php" class="active">Fasteners</a>
        <a href="manager_inventory.php">Inventory</a>

        <a href="manager_suppliers.php">Suppliers</a>
        <a href="manager_orders.php">Orders</a>
        <a href="manager_reports.php">Reports</a>
    </div>
    <div class="nav-right">
        <div class="pill"><i class="fa fa-calendar"></i><span id="dateStr"></span></div>
        <div class="pill"><i class="fa fa-user-tie"></i><?= htmlspecialchars($_SESSION['username']) ?></div>
        <div class="icon-btn" onclick="toggleDark()"><i class="fa-solid fa-moon"></i></div>
        <a href="logout.php" class="logout-btn">Logout</a>
    </div>
</div>
<div class="main">
    <div class="page-header">
        <div class="page-title">
            <div class="page-title-icon"><i class="fa-solid fa-screwdriver-wrench"></i></div>
            <div><h1>Fasteners</h1><p>View all fastener catalog entries</p></div>
        </div>
        <div style="display:flex;gap:10px;align-items:center;">
            <div class="count-badge"><i class="fa fa-layer-group"></i> <?= $totalCount ?> Records</div>
            <a href="manager_report_export.php?type=fasteners" class="btn-export"><i class="fa fa-file-pdf"></i> Download</a>
        </div>
    </div>
    <div class="table-card">
        <div class="table-card-header">
            <h3>All Fasteners</h3>
            <input type="text" class="search-box" id="searchInput" placeholder="Search fasteners…" onkeyup="filterTable()">
        </div>
        <div class="table-scroll">
        <table id="mainTable">
            <thead><tr><th>ID</th><th>Image</th><th>Name</th><th>Part No.</th><th>Type</th><th>Size</th><th>Price (₹)</th><th>Description</th></tr></thead>
            <tbody>
            <?php if($totalCount===0): ?>
            <tr><td colspan="8"><div class="empty-state"><i class="fa fa-screwdriver-wrench"></i><p>No fasteners found.</p></div></td></tr>
            <?php else: foreach($rows as $i => $row): ?>
            <tr>
                <td class="id-cell"><?= $i+1 ?></td>
                <td>
                    <?php if(!empty($row['image'])): ?>
                    <img src="uploads/<?= htmlspecialchars($row['image']) ?>" class="thumb" alt=""
                         onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
                    <div class="no-img" style="display:none"><i class="fa fa-image"></i></div>
                    <?php else: ?>
                    <div class="no-img"><i class="fa fa-image"></i></div>
                    <?php endif; ?>
                </td>
                <td class="name-cell"><?= htmlspecialchars($row['name']) ?></td>
                <td class="part-cell"><?= htmlspecialchars(display_part_number($row['part_number'] ?? '')) ?></td>
                <td><span class="type-pill"><?= htmlspecialchars($row['type']) ?></span></td>
                <td class="size-cell"><?= htmlspecialchars($row['size']) ?></td>
                <td class="price-cell">₹<?= number_format($row['unit_price'],2) ?></td>
                <td style="font-size:13px;color:var(--muted);max-width:200px;"><?= htmlspecialchars($row['description'] ?? '—') ?></td>
            </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
        </div>
    </div>
</div>
<script>
function tick(){const n=new Date();document.getElementById('dateStr').textContent=n.toLocaleDateString('en-IN');}
setInterval(tick,1000);tick();
function toggleDark(){document.body.classList.toggle('dark');localStorage.setItem('boltTheme',document.body.classList.contains('dark')?'dark':'light');}
if(localStorage.getItem('boltTheme')==='dark')document.body.classList.add('dark');
function filterTable(){const q=document.getElementById('searchInput').value.toLowerCase();document.querySelectorAll('#mainTable tbody tr').forEach(r=>{r.style.display=r.textContent.toLowerCase().includes(q)?'':''+(r.style.display='none')||'';});}
function filterTable(){const q=document.getElementById('searchInput').value.toLowerCase();document.querySelectorAll('#mainTable tbody tr').forEach(r=>{r.style.display=r.textContent.toLowerCase().includes(q)?'':'none';});}
</script>
</body>
</html>

