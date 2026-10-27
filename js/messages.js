        const ME = window.NEXAGRAM_CONFIG.values[0];
        const PEER = window.NEXAGRAM_CONFIG.values[1];
        const STATE = window.NEXAGRAM_CONFIG.values[2];
        const GROUP_ID = window.NEXAGRAM_CONFIG.values[11];
        const API = 'api_messages.php';

        const moreToggle = document.getElementById('moreToggle');
        const moreMenu = document.getElementById('moreMenu');
        const themeToggle = document.getElementById('theme-toggle');
        const themeIcon = document.getElementById('theme-icon');
        const themeText = document.getElementById('theme-text');
        const newMessageToggle = document.getElementById('newMessageToggle');
        const newMessageDialog = document.getElementById('newMessageDialog');
        const newMessageClose = document.getElementById('newMessageClose');
        const darkModeText = window.NEXAGRAM_CONFIG.values[4];
        const lightModeText = window.NEXAGRAM_CONFIG.values[5];
        const sendErrorLabels = window.NEXAGRAM_CONFIG.values.slice(6);

        if (newMessageDialog.dataset.openOnLoad === 'true') {
            newMessageDialog.showModal();
        }
        newMessageToggle.addEventListener('click', () => newMessageDialog.showModal());
        newMessageClose.addEventListener('click', () => newMessageDialog.close());
        newMessageDialog.addEventListener('click', event => {
            if (event.target === newMessageDialog) {
                newMessageDialog.close();
            }
        });

        function appendTranslationTools(container, sourceText) {
            const translatable = document.createElement('div');
            translatable.dataset.translatable = '';
            const source = document.createElement('div');
            source.dataset.translationSource = '';
            source.textContent = sourceText;
            const tools = document.createElement('div');
            tools.className = 'translation-tools';
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'translate-button';
            button.dataset.translateButton = '';
            button.dataset.label = document.documentElement.dataset.translationLabel;
            button.textContent = button.dataset.label;
            const result = document.createElement('span');
            result.className = 'translation-result';
            result.dataset.translationResult = '';
            result.hidden = true;
            tools.append(button, result);
            translatable.append(source, tools);
            container.appendChild(translatable);
        }

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

        if (GROUP_ID > 0) {
            const chatBody = document.getElementById('chatBody');
            const form = document.getElementById('msgForm');
            const textInput = document.getElementById('msgText');
            const panel = document.getElementById('emojiPanel');
            let lastId = window.NEXAGRAM_CONFIG.values[12];
            let sending = false;

            chatBody.scrollTop = chatBody.scrollHeight;

            function appendGroupMessage(message) {
                if (document.querySelector('.bubble-row[data-id="' + message.id + '"]')) return;
                const row = document.createElement('div');
                const mine = message.sender_id === ME;
                row.className = 'bubble-row ' + (mine ? 'mine' : 'theirs');
                row.dataset.id = message.id;

                const bubble = document.createElement('div');
                bubble.className = 'bubble';
                if (!mine) {
                    const sender = document.createElement('span');
                    sender.className = 'group-sender';
                    sender.textContent = message.sender_username;
                    bubble.appendChild(sender);
                }
                if (message.message_text) {
                    appendTranslationTools(bubble, message.message_text);
                }
                if (message.image_path) {
                    const image = document.createElement('img');
                    image.src = message.image_path;
                    image.className = 'msg-image';
                    image.dataset.uiAction = 'open-message-lightbox';
                    image.alt = '';
                    bubble.appendChild(image);
                }
                const time = document.createElement('span');
                time.className = 'time';
                const date = new Date(message.created_at.replace(' ', 'T'));
                time.textContent = date.toLocaleDateString('en-US', {month: 'short', day: '2-digit'}) + ' ' +
                    date.toLocaleTimeString('en-US', {hour: '2-digit', minute: '2-digit'});
                bubble.appendChild(time);
                row.appendChild(bubble);
                chatBody.appendChild(row);
                chatBody.scrollTop = chatBody.scrollHeight;
                lastId = Math.max(lastId, message.id);
            }

            setInterval(function () {
                fetch(API + '?action=group_fetch&group=' + GROUP_ID + '&since=' + lastId)
                    .then(response => response.json())
                    .then(data => {
                        if (data.error === 'group_not_found') {
                            location.href = 'messages.php';
                            return;
                        }
                        (data.messages || []).forEach(appendGroupMessage);
                    })
                    .catch(() => {});
            }, 3000);

            form.addEventListener('submit', function (event) {
                event.preventDefault();
                if (sending || !textInput.value.trim()) return;

                const formData = new FormData();
                formData.append('action', 'group_send');
                formData.append('group', GROUP_ID);
                formData.append('message_text', textInput.value.trim());
                sending = true;
                fetch(API + '?action=group_send', {method: 'POST', body: formData})
                    .then(response => response.json())
                    .then(data => {
                        sending = false;
                        if (data.error) {
                            alert(sendErrorText(data.error));
                            return;
                        }
                        appendGroupMessage(data.message);
                        textInput.value = '';
                        textInput.focus();
                    })
                    .catch(() => {
                        sending = false;
                        alert(sendErrorLabels[4]);
                    });
            });

            textInput.addEventListener('keydown', function (event) {
                if (event.key === 'Enter' && !event.shiftKey) {
                    event.preventDefault();
                    form.requestSubmit();
                }
            });

            const emojis = ['😀','😂','😅','😍','🥰','😘','😎','🤔','😢','😭','😡','🥺','😴','🤗','🤭','🙃','😇','🤩','😋','😜','👍','👎','👏','🙏','💪','🤝','❤️','💙','💚','🖤','🔥','✨','🎉','🎂','🌹','💐','☕','🍻','📸','💯'];
            emojis.forEach(emoji => {
                const button = document.createElement('button');
                button.type = 'button';
                button.textContent = emoji;
                button.addEventListener('click', () => {
                    textInput.value += emoji;
                    textInput.focus();
                });
                panel.appendChild(button);
            });
            document.getElementById('emojiBtn').addEventListener('click', () => panel.classList.toggle('open'));
        } else if (PEER > 0) {
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
                const bubble = document.createElement('div');
                bubble.className = 'bubble';
                if (m.message_text) appendTranslationTools(bubble, m.message_text);
                if (m.image_path) {
                    const image = document.createElement('img');
                    image.src = m.image_path;
                    image.className = 'msg-image';
                    image.dataset.uiAction = 'open-message-lightbox';
                    image.alt = '';
                    bubble.appendChild(image);
                }
                const time = document.createElement('span');
                time.className = 'time';
                time.textContent = timeLabel(m.created_at);
                bubble.appendChild(time);
                row.appendChild(bubble);
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
    