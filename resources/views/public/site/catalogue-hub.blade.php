@extends('layouts.public')

{{-- /pests and /diseases (2026-10-07), built like /weeds: the crop shelves,
     the catalogue (one card per profile page, searched by any name and
     filtered by shelf), the guides and Anee. The finder ("What is attacking
     my crop?") left the public site on 2026-10-14 (the owner): it lives in
     anee.io's Field helpers. The words around it come from
     App\Support\ProblemCatalogue::HUB, the card facts from the field
     catalogue (App\Support\FieldCatalogue), every crop since 2026-10-08. --}}
@include('public.partials.site-css')
@include('public.site.css')

@php
    $S = \App\Support\SitePages::class;
    $P = \App\Support\ProblemCatalogue::class;
    $F = \App\Support\FieldCatalogue::class;
    // Every entry of every crop: its profile page when one is written, its
    // fact sheet when not (2026-10-08), and the shelves whose crops it attacks.
    $items = $P::items($section, $pages);
    $guides = $pages->filter(fn ($p) => ! $P::get($section, $p->slug))->values();
    $onShelf = fn ($g) => $items->filter(fn ($w) => in_array($g, $w['shelves'], true));
    $count = fn ($g) => $onShelf($g)->count();
    $faces = collect(array_keys($F::SHELVES))->map(fn ($g) => $items->where('group', $g)->first(fn ($w) => $w['thumb']))->filter()->take(3)->values();
    $finder = $P::finderFacts($section, $items);
    $isPests = $finder['isPests'];
    $words = $finder['words'];
    $kindHue = $P::KINDS;
    $shelfTag = fn ($g) => ['Fruits and Plantation Crops' => 'Fruits', 'Sugarcane and Industrial Crops' => 'Industrial', 'Corn and Sorghum' => 'Corn'][$F::SHELVES[$g][0]] ?? $F::SHELVES[$g][0];
    // The members' gate (2026-10-14): a guest sees the catalogue's first rows.
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
    {{-- @@context: a bare @context is a Blade directive, and printed PHP into this JSON. --}}
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
        /* Two tags across a phone card ran into each other ("Vegetables"
           over "Water mold"): there the kind sits on the photo's lower edge. */
        @media (max-width: 639.98px) { .wk-tag.is-kind { top: auto; bottom: .4rem; left: .4rem; right: auto; } }
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
                        <a href="#catalogue"><svg fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="6.5"/><path stroke-linecap="round" d="M20 20l-4.2-4.2"/></svg>Find a {{ $words['noun'] }}</a>
                        @if ($hg)
                            <a href="#spGateCard" class="hg-chip"><svg fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><rect x="5" y="11" width="14" height="10" rx="2"/><path stroke-linecap="round" d="M8 11V8a4 4 0 018 0v3"/></svg>Register for free to see them all</a>
                        @elseif ($member)
                            <a href="?guest=1#spGateCard" class="hg-chip is-open"><svg fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><rect x="5" y="11" width="14" height="10" rx="2"/><path stroke-linecap="round" d="M8 11V8a4 4 0 017.5-2"/></svg>Open for you · <u>See it as a guest</u></a>
                        @endif
                        @if ($guides->count())<a href="#guides"><svg fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.5C10.5 5 8 4.5 4 4.5v13c4 0 6.5.5 8 2m0-13c1.5-1.5 4-2 8-2v13c-4 0-6.5.5-8 2m0-13v13"/></svg>Read the guides</a>@endif
                    </div>
                </div>
                @if ($faces->count() === 3)
                    <div class="wk-mosaic" aria-hidden="true">
                        @foreach ($faces as $f)
                            <figure style="--g: {{ $F::SHELVES[$f['group']][2] }}">
                                <img src="{{ $f['thumb'] }}" alt="" referrerpolicy="no-referrer" @if ($loop->first) fetchpriority="high" @else loading="lazy" @endif>
                                <figcaption><i></i>{{ $F::SHELVES[$f['group']][0] }}: {{ $f['name'] }}</figcaption>
                            </figure>
                        @endforeach
                    </div>
                @endif
            </div>
            {{-- The sections, one row under the whole hero (it wrapped in the text column). --}}
            <div class="mt-8">
                <div class="sp-tabs">
                    <a href="{{ $S::url('crops') }}">Crop guides</a>
                    <a href="{{ $S::url('crop-varieties') }}">Crop varieties</a>
                    <a href="{{ $S::url('land-preparation') }}">Land preparation</a>
                    <a href="{{ $S::url('pests') }}" class="{{ $section === 'pests' ? 'is-on' : '' }}">Crop pests</a>
                    <a href="{{ $S::url('diseases') }}" class="{{ $section === 'diseases' ? 'is-on' : '' }}">Crop diseases</a>
                    <a href="{{ $S::url('weeds') }}">Weeds and grasses</a>
                    <a href="{{ $S::url('nutrient-deficiencies') }}">Nutrient deficiencies</a>
                    <a href="{{ $S::url('blog') }}">Latest in Agriculture</a>
                </div>
            </div>
        </div>
    </section>

    {{-- The shelves: one card per crop group. --}}
    <section class="bg-white">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 py-12 sm:py-16">
            <div class="wk-sec-h">
                <h2>{{ ucfirst($words['nouns']) }} by Crop</h2>
                <p>Start from the crop in front of you. Each shelf holds the {{ $words['nouns'] }} that matter most on Philippine farms, from palay to durian, with their local names and what to spray.</p>
            </div>
            <div class="wk-groups mt-8" style="grid-template-columns: repeat(auto-fit, minmax(min(100%, 15rem), 1fr))">
                @foreach ($F::SHELVES as $g => [$gLabel, $gLocal, $gHue, $gIcon])
                    @php($inG = $onShelf($g)->values())
                    @continue($inG->isEmpty())
                    <div class="wk-group" style="--g: {{ $gHue }}">
                        <div class="wk-group-top">
                            <span class="wk-gico"><svg fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true"><path d="{{ $gIcon }}"/></svg></span>
                            <h3>{{ $gLabel }}<small>{{ $inG->count() }} {{ $words['nouns'] }} · {{ $gLocal }}</small></h3>
                        </div>
                        <p class="ex">Like <b>{{ ($inG->where('group', $g)->count() ? $inG->where('group', $g) : $inG)->take(3)->pluck('name')->map(fn ($n) => preg_replace('/\s*\(.*\)$/', '', $n))->join(', ', ' and ') }}</b>.</p>
                        <button type="button" data-show-group="{{ $g }}">See all {{ $inG->count() }}
                            <svg fill="none" stroke="currentColor" stroke-width="2.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg></button>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- The catalogue --}}
    <section class="bg-[#fbfcf9] border-t border-[#eef3e8]" id="catalogue" style="scroll-margin-top: 5rem">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 py-12 sm:py-16">
            <div class="wk-sec-h">
                <h2>{{ $words['catalogue'] }}</h2>
                <p>{{ $items->count() }} {{ $words['nouns'] }} of {{ count($finder['crops']) }} Philippine crops, from palay, mais and gulay to the root crops, sugarcane and the fruit trees. {{ $words['catalogueLead'] }}</p>
            </div>
            <div class="wk-tools mt-7">
                <label class="wk-search">
                    <span class="sr-only">Search the {{ $words['nouns'] }}</span>
                    <svg fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="6.5"/><path stroke-linecap="round" d="M20 20l-4.2-4.2"/></svg>
                    <input type="search" id="wkQ" placeholder="Search a name" autocomplete="off">
                </label>
                <div class="wk-row">
                    <span class="wk-lab" aria-hidden="true">Crop</span>
                    <div class="sp-cats wk-cats" id="wkCats" role="group" aria-label="Crop">
                        <button type="button" class="is-on" data-group="">All <i>{{ $items->count() }}</i></button>
                        @foreach ($F::SHELVES as $g => [$gLabel])
                            @if ($count($g))<button type="button" data-group="{{ $g }}">{{ $shelfTag($g) }} <i>{{ $count($g) }}</i></button>@endif
                        @endforeach
                    </div>
                </div>
                <span class="wk-count" id="wkCount" aria-live="polite"></span>
            </div>
            @if ($hg)<div class="hg" data-hg data-hg-one="{{ $words['noun'] }}" data-hg-many="{{ $words['nouns'] }}">@endif
            <div class="wk-grid hg-grid mt-6" id="wkGrid" data-hg-grid data-hg-card=".wk-card">
                @foreach ($items as $w)
                    @php($gh = $F::SHELVES[$w['group']][2])
                    <a href="{{ $w['url'] }}" class="wk-card" style="--g: {{ $gh }}" data-group="{{ $w['group'] }}" data-shelves="{{ implode(' ', $w['shelves']) }}"
                       data-q="{{ \Illuminate\Support\Str::lower($w['name'] . ' ' . $w['sci'] . ' ' . $w['local'] . ' ' . $w['kind'] . ' ' . collect($w['crops'])->map(fn ($c) => implode(' ', array_slice($F::crops()[$c] ?? [], 0, 2)))->join(' ')) }}">
                        <span class="wk-ph">
                            @if ($w['thumb'])
                                <img src="{{ $w['thumb'] }}" alt="{{ $w['alt'] }}" loading="lazy" width="480" height="360" referrerpolicy="no-referrer">
                            @else
                                <span class="none"><svg fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true"><path d="{{ $F::SHELVES[$w['group']][3] }}"/></svg></span>
                            @endif
                            <span class="wk-tag">{{ $shelfTag($w['group']) }}</span>
                            <span class="wk-tag is-kind" style="--g: {{ $kindHue[$w['kind']] ?? 98 }}">{{ $w['kind'] }}</span>
                        </span>
                        <div class="wk-in">
                            <b>{{ $w['name'] }}</b>
                            <em>{{ $w['sci'] }}</em>
                            {{-- A few "local" fields are a sentence about the names ("Maya is the name for..."): those cards show the sign instead. --}}
                            @if ($w['local'] !== '' && ! preg_match('/\b(is|are|call|called|means)\b/i', $w['local']))<p><span>Local names:</span> {{ $w['local'] }}</p>@elseif ($w['hint'] !== '')<p>{{ $w['hint'] }}</p>@endif
                        </div>
                    </a>
                @endforeach
            </div>
            <button type="button" class="wk-more" id="wkMore">Show all {{ $items->count() }} {{ $words['nouns'] }}
                <svg fill="none" stroke="currentColor" stroke-width="2.6" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg></button>
            <div class="wk-empty mt-6" id="wkEmpty">
                {{-- The name is in the catalogue, under another crop: say so, one tap away. --}}
                <p class="wk-else" hidden>None under this crop. <button type="button" id="wkElse"></button></p>
                <p class="wk-none">Nothing by that name in the catalogue yet. Try its scientific name, or take a photo and
                <a href="{{ url('/features/ai-agricultural-technician') }}">ask Anee, the smart farm technician</a>.</p>
            </div>
            @if ($hg)
                @include('public.site.partials.content-gate-card', [
                    'here' => '/' . $section,
                    'title' => 'See all ' . $items->count() . ' crop ' . $words['nouns'] . ($isPests ? ' and how to stop each one' : ' and how to manage each one'),
                    'lead' => $isPests
                        ? 'Register for free to open every pest: how to be sure which one you have, the damage it does, when it pays to act and what to spray.'
                        : 'Register for free to open every disease: how to tell it from look alikes, what makes it spread, and what to spray and when.',
                ])
            </div>
            @endif
        </div>
    </section>


    @if ($guides->count())
        <section class="bg-white" id="guides" style="scroll-margin-top: 5rem">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 py-12 sm:py-16">
                <div class="wk-sec-h">
                    <h2>{{ $words['guides'] }}</h2>
                    <p>{{ $words['guidesLead'] }}</p>
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
            <p class="text-sm font-bold uppercase tracking-wider text-accent-400">{{ $words['bandKick'] }}</p>
            <h2 class="mt-2 font-heading text-3xl sm:text-4xl font-bold text-white text-balance">{{ $words['bandTitle'] }}</h2>
            <p class="mt-4 text-[#cdd8c0] leading-relaxed max-w-2xl mx-auto">{{ $words['bandText'] }}</p>
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
    /* The catalogue: a crop chip and the search box narrow the cards
       together. A chip shows every card that attacks that crop, its own
       shelf first. A phone shows the first eight until asked for more. */
    const grid = document.getElementById('wkGrid'), cats = document.getElementById('wkCats'), q = document.getElementById('wkQ');
    const count = document.getElementById('wkCount'), empty = document.getElementById('wkEmpty'), more = document.getElementById('wkMore');
    if (!grid || !cats || !q) return;
    const cards = [...grid.querySelectorAll('.wk-card')];
    const phone = matchMedia('(max-width: 639.98px)'), cap = () => (phone.matches ? 8 : 24);
    // Behind the members' gate, the gate decides what shows (partials/hub-gate).
    const gated = !!grid.closest('[data-hg]');
    let group = '', all = false;
    const norm = (s) => s.toLowerCase().replace(/[^a-z0-9ñ ]+/g, ' ').replace(/\s+/g, ' ').trim();
    const rise = (c, i) => { c.classList.remove('is-in'); c.style.animationDelay = Math.min(i, 12) * 25 + 'ms'; void c.offsetWidth; c.classList.add('is-in'); };
    let booted = false;
    const paint = () => {
        const words = norm(q.value).split(' ').filter(Boolean);
        const capped = !gated && !all && !group && !words.length && cards.length > cap();
        let shown = 0;
        cards.forEach((c) => {
            const on = (!group || (c.dataset.shelves || c.dataset.group).split(' ').includes(group)) && words.every((w) => norm(c.dataset.q).includes(w));
            const was = !c.classList.contains('is-out');
            c.classList.toggle('is-out', !on);
            c.classList.toggle('is-also', !!group && c.dataset.group !== group);
            if (on && !was) rise(c, shown);
            if (on) shown++;
        });
        grid.classList.toggle('is-capped', capped);
        count.textContent = capped ? 'Showing ' + cap() + ' of ' + cards.length
            : shown === cards.length ? cards.length + ' in all' : 'Showing ' + shown + ' of ' + cards.length;
        empty.classList.toggle('is-on', shown === 0);
        // Nothing under this crop, but the name is in the catalogue: offer them.
        const elsewhere = shown === 0 && group && words.length ? cards.filter((c) => words.every((w) => norm(c.dataset.q).includes(w))).length : 0;
        elseP.hidden = !elsewhere;
        if (elsewhere) elseB.textContent = 'See the ' + elsewhere + ' found in all crops';
        window.aneeHubGate?.(grid);
    };
    // After the first paint every change moves the cards (partials/hub-gate).
    const apply = () => (booted && window.aneeFlip ? window.aneeFlip(grid, paint) : paint());
    const elseP = empty.querySelector('.wk-else'), elseB = document.getElementById('wkElse');
    elseB.addEventListener('click', () => setGroup(''));
    more?.addEventListener('click', () => {
        all = true; apply();
        const from = cap();
        cards.slice(from).forEach((c, i) => rise(c, i));
        cards[from]?.focus({ preventScroll: true });
    });
    phone.addEventListener?.('change', apply);
    const setGroup = (g) => { group = g; cats.querySelectorAll('button').forEach((b) => b.classList.toggle('is-on', b.dataset.group === g)); apply(); };
    cats.addEventListener('click', (e) => { const b = e.target.closest('button[data-group]'); if (b) setGroup(b.dataset.group); });
    q.addEventListener('input', apply);
    document.querySelectorAll('[data-show-group]').forEach((b) => b.addEventListener('click', () => {
        setGroup(b.dataset.showGroup);
        document.getElementById('catalogue').scrollIntoView({ behavior: matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth' });
    }));
    const want = new URLSearchParams(location.search).get('group');
    if (want && cats.querySelector('[data-group="' + CSS.escape(want) + '"]')) setGroup(want); else apply();
    booted = true;
})();
</script>
@endpush
