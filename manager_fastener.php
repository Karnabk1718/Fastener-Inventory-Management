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
<link rel="stylesheet" href="css/manager.css">

</head>
<body class="page-manager_fastener">
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
function tick(){const n=new Date();document.getElementById('dateStr').textContent=n.toLocaleDateString('en-IN');const timeEl = document.getElementById('timeStr'); if (timeEl) timeEl.textContent = n.toLocaleTimeString('en-IN');}
setInterval(tick,1000);tick();
function toggleDark(){document.body.classList.toggle('dark');localStorage.setItem('boltTheme',document.body.classList.contains('dark')?'dark':'light');}
if(localStorage.getItem('boltTheme')==='dark')document.body.classList.add('dark');
function filterTable(){const q=document.getElementById('searchInput').value.toLowerCase();document.querySelectorAll('#mainTable tbody tr').forEach(r=>{r.style.display=r.textContent.toLowerCase().includes(q)?'':''+(r.style.display='none')||'';});}
function filterTable(){const q=document.getElementById('searchInput').value.toLowerCase();document.querySelectorAll('#mainTable tbody tr').forEach(r=>{r.style.display=r.textContent.toLowerCase().includes(q)?'':'none';});}
</script>
</body>
</html>

