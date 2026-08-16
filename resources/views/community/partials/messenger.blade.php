{{-- Messenger-style dock: a launcher (bottom-right) that opens a conversation
     list and sticky chat windows. Global — include once in the app layout for
     logged-in users. Exposes window.plazaOpenDm(userId, name). --}}
<div id="msgrDock" aria-live="polite">
    <div class="msgr-panel hidden" id="msgrPanel">
        <div class="msgr-panel-head">
            <span class="font-bold text-gray-900">Messages</span>
            <button type="button" class="msgr-x" data-msgr-close-panel aria-label="Close">✕</button>
        </div>
        <div class="msgr-panel-body" id="msgrThreads">
            <p class="text-sm text-gray-400 text-center py-6">Loading…</p>
        </div>
    </div>
    <div class="msgr-windows" id="msgrWindows"></div>
    <button type="button" class="msgr-launcher" id="msgrLauncher" aria-label="Messages">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.9 9.9 0 01-4.29-.94L3 20l1.05-3.15A7.6 7.6 0 013 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
        <span class="msgr-badge hidden" id="msgrBadge">0</span>
    </button>
</div>

{{-- Forward-a-message picker (shared by the dock's per-bubble Forward action) --}}
<div class="sheet hidden" id="msgrForwardSheet" style="--sheet-width:28rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title">Forward message</h3>
        <button data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body">
        <p class="text-sm text-gray-500 mb-2" id="msgrForwardPreview"></p>
        <label class="form-label">Send to a co-farmer</label>
        <div id="msgrForwardList" class="space-y-1 max-h-72 overflow-y-auto rounded-xl border border-gray-100 p-1">
            <p class="text-sm text-gray-400 px-2 py-3 text-center">Loading…</p>
        </div>
    </div>
</div>

<style>
    #msgrDock { position:fixed; right:1rem; bottom:1rem; z-index:90; display:flex; align-items:flex-end; gap:.75rem; }
    /* Clear the mobile bottom tab bar (visible < md) so the launcher doesn't sit on the Account button. */
    @media (max-width:767px) { #msgrDock { bottom:calc(4.5rem + env(safe-area-inset-bottom, 0px)); } }
    .msgr-launcher { position:relative; width:3.25rem; height:3.25rem; border-radius:9999px; border:0;
        background:var(--color-brand-600); color:#fff; box-shadow:0 8px 24px rgb(74 124 42 / .4); cursor:pointer;
        display:flex; align-items:center; justify-content:center; transition:transform .15s ease, background .2s ease; }
    .msgr-launcher:hover { background:var(--color-brand-700); }
    .msgr-launcher:active { transform:scale(.94); }
    .msgr-badge { position:absolute; top:-2px; right:-2px; min-width:1.15rem; height:1.15rem; padding:0 .3rem;
        border-radius:9999px; background:#ef4444; color:#fff; font-size:.65rem; font-weight:800;
        display:inline-flex; align-items:center; justify-content:center; }
    .msgr-badge.hidden { display:none; }
    .msgr-panel { width:19rem; max-width:calc(100vw - 2rem); max-height:26rem; background:#fff; border-radius:1rem;
        box-shadow:0 16px 48px rgb(0 0 0 / .22); overflow:hidden; display:flex; flex-direction:column;
        animation:msgrIn .2s var(--ease-house, cubic-bezier(.22,1,.36,1)); }
    .msgr-panel.hidden { display:none; }
    @keyframes msgrIn { from { opacity:0; transform:translateY(12px); } to { opacity:1; transform:none; } }
    .msgr-panel-head { display:flex; align-items:center; justify-content:space-between; padding:.75rem 1rem; border-bottom:1px solid #f1f3f5; }
    .msgr-panel-body { overflow-y:auto; }
    .msgr-thread { display:flex; align-items:center; gap:.6rem; padding:.6rem 1rem; cursor:pointer; border-bottom:1px solid #f6f7f8; }
    .msgr-thread:hover { background:var(--color-brand-50); }
    .msgr-thread .avatar { width:2.25rem; height:2.25rem; font-size:.7rem; }
    .msgr-thread-name { font-weight:600; font-size:.85rem; color:#1f2937; }
    .msgr-thread-last { font-size:.75rem; color:#6b7280; }
    .msgr-thread-unread { margin-left:auto; min-width:1.1rem; height:1.1rem; padding:0 .3rem; border-radius:9999px;
        background:var(--color-brand-600); color:#fff; font-size:.62rem; font-weight:800; display:inline-flex; align-items:center; justify-content:center; }
    .msgr-windows { display:flex; align-items:flex-end; gap:.75rem; }
    .msgr-window { width:24rem; max-width:calc(100vw - 2rem); height:33rem; background:#fff; border-radius:1rem 1rem 0 0;
        box-shadow:0 16px 48px rgb(0 0 0 / .22); display:flex; flex-direction:column; overflow:hidden;
        animation:msgrIn .2s var(--ease-house, cubic-bezier(.22,1,.36,1)); }
    .msgr-window-head { display:flex; align-items:center; gap:.5rem; padding:.5rem .75rem; background:var(--color-brand-600); color:#fff; }
    .msgr-window-head .avatar { width:2.1rem; height:2.1rem; font-size:.7rem; box-shadow:none; }
    .msgr-window-head a { color:#fff; font-weight:700; font-size:.98rem; text-decoration:none; }
    .msgr-window-body { flex:1; overflow-y:auto; padding:.75rem; display:flex; flex-direction:column; gap:.35rem; background:#fafbfc; }
    .msgr-bubble { max-width:82%; padding:.5rem .8rem; border-radius:1rem; font-size:.95rem; line-height:1.4; word-wrap:break-word; }
    .msgr-bubble.them { align-self:flex-start; background:#eceff2; color:#1f2937; border-bottom-left-radius:.3rem; }
    .msgr-bubble.me { align-self:flex-end; background:var(--color-brand-600); color:#fff; border-bottom-right-radius:.3rem; }
    .msgr-window-foot { padding:.5rem; border-top:1px solid #f1f3f5; display:flex; gap:.4rem; }
    .msgr-window-foot input { flex:1 1 0; min-width:0; border:1px solid #e5e7eb; border-radius:9999px; padding:.5rem .9rem; font-size:.92rem; outline:none; }
    .msgr-window-foot input:focus { border-color:var(--color-brand-400); }
    .msgr-send { border:0; background:var(--color-brand-600); color:#fff; border-radius:9999px; width:2.25rem; height:2.25rem; cursor:pointer; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
    .msgr-window-foot-wrap { border-top:1px solid #f1f3f5; }
    html.dark .msgr-window-foot-wrap { border-color:#2b3a1c; }
    .msgr-window-foot { border-top:0; }
    .msgr-icon { border:0; background:transparent; color:#6b7280; cursor:pointer; width:2.1rem; height:2.1rem; border-radius:9999px; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
    .msgr-icon:hover { background:#f1f3f5; color:#374151; }
    html.dark .msgr-icon { color:#9fb389; }
    html.dark .msgr-icon:hover { background:rgb(255 255 255 / .07); color:#e5e9df; }
    .msgr-attach { display:flex; align-items:center; gap:.4rem; padding:.5rem .5rem 0; }
    .msgr-attach.hidden { display:none; }
    .msgr-attach-thumb { width:2.75rem; height:2.75rem; object-fit:cover; border-radius:.5rem; }
    .msgr-attach-x { border:0; background:transparent; color:#9ca3af; cursor:pointer; font-size:.9rem; }
    .msgr-bubble img { max-width:100%; border-radius:.65rem; margin-top:.15rem; display:block; cursor:pointer; }
    /* Quoted reply inside a bubble (FB Messenger style). */
    .msgr-quote { font-size:.78rem; opacity:.8; padding:.25rem .5rem; margin:-.1rem -.2rem .3rem; border-left:2px solid currentColor;
        border-radius:.35rem; background:rgb(0 0 0 / .06); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:100%; }
    .msgr-bubble.me .msgr-quote { background:rgb(255 255 255 / .18); }
    /* Per-bubble hover actions (reply / forward). */
    .msgr-row { display:flex; align-items:center; gap:.25rem; max-width:100%; }
    .msgr-row.me { align-self:flex-end; flex-direction:row-reverse; }
    .msgr-row.them { align-self:flex-start; }
    .msgr-row .msgr-bubble { max-width:100%; }
    .msgr-acts { display:flex; gap:.2rem; opacity:0; transition:opacity .15s ease; flex-shrink:0; }
    .msgr-row:hover .msgr-acts, .msgr-row:focus-within .msgr-acts { opacity:1; }
    .msgr-act { border:0; background:#eef1f4; color:#6b7280; cursor:pointer; width:1.6rem; height:1.6rem; border-radius:9999px;
        display:inline-flex; align-items:center; justify-content:center; padding:0; transition:background .15s ease, color .15s ease, transform .15s ease; }
    .msgr-act svg { width:.85rem; height:.85rem; }
    .msgr-act:hover { background:var(--color-brand-100, #dcfce7); color:var(--color-brand-700, #15803d); transform:translateY(-1px); }
    .msgr-act:active { transform:none; }
    html.dark .msgr-act { background:rgb(255 255 255 / .07); color:#9aa69a; }
    html.dark .msgr-act:hover { background:rgb(255 255 255 / .14); color:#e5e9df; }
    @media (prefers-reduced-motion: reduce) { .msgr-act:hover { transform:none; } }
    /* Reply preview above the composer. */
    .msgr-reply-bar { display:none; align-items:center; gap:.5rem; padding:.4rem .6rem; background:#f1f3f5; border-top:1px solid #e5e7eb; font-size:.8rem; }
    .msgr-reply-bar.show { display:flex; }
    .msgr-reply-bar .rb-body { min-width:0; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; color:#4b5563; }
    .msgr-reply-bar .rb-x { margin-left:auto; border:0; background:transparent; color:#9ca3af; cursor:pointer; flex-shrink:0; }
    html.dark .msgr-reply-bar { background:#1a2213; border-color:#2b3a1c; }
    html.dark .msgr-reply-bar .rb-body { color:#c3cdb5; }
    html.dark .msgr-quote { background:rgb(255 255 255 / .09); }
    /* New message pops in (house easing); sent bubbles grow from the composer side. */
    @keyframes msgrBubbleIn { from { opacity:0; transform:translateY(9px) scale(.9); } to { opacity:1; transform:none; } }
    .msgr-bubble-in { animation:msgrBubbleIn .28s cubic-bezier(.22,1,.36,1) both; }
    .msgr-bubble.me.msgr-bubble-in { transform-origin:bottom right; }
    .msgr-bubble.them.msgr-bubble-in { transform-origin:bottom left; }
    @keyframes msgrSendPop { 0%{transform:scale(1);} 45%{transform:scale(.8);} 100%{transform:scale(1);} }
    .msgr-send.is-sending { animation:msgrSendPop .3s cubic-bezier(.22,1,.36,1); }
    /* Optimistic bubble: dimmed while its photo/message uploads. */
    .msgr-bubble.is-pending { opacity:.82; }
    .msgr-uploading { display:flex; align-items:center; gap:.4rem; margin-top:.3rem; font-size:.72rem; font-weight:600; opacity:.9; }
    .msgr-bubble.me .msgr-uploading { color:rgb(255 255 255 / .92); }
    .msgr-spin { width:.82rem; height:.82rem; border-radius:9999px; border:2px solid currentColor; border-top-color:transparent; display:inline-block; animation:msgrSpin .6s linear infinite; }
    @keyframes msgrSpin { to { transform:rotate(360deg); } }
    @media (prefers-reduced-motion: reduce) { .msgr-bubble-in, .msgr-send.is-sending { animation:none; } .msgr-spin { animation-duration:1.4s; } }
    .msgr-off { padding:1rem; text-align:center; font-size:.8rem; color:#9ca3af; }
    .msgr-x { border:0; background:transparent; color:#9ca3af; cursor:pointer; font-size:.9rem; }
    .msgr-window-head .msgr-x { color:#fff; opacity:.9; }
    html.dark .msgr-panel, html.dark .msgr-window { background:#151b12; }
    html.dark .msgr-window-body { background:#10160c; }
    html.dark .msgr-thread-name { color:#e5e9df; }
    html.dark .msgr-bubble.them { background:#232a1c; color:#dbe6cf; }
    html.dark .msgr-window-foot input { background:#10160c; border-color:#2b3a1c; color:#e5e9df; }
    /* On a phone one conversation owns the bottom of the screen.
       The row that holds the windows must NOT be display:none here: the
       windows are appended INTO it, and a display:none parent takes its
       whole subtree out of the box tree — a position:fixed child of a hidden
       parent renders nothing at all. That is the entire "messaging does not
       work on my phone": the window was being built and then never drawn.
       `display:contents` gets the row out of the way without taking its
       children with it. */
    @media (max-width:480px) {
        .msgr-windows { display:contents; }
        .msgr-window { position:fixed; inset:auto 0 0 0; width:100%; height:70vh;
            border-radius:1rem 1rem 0 0; z-index:96; }
        /* Two open at once would stack on the same spot; the newest wins. */
        .msgr-window:not(:last-child) { display:none; }
    }
</style>

<script>
(function () {
    if (window.__plazaMessengerBound) return;
    window.__plazaMessengerBound = true;

    const CSRF = () => document.querySelector('meta[name=csrf-token]')?.content || '';
    const dock = document.getElementById('msgrDock');
    const panel = document.getElementById('msgrPanel');
    const launcher = document.getElementById('msgrLauncher');
    const badge = document.getElementById('msgrBadge');
    const windowsWrap = document.getElementById('msgrWindows');
    const openWins = {};   // userId -> element
    const esc = (s) => { const d = document.createElement('div'); d.textContent = s == null ? '' : s; return d.innerHTML; };

    function setBadge(n) {
        if (n > 0) { badge.textContent = n > 99 ? '99+' : n; badge.classList.remove('hidden'); }
        else badge.classList.add('hidden');
    }
    async function refreshBadge() {
        try {
            const r = await fetch(@json(route('community.messages.unread')), { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
            const d = await r.json(); setBadge(d.data.unread);
        } catch (_) {}
    }

    // Live delivery: pull messages newer than what we've seen and surface them.
    let lastSeenId = 0;
    let synced = false;
    async function livePoll() {
        try {
            const r = await fetch(@json(route('community.messages.poll')) + '?after=' + lastSeenId, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
            const d = await r.json();
            setBadge(d.data.unread);
            if (!synced) { lastSeenId = d.data.maxId || 0; synced = true; return; }  // first sync: adopt maxId, don't replay history
            for (const m of (d.data.incoming || [])) {
                if (m.id > lastSeenId) lastSeenId = m.id;
                if (openWins[m.senderId]) {
                    // Window already open → append the new bubble live + mark read.
                    appendBubble(openWins[m.senderId].querySelector('.msgr-window-body'), { id: m.id, body: m.body, image: m.image, mine: false, replyTo: m.replyTo }, true);
                    fetch(`/app/community/messages/${m.senderId}`, { headers: { Accept: 'application/json' }, credentials: 'same-origin' }).then(refreshBadge).catch(() => {});
                } else {
                    // Otherwise pop a chat window open (FB-style), which loads the thread.
                    openWindow(m.senderId, m.senderName);
                }
            }
        } catch (_) {}
    }

    async function loadThreads() {
        const box = document.getElementById('msgrThreads');
        box.innerHTML = '<p class="text-sm text-gray-400 text-center py-6">Loading…</p>';
        try {
            const r = await fetch(@json(route('community.messages.threads')), { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
            const d = await r.json();
            setBadge(d.data.unread);
            const threads = d.data.threads || [];
            if (!threads.length) { box.innerHTML = '<p class="text-sm text-gray-400 text-center py-8">No messages yet.<br>Open a member\'s profile and tap Message.</p>'; return; }
            box.innerHTML = threads.map((t) => {
                const av = t.avatar ? `<span class="avatar overflow-hidden"><img src="${esc(t.avatar)}" class="w-full h-full object-cover" alt=""></span>`
                    : `<span class="avatar av-h${t.userId % 8}">${esc(t.initials || '?')}</span>`;
                return `<div class="msgr-thread" data-user="${t.userId}" data-name="${esc(t.name)}">
                    ${av}<span class="min-w-0"><span class="msgr-thread-name block truncate">${esc(t.name)}</span>
                    <span class="msgr-thread-last block truncate">${esc(t.lastBody)} · ${esc(t.lastAt)}</span></span>
                    ${t.unread ? `<span class="msgr-thread-unread">${t.unread}</span>` : ''}</div>`;
            }).join('');
        } catch (_) { box.innerHTML = '<p class="text-sm text-red-500 text-center py-6">Could not load.</p>'; }
    }

    function togglePanel(show) {
        const on = show ?? panel.classList.contains('hidden');
        panel.classList.toggle('hidden', !on);
        if (on) loadThreads();
    }
    launcher.addEventListener('click', () => togglePanel());
    dock.addEventListener('click', (e) => {
        if (e.target.closest('[data-msgr-close-panel]')) { togglePanel(false); return; }
        const t = e.target.closest('.msgr-thread');
        if (t) { openWindow(parseInt(t.dataset.user, 10), t.dataset.name); togglePanel(false); }
    });

    async function openWindow(userId, name) {
        if (openWins[userId]) { openWins[userId].querySelector('.msgr-window-foot input')?.focus(); return; }
        const win = document.createElement('div');
        win.className = 'msgr-window'; win.dataset.user = userId;
        win.innerHTML = `<div class="msgr-window-head">
                <span class="avatar av-h${userId % 8}">?</span>
                <a href="/app/community/members/${userId}" class="grow truncate">${esc(name || 'Member')}</a>
                <button type="button" class="msgr-x" data-close aria-label="Close">✕</button>
            </div>
            <div class="msgr-window-body"><p class="text-xs text-gray-400 text-center py-4">Loading…</p></div>
            <div class="msgr-window-foot-wrap emoji-scope">
                <div class="msgr-reply-bar">
                    <svg class="w-4 h-4 shrink-0" style="color:var(--color-brand-600)" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h10a5 5 0 015 5v2m-15-7l4-4m-4 4l4 4"/></svg>
                    <span class="rb-body"></span>
                    <button type="button" class="rb-x" aria-label="Cancel reply">✕</button>
                </div>
                <div class="msgr-attach hidden"><img class="msgr-attach-thumb" alt=""><button type="button" class="msgr-attach-x" aria-label="Remove photo">✕</button></div>
                <div class="msgr-window-foot">
                    <button type="button" class="msgr-icon js-msgr-photo" aria-label="Add a photo" title="Photo"><svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg></button>
                    <input type="file" class="js-msgr-file hidden" accept="image/*">
                    <button type="button" class="msgr-icon js-emoji-btn" aria-label="Add an emoji" title="Emoji"><svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></button>
                    <input type="text" placeholder="Aa" maxlength="5000">
                    <button type="button" class="msgr-send" aria-label="Send"><svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14m0 0l-6-6m6 6l-6 6"/></svg></button>
                </div>
            </div>`;
        windowsWrap.appendChild(win);
        openWins[userId] = win;

        win.querySelector('[data-close]').addEventListener('click', () => { win.remove(); delete openWins[userId]; });
        const input = win.querySelector('input[type="text"]');
        const bodyEl = win.querySelector('.msgr-window-body');
        const fileInput = win.querySelector('.js-msgr-file');
        const attach = win.querySelector('.msgr-attach');
        const attachThumb = win.querySelector('.msgr-attach-thumb');
        const clearAttach = () => {
            if (fileInput) fileInput.value = '';
            attach?.classList.add('hidden');
            if (attachThumb?.src) { try { URL.revokeObjectURL(attachThumb.src); } catch (e) {} attachThumb.removeAttribute('src'); }
        };
        win.querySelector('.js-msgr-photo').addEventListener('click', () => fileInput.click());
        fileInput.addEventListener('change', () => {
            const f = fileInput.files[0];
            if (f) { attachThumb.src = URL.createObjectURL(f); attach.classList.remove('hidden'); }
            else clearAttach();
        });
        win.querySelector('.msgr-attach-x').addEventListener('click', clearAttach);
        win.querySelector('.rb-x')?.addEventListener('click', () => clearReply(win));
        const sendBtn = win.querySelector('.msgr-send');
        const doSend = async () => {
            const text = input.value.trim();
            const file = fileInput.files[0];
            if (!text && !file) return;
            sendBtn.classList.remove('is-sending'); void sendBtn.offsetWidth; sendBtn.classList.add('is-sending');
            input.value = '';
            // Independent object URL for the optimistic bubble (clearAttach revokes
            // the composer's own preview URL, so we can't reuse it).
            const pendingImageUrl = file ? URL.createObjectURL(file) : null;
            const replyMeta = win._reply ? { body: win._reply.body || '📷 Photo', mine: false } : null;
            const fd = new FormData();
            if (text) fd.append('body', text);
            if (file) fd.append('image', file);
            if (win._reply && win._reply.id) fd.append('replyToId', win._reply.id);
            clearAttach();
            clearReply(win);

            // Show the message right away with an uploading loader.
            const pending = appendPendingBubble(bodyEl, { text, image: pendingImageUrl, replyTo: replyMeta });
            try {
                const r = await fetch(`/app/community/messages/${userId}`, { method: 'POST',
                    headers: { 'X-CSRF-TOKEN': CSRF(), Accept: 'application/json' }, body: fd });
                const d = await r.json();
                if (d.success) { finalizePending(bodyEl, pending, { id: d.data.id, body: d.data.body, image: d.data.image, replyTo: d.data.replyTo }); }
                else { failPending(pending, d.message); if (text) input.value = text; }
            } catch (_) { failPending(pending, 'Network error — try again.'); if (text) input.value = text; }
        };
        win.querySelector('.msgr-send').addEventListener('click', doSend);
        input.addEventListener('keydown', (e) => { if (e.key === 'Enter') { e.preventDefault(); doSend(); } });

        // Load the thread with retries so a transient hiccup never leaves a
        // dead "Could not load" — it keeps trying, then offers a Retry button.
        async function loadThread(attempt) {
            attempt = attempt || 1;
            try {
                const r = await fetch(`/app/community/messages/${userId}`, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
                if (!r.ok) throw new Error('http ' + r.status);
                const d = await r.json();
                if (!d || !d.data) throw new Error('bad payload');
                const head = win.querySelector('.msgr-window-head');
                if (d.data.user.avatar) head.querySelector('.avatar').outerHTML = `<span class="avatar overflow-hidden"><img src="${esc(d.data.user.avatar)}" class="w-full h-full object-cover" alt=""></span>`;
                else head.querySelector('.avatar').textContent = d.data.user.initials || '?';
                head.querySelector('a').textContent = d.data.user.name;
                bodyEl.innerHTML = '';
                (d.data.messages || []).forEach((m) => appendBubble(bodyEl, m));
                if (!d.data.canMessage) {
                    win.querySelector('.msgr-window-foot-wrap').outerHTML = '<div class="msgr-off">This member has turned off messages.</div>';
                }
                (d.data.messages || []).forEach((m) => { if (m.id > lastSeenId) lastSeenId = m.id; });
                refreshBadge();
            } catch (_) {
                if (attempt < 4) { setTimeout(() => loadThread(attempt + 1), attempt * 700); return; }
                bodyEl.innerHTML = '<p class="text-xs text-gray-400 text-center py-4">Couldn\'t load the chat. <button type="button" class="msgr-retry" style="color:var(--color-brand-600);text-decoration:underline;cursor:pointer">Retry</button></p>';
                bodyEl.querySelector('.msgr-retry')?.addEventListener('click', () => { bodyEl.innerHTML = '<p class="text-xs text-gray-400 text-center py-4">Loading…</p>'; loadThread(1); });
            }
        }
        loadThread(1);
    }

    function appendBubble(bodyEl, m, animate) {
        const row = document.createElement('div');
        row.className = 'msgr-row ' + (m.mine ? 'me' : 'them');

        const b = document.createElement('div');
        b.className = 'msgr-bubble ' + (m.mine ? 'me' : 'them') + (animate ? ' msgr-bubble-in' : '');
        if (m.id) b.dataset.msgId = m.id;
        if (m.body) b.dataset.msgBody = m.body;

        // Quoted reply (FB Messenger style).
        if (m.replyTo && m.replyTo.body) {
            const q = document.createElement('div');
            q.className = 'msgr-quote';
            q.textContent = (m.replyTo.mine ? 'You: ' : '') + m.replyTo.body;
            b.appendChild(q);
        }
        if (m.body) { const t = document.createElement('div'); t.textContent = m.body; b.appendChild(t); }
        if (m.image) {
            const img = document.createElement('img');
            img.src = m.image; img.alt = ''; img.loading = 'lazy';
            img.setAttribute('data-lightbox', '');
            img.addEventListener('load', () => { bodyEl.scrollTop = bodyEl.scrollHeight; });
            b.appendChild(img);
        }
        row.appendChild(b);

        addBubbleActs(row, m);

        bodyEl.appendChild(row);
        bodyEl.scrollTop = bodyEl.scrollHeight;
    }

    // Per-bubble hover actions (reply needs an id, forward needs text). Shared
    // by fully-rendered bubbles and finalized optimistic ones.
    const ICON_REPLY = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 15L3 9m0 0l6-6M3 9h11a6 6 0 010 12h-2"/></svg>';
    const ICON_FWD = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 15l6-6m0 0l-6-6m6 6H10a6 6 0 000 12h2"/></svg>';
    function addBubbleActs(row, m) {
        if (row.querySelector('.msgr-acts')) return;
        const actsHtml =
            (m.id ? '<button type="button" class="msgr-act" data-msgr-act="reply" aria-label="Reply" title="Reply">' + ICON_REPLY + '</button>' : '') +
            (m.body ? '<button type="button" class="msgr-act" data-msgr-act="forward" aria-label="Forward" title="Forward">' + ICON_FWD + '</button>' : '');
        if (!actsHtml) return;
        const acts = document.createElement('div');
        acts.className = 'msgr-acts';
        acts.innerHTML = actsHtml;
        row.appendChild(acts);
    }

    // Optimistic outgoing bubble shown immediately, with an "uploading" loader
    // while the request (and any photo) is in flight. Animates in like a real
    // one; finalized in place on success, removed on failure.
    function appendPendingBubble(bodyEl, m) {
        const row = document.createElement('div');
        row.className = 'msgr-row me';
        const b = document.createElement('div');
        b.className = 'msgr-bubble me msgr-bubble-in is-pending';
        if (m.replyTo && m.replyTo.body) {
            const q = document.createElement('div');
            q.className = 'msgr-quote';
            q.textContent = (m.replyTo.mine ? 'You: ' : '') + m.replyTo.body;
            b.appendChild(q);
        }
        if (m.text) { const t = document.createElement('div'); t.textContent = m.text; b.appendChild(t); }
        if (m.image) { const img = document.createElement('img'); img.src = m.image; img.alt = ''; b.appendChild(img); }
        const load = document.createElement('div');
        load.className = 'msgr-uploading';
        load.innerHTML = '<span class="msgr-spin"></span><span>' + (m.image ? 'Sending photo…' : 'Sending…') + '</span>';
        b.appendChild(load);
        row.appendChild(b);
        bodyEl.appendChild(row);
        bodyEl.scrollTop = bodyEl.scrollHeight;
        return { row, b, load, localUrl: m.image };
    }
    function finalizePending(bodyEl, p, m) {
        p.load?.remove();
        p.b.classList.remove('is-pending');
        if (m.id) p.b.dataset.msgId = m.id;
        if (m.body) p.b.dataset.msgBody = m.body;
        const img = p.b.querySelector('img');
        if (img) {
            img.setAttribute('data-lightbox', '');
            if (m.image) img.src = m.image;         // swap local preview → stored URL
            img.addEventListener('load', () => { bodyEl.scrollTop = bodyEl.scrollHeight; });
        }
        if (p.localUrl) { try { URL.revokeObjectURL(p.localUrl); } catch (_) {} }
        addBubbleActs(p.row, m);
    }
    function failPending(p, msg) {
        if (p.localUrl) { try { URL.revokeObjectURL(p.localUrl); } catch (_) {} }
        p.row.remove();
        if (window.toast) toast(msg || 'Could not send.', 'error');
    }

    // Reply state lives on the window node (win._reply = {id, body}).
    function startReply(win, id, body) {
        if (!win || !id) return;
        win._reply = { id: parseInt(id, 10), body: body || '' };
        const bar = win.querySelector('.msgr-reply-bar');
        bar.querySelector('.rb-body').textContent = body || '📷 Photo';
        bar.classList.add('show');
        win.querySelector('input[type="text"]')?.focus();
    }
    function clearReply(win) {
        if (!win) return;
        win._reply = null;
        const bar = win.querySelector('.msgr-reply-bar');
        if (bar) { bar.classList.remove('show'); bar.querySelector('.rb-body').textContent = ''; }
    }

    // Forward: pick a co-farmer and send them the message body.
    let forwardBody = null, forwardCache = null;
    function openForward(body) {
        if (!body) { if (window.toast) toast('Nothing to forward.', 'error'); return; }
        forwardBody = body;
        const prev = document.getElementById('msgrForwardPreview');
        if (prev) prev.textContent = body.length > 90 ? body.slice(0, 90) + '…' : body;
        if (window.openSheet) window.openSheet('msgrForwardSheet');
        loadForwardList();
    }
    async function loadForwardList() {
        const box = document.getElementById('msgrForwardList');
        if (!box) return;
        if (forwardCache) { renderForwardList(forwardCache); return; }
        try {
            const r = await fetch(@json(route('community.cofarmers.list')), { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
            const d = await r.json();
            forwardCache = (d.data && d.data.items) || [];
            renderForwardList(forwardCache);
        } catch (_) {
            box.innerHTML = '<p class="text-sm text-red-500 px-2 py-3 text-center">Could not load co-farmers.</p>';
        }
    }
    function renderForwardList(items) {
        const box = document.getElementById('msgrForwardList');
        if (!items.length) { box.innerHTML = '<p class="text-sm text-gray-400 px-2 py-3 text-center">No co-farmers yet.</p>'; return; }
        box.innerHTML = items.map((u) => {
            const av = u.avatar
                ? `<img src="${esc(u.avatar)}" alt="" class="w-8 h-8 rounded-full object-cover shrink-0">`
                : `<span class="w-8 h-8 rounded-full bg-brand-100 text-brand-700 text-xs font-bold flex items-center justify-center shrink-0">${esc(u.initials || '?')}</span>`;
            const btn = u.allowMessages
                ? `<button type="button" class="btn btn-white btn-sm shrink-0" data-msgr-fwd="${u.id}">Send</button>`
                : '<span class="text-xs text-gray-400 shrink-0">Messages off</span>';
            return `<div class="flex items-center gap-2 px-2 py-1.5 rounded-lg hover:bg-gray-50">${av}<span class="grow min-w-0 truncate text-sm font-semibold text-gray-800">${esc(u.name)}</span>${btn}</div>`;
        }).join('');
    }

    // Delegated actions for bubble reply/forward + forward-sheet sends.
    document.addEventListener('click', async (e) => {
        const act = e.target.closest('[data-msgr-act]');
        if (act) {
            const bubble = act.closest('.msgr-row')?.querySelector('.msgr-bubble');
            const win = act.closest('.msgr-window');
            if (!bubble) return;
            if (act.getAttribute('data-msgr-act') === 'reply') {
                startReply(win, bubble.dataset.msgId, bubble.dataset.msgBody || '📷 Photo');
            } else {
                openForward(bubble.dataset.msgBody || '');
            }
            return;
        }
        const fwd = e.target.closest('[data-msgr-fwd]');
        if (fwd) {
            const userId = fwd.getAttribute('data-msgr-fwd');
            if (!userId || !forwardBody) return;
            fwd.disabled = true; const orig = fwd.textContent; fwd.textContent = 'Sending…';
            try {
                const fd = new FormData();
                fd.append('body', forwardBody);
                const r = await fetch(`/app/community/messages/${userId}`, { method: 'POST', headers: { 'X-CSRF-TOKEN': CSRF(), Accept: 'application/json' }, body: fd });
                const d = await r.json();
                if (d.success) { fwd.textContent = 'Sent ✓'; if (window.toast) toast('Message forwarded.'); }
                else { fwd.disabled = false; fwd.textContent = orig; if (window.toast) toast(d.message || 'Could not send.', 'error'); }
            } catch (_) { fwd.disabled = false; fwd.textContent = orig; if (window.toast) toast('Network error.', 'error'); }
        }
    });

    // Public: open a DM window (used by the bell deep-link + programmatic callers).
    window.plazaOpenDm = (userId, name) => openWindow(parseInt(userId, 10), name);

    // Any "Message" button anywhere on the page (profile, member cards…) opens
    // the dock. Data-attribute + delegation avoids fragile inline onclick.
    document.addEventListener('click', (e) => {
        const b = e.target.closest('.js-open-dm');
        if (!b) return;
        e.preventDefault();
        openWindow(parseInt(b.dataset.dmUser, 10), b.dataset.dmName || 'Member');
    });

    // Deep-link: /app/community?dm=<userId> opens that thread (from the bell).
    const dm = new URLSearchParams(location.search).get('dm');
    if (dm && /^\d+$/.test(dm)) openWindow(parseInt(dm, 10), 'Member');

    /* The dock's own line.
     *
     * Sending a DM already broadcasts UserNotified on the recipient's
     * private user.{id} channel — that is how their bell rings the instant a
     * message lands. The dock never subscribed to it, so the bell announced
     * a message the chat window would not show for up to eight more seconds.
     * It listens now and pulls immediately; the poll stays as the floor,
     * because a dropped broadcast should cost a few seconds, not the
     * message. */
    const ME = {{ (int) auth()->id() }};
    try {
        window.Echo?.private('user.' + ME).listen('.notify', (p) => {
            if (!p || (p.type && p.type !== 'message')) return;
            livePoll();
        });
    } catch (_) { /* no realtime here — the poll covers it */ }

    livePoll();
    setInterval(livePoll, 8000);
})();
</script>
