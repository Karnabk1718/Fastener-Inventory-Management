<?php
session_start();
include "db.php";
include "manager_auth.php";

$result = mysqli_query($conn,"SELECT * FROM supplier ORDER BY id ASC");
$rows = [];
while($r = mysqli_fetch_assoc($result)) $rows[] = $r;
usort($rows, fn($a,$b) => $a['id'] - $b['id']);
$totalCount = count($rows);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Suppliers – Manager – Bolt Base</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&family=DM+Mono:wght@500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="css/manager.css">

</head>
<body class="page-manager_suppliers">
<div class="navbar">
    <div class="logo">
        <div class="logo-icon"><i class="fa-solid fa-screwdriver-wrench" style="color:var(--gold);font-size:13px"></i></div>
        BOLT BASE &nbsp;<span class="role-chip"><i class="fa fa-user-tie"></i> Manager</span>
    </div>
    <div class="nav-links">
        <a href="manager_dashboard.php">Dashboard</a>
        <a href="manager_fasteners.php">Fasteners</a>
        <a href="manager_inventory.php">Inventory</a>
        <a href="manager_suppliers.php" class="active">Suppliers</a>
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
            <div class="page-title-icon"><i class="fa-solid fa-industry"></i></div>
            <div><h1>Suppliers</h1><p>View all supplier records and contracts</p></div>
        </div>
        <div style="display:flex;gap:10px;align-items:center;">
            <div class="count-badge"><i class="fa fa-layer-group"></i> <?= $totalCount ?> Records</div>
            <a href="manager_report_export.php?type=supplier" class="btn-export"><i class="fa fa-file-pdf"></i> Download</a>
        </div>
    </div>
    <div class="table-card">
        <div class="table-card-header">
            <h3>All Suppliers</h3>
            <input type="text" class="search-box" id="searchInput" placeholder="Search suppliers…" onkeyup="filterTable()">
        </div>
        <table id="mainTable">
            <thead><tr><th>ID</th><th>Name</th><th>Contact</th><th>Address</th><th>Contract Date</th></tr></thead>
            <tbody>
            <?php if($totalCount===0): ?>
            <tr><td colspan="5" style="text-align:center;padding:40px;color:var(--muted)">No suppliers found.</td></tr>
            <?php else: foreach($rows as $i => $row): ?>
            <tr>
                <td class="id-cell">#<?= $i+1 ?></td>
                <td class="name-cell"><?= htmlspecialchars($row['name']) ?></td>
                <td class="contact-cell"><?= htmlspecialchars($row['contact']) ?></td>
                <td><?= htmlspecialchars($row['address']) ?></td>
                <td class="date-cell"><?= htmlspecialchars($row['contract_date']) ?></td>
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
function filterTable(){const q=document.getElementById('searchInput').value.toLowerCase();document.querySelectorAll('#mainTable tbody tr').forEach(r=>{r.style.display=r.textContent.toLowerCase().includes(q)?'':'none';});}
</script>
</body>
</html>

