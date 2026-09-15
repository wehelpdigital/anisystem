{{-- THE TUTORIAL CARD.

     A short video about the screen you just opened, in a card over that
     screen, with two ways out: Close, which is for now -- it is back the
     next time the page is opened -- and "don't show this again", which is
     remembered against the account and followed onto every device.

     One card for the whole app, painted with whichever screen's words and
     clip a page hands it. Pages do not build modals; they include
     partials.tutorial-offer with the keys they may show and the one to show
     now, and this decides whether that one is due. Loaded from the layout
     for anyone signed in; it draws nothing until a page asks.

     The clip does not autoplay. A tutorial with the sound off is a slideshow,
     and a browser will not start one with the sound on -- so the poster waits
     under a play button, and the first tap is the person's. --}}
@auth
<div class="tutv-wrap" id="tutvModal" hidden aria-hidden="true">
    <div class="tutv-backdrop" data-tutv-close></div>
    <div class="tutv-card" role="dialog" aria-modal="true" aria-labelledby="tutvTitle" aria-describedby="tutvBlurb" tabindex="-1">
        <button type="button" class="tutv-x" data-tutv-close aria-label="Close">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M18 6L6 18"/></svg>
        </button>

        {{-- data-cv-skip: the chat clip enhancer (chat-media-js) dresses every
             video it can see with its own full-screen button and a wrapper
             that sets the height -- which put a second control under our X
             and cropped the bottom of a portrait clip. This one is not a
             clip in a bubble. --}}
        <div class="tutv-screen" data-cv-skip>
            {{-- No browser controls until it is playing: a poster with our one
                 play button on it reads as one thing to press, and a second
                 play button in a bar under it read as two. disablepictureinpicture
                 because Chrome's own PiP toggle lands exactly under our ✕. --}}
            <video id="tutvVideo" playsinline preload="metadata" controlslist="nodownload noremoteplayback" disablepictureinpicture></video>
            {{-- The big play sits over the poster until the clip runs, then
                 gets out of the way of the browser's own controls. --}}
            <button type="button" class="tutv-play" id="tutvPlay" aria-label="Play the tutorial">
                <span class="tutv-play-ring" aria-hidden="true"></span>
                <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M8 5.14v13.72L19 12 8 5.14z"/></svg>
            </button>
        </div>

        <div class="tutv-body">
            <p class="tutv-kicker">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10l4.55-2.28A1 1 0 0121 8.62v6.76a1 1 0 01-1.45.9L15 14M5 7h8a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2V9a2 2 0 012-2z"/></svg>
                Quick tutorial
            </p>
            <h3 class="tutv-title" id="tutvTitle"></h3>
            <p class="tutv-blurb" id="tutvBlurb"></p>
            <div class="tutv-acts">
                <button type="button" class="tutv-never" data-tutv-never>Don’t show this again</button>
                <button type="button" class="tutv-close" data-tutv-close>Close</button>
            </div>
        </div>
    </div>
</div>

<style>
    /* Under the review prompt (245) and above every sheet (200).

       THE MOTION IS TRANSITIONS DRIVEN BY ONE CLASS, NOT KEYFRAMES.
       The first version ran a keyframe on arrival and the same keyframe
       reversed on the way out -- and changing the direction of an animation
       whose name has not changed does not restart it: an animation already
       at its end simply jumped to the reversed end state. Every close was a
       cut. With `.is-open` toggling the same properties both ways, the card
       leaves along the path it arrived on, and nothing has to restart. */
    .tutv-wrap { position: fixed; inset: 0; z-index: 240; display: flex; align-items: flex-end; justify-content: center; }
    .tutv-wrap[hidden] { display: none; }
    .tutv-backdrop { position: absolute; inset: 0; background: rgb(6 12 4 / .62); touch-action: none;
        backdrop-filter: blur(3px); -webkit-backdrop-filter: blur(3px);
        opacity: 0; transition: opacity .34s cubic-bezier(.22,1,.36,1); }
    .tutv-wrap.is-open .tutv-backdrop { opacity: 1; }

    /* A sheet from the bottom on a phone, a card in the middle with room.
       It arrives from below with a little overshoot and settles; on the way
       out it goes faster and eases in, the way a thing you dismiss should. */
    .tutv-card { position: relative; width: min(34rem, 100%); max-height: calc(100dvh - 1rem); overflow: auto;
        border-radius: 1.35rem 1.35rem 0 0; background: var(--color-white, #fff); color: var(--color-gray-900, #111827);
        box-shadow: 0 -24px 70px -20px rgb(0 0 0 / .6); outline: none;
        padding-bottom: env(safe-area-inset-bottom, 0px);
        opacity: 0; transform: translateY(100%);
        transition: transform .52s cubic-bezier(.22,1,.36,1), opacity .3s ease; }
    .tutv-wrap.is-open .tutv-card { opacity: 1; transform: none; }
    .tutv-wrap.is-closing .tutv-card { transition: transform .3s cubic-bezier(.4,0,1,1), opacity .22s ease .06s; }
    .tutv-wrap.is-closing .tutv-backdrop { transition-duration: .26s; }
    @media (min-width: 640px) {
        .tutv-wrap { align-items: center; padding: 1rem; }
        .tutv-card { border-radius: 1.35rem; max-height: calc(100dvh - 2rem); padding-bottom: 0;
            box-shadow: 0 30px 80px -24px rgb(0 0 0 / .65);
            transform: translateY(26px) scale(.95);
            transition: transform .46s cubic-bezier(.22,1,.36,1), opacity .3s ease; }
        .tutv-wrap.is-closing .tutv-card { transform: translateY(14px) scale(.97);
            transition: transform .26s cubic-bezier(.4,0,1,1), opacity .2s ease .04s; }
    }

    /* The words arrive a beat after the card, one line after another, so the
       card is seen to open rather than to appear already full. */
    .tutv-body > * { opacity: 0; transform: translateY(10px);
        transition: opacity .36s ease, transform .44s cubic-bezier(.22,1,.36,1); }
    .tutv-wrap.is-open .tutv-body > * { opacity: 1; transform: none; }
    .tutv-wrap.is-open .tutv-body > :nth-child(1) { transition-delay: .16s; }
    .tutv-wrap.is-open .tutv-body > :nth-child(2) { transition-delay: .22s; }
    .tutv-wrap.is-open .tutv-body > :nth-child(3) { transition-delay: .28s; }
    .tutv-wrap.is-open .tutv-body > :nth-child(4) { transition-delay: .36s; }
    .tutv-wrap.is-closing .tutv-body > * { transition: opacity .16s ease; }
    /* And the picture fades up under them once its poster is actually there. */
    .tutv-screen video, .tutv-screen iframe { opacity: 0; transition: opacity .4s ease .1s; }
    .tutv-wrap.is-open .tutv-screen video, .tutv-wrap.is-open .tutv-screen iframe { opacity: 1; }

    /* Above the body, which is positioned too and comes later in the DOM. */
    .tutv-x { position: absolute; top: .65rem; right: .65rem; z-index: 3; width: 2.1rem; height: 2.1rem; border-radius: 999px;
        display: inline-flex; align-items: center; justify-content: center; cursor: pointer;
        color: #fff; background: rgb(0 0 0 / .38); transition: background .2s ease, transform .28s cubic-bezier(.22,1,.36,1); }
    .tutv-x:hover { background: rgb(0 0 0 / .55); transform: scale(1.06); }
    .tutv-x svg { width: 1rem; height: 1rem; }

    /* THE SCREEN TAKES THE SHAPE OF THE CLIP.
       A landscape clip fills a 16:9 band edge to edge. A portrait clip --
       every tutorial recorded on a phone -- gets a phone-shaped screen
       instead: height-capped so the title and the buttons stay on screen
       under it without scrolling, the clip centred, and a blurred, dimmed
       copy of its own poster filling the sides rather than two black bars. */
    .tutv-screen { position: relative; aspect-ratio: 16 / 9; background: #0b1208; overflow: hidden; }
    .tutv-screen::before { content: ''; position: absolute; inset: -16px;
        background: var(--tutv-poster, none) center / cover no-repeat;
        filter: blur(16px) brightness(.5) saturate(1.1); transform: scale(1.08); }
    .tutv-screen video, .tutv-screen iframe { position: relative; display: block; width: 100%; height: 100%; border: 0; object-fit: cover; }
    .tutv-screen video[hidden] { display: none; }
    /* Until the clip is playing, the browser paints nothing of its own on
       it: Chrome on a phone draws a fullscreen toggle in the top corner of
       a video that has no controls yet, right under our X. Once controls
       are switched on (first play) the browser's bar is welcome. */
    .tutv-screen video:not([controls])::-webkit-media-controls,
    .tutv-screen video:not([controls])::-webkit-media-controls-enclosure,
    .tutv-screen video:not([controls])::-webkit-media-controls-overlay-enclosure { display: none !important; }
    .tutv-screen[data-shape="portrait"] { aspect-ratio: auto; height: min(62dvh, 40rem); }
    .tutv-screen[data-shape="portrait"] video { object-fit: contain; }
    /* ON A PHONE THE SHEET IS THE WHOLE SCREEN and the clip gets every pixel
       of height the words and the buttons do not need. A height-capped box
       inside a bottom sheet came out wider than it was tall -- 390 by 456 --
       with the clip a narrow strip down the middle, which is not what a
       portrait tutorial looks like on the phone it was recorded on. */
    @media (max-width: 639px) {
        .tutv-wrap.is-portrait .tutv-card { height: 100dvh; max-height: 100dvh; border-radius: 0;
            display: flex; flex-direction: column; overflow: hidden; }
        .tutv-wrap.is-portrait .tutv-screen[data-shape="portrait"] { flex: 1 1 auto; min-height: 0; height: auto; }
        .tutv-wrap.is-portrait .tutv-body { flex: none; padding: .85rem 1.1rem calc(.9rem + env(safe-area-inset-bottom, 0px)); }
        .tutv-wrap.is-portrait .tutv-title { font-size: 1.15rem; }
        .tutv-wrap.is-portrait .tutv-blurb { font-size: .86rem; line-height: 1.45; margin-top: .35rem; }
        .tutv-wrap.is-portrait .tutv-acts { margin-top: .8rem; gap: .4rem; }
        .tutv-wrap.is-portrait .tutv-close { min-height: 2.8rem; padding: .65rem 1.25rem; }
        .tutv-wrap.is-portrait .tutv-never { min-height: 2.3rem; padding: .4rem 1rem; }
        /* The X sits over the clip's top corner, clear of the phone's notch. */
        .tutv-wrap.is-portrait .tutv-x { top: calc(.65rem + env(safe-area-inset-top, 0px)); }
    }
    /* A portrait player from YouTube is a Short: 9:16 in the middle of the screen. */
    .tutv-screen[data-shape="portrait"] iframe { width: auto; aspect-ratio: 9 / 16; margin: 0 auto; }
    .tutv-play { position: absolute; inset: 0; z-index: 2; display: flex; align-items: center; justify-content: center; cursor: pointer;
        background: linear-gradient(to top, rgb(0 0 0 / .35), rgb(0 0 0 / .05)); color: #1a1a1a;
        transition: opacity .28s cubic-bezier(.22,1,.36,1); }
    .tutv-play[hidden] { display: none; }
    .tutv-play svg { position: relative; width: 4.4rem; height: 4.4rem; padding: 1.2rem 1.05rem 1.2rem 1.35rem; border-radius: 999px;
        background: var(--color-accent-500, #f5c518); box-shadow: 0 14px 34px -8px rgb(0 0 0 / .55), 0 0 0 6px rgb(255 255 255 / .18);
        transform: scale(.6); opacity: 0;
        transition: transform .5s cubic-bezier(.22,1,.36,1) .28s, opacity .3s ease .28s; }
    .tutv-wrap.is-open .tutv-play svg { transform: scale(1); opacity: 1; }
    .tutv-wrap.is-open .tutv-play:hover svg { transform: scale(1.07); transition-delay: 0s; }
    /* One ring breathing out from the button: "this is the thing to press". */
    .tutv-play-ring { position: absolute; width: 4.4rem; height: 4.4rem; border-radius: 999px; opacity: 0;
        border: 2px solid rgb(245 197 24 / .75); }
    .tutv-wrap.is-open .tutv-play-ring { animation: tutvRing 2.2s cubic-bezier(.22,1,.36,1) .7s infinite; }
    @keyframes tutvRing { 0% { transform: scale(1); opacity: .9; } 100% { transform: scale(1.9); opacity: 0; } }
    /* The X is the last thing in, so nothing invites a dismissal before the
       card has even said what it is. */
    .tutv-x { opacity: 0; transition: opacity .3s ease .4s, background .2s ease, transform .28s cubic-bezier(.22,1,.36,1); }
    .tutv-wrap.is-open .tutv-x { opacity: 1; }

    .tutv-body { position: relative; padding: 1.1rem 1.25rem 1.2rem; }
    .tutv-kicker { display: inline-flex; align-items: center; gap: .4rem; font-size: .68rem; font-weight: 800;
        letter-spacing: .08em; text-transform: uppercase; color: var(--color-brand-700, #3d6823); }
    .tutv-kicker svg { width: .95rem; height: .95rem; }
    .tutv-title { font-family: var(--font-heading, inherit); font-size: 1.28rem; line-height: 1.2; font-weight: 800; margin-top: .3rem; }
    .tutv-blurb { font-size: .9rem; line-height: 1.55; color: var(--color-gray-600, #4b5563); margin-top: .45rem; }

    /* Close is the loud one -- the safe answer. Never-again stays quiet, so a
       hurried tap does not switch the tutorials off for good. Stacked on a
       phone, a row with room. */
    .tutv-acts { display: flex; flex-direction: column-reverse; gap: .55rem; margin-top: 1.05rem; }
    .tutv-close { width: 100%; min-height: 3rem; padding: .8rem 1.5rem; border: 0; border-radius: 1rem; cursor: pointer;
        font: inherit; font-size: .95rem; font-weight: 800; color: #fff;
        background: linear-gradient(115deg, #3d6823 0%, #6b9f3d 35%, #4a7c2a 60%, #86b556 85%, #3d6823 100%);
        background-size: 260% 100%; background-position: 0% 50%;
        box-shadow: 0 10px 26px rgb(74 124 42 / .28);
        animation: tutvSweep 7s ease-in-out infinite;
        transition: transform .28s cubic-bezier(.22,1,.36,1), box-shadow .28s cubic-bezier(.22,1,.36,1); }
    .tutv-close:hover, .tutv-close:focus-visible { transform: translateY(-2px); box-shadow: 0 14px 32px rgb(74 124 42 / .36); outline: none; }
    .tutv-close:active { transform: translateY(0) scale(.985); }
    @keyframes tutvSweep { 0%, 100% { background-position: 0% 50%; } 50% { background-position: 100% 50%; } }
    .tutv-never { width: 100%; min-height: 2.6rem; padding: .55rem 1rem; border-radius: .85rem; cursor: pointer;
        font: inherit; font-size: .84rem; font-weight: 700; color: var(--color-gray-500, #6b7280); background: transparent;
        transition: background .2s ease, color .2s ease; }
    .tutv-never:hover { background: var(--color-gray-100, #f3f4f6); color: var(--color-gray-700, #374151); }
    @media (min-width: 640px) {
        .tutv-acts { flex-direction: row; align-items: center; justify-content: flex-end; }
        .tutv-close, .tutv-never { width: auto; }
        .tutv-never { margin-right: auto; }
    }

    html.dark .tutv-card { background: #151b12; color: #e8efe1; }
    html.dark .tutv-blurb { color: #a8b8a0; }
    html.dark .tutv-kicker { color: #a8cc7e; }
    html.dark .tutv-never { color: #93a58b; }
    html.dark .tutv-never:hover { background: #1c2416; color: #d8ecc4; }

    @media (prefers-reduced-motion: reduce) {
        .tutv-play-ring, .tutv-close { animation: none; }
        .tutv-wrap *, .tutv-wrap .tutv-body > *, .tutv-wrap .tutv-play svg { transition: none !important; }
        .tutv-card { transform: none; }
    }
</style>

<script>
(() => {
    const wrap = document.getElementById('tutvModal');
    if (!wrap) return;
    const card = wrap.querySelector('.tutv-card');
    const screen = wrap.querySelector('.tutv-screen');
    const video = document.getElementById('tutvVideo');
    const play = document.getElementById('tutvPlay');
    const CSRF = document.querySelector('meta[name=csrf-token]')?.content || '';
    const DISMISS_URL = @json(route('tutorial.dismiss'));
    /* Long enough for the screen to have painted under it, short enough
       that it is still plainly about the screen you just opened. Counted
       from the page being fully loaded, not from the script running: a card
       sliding up over images still streaming in reads as one more thing
       loading, not as an arrival. */
    const DELAY = 650;
    const LOAD_CAP = 3000;   // but a slow image somewhere is not a reason to wait forever
    const NEVER = (k) => 'anee-tutv-never:' + k;
    /* CLOSE MEANS FOR NOW. Not sessionStorage: a tab on a phone lives for
       days, so "closed for the sitting" turned into "closed for good" on
       every screen the card had been closed on once, and nobody had asked
       for that. A set that lives as long as this page load does the one
       job wanted of it -- inside the Activities shell, walking back to a
       room you already closed the card in does not raise it again -- and
       the next time the page is opened, the card is back. */
    const closedNow = new Set();

    let current = null;      // the key on screen, or null
    let lastFocus = null;
    let closing = 0;         // which close is in flight; a new show() voids it

    const data = () => (window.ANEE_TUTORIALS ||= { items: {}, seen: [] });
    const ls = { get: (k) => { try { return localStorage.getItem(k); } catch (_) { return null; } },
                 set: (k, v) => { try { localStorage.setItem(k, v); } catch (_) { /* private mode */ } } };

    /* WHICH CLIP: the phone's, on a phone. The card is a bottom sheet under
       640px, and that is the width at which a tutorial recorded on a phone
       is the one being watched on one. Either shape stands in for a missing
       other, so a screen with one recording shows it everywhere. */
    /* A phone is a narrow screen -- or any touch screen held upright, which
       covers a big phone, a phone with the text zoomed out, and a tablet. */
    const touch = () => matchMedia('(pointer: coarse)').matches || matchMedia('(any-pointer: coarse)').matches || (navigator.maxTouchPoints || 0) > 0;
    const phoneWide = () => matchMedia('(max-width: 639px)').matches
        || (touch() && matchMedia('(orientation: portrait)').matches);
    function pick(item) {
        const hasP = !!(item.portrait || item.youtubePortrait);
        const hasL = !!(item.video || item.youtube);
        const portrait = hasP && (phoneWide() || !hasL);
        return portrait
            ? { shape: 'portrait', src: item.portrait, poster: item.portraitPoster, youtube: item.youtubePortrait }
            : { shape: 'landscape', src: item.video, poster: item.poster, youtube: item.youtube };
    }

    /* Told to stay closed -- by the server for this account, or by this
       browser when the server could not be reached at the time. */
    const neverAgain = (key) => data().seen.includes(key) || ls.get(NEVER(key)) === '1';
    /* Never over something else that is already asking for attention. */
    const blocked = () => current !== null
        || !!document.querySelector('.sheet.is-open, .note-lb.is-open, .draw-modal.show, #reviewPrompt:not([hidden])');

    /* Everything the card needs before it moves: the page settled, and the
       poster in the browser's cache, so the screen fades up with the picture
       on it rather than going black and then flashing to the picture. */
    const pageSettled = () => new Promise((res) => {
        if (document.readyState === 'complete') { res(); return; }
        const t = setTimeout(res, LOAD_CAP);
        window.addEventListener('load', () => { clearTimeout(t); res(); }, { once: true });
    });
    const posterReady = (url) => new Promise((res) => {
        if (!url) { res(); return; }
        const img = new Image();
        const t = setTimeout(res, 1500);
        img.onload = img.onerror = () => { clearTimeout(t); res(); };
        img.src = url;
    });

    /** A page says which screen is on; this decides whether its card is due. */
    function offer(key) {
        const item = data().items[key];
        if (!item || neverAgain(key) || closedNow.has(key)) return;
        const mine = ++offerSeq;
        Promise.all([
            pageSettled().then(() => new Promise((r) => setTimeout(r, DELAY))),
            posterReady((() => { const c = pick(item); return c.youtube ? '' : c.poster; })()),
        ]).then(() => {
            if (mine !== offerSeq) return;   // a later room was asked for meanwhile
            if (!blocked()) show(key);
        });
    }
    let offerSeq = 0;

    function show(key) {
        const item = data().items[key];
        if (!item) return;
        current = key;
        closing = 0;         // a close still animating must not hide THIS card
        lastFocus = document.activeElement;

        document.getElementById('tutvTitle').textContent = item.title;
        document.getElementById('tutvBlurb').textContent = item.blurb;
        /* A YouTube id gets YouTube's own player in the same frame; a file
           gets ours. The library at /app/tutorials is YouTube-based, so the
           finished recordings are likely to arrive that way. */
        const clip = pick(item);
        screen.dataset.shape = clip.shape;
        wrap.classList.toggle('is-portrait', clip.shape === 'portrait');
        screen.style.setProperty('--tutv-poster', clip.poster ? `url("${clip.poster}")` : 'none');
        screen.querySelector('iframe')?.remove();
        if (clip.youtube) {
            video.hidden = true; play.hidden = true;
            const f = document.createElement('iframe');
            f.src = 'https://www.youtube-nocookie.com/embed/' + encodeURIComponent(clip.youtube) + '?rel=0&modestbranding=1&playsinline=1';
            f.title = item.title;
            f.allow = 'accelerometer; encrypted-media; gyroscope; picture-in-picture; fullscreen';
            f.setAttribute('allowfullscreen', '');
            f.setAttribute('loading', 'lazy');
            screen.appendChild(f);
        } else {
            video.hidden = false;
            video.poster = clip.poster || '';
            video.src = clip.src;
            video.load();
            play.hidden = false;
        }

        wrap.classList.remove('is-closing', 'is-open');
        wrap.hidden = false;
        wrap.setAttribute('aria-hidden', 'false');
        document.documentElement.classList.add('modal-open');
        window.registerOverlay?.('tutorial', () => close());
        card.scrollTop = 0;
        /* Painted once in its starting place before it is told to move:
           display:none to block and the class in the same frame would let
           the browser skip straight to the end state, and the card would
           simply be there. The read below forces that first layout. */
        void wrap.offsetWidth;
        requestAnimationFrame(() => {
            if (current !== key) return;
            wrap.classList.add('is-open');
            card.focus({ preventScroll: true });
        });
    }

    /* Out the way it came in. The video is stopped and unhooked so it does
       not go on buffering behind a card that is no longer there. */
    function close() {
        if (current === null) return;
        const key = current;
        current = null;
        video.pause();
        video.controls = false;
        video.removeAttribute('src');
        video.load();
        screen.querySelector('iframe')?.remove();
        window.unregisterOverlay?.('tutorial');
        document.documentElement.classList.remove('modal-open');
        closedNow.add(key);

        /* Two things end this close -- the card's transition, or a timer in
           case that never comes -- and whichever is second must do nothing.
           An earlier version left a once-listener armed when the timer won,
           and the NEXT card's arrival fired it: Lots was written into the
           card and hidden in the same breath. Hence the token, and hence
           checking the event came from the card itself and not a child
           whose own transition happened to end. */
        const token = ++closing;
        const done = () => {
            if (token !== closing) return;
            closing = 0;
            card.removeEventListener('transitionend', onEnd);
            wrap.hidden = true;
            wrap.setAttribute('aria-hidden', 'true');
            wrap.classList.remove('is-closing');
            lastFocus?.focus?.({ preventScroll: true });
        };
        // The card's own transform arriving is the end of the exit.
        const onEnd = (e) => { if (e.target === card && e.propertyName === 'transform') done(); };
        wrap.classList.add('is-closing');
        wrap.classList.remove('is-open');
        const still = matchMedia('(prefers-reduced-motion: reduce)').matches;
        if (still) { done(); return; }
        card.addEventListener('transitionend', onEnd);
        setTimeout(done, 450);
    }

    /* "Don't show this again": remembered here first, so the card behaves
       the same whether or not the server is reachable, then sent up so the
       other devices learn it too. */
    function never() {
        const key = current;
        if (!key) return;
        ls.set(NEVER(key), '1');
        data().seen.push(key);
        const body = new FormData();
        body.append('key', key);
        fetch(DISMISS_URL, {
            method: 'POST', body, credentials: 'same-origin',
            headers: { 'X-CSRF-TOKEN': CSRF, Accept: 'application/json' },
        }).catch(() => {});
        close();
    }

    wrap.addEventListener('click', (e) => {
        if (e.target.closest('[data-tutv-never]')) { never(); return; }
        if (e.target.closest('[data-tutv-close]')) { close(); }
    });
    play.addEventListener('click', () => { video.play().catch(() => {}); });
    video.addEventListener('play', () => { play.hidden = true; video.controls = true; });
    video.addEventListener('pause', () => { if (!video.seeking && video.currentTime < video.duration - .1) play.hidden = false; });
    video.addEventListener('ended', () => { play.hidden = false; });
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && current !== null) close(); });

    window.aneeTutorial = { offer, close, showing: () => current };

    /* Pages may have asked before this script ran; answer them now, and
       take the queue over so anything pushed later is answered at once. */
    const queued = Array.isArray(window.aneeTutorialQueue) ? window.aneeTutorialQueue : [];
    window.aneeTutorialQueue = { push: (k) => offer(k) };
    queued.forEach(offer);
})();
</script>
@endauth
