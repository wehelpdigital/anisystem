@extends('layouts.public')

@include('public.partials.site-css')
@include('public.partials.hp-base')

{{-- FARM MANAGEMENT IN THE PHILIPPINES (2026-11-24, the owner: "for best farm management software in ph or just best
     farm management in ph, anee is nowhere").

     The search "best farm management" asks how to manage a farm well as much as which tool to use, so this page answers
     the practice first and names the system second, unlike its siblings (best-farm-app, best-farm-software):
       1. the answer (hero and the short answer): the six jobs of farm management, done for every lot, in one system;
       2. the six jobs, each with the good practice and how anee.io carries it;
       3. a farm management plan in seven steps; the mistakes that cost Filipino farms the most;
       4. memory, notebooks and group chats beside a farm management system, by kind;
       5. Anee, the questions (FAQPage, two in Tagalog), the last call.
     Same rules: live counts, no ratings or user counts, plain words, no dashes, never "AI farm technician", no agency
     named, no wallet named. Styles: partials/bf-kit. --}}
@php
    $signup = route('signup');
    $ask = route('ask.page');
    $self = route('farm.management');
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
    $blog = fn (string $slug) => \App\Support\SitePages::url('blog', $slug);
    $F = $facts;
    $plusPrice = \App\Support\Region::money((float) (\App\Support\Region::tierPrice('libreAnee', 'month') ?? 0), 0);

    $title = 'Best Farm Management in the Philippines: How to Run a Farm Well';
    $desc = 'Best farm management in the Philippines: plan by day count, run tasks on time, pay fairly, count every peso per lot, guard against weather and sell well.';
    $answer = 'The best farm management in the Philippines comes down to six jobs done on time for every lot: plan the season by the crop\'s day count, '
        . 'run the work and the inputs on schedule, manage your people fairly, count every peso per lot, guard the crop against weather and pests, and sell at the right time and price. '
        . 'Farms that keep these six jobs in one system manage far better than farms that keep them in memory, notebooks and group chats. '
        . 'anee.io is the farm management system made for this in the Philippines: it dates every task from each lot\'s planting day, keeps the costs, workers, stock and harvest, '
        . 'reads the weather and typhoons for each lot, and comes with Anee, a smart farm technician who answers in Tagalog, Bisaya, Ilocano or English. It is free to start.';

    // The six jobs of farm management: the good practice, and how anee.io carries it.
    $jobs = [
        ['Plan the season', 'Choose the crop, the variety and the planting date for each lot from its soil, its water and the weather ahead. Set the budget before the first peso is spent.',
            'When to Plant and What to Plant Analysis, variety research, the Land Prep Planner and a cropping calendar with every task dated.', $feat('when-to-plant-analysis'), 'M8 7V3m8 4V3M4 11h16M5 5h14a1 1 0 011 1v13a1 1 0 01-1 1H5a1 1 0 01-1-1V6a1 1 0 011-1z'],
        ['Run the work on time', 'Every task on its right day, the inputs bought before they are needed, and what was done written down the same day.',
            'The Activities board and today\'s tasks for each lot, the morning plan by email, and stock taken off as each task is done.', $feat('daily-farm-tasks'), 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4'],
        ['Manage people fairly', 'Clear tasks, fair daily pay, a record of the days each person worked, and a fair share with the katiwala or tenant.',
            'Workers with daily rates, days off and payroll, logins with the access you choose, and the Hatian Calculator for the harvest share.', $feat('farm-workers-and-payroll'), 'M17 20h5v-1a4 4 0 00-4-4h-1M9 11a4 4 0 100-8 4 4 0 000 8zm8 0a3 3 0 100-6M2 20v-1a5 5 0 015-5h4a5 5 0 015 5v1H2z'],
        ['Count every peso', 'Keep farm money apart from household money, write down every expense, and know the cost per kilo and the profit of each lot.',
            'Expenses, labor and profit reports per lot and season, and Compare Reports to see which season and which lot paid best.', $feat('profit-report'), 'M12 8c-1.7 0-3 .9-3 2s1.3 2 3 2 3 .9 3 2-1.3 2-3 2m0-8c1.1 0 2.1.4 2.6 1M12 8V7m0 1v8m0 0v1m0-1c-1.1 0-2.1-.4-2.6-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
        ['Guard against weather and pests', 'Watch the field every week, plan around typhoons and dry spells, and keep records that support insurance and loans.',
            'Satellite Weather with typhoon tracks, your town\'s twenty years of weather, the pest and disease finders, and the Range Effect calendar.', $feat('satellite-weather'), 'M12 3l7 4v5c0 5-3.5 8-7 9-3.5-1-7-4-7-9V7l7-4z'],
        ['Sell at the right time', 'Know where the price is heading, decide whether to sell wet or dry the harvest first, and keep a list of buyers who pay fairly.',
            'The Market Price Forecast, the Benta o Bilad calculator, harvest records with buyers and prices, and the Contact List.', $feat('harvest-records'), 'M3 3v18h18M7 14l4-4 3 3 5-6'],
    ];

    // A farm management plan in seven steps.
    $plan = [
        ['Know your lots', 'Write down each field once: where it is, its size, its soil, its water and what grew there last.'],
        ['Choose the crop and variety', 'Pick what fits each lot and this season\'s weather, not only what you planted last year.'],
        ['Set the calendar', 'Date every task from the planting day: land preparation, fertilizer, water, spraying and harvest.'],
        ['Make the budget', 'Add up seed, fertilizer, labor, hired services and interest before you start, so the money lasts the season.'],
        ['Assign the people', 'Decide who does what and on which day, and how each one is paid.'],
        ['Watch and adjust every week', 'Walk the field, compare it with the plan, and move tasks when the rain or a pest says so.'],
        ['Review the season', 'Count the harvest and the profit per lot, then change one thing for the next season.'],
    ];

    // The mistakes that cost Filipino farms the most.
    $mistakes = [
        ['Planting by habit', 'The same date and variety every year, whatever the weather outlook says.', '📅'],
        ['No records', 'A season kept in memory cannot be compared with the next one.', '📝'],
        ['Farm and home money mixed', 'Nobody knows if the farm made money, only that the money ran out.', '💸'],
        ['Spraying by the calendar', 'Sprays on a fixed day kill the friendly insects and waste money.', '🧴'],
        ['Selling at the harvest peak', 'Everyone sells the same week, so the price is at its lowest.', '📉'],
        ['Paying labor by guess', 'Days worked are not written down, and arguments follow at payday.', '👥'],
    ];

    $cols = [
        'note' => ['Memory and a notebook', 'Notebook'],
        'sheet' => ['A spreadsheet', 'Spreadsheet'],
        'chat' => ['Group chats and a box of receipts', 'Group chats'],
        'anee' => ['anee.io', 'anee.io'],
    ];
    $rows = [
        ['Season planned by the crop\'s day count', ['note' => ['p', 'By hand'], 'sheet' => ['p', 'If you build it'], 'chat' => ['n', 'Lost in the thread'], 'anee' => ['y', 'Every task dated per lot']]],
        ['Reminders on the right day', ['note' => ['n', 'Only if you look'], 'sheet' => ['n', 'No'], 'chat' => ['p', 'If someone posts'], 'anee' => ['y', 'Today\'s tasks and the morning plan']]],
        ['Cost and profit per lot', ['note' => ['p', 'Long hand math'], 'sheet' => ['p', 'Formulas you keep up'], 'chat' => ['n', 'Receipts in a box'], 'anee' => ['y', 'Counted for you']]],
        ['Labor days and pay', ['note' => ['p', 'A page per payday'], 'sheet' => ['p', 'By hand'], 'chat' => ['p', 'Photos of lists'], 'anee' => ['y', 'Attendance and payroll']]],
        ['Stock of fertilizer and seed', ['note' => ['p', 'Counted now and then'], 'sheet' => ['p', 'If kept up'], 'chat' => ['n', 'Not kept'], 'anee' => ['y', 'Taken off as tasks are done']]],
        ['Weather, typhoons and pests', ['note' => ['n', 'Not included'], 'sheet' => ['n', 'Not included'], 'chat' => ['p', 'Shared news'], 'anee' => ['y', 'For each lot, with Anee']]],
        ['A history to learn from', ['note' => ['p', 'If the notebook survives'], 'sheet' => ['p', 'If saved'], 'chat' => ['n', 'Hard to find'], 'anee' => ['y', 'Seasons side by side']]],
        ['Shared with the owner and the crew', ['note' => ['n', 'One copy'], 'sheet' => ['p', 'Files sent around'], 'chat' => ['y', 'Everyone sees it'], 'anee' => ['y', 'Logins with roles']]],
    ];

    $faqs = [
        ['What is the best farm management in the Philippines?', $answer],
        ['What is farm management?',
            'Farm management is running a farm as a business: deciding what to plant and when, getting the work and the inputs done on time, managing the people, keeping the money straight, guarding against weather and pests, and selling well. Good farm management turns the same land, seed and fertilizer into a bigger and safer profit.'],
        ['What are the best farm management practices for Filipino farmers?',
            'Plan each lot\'s season by its day count, plant with your neighbors and on the best date for your town, feed by growth stage and by a soil test, scout every week, keep farm money apart from household money, write down every expense and every day worked, and review the profit per lot after each harvest. Our guide on [how to increase crop yield](' . $blog('how-to-increase-crop-yield') . ') covers the field side.'],
        ['What is a farm management system?',
            'A farm management system keeps the plan, the tasks, the inputs, the people, the money and the harvest of a farm in one place, so each decision is made from the same records. anee.io is a farm management system made for Philippine farms, on any phone or computer.'],
        ['How do I make a farm management plan?',
            'Know your lots, choose the crop and variety for each, date every task from the planting day, make the budget, assign the people, watch and adjust every week, and review the season at the end. In anee.io, the cropping calendar builds most of this from your lot and planting date.'],
        ['How do I compute my farm\'s profit?',
            'Add every cost of the season for a lot: seed, fertilizer, chemicals, labor, hired machines, irrigation and interest. Subtract it from what the harvest sold for. anee.io does this per lot and per season in its profit report. See also our guide on [farm profitability](' . $blog('farm-profitability') . ').'],
        ['Ano ang farm management sa Tagalog?',
            'Pamamahala ng bukid o sakahan. Ito ang pagpaplano kung ano at kailan magtatanim, paggawa ng bawat trabaho sa tamang araw, pag aasikaso ng mga trabahador, pagtatala ng bawat gastos at kita, pagbabantay sa panahon at peste, at pagbebenta sa tamang presyo.'],
        ['Paano pamahalaan nang maayos ang bukid?',
            'Planuhin ang bawat lote ayon sa DAT o DAS, gawin ang bawat trabaho sa tamang araw, itala ang gastos, sahod at ani, at alamin ang kita ng bawat lote pagkatapos ng anihan. Sa anee.io, nasa iisang lugar ang lahat ng ito, at si Anee ay sumasagot sa Tagalog. Libre itong simulan.'],
    ];
    // The FAQ answers may hold [label](url) links; the schema carries plain words.
    $plainQa = fn (string $s) => preg_replace('/\[([^\]]+)\]\([^)]+\)/', '$1', $s);
    $linkQa = fn (string $s) => preg_replace('/\[([^\]]+)\]\(([^)\s]+)\)/', '<a href="$2">$1</a>', e($s));
@endphp

@section('title', $title)
@section('meta_description', $desc)

@push('head')
    <link rel="canonical" href="{{ $self }}">
    <meta property="og:type" content="website">
    <meta property="og:title" content="Best farm management in the Philippines, made simple with anee.io">
    <meta property="og:description" content="{{ $desc }}">
    <meta property="og:url" content="{{ $self }}">
    <meta property="og:image" content="{{ asset('images/site/photos/palay-phone-800.webp') }}">
    <meta name="twitter:card" content="summary_large_image">
    @php
        $fmSchema = [
            '@context' => 'https://schema.org',
            '@graph' => [
                [
                    '@type' => 'WebPage',
                    '@id' => $self . '#page',
                    'url' => $self,
                    'name' => $title,
                    'description' => $desc,
                    'inLanguage' => 'en-PH',
                    'primaryImageOfPage' => asset('images/site/photos/palay-phone-800.webp'),
                    'about' => [['@type' => 'Thing', 'name' => 'Farm management in the Philippines'], ['@id' => url('/') . '#app']],
                    'mentions' => ['@id' => url('/') . '#app'],
                ],
                [
                    '@type' => 'SoftwareApplication',
                    '@id' => url('/') . '#app',
                    'name' => 'anee.io',
                    'url' => url('/'),
                    'description' => 'anee.io is a farm management system made for Philippine farms: season planning by day count, daily tasks, inventory, workers and payroll, costs and profit per lot, harvest and sales records, weather and typhoon tracking, and Anee, a smart farm technician who answers in Tagalog, Bisaya, Ilocano or English.',
                    'applicationCategory' => 'BusinessApplication',
                    'applicationSubCategory' => 'Farm management software',
                    'operatingSystem' => 'Android, iOS, Windows, macOS (in the browser)',
                    'countriesSupported' => 'PH',
                    'offers' => ['@type' => 'Offer', 'price' => '0', 'priceCurrency' => 'PHP', 'name' => 'Libre', 'url' => route('pricing')],
                ],
                [
                    '@type' => 'BreadcrumbList',
                    'itemListElement' => [
                        ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => route('home')],
                        ['@type' => 'ListItem', 'position' => 2, 'name' => 'Farm Management', 'item' => $self],
                    ],
                ],
                [
                    '@type' => 'FAQPage',
                    'mainEntity' => array_map(fn ($f) => ['@type' => 'Question', 'name' => $f[0], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $plainQa($f[1])]], $faqs),
                ],
            ],
        ];
    @endphp
    <script type="application/ld+json">{!! json_encode($fmSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
@endpush

@section('content')

    {{-- ================= HERO ================= --}}
    <section class="bf-hero on-dark">
        <img src="{{ asset('images/site/photos/palay-phone.jpg') }}" srcset="{{ asset('images/site/photos/palay-phone-480.webp') }} 480w, {{ asset('images/site/photos/palay-phone-800.webp') }} 800w, {{ asset('images/site/photos/palay-phone.jpg') }} 1600w" sizes="100vw" alt="" class="bf-hero-bg" loading="eager" fetchpriority="high">
        <div class="bf-hero-shade" aria-hidden="true"></div>
        <div class="relative max-w-6xl mx-auto px-4 sm:px-6 bf-hero-grid">
            <div class="bf-hero-copy">
                <nav class="bf-crumbs animate-fade-up" aria-label="Breadcrumb">
                    <a href="{{ route('home') }}">Home</a>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 6l6 6-6 6"/></svg>
                    <span aria-current="page">Farm Management</span>
                </nav>
                <p class="bf-badge animate-fade-up" style="animation-delay:.04s"><i>{!! $flag !!}</i>A guide for Filipino farms</p>
                <h1 class="bf-h1 animate-fade-up" style="animation-delay:.08s">
                    Best Farm Management in the Philippines:
                    <span class="hp-mark"><span class="hp-shimmer">Plan, Count, Share.</span><svg class="hp-mark-line" viewBox="0 0 300 24" preserveAspectRatio="none" aria-hidden="true"><path class="a" d="M6 13 Q 150 27, 294 5"/></svg></span>
                </h1>
                <p class="bf-lede animate-fade-up" style="animation-delay:.14s">
                    Good farm management turns the same land, seed and fertilizer into a bigger and safer profit. Here are the six jobs it takes,
                    the plan to follow, the mistakes to avoid, and <b>anee.io</b>, the farm management system that keeps it all in one place.
                </p>
                <div class="bf-ctas animate-fade-up" style="animation-delay:.2s">
                    <a href="{{ $signup }}" class="btn btn-accent btn-lg hp-go">Start free on Libre {!! $arrow !!}</a>
                    <a href="#fmJobs" class="hp-alt is-glass">Read the six jobs</a>
                </div>
                <ul class="bf-ticks animate-fade-up" style="animation-delay:.26s">
                    <li>{!! $mk['y'] !!}Made for palay, mais, gulay and fruit</li>
                    <li>{!! $mk['y'] !!}Every peso counted per lot</li>
                    <li>{!! $mk['y'] !!}Free to start</li>
                </ul>
            </div>
            <div class="bf-hero-art animate-fade-up" style="animation-delay:.12s">
                <figure class="bf-phone">
                    <span class="bf-notch" aria-hidden="true"></span>
                    <img src="{{ asset('images/site/app/how/season.webp') }}" srcset="{{ asset('images/site/app/how/season-480.webp') }} 480w, {{ asset('images/site/app/how/season.webp') }} 540w" sizes="(min-width: 1024px) 17rem, 62vw" alt="A cropping season in anee.io on a phone, every task dated from the planting day" width="540" height="1169" loading="eager">
                </figure>
                @foreach ([['Plan: DAT 21, top dress today', 'is-a', '🌾'], ['People: 4 workers, payroll set', 'is-b', '👥'], ['Money: profit per lot', 'is-c', '₱'], ['Anee: typhoon in 3 days, harvest Lot 1 first', 'is-d', null]] as $i => [$chip, $pos, $em])
                    <span class="bf-float {{ $pos }}" style="--d: {{ 0.6 + $i * 0.2 }}s">
                        @if ($em)<i aria-hidden="true">{{ $em }}</i>@else<img src="{{ $face }}" alt="" class="bf-float-face">@endif
                        {{ $chip }}
                    </span>
                @endforeach
            </div>
        </div>
        <div class="relative max-w-6xl mx-auto px-4 sm:px-6">
            <dl class="bf-facts">
                @foreach ([['6', 'jobs of farm management'], ['7', 'steps to a farm plan'], [$F['crops'], 'Philippine crops covered'], [$F['tools'], 'tools in one system'], ['20', 'years of weather per town']] as $i => [$n, $w])
                    <div class="reveal" style="--reveal-delay: {{ $i * 0.06 }}s"><dt>{{ $w }}</dt><dd>{{ $n }}</dd></div>
                @endforeach
            </dl>
        </div>
    </section>

    {{-- ================= THE SHORT ANSWER ================= --}}
    <section class="hp-sec bg-white bf-answer-sec">
        <div class="max-w-4xl mx-auto px-4 sm:px-6">
            <article class="bf-answer reveal" aria-labelledby="fmAnswerQ">
                <p class="bf-answer-k"><img src="{{ $face }}" alt="">The short answer</p>
                <h2 id="fmAnswerQ" class="bf-answer-q">What is the best farm management in the Philippines?</h2>
                <p class="bf-answer-a">{{ $answer }}</p>
                <div class="bf-best">
                    <p>The six jobs</p>
                    <ul>
                        @foreach ($jobs as [$h]) <li>{!! $mk['y'] !!}{{ $h }}</li> @endforeach
                    </ul>
                </div>
            </article>
        </div>
    </section>

    {{-- ================= THE SIX JOBS ================= --}}
    <section class="hp-sec bf-must-sec" id="fmJobs" style="scroll-margin-top:5rem">
        <div class="max-w-6xl mx-auto px-4 sm:px-6">
            <div class="hp-head reveal">
                <p class="hp-kick">The practice</p>
                <h2 class="hp-h2">The Six Jobs of <em>Good Farm Management</em></h2>
                <p class="hp-p">Every well run farm does these six, on every lot, every season. Here is the good practice for each, and how anee.io carries it for you.</p>
            </div>
            <ol class="bf-must" style="--cols:3">
                @foreach ($jobs as $i => [$h, $why, $inApp, $link, $icon])
                    <li class="bf-mcard reveal" style="--reveal-delay: {{ ($i % 3) * 0.06 }}s">
                        <span class="bf-mnum">{{ $i + 1 }}</span>
                        <i class="bf-mico" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="{{ $icon }}"/></svg></i>
                        <h3>{{ $h }}</h3>
                        <p class="bf-why"><b>Good practice:</b> {{ $why }}</p>
                        <p class="bf-how"><b>{!! $mk['y'] !!}In anee.io</b>{{ $inApp }}</p>
                        <a href="{{ $link }}" class="bf-more">See how {!! $arrow !!}</a>
                    </li>
                @endforeach
            </ol>
        </div>
    </section>

    {{-- ================= A FARM MANAGEMENT PLAN ================= --}}
    <section class="hp-sec bf-steps-sec on-dark">
        <div class="max-w-6xl mx-auto px-4 sm:px-6">
            <div class="hp-head reveal">
                <p class="hp-kick">Step by step</p>
                <h2 class="hp-h2">A Farm Management Plan <em>in Seven Steps.</em></h2>
                <p class="hp-p">The same plan works for a quarter hectare of gulay and for ten hectares of palay. Do it once, then improve it every season.</p>
            </div>
            <ol class="bf-steps">
                @foreach ($plan as $i => [$h, $p])
                    <li class="bf-step reveal" style="--reveal-delay: {{ ($i % 4) * 0.06 }}s">
                        <span class="bf-snum">{{ $i + 1 }}</span>
                        <h3>{{ $h }}</h3>
                        <p>{{ $p }}</p>
                    </li>
                @endforeach
                <li class="bf-step reveal" style="--reveal-delay:.18s">
                    <span class="bf-snum"><img src="{{ $face }}" alt="" style="width:100%;height:100%;border-radius:999px;object-fit:cover"></span>
                    <h3>Or let anee.io build it</h3>
                    <p>Add your lot and planting date, and the cropping calendar dates the tasks of the whole season. Anee checks the rest with you.</p>
                    <ul><li><a href="{{ $feat('cropping-calendar') }}" style="color:inherit;text-decoration:none">The cropping calendar</a></li></ul>
                </li>
            </ol>
        </div>
    </section>

    {{-- ================= THE MISTAKES ================= --}}
    <section class="hp-sec bg-white">
        <div class="max-w-6xl mx-auto px-4 sm:px-6">
            <div class="hp-head reveal">
                <p class="hp-kick">What to avoid</p>
                <h2 class="hp-h2">Farm Management Mistakes That <em>Cost Filipino Farms the Most</em></h2>
                <p class="hp-p">None of these needs more land or more money to fix. Each one needs a plan and a record.</p>
            </div>
            <div class="bf-farms">
                @foreach ($mistakes as $i => [$h, $p, $em])
                    <article class="bf-farm reveal" style="--reveal-delay: {{ ($i % 3) * 0.06 }}s">
                        <span class="bf-farm-em" aria-hidden="true">{{ $em }}</span>
                        <h3>{{ $h }}</h3>
                        <p>{{ $p }}</p>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ================= SIDE BY SIDE ================= --}}
    <section class="hp-sec bf-vs-sec" style="background:#f6faf1">
        <div class="max-w-6xl mx-auto px-4 sm:px-6">
            <div class="hp-head reveal">
                <p class="hp-kick">Side by side</p>
                <h2 class="hp-h2">Memory and Notebooks Beside <em>a Farm Management System</em></h2>
                <p class="hp-p">Most farms here are run from memory, a notebook, a spreadsheet or a group chat. Each keeps part of the farm.</p>
            </div>
            <div class="bf-pick reveal" role="group" aria-label="Compare anee.io with">
                <span>Compare with</span>
                @foreach (array_slice($cols, 0, 3, true) as $k => [$long, $short])
                    <button type="button" data-vs="{{ $k }}" aria-pressed="{{ $k === 'note' ? 'true' : 'false' }}">{{ $short }}</button>
                @endforeach
            </div>
            <div class="bf-tbl-wrap reveal">
                <table class="bf-tbl" id="bfTbl" data-vs="note">
                    <caption class="sr-only">A farm management system compared with memory and a notebook, a spreadsheet, and group chats</caption>
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

    {{-- ================= ANEE ================= --}}
    <section class="hp-sec bf-anee-sec">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 bf-anee">
            <div class="bf-anee-art reveal">
                <img src="{{ asset('images/site/photos/anee-chat-hand.jpg') }}" srcset="{{ asset('images/site/photos/anee-chat-hand-480.webp') }} 480w, {{ asset('images/site/photos/anee-chat-hand-800.webp') }} 800w, {{ asset('images/site/photos/anee-chat-hand.jpg') }} 1200w" sizes="(min-width: 1024px) 26rem, 90vw" alt="Anee answering a farmer on a phone in the rice field" width="1200" height="1600" loading="lazy">
            </div>
            <div class="bf-anee-copy reveal" style="--reveal-delay:.08s">
                <p class="hp-kick">A farm manager's second pair of eyes</p>
                <h2 class="hp-h2">Anee Helps You <em>Manage, Not Only Record.</em></h2>
                <p class="hp-p">A good farm manager asks the right questions at the right time. Anee, the smart farm technician in anee.io, asks them with you.</p>
                <ul class="bf-anee-list">
                    <li><b>Before planting:</b> which crop and variety fit this lot, and the safest week to plant in your town.</li>
                    <li><b>During the season:</b> what that spot on the leaf is, what to spray if anything, and whether the rain will wash it off.</li>
                    <li><b>Before selling:</b> where the price is heading, and whether to sell wet or dry the harvest first.</li>
                    <li><b>After the harvest:</b> what each lot cost and earned, and what to change next season.</li>
                </ul>
                <div class="hp-cta is-left">
                    <div class="hp-cta-row">
                        <a href="{{ $ask }}" class="btn btn-accent btn-lg hp-go"><img src="{{ $face }}" alt="" class="hp-face">Ask Anee a free question</a>
                        <a href="{{ route('best.software') }}" class="hp-alt">Farm management software</a>
                    </div>
                    <p class="hp-cta-note">Anee comes with Libre + Anee at {{ $plusPrice }} a month and the plans above it. Libre is free forever.</p>
                </div>
            </div>
        </div>
    </section>

    {{-- ================= FAQ ================= --}}
    <section class="hp-sec bf-faq-sec">
        <div class="max-w-3xl mx-auto px-4 sm:px-6">
            <div class="hp-head reveal">
                <p class="hp-kick">Questions</p>
                <h2 class="hp-h2">Farm Management <em>in the Philippines, Answered.</em></h2>
            </div>
            <div class="bf-faq">
                @foreach ($faqs as $i => [$q, $a])
                    <details class="bf-qa reveal" style="--reveal-delay: {{ min($i, 6) * 0.04 }}s" @if ($i === 0) open @endif>
                        <summary><h3>{{ $q }}</h3><span class="bf-qa-plus" aria-hidden="true"></span></summary>
                        <div class="bf-qa-a"><p>{!! $linkQa($a) !!}</p></div>
                    </details>
                @endforeach
            </div>
            <p class="hp-cta-note" style="margin-top:1.4rem;text-align:center">Also read: <a href="{{ route('best.app') }}">the best farm management app in the Philippines</a> and <a href="{{ route('best.software') }}">the best farm management software in the Philippines</a>.</p>
        </div>
    </section>

    {{-- ================= LAST CALL ================= --}}
    <section class="bf-final">
        <img src="{{ asset('images/site/photos/sacks.jpg') }}" srcset="{{ asset('images/site/photos/sacks-480.webp') }} 480w, {{ asset('images/site/photos/sacks-800.webp') }} 800w, {{ asset('images/site/photos/sacks.jpg') }} 1600w" sizes="100vw" alt="" class="bf-final-bg" loading="lazy">
        <div class="bf-final-shade" aria-hidden="true"></div>
        <div class="relative max-w-4xl mx-auto px-4 sm:px-6 hp-sec text-center reveal on-dark" style="z-index:1">
            <img src="{{ $faceLg }}" alt="" class="bf-final-face">
            <h2 class="hp-h2">Manage Your Next Season <em>on anee.io.</em></h2>
            <p class="hp-p">Create a free account, add your first lot, and see the plan, the work, the people and the money of your season in one place.</p>
            <div class="hp-cta">
                <div class="hp-cta-row">
                    <a href="{{ $signup }}" class="btn btn-accent btn-lg hp-go">Start free on Libre {!! $arrow !!}</a>
                    <a href="{{ route('how') }}" class="hp-alt is-glass">See how it works</a>
                </div>
                <p class="hp-cta-note">Free forever on Libre. No card, no download, any phone.</p>
            </div>
        </div>
    </section>

@endsection

@include('public.partials.bf-kit')
