document.addEventListener('click', function (event) {
    const target = event.target instanceof Element
        ? event.target.closest('[data-ui-action]')
        : null;

    if (!target) return;

    const requireHandler = (name) => {
        if (typeof window[name] !== 'function') {
            console.error(`Nexagram action handler "${name}" is unavailable.`);
            return null;
        }
        return window[name];
    };

    switch (target.dataset.uiAction) {
        case 'toggle-lang-menu':
            requireHandler('toggleLangMenu')?.();
            break;
        case 'follow-user':
            requireHandler('handleFollowAction')?.(target, Number(target.dataset.targetId));
            break;
        case 'friend-action':
            requireHandler('handleFriendAction')?.(
                target,
                Number(target.dataset.targetId),
                target.dataset.friendAction
            );
            break;
        case 'unfriend':
            requireHandler('handleUnfriend')?.(target, Number(target.dataset.targetId));
            break;
        case 'friend-request':
            requireHandler('handleFriendRequest')?.(
                target,
                Number(target.dataset.targetId),
                target.dataset.friendAction
            );
            break;
        case 'open-edit-modal':
            requireHandler('openEditModal')?.(
                Number(target.dataset.postId),
                target.dataset.caption
            );
            break;
        case 'close-edit-modal':
            requireHandler('closeEditModal')?.();
            break;
        case 'feed-open-lightbox':
            try {
                requireHandler('openLightbox')?.(
                    JSON.parse(target.dataset.media),
                    Number(target.dataset.mediaIndex)
                );
            } catch (error) {
                console.error('Unable to open post media.', error);
            }
            break;
        case 'feed-close-lightbox':
            requireHandler('closeLightbox')?.();
            break;
        case 'feed-previous-lightbox':
            requireHandler('changeLightboxMedia')?.(-1);
            break;
        case 'feed-next-lightbox':
            requireHandler('changeLightboxMedia')?.(1);
            break;
        case 'feed-open-comments':
            requireHandler('openCommentSheet')?.(Number(target.dataset.postId));
            break;
        case 'feed-close-comments':
            requireHandler('closeCommentSheet')?.();
            break;
        case 'feed-cancel-reply':
            requireHandler('cancelReply')?.();
            break;
        case 'open-message-lightbox':
            requireHandler('openLightbox')?.(target.src);
            break;
        case 'close-message-lightbox':
            target.classList.remove('open');
            break;
        case 'register-role':
            requireHandler('setRole')?.(target.dataset.role);
            break;
        case 'go-back':
            event.preventDefault();
            window.history.back();
            break;
        case 'reply-to-comment':
            requireHandler('setReplyTarget')?.(
                Number(target.dataset.commentId),
                target.dataset.commentUsername
            );
            break;
        case 'like-comment':
            requireHandler('toggleCommentLike')?.(Number(target.dataset.commentId));
            break;
    }
}, true);

document.addEventListener('change', function (event) {
    const target = event.target instanceof Element
        ? event.target.closest('[data-ui-action]')
        : null;

    if (!target) return;

    if (target.dataset.uiAction === 'language-select') {
        window.location.assign(target.value);
    } else if (target.dataset.uiAction === 'preview-image') {
        if (typeof window.previewImage !== 'function') {
            console.error('Nexagram action handler "previewImage" is unavailable.');
            return;
        }
        window.previewImage(event);
    }
}, true);

document.addEventListener('submit', function (event) {
    const form = event.target;
    if (!(form instanceof HTMLFormElement) || !form.dataset.uiConfirm) return;

    if (!window.confirm(form.dataset.uiConfirm)) {
        event.preventDefault();
    }
}, true);
