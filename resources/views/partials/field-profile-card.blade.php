{{-- YOUR FIELD IN DETAIL, the card every analysis wears (2026-10-15).

     The shared field questions (partials/field-profile, App\Support\FieldProfile)
     as one optional step of an analysis: six tiles that light up as the pages
     are answered, the answers as short chips, a button that opens the whole
     set of questions in a sheet (aneeFieldProfile.open), and "Use the answers
     from {lot}" chips for the fields the farm described before
     (as_field_profiles). Include it once on a page; it brings the kit with it.

     JS (window.aneeFieldCard):
       const card = aneeFieldCard.mount(el, {
           crop: () => 'rice',                // the analysis's crop key, or null
           title: () => 'North field',        // the sheet's title (the lot or the place)
           onChange(answers, legacy, card) {} // after every change; legacy is
                                              // FieldProfile::legacy() in JS. Return
                                              // the words of what it filled, if any.
       });
       card.value()  card.from()  card.payload()  -> { fieldProfile, fieldProfileFrom }
       card.set(answers, from)  card.clear()  card.refresh()
       aneeFieldCard.legacy(answers)  -> Promise of FieldProfile::legacy()'s map
       aneeFieldCard.hint(el, on)     the "Filled in from your field" line on a step
       aneeFieldCard.said(pairs)      the saved answers as chips, for a report
     The JS legacy() mirrors App\Support\FieldProfile::legacy(): change both together. --}}
@include('partials.field-profile')
@once
@push('head')
<style>
    .fpc { --fpc-e: cubic-bezier(.22,1,.36,1); container-type: inline-size; min-width: 0; padding: .85rem; border-radius: 1.1rem;
        background: linear-gradient(160deg, #fbfdf8, #f2f8ea); border: 1px solid #e0ebd3; }
    .fpc-tiles { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: .45rem; }
    @container (min-width: 34rem) { .fpc-tiles { grid-template-columns: repeat(6, minmax(0, 1fr)); } }
    .fpc-t { position: relative; min-width: 0; display: flex; flex-direction: column; align-items: center; gap: .15rem; padding: .6rem .3rem .55rem; border-radius: .9rem;
        text-align: center; background: #fff; border: 1.5px solid #e5e9e0; color: #14210c;
        transition: border-color .28s var(--fpc-e), background-color .28s var(--fpc-e), transform .28s var(--fpc-e); }
    .fpc-t > span { font-size: 1.2rem; line-height: 1.2; filter: grayscale(.55); opacity: .8; transition: filter .28s var(--fpc-e), opacity .28s var(--fpc-e); }
    .fpc-t > b { max-width: 100%; font-size: .76rem; font-weight: 800; line-height: 1.2; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .fpc-t > small { font-size: .66rem; font-weight: 700; color: #8a9480; white-space: nowrap; }
    .fpc-t > i { position: absolute; top: -.35rem; right: -.35rem; width: 1.15rem; height: 1.15rem; border-radius: 999px; display: grid; place-items: center; color: #fff;
        background: #4a7c2a; box-shadow: 0 0 0 2px #fff; transform: scale(0); transition: transform .28s var(--fpc-e); }
    .fpc-t > i svg { width: .65rem; height: .65rem; }
    .fpc-t.is-done { border-color: #a8cc7e; background: #f4f9ee; }
    .fpc-t.is-done > span { filter: none; opacity: 1; }
    .fpc-t.is-done > small { color: #3d6823; }
    .fpc-t.is-done > i { transform: none; }

    .fpc-sum { display: grid; grid-template-rows: 0fr; transition: grid-template-rows .32s var(--fpc-e); }
    .fpc-sum.is-on { grid-template-rows: 1fr; }
    .fpc-sum > div { overflow: hidden; min-width: 0; }
    .fpc-chips { display: flex; flex-wrap: wrap; gap: .35rem; padding-top: .75rem; }
    .fpc-p { display: inline-flex; align-items: baseline; gap: .3rem; max-width: 100%; padding: .28rem .6rem; border-radius: 999px; font-size: .76rem; font-weight: 700; line-height: 1.35;
        color: #1f3a10; background: #fff; border: 1px solid #dbe8cc; animation: fpcPop .3s var(--fpc-e) both; }
    .fpc-p i { flex: none; font-style: normal; font-size: .64rem; font-weight: 800; letter-spacing: .03em; text-transform: uppercase; color: #6b7a5e; }
    .fpc-p span { min-width: 0; overflow-wrap: anywhere; }
    .fpc-more { padding: .28rem .65rem; border-radius: 999px; font-size: .74rem; font-weight: 800; color: #3d6823; background: transparent; border: 1px dashed #b9d59a;
        transition: background-color .28s var(--fpc-e); }
    .fpc-more:hover { background: #fff; }
    @keyframes fpcPop { from { opacity: 0; transform: translateY(4px); } }

    .fpc-acts { display: flex; flex-wrap: wrap; align-items: center; gap: .5rem; margin-top: .8rem; }
    .fpc-go { flex: 1 1 12rem; display: inline-flex; align-items: center; justify-content: center; gap: .45rem; min-height: 2.9rem; padding: .55rem 1rem; border-radius: .95rem;
        font-size: .9rem; font-weight: 800; color: #fff; background: #4a7c2a; box-shadow: 0 8px 18px -12px rgb(45 80 22 / .7);
        transition: background-color .28s var(--fpc-e), transform .2s var(--fpc-e); }
    .fpc-go:hover { background: #3d6823; }
    .fpc-go:active { transform: scale(.98); }
    .fpc-go svg { width: 1.05rem; height: 1.05rem; flex: none; }
    .fpc-x { flex: none; min-height: 2.9rem; padding: .5rem .95rem; border-radius: .95rem; font-size: .84rem; font-weight: 800; color: #4b5563; background: #fff; border: 1px solid #e1e7da;
        transition: border-color .28s var(--fpc-e), color .28s var(--fpc-e), background-color .28s var(--fpc-e); }
    .fpc-x:hover { border-color: #fecaca; color: #b91c1c; background: #fff7f7; }

    .fpc-saved { margin-top: .85rem; padding-top: .75rem; border-top: 1px dashed #d7e5c6; }
    .fpc-saved > p { font-size: .68rem; font-weight: 900; letter-spacing: .07em; text-transform: uppercase; color: #5b6b4c; margin-bottom: .45rem; }
    .fpc-sl { display: flex; flex-wrap: wrap; gap: .4rem; }
    .fpc-s { display: inline-flex; align-items: center; gap: .45rem; max-width: 100%; min-height: 2.6rem; padding: .3rem .8rem .3rem .35rem; border-radius: 999px; text-align: left;
        background: #fff; border: 1.5px solid #dfe8d4; color: #14210c; animation: fpcPop .3s var(--fpc-e) both;
        transition: border-color .28s var(--fpc-e), background-color .28s var(--fpc-e), color .28s var(--fpc-e); }
    .fpc-s:hover { border-color: #a8cc7e; }
    .fpc-s > span { flex: none; width: 1.95rem; height: 1.95rem; border-radius: 999px; display: grid; place-items: center; font-size: .95rem; background: #f1f6ea;
        transition: background-color .28s var(--fpc-e); }
    .fpc-s > b { min-width: 0; display: flex; flex-direction: column; font-size: .82rem; font-weight: 800; line-height: 1.2; overflow: hidden; }
    .fpc-s > b em { font-style: normal; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .fpc-s > b small { font-size: .68rem; font-weight: 600; color: #6b7280; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .fpc-s.is-on { border-color: #4a7c2a; background: #4a7c2a; color: #fff; }
    .fpc-s.is-on > span { background: rgb(255 255 255 / .2); }
    .fpc-s.is-on > b small { color: #dcebc9; }

    .fpc-note { display: flex; align-items: flex-start; gap: .5rem; margin-top: .75rem; padding: .6rem .7rem; border-radius: .85rem; font-size: .8rem; line-height: 1.45;
        color: #2d5016; background: #e9f3dd; border: 1px solid #cfe3b8; animation: fpcPop .3s var(--fpc-e) both; }
    .fpc-note svg { flex: none; width: 1rem; height: 1rem; margin-top: .1rem; }
    .fpc-note[hidden] { display: none; }

    /* The line on a simple step that the details filled. */
    .fpc-hint { display: flex; align-items: center; gap: .45rem; width: fit-content; max-width: 100%; margin: -.2rem 0 .75rem; padding: .35rem .7rem .35rem .45rem; border-radius: 999px;
        font-size: .76rem; font-weight: 700; line-height: 1.35; color: #2d5016; background: #e9f3dd; border: 1px solid #cfe3b8; animation: fpcPop .3s var(--fpc-e) both; }
    .fpc-hint i { flex: none; width: 1.25rem; height: 1.25rem; border-radius: 999px; display: grid; place-items: center; color: #fff; background: #4a7c2a; }
    .fpc-hint i svg { width: .7rem; height: .7rem; }

    /* The answers on a report. */
    .fpc-said { display: flex; flex-wrap: wrap; gap: .35rem; }

    @media (prefers-reduced-motion: reduce) {
        .fpc-t, .fpc-t > span, .fpc-t > i, .fpc-sum, .fpc-go, .fpc-x, .fpc-s, .fpc-s > span, .fpc-more { transition: none !important; }
        .fpc-p, .fpc-s, .fpc-note, .fpc-hint { animation: none !important; }
    }

    html.dark .fpc { background: linear-gradient(160deg, #151c11, #18220f); border-color: #2b3a1c; }
    html.dark .fpc-t { background: #10150c; border-color: #2b3a1c; color: #e8efe1; }
    html.dark .fpc-t > small { color: #8d9a81; }
    html.dark .fpc-t > i { box-shadow: 0 0 0 2px #151c11; }
    html.dark .fpc-t.is-done { background: #1c2914; border-color: #4f7a2c; }
    html.dark .fpc-t.is-done > small { color: #a5c97e; }
    html.dark .fpc-p { background: #10150c; border-color: #2b3a1c; color: #dbe8cc; }
    html.dark .fpc-p i { color: #93a684; }
    html.dark .fpc-more { color: #a5c97e; border-color: #3d5a24; }
    html.dark .fpc-more:hover { background: #10150c; }
    html.dark .fpc-x { background: #10150c; border-color: #2b3a1c; color: #c9d6bc; }
    html.dark .fpc-x:hover { background: #2a1414; border-color: #4c1d1d; color: #fca5a5; }
    html.dark .fpc-saved { border-color: #2b3a1c; }
    html.dark .fpc-saved > p { color: #93a684; }
    html.dark .fpc-s { background: #10150c; border-color: #2b3a1c; color: #e8efe1; }
    html.dark .fpc-s > span { background: #1c2616; }
    html.dark .fpc-s > b small { color: #93a684; }
    html.dark .fpc-s.is-on { background: #4a7c2a; border-color: #6b9f3d; color: #fff; }
    html.dark .fpc-s.is-on > b small { color: #dcebc9; }
    html.dark .fpc-note, html.dark .fpc-hint { background: #1c2914; border-color: #3d5a24; color: #cfe6b8; }
</style>
@endpush

@push('scripts')
@php
    $fpcData = [
        'urls' => ['list' => route('fieldprofile.list'), 'troubles' => route('fieldprofile.troubles')],
        'kinds' => collect(\App\Support\FieldProfile::OTHER)->map(fn ($o) => $o[3])->all(),
        'others' => collect(\App\Support\FieldProfile::OTHER)->map(fn ($o) => $o[0])->all(),
    ];
@endphp
<script>
(() => {
    const D = @json($fpcData);
    const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const reduce = () => window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const clone = (o) => JSON.parse(JSON.stringify(o || {}));
    const isRice = (crop) => String(crop || '').startsWith('rice');
    // FieldCatalogue::cropKey(), near enough for sorting: rice is rice, corn is yellow corn.
    const cropKey = (crop) => (!crop ? null : isRice(crop) ? 'rice' : crop === 'corn' ? 'corn_yellow' : crop);
    const CHECK = '<svg fill="none" stroke="currentColor" stroke-width="3.2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>';
    const PEN = '<svg fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4 20h4L18.5 9.5a2.1 2.1 0 00-4-4L4 16v4zM13.5 6.5l4 4"/></svg>';
    const LIST = '<svg fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 6h11M9 12h11M9 18h11M4.5 6h.01M4.5 12h.01M4.5 18h.01"/></svg>';
    const TILES = { soil: ['🟤', 'Soil'], water: ['💧', 'Water'], acid: ['🧪', 'Acidity'], last: ['🌾', 'Last season'], weeds: ['🌿', 'Weeds'], problems: ['🐛', 'Troubles'] };
    const FP = () => window.aneeFieldProfile;

    /* window.api comes with app.js, a module that may land after a page's own
       script has already drawn the card: every fetch waits for it. */
    const apiReady = () => (window.api ? Promise.resolve() : new Promise((r) => window.addEventListener('load', r, { once: true })));
    const get = (url) => apiReady().then(() => window.api(url));

    /* ---- the troubles of a crop: their names and kinds (cached) ---- */
    const ROWS = {};
    function troubleRows(crop) {
        const key = crop || 'rice';
        if (!ROWS[key]) {
            ROWS[key] = get(D.urls.troubles + '?crop=' + encodeURIComponent(key))
                .then((r) => (r.data && r.data.rows) || []).catch(() => { delete ROWS[key]; return []; });
        }
        return ROWS[key];
    }
    async function troubleMap(crop, keys) {
        const want = (keys || []).filter((k) => /^(pest|disease):/.test(k));
        if (!want.length) return {};
        const rows = await troubleRows(crop);
        return Object.fromEntries(rows.map((r) => [r.k, r]));
    }
    const kindOf = (k, map) => {
        if (k.startsWith('other:')) return D.kinds[k.slice(6)] || null;
        const r = map[k];
        if (r && r.kind) return r.kind;
        return k.startsWith('pest:') ? 'Insect' : (k.startsWith('disease:') ? 'Fungus' : null);
    };

    /* ---- FieldProfile::legacy(), in the browser: the simple fields a profile fills ---- */
    function legacyOf(a, kind) {
        a = a || {};
        const water = [].concat(a.water || []), weeds = [].concat(a.weeds || []);
        const troubles = [].concat(a.outbreaks || []).filter((k) => k !== 'none').map((k) => ({ key: k, kind: kind(k), slug: k.replace(/^[a-z]+:/, '') })).filter((t) => t.kind);
        const kinds = troubles.map((t) => t.kind), slugs = troubles.map((t) => t.slug);
        const isPest = troubles.some((t) => t.key.startsWith('pest:')), isDisease = troubles.some((t) => t.key.startsWith('disease:'));
        const had = (s) => slugs.includes(s);
        const has = (list, any) => list.some((x) => any.includes(x));
        const lo = a.phFrom != null ? Number(a.phFrom) : null, hi = a.phTo != null ? Number(a.phTo) : null;
        // PHP's round() pre-rounds to 15 digits, so 4.449999… is 4.5 there: the same here.
        const mid = lo !== null ? Math.round(Number((((lo + (hi ?? lo)) / 2) * 10).toPrecision(15))) / 10 : null;
        const phWord = mid !== null ? (mid < 6 ? 'acidic' : (mid > 7.5 ? 'alkaline' : 'neutral'))
            : (['mild', 'strong'].includes(a.acidity) ? 'acidic' : (a.acidity === 'none' ? 'neutral' : (a.acidity === 'alkaline' ? 'alkaline' : null)));
        const salt = a.salt || '';
        const saline = ['crust', 'sea', 'tips'].includes(salt) || had('salt'), sodic = salt === 'sodic', sulfate = a.acidity === 'sulfate';
        const texture = a.texture || '';
        const soil = ['clay', 'heavy_clay'].includes(texture) ? 'clay' : texture === 'loam' ? 'loam' : texture === 'sandy' ? 'sandy' : a.rocks === 'many' ? 'rocky' : 'unsure';
        const lay = (a.drainage === 'stands' || a.flooding === 'often') ? 'low' : a.slope === 'steep' ? 'steep' : a.slope === 'gentle' ? 'gentle' : ['flat', 'terraced'].includes(a.slope) ? 'flat' : null;
        const reliable = has(water, ['irrigation', 'pump']), partly = has(water, ['river', 'reservoir']), drought = had('drought');
        const rainOnly = water.length === 1 && water[0] === 'rainfed';
        const w = reliable && !drought ? 'irrigated' : (reliable || partly) ? 'limited' : rainOnly ? 'rainfed' : null;
        const auto = water.includes('irrigation') ? (drought ? 'canal_late' : 'canal_ok') : water.includes('pump') ? 'pump_own' : water.includes('river') ? 'river'
            : water.includes('reservoir') ? 'reservoir' : rainOnly ? 'rain_only' : null;
        const floods = ['sometimes', 'often'].includes(a.flooding) || had('flood');
        const weedy = has(weeds, ['many', 'tough', 'sedges', 'weedy_rice']);
        const poorDrain = ['slow', 'stands'].includes(a.drainage);
        const limitedWater = (partly && !reliable) || drought;
        const keep = (list) => list.filter(Boolean);
        const BUGLESS = ['Rodent', 'Bird', 'Snail', 'Other', 'Fungus', 'Bacterium', 'Virus', 'Viroid', 'Water mold', 'Nematode'];
        const SICK = ['Fungus', 'Bacterium', 'Virus', 'Viroid', 'Water mold', 'Nematode'];
        return {
            soilConditions: keep([phWord, sodic && 'sodic', saline && 'saline', sulfate && 'acid_sulfate']), phValue: mid, phLow: lo, phHigh: hi ?? lo,
            soil, phLevel: phWord || 'unsure', soilExtra: keep([sodic && 'sodic', saline && 'saline', sulfate && 'acid_sulfate']), lay,
            water: w, waterNpk: w === 'limited' ? 'partial' : w, waterAuto: auto,
            problemsWhen: keep([floods && 'floods', texture === 'heavy_clay' && 'cracking', texture === 'sandy' && 'sandy', limitedWater && 'water_source', rainOnly && 'rainfed',
                salt === 'sea' && 'sea', isPest && 'pests', weedy && 'weeds', poorDrain && 'drainage']),
            problemsVariety: keep([drought && 'drought', floods && 'floods', limitedWater && 'water_source', saline && 'salinity', phWord === 'acidic' && 'acidic', phWord === 'alkaline' && 'alkaline',
                sodic && 'sodic', sulfate && 'acid_sulfate', isPest && 'pests', isDisease && 'disease', weedy && 'weeds', poorDrain && 'drainage',
                (a.organic === 'low' || had('nutrient')) && 'low_fertility', ['gentle', 'steep'].includes(a.slope) && 'slope']),
            problemsAuto: keep([weedy && 'weeds', floods && 'flood', drought && 'drought', kinds.some((k) => !BUGLESS.includes(k)) && 'insects', kinds.some((k) => SICK.includes(k)) && 'diseases',
                kinds.includes('Rodent') && 'rats', kinds.includes('Bird') && 'birds', kinds.includes('Snail') && 'snails', had('lodging') && 'lodging', saline && 'salt', a.drainage === 'stands' && 'waterlog']),
            soilSignsAuto: keep([texture === 'heavy_clay' && 'cracks', texture === 'clay' && 'sticky', texture === 'sandy' && 'gritty', texture === 'loam' && 'crumbly',
                salt === 'crust' && 'white_crust', salt === 'sodic' && 'black_crust', salt === 'sea' && 'salty', salt === 'tips' && 'burnt_tips', sulfate && 'rusty', a.color === 'red' && 'red_soil',
                (a.organic === 'high' || a.color === 'dark') && 'dark_rich', a.organic === 'low' && 'pale_hard', a.drainage === 'stands' && 'stays_wet']),
        };
    }
    async function legacy(answers, crop) {
        const map = await troubleMap(crop, (answers || {}).outbreaks);
        return legacyOf(answers, (k) => kindOf(k, map));
    }

    /* ---- the answers as [short, words], the way FieldProfile::pairs says them ---- */
    function pairs(v, names) {
        const Q = FP().questions, out = [];
        Object.entries(Q).forEach(([k, q]) => {
            const a = v[k];
            if (a === undefined || a === null || a === '' || (Array.isArray(a) && !a.length)) return;
            if (k === 'outbreaks') {
                const list = [].concat(a);
                out.push([q.short, list.includes('none') ? 'None' : list.map((x) => (names[x] && names[x].n) || (x.startsWith('other:') ? (D.others[x.slice(6)] || x.slice(6)) : x.replace(/^[a-z]+:/, '').replace(/-/g, ' '))).join(', ')]);
                return;
            }
            const words = [].concat(a).map((x) => (q.o[x] || [])[0]).filter(Boolean);
            if (words.length) out.push([q.short, words.join(', ')]);
            if (k === 'acidity' && v.phFrom != null) out.push(['Known pH', v.phFrom === (v.phTo ?? v.phFrom) ? String(v.phFrom) : v.phFrom + ' to ' + v.phTo]);
        });
        if (v.phFrom != null && !v.acidity) out.push(['Known pH', v.phFrom === (v.phTo ?? v.phFrom) ? String(v.phFrom) : v.phFrom + ' to ' + v.phTo]);
        return out;
    }
    const chip = (p, i) => '<span class="fpc-p" style="animation-delay:' + Math.min(i, 10) * 25 + 'ms"><i>' + esc(p[0]) + '</i><span>' + esc(p[1]) + '</span></span>';

    /* The farm's saved profiles, once a page. */
    let SAVED = null;
    const savedRows = (fresh) => { if (!SAVED || fresh) SAVED = get(D.urls.list).then((r) => (r.data && r.data.rows) || []).catch(() => { SAVED = null; return []; }); return SAVED; };
    /* A saved profile, fitted to this crop as the kit fits it. */
    function fitted(answers, crop) {
        const Q = FP().questions, keep = {};
        Object.entries(answers || {}).forEach(([k, v]) => { if (Q[k] || k === 'phFrom' || k === 'phTo') keep[k] = clone(v); });
        if (crop && !isRice(crop)) {
            delete keep.level;
            ['weeds', 'stubble'].forEach((k) => {
                if (keep[k] === undefined) return;
                const left = [].concat(keep[k]).filter((x) => !(Q[k].o[x] && Q[k].o[x][2] === 'rice'));
                if (!left.length) delete keep[k]; else keep[k] = Array.isArray(keep[k]) ? left : left[0];
            });
        }
        return keep;
    }
    const sameAs = (a, b) => {
        const norm = (o) => JSON.stringify(Object.keys(o || {}).sort().map((k) => [k, Array.isArray(o[k]) ? o[k].slice().sort() : o[k]]));
        return norm(a) === norm(b);
    };

    /* ---- one card ---- */
    function mount(el, o) {
        o = o || {};
        const st = { value: clone(o.value), from: o.from || null, open: false, more: false, filled: '' };
        const crop = () => (typeof o.crop === 'function' ? o.crop() : o.crop) || null;
        const title = () => (typeof o.title === 'function' ? o.title() : o.title) || 'Your field';
        el.innerHTML = '<div class="fpc">'
            + '<div class="fpc-tiles" data-fpc-tiles></div>'
            + '<div class="fpc-sum" data-fpc-sum><div><div class="fpc-chips" data-fpc-chips></div></div></div>'
            + '<div class="fpc-acts"><button type="button" class="fpc-go" data-fpc-go>' + PEN + '<span>Answer the questions</span></button><button type="button" class="fpc-x" data-fpc-x hidden>Clear</button></div>'
            + '<p class="fpc-note" data-fpc-note hidden></p>'
            + '<div class="fpc-saved" data-fpc-saved hidden><p>Use the answers from</p><div class="fpc-sl" data-fpc-sl></div></div>'
            + '</div>';
        const $q = (s) => el.querySelector(s);

        function paintTiles() {
            const Q = FP().questions, v = st.value, c = crop();
            $q('[data-fpc-tiles]').innerHTML = FP().pagesFor(c).map((p) => {
                const qs = Object.entries(Q).filter(([, q]) => q.page === p && !(c && q.only === 'rice' && !isRice(c)));
                const done = qs.filter(([k]) => { const a = v[k]; return a !== undefined && a !== null && a !== '' && !(Array.isArray(a) && !a.length); }).length
                    + (p === 'acid' && v.phFrom != null && !v.acidity ? 1 : 0);
                const t = TILES[p] || ['•', p];
                return '<div class="fpc-t' + (done ? ' is-done' : '') + '"><span aria-hidden="true">' + t[0] + '</span><b>' + esc(t[1]) + '</b><small>' + (done ? Math.min(done, qs.length) + ' of ' + qs.length : 'Not yet') + '</small><i aria-hidden="true">' + CHECK + '</i></div>';
            }).join('');
        }
        async function paintSum() {
            const v = st.value, n = FP().count(v);
            const map = await troubleMap(crop(), v.outbreaks);
            if (v !== st.value) return;
            const all = pairs(v, map);
            const cap = st.more ? all.length : 5;
            $q('[data-fpc-chips]').innerHTML = all.slice(0, cap).map(chip).join('')
                + (all.length > cap ? '<button type="button" class="fpc-more" data-fpc-more>' + (all.length - cap) + ' more</button>' : '')
                + (st.more && all.length > 5 ? '<button type="button" class="fpc-more" data-fpc-less>Show less</button>' : '');
            $q('[data-fpc-sum]').classList.toggle('is-on', n > 0);
            $q('[data-fpc-go] span').textContent = n ? 'Change the answers' : 'Answer the questions';
            $q('[data-fpc-x]').hidden = !n;
        }
        function paintNote() {
            const n = FP().count(st.value), bits = [];
            if (st.from && n) bits.push((st.from.edited ? 'Started from your saved answers for ' : 'From your saved answers for ') + st.from.name + '.');
            if (st.filled) bits.push(st.filled);
            const note = $q('[data-fpc-note]');
            note.hidden = !bits.length;
            note.innerHTML = bits.length ? LIST + '<span>' + esc(bits.join(' ')) + '</span>' : '';
        }
        async function paintSaved() {
            // The fields of this crop first, then the rest, the newest first within each.
            const ck = cropKey(crop());
            const rows = (await savedRows()).slice().sort((a, b) => (ck && a.crop === ck ? 0 : 1) - (ck && b.crop === ck ? 0 : 1));
            const box = $q('[data-fpc-saved]');
            box.hidden = !rows.length;
            if (!rows.length) return;
            const cap = st.allSaved ? rows.length : 4;
            $q('[data-fpc-sl]').innerHTML = rows.slice(0, cap).map((r, i) => '<button type="button" class="fpc-s' + (st.from && st.from.id === r.id ? ' is-on' : '') + '" data-fpc-use="' + r.id + '" aria-pressed="' + !!(st.from && st.from.id === r.id) + '" style="animation-delay:' + i * 40 + 'ms">'
                + '<span aria-hidden="true">' + (r.lotId ? '🌾' : '📍') + '</span><b><em>' + esc(r.name) + '</em><small>' + esc([r.cropLabel, r.count + (r.count === 1 ? ' answer' : ' answers')].filter(Boolean).join(' · ')) + '</small></b></button>').join('')
                + (rows.length > cap ? '<button type="button" class="fpc-more" data-fpc-all>' + (rows.length - cap) + ' more</button>' : '');
        }
        async function changed() {
            paintTiles();
            paintSum();
            st.filled = '';
            paintNote();
            if (o.onChange) {
                try {
                    const L = await legacy(st.value, crop());
                    const said = await o.onChange(clone(st.value), L, api);
                    st.filled = said || '';
                } catch (err) { console.error(err); }
            }
            paintNote();
            paintSaved();
        }
        function set(v, from) {
            st.value = clone(v);
            st.from = from || null;
            st.more = false;
            return changed();
        }
        el.addEventListener('click', async (e) => {
            if (e.target.closest('[data-fpc-go]')) {
                if (!FP()) return;
                const got = await FP().open({ crop: crop(), value: st.value, title: title() });
                if (got === null) return;
                // Answers the sheet took whole from another saved field are that field's, not an edit of this one.
                const same = (await savedRows()).find((r) => sameAs(fitted(r.answers, crop()), got));
                if (same) st.from = { id: same.id, name: same.name, answers: clone(got), edited: false };
                else if (st.from) st.from = Object.assign({}, st.from, { edited: !sameAs(got, st.from.answers) });
                st.value = got;
                changed();
                return;
            }
            if (e.target.closest('[data-fpc-x]')) { set({}, null); return; }
            if (e.target.closest('[data-fpc-more]')) { st.more = true; paintSum(); return; }
            if (e.target.closest('[data-fpc-less]')) { st.more = false; paintSum(); return; }
            if (e.target.closest('[data-fpc-all]')) { st.allSaved = true; paintSaved(); return; }
            const use = e.target.closest('[data-fpc-use]');
            if (use) {
                const rows = await savedRows();
                const r = rows.find((x) => String(x.id) === use.dataset.fpcUse);
                if (!r) return;
                if (st.from && st.from.id === r.id) { set({}, null); return; }
                const answers = fitted(r.answers, crop());
                set(answers, { id: r.id, name: r.name, answers: clone(answers), edited: false });
                window.toast?.('Filled from ' + r.name + '. Change anything that differs this season.');
            }
        });

        const api = {
            el,
            value: () => clone(st.value),
            from: () => st.from,
            count: () => FP().count(st.value),
            payload: () => ({ fieldProfile: FP().count(st.value) ? clone(st.value) : null, fieldProfileFrom: st.from && FP().count(st.value) ? st.from.id : null }),
            set,
            clear: () => set({}, null),
            refresh: () => { paintTiles(); paintSum(); paintSaved(); },
        };
        paintTiles();
        paintSum();
        paintSaved();
        return api;
    }

    /* The line a simple step shows when the details filled it. */
    function hint(el, on, words, after) {
        if (!el) return;
        let h = el.querySelector('.fpc-hint');
        if (!on) { if (h) h.remove(); return; }
        const text = words || 'Filled in from your field in detail. Change anything that is not right.';
        if (h) { h.querySelector('span').textContent = text; return; }
        h = document.createElement('p');
        h.className = 'fpc-hint';
        h.innerHTML = '<i aria-hidden="true">' + CHECK + '</i><span></span>';
        h.querySelector('span').textContent = text;
        // Under the step's own line, or under the element named.
        const sub = after || el.querySelector(':scope > .wtp-sub, :scope > .sat-sub, :scope > .au-sub, :scope > .np-sub');
        if (sub) sub.after(h); else el.prepend(h);
    }

    /* The saved answers (FieldProfile::pairs) as chips, for a report. */
    const said = (list) => (Array.isArray(list) && list.length) ? '<div class="fpc-said">' + list.map((p, i) => chip([p[0], p[1]], i)).join('') + '</div>' : '';

    window.aneeFieldCard = { mount, legacy, legacyOf, hint, said, pairs };
})();
</script>
@endpush
@endonce
