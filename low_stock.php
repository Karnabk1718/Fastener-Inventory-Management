<?php
session_start();
include "db.php";

if(!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$result = mysqli_query($conn, "
    SELECT s.id, s.fastener_id, f.name, f.type, f.size, f.unit_price, s.quantity, f.image, f.description
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
.main { padding: 32px 30px 50px; max-width: 1200px; margin: 0 auto; }
.page-header {
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: 28px; flex-wrap: wrap; gap: 12px;
}
.page-title { display: flex; align-items: center; gap: 12px; }
.page-title-icon {
    width: 46px; height: 46px;
    background: rgba(255,107,107,0.18); border: 1.5px solid rgba(255,107,107,0.40);
    border-radius: 12px; display: flex; align-items: center; justify-content: center;
    font-size: 20px; color: #fecaca;
}
.page-title h1 { font-size: 22px; font-weight: 600; letter-spacing: -0.3px; margin-bottom: 2px; }
.page-title p  { font-size: 13px; color: var(--muted); }
.page-header-right { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
.count-badge {
    background: rgba(255,107,107,0.16); border: 1px solid rgba(255,107,107,0.35);
    color: #fecaca; border-radius: 20px; padding: 5px 14px;
    font-size: 13px; font-weight: 600;
}
.btn-export, .btn-back {
    background: rgba(255,255,255,0.12); color: #fff; border: 1px solid rgba(255,255,255,0.18);
    border-radius: 8px; padding: 9px 16px; font-size: 13px; font-weight: 700; cursor: pointer;
    text-decoration: none; display: flex; align-items: center; gap: 7px; transition: 0.2s;
}
.btn-export:hover, .btn-back:hover { background: rgba(255,255,255,0.20); }
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
    content: ''; width: 3px; height: 14px; background: #ff8a8a;
    border-radius: 2px; display: inline-block;
}
.search-box {
    background: var(--input-bg); border: 1px solid var(--input-bdr);
    border-radius: 8px; padding: 7px 13px; color: #fff;
    font-family: inherit; font-size: 13px; font-weight: 500;
    outline: none; width: 230px; transition: border-color 0.2s;
}
.search-box::placeholder { color: rgba(255,255,255,0.40); }
.search-box:focus { border-color: #ff8a8a; }
.table-scroll { overflow-x: auto; }
table { width: 100%; border-collapse: collapse; font-size: 13.5px; min-width: 960px; }
thead tr { background: var(--thead-bg); }
th {
    padding: 13px 16px; text-align: left; font-size: 11px; font-weight: 700;
    text-transform: uppercase; letter-spacing: 1px; color: rgba(255,255,255,0.90); white-space: nowrap;
}
td {
    padding: 14px 16px; border-bottom: 1px solid var(--row-bdr);
    vertical-align: middle; color: #ffffff; font-weight: 500;
}
tbody tr:last-child td { border-bottom: none; }
tbody tr:hover { background: var(--row-hover); }
.id-cell { font-family: 'DM Mono', monospace; font-size: 12px; color: rgba(255,255,255,0.70); font-weight: 500; }
.name-cell { font-weight: 600; font-size: 14px; }
.type-pill {
    background: rgba(255,215,0,0.18); border: 1px solid rgba(255,215,0,0.40);
    color: var(--gold); border-radius: 20px; padding: 4px 12px;
    font-size: 12px; font-weight: 600; display: inline-block;
}
.size-cell { font-family: 'DM Mono', monospace; font-size: 13px; color: rgba(255,255,255,0.90); }
.price-cell { font-weight: 700; font-size: 14px; color: #ffffff; }
.qty-cell { font-family: 'DM Mono', monospace; font-size: 13px; font-weight: 600; color: #fecaca; }
.stock-status {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 4px 10px; border-radius: 999px;
    font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.6px;
    background: rgba(255,107,107,0.18); border: 1px solid rgba(255,107,107,0.35); color: #fecaca;
}
.action-cell { display: flex; gap: 6px; align-items: center; }
.btn-edit {
    background: rgba(96,165,250,0.20); border: 1px solid rgba(96,165,250,0.40);
    color: #93c5fd; border-radius: 7px; padding: 5px 13px; font-size: 12px; font-weight: 600;
    text-decoration: none; display: flex; align-items: center; gap: 5px; transition: 0.2s;
}
.btn-edit:hover { background: rgba(96,165,250,0.35); }
.thumb {
    width: 48px; height: 48px; object-fit: cover;
    border-radius: 8px; border: 1px solid var(--card-bdr); display: block;
}
.no-img {
    width: 48px; height: 48px; border-radius: 8px;
    background: rgba(255,255,255,0.07); border: 1px dashed rgba(255,255,255,0.20);
    display: flex; align-items: center; justify-content: center;
    color: rgba(255,255,255,0.30); font-size: 16px;
}
.desc-cell {
    font-size: 13px; color: var(--muted);
    max-width: 220px; white-space: normal;
    word-break: break-word; line-height: 1.55;
}
.empty-state { text-align: center; padding: 48px 20px; color: rgba(255,255,255,0.50); }
.empty-state i { font-size: 36px; margin-bottom: 12px; display: block; opacity: 0.4; }
.empty-state p { font-size: 14px; }
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
            <div class="page-title-icon"><i class="fa-solid fa-triangle-exclamation"></i></div>
            <div>
                <h1>Low Stock Inventory</h1>
                <p>Inventory items with quantity below 20 units</p>
            </div>
        </div>
        <div class="page-header-right">
            <div class="count-badge"><i class="fa fa-triangle-exclamation"></i> <?= $totalCount ?> Alerts</div>
            <a href="export_report.php?type=low_stock" class="btn-export"><i class="fa fa-file-pdf"></i> Low Stock PDF</a>
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
                    <td><span class="type-pill"><?= htmlspecialchars($row['type']) ?></span></td>
                    <td class="size-cell"><?= htmlspecialchars($row['size']) ?></td>
                    <td class="price-cell">₹<?= number_format($row['unit_price'], 2) ?></td>
                    <td class="qty-cell"><?= $row['quantity'] ?></td>
                    <td><span class="stock-status">Below Minimum</span></td>
                    <td class="desc-cell"><?= htmlspecialchars($row['description'] ?? '—') ?></td>
                    <td>
                        <div class="action-cell">
                            <a href="edit_inventory.php?id=<?= $row['id'] ?>" class="btn-edit">
                                <i class="fa fa-pen"></i> Edit Stock
                            </a>
                        </div>
                    </td>
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