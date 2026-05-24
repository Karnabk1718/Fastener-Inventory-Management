<?php
session_start();
include "db.php";
ensure_user_security_schema();
$error = "";
$selectedRole = ($_GET['role'] ?? '') === 'manager' ? 'manager' : 'supervisor';
$captchaQuestion = $_SERVER['REQUEST_METHOD'] === 'POST' ? '' : generate_captcha();
if(isset($_POST['login']))
{
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $role_req = $_POST['role'] ?? 'supervisor'; // which login panel submitted
    $role_req = $role_req === 'manager' ? 'manager' : 'supervisor';
    $selectedRole = $role_req;
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
<style>
*,
*::before,
*::after {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
}
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
    flex-direction: column;
    gap: 18px;
}
body::before {
    content: '';
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.60);
}
/* Role selector tabs above card */
.role-tabs {
    position: relative;
    display: flex;
    gap: 10px;
    z-index: 1;
}
.role-tab {
    padding: 10px 32px;
    border-radius: 10px 10px 0 0;
    font-size: 14px;
    font-weight: 700;
    cursor: pointer;
    border: none;
    letter-spacing: 0.5px;
    transition: 0.2s;
}
.role-tab.supervisor {
    background: #6B001A;
    color: #fff;
}
.role-tab.manager {
    background: rgba(255,255,255,0.15);
    color: rgba(255,255,255,0.7);
}
.role-tab.supervisor.active {
    background: white;
    color: #6B001A;
}
.role-tab.manager.active {
    background: #1a3a6b;
    color: white;
}
.role-tab:not(.active):hover {
    opacity: 0.85;
}
.back-home {
    position: fixed;
    right: 22px;
    bottom: 22px;
    z-index: 1;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    min-height: 36px;
    padding: 0 15px;
    border-radius: 999px;
    border: 1px solid rgba(255, 255, 255, 0.34);
    background: rgba(255, 255, 255, 0.12);
    color: #fff;
    text-decoration: none;
    font-size: 12px;
    font-weight: 800;
    backdrop-filter: blur(10px);
    box-shadow: 0 12px 28px rgba(0, 0, 0, 0.26);
    transition: 0.2s ease;
}
.back-home:hover {
    background: #fff7f5;
    color: #6b001a;
    border-color: #fff7f5;
}
.page {
    position: relative;
    display: flex;
    width: 700px;
    min-height: 480px;
    border-radius: 0 16px 16px 16px;
    overflow: hidden;
    box-shadow: 0 24px 60px rgba(0,0,0,0.5);
}
/* ── Left panel ── */
.left {
    width: 220px;
    flex-shrink: 0;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 2.5rem 1.5rem;
    gap: 20px;
    transition: background 0.3s;
}
.left.supervisor-bg {
    background: #6B001A;
}
.left.manager-bg {
    background: #1a3a6b;
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
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 7px;
    height: 7px;
    border-radius: 50%;
}
.left.supervisor-bg .bolt::after {
    background: #6B001A;
}
.left.manager-bg .bolt::after {
    background: #1a3a6b;
}
.brand {
    color: white;
    text-align: center;
}
.brand-title {
    font-size: 22px;
    font-weight: 600;
    letter-spacing: 1.5px;
}
.brand-sub {
    font-size: 11px;
    opacity: 0.55;
    letter-spacing: 2.5px;
    text-transform: uppercase;
    margin-top: 5px;
}
.role-badge {
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 2px;
    padding: 5px 14px;
    border-radius: 20px;
    border: 1px solid rgba(255,255,255,0.30);
    color: rgba(255,255,255,0.80);
    margin-top: 2px;
}
.divider {
    width: 40px;
    height: 1px;
    background: rgba(255,255,255,0.2);
}
.tagline {
    font-size: 12px;
    color: rgba(255,255,255,0.4);
    text-align: center;
    line-height: 1.8;
}
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
.accent-bar {
    width: 36px;
    height: 3px;
    border-radius: 2px;
    margin-bottom: 14px;
    transition: background 0.3s;
}
.accent-bar.supervisor {
    background: #6B001A;
}
.accent-bar.manager {
    background: #1a3a6b;
}
.form-header {
    margin-bottom: 1.75rem;
}
.form-header h2 {
    font-size: 22px;
    font-weight: 600;
    color: #000000;
}
.form-header p {
    font-size: 13px;
    color: #111111;
    margin-top: 4px;
    font-weight: 500;
}
.error {
    background: #fff0f2;
    border: 1px solid #f5c0cb;
    color: #7a0000;
    font-size: 13px;
    font-weight: 600;
    padding: 9px 14px;
    border-radius: 8px;
    margin-bottom: 1.25rem;
    transition: opacity 0.35s ease, transform 0.35s ease, margin 0.35s ease, padding 0.35s ease;
}
.error.hide-warning {
    opacity: 0;
    transform: translateY(-8px);
    margin-bottom: 0;
    padding-top: 0;
    padding-bottom: 0;
    pointer-events: none;
}
.field {
    margin-bottom: 1.2rem;
}
.field label {
    display: block;
    font-size: 11px;
    font-weight: 700;
    color: #000000;
    letter-spacing: 0.8px;
    text-transform: uppercase;
    margin-bottom: 7px;
}
.field-wrap {
    position: relative;
}
.field-wrap i.icon {
    position: absolute;
    left: 13px;
    top: 50%;
    transform: translateY(-50%);
    color: #333333;
    font-size: 14px;
}
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
.field input::placeholder {
    color: #555555;
    font-weight: 400;
}
.field input:focus {
    background: white;
}
.field input.supervisor-focus:focus {
    border-color: #6B001A;
}
.field input.manager-focus:focus {
    border-color: #1a3a6b;
}
.eye {
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    cursor: pointer;
    color: #333333;
    font-size: 14px;
    transition: color 0.2s;
}
.login-btn {
    width: 100%;
    height: 44px;
    border: none;
    color: white;
    font-size: 14px;
    font-weight: 600;
    letter-spacing: 0.5px;
    border-radius: 8px;
    cursor: pointer;
    transition: background 0.2s, transform 0.1s, box-shadow 0.2s;
    margin-top: 0.25rem;
}
.login-btn.supervisor {
    background: #6B001A;
    box-shadow: 0 4px 15px rgba(107,0,26,0.45);
}
.login-btn.supervisor:hover {
    background: #8a0022;
    box-shadow: 0 6px 20px rgba(107,0,26,0.6);
}
.login-btn.manager {
    background: #1a3a6b;
    box-shadow: 0 4px 15px rgba(26,58,107,0.45);
}
.login-btn.manager:hover {
    background: #244d8f;
    box-shadow: 0 6px 20px rgba(26,58,107,0.6);
}
.login-btn:active {
    transform: scale(0.98);
}
.footer-note {
    font-size: 11px;
    color: #333333;
    font-weight: 500;
    text-align: center;
    margin-top: 1.5rem;
}
body.login-page,
body:has(.role-tabs) {
    --login-manager-olive: #636b2f;
    --login-manager-light: #d4de95;
    --login-manager-dark: #252914;
    --login-manager-panel: #fbfcf3;
    --login-manager-muted: #3f472a;
    background-color: #1a1a1a !important;
    background-image: url('fastener.jpg') !important;
    background-size: cover !important;
    background-position: center !important;
}
.right {
    background: #fff7f5 !important;
    color: var(--supervisor-dark) !important;
}
.form-header h2,
.field label {
    color: var(--supervisor-dark) !important;
}
.form-header p,
.footer-note,
.hint {
    color: var(--supervisor-muted) !important;
}
.field-wrap i.icon,
.eye {
    color: var(--supervisor-dark) !important;
}
.field input {
    background: #fff !important;
    border-color: rgba(56, 0, 10, 0.38) !important;
    color: var(--supervisor-dark) !important;
}
.role-tab.supervisor,
.left.supervisor-bg,
.accent-bar.supervisor,
.login-btn.supervisor,
.register-btn.supervisor {
    background: var(--supervisor-deep) !important;
}
.left.supervisor-bg .bolt::after {
    background: var(--supervisor-deep) !important;
}
.role-tab.supervisor.active {
    background: #fff7f5 !important;
    color: var(--supervisor-deep) !important;
}
.field input.supervisor-focus:focus,
.field input.sup:focus {
    border-color: var(--supervisor-red) !important;
}
.login-btn.supervisor:hover,
.register-btn.supervisor:hover {
    background: var(--supervisor-dark) !important;
    box-shadow: 0 6px 20px rgba(56, 0, 10, 0.42) !important;
}
.role-tab.manager {
    background: rgba(212, 222, 149, 0.18) !important;
    color: rgba(255, 255, 255, 0.78) !important;
}
.left.manager-bg,
.accent-bar.manager,
.role-tab.manager.active,
.login-btn.manager,
.register-btn.manager {
    background: var(--login-manager-olive) !important;
}
.left.manager-bg {
    background: linear-gradient(160deg, var(--login-manager-dark) 0%, var(--login-manager-olive) 100%) !important;
}
.left.manager-bg .bolt::after {
    background: var(--login-manager-olive) !important;
}
.role-tab.manager.active,
.login-btn.manager,
.register-btn.manager {
    color: #fff !important;
}
body.manager-mode .right {
    background: var(--login-manager-panel) !important;
    color: var(--login-manager-dark) !important;
}
body.manager-mode .form-header h2,
body.manager-mode .field label,
body.manager-mode .field-wrap i.icon,
body.manager-mode .eye {
    color: var(--login-manager-dark) !important;
}
body.manager-mode .form-header p,
body.manager-mode .footer-note {
    color: var(--login-manager-muted) !important;
}
body.manager-mode .field input.manager-focus:focus,
.field input.mgr:focus {
    border-color: var(--login-manager-olive) !important;
}
/* Polished supervisor login mode */
body.login-page,
body:has(.role-tabs) {
    --supervisor-deep: #6b001a;
    --supervisor-red: #9f1239;
    --supervisor-dark: #38000a;
    --supervisor-muted: #7a3747;
    --supervisor-panel: #fff8f6;
    --supervisor-field: #fffdfc;
    --supervisor-border: rgba(56, 0, 10, 0.22);
}
body.supervisor-mode .page {
    border: 1px solid rgba(255, 255, 255, 0.18);
    box-shadow: 0 28px 70px rgba(31, 0, 8, 0.52);
}
body.supervisor-mode .left.supervisor-bg {
    background: linear-gradient(160deg, #38000a 0%, #6b001a 58%, #9f1239 100%) !important;
}
body.supervisor-mode .right {
    background: linear-gradient(180deg, #fffdfc 0%, var(--supervisor-panel) 100%) !important;
    padding: 3.1rem 2.75rem;
}
body.supervisor-mode .accent-bar.supervisor {
    width: 48px;
    height: 4px;
    background: linear-gradient(90deg, var(--supervisor-deep), var(--supervisor-red)) !important;
}
body.supervisor-mode .form-header h2 {
    font-size: 25px;
    font-weight: 800;
    color: var(--supervisor-dark) !important;
}
body.supervisor-mode .form-header p,
body.supervisor-mode .footer-note {
    color: var(--supervisor-muted) !important;
}
body.supervisor-mode .field input {
    height: 46px;
    background: var(--supervisor-field) !important;
    border: 1.5px solid var(--supervisor-border) !important;
    box-shadow: 0 6px 16px rgba(56, 0, 10, 0.05);
}
body.supervisor-mode .field input:focus {
    border-color: var(--supervisor-red) !important;
    box-shadow: 0 0 0 4px rgba(159, 18, 57, 0.12);
}
body.supervisor-mode .login-btn.supervisor {
    height: 46px;
    background: linear-gradient(135deg, var(--supervisor-deep), var(--supervisor-red)) !important;
    box-shadow: 0 10px 24px rgba(107, 0, 26, 0.28) !important;
}
body.supervisor-mode .login-btn.supervisor:hover {
    background: linear-gradient(135deg, #4e0012, var(--supervisor-deep)) !important;
}
body.manager-mode .page {
    border: 1px solid rgba(255, 255, 255, 0.18);
    box-shadow: 0 28px 70px rgba(31, 0, 8, 0.52);
}
body.manager-mode .right {
    background: linear-gradient(180deg, #fffef8 0%, var(--login-manager-panel) 100%) !important;
    padding: 3.1rem 2.75rem;
}
body.manager-mode .accent-bar.manager {
    width: 48px;
    height: 4px;
    background: linear-gradient(90deg, var(--login-manager-dark), var(--login-manager-olive)) !important;
}
body.manager-mode .form-header h2 {
    font-size: 25px;
    font-weight: 800;
    color: var(--login-manager-dark) !important;
}
body.manager-mode .form-header p,
body.manager-mode .footer-note {
    color: var(--login-manager-muted) !important;
}
body.manager-mode .field input {
    height: 46px;
    background: #fffef8 !important;
    border: 1.5px solid rgba(37, 41, 20, 0.22) !important;
    box-shadow: 0 6px 16px rgba(37, 41, 20, 0.05);
}
body.manager-mode .field input:focus {
    border-color: var(--login-manager-olive) !important;
    box-shadow: 0 0 0 4px rgba(99, 107, 47, 0.14);
}
body.manager-mode .login-btn.manager {
    height: 46px;
    background: linear-gradient(135deg, var(--login-manager-dark), var(--login-manager-olive)) !important;
    box-shadow: 0 10px 24px rgba(99, 107, 47, 0.28) !important;
}
body.manager-mode .login-btn.manager:hover {
    background: linear-gradient(135deg, #171a0d, var(--login-manager-muted)) !important;
}
@media (max-width: 760px) {
    body {
        padding: 18px 12px;
    }
    .role-tabs {
        width: min(100%, 420px);
        gap: 6px;
    }
    .role-tab {
        flex: 1;
        padding: 10px 8px;
        font-size: 12px;
    }
    .page {
        width: min(100%, 420px);
        min-height: 0;
        flex-direction: column;
        border-radius: 0 0 16px 16px !important;
    }
    .left {
        width: 100%;
        padding: 1.4rem 1.2rem;
        gap: 10px;
    }
    .left .bolt-grid:last-child,
    .divider,
    .tagline {
        display: none;
    }
    body.supervisor-mode .right,
    .right {
        padding: 2rem 1.35rem;
    }
}
</style>
</head>
<body class="login-page <?= $selectedRole === 'manager' ? 'manager-mode' : 'supervisor-mode' ?>">
<a href="index.php" class="back-home"><i class="fa fa-arrow-left"></i> Back to Home</a>
<div class="role-tabs">
    <button class="role-tab supervisor <?= $selectedRole === 'supervisor' ? 'active' : '' ?>" id="tab-supervisor" onclick="switchRole('supervisor')">
        <i class="fa fa-shield-halved"></i><b> Supervisor Login</b>
    </button>
    <button class="role-tab manager <?= $selectedRole === 'manager' ? 'active' : '' ?>" id="tab-manager" onclick="switchRole('manager')">
        <i class="fa fa-user-tie"></i><b> Manager Login</b>
    </button>
</div>
<div class="page" id="loginCard">
  <div class="left <?= $selectedRole === 'manager' ? 'manager-bg' : 'supervisor-bg' ?>" id="leftPanel">
    <div class="bolt-grid">
      <?php for($i=0;$i<9;$i++) echo '<div class="bolt"></div>'; ?>
    </div>
    <div class="brand">
      <div class="brand-title">BOLT BASE</div>
      <div class="brand-sub">Fastener Inventory</div>
      <div class="role-badge" id="roleBadge"><?= $selectedRole === 'manager' ? 'Manager' : 'Supervisor' ?></div>
    </div>
    <div class="divider"></div>
    <div class="tagline" id="tagline"><?= $selectedRole === 'manager' ? 'Full visibility.<br>Smart reports.<br>Data-driven decisions.' : 'Precision parts.<br>Reliable tracking.<br>Zero loose ends.' ?></div>
    <div class="bolt-grid" style="opacity:0.1;">
      <?php for($i=0;$i<9;$i++) echo '<div class="bolt"></div>'; ?>
    </div>
  </div>
  <div class="right">
    <div class="accent-bar <?= $selectedRole ?>" id="accentBar"></div>
    <div class="form-header">
      <h2 id="formTitle"><?= $selectedRole === 'manager' ? 'Manager Login' : 'Supervisor Login' ?></h2>
      <p id="formSub"><?= $selectedRole === 'manager' ? 'Sign in to view reports and analytics' : 'Sign in to manage inventory and operations' ?></p>
    </div>
    <?php if($error != ""): ?>
    <div class="error" id="loginWarning"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form method="POST" autocomplete="off">
      <input type="hidden" name="role" id="roleInput" value="<?= $selectedRole ?>">
      <div class="field">
        <label>Username</label>
        <div class="field-wrap">
          <i class="fa-solid fa-user icon"></i>
          <input type="text" name="username" placeholder="Enter username or email" id="usernameInput"
                 class="<?= $selectedRole ?>-focus" autocomplete="off" required>
        </div>
      </div>
      <div class="field">
        <label>Password</label>
        <div class="field-wrap">
          <i class="fa-solid fa-lock icon"></i>
          <input type="password" id="password" name="password" placeholder="Enter password"
                 class="<?= $selectedRole ?>-focus" autocomplete="new-password" required>
          <span class="eye" onclick="togglePassword()">
            <i id="eyeIcon" class="fa-solid fa-eye-slash"></i>
          </span>
        </div>
      </div>
      <div class="field">
        <label>Captcha: <?= htmlspecialchars($captchaQuestion) ?> = ?</label>
        <div class="field-wrap">
          <i class="fa-solid fa-shield-halved icon"></i>
          <input type="text" name="captcha" placeholder="Answer" class="<?= $selectedRole ?>-focus" autocomplete="off" required>
        </div>
      </div>
      <button type="submit" name="login" class="login-btn <?= $selectedRole ?>" id="loginBtn">Sign in</button>
    </form>
    <div class="footer-note" id="footerNote">
      <a href="forgot_password.php" style="color:inherit;font-weight:800;">Forgot password?</a> &nbsp; <?= $selectedRole === 'manager' ? 'Read-only access with full reporting' : 'Authorized personnel only' ?>
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
const selectedRole = <?= json_encode($selectedRole) ?>;
if(selectedRole === 'manager') {
    switchRole('manager');
}
const loginWarning = document.getElementById('loginWarning');
if(loginWarning) {
    setTimeout(() => {
        loginWarning.classList.add('hide-warning');
    }, 4000);
}
</script>
</body>
</html>
