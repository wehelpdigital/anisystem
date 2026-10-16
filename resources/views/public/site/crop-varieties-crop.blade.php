@extends('layouts.public')

{{-- /crop-varieties/{crop} (2026-10-13): one crop's variety finder, every
     variety of it on the NSIC registry plus the ones farmers buy that were
     never registered, each card a link to its own page. All the cards are
     in the page (so search engines read every link); the script only hides
     and orders them. See App\Support\CropVarieties. --}}
@include('public.partials.site-css')
@include('public.site.css')

@php
    $S = \App\Support\SitePages::class;
    $CV = \App\Support\CropVarieties::class;
    $all = collect($varieties);
    $name = $crop['name'];
    $lname = strtolower($name);
    $local = $crop['local'] ?? null;
    $url = url('/crop-varieties/' . $crop['slug']);
    $fmt = fn ($x, $d = 1) => $d > 0 ? rtrim(rtrim(number_format((float) $x, $d, '.', ''), '0'), '.') : number_format((float) $x, 0, '.', '');
    [$heroSrc, $heroAlt] = $CV::hero($crop['key']);
    $years = $all->pluck('year')->filter();
    $registered = $all->filter(fn ($v) => ! empty($v['reg']))->count();
    $sold = $all->count() - $registered;
    $mats = $all->filter(fn ($v) => ! empty($v['maturity']));
    $unit = $mats->pluck('maturity.unit')->countBy()->sortDesc()->keys()->first() ?? 'days';
    $mats = $mats->filter(fn ($v) => $v['maturity']['unit'] === $unit)->pluck('maturity.value');
    $ys = $all->filter(fn ($v) => isset($v['yield']['value']) && ($v['yield']['unit'] ?? '') === 't/ha')->pluck('yield.value');
    $hybrids = $all->filter(fn ($v) => str_contains(strtolower((string) ($v['kind'] ?? '')), 'hybrid'))->count();
    $title = $name . ' Varieties in the Philippines';
    // The members' gate (2026-10-14): a guest sees the finder's first rows.
    $hg = \App\Support\ContentGate::finderApplies('crop-varieties');
    $member = auth()->check() && ! $hg;
    $metaTitle = collect([$title . ': Every Registered Variety', $title . ': Yield and Maturity', $title])->first(fn ($t) => mb_strlen($t) <= 60);
    $desc = collect([
        'Every ' . $lname . ' variety on the NSIC registry' . ($sold ? ', plus the seed farmers buy most' : '') . ($local ? ' (' . $local . ')' : '') . ': days to harvest, yield, ratings and where each grows best.',
        'Every ' . $lname . ' variety on the NSIC registry' . ($sold ? ', plus the seed farmers buy most' : '') . ': days to harvest, yield, ratings and where each grows best.',
        'Every ' . $lname . ' variety on the NSIC registry: days to harvest, yield and where each grows best.',
    ])->first(fn ($d) => mb_strlen($d) <= 156);
    $intro = trim(($crop['hub'] ?? '') . ' ' . $all->count() . ' varieties' . ($sold ? ', ' . $registered . ' of them on the national registry and ' . $sold . ' sold by seed companies or grown by farmers' : ' from the national registry') . ', each with a page of its own.');
    $guides = $crop['guides'] ?? [];
    $faq = [];
    if ($mats->count() >= 3) {
        $faq[] = ['How many ' . $unit . ' does ' . $lname . ' take to harvest?', 'Across the ' . $mats->count() . ' ' . $lname . ' varieties with a count, the average is ' . $fmt($mats->avg()) . ' ' . $unit . ', from ' . $fmt($mats->min(), 0) . ' for the fastest to ' . $fmt($mats->max(), 0) . ' for the slowest. Use the ' . ($unit === 'months' ? 'months' : 'days') . ' filter above to see the short ones.'];
    }
    if ($ys->count() >= 3) {
        $faq[] = ['What is the average yield of ' . $lname . ' varieties?', 'The ' . $ys->count() . ' varieties with a trial yield average ' . $fmt($ys->avg(), 2) . ' tonnes per hectare, and the best reach ' . $fmt($ys->max(), 2) . '. Trial plots get good care, so expect less on an average farm.'];
    }
    $faq[] = ['Which ' . $lname . ' variety is the best?', 'The best one is the variety bred for your area, your season and your buyer. Start with the filters above, then ask your municipal agriculturist which ones have done well in your town, and test a new one on a small area first.'];
    if ($hybrids) {
        $faq[] = ['Should I plant a hybrid ' . $lname . '?', 'A hybrid often yields more, but you must buy new seed every season, and it pays back only with good water and fertilizer. Open pollinated varieties cost less, and you can save their seed.'];
    }
    $faq[] = ['What does it mean when a variety is on the NSIC registry?', 'The National Seed Industry Council approved it after trials in several places, and the Bureau of Plant Industry keeps the record. A variety that is not on the registry can still be good, but its numbers come only from its seller.'];
    $faq[] = ['Where do these numbers come from?', 'From the NSIC registry of crop varieties kept by the Bureau of Plant Industry, and for unregistered varieties from their sellers and farm sources, each one named on the variety page. The words on our pages are our own.'];
@endphp

@section('title_full', $metaTitle . ' | anee.io')
@section('meta_description', $desc)

@push('head')
    <link rel="canonical" href="{{ $url }}">
    <meta property="og:title" content="{{ $metaTitle }}">
    <meta property="og:description" content="{{ $desc }}">
    <meta property="og:url" content="{{ $url }}">
    <meta property="og:image" content="{{ asset(ltrim($heroSrc, '/')) }}">
    <script type="application/ld+json">{!! json_encode([
        '@@context' => 'https://schema.org',
        '@graph' => [
            [
                '@type' => 'CollectionPage',
                'name' => $title,
                'description' => $desc,
                'url' => $url,
                'isAccessibleForFree' => false,
                'hasPart' => $all->map(fn ($v) => ['@type' => 'WebPage', 'name' => $v['name'], 'url' => $S::url('crop-varieties', $v['slug'])])->values()
                    ->push(['@type' => 'WebPageElement', 'isAccessibleForFree' => false, 'cssSelector' => '.hg-grid'])->all(),
            ],
            [
                '@type' => 'FAQPage',
                'mainEntity' => collect($faq)->map(fn ($f) => ['@type' => 'Question', 'name' => $f[0], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $f[1]]])->all(),
            ],
            [
                '@type' => 'BreadcrumbList',
                'itemListElement' => [
                    ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => url('/')],
                    ['@type' => 'ListItem', 'position' => 2, 'name' => $meta['crumb'], 'item' => $S::url($section)],
                    ['@type' => 'ListItem', 'position' => 3, 'name' => $name, 'item' => $url],
                ],
            ],
        ],
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
    @include('public.site.catalogue-css')
    @include('public.site.partials.crop-varieties-css')
@endpush

@section('content')
    @if ($hg || $member)@include('public.site.partials.hub-gate')@endif
    <section class="sp-hero">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 pt-10 pb-10 sm:pt-14">
            <nav class="sp-crumbs" aria-label="Breadcrumb">
                <a href="{{ url('/') }}">Home</a>
                <svg fill="none" stroke="currentColor" stroke-width="2.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                <a href="{{ $S::url($section) }}">{{ $meta['crumb'] }}</a>
                <svg fill="none" stroke="currentColor" stroke-width="2.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                <span>{{ $name }}</span>
            </nav>
            <div class="wk-hero mt-5">
                <div>
                    <span class="sp-chip">{{ $name }} varieties{{ $local ? ' · ' . $local : '' }}</span>
                    <h1 class="sp-h1 mt-3">{{ $title }}</h1>
                    <p class="sp-lead mt-4">{{ $intro }}</p>
                    <div class="wk-jump mt-6">
                        <a href="#finder"><svg fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="6.5"/><path stroke-linecap="round" d="M20 20l-4.2-4.2"/></svg>Find a variety</a>
                        <a href="#choose"><svg fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.5C10.5 5 8 4.5 4 4.5v13c4 0 6.5.5 8 2m0-13c1.5-1.5 4-2 8-2v13c-4 0-6.5.5-8 2m0-13v13"/></svg>How to choose</a>
                        @if ($hg)
                            <a href="#spGateCard" class="hg-chip"><svg fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><rect x="5" y="11" width="14" height="10" rx="2"/><path stroke-linecap="round" d="M8 11V8a4 4 0 018 0v3"/></svg>See them all with Libre + Anee</a>
                        @elseif ($member)
                            <a href="?guest=1#spGateCard" class="hg-chip is-open"><svg fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><rect x="5" y="11" width="14" height="10" rx="2"/><path stroke-linecap="round" d="M8 11V8a4 4 0 017.5-2"/></svg>Open for you · <u>See it as a guest</u></a>
                        @endif
                    </div>
                </div>
                <figure class="cv-hero">
                    <img src="{{ asset(ltrim($heroSrc, '/')) }}" alt="{{ $heroAlt }}" width="800" height="600" fetchpriority="high">
                    <div class="cv-hero-stats">
                        <span><b>{{ $all->count() }}</b>varieties</span>
                        @if ($mats->count() >= 3)<span><b>{{ $fmt($mats->min(), 0) }} to {{ $fmt($mats->max(), 0) }}</b>{{ $unit }} to harvest</span>@endif
                        @if ($years->count())<span><b>{{ $years->min() }} to {{ $years->max() }}</b>registered</span>@endif
                    </div>
                </figure>
            </div>
            <div class="mt-8">
                <div class="sp-tabs">
                    <a href="{{ $S::url('crop-varieties') }}">All crops</a>
                    @foreach (collect($CV::crops())->filter(fn ($c) => $c['hasHub'] && ($c['group'] ?? '') === ($crop['group'] ?? ''))->sortByDesc('count')->take(8) as $c)
                        <a href="{{ url('/crop-varieties/' . $c['slug']) }}" class="{{ $c['key'] === $crop['key'] ? 'is-on' : '' }}">{{ $c['name'] }}</a>
                    @endforeach
                    <a href="{{ $S::url('rice-varieties') }}">Rice</a>
                </div>
            </div>
        </div>
    </section>

    {{-- The finder --}}
    <section class="bg-[#fbfcf9] border-b border-[#eef3e8]" id="finder" style="scroll-margin-top: 5rem">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 py-12 sm:py-16">
            <div class="wk-sec-h">
                <h2>Find the Right {{ $name }} Variety for Your Farm</h2>
                <p>Search a name or a brand, pick a type, then sort by yield or by {{ $unit }} to harvest. Tap a card for the full record.</p>
            </div>
            @include('public.site.partials.crop-variety-finder', ['varieties' => $varieties, 'crop' => $crop, 'hubGate' => $hg ? [
                'here' => '/crop-varieties/' . $crop['slug'],
                'title' => 'See all ' . $all->count() . ' ' . $lname . ' varieties, and the ones that fit your farm',
                'lead' => 'Compare every variety side by side with Libre + Anee: its yield, its strengths and weaknesses, and how it will do on your farm, in your season and your soil.',
            ] : null])
        </div>
    </section>

    {{-- How to choose, the guides, the problems --}}
    <section class="bg-white" id="choose" style="scroll-margin-top: 5rem">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 py-12 sm:py-16">
            <div class="wk-sec-h">
                <h2>How to Choose a {{ $name }} Variety</h2>
                @if (! empty($crop['choose']))<p>{{ $crop['choose'] }}</p>@endif
            </div>
            <div class="rv-how">
                <div><h3>{{ ucfirst($unit) }} to harvest</h3><p>{{ ucfirst($crop['harvest'] ?? ($unit . ' to harvest')) }}. A shorter crop frees the field sooner and dodges more of the weather, while a longer one often fills more.</p></div>
                <div><h3>Yield</h3><p>Trial yields come from well managed plots in several places, so treat them as a ranking more than a promise. Compare varieties within the same type.</p></div>
                <div><h3>Ratings</h3><p>R means resistant and MR moderately resistant. MS and S mean the variety took clear or heavy damage in the trials, so scout it often.</p></div>
                <div><h3>Registered or not</h3><p>A registered variety passed national trials. One that is not on the registry shows only its seller's numbers, so test it on a small area first.</p></div>
            </div>
            @php
                $keys = $crop['catalogue'] ?? [];
                $probs = collect(['pests', 'diseases'])->flatMap(fn ($sec) => collect(\App\Support\FieldCatalogue::problems($sec))
                    ->filter(fn ($p, $slug) => array_intersect($keys, $p['crops'] ?? []) && is_file(database_path('site-pages/' . $sec . '/' . $slug . '.json')))
                    ->map(fn ($p, $slug) => ['name' => $p['name'], 'url' => $S::url($sec, $slug)]))->take(8);
            @endphp
            @if ($probs->count())
                <div class="mt-12 wk-sec-h"><h2>Pests and Diseases of {{ $name }}</h2>
                    <p>The problems that hit {{ $lname }} fields most often. A variety rated resistant to one of these saves you sprays, so check the ratings on each variety page.</p></div>
                <div class="rv-pop">
                    @foreach ($probs as $p)
                        <a href="{{ $p['url'] }}">{{ $p['name'] }}</a>
                    @endforeach
                </div>
            @endif
            <div class="mt-12 wk-sec-h"><h2>Grow Your {{ $name }} Season with anee.io</h2>
                <p>Once you pick a variety, anee.io dates every task from each lot's own planting day, from land preparation to harvest, and Anee, your smart farm technician, answers your questions about it.</p></div>
            <div class="rv-links">
                @foreach (array_merge($guides, [['/crop-varieties', 'Every crop, every variety'], ['/features/variety-research', 'Variety Ranking: Anee compares varieties for your town'], ['/features/growth-stages-and-weather', 'Growth stages and weather for each lot']]) as [$href, $label])
                    <a href="{{ url($href) }}">{{ $label }}<svg fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg></a>
                @endforeach
            </div>

            <div class="mt-12 wk-sec-h"><h2>Questions About {{ $name }} Varieties</h2></div>
            <div class="sp-body mt-6"><div class="sp-faq">
                @foreach ($faq as [$q, $a])
                    <details>
                        <summary><span>{{ $q }}</span><svg fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24"><path stroke-linecap="round" d="M12 5v14M5 12h14"/></svg></summary>
                        <div class="ans">{{ $a }}</div>
                    </details>
                @endforeach
            </div></div>
            <p class="rv-src">Sources include the <a href="https://nsic.buplant.da.gov.ph/registry.php" rel="noopener" target="_blank">NSIC registry of crop varieties</a> kept by the Bureau of Plant Industry{{ $sold ? ', and for the varieties not on it, their sellers and farm sources, named on each variety page' : '' }}. The words and pictures are anee.io's own.</p>
        </div>
    </section>
@endsection
