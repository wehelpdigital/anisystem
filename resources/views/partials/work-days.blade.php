{{--
    A worker's week (2026-10-28, the owner: "weekdays they do not work / work
    should be selectable if half day am/pm or wholeday as well"). Seven day
    chips; a tap opens a small menu: whole day, morning only, afternoon only,
    day off. The days off are stored where they always were (the season
    card's off days, the roster's offWeekdays); the half days are `dayParts`,
    weekday => am|pm (App\Support\WorkDays). Used by the Anee Auto wizard,
    Global Workers and a season's Workers page, so the three read the same.

    window.workDays.html(off, parts, name) -> the chips
    window.workDays.read(group)             -> { off: [..], parts: { dow: 'am'|'pm' } }
    window.workDays.partsOf(x)              -> a clean parts map from what the server sent
    window.workDays.words(off, parts)       -> "Off Sun · Mon AM" for a summary
    A pick fires `workdays:change` (bubbles) on the group.
--}}
@once
<style>
    .wd { display: grid; grid-template-columns: repeat(7, minmax(0, 1fr)); gap: .3rem; max-width: 27rem; }
    .wd-d { display: grid; justify-items: center; gap: .18rem; padding: .42rem 0 .38rem; border-radius: .75rem; font-size: .74rem; font-weight: 800; line-height: 1.1;
        color: #fff; background: var(--color-brand-600, #4a7c2a); border: 1px solid var(--color-brand-600, #4a7c2a);
        transition: background-color .28s cubic-bezier(.22,1,.36,1), color .28s cubic-bezier(.22,1,.36,1), border-color .28s cubic-bezier(.22,1,.36,1), transform .2s cubic-bezier(.22,1,.36,1); }
    .wd-d:active { transform: scale(.95); }
    .wd-d small { font-size: .6rem; font-weight: 700; letter-spacing: .02em; }
    .wd-d i, .wd-menu i { display: flex; gap: 2px; width: 68%; height: .26rem; }
    .wd-d i::before, .wd-d i::after, .wd-menu i::before, .wd-menu i::after { content: ''; flex: 1; border-radius: 999px; background: currentColor; transition: opacity .28s cubic-bezier(.22,1,.36,1); }
    .wd-d.is-am, .wd-d.is-pm { color: #2d5016; background: #eef6e4; border-color: #b7d597; }
    .wd-d.is-am i::after, .wd-d.is-pm i::before, .wd-menu [data-wd-pick="am"] i::after, .wd-menu [data-wd-pick="pm"] i::before { opacity: .18; }
    .wd-d.is-off { color: var(--color-gray-400, #9ca3af); background: var(--color-gray-100, #f3f4f6); border-color: var(--color-gray-100, #f3f4f6); }
    .wd-d.is-off i::before, .wd-d.is-off i::after, .wd-menu [data-wd-pick="off"] i::before, .wd-menu [data-wd-pick="off"] i::after { opacity: .2; }
    .wd-d[aria-expanded="true"] { box-shadow: 0 0 0 3px rgb(107 159 61 / .35); }
    .wd-menu { position: fixed; z-index: 400; width: 13rem; padding: .35rem; border-radius: 1rem; background: #fff; border: 1px solid var(--color-gray-200, #e5e7eb);
        box-shadow: 0 18px 40px -16px rgb(0 0 0 / .4); opacity: 0; transform: translateY(-4px) scale(.98); transform-origin: top center;
        transition: opacity .2s ease, transform .28s cubic-bezier(.22,1,.36,1); }
    .wd-menu.is-up { transform-origin: bottom center; transform: translateY(4px) scale(.98); }
    .wd-menu.is-on { opacity: 1; transform: none; }
    .wd-menu[hidden] { display: none; }
    .wd-menu p { padding: .35rem .6rem .3rem; font-size: .68rem; font-weight: 800; letter-spacing: .06em; text-transform: uppercase; color: var(--color-gray-400, #9ca3af); }
    .wd-menu button { display: flex; align-items: center; gap: .65rem; width: 100%; padding: .55rem .6rem; border-radius: .7rem; text-align: left; font-size: .86rem; font-weight: 700;
        color: var(--color-gray-800, #1f2937); transition: background-color .2s ease; }
    .wd-menu button i { flex: none; width: 1.6rem; color: var(--color-brand-600, #4a7c2a); }
    .wd-menu button b { font-weight: 700; }
    .wd-menu button small { display: block; font-size: .7rem; font-weight: 600; color: var(--color-gray-500, #6b7280); }
    .wd-menu button:hover, .wd-menu button:focus-visible { background: #f3f8ec; outline: none; }
    .wd-menu button[aria-checked="true"] { background: #eef6e4; }
    .wd-menu button[aria-checked="true"]::after { content: '✓'; margin-left: auto; font-weight: 900; color: var(--color-brand-600, #4a7c2a); }
    html.dark .wd-d.is-am, html.dark .wd-d.is-pm { color: #c5e09f; background: #1c2616; border-color: #3d5a24; }
    html.dark .wd-d.is-off { color: #6b7563; background: #1a1f16; border-color: #262e20; }
    html.dark .wd-menu { background: #161c12; border-color: #2b3a1c; }
    html.dark .wd-menu button { color: #e3ead9; }
    html.dark .wd-menu button small { color: #9aa78d; }
    html.dark .wd-menu button:hover, html.dark .wd-menu button:focus-visible, html.dark .wd-menu button[aria-checked="true"] { background: #1f2a17; }
    html.dark .wd-menu button i { color: #a5c97e; }
    @media (prefers-reduced-motion: reduce) { .wd-d, .wd-d i::before, .wd-d i::after, .wd-menu { transition: none; } }
    html.sm-still .wd-d, html.sm-still .wd-menu { transition: none; }
</style>
<script>
window.workDays = window.workDays || (() => {
    const DAY = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
    const LONG = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    const SHORT = { whole: 'Whole', am: 'AM', pm: 'PM', off: 'Off' };
    const SAY = { whole: 'whole day', am: 'morning only', pm: 'afternoon only', off: 'day off' };
    const PICKS = [['whole', 'Whole day', 'Morning and afternoon'], ['am', 'Morning only', 'AM, a half day'], ['pm', 'Afternoon only', 'PM, a half day'], ['off', 'Day off', 'Does not work this day']];
    const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const partsOf = (x) => { const o = {}; if (x && typeof x === 'object') Object.entries(x).forEach(([k, v]) => { const d = Number(k); if (d >= 0 && d <= 6 && (v === 'am' || v === 'pm')) o[d] = v; }); return o; };
    const stateOf = (off, parts, d) => (off || []).map(Number).includes(d) ? 'off' : (partsOf(parts)[d] || 'whole');
    const chip = (d, st, name) => '<button type="button" class="wd-d is-' + st + '" data-wd-day="' + d + '" data-state="' + st + '" aria-haspopup="menu" aria-expanded="false" aria-label="'
        + esc((name ? name + ', ' : '') + LONG[d] + ': ' + SAY[st] + '. Change') + '"><span>' + DAY[d] + '</span><i aria-hidden="true"></i><small>' + SHORT[st] + '</small></button>';
    const html = (off, parts, name) => '<div class="wd" role="group" data-wd' + (name ? ' data-wd-name="' + esc(name) + '"' : '') + ' aria-label="' + esc((name ? name + ': ' : '') + 'days they work') + '">'
        + DAY.map((_, d) => chip(d, stateOf(off, parts, d), name)).join('') + '</div>';
    const read = (group) => {
        const out = { off: [], parts: {} };
        group?.querySelectorAll('[data-wd-day]').forEach((b) => { const d = Number(b.dataset.wdDay), st = b.dataset.state; if (st === 'off') out.off.push(d); else if (st === 'am' || st === 'pm') out.parts[d] = st; });
        return out;
    };
    const words = (off, parts) => {
        const p = partsOf(parts), o = (off || []).map(Number).sort((a, b) => a - b);
        return [o.length ? 'Off ' + o.map((d) => DAY[d]).join(', ') : '', ...Object.keys(p).map(Number).sort((a, b) => a - b).filter((d) => !o.includes(d)).map((d) => DAY[d] + ' ' + SHORT[p[d]])].filter(Boolean).join(' · ');
    };
    const set = (b, st) => {
        const g = b.closest('[data-wd]'), name = g?.dataset.wdName || '', d = Number(b.dataset.wdDay);
        b.dataset.state = st;
        b.className = 'wd-d is-' + st;
        b.querySelector('small').textContent = SHORT[st];
        b.setAttribute('aria-label', (name ? name + ', ' : '') + LONG[d] + ': ' + SAY[st] + '. Change');
    };

    // One menu for every chip on the page. Inside a <dialog> it moves into it, so it shows over the top layer.
    let menu = null, at = null;
    const still = () => matchMedia('(prefers-reduced-motion: reduce)').matches || document.documentElement.classList.contains('sm-still');
    const close = (back) => {
        if (!menu || menu.hidden) return;
        const was = at; at = null;
        was?.setAttribute('aria-expanded', 'false');
        menu.classList.remove('is-on');
        if (still()) menu.hidden = true; else setTimeout(() => { if (!at) menu.hidden = true; }, 200);
        if (back) was?.focus();
    };
    const open = (b) => {
        if (!menu) {
            menu = document.createElement('div');
            menu.className = 'wd-menu'; menu.hidden = true; menu.setAttribute('role', 'menu');
            menu.addEventListener('click', (e) => {
                const p = e.target.closest('[data-wd-pick]'); if (!p || !at) return;
                const b2 = at, st = p.dataset.wdPick, changed = b2.dataset.state !== st;
                set(b2, st);
                close(true);
                if (changed) b2.closest('[data-wd]')?.dispatchEvent(new CustomEvent('workdays:change', { bubbles: true, detail: { day: Number(b2.dataset.wdDay), state: st } }));
            });
            menu.addEventListener('keydown', (e) => {
                const items = [...menu.querySelectorAll('[data-wd-pick]')], i = items.indexOf(document.activeElement);
                if (e.key === 'ArrowDown' || e.key === 'ArrowUp') { e.preventDefault(); items[(i + (e.key === 'ArrowDown' ? 1 : items.length - 1)) % items.length].focus(); }
            });
        }
        (b.closest('dialog') || document.body).appendChild(menu);
        at?.setAttribute('aria-expanded', 'false');
        at = b; b.setAttribute('aria-expanded', 'true');
        const d = Number(b.dataset.wdDay), cur = b.dataset.state;
        menu.setAttribute('aria-label', LONG[d]);
        menu.innerHTML = '<p>' + LONG[d] + '</p>' + PICKS.map(([k, t, s]) => '<button type="button" role="menuitemradio" data-wd-pick="' + k + '" aria-checked="' + (k === cur) + '"><i aria-hidden="true"></i><span><b>' + t + '</b><small>' + s + '</small></span></button>').join('');
        menu.hidden = false; menu.classList.remove('is-on');
        const r = b.getBoundingClientRect(), w = menu.offsetWidth, h = menu.offsetHeight, vw = document.documentElement.clientWidth, vh = window.innerHeight;
        const up = r.bottom + 6 + h > vh - 8 && r.top - 6 - h > 8;
        menu.classList.toggle('is-up', up);
        menu.style.left = Math.round(Math.min(Math.max(8, r.left + r.width / 2 - w / 2), vw - w - 8)) + 'px';
        menu.style.top = Math.round(up ? r.top - 6 - h : Math.min(r.bottom + 6, vh - h - 8)) + 'px';
        requestAnimationFrame(() => menu.classList.add('is-on'));
        if (!matchMedia('(hover: none)').matches) menu.querySelector('[aria-checked="true"]')?.focus();
    };
    document.addEventListener('click', (e) => {
        const b = e.target.closest('[data-wd-day]');
        if (b && b.closest('[data-wd]')) { if (at === b) close(); else open(b); return; }
        if (menu && !menu.hidden && !menu.contains(e.target)) close();
    });
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && menu && !menu.hidden) { e.stopPropagation(); close(true); } }, true);
    // A scroll or a turn of the phone moves the chip from under the menu: close it.
    window.addEventListener('scroll', (e) => { if (menu && !menu.hidden && !menu.contains(e.target)) close(); }, true);
    window.addEventListener('resize', () => close());

    return { html, read, partsOf, words, DAY };
})();
</script>
@endonce
