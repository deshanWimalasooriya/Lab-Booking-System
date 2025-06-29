<?php
require_once '../../config/config.php';
requireLogin();

if (!hasRole(ROLE_INSTRUCTOR) && !hasRole(ROLE_LECTURE_IN_CHARGE)) {
    header('Location: ../../dashboard.php');
    exit;
}

$labs = getAllLabs($pdo);
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $lab_id = $_POST['lab_id'];
    $date = $_POST['date'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];
    
    // Validation
    if (empty($lab_id) || empty($date) || empty($start_time) || empty($end_time)) {
        $error = "Please fill in all required fields!";
    } elseif ($start_time >= $end_time) {
        $error = "End time must be after start time!";
    } elseif ($date < date('Y-m-d')) {
        $error = "Cannot book for past dates!";
    } else {
        // Check for conflicts
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count FROM lab_bookings 
            WHERE lab_id = ? AND date = ? AND status != 'rejected'
            AND ((start_time <= ? AND end_time > ?) OR (start_time < ? AND end_time >= ?))
        ");
        $stmt->execute([$lab_id, $date, $start_time, $start_time, $end_time, $end_time]);
        $conflict = $stmt->fetch()['count'];
        
        if ($conflict > 0) {
            $error = "Time slot conflicts with existing booking!";
        } else {
            try {
                // Simple insert without created_at
                $stmt = $pdo->prepare("
                    INSERT INTO lab_bookings (instructor_id, lab_id, date, start_time, end_time, status) 
                    VALUES (?, ?, ?, ?, ?, 'pending')
                ");
                
                if ($stmt->execute([$_SESSION['user_id'], $lab_id, $date, $start_time, $end_time])) {
                    $booking_id = $pdo->lastInsertId();
                    
                    // Send notifications to Lab TOs
                    notifyLabTOsAboutBooking($pdo, $booking_id);
                    
                    logUserActivity($_SESSION['user_id'], 'create_booking', $lab_id);
                    $success = "Booking request submitted successfully! Lab TOs have been notified.";
                    
                    // Clear form data
                    $lab_id = $date = $start_time = $end_time = '';
                } else {
                    $error = "Failed to create booking!";
                }
            } catch (Exception $e) {
                $error = "Database error: " . $e->getMessage();
            }
        }
    }
}

$page_title = 'Create Booking';
$css_path = '../../assets/css/';
$nav_path = '../../';
include '../../includes/header.php';
?>

<div class="container">
    <div class="page-header">
        <h1><i class="fas fa-plus-circle"></i> Create New Booking</h1>
        <a href="my_bookings.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to My Bookings
        </a>
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
    
    <div class="card">
        <form method="POST" id="bookingForm">
            <div class="form-row">
                <div class="form-group">
                    <label for="lab_id"><i class="fas fa-building"></i> Laboratory *</label>
                    <select id="lab_id" name="lab_id" required>
                        <option value="">Select Laboratory</option>
                        <?php foreach ($labs as $lab): ?>
                            <option value="<?php echo $lab['lab_id']; ?>" 
                                    data-capacity="<?php echo $lab['capacity']; ?>"
                                    <?php echo $lab['availability'] == 'unavailable' ? 'disabled' : ''; ?>
                                    <?php echo (isset($lab_id) && $lab_id == $lab['lab_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($lab['type']); ?> 
                                (Capacity: <?php echo $lab['capacity']; ?>)
                                <?php echo $lab['availability'] == 'unavailable' ? ' - Unavailable' : ''; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="date"><i class="fas fa-calendar"></i> Date *</label>
                    <input type="date" id="date" name="date" 
                           min="<?php echo date('Y-m-d'); ?>" 
                           value="<?php echo htmlspecialchars($date ?? ''); ?>" required>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="start_time"><i class="fas fa-clock"></i> Start Time *</label>
                    <input type="time" id="start_time" name="start_time" 
                           value="<?php echo htmlspecialchars($start_time ?? ''); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="end_time"><i class="fas fa-clock"></i> End Time *</label>
                    <input type="time" id="end_time" name="end_time" 
                           value="<?php echo htmlspecialchars($end_time ?? ''); ?>" required>
                </div>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Submit Booking Request
                </button>
                <a href="my_bookings.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancel
                </a>
            </div>
        </form>
    </div>
</div>

<script>
document.getElementById('bookingForm').addEventListener('submit', function(e) {
    const startTime = document.getElementById('start_time').value;
    const endTime = document.getElementById('end_time').value;
    const date = document.getElementById('date').value;
    const labId = document.getElementById('lab_id').value;
    
    if (!labId || !date || !startTime || !endTime) {
        e.preventDefault();
        alert('Please fill in all required fields');
        return;
    }
    
    if (startTime >= endTime) {
        e.preventDefault();
        alert('End time must be after start time!');
        return;
    }
});
</script>

<?php include '../../includes/footer.php'; ?>
