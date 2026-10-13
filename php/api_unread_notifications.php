<?php
// api_unread_notifications.php
session_start();
require_once 'config.php';
require_once 'notification_helper.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['unread_count' => 0, 'msg_count' => 0]);
    exit;
}

$unread_count = getUnreadNotificationCount($pdo, $_SESSION['user_id']);

$msg_stmt = $pdo->prepare("SELECT COUNT(*) FROM messages WHERE receiver_id = ? AND is_read = 0");
$msg_stmt->execute([$_SESSION['user_id']]);
$msg_count = (int)$msg_stmt->fetchColumn();

echo json_encode(['unread_count' => $unread_count, 'msg_count' => $msg_count]);