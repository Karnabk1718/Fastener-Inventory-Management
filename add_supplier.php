<?php
session_start();
include "db.php";

if(!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$errorMsg = '';

if(isset($_POST['add'])) {
    $name    = trim($_POST['name']);
    $contact = trim($_POST['contact']);
    $address = trim($_POST['address']);
    $date    = $_POST['contract_date'];

    if(!valid_name($name)) {
        $errorMsg = 'Name must contain only letters, spaces, dots, apostrophes, or hyphens.';
    } elseif(!valid_phone($contact)) {
        $errorMsg = 'Phone number must be 10 digits and start with 6–9.';
    } else {
        try {
            $stmt = mysqli_prepare($conn, "INSERT INTO supplier(name,contact,address,contract_date) VALUES(?,?,?,?)");
            mysqli_stmt_bind_param($stmt, "ssss", $name, $contact, $address, $date);
            mysqli_stmt_execute($stmt);
            header("Location: supplier.php?success=added");
            exit();
        } catch (Throwable $e) {
            error_log($e->getMessage());
            $errorMsg = str_contains($e->getMessage(), 'Duplicate') ? 'This supplier already exists.' : 'Failed to add supplier. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Add Supplier – Bolt Base</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&family=DM+Mono:wght@500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="css/user.css">
</head>
<body class="page-add_supplier">

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
        <a href="supplier.php" class="active">Suppliers</a>
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
        <div class="page-title-icon"><i class="fa-solid fa-plus"></i></div>
        <div>
            <h1>Add New Supplier</h1>
            <p>Fill in the details below to add a supplier</p>
        </div>
    </div>

    <?php if($errorMsg): ?>
    <div class="alert error"><i class="fa fa-circle-exclamation"></i> <?= $errorMsg ?></div>
    <?php endif; ?>

    <div class="form-card">
        <div class="form-card-title"><i class="fa fa-plus"></i> Supplier Details</div>
        <form method="POST">
            <div class="form-grid">
                <div class="field">
                    <label>Name</label>
                    <input type="text" name="name" placeholder="e.g. ABC Supplies"
                        pattern="[A-Za-z ]+" title="Only letters allowed" required
                        value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
                </div>
                <div class="field">
                    <label>Contact</label>
                    <input type="text" name="contact" placeholder="e.g. 9876543210"
                        pattern="[6-9]{1}[0-9]{9}" maxlength="10"
                        title="10 digits, starting with 6–9" required
                        value="<?= htmlspecialchars($_POST['contact'] ?? '') ?>">
                </div>
                <div class="field full">
                    <label>Address</label>
                    <input type="text" name="address" placeholder="e.g. Vidyagiri Dharwad" required
                        value="<?= htmlspecialchars($_POST['address'] ?? '') ?>">
                </div>
                <div class="field">
                    <label>Contract Date</label>
                    <input type="date" name="contract_date"
                        max="<?= date('Y-m-d') ?>" required
                        value="<?= htmlspecialchars($_POST['contract_date'] ?? '') ?>">
                </div>
                <div class="form-actions">
                    <button type="submit" name="add" class="btn-primary">
                        <i class="fa fa-plus"></i> Add Supplier
                    </button>
                    <a href="supplier.php" class="btn-cancel">
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
