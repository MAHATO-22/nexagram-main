const redirectNotice = document.getElementById('redirect-notice');

if (redirectNotice) {
    alert(redirectNotice.dataset.message);
    window.location.href = redirectNotice.dataset.target;
}
