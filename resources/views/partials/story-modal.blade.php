{{-- A full screen story (2026-10-28, the owner: "show a modal box, a full screen modal explaining in an animated way";
     "the page that appears reloads twice, please fix. if it needs time to load you can show a loader").

     The shell the dashboard's two explainers share: How anee.io works (app.partials.how-card) and Anee Auto's
     How it works (auto.partials.card). Each pushes its own .stm box (see the markup below) and opens it with

        aneeStory.open(box, fromEl, init)

     The story itself is a page fetched once, the first time (box.dataset.url): Anee's face waits while it loads
     and its first pictures decode, then the story is drawn one time and kept, so nothing on it is drawn twice.
     It grows out of the button that opened it and folds back into it.

     A story is made of <section class="stm-scene" data-title="...">. Inside a scene:
       [data-in] (style --d: a delay)   rises into view when the scene comes into view, every time the story opens
       [data-count="62"]                counts up to its number then
       [data-bar] (style --v: 0 to 1)   a bar that grows to its share then
       [data-stm-next]                  scrolls to the next scene
     init(box) runs once, after the story is drawn; box fires 'stm:scene' (detail: the scene) as each one comes in.

     The markup a story box wears (app.partials.how-card has one): .stm (role dialog, data-url, hidden) holding
     .stm-bg (three <i>), .stm-bar (a picture, the title, .stm-x, .stm-prog <i>), .stm-body (.stm-load with a
     face and <b data-wait>, then .stm-host) and nav.stm-dots. --}}
@once
@push('head')
<style>
    html.stm-lock { overflow: hidden; }
    .stm { --e: cubic-bezier(.22,1,.36,1); --ink: #eef4e6; --mute: #b9caa8; --gold: #f5c518; --leaf: #a8cc7e;
        position: fixed; inset: 0; z-index: 270; display: flex; flex-direction: column; color: var(--ink); background: #0b1407; isolation: isolate; }
    .stm[hidden] { display: none; }
    /* Closing: nothing moves behind the fade, and nothing takes a tap. */
    .stm.is-closing { pointer-events: none; }
    .stm.is-closing .stm-bg i, .stm.is-closing .stm-load-face { animation-play-state: paused; }
    .stm *, .stm *::before, .stm *::after { box-sizing: border-box; }
    /* Slow light behind the story. */
    .stm-bg { position: absolute; inset: 0; z-index: -1; overflow: hidden; pointer-events: none; }
    .stm-bg i { position: absolute; width: 46rem; height: 46rem; border-radius: 999px; filter: blur(10px); opacity: .55; animation: stmDrift 26s ease-in-out infinite alternate; }
    .stm-bg i:nth-child(1) { left: -18rem; top: -20rem; background: radial-gradient(circle, rgb(106 163 58 / .45), transparent 62%); }
    .stm-bg i:nth-child(2) { right: -20rem; top: 30%; background: radial-gradient(circle, rgb(245 197 24 / .16), transparent 60%); animation-duration: 32s; }
    .stm-bg i:nth-child(3) { left: 20%; bottom: -28rem; background: radial-gradient(circle, rgb(45 80 22 / .7), transparent 62%); animation-duration: 38s; }
    .stm-bg::after { content: ''; position: absolute; inset: 0; background-image: radial-gradient(rgb(255 255 255 / .07) 1px, transparent 1.4px); background-size: 22px 22px; }
    @keyframes stmDrift { to { transform: translate(6rem, 4rem) scale(1.12); } }

    .stm-bar { position: relative; z-index: 5; flex: none; display: flex; align-items: center; gap: .65rem; padding: calc(.55rem + env(safe-area-inset-top, 0px)) .7rem .55rem 1rem;
        background: rgb(11 20 7 / .72); border-bottom: 1px solid rgb(255 255 255 / .07); -webkit-backdrop-filter: blur(10px); backdrop-filter: blur(10px); }
    .stm-bar img { flex: none; width: 2.1rem; height: 2.1rem; border-radius: 999px; object-fit: cover; background: rgb(255 255 255 / .1); }
    .stm-bar b { display: block; font-family: var(--font-heading); font-size: 1rem; line-height: 1.2; color: #fff; }
    .stm-bar small { display: block; font-size: .7rem; font-weight: 700; color: var(--leaf); }
    .stm-x { margin-left: auto; flex: none; width: 2.6rem; height: 2.6rem; border-radius: .9rem; display: grid; place-items: center; color: #fff; background: rgb(255 255 255 / .08);
        transition: background-color .28s var(--e), transform .28s var(--e); }
    .stm-x:hover { background: rgb(255 255 255 / .16); transform: rotate(90deg); }
    .stm-x svg { width: 1.2rem; height: 1.2rem; }
    .stm-prog { position: absolute; left: 0; right: 0; bottom: -1px; height: 2px; }
    .stm-prog i { display: block; height: 100%; width: 100%; transform-origin: left; transform: scaleX(var(--p, 0)); background: linear-gradient(90deg, var(--leaf), var(--gold)); transition: transform .2s linear; }

    .stm-body { position: relative; flex: 1; min-height: 0; overflow-y: auto; overscroll-behavior: contain; -webkit-overflow-scrolling: touch; scroll-behavior: smooth; }
    .stm-body:focus { outline: none; }
    /* Anee waits while the story loads. */
    .stm-load { position: absolute; inset: 0; z-index: 3; display: grid; place-content: center; justify-items: center; gap: .9rem; text-align: center; transition: opacity .35s var(--e), visibility .35s; }
    .stm.is-ready .stm-load { opacity: 0; visibility: hidden; }
    .stm-load-face { position: relative; width: 5.2rem; height: 5.2rem; border-radius: 999px; padding: .35rem; background: rgb(255 255 255 / .08); animation: stmBob 1.6s ease-in-out infinite; }
    .stm-load-face img { width: 100%; height: 100%; object-fit: contain; }
    .stm-load-face::before, .stm-load-face::after { content: ''; position: absolute; inset: -6px; border-radius: 999px; border: 2px solid rgb(245 197 24 / .55); animation: stmRing 1.6s var(--e) infinite; }
    .stm-load-face::after { animation-delay: .8s; }
    @keyframes stmRing { from { transform: scale(.85); opacity: 1; } to { transform: scale(1.45); opacity: 0; } }
    @keyframes stmBob { 50% { transform: translateY(-5px); } }
    .stm-load b { font-size: .9rem; font-weight: 800; color: var(--mute); }
    .stm-load button { margin-top: .2rem; padding: .55rem 1rem; border-radius: 999px; font-weight: 800; font-size: .85rem; color: #1d330d; background: var(--gold); }
    .stm-host { opacity: 0; transition: opacity .4s var(--e); }
    .stm.is-ready .stm-host { opacity: 1; }

    /* The parts of the story, as dots on the right (wide screens). */
    .stm-dots { position: absolute; right: .9rem; top: 50%; z-index: 4; display: none; flex-direction: column; gap: .55rem; transform: translateY(-50%); }
    @media (min-width: 1024px) { .stm-dots { display: flex; } }
    .stm-dots button { position: relative; width: .7rem; height: .7rem; border-radius: 999px; background: rgb(255 255 255 / .22); transition: background-color .28s var(--e), transform .28s var(--e); }
    .stm-dots button:hover, .stm-dots button.is-on { background: var(--gold); transform: scale(1.25); }
    .stm-dots button span { position: absolute; right: 1.3rem; top: 50%; transform: translateY(-50%); white-space: nowrap; padding: .2rem .55rem; border-radius: 999px; font-size: .7rem; font-weight: 800;
        color: #1d330d; background: var(--gold); opacity: 0; pointer-events: none; transition: opacity .2s var(--e); }
    .stm-dots button:hover span, .stm-dots button:focus-visible span { opacity: 1; }

    /* A scene: a screen of the story. */
    /* A scene is as tall as what it holds on a phone and a tablet (2026-10-28, the owner: "in mobile sections has like
       really huge spacing from each other"); a whole screen from 1024 px, where it sits in the middle of it. */
    .stm-scene { position: relative; display: grid; align-content: center; padding: 2.4rem 1rem; }
    @media (min-width: 640px) { .stm-scene { padding: 3rem 1.6rem; } }
    @media (min-width: 1024px) { .stm-scene { min-height: calc(100dvh - 3.6rem); padding: 3.5rem 4rem; } }
    /* Between two scenes, a thin line of light that draws itself out from the middle, a spark at its centre. */
    .stm-scene + .stm-scene::before { content: ''; position: absolute; top: 0; left: 50%; width: min(72%, 30rem); height: 1px; transform: translateX(-50%) scaleX(0);
        background: linear-gradient(90deg, transparent, rgb(168 204 126 / .65) 25%, rgb(245 197 24 / .85) 50%, rgb(168 204 126 / .65) 75%, transparent); transition: transform 1.2s cubic-bezier(.22,1,.36,1); }
    .stm-scene + .stm-scene::after { content: ''; position: absolute; top: -3px; left: 50%; width: 7px; height: 7px; margin-left: -3.5px; border-radius: 999px; background: #f5c518;
        box-shadow: 0 0 10px 2px rgb(245 197 24 / .7); opacity: 0; transition: opacity .5s .5s; }
    .stm-scene.is-in + .stm-scene::before, .stm-scene + .stm-scene.is-in::before { transform: translateX(-50%) scaleX(1); }
    .stm-scene.is-in + .stm-scene::after, .stm-scene + .stm-scene.is-in::after { opacity: 1; animation: stmSpark 2.6s ease-in-out 1.2s infinite; }
    @keyframes stmSpark { 50% { transform: scale(1.6); opacity: .55; } }
    .stm-wrap { width: 100%; max-width: 68rem; margin: 0 auto; }
    .stm-kick { display: inline-flex; align-items: center; gap: .45rem; font-size: .7rem; font-weight: 900; letter-spacing: .14em; text-transform: uppercase; color: var(--gold); }
    .stm-h { margin-top: .45rem; font-family: var(--font-heading); font-weight: 800; font-size: clamp(1.55rem, 4.6vw, 2.6rem); line-height: 1.24; letter-spacing: -.01em; color: #fff; }
    .stm-h em { font-style: normal; color: var(--gold); }
    .stm-h2 { margin-top: .4rem; font-family: var(--font-heading); font-weight: 800; font-size: clamp(1.3rem, 3.6vw, 2rem); line-height: 1.3; color: #fff; }
    .stm-p { margin-top: .7rem; max-width: 40rem; font-size: clamp(.92rem, 2.2vw, 1.05rem); line-height: 1.6; color: var(--mute); }
    .stm-next { display: inline-flex; align-items: center; gap: .45rem; margin-top: 1.4rem; padding: .75rem 1.2rem; border-radius: 999px; font-weight: 800; font-size: .92rem;
        color: #1d330d; background: var(--gold); box-shadow: 0 14px 30px -16px rgb(245 197 24 / .9); transition: transform .28s var(--e); }
    .stm-next:hover { transform: translateY(-2px); }
    .stm-next svg { width: 1rem; height: 1rem; animation: stmNudge 1.8s ease-in-out infinite; }
    @keyframes stmNudge { 50% { transform: translateY(3px); } }
    .stm-fine { margin-top: .8rem; font-size: .72rem; line-height: 1.5; color: rgb(185 202 168 / .75); }

    /* What rises in: drawn once, shown each time its scene comes in. */
    .stm [data-in] { opacity: 0; transform: translateY(18px); transition: opacity .7s var(--e) var(--d, 0ms), transform .7s var(--e) var(--d, 0ms); }
    .stm .stm-scene.is-in [data-in] { opacity: 1; transform: none; }
    .stm [data-bar] { transform-origin: left; transform: scaleX(0); transition: transform 1.1s var(--e) var(--d, 0ms); }
    .stm .stm-scene.is-in [data-bar] { transform: scaleX(var(--v, 1)); }

    @media (prefers-reduced-motion: reduce) {
        .stm-bg i, .stm-load-face, .stm-load-face::before, .stm-load-face::after, .stm-next svg { animation: none !important; }
        .stm [data-in], .stm [data-bar], .stm-host, .stm-load, .stm-prog i, .stm-scene::before, .stm-scene::after { transition: none !important; animation: none !important; }
        .stm-scene + .stm-scene::before { transform: translateX(-50%) scaleX(1); } .stm-scene + .stm-scene::after { opacity: 1; }
        .stm [data-in] { opacity: 1; transform: none; }
        .stm [data-bar] { transform: scaleX(var(--v, 1)); }
        .stm-body { scroll-behavior: auto; }
    }
</style>
@endpush

@push('scripts')
<script>
(() => {
    if (window.aneeStory) return;
    const still = () => window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const EASE = 'cubic-bezier(.22,1,.36,1)';
    const fromBox = (el) => {
        if (!el || !el.getBoundingClientRect) return 'inset(45% 45% 45% 45% round 1.25rem)';
        const r = el.getBoundingClientRect();
        return `inset(${Math.max(0, r.top)}px ${Math.max(0, innerWidth - r.right)}px ${Math.max(0, innerHeight - r.bottom)}px ${Math.max(0, r.left)}px round 1.25rem)`;
    };
    const count = (el) => {
        const to = Number(el.dataset.count) || 0, fmt = (v) => Math.round(v).toLocaleString('en-US');
        if (still() || to < 2) { el.textContent = fmt(to); return; }
        const t0 = performance.now(), dur = Math.min(1600, 700 + to * 2);
        const step = (t) => { const k = Math.min(1, (t - t0) / dur); el.textContent = fmt(to * (1 - Math.pow(1 - k, 3))); if (k < 1) requestAnimationFrame(step); };
        el.textContent = '0';
        requestAnimationFrame(step);
    };
    const state = new WeakMap();

    function wire(box) {
        const body = box.querySelector('.stm-body'), scenes = [...box.querySelectorAll('.stm-scene')];
        const dots = box.querySelector('.stm-dots'), prog = box.querySelector('.stm-prog i');
        if (dots) {
            dots.innerHTML = scenes.map((s, i) => `<button type="button" data-stm-go="${i}" aria-label="${(s.dataset.title || 'Part ' + (i + 1)).replace(/"/g, '&quot;')}"><span>${(s.dataset.title || '').replace(/</g, '&lt;')}</span></button>`).join('');
            dots.addEventListener('click', (e) => { const b = e.target.closest('[data-stm-go]'); if (b) scenes[Number(b.dataset.stmGo)].scrollIntoView({ block: 'start' }); });
        }
        const io = new IntersectionObserver((list) => list.forEach((en) => {
            if (!en.isIntersecting) return;
            const s = en.target;
            if (!s.classList.contains('is-in')) {
                s.classList.add('is-in');
                s.querySelectorAll('[data-count]').forEach(count);
                box.dispatchEvent(new CustomEvent('stm:scene', { detail: s }));
            }
            if (dots) dots.querySelectorAll('button').forEach((b, i) => b.classList.toggle('is-on', scenes[i] === s));
        }), { root: body, threshold: 0.28 });
        scenes.forEach((s) => io.observe(s));
        body.addEventListener('scroll', () => {
            if (!prog) return;
            const max = body.scrollHeight - body.clientHeight;
            prog.style.setProperty('--p', max > 0 ? Math.min(1, body.scrollTop / max).toFixed(3) : 0);
        }, { passive: true });
        box.addEventListener('click', (e) => {
            const n = e.target.closest('[data-stm-next]');
            if (!n) return;
            const s = n.closest('.stm-scene'), i = scenes.indexOf(s);
            if (scenes[i + 1]) scenes[i + 1].scrollIntoView({ block: 'start' });
        });
        state.set(box, { scenes });
    }

    async function load(box, init) {
        const host = box.querySelector('.stm-host'), loadEl = box.querySelector('.stm-load');
        const msg = loadEl.querySelector('b');
        msg.textContent = msg.dataset.wait || 'Getting it ready';
        loadEl.querySelector('button')?.remove();
        try {
            const res = await fetch(box.dataset.url, { headers: { Accept: 'text/html', 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' });
            if (!res.ok) throw new Error(res.status);
            host.innerHTML = await res.text();
            // The first screen's pictures decode before the story shows, so nothing pops in after it.
            const first = [...host.querySelectorAll('.stm-scene:first-child img')];
            await Promise.race([Promise.all(first.map((i) => (i.decode ? i.decode().catch(() => {}) : null))), new Promise((r) => setTimeout(r, 2500))]);
            wire(box);
            box.dataset.ready = '1';
            if (init) { try { init(box); } catch (e) { console.error(e); } }
        } catch (e) {
            msg.textContent = 'It did not load. Check the connection and try again.';
            const b = document.createElement('button');
            b.type = 'button'; b.textContent = 'Try again';
            b.addEventListener('click', () => load(box, init));
            loadEl.appendChild(b);
            return false;
        }
        requestAnimationFrame(() => box.classList.add('is-ready'));
        return true;
    }

    let busy = false, opener = null;
    function open(box, from, init) {
        if (!box || busy || !box.hidden) return;
        busy = true;
        opener = from || document.activeElement;
        box.__from = from;
        box.hidden = false;
        document.documentElement.classList.add('stm-lock');
        const body = box.querySelector('.stm-body');
        body.scrollTop = 0;
        const fresh = box.dataset.ready !== '1';
        if (fresh) box.classList.remove('is-ready');
        const done = () => {
            busy = false;
            box.querySelector('.stm-x')?.focus({ preventScroll: true });
            if (fresh) load(box, init);
        };
        if (still() || typeof box.animate !== 'function') { done(); return; }
        box.animate([{ clipPath: fromBox(from) }, { clipPath: 'inset(0px 0px 0px 0px round 0px)' }], { duration: 560, easing: EASE }).onfinish = done;
    }
    function close(box) {
        if (!box || busy || box.hidden) return;
        busy = true;
        const done = () => {
            box.hidden = true;
            document.documentElement.classList.remove('stm-lock');
            // Each opening shows the story rise in again.
            (state.get(box)?.scenes || []).forEach((s) => s.classList.remove('is-in'));
            busy = false;
            opener?.focus?.({ preventScroll: true });
            box.dispatchEvent(new CustomEvent('stm:closed'));
        };
        if (still() || typeof box.animate !== 'function') { done(); return; }
        /* A fade and a slight settle (2026-10-28, the owner: "when you close that window, the animation is glitchy").
           The clip back into the button repainted the whole blurred story every frame, and the frame between its end
           and the hide showed the story whole again. Opacity and scale run on the compositor; the last frame is held
           (fill) until the box is hidden. */
        box.classList.add('is-closing');
        const a = box.animate([{ opacity: 1, transform: 'scale(1)' }, { opacity: 0, transform: 'scale(.97)' }], { duration: 300, easing: EASE, fill: 'forwards' });
        a.onfinish = () => { done(); a.cancel(); box.classList.remove('is-closing'); };
    }
    document.addEventListener('click', (e) => {
        const x = e.target.closest('.stm .stm-x');
        if (x) close(x.closest('.stm'));
    });
    document.addEventListener('keydown', (e) => {
        if (e.key !== 'Escape') return;
        const box = [...document.querySelectorAll('.stm')].find((b) => !b.hidden);
        if (box) { e.preventDefault(); close(box); }
    });
    window.aneeStory = { open, close };
})();
</script>
@endpush
@endonce
