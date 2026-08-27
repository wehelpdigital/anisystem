{{-- The mutual co-farmers sheet, shared by every "N mutual co-farmers"
     link: tap the number anywhere — a wall post, a member card, a profile —
     and the faces behind it slide up. Delegated + guarded singleton. --}}
@once
<style>
    /* Each shared face is a card wearing the house gradient as its edge:
       the wrapper IS the border — a thicker band along the top, a hairline
       around the rest — and the white card sits inside it. */
    /* The gradient edge, and the top of it no thicker than the rest: at 4px
       it read as a coloured bar laid on the card rather than as the card's
       own rim. */
    .mut-card { padding: 2px 1.5px 1.5px; border-radius: 1rem; margin-bottom: .65rem;
        background: linear-gradient(120deg, #2f5219, #8fc267 45%, #4a7c2a 75%, #a9d383);
        background-size: 220% 220%;
        animation: mutEdge 9s ease-in-out infinite alternate; }
    @keyframes mutEdge { from { background-position: 0% 30%; } to { background-position: 100% 70%; } }
    .mut-card-in { position: relative; background: var(--color-white);
        border-radius: calc(1rem - 2px); padding: .75rem .85rem; }
    html.dark .mut-card-in { background: #1c2415; }
    /* Every card pays for the air now, cloud or none: the Follow pill lives
       up there too, so the room has to be there whether anybody has said
       what is on their mind or not. (See the rule further down.) */
    /* Hung from the card's own top-left, so it clears the whole row and the
       tail still comes down over the face beneath it. Two classes deep on
       purpose: .status-cloud's own `bottom` is defined later in the cascade,
       and left standing it squashed this box to its padding. */
    .mut-card-in .mut-cloud { top: .5rem; left: .85rem; bottom: auto; max-width: 8.5rem; }
    /* Follow goes to the card's own upper corner.
       Sitting at the end of the head row it was squeezing the name and the
       place line into whatever was left, and on a long name the pill and the
       location fought over the same forty pixels. Lifted out, the words get
       the width and the one thing you can do about this person is where the
       eye lands first. */
    .mut-card-in > .mut-head > .fp-follow,
    .mut-card-in > .fp-follow { position: absolute; top: .55rem; right: .6rem; z-index: 3; }
    /* The head no longer holds it, so the row is face + words. */
    .mut-head { display: flex; align-items: center; gap: .7rem; padding-right: 5.5rem; }
    /* Room for the pill above the row, and the cloud shortened so the two
       never reach across each other. */
    .mut-card-in { padding-top: 2.35rem; }
    .mut-face { flex: none; }
    .mut-mid { min-width: 0; flex: 1 1 auto; }
    .mut-name { display: block; font-size: .88rem; font-weight: 800; color: var(--color-gray-900);
        overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .mut-name:hover { color: var(--color-brand-700); }
    /* The level and the place share one line under the name, the whole
       block sitting centred on the face beside it. Never wrapping: two
       lines here made the column taller than the photo it stands against. */
    .mut-line { display: flex; align-items: center; gap: .45rem; flex-wrap: nowrap;
        min-width: 0; margin-top: .25rem; }
    /* The badge gives up its title in this narrow card — "🌱 Lv 4" leaves
       the place its room. */
    .mut-line .rankb { flex: none; }
    .mut-line .rankb-t { display: none; }
    .mut-loc { display: flex; align-items: center; gap: .25rem; font-size: .72rem;
        color: var(--color-gray-500); min-width: 0; }
    .mut-loc svg { width: .8rem; height: .8rem; flex: none; color: #e11d48; }
    /* A long town keeps the line honest by trailing off. */
    .mut-loc-t { min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .mut-head .fp-follow { flex: none; }
    .mut-counts { display: flex; gap: .9rem; flex-wrap: wrap; margin-top: .5rem;
        font-size: .72rem; font-weight: 600; color: var(--color-gray-500); }
    .mut-counts b { color: var(--color-gray-900); font-weight: 800; }
    html.dark .mut-counts b { color: #e8efe1; }
    .mut-find { width: 100%; margin-bottom: .8rem; }
    .mut-state { text-align: center; font-size: .8rem; color: var(--color-gray-400); padding: 1.2rem 0; }
    /* The number itself invites the tap wherever author-facts prints it. */
    .js-mutual { cursor: pointer; text-decoration: underline; text-decoration-style: dotted;
        text-underline-offset: 2px; }
    @media (prefers-reduced-motion: reduce) { .mut-card { animation: none; } }
</style>
<div class="sheet hidden" id="mutualSheet" style="--sheet-width:26rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title" id="mutualSheetTitle">Mutual co-farmers</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body" style="padding-bottom:1.1rem">
        <input type="search" id="mutualSheetFind" class="form-input mut-find"
               placeholder="Search these co-farmers…" autocomplete="off">
        <div id="mutualSheetList"><p class="mut-state">Loading…</p></div>
    </div>
</div>
<script>
(function () {
    if (window.__mutualSheetBound) return;
    window.__mutualSheetBound = true;

    document.addEventListener('click', async (e) => {
        const btn = e.target.closest('.js-mutual[data-mutual-user]');
        if (!btn) return;
        e.preventDefault();
        e.stopPropagation();
        const list = document.getElementById('mutualSheetList');
        const title = document.getElementById('mutualSheetTitle');
        if (!list) return;
        title.textContent = 'Mutual co-farmers';
        list.innerHTML = '<p class="mut-state">Loading…</p>';
        window.openSheet?.('mutualSheet');
        try {
            const res = await fetch(@json(route('community.mutual')) + '?userId=' + encodeURIComponent(btn.getAttribute('data-mutual-user')),
                { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
            const d = (await res.json()).data || {};
            const who = btn.getAttribute('data-mutual-name');
            title.textContent = (d.count || 0) + ' mutual ' + ((d.count || 0) === 1 ? 'co-farmer' : 'co-farmers')
                + (who ? ' with ' + who : '');
            list.innerHTML = d.html || '<p class="mut-state">Nobody shared — yet.</p>';
        } catch (_) {
            list.innerHTML = '<p class="mut-state">Could not load the list just now.</p>';
        }
    });

    /* The search box narrows the cards as you type — name, place or work,
     * matched against what the card itself carries. */
    document.getElementById('mutualSheetFind')?.addEventListener('input', (e) => {
        const q = (e.target.value || '').trim().toLowerCase();
        document.querySelectorAll('#mutualSheetList .mut-card').forEach((c) => {
            c.style.display = !q || (c.getAttribute('data-mut-find') || '').includes(q) ? '' : 'none';
        });
    });
})();
</script>
@endonce
