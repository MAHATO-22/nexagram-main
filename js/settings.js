const themeSelect = document.getElementById('theme-select');

if (themeSelect) {
    const savedTheme = localStorage.getItem('theme');
    const initialTheme = savedTheme === 'light' ? 'light' : 'dark';

    function applyTheme(theme) {
        document.documentElement.setAttribute('data-theme', theme);
        localStorage.setItem('theme', theme);
        themeSelect.value = theme;
    }

    applyTheme(initialTheme);
    themeSelect.addEventListener('change', () => applyTheme(themeSelect.value));
}
