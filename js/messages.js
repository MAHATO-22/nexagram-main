        const ME = window.NEXAGRAM_CONFIG.values[0];
        const PEER = window.NEXAGRAM_CONFIG.values[1];
        const STATE = window.NEXAGRAM_CONFIG.values[2];
        const API = 'api_messages.php';

        const moreToggle = document.getElementById('moreToggle');
        const moreMenu = document.getElementById('moreMenu');
        const themeToggle = document.getElementById('theme-toggle');
        const themeIcon = document.getElementById('theme-icon');
        const themeText = document.getElementById('theme-text');
        const darkModeText = window.NEXAGRAM_CONFIG.values[4];
        const lightModeText = window.NEXAGRAM_CONFIG.values[5];
        const sendErrorLabels = window.NEXAGRAM_CONFIG.values.slice(6);

        function setTheme(theme) {
            document.documentElement.setAttribute('data-theme', theme);
            localStorage.setItem('theme', theme);
            themeIcon.className = theme === 'dark' ? 'fa-solid fa-moon' : 'fa-solid fa-sun';
            themeText.textContent = theme === 'dark' ? darkModeText : lightModeText;
        }

        setTheme(localStorage.getItem('theme') || 'dark');
        themeToggle.addEventListener('click', () => {
            const currentTheme = document.documentElement.getAttribute('data-theme') || 'dark';
            setTheme(currentTheme === 'dark' ? 'light' : 'dark');
        });

        moreToggle.addEventListener('click', () => {
            const isOpen = moreMenu.classList.toggle('show');
            moreToggle.setAttribute('aria-expanded', String(isOpen));
        });

        document.addEventListener('click', event => {
            if (!moreMenu.contains(event.target) && !moreToggle.contains(event.target)) {
                moreMenu.classList.remove('show');
                moreToggle.setAttribute('aria-expanded', 'false');
            }
        });

        function openLightbox(src) {
            document.getElementById('lightboxImg').src = src;
            document.getElementById('lightbox').classList.add('open');
        }

        // Reply / Block / Unblock buttons (banner + header)
        document.querySelectorAll('[data-action]').forEach(btn => {
            btn.addEventListener('click', function () {
                btn.disabled = true;
                const fd = new FormData();
                fd.append('action', btn.dataset.action);
                fd.append('user', PEER);
                fetch(API + '?action=' + btn.dataset.action, { method: 'POST', body: fd })
                    .then(r => r.json())
                    .then(() => location.reload())
                    .catch(() => { btn.disabled = false; });
            });
        });

        if (PEER > 0) {
            const chatBody = document.getElementById('chatBody');
            const form = document.getElementById('msgForm');
            const textInput = document.getElementById('msgText');
            const imageInput = document.getElementById('imageInput');
            const attachName = document.getElementById('attachName');
            let lastId = window.NEXAGRAM_CONFIG.values[3];
            let sending = false;

            if (chatBody) chatBody.scrollTop = chatBody.scrollHeight;

            function timeLabel(iso) {
                const d = new Date(iso.replace(' ', 'T'));
                return d.toLocaleDateString('en-US', {month:'short', day:'2-digit'}) + ' ' +
                       d.toLocaleTimeString('en-US', {hour:'2-digit', minute:'2-digit'});
            }

            function appendMessage(m, mine) {
                if (document.querySelector('.bubble-row[data-id="' + m.id + '"]')) return;
                const row = document.createElement('div');
                row.className = 'bubble-row ' + (mine ? 'mine' : 'theirs');
                row.dataset.id = m.id;
                let inner = '';
                if (m.message_text) inner += m.message_text.split('\n').map(t => t.replace(/&/g,'&amp;').replace(/</g,'&lt;')).join('<br>');
                if (m.image_path) inner += '<img src="' + m.image_path + '" class="msg-image" data-ui-action="open-message-lightbox">';
                inner += '<span class="time">' + timeLabel(m.created_at) + '</span>';
                row.innerHTML = '<div class="bubble">' + inner + '</div>';
                chatBody.appendChild(row);
                chatBody.scrollTop = chatBody.scrollHeight;
                lastId = Math.max(lastId, m.id);
            }

            // Poll for new messages + thread state changes (reply/block/unblock by peer)
            setInterval(function () {
                fetch(API + '?action=fetch&user=' + PEER + '&since=' + lastId)
                    .then(r => r.json())
                    .then(data => {
                        if (data.state && data.state !== STATE) { location.reload(); return; }
                        (data.messages || []).forEach(m => appendMessage(m, m.sender_id === ME));
                    })
                    .catch(() => {});
            }, 3000);

            if (form) {
                // Send text / photo
                form.addEventListener('submit', function (e) {
                    e.preventDefault();
                    if (sending) return;
                    const fd = new FormData();
                    fd.append('action', 'send');
                    fd.append('to', PEER);
                    fd.append('message_text', textInput.value.trim());
                    if (imageInput.files[0]) fd.append('image', imageInput.files[0]);
                    if (!textInput.value.trim() && !imageInput.files[0]) return;

                    sending = true;
                    fetch(API + '?action=send', { method: 'POST', body: fd })
                        .then(r => r.json())
                        .then(data => {
                            sending = false;
                            if (data.error) { alert(sendErrorText(data.error)); return; }
                            appendMessage(data.message, true);
                            textInput.value = '';
                            imageInput.value = '';
                            attachName.textContent = '';
                            textInput.focus();
                        })
                        .catch(() => { sending = false; alert(sendErrorLabels[4]); });
                });

                textInput.addEventListener('keydown', function (e) {
                    if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); form.requestSubmit(); }
                });

                imageInput.addEventListener('change', function () {
                    attachName.textContent = imageInput.files[0] ? imageInput.files[0].name : '';
                });

                // Emoji picker
                const EMOJIS = ['😀','😂','😅','😍','🥰','😘','😎','🤔','😢','😭','😡','🥺','😴','🤗','🤭','🙃','😇','🤩','😋','😜','👍','👎','👏','🙏','💪','🤝','❤️','💙','💚','🖤','🔥','✨','🎉','🎂','🌹','💐','☕','🍻','📸','💯'];
                const panel = document.getElementById('emojiPanel');
                EMOJIS.forEach(em => {
                    const b = document.createElement('button');
                    b.type = 'button';
                    b.textContent = em;
                    b.addEventListener('click', () => { textInput.value += em; textInput.focus(); });
                    panel.appendChild(b);
                });
                document.getElementById('emojiBtn').addEventListener('click', () => panel.classList.toggle('open'));
            }
        }

        function sendErrorText(code) {
            const map = {
                'request_awaiting_reply': sendErrorLabels[0],
                'you_blocked_this_user': sendErrorLabels[1],
                'blocked_by_peer': sendErrorLabels[2],
                'empty_message': sendErrorLabels[3]
            };
            return map[code] || sendErrorLabels[4];
        }
    