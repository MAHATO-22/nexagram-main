<?php
// login.php - Nexagram School Email Only Login
session_start();
require_once 'config.php';
require_once 'lang.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // Allowed domain configuration
    $allowed_domain = 'yse-c.net';
    $user_email_domain = strtolower(substr(strrchr($email, "@"), 1));

    if (empty($email) || empty($password)) {
        $error_message = __('required_fields');
    } elseif ($user_email_domain !== $allowed_domain) {
        $error_message = "Access restricted: Please enter an official @" . $allowed_domain . " email address.";
    } else {
        try {
            // Query ONLY by email address
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                
                $preferred_language = $user['language_code'] ?? null;
                if (is_string($preferred_language) && isset($available_languages[$preferred_language])) {
                    $_SESSION['lang'] = $preferred_language;
                }
                
                header("Location: index.php");
                exit;
            } else {
                $error_message = __('login_invalid');
            }
        } catch (\PDOException $e) {
            $error_message = __('database_error');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($current_lang, ENT_QUOTES, 'UTF-8'); ?>" dir="<?php echo htmlspecialchars($text_direction, ENT_QUOTES, 'UTF-8'); ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nexagram • <?php echo htmlspecialchars(__('login_title'), ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="../js/theme-init.js"></script>
    <link rel="stylesheet" href="../css/login.css">
    <link rel="stylesheet" href="../css/utilities.css">
</head>

<body>

    <button class="theme-toggle-fixed" id="themeToggle" title="<?php echo htmlspecialchars(__('dark_mode'), ENT_QUOTES, 'UTF-8'); ?>">
        <i class="fa-solid fa-moon" id="themeIcon"></i>
    </button>

    <div class="ambient-orb orb-1"></div>
    <div class="ambient-orb orb-2"></div>

    <div class="login-card">
        <form method="get" action="login.php" class="language-picker">
            <label for="login-language"><?php echo htmlspecialchars(__('language'), ENT_QUOTES, 'UTF-8'); ?></label>
            <select id="login-language" name="lang" onchange="this.form.submit()">
                <?php foreach ($available_languages as $code => $name): ?>
                    <option value="<?php echo htmlspecialchars($code, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $current_lang === $code ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
        <div class="brand-header">
            <a href="index.php" class="brand-home-link">
                <h1 class="brand-logo">Nexagram</h1>
            </a>
            <p class="brand-subtitle"><?php echo htmlspecialchars(__('login_subtitle'), ENT_QUOTES, 'UTF-8'); ?></p>
        </div>

        <?php if (!empty($error_message)): ?>
            <div class="alert-error">
                <i class="fa-solid fa-circle-exclamation"></i>
                <span><?php echo htmlspecialchars($error_message); ?></span>
            </div>
        <?php endif; ?>

        <form action="login.php" method="POST">
            <!-- Email Input (School Email Only) -->
            <div class="form-group">
                <div class="input-wrapper">
                    <input type="email" name="email" class="form-input" placeholder="student@yse-c.net" pattern=".+@yse-c\.net$" title="Please enter your official @yse-c.net email." required autocomplete="email">
                    <i class="fa-regular fa-envelope input-icon"></i>
                </div>
            </div>

            <!-- Password Input -->
            <div class="form-group">
                <div class="input-wrapper">
                    <input type="password" name="password" id="passwordInput" class="form-input" placeholder="<?php echo htmlspecialchars(__('login_password_placeholder'), ENT_QUOTES, 'UTF-8'); ?>" required autocomplete="current-password">
                    <i class="fa-solid fa-lock input-icon"></i>
                    <i class="fa-regular fa-eye toggle-password" id="togglePassword"></i>
                </div>
            </div>

            <button type="submit" class="submit-btn"><?php echo htmlspecialchars(__('login_button'), ENT_QUOTES, 'UTF-8'); ?></button>
        </form>

        <div class="divider">
            <span><?php echo htmlspecialchars(__('login_or'), ENT_QUOTES, 'UTF-8'); ?></span>
        </div>

        <div class="footer-text">
            <?php echo htmlspecialchars(__('no_account'), ENT_QUOTES, 'UTF-8'); ?>
            <a href="register.php" class="footer-link"><?php echo htmlspecialchars(__('register_link'), ENT_QUOTES, 'UTF-8'); ?></a>
        </div>
    </div>

    <script src="../js/login.js"></script>
</body>

</html>