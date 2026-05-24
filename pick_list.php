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
:root {
    --supervisor-red: #CD1C18;
    --supervisor-peach: #FFA896;
    --supervisor-deep: #9B1313;
    --supervisor-dark: #38000A;
    --supervisor-panel: #fff7f5;
    --supervisor-muted: #6f2220;
    --supervisor-border: #38000A;
    --input-bg: #fff;
    --danger: #9B1313;
}
*,
*::before,
*::after {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
}
body {
    min-height: 100vh;
    overflow-x: hidden;
    color: var(--supervisor-dark);
    font-family: 'DM Sans', 'Segoe UI', sans-serif;
    background: linear-gradient(135deg, #fff7f5 0%, #ffd8d0 46%, var(--supervisor-peach) 100%);
}
/* Navbar */
.navbar {
    position: sticky;
    top: 0;
    z-index: 200;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    padding: 14px 30px;
    background: linear-gradient(90deg, var(--supervisor-dark) 0%, var(--supervisor-deep) 100%);
    border-bottom: 1px solid var(--supervisor-border);
    box-shadow: 0 8px 22px rgba(56, 0, 10, 0.24);
}
.logo {
    display: flex;
    align-items: center;
    gap: 9px;
    color: #fff;
    font-size: 18px;
    font-weight: 600;
    white-space: nowrap;
}
.logo-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    color: var(--supervisor-dark);
    background: var(--supervisor-peach);
    border: 1.5px solid var(--supervisor-border);
    border-radius: 8px;
}
.nav-links,
.nav-right {
    display: flex;
    align-items: center;
}
.nav-links {
    gap: 2px;
}
.nav-links a {
    padding: 6px 12px;
    color: rgba(255, 255, 255, 0.84);
    font-size: 13px;
    font-weight: 500;
    text-decoration: none;
    white-space: nowrap;
    border: 1px solid rgba(255, 168, 150, 0.32);
    border-radius: 8px;
}
.nav-links a:hover,
.nav-links a.active {
    color: #fff;
    background: rgba(255, 168, 150, 0.22);
    border-color: var(--supervisor-peach);
}
.nav-right {
    flex-shrink: 0;
    gap: 8px;
}
.pill,
.icon-btn {
    display: flex;
    align-items: center;
    gap: 5px;
    color: #fff;
    font-size: 12px;
    white-space: nowrap;
    background: rgba(255, 168, 150, 0.14);
    border: 1px solid rgba(255, 168, 150, 0.35);
    border-radius: 20px;
}
.pill {
    padding: 5px 11px;
}
.icon-btn {
    justify-content: center;
    width: 32px;
    height: 32px;
    padding: 0;
    cursor: pointer;
    border-radius: 8px;
}
.logout-btn {
    padding: 6px 14px;
    color: #fff;
    font-size: 13px;
    font-weight: 700;
    text-decoration: none;
    background: var(--supervisor-red);
    border: 1px solid var(--supervisor-border);
    border-radius: 8px;
}
/* Page layout */
.main {
    max-width: 1080px;
    margin: 0 auto;
    padding: 32px 30px 50px;
}
.page-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 12px;
    margin-bottom: 28px;
}
.page-title {
    display: flex;
    align-items: center;
    gap: 12px;
}
.page-title-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 46px;
    height: 46px;
    font-size: 20px;
    background: var(--supervisor-peach);
    border: 1.5px solid var(--supervisor-border);
    border-radius: 12px;
}
.page-header h1 {
    margin-bottom: 2px;
    font-size: 22px;
    font-weight: 600;
}
.page-header p {
    color: var(--supervisor-muted);
    font-size: 13px;
}
/* Feedback */
.alert {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 20px;
    padding: 12px 16px;
    font-size: 13px;
    font-weight: 600;
    border-radius: 10px;
}
.alert.error {
    color: var(--danger);
    background: rgba(205, 28, 24, 0.14);
    border: 1px solid var(--supervisor-red);
}
.alert.success {
    color: var(--supervisor-dark);
    background: #ffe3dc;
    border: 1px solid var(--supervisor-border);
}
.alert.success {
    position: fixed;
    top: 82px;
    left: 50%;
    z-index: 1000;
    min-width: min(420px, calc(100vw - 32px));
    margin: 0 !important;
    justify-content: center;
    box-shadow: 0 14px 32px rgba(56, 0, 10, 0.18);
    transform: translateX(-50%);
}
/* Form */
.form-card {
    max-width: 760px;
    margin: 0 auto;
    padding: 20px;
    background: var(--supervisor-panel);
    border: 1px solid var(--supervisor-border);
    border-radius: 16px;
    box-shadow: 0 8px 18px rgba(56, 0, 10, 0.12);
}
.form-card-title {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 16px;
    font-size: 14px;
    font-weight: 700;
}
.form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
}
.field {
    display: flex;
    flex-direction: column;
    gap: 4px;
}
.field.full,
.form-actions {
    grid-column: 1 / -1;
}
.field label {
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.8px;
    text-transform: uppercase;
}
.field input,
.field select {
    width: 100%;
    padding: 8px 12px;
    color: var(--supervisor-dark);
    font-family: inherit;
    font-size: 13px;
    font-weight: 600;
    background: var(--input-bg);
    border: 1px solid var(--supervisor-border);
    border-radius: 8px;
    outline: none;
}
.field input:focus,
.field select:focus {
    border-color: var(--supervisor-red);
    box-shadow: 0 0 0 3px rgba(205, 28, 24, 0.12);
}
.form-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-top: 4px;
}
.btn-primary,
.btn-cancel,
.btn-download {
    display: flex;
    align-items: center;
    gap: 7px;
    border: 1px solid var(--supervisor-border);
    border-radius: 8px;
    font-size: 13px;
    font-weight: 800;
    text-decoration: none;
}
.btn-primary,
.btn-cancel {
    padding: 11px 24px;
    cursor: pointer;
}
.btn-primary,
.btn-download {
    color: #fff;
    background: var(--supervisor-red);
}
.btn-primary:hover,
.btn-download:hover {
    background: var(--supervisor-dark);
}
.btn-cancel {
    color: var(--supervisor-dark);
    background: #fff;
    box-shadow: 0 4px 12px rgba(56, 0, 10, 0.10);
}
.btn-cancel i {
    color: var(--supervisor-red);
}
.btn-cancel:hover {
    background: var(--supervisor-peach);
}
.btn-download {
    padding: 10px 16px;
}
/* History table */
.history-card {
    margin-top: 22px;
    overflow: hidden;
    background: var(--supervisor-panel);
    border: 1px solid var(--supervisor-border);
    border-radius: 16px;
    box-shadow: 0 8px 18px rgba(56, 0, 10, 0.12);
}
.history-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 12px;
    padding: 16px 20px;
    border-bottom: 1px solid var(--supervisor-border);
}
.history-head h3 {
    font-size: 14px;
}
.table-scroll {
    overflow-x: auto;
}
table {
    width: 100%;
    min-width: 760px;
    font-size: 13px;
    border-collapse: collapse;
}
th {
    padding: 11px 12px;
    font-size: 10px;
    letter-spacing: 0.7px;
    text-align: left;
    text-transform: uppercase;
    background: #ffd6cc;
    border-bottom: 1px solid var(--supervisor-border);
}
td {
    padding: 12px;
    font-weight: 600;
    border-bottom: 1px solid rgba(56, 0, 10, 0.20);
}
.mono {
    font-family: 'DM Mono', monospace;
}
.empty-state {
    padding: 34px;
    color: var(--supervisor-muted);
    font-weight: 700;
    text-align: center;
}
/* Embedded dark-mode contrast layer */
/* Final dark-mode contrast layer loaded after page inline styles. */
body.dark {
    --text: #f8fafc !important;
    --muted: #cbd5e1 !important;
    --gold: #facc15 !important;
    --gold-bg: rgba(250, 204, 21, 0.16) !important;
    --gold-br: rgba(250, 204, 21, 0.55) !important;
    --card-bg: #1f2937 !important;
    --card-bdr: #94a3b8 !important;
    --input-bg: #0f172a !important;
    --input-bdr: #94a3b8 !important;
    --thead-bg: #334155 !important;
    --row-bdr: rgba(203, 213, 225, 0.28) !important;
    --row-hover: #334155 !important;
    background: linear-gradient(135deg, #0f172a 0%, #1e293b 52%, #312e81 100%) !important;
    color: #f8fafc !important;
}

body.dark .navbar {
    background: linear-gradient(90deg, #020617 0%, #1e293b 100%) !important;
    border-bottom-color: #64748b !important;
}

body.dark .logo,
body.dark .navbar .logo,
body.dark .navbar a,
body.dark .navbar .pill,
body.dark .navbar .icon-btn,
body.dark .role-chip {
    color: #f8fafc !important;
}

body.dark .nav-links a:hover,
body.dark .nav-links a.active {
    background: rgba(250, 204, 21, 0.18) !important;
    color: #fef9c3 !important;
    border-color: #facc15 !important;
}

body.dark .page-title h1,
body.dark .page-header h1,
body.dark h1,
body.dark h2,
body.dark h3,
body.dark h4,
body.dark .form-card-title,
body.dark .table-card-header h3,
body.dark .chart-card h3,
body.dark .report-card h3,
body.dark .stat-value,
body.dark .name-cell,
body.dark .price-cell,
body.dark td,
body.dark th,
body.dark label,
body.dark .field label {
    color: #f8fafc !important;
}

body.dark .page-title p,
body.dark .page-header p,
body.dark .stat-label,
body.dark .stat-sub,
body.dark .id-cell,
body.dark .desc-cell,
body.dark .sub,
body.dark .sec-label,
body.dark .sec-head,
body.dark .empty-state,
body.dark small {
    color: #cbd5e1 !important;
}

body.dark .table-card,
body.dark .form-card,
body.dark .history-card,
body.dark .stat-card,
body.dark .action-card,
body.dark .report-card,
body.dark .report-btn-card,
body.dark .chart-card,
body.dark .range-card,
body.dark .readonly-notice,
body.dark .alert,
body.dark .datetime-cell {
    background: #1f2937 !important;
    border-color: #94a3b8 !important;
    color: #f8fafc !important;
    box-shadow: 0 10px 24px rgba(2, 6, 23, 0.28) !important;
}

body.dark .table-card *,
body.dark .form-card *,
body.dark .history-card *,
body.dark .stat-card *,
body.dark .action-card *,
body.dark .report-card *,
body.dark .report-btn-card *,
body.dark .chart-card *,
body.dark .range-card *,
body.dark .readonly-notice *,
body.dark .datetime-cell * {
    color: inherit;
}

body.dark thead tr,
body.dark th {
    background: #334155 !important;
    color: #f8fafc !important;
}

body.dark tbody tr:hover,
body.dark .table-card:hover,
body.dark .form-card:hover,
body.dark .history-card:hover,
body.dark .stat-card:hover,
body.dark .action-card:hover,
body.dark .report-card:hover,
body.dark .report-btn-card:hover,
body.dark .chart-card:hover,
body.dark .range-card:hover,
body.dark .readonly-notice:hover {
    background: #334155 !important;
    color: #f8fafc !important;
}

body.dark tbody tr:hover *,
body.dark .table-card:hover *,
body.dark .form-card:hover *,
body.dark .history-card:hover *,
body.dark .stat-card:hover *,
body.dark .action-card:hover *,
body.dark .report-card:hover *,
body.dark .report-btn-card:hover *,
body.dark .chart-card:hover *,
body.dark .range-card:hover *,
body.dark .readonly-notice:hover * {
    color: #f8fafc !important;
}

body.dark input,
body.dark select,
body.dark textarea,
body.dark .search-box,
body.dark .field input,
body.dark .field select,
body.dark .field textarea,
body.dark .file-label {
    background: #0f172a !important;
    border-color: #94a3b8 !important;
    color: #f8fafc !important;
}

body.dark input::placeholder,
body.dark textarea::placeholder,
body.dark .search-box::placeholder {
    color: #94a3b8 !important;
}

body.dark select option {
    background: #0f172a !important;
    color: #f8fafc !important;
}

body.dark .btn-primary,
body.dark .btn-add,
body.dark .btn-export,
body.dark .btn-download,
body.dark .btn-report,
body.dark .btn-dl,
body.dark .logout-btn,
body.dark button,
body.dark input[type="button"],
body.dark input[type="submit"],
body.dark a[class*="btn"] {
    background: #facc15 !important;
    border-color: #fef08a !important;
    color: #111827 !important;
}

body.dark .btn-primary *,
body.dark .btn-add *,
body.dark .btn-export *,
body.dark .btn-download *,
body.dark .btn-report *,
body.dark .btn-dl *,
body.dark .logout-btn *,
body.dark button *,
body.dark a[class*="btn"] * {
    color: #111827 !important;
}

body.dark .btn-primary:hover,
body.dark .btn-add:hover,
body.dark .btn-export:hover,
body.dark .btn-download:hover,
body.dark .btn-report:hover,
body.dark .btn-dl:hover,
body.dark .logout-btn:hover,
body.dark button:hover,
body.dark input[type="button"]:hover,
body.dark input[type="submit"]:hover,
body.dark a[class*="btn"]:hover {
    background: #fde68a !important;
    color: #111827 !important;
}

body.dark .btn-cancel,
body.dark .filter-btn,
body.dark .icon-btn,
body.dark .toggle-btn {
    background: #334155 !important;
    border-color: #94a3b8 !important;
    color: #f8fafc !important;
}

body.dark .btn-cancel *,
body.dark .filter-btn *,
body.dark .icon-btn *,
body.dark .toggle-btn * {
    color: #f8fafc !important;
}

body.dark .filter-btn.active,
body.dark .filter-btn:hover {
    background: #facc15 !important;
    color: #111827 !important;
}

body.dark .filter-btn.active *,
body.dark .filter-btn:hover * {
    color: #111827 !important;
}

body.dark .count-badge,
body.dark .type-pill,
body.dark .role-chip {
    background: #334155 !important;
    border-color: #94a3b8 !important;
    color: #f8fafc !important;
}

body.dark .pending-badge,
body.dark .badge.pending,
body.dark .status-badge.pending {
    background: #fef3c7 !important;
    border-color: #f59e0b !important;
    color: #451a03 !important;
}

body.dark .deliv-badge,
body.dark .badge.delivered,
body.dark .status-badge.delivered,
body.dark .stock-status.ok,
body.dark .stock-ok {
    background: #dcfce7 !important;
    border-color: #22c55e !important;
    color: #052e16 !important;
}

body.dark .low-badge,
body.dark .stock-status.low,
body.dark .stock-low,
body.dark .status-badge.not-delivered,
body.dark .alert.error {
    background: #fee2e2 !important;
    border-color: #ef4444 !important;
    color: #450a0a !important;
}

body.dark .alert.success {
    background: #dcfce7 !important;
    border-color: #22c55e !important;
    color: #052e16 !important;
}

/* Comprehensive white text color for all elements in dark mode */
body.dark,
body.dark * {
    color: #f8fafc !important;
}

/* Exception: Keep specific badge colors */
body.dark .pending-badge,
body.dark .badge.pending,
body.dark .status-badge.pending,
body.dark .pending-badge *,
body.dark .badge.pending *,
body.dark .status-badge.pending * {
    color: #451a03 !important;
}

body.dark .deliv-badge,
body.dark .badge.delivered,
body.dark .status-badge.delivered,
body.dark .stock-status.ok,
body.dark .stock-ok,
body.dark .deliv-badge *,
body.dark .badge.delivered *,
body.dark .status-badge.delivered *,
body.dark .stock-status.ok *,
body.dark .stock-ok * {
    color: #052e16 !important;
}

body.dark .low-badge,
body.dark .stock-status.low,
body.dark .stock-low,
body.dark .status-badge.not-delivered,
body.dark .alert.error,
body.dark .low-badge *,
body.dark .stock-status.low *,
body.dark .stock-low *,
body.dark .status-badge.not-delivered *,
body.dark .alert.error * {
    color: #450a0a !important;
}

body.dark .alert.success,
body.dark .alert.success * {
    color: #052e16 !important;
}

/* Button text colors - keep them for visibility */
body.dark .btn-primary,
body.dark .btn-add,
body.dark .btn-export,
body.dark .btn-download,
body.dark .btn-report,
body.dark .btn-dl,
body.dark .logout-btn,
body.dark button,
body.dark input[type="button"],
body.dark input[type="submit"],
body.dark a[class*="btn"],
body.dark .btn-primary *,
body.dark .btn-add *,
body.dark .btn-export *,
body.dark .btn-download *,
body.dark .btn-report *,
body.dark .btn-dl *,
body.dark .logout-btn *,
body.dark button *,
body.dark a[class*="btn"] * {
    color: #111827 !important;
}
</style>
<link rel="stylesheet" href="nav_responsive.css">
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
                            <?= htmlspecialchars($f['name']) ?><?= display_part_number($f['part_number'] ?? '', '') !== '' ? ' - ' . htmlspecialchars(display_part_number($f['part_number'] ?? '', '')) : '' ?> (<?= htmlspecialchars($f['size']) ?>) - Stock: <?= (int)$f['quantity'] ?>
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
                    <td class="mono"><?= $index + 1 ?></td>
                    <td><?= htmlspecialchars($row['name']) ?></td>
                    <td class="mono"><?= htmlspecialchars(display_part_number($row['part_number'] ?? '', '-')) ?></td>
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
function tick(){const now=new Date();document.getElementById('dateStr').textContent=now.toLocaleDateString('en-IN');}
setInterval(tick,1000);tick();
function toggleDark(){document.body.classList.toggle('dark');localStorage.setItem('boltTheme',document.body.classList.contains('dark')?'dark':'light');}
if(localStorage.getItem('boltTheme')==='dark')document.body.classList.add('dark');
setTimeout(() => {
    document.querySelectorAll('.alert.success').forEach(alert => {
        alert.style.transition = 'opacity 0.25s ease, transform 0.25s ease';
        alert.style.opacity = '0';
        alert.style.transform = 'translate(-50%, -6px)';
        setTimeout(() => alert.remove(), 300);
    });
}, 2700);
</script>
</body>
</html>
