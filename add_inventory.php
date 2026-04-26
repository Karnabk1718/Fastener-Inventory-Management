<?php
session_start();
include "db.php";

if(!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$errorMsg = '';

if(isset($_POST['add'])) {
    $fid = (int)$_POST['fastener_id'];
    $qty = (int)$_POST['quantity'];

    if($qty <= 0) {
        $errorMsg = 'Quantity must be greater than 0.';
    } else {
        $check = mysqli_query($conn, "SELECT * FROM stock WHERE fastener_id='$fid'");
        if(mysqli_num_rows($check) > 0) {
            mysqli_query($conn, "UPDATE stock SET quantity = quantity + $qty WHERE fastener_id='$fid'");
        } else {
            mysqli_query($conn, "INSERT INTO stock(fastener_id, quantity) VALUES('$fid','$qty')");
        }
        header("Location: inventory.php?success=added");
        exit();
    }
}

$fasteners = mysqli_query($conn, "SELECT * FROM fastener ORDER BY name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Add Stock – Bolt Base</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&family=DM+Mono:wght@500&display=swap" rel="stylesheet">
<style>
:root{--bg-from:#800020;--bg-to:#cc4444;--text:#ffffff;--muted:rgba(255,255,255,0.65);--gold:#ffd700;--gold-bg:rgba(255,215,0,0.15);--gold-br:rgba(255,215,0,0.40);--danger:#ff6b6b;--card-bg:rgba(0,0,0,0.40);--card-bdr:rgba(255,255,255,0.16);--input-bg:rgba(0,0,0,0.40);--input-bdr:rgba(255,255,255,0.25);}
body.dark{--bg-from:#0f172a;--bg-to:#1e293b;--card-bg:rgba(0,0,0,0.55);--card-bdr:rgba(255,255,255,0.10);--input-bg:rgba(0,0,0,0.55);--input-bdr:rgba(255,255,255,0.15);}
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
.main{padding:32px 30px 50px;max-width:700px;margin:0 auto;}
.page-header{display:flex;align-items:center;gap:12px;margin-bottom:28px;}
.page-title-icon{width:46px;height:46px;background:var(--gold-bg);border:1.5px solid var(--gold-br);border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:20px;}
.page-header h1{font-size:22px;font-weight:600;letter-spacing:-0.3px;margin-bottom:2px;}
.page-header p{font-size:13px;color:var(--muted);}
.alert{padding:12px 16px;border-radius:10px;font-size:13px;font-weight:500;margin-bottom:20px;display:flex;align-items:center;gap:10px;}
.alert.error{background:rgba(255,107,107,0.18);border:1px solid rgba(255,107,107,0.35);color:var(--danger);}
.form-card{background:var(--card-bg);border:1px solid var(--card-bdr);border-radius:16px;padding:28px;}
.form-card-title{font-size:14px;font-weight:600;margin-bottom:22px;display:flex;align-items:center;gap:8px;color:var(--gold);}
.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px;}
.field{display:flex;flex-direction:column;gap:6px;}
.field label{font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:0.8px;color:rgba(255,255,255,0.80);}
.field input,.field select{background:var(--input-bg);border:1px solid var(--input-bdr);border-radius:8px;padding:10px 13px;color:#fff;font-family:inherit;font-size:13px;font-weight:500;width:100%;transition:border-color 0.2s;outline:none;}
.field input::placeholder{color:rgba(255,255,255,0.40);}
.field input:focus,.field select:focus{border-color:var(--gold);background:rgba(0,0,0,0.55);}
.field select option{background:#1a0a00;color:#fff;}
.form-actions{grid-column:1/-1;display:flex;gap:10px;margin-top:4px;}
.btn-primary{background:var(--gold);color:#1a0a00;border:none;border-radius:8px;padding:11px 24px;font-size:13px;font-weight:700;cursor:pointer;display:flex;align-items:center;gap:7px;transition:0.2s;}
.btn-primary:hover{background:#ffe040;}
.btn-cancel{background:rgba(255,255,255,0.12);color:#fff;border:1px solid rgba(255,255,255,0.20);border-radius:8px;padding:11px 20px;font-size:13px;font-weight:600;cursor:pointer;text-decoration:none;display:flex;align-items:center;gap:6px;transition:0.2s;}
.btn-cancel:hover{background:rgba(255,255,255,0.20);}
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
        <a href="inventory.php" class="active">Inventory</a>
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
        <div class="page-title-icon"><i class="fa-solid fa-plus"></i></div>
        <div>
            <h1>Add Stock</h1>
            <p>Add stock for a fastener below</p>
        </div>
    </div>

    <?php if($errorMsg): ?>
    <div class="alert error"><i class="fa fa-circle-exclamation"></i> <?= $errorMsg ?></div>
    <?php endif; ?>

    <div class="form-card">
        <div class="form-card-title"><i class="fa fa-plus"></i> Stock Details</div>
        <form method="POST">
            <div class="form-grid">
                <div class="field">
                    <label>Fastener</label>
                    <select name="fastener_id" required>
                        <option value="">Select Fastener</option>
                        <?php
                        $fl = mysqli_query($conn, "SELECT * FROM fastener ORDER BY name");
                        while($f = mysqli_fetch_assoc($fl)):
                            $sel = (isset($_POST['fastener_id']) && $_POST['fastener_id'] == $f['id']) ? 'selected' : '';
                        ?>
                        <option value="<?= $f['id'] ?>" <?= $sel ?>>
                            <?= htmlspecialchars($f['name']) ?> (<?= htmlspecialchars($f['size']) ?>)
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="field">
                    <label>Quantity</label>
                    <input type="number" name="quantity" placeholder="e.g. 100"
                           min="1" required
                           value="<?= htmlspecialchars($_POST['quantity'] ?? '') ?>">
                </div>
                <div class="form-actions">
                    <button type="submit" name="add" class="btn-primary">
                        <i class="fa fa-plus"></i> Add Stock
                    </button>
                    <a href="inventory.php" class="btn-cancel">
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