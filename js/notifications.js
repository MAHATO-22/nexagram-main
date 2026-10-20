document.addEventListener('DOMContentLoaded', () => {
    const moreToggle = document.getElementById('moreToggle');
    const moreMenu = document.getElementById('moreMenu');
    const themeToggle = document.getElementById('theme-toggle');
    const themeIcon = document.getElementById('theme-icon');
    const themeText = document.getElementById('theme-text');

    if (!moreToggle || !moreMenu || !themeToggle || !themeIcon || !themeText) {
        return;
    }

    const darkModeText = window.NEXAGRAM_CONFIG && window.NEXAGRAM_CONFIG.values ? window.NEXAGRAM_CONFIG.values[0] : 'Dark mode';
    const lightModeText = window.NEXAGRAM_CONFIG && window.NEXAGRAM_CONFIG.values ? window.NEXAGRAM_CONFIG.values[1] : 'Light mode';

    function setTheme(theme) {
        document.documentElement.setAttribute('data-theme', theme);
        localStorage.setItem('theme', theme);
        themeIcon.className = theme === 'dark' ? 'fa-solid fa-moon' : 'fa-solid fa-sun';
        themeText.textContent = theme === 'dark' ? darkModeText : lightModeText;
    }

    moreToggle.addEventListener('click', () => {
        const isOpen = moreMenu.classList.toggle('show');
        moreToggle.setAttribute('aria-expanded', String(isOpen));
    });

    document.addEventListener('click', (event) => {
        if (!moreMenu.contains(event.target) && !moreToggle.contains(event.target)) {
            moreMenu.classList.remove('show');
            moreToggle.setAttribute('aria-expanded', 'false');
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            moreMenu.classList.remove('show');
            moreToggle.setAttribute('aria-expanded', 'false');
            moreToggle.focus();
        }
    });

    themeToggle.addEventListener('click', () => {
        const currentTheme = document.documentElement.getAttribute('data-theme') || 'dark';
        setTheme(currentTheme === 'dark' ? 'light' : 'dark');
    });

    setTheme(localStorage.getItem('theme') || 'dark');
});
    