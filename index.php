<?php
require_once 'config/config.php';

// If user is already logged in, redirect to dashboard
if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

// Otherwise redirect to login
header('Location: login.php');
exit;
?>
