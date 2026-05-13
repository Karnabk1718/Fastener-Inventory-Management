<?php
session_start();
include "db.php";

if(!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

if(!isset($_GET['id'])) {
    header("Location: supplier.php");
    exit();
}

$id = (int)$_GET['id'];
$editResult = mysqli_query($conn, "SELECT * FROM supplier WHERE id=$id");
$editData   = mysqli_fetch_assoc($editResult);

if(!$editData) {
    header("Location: supplier.php");
    exit();
}

$errorMsg = '';
$minContractDate = '2000-01-01';
$maxContractDate = date('Y-m-d');

if(isset($_POST['update'])) {
    $name    = trim($_POST['name']);
    $contact = trim($_POST['contact']);
    $address = trim($_POST['address']);
    $date    = $_POST['contract_date'];
    $parsedDate = DateTime::createFromFormat('Y-m-d', $date);
    $dateErrors = DateTime::getLastErrors();
    $isValidDate = $parsedDate
        && ($dateErrors === false || ($dateErrors['warning_count'] === 0 && $dateErrors['error_count'] === 0))
        && $parsedDate->format('Y-m-d') === $date;

    if(!valid_name($name)) {
        $errorMsg = 'Name must contain only letters, spaces, dots, apostrophes, or hyphens.';
    } elseif(!valid_phone($contact)) {
        $errorMsg = 'Phone number must be 10 digits and start with 6–9.';
    } elseif(!$isValidDate || $date < $minContractDate || $date > $maxContractDate) {
        $errorMsg = 'Contract date must be from 2000-01-01 up to today.';
    } else {
        try {
            $stmt = mysqli_prepare($conn, "UPDATE supplier SET name=?, contact=?, address=?, contract_date=? WHERE id=?");
            mysqli_stmt_bind_param($stmt, "ssssi", $name, $contact, $address, $date, $id);
            mysqli_stmt_execute($stmt);
            header("Location: supplier.php?success=updated");
            exit();
        } catch (Throwable $e) {
            error_log($e->getMessage());
            $errorMsg = str_contains($e->getMessage(), 'Duplicate') ? 'This supplier already exists.' : 'Failed to update supplier. Please try again.';
        }
    }
    $editData['name']          = $_POST['name'];
    $editData['contact']       = $_POST['contact'];
    $editData['address']       = $_POST['address'];
    $editData['contract_date'] = $_POST['contract_date'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Edit Supplier – Bolt Base</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&family=DM+Mono:wght@500&display=swap" rel="stylesheet">
<style>
:root {
    --bg-from:#800020;
    --bg-to:#cc4444;
    --text:#ffffff;
    --muted:rgba(255,255,255,0.65);
    --gold:#ffd700;
    --gold-bg:rgba(255,215,0,0.15);
    --gold-br:rgba(255,215,0,0.40);
    --danger:#ff6b6b;
    --card-bg:rgba(0,0,0,0.40);
    --card-bdr:rgba(255,255,255,0.16);
    --input-bg:rgba(0,0,0,0.40);
    --input-bdr:rgba(255,255,255,0.25);
}
body.dark {
    --bg-from:#0f172a;
    --bg-to:#1e293b;
    --card-bg:rgba(0,0,0,0.55);
    --card-bdr:rgba(255,255,255,0.10);
    --input-bg:rgba(0,0,0,0.55);
    --input-bdr:rgba(255,255,255,0.15);
}
*,
*::before,
*::after {
    box-sizing:border-box;
    margin:0;
    padding:0;
}
body {
    font-family:'DM Sans','Segoe UI',sans-serif;
    background:linear-gradient(135deg,var(--bg-from) 0%,var(--bg-to) 100%);
    color:var(--text);
    min-height:100vh;
    transition:background 0.35s;
}
.navbar {
    display:flex;
    justify-content:space-between;
    align-items:center;
    padding:14px 30px;
    background:rgba(0,0,0,0.40);
    backdrop-filter:blur(14px);
    border-bottom:1px solid rgba(255,255,255,0.12);
    position:sticky;
    top:0;
    z-index:200;
    gap:16px;
}
.logo {
    font-size:18px;
    font-weight:600;
    display:flex;
    align-items:center;
    gap:9px;
    white-space:nowrap;
}
.logo-icon {
    width:32px;
    height:32px;
    background:var(--gold-bg);
    border:1.5px solid var(--gold);
    border-radius:8px;
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:14px;
}
.nav-links {
    display:flex;
    gap:2px;
}
.nav-links a {
    color:rgba(255,255,255,0.75);
    padding:6px 12px;
    border-radius:8px;
    text-decoration:none;
    font-size:13px;
    font-weight:500;
    transition:0.2s;
    white-space:nowrap;
}
.nav-links a:hover {
    background:rgba(255,255,255,0.12);
    color:#fff;
}
.nav-links a.active {
    background:rgba(255,255,255,0.16);
    color:#fff;
}
.nav-right {
    display:flex;
    align-items:center;
    gap:8px;
    flex-shrink:0;
}
.pill {
    background:rgba(0,0,0,0.30);
    border:1px solid rgba(255,255,255,0.14);
    border-radius:20px;
    padding:5px 11px;
    font-size:12px;
    display:flex;
    align-items:center;
    gap:5px;
    white-space:nowrap;
}
.pill i {
    font-size:10px;
    opacity:0.7;
}
.icon-btn {
    width:32px;
    height:32px;
    background:rgba(0,0,0,0.30);
    border:1px solid rgba(255,255,255,0.14);
    border-radius:8px;
    cursor:pointer;
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:13px;
    color:var(--text);
    transition:0.2s;
}
.icon-btn:hover {
    background:rgba(0,0,0,0.50);
}
.logout-btn {
    background:var(--gold);
    color:#1a0a00;
    border:none;
    border-radius:8px;
    padding:6px 14px;
    font-size:13px;
    font-weight:700;
    cursor:pointer;
    text-decoration:none;
    transition:0.2s;
    white-space:nowrap;
}
.logout-btn:hover {
    background:#ffe040;
}
.main {
    padding:32px 30px 50px;
    max-width:700px;
    margin:0 auto;
}
.page-header {
    display:flex;
    align-items:center;
    gap:12px;
    margin-bottom:28px;
}
.page-title-icon {
    width:46px;
    height:46px;
    background:var(--gold-bg);
    border:1.5px solid var(--gold-br);
    border-radius:12px;
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:20px;
}
.page-header h1 {
    font-size:22px;
    font-weight:600;
    letter-spacing:-0.3px;
    margin-bottom:2px;
}
.page-header p {
    font-size:13px;
    color:var(--muted);
}
.alert {
    padding:12px 16px;
    border-radius:10px;
    font-size:13px;
    font-weight:500;
    margin-bottom:20px;
    display:flex;
    align-items:center;
    gap:10px;
}
.alert.error {
    background:rgba(255,107,107,0.18);
    border:1px solid rgba(255,107,107,0.35);
    color:var(--danger);
}
.form-card {
    background:var(--card-bg);
    border:1px solid var(--card-bdr);
    border-radius:16px;
    padding:28px;
}
.form-card-title {
    font-size:14px;
    font-weight:600;
    margin-bottom:22px;
    display:flex;
    align-items:center;
    gap:8px;
    color:var(--gold);
}
.form-grid {
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:16px;
}
.field {
    display:flex;
    flex-direction:column;
    gap:6px;
}
.field.full {
    grid-column:1/-1;
}
.field label {
    font-size:11px;
    font-weight:600;
    text-transform:uppercase;
    letter-spacing:0.8px;
    color:rgba(255,255,255,0.80);
}
.field input {
    background:var(--input-bg);
    border:1px solid var(--input-bdr);
    border-radius:8px;
    padding:10px 13px;
    color:#fff;
    font-family:inherit;
    font-size:13px;
    font-weight:500;
    width:100%;
    transition:border-color 0.2s;
    outline:none;
}
.field input::placeholder {
    color:rgba(255,255,255,0.40);
}
.field input:focus {
    border-color:var(--gold);
    background:rgba(0,0,0,0.55);
}
.form-actions {
    grid-column:1/-1;
    display:flex;
    gap:10px;
    margin-top:4px;
}
.btn-primary {
    background:var(--gold);
    color:#1a0a00;
    border:none;
    border-radius:8px;
    padding:11px 24px;
    font-size:13px;
    font-weight:700;
    cursor:pointer;
    display:flex;
    align-items:center;
    gap:7px;
    transition:0.2s;
}
.btn-primary:hover {
    background:#ffe040;
}
.btn-cancel {
    background:rgba(255,255,255,0.12);
    color:#fff;
    border:1px solid rgba(255,255,255,0.20);
    border-radius:8px;
    padding:11px 20px;
    font-size:13px;
    font-weight:600;
    cursor:pointer;
    text-decoration:none;
    display:flex;
    align-items:center;
    gap:6px;
    transition:0.2s;
}
.btn-cancel:hover {
    background:rgba(255,255,255,0.20);
}
.id-badge {
    display:inline-flex;
    align-items:center;
    gap:6px;
    background:rgba(255,215,0,0.15);
    border:1px solid rgba(255,215,0,0.40);
    color:var(--gold);
    border-radius:8px;
    padding:4px 10px;
    font-size:12px;
    font-weight:600;
    font-family:'DM Mono',monospace;
}
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
<link rel="stylesheet" href="dark_mode_fix.css">
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
        <a href="pick_list.php">Pick List</a>
        <a href="supplier.php" class="active">Suppliers</a>
        <a href="orders.php">Orders</a>
    </div>
    <div class="nav-right">
        <div class="pill"><i class="fa fa-calendar"></i><span id="dateStr"></span></div>
        <div class="pill"><i class="fa fa-user"></i><?= htmlspecialchars($_SESSION['username']) ?></div>
        <div class="icon-btn" onclick="toggleDark()" title="Toggle dark mode"><i class="fa-solid fa-moon"></i></div>
        <a href="logout.php" class="logout-btn">Logout</a>
    </div>
</div>

<div class="main">
    <div class="page-header">
        <div class="page-title-icon"><i class="fa-solid fa-pen"></i></div>
        <div>
            <h1>Edit Supplier &nbsp;<span class="id-badge"><?= $editData['id'] ?></span></h1>
            <p>Update the supplier details below</p>
        </div>
    </div>

    <?php if($errorMsg): ?>
    <div class="alert error"><i class="fa fa-circle-exclamation"></i> <?= $errorMsg ?></div>
    <?php endif; ?>

    <div class="form-card">
        <div class="form-card-title"><i class="fa fa-pen"></i> Edit Supplier Details</div>
        <form method="POST">
            <div class="form-grid">
                <div class="field">
                    <label>Name</label>
                    <input type="text" name="name" placeholder="e.g. ABC Supplies"
                        pattern="[A-Za-z ]+" title="Only letters allowed" required
                        value="<?= htmlspecialchars($editData['name']) ?>">
                </div>
                <div class="field">
                    <label>Contact</label>
                    <input type="text" name="contact" placeholder="e.g. 9876543210"
                        pattern="[6-9]{1}[0-9]{9}" maxlength="10"
                        title="10 digits, starting with 6–9" required
                        value="<?= htmlspecialchars($editData['contact']) ?>">
                </div>
                <div class="field full">
                    <label>Address</label>
                    <input type="text" name="address" placeholder="e.g. Vidyagiri Dharwad" required
                        value="<?= htmlspecialchars($editData['address']) ?>">
                </div>
                <div class="field">
                    <label>Contract Date</label>
                    <input type="date" name="contract_date"
                        min="<?= $minContractDate ?>" max="<?= $maxContractDate ?>" required
                        value="<?= htmlspecialchars($editData['contract_date']) ?>">
                </div>
                <div class="form-actions">
                    <button type="submit" name="update" class="btn-primary">
                        <i class="fa fa-check"></i> Update Supplier
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
