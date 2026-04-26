<?php
session_start();
include "db.php";

$error = "";

if(isset($_POST['login']))
{
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = $_POST['password'];

    $sql = "SELECT * FROM users WHERE username='$username'";
    $result = mysqli_query($conn, $sql);

    if(mysqli_num_rows($result) > 0)
    {
        $user = mysqli_fetch_assoc($result);
        if(password_verify($password, $user['password']))
        {
            $_SESSION['username'] = $username;
            header("Location: dashboard.php");
            exit();
        }
        else { $error = "Invalid username or password"; }
    }
    else { $error = "Invalid username or password"; }
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Bolt Base — Login</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

body {
    font-family: 'Segoe UI', sans-serif;
    background: #1a1a1a;
    background-image: url('fastener.jpg');
    background-size: cover;
    background-position: center;
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
}

body::before {
    content: '';
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.55);
}

.page {
    position: relative;
    display: flex;
    width: 700px;
    min-height: 480px;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 24px 60px rgba(0,0,0,0.5);
}

/* ── Left panel ── */
.left {
    background: #6B001A;
    width: 220px;
    flex-shrink: 0;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 2.5rem 1.5rem;
    gap: 20px;
}

.bolt-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 10px;
    opacity: 0.18;
}

.bolt {
    width: 18px;
    height: 18px;
    background: white;
    border-radius: 50%;
    position: relative;
}

.bolt::after {
    content: '';
    position: absolute;
    top: 50%; left: 50%;
    transform: translate(-50%, -50%);
    width: 7px; height: 7px;
    background: #6B001A;
    border-radius: 50%;
}

.brand { color: white; text-align: center; }
.brand-title { font-size: 22px; font-weight: 600; letter-spacing: 1.5px; }
.brand-sub { font-size: 11px; opacity: 0.55; letter-spacing: 2.5px; text-transform: uppercase; margin-top: 5px; }

.divider { width: 40px; height: 1px; background: rgba(255,255,255,0.2); }

.tagline { font-size: 12px; color: rgba(255,255,255,0.4); text-align: center; line-height: 1.8; }

/* ── Right panel ── */
.right {
    flex: 1;
    background: white;
    display: flex;
    flex-direction: column;
    justify-content: center;
    padding: 3rem 2.5rem;
    color: #000000;
}

.accent-bar { width: 36px; height: 3px; background: #6B001A; border-radius: 2px; margin-bottom: 14px; }

.form-header { margin-bottom: 1.75rem; }
.form-header h2 { font-size: 22px; font-weight: 600; color: #000000; }
.form-header p { font-size: 13px; color: #111111; margin-top: 4px; font-weight: 500; }

.error {
    background: #fff0f2;
    border: 1px solid #f5c0cb;
    color: #7a0000;
    font-size: 13px;
    font-weight: 600;
    padding: 9px 14px;
    border-radius: 8px;
    margin-bottom: 1.25rem;
}

.field { margin-bottom: 1.2rem; }
.field label {
    display: block;
    font-size: 11px;
    font-weight: 700;
    color: #000000;
    letter-spacing: 0.8px;
    text-transform: uppercase;
    margin-bottom: 7px;
}

.field-wrap { position: relative; }
.field-wrap i.icon { position: absolute; left: 13px; top: 50%; transform: translateY(-50%); color: #333333; font-size: 14px; }

.field input {
    width: 100%;
    height: 42px;
    padding: 0 38px;
    border: 1.5px solid #cccccc;
    border-radius: 8px;
    font-size: 14px;
    color: #000000;
    font-weight: 500;
    background: #f5f5f5;
    outline: none;
    transition: border-color 0.2s, background 0.2s;
}

.field input::placeholder { color: #555555; font-weight: 400; }
.field input:focus { border-color: #6B001A; background: white; }

.eye { position: absolute; right: 12px; top: 50%; transform: translateY(-50%); cursor: pointer; color: #333333; font-size: 14px; transition: color 0.2s; }
.eye:hover { color: #6B001A; }

.login-btn {
    width: 100%;
    height: 44px;
    background: #6B001A;
    border: none;
    color: white;
    font-size: 14px;
    font-weight: 600;
    letter-spacing: 0.5px;
    border-radius: 8px;
    cursor: pointer;
    transition: background 0.2s, transform 0.1s, box-shadow 0.2s;
    margin-top: 0.25rem;
    box-shadow: 0 4px 15px rgba(107, 0, 26, 0.45);  /* ← add this */
}

.login-btn:hover {
    background: #8a0022;
    box-shadow: 0 6px 20px rgba(107, 0, 26, 0.6);   /* ← deeper on hover */
}

.login-btn:active {
    transform: scale(0.98);
    box-shadow: 0 2px 8px rgba(107, 0, 26, 0.35);   /* ← recedes on click */
}

.footer-note { font-size: 11px; color: #333333; font-weight: 500; text-align: center; margin-top: 1.5rem; }
</style>
</head>
<body>

<div class="page">

  <div class="left">
    <div class="bolt-grid">
      <?php for($i=0;$i<9;$i++) echo '<div class="bolt"></div>'; ?>
    </div>
    <div class="brand">
      <div class="brand-title">BOLT BASE</div>
      <div class="brand-sub">Fastener Inventory</div>
    </div>
    <div class="divider"></div>
    <div class="tagline">Precision parts.<br>Reliable tracking.<br>Zero loose ends.</div>
    <div class="bolt-grid" style="opacity:0.1;">
      <?php for($i=0;$i<9;$i++) echo '<div class="bolt"></div>'; ?>
    </div>
  </div>

  <div class="right">
    <div class="accent-bar"></div>
    <div class="form-header">
      <h2>Operator login</h2>
      <p>Sign in to access your inventory dashboard</p>
    </div>

    <?php if($error != ""): ?>
    <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST">

      <div class="field">
        <label>Username</label>
        <div class="field-wrap">
          <i class="fa-solid fa-user icon"></i>
          <input type="text" name="username" placeholder="Enter username" required>
        </div>
      </div>

      <div class="field">
        <label>Password</label>
        <div class="field-wrap">
          <i class="fa-solid fa-lock icon"></i>
          <input type="password" id="password" name="password" placeholder="Enter password" required>
          <span class="eye" onclick="togglePassword()">
            <i id="eyeIcon" class="fa-solid fa-eye-slash"></i>
          </span>
        </div>
      </div>

      <button type="submit" name="login" class="login-btn">Sign in</button>

    </form>

    <div class="footer-note">Authorized personnel only </div>
  </div>

</div>

<script>
function togglePassword() {
    const pw = document.getElementById('password');
    const icon = document.getElementById('eyeIcon');
    if(pw.type === 'password') {
        pw.type = 'text';
        icon.classList.replace('fa-eye-slash', 'fa-eye');
    } else {
        pw.type = 'password';
        icon.classList.replace('fa-eye', 'fa-eye-slash');
    }
}
</script>

</body>
</html>