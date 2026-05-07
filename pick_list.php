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
<style>
:root{--supervisor-red:#CD1C18;--supervisor-peach:#FFA896;--supervisor-deep:#9B1313;--supervisor-dark:#38000A;--supervisor-panel:#fff7f5;--supervisor-muted:#6f2220;--supervisor-border:#38000A;--input-bg:#fff;--danger:#9B1313}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
body{font-family:'DM Sans','Segoe UI',sans-serif;min-height:100vh;background:linear-gradient(135deg,#fff7f5 0%,#ffd8d0 46%,var(--supervisor-peach) 100%);color:var(--supervisor-dark);overflow-x:hidden}
.navbar{display:flex;justify-content:space-between;align-items:center;padding:14px 30px;background:linear-gradient(90deg,var(--supervisor-dark) 0%,var(--supervisor-deep) 100%);border-bottom:1px solid var(--supervisor-border);box-shadow:0 8px 22px rgba(56,0,10,.24);position:sticky;top:0;z-index:200;gap:16px}
.logo{font-size:18px;font-weight:600;display:flex;align-items:center;gap:9px;white-space:nowrap;color:#fff}
.logo-icon{width:32px;height:32px;background:var(--supervisor-peach);border:1.5px solid var(--supervisor-border);border-radius:8px;display:flex;align-items:center;justify-content:center;color:var(--supervisor-dark)}
.nav-links{display:flex;gap:2px}.nav-links a{color:rgba(255,255,255,.84);border:1px solid rgba(255,168,150,.32);padding:6px 12px;border-radius:8px;text-decoration:none;font-size:13px;font-weight:500;white-space:nowrap}.nav-links a:hover,.nav-links a.active{background:rgba(255,168,150,.22);color:#fff;border-color:var(--supervisor-peach)}
.nav-right{display:flex;align-items:center;gap:8px;flex-shrink:0}.pill,.icon-btn{background:rgba(255,168,150,.14);border:1px solid rgba(255,168,150,.35);border-radius:20px;padding:5px 11px;font-size:12px;display:flex;align-items:center;gap:5px;color:#fff;white-space:nowrap}.icon-btn{width:32px;height:32px;border-radius:8px;justify-content:center;cursor:pointer;padding:0}.logout-btn{background:var(--supervisor-red);border:1px solid var(--supervisor-border);color:#fff;border-radius:8px;padding:6px 14px;font-size:13px;font-weight:700;text-decoration:none}
.main{padding:32px 30px 50px;max-width:1080px;margin:0 auto}.page-header{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:28px;flex-wrap:wrap}.page-title{display:flex;align-items:center;gap:12px}.page-title-icon{width:46px;height:46px;background:var(--supervisor-peach);border:1.5px solid var(--supervisor-border);border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:20px}.page-header h1{font-size:22px;font-weight:600;margin-bottom:2px}.page-header p{font-size:13px;color:var(--supervisor-muted)}
.alert{padding:12px 16px;border-radius:10px;font-size:13px;font-weight:600;margin-bottom:20px;display:flex;align-items:center;gap:10px}.alert.error{background:rgba(205,28,24,.14);border:1px solid var(--supervisor-red);color:var(--danger)}.alert.success{background:#ffe3dc;border:1px solid var(--supervisor-border);color:var(--supervisor-dark)}
.form-card{background:var(--supervisor-panel);border:1px solid var(--supervisor-border);border-radius:16px;padding:28px;box-shadow:0 8px 18px rgba(56,0,10,.12)}.form-card-title{font-size:14px;font-weight:700;margin-bottom:22px;display:flex;align-items:center;gap:8px}.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px}.field{display:flex;flex-direction:column;gap:6px}.field.full{grid-column:1/-1}.field label{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.8px}.field input,.field select{background:var(--input-bg);border:1px solid var(--supervisor-border);border-radius:8px;padding:10px 13px;color:var(--supervisor-dark);font-family:inherit;font-size:13px;font-weight:600;width:100%;outline:none}.field input:focus,.field select:focus{border-color:var(--supervisor-red);box-shadow:0 0 0 3px rgba(205,28,24,.12)}
.form-actions{grid-column:1/-1;display:flex;gap:10px;margin-top:4px;flex-wrap:wrap}.btn-primary,.btn-cancel{border-radius:8px;padding:11px 24px;font-size:13px;font-weight:800;cursor:pointer;text-decoration:none;display:flex;align-items:center;gap:7px;border:1px solid var(--supervisor-border)}.btn-primary{background:var(--supervisor-red);color:#fff}.btn-primary:hover{background:var(--supervisor-dark)}.btn-cancel{background:#fff;color:var(--supervisor-dark);box-shadow:0 4px 12px rgba(56,0,10,.10)}.btn-cancel i{color:var(--supervisor-red)}.btn-cancel:hover{background:var(--supervisor-peach)}
.btn-download{background:var(--supervisor-red);border:1px solid var(--supervisor-border);color:#fff;border-radius:8px;padding:10px 16px;font-size:13px;font-weight:800;text-decoration:none;display:flex;align-items:center;gap:7px}.btn-download:hover{background:var(--supervisor-dark)}
.history-card{margin-top:22px;background:var(--supervisor-panel);border:1px solid var(--supervisor-border);border-radius:16px;overflow:hidden;box-shadow:0 8px 18px rgba(56,0,10,.12)}.history-head{padding:16px 20px;border-bottom:1px solid var(--supervisor-border);display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap}.history-head h3{font-size:14px}.table-scroll{overflow-x:auto}table{width:100%;border-collapse:collapse;font-size:13px;min-width:760px}th{background:#ffd6cc;text-align:left;text-transform:uppercase;letter-spacing:.7px;font-size:10px;padding:11px 12px;border-bottom:1px solid var(--supervisor-border)}td{padding:12px;border-bottom:1px solid rgba(56,0,10,.2);font-weight:600}.mono{font-family:'DM Mono',monospace}.empty-state{padding:34px;text-align:center;color:var(--supervisor-muted);font-weight:700}
body.dark{background:linear-gradient(135deg,var(--supervisor-dark) 0%,#6d0710 48%,var(--supervisor-deep) 100%);color:#fff7f5}body.dark .form-card{background:rgba(56,0,10,.72);border-color:#FFA896}body.dark .page-header p,body.dark .field label{color:#ffd6cc}body.dark .field input,body.dark .field select{background:rgba(56,0,10,.78);border-color:#FFA896;color:#fff7f5}body.dark .btn-cancel{background:rgba(255,168,150,.16);border-color:#FFA896;color:#fff7f5}body.dark .btn-cancel i{color:#FFA896}
</style>
</head>
<body>
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
