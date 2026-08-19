@once
{{-- Reels: sixty seconds, filling the phone.

     Three things live here, because they are one feature and splitting them
     would mean three files that only ever appear together:

       the rail     — a strip of covers at the top of the wall
       the viewer   — full screen, one reel at a time, swipe for the next
       the studio   — film or pick, then trim, filter, caption and score it

     A reel is a wall post underneath (see the migration), so the viewer's
     react and comment buttons speak to the same endpoints every other post
     uses. Nothing about reacting or commenting is written twice. --}}

{{-- ------------------------------------------------------------ the rail --}}
<section class="rl-rail-wrap" id="rlRailWrap" aria-label="Reels" hidden>
    <div class="rl-rail-head">
        <h2>Reels</h2>
        <button type="button" class="rl-new" id="rlNew">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14m-7-7h14"/></svg>
            Make one
        </button>
    </div>
    <div class="rl-rail" id="rlRail"></div>
</section>

{{-- ---------------------------------------------------------- the viewer --}}
<div class="rl-viewer hidden" id="rlViewer" role="dialog" aria-modal="true" aria-label="Reels">
    <button type="button" class="rl-x" id="rlClose" aria-label="Close">✕</button>
    <div class="rl-deck" id="rlDeck"></div>
</div>

{{-- ---------------------------------------------------------- the studio --}}
<div class="rl-studio hidden" id="rlStudio" role="dialog" aria-modal="true" aria-label="Make a reel">
    <div class="rl-bar">
        <button type="button" class="rl-icon" id="rlCancel" aria-label="Cancel">✕</button>
        <span class="rl-title" id="rlStep">Make a reel</span>
        <button type="button" class="btn btn-primary btn-sm hidden" id="rlPost">Post</button>
    </div>

    {{-- Step one: where the video comes from. --}}
    <div class="rl-pick" id="rlPick">
        <div class="rl-pick-inner">
            <p class="rl-pick-lead">A minute, no more — and it fills the screen.</p>
            <button type="button" class="rl-source" id="rlRecord">
                <span class="rl-source-ico">🎥</span>
                <span><b>Record now</b><i>Film it where you are standing</i></span>
            </button>
            <button type="button" class="rl-source" id="rlUpload">
                <span class="rl-source-ico">⬆️</span>
                <span><b>Upload a video</b><i>Something already on this phone</i></span>
            </button>
            <button type="button" class="rl-source" id="rlFromGallery">
                <span class="rl-source-ico">🖼️</span>
                <span><b>From your gallery</b><i>A clip one of your seasons kept</i></span>
            </button>
            <input type="file" id="rlFile" accept="video/*" class="hidden">
            <input type="file" id="rlFileCam" accept="video/*" capture="environment" class="hidden">
        </div>
    </div>

    {{-- Step two: making it yours. --}}
    <div class="rl-edit hidden" id="rlEdit">
        <div class="rl-stage">
            <video id="rlPreview" playsinline muted loop></video>
            <div class="rl-overlay-text" id="rlOverlayText"></div>
        </div>

        <div class="rl-tools">
            <div class="rl-trim">
                <label class="rl-lbl" for="rlStart">Start</label>
                <input type="range" id="rlStart" min="0" max="0" step="0.1" value="0">
                <span class="rl-time" id="rlStartLabel">0.0s</span>
            </div>
            <div class="rl-trim">
                <label class="rl-lbl" for="rlLen">Length</label>
                <input type="range" id="rlLen" min="1" max="60" step="1" value="15">
                <span class="rl-time" id="rlLenLabel">15s</span>
            </div>

            <div class="rl-looks" id="rlLooks"></div>

            <input type="text" id="rlOverlay" class="form-input" maxlength="120" placeholder="Words on the video (optional)">
            <textarea id="rlCaption" class="form-textarea" rows="2" maxlength="2000" placeholder="Say something about it…"></textarea>

            <button type="button" class="rl-music" id="rlMusicBtn">
                <span>🎵</span><span id="rlMusicName">Add music</span>
            </button>
            <input type="file" id="rlAudio" accept="audio/*" class="hidden">
        </div>
    </div>

    <div class="rl-busy hidden" id="rlBusy">
        <div class="rl-spin"></div>
        <p>Preparing your reel…</p>
        <small>Trimming, filling the screen, and making it small enough to travel.</small>
    </div>
</div>

{{-- The music picker, its own sheet so the studio stays uncluttered. --}}
<div class="sheet hidden" id="rlMusicSheet" style="--sheet-width:24rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title">Music</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body space-y-1" id="rlMusicList">
        <p class="text-sm text-gray-400 text-center py-4">Loading…</p>
    </div>
</div>

<style>
    /* ---------------------------------------------------------- the rail */
    .rl-rail-wrap { margin-bottom: 1.25rem; }
    .rl-rail-wrap[hidden] { display: none; }
    .rl-rail-head { display: flex; align-items: center; justify-content: space-between; gap: .75rem; margin-bottom: .5rem; }
    .rl-rail-head h2 { font-family: var(--font-heading); font-size: .95rem; font-weight: 800; color: var(--color-gray-900); }
    .rl-new { display: inline-flex; align-items: center; gap: .3rem; padding: .3rem .6rem; border-radius: 999px;
        border: 1px solid var(--color-brand-100); background: var(--color-brand-50); color: var(--color-brand-700);
        font-size: .75rem; font-weight: 800; cursor: pointer; }
    .rl-new svg { width: .9rem; height: .9rem; }
    .rl-rail { display: flex; gap: .5rem; overflow-x: auto; padding-bottom: .3rem; scroll-snap-type: x proximity; }
    .rl-tile { position: relative; flex: none; width: 7.5rem; aspect-ratio: 9 / 16; border-radius: .85rem;
        overflow: hidden; border: 0; padding: 0; cursor: pointer; background: #111; scroll-snap-align: start;
        transition: transform .28s cubic-bezier(.22,1,.36,1); }
    .rl-tile:hover { transform: translateY(-2px); }
    .rl-tile img { width: 100%; height: 100%; object-fit: cover; display: block; }
    .rl-tile-grad { position: absolute; inset: auto 0 0 0; height: 60%;
        background: linear-gradient(to top, rgb(0 0 0 / .78), transparent); }
    .rl-tile-who { position: absolute; left: .4rem; right: .4rem; bottom: .4rem; color: #fff; text-align: left; }
    .rl-tile-who b { display: block; font-size: .68rem; font-weight: 800; overflow: hidden;
        text-overflow: ellipsis; white-space: nowrap; }
    .rl-tile-who i { font-style: normal; font-size: .6rem; opacity: .8; }

    /* -------------------------------------------------------- the viewer */
    .rl-viewer { position: fixed; inset: 0; z-index: 330; background: #000;
        animation: rlIn .28s cubic-bezier(.22,1,.36,1); }
    .rl-viewer.hidden { display: none; }
    @keyframes rlIn { from { opacity: 0; } }
    .rl-x { position: absolute; top: calc(env(safe-area-inset-top) + .7rem); right: .8rem; z-index: 3;
        width: 2.4rem; height: 2.4rem; border-radius: 999px; border: 0; cursor: pointer;
        background: rgb(255 255 255 / .16); color: #fff; font-size: 1.05rem; }
    /* One reel per screen, snapped, scrolled with a thumb. */
    .rl-deck { height: 100%; overflow-y: auto; scroll-snap-type: y mandatory; }
    .rl-slide { position: relative; height: 100%; scroll-snap-align: start; display: flex;
        align-items: center; justify-content: center; }
    .rl-slide video { width: 100%; height: 100%; object-fit: contain; background: #000; }
    .rl-meta { position: absolute; left: 0; right: 4.5rem; bottom: 0; padding: 1rem 1rem calc(1.4rem + env(safe-area-inset-bottom));
        background: linear-gradient(to top, rgb(0 0 0 / .8), transparent); color: #fff; }
    .rl-meta-who { display: flex; align-items: center; gap: .5rem; font-weight: 800; font-size: .9rem; }
    .rl-meta-who span { width: 2rem; height: 2rem; border-radius: 999px; overflow: hidden; background: rgb(255 255 255 / .2);
        display: inline-flex; align-items: center; justify-content: center; font-size: .7rem; }
    .rl-meta-who img { width: 100%; height: 100%; object-fit: cover; }
    .rl-meta p { margin-top: .4rem; font-size: .88rem; line-height: 1.5; }
    .rl-meta small { display: block; margin-top: .3rem; font-size: .72rem; opacity: .75; }
    .rl-side { position: absolute; right: .6rem; bottom: calc(2rem + env(safe-area-inset-bottom)); z-index: 2;
        display: flex; flex-direction: column; gap: .9rem; align-items: center; }
    .rl-side button { width: 2.9rem; height: 2.9rem; border-radius: 999px; border: 0; cursor: pointer;
        background: rgb(255 255 255 / .16); color: #fff; display: inline-flex; flex-direction: column;
        align-items: center; justify-content: center; gap: .05rem; }
    .rl-side button svg { width: 1.3rem; height: 1.3rem; }
    .rl-side b { font-size: .62rem; font-weight: 800; }

    /* -------------------------------------------------------- the studio */
    .rl-studio { position: fixed; inset: 0; z-index: 335; display: flex; flex-direction: column;
        background: #0b0f0a; color: #fff;
        padding-top: env(safe-area-inset-top); padding-bottom: env(safe-area-inset-bottom); }
    .rl-studio.hidden { display: none; }
    .rl-bar { display: flex; align-items: center; justify-content: space-between; gap: .5rem;
        padding: .55rem .7rem; border-bottom: 1px solid rgb(255 255 255 / .08); flex: none; }
    .rl-title { font-weight: 700; font-size: .95rem; }
    .rl-icon { width: 2.25rem; height: 2.25rem; border-radius: 999px; border: 0; cursor: pointer;
        background: rgb(255 255 255 / .12); color: #fff; }

    .rl-pick { flex: 1 1 auto; display: flex; align-items: center; justify-content: center; padding: 1rem; }
    .rl-pick.hidden { display: none; }
    .rl-pick-inner { width: 100%; max-width: 22rem; display: flex; flex-direction: column; gap: .6rem; }
    .rl-pick-lead { text-align: center; font-size: .85rem; color: rgb(255 255 255 / .6); margin-bottom: .4rem; }
    .rl-source { display: flex; align-items: center; gap: .8rem; padding: .8rem .9rem; border-radius: .9rem;
        border: 1px solid rgb(255 255 255 / .14); background: rgb(255 255 255 / .06); color: #fff;
        cursor: pointer; text-align: left; }
    .rl-source:hover { background: rgb(255 255 255 / .12); }
    .rl-source-ico { font-size: 1.4rem; }
    .rl-source b { display: block; font-size: .9rem; }
    .rl-source i { font-style: normal; font-size: .74rem; color: rgb(255 255 255 / .55); }

    .rl-edit { flex: 1 1 auto; min-height: 0; display: flex; flex-direction: column; }
    .rl-edit.hidden { display: none; }
    .rl-stage { position: relative; flex: 1 1 auto; min-height: 0; display: flex; align-items: center;
        justify-content: center; background: #000; }
    .rl-stage video { max-width: 100%; max-height: 100%; aspect-ratio: 9 / 16; object-fit: cover; background: #000; }
    .rl-overlay-text { position: absolute; left: 0; right: 0; bottom: 12%; text-align: center; padding: 0 1rem;
        font-weight: 800; font-size: 1rem; color: #fff; pointer-events: none; }
    .rl-overlay-text span { background: rgb(0 0 0 / .45); padding: .25rem .5rem; border-radius: .3rem; }
    .rl-tools { flex: none; padding: .6rem .7rem .8rem; display: flex; flex-direction: column; gap: .5rem;
        max-height: 46vh; overflow-y: auto; border-top: 1px solid rgb(255 255 255 / .08); }
    .rl-trim { display: flex; align-items: center; gap: .5rem; }
    .rl-trim input[type=range] { flex: 1; }
    .rl-lbl { font-size: .74rem; font-weight: 700; color: rgb(255 255 255 / .6); min-width: 3.2rem; }
    .rl-time { font-size: .74rem; font-weight: 700; min-width: 3rem; text-align: right; font-variant-numeric: tabular-nums; }
    .rl-looks { display: flex; gap: .4rem; overflow-x: auto; padding-bottom: .2rem; }
    .rl-look { flex: none; padding: .3rem .65rem; border-radius: 999px; border: 1px solid rgb(255 255 255 / .18);
        background: rgb(255 255 255 / .06); color: rgb(255 255 255 / .75); font-size: .74rem;
        font-weight: 700; cursor: pointer; }
    .rl-look.is-on { background: #fff; color: #111; border-color: #fff; }
    .rl-music { display: flex; align-items: center; gap: .5rem; padding: .55rem .7rem; border-radius: .7rem;
        border: 1px solid rgb(255 255 255 / .16); background: rgb(255 255 255 / .06); color: #fff;
        font-size: .82rem; font-weight: 700; cursor: pointer; }
    .rl-studio .form-input, .rl-studio .form-textarea {
        background: rgb(255 255 255 / .1); border-color: rgb(255 255 255 / .18); color: #fff; }
    .rl-studio .form-input::placeholder, .rl-studio .form-textarea::placeholder { color: rgb(255 255 255 / .45); }

    .rl-busy { position: absolute; inset: 0; z-index: 4; display: flex; flex-direction: column;
        align-items: center; justify-content: center; gap: .5rem; text-align: center; padding: 1rem;
        background: rgb(11 15 10 / .92); }
    .rl-busy.hidden { display: none; }
    .rl-busy p { font-weight: 700; }
    .rl-busy small { color: rgb(255 255 255 / .55); max-width: 20rem; }
    .rl-spin { width: 2.4rem; height: 2.4rem; border-radius: 999px; border: 3px solid rgb(255 255 255 / .25);
        border-top-color: #fff; animation: rlSpin .8s linear infinite; }
    @keyframes rlSpin { to { transform: rotate(360deg); } }

    @media (prefers-reduced-motion: reduce) {
        .rl-viewer { animation: none; }
        .rl-tile { transition: none; }
        /* Slowed, not stopped: an encode takes real seconds and a still
           spinner reads as a crash. */
        .rl-spin { animation-duration: 2.4s; }
    }
</style>

<script>
(function reels() {
    if (window.__reelsBooted) return;
    window.__reelsBooted = true;

    const URLS = {
        feed: @json(route('community.reels.feed')),
        store: @json(route('community.reels.store')),
        music: @json(route('community.reels.music')),
    };
    const LOOKS = ['none', 'warm', 'cool', 'bright', 'punch', 'mono', 'faded'];
    const MAX = 60;
    const $ = (id) => document.getElementById(id);
    const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content || '';
    const esc = (s) => (window.escapeHtml ? window.escapeHtml(s) : String(s ?? ''));

    let reels = [];

    /* ------------------------------------------------------------ rail */
    async function loadRail() {
        const rail = $('rlRail');
        const wrap = $('rlRailWrap');
        if (!rail || !wrap) return;
        /* Shown before the fetch, not after it.
         *
         * The rail carries "Make one", so hiding it until reels arrive means a
         * farmer on a bad connection cannot post one either — the feature
         * disappears exactly when the network is worst. It appears empty and
         * fills in. */
        wrap.hidden = false;
        try {
            const r = await fetch(URLS.feed, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
            const items = ((await r.json()).data || {}).items || [];
            reels = items;
            rail.innerHTML = items.map((it, i) => `
                <button type="button" class="rl-tile" data-reel="${i}" aria-label="Play reel by ${esc(it.author.name)}">
                    ${it.poster ? `<img src="${esc(it.poster)}" alt="" loading="lazy">` : ''}
                    <span class="rl-tile-grad"></span>
                    <span class="rl-tile-who"><b>${esc(it.author.name)}</b><i>${it.seconds}s</i></span>
                </button>`).join('');
            if (!items.length) {
                rail.innerHTML = '<p style="font-size:.8rem;color:var(--color-gray-400);padding:.5rem 0">'
                    + 'Wala pang reels — ikaw ang mauna.</p>';
            }
        } catch (_) {
            // The covers could not be fetched; making one still can be.
            rail.innerHTML = '<p style="font-size:.8rem;color:var(--color-gray-400);padding:.5rem 0">'
                + 'Reels could not load just now.</p>';
        }
    }

    /* ---------------------------------------------------------- viewer */
    function openViewer(index) {
        const deck = $('rlDeck');
        deck.innerHTML = reels.map((it) => `
            <div class="rl-slide" data-post="${it.id}">
                <video src="${esc(it.video)}" ${it.poster ? `poster="${esc(it.poster)}"` : ''} playsinline loop preload="metadata"></video>
                <div class="rl-side">
                    <button type="button" data-rl-react="${it.id}" aria-label="Like">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><path stroke-linecap="round" stroke-linejoin="round" d="M4.3 12.2l7.7 7.7 7.7-7.7a4.5 4.5 0 10-6.4-6.4L12 6.9l-1.3-1.1a4.5 4.5 0 10-6.4 6.4z"/></svg>
                    </button>
                    <button type="button" class="js-open-comments" data-post-id="${it.id}" aria-label="Comments">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><path stroke-linecap="round" stroke-linejoin="round" d="M8 12h8m-8-4h5m-6 12V6a2 2 0 012-2h12a2 2 0 012 2v8a2 2 0 01-2 2H8l-3 4z"/></svg>
                        <b>${it.comments}</b>
                    </button>
                    <button type="button" class="js-share" data-post-id="${it.id}" aria-label="Share">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><path stroke-linecap="round" stroke-linejoin="round" d="M4 12v7a2 2 0 002 2h12a2 2 0 002-2v-7M16 6l-4-4-4 4M12 2v14"/></svg>
                    </button>
                    <button type="button" class="js-bookmark" data-post-id="${it.id}" aria-label="Save">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><path stroke-linecap="round" stroke-linejoin="round" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-4-7 4V5z"/></svg>
                    </button>
                </div>
                <div class="rl-meta">
                    <span class="rl-meta-who">
                        <span>${it.author.avatar ? `<img src="${esc(it.author.avatar)}" alt="">` : esc(it.author.initials)}</span>
                        ${esc(it.author.name)}
                    </span>
                    ${it.caption ? `<p>${esc(it.caption)}</p>` : ''}
                    ${it.audio ? `<small>🎵 ${esc(it.audio)}</small>` : ''}
                </div>
            </div>`).join('');
        $('rlViewer').classList.remove('hidden');
        document.documentElement.classList.add('overlay-open');
        const slides = deck.querySelectorAll('.rl-slide');
        slides[index]?.scrollIntoView();
        watchSlides(deck);
    }

    /* Only the reel on screen plays. Anything else is four videos fighting
       over one radio, which on a farm connection means none of them play. */
    function watchSlides(deck) {
        const obs = new IntersectionObserver((entries) => {
            entries.forEach((en) => {
                const v = en.target.querySelector('video');
                if (!v) return;
                if (en.isIntersecting && en.intersectionRatio > 0.6) v.play().catch(() => {});
                else v.pause();
            });
        }, { root: deck, threshold: [0, 0.6, 1] });
        deck.querySelectorAll('.rl-slide').forEach((s) => obs.observe(s));
        deck.__obs = obs;
    }

    function closeViewer() {
        const deck = $('rlDeck');
        deck.__obs?.disconnect();
        deck.querySelectorAll('video').forEach((v) => v.pause());
        deck.innerHTML = '';
        $('rlViewer').classList.add('hidden');
        document.documentElement.classList.remove('overlay-open');
    }

    document.addEventListener('click', (e) => {
        const tile = e.target.closest('[data-reel]');
        if (tile) { openViewer(parseInt(tile.dataset.reel, 10)); return; }
        if (e.target.closest('#rlClose')) closeViewer();
    });
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && !$('rlViewer').classList.contains('hidden')) closeViewer();
    });

    // Reacting rides the same endpoint every post uses.
    document.addEventListener('click', async (e) => {
        const btn = e.target.closest('[data-rl-react]');
        if (!btn) return;
        try {
            await fetch(@json(route('community.react')), {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf(), Accept: 'application/json', 'Content-Type': 'application/json' },
                credentials: 'same-origin',
                body: JSON.stringify({ type: 'wallpost', id: btn.dataset.rlReact, reaction: 'like' }),
            });
            btn.style.background = 'rgb(239 68 68 / .85)';
        } catch (_) { window.toast?.('Could not react just now.', 'error'); }
    });

    /* ---------------------------------------------------------- studio */
    let chosen = null, look = 'none', audioFile = null, audioName = null, media = null;

    function studio(on) {
        $('rlStudio').classList.toggle('hidden', !on);
        document.documentElement.classList.toggle('overlay-open', on);
        if (!on) {
            $('rlPreview').pause();
            $('rlPreview').removeAttribute('src');
            chosen = null; audioFile = null; audioName = null; look = 'none';
            $('rlPick').classList.remove('hidden');
            $('rlEdit').classList.add('hidden');
            $('rlPost').classList.add('hidden');
            $('rlBusy').classList.add('hidden');
            $('rlStep').textContent = 'Make a reel';
        }
    }

    $('rlNew')?.addEventListener('click', () => studio(true));
    $('rlCancel')?.addEventListener('click', () => studio(false));
    $('rlRecord')?.addEventListener('click', () => $('rlFileCam').click());
    $('rlUpload')?.addEventListener('click', () => $('rlFile').click());

    $('rlFromGallery')?.addEventListener('click', () => {
        if (typeof window.smPickMedia !== 'function') { window.toast?.('The gallery is not available here.', 'error'); return; }
        window.smPickMedia({
            allSchedules: true,
            kinds: 'video',
            title: 'Pick a clip',
            onPick: async (item) => {
                // A gallery clip has to become a real file to be re-encoded;
                // it is already ours, so fetching it back is safe.
                try {
                    const r = await fetch(item.url, { credentials: 'same-origin' });
                    const blob = await r.blob();
                    takeVideo(new File([blob], 'clip.mp4', { type: blob.type || 'video/mp4' }));
                } catch (_) { window.toast?.('That clip could not be opened.', 'error'); }
            },
        });
    });

    [$('rlFile'), $('rlFileCam')].forEach((input) => input?.addEventListener('change', (e) => {
        const f = e.target.files && e.target.files[0];
        if (f) takeVideo(f);
        e.target.value = '';
    }));

    function takeVideo(file) {
        chosen = file;
        const url = URL.createObjectURL(file);
        const v = $('rlPreview');
        v.src = url;
        v.onloadedmetadata = () => {
            const dur = Math.max(1, Math.min(v.duration || MAX, 600));
            $('rlStart').max = String(Math.max(0, dur - 1));
            $('rlLen').max = String(Math.min(MAX, Math.round(dur)));
            $('rlLen').value = String(Math.min(15, Math.round(dur)));
            syncLabels();
            v.play().catch(() => {});
        };
        $('rlPick').classList.add('hidden');
        $('rlEdit').classList.remove('hidden');
        $('rlPost').classList.remove('hidden');
        $('rlStep').textContent = 'Make it yours';
        paintLooks();
    }

    function syncLabels() {
        $('rlStartLabel').textContent = parseFloat($('rlStart').value).toFixed(1) + 's';
        $('rlLenLabel').textContent = $('rlLen').value + 's';
        const v = $('rlPreview');
        if (v.duration) v.currentTime = parseFloat($('rlStart').value);
    }
    $('rlStart')?.addEventListener('input', syncLabels);
    $('rlLen')?.addEventListener('input', syncLabels);

    function paintLooks() {
        const host = $('rlLooks');
        host.innerHTML = LOOKS.map((l) => `<button type="button" class="rl-look${l === look ? ' is-on' : ''}" data-look="${l}">${l === 'none' ? 'Original' : l[0].toUpperCase() + l.slice(1)}</button>`).join('');
    }
    $('rlLooks')?.addEventListener('click', (e) => {
        const b = e.target.closest('[data-look]');
        if (!b) return;
        look = b.dataset.look;
        paintLooks();
        // Previewed with the browser's own filter, which is close enough to
        // choose by and free; the encoder does the real one.
        const map = { none: 'none', warm: 'saturate(1.25) sepia(.18)', cool: 'saturate(1.1) hue-rotate(-12deg)',
            bright: 'brightness(1.18)', punch: 'saturate(1.6) contrast(1.18)', mono: 'grayscale(1)',
            faded: 'saturate(.75) brightness(1.08) contrast(.92)' };
        $('rlPreview').style.filter = map[look] || 'none';
    });

    $('rlOverlay')?.addEventListener('input', (e) => {
        const t = e.target.value.trim();
        $('rlOverlayText').innerHTML = t ? '<span>' + esc(t) + '</span>' : '';
    });

    $('rlMusicBtn')?.addEventListener('click', async () => {
        window.openSheet?.('rlMusicSheet');
        const list = $('rlMusicList');
        list.innerHTML = '<p class="text-sm text-gray-400 text-center py-4">Loading…</p>';
        let items = [];
        try {
            const r = await fetch(URLS.music, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
            items = ((await r.json()).data || {}).items || [];
        } catch (_) {}
        list.innerHTML = '<button type="button" class="ai-attach-opt" data-rl-own>'
            + '<span class="ic">📱</span><span>Sound from this phone<span class="sub">Pick an audio file</span></span></button>'
            + '<button type="button" class="ai-attach-opt" data-rl-nomusic>'
            + '<span class="ic">🔇</span><span>Keep the original sound</span></button>'
            + items.map((m) => `<button type="button" class="ai-attach-opt" data-rl-track="${esc(m.name)}">`
                + `<span class="ic">🎵</span><span>${esc(m.title)}</span></button>`).join('')
            + (items.length ? '' : '<p class="text-xs text-gray-400 px-2 py-3">No tracks have been added to the library yet.</p>');
    });

    document.addEventListener('click', (e) => {
        if (e.target.closest('[data-rl-own]')) { window.closeSheet?.('rlMusicSheet'); $('rlAudio').click(); return; }
        if (e.target.closest('[data-rl-nomusic]')) {
            audioFile = null; audioName = null;
            $('rlMusicName').textContent = 'Add music';
            window.closeSheet?.('rlMusicSheet');
            return;
        }
        const track = e.target.closest('[data-rl-track]');
        if (track) {
            audioFile = null;
            audioName = track.dataset.rlTrack;
            $('rlMusicName').textContent = track.textContent.trim();
            window.closeSheet?.('rlMusicSheet');
        }
    });
    $('rlAudio')?.addEventListener('change', (e) => {
        audioFile = e.target.files && e.target.files[0];
        audioName = null;
        if (audioFile) $('rlMusicName').textContent = audioFile.name;
    });

    $('rlPost')?.addEventListener('click', async () => {
        if (!chosen) return;
        $('rlBusy').classList.remove('hidden');
        const fd = new FormData();
        fd.append('video', chosen);
        fd.append('caption', $('rlCaption').value.trim());
        fd.append('overlay', $('rlOverlay').value.trim());
        fd.append('start', $('rlStart').value);
        fd.append('duration', $('rlLen').value);
        fd.append('look', look);
        if (audioFile) fd.append('audio', audioFile);
        if (audioName) fd.append('audioName', audioName);
        try {
            const r = await fetch(URLS.store, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf(), Accept: 'application/json' },
                credentials: 'same-origin',
                body: fd,
            });
            const j = await r.json();
            if (!r.ok || j.success === false) throw new Error(j.message || 'Could not post that.');
            studio(false);
            window.toast?.('Reel posted.');
            loadRail();
        } catch (err) {
            $('rlBusy').classList.add('hidden');
            window.toast?.(err.message, 'error');
        }
    });

    loadRail();
})();
</script>
@endonce
