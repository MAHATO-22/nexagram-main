<?php
// index.php - Nexagram Feed with Multi-Media Grid, Lightbox, Comment Likes & Notifications
session_start();
require_once 'config.php';
require_once 'lang.php'; // Included language support

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$current_user_id = $_SESSION['user_id'];
$current_username = $_SESSION['username'] ?? 'User';
$allowed_reactions = ['❤️', '👍', '👎', '😂', '🤣', '😮', '😢', '😭', '😡', '😍', '🥰', '🤩', '😎', '🤔', '🥳', '🙏', '🙌', '👏', '🔥', '🎉', '💯', '💪', '✨', '👀'];

// Get current user's profile image
$user_stmt = $pdo->prepare("SELECT profile_image, student_id, user_code, department, class_name, school_name FROM users WHERE id = ?");
$user_stmt->execute([$current_user_id]);
$current_user_data = $user_stmt->fetch();

function resolveImagePath($fileName)
{
    if (empty($fileName)) return null;
    if (strpos($fileName, 'http') === 0) return $fileName;

    $cleanName = ltrim($fileName, '/');
    if (file_exists(__DIR__ . '/../' . $cleanName)) return '../' . $cleanName;
    if (file_exists(__DIR__ . '/../uploads/' . $cleanName)) return '../uploads/' . $cleanName;

    return (strpos($cleanName, 'uploads/') === 0) ? '../' . $cleanName : '../uploads/' . $cleanName;
}

$current_user_avatar = resolveImagePath($current_user_data['profile_image'] ?? null);

require_once 'suggestion_helper.php';
$suggestions = getPeopleSuggestions($pdo, (int)$current_user_id);

// Fetch Posts with Public, Follower-Only, and Friend-Only Privacy Rules
// Fetch Posts with Public, Follower-Only, and Friend-Only Privacy Rules
try {
    $stmt = $pdo->prepare("
        SELECT posts.*, users.username, users.profile_image,
            (SELECT COUNT(*) FROM likes WHERE likes.post_id = posts.id) as like_count,
            (SELECT COUNT(*) FROM likes WHERE likes.post_id = posts.id AND likes.user_id = :user_id1) as is_liked,
            (SELECT reaction FROM likes WHERE likes.post_id = posts.id AND likes.user_id = :user_id6 LIMIT 1) as user_reaction,
            (SELECT COUNT(*) FROM comments WHERE comments.post_id = posts.id) as comment_count
        FROM posts 
        JOIN users ON posts.user_id = users.id 
        WHERE 
            -- 1. Viewer's own posts
            posts.user_id = :user_id2
            
            -- 2. Public posts
            OR posts.visibility = 'public'
            
            -- 3. Followers-Only posts (Viewer follows author)
            OR (
                posts.visibility = 'followers' 
                AND EXISTS (
                    SELECT 1 FROM follows 
                    WHERE follower_id = :user_id3 
                      AND following_id = posts.user_id
                )
            )
            
            -- 4. Friends-Only posts (Accepted friend request in friend_requests table)
            OR (
                posts.visibility = 'friends' 
                AND EXISTS (
                    SELECT 1 FROM friend_requests 
                    WHERE ((sender_id = :user_id4 AND receiver_id = posts.user_id)
                       OR  (sender_id = posts.user_id AND receiver_id = :user_id5))
                      AND status = 'accepted'
                )
            )
        ORDER BY posts.created_at DESC
    ");

    $stmt->execute([
        'user_id1' => $current_user_id,
        'user_id2' => $current_user_id,
        'user_id3' => $current_user_id,
        'user_id4' => $current_user_id,
        'user_id5' => $current_user_id,
        'user_id6' => $current_user_id
    ]);

    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (\PDOException $e) {
    die("Database Error: " . $e->getMessage());
}

$encoded_data = [];
        foreach ($posts as $p) {
            $c_stmt = $pdo->prepare("SELECT comments.*, users.username, users.profile_image,
                                     (SELECT COUNT(*) FROM comment_likes WHERE comment_likes.comment_id = comments.id) as like_count,
                                     (SELECT COUNT(*) FROM comment_likes WHERE comment_likes.comment_id = comments.id AND comment_likes.user_id = ?) as is_liked
                                     FROM comments 
                                     JOIN users ON comments.user_id = users.id 
                                     WHERE post_id = ? 
                                     ORDER BY comments.id ASC");
            $c_stmt->execute([$current_user_id, $p['id']]);
            $comments = $c_stmt->fetchAll();

            $encoded_data[$p['id']] = [];
            foreach ($comments as $c) {
                $encoded_data[$p['id']][] = [
                    'id' => (int)$c['id'],
                    'parent_id' => $c['parent_id'] ? (int)$c['parent_id'] : null,
                    'username' => htmlspecialchars($c['username']),
                    'avatar' => resolveImagePath($c['profile_image'] ?? null),
                    'text' => htmlspecialchars($c['comment_text']),
                    'like_count' => (int)$c['like_count'],
                    'is_liked' => (bool)$c['is_liked']
                ];
            }
        }
        
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($current_lang, ENT_QUOTES, 'UTF-8'); ?>" dir="<?php echo htmlspecialchars($text_direction, ENT_QUOTES, 'UTF-8'); ?>"
      data-translation-unavailable="<?php echo htmlspecialchars(__('translation_unavailable'), ENT_QUOTES, 'UTF-8'); ?>"
      data-translation-failed="<?php echo htmlspecialchars(__('translation_failed'), ENT_QUOTES, 'UTF-8'); ?>"
      data-translation-loading="<?php echo htmlspecialchars(__('translation_loading'), ENT_QUOTES, 'UTF-8'); ?>"
      data-translation-label="<?php echo htmlspecialchars(__('translate'), ENT_QUOTES, 'UTF-8'); ?>"
      data-translation-too-long="<?php echo htmlspecialchars(__('translation_too_long'), ENT_QUOTES, 'UTF-8'); ?>"
      data-translation-target="<?php echo htmlspecialchars($current_lang, ENT_QUOTES, 'UTF-8'); ?>"
      data-translation-user="<?php echo (int)$current_user_id; ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nexagram • <?php echo __('nav_home'); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="../js/theme-init.js"></script>
    <link rel="stylesheet" href="../css/index.css">
    <link rel="stylesheet" href="../css/translation.css">
    <link rel="stylesheet" href="../css/utilities.css">
    <link rel="stylesheet" href="../css/more-menu.css">
    <link rel="stylesheet" href="../css/suggestions-widget.css">
</head>

<body>

    <!-- Sidebar Component -->
    <nav class="sidebar">
        <a href="index.php" class="logo logo-link">Nexagram</a>
        <ul class="nav-menu">
            <li class="nav-item active"><a href="index.php"><i class="fa-solid fa-house"></i> <?php echo __('nav_home'); ?></a></li>
            <li class="nav-item">
                <a href="notifications.php">
                    <i class="fa-solid fa-bell"></i> <?php echo htmlspecialchars(__('nav_notifications'), ENT_QUOTES, 'UTF-8'); ?>
                    <span class="notif-badge is-hidden" id="navNotifBadge">0</span>
                </a>
            </li>
            <li class="nav-item"><a href="friends.php"><i class="fa-solid fa-user-group"></i> <?php echo __('nav_friends'); ?></a></li>
            <li class="nav-item"><a href="users.php"><i class="fa-solid fa-users"></i> <?php echo __('nav_users'); ?></a></li>
            <li class="nav-item">
                <a href="messages.php">
                    <i class="fa-solid fa-paper-plane"></i> <?php echo __('nav_messages'); ?>
                    <span class="notif-badge is-hidden" id="navMsgBadge">0</span>
                </a>
            </li>
            <li class="nav-item"><a href="create_post.php"><i class="fa-solid fa-square-plus"></i> <?php echo __('nav_create'); ?></a></li>
            <li class="nav-item"><a href="profile.php"><i class="fa-solid fa-user"></i> <?php echo __('nav_profile'); ?></a></li>
        </ul>

        <!-- More menu -->
        <div class="sidebar-bottom">
            <div class="lang-dropdown-container" id="langDropdownContainer">
                <div class="lang-dropdown-menu" id="langDropdownMenu">
                    <a href="settings.php" class="lang-option">
                        <i class="fa-solid fa-gear"></i> <?php echo __('nav_settings'); ?>
                    </a>
                    <button type="button" class="sidebar-action-btn" id="theme-toggle">
                        <i class="fa-solid fa-moon" id="theme-icon"></i>
                        <span id="theme-text"><?php echo __('dark_mode'); ?></span>
                    </button>
                    <a href="logout.php" class="sidebar-action-btn logout-btn">
                        <i class="fa-solid fa-right-from-bracket"></i>
                        <span><?php echo __('nav_logout'); ?></span>
                    </a>
                </div>

                <button class="lang-dropdown-btn" id="langDropdownToggle" type="button">
                    <i class="fa-solid fa-ellipsis"></i>
                    <span><?php echo __('more'); ?></span>
                    <i class="fa-solid fa-chevron-down arrow-icon"></i>
                </button>
            </div>
        </div>
    </nav>

    <main class="main-content">
        <div class="feed-container">
            <div class="auto-translation-control">
                <button type="button" class="auto-translate-toggle" data-auto-translate
                        data-enabled-label="<?php echo htmlspecialchars(__('auto_translate_on'), ENT_QUOTES, 'UTF-8'); ?>"
                        data-disabled-label="<?php echo htmlspecialchars(__('auto_translate_off'), ENT_QUOTES, 'UTF-8'); ?>"
                        aria-pressed="false"><?php echo htmlspecialchars(__('auto_translate'), ENT_QUOTES, 'UTF-8'); ?></button>
                <span class="auto-translate-status" data-auto-translate-status role="status"></span>
            </div>
            <section class="suggestions-panel" aria-labelledby="suggestions-title">
                <div class="suggestions-heading">
                    <div>
                        <h1 id="suggestions-title"><?php echo __('suggestions_title'); ?></h1>
                        <p><?php echo __('suggestions_subtitle'); ?></p>
                    </div>
                    <a href="users.php" class="suggestions-all-link"><?php echo __('see_all_users'); ?></a>
                </div>
                <?php if ($suggestions): ?>
                    <div class="suggestions-list">
                        <?php foreach ($suggestions as $suggestion): ?>
                            <?php
                            $suggestion_avatar = resolveImagePath($suggestion['profile_image'] ?? null);
                            $reason_key = 'suggestion_' . $suggestion['match_reason'];
                            ?>
                            <article class="suggestion-card">
                                <a class="suggestion-person" href="profile.php?id=<?php echo (int)$suggestion['id']; ?>">
                                    <?php if ($suggestion_avatar): ?>
                                        <img src="<?php echo htmlspecialchars($suggestion_avatar, ENT_QUOTES, 'UTF-8'); ?>" alt="" class="suggestion-avatar">
                                    <?php else: ?>
                                        <span class="suggestion-avatar suggestion-avatar-fallback"><?php echo htmlspecialchars(mb_substr($suggestion['username'], 0, 1, 'UTF-8'), ENT_QUOTES, 'UTF-8'); ?></span>
                                    <?php endif; ?>
                                    <span class="suggestion-person-info">
                                        <strong><?php echo htmlspecialchars($suggestion['username'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                        <span><?php echo __($reason_key); ?></span>
                                    </span>
                                </a>
                                <div class="suggestion-actions">
                                    <button type="button" class="suggestion-action suggestion-follow" data-target-id="<?php echo (int)$suggestion['id']; ?>" data-error-label="<?php echo htmlspecialchars(__('suggestion_follow_error'), ENT_QUOTES, 'UTF-8'); ?>">
                                        <i class="fa-solid fa-user-plus"></i><span><?php echo __('btn_follow'); ?></span>
                                        <span class="suggestion-labels" hidden data-follow="<?php echo htmlspecialchars(__('btn_follow'), ENT_QUOTES, 'UTF-8'); ?>" data-following="<?php echo htmlspecialchars(__('btn_following'), ENT_QUOTES, 'UTF-8'); ?>"></span>
                                    </button>
                                    <button type="button" class="suggestion-action suggestion-friend" data-target-id="<?php echo (int)$suggestion['id']; ?>" data-error-label="<?php echo htmlspecialchars(__('suggestion_friend_error'), ENT_QUOTES, 'UTF-8'); ?>" data-add-label="<?php echo htmlspecialchars(__('btn_add_friend'), ENT_QUOTES, 'UTF-8'); ?>" data-pending-label="<?php echo htmlspecialchars(__('btn_pending'), ENT_QUOTES, 'UTF-8'); ?>">
                                        <i class="fa-solid fa-user-group"></i><span><?php echo __('btn_add_friend'); ?></span>
                                    </button>
                                </div>
                                <p class="suggestion-feedback" aria-live="polite"></p>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="suggestions-empty"><?php echo __('suggestions_empty'); ?></p>
                <?php endif; ?>
            </section>

            <?php foreach ($posts as $post): ?>
                <?php
                $user_avatar = resolveImagePath($post['profile_image'] ?? null);

                // Fetch media from post_media table
                $media_items = [];
                try {
                    $m_stmt = $pdo->prepare("SELECT media_path FROM post_media WHERE post_id = ? ORDER BY id ASC");
                    $m_stmt->execute([$post['id']]);
                    $fetched_media = $m_stmt->fetchAll(PDO::FETCH_COLUMN);

                    foreach ($fetched_media as $m) {
                        $resolved = resolveImagePath($m);
                        if ($resolved) {
                            $media_items[] = $resolved;
                        }
                    }
                } catch (\PDOException $e) {
                    // Fallback if table doesn't exist yet
                }

                // Fallback to legacy posts.image_path if post_media is empty
                if (empty($media_items)) {
                    $raw_file = $post['image_path'] ?? $post['image_url'] ?? $post['image'] ?? $post['file_path'] ?? $post['media_url'] ?? null;
                    $single_media = resolveImagePath($raw_file);
                    if (!empty($single_media)) {
                        $media_items[] = $single_media;
                    }
                }

                $total_media = count($media_items);
                $display_count = min($total_media, 4);
                $media_json = htmlspecialchars(json_encode($media_items), ENT_QUOTES, 'UTF-8');
                ?>
                <div class="post-card" id="post_<?php echo $post['id']; ?>">
                    <div class="post-header">
                        <a href="profile.php?id=<?php echo $post['user_id']; ?>" class="feed-profile-link">
                            <?php if (!empty($user_avatar)): ?>
                                <img src="<?php echo htmlspecialchars($user_avatar); ?>" class="profile-img-avatar">
                            <?php else: ?>
                                <div class="avatar-circle"><?php echo mb_substr(htmlspecialchars($post['username']), 0, 1, 'UTF-8'); ?></div>
                            <?php endif; ?>
                        </a>

                        <div class="header-info">
                            <a href="profile.php?id=<?php echo $post['user_id']; ?>" class="feed-username-link">
                                <span class="username"><?php echo htmlspecialchars($post['username']); ?></span>
                            </a>
                            <span class="post-time-top"><?php echo date('Y-m-d H:i', strtotime($post['created_at'])); ?></span>
                        </div>
                    </div>

                    <?php if (!empty($post['caption'])): ?>
                        <div class="post-info">
                            <div data-translatable>
                                <div class="post-caption" data-translation-source><?php echo htmlspecialchars($post['caption'], ENT_QUOTES, 'UTF-8'); ?></div>
                                <div class="translation-tools">
                                    <button type="button" class="translate-button" data-translate-button data-label="<?php echo htmlspecialchars(__('translate'), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars(__('translate'), ENT_QUOTES, 'UTF-8'); ?></button>
                                    <span class="translation-result" data-translation-result hidden></span>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($media_items)): ?>
                        <div class="post-media-grid count-<?php echo $display_count; ?>">
                            <?php for ($i = 0; $i < $display_count; $i++): ?>
                                <?php
                                $media_src = $media_items[$i];
                                $ext = strtolower(pathinfo($media_src, PATHINFO_EXTENSION));
                                $is_last = ($i === 3 && $total_media > 4);
                                $more_count = $total_media - 4;
                                ?>
                                <div class="media-item-box" data-ui-action="feed-open-lightbox" data-media="<?php echo $media_json; ?>" data-media-index="<?php echo $i; ?>">
                                    <?php if (in_array($ext, ['mp4', 'webm', 'mov'])): ?>
                                        <video src="<?php echo htmlspecialchars($media_src); ?>" class="post-media-element"></video>
                                    <?php else: ?>
                                        <img src="<?php echo htmlspecialchars($media_src); ?>" class="post-media-element" alt="Post photo">
                                    <?php endif; ?>

                                    <?php if ($is_last): ?>
                                        <div class="media-overlay-more">+<?php echo $more_count; ?></div>
                                    <?php endif; ?>
                                </div>
                            <?php endfor; ?>
                        </div>
                    <?php endif; ?>

                    <div class="counts-row">
                        <div class="likes-count-display">
                            <i class="fa-solid fa-heart feed-like-icon"></i>
                            <span class="like-count-num"><?php echo (int)$post['like_count']; ?></span> <?php echo __('likes_label'); ?>
                        </div>
                        <div class="comments-count-display">
                            <span class="comment-count-text"><?php echo (int)$post['comment_count']; ?> <?php echo __('comments_label'); ?></span>
                        </div>
                    </div>

                    <div class="action-bar">
                        <div class="like-action-wrap">
                            <?php $display_reaction = in_array($post['user_reaction'], $allowed_reactions, true) ? $post['user_reaction'] : '❤️'; ?>
                            <button type="button" class="action-button like-btn <?php echo $post['is_liked'] ? 'liked' : ''; ?>" data-post-id="<?php echo (int)$post['id']; ?>" data-reaction="<?php echo htmlspecialchars($display_reaction, ENT_QUOTES, 'UTF-8'); ?>" aria-expanded="false" aria-controls="reactionPicker<?php echo (int)$post['id']; ?>" title="Long press to choose a reaction">
                                <span class="like-reaction action-icon"><?php if ($post['is_liked']): ?><?php echo htmlspecialchars($display_reaction, ENT_QUOTES, 'UTF-8'); ?><?php else: ?><i class="fa-regular fa-heart"></i><?php endif; ?></span>
                                <span><?php echo __('like_button'); ?></span>
                            </button>
                            <div class="reaction-picker" id="reactionPicker<?php echo (int)$post['id']; ?>" role="group" aria-label="Post reactions">
                                <?php foreach ($allowed_reactions as $reaction): ?>
                                    <button type="button" class="reaction-option" data-post-id="<?php echo (int)$post['id']; ?>" data-reaction="<?php echo htmlspecialchars($reaction, ENT_QUOTES, 'UTF-8'); ?>" aria-label="<?php echo htmlspecialchars($reaction, ENT_QUOTES, 'UTF-8'); ?>"><?php echo $reaction; ?></button>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <button type="button" class="action-button comment-btn" data-ui-action="feed-open-comments" data-post-id="<?php echo (int)$post['id']; ?>">
                            <i class="fa-regular fa-comment action-icon"></i>
                            <span><?php echo __('comment_button'); ?></span>
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </main>

    <!-- Lightbox Modal Viewer -->
    <div class="lightbox-modal" id="lightboxModal">
        <span class="lightbox-close" data-ui-action="feed-close-lightbox"><i class="fa-solid fa-xmark"></i></span>
        <span class="lightbox-prev" data-ui-action="feed-previous-lightbox"><i class="fa-solid fa-chevron-left"></i></span>
        <span class="lightbox-next" data-ui-action="feed-next-lightbox"><i class="fa-solid fa-chevron-right"></i></span>
        <div id="lightboxContainer"></div>
    </div>

    <!-- COMMENT MODAL STRUCTURE -->
    <div class="comment-overlay" id="commentOverlay" data-ui-action="feed-close-comments"></div>
    <div class="comment-sheet" id="commentSheet">
        <div class="sheet-header">
            <span><?php echo __('comments_title'); ?></span>
            <i class="fa-solid fa-xmark sheet-close-btn" data-ui-action="feed-close-comments"></i>
        </div>
        <div class="sheet-body" id="sheetBody"></div>
        <div class="reply-target-tag" id="replyTargetTag">
            <span><?php echo __('reply_to'); ?>: <strong id="replyTargetUser"></strong></span>
            <i class="fa-solid fa-xmark reply-cancel-icon" data-ui-action="feed-cancel-reply"></i>
        </div>
        <div class="sheet-footer">
            <form class="comment-form" id="commentForm">
                <input type="hidden" id="sheetPostId" value="">
                <input type="hidden" id="sheetParentId" value="">
                <input type="text" class="comment-input" id="commentInput" placeholder="<?php echo __('add_comment_placeholder'); ?>" required autocomplete="off">
                <div class="comment-emoji-wrap">
                    <button type="button" class="comment-emoji-toggle" id="commentEmojiToggle" aria-label="Add emoji" aria-expanded="false" aria-controls="commentEmojiPicker">😊</button>
                    <div class="comment-emoji-picker" id="commentEmojiPicker" role="group" aria-label="Choose an emoji">
                        <?php foreach (['😊', '😀', '😁', '😂', '🤣', '🥰', '😍', '😘', '😎', '🤩', '🥳', '🤔', '😮', '😢', '😭', '😡', '🙏', '👏', '🙌', '👍', '👎', '❤️', '🧡', '💛', '💚', '💙', '💜', '🔥', '🎉', '💯', '💪', '✨', '👀', '🌸', '☀️', '☕'] as $emoji): ?>
                            <button type="button" class="comment-emoji-option" data-emoji="<?php echo htmlspecialchars($emoji, ENT_QUOTES, 'UTF-8'); ?>"><?php echo $emoji; ?></button>
                        <?php endforeach; ?>
                    </div>
                </div>
                <button type="submit" class="comment-send-btn"><?php echo __('post_comment_btn'); ?></button>
            </form>
        </div>
    </div>

    <div id="page-config" hidden data-config="<?php echo htmlspecialchars(json_encode(['values' => [__('btn_reply'), __('no_comments'), __('comments_label'), __('dark_mode'), __('light_mode'), $current_username, $current_user_avatar, $encoded_data]], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP), ENT_QUOTES, 'UTF-8'); ?>"></div>
    <script src="../js/page-config.js"></script>
    <script src="../js/index.js"></script>
    <script src="../js/translation.js"></script>
    <script src="../js/actions.js"></script>
    <script src="../js/badge.js"></script>
    <?php include __DIR__ . '/suggestions_widget.php'; ?>
</body>

</html>