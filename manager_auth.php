<?php
// manager_auth.php — Include at top of every manager page
// Redirects non-managers away.

if(!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}
if(($_SESSION['role'] ?? '') !== 'manager') {
    header("Location: dashboard.php");
    exit();
}
?>