<?php
session_start();
include "db.php";

if(!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$errorMsg = '';

if(isset($_POST['add'])) {
    $fastener_id = (int)$_POST['fastener_id'];
    $supplier_id = (int)$_POST['supplier_id'];
    $quantity    = (int)$_POST['quantity'];

    if($fastener_id == 0 || $supplier_id == 0) {
        $errorMsg = 'Please select both a Fastener and a Supplier.';
    } elseif($quantity <= 0) {
        $errorMsg = 'Quantity must be greater than 0.';
    } else {
        // NOW() saves full date + time so orders.php can display hours, minutes, seconds
        mysqli_query($conn, "INSERT INTO orders(fastener_id,supplier_id,quantity,order_date,status)
            VALUES('$fastener_id','$supplier_id','$quantity',NOW(),'Pending')");
        header("Location: orders.php?success=added");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Add Order – Bolt Base</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&family=DM+Mono:wght@500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="css/user.css">
</head>
<body class="page-add_orders">

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
        <div class="page-title-icon"><i class="fa-solid fa-plus"></i></div>
        <div>
            <h1>Add New Order</h1>
            <p>Fill in the details below to place an order</p>
        </div>
    </div>

    <?php if($errorMsg): ?>
    <div class="alert error"><i class="fa fa-circle-exclamation"></i> <?= $errorMsg ?></div>
    <?php endif; ?>

    <div class="form-card">
        <div class="form-card-title"><i class="fa fa-plus"></i> Order Details</div>
        <form method="POST">
            <div class="form-grid">
                <div class="field">
                    <label>Fastener</label>
                    <select name="fastener_id" required>
                        <option value="">Select Fastener</option>
                        <?php
                        $f = mysqli_query($conn, "SELECT * FROM fastener ORDER BY name");
                        while($row = mysqli_fetch_assoc($f)):
                            $sel = (isset($_POST['fastener_id']) && $_POST['fastener_id'] == $row['id']) ? 'selected' : '';
                        ?>
                        <option value="<?= $row['id'] ?>" <?= $sel ?>><?= htmlspecialchars($row['name']) ?><?= !empty($row['part_number']) ? ' - ' . htmlspecialchars($row['part_number']) : '' ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="field">
                    <label>Supplier</label>
                    <select name="supplier_id" required>
                        <option value="">Select Supplier</option>
                        <?php
                        $s = mysqli_query($conn, "SELECT * FROM supplier ORDER BY name");
                        while($row = mysqli_fetch_assoc($s)):
                            $sel = (isset($_POST['supplier_id']) && $_POST['supplier_id'] == $row['id']) ? 'selected' : '';
                        ?>
                        <option value="<?= $row['id'] ?>" <?= $sel ?>><?= htmlspecialchars($row['name']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="field" style="grid-column:1/-1;">
                    <label>Quantity</label>
                    <input type="number" name="quantity" placeholder="e.g. 50" min="1" required
                           value="<?= htmlspecialchars($_POST['quantity'] ?? '') ?>">
                </div>
                <div class="form-actions">
                    <button type="submit" name="add" class="btn-primary">
                        <i class="fa fa-plus"></i> Add Order
                    </button>
                    <a href="orders.php" class="btn-cancel">
                        <i class="fa fa-xmark"></i> Cancel
                    </a>
                </div>
            </div>
        </form>
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
</script>
</body>
</html>
