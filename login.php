<?php
session_start();
include "db.php";
ensure_user_security_schema();
$error = "";
$captchaQuestion = $_SERVER['REQUEST_METHOD'] === 'POST' ? '' : generate_captcha();
if(isset($_POST['login']))
{
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $role_req = $_POST['role'] ?? 'supervisor'; // which login panel submitted
    $captcha  = trim($_POST['captcha'] ?? '');
    if ($captcha === '' || !isset($_SESSION['captcha_answer']) || $captcha !== $_SESSION['captcha_answer']) {
        $error = "Captcha answer is incorrect.";
    } else {
        try {
            $stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE username=? OR email=?");
            mysqli_stmt_bind_param($stmt, "ss", $username, $username);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            if($result && mysqli_num_rows($result) > 0)
            {
                $user = mysqli_fetch_assoc($result);
                if(password_verify($password, $user['password']))
                {
                    // Role mismatch check
                    if($user['role'] !== $role_req) {
                        $error = "Access denied. You are not registered as a " . ucfirst($role_req) . ".";
                    } else {
                        $_SESSION['username'] = $user['username'];
                        $_SESSION['role']     = $user['role'];
                        if($user['role'] === 'manager') {
                            header("Location: manager_dashboard.php");
                        } else {
                            header("Location: dashboard.php");
                        }
                        exit();
                    }
                }
                else { $error = "Invalid username or password"; }
            }
            else { $error = "Invalid username or password"; }
        } catch (Throwable $e) {
            error_log($e->getMessage());
            $error = "Login failed. Please try again.";
        }
    }
    if($error !== "")
    {
        $captchaQuestion = generate_captcha();
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Bolt Base — Login</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link rel="stylesheet" href="css/user.css">
</head>
<body class="login-page supervisor-mode page-login">
<a href="index.php" class="back-home"><i class="fa fa-arrow-left"></i> Back to Home</a>
<div class="role-tabs">
    <button class="role-tab supervisor active" id="tab-supervisor" onclick="switchRole('supervisor')">
        <i class="fa fa-shield-halved"></i><b> Supervisor Login</b>
    </button>
    <button class="role-tab manager" id="tab-manager" onclick="switchRole('manager')">
        <i class="fa fa-user-tie"></i><b> Manager Login</b>
    </button>
</div>
<div class="page" id="loginCard">
  <div class="left supervisor-bg" id="leftPanel">
    <div class="bolt-grid">
      <?php for($i=0;$i<9;$i++) echo '<div class="bolt"></div>'; ?>
    </div>
    <div class="brand">
      <div class="brand-title">BOLT BASE</div>
      <div class="brand-sub">Fastener Inventory</div>
      <div class="role-badge" id="roleBadge">Supervisor</div>
    </div>
    <div class="divider"></div>
    <div class="tagline" id="tagline">Precision parts.<br>Reliable tracking.<br>Zero loose ends.</div>
    <div class="bolt-grid" style="opacity:0.1;">
      <?php for($i=0;$i<9;$i++) echo '<div class="bolt"></div>'; ?>
    </div>
  </div>
  <div class="right">
    <div class="accent-bar supervisor" id="accentBar"></div>
    <div class="form-header">
      <h2 id="formTitle">Supervisor Login</h2>
      <p id="formSub">Sign in to manage inventory and operations</p>
    </div>
    <?php if($error != ""): ?>
    <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form method="POST">
      <input type="hidden" name="role" id="roleInput" value="supervisor">
      <div class="field">
        <label>Username</label>
        <div class="field-wrap">
          <i class="fa-solid fa-user icon"></i>
          <input type="text" name="username" placeholder="Enter username or email" id="usernameInput"
                 class="supervisor-focus" autocomplete="username" required>
        </div>
      </div>
      <div class="field">
        <label>Password</label>
        <div class="field-wrap">
          <i class="fa-solid fa-lock icon"></i>
          <input type="password" id="password" name="password" placeholder="Enter password"
                 class="supervisor-focus" autocomplete="current-password" required>
          <span class="eye" onclick="togglePassword()">
            <i id="eyeIcon" class="fa-solid fa-eye-slash"></i>
          </span>
        </div>
      </div>
      <div class="field">
        <label>Captcha: <?= htmlspecialchars($captchaQuestion) ?> = ?</label>
        <div class="field-wrap">
          <i class="fa-solid fa-shield-halved icon"></i>
          <input type="text" name="captcha" placeholder="Answer" class="supervisor-focus" required>
        </div>
      </div>
      <button type="submit" name="login" class="login-btn supervisor" id="loginBtn">Sign in</button>
    </form>
    <div class="footer-note" id="footerNote">
      <a href="forgot_password.php" style="color:inherit;font-weight:800;">Forgot password?</a> &nbsp; Authorized personnel only
    </div>
  </div>
</div>
<script>
function switchRole(role) {
    const isSup = role === 'supervisor';
    document.getElementById('roleInput').value = role;
    document.body.classList.toggle('supervisor-mode', isSup);
    document.body.classList.toggle('manager-mode', !isSup);
    // Tabs
    document.getElementById('tab-supervisor').classList.toggle('active', isSup);
    document.getElementById('tab-manager').classList.toggle('active', !isSup);
    // Left panel
    const lp = document.getElementById('leftPanel');
    lp.className = 'left ' + (isSup ? 'supervisor-bg' : 'manager-bg');
    // Badge & tagline
    document.getElementById('roleBadge').textContent  = isSup ? 'Supervisor' : 'Manager';
    document.getElementById('tagline').innerHTML = isSup
        ? 'Precision parts.<br>Reliable tracking.<br>Zero loose ends.'
        : 'Full visibility.<br>Smart reports.<br>Data-driven decisions.';
    // Right panel
    document.getElementById('accentBar').className   = 'accent-bar ' + role;
    document.getElementById('formTitle').textContent = isSup ? 'Supervisor Login' : 'Manager Login';
    document.getElementById('formSub').textContent   = isSup
        ? 'Sign in to manage inventory and operations'
        : 'Sign in to view reports and analytics';
    document.getElementById('loginBtn').className = 'login-btn ' + role;
    document.getElementById('footerNote').innerHTML = '<a href="forgot_password.php" style="color:inherit;font-weight:800;">Forgot password?</a> &nbsp; ' + (isSup
        ? 'Authorized personnel only'
        : 'Read-only access with full reporting');
    // Input focus class
    document.querySelectorAll('.field input').forEach(i => {
        i.className = role + '-focus';
    });
    // Card radius — supervisor tab left-aligned, manager tab is 2nd
    document.getElementById('loginCard').style.borderRadius = isSup
        ? '0 16px 16px 16px' : '16px 0 16px 16px';
}
function togglePassword() {
    const pw   = document.getElementById('password');
    const icon = document.getElementById('eyeIcon');
    if(pw.type === 'password') {
        pw.type = 'text';
        icon.classList.replace('fa-eye-slash', 'fa-eye');
    } else {
        pw.type = 'password';
        icon.classList.replace('fa-eye', 'fa-eye-slash');
    }
}
const savedRole = localStorage.getItem('boltLoginRole');
if(savedRole === 'manager') {
    switchRole('manager');
    localStorage.removeItem('boltLoginRole');
}
</script>
</body>
</html>