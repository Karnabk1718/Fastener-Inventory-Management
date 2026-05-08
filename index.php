<?php
$year = date('Y');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Bolt Base | Fastener Inventory System</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&family=DM+Mono:wght@500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="css/user.css">
</head>
<body class="page-index">
<nav class="nav">
    <a class="brand" href="index.php">
        <span class="brand-mark"><i class="fa-solid fa-screwdriver-wrench"></i></span>
        <span>
            <span class="brand-title">BOLT BASE</span>
            <span class="brand-sub">Fastener Inventory</span>
        </span>
    </a>
    <div class="nav-actions">
        <a class="nav-link primary" href="login.php"><i class="fa-solid fa-right-to-bracket"></i> Login</a>
    </div>
</nav>

<main>
    <section class="hero">
        <div class="hero-inner">
            <div class="eyebrow"><i class="fa-solid fa-shield-halved"></i> Secure inventory control</div>
            <h1>Bolt Base</h1>
            <p class="hero-copy">A fastener inventory management system for tracking stock, suppliers, orders, low-stock alerts, and management reports from one clean workspace.</p>
            <div class="hero-actions">
                <a class="hero-btn primary" href="login.php"><i class="fa-solid fa-user-shield"></i> Supervisor Login</a>
                <a class="hero-btn secondary" href="login.php" onclick="localStorage.setItem('boltLoginRole','manager')"><i class="fa-solid fa-user-tie"></i> Manager Login</a>
            </div>
        </div>
    </section>

    <section class="metrics" aria-label="Project highlights">
        <div class="metric"><strong>01</strong><span>Stock Tracking</span></div>
        <div class="metric"><strong>02</strong><span>Supplier Records</span></div>
        <div class="metric"><strong>03</strong><span>Order Monitoring</span></div>
        <div class="metric"><strong>04</strong><span>PDF Reports</span></div>
    </section>

    <section class="section">
        <div class="section-head">
            <h2>Built for fastener operations</h2>
            <p>Supervisor tools focus on daily inventory work. Manager tools focus on visibility, analytics, and exportable reports.</p>
        </div>
        <div class="feature-grid">
            <article class="feature">
                <i class="fa-solid fa-screwdriver-wrench"></i>
                <h3>Fastener Catalog</h3>
                <p>Maintain part names, types, sizes, prices, images, and descriptions in one place.</p>
            </article>
            <article class="feature">
                <i class="fa-solid fa-boxes-stacked"></i>
                <h3>Inventory Levels</h3>
                <p>Track available quantity and identify items that need restocking before work slows down.</p>
            </article>
            <article class="feature">
                <i class="fa-solid fa-industry"></i>
                <h3>Supplier Details</h3>
                <p>Keep supplier contact and contract data close to the items and orders they support.</p>
            </article>
            <article class="feature">
                <i class="fa-solid fa-file-pdf"></i>
                <h3>Manager Reports</h3>
                <p>Generate daily, monthly, yearly, inventory, low-stock, supplier, and order PDFs.</p>
            </article>
        </div>
    </section>

    <section class="section workflow">
        <div class="workflow-image" role="img" aria-label="Grouped bolts, nuts and washers"></div>
        <div>
            <div class="section-head">
                <h2>Simple daily flow</h2>
            </div>
            <div class="workflow-list">
                <div class="step">
                    <div class="step-num">1</div>
                    <div><h3>Add or update parts</h3><p>Record new fasteners, inventory quantities, supplier details, and order entries.</p></div>
                </div>
                <div class="step">
                    <div class="step-num">2</div>
                    <div><h3>Monitor stock status</h3><p>Use low-stock views and order status indicators to keep material movement visible.</p></div>
                </div>
                <div class="step">
                    <div class="step-num">3</div>
                    <div><h3>Review reports</h3><p>Managers can inspect dashboards, charts, and export PDF reports when needed.</p></div>
                </div>
            </div>
        </div>
    </section>
</main>

<footer class="footer">
    <div><strong>BOLT BASE</strong> &nbsp; Fastener Inventory Management System</div>
    <div>&copy; <?= $year ?> Bolt Base. All rights reserved.</div>
</footer>
</body>
</html>
