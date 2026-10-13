<?php
// messages.php - Direct Messages (chat, photo share, emoji, unread indicators)
session_start();
require_once 'config.php';
require_once 'lang.php';
require_once 'message_helper.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$me = (int)$_SESSION['user_id'];

function resolveImagePath($fileName) {
    if (empty($fileName)) return null;
    if (strpos($fileName, 'http') === 0) return $fileName;
    $cleanName = ltrim($fileName, '/');
    if (file_exists(__DIR__ . '/../' . $cleanName)) return '../' . $cleanName;
    if (file_exists(__DIR__ . '/../uploads/' . $cleanName)) return '../uploads/' . $cleanName;
    return (strpos($cleanName, 'uploads/') === 0) ? '../' . $cleanName : '../uploads/' . $cleanName;
}

// Blocked threads (either direction) are hidden from my conversation list
$blocked_peers = [];
$blk_stmt = $pdo->prepare("SELECT blocker_id, blocked_id FROM user_blocks WHERE blocker_id = ? OR blocked_id = ?");
$blk_stmt->execute([$me, $me]);
foreach ($blk_stmt->fetchAll() as $b) {
    $other = ((int)$b['blocker_id'] === $me) ? (int)$b['blocked_id'] : (int)$b['blocker_id'];
    $blocked_peers[$other] = ((int)$b['blocker_id'] === $me) ? 'mine' : 'peer';
}

// All users except me
$users_stmt = $pdo->prepare("SELECT id, username, profile_image FROM users WHERE id <> ? ORDER BY username ASC");
$users_stmt->execute([$me]);
$all_users = $users_stmt->fetchAll();
$users_by_id = [];
foreach ($all_users as $u) $users_by_id[$u['id']] = $u;

// My recent messages -> per-peer last message + pending request flag
$msg_stmt = $pdo->prepare("SELECT id, sender_id, receiver_id, message_text, image_path, is_request, created_at FROM messages WHERE sender_id = ? OR receiver_id = ? ORDER BY id DESC LIMIT 1000");
$msg_stmt->execute([$me, $me]);
$recent = $msg_stmt->fetchAll();

// peer_id => ['last'=>row, 'unread'=>n, 'request'=>bool]  (rows are DESC, first seen = newest)
$conversations = [];
foreach ($recent as $m) {
    $peer = ((int)$m['sender_id'] === $me) ? (int)$m['receiver_id'] : (int)$m['sender_id'];
    if (!isset($conversations[$peer])) $conversations[$peer] = ['last' => null, 'unread' => 0, 'request' => false];
    if ($conversations[$peer]['last'] === null) $conversations[$peer]['last'] = $m;
    if ((int)$m['sender_id'] === $peer && (int)$m['is_request'] === 1) $conversations[$peer]['request'] = true;
}

// Unread counts (messages sent TO me, unread)
$unread_stmt = $pdo->prepare("SELECT sender_id, COUNT(*) c FROM messages WHERE receiver_id = ? AND is_read = 0 GROUP BY sender_id");
$unread_stmt->execute([$me]);
$unread_map = [];
foreach ($unread_stmt->fetchAll() as $r) $unread_map[(int)$r['sender_id']] = (int)$r['c'];
foreach ($conversations as $pid => $c) {
    $conversations[$pid]['unread'] = $unread_map[$pid] ?? 0;
}

// Open conversation
$peer_id = (int)($_GET['user'] ?? 0);
$peer = null;
$chat_messages = [];
$state = 'ok';
if ($peer_id > 0 && isset($users_by_id[$peer_id])) {
    $peer = $users_by_id[$peer_id];
    $state = threadState($pdo, $me, $peer_id);

    // Pending message requests stay unread until replied to / blocked
    if ($state !== 'blocked_mine' && $state !== 'blocked_peer') {
        $mark = $pdo->prepare("UPDATE messages SET is_read = 1 WHERE sender_id = ? AND receiver_id = ? AND is_read = 0 AND is_request = 0");
        $mark->execute([$peer_id, $me]);
    }

    $chat_stmt = $pdo->prepare("SELECT * FROM messages WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?) ORDER BY id ASC");
    $chat_stmt->execute([$me, $peer_id, $peer_id, $me]);
    $chat_messages = $chat_stmt->fetchAll();
}

// Conversation list order: has messages first (newest), then the rest (blocked hidden)
$others = [];
foreach ($all_users as $u) {
    if (isset($blocked_peers[$u['id']])) continue;
    $others[] = [
        'user' => $u,
        'last' => $conversations[$u['id']]['last'] ?? null,
        'unread' => $conversations[$u['id']]['unread'] ?? 0,
        'request' => $conversations[$u['id']]['request'] ?? false,
    ];
}
usort($others, function ($a, $b) {
    $la = $a['last'] ? (int)$a['last']['id'] : 0;
    $lb = $b['last'] ? (int)$b['last']['id'] : 0;
    if ($la !== $lb) return $lb - $la;
    return strcmp($a['user']['username'], $b['user']['username']);
});

function previewText($m) {
    if (!$m) return '';
    if (!empty($m['message_text'])) return $m['message_text'];
    return '📷 Photo';
}
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($current_lang, ENT_QUOTES, 'UTF-8'); ?>" dir="<?php echo htmlspecialchars($text_direction, ENT_QUOTES, 'UTF-8'); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nexagram • Messages</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="../js/theme-init.js"></script>
    <link rel="stylesheet" href="../css/messages.css">
    <link rel="stylesheet" href="../css/utilities.css">
    <link rel="stylesheet" href="../css/more-menu.css">
</head>
<body>

    <nav class="sidebar">
        <a href="index.php" class="logo logo-link">Nexagram</a>
        <ul class="nav-menu">
            <li class="nav-item"><a href="index.php"><i class="fa-solid fa-house"></i> <?php echo __('nav_home'); ?></a></li>
            <li class="nav-item">
                <a href="notifications.php">
                    <i class="fa-solid fa-bell"></i> <?php echo htmlspecialchars(__('nav_notifications'), ENT_QUOTES, 'UTF-8'); ?>
                    <span class="notif-badge is-hidden" id="navNotifBadge">0</span>
                </a>
            </li>
            <li class="nav-item"><a href="friends.php"><i class="fa-solid fa-user-group"></i> <?php echo __('nav_friends'); ?></a></li>
            <li class="nav-item"><a href="users.php"><i class="fa-solid fa-users"></i> <?php echo __('nav_users'); ?></a></li>
            <li class="nav-item active">
                <a href="messages.php">
                    <i class="fa-solid fa-paper-plane"></i> <?php echo __('nav_messages'); ?>
                    <span class="notif-badge is-hidden" id="navMsgBadge">0</span>
                </a>
            </li>
            <li class="nav-item"><a href="create_post.php"><i class="fa-solid fa-square-plus"></i> <?php echo __('nav_create'); ?></a></li>
            <li class="nav-item"><a href="profile.php"><i class="fa-solid fa-user"></i> <?php echo __('nav_profile'); ?></a></li>
            <li class="nav-item more-menu-item">
                <button type="button" class="more-trigger" id="moreToggle" aria-expanded="false" aria-controls="moreMenu">
                    <i class="fa-solid fa-ellipsis"></i>
                    <span><?php echo __('more'); ?></span>
                    <i class="fa-solid fa-chevron-down lang-arrow"></i>
                </button>
                <div class="lang-menu" id="moreMenu">
                    <a href="settings.php"><i class="fa-solid fa-gear"></i> <?php echo __('nav_settings'); ?></a>
                    <button type="button" class="theme-btn" id="theme-toggle">
                        <i class="fa-solid fa-sun" id="theme-icon"></i>
                        <span id="theme-text"><?php echo __('light_mode'); ?></span>
                    </button>
                    <a href="logout.php" class="logout-item"><i class="fa-solid fa-right-from-bracket"></i><?php echo __('nav_logout'); ?></a>
                </div>
            </li>
        </ul>
    </nav>

    <main class="main-content">
        <div class="chat-layout">

            <section class="conv-panel">
                <div class="panel-title"><?php echo htmlspecialchars(__('nav_messages'), ENT_QUOTES, 'UTF-8'); ?></div>
                <div class="conv-list">
                    <?php foreach ($others as $row): $u = $row['user']; ?>
                        <a href="messages.php?user=<?php echo $u['id']; ?>"
                           class="conv-item <?php echo ($peer_id === (int)$u['id']) ? 'active' : ''; ?>">
                            <?php $av = resolveImagePath($u['profile_image']); ?>
                            <?php if ($av): ?>
                                <img src="<?php echo htmlspecialchars($av); ?>" class="conv-avatar">
                            <?php else: ?>
                                <div class="avatar-placeholder"><?php echo mb_substr($u['username'], 0, 1, 'UTF-8'); ?></div>
                            <?php endif; ?>
                            <div class="conv-body">
                                <div class="conv-name"><?php echo htmlspecialchars($u['username']); ?></div>
                                <div class="conv-preview"><?php echo $row['request'] ? '📩 ' : ''; ?><?php echo htmlspecialchars(previewText($row['last'])); ?></div>
                            </div>
                            <?php if ($row['unread'] > 0): ?>
                                <span class="conv-unread"><?php echo $row['unread']; ?></span>
                            <?php endif; ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </section>

            <section class="chat-panel">
                <?php if ($peer): ?>
                    <div class="chat-header">
                        <?php $pav = resolveImagePath($peer['profile_image']); ?>
                        <a class="chat-profile-link" href="profile.php?id=<?php echo (int)$peer['id']; ?>" aria-label="<?php echo htmlspecialchars(__('view_profile'), ENT_QUOTES, 'UTF-8'); ?>: <?php echo htmlspecialchars($peer['username'], ENT_QUOTES, 'UTF-8'); ?>">
                            <?php if ($pav): ?>
                                <img src="<?php echo htmlspecialchars($pav); ?>" class="conv-avatar" alt="">
                            <?php else: ?>
                                <div class="avatar-placeholder"><?php echo mb_substr($peer['username'], 0, 1, 'UTF-8'); ?></div>
                            <?php endif; ?>
                            <span class="name"><?php echo htmlspecialchars($peer['username']); ?></span>
                        </a>
                        <div class="header-actions">
                            <?php if ($state === 'blocked_mine'): ?>
                                <button type="button" class="banner-btn ghost" data-action="unblock"><i class="fa-solid fa-unlock"></i> <?php echo htmlspecialchars(__('unblock'), ENT_QUOTES, 'UTF-8'); ?></button>
                            <?php elseif ($state !== 'blocked_peer'): ?>
                                <button type="button" class="banner-btn danger" data-action="block"><i class="fa-solid fa-ban"></i> <?php echo htmlspecialchars(__('block'), ENT_QUOTES, 'UTF-8'); ?></button>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php if ($state !== 'ok'): ?>
                    <div class="thread-banner banner-<?php echo $state; ?>">
                        <?php if ($state === 'request'): ?>
                            <span>📩 <?php echo htmlspecialchars($peer['username']); ?><?php echo htmlspecialchars(__('message_request_prompt'), ENT_QUOTES, 'UTF-8'); ?></span>
                            <div class="banner-actions">
                                <button type="button" class="banner-btn" data-action="accept"><?php echo htmlspecialchars(__('message_request_reply'), ENT_QUOTES, 'UTF-8'); ?></button>
                                <button type="button" class="banner-btn danger" data-action="block"><?php echo htmlspecialchars(__('block'), ENT_QUOTES, 'UTF-8'); ?></button>
                            </div>
                        <?php elseif ($state === 'waiting'): ?>
                            <span>⏳ <?php echo htmlspecialchars(__('message_request_waiting'), ENT_QUOTES, 'UTF-8'); ?> <?php echo htmlspecialchars($peer['username']); ?></span>
                            <div class="banner-actions">
                                <button type="button" class="banner-btn danger" data-action="block"><?php echo htmlspecialchars(__('cancel_and_block'), ENT_QUOTES, 'UTF-8'); ?></button>
                            </div>
                        <?php elseif ($state === 'blocked_mine'): ?>
                            <span>🚫 <?php echo htmlspecialchars(__('blocked_user_notice'), ENT_QUOTES, 'UTF-8'); ?> <?php echo htmlspecialchars($peer['username']); ?></span>
                            <div class="banner-actions">
                                <button type="button" class="banner-btn ghost" data-action="unblock"><?php echo htmlspecialchars(__('unblock'), ENT_QUOTES, 'UTF-8'); ?></button>
                            </div>
                        <?php else: ?>
                            <span>🚫 <?php echo htmlspecialchars($peer['username']); ?>: <?php echo htmlspecialchars(__('blocked_by_user_notice'), ENT_QUOTES, 'UTF-8'); ?></span>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                    <div class="chat-body" id="chatBody">
                        <?php foreach ($chat_messages as $m): ?>
                            <?php $mine = ((int)$m['sender_id'] === $me); ?>
                            <div class="bubble-row <?php echo $mine ? 'mine' : 'theirs'; ?>" data-id="<?php echo (int)$m['id']; ?>">
                                <div class="bubble">
                                    <?php if (!empty($m['message_text'])): ?>
                                        <?php echo nl2br(htmlspecialchars($m['message_text'])); ?>
                                    <?php endif; ?>
                                    <?php if (!empty($m['image_path'])): ?>
                                        <img src="<?php echo htmlspecialchars(resolveImagePath($m['image_path'])); ?>" class="msg-image" data-ui-action="open-message-lightbox">
                                    <?php endif; ?>
                                    <span class="time"><?php echo date('M d, H:i', strtotime($m['created_at'])); ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <?php $can_send = in_array($state, ['ok', 'waiting']); ?>
                    <?php if ($can_send): ?>
                    <div class="emoji-panel" id="emojiPanel"></div>

                    <form class="chat-input" id="msgForm" enctype="multipart/form-data">
                        <label class="file-label" title="<?php echo htmlspecialchars(__('share_photo'), ENT_QUOTES, 'UTF-8'); ?>">
                            <i class="fa-solid fa-image"></i>
                            <input type="file" id="imageInput" name="image" accept="image/*">
                        </label>
                        <span class="attach-name" id="attachName"></span>
                        <button type="button" class="icon-btn" id="emojiBtn" title="<?php echo htmlspecialchars(__('emoji'), ENT_QUOTES, 'UTF-8'); ?>"><i class="fa-solid fa-face-smile"></i></button>
                        <input type="text" id="msgText" name="message_text" placeholder="<?php echo htmlspecialchars(__('message_placeholder'), ENT_QUOTES, 'UTF-8'); ?>" autocomplete="off">
                        <button type="submit" class="icon-btn send-btn" title="<?php echo htmlspecialchars(__('send'), ENT_QUOTES, 'UTF-8'); ?>"><i class="fa-solid fa-paper-plane"></i></button>
                    </form>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="chat-empty"><?php echo htmlspecialchars(__('select_conversation'), ENT_QUOTES, 'UTF-8'); ?></div>
                <?php endif; ?>
            </section>
        </div>
    </main>

    <div class="lightbox" id="lightbox" data-ui-action="close-message-lightbox"><img id="lightboxImg" src=""></div>

    <script src="../js/badge.js"></script>
    <div id="page-config" hidden data-config="<?php echo htmlspecialchars(json_encode(['values' => [$me, (int)$peer_id, $state, $chat_messages ? (int)end($chat_messages)['id'] : 0, __('dark_mode'), __('light_mode'), __('message_request_reply_or_block'), __('unblock_to_send_message'), __('cannot_message_blocked_user'), __('enter_message_or_photo'), __('message_send_failed')]], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP), ENT_QUOTES, 'UTF-8'); ?>"></div>
    <script src="../js/page-config.js"></script>
    <script src="../js/messages.js"></script>
    <script src="../js/actions.js"></script>
</body>
</html>
