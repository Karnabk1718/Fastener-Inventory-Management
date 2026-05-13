<?php
session_start();
include "db.php";

if(!isset($_SESSION['username']))
{
    header("Location: login.php");
    exit();
}

// Stats
$totalFasteners = mysqli_fetch_row(mysqli_query($conn,"SELECT COUNT(*) FROM fastener"))[0];
$totalSuppliers = mysqli_fetch_row(mysqli_query($conn,"SELECT COUNT(*) FROM supplier"))[0];
$totalStock     = mysqli_fetch_row(mysqli_query($conn,"SELECT COALESCE(SUM(quantity),0) FROM stock"))[0];
$totalOrders    = mysqli_fetch_row(mysqli_query($conn,"SELECT COUNT(*) FROM orders"))[0];
$lowStockItems  = mysqli_fetch_row(mysqli_query($conn,"SELECT COUNT(*) FROM stock WHERE quantity < 20"))[0];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Bolt Base – Dashboard</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
/* ── THEME VARIABLES ── */
:root {
    --bg-from:  #800020;
    --bg-to:    #cc4444;
    --card:     rgba(0,0,0,0.40);
    --card-h:   rgba(0,0,0,0.50);
    --border:   rgba(255,255,255,0.16);
    --text:     #ffffff;
    --muted:    rgba(255,255,255,0.60);
    --gold:     #ffd700;
    --gold-bg:  rgba(255,215,0,0.18);
    --gold-bdr: rgba(255,215,0,0.35);
}
/* ── RESET & BASE ── */
*,
*::before,
*::after {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
}
body {
    font-family: 'Segoe UI', sans-serif;
    background: linear-gradient(135deg, var(--bg-from) 0%, var(--bg-to) 100%);
    color: var(--text);
    min-height: 100vh;
    transition: background 0.35s, color 0.35s;
}
/* ── NAVBAR ── */
.navbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 16px 30px;
    background: rgba(0,0,0,0.40);
    backdrop-filter: blur(12px);
    border-bottom: 1px solid var(--border);
    position: sticky;
    top: 0;
    z-index: 100;
}
.logo {
    font-size: 22px;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 8px;
    letter-spacing: -0.3px;
}
.logo-icon {
    width: 36px;
    height: 36px;
    background: var(--gold-bg);
    border: 1.5px solid var(--gold);
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
}
.nav-links {
    display: flex;
    gap: 4px;
}
.nav-links a {
    color: rgba(255,255,255,0.80);
    padding: 8px 15px;
    border-radius: 8px;
    text-decoration: none;
    font-size: 14px;
    font-weight: 500;
    transition: 0.2s;
}
.nav-links a:hover,
.nav-links a.active {
    background: rgba(255,255,255,0.15);
    color: #fff;
}
.nav-right {
    display: flex;
    align-items: center;
    gap: 9px;
}
.pill {
    background: var(--card);
    border: 1px solid var(--border);
    border-radius: 20px;
    padding: 5px 12px;
    font-size: 12px;
    display: flex;
    align-items: center;
    gap: 6px;
}
.pill i {
    font-size: 11px;
    opacity: 0.75;
}
.toggle-btn {
    width: 34px;
    height: 34px;
    background: var(--card);
    border: 1px solid var(--border);
    border-radius: 8px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 15px;
    transition: 0.2s;
    color: var(--text);
}
.toggle-btn:hover {
    background: var(--card-h);
}
.logout-btn {
    background: var(--gold);
    color: #1a0a00;
    border: none;
    border-radius: 8px;
    padding: 7px 15px;
    font-size: 13px;
    font-weight: 700;
    cursor: pointer;
    text-decoration: none;
    transition: 0.2s;
}
.logout-btn:hover {
    background: #ffe040;
}
/* ── MAIN ── */
.main {
    padding: 30px 28px 40px;
}
/* ── WELCOME ROW ── */
.welcome-row {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 26px;
    flex-wrap: wrap;
    gap: 12px;
}
.welcome-text h1 {
    font-size: 26px;
    font-weight: 700;
    margin-bottom: 4px;
}
.welcome-text p {
    font-size: 14px;
    color: var(--muted);
}
/* ── STAT CARDS ── */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 18px;
    margin-bottom: 22px;
}
.stat-card {
    background: var(--card);
    border: 1px solid var(--border);
    border-radius: 16px;
    padding: 22px 20px;
    position: relative;
    overflow: hidden;
    transition: 0.25s;
    cursor: pointer;
}
.stat-card:hover {
    transform: translateY(-5px);
    background: var(--card-h);
    box-shadow: 0 14px 30px rgba(0,0,0,0.25);
}
.stat-card::before {
    content: '';
    position: absolute;
    top: -22px;
    right: -22px;
    width: 84px;
    height: 84px;
    border-radius: 50%;
    opacity: 0.13;
}
.stat-card.s1::before {
    background: #ffd700;
}
.stat-card.s2::before {
    background: #60efff;
}
.stat-card.s3::before {
    background: #a78bfa;
}
.stat-card.s4::before {
    background: #4ade80;
}
.stat-icon {
    position: absolute;
    top: 18px;
    right: 18px;
    width: 36px;
    height: 36px;
    background: rgba(255,255,255,0.12);
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
}
.stat-label {
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 1px;
    color: var(--muted);
    margin-bottom: 8px;
}
.stat-value {
    font-size: 34px;
    font-weight: 700;
    line-height: 1;
    margin-bottom: 6px;
}
.stat-sub {
    font-size: 12px;
    color: var(--muted);
}
/* ── ACTION CARDS ── */
.action-row {
    display: grid;
    grid-template-columns: repeat(5, minmax(0, 1fr));
    gap: 18px;
}
.report-row {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 18px;
    margin-top: 22px;
}
.section-title {
    font-size: 14px;
    font-weight: 700;
    color: var(--muted);
    text-transform: uppercase;
    letter-spacing: 1px;
    margin: 22px 0 14px;
}
.action-card {
    background: var(--card);
    border: 1px solid var(--border);
    border-radius: 16px;
    padding: 22px 20px;
    display: flex;
    flex-direction: column;
    gap: 10px;
    transition: 0.25s;
    cursor: pointer;
}
.action-card:hover {
    transform: translateY(-4px);
    background: var(--card-h);
    border-color: var(--gold-bdr);
    box-shadow: 0 10px 24px rgba(0,0,0,0.2);
}
.ac-header {
    display: flex;
    align-items: center;
    gap: 12px;
}
.ac-icon {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    flex-shrink: 0;
}
.ac-icon.i1 {
    background: rgba(255,215,0,0.18);
    border: 1px solid rgba(255,215,0,0.30);
}
.ac-icon.i2 {
    background: rgba(96,239,255,0.15);
    border: 1px solid rgba(96,239,255,0.25);
}
.ac-icon.i3 {
    background: rgba(167,139,250,0.18);
    border: 1px solid rgba(167,139,250,0.28);
}
.ac-icon.i4 {
    background: rgba(74,222,128,0.15);
    border: 1px solid rgba(74,222,128,0.25);
}
.ac-icon.i5 {
    background: rgba(255,107,107,0.18);
    border: 1px solid rgba(255,107,107,0.28);
}
.ac-title {
    font-size: 16px;
    font-weight: 600;
}
.ac-desc {
    font-size: 13px;
    color: var(--muted);
    line-height: 1.5;
    flex: 1;
}
.ac-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-top: 1px solid var(--border);
    padding-top: 12px;
}
.ac-count {
    font-size: 13px;
    color: var(--muted);
}
.ac-count span {
    color: var(--text);
    font-weight: 600;
}
.ac-btn {
    background: var(--gold);
    color: #1a0a00;
    border: none;
    border-radius: 8px;
    padding: 6px 14px;
    font-size: 12px;
    font-weight: 700;
    cursor: pointer;
    text-decoration: none;
    transition: 0.2s;
}
.ac-btn:hover {
    background: #ffe040;
}
.report-card {
    background: var(--card);
    border: 1px solid var(--border);
    border-radius: 16px;
    padding: 18px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 14px;
}
.report-meta h3 {
    font-size: 15px;
    margin-bottom: 4px;
}
.report-meta p {
    font-size: 12px;
    color: var(--muted);
}
.report-btn {
    background: rgba(255,255,255,0.12);
    border: 1px solid var(--border);
    color: var(--text);
    border-radius: 10px;
    padding: 10px 14px;
    text-decoration: none;
    font-size: 12px;
    font-weight: 700;
}
.report-btn:hover {
    background: var(--card-h);
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
.stat-card,
.action-card,
.report-card {
    background: var(--supervisor-panel) !important;
    border-color: var(--supervisor-border) !important;
    color: var(--supervisor-dark) !important;
    box-shadow: 0 8px 18px rgba(56, 0, 10, 0.12);
}
.stat-card:hover,
.action-card:hover,
.report-card:hover {
    background: var(--supervisor-panel-soft) !important;
    border-color: var(--supervisor-border) !important;
    color: var(--supervisor-dark) !important;
    box-shadow: 0 12px 26px rgba(56, 0, 10, 0.18);
}
.welcome-text h1,
.ac-title,
.stat-value,
.report-meta h3,
.section-title {
    color: var(--supervisor-dark) !important;
}
.welcome-text p,
.ac-desc,
.ac-count,
.stat-label,
.stat-sub,
.report-meta p {
    color: var(--supervisor-muted) !important;
}
.stat-icon,
.ac-icon {
    background: var(--supervisor-peach) !important;
    border-color: var(--supervisor-border) !important;
    color: var(--supervisor-dark) !important;
}
.stat-icon i,
.ac-icon i {
    color: var(--supervisor-dark) !important;
}
.stat-card::before {
    opacity: 0.28 !important;
    filter: saturate(1.15);
}
.stat-card.s1::before {
    background: var(--supervisor-red) !important;
}
.stat-card.s2::before {
    background: #9B1313 !important;
}
.stat-card.s3::before {
    background: #FFA896 !important;
    opacity: 0.42 !important;
}
.stat-card.s4::before {
    background: #38000A !important;
}
.ac-btn,
.report-btn {
    background: var(--supervisor-red) !important;
    border: 1px solid var(--supervisor-border) !important;
    color: #fff !important;
}
.ac-btn:hover,
.report-btn:hover {
    background: var(--supervisor-peach) !important;
    color: var(--supervisor-dark) !important;
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
</head>
<body>

<!-- NAVBAR -->
<div class="navbar">

    <div class="logo">
        <div class="logo-icon"><i class="fa-solid fa-screwdriver-wrench" style="font-size:15px;color:var(--gold)"></i></div>
        BOLT BASE
    </div>

    <div class="nav-links">
        <a href="dashboard.php" class="active">Dashboard</a>
        <a href="fastener.php">Fasteners</a>
        <a href="inventory.php">Inventory</a>
        <a href="pick_list.php">Pick List</a>
        <a href="supplier.php">Suppliers</a>
        <a href="orders.php">Orders</a>
    </div>

    <div class="nav-right">
        <div class="pill"><i class="fa fa-calendar"></i><span id="dateStr"></span></div>
        <div class="pill"><i class="fa fa-user"></i><?= htmlspecialchars($_SESSION['username']) ?></div>
        <div class="toggle-btn" onclick="toggleDark()" title="Toggle dark mode">
            <i class="fa-solid fa-moon"></i>
        </div>
        <a href="logout.php" class="logout-btn">Logout</a>
    </div>

</div>

<!-- MAIN -->
<div class="main">

    <!-- WELCOME -->
    <div class="welcome-row">
        <div class="welcome-text">
            <h1>Welcome back, <?= htmlspecialchars($_SESSION['username']) ?> 👋</h1>
            <p>Here's what's happening with your fastener inventory today.</p>
        </div>
    </div>

    <!-- STAT CARDS -->
    <div class="stats-grid">

        <div class="stat-card s1" >
            <div class="stat-icon"><i class="fa-solid fa-screwdriver-wrench"></i></div>
            <div class="stat-label">Total Fasteners</div>
            <div class="stat-value"><?= $totalFasteners ?></div>
            <div class="stat-sub">All fastener records</div>
        </div>

        <div class="stat-card s2" ">
            <div class="stat-icon"><i class="fa-solid fa-box"></i></div>
            <div class="stat-label">Stock Units</div>
            <div class="stat-value"><?= number_format($totalStock) ?></div>
            <div class="stat-sub">Across all categories</div>
        </div>

        <div class="stat-card s3" >
            <div class="stat-icon"><i class="fa-solid fa-industry"></i></div>
            <div class="stat-label">Suppliers</div>
            <div class="stat-value"><?= $totalSuppliers ?></div>
            <div class="stat-sub">Registered suppliers</div>
        </div>

        <div class="stat-card s4" >
            <div class="stat-icon"><i class="fa-solid fa-cart-shopping"></i></div>
            <div class="stat-label">Total Orders</div>
            <div class="stat-value"><?= $totalOrders ?></div>
            <div class="stat-sub">All purchase orders</div>
        </div>

    </div>

    <!-- ACTION CARDS -->
    <div class="action-row">

        <div class="action-card" onclick="location.href='fastener.php'">
            <div class="ac-header">
                <div class="ac-icon i1"><i class="fa-solid fa-screwdriver-wrench"></i></div>
                <div class="ac-title">Fasteners</div>
            </div>
            <div class="ac-desc">Manage all fastener types — bolts, nuts, screws, washers and more.</div>
            <div class="ac-footer">
                <div class="ac-count">Items: <span><?= $totalFasteners ?></span></div>
                <a href="fastener.php" class="ac-btn">Open →</a>
            </div>
        </div>

        <div class="action-card" onclick="location.href='inventory.php'">
            <div class="ac-header">
                <div class="ac-icon i2"><i class="fa-solid fa-box"></i></div>
                <div class="ac-title">Inventory</div>
            </div>
            <div class="ac-desc">Track real-time stock levels, warehouses, and reorder alerts.</div>
            <div class="ac-footer">
                <div class="ac-count">Stock: <span><?= number_format($totalStock) ?></span></div>
                <a href="inventory.php" class="ac-btn">Open →</a>
            </div>
        </div>

        <div class="action-card" onclick="location.href='supplier.php'">
            <div class="ac-header">
                <div class="ac-icon i3"><i class="fa-solid fa-industry"></i></div>
                <div class="ac-title">Suppliers</div>
            </div>
            <div class="ac-desc">View and manage your supplier contacts, details and agreements.</div>
            <div class="ac-footer">
                <div class="ac-count">Total: <span><?= $totalSuppliers ?></span></div>
                <a href="supplier.php" class="ac-btn">Open →</a>
            </div>
        </div>

        <div class="action-card" onclick="location.href='orders.php'">
            <div class="ac-header">
                <div class="ac-icon i4"><i class="fa-solid fa-cart-shopping"></i></div>
                <div class="ac-title">Orders</div>
            </div>
            <div class="ac-desc">Place and track purchase orders across all your suppliers.</div>
            <div class="ac-footer">
                <div class="ac-count">Orders: <span><?= $totalOrders ?></span></div>
                <a href="orders.php" class="ac-btn">Open →</a>
            </div>
        </div>

        <div class="action-card" onclick="location.href='low_stock.php'">
            <div class="ac-header">
                <div class="ac-icon i5"><i class="fa-solid fa-triangle-exclamation"></i></div>
                <div class="ac-title">Low Stock</div>
            </div>
            <div class="ac-desc">Review only the inventory items that are below the minimum stock level of 20.</div>
            <div class="ac-footer">
                <div class="ac-count">Alerts: <span><?= $lowStockItems ?></span></div>
                <a href="low_stock.php" class="ac-btn">Open →</a>
            </div>
        </div>

    </div>

    <div class="section-title">Quick Reports</div>
    <div class="report-row">
        <div class="report-card">
            <div class="report-meta">
                <h3>Fasteners PDF</h3>
                <p>Download all fastener details in one report.</p>
            </div>
            <a href="export_report.php?type=fastener" class="report-btn">Download</a>
        </div>
        <div class="report-card">
            <div class="report-meta">
                <h3>Download</h3>
                <p>Includes stock quantities and low-stock status.</p>
            </div>
            <a href="export_report.php?type=inventory" class="report-btn">Download</a>
        </div>
        <div class="report-card">
            <div class="report-meta">
                <h3>Suppliers PDF</h3>
                <p>Collect supplier contacts and contract details.</p>
            </div>
            <a href="export_report.php?type=supplier" class="report-btn">Download</a>
        </div>
        <div class="report-card">
            <div class="report-meta">
                <h3>Download</h3>
                <p>Download order quantities, dates, and statuses.</p>
            </div>
            <a href="export_report.php?type=orders" class="report-btn">Download</a>
        </div>
    </div>

</div>

<script>
// ── DATETIME ──
function tick() {
    const now = new Date();
    document.getElementById('dateStr').textContent = now.toLocaleDateString('en-IN');
}
setInterval(tick, 1000);
tick();

// ── DARK MODE ──
function toggleDark() {
    document.body.classList.toggle('dark');
    localStorage.setItem('boltTheme', document.body.classList.contains('dark') ? 'dark' : 'light');
}
if(localStorage.getItem('boltTheme') === 'dark') document.body.classList.add('dark');
</script>
</body>
</html>
