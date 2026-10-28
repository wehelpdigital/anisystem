@extends('layouts.public')

{{-- /nutrient-deficiencies (2026-10-16, the owner), built like /pests: the
     twelve nutrients, the crops by shelf (each to its own page, every
     deficiency of that crop a section), the catalogue (one card per crop and
     nutrient, searched by any name and filtered by shelf or nutrient), the
     guides and Anee. The finder itself lives in anee.io's Field helpers, as
     the pest finder does. Behind the members' gate like the other
     catalogues: every card is in the HTML. The entries come from
     App\Support\DeficiencyCatalogue. --}}
@include('public.partials.site-css')
@include('public.site.css')

@php
    $S = \App\Support\SitePages::class;
    $D = \App\Support\DeficiencyCatalogue::class;
    $F = \App\Support\FieldCatalogue::class;
    $live = $pages->keyBy('slug');
    $crops = $D::crops();
    // One card a crop and nutrient: its section on the crop page, its photo (or its nutrient's, said so).
    $items = collect($D::entries())->map(function ($e, $slug) use ($D, $S, $F) {
        $ph = $D::photo($slug, $e['nutrient']);

        // A card shows the crop's own photo, or the nearest crop's with that crop's name on it (2026-10-28, the owner:
        // "nearest to the family of that crop ... but have a disclaimer that it is a different crop").
        $own = $ph && empty($ph['standin']);

        return $e + ['url' => $D::url($e), 'thumb' => $ph && ($own || ($ph['kin'] ?? 0) >= 1) ? $S::img($ph['thumb'] ?? $ph['src']) : null, 'alt' => $own ? ($ph['alt'] ?? $e['name']) : ($ph['note'] ?? $e['name']),
            'standin' => ! empty($ph['standin']), 'near' => $ph && ! $own && ($ph['kin'] ?? 0) >= 1 ? (($ph['shows'] ?? '') !== '' ? $D::plantName((string) $ph['shows']) : 'another crop') : null,
            'cropName' => $F::crops()[$e['crop']] ?? [$e['crop'], $e['crop'], '🌱', 'rice']];
    })->sortBy(fn ($e) => [array_search($e['crop'], array_keys($crops), true), $e['rank']])->values();
    $onShelf = fn ($g) => $items->filter(fn ($e) => $e['group'] === $g);
    $faces = $items->filter(fn ($e) => $e['thumb'] && ! $e['standin'])->unique('thumb')->unique('group')->take(3)->values();
    $cropPages = collect($crops)->keys()->map(fn ($c) => $D::pageKey($c))->unique()->values();
    $nutrientPages = collect($D::NUTRIENTS)->keys()->filter(fn ($n) => $live->has($D::nutrientSlug($n)));
    $guides = $pages->filter(fn ($p) => ! str_ends_with($p->slug, '-nutrient-deficiency') && ! in_array($p->slug, collect($D::NUTRIENTS)->keys()->map(fn ($n) => $D::nutrientSlug($n))->all(), true))->values();
    $shelfTag = fn ($g) => ['Fruits and Plantation Crops' => 'Fruits', 'Sugarcane and Industrial Crops' => 'Industrial', 'Corn and Sorghum' => 'Corn'][$F::SHELVES[$g][0]] ?? $F::SHELVES[$g][0];
    $hg = \App\Support\ContentGate::hubApplies($section);
    $member = auth()->check() && ! $hg;
@endphp

@section('title_full', $meta['metaTitle'] . ' | anee.io')
@section('meta_description', $meta['metaDescription'])

@push('head')
    <link rel="canonical" href="{{ $S::url($section) }}">
    <meta property="og:title" content="{{ $meta['metaTitle'] }}">
    <meta property="og:description" content="{{ $meta['metaDescription'] }}">
    <meta property="og:url" content="{{ $S::url($section) }}">
    @if ($faces->count())<meta property="og:image" content="{{ $faces[0]['thumb'] }}">@endif
    {{-- @@context: a bare @context is a Blade directive. --}}
    <script type="application/ld+json">{!! json_encode([
        '@@context' => 'https://schema.org',
        '@type' => 'CollectionPage',
        'name' => $meta['hubTitle'],
        'description' => $meta['metaDescription'],
        'url' => $S::url($section),
        'isAccessibleForFree' => false,
        'hasPart' => $pages->map(fn ($p) => ['@type' => 'Article', 'headline' => $p->title, 'url' => $S::pageUrl($p)])->values()
            ->push(['@type' => 'WebPageElement', 'isAccessibleForFree' => false, 'cssSelector' => '.hg-grid'])->all(),
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
    @include('public.site.catalogue-css')
    <style>
        .wk-group p.ex { font-size: .86rem; line-height: 1.5; color: #4b5563; }
        .wk-group p.ex b { color: #14210c; }
        .wk-tag.is-kind { left: auto; right: .55rem; }
        @media (max-width: 639.98px) { .wk-tag.is-kind { top: auto; bottom: .4rem; left: .4rem; right: auto; } }
        /* The twelve nutrients: a symbol tile, the name, where it shows first. */
        .nd-nuts { display: grid; gap: .7rem; grid-template-columns: repeat(auto-fill, minmax(min(100%, 15.5rem), 1fr)); }
        .nd-nut { --g: 98; display: flex; gap: .8rem; align-items: flex-start; padding: .9rem; border-radius: 1.1rem; background: #fff; border: 1px solid #e5ebdf; text-decoration: none; color: inherit;
            transition: transform .28s cubic-bezier(.22,1,.36,1), box-shadow .28s cubic-bezier(.22,1,.36,1), border-color .28s cubic-bezier(.22,1,.36,1); }
        a.nd-nut:hover { transform: translateY(-2px); border-color: hsl(var(--g) 40% 72%); box-shadow: 0 16px 32px -26px rgb(20 33 12 / .6); }
        .nd-nut > i { flex: none; display: grid; place-items: center; width: 3rem; height: 3rem; border-radius: .9rem; font-style: normal; font-family: var(--font-heading); font-weight: 800; font-size: 1.15rem;
            color: #fff; background: linear-gradient(140deg, hsl(var(--g) 50% 46%), hsl(var(--g) 55% 32%)); box-shadow: inset 0 -3px 0 rgb(0 0 0 / .14); }
        .nd-nut b { display: block; font-size: .98rem; font-weight: 800; color: #14210c; }
        .nd-nut small { display: block; margin-top: .1rem; font-size: .76rem; font-weight: 700; color: hsl(var(--g) 45% 32%); }
        .nd-nut p { margin-top: .3rem; font-size: .8rem; line-height: 1.45; color: #4b5563; }
        .nd-crops { display: flex; flex-wrap: wrap; gap: .4rem; margin-top: .6rem; }
        .nd-crops a { display: inline-flex; align-items: center; gap: .3rem; padding: .32rem .7rem; border-radius: 999px; font-size: .8rem; font-weight: 700; text-decoration: none; color: #2d5016; background: #f1f7ea; border: 1px solid #e1edd3;
            transition: background-color .28s cubic-bezier(.22,1,.36,1), border-color .28s cubic-bezier(.22,1,.36,1); }
        .nd-crops a:hover { background: #fff; border-color: #a8cc7e; }
        .wk-card .nd-sym { position: absolute; right: .55rem; top: .55rem; display: grid; place-items: center; min-width: 2rem; height: 2rem; padding: 0 .35rem; border-radius: .6rem; font-family: var(--font-heading); font-size: .84rem; font-weight: 800; color: #fff;
            background: hsl(var(--g) 50% 38%); box-shadow: 0 6px 14px -8px rgb(0 0 0 / .6); }
        .wk-card .nd-alt { display: block; margin-top: .25rem; font-size: .7rem; font-style: italic; color: #6b7280; }
        .wk-card .nd-near { position: absolute; left: .55rem; bottom: .55rem; max-width: calc(100% - 1.1rem); padding: .2rem .55rem; border-radius: 999px; font-size: .64rem; font-weight: 800;
            color: #fff; background: rgb(15 23 10 / .62); backdrop-filter: blur(4px); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        @media (prefers-reduced-motion: reduce) { .nd-nut, .nd-crops a { transition: none; } }
    </style>
@endpush

@section('content')
    @if ($hg || $member)@include('public.site.partials.hub-gate')@endif
    <section class="sp-hero">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 pt-10 pb-10 sm:pt-14">
            <nav class="sp-crumbs" aria-label="Breadcrumb">
                <a href="{{ url('/') }}">Home</a>
                <svg fill="none" stroke="currentColor" stroke-width="2.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                <span>{{ $meta['crumb'] }}</span>
            </nav>
            <div class="wk-hero mt-5">
                <div>
                    <span class="sp-chip">{{ $meta['kicker'] }}</span>
                    <h1 class="sp-h1 mt-3">{{ $meta['hubTitle'] }}</h1>
                    <p class="sp-lead mt-4">{{ $meta['intro'] }}</p>
                    <div class="wk-jump mt-6">
                        <a href="#catalogue"><svg fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="6.5"/><path stroke-linecap="round" d="M20 20l-4.2-4.2"/></svg>Find a deficiency</a>
                        <a href="#nutrients"><svg fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 20c0-7 4-13 13-15-1 9-6 14-13 15zm0 0c2-4 5-7 9-10"/></svg>The {{ count($D::NUTRIENTS) }} nutrients</a>
                        @if ($hg)
                            <a href="#spGateCard" class="hg-chip"><svg fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><rect x="5" y="11" width="14" height="10" rx="2"/><path stroke-linecap="round" d="M8 11V8a4 4 0 018 0v3"/></svg>See them all with Libre + Anee</a>
                        @elseif ($member)
                            <a href="?guest=1#spGateCard" class="hg-chip is-open"><svg fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><rect x="5" y="11" width="14" height="10" rx="2"/><path stroke-linecap="round" d="M8 11V8a4 4 0 017.5-2"/></svg>Open for you · <u>See it as a guest</u></a>
                        @endif
                    </div>
                </div>
                @if ($faces->count() === 3)
                    <div class="wk-mosaic" aria-hidden="true">
                        @foreach ($faces as $f)
                            <figure style="--g: {{ $f['hue'] }}">
                                <img src="{{ $f['thumb'] }}" alt="" referrerpolicy="no-referrer" @if ($loop->first) fetchpriority="high" @else loading="lazy" @endif>
                                <figcaption><i></i>{{ $f['name'] }}</figcaption>
                            </figure>
                        @endforeach
                    </div>
                @endif
            </div>
            <div class="mt-8">
                <div class="sp-tabs">
                    <a href="{{ $S::url('crops') }}">Crop guides</a>
                    <a href="{{ $S::url('crop-varieties') }}">Crop varieties</a>
                    <a href="{{ $S::url('how-to-plant') }}">How to plant</a>
                    <a href="{{ $S::url('land-preparation') }}">Land preparation</a>
                    <a href="{{ $S::url('pests') }}">Crop pests</a>
                    <a href="{{ $S::url('diseases') }}">Crop diseases</a>
                    <a href="{{ $S::url('weeds') }}">Weeds and grasses</a>
                    <a href="{{ $S::url('nutrient-deficiencies') }}" class="is-on">Nutrient deficiencies</a>
                    <a href="{{ $S::url('blog') }}">Latest in Agriculture</a>
                </div>
            </div>
        </div>
    </section>

    {{-- The nutrients (twelve, and silicon since 2026-10-19), each to its own page when it has one. --}}
    <section class="bg-white" id="nutrients" style="scroll-margin-top: 5rem">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 py-12 sm:py-16">
            <div class="wk-sec-h">
                <h2>The {{ count($D::NUTRIENTS) }} Nutrients Your Crop Needs</h2>
                <p>Where the change starts tells you a lot. Nitrogen, phosphorus, potassium, magnesium and molybdenum move inside the plant, so the old, lower leaves show it first. The others stay where they are, so the young leaves and the growing tip show it first. Silicon, a helpful element for rice and sugarcane, shows on the whole plant.</p>
            </div>
            <div class="nd-nuts mt-8">
                @foreach ($D::NUTRIENTS as $n => [$nName, $nSym, $nTl, $nMobile, $nHue, $nRole])
                    @php $nPage = $live->get($D::nutrientSlug($n)); $nCount = count($D::forNutrient($n)); @endphp
                    @php $nIn = '<i aria-hidden="true">' . e($nSym) . '</i><span><b>' . e($nName) . ' deficiency</b><small>' . e($nTl) . ' · ' . ($n === 'si' ? 'the whole plant' : ($nMobile ? 'old leaves first' : 'young leaves first')) . '</small><p>' . e($nRole) . ($nCount ? ' In ' . $nCount . ' ' . ($nCount === 1 ? 'crop' : 'crops') . ' here.' : '') . '</p></span>'; @endphp
                    @if ($nPage)
                        <a href="{{ $S::pageUrl($nPage) }}" class="nd-nut" style="--g: {{ $nHue }}">{!! $nIn !!}</a>
                    @else
                        <div class="nd-nut" style="--g: {{ $nHue }}">{!! $nIn !!}</div>
                    @endif
                @endforeach
            </div>
        </div>
    </section>

    {{-- The crops, by shelf, each to its own page. --}}
    <section class="bg-[#fbfcf9] border-t border-[#eef3e8]">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 py-12 sm:py-16">
            <div class="wk-sec-h">
                <h2>Nutrient Deficiencies by Crop</h2>
                <p>Start from the crop in front of you. Each page shows every deficiency that troubles it on Philippine farms, the photo of each, how to be sure, and the fertilizer that fixes it.</p>
            </div>
            <div class="wk-groups mt-8" style="grid-template-columns: repeat(auto-fit, minmax(min(100%, 17rem), 1fr))">
                @foreach ($F::SHELVES as $g => [$gLabel, $gLocal, $gHue, $gIcon])
                    @php $gCrops = collect($crops)->filter(fn ($c) => $c[3] === $g)->keys()->map(fn ($c) => $D::pageKey($c))->unique()->values(); @endphp
                    @continue($gCrops->isEmpty())
                    <div class="wk-group" style="--g: {{ $gHue }}">
                        <div class="wk-group-top">
                            <span class="wk-gico"><svg fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true"><path d="{{ $gIcon }}"/></svg></span>
                            <h3>{{ $gLabel }}<small>{{ $onShelf($g)->count() }} deficiencies · {{ $gLocal }}</small></h3>
                        </div>
                        <div class="nd-crops">
                            @foreach ($gCrops as $pk)
                                @php $pg = $live->get($D::cropSlug($pk)); $cn = $pk === 'corn' ? ['Mais', 'Corn', '🌽'] : ($F::crops()[$pk] ?? [$pk, $pk, '🌱']); @endphp
                                @if ($pg)<a href="{{ $S::pageUrl($pg) }}"><span aria-hidden="true">{{ $cn[2] }}</span>{{ $cn[0] }}</a>@endif
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- The catalogue --}}
    <section class="bg-white" id="catalogue" style="scroll-margin-top: 5rem">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 py-12 sm:py-16">
            <div class="wk-sec-h">
                <h2>Every Deficiency, Crop by Crop</h2>
                <p>{{ $items->count() }} deficiencies of {{ $cropPages->count() }} Philippine crops. Search a crop or a nutrient, like palay, potassium or zinc, or say it in Tagalog, like kakulangan sa nitrogen.</p>
            </div>
            <div class="wk-tools mt-7">
                <label class="wk-search">
                    <span class="sr-only">Search the deficiencies</span>
                    <svg fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="6.5"/><path stroke-linecap="round" d="M20 20l-4.2-4.2"/></svg>
                    <input type="search" id="wkQ" placeholder="Search a crop or a nutrient" autocomplete="off">
                </label>
                <div class="wk-row">
                    <span class="wk-lab" aria-hidden="true">Crop</span>
                    <div class="sp-cats wk-cats" id="wkCats" role="group" aria-label="Crop">
                        <button type="button" class="is-on" data-group="">All <i>{{ $items->count() }}</i></button>
                        @foreach ($F::SHELVES as $g => [$gLabel])
                            @if ($onShelf($g)->count())<button type="button" data-group="{{ $g }}">{{ $shelfTag($g) }} <i>{{ $onShelf($g)->count() }}</i></button>@endif
                        @endforeach
                    </div>
                </div>
                <span class="wk-count" id="wkCount" aria-live="polite"></span>
            </div>
            @if ($hg)<div class="hg" data-hg data-hg-one="deficiency" data-hg-many="deficiencies">@endif
            <div class="wk-grid hg-grid mt-6" id="wkGrid" data-hg-grid data-hg-card=".wk-card">
                @foreach ($items as $e)
                    <a href="{{ $e['url'] }}" class="wk-card" style="--g: {{ $e['hue'] }}" data-group="{{ $e['group'] }}" data-shelves="{{ $e['group'] }}"
                       data-q="{{ \Illuminate\Support\Str::lower($e['name'] . ' ' . ($e['local'] ?? '') . ' ' . $e['nutrientName'] . ' ' . $e['symbol'] . ' ' . $e['cropName'][0] . ' ' . $e['cropName'][1]) }}">
                        <span class="wk-ph">
                            @if ($e['thumb'])
                                <img src="{{ $e['thumb'] }}" alt="{{ $e['alt'] }}" loading="lazy" width="480" height="360" referrerpolicy="no-referrer">
                            @else
                                <span class="none"><svg fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true"><path d="{{ $F::SHELVES[$e['group']][3] }}"/></svg></span>
                            @endif
                            <span class="wk-tag">{{ $e['cropName'][0] }}</span>
                            <span class="nd-sym" aria-hidden="true">{{ $e['symbol'] }}</span>
                            @if ($e['near'])<span class="nd-near" title="{{ $e['alt'] }}">Shown on {{ $e['near'] }}</span>@endif
                        </span>
                        <div class="wk-in">
                            <b>{{ $e['name'] }}</b>
                            @if (! empty($e['local']))<em>{{ $e['local'] }}</em>@endif
                            @if (! empty($e['hint']))<p>{{ $e['hint'] }}</p>@endif
                        </div>
                    </a>
                @endforeach
            </div>
            <button type="button" class="wk-more" id="wkMore">Show all {{ $items->count() }} deficiencies
                <svg fill="none" stroke="currentColor" stroke-width="2.6" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg></button>
            <div class="wk-empty mt-6" id="wkEmpty">
                <p class="wk-else" hidden>None under this crop. <button type="button" id="wkElse"></button></p>
                <p class="wk-none">Nothing by that name in the catalogue yet. Try the nutrient's name, or take a photo and
                <a href="{{ url('/features/ai-agricultural-technician') }}">ask Anee, the smart farm technician</a>.</p>
            </div>
            @if ($hg)
                @include('public.site.partials.content-gate-card', [
                    'here' => '/' . $section,
                    'title' => 'See all ' . $items->count() . ' nutrient deficiencies and the fertilizer for each',
                    'lead' => 'Every deficiency of every crop is open with Libre + Anee: the photo, how to tell it from look alikes, how to be sure, what to apply and the fertilizers sold here that supply it.',
                ])
            </div>
            @endif
        </div>
    </section>

    @if ($guides->count())
        <section class="bg-[#fbfcf9] border-t border-[#eef3e8]" id="guides" style="scroll-margin-top: 5rem">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 py-12 sm:py-16">
                <div class="wk-sec-h">
                    <h2>Guides to Feeding Your Crop</h2>
                    <p>How to read the leaves, how to test your soil, and when a foliar spray helps more than fertilizer on the soil.</p>
                </div>
                <div class="sp-grid wk-guides mt-8">
                    @foreach ($guides as $p)
                        @include('public.site.tile', ['p' => $p])
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    <section class="anee-band">
        <div class="relative max-w-4xl mx-auto px-4 sm:px-6 py-14 sm:py-16 text-center">
            <p class="text-sm font-bold uppercase tracking-wider text-accent-400">Not sure which nutrient it is?</p>
            <h2 class="mt-2 font-heading text-3xl sm:text-4xl font-bold text-white text-balance">Use the Nutrient Deficiency Finder</h2>
            <p class="mt-4 text-[#cdd8c0] leading-relaxed max-w-2xl mx-auto">In anee.io, pick your crop, where the leaves change first and what they look like. The finder shows the likely deficiency and the fertilizers sold here that fix it, and Anee, the smart farm technician, reads a photo of the leaf in Tagalog or English.</p>
            <div class="mt-8 flex flex-col sm:flex-row justify-center gap-3">
                <a href="{{ route('signup') }}" class="btn btn-accent btn-lg">Start free</a>
                <a href="{{ url('/features/ai-agricultural-technician') }}" class="btn btn-lg btn-on-dark">Meet Anee</a>
            </div>
        </div>
    </section>
@endsection

@push('scripts')
<script>
(() => {
    /* The catalogue: a crop shelf chip and the search box narrow the cards
       together; a phone shows the first eight until asked for more. */
    const grid = document.getElementById('wkGrid'), cats = document.getElementById('wkCats'), q = document.getElementById('wkQ');
    const count = document.getElementById('wkCount'), empty = document.getElementById('wkEmpty'), more = document.getElementById('wkMore');
    if (!grid || !cats || !q) return;
    const cards = [...grid.querySelectorAll('.wk-card')];
    const phone = matchMedia('(max-width: 639.98px)'), cap = () => (phone.matches ? 8 : 24);
    const gated = !!grid.closest('[data-hg]');
    let group = '', all = false, booted = false;
    const norm = (s) => s.toLowerCase().replace(/[^a-z0-9ñ ]+/g, ' ').replace(/\s+/g, ' ').trim();
    const rise = (c, i) => { c.classList.remove('is-in'); c.style.animationDelay = Math.min(i, 12) * 25 + 'ms'; void c.offsetWidth; c.classList.add('is-in'); };
    const elseP = empty.querySelector('.wk-else'), elseB = document.getElementById('wkElse');
    const paint = () => {
        const words = norm(q.value).split(' ').filter(Boolean);
        const capped = !gated && !all && !group && !words.length && cards.length > cap();
        let shown = 0;
        cards.forEach((c) => {
            const on = (!group || c.dataset.group === group) && words.every((w) => norm(c.dataset.q).includes(w));
            const was = !c.classList.contains('is-out');
            c.classList.toggle('is-out', !on);
            if (on && !was) rise(c, shown);
            if (on) shown++;
        });
        grid.classList.toggle('is-capped', capped);
        count.textContent = capped ? 'Showing ' + cap() + ' of ' + cards.length : shown === cards.length ? cards.length + ' in all' : 'Showing ' + shown + ' of ' + cards.length;
        empty.classList.toggle('is-on', shown === 0);
        const elsewhere = shown === 0 && group && words.length ? cards.filter((c) => words.every((w) => norm(c.dataset.q).includes(w))).length : 0;
        elseP.hidden = !elsewhere;
        if (elsewhere) elseB.textContent = 'See the ' + elsewhere + ' found in all crops';
        window.aneeHubGate?.(grid);
    };
    const apply = () => (booted && window.aneeFlip ? window.aneeFlip(grid, paint) : paint());
    const setGroup = (g) => { group = g; cats.querySelectorAll('button').forEach((b) => b.classList.toggle('is-on', b.dataset.group === g)); apply(); };
    elseB.addEventListener('click', () => setGroup(''));
    more?.addEventListener('click', () => { all = true; apply(); const from = cap(); cards.slice(from).forEach((c, i) => rise(c, i)); cards[from]?.focus({ preventScroll: true }); });
    phone.addEventListener?.('change', apply);
    cats.addEventListener('click', (e) => { const b = e.target.closest('button[data-group]'); if (b) setGroup(b.dataset.group); });
    q.addEventListener('input', apply);
    const want = new URLSearchParams(location.search).get('group');
    if (want && cats.querySelector('[data-group="' + CSS.escape(want) + '"]')) setGroup(want); else apply();
    booted = true;
})();
</script>
@endpush
