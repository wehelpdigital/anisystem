{{-- THE GUIDED WALK.

     A dim over the page with a hole cut in it, a card that says what the thing
     in the hole is for, and Back / Next. It is the one way this app can teach
     a module without asking anybody to read a page first: the explanation
     stands next to the button it is explaining.

     A tour is a list of steps, and a step is a selector plus two sentences.
     Pages declare their own (see window.aneeTour.register) and the walk can
     hand off to another page mid-run, so one tour can cross the whole app and
     still come back to the step it was on.

     Loaded on every app page so the handoff has something to land in; it draws
     nothing and binds nothing until a tour is actually started. --}}
@once
@push('head')
<style>
    /* NOTHING UNDERNEATH IS CLICKABLE WHILE THE WALK IS ON.
       The dim is drawn by a shadow, and a shadow catches no clicks — so the
       module tiles were still live behind it and a tap that missed Next by a
       few pixels navigated away mid-tour. This catches them. Below the hole
       and the card, above everything else. */
    .tour-veil { position: fixed; inset: 0; z-index: 299; background: transparent; }

    /* The hole. One element with an enormous spread shadow dims everything
       around it — cheaper and steadier than four panels chasing a rect, and
       it cannot leak a seam at the corners the way four panels do. */
    .tour-spot {
        position: fixed; z-index: 300; border-radius: .8rem; pointer-events: none;
        box-shadow: 0 0 0 9999px rgb(12 17 8 / .72), 0 0 0 3px var(--color-accent-400, #f2c94c);
        transition: top .28s cubic-bezier(.22,1,.36,1), left .28s cubic-bezier(.22,1,.36,1),
                    width .28s cubic-bezier(.22,1,.36,1), height .28s cubic-bezier(.22,1,.36,1);
    }
    /* A step with nothing to point at — "here is the module, in general" —
       dims the whole screen and lets the card carry it alone. */
    .tour-spot.is-nowhere { box-shadow: 0 0 0 9999px rgb(12 17 8 / .72); opacity: 0; }

    .tour-card {
        position: fixed; z-index: 301; width: min(22rem, calc(100vw - 1.6rem));
        background: var(--tl-surface, #fff); color: var(--tl-text, #111827);
        border-radius: 1rem; padding: 1rem 1.05rem 0.9rem;
        box-shadow: 0 24px 60px -12px rgb(0 0 0 / .45);
        /* It ARRIVES with a pop and then TRAVELS. Re-popping on every step made
           the card blink out and back in at a new place, which reads as two
           cards rather than one walking you through; sliding, it stays the same
           card and the eye follows it. */
        transition: top .34s cubic-bezier(.22,1,.36,1), left .34s cubic-bezier(.22,1,.36,1);
    }
    .tour-card.is-new { animation: app-pop-in .26s cubic-bezier(.22,1,.36,1) both; }
    /* The words change under it, so they fade rather than snap. */
    .tour-body { transition: opacity .18s ease; }
    .tour-card.is-turning .tour-body { opacity: 0; }
    .tour-step { font-size: .68rem; font-weight: 800; letter-spacing: .06em; text-transform: uppercase;
        color: var(--color-brand-600, #4a7c2a); }
    .tour-h { font-family: var(--font-heading); font-size: 1.05rem; font-weight: 800; line-height: 1.25; margin: .2rem 0 .3rem; }
    .tour-p { font-size: .86rem; line-height: 1.6; color: var(--tl-text-soft, #6b7280); }
    .tour-foot { display: flex; align-items: center; gap: .5rem; margin-top: .9rem; }
    .tour-dots { display: flex; gap: .22rem; margin-right: auto; }
    .tour-dot { width: .35rem; height: .35rem; border-radius: 999px; background: var(--color-gray-300, #d1d5db); }
    .tour-dot.is-on { background: var(--color-brand-600, #4a7c2a); width: .9rem; }
    .tour-x { position: absolute; top: .5rem; right: .5rem; width: 1.9rem; height: 1.9rem; border-radius: 999px;
        display: inline-flex; align-items: center; justify-content: center;
        color: var(--color-gray-400); cursor: pointer; }
    .tour-x:hover { background: var(--tl-hover, rgb(0 0 0 / .06)); }
    .tour-x svg { width: 1rem; height: 1rem; }

    /* On a phone the card stops chasing the hole and sits at the foot of the
       screen. There is no room beside anything, and a card that jumps around
       the page is harder to read than one that stays where you last saw it. */
    /* On a phone the card stops chasing the hole and parks at one end of the
       screen. There is no room beside anything, and a card that jumps about is
       harder to read than one that stays where you last saw it. It slides
       between the two ends rather than teleporting, and the safe-area inset
       keeps it off the home bar. */
    @media (max-width: 640px) {
        .tour-card {
            left: .7rem; right: .7rem; width: auto;
            top: auto !important; bottom: calc(.7rem + env(safe-area-inset-bottom, 0px));
            transition: bottom .34s cubic-bezier(.22,1,.36,1), top .34s cubic-bezier(.22,1,.36,1);
        }
        .tour-card.is-low { bottom: auto; top: calc(.7rem + env(safe-area-inset-top, 0px)) !important; }
        .tour-h { font-size: 1rem; }
        .tour-p { font-size: .84rem; }
        /* Thumb-sized, and the dots give way before the buttons do. */
        .tour-foot .btn { min-height: 2.5rem; }
        .tour-dots { flex-wrap: wrap; max-width: 45%; }
    }
    /* Deliberately NOT overflow:hidden on the page. The walk scrolls each
       target into view itself, and a locked page is a page scrollIntoView
       cannot move — the hole would sit over whatever happened to be on
       screen. The veil above stops the taps; the scrolling is ours. */
    @media (prefers-reduced-motion: reduce) {
        .tour-spot, .tour-card, .tour-body { transition: none; }
        .tour-card.is-new { animation: none; }
    }
</style>
@endpush

{{-- PREPENDED, not pushed. A page registers its own steps from its own
     @push('scripts'), and the layout renders the content section before it
     reaches this include — so pushing would define the engine AFTER the page
     had already tried to hand it a tour, and the optional-chained call would
     no-op in silence. Prepending puts the engine at the head of the stack,
     where everything that uses it can find it. --}}
@prepend('scripts')
<script>
(() => {
    const TOURS = {};
    /* Where a tour is up to, kept in sessionStorage rather than a variable:
       a step may send the reader to another page, and a variable does not
       survive that. Session, not local — a half-finished walk should not be
       waiting in a tab opened next week. */
    const KEY = 'aneeTour';
    const read = () => { try { return JSON.parse(sessionStorage.getItem(KEY) || 'null'); } catch (_) { return null; } };
    const write = (v) => { try { v ? sessionStorage.setItem(KEY, JSON.stringify(v)) : sessionStorage.removeItem(KEY); } catch (_) { /* private mode */ } };

    let veil = null, spot = null, card = null, onResize = null;

    const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

    function teardown() {
        veil?.remove(); spot?.remove(); card?.remove();
        veil = spot = card = null;
        if (onResize) {
            window.removeEventListener('resize', onResize);
            window.removeEventListener('scroll', onResize, true);
            onResize = null;
        }
        document.documentElement.classList.remove('tour-running');
    }

    function stop() {
        write(null);
        teardown();
    }

    /** Put the hole over the target and the card beside it. */
    function place(step, i, steps) {
        const el = step.target ? document.querySelector(step.target) : null;
        const pad = 6;

        if (el) {
            const b = el.getBoundingClientRect();
            spot.classList.remove('is-nowhere');
            spot.style.top = (b.top - pad) + 'px';
            spot.style.left = (b.left - pad) + 'px';
            spot.style.width = (b.width + pad * 2) + 'px';
            spot.style.height = (b.height + pad * 2) + 'px';
        } else {
            // Nothing to point at: dim everything and let the card speak.
            spot.classList.add('is-nowhere');
            spot.style.top = '50%'; spot.style.left = '50%';
            spot.style.width = '0px'; spot.style.height = '0px';
        }

        const narrow = window.innerWidth <= 640;
        if (narrow) {
            // The card is pinned; the only choice is which end of the screen,
            // and it goes to the end the hole is NOT at.
            const b = el ? el.getBoundingClientRect() : null;
            card.classList.toggle('is-low', !!b && b.top > window.innerHeight * 0.55);

            return;
        }

        card.classList.remove('is-low');
        const cb = card.getBoundingClientRect();
        if (!el) {
            card.style.top = Math.max(12, (window.innerHeight - cb.height) / 2) + 'px';
            card.style.left = Math.max(12, (window.innerWidth - cb.width) / 2) + 'px';

            return;
        }
        const b = el.getBoundingClientRect();
        const gap = 14;
        // Under the target if it fits, over it if not, and never off an edge.
        let top = b.bottom + gap;
        if (top + cb.height > window.innerHeight - 12) {
            top = Math.max(12, b.top - gap - cb.height);
        }
        let left = b.left + (b.width / 2) - (cb.width / 2);
        left = Math.min(Math.max(12, left), window.innerWidth - cb.width - 12);
        card.style.top = top + 'px';
        card.style.left = left + 'px';
    }

    async function show() {
        const at = read();
        if (!at) { teardown(); return; }
        const steps = TOURS[at.name];
        if (!steps) { teardown(); return; }          // this page is not the one
        if (at.i >= steps.length) { finish(at.name); return; }

        const step = steps[at.i];

        // A step may need the page opened somewhere first — a sheet raised, a
        // day unfolded. It is awaited, because what it opens is what the hole
        // has to sit over.
        try { if (step.before) await step.before(); } catch (_) { /* keep walking */ }

        const el = step.target ? document.querySelector(step.target) : null;
        if (step.target && !el) {
            // The thing this step is about is not on this screen. Skipping it
            // beats pointing at nothing and beats stopping the walk dead.
            write({ ...at, i: at.i + 1 });

            return show();
        }
        el?.scrollIntoView({ block: 'center', behavior: 'smooth' });
        await new Promise((r) => setTimeout(r, el ? 320 : 0));

        if (!spot) {
            document.documentElement.classList.add('tour-running');
            veil = document.createElement('div');
            veil.className = 'tour-veil';
            spot = document.createElement('div');
            spot.className = 'tour-spot';
            card = document.createElement('div');
            card.className = 'tour-card is-new';
            // The pop is for arriving. Taken off once it has played, so every
            // step after this one slides instead.
            setTimeout(() => card?.classList.remove('is-new'), 400);
            card.setAttribute('role', 'dialog');
            card.setAttribute('aria-live', 'polite');
            document.body.append(veil, spot, card);
            onResize = () => { const a = read(); if (a && TOURS[a.name]) place(TOURS[a.name][a.i] || {}, a.i, TOURS[a.name]); };
            window.addEventListener('resize', onResize);
            window.addEventListener('scroll', onResize, true);
        }

        const last = at.i === steps.length - 1;
        /* The card's CONTENTS are replaced; the card is not. Rebuilding the
           whole element every  step threw away the box the transition was moving,
           so it vanished and reappeared instead of travelling. */
        card.innerHTML = `
            <button type="button" class="tour-x" data-tour-stop aria-label="End the walk">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M18 6L6 18"/></svg>
            </button>
            <div class="tour-body">
                <p class="tour-step">Step ${at.i + 1} of ${steps.length}</p>
                <h3 class="tour-h">${esc(step.title)}</h3>
                <p class="tour-p">${esc(step.body)}</p>
            </div>
            <div class="tour-foot">
                <span class="tour-dots">${steps.map((_, n) => `<span class="tour-dot${n === at.i ? ' is-on' : ''}"></span>`).join('')}</span>
                ${at.i > 0 ? '<button type="button" class="btn btn-white btn-sm" data-tour-back>Back</button>' : ''}
                <button type="button" class="btn btn-primary btn-sm" data-tour-next>${last ? 'Finish' : 'Next'}</button>
            </div>`;
        card.classList.remove('is-turning');

        place(step, at.i, steps);
        // Measured once laid out, then placed again: the card's height is not
        // knowable until its words are in it, and the first placement used the
        // height of whatever step came before.
        requestAnimationFrame(() => place(step, at.i, steps));
    }

    function finish(name) {
        teardown();
        write(null);
        try { localStorage.setItem('aneeTourDone:' + name, '1'); } catch (_) { /* fine */ }
        window.toast?.('That is the tour. Everything in this season is yours to change.');
    }

    function go(delta) {
        const at = read();
        if (!at) return;
        const steps = TOURS[at.name] || [];
        const next = at.i + delta;
        if (next < 0) return;
        if (next >= steps.length) { finish(at.name); return; }

        /* A step can live on another page. The walk is written down before the
           browser leaves, so the next page picks it up at exactly this step —
           that is the whole reason the position is in storage and not a
           variable. */
        const step = steps[next];
        write({ ...at, i: next });
        // The words go before the new ones arrive, so the swap is a turn of
        // the page rather than a flicker.
        card?.classList.add('is-turning');
        if (step.goTo && step.goTo !== location.pathname + location.search) {
            teardown();
            location.href = step.goTo;

            return;
        }
        show();
    }

    document.addEventListener('click', (e) => {
        if (e.target.closest('[data-tour-next]')) { go(1); return; }
        if (e.target.closest('[data-tour-back]')) { go(-1); return; }
        if (e.target.closest('[data-tour-stop]')) { stop(); }
    });
    document.addEventListener('keydown', (e) => {
        if (!read()) return;
        if (e.key === 'Escape') stop();
        if (e.key === 'ArrowRight') go(1);
        if (e.key === 'ArrowLeft') go(-1);
    });

    window.aneeTour = {
        /** A page hands over the steps it knows about. */
        register(name, steps) {
            TOURS[name] = steps;
            // Already walking this one? Then this page is a stop on it.
            const at = read();
            if (at && at.name === name) setTimeout(show, 250);
        },
        start(name) {
            write({ name, i: 0 });
            const first = (TOURS[name] || [])[0];
            if (first && first.goTo && first.goTo !== location.pathname + location.search) {
                location.href = first.goTo;

                return;
            }
            show();
        },
        stop,
        running: () => !!read(),
    };
})();
</script>
@endprepend
@endonce
