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
<link rel="stylesheet" href="css/manager.css">

</head>
<body class="page-manager_dashboard">
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
                <td style="font-family:'DM Mono',monospace;font-size:12px;color:var(--muted)">#<?= $row['order_id'] ?></td>
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
    const timeEl = document.getElementById('timeStr'); if (timeEl) timeEl.textContent = n.toLocaleTimeString('en-IN');
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

