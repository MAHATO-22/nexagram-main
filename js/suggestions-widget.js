(() => {
    const widget = document.getElementById('people-suggestions-widget');
    if (!widget) return;

    const config = JSON.parse(widget.dataset.config || '{}');
    const launcher = widget.querySelector('.people-suggestions-launcher');
    const panel = widget.querySelector('.people-suggestions-panel');
    const closeButton = widget.querySelector('.people-suggestions-close');
    const list = widget.querySelector('.people-suggestions-list');
    let loaded = false;
    let loading = false;

    function setOpen(open) {
        panel.hidden = !open;
        launcher.setAttribute('aria-expanded', String(open));
        if (open && !loaded && !loading) loadSuggestions();
    }

    function showMessage(message) {
        const element = document.createElement('p');
        element.className = 'people-suggestions-message';
        element.textContent = message;
        list.replaceChildren(element);
    }

    function addAction(container, action, id, label) {
        const button = document.createElement('button');
        button.type = 'button';
        button.textContent = label;
        button.dataset.targetId = id;
        button.dataset.action = action;
        container.append(button);
    }

    function renderSuggestions(suggestions) {
        list.replaceChildren();
        if (!suggestions.length) {
            showMessage(config.empty);
            return;
        }

        suggestions.forEach(person => {
            const card = document.createElement('article');
            card.className = 'people-suggestion-card';

            const profile = document.createElement('a');
            profile.className = 'people-suggestion-person';
            profile.href = `profile.php?id=${encodeURIComponent(person.id)}`;

            const avatar = person.avatar_url ? document.createElement('img') : document.createElement('span');
            avatar.className = 'people-suggestion-avatar';
            if (person.avatar_url) {
                avatar.src = person.avatar_url;
                avatar.alt = '';
            } else {
                avatar.textContent = Array.from(person.username || '?')[0];
            }
            profile.append(avatar);

            const copy = document.createElement('span');
            copy.className = 'people-suggestion-copy';
            const name = document.createElement('strong');
            name.textContent = person.username;
            const reason = document.createElement('span');
            reason.textContent = config[person.match_reason] || config.platform;
            copy.append(name, reason);
            profile.append(copy);
            card.append(profile);

            const actions = document.createElement('div');
            actions.className = 'people-suggestion-actions';
            addAction(actions, 'follow', person.id, config.follow);
            addAction(actions, 'friend', person.id, config.addFriend);
            card.append(actions);

            const status = document.createElement('span');
            status.className = 'people-suggestion-status';
            status.setAttribute('aria-live', 'polite');
            card.append(status);
            list.append(card);
        });

    }

    async function loadSuggestions() {
        loading = true;
        showMessage(config.loading);
        try {
            const response = await fetch('suggestions_api.php', { headers: { Accept: 'application/json' } });
            const data = await response.json();
            if (!response.ok || !data.success) throw new Error(data.message || config.error);
            loaded = true;
            renderSuggestions(data.suggestions || []);
        } catch (error) {
            console.error('Could not load people suggestions:', error);
            showMessage(config.error);
        } finally {
            loading = false;
        }
    }

    async function handleActions(event) {
        const button = event.target.closest('button[data-action]');
        if (!button) return;

        const card = button.closest('.people-suggestion-card');
        const status = card.querySelector('.people-suggestion-status');
        button.disabled = true;
        try {
            const formData = new FormData();
            formData.append('target_id', button.dataset.targetId);
            if (button.dataset.action === 'follow') {
                const response = await fetch('follow_process.php', { method: 'POST', body: formData });
                const data = await response.json();
                if (!response.ok || !['followed', 'unfollowed'].includes(data.status)) throw new Error(config.followError);
                if (data.status === 'followed') {
                    button.textContent = config.following;
                    button.disabled = true;
                } else {
                    button.textContent = config.follow;
                    button.disabled = false;
                }
            } else {
                formData.append('action', 'send');
                const response = await fetch('friend_process.php', { method: 'POST', body: formData });
                const data = await response.json();
                if (!response.ok || !data.success) throw new Error(config.friendError);
                if (data.status === 'requested' || data.status === 'pending') {
                    button.textContent = config.pending;
                    button.disabled = true;
                } else {
                    button.disabled = false;
                }
            }
            status.textContent = '';
        } catch (error) {
            console.error('People suggestion action failed:', error);
            status.textContent = button.dataset.action === 'follow' ? config.followError : config.friendError;
            button.disabled = false;
        }
    }

    launcher.addEventListener('click', () => setOpen(panel.hidden));
    closeButton.addEventListener('click', () => setOpen(false));
    list.addEventListener('click', handleActions);
    document.addEventListener('keydown', event => {
        if (event.key === 'Escape' && !panel.hidden) setOpen(false);
    });
})();
