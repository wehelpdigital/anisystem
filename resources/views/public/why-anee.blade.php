@extends('layouts.public')

@include('public.partials.site-css')
@include('public.partials.hp-base')

{{-- WHY ANEE (2026-10-22, the owner: "a new page under How It Works about why
     Anee is better ... than ai app like chatgpt, gemini ... we connect to the
     whole data, your crop and soil info, enso, historical data"). The case in
     the order a reader asks it: what is different (the data, not the model),
     what she reads (eight shelves around her), the same question answered
     both ways, why it matters on the farm, where she stops, and the FAQ.
     Every number is a count off the live shelves (2026-10-22), rounded down.
     Plain words, no dashes, never "AI farm technician". --}}
@php
    $ph = \App\Support\Region::ph();
    $signup = route('signup');
    $ask = route('ask.page');
    $face = asset('images/anee/avatar-160.jpg');
    $faceLg = asset('images/anee/avatar-512.jpg');
    $arrow = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5-5 5M6 12h12"/></svg>';
    $tick = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12.5l4.5 4.5L19 7.5"/></svg>';
    $dash = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true"><path stroke-linecap="round" d="M7 12h10"/></svg>';
    $feat = fn (string $slug) => url('/features/' . $slug);
    $title = 'AI in Agriculture: Why Anee Beats a General Chat App';
    $desc = 'AI in agriculture works best when it knows your farm. Anee reads your lots, soil, crop stage, ENSO and 20 years of local weather before she answers.';

    // What Anee reads, in the order the grid shows them (the hub sits after the fourth).
    $shelves = [
        ['Your lots and your season', 'The crop, variety, planting date and size of every lot, with the tasks, notes, harvests and photos on your board.', 'Your own records',
            'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4'],
        ['Your soil', 'What your field profile says, plus the usual fertility, pH and salinity of your province, town and barangay.', 'Down to the barangay',
            'M12 3l9 5-9 5-9-5 9-5zM3 12l9 5 9-5M3 16l9 5 9-5'],
        ['Your weather', 'The forecast for your own spot on the map, kept fresh every three hours, with the advisories that touch your area.', 'Fresh every 3 hours',
            'M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.001 4.001 0 003 15z'],
        ['The ENSO outlook', 'Whether El Niño or La Niña is on the way, checked about once a week, and what it means for rain this season.', 'Checked every week',
            'M3 7c2-3 4-3 6 0s4 3 6 0 4-3 6 0M3 12c2-3 4-3 6 0s4 3 6 0 4-3 6 0M3 17c2-3 4-3 6 0s4 3 6 0 4-3 6 0'],
        ['Twenty years of climate', 'Rain and heat for your spot, season by season, so a plan fits what your area really gets and not a national average.', '20 years per place',
            'M3 3v18h18M7 15l4-4 3 3 5-6'],
        ['Typhoons, floods and droughts', 'Every storm, flood and dry spell that hit your town since 2005, and the months they usually come.', 'Every town and city',
            'M13 10V3L4 14h7v7l9-11h-7z'],
        ['Varieties', 'Days to harvest, yield and resistance of the rice, corn, vegetable and fruit varieties planted in the Philippines.', 'Over 1,600 varieties',
            'M12 21v-9m0 0C12 7 8 5 4 5c0 4 3 7 8 7zm0 0c0-4 3-7 8-7 0 4-4 7-8 7z'],
        ['Products, pests and diseases', 'Fertilizers and pesticides registered here, Philippine companies first, and the pests, diseases, weeds and deficiencies of each crop.', 'Over 7,000 products',
            'M12 3l7 3v6c0 4.5-3 7.5-7 9-4-1.5-7-4.5-7-9V6l7-3zM9 12l2 2 4-4'],
    ];

    $faqs = [
        ['Is Anee better than ChatGPT for farming?',
            'For questions about your own farm, yes. Anee reads your lots, your soil, your crop stage, the forecast, the ENSO outlook and twenty years of your town\'s weather before she answers. A general chat app such as ChatGPT or Gemini only knows what you type and what it learned online, so its answer has to stay general. For writing a letter or a question outside farming, a general app works fine.'],
        ['What data does Anee use in her analysis?',
            'Your own lots and season (crop, variety, planting date, tasks, harvests and photos), your field profile and the usual soil of your barangay, the forecast for your spot, the ENSO outlook, twenty years of rain and heat, every typhoon, flood and drought in your town since 2005, the records of over 1,600 varieties, and over 7,000 fertilizers and pesticides registered in the Philippines.'],
        ['Does Anee answer in Tagalog?',
            'Yes. Anee answers in Tagalog, Bisaya, Ilocano or English, and she understands the Taglish most farmers type. Send her a photo of your crop and she reads that too.'],
        ['Does Anee work for crops other than rice?',
            'Yes. She covers palay, mais, vegetables, root crops and fruit trees, about 85 crops in all, each with its own growth stages, varieties, pests and diseases.'],
        ['Can I try Anee for free?',
            'Yes. You can ask Anee one question a week for free on the Ask Anee page, with no account. Inside the app, Anee comes with the Libre + Anee plan and the plans above it, and her analyses run on credits.'],
    ];
@endphp

@section('title', $title)
@section('meta_description', $desc)

@push('head')
    <link rel="canonical" href="{{ route('how.why') }}">
    <meta property="og:type" content="website">
    <meta property="og:title" content="Why Anee beats a general AI chat app on your farm">
    <meta property="og:description" content="{{ $desc }}">
    <meta property="og:url" content="{{ route('how.why') }}">
    <meta property="og:image" content="{{ asset('images/site/photos/anee-chat-hand.jpg') }}">
    <meta name="twitter:card" content="summary_large_image">
    @php
        $waSchema = [
            '@context' => 'https://schema.org',
            '@graph' => [
                [
                    '@type' => 'WebPage',
                    '@id' => route('how.why') . '#page',
                    'url' => route('how.why'),
                    'name' => $title,
                    'description' => $desc,
                    'inLanguage' => 'en-PH',
                    'primaryImageOfPage' => asset('images/site/photos/anee-chat-hand.jpg'),
                    'about' => ['@type' => 'SoftwareApplication', 'name' => 'anee.io', 'applicationCategory' => 'BusinessApplication', 'operatingSystem' => 'Any phone browser'],
                ],
                [
                    '@type' => 'BreadcrumbList',
                    'itemListElement' => [
                        ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => route('home')],
                        ['@type' => 'ListItem', 'position' => 2, 'name' => 'How It Works', 'item' => route('how')],
                        ['@type' => 'ListItem', 'position' => 3, 'name' => 'Why Anee', 'item' => route('how.why')],
                    ],
                ],
                [
                    '@type' => 'FAQPage',
                    'mainEntity' => array_map(fn ($f) => ['@type' => 'Question', 'name' => $f[0], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $f[1]]], $faqs),
                ],
            ],
        ];
    @endphp
    <script type="application/ld+json">{!! json_encode($waSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
@endpush

@section('content')

    {{-- ================= HERO ================= --}}
    <section class="wa-hero on-dark">
        <img src="{{ asset('images/site/fields-aerial.jpg') }}" srcset="{{ asset('images/site/fields-aerial-480.webp') }} 480w, {{ asset('images/site/fields-aerial-800.webp') }} 800w, {{ asset('images/site/fields-aerial.jpg') }} 1600w" sizes="100vw" alt="" class="wa-hero-bg" loading="eager" fetchpriority="high">
        <div class="wa-hero-shade" aria-hidden="true"></div>
        <div class="relative max-w-6xl mx-auto px-4 sm:px-6 wa-hero-grid">
            <div class="wa-hero-copy">
                <nav class="wa-crumbs animate-fade-up" aria-label="Breadcrumb">
                    <a href="{{ route('home') }}">Home</a>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 6l6 6-6 6"/></svg>
                    <a href="{{ route('how') }}">How It Works</a>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 6l6 6-6 6"/></svg>
                    <span aria-current="page">Why Anee</span>
                </nav>
                <h1 class="wa-h1 animate-fade-up" style="animation-delay:.06s">
                    AI in Agriculture That Reads
                    <span class="hp-mark"><span class="hp-shimmer">Your Farm First.</span><svg class="hp-mark-line" viewBox="0 0 300 24" preserveAspectRatio="none" aria-hidden="true"><path class="a" d="M6 13 Q 150 27, 294 5"/></svg></span>
                </h1>
                <p class="wa-lede animate-fade-up" style="animation-delay:.12s">
                    Most AI in agriculture starts from zero. ChatGPT and Gemini are clever, but they meet your farm for the first time with every question.
                    Anee, your smart farm technician, reads your lots, your soil, your crop's day count, the ENSO outlook and
                    twenty years of your town's weather before she gives you an answer.
                </p>
                <div class="wa-ctas animate-fade-up" style="animation-delay:.18s">
                    <a href="{{ $signup }}" class="btn btn-accent btn-lg hp-go">Create your free account {!! $arrow !!}</a>
                    <a href="{{ $ask }}" class="hp-alt is-glass"><img src="{{ $face }}" alt="" class="hp-face">Ask Anee a free question</a>
                </div>
                <ul class="wa-ticks animate-fade-up" style="animation-delay:.24s">
                    <li>Tagalog, Bisaya, Ilocano or English</li>
                    <li>Reads a photo of your crop</li>
                    <li>One free question every week</li>
                </ul>
            </div>
            <div class="wa-hero-art animate-fade-up" style="animation-delay:.1s">
                <figure class="wa-phone">
                    <img src="{{ asset('images/site/photos/anee-chat-hand.jpg') }}" srcset="{{ asset('images/site/photos/anee-chat-hand-480.webp') }} 480w, {{ asset('images/site/photos/anee-chat-hand-800.webp') }} 800w, {{ asset('images/site/photos/anee-chat-hand.jpg') }} 1200w" sizes="(min-width: 1024px) 22rem, 70vw" alt="Anee answering a farmer's photo of his palay on a phone in the rice field" width="1200" height="1600" loading="eager">
                </figure>
                @foreach ([['Your crop\'s day count', 'is-a'], ['Your area\'s soil', 'is-b'], ['ENSO outlook', 'is-c'], ['Typhoons since 2005', 'is-d']] as $i => [$chip, $pos])
                    <span class="wa-float {{ $pos }}" style="--d: {{ 0.5 + $i * 0.18 }}s"><i>{!! $tick !!}</i>{{ $chip }}</span>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ================= THE DIFFERENCE (2026-10-28, the owner: "make this part or section look better with animation, it
         looks bad now") =================
         The same kind of model on both sides; what flows into it is the difference. When the two engines come into view,
         each side's inputs pop in, the funnel under them draws itself, and the inputs flow down into the model: three
         thin, slow lines for a general chat app (and a fourth, your farm, that stops short), seven busy ones for Anee.
         Then out comes the answer. The flows run only while the section is on screen. --}}
    @php
        $waEngines = [
            ['kind' => 'plain', 'name' => 'A general AI chat app', 'sub' => 'ChatGPT, Gemini and the like',
                'in' => [['💬', 'Your question'], ['🌐', 'What it learned online'], ['🔎', 'A web search, sometimes']],
                'thin' => ['🌾', 'Your farm, only as you describe it'],
                // What it never sees, faded and struck through where Anee's column has its inputs (wide screens).
                'ghost' => [['🪨', 'Your soil'], ['🌦️', 'Your forecast'], ['📈', 'Your weather history'], ['📒', 'Your season records']],
                'out' => ['A general answer', 'You still work out what it means for your lot.']],
            ['kind' => 'anee', 'name' => 'Anee', 'sub' => 'Your smart farm technician',
                'in' => [['📷', 'Your question and photo'], ['🌾', 'Your lots, crop and variety'], ['🪨', 'Your field and your area\'s soil'],
                    ['🌦️', 'The forecast and ENSO outlook'], ['📈', '20 years of your town\'s weather'], ['🧪', 'Varieties and products sold here'],
                    ['📒', 'Your tasks, notes and harvests']],
                'thin' => null,
                'ghost' => [],
                'out' => ['An answer for your field', 'Your lot, your variety, this week\'s weather.']],
        ];
        // The funnel: one line from each input, spread across the top, down into the model.
        $waFunnel = function (int $n): array {
            $out = [];
            for ($i = 0; $i < $n; $i++) {
                $x = $n === 1 ? 150 : 24 + $i * (252 / ($n - 1));
                $out[] = sprintf('M%.1f 2C%.1f 42 150 34 150 74', $x, $x);
            }
            return $out;
        };
        // The model's icon: a bright, lit bulb for Anee (its rays pulse), a dull one for a general app (2026-10-29).
        $waBulbBright = '<svg class="wa-bulb is-bright" viewBox="0 0 32 32" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><g class="rays" stroke-width="1.9"><path d="M16 1.2v2.6M5.4 4.9l1.9 1.9M26.6 4.9l-1.9 1.9M1.6 13.3h2.7M27.7 13.3h2.7M4.6 21.4l2.2-1.3M27.4 21.4l-2.2-1.3"/></g><path class="glass" d="M16 6.5a6.5 6.5 0 00-3.9 11.7c.6.5 1 1.2 1 2V21h5.8v-.8c0-.8.4-1.5 1-2A6.5 6.5 0 0016 6.5z" stroke-width="1.6"/><path class="wire" d="M14 16.2l2 2 2-2" stroke-width="1.5"/><path d="M13.2 24h5.6M14.2 27h3.6" stroke-width="1.8"/></svg>';
        $waBulbDull = '<svg class="wa-bulb is-dull" viewBox="0 0 32 32" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path class="glass" d="M16 6.5a6.5 6.5 0 00-3.9 11.7c.6.5 1 1.2 1 2V21h5.8v-.8c0-.8.4-1.5 1-2A6.5 6.5 0 0016 6.5z" stroke-width="1.6"/><path class="wire" d="M14 16.2l2 2 2-2" stroke-width="1.5"/><path d="M13.2 24h5.6M14.2 27h3.6" stroke-width="1.8"/></svg>';
    @endphp
    <section class="hp-sec bg-white wa-diff-sec">
        <div class="max-w-6xl mx-auto px-4 sm:px-6">
            <div class="hp-head reveal">
                <p class="hp-kick">The real difference</p>
                <h2 class="hp-h2">The Model Is Not the Difference. <em>The Data Is.</em></h2>
                <p class="hp-p">
                    A general AI chat app knows a lot about farming in general. Ask it about your own field, though, and it has
                    only two things to work with: what it learned online and what you typed in the box. So its answer has to stay
                    general, and you still have to work out what it means for your lot.
                </p>
            </div>

            <div class="wa-eng" data-wa-eng>
                @foreach ($waEngines as $ei => $eng)
                    @if ($ei === 1)
                        {{-- No "same core model" line since 2026-10-29 (the owner): Anee's model is the smarter, highly trained one. --}}
                        <div class="wa-eng-eq" aria-hidden="true"><b>VS</b></div>
                    @endif
                    <div class="wa-eng-col is-{{ $eng['kind'] }}">
                        <div class="wa-eng-head">
                            @if ($eng['kind'] === 'anee')
                                <img src="{{ $face }}" alt="" width="80" height="80">
                            @else
                                <span class="wa-eng-ic" aria-hidden="true">💬</span>
                            @endif
                            <p><b>{{ $eng['name'] }}</b><small>{{ $eng['sub'] }}</small></p>
                        </div>
                        <div class="wa-eng-in">
                        @if ($eng['ghost'])
                            <p class="wa-eng-ghosts" aria-hidden="true">
                                @foreach ($eng['ghost'] as [$emo, $what])
                                    <span><i>{{ $emo }}</i>{{ $what }}</span>
                                @endforeach
                            </p>
                        @endif
                        <ul class="wa-eng-list" aria-label="What it reads before it answers">
                            @foreach ($eng['in'] as $ii => [$emo, $what])
                                <li style="--i: {{ $ii }}"><i aria-hidden="true">{{ $emo }}</i>{{ $what }}</li>
                            @endforeach
                            @if ($eng['thin'])
                                <li class="is-thin" style="--i: {{ count($eng['in']) }}"><i aria-hidden="true">{{ $eng['thin'][0] }}</i>{{ $eng['thin'][1] }}</li>
                            @endif
                        </ul>
                        </div>
                        @if ($eng['kind'] === 'anee')
                            {{-- Anee's side (2026-10-28, the owner: "make the lines animated and better looking ... to signify that it
                                 is smarter"): glowing gradient lines, a light running down each one into the model, the point where
                                 they meet pulsing. Played by SMIL, paused off screen (the script below). --}}
                            <svg class="wa-eng-fun is-smart" viewBox="0 0 300 80" aria-hidden="true" data-wa-smart>
                                <defs>
                                    <linearGradient id="waLineA" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#bef264"/><stop offset=".55" stop-color="#4a7c2a"/><stop offset="1" stop-color="#f5c518"/></linearGradient>
                                    <radialGradient id="waSparkA"><stop offset="0" stop-color="#fff"/><stop offset=".35" stop-color="#fde68a"/><stop offset="1" stop-color="#f5c518" stop-opacity="0"/></radialGradient>
                                    <filter id="waGlowA" x="-40%" y="-40%" width="180%" height="180%"><feGaussianBlur stdDeviation="2.4"/></filter>
                                </defs>
                                <g class="glow" filter="url(#waGlowA)">
                                    @foreach ($waFunnel(count($eng['in'])) as $d)<path d="{{ $d }}"/>@endforeach
                                </g>
                                @foreach ($waFunnel(count($eng['in'])) as $d)
                                    <path class="base" d="{{ $d }}" pathLength="100"/>
                                @endforeach
                                <g class="comets">
                                    @foreach ($waFunnel(count($eng['in'])) as $fi => $d)
                                        @foreach ([0, 0.8] as $lag)
                                            <g>
                                                <circle r="7" fill="url(#waSparkA)" opacity=".85"/>
                                                <circle r="2.1" fill="#fff"/>
                                                <animateMotion dur="1.6s" begin="-{{ round($fi * 0.19 + $lag, 2) }}s" repeatCount="indefinite" path="{{ $d }}" calcMode="spline" keyTimes="0;1" keyPoints="0;1" keySplines=".5 0 .7 1"/>
                                            </g>
                                        @endforeach
                                    @endforeach
                                </g>
                                <circle class="hub-glow" cx="150" cy="74" r="9" fill="url(#waSparkA)"><animate attributeName="r" values="7;13;7" dur="1.6s" repeatCount="indefinite"/><animate attributeName="opacity" values=".9;.35;.9" dur="1.6s" repeatCount="indefinite"/></circle>
                                <circle cx="150" cy="74" r="3" fill="#fff"/>
                            </svg>
                        @else
                            <svg class="wa-eng-fun" viewBox="0 0 300 76" aria-hidden="true">
                                @foreach ($waFunnel(count($eng['in'])) as $fi => $d)
                                    <path class="base" d="{{ $d }}" pathLength="100"/>
                                @endforeach
                                @if ($eng['thin'])
                                    <path class="thin" d="M288 2C288 30 236 34 212 46" pathLength="100"/>
                                @endif
                                @foreach ($waFunnel(count($eng['in'])) as $fi => $d)
                                    <path class="flow" d="{{ $d }}" pathLength="100" style="animation-delay: -{{ $fi * 0.9 }}s"/>
                                @endforeach
                            </svg>
                        @endif
                        @if ($eng['kind'] === 'anee')
                            <div class="wa-eng-model is-smart">
                                <i class="wa-spark is-a" aria-hidden="true">✦</i><i class="wa-spark is-b" aria-hidden="true">✦</i><i class="wa-spark is-c" aria-hidden="true">✦</i>
                                {!! $waBulbBright !!}
                                <p><b>A smarter AI model</b><small>Highly trained, and fed your whole farm</small></p>
                            </div>
                        @else
                            <div class="wa-eng-model">
                                {!! $waBulbDull !!}
                                <p><b>A language model</b><small>Knows farming in general</small></p>
                            </div>
                        @endif
                        <div class="wa-eng-out">
                            @if ($eng['kind'] === 'anee')
                                <img src="{{ $face }}" alt="" width="80" height="80">
                            @else
                                <span class="wa-eng-ic is-sm" aria-hidden="true">≈</span>
                            @endif
                            <p><b>{{ $eng['out'][0] }}</b><small>{{ $eng['out'][1] }}</small></p>
                        </div>
                    </div>
                @endforeach
            </div>

            <p class="hp-p wa-eng-foot reveal">
                Anee runs on a top language model too. The difference is what she reads before she answers. Each of her
                analyses, from <a href="{{ $feat('when-to-plant-analysis') }}" class="wa-link">When to Plant</a> to the
                <a href="{{ $feat('crop-protocol-analysis') }}" class="wa-link">Crop Protocol</a>, pulls in your season and the
                records we keep for every town in the Philippines. That is how AI in agriculture should work: the answer fits
                your field, your variety and this week's weather.
            </p>
        </div>
    </section>

    {{-- ================= WHAT SHE READS ================= --}}
    <section class="hp-sec wa-reads">
        <div class="max-w-6xl mx-auto px-4 sm:px-6">
            <div class="hp-head reveal">
                <p class="hp-kick">Connected to the whole farm</p>
                <h2 class="hp-h2">What Anee Reads <em>Before She Answers.</em></h2>
                <p class="hp-p">Eight shelves of data sit behind every analysis. Some of it is yours and grows as you farm. The rest we built and keep fresh for the whole country.</p>
            </div>
            <div class="wa-map">
                <svg class="wa-wires" viewBox="0 0 300 300" preserveAspectRatio="none" aria-hidden="true">
                    @foreach ([[50, 50], [150, 50], [250, 50], [50, 150], [250, 150], [50, 250], [150, 250], [250, 250]] as $i => [$x, $y])
                        <path d="M{{ $x }} {{ $y }} L150 150" pathLength="1" style="--w: {{ $i * 0.35 }}s"/>
                    @endforeach
                </svg>
                @foreach ($shelves as $i => [$name, $line, $stat, $ico])
                    @if ($i === 4)
                        <div class="wa-hub reveal">
                            <span class="wa-hub-face"><img src="{{ $faceLg }}" alt="Anee, the smart farm technician of anee.io" loading="lazy"></span>
                            <b>Anee</b>
                            <span>Reads all eight, then answers in your own words.</span>
                        </div>
                    @endif
                    <article class="wa-shelf reveal" style="--reveal-delay: {{ ($i % 4) * 0.06 }}s">
                        <span class="wa-shelf-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $ico }}"/></svg></span>
                        <h3>{{ $name }}</h3>
                        <p>{{ $line }}</p>
                        <span class="wa-stat">{{ $stat }}</span>
                    </article>
                @endforeach
            </div>
            <p class="wa-note reveal">
                The weather shelves are why her timing holds up. Read our guides on
                <a href="{{ url('/blog/el-nino-la-nina-farming-tips') }}" class="wa-link">farming through El Niño and La Niña</a> and on
                <a href="{{ url('/blog/track-typhoons-near-my-farm') }}" class="wa-link">tracking typhoons near your farm</a>, then see the
                <a href="{{ url('/rice-varieties') }}" class="wa-link">rice varieties</a> and
                <a href="{{ url('/crop-varieties') }}" class="wa-link">crop varieties</a> she knows by name.
            </p>
        </div>
    </section>

    {{-- ================= A REAL POST: TWENTY COMMENTS, SEVEN ANSWERS (2026-10-28, the owner) ================= --}}
    @include('public.partials.social-guess')

    {{-- ================= ONE QUESTION, TWO ANSWERS ================= --}}
    <section class="hp-sec bg-white">
        <div class="max-w-6xl mx-auto px-4 sm:px-6">
            <div class="hp-head reveal">
                <p class="hp-kick">See the difference</p>
                <h2 class="hp-h2">One Question, <em>Two Answers.</em></h2>
                <p class="hp-p">The same question, asked both ways. The words are an example, but the gap between them is real.</p>
            </div>
            <div class="wa-ask reveal">
                <span class="wa-q">Kailan ko lalagyan ulit ng abono ang palay ko?<small>When do I put fertilizer on my palay again?</small></span>
            </div>
            <div class="wa-chats">
                <span class="wa-vsb" aria-hidden="true">VS</span>
                <div class="wa-chat is-plain reveal" style="--reveal-delay:.06s">
                    <p class="wa-chat-who"><span class="wa-chat-dot" aria-hidden="true"></span>A general AI chat app</p>
                    <p class="wa-chat-a">For rice, the second fertilizer is usually put in at active tillering, about 20 to 30 days after transplanting. The right time depends on your variety, your soil and the weather, so check with your local agriculturist.</p>
                    <div class="wa-read is-miss">
                        <span class="wa-read-k">What it could not read</span>
                        @foreach (['Your crop\'s day count', 'The forecast for your farm', 'Your area\'s soil', 'Your season plan'] as $i => $r)
                            <span class="wa-read-chip" style="--i: {{ $i }}"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.8" aria-hidden="true"><path stroke-linecap="round" d="M7 7l10 10M17 7L7 17"/></svg>{{ $r }}</span>
                        @endforeach
                    </div>
                    <p class="wa-chat-verdict">Correct, but general. You still have to work out your own date.</p>
                </div>
                <div class="wa-chat is-anee reveal" style="--reveal-delay:.14s">
                    <p class="wa-chat-who"><img src="{{ $face }}" alt="">Anee</p>
                    <p class="wa-chat-a">Your palay is at active tillering now, so the second dose of urea falls this week. Rain is due on Thursday afternoon, so put it in on Wednesday morning while the field has a thin layer of water. The soil in your area runs low in nitrogen, so keep the full amount in your plan.</p>
                    <div class="wa-read">
                        <span class="wa-read-k">What she read</span>
                        @foreach (['Your crop\'s day count', 'The forecast for your farm', 'Your area\'s soil', 'Your season plan'] as $i => $r)
                            <span class="wa-read-chip" style="--i: {{ $i }}">{!! $tick !!}{{ $r }}</span>
                        @endforeach
                    </div>
                    <p class="wa-chat-win">Your date, your field: Wednesday morning, before the rain.</p>
                </div>
            </div>
        </div>
    </section>

    {{-- ================= WHY IT MATTERS ================= --}}
    <section class="hp-sec wa-matters on-dark">
        <div class="relative max-w-6xl mx-auto px-4 sm:px-6 wa-matters-grid" style="z-index:1">
            <div class="reveal">
                <p class="hp-kick">Built for decisions</p>
                <h2 class="hp-h2">Answers You Can <em>Act On Today.</em></h2>
                <p class="hp-p">
                    An answer only helps when it turns into work in the field. So Anee's answers agree with each other, get
                    cheaper as she learns, and land in your plan as dated tasks.
                </p>
                <div class="wa-inline-cta">
                    <a href="{{ $signup }}" class="btn btn-accent btn-lg hp-go">Start free {!! $arrow !!}</a>
                    <a href="{{ $feat('ai-agricultural-technician') }}" class="wa-textlink">Everything Anee can do {!! $arrow !!}</a>
                </div>
            </div>
            <ul class="wa-pillars">
                <li class="wa-pillar reveal" style="--reveal-delay:.1s">
                    <span class="wa-pillar-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M4 12h16M4 17h10"/></svg></span>
                    <span>
                        <h3>One lot, one set of facts</h3>
                        <p>When to Plant, the Crop Protocol, the <a href="{{ $feat('npk-plus-calculator') }}">NPK Plus calculator</a> and <a href="{{ $feat('satellite-analysis') }}">Satellite Analysis</a> read the same shelves, so they agree about your lot.</p>
                    </span>
                </li>
                <li class="wa-pillar reveal" style="--reveal-delay:.2s">
                    <span class="wa-pillar-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/></svg></span>
                    <span>
                        <h3>She keeps what she learns</h3>
                        <p>A variety or product Anee had to look up online is kept, so the next answer comes faster and costs fewer credits.</p>
                    </span>
                </li>
                <li class="wa-pillar reveal" style="--reveal-delay:.3s">
                    <span class="wa-pillar-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg></span>
                    <span>
                        <h3>Answers that land in your plan</h3>
                        <p>Her plans go straight into your cropping schedule as dated tasks, and Anee Auto can run the whole season and adjust it when the weather turns.</p>
                    </span>
                </li>
            </ul>
        </div>
    </section>

    {{-- ================= WHERE SHE STOPS ================= --}}
    <section class="hp-sec bg-white">
        {{-- Honest limits, as four cards (2026-10-28, the owner: "make this page look better"). --}}
        <div class="max-w-6xl mx-auto px-4 sm:px-6">
            <div class="hp-head reveal">
                <p class="hp-kick">Honest limits</p>
                <h2 class="hp-h2">Where Anee Stops <em>and You Decide.</em></h2>
                <p class="hp-p">She is a smart farm technician, not a fortune teller. Here is what she does when she is unsure, and what stays yours.</p>
            </div>
            <div class="wa-limits">
                @foreach ([
                    ['M8.2 9a3.8 3.8 0 117.3 1.4c-.6 1.4-2.3 2-3 3.1-.2.3-.3.7-.3 1.2M12 18h.01', 'She says when she is not sure', 'Every answer shows how sure she is and what she read to reach it, so you can check her reasons yourself.'],
                    ['M9 3v6.5L4.6 17a2 2 0 001.7 3h11.4a2 2 0 001.7-3L15 9.5V3M8 3h8M7.5 14h9', 'Not a soil laboratory', 'She reads your answers and your area\'s usual soil. A laboratory soil test is still the surest, and she tells you when one is worth it.'],
                    ['M9 12l2 2 4-4M12 3l7 3v6c0 4.5-3 7.5-7 9-4-1.5-7-4.5-7-9V6l7-3z', 'The label comes first', 'Every pesticide she names comes with the reminder to follow its label and its FPA registration.'],
                    ['M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5', 'You make the call', 'She advises and you decide. In Anee Auto you can change any task she plans, any day.'],
                ] as $i => [$d, $t, $w])
                    <div class="wa-limit reveal" style="--reveal-delay: {{ .06 + $i * .08 }}s">
                        <span class="wa-limit-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $d }}"/></svg></span>
                        <b>{{ $t }}</b><p>{{ $w }}</p>
                    </div>
                @endforeach
            </div>
            <div class="wa-limit-foot reveal">
                <p>General chat apps are still handy for writing a letter or explaining a word. For decisions on your own field, use the one that reads your field.</p>
                <div class="wa-limit-links">
                    <a href="{{ url('/blog/ai-farm-advice-accuracy') }}">How accurate is AI farm advice? {!! $arrow !!}</a>
                    <a href="{{ route('pricing') }}">Plans that include Anee {!! $arrow !!}</a>
                    <a href="{{ route('how') }}">The whole season, step by step {!! $arrow !!}</a>
                </div>
            </div>
        </div>
    </section>

    {{-- ================= FAQ ================= --}}
    <section class="hp-sec wa-faq-sec">
        <div class="max-w-3xl mx-auto px-4 sm:px-6">
            <div class="hp-head reveal">
                <p class="hp-kick">Questions</p>
                <h2 class="hp-h2">AI in Agriculture, <em>Answered.</em></h2>
            </div>
            <div class="wa-faq">
                @foreach ($faqs as $i => [$q, $a])
                    <details class="wa-qa reveal" style="--reveal-delay: {{ $i * 0.05 }}s" @if ($i === 0) open @endif>
                        <summary><h3>{{ $q }}</h3><span class="wa-qa-plus" aria-hidden="true"></span></summary>
                        <div class="wa-qa-a"><p>{{ $a }}</p></div>
                    </details>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ================= LAST CALL ================= --}}
    <section class="wa-final">
        <img src="{{ asset('images/site/photos/palay-heads.jpg') }}" srcset="{{ asset('images/site/photos/palay-heads-480.webp') }} 480w, {{ asset('images/site/photos/palay-heads-800.webp') }} 800w, {{ asset('images/site/photos/palay-heads.jpg') }} 1600w" sizes="100vw" alt="" class="wa-final-bg" loading="lazy">
        <div class="wa-final-shade" aria-hidden="true"></div>
        <div class="relative max-w-4xl mx-auto px-4 sm:px-6 hp-sec text-center reveal on-dark" style="z-index:1">
            <img src="{{ $faceLg }}" alt="" class="wa-final-face">
            <h2 class="hp-h2">Farm With an AI That <em>Knows Your Field.</em></h2>
            <p class="hp-p">Create a free account, add your first lot, and let Anee read it before she answers.</p>
            <div class="hp-cta">
                <div class="hp-cta-row">
                    <a href="{{ $signup }}" class="btn btn-accent btn-lg hp-go">Create your free account {!! $arrow !!}</a>
                    <a href="{{ $ask }}" class="hp-alt is-glass"><img src="{{ $face }}" alt="" class="hp-face">Ask Anee a free question</a>
                </div>
                <p class="hp-cta-note">Free forever on Libre. Anee comes with Libre + Anee and the plans above it.</p>
            </div>
        </div>
    </section>

@endsection

@push('head')
<style>
    /* ===================== WHY ANEE (2026-10-22) ===================== */
    .wa-link { font-weight: 800; color: var(--hp-green); text-decoration: underline; text-decoration-color: rgb(74 124 42 / .35); text-underline-offset: 3px;
        transition: text-decoration-color .28s var(--hp-ease); }
    .wa-link:hover { text-decoration-color: var(--hp-green); }
    .wa-textlink { display: inline-flex; align-items: center; gap: .4rem; font-weight: 800; color: var(--hp-sun); text-decoration: none; }
    .wa-textlink svg { width: 1.05rem; height: 1.05rem; flex: none; transition: transform .28s var(--hp-ease); }
    .wa-textlink:hover svg { transform: translateX(4px); }

    /* ---- hero: the farm from above, shaded deep green ---- */
    .wa-hero { position: relative; isolation: isolate; overflow: hidden; color: #fff; padding: 2.5rem 0 3.5rem; }
    @media (min-width: 1024px) { .wa-hero { padding: 3.5rem 0 4.5rem; } }
    .wa-hero-bg { position: absolute; inset: 0; z-index: -2; width: 100%; height: 100%; object-fit: cover; transform: scale(1.06);
        animation: waDrift 26s ease-in-out infinite alternate; }
    @keyframes waDrift { to { transform: scale(1.12) translate(-1.5%, -1%); } }
    .wa-hero-shade { position: absolute; inset: 0; z-index: -1;
        background: radial-gradient(60rem 30rem at 85% 20%, rgb(245 197 24 / .14), transparent 60%),
            linear-gradient(105deg, rgb(10 20 6 / .94) 0%, rgb(16 32 9 / .88) 45%, rgb(24 46 12 / .7) 100%); }
    .wa-hero-grid { display: grid; gap: 2.75rem; align-items: center; }
    @media (min-width: 1024px) { .wa-hero-grid { grid-template-columns: minmax(0, 1.2fr) minmax(0, .8fr); gap: 3rem; } }
    .wa-crumbs { display: flex; flex-wrap: wrap; align-items: center; gap: .35rem; font-size: .8rem; font-weight: 700; color: rgb(255 255 255 / .7); }
    .wa-crumbs a { color: rgb(255 255 255 / .82); text-decoration: none; transition: color .28s var(--hp-ease); }
    .wa-crumbs a:hover { color: var(--hp-sun); }
    .wa-crumbs svg { width: .85rem; height: .85rem; opacity: .6; }
    .wa-crumbs span { color: var(--hp-sun); }
    .wa-h1 { margin-top: 1.1rem; font-family: var(--font-heading); font-weight: 800; letter-spacing: -.025em; line-height: 1.06; color: #fff;
        font-size: clamp(2.2rem, 5.4vw, 3.8rem); text-wrap: balance; }
    .wa-lede { margin-top: 1.5rem; max-width: 36rem; font-size: clamp(1.02rem, 1.6vw, 1.15rem); line-height: 1.7; color: #d6e2ca; text-wrap: pretty; }
    .wa-ctas { margin-top: 1.9rem; display: flex; flex-wrap: wrap; gap: .75rem; }
    @media (max-width: 639.98px) { .wa-ctas > * { width: 100%; } }
    .wa-ticks { margin-top: 1.4rem; display: flex; flex-wrap: wrap; gap: .45rem 1.2rem; font-size: .88rem; font-weight: 700; color: #dbe7cd; }
    .wa-ticks li { display: inline-flex; align-items: center; gap: .4rem; }
    .wa-ticks li::before { content: ''; width: .55rem; height: .55rem; border-radius: 999px; background: var(--hp-sun); box-shadow: 0 0 0 3px rgb(245 197 24 / .25); }

    .wa-hero-art { position: relative; width: min(100%, 22rem); margin: 0 auto; padding: 0 1.5rem; }
    .wa-phone { margin: 0; overflow: hidden; border-radius: 1.8rem; aspect-ratio: 3 / 4; background: #24361a;
        box-shadow: 0 40px 80px -40px rgb(0 0 0 / .9), 0 0 0 6px rgb(255 255 255 / .08); transform: rotate(2deg); }
    .wa-phone img { width: 100%; height: 100%; object-fit: cover; display: block; }
    .wa-float { position: absolute; display: inline-flex; align-items: center; gap: .45rem; padding: .5rem .8rem .5rem .5rem; border-radius: 999px;
        font-size: .8rem; font-weight: 800; color: var(--hp-ink); background: #fff; white-space: nowrap;
        box-shadow: 0 18px 34px -16px rgb(0 0 0 / .65);
        opacity: 0; animation: waPop .6s var(--hp-ease) var(--d) forwards, waBob 6s ease-in-out calc(var(--d) + .6s) infinite; }
    .wa-float i { display: grid; place-items: center; width: 1.45rem; height: 1.45rem; border-radius: 999px; background: #e3f0d3; color: var(--hp-green); }
    .wa-float i svg { width: .9rem; height: .9rem; }
    .wa-float.is-a { top: 8%; left: -.5rem; }
    .wa-float.is-b { top: 30%; right: -.75rem; }
    .wa-float.is-c { top: 58%; left: -1rem; }
    .wa-float.is-d { bottom: 8%; right: -.25rem; }
    @keyframes waPop { from { opacity: 0; transform: translateY(10px) scale(.92); } to { opacity: 1; transform: none; } }
    @keyframes waBob { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-6px); } }

    /* ---- the difference: the same kind of model, fed two ways (2026-10-28) ----
       Two engines side by side, their rows lined up on a subgrid (heads, inputs, funnel, model, answer), the = between
       the two models. Stacked on a phone, the = between them. */
    .wa-eng { margin: 2.75rem auto 0; max-width: 62rem; display: grid; gap: 1rem; }
    .wa-eng-col { position: relative; display: flex; flex-direction: column; padding: 1.3rem 1.15rem 1.35rem; border-radius: 1.6rem; }
    @media (min-width: 768px) {
        .wa-eng { grid-template-columns: minmax(0, 1fr) 5.75rem minmax(0, 1fr); grid-template-rows: repeat(5, auto); gap: 0 .35rem; }
        .wa-eng-col { grid-row: 1 / span 5; display: grid; grid-template-rows: subgrid; padding: 1.5rem 1.4rem 1.6rem; }
        .wa-eng-col.is-plain { grid-column: 1; }
        .wa-eng-col.is-anee { grid-column: 3; }
        .wa-eng-eq { grid-column: 2; grid-row: 4; flex-direction: column; }
    }
    .wa-eng-col.is-plain { background: #f6f7f4; border: 1px solid #e5e7e1; }
    .wa-eng-col.is-anee { background: radial-gradient(28rem 16rem at 50% 0%, #fdf8e1, transparent 70%), linear-gradient(175deg, #f3f9ea, #e2f0cf);
        border: 1.5px solid #c3dca5; box-shadow: 0 34px 64px -42px rgb(47 82 25 / .75); }
    .wa-eng-head { display: flex; align-items: center; gap: .65rem; }
    .wa-eng-head img { flex: none; width: 2.5rem; height: 2.5rem; border-radius: 999px; object-fit: cover; box-shadow: 0 0 0 2px #fff, 0 0 0 4px var(--hp-sun); }
    .wa-eng-ic { flex: none; display: grid; place-items: center; width: 2.5rem; height: 2.5rem; border-radius: 999px; font-size: 1.15rem; background: #e9ebe6; }
    .wa-eng-ic.is-sm { width: 2.1rem; height: 2.1rem; font-size: 1.1rem; font-weight: 900; color: #6b7280; }
    .wa-eng-head b { display: block; font-family: var(--font-heading); font-size: 1.12rem; font-weight: 800; color: var(--hp-ink); line-height: 1.2; }
    .wa-eng-head small { display: block; margin-top: .1rem; font-size: .78rem; font-weight: 700; color: #6b7280; }
    .is-anee .wa-eng-head small { color: var(--hp-green); }
    /* The inputs: chips, set at the foot of their row so they sit right on top of the funnel. */
    .wa-eng-in { margin-top: 1.1rem; display: flex; flex-direction: column; justify-content: space-between; gap: 1rem; }
    .wa-eng-list { display: flex; flex-wrap: wrap; justify-content: center; gap: .4rem; }
    .wa-eng-in li { display: inline-flex; align-items: center; gap: .35rem; padding: .36rem .7rem .36rem .38rem; border-radius: 999px;
        font-size: .78rem; font-weight: 700; line-height: 1.25; color: #4b5563; background: #fff; border: 1px solid #e3e6df; }
    .wa-eng-in li i { flex: none; display: grid; place-items: center; width: 1.45rem; height: 1.45rem; border-radius: 999px; font-style: normal; font-size: .78rem; background: #eef0eb; }
    .is-anee .wa-eng-in li { color: #24400f; border-color: #cfe3b8; box-shadow: 0 6px 14px -12px rgb(47 82 25 / .7); }
    .is-anee .wa-eng-in li i { background: #e3f0d3; }
    .wa-eng-in li.is-thin { color: #9a3412; background: #fff7ed; border: 1px dashed #fdba74; }
    .wa-eng-in li.is-thin i { background: #ffedd5; }
    /* What a general app never sees: faded, struck through, in the room Anee's longer list takes (wide screens only). */
    .wa-eng-ghosts { display: none; flex-wrap: wrap; justify-content: center; gap: .4rem; }
    .wa-eng-ghosts span { display: inline-flex; align-items: center; gap: .35rem; padding: .32rem .7rem .32rem .38rem; border-radius: 999px; font-size: .76rem; font-weight: 700;
        color: #a3a8a0; border: 1px dashed #d4d8cf; text-decoration: line-through; text-decoration-color: rgb(156 163 175 / .8); }
    .wa-eng-ghosts i { display: grid; place-items: center; width: 1.4rem; height: 1.4rem; border-radius: 999px; font-style: normal; font-size: .74rem; opacity: .5; filter: grayscale(1); }
    @media (min-width: 768px) { .wa-eng-ghosts { display: flex; } }
    /* The funnel: thin grey lines on one side, a busy green and gold stream on the other. */
    .wa-eng-fun { display: block; width: 100%; max-width: 22rem; height: auto; margin: .35rem auto 0; overflow: visible; }
    .wa-eng-fun path { fill: none; stroke-linecap: round; }
    .wa-eng-fun .base { stroke: #dfe3d9; stroke-width: 2; }
    .is-anee .wa-eng-fun .base { stroke: #c6e0a9; }
    .wa-eng-fun .thin { stroke: #fb923c; stroke-width: 2; stroke-dasharray: 3 4; }
    /* A general app: a few grey dots crawling down. (Its own keyframe names: the shelves' wires further down already
       use waFlow and waRing, and a later @keyframes of the same name wins.) */
    .wa-eng-fun .flow { stroke: #a3a8a0; stroke-width: 3.4; stroke-dasharray: 2 34; animation: waEngSlow 3.4s linear infinite; }
    @keyframes waEngSlow { to { stroke-dashoffset: -36; } }
    /* Anee: glowing lines, a light running down each one, the meeting point pulsing. */
    .wa-eng-fun.is-smart { max-width: 23rem; }
    .wa-eng-fun.is-smart .glow path { fill: none; stroke: url(#waLineA); stroke-width: 5; stroke-linecap: round; opacity: .45; }
    .wa-eng-fun.is-smart .base { stroke: url(#waLineA); stroke-width: 2.4; }
    .wa-eng-fun.is-smart .comets, .wa-eng-fun.is-smart .hub-glow { mix-blend-mode: normal; }
    /* The model: the same core on both sides; Anee's runs smarter, and shows it. */
    .wa-eng-model { position: relative; align-self: center; justify-self: center; display: inline-flex; align-items: center; gap: .6rem; margin: .1rem auto 0;
        padding: .6rem 1.05rem .6rem .6rem; border-radius: 1.05rem; color: #fff; background: linear-gradient(135deg, #2b4419, #14210c);
        box-shadow: 0 18px 32px -18px rgb(20 33 12 / .85); }
    .wa-eng-model svg { flex: none; width: 2rem; height: 2rem; padding: .3rem; border-radius: .65rem; color: var(--hp-sun); background: rgb(255 255 255 / .12); }
    .wa-eng-model b { display: block; font-size: .92rem; font-weight: 800; line-height: 1.2; }
    .wa-eng-model small { display: block; font-size: .7rem; font-weight: 700; color: #b9caa8; }
    @property --waAng { syntax: '<angle>'; inherits: false; initial-value: 0deg; }
    .wa-eng-model.is-smart { padding: .7rem 1.15rem .7rem .7rem; border: 2px solid transparent;
        background: linear-gradient(135deg, #2f5219, #14210c) padding-box, conic-gradient(from var(--waAng), #f5c518, #bef264, #22c55e, #4a7c2a, #f5c518) border-box;
        box-shadow: 0 0 0 4px rgb(245 197 24 / .12), 0 22px 40px -18px rgb(47 82 25 / .9), 0 0 34px -6px rgb(190 242 100 / .45);
        animation: waEngSpin 3.2s linear infinite; }
    @keyframes waEngSpin { to { --waAng: 360deg; } }
    /* The bulbs. A general app's: grey, unlit. Anee's: lit gold, glowing, its rays pulsing. */
    .wa-eng-model svg.wa-bulb.is-dull { color: #9ca3af; background: rgb(255 255 255 / .06); }
    .wa-bulb.is-dull .glass { fill: rgb(156 163 175 / .16); }
    .wa-bulb.is-dull .wire { stroke: #6b7280; }
    .wa-bulb.is-bright .glass { fill: #f5c518; stroke: #fff3b0; }
    .wa-bulb.is-bright .wire { stroke: #7a4b00; }
    .wa-bulb.is-bright .rays { stroke: #fde68a; transform-origin: 16px 13.5px; animation: waEngRays 1.6s ease-in-out infinite; }
    @keyframes waEngRays { 0%, 100% { opacity: .45; transform: scale(.9); } 50% { opacity: 1; transform: scale(1.06); } }
    .wa-eng-model.is-smart svg.wa-bulb { padding: .2rem; filter: drop-shadow(0 0 5px rgb(245 197 24 / .85)); animation: waEngBulb 1.6s ease-in-out infinite; }
    @keyframes waEngBulb { 0%, 100% { filter: drop-shadow(0 0 3px rgb(245 197 24 / .55)); } 50% { filter: drop-shadow(0 0 9px rgb(245 197 24 / .95)); } }
    .wa-eng-model.is-smart svg { width: 2.3rem; height: 2.3rem; color: #fde68a; background: radial-gradient(circle at 50% 40%, rgb(245 197 24 / .35), rgb(255 255 255 / .08)); }
    .wa-eng-model.is-smart b { font-size: 1rem; background: linear-gradient(90deg, #fff 0%, #fde68a 45%, #fff 60%, #fff 100%) 0 0 / 220% 100%; -webkit-background-clip: text; background-clip: text; color: transparent;
        animation: waEngShine 2.8s ease-in-out infinite; }
    @keyframes waEngShine { 0% { background-position: 110% 0; } 100% { background-position: -110% 0; } }
    .wa-eng-model.is-smart small { color: #d9f99d; }
    /* The ring that pulses out as each light arrives. */
    .wa-eng-model.is-smart::after { content: ''; position: absolute; inset: -6px; border-radius: 1.35rem; border: 2px solid rgb(245 197 24 / .75); opacity: 0;
        animation: waEngRing 1.6s ease-out infinite; pointer-events: none; }
    @keyframes waEngRing { 0% { opacity: .9; transform: scale(.97); } 100% { opacity: 0; transform: scale(1.12); } }
    /* Three sparks that twinkle around it. */
    .wa-spark { position: absolute; z-index: 1; font-style: normal; line-height: 1; color: #f5c518; text-shadow: 0 0 10px rgb(245 197 24 / .9); pointer-events: none;
        animation: waEngTwinkle 2.4s ease-in-out infinite; }
    .wa-spark.is-a { top: -.7rem; left: .6rem; font-size: .9rem; }
    .wa-spark.is-b { top: -.45rem; right: -.4rem; font-size: .7rem; animation-delay: -.8s; }
    .wa-spark.is-c { bottom: -.6rem; right: 1.6rem; font-size: .8rem; animation-delay: -1.6s; }
    @keyframes waEngTwinkle { 0%, 100% { opacity: 0; transform: scale(.4) rotate(0deg); } 50% { opacity: 1; transform: scale(1.15) rotate(90deg); } }
    /* Anee's inputs light up one after another, as each is read. */
    .is-anee .wa-eng-in li { animation: waEngLit 3.5s ease-in-out infinite; animation-delay: calc(var(--i) * .5s); }
    @keyframes waEngLit { 0%, 70%, 100% { box-shadow: 0 6px 14px -12px rgb(47 82 25 / .7); } 82% { box-shadow: 0 0 0 3px rgb(190 242 100 / .45), 0 6px 18px -6px rgb(74 124 42 / .6); } }
    /* The answer, dripping out of the model. */
    .wa-eng-out { position: relative; align-self: start; display: flex; align-items: center; gap: .65rem; margin-top: 1.75rem; padding: .8rem 1rem; border-radius: 1.15rem; }
    .wa-eng-out::before { content: ''; position: absolute; left: 50%; top: -1.75rem; width: 2px; height: 1.75rem; margin-left: -1px;
        background: repeating-linear-gradient(180deg, currentColor 0 3px, transparent 3px 8px); opacity: .55; animation: waEngDrip 1.4s linear infinite; }
    @keyframes waEngDrip { to { background-position: 0 8px; } }
    /* Anee's answer: a bright beam, a light running down it. */
    .is-anee .wa-eng-out::before { width: 3px; margin-left: -1.5px; opacity: 1; border-radius: 999px;
        background: linear-gradient(180deg, transparent, #fff 45%, #f5c518 55%, transparent) 0 -120% / 100% 55% no-repeat, linear-gradient(180deg, #bef264, #4a7c2a);
        box-shadow: 0 0 10px rgb(190 242 100 / .7); animation: waEngBeam 1.1s linear infinite; }
    @keyframes waEngBeam { to { background-position: 0 220%, 0 0; } }
    .is-plain .wa-eng-out { color: #6b7280; background: #fff; border: 1px solid #e3e6df; }
    .is-anee .wa-eng-out { color: #fff; background: linear-gradient(135deg, #3d6823, #24400f); box-shadow: 0 22px 40px -24px rgb(36 64 15 / .9); }
    .wa-eng-out img { flex: none; width: 2.1rem; height: 2.1rem; border-radius: 999px; object-fit: cover; box-shadow: 0 0 0 2px var(--hp-sun); }
    .wa-eng-out b { display: block; font-size: .95rem; font-weight: 800; color: var(--hp-ink); line-height: 1.25; }
    .is-anee .wa-eng-out b { color: #fff; }
    .wa-eng-out small { display: block; margin-top: .1rem; font-size: .8rem; font-weight: 600; line-height: 1.4; }
    .is-anee .wa-eng-out small { color: #d9ebc4; }
    /* The = between the two models. */
    .wa-eng-eq { display: flex; align-items: center; justify-content: center; gap: .45rem; text-align: center;
        font-size: .66rem; font-weight: 800; letter-spacing: .07em; text-transform: uppercase; color: #6b7280; line-height: 1.3; }
    .wa-eng-eq b { flex: none; display: grid; place-items: center; width: 2.6rem; height: 2.6rem; border-radius: 999px; font-size: .9rem; letter-spacing: .02em; font-weight: 900;
        color: var(--hp-ink); background: #fff; border: 2px solid #e3e6df; box-shadow: 0 12px 24px -16px rgb(20 33 12 / .6); }
    .wa-eng-foot { max-width: 46rem; margin: 2.25rem auto 0; text-align: center; }

    /* Played in, in order, the first time the engines are seen (JS present, motion welcome); the loops only while on screen. */
    @media (prefers-reduced-motion: no-preference) {
        .wa-eng-col { transition: opacity .6s var(--hp-ease), transform .6s var(--hp-ease); }
        .wa-eng-col.is-anee { transition-delay: .12s; }
        .wa-eng-in li { transition: opacity .45s var(--hp-ease) calc(.35s + var(--i) * 85ms), transform .45s var(--hp-ease) calc(.35s + var(--i) * 85ms); }
        .wa-eng-fun .base { stroke-dasharray: 100; transition: stroke-dashoffset 1s var(--hp-ease) .9s; }
        .wa-eng-fun .thin, .wa-eng-fun .flow, .wa-eng-fun .comets, .wa-eng-fun .hub-glow, .wa-eng-fun .glow { transition: opacity .6s ease 1.6s; }
        .wa-eng-model { transition: opacity .5s var(--hp-ease) 1.45s, transform .5s var(--hp-ease) 1.45s; }
        .wa-eng-eq b, .wa-eng-eq span { transition: opacity .5s var(--hp-ease) 1.7s, transform .5s var(--hp-ease) 1.7s; }
        .wa-eng-out { transition: opacity .55s var(--hp-ease) 2.1s, transform .55s var(--hp-ease) 2.1s; }
        .is-anee .wa-eng-out { transition-delay: 2.35s; }
        html.js .wa-eng:not(.is-in) .wa-eng-col { opacity: 0; transform: translateY(18px); }
        html.js .wa-eng:not(.is-in) .wa-eng-in li { opacity: 0; transform: translateY(10px) scale(.92); }
        .wa-eng-ghosts { transition: opacity .8s ease .25s; }
        html.js .wa-eng:not(.is-in) .wa-eng-ghosts { opacity: 0; }
        html.js .wa-eng:not(.is-in) .wa-eng-fun .base { stroke-dashoffset: 100; }
        html.js .wa-eng:not(.is-in) .wa-eng-fun .thin, html.js .wa-eng:not(.is-in) .wa-eng-fun .flow, html.js .wa-eng:not(.is-in) .wa-eng-fun .comets,
        html.js .wa-eng:not(.is-in) .wa-eng-fun .hub-glow, html.js .wa-eng:not(.is-in) .wa-eng-fun .glow { opacity: 0; }
        html.js .wa-eng:not(.is-in) .wa-eng-model { opacity: 0; transform: scale(.85); }
        html.js .wa-eng:not(.is-in) .wa-eng-eq b, html.js .wa-eng:not(.is-in) .wa-eng-eq span { opacity: 0; transform: scale(.6); }
        html.js .wa-eng:not(.is-in) .wa-eng-out { opacity: 0; transform: translateY(12px) scale(.96); }
        .wa-eng:not(.is-live) .wa-eng-fun .flow, .wa-eng:not(.is-live) .wa-eng-model::after, .wa-eng:not(.is-live) .wa-eng-out::before,
        .wa-eng:not(.is-live) .wa-eng-model.is-smart, .wa-eng:not(.is-live) .wa-eng-model.is-smart b, .wa-eng:not(.is-live) .wa-spark,
        .wa-eng:not(.is-live) .wa-bulb .rays, .wa-eng:not(.is-live) .wa-eng-model.is-smart svg.wa-bulb,
        .wa-eng:not(.is-live) .is-anee .wa-eng-in li { animation-play-state: paused; }
    }
    @media (prefers-reduced-motion: reduce) {
        .wa-eng-fun .flow, .wa-eng-fun .comets, .wa-spark { display: none; }
        .wa-eng-model::after, .wa-eng-out::before, .wa-eng-model.is-smart, .wa-eng-model.is-smart b, .is-anee .wa-eng-in li, .wa-bulb .rays, .wa-eng-model.is-smart svg.wa-bulb { animation: none !important; }
        .wa-eng-model.is-smart b { color: #fff; background: none; }
    }

    /* ---- what she reads: eight shelves wired into Anee ---- */
    .wa-reads { background: linear-gradient(180deg, #f6f9f0, #eef5e4); }
    .wa-map { position: relative; margin-top: 3rem; display: grid; gap: 1rem; }
    @media (min-width: 640px) { .wa-map { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
    @media (min-width: 1024px) { .wa-map { grid-template-columns: repeat(3, minmax(0, 1fr)); grid-auto-rows: 1fr; gap: 1.5rem; } }
    .wa-wires { display: none; }
    @media (min-width: 1024px) {
        .wa-wires { display: block; position: absolute; inset: 0; width: 100%; height: 100%; overflow: visible; pointer-events: none; }
        .wa-wires path { fill: none; stroke: #9cc47a; stroke-width: 2px; vector-effect: non-scaling-stroke; stroke-dasharray: .05 .05;
            animation: waFlow 2.8s linear var(--w) infinite; }
    }
    @keyframes waFlow { to { stroke-dashoffset: -.1; } }
    .wa-shelf { position: relative; display: flex; flex-direction: column; padding: 1.2rem 1.2rem 1.25rem; border-radius: 1.3rem; background: #fff;
        border: 1px solid #e3ecd7; box-shadow: 0 16px 36px -30px rgb(20 33 12 / .5);
        transition: transform .28s var(--hp-ease), box-shadow .28s var(--hp-ease), border-color .28s var(--hp-ease); }
    .wa-shelf:hover { transform: translateY(-3px); border-color: #c3dca5; box-shadow: 0 24px 44px -30px rgb(20 33 12 / .6); }
    .wa-shelf-ico { width: 2.6rem; height: 2.6rem; border-radius: .9rem; display: grid; place-items: center; color: var(--hp-deep); background: #e3f0d3; }
    .wa-shelf-ico svg { width: 1.35rem; height: 1.35rem; }
    .wa-shelf h3 { margin-top: .85rem; font-family: var(--font-heading); font-size: 1.05rem; font-weight: 800; color: var(--hp-ink); }
    .wa-shelf p { margin-top: .35rem; flex: 1; font-size: .88rem; line-height: 1.55; color: #4b5563; }
    .wa-stat { align-self: flex-start; margin-top: .85rem; padding: .3rem .7rem; border-radius: 999px; font-size: .74rem; font-weight: 800; color: #6b4f00; background: #fdf3c4; }
    .wa-hub { position: relative; display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; gap: .35rem;
        padding: 1.6rem 1.25rem; border-radius: 1.6rem; color: #fff;
        background: radial-gradient(120% 90% at 50% 0%, #4a7c2a 0%, #24401a 60%, #16260e 100%); box-shadow: 0 30px 60px -34px rgb(20 33 12 / .9); }
    @media (max-width: 1023.98px) { .wa-hub { order: -1; } }
    @media (min-width: 640px) and (max-width: 1023.98px) { .wa-hub { grid-column: 1 / -1; } }
    .wa-hub-face { position: relative; display: grid; place-items: center; width: 5.2rem; height: 5.2rem; margin-bottom: .4rem; }
    .wa-hub-face img { position: relative; z-index: 1; width: 100%; height: 100%; border-radius: 999px; object-fit: cover; box-shadow: 0 0 0 4px var(--hp-sun); }
    .wa-hub-face::before, .wa-hub-face::after { content: ''; position: absolute; inset: 0; border-radius: 999px; border: 2px solid rgb(245 197 24 / .55);
        animation: waRing 2.6s ease-out infinite; }
    .wa-hub-face::after { animation-delay: 1.3s; }
    @keyframes waRing { from { transform: scale(1); opacity: .9; } to { transform: scale(1.7); opacity: 0; } }
    .wa-hub b { font-family: var(--font-heading); font-size: 1.4rem; font-weight: 800; }
    .wa-hub span:last-child { max-width: 15rem; font-size: .9rem; line-height: 1.5; color: #d3dec7; }
    .wa-note { margin: 2.5rem auto 0; max-width: 46rem; text-align: center; font-size: .95rem; line-height: 1.7; color: #4b5563; }

    /* ---- one question, two answers ---- */
    .wa-ask { margin-top: 2.6rem; display: flex; justify-content: center; }
    .wa-q { position: relative; max-width: 34rem; padding: .95rem 1.2rem; border-radius: 1.3rem 1.3rem .35rem 1.3rem; font-weight: 800; font-size: 1.05rem;
        color: #fff; background: var(--hp-green); box-shadow: 0 20px 40px -24px rgb(47 82 25 / .8); text-align: left; }
    .wa-q small { display: block; margin-top: .3rem; font-size: .82rem; font-weight: 600; color: #dcebc9; }
    .wa-chats { margin-top: 1.5rem; display: grid; gap: 1rem; }
    @media (min-width: 900px) { .wa-chats { grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); gap: 1.4rem; } }
    .wa-chat { padding: 1.3rem 1.3rem 1.35rem; border-radius: 1.4rem; }
    .wa-chat.is-plain { background: #f5f6f3; border: 1px solid #e5e7e1; }
    .wa-chat.is-anee { background: #fff; border: 1.5px solid #c3dca5; box-shadow: 0 26px 50px -34px rgb(47 82 25 / .6); }
    .wa-chat-who { display: flex; align-items: center; gap: .5rem; font-size: .82rem; font-weight: 800; letter-spacing: .04em; text-transform: uppercase; color: #6b7280; }
    .wa-chat.is-anee .wa-chat-who { color: var(--hp-green); }
    .wa-chat-who img { width: 1.8rem; height: 1.8rem; border-radius: 999px; object-fit: cover; box-shadow: 0 0 0 2px var(--hp-sun); }
    .wa-chat-dot { width: 1.8rem; height: 1.8rem; border-radius: 999px; background: #d1d5db; }
    .wa-chat-a { margin-top: .85rem; font-size: .98rem; line-height: 1.7; color: #374151; }
    .wa-chat-verdict { margin-top: 1rem; padding-top: .85rem; border-top: 1px dashed #d1d5db; font-size: .86rem; font-weight: 700; color: #9a3412; }
    .wa-read { margin-top: 1rem; padding-top: .85rem; border-top: 1px dashed #c3dca5; display: flex; flex-wrap: wrap; align-items: center; gap: .4rem; }
    .wa-read-k { width: 100%; font-size: .74rem; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; color: var(--hp-green); }
    .wa-read-chip { display: inline-flex; align-items: center; gap: .3rem; padding: .3rem .65rem .3rem .4rem; border-radius: 999px; font-size: .78rem; font-weight: 800;
        color: var(--hp-deep); background: #eaf4dd; }
    .wa-read-chip svg { width: 1rem; height: 1rem; padding: .15rem; border-radius: 999px; color: #fff; background: var(--hp-green); }
    html.js .wa-chat.is-anee.reveal .wa-read-chip { opacity: 0; transform: translateY(6px); }
    html.js .wa-chat.is-anee.reveal.is-visible .wa-read-chip { animation: waChip .45s var(--hp-ease) calc(.5s + var(--i) * .18s) forwards; }
    @keyframes waChip { to { opacity: 1; transform: none; } }
    /* What the general app could not read: the same four, crossed out; and the VS between the two answers. */
    .wa-chats { position: relative; }
    .wa-vsb { display: none; }
    @media (min-width: 900px) {
        .wa-vsb { position: absolute; left: 50%; top: 50%; z-index: 2; display: flex; align-items: center; justify-content: center; width: 3.2rem; height: 3.2rem; margin: -1.6rem 0 0 -1.6rem; padding: 0; border-radius: 999px; text-align: center; line-height: 1; letter-spacing: 0;
            font-family: var(--font-heading); font-size: .95rem; font-weight: 800; color: #2b2100; background: var(--hp-sun); box-shadow: 0 0 0 6px #fff, 0 14px 28px -12px rgb(0 0 0 / .35); }
    }
    .wa-read.is-miss { border-top-color: #e5d5c8; }
    .wa-read.is-miss .wa-read-k { color: #9a3412; }
    .wa-read.is-miss .wa-read-chip { color: #6b7280; background: #eceee9; text-decoration: line-through; text-decoration-color: rgb(154 52 18 / .5); }
    .wa-read.is-miss .wa-read-chip svg { background: #c2410c; }
    html.js .wa-chat.is-plain.reveal .wa-read-chip { opacity: 0; transform: translateY(6px); }
    html.js .wa-chat.is-plain.reveal.is-visible .wa-read-chip { animation: waChip .45s var(--hp-ease) calc(.4s + var(--i) * .14s) forwards; }
    .wa-chat-win { margin-top: 1rem; padding: .6rem .8rem; border-radius: .9rem; font-size: .86rem; font-weight: 800; color: #2d5016; background: #eaf4dd; }
    /* Honest limits: four cards. */
    .wa-limits { display: grid; gap: .9rem; margin-top: 2.2rem; }
    @media (min-width: 640px) { .wa-limits { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
    @media (min-width: 1024px) { .wa-limits { grid-template-columns: repeat(4, minmax(0, 1fr)); } }
    .wa-limit { display: grid; align-content: start; gap: .45rem; padding: 1.3rem 1.2rem; border-radius: 1.3rem; background: #fff; border: 1px solid #e6eedc;
        box-shadow: 0 18px 36px -30px rgb(47 82 25 / .55); transition: transform .3s var(--hp-ease), box-shadow .3s var(--hp-ease), border-color .3s var(--hp-ease); }
    .wa-limit:hover { transform: translateY(-3px); border-color: #c3dca5; box-shadow: 0 24px 44px -30px rgb(47 82 25 / .7); }
    .wa-limit-ico { display: grid; place-items: center; width: 2.8rem; height: 2.8rem; border-radius: .9rem; color: var(--hp-green); background: #eaf4dd; }
    .wa-limit-ico svg { width: 1.4rem; height: 1.4rem; }
    .wa-limit b { margin-top: .3rem; font-family: var(--font-heading); font-size: 1.05rem; font-weight: 800; color: var(--hp-ink); }
    .wa-limit p { font-size: .9rem; line-height: 1.6; color: #4b5563; }
    .wa-limit-foot { margin-top: 1.6rem; display: grid; gap: .9rem; justify-items: center; text-align: center; }
    .wa-limit-foot > p { max-width: 40rem; font-size: .95rem; line-height: 1.6; color: #4b5563; }
    .wa-limit-links { display: flex; flex-wrap: wrap; justify-content: center; gap: .5rem; }
    .wa-limit-links a { display: inline-flex; align-items: center; gap: .35rem; padding: .55rem .95rem; border-radius: 999px; font-size: .86rem; font-weight: 800; color: var(--hp-deep);
        background: #f3f8ec; border: 1px solid #d6e7c0; transition: background-color .28s var(--hp-ease), border-color .28s var(--hp-ease); }
    .wa-limit-links a:hover { background: #e7f2d9; border-color: #b8d699; }
    .wa-limit-links svg { width: 1rem; height: 1rem; }

    /* ---- why it matters: the dark band ---- */
    .wa-matters { position: relative; overflow: hidden;
        background: radial-gradient(90% 120% at 15% 0%, #2d4a1a 0%, transparent 60%), linear-gradient(160deg, #10160c 0%, #1c2416 55%, #24301a 100%); }
    .wa-matters-grid { display: grid; gap: 2.5rem; align-items: center; }
    @media (min-width: 1024px) { .wa-matters-grid { grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); gap: 4rem; } }
    .wa-matters .hp-p { color: #c9d5bd; }
    .wa-inline-cta { margin-top: 2rem; display: flex; flex-wrap: wrap; align-items: center; gap: 1rem 1.4rem; }
    @media (max-width: 639.98px) { .wa-inline-cta .hp-go { width: 100%; } }
    .wa-pillars { display: grid; gap: .9rem; }
    .wa-pillar { display: flex; gap: 1rem; align-items: flex-start; padding: 1.25rem 1.3rem; border-radius: 1.3rem; background: rgb(255 255 255 / .06);
        border: 1px solid rgb(255 255 255 / .08); transition: background-color .28s var(--hp-ease), transform .28s var(--hp-ease); }
    .wa-pillar:hover { background: rgb(255 255 255 / .1); transform: translateX(4px); }
    .wa-pillar-ico { flex: none; width: 2.8rem; height: 2.8rem; border-radius: .95rem; display: grid; place-items: center; color: var(--hp-ink); background: var(--hp-sun); }
    .wa-pillar-ico svg { width: 1.4rem; height: 1.4rem; }
    .wa-pillar h3 { font-family: var(--font-heading); font-size: 1.15rem; font-weight: 800; color: #fff; }
    .wa-pillar p { margin-top: .3rem; font-size: .93rem; line-height: 1.6; color: #c9d5bd; }
    .wa-pillar a { color: var(--hp-sun); font-weight: 800; text-decoration: underline; text-decoration-color: rgb(245 197 24 / .4); text-underline-offset: 3px; }

    /* ---- FAQ ---- */
    .wa-faq-sec { background: #f7f8f4; }
    .wa-faq { margin-top: 2.25rem; display: grid; gap: .75rem; }
    .wa-qa { border-radius: 1.2rem; background: #fff; border: 1px solid #e3ecd7; overflow: hidden; }
    .wa-qa summary { display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: 1.05rem 1.2rem; cursor: pointer; list-style: none; }
    .wa-qa summary::-webkit-details-marker { display: none; }
    .wa-qa summary h3 { font-family: var(--font-heading); font-size: 1.02rem; font-weight: 800; color: var(--hp-ink); }
    .wa-qa-plus { position: relative; flex: none; width: 1.9rem; height: 1.9rem; border-radius: 999px; background: #eaf4dd; transition: transform .28s var(--hp-ease), background-color .28s var(--hp-ease); }
    .wa-qa-plus::before, .wa-qa-plus::after { content: ''; position: absolute; left: 50%; top: 50%; width: .8rem; height: 2px; margin: -1px 0 0 -.4rem; border-radius: 2px; background: var(--hp-deep); }
    .wa-qa-plus::after { transform: rotate(90deg); transition: transform .28s var(--hp-ease); }
    .wa-qa[open] .wa-qa-plus { background: var(--hp-sun); }
    .wa-qa[open] .wa-qa-plus::after { transform: rotate(0deg); }
    .wa-qa-a { padding: 0 1.2rem 1.15rem; }
    .wa-qa-a p { font-size: .95rem; line-height: 1.7; color: #4b5563; }
    .wa-qa[open] .wa-qa-a { animation: waOpen .32s var(--hp-ease); }
    @keyframes waOpen { from { opacity: 0; transform: translateY(-4px); } }

    /* ---- last call ---- */
    .wa-final { position: relative; isolation: isolate; overflow: hidden; }
    .wa-final-bg { position: absolute; inset: 0; z-index: -2; width: 100%; height: 100%; object-fit: cover; }
    .wa-final-shade { position: absolute; inset: 0; z-index: -1; background: linear-gradient(160deg, rgb(20 36 12 / .92), rgb(29 51 15 / .82) 55%, rgb(47 82 25 / .74)); }
    .wa-final-face { width: 5rem; height: 5rem; margin: 0 auto 1rem; border-radius: 999px; object-fit: cover;
        box-shadow: 0 0 0 4px var(--hp-sun), 0 0 0 12px rgb(245 197 24 / .18); animation: waBob 5s ease-in-out infinite; }

    @media (max-width: 639.98px) {
        .wa-hero-art { padding: 0 1rem; width: min(100%, 19rem); }
        .wa-float { font-size: .74rem; }
        .wa-float.is-a { left: -.4rem; }
        .wa-float.is-c { left: -.6rem; }
    }

    @media (prefers-reduced-motion: reduce) {
        .wa-hero-bg, .wa-float, .wa-hub-face::before, .wa-hub-face::after, .wa-final-face, .wa-wires path, .wa-qa[open] .wa-qa-a { animation: none !important; }
        .wa-float { opacity: 1; }
        html.js .wa-chat.is-anee.reveal .wa-read-chip, html.js .wa-chat.is-plain.reveal .wa-read-chip { opacity: 1; transform: none; animation: none !important; }
        .wa-limit, .wa-limit-links a { transition: none !important; }
        .wa-shelf, .wa-pillar, .wa-qa-plus, .wa-qa-plus::after, .wa-link, .wa-textlink svg, .wa-crumbs a { transition: none !important; }
    }
</style>
@endpush

@push('scripts')
<script>
(() => {
    // The two engines: played in the first time they are seen, their flows running only while on screen.
    const eng = document.querySelector('[data-wa-eng]');
    if (!eng) return;
    if (!('IntersectionObserver' in window)) { eng.classList.add('is-in', 'is-live'); return; }
    const smart = eng.querySelector('[data-wa-smart]');
    const calm = matchMedia('(prefers-reduced-motion: reduce)').matches;
    if (smart && smart.pauseAnimations) smart.pauseAnimations();
    new IntersectionObserver((es) => es.forEach((e) => {
        if (e.isIntersecting) eng.classList.add('is-in');
        eng.classList.toggle('is-live', e.isIntersecting);
        if (smart && smart.pauseAnimations && !calm) { if (e.isIntersecting) smart.unpauseAnimations(); else smart.pauseAnimations(); }
    }), { rootMargin: '0px 0px -12% 0px' }).observe(eng);
})();
</script>
@endpush
