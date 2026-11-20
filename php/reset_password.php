<?php
session_start();
require_once 'config.php';
require_once 'lang.php';

$token = isset($_GET['token']) ? trim((string)$_GET['token']) : '';
$error = '';
$success = false;

if ($token === '' || !preg_match('/^[a-f0-9]{64}$/i', $token)) {
    $error = 'Invalid or expired reset link.';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newPassword = (string)($_POST['password'] ?? '');
    $confirmPassword = (string)($_POST['confirm_password'] ?? '');

    if (strlen($newPassword) < 8) {
        $error = 'Password must be at least 8 characters.';
    } elseif ($newPassword !== $confirmPassword) {
        $error = 'Passwords do not match.';
    } else {
        $attemptKey = 'reset_password_attempts';
        $attemptTimeKey = 'reset_password_attempt_time';
        $attemptTime = (int)($_SESSION[$attemptTimeKey] ?? 0);
        $attempts = $attemptTime + 900 > time() ? (int)($_SESSION[$attemptKey] ?? 0) : 0;
        if ($attempts >= 5) {
            $error = 'Too many reset attempts. Please request a new link.';
        } else {
            $_SESSION[$attemptKey] = $attempts + 1;
            $_SESSION[$attemptTimeKey] = time();

            $tokenHash = hash('sha256', $token);
            $stmt = $pdo->prepare('SELECT id FROM users WHERE reset_token_hash = ? AND reset_token_expires > NOW()');
            $stmt->execute([$tokenHash]);
            $user = $stmt->fetch();

            if (!$user) {
                $error = 'Invalid or expired reset link.';
            } else {
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                $update = $pdo->prepare(
                    'UPDATE users SET password = ?, reset_token_hash = NULL, reset_token_expires = NULL WHERE id = ?'
                );
                $update->execute([$hashedPassword, $user['id']]);

                $_SESSION = [];
                if (ini_get('session.use_cookies')) {
                    $params = session_get_cookie_params();
                    setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
                }
                session_destroy();
                $success = true;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($current_lang, ENT_QUOTES, 'UTF-8'); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nexagram • Reset Password</title>
    <link rel="stylesheet" href="../css/login.css">
    <link rel="stylesheet" href="../css/utilities.css">
</head>
<body>
    <div class="ambient-orb orb-1"></div>
    <div class="ambient-orb orb-2"></div>
    <div class="login-card">
        <div class="brand-header">
            <h1 class="brand-logo">Nexagram</h1>
            <p class="brand-subtitle">Reset Password</p>
        </div>
        <?php if ($success): ?>
            <div class="alert-error" style="border-color:rgba(34,197,94,.3);background:rgba(34,197,94,.12);color:#4ade80;">
                <span>Password updated successfully.</span>
            </div>
            <a class="submit-btn" style="display:block;text-align:center;text-decoration:none;" href="login.php?reset=success">Go to Login</a>
        <?php else: ?>
            <?php if ($error): ?>
                <div class="alert-error"><span><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></span></div>
            <?php endif; ?>
            <form method="POST" action="reset_password.php?token=<?php echo urlencode($token); ?>">
                <div class="form-group">
                    <input type="password" name="password" class="form-input" placeholder="New Password" minlength="8" required autocomplete="new-password" style="padding-left:16px;">
                </div>
                <div class="form-group">
                    <input type="password" name="confirm_password" class="form-input" placeholder="Confirm Password" minlength="8" required autocomplete="new-password" style="padding-left:16px;">
                </div>
                <button type="submit" class="submit-btn">Reset Password</button>
            </form>
            <p class="footer-text" style="margin-top:22px;">
                <a href="login.php" class="footer-link">Back to Login</a>
            </p>
        <?php endif; ?>
    </div>
</body>
</html>
