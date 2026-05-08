<?php
session_start();
include "db.php";

if(!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$result = mysqli_query($conn, "
    SELECT s.id, s.fastener_id, f.name, f.part_number, f.type, f.size, f.unit_price, s.quantity, f.image, f.description
    FROM stock s
    JOIN fastener f ON s.fastener_id = f.id
    WHERE s.quantity < 20
    ORDER BY s.quantity ASC, s.id ASC
");

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
<title>Low Stock – Bolt Base</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&family=DM+Mono:wght@500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="css/user.css">
</head>
<body class="page-low_stock">

<div class="navbar">
    <div class="logo">
        <div class="logo-icon"><i class="fa-solid fa-screwdriver-wrench" style="color:var(--gold);font-size:13px"></i></div>
        BOLT BASE
    </div>
    <div class="nav-links">
        <a href="dashboard.php">Dashboard</a>
        <a href="fastener.php">Fasteners</a>
        <a href="inventory.php" class="active">Inventory</a>
        <a href="pick_list.php">Pick List</a>
        <a href="supplier.php">Suppliers</a>
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
            <div class="page-title-icon"><i class="fa-solid fa-triangle-exclamation"></i></div>
            <div>
                <h1>Low Stock Inventory</h1>
                <p>Inventory items with quantity below 20 units</p>
            </div>
        </div>
        <div class="page-header-right">
            <div class="count-badge"><i class="fa fa-triangle-exclamation"></i> <?= $totalCount ?> Alerts</div>
            <a href="export_report.php?type=low_stock" class="btn-export"><i class="fa fa-file-pdf"></i> Download</a>
            <a href="inventory.php" class="btn-back"><i class="fa fa-arrow-left"></i> Back to Inventory</a>
        </div>
    </div>

    <div class="table-card">
        <div class="table-card-header">
            <h3>Minimum Stock Watchlist</h3>
            <input type="text" class="search-box" id="searchInput" placeholder="Search low stock…" onkeyup="filterTable()">
        </div>
        <div class="table-scroll">
        <table id="lowStockTable">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Image</th>
                    <th>Fastener Name</th>
                    <th>Part No.</th>
                    <th>Type</th>
                    <th>Size</th>
                    <th>Price / Unit (₹)</th>
                    <th>Quantity</th>
                    <th>Status</th>
                    <th>Description</th>
                </tr>
            </thead>
            <tbody>
                <?php if($totalCount === 0): ?>
                <tr><td colspan="10">
                    <div class="empty-state">
                        <i class="fa-solid fa-circle-check"></i>
                        <p>No low stock items right now. All stock levels are healthy!</p>
                    </div>
                </td></tr>
                <?php else: foreach($rows as $index => $row): ?>
                <tr>
                    <td class="id-cell">#<?= $index + 1 ?></td>
                    <td>
                        <?php if(!empty($row['image'])): ?>
                            <?php $imgSrc = rtrim(dirname($_SERVER['PHP_SELF']), '/') . '/uploads/' . htmlspecialchars($row['image']); ?>
                            <img src="<?= $imgSrc ?>" alt="<?= htmlspecialchars($row['name']) ?>" class="thumb"
                                 onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
                            <div class="no-img" style="display:none"><i class="fa-solid fa-image"></i></div>
                        <?php else: ?>
                            <div class="no-img"><i class="fa-solid fa-image"></i></div>
                        <?php endif; ?>
                    </td>
                    <td class="name-cell"><?= htmlspecialchars($row['name']) ?></td>
                    <td class="part-cell"><?= htmlspecialchars($row['part_number'] ?? '—') ?></td>
                    <td><span class="type-pill"><?= htmlspecialchars($row['type']) ?></span></td>
                    <td class="size-cell"><?= htmlspecialchars($row['size']) ?></td>
                    <td class="price-cell">₹<?= number_format($row['unit_price'], 2) ?></td>
                    <td class="qty-cell"><?= $row['quantity'] ?></td>
                    <td><span class="stock-status low">Below Minimum</span></td>
                    <td class="desc-cell"><?= htmlspecialchars($row['description'] ?? '—') ?></td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
        </div>
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
function filterTable() {
    const q = document.getElementById('searchInput').value.toLowerCase();
    document.querySelectorAll('#lowStockTable tbody tr').forEach(r => {
        r.style.display = r.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
}
</script>
</body>
</html>
