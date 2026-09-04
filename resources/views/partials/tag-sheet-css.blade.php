{{-- THE TAG AND ITS ROWS — the lot form's crop-tag / dt-row families,
     as one shared include for pages that never load the lot form or the
     inventory sheets. Same names, one house style. @once, so a page that
     collects it twice pays once. --}}
@once
<style>
    .crop-tag { display: flex; align-items: center; gap: .5rem; width: 100%;
        padding: .55rem .7rem; border-radius: .75rem; cursor: pointer; text-align: left;
        border: 1px solid var(--color-gray-200); background: var(--color-white);
        transition: border-color .28s cubic-bezier(.22,1,.36,1), background .28s cubic-bezier(.22,1,.36,1); }
    .crop-tag:hover { border-color: var(--color-brand-300); background: var(--color-brand-50); }
    .crop-tag-e { font-size: 1.1rem; line-height: 1; flex: none; }
    .crop-tag-t { flex: 1 1 auto; min-width: 0; font-size: .9rem; font-weight: 700; color: #3d6823;
        overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .crop-tag-t.is-none { color: var(--color-gray-500); font-weight: 600; }
    .crop-tag-c { width: 1rem; height: 1rem; flex: none; color: var(--color-gray-400); }
    html.dark .crop-tag { background: #1c2416; border-color: #2b3a1c; }
    html.dark .crop-tag-t.is-none { color: #9aa78d; }
    @media (prefers-reduced-motion: reduce) { .crop-tag { transition: none; } }

    .dt-rows { display: flex; flex-direction: column; gap: .4rem; }
    .dt-row { display: flex; align-items: flex-start; gap: .6rem; width: 100%; text-align: left;
        padding: .6rem .7rem; border-radius: .75rem; cursor: pointer; position: relative;
        border: 1px solid var(--color-gray-200); background: var(--color-white); }
    .dt-row:hover { border-color: var(--color-brand-300); background: var(--color-brand-50); }
    .dt-row.is-on { border-color: var(--color-brand-500); background: var(--color-brand-50); }
    .dt-row-e { font-size: 1.1rem; line-height: 1.3; flex: none; }
    .dt-row-body { flex: 1 1 auto; min-width: 0; }
    .dt-row-body b { display: block; font-size: .88rem; font-weight: 800; color: var(--color-gray-900); }
    .dt-row-body i { display: block; font-style: normal; font-size: .75rem; color: var(--color-gray-500); margin-top: .1rem; }
    .dt-row-tick { width: 1.1rem; height: 1.1rem; flex: none; color: var(--color-brand-600); margin-top: .15rem; }
    .dt-row .dt-row-tick { visibility: hidden; }
    .dt-row.is-on .dt-row-tick { visibility: visible; }
    html.dark .dt-row { background: #1c2417; border-color: #2f3a26; }
    html.dark .dt-row.is-on { background: #22301a; border-color: #6b9f3d; }
    html.dark .dt-row-body b { color: #e8efe1; }
</style>
@endonce
