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
<link rel="stylesheet" href="css/user.css">
</head>
<body class="page-edit_fastener">

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
