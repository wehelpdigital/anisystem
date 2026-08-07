{{-- YouTube preview card for a post body. Expects: $vid (11-char video id).
     Thumbnail → click to play inline. Title lazy-loaded via oEmbed. A Forward
     button shares the video to a co-farmer via community messaging.
     The shared JS + forward sheet + CSS emit once per page via @once. --}}
@php $ytUrl = 'https://www.youtube.com/watch?v=' . $vid; @endphp
<div class="yt-card" data-yt="{{ $vid }}">
    <button type="button" class="yt-thumb" data-yt-play="{{ $vid }}" aria-label="Play video">
        <img src="https://i.ytimg.com/vi/{{ $vid }}/hqdefault.jpg" alt="" loading="lazy" onerror="this.src='https://img.youtube.com/vi/{{ $vid }}/hqdefault.jpg'">
        <span class="yt-play">
            <svg viewBox="0 0 68 48" width="54" height="38"><path d="M66.5 7.7c-.8-2.9-3-5.1-5.9-5.9C55.3.5 34 .5 34 .5S12.7.5 7.4 1.8C4.5 2.6 2.3 4.8 1.5 7.7.2 13 .2 24 .2 24s0 11 1.3 16.3c.8 2.9 3 5.1 5.9 5.9C12.7 47.5 34 47.5 34 47.5s21.3 0 26.6-1.3c2.9-.8 5.1-3 5.9-5.9C67.8 35 67.8 24 67.8 24s0-11-1.3-16.3z" fill="#f00"/><path d="M27 34l18-10-18-10z" fill="#fff"/></svg>
        </span>
    </button>
    <div class="yt-meta">
        <span class="yt-title" data-yt-title="{{ $vid }}">YouTube video</span>
        <div class="yt-actions">
            <a href="{{ $ytUrl }}" target="_blank" rel="noopener" class="yt-open">Watch on YouTube ↗</a>
            <button type="button" class="yt-forward" data-yt-forward="{{ $vid }}" title="Forward to a co-farmer">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                Forward
            </button>
        </div>
    </div>
</div>

@once
<style>
    .yt-card { margin-top:.6rem; border:1px solid var(--color-gray-100); border-radius:.9rem; overflow:hidden; background:var(--color-white); }
    .yt-thumb { display:block; width:100%; position:relative; cursor:pointer; line-height:0; background:#000; }
    .yt-thumb img { width:100%; height:auto; aspect-ratio:16/9; object-fit:cover; opacity:.92; transition:opacity .2s ease; }
    .yt-thumb:hover img { opacity:1; }
    .yt-play { position:absolute; inset:0; display:flex; align-items:center; justify-content:center; }
    .yt-play svg { filter:drop-shadow(0 1px 3px rgba(0,0,0,.4)); opacity:.92; transition:opacity .2s ease, transform .15s ease; }
    .yt-thumb:hover .yt-play svg { opacity:1; transform:scale(1.06); }
    .yt-card iframe { width:100%; aspect-ratio:16/9; border:0; display:block; }
    .yt-meta { display:flex; align-items:center; gap:.6rem; padding:.5rem .7rem; }
    .yt-title { font-size:.82rem; font-weight:700; color:var(--color-gray-800); min-width:0; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    html.dark .yt-title { color:var(--color-gray-900); }
    .yt-actions { margin-left:auto; display:flex; align-items:center; gap:.5rem; flex-shrink:0; }
    .yt-open { font-size:.74rem; font-weight:600; color:#c00; text-decoration:none; white-space:nowrap; }
    .yt-open:hover { text-decoration:underline; }
    .yt-forward { display:inline-flex; align-items:center; gap:.3rem; font-size:.74rem; font-weight:700; color:var(--color-brand-700); padding:.25rem .5rem; border-radius:.5rem; transition:background .15s ease; }
    .yt-forward:hover { background:var(--color-brand-50); }
    html.dark .yt-forward { color:var(--color-brand-300); }
    html.dark .yt-forward:hover { background:rgb(74 124 42 / .2); }
</style>

@push('sheets')
<div class="sheet hidden" id="ytForwardSheet" style="--sheet-width:28rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title">Forward video</h3>
        <button data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body">
        <label class="form-label">Send to a co-farmer</label>
        <div id="ytForwardList" class="space-y-1 max-h-72 overflow-y-auto rounded-xl border border-gray-100 p-1">
            <p class="text-sm text-gray-400 px-2 py-3 text-center">Loading your co-farmers…</p>
        </div>
    </div>
</div>
@endpush

@push('scripts')
<script>
(function youtubePosts() {
    if (window.__ytBound) return;
    window.__ytBound = true;

    const COFARMERS_URL = @json(route('community.cofarmers.list'));
    const SEND_URL = @json(url('/app/community/messages'));
    const esc = window.escapeHtml || ((s) => String(s ?? ''));
    let forwardVid = null, cofarmersCache = null;

    // Lazy-load real titles via YouTube oEmbed (CORS-enabled, no key needed).
    const titled = new Set();
    async function fillTitles() {
        const els = document.querySelectorAll('.yt-title[data-yt-title]');
        for (const el of els) {
            const vid = el.getAttribute('data-yt-title');
            if (!vid || titled.has(vid)) continue;
            titled.add(vid);
            try {
                const r = await fetch('https://www.youtube.com/oembed?format=json&url=' + encodeURIComponent('https://www.youtube.com/watch?v=' + vid));
                if (!r.ok) continue;
                const d = await r.json();
                document.querySelectorAll(`.yt-title[data-yt-title="${vid}"]`).forEach((n) => { n.textContent = d.title || 'YouTube video'; });
            } catch (_) { /* offline / blocked — keep generic label */ }
        }
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', fillTitles, { once: true });
    else fillTitles();

    document.addEventListener('click', (e) => {
        // Click thumbnail → swap in the embed and autoplay.
        const play = e.target.closest('[data-yt-play]');
        if (play) {
            const vid = play.getAttribute('data-yt-play');
            const card = play.closest('.yt-card');
            const frame = document.createElement('iframe');
            frame.src = 'https://www.youtube.com/embed/' + vid + '?autoplay=1&rel=0';
            frame.allow = 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture';
            frame.setAttribute('allowfullscreen', '');
            play.replaceWith(frame);
            return;
        }
        // Forward → open the co-farmer picker.
        const fwd = e.target.closest('[data-yt-forward]');
        if (fwd) {
            forwardVid = fwd.getAttribute('data-yt-forward');
            window.openSheet('ytForwardSheet');
            loadForwardList();
            return;
        }
        // Send within the picker.
        const send = e.target.closest('[data-yt-send]');
        if (send) { forwardTo(send); return; }
    });

    async function loadForwardList() {
        const box = document.getElementById('ytForwardList');
        if (cofarmersCache) { renderForwardList(cofarmersCache); return; }
        try {
            const res = await window.api(COFARMERS_URL);
            cofarmersCache = (res.data && res.data.items) || [];
            renderForwardList(cofarmersCache);
        } catch (err) {
            box.innerHTML = `<p class="text-sm text-red-500 px-2 py-3 text-center">${esc(err.message)}</p>`;
        }
    }
    function renderForwardList(items) {
        const box = document.getElementById('ytForwardList');
        if (!items.length) {
            box.innerHTML = '<p class="text-sm text-gray-400 px-2 py-3 text-center">No co-farmers yet.</p>';
            return;
        }
        box.innerHTML = items.map((u) => {
            const av = u.avatar
                ? `<img src="${esc(u.avatar)}" alt="" class="w-8 h-8 rounded-full object-cover shrink-0">`
                : `<span class="w-8 h-8 rounded-full bg-brand-100 text-brand-700 text-xs font-bold flex items-center justify-center shrink-0">${esc(u.initials || '?')}</span>`;
            const btn = u.allowMessages
                ? `<button type="button" class="btn btn-white btn-sm shrink-0" data-yt-send="${u.id}">Send</button>`
                : `<span class="text-xs text-gray-400 shrink-0">Messages off</span>`;
            return `<div class="flex items-center gap-2 px-2 py-1.5 rounded-lg hover:bg-gray-50">
                ${av}<span class="grow min-w-0 truncate text-sm font-semibold text-gray-800">${esc(u.name)}</span>${btn}</div>`;
        }).join('');
    }
    async function forwardTo(btn) {
        const userId = btn.getAttribute('data-yt-send');
        if (!userId || !forwardVid) return;
        btn.disabled = true; const orig = btn.textContent; btn.textContent = 'Sending…';
        try {
            await window.api(SEND_URL + '/' + userId, {
                method: 'POST',
                body: { body: 'https://www.youtube.com/watch?v=' + forwardVid },
            });
            btn.textContent = 'Sent ✓';
            window.toast('Video forwarded.');
        } catch (err) {
            btn.disabled = false; btn.textContent = orig;
            window.toast(err.message, 'error');
        }
    }
})();
</script>
@endpush
@endonce
