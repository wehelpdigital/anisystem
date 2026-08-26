{{-- The mutual co-farmers sheet, shared by every "N mutual co-farmers"
     link: tap the number anywhere — a wall post, a member card, a profile —
     and the faces behind it slide up. Delegated + guarded singleton. --}}
@once
<style>
    /* Each shared face is a card wearing the house gradient as its edge:
       the wrapper IS the border, the white card sits 1.5px inside it. */
    .mut-card { padding: 1.5px; border-radius: 1rem; margin-bottom: .65rem;
        background: linear-gradient(120deg, #2f5219, #8fc267 45%, #4a7c2a 75%, #a9d383);
        background-size: 220% 220%;
        animation: mutEdge 9s ease-in-out infinite alternate; }
    @keyframes mutEdge { from { background-position: 0% 30%; } to { background-position: 100% 70%; } }
    .mut-card-in { background: var(--color-white); border-radius: calc(1rem - 1.5px);
        padding: .75rem .85rem; }
    html.dark .mut-card-in { background: #1c2415; }
    .mut-head { display: flex; align-items: center; gap: .7rem; }
    .mut-face { flex: none; }
    .mut-mid { min-width: 0; flex: 1 1 auto; }
    .mut-name-row { display: flex; align-items: center; gap: .4rem; flex-wrap: wrap; }
    .mut-name { font-size: .88rem; font-weight: 800; color: var(--color-gray-900);
        overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .mut-name:hover { color: var(--color-brand-700); }
    .mut-loc { display: flex; align-items: center; gap: .25rem; font-size: .72rem;
        color: var(--color-gray-500); margin-top: .2rem; }
    .mut-loc svg { width: .8rem; height: .8rem; flex: none; color: #e11d48; }
    .mut-head .fp-follow { flex: none; align-self: flex-start; }
    .mut-bubble { margin-top: .5rem; font-size: .76rem; color: var(--color-gray-500); }
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
