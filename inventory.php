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
}

// FETCH STOCK
$result = mysqli_query($conn, "
    SELECT s.id, s.fastener_id, f.name, f.type, f.size, f.unit_price, s.quantity, f.image, f.description
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
<style>
:root {
    --bg-from:  #800020;
    --bg-to:    #cc4444;
    --text:     #ffffff;
    --muted:    rgba(255,255,255,0.65);
    --gold:     #ffd700;
    --gold-bg:  rgba(255,215,0,0.15);
    --gold-br:  rgba(255,215,0,0.40);
    --danger:   #ff6b6b;
    --success:  #4ade80;
    --blue:     #60a5fa;
    --card-bg:    rgba(0,0,0,0.40);
    --card-bdr:   rgba(255,255,255,0.16);
    --input-bg:   rgba(0,0,0,0.40);
    --input-bdr:  rgba(255,255,255,0.25);
    --thead-bg:   rgba(0,0,0,0.50);
    --row-bdr:    rgba(255,255,255,0.10);
    --row-hover:  rgba(255,255,255,0.06);
}
body.dark {
    --bg-from:  #0f172a;
    --bg-to:    #1e293b;
    --card-bg:    rgba(0,0,0,0.55);
    --card-bdr:   rgba(255,255,255,0.10);
    --input-bg:   rgba(0,0,0,0.55);
    --input-bdr:  rgba(255,255,255,0.15);
    --thead-bg:   rgba(0,0,0,0.60);
    --row-bdr:    rgba(255,255,255,0.07);
    --row-hover:  rgba(255,255,255,0.04);
}
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
body {
    font-family: 'DM Sans', 'Segoe UI', sans-serif;
    background: linear-gradient(135deg, var(--bg-from) 0%, var(--bg-to) 100%);
    color: var(--text);
    min-height: 100vh;
    transition: background 0.35s;
}
.navbar {
    display: flex; justify-content: space-between; align-items: center;
    padding: 14px 30px;
    background: rgba(0,0,0,0.40); backdrop-filter: blur(14px);
    border-bottom: 1px solid rgba(255,255,255,0.12);
    position: sticky; top: 0; z-index: 200; gap: 16px;
}
.logo { font-size: 18px; font-weight: 600; display: flex; align-items: center; gap: 9px; white-space: nowrap; }
.logo-icon {
    width: 32px; height: 32px; background: var(--gold-bg); border: 1.5px solid var(--gold);
    border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 14px;
}
.nav-links { display: flex; gap: 2px; }
.nav-links a {
    color: rgba(255,255,255,0.75); padding: 6px 12px; border-radius: 8px;
    text-decoration: none; font-size: 13px; font-weight: 500; transition: 0.2s; white-space: nowrap;
}
.nav-links a:hover  { background: rgba(255,255,255,0.12); color: #fff; }
.nav-links a.active { background: rgba(255,255,255,0.16); color: #fff; }
.nav-right { display: flex; align-items: center; gap: 8px; flex-shrink: 0; }
.pill {
    background: rgba(0,0,0,0.30); border: 1px solid rgba(255,255,255,0.14);
    border-radius: 20px; padding: 5px 11px; font-size: 12px;
    display: flex; align-items: center; gap: 5px; white-space: nowrap;
}
.pill i { font-size: 10px; opacity: 0.7; }
.icon-btn {
    width: 32px; height: 32px; background: rgba(0,0,0,0.30);
    border: 1px solid rgba(255,255,255,0.14); border-radius: 8px;
    cursor: pointer; display: flex; align-items: center; justify-content: center;
    font-size: 13px; color: var(--text); transition: 0.2s;
}
.icon-btn:hover { background: rgba(0,0,0,0.50); }
.logout-btn {
    background: var(--gold); color: #1a0a00; border: none; border-radius: 8px;
    padding: 6px 14px; font-size: 13px; font-weight: 700; cursor: pointer;
    text-decoration: none; transition: 0.2s; white-space: nowrap;
}
.logout-btn:hover { background: #ffe040; }
.main { padding: 32px 30px 50px; max-width: 1300px; margin: 0 auto; }
.page-header {
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: 28px; flex-wrap: wrap; gap: 12px;
}
.page-title { display: flex; align-items: center; gap: 12px; }
.page-title-icon {
    width: 46px; height: 46px; background: var(--gold-bg);
    border: 1.5px solid var(--gold-br); border-radius: 12px;
    display: flex; align-items: center; justify-content: center; font-size: 20px;
}
.page-title h1 { font-size: 22px; font-weight: 600; letter-spacing: -0.3px; margin-bottom: 2px; }
.page-title p  { font-size: 13px; color: var(--muted); }
.page-header-right { display: flex; align-items: center; gap: 10px; }
.count-badge {
    background: var(--gold-bg); border: 1px solid var(--gold-br);
    color: var(--gold); border-radius: 20px; padding: 5px 14px;
    font-size: 13px; font-weight: 600;
}
.count-badge.alert-badge {
    background: rgba(255,107,107,0.16); border-color: rgba(255,107,107,0.35); color: #fecaca;
    text-decoration: none; display: flex; align-items: center; gap: 6px; transition: 0.2s;
}
.count-badge.alert-badge:hover { background: rgba(255,107,107,0.28); }
.btn-export {
    background: rgba(255,255,255,0.12); color: #fff; border: 1px solid rgba(255,255,255,0.18);
    border-radius: 8px; padding: 9px 16px; font-size: 13px; font-weight: 700; cursor: pointer;
    text-decoration: none; display: flex; align-items: center; gap: 7px; transition: 0.2s;
}
.btn-export:hover { background: rgba(255,255,255,0.20); }
.btn-add {
    background: var(--gold); color: #1a0a00; border: none; border-radius: 8px;
    padding: 9px 18px; font-size: 13px; font-weight: 700; cursor: pointer;
    text-decoration: none; display: flex; align-items: center; gap: 7px; transition: 0.2s;
}
.btn-add:hover { background: #ffe040; }
.alert {
    padding: 12px 16px; border-radius: 10px; font-size: 13px; font-weight: 500;
    margin-bottom: 20px; display: flex; align-items: center; gap: 10px;
}
.alert.success { background: rgba(74,222,128,0.18); border: 1px solid rgba(74,222,128,0.35); color: var(--success); }
.table-card {
    background: var(--card-bg); border: 1px solid var(--card-bdr);
    border-radius: 16px; overflow: hidden;
}
.table-card-header {
    padding: 18px 24px; border-bottom: 1px solid var(--card-bdr);
    display: flex; align-items: center; justify-content: space-between; gap: 12px;
}
.table-card-header h3 {
    font-size: 14px; font-weight: 600; color: #fff;
    display: flex; align-items: center; gap: 8px;
}
.table-card-header h3::before {
    content: ''; width: 3px; height: 14px; background: var(--gold);
    border-radius: 2px; display: inline-block;
}
.search-box {
    background: var(--input-bg); border: 1px solid var(--input-bdr);
    border-radius: 8px; padding: 7px 13px; color: #fff;
    font-family: inherit; font-size: 13px; font-weight: 500;
    outline: none; width: 230px; transition: border-color 0.2s;
}
.search-box::placeholder { color: rgba(255,255,255,0.40); }
.search-box:focus { border-color: var(--gold); }
table { width: 100%; border-collapse: collapse; font-size: 12.5px; table-layout: fixed; }
thead tr { background: var(--thead-bg); }
th {
    padding: 10px 10px; text-align: left; font-size: 10px; font-weight: 700;
    text-transform: uppercase; letter-spacing: 0.8px; color: rgba(255,255,255,0.90); white-space: nowrap;
    overflow: hidden;
}
/* Fixed column widths so everything fits */
th:nth-child(1)  { width: 44px;  }   /* ID */
th:nth-child(2)  { width: 72px;  }   /* Image */
th:nth-child(3)  { width: 130px; }   /* Fastener Name */
th:nth-child(4)  { width: 90px;  }   /* Type */
th:nth-child(5)  { width: 70px;  }   /* Size */
th:nth-child(6)  { width: 90px;  }   /* Price */
th:nth-child(7)  { width: 62px;  }   /* Quantity */
th:nth-child(8)  { width: 88px;  }   /* Status */
th:nth-child(9)  { width: 180px; }   /* Description */
th:nth-child(10) { width: 100px; }   /* Actions */
td {
    padding: 10px 10px; border-bottom: 1px solid var(--row-bdr);
    vertical-align: middle; color: #ffffff; font-weight: 500;
    overflow: hidden;
}
tbody tr:last-child td { border-bottom: none; }
tbody tr:hover { background: var(--row-hover); }

/* ── Cell styles — pixel-perfect match to fastener.php ── */
.id-cell   { font-family: 'DM Mono', monospace; font-size: 12.5px; color: rgba(255,255,255,0.70); font-weight: 500; }
.name-cell { font-weight: 600; font-size: 14px; white-space: normal; word-break: break-word; }
.type-pill {
    background: rgba(255,215,0,0.18); border: 1px solid rgba(255,215,0,0.40);
    color: var(--gold); border-radius: 20px; padding: 4px 10px;
    font-size: 12px; font-weight: 600; display: inline-block; white-space: nowrap;
}
.size-cell  { font-family: 'DM Mono', monospace; font-size: 13px; color: rgba(255,255,255,0.90); }
.price-cell { font-weight: 700; font-size: 13.5px; color: #ffffff; white-space: nowrap; }
.qty-cell   { font-family: 'DM Mono', monospace; font-size: 13.5px; font-weight: 600; text-align: center; }
.qty-cell.low-stock { color: #fecaca; }
.desc-cell {
    font-size: 13px; color: var(--muted);
    white-space: normal; word-break: break-word; line-height: 1.55;
}

/* ── Stock status badge ── */
.stock-status {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 3px 7px; border-radius: 999px;
    font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.4px; white-space: nowrap;
}
.stock-status.low { background: rgba(255,107,107,0.18); border: 1px solid rgba(255,107,107,0.35); color: #fecaca; }
.stock-status.ok  { background: rgba(74,222,128,0.18);  border: 1px solid rgba(74,222,128,0.35);  color: #bbf7d0; }
tbody tr.table-low-stock { background: rgba(255,107,107,0.04); }

.action-cell { display: flex; flex-direction: column; gap: 5px; align-items: stretch; }
.btn-edit {
    background: rgba(96,165,250,0.20); border: 1px solid rgba(96,165,250,0.40);
    color: #93c5fd; border-radius: 6px; padding: 5px 8px; font-size: 11px; font-weight: 600;
    text-decoration: none; display: flex; align-items: center; justify-content: center; gap: 4px; transition: 0.2s;
}
.btn-edit:hover { background: rgba(96,165,250,0.35); }
.btn-delete {
    background: rgba(255,107,107,0.20); border: 1px solid rgba(255,107,107,0.40);
    color: #fca5a5; border-radius: 6px; padding: 5px 8px; font-size: 11px; font-weight: 600;
    text-decoration: none; display: flex; align-items: center; justify-content: center; gap: 4px; transition: 0.2s;
}
.btn-delete:hover { background: rgba(255,107,107,0.35); }
.empty-state { text-align: center; padding: 48px 20px; color: rgba(255,255,255,0.50); }
.empty-state i { font-size: 36px; margin-bottom: 12px; display: block; opacity: 0.4; }
.empty-state p { font-size: 14px; }
.thumb {
    width: 56px; height: 56px; object-fit: cover;
    border-radius: 8px; border: 1px solid var(--card-bdr); display: block;
}
.no-img {
    width: 56px; height: 56px; border-radius: 8px;
    background: rgba(255,255,255,0.07); border: 1px dashed rgba(255,255,255,0.20);
    display: flex; align-items: center; justify-content: center;
    color: rgba(255,255,255,0.30); font-size: 18px;
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
        <a href="inventory.php" class="active">Inventory</a>
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
            <a href="export_report.php?type=inventory" class="btn-export"><i class="fa fa-file-pdf"></i> Inventory PDF</a>
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
                    <th>Type</th>
                    <th>Size</th>
                    <th>Price / Unit (₹)</th>
                    <th>Quantity</th>
                    <th>Status</th>
                    <th>Description</th>
                    <th>Actions</th>
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
                    <td>
                        <div class="action-cell">
                            <a href="edit_inventory.php?id=<?= $row['id'] ?>" class="btn-edit">
                                <i class="fa fa-pen"></i> Edit
                            </a>
                            <a href="?delete=<?= $row['id'] ?>" class="btn-delete"
                               onclick="return confirm('Delete this stock record?')">
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
function filterTable() {
    const q = document.getElementById('searchInput').value.toLowerCase();
    document.querySelectorAll('#stockTable tbody tr').forEach(r => {
        r.style.display = r.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
}
</script>
</body>
</html>