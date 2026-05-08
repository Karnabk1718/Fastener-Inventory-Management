<?php
session_start();
include "db.php";

if(!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

if(!isset($_GET['id'])) {
    header("Location: fastener.php");
    exit();
}

$id = (int)$_GET['id'];
$editResult = mysqli_query($conn, "SELECT * FROM fastener WHERE id=$id");
$editData   = mysqli_fetch_assoc($editResult);

if(!$editData) {
    header("Location: fastener.php");
    exit();
}

$errorMsg = '';

if(isset($_POST['update'])) {
    $name  = trim($_POST['name']);
    $partNumber = trim($_POST['part_number']);
    $type  = trim($_POST['type']);
    $size  = trim($_POST['size']);
    $price = $_POST['price'];
    $desc  = trim($_POST['description']);

    $imageName = $editData['image'] ?? '';
    if(!empty($_FILES['image']['name'])) {
        $allowed = ['jpg','jpeg','png','webp'];
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        if(!in_array($ext, $allowed)) {
            $errorMsg = 'Image must be JPG, PNG, or WEBP.';
        } else {
            $imageName = uniqid('fastener_') . '.' . $ext;
            move_uploaded_file($_FILES['image']['tmp_name'], 'uploads/' . $imageName);
        }
    }

    if(!$errorMsg) {
        if($price <= 0) {
            $errorMsg = 'Price must be greater than 0.';
        } elseif(!preg_match('/^[0-9]+mm$/', $size)) {
            $errorMsg = 'Size must be in format like 5mm, 10mm.';
        } else {
            $desc_safe = mysqli_real_escape_string($conn, $desc);
            $name_safe = mysqli_real_escape_string($conn, $name);
            $part_safe = mysqli_real_escape_string($conn, $partNumber);
            $type_safe = mysqli_real_escape_string($conn, $type);
            $size_safe = mysqli_real_escape_string($conn, $size);
            $img_safe  = mysqli_real_escape_string($conn, $imageName);
            mysqli_query($conn, "UPDATE fastener SET name='$name_safe', part_number='$part_safe', type='$type_safe', size='$size_safe', unit_price='$price', description='$desc_safe', image='$img_safe' WHERE id=$id");
            header("Location: fastener.php?success=updated");
            exit();
        }
    }
    // keep form values from POST on error
    $editData['name']        = $_POST['name'];
    $editData['part_number'] = $_POST['part_number'];
    $editData['type']        = $_POST['type'];
    $editData['size']        = $_POST['size'];
    $editData['unit_price']  = $_POST['price'];
    $editData['description'] = $_POST['description'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Edit Fastener – Bolt Base</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&family=DM+Mono:wght@500&display=swap" rel="stylesheet">
<style>
:root {
    --bg-from:#800020; --bg-to:#cc4444; --text:#ffffff;
    --muted:rgba(255,255,255,0.65); --gold:#ffd700;
    --gold-bg:rgba(255,215,0,0.15); --gold-br:rgba(255,215,0,0.40);
    --danger:#ff6b6b; --success:#4ade80;
    --card-bg:rgba(0,0,0,0.40); --card-bdr:rgba(255,255,255,0.16);
    --input-bg:rgba(0,0,0,0.40); --input-bdr:rgba(255,255,255,0.25);
}
body.dark { --bg-from:#0f172a; --bg-to:#1e293b; --card-bg:rgba(0,0,0,0.55); --card-bdr:rgba(255,255,255,0.10); --input-bg:rgba(0,0,0,0.55); --input-bdr:rgba(255,255,255,0.15); }
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
.field input{background:var(--input-bg);border:1px solid var(--input-bdr);border-radius:8px;padding:10px 13px;color:#fff;font-family:inherit;font-size:13px;font-weight:500;width:100%;transition:border-color 0.2s;outline:none;}
.field input::placeholder{color:rgba(255,255,255,0.40);}
.field input:focus{border-color:var(--gold);background:rgba(0,0,0,0.55);}
.form-actions{grid-column:1/-1;display:flex;gap:10px;margin-top:4px;}
.btn-primary{background:var(--gold);color:#1a0a00;border:none;border-radius:8px;padding:11px 24px;font-size:13px;font-weight:700;cursor:pointer;display:flex;align-items:center;gap:7px;transition:0.2s;}
.btn-primary:hover{background:#ffe040;}
.btn-cancel{background:rgba(255,255,255,0.12);color:#fff;border:1px solid rgba(255,255,255,0.20);border-radius:8px;padding:11px 20px;font-size:13px;font-weight:600;cursor:pointer;text-decoration:none;display:flex;align-items:center;gap:6px;transition:0.2s;}
.btn-cancel:hover{background:rgba(255,255,255,0.20);}
.id-badge{display:inline-flex;align-items:center;gap:6px;background:rgba(255,215,0,0.15);border:1px solid rgba(255,215,0,0.40);color:var(--gold);border-radius:8px;padding:4px 10px;font-size:12px;font-weight:600;font-family:'DM Mono',monospace;}
.field textarea{background:var(--input-bg);border:1px solid var(--input-bdr);border-radius:8px;padding:10px 13px;color:#fff;font-family:inherit;font-size:13px;font-weight:500;width:100%;transition:border-color 0.2s;outline:none;resize:vertical;min-height:80px;}
.field textarea::placeholder{color:rgba(255,255,255,0.40);}
.field textarea:focus{border-color:var(--gold);background:rgba(0,0,0,0.55);}
.file-label{display:flex;align-items:center;gap:10px;background:var(--input-bg);border:1px dashed var(--input-bdr);border-radius:8px;padding:10px 13px;cursor:pointer;transition:border-color 0.2s;font-size:13px;color:rgba(255,255,255,0.60);}
.file-label:hover{border-color:var(--gold);color:#fff;}
.file-label i{font-size:16px;color:var(--gold);}
.file-label input[type=file]{display:none;}
#fileName{font-size:12px;color:var(--muted);margin-top:4px;}
.current-img{display:flex;align-items:center;gap:10px;margin-bottom:8px;}
.current-img img{width:52px;height:52px;object-fit:cover;border-radius:8px;border:1px solid var(--card-bdr);}
.current-img span{font-size:12px;color:var(--muted);}
.img-preview-wrap{display:none;margin-top:12px;position:relative;width:fit-content;}
.img-preview-wrap img{width:120px;height:120px;object-fit:cover;border-radius:10px;border:2px solid var(--gold-br);display:block;box-shadow:0 4px 18px rgba(0,0,0,0.40);}
.img-preview-wrap .remove-img{position:absolute;top:-8px;right:-8px;width:22px;height:22px;background:#ff6b6b;border:none;border-radius:50%;color:#fff;font-size:11px;cursor:pointer;display:flex;align-items:center;justify-content:center;}
.img-preview-label{font-size:11px;color:var(--gold);margin-top:5px;font-weight:600;text-transform:uppercase;letter-spacing:0.6px;}


/* Page theme overrides */
:root {
    --supervisor-red: #CD1C18;
    --supervisor-peach: #FFA896;
    --supervisor-deep: #9B1313;
    --supervisor-dark: #38000A;
    --supervisor-panel: #fff7f5;
    --supervisor-panel-soft: #ffe3dc;
    --supervisor-muted: #6f2220;
    --supervisor-border: #38000A;
    --bg-from: #fff0ec;
    --bg-to: var(--supervisor-peach);
    --text: var(--supervisor-dark);
    --muted: var(--supervisor-muted);
    --gold: var(--supervisor-red);
    --gold-bg: var(--supervisor-panel-soft);
    --gold-br: var(--supervisor-border);
    --card-bg: var(--supervisor-panel);
    --card-bdr: var(--supervisor-border);
    --input-bg: #fff;
    --input-bdr: var(--supervisor-border);
    --thead-bg: #ffd6cc;
    --row-bdr: rgba(56, 0, 10, 0.22);
    --row-hover: #ffe8e2;
    --border: rgba(56, 0, 10, 0.26);
}

body {
    background: linear-gradient(135deg, #fff7f5 0%, #ffd8d0 46%, var(--supervisor-peach) 100%) !important;
    color: var(--supervisor-dark) !important;
    overflow-x: hidden;
}

body.dark {
    --text: #fff7f5;
    --muted: #ffd6cc;
    --gold: #FFA896;
    --card-bg: rgba(56, 0, 10, 0.72);
    --card-bdr: #FFA896;
    --input-bg: rgba(56, 0, 10, 0.78);
    --input-bdr: #FFA896;
    --thead-bg: rgba(56, 0, 10, 0.92);
    --row-bdr: rgba(255, 168, 150, 0.30);
    --row-hover: rgba(255, 168, 150, 0.14);
    background: linear-gradient(135deg, var(--supervisor-dark) 0%, #6d0710 48%, var(--supervisor-deep) 100%) !important;
    color: #fff7f5 !important;
}

.navbar {
    background: linear-gradient(90deg, var(--supervisor-dark) 0%, var(--supervisor-deep) 100%) !important;
    border-bottom-color: var(--supervisor-border) !important;
    box-shadow: 0 8px 22px rgba(56, 0, 10, 0.24);
}

.logo,
.navbar .logo,
.navbar .logo *:not(.logo-icon):not(.logo-icon *) {
    color: #fff !important;
}

.logo-icon,
.page-title-icon {
    background: var(--supervisor-peach) !important;
    border-color: var(--supervisor-border) !important;
    color: var(--supervisor-dark) !important;
}

.logo-icon i,
.page-title-icon i,
.page-header i,
.count-badge i {
    color: var(--supervisor-dark) !important;
}

.nav-links a {
    color: rgba(255, 255, 255, 0.84) !important;
    border: 1px solid rgba(255, 168, 150, 0.32);
}

.nav-links a:hover,
.nav-links a.active {
    background: rgba(255, 168, 150, 0.22) !important;
    color: #fff !important;
    border-color: var(--supervisor-peach) !important;
}

.navbar .pill,
.navbar .icon-btn,
.toggle-btn {
    background: rgba(255, 168, 150, 0.14) !important;
    border-color: rgba(255, 168, 150, 0.35) !important;
    color: #fff !important;
}

.page-title h1,
.page-header h1 {
    color: var(--supervisor-dark) !important;
}

.page-title p,
.page-header p {
    color: var(--supervisor-muted) !important;
}

.logout-btn {
    background: var(--supervisor-red) !important;
    border: 1px solid var(--supervisor-border) !important;
    color: #fff !important;
}

.logout-btn:hover {
    background: var(--supervisor-dark) !important;
    color: #fff !important;
}

body.dark .page-title h1,
body.dark .page-header h1 {
    color: #fff7f5 !important;
}

body.dark .page-title p,
body.dark .page-header p {
    color: #ffd6cc !important;
}
.form-card {
    background: var(--supervisor-panel) !important;
    border-color: var(--supervisor-border) !important;
    color: var(--supervisor-dark) !important;
    box-shadow: 0 8px 18px rgba(56, 0, 10, 0.12);
}

.form-card-title,
.field label {
    color: var(--supervisor-dark) !important;
}

.field input,
.field textarea,
.field select,
.file-label {
    background: var(--input-bg) !important;
    border-color: var(--input-bdr) !important;
    color: var(--supervisor-dark) !important;
}

.field input::placeholder,
.field textarea::placeholder {
    color: rgba(56, 0, 10, 0.48) !important;
}

.field input:focus,
.field textarea:focus,
.field select:focus,
.file-label:hover {
    border-color: var(--supervisor-red) !important;
    box-shadow: 0 0 0 3px rgba(205, 28, 24, 0.12);
}

.btn-primary,
button[type="submit"] {
    background: var(--supervisor-red) !important;
    border: 1px solid var(--supervisor-border) !important;
    color: #fff !important;
}

.btn-primary:hover {
    background: var(--supervisor-peach) !important;
    color: var(--supervisor-dark) !important;
}

button[type="submit"]:hover {
    background: var(--supervisor-dark) !important;
    color: #fff !important;
}

.btn-cancel {
    background: #fff !important;
    border: 1px solid var(--supervisor-border) !important;
    color: var(--supervisor-dark) !important;
    box-shadow: 0 4px 12px rgba(56, 0, 10, 0.10);
}

.btn-cancel i {
    color: var(--supervisor-red) !important;
}

.btn-cancel:hover {
    background: var(--supervisor-peach) !important;
    color: var(--supervisor-dark) !important;
    box-shadow: 0 6px 16px rgba(56, 0, 10, 0.16);
}

.alert.error {
    background: rgba(205, 28, 24, 0.14) !important;
    border-color: var(--supervisor-red) !important;
    color: var(--supervisor-deep) !important;
}

body.dark .form-card {
    background: rgba(56, 0, 10, 0.72) !important;
}

body.dark .form-card-title,
body.dark .field label {
    color: #fff7f5 !important;
}

body.dark .field input,
body.dark .field textarea,
body.dark .field select,
body.dark .file-label {
    color: #fff7f5 !important;
}

body.dark .btn-cancel {
    background: rgba(255, 168, 150, 0.16) !important;
    border-color: #FFA896 !important;
    color: #fff7f5 !important;
}

body.dark .btn-cancel i {
    color: #FFA896 !important;
}
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
        <div class="page-title-icon"><i class="fa-solid fa-pen"></i></div>
        <div>
            <h1>Edit Fastener &nbsp;<span class="id-badge">#<?= $editData['id'] ?></span></h1>
            <p>Update the fastener details below</p>
        </div>
    </div>

    <?php if($errorMsg): ?>
    <div class="alert error"><i class="fa fa-circle-exclamation"></i> <?= $errorMsg ?></div>
    <?php endif; ?>

    <div class="form-card">
        <div class="form-card-title"><i class="fa fa-pen"></i> Edit Fastener Details</div>
        <form method="POST" enctype="multipart/form-data">
            <div class="form-grid">
                <div class="field">
                    <label>Name</label>
                    <input type="text" name="name" placeholder="e.g. Hex Bolt"
                        pattern="[A-Za-z0-9 ]+" required
                        value="<?= htmlspecialchars($editData['name']) ?>">
                </div>
                <div class="field">
                    <label>Part Number</label>
                    <input type="text" name="part_number" placeholder="e.g. HHB-10"
                        pattern="[A-Za-z0-9._ -]+"
                        value="<?= htmlspecialchars($editData['part_number'] ?? '') ?>">
                </div>
                <div class="field">
                    <label>Type</label>
                    <input type="text" name="type" placeholder="e.g. Bolt"
                        pattern="[A-Za-z ]+"
                        value="<?= htmlspecialchars($editData['type']) ?>">
                </div>
                <div class="field">
                    <label>Size</label>
                    <input type="text" name="size" placeholder="e.g. 10mm"
                        pattern="[0-9]+mm" required
                        value="<?= htmlspecialchars($editData['size']) ?>">
                </div>
                <div class="field">
                    <label>Price (₹)</label>
                    <input type="number" step="1" name="price" placeholder="e.g. 10"
                        min="1" required
                        value="<?= htmlspecialchars($editData['unit_price']) ?>">
                </div>
                <div class="field full">
                    <label>Description</label>
                    <textarea name="description" placeholder="Brief description of the fastener…"><?= htmlspecialchars($editData['description'] ?? '') ?></textarea>
                </div>
                <div class="field full">
                    <label>Image</label>
                    <?php if(!empty($editData['image'])): ?>
                    <div class="current-img">
                        <img src="uploads/<?= htmlspecialchars($editData['image']) ?>" alt="Current image">
                        <span>Current image — upload a new one to replace it</span>
                    </div>
                    <?php endif; ?>
                    <label class="file-label" for="imageInput">
                        <i class="fa fa-image"></i>
                        <span id="uploadLabel">Click to upload image (JPG, PNG, WEBP)</span>
                        <input type="file" id="imageInput" name="image" accept=".jpg,.jpeg,.png,.webp" onchange="showPreview(this)">
                    </label>
                    <span id="fileName"></span>
                    <div class="img-preview-wrap" id="previewWrap">
                        <img id="previewImg" src="" alt="Preview">
                        <button type="button" class="remove-img" onclick="clearImage()" title="Remove image"><i class="fa fa-xmark"></i></button>
                        <div class="img-preview-label"><i class="fa fa-check-circle"></i> New image ready to upload</div>
                    </div>
                </div>
                <div class="form-actions">
                    <button type="submit" name="update" class="btn-primary">
                        <i class="fa fa-check"></i> Update Fastener
                    </button>
                    <a href="fastener.php" class="btn-cancel">
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
function showPreview(input) {
    const file = input.files[0];
    if (!file) return;
    document.getElementById('fileName').textContent = '📎 ' + file.name;
    document.getElementById('uploadLabel').textContent = file.name;
    const reader = new FileReader();
    reader.onload = e => {
        document.getElementById('previewImg').src = e.target.result;
        document.getElementById('previewWrap').style.display = 'block';
    };
    reader.readAsDataURL(file);
}
function clearImage() {
    document.getElementById('imageInput').value = '';
    document.getElementById('fileName').textContent = '';
    document.getElementById('uploadLabel').textContent = 'Click to upload image (JPG, PNG, WEBP)';
    document.getElementById('previewWrap').style.display = 'none';
    document.getElementById('previewImg').src = '';
}
</script>
</body>
</html>
