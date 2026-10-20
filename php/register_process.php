<?php
// register_process.php - Nexagram User Registration Handling
session_start();
require_once 'config.php'; // ရှေ့မှာ ဆောက်ခဲ့တဲ့ Database Connection ကို လှမ်းခေါ်တာပါ
require_once 'lang.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Form ကနေ ပို့လိုက်တဲ့ Data တွေကို လက်ခံပြီး သန့်စင်ခြင်း (Sanitization)
    $student_id = trim($_POST['student_id']);
    $username   = trim($_POST['username']);
    $email      = trim($_POST['email']);
    $password   = $_POST['password'];

    // အချက်အလက်တွေ အားလုံး ပါ၊ မပါ အခြေခံ စစ်ဆေးခြင်း
    if (empty($student_id) || empty($username) || empty($email) || empty($password)) {
        die(htmlspecialchars(__('required_fields'), ENT_QUOTES, 'UTF-8'));
    }

    try {
        // 1. Database ထဲမှာ ဒီ Student ID, Username သို့မဟုတ် Email က ရှိပြီးသား ဖြစ်နေလား အရင်စစ်မယ်
        $stmt = $pdo->prepare("SELECT id FROM users WHERE student_id = ? OR username = ? OR email = ?");
        $stmt->execute([$student_id, $username, $email]);
        
        if ($stmt->rowCount() > 0) {
            die(htmlspecialchars(__('registration_duplicate'), ENT_QUOTES, 'UTF-8'));
        }

        // 2. Password (စကားဝှက်) ကို လုံခြုံအောင် Encrypt (Hash) လုပ်ခြင်း
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // 3. Database ထဲကို ကျောင်းသားအသစ်အဖြစ် သိမ်းဆည်းခြင်း
        $sql = "INSERT INTO users (student_id, username, email, password) VALUES (?, ?, ?, ?)";
        $insert_stmt = $pdo->prepare($sql);
        $insert_stmt->execute([$student_id, $username, $email, $hashed_password]);

        // အကောင့်ဖွင့်တာ အောင်မြင်သွားရင် Login Page ကို ပို့ပေးမယ် (Login Page ကို နောက်အဆင့်မှာ ဆောက်ပါမယ်)
        $message = __('registration_success');
        echo '<!DOCTYPE html><html><body><div id="redirect-notice" hidden data-message="' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '" data-target="login.php"></div><script src="../js/redirect-alert.js"></script></body></html>';
        exit;

    } catch (\PDOException $e) {
        // Error တက်ခဲ့ရင် ပြပေးဖို့
        error_log('Legacy registration failed: ' . $e->getMessage());
        die(htmlspecialchars(__('database_error'), ENT_QUOTES, 'UTF-8'));
    }
} else {
    // တကယ်လို့ တိုက်ရိုက် url ကနေ ဝင်လာရင် register.php ဆီ ပြန်မောင်းထုတ်မယ်
    header("Location: register.php");
    exit;
}
?>