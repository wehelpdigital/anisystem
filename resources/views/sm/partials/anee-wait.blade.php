{{-- ANEE, AT WORK — the wait every AI run wears.

     A full-screen veil with her face in a ring: her thinking, or searching
     with a magnifier (drawn at random), while the model works, and when the
     answer lands a crossfade to her pointing at a lightbulb, blowing a
     kiss or shouting yehey (drawn at random again), which plays out before
     the veil lifts and the result is shown. The clips are square crops of
     the emoji videos, silent, with no controls and nothing to tap: they are
     a face, not a player. Under the ring, what she is doing right now (the
     lines rotate), and the one warning that matters — stay here, because
     leaving loses the run and the credits it uses. The page is held still
     underneath, and the browser asks before the tab is closed.

     One partial, one API:

         window.aneeWait.show({ title, lines, sub })   -> nothing
         await window.aneeWait.done({ title, line })   -> resolves once the veil has lifted
         window.aneeWait.fail()                        -> lifts at once
         window.aneeWait.progress({ phase, try, beatAgo, ... }) -> moves the bar (the poll below calls it)
         await window.aneeWait.poll({ id, job, phases }) -> polls a job until ready; the bar, the
                                                     clock and the hang check ride along

     The bar is honest about what it knows: each phase the job reports owns
     a slice of the hundred, and inside a phase the number creeps with the
     time that phase usually takes, never reaching the slice's end until
     the next phase is heard. Under it, when the job was last heard from --
     so a farmer can tell a slow read from a dead one.

     Included once by any page that runs the model (@once inside). --}}
@once
<div class="aw-veil" id="aneeWaitVeil" hidden aria-live="polite" role="status">
    <div class="aw-card">
        <div class="aw-ring" aria-hidden="true">
            <video class="aw-vid is-on" id="aneeWaitThink" src="{{ asset('videos/anee/thinking.mp4') }}" poster="{{ asset('videos/anee/thinking.jpg') }}"
                   muted playsinline loop preload="auto" disablepictureinpicture disableremoteplayback tabindex="-1"></video>
            <video class="aw-vid" id="aneeWaitDone" muted playsinline preload="auto" disablepictureinpicture disableremoteplayback tabindex="-1"></video>
            <span class="aw-halo"></span>
        </div>
        <p class="aw-title" id="aneeWaitTitle">Anee is thinking…</p>
        <p class="aw-line" id="aneeWaitLine"></p>
        <div class="aw-prog" id="aneeWaitProg" hidden>
            <div class="aw-bar" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0" id="aneeWaitBarBox"><span id="aneeWaitBar"></span></div>
            <div class="aw-prog-row"><b id="aneeWaitPct">0%</b><span id="aneeWaitStep">Starting…</span><i id="aneeWaitClock">0:00</i></div>
            <p class="aw-check" id="aneeWaitCheck"></p>
        </div>
        <p class="aw-sub" id="aneeWaitSub"></p>
        <p class="aw-stay" id="aneeWaitStay">Please stay on this screen. Closing or leaving loses this run — and the credits it uses.</p>
    </div>
</div>
<style>
    .aw-veil { position: fixed; inset: 0; z-index: 320; display: flex; align-items: center; justify-content: center;
        padding: 1.25rem; background: rgb(246 249 242 / .96); backdrop-filter: blur(10px); -webkit-backdrop-filter: blur(10px);
        opacity: 0; transition: opacity .34s cubic-bezier(.22,1,.36,1); }
    .aw-veil[hidden] { display: none; }
    .aw-veil.is-on { opacity: 1; }
    .aw-card { width: min(26rem, 100%); text-align: center; transform: translateY(8px) scale(.98); opacity: 0;
        transition: transform .42s cubic-bezier(.22,1,.36,1), opacity .34s cubic-bezier(.22,1,.36,1); }
    .aw-veil.is-on .aw-card { transform: none; opacity: 1; }
    /* Her face in a ring. Both clips sit in the same circle; the one that
       is on shows, the other fades in over it when the answer lands. */
    .aw-ring { position: relative; width: 9.5rem; height: 9.5rem; margin: 0 auto 1.1rem; border-radius: 999px;
        overflow: hidden; background: #cfe3bd; box-shadow: 0 0 0 5px #fff, 0 18px 44px -18px rgb(40 70 15 / .55); }
    .aw-vid { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; display: block;
        opacity: 0; transition: opacity .7s cubic-bezier(.22,1,.36,1); pointer-events: none; }
    .aw-vid.is-on { opacity: 1; }
    .aw-vid::-webkit-media-controls, .aw-vid::-webkit-media-controls-enclosure { display: none !important; }
    /* A slow breathing halo behind the ring, so the wait reads as alive. */
    .aw-halo { position: absolute; inset: -6px; border-radius: 999px; pointer-events: none;
        box-shadow: 0 0 0 0 rgb(107 159 61 / .35); animation: awHalo 2.4s ease-in-out infinite; }
    @keyframes awHalo { 0%, 100% { box-shadow: 0 0 0 0 rgb(107 159 61 / .35); } 50% { box-shadow: 0 0 0 14px rgb(107 159 61 / 0); } }
    .aw-title { font-family: var(--font-heading); font-size: 1.15rem; font-weight: 800; color: var(--color-gray-900); }
    .aw-line { margin-top: .35rem; font-size: .9rem; font-weight: 700; color: var(--color-brand-800, #2f5219); min-height: 1.4em;
        transition: opacity .28s ease; }
    .aw-sub { margin-top: .35rem; font-size: .82rem; color: var(--color-gray-500); }
    /* The bar: a slice per phase, a creep inside it, the clock beside it. */
    .aw-prog { margin: .7rem auto 0; max-width: 22rem; }
    .aw-bar { height: .55rem; border-radius: 999px; background: rgb(107 159 61 / .18); overflow: hidden; }
    .aw-bar span { display: block; height: 100%; width: 0; border-radius: 999px;
        background: linear-gradient(90deg, #4a7c2a, #8fc96a, #4a7c2a); background-size: 220% 100%;
        animation: awTide 2.6s linear infinite; transition: width .9s cubic-bezier(.22,1,.36,1); }
    @keyframes awTide { from { background-position: 0% 50%; } to { background-position: 220% 50%; } }
    .aw-prog-row { display: flex; align-items: baseline; gap: .6rem; margin-top: .35rem; font-size: .74rem; color: var(--color-gray-500); }
    .aw-prog-row b { font-size: .9rem; color: var(--color-gray-900); font-variant-numeric: tabular-nums; }
    .aw-prog-row span { flex: 1 1 auto; min-width: 0; text-align: left; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .aw-prog-row i { font-style: normal; font-variant-numeric: tabular-nums; }
    .aw-check { margin-top: .3rem; font-size: .72rem; font-weight: 700; color: #3f6220; min-height: 1.2em; transition: color .28s; }
    .aw-check.is-quiet { color: #b45309; }
    .aw-check.is-lost { color: #b91c1c; }
    .aw-check:empty { display: none; }
    html.dark .aw-bar { background: rgb(143 201 106 / .16); }
    html.dark .aw-prog-row { color: #a5b89a; }
    html.dark .aw-prog-row b { color: #e8efe1; }
    html.dark .aw-check { color: #b9dc98; }
    html.dark .aw-check.is-quiet { color: #fbbf24; }
    html.dark .aw-check.is-lost { color: #fca5a5; }
    .aw-stay { margin: 1rem auto 0; max-width: 22rem; padding: .6rem .8rem; border-radius: .85rem; font-size: .78rem; font-weight: 700;
        color: #b45309; background: #fff7e6; border: 1px solid #fde4b8; }
    .aw-veil.is-done .aw-stay { opacity: 0; transition: opacity .3s ease; }
    @media (min-width: 640px) { .aw-ring { width: 11rem; height: 11rem; } }
    html.dark .aw-veil { background: rgb(13 17 9 / .96); }
    html.dark .aw-ring { background: #2f4d24; box-shadow: 0 0 0 5px #1c2416, 0 18px 44px -18px rgb(0 0 0 / .7); }
    html.dark .aw-title { color: #e8efe1; }
    html.dark .aw-line { color: #b9dc98; }
    html.dark .aw-sub { color: #a5b89a; }
    html.dark .aw-stay { color: #fbbf24; background: rgb(180 83 9 / .16); border-color: rgb(251 191 36 / .3); }
    html.aw-lock, html.aw-lock body { overflow: hidden; }
    @media (prefers-reduced-motion: reduce) {
        .aw-veil, .aw-card, .aw-vid, .aw-line, .aw-bar span { transition: none; }
        .aw-halo, .aw-bar span { animation: none; }
    }
</style>
<script>
(() => {
    // The veil's id is not "aneeWait": a named element becomes a window
    // global, and the API below would have found itself already taken.
    const veil = document.getElementById('aneeWaitVeil');
    if (!veil || (window.aneeWait && typeof window.aneeWait.show === 'function')) return;
    const think = document.getElementById('aneeWaitThink');
    const done = document.getElementById('aneeWaitDone');
    const title = document.getElementById('aneeWaitTitle');
    const line = document.getElementById('aneeWaitLine');
    const sub = document.getElementById('aneeWaitSub');
    const CLIPS = [@json(asset('videos/anee/lightbulb.mp4')), @json(asset('videos/anee/kiss.mp4')), @json(asset('videos/anee/yehey.mp4'))];
    /* The wait itself is drawn at random too (the owner's ask, 2026-09-15):
       her thinking, her searching with the magnifier, her typing at the
       laptop, or her writing in her notebook -- each with its own poster so the ring never opens on the
       wrong face. */
    const THINKS = [
        { src: @json(asset('videos/anee/thinking.mp4')), poster: @json(asset('videos/anee/thinking.jpg')) },
        { src: @json(asset('videos/anee/searching.mp4')), poster: @json(asset('videos/anee/searching.jpg')) },
        { src: @json(asset('videos/anee/typing.mp4')), poster: @json(asset('videos/anee/typing.jpg')) },
        { src: @json(asset('videos/anee/writing.mp4')), poster: @json(asset('videos/anee/writing.jpg')) },
    ];
    const reduce = () => window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    let lineTimer = null;
    let pending = false;

    /* ---- the bar ----
       Each phase a job reports owns a slice [lo, hi] of the hundred and a
       typical length in seconds; inside the phase the number creeps toward
       hi as that time passes and never quite arrives, so it only jumps when
       the job itself says the next phase has begun. */
    const prog = document.getElementById('aneeWaitProg');
    const bar = document.getElementById('aneeWaitBar');
    const barBox = document.getElementById('aneeWaitBarBox');
    const pctEl = document.getElementById('aneeWaitPct');
    const stepEl = document.getElementById('aneeWaitStep');
    const clockEl = document.getElementById('aneeWaitClock');
    const checkEl = document.getElementById('aneeWaitCheck');
    const RESEARCH_PHASES = {
        start: { label: 'Getting started', lo: 2, hi: 8, tau: 12 },
        research: { label: 'Reading the web', lo: 8, hi: 52, tau: 110 },
        document: { label: 'Writing the analysis', lo: 52, hi: 90, tau: 95 },
        'document-json': { label: 'Tidying the document', lo: 90, hi: 97, tau: 30 },
    };
    const PLAIN_PHASES = {
        start: { label: 'Getting started', lo: 2, hi: 10, tau: 10 },
        document: { label: 'Writing the analysis', lo: 10, hi: 92, tau: 70 },
        'document-json': { label: 'Tidying the document', lo: 92, hi: 97, tau: 25 },
    };
    let P = null;         // { phases, phase, phaseAt, t0, pct, tries, beatAgo, misses, tick }
    const clock = (s) => Math.floor(s / 60) + ':' + String(s % 60).padStart(2, '0');
    const paintBar = () => {
        if (!P) return;
        const ph = P.phases[P.phase] || P.phases.start || { label: 'Working', lo: 2, hi: 90, tau: 90 };
        const t = Math.max(0, (Date.now() - P.phaseAt) / 1000);
        const target = P.ready ? 100 : (ph.lo + (ph.hi - ph.lo) * (1 - Math.exp(-t / ph.tau)));
        P.pct = Math.max(P.pct, Math.min(100, target));   // never backwards
        const n = Math.round(P.pct);
        bar.style.width = n + '%';
        barBox.setAttribute('aria-valuenow', String(n));
        pctEl.textContent = n + '%';
        stepEl.textContent = P.ready ? 'Done' : (ph.label + (P.tries > 1 ? ' (again)' : '') + '…');
        clockEl.textContent = clock(Math.round((Date.now() - P.t0) / 1000));
        // The hang check: when the job was last heard from.
        checkEl.classList.remove('is-quiet', 'is-lost');
        if (P.ready) { checkEl.textContent = ''; return; }
        if (P.misses > 0) { checkEl.textContent = `Can’t reach the server — retrying (${P.misses}/8)…`; checkEl.classList.add('is-lost'); return; }
        const ago = P.beatAgo == null ? null : Math.round(P.beatAgo + (Date.now() - P.heardAt) / 1000);
        if (ago == null) { checkEl.textContent = 'Checking on her…'; return; }
        // A single call to the model can run a couple of minutes without a
        // word, so the line stays calm until a read is unusually long, and
        // the server declares a dead job on its own a minute after that.
        if (ago < 45) { checkEl.textContent = `Anee is working — heard from her ${ago < 5 ? 'just now' : ago + 's ago'}.`; return; }
        if (ago < 150) { checkEl.textContent = `Anee is working — a deep read runs a couple of minutes between words (last one ${clock(ago)} ago).`; return; }
        checkEl.textContent = `No word for ${clock(ago)} — longer than usual. Still checking; if she has stopped, this screen will say so within a minute.`;
        checkEl.classList.add('is-quiet');
    };
    const startProg = (phases) => {
        P = { phases: phases || RESEARCH_PHASES, phase: 'start', phaseAt: Date.now(), t0: Date.now(), pct: 0, tries: 1, beatAgo: null, heardAt: Date.now(), misses: 0, ready: false };
        clearInterval(P.tick);
        bar.style.width = '0%';
        prog.hidden = false;
        paintBar();
        P.tick = setInterval(paintBar, 700);
    };
    const stopProg = (ready) => {
        if (!P) return;
        P.ready = !!ready;
        if (ready) { P.pct = 100; paintBar(); }
        clearInterval(P.tick);
        if (!ready) prog.hidden = true;
    };
    const play = (v) => { try { const p = v.play(); if (p && p.catch) p.catch(() => {}); } catch (_) {} };
    /* Leaving mid-run is the one thing the veil is there to prevent: the
       browser's own "leave this page?" stands behind the warning line. */
    const guard = (e) => { if (!pending) return; e.preventDefault(); e.returnValue = ''; };
    window.addEventListener('beforeunload', guard);

    const rotate = (lines) => {
        clearInterval(lineTimer);
        if (!lines || !lines.length) { line.textContent = ''; return; }
        let i = 0;
        line.textContent = lines[0];
        if (lines.length < 2) return;
        lineTimer = setInterval(() => {
            i = (i + 1) % lines.length;
            line.style.opacity = 0;
            setTimeout(() => { line.textContent = lines[i]; line.style.opacity = 1; }, 280);
        }, 5200);
    };

    window.aneeWait = {
        /* A live word from the job itself ("Reading the web · 0:42") on the
           small line, leaving the rotating lines to their turn. */
        say(text) { if (pending) sub.textContent = text || ''; },
        /* The job's own word on where it stands: the phase moves the bar to
           its slice, `beatAgo` feeds the hang check, `misses` says the
           server could not be reached this time. */
        progress(o = {}) {
            if (!pending) return;
            if (!P) startProg(o.phases);
            if (o.phases && o.phases !== P.phases) P.phases = o.phases;
            const phase = o.phase || P.phase;
            const tries = Number(o.try) || 1;
            if (phase !== P.phase || tries !== P.tries) { P.phase = phase; P.tries = tries; P.phaseAt = Date.now(); }
            if (o.beatAgo != null) { P.beatAgo = Number(o.beatAgo); P.heardAt = Date.now(); }
            P.misses = Number(o.misses) || 0;
            paintBar();
        },
        /* The poll every AI run shares: reads the job until it is ready,
           feeds the bar, forgives a poll that could not reach the server
           (a phone losing signal, a gateway hiccup -- up to eight in a row),
           and throws only on the job's own word that it failed. */
        async poll({ id, job, phases, every = 3000, limit = 260 }) {
            startProg(phases || RESEARCH_PHASES);
            let misses = 0;
            for (let i = 0; i < limit && pending; i++) {
                await new Promise((r) => setTimeout(r, every));
                let st;
                try { st = await window.api(job(id), { method: 'GET' }); }
                catch (err) {
                    const failed = err.data && err.data.status === 'failed';
                    const transient = !failed && (err.offline || (err.status && err.status >= 500) || /^Request failed \(5\d\d\)$/.test(err.message || ''));
                    if (transient && ++misses <= 8) { window.aneeWait.progress({ misses }); continue; }
                    throw err;
                }
                misses = 0;
                const d = st && st.data ? st.data : {};
                if (d.status === 'ready') { stopProg(true); return d; }
                window.aneeWait.progress({ phase: d.phase || 'start', try: d.try || 1, beatAgo: d.beatAgo, misses: 0 });
            }
            throw new Error('Still working — give it a minute, then look on the Saved tab.');
        },
        phases: { research: RESEARCH_PHASES, plain: PLAIN_PHASES },
        show(opts = {}) {
            pending = true;
            P = null;
            prog.hidden = true;
            checkEl.textContent = '';
            title.textContent = opts.title || 'Anee is thinking…';
            sub.textContent = opts.sub || 'A deep read takes a minute or two.';
            rotate(opts.lines || []);
            done.classList.remove('is-on');
            think.classList.add('is-on');
            veil.classList.remove('is-done');
            veil.hidden = false;
            document.documentElement.classList.add('aw-lock');
            void veil.offsetWidth;
            veil.classList.add('is-on');
            const pick = THINKS[Math.floor(Math.random() * THINKS.length)];
            if (think.getAttribute('src') !== pick.src) {
                // A new clip: play once it can, not into the load that
                // replaces it (that play() is aborted and she stands still).
                think.setAttribute('poster', pick.poster);
                think.setAttribute('src', pick.src);
                think.addEventListener('canplay', () => { if (pending) play(think); }, { once: true });
                think.load();
            } else {
                think.currentTime = 0;
                play(think);
            }
        },
        /* The answer is in: her face lights up, the clip plays out, and
           only then does the veil lift -- the result is drawn underneath
           in the meantime, so it is simply there when she goes. */
        done(opts = {}) {
            return new Promise((resolve) => {
                pending = false;
                stopProg(true);
                clearInterval(lineTimer);
                if (opts.title) title.textContent = opts.title;
                line.textContent = opts.line || 'Done — here is what I found.';
                sub.textContent = '';
                veil.classList.add('is-done');
                const lift = () => {
                    veil.classList.remove('is-on');
                    document.documentElement.classList.remove('aw-lock');
                    setTimeout(() => { if (!pending) { veil.hidden = true; try { think.pause(); done.pause(); } catch (_) {} } resolve(); }, reduce() ? 0 : 360);
                };
                if (reduce()) { lift(); return; }
                const src = CLIPS[Math.floor(Math.random() * CLIPS.length)];
                let lifted = false;
                const finish = () => { if (lifted) return; lifted = true; lift(); };
                done.onended = finish;
                done.onerror = finish;
                done.src = src;
                done.load();
                done.currentTime = 0;
                play(done);
                // The crossfade: hers fades up over the thinking one.
                requestAnimationFrame(() => { done.classList.add('is-on'); think.classList.remove('is-on'); });
                // A clip that never ends (a stalled download) must not hold the answer hostage.
                setTimeout(finish, 6500);
            });
        },
        fail() {
            pending = false;
            stopProg(false);
            clearInterval(lineTimer);
            veil.classList.remove('is-on');
            document.documentElement.classList.remove('aw-lock');
            // Not if she was asked again in the meantime.
            setTimeout(() => { if (pending) return; veil.hidden = true; try { think.pause(); } catch (_) {} }, 360);
        },
        line(text) { clearInterval(lineTimer); line.textContent = text || ''; },
    };
})();
</script>
@endonce
