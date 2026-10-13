        // Language Dropdown Toggle Handler
        const langToggleBtn = document.getElementById('langDropdownToggle');
        const langContainer = document.getElementById('langDropdownContainer');

        langToggleBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            langContainer.classList.toggle('open');
        });

        document.addEventListener('click', (e) => {
            if (!langContainer.contains(e.target)) {
                langContainer.classList.remove('open');
            }
        });

        // Language Text JS Variables
        const txtFollow = window.NEXAGRAM_CONFIG.values[0];
        const txtFollowing = window.NEXAGRAM_CONFIG.values[1];
        const txtAddFriend = window.NEXAGRAM_CONFIG.values[2];
        const txtPending = window.NEXAGRAM_CONFIG.values[3];
        const txtFriends = window.NEXAGRAM_CONFIG.values[4];

        // Real-Time Live Search Script
        const searchInput = document.getElementById('searchInput');
        const clearSearchBtn = document.getElementById('clearSearchBtn');
        const userCards = document.querySelectorAll('.user-card');
        const noResultsMsg = document.getElementById('noResultsMsg');

        function toggleClearButton() {
            if (searchInput.value.trim().length > 0) {
                clearSearchBtn.style.display = 'flex';
            } else {
                clearSearchBtn.style.display = 'none';
            }
        }

        function filterUsers() {
            const query = searchInput.value.toLowerCase().trim();
            let visibleCount = 0;

            userCards.forEach(card => {
                const username = card.getAttribute('data-username') || '';
                const department = card.getAttribute('data-department') || '';
                const bio = card.getAttribute('data-bio') || '';

                if (username.includes(query) || department.includes(query) || bio.includes(query)) {
                    card.style.display = 'flex';
                    visibleCount++;
                } else {
                    card.style.display = 'none';
                }
            });

            if (userCards.length > 0) {
                if (visibleCount === 0) {
                    noResultsMsg.style.display = 'block';
                } else {
                    noResultsMsg.style.display = 'none';
                }
            }
            toggleClearButton();
        }

        searchInput.addEventListener('input', filterUsers);

        clearSearchBtn.addEventListener('click', function() {
            searchInput.value = '';
            filterUsers();
            searchInput.focus();
            
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('search')) {
                const role = urlParams.get('role') || 'all';
                window.location.href = `users.php?role=${role}`;
            }
        });

        toggleClearButton();

        // Follow / Unfollow Handler
        function handleFollowAction(button, targetUserId) {
            const formData = new FormData();
            formData.append('target_user_id', targetUserId);
            formData.append('target_id', targetUserId);

            fetch('follow_process.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                const icon = button.querySelector('i');
                const textSpan = button.querySelector('span');
                const status = data.status || data.action || (data.success ? 'followed' : 'none');

                if (status === 'followed' || status === 'follow') {
                    button.className = 'btn btn-following';
                    icon.className = 'fa-solid fa-user-check';
                    textSpan.textContent = txtFollowing;
                } else {
                    button.className = 'btn btn-follow';
                    icon.className = 'fa-solid fa-user-plus';
                    textSpan.textContent = txtFollow;
                }
            })
            .catch(err => console.error("Follow error:", err));
        }

        // Friend Action Handler
        function handleFriendAction(button, targetId, currentAction) {
            const formData = new FormData();
            formData.append('target_id', targetId);
            formData.append('target_user_id', targetId);
            formData.append('action', currentAction);

            fetch('friend_process.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if (!data.success && !data.status) return;

                const icon = button.querySelector('i');
                const textSpan = button.querySelector('span');

                if (data.status === 'requested' || currentAction === 'send') {
                    button.className = 'btn btn-pending';
                    button.dataset.uiAction = 'friend-action';
                    button.dataset.targetId = targetId;
                    button.dataset.friendAction = 'cancel';
                    icon.className = 'fa-solid fa-clock';
                    textSpan.textContent = txtPending;
                } else if (data.status === 'accepted' || currentAction === 'accept') {
                    button.className = 'btn btn-friends';
                    button.dataset.uiAction = 'friend-action';
                    button.dataset.targetId = targetId;
                    button.dataset.friendAction = 'remove';
                    icon.className = 'fa-solid fa-user-check';
                    textSpan.textContent = txtFriends;
                } else {
                    button.className = 'btn btn-add-friend';
                    button.dataset.uiAction = 'friend-action';
                    button.dataset.targetId = targetId;
                    button.dataset.friendAction = 'send';
                    icon.className = 'fa-solid fa-user-plus';
                    textSpan.textContent = txtAddFriend;
                }
            })
            .catch(err => console.error("Friend action error:", err));
        }

        // Theme Switcher
        const themeToggleBtn = document.getElementById('theme-toggle');
        const themeIcon = document.getElementById('theme-icon');
        const themeText = document.getElementById('theme-text');
        const darkTxt = window.NEXAGRAM_CONFIG.values[5];
        const lightTxt = window.NEXAGRAM_CONFIG.values[6];

        function updateThemeUI(theme) {
            document.documentElement.setAttribute('data-theme', theme);
            localStorage.setItem('theme', theme);
            if (theme === 'dark') {
                if (themeIcon) themeIcon.className = 'fa-solid fa-moon';
                if (themeText) themeText.textContent = darkTxt;
            } else {
                if (themeIcon) themeIcon.className = 'fa-solid fa-sun';
                if (themeText) themeText.textContent = lightTxt;
            }
        }

        updateThemeUI(localStorage.getItem('theme') || 'dark');
        if (themeToggleBtn) {
            themeToggleBtn.addEventListener('click', function() {
                const currentTheme = document.documentElement.getAttribute('data-theme') || 'dark';
                updateThemeUI(currentTheme === 'dark' ? 'light' : 'dark');
            });
        }