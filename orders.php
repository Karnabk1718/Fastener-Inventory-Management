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
    SELECT orders.*, fastener.name AS fname, supplier.name AS sname
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
<style>
:root{--bg-from:#800020;--bg-to:#cc4444;--text:#ffffff;--muted:rgba(255,255,255,0.65);--gold:#ffd700;--gold-bg:rgba(255,215,0,0.15);--gold-br:rgba(255,215,0,0.40);--danger:#ff6b6b;--success:#4ade80;--card-bg:rgba(0,0,0,0.40);--card-bdr:rgba(255,255,255,0.16);--input-bg:rgba(0,0,0,0.40);--input-bdr:rgba(255,255,255,0.25);--thead-bg:rgba(0,0,0,0.50);--row-bdr:rgba(255,255,255,0.10);--row-hover:rgba(255,255,255,0.06);}
body.dark{--bg-from:#0f172a;--bg-to:#1e293b;--card-bg:rgba(0,0,0,0.55);--card-bdr:rgba(255,255,255,0.10);--input-bg:rgba(0,0,0,0.55);--input-bdr:rgba(255,255,255,0.15);--thead-bg:rgba(0,0,0,0.60);--row-bdr:rgba(255,255,255,0.07);--row-hover:rgba(255,255,255,0.04);}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
body{font-family:'DM Sans','Segoe UI',sans-serif;background:linear-gradient(135deg,var(--bg-from) 0%,var(--bg-to) 100%);color:var(--text);min-height:100vh;transition:background 0.35s;}
.navbar{display:flex;justify-content:space-between;align-items:center;padding:14px 30px;background:rgba(0,0,0,0.40);backdrop-filter:blur(14px);border-bottom:1px solid rgba(255,255,255,0.12);position:sticky;top:0;z-index:200;gap:16px;}
.logo{font-size:18px;font-weight:600;display:flex;align-items:center;gap:9px;white-space:nowrap;}
.logo-icon{width:32px;height:32px;background:var(--gold-bg);border:1.5px solid var(--gold);border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:14px;}
.nav-links{display:flex;gap:2px;}
.nav-links a{color:rgba(255,255,255,0.75);padding:6px 12px;border-radius:8px;text-decoration:none;font-size:13px;font-weight:500;transition:0.2s;white-space:nowrap;}
.nav-links a:hover{background:rgba(255,255,255,0.12);color:#fff;}
.nav-links a.active{background:rgba(255,255,255,0.16);color:#fff;}
.nav-right{display:flex;align-items:center;gap:8px;flex-shrink:0;}
.pill{background:rgba(0,0,0,0.30);border:1px solid rgba(255,255,255,0.14);border-radius:20px;padding:5px 11px;font-size:12px;display:flex;align-items:center;gap:5px;white-space:nowrap;}
.pill i{font-size:10px;opacity:0.7;}
.icon-btn{width:32px;height:32px;background:rgba(0,0,0,0.30);border:1px solid rgba(255,255,255,0.14);border-radius:8px;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:13px;color:var(--text);transition:0.2s;}
.icon-btn:hover{background:rgba(0,0,0,0.50);}
.logout-btn{background:var(--gold);color:#1a0a00;border:none;border-radius:8px;padding:6px 14px;font-size:13px;font-weight:700;cursor:pointer;text-decoration:none;transition:0.2s;white-space:nowrap;}
.logout-btn:hover{background:#ffe040;}
.main{padding:32px 30px 50px;max-width:1300px;margin:0 auto;}
.page-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:28px;flex-wrap:wrap;gap:12px;}
.page-title{display:flex;align-items:center;gap:12px;}
.page-title-icon{width:46px;height:46px;background:var(--gold-bg);border:1.5px solid var(--gold-br);border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:20px;}
.page-title h1{font-size:22px;font-weight:600;letter-spacing:-0.3px;margin-bottom:2px;}
.page-title p{font-size:13px;color:var(--muted);}
.page-header-right{display:flex;align-items:center;gap:10px;}
.count-badge{background:var(--gold-bg);border:1px solid var(--gold-br);color:var(--gold);border-radius:20px;padding:5px 14px;font-size:13px;font-weight:600;}
.btn-export{background:rgba(255,255,255,0.12);color:#fff;border:1px solid rgba(255,255,255,0.18);border-radius:8px;padding:9px 16px;font-size:13px;font-weight:700;cursor:pointer;text-decoration:none;display:flex;align-items:center;gap:7px;transition:0.2s;}
.btn-export:hover{background:rgba(255,255,255,0.20);}
.btn-add{background:var(--gold);color:#1a0a00;border:none;border-radius:8px;padding:9px 18px;font-size:13px;font-weight:700;cursor:pointer;text-decoration:none;display:flex;align-items:center;gap:7px;transition:0.2s;}
.btn-add:hover{background:#ffe040;}
.alert{padding:12px 16px;border-radius:10px;font-size:13px;font-weight:500;margin-bottom:20px;display:flex;align-items:center;gap:10px;}
.alert.success{background:rgba(74,222,128,0.18);border:1px solid rgba(74,222,128,0.35);color:var(--success);}
.table-card{background:var(--card-bg);border:1px solid var(--card-bdr);border-radius:16px;overflow:hidden;}
.table-card-header{padding:18px 24px;border-bottom:1px solid var(--card-bdr);display:flex;align-items:center;justify-content:space-between;gap:12px;}
.table-card-header h3{font-size:14px;font-weight:600;color:#fff;display:flex;align-items:center;gap:8px;}
.table-card-header h3::before{content:'';width:3px;height:14px;background:var(--gold);border-radius:2px;display:inline-block;}
.search-box{background:var(--input-bg);border:1px solid var(--input-bdr);border-radius:8px;padding:7px 13px;color:#fff;font-family:inherit;font-size:13px;font-weight:500;outline:none;width:230px;transition:border-color 0.2s;}
.search-box::placeholder{color:rgba(255,255,255,0.40);}
.search-box:focus{border-color:var(--gold);}
table{width:100%;border-collapse:collapse;font-size:13.5px;}
thead tr{background:var(--thead-bg);}
th{padding:13px 16px;text-align:left;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:rgba(255,255,255,0.90);white-space:nowrap;}
td{padding:14px 16px;border-bottom:1px solid var(--row-bdr);vertical-align:middle;color:#ffffff;font-weight:500;}
tbody tr:last-child td{border-bottom:none;}
tbody tr:hover{background:var(--row-hover);}
.id-cell{font-family:'DM Mono',monospace;font-size:12px;color:rgba(255,255,255,0.70);font-weight:500;}
.name-cell{font-weight:600;font-size:14px;}
.qty-cell{font-family:'DM Mono',monospace;font-size:13px;font-weight:600;}

/* ── Date + Time combined cell ── */
.datetime-cell{
    font-family:'DM Mono',monospace;
    font-size:12.5px;
    color:rgba(255,255,255,0.90);
    line-height:1.6;
    white-space:nowrap;
}
.datetime-cell .dt-date{
    display:flex;align-items:center;gap:5px;
    color:#ffffff;font-weight:600;
}
.datetime-cell .dt-time{
    display:flex;align-items:center;gap:5px;
    color:rgba(255,215,0,0.85);font-size:12px;margin-top:2px;
}
.datetime-cell i{font-size:10px;opacity:0.75;}

.status-badge{display:inline-flex;align-items:center;gap:6px;border-radius:20px;padding:5px 12px;font-size:12px;font-weight:600;text-decoration:none;cursor:pointer;transition:0.2s;}
.status-badge.pending{background:rgba(251,191,36,0.18);border:1px solid rgba(251,191,36,0.40);color:#fbbf24;}
.status-badge.delivered{background:rgba(74,222,128,0.18);border:1px solid rgba(74,222,128,0.35);color:#4ade80;}
.status-badge.not-delivered{background:rgba(255,107,107,0.18);border:1px solid rgba(255,107,107,0.35);color:#fca5a5;}
.status-badge.pending:hover,.status-badge.delivered:hover{opacity:0.80;}
.action-cell{display:flex;gap:6px;align-items:center;}
.btn-edit{background:rgba(96,165,250,0.20);border:1px solid rgba(96,165,250,0.40);color:#93c5fd;border-radius:7px;padding:5px 13px;font-size:12px;font-weight:600;text-decoration:none;display:flex;align-items:center;gap:5px;transition:0.2s;}
.btn-edit:hover{background:rgba(96,165,250,0.35);}
.btn-delete{background:rgba(255,107,107,0.20);border:1px solid rgba(255,107,107,0.40);color:#fca5a5;border-radius:7px;padding:5px 13px;font-size:12px;font-weight:600;text-decoration:none;display:flex;align-items:center;gap:5px;transition:0.2s;}
.btn-delete:hover{background:rgba(255,107,107,0.35);}
.empty-state{text-align:center;padding:48px 20px;color:rgba(255,255,255,0.50);}
.empty-state i{font-size:36px;margin-bottom:12px;display:block;opacity:0.4;}
.empty-state p{font-size:14px;}
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
        <a href="inventory.php">Inventory</a>
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
            <a href="export_report.php?type=orders" class="btn-export"><i class="fa fa-file-pdf"></i> Orders PDF</a>
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
                    <td class="name-cell"><?= htmlspecialchars($row['fname']) ?></td>
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
function filterTable() {
    const q = document.getElementById('searchInput').value.toLowerCase();
    document.querySelectorAll('#ordersTable tbody tr').forEach(r => {
        r.style.display = r.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
}
</script>
</body>
</html>