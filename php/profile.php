<?php
// profile.php - Nexagram Profile with Multi-Media Edit & Profile Management
session_start();
require_once 'config.php';
require_once 'lang.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$logged_in_user_id =$_SESSION['user_id'];
$profile_user_id = isset($_GET['id']) ? (int)$_GET['id'] :$logged_in_user_id;
$is_own_profile = ($logged_in_user_id ===$profile_user_id);

// --- HANDLE PROFILE DETAILS EDIT ACTION ---
if ($is_own_profile && isset($_POST['action']) &&$_POST['action'] === 'update_profile') {
    $new_bio = trim($_POST['bio'] ?? '');
    $new_department = trim($_POST['department'] ?? '');
    $new_class_name = trim($_POST['class_name'] ?? '');
    $new_school_name = trim($_POST['school_name'] ?? '');

    $stmt =$pdo->prepare("
        UPDATE users 
        SET bio = ?, department = ?, class_name = ?, school_name = ? 
        WHERE id = ?
    ");
    $stmt->execute([
        $new_bio,$new_department !== '' ? $new_department : null,$new_class_name !== '' ? $new_class_name : null,$new_school_name !== '' ? $new_school_name : null,$logged_in_user_id
    ]);

    header("Location: profile.php?id=" . $profile_user_id);
    exit();
}

// --- HANDLE POST DELETE ACTION ---
if ($is_own_profile && isset($_POST['action']) &&$_POST['action'] === 'delete_post') {
    $delete_post_id = (int)$_POST['post_id'];
    
    // Fetch media path to remove file if needed
    $stmt =$pdo->prepare("SELECT * FROM posts WHERE id = ? AND user_id = ?");
    $stmt->execute([$delete_post_id,$logged_in_user_id]);
    $post_to_delete =$stmt->fetch();

    if ($post_to_delete) {
        // Delete post_media entries & physical files
        try {
            $m_stmt =$pdo->prepare("SELECT media_path FROM post_media WHERE post_id = ?");
            $m_stmt->execute([$delete_post_id]);
            $media_files =$m_stmt->fetchAll(PDO::FETCH_COLUMN);
            foreach ($media_files as$m_file) {
                $f_remove = __DIR__ . '/../' . ltrim($m_file, '/');
                if (file_exists($f_remove)) @unlink($f_remove);
            }
            $pdo->prepare("DELETE FROM post_media WHERE post_id = ?")->execute([$delete_post_id]);
        } catch (\PDOException $e) {}

        $raw_file =$post_to_delete['image_url'] ?? $post_to_delete['image_path'] ?? $post_to_delete['image'] ?? $post_to_delete['file_path'] ?? $post_to_delete['media_url'] ?? null;
        if ($raw_file) {
            $file_to_remove = (strpos($raw_file, 'uploads/') === 0) ? $raw_file : 'uploads/' . ltrim($raw_file, '/');
            $file_to_remove = __DIR__ . '/../' .$file_to_remove;
            if (file_exists($file_to_remove)) @unlink($file_to_remove);
        }

        // Delete comments, likes, and the post record
        $pdo->prepare("DELETE FROM comments WHERE post_id = ?")->execute([$delete_post_id]);$pdo->prepare("DELETE FROM likes WHERE post_id = ?")->execute([$delete_post_id]);$pdo->prepare("DELETE FROM posts WHERE id = ? AND user_id = ?")->execute([$delete_post_id,$logged_in_user_id]);
    }
    
    header("Location: profile.php?id=" . $profile_user_id);
    exit();
}

// --- HANDLE POST EDIT ACTION (CAPTION, VISIBILITY, MULTI-MEDIA ADD & REMOVE) ---
if ($is_own_profile && isset($_POST['action']) && $_POST['action'] === 'edit_post') {$edit_post_id = (int)$_POST['post_id'];$new_caption = trim($_POST['caption'] ?? '');$new_visibility = $_POST['visibility'] ?? 'public';$remove_media = isset($_POST['remove_media']) &&$_POST['remove_media'] === '1';

    // Allowed privacy options
    $allowed_visibilities = ['public', 'followers', 'friends', 'only_me'];
    if (!in_array($new_visibility, $allowed_visibilities)) {$new_visibility = 'public';
    }

    $stmt =$pdo->prepare("SELECT * FROM posts WHERE id = ? AND user_id = ?");
    $stmt->execute([$edit_post_id,$logged_in_user_id]);
    $existing_post =$stmt->fetch();

    if ($existing_post) {$target_dir = __DIR__ . '/../uploads/';
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        // 1. Check if user selected "Remove All Media"
        if ($remove_media) {
            try {
                $m_stmt =$pdo->prepare("SELECT media_path FROM post_media WHERE post_id = ?");
                $m_stmt->execute([$edit_post_id]);
                $media_files =$m_stmt->fetchAll(PDO::FETCH_COLUMN);
                foreach ($media_files as$m_file) {
                    $f_remove = __DIR__ . '/../' . ltrim($m_file, '/');
                    if (file_exists($f_remove)) @unlink($f_remove);
                }
                $pdo->prepare("DELETE FROM post_media WHERE post_id = ?")->execute([$edit_post_id]);
            } catch (\PDOException $e) {}

            $raw_file =$existing_post['image_url'] ?? $existing_post['image_path'] ?? $existing_post['image'] ?? $existing_post['file_path'] ?? $existing_post['media_url'] ?? null;
            if ($raw_file) {
                $old_file = (strpos($raw_file, 'uploads/') === 0) ? $raw_file : 'uploads/' . ltrim($raw_file, '/');
                $old_file_full = __DIR__ . '/../' .$old_file;
                if (file_exists($old_file_full)) @unlink($old_file_full);
            }
            
            // Clear legacy column in posts
            $column_to_update = 'image_url';
            if (array_key_exists('image_path', $existing_post))$column_to_update = 'image_path';
            elseif (array_key_exists('image', $existing_post))$column_to_update = 'image';
            elseif (array_key_exists('file_path', $existing_post))$column_to_update = 'file_path';
            elseif (array_key_exists('media_url', $existing_post))$column_to_update = 'media_url';

            $pdo->prepare("UPDATE posts SET {$column_to_update} = NULL WHERE id = ?")->execute([$edit_post_id]);
        }

        // 2. Upload multiple new photos/videos
        if (isset($_FILES['media']) && !empty($_FILES['media']['name'][0])) {$first_uploaded = null;

            foreach ($_FILES['media']['name'] as $key =>$name) {
                if ($_FILES['media']['error'][$key] === UPLOAD_ERR_OK) {$file_tmp = $_FILES['media']['tmp_name'][$key];
                    $file_name = time() . '_' .$key . '_' . basename($name);$target_file = $target_dir .$file_name;

                    if (move_uploaded_file($file_tmp,$target_file)) {
                        $rel_path = 'uploads/' .$file_name;
                        if (!$first_uploaded) $first_uploaded =$rel_path;

                        try {
                            $pdo->prepare("INSERT INTO post_media (post_id, media_path) VALUES (?, ?)")->execute([$edit_post_id,$rel_path]);
                        } catch (\PDOException $e) {}
                    }
                }
            }

            // Sync legacy column with first new upload if empty
            if ($first_uploaded) {$column_to_update = 'image_url';
                if (array_key_exists('image_path', $existing_post))$column_to_update = 'image_path';
                elseif (array_key_exists('image', $existing_post))$column_to_update = 'image';
                elseif (array_key_exists('file_path', $existing_post))$column_to_update = 'file_path';
                elseif (array_key_exists('media_url', $existing_post))$column_to_update = 'media_url';

                $pdo->prepare("UPDATE posts SET {$column_to_update} = ? WHERE id = ?")->execute([$first_uploaded,$edit_post_id]);
            }
        }

        // 3. Update Caption & Visibility
        $update_sql = "UPDATE posts SET caption = ?, visibility = ? WHERE id = ? AND user_id = ?";
        $stmt =$pdo->prepare($update_sql);$stmt->execute([$new_caption,$new_visibility, $edit_post_id,$logged_in_user_id]);
    }

    header("Location: profile.php?id=" . $profile_user_id);
    exit();
}

// 1. Fetch User Info
$user_stmt =$pdo->prepare("SELECT id, username, profile_image, bio, role, user_code, department, class_name, school_name FROM users WHERE id = ?");
$user_stmt->execute([$profile_user_id]);
$user =$user_stmt->fetch();

if (!$user) {
    header("Location: index.php");
    exit();
}

// 2. Metrics
$posts_count =$pdo->prepare("SELECT COUNT(*) FROM posts WHERE user_id = ?");
$posts_count->execute([$profile_user_id]);
$posts_count =$posts_count->fetchColumn();

$followers_count =$pdo->prepare("SELECT COUNT(*) FROM follows WHERE following_id = ?");
$followers_count->execute([$profile_user_id]);
$followers_count =$followers_count->fetchColumn();

$following_count =$pdo->prepare("SELECT COUNT(*) FROM follows WHERE follower_id = ?");
$following_count->execute([$profile_user_id]);
$following_count =$following_count->fetchColumn();

$friends_count =$pdo->prepare("SELECT COUNT(*) FROM friend_requests WHERE (sender_id = ? OR receiver_id = ?) AND status = 'accepted'");
$friends_count->execute([$profile_user_id,$profile_user_id]);
$friends_count =$friends_count->fetchColumn();

// 3. User Posts
$posts_stmt =$pdo->prepare("
    SELECT posts.*, 
    (SELECT COUNT(*) FROM likes WHERE likes.post_id = posts.id) as like_count,
    (SELECT COUNT(*) FROM comments WHERE comments.post_id = posts.id) as comment_count
    FROM posts 
    WHERE user_id = ? 
    ORDER BY created_at DESC
");
$posts_stmt->execute([$profile_user_id]);
$user_posts =$posts_stmt->fetchAll();

function resolveImagePath($fileName) {
    if (empty($fileName)) return null;
    if (strpos($fileName, 'http') === 0) return$fileName;

    $cleanName = ltrim($fileName, '/');
    if (file_exists(__DIR__ . '/../' . $cleanName)) return '../' .$cleanName;
    if (file_exists(__DIR__ . '/../uploads/' . $cleanName)) return '../uploads/' .$cleanName;

    return (strpos($cleanName, 'uploads/') === 0) ? '../' . $cleanName : '../uploads/' .$cleanName;
}
?>

<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($current_lang); ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nexagram • <?php echo htmlspecialchars($user['username']); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        const storedTheme = localStorage.getItem("theme") || "dark";
        document.documentElement.setAttribute("data-theme", storedTheme);
    </script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');

        :root {
            --bg-color: #0b0f17;
            --card-bg: rgba(23, 29, 45, 0.65);
            --card-border: rgba(255, 255, 255, 0.08);
            --text-main: #f8fafc;
            --text-sub: #94a3b8;
            --sidebar-bg: #111827;
            --hover-bg: rgba(255, 255, 255, 0.06);
            --accent-gradient: linear-gradient(135deg, #ec4899, #8b5cf6, #3b82f6);
            --glass-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.37);
        }

        [data-theme="light"] {
            --bg-color: #f1f5f9;
            --card-bg: rgba(255, 255, 255, 0.85);
            --card-border: rgba(0, 0, 0, 0.08);
            --text-main: #0f172a;
            --text-sub: #64748b;
            --sidebar-bg: #ffffff;
            --hover-bg: rgba(0, 0, 0, 0.05);
            --glass-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.07);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Plus Jakarta Sans', -apple-system, sans-serif; }
        body { background-color: var(--bg-color); color: var(--text-main); display: flex; min-height: 100vh; }

        /* Sidebar Navigation */
        .sidebar { position: fixed; left: 0; top: 0; height: 100vh; width: 250px; border-right: 1px solid var(--card-border); padding: 30px 18px; display: flex; flex-direction: column; background-color: var(--sidebar-bg); z-index: 100; }
        .logo { font-size: 28px; font-weight: 800; background: var(--accent-gradient); -webkit-background-clip: text; -webkit-text-fill-color: transparent; margin-bottom: 40px; padding-left: 10px; }
        .nav-menu { list-style: none; display: flex; flex-direction: column; gap: 6px; height: 100%; }
        .nav-item a { text-decoration: none; color: var(--text-main); display: flex; align-items: center; gap: 14px; padding: 12px 16px; border-radius: 12px; font-size: 15px; font-weight: 600; transition: background 0.2s; }
        .nav-item:hover a, .nav-item.active a { background-color: var(--hover-bg); }
        .nav-item i { font-size: 18px; width: 22px; }

        .settings-nav-item { margin-top: auto; }

        .theme-btn { cursor: pointer; display: flex; align-items: center; gap: 14px; padding: 12px 16px; border-radius: 12px; font-size: 15px; font-weight: 600; transition: background 0.2s; }
        .theme-btn:hover { background-color: var(--hover-bg); }
        .logout-item a { color: #ef4444 !important; }

        /* Main Content */
        .main-content { margin-left: 250px; width: calc(100% - 250px); display: flex; justify-content: center; padding: 40px 20px; }
        .profile-wrapper { max-width: 935px; width: 100%; }

        .profile-card { background: var(--card-bg); backdrop-filter: blur(16px); border: 1px solid var(--card-border); box-shadow: var(--glass-shadow); border-radius: 24px; padding: 36px 40px; display: flex; align-items: flex-start; gap: 48px; margin-bottom: 36px; }
        .avatar-ring { width: 140px; height: 140px; border-radius: 50%; padding: 4px; background: var(--accent-gradient); flex-shrink: 0; }
        .avatar-inner { width: 100%; height: 100%; border-radius: 50%; overflow: hidden; background: var(--sidebar-bg); display: flex; align-items: center; justify-content: center; font-size: 42px; font-weight: 800; color: var(--text-main); }
        .avatar-inner img { width: 100%; height: 100%; object-fit: cover; }
        .profile-info { display: flex; flex-direction: column; gap: 16px; flex-grow: 1; }
        .header-top { display: flex; align-items: center; gap: 16px; flex-wrap: wrap; }
        .username { font-size: 26px; font-weight: 800; }
        
        .role-badge { padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; background: rgba(59, 130, 246, 0.15); color: #60a5fa; border: 1px solid rgba(59, 130, 246, 0.3); }
        .role-badge.teacher { background: rgba(236, 72, 153, 0.15); color: #f472b6; border-color: rgba(236, 72, 153, 0.3); }

        .btn-edit-profile { padding: 8px 18px; border-radius: 12px; font-weight: 700; font-size: 13.5px; background: var(--hover-bg); border: 1px solid var(--card-border); color: var(--text-main); cursor: pointer; transition: background 0.2s; }
        .btn-edit-profile:hover { background: rgba(255, 255, 255, 0.12); }
        
        .stats-row { display: flex; gap: 32px; margin: 4px 0; }
        .stat-box { display: flex; align-items: center; gap: 6px; text-decoration: none; color: var(--text-main); font-size: 15px; }
        .stat-val { font-weight: 800; font-size: 17px; }
        .stat-lbl { color: var(--text-sub); font-weight: 500; }
        
        .user-bio { font-size: 14.5px; color: var(--text-main); line-height: 1.5; }
        
        /* Academic Tags Styling */
        .school-details-list { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 6px; }
        .detail-chip { font-size: 13px; font-weight: 600; color: var(--text-sub); background: var(--hover-bg); border: 1px solid var(--card-border); padding: 6px 12px; border-radius: 10px; display: flex; align-items: center; gap: 8px; }
        .detail-chip i { color: #3b82f6; font-size: 14px; }

        .posts-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; }
        .grid-card { aspect-ratio: 1 / 1; border-radius: 16px; overflow: hidden; position: relative; background: var(--card-bg); border: 1px solid var(--card-border); display: block; text-decoration: none; }
        .grid-media { width: 100%; height: 100%; object-fit: cover; transition: transform 0.4s ease; }
        .grid-card:hover .grid-media { transform: scale(1.06); }
        .text-only-card { width: 100%; height: 100%; padding: 20px; display: flex; align-items: center; justify-content: center; text-align: center; font-weight: 600; font-size: 14px; color: var(--text-main); background: var(--hover-bg); }

        /* Modern Grid Overlay Controls */
        .grid-overlay { position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.65); backdrop-filter: blur(3px); display: flex; flex-direction: column; justify-content: space-between; padding: 16px; opacity: 0; transition: opacity 0.25s ease; color: #ffffff; z-index: 2; }
        .grid-card:hover .grid-overlay { opacity: 1; }

        .overlay-top-actions { display: flex; justify-content: space-between; align-items: center; }
        .visibility-badge { font-size: 13px; font-weight: 600; background: rgba(0,0,0,0.5); padding: 5px 10px; border-radius: 20px; display: flex; align-items: center; gap: 6px; }
        .top-right-btns { display: flex; gap: 8px; }
        .action-btn { background: rgba(255, 255, 255, 0.2); border: none; color: #fff; width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: background 0.2s, transform 0.2s; }
        .action-btn:hover { background: rgba(255, 255, 255, 0.4); transform: scale(1.1); }
        .action-btn.btn-delete:hover { background: #ef4444; }

        .overlay-center-stats { display: flex; justify-content: center; align-items: center; gap: 24px; font-weight: 800; font-size: 16px; margin-top: auto; margin-bottom: auto; }
        .overlay-item { display: flex; align-items: center; gap: 8px; }
        .card-caption-tag { font-size: 13px; font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; background: rgba(0,0,0,0.4); padding: 6px 10px; border-radius: 8px; }

        /* Modal Styling */
        .modal-overlay { position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.75); backdrop-filter: blur(5px); display: none; align-items: center; justify-content: center; z-index: 1000; }
        .modal-box { background: var(--sidebar-bg); border: 1px solid var(--card-border); border-radius: 20px; width: 100%; max-width: 520px; padding: 24px; box-shadow: var(--glass-shadow); color: var(--text-main); }
        .modal-title { font-size: 18px; font-weight: 800; margin-bottom: 16px; display: flex; justify-content: space-between; align-items: center; }
        .modal-close { cursor: pointer; color: var(--text-sub); }
        .form-group { margin-bottom: 16px; }
        .form-label { display: block; font-size: 13px; font-weight: 700; margin-bottom: 6px; color: var(--text-sub); }
        .modal-input, .modal-textarea, .modal-select { width: 100%; background: var(--hover-bg); border: 1px solid var(--card-border); border-radius: 12px; color: var(--text-main); padding: 12px; font-size: 14px; outline: none; }
        .modal-textarea { height: 90px; resize: none; }
        .modal-select option { background: var(--sidebar-bg); color: var(--text-main); }
        
        /* Multi-Media Gallery Grid Preview inside Modal */
        .media-preview-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; width: 100%; max-height: 180px; overflow-y: auto; padding: 8px; border-radius: 12px; background: var(--hover-bg); border: 1px dashed var(--card-border); margin-bottom: 12px; }
        .media-preview-grid img, .media-preview-grid video { width: 100%; aspect-ratio: 1/1; object-fit: cover; border-radius: 8px; }
        .media-controls-row { display: flex; gap: 10px; }
        .file-upload-btn { flex: 1; display: flex; align-items: center; justify-content: center; gap: 8px; padding: 10px; background: var(--hover-bg); border: 1px solid var(--card-border); border-radius: 12px; cursor: pointer; font-size: 13px; font-weight: 600; color: var(--text-main); transition: background 0.2s; }
        .file-upload-btn:hover { background: rgba(255, 255, 255, 0.1); }
        .btn-remove-media { padding: 10px 14px; background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.3); color: #ef4444; border-radius: 12px; cursor: pointer; font-size: 13px; font-weight: 700; display: flex; align-items: center; gap: 6px; }
        .btn-remove-media:hover { background: #ef4444; color: #fff; }
        
        .modal-actions { display: flex; justify-content: flex-end; gap: 12px; margin-top: 20px; }
        .btn-cancel { padding: 10px 18px; border-radius: 10px; background: transparent; border: 1px solid var(--card-border); color: var(--text-main); cursor: pointer; font-weight: 600; }
        .btn-save { padding: 10px 18px; border-radius: 10px; background: #3b82f6; border: none; color: #fff; cursor: pointer; font-weight: 700; }
    </style>
</head>

<body>

    <nav class="sidebar">
        <a href="index.php" class="logo logo-link">Nexagram</a>
        <ul class="nav-menu">
            <li class="nav-item"><a href="index.php"><i class="fa-solid fa-house"></i> <?php echo __('nav_home'); ?></a></li>
            <li class="nav-item"><a href="friends.php"><i class="fa-solid fa-user-group"></i> <?php echo __('nav_friends'); ?></a></li>
            <li class="nav-item"><a href="users.php"><i class="fa-solid fa-users"></i> <?php echo __('nav_users'); ?></a></li>
            <li class="nav-item"><a href="#"><i class="fa-solid fa-paper-plane"></i> <?php echo __('nav_messages'); ?></a></li>
            <li class="nav-item"><a href="create_post.php"><i class="fa-solid fa-square-plus"></i> <?php echo __('nav_create'); ?></a></li>
            <li class="nav-item active"><a href="profile.php"><i class="fa-solid fa-user"></i> <?php echo __('nav_profile'); ?></a></li>

            <li class="nav-item settings-nav-item">
                <a href="settings.php"><i class="fa-solid fa-gear"></i> <?php echo __('nav_settings'); ?></a>
            </li>

            <li class="theme-btn" id="theme-toggle">
                <i class="fa-solid fa-moon" id="theme-icon"></i>
                <span id="theme-text"><?php echo __('dark_mode'); ?></span>
            </li>
            <li class="nav-item logout-item"><a href="logout.php"><i class="fa-solid fa-right-from-bracket"></i> <?php echo __('nav_logout'); ?></a></li>
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
                        <?php if (!empty($user['role'])): ?>
                            <span class="role-badge <?php echo htmlspecialchars($user['role']); ?>">
                                <?php echo htmlspecialchars(ucfirst($user['role'])); ?>
                            </span>
                        <?php endif; ?>
                        
                        <?php if ($is_own_profile): ?>
                            <button type="button" class="btn-edit-profile" onclick="openProfileEditModal()"><?php echo __('edit_profile'); ?></button>
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

                    <!-- Academic Info Chips -->
                    <div class="school-details-list">
                        <?php if (!empty($user['user_code'])): ?>
                            <div class="detail-chip">
                                <i class="fa-solid fa-id-card"></i>
                                <span>ID: <?php echo htmlspecialchars($user['user_code']); ?></span>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($user['department'])): ?>
                            <div class="detail-chip">
                                <i class="fa-solid fa-graduation-cap"></i>
                                <span><?php echo htmlspecialchars($user['department']); ?></span>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($user['class_name'])): ?>
                            <div class="detail-chip">
                                <i class="fa-solid fa-users-rectangle"></i>
                                <span>Class: <?php echo htmlspecialchars($user['class_name']); ?></span>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($user['school_name'])): ?>
                            <div class="detail-chip">
                                <i class="fa-solid fa-school"></i>
                                <span><?php echo htmlspecialchars($user['school_name']); ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Posts Grid -->
            <div class="posts-grid">
                <?php if (!empty($user_posts)): ?>
                    <?php foreach ($user_posts as $post): ?>
                        <?php 
                            // Fetch all media items for post
                            $post_media_items = [];
                            try {
                                $pm_stmt = $pdo->prepare("SELECT media_path FROM post_media WHERE post_id = ? ORDER BY id ASC");
                                $pm_stmt->execute([$post['id']]);
                                $fetched = $pm_stmt->fetchAll(PDO::FETCH_COLUMN);
                                foreach ($fetched as $f) {
                                    $res = resolveImagePath($f);
                                    if ($res) $post_media_items[] = $res;
                                }
                            } catch (\PDOException $e) {}

                            if (empty($post_media_items)) {
                                $raw_file = $post['image_url'] ?? $post['image_path'] ?? $post['image'] ?? $post['file_path'] ?? $post['media_url'] ?? null;
                                $single = resolveImagePath($raw_file);
                                if ($single) $post_media_items[] = $single;
                            }

                            $caption = $post['caption'] ?? $post['content'] ?? '';
                            $first_media = $post_media_items[0] ?? null;
                            $visibility = $post['visibility'] ?? 'public';
                        ?>
                        <!-- Clicking card navigates to index.php with target post ID -->
                        <a href="index.php?post_id=<?php echo $post['id']; ?>#post_<?php echo $post['id']; ?>" class="grid-card">
                            <?php if (!empty($first_media)): ?>
                                <?php if (preg_match('/\.(mp4|webm|ogg)$/i', $first_media)): ?>
                                    <video src="<?php echo htmlspecialchars($first_media); ?>" class="grid-media"></video>
                                <?php else: ?>
                                    <img src="<?php echo htmlspecialchars($first_media); ?>" class="grid-media" alt="Post Image">
                                <?php endif; ?>
                            <?php else: ?>
                                <div class="text-only-card">
                                    <span><?php echo htmlspecialchars($caption); ?></span>
                                </div>
                            <?php endif; ?>

                            <!-- Hover Overlay with Privacy, Edit & Delete Controls -->
                            <div class="grid-overlay">
                                <div class="overlay-top-actions">
                                    <div class="visibility-badge">
                                        <?php if ($visibility === 'followers'): ?>
                                            <i class="fa-solid fa-users"></i> Followers
                                        <?php elseif ($visibility === 'friends'): ?>
                                            <i class="fa-solid fa-user-group"></i> Friends
                                        <?php elseif ($visibility === 'only_me'): ?>
                                            <i class="fa-solid fa-lock"></i> Only Me
                                        <?php else: ?>
                                            <i class="fa-solid fa-globe"></i> Public
                                        <?php endif; ?>
                                    </div>

                                    <?php if ($is_own_profile): ?>
                                        <div class="top-right-btns">
                                            <button type="button" class="action-btn" title="<?php echo __('edit_caption_tooltip'); ?>" onclick="event.stopPropagation(); event.preventDefault(); openEditModal(<?php echo (int)$post['id']; ?>, <?php echo htmlspecialchars(json_encode($caption, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP), ENT_QUOTES, 'UTF-8'); ?>, <?php echo htmlspecialchars(json_encode($visibility, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP), ENT_QUOTES, 'UTF-8'); ?>, <?php echo htmlspecialchars(json_encode($post_media_items, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP), ENT_QUOTES, 'UTF-8'); ?>)">
                                                <i class="fa-solid fa-pen"></i>
                                            </button>
                                            <form method="POST" onclick="event.stopPropagation();" onsubmit="return confirm('<?php echo addslashes(__('confirm_delete_post')); ?>');">
                                                <input type="hidden" name="action" value="delete_post">
                                                <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                                                <button type="submit" class="action-btn btn-delete" title="<?php echo __('delete_post_tooltip'); ?>">
                                                    <i class="fa-solid fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="overlay-center-stats">
                                    <div class="overlay-item"><i class="fa-solid fa-heart"></i> <?php echo $post['like_count']; ?></div>
                                    <div class="overlay-item"><i class="fa-solid fa-comment"></i> <?php echo $post['comment_count']; ?></div>
                                </div>

                                <?php if (!empty($caption)): ?>
                                    <div class="card-caption-tag"><?php echo htmlspecialchars($caption); ?></div>
                                <?php endif; ?>
                            </div>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

        </div>
    </main>

    <!-- Modal for Editing Profile Info -->
    <div class="modal-overlay" id="profileEditModal">
        <div class="modal-box">
            <div class="modal-title">
                <span>Edit Profile Details</span>
                <i class="fa-solid fa-xmark modal-close" onclick="closeProfileEditModal()"></i>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="update_profile">
                
                <div class="form-group">
                    <label class="form-label">Bio</label>
                    <textarea name="bio" class="modal-textarea" placeholder="Tell something about yourself..."><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                </div>

                <div class="form-group">
                    <label class="form-label">Department</label>
                    <select name="department" class="modal-select">
                        <option value="" <?php echo empty($user['department']) ? 'selected' : ''; ?>>Select Department</option>
                        <option value="Global Systems" <?php echo ($user['department'] ?? '') === 'Global Systems' ? 'selected' : ''; ?>>Global Systems</option>
                        <option value="Information Technology" <?php echo ($user['department'] ?? '') === 'Information Technology' ? 'selected' : ''; ?>>Information Technology</option>
                        <option value="Business Management" <?php echo ($user['department'] ?? '') === 'Business Management' ? 'selected' : ''; ?>>Business Management</option>
                        <option value="Digital Design" <?php echo ($user['department'] ?? '') === 'Digital Design' ? 'selected' : ''; ?>>Digital Design</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Class Name</label>
                    <input type="text" name="class_name" class="modal-input" value="<?php echo htmlspecialchars($user['class_name'] ?? ''); ?>" placeholder="e.g. GS11">
                </div>

                <div class="form-group">
                    <label class="form-label">School Name</label>
                    <input type="text" name="school_name" class="modal-input" value="<?php echo htmlspecialchars($user['school_name'] ?? ''); ?>" placeholder="e.g. 横浜システム工学院専門学校">
                </div>

                <div class="modal-actions">
                    <button type="button" class="btn-cancel" onclick="closeProfileEditModal()"><?php echo __('btn_cancel'); ?></button>
                    <button type="submit" class="btn-save"><?php echo __('btn_save'); ?></button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal for Editing Post Caption, Privacy, Multi-Media Upload & Removal -->
    <div class="modal-overlay" id="editModal">
        <div class="modal-box">
            <div class="modal-title">
                <span>Edit Post</span>
                <i class="fa-solid fa-xmark modal-close" onclick="closeEditModal()"></i>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="edit_post">
                <input type="hidden" name="post_id" id="modalPostId">
                <input type="hidden" name="remove_media" id="modalRemoveMedia" value="0">

                <!-- Multi-Media Preview & Upload Field -->
                <div class="form-group">
                    <label class="form-label">Photos / Videos</label>
                    <div class="media-preview-grid" id="modalMediaPreviewGrid">
                        <span style="color: var(--text-sub); font-size: 13px; grid-column: 1 / -1; text-align: center;">No Media Attached</span>
                    </div>
                    
                    <div class="media-controls-row">
                        <label for="modalFileInput" class="file-upload-btn">
                            <i class="fa-solid fa-images"></i> Add More Photos / Videos
                        </label>
                        <button type="button" class="btn-remove-media" id="btnRemoveMedia" onclick="markMediaForRemoval()" style="display: none;">
                            <i class="fa-solid fa-trash"></i> Remove All
                        </button>
                    </div>
                    <!-- multiple attribute allows choosing multiple photos at once -->
                    <input type="file" name="media[]" id="modalFileInput" accept="image/*,video/*" multiple style="display: none;" onchange="previewSelectedFiles(this)">
                </div>

                <div class="form-group">
                    <label class="form-label">Caption</label>
                    <textarea name="caption" id="modalCaption" class="modal-textarea" placeholder="<?php echo __('write_caption_placeholder'); ?>"></textarea>
                </div>

                <div class="form-group">
                    <label class="form-label">Privacy Setting</label>
                    <select name="visibility" id="modalVisibility" class="modal-select">
                        <option value="public">🌐 Public</option>
                        <option value="followers">👥 Followers-Only</option>
                        <option value="friends">🤝 Friends-Only</option>
                        <option value="only_me">🔒 Only Me</option>
                    </select>
                </div>

                <div class="modal-actions">
                    <button type="button" class="btn-cancel" onclick="closeEditModal()"><?php echo __('btn_cancel'); ?></button>
                    <button type="submit" class="btn-save"><?php echo __('btn_save'); ?></button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const i18n = {
            darkMode: "<?php echo addslashes(__('dark_mode')); ?>",
            lightMode: "<?php echo addslashes(__('light_mode')); ?>"
        };

        // Edit Profile Details Modal Handlers
        function openProfileEditModal() {
            document.getElementById('profileEditModal').style.display = 'flex';
        }

        function closeProfileEditModal() {
            document.getElementById('profileEditModal').style.display = 'none';
        }

        // Edit Post Modal Handlers (Supports Multiple Photos/Videos)
        function openEditModal(postId, currentCaption, currentVisibility, mediaItems) {
            document.getElementById('modalPostId').value = postId;
            document.getElementById('modalCaption').value = currentCaption;
            document.getElementById('modalVisibility').value = currentVisibility || 'public';
            document.getElementById('modalFileInput').value = ''; 
            document.getElementById('modalRemoveMedia').value = '0';

            const previewGrid = document.getElementById('modalMediaPreviewGrid');
            const removeBtn = document.getElementById('btnRemoveMedia');

            if (mediaItems && mediaItems.length > 0) {
                previewGrid.innerHTML = '';
                mediaItems.forEach(src => {
                    const isVideo = /\.(mp4|webm|ogg)$/i.test(src);
                    if (isVideo) {
                        previewGrid.innerHTML += `<video src="${src}" controls></video>`;
                    } else {
                        previewGrid.innerHTML += `<img src="${src}" alt="Preview">`;
                    }
                });
                removeBtn.style.display = 'flex';
            } else {
                previewGrid.innerHTML = `<span style="color: var(--text-sub); font-size: 13px; grid-column: 1 / -1; text-align: center;">No Media Attached</span>`;
                removeBtn.style.display = 'none';
            }

            document.getElementById('editModal').style.display = 'flex';
        }

        function previewSelectedFiles(input) {
            const previewGrid = document.getElementById('modalMediaPreviewGrid');
            const removeBtn = document.getElementById('btnRemoveMedia');
            document.getElementById('modalRemoveMedia').value = '0';

            if (input.files && input.files.length > 0) {
                // Keep existing previews or clear depending on selection
                for (let i = 0; i < input.files.length; i++) {
                    const file = input.files[i];
                    const url = URL.createObjectURL(file);
                    if (file.type.startsWith('video/')) {
                        previewGrid.innerHTML += `<video src="${url}" controls></video>`;
                    } else {
                        previewGrid.innerHTML += `<img src="${url}" alt="Preview">`;
                    }
                }
                removeBtn.style.display = 'flex';
            }
        }

        function markMediaForRemoval() {
            document.getElementById('modalRemoveMedia').value = '1';
            document.getElementById('modalFileInput').value = '';
            document.getElementById('modalMediaPreviewGrid').innerHTML = `<span style="color: var(--text-sub); font-size: 13px; grid-column: 1 / -1; text-align: center;">Media marked for removal</span>`;
            document.getElementById('btnRemoveMedia').style.display = 'none';
        }

        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
        }

        // Theme Switcher
        const themeToggleBtn = document.getElementById('theme-toggle');
        const themeIcon = document.getElementById('theme-icon');
        const themeText = document.getElementById('theme-text');

        function updateThemeUI(theme) {
            document.documentElement.setAttribute('data-theme', theme);
            localStorage.setItem('theme', theme);
            if (theme === 'dark') {
                if (themeIcon) themeIcon.className = 'fa-solid fa-moon';
                if (themeText) themeText.textContent = i18n.darkMode;
            } else {
                if (themeIcon) themeIcon.className = 'fa-solid fa-sun';
                if (themeText) themeText.textContent = i18n.lightMode;
            }
        }

        updateThemeUI(localStorage.getItem('theme') || 'dark');
        if (themeToggleBtn) {
            themeToggleBtn.addEventListener('click', () => {
                const current = document.documentElement.getAttribute('data-theme') || 'dark';
                updateThemeUI(current === 'dark' ? 'light' : 'dark');
            });
        }
    </script>
</body>
</html>