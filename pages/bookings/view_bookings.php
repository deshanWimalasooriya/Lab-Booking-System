<?php
require_once '../../config/config.php';
requireLogin();

// Only Lab TO and Lecture in Charge can view all bookings
if (!hasRole(ROLE_LAB_TO) && !hasRole(ROLE_LECTURE_IN_CHARGE)) {
    header('Location: ../../dashboard.php');
    exit;
}

$success = '';
$error = '';

// Handle success/error messages from URL
if (isset($_GET['success'])) {
    $success = htmlspecialchars($_GET['success']);
}
if (isset($_GET['error'])) {
    $error = htmlspecialchars($_GET['error']);
}

// Handle booking approval/rejection
if (isset($_GET['action']) && isset($_GET['id']) && is_numeric($_GET['id'])) {
    $booking_id = $_GET['id'];
    $action = $_GET['action'];
    
    if (in_array($action, ['approve', 'reject'])) {
        $status = ($action == 'approve') ? 'approved' : 'rejected';
        
        try {
            if ($action == 'approve') {
                // Get booking details for conflict check
                $stmt = $pdo->prepare("SELECT * FROM lab_bookings WHERE booking_id = ?");
                $stmt->execute([$booking_id]);
                $booking = $stmt->fetch();
                
                if ($booking) {
                    // Check for conflicts with other approved bookings
                    $stmt = $pdo->prepare("
                        SELECT COUNT(*) as count FROM lab_bookings 
                        WHERE lab_id = ? AND date = ? AND status = 'approved' AND booking_id != ?
                        AND ((start_time <= ? AND end_time > ?) OR (start_time < ? AND end_time >= ?))
                    ");
                    $stmt->execute([
                        $booking['lab_id'], 
                        $booking['date'], 
                        $booking_id,
                        $booking['start_time'], $booking['start_time'], 
                        $booking['end_time'], $booking['end_time']
                    ]);
                    $conflict = $stmt->fetch()['count'];
                    
                    if ($conflict > 0) {
                        header('Location: view_bookings.php?error=Cannot approve: Time slot conflicts with existing approved booking!');
                        exit;
                    }
                }
            }
            
            // Update booking status
            $stmt = $pdo->prepare("UPDATE lab_bookings SET status = ? WHERE booking_id = ?");
            if ($stmt->execute([$status, $booking_id])) {
                // Try to send notification (safe approach)
                try {
                    if (function_exists('createNotification')) {
                        // Get booking details for notification
                        $stmt = $pdo->prepare("
                            SELECT b.*, l.type as lab_type
                            FROM lab_bookings b
                            JOIN labs l ON b.lab_id = l.lab_id
                            WHERE b.booking_id = ?
                        ");
                        $stmt->execute([$booking_id]);
                        $booking_data = $stmt->fetch();
                        
                        if ($booking_data) {
                            $notification_type = ($status == 'approved') ? 'booking_approved' : 'booking_rejected';
                            $notification_title = ($status == 'approved') ? 'Booking Approved ✅' : 'Booking Rejected ❌';
                            $notification_message = "Your booking for {$booking_data['lab_type']} on " . date('M d, Y', strtotime($booking_data['date'])) . " has been {$status}.";
                            
                            createNotification(
                                $pdo,
                                $booking_data['instructor_id'],
                                $notification_type,
                                $notification_title,
                                $notification_message,
                                $booking_id
                            );
                        }
                    }
                } catch (Exception $e) {
                    // Notification failed, but booking status was updated successfully
                    error_log("Notification failed: " . $e->getMessage());
                }
                
                logUserActivity($_SESSION['user_id'], $action . '_booking');
                header('Location: view_bookings.php?success=Booking ' . $action . 'd successfully!');
                exit;
            } else {
                header('Location: view_bookings.php?error=Failed to ' . $action . ' booking!');
                exit;
            }
        } catch (Exception $e) {
            header('Location: view_bookings.php?error=Database error occurred');
            exit;
        }
    }
}

// Get filter parameters
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$lab_filter = isset($_GET['lab']) ? $_GET['lab'] : '';
$date_filter = isset($_GET['date']) ? $_GET['date'] : '';

// Build query with filters
$where_conditions = [];
$params = [];

if ($status_filter) {
    $where_conditions[] = "b.status = ?";
    $params[] = $status_filter;
}

if ($lab_filter) {
    $where_conditions[] = "b.lab_id = ?";
    $params[] = $lab_filter;
}

if ($date_filter) {
    $where_conditions[] = "b.date = ?";
    $params[] = $date_filter;
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get all bookings with filters
try {
    $stmt = $pdo->prepare("
        SELECT b.*, l.type as lab_type, u.name as instructor_name, u.email as instructor_email
        FROM lab_bookings b
        JOIN labs l ON b.lab_id = l.lab_id
        JOIN users u ON b.instructor_id = u.user_id
        $where_clause
        ORDER BY 
            CASE b.status 
                WHEN 'pending' THEN 1 
                WHEN 'approved' THEN 2 
                WHEN 'rejected' THEN 3 
            END,
            b.date DESC, b.start_time DESC
    ");
    $stmt->execute($params);
    $bookings = $stmt->fetchAll();
} catch (Exception $e) {
    $error = "Failed to load bookings";
    $bookings = [];
}

// Get labs for filter
$labs = getAllLabs($pdo);

// Get booking statistics
try {
    $stats_stmt = $pdo->query("
        SELECT 
            status,
            COUNT(*) as count
        FROM lab_bookings 
        GROUP BY status
    ");
    $booking_stats = [];
    while ($row = $stats_stmt->fetch()) {
        $booking_stats[$row['status']] = $row['count'];
    }
} catch (Exception $e) {
    $booking_stats = ['pending' => 0, 'approved' => 0, 'rejected' => 0];
}

$page_title = 'Manage Bookings';
$css_path = '../../assets/css/';
$nav_path = '../../';
include '../../includes/header.php';
?>

<div class="container">
    <div class="page-header">
        <h1><i class="fas fa-calendar-check"></i> Manage Bookings</h1>
        <div class="header-actions">
            <a href="../../dashboard.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>
    
    <?php if ($success): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> <?php echo $success; ?>
        </div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
        </div>
    <?php endif; ?>
    
    <!-- Booking Statistics -->
    <div class="booking-stats">
        <div class="stat-card pending">
            <div class="stat-icon">
                <i class="fas fa-clock"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo $booking_stats['pending'] ?? 0; ?></h3>
                <p>Pending Approval</p>
            </div>
        </div>
        
        <div class="stat-card approved">
            <div class="stat-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo $booking_stats['approved'] ?? 0; ?></h3>
                <p>Approved</p>
            </div>
        </div>
        
        <div class="stat-card rejected">
            <div class="stat-icon">
                <i class="fas fa-times-circle"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo $booking_stats['rejected'] ?? 0; ?></h3>
                <p>Rejected</p>
            </div>
        </div>
    </div>
    
    <!-- Filters -->
    <div class="card">
        <h3><i class="fas fa-filter"></i> Filter Bookings</h3>
        <form method="GET" class="filter-form">
            <div class="filter-row">
                <div class="form-group">
                    <label for="status">Status</label>
                    <select id="status" name="status">
                        <option value="">All Statuses</option>
                        <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="approved" <?php echo $status_filter == 'approved' ? 'selected' : ''; ?>>Approved</option>
                        <option value="rejected" <?php echo $status_filter == 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="lab">Laboratory</label>
                    <select id="lab" name="lab">
                        <option value="">All Laboratories</option>
                        <?php foreach ($labs as $lab): ?>
                            <option value="<?php echo $lab['lab_id']; ?>" 
                                    <?php echo $lab_filter == $lab['lab_id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($lab['type']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="date">Date</label>
                    <input type="date" id="date" name="date" value="<?php echo htmlspecialchars($date_filter); ?>">
                </div>
                
                <div class="form-group">
                    <label>&nbsp;</label>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> Filter
                    </button>
                    <a href="view_bookings.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Clear
                    </a>
                </div>
            </div>
        </form>
    </div>
    
    <!-- Bookings Table -->
    <div class="card">
        <?php if (empty($bookings)): ?>
            <div class="empty-state">
                <i class="fas fa-calendar-times"></i>
                <h3>No Bookings Found</h3>
                <p>No bookings match your current filters.</p>
            </div>
        <?php else: ?>
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Booking ID</th>
                            <th>Instructor</th>
                            <th>Laboratory</th>
                            <th>Date & Time</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($bookings as $booking): ?>
                        <tr class="booking-row <?php echo $booking['status']; ?>">
                            <td>
                                <strong>#<?php echo $booking['booking_id']; ?></strong>
                            </td>
                            <td>
                                <div class="instructor-info">
                                    <strong><?php echo htmlspecialchars($booking['instructor_name']); ?></strong>
                                    <br><small><?php echo htmlspecialchars($booking['instructor_email']); ?></small>
                                </div>
                            </td>
                            <td>
                                <span class="lab-badge"><?php echo htmlspecialchars($booking['lab_type']); ?></span>
                            </td>
                            <td>
                                <div class="datetime-info">
                                    <strong><?php echo date('M d, Y', strtotime($booking['date'])); ?></strong>
                                    <br><small>
                                        <?php echo date('H:i', strtotime($booking['start_time'])); ?> - 
                                        <?php echo date('H:i', strtotime($booking['end_time'])); ?>
                                    </small>
                                </div>
                            </td>
                            <td><?php echo getStatusBadge($booking['status']); ?></td>
                            <td>
                                <div class="action-buttons">
                                    <?php if ($booking['status'] == 'pending'): ?>
                                        <a href="?action=approve&id=<?php echo $booking['booking_id']; ?>" 
                                           class="btn btn-sm btn-success"
                                           onclick="return confirm('Approve this booking request for <?php echo htmlspecialchars($booking['lab_type']); ?> on <?php echo date('M d, Y', strtotime($booking['date'])); ?>?')">
                                            <i class="fas fa-check"></i> Approve
                                        </a>
                                        <a href="?action=reject&id=<?php echo $booking['booking_id']; ?>" 
                                           class="btn btn-sm btn-danger"
                                           onclick="return confirm('Reject this booking request for <?php echo htmlspecialchars($booking['lab_type']); ?>?')">
                                            <i class="fas fa-times"></i> Reject
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted">
                                            <i class="fas fa-<?php echo $booking['status'] == 'approved' ? 'check' : 'times'; ?>"></i>
                                            <?php echo ucfirst($booking['status']); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Quick Actions for Pending Bookings -->
    <?php 
    $pending_bookings = array_filter($bookings, function($b) { return $b['status'] == 'pending'; });
    if (!empty($pending_bookings) && count($pending_bookings) > 0): 
    ?>
    <div class="card">
        <h3><i class="fas fa-bolt"></i> Quick Actions</h3>
        <p>Pending bookings requiring your attention (<?php echo count($pending_bookings); ?> total):</p>
        <div class="quick-actions-grid">
            <?php foreach (array_slice($pending_bookings, 0, 3) as $booking): ?>
                <div class="quick-action-item">
                    <div class="quick-action-info">
                        <strong><?php echo htmlspecialchars($booking['instructor_name']); ?></strong>
                        <br><small><?php echo htmlspecialchars($booking['lab_type']); ?> - <?php echo date('M d, H:i', strtotime($booking['date'] . ' ' . $booking['start_time'])); ?></small>
                    </div>
                    <div class="quick-action-buttons">
                        <a href="?action=approve&id=<?php echo $booking['booking_id']; ?>" 
                           class="btn btn-sm btn-success"
                           onclick="return confirm('Approve this booking?')"
                           title="Approve booking">
                            <i class="fas fa-check"></i>
                        </a>
                        <a href="?action=reject&id=<?php echo $booking['booking_id']; ?>" 
                           class="btn btn-sm btn-danger"
                           onclick="return confirm('Reject this booking?')"
                           title="Reject booking">
                            <i class="fas fa-times"></i>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <?php if (count($pending_bookings) > 3): ?>
            <div style="text-align: center; margin-top: 1rem;">
                <small class="text-muted">
                    Showing 3 of <?php echo count($pending_bookings); ?> pending bookings. 
                    <a href="?status=pending">View all pending bookings</a>
                </small>
            </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<?php include '../../includes/footer.php'; ?>
