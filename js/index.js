        // Language Dropdown Toggle Handler
        const langToggleBtn = document.getElementById('langDropdownToggle');
        const langContainer = document.getElementById('langDropdownContainer');

        langToggleBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            langContainer.classList.toggle('open');
        });

        document.addEventListener('click', (e) => {
            if (!langContainer.contains(e.target)) {
                langContainer.classList.remove('open');
            }
        });

        // JS Translations
        const txtReply = window.NEXAGRAM_CONFIG.values[0];
        const txtNoComments = window.NEXAGRAM_CONFIG.values[1];
        const txtCommentsLabel = window.NEXAGRAM_CONFIG.values[2];
        const darkTxt = window.NEXAGRAM_CONFIG.values[3];
        const lightTxt = window.NEXAGRAM_CONFIG.values[4];

        // Theme Toggle
        const themeToggleBtn = document.getElementById('theme-toggle');
        const themeIcon = document.getElementById('theme-icon');
        const themeText = document.getElementById('theme-text');

        function updateThemeUI(theme) {
            document.documentElement.setAttribute('data-theme', theme);
            localStorage.setItem('theme', theme);

            if (theme === 'dark') {
                if (themeIcon) themeIcon.className = 'fa-solid fa-moon';
                if (themeText) themeText.textContent = darkTxt;
            } else {
                if (themeIcon) themeIcon.className = 'fa-solid fa-sun';
                if (themeText) themeText.textContent = lightTxt;
            }
        }

        const initialTheme = localStorage.getItem('theme') || 'dark';
        updateThemeUI(initialTheme);

        if (themeToggleBtn) {
            themeToggleBtn.addEventListener('click', function() {
                const currentTheme = document.documentElement.getAttribute('data-theme') || 'dark';
                const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
                updateThemeUI(newTheme);
            });
        }

        document.querySelectorAll('.suggestion-follow').forEach(button => {
            button.addEventListener('click', async () => {
                const labels = button.querySelector('.suggestion-labels');
                const text = button.querySelector('span');
                const icon = button.querySelector('i');
                const feedback = button.closest('.suggestion-card').querySelector('.suggestion-feedback');
                button.disabled = true;

                try {
                    const formData = new FormData();
                    formData.append('target_user_id', button.dataset.targetId);
                    const response = await fetch('follow_process.php', { method: 'POST', body: formData });
                    const data = await response.json();
                    if (!response.ok || !['followed', 'unfollowed'].includes(data.status)) {
                        throw new Error(data.message || 'Unable to update follow status.');
                    }

                    const isFollowing = data.status === 'followed';
                    button.classList.toggle('is-following', isFollowing);
                    icon.className = isFollowing ? 'fa-solid fa-user-check' : 'fa-solid fa-user-plus';
                    text.textContent = isFollowing ? labels.dataset.following : labels.dataset.follow;
                    feedback.textContent = '';
                } catch (error) {
                    console.error('Suggestion follow action failed:', error);
                    feedback.textContent = button.dataset.errorLabel;
                } finally {
                    button.disabled = false;
                }
            });
        });

        document.querySelectorAll('.suggestion-friend').forEach(button => {
            button.addEventListener('click', async () => {
                const text = button.querySelector('span');
                const icon = button.querySelector('i');
                const feedback = button.closest('.suggestion-card').querySelector('.suggestion-feedback');
                button.disabled = true;

                try {
                    const formData = new FormData();
                    formData.append('target_id', button.dataset.targetId);
                    formData.append('action', 'send');
                    const response = await fetch('friend_process.php', { method: 'POST', body: formData });
                    const data = await response.json();
                    if (!response.ok || !data.success) {
                        throw new Error(data.error || 'Unable to send friend request.');
                    }

                    text.textContent = data.status === 'requested' ? button.dataset.pendingLabel : button.dataset.addLabel;
                    icon.className = data.status === 'requested' ? 'fa-solid fa-clock' : 'fa-solid fa-user-group';
                    button.classList.toggle('is-pending', data.status === 'requested');
                    button.disabled = data.status === 'requested';
                    feedback.textContent = '';
                } catch (error) {
                    console.error('Suggestion friend request failed:', error);
                    feedback.textContent = button.dataset.errorLabel;
                    button.disabled = false;
                }
            });
        });

        // Unread badges (notifications + messages) handled by badge.js

        // Lightbox Handler
        let currentLightboxItems = [];
        let currentLightboxIndex = 0;

        function openLightbox(items, index) {
            currentLightboxItems = items;
            currentLightboxIndex = index;
            renderLightboxItem();
            document.getElementById('lightboxModal').classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeLightbox() {
            document.getElementById('lightboxModal').classList.remove('active');
            document.body.style.overflow = '';
        }

        function renderLightboxItem() {
            const container = document.getElementById('lightboxContainer');
            const src = currentLightboxItems[currentLightboxIndex];
            const ext = src.split('.').pop().toLowerCase();

            if (['mp4', 'webm', 'mov'].includes(ext)) {
                container.innerHTML = `<video src="${src}" controls autoplay class="lightbox-content"></video>`;
            } else {
                container.innerHTML = `<img src="${src}" class="lightbox-content">`;
            }
        }

        function changeLightboxMedia(direction) {
            currentLightboxIndex += direction;
            if (currentLightboxIndex < 0) {
                currentLightboxIndex = currentLightboxItems.length - 1;
            } else if (currentLightboxIndex >= currentLightboxItems.length) {
                currentLightboxIndex = 0;
            }
            renderLightboxItem();
        }

        document.addEventListener('keydown', function(e) {
            const modal = document.getElementById('lightboxModal');
            if (!modal.classList.contains('active')) return;

            if (e.key === 'Escape') closeLightbox();
            if (e.key === 'ArrowLeft') changeLightboxMedia(-1);
            if (e.key === 'ArrowRight') changeLightboxMedia(1);
        });

        function updatePostLike(postId, reaction, mode) {
            const query = new URLSearchParams({ post_id: postId, reaction, mode });
            fetch('like_process.php?' + query.toString())
                .then(response => response.json())
                .then(data => {
                    if (!data.success) {
                        alert('Error: ' + (data.message || 'Action failed.'));
                        return;
                    }

                    const postCard = document.getElementById('post_' + postId);
                    const likeButton = postCard.querySelector('.like-btn');
                    const likeCount = postCard.querySelector('.like-count-num');
                    const reactionDisplay = likeButton.querySelector('.like-reaction');
                    likeCount.textContent = data.like_count;

                    if (data.is_liked) {
                        likeButton.classList.add('liked');
                        likeButton.dataset.reaction = data.reaction;
                        reactionDisplay.textContent = data.reaction;
                    } else {
                        likeButton.classList.remove('liked');
                        likeButton.dataset.reaction = '❤️';
                        reactionDisplay.textContent = '';
                        const heartIcon = document.createElement('i');
                        heartIcon.className = 'fa-regular fa-heart';
                        reactionDisplay.appendChild(heartIcon);
                    }
                })
                .catch(err => console.error('Like error:', err));
        }

        function hideReactionPicker(picker) {
            picker.classList.remove('show');
            const likeButton = document.querySelector(`[aria-controls="${picker.id}"]`);
            likeButton?.setAttribute('aria-expanded', 'false');
        }

        document.querySelectorAll('.like-btn').forEach(button => {
            const picker = document.getElementById(button.getAttribute('aria-controls'));
            let pressTimer = null;
            let suppressClick = false;

            function clearPressTimer() {
                if (pressTimer !== null) {
                    window.clearTimeout(pressTimer);
                    pressTimer = null;
                }
            }

            button.addEventListener('pointerdown', event => {
                if (event.pointerType === 'mouse' && event.button !== 0) return;
                clearPressTimer();
                pressTimer = window.setTimeout(() => {
                    picker.classList.add('show');
                    button.setAttribute('aria-expanded', 'true');
                    suppressClick = true;
                }, 450);
            });

            button.addEventListener('pointerup', clearPressTimer);
            button.addEventListener('pointercancel', () => {
                clearPressTimer();
                suppressClick = false;
            });

            button.addEventListener('click', event => {
                if (suppressClick) {
                    event.preventDefault();
                    event.stopPropagation();
                    suppressClick = false;
                    return;
                }
                updatePostLike(button.dataset.postId, button.dataset.reaction || '❤️', 'toggle');
                hideReactionPicker(picker);
            });
        });

        document.querySelectorAll('.reaction-option').forEach(option => {
            option.addEventListener('click', () => {
                updatePostLike(option.dataset.postId, option.dataset.reaction, 'react');
                document.querySelectorAll('.reaction-picker.show').forEach(hideReactionPicker);
            });
        });

        document.addEventListener('click', event => {
            if (event.target instanceof Element && !event.target.closest('.like-action-wrap')) {
                document.querySelectorAll('.reaction-picker.show').forEach(hideReactionPicker);
            }
        });

        // Load Comments & Comment Likes from Database
        const currentUsername = window.NEXAGRAM_CONFIG.values[5];
        const currentUserAvatar = window.NEXAGRAM_CONFIG.values[6];

        const postsCommentsData = window.NEXAGRAM_CONFIG.values[7];

        const overlay = document.getElementById('commentOverlay');
        const sheet = document.getElementById('commentSheet');
        const sheetBody = document.getElementById('sheetBody');
        const sheetPostIdInput = document.getElementById('sheetPostId');
        const sheetParentIdInput = document.getElementById('sheetParentId');
        const replyTargetTag = document.getElementById('replyTargetTag');
        const replyTargetUser = document.getElementById('replyTargetUser');
        const commentForm = document.getElementById('commentForm');
        const commentInput = document.getElementById('commentInput');
        const commentEmojiToggle = document.getElementById('commentEmojiToggle');
        const commentEmojiPicker = document.getElementById('commentEmojiPicker');

        commentEmojiToggle.addEventListener('click', () => {
            const isOpen = commentEmojiPicker.classList.toggle('show');
            commentEmojiToggle.setAttribute('aria-expanded', String(isOpen));
        });

        document.querySelectorAll('.comment-emoji-option').forEach(option => {
            option.addEventListener('click', () => {
                const emoji = option.dataset.emoji;
                const start = commentInput.selectionStart;
                const end = commentInput.selectionEnd;
                commentInput.setRangeText(emoji, start, end, 'end');
                commentInput.focus();
                commentEmojiPicker.classList.remove('show');
                commentEmojiToggle.setAttribute('aria-expanded', 'false');
            });
        });

        function openCommentSheet(postId) {
            commentEmojiPicker.classList.remove('show');
            commentEmojiToggle.setAttribute('aria-expanded', 'false');
            sheetPostIdInput.value = postId;
            cancelReply();
            renderComments(postId);

            overlay.style.display = 'block';
            setTimeout(() => overlay.style.opacity = '1', 10);
            sheet.classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeCommentSheet() {
            overlay.style.opacity = '0';
            sheet.classList.remove('active');
            document.body.style.overflow = '';
            setTimeout(() => overlay.style.display = 'none', 300);
        }

        function renderComments(postId) {
            sheetBody.innerHTML = '';
            const comments = postsCommentsData[postId] || [];

            if (comments.length === 0) {
                sheetBody.innerHTML = `<div class="empty-comments">${txtNoComments}</div>`;
                return;
            }

            const parents = comments.filter(c => !c.parent_id);
            const replies = comments.filter(c => c.parent_id);

            parents.forEach(parent => {
                appendCommentNode(parent, false);
                const childReplies = replies.filter(r => r.parent_id == parent.id);
                childReplies.forEach(reply => {
                    appendCommentNode(reply, true);
                });
            });
        }

        function appendCommentNode(comment, isReply) {
            const item = document.createElement('div');
            item.className = `panel-comment-item ${isReply ? 'reply-item' : ''}`;
            item.id = `comment_node_${comment.id}`;

            const escapeHtml = (value) => {
                const element = document.createElement('span');
                element.textContent = String(value ?? '');
                return element.innerHTML;
            };
            const usernameValue = String(comment.username ?? '');
            const username = escapeHtml(usernameValue);
            const avatarHTML = comment.avatar ?
                `<img src="${escapeHtml(comment.avatar)}" class="comment-avatar">` :
                `<div class="comment-avatar-circle">${escapeHtml(usernameValue.charAt(0))}</div>`;

            const heartClass = comment.is_liked ? 'fa-solid fa-heart' : 'fa-regular fa-heart';
            const likedClass = comment.is_liked ? 'liked' : '';

            item.innerHTML = `
                ${avatarHTML}
                <div class="comment-content-wrapper">
                    <div class="panel-comment-bubble" data-translatable>
                        <div class="panel-comment-user">${username}</div>
                        <div class="panel-comment-text" data-translation-source>${escapeHtml(comment.text)}</div>
                        <div class="translation-tools">
                            <button type="button" class="translate-button" data-translate-button data-label="${escapeHtml(document.documentElement.dataset.translationLabel)}">${escapeHtml(document.documentElement.dataset.translationLabel)}</button>
                            <span class="translation-result" data-translation-result hidden></span>
                        </div>
                    </div>
                    <div class="comment-actions">
                        <span class="comment-action-btn" data-ui-action="reply-to-comment" data-comment-id="${comment.id}" data-comment-username="${username}">${txtReply}</span>
                        <span class="comment-like-btn ${likedClass}" id="comment_like_btn_${comment.id}" data-ui-action="like-comment" data-comment-id="${comment.id}">
                            <i class="${heartClass}" id="comment_like_icon_${comment.id}"></i>
                            <span id="comment_like_count_${comment.id}">${comment.like_count > 0 ? comment.like_count : ''}</span>
                        </span>
                    </div>
                </div>
            `;
            sheetBody.appendChild(item);
        }

        function toggleCommentLike(commentId) {
            fetch('comment_like_process.php?comment_id=' + commentId)
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        const btn = document.getElementById('comment_like_btn_' + commentId);
                        const icon = document.getElementById('comment_like_icon_' + commentId);
                        const countSpan = document.getElementById('comment_like_count_' + commentId);

                        if (data.is_liked) {
                            btn.classList.add('liked');
                            icon.className = 'fa-solid fa-heart';
                        } else {
                            btn.classList.remove('liked');
                            icon.className = 'fa-regular fa-heart';
                        }

                        countSpan.innerText = data.like_count > 0 ? data.like_count : '';

                        const postId = sheetPostIdInput.value;
                        if (postsCommentsData[postId]) {
                            const found = postsCommentsData[postId].find(c => c.id == commentId);
                            if (found) {
                                found.is_liked = data.is_liked;
                                found.like_count = data.like_count;
                            }
                        }
                    } else {
                        alert('Error: ' + (data.message || ''));
                    }
                })
                .catch(err => {
                    console.error('Comment like error:', err);
                });
        }

        function setReplyTarget(parentId, username) {
            sheetParentIdInput.value = parentId;
            replyTargetUser.innerText = username;
            replyTargetTag.style.display = 'flex';
            commentInput.focus();
        }

        function cancelReply() {
            sheetParentIdInput.value = '';
            replyTargetTag.style.display = 'none';
        }

        commentForm.addEventListener('submit', function(e) {
            e.preventDefault();

            const postId = sheetPostIdInput.value;
            const parentId = sheetParentIdInput.value;
            const text = commentInput.value.trim();
            if (!text) return;

            const formData = new FormData();
            formData.append('post_id', postId);
            if (parentId) {
                formData.append('parent_id', parentId);
            }
            formData.append('comment_text', text);

            fetch('comment_process.php', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        const newComment = {
                            id: data.comment_id,
                            parent_id: parentId ? parseInt(parentId) : null,
                            username: currentUsername,
                            avatar: currentUserAvatar,
                            text: text,
                            like_count: 0,
                            is_liked: false
                        };

                        if (!postsCommentsData[postId]) {
                            postsCommentsData[postId] = [];
                        }

                        postsCommentsData[postId].push(newComment);

                        renderComments(postId);
                        commentInput.value = '';
                        cancelReply();

                        const postCard = document.getElementById('post_' + postId);
                        if (postCard) {
                            const countSpan = postCard.querySelector('.comment-count-text');
                            if (countSpan) {
                                countSpan.innerText = `${postsCommentsData[postId].length} ${txtCommentsLabel}`;
                            }
                        }

                        sheetBody.scrollTop = sheetBody.scrollHeight;
                    } else {
                        alert('Failed: ' + (data.message || ''));
                    }
                })
                .catch(err => {
                    console.error('Error adding comment:', err);
                });
        });