<?php
// Create notification
function createNotification($pdo, $user_id, $type, $title, $message, $related_id = null) {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO notifications (user_id, type, title, message, related_id, created_at) 
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        return $stmt->execute([$user_id, $type, $title, $message, $related_id]);
    } catch (Exception $e) {
        error_log("Notification creation failed: " . $e->getMessage());
        return false;
    }
}

// Get unread notifications count
function getUnreadNotificationCount($pdo, $user_id) {
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0");
        $stmt->execute([$user_id]);
        return $stmt->fetch()['count'];
    } catch (Exception $e) {
        return 0;
    }
}

// Get notifications for user
function getUserNotifications($pdo, $user_id, $limit = 10) {
    try {
        $stmt = $pdo->prepare("
            SELECT * FROM notifications 
            WHERE user_id = ? 
            ORDER BY created_at DESC 
            LIMIT ?
        ");
        $stmt->execute([$user_id, $limit]);
        return $stmt->fetchAll();
    } catch (Exception $e) {
        return [];
    }
}

// Mark notification as read
function markNotificationAsRead($pdo, $notification_id, $user_id) {
    try {
        $stmt = $pdo->prepare("
            UPDATE notifications 
            SET is_read = 1 
            WHERE notification_id = ? AND user_id = ?
        ");
        return $stmt->execute([$notification_id, $user_id]);
    } catch (Exception $e) {
        return false;
    }
}

// Mark all notifications as read
function markAllNotificationsAsRead($pdo, $user_id) {
    try {
        $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
        return $stmt->execute([$user_id]);
    } catch (Exception $e) {
        return false;
    }
}

// Notify Lab TOs about booking creation
function notifyLabTOsAboutBooking($pdo, $booking_id) {
    try {
        // Get booking details
        $stmt = $pdo->prepare("
            SELECT b.*, l.type as lab_type, u.name as instructor_name
            FROM lab_bookings b
            JOIN labs l ON b.lab_id = l.lab_id
            JOIN users u ON b.instructor_id = u.user_id
            WHERE b.booking_id = ?
        ");
        $stmt->execute([$booking_id]);
        $booking = $stmt->fetch();
        
        if (!$booking) return false;
        
        // Get all Lab TOs
        $stmt = $pdo->prepare("SELECT user_id FROM users WHERE role = 'lab_to'");
        $stmt->execute();
        $lab_tos = $stmt->fetchAll();
        
        foreach ($lab_tos as $lab_to) {
            createNotification(
                $pdo,
                $lab_to['user_id'],
                'booking_request',
                'New Lab Booking Request',
                "New booking request for {$booking['lab_type']} by {$booking['instructor_name']} on " . date('M d, Y', strtotime($booking['date'])) . " from " . date('H:i', strtotime($booking['start_time'])) . " to " . date('H:i', strtotime($booking['end_time'])),
                $booking_id
            );
        }
        
        return true;
    } catch (Exception $e) {
        error_log("Failed to notify Lab TOs: " . $e->getMessage());
        return false;
    }
}

// Notify instructor about booking approval/rejection
function notifyInstructorAboutBookingStatus($pdo, $booking_id, $status) {
    try {
        // Get booking details
        $stmt = $pdo->prepare("
            SELECT b.*, l.type as lab_type
            FROM lab_bookings b
            JOIN labs l ON b.lab_id = l.lab_id
            WHERE b.booking_id = ?
        ");
        $stmt->execute([$booking_id]);
        $booking = $stmt->fetch();
        
        if (!$booking) return false;
        
        $type = ($status == 'approved') ? 'booking_approved' : 'booking_rejected';
        $title = ($status == 'approved') ? 'Booking Approved ✅' : 'Booking Rejected ❌';
        $message = "Your booking for {$booking['lab_type']} on " . date('M d, Y', strtotime($booking['date'])) . " from " . date('H:i', strtotime($booking['start_time'])) . " to " . date('H:i', strtotime($booking['end_time'])) . " has been {$status}.";
        
        createNotification(
            $pdo,
            $booking['instructor_id'],
            $type,
            $title,
            $message,
            $booking_id
        );
        
        return true;
    } catch (Exception $e) {
        error_log("Failed to notify instructor: " . $e->getMessage());
        return false;
    }
}

// Notify Lab TOs about lab updates
function notifyLabTOsAboutLabUpdate($pdo, $lab_id, $action = 'updated') {
    try {
        // Get lab details
        $stmt = $pdo->prepare("SELECT type FROM labs WHERE lab_id = ?");
        $stmt->execute([$lab_id]);
        $lab = $stmt->fetch();
        
        if (!$lab) return false;
        
        // Get all Lab TOs
        $stmt = $pdo->prepare("SELECT user_id FROM users WHERE role = 'lab_to'");
        $stmt->execute();
        $lab_tos = $stmt->fetchAll();
        
        $title = "Laboratory " . ucfirst($action);
        $message = "Laboratory '{$lab['type']}' has been {$action}.";
        
        foreach ($lab_tos as $lab_to) {
            createNotification(
                $pdo,
                $lab_to['user_id'],
                'lab_updated',
                $title,
                $message,
                $lab_id
            );
        }
        
        return true;
    } catch (Exception $e) {
        error_log("Failed to notify about lab update: " . $e->getMessage());
        return false;
    }
}

// Notify Lab TOs about equipment updates
function notifyLabTOsAboutEquipmentUpdate($pdo, $equipment_id, $action = 'updated') {
    try {
        // Get equipment and lab details
        $stmt = $pdo->prepare("
            SELECT e.name as equipment_name, l.type as lab_type, l.lab_id
            FROM lab_equipments e
            JOIN labs l ON e.lab_id = l.lab_id
            WHERE e.equipment_id = ?
        ");
        $stmt->execute([$equipment_id]);
        $equipment = $stmt->fetch();
        
        if (!$equipment) return false;
        
        // Get all Lab TOs
        $stmt = $pdo->prepare("SELECT user_id FROM users WHERE role = 'lab_to'");
        $stmt->execute();
        $lab_tos = $stmt->fetchAll();
        
        $title = "Equipment " . ucfirst($action);
        $message = "Equipment '{$equipment['equipment_name']}' in {$equipment['lab_type']} has been {$action}.";
        
        foreach ($lab_tos as $lab_to) {
            createNotification(
                $pdo,
                $lab_to['user_id'],
                'equipment_updated',
                $title,
                $message,
                $equipment_id
            );
        }
        
        return true;
    } catch (Exception $e) {
        error_log("Failed to notify about equipment update: " . $e->getMessage());
        return false;
    }
}

// Notify instructors about schedule updates
function notifyInstructorsAboutScheduleUpdate($pdo, $lab_id) {
    try {
        // Get lab details
        $stmt = $pdo->prepare("SELECT type FROM labs WHERE lab_id = ?");
        $stmt->execute([$lab_id]);
        $lab = $stmt->fetch();
        
        if (!$lab) return false;
        
        // Get all instructors who have bookings for this lab
        $stmt = $pdo->prepare("
            SELECT DISTINCT u.user_id, u.name
            FROM users u
            JOIN lab_bookings b ON u.user_id = b.instructor_id
            WHERE b.lab_id = ? AND b.status = 'approved' AND b.date >= CURDATE()
        ");
        $stmt->execute([$lab_id]);
        $instructors = $stmt->fetchAll();
        
        $title = "Lab Schedule Updated";
        $message = "The schedule for {$lab['type']} has been updated. Please check for any changes.";
        
        foreach ($instructors as $instructor) {
            createNotification(
                $pdo,
                $instructor['user_id'],
                'schedule_updated',
                $title,
                $message,
                $lab_id
            );
        }
        
        return true;
    } catch (Exception $e) {
        error_log("Failed to notify about schedule update: " . $e->getMessage());
        return false;
    }
}

// Get notification icon based on type
function getNotificationIcon($type) {
    $icons = [
        'booking_request' => 'fas fa-calendar-plus',
        'booking_approved' => 'fas fa-check-circle',
        'booking_rejected' => 'fas fa-times-circle',
        'equipment_updated' => 'fas fa-tools',
        'lab_updated' => 'fas fa-building',
        'schedule_updated' => 'fas fa-clock',
        'maintenance_required' => 'fas fa-wrench',
        'system' => 'fas fa-info-circle'
    ];
    
    return $icons[$type] ?? 'fas fa-bell';
}

// Get notification color based on type
function getNotificationColor($type) {
    $colors = [
        'booking_request' => '#3b82f6',
        'booking_approved' => '#10b981',
        'booking_rejected' => '#ef4444',
        'equipment_updated' => '#8b5cf6',
        'lab_updated' => '#06b6d4',
        'schedule_updated' => '#f59e0b',
        'maintenance_required' => '#f59e0b',
        'system' => '#6b7280'
    ];
    
    return $colors[$type] ?? '#6b7280';
}


?>



