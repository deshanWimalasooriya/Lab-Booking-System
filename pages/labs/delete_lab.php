<?php
require_once '../../config/config.php';
requireLogin();

// Only Lab TO and Lecture in Charge can delete labs
if (!hasRole(ROLE_LAB_TO) && !hasRole(ROLE_LECTURE_IN_CHARGE)) {
    header('Location: ../../dashboard.php');
    exit;
}

$lab_id = isset($_GET['id']) ? $_GET['id'] : null;

if (!$lab_id) {
    header('Location: view_labs.php');
    exit;
}

// Get lab data
$stmt = $pdo->prepare("SELECT * FROM labs WHERE lab_id = ?");
$stmt->execute([$lab_id]);
$lab = $stmt->fetch();

if (!$lab) {
    header('Location: view_labs.php?error=Lab not found');
    exit;
}

// Check for existing bookings
$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM lab_bookings WHERE lab_id = ? AND status = 'approved' AND date >= CURDATE()");
$stmt->execute([$lab_id]);
$future_bookings = $stmt->fetch()['count'];

if ($future_bookings > 0) {
    header('Location: view_labs.php?error=Cannot delete laboratory with future approved bookings');
    exit;
}

// Delete lab and related data
try {
    $pdo->beginTransaction();
    
    // Delete equipment
    $stmt = $pdo->prepare("DELETE FROM lab_equipments WHERE lab_id = ?");
    $stmt->execute([$lab_id]);
    
    // Delete bookings
    $stmt = $pdo->prepare("DELETE FROM lab_bookings WHERE lab_id = ?");
    $stmt->execute([$lab_id]);
    
    // Delete schedules
    $stmt = $pdo->prepare("DELETE FROM lab_schedules WHERE lab_id = ?");
    $stmt->execute([$lab_id]);
    
    // Delete lab
    $stmt = $pdo->prepare("DELETE FROM labs WHERE lab_id = ?");
    $stmt->execute([$lab_id]);
    
    $pdo->commit();
    
    logUserActivity($_SESSION['user_id'], 'delete_lab', $lab_id);
    header('Location: view_labs.php?success=Laboratory deleted successfully');
    exit;
    
} catch (Exception $e) {
    $pdo->rollback();
    header('Location: view_labs.php?error=Failed to delete laboratory');
    exit;
}
?>
