<?php
// users.php - Nexagram User Directory
session_start();
require_once 'config.php';
require_once 'lang.php'; // Included language support

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$current_user_id = $_SESSION['user_id'];
$search = trim($_GET['search'] ?? '');
$role_tab = $_GET['role'] ?? 'all'; // 'all', 'student', or 'teacher'

function resolveImagePath($fileName)
{
    if (empty($fileName)) return null;
    if (strpos($fileName, 'http') === 0) return $fileName;

    $cleanName = ltrim($fileName, '/');
    if (file_exists(__DIR__ . '/../' . $cleanName)) return '../' . $cleanName;
    if (file_exists(__DIR__ . '/../uploads/' . $cleanName)) return '../uploads/' . $cleanName;

    return (strpos($cleanName, 'uploads/') === 0) ? '../' . $cleanName : '../uploads/' . $cleanName;
}

try {
    // Count totals for badges
    $stmtCountAll = $pdo->prepare("SELECT COUNT(*) FROM users WHERE id != ?");
    $stmtCountAll->execute([$current_user_id]);
    $count_all = $stmtCountAll->fetchColumn();

    $stmtCountStudent = $pdo->prepare("SELECT COUNT(*) FROM users WHERE id != ? AND (role = 'student' OR role IS NULL)");
    $stmtCountStudent->execute([$current_user_id]);
    $count_students = $stmtCountStudent->fetchColumn();

    $stmtCountTeacher = $pdo->prepare("SELECT COUNT(*) FROM users WHERE id != ? AND role = 'teacher'");
    $stmtCountTeacher->execute([$current_user_id]);
    $count_teachers = $stmtCountTeacher->fetchColumn();

    // Query Users with Follow & Friend Status
    $sql = "SELECT u.id, u.username, u.profile_image, u.bio, u.role, u.department,
            (SELECT COUNT(*) FROM follows WHERE follower_id = ? AND following_id = u.id) AS is_following,
            (SELECT status FROM friend_requests WHERE (sender_id = ? AND receiver_id = u.id) OR (sender_id = u.id AND receiver_id = ?) LIMIT 1) AS friend_status,
            (SELECT sender_id FROM friend_requests WHERE (sender_id = ? AND receiver_id = u.id) OR (sender_id = u.id AND receiver_id = ?) LIMIT 1) AS request_sender_id
            FROM users u
            WHERE u.id != ?";

    $params = [$current_user_id, $current_user_id, $current_user_id, $current_user_id, $current_user_id, $current_user_id];

    // Filter by Role Tab
    if ($role_tab === 'student') {
        $sql .= " AND (u.role = 'student' OR u.role IS NULL)";
    } elseif ($role_tab === 'teacher') {
        $sql .= " AND u.role = 'teacher'";
    }

    // Filter by Search Query
    if ($search !== '') {
        $sql .= " AND (u.username LIKE ? OR u.department LIKE ? OR u.bio LIKE ?)";
        $searchTerm = "%{$search}%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $users = $stmt->fetchAll();

} catch (\PDOException $e) {
    die("Database Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($current_lang, ENT_QUOTES, 'UTF-8'); ?>" dir="<?php echo htmlspecialchars($text_direction, ENT_QUOTES, 'UTF-8'); ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nexagram • <?php echo __('nav_users'); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="../js/theme-init.js"></script>
    <link rel="stylesheet" href="../css/users.css">
    <link rel="stylesheet" href="../css/utilities.css">
    <link rel="stylesheet" href="../css/more-menu.css">
    <link rel="stylesheet" href="../css/suggestions-widget.css">
</head>

<body>

    <!-- Sidebar Component -->
    <nav class="sidebar">
        <a href="index.php" class="logo logo-link">Nexagram</a>
        <ul class="nav-menu">
            <li class="nav-item"><a href="index.php"><i class="fa-solid fa-house"></i> <?php echo __('nav_home'); ?></a></li>
            <li class="nav-item"><a href="friends.php"><i class="fa-solid fa-user-group"></i> <?php echo __('nav_friends'); ?></a></li>
            <li class="nav-item active"><a href="users.php"><i class="fa-solid fa-users"></i> <?php echo __('nav_users'); ?></a></li>
            <li class="nav-item"><a href="messages.php"><i class="fa-solid fa-paper-plane"></i> <?php echo __('nav_messages'); ?> <span class="notif-badge is-hidden" id="navMsgBadge">0</span></a></li>
            <li class="nav-item"><a href="create_post.php"><i class="fa-solid fa-square-plus"></i> <?php echo __('nav_create'); ?></a></li>
            <li class="nav-item"><a href="profile.php"><i class="fa-solid fa-user"></i> <?php echo __('nav_profile'); ?></a></li>
        </ul>

        <!-- More menu -->
        <div class="sidebar-bottom">
            <div class="lang-dropdown-container" id="langDropdownContainer">
                <div class="lang-dropdown-menu" id="langDropdownMenu">
                    <a href="settings.php" class="lang-option">
                        <i class="fa-solid fa-gear"></i> <?php echo __('nav_settings'); ?>
                    </a>
                    <button type="button" class="sidebar-action-btn" id="theme-toggle">
                        <i class="fa-solid fa-moon" id="theme-icon"></i>
                        <span id="theme-text"><?php echo __('dark_mode'); ?></span>
                    </button>
                    <a href="logout.php" class="sidebar-action-btn logout-btn">
                        <i class="fa-solid fa-right-from-bracket"></i>
                        <span><?php echo __('nav_logout'); ?></span>
                    </a>
                </div>

                <button class="lang-dropdown-btn" id="langDropdownToggle" type="button">
                    <i class="fa-solid fa-ellipsis"></i>
                    <span><?php echo __('more'); ?></span>
                    <i class="fa-solid fa-chevron-down arrow-icon"></i>
                </button>
            </div>
        </div>
    </nav>

    <main class="main-content">
        <div class="top-nav-bar">
            <a href="#" class="nav-back-btn" data-ui-action="go-back"><i class="fa-solid fa-arrow-left"></i> <?php echo htmlspecialchars(__('back'), ENT_QUOTES, 'UTF-8'); ?></a>
            <a href="index.php" class="nav-back-btn"><i class="fa-solid fa-house"></i> <?php echo htmlspecialchars(__('nav_home'), ENT_QUOTES, 'UTF-8'); ?></a>
        </div>

        <h1 class="page-title"><i class="fa-solid fa-users users-title-icon"></i> Nexagram <?php echo htmlspecialchars(__('nav_users'), ENT_QUOTES, 'UTF-8'); ?></h1>

        <!-- Student / Teacher Role Filter Tabs -->
        <div class="role-tabs">
            <a href="users.php?role=all&search=<?php echo urlencode($search); ?>" class="tab-btn <?php echo $role_tab === 'all' ? 'active' : ''; ?>">
                <i class="fa-solid fa-globe"></i> <?php echo __('tab_all'); ?> <span class="badge"><?php echo $count_all; ?></span>
            </a>
            <a href="users.php?role=student&search=<?php echo urlencode($search); ?>" class="tab-btn <?php echo $role_tab === 'student' ? 'active' : ''; ?>">
                <i class="fa-solid fa-graduation-cap"></i> <?php echo __('tab_students'); ?> <span class="badge"><?php echo $count_students; ?></span>
            </a>
            <a href="users.php?role=teacher&search=<?php echo urlencode($search); ?>" class="tab-btn <?php echo $role_tab === 'teacher' ? 'active' : ''; ?>">
                <i class="fa-solid fa-chalkboard-user"></i> <?php echo __('tab_teachers'); ?> <span class="badge"><?php echo $count_teachers; ?></span>
            </a>
        </div>

        <!-- Search Bar with Form & Interactive Features -->
        <form method="GET" action="users.php" class="search-bar" id="searchForm">
            <input type="hidden" name="role" value="<?php echo htmlspecialchars($role_tab); ?>">
            <button type="submit" class="search-btn" title="<?php echo htmlspecialchars(__('search'), ENT_QUOTES, 'UTF-8'); ?>">
                <i class="fa-solid fa-magnifying-glass"></i>
            </button>
            <input type="text" name="search" id="searchInput" placeholder="<?php echo __('search_placeholder'); ?>" value="<?php echo htmlspecialchars($search); ?>" autocomplete="off">
            <button type="button" class="clear-btn" id="clearSearchBtn" title="<?php echo htmlspecialchars(__('clear'), ENT_QUOTES, 'UTF-8'); ?>">
                <i class="fa-solid fa-circle-xmark"></i>
            </button>
        </form>

        <!-- User Cards Grid -->
        <div class="users-grid" id="usersGrid">
            <div class="no-results-msg" id="noResultsMsg">
                <?php echo __('no_users'); ?>
            </div>

            <?php if (empty($users)): ?>
                <div class="users-empty-state" id="serverNoResults">
                    <?php echo __('no_users'); ?>
                </div>
            <?php else: ?>
                <?php foreach ($users as $u): ?>
                    <?php
                        $u_avatar = resolveImagePath($u['profile_image'] ?? null);
                        $is_following = (bool)$u['is_following'];
                        $friend_status = $u['friend_status'];
                        $is_sender = ($u['request_sender_id'] == $current_user_id);
                        $is_teacher = ($u['role'] === 'teacher');
                    ?>
                    <div class="user-card" 
                         data-username="<?php echo htmlspecialchars(mb_strtolower($u['username'], 'UTF-8')); ?>" 
                         data-department="<?php echo htmlspecialchars(mb_strtolower($u['department'] ?? '', 'UTF-8')); ?>"
                         data-bio="<?php echo htmlspecialchars(mb_strtolower($u['bio'] ?? '', 'UTF-8')); ?>">
                        
                        <a href="profile.php?id=<?php echo $u['id']; ?>">
                            <?php if (!empty($u_avatar)): ?>
                                <img src="<?php echo htmlspecialchars($u_avatar); ?>" class="avatar-img">
                            <?php else: ?>
                                <div class="avatar-circle"><?php echo mb_substr(htmlspecialchars($u['username']), 0, 1, 'UTF-8'); ?></div>
                            <?php endif; ?>
                        </a>

                        <a href="profile.php?id=<?php echo $u['id']; ?>" class="username"><?php echo htmlspecialchars($u['username']); ?></a>

                        <!-- Role Badge -->
                        <span class="role-badge <?php echo $is_teacher ? 'role-teacher' : 'role-student'; ?>">
                            <i class="fa-solid <?php echo $is_teacher ? 'fa-chalkboard-user' : 'fa-graduation-cap'; ?>"></i>
                            <?php echo $is_teacher ? __('role_teacher') : __('role_student'); ?>
                        </span>

                        <!-- Department Tag -->
                        <?php if (!empty($u['department'])): ?>
                            <div class="department-text">
                                <i class="fa-solid fa-building-columns"></i> <?php echo htmlspecialchars($u['department']); ?>
                            </div>
                        <?php endif; ?>

                        <p class="bio"><?php echo htmlspecialchars($u['bio'] ?? 'Nexagram user'); ?></p>

                        <div class="card-actions">
                            <!-- Follow Button -->
                            <button class="btn <?php echo $is_following ? 'btn-following' : 'btn-follow'; ?>"
                                    data-ui-action="follow-user" data-target-id="<?php echo (int)$u['id']; ?>">
                                <i class="fa-solid <?php echo $is_following ? 'fa-user-check' : 'fa-user-plus'; ?>"></i>
                                <span><?php echo $is_following ? __('btn_following') : __('btn_follow'); ?></span>
                            </button>

                            <!-- Friend Button -->
                            <?php if ($friend_status === 'accepted'): ?>
                                <button class="btn btn-friends" data-ui-action="friend-action" data-target-id="<?php echo (int)$u['id']; ?>" data-friend-action="remove">
                                    <i class="fa-solid fa-user-check"></i>
                                    <span><?php echo __('btn_friends'); ?></span>
                                </button>
                            <?php elseif ($friend_status === 'pending' && $is_sender): ?>
                                <button class="btn btn-pending" data-ui-action="friend-action" data-target-id="<?php echo (int)$u['id']; ?>" data-friend-action="cancel">
                                    <i class="fa-solid fa-clock"></i>
                                    <span><?php echo __('btn_pending'); ?></span>
                                </button>
                            <?php elseif ($friend_status === 'pending' && !$is_sender): ?>
                                <button class="btn btn-add-friend" data-ui-action="friend-action" data-target-id="<?php echo (int)$u['id']; ?>" data-friend-action="accept">
                                    <i class="fa-solid fa-user-plus"></i>
                                    <span>Accept</span>
                                </button>
                            <?php else: ?>
                                <button class="btn btn-add-friend" data-ui-action="friend-action" data-target-id="<?php echo (int)$u['id']; ?>" data-friend-action="send">
                                    <i class="fa-solid fa-user-plus"></i>
                                    <span><?php echo __('btn_add_friend'); ?></span>
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>

    <div id="page-config" hidden data-config="<?php echo htmlspecialchars(json_encode(['values' => [__('btn_follow'), __('btn_following'), __('btn_add_friend'), __('btn_pending'), __('btn_friends'), __('dark_mode'), __('light_mode')]], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP), ENT_QUOTES, 'UTF-8'); ?>"></div>
    <script src="../js/page-config.js"></script>
    <script src="../js/users.js"></script>
    <script src="../js/actions.js"></script>
    <script src="../js/badge.js"></script>
    <?php include __DIR__ . '/suggestions_widget.php'; ?>
</body>
</html>