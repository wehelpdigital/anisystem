@extends('layouts.public')

{{-- /rice-varieties (2026-10-13): the rice variety finder. Every released
     rice variety (no agency is shown as the source, the owner 2026-10-17),
     each card a link to its own page; filter by the kind of farm, the
     days to harvest and the pests and diseases it holds up against, sort by
     year, yield or maturity. All the cards are in the page (so search
     engines read every link); the script only hides and orders them. --}}
@include('public.partials.site-css')
@include('public.site.css')

@php
    $S = \App\Support\SitePages::class;
    $RV = \App\Support\RiceVarieties::class;
    $all = collect($varieties);
    $envCount = fn ($k) => $all->filter(fn ($v) => in_array($k, $v['environments'], true))->count();
    $years = $all->pluck('year')->filter();
    $fmt = fn ($x) => rtrim(rtrim(number_format((float) $x, 1, '.', ''), '0'), '.');
    $avgOf = fn ($f) => $fmt(round($all->filter($f)->avg('yieldAvg') ?? 0, 1));
    $irrInbred = $avgOf(fn ($v) => $v['type'] === 'inbred' && in_array('irrigated', $v['environments'], true));
    $irrHybrid = $avgOf(fn ($v) => $v['type'] === 'hybrid' && in_array('irrigated', $v['environments'], true));
    $saline = $avgOf(fn ($v) => in_array('saline', $v['environments'], true));
    $popular = ['nsic-rc-222', 'nsic-rc-216', 'nsic-rc-160', 'nsic-rc-402', 'psb-rc-18', 'psb-rc-82', 'nsic-rc-238', 'nsic-rc-300', 'nsic-rc-354', 'nsic-rc-436', 'nsic-rc-440', 'nsic-rc-480'];
    $popular = $all->whereIn('slug', $popular)->sortBy(fn ($v) => array_search($v['slug'], $popular))->values();
    // The names on the seed bag (Jackpot 102 is NSIC Rc 666H), hybrids first.
    $tradeName = fn ($t) => preg_replace('/(?<=[A-Za-z0-9])-(?=[A-Za-z0-9])/', ' ', $t);
    $brands = $all->filter(fn ($v) => ! empty($v['tradeNames']))
        ->sortBy(fn ($v) => [$v['type'] === 'hybrid' ? 0 : 1, strtolower($tradeName($v['tradeNames'][0]))])->values();
    $faq = [
        ['What is the best rice variety in the Philippines?', 'There is no single best one. The best variety is the one bred for your kind of farm, your season and the pests in your area. Start with the filters above: pick your farm type, how many days you can wait for harvest and the problems you fear most, then compare the average yields. Your municipal agriculturist knows which ones do well in your town.'],
        ['What do the NSIC and PSB numbers mean?', 'NSIC is the National Seed Industry Council, which approves rice varieties for planting in the Philippines. Before it, the Philippine Seed Board (PSB) did the job. Rc stands for rice, and a number ending in H marks a hybrid. So NSIC Rc 222 is an inbred, and NSIC Rc 204H is a hybrid.'],
        ['What does this rice variety list show?', 'Every released rice variety, with its maturity, yields, pest and disease ratings, grain quality, season and regions' . ($brands->count() ? ', and the brand names it is sold under, with links to the seed companies\' own pages. ' : '. ') . 'The words on our pages are our own.'],
        ['Are hybrid rice varieties better than inbreds?', 'In the trials the irrigated hybrids average ' . $irrHybrid . ' tonnes per hectare, against ' . $irrInbred . ' for the irrigated inbreds. But you must buy new hybrid seed every season, and hybrids need good water and fertilizer to give that much. Inbreds cost less, and their seed grows true. Compare both kinds above with the Type filter.'],
        ['Which rice varieties are for saline or flood prone fields?', 'Use the Kind of farm filter. Saline prone varieties are bred to stand salt in the soil, and flood prone ones to survive a spell under water. They yield less than irrigated varieties on good land: the saline prone ones average ' . $saline . ' tonnes per hectare, because they are bred to survive where others fail.'],
    ];
    // The members' gate (2026-10-14): a guest sees the finder's first rows.
    $hg = \App\Support\ContentGate::hubApplies($section);
    $member = auth()->check() && ! $hg;
    if ($brands->count()) {
        array_splice($faq, 4, 0, [['Why does my seed bag show a brand name and not an NSIC number?', 'Seed companies sell their varieties under their own brand names, while each is registered under its NSIC number. They are the same seed. Type the brand name into the search box above, and the card shows the NSIC number with its numbers.']]);
    }
@endphp

@section('title_full', $meta['metaTitle'] . ' | anee.io')
@section('meta_description', $meta['metaDescription'])

@push('head')
    <link rel="canonical" href="{{ $S::url($section) }}">
    <meta property="og:title" content="{{ $meta['metaTitle'] }}">
    <meta property="og:description" content="{{ $meta['metaDescription'] }}">
    <meta property="og:url" content="{{ $S::url($section) }}">
    <meta property="og:image" content="{{ asset('images/site/photos/palay-heads.jpg') }}">
    <script type="application/ld+json">{!! json_encode([
        '@@context' => 'https://schema.org',
        '@graph' => [
            [
                '@type' => 'CollectionPage',
                'name' => $meta['hubTitle'],
                'description' => $meta['metaDescription'],
                'url' => $S::url($section),
                'isAccessibleForFree' => false,
                'hasPart' => $all->map(fn ($v) => ['@type' => 'WebPage', 'name' => $v['name'], 'url' => $S::url($section, $v['slug'])])->values()
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
                    ['@type' => 'ListItem', 'position' => 2, 'name' => 'Crop varieties', 'item' => $S::url('crop-varieties')],
                    ['@type' => 'ListItem', 'position' => 3, 'name' => 'Rice', 'item' => $S::url($section)],
                ],
            ],
        ],
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
    @include('public.site.catalogue-css')
    <style>
        .rv-stats { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .7rem; }
        .rv-stat { padding: 1rem 1.1rem; border-radius: 1.2rem; background: #fff; border: 1px solid #e1ecd3; box-shadow: 0 18px 36px -30px rgb(20 33 12 / .5); }
        .rv-stat b { display: block; font-family: var(--font-heading); font-size: clamp(1.6rem, 3vw, 2.1rem); font-weight: 800; line-height: 1; color: #2d5016; }
        .rv-stat span { display: block; margin-top: .35rem; font-size: .82rem; font-weight: 700; color: #5b6b4c; line-height: 1.35; }
        .rv-stat.is-gold b { color: #a07800; }

        .rv-pop { margin-top: 1.2rem; display: flex; flex-wrap: wrap; gap: .45rem; }
        .rv-pop a { display: inline-flex; align-items: center; gap: .35rem; padding: .45rem .85rem; border-radius: 999px; font-size: .86rem; font-weight: 800; color: #2d5016; background: #fff; border: 1px solid #dbe8cc; text-decoration: none;
            transition: transform .28s cubic-bezier(.22,1,.36,1), border-color .28s cubic-bezier(.22,1,.36,1); }
        .rv-pop a:hover { transform: translateY(-2px); border-color: #a8cc7e; }
        .rv-pop a small { font-weight: 700; color: #7c8d6b; }

        .rv-how { margin-top: 1.6rem; display: grid; gap: .8rem; grid-template-columns: repeat(auto-fill, minmax(min(100%, 15rem), 1fr)); }
        .rv-how div { padding: 1.1rem; border-radius: 1.2rem; background: #fff; border: 1px solid #e3ecd8; }
        .rv-how h3 { font-family: var(--font-heading); font-size: 1.02rem; font-weight: 800; color: #14210c; }
        .rv-how p { margin-top: .35rem; font-size: .9rem; line-height: 1.6; color: #4b5563; }
        .rv-links { margin-top: 1.4rem; display: grid; gap: .6rem; grid-template-columns: repeat(auto-fill, minmax(min(100%, 16rem), 1fr)); }
        .rv-links a { display: flex; align-items: center; justify-content: space-between; gap: .6rem; padding: .85rem 1rem; border-radius: 1rem; background: #fff; border: 1px solid #e3ecd8;
            font-weight: 800; color: #14210c; text-decoration: none; transition: border-color .28s cubic-bezier(.22,1,.36,1), transform .28s cubic-bezier(.22,1,.36,1); }
        .rv-links a:hover { border-color: #a8cc7e; transform: translateY(-2px); }
        .rv-links svg { flex: none; width: 1rem; height: 1rem; color: #4a7c2a; }
        .rv-src { margin-top: 1rem; font-size: .82rem; line-height: 1.6; color: #6b7280; }
        .rv-src a { color: #3d6823; font-weight: 700; text-decoration: underline; text-underline-offset: 2px; }
        /* Behind the members' gate the brand list is a blurred taste. */
        .rv-brands.hg-soft { max-height: 8.5rem; overflow: hidden; filter: blur(4px); opacity: .7; user-select: none;
            -webkit-mask-image: linear-gradient(#000 30%, transparent); mask-image: linear-gradient(#000 30%, transparent); }
        @media (prefers-reduced-motion: reduce) { .rv-card, .rv-chip, .rv-pop a, .rv-links a, .rv-more { transition: none; } }
    </style>
@endpush

@section('content')
    @if ($hg || $member)@include('public.site.partials.hub-gate')@endif
    <section class="sp-hero">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 pt-10 pb-10 sm:pt-14">
            <nav class="sp-crumbs" aria-label="Breadcrumb">
                <a href="{{ url('/') }}">Home</a>
                <svg fill="none" stroke="currentColor" stroke-width="2.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                <a href="{{ $S::url('crop-varieties') }}">Crop varieties</a>
                <svg fill="none" stroke="currentColor" stroke-width="2.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                <span>Rice</span>
            </nav>
            <div class="wk-hero mt-5">
                <div>
                    <span class="sp-chip">{{ $meta['kicker'] }}</span>
                    <h1 class="sp-h1 mt-3">{{ $meta['hubTitle'] }}</h1>
                    <p class="sp-lead mt-4">{{ $meta['intro'] }}</p>
                    <div class="wk-jump mt-6">
                        <a href="#finder"><svg fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="6.5"/><path stroke-linecap="round" d="M20 20l-4.2-4.2"/></svg>Find a variety</a>
                        <a href="#read"><svg fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.5C10.5 5 8 4.5 4 4.5v13c4 0 6.5.5 8 2m0-13c1.5-1.5 4-2 8-2v13c-4 0-6.5.5-8 2m0-13v13"/></svg>How to read the numbers</a>
                        @if ($hg)
                            <a href="#spGateCard" class="hg-chip"><svg fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><rect x="5" y="11" width="14" height="10" rx="2"/><path stroke-linecap="round" d="M8 11V8a4 4 0 018 0v3"/></svg>See them all with Libre + Anee</a>
                        @elseif ($member)
                            <a href="?guest=1#spGateCard" class="hg-chip is-open"><svg fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><rect x="5" y="11" width="14" height="10" rx="2"/><path stroke-linecap="round" d="M8 11V8a4 4 0 017.5-2"/></svg>Open for you · <u>See it as a guest</u></a>
                        @endif
                    </div>
                </div>
                <div class="rv-stats" aria-label="The list in numbers">
                    <div class="rv-stat"><b>{{ $all->count() }}</b><span>rice varieties, each with its own page</span></div>
                    <div class="rv-stat is-gold"><b>{{ $all->where('type', 'hybrid')->count() }}</b><span>hybrids, the rest inbreds and special rice</span></div>
                    <div class="rv-stat"><b>{{ count(array_filter(array_keys($RV::ENVIRONMENTS), fn ($k) => $envCount($k) > 0)) }}</b><span>kinds of farm, from irrigated to saline and upland</span></div>
                    <div class="rv-stat"><b>{{ $years->min() }} to {{ $years->max() }}</b><span>years of NSIC and PSB approvals</span></div>
                </div>
            </div>
            <div class="mt-8">
                <div class="sp-tabs">
                    <a href="{{ $S::url('crops') }}">Crop guides</a>
                    <a href="{{ $S::url('crop-varieties') }}" class="is-on">Crop varieties</a>
                    <a href="{{ $S::url('how-to-plant') }}">How to plant</a>
                    <a href="{{ $S::url('land-preparation') }}">Land preparation</a>
                    <a href="{{ $S::url('pests') }}">Crop pests</a>
                    <a href="{{ $S::url('diseases') }}">Crop diseases</a>
                    <a href="{{ $S::url('weeds') }}">Weeds and grasses</a>
                    <a href="{{ $S::url('nutrient-deficiencies') }}">Nutrient deficiencies</a>
                    <a href="{{ $S::url('blog') }}">Latest in Agriculture</a>
                </div>
            </div>
        </div>
    </section>

    {{-- The finder --}}
    <section class="bg-[#fbfcf9] border-b border-[#eef3e8]" id="finder" style="scroll-margin-top: 5rem">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 py-12 sm:py-16">
            <div class="wk-sec-h">
                <h2>Find the Right Rice Variety for Your Farm</h2>
                <p>Pick your kind of farm first, then how long you can wait for harvest and the pests and diseases you fear most. Each card shows the days to harvest, the average yield and the best trial yield, in tonnes per hectare, and opens the variety's full page.</p>
            </div>
            @include('public.site.partials.variety-finder', ['varieties' => $varieties, 'hubGate' => $hg ? [
                'here' => '/' . $section,
                'title' => 'See all ' . $all->count() . ' rice varieties, and the ones that fit your farm',
                'lead' => 'Compare every variety side by side with Libre + Anee: its yield, its strengths and weaknesses, and how it will do on your farm, in your season and your soil.',
            ] : null])

            @if ($popular->count())
                <div class="mt-12">
                    <div class="wk-sec-h"><h2>Popular Rice Varieties Farmers Ask About</h2>
                        <p>The varieties farmers look up the most, among them the old favorites still planted across the country.</p></div>
                    <div class="rv-pop">
                        @foreach ($popular as $v)
                            <a href="{{ $S::url('rice-varieties', $v['slug']) }}">{{ $v['name'] }} <small>{{ (int) $v['maturity'] }} days</small></a>
                        @endforeach
                    </div>
                </div>
            @endif

            @if ($brands->count())
                <div class="mt-12" id="brands" style="scroll-margin-top: 5rem">
                    <div class="wk-sec-h"><h2>Rice Varieties by Brand Name</h2>
                        <p>Seed bags often carry the company's brand name, not the NSIC number. Here is the NSIC variety behind each name, so you can read the numbers of the seed you were offered.</p></div>
                    <div class="rv-pop rv-brands {{ $hg ? 'hg-soft' : '' }}" @if ($hg) inert @endif>
                        @foreach ($brands as $v)
                            <a href="{{ $S::url('rice-varieties', $v['slug']) }}">{{ $tradeName($v['tradeNames'][0]) }} <small>{{ $v['name'] }}</small></a>
                        @endforeach
                    </div>
                    @if ($hg)<p class="mt-3"><a href="#spGateCard" class="hg-chip">See the variety behind every brand with Libre + Anee ›</a></p>@endif
                </div>
            @endif
        </div>
    </section>

    {{-- How to read the numbers --}}
    <section class="bg-white" id="read" style="scroll-margin-top: 5rem">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 py-12 sm:py-16">
            <div class="wk-sec-h">
                <h2>How to Read a Rice Variety's Numbers</h2>
                <p>Every card and page shows the same few numbers. Here is what each one tells you before you buy seed.</p>
            </div>
            <div class="rv-how">
                <div><h3>Days to harvest</h3><p>Counted from sowing. Varieties of 100 to 120 days count as short duration and 120 to 140 as medium. A direct seeded crop is often ready a week sooner than a transplanted one.</p></div>
                <div><h3>Average and top yield</h3><p>The average is what the variety gave across trials, in tonnes per hectare. The top is its best trial, with ideal care. One tonne is about 20 cavans of 50 kilograms.</p></div>
                <div><h3>Pest and disease ratings</h3><p>R means resistant and MR moderately resistant. I is intermediate. MS and S mean the variety took clear or heavy damage, so scout it often.</p></div>
                <div><h3>Amylose and grain</h3><p>Amylose decides how rice cooks: low is soft, intermediate is soft but not sticky, high is firm. Head rice is the share of whole grains after milling.</p></div>
            </div>
            <div class="mt-12 wk-sec-h"><h2>Plan Your Variety's Whole Season</h2>
                <p>Once you pick a variety, anee.io dates every task from each lot's own planting day, from land preparation to harvest, and Anee, your smart farm technician, can research the varieties that suit your town.</p></div>
            <div class="rv-links">
                @foreach ([
                    ['/crops/rice-varieties-philippines', 'Rice varieties in the Philippines: inbred, hybrid, special'],
                    ['/blog/best-rice-variety-for-my-area', 'How to choose the best rice variety for your area'],
                    ['/features/variety-research', 'Variety Ranking: Anee compares varieties for your town'],
                    ['/blog/increase-rice-yield-per-hectare', 'Raise your rice yield per hectare, check by check'],
                    ['/land-preparation/rice-land-preparation', 'Land preparation for rice'],
                    ['/blog/best-time-to-plant-rice-philippines', 'The best time to plant rice in your province'],
                ] as [$href, $label])
                    <a href="{{ url($href) }}">{{ $label }}<svg fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg></a>
                @endforeach
            </div>

            <div class="mt-12 wk-sec-h"><h2>Questions About Rice Varieties</h2></div>
            <div class="sp-body mt-6"><div class="sp-faq">
                @foreach ($faq as [$q, $a])
                    <details>
                        <summary><span>{{ $q }}</span><svg fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24"><path stroke-linecap="round" d="M12 5v14M5 12h14"/></svg></summary>
                        <div class="ans">{{ $a }}</div>
                    </details>
                @endforeach
            </div></div>
            <p class="rv-src">The words and pictures are anee.io's own.
                Some of the newest releases, up to {{ $years->max() }}, have no days to harvest or yield listed yet: ask your municipal agriculturist for their regional trial results.</p>
        </div>
    </section>
@endsection
