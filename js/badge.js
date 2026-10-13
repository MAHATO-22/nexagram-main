// badge.js - polls unread notification + direct message counts
function updateBadges() {
    fetch('api_unread_notifications.php')
        .then(res => res.json())
        .then(data => {
            const set = (id, n) => {
                const b = document.getElementById(id);
                if (!b) return;
                b.textContent = n;
                b.style.display = n > 0 ? 'inline-block' : 'none';
            };
            set('navNotifBadge', data.unread_count || 0);
            set('navMsgBadge', data.msg_count || 0);
        })
        .catch(err => console.error('Badge Error:', err));
}
updateBadges();
setInterval(updateBadges, 5000);
