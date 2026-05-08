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

        // SMTP SETTINGS
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;

        // YOUR GMAIL
        $mail->Username   = 'kkempshivannavar@gmail.com';

        // GMAIL APP PASSWORD (WITHOUT SPACES)
        $mail->Password   = 'xvxfoicpykomxxja';

        $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // FIX SSL ERROR
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer'       => false,
                'verify_peer_name'  => false,
                'allow_self_signed' => true
            )
        );

        // DEBUGGING
        // REMOVE AFTER TESTING
        $mail->SMTPDebug = 0;

        // SENDER
        $mail->setFrom('kkempshivannavar@gmail.com', 'Bolt Base');

        // RECEIVER
        $mail->addAddress($email);

        // EMAIL CONTENT
        $mail->isHTML(true);

        $mail->Subject = 'Bolt Base - Password Reset OTP';

        $mail->Body = "
        <div style='font-family:Segoe UI,sans-serif;
                    max-width:420px;
                    margin:auto;
                    padding:32px;
                    border:1px solid #f0dada;
                    border-radius:12px;
                    background:#fff7f5;'>

            <h2 style='color:#9b1313;margin:0 0 8px;'>
                Bolt Base
            </h2>

            <p style='color:#6f2220;
                      font-size:14px;
                      margin:0 0 24px;'>
                Password Reset Request
            </p>

            <p style='font-size:14px;
                      color:#333;
                      margin:0 0 12px;'>
                Use the OTP below to reset your password.
                It expires in <strong>10 minutes</strong>.
            </p>

            <div style='font-size:36px;
                        font-weight:800;
                        letter-spacing:10px;
                        color:#38000a;
                        background:#fde8e8;
                        padding:16px;
                        border-radius:8px;
                        text-align:center;
                        margin:0 0 20px;'>

                $otp

            </div>

            <p style='font-size:12px;color:#999;'>
                If you did not request a password reset,
                you can safely ignore this email.
            </p>

        </div>";

        $mail->AltBody = "Your Bolt Base password reset OTP is: $otp";

        // SEND MAIL
        $mail->send();

        return true;

    } catch (\Exception $e) {

        // SHOW REAL ERROR
        die("Mailer Error: " . $mail->ErrorInfo);

    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    try {

        // STEP 1 : SEND OTP
        if ($step === 'request') {

            $email   = trim($_POST['email'] ?? '');
            $captcha = trim($_POST['captcha'] ?? '');

            // EMAIL VALIDATION
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

                $error = 'Enter a valid email address.';

            }
            // CAPTCHA CHECK
            elseif (
                $captcha === '' ||
                !isset($_SESSION['captcha_answer']) ||
                $captcha !== $_SESSION['captcha_answer']
            ) {

                $error = 'Captcha answer is incorrect.';

            }
            else {

                // CHECK EMAIL EXISTS
                $stmt = mysqli_prepare(
                    $conn,
                    "SELECT id,email FROM users WHERE email=?"
                );

                mysqli_stmt_bind_param($stmt, "s", $email);

                mysqli_stmt_execute($stmt);

                $res  = mysqli_stmt_get_result($stmt);

                $user = mysqli_fetch_assoc($res);

                if (!$user) {

                    $error = 'No account found with that email.';

                } else {

                    // GENERATE OTP
                    $otp = random_int(100000, 999999);

                    // HASH OTP
                    $hash = password_hash($otp, PASSWORD_DEFAULT);

                    // EXPIRY
                    $expires = date(
                        'Y-m-d H:i:s',
                        time() + 600
                    );

                    // SAVE OTP
                    $upd = mysqli_prepare(
                        $conn,
                        "UPDATE users
                         SET reset_otp=?,
                             reset_otp_expires=?
                         WHERE id=?"
                    );

                    mysqli_stmt_bind_param(
                        $upd,
                        "ssi",
                        $hash,
                        $expires,
                        $user['id']
                    );

                    mysqli_stmt_execute($upd);

                    // SESSION
                    $_SESSION['reset_email'] = $email;

                    // SEND EMAIL
                    $sent = send_reset_otp($email, $otp);

                    if ($sent) {

                        $success =
                            'OTP sent successfully to your email.';

                        $step = 'reset';

                    } else {

                        $error = 'Failed to send OTP.';

                    }
                }
            }
        }

        // STEP 2 : RESET PASSWORD
        elseif ($step === 'reset') {

            $email    = $_SESSION['reset_email'] ?? '';
            $otp      = trim($_POST['otp'] ?? '');
            $password = $_POST['password'] ?? '';
            $confirm  = $_POST['confirm_password'] ?? '';

            if ($otp === '') {

                $error = 'Enter OTP.';

            }
            elseif (strlen($password) < 8) {

                $error =
                    'Password must be at least 8 characters.';

            }
            elseif ($password !== $confirm) {

                $error = 'Passwords do not match.';

            }
            else {

                // GET USER
                $stmt = mysqli_prepare(
                    $conn,
                    "SELECT id,
                            reset_otp,
                            reset_otp_expires
                     FROM users
                     WHERE email=?"
                );

                mysqli_stmt_bind_param(
                    $stmt,
                    "s",
                    $email
                );

                mysqli_stmt_execute($stmt);

                $res  = mysqli_stmt_get_result($stmt);

                $user = mysqli_fetch_assoc($res);

                // VERIFY OTP
                if (
                    !$user ||
                    !$user['reset_otp'] ||
                    strtotime($user['reset_otp_expires']) < time() ||
                    !password_verify($otp, $user['reset_otp'])
                ) {

                    $error = 'Invalid or expired OTP.';
                    $step = 'reset';

                } else {

                    // HASH PASSWORD
                    $newHash = password_hash(
                        $password,
                        PASSWORD_DEFAULT
                    );

                    // UPDATE PASSWORD
                    $upd = mysqli_prepare(
                        $conn,
                        "UPDATE users
                         SET password=?,
                             reset_otp=NULL,
                             reset_otp_expires=NULL
                         WHERE id=?"
                    );

                    mysqli_stmt_bind_param(
                        $upd,
                        "si",
                        $newHash,
                        $user['id']
                    );

                    mysqli_stmt_execute($upd);

                    unset($_SESSION['reset_email']);

                    $success =
                        "Password updated successfully.";

                    $step = 'done';
                }
            }
        }

    } catch (Throwable $e) {

        die($e->getMessage());

    }

    $captchaQuestion = generate_captcha();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">
<meta name="viewport"
      content="width=device-width, initial-scale=1.0">

<title>Bolt Base - Forgot Password</title>

<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link rel="stylesheet" href="css/user.css">

</head>

<body class="page-forgot_password">

<div class="card">

<h1>Forgot Password</h1>

<p>
Use your registered email to receive an OTP
and create a new password.
</p>

<?php if($error): ?>
<div class="alert error">
<?= htmlspecialchars($error) ?>
</div>
<?php endif; ?>

<?php if($success): ?>
<div class="alert success">
<?= $success ?>
</div>
<?php endif; ?>

<?php if($step !== 'done'): ?>

<form method="POST">

<input type="hidden"
       name="step"
       value="<?= htmlspecialchars($step) ?>">

<?php if($step === 'request'): ?>

<div class="field">
<label>Email</label>

<div class="wrap">
<i class="fa fa-envelope icon"></i>

<input type="email"
       name="email"
       required
       placeholder="you@example.com">
</div>
</div>

<div class="field">
<label>
Captcha:
<?= htmlspecialchars($captchaQuestion) ?> = ?
</label>

<div class="wrap">
<i class="fa fa-shield-halved icon"></i>

<input type="text"
       name="captcha"
       required
       placeholder="Answer">
</div>
</div>

<button class="btn" type="submit">
Send OTP
</button>

<?php else: ?>

<div class="field">
<label>OTP</label>

<div class="wrap">
<i class="fa fa-key icon"></i>

<input type="text"
       name="otp"
       maxlength="6"
       required
       placeholder="6 digit OTP">
</div>
</div>

<div class="field">
<label>New Password</label>

<div class="wrap">
<i class="fa fa-lock icon"></i>

<input type="password"
       id="password"
       name="password"
       required>

<span class="eye"
      onclick="togglePw('password','eye1')">

<i id="eye1"
   class="fa fa-eye-slash"></i>

</span>
</div>
</div>

<div class="field">
<label>Retype Password</label>

<div class="wrap">
<i class="fa fa-lock icon"></i>

<input type="password"
       id="confirm_password"
       name="confirm_password"
       required>

<span class="eye"
      onclick="togglePw('confirm_password','eye2')">

<i id="eye2"
   class="fa fa-eye-slash"></i>

</span>
</div>
</div>

<button class="btn" type="submit">
Reset Password
</button>

<?php endif; ?>

</form>

<?php endif; ?>

</div>

<script>

function togglePw(fId,iId)
{
    const f=document.getElementById(fId);
    const i=document.getElementById(iId);

    const show=f.type==='password';

    f.type=show?'text':'password';

    i.className=show
        ?'fa fa-eye'
        :'fa fa-eye-slash';
}

</script>

</body>
</html>