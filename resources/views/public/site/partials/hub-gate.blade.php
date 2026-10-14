{{-- The members' gate on a finder or catalogue (2026-10-14, the owner):
     /pests, /diseases, /weeds, /rice-varieties and every crop's variety
     finder. The search and the filters still work for a guest, but the
     first row of what matches shows clear, a blur starts halfway down the
     second row and covers the third, and the rest is held back under the
     members' card. Every card stays in the HTML, so search engines read
     every link.

     It also moves the cards (window.aneeFlip): a search or a filter slides
     the cards that stay to their new places, fades the new ones in and the
     leavers out, and eases the grid's height, for members too.

     Markup: wrap the grid (and its "show more" button) in
         <div class="hg" data-hg data-hg-one="pest" data-hg-many="pests">
     give the grid data-hg-grid (and data-hg-card, the card selector), then
     @include('public.site.partials.content-gate-card', [...]) inside the
     wrapper, after the grid. The grid's own script paints through
     window.aneeFlip(grid, paint) and calls window.aneeHubGate(grid) at the
     end of every paint. --}}
@include('public.site.partials.content-gate-assets')
@once
@push('head')
<style>
    .hg { position: relative; }
    .hg-locked { pointer-events: none; user-select: none; }
    .hg-cut { display: none !important; }
    .hg .wk-more, .hg [data-rv-more] { display: none !important; }
    .hg .sp-gate-card { margin-top: 1.6rem; }
    /* The blur: a veil over the grid from halfway down its second row (a
       finder's answer: its second result, or its second block). It
       blurs what is under it, so a row can be half clear and half not. */
    .hg-veil { position: absolute; left: -.5rem; right: -.5rem; z-index: 1; top: 0; height: 0; pointer-events: none; opacity: 0;
        -webkit-backdrop-filter: blur(7px); backdrop-filter: blur(7px); background: rgb(251 252 249 / .4);
        -webkit-mask-image: linear-gradient(transparent, #000 2.75rem); mask-image: linear-gradient(transparent, #000 2.75rem);
        transition: top .3s cubic-bezier(.22,1,.36,1), height .3s cubic-bezier(.22,1,.36,1), opacity .3s cubic-bezier(.22,1,.36,1); }
    .hg.has-locked .hg-veil { opacity: 1; pointer-events: auto; }
    /* The blurred rows fade out under the card that sits over their end. */
    .hg.has-locked [data-hg-grid] { -webkit-mask-image: linear-gradient(to bottom, #000 calc(100% - 12rem), transparent); mask-image: linear-gradient(to bottom, #000 calc(100% - 12rem), transparent); }
    .hg.has-locked .sp-gate-card { margin-top: -7rem; }
    /* Inside a finder's answer only, what follows a held list (its legend,
       the shop note, the sources) waits too. Never the hub's own card, which
       sits after the grid in the same wrapper. */
    .hg.has-locked .wc-card [data-hg-grid] ~ *, .hg.has-locked .wc-card :has(> [data-hg-grid]) ~ * { display: none; }
    .hg-chip, .wk-jump .hg-chip { display: inline-flex; align-items: center; gap: .4rem; padding: .5rem .9rem; border-radius: 999px; font-size: .88rem; font-weight: 800; text-decoration: none;
        color: #2d5016; background: #eef6e4; border: 1px solid #d3e6bd; transition: background-color .28s cubic-bezier(.22,1,.36,1); }
    a.hg-chip:hover, .wk-jump a.hg-chip:hover { background: #e2f0d2; }
    .hg-chip svg, .wk-jump .hg-chip svg { width: 1rem; height: 1rem; color: #4d7f2a; }
    .hg-chip.is-open u { text-underline-offset: 3px; }
    @media (max-width: 639.98px) { .hg.has-locked .sp-gate-card { margin-top: -5rem; } }
    @media (prefers-reduced-motion: reduce) { .hg-veil { transition: none; } }
</style>
@endpush
@push('scripts')
<script>
(() => {
    const EASE = 'cubic-bezier(.22,1,.36,1)';
    const still = () => matchMedia('(prefers-reduced-motion: reduce)').matches;
    const cardsOf = (grid) => [...grid.children].filter((c) => c.matches(grid.dataset.hgCard || 'a'));
    const shown = (c) => c.offsetParent !== null;
    // An element's top inside the wrapper, without the moves in flight.
    const topIn = (el, wrap) => { let y = 0; for (let n = el; n && n !== wrap; n = n.offsetParent) y += n.offsetTop; return y; };

    /* The gate: the first row of what matches stays clear; the blur starts
       halfway down the second row (halfway down the only row, when one row
       is all there is) and covers the third; anything after the third row
       waits behind the card. Rows are read from where the cards actually
       sit, so a grid, a single column and a wrapping row of chips all
       count alike (and a crop chip's CSS order is followed). */
    const lock = (grid) => {
        const wrap = grid && grid.closest('[data-hg]');
        if (!wrap) return;
        const foot = wrap.querySelector('[data-gate-foot]');
        // An answer with nothing to hold back (no list at all): no gate.
        if (!grid.hasAttribute('data-hg-grid')) { wrap.classList.remove('has-locked'); if (foot) foot.textContent = ''; return; }
        const cards = cardsOf(grid);
        cards.forEach((c) => { c.classList.remove('hg-locked', 'hg-cut'); c.inert = false; });
        const on = cards.filter((c) => !c.hidden && !c.classList.contains('is-out') && shown(c));
        // Locked or not changes what shows after the list, so measure as it will be.
        wrap.classList.toggle('has-locked', on.length > 1);
        // The rows: the cards grouped by their top, a few pixels either way.
        let at, tops;
        const measure = (list) => {
            at = new Map(list.map((c) => [c, topIn(c, wrap)]));
            tops = [];
            [...at.values()].sort((a, b) => a - b).forEach((t) => { if (!tops.length || t - tops[tops.length - 1] > 4) tops.push(t); });
        };
        const rowOf = (c) => { const t = at.get(c); let r = 0; tops.forEach((x, i) => { if (t >= x - 4) r = i; }); return r; };
        measure(on);
        on.forEach((c) => { if (rowOf(c) >= 3) c.classList.add('hg-cut'); });
        // Holding rows back can move what is left (an answer box that
        // centres its content), so the rows that stay are read again.
        const kept = on.filter((c) => !c.classList.contains('hg-cut'));
        measure(kept);
        let veil = wrap.querySelector(':scope > .hg-veil');
        if (!veil) { veil = document.createElement('div'); veil.className = 'hg-veil'; veil.setAttribute('aria-hidden', 'true'); wrap.appendChild(veil); }
        let held = 0;
        if (on.length > 1) {
            const r = tops.length > 1 ? 1 : 0;
            const row = kept.filter((c) => rowOf(c) === r);
            const from = Math.round(tops[r] + Math.max(...row.map((c) => c.offsetHeight)) / 2);
            const bottom = topIn(grid, wrap) + grid.offsetHeight;
            veil.style.top = from + 'px';
            veil.style.height = Math.max(0, bottom - from) + 'px';
            kept.forEach((c) => {
                const t = at.get(c);
                if (t + c.offsetHeight > from) held++;
                if (t >= from) { c.classList.add('hg-locked'); c.inert = true; }
            });
            held += on.length - kept.length;
        }
        wrap.classList.toggle('has-locked', held > 0);
        if (foot) {
            const noun = held === 1 ? wrap.dataset.hgOne : wrap.dataset.hgMany;
            foot.textContent = !held ? '' : wrap.dataset.hgFoot || (on.length < cards.length ? held + ' more ' + noun + ' match what you picked' : 'And ' + held + ' more ' + noun + ' waiting for you');
        }
    };

    /* An answer drawn again from scratch (the finders write their card
       anew): the rows with the same key slide from where they were, new
       ones and the unkeyed parts fade up, rows that left fade where they
       stood, and the box's height eases. */
    const swap = (box, paint, o = {}) => {
        if (still() || !box) { paint(); return; }
        const pick = (sel) => (sel ? [...box.querySelectorAll(sel)].filter(shown) : []);
        const was = new Map(), leave = [];
        pick(o.key).forEach((el) => { const r = el.getBoundingClientRect(); was.set(el.dataset.k, r); leave.push([el.dataset.k, el.cloneNode(true), r]); });
        const h0 = box.getBoundingClientRect().height;
        box.getAnimations({ subtree: true }).forEach((a) => { if (a.id === 'flip') a.cancel(); });
        document.querySelectorAll('.hg-ghost').forEach((g) => g.remove());
        paint();
        const h1 = box.getBoundingClientRect().height;
        const vh = innerHeight, stay = new Set();
        let n = 0;
        const fadeUp = (el) => { const a = el.animate([{ opacity: 0, transform: 'translateY(10px)' }, { opacity: 1, transform: 'none' }], { duration: 300, delay: Math.min(n++, 8) * 35, easing: EASE, fill: 'backwards' }); a.id = 'flip'; };
        pick(o.key).forEach((el) => {
            const now = el.getBoundingClientRect(), before = was.get(el.dataset.k);
            stay.add(el.dataset.k);
            if (now.bottom < -200 || now.top > vh + 200) return;
            if (!before) { fadeUp(el); return; }
            const dx = before.left - now.left, dy = before.top - now.top;
            if (Math.abs(dx) < 1 && Math.abs(dy) < 1) return;
            const a = el.animate([{ transform: `translate(${dx}px, ${dy}px)` }, { transform: 'none' }], { duration: 320, easing: EASE });
            a.id = 'flip';
        });
        pick(o.fade).forEach(fadeUp);
        let gone = 0;
        leave.forEach(([k, g, r]) => {
            if (stay.has(k) || r.bottom < 0 || r.top > vh || gone++ > 12) return;
            g.classList.add('hg-ghost');
            g.inert = true;
            Object.assign(g.style, { position: 'fixed', left: r.left + 'px', top: r.top + 'px', width: r.width + 'px', height: r.height + 'px', margin: '0', zIndex: '3', pointerEvents: 'none' });
            document.body.appendChild(g);
            g.animate([{ opacity: 1, transform: 'scale(1)' }, { opacity: 0, transform: 'scale(.97)' }], { duration: 200, easing: EASE }).onfinish = () => g.remove();
        });
        if (Math.abs(h1 - h0) > 1) {
            const a = box.animate([{ height: h0 + 'px' }, { height: h1 + 'px' }], { duration: 320, easing: EASE });
            a.id = 'flip';
        }
    };

    /* The moves: First, Last, Invert, Play. Positions are read before the
       change (as they are on screen, mid move included), the change is
       made, and every card that stays slides from where it was; new cards
       fade in, the ones that left fade out where they stood, and the grid's
       height eases so the page below does not jump. */
    const flip = (grid, paint) => {
        if (still()) { paint(); return; }
        const cards = cardsOf(grid);
        const was = new Map();
        cards.forEach((c) => { if (shown(c)) was.set(c, c.getBoundingClientRect()); });
        const h0 = grid.getBoundingClientRect().height;
        [grid, ...cards].forEach((el) => el.getAnimations().forEach((a) => { if (a.id === 'flip') a.cancel(); }));
        document.querySelectorAll('.hg-ghost').forEach((g) => g.remove());
        paint();
        const h1 = grid.getBoundingClientRect().height;
        const vh = innerHeight;
        let n = 0, gone = 0;
        cards.forEach((c) => {
            const before = was.get(c);
            if (!shown(c)) {
                // Left: a picture of it fades where it stood.
                if (before && before.bottom > 0 && before.top < vh && gone++ < 24) {
                    const g = c.cloneNode(true);
                    g.removeAttribute('id');
                    g.hidden = false;
                    g.classList.remove('is-out', 'hg-cut', 'hg-locked', 'is-in');
                    g.classList.add('hg-ghost');
                    g.inert = true;
                    Object.assign(g.style, { position: 'fixed', left: before.left + 'px', top: before.top + 'px', width: before.width + 'px', height: before.height + 'px',
                        margin: '0', zIndex: '3', pointerEvents: 'none', display: '' });
                    document.body.appendChild(g);
                    g.animate([{ opacity: 1, transform: 'scale(1)' }, { opacity: 0, transform: 'scale(.96)' }], { duration: 200, easing: EASE }).onfinish = () => g.remove();
                }
                return;
            }
            const now = c.getBoundingClientRect();
            if (now.bottom < -200 || now.top > vh + 200) return;
            let a;
            if (before) {
                const dx = before.left - now.left, dy = before.top - now.top;
                if (Math.abs(dx) < 1 && Math.abs(dy) < 1) return;
                a = c.animate([{ transform: `translate(${dx}px, ${dy}px)` }, { transform: 'none' }], { duration: 320, easing: EASE });
            } else {
                a = c.animate([{ opacity: 0, transform: 'translateY(12px) scale(.98)' }, { opacity: 1, transform: 'none' }],
                    { duration: 320, delay: Math.min(n++, 10) * 28, easing: EASE, fill: 'backwards' });
            }
            a.id = 'flip';
        });
        if (Math.abs(h1 - h0) > 1) {
            grid.style.overflow = 'clip';
            const a = grid.animate([{ height: h0 + 'px' }, { height: h1 + 'px' }], { duration: 320, easing: EASE });
            a.id = 'flip';
            const done = () => { if (!grid.getAnimations().some((x) => x.id === 'flip' && x.playState === 'running')) grid.style.overflow = ''; };
            a.onfinish = done; a.oncancel = done;
        }
    };

    window.aneeHubGate = lock;
    window.aneeFlip = flip;
    window.aneeSwap = swap;
    const all = () => document.querySelectorAll('[data-hg] [data-hg-grid]').forEach(lock);
    all();
    document.addEventListener('DOMContentLoaded', all);
    window.addEventListener('load', all);
    document.fonts?.ready?.then(all);
    let rt = 0;
    window.addEventListener('resize', () => { clearTimeout(rt); rt = setTimeout(all, 150); });
})();
</script>
@endpush
@endonce
