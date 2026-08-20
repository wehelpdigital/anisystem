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
        return `<span class="reply-mention-pill inline-flex items-center gap-1 text-[0.688rem] font-semibold text-brand-700 bg-brand-50 border border-brand-100 rounded-full pl-2 pr-1 py-0.5 shrink-0" title="This reply notifies @${escHtml(name)}">@${escHtml(name)}<button type="button" class="js-reply-mention-x w-4 h-4 flex items-center justify-center rounded-full text-brand-400 hover:text-red-500 hover:bg-white leading-none" aria-label="Remove mention">×</button></span>`;
    }
    function replyFormHtml(postId, parentId, mentionId, mentionName) {
        const hasMention = mentionId && mentionName;
        const attrs = hasMention ? ` data-mention-id="${escHtml(mentionId)}" data-mention-name="${escHtml(mentionName)}"` : '';
        // The field takes the whole first line of the shell and the tools sit
        // under it (see .reply-shell) — on a phone they used to squeeze the
        // typing room down to a few characters. Cancel moved in beside Send so
        // it joins that tool row instead of stranding a ✕ on a line of its own;
        // it is still inside the form, which is all its handler looks for.
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
                <button type="button" class="js-reply-cancel btn-ghost rounded-full w-7 h-7 flex items-center justify-center text-gray-400 hover:text-red-500 shrink-0" aria-label="Cancel reply" title="Cancel">${SVG_X}</button>
                <button type="submit" class="reply-send" aria-label="Send">${SVG_SEND}</button>
            </span>
            <span class="attach-chip hidden js-comment-chip"><span class="js-chip-name"></span><button type="button" class="js-chip-clear" aria-label="Remove photo">✕</button></span>
            <span class="js-video-chip attach-chip items-center gap-1 text-xs font-semibold text-gray-600" style="display:none"><span class="js-video-name"></span><button type="button" class="js-video-clear text-red-600 font-bold" aria-label="Remove video">✕</button></span>
        </form>`;
    }

    /* ================================================================
     * Long threads fold themselves.
     *
     * Past two entries a card stops being a card, so everything after the
     * first two slides behind one toggle — for a post's comments and for a
     * comment's replies alike.
     *
     * This lives here and not in the Blade partials because four different
     * templates print this markup and fetch reprints it on top of them; one
     * observer cannot drift out of step with itself the way four copies of
     * the rule would. It also means a folded thread comes back folded after
     * the feed re-renders, without any of those templates knowing.
     * ============================================================== */
    const FOLD_AFTER = 2;
    const ZONES = [
        { sel: '.wall-comments', item: 'wall-comment', word: 'comments' },
        { sel: '.wall-replies', item: 'wall-comment', word: 'replies' },
        { sel: '.cp-replies', item: 'cp-comment', word: 'replies' },
    ];
    const ZONE_SEL = ZONES.map((z) => z.sel).join(',');
    const CHEV = '<svg class="th-chev" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>';
    const DOTS = '<svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><circle cx="5" cy="12" r="1.9"/><circle cx="12" cy="12" r="1.9"/><circle cx="19" cy="12" r="1.9"/></svg>';
    const reduced = () => window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const kindOf = (zone) => ZONES.find((z) => zone.matches(z.sel));
    let observer = null;

    // Every entry of a zone in reading order, the folded-away ones included.
    function threadEntries(zone, cls) {
        const out = [];
        for (const n of zone.children) {
            if (!n.classList) continue;
            if (n.classList.contains(cls)) out.push(n);
            else if (n.classList.contains('thread-fold') && n.firstElementChild) {
                for (const k of n.firstElementChild.children) {
                    if (k.classList && k.classList.contains(cls)) out.push(k);
                }
            }
        }
        return out;
    }

    // Lift the tail back out. Every other handler in this file walks the shape
    // the server printed, so an opened thread must end up looking like it.
    function flatten(zone) {
        const fold = zone.querySelector(':scope > .thread-fold');
        if (!fold) return;
        const box = fold.firstElementChild;
        while (box && box.firstChild) fold.before(box.firstChild);
        fold.remove();
    }

    function syncToggle(zone) {
        const kind = kindOf(zone);
        const toggle = zone.querySelector(':scope > .js-thread-toggle');
        if (!kind || !toggle) return;
        const open = zone.dataset.threadOpen === '1';
        const text = open
            ? 'Show fewer ' + kind.word
            : 'Show all ' + threadEntries(zone, kind.item).length + ' ' + kind.word;
        if (toggle.dataset.label !== text) {
            toggle.dataset.label = text;
            toggle.innerHTML = escHtml(text) + CHEV;
        }
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    }

    function foldZone(zone) {
        const kind = kindOf(zone);
        // A thread the reader opened stays open, and one mid-fetch is not ours
        // to rearrange until its comments have landed.
        if (!kind || zone.dataset.threadOpen === '1' || zone.dataset.loading) return;
        const list = threadEntries(zone, kind.item);
        let fold = zone.querySelector(':scope > .thread-fold');
        let toggle = zone.querySelector(':scope > .js-thread-toggle');

        if (list.length <= FOLD_AFTER) {
            flatten(zone);
            toggle?.remove();
            return;
        }

        // Only rebuild when the tail is not already sitting where it belongs —
        // this runs on every mutation, and needless moves would blur a field.
        const tail = list.slice(FOLD_AFTER);
        const box = fold && fold.firstElementChild;
        const settled = box && box.children.length === tail.length
            && tail.every((n, i) => box.children[i] === n)
            && fold.previousElementSibling === list[FOLD_AFTER - 1];
        if (!settled) {
            if (!fold) {
                fold = document.createElement('div');
                fold.className = 'thread-fold';
                fold.appendChild(document.createElement('div'));
            }
            list[FOLD_AFTER - 1].after(fold);
            tail.forEach((n) => fold.firstElementChild.appendChild(n));
        }
        if (!toggle) {
            toggle = document.createElement('button');
            toggle.type = 'button';
            toggle.className = 'thread-toggle js-thread-toggle';
            zone.prepend(toggle);
        } else if (zone.firstElementChild !== toggle) {
            // Above the thread, where the server's own "view all" button sat,
            // so nothing appended to the zone later can land on top of it.
            zone.prepend(toggle);
        }
        syncToggle(zone);
    }

    // Open a thread for good: after this the reader owns it, and no re-render
    // folds it back behind them.
    function openZone(zone, animate) {
        if (!zone || !kindOf(zone)) return;
        zone.dataset.threadOpen = '1';
        const fold = zone.querySelector(':scope > .thread-fold');
        if (!fold) { syncToggle(zone); return; }
        const settle = () => {
            if (!fold.isConnected) return;
            flatten(zone);
            observer?.takeRecords();
        };
        if (animate === false || reduced()) { settle(); syncToggle(zone); return; }
        fold.addEventListener('transitionend', function done(ev) {
            if (ev.target !== fold || ev.propertyName !== 'grid-template-rows') return;
            fold.removeEventListener('transitionend', done);
            settle();
        });
        // A dropped transitionend (a hidden tab, a folded card scrolled away)
        // would leave the tail wrapped forever; the height is already right by
        // then, so flattening late costs nothing.
        setTimeout(settle, 600);
        fold.classList.add('is-open');
        syncToggle(zone);
    }

    function closeZone(zone) {
        if (!zone || !kindOf(zone)) return;
        delete zone.dataset.threadOpen;
        foldZone(zone);
        const fold = zone.querySelector(':scope > .thread-fold');
        if (fold && !reduced()) {
            // The wrapper is born collapsed, so there is nothing to travel from.
            // Paint it open, force the measurement, then let go.
            fold.classList.add('is-open');
            void fold.offsetHeight;
            fold.classList.remove('is-open');
        }
        observer?.takeRecords();
        syncToggle(zone);
    }

    /* ---- One ⋯ instead of an action row that wraps ---------------------
       A comment's row already carries four reaction pills; Reply and Delete
       beside them pushed the reactions onto a second line on a phone.
       Reacting is the frequent tap, so it keeps the row and the two rare
       actions move into a sheet. The buttons themselves are only hidden by
       CSS — everything that reacts to them is bound by delegation and still
       fires when the sheet clicks them. ---- */
    function condense(comment) {
        const btn = comment.querySelector('.js-wall-reply, .js-comment-delete');
        // querySelector reaches into nested replies too; only this comment's
        // own row belongs to this comment.
        if (!btn || btn.closest('.wall-comment') !== comment) return;
        const row = btn.parentElement;
        row.classList.add('wc-actions');
        if (row.querySelector(':scope > .js-comment-more')) return;
        const more = document.createElement('button');
        more.type = 'button';
        more.className = 'wc-more js-comment-more';
        more.setAttribute('aria-label', 'More actions');
        more.setAttribute('aria-haspopup', 'dialog');
        more.innerHTML = DOTS;
        row.appendChild(more);
    }

    function openActionSheet(reply, del) {
        let el = document.getElementById('wc-action-sheet');
        if (!el) {
            el = document.createElement('div');
            el.id = 'wc-action-sheet';
            el.className = 'sheet hidden';
            el.style.setProperty('--sheet-width', '22rem');
            document.body.appendChild(el);
        }
        const ICON_REPLY = '<svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h10a6 6 0 016 6v2M3 10l5-5M3 10l5 5"/></svg>';
        const ICON_DEL = '<svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.9 12.1a2 2 0 01-2 1.9H7.9a2 2 0 01-2-1.9L5 7m3 0V5a2 2 0 012-2h4a2 2 0 012 2v2m-11 0h16"/></svg>';
        el.innerHTML = '<div class="sheet-handle"></div><div class="sheet-body pt-4">'
            + (reply ? '<button type="button" class="wc-menu-item" data-do="reply">' + ICON_REPLY + 'Reply</button>' : '')
            + (del ? '<button type="button" class="wc-menu-item is-danger" data-do="delete">' + ICON_DEL + 'Delete</button>' : '')
            + '<button type="button" class="wc-menu-item is-quiet" data-sheet-close>Cancel</button></div>';
        el.querySelectorAll('[data-do]').forEach((b) => {
            b.addEventListener('click', () => {
                const target = b.dataset.do === 'reply' ? reply : del;
                window.closeSheet('wc-action-sheet');
                // Wait out the sheet's own 250ms close before the reply field
                // asks for the keyboard, or Delete puts up its confirmation —
                // two sheets overlapping leaves the backdrop half-dressed.
                setTimeout(() => target && target.click(), 300);
            });
        });
        window.openSheet('wc-action-sheet');
    }

    document.addEventListener('click', (e) => {
        const more = e.target.closest('.js-comment-more');
        if (!more) return;
        const row = more.parentElement;
        openActionSheet(row.querySelector(':scope > .js-wall-reply'), row.querySelector(':scope > .js-comment-delete'));
    });

    document.addEventListener('click', (e) => {
        const toggle = e.target.closest('.js-thread-toggle');
        if (!toggle) return;
        e.preventDefault();
        const zone = toggle.parentElement;
        if (zone.dataset.threadOpen === '1') closeZone(zone);
        else openZone(zone, true);
    });

    function scan(root) {
        const at = root || document;
        at.querySelectorAll(ZONE_SEL).forEach(foldZone);
        at.querySelectorAll('.wall-comment').forEach(condense);
        // Our own reshuffling is not news; dropping it keeps the observer from
        // calling us straight back into another pass.
        observer?.takeRecords();
    }
    window.plazaThreadFold = { scan, open: openZone, close: closeZone };

    let queued = false;
    const rescan = () => {
        if (queued) return;
        queued = true;
        requestAnimationFrame(() => { queued = false; scan(); });
    };
    const interesting = (r) => {
        const t = r.target;
        if (!t || t.nodeType !== 1) return false;
        if (t.closest('.wall-post, .wall-comment, .cp-comment')) return true;
        return Array.from(r.addedNodes).some((n) => n.nodeType === 1
            && (n.matches?.('.wall-post, .wall-comment, .cp-comment') || n.querySelector?.('.wall-comments, .cp-replies')));
    };
    observer = new MutationObserver((records) => { if (records.some(interesting)) rescan(); });
    observer.observe(document.documentElement, { childList: true, subtree: true });
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', () => scan(), { once: true });
    else scan();

    /* Which post an element belongs to.
     *
     * On the wall that is the card it sits in. In the comment sheet there is
     * no card — the sheet declares itself the scope instead, so every
     * handler below works in both places without being written twice. */
    const postScope = (el) => el.closest('.wall-post') || el.closest('[data-comment-scope]');

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
                    // Answering a thread is asking to see it: fold it open
                    // first, or the reply lands under a "Show all 6 replies".
                    openZone(form.closest('.wall-replies'), false);
                    form.insertAdjacentHTML('beforebegin', data.data.html);
                    animateIn(form.previousElementSibling);
                    form.remove();
                } else {
                    const zone = postScope(form)?.querySelector('.wall-comments');
                    if (!zone) { input.value = ''; return; }
                    openZone(zone, false);
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
        const post = postScope(btn);
        const mId = btn.dataset.authorId, mName = btn.dataset.authorName;

        const zone = post && post.querySelector(`.wall-replies[data-parent-id="${parentId}"]`);
        if (!zone) return;
        // Show what is being replied to before asking for the reply.
        openZone(zone, true);
        let form = zone.querySelector('.wall-reply-form');
        if (!form) {
            zone.insertAdjacentHTML('beforeend', replyFormHtml(post.getAttribute('data-post-id'), parentId, mId, mName));
            form = zone.querySelector('.wall-reply-form');
            growIn(form);
        } else if (mId && mName && !form.dataset.mentionId) {
            // Reusing an open reply box → attach the mention pill to it.
            form.dataset.mentionId = mId;
            form.dataset.mentionName = mName;
            form.insertAdjacentHTML('afterbegin', replyMentionPill(mName));
        }
        // Shown, not typed into, on a phone: the keyboard would cover the
        // comment being answered.
        if (!window.matchMedia('(pointer: coarse)').matches) form.querySelector('input[type="text"]').focus();
    });

    /* A field that grows into its place rather than being dropped in at full
     * height — the same motion the discussion room's replies use. */
    function growIn(el) {
        if (!el || window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
        el.style.maxHeight = 'none';
        const h = el.getBoundingClientRect().height;
        el.style.overflow = 'hidden';
        el.style.maxHeight = '0px';
        el.style.opacity = '0';
        requestAnimationFrame(() => {
            el.style.transition = 'max-height .28s cubic-bezier(.22,1,.36,1), opacity .28s cubic-bezier(.22,1,.36,1)';
            el.style.maxHeight = h + 'px';
            el.style.opacity = '1';
        });
        const done = () => {
            el.style.transition = ''; el.style.maxHeight = '';
            el.style.overflow = ''; el.style.opacity = '';
        };
        el.addEventListener('transitionend', (e) => { if (e.propertyName === 'max-height') done(); });
        setTimeout(done, 460);
    }

    // Remove the "replying to @Name" pill (and drop the pending mention).
    document.addEventListener('click', (e) => {
        const x = e.target.closest('.js-reply-mention-x');
        if (!x) return;
        const form = x.closest('.wall-reply-form, .wall-comment-form');
        if (form) {
            delete form.dataset.mentionId;
            delete form.dataset.mentionName;
            // The sheet's box is shared, so dropping the name drops the
            // thread it was aimed at — otherwise the next comment quietly
            // lands under somebody else's.
            form.removeAttribute('data-parent-id');
        }
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
        const post = postScope(btn);
        const wrap = post && post.querySelector('.wall-comments');
        if (!wrap || wrap.dataset.loading) return;
        const postId = post.getAttribute('data-post-id');
        wrap.dataset.loading = '1';
        // Asked for the whole thread from the server: the client-side fold has
        // no business collapsing what just arrived.
        wrap.dataset.threadOpen = '1';

        if (btn.classList.contains('js-view-all-comments')) {
            // First open: drop the preview + button, reload the full thread from page 1.
            wrap.dataset.page = '1';
            wrap.querySelectorAll('.wall-comment').forEach((c) => c.remove());
            // The client-side fold's own scaffolding goes with the preview it
            // was folding, or an empty wrapper and a stale toggle stay behind.
            wrap.querySelector(':scope > .thread-fold')?.remove();
            wrap.querySelector(':scope > .js-thread-toggle')?.remove();
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
