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

    {{-- ================= THE DIFFERENCE ================= --}}
    <section class="hp-sec bg-white">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 wa-diff">
            <div class="reveal">
                <p class="hp-kick">The real difference</p>
                <h2 class="hp-h2">The Model Is Not the Difference. <em>The Data Is.</em></h2>
                <p class="hp-p">
                    A general AI chat app knows a lot about farming in general. Ask it about your own field, though, and it has
                    only two things to work with: what it learned online and what you typed in the box. So its answer has to stay
                    general, and you still have to work out what it means for your lot.
                </p>
                <p class="hp-p">
                    Anee runs on a top language model too. The difference is what she reads before she answers. Each of her
                    analyses, from <a href="{{ $feat('when-to-plant-analysis') }}" class="wa-link">When to Plant</a> to the
                    <a href="{{ $feat('crop-protocol-analysis') }}" class="wa-link">Crop Protocol</a>, pulls in your season and the
                    records we keep for every town in the Philippines. That is how AI in agriculture should work: the answer fits
                    your field, your variety and this week's weather.
                </p>
            </div>
            <div class="wa-vs">
                <div class="wa-card is-plain reveal" style="--reveal-delay:.06s">
                    <p class="wa-card-k">A general AI chat app</p>
                    <p class="wa-card-s">ChatGPT, Gemini and the like</p>
                    <ul>
                        <li class="is-yes">{!! $tick !!}<span>Your question</span></li>
                        <li class="is-yes">{!! $tick !!}<span>What it learned online, from farms in many countries</span></li>
                        <li class="is-yes">{!! $tick !!}<span>A web search, when it decides to run one</span></li>
                        <li class="is-no">{!! $dash !!}<span>Your farm, only as far as you describe it each time</span></li>
                    </ul>
                </div>
                <div class="wa-card is-anee reveal" style="--reveal-delay:.14s">
                    <p class="wa-card-k"><img src="{{ $face }}" alt="">Anee</p>
                    <p class="wa-card-s">Your smart farm technician</p>
                    <ul>
                        @foreach (['Your question, and the photo you send', 'Your lots, crop, variety and planting date', 'Your field profile and your barangay\'s usual soil', 'The forecast for your farm and the ENSO outlook', 'Twenty years of rain, heat, typhoons, floods and droughts in your town', 'Registered varieties, fertilizers and pesticides sold here', 'Your season\'s tasks, notes, harvests and photos'] as $line)
                            <li class="is-yes">{!! $tick !!}<span>{{ $line }}</span></li>
                        @endforeach
                    </ul>
                </div>
            </div>
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
                <div class="wa-chat is-plain reveal" style="--reveal-delay:.06s">
                    <p class="wa-chat-who"><span class="wa-chat-dot" aria-hidden="true"></span>A general AI chat app</p>
                    <p class="wa-chat-a">For rice, the second fertilizer is usually put in at active tillering, about 20 to 30 days after transplanting. The right time depends on your variety, your soil and the weather, so check with your local agriculturist.</p>
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
        <div class="max-w-3xl mx-auto px-4 sm:px-6 text-center reveal">
            <p class="hp-kick">Honest limits</p>
            <h2 class="hp-h2">Where Anee Stops <em>and You Decide.</em></h2>
            <p class="hp-p">
                Anee tells you when she is not sure, and she shows what she read to reach her answer. She does not replace a
                laboratory soil test, and every pesticide she names comes with the reminder to follow its label and FPA
                registration. Our guide on <a href="{{ url('/blog/ai-farm-advice-accuracy') }}" class="wa-link">how accurate AI farm advice is</a>
                explains when to trust an answer and when to ask a technician.
            </p>
            <p class="hp-p">
                General chat apps are still handy for writing a letter or explaining a word. For decisions on your own field, use
                the one that reads your field. See the <a href="{{ route('pricing') }}" class="wa-link">plans that include Anee</a>
                or follow the whole season on <a href="{{ route('how') }}" class="wa-link">How It Works</a>.
            </p>
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

    /* ---- the difference: words beside two cards ---- */
    .wa-diff { display: grid; gap: 2.5rem; align-items: center; }
    @media (min-width: 1024px) { .wa-diff { grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); gap: 3.5rem; } }
    .wa-vs { display: grid; gap: 1rem; }
    @media (min-width: 640px) { .wa-vs { grid-template-columns: minmax(0, .9fr) minmax(0, 1.1fr); align-items: start; } }
    .wa-card { padding: 1.3rem 1.25rem 1.4rem; border-radius: 1.4rem; }
    .wa-card.is-plain { background: #f5f6f3; border: 1px solid #e5e7e1; }
    .wa-card.is-anee { background: linear-gradient(170deg, #f3f9ea, #e6f2d6); border: 1.5px solid #c3dca5; box-shadow: 0 26px 50px -34px rgb(47 82 25 / .6); }
    .wa-card-k { display: flex; align-items: center; gap: .55rem; font-family: var(--font-heading); font-size: 1.1rem; font-weight: 800; color: var(--hp-ink); }
    .wa-card-k img { width: 2rem; height: 2rem; border-radius: 999px; object-fit: cover; box-shadow: 0 0 0 2px var(--hp-sun); }
    .wa-card-s { margin-top: .2rem; font-size: .8rem; font-weight: 700; color: #6b7280; }
    .wa-card.is-anee .wa-card-s { color: var(--hp-green); }
    .wa-card ul { margin-top: 1rem; display: grid; gap: .6rem; }
    .wa-card li { display: flex; gap: .55rem; align-items: flex-start; font-size: .9rem; line-height: 1.45; font-weight: 600; color: #374151; }
    .wa-card li svg { flex: none; width: 1.25rem; height: 1.25rem; padding: .2rem; border-radius: 999px; margin-top: .05rem; }
    .wa-card li.is-yes svg { color: #fff; background: var(--hp-green); }
    .wa-card.is-plain li.is-yes svg { background: #9ca3af; }
    .wa-card li.is-no { color: #9a3412; }
    .wa-card li.is-no svg { color: #fff; background: #ea580c; }

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
        html.js .wa-chat.is-anee.reveal .wa-read-chip { opacity: 1; transform: none; animation: none !important; }
        .wa-shelf, .wa-pillar, .wa-qa-plus, .wa-qa-plus::after, .wa-link, .wa-textlink svg, .wa-crumbs a { transition: none !important; }
    }
</style>
@endpush
