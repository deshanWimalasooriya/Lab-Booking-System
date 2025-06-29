<?php
require_once '../config/config.php';
require_once '../includes/notification_functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'get_notifications':
        try {
            $notifications = getUserNotifications($pdo, $_SESSION['user_id'], 15);
            $unread_count = getUnreadNotificationCount($pdo, $_SESSION['user_id']);
            
            $html = '';
            if (empty($notifications)) {
                $html = '<li class="notification-item no-notifications">
                            <div class="notification-content">
                                <i class="fas fa-bell-slash"></i>
                                <p>No notifications yet</p>
                            </div>
                         </li>';
            } else {
                foreach ($notifications as $notification) {
                    $icon = getNotificationIcon($notification['type']);
                    $color = getNotificationColor($notification['type']);
                    $time_ago = timeAgo($notification['created_at']);
                    $read_class = $notification['is_read'] ? 'read' : 'unread';
                    
                    $html .= '<li class="notification-item ' . $read_class . '" data-id="' . $notification['notification_id'] . '">
                                <div class="notification-icon" style="color: ' . $color . '">
                                    <i class="' . $icon . '"></i>
                                </div>
                                <div class="notification-content">
                                    <h4>' . htmlspecialchars($notification['title']) . '</h4>
                                    <p>' . htmlspecialchars($notification['message']) . '</p>
                                    <small>' . $time_ago . '</small>
                                </div>
                              </li>';
                }
            }
            
            echo json_encode([
                'notifications' => $html,
                'unread_count' => $unread_count
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'error' => 'Database error: ' . $e->getMessage(),
                'notifications' => '<li class="notification-item no-notifications">
                                     <div class="notification-content">
                                         <i class="fas fa-exclamation-triangle"></i>
                                         <p>Error loading notifications</p>
                                         <small>' . $e->getMessage() . '</small>
                                     </div>
                                   </li>',
                'unread_count' => 0
            ]);
        }
        break;
        
    case 'mark_as_read':
        $notification_id = $_POST['notification_id'] ?? 0;
        $success = markNotificationAsRead($pdo, $notification_id, $_SESSION['user_id']);
        echo json_encode(['success' => $success]);
        break;
        
    case 'mark_all_read':
        $success = markAllNotificationsAsRead($pdo, $_SESSION['user_id']);
        echo json_encode(['success' => $success]);
        break;
        
    // REMOVE THIS ENTIRE CASE:
    /*
    case 'create_test':
        // Test notification creation
        $result = createNotification(
            $pdo,
            $_SESSION['user_id'],
            'system',
            'Test Notification',
            'This is a test notification created at ' . date('Y-m-d H:i:s'),
            null
        );
        echo json_encode(['success' => $result, 'message' => 'Test notification created']);
        break;
    */
        
    default:
        echo json_encode(['error' => 'Invalid action']);
}

function timeAgo($datetime) {
    $time = time() - strtotime($datetime);
    
    if ($time < 60) return 'Just now';
    if ($time < 3600) return floor($time/60) . ' min ago';
    if ($time < 86400) return floor($time/3600) . ' hr ago';
    if ($time < 2592000) return floor($time/86400) . ' days ago';
    
    return date('M d, Y', strtotime($datetime));
}
?>
