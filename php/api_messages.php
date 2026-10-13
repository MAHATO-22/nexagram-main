<?php
// api_messages.php - Direct Message API (fetch / send / accept / block / unblock)
session_start();
require_once 'config.php';
require_once 'notification_helper.php';
require_once 'message_helper.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'login_required']);
    exit;
}

$me = (int)$_SESSION['user_id'];
$action = $_GET['action'] ?? ($_POST['action'] ?? 'fetch');

function sanitizeMessage($row) {
    $imagePath = $row['image_path'] ?? null;

    return [
        'id'           => (int)$row['id'],
        'sender_id'    => (int)$row['sender_id'],
        'receiver_id'  => (int)$row['receiver_id'],
        'message_text' => $row['message_text'],
        'image_path'   => empty($imagePath) ? null : (strpos($imagePath, 'http') === 0 ? $imagePath : '../' . ltrim($imagePath, '/')),
        'created_at'   => $row['created_at'],
    ];
}

if ($action === 'fetch') {
    $peer = (int)($_GET['user'] ?? 0);
    $since = (int)($_GET['since'] ?? 0);
    if ($peer <= 0 || $peer === $me) { echo json_encode(['messages' => [], 'state' => 'ok']); exit; }

    $state = threadState($pdo, $me, $peer);
    if ($state === 'blocked_mine' || $state === 'blocked_peer') {
        echo json_encode(['messages' => [], 'state' => $state]);
        exit;
    }

    $stmt = $pdo->prepare("SELECT * FROM messages WHERE sender_id = ? AND receiver_id = ? AND id > ? ORDER BY id ASC");
    $stmt->execute([$peer, $me, $since]);
    $rows = $stmt->fetchAll();

    // Pending message requests stay unread until replied to / blocked (badge stays)
    if ($rows) {
        $upd = $pdo->prepare("UPDATE messages SET is_read = 1 WHERE sender_id = ? AND receiver_id = ? AND is_read = 0 AND is_request = 0");
        $upd->execute([$peer, $me]);
    }

    echo json_encode(['messages' => array_map('sanitizeMessage', $rows), 'state' => $state]);
    exit;
}

if ($action === 'accept') {
    // Reply to a message request: open the thread for both sides
    $peer = (int)($_POST['user'] ?? 0);
    if ($peer <= 0 || $peer === $me) { echo json_encode(['error' => 'invalid_peer']); exit; }
    if (blockState($pdo, $me, $peer)) { echo json_encode(['error' => 'blocked']); exit; }

    $upd = $pdo->prepare("UPDATE messages SET is_request = 0, is_read = 1 WHERE sender_id = ? AND receiver_id = ? AND is_request = 1");
    $upd->execute([$peer, $me]);
    $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND actor_id = ? AND type = 'message'")->execute([$me, $peer]);
    echo json_encode(['state' => 'ok']);
    exit;
}

if ($action === 'block') {
    $peer = (int)($_POST['user'] ?? 0);
    if ($peer <= 0 || $peer === $me) { echo json_encode(['error' => 'invalid_peer']); exit; }

    $ins = $pdo->prepare("INSERT IGNORE INTO user_blocks (blocker_id, blocked_id) VALUES (?, ?)");
    $ins->execute([$me, $peer]);

    // Clear their unread messages + notifications from my badges
    $pdo->prepare("UPDATE messages SET is_read = 1 WHERE sender_id = ? AND receiver_id = ? AND is_read = 0")->execute([$peer, $me]);
    $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND actor_id = ? AND type = 'message'")->execute([$me, $peer]);

    echo json_encode(['state' => 'blocked_mine']);
    exit;
}

if ($action === 'unblock') {
    $peer = (int)($_POST['user'] ?? 0);
    if ($peer <= 0 || $peer === $me) { echo json_encode(['error' => 'invalid_peer']); exit; }

    $del = $pdo->prepare("DELETE FROM user_blocks WHERE blocker_id = ? AND blocked_id = ?");
    $del->execute([$me, $peer]);
    echo json_encode(['state' => threadState($pdo, $me, $peer)]);
    exit;
}

if ($action === 'send') {
    $to = (int)($_POST['to'] ?? 0);
    $text = trim($_POST['message_text'] ?? '');
    $image_destination = null;

    if ($to <= 0 || $to === $me) { echo json_encode(['error' => 'invalid_recipient']); exit; }

    $blocked = blockState($pdo, $me, $to);
    if ($blocked === 'mine') { echo json_encode(['error' => 'you_blocked_this_user']); exit; }
    if ($blocked === 'peer') { echo json_encode(['error' => 'blocked_by_peer']); exit; }

    // They sent a message request: I must Reply (accept) or Block first
    $pend = $pdo->prepare("SELECT 1 FROM messages WHERE sender_id = ? AND receiver_id = ? AND is_request = 1 LIMIT 1");
    $pend->execute([$to, $me]);
    if ($pend->fetchColumn()) { echo json_encode(['error' => 'request_awaiting_reply']); exit; }

    // Photo / image sharing
    if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
        $file = $_FILES['image'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

        if (!in_array($ext, $allowed)) { echo json_encode(['error' => 'invalid_file_type']); exit; }
        if ($file['error'] !== 0 || $file['size'] >= 10000000) { echo json_encode(['error' => 'upload_failed_or_too_large']); exit; }

        $image_destination = 'uploads/' . uniqid('MSG_', true) . '.' . $ext;
        if (!move_uploaded_file($file['tmp_name'], __DIR__ . '/../' . $image_destination)) { echo json_encode(['error' => 'move_failed']); exit; }
    }

    if (empty($text) && empty($image_destination)) { echo json_encode(['error' => 'empty_message']); exit; }

    // Non-friend with no accepted message yet => message request
    $is_request = 0;
    if (!isFriends($pdo, $me, $to)) {
        $acc = $pdo->prepare("SELECT 1 FROM messages WHERE ((sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)) AND is_request = 0 LIMIT 1");
        $acc->execute([$me, $to, $to, $me]);
        $is_request = $acc->fetchColumn() ? 0 : 1;
    }

    $stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, message_text, image_path, is_request) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$me, $to, $text, $image_destination, $is_request]);

    $id = (int)$pdo->lastInsertId();
    $row = $pdo->prepare("SELECT * FROM messages WHERE id = ?");
    $row->execute([$id]);
    $message = sanitizeMessage($row->fetch());

    // Notify receiver once per unread thread (avoid flooding)
    $has_unread = $pdo->prepare("SELECT id FROM notifications WHERE user_id = ? AND actor_id = ? AND type = 'message' AND is_read = 0");
    $has_unread->execute([$to, $me]);
    if (!$has_unread->fetch()) {
        createNotification($pdo, $to, $me, 'message');
    }

    echo json_encode(['message' => $message, 'state' => threadState($pdo, $me, $to)]);
    exit;
}

echo json_encode(['error' => 'unknown_action']);
