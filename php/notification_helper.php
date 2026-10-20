<?php
// notification_helper.php
require_once 'config.php';

/**
 * Creates a notification if the actor is not the owner.
 */
function createNotification($pdo, $recipient_id, $actor_id, $type, $post_id = null, $comment_id = null) {
    // Prevent notifying users of their own actions
    if ((int)$recipient_id === (int)$actor_id) {
        return false;
    }

    try {
        $settings_stmt = $pdo->prepare("SELECT notifications_enabled FROM users WHERE id = ?");
        $settings_stmt->execute([$recipient_id]);
        if (!(bool)$settings_stmt->fetchColumn()) {
            return false;
        }

        $stmt = $pdo->prepare("INSERT INTO notifications (user_id, actor_id, type, post_id, comment_id) VALUES (?, ?, ?, ?, ?)");
        return $stmt->execute([$recipient_id, $actor_id, $type, $post_id, $comment_id]);
    } catch (\PDOException $e) {
        error_log("Notification Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Gets total count of unread notifications for a user.
 */
function getUnreadNotificationCount($pdo, $user_id) {
    try {
        $stmt = $pdo->prepare("
            SELECT COUNT(*)
            FROM notifications n
            JOIN users u ON u.id = n.user_id
            WHERE n.user_id = ? AND n.is_read = 0 AND u.notifications_enabled = 1
        ");
        $stmt->execute([$user_id]);
        return (int)$stmt->fetchColumn();
    } catch (\PDOException $e) {
        return 0;
    }
}