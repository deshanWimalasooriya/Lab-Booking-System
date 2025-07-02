<?php
require_once '../config/config.php';
require_once '../includes/chat_functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'get_users':
        $users = getChatUsers($pdo, $_SESSION['user_id']);
        echo json_encode(['users' => $users]);
        break;
        
    case 'get_messages':
        $other_user_id = $_POST['user_id'] ?? 0;
        if ($other_user_id) {
            $messages = getDirectMessages($pdo, $_SESSION['user_id'], $other_user_id);
            markMessagesAsRead($pdo, $other_user_id, $_SESSION['user_id']);
            echo json_encode(['messages' => $messages]);
        } else {
            echo json_encode(['error' => 'User ID required']);
        }
        break;
        
    case 'send_message':
        $receiver_id = $_POST['receiver_id'] ?? 0;
        $message = trim($_POST['message'] ?? '');
        
        if ($receiver_id && $message) {
            $success = sendMessage($pdo, $_SESSION['user_id'], $receiver_id, $message);
            echo json_encode(['success' => $success]);
        } else {
            echo json_encode(['error' => 'Receiver ID and message required']);
        }
        break;
        
    case 'get_conversations':
        $conversations = getRecentConversations($pdo, $_SESSION['user_id']);
        echo json_encode(['conversations' => $conversations]);
        break;
        
    case 'get_unread_count':
        $count = getUnreadMessageCount($pdo, $_SESSION['user_id']);
        echo json_encode(['unread_count' => $count]);
        break;
        
    default:
        echo json_encode(['error' => 'Invalid action']);
}
?>
