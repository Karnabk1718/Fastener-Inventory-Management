<?php
session_start();
include "db.php";
include "manager_auth.php";

$result = mysqli_query($conn,"
    SELECT s.id, f.name, f.part_number, f.type, f.size, f.unit_price, s.quantity, f.image
    FROM stock s JOIN fastener f ON s.fastener_id=f.id
    ORDER BY s.id ASC
");
$rows = [];
while($r = mysqli_fetch_assoc($result)) $rows[] = $r;
$totalCount = count($rows);
$lowCount   = count(array_filter($rows, fn($r) => $r['quantity'] < 20));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Inventory – Manager – Bolt Base</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&family=DM+Mono:wght@500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="css/manager.css">

</head>
<body class="page-manager_inventory">
<div class="navbar">
    <div class="logo">
        <div class="logo-icon"><i class="fa-solid fa-screwdriver-wrench" style="color:var(--gold);font-size:13px"></i></div>
        BOLT BASE &nbsp;<span class="role-chip"><i class="fa fa-user-tie"></i> Manager</span>
    </div>
    <div class="nav-links">
        <a href="manager_dashboard.php">Dashboard</a>
        <a href="manager_fasteners.php">Fasteners</a>
        <a href="manager_inventory.php" class="active">Inventory</a>
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
            <div class="page-title-icon"><i class="fa-solid fa-boxes-stacked"></i></div>
            <div><h1>Inventory</h1><p>Current stock levels for all fasteners</p></div>
        </div>
        <div class="badge-row">
            <div class="count-badge"><i class="fa fa-layer-group"></i> <?= $totalCount ?> Items</div>
            <?php if($lowCount > 0): ?>
            <div class="low-badge"><i class="fa fa-triangle-exclamation"></i> <?= $lowCount ?> Low Stock</div>
            <?php endif; ?>
            <a href="manager_report_export.php?type=inventory" class="btn-export"><i class="fa fa-file-pdf"></i> Download</a>
            <a href="manager_report_export.php?type=low_stock" class="btn-export"><i class="fa fa-triangle-exclamation"></i> Download</a>
        </div>
    </div>
    <div class="table-card">
        <div class="table-card-header">
            <h3>Stock Levels</h3>
            <input type="text" class="search-box" id="searchInput" placeholder="Search inventory…" onkeyup="filterTable()">
        </div>
        <div class="table-scroll">
        <table id="mainTable">
            <thead><tr><th>ID</th><th>Image</th><th>Fastener</th><th>Part No.</th><th>Type</th><th>Size</th><th>Price (₹)</th><th>Quantity</th><th>Status</th></tr></thead>
            <tbody>
            <?php if($totalCount===0): ?>
            <tr><td colspan="9" style="text-align:center;padding:40px;color:var(--muted)">No inventory records found.</td></tr>
            <?php else: foreach($rows as $i => $row): $low = $row['quantity'] < 20; ?>
            <tr class="<?= $low ? 'low-row' : '' ?>">
                <td class="id-cell">#<?= $i+1 ?></td>
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
                <td class="part-cell"><?= htmlspecialchars($row['part_number'] ?? '—') ?></td>
                <td><span class="type-pill"><?= htmlspecialchars($row['type']) ?></span></td>
                <td style="font-family:'DM Mono',monospace"><?= htmlspecialchars($row['size']) ?></td>
                <td style="font-weight:700">₹<?= number_format($row['unit_price'],2) ?></td>
                <td class="<?= $low ? 'qty-low' : 'qty-ok' ?>"><?= $row['quantity'] ?></td>
                <td><span class="<?= $low ? 'stock-low' : 'stock-ok' ?>"><?= $low ? '⚠ Low Stock' : '✓ In Stock' ?></span></td>
            </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
        </div>
    </div>
</div>
<script>
function tick(){const n=new Date();document.getElementById('dateStr').textContent=n.toLocaleDateString('en-IN');const timeEl = document.getElementById('timeStr'); if (timeEl) timeEl.textContent = n.toLocaleTimeString('en-IN');}
setInterval(tick,1000);tick();
function toggleDark(){document.body.classList.toggle('dark');localStorage.setItem('boltTheme',document.body.classList.contains('dark')?'dark':'light');}
if(localStorage.getItem('boltTheme')==='dark')document.body.classList.add('dark');
function filterTable(){const q=document.getElementById('searchInput').value.toLowerCase();document.querySelectorAll('#mainTable tbody tr').forEach(r=>{r.style.display=r.textContent.toLowerCase().includes(q)?'':'none';});}
</script>
</body>
</html>

