<?php
require_once '../../config/config.php';
requireLogin();

// Handle week navigation
$week_offset = isset($_GET['week']) ? $_GET['week'] : date('Y-m-d');
$current_date = new DateTime($week_offset);

// Get Monday of the current week
$week_start = clone $current_date;
$week_start->modify('monday this week');

// Create array of week dates
$week_dates = [];
for ($i = 0; $i < 7; $i++) {
    $date = clone $week_start;
    $date->modify("+$i days");
    $week_dates[] = $date;
}

// Get week end date
$week_end = clone $week_start;
$week_end->modify('+6 days');

// Get all labs
$labs = getAllLabs($pdo);

// Define time slots
$time_slots = [
    '08:00', '09:00', '10:00', '11:00', '12:00', 
    '13:00', '14:00', '15:00', '16:00', '17:00'
];

// Get bookings for the week
$week_start_str = $week_start->format('Y-m-d');
$week_end_str = $week_end->format('Y-m-d');

$stmt = $pdo->prepare("
    SELECT b.*, l.type as lab_type, u.name as instructor_name
    FROM lab_bookings b
    JOIN labs l ON b.lab_id = l.lab_id
    JOIN users u ON b.instructor_id = u.user_id
    WHERE b.date BETWEEN ? AND ? AND b.status = 'approved'
    ORDER BY b.date, b.start_time
");
$stmt->execute([$week_start_str, $week_end_str]);
$bookings = $stmt->fetchAll();

// Organize bookings by date and time
$schedule = [];
foreach ($bookings as $booking) {
    $date = $booking['date'];
    $start_hour = date('H:i', strtotime($booking['start_time']));
    $end_hour = date('H:i', strtotime($booking['end_time']));
    
    // Create time range for booking
    $start_time = new DateTime($booking['start_time']);
    $end_time = new DateTime($booking['end_time']);
    
    // Add booking to each hour it spans
    foreach ($time_slots as $slot) {
        $slot_time = new DateTime($slot . ':00');
        $next_slot_time = clone $slot_time;
        $next_slot_time->modify('+1 hour');
        
        // Check if booking overlaps with this time slot
        if ($start_time < $next_slot_time && $end_time > $slot_time) {
            $schedule[$date][$slot][] = $booking;
        }
    }
}

// Calculate navigation dates
$prev_week = clone $week_start;
$prev_week->modify('-1 week');
$next_week = clone $week_start;
$next_week->modify('+1 week');

$page_title = 'Weekly Schedule';
$css_path = '../../assets/css/';
$nav_path = '../../';
include '../../includes/header.php';
?>

<div class="container">
    <div class="page-header">
        <h1><i class="fas fa-calendar"></i> Weekly Schedule</h1>
        <div class="week-navigation">
            <a href="?week=<?php echo $prev_week->format('Y-m-d'); ?>" class="btn btn-secondary">
                <i class="fas fa-chevron-left"></i> Previous Week
            </a>
            <span class="current-week">
                <?php echo $week_start->format('M d') . ' - ' . $week_end->format('M d, Y'); ?>
            </span>
            <a href="?week=<?php echo $next_week->format('Y-m-d'); ?>" class="btn btn-secondary">
                Next Week <i class="fas fa-chevron-right"></i>
            </a>
        </div>
    </div>
    
    <!-- Week Summary -->
    <div class="week-summary">
        <div class="summary-card">
            <h3><?php echo count($bookings); ?></h3>
            <p>Total Bookings This Week</p>
        </div>
        <div class="summary-card">
            <h3><?php echo count(array_unique(array_column($bookings, 'lab_id'))); ?></h3>
            <p>Labs in Use</p>
        </div>
        <div class="summary-card">
            <h3><?php echo count(array_unique(array_column($bookings, 'instructor_id'))); ?></h3>
            <p>Active Instructors</p>
        </div>
    </div>
    
    <div class="schedule-container">
        <div class="schedule-table">
            <table class="schedule-grid">
                <thead>
                    <tr class="schedule-header">
                        <th class="time-slot-header">Time</th>
                        <?php foreach ($week_dates as $date): ?>
                            <th class="day-header <?php echo $date->format('Y-m-d') == date('Y-m-d') ? 'today' : ''; ?>">
                                <div class="day-name"><?php echo $date->format('l'); ?></div>
                                <div class="day-date"><?php echo $date->format('M d'); ?></div>
                            </th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($time_slots as $time): ?>
                        <tr class="schedule-row">
                            <td class="time-slot"><?php echo $time; ?></td>
                            <?php foreach ($week_dates as $date): ?>
                                <td class="schedule-cell">
                                    <?php
                                    $date_str = $date->format('Y-m-d');
                                    
                                    // Display bookings for this time slot and date
                                    if (isset($schedule[$date_str][$time])) {
                                        foreach ($schedule[$date_str][$time] as $booking) {
                                            $start_time = date('H:i', strtotime($booking['start_time']));
                                            $end_time = date('H:i', strtotime($booking['end_time']));
                                            
                                            echo '<div class="booking-item" title="' . 
                                                 htmlspecialchars($booking['lab_type'] . ' - ' . $booking['instructor_name']) . '">';
                                            echo '<div class="booking-lab">' . htmlspecialchars($booking['lab_type']) . '</div>';
                                            echo '<div class="booking-instructor">' . htmlspecialchars($booking['instructor_name']) . '</div>';
                                            echo '<div class="booking-time">' . $start_time . ' - ' . $end_time . '</div>';
                                            echo '</div>';
                                        }
                                    }
                                    ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Today's Bookings Summary -->
    <?php
    $today = date('Y-m-d');
    $today_bookings = array_filter($bookings, function($booking) use ($today) {
        return $booking['date'] == $today;
    });
    ?>
    
    <?php if (!empty($today_bookings)): ?>
    <div class="card">
        <h2><i class="fas fa-calendar-day"></i> Today's Lab Sessions (<?php echo date('l, M d, Y'); ?>)</h2>
        <div class="today-sessions">
            <?php foreach ($today_bookings as $session): ?>
                <div class="session-item">
                    <div class="session-time">
                        <i class="fas fa-clock"></i>
                        <?php echo date('H:i', strtotime($session['start_time'])) . ' - ' . date('H:i', strtotime($session['end_time'])); ?>
                    </div>
                    <div class="session-details">
                        <strong><?php echo htmlspecialchars($session['lab_type']); ?></strong>
                        <br><small>Instructor: <?php echo htmlspecialchars($session['instructor_name']); ?></small>
                    </div>
                    <div class="session-status">
                        <?php echo getStatusBadge($session['status']); ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Legend -->
    <div class="schedule-legend">
        <h3><i class="fas fa-info-circle"></i> Legend</h3>
        <div class="legend-items">
            <div class="legend-item">
                <div class="legend-color booking-item-sample"></div>
                <span>Approved Lab Session</span>
            </div>
            <div class="legend-item">
                <div class="legend-color today-sample"></div>
                <span>Today</span>
            </div>
            <div class="legend-item">
                <div class="legend-color empty-sample"></div>
                <span>Available Time Slot</span>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
