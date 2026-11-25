/* Movement nobody can see still costs the phone (2026-11-26, the owner: "on slower phones the experience is really bad").
 *
 * The app is full of small drawings that never stop: rings, leaves, sparks. Measured on a slow phone, the dashboard kept
 * its processor busy 4.99 seconds of every 5 with 64 of them running, most of them further down the page than anybody had
 * scrolled, and a tap had to wait its turn behind them.
 *
 * 1. For everybody: a loop that has no end rests while what it draws on is off the screen, and goes on when it is back.
 * 2. On a slow phone (html.sm-lite, set before the first paint in the layout's head): the loops rest altogether, except
 *    the ones that say "wait". Every change of state still moves; only the endless ones are still.
 *
 * Slow is judged by what the phone says of itself (memory, cores, data saver) and by a short piece of work timed here once
 * a week. Settings > Accessibility has the switch, and what a person chose there is never overruled. */
(() => {
    const root = document.documentElement;
    const later = window.requestIdleCallback ? (f, t) => requestIdleCallback(f, { timeout: t }) : (f, t) => setTimeout(f, Math.min(t, 1200));

    /* ---- how fast is this phone ---- */
    const work = () => {
        const t = performance.now();
        const a = [];
        let x = 0;
        for (let i = 0; i < 60000; i++) a.push({ k: i % 97, s: 'r' + (i % 31) });
        a.sort((p, q) => p.k - q.k);
        const m = new Map();
        for (const o of a) m.set(o.s, (m.get(o.s) || 0) + o.k);
        for (let i = 0; i < 400000; i++) x += Math.sqrt(i) % 3;
        return performance.now() - t + (x < 0 ? m.size : 0);
    };
    const time = () => {
        try {
            const [, when] = (localStorage.getItem('sm-bench') || '').split('|');
            if (Date.now() - (parseInt(when, 10) || 0) < 7 * 864e5) return;
        } catch (_) { return; }
        // The best of four, each in a quiet moment of its own: what else the phone was doing is not what is being asked.
        const got = [];
        const one = () => later(() => {
            if (document.visibilityState !== 'visible') return;
            got.push(work());
            if (got.length < 4) { setTimeout(one, 250); return; }
            try { localStorage.setItem('sm-bench', Math.round(Math.min(...got)) + '|' + Date.now()); } catch (_) {}
        }, 8000);
        one();
    };
    window.addEventListener('load', () => setTimeout(time, 9000), { once: true });

    /* ---- the loops ---- */
    if (typeof document.getAnimations !== 'function' || !('IntersectionObserver' in window)) return;
    const KEEP = /spin|load|wait|shimmer|skel|progress|typing|dots|rec|busy|scan|marquee|ticker|carousel|bar(b)?er|^bv/i;
    const WAITING = '.bv-card, .boot-veil, [aria-busy="true"], [role="progressbar"], [role="status"], .screen-loader, .ai-typing';
    const known = new WeakSet();
    const rested = new WeakSet();
    const hosts = new Map();      // what is watched -> its loops
    const lite = () => root.classList.contains('sm-lite');
    const calms = (a, host) => lite() && !KEEP.test(a.animationName || '') && !host.closest?.(WAITING);
    const rest = (a) => { if (a.playState === 'running') { a.pause(); rested.add(a); } };
    const wake = (a) => { if (rested.has(a) && a.playState === 'paused') { rested.delete(a); a.play(); } };

    const eye = new IntersectionObserver((seen) => {
        for (const s of seen) {
            const h = hosts.get(s.target);
            if (!h) continue;
            h.on = s.isIntersecting;
            for (const a of h.loops) (h.on && !calms(a, s.target)) ? wake(a) : rest(a);
            if (h.svg) { try { h.on && !lite() ? s.target.unpauseAnimations() : s.target.pauseAnimations(); } catch (_) {} }
        }
    }, { rootMargin: '140px' });
    const watch = (el) => {
        let h = hosts.get(el);
        if (!h) { h = { loops: new Set(), on: true, svg: false }; hosts.set(el, h); eye.observe(el); }
        return h;
    };
    const sweep = () => {
        for (const [el, h] of hosts) {
            if (!el.isConnected) { eye.unobserve(el); hosts.delete(el); continue; }
            for (const a of h.loops) if (a.playState === 'idle') h.loops.delete(a);
        }
        for (const a of document.getAnimations()) {
            if (known.has(a)) continue;
            const t = a.effect?.getTiming?.();
            const el = a.effect?.target;
            if (!t || !el || t.iterations !== Infinity) continue;
            known.add(a);
            // Watched from something that stands still: a drawing from its whole picture, anything else from its parent.
            const host = el.ownerSVGElement ? (el.closest('svg') || el) : (a.effect.pseudoElement ? el : (el.parentElement || el));
            if (host === document.body || host === root) { if (calms(a, host)) rest(a); continue; }
            const h = watch(host);
            h.loops.add(a);
            if (!h.on || calms(a, host)) rest(a);
        }
        // Drawings that move themselves (SMIL) have a switch of their own.
        document.querySelectorAll('svg animate, svg animateTransform, svg animateMotion').forEach((n) => {
            const svg = n.ownerSVGElement;
            if (svg && !svg.closest(WAITING)) {
                const h = watch(svg);
                if (!h.svg) { h.svg = true; if (lite()) { try { svg.pauseAnimations(); } catch (_) {} } }
            }
        });
    };
    let due = 0;
    const soon = () => {
        if (due) return;
        due = setTimeout(() => later(() => { due = 0; sweep(); }, 900), 350);
    };
    document.addEventListener('animationstart', soon, { capture: true, passive: true });
    window.addEventListener('load', soon, { once: true });
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', soon, { once: true });
    else soon();
    // The switch in Settings answers at once.
    document.addEventListener('anee:lite', () => {
        for (const [el, h] of hosts) {
            for (const a of h.loops) (h.on && !calms(a, el)) ? wake(a) : rest(a);
        }
    });
})();
