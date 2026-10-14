{{-- The members' gate on a section's front page (2026-10-14, the owner):
     /pests, /diseases, /weeds and /rice-varieties. The catalogue or finder
     still works for a guest, but only its first rows show; the next rows
     are blurred and the rest held back, under the members' card. Every card
     stays in the HTML, so search engines read every link.

     Markup: wrap the grid (and its "show more" button) in
         <div class="hg" data-hg data-hg-one="pest" data-hg-many="pests">
     give the grid data-hg-grid (and data-hg-card, the card selector), then
     @include('public.site.partials.content-gate-card', [...]) inside the
     wrapper, after the grid. The grid's own script calls
     window.aneeHubGate(grid) after every filter. Only when
     App\Support\ContentGate::hubApplies() says so. --}}
@include('public.site.partials.content-gate-assets')
@once
@push('head')
<style>
    .hg { position: relative; }
    .hg-locked { filter: blur(4px); opacity: .55; pointer-events: none; user-select: none; }
    .hg-cut { display: none !important; }
    .hg .wk-more, .hg [data-rv-more] { display: none !important; }
    .hg .sp-gate-card { margin-top: 1.6rem; }
    /* The blurred rows fade out under the card that sits over their end. */
    .hg.has-locked [data-hg-grid] { -webkit-mask-image: linear-gradient(to bottom, #000 calc(100% - 15rem), transparent); mask-image: linear-gradient(to bottom, #000 calc(100% - 15rem), transparent); }
    .hg.has-locked .sp-gate-card { margin-top: -8rem; }
    .hg-chip, .wk-jump .hg-chip { display: inline-flex; align-items: center; gap: .4rem; padding: .5rem .9rem; border-radius: 999px; font-size: .88rem; font-weight: 800; text-decoration: none;
        color: #2d5016; background: #eef6e4; border: 1px solid #d3e6bd; transition: background-color .28s cubic-bezier(.22,1,.36,1); }
    a.hg-chip:hover, .wk-jump a.hg-chip:hover { background: #e2f0d2; }
    .hg-chip svg, .wk-jump .hg-chip svg { width: 1rem; height: 1rem; color: #4d7f2a; }
    .hg-chip.is-open u { text-underline-offset: 3px; }
    @media (max-width: 639.98px) { .hg.has-locked .sp-gate-card { margin-top: -6rem; } }
</style>
@endpush
@push('scripts')
<script>
(() => {
    /* Whole rows: at least two, and at least four cards, show clear; the
       next two rows blur; the rest wait behind the card. A crop chip puts
       other shelves' cards after the shelf's own (CSS order), so the count
       follows that order, not the page's. */
    const lock = (grid) => {
        const wrap = grid && grid.closest('[data-hg]');
        if (!wrap) return;
        const sel = grid.dataset.hgCard || 'a';
        const cards = [...grid.children].filter((c) => c.matches(sel));
        const on = cards.filter((c) => !c.hidden && !c.classList.contains('is-out'))
            .sort((a, b) => (a.classList.contains('is-also') ? 1 : 0) - (b.classList.contains('is-also') ? 1 : 0));
        const cols = Math.max(1, getComputedStyle(grid).gridTemplateColumns.split(' ').filter(Boolean).length);
        const open = cols * Math.max(2, Math.ceil(4 / cols)), blur = cols * 2;
        cards.forEach((c) => { c.classList.remove('hg-locked', 'hg-cut'); c.inert = false; });
        on.forEach((c, i) => {
            if (i >= open + blur) c.classList.add('hg-cut');
            else if (i >= open) { c.classList.add('hg-locked'); c.inert = true; }
        });
        const held = Math.max(0, on.length - open);
        wrap.classList.toggle('has-locked', held > 0);
        const foot = wrap.querySelector('[data-gate-foot]');
        if (foot) {
            const noun = held === 1 ? wrap.dataset.hgOne : wrap.dataset.hgMany;
            foot.textContent = !held ? '' : on.length < cards.length ? held + ' more ' + noun + ' match what you picked' : 'And ' + held + ' more ' + noun + ' waiting for you';
        }
    };
    window.aneeHubGate = lock;
    const all = () => document.querySelectorAll('[data-hg] [data-hg-grid]').forEach(lock);
    all();
    document.addEventListener('DOMContentLoaded', all);
    let rt = 0;
    window.addEventListener('resize', () => { clearTimeout(rt); rt = setTimeout(all, 150); });
})();
</script>
@endpush
@endonce
