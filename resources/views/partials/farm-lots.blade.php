{{-- THE FARM'S LOTS, the kit every analysis and Global Lots share (2026-10-16, the owner).

     "Most of these analyses ask the same questions about the soil and the
     water, and they always pertain to a specific lot": a lot is answered once,
     with where it is (province, town or city and barangay, each a tag that
     opens a list), its size, the crop usually on it, and its soil, water and
     last season (the shared field questions, partials/field-profile), and an
     analysis then asks only which lot. App\Support\FarmLots and
     FarmLotController keep them (as_field_profiles).

     Include it once on a page: @include('partials.farm-lots'). It brings the
     field questions and the field card with it.

     JS (window.aneeLots):
       const p = aneeLots.picker(el, {
           crop: () => 'rice',          // the analysis's crop, for the troubles list (or null)
           value: 12,                   // a lot id to start on (optional)
           onPick(lot) {},              // a lot picked, saved or changed (null when cleared)
       });
       p.value()  p.set(id)  p.refresh()
       aneeLots.edit(lot | null, { crop }) -> Promise<lot | null>   the lot sheet (new when null)
       aneeLots.rows(fresh) -> Promise<lots>
       aneeLots.answered(lot) -> the question keys the lot has answered
       aneeLots.has(lot, key) -> whether one question is answered
       aneeLots.legacy(lot, crop) -> Promise of FieldProfile::legacy() for the lot's answers
       aneeLots.payload(lot) -> { lotId, location, place, areaHa, fieldProfile, fieldProfileFrom }
       aneeLots.confirm(lot) -> Promise<lot>   "Still correct": the six months start again
     A lot: { id, name, province, town, barangay, place, where, areaHa, crop, cropLabel,
              answers, count, updated, updatedAt, ageDays, stale, staleOn }
     Every save fires `aneelots:changed` on window, and every picker on the page repaints. --}}
@include('partials.field-profile-card')
@once
@push('head')
<style>
    .fl-pick { --fl-e: cubic-bezier(.22,1,.36,1); display: grid; gap: .55rem; min-width: 0; }
    .fl-card { position: relative; display: flex; align-items: center; gap: .75rem; width: 100%; min-width: 0; padding: .75rem .8rem; border-radius: 1.1rem; text-align: left;
        background: var(--color-white); border: 1.5px solid var(--color-gray-200); color: var(--color-gray-900); cursor: pointer;
        transition: border-color .28s var(--fl-e), background-color .28s var(--fl-e), transform .28s var(--fl-e), box-shadow .28s var(--fl-e); animation: flIn .32s var(--fl-e) both; }
    @media (hover: hover) { .fl-card:hover { border-color: #b9d39c; transform: translateY(-1px); box-shadow: 0 10px 22px -16px rgb(20 33 12 / .35); } }
    .fl-card.is-on { border-color: #4a7c2a; background: #f3f8ec; box-shadow: 0 0 0 1px #4a7c2a inset; }
    @keyframes flIn { from { opacity: 0; transform: translateY(6px); } }
    .fl-ic { flex: none; width: 2.7rem; height: 2.7rem; border-radius: .9rem; display: grid; place-items: center; font-size: 1.35rem; background: #f1f6ea; border: 1px solid #e1ecd4; }
    .fl-ic img { width: 1.7rem; height: 1.7rem; object-fit: contain; }
    .fl-tx { flex: 1; min-width: 0; }
    .fl-tx > b { display: block; font-size: .95rem; font-weight: 800; line-height: 1.3; overflow-wrap: anywhere; }
    .fl-tx > small { display: block; margin-top: .05rem; font-size: .78rem; line-height: 1.4; color: var(--color-gray-500); overflow-wrap: anywhere; }
    .fl-tx > small.is-none { color: #b45309; font-weight: 700; }
    .fl-chips { display: flex; flex-wrap: wrap; gap: .3rem; margin-top: .4rem; }
    .fl-chip { display: inline-flex; align-items: center; gap: .25rem; padding: .12rem .5rem; border-radius: 999px; font-size: .7rem; font-weight: 800; line-height: 1.35; color: #3d5226; background: #eef5e6; }
    .fl-chip.is-none { color: var(--color-gray-500); background: var(--color-gray-100); }
    .fl-chip.is-old { color: #92400e; background: #fef3c7; }
    .fl-chip svg { width: .78rem; height: .78rem; }
    .fl-ck { flex: none; width: 1.5rem; height: 1.5rem; border-radius: 999px; display: grid; place-items: center; border: 2px solid #cbd5c1; color: #fff; transition: background-color .28s var(--fl-e), border-color .28s var(--fl-e); }
    .fl-ck svg { width: .8rem; height: .8rem; opacity: 0; transform: scale(.6); transition: opacity .2s ease, transform .28s var(--fl-e); }
    .fl-card.is-on .fl-ck { background: #4a7c2a; border-color: #4a7c2a; }
    .fl-card.is-on .fl-ck svg { opacity: 1; transform: none; }
    .fl-add { display: flex; align-items: center; justify-content: center; gap: .45rem; width: 100%; min-height: 3.1rem; padding: .6rem 1rem; border-radius: 1.1rem; font-size: .9rem; font-weight: 800;
        color: #3d6823; background: transparent; border: 1.5px dashed #b9d39c; cursor: pointer; transition: background-color .28s var(--fl-e), border-color .28s var(--fl-e); }
    .fl-add:hover { background: #f3f8ec; border-color: #86b556; }
    .fl-add svg { width: 1.05rem; height: 1.05rem; }
    .fl-more { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: .4rem; font-size: .76rem; color: var(--color-gray-500); }
    .fl-more a { font-weight: 800; color: #3d6823; text-decoration: underline; text-underline-offset: 3px; }
    .fl-empty { padding: 1rem; border-radius: 1.1rem; font-size: .86rem; line-height: 1.55; color: var(--color-gray-600); background: var(--color-gray-50); border: 1px dashed var(--color-gray-200); }
    .fl-wait { padding: .9rem; font-size: .84rem; color: var(--color-gray-400); }
    .fl-old { display: grid; gap: .55rem; padding: .75rem .85rem; border-radius: 1rem; font-size: .82rem; line-height: 1.5; color: #78350f; background: #fffbeb; border: 1px solid #fde68a; animation: flIn .3s var(--fl-e) both; }
    .fl-old[hidden] { display: none; }
    .fl-old > div { display: flex; flex-wrap: wrap; gap: .45rem; }
    .fl-old button { min-height: 2.4rem; padding: .35rem .9rem; border-radius: 999px; font-size: .8rem; font-weight: 800; border: 1.5px solid #f59e0b; color: #92400e; background: #fff; transition: background-color .28s var(--fl-e); }
    .fl-old button.is-main { color: #fff; background: #d97706; border-color: #d97706; }
    .fl-old button:hover { background: #fef3c7; }
    .fl-old button.is-main:hover { background: #b45309; }

    /* The lot sheet. It wears the field questions' sheet (fq-sheet, fq-box). */
    .fl-f { margin-top: 1rem; }
    .fl-f:first-child { margin-top: .4rem; }
    .fl-lab { display: block; margin-bottom: .4rem; font-size: .84rem; font-weight: 800; color: var(--color-gray-800); }
    .fl-lab small { font-weight: 600; color: var(--color-gray-400); }
    .fl-sub { margin: -.2rem 0 .5rem; font-size: .76rem; line-height: 1.45; color: var(--color-gray-500); }
    .fl-tags { display: grid; gap: .45rem; }
    @container (min-width: 32rem) { .fl-tags.is-3 { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
    .fl-tag { display: flex; align-items: center; gap: .55rem; width: 100%; min-width: 0; min-height: 3rem; padding: .45rem .75rem; border-radius: .95rem; text-align: left;
        background: var(--color-white); border: 1.5px solid var(--color-gray-200); color: var(--color-gray-900); cursor: pointer; transition: border-color .28s var(--fl-e), background-color .28s var(--fl-e), opacity .28s var(--fl-e); }
    .fl-tag:hover:not(:disabled) { border-color: #86b556; background: #f8fbf4; }
    .fl-tag:disabled { opacity: .5; cursor: default; }
    .fl-tag .ti { flex: none; font-size: 1.05rem; }
    .fl-tag .tt { flex: 1; min-width: 0; }
    .fl-tag .tt small { display: block; font-size: .66rem; font-weight: 800; letter-spacing: .05em; text-transform: uppercase; color: var(--color-gray-400); }
    .fl-tag .tt b { display: block; font-size: .9rem; font-weight: 800; color: #2d5016; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .fl-tag.is-none .tt b { font-weight: 600; color: var(--color-gray-400); }
    .fl-tag .tc { flex: none; width: 1rem; height: 1rem; color: var(--color-gray-400); }
    .fl-tag.is-bump { animation: flBump .4s var(--fl-e); }
    @keyframes flBump { 40% { transform: scale(1.02); } }
    .fl-area { position: relative; }
    .fl-area .form-input { padding-right: 5.5rem; }
    .fl-area span { position: absolute; right: .9rem; top: 50%; transform: translateY(-50%); font-size: .8rem; font-weight: 700; color: var(--color-gray-400); pointer-events: none; }
    .fl-q { padding: .8rem; border-radius: 1.1rem; background: linear-gradient(160deg, #fbfdf8, #f2f8ea); border: 1px solid #e0ebd3; }
    .fl-q-tiles { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: .4rem; }
    @container (min-width: 32rem) { .fl-q-tiles { grid-template-columns: repeat(6, minmax(0, 1fr)); } }
    .fl-qt { position: relative; display: flex; flex-direction: column; align-items: center; gap: .1rem; padding: .5rem .25rem; border-radius: .85rem; text-align: center; background: #fff; border: 1.5px solid #e5e9e0;
        transition: border-color .28s var(--fl-e), background-color .28s var(--fl-e); }
    .fl-qt > span { font-size: 1.1rem; filter: grayscale(.6); opacity: .75; transition: filter .28s var(--fl-e), opacity .28s var(--fl-e); }
    .fl-qt > b { font-size: .7rem; font-weight: 800; line-height: 1.2; color: #14210c; }
    .fl-qt > small { font-size: .62rem; font-weight: 700; color: #8a9480; }
    .fl-qt.is-on { border-color: #86b556; background: #f4f9ee; }
    .fl-qt.is-on > span { filter: none; opacity: 1; }
    .fl-q-say { margin: .6rem 0 .1rem; font-size: .78rem; line-height: 1.5; color: #3f4a37; }
    .fl-q .btn { width: 100%; margin-top: .55rem; min-height: 2.9rem; }
    .fl-warn { margin-top: .4rem; padding: .6rem .75rem; border-radius: .85rem; font-size: .8rem; line-height: 1.5; color: #78350f; background: #fffbeb; border: 1px solid #fde68a; }
    .fl-err { margin-top: .6rem; font-size: .8rem; font-weight: 700; color: #b91c1c; }
    .fl-err[hidden], .fl-warn[hidden] { display: none; }
    .fl-shake { animation: flShake .4s var(--fl-e); }
    @keyframes flShake { 20%, 60% { transform: translateX(-5px); } 40%, 80% { transform: translateX(5px); } }
    #flEditSheet .fq-body { container-type: inline-size; }

    html.dark .fl-card, html.dark .fl-tag { background: #151b12; border-color: #2b3a1c; color: #e8efe1; }
    html.dark .fl-card.is-on { background: #1c2914; border-color: #6b9f3d; box-shadow: 0 0 0 1px #6b9f3d inset; }
    html.dark .fl-ic { background: #1c2616; border-color: #2b3a1c; }
    html.dark .fl-chip { color: #cfe6b8; background: #22301a; }
    html.dark .fl-chip.is-old { color: #fcd34d; background: #3a2a0c; }
    html.dark .fl-tag .tt b { color: #a5c97e; }
    html.dark .fl-tag.is-none .tt b { color: #7d8b70; }
    html.dark .fl-add { color: #a5c97e; border-color: #3d5a26; }
    html.dark .fl-add:hover { background: #1c2914; }
    html.dark .fl-more a { color: #a5c97e; }
    html.dark .fl-old, html.dark .fl-warn { color: #fde68a; background: #2a2010; border-color: #5c4413; }
    html.dark .fl-old button { background: #1a1408; color: #fcd34d; }
    html.dark .fl-q { background: #151b12; border-color: #2b3a1c; }
    html.dark .fl-qt { background: #10150d; border-color: #2b3a1c; }
    html.dark .fl-qt > b { color: #e8efe1; }
    html.dark .fl-qt.is-on { background: #1c2914; border-color: #6b9f3d; }
    html.dark .fl-q-say { color: #cdd8c0; }
    @media (prefers-reduced-motion: reduce) {
        .fl-card, .fl-old, .fl-tag.is-bump, .fl-shake { animation: none !important; }
        .fl-card, .fl-ck, .fl-ck svg, .fl-add, .fl-tag, .fl-qt, .fl-qt > span, .fl-old button { transition: none !important; }
    }
    html.sm-still .fl-card, html.sm-still .fl-old { animation: none !important; }
</style>
@endpush

@push('sheets')
{{-- One lot: its name, where it is, its size, its crop and its field answers. --}}
<dialog class="fq-sheet is-full" id="flEditSheet" aria-labelledby="flEditTitle">
    <div class="fq-box" tabindex="-1">
        <div class="fq-grip" aria-hidden="true"></div>
        <div class="fq-head"><h2 id="flEditTitle">New lot<small id="flEditSub">Where it is, its size, and its soil and water</small></h2><button type="button" class="fq-x" data-fq-close aria-label="Close"><svg fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" d="M6 6l12 12M18 6L6 18"/></svg></button></div>
        <div class="fq-body" id="flEditBody"></div>
        <div class="fq-foot"><button type="button" class="btn btn-white" data-fq-close>Cancel</button><span></span><button type="button" class="btn btn-primary" id="flEditSave">Save the lot</button></div>
    </div>
</dialog>

{{-- A list to pick from: the provinces, the towns, the barangays, the crops. --}}
<dialog class="fq-sheet" id="flPickSheet" aria-labelledby="flPickTitle">
    <div class="fq-box" tabindex="-1">
        <div class="fq-grip" aria-hidden="true"></div>
        <div class="fq-head"><h2 id="flPickTitle">Choose<small id="flPickSub"></small></h2><button type="button" class="fq-x" data-fq-close aria-label="Close"><svg fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" d="M6 6l12 12M18 6L6 18"/></svg></button></div>
        <label class="fq-find"><svg fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="6.5"/><path stroke-linecap="round" d="M20 20l-4.2-4.2"/></svg><span class="sr-only">Search</span><input type="search" id="flPickQ" placeholder="Search" autocomplete="off" maxlength="60"></label>
        <div class="fq-list" id="flPickList"></div>
    </div>
</dialog>
@endpush

@push('scripts')
@php
    $flData = [
        'urls' => [
            'list' => route('farmlots.list'), 'save' => route('farmlots.save'), 'update' => url('/app/farm-lots'),
            'places' => route('farmlots.places'), 'page' => route('farmlots.index'),
        ],
        'ph' => \App\Support\Region::ph(),
        'staleDays' => \App\Support\FarmLots::STALE_DAYS,
        'crops' => collect(\App\Support\FieldCatalogue::crops())->map(fn ($c, $k) => [$k, $c[1], $c[2], $c[0]])->values(),
        'pages' => collect(\App\Support\FieldProfile::QUESTIONS)->map(fn ($q) => $q['page'])->all(),
        'groups' => collect(\App\Support\FieldProfile::GROUPS)->map(fn ($g) => $g[0])->all(),
        'icon' => asset('images/treasure-map.png'),
    ];
@endphp
<script>
(() => {
    if (window.aneeLots) return;
    const D = @json($flData);
    const $ = (id) => document.getElementById(id);
    const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const clone = (o) => JSON.parse(JSON.stringify(o || {}));
    const reduce = () => document.documentElement.classList.contains('sm-still') || matchMedia('(prefers-reduced-motion: reduce)').matches;
    const apiReady = () => (window.api ? Promise.resolve() : new Promise((r) => window.addEventListener('load', r, { once: true })));
    const call = (url, opts) => apiReady().then(() => window.api(url, opts));
    const CROPS = Object.fromEntries(D.crops.map((c) => [c[0], c]));
    const TILE = { soil: ['🟤', 'Soil'], water: ['💧', 'Water'], acid: ['🧪', 'Acidity'], last: ['🌾', 'Last season'], weeds: ['🌿', 'Weeds'], problems: ['🐛', 'Troubles'] };
    const CHECK = '<svg fill="none" stroke="currentColor" stroke-width="3.2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>';
    const CLOCK = '<svg fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="8.5"/><path stroke-linecap="round" d="M12 7.5V12l3 2"/></svg>';
    const PLUS = '<svg fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" d="M12 5v14M5 12h14"/></svg>';
    const CHEV = '<svg class="tc" fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/></svg>';
    const fmtHa = (a) => { const v = Number(a); return v ? (v < 0.1 ? Math.round(v * 10000).toLocaleString() + ' sq m' : (Math.round(v * 100) / 100).toLocaleString() + ' ha') : ''; };

    /* ---- the farm's lots, once a page (fresh after a save) ---- */
    let ROWS = null, META = { ready: true, stale: 0 };
    function rows(fresh) {
        if (!ROWS || fresh) {
            ROWS = call(D.urls.list).then((r) => { META = { ready: !!(r.data && r.data.ready), stale: (r.data && r.data.stale) || 0 }; return (r.data && r.data.rows) || []; })
                .catch(() => { ROWS = null; return []; });
        }
        return ROWS;
    }
    const changed = (lot) => { ROWS = null; window.dispatchEvent(new CustomEvent('aneelots:changed', { detail: { lot } })); };

    /* ---- what a lot has answered ---- */
    const answered = (lot) => Object.keys((lot && lot.answers) || {}).filter((k) => { const v = lot.answers[k]; return v !== undefined && v !== null && v !== '' && !(Array.isArray(v) && !v.length); });
    const has = (lot, key) => answered(lot).includes(key);
    const pageDone = (answers, page) => Object.keys(answers || {}).some((k) => D.pages[k] === page && answers[k] !== undefined && !(Array.isArray(answers[k]) && !answers[k].length));
    const legacy = (lot, crop) => (window.aneeFieldCard ? window.aneeFieldCard.legacy((lot && lot.answers) || {}, crop || (lot && lot.crop) || null) : Promise.resolve({}));
    const payload = (lot) => !lot ? {} : ({ lotId: lot.id, location: lot.where || lot.place || '', place: lot.place || '', areaHa: lot.areaHa || null,
        fieldProfile: lot.count ? clone(lot.answers) : null, fieldProfileFrom: lot.count ? lot.id : null });

    /* ---- the sheets: open, close, and wait for the close ---- */
    const lock = () => document.documentElement.classList.toggle('fq-lock', !!document.querySelector('.fq-sheet[open]'));
    function sheetOpen(dlg) {
        if (!dlg || dlg.open || !dlg.showModal) return;
        dlg.showModal();
        lock();
        if (window.registerOverlay) window.registerOverlay('fq:' + dlg.id, () => sheetClose(dlg));
        dlg.querySelector('.fq-box').focus({ preventScroll: true });
    }
    function sheetClose(dlg) {
        if (!dlg.open || dlg.classList.contains('is-closing')) return;
        if (window.unregisterOverlay) window.unregisterOverlay('fq:' + dlg.id);
        const done = () => { dlg.classList.remove('is-closing'); dlg.close(); lock(); dlg.dispatchEvent(new Event('fqclosed')); };
        if (reduce()) { done(); return; }
        dlg.classList.add('is-closing');
        setTimeout(done, 230);
    }
    const closed = (dlg) => new Promise((r) => dlg.addEventListener('fqclosed', r, { once: true }));
    function bind(dlg) {
        if (!dlg || dlg.dataset.flBound) return;
        dlg.dataset.flBound = '1';
        dlg.addEventListener('click', (e) => { if (e.target === dlg || e.target.closest('[data-fq-close]')) sheetClose(dlg); });
        dlg.addEventListener('cancel', (e) => { e.preventDefault(); sheetClose(dlg); });
    }

    /* ---- a list to pick from (provinces, towns, barangays, crops) ---- */
    function pickFrom(o) {
        const dlg = $('flPickSheet');
        bind(dlg);
        $('flPickTitle').firstChild.textContent = o.title;
        $('flPickSub').textContent = o.sub || '';
        const q = $('flPickQ');
        q.value = '';
        q.placeholder = o.find || 'Search';
        let result = null;
        const paint = () => {
            const t = q.value.trim().toLowerCase();
            const items = (o.items || []).filter((x) => !t || (x.l + ' ' + (x.s || '') + ' ' + (x.f || '')).toLowerCase().includes(t));
            $('flPickList').innerHTML = (o.none ? '<button type="button" class="fq-row" data-v=""><span class="fq-ri">✖️</span><span class="fq-rt"><b>' + esc(o.none) + '</b></span></button>' : '')
                + (items.length ? items.slice(0, 400).map((x) => '<button type="button" class="fq-row" role="option" data-v="' + esc(x.v) + '" aria-checked="' + (x.v === o.value) + '">'
                    + (x.i ? '<span class="fq-ri">' + esc(x.i) + '</span>' : '') + '<span class="fq-rt"><b>' + esc(x.l) + '</b>' + (x.s ? '<small>' + esc(x.s) + '</small>' : '') + '</span>'
                    + (x.v === o.value ? '<span class="fq-ck">' + CHECK + '</span>' : '') + '</button>').join('')
                    : '<p class="fq-nothing">' + esc(o.empty || 'Nothing by that name.') + '</p>');
        };
        q.oninput = paint;
        $('flPickList').onclick = (e) => {
            const b = e.target.closest('[data-v]');
            if (!b) return;
            result = { v: b.getAttribute('data-v') };
            sheetClose(dlg);
        };
        paint();
        const p = closed(dlg).then(() => result);
        sheetOpen(dlg);
        $('flPickList').scrollTop = 0;
        if (matchMedia('(pointer: fine)').matches && (o.items || []).length > 8) setTimeout(() => q.focus(), 60);
        return p;
    }
    const PLACES = {};
    function places(province, town) {
        const key = (province || '') + '|' + (town || '');
        if (!PLACES[key]) {
            const qs = new URLSearchParams();
            if (province) qs.set('province', province);
            if (town) qs.set('town', town);
            PLACES[key] = call(D.urls.places + (qs.toString() ? '?' + qs.toString() : '')).then((r) => (r.data && r.data.items) || [])
                .catch((err) => { delete PLACES[key]; throw err; });
        }
        return PLACES[key];
    }

    /* ---- one lot, in its sheet ---- */
    let editing = null;
    function edit(lot, o) {
        o = o || {};
        const dlg = $('flEditSheet');
        bind(dlg);
        const st = {
            id: lot && lot.id ? lot.id : null, name: (lot && lot.name) || '', province: (lot && lot.province) || '', town: (lot && lot.town) || '',
            barangay: (lot && lot.barangay) || '', areaHa: lot && lot.areaHa ? String(lot.areaHa) : '', crop: (lot && lot.crop) || o.crop || '',
            answers: clone(lot && lot.answers), stale: !!(lot && lot.stale), updated: (lot && lot.updated) || '',
        };
        // A crop the catalogue keys differently (corn is yellow corn, every rice is rice).
        if (st.crop && !CROPS[st.crop]) st.crop = String(st.crop).startsWith('rice') ? 'rice' : (st.crop === 'corn' ? 'corn_yellow' : '');
        editing = st;
        $('flEditTitle').firstChild.textContent = st.id ? st.name || 'Your lot' : 'New lot';
        $('flEditSub').textContent = o.reason === 'place' ? 'Say where it is, so Anee reads the right weather' : 'Where it is, its size, and its soil and water';
        let result = null;
        const body = $('flEditBody');
        const tag = (id, icon, label, value, none, disabled) => '<button type="button" class="fl-tag' + (value ? '' : ' is-none') + '" id="' + id + '" aria-haspopup="dialog"' + (disabled ? ' disabled' : '') + '>'
            + '<span class="ti" aria-hidden="true">' + icon + '</span><span class="tt"><small>' + esc(label) + '</small><b>' + esc(value || none) + '</b></span>' + CHEV + '</button>';
        const paintPlace = () => {
            const box = $('flPlace');
            if (!box) return;
            box.innerHTML = D.ph
                ? tag('flProv', '🗺️', 'Province', st.province, 'Choose the province', false)
                    + tag('flTown', '🏘️', 'Town or city', st.town, st.province ? 'Choose the town or city' : 'Pick the province first', !st.province)
                    + tag('flBrgy', '📍', 'Barangay', st.barangay, st.town ? 'Choose the barangay' : 'Pick the town first', !st.town)
                : '<input type="text" class="form-input" id="flTownIn" maxlength="120" placeholder="Town or city" value="' + esc(st.town) + '">'
                    + '<input type="text" class="form-input" id="flProvIn" maxlength="120" placeholder="Province, state or region" value="' + esc(st.province) + '">';
        };
        const paintCrop = () => {
            const c = CROPS[st.crop];
            const b = $('flCrop');
            if (!b) return;
            b.classList.toggle('is-none', !c);
            b.querySelector('.ti').textContent = c ? c[2] || '🌱' : '🌱';
            b.querySelector('b').textContent = c ? c[1] : 'Pick the crop (optional)';
        };
        const paintQ = () => {
            const box = $('flQ');
            if (!box) return;
            const FP = window.aneeFieldProfile;
            const n = FP ? FP.count(st.answers) : Object.keys(st.answers).length;
            box.innerHTML = '<div class="fl-q-tiles">' + Object.keys(D.groups).map((g) => '<div class="fl-qt' + (pageDone(st.answers, g) ? ' is-on' : '') + '"><span aria-hidden="true">' + ((TILE[g] || [])[0] || '•') + '</span><b>' + esc((TILE[g] || [])[1] || D.groups[g]) + '</b><small>' + (pageDone(st.answers, g) ? 'Answered' : 'Not yet') + '</small></div>').join('') + '</div>'
                + '<p class="fl-q-say">' + (n ? esc(n + ' answer' + (n === 1 ? '' : 's') + ': ' + (FP && FP.words ? FP.words(st.answers) : '')) : 'Not answered yet. Every analysis reads these answers, so they are asked only once, here.') + '</p>'
                + '<button type="button" class="btn ' + (n ? 'btn-white' : 'btn-primary') + '" id="flQBtn">' + (n ? 'Change the answers' : 'Answer the questions') + '</button>';
        };
        body.innerHTML = (st.stale ? '<div class="fl-warn">These answers were saved on ' + esc(st.updated) + ', over six months ago. Check each one and change what is different now, then save.</div>' : '')
            + '<div class="fl-f"><label class="fl-lab" for="flName">Name of the lot</label><input type="text" class="form-input" id="flName" maxlength="120" placeholder="Like North field, or the lot by the creek" value="' + esc(st.name) + '"></div>'
            + '<div class="fl-f"><span class="fl-lab">Where is it?</span><p class="fl-sub">The weather, the season and the advice follow the place.</p><div class="fl-tags is-3" id="flPlace"></div></div>'
            + '<div class="fl-f"><label class="fl-lab" for="flArea">How big is it? <small>(optional)</small></label><div class="fl-area"><input type="number" class="form-input" id="flArea" min="0" step="any" inputmode="decimal" placeholder="e.g. 1.5" value="' + esc(st.areaHa) + '"><span>hectares</span></div></div>'
            + '<div class="fl-f"><span class="fl-lab">What grows on it? <small>(optional)</small></span><p class="fl-sub">Its pests and diseases fill the troubles question.</p>'
            + '<button type="button" class="fl-tag is-none" id="flCrop" aria-haspopup="dialog"><span class="ti" aria-hidden="true">🌱</span><span class="tt"><small>Crop</small><b>Pick the crop (optional)</b></span>' + CHEV + '</button></div>'
            + '<div class="fl-f"><span class="fl-lab">Its soil, water and last season</span><div class="fl-q" id="flQ"></div></div>'
            + '<p class="fl-err" id="flErr" hidden></p>';
        paintPlace(); paintCrop(); paintQ();
        const err = (msg, el) => { const e = $('flErr'); e.textContent = msg; e.hidden = !msg; if (el && !reduce()) { el.classList.remove('fl-shake'); void el.offsetWidth; el.classList.add('fl-shake'); } };
        const bump = (el) => { if (el && !reduce()) { el.classList.remove('is-bump'); void el.offsetWidth; el.classList.add('is-bump'); } };

        body.onclick = async (e) => {
            const t = e.target.closest('button');
            if (!t || t.disabled) return;
            try {
                if (t.id === 'flProv') {
                    const list = await places();
                    const r = await pickFrom({ title: 'Choose the province', items: list.map((n) => ({ v: n, l: n })), value: st.province, find: 'Search a province' });
                    if (r && r.v && r.v !== st.province) { st.province = r.v; st.town = ''; st.barangay = ''; paintPlace(); bump($('flProv')); setTimeout(() => $('flTown') && $('flTown').click(), 260); }
                } else if (t.id === 'flTown') {
                    const list = await places(st.province);
                    const r = await pickFrom({ title: 'Choose the town or city', sub: st.province, items: list.map((n) => ({ v: n, l: n })), value: st.town, find: 'Search a town or city' });
                    if (r && r.v && r.v !== st.town) { st.town = r.v; st.barangay = ''; paintPlace(); bump($('flTown')); setTimeout(() => $('flBrgy') && $('flBrgy').click(), 260); }
                } else if (t.id === 'flBrgy') {
                    const list = await places(st.province, st.town);
                    const r = await pickFrom({ title: 'Choose the barangay', sub: st.town + ', ' + st.province, items: list.map((n) => ({ v: n, l: n })), value: st.barangay,
                        find: 'Search a barangay', none: st.barangay ? 'No barangay' : null, empty: 'No barangay by that name.' });
                    if (r) { st.barangay = r.v || ''; paintPlace(); bump($('flBrgy')); }
                } else if (t.id === 'flCrop') {
                    const r = await pickFrom({ title: 'What grows on this lot?', sub: 'Its pests and diseases fill the troubles question', value: st.crop, find: 'Search a crop',
                        items: D.crops.map((c) => ({ v: c[0], l: c[1], s: c[3] !== c[1] ? c[3] : '', i: c[2] || '🌱', f: c[3] })), none: st.crop ? 'No crop' : null });
                    if (r) { st.crop = r.v || ''; paintCrop(); bump($('flCrop')); }
                } else if (t.id === 'flQBtn') {
                    await askQuestions();
                }
            } catch (_) {
                window.toast?.('The list could not load. Please try again.', 'error');
            }
        };
        const read = () => {
            st.name = $('flName').value.trim();
            st.areaHa = $('flArea').value.trim();
            if (!D.ph) { st.town = ($('flTownIn') || {}).value?.trim() || ''; st.province = ($('flProvIn') || {}).value?.trim() || ''; }
        };
        async function askQuestions() {
            read();
            if (!window.aneeFieldProfile) return;
            const got = await window.aneeFieldProfile.open({ crop: st.crop || null, value: st.answers, title: st.name || 'Your lot', saved: false });
            if (got) { st.answers = got; paintQ(); }
        }
        $('flEditSave').onclick = async () => {
            read();
            if (!st.name) { err('Give the lot a name.', $('flName')); $('flName').focus(); return; }
            if (!st.town || !st.province) { err(D.ph ? 'Choose the province and the town or city.' : 'Type the town and the province or state.', $('flPlace')); return; }
            err('');
            // A new lot is not done until its questions are asked: the first save walks them.
            if (!st.id && !(window.aneeFieldProfile && window.aneeFieldProfile.count(st.answers)) && o.ask !== false) await askQuestions();
            const btn = $('flEditSave');
            btn.disabled = true;
            try {
                const body = { name: st.name, province: st.province, town: st.town, barangay: st.barangay || null, areaHa: Number(st.areaHa) > 0 ? Number(st.areaHa) : null,
                    crop: st.crop || null, answers: st.answers };
                const r = await call(st.id ? D.urls.update + '/' + st.id : D.urls.save, { method: 'POST', body });
                result = r.data.row;
                window.toast?.(r.message, 'success');
                changed(result);
                sheetClose(dlg);
            } catch (e) {
                err(e.message || 'The lot could not be saved. Please try again.');
            } finally { btn.disabled = false; }
        };
        const p = closed(dlg).then(() => { editing = null; return result; });
        sheetOpen(dlg);
        $('flEditBody').scrollTop = 0;
        return p;
    }

    async function confirm(lot) {
        const r = await call(D.urls.update + '/' + lot.id + '/checked', { method: 'POST' });
        window.toast?.(r.message, 'success');
        changed(r.data.row);
        return r.data.row;
    }

    /* ---- a lot as a card ---- */
    function card(lot, on, i) {
        const c = CROPS[lot.crop];
        const chips = [];
        if (lot.areaHa) chips.push('<span class="fl-chip">' + esc(fmtHa(lot.areaHa)) + '</span>');
        if (c) chips.push('<span class="fl-chip">' + esc((c[2] ? c[2] + ' ' : '') + c[1]) + '</span>');
        chips.push(lot.count ? '<span class="fl-chip">' + lot.count + ' answer' + (lot.count === 1 ? '' : 's') + '</span>' : '<span class="fl-chip is-none">No answers yet</span>');
        if (lot.stale) chips.push('<span class="fl-chip is-old" title="Saved on ' + esc(lot.updated) + '">' + CLOCK + 'Check it</span>');
        return '<button type="button" class="fl-card' + (on ? ' is-on' : '') + '" data-fl-lot="' + lot.id + '" role="radio" aria-checked="' + !!on + '" style="animation-delay:' + Math.min(i || 0, 8) * 30 + 'ms">'
            + '<span class="fl-ic" aria-hidden="true">' + (c && c[2] ? esc(c[2]) : '<img src="' + esc(D.icon) + '" alt="">') + '</span>'
            + '<span class="fl-tx"><b>' + esc(lot.name) + '</b>' + (lot.place ? '<small>' + esc(lot.place) + '</small>' : '<small class="is-none">No place yet: tap to add it</small>')
            + '<span class="fl-chips">' + chips.join('') + '</span></span><span class="fl-ck">' + CHECK + '</span></button>';
    }

    /* ---- the picker an analysis wears ---- */
    function picker(el, o) {
        o = o || {};
        const crop = () => (typeof o.crop === 'function' ? o.crop() : o.crop) || null;
        const st = { id: o.value || null, lot: null, all: [] };
        el.classList.add('fl-pick');
        el.setAttribute('role', 'radiogroup');
        async function paint(fresh) {
            if (!st.all.length) el.innerHTML = '<p class="fl-wait">Loading your lots…</p>';
            st.all = await rows(fresh);
            st.lot = st.all.find((l) => l.id === st.id) || null;
            if (st.id && !st.lot) st.id = null;
            el.innerHTML = (st.all.length ? st.all.map((l, i) => card(l, l.id === st.id, i)).join('')
                : '<div class="fl-empty">No lots yet. Add your first one: where it is, its size, and its soil and water. You answer it once, and every analysis reads it.</div>')
                + '<button type="button" class="fl-add" data-fl-add>' + PLUS + (st.all.length ? 'Add another lot' : 'Add a lot') + '</button>'
                + '<div class="fl-old" data-fl-old hidden></div>'
                + '<p class="fl-more"><span>Your lots are kept in Global Lots.</span><a href="' + esc(D.urls.page) + '" target="_blank" rel="noopener">Open Global Lots</a></p>';
            paintOld();
        }
        function paintOld() {
            const box = el.querySelector('[data-fl-old]');
            if (!box) return;
            const l = st.lot;
            if (!l || !l.stale) { box.hidden = true; return; }
            box.innerHTML = '<span><b>' + esc(l.name) + '</b> was last checked on ' + esc(l.updated) + ', over six months ago. Are its answers still right?</span>'
                + '<div><button type="button" class="is-main" data-fl-check>Check them</button><button type="button" data-fl-fine>Still correct</button></div>';
            box.hidden = false;
        }
        function pick(lot) {
            st.id = lot ? lot.id : null;
            st.lot = lot;
            el.querySelectorAll('.fl-card').forEach((c) => { const on = Number(c.getAttribute('data-fl-lot')) === st.id; c.classList.toggle('is-on', on); c.setAttribute('aria-checked', on ? 'true' : 'false'); });
            paintOld();
            if (o.onPick) o.onPick(lot ? clone(lot) : null);
        }
        el.addEventListener('click', async (e) => {
            const add = e.target.closest('[data-fl-add]');
            if (add) { const row = await edit(null, { crop: crop() }); if (row) { st.id = row.id; await paint(true); pick(st.lot); } return; }
            if (e.target.closest('[data-fl-check]') && st.lot) { const row = await edit(st.lot, { crop: crop() }); if (row) { await paint(true); pick(st.lot); } return; }
            if (e.target.closest('[data-fl-fine]') && st.lot) { try { await confirm(st.lot); await paint(true); pick(st.lot); } catch (err) { window.toast?.(err.message, 'error'); } return; }
            const c = e.target.closest('[data-fl-lot]');
            if (!c) return;
            const lot = st.all.find((l) => l.id === Number(c.getAttribute('data-fl-lot')));
            if (!lot) return;
            // A lot saved before the lots had a place: say where it is first.
            if (!lot.where) {
                const row = await edit(lot, { crop: crop(), reason: 'place', ask: false });
                if (!row) return;
                st.id = row.id; await paint(true); pick(st.lot); return;
            }
            pick(lot);
        });
        window.addEventListener('aneelots:changed', () => { paint(true).then(() => { if (st.lot && o.onPick) o.onPick(clone(st.lot)); }); });
        paint();
        return {
            el,
            value: () => (st.lot ? clone(st.lot) : null),
            set: (id) => { st.id = id || null; return paint().then(() => { if (st.lot) pick(st.lot); }); },
            refresh: () => paint(true),
        };
    }

    window.aneeLots = { picker, edit, rows, answered, has, legacy, payload, confirm, card, meta: () => META, staleDays: D.staleDays, crops: CROPS, fmtHa };
})();
</script>
@endpush
@endonce
