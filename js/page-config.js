const pageConfigElement = document.getElementById('page-config');
window.NEXAGRAM_CONFIG = pageConfigElement
    ? JSON.parse(pageConfigElement.dataset.config)
    : { values: [] };
