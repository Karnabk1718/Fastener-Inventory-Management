<?php
// supervisor_auth.php — Include at top of every supervisor page
// Redirects managers away from supervisor pages.

if(!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}
if(($_SESSION['role'] ?? 'supervisor') === 'manager') {
    header("Location: manager_dashboard.php");
    exit();
}
?>