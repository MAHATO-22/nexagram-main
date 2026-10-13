        const i18n = {
            darkMode: window.NEXAGRAM_CONFIG.values[0],
            lightMode: window.NEXAGRAM_CONFIG.values[1]
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

        // Edit Post Modal Handlers
        function openEditModal(postId, currentCaption) {
            document.getElementById('modalPostId').value = postId;
            document.getElementById('modalCaption').value = currentCaption;
            document.getElementById('editModal').style.display = 'flex';
        }

        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
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
            themeToggleBtn.addEventListener('click', () => {
                const current = document.documentElement.getAttribute('data-theme') || 'dark';
                updateThemeUI(current === 'dark' ? 'light' : 'dark');
            });
        }