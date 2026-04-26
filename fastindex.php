<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Bolt Base — Fastener Inventory System</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

body {
    font-family: 'Segoe UI', sans-serif;
    background: #fff;
    color: #111;
    min-height: 100vh;
    display: flex;
    flex-direction: column;
}

/* ── Navbar ── */
.navbar {
    background: #6B001A;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 2.5rem;
    height: 62px;
}
.nav-brand { display: flex; align-items: center; gap: 12px; }
.bolt-mini { display: grid; grid-template-columns: repeat(3,1fr); gap: 4px; opacity: 0.5; }
.bm { width: 6px; height: 6px; background: white; border-radius: 50%; }
.nav-title { color: white; font-size: 17px; font-weight: 700; letter-spacing: 1.5px; }
.nav-sub { color: rgba(255,255,255,0.45); font-size: 10px; letter-spacing: 2.5px; text-transform: uppercase; margin-top: 2px; }
.nav-btn {
    background: white;
    color: #6B001A;
    border: none;
    padding: 9px 22px;
    border-radius: 7px;
    font-size: 13px;
    font-weight: 700;
    cursor: pointer;
    text-decoration: none;
    transition: background 0.2s;
}
.nav-btn:hover { background: #f0e0e4; }

/* ── Hero ── */
.hero {
    flex: 1;
    background: #6B001A;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-align: center;
    padding: 5rem 2rem;
}
.hero-badge {
    display: inline-block;
    background: rgba(255,255,255,0.15);
    color: rgba(255,255,255,0.85);
    font-size: 11px;
    letter-spacing: 1.5px;
    text-transform: uppercase;
    padding: 6px 18px;
    border-radius: 20px;
    margin-bottom: 1.5rem;
}
.hero h1 {
    font-size: 40px;
    font-weight: 700;
    color: white;
    line-height: 1.2;
    margin-bottom: 1rem;
    max-width: 560px;
}
.hero p {
    font-size: 15px;
    color: rgba(255,255,255,0.6);
    line-height: 1.85;
    max-width: 440px;
    margin-bottom: 2.5rem;
}
.hero-btn {
    background: white;
    color: #6B001A;
    border: none;
    padding: 13px 36px;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 700;
    cursor: pointer;
    text-decoration: none;
    box-shadow: 0 4px 20px rgba(0,0,0,0.25);
    transition: background 0.2s, transform 0.1s;
}
.hero-btn:hover { background: #f0e0e4; }
.hero-btn:active { transform: scale(0.98); }

/* ── Footer ── */
.footer {
    background: #6B001A;
    padding: 1.5rem 2.5rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 10px;
}
.foot-brand { color: white; font-size: 13px; font-weight: 700; letter-spacing: 1px; }
.foot-sub { color: rgba(255,255,255,0.4); font-size: 11px; margin-top: 3px; }
.foot-copy { color: rgba(255,255,255,0.35); font-size: 11px; }

@media(max-width: 480px) {
    .hero h1 { font-size: 28px; }
    .navbar { padding: 0 1.25rem; }
    .footer { flex-direction: column; text-align: center; }
}
</style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar">
  <div class="nav-brand">
    <div class="bolt-mini">
      <?php for($i=0;$i<9;$i++) echo '<div class="bm"></div>'; ?>
    </div>
    <div>
      <div class="nav-title">BOLT BASE</div>
      <div class="nav-sub">Fastener Inventory</div>
    </div>
  </div>
  <a href="login.php" class="nav-btn">Operator Login</a>
</nav>

<!-- Hero -->
<section class="hero">
  <div class="hero-badge">BCA Final Year Project &mdash; 2024&ndash;25</div>
  <h1>Fastener Inventory Management System</h1>
  <p>A simple and secure system to manage your fastener stock, track movements, and keep your inventory organised.</p>
  <a href="login.php" class="hero-btn">
    <i class="fa-solid fa-right-to-bracket" style="margin-right:8px;"></i>Login to Dashboard
  </a>
</section>

<!-- Footer -->
<footer class="footer">
  <div>
    <div class="foot-brand">BOLT BASE</div>
    <div class="foot-sub">BCA Final Year Project &nbsp;&bull;&nbsp; 2024&ndash;25</div>
  </div>
  <div class="foot-copy">&copy; 2025 Bolt Base. All rights reserved.</div>
</footer>

</body>
</html>