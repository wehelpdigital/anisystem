{{-- The mutual co-farmers sheet, shared by every "N mutual co-farmers"
     link: tap the number anywhere — a wall post, a member card, a profile —
     and the faces behind it slide up. Delegated + guarded singleton. --}}
@once
<style>
    .mut-row { display: flex; align-items: center; gap: .75rem; padding: .55rem .35rem;
        border-radius: .7rem; text-decoration: none;
        transition: background .28s cubic-bezier(.22, 1, .36, 1); }
    .mut-row:hover { background: var(--color-gray-50); }
    html.dark .mut-row:hover { background: rgb(255 255 255 / .05); }
    .mut-row-mid { min-width: 0; flex: 1 1 auto; }
    .mut-row-mid b { display: block; font-size: .85rem; font-weight: 700; color: var(--color-gray-900);
        overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .mut-row-mid i { display: block; font-style: normal; font-size: .72rem; color: var(--color-gray-500); margin-top: .1rem; }
    .mut-row-go { flex: none; width: 1rem; height: 1rem; color: var(--color-gray-300); }
    .mut-state { text-align: center; font-size: .8rem; color: var(--color-gray-400); padding: 1.2rem 0; }
    /* The number itself invites the tap wherever author-facts prints it. */
    .js-mutual { cursor: pointer; text-decoration: underline; text-decoration-style: dotted;
        text-underline-offset: 2px; }
</style>
<div class="sheet hidden" id="mutualSheet" style="--sheet-width:26rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title" id="mutualSheetTitle">Mutual co-farmers</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body" style="padding-bottom:1.1rem">
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
})();
</script>
@endonce
