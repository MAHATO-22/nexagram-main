<?php
session_start();
require_once 'config.php';
require_once 'lang.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$settings_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['lang'])) {
    $requested_language = $_POST['lang'];
    if (!is_string($requested_language) || !isset($available_languages[$requested_language])) {
        $settings_error = __('language_save_error');
    } else {
        $language_stmt = $pdo->prepare('UPDATE users SET language_code = ? WHERE id = ?');
        $language_stmt->execute([$requested_language, $user_id]);
        $_SESSION['lang'] = $requested_language;
        header('Location: settings.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($current_lang, ENT_QUOTES, 'UTF-8'); ?>" dir="<?php echo htmlspecialchars($text_direction, ENT_QUOTES, 'UTF-8'); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nexagram • <?php echo htmlspecialchars(__('nav_settings'), ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="../js/theme-init.js"></script>
    <link rel="stylesheet" href="../css/settings.css">
    <link rel="stylesheet" href="../css/suggestions-widget.css">
</head>
<body>
    <main class="settings-card">
        <a class="back-link" href="index.php"><i class="fa-solid fa-arrow-left"></i> <?php echo htmlspecialchars(__('nav_home'), ENT_QUOTES, 'UTF-8'); ?></a>
        <h1><?php echo htmlspecialchars(__('nav_settings'), ENT_QUOTES, 'UTF-8'); ?></h1>
        <?php if ($settings_error !== ''): ?>
            <p class="setting-notice setting-error" role="alert"><?php echo htmlspecialchars($settings_error, ENT_QUOTES, 'UTF-8'); ?></p>
        <?php endif; ?>
        <section class="setting-section" aria-labelledby="language-heading">
            <div class="setting-icon"><i class="fa-solid fa-language"></i></div>
            <div class="setting-content">
                <h2 id="language-heading"><?php echo htmlspecialchars(__('language_settings'), ENT_QUOTES, 'UTF-8'); ?></h2>
                <p><?php echo htmlspecialchars(__('language_description'), ENT_QUOTES, 'UTF-8'); ?></p>
                <form method="post" action="settings.php">
                    <label for="language-select"><?php echo htmlspecialchars(__('language'), ENT_QUOTES, 'UTF-8'); ?></label>
                    <select id="language-select" name="lang" onchange="this.form.submit()">
                        <?php foreach ($available_languages as $code => $name): ?>
                            <option value="<?php echo htmlspecialchars($code, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $current_lang === $code ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($name . ' (' . $code . ')', ENT_QUOTES, 'UTF-8'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </div>
        </section>
        <section class="setting-section" aria-labelledby="theme-heading">
            <div class="setting-icon"><i class="fa-solid fa-circle-half-stroke"></i></div>
            <div class="setting-content">
                <h2 id="theme-heading"><?php echo htmlspecialchars(__('theme_settings'), ENT_QUOTES, 'UTF-8'); ?></h2>
                <p><?php echo htmlspecialchars(__('theme_description'), ENT_QUOTES, 'UTF-8'); ?></p>
                <label for="theme-select"><?php echo htmlspecialchars(__('theme'), ENT_QUOTES, 'UTF-8'); ?></label>
                <select id="theme-select">
                    <option value="light"><?php echo htmlspecialchars(__('light_mode'), ENT_QUOTES, 'UTF-8'); ?></option>
                    <option value="dark"><?php echo htmlspecialchars(__('dark_mode'), ENT_QUOTES, 'UTF-8'); ?></option>
                </select>
            </div>
        </section>
    </main>
    <?php include __DIR__ . '/suggestions_widget.php'; ?>
    <script src="../js/settings.js" defer></script>
</body>
</html>
