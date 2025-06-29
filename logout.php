<?php
session_start();

// Check if user is logged in before logging out
if (isset($_SESSION['user_id'])) {
    // Include database connection for logging
    require_once 'config/database.php';
    
    // Log the logout activity
    try {
        $stmt = $pdo->prepare("INSERT INTO user_log (user_id, action, time_stamp) VALUES (?, 'logout', NOW())");
        $stmt->execute([$_SESSION['user_id']]);
    } catch(Exception $e) {
        // Continue with logout even if logging fails
    }
}

// Destroy all session data
session_unset();
session_destroy();

// Clear session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Redirect to login page
header('Location: login.php?message=logged_out');
exit;
?>
