<?php
session_start();
include "db.php";

if(!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$successMsg = '';

// DELETE
if(isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    mysqli_query($conn, "DELETE FROM supplier WHERE id=$id");
    header("Location: supplier.php?success=deleted");
    exit();
}

if(isset($_GET['success'])) {
    if($_GET['success'] === 'added')   $successMsg = 'Supplier added successfully.';
    if($_GET['success'] === 'updated') $successMsg = 'Supplier updated successfully.';
    if($_GET['success'] === 'deleted') $successMsg = 'Supplier deleted successfully.';
}

// FETCH ALL
$result = mysqli_query($conn, "SELECT * FROM supplier ORDER BY id ASC");
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
<title>Suppliers – Bolt Base</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&family=DM+Mono:wght@500&display=swap" rel="stylesheet">
<style>
:root{--bg-from:#800020;--bg-to:#cc4444;--text:#ffffff;--muted:rgba(255,255,255,0.65);--gold:#ffd700;--gold-bg:rgba(255,215,0,0.15);--gold-br:rgba(255,215,0,0.40);--danger:#ff6b6b;--success:#4ade80;--blue:#60a5fa;--card-bg:rgba(0,0,0,0.40);--card-bdr:rgba(255,255,255,0.16);--input-bg:rgba(0,0,0,0.40);--input-bdr:rgba(255,255,255,0.25);--thead-bg:rgba(0,0,0,0.50);--row-bdr:rgba(255,255,255,0.10);--row-hover:rgba(255,255,255,0.06);}
body.dark{--bg-from:#0f172a;--bg-to:#1e293b;--card-bg:rgba(0,0,0,0.55);--card-bdr:rgba(255,255,255,0.10);--input-bg:rgba(0,0,0,0.55);--input-bdr:rgba(255,255,255,0.15);--thead-bg:rgba(0,0,0,0.60);--row-bdr:rgba(255,255,255,0.07);--row-hover:rgba(255,255,255,0.04);}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
body{font-family:'DM Sans','Segoe UI',sans-serif;background:linear-gradient(135deg,var(--bg-from) 0%,var(--bg-to) 100%);color:var(--text);min-height:100vh;transition:background 0.35s;}
.navbar{display:flex;justify-content:space-between;align-items:center;padding:14px 30px;background:rgba(0,0,0,0.40);backdrop-filter:blur(14px);border-bottom:1px solid rgba(255,255,255,0.12);position:sticky;top:0;z-index:200;gap:16px;}
.logo{font-size:18px;font-weight:600;display:flex;align-items:center;gap:9px;white-space:nowrap;}
.logo-icon{width:32px;height:32px;background:var(--gold-bg);border:1.5px solid var(--gold);border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:14px;}
.nav-links{display:flex;gap:2px;}
.nav-links a{color:rgba(255,255,255,0.75);padding:6px 12px;border-radius:8px;text-decoration:none;font-size:13px;font-weight:500;transition:0.2s;white-space:nowrap;}
.nav-links a:hover{background:rgba(255,255,255,0.12);color:#fff;}
.nav-links a.active{background:rgba(255,255,255,0.16);color:#fff;}
.nav-right{display:flex;align-items:center;gap:8px;flex-shrink:0;}
.pill{background:rgba(0,0,0,0.30);border:1px solid rgba(255,255,255,0.14);border-radius:20px;padding:5px 11px;font-size:12px;display:flex;align-items:center;gap:5px;white-space:nowrap;}
.pill i{font-size:10px;opacity:0.7;}
.icon-btn{width:32px;height:32px;background:rgba(0,0,0,0.30);border:1px solid rgba(255,255,255,0.14);border-radius:8px;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:13px;color:var(--text);transition:0.2s;}
.icon-btn:hover{background:rgba(0,0,0,0.50);}
.logout-btn{background:var(--gold);color:#1a0a00;border:none;border-radius:8px;padding:6px 14px;font-size:13px;font-weight:700;cursor:pointer;text-decoration:none;transition:0.2s;white-space:nowrap;}
.logout-btn:hover{background:#ffe040;}
.main{padding:32px 30px 50px;max-width:1200px;margin:0 auto;}
.page-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:28px;flex-wrap:wrap;gap:12px;}
.page-title{display:flex;align-items:center;gap:12px;}
.page-title-icon{width:46px;height:46px;background:var(--gold-bg);border:1.5px solid var(--gold-br);border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:20px;}
.page-title h1{font-size:22px;font-weight:600;letter-spacing:-0.3px;margin-bottom:2px;}
.page-title p{font-size:13px;color:var(--muted);}
.page-header-right{display:flex;align-items:center;gap:10px;}
.count-badge{background:var(--gold-bg);border:1px solid var(--gold-br);color:var(--gold);border-radius:20px;padding:5px 14px;font-size:13px;font-weight:600;}
.btn-export{background:rgba(255,255,255,0.12);color:#fff;border:1px solid rgba(255,255,255,0.18);border-radius:8px;padding:9px 16px;font-size:13px;font-weight:700;cursor:pointer;text-decoration:none;display:flex;align-items:center;gap:7px;transition:0.2s;}
.btn-export:hover{background:rgba(255,255,255,0.20);}
.btn-add{background:var(--gold);color:#1a0a00;border:none;border-radius:8px;padding:9px 18px;font-size:13px;font-weight:700;cursor:pointer;text-decoration:none;display:flex;align-items:center;gap:7px;transition:0.2s;}
.btn-add:hover{background:#ffe040;}
.alert{padding:12px 16px;border-radius:10px;font-size:13px;font-weight:500;margin-bottom:20px;display:flex;align-items:center;gap:10px;}
.alert.success{background:rgba(74,222,128,0.18);border:1px solid rgba(74,222,128,0.35);color:var(--success);}
.table-card{background:var(--card-bg);border:1px solid var(--card-bdr);border-radius:16px;overflow:hidden;}
.table-card-header{padding:18px 24px;border-bottom:1px solid var(--card-bdr);display:flex;align-items:center;justify-content:space-between;gap:12px;}
.table-card-header h3{font-size:14px;font-weight:600;color:#fff;display:flex;align-items:center;gap:8px;}
.table-card-header h3::before{content:'';width:3px;height:14px;background:var(--gold);border-radius:2px;display:inline-block;}
.search-box{background:var(--input-bg);border:1px solid var(--input-bdr);border-radius:8px;padding:7px 13px;color:#fff;font-family:inherit;font-size:13px;font-weight:500;outline:none;width:230px;transition:border-color 0.2s;}
.search-box::placeholder{color:rgba(255,255,255,0.40);}
.search-box:focus{border-color:var(--gold);}
table{width:100%;border-collapse:collapse;font-size:13.5px;}
thead tr{background:var(--thead-bg);}
th{padding:13px 16px;text-align:left;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:rgba(255,255,255,0.90);white-space:nowrap;}
td{padding:14px 16px;border-bottom:1px solid var(--row-bdr);vertical-align:middle;color:#ffffff;font-weight:500;}
tbody tr:last-child td{border-bottom:none;}
tbody tr:hover{background:var(--row-hover);}
.id-cell{font-family:'DM Mono',monospace;font-size:12px;color:rgba(255,255,255,0.70);font-weight:500;}
.name-cell{font-weight:600;font-size:14px;}
.contact-cell{font-family:'DM Mono',monospace;font-size:13px;}
.date-cell{font-family:'DM Mono',monospace;font-size:13px;color:rgba(255,255,255,0.85);}
.action-cell{display:flex;gap:6px;align-items:center;}
.btn-edit{background:rgba(96,165,250,0.20);border:1px solid rgba(96,165,250,0.40);color:#93c5fd;border-radius:7px;padding:5px 13px;font-size:12px;font-weight:600;text-decoration:none;display:flex;align-items:center;gap:5px;transition:0.2s;}
.btn-edit:hover{background:rgba(96,165,250,0.35);}
.btn-delete{background:rgba(255,107,107,0.20);border:1px solid rgba(255,107,107,0.40);color:#fca5a5;border-radius:7px;padding:5px 13px;font-size:12px;font-weight:600;text-decoration:none;display:flex;align-items:center;gap:5px;transition:0.2s;}
.btn-delete:hover{background:rgba(255,107,107,0.35);}
.empty-state{text-align:center;padding:48px 20px;color:rgba(255,255,255,0.50);}
.empty-state i{font-size:36px;margin-bottom:12px;display:block;opacity:0.4;}
.empty-state p{font-size:14px;}


/* Page theme overrides */
:root {
    --supervisor-red: #CD1C18;
    --supervisor-peach: #FFA896;
    --supervisor-deep: #9B1313;
    --supervisor-dark: #38000A;
    --supervisor-panel: #fff7f5;
    --supervisor-panel-soft: #ffe3dc;
    --supervisor-muted: #6f2220;
    --supervisor-border: #38000A;
    --bg-from: #fff0ec;
    --bg-to: var(--supervisor-peach);
    --text: var(--supervisor-dark);
    --muted: var(--supervisor-muted);
    --gold: var(--supervisor-red);
    --gold-bg: var(--supervisor-panel-soft);
    --gold-br: var(--supervisor-border);
    --card-bg: var(--supervisor-panel);
    --card-bdr: var(--supervisor-border);
    --input-bg: #fff;
    --input-bdr: var(--supervisor-border);
    --thead-bg: #ffd6cc;
    --row-bdr: rgba(56, 0, 10, 0.22);
    --row-hover: #ffe8e2;
    --border: rgba(56, 0, 10, 0.26);
}

body {
    background: linear-gradient(135deg, #fff7f5 0%, #ffd8d0 46%, var(--supervisor-peach) 100%) !important;
    color: var(--supervisor-dark) !important;
    overflow-x: hidden;
}

body.dark {
    --text: #fff7f5;
    --muted: #ffd6cc;
    --gold: #FFA896;
    --card-bg: rgba(56, 0, 10, 0.72);
    --card-bdr: #FFA896;
    --input-bg: rgba(56, 0, 10, 0.78);
    --input-bdr: #FFA896;
    --thead-bg: rgba(56, 0, 10, 0.92);
    --row-bdr: rgba(255, 168, 150, 0.30);
    --row-hover: rgba(255, 168, 150, 0.14);
    background: linear-gradient(135deg, var(--supervisor-dark) 0%, #6d0710 48%, var(--supervisor-deep) 100%) !important;
    color: #fff7f5 !important;
}

.navbar {
    background: linear-gradient(90deg, var(--supervisor-dark) 0%, var(--supervisor-deep) 100%) !important;
    border-bottom-color: var(--supervisor-border) !important;
    box-shadow: 0 8px 22px rgba(56, 0, 10, 0.24);
}

.logo,
.navbar .logo,
.navbar .logo *:not(.logo-icon):not(.logo-icon *) {
    color: #fff !important;
}

.logo-icon,
.page-title-icon {
    background: var(--supervisor-peach) !important;
    border-color: var(--supervisor-border) !important;
    color: var(--supervisor-dark) !important;
}

.logo-icon i,
.page-title-icon i,
.page-header i,
.count-badge i {
    color: var(--supervisor-dark) !important;
}

.nav-links a {
    color: rgba(255, 255, 255, 0.84) !important;
    border: 1px solid rgba(255, 168, 150, 0.32);
}

.nav-links a:hover,
.nav-links a.active {
    background: rgba(255, 168, 150, 0.22) !important;
    color: #fff !important;
    border-color: var(--supervisor-peach) !important;
}

.navbar .pill,
.navbar .icon-btn,
.toggle-btn {
    background: rgba(255, 168, 150, 0.14) !important;
    border-color: rgba(255, 168, 150, 0.35) !important;
    color: #fff !important;
}

.page-title h1,
.page-header h1 {
    color: var(--supervisor-dark) !important;
}

.page-title p,
.page-header p {
    color: var(--supervisor-muted) !important;
}

.logout-btn {
    background: var(--supervisor-red) !important;
    border: 1px solid var(--supervisor-border) !important;
    color: #fff !important;
}

.logout-btn:hover {
    background: var(--supervisor-dark) !important;
    color: #fff !important;
}

body.dark .page-title h1,
body.dark .page-header h1 {
    color: #fff7f5 !important;
}

body.dark .page-title p,
body.dark .page-header p {
    color: #ffd6cc !important;
}
.table-card {
    background: var(--supervisor-panel) !important;
    border-color: var(--supervisor-border) !important;
    color: var(--supervisor-dark) !important;
    box-shadow: 0 8px 18px rgba(56, 0, 10, 0.12);
}

.table-card-header {
    border-bottom-color: var(--supervisor-border) !important;
}

.table-card-header h3,
.name-cell,
.price-cell {
    color: var(--supervisor-dark) !important;
}

.table-card-header h3::before {
    background: var(--supervisor-red) !important;
}

thead tr,
th {
    background: var(--thead-bg) !important;
    color: var(--supervisor-dark) !important;
}

td {
    color: var(--supervisor-dark) !important;
    border-bottom-color: var(--row-bdr) !important;
}

tbody tr:hover {
    background: var(--row-hover) !important;
}

.id-cell,
.desc-cell {
    color: var(--supervisor-muted) !important;
}

.search-box {
    background: var(--input-bg) !important;
    border-color: var(--input-bdr) !important;
    color: var(--supervisor-dark) !important;
}

.search-box::placeholder {
    color: rgba(56, 0, 10, 0.48) !important;
}

.search-box:focus {
    border-color: var(--supervisor-red) !important;
    box-shadow: 0 0 0 3px rgba(205, 28, 24, 0.12);
}

.btn-add,
.btn-export {
    background: var(--supervisor-red) !important;
    border: 1px solid var(--supervisor-border) !important;
    color: #fff !important;
}

.btn-add:hover,
.btn-export:hover {
    background: var(--supervisor-peach) !important;
    color: var(--supervisor-dark) !important;
}

.count-badge,
.type-pill {
    background: var(--supervisor-peach) !important;
    border-color: var(--supervisor-border) !important;
    color: var(--supervisor-dark) !important;
}

.btn-edit {
    background: rgba(255, 168, 150, 0.45) !important;
    border-color: var(--supervisor-border) !important;
    color: var(--supervisor-dark) !important;
}

.btn-delete {
    background: rgba(205, 28, 24, 0.12) !important;
    border-color: var(--supervisor-red) !important;
    color: var(--supervisor-deep) !important;
}

.alert.success {
    background: #ffe3dc !important;
    border-color: var(--supervisor-border) !important;
    color: var(--supervisor-dark) !important;
}

.stock-status.ok {
    background: #ffe3dc !important;
    border-color: var(--supervisor-border) !important;
    color: var(--supervisor-dark) !important;
}

.stock-status.low,
.qty-cell.low-stock {
    background: rgba(205, 28, 24, 0.14) !important;
    border-color: var(--supervisor-red) !important;
    color: var(--supervisor-deep) !important;
}

.thumb,
.no-img {
    border-color: var(--supervisor-border) !important;
}

body.dark .table-card {
    background: rgba(56, 0, 10, 0.72) !important;
}

body.dark .table-card-header h3,
body.dark td,
body.dark .name-cell,
body.dark .price-cell {
    color: #fff7f5 !important;
}

body.dark .id-cell,
body.dark .desc-cell {
    color: #ffd6cc !important;
}

body.dark .search-box {
    color: #fff7f5 !important;
}
</style>
</head>
<body>

<div class="navbar">
    <div class="logo">
        <div class="logo-icon"><i class="fa-solid fa-screwdriver-wrench" style="color:var(--gold);font-size:13px"></i></div>
        BOLT BASE
    </div>
    <div class="nav-links">
        <a href="dashboard.php">Dashboard</a>
        <a href="fastener.php">Fasteners</a>
        <a href="inventory.php">Inventory</a>
        <a href="pick_list.php">Pick List</a>
        <a href="supplier.php" class="active">Suppliers</a>
        <a href="orders.php">Orders</a>
    </div>
    <div class="nav-right">
        <div class="pill"><i class="fa fa-calendar"></i><span id="dateStr"></span></div>
        <div class="pill"><i class="fa fa-clock"></i><span id="timeStr"></span></div>
        <div class="pill"><i class="fa fa-user"></i><?= htmlspecialchars($_SESSION['username']) ?></div>
        <div class="icon-btn" onclick="toggleDark()" title="Toggle dark mode"><i class="fa-solid fa-moon"></i></div>
        <a href="logout.php" class="logout-btn">Logout</a>
    </div>
</div>

<div class="main">

    <div class="page-header">
        <div class="page-title">
            <div class="page-title-icon"><i class="fa-solid fa-industry"></i></div>
            <div>
                <h1>Supplier Management</h1>
                <p>View and manage all supplier records</p>
            </div>
        </div>
        <div class="page-header-right">
            <div class="count-badge"><i class="fa fa-layer-group"></i> <?= $totalCount ?> Records</div>
            <a href="export_report.php?type=supplier" class="btn-export"><i class="fa fa-file-pdf"></i> Download</a>
            <a href="add_supplier.php" class="btn-add"><i class="fa fa-plus"></i> Add Supplier</a>
        </div>
    </div>

    <?php if($successMsg): ?>
    <div class="alert success"><i class="fa fa-circle-check"></i> <?= $successMsg ?></div>
    <?php endif; ?>

    <div class="table-card">
        <div class="table-card-header">
            <h3>All Suppliers</h3>
            <input type="text" class="search-box" id="searchInput" placeholder="Search suppliers…" onkeyup="filterTable()">
        </div>
        <table id="supplierTable">
            <thead>
                <tr>
                    <th>ID</th><th>Name</th><th>Contact</th><th>Address</th><th>Contract Date</th><th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if($totalCount === 0): ?>
                <tr><td colspan="6">
                    <div class="empty-state">
                        <i class="fa-solid fa-industry"></i>
                        <p>No suppliers found. <a href="add_supplier.php" style="color:var(--gold)">Add one now.</a></p>
                    </div>
                </td></tr>
                <?php else: foreach($rows as $index => $row): ?>
                <tr>
                    <td class="id-cell">#<?= $index + 1 ?></td>
                    <td class="name-cell"><?= htmlspecialchars($row['name']) ?></td>
                    <td class="contact-cell"><?= htmlspecialchars($row['contact']) ?></td>
                    <td><?= htmlspecialchars($row['address']) ?></td>
                    <td class="date-cell"><?= htmlspecialchars($row['contract_date']) ?></td>
                    <td>
                        <div class="action-cell">
                            <a href="edit_supplier.php?id=<?= $row['id'] ?>" class="btn-edit">
                                <i class="fa fa-pen"></i> Edit
                            </a>
                            <a href="?delete=<?= $row['id'] ?>" class="btn-delete"
                               onclick="return confirm('Delete this supplier?')">
                                <i class="fa fa-trash"></i> Delete
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>

</div>

<script>
function tick() {
    const now = new Date();
    document.getElementById('dateStr').textContent = now.toLocaleDateString('en-IN');
    document.getElementById('timeStr').textContent = now.toLocaleTimeString('en-IN');
}
setInterval(tick, 1000); tick();
function toggleDark() {
    document.body.classList.toggle('dark');
    localStorage.setItem('boltTheme', document.body.classList.contains('dark') ? 'dark' : 'light');
}
if(localStorage.getItem('boltTheme') === 'dark') document.body.classList.add('dark');
setTimeout(() => {
    document.querySelectorAll('.alert.success').forEach(alert => {
        alert.style.transition = 'opacity 0.25s ease, transform 0.25s ease';
        alert.style.opacity = '0';
        alert.style.transform = 'translateY(-6px)';
        setTimeout(() => alert.remove(), 300);
    });
}, 4000);
function filterTable() {
    const q = document.getElementById('searchInput').value.toLowerCase();
    document.querySelectorAll('#supplierTable tbody tr').forEach(r => {
        r.style.display = r.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
}
</script>
</body>
</html>
