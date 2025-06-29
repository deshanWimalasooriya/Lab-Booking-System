<?php
require_once '../../config/config.php';
requireLogin();

if (!hasRole(ROLE_INSTRUCTOR) && !hasRole(ROLE_LECTURE_IN_CHARGE)) {
    header('Location: ../../dashboard.php');
    exit;
}

$bookings = getUserBookings($pdo, $_SESSION['user_id']);

// Handle booking cancellation
if (isset($_GET['cancel']) && is_numeric($_GET['cancel'])) {
    $booking_id = $_GET['cancel'];
    
    // Check if booking belongs to user and is pending
    $stmt = $pdo->prepare("SELECT * FROM lab_bookings WHERE booking_id = ? AND instructor_id = ? AND status = 'pending'");
    $stmt->execute([$booking_id, $_SESSION['user_id']]);
    
    if ($stmt->fetch()) {
        $stmt = $pdo->prepare("UPDATE lab_bookings SET status = 'rejected' WHERE booking_id = ?");
        $stmt->execute([$booking_id]);
        
        logUserActivity($_SESSION['user_id'], 'cancel_booking');
        header('Location: my_bookings.php?success=Booking cancelled successfully');
        exit;
    }
}

$page_title = 'My Bookings';
$css_path = '../../assets/css/';
$nav_path = '../../';
include '../../includes/header.php';
?>

<div class="container">
    <div class="page-header">
        <h1><i class="fas fa-list"></i> My Bookings</h1>
        <a href="create_booking.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> New Booking
        </a>
    </div>
    
    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($_GET['success']); ?>
        </div>
    <?php endif; ?>
    
    <div class="card">
        <?php if (empty($bookings)): ?>
            <div class="empty-state">
                <i class="fas fa-calendar-times"></i>
                <h3>No Bookings Found</h3>
                <p>You haven't made any lab bookings yet.</p>
                <a href="create_booking.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Create Your First Booking
                </a>
            </div>
        <?php else: ?>
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Booking ID</th>
                            <th>Laboratory</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($bookings as $booking): ?>
                        <tr>
                            <td>#<?php echo $booking['booking_id']; ?></td>
                            <td><?php echo htmlspecialchars($booking['lab_type']); ?></td>
                            <td><?php echo date('M d, Y', strtotime($booking['date'])); ?></td>
                            <td>
                                <?php echo date('H:i', strtotime($booking['start_time'])); ?> - 
                                <?php echo date('H:i', strtotime($booking['end_time'])); ?>
                            </td>
                            <td><?php echo getStatusBadge($booking['status']); ?></td>
                            <td><?php echo date('M d, Y H:i', strtotime($booking['created_at'])); ?></td>
                            <td>
                                <?php if ($booking['status'] == 'pending'): ?>
                                    <a href="?cancel=<?php echo $booking['booking_id']; ?>" 
                                       class="btn btn-sm btn-danger"
                                       onclick="return confirm('Are you sure you want to cancel this booking?')">
                                        <i class="fas fa-times"></i> Cancel
                                    </a>
                                <?php else: ?>
                                    <span class="text-muted">No actions</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
