<?php
require_once '../../config/config.php';
requireLogin();

// Only Lab TO and Lecture in Charge can delete equipment
if (!hasRole(ROLE_LAB_TO) && !hasRole(ROLE_LECTURE_IN_CHARGE)) {
    header('Location: ../../dashboard.php');
    exit;
}

$equipment_id = isset($_GET['id']) ? $_GET['id'] : null;

if (!$equipment_id) {
    header('Location: view_equipment.php');
    exit;
}

// Get equipment data for notification
$stmt = $pdo->prepare("SELECT * FROM lab_equipments WHERE equipment_id = ?");
$stmt->execute([$equipment_id]);
$equipment = $stmt->fetch();

if (!$equipment) {
    header('Location: view_equipment.php?error=Equipment not found');
    exit;
}

// Delete equipment
try {
    $stmt = $pdo->prepare("DELETE FROM lab_equipments WHERE equipment_id = ?");
    $stmt->execute([$equipment_id]);
    
    // Notify Lab TOs about equipment deletion
    notifyLabTOsAboutEquipmentUpdate($pdo, $equipment_id, 'deleted');
    
    logUserActivity($_SESSION['user_id'], 'delete_equipment', $equipment['lab_id']);
    header('Location: view_equipment.php?success=Equipment deleted successfully');
    exit;
    
} catch (Exception $e) {
    header('Location: view_equipment.php?error=Failed to delete equipment');
    exit;
}
?>
