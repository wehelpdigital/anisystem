@extends('layouts.public')

{{-- /crop-varieties (2026-10-13): every crop with its varieties, grouped,
     each crop to its own finder (or, for a crop with only a variety or two,
     straight to their pages). Rice has its own finder at /rice-varieties.
     Each crop's finder has its own search. See
     App\Support\CropVarieties. --}}
@include('public.partials.site-css')
@include('public.site.css')

@php
    $S = \App\Support\SitePages::class;
    $CV = \App\Support\CropVarieties::class;
    $book = $CV::book();
    $crops = collect($CV::crops());
    $all = collect($CV::all());
    $byCrop = $all->groupBy('crop');
    $groups = collect($book['groups'] ?? [])->sortBy('order');
    $registered = $all->filter(fn ($v) => ! empty($v['reg']))->count();
    // Rice is one of the crops too (2026-10-14): its own finder at
    // /rice-varieties, counted here with the rest.
    $rice = collect(\App\Support\RiceVarieties::all());
    $riceCount = $rice->count();
    $riceDays = $rice->pluck('maturity')->filter();
    $total = $all->count() + $riceCount;
    $fmt = fn ($x) => number_format((float) $x, 0, '.', '');
    $faq = [
        ['How many crop varieties are registered in the Philippines?', 'This list holds ' . number_format($total) . ' varieties of ' . ($crops->count() + 1) . ' crops, ' . number_format($registered + $riceCount) . ' of them on the national registry of the National Seed Industry Council. Corn has the most, with ' . ($crops['corn']['count'] ?? 0) . ', then rice with ' . $riceCount . '.'],
        ['What is the NSIC?', 'The National Seed Industry Council approves new crop varieties after trials in several places, and the Bureau of Plant Industry keeps the registry. A registered variety has passed those trials, so its numbers are more than a seller\'s promise.'],
        ['Why is a variety I buy not on the list of registered ones?', 'Many vegetable hybrids sold by seed companies are never registered with the NSIC. We list the popular ones too, with the facts their sellers give, and mark them as not on the registry.'],
        ['How do I choose the right variety?', 'Pick by your buyer first, then by your season and your area. Compare days to harvest and yield within the same type, check the pest and disease ratings, and plant a small area of a new variety before you switch the whole farm.'],
    ];
@endphp

@section('title_full', $meta['metaTitle'] . ' | anee.io')
@section('meta_description', $meta['metaDescription'])

@push('head')
    <link rel="canonical" href="{{ $S::url($section) }}">
    <meta property="og:title" content="{{ $meta['metaTitle'] }}">
    <meta property="og:description" content="{{ $meta['metaDescription'] }}">
    <meta property="og:url" content="{{ $S::url($section) }}">
    <meta property="og:image" content="{{ asset('images/site/guides/crops-vegetables-philippines.webp') }}">
    <script type="application/ld+json">{!! json_encode([
        '@@context' => 'https://schema.org',
        '@graph' => [
            [
                '@type' => 'CollectionPage',
                'name' => $meta['hubTitle'],
                'description' => $meta['metaDescription'],
                'url' => $S::url($section),
                'hasPart' => $crops->filter(fn ($c) => $c['hasHub'])->map(fn ($c) => ['@type' => 'WebPage', 'name' => $c['name'] . ' varieties', 'url' => url('/crop-varieties/' . $c['slug'])])->values()->all(),
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
                ],
            ],
        ],
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
    @include('public.site.catalogue-css')
    @include('public.site.partials.crop-varieties-css')
@endpush

@section('content')
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
                        <a href="#crops"><svg fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h10"/></svg>Browse by crop</a>
                        <a href="#read"><svg fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.5C10.5 5 8 4.5 4 4.5v13c4 0 6.5.5 8 2m0-13c1.5-1.5 4-2 8-2v13c-4 0-6.5.5-8 2m0-13v13"/></svg>How to read a record</a>
                    </div>
                </div>
                <div class="rv-stats" aria-label="The list in numbers">
                    <div class="rv-stat"><b>{{ number_format($total) }}</b><span>varieties, each with its own page</span></div>
                    <div class="rv-stat is-gold"><b>{{ $crops->count() + 1 }}</b><span>crops, from rice and corn to mango and cacao</span></div>
                    <div class="rv-stat"><b>{{ number_format($registered + $riceCount) }}</b><span>on the national NSIC registry</span></div>
                    <div class="rv-stat"><b>{{ $all->count() - $registered }}</b><span>sold by seed companies, never registered</span></div>
                </div>
            </div>
            <div class="mt-8">
                <div class="sp-tabs">
                    <a href="{{ $S::url('crops') }}">Crop guides</a>
                    <a href="{{ $S::url('crop-varieties') }}" class="is-on">Crop varieties</a>
                    <a href="{{ $S::url('land-preparation') }}">Land preparation</a>
                    <a href="{{ $S::url('pests') }}">Crop pests</a>
                    <a href="{{ $S::url('diseases') }}">Crop diseases</a>
                    <a href="{{ $S::url('weeds') }}">Weeds and grasses</a>
                    <a href="{{ $S::url('blog') }}">Latest in Agriculture</a>
                </div>
            </div>
        </div>
    </section>

    {{-- The crops, by group --}}
    <section class="bg-white" id="crops" style="scroll-margin-top: 5rem">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 py-12 sm:py-16">
            <div class="wk-sec-h">
                <h2>Browse Crop Varieties by Crop</h2>
                <p>Each crop opens its own finder, with every variety's days to harvest, yield, ratings and where it is recommended.</p>
            </div>
            <div class="mt-8">
                @foreach ($groups as $gk => $g)
                    @php $inGroup = $crops->filter(fn ($c) => ($c['group'] ?? 'fruit') === $gk)->sortByDesc('count'); @endphp
                    @if ($inGroup->count() || $gk === 'cereal')
                        <div class="cv-group">
                            <h3>{{ $g['label'] }}</h3>
                            <div class="cv-crops">
                                @if ($gk === 'cereal')
                                    <a class="cv-crop is-rice" href="{{ $S::url('rice-varieties') }}">
                                        <span class="cv-crop-top"><b>Rice</b><span class="n">{{ $riceCount }}</span></span>
                                        <span class="l">palay</span>
                                        <span class="d">{{ $riceDays->count() >= 2 ? $fmt($riceDays->min()) . ' to ' . $fmt($riceDays->max()) . ' days to harvest. ' : '' }}<span class="h">Inbred, hybrid and special rice, from PSB Rc 18 to the newest NSIC releases, with the brand names on the sack.</span></span>
                                    </a>
                                @endif
                                @foreach ($inGroup as $c)
                                    @php
                                        $vs = collect($byCrop[$c['key']] ?? []);
                                        $mm = $vs->pluck('maturity')->filter();
                                        $u = $mm->pluck('unit')->countBy()->sortDesc()->keys()->first();
                                        $mv = $mm->where('unit', $u)->pluck('value');
                                    @endphp
                                    @if ($c['hasHub'])
                                        <a class="cv-crop" href="{{ url('/crop-varieties/' . $c['slug']) }}">
                                            <span class="cv-crop-top"><b>{{ $c['name'] }}</b><span class="n">{{ $c['count'] }}</span></span>
                                            @if (! empty($c['local']) && strtolower($c['local']) !== strtolower($c['name']))<span class="l">{{ $c['local'] }}</span>@endif
                                            <span class="d">{{ $mv->count() >= 2 ? $fmt($mv->min()) . ' to ' . $fmt($mv->max()) . ' ' . $u . ' to harvest. ' : '' }}<span class="h">{{ $c['hub'] ?? '' }}</span></span>
                                        </a>
                                    @else
                                        <div class="cv-crop">
                                            <span class="cv-crop-top"><b>{{ $c['name'] }}</b><span class="n">{{ $c['count'] }}</span></span>
                                            @if (! empty($c['local']) && strtolower($c['local']) !== strtolower($c['name']))<span class="l">{{ $c['local'] }}</span>@endif
                                            <span class="few">
                                                @foreach ($vs as $v)
                                                    <a href="{{ $S::url('crop-varieties', $v['slug']) }}">{{ $v['name'] }}</a>
                                                @endforeach
                                            </span>
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>

            <div class="mt-14 wk-sec-h" id="read" style="scroll-margin-top: 5rem"><h2>How to Read a Variety's Record</h2>
                <p>Every variety page shows the same few things. Here is what each one tells you before you buy seed or planting material.</p></div>
            <div class="rv-how">
                <div><h3>Days to harvest</h3><p>Counted from sowing or transplanting, or in months for root crops and sugarcane. Fruit trees count years to the first fruit instead.</p></div>
                <div><h3>Yield</h3><p>The average from national trials, in tonnes per hectare for most field crops. One tonne of shelled corn fills about 20 sacks of 50 kilograms.</p></div>
                <div><h3>Ratings</h3><p>R means resistant and MR moderately resistant. MS and S mean the variety took clear or heavy damage in the trials, so scout it often.</p></div>
                <div><h3>Registered or sold</h3><p>A registered variety passed national trials. A seller's variety shows the seller's own numbers, so test it on a small area first.</p></div>
            </div>
            <div class="mt-12 wk-sec-h"><h2>Plan the Season Around Your Variety</h2>
                <p>Once you pick a variety, anee.io dates every task from each lot's own planting day, and Anee, your smart farm technician, can research the varieties that suit your town.</p></div>
            <div class="rv-links">
                @foreach ([
                    ['/rice-varieties', 'Every rice variety, side by side'],
                    ['/features/variety-research', 'Variety Research: Anee compares varieties for your town'],
                    ['/crops/pagtatanim-ng-mais', 'Pagtatanim ng mais, step by step'],
                    ['/crops/vegetables-philippines', 'Vegetables in the Philippines'],
                    ['/land-preparation', 'Land preparation for every crop'],
                    ['/features/growth-stages-and-weather', 'Growth stages and weather for each lot'],
                ] as [$href, $label])
                    <a href="{{ url($href) }}">{{ $label }}<svg fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg></a>
                @endforeach
            </div>

            <div class="mt-12 wk-sec-h"><h2>Questions About Crop Varieties</h2></div>
            <div class="sp-body mt-6"><div class="sp-faq">
                @foreach ($faq as [$q, $a])
                    <details>
                        <summary><span>{{ $q }}</span><svg fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24"><path stroke-linecap="round" d="M12 5v14M5 12h14"/></svg></summary>
                        <div class="ans">{{ $a }}</div>
                    </details>
                @endforeach
            </div></div>
            <p class="rv-src">Sources include the <a href="https://nsic.buplant.da.gov.ph/registry.php" rel="noopener" target="_blank">NSIC registry of crop varieties</a> kept by the Bureau of Plant Industry, and for the varieties not on it, their sellers and farm sources, named on each variety page. The words and pictures are anee.io's own.</p>
        </div>
    </section>
@endsection
