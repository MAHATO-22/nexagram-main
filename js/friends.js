        const i18n = {
            confirmUnfriend: window.NEXAGRAM_CONFIG.values[0],
            btnFollowing: window.NEXAGRAM_CONFIG.values[1],
            btnFollowBack: window.NEXAGRAM_CONFIG.values[2],
            darkMode: window.NEXAGRAM_CONFIG.values[3],
            lightMode: window.NEXAGRAM_CONFIG.values[4]
        };

        // Language Dropdown Handler
        function toggleLangMenu() {
            const langMenu = document.getElementById('langMenu');
            if (langMenu) {
                const isOpen = langMenu.classList.toggle('show');
                document.querySelector('.more-trigger')?.setAttribute('aria-expanded', String(isOpen));
            }
        }

        document.addEventListener('click', function(e) {
            const dropdown = document.querySelector('.more-menu-item');
            const langMenu = document.getElementById('langMenu');
            if (dropdown && !dropdown.contains(e.target) && langMenu) {
                langMenu.classList.remove('show');
                dropdown.querySelector('.more-trigger')?.setAttribute('aria-expanded', 'false');
            }
        });

        // Unfriend Action Handler
        function handleUnfriend(button, targetId) {
            if (!confirm(i18n.confirmUnfriend)) return;

            const formData = new FormData();
            formData.append('target_id', targetId);
            formData.append('target_user_id', targetId);
            formData.append('action', 'remove');

            fetch('friend_process.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success || data.status) {
                    const row = button.closest('.user-item');
                    row.style.opacity = '0';
                    setTimeout(() => row.remove(), 200);
                }
            })
            .catch(err => console.error("Unfriend error:", err));
        }

        // Follow / Unfollow / Follow Back Action Handler
        function handleFollowAction(button, targetUserId) {
            const formData = new FormData();
            formData.append('target_user_id', targetUserId);
            formData.append('target_id', targetUserId);

            fetch('follow_process.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                const icon = button.querySelector('i');
                const textSpan = button.querySelector('span');
                const status = data.status || data.action || (data.success ? 'followed' : 'none');

                if (status === 'followed' || status === 'follow') {
                    button.className = 'action-btn btn-following';
                    icon.className = 'fa-solid fa-user-check';
                    textSpan.textContent = i18n.btnFollowing;
                } else {
                    button.className = 'action-btn btn-follow-back';
                    icon.className = 'fa-solid fa-user-plus';
                    textSpan.textContent = i18n.btnFollowBack;
                }
            })
            .catch(err => console.error("Follow action error:", err));
        }

        // Friend Request Accept/Reject Handler
        function handleFriendRequest(button, targetId, action) {
            const formData = new FormData();
            formData.append('target_id', targetId);
            formData.append('target_user_id', targetId);
            formData.append('sender_id', targetId);
            formData.append('action', action);

            fetch('friend_process.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success || data.status) {
                    const row = button.closest('.user-item');
                    row.style.opacity = '0';
                    setTimeout(() => row.remove(), 200);
                } else {
                    console.error("Server error:", data.error || data.message);
                }
            })
            .catch(err => console.error("Friend request error:", err));
        }

        // Theme Switcher
        const themeToggleBtn = document.getElementById('theme-toggle');
        const themeIcon = document.getElementById('theme-icon');
        const themeText = document.getElementById('theme-text');

        function updateThemeUI(theme) {
            document.documentElement.setAttribute('data-theme', theme);
            localStorage.setItem('theme', theme);
            if (theme === 'dark') {
                if (themeIcon) themeIcon.className = 'fa-solid fa-moon';
                if (themeText) themeText.textContent = i18n.darkMode;
            } else {
                if (themeIcon) themeIcon.className = 'fa-solid fa-sun';
                if (themeText) themeText.textContent = i18n.lightMode;
            }
        }

        updateThemeUI(localStorage.getItem('theme') || 'dark');
        if (themeToggleBtn) {
            themeToggleBtn.addEventListener('click', function() {
                const currentTheme = document.documentElement.getAttribute('data-theme') || 'dark';
                updateThemeUI(currentTheme === 'dark' ? 'light' : 'dark');
            });
        }