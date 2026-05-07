<?php
session_start();
include "db.php";
ensure_user_security_schema();

$error   = "";
$success = "";
$captchaQuestion = $_SERVER['REQUEST_METHOD'] === 'POST' ? '' : generate_captcha();

if (isset($_POST['register'])) {
    $username = trim(mysqli_real_escape_string($conn, $_POST['username']));
    $email    = trim($_POST['email'] ?? '');
    $phone    = trim($_POST['phone'] ?? '');
    $password = $_POST['password'];
    $confirm  = $_POST['confirm_password'];
    $role     = $_POST['role'] ?? 'supervisor';
    $captcha  = trim($_POST['captcha'] ?? '');

    if (empty($username) || empty($email) || empty($phone) || empty($password) || empty($confirm)) {
        $error = "All fields are required.";
    } elseif (!preg_match('/^[a-zA-Z0-9_]{3,50}$/', $username)) {
        $error = "Username must be 3–50 characters (letters, numbers, underscores only).";
    } elseif (!valid_email_addr($email)) {
        $error = "Enter a valid email address.";
    } elseif (!valid_phone($phone)) {
        $error = "Phone number must be 10 digits and start with 6-9.";
    } elseif (strlen($password) < 8) {
        $error = "Password must be at least 8 characters.";
    } elseif ($password !== $confirm) {
        $error = "Passwords do not match.";
    } elseif (!in_array($role, ['supervisor', 'manager'])) {
        $error = "Invalid role selected.";
    } elseif ($captcha === '' || !isset($_SESSION['captcha_answer']) || $captcha !== $_SESSION['captcha_answer']) {
        $error = "Captcha answer is incorrect.";
    } else {
        try {
            $check = mysqli_prepare($conn, "SELECT id FROM users WHERE username=? OR email=? OR phone=?");
            mysqli_stmt_bind_param($check, "sss", $username, $email, $phone);
            mysqli_stmt_execute($check);
            $result = mysqli_stmt_get_result($check);
            if ($result && mysqli_num_rows($result) > 0) {
                $error = "Username, email, or phone already exists. Please use unique details.";
            } else {
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $sql = mysqli_prepare($conn, "INSERT INTO users (username, email, phone, password, role) VALUES (?, ?, ?, ?, ?)");
                mysqli_stmt_bind_param($sql, "sssss", $username, $email, $phone, $hashed, $role);
                if (mysqli_stmt_execute($sql)) {
                    $success = "Account created successfully! <a href='login.php'>Sign in now &rarr;</a>";
                } else {
                    $error = "Registration failed. Please try again.";
                }
            }
        } catch (Throwable $e) {
            error_log($e->getMessage());
            $error = str_contains($e->getMessage(), 'Duplicate')
                ? "Username, email, or phone already exists."
                : "Registration failed. Please try again.";
        }
    }
}
$captchaQuestion = generate_captcha();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Bolt Base — Register</title>
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
    flex-direction: column;
    gap: 18px;
    padding: 2rem 1rem;
}
body::before {
    content: '';
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.62);
}
.role-tabs { position: relative; display: flex; gap: 10px; z-index: 1; }
.role-tab {
    padding: 10px 32px;
    border-radius: 10px 10px 0 0;
    font-size: 14px; font-weight: 700;
    cursor: pointer; border: none; letter-spacing: 0.5px; transition: 0.2s;
}
.role-tab.supervisor { background: #6B001A; color: #fff; }
.role-tab.manager    { background: rgba(255,255,255,0.15); color: rgba(255,255,255,0.7); }
.role-tab.supervisor.active { background: white; color: #6B001A; }
.role-tab.manager.active    { background: #1a3a6b; color: white; }
.role-tab:not(.active):hover { opacity: 0.85; }
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
    position: relative; display: flex;
    width: 680px; min-height: 460px;
    border-radius: 0 16px 16px 16px;
    overflow: hidden;
    box-shadow: 0 24px 60px rgba(0,0,0,0.55);
    z-index: 1;
}
.left {
    width: 220px; flex-shrink: 0;
    display: flex; flex-direction: column;
    align-items: center; justify-content: center;
    padding: 2.5rem 1.5rem; gap: 18px; transition: background 0.3s;
}
.left.supervisor-bg { background: #6B001A; }
.left.manager-bg    { background: #1a3a6b; }
.bolt-grid { display: grid; grid-template-columns: repeat(3,1fr); gap: 10px; opacity: 0.18; }
.bolt { width: 18px; height: 18px; background: white; border-radius: 50%; position: relative; }
.bolt::after {
    content: ''; position: absolute;
    top: 50%; left: 50%; transform: translate(-50%,-50%);
    width: 7px; height: 7px; border-radius: 50%;
}
.left.supervisor-bg .bolt::after { background: #6B001A; }
.left.manager-bg    .bolt::after { background: #1a3a6b; }
.brand { color: white; text-align: center; }
.brand-title { font-size: 22px; font-weight: 600; letter-spacing: 1.5px; }
.brand-sub   { font-size: 11px; opacity: 0.55; letter-spacing: 2.5px; text-transform: uppercase; margin-top: 5px; }
.role-badge  {
    font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 2px;
    padding: 5px 14px; border-radius: 20px; border: 1px solid rgba(255,255,255,0.30);
    color: rgba(255,255,255,0.80); margin-top: 2px;
}
.divider { width: 40px; height: 1px; background: rgba(255,255,255,0.2); }
.step-list { list-style: none; display: flex; flex-direction: column; gap: 11px; width: 100%; }
.step-list li {
    display: flex; align-items: center; gap: 10px;
    font-size: 11px; color: rgba(255,255,255,0.55); font-weight: 500; letter-spacing: 0.3px;
}
.step-list li i { font-size: 12px; opacity: 0.75; }
.right {
    flex: 1; background: white;
    display: flex; flex-direction: column; justify-content: center;
    padding: 3rem 2.5rem; color: #000;
}
.accent-bar { width: 36px; height: 3px; border-radius: 2px; margin-bottom: 14px; transition: background 0.3s; }
.accent-bar.supervisor { background: #6B001A; }
.accent-bar.manager    { background: #1a3a6b; }
.form-header { margin-bottom: 1.75rem; }
.form-header h2 { font-size: 22px; font-weight: 600; color: #000; }
.form-header p  { font-size: 13px; color: #444; margin-top: 4px; font-weight: 500; }
.alert {
    font-size: 13px; font-weight: 600;
    padding: 9px 14px; border-radius: 8px; margin-bottom: 1.25rem;
    display: flex; align-items: center; gap: 8px;
}
.alert.error   { background: #fff0f2; border: 1px solid #f5c0cb; color: #7a0000; }
.alert.success { background: #f0fff4; border: 1px solid #b2dfdb; color: #005a34; }
.alert.success a { color: #005a34; font-weight: 700; text-decoration: none; }
.alert.success a:hover { text-decoration: underline; }
.field { margin-bottom: 1.2rem; }
.field label {
    display: block; font-size: 11px; font-weight: 700; color: #111;
    letter-spacing: 0.8px; text-transform: uppercase; margin-bottom: 7px;
}
.field-wrap { position: relative; }
.field-wrap i.icon {
    position: absolute; left: 13px; top: 50%;
    transform: translateY(-50%); color: #444; font-size: 14px;
}
.field input {
    width: 100%; height: 42px; padding: 0 38px;
    border: 1.5px solid #ccc; border-radius: 8px;
    font-size: 14px; color: #000; font-weight: 500;
    background: #f5f5f5; outline: none;
    transition: border-color 0.2s, background 0.2s;
}
.field input::placeholder { color: #777; font-weight: 400; }
.field input:focus { background: white; }
.field input.sup:focus { border-color: #6B001A; }
.field input.mgr:focus { border-color: #1a3a6b; }
.field input.valid   { border-color: #2e7d52; background: #f6fff9; }
.field input.invalid { border-color: #c0392b; background: #fff6f6; }
.vicon { position: absolute; right: 36px; top: 50%; transform: translateY(-50%); font-size: 13px; display: none; }
.vicon.show { display: block; }
.vicon.ok  { color: #2e7d52; }
.vicon.err { color: #c0392b; }
.eye {
    position: absolute; right: 12px; top: 50%;
    transform: translateY(-50%); cursor: pointer; color: #444; font-size: 14px;
}
.strength-wrap { margin-top: 6px; }
.strength-bar-bg { height: 4px; background: #e0e0e0; border-radius: 4px; overflow: hidden; }
.strength-bar { height: 100%; border-radius: 4px; width: 0%; transition: width 0.3s, background 0.3s; }
.strength-label { font-size: 10px; color: #888; margin-top: 3px; font-weight: 600; letter-spacing: 0.5px; }
.hint { font-size: 11px; color: #888; margin-top: 4px; font-weight: 500; }
.register-btn {
    width: 100%; height: 44px; border: none; color: white;
    font-size: 14px; font-weight: 600; letter-spacing: 0.5px;
    border-radius: 8px; cursor: pointer;
    transition: background 0.2s, transform 0.1s, box-shadow 0.2s;
    margin-top: 0.4rem;
}
.register-btn.supervisor { background: #6B001A; box-shadow: 0 4px 15px rgba(107,0,26,0.4); }
.register-btn.supervisor:hover { background: #8a0022; box-shadow: 0 6px 20px rgba(107,0,26,0.55); }
.register-btn.manager    { background: #1a3a6b; box-shadow: 0 4px 15px rgba(26,58,107,0.4); }
.register-btn.manager:hover    { background: #244d8f; box-shadow: 0 6px 20px rgba(26,58,107,0.55); }
.register-btn:active { transform: scale(0.98); }
.footer-note { font-size: 12px; color: #555; text-align: center; margin-top: 1.4rem; font-weight: 500; }
.footer-note a         { color: #6B001A; font-weight: 700; text-decoration: none; }
.footer-note a.mgr-lnk { color: #1a3a6b; }
.footer-note a:hover   { text-decoration: underline; }


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
</style>
</head>
<body>

<a href="index.php" class="back-home"><i class="fa fa-arrow-left"></i> Back to Home</a>

<div class="role-tabs">
    <button class="role-tab supervisor active" id="tab-supervisor" onclick="switchRole('supervisor')">
        <i class="fa fa-shield-halved"></i> Supervisor Register
    </button>
    <button class="role-tab manager" id="tab-manager" onclick="switchRole('manager')">
        <i class="fa fa-user-tie"></i> Manager Register
    </button>
</div>

<div class="page" id="regCard">

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
    <ul class="step-list">
      <li><i class="fa-solid fa-circle-check"></i> Select your role above</li>
      <li><i class="fa-solid fa-circle-check"></i> Pick a unique username</li>
      <li><i class="fa-solid fa-circle-check"></i> Set a strong password</li>
      <li><i class="fa-solid fa-circle-check"></i> Sign in immediately</li>
    </ul>
    <div class="bolt-grid" style="opacity:0.1;">
      <?php for($i=0;$i<9;$i++) echo '<div class="bolt"></div>'; ?>
    </div>
  </div>

  <div class="right">
    <div class="accent-bar supervisor" id="accentBar"></div>
    <div class="form-header">
      <h2 id="formTitle">Create Supervisor Account</h2>
      <p id="formSub">Choose a username and password to get started</p>
    </div>

    <?php if ($error !== ''): ?>
      <div class="alert error">
        <i class="fa-solid fa-circle-exclamation"></i>
        <span><?= htmlspecialchars($error) ?></span>
      </div>
    <?php endif; ?>
    <?php if ($success !== ''): ?>
      <div class="alert success">
        <i class="fa-solid fa-circle-check"></i>
        <span><?= $success ?></span>
      </div>
    <?php endif; ?>

    <form method="POST" novalidate>
      <input type="hidden" name="role" id="roleInput" value="supervisor">

      <!-- Username -->
      <div class="field">
        <label>Username</label>
        <div class="field-wrap">
          <i class="fa-solid fa-user icon"></i>
          <input type="text" name="username" id="username"
                 placeholder="Letters, numbers, underscores" class="sup"
                 value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required>
          <span class="vicon" id="vnUser"></span>
        </div>
        <div class="hint">3–50 characters &nbsp;&middot;&nbsp; a–z, 0–9, underscore only</div>
      </div>

      <div class="field">
        <label>Email</label>
        <div class="field-wrap">
          <i class="fa-solid fa-envelope icon"></i>
          <input type="email" name="email" id="email" placeholder="you@example.com" class="sup"
                 value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
          <span class="vicon" id="vnEmail"></span>
        </div>
        <div class="hint" id="emailHint">Used for forgot-password OTP</div>
      </div>

      <div class="field">
        <label>Phone Number</label>
        <div class="field-wrap">
          <i class="fa-solid fa-phone icon"></i>
          <input type="tel" name="phone" id="phone" placeholder="9876543210" class="sup"
                 maxlength="10" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>" required>
          <span class="vicon" id="vnPhone"></span>
        </div>
        <div class="hint" id="phoneHint">10 digits, starting with 6-9</div>
      </div>

      <!-- Password -->
      <div class="field">
        <label>Password</label>
        <div class="field-wrap">
          <i class="fa-solid fa-lock icon"></i>
          <input type="password" name="password" id="password"
                 placeholder="Minimum 8 characters" class="sup"
                 required oninput="checkStrength(this.value)">
          <span class="eye" onclick="togglePw('password','eye1')">
            <i id="eye1" class="fa-solid fa-eye-slash"></i>
          </span>
        </div>
        <div class="strength-wrap">
          <div class="strength-bar-bg"><div class="strength-bar" id="sBar"></div></div>
          <div class="strength-label" id="sLabel">Enter a password</div>
        </div>
      </div>

      <!-- Confirm password -->
      <div class="field">
        <label>Confirm Password</label>
        <div class="field-wrap">
          <i class="fa-solid fa-lock icon"></i>
          <input type="password" name="confirm_password" id="confirmPw"
                 placeholder="Re-enter your password" class="sup" required>
          <span class="vicon" id="vnConfirm"></span>
          <span class="eye" onclick="togglePw('confirmPw','eye2')">
            <i id="eye2" class="fa-solid fa-eye-slash"></i>
          </span>
        </div>
      </div>

      <div class="field">
        <label>Captcha: <?= htmlspecialchars($captchaQuestion) ?> = ?</label>
        <div class="field-wrap">
          <i class="fa-solid fa-shield-halved icon"></i>
          <input type="text" name="captcha" id="captcha" placeholder="Answer" class="sup" required>
        </div>
      </div>

      <button type="submit" name="register" class="register-btn supervisor" id="regBtn">
        <i class="fa-solid fa-user-plus"></i>&nbsp; Create Account
      </button>
    </form>

    <div class="footer-note">
      Already have an account? <a href="login.php" id="loginLink">Sign in here</a>
    </div>
  </div>
</div>

<script>
function switchRole(role) {
    const isSup = role === 'supervisor';
    document.getElementById('roleInput').value = role;
    document.getElementById('tab-supervisor').classList.toggle('active', isSup);
    document.getElementById('tab-manager').classList.toggle('active', !isSup);
    document.getElementById('leftPanel').className = 'left ' + (isSup ? 'supervisor-bg' : 'manager-bg');
    document.getElementById('roleBadge').textContent = isSup ? 'Supervisor' : 'Manager';
    document.getElementById('accentBar').className   = 'accent-bar ' + role;
    document.getElementById('formTitle').textContent = isSup ? 'Create Supervisor Account' : 'Create Manager Account';
    document.getElementById('formSub').textContent   = isSup
        ? 'Choose a username and password to get started'
        : 'Set up your manager credentials below';
    document.getElementById('regBtn').className = 'register-btn ' + role;
    document.getElementById('regCard').style.borderRadius = isSup
        ? '0 16px 16px 16px' : '16px 0 16px 16px';
    document.getElementById('loginLink').className = isSup ? '' : 'mgr-lnk';
    const cls = isSup ? 'sup' : 'mgr';
    document.querySelectorAll('.field input').forEach(el => {
        el.className = el.className.replace(/\bsup\b|\bmgr\b/g, '').trim() + ' ' + cls;
    });
}

function togglePw(fId, iId) {
    const f = document.getElementById(fId);
    const i = document.getElementById(iId);
    const show = f.type === 'password';
    f.type = show ? 'text' : 'password';
    i.className = show ? 'fa-solid fa-eye' : 'fa-solid fa-eye-slash';
}

function checkStrength(val) {
    let score = 0;
    if (val.length >= 8)           score++;
    if (/[A-Z]/.test(val))         score++;
    if (/[0-9]/.test(val))         score++;
    if (/[^A-Za-z0-9]/.test(val))  score++;
    const lvls = [
        { w:'0%',   bg:'#e0e0e0', t:'Enter a password' },
        { w:'25%',  bg:'#e53935', t:'Weak'             },
        { w:'50%',  bg:'#f57c00', t:'Fair'             },
        { w:'75%',  bg:'#fbc02d', t:'Good'             },
        { w:'100%', bg:'#388e3c', t:'Strong \u2713'    },
    ];
    const l = val.length === 0 ? lvls[0] : (lvls[score] || lvls[1]);
    const bar = document.getElementById('sBar');
    bar.style.width = l.w; bar.style.background = l.bg;
    document.getElementById('sLabel').textContent = l.t;
}

function markField(el, iconId, ok) {
    el.classList.toggle('valid',   ok);
    el.classList.toggle('invalid', !ok);
    const ic = document.getElementById(iconId);
    if (ic) ic.className = 'vicon show fa-solid ' + (ok ? 'fa-circle-check ok' : 'fa-circle-xmark err');
}

document.getElementById('username').addEventListener('blur', function() {
    const ok = /^[a-zA-Z0-9_]{3,50}$/.test(this.value.trim());
    markField(this, 'vnUser', ok);
    if (ok) checkExisting('username', this.value.trim(), this, 'vnUser');
});
document.getElementById('email').addEventListener('blur', function() {
    const ok = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(this.value.trim());
    markField(this, 'vnEmail', ok);
    if (ok) checkExisting('email', this.value.trim(), this, 'vnEmail', 'emailHint');
});
document.getElementById('phone').addEventListener('input', function() {
    this.value = this.value.replace(/\D/g, '').slice(0, 10);
});
document.getElementById('phone').addEventListener('blur', function() {
    const ok = /^[6-9][0-9]{9}$/.test(this.value.trim());
    markField(this, 'vnPhone', ok);
    if (ok) checkExisting('phone', this.value.trim(), this, 'vnPhone', 'phoneHint');
});
document.getElementById('confirmPw').addEventListener('input', function() {
    if (this.value.length > 0)
        markField(this, 'vnConfirm', this.value === document.getElementById('password').value);
});

function checkExisting(field, value, el, iconId, hintId) {
    fetch('ajax_check_user.php?field=' + encodeURIComponent(field) + '&value=' + encodeURIComponent(value))
        .then(r => r.json())
        .then(data => {
            markField(el, iconId, !data.exists);
            if (hintId && data.message) document.getElementById(hintId).textContent = data.message;
        })
        .catch(() => {
            if (hintId) document.getElementById(hintId).textContent = 'Could not check right now.';
        });
}
</script>
</body>
</html>
