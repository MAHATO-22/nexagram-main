<?php
session_start();
require_once 'config.php';
require_once 'lang.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$message = '';
$success = false;

// လက်ရှိ User အချက်အလက်ယူမည်
$user_stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$user_stmt->execute([$user_id]);
$user = $user_stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_username = trim($_POST['username']);
    $profile_image_path = $user['profile_image']; // မူလ ပုံလမ်းကြောင်း ထိန်းထားမည်

    // 1. Profile Picture Update ပြုလုပ်ခြင်း
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['profile_image']['tmp_name'];
        $fileName = $_FILES['profile_image']['name'];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

        if (in_array($fileExtension, $allowedExtensions)) {
            // Folder မရှိပါက အလိုအလျောက် ဆောက်မည်
            $uploadFileDir = __DIR__ . '/../uploads/';
            if (!is_dir($uploadFileDir)) {
                mkdir($uploadFileDir, 0755, true);
            }

            // ဖိုင်နာမည် မထပ်အောင် နာမည်အသစ်ပေးမည်
            $newFileName = 'avatar_' . $user_id . '_' . time() . '.' . $fileExtension;
            $dest_path = $uploadFileDir . $newFileName;

            if (move_uploaded_file($fileTmpPath, $dest_path)) {
                // မူလပုံဟောင်းရှိရင် Server ပေါ်မှ ဖျက်မည်
                $oldProfileImage = !empty($user['profile_image']) ? __DIR__ . '/../' . ltrim($user['profile_image'], '/') : '';
                if ($oldProfileImage !== '' && file_exists($oldProfileImage)) {
                    unlink($oldProfileImage);
                }
                $profile_image_path = 'uploads/' . $newFileName;
            } else {
                $message = __('profile_photo_error');
            }
        } else {
            $message = __('profile_photo_format_error');
        }
    }

    // 2. Database တွင် အချက်အလက်များ Update လုပ်မည်
    if (empty($message)) {
        if (!empty($new_username)) {
            $class_name = trim($_POST['class_name'] ?? '');
            $school_name = trim($_POST['school_name'] ?? '');
            $update_stmt = $pdo->prepare("UPDATE users SET username = ?, profile_image = ?, class_name = ?, school_name = ? WHERE id = ?");
            if ($update_stmt->execute([$new_username, $profile_image_path, $class_name !== '' ? $class_name : null, $school_name !== '' ? $school_name : null, $user_id])) {
                $_SESSION['username'] = $new_username;
                header("Location: profile.php");
                exit();
            } else {
                $message = __('profile_update_error');
            }
        } else {
            $message = __('username_required');
        }
    }
}
?>

<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($current_lang, ENT_QUOTES, 'UTF-8'); ?>" dir="<?php echo htmlspecialchars($text_direction, ENT_QUOTES, 'UTF-8'); ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars(__('edit_profile'), ENT_QUOTES, 'UTF-8'); ?> • Nexagram</title>
    <link rel="stylesheet" href="../css/edit_profile.css">
    <link rel="stylesheet" href="../css/utilities.css">
    <link rel="stylesheet" href="../css/suggestions-widget.css">
</head>

<body>

    <div class="edit-box">
        <h3><?php echo htmlspecialchars(__('edit_profile'), ENT_QUOTES, 'UTF-8'); ?></h3>

        <?php if ($message): ?>
            <div class="error-msg"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <!-- Form မှာ ပုံတင်နိုင်ရန် enctype="multipart/form-data" မဖြစ်မနေ ပါရပါမည် -->
        <form method="POST" enctype="multipart/form-data">
            <div class="avatar-preview-container">
                <?php if (!empty($user['profile_image']) && file_exists(__DIR__ . '/../' . ltrim($user['profile_image'], '/'))): ?>
                    <img src="<?php echo htmlspecialchars('../' . ltrim($user['profile_image'], '/')); ?>" id="imgPreview" class="avatar-preview">
                <?php else: ?>
                    <div class="avatar-preview" id="imgPreviewText"><?php echo mb_substr(htmlspecialchars($user['username']), 0, 1, 'UTF-8'); ?></div>
                    <img src="" id="imgPreview" class="avatar-preview is-hidden">
                <?php endif; ?>

                <label for="profile_image" class="file-input-label"><?php echo htmlspecialchars(__('change_profile_photo'), ENT_QUOTES, 'UTF-8'); ?></label>
                <input type="file" name="profile_image" id="profile_image" accept="image/*" data-ui-action="preview-image">
            </div>

            <div class="input-group">
                <label><?php echo htmlspecialchars(__('username_label'), ENT_QUOTES, 'UTF-8'); ?></label>
                <input type="text" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
            </div>

            <div class="input-group">
                <label><?php echo htmlspecialchars(__('class_placeholder'), ENT_QUOTES, 'UTF-8'); ?></label>
                <input type="text" name="class_name" value="<?php echo htmlspecialchars($user['class_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" maxlength="100">
            </div>

            <div class="input-group">
                <label><?php echo htmlspecialchars(__('school_placeholder'), ENT_QUOTES, 'UTF-8'); ?></label>
                <input type="text" name="school_name" value="<?php echo htmlspecialchars($user['school_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" maxlength="150">
            </div>

            <button type="submit" class="save-btn"><?php echo htmlspecialchars(__('save_changes'), ENT_QUOTES, 'UTF-8'); ?></button>
            <a href="profile.php" class="cancel-btn"><?php echo htmlspecialchars(__('btn_cancel'), ENT_QUOTES, 'UTF-8'); ?></a>
        </form>
    </div>

    <script src="../js/edit_profile.js"></script>
    <script src="../js/actions.js"></script>
    <?php include __DIR__ . '/suggestions_widget.php'; ?>

</body>

</html>
