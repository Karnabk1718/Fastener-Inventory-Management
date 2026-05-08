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
    mysqli_query($conn, "DELETE FROM fastener WHERE id=$id");
    header("Location: fastener.php?success=deleted");
    exit();
}

if(isset($_GET['success'])) {
    if($_GET['success'] === 'added')   $successMsg = 'Fastener added successfully.';
    if($_GET['success'] === 'updated') $successMsg = 'Fastener updated successfully.';
    if($_GET['success'] === 'deleted') $successMsg = 'Fastener deleted successfully.';
}

// FETCH ALL
$result     = mysqli_query($conn, "SELECT * FROM fastener ORDER BY id ASC");
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
<title>Fasteners – Bolt Base</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&family=DM+Mono:wght@500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="css/user.css">
</head>
<body class="page-fastener">

<div class="navbar">
    <div class="logo">
        <div class="logo-icon"><i class="fa-solid fa-screwdriver-wrench" style="color:var(--gold);font-size:13px"></i></div>
        BOLT BASE
    </div>
    <div class="nav-links">
        <a href="dashboard.php">Dashboard</a>
        <a href="fastener.php" class="active">Fasteners</a>
        <a href="inventory.php">Inventory</a>
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
            <div class="page-title-icon"><i class="fa-solid fa-screwdriver-wrench"></i></div>
            <div>
                <h1>Fastener Management</h1>
                <p>View and manage all fastener records</p>
            </div>
        </div>
        <div class="page-header-right">
            <div class="count-badge"><i class="fa fa-layer-group"></i> <?= $totalCount ?> Records</div>
            <a href="export_report.php?type=fastener" class="btn-export"><i class="fa fa-file-pdf"></i> Download</a>
            <a href="add_fastener.php" class="btn-add"><i class="fa fa-plus"></i> Add Fastener</a>
        </div>
    </div>

    <?php if($successMsg): ?>
    <div class="alert success"><i class="fa fa-circle-check"></i> <?= $successMsg ?></div>
    <?php endif; ?>

    <div class="table-card">
        <div class="table-card-header">
            <h3>All Fasteners</h3>
            <input type="text" class="search-box" id="searchInput" placeholder="Search fasteners…" onkeyup="filterTable()">
        </div>
        <div class="table-scroll">
        <table id="fastenerTable">
            <thead>
                <tr>
                    <th>ID</th><th>Image</th><th>Name</th><th>Part No.</th><th>Type</th><th>Size</th><th>Price / Unit (₹)</th><th>Description</th><th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if($totalCount === 0): ?>
                <tr><td colspan="9">
                    <div class="empty-state">
                        <i class="fa-solid fa-screwdriver-wrench"></i>
                        <p>No fasteners found. <a href="add_fastener.php" style="color:var(--gold)">Add one now.</a></p>
                    </div>
                </td></tr>
                <?php else: foreach($rows as $index => $row): ?>
                <tr>
                    <td class="id-cell">#<?= $index + 1 ?></td>
                    <td>
                        <?php if(!empty($row['image'])): ?>
                            <?php
                                // Build a reliable image URL relative to this script's web location
                                $imgSrc = rtrim(dirname($_SERVER['PHP_SELF']), '/') . '/uploads/' . htmlspecialchars($row['image']);
                            ?>
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
                    <td class="desc-cell"><?= htmlspecialchars($row['description'] ?? '—') ?></td>
                    <td>
                        <div class="action-cell">
                            <a href="edit_fastener.php?id=<?= $row['id'] ?>" class="btn-edit">
                                <i class="fa fa-pen"></i> Edit
                            </a>
                            <a href="?delete=<?= $row['id'] ?>" class="btn-delete"
                               onclick="return confirm('Delete this fastener?')">
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
    document.querySelectorAll('#fastenerTable tbody tr').forEach(r => {
        r.style.display = r.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
}
</script>
</body>
</html>
