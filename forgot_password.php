<?php
session_start();
include "db.php";
ensure_user_security_schema();

$step = $_POST['step'] ?? 'request';
$error = '';
$success = '';
$captchaQuestion = $_SERVER['REQUEST_METHOD'] === 'POST' ? '' : generate_captcha();

function send_reset_otp($email, $otp)
{
    require_once 'PHPMailer/src/Exception.php';
    require_once 'PHPMailer/src/PHPMailer.php';
    require_once 'PHPMailer/src/SMTP.php';

    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'kkempshivannavar@gmail.com';   // ← Replace with your Gmail
        $mail->Password   = 'xvxf oicp ykom xxja';   // ← Your App Password (regenerate a new one!)
        $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom('yourgmail@gmail.com', 'Bolt Base');
        $mail->addAddress($email);
        $mail->isHTML(true);
        $mail->Subject = 'Bolt Base - Password Reset OTP';
        $mail->Body    = "
        <div style='font-family:Segoe UI,sans-serif;max-width:420px;margin:auto;padding:32px;border:1px solid #f0dada;border-radius:12px;background:#fff7f5;'>
            <h2 style='color:#9b1313;margin:0 0 8px;'>Bolt Base</h2>
            <p style='color:#6f2220;font-size:14px;margin:0 0 24px;'>Password Reset Request</p>
            <p style='font-size:14px;color:#333;margin:0 0 12px;'>Use the OTP below to reset your password. It expires in <strong>10 minutes</strong>.</p>
            <div style='font-size:36px;font-weight:800;letter-spacing:10px;color:#38000a;background:#fde8e8;padding:16px;border-radius:8px;text-align:center;margin:0 0 20px;'>$otp</div>
            <p style='font-size:12px;color:#999;'>If you did not request a password reset, you can safely ignore this email.</p>
        </div>";
        $mail->AltBody = "Your Bolt Base password reset OTP is: $otp\n\nThis code expires in 10 minutes.";

        $mail->send();
        return true;
    } catch (\Exception $e) {
        error_log('PHPMailer Error: ' . $mail->ErrorInfo);
        return false;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if ($step === 'request') {
            $email = trim($_POST['email'] ?? '');
            $captcha = trim($_POST['captcha'] ?? '');

            if (!valid_email_addr($email)) {
                $error = 'Enter a valid registered email address.';
            } elseif ($captcha === '' || !isset($_SESSION['captcha_answer']) || $captcha !== $_SESSION['captcha_answer']) {
                $error = 'Captcha answer is incorrect.';
            } else {
                $stmt = mysqli_prepare($conn, "SELECT id, email FROM users WHERE email=?");
                mysqli_stmt_bind_param($stmt, "s", $email);
                mysqli_stmt_execute($stmt);
                $res = mysqli_stmt_get_result($stmt);
                $user = $res ? mysqli_fetch_assoc($res) : null;

                if (!$user) {
                    $error = 'No account found with that email.';
                } else {
                    $otp = (string)random_int(100000, 999999);
                    $hash = password_hash($otp, PASSWORD_DEFAULT);
                    $expires = date('Y-m-d H:i:s', time() + 600);
                    $upd = mysqli_prepare($conn, "UPDATE users SET reset_otp=?, reset_otp_expires=? WHERE id=?");
                    mysqli_stmt_bind_param($upd, "ssi", $hash, $expires, $user['id']);
                    mysqli_stmt_execute($upd);
                    $_SESSION['reset_email'] = $email;
                    $sent = send_reset_otp($email, $otp);
                    $success = $sent
                        ? 'OTP sent to your email. Enter it below to set a new password.'
                        : 'Failed to send OTP. Please check your email address or try again.';
                    $step = 'reset';
                }
            }
        } elseif ($step === 'reset') {
            $email = $_SESSION['reset_email'] ?? trim($_POST['email'] ?? '');
            $otp = trim($_POST['otp'] ?? '');
            $password = $_POST['password'] ?? '';
            $confirm = $_POST['confirm_password'] ?? '';

            if (!valid_email_addr($email) || $otp === '') {
                $error = 'Enter the OTP sent to your email.';
            } elseif (strlen($password) < 8) {
                $error = 'Password must be at least 8 characters.';
            } elseif ($password !== $confirm) {
                $error = 'Passwords do not match.';
            } else {
                $stmt = mysqli_prepare($conn, "SELECT id, reset_otp, reset_otp_expires FROM users WHERE email=?");
                mysqli_stmt_bind_param($stmt, "s", $email);
                mysqli_stmt_execute($stmt);
                $res = mysqli_stmt_get_result($stmt);
                $user = $res ? mysqli_fetch_assoc($res) : null;

                if (!$user || !$user['reset_otp'] || strtotime($user['reset_otp_expires']) < time() || !password_verify($otp, $user['reset_otp'])) {
                    $error = 'OTP is invalid or expired.';
                    $step = 'reset';
                } else {
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    $upd = mysqli_prepare($conn, "UPDATE users SET password=?, reset_otp=NULL, reset_otp_expires=NULL WHERE id=?");
                    mysqli_stmt_bind_param($upd, "si", $hash, $user['id']);
                    mysqli_stmt_execute($upd);
                    unset($_SESSION['reset_email']);
                    $success = "Password updated successfully. <a href='login.php'>Sign in now</a>.";
                    $step = 'done';
                }
            }
        }
    } catch (Throwable $e) {
        error_log($e->getMessage());
        $error = 'Something went wrong. Please try again.';
    }
    $captchaQuestion = generate_captcha();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Bolt Base - Forgot Password</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
*{box-sizing:border-box;margin:0;padding:0}body{font-family:'Segoe UI',sans-serif;min-height:100vh;background:url('fastener.jpg') center/cover fixed;display:flex;align-items:center;justify-content:center;padding:24px;color:#38000a}body:before{content:'';position:fixed;inset:0;background:rgba(0,0,0,.62)}.card{position:relative;width:min(440px,100%);background:#fff7f5;border-radius:14px;padding:30px;box-shadow:0 24px 60px rgba(0,0,0,.45)}h1{font-size:24px;margin-bottom:8px}p{font-size:13px;color:#6f2220;margin-bottom:20px}.field{margin-bottom:15px}.field label{display:block;font-size:11px;text-transform:uppercase;font-weight:800;margin-bottom:7px}.wrap{position:relative}.wrap i.icon{position:absolute;left:13px;top:50%;transform:translateY(-50%)}input{width:100%;height:44px;border:1.5px solid #38000a;border-radius:8px;padding:0 40px 0 38px;font-size:14px}.eye{position:absolute;right:13px;top:50%;transform:translateY(-50%);cursor:pointer}.btn{width:100%;height:44px;border:1px solid #38000a;border-radius:8px;background:#9b1313;color:white;font-weight:800;cursor:pointer}.links{display:flex;justify-content:space-between;margin-top:16px;font-size:13px}.links a,.alert a{color:#9b1313;font-weight:800;text-decoration:none}.alert{padding:11px 13px;border-radius:8px;margin-bottom:16px;font-size:13px;font-weight:650}.error{background:#fff0f2;border:1px solid #f5c0cb;color:#7a0000}.success{background:#effaf1;border:1px solid #9ad4a6;color:#075c22}.back-top{position:fixed;right:22px;bottom:22px;z-index:2;color:#fff;text-decoration:none;border:1px solid rgba(255,255,255,.35);border-radius:999px;padding:9px 14px;background:rgba(255,255,255,.12);font-size:12px;font-weight:800}
</style>
</head>
<body>
<a class="back-top" href="index.php"><i class="fa fa-arrow-left"></i> Back Home</a>
<div class="card">
  <h1>Forgot Password</h1>
  <p>Use your registered email to receive an OTP and create a new password.</p>
  <?php if ($error): ?><div class="alert error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
  <?php if ($success): ?><div class="alert success"><?= $success ?></div><?php endif; ?>

  <?php if ($step !== 'done'): ?>
  <form method="POST">
    <input type="hidden" name="step" value="<?= htmlspecialchars($step) ?>">
    <?php if ($step === 'request'): ?>
      <div class="field"><label>Email</label><div class="wrap"><i class="fa fa-envelope icon"></i><input type="email" name="email" required placeholder="you@example.com"></div></div>
      <div class="field"><label>Captcha: <?= htmlspecialchars($captchaQuestion) ?> = ?</label><div class="wrap"><i class="fa fa-shield-halved icon"></i><input type="text" name="captcha" required placeholder="Answer"></div></div>
      <button class="btn" type="submit">Send OTP</button>
    <?php else: ?>
      <div class="field"><label>OTP</label><div class="wrap"><i class="fa fa-key icon"></i><input type="text" name="otp" maxlength="6" required placeholder="6 digit OTP"></div></div>
      <div class="field"><label>New Password</label><div class="wrap"><i class="fa fa-lock icon"></i><input type="password" id="password" name="password" required minlength="8"><span class="eye" onclick="togglePw('password','eye1')"><i id="eye1" class="fa fa-eye-slash"></i></span></div></div>
      <div class="field"><label>Retype Password</label><div class="wrap"><i class="fa fa-lock icon"></i><input type="password" id="confirm_password" name="confirm_password" required minlength="8"><span class="eye" onclick="togglePw('confirm_password','eye2')"><i id="eye2" class="fa fa-eye-slash"></i></span></div></div>
      <button class="btn" type="submit">Reset Password</button>
    <?php endif; ?>
  </form>
  <?php endif; ?>
  <div class="links"><a href="login.php">Back to Login</a><a href="index.php">Home</a></div>
</div>
<script>
function togglePw(fId,iId){const f=document.getElementById(fId),i=document.getElementById(iId),show=f.type==='password';f.type=show?'text':'password';i.className=show?'fa fa-eye':'fa fa-eye-slash'}
</script>
</body>
</html>