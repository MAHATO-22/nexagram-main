<?php
// login_process.php - Nexagram User Login Handling
session_start();
require_once 'config.php';
require_once 'lang.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username_email = trim($_POST['username_email']);
    $password       = $_POST['password'];

    if (empty($username_email) || empty($password)) {
        http_response_code(400);
        die(htmlspecialchars(__('required_fields'), ENT_QUOTES, 'UTF-8'));
    }

    try {
        // Username သို့မဟုတ် Email တစ်ခုခုနဲ့ ကိုက်ညီတဲ့ user ရှိလား ရှာမယ်
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username_email, $username_email]);
        $user = $stmt->fetch();

        // User ရှိတယ်ဆိုရင် Password မှန်၊ မမှန် ဆက်စစ်မယ်
        if ($user && password_verify($password, $user['password'])) {
            // Password မှန်ရင် Session ထဲမှာ User ရဲ့ ID နဲ့ နာမည်ကို မှတ်သားလိုက်မယ်
            $_SESSION['user_id']  = $user['id'];
            $_SESSION['username'] = $user['username'];
            $preferred_language = $user['language_code'] ?? null;
            if (is_string($preferred_language) && isset($available_languages[$preferred_language])) {
                $_SESSION['lang'] = $preferred_language;
            }

            // အောင်မြင်ရင် Home Feed Page (index.php) ကို ပို့ပေးမယ်
            header("Location: index.php");
            exit;
        } else {
            // Password မှားရင် သို့မဟုတ် User ရှာမတွေ့ရင်
            http_response_code(401);
            die(htmlspecialchars(__('login_invalid'), ENT_QUOTES, 'UTF-8'));
        }

    } catch (\PDOException $e) {
        error_log('Login failed: ' . $e->getMessage());
        http_response_code(500);
        die(htmlspecialchars(__('database_error'), ENT_QUOTES, 'UTF-8'));
    }
} else {
    header("Location: login.php");
    exit;
}
?>