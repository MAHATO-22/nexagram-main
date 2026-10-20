<?php
session_start();
require_once 'config.php';
require_once 'lang.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $caption = trim($_POST['caption']);
    
    $image_destination = null; // Default အနေနဲ့ null ထားမည်

    // File တင်ထားခြင်း ရှိ/မရှိ စစ်ဆေးခြင်း
    if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
        $file = $_FILES['image'];
        $file_name = $file['name'];
        $file_tmp = $file['tmp_name'];
        $file_size = $file['size'];
        $file_error = $file['error'];

        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        // Image နဲ့ Video format များပါ ခွင့်ပြုမည်
        $allowed_ext = ['jpg', 'jpeg', 'png', 'gif', 'mp4', 'webm', 'mov', 'avi'];

        if (in_array($file_ext, $allowed_ext)) {
            if ($file_error === 0) {
                // Video ပါဝင်နိုင်သောကြောင့် Max Size ကို 50MB (50,000,000 bytes) ထိ တိုးထားသည်
                if ($file_size < 50000000) {
                    $new_file_name = uniqid('POST_', true) . '.' . $file_ext;
                    $image_destination = 'uploads/' . $new_file_name;

                    if (!move_uploaded_file($file_tmp, __DIR__ . '/../' . $image_destination)) {
                        die(htmlspecialchars(__('file_upload_failed'), ENT_QUOTES, 'UTF-8'));
                    }
                } else {
                    die(htmlspecialchars(__('file_too_large'), ENT_QUOTES, 'UTF-8'));
                }
            } else {
                die(htmlspecialchars(__('file_upload_error'), ENT_QUOTES, 'UTF-8'));
            }
        } else {
            die(htmlspecialchars(__('err_invalid_format'), ENT_QUOTES, 'UTF-8'));
        }
    }

    // စာလည်းမပါ၊ File လည်းမပါရင် Upload မလုပ်ခိုင်းပါ
    if (empty($caption) && empty($image_destination)) {
        header("Location: create_post.php");
        exit;
    }

    // Database ထဲသို့ သိမ်းဆည်းခြင်း
    try {
        $sql = "INSERT INTO posts (user_id, caption, image_path) VALUES (?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$user_id, $caption, $image_destination]);

        $message = __('post_shared_success');
        echo '<!DOCTYPE html><html><body><div id="redirect-notice" hidden data-message="' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '" data-target="index.php"></div><script src="../js/redirect-alert.js"></script></body></html>';
        exit;
    } catch (PDOException $e) {
        error_log('Post creation failed: ' . $e->getMessage());
        die(htmlspecialchars(__('database_error'), ENT_QUOTES, 'UTF-8'));
    }
} else {
    header("Location: create_post.php");
    exit;
}
?>