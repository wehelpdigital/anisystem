{{-- ================================================================
     Taglish help (2026-10-15, the owner; a flag until 2026-10-16, now a
     small tag that says Taglish): it sits after a technical label, and
     tapping it explains the term in plain Taglish. A tooltip anchored to
     the tag on a wide screen, a bottom
     sheet on a phone. One open at a time; it closes on a tap outside, on
     Esc and on the phone's Back.

     Included once by layouts.app, in the head, so its click listener is
     the first in line (a tag inside a tile link or a locked door must not
     open the tile). The words are App\Support\Tagalog's glossary, fetched
     on the first tap; this page carries only the list of keys.

     Ways in: \App\Support\Tagalog::flag('dat') in Blade, data-tl="dat" on
     an element a script draws, window.aneeTl.flag('dat') in a script.
     Off for the international face (window.aneeTl is then a stub that
     draws nothing), and hidden everywhere by html.tlx-off (Settings).
     ================================================================ --}}
@if (\App\Support\Tagalog::on())
<style>
    /* ---- the Taglish tag (2026-10-16, the owner: "a tag like button that
       says Taglish", in place of the Philippine flag) ----
       An inline span with padding where the tag sits, and two pseudo
       elements. ::before is the tag AND its tap target: a pill of fixed width
       (--tlx-w) whose transparent border widens what a finger can hit while
       background-clip keeps the pill its own size, so the target moves
       nothing. ::after is a word joiner: Chrome treats an absolutely placed
       box inside an inline as a place the line may break, and the joiner
       forbids it, so the tag never drops to a line by itself. Neither adds
       to the label's textContent. */
    /* Important on the box itself: page rules like ".card span { display: block }"
       would otherwise reshape it, and it lives inside every kind of page. */
    .tlx { --tlx-w: 45px; position: relative !important; display: inline !important; padding: 0 0 0 var(--tlx-w) !important; margin: 0 0 0 .38em !important;
        border: 0 !important; background: none !important; width: auto !important; height: auto !important; min-height: 1em;
        font-size: inherit !important; line-height: inherit !important; letter-spacing: normal !important; opacity: 1 !important;
        float: none !important; flex: none; cursor: pointer; outline: none; -webkit-tap-highlight-color: transparent; vertical-align: baseline;
        font-style: normal; text-decoration: none; pointer-events: auto; user-select: none; -webkit-user-select: none; }
    .tlx::after { content: '\2060' !important; position: static !important; display: inline !important; }
    .tlx::before { content: 'Taglish' !important; position: absolute !important; display: block !important; box-sizing: content-box !important;
        left: -7px; top: 50%; width: var(--tlx-w) !important; height: 17px !important; margin: -15.5px 0 0 !important; padding: 0 !important;
        border: 7px solid transparent !important; border-radius: 999px !important; background-clip: padding-box !important;
        background-color: #eef6e4; box-shadow: inset 0 0 0 1px #bfd6a6;
        color: #2d5016; font-family: inherit; font-size: 10px !important; font-weight: 800 !important; line-height: 17px !important;
        font-style: normal !important; letter-spacing: .02em !important; text-transform: none !important; text-decoration: none !important;
        text-align: center !important; white-space: nowrap !important; text-indent: 0 !important; overflow: hidden;
        transition: transform .28s cubic-bezier(.22,1,.36,1), background-color .28s cubic-bezier(.22,1,.36,1), color .28s cubic-bezier(.22,1,.36,1), box-shadow .28s cubic-bezier(.22,1,.36,1); }
    @media (hover: hover) { .tlx:hover::before { background-color: #e1eed2; box-shadow: inset 0 0 0 1px #9fc27c; } }
    .tlx:active::before { transform: scale(.94); }
    /* Open, or reached by the keyboard: the tag fills in. */
    .tlx.is-open::before, .tlx:focus-visible::before { background-color: #3d6823; color: #fff; box-shadow: inset 0 0 0 1.5px #f5c518; }
    html.dark .tlx::before { background-color: #22301a; color: #cfe6b8; box-shadow: inset 0 0 0 1px #3d5a26; }
    @media (hover: hover) { html.dark .tlx:hover::before { background-color: #2b3d20; box-shadow: inset 0 0 0 1px #5a8236; } }
    html.dark .tlx.is-open::before, html.dark .tlx:focus-visible::before { background-color: #6b9f3d; color: #10170b; box-shadow: inset 0 0 0 1.5px #f5c518; }
    .tlx-w { display: inline !important; margin: 0 !important; padding: 0 !important; border: 0 !important; background: none !important; }
    /* Beside a chip: a little room after it too, and the chip's wrapper never shrinks or clips the pair. */
    .tlx.tlx-out { margin-right: .3em !important; overflow: visible !important; }
    .tlx-wc { flex: none !important; overflow: visible !important; white-space: nowrap !important; max-width: none !important; }
    .tlx-wc > .tlx.tlx-out { margin-right: 0 !important; }
    /* Settings > Taglish help, off. */
    html.tlx-off .tlx { display: none !important; }
    @media print { html .tlx { display: none !important; } }

    /* ---- what opens ----
       Both are popovers, so they rise above a modal <dialog> too (a sheet
       the tag sits in), and both are built on the first tap. */
    .tlx-pop { margin: 0; padding: 0; border: 0; background: transparent; color: inherit; overflow: visible; inset: auto; }
    .tlx-pop[hidden] { display: none; }
    .tlx-card:focus { outline: none; }
    .tlx-card { position: relative; z-index: 1; text-align: left; background: #fff; color: #1f2a17;
        border: 1px solid #d9e3cf; font-size: 1rem; letter-spacing: normal; text-transform: none; font-weight: 400; white-space: normal; }
    .tlx-head { display: flex; align-items: center; gap: .6rem; }
    .tlx-term { flex: 1 1 auto; min-width: 0; font-size: .8rem; font-weight: 800; line-height: 1.3; letter-spacing: .04em;
        font-variant-caps: small-caps; color: #3d6823; overflow-wrap: anywhere; }
    .tlx-lang { flex: none; display: inline-flex; align-items: center; gap: .32rem; padding: .14rem .5rem; border-radius: 999px;
        font-size: .62rem; font-weight: 800; letter-spacing: .05em; text-transform: uppercase; line-height: 1.2; color: #5b6650; background: #f2f5ee; }
    .tlx-tl { margin-top: .4rem; font-size: .9rem; line-height: 1.58; color: #1f2a17; }
    .tlx-tl.is-wait { color: #6b7280; }
    .tlx-tl.is-wait::after { content: ''; display: inline-block; width: .8em; height: .8em; margin-left: .45em; vertical-align: -.08em;
        border-radius: 999px; border: 2px solid #c9dab5; border-top-color: #4a7c2a; animation: tlxSpin .8s linear infinite; }
    @keyframes tlxSpin { to { transform: rotate(360deg); } }
    .tlx-ex { margin-top: .55rem; padding: .5rem .65rem; border-radius: .65rem; background: #f6f8f2; border-left: 3px solid #f5c518;
        font-size: .82rem; line-height: 1.5; color: #3f4a37; }
    .tlx-ex b { font-weight: 800; color: #2d5016; }
    .tlx-ex[hidden] { display: none; }
    /* Where to go next, when the words point somewhere (Anee does it for you: upgrade, buy credits). */
    .tlx-links { display: flex; flex-wrap: wrap; gap: .45rem; margin-top: .7rem; }
    .tlx-links[hidden] { display: none; }
    .tlx-link { display: inline-flex; align-items: center; gap: .3rem; min-height: 2.1rem; padding: .3rem .8rem; border-radius: 999px;
        font-size: .8rem; font-weight: 800; line-height: 1.2; text-decoration: none; color: #2d5016; background: #eef5e6; border: 1px solid #cfe0bd;
        transition: background-color .28s cubic-bezier(.22,1,.36,1), border-color .28s cubic-bezier(.22,1,.36,1); }
    .tlx-link:first-child { color: #fff; background: #3d6823; border-color: #3d6823; }
    .tlx-link::after { content: '\203A'; font-size: 1.05em; line-height: 1; }
    @media (hover: hover) { .tlx-link:hover { background: #e0ecd2; } .tlx-link:first-child:hover { background: #2d5016; } }
    .tlx-sheet .tlx-link { min-height: 2.6rem; font-size: .9rem; padding: .45rem 1rem; }
    .tlx-tl.is-fresh, .tlx-ex.is-fresh, .tlx-links.is-fresh { animation: tlxIn .28s cubic-bezier(.22,1,.36,1) both; }
    .tlx-ex.is-fresh, .tlx-links.is-fresh { animation-delay: .06s; }
    @keyframes tlxIn { from { opacity: 0; transform: translateY(3px); } }

    /* The tooltip: anchored to the tag, flips above or below, kept inside the screen. */
    .tlx-tip { position: fixed; left: 0; top: 0; width: max-content; max-width: min(22rem, calc(100vw - 16px));
        opacity: 0; transform: translateY(var(--tlx-dy, 6px)) scale(.86); transform-origin: var(--tlx-ox, 50%) var(--tlx-oy, 100%);
        transition: opacity .22s cubic-bezier(.22,1,.36,1), transform .28s cubic-bezier(.22,1,.36,1); pointer-events: none; }
    .tlx-tip.is-on { opacity: 1; transform: none; pointer-events: auto; }
    .tlx-tip .tlx-card { padding: .65rem .85rem .75rem; border-radius: .95rem;
        box-shadow: 0 22px 44px -18px rgb(16 28 8 / .5), 0 4px 12px -6px rgb(16 28 8 / .18); }
    .tlx-arrow { position: absolute; left: var(--tlx-ax, 50%); bottom: -6px; width: 13px; height: 13px; margin-left: -6.5px; transform: rotate(45deg);
        background: #fff; border: 1px solid #d9e3cf; border-top-color: transparent; border-left-color: transparent; border-radius: 0 0 3px 0;
        box-shadow: 3px 3px 6px -2px rgb(16 28 8 / .22); }
    /* Below the tag the arrow sits on the card's flag coloured top edge, so it
       wears the colour of the stripe it rises from. */
    .tlx-tip.is-below .tlx-arrow { bottom: auto; top: -5px; border: 0; border-radius: 2.5px 0 0 0; background: var(--tlx-abg, #fff); box-shadow: none; }
    .tlx-tip .tlx-card::before { content: ''; position: absolute; left: 0; right: 0; top: 0; height: 3px; border-radius: .95rem .95rem 0 0;
        background: linear-gradient(90deg, #0038a8 0 33.4%, #f5c518 33.4% 66.7%, #ce1126 66.7%); }

    /* The phone's sheet: a card that slides up over a soft dim. */
    .tlx-sheet { position: fixed; inset: 0; width: 100%; height: 100%; max-width: none; max-height: none; }
    .tlx-scrim { position: absolute; inset: 0; background: rgb(10 18 6 / .42); opacity: 0; touch-action: none;
        transition: opacity .28s cubic-bezier(.22,1,.36,1); }
    .tlx-sheet .tlx-card { position: absolute; left: .6rem; right: .6rem; bottom: calc(.6rem + env(safe-area-inset-bottom, 0px));
        max-width: 34rem; margin: 0 auto; max-height: 72vh; max-height: 72dvh; overflow-y: auto; overscroll-behavior: contain;
        padding: .45rem 1.15rem 1rem; border-radius: 1.35rem; box-shadow: 0 -18px 48px -18px rgb(10 18 6 / .55);
        transform: translateY(calc(100% + 1.5rem)); transition: transform .34s cubic-bezier(.22,1,.36,1); }
    .tlx-sheet.is-on .tlx-scrim { opacity: 1; }
    .tlx-sheet.is-on .tlx-card { transform: none; }
    .tlx-grip { width: 2.6rem; height: .3rem; margin: .1rem auto .7rem; border-radius: 999px; background: #d7dfcd; }
    .tlx-sheet .tlx-term { font-size: .9rem; }
    .tlx-sheet .tlx-tl { margin-top: .55rem; font-size: 1.02rem; line-height: 1.62; }
    .tlx-sheet .tlx-ex { font-size: .9rem; margin-top: .7rem; padding: .6rem .75rem; }
    .tlx-close { display: block; width: 100%; margin-top: .95rem; min-height: 2.9rem; border-radius: 999px; border: 0; cursor: pointer;
        font-size: .95rem; font-weight: 800; color: #2d5016; background: #eef5e6; transition: background-color .28s cubic-bezier(.22,1,.36,1); }
    .tlx-close:active { background: #e0ecd2; }

    html.dark .tlx-card { background: #1a2214; color: #e8efe1; border-color: #2f4020; }
    html.dark .tlx-arrow { background: #1a2214; border-color: #2f4020; }
    html.dark .tlx-tip:not(.is-below) .tlx-arrow { border-top-color: transparent; border-left-color: transparent; }
    html.dark .tlx-term { color: #a5c97e; }
    html.dark .tlx-lang { color: #b9caa8; background: #22301a; }
    html.dark .tlx-tl { color: #e8efe1; }
    html.dark .tlx-tl.is-wait { color: #9fb08e; }
    html.dark .tlx-ex { background: #222d1a; color: #cdd8c0; }
    html.dark .tlx-ex b { color: #c9e0ae; }
    html.dark .tlx-link { color: #e8efe1; background: #22301a; border-color: #3d5a26; }
    html.dark .tlx-link:first-child { color: #10170b; background: #8fc25e; border-color: #8fc25e; }
    html.dark .tlx-grip { background: #3a4a2c; }
    html.dark .tlx-close { background: #2b3a1c; color: #e8efe1; }
    html.dark .tlx-tip .tlx-card { box-shadow: 0 22px 44px -18px rgb(0 0 0 / .8), 0 4px 12px -6px rgb(0 0 0 / .5); }

    @media (prefers-reduced-motion: reduce) {
        .tlx::before, .tlx-tip, .tlx-scrim, .tlx-sheet .tlx-card, .tlx-close, .tlx-link { transition: none; }
        .tlx-tl.is-wait::after, .tlx-tl.is-fresh, .tlx-ex.is-fresh, .tlx-links.is-fresh { animation: none; }
    }
    html.sm-still .tlx::before, html.sm-still .tlx-tip, html.sm-still .tlx-scrim, html.sm-still .tlx-sheet .tlx-card { transition: none; }
</style>
<script>
(() => {
    if (window.aneeTl) return;
    const BOOT = @json(\App\Support\Tagalog::boot());
    const KEYS = new Set(BOOT.keys ? BOOT.keys.split(',') : []);
    const root = document.documentElement;
    const HOUSE = 280, SHEET = 340;
    let book = null, loading = null, failed = false;
    let cur = null;                 // { anchor, el, mode: 'tip' | 'sheet' }
    const tips = [];                // two, so one can leave while the next arrives
    let tipTurn = 0, sheetEl = null;

    const esc = (s) => String(s == null ? '' : s).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const still = () => root.classList.contains('sm-still') || matchMedia('(prefers-reduced-motion: reduce)').matches;
    const phone = () => matchMedia('(pointer: coarse)').matches || window.innerWidth < 640;
    const termOf = (key, term) => term || (book && book[key] ? book[key][0] : String(key).replace(/[-_]+/g, ' '));

    /* ---- the tag's markup, for Blade's twin in scripts ---- */
    const SAY = 'Taglish: ';
    const flag = (key, term) => KEYS.has(key)
        ? '<span class="tlx" role="button" tabindex="0" aria-expanded="false" data-tlk="' + esc(key) + '" aria-label="' + esc(SAY + termOf(key, term)) + '"></span>'
        : '';

    /* ---- the glossary, fetched once ---- */
    function load() {
        if (book) return Promise.resolve(book);
        if (!loading) {
            loading = fetch(BOOT.url, { credentials: 'omit' })
                .then((r) => { if (!r.ok) throw new Error('error ' + r.status); return r.json(); })
                .then((j) => { book = (j && j.t) || {}; failed = false; return book; })
                .catch((e) => { loading = null; failed = true; throw e; });
        }
        return loading;
    }

    /* ---- building what opens ---- */
    function cardHtml(sheet) {
        return '<div class="tlx-card"' + (sheet ? ' role="dialog" aria-modal="true" tabindex="-1"' : '') + '>'
            + (sheet ? '<div class="tlx-grip" aria-hidden="true"></div>' : '')
            + '<div class="tlx-head"><span class="tlx-term"></span><span class="tlx-lang">Taglish</span></div>'
            + '<p class="tlx-tl" aria-live="polite"></p>'
            + '<p class="tlx-ex" hidden><b>Halimbawa:</b> <span></span></p>'
            + '<p class="tlx-links" hidden></p>'
            + (sheet ? '<button type="button" class="tlx-close">Isara</button>' : '')
            + '</div>';
    }
    function popify(el) {
        el.hidden = true;
        if (typeof el.showPopover === 'function') el.setAttribute('popover', 'manual');
        else el.style.zIndex = '2147483000';
        document.body.appendChild(el);
        return el;
    }
    function makeTip() {
        const el = document.createElement('div');
        el.className = 'tlx-pop tlx-tip';
        el.setAttribute('data-tl-off', '');
        el.innerHTML = '<i class="tlx-arrow" aria-hidden="true"></i>' + cardHtml(false);
        return popify(el);
    }
    function makeSheet() {
        const el = document.createElement('div');
        el.className = 'tlx-pop tlx-sheet';
        el.setAttribute('data-tl-off', '');
        el.innerHTML = '<div class="tlx-scrim"></div>' + cardHtml(true);
        el.querySelector('.tlx-scrim').addEventListener('click', () => close({ refocus: true }));
        el.querySelector('.tlx-close').addEventListener('click', () => close({ refocus: true }));
        dragToClose(el.querySelector('.tlx-card'));
        return popify(el);
    }

    /* Pull the sheet down to let it go. */
    function dragToClose(card) {
        let y0 = null, dy = 0;
        card.addEventListener('touchstart', (e) => {
            if (card.scrollTop > 0 || e.touches.length !== 1) { y0 = null; return; }
            y0 = e.touches[0].clientY; dy = 0;
        }, { passive: true });
        card.addEventListener('touchmove', (e) => {
            if (y0 === null) return;
            dy = Math.max(0, e.touches[0].clientY - y0);
            if (dy > 4) { card.style.transition = 'none'; card.style.transform = 'translateY(' + dy + 'px)'; }
        }, { passive: true });
        const end = () => {
            if (y0 === null) return;
            y0 = null;
            card.style.transition = '';
            if (dy > 70) close({ refocus: true });
            card.style.transform = '';
        };
        card.addEventListener('touchend', end);
        card.addEventListener('touchcancel', end);
    }

    function fill(el, key, anchor, late) {
        const row = book && book[key];
        const card = el.querySelector('.tlx-card');
        const term = el.querySelector('.tlx-term'), p = el.querySelector('.tlx-tl'), ex = el.querySelector('.tlx-ex'), links = el.querySelector('.tlx-links');
        const h0 = late && el.classList.contains('tlx-sheet') ? card.offsetHeight : 0;
        term.textContent = row ? row[0] : (anchor.getAttribute('aria-label') || '').replace(SAY, '');
        p.classList.toggle('is-wait', !row);
        if (row) {
            p.textContent = row[1];
            ex.hidden = !row[2];
            ex.querySelector('span').textContent = row[2] || '';
            const go = Array.isArray(row[3]) ? row[3] : [];
            links.innerHTML = go.map((l) => '<a class="tlx-link" href="' + esc(l[1]) + '">' + esc(l[0]) + '</a>').join('');
            links.hidden = !go.length;
        } else {
            p.textContent = failed ? 'Walang koneksyon. Pindutin ulit ang Taglish mamaya.' : 'Sandali lang';
            ex.hidden = true;
            links.hidden = true;
        }
        // Words that arrive after the card opened fade in, and the sheet
        // grows to them instead of jumping.
        if (late && row && !still()) {
            [p, ex, links].forEach((n) => { n.classList.remove('is-fresh'); void n.offsetWidth; n.classList.add('is-fresh'); });
            if (h0 && card.animate) {
                const h1 = card.offsetHeight;
                if (Math.abs(h1 - h0) > 2) card.animate([{ height: h0 + 'px', overflow: 'hidden' }, { height: h1 + 'px', overflow: 'hidden' }], { duration: HOUSE, easing: 'cubic-bezier(.22,1,.36,1)' });
            }
        }
    }

    /* ---- placing the tooltip ---- */
    function place() {
        if (!cur || cur.mode !== 'tip') return;
        const { el, anchor } = cur;
        if (!anchor.isConnected || !anchor.getClientRects().length) { close(); return; }
        const rects = anchor.getClientRects(), r = rects[rects.length - 1];
        const vw = root.clientWidth, vh = window.innerHeight;
        if (r.bottom < 0 || r.top > vh || r.right < 0 || r.left > vw) { close(); return; }
        const fy = r.top + r.height / 2;                    // the tag's middle
        const fTop = fy - 9, fBottom = fy + 9;              // the tag is 17 px tall
        const w = el.offsetWidth, h = el.offsetHeight, gap = 11, pad = 8;
        const below = fTop - gap - h < pad && vh - fBottom > fTop;
        let top = below ? fBottom + gap : fTop - gap - h;
        top = Math.max(pad, Math.min(top, vh - h - pad));
        const tw = parseFloat(getComputedStyle(anchor).getPropertyValue('--tlx-w')) || 45;
        const cx = r.left + tw / 2;                         // the tag's centre
        const left = Math.max(pad, Math.min(cx - w / 2, vw - w - pad));
        const ax = Math.max(16, Math.min(cx - left, w - 16));
        el.style.left = Math.round(left) + 'px';
        el.style.top = Math.round(top) + 'px';
        el.classList.toggle('is-below', below);
        el.style.setProperty('--tlx-ax', ax + 'px');
        el.style.setProperty('--tlx-ox', ax + 'px');
        el.style.setProperty('--tlx-oy', below ? '0px' : h + 'px');
        el.style.setProperty('--tlx-dy', below ? '-6px' : '6px');
        el.style.setProperty('--tlx-abg', ax < w / 3 ? '#0038a8' : (ax < (2 * w) / 3 ? '#f5c518' : '#ce1126'));
    }
    let raf = 0;
    const follow = () => { if (!raf) raf = requestAnimationFrame(() => { raf = 0; place(); }); };

    /* ---- open and close ---- */
    function show(el, anchor) {
        // Inside an open modal dialog, live there: the rest of the page is inert under it.
        const host = anchor.closest('dialog[open]') || document.body;
        if (el.parentNode !== host) host.appendChild(el);
        el.__gen = (el.__gen || 0) + 1;
        el.hidden = false;
        if (el.showPopover) { try { if (!el.matches(':popover-open')) el.showPopover(); } catch (_) { /* not connected */ } }
    }
    function hide(el, gen) {
        if (gen !== el.__gen || el.classList.contains('is-on')) return;     // opened again meanwhile
        if (el.hidePopover) { try { el.hidePopover(); } catch (_) { /* already hidden */ } }
        el.hidden = true;
    }

    function open(anchor) {
        const key = anchor.getAttribute('data-tlk');
        if (!key || !KEYS.has(key)) return;
        if (cur && cur.anchor === anchor) { close({ refocus: true }); return; }
        const mode = phone() ? 'sheet' : 'tip';

        if (cur && cur.mode === 'sheet' && mode === 'sheet') {
            // A second tag while the sheet is up (only from a keyboard): new words, same sheet.
            cur.anchor.classList.remove('is-open'); cur.anchor.setAttribute('aria-expanded', 'false');
            cur.anchor = anchor;
        } else {
            if (cur) close();
            let el;
            if (mode === 'tip') { tipTurn = (tipTurn + 1) % 2; el = tips[tipTurn] || (tips[tipTurn] = makeTip()); }
            else el = sheetEl || (sheetEl = makeSheet());
            el.classList.remove('is-on');
            cur = { anchor, el, mode };
            show(el, anchor);
            if (mode === 'sheet') {
                window.registerOverlay?.('tlx', () => close({ fromBack: true }));
            } else {
                window.addEventListener('scroll', follow, true);
                window.addEventListener('resize', follow);
            }
        }
        const { el } = cur;
        anchor.classList.add('is-open');
        anchor.setAttribute('aria-expanded', 'true');
        fill(el, key, anchor);
        if (mode === 'tip') place();
        void el.offsetWidth;                            // the start state, painted
        requestAnimationFrame(() => {
            if (!cur || cur.el !== el) return;
            el.classList.add('is-on');
            if (mode === 'sheet') el.querySelector('.tlx-card').focus({ preventScroll: true });
        });

        if (!book) {
            load().then(() => { if (cur && cur.anchor === anchor) { fill(el, key, anchor, true); place(); } })
                .catch(() => { if (cur && cur.anchor === anchor) { fill(el, key, anchor, true); place(); } });
        }
    }

    function close(opts = {}) {
        if (!cur) return;
        const { el, anchor, mode } = cur;
        cur = null;
        anchor.classList.remove('is-open');
        anchor.setAttribute('aria-expanded', 'false');
        el.classList.remove('is-on');
        if (mode === 'tip') {
            window.removeEventListener('scroll', follow, true);
            window.removeEventListener('resize', follow);
        } else if (!opts.fromBack) {
            window.unregisterOverlay?.('tlx');
        }
        const gen = el.__gen;
        if (still()) hide(el, gen); else setTimeout(() => hide(el, gen), mode === 'sheet' ? SHEET : HOUSE);
        if (opts.refocus && anchor.isConnected) { try { anchor.focus({ preventScroll: true }); } catch (_) { /* gone */ } }
    }

    /* ---- the listeners, first in line ----
       Capture on window runs before anything the page wired, so a tag inside
       a tile link, a fold button, a label or a locked door opens only itself. */
    window.addEventListener('click', (e) => {
        const f = e.target && e.target.closest ? e.target.closest('.tlx') : null;
        if (!f) return;
        e.preventDefault();
        e.stopImmediatePropagation();
        open(f);
    }, true);
    window.addEventListener('pointerdown', (e) => {
        const f = e.target && e.target.closest ? e.target.closest('.tlx') : null;
        if (f) { load().catch(() => {}); return; }       // fetch while the finger is still down
        if (cur && cur.mode === 'tip' && !cur.el.contains(e.target)) close();
    }, true);
    window.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && cur) {
            e.preventDefault();
            e.stopImmediatePropagation();
            close({ refocus: true });
            return;
        }
        const t = e.target;
        if ((e.key === 'Enter' || e.key === ' ') && t && t.classList && t.classList.contains('tlx')) {
            e.preventDefault();
            e.stopImmediatePropagation();
            open(t);
        }
    }, true);

    /* ---- data-tl: markup drawn by scripts asks for a tag by key ----
       The tag goes right after the element's last word (not after trailing
       space, where the line could break and leave it alone). Kept there as
       scripts redraw: a redraw that wipes it gets it back. */
    const SKIP = /^(SCRIPT|STYLE|SVG|TEXTAREA|SELECT|OPTION|INPUT|IMG)$/;
    function lastText(el) {
        const w = document.createTreeWalker(el, NodeFilter.SHOW_TEXT, {
            acceptNode(n) {
                if (!/\S/.test(n.nodeValue)) return NodeFilter.FILTER_REJECT;
                const p = n.parentElement;
                if (!p || p.closest('.tlx, svg') || (p !== el && p.closest('[data-tl]') !== el)) return NodeFilter.FILTER_REJECT;
                return NodeFilter.FILTER_ACCEPT;
            },
        });
        let last = null, n;
        while ((n = w.nextNode())) last = n;
        return last;
    }
    /* The word sits in a chip (a small filled, rounded box like INSECT or
       IRAC 9B): the tag goes beside the chip, never inside it, where it
       spilled over the chip's edges (2026-10-17, the owner). Looked for from
       the word up to the element that asked. */
    const CLEAR = /^(transparent|rgba\([^)]*,\s*0\))$/;
    function chipOf(box, el) {
        for (let p = box; p && p.nodeType === 1; p = p.parentElement) {
            const cs = getComputedStyle(p);
            if ((cs.backgroundImage !== 'none' || !CLEAR.test(cs.backgroundColor)) && parseFloat(cs.borderTopLeftRadius) >= 6 && p.offsetHeight <= 30) return p;
            if (p === el) break;
        }
        return null;
    }
    function attach(el) {
        if (!el.isConnected || SKIP.test(el.tagName.toUpperCase())) return;
        const key = el.getAttribute('data-tl');
        const own = el.__tlx;
        if (own && own.isConnected && (el.contains(own) || own.previousSibling === el) && own.getAttribute('data-tlk') === key) return;
        if (own && own.isConnected) own.remove();
        el.__tlx = null;
        if (!key || !KEYS.has(key) || el.closest('[data-tl-off]')) return;
        const term = el.getAttribute('data-tl-term') || (el.textContent || '').replace(/\s+/g, ' ').trim().slice(0, 60) || null;
        const tpl = document.createElement('span');
        tpl.innerHTML = flag(key, term);
        const f = tpl.firstChild;
        const t = lastText(el);
        const chip = t ? chipOf(t.parentNode, el) : null;
        if (chip) {
            const box = chip.parentNode;
            f.classList.add('tlx-out');
            if (box.classList && box.classList.contains('tlx-wc')) {
                box.appendChild(f);
            } else if (/flex|grid/.test(getComputedStyle(box).display)) {
                // In a flex row or column the tag would be an item of its own,
                // a gap away or on a line below: one wrapper keeps the pair.
                const w = document.createElement('span');
                w.className = 'tlx-w tlx-wc';
                box.insertBefore(w, chip);
                w.appendChild(chip);
                w.appendChild(f);
            } else {
                box.insertBefore(f, chip.nextSibling);
            }
        } else if (t) {
            const end = t.nodeValue.replace(/\s+$/, '').length;
            if (end < t.nodeValue.length) t.splitText(end);
            const box = t.parentNode;
            if (/flex|grid/.test(getComputedStyle(box).display)) {
                // Loose text in a flex row is an item of its own: the tag
                // would be another, a gap away. One wrapper keeps them as one.
                const w = document.createElement('span');
                w.className = 'tlx-w';
                box.insertBefore(w, t);
                w.appendChild(t);
                w.appendChild(f);
            } else {
                box.insertBefore(f, t.nextSibling);
            }
        } else {
            el.appendChild(f);
        }
        el.__tlx = f;
    }
    const queued = new Set();
    let flushing = false;
    const queue = (el) => {
        queued.add(el);
        if (!flushing) { flushing = true; queueMicrotask(flush); }
    };
    function flush() {
        flushing = false;
        const list = Array.from(queued); queued.clear();
        list.forEach(attach);
    }
    function scan(scope) {
        const s = scope || document;
        if (s.nodeType === 1 && s.hasAttribute('data-tl')) queue(s);
        (s.querySelectorAll ? s.querySelectorAll('[data-tl]') : []).forEach(queue);
    }
    const mo = new MutationObserver((records) => {
        for (const m of records) {
            if (m.type === 'attributes') { queue(m.target); continue; }
            const host = m.target.nodeType === 1 ? m.target.closest('[data-tl]') : null;
            if (host) queue(host);
            // A chip taken away alone leaves no tag beside it (moved into its wrapper is not taken away).
            for (const n of m.removedNodes) {
                if (n.nodeType === 1 && !n.isConnected && n.__tlx && n.__tlx.isConnected && !n.contains(n.__tlx)) n.__tlx.remove();
            }
            for (const n of m.addedNodes) {
                if (n.nodeType !== 1 || n.classList.contains('tlx')) continue;
                if (n.hasAttribute('data-tl')) queue(n);
                if (n.firstElementChild) n.querySelectorAll('[data-tl]').forEach(queue);
            }
        }
    });
    const start = () => {
        scan(document);
        mo.observe(document.documentElement, { childList: true, subtree: true, attributes: true, attributeFilter: ['data-tl'] });
    };
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', start, { once: true });
    else start();

    window.aneeTl = {
        on: true,
        has: (key) => KEYS.has(key),
        /** The tag's HTML for a key ('' for an unknown one). Write it straight after the last word. */
        flag,
        /** An escaped label with its tag. */
        label: (text, key) => esc(text) + flag(key, text),
        open: (anchor) => anchor && open(anchor),
        close: () => close(),
        scan,
        load,
    };
})();
</script>
@else
<script>
    /* No Taglish help on this face: the helpers answer, drawing nothing. */
    window.aneeTl = window.aneeTl || { on: false, has: () => false, flag: () => '',
        label: (t) => String(t == null ? '' : t).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c])),
        open() {}, close() {}, scan() {}, load: () => Promise.resolve({}) };
</script>
@endif
