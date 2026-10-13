<?php
// profile.php - Nexagram Profile with Post Management (Edit & Delete)
session_start();
require_once 'config.php';
require_once 'lang.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$logged_in_user_id = $_SESSION['user_id'];
$profile_user_id = isset($_GET['id']) ? (int)$_GET['id'] : $logged_in_user_id;
$is_own_profile = ($logged_in_user_id === $profile_user_id);

// --- HANDLE POST DELETE ACTION ---
if ($is_own_profile && isset($_POST['action']) && $_POST['action'] === 'delete_post') {
    $delete_post_id = (int)$_POST['post_id'];
    
    // Fetch media path to remove file if needed
    $stmt = $pdo->prepare("SELECT * FROM posts WHERE id = ? AND user_id = ?");
    $stmt->execute([$delete_post_id, $logged_in_user_id]);
    $post_to_delete = $stmt->fetch();

    if ($post_to_delete) {
        $raw_file = $post_to_delete['image_url'] ?? $post_to_delete['image_path'] ?? $post_to_delete['image'] ?? $post_to_delete['file_path'] ?? $post_to_delete['media_url'] ?? null;
        if ($raw_file) {
            $file_to_remove = (strpos($raw_file, 'uploads/') === 0) ? $raw_file : 'uploads/' . ltrim($raw_file, '/');
            $file_to_remove = __DIR__ . '/../' . $file_to_remove;
            if (file_exists($file_to_remove)) {
                @unlink($file_to_remove);
            }
        }

        // Delete comments, likes, and the post record
        $pdo->prepare("DELETE FROM comments WHERE post_id = ?")->execute([$delete_post_id]);
        $pdo->prepare("DELETE FROM likes WHERE post_id = ?")->execute([$delete_post_id]);
        $pdo->prepare("DELETE FROM posts WHERE id = ? AND user_id = ?")->execute([$delete_post_id, $logged_in_user_id]);
    }
    
    header("Location: profile.php?id=" . $profile_user_id);
    exit();
}

// --- HANDLE POST EDIT ACTION ---
if ($is_own_profile && isset($_POST['action']) && $_POST['action'] === 'edit_post') {
    $edit_post_id = (int)$_POST['post_id'];
    $new_caption = trim($_POST['caption'] ?? '');

    $stmt = $pdo->prepare("UPDATE posts SET caption = ? WHERE id = ? AND user_id = ?");
    $stmt->execute([$new_caption, $edit_post_id, $logged_in_user_id]);

    header("Location: profile.php?id=" . $profile_user_id);
    exit();
}

// 1. Fetch User Info
$user_stmt = $pdo->prepare("SELECT id, username, profile_image, bio FROM users WHERE id = ?");
$user_stmt->execute([$profile_user_id]);
$user = $user_stmt->fetch();

if (!$user) {
    header("Location: index.php");
    exit();
}

// 2. Metrics
$posts_count = $pdo->prepare("SELECT COUNT(*) FROM posts WHERE user_id = ?");
$posts_count->execute([$profile_user_id]);
$posts_count = $posts_count->fetchColumn();

$followers_count = $pdo->prepare("SELECT COUNT(*) FROM follows WHERE following_id = ?");
$followers_count->execute([$profile_user_id]);
$followers_count = $followers_count->fetchColumn();

$following_count = $pdo->prepare("SELECT COUNT(*) FROM follows WHERE follower_id = ?");
$following_count->execute([$profile_user_id]);
$following_count = $following_count->fetchColumn();

$friends_count = $pdo->prepare("SELECT COUNT(*) FROM friend_requests WHERE (sender_id = ? OR receiver_id = ?) AND status = 'accepted'");
$friends_count->execute([$profile_user_id, $profile_user_id]);
$friends_count = $friends_count->fetchColumn();

// 3. User Posts
$posts_stmt = $pdo->prepare("
    SELECT posts.*, 
    (SELECT COUNT(*) FROM likes WHERE likes.post_id = posts.id) as like_count,
    (SELECT COUNT(*) FROM comments WHERE comments.post_id = posts.id) as comment_count
    FROM posts 
    WHERE user_id = ? 
    ORDER BY created_at DESC
");
$posts_stmt->execute([$profile_user_id]);
$user_posts = $posts_stmt->fetchAll();

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
    <title>Nexagram • <?php echo htmlspecialchars($user['username']); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="../js/theme-init.js"></script>
    <link rel="stylesheet" href="../css/profile.css">
    <link rel="stylesheet" href="../css/utilities.css">
    <link rel="stylesheet" href="../css/more-menu.css">
    <link rel="stylesheet" href="../css/suggestions-widget.css">
</head>

<body>

    <nav class="sidebar">
        <a href="index.php" class="logo logo-link">Nexagram</a>
        <ul class="nav-menu">
            <li class="nav-item"><a href="index.php"><i class="fa-solid fa-house"></i> <?php echo __('nav_home'); ?></a></li>
            <li class="nav-item"><a href="friends.php"><i class="fa-solid fa-user-group"></i> <?php echo __('nav_friends'); ?></a></li>
            <li class="nav-item"><a href="users.php"><i class="fa-solid fa-users"></i> <?php echo __('nav_users'); ?></a></li>
            <li class="nav-item"><a href="messages.php"><i class="fa-solid fa-paper-plane"></i> <?php echo __('nav_messages'); ?> <span class="notif-badge is-hidden" id="navMsgBadge">0</span></a></li>
            <li class="nav-item"><a href="create_post.php"><i class="fa-solid fa-square-plus"></i> <?php echo __('nav_create'); ?></a></li>
            <li class="nav-item active"><a href="profile.php"><i class="fa-solid fa-user"></i> <?php echo __('nav_profile'); ?></a></li>

            <li class="nav-item more-menu-item">
                <button type="button" class="more-trigger" data-ui-action="toggle-lang-menu">
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
        <div class="profile-wrapper">

            <!-- Profile Header -->
            <div class="profile-card">
                <div class="avatar-ring">
                    <div class="avatar-inner">
                        <?php 
                            $profile_img_src = resolveImagePath($user['profile_image'] ?? null);
                            if ($profile_img_src): 
                        ?>
                            <img src="<?php echo htmlspecialchars($profile_img_src); ?>" alt="Avatar">
                        <?php else: ?>
                            <?php echo mb_substr(htmlspecialchars($user['username']), 0, 1, 'UTF-8'); ?>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="profile-info">
                    <div class="header-top">
                        <h1 class="username"><?php echo htmlspecialchars($user['username']); ?></h1>
                        <?php if ($is_own_profile): ?>
                            <a href="edit_profile.php" class="btn-edit-profile"><?php echo __('edit_profile'); ?></a>
                        <?php endif; ?>
                    </div>

                    <div class="stats-row">
                        <div class="stat-box"><span class="stat-val"><?php echo $posts_count; ?></span> <span class="stat-lbl"><?php echo __('stat_posts'); ?></span></div>
                        <a href="friends.php?tab=followers" class="stat-box"><span class="stat-val"><?php echo $followers_count; ?></span> <span class="stat-lbl"><?php echo __('stat_followers'); ?></span></a>
                        <a href="friends.php?tab=following" class="stat-box"><span class="stat-val"><?php echo $following_count; ?></span> <span class="stat-lbl"><?php echo __('stat_following'); ?></span></a>
                        <a href="friends.php?tab=friends" class="stat-box"><span class="stat-val"><?php echo $friends_count; ?></span> <span class="stat-lbl"><?php echo __('stat_friends'); ?></span></a>
                    </div>

                    <?php if (!empty($user['bio'])): ?>
                        <div class="user-bio"><?php echo nl2br(htmlspecialchars($user['bio'])); ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Posts Grid -->
            <div class="posts-grid">
                <?php if (!empty($user_posts)): ?>
                    <?php foreach ($user_posts as $post): ?>
                        <?php 
                            $raw_file = $post['image_url'] ?? $post['image_path'] ?? $post['image'] ?? $post['file_path'] ?? $post['media_url'] ?? null;
                            $caption = $post['caption'] ?? $post['content'] ?? '';
                            $media_src = resolveImagePath($raw_file);
                        ?>
                        <div class="grid-card">
                            <?php if (!empty($media_src)): ?>
                                <?php if (preg_match('/\.(mp4|webm|ogg)$/i', $media_src)): ?>
                                    <video src="<?php echo htmlspecialchars($media_src); ?>" class="grid-media"></video>
                                <?php else: ?>
                                    <img src="<?php echo htmlspecialchars($media_src); ?>" class="grid-media" alt="Post Image">
                                <?php endif; ?>
                            <?php endif; ?>

                            <!-- Hover Overlay with Edit/Delete Controls -->
                            <div class="grid-overlay">
                                <?php if ($is_own_profile): ?>
                                    <div class="overlay-top-actions">
                                        <button class="action-btn" title="<?php echo __('edit_caption_tooltip'); ?>" data-ui-action="open-edit-modal" data-post-id="<?php echo (int)$post['id']; ?>" data-caption="<?php echo htmlspecialchars($caption, ENT_QUOTES, 'UTF-8'); ?>">
                                            <i class="fa-solid fa-pen"></i>
                                        </button>
                                        <form method="POST" data-ui-confirm="<?php echo htmlspecialchars(__('confirm_delete_post'), ENT_QUOTES, 'UTF-8'); ?>">
                                            <input type="hidden" name="action" value="delete_post">
                                            <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                                            <button type="submit" class="action-btn btn-delete" title="<?php echo __('delete_post_tooltip'); ?>">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                <?php else: ?>
                                    <div></div>
                                <?php endif; ?>

                                <div class="overlay-center-stats">
                                    <div class="overlay-item"><i class="fa-solid fa-heart"></i> <?php echo $post['like_count']; ?></div>
                                    <div class="overlay-item"><i class="fa-solid fa-comment"></i> <?php echo $post['comment_count']; ?></div>
                                </div>

                                <?php if (!empty($caption)): ?>
                                    <div class="card-caption-tag"><?php echo htmlspecialchars($caption); ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

        </div>
    </main>

    <!-- Modal for Editing Post Caption -->
    <div class="modal-overlay" id="editModal">
        <div class="modal-box">
            <div class="modal-title">
                <span><?php echo __('edit_caption_title'); ?></span>
                <i class="fa-solid fa-xmark modal-close" data-ui-action="close-edit-modal"></i>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="edit_post">
                <input type="hidden" name="post_id" id="modalPostId">
                <textarea name="caption" id="modalCaption" class="modal-textarea" placeholder="<?php echo __('write_caption_placeholder'); ?>"></textarea>
                <div class="modal-actions">
                    <button type="button" class="btn-cancel" data-ui-action="close-edit-modal"><?php echo __('btn_cancel'); ?></button>
                    <button type="submit" class="btn-save"><?php echo __('btn_save'); ?></button>
                </div>
            </form>
        </div>
    </div>

    <div id="page-config" hidden data-config="<?php echo htmlspecialchars(json_encode(['values' => [__('dark_mode'), __('light_mode')]], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP), ENT_QUOTES, 'UTF-8'); ?>"></div>
    <script src="../js/page-config.js"></script>
    <script src="../js/profile.js"></script>
    <script src="../js/actions.js"></script>
    <script src="../js/badge.js"></script>
    <?php include __DIR__ . '/suggestions_widget.php'; ?>
</body>
</html>