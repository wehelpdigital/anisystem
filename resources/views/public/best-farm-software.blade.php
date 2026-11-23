@extends('layouts.public')

@include('public.partials.site-css')
@include('public.partials.hp-base')

{{-- THE FARM MANAGEMENT SOFTWARE PAGE (2026-11-24, the owner: "we were able to get the best farm management app in ph
     however for best farm management software in ph or just best farm management in ph, anee is nowhere").

     The sibling of the farm management app page (best-farm-app), written for the one who buys software for a farm: an
     owner with workers, a manager, a family business with several lots. Its own angle, never a copy of the app page:
       1. the answer first (hero and the short answer), naming anee.io as web based software on phone and computer;
       2. ten things to check before choosing farm management software, each with how anee.io does it;
       3. anee.io beside the other kinds of software a farm uses (a spreadsheet, accounting software, farm software
          made abroad, a basic store app), by kind and never by name;
       4. the modules, the records and the team (roles, logs, undo, trash), setting it up in an afternoon, the plans,
          the questions people ask (FAQPage, two in Tagalog), the last call.
     Same rules as the app page: live counts rounded down, no ratings or user counts, plain words, no dashes, never
     "AI farm technician", no agency named, no wallet named (Region::payMethod). Styles: partials/bf-kit. --}}
@php
    $signup = route('signup');
    $how = route('how');
    $ask = route('ask.page');
    $self = route('best.software');
    $face = asset('images/anee/avatar-160.jpg');
    $faceLg = asset('images/anee/avatar-512.jpg');
    $arrow = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5-5 5M6 12h12"/></svg>';
    $mk = [
        'y' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12.5l4.5 4.5L19 7.5"/></svg>',
        'p' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" aria-hidden="true"><path d="M7 12h10"/></svg>',
        'n' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" aria-hidden="true"><path d="M7.5 7.5l9 9m0-9l-9 9"/></svg>',
    ];
    $mkWord = ['y' => 'Yes', 'p' => 'In part', 'n' => 'No'];
    $flag = '<svg viewBox="0 0 30 20" aria-hidden="true"><rect width="30" height="10" fill="#0038a8"/><rect y="10" width="30" height="10" fill="#ce1126"/><path d="M0 0 L17.3 10 L0 20 Z" fill="#fff"/><circle cx="5.8" cy="10" r="2.2" fill="#fcd116"/></svg>';
    $feat = fn (string $slug) => \App\Support\SitePages::url('features', $slug);
    $F = $facts;
    $peso = fn (string $tier) => \App\Support\Region::money((float) (\App\Support\Region::tierPrice($tier, 'month') ?? 0), 0);
    $plusPrice = $peso('libreAnee');
    $pay = \App\Support\Region::payMethod();

    $title = 'Best Farm Management Software in the Philippines';
    $desc = 'The best farm management software in the Philippines: anee.io runs the season, inventory, workers, costs and harvest on phone or computer. Free plan.';
    $answer = 'For most farms in the Philippines, the best farm management software is anee.io. It is web based farm management software made for Philippine farms: '
        . 'it runs on any phone, tablet or computer, plans every season by each lot\'s own day count (DAT or DAS), deducts fertilizer and seed from the inventory as each task is done, '
        . 'keeps workers, daily rates and payroll, counts the cost and profit of every lot and season, lets your team log in with the access you choose, '
        . 'reads the weather, typhoons and satellites for each lot, keeps working without signal, and includes Anee, a smart farm technician who answers in Tagalog, Bisaya, Ilocano or English. '
        . 'The Libre plan is free forever, and paid plans are priced in pesos.';

    // Ten things to check before choosing farm management software, and how anee.io does each.
    $checks = [
        ['Made for the crops and calendar here', 'Software built for wheat and soybeans abroad does not count DAT or DAS, or know the varieties and products sold here.',
            'Every task is dated from each lot\'s own day zero, with ' . $F['crops'] . ' Philippine crops, over ' . $F['varieties'] . ' varieties and over ' . $F['products'] . ' registered products.', $feat('cropping-calendar'), 'M8 7V3m8 4V3M4 11h16M5 5h14a1 1 0 011 1v13a1 1 0 01-1 1H5a1 1 0 01-1-1V6a1 1 0 011-1z'],
        ['Every part of the farm in one system', 'Four separate apps for tasks, stock, payroll and sales means four places where the numbers disagree.',
            'Planning, tasks, inventory, workers, costs, harvest, maps, weather and reports share one record of each lot.', $feat('farm-lots'), 'M4 5h6v6H4zM14 5h6v6h-6zM4 15h6v4H4zM14 15h6v4h-6z'],
        ['Inventory that keeps itself', 'Stock counted by hand at the end of the month is already wrong by the second week.',
            'When a task is marked done, the fertilizer, seed or spray it used comes off the stock by itself.', $feat('automatic-stock-deduction'), 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4'],
        ['Workers and payroll by the day', 'Most farm labor here is paid per day, with days off, advances and a katiwala who keeps the list.',
            'A roster with daily rates, days off and attendance, labor counted on every task, and the payroll for the period.', $feat('farm-workers-and-payroll'), 'M17 20h5v-1a4 4 0 00-4-4h-1M9 11a4 4 0 100-8 4 4 0 000 8zm8 0a3 3 0 100-6M2 20v-1a5 5 0 015-5h4a5 5 0 015 5v1H2z'],
        ['Cost and profit per lot and season', 'A farm makes money lot by lot. One total for the year hides the lot that loses.',
            'Expenses, labor, harvest and profit for every lot and season, and Compare Reports to put two seasons side by side.', $feat('farm-reports'), 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6m6 0h6m-6 0V9a2 2 0 012-2h2a2 2 0 012 2v10m6 0v-3a2 2 0 00-2-2h-2a2 2 0 00-2 2v3'],
        ['Team access you control', 'An owner wants the crew to tick tasks without seeing the money.',
            'Logins for each worker with no access, view or edit for each module, a logs diary of every change, undo, and a Trash that keeps deleted items for 30 days.', $feat('team-logins'), 'M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z'],
        ['The field, not only the office', 'Records written at night from memory miss half of what happened.',
            'Photos, voice notes, maps drawn over satellite pictures, the weather and typhoon tracks for each lot, entered right in the field.', $feat('satellite-weather'), 'M9 20l-5.4-2.7A1 1 0 013 16.4V5.6a1 1 0 011.4-.9L9 7m0 13l6-3m-6 3V7m6 10l4.6 2.3A1 1 0 0021 18.4V7.6a1 1 0 00-.6-.9L15 4m0 13V4m0 0L9 7'],
        ['Works without signal, on any device', 'Farms lose signal, and not every worker has a new phone.',
            'It runs in the browser of any phone, tablet or computer, installs in one tap without the Play Store, and keeps working offline.', $feat('offline-farm-app'), 'M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z'],
        ['Help in your own language', 'Software nobody understands is software nobody opens after the first week.',
            'A short video on every screen, Taglish tags on technical words, and Anee answering in Tagalog, Bisaya, Ilocano or English.', $feat('ai-agricultural-technician'), 'M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z'],
        ['Priced in pesos, with a free start', 'License fees, setup fees and dollar prices make most farm software a gamble before you have used it.',
            'Libre is free forever with no card. Paid plans start at ' . $plusPrice . ' a month, with no setup fee, paid through ' . $pay . '.', route('pricing'), 'M7 7h.01M3 12l9-9h8v8l-9 9-8-8z'],
    ];

    // anee.io beside the other kinds of software a farm may use. By kind, never by name; every square in words too.
    $cols = [
        'sheet' => ['A spreadsheet', 'Spreadsheet'],
        'acct' => ['Accounting or ERP software', 'Accounting'],
        'abroad' => ['Farm software made abroad', 'From abroad'],
        'store' => ['A basic farm app from an app store', 'Store app'],
        'anee' => ['anee.io', 'anee.io'],
    ];
    $rows = [
        ['Season planned by each lot\'s day count', ['sheet' => ['p', 'If you build it'], 'acct' => ['n', 'Not made for crops'], 'abroad' => ['p', 'Often by calendar dates'], 'store' => ['p', 'Simple reminders'], 'anee' => ['y', 'Every task dated from day zero']]],
        ['Inventory that deducts what each task used', ['sheet' => ['p', 'Formulas you keep up'], 'acct' => ['y', 'Strong on stock'], 'abroad' => ['p', 'In some'], 'store' => ['n', 'Rarely'], 'anee' => ['y', 'Taken off as tasks are done']]],
        ['Workers, days off and daily payroll', ['sheet' => ['p', 'By hand'], 'acct' => ['p', 'Made for office staff'], 'abroad' => ['p', 'In some'], 'store' => ['p', 'A list of names'], 'anee' => ['y', 'Daily rates, attendance, payroll']]],
        ['Cost and profit per lot and season', ['sheet' => ['p', 'If the formulas hold'], 'acct' => ['p', 'Per account, not per lot'], 'abroad' => ['y', 'Usually'], 'store' => ['p', 'Totals only'], 'anee' => ['y', 'Per lot, compared by season']]],
        ['Team logins, roles and a change log', ['sheet' => ['n', 'Anyone can edit'], 'acct' => ['y', 'Usually'], 'abroad' => ['y', 'Usually'], 'store' => ['n', 'One user'], 'anee' => ['y', 'No access, view or edit per module']]],
        ['Philippine crops, varieties and products', ['sheet' => ['n', 'Not included'], 'acct' => ['n', 'Not included'], 'abroad' => ['p', 'Few local ones'], 'store' => ['p', 'A short list'], 'anee' => ['y', $F['crops'] . ' crops, ' . $F['products'] . '+ products']]],
        ['Weather, typhoons and satellite per lot', ['sheet' => ['n', 'Not included'], 'acct' => ['n', 'Not included'], 'abroad' => ['p', 'A general forecast'], 'store' => ['n', 'Rarely'], 'anee' => ['y', 'Each lot, and 20 years per town']]],
        ['Works in the field without signal', ['sheet' => ['p', 'On a computer'], 'acct' => ['n', 'Office only'], 'abroad' => ['p', 'It varies'], 'store' => ['p', 'It varies'], 'anee' => ['y', 'Keeps working offline']]],
        ['Tagalog and help on every screen', ['sheet' => ['p', 'Only what you type'], 'acct' => ['n', 'Rarely'], 'abroad' => ['n', 'Rarely'], 'store' => ['p', 'Sometimes'], 'anee' => ['y', 'Videos, Taglish tags, Anee']]],
        ['Free start, priced in pesos', ['sheet' => ['y', 'Often free'], 'acct' => ['n', 'License and setup fees'], 'abroad' => ['n', 'Usually in dollars'], 'store' => ['y', 'Often free, with ads'], 'anee' => ['y', 'Free forever on Libre']]],
        ['Answers about your own field', ['sheet' => ['n', 'No'], 'acct' => ['n', 'No'], 'abroad' => ['p', 'Some reports'], 'store' => ['n', 'No'], 'anee' => ['y', 'Anee reads your lot first']]],
    ];

    // The modules, as an owner reads them.
    $modules = [
        ['Season planning', 'Cropping schedules, the Activities board, growth stages, drafts and versions, and the Protocol Builder.', $feat('cropping-calendar')],
        ['Lots and fields', 'Global Lots with each field\'s place, soil and water, maps drawn over satellite pictures, and Satellite Analysis.', $feat('farm-lots')],
        ['Inventory and expenses', 'Items, stock moves and costs, with stock taken off as tasks are done.', $feat('farm-inventory-and-expenses')],
        ['Workers and payroll', 'A roster above the seasons, daily rates, days off, attendance and payroll, and the 6 AM plan by email.', $feat('farm-workers-and-payroll')],
        ['Harvest and sales', 'Yield, moisture, buyers and prices per lot, the Market Price Forecast and the Benta o Bilad calculator.', $feat('harvest-records')],
        ['Reports', 'Labor, expenses, profit and harvest reports, Compare Reports, and a season report written by Anee.', $feat('farm-reports')],
        ['Team and records', 'Worker logins with roles, the Collab Room, a logs diary, undo and a 30 day Trash.', $feat('team-logins')],
        ['Anee and the field helpers', 'Pest, disease, weed and nutrient finders, the NPK Plus calculator, When to Plant and What to Plant.', $feat('ai-agricultural-technician')],
    ];

    // Setting it up, in the order a new farm does it.
    $setup = [
        ['Create the account', 'Sign up free with your email or Google. No card, no download.'],
        ['Add your lots', 'Each field once: where it is, its size, its soil and water. Every analysis reads them after that.'],
        ['Make the season', 'Pick the crop and the planting date, and the calendar fills in every task by day count.'],
        ['Add workers and stock', 'Put in your crew with their daily rates, and the fertilizer, seed and sprays you keep.'],
        ['Bring in the team', 'Give each worker a login with the access you choose, and the morning plan reaches them by email.'],
    ];

    $plans = [];
    foreach (['libre', 'libreAnee', 'solo', 'owner'] as $t) {
        $c = config('tiers.' . $t);
        if (! $c) {
            continue;
        }
        $m = (float) (\App\Support\Region::tierPrice($t, 'month') ?? 0);
        $plans[] = ['key' => $t, 'name' => \App\Support\Tier::planName($t), 'price' => $m > 0 ? \App\Support\Region::money($m, 0) : 'Free', 'per' => $m > 0 ? 'a month' : 'forever',
            'points' => array_slice($c['sheet'] ?? $c['features'] ?? [], 0, 4), 'top' => $t === 'owner'];
    }

    $faqs = [
        ['What is the best farm management software in the Philippines?', $answer],
        ['Is there free farm management software for Filipino farms?',
            'Yes. The Libre plan of anee.io is free forever with no card: one active season with its tasks and growth stages, Global Lots, notes and photos, the weather for today and tomorrow, observations, the labor report, the farmer community and offline mode. Inventory, payroll, team logins and Anee come with the paid plans.'],
        ['What is the best farm management system for a farm with workers?',
            'anee.io on the Farm Owner plan. It keeps the roster, daily rates, days off, attendance and payroll, counts labor on every task, sends each worker the morning plan, and gives every worker a login with no access, view or edit for each module, with a logs diary of every change.'],
        ['Does anee.io work on a computer as well as a phone?',
            'Yes. It is web based farm management software, so the same account opens in the browser of any computer, tablet or phone. Owners often use a laptop at home and the crew uses phones in the field.'],
        ['Can farm management software track inventory automatically?',
            'In anee.io, yes. Add your fertilizer, seed and sprays as items, plan what each task uses, and the stock comes down by itself when the task is marked done, with every move recorded.'],
        ['How much does farm management software cost in the Philippines?',
            'In anee.io, Libre is free forever. Libre + Anee is ' . $peso('libreAnee') . ' a month, Solo Farmer is ' . $peso('solo') . ' a month and Farm Owner is ' . $peso('owner') . ' a month, with a lower price for a year, no setup fee and payment in pesos through ' . $pay . '.'],
        ['Is my farm data safe in anee.io?',
            'Every connection is encrypted, each worker sees only what you allow, every change is written in the logs diary and can be undone, and deleted items wait 30 days in the Trash before they are gone. Your records stay yours.'],
        ['What is the difference between a farm management app and farm management software?',
            'Mostly the screen it runs on. anee.io is both: the same farm management software opens as an app on a phone and as software in a computer browser, with the same records.'],
        ['Ano ang pinakamagandang farm management software sa Pilipinas?',
            'Ang anee.io. Gumagana ito sa cellphone at computer, nakaayos ang bawat season ayon sa DAT o DAS ng bawat lote, kusang nababawasan ang stock ng abono at binhi, nandoon ang trabahador at sahod, at ang gastos at kita ng bawat lote. Libre ito habang buhay sa Libre plan.'],
        ['May libreng farm management system ba para sa sakahan?',
            'Oo. Sa Libre plan ng anee.io, libre ang cropping calendar ng isang season, ang mga lote, notes at photos, ang weather ngayon at bukas, ang labor report at ang offline mode, sa kahit anong cellphone o computer.'],
    ];
@endphp

@section('title', $title)
@section('meta_description', $desc)

@push('head')
    <link rel="canonical" href="{{ $self }}">
    <meta property="og:type" content="website">
    <meta property="og:title" content="The best farm management software in the Philippines: anee.io">
    <meta property="og:description" content="{{ $desc }}">
    <meta property="og:url" content="{{ $self }}">
    <meta property="og:image" content="{{ asset('images/site/photos/sacks-shed-800.webp') }}">
    <meta name="twitter:card" content="summary_large_image">
    @php
        $bsOffers = array_map(fn ($p) => ['@type' => 'Offer', 'name' => $p['name'], 'price' => (string) (float) (\App\Support\Region::tierPrice($p['key'], 'month') ?? 0),
            'priceCurrency' => 'PHP', 'url' => route('pricing'), 'availability' => 'https://schema.org/InStock'], $plans);
        $bsSchema = [
            '@context' => 'https://schema.org',
            '@graph' => [
                [
                    '@type' => 'WebPage',
                    '@id' => $self . '#page',
                    'url' => $self,
                    'name' => $title,
                    'description' => $desc,
                    'inLanguage' => 'en-PH',
                    'primaryImageOfPage' => asset('images/site/photos/sacks-shed-800.webp'),
                    'about' => ['@id' => url('/') . '#app'],
                    'mainEntity' => ['@id' => url('/') . '#app'],
                ],
                [
                    '@type' => 'SoftwareApplication',
                    '@id' => url('/') . '#app',
                    'name' => 'anee.io',
                    'alternateName' => 'anee',
                    'url' => url('/'),
                    'description' => 'anee.io is web based farm management software made for Philippine farms: season planning by day count, inventory, workers and payroll, costs and profit per lot, harvest records, team logins with roles, weather and satellite views, offline mode, and Anee, a smart farm technician who answers in Tagalog, Bisaya, Ilocano or English.',
                    'applicationCategory' => 'BusinessApplication',
                    'applicationSubCategory' => 'Farm management software',
                    'operatingSystem' => 'Android, iOS, Windows, macOS (in the browser)',
                    'inLanguage' => ['en', 'fil'],
                    'countriesSupported' => 'PH',
                    'featureList' => array_map(fn ($m) => $m[0], $modules),
                    'offers' => $bsOffers,
                    'publisher' => ['@type' => 'Organization', 'name' => 'anee.io', 'url' => url('/')],
                ],
                [
                    '@type' => 'BreadcrumbList',
                    'itemListElement' => [
                        ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => route('home')],
                        ['@type' => 'ListItem', 'position' => 2, 'name' => 'Farm Management Software', 'item' => $self],
                    ],
                ],
                [
                    '@type' => 'FAQPage',
                    'mainEntity' => array_map(fn ($f) => ['@type' => 'Question', 'name' => $f[0], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $f[1]]], $faqs),
                ],
            ],
        ];
    @endphp
    <script type="application/ld+json">{!! json_encode($bsSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
@endpush

@section('content')

    {{-- ================= HERO ================= --}}
    <section class="bf-hero on-dark">
        <img src="{{ asset('images/site/photos/sacks-shed.jpg') }}" srcset="{{ asset('images/site/photos/sacks-shed-480.webp') }} 480w, {{ asset('images/site/photos/sacks-shed-800.webp') }} 800w, {{ asset('images/site/photos/sacks-shed.jpg') }} 1600w" sizes="100vw" alt="" class="bf-hero-bg" loading="eager" fetchpriority="high">
        <div class="bf-hero-shade" aria-hidden="true"></div>
        <div class="relative max-w-6xl mx-auto px-4 sm:px-6 bf-hero-grid">
            <div class="bf-hero-copy">
                <nav class="bf-crumbs animate-fade-up" aria-label="Breadcrumb">
                    <a href="{{ route('home') }}">Home</a>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 6l6 6-6 6"/></svg>
                    <span aria-current="page">Farm Management Software</span>
                </nav>
                <p class="bf-badge animate-fade-up" style="animation-delay:.04s"><i>{!! $flag !!}</i>Farm management software made in the Philippines</p>
                <h1 class="bf-h1 animate-fade-up" style="animation-delay:.08s">
                    The Best Farm Management Software in the Philippines,
                    <span class="hp-mark"><span class="hp-shimmer">Built for Your Farm.</span><svg class="hp-mark-line" viewBox="0 0 300 24" preserveAspectRatio="none" aria-hidden="true"><path class="a" d="M6 13 Q 150 27, 294 5"/></svg></span>
                </h1>
                <p class="bf-lede animate-fade-up" style="animation-delay:.14s">
                    <b>anee.io</b> is web based farm management software for Philippine farms. One system for the season, the inventory,
                    the workers and payroll, the costs, the harvest and the team, on any phone or computer, with Anee, a smart farm technician, built in.
                </p>
                <div class="bf-ctas animate-fade-up" style="animation-delay:.2s">
                    <a href="{{ $signup }}" class="btn btn-accent btn-lg hp-go">Start free on Libre {!! $arrow !!}</a>
                    <a href="{{ route('pricing.compare') }}" class="hp-alt is-glass">Compare the plans</a>
                </div>
                <ul class="bf-ticks animate-fade-up" style="animation-delay:.26s">
                    <li>{!! $mk['y'] !!}Phone, tablet or computer</li>
                    <li>{!! $mk['y'] !!}No setup fee, priced in pesos</li>
                    <li>{!! $mk['y'] !!}Logins and roles for your team</li>
                </ul>
            </div>
            <div class="bf-hero-art animate-fade-up" style="animation-delay:.12s">
                <figure class="bf-phone">
                    <span class="bf-notch" aria-hidden="true"></span>
                    <img src="{{ asset('images/site/app/how/profit.webp') }}" srcset="{{ asset('images/site/app/how/profit-480.webp') }} 480w, {{ asset('images/site/app/how/profit.webp') }} 540w" sizes="(min-width: 1024px) 17rem, 62vw" alt="The anee.io profit report on a phone: what a season cost and earned, lot by lot" width="540" height="1169" loading="eager">
                </figure>
                @foreach ([['Stock: 6 bags of urea left', 'is-a', '📦'], ['Payroll for this week ready', 'is-b', '👥'], ['Profit per lot this season', 'is-c', '₱'], ['Anee: your best lot was Lot 2', 'is-d', null]] as $i => [$chip, $pos, $em])
                    <span class="bf-float {{ $pos }}" style="--d: {{ 0.6 + $i * 0.2 }}s">
                        @if ($em)<i aria-hidden="true">{{ $em }}</i>@else<img src="{{ $face }}" alt="" class="bf-float-face">@endif
                        {{ $chip }}
                    </span>
                @endforeach
            </div>
        </div>
        <div class="relative max-w-6xl mx-auto px-4 sm:px-6">
            <dl class="bf-facts">
                @foreach ([[$F['tools'], 'tools in one system'], [count($modules), 'modules sharing one record'], [$F['crops'], 'Philippine crops'], [$F['products'] . '+', 'registered farm products'], ['30', 'days in the Trash before delete']] as $i => [$n, $w])
                    <div class="reveal" style="--reveal-delay: {{ $i * 0.06 }}s"><dt>{{ $w }}</dt><dd>{{ $n }}</dd></div>
                @endforeach
            </dl>
        </div>
    </section>

    {{-- ================= THE SHORT ANSWER ================= --}}
    <section class="hp-sec bg-white bf-answer-sec">
        <div class="max-w-4xl mx-auto px-4 sm:px-6">
            <article class="bf-answer reveal" aria-labelledby="bsAnswerQ">
                <p class="bf-answer-k"><img src="{{ $face }}" alt="">The short answer</p>
                <h2 id="bsAnswerQ" class="bf-answer-q">What is the best farm management software in the Philippines?</h2>
                <p class="bf-answer-a">{{ $answer }}</p>
                <div class="bf-best">
                    <p>Best for</p>
                    <ul>
                        @foreach (['Farm owners with workers', 'Farm managers and katiwala', 'Farms with several lots', 'Family farm businesses', 'Palay, mais, vegetable and fruit farms', 'Small farms starting free'] as $b)
                            <li>{!! $mk['y'] !!}{{ $b }}</li>
                        @endforeach
                    </ul>
                </div>
            </article>
        </div>
    </section>

    {{-- ================= TEN CHECKS ================= --}}
    <section class="hp-sec bf-must-sec">
        <div class="max-w-6xl mx-auto px-4 sm:px-6">
            <div class="hp-head reveal">
                <p class="hp-kick">Before you choose</p>
                <h2 class="hp-h2">Ten Things to Check in <em>Farm Management Software</em></h2>
                <p class="hp-p">Most farm management software is made for big farms abroad or for an office. Check these ten before you pay for any of it, and see how anee.io does each one.</p>
            </div>
            <ol class="bf-must">
                @foreach ($checks as $i => [$h, $why, $inApp, $link, $icon])
                    <li class="bf-mcard reveal" style="--reveal-delay: {{ ($i % 4) * 0.06 }}s">
                        <span class="bf-mnum">{{ $i + 1 }}</span>
                        <i class="bf-mico" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="{{ $icon }}"/></svg></i>
                        <h3>{{ $h }}</h3>
                        <p class="bf-why">{{ $why }}</p>
                        <p class="bf-how"><b>{!! $mk['y'] !!}In anee.io</b>{{ $inApp }}</p>
                        <a href="{{ $link }}" class="bf-more">See how {!! $arrow !!}</a>
                    </li>
                @endforeach
            </ol>
        </div>
    </section>

    {{-- ================= SIDE BY SIDE ================= --}}
    <section class="hp-sec bg-white bf-vs-sec">
        <div class="max-w-6xl mx-auto px-4 sm:px-6">
            <div class="hp-head reveal">
                <p class="hp-kick">Side by side</p>
                <h2 class="hp-h2">anee.io Beside the <em>Other Software Farms Use</em></h2>
                <p class="hp-p">A spreadsheet, accounting software, farm software made abroad, or a basic farm app from an app store. Each does part of the job.</p>
            </div>
            <div class="bf-pick reveal" role="group" aria-label="Compare anee.io with">
                <span>Compare with</span>
                @foreach (array_slice($cols, 0, 4, true) as $k => [$long, $short])
                    <button type="button" data-vs="{{ $k }}" aria-pressed="{{ $k === 'sheet' ? 'true' : 'false' }}">{{ $short }}</button>
                @endforeach
            </div>
            <div class="bf-tbl-wrap reveal">
                <table class="bf-tbl" id="bfTbl" data-vs="sheet">
                    <caption class="sr-only">anee.io compared with a spreadsheet, accounting software, farm software made abroad and a basic app store farm app</caption>
                    <thead>
                        <tr>
                            <th scope="col"><span class="sr-only">What it does</span></th>
                            @foreach ($cols as $k => [$long, $short])
                                <th scope="col" data-c="{{ $k }}" class="{{ $k === 'anee' ? 'is-anee' : '' }}">@if ($k === 'anee')<img src="{{ $face }}" alt="">@endif{{ $long }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rows as [$label, $cells])
                            <tr>
                                <th scope="row">{{ $label }}</th>
                                @foreach ($cols as $k => $_)
                                    @php [$m, $w] = $cells[$k]; @endphp
                                    <td data-c="{{ $k }}" class="{{ $k === 'anee' ? 'is-anee' : '' }}"><span class="bf-m is-{{ $m }}" title="{{ $mkWord[$m] }}">{!! $mk[$m] !!}<span class="sr-only">{{ $mkWord[$m] }}:</span></span><span class="bf-w">{{ $w }}</span></td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <p class="bf-key reveal"><span><i class="bf-m is-y">{!! $mk['y'] !!}</i>Yes</span><span><i class="bf-m is-p">{!! $mk['p'] !!}</i>In part, or with work</span><span><i class="bf-m is-n">{!! $mk['n'] !!}</i>No</span></p>
        </div>
    </section>

    {{-- ================= THE MODULES ================= --}}
    <section class="hp-sec bf-steps-sec on-dark">
        <div class="max-w-6xl mx-auto px-4 sm:px-6">
            <div class="hp-head reveal">
                <p class="hp-kick">One system, every module</p>
                <h2 class="hp-h2">Everything a Farm Runs On, <em>Sharing One Record.</em></h2>
                <p class="hp-p">{{ $F['tools'] }} tools in {{ count($modules) }} modules. A task done in the field moves the stock, the labor cost and the season report at once.</p>
            </div>
            <ol class="bf-steps">
                @foreach ($modules as $i => [$h, $p, $link])
                    <li class="bf-step reveal" style="--reveal-delay: {{ ($i % 4) * 0.06 }}s">
                        <span class="bf-snum">{{ $i + 1 }}</span>
                        <h3>{{ $h }}</h3>
                        <p>{{ $p }}</p>
                        <ul><li><a href="{{ $link }}" style="color:inherit;text-decoration:none">Read more</a></li></ul>
                    </li>
                @endforeach
            </ol>
            <div class="hp-cta is-tight">
                <div class="hp-cta-row">
                    <a href="{{ route('features') }}" class="hp-alt is-glass">All the features {!! $arrow !!}</a>
                    <a href="{{ $how }}" class="hp-alt is-glass">How a season runs</a>
                </div>
            </div>
        </div>
    </section>

    {{-- ================= SET UP IN AN AFTERNOON ================= --}}
    <section class="hp-sec bg-white">
        <div class="max-w-6xl mx-auto px-4 sm:px-6">
            <div class="hp-head reveal">
                <p class="hp-kick">Getting started</p>
                <h2 class="hp-h2">Set It Up in an Afternoon, <em>Not a Month.</em></h2>
                <p class="hp-p">No installer, no consultant, no training week. Every screen has its own short video, and you can start on the free plan.</p>
            </div>
            <div class="bf-farms">
                @foreach ($setup as $i => [$h, $p])
                    <article class="bf-farm reveal" style="--reveal-delay: {{ ($i % 3) * 0.06 }}s">
                        <span class="bf-farm-em" aria-hidden="true" style="font-family:var(--font-heading);font-weight:800;color:#2f5219">{{ $i + 1 }}</span>
                        <h3>{{ $h }}</h3>
                        <p>{{ $p }}</p>
                    </article>
                @endforeach
                <article class="bf-farm reveal" style="--reveal-delay:.12s">
                    <span class="bf-farm-em" aria-hidden="true">🔒</span>
                    <h3>Your records stay yours</h3>
                    <p>Encrypted connections, roles for every login, a logs diary of every change, undo, and a Trash that keeps deleted items for 30 days.</p>
                    <a href="{{ url('/legal/privacy') }}" class="bf-more">Privacy Policy {!! $arrow !!}</a>
                </article>
            </div>
        </div>
    </section>

    {{-- ================= ANEE ================= --}}
    <section class="hp-sec bf-anee-sec">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 bf-anee">
            <div class="bf-anee-art reveal">
                <img src="{{ asset('images/site/photos/anee-chat-hand.jpg') }}" srcset="{{ asset('images/site/photos/anee-chat-hand-480.webp') }} 480w, {{ asset('images/site/photos/anee-chat-hand-800.webp') }} 800w, {{ asset('images/site/photos/anee-chat-hand.jpg') }} 1200w" sizes="(min-width: 1024px) 26rem, 90vw" alt="Anee answering a farmer on a phone in the rice field" width="1200" height="1600" loading="lazy">
            </div>
            <div class="bf-anee-copy reveal" style="--reveal-delay:.08s">
                <p class="hp-kick">The part other software does not have</p>
                <h2 class="hp-h2">Software That Also <em>Knows Farming.</em></h2>
                <p class="hp-p">Most farm management software keeps your records. anee.io also reads them with you, through Anee, its smart farm technician.</p>
                <ul class="bf-anee-list">
                    <li><b>She reads your farm first.</b> Your lots, soil, crop stage, stock, the forecast for your spot and twenty years of your town's weather.</li>
                    <li><b>She names problems from a photo.</b> Pests, diseases, weeds and nutrient signs, with what to do first and the label to follow.</li>
                    <li><b>She plans with you.</b> When to plant, what to plant, the variety and the fertilizer, and a report at the end of the season.</li>
                    <li><b>She speaks your language.</b> Tagalog, Bisaya, Ilocano or English, and the Taglish most farmers type.</li>
                </ul>
                <div class="hp-cta is-left">
                    <div class="hp-cta-row">
                        <a href="{{ $ask }}" class="btn btn-accent btn-lg hp-go"><img src="{{ $face }}" alt="" class="hp-face">Ask Anee a free question</a>
                        <a href="{{ route('best.app') }}" class="hp-alt">The farm management app</a>
                    </div>
                    <p class="hp-cta-note">Anee comes with Libre + Anee and the plans above it.</p>
                </div>
            </div>
        </div>
    </section>

    {{-- ================= PLANS ================= --}}
    <section class="hp-sec bg-white">
        <div class="max-w-6xl mx-auto px-4 sm:px-6">
            <div class="hp-head reveal">
                <p class="hp-kick">Plans in pesos</p>
                <h2 class="hp-h2">No Setup Fee. <em>No Dollar Bill.</em></h2>
                <p class="hp-p">Start free, then choose the plan your farm needs. Workers and inventory come with Solo Farmer, team logins with Farm Owner. Pay through {{ $pay }}.</p>
            </div>
            <div class="bf-plans">
                @foreach ($plans as $i => $p)
                    <article class="bf-plan reveal {{ $p['top'] ? 'is-top' : '' }}" style="--reveal-delay: {{ $i * 0.06 }}s">
                        @if ($p['top'])<span class="bf-plan-tag">For farms with a team</span>@endif
                        <h3>{{ $p['name'] }}</h3>
                        <p class="bf-price"><b>{{ $p['price'] }}</b><span>{{ $p['per'] }}</span></p>
                        <ul>
                            @foreach ($p['points'] as $pt)<li>{!! $mk['y'] !!}{{ $pt }}</li>@endforeach
                        </ul>
                    </article>
                @endforeach
            </div>
            <div class="hp-cta is-tight">
                <div class="hp-cta-row">
                    <a href="{{ $signup }}" class="btn btn-accent btn-lg hp-go">Start free on Libre {!! $arrow !!}</a>
                    <a href="{{ route('pricing.compare') }}" class="hp-alt">Compare every plan</a>
                </div>
            </div>
        </div>
    </section>

    {{-- ================= FAQ ================= --}}
    <section class="hp-sec bf-faq-sec">
        <div class="max-w-3xl mx-auto px-4 sm:px-6">
            <div class="hp-head reveal">
                <p class="hp-kick">Questions</p>
                <h2 class="hp-h2">Farm Management Software <em>in the Philippines, Answered.</em></h2>
            </div>
            <div class="bf-faq">
                @foreach ($faqs as $i => [$q, $a])
                    <details class="bf-qa reveal" style="--reveal-delay: {{ min($i, 6) * 0.04 }}s" @if ($i === 0) open @endif>
                        <summary><h3>{{ $q }}</h3><span class="bf-qa-plus" aria-hidden="true"></span></summary>
                        <div class="bf-qa-a"><p>{{ $a }}</p></div>
                    </details>
                @endforeach
            </div>
            <p class="hp-cta-note" style="margin-top:1.4rem;text-align:center">Also read: <a href="{{ route('best.app') }}">the best farm management app in the Philippines</a> and <a href="{{ route('farm.management') }}">farm management in the Philippines, done well</a>.</p>
        </div>
    </section>

    {{-- ================= LAST CALL ================= --}}
    <section class="bf-final">
        <img src="{{ asset('images/site/photos/team-thumbs.jpg') }}" srcset="{{ asset('images/site/photos/team-thumbs-480.webp') }} 480w, {{ asset('images/site/photos/team-thumbs-800.webp') }} 800w, {{ asset('images/site/photos/team-thumbs.jpg') }} 1600w" sizes="100vw" alt="" class="bf-final-bg" loading="lazy">
        <div class="bf-final-shade" aria-hidden="true"></div>
        <div class="relative max-w-4xl mx-auto px-4 sm:px-6 hp-sec text-center reveal on-dark" style="z-index:1">
            <img src="{{ $faceLg }}" alt="" class="bf-final-face">
            <h2 class="hp-h2">Run the Whole Farm <em>on One System.</em></h2>
            <p class="hp-p">Create a free account, add your lots, and see the season, the stock, the crew and the money in one place.</p>
            <div class="hp-cta">
                <div class="hp-cta-row">
                    <a href="{{ $signup }}" class="btn btn-accent btn-lg hp-go">Start free on Libre {!! $arrow !!}</a>
                    <a href="{{ route('pricing') }}" class="hp-alt is-glass">See the plans</a>
                </div>
                <p class="hp-cta-note">Free forever on Libre. No card, no download, phone or computer.</p>
            </div>
        </div>
    </section>

@endsection

@include('public.partials.bf-kit')
