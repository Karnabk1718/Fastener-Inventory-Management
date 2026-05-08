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
    mysqli_query($conn, "DELETE FROM stock WHERE id=$id");
    header("Location: inventory.php?success=deleted");
    exit();
}

if(isset($_GET['success'])) {
    if($_GET['success'] === 'added')   $successMsg = 'Stock added successfully.';
    if($_GET['success'] === 'updated') $successMsg = 'Stock updated successfully.';
    if($_GET['success'] === 'deleted') $successMsg = 'Stock record deleted successfully.';
    if($_GET['success'] === 'picked')  $successMsg = 'Stock picked successfully.';
}

// FETCH STOCK
$result = mysqli_query($conn, "
    SELECT s.id, s.fastener_id, f.name, f.part_number, f.type, f.size, f.unit_price, s.quantity, f.image, f.description
    FROM stock s
    JOIN fastener f ON s.fastener_id = f.id
    ORDER BY s.id ASC
");
$rows = [];
while($r = mysqli_fetch_assoc($result)) $rows[] = $r;
usort($rows, fn($a,$b) => $a['id'] - $b['id']);
$totalCount    = count($rows);
$lowStockCount = 0;
foreach($rows as $stockRow) {
    if((int)$stockRow['quantity'] < 20) $lowStockCount++;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Inventory – Bolt Base</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&family=DM+Mono:wght@500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="css/user.css">
</head>
<body class="page-inventory">

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
            <div class="page-title-icon"><i class="fa-solid fa-boxes-stacked"></i></div>
            <div>
                <h1>Inventory Management</h1>
                <p>Track and manage stock levels for all fasteners</p>
            </div>
        </div>
        <div class="page-header-right">
            <div class="count-badge"><i class="fa fa-layer-group"></i> <?= $totalCount ?> Stock Records</div>
            <a href="low_stock.php" class="count-badge alert-badge">
                <i class="fa fa-triangle-exclamation"></i> <?= $lowStockCount ?> Low Stock
            </a>
            <a href="export_report.php?type=inventory" class="btn-export"><i class="fa fa-file-pdf"></i> Download</a>
            <a href="pick_list.php" class="btn-add"><i class="fa fa-clipboard-list"></i> Pick Stock</a>
            <a href="add_inventory.php" class="btn-add"><i class="fa fa-plus"></i> Add Stock</a>
        </div>
    </div>

    <?php if($successMsg): ?>
    <div class="alert success"><i class="fa fa-circle-check"></i> <?= $successMsg ?></div>
    <?php endif; ?>

    <div class="table-card">
        <div class="table-card-header">
            <h3>All Inventory</h3>
            <input type="text" class="search-box" id="searchInput" placeholder="Search inventory…" onkeyup="filterTable()">
        </div>
        <table id="stockTable">
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
                        <i class="fa-solid fa-boxes-stacked"></i>
                        <p>No stock records found. <a href="add_inventory.php" style="color:var(--gold)">Add stock now.</a></p>
                    </div>
                </td></tr>
                <?php else: foreach($rows as $index => $row): ?>
                <?php $isLowStock = (int)$row['quantity'] < 20; ?>
                <tr class="<?= $isLowStock ? 'table-low-stock' : '' ?>">
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
                    <td class="price-cell">&#8377;<?= number_format($row['unit_price'], 2) ?></td>
                    <td class="qty-cell <?= $isLowStock ? 'low-stock' : '' ?>"><?= $row['quantity'] ?></td>
                    <td>
                        <span class="stock-status <?= $isLowStock ? 'low' : 'ok' ?>">
                            <i class="fa <?= $isLowStock ? 'fa-triangle-exclamation' : 'fa-circle-check' ?>"></i>
                            <?= $isLowStock ? 'Low Stock' : 'In Stock' ?>
                        </span>
                    </td>
                    <td class="desc-cell"><?= htmlspecialchars($row['description'] ?? '—') ?></td>
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
    document.querySelectorAll('#stockTable tbody tr').forEach(r => {
        r.style.display = r.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
}
</script>
</body>
</html>
