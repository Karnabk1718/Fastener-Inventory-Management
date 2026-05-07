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
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

:root {
    --ink: #270009;
    --red: #7a001d;
    --red-soft: #9f1239;
    --olive: #636b2f;
    --sage: #d4de95;
    --paper: #fff8f6;
    --line: rgba(39, 0, 9, 0.18);
    --muted: #70414d;
}

body {
    font-family: 'DM Sans', 'Segoe UI', sans-serif;
    background: var(--paper);
    color: var(--ink);
    min-height: 100vh;
}

.nav {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    z-index: 50;
    height: 66px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 clamp(16px, 4vw, 54px);
    background: rgba(39, 0, 9, 0.72);
    border-bottom: 1px solid rgba(255, 255, 255, 0.18);
    backdrop-filter: blur(14px);
}

.brand {
    color: #fff;
    display: flex;
    align-items: center;
    gap: 12px;
    text-decoration: none;
}

.brand-mark {
    width: 36px;
    height: 36px;
    display: grid;
    place-items: center;
    border: 1px solid rgba(255, 255, 255, 0.36);
    border-radius: 8px;
    background: rgba(255, 255, 255, 0.12);
}

.brand-title {
    font-size: 18px;
    font-weight: 800;
    letter-spacing: 1px;
}

.brand-sub {
    font-size: 10px;
    letter-spacing: 2px;
    text-transform: uppercase;
    color: rgba(255, 255, 255, 0.62);
}

.nav-actions {
    display: flex;
    align-items: center;
    gap: 10px;
}

.nav-link,
.hero-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    min-height: 40px;
    padding: 0 18px;
    border-radius: 8px;
    border: 1px solid rgba(255, 255, 255, 0.42);
    color: #fff;
    text-decoration: none;
    font-size: 13px;
    font-weight: 800;
    transition: 0.2s ease;
    white-space: nowrap;
}

.nav-link.primary,
.hero-btn.primary {
    background: #fff;
    color: var(--red);
    border-color: #fff;
}

.nav-link:hover,
.hero-btn:hover {
    background: var(--sage);
    color: var(--ink);
    border-color: var(--ink);
}

.hero {
    min-height: 88vh;
    display: grid;
    align-items: end;
    background:
        linear-gradient(90deg, rgba(39, 0, 9, 0.92) 0%, rgba(78, 0, 18, 0.76) 45%, rgba(39, 0, 9, 0.30) 100%),
        url('fastener.jpg');
    background-size: cover;
    background-position: center;
    padding: 112px clamp(18px, 5vw, 72px) 54px;
}

.hero-inner {
    width: min(100%, 1060px);
}

.eyebrow {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    color: #ffe9e4;
    border: 1px solid rgba(255, 255, 255, 0.24);
    background: rgba(255, 255, 255, 0.10);
    border-radius: 999px;
    padding: 7px 14px;
    font-size: 11px;
    font-weight: 800;
    letter-spacing: 1px;
    text-transform: uppercase;
    margin-bottom: 18px;
}

h1 {
    color: #fff;
    font-size: clamp(38px, 7vw, 76px);
    line-height: 1;
    letter-spacing: 0;
    max-width: 820px;
}

.hero-copy {
    color: rgba(255, 255, 255, 0.80);
    font-size: clamp(15px, 2vw, 18px);
    line-height: 1.7;
    max-width: 610px;
    margin-top: 18px;
}

.hero-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    margin-top: 28px;
}

.hero-btn {
    min-height: 46px;
    padding: 0 22px;
}

.hero-btn.secondary {
    background: rgba(255, 255, 255, 0.12);
}

.metrics {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 1px;
    background: var(--ink);
    border-top: 1px solid var(--ink);
    border-bottom: 1px solid var(--ink);
}

.metric {
    background: #fffdfc;
    padding: 24px clamp(16px, 3vw, 34px);
}

.metric strong {
    display: block;
    color: var(--red);
    font-size: 24px;
    font-family: 'DM Mono', monospace;
    margin-bottom: 4px;
}

.metric span {
    color: var(--muted);
    font-size: 12px;
    font-weight: 800;
    letter-spacing: 0.8px;
    text-transform: uppercase;
}

.section {
    padding: 58px clamp(18px, 5vw, 72px);
}

.section-head {
    display: flex;
    align-items: end;
    justify-content: space-between;
    gap: 24px;
    margin-bottom: 24px;
}

.section-head h2 {
    font-size: clamp(26px, 4vw, 40px);
}

.section-head p {
    color: var(--muted);
    line-height: 1.6;
    max-width: 520px;
}

.feature-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 14px;
}

.feature {
    border: 1px solid var(--line);
    border-radius: 8px;
    padding: 20px;
    background: #fffdfc;
}

.feature i {
    width: 38px;
    height: 38px;
    display: inline-grid;
    place-items: center;
    border-radius: 8px;
    background: #ffe2dc;
    color: var(--red);
    margin-bottom: 16px;
}

.feature h3 {
    font-size: 16px;
    margin-bottom: 8px;
}

.feature p {
    color: var(--muted);
    font-size: 13px;
    line-height: 1.6;
}

.workflow {
    display: grid;
    grid-template-columns: 1.05fr 0.95fr;
    gap: 26px;
    align-items: center;
    background: #f3f6d8;
    border-top: 1px solid var(--line);
    border-bottom: 1px solid var(--line);
}

.workflow-image {
    position: relative;
    overflow: hidden;
    min-height: 330px;
    background: url('group.jpg') center/cover;
    border: 1px solid var(--ink);
    border-radius: 8px;
}

.workflow-list {
    display: grid;
    gap: 12px;
}

.step {
    display: grid;
    grid-template-columns: 42px 1fr;
    gap: 12px;
    align-items: start;
}

.step-num {
    height: 42px;
    display: grid;
    place-items: center;
    background: var(--olive);
    color: #fff;
    border-radius: 8px;
    font-family: 'DM Mono', monospace;
    font-weight: 800;
}

.step h3 {
    font-size: 15px;
    margin-bottom: 4px;
}

.step p {
    color: #45501f;
    font-size: 13px;
    line-height: 1.55;
}

.footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 16px;
    padding: 26px clamp(18px, 5vw, 72px);
    background: var(--ink);
    color: rgba(255, 255, 255, 0.70);
    font-size: 12px;
}

.footer strong {
    color: #fff;
    letter-spacing: 1px;
}

@media (max-width: 900px) {
    .nav { height: auto; min-height: 66px; gap: 12px; }
    .nav-actions { gap: 8px; }
    .nav-link { padding: 0 12px; font-size: 12px; }
    .metrics,
    .feature-grid,
    .workflow {
        grid-template-columns: 1fr 1fr;
    }
    .section-head {
        display: block;
    }
    .section-head p {
        margin-top: 10px;
    }
}

@media (max-width: 620px) {
    .nav {
        position: absolute;
        align-items: flex-start;
        flex-direction: column;
        padding-top: 14px;
        padding-bottom: 14px;
    }
    .nav-actions {
        width: 100%;
    }
    .nav-link {
        flex: 1;
    }
    .hero {
        min-height: 92vh;
        padding-top: 158px;
    }
    .metrics,
    .feature-grid,
    .workflow {
        grid-template-columns: 1fr;
    }
    .workflow-image {
        min-height: 230px;
    }
    .footer {
        flex-direction: column;
        align-items: flex-start;
    }
}
</style>
</head>
<body>
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
