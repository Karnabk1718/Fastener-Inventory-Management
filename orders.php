<?php
session_start();
include "db.php";

if(!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$successMsg = '';

// UPDATE STATUS
if(isset($_GET['status'])) {
    $id         = (int)$_GET['id'];
    $new_status = $_GET['status'] === 'Delivered' ? 'Pending' : 'Delivered';
    mysqli_query($conn, "UPDATE orders SET status='$new_status' WHERE order_id=$id");
    header("Location: orders.php?success=status");
    exit();
}

// DELETE
if(isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    mysqli_query($conn, "DELETE FROM orders WHERE order_id=$id");
    header("Location: orders.php?success=deleted");
    exit();
}

if(isset($_GET['success'])) {
    if($_GET['success'] === 'added')   $successMsg = 'Order added successfully.';
    if($_GET['success'] === 'updated') $successMsg = 'Order updated successfully.';
    if($_GET['success'] === 'deleted') $successMsg = 'Order deleted successfully.';
    if($_GET['success'] === 'status')  $successMsg = 'Order status updated.';
}

// FETCH ALL
$result = mysqli_query($conn, "
    SELECT orders.*, fastener.name AS fname, fastener.part_number, supplier.name AS sname
    FROM orders
    JOIN fastener ON orders.fastener_id = fastener.id
    JOIN supplier ON orders.supplier_id = supplier.id
    ORDER BY orders.order_id ASC
");
$rows = [];
while($r = mysqli_fetch_assoc($result)) $rows[] = $r;
usort($rows, fn($a,$b) => $a['order_id'] - $b['order_id']);
$totalCount = count($rows);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Orders – Bolt Base</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&family=DM+Mono:wght@500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="css/user.css">
</head>
<body class="page-orders">

<div class="navbar">
    <div class="logo">
        <div class="logo-icon"><i class="fa-solid fa-screwdriver-wrench" style="color:var(--gold);font-size:13px"></i></div>
        BOLT BASE
    </div>
    <div class="nav-links">
        <a href="dashboard.php">Dashboard</a>
        <a href="fastener.php">Fasteners</a>
        <a href="inventory.php">Inventory</a>
        <a href="pick_list.php">Pick List</a>
        <a href="supplier.php">Suppliers</a>
        <a href="orders.php" class="active">Orders</a>
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
            <div class="page-title-icon"><i class="fa-solid fa-box-open"></i></div>
            <div>
                <h1>Orders Management</h1>
                <p>Place, edit, and track all fastener orders</p>
            </div>
        </div>
        <div class="page-header-right">
            <div class="count-badge"><i class="fa fa-layer-group"></i> <?= $totalCount ?> Records</div>
            <a href="export_report.php?type=orders" class="btn-export"><i class="fa fa-file-pdf"></i> Download</a>
            <a href="add_orders.php" class="btn-add"><i class="fa fa-plus"></i> Add Order</a>
        </div>
    </div>

    <?php if($successMsg): ?>
    <div class="alert success"><i class="fa fa-circle-check"></i> <?= $successMsg ?></div>
    <?php endif; ?>

    <div class="table-card">
        <div class="table-card-header">
            <h3>All Orders</h3>
            <input type="text" class="search-box" id="searchInput" placeholder="Search orders…" onkeyup="filterTable()">
        </div>
        <table id="ordersTable">
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Fastener</th>
                    <th>Supplier</th>
                    <th>Quantity</th>
                    <th>Order Date &amp; Time</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if($totalCount === 0): ?>
                <tr><td colspan="7">
                    <div class="empty-state">
                        <i class="fa-solid fa-box-open"></i>
                        <p>No orders found. <a href="add_orders.php" style="color:var(--gold)">Add one now.</a></p>
                    </div>
                </td></tr>
                <?php else: foreach($rows as $index => $row):
                    $status      = $row['status'] ?? 'Pending';
                    // order_date is a TIMESTAMP — contains both date and time
                    $orderDT     = new DateTime($row['order_date']);
                    $today       = new DateTime(date('Y-m-d'));
                    $daysDiff    = (int)$today->diff($orderDT)->days;
                    $isExpired   = ($daysDiff > 30 && $status === 'Pending');
                    $displayDate = $orderDT->format('d-m-Y');
                    $displayTime = $orderDT->format('h:i:s A'); // e.g. 02:35:47 PM

                    if($isExpired) {
                        $statusClass = 'not-delivered'; $statusIcon = 'fa-circle-xmark'; $statusLabel = 'Not Delivered'; $toggleUrl = null;
                    } elseif($status === 'Delivered') {
                        $statusClass = 'delivered'; $statusIcon = 'fa-circle-check'; $statusLabel = 'Delivered';
                        $toggleUrl = "?status=".urlencode($status)."&id=".$row['order_id'];
                    } else {
                        $statusClass = 'pending'; $statusIcon = 'fa-clock'; $statusLabel = 'Pending';
                        $toggleUrl = "?status=".urlencode($status)."&id=".$row['order_id'];
                    }
                ?>
                <tr>
                    <td class="id-cell">#<?= $index + 1 ?></td>
                    <td class="name-cell">
                        <?= htmlspecialchars($row['fname']) ?>
                        <span class="part-sub"><?= htmlspecialchars($row['part_number'] ?? '—') ?></span>
                    </td>
                    <td><?= htmlspecialchars($row['sname']) ?></td>
                    <td class="qty-cell"><?= $row['quantity'] ?></td>
                    <td>
                        <div class="datetime-cell">
                            <div class="dt-date"><i class="fa fa-calendar-day"></i><?= $displayDate ?></div>
                            <div class="dt-time"><i class="fa fa-clock"></i><?= $displayTime ?></div>
                        </div>
                    </td>
                    <td>
                        <?php if($toggleUrl): ?>
                        <a href="<?= $toggleUrl ?>" class="status-badge <?= $statusClass ?>" title="Click to toggle status">
                            <i class="fa <?= $statusIcon ?>"></i> <?= $statusLabel ?>
                        </a>
                        <?php else: ?>
                        <span class="status-badge <?= $statusClass ?>" title="Order exceeded 30 days without delivery">
                            <i class="fa <?= $statusIcon ?>"></i> <?= $statusLabel ?>
                        </span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="action-cell">
                            <a href="?delete=<?= $row['order_id'] ?>" class="btn-delete"
                               onclick="return confirm('Delete this order?')">
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
    document.querySelectorAll('#ordersTable tbody tr').forEach(r => {
        r.style.display = r.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
}
</script>
</body>
</html>
