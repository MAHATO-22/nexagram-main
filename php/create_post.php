<?php
// create_post.php - Nexagram Multi-Photo Post Creator
session_start();
require_once 'config.php';
require_once 'lang.php'; // Localized strings and language system logic

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$error_message = '';
$visibility_stmt = $pdo->prepare("SELECT default_visibility FROM users WHERE id = ?");
$visibility_stmt->execute([$user_id]);
$visibility = $visibility_stmt->fetchColumn() ?: 'public';
$allowed_visibilities = ['public', 'followers', 'friends', 'only_me'];
if (!in_array($visibility, $allowed_visibilities, true)) {
    $visibility = 'public';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $caption = trim($_POST['caption'] ?? '');
    $requested_visibility = $_POST['visibility'] ?? $visibility;
    if (!is_string($requested_visibility) || !in_array($requested_visibility, $allowed_visibilities, true)) {
        $requested_visibility = $visibility;
    }
    $visibility = $requested_visibility;

    $uploaded_files = [];

    // Process file uploads
    if (isset($_FILES['media']) && !empty($_FILES['media']['name'][0])) {
        $file_count = count($_FILES['media']['name']);
        $allowed_exts = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'mp4', 'webm', 'mov'];
        $upload_dir = __DIR__ . '/../uploads/';

        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        for ($i = 0; $i < $file_count; $i++) {
            if (isset($_FILES['media']['error'][$i]) && $_FILES['media']['error'][$i] === UPLOAD_ERR_OK) {
                $file_tmp =$_FILES['media']['tmp_name'][$i];$file_name = $_FILES['media']['name'][$i];
                $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

                if (in_array($ext, $allowed_exts, true)) {$new_file_name = uniqid('post_', true) . '_' . $i . '.' .$ext;
                    $destination = $upload_dir . $new_file_name;

                    if (move_uploaded_file($file_tmp, $destination)) {
                        $uploaded_files[] = 'uploads/' . $new_file_name;
                    }
                } else {
                    $error_message = __('err_invalid_format');
                    break;
                }
            }
        }
    }

    if (empty($error_message)) {
       if (!empty($caption) || !empty($uploaded_files)) {
            try {
                $pdo->beginTransaction();

                // 1. Insert main post with visibility setting
                $first_image = !empty($uploaded_files) ?$uploaded_files[0] : null;
                $stmt =$pdo->prepare("INSERT INTO posts (user_id, caption, image_path, visibility, created_at) VALUES (?, ?, ?, ?, NOW())");
                $stmt->execute([$user_id,$caption, $first_image,$visibility]);
                $post_id =$pdo->lastInsertId();

                // 2. Insert individual media records into post_media for multi-photo support
                if (!empty($uploaded_files)) {
                    $media_stmt =$pdo->prepare("INSERT INTO post_media (post_id, media_path) VALUES (?, ?)");
                    foreach ($uploaded_files as $path) {$media_stmt->execute([$post_id,$path]);
                    }
                }

                $pdo->commit();
                header("Location: index.php");
                exit;
            } catch (\PDOException $e) {
                if ($pdo->inTransaction()) {$pdo->rollBack();
                }
                error_log('Post creation failed: ' . $e->getMessage());
                $error_message = __('database_error');
            }
        } else {
            $error_message = __('err_empty_post');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($current_lang, ENT_QUOTES, 'UTF-8'); ?>" dir="<?php echo htmlspecialchars($text_direction, ENT_QUOTES, 'UTF-8'); ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nexagram • <?php echo __('create_post_title'); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="../js/theme-init.js"></script>
    <link rel="stylesheet" href="../css/create_post.css">
    <link rel="stylesheet" href="../css/utilities.css">
    <link rel="stylesheet" href="../css/more-menu.css">
    <link rel="stylesheet" href="../css/suggestions-widget.css">
</head>

<body>

    <!-- Sidebar Component -->
    <nav class="sidebar">
        <a href="index.php" class="logo logo-link">Nexagram</a>
        <ul class="nav-menu">
            <li class="nav-item"><a href="index.php"><i class="fa-solid fa-house"></i> <?php echo __('nav_home'); ?></a></li>
            <li class="nav-item"><a href="friends.php"><i class="fa-solid fa-user-group"></i> <?php echo __('nav_friends'); ?></a></li>
            <li class="nav-item"><a href="users.php"><i class="fa-solid fa-users"></i> <?php echo __('nav_users'); ?></a></li>
            <li class="nav-item"><a href="messages.php"><i class="fa-solid fa-paper-plane"></i> <?php echo __('nav_messages'); ?> <span class="notif-badge is-hidden" id="navMsgBadge">0</span></a></li>
            <li class="nav-item active"><a href="create_post.php"><i class="fa-solid fa-square-plus"></i> <?php echo __('nav_create'); ?></a></li>
            <li class="nav-item"><a href="profile.php"><i class="fa-solid fa-user"></i> <?php echo __('nav_profile'); ?></a></li>
        </ul>

        <!-- More menu -->
        <div class="sidebar-bottom">
            <div class="lang-dropdown-container" id="langDropdownContainer">
                <div class="lang-dropdown-menu" id="langDropdownMenu">
                    <a href="settings.php" class="lang-option">
                        <i class="fa-solid fa-gear"></i> <?php echo __('nav_settings'); ?>
                    </a>
                    <button type="button" class="sidebar-action-btn" id="themeToggleBtn">
                        <i class="fa-solid fa-moon"></i>
                        <span><?php echo __('dark_mode'); ?></span>
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

    <!-- Main Content Area -->
    <main class="main-content">
        <div class="create-card">
            <div class="card-header">
                <i class="fa-solid fa-square-plus"></i>
                <span><?php echo __('create_post_title'); ?></span>
            </div>

            <?php if (!empty($error_message)): ?>
                <div class="alert-error">
                    <i class="fa-solid fa-triangle-exclamation"></i> <?php echo htmlspecialchars($error_message, ENT_QUOTES, 'UTF-8'); ?>
                </div>
            <?php endif; ?>

            <form action="create_post.php" method="POST" enctype="multipart/form-data" id="postForm">
                <!-- File Dropzone -->
                <div class="drop-zone" id="dropZone">
                    <div class="drop-zone-prompt" id="dropZonePrompt">
                        <div class="upload-icon-circle">
                            <i class="fa-solid fa-images"></i>
                        </div>
                        <div class="prompt-title"><?php echo __('dropzone_title'); ?></div>
                        <div class="prompt-subtitle"><?php echo __('dropzone_subtitle'); ?></div>
                    </div>
                </div>

                <!-- File input configured for multiple selections -->
                <input type="file" name="media[]" id="mediaInput" class="file-input" accept="image/*,video/*" multiple>

                <!-- Multi-image Preview Grid -->
                <div class="preview-grid is-hidden" id="previewGrid"></div>

                <!-- Caption Field -->
                <div class="caption-section">
                    <div class="caption-header">
                        <span><?php echo __('caption_label'); ?></span>
                        <span id="charCounter">0 / 500</span>
                    </div>
                    <textarea name="caption" id="captionInput" class="caption-textarea" maxlength="500" placeholder="<?php echo __('write_caption_placeholder'); ?>"></textarea>
                </div>

                <div class="visibility-field" style="margin-top: 18px;">
                    <label for="post-visibility" class="visibility-label"><?php echo __('visibility_target_audience'); ?></label>
                    <select id="post-visibility" name="visibility" class="visibility-select">
                        <?php foreach ($allowed_visibilities as $visibility_option): ?>
                            <option value="<?php echo htmlspecialchars($visibility_option, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $visibility === $visibility_option ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars(__('visibility_' . $visibility_option), ENT_QUOTES, 'UTF-8'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Form Action Buttons -->
                <div class="button-row">
                    <a href="index.php" class="cancel-btn"><?php echo __('btn_cancel'); ?></a>
                    <button type="submit" class="share-btn">
                        <i class="fa-solid fa-paper-plane"></i> <?php echo __('btn_share'); ?>
                    </button>
                </div>
            </form>
        </div>
    </main>

    <div id="page-config" hidden data-config="<?php echo htmlspecialchars(json_encode(['values' => [__('add_more_photos')]], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP), ENT_QUOTES, 'UTF-8'); ?>"></div>
    <script src="../js/page-config.js"></script>
    <script src="../js/create_post.js"></script>
    <script src="../js/badge.js"></script>
    <?php include __DIR__ . '/suggestions_widget.php'; ?>
</body>

</html>