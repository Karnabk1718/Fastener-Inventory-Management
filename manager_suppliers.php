<?php
session_start();
include "db.php";
include "manager_auth.php";

$result = mysqli_query($conn,"SELECT * FROM supplier ORDER BY id ASC");
$rows = [];
while($r = mysqli_fetch_assoc($result)) $rows[] = $r;
usort($rows, fn($a,$b) => $a['id'] - $b['id']);
$totalCount = count($rows);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Suppliers – Manager – Bolt Base</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&family=DM+Mono:wght@500&display=swap" rel="stylesheet">
<style>
:root {
    --bg-from:#1a3a6b;
    --bg-to:#2d5fa6;
    --text:#fff;
    --muted:rgba(255,255,255,0.65);
    --gold:#ffd700;
    --gold-bg:rgba(255,215,0,0.15);
    --gold-br:rgba(255,215,0,0.40);
    --card-bg:rgba(0,0,0,0.35);
    --card-bdr:rgba(255,255,255,0.16);
    --input-bg:rgba(0,0,0,0.35);
    --input-bdr:rgba(255,255,255,0.25);
    --thead-bg:rgba(0,0,0,0.45);
    --row-bdr:rgba(255,255,255,0.10);
    --row-hover:rgba(255,255,255,0.06);
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
}
.navbar {
    display:flex;
    justify-content:space-between;
    align-items:center;
    padding:14px 28px;
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
    font-weight:700;
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
}
.role-chip {
    background:rgba(26,58,107,0.60);
    border:1px solid rgba(100,160,255,0.40);
    color:#93c5fd;
    border-radius:20px;
    padding:4px 12px;
    font-size:11px;
    font-weight:700;
    text-transform:uppercase;
    letter-spacing:1px;
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
}
.logout-btn:hover {
    background:#ffe040;
}
.main {
    padding:28px 28px 50px;
    max-width:1300px;
    margin:0 auto;
}
.page-header {
    display:flex;
    align-items:center;
    justify-content:space-between;
    margin-bottom:24px;
    flex-wrap:wrap;
    gap:12px;
}
.page-title {
    display:flex;
    align-items:center;
    gap:12px;
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
.page-title h1 {
    font-size:22px;
    font-weight:600;
    margin-bottom:2px;
}
.page-title p {
    font-size:13px;
    color:var(--muted);
}
.count-badge {
    background:var(--gold-bg);
    border:1px solid var(--gold-br);
    color:var(--gold);
    border-radius:20px;
    padding:5px 14px;
    font-size:13px;
    font-weight:600;
}
.btn-export {
    background:rgba(255,255,255,0.12);
    color:#fff;
    border:1px solid rgba(255,255,255,0.18);
    border-radius:8px;
    padding:9px 16px;
    font-size:13px;
    font-weight:700;
    cursor:pointer;
    text-decoration:none;
    display:flex;
    align-items:center;
    gap:7px;
    transition:0.2s;
}
.btn-export:hover {
    background:rgba(255,255,255,0.20);
}
.readonly-notice {
    background:rgba(96,165,250,0.12);
    border:1px solid rgba(96,165,250,0.30);
    color:#93c5fd;
    border-radius:10px;
    padding:10px 16px;
    font-size:13px;
    margin-bottom:18px;
    display:flex;
    align-items:center;
    gap:8px;
}
.table-card {
    background:var(--card-bg);
    border:1px solid var(--card-bdr);
    border-radius:16px;
    overflow:hidden;
}
.table-card-header {
    padding:16px 22px;
    border-bottom:1px solid var(--card-bdr);
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:12px;
}
.table-card-header h3 {
    font-size:14px;
    font-weight:600;
    display:flex;
    align-items:center;
    gap:8px;
}
.table-card-header h3::before {
    content:'';
    width:3px;
    height:14px;
    background:var(--gold);
    border-radius:2px;
}
.search-box {
    background:var(--input-bg);
    border:1px solid var(--input-bdr);
    border-radius:8px;
    padding:7px 13px;
    color:#fff;
    font-family:inherit;
    font-size:13px;
    outline:none;
    width:220px;
}
.search-box::placeholder {
    color:rgba(255,255,255,0.40);
}
.search-box:focus {
    border-color:var(--gold);
}
table {
    width:100%;
    border-collapse:collapse;
    font-size:13.5px;
}
thead tr {
    background:var(--thead-bg);
}
th {
    padding:12px 16px;
    text-align:left;
    font-size:11px;
    font-weight:700;
    text-transform:uppercase;
    letter-spacing:1px;
    color:rgba(255,255,255,0.90);
}
td {
    padding:13px 16px;
    border-bottom:1px solid var(--row-bdr);
    color:#fff;
    font-weight:500;
    vertical-align:middle;
}
tbody tr:last-child td {
    border-bottom:none;
}
tbody tr:hover {
    background:var(--row-hover);
}
.id-cell {
    font-family:'DM Mono',monospace;
    font-size:12px;
    color:rgba(255,255,255,0.65);
}
.name-cell {
    font-weight:600;
    font-size:14px;
}
.contact-cell {
    font-family:'DM Mono',monospace;
    font-size:13px;
}
.date-cell {
    font-family:'DM Mono',monospace;
    font-size:13px;
}
:root {
    --bg-from:#dff3ff;
    --bg-to:#b9e3ff;
    --text:#071827;
    --muted:#315166;
    --gold:#0b4f8a;
    --gold-bg:#d8efff;
    --gold-br:#000;
    --card-bg:rgba(255,255,255,0.82);
    --card-bdr:#000;
    --input-bg:#fff;
    --input-bdr:#000;
    --thead-bg:#c7e8fb;
    --row-bdr:#000;
    --row-hover:#d5ecff;
}
.navbar,
.pill,
.icon-btn,
.logout-btn,
.btn-export,
.count-badge,
.readonly-notice,
.table-card,
.search-box,
.page-title-icon {
    border-color:#000!important;
}
.navbar {
    background:rgba(223,243,255,0.92);
}
.nav-links a,
.pill,
td,
th,
.btn-export {
    color:#071827;
}
.nav-links a:hover,
.nav-links a.active,
.icon-btn:hover,
.logout-btn:hover,
.btn-export:hover {
    background:#073b78!important;
    color:#fff!important;
    border-color:#000!important;
    text-decoration:none;
}
.table-card:hover,
.readonly-notice:hover {
    background:#073b78!important;
    border-color:#000!important;
    color:#fff;
}
.table-card:hover *,
.readonly-notice:hover * {
    color:#fff!important;
}
/* Page manager theme overrides */
:root {
    --manager-olive: #636b2f;
    --manager-sage: #bac095;
    --manager-light: #d4de95;
    --manager-dark: #252914;
    --manager-panel: #fbfcf3;
    --manager-panel-soft: #f4f7df;
    --manager-muted: #3f472a;
    --manager-border: #111;
    --bg-from: #eef2cf;
    --bg-to: var(--manager-sage);
    --text: var(--manager-dark);
    --muted: var(--manager-muted);
    --gold: var(--manager-olive);
    --gold-bg: var(--manager-light);
    --gold-br: var(--manager-border);
    --card-bg: var(--manager-panel);
    --card-bdr: var(--manager-border);
    --input-bg: #fff;
    --input-bdr: var(--manager-border);
    --thead-bg: #e9edc6;
    --row-bdr: rgba(17, 17, 17, 0.22);
    --row-hover: #f1f5d6;
}
body {
    background: linear-gradient(135deg, #f7f9e8 0%, #e7edbd 48%, var(--manager-sage) 100%) !important;
    color: var(--manager-dark) !important;
    overflow-x: hidden;
}
.navbar {
    background: linear-gradient(90deg, var(--manager-dark) 0%, var(--manager-olive) 100%) !important;
    border-bottom: 1px solid var(--manager-border) !important;
    box-shadow: 0 8px 22px rgba(61, 65, 39, 0.24);
    justify-content: flex-start !important;
    flex-wrap: nowrap !important;
    gap: 10px !important;
    padding: 10px 18px !important;
    overflow-x: auto;
}
.logo {
    flex: 0 0 auto;
    color: #fff !important;
    font-weight: 800 !important;
}
.navbar .logo,
.navbar .logo *:not(.role-chip):not(.role-chip *) {
    color: #fff !important;
}
.logo-icon,
.page-title-icon,
.report-card-icon,
.stat-icon {
    background: var(--manager-light) !important;
    border-color: var(--manager-border) !important;
    color: #000 !important;
}
.logo-icon i,
.page-title-icon i,
.report-card-icon i,
.stat-icon i,
.table-card-header i,
.btn-export i,
.btn-dl i,
.btn-report i,
.report-btn-card i,
.count-badge i,
.low-badge i,
.pending-badge i,
.deliv-badge i {
    color: #000 !important;
}
.role-chip {
    background: rgba(212, 222, 149, 0.22) !important;
    border-color: var(--manager-border) !important;
    color: #f6ffd5 !important;
}
.nav-links {
    flex: 0 0 auto;
    flex-wrap: nowrap !important;
    gap: 3px !important;
}
.nav-links a {
    color: rgba(255, 255, 255, 0.84) !important;
    border: 1px solid #000 !important;
    padding: 6px 9px !important;
    font-size: 12px !important;
    white-space: nowrap;
}
.nav-links a:hover,
.nav-links a.active {
    background: rgba(212, 222, 149, 0.20) !important;
    color: #fff !important;
}
.nav-right {
    flex: 0 0 auto;
    margin-left: auto;
    gap: 5px !important;
}
.navbar .pill,
.navbar .icon-btn {
    color: #fff !important;
    background: rgba(255, 255, 255, 0.08) !important;
    border-color: var(--manager-border) !important;
}
.main {
    width: min(100%, 1280px) !important;
    padding: 16px clamp(10px, 2vw, 22px) 32px !important;
    overflow-x: hidden;
}
.page-title h1,
.page-header h1,
h1 {
    color: var(--manager-dark) !important;
}
.page-title p,
.page-header p,
.sub,
.sec-label,
.sec-head {
    color: var(--manager-muted) !important;
}
.logout-btn,
.btn-report,
.btn-export,
.btn-dl,
.report-btn-card,
.filter-btn,
button,
input[type="button"],
input[type="submit"],
a[class*="btn"] {
    background: var(--manager-olive) !important;
    border: 1px solid var(--manager-border) !important;
    color: #fff !important;
    font-weight: 700;
}
.logout-btn:hover,
.btn-report:hover,
.btn-export:hover,
.btn-dl:hover,
.report-btn-card:hover,
.filter-btn:hover,
.filter-btn.active,
button:hover,
input[type="button"]:hover,
input[type="submit"]:hover,
a[class*="btn"]:hover {
    background: var(--manager-light) !important;
    color: var(--manager-dark) !important;
    border-color: var(--manager-border) !important;
    box-shadow: 0 5px 14px rgba(99, 107, 47, 0.18) !important;
    text-decoration: none;
}
.table-card,
.readonly-notice {
    background: var(--manager-panel) !important;
    border-color: var(--manager-border) !important;
    color: var(--manager-dark) !important;
    box-shadow: 0 8px 18px rgba(61, 65, 39, 0.12);
}
.table-card:hover,
.readonly-notice:hover {
    background: var(--manager-panel-soft) !important;
    color: var(--manager-dark) !important;
}
.table-card-header {
    border-bottom-color: var(--manager-border) !important;
}
.table-card-header h3,
.name-cell,
.price-cell,
td {
    color: var(--manager-dark) !important;
}
.table-card-header h3::before {
    background: var(--manager-olive) !important;
}
th {
    background: var(--thead-bg) !important;
    color: var(--manager-dark) !important;
}
td {
    border-bottom-color: var(--row-bdr) !important;
}
tbody tr:hover {
    background: var(--row-hover) !important;
}
.id-cell,
.desc-cell,
.readonly-notice {
    color: var(--manager-muted) !important;
}
.search-box,
input,
select {
    background: var(--input-bg) !important;
    border-color: var(--manager-border) !important;
    color: var(--manager-dark) !important;
}
.count-badge,
.type-pill {
    background: var(--manager-light) !important;
    border-color: var(--manager-border) !important;
    color: var(--manager-dark) !important;
    font-weight: 800 !important;
}
.pending-badge,
.badge.pending {
    background: #fff1b8 !important;
    color: #3d2f00 !important;
    border-color: var(--manager-border) !important;
}
.deliv-badge,
.badge.delivered,
.stock-ok {
    background: #dceec2 !important;
    color: #20360e !important;
    border-color: var(--manager-border) !important;
}
.low-badge,
.stock-low {
    background: #ffe0d7 !important;
    color: #5b170d !important;
    border-color: var(--manager-border) !important;
}
.thumb,
.no-img {
    border-color: var(--manager-border) !important;
}
/* Manager hover readability fixes */
.stat-card:hover,
.report-card:hover,
.range-card:hover,
.chart-card:hover,
.table-card:hover,
.readonly-notice:hover {
    background: #e4eabd !important;
    color: var(--manager-dark) !important;
    border-color: var(--manager-border) !important;
}
.stat-card:hover *,
.report-card:hover *,
.range-card:hover *,
.chart-card:hover *,
.table-card:hover *,
.readonly-notice:hover * {
    color: var(--manager-dark) !important;
}
tbody tr:hover {
    background: #e4eabd !important;
}
.logout-btn:hover,
.btn-report:hover,
.btn-export:hover,
.btn-dl:hover,
.report-btn-card:hover,
.filter-btn:hover,
.filter-btn.active,
button:hover,
input[type="button"]:hover,
input[type="submit"]:hover,
a[class*="btn"]:hover {
    background: #4d5528 !important;
    color: #fff !important;
    border-color: var(--manager-border) !important;
    box-shadow: 0 5px 14px rgba(37, 41, 20, 0.22) !important;
    text-decoration: none !important;
}
.logout-btn:hover *,
.btn-report:hover *,
.btn-export:hover *,
.btn-dl:hover *,
.report-btn-card:hover *,
.filter-btn:hover *,
.filter-btn.active *,
button:hover *,
input[type="button"]:hover *,
input[type="submit"]:hover *,
a[class*="btn"]:hover * {
    color: #fff !important;
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
        BOLT BASE &nbsp;<span class="role-chip"><i class="fa fa-user-tie"></i> Manager</span>
    </div>
    <div class="nav-links">
        <a href="manager_dashboard.php">Dashboard</a>
        <a href="manager_fasteners.php">Fasteners</a>
        <a href="manager_inventory.php">Inventory</a>
        <a href="manager_suppliers.php" class="active">Suppliers</a>
        <a href="manager_orders.php">Orders</a>
        <a href="manager_reports.php">Reports</a>
    </div>
    <div class="nav-right">
        <div class="pill"><i class="fa fa-calendar"></i><span id="dateStr"></span></div>
        <div class="pill"><i class="fa fa-user-tie"></i><?= htmlspecialchars($_SESSION['username']) ?></div>
        <div class="icon-btn" onclick="toggleDark()"><i class="fa-solid fa-moon"></i></div>
        <a href="logout.php" class="logout-btn">Logout</a>
    </div>
</div>
<div class="main">
    <div class="page-header">
        <div class="page-title">
            <div class="page-title-icon"><i class="fa-solid fa-industry"></i></div>
            <div><h1>Suppliers</h1><p>View all supplier records and contracts</p></div>
        </div>
        <div style="display:flex;gap:10px;align-items:center;">
            <div class="count-badge"><i class="fa fa-layer-group"></i> <?= $totalCount ?> Records</div>
            <a href="manager_report_export.php?type=supplier" class="btn-export"><i class="fa fa-file-pdf"></i> Download</a>
        </div>
    </div>
    <div class="table-card">
        <div class="table-card-header">
            <h3>All Suppliers</h3>
            <input type="text" class="search-box" id="searchInput" placeholder="Search suppliers…" onkeyup="filterTable()">
        </div>
        <table id="mainTable">
            <thead><tr><th>ID</th><th>Name</th><th>Contact</th><th>Address</th><th>Contract Date</th></tr></thead>
            <tbody>
            <?php if($totalCount===0): ?>
            <tr><td colspan="5" style="text-align:center;padding:40px;color:var(--muted)">No suppliers found.</td></tr>
            <?php else: foreach($rows as $i => $row): ?>
            <tr>
                <td class="id-cell"><?= $i+1 ?></td>
                <td class="name-cell"><?= htmlspecialchars($row['name']) ?></td>
                <td class="contact-cell"><?= htmlspecialchars($row['contact']) ?></td>
                <td><?= htmlspecialchars($row['address']) ?></td>
                <td class="date-cell"><?= htmlspecialchars($row['contract_date']) ?></td>
            </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
<script>
function tick(){const n=new Date();document.getElementById('dateStr').textContent=n.toLocaleDateString('en-IN');}
setInterval(tick,1000);tick();
function toggleDark(){document.body.classList.toggle('dark');localStorage.setItem('boltTheme',document.body.classList.contains('dark')?'dark':'light');}
if(localStorage.getItem('boltTheme')==='dark')document.body.classList.add('dark');
function filterTable(){const q=document.getElementById('searchInput').value.toLowerCase();document.querySelectorAll('#mainTable tbody tr').forEach(r=>{r.style.display=r.textContent.toLowerCase().includes(q)?'':'none';});}
</script>
</body>
</html>

