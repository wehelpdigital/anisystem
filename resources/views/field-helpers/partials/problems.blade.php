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
@endphp
@include('field-helpers.partials.kit')

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
        <div class="wc-q">
            <b><i>2</i>{{ $w['askWhere'] }}</b>
            <small>Pick one, or leave it to see all</small>
            <div class="wc-opts" role="radiogroup" aria-label="{{ $w['askWhere'] }}" id="fhWhere">
                @foreach ($book['askOpts'] as $k => $label)
                    <button type="button" role="radio" aria-checked="false" data-where="{{ $k }}">{{ $label }}</button>
                @endforeach
            </div>
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
    'entries' => $book['entries'],
    'crops' => collect($crops)->map(fn ($c) => $c[1]),
    'cropNames' => $book['cropNames'],
    'crop' => $fdCrop,
    'where' => $book['askOpts'],
    'parts' => $book['parts'],
    'signs' => $book['signs'],
    'facets' => $book['facets'],
    'noun' => $w['noun'], 'nouns' => $w['nouns'], 'pests' => $book['isPests'],
    // Only the products this page can show (FieldBook::problemProducts).
    'products' => $book['products'],
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
    const KIND_ICON = Object.fromEntries(D.facets.map((f) => [f[0], f[2]]));
    const P = D.products || {};
    Object.assign(FH.prodCards, P.cards || {});
    // The products sold here for one ingredient on one crop ("*": any crop), and those a label names for one entry.
    const prodsOf = (name, crop) => (((P.ip || {})[FH.norm(name)] || {})[crop || '*'] || []).map((k) => FH.prodCards[k]).filter(Boolean);
    const labelled = (slug, crop) => (((P.pp || {})[slug] || {})[crop || '*'] || []).map((k) => FH.prodCards[k]).filter(Boolean);
    const sold = (i, crop) => (i.ai || []).some(([n]) => prodsOf(n, crop).length);

    // What to spray against one pest or disease, or why no spray helps.
    const sprayOf = (i, crop) => (i.ai && i.ai.length) ? FH.ingList(i.ai.map(([n, g]) => FH.ing(n, g, '', prodsOf(n, crop)))) : '';
    const noSpray = (i) => i.no ? '<p class="fh-no">' + I.warn + '<span><b>Before you spray</b>' + esc(i.no) + '</span></p>' : '';
    const legend = D.pests ? 'The IRAC group tells how an insecticide kills.' : 'The FRAC group tells how a fungicide works.';

    /* ---- One entry, in the catalogue's second pane ------------------- */
    const labels = (keys, book) => keys.map((k) => book[k]).filter(Boolean);
    const chips = (list) => '<ul class="fhd-chips">' + list.map((l) => '<li>' + esc(l) + '</li>').join('') + '</ul>';
    const detail = (i, ctx) => {
        const parts = labels(i.parts || [], D.parts), signs = labels(i.signs || [], D.signs);
        // The crop the catalogue is on, else any crop (each product says what its label names).
        const crop = ctx && ctx.crop && i.crops.includes(ctx.crop) ? ctx.crop : '';
        const spray = sprayOf(i, crop), named = labelled(i.k, crop);
        return '<article class="fhd" style="--g: ' + i.hue + '">' + FH.photo(i)
            + '<div class="fhd-body">'
            + '<header class="fhd-head"><span class="fhd-pill">' + esc(KIND_ICON[i.kind] || '') + ' ' + esc(i.kind) + '</span><h2>' + esc(i.name) + '</h2>'
            + (i.sci ? '<p class="fhd-sci">' + esc(i.sci) + '</p>' : '')
            + (i.local ? '<p class="fhd-local"><b>Local names:</b> ' + esc(i.local) + '</p>' : '') + '</header>'
            + (i.hint ? FH.sec(I.eye, 'Signs to look for', '<p>' + esc(i.hint) + '</p>') : '')
            + (signs.length ? FH.sec(I.eye, 'What you see', chips(signs)) : '')
            + (parts.length ? FH.sec(I.pin, D.pests ? 'Where the damage shows' : 'Where it shows', chips(parts)) : '')
            + FH.sec(I.sprout, (D.pests ? 'Crops it attacks' : 'Crops it infects') + ' (' + i.crops.length + ')', FH.cropChips(i.crops, D.cropNames, st.crop))
            + (spray ? FH.sec(I.drop, 'What to spray', spray + '<p class="fh-fine">' + legend + ' Switch to a different group next time, so the ' + D.noun + ' does not learn to survive it.</p>'
                + (sold(i, crop) ? FH.rateLine(D.noun) : '') + FH.SHOP_NOTE) : '')
            + (i.no ? '<section>' + noSpray(i) + '</section>' : '')
            + (named.length ? FH.sec(I.bag, 'Products labelled for this' + (crop && D.cropNames[crop] ? ' on ' + D.cropNames[crop][0].toLowerCase() : ''), FH.prodStrip(named)
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
    const row = (i) => {
        const loc = FH.localOf(i);
        return '<article class="fh-rc" style="--g: ' + i.hue + '" data-k="' + esc(i.k) + '">'
            + '<button type="button" class="fh-rc-head" data-open="' + esc(i.k) + '">'
            + (i.thumb ? '<img src="' + esc(i.thumb) + '" alt="" loading="lazy" referrerpolicy="no-referrer">' : '<span class="none">' + I.leaf + '</span>')
            + '<span class="fh-rc-t"><b>' + esc(i.name) + '</b><small><em>' + esc(i.kind) + '</em>' + (loc ? '<span>' + esc(loc) + '</span>' : '') + '</small></span>'
            + '<span class="fh-rc-go" aria-hidden="true">' + I.arrow + '</span><span class="sr-only">Open the details</span></button>'
            + (i.hint ? '<p class="fh-rc-sign">' + I.eye + '<span>' + esc(i.hint) + '</span></p>' : '')
            + (i.ai && i.ai.length ? '<div class="fh-rc-spray"><span class="fh-spray-h">' + I.drop + 'What to spray</span>' + sprayOf(i, st.crop) + '</div>'
                : (i.no ? '<div class="fh-rc-spray">' + noSpray(i) + '</div>' : ''))
            + '</article>';
    };
    const render = (swap) => {
        const rank = (i) => (i.ranks && i.ranks[st.crop] !== undefined ? i.ranks[st.crop] : 99);
        const forCrop = D.entries.map((i, n) => [i, n]).filter(([i]) => i.crops.includes(st.crop)).sort((a, b) => rank(a[0]) - rank(b[0]) || a[1] - b[1]).map((x) => x[0]);
        // IRRI and PhilRice: no insecticide on young palay unless a pest is at outbreak level.
        const early = D.pests && st.crop === 'rice'
            ? '<p class="wc-note">Palay younger than 30 to 40 days after transplanting: hold the insecticide unless a pest is at outbreak level. Early sprays kill the spiders and wasps that protect your crop.</p>' : '';
        whereBox.querySelectorAll('button').forEach((b) => {
            const has = forCrop.some((i) => i.where.includes(b.dataset.where));
            b.disabled = !has;
            if (!has && st.where === b.dataset.where) st.where = '';
            b.setAttribute('aria-checked', String(b.dataset.where === st.where));
        });
        const hits = st.where ? forCrop.filter((i) => i.where.includes(st.where)) : forCrop;
        const head = st.where ? D.where[st.where] : 'Every ' + D.noun + ' we cover';
        const draw = () => {
            card.innerHTML = '<div class="wc-when"><span class="wc-timing">' + esc(D.crops[st.crop]) + '</span><b>' + esc(head) + '</b>'
                + '<small>' + hits.length + ' ' + (hits.length === 1 ? D.noun : D.nouns) + (st.where ? '. Tap one for its photo and every detail.' : '. Pick what you see to narrow it down.') + '</small></div>'
                + '<div class="wc-body">'
                + (hits.length
                    ? early + '<div class="fh-res">' + hits.map(row).join('') + '</div>'
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
    card.addEventListener('click', (e) => { const b = e.target.closest('[data-open]'); if (b) cat.open({ k: b.dataset.open, crop: st.crop }); });
    go?.addEventListener('click', () => card.closest('.wc-out').scrollIntoView({ behavior: FH.reduce() ? 'auto' : 'smooth', block: 'start' }));
    cropTag?.addEventListener('croppick', (e) => { st.crop = e.detail.crop; render(true); });
    whereBox.addEventListener('click', (e) => {
        const b = e.target.closest('button[data-where]');
        if (!b || b.disabled) return;
        st.where = st.where === b.dataset.where ? '' : b.dataset.where;
        render(true);
    });
    render(false);
})();
</script>
@endpush
