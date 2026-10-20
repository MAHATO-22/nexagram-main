<?php
// notifications.php
session_start();
require_once 'config.php';
require_once 'lang.php';
require_once 'notification_helper.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$current_user_id = $_SESSION['user_id'];

// Handle "Mark all as read" action
if (isset($_GET['action']) && $_GET['action'] === 'read_all') {
    $mark_stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
    $mark_stmt->execute([$current_user_id]);
    header("Location: notifications.php");
    exit;
}

// Handle single notification mark as read & redirect
if (isset($_GET['read_id'])) {
    $read_id = (int)$_GET['read_id'];
    
    // Fetch notification target
    $n_stmt = $pdo->prepare("SELECT post_id, type, actor_id FROM notifications WHERE id = ? AND user_id = ?");
    $n_stmt->execute([$read_id, $current_user_id]);
    $notif = $n_stmt->fetch();

    if ($notif) {
        $update_stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ?");
        $update_stmt->execute([$read_id]);

        if ($notif['type'] === 'message') {
            header("Location: messages.php?user=" . (int)$notif['actor_id']);
            exit;
        }
        if ($notif['post_id']) {
            header("Location: index.php#post_" . $notif['post_id']);
            exit;
        }
    }
    header("Location: notifications.php");
    exit;
}

// Fetch user notifications
$stmt = $pdo->prepare("
    SELECT n.*, u.username as actor_name, u.profile_image as actor_image 
    FROM notifications n
    LEFT JOIN users u ON n.actor_id = u.id
    WHERE n.user_id = ?
    ORDER BY n.created_at DESC
    LIMIT 50
");
$stmt->execute([$current_user_id]);
$notifications = $stmt->fetchAll();

function resolveImagePath($fileName) {
    if (empty($fileName)) return null;
    if (strpos($fileName, 'http') === 0) return $fileName;
    $cleanName = ltrim($fileName, '/');
    if (file_exists(__DIR__ . '/../' . $cleanName)) return '../' . $cleanName;
    if (file_exists(__DIR__ . '/../uploads/' . $cleanName)) return '../uploads/' . $cleanName;
    return (strpos($cleanName, 'uploads/') === 0) ? '../' . $cleanName : '../uploads/' . $cleanName;
}
?>

<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($current_lang, ENT_QUOTES, 'UTF-8'); ?>" dir="<?php echo htmlspecialchars($text_direction, ENT_QUOTES, 'UTF-8'); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nexagram • <?php echo htmlspecialchars(__('notifications_title'), ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="../js/theme-init.js"></script>
    <link rel="stylesheet" href="../css/notifications.css">
    <link rel="stylesheet" href="../css/utilities.css">
    <link rel="stylesheet" href="../css/more-menu.css">
    <link rel="stylesheet" href="../css/suggestions-widget.css">
</head>
<body>

    <nav class="sidebar">
        <a href="index.php" class="logo logo-link">Nexagram</a>
        <ul class="nav-menu">
            <li class="nav-item"><a href="index.php" aria-label="<?php echo htmlspecialchars(__('nav_home')); ?>" title="<?php echo htmlspecialchars(__('nav_home')); ?>"><i class="fa-solid fa-house"></i><span><?php echo __('nav_home'); ?></span></a></li>
            <li class="nav-item active">
                <a href="notifications.php" aria-label="<?php echo htmlspecialchars(__('nav_notifications'), ENT_QUOTES, 'UTF-8'); ?>" title="<?php echo htmlspecialchars(__('nav_notifications'), ENT_QUOTES, 'UTF-8'); ?>">
                    <i class="fa-solid fa-bell"></i><span><?php echo htmlspecialchars(__('nav_notifications'), ENT_QUOTES, 'UTF-8'); ?></span>
                    <span class="notif-badge is-hidden" id="navNotifBadge">0</span>
                </a>
            </li>
            <li class="nav-item"><a href="friends.php" aria-label="<?php echo htmlspecialchars(__('nav_friends')); ?>" title="<?php echo htmlspecialchars(__('nav_friends')); ?>"><i class="fa-solid fa-user-group"></i><span><?php echo __('nav_friends'); ?></span></a></li>
            <li class="nav-item"><a href="users.php" aria-label="<?php echo htmlspecialchars(__('nav_users')); ?>" title="<?php echo htmlspecialchars(__('nav_users')); ?>"><i class="fa-solid fa-users"></i><span><?php echo __('nav_users'); ?></span></a></li>
            <li class="nav-item">
                <a href="messages.php" aria-label="<?php echo htmlspecialchars(__('nav_messages')); ?>" title="<?php echo htmlspecialchars(__('nav_messages')); ?>">
                    <i class="fa-solid fa-paper-plane"></i><span><?php echo __('nav_messages'); ?></span>
                    <span class="notif-badge is-hidden" id="navMsgBadge">0</span>
                </a>
            </li>
            <li class="nav-item"><a href="create_post.php" aria-label="<?php echo htmlspecialchars(__('nav_create')); ?>" title="<?php echo htmlspecialchars(__('nav_create')); ?>"><i class="fa-solid fa-square-plus"></i><span><?php echo __('nav_create'); ?></span></a></li>
            <li class="nav-item"><a href="profile.php" aria-label="<?php echo htmlspecialchars(__('nav_profile')); ?>" title="<?php echo htmlspecialchars(__('nav_profile')); ?>"><i class="fa-solid fa-user"></i><span><?php echo __('nav_profile'); ?></span></a></li>
            <li class="nav-item more-menu-item">
                <button type="button" class="nav-item more-trigger" id="moreToggle" aria-expanded="false" aria-controls="moreMenu">
                    <i class="fa-solid fa-ellipsis"></i><span><?php echo __('more'); ?></span><i class="fa-solid fa-chevron-down lang-arrow"></i>
                </button>
                <div class="lang-menu" id="moreMenu">
                    <a href="settings.php"><i class="fa-solid fa-gear"></i> <?php echo __('nav_settings'); ?></a>
                    <button type="button" class="theme-btn" id="theme-toggle">
                        <i class="fa-solid fa-moon" id="theme-icon"></i>
                        <span id="theme-text"><?php echo __('dark_mode'); ?></span>
                    </button>
                    <a href="logout.php" class="logout-item"><i class="fa-solid fa-right-from-bracket"></i><?php echo __('nav_logout'); ?></a>
                </div>
            </li>
        </ul>
    </nav>

    <main class="main-content">
        <div class="notif-container">
            <div class="header-bar">
                <h1 class="header-title"><?php echo htmlspecialchars(__('notifications_title'), ENT_QUOTES, 'UTF-8'); ?></h1>
                <a href="notifications.php?action=read_all" class="mark-read-btn"><?php echo htmlspecialchars(__('notifications_mark_all_read'), ENT_QUOTES, 'UTF-8'); ?></a>
            </div>

            <?php if (empty($notifications)): ?>
                <div class="empty-state"><?php echo htmlspecialchars(__('notifications_empty'), ENT_QUOTES, 'UTF-8'); ?></div>
            <?php else: ?>
                <?php foreach ($notifications as $n): ?>
                    <?php
                    $avatar = resolveImagePath($n['actor_image'] ?? null);
                    $unreadClass = ((int)($n['is_read'] ?? 1) === 0) ? 'unread' : '';

                    $actorName = !empty($n['actor_name']) ? $n['actor_name'] : __('user_deleted');
                    $actionText = "";
                    $iconClass = "fa-solid fa-bell";
                    $iconColor = "icon-comment";

                    $is_msg_request = false;
                    if (($n['type'] ?? null) === 'message') {
                        $rq = $pdo->prepare("SELECT is_request FROM messages WHERE sender_id = ? AND receiver_id = ? ORDER BY id DESC LIMIT 1");
                        $rq->execute([(int)($n['actor_id'] ?? 0), $current_user_id]);
                        $rq_row = $rq->fetch();
                        $is_msg_request = $rq_row && (int)$rq_row['is_request'] === 1;
                    }

                    switch ($n['type']) {
                        case 'like_post':
                            $actionText = __('notification_liked_post');
                            $iconClass = "fa-solid fa-heart";
                            $iconColor = "icon-like";
                            break;
                        case 'comment_post':
                            $actionText = __('notification_commented_post');
                            $iconClass = "fa-solid fa-comment";
                            $iconColor = "icon-comment";
                            break;
                        case 'like_comment':
                            $actionText = __('notification_liked_comment');
                            $iconClass = "fa-solid fa-heart";
                            $iconColor = "icon-like";
                            break;
                        case 'reply_comment':
                            $actionText = __('notification_replied_comment');
                            $iconClass = "fa-solid fa-reply";
                            $iconColor = "icon-reply";
                            break;
                        case 'message':
                            $actionText = $is_msg_request ? __('notification_message_request') : __('notification_message');
                            $iconClass = "fa-solid fa-paper-plane";
                            $iconColor = "icon-reply";
                            break;
                    }
                    ?>
                    <a href="notifications.php?read_id=<?php echo $n['id']; ?>" class="notif-card <?php echo $unreadClass; ?>">
                        <?php if ($avatar): ?>
                            <img src="<?php echo htmlspecialchars($avatar); ?>" class="actor-avatar" alt="<?php echo htmlspecialchars($actorName); ?>">
                        <?php else: ?>
                            <div class="avatar-placeholder"><?php echo mb_substr($actorName, 0, 1, 'UTF-8'); ?></div>
                        <?php endif; ?>

                        <div class="notif-body">
                            <div class="notif-text">
                                <strong><?php echo htmlspecialchars($actorName); ?></strong> <?php echo $actionText; ?>
                            </div>
                            <div class="notif-time"><?php echo date('M d, H:i', strtotime($n['created_at'] ?? 'now')); ?></div>
                        </div>

                        <i class="<?php echo $iconClass . ' ' . $iconColor; ?> type-icon"></i>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>

    <div id="page-config" hidden data-config="<?php echo htmlspecialchars(json_encode(['values' => [__('dark_mode'), __('light_mode')]], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP), ENT_QUOTES, 'UTF-8'); ?>"></div>
    <script src="../js/page-config.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const moreToggle = document.getElementById('moreToggle');
            const moreMenu = document.getElementById('moreMenu');
            const themeToggle = document.getElementById('theme-toggle');
            const themeIcon = document.getElementById('theme-icon');
            const themeText = document.getElementById('theme-text');
            if (!moreToggle || !moreMenu || !themeToggle || !themeIcon || !themeText) return;

            const darkModeText = window.NEXAGRAM_CONFIG && window.NEXAGRAM_CONFIG.values ? window.NEXAGRAM_CONFIG.values[0] : 'Dark mode';
            const lightModeText = window.NEXAGRAM_CONFIG && window.NEXAGRAM_CONFIG.values ? window.NEXAGRAM_CONFIG.values[1] : 'Light mode';

            function setTheme(theme) {
                document.documentElement.setAttribute('data-theme', theme);
                localStorage.setItem('theme', theme);
                themeIcon.className = theme === 'dark' ? 'fa-solid fa-moon' : 'fa-solid fa-sun';
                themeText.textContent = theme === 'dark' ? darkModeText : lightModeText;
            }

            moreToggle.addEventListener('click', () => {
                const isOpen = moreMenu.classList.toggle('show');
                moreToggle.setAttribute('aria-expanded', String(isOpen));
            });

            document.addEventListener('click', (event) => {
                if (!moreMenu.contains(event.target) && !moreToggle.contains(event.target)) {
                    moreMenu.classList.remove('show');
                    moreToggle.setAttribute('aria-expanded', 'false');
                }
            });

            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') {
                    moreMenu.classList.remove('show');
                    moreToggle.setAttribute('aria-expanded', 'false');
                    moreToggle.focus();
                }
            });

            themeToggle.addEventListener('click', () => {
                const currentTheme = document.documentElement.getAttribute('data-theme') || 'dark';
                setTheme(currentTheme === 'dark' ? 'light' : 'dark');
            });

            setTheme(localStorage.getItem('theme') || 'dark');
        });
    </script>
    <script src="../js/badge.js"></script>
    <?php include __DIR__ . '/suggestions_widget.php'; ?>
</body>
</html>