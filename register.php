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
<link rel="stylesheet" href="css/user.css">
</head>
<body class="page-register">

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
