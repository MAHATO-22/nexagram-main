<?php
// friends.php - Nexagram People & Network Directory
session_start();
require_once 'config.php';
require_once 'lang.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$current_user_id = $_SESSION['user_id'];
$tab = $_GET['tab'] ?? 'friends';
$search = trim($_GET['search'] ?? '');

function resolveImagePath($fileName)
{
    if (empty($fileName)) return null;
    if (strpos($fileName, 'http') === 0) return $fileName;

    $cleanName = ltrim($fileName, '/');
    if (file_exists(__DIR__ . '/../' . $cleanName)) return '../' . $cleanName;
    if (file_exists(__DIR__ . '/../uploads/' . $cleanName)) return '../uploads/' . $cleanName;

    return (strpos($cleanName, 'uploads/') === 0) ? '../' . $cleanName : '../uploads/' . $cleanName;
}

// Counts for tab badges
try {
    // Friends Count
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM friend_requests WHERE (sender_id = ? OR receiver_id = ?) AND status = 'accepted'");
    $stmt->execute([$current_user_id, $current_user_id]);
    $friends_count = $stmt->fetchColumn();

    // Requests Count
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM friend_requests WHERE receiver_id = ? AND status = 'pending'");
    $stmt->execute([$current_user_id]);
    $requests_count = $stmt->fetchColumn();

    // Following Count
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM follows WHERE follower_id = ?");
    $stmt->execute([$current_user_id]);
    $following_count = $stmt->fetchColumn();

    // Followers Count
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM follows WHERE following_id = ?");
    $stmt->execute([$current_user_id]);
    $followers_count = $stmt->fetchColumn();

    // Query active tab data
    $list_users = [];

    if ($tab === 'friends') {
        $sql = "SELECT users.id, users.username, users.profile_image, users.bio
                FROM friend_requests fr
                JOIN users ON (users.id = IF(fr.sender_id = ?, fr.receiver_id, fr.sender_id))
                WHERE (fr.sender_id = ? OR fr.receiver_id = ?) AND fr.status = 'accepted'";
        $params = [$current_user_id, $current_user_id, $current_user_id];
        if ($search !== '') {
            $sql .= " AND users.username LIKE ?";
            $params[] = "%$search%";
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $list_users = $stmt->fetchAll();

    } elseif ($tab === 'requests') {
        $sql = "SELECT users.id, users.username, users.profile_image, users.bio, fr.id AS request_id
                FROM friend_requests fr
                JOIN users ON users.id = fr.sender_id
                WHERE fr.receiver_id = ? AND fr.status = 'pending'";
        $params = [$current_user_id];
        if ($search !== '') {
            $sql .= " AND users.username LIKE ?";
            $params[] = "%$search%";
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $list_users = $stmt->fetchAll();

    } elseif ($tab === 'following') {
        $sql = "SELECT users.id, users.username, users.profile_image, users.bio, 1 AS is_following
                FROM follows f
                JOIN users ON users.id = f.following_id
                WHERE f.follower_id = ?";
        $params = [$current_user_id];
        if ($search !== '') {
            $sql .= " AND users.username LIKE ?";
            $params[] = "%$search%";
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $list_users = $stmt->fetchAll();

    } elseif ($tab === 'followers') {
        $sql = "SELECT users.id, users.username, users.profile_image, users.bio,
                (SELECT COUNT(*) FROM follows WHERE follower_id = ? AND following_id = users.id) AS is_following_back
                FROM follows f
                JOIN users ON users.id = f.follower_id
                WHERE f.following_id = ?";
        $params = [$current_user_id, $current_user_id];
        if ($search !== '') {
            $sql .= " AND users.username LIKE ?";
            $params[] = "%$search%";
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $list_users = $stmt->fetchAll();
    }

} catch (\PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($current_lang, ENT_QUOTES, 'UTF-8'); ?>" dir="<?php echo htmlspecialchars($text_direction, ENT_QUOTES, 'UTF-8'); ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nexagram • <?php echo __('nav_friends'); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="../js/theme-init.js"></script>
    <link rel="stylesheet" href="../css/friends.css">
    <link rel="stylesheet" href="../css/utilities.css">
    <link rel="stylesheet" href="../css/more-menu.css">
    <link rel="stylesheet" href="../css/suggestions-widget.css">
</head>

<body>

    <nav class="sidebar">
        <a href="index.php" class="logo logo-link">Nexagram</a>
        <ul class="nav-menu">
            <li class="nav-item"><a href="index.php"><i class="fa-solid fa-house"></i> <?php echo __('nav_home'); ?></a></li>
            <li class="nav-item active"><a href="friends.php"><i class="fa-solid fa-user-group"></i> <?php echo __('nav_friends'); ?></a></li>
            <li class="nav-item"><a href="users.php"><i class="fa-solid fa-users"></i> <?php echo __('nav_users'); ?></a></li>
            <li class="nav-item"><a href="messages.php"><i class="fa-solid fa-paper-plane"></i> <?php echo __('nav_messages'); ?> <span class="notif-badge is-hidden" id="navMsgBadge">0</span></a></li>
            <li class="nav-item"><a href="create_post.php"><i class="fa-solid fa-square-plus"></i> <?php echo __('nav_create'); ?></a></li>
            <li class="nav-item"><a href="profile.php"><i class="fa-solid fa-user"></i> <?php echo __('nav_profile'); ?></a></li>

            <li class="nav-item more-menu-item">
                <button type="button" class="nav-item more-trigger" data-ui-action="toggle-lang-menu">
                    <i class="fa-solid fa-ellipsis"></i>
                    <span><?php echo __('more'); ?></span>
                    <i class="fa-solid fa-chevron-down lang-arrow"></i>
                </button>
                <div class="lang-menu" id="langMenu">
                    <a href="settings.php"><i class="fa-solid fa-gear"></i> <?php echo __('nav_settings'); ?></a>
                    <button type="button" class="theme-btn" id="theme-toggle">
                        <i class="fa-solid fa-moon" id="theme-icon"></i>
                        <span id="theme-text"><?php echo __('dark_mode'); ?></span>
                    </button>
                    <a href="logout.php" class="logout-item"><i class="fa-solid fa-right-from-bracket"></i> <?php echo __('nav_logout'); ?></a>
                </div>
            </li>

        </ul>
    </nav>

    <main class="main-content">
        <div class="page-header">
            <h1 class="page-title"><?php echo __('network_title'); ?></h1>

            <form method="GET" action="friends.php" class="search-bar">
                <input type="hidden" name="tab" value="<?php echo htmlspecialchars($tab); ?>">
                <i class="fa-solid fa-magnifying-glass friends-search-icon"></i>
                <input type="text" name="search" placeholder="<?php echo __('search_connections_placeholder'); ?>" value="<?php echo htmlspecialchars($search); ?>">
            </form>

            <div class="nav-tabs">
                <a href="friends.php?tab=friends" class="tab-btn <?php echo $tab === 'friends' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-user-check"></i> <?php echo __('tab_friends'); ?> <span class="badge"><?php echo $friends_count; ?></span>
                </a>
                <a href="friends.php?tab=requests" class="tab-btn <?php echo $tab === 'requests' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-user-plus"></i> <?php echo __('tab_requests'); ?> <span class="badge"><?php echo $requests_count; ?></span>
                </a>
                <a href="friends.php?tab=following" class="tab-btn <?php echo $tab === 'following' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-users"></i> <?php echo __('tab_following'); ?> <span class="badge"><?php echo $following_count; ?></span>
                </a>
                <a href="friends.php?tab=followers" class="tab-btn <?php echo $tab === 'followers' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-users-viewfinder"></i> <?php echo __('tab_followers'); ?> <span class="badge"><?php echo $followers_count; ?></span>
                </a>
            </div>
        </div>

        <div class="network-list">
            <?php if (empty($list_users)): ?>
                <div class="friends-empty-state"><?php echo __('no_users'); ?></div>
            <?php else: ?>
                <?php foreach ($list_users as $u): ?>
                    <?php $u_avatar = resolveImagePath($u['profile_image'] ?? null); ?>
                    <div class="user-item">
                        <a href="profile.php?id=<?php echo $u['id']; ?>" class="user-info-wrap">
                            <?php if (!empty($u_avatar)): ?>
                                <img src="<?php echo htmlspecialchars($u_avatar); ?>" class="avatar-img">
                            <?php else: ?>
                                <div class="avatar-circle"><?php echo mb_substr(htmlspecialchars($u['username']), 0, 1, 'UTF-8'); ?></div>
                            <?php endif; ?>

                            <div class="user-details">
                                <span class="username"><?php echo htmlspecialchars($u['username']); ?></span>
                                <?php if ($tab === 'followers'): ?>
                                    <span class="badge-follows-you"><?php echo __('follows_you'); ?></span>
                                <?php endif; ?>
                            </div>
                        </a>

                        <div class="action-group">
                            <?php if ($tab === 'friends'): ?>
                                <button class="btn-unfriend" data-ui-action="unfriend" data-target-id="<?php echo (int)$u['id']; ?>">
                                    <i class="fa-solid fa-user-minus"></i>
                                    <span><?php echo __('btn_unfriend'); ?></span>
                                </button>

                            <?php elseif ($tab === 'followers'): ?>
                                <?php $is_following_back = (bool)$u['is_following_back']; ?>
                                <button class="action-btn <?php echo $is_following_back ? 'btn-following' : 'btn-follow-back'; ?>"
                                        data-ui-action="follow-user" data-target-id="<?php echo (int)$u['id']; ?>">
                                    <i class="fa-solid <?php echo $is_following_back ? 'fa-user-check' : 'fa-user-plus'; ?>"></i>
                                    <span><?php echo $is_following_back ? __('btn_following') : __('btn_follow_back'); ?></span>
                                </button>

                            <?php elseif ($tab === 'following'): ?>
                                <button class="action-btn btn-following"
                                        data-ui-action="follow-user" data-target-id="<?php echo (int)$u['id']; ?>">
                                    <i class="fa-solid fa-user-check"></i>
                                    <span><?php echo __('btn_following'); ?></span>
                                </button>

                            <?php elseif ($tab === 'requests'): ?>
                                <button class="btn-accept" data-ui-action="friend-request" data-target-id="<?php echo (int)$u['id']; ?>" data-friend-action="accept"><?php echo __('btn_accept'); ?></button>
                                <button class="btn-reject" data-ui-action="friend-request" data-target-id="<?php echo (int)$u['id']; ?>" data-friend-action="reject"><?php echo __('btn_reject'); ?></button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>

    <div id="page-config" hidden data-config="<?php echo htmlspecialchars(json_encode(['values' => [__('confirm_unfriend'), __('btn_following'), __('btn_follow_back'), __('dark_mode'), __('light_mode')]], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP), ENT_QUOTES, 'UTF-8'); ?>"></div>
    <script src="../js/page-config.js"></script>
    <script src="../js/friends.js"></script>
    <script src="../js/actions.js"></script>
    <script src="../js/badge.js"></script>
    <?php include __DIR__ . '/suggestions_widget.php'; ?>
</body>
</html>