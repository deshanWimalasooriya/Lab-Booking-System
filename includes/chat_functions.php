<?php
// Get all users for chat (excluding current user)
function getChatUsers($pdo, $current_user_id) {
    try {
        $stmt = $pdo->prepare("
            SELECT user_id, name, email, role 
            FROM users 
            WHERE user_id != ? 
            ORDER BY name
        ");
        $stmt->execute([$current_user_id]);
        return $stmt->fetchAll();
    } catch (Exception $e) {
        return [];
    }
}

// Get direct messages between two users
function getDirectMessages($pdo, $user1_id, $user2_id, $limit = 50) {
    try {
        $stmt = $pdo->prepare("
            SELECT cm.*, u.name as sender_name 
            FROM chat_messages cm
            JOIN users u ON cm.sender_id = u.user_id
            WHERE ((cm.sender_id = ? AND cm.receiver_id = ?) 
                   OR (cm.sender_id = ? AND cm.receiver_id = ?))
            AND cm.room_type = 'direct'
            ORDER BY cm.created_at DESC
            LIMIT ?
        ");
        $stmt->execute([$user1_id, $user2_id, $user2_id, $user1_id, $limit]);
        return array_reverse($stmt->fetchAll());
    } catch (Exception $e) {
        return [];
    }
}

// Send a message
function sendMessage($pdo, $sender_id, $receiver_id, $message, $room_type = 'direct') {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO chat_messages (sender_id, receiver_id, message, room_type, created_at) 
            VALUES (?, ?, ?, ?, NOW())
        ");
        return $stmt->execute([$sender_id, $receiver_id, $message, $room_type]);
    } catch (Exception $e) {
        return false;
    }
}

// Get unread message count
function getUnreadMessageCount($pdo, $user_id) {
    try {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count 
            FROM chat_messages 
            WHERE receiver_id = ? AND is_read = 0
        ");
        $stmt->execute([$user_id]);
        return $stmt->fetch()['count'];
    } catch (Exception $e) {
        return 0;
    }
}

// Mark messages as read
function markMessagesAsRead($pdo, $sender_id, $receiver_id) {
    try {
        $stmt = $pdo->prepare("
            UPDATE chat_messages 
            SET is_read = 1 
            WHERE sender_id = ? AND receiver_id = ?
        ");
        return $stmt->execute([$sender_id, $receiver_id]);
    } catch (Exception $e) {
        return false;
    }
}

// Get recent conversations
function getRecentConversations($pdo, $user_id, $limit = 10) {
    try {
        $stmt = $pdo->prepare("
            SELECT 
                CASE 
                    WHEN cm.sender_id = ? THEN cm.receiver_id 
                    ELSE cm.sender_id 
                END as other_user_id,
                u.name as other_user_name,
                u.role as other_user_role,
                cm.message as last_message,
                cm.created_at as last_message_time,
                COUNT(CASE WHEN cm.receiver_id = ? AND cm.is_read = 0 THEN 1 END) as unread_count
            FROM chat_messages cm
            JOIN users u ON (
                CASE 
                    WHEN cm.sender_id = ? THEN cm.receiver_id 
                    ELSE cm.sender_id 
                END = u.user_id
            )
            WHERE (cm.sender_id = ? OR cm.receiver_id = ?)
            AND cm.room_type = 'direct'
            GROUP BY other_user_id
            ORDER BY cm.created_at DESC
            LIMIT ?
        ");
        $stmt->execute([$user_id, $user_id, $user_id, $user_id, $user_id, $limit]);
        return $stmt->fetchAll();
    } catch (Exception $e) {
        return [];
    }
}

?>


