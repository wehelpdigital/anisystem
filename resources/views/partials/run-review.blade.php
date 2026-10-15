{{--
    The review before a run (2026-10-16, the owner: "Ready to run it?"
    looked bad). One card for every analysis: a head with what is being
    asked, the answers as rows (a row with a step goes back to it), and the
    lot with what it said and what it has not said yet.

    window.aneeReview.html({
        icon, kicker, title, sub,                 the head
        rows: [{ e, k, v, go, lot }],             e icon, k label, v value (string or list),
                                                  go the step to go back to, lot: from the lot
        lot: { name, place, size, said: [], missing: [] },
        editAttr: 'data-lot-edit',                the attribute the page's "edit the lot" listens for
    }) -> markup. The page listens for [data-rv-go] on its host and shows that step.
--}}
@once
<style>
    .rv { --rv-e: cubic-bezier(.22,1,.36,1); display: grid; gap: .7rem; margin: .35rem 0 1rem; text-align: left; }
    .rv > * { animation: rvIn .34s var(--rv-e) both; }
    .rv > *:nth-child(2) { animation-delay: .05s; }
    .rv > *:nth-child(3) { animation-delay: .1s; }
    @keyframes rvIn { from { opacity: 0; transform: translateY(6px); } }
    .rv-hero { display: flex; align-items: center; gap: .8rem; padding: .85rem .95rem; border-radius: 1.1rem; background: var(--color-brand-50); border: 1px solid var(--color-brand-100); }
    .rv-hi { flex: none; width: 2.9rem; height: 2.9rem; border-radius: .95rem; display: grid; place-items: center; font-size: 1.55rem; background: var(--color-white); box-shadow: 0 1px 2px rgba(0,0,0,.06); }
    .rv-ht { min-width: 0; }
    .rv-ht small { display: block; font-size: .64rem; font-weight: 800; letter-spacing: .07em; text-transform: uppercase; color: var(--color-brand-700); }
    .rv-ht b { display: block; font-size: 1.02rem; font-weight: 800; line-height: 1.3; color: var(--color-gray-900); overflow-wrap: anywhere; }
    .rv-ht span { display: block; font-size: .8rem; color: var(--color-gray-600); margin-top: .1rem; }
    .rv-list { display: grid; border: 1px solid var(--color-gray-200); border-radius: 1.1rem; overflow: hidden; background: var(--color-white); }
    .rv-row { display: flex; align-items: center; gap: .7rem; width: 100%; padding: .62rem .8rem; text-align: left; background: transparent; border: 0; min-width: 0; font: inherit; color: inherit; }
    .rv-row + .rv-row { border-top: 1px solid var(--color-gray-100); }
    button.rv-row { cursor: pointer; transition: background-color .28s var(--rv-e); }
    button.rv-row:hover { background: var(--color-gray-50); }
    button.rv-row:focus-visible { outline: 2px solid var(--color-brand-500); outline-offset: -2px; }
    .rv-e { flex: none; width: 2rem; height: 2rem; border-radius: .7rem; display: grid; place-items: center; font-size: 1rem; background: var(--color-gray-100); }
    .rv-t { flex: 1; min-width: 0; }
    .rv-t small { display: block; font-size: .64rem; font-weight: 800; letter-spacing: .06em; text-transform: uppercase; color: var(--color-gray-400); }
    .rv-t b { display: block; font-size: .88rem; font-weight: 700; line-height: 1.35; color: var(--color-gray-900); overflow-wrap: anywhere; }
    .rv-t b.is-none { font-weight: 600; color: var(--color-gray-400); }
    .rv-c { flex: none; font-size: .72rem; font-weight: 800; color: var(--color-brand-600); }
    .rv-c.is-lot { font-weight: 700; color: var(--color-gray-400); }
    .rv-lot { border: 1px solid var(--color-gray-200); border-radius: 1.1rem; padding: .8rem .85rem; background: var(--color-white); }
    .rv-lot-h { display: flex; align-items: center; gap: .7rem; }
    .rv-lot-h .rv-e { background: var(--color-brand-50); }
    .rv-lot-h .rv-t span { display: block; font-size: .76rem; color: var(--color-gray-500); overflow-wrap: anywhere; }
    .rv-btn { flex: none; display: inline-flex; align-items: center; gap: .3rem; padding: .4rem .7rem; border-radius: 999px; font-size: .74rem; font-weight: 800;
        color: var(--color-brand-700); background: var(--color-brand-50); border: 1px solid var(--color-brand-100); cursor: pointer; transition: background-color .28s var(--rv-e), transform .28s var(--rv-e); }
    .rv-btn:hover { background: var(--color-brand-100); transform: translateY(-1px); }
    .rv-btn svg { width: .85rem; height: .85rem; }
    .rv-chips { display: flex; flex-wrap: wrap; gap: .35rem; margin-top: .65rem; }
    .rv-chips span { font-size: .74rem; font-weight: 600; line-height: 1.2; padding: .3rem .6rem; border-radius: 999px; background: var(--color-gray-100); color: var(--color-gray-700); }
    .rv-none { margin-top: .55rem; font-size: .78rem; color: var(--color-gray-500); }
    .rv-miss { display: flex; align-items: flex-start; gap: .6rem; margin-top: .7rem; padding: .65rem .7rem; border-radius: .9rem; background: #fff8e6; border: 1px solid #f5dfa3; }
    .rv-miss > i { flex: none; width: 1.5rem; height: 1.5rem; border-radius: 999px; display: grid; place-items: center; font-style: normal; font-size: .8rem; font-weight: 900; color: #8a5a00; background: #fde9b2; }
    .rv-miss div { flex: 1; min-width: 0; font-size: .78rem; line-height: 1.4; color: #6b4a00; }
    .rv-miss div b { display: block; font-size: .8rem; color: #5a3d00; }
    .rv-miss .rv-btn { align-self: center; color: #6b4a00; background: #fff; border-color: #f0d38a; }
    @media (max-width: 520px) { .rv-miss { flex-wrap: wrap; } .rv-miss .rv-btn { margin-left: 2.1rem; } }
    html.dark .rv-hi { box-shadow: none; }
    html.dark .rv-miss { background: #241d0c; border-color: #4a3b14; }
    html.dark .rv-miss > i { color: #f3cf7a; background: #3a2e10; }
    html.dark .rv-miss div, html.dark .rv-miss div b { color: #f0dcae; }
    html.dark .rv-miss .rv-btn { color: #f3cf7a; background: #2d250f; border-color: #4a3b14; }
    @media (prefers-reduced-motion: reduce) { .rv > * { animation: none; } }
    html.sm-still .rv > * { animation: none; }
</style>
<script>
(() => {
    if (window.aneeReview) return;
    // No dashes in what the farmer reads: a label's " — " reads as a comma here.
    const plain = (s) => String(s ?? '').replace(/ — /g, ', ');
    const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const PEN = '<svg fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4 20h4L18.5 9.5a2.1 2.1 0 00-4-4L4 16v4zM13.5 6.5l4 4"/></svg>';
    const andList = (a) => a.length < 2 ? (a[0] || '') : a.slice(0, -1).join(', ') + ' and ' + a[a.length - 1];
    function row(r) {
        const v = plain(Array.isArray(r.v) ? r.v.filter(Boolean).join(', ') : r.v);
        const val = v ? '<b>' + esc(v) + '</b>' : '<b class="is-none">' + esc(r.none || 'Not given') + '</b>';
        const inner = '<span class="rv-e" aria-hidden="true">' + esc(r.e || '•') + '</span><span class="rv-t"><small>' + esc(r.k) + '</small>' + val + '</span>';
        if (r.go != null) return '<button type="button" class="rv-row" data-rv-go="' + Number(r.go) + '" aria-label="' + esc(r.k + ': ' + (v || r.none || 'Not given') + '. Change it') + '">' + inner + '<span class="rv-c">Change</span></button>';
        return '<div class="rv-row">' + inner + (r.lot ? '<span class="rv-c is-lot">From the lot</span>' : '') + '</div>';
    }
    function html(o) {
        o = o || {};
        const edit = o.editAttr || 'data-lot-edit';
        let h = '<div class="rv">';
        h += '<div class="rv-hero"><span class="rv-hi" aria-hidden="true">' + esc(o.icon || '🌱') + '</span><div class="rv-ht">'
            + (o.kicker ? '<small>' + esc(o.kicker) + '</small>' : '') + '<b>' + esc(plain(o.title)) + '</b>' + (o.sub ? '<span>' + esc(plain(o.sub)) + '</span>' : '') + '</div></div>';
        const rows = (o.rows || []).filter((r) => r && (r.v || r.none || r.go != null));
        if (rows.length) h += '<div class="rv-list">' + rows.map(row).join('') + '</div>';
        const L = o.lot;
        if (L) {
            const said = (L.said || []).filter(Boolean), miss = (L.missing || []).filter(Boolean);
            h += '<div class="rv-lot"><div class="rv-lot-h"><span class="rv-e" aria-hidden="true">🗺️</span><span class="rv-t"><small>The lot</small><b>' + esc(L.name) + '</b>'
                + ([L.place, L.size].filter(Boolean).length ? '<span>' + esc([L.place, L.size].filter(Boolean).join(' · ')) + '</span>' : '') + '</span>'
                + '<button type="button" class="rv-btn" ' + edit + '>' + PEN + 'Edit</button></div>';
            h += said.length ? '<div class="rv-chips" aria-label="What the lot says">' + said.map((s) => '<span>' + esc(s) + '</span>').join('') + '</div>'
                : '<p class="rv-none">This lot has no field answers yet.</p>';
            if (miss.length) {
                h += '<div class="rv-miss"><i aria-hidden="true">!</i><div><b>Not in the lot yet</b>' + esc(andList(miss).replace(/^./, (c) => c.toUpperCase()))
                    + '. Anee leaves them out until the lot has them.</div><button type="button" class="rv-btn" ' + edit + '>Answer them</button></div>';
            }
            h += '</div>';
        }
        return h + '</div>';
    }
    window.aneeReview = { html, andList };
})();
</script>
@endonce
