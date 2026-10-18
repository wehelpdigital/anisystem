{{-- The in-app Pest Finder and Disease Finder (2026-10-14): the crop, then
     where the damage shows (pests) or what is seen (diseases), and back come
     the matching pests or diseases as cards, each with its sign and what to
     spray (every active ingredient on a row of its own, its IRAC or FRAC
     group beside it, who sells it and the products sold here under it), or
     why no spray helps. A card
     opens its entry in the whole catalogue, a full screen sheet the strip
     on top opens too. Never a link to the public catalogue.

     Expects $book (App\Support\FieldBook::problems()) and may take
     $defaultCrop (the newest season's crop). ?crop= picks the crop. --}}
@php
    $F = \App\Support\FieldCatalogue::class;
    $crops = $book['crops'];
    $w = $book['words'];
    $fdCrop = $F::cropKey(request('crop') ?: ($defaultCrop ?? null));
    $fdCrop = isset($crops[$fdCrop]) ? $fdCrop : array_key_first($crops);
    $faces = collect($book['entries'])->filter(fn ($e) => $e['thumb'] && in_array($fdCrop, $e['crops'], true))->take(3);
    if ($faces->count() < 3) {
        $faces = collect($book['entries'])->filter(fn ($e) => $e['thumb'])->take(3);
    }
    // Each photo's original page and licence, for the credit under it when it is shown large
    // (2026-10-17): known for the catalogue's own photos, not for a page's own picture.
    $fdEntries = collect($book['entries'])->map(function ($e) use ($F, $book) {
        $ph = $e['photo'] ? $F::photo($book['section'], $e['k']) : null;

        return $ph && \App\Support\SitePages::img($ph['src']) === $e['photo'] ? $e + ['pg' => (string) ($ph['page'] ?? ''), 'lic' => (string) ($ph['license'] ?? '')] : $e;
    })->all();
@endphp
@include('field-helpers.partials.kit')
<style>
    /* 2026-10-19: when to act, the rows past the first six, the not advised line, prevention. */
    .fd-when { margin: 0 0 .6rem; padding: .55rem .7rem; border-radius: .8rem; font-size: .86rem; line-height: 1.5; color: #1f2937; background: #f3f7ee; border: 1px solid #e1ebd5; }
    .fd-when b { display: block; font-size: .68rem; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; color: #3d6823; }
    .fd-more { margin-top: .45rem; }
    .fd-more-btn { display: inline-flex; align-items: center; gap: .35rem; min-height: 2.4rem; padding: .35rem .75rem; border-radius: 999px; font-size: .8rem; font-weight: 800;
        color: #2d5016; background: #fff; border: 1px solid #cfe0bb; transition: background-color .28s cubic-bezier(.22,1,.36,1), border-color .28s cubic-bezier(.22,1,.36,1); }
    .fd-more-btn:hover { background: #f3f7ee; border-color: #a8cc7e; }
    .fd-more-btn svg { width: .95rem; height: .95rem; transition: transform .28s cubic-bezier(.22,1,.36,1); }
    .fd-more.is-open .fd-more-btn svg { transform: rotate(180deg); }
    .fd-more-fold { display: grid; grid-template-rows: 0fr; transition: grid-template-rows .4s cubic-bezier(.22,1,.36,1); }
    .fd-more.is-open .fd-more-fold { grid-template-rows: 1fr; }
    .fd-more-fold > div { min-height: 0; overflow: hidden; }
    .fd-more-fold .fh-ings { opacity: 0; transition: opacity .28s cubic-bezier(.22,1,.36,1); }
    .fd-more.is-open .fd-more-fold .fh-ings { opacity: 1; }
    .fd-off { margin: .1rem 0 .45rem; color: #8a5a00; }
    html.dark .fd-off { color: #f5d98a; }
    .fd-avoid { margin-top: .6rem; }
    .fd-avoid em { font-style: normal; font-weight: 800; }
    .fd-avoid p + p { margin-top: .35rem; }
    .fd-avoid .fd-more-fold p { margin-top: .35rem; }
    .fd-avoid .fd-more-btn { color: #6b4a00; border-color: #f0d38a; background: #fffdf5; }
    .fd-avoid .fd-more-fold > div { opacity: 0; transition: opacity .28s cubic-bezier(.22,1,.36,1); }
    .fd-avoid .fd-more.is-open .fd-more-fold > div { opacity: 1; }
    html.dark .fd-avoid .fd-more-btn { color: #f5d98a; border-color: #5c4a1c; background: #221c0d; }
    .fd-rotate b { color: #3d6823; }
    .fd-list { display: grid; gap: .35rem; margin: .1rem 0 0; padding: 0; list-style: none; }
    .fd-list li { position: relative; padding-left: 1.05rem; font-size: .88rem; line-height: 1.5; color: #1f2937; }
    .fd-list li::before { content: ''; position: absolute; left: .2rem; top: .55em; width: .38rem; height: .38rem; border-radius: 999px; background: #86b556; }
    .fhd-sec .fd-list li { font-size: .93rem; }
    .fd-prev { margin-top: .7rem; }
    .fd-prev .fd-list { margin-top: .35rem; }
    .fd-vec { margin-top: .6rem; }
    .fd-vec .fh-fine { margin: .25rem 0 0; }
    .fd-all { margin-top: .45rem; min-height: 2.2rem; padding: 0; font-size: .82rem; font-weight: 800; color: #3d6823; background: none; border: 0; text-decoration: underline; text-underline-offset: 3px; }
    html.dark .fd-when { color: #e5e7eb; background: #18210f; border-color: #2c3a20; }
    html.dark .fd-when b, html.dark .fd-rotate b, html.dark .fd-all { color: #a8cc7e; }
    html.dark .fd-more-btn { color: #cfe0bb; background: #151b12; border-color: #2c3a20; }
    html.dark .fd-more-btn:hover { background: #1b2416; border-color: #4b6b2e; }
    html.dark .fd-list li { color: #e5e7eb; }
    @media (prefers-reduced-motion: reduce) { .fd-more-btn, .fd-more-btn svg, .fd-more-fold, .fd-more-fold .fh-ings { transition: none; } }
</style>

<button type="button" class="fh-cat-open" data-fh-cat>
    <span class="fh-faces" aria-hidden="true">@foreach ($faces as $e)<img src="{{ $e['thumb'] }}" alt="" loading="lazy" referrerpolicy="no-referrer">@endforeach</span>
    <span class="fh-cat-t"><b>Open the {{ $w['noun'] }} catalogue</b><small>{{ count($book['entries']) }} {{ $w['nouns'] }} of {{ count($crops) }} crops, with photos and what to spray</small></span>
    <span class="fh-cat-go"><span>Open</span><svg fill="none" stroke="currentColor" stroke-width="2.6" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg></span>
</button>

<div class="wc">
    <div class="wc-ask">
        <div class="wc-q">
            <b><i>1</i>Which crop?</b>
            <small>{{ count($crops) }} crops, from palay to durian</small>
            <div id="fhCrop">
                @include('public.site.partials.crop-pick', ['id' => 'fhCropSheet', 'crops' => $crops, 'current' => $fdCrop, 'title' => 'Which crop?', 'noun' => $w['nouns']])
            </div>
        </div>
        {{-- Step two is a tag that opens its answers in a sheet (FH.optTag, 2026-10-17, the owner). --}}
        <div class="wc-q">
            <b><i>2</i>{{ $w['askWhere'] }}</b>
            <small>Pick one, or leave it to see all</small>
            <div class="fh-otag-box" id="fhWhere"></div>
        </div>
        {{-- Below two columns the answer is under these questions: this says what waits there. --}}
        <button type="button" class="wc-go" id="fhGo" aria-controls="fhCard"><span>See the {{ $w['nouns'] }}</span>
            <svg fill="none" stroke="currentColor" stroke-width="2.6" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg></button>
    </div>
    <div class="wc-out" aria-live="polite">
        <div class="wc-card" id="fhCard">
            <noscript><div class="wc-body"><p>Turn on JavaScript to use the finder.</p></div></noscript>
        </div>
        <p class="wc-fine">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.3 3.9L1.8 18a2 2 0 001.7 3h17a2 2 0 001.7-3L13.7 3.9a2 2 0 00-3.4 0z"/></svg>
            <span>Many {{ $w['nouns'] }} look alike. Check the signs in the catalogue before you spend on a spray, and when you do, use only products registered with the Fertilizer and Pesticide Authority (FPA) and follow the label.</span>
        </p>
    </div>
</div>

@include('field-helpers.partials.catalogue', [
    'id' => 'fhCat', 'title' => ucfirst($w['noun']) . ' catalogue', 'sub' => count($book['entries']) . ' ' . $w['nouns'] . ' of ' . count($crops) . ' crops',
    'placeholder' => $book['isPests'] ? 'Search any name, like atangya or kuhol' : 'Search any name, like tungro or bugtok',
    'crops' => $crops, 'nouns' => $w['nouns'], 'allLine' => 'Every ' . $w['noun'],
    'facetAll' => 'Every kind', 'facetTitle' => $book['isPests'] ? 'Insects, mites, snails' : 'Fungi, bacteria, viruses',
])

<script type="application/json" id="fhData">{!! json_encode([
    'entries' => $fdEntries,
    'crops' => collect($crops)->map(fn ($c) => $c[1]),
    'cropNames' => $book['cropNames'],
    'crop' => $fdCrop,
    'where' => $book['askOpts'],
    'ask' => $w['askWhere'],
    'parts' => $book['parts'],
    'signs' => $book['signs'],
    'facets' => $book['facets'],
    'noun' => $w['noun'], 'nouns' => $w['nouns'], 'pests' => $book['isPests'],
    // Only the products this page can show (FieldBook::problemProducts).
    'products' => $book['products'],
    // Systemic, translaminar or contact, per active ingredient (App\Support\AiMobility).
    'mobility' => $book['mobility'] ?? null,
    // The varieties rated to hold up against each one, per crop (FieldBook::resistant).
    'resist' => (object) ($book['resist'] ?? []),
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
@push('scripts')
<script>
(() => {
    /* The finder: the crop, then where the damage shows (pests) or what is
       seen (diseases); the matches come back as cards, the crop's worst
       first. A second step with nothing for that crop is greyed out. */
    const el = document.getElementById('fhData'), card = document.getElementById('fhCard');
    if (!el || !card || !window.FH) return;
    const FH = window.FH, esc = FH.esc, I = FH.icon;
    const D = JSON.parse(el.textContent);
    const cropTag = document.querySelector('#fhCrop .cp-tag'), whereBox = document.getElementById('fhWhere');
    const go = document.getElementById('fhGo'), goText = go?.querySelector('span');
    const st = { crop: D.crop, where: '' };
    // Step two: a tag, its answers in a sheet (FH.optTag, 2026-10-17, the owner); the grey ones are those this crop never shows.
    let greyed = false;
    const whereTag = FH.optTag({ box: whereBox, title: D.ask, icon: D.pests ? I.pin : I.eye, empty: D.pests ? 'Pick where you see it' : 'Pick what you see', opts: D.where,
        note: () => (greyed ? 'The grey ones do not show on ' + String(D.crops[st.crop] || 'this crop').toLowerCase() + '.' : ''),
        all: 'Not sure', allSub: 'Show every ' + D.noun + ' of the crop', onPick: (v) => { st.where = v; render(true); } });
    const KIND_ICON = Object.fromEntries(D.facets.map((f) => [f[0], f[2]]));
    const P = D.products || {};
    // A Philippine company's products come first in every list (the owner, 2026-10-19), and say so with its flag.
    Object.values(P.cards || {}).forEach((c) => { if (c && c.origin === 'ph' && c.company && c.company.indexOf('🇵🇭') !== 0) c.company = '🇵🇭 ' + c.company; });
    Object.assign(FH.prodCards, P.cards || {});
    FH.setMobility(D.mobility);
    const cropWord = (c) => (c && D.cropNames[c] ? D.cropNames[c][0].toLowerCase() : '');
    // The ingredients for one crop (2026-10-19): the registered ones whose labels name it there, best first
    // ([name, group, tier, products, companies, why]), else the entry's own list.
    const aiOf = (i, crop) => { const r = crop && i.aiBy ? i.aiBy[crop] : null; return (r && r.length) ? r : (i.ai || []).map(([n, g]) => [n, g, '', 0, 0, '']); };
    const refOf = (i) => (D.pests ? 'p:' : 'd:') + i.k;
    // The products sold here for one ingredient: those whose label names this very pest or disease on the crop
    // ("*": any crop), else the ingredient on the crop's labels. Philippine companies first (the server's order).
    const prodsOf = (ref, name, crop) => {
        const c = crop || '*', n = FH.norm(name), own = (((P.pi || {})[ref] || {})[c] || {})[n];
        return (own || ((P.ip || {})[n] || {})[c] || []).map((k) => FH.prodCards[k]).filter(Boolean);
    };
    const labelled = (slug, crop) => (((P.pp || {})[slug] || {})[crop || '*'] || []).map((k) => FH.prodCards[k]).filter(Boolean);
    const sold = (i, crop) => aiOf(i, crop).some(([n]) => prodsOf(refOf(i), n, crop).length);

    // What to spray against one pest or disease, or why no spray helps. Shown only opened (a card's
    // fold, the catalogue entry), so each ingredient's note on how it moves comes under its chips.
    // The first SHOW rows show; the rest fold under "N more registered for it".
    const TIER = { A: 'First choice', C: 'Last resort, old or broad chemistry' };
    const SHOW = 6;
    const modeOf = (r) => [TIER[r[2]] || '', r[3] ? r[3] + ' registered product' + (r[3] === 1 ? '' : 's') : '', r[5] || ''].filter(Boolean).join('. ');
    const listOf = (ref, rows, crop) => {
        const all = rows.map((r) => FH.ing(r[0], r[1], modeOf(r), prodsOf(ref, r[0], crop)));
        if (all.length <= SHOW + 1) return FH.ingList(all, { notes: true });
        const n = all.length - SHOW;
        return FH.ingList(all.slice(0, SHOW), { notes: true })
            + '<div class="fd-more"><button type="button" class="fd-more-btn" data-more data-n="' + n + '" aria-expanded="false">' + I.chev + '<span>' + n + ' more registered for it</span></button>'
            + '<div class="fd-more-fold"><div>' + FH.ingList(all.slice(SHOW), { notes: true }) + '</div></div></div>';
    };
    // Labels name it on other crops but not on this one: say so, so nobody sprays off label without knowing.
    const offLabel = (i, crop) => crop && i.aiBy && Object.keys(i.aiBy).length && !i.aiBy[crop]
        ? '<p class="fh-fine fd-off">No product sold here names it on ' + esc(cropWord(crop) || 'this crop') + ' yet. These are the ingredients its labels name on other crops: use one only where the label names your crop, or ask your technician.</p>' : '';
    const sprayOf = (i, crop) => { const rows = aiOf(i, crop); return rows.length ? offLabel(i, crop) + listOf(refOf(i), rows, crop) : ''; };
    const noSpray = (i) => i.no ? '<p class="fh-no">' + I.warn + '<span><b>Before you spray</b>' + esc(i.no) + '</span></p>' : '';
    // Registered for it, but not advised (resurgence, resistance, an adjuvant): a warning, never a spray.
    // Ingredients that share a reason read as one line ("Cypermethrin, alpha cypermethrin and permethrin: ..."); three lines show, the rest fold.
    const names = (ns) => ns.map((n, k) => (k && !/^[A-Z]{2}/.test(n) ? n.charAt(0).toLowerCase() + n.slice(1) : n)).join(', ').replace(/, ([^,]*)$/, ' and $1');
    const avoidOf = (i, crop) => {
        const list = (i.avoid || []).filter((a) => !a[2] || !crop || a[2].includes(crop));
        if (!list.length) return '';
        const groups = [], at = {};
        list.forEach(([n, w]) => { const k = w || ''; if (at[k] === undefined) { at[k] = groups.length; groups.push([[], w]); } groups[at[k]][0].push(n); });
        const line = ([ns, w]) => '<p><em>' + esc(names(ns)) + '</em>' + (w ? ': ' + esc(w) : '') + '</p>';
        const rest = groups.slice(3);
        return '<div class="fh-no fd-avoid">' + I.warn + '<div><b>On labels for it, but not advised</b>' + groups.slice(0, 3).map(line).join('')
            + (rest.length ? '<div class="fd-more"><button type="button" class="fd-more-btn" data-more data-n="' + rest.length + '" data-word="more not advised" aria-expanded="false">' + I.chev
                + '<span>' + rest.length + ' more not advised</span></button><div class="fd-more-fold"><div>' + rest.map(line).join('') + '</div></div></div>' : '') + '</div></div>';
    };
    const whenOf = (i) => i.threshold ? '<p class="fd-when"><b>When to act</b>' + esc(i.threshold) + '</p>' : '';
    const rotateOf = (i) => i.rotate ? '<p class="fh-fine fd-rotate"><b>Resistance:</b> ' + esc(i.rotate) + '</p>' : '';
    const linesOf = (list, max) => '<ul class="fd-list">' + list.slice(0, max || list.length).map((l) => '<li>' + esc(l) + '</li>').join('') + '</ul>';
    // A virus: no spray cures the plant; its vectors' own lists, on the crop in view when they attack it.
    const vectorOf = (i, crop) => (i.vec || []).map((v) => {
        const c = crop && v.ai && v.ai[crop] ? crop : '', rows = c ? v.ai[c] : (v.any || []);
        const nm = String(v.name).toLowerCase();
        return '<div class="fd-vec"><span class="fh-spray-h">' + I.drop + 'Slow the spread: control the ' + esc(nm) + '</span>'
            + '<p class="fh-fine">The ' + esc(nm) + ' carries it from plant to plant. These work on the insect' + (c ? ' on ' + esc(cropWord(c)) : '') + ', not on the sick plant.</p>'
            + (crop && !c && rows.length ? '<p class="fh-fine fd-off">No product sold here names the ' + esc(nm) + ' on ' + esc(cropWord(crop) || 'this crop') + ' yet. These are labelled for it on other crops: use one only where the label names your crop, or ask your technician.</p>' : '')
            + (rows.length ? listOf('p:' + v.k, rows, c) : '') + '</div>';
    }).join('');
    const legend = D.pests ? 'The IRAC group tells how an insecticide kills.' : 'The FRAC group tells how a fungicide works.';
    // "N more registered for it" opens and folds its rows (eased, like every fold here).
    document.addEventListener('click', (e) => {
        const b = e.target.closest('[data-more]');
        if (!b) return;
        const w = b.closest('.fd-more'), open = !w.classList.contains('is-open');
        w.classList.toggle('is-open', open);
        b.setAttribute('aria-expanded', String(open));
        b.querySelector('span').textContent = open ? 'Show fewer' : b.dataset.n + ' ' + (b.dataset.word || 'more registered for it');
    });
    // The Tagalog help flag of a kind ("Water mold" is kind-water-mold).
    const kindTl = (k) => 'kind-' + String(k || '').toLowerCase().trim().replace(/\s+/g, '-');

    /* ---- One entry, in the catalogue's second pane ------------------- */
    const labels = (keys, book) => keys.map((k) => book[k]).filter(Boolean);
    const chips = (list) => '<ul class="fhd-chips">' + list.map((l) => '<li>' + esc(l) + '</li>').join('') + '</ul>';
    // The varieties rated to hold up against it, on the crop in view or its first rated crop (2026-10-18).
    const RES_WORD = { HR: 'Highly resistant', R: 'Resistant', MR: 'Moderately resistant' };
    const resistOf = (i, crop) => {
        const by = (D.resist || {})[i.k];
        if (!by) return '';
        const c = crop && by[crop] ? crop : Object.keys(by)[0];
        const list = by[c] || [];
        if (!list.length) return '';
        const cn = D.cropNames[c] ? D.cropNames[c][0].toLowerCase() : '';
        return FH.sec(I.sprout, 'Varieties that hold up against it' + (cn ? ' (' + cn + ')' : ''),
            '<ul class="fhd-chips">' + list.map(([n, a, code]) => '<li title="' + esc(RES_WORD[code] || '') + '">' + esc(n + (a ? ' (' + a + ')' : '')) + ' · ' + esc(code) + '</li>').join('') + '</ul>'
            + '<p class="fh-fine">Rated in their trials: HR highly resistant, R resistant, MR moderately resistant. A rating can break down when a new strain comes, so keep scouting.</p>');
    };
    const detail = (i, ctx) => {
        const parts = labels(i.parts || [], D.parts), signs = labels(i.signs || [], D.signs);
        // The crop the catalogue is on, else any crop (each product says what its label names).
        const crop = ctx && ctx.crop && i.crops.includes(ctx.crop) ? ctx.crop : '';
        const spray = sprayOf(i, crop), named = labelled(i.k, crop);
        return '<article class="fhd" style="--g: ' + i.hue + '">' + FH.photo(i)
            + '<div class="fhd-body">'
            + '<header class="fhd-head"><span class="fhd-pill"' + FH.tlAttr(kindTl(i.kind)) + '>' + esc(KIND_ICON[i.kind] || '') + ' ' + esc(i.kind) + '</span><h2>' + esc(i.name) + '</h2>'
            + (i.sci ? '<p class="fhd-sci">' + esc(i.sci) + '</p>' : '')
            + (i.local ? '<p class="fhd-local"><b>Local names:</b> ' + esc(i.local) + '</p>' : '') + '</header>'
            + (i.hint ? FH.sec(I.eye, 'Signs to look for', '<p>' + esc(i.hint) + '</p>') : '')
            + (signs.length ? FH.sec(I.eye, 'What you see', chips(signs)) : '')
            + (parts.length ? FH.sec(I.pin, D.pests ? 'Where the damage shows' : 'Where it shows', chips(parts)) : '')
            + FH.sec(I.sprout, (D.pests ? 'Crops it attacks' : 'Crops it infects') + ' (' + i.crops.length + ')', FH.cropChips(i.crops, D.cropNames, st.crop))
            + (i.threshold ? FH.sec(I.eye, 'When to act', '<p>' + esc(i.threshold) + '</p>') : '')
            + (spray ? FH.sec(I.drop, 'What to spray' + (crop && i.aiBy && i.aiBy[crop] ? ' on ' + cropWord(crop) : ''), spray + avoidOf(i, crop)
                + '<p class="fh-fine">' + legend + ' Switch to a different group next time, so the ' + D.noun + ' does not learn to survive it.</p>' + rotateOf(i)
                + (sold(i, crop) ? FH.rateLine(D.noun) : '') + FH.SHOP_NOTE, 'what-to-spray') : (avoidOf(i, crop) ? '<section>' + avoidOf(i, crop) + '</section>' : ''))
            + (i.no ? '<section>' + noSpray(i) + '</section>' : '')
            + ((i.vec || []).length ? FH.sec(I.drop, 'The insect that spreads it', vectorOf(i, crop) + FH.rateLine('pest')) : '')
            + ((i.prevent || []).length ? FH.sec(I.sprout, 'Prevent it, and what to do without spraying', linesOf(i.prevent)) : '')
            + ((i.bio || []).length ? FH.sec(I.sprout, 'Natural enemies and biological help', linesOf(i.bio)) : '')
            + resistOf(i, crop)
            + (named.length ? FH.sec(I.bag, 'Products labelled for this' + (crop && D.cropNames[crop] ? ' on ' + D.cropNames[crop][0].toLowerCase() : ''), FH.prodStrip(named, { help: !spray })
                + (spray ? '' : FH.rateLine(D.noun))) : '')
            + FH.sources(i.sources)
            + '</div></article>';
    };
    const cat = FH.catalogue({
        dlg: document.getElementById('fhCat'), items: D.entries, noun: D.noun, nouns: D.nouns,
        tag: (i) => i.kind, detail,
        facet: { title: D.pests ? 'Which kind of pest?' : 'Which kind of disease?', all: 'Every kind', allIcon: D.pests ? '🐛' : '🍄', opts: D.facets, icon: (k) => KIND_ICON[k] || '' },
    });
    document.querySelectorAll('[data-fh-cat]').forEach((b) => b.addEventListener('click', () => cat.open({ crop: '' })));

    /* ---- The finder's answer ----------------------------------------- */
    // Which cards are open, kept across a filter or a crop change. Folded by default (2026-10-15, the owner).
    const opened = new Set();
    // kindFlag: ' data-tl="kind-…"' on the first card of each kind in the answer, '' on the rest.
    const row = (i, kindFlag) => {
        const loc = FH.localOf(i), open = opened.has(i.k), id = 'fhrc-' + String(i.k).replace(/[^a-z0-9_-]/gi, '');
        const rows = aiOf(i, st.crop), nAi = rows.length, nProd = rows.reduce((n, [a]) => n + prodsOf(refOf(i), a, st.crop).length, 0);
        // How its sprays move, seen before the card is opened (2026-10-15, the owner).
        const cls = [...new Set(rows.flatMap(([a]) => (FH.mob(a) || {}).c || []))];
        const sum = nAi ? '<i>' + nAi + ' to spray</i>' + (nProd ? '<i>' + nProd + ' product' + (nProd === 1 ? '' : 's') + '</i>' : '') + FH.mobChips(cls)
            : (i.no ? '<i class="is-warn">Read before you spray</i>' : '');
        // When to act, what to spray (or why not, and a virus's carrier), then the first ways to prevent it (2026-10-19).
        const vec = vectorOf(i, st.crop);
        const prev = (i.prevent || []).length ? '<div class="fd-prev"><span class="fh-spray-h">' + I.sprout + 'Prevent it</span>' + linesOf(i.prevent, 3)
            + (i.prevent.length > 3 || (i.bio || []).length ? '<button type="button" class="fd-all" data-open="' + esc(i.k) + '">More ways to prevent it</button>' : '') + '</div>' : '';
        const body = (nAi || i.no || vec || prev) ? '<div class="fh-rc-spray">' + whenOf(i)
            + (nAi ? '<span class="fh-spray-h" data-tl="what-to-spray">' + I.drop + 'What to spray</span>' + sprayOf(i, st.crop) + avoidOf(i, st.crop) : noSpray(i))
            + vec + '</div>' + prev : '';
        // The small photo shows it large (2026-10-17, the owner); the name folds the card (or opens it, with nothing to fold).
        const act = body ? ' data-fold="' + esc(i.k) + '"' : ' data-open="' + esc(i.k) + '"';
        return '<article class="fh-rc' + (open ? ' is-open' : '') + '" style="--g: ' + i.hue + '" data-k="' + esc(i.k) + '">'
            + '<div class="fh-rc-top">' + FH.rcPhoto(i, I.leaf, act)
            + (body ? '<button type="button" class="fh-rc-head" data-fold="' + esc(i.k) + '" aria-expanded="' + open + '" aria-controls="' + id + '">'
                : '<button type="button" class="fh-rc-head" data-open="' + esc(i.k) + '">')
            + '<span class="fh-rc-t"><b>' + esc(i.name) + '</b><small><em' + (kindFlag || '') + '>' + esc(i.kind) + '</em>' + (loc ? '<span>' + esc(loc) + '</span>' : '') + '</small>'
            + (sum && body ? '<span class="fh-rc-sum">' + sum + '</span>' : '') + '</span></button>'
            + '<span class="fh-rc-acts">'
            + '<button type="button" class="fh-rc-btn fh-rc-info" data-open="' + esc(i.k) + '" aria-label="About ' + esc(i.name) + '" title="About ' + esc(i.name) + '">' + I.info + '</button>'
            + (body ? '<button type="button" class="fh-rc-btn fh-rc-fold-btn" data-fold="' + esc(i.k) + '" aria-expanded="' + open + '" aria-controls="' + id + '" aria-label="' + (open ? 'Fold away' : 'Show') + ' what to do">' + I.chev + '</button>' : '')
            + '</span></div>'
            + (i.hint ? '<p class="fh-rc-sign">' + I.eye + '<span>' + esc(i.hint) + '</span></p>' : '')
            + (body ? '<div class="fh-rc-fold" id="' + id + '"><div>' + body + '</div></div>' : '')
            + '</article>';
    };
    const fold = (k) => {
        const art = card.querySelector('.fh-rc[data-k="' + CSS.escape(k) + '"]');
        if (!art || !art.querySelector('.fh-rc-fold')) return;
        const open = !art.classList.contains('is-open');
        if (open) opened.add(k); else opened.delete(k);
        art.classList.toggle('is-open', open);
        art.querySelectorAll('[data-fold]').forEach((b) => b.setAttribute('aria-expanded', String(open)));
        art.querySelector('.fh-rc-fold-btn')?.setAttribute('aria-label', (open ? 'Fold away' : 'Show') + ' what to do');
        // Opened low on a phone: bring the top of the card into view, gently.
        if (open && !FH.reduce()) setTimeout(() => { const r = art.getBoundingClientRect(); if (r.top < 70) art.scrollIntoView({ behavior: 'smooth', block: 'start' }); }, 60);
    };
    const render = (swap) => {
        const rank = (i) => (i.ranks && i.ranks[st.crop] !== undefined ? i.ranks[st.crop] : 99);
        const forCrop = D.entries.map((i, n) => [i, n]).filter(([i]) => i.crops.includes(st.crop)).sort((a, b) => rank(a[0]) - rank(b[0]) || a[1] - b[1]).map((x) => x[0]);
        // The usual rule: no insecticide on young palay unless a pest is at outbreak level.
        const early = D.pests && st.crop === 'rice'
            ? '<p class="wc-note">Palay younger than 30 to 40 days after transplanting: hold the insecticide unless a pest is at outbreak level. Early sprays kill the spiders and wasps that protect your crop.</p>' : '';
        // How many of this crop's show each answer: one none shows is greyed out in the sheet, and let go.
        const n = Object.fromEntries(Object.keys(D.where).map((k) => [k, forCrop.filter((i) => i.where.includes(k)).length]));
        if (st.where && !n[st.where]) st.where = '';
        greyed = Object.values(n).some((x) => !x);
        whereTag.counts(n, forCrop.length);
        whereTag.set(st.where, swap);
        const hits = st.where ? forCrop.filter((i) => i.where.includes(st.where)) : forCrop;
        const head = st.where ? D.where[st.where] : 'Every ' + D.noun + ' we cover';
        const kinds = new Set();
        const draw = () => {
            kinds.clear();
            card.innerHTML = '<div class="wc-when"><span class="wc-timing">' + esc(D.crops[st.crop]) + '</span><b>' + esc(head) + '</b>'
                + '<small>' + hits.length + ' ' + (hits.length === 1 ? D.noun : D.nouns) + (st.where ? '. Tap one for its photo and every detail.' : '. Pick what you see to narrow it down.') + '</small></div>'
                + '<div class="wc-body">'
                + (hits.length
                    ? early + '<div class="fh-res">' + hits.map((i) => row(i, FH.tlAttr(kindTl(i.kind), kinds))).join('') + '</div>'
                        + '<div><p class="wc-legend">' + legend + ' Switch to a different group next time, so the ' + D.noun + ' does not learn to survive it.</p>'
                        + (hits.some((i) => sold(i, st.crop)) ? FH.rateLine(D.noun) : '')
                        + (hits.some((i) => i.ai && i.ai.length) ? FH.SHOP_NOTE : '') + '</div>'
                    : '<p class="wc-note ok">Nothing in our catalogue for ' + esc(String(D.crops[st.crop]).toLowerCase()) + ' matches that yet. Send a photo to Anee, the smart farm technician, and she will tell you what it most likely is.</p>')
                + '</div>';
        };
        if (swap) FH.swap(card, draw); else draw();
        if (goText) {
            goText.textContent = hits.length ? 'See the ' + hits.length + ' ' + (hits.length === 1 ? D.noun : D.nouns) : 'See the answer';
            if (swap) FH.bump(go);
        }
    };
    card.addEventListener('click', (e) => {
        const b = e.target.closest('[data-open]');
        if (b) { cat.open({ k: b.dataset.open, crop: st.crop }); return; }
        const f = e.target.closest('[data-fold]');
        if (f) fold(f.dataset.fold);
    });
    go?.addEventListener('click', () => card.closest('.wc-out').scrollIntoView({ behavior: FH.reduce() ? 'auto' : 'smooth', block: 'start' }));
    cropTag?.addEventListener('croppick', (e) => { st.crop = e.detail.crop; render(true); });
    render(false);
})();
</script>
@endpush
