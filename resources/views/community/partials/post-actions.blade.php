@once
{{-- Everything a post card can open, defined once per page however many cards
     there are: the comment sheet, the share sheet, and the handlers behind
     Follow / Save / Share.

     Delegated from the document rather than bound per card, because cards
     arrive by fetch as the reader scrolls and listeners bound at load would
     never meet them. --}}
<div class="sheet hidden" id="wallCommentSheet" style="--sheet-width:34rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title">Comments</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body">
        <div id="wcsList" class="space-y-1.5"></div>
        <p class="wcs-state" id="wcsState">Loading…</p>
        <button type="button" class="btn btn-white btn-sm w-full mt-2 hidden" id="wcsMore">Show older comments</button>
    </div>
    <div class="sheet-footer" id="wcsFoot"></div>
</div>

<div class="sheet hidden" id="wallShareSheet" style="--sheet-width:26rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title">Share this post</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body space-y-1">
        <button type="button" class="ai-attach-opt" id="shareToWall">
            <span class="ic"><svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg></span>
            <span>Share to my wall<span class="sub">Add your own words above it</span></span>
        </button>
        <button type="button" class="ai-attach-opt" id="shareToMessage">
            <span class="ic"><svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 12h8m-8-4h5m-6 12V6a2 2 0 012-2h12a2 2 0 012 2v8a2 2 0 01-2 2H8l-3 4z"/></svg></span>
            <span>Send to a co-farmer<span class="sub">As a private message</span></span>
        </button>
        <button type="button" class="ai-attach-opt" id="shareExternal">
            <span class="ic"><svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.8 10.2a4 4 0 010 5.7l-3 3a4 4 0 01-5.7-5.7l1.5-1.5m7.2-2.4a4 4 0 015.7 0 4 4 0 010 5.7l-1.5 1.5"/></svg></span>
            <span>Get a public link<span class="sub">Messenger, Facebook, or anywhere</span></span>
        </button>
    </div>
</div>

{{-- Your own words above somebody else's post, the way everyone expects. --}}
<div class="sheet hidden" id="shareWallSheet" style="--sheet-width:26rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title">Share to my wall</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body space-y-3">
        <textarea id="shareWallBody" class="form-textarea" rows="3" maxlength="5000" placeholder="Say something about this (optional)…"></textarea>
        <button type="button" id="shareWallGo" class="btn btn-primary w-full">Share</button>
    </div>
</div>

<div class="sheet hidden" id="shareDmSheet" style="--sheet-width:26rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title">Send to a co-farmer</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body space-y-1" id="shareDmList">
        <p class="text-sm text-gray-400 text-center py-6">Loading your co-farmers…</p>
    </div>
</div>

<style>
    /* --- the action row under every post --- */
    .fp-acts { display:flex; align-items:center; gap:.35rem; margin-top:.6rem;
        padding-top:.55rem; border-top:1px solid var(--color-gray-100); }
    .fp-act { display:inline-flex; align-items:center; gap:.35rem; padding:.4rem .6rem;
        border-radius:.6rem; font-size:.78rem; font-weight:700; color:var(--color-gray-500);
        background:none; border:none; cursor:pointer;
        transition:background-color .28s cubic-bezier(.22,1,.36,1), color .28s cubic-bezier(.22,1,.36,1); }
    .fp-act:hover { background:var(--color-gray-100); color:var(--color-gray-700); }
    .fp-act svg { width:1.05rem; height:1.05rem; flex:none; }
    .fp-act .on { display:none; }
    .fp-act.is-on { color:var(--color-brand-700); }
    .fp-act.is-on .on { display:inline; }
    .fp-act.is-on .off { display:none; }
    .fp-act.is-on svg { fill:currentColor; stroke:currentColor; }
    .fp-n { font-variant-numeric:tabular-nums; }
    /* The label steps aside on a narrow phone; the icon and the number carry it. */
    @media (max-width:420px) { .fp-act .fp-lbl { display:none; } .fp-act { padding:.4rem .5rem; } }

    /* --- follow, as a tag rather than a button --- */
    .fp-follow { flex:none; align-self:flex-start; padding:.25rem .6rem; border-radius:999px;
        font-size:.72rem; font-weight:800; cursor:pointer;
        color:var(--color-brand-700); background:var(--color-brand-50);
        border:1px solid var(--color-brand-100);
        transition:background-color .28s cubic-bezier(.22,1,.36,1), color .28s cubic-bezier(.22,1,.36,1); }
    .fp-follow:hover { background:var(--color-brand-100); }
    .fp-follow .on { display:none; }
    .fp-follow.is-on { color:var(--color-gray-600); background:var(--color-gray-100); border-color:var(--color-gray-200); }
    .fp-follow.is-on .on { display:inline; }
    .fp-follow.is-on .off { display:none; }

    /* --- a post carried inside a post --- */
    .fp-shared { display:block; margin-top:.7rem; padding:.65rem .75rem; border-radius:.8rem;
        border:1px solid var(--color-gray-200); background:var(--color-gray-50);
        transition:border-color .28s cubic-bezier(.22,1,.36,1); }
    .fp-shared:hover { border-color:var(--color-brand-200); }
    .fp-shared-head { display:flex; align-items:center; gap:.4rem; font-size:.75rem; color:var(--color-gray-500); }
    .fp-shared-head img, .fp-shared-head i { width:1.4rem; height:1.4rem; border-radius:999px; object-fit:cover;
        display:inline-flex; align-items:center; justify-content:center; font-style:normal; font-size:.62rem;
        font-weight:800; background:var(--color-brand-50); color:var(--color-brand-700); flex:none; }
    .fp-shared-head b { color:var(--color-gray-800); font-weight:700; }
    .fp-shared-head em { font-style:normal; color:var(--color-gray-400); }
    .fp-shared-body { display:block; margin-top:.4rem; font-size:.85rem; color:var(--color-gray-700); line-height:1.55; }
    .fp-shared-img { display:block; width:100%; border-radius:.5rem; margin-top:.5rem; }

    .wcs-state { padding:1.25rem .5rem; text-align:center; font-size:.85rem; font-weight:600; color:var(--color-gray-400); }
    .wcs-state[hidden] { display:none; }

    html.dark .fp-shared { background:rgb(255 255 255 / .04); border-color:rgb(255 255 255 / .09); }
    html.dark .fp-acts { border-top-color:rgb(255 255 255 / .08); }

    @media (prefers-reduced-motion: reduce) {
        .fp-act, .fp-follow, .fp-shared { transition:none; }
    }
</style>

<script>
(function postActions() {
    if (window.__wallActionsBooted) return;
    window.__wallActionsBooted = true;

    const URLS = {
        follow: (id) => @json(url('/app/community/follow')) + '/' + id,
        bookmark: (id) => @json(url('/app/community/bookmark')) + '/' + id,
        comments: (id) => @json(url('/app/community/wall')) + '/' + id + '/comments',
        shareWall: (id) => @json(url('/app/community/share')) + '/' + id + '/wall',
        shareDm: (id) => @json(url('/app/community/share')) + '/' + id + '/message',
        shareLink: (id) => @json(url('/app/community/share')) + '/' + id + '/link',
        cofarmers: @json(route('community.cofarmers.list')),
    };
    const $ = (id) => document.getElementById(id);
    const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content || '';
    const post = (url, body) => fetch(url, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': csrf(),
            Accept: 'application/json',
            ...(body ? { 'Content-Type': 'application/json' } : {}),
        },
        credentials: 'same-origin',
        body: body ? JSON.stringify(body) : undefined,
    }).then(async (r) => {
        const j = await r.json().catch(() => ({}));
        if (!r.ok || j.success === false) throw new Error(j.message || 'Something went wrong.');
        return j;
    });

    /* ---------------- follow ---------------- */
    document.addEventListener('click', async (e) => {
        const btn = e.target.closest('[data-follow]');
        if (!btn || btn.dataset.busy) return;
        const on = btn.classList.contains('is-on');
        // Unfollowing is the destructive half, so only that half asks.
        if (on) {
            const ok = window.confirmAction
                ? await window.confirmAction({
                    title: 'Unfollow ' + (btn.dataset.name || 'this member') + '?',
                    body: 'Their posts will stop being lifted to the top of your wall.',
                    confirmText: 'Unfollow',
                })
                : true;
            if (!ok) return;
        }
        btn.dataset.busy = '1';
        try {
            const res = await post(URLS.follow(btn.dataset.follow));
            const now = !!res.data.following;
            // Every card by this author agrees at once — the same person can
            // be on screen three times.
            document.querySelectorAll('[data-follow="' + btn.dataset.follow + '"]').forEach((b) => {
                b.classList.toggle('is-on', now);
                b.setAttribute('aria-pressed', now ? 'true' : 'false');
            });
            window.toast?.(res.message);
        } catch (err) { window.toast?.(err.message, 'error'); }
        finally { delete btn.dataset.busy; }
    });

    /* ---------------- bookmark ---------------- */
    document.addEventListener('click', async (e) => {
        const btn = e.target.closest('.js-bookmark');
        if (!btn || btn.dataset.busy) return;
        btn.dataset.busy = '1';
        try {
            const res = await post(URLS.bookmark(btn.dataset.postId));
            btn.classList.toggle('is-on', !!res.data.saved);
            btn.setAttribute('aria-pressed', res.data.saved ? 'true' : 'false');
            window.toast?.(res.message);
        } catch (err) { window.toast?.(err.message, 'error'); }
        finally { delete btn.dataset.busy; }
    });

    /* ---------------- comments, in a sheet ---------------- */
    let cPost = null, cPage = 1, cBusy = false;

    async function loadComments(reset) {
        if (cBusy || !cPost) return;
        cBusy = true;
        const list = $('wcsList'), state = $('wcsState'), more = $('wcsMore');
        if (reset) { list.innerHTML = ''; cPage = 1; }
        state.hidden = false;
        state.textContent = 'Loading…';
        more.classList.add('hidden');
        try {
            const r = await fetch(URLS.comments(cPost) + '?page=' + cPage, {
                headers: { Accept: 'application/json' }, credentials: 'same-origin',
            });
            const j = await r.json();
            const d = j.data || {};
            list.insertAdjacentHTML('beforeend', d.html || '');
            state.hidden = list.children.length > 0;
            if (!list.children.length) state.textContent = 'No comments yet — be the first.';
            cPage = d.nextPage || cPage + 1;
            more.classList.toggle('hidden', !d.hasMore);
            // The card's own number should agree with what the sheet counted.
            const n = document.querySelector('[data-comment-count="' + cPost + '"]');
            if (n && typeof d.total === 'number') n.textContent = d.total;
        } catch (err) {
            state.hidden = false;
            state.textContent = 'Could not load the comments.';
        } finally { cBusy = false; }
    }

    document.addEventListener('click', (e) => {
        const btn = e.target.closest('.js-open-comments');
        if (!btn) return;
        cPost = btn.dataset.postId;
        // The composer belongs to the post being read, so it is rebuilt each
        // time rather than pointed somewhere new.
        const form = document.querySelector('#wallpost-' + cPost + ' .wall-comment-form');
        const foot = $('wcsFoot');
        foot.innerHTML = '';
        if (form) foot.appendChild(form.cloneNode(true));
        window.openSheet?.('wallCommentSheet');
        loadComments(true);
    });
    $('wcsMore')?.addEventListener('click', () => loadComments(false));

    /* ---------------- share ---------------- */
    let sPost = null;
    document.addEventListener('click', (e) => {
        const btn = e.target.closest('.js-share');
        if (!btn) return;
        sPost = btn.dataset.postId;
        window.openSheet?.('wallShareSheet');
    });

    $('shareToWall')?.addEventListener('click', () => {
        window.closeSheet?.('wallShareSheet');
        $('shareWallBody').value = '';
        window.openSheet?.('shareWallSheet');
    });
    $('shareWallGo')?.addEventListener('click', async (e) => {
        const btn = e.currentTarget;
        const was = btn.textContent;
        btn.disabled = true; btn.textContent = 'Sharing…';
        try {
            const res = await post(URLS.shareWall(sPost), { body: $('shareWallBody').value.trim() });
            window.closeSheet?.('shareWallSheet');
            window.toast?.(res.message);
        } catch (err) { window.toast?.(err.message, 'error'); }
        finally { btn.disabled = false; btn.textContent = was; }
    });

    $('shareToMessage')?.addEventListener('click', async () => {
        window.closeSheet?.('wallShareSheet');
        const list = $('shareDmList');
        list.innerHTML = '<p class="text-sm text-gray-400 text-center py-6">Loading your co-farmers…</p>';
        window.openSheet?.('shareDmSheet');
        try {
            const r = await fetch(URLS.cofarmers, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
            const j = await r.json();
            const people = (j.data && j.data.items) || [];
            if (!people.length) {
                list.innerHTML = '<p class="text-sm text-gray-400 text-center py-6">You have no co-farmers yet.</p>';
                return;
            }
            list.innerHTML = people.map((p) => `<button type="button" class="ai-attach-opt js-dm-to" data-user="${p.id}">`
                + `<span class="ic">${p.initials || '?'}</span><span>${p.name || 'Co-farmer'}</span></button>`).join('');
        } catch (err) {
            list.innerHTML = '<p class="text-sm text-gray-400 text-center py-6">Could not load your co-farmers.</p>';
        }
    });
    document.addEventListener('click', async (e) => {
        const row = e.target.closest('.js-dm-to');
        if (!row) return;
        try {
            const res = await post(URLS.shareDm(sPost), { userId: parseInt(row.dataset.user, 10) });
            window.closeSheet?.('shareDmSheet');
            window.toast?.(res.message);
        } catch (err) { window.toast?.(err.message, 'error'); }
    });

    $('shareExternal')?.addEventListener('click', async () => {
        try {
            const res = await post(URLS.shareLink(sPost));
            const url = res.data.url;
            window.closeSheet?.('wallShareSheet');
            // The phone's own share sheet knows every app installed on it;
            // a desktop gets the link on the clipboard instead.
            if (navigator.share) {
                await navigator.share({ url, title: 'A post on AniSystem' }).catch(() => {});
                return;
            }
            await navigator.clipboard?.writeText(url);
            window.toast?.('Public link copied.');
        } catch (err) { window.toast?.(err.message, 'error'); }
    });
})();
</script>
@endonce
