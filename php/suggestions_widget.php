<?php
$suggestions_widget_text = [
    'title' => __('suggestions_title'),
    'subtitle' => __('suggestions_subtitle'),
    'loading' => __('suggestions_loading'),
    'empty' => __('suggestions_empty'),
    'error' => __('suggestions_error'),
    'follow' => __('btn_follow'),
    'following' => __('btn_following'),
    'addFriend' => __('btn_add_friend'),
    'pending' => __('btn_pending'),
    'followError' => __('suggestion_follow_error'),
    'friendError' => __('suggestion_friend_error'),
    'class' => __('suggestion_same_class'),
    'id_group' => __('suggestion_same_id_group'),
    'department' => __('suggestion_same_department'),
    'school' => __('suggestion_same_school'),
    'platform' => __('suggestion_platform'),
    'seeAll' => __('see_all_users'),
];
?>
<div id="people-suggestions-widget"
     data-config="<?php echo htmlspecialchars(json_encode($suggestions_widget_text, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP), ENT_QUOTES, 'UTF-8'); ?>">
    <button class="people-suggestions-launcher" type="button" aria-expanded="false" aria-controls="people-suggestions-panel">
        <i class="fa-solid fa-user-group" aria-hidden="true"></i>
        <span><?php echo htmlspecialchars(__('suggestions_launcher'), ENT_QUOTES, 'UTF-8'); ?></span>
    </button>
    <section class="people-suggestions-panel" id="people-suggestions-panel" aria-label="<?php echo htmlspecialchars(__('suggestions_title'), ENT_QUOTES, 'UTF-8'); ?>" hidden>
        <header class="people-suggestions-header">
            <div>
                <h2><?php echo htmlspecialchars(__('suggestions_title'), ENT_QUOTES, 'UTF-8'); ?></h2>
                <p><?php echo htmlspecialchars(__('suggestions_subtitle'), ENT_QUOTES, 'UTF-8'); ?></p>
            </div>
            <button type="button" class="people-suggestions-close" aria-label="<?php echo htmlspecialchars(__('suggestions_close'), ENT_QUOTES, 'UTF-8'); ?>"><i class="fa-solid fa-xmark"></i></button>
        </header>
        <div class="people-suggestions-list" aria-live="polite"></div>
        <a class="people-suggestions-all" href="users.php"><?php echo htmlspecialchars(__('see_all_users'), ENT_QUOTES, 'UTF-8'); ?></a>
    </section>
</div>
<script src="../js/suggestions-widget.js" defer></script>
