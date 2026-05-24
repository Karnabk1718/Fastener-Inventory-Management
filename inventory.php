<?php
session_start();
include "db.php";

if(!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$successMsg = '';

// DELETE
if(isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    mysqli_query($conn, "DELETE FROM stock WHERE id=$id");
    header("Location: inventory.php?success=deleted");
    exit();
}

if(isset($_GET['success'])) {
    if($_GET['success'] === 'added')   $successMsg = 'Stock added successfully.';
    if($_GET['success'] === 'updated') $successMsg = 'Stock updated successfully.';
    if($_GET['success'] === 'deleted') $successMsg = 'Stock record deleted successfully.';
    if($_GET['success'] === 'picked')  $successMsg = 'Stock picked successfully.';
}

// FETCH STOCK
$result = mysqli_query($conn, "
    SELECT s.id, s.fastener_id, f.name, f.part_number, f.type, f.size, f.unit_price, s.quantity, f.image, f.description
    FROM stock s
    JOIN fastener f ON s.fastener_id = f.id
    ORDER BY s.id ASC
");
$rows = [];
while($r = mysqli_fetch_assoc($result)) $rows[] = $r;
usort($rows, fn($a,$b) => $a['id'] - $b['id']);
$totalCount    = count($rows);
$lowStockCount = 0;
foreach($rows as $stockRow) {
    if((int)$stockRow['quantity'] < 20) $lowStockCount++;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Inventory – Bolt Base</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&family=DM+Mono:wght@500&display=swap" rel="stylesheet">
<style>
:root {
    --bg-from:  #800020;
    --bg-to:    #cc4444;
    --text:     #ffffff;
    --muted:    rgba(255,255,255,0.65);
    --gold:     #ffd700;
    --gold-bg:  rgba(255,215,0,0.15);
    --gold-br:  rgba(255,215,0,0.40);
    --danger:   #ff6b6b;
    --success:  #4ade80;
    --blue:     #60a5fa;
    --card-bg:    rgba(0,0,0,0.40);
    --card-bdr:   rgba(255,255,255,0.16);
    --input-bg:   rgba(0,0,0,0.40);
    --input-bdr:  rgba(255,255,255,0.25);
    --thead-bg:   rgba(0,0,0,0.50);
    --row-bdr:    rgba(255,255,255,0.10);
    --row-hover:  rgba(255,255,255,0.06);
}
*,
*::before,
*::after {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
}
body {
    font-family: 'DM Sans', 'Segoe UI', sans-serif;
    background: linear-gradient(135deg, var(--bg-from) 0%, var(--bg-to) 100%);
    color: var(--text);
    min-height: 100vh;
    transition: background 0.35s;
}
.navbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 14px 30px;
    background: rgba(0,0,0,0.40);
    backdrop-filter: blur(14px);
    border-bottom: 1px solid rgba(255,255,255,0.12);
    position: sticky;
    top: 0;
    z-index: 200;
    gap: 16px;
}
.logo {
    font-size: 18px;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 9px;
    white-space: nowrap;
}
.logo-icon {
    width: 32px;
    height: 32px;
    background: var(--gold-bg);
    border: 1.5px solid var(--gold);
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
}
.nav-links {
    display: flex;
    gap: 2px;
}
.nav-links a {
    color: rgba(255,255,255,0.75);
    padding: 6px 12px;
    border-radius: 8px;
    text-decoration: none;
    font-size: 13px;
    font-weight: 500;
    transition: 0.2s;
    white-space: nowrap;
}
.nav-links a:hover {
    background: rgba(255,255,255,0.12);
    color: #fff;
}
.nav-links a.active {
    background: rgba(255,255,255,0.16);
    color: #fff;
}
.nav-right {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-shrink: 0;
}
.pill {
    background: rgba(0,0,0,0.30);
    border: 1px solid rgba(255,255,255,0.14);
    border-radius: 20px;
    padding: 5px 11px;
    font-size: 12px;
    display: flex;
    align-items: center;
    gap: 5px;
    white-space: nowrap;
}
.pill i {
    font-size: 10px;
    opacity: 0.7;
}
.icon-btn {
    width: 32px;
    height: 32px;
    background: rgba(0,0,0,0.30);
    border: 1px solid rgba(255,255,255,0.14);
    border-radius: 8px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 13px;
    color: var(--text);
    transition: 0.2s;
}
.icon-btn:hover {
    background: rgba(0,0,0,0.50);
}
.logout-btn {
    background: var(--gold);
    color: #1a0a00;
    border: none;
    border-radius: 8px;
    padding: 6px 14px;
    font-size: 13px;
    font-weight: 700;
    cursor: pointer;
    text-decoration: none;
    transition: 0.2s;
    white-space: nowrap;
}
.logout-btn:hover {
    background: #ffe040;
}
.main {
    padding: 32px 30px 50px;
    max-width: 1300px;
    margin: 0 auto;
}
.page-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 28px;
    flex-wrap: wrap;
    gap: 12px;
}
.page-title {
    display: flex;
    align-items: center;
    gap: 12px;
}
.page-title-icon {
    width: 46px;
    height: 46px;
    background: var(--gold-bg);
    border: 1.5px solid var(--gold-br);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
}
.page-title h1 {
    font-size: 22px;
    font-weight: 600;
    letter-spacing: -0.3px;
    margin-bottom: 2px;
}
.page-title p {
    font-size: 13px;
    color: var(--muted);
}
.page-header-right {
    display: flex;
    align-items: center;
    gap: 10px;
}
.count-badge {
    background: var(--gold-bg);
    border: 1px solid var(--gold-br);
    color: var(--gold);
    border-radius: 20px;
    padding: 5px 14px;
    font-size: 13px;
    font-weight: 600;
}
.count-badge.alert-badge {
    background: rgba(255,107,107,0.16);
    border-color: rgba(255,107,107,0.35);
    color: #fecaca;
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 6px;
    transition: 0.2s;
}
.count-badge.alert-badge:hover {
    background: rgba(255,107,107,0.28);
}
.btn-export {
    background: rgba(255,255,255,0.12);
    color: #fff;
    border: 1px solid rgba(255,255,255,0.18);
    border-radius: 8px;
    padding: 9px 16px;
    font-size: 13px;
    font-weight: 700;
    cursor: pointer;
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 7px;
    transition: 0.2s;
}
.btn-export:hover {
    background: rgba(255,255,255,0.20);
}
.btn-add {
    background: var(--gold);
    color: #1a0a00;
    border: none;
    border-radius: 8px;
    padding: 9px 18px;
    font-size: 13px;
    font-weight: 700;
    cursor: pointer;
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 7px;
    transition: 0.2s;
}
.btn-add:hover {
    background: #ffe040;
}
.alert {
    padding: 12px 16px;
    border-radius: 10px;
    font-size: 13px;
    font-weight: 500;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
}
.alert.success {
    background: rgba(74,222,128,0.18);
    border: 1px solid rgba(74,222,128,0.35);
    color: var(--success);
}
.table-card {
    background: var(--card-bg);
    border: 1px solid var(--card-bdr);
    border-radius: 16px;
    overflow: hidden;
}
.table-card-header {
    padding: 18px 24px;
    border-bottom: 1px solid var(--card-bdr);
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
}
.table-card-header h3 {
    font-size: 14px;
    font-weight: 600;
    color: #fff;
    display: flex;
    align-items: center;
    gap: 8px;
}
.table-card-header h3::before {
    content: '';
    width: 3px;
    height: 14px;
    background: var(--gold);
    border-radius: 2px;
    display: inline-block;
}
.search-box {
    background: var(--input-bg);
    border: 1px solid var(--input-bdr);
    border-radius: 8px;
    padding: 7px 13px;
    color: #fff;
    font-family: inherit;
    font-size: 13px;
    font-weight: 500;
    outline: none;
    width: 230px;
    transition: border-color 0.2s;
}
.search-box::placeholder {
    color: rgba(255,255,255,0.40);
}
.search-box:focus {
    border-color: var(--gold);
}
table {
    width: 100%;
    border-collapse: collapse;
    font-size: 12.5px;
    table-layout: fixed;
}
thead tr {
    background: var(--thead-bg);
}
th {
    padding: 8px 8px;
    text-align: left;
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.8px;
    color: rgba(255,255,255,0.90);
    white-space: nowrap;
    overflow: hidden;
}
/* Fixed column widths so everything fits */
th:nth-child(1) {
    width: 40px;
}
/* ID */
th:nth-child(2) {
    width: 60px;
}
/* Image */
th:nth-child(3) {
    width: 120px;
}
/* Fastener Name */
th:nth-child(4) {
    width: 70px;
}
/* Part No */
th:nth-child(5) {
    width: 58px;
}
/* Type */
th:nth-child(6) {
    width: 44px;
}
/* Size */
th:nth-child(7) {
    width: 72px;
}
/* Price */
th:nth-child(8) {
    width: 48px;
}
/* Quantity */
th:nth-child(9) {
    width: 92px;
}
/* Status */
th:nth-child(10) {
    width: 160px;
}
/* Description */
td {
    padding: 8px 8px;
    border-bottom: 1px solid var(--row-bdr);
    vertical-align: middle;
    color: #ffffff;
    font-weight: 500;
    overflow: hidden;
}
tbody tr:last-child td {
    border-bottom: none;
}
tbody tr:hover {
    background: var(--row-hover);
}
/* ── Cell styles — pixel-perfect match to fastener.php ── */
.id-cell {
    font-family: 'DM Mono', monospace;
    font-size: 12.5px;
    color: rgba(255,255,255,0.70);
    font-weight: 500;
}
.name-cell {
    font-weight: 600;
    font-size: 14px;
    white-space: normal;
    word-break: break-word;
}
.part-cell {
    font-family: 'DM Mono', monospace;
    font-size: 11.5px;
    color: var(--muted);
    word-break: break-word;
}
.type-pill {
    background: rgba(255,215,0,0.18);
    border: 1px solid rgba(255,215,0,0.40);
    color: var(--gold);
    border-radius: 20px;
    padding: 4px 10px;
    font-size: 12px;
    font-weight: 600;
    display: inline-block;
    white-space: nowrap;
}
.size-cell {
    font-family: 'DM Mono', monospace;
    font-size: 13px;
    color: rgba(255,255,255,0.90);
}
.price-cell {
    font-weight: 700;
    font-size: 13.5px;
    color: #ffffff;
    white-space: nowrap;
}
.qty-cell {
    font-family: 'DM Mono', monospace;
    font-size: 13.5px;
    font-weight: 600;
    text-align: center;
}
.qty-cell.low-stock {
    color: #fecaca;
}
.desc-cell {
    font-size: 13px;
    color: var(--muted);
    white-space: normal;
    word-break: break-word;
    line-height: 1.55;
}
/* ── Stock status badge ── */
.stock-status {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 3px 7px;
    border-radius: 999px;
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.4px;
    white-space: nowrap;
}
.stock-status.low {
    background: rgba(255,107,107,0.18);
    border: 1px solid rgba(255,107,107,0.35);
    color: #fecaca;
}
.stock-status.ok {
    background: rgba(74,222,128,0.18);
    border: 1px solid rgba(74,222,128,0.35);
    color: #bbf7d0;
}
tbody tr.table-low-stock {
    background: rgba(255,107,107,0.04);
}
.action-cell {
    display: flex;
    flex-direction: column;
    gap: 5px;
    align-items: stretch;
}
.btn-edit {
    background: rgba(96,165,250,0.20);
    border: 1px solid rgba(96,165,250,0.40);
    color: #93c5fd;
    border-radius: 6px;
    padding: 5px 8px;
    font-size: 11px;
    font-weight: 600;
    text-decoration: none;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 4px;
    transition: 0.2s;
}
.btn-edit:hover {
    background: rgba(96,165,250,0.35);
}
.btn-delete {
    background: rgba(255,107,107,0.20);
    border: 1px solid rgba(255,107,107,0.40);
    color: #fca5a5;
    border-radius: 6px;
    padding: 5px 8px;
    font-size: 11px;
    font-weight: 600;
    text-decoration: none;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 4px;
    transition: 0.2s;
}
.btn-delete:hover {
    background: rgba(255,107,107,0.35);
}
.empty-state {
    text-align: center;
    padding: 48px 20px;
    color: rgba(255,255,255,0.50);
}
.empty-state i {
    font-size: 36px;
    margin-bottom: 12px;
    display: block;
    opacity: 0.4;
}
.empty-state p {
    font-size: 14px;
}
.thumb {
    width: 56px;
    height: 56px;
    object-fit: cover;
    border-radius: 8px;
    border: 1px solid var(--card-bdr);
    display: block;
}
.no-img {
    width: 56px;
    height: 56px;
    border-radius: 8px;
    background: rgba(255,255,255,0.07);
    border: 1px dashed rgba(255,255,255,0.20);
    display: flex;
    align-items: center;
    justify-content: center;
    color: rgba(255,255,255,0.30);
    font-size: 18px;
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
.table-card {
    background: var(--supervisor-panel) !important;
    border-color: var(--supervisor-border) !important;
    color: var(--supervisor-dark) !important;
    box-shadow: 0 8px 18px rgba(56, 0, 10, 0.12);
}
.table-card-header {
    border-bottom-color: var(--supervisor-border) !important;
}
.table-card-header h3,
.name-cell,
.price-cell {
    color: var(--supervisor-dark) !important;
}
.table-card-header h3::before {
    background: var(--supervisor-red) !important;
}
thead tr,
th {
    background: var(--thead-bg) !important;
    color: var(--supervisor-dark) !important;
}
td {
    color: var(--supervisor-dark) !important;
    border-bottom-color: var(--row-bdr) !important;
}
tbody tr:hover {
    background: var(--row-hover) !important;
}
.id-cell,
.desc-cell {
    color: var(--supervisor-muted) !important;
}
.search-box {
    background: var(--input-bg) !important;
    border-color: var(--input-bdr) !important;
    color: var(--supervisor-dark) !important;
}
.search-box::placeholder {
    color: rgba(56, 0, 10, 0.48) !important;
}
.search-box:focus {
    border-color: var(--supervisor-red) !important;
    box-shadow: 0 0 0 3px rgba(205, 28, 24, 0.12);
}
.btn-add,
.btn-export {
    background: var(--supervisor-red) !important;
    border: 1px solid var(--supervisor-border) !important;
    color: #fff !important;
}
.btn-add:hover,
.btn-export:hover {
    background: var(--supervisor-peach) !important;
    color: var(--supervisor-dark) !important;
}
.count-badge,
.type-pill {
    background: var(--supervisor-peach) !important;
    border-color: var(--supervisor-border) !important;
    color: var(--supervisor-dark) !important;
}
.btn-edit {
    background: rgba(255, 168, 150, 0.45) !important;
    border-color: var(--supervisor-border) !important;
    color: var(--supervisor-dark) !important;
}
.btn-delete {
    background: rgba(205, 28, 24, 0.12) !important;
    border-color: var(--supervisor-red) !important;
    color: var(--supervisor-deep) !important;
}
.alert.success {
    background: #ffe3dc !important;
    border-color: var(--supervisor-border) !important;
    color: var(--supervisor-dark) !important;
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
.stock-status.ok {
    background: #ffe3dc !important;
    border-color: var(--supervisor-border) !important;
    color: var(--supervisor-dark) !important;
}
.stock-status.low,
.qty-cell.low-stock {
    background: rgba(205, 28, 24, 0.14) !important;
    border-color: var(--supervisor-red) !important;
    color: var(--supervisor-deep) !important;
}
.thumb,
.no-img {
    border-color: var(--supervisor-border) !important;
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
    <div class="logo">
        <div class="logo-icon"><i class="fa-solid fa-screwdriver-wrench" style="color:var(--gold);font-size:13px"></i></div>
        BOLT BASE
    </div>
    <div class="nav-links">
        <a href="dashboard.php">Dashboard</a>
        <a href="fastener.php">Fasteners</a>
        <a href="inventory.php" class="active">Inventory</a>
        <a href="pick_list.php">Pick List</a>
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
            <div class="page-title-icon"><i class="fa-solid fa-boxes-stacked"></i></div>
            <div>
                <h1>Inventory Management</h1>
                <p>Track and manage stock levels for all fasteners</p>
            </div>
        </div>
        <div class="page-header-right">
            <div class="count-badge"><i class="fa fa-layer-group"></i> <?= $totalCount ?> Stock Records</div>
            <a href="low_stock.php" class="count-badge alert-badge">
                <i class="fa fa-triangle-exclamation"></i> <?= $lowStockCount ?> Low Stock
            </a>
            <a href="export_report.php?type=inventory" class="btn-export"><i class="fa fa-file-pdf"></i> Download</a>
            <a href="pick_list.php" class="btn-add"><i class="fa fa-clipboard-list"></i> Pick Stock</a>
            <a href="add_inventory.php" class="btn-add"><i class="fa fa-plus"></i> Add Stock</a>
        </div>
    </div>

    <?php if($successMsg): ?>
    <div class="alert success"><i class="fa fa-circle-check"></i> <?= $successMsg ?></div>
    <?php endif; ?>

    <div class="table-card">
        <div class="table-card-header">
            <h3>All Inventory</h3>
            <input type="text" class="search-box" id="searchInput" placeholder="Search inventory…" onkeyup="filterTable()">
        </div>
        <table id="stockTable">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Image</th>
                    <th>Fastener Name</th>
                    <th>Part No.</th>
                    <th>Type</th>
                    <th>Size</th>
                    <th>Price / Unit (₹)</th>
                    <th>Quantity</th>
                    <th>Status</th>
                    <th>Description</th>
                </tr>
            </thead>
            <tbody>
                <?php if($totalCount === 0): ?>
                <tr><td colspan="10">
                    <div class="empty-state">
                        <i class="fa-solid fa-boxes-stacked"></i>
                        <p>No stock records found. <a href="add_inventory.php" style="color:var(--gold)">Add stock now.</a></p>
                    </div>
                </td></tr>
                <?php else: foreach($rows as $index => $row): ?>
                <?php $isLowStock = (int)$row['quantity'] < 20; ?>
                <tr class="<?= $isLowStock ? 'table-low-stock' : '' ?>">
                    <td class="id-cell"><?= $index + 1 ?></td>
                    <td>
                        <?php if(!empty($row['image'])): ?>
                            <?php $imgSrc = rtrim(dirname($_SERVER['PHP_SELF']), '/') . '/uploads/' . htmlspecialchars($row['image']); ?>
                            <img src="<?= $imgSrc ?>" alt="<?= htmlspecialchars($row['name']) ?>" class="thumb"
                                 onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
                            <div class="no-img" style="display:none"><i class="fa-solid fa-image"></i></div>
                        <?php else: ?>
                            <div class="no-img"><i class="fa-solid fa-image"></i></div>
                        <?php endif; ?>
                    </td>
                    <td class="name-cell"><?= htmlspecialchars($row['name']) ?></td>
                    <td class="part-cell"><?= htmlspecialchars(display_part_number($row['part_number'] ?? '')) ?></td>
                    <td><span class="type-pill"><?= htmlspecialchars($row['type']) ?></span></td>
                    <td class="size-cell"><?= htmlspecialchars($row['size']) ?></td>
                    <td class="price-cell">&#8377;<?= number_format($row['unit_price'], 2) ?></td>
                    <td class="qty-cell <?= $isLowStock ? 'low-stock' : '' ?>"><?= $row['quantity'] ?></td>
                    <td>
                        <span class="stock-status <?= $isLowStock ? 'low' : 'ok' ?>">
                            <i class="fa <?= $isLowStock ? 'fa-triangle-exclamation' : 'fa-circle-check' ?>"></i>
                            <?= $isLowStock ? 'Low Stock' : 'In Stock' ?>
                        </span>
                    </td>
                    <td class="desc-cell"><?= htmlspecialchars($row['description'] ?? '—') ?></td>
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
}
setInterval(tick, 1000); tick();
function toggleDark() {
    document.body.classList.toggle('dark');
    localStorage.setItem('boltTheme', document.body.classList.contains('dark') ? 'dark' : 'light');
}
if(localStorage.getItem('boltTheme') === 'dark') document.body.classList.add('dark');
setTimeout(() => {
    document.querySelectorAll('.alert.success').forEach(alert => {
        alert.style.transition = 'opacity 0.25s ease, transform 0.25s ease';
        alert.style.opacity = '0';
        alert.style.transform = 'translate(-50%, -6px)';
        setTimeout(() => alert.remove(), 300);
    });
}, 2700);
function filterTable() {
    const q = document.getElementById('searchInput').value.toLowerCase();
    document.querySelectorAll('#stockTable tbody tr').forEach(r => {
        r.style.display = r.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
}
</script>
</body>
</html>
