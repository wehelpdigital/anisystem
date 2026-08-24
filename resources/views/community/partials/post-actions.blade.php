@once
{{-- Everything a post card can open, defined once per page however many cards
     there are: the comment sheet, the share sheet, and the handlers behind
     Follow / Save / Share.

     Delegated from the document rather than bound per card, because cards
     arrive by fetch as the reader scrolls and listeners bound at load would
     never meet them. --}}
{{-- The sheet IS the post, as far as the comment machinery is concerned.
     data-comment-scope makes closest() find it where a card would be found on
     the wall, so replying, deleting and "show older" all work in here without
     a second implementation. --}}
<div class="sheet hidden" id="wallCommentSheet" style="--sheet-width:34rem"
     data-comment-scope data-post-id="">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title">Comments</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body" id="wcsBody">
        {{-- The post itself, moved in on open and put back on close — the
             same thing a discussion's thread modal does, so a reader can see
             what is being talked about. --}}
        <div id="wcsPost"></div>
        <p class="wcs-count" id="wcsCount" hidden></p>
        {{-- .wall-comments is the name the handlers append into. --}}
        <div id="wcsList" class="wall-comments space-y-1.5"></div>
        <p class="wcs-state" id="wcsState">Loading…</p>
        <button type="button" class="btn btn-white btn-sm w-full mt-2 hidden" id="wcsMore">Show older comments</button>
        {{-- The box to write in, at the end of what you are reading rather
             than pinned to the floor. Its post id is set when the sheet
             opens; replying sets its parent. --}}
        <div id="wcsFoot" class="wcs-write">
            {{-- One quiet line: this box sits at the end of a conversation
                 the reader is already in, and does not need a title. --}}
            <p class="reply-lead">
                <span class="avatar avatar-sm {{ \App\Support\CommunityAvatar::hue(auth()->user()->full_name ?? '?') }}">{{ auth()->user()->initials ?? '?' }}</span>
                <span><i>Add a comment — use @ to tag a co-farmer.</i></span>
            </p>
            @include('community.partials.wall-comment-form', ['postId' => ''])
        </div>
    </div>
</div>

<div class="sheet hidden" id="wallShareSheet" style="--sheet-width:26rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title">Share this post</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body">
        {{-- What is being shared, so nobody has to remember which post they
             tapped once the sheet has covered it. Filled in on open. --}}
        <div class="sh-preview" id="sharePreview" hidden>
            <span class="sh-preview-shot" id="sharePreviewShot" hidden><img src="" alt=""></span>
            <span class="sh-preview-txt">
                <b id="sharePreviewWho">A co-farmer</b>
                <i id="sharePreviewBody"></i>
            </span>
        </div>
        <div class="sh-opts">
            <button type="button" class="sh-opt" id="shareToWall">
                <span class="sh-ic is-wall">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h10M4 18h7"/><path stroke-linecap="round" stroke-linejoin="round" d="M17 14v6m3-3h-6"/></svg>
                </span>
                <span class="sh-txt">
                    <b>Share to my wall</b>
                    <i>Add your own words above it</i>
                </span>
                <svg class="sh-go" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
            </button>
            <button type="button" class="sh-opt" id="shareToMessage">
                <span class="sh-ic is-dm">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 12h8m-8-4h5m-6 12V6a2 2 0 012-2h12a2 2 0 012 2v8a2 2 0 01-2 2H8l-3 4z"/></svg>
                </span>
                <span class="sh-txt">
                    <b>Send to a co-farmer</b>
                    <i>As a private message</i>
                </span>
                <svg class="sh-go" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
            </button>
            <button type="button" class="sh-opt" id="shareExternal">
                <span class="sh-ic is-link">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.8 10.2a4 4 0 010 5.7l-3 3a4 4 0 01-5.7-5.7l1.5-1.5m7.2-2.4a4 4 0 015.7 0 4 4 0 010 5.7l-1.5 1.5"/></svg>
                </span>
                <span class="sh-txt">
                    <b>Get a public link</b>
                    <i>Messenger, Facebook, or anywhere</i>
                </span>
                <svg class="sh-go" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
            </button>
        </div>
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

    /* ---- Share this post ----
       Its own clothes: the rows used to borrow a class from the AI module's
       page, which is not loaded here, so they arrived as bare markup. */
    .sh-preview { display: flex; align-items: center; gap: .6rem; padding: .55rem .6rem; margin-bottom: .7rem;
        border-radius: .8rem; background: var(--color-gray-100); }
    .sh-preview[hidden] { display: none; }
    .sh-preview-shot { flex: none; width: 2.75rem; height: 2.75rem; border-radius: .55rem; overflow: hidden;
        background: var(--color-gray-200); }
    .sh-preview-shot[hidden] { display: none; }
    .sh-preview-shot img { width: 100%; height: 100%; object-fit: cover; display: block; }
    .sh-preview-txt { min-width: 0; display: flex; flex-direction: column; gap: .1rem; }
    .sh-preview-txt b { font-size: .78rem; font-weight: 800; color: var(--color-gray-900);
        overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .sh-preview-txt i { font-style: normal; font-size: .72rem; line-height: 1.35; color: var(--color-gray-500);
        display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
    .sh-opts { display: grid; gap: .35rem; }
    .sh-opt { display: flex; align-items: center; gap: .75rem; width: 100%; text-align: left;
        padding: .7rem .75rem; border-radius: .85rem; border: 1px solid transparent; cursor: pointer;
        background: var(--color-gray-50);
        transition: background .28s cubic-bezier(.22,1,.36,1), border-color .28s cubic-bezier(.22,1,.36,1),
            transform .28s cubic-bezier(.22,1,.36,1); }
    .sh-opt:hover { background: var(--color-white); border-color: var(--color-brand-200); transform: translateY(-1px); }
    .sh-ic { flex: none; width: 2.5rem; height: 2.5rem; border-radius: .8rem; display: flex;
        align-items: center; justify-content: center; }
    .sh-ic svg { width: 1.2rem; height: 1.2rem; }
    /* One colour per destination, so the three read apart at a glance. */
    .sh-ic.is-wall { background: var(--color-brand-50); color: var(--color-brand-700); }
    .sh-ic.is-dm { background: #eaf1fd; color: #1d4ed8; }
    .sh-ic.is-link { background: #fdf6e6; color: #b45309; }
    .sh-txt { display: flex; flex-direction: column; gap: .1rem; min-width: 0; flex: 1 1 auto; }
    .sh-txt b { font-size: .86rem; font-weight: 800; color: var(--color-gray-900); line-height: 1.25; }
    .sh-txt i { font-style: normal; font-size: .74rem; line-height: 1.35; color: var(--color-gray-500); }
    .sh-go { flex: none; width: .9rem; height: .9rem; color: var(--color-gray-300); }
    .sh-opt:hover .sh-go { color: var(--color-brand-600); }
    html.dark .sh-ic.is-dm { background: rgb(29 78 216 / .22); color: #9fc0f5; }
    html.dark .sh-ic.is-link { background: rgb(180 83 9 / .18); color: #e0b457; }
    @media (prefers-reduced-motion: reduce) {
        .sh-opt { transition: none; }
        .sh-opt:hover { transform: none; }
    }

    /* --- the action row under every post --- */
    /* Views are told, not pressed: no hover, no pointer, no hand. */
    .fp-views { cursor:default; }
    .fp-views:hover { background:transparent; color:var(--color-gray-500); }
    /* The time closes the row, pushed to the far edge. */
    .fp-when { margin-left:auto; flex:none; font-size:.7rem; font-weight:600; color:var(--color-gray-400);
        white-space:nowrap; padding-left:.4rem; }
    .fp-acts { display:flex; align-items:center; gap:.35rem; margin-top:.6rem;
        padding-top:.55rem; border-top:1px solid var(--color-gray-100); }
    /* A post ends on this row, and every button in it carries .4rem of its own
       padding under the words. With a uniform 1rem all round, that padding
       stacked on the card's and the bottom of every post read as a deeper
       margin than the top. The card gives the .4rem back, so the air above
       the first line and under the last one is the same air. */
    .feed-post:has(.fp-acts), .wall-post:has(.fp-acts) { padding-bottom:.6rem; }
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
    /* The whole picture. object-fit:cover on a fixed box turned a portrait
       photograph into a letterbox of its middle. */
    .fp-shared-img { display:block; width:100%; max-height:26rem; object-fit:contain;
        border-radius:.5rem; margin-top:.5rem; background:var(--color-gray-100); cursor:zoom-in; }
    .fp-shared-clip { display:block; margin-top:.5rem; font-size:.78rem; font-weight:700;
        color:var(--color-gray-500); }
    .fp-shared-head b a { color:inherit; text-decoration:none; }
    .fp-shared-head b a:hover { color:var(--color-brand-700); }
    .fp-shared-open { display:inline-flex; align-items:center; gap:.3rem; margin-top:.6rem;
        font-size:.75rem; font-weight:800; color:var(--color-brand-700); text-decoration:none; }
    .fp-shared-open::after { content:'\2192'; }
    .fp-shared-open:hover { color:var(--color-brand-800); text-decoration:underline; }

    .wcs-state { padding:1.25rem .5rem; text-align:center; font-size:.85rem; font-weight:600; color:var(--color-gray-400); }
    .wcs-state[hidden] { display:none; }

    /* The sheet reads like a discussion's thread: the post at the top with
       its own chrome stripped, the answers under it, the box at the end. */
    #wcsPost .wall-post { border:0; box-shadow:none; margin:0; padding:1.2rem 0 0;
        background:transparent; border-radius:0; }
    #wcsPost .wall-post::before { display:none; }        /* the card's edge belongs to the wall */
    #wcsPost .fp-acts { display:none; }                  /* you are already in the comments */
    #wcsPost .fp-follow { display:none; }
    .wcs-count { margin:.9rem 0 .5rem; font-size:.72rem; font-weight:800; letter-spacing:.04em;
        text-transform:uppercase; color:var(--color-gray-400); }
    .wcs-count[hidden] { display:none; }
    .wcs-write { margin-top:1rem; padding-top:.85rem; border-top:1px solid var(--color-gray-100); }
    html.dark .wcs-write { border-top-color:rgb(255 255 255 / .08); }

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
                    message: 'Their posts will stop being lifted to the top of your wall.',
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

    /* ---------------- join a discussion, from its wall card ---------------- */
    document.addEventListener('click', async (e) => {
        const btn = e.target.closest('.js-join-group');
        if (!btn || btn.dataset.busy) return;
        // Already in it: the tag is a link to the room, not a leave button —
        // leaving belongs in the room, where you can see what you are leaving.
        if (btn.classList.contains('is-on')) {
            window.location.href = @json(url('/app/community/groups')) + '/' + btn.dataset.groupId;
            return;
        }
        /* Asked first: joining puts your name in a room other people can
           see, which is not the sort of thing a thumb should do by brushing
           past a card on a wall. */
        const name = btn.dataset.name || 'this discussion';
        const ok = await (window.confirmAction ? window.confirmAction({
            title: 'Join ' + name + '?',
            message: 'You will see its topics on your wall, and the others there will see you as a member.',
            confirmText: 'Join',
            confirmClass: 'btn-primary',
        }) : Promise.resolve(true));
        if (!ok) return;
        btn.dataset.busy = '1';
        try {
            await post(@json(url('/app/community/groups')) + '/' + btn.dataset.groupId + '/join');
            btn.classList.add('is-on');
            btn.setAttribute('aria-pressed', 'true');
            /* The way in changes with the answer.
             *
             * The big button IS the join button for a stranger, so once they
             * are in it becomes the invitation to walk inside — and the next
             * tap opens the room (the is-on branch above). */
            const card = btn.closest('[data-discussion-card]');
            card?.querySelectorAll('.fd-open').forEach((el) => {
                el.textContent = 'Take a look inside';
                el.classList.add('is-on');   // stays green: it is still the way in
            });
            window.toast?.('Sali ka na sa ' + (btn.dataset.name || 'usapan') + '.');
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
            // "3 sagot", the way the room labels its answers.
            const label = $('wcsCount');
            if (label && typeof d.total === 'number') {
                label.hidden = d.total < 1;
                label.textContent = d.total + ' ' + (d.total === 1 ? 'comment' : 'comments');
            }
            // The card's own number should agree with what the sheet counted.
            const n = document.querySelector('[data-comment-count="' + cPost + '"]');
            if (n && typeof d.total === 'number') n.textContent = d.total;
        } catch (err) {
            state.hidden = false;
            state.textContent = 'Could not load the comments.';
        } finally { cBusy = false; }
    }


    /* The post is MOVED into the sheet, not copied — every handler on this
     * page is delegated off document and reaches for .closest('.wall-post'),
     * so the same nodes in a new place keep working with nothing rewritten.
     * A marker holds its place in the wall, and closing puts it back exactly
     * where it was. (The discussion room does this with its topics.) */
    let cCard = null, cSlot = null;

    function takePost(card) {
        if (!card) return;
        putPostBack();
        cSlot = document.createComment('wall-post-slot');
        card.parentNode.insertBefore(cSlot, card);
        cCard = card;
        $('wcsPost').appendChild(card);
        // The name link, not the avatar's — the avatar is an <a> too, and
        // its text is an initial or nothing at all.
        const who = card.querySelector('.fp-head-txt a')?.textContent?.trim();
        const title = document.querySelector('#wallCommentSheet .sheet-title');
        if (title) title.textContent = who ? who + '’s post' : 'Comments';
    }

    function putPostBack() {
        if (!cCard) return;
        const card = cCard, slot = cSlot;
        cCard = null; cSlot = null;
        if (slot && slot.parentNode) { slot.parentNode.insertBefore(card, slot); slot.remove(); }
        else document.getElementById('plazaFeed')?.appendChild(card);
    }

    /* Closing the sheet hands the post back to the wall it was lifted from
     * — unless it is being closed to make room for something the sheet
     * itself asked for. The gallery picker steps in front of it and puts it
     * back afterwards, and the post has to still be in it when it returns. */
    document.addEventListener('sm:sheet-closed', (e) => {
        if (e.detail && e.detail.id === 'wallCommentSheet' && ! window.plazaSheetHold) putPostBack();
    });

    document.addEventListener('click', (e) => {
        const btn = e.target.closest('.js-open-comments');
        if (!btn) return;
        cPost = btn.dataset.postId;
        primeComposer();
        takePost(btn.closest('.wall-post'));
        window.openSheet?.('wallCommentSheet');
        loadComments(true);
        /* Opened to be read, not written in.
         *
         * A field that takes focus as the sheet arrives raises the phone's
         * keyboard over half the comments before anybody has read one. The
         * shared opener blurs once; this repeats it after the sheet has
         * settled, because the list paints in between. */
        [60, 260, 520].forEach((ms) => setTimeout(() => {
            const sheet = $('wallCommentSheet');
            const active = document.activeElement;
            if (sheet && active && sheet.contains(active) && active.tagName === 'INPUT') active.blur();
        }, ms));
    });

    /* Point the sheet's own composer at the post being read, and empty it.
     *
     * It used to clone the card's composer, which the wall's card partial does
     * not have — so the sheet opened with no way to write. */
    function primeComposer() {
        const sheet = $('wallCommentSheet');
        sheet.setAttribute('data-post-id', cPost);
        const form = sheet.querySelector('.wall-comment-form');
        if (form) {
            form.setAttribute('data-post-id', cPost);
            form.removeAttribute('data-parent-id');
            delete form.dataset.mentionId;
            delete form.dataset.mentionName;
            form.querySelectorAll('.reply-mention-pill').forEach((x) => x.remove());
            const t = form.querySelector('input[type="text"]');
            if (t) { t.value = ''; t.disabled = false; }
            form.querySelectorAll('input[type="file"]').forEach((f) => { f.value = ''; });
            // The tray of attached pictures belongs to the post being read.
            window.plazaClearShots?.(form);
            window.plazaSetChip?.(form, null);
            window.plazaClearVideo?.(form);
        }
    }
    $('wcsMore')?.addEventListener('click', () => loadComments(false));

    /* ---------------- share ---------------- */
    let sPost = null;
    /* Show what is about to be shared.
     *
     * The sheet covers the post it came from, and "Share this post" with no
     * post in sight is a question about something you can no longer see. */
    function fillSharePreview(card) {
        const box = $('sharePreview');
        if (!box) return;
        if (!card) { box.hidden = true; return; }
        const who = card.querySelector('.fp-name, .font-semibold');
        const body = card.querySelector('.fp-body, .feed-post-body, .wall-post-body');
        const shot = card.querySelector('.post-media img, .fp-media img, img.post-photo');
        $('sharePreviewWho').textContent = (who?.textContent || 'A co-farmer').trim().slice(0, 60);
        $('sharePreviewBody').textContent = (body?.textContent || '').trim().slice(0, 160);
        const shotBox = $('sharePreviewShot');
        if (shot && shot.getAttribute('src')) {
            shotBox.querySelector('img').src = shot.getAttribute('src');
            shotBox.hidden = false;
        } else shotBox.hidden = true;
        box.hidden = false;
    }
    document.addEventListener('click', (e) => {
        const btn = e.target.closest('.js-share');
        if (!btn) return;
        sPost = btn.dataset.postId;
        fillSharePreview(btn.closest('.wall-post, .feed-post, .fp-card, article'));
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
            /* Which card to ask for: the community feed draws one shape, a
               member's own wall another. Whichever wrapper is on the page is
               the one the share is about to join. */
            const feedWrap = document.getElementById('feedWrap');
            const wallWrap = document.getElementById('wallPosts') || document.getElementById('profileWall');
            const res = await post(URLS.shareWall(sPost), {
                body: $('shareWallBody').value.trim(),
                render: feedWrap ? 'feed' : 'wall',
            });
            window.closeSheet?.('shareWallSheet');
            window.toast?.(res.message);
            /* Put it up straight away. A share that only appears after a
               reload reads as one that did not happen — and what a person
               wants to see is their words with the original quoted under
               them, which is the whole reason they shared it. */
            const wrap = feedWrap || wallWrap;
            if (wrap && res.data?.html) {
                wrap.querySelector('.card.p-8.text-center')?.remove();
                wrap.insertAdjacentHTML('afterbegin', res.data.html);
                const added = wrap.firstElementChild;
                if (added) {
                    added.classList.add('plaza-comment-enter');
                    added.addEventListener('animationend', () => added.classList.remove('plaza-comment-enter'), { once: true });
                    added.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                }
            }
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
