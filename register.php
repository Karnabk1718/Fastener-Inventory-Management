<?php
include "db.php";

$msg = "";

if(isset($_POST['register']))
{
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = $_POST['password'];
    $confirm  = $_POST['confirm_password'];

    // ✅ Check if passwords match
    if($password != $confirm)
    {
        $msg = "Passwords do not match!";
    }
    else
    {
        // ✅ Check if username already exists
        $check = mysqli_query($conn, "SELECT * FROM users WHERE username='$username'");
        
        if(mysqli_num_rows($check) > 0)
        {
            $msg = "Username already exists!";
        }
        else
        {
            // ✅ Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // ✅ Insert user
            $sql = "INSERT INTO users (username, password) VALUES ('$username', '$hashed_password')";
            
            if(mysqli_query($conn, $sql))
            {
                $msg = "Registration Successful!";
            }
            else
            {
                $msg = "Error: " . mysqli_error($conn);
            }
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>

<title>Register</title>

<!-- Font Awesome -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

<style>
body{
font-family:Times new roman;
background:linear-gradient(135deg,#800020,#d44);
height:100vh;
display:flex;
justify-content:center;
align-items:center;
}

.box{
background:white;
width:380px;
padding:35px;
border-radius:10px;
text-align:center;
border:3px solid #800020;
box-shadow:0 10px 25px rgba(0,0,0,0.3);
}

.input-group{
position:relative;
margin-bottom:20px;
}

.input-group input{
width:300px;
padding:12px 40px;
border:2px solid #800020;
border-radius:6px;
}

.icon{
position:absolute;
left:12px;
top:50%;
transform:translateY(-50%);
color:#800020;
}

label{
position:absolute;
left:40px;
top:12px;
background:white;
padding:0 5px;
color:gray;
transition:0.3s;
}

input:focus + label,
input:valid + label{
top:-8px;
font-size:12px;
color:#800020;
}

button{
padding:12px;
background:#800020;
color:white;
border:none;
border-radius:6px;
cursor:pointer;
}

.msg{
margin-bottom:10px;
color:red;
font-weight:bold;
}

.link{
margin-top:15px;
}

.link a{
color:#800020;
font-weight:bold;
text-decoration:none;
}
</style>

</head>

<body>

<div class="box">
<h2 style="color:#800020;">Register</h2>

<?php if($msg != ""): ?>
<div class="msg"><?= $msg ?></div>
<?php endif; ?>

<form method="POST">

<div class="input-group">
    <i class="fa fa-user icon"></i>
    <input type="text" name="username" required>
    <label>Username</label>
</div>

<div class="input-group">
    <i class="fa fa-lock icon"></i>
    <input type="password" name="password" required>
    <label>Password</label>
</div>

<div class="input-group">
    <i class="fa fa-lock icon"></i>
    <input type="password" name="confirm_password" required>
    <label>Confirm Password</label>
</div>

<button type="submit" name="register">Register</button>

</form>

<div class="link">
Already have account? <a href="login.php">Login</a>
</div>

</div>

</body>
</html>