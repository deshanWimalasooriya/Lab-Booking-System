<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title ?? 'Lab Booking System'; ?></title>
    <link rel="stylesheet" href="<?php echo isset($css_path) ? $css_path : 'assets/css/'; ?>style.css">
    <style>
/* Hide test notification button completely */
button[style*="position: fixed"][style*="top: 10px"],
*[style*="position: fixed"][style*="top: 10px"][style*="right: 10px"] {
    display: none !important;
    visibility: hidden !important;
}
</style>

    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <?php if (isLoggedIn()): ?>
        <nav class="navbar">
            <div class="nav-container">
                <a href="<?php echo isset($nav_path) ? $nav_path : ''; ?>dashboard.php" class="nav-brand">
                    <i class="fas fa-flask"></i> Lab Booking System
                </a>
                
                <ul class="nav-menu">
                    <li><a href="<?php echo isset($nav_path) ? $nav_path : ''; ?>dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
                    
                    <?php if (hasRole(ROLE_STUDENT)): ?>
                        <!-- Student Navigation -->
                        <li><a href="<?php echo isset($nav_path) ? $nav_path : ''; ?>pages/schedules/view_schedule.php"><i class="fas fa-calendar"></i> View Schedule</a></li>
                        <li><a href="<?php echo isset($nav_path) ? $nav_path : ''; ?>pages/labs/view_labs.php"><i class="fas fa-building"></i> View Labs</a></li>
                    
                    <?php elseif (hasRole(ROLE_INSTRUCTOR)): ?>
                        <!-- Instructor Navigation -->
                        <li class="dropdown">
                            <a href="#"><i class="fas fa-calendar"></i> Bookings <i class="fas fa-chevron-down"></i></a>
                            <ul class="dropdown-menu">
                                <li><a href="<?php echo isset($nav_path) ? $nav_path : ''; ?>pages/bookings/create_booking.php">New Booking</a></li>
                                <li><a href="<?php echo isset($nav_path) ? $nav_path : ''; ?>pages/bookings/my_bookings.php">My Bookings</a></li>
                            </ul>
                        </li>
                        <li><a href="<?php echo isset($nav_path) ? $nav_path : ''; ?>pages/labs/view_labs.php"><i class="fas fa-building"></i> Labs</a></li>
                        <li><a href="<?php echo isset($nav_path) ? $nav_path : ''; ?>pages/schedules/view_schedule.php"><i class="fas fa-clock"></i> Schedule</a></li>
                    
                    <?php elseif (hasRole(ROLE_LECTURE_IN_CHARGE)): ?>
                        <!-- Lecture in Charge Navigation -->
                        <li class="dropdown">
                            <a href="#"><i class="fas fa-calendar"></i> Bookings <i class="fas fa-chevron-down"></i></a>
                            <ul class="dropdown-menu">
                                <li><a href="<?php echo isset($nav_path) ? $nav_path : ''; ?>pages/bookings/create_booking.php">New Booking</a></li>
                                <li><a href="<?php echo isset($nav_path) ? $nav_path : ''; ?>pages/bookings/my_bookings.php">My Bookings</a></li>
                                <li><a href="<?php echo isset($nav_path) ? $nav_path : ''; ?>pages/bookings/view_bookings.php">Manage Bookings</a></li>
                            </ul>
                        </li>
                        
                        <li class="dropdown">
                            <a href="#"><i class="fas fa-building"></i> Labs <i class="fas fa-chevron-down"></i></a>
                            <ul class="dropdown-menu">
                                <li><a href="<?php echo isset($nav_path) ? $nav_path : ''; ?>pages/labs/view_labs.php">View Labs</a></li>
                                <li><a href="<?php echo isset($nav_path) ? $nav_path : ''; ?>pages/labs/add_lab.php">Add Laboratory</a></li>
                            </ul>
                        </li>
                        
                        <li class="dropdown">
                            <a href="#"><i class="fas fa-tools"></i> Equipment <i class="fas fa-chevron-down"></i></a>
                            <ul class="dropdown-menu">
                                <li><a href="<?php echo isset($nav_path) ? $nav_path : ''; ?>pages/equipment/view_equipment.php">View Equipment</a></li>
                                <li><a href="<?php echo isset($nav_path) ? $nav_path : ''; ?>pages/equipment/add_equipment.php">Add Equipment</a></li>
                                <li><a href="<?php echo isset($nav_path) ? $nav_path : ''; ?>pages/equipment/maintenance.php">Maintenance</a></li>
                            </ul>
                        </li>
                        
                        <li class="dropdown">
                            <a href="#"><i class="fas fa-users"></i> Users <i class="fas fa-chevron-down"></i></a>
                            <ul class="dropdown-menu">
                                <li><a href="<?php echo isset($nav_path) ? $nav_path : ''; ?>pages/users/manage_users.php">Manage Users</a></li>
                                <li><a href="<?php echo isset($nav_path) ? $nav_path : ''; ?>pages/users/create_user.php">Create User</a></li>
                            </ul>
                        </li>
                        
                        <li><a href="<?php echo isset($nav_path) ? $nav_path : ''; ?>pages/schedules/view_schedule.php"><i class="fas fa-clock"></i> Schedule</a></li>
                    
                    <?php elseif (hasRole(ROLE_LAB_TO)): ?>
                        <!-- Lab TO Navigation -->
                        <li class="dropdown">
                            <a href="#"><i class="fas fa-calendar-check"></i> Bookings <i class="fas fa-chevron-down"></i></a>
                            <ul class="dropdown-menu">
                                <li><a href="<?php echo isset($nav_path) ? $nav_path : ''; ?>pages/bookings/view_bookings.php">Manage Bookings</a></li>
                                <li><a href="<?php echo isset($nav_path) ? $nav_path : ''; ?>pages/bookings/create_booking.php">Create Booking</a></li>
                            </ul>
                        </li>
                        
                        <li class="dropdown">
                            <a href="#"><i class="fas fa-tools"></i> Equipment <i class="fas fa-chevron-down"></i></a>
                            <ul class="dropdown-menu">
                                <li><a href="<?php echo isset($nav_path) ? $nav_path : ''; ?>pages/equipment/view_equipment.php">View Equipment</a></li>
                                <li><a href="<?php echo isset($nav_path) ? $nav_path : ''; ?>pages/equipment/add_equipment.php">Add Equipment</a></li>
                                <li><a href="<?php echo isset($nav_path) ? $nav_path : ''; ?>pages/equipment/maintenance.php">Maintenance</a></li>
                            </ul>
                        </li>
                        
                        <li class="dropdown">
                            <a href="#"><i class="fas fa-building"></i> Labs <i class="fas fa-chevron-down"></i></a>
                            <ul class="dropdown-menu">
                                <li><a href="<?php echo isset($nav_path) ? $nav_path : ''; ?>pages/labs/view_labs.php">View Labs</a></li>
                                <li><a href="<?php echo isset($nav_path) ? $nav_path : ''; ?>pages/labs/add_lab.php">Add Laboratory</a></li>
                            </ul>
                        </li>
                        
                        <li><a href="<?php echo isset($nav_path) ? $nav_path : ''; ?>pages/schedules/view_schedule.php"><i class="fas fa-clock"></i> Schedule</a></li>
                    <?php endif; ?>
                    
                    <!-- Notification System -->
                    <li class="notification-dropdown">
                        <a href="#" class="notification-toggle" id="notificationToggle">
                            <i class="fas fa-bell"></i>
                            <span class="notification-badge" id="notificationBadge">0</span>
                        </a>
                        <div class="notification-panel" id="notificationPanel">
                            <div class="notification-header">
                                <h3>Notifications</h3>
                                <button class="mark-all-read" id="markAllRead">
                                    <i class="fas fa-check-double"></i> Mark all read
                                </button>
                            </div>
                            <ul class="notification-list" id="notificationList">
                                <!-- Notifications will be loaded here -->
                            </ul>
                            <div class="notification-footer">
                                <a href="<?php echo isset($nav_path) ? $nav_path : ''; ?>pages/notifications/view_all.php">View All Notifications</a>
                            </div>
                        </div>
                    </li>
                    
                    <!-- User Profile Dropdown -->
                    <li class="dropdown">
                        <a href="#"><i class="fas fa-user"></i> <?php echo $_SESSION['name']; ?> <i class="fas fa-chevron-down"></i></a>
                        <ul class="dropdown-menu">
                            <li><a href="<?php echo isset($nav_path) ? $nav_path : ''; ?>pages/users/profile.php"><i class="fas fa-user-edit"></i> Profile</a></li>
                            <li><a href="<?php echo isset($nav_path) ? $nav_path : ''; ?>logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </nav>
    <?php endif; ?>
    
    <main class="main-content">
