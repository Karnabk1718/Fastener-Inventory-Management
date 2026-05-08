<?php
session_start();
include "db.php";
include "manager_auth.php";

$result = mysqli_query($conn,"
    SELECT o.order_id, f.name AS fname, f.part_number, s.name AS sname, o.quantity, o.status, o.order_date
    FROM orders o
    JOIN fastener f ON o.fastener_id=f.id
    JOIN supplier s ON o.supplier_id=s.id
    ORDER BY o.order_id ASC
");
$rows = [];
while($r = mysqli_fetch_assoc($result)) $rows[] = $r;
$totalCount   = count($rows);
$pendingCount = count(array_filter($rows, fn($r) => $r['status']==='Pending'));
$delivCount   = count(array_filter($rows, fn($r) => $r['status']==='Delivered'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Orders – Manager – Bolt Base</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&family=DM+Mono:wght@500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="css/manager.css">

</head>
<body class="page-manager_orders">
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
        <a href="manager_orders.php" class="active">Orders</a>
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
            <div class="page-title-icon"><i class="fa-solid fa-cart-shopping"></i></div>
            <div><h1>Orders</h1><p>View all purchase orders</p></div>
        </div>
        <div class="badge-row">
            <div class="count-badge"><i class="fa fa-layer-group"></i> <?= $totalCount ?> Total</div>
            <div class="pending-badge"><i class="fa fa-clock"></i> <?= $pendingCount ?> Pending</div>
            <div class="deliv-badge"><i class="fa fa-check"></i> <?= $delivCount ?> Delivered</div>
            <a href="manager_report_export.php?type=orders" class="btn-export"><i class="fa fa-file-pdf"></i> Download</a>
        </div>
    </div>
    <div class="filter-bar">
        <button class="filter-btn active" onclick="filterStatus('all',this)">All</button>
        <button class="filter-btn" onclick="filterStatus('Pending',this)">Pending</button>
        <button class="filter-btn" onclick="filterStatus('Delivered',this)">Delivered</button>
    </div>
    <div class="table-card">
        <div class="table-card-header">
            <h3>All Orders</h3>
            <input type="text" class="search-box" id="searchInput" placeholder="Search orders…" onkeyup="searchTable()">
        </div>
        <table id="mainTable">
            <thead><tr><th>ID</th><th>Fastener</th><th>Part No.</th><th>Supplier</th><th>Quantity</th><th>Order Date</th><th>Status</th></tr></thead>
            <tbody>
            <?php if($totalCount===0): ?>
            <tr><td colspan="7" style="text-align:center;padding:40px;color:var(--muted)">No orders found.</td></tr>
            <?php else: foreach($rows as $i => $row): ?>
            <tr data-status="<?= htmlspecialchars($row['status']) ?>">
                <td class="id-cell">#<?= $row['order_id'] ?></td>
                <td style="font-weight:600"><?= htmlspecialchars($row['fname']) ?></td>
                <td style="font-family:'DM Mono',monospace"><?= htmlspecialchars($row['part_number'] ?? '—') ?></td>
                <td><?= htmlspecialchars($row['sname']) ?></td>
                <td style="font-family:'DM Mono',monospace"><?= $row['quantity'] ?></td>
                <td style="font-family:'DM Mono',monospace"><?= date('d M Y, h:i A', strtotime($row['order_date'])) ?></td>
                <td><span class="badge <?= strtolower($row['status']) ?>"><?= $row['status'] ?></span></td>
            </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
<script>
function tick(){const n=new Date();document.getElementById('dateStr').textContent=n.toLocaleDateString('en-IN');const timeEl = document.getElementById('timeStr'); if (timeEl) timeEl.textContent = n.toLocaleTimeString('en-IN');}
setInterval(tick,1000);tick();
function toggleDark(){document.body.classList.toggle('dark');localStorage.setItem('boltTheme',document.body.classList.contains('dark')?'dark':'light');}
if(localStorage.getItem('boltTheme')==='dark')document.body.classList.add('dark');
let activeStatus = 'all';
function filterStatus(status, btn) {
    activeStatus = status;
    document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    applyFilters();
}
function searchTable() { applyFilters(); }
function applyFilters() {
    const q = document.getElementById('searchInput').value.toLowerCase();
    document.querySelectorAll('#mainTable tbody tr').forEach(r => {
        const matchStatus = activeStatus === 'all' || r.dataset.status === activeStatus;
        const matchText   = r.textContent.toLowerCase().includes(q);
        r.style.display   = (matchStatus && matchText) ? '' : 'none';
    });
}
</script>
</body>
</html>

