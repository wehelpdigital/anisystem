{{-- Delegated wall comment/reply/delete + "view all" modal handlers.
     Guarded singleton so it works on any page that renders wall posts (the
     profile wall AND the co-farmers "Latest"). Requires the shared modal
     (community.partials.wall-comments-modal) for "view all"; degrades safely
     if it's absent. Emoji/photo/react/mention/lightbox come from their own
     shared includes. --}}
<script>
(function () {
    if (window.__plazaWallCommentsBound) return;
    window.__plazaWallCommentsBound = true;

    const csrf = () => document.querySelector('meta[name=csrf-token]')?.content || '';
    const say = (m, t) => { if (typeof window.toast === 'function') window.toast(m, t); };
    const jsonHeaders = () => ({ 'X-CSRF-TOKEN': csrf(), Accept: 'application/json' });

    // Sending spinner + entrance animation come from comment-tools-js (shared).
    const fx = () => window.plazaCommentFx || {};
    const startSending = (b) => fx().startSending && fx().startSending(b);
    const stopSending = (b) => fx().stopSending && fx().stopSending(b);
    const animateIn = (n) => fx().animateIn && fx().animateIn(n);

    const SVG_PHOTO = '<svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>';
    const SVG_SMILE = '<svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>';
    const SVG_SEND = '<svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14m0 0l-6-6m6 6l-6 6"/></svg>';
    const SVG_X = '<svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>';
    const SVG_VIDEO = '<svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>';
    const SVG_REC = '<svg class="w-5 h-5 text-red-500" fill="none" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2"/><circle cx="12" cy="12" r="4.5" fill="currentColor"/></svg>';
    const escHtml = (s) => (window.escapeHtml ? window.escapeHtml(s) : String(s == null ? '' : s));
    // A "replying to @Name" chip — clean, visible proof the reply will tag them.
    // The actual @[Name](id) token is prepended on send (see submit handler).
    function replyMentionPill(name) {
        return `<span class="reply-mention-pill inline-flex items-center gap-1 text-[11px] font-semibold text-brand-700 bg-brand-50 border border-brand-100 rounded-full pl-2 pr-1 py-0.5 shrink-0" title="This reply notifies @${escHtml(name)}">@${escHtml(name)}<button type="button" class="js-reply-mention-x w-4 h-4 flex items-center justify-center rounded-full text-brand-400 hover:text-red-500 hover:bg-white leading-none" aria-label="Remove mention">×</button></span>`;
    }
    function replyFormHtml(postId, parentId, mentionId, mentionName) {
        const hasMention = mentionId && mentionName;
        const attrs = hasMention ? ` data-mention-id="${escHtml(mentionId)}" data-mention-name="${escHtml(mentionName)}"` : '';
        return `<form class="wall-comment-form wall-reply-form flex flex-wrap items-center gap-2 mt-2 mb-3" data-post-id="${postId}" data-parent-id="${parentId}"${attrs}>
            ${hasMention ? replyMentionPill(mentionName) : ''}
            <span class="reply-shell">
                <input type="text" placeholder="Reply…" maxlength="2000">
                <button type="button" class="emoji-btn js-comment-photo" aria-label="Attach a photo" title="Photo">${SVG_PHOTO}</button>
                <input type="file" class="js-comment-file hidden" accept="image/*">
                <button type="button" class="emoji-btn js-video-attach" aria-label="Upload a video" title="Video">${SVG_VIDEO}</button>
                <button type="button" class="emoji-btn js-video-record" aria-label="Record a video" title="Record">${SVG_REC}</button>
                <input type="file" class="js-video-file hidden" accept="video/*">
                <button type="button" class="emoji-btn js-emoji-btn" aria-label="Add an emoji" title="Emoji">${SVG_SMILE}</button>
                <button type="submit" class="reply-send" aria-label="Send">${SVG_SEND}</button>
            </span>
            <button type="button" class="js-reply-cancel btn-ghost rounded-full w-7 h-7 flex items-center justify-center text-gray-400 hover:text-red-500 shrink-0" aria-label="Cancel reply" title="Cancel">${SVG_X}</button>
            <span class="attach-chip hidden js-comment-chip"><span class="js-chip-name"></span><button type="button" class="js-chip-clear" aria-label="Remove photo">✕</button></span>
            <span class="js-video-chip attach-chip items-center gap-1 text-xs font-semibold text-gray-600" style="display:none"><span class="js-video-name"></span><button type="button" class="js-video-clear text-red-600 font-bold" aria-label="Remove video">✕</button></span>
        </form>`;
    }

    // Comment + reply submit (multipart — comments/replies can carry a photo).
    document.addEventListener('submit', async (e) => {
        const form = e.target.closest('.wall-comment-form');
        if (!form) return;
        e.preventDefault();
        const input = form.querySelector('input[type="text"]');
        const fileInput = form.querySelector('.js-comment-file');
        const videoInput = form.querySelector('.js-video-file');
        const body = input.value.trim();
        const file = fileInput && fileInput.files[0];
        const video = videoInput && videoInput.files[0];
        if (!body && !file && !video) { say('Write something or add a photo/video.', 'error'); return; }
        const postId = form.getAttribute('data-post-id');
        const parentId = form.getAttribute('data-parent-id');
        // Prepend the @mention token when this reply is tagging someone, so the
        // person is notified — the composer only shows the friendly pill.
        const mId = form.dataset.mentionId, mName = form.dataset.mentionName;
        const token = (mId && mName)
            ? (window.plazaMentionToken ? window.plazaMentionToken(mName, mId) : `@[${mName}](${mId}) `)
            : '';
        const sendBody = (token + body).trim();
        const fd = new FormData();
        if (sendBody) fd.append('body', sendBody);
        if (file) fd.append('image', file);
        if (video) fd.append('video', video);
        if (parentId) fd.append('parentId', parentId);
        const sendBtn = form.querySelector('button[type="submit"]');
        input.disabled = true;
        startSending(sendBtn);
        try {
            const res = await fetch(`/app/community/wall/${postId}/comment`, { method: 'POST', headers: { 'X-CSRF-TOKEN': csrf(), Accept: 'application/json' }, body: fd });
            const data = await res.json();
            if (data.success) {
                if (parentId) {
                    form.insertAdjacentHTML('beforebegin', data.data.html);
                    animateIn(form.previousElementSibling);
                    form.remove();
                } else {
                    const zone = form.closest('.wall-post').querySelector('.wall-comments');
                    zone.insertAdjacentHTML('beforeend', data.data.html);
                    animateIn(zone.lastElementChild);
                    input.value = '';
                    if (fileInput) fileInput.value = '';
                    if (window.plazaSetChip) window.plazaSetChip(form, null);
                    if (window.plazaClearVideo) window.plazaClearVideo(form);
                }
            } else say(data.message, 'error');
        } catch (_) { say('Network error — try again.', 'error'); }
        finally { input.disabled = false; stopSending(sendBtn); }
    });

    // Reply link → slide a reply form into the thread.
    document.addEventListener('click', (e) => {
        const btn = e.target.closest('.js-wall-reply');
        if (!btn) return;
        const parentId = btn.getAttribute('data-parent-id');
        const post = btn.closest('.wall-post');
        const zone = post && post.querySelector(`.wall-replies[data-parent-id="${parentId}"]`);
        if (!zone) return;
        const mId = btn.dataset.authorId, mName = btn.dataset.authorName;
        let form = zone.querySelector('.wall-reply-form');
        if (!form) {
            zone.insertAdjacentHTML('beforeend', replyFormHtml(post.getAttribute('data-post-id'), parentId, mId, mName));
            form = zone.querySelector('.wall-reply-form');
        } else if (mId && mName && !form.dataset.mentionId) {
            // Reusing an open reply box → attach the mention pill to it.
            form.dataset.mentionId = mId;
            form.dataset.mentionName = mName;
            form.insertAdjacentHTML('afterbegin', replyMentionPill(mName));
        }
        form.querySelector('input[type="text"]').focus();
    });

    // Remove the "replying to @Name" pill (and drop the pending mention).
    document.addEventListener('click', (e) => {
        const x = e.target.closest('.js-reply-mention-x');
        if (!x) return;
        const form = x.closest('.wall-reply-form');
        if (form) { delete form.dataset.mentionId; delete form.dataset.mentionName; }
        x.closest('.reply-mention-pill')?.remove();
    });

    // Cancel a reply → discard the typed text and remove the reply field.
    document.addEventListener('click', (e) => {
        const btn = e.target.closest('.js-reply-cancel');
        if (!btn) return;
        const form = btn.closest('.wall-reply-form');
        if (form) form.remove();
    });

    // Delete a whole post (owner/author only — the button only renders for them).
    document.addEventListener('click', async (e) => {
        const btn = e.target.closest('.wall-delete-btn');
        if (!btn) return;
        const ok = await confirmAction({ title: 'Delete post?', message: 'This removes the post and its comments.', confirmText: 'Delete' });
        if (!ok) return;
        try {
            const res = await fetch(`/app/community/wall/${btn.getAttribute('data-post-id')}`, { method: 'DELETE', headers: jsonHeaders() });
            const data = await res.json();
            if (data.success) { btn.closest('.wall-post').remove(); say(data.message); }
            else say(data.message, 'error');
        } catch (_) { say('Network error — try again.', 'error'); }
    });

    // Delete own comment/reply → tombstone.
    document.addEventListener('click', async (e) => {
        const btn = e.target.closest('.js-comment-delete');
        if (!btn) return;
        const ok = await confirmAction({ title: 'Delete comment?', message: 'It will show as "This comment was deleted".', confirmText: 'Delete' });
        if (!ok) return;
        try {
            const res = await fetch(`/app/community/wall-comment/${btn.getAttribute('data-comment-id')}`, { method: 'DELETE', headers: jsonHeaders() });
            const data = await res.json();
            if (data.success) {
                const holder = btn.closest('.wall-comment').querySelector('.flex > .min-w-0');
                if (holder) holder.innerHTML = '<div class="bg-gray-50 rounded-lg px-2.5 py-1.5 text-xs text-gray-400 italic wall-comment-tombstone tombstone-in">This comment was deleted</div>';
            } else say(data.message, 'error');
        } catch (_) { say('Network error — try again.', 'error'); }
    });

    // "View all comments" → expand the thread inline (accordion, no modal),
    // paginated: 10 at a time, auto-loading more as the sentinel scrolls in.
    function observeSentinel(el) {
        if (!el || !('IntersectionObserver' in window)) return;
        const obs = new IntersectionObserver((entries, o) => {
            if (entries.some((en) => en.isIntersecting)) { o.disconnect(); el.click(); }
        }, { rootMargin: '250px' });
        obs.observe(el);
    }
    document.addEventListener('click', async (e) => {
        const btn = e.target.closest('.js-view-all-comments, .js-load-more-comments');
        if (!btn) return;
        e.preventDefault();
        const post = btn.closest('.wall-post');
        const wrap = post && post.querySelector('.wall-comments');
        if (!wrap || wrap.dataset.loading) return;
        const postId = post.getAttribute('data-post-id');
        wrap.dataset.loading = '1';

        if (btn.classList.contains('js-view-all-comments')) {
            // First open: drop the preview + button, reload the full thread from page 1.
            wrap.dataset.page = '1';
            wrap.querySelectorAll('.wall-comment').forEach((c) => c.remove());
            btn.remove();
            wrap.insertAdjacentHTML('beforeend', '<div class="js-comments-loading text-xs text-gray-400 py-1.5">Loading…</div>');
        } else {
            btn.textContent = 'Loading…';
        }
        const page = parseInt(wrap.dataset.page || '1', 10);
        try {
            const res = await fetch(`/app/community/wall/${postId}/comments?page=${page}`, { headers: { Accept: 'application/json' } });
            const d = await res.json();
            wrap.querySelector('.js-comments-loading')?.remove();
            wrap.querySelector('.js-comments-sentinel')?.remove();
            if (d.success && d.data.html) {
                // Append each comment and reveal it with a small staggered animation.
                const tmp = document.createElement('div');
                tmp.innerHTML = d.data.html;
                let i = 0;
                Array.from(tmp.children).forEach((node) => {
                    wrap.appendChild(node);
                    if (node.classList && node.classList.contains('wall-comment')) {
                        node.classList.add('comment-reveal');
                        node.style.animationDelay = Math.min(i * 45, 400) + 'ms';
                        node.addEventListener('animationend', () => { node.classList.remove('comment-reveal'); node.style.animationDelay = ''; }, { once: true });
                        i++;
                    }
                });
            }
            if (d.data && d.data.hasMore) {
                wrap.dataset.page = String(d.data.nextPage);
                wrap.insertAdjacentHTML('beforeend', '<button type="button" class="js-load-more-comments js-comments-sentinel block text-xs font-semibold text-brand-700 hover:text-brand-800 py-1.5">Load more comments</button>');
                observeSentinel(wrap.querySelector('.js-comments-sentinel'));
            }
        } catch (_) {
            wrap.querySelector('.js-comments-loading')?.remove();
            say('Could not load comments.', 'error');
        } finally {
            delete wrap.dataset.loading;
        }
    });
})();
</script>
