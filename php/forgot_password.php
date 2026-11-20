<?php
session_start();
require_once 'config.php';
require_once 'lang.php';

$message = '';
$error = '';
$resetLink = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(trim($_POST['email'] ?? ''));
    $now = time();

    if (!is_string($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (($now - (int)($_SESSION['forgot_password_last_request'] ?? 0)) < 60) {
        $error = 'Please wait one minute before requesting another reset link.';
    } else {
        $_SESSION['forgot_password_last_request'] = $now;

        try {
            $stmt = $pdo->prepare('SELECT id, email FROM users WHERE email = ?');
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user) {
                $token = bin2hex(random_bytes(32));
                $tokenHash = hash('sha256', $token);
                $update = $pdo->prepare(
                    'UPDATE users SET reset_token_hash = ?, reset_token_expires = DATE_ADD(NOW(), INTERVAL 30 MINUTE) WHERE id = ?'
                );
                $update->execute([$tokenHash, $user['id']]);

                $appUrl = getenv('NEXAGRAM_APP_URL');
                if (!is_string($appUrl) || trim($appUrl) === '') {
                    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
                    $appUrl = $scheme . '://' . $host . '/nexagram-main';
                }
                $resetLink = rtrim($appUrl, '/') . '/php/reset_password.php?token=' . urlencode($token);

                $subject = 'Reset your Nexagram password';
                $body = "A password reset was requested for your Nexagram account.\n\n"
                    . "Open this link within 30 minutes:\n" . $resetLink . "\n\n"
                    . "If you did not request this, ignore this email.";
                $headers = "From: Nexagram <no-reply@nexagram.local>\r\n"
                    . "Content-Type: text/plain; charset=UTF-8\r\n";
                @mail($user['email'], $subject, $body, $headers);
            }
        } catch (Throwable $e) {
            error_log('Forgot-password request failed: ' . $e->getMessage());
        }

        $message = 'If that email exists, a password reset link has been sent.';
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($current_lang, ENT_QUOTES, 'UTF-8'); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nexagram • Forgot Password</title>
    <link rel="stylesheet" href="../css/login.css">
    <link rel="stylesheet" href="../css/utilities.css">
</head>
<body>
    <div class="ambient-orb orb-1"></div>
    <div class="ambient-orb orb-2"></div>
    <div class="login-card">
        <div class="brand-header">
            <h1 class="brand-logo">Nexagram</h1>
            <p class="brand-subtitle">Forgot Password</p>
        </div>
        <?php if ($error): ?>
            <div class="alert-error"><span><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></span></div>
        <?php endif; ?>
        <?php if ($message): ?>
            <div class="alert-error" style="border-color:rgba(34,197,94,.3);background:rgba(34,197,94,.12);color:#4ade80;">
                <span><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></span>
            </div>
        <?php endif; ?>
        <form method="POST" action="forgot_password.php">
            <div class="form-group">
                <div class="input-wrapper">
                    <input type="email" name="email" class="form-input" placeholder="student@yse-c.net" required autocomplete="email">
                </div>
            </div>
            <button type="submit" class="submit-btn">Reset Password</button>
        </form>
        <?php if ($resetLink): ?>
            <?php $isLocal = in_array($_SERVER['HTTP_HOST'] ?? '', ['localhost', '127.0.0.1'], true); ?>
            <?php if ($isLocal): ?>
                <div class="footer-text" style="margin-top:18px;overflow-wrap:anywhere;">
                    Local test link: <a class="footer-link" href="<?php echo htmlspecialchars($resetLink, ENT_QUOTES, 'UTF-8'); ?>">Open reset page</a>
                </div>
            <?php endif; ?>
        <?php endif; ?>
        <p class="footer-text" style="margin-top:22px;">
            <a href="login.php" class="footer-link">Back to Login</a>
        </p>
    </div>
</body>
</html>
