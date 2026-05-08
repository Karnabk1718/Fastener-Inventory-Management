<?php
session_start();
include "db.php";

if(!isset($_SESSION['username']))
{
    header("Location: login.php");
    exit();
}

// Stats
$totalFasteners = mysqli_fetch_row(mysqli_query($conn,"SELECT COUNT(*) FROM fastener"))[0];
$totalSuppliers = mysqli_fetch_row(mysqli_query($conn,"SELECT COUNT(*) FROM supplier"))[0];
$totalStock     = mysqli_fetch_row(mysqli_query($conn,"SELECT COALESCE(SUM(quantity),0) FROM stock"))[0];
$totalOrders    = mysqli_fetch_row(mysqli_query($conn,"SELECT COUNT(*) FROM orders"))[0];
$lowStockItems  = mysqli_fetch_row(mysqli_query($conn,"SELECT COUNT(*) FROM stock WHERE quantity < 20"))[0];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Bolt Base – Dashboard</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link rel="stylesheet" href="css/user.css">
</head>
<body class="page-dashboard">

<!-- NAVBAR -->
<div class="navbar">

    <div class="logo">
        <div class="logo-icon"><i class="fa-solid fa-screwdriver-wrench" style="font-size:15px;color:var(--gold)"></i></div>
        BOLT BASE
    </div>

    <div class="nav-links">
        <a href="dashboard.php" class="active">Dashboard</a>
        <a href="fastener.php">Fasteners</a>
        <a href="inventory.php">Inventory</a>
        <a href="pick_list.php">Pick List</a>
        <a href="supplier.php">Suppliers</a>
        <a href="orders.php">Orders</a>
    </div>

    <div class="nav-right">
        <div class="pill"><i class="fa fa-calendar"></i><span id="dateStr"></span></div>
        <div class="pill"><i class="fa fa-user"></i><?= htmlspecialchars($_SESSION['username']) ?></div>
        <div class="toggle-btn" onclick="toggleDark()" title="Toggle dark mode">
            <i class="fa-solid fa-moon"></i>
        </div>
        <a href="logout.php" class="logout-btn">Logout</a>
    </div>

</div>

<!-- MAIN -->
<div class="main">

    <!-- WELCOME -->
    <div class="welcome-row">
        <div class="welcome-text">
            <h1>Welcome back, <?= htmlspecialchars($_SESSION['username']) ?> 👋</h1>
            <p>Here's what's happening with your fastener inventory today.</p>
        </div>
    </div>

    <!-- STAT CARDS -->
    <div class="stats-grid">

        <div class="stat-card s1" >
            <div class="stat-icon"><i class="fa-solid fa-screwdriver-wrench"></i></div>
            <div class="stat-label">Total Fasteners</div>
            <div class="stat-value"><?= $totalFasteners ?></div>
            <div class="stat-sub">All fastener records</div>
        </div>

        <div class="stat-card s2" ">
            <div class="stat-icon"><i class="fa-solid fa-box"></i></div>
            <div class="stat-label">Stock Units</div>
            <div class="stat-value"><?= number_format($totalStock) ?></div>
            <div class="stat-sub">Across all categories</div>
        </div>

        <div class="stat-card s3" >
            <div class="stat-icon"><i class="fa-solid fa-industry"></i></div>
            <div class="stat-label">Suppliers</div>
            <div class="stat-value"><?= $totalSuppliers ?></div>
            <div class="stat-sub">Registered suppliers</div>
        </div>

        <div class="stat-card s4" >
            <div class="stat-icon"><i class="fa-solid fa-cart-shopping"></i></div>
            <div class="stat-label">Total Orders</div>
            <div class="stat-value"><?= $totalOrders ?></div>
            <div class="stat-sub">All purchase orders</div>
        </div>

    </div>

    <!-- ACTION CARDS -->
    <div class="action-row">

        <div class="action-card" onclick="location.href='fastener.php'">
            <div class="ac-header">
                <div class="ac-icon i1"><i class="fa-solid fa-screwdriver-wrench"></i></div>
                <div class="ac-title">Fasteners</div>
            </div>
            <div class="ac-desc">Manage all fastener types — bolts, nuts, screws, washers and more.</div>
            <div class="ac-footer">
                <div class="ac-count">Items: <span><?= $totalFasteners ?></span></div>
                <a href="fastener.php" class="ac-btn">Open →</a>
            </div>
        </div>

        <div class="action-card" onclick="location.href='inventory.php'">
            <div class="ac-header">
                <div class="ac-icon i2"><i class="fa-solid fa-box"></i></div>
                <div class="ac-title">Inventory</div>
            </div>
            <div class="ac-desc">Track real-time stock levels, warehouses, and reorder alerts.</div>
            <div class="ac-footer">
                <div class="ac-count">Stock: <span><?= number_format($totalStock) ?></span></div>
                <a href="inventory.php" class="ac-btn">Open →</a>
            </div>
        </div>

        <div class="action-card" onclick="location.href='supplier.php'">
            <div class="ac-header">
                <div class="ac-icon i3"><i class="fa-solid fa-industry"></i></div>
                <div class="ac-title">Suppliers</div>
            </div>
            <div class="ac-desc">View and manage your supplier contacts, details and agreements.</div>
            <div class="ac-footer">
                <div class="ac-count">Total: <span><?= $totalSuppliers ?></span></div>
                <a href="supplier.php" class="ac-btn">Open →</a>
            </div>
        </div>

        <div class="action-card" onclick="location.href='orders.php'">
            <div class="ac-header">
                <div class="ac-icon i4"><i class="fa-solid fa-cart-shopping"></i></div>
                <div class="ac-title">Orders</div>
            </div>
            <div class="ac-desc">Place and track purchase orders across all your suppliers.</div>
            <div class="ac-footer">
                <div class="ac-count">Orders: <span><?= $totalOrders ?></span></div>
                <a href="orders.php" class="ac-btn">Open →</a>
            </div>
        </div>

        <div class="action-card" onclick="location.href='low_stock.php'">
            <div class="ac-header">
                <div class="ac-icon i5"><i class="fa-solid fa-triangle-exclamation"></i></div>
                <div class="ac-title">Low Stock</div>
            </div>
            <div class="ac-desc">Review only the inventory items that are below the minimum stock level of 20.</div>
            <div class="ac-footer">
                <div class="ac-count">Alerts: <span><?= $lowStockItems ?></span></div>
                <a href="low_stock.php" class="ac-btn">Open →</a>
            </div>
        </div>

    </div>

    <div class="section-title">Quick Reports</div>
    <div class="report-row">
        <div class="report-card">
            <div class="report-meta">
                <h3>Fasteners PDF</h3>
                <p>Download all fastener details in one report.</p>
            </div>
            <a href="export_report.php?type=fastener" class="report-btn">Download</a>
        </div>
        <div class="report-card">
            <div class="report-meta">
                <h3>Download</h3>
                <p>Includes stock quantities and low-stock status.</p>
            </div>
            <a href="export_report.php?type=inventory" class="report-btn">Download</a>
        </div>
        <div class="report-card">
            <div class="report-meta">
                <h3>Suppliers PDF</h3>
                <p>Collect supplier contacts and contract details.</p>
            </div>
            <a href="export_report.php?type=supplier" class="report-btn">Download</a>
        </div>
        <div class="report-card">
            <div class="report-meta">
                <h3>Download</h3>
                <p>Download order quantities, dates, and statuses.</p>
            </div>
            <a href="export_report.php?type=orders" class="report-btn">Download</a>
        </div>
    </div>

</div>

<script>
// ── DATETIME ──
function tick() {
    const now = new Date();
    document.getElementById('dateStr').textContent = now.toLocaleDateString('en-IN');
}
setInterval(tick, 1000);
tick();

// ── DARK MODE ──
function toggleDark() {
    document.body.classList.toggle('dark');
    localStorage.setItem('boltTheme', document.body.classList.contains('dark') ? 'dark' : 'light');
}
if(localStorage.getItem('boltTheme') === 'dark') document.body.classList.add('dark');
</script>
</body>
</html>
