<?php
// register.php
session_start();
require_once 'config.php';
require_once 'lang.php'; // Included language support

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $role = $_POST['role'] ?? 'student';
    $user_code = trim($_POST['user_code'] ?? ''); // Student ID or Teacher ID
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $department = $_POST['department'] ?? '';
    $class_name = trim($_POST['class_name'] ?? '');
    $school_name = trim($_POST['school_name'] ?? '');

    if (empty($user_code) || empty($username) || empty($email) || empty($password) || empty($department)) {
        $error = __('required_fields');
    } else {
        try {
            // Check if email or username already exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
            $stmt->execute([$email, $username]);

            if ($stmt->fetch()) {
                $error = __('registration_duplicate');
            } else {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

                $stmt = $pdo->prepare("INSERT INTO users (role, user_code, student_id, username, email, password, department, class_name, school_name) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$role, $user_code, $user_code, $username, $email, $hashedPassword, $department, $class_name !== '' ? $class_name : null, $school_name !== '' ? $school_name : null]);

                $success = __('registration_success');
            }
        } catch (\PDOException $e) {
            error_log('Registration failed: ' . $e->getMessage());
            $error = __('registration_error');
        }
    }
}
?>

<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($current_lang, ENT_QUOTES, 'UTF-8'); ?>" dir="<?php echo htmlspecialchars($text_direction, ENT_QUOTES, 'UTF-8'); ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nexagram • <?php echo __('register_title') ?? '新規登録'; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/register.css">
    <link rel="stylesheet" href="../css/utilities.css">
</head>

<body>

    <!-- Language Selector Dropdown -->
    <div class="top-lang-bar">
        <i class="fa-solid fa-globe"></i>
        <select data-ui-action="language-select">
            <?php foreach ($available_languages as $code => $name): ?>
                <option value="?lang=<?php echo rawurlencode($code); ?>" <?php echo $current_lang === $code ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($name . ' (' . $code . ')', ENT_QUOTES, 'UTF-8'); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="register-card">
        <a href="index.php" class="logo logo-link">Nexagram</a>
        <p class="sub-text">キャンパスの仲間とつながり、最新の学内情報をシェアしよう。</p>

        <?php if ($error): ?>
            <div class="message-box error-msg"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="message-box success-msg"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <form method="POST" action="register.php">
            <!-- Hidden Role Input -->
            <input type="hidden" name="role" id="roleInput" value="student">

            <!-- Role Selector Toggle -->
            <div class="role-selector">
                <div class="role-option active" id="btnStudent" data-ui-action="register-role" data-role="student">
                    <i class="fa-solid fa-graduation-cap"></i> <?php echo __('role_student') ?? '学生 (Student)'; ?>
                </div>
                <div class="role-option" id="btnTeacher" data-ui-action="register-role" data-role="teacher">
                    <i class="fa-solid fa-chalkboard-user"></i> <?php echo __('role_teacher') ?? '教員 (Teacher)'; ?>
                </div>
            </div>

            <!-- ID Input (Changes dynamically) -->
            <div class="form-group">
                <input type="text" name="user_code" id="userCodeInput" class="form-input" placeholder="<?php echo htmlspecialchars(__('ph_student_id'), ENT_QUOTES, 'UTF-8'); ?>" data-student-placeholder="<?php echo htmlspecialchars(__('ph_student_id'), ENT_QUOTES, 'UTF-8'); ?>" data-teacher-placeholder="<?php echo htmlspecialchars(__('ph_teacher_id'), ENT_QUOTES, 'UTF-8'); ?>" required>
            </div>

            <!-- Username -->
            <div class="form-group">
                <input type="text" name="username" class="form-input" placeholder="<?php echo htmlspecialchars(__('ph_username'), ENT_QUOTES, 'UTF-8'); ?>" required>
            </div>

            <!-- Email -->
            <div class="form-group">
                <input type="email" name="email" class="form-input" placeholder="<?php echo htmlspecialchars(__('ph_email'), ENT_QUOTES, 'UTF-8'); ?>" required>
            </div>

            <!-- Password -->
            <div class="form-group">
                <input type="password" name="password" class="form-input" placeholder="<?php echo htmlspecialchars(__('ph_password'), ENT_QUOTES, 'UTF-8'); ?>" required>
            </div>

            <!-- Department Dropdown Selection -->
            <div class="form-group">
                <select name="department" class="form-select" required>
                    <option value="" disabled selected><?php echo htmlspecialchars(__('select_dept'), ENT_QUOTES, 'UTF-8'); ?></option>
                    <option value="Global Systems"><?php echo htmlspecialchars(__('department_global_systems'), ENT_QUOTES, 'UTF-8'); ?></option>
                    <option value="Information Technology"><?php echo htmlspecialchars(__('department_information_technology'), ENT_QUOTES, 'UTF-8'); ?></option>
                    <option value="Business Management"><?php echo htmlspecialchars(__('department_business_management'), ENT_QUOTES, 'UTF-8'); ?></option>
                    <option value="Digital Design"><?php echo htmlspecialchars(__('department_digital_design'), ENT_QUOTES, 'UTF-8'); ?></option>
                </select>
            </div>

            <div class="form-group">
                <input type="text" name="class_name" class="form-input" placeholder="<?php echo htmlspecialchars(__('class_placeholder'), ENT_QUOTES, 'UTF-8'); ?>" maxlength="100">
            </div>

            <div class="form-group">
                <input type="text" name="school_name" class="form-input" placeholder="<?php echo htmlspecialchars(__('school_placeholder'), ENT_QUOTES, 'UTF-8'); ?>" maxlength="150">
            </div>

            <button type="submit" class="btn-submit"><?php echo htmlspecialchars(__('btn_signup'), ENT_QUOTES, 'UTF-8'); ?></button>
        </form>

        <p class="footer-text"><?php echo htmlspecialchars(__('has_account'), ENT_QUOTES, 'UTF-8'); ?> <a href="login.php"><?php echo htmlspecialchars(__('login_link'), ENT_QUOTES, 'UTF-8'); ?></a></p>
    </div>

    <script src="../js/register.js"></script>
    <script src="../js/actions.js"></script>
</body>

</html>