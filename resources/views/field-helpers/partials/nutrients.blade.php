{{-- The in-app Nutrient Deficiency Finder (2026-10-16, the owner): the crop,
     then where it shows first and what it looks like, and back come the
     deficiencies that fit, the likeliest first, each with its sign, what to
     apply and the fertilizers sold here that supply that nutrient. A card
     opens its entry in the whole catalogue (every crop, every nutrient), a
     full screen sheet the strip on top opens too.

     Expects $book (App\Support\FieldBook::deficiencies()) and may take
     $defaultCrop (the newest season's crop). ?crop= picks the crop. --}}
@php
    $F = \App\Support\FieldCatalogue::class;
    $crops = $book['crops'];
    $ndCrop = $F::cropKey(request('crop') ?: ($defaultCrop ?? null));
    $ndCrop = isset($crops[$ndCrop]) ? $ndCrop : array_key_first($crops);
    $faces = collect($book['entries'])->filter(fn ($e) => $e['thumb'] && ! $e['standin'] && in_array($ndCrop, $e['crops'], true))->unique('thumb')->take(3);
    if ($faces->count() < 3) {
        $faces = collect($book['entries'])->filter(fn ($e) => $e['thumb'] && ! $e['standin'])->unique('thumb')->take(3);
    }
    // Each photo's original page and licence, for the credit under it when it is shown large (2026-10-17).
    $ndEntries = collect($book['entries'])->map(function ($e) {
        $ph = $e['photo'] ? \App\Support\DeficiencyCatalogue::photo($e['k'], $e['n']) : null;

        return $ph ? $e + ['pg' => (string) ($ph['page'] ?? ''), 'lic' => (string) ($ph['license'] ?? '')] : $e;
    })->all();
@endphp
@include('field-helpers.partials.kit')
<style>
    /* The nutrient's symbol, on a card and in its entry. */
    .nd-sym { flex: none; display: inline-grid; place-items: center; min-width: 1.9rem; height: 1.9rem; padding: 0 .35rem; border-radius: .6rem; font-family: var(--font-heading); font-size: .82rem; font-weight: 800;
        color: #fff; background: hsl(var(--g) 45% 38%); box-shadow: inset 0 -2px 0 rgb(0 0 0 / .12); }
    .nd-mats { display: flex; flex-wrap: wrap; gap: .35rem; margin-top: .5rem; }
    .nd-mats span { padding: .22rem .6rem; border-radius: 999px; font-size: .76rem; font-weight: 800; color: #2d5016; background: #eef5e6; }
    html.dark .nd-mats span { color: #cfe6b8; background: #22301a; }
    .nd-why { display: flex; gap: .5rem; margin-top: .6rem; padding: .6rem .7rem; border-radius: .85rem; font-size: .8rem; line-height: 1.5; color: #3d5226; background: #f4f9ee; border: 1px solid #e1edd3; }
    html.dark .nd-why { color: #d5e3c5; background: #17220f; border-color: #2b3a1c; }
    .nd-why b { color: inherit; }
    .nd-standin { display: block; margin-top: .3rem; font-size: .72rem; font-style: italic; color: var(--color-gray-500); }
    .nd-match { display: inline-flex; align-items: center; gap: .25rem; padding: .08rem .5rem; border-radius: 999px; font-size: .66rem; font-weight: 800; color: #14532d; background: #dcfce7; }
    html.dark .nd-match { color: #bbf7d0; background: #14351f; }
    .nd-legend { display: flex; flex-wrap: wrap; gap: .4rem; }
    .nd-legend span { display: inline-flex; align-items: center; gap: .35rem; font-size: .74rem; color: var(--color-gray-600); }
    /* Sold here, split by how it goes on (2026-10-19): the soil products, then the sprays. */
    .nd-use { margin: .55rem 0 .3rem; font-size: .7rem; font-weight: 800; letter-spacing: .04em; text-transform: uppercase; color: var(--color-gray-500); }
</style>

<button type="button" class="fh-cat-open" data-fh-cat>
    <span class="fh-faces" aria-hidden="true">@foreach ($faces as $e)<img src="{{ $e['thumb'] }}" alt="" loading="lazy" referrerpolicy="no-referrer">@endforeach</span>
    <span class="fh-cat-t"><b>Open the deficiency catalogue</b><small>{{ count($book['entries']) }} deficiencies of {{ count($crops) }} crops, with photos and what to apply</small></span>
    <span class="fh-cat-go"><span>Open</span><svg fill="none" stroke="currentColor" stroke-width="2.6" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg></span>
</button>

<div class="wc">
    <div class="wc-ask">
        <div class="wc-q">
            <b><i>1</i>Which crop?</b>
            <small>{{ count($crops) }} crops, from palay to durian</small>
            <div id="ndCrop">
                @include('public.site.partials.crop-pick', ['id' => 'ndCropSheet', 'crops' => $crops, 'current' => $ndCrop, 'title' => 'Which crop?', 'noun' => 'deficiencies'])
            </div>
        </div>
        {{-- Steps two and three are tags that open their answers in a sheet
             (FH.optTag, 2026-10-17, the owner): fourteen buttons ran long on a phone. --}}
        <div class="wc-q">
            <b><i>2</i>Where does it show first?</b>
            <small>Old leaves or young leaves is the best clue. Pick one, or leave it</small>
            <div class="fh-otag-box" id="ndWhere"></div>
        </div>
        <div class="wc-q">
            <b><i>3</i>What does it look like?</b>
            <small>Pick the closest, or leave it</small>
            <div class="fh-otag-box" id="ndSign"></div>
        </div>
        <button type="button" class="wc-go" id="ndGo" aria-controls="ndCard"><span>See the deficiencies</span>
            <svg fill="none" stroke="currentColor" stroke-width="2.6" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg></button>
    </div>
    <div class="wc-out" aria-live="polite">
        <div class="wc-card" id="ndCard">
            <noscript><div class="wc-body"><p>Turn on JavaScript to use the finder.</p></div></noscript>
        </div>
        <p class="wc-fine">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.3 3.9L1.8 18a2 2 0 001.7 3h17a2 2 0 001.7-3L13.7 3.9a2 2 0 00-3.4 0z"/></svg>
            <span>Yellow leaves can also come from too much water, dry soil, cold, a disease or a pest. A soil test from your Municipal Agriculture Office or a DA soil laboratory tells you for sure before you spend on fertilizer. Use products registered with the Fertilizer and Pesticide Authority (FPA) and follow the label.</span>
        </p>
    </div>
</div>

@include('field-helpers.partials.catalogue', [
    'id' => 'ndCat', 'title' => 'Deficiency catalogue', 'sub' => count($book['entries']) . ' deficiencies of ' . count($crops) . ' crops',
    'placeholder' => 'Search, like nitrogen, zinc or kakulangan',
    'crops' => $crops, 'nouns' => 'deficiencies', 'allLine' => 'Every deficiency',
    'facetAll' => 'Every nutrient', 'facetTitle' => 'Nitrogen, potassium, zinc and more',
])

<script type="application/json" id="ndData">{!! json_encode([
    'entries' => $ndEntries,
    'crops' => collect($crops)->map(fn ($c) => $c[1]),
    'cropNames' => $book['cropNames'],
    'crop' => $ndCrop,
    'where' => $book['where'],
    'signs' => $book['signs'],
    'facets' => $book['facets'],
    'nutrients' => $book['nutrients'],
    'products' => $book['products'],
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
@push('scripts')
<script>
(() => {
    /* The finder: the crop, then where it shows first and what it looks like.
       Every deficiency of the crop is scored by how well it fits the two
       answers (where counts double: old against young leaves is the best
       clue there is), the crop's commonest first among equals. */
    const el = document.getElementById('ndData'), card = document.getElementById('ndCard');
    if (!el || !card || !window.FH) return;
    const FH = window.FH, esc = FH.esc, I = FH.icon;
    const D = JSON.parse(el.textContent);
    const cropTag = document.querySelector('#ndCrop .cp-tag'), whereBox = document.getElementById('ndWhere'), signBox = document.getElementById('ndSign');
    const go = document.getElementById('ndGo'), goText = go?.querySelector('span');
    const st = { crop: D.crop, where: '', sign: '' };
    // Where it shows and what it looks like: a tag each, the answers in a sheet (FH.optTag, 2026-10-17, the owner).
    // The grey answers in a sheet are those this crop never shows; its note says so.
    const greyed = { where: false, sign: false };
    const grey = (key) => (greyed[key] ? ' The grey ones do not show on ' + String(D.crops[st.crop] || 'this crop').toLowerCase() + '.' : '');
    const whereTag = FH.optTag({ box: whereBox, title: 'Where does it show first?', icon: I.pin, empty: 'Pick where it shows', opts: D.where,
        note: () => 'Old leaves or young leaves is the best clue.' + grey('where'), all: 'Not sure', allSub: 'Leave this out of the answer',
        onPick: (v) => { st.where = v; render(true); } });
    const signTag = FH.optTag({ box: signBox, title: 'What does it look like?', icon: I.eye, empty: 'Pick what it looks like', opts: D.signs,
        note: () => 'Pick the closest.' + grey('sign'), all: 'Not sure', allSub: 'Leave this out of the answer',
        onPick: (v) => { st.sign = v; render(true); } });
    const P = D.products || {};
    Object.assign(FH.prodCards, P.cards || {});
    const NUT = D.nutrients || {};
    const symOf = (i) => '<span class="nd-sym" style="--g: ' + (i.hue || 98) + '" aria-hidden="true">' + esc(i.sym) + '</span>';
    const prods = (i) => (((P.nc || {})[i.n] || {})[i.crops[0]] || []).map((k) => FH.prodCards[k]).filter(Boolean);
    // The products for the soil (granules, powders, salts that also dissolve for a spray), then the sprays for the leaves.
    const strips = (ps) => {
        const soil = ps.filter((p) => p.use !== 'foliar'), spray = ps.filter((p) => p.use === 'foliar');
        if (!soil.length || !spray.length) return FH.prodStrip(ps);
        return '<p class="nd-use">For the soil</p>' + FH.prodStrip(soil) + '<p class="nd-use">Sprays for the leaves</p>' + FH.prodStrip(spray);
    };
    const mats = (i) => (i.materials || []).length ? '<div class="nd-mats">' + i.materials.map((m) => '<span>' + esc(m) + '</span>').join('') + '</div>' : '';
    const moves = (i) => {
        const n = NUT[i.n];
        if (!n) return '';
        return '<p class="nd-why">' + I.leaf + '<span><b>' + esc(n.name) + ' (' + esc(n.sym) + '), ' + esc(n.tl) + ':</b> ' + esc(n.role) + ' '
            + (n.mobile ? 'The plant moves it from the old leaves to the new ones, so the old, lower leaves show it first.' : 'The plant cannot move it out of old leaves, so the young leaves and the growing tip show it first.') + '</span></p>';
    };
    const apply = (i, withProducts) => {
        const ps = prods(i);
        return (i.fix ? '<p>' + esc(i.fix) + '</p>' : '') + mats(i)
            + (withProducts && ps.length ? '<p class="fh-spray-h" style="margin-top:.7rem">' + I.bag + 'Sold here</p>' + strips(ps) : '');
    };

    /* ---- One entry, in the catalogue's second pane ------------------- */
    const text = (icon, h, t) => (t ? FH.sec(icon, h, '<p>' + esc(t) + '</p>') : '');
    // A stand in photo says so, under it and when it is shown large.
    const standin = (i) => (i.standin ? i.note || 'The photo shows ' + String(i.kind).toLowerCase() + ' deficiency on another crop: no free photo of it on this crop was found.' : '');
    const detail = (i) => {
        const where = (i.where || []).map((k) => D.where[k]).filter(Boolean), signs = (i.signs || []).map((k) => D.signs[k]).filter(Boolean);
        const chips = (list) => '<ul class="fhd-chips">' + list.map((l) => '<li>' + esc(l) + '</li>').join('') + '</ul>';
        const ps = prods(i);
        return '<article class="fhd" style="--g: ' + i.hue + '">' + FH.photo(i, { note: standin(i) })
            + (i.standin ? '<p class="nd-standin" style="padding:0 1rem">' + esc(standin(i)) + '</p>' : '')
            + '<div class="fhd-body">'
            + '<header class="fhd-head"><span class="fhd-pill">' + esc(i.sym) + ' ' + esc(i.kind) + '</span><h2>' + esc(i.name) + '</h2>'
            + (i.local ? '<p class="fhd-local"><b>In Tagalog:</b> ' + esc(i.local) + '</p>' : '') + '</header>'
            + (i.hint ? FH.sec(I.eye, 'The sign to look for', '<p>' + esc(i.hint) + '</p>') : '')
            + text(I.eye, 'What it looks like', i.symptoms)
            + (where.length ? FH.sec(I.pin, 'Where it shows first', chips(where)) : '')
            + (signs.length ? FH.sec(I.eye, 'What you see', chips(signs)) : '')
            + text(I.sprout, 'When it shows', i.stage)
            + text(I.warn, 'Why it comes', i.causes)
            + text(I.info, 'Easy to mistake for', i.confuse)
            + text(I.check || I.eye, 'How to be sure', i.confirm)
            + FH.sec(I.drop, 'What to apply', apply(i, false))
            + (ps.length ? FH.sec(I.bag, 'Fertilizers sold here with ' + String(i.kind).toLowerCase(), strips(ps) + '<p class="fh-fine">The brands of Philippine companies come first, then the global makers sold here.Use the rate on the label and your soil test. The Shopee and Lazada links only search those shops for the name.</p>') : '')
            + text(I.sprout, 'How to keep it away', i.prevent)
            + moves(i)
            + FH.sources(i.sources)
            + '</div></article>';
    };
    const facetOf = Object.fromEntries((D.facets || []).map((f) => [f[0], f[2]]));
    const cat = FH.catalogue({
        dlg: document.getElementById('ndCat'), items: D.entries, noun: 'deficiency', nouns: 'deficiencies',
        tag: (i) => i.kind, detail,
        facet: { title: 'Which nutrient?', all: 'Every nutrient', allIcon: '🌿', opts: D.facets, icon: (k) => '<b style="font-size:.8rem">' + esc(facetOf[k] || '') + '</b>' },
    });
    document.querySelectorAll('[data-fh-cat]').forEach((b) => b.addEventListener('click', () => cat.open({ crop: '' })));

    /* ---- The finder's answer ----------------------------------------- */
    const opened = new Set();
    const score = (i) => (st.where && i.where.includes(st.where) ? 2 : 0) + (st.sign && i.signs.includes(st.sign) ? 1 : 0);
    const best = () => (st.where ? 2 : 0) + (st.sign ? 1 : 0);
    const row = (i) => {
        const open = opened.has(i.k), id = 'ndrc-' + String(i.k).replace(/[^a-z0-9_-]/gi, '');
        const ps = prods(i), full = best() && score(i) === best();
        const sum = '<i>' + (i.materials || []).length + ' to apply</i>' + (ps.length ? '<i>' + ps.length + ' sold here</i>' : '');
        // The small photo shows it large (2026-10-17, the owner); the name folds the card.
        return '<article class="fh-rc' + (open ? ' is-open' : '') + '" style="--g: ' + i.hue + '" data-k="' + esc(i.k) + '">'
            + '<div class="fh-rc-top">' + FH.rcPhoto(i, symOf(i), ' data-fold="' + esc(i.k) + '"', standin(i))
            + '<button type="button" class="fh-rc-head" data-fold="' + esc(i.k) + '" aria-expanded="' + open + '" aria-controls="' + id + '">'
            + '<span class="fh-rc-t"><b>' + esc(i.name) + '</b><small><em>' + esc(i.sym + ', ' + i.kind) + '</em>' + (full ? '<span class="nd-match">Fits what you see</span>' : '') + '</small>'
            + '<span class="fh-rc-sum">' + sum + '</span></span></button>'
            + '<span class="fh-rc-acts">'
            + '<button type="button" class="fh-rc-btn fh-rc-info" data-open="' + esc(i.k) + '" aria-label="About ' + esc(i.name) + '" title="About ' + esc(i.name) + '">' + I.info + '</button>'
            + '<button type="button" class="fh-rc-btn fh-rc-fold-btn" data-fold="' + esc(i.k) + '" aria-expanded="' + open + '" aria-controls="' + id + '" aria-label="' + (open ? 'Fold away' : 'Show') + ' what to apply">' + I.chev + '</button>'
            + '</span></div>'
            + (i.hint ? '<p class="fh-rc-sign">' + I.eye + '<span>' + esc(i.hint) + '</span></p>' : '')
            + '<div class="fh-rc-fold" id="' + id + '"><div><div class="fh-rc-spray"><span class="fh-spray-h">' + I.drop + 'What to apply</span>' + apply(i, true)
            + (i.confirm ? '<p class="fh-fine" style="margin-top:.6rem"><b>How to be sure:</b> ' + esc(i.confirm) + '</p>' : '') + '</div></div></div>'
            + '</article>';
    };
    const fold = (k) => {
        const art = card.querySelector('.fh-rc[data-k="' + CSS.escape(k) + '"]');
        if (!art) return;
        const open = !art.classList.contains('is-open');
        if (open) opened.add(k); else opened.delete(k);
        art.classList.toggle('is-open', open);
        art.querySelectorAll('[data-fold]').forEach((b) => b.setAttribute('aria-expanded', String(open)));
        art.querySelector('.fh-rc-fold-btn')?.setAttribute('aria-label', (open ? 'Fold away' : 'Show') + ' what to apply');
        if (open && !FH.reduce()) setTimeout(() => { const r = art.getBoundingClientRect(); if (r.top < 70) art.scrollIntoView({ behavior: 'smooth', block: 'start' }); }, 60);
    };
    const render = (swap) => {
        const rank = (i) => (i.ranks && i.ranks[st.crop] !== undefined ? i.ranks[st.crop] : 99);
        const forCrop = D.entries.filter((i) => i.crops.includes(st.crop));
        // How many of this crop's deficiencies show each answer: one none shows is greyed out in its sheet, and let go.
        const mark = (tag, book, key, list) => {
            const n = Object.fromEntries(Object.keys(book).map((k) => [k, forCrop.filter((i) => i[list].includes(k)).length]));
            if (st[key] && !n[st[key]]) st[key] = '';
            greyed[key] = Object.values(n).some((x) => !x);
            tag.counts(n, forCrop.length);
            tag.set(st[key], swap);
        };
        mark(whereTag, D.where, 'where', 'where');
        mark(signTag, D.signs, 'sign', 'signs');
        const asked = best();
        let hits = forCrop.slice().sort((a, b) => score(b) - score(a) || rank(a) - rank(b));
        if (asked) hits = hits.filter((i) => score(i) > 0);
        const head = asked ? [st.where ? D.where[st.where] : '', st.sign ? D.signs[st.sign] : ''].filter(Boolean).join(', ') : 'Every deficiency we cover';
        const draw = () => {
            card.innerHTML = '<div class="wc-when"><span class="wc-timing">' + esc(D.crops[st.crop]) + '</span><b>' + esc(head) + '</b>'
                + '<small>' + hits.length + ' ' + (hits.length === 1 ? 'deficiency' : 'deficiencies') + (asked ? ', the best fit first. Open one for its photo and what to apply.' : '. Say where it shows first to narrow it down.') + '</small></div>'
                + '<div class="wc-body">'
                + (hits.length
                    ? '<div class="fh-res">' + hits.map(row).join('') + '</div>'
                    : '<p class="wc-note ok">Nothing in our catalogue for ' + esc(String(D.crops[st.crop]).toLowerCase()) + ' fits that. It may be water, weather, a pest or a disease. Send a photo to Anee, the smart farm technician, and she will tell you what it most likely is.</p>')
                + '</div>';
        };
        if (swap) FH.swap(card, draw); else draw();
        if (goText) {
            goText.textContent = hits.length ? 'See the ' + hits.length + ' ' + (hits.length === 1 ? 'deficiency' : 'deficiencies') : 'See the answer';
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
