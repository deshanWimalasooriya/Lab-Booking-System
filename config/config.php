<?php
session_start();

// Site configuration
define('SITE_NAME', 'Lab Booking System');
define('SITE_URL', 'http://localhost/Lab_Booking_System');

// User roles
define('ROLE_INSTRUCTOR', 'instructor');
define('ROLE_STUDENT', 'student');
define('ROLE_LECTURE_IN_CHARGE', 'lecture_in_charge');
define('ROLE_LAB_TO', 'lab_to');

// Include database connection
require_once 'database.php';

// Include functions with proper path
if (!function_exists('isLoggedIn')) {
    require_once dirname(__DIR__) . '/includes/functions.php';
}

// Include notification functions
if (!function_exists('createNotification')) {
    require_once dirname(__DIR__) . '/includes/notification_functions.php';
}
?>
