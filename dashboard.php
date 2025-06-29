<?php
require_once 'config/config.php';
requireLogin();

$stats = getDashboardStats($pdo);
$recent_bookings = [];

// Only get bookings for instructors and lecture in charge
if (hasRole(ROLE_INSTRUCTOR) || hasRole(ROLE_LECTURE_IN_CHARGE)) {
    $recent_bookings = getUserBookings($pdo, $_SESSION['user_id']);
    $recent_bookings = array_slice($recent_bookings, 0, 5);
}

// Get today's schedule for students
$today_schedule = [];
if (hasRole(ROLE_STUDENT)) {
    $today = date('Y-m-d');
    $stmt = $pdo->prepare("
        SELECT b.*, l.type as lab_type, u.name as instructor_name
        FROM lab_bookings b
        JOIN labs l ON b.lab_id = l.lab_id
        JOIN users u ON b.instructor_id = u.user_id
        WHERE b.date = ? AND b.status = 'approved'
        ORDER BY b.start_time
    ");
    $stmt->execute([$today]);
    $today_schedule = $stmt->fetchAll();
}

$page_title = 'Dashboard - Lab Booking System';
$css_path = 'assets/css/';
$nav_path = '';
include 'includes/header.php';
?>

<div class="dashboard">
    <div class="dashboard-header">
        <h1><i class="fas fa-tachometer-alt"></i> Welcome to Lab Booking System</h1>
        <p>Hello, <strong><?php echo $_SESSION['name']; ?></strong>! Role: <span class="badge badge-info"><?php echo ucfirst(str_replace('_', ' ', $_SESSION['role'])); ?></span></p>
    </div>
    
    <?php if (hasRole(ROLE_STUDENT)): ?>
        <!-- Student Dashboard -->
        <div class="dashboard-stats">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-building"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $stats['total_labs']; ?></h3>
                    <p>Total Laboratories</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-calendar-day"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo count($today_schedule); ?></h3>
                    <p>Today's Sessions</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $stats['available_labs']; ?></h3>
                    <p>Available Labs</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-tools"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $stats['total_equipment']; ?></h3>
                    <p>Equipment Items</p>
                </div>
            </div>
        </div>
        
        <div class="dashboard-grid">
            <div class="card">
                <h2><i class="fas fa-eye"></i> Available Actions</h2>
                <div class="quick-actions">
                    <a href="pages/schedules/view_schedule.php" class="btn btn-primary">
                        <i class="fas fa-calendar"></i> View Lab Schedule
                    </a>
                    <a href="pages/labs/view_labs.php" class="btn btn-info">
                        <i class="fas fa-building"></i> View Laboratories
                    </a>
                </div>
                <div class="student-notice">
                    <p><i class="fas fa-info-circle"></i> <strong>Note:</strong> As a student, you can view lab schedules and laboratory information. For lab bookings, please contact your instructor.</p>
                </div>
            </div>
            
            <?php if (!empty($today_schedule)): ?>
            <div class="card">
                <h2><i class="fas fa-calendar-day"></i> Today's Lab Sessions</h2>
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Time</th>
                                <th>Laboratory</th>
                                <th>Instructor</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($today_schedule as $session): ?>
                            <tr>
                                <td><?php echo date('H:i', strtotime($session['start_time'])) . ' - ' . date('H:i', strtotime($session['end_time'])); ?></td>
                                <td><?php echo htmlspecialchars($session['lab_type']); ?></td>
                                <td><?php echo htmlspecialchars($session['instructor_name']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php else: ?>
            <div class="card">
                <h2><i class="fas fa-calendar-day"></i> Today's Lab Sessions</h2>
                <div class="empty-state">
                    <i class="fas fa-calendar-times"></i>
                    <h3>No Sessions Today</h3>
                    <p>There are no lab sessions scheduled for today.</p>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
    <?php else: ?>
        <!-- Regular Dashboard for other roles -->
        <div class="dashboard-stats">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-building"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $stats['total_labs']; ?></h3>
                    <p>Total Laboratories</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-calendar-check"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $stats['active_bookings']; ?></h3>
                    <p>Active Bookings</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-tools"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $stats['total_equipment']; ?></h3>
                    <p>Equipment Items</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $stats['available_labs']; ?></h3>
                    <p>Available Labs</p>
                </div>
            </div>
        </div>
        
        <div class="dashboard-grid">
            <div class="card">
                <h2><i class="fas fa-rocket"></i> Quick Actions</h2>
                <div class="quick-actions">
                    <?php if (hasRole(ROLE_INSTRUCTOR) || hasRole(ROLE_LECTURE_IN_CHARGE)): ?>
                        <a href="pages/bookings/create_booking.php" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Book Laboratory
                        </a>
                        <a href="pages/bookings/my_bookings.php" class="btn btn-success">
                            <i class="fas fa-list"></i> My Bookings
                        </a>
                    <?php endif; ?>
                    
                    <?php if (hasRole(ROLE_LAB_TO)): ?>
                        <a href="pages/equipment/add_equipment.php" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Add Equipment
                        </a>
                        <a href="pages/equipment/maintenance.php" class="btn btn-warning">
                            <i class="fas fa-wrench"></i> Maintenance
                        </a>
                        <a href="pages/bookings/view_bookings.php" class="btn btn-info">
                            <i class="fas fa-eye"></i> Approve Bookings
                        </a>
                    <?php endif; ?>
                    
                    <a href="pages/schedules/view_schedule.php" class="btn btn-info">
                        <i class="fas fa-calendar"></i> View Schedule
                    </a>
                    <a href="pages/labs/view_labs.php" class="btn btn-secondary">
                        <i class="fas fa-building"></i> View All Labs
                    </a>
                </div>
            </div>
            
            <?php if (!empty($recent_bookings)): ?>
            <div class="card">
                <h2><i class="fas fa-history"></i> Recent Bookings</h2>
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Lab</th>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_bookings as $booking): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($booking['lab_type']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($booking['date'])); ?></td>
                                <td><?php echo date('H:i', strtotime($booking['start_time'])) . ' - ' . date('H:i', strtotime($booking['end_time'])); ?></td>
                                <td><?php echo getStatusBadge($booking['status']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
