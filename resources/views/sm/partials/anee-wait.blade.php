{{-- ANEE, AT WORK — the wait every AI run wears.

     A full-screen veil with her face in a ring: her thinking, or searching
     with a magnifier (drawn at random), while the model works, and when the
     answer lands a crossfade to her pointing at a lightbulb or blowing a
     kiss (drawn at random again), which plays out before
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
        .aw-veil, .aw-card, .aw-vid, .aw-line { transition: none; }
        .aw-halo { animation: none; }
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
    const CLIPS = [@json(asset('videos/anee/lightbulb.mp4')), @json(asset('videos/anee/kiss.mp4'))];
    /* The wait itself is drawn at random too (the owner's ask, 2026-09-15):
       her thinking, or her searching with the magnifier -- each with its
       own poster so the ring never opens on the wrong face. */
    const THINKS = [
        { src: @json(asset('videos/anee/thinking.mp4')), poster: @json(asset('videos/anee/thinking.jpg')) },
        { src: @json(asset('videos/anee/searching.mp4')), poster: @json(asset('videos/anee/searching.jpg')) },
    ];
    const reduce = () => window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    let lineTimer = null;
    let pending = false;
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
        show(opts = {}) {
            pending = true;
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
