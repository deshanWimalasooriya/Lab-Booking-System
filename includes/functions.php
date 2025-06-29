<?php
// Authentication functions
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        // Determine the correct path to login.php based on current location
        $login_path = 'login.php';
        if (strpos($_SERVER['REQUEST_URI'], '/pages/') !== false) {
            $login_path = '../../login.php';
        }
        header('Location: ' . $login_path);
        exit;
    }
}

function hasRole($role) {
    return isset($_SESSION['role']) && $_SESSION['role'] === $role;
}

// Secure logout function
function secureLogout() {
    global $pdo;
    
    if (isset($_SESSION['user_id'])) {
        try {
            logUserActivity($_SESSION['user_id'], 'logout');
        } catch(Exception $e) {
            // Continue with logout even if logging fails
        }
    }
    
    // Destroy session
    session_unset();
    session_destroy();
    
    // Clear session cookie
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }
}

// Dashboard statistics
function getDashboardStats($pdo) {
    $stats = [];
    
    try {
        // Total labs
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM labs");
        $stats['total_labs'] = $stmt->fetch()['count'];
        
        // Active bookings
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM lab_bookings WHERE status = 'approved' AND date >= CURDATE()");
        $stats['active_bookings'] = $stmt->fetch()['count'];
        
        // Total equipment
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM lab_equipments");
        $stats['total_equipment'] = $stmt->fetch()['count'];
        
        // Available labs
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM labs WHERE availability = 'available'");
        $stats['available_labs'] = $stmt->fetch()['count'];
    } catch(Exception $e) {
        // Return default values if query fails
        $stats = [
            'total_labs' => 0,
            'active_bookings' => 0,
            'total_equipment' => 0,
            'available_labs' => 0
        ];
    }
    
    return $stats;
}

// User activity logging
function logUserActivity($user_id, $action, $lab_id = null) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("INSERT INTO user_log (user_id, lab_id, action, time_stamp) VALUES (?, ?, ?, NOW())");
        $stmt->execute([$user_id, $lab_id, $action]);
    } catch(Exception $e) {
        // Fail silently - logging shouldn't break the application
    }
}

// Get user bookings
function getUserBookings($pdo, $user_id) {
    try {
        $stmt = $pdo->prepare("
            SELECT b.*, l.type as lab_type 
            FROM lab_bookings b 
            JOIN labs l ON b.lab_id = l.lab_id 
            WHERE b.instructor_id = ? 
            ORDER BY b.date DESC, b.start_time DESC
        ");
        $stmt->execute([$user_id]);
        return $stmt->fetchAll();
    } catch(Exception $e) {
        return [];
    }
}

// Get all labs
function getAllLabs($pdo) {
    try {
        $stmt = $pdo->query("SELECT * FROM labs ORDER BY type");
        return $stmt->fetchAll();
    } catch(Exception $e) {
        return [];
    }
}

// Get equipment by lab
function getEquipmentByLab($pdo, $lab_id) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM lab_equipments WHERE lab_id = ? ORDER BY name");
        $stmt->execute([$lab_id]);
        return $stmt->fetchAll();
    } catch(Exception $e) {
        return [];
    }
}

// Format status badge
function getStatusBadge($status) {
    $badges = [
        'available' => 'badge-success',
        'unavailable' => 'badge-danger',
        'booked' => 'badge-warning',
        'approved' => 'badge-success',
        'pending' => 'badge-warning',
        'rejected' => 'badge-danger',
        'working' => 'badge-success',
        'under repair' => 'badge-warning',
        'not working' => 'badge-danger'
    ];
    
    $class = $badges[$status] ?? 'badge-info';
    return "<span class='badge {$class}'>" . htmlspecialchars($status) . "</span>";
}

// Session timeout check (optional)
function checkSessionTimeout($timeout = 3600) { // 1 hour default
    if (isset($_SESSION['last_activity'])) {
        if (time() - $_SESSION['last_activity'] > $timeout) {
            secureLogout();
            header('Location: login.php?message=session_expired');
            exit;
        }
    }
    $_SESSION['last_activity'] = time();
}
?>
