<?php
session_start();
include "db.php";

if(!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$errorMsg = '';

if(isset($_POST['pick'])) {
    $fid = (int)($_POST['fastener_id'] ?? 0);
    $qty = (int)($_POST['quantity'] ?? 0);

    if($fid <= 0) {
        $errorMsg = 'Please select a fastener.';
    } elseif($qty <= 0) {
        $errorMsg = 'Quantity must be greater than 0.';
    } else {
        $stockResult = mysqli_query($conn, "SELECT quantity FROM stock WHERE fastener_id=$fid LIMIT 1");
        $stockRow = $stockResult ? mysqli_fetch_assoc($stockResult) : null;
        $availableQty = $stockRow ? (int)$stockRow['quantity'] : 0;

        if(!$stockRow) {
            $errorMsg = 'No stock record exists for the selected fastener.';
        } elseif($qty > $availableQty) {
            $errorMsg = 'Pick quantity cannot be greater than available stock.';
        } else {
            mysqli_query($conn, "UPDATE stock SET quantity = quantity - $qty WHERE fastener_id=$fid AND quantity >= $qty");
            if(mysqli_affected_rows($conn) > 0) {
                $pickedBy = mysqli_real_escape_string($conn, $_SESSION['username'] ?? '');
                mysqli_query($conn, "INSERT INTO pick_list(fastener_id, quantity, picked_by, picked_at) VALUES($fid, $qty, '$pickedBy', NOW())");
                header("Location: pick_list.php?success=picked");
                exit();
            }
            $errorMsg = 'Unable to update stock. Please try again.';
        }
    }
}

$successMsg = '';
if(isset($_GET['success']) && $_GET['success'] === 'picked') {
    $successMsg = 'Stock picked successfully.';
}

$pickRows = [];
$pickResult = mysqli_query($conn, "
    SELECT p.id, p.quantity, p.picked_by, p.picked_at, f.name, f.part_number, f.size
    FROM pick_list p
    JOIN fastener f ON p.fastener_id = f.id
    ORDER BY p.picked_at DESC, p.id DESC
");
if($pickResult) {
    while($row = mysqli_fetch_assoc($pickResult)) $pickRows[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Pick Stock - Bolt Base</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&family=DM+Mono:wght@500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="css/user.css">
</head>
<body class="page-pick_list">
<div class="navbar">
    <div class="logo"><div class="logo-icon"><i class="fa-solid fa-screwdriver-wrench"></i></div>BOLT BASE</div>
    <div class="nav-links">
        <a href="dashboard.php">Dashboard</a>
        <a href="fastener.php">Fasteners</a>
        <a href="inventory.php">Inventory</a>
        <a href="pick_list.php" class="active">Pick List</a>
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
            <div class="page-title-icon"><i class="fa-solid fa-clipboard-list"></i></div>
            <div>
                <h1>Pick Stock</h1>
                <p>Select a fastener and subtract picked quantity from inventory</p>
            </div>
        </div>
        <a href="export_report.php?type=pick_list" class="btn-download"><i class="fa fa-file-pdf"></i> Download</a>
    </div>

    <?php if($successMsg): ?>
    <div class="alert success"><i class="fa fa-circle-check"></i> <?= htmlspecialchars($successMsg) ?></div>
    <?php endif; ?>

    <?php if($errorMsg): ?>
    <div class="alert error"><i class="fa fa-circle-exclamation"></i> <?= htmlspecialchars($errorMsg) ?></div>
    <?php endif; ?>

    <div class="form-card">
        <div class="form-card-title"><i class="fa fa-minus-circle"></i> Pick List Details</div>
        <form method="POST">
            <div class="form-grid">
                <div class="field full">
                    <label>Fastener</label>
                    <select name="fastener_id" required>
                        <option value="">Select Fastener</option>
                        <?php
                        $stockOptions = mysqli_query($conn, "
                            SELECT f.id, f.name, f.part_number, f.size, s.quantity
                            FROM stock s
                            JOIN fastener f ON s.fastener_id = f.id
                            WHERE s.quantity > 0
                            ORDER BY f.name ASC
                        ");
                        while($f = mysqli_fetch_assoc($stockOptions)):
                            $sel = (isset($_POST['fastener_id']) && $_POST['fastener_id'] == $f['id']) ? 'selected' : '';
                        ?>
                        <option value="<?= $f['id'] ?>" <?= $sel ?>>
                            <?= htmlspecialchars($f['name']) ?><?= !empty($f['part_number']) ? ' - ' . htmlspecialchars($f['part_number']) : '' ?> (<?= htmlspecialchars($f['size']) ?>) - Stock: <?= (int)$f['quantity'] ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="field">
                    <label>Pick Quantity</label>
                    <input type="number" name="quantity" placeholder="e.g. 25" min="1" required value="<?= htmlspecialchars($_POST['quantity'] ?? '') ?>">
                </div>
                <div class="form-actions">
                    <button type="submit" name="pick" class="btn-primary">
                        <i class="fa fa-minus"></i> Pick Stock
                    </button>
                    <a href="inventory.php" class="btn-cancel">
                        <i class="fa fa-xmark"></i> Cancel
                    </a>
                </div>
            </div>
        </form>
    </div>

    <div class="history-card">
        <div class="history-head">
            <h3><i class="fa fa-list-check"></i> Picked Fasteners</h3>
            <span class="mono"><?= count($pickRows) ?> Records</span>
        </div>
        <div class="table-scroll">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Fastener</th>
                    <th>Part No.</th>
                    <th>Size</th>
                    <th>Picked Qty</th>
                    <th>Picked By</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <?php if(!$pickRows): ?>
                <tr><td colspan="7"><div class="empty-state">No picked stock records yet.</div></td></tr>
                <?php else: foreach($pickRows as $index => $row): ?>
                <tr>
                    <td class="mono">#<?= $index + 1 ?></td>
                    <td><?= htmlspecialchars($row['name']) ?></td>
                    <td class="mono"><?= htmlspecialchars($row['part_number'] ?? '-') ?></td>
                    <td class="mono"><?= htmlspecialchars($row['size']) ?></td>
                    <td class="mono"><?= (int)$row['quantity'] ?></td>
                    <td><?= htmlspecialchars($row['picked_by'] ?: '-') ?></td>
                    <td class="mono"><?= htmlspecialchars(date('d M Y, h:i A', strtotime($row['picked_at']))) ?></td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
        </div>
    </div>
</div>

<script>
function tick(){const now=new Date();document.getElementById('dateStr').textContent=now.toLocaleDateString('en-IN');document.getElementById('timeStr').textContent=now.toLocaleTimeString('en-IN');}
setInterval(tick,1000);tick();
function toggleDark(){document.body.classList.toggle('dark');localStorage.setItem('boltTheme',document.body.classList.contains('dark')?'dark':'light');}
if(localStorage.getItem('boltTheme')==='dark')document.body.classList.add('dark');
</script>
</body>
</html>
