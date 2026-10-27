@extends('layouts.public')

@include('public.partials.site-css')
@include('public.partials.hp-base')

@php
    $ph = \App\Support\Region::ph();
    $signup = route('signup');
    $ask = route('ask.page');
    $face = asset('images/anee/avatar-160.jpg');
    $faceLg = asset('images/anee/avatar-512.jpg');
    $arrow = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5-5 5M6 12h12"/></svg>';
    // The app as it is today: How It Works' own steps, Anee Auto first.
    $abSteps = collect(\App\Support\HowItWorks::stages())->sortBy(fn ($st) => $st['key'] === 'auto' ? 0 : 1)->values()->all();
    $abTools = collect($abSteps)->sum(fn ($st) => count($st['items']));
    $pic = fn ($base, $ext = 'jpg') => [
        'src' => asset('images/site/photos/' . $base . '.' . $ext),
        'set' => file_exists(public_path('images/site/photos/' . $base . '-800.webp'))
            ? asset('images/site/photos/' . $base . '-480.webp') . ' 480w, ' . asset('images/site/photos/' . $base . '-800.webp') . ' 800w'
            : null,
    ];
@endphp

@section('title', $ph ? 'About anee.io: Named After Ani, the Palay Harvest' : 'About anee.io: Named After the Harvest')
@section('meta_description', $ph
    ? 'anee.io takes its name from ani, the harvest. A farm app built by agronomists for Filipino palay, mais and gulay farmers, from pagtatanim to the last sack.'
    : 'anee.io takes its name from ani, the Filipino word for harvest. A farm app built by agronomists to plan every cropping season, from sowing to the last sack.')

{{-- ABOUT (rebuilt 2026-10-18, the owner: "it looks really bad"). The
     story in the order a reader asks it: who we are (a split hero with the
     farm photos), how the app came out of our own field work (three photo
     chapters), where the name comes from (a dictionary entry), the mission
     (one dark band with the quote), why it is built for Philippine farms,
     what it does (the season as one rail of steps), what we stand for, and
     the last call. One yellow button per screen at most; the rest are quiet
     links. Plain words, no dashes, no invented numbers, no byline. --}}
@section('content')

    {{-- ================= HERO ================= --}}
    {{-- The farmers behind anee.io fill the header (2026-10-27, the owner: "header has no background image and the
         filipino farmers text is yellow which is hard to read"): their photo under a deep green shade, darkest
         behind the words, so the white heading and its gold line read clearly; the family and their sign stay
         in sight on the right. The facts sit on a white card across the header's foot. --}}
    @php $p1 = $pic('powered-by'); @endphp
    <section class="ab-hero on-dark">
        <img src="{{ $p1['src'] }}" @if ($p1['set']) srcset="{{ $p1['set'] }}, {{ $p1['src'] }} 1600w" sizes="100vw" @endif
             alt="{{ $ph ? 'The farmers behind anee.io in their palayan, with a Powered by anee.io sign' : 'The farmers behind anee.io in their rice field, with a Powered by anee.io sign' }}"
             class="ab-hero-bg" loading="eager" fetchpriority="high" width="1600" height="1195">
        <div class="ab-hero-shade" aria-hidden="true"></div>
        <div class="relative max-w-6xl mx-auto px-4 sm:px-6 ab-hero-grid">
            <div class="ab-hero-copy">
                <span class="ab-chip animate-fade-up"><span class="ab-chip-dot"></span>About anee.io</span>
                <h1 class="ab-h1 animate-fade-up" style="animation-delay:.06s">
                    Built by Farmers, for
                    <span class="hp-mark"><span class="hp-shimmer">{{ $ph ? 'Filipino Farmers.' : 'Every Farmer.' }}</span><svg class="hp-mark-line" viewBox="0 0 300 24" preserveAspectRatio="none" aria-hidden="true"><path class="a" d="M6 13 Q 150 27, 294 5"/></svg></span>
                </h1>
                <p class="ab-lede animate-fade-up" style="animation-delay:.12s">
                    anee.io started as the system our own agronomists and technicians use on client farms. Now it is open
                    to every farmer, with one goal: a higher yield and a better income from the same land.
                </p>
                <div class="ab-ctas animate-fade-up" style="animation-delay:.18s">
                    <a href="{{ $signup }}" class="btn btn-accent btn-lg hp-go">Create your free account {!! $arrow !!}</a>
                    <a href="{{ route('how') }}" class="hp-alt">See how it works</a>
                </div>
                <ul class="ab-ticks animate-fade-up" style="animation-delay:.24s">
                    <li>Free forever on Libre</li>
                    <li>No card needed</li>
                    <li>Works on any phone</li>
                </ul>
            </div>
            <div class="ab-hero-side animate-fade-up" style="animation-delay:.2s">
                <div class="ab-float is-ani" aria-hidden="true"><b>ani</b><small>harvest</small></div>
                <div class="ab-float is-anee">
                    <img src="{{ $face }}" alt="">
                    <span><b>Anee</b><small>Your smart farm technician</small></span>
                </div>
            </div>
        </div>
    </section>
    <div class="ab-facts-band">
        <div class="relative max-w-5xl mx-auto px-4 sm:px-6">
            <dl class="ab-facts animate-fade-up" style="animation-delay:.3s">
                <div><dt>{{ $abTools }}</dt><dd>tools for one season</dd></div>
                <div><dt>{{ $ph ? '85' : '100' }}</dt><dd>crops it knows by stage</dd></div>
                <div><dt>{{ count($abSteps) }}</dt><dd>steps from plan to harvest</dd></div>
                <div><dt>{{ \App\Support\Region::money(0, 0) }}</dt><dd>to start, on Libre</dd></div>
            </dl>
        </div>
    </div>

    {{-- ================= FROM OUR FIELDS TO YOURS ================= --}}
    <section class="hp-sec bg-white" id="ab-story">
        <div class="max-w-6xl mx-auto px-4 sm:px-6">
            <div class="ab-head-split reveal">
                <div>
                    <p class="hp-kick">Our story</p>
                    <h2 class="hp-h2">From Our Fields <em>to Yours.</em></h2>
                </div>
                <p class="hp-p">anee.io was not made in an office. It grew out of years of work on real farms, one season at a time.</p>
            </div>
            <ol class="ab-chapters">
                @foreach ([
                    ['Years in the field', 'Our team has helped farmers grow bigger harvests of ' . \App\Support\Region::t('rice') . ', ' . \App\Support\Region::t('corn') . ' and more through research, technician support, fertilization and farm management, with results recognized here and abroad.', 'team-thumbs', 'Two farmers giving a thumbs up beside their rice field'],
                    ['The system behind the results', 'To run client farms well, our technicians built a cropping schedule manager: every task counted from day zero, every cost written down, every season kept on record.', 'transplant', 'A farmer following his plan on a phone at transplanting time'],
                    ['Now in your hands', 'anee.io puts that same system on your phone, with Anee, the smart farm technician, to answer when a technician cannot be there.', 'anee-chat-hand', 'A hand holding a phone with Anee answering in the middle of a rice field'],
                ] as $i => [$t, $p, $img, $alt])
                    @php $ch = $pic($img); @endphp
                    <li class="ab-chapter reveal" style="--reveal-delay: {{ $i * 0.12 }}s">
                        <figure class="ab-chapter-pic">
                            <img src="{{ $ch['src'] }}" @if ($ch['set']) srcset="{{ $ch['set'] }}" sizes="(min-width: 1024px) 22rem, (min-width: 640px) 45vw, 92vw" @endif alt="{{ $alt }}" loading="lazy">
                            <span class="ab-chapter-n">{{ sprintf('%02d', $i + 1) }}</span>
                        </figure>
                        <div class="ab-chapter-body">
                            <h3>{{ $t }}</h3>
                            <p>{{ $p }}</p>
                        </div>
                    </li>
                @endforeach
            </ol>
        </div>
    </section>

    {{-- ================= THE NAME ================= --}}
    <section class="hp-sec ab-name-sec" id="ab-name">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 ab-two">
            <div class="ab-dict reveal">
                <div class="ab-dict-word">
                    <b>ani</b>
                    <span class="ab-dict-say">(ah nee)</span>
                </div>
                <p class="ab-dict-kind">noun · Filipino</p>
                <ol class="ab-dict-means">
                    <li>The harvest: the crop that comes off the field at the end of a season.</li>
                    <li>The one score every farmer keeps.</li>
                </ol>
                @if ($ph)
                    <p class="ab-dict-eg"><i>“Kumusta ang ani?”</i> How was the harvest? How many cavans of palay came off the field?</p>
                @endif
                <div class="ab-dict-anee">
                    <img src="{{ $faceLg }}" alt="Anee, the anee.io smart farm technician">
                    <span><b>anee.io</b> and <b>Anee</b> both take their name from it.</span>
                </div>
            </div>
            <div class="reveal" style="--reveal-delay:.1s">
                <p class="hp-kick">Our name</p>
                <h2 class="hp-h2">Our Name Comes From <em>Ani, the Harvest.</em></h2>
                <p class="hp-p">
                    <b>Ani</b> is the whole point of a season. Every plan, every spray and every sack of fertilizer is
                    there for one day: the day the crop comes in.
                    @if ($ph)
                        Our page on the <a href="{{ url('/blog/ani-meaning') }}" class="ab-link">ani meaning</a> tells the rest.
                    @endif
                </p>
                <p class="hp-p">
                    Ani also gives its name to the smart farm technician inside the app. Ask <b>Anee</b> about your crop, send a photo
                    of a leaf, and she answers{{ $ph ? ' in Filipino or English' : '' }}, any time of the day.
                </p>
                <div class="ab-inline-cta">
                    <a href="{{ $ask }}" class="btn btn-accent btn-lg hp-go"><img src="{{ $face }}" alt="" class="hp-face">Ask Anee a free question {!! $arrow !!}</a>
                    <span>No account needed. One free question a week.</span>
                </div>
            </div>
        </div>
    </section>

    {{-- ================= THE MISSION ================= --}}
    <section class="hp-sec ab-mission spark-field on-dark">
        <div class="relative max-w-6xl mx-auto px-4 sm:px-6 ab-mission-grid" style="z-index:1">
            <div class="reveal">
                <p class="hp-kick">Our mission</p>
                <h2 class="hp-h2">Help Every {{ $ph ? 'Filipino ' : '' }}Farmer Grow <em>More From the Same Land.</em></h2>
                <p class="hp-p">
                    Fertilizer and fuel cost more every year, and the price at harvest does not keep up.
                </p>
                <blockquote class="ab-quote">
                    The answer is not another product to buy. It is better farm management, made simple enough to follow
                    from a phone in the middle of the field.
                </blockquote>
                <div class="ab-inline-cta is-dark">
                    <a href="{{ $signup }}" class="btn btn-accent btn-lg hp-go">Start free and grow more {!! $arrow !!}</a>
                    <span>Free forever on Libre. No card needed.</span>
                </div>
            </div>
            <ul class="ab-pillars">
                @foreach ([
                    ['A higher yield', 'Every task on its right day, counted from each lot\'s own day zero, so the crop gets what it needs when it needs it.', '<path stroke-linecap="round" stroke-linejoin="round" d="M3 17l6-6 4 4 8-8m0 0h-5m5 0v5"/>'],
                    ['A lower cost', 'Every ' . ($ph ? 'peso' : 'dollar') . ' written down as it is spent, so nothing is wasted and nothing is forgotten.', '<path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>'],
                    ['A smarter next season', 'Every season leaves a record, so the next one starts from what worked on your own farm.', '<path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h5M20 20v-5h-5M5.1 15a7.5 7.5 0 0013.4 1.5M18.9 9A7.5 7.5 0 005.5 7.5"/>'],
                ] as $i => [$t, $p, $ico])
                    <li class="ab-pillar reveal" style="--reveal-delay: {{ 0.1 + $i * 0.1 }}s">
                        <span class="ab-pillar-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">{!! $ico !!}</svg></span>
                        <span>
                            <h3>{{ $t }}</h3>
                            <p>{{ $p }}</p>
                        </span>
                    </li>
                @endforeach
            </ul>
        </div>
    </section>

    @if ($ph)
    {{-- ================= BUILT FOR PHILIPPINE FARMS ================= --}}
    <section class="hp-sec bg-white">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 ab-two is-flip">
            <div class="reveal">
                <p class="hp-kick">Why we built it</p>
                <h2 class="hp-h2">Made for Farms <em>in the Philippines.</em></h2>
                <p class="hp-p">
                    Farming still employs about one in five working Filipinos, and most farms here are small. Our guide to
                    <a href="{{ url('/blog/agriculture-in-the-philippine-economy') }}" class="ab-link">agriculture in the Philippine economy</a>
                    has the numbers. A small farm has no room for a missed fertilizer day or a pest found a week late.
                </p>
                <p class="hp-p">
                    So anee.io counts every task from each lot's own day zero, the way agronomists do. It works for
                    <a href="{{ url('/crops/pagtatanim-ng-palay') }}" class="ab-link">pagtatanim ng palay</a> in a Nueva Ecija
                    palayan, for mais in Isabela and Mindanao, and for gulay on the
                    <a href="{{ url('/crops/vegetables-philippines') }}" class="ab-link">vegetable farms of the Philippines</a>.
                    And Anee reads your season before she answers, in Filipino or English.
                </p>
                <p class="ab-guides-k">Guides from our team</p>
                <div class="ab-guides">
                    @foreach ([
                        ['/crops/palay', 'Palay', 'Growth stages, and how palay becomes rice.'],
                        ['/crops/corn-kernel', 'Corn and mais', 'Parts of the plant, corn types and the kernel price.'],
                        ['/blog/urea-fertilizer', 'Urea and 14 14 14', 'What each fertilizer does and when it goes in.'],
                        ['/pests/rice-bug', 'Rice bug and thrips', 'Signs, thresholds and control of the worst pests.'],
                        ['/blog/fertilizer-and-pesticide-authority', 'Fertilizer and Pesticide Authority', 'How to check that a product is registered before you buy.'],
                    ] as $i => [$href, $name, $line])
                        <a href="{{ url($href) }}" class="ab-guide">
                            <span class="ab-guide-t"><b>{{ $name }}</b><small>{{ $line }}</small></span>
                            <span class="ab-guide-go">{!! $arrow !!}</span>
                        </a>
                    @endforeach
                </div>
            </div>
            <div class="ab-ph-farm reveal" style="--reveal-delay:.1s">
                @php $pf = $pic('inspect'); @endphp
                <figure class="ab-ph is-farm">
                    <img src="{{ $pf['src'] }}" @if ($pf['set']) srcset="{{ $pf['set'] }}" sizes="(min-width: 1024px) 34rem, 92vw" @endif alt="A farmer checking her rice crop with her phone" loading="lazy">
                </figure>
                <div class="ab-crops">
                    @foreach ([['palay', 'Palay'], ['mais', 'Mais'], ['gulay', 'Gulay'], ['puno', 'Puno']] as [$img, $label])
                        <span class="ab-crop"><img src="{{ asset('images/site/home-crops/' . $img . '.webp') }}" alt="" loading="lazy">{{ $label }}</span>
                    @endforeach
                </div>
            </div>
        </div>
    </section>
    @endif

    {{-- ================= WHAT IT DOES ================= --}}
    <section class="hp-sec ab-season-sec">
        <div class="max-w-6xl mx-auto px-4 sm:px-6">
            <div class="ab-head-split reveal">
                <div>
                    <p class="hp-kick">The app</p>
                    <h2 class="hp-h2">One App for <em>the Whole Season.</em></h2>
                </div>
                <p class="hp-p">{{ $abTools }} tools, step by step from the first plan to the last sack. Or let Anee run the whole season for you with Anee Auto.</p>
            </div>
            <ol class="ab-rail">
                @foreach ($abSteps as $i => $st)
                    <li class="reveal" style="--reveal-delay: {{ ($i % 4) * 0.06 }}s">
                        <a href="{{ route('features') }}#step-{{ $st['key'] }}" class="ab-stop {{ $st['key'] === 'auto' ? 'is-auto' : '' }}">
                            <span class="ab-stop-n">{{ $i + 1 }}</span>
                            <span class="ab-stop-t">
                                <b>{{ $st['title'] }}</b>
                                <small>{{ count($st['items']) }} {{ \Illuminate\Support\Str::plural('tool', count($st['items'])) }}</small>
                            </span>
                            <span class="ab-stop-icons" aria-hidden="true">
                                @foreach (array_slice($st['items'], 0, 4) as $it)<img src="{{ asset('images/' . $it['icon']) }}" alt="" loading="lazy" class="{{ str_starts_with($it['icon'], 'anee/') ? 'is-face' : '' }}">@endforeach
                            </span>
                        </a>
                    </li>
                @endforeach
            </ol>
            <div class="ab-links reveal">
                <a href="{{ route('how') }}" class="hp-alt">See how it works</a>
                <a href="{{ route('features') }}" class="ab-textlink">Every feature, one by one {!! $arrow !!}</a>
            </div>
        </div>
    </section>

    {{-- ================= VALUES ================= --}}
    <section class="hp-sec bg-white">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 ab-values-grid">
            <div class="ab-values-head reveal">
                <p class="hp-kick">What we stand for</p>
                <h2 class="hp-h2">The Values <em>Behind Every Tool.</em></h2>
                <p class="hp-p">Four rules we hold every new tool to before it goes into the app.</p>
                <a href="{{ route('contact') }}" class="ab-textlink">Talk to us. A real person reads every message {!! $arrow !!}</a>
            </div>
            <div class="ab-values">
                @foreach ([
                    ['Farmer first', 'Everything we build starts with the real life of ' . ($ph ? 'Filipino farms' : 'working farms') . ': the budget, the weather and the labor.'],
                    ['Backed by science', 'Our schedules and protocols come from research and years of field results, not guesswork.'],
                    ['Income and the land', 'Yield matters, and so does the soil. We plan for this season and the many seasons after it.'],
                    ['Simple on a phone', 'If it does not work on a phone in the middle of a ' . ($ph ? 'palayan' : 'rice field') . ', it does not go into the app.'],
                ] as $i => [$t, $p])
                    <div class="ab-value reveal" style="--reveal-delay: {{ $i * 0.07 }}s">
                        <span class="ab-value-n">{{ sprintf('%02d', $i + 1) }}</span>
                        <h3>{{ $t }}</h3>
                        <p>{{ $p }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ================= LAST CALL ================= --}}
    <section class="ab-final">
        @php $pl = $pic('palay-heads'); @endphp
        <img src="{{ $pl['src'] }}" @if ($pl['set']) srcset="{{ $pl['set'] }}, {{ $pl['src'] }} 1600w" sizes="100vw" @endif alt="" class="ab-final-bg" loading="lazy">
        <div class="ab-final-shade" aria-hidden="true"></div>
        <div class="relative max-w-4xl mx-auto px-4 sm:px-6 hp-sec text-center reveal on-dark" style="z-index:1">
            <img src="{{ $faceLg }}" alt="" class="ab-final-face">
            <h2 class="hp-h2">Reach Your Crop's Full Potential <em>This Season.</em></h2>
            <p class="hp-p">Start planning with the same system our own technicians use. It is free to start.</p>
            <div class="hp-cta">
                <div class="hp-cta-row">
                    <a href="{{ $signup }}" class="btn btn-accent btn-lg hp-go">Create your free account {!! $arrow !!}</a>
                    <a href="{{ $ask }}" class="hp-alt is-glass"><img src="{{ $face }}" alt="" class="hp-face">Ask Anee a free question</a>
                </div>
                <p class="hp-cta-note">Free forever on Libre. No card needed.</p>
            </div>
        </div>
    </section>

@endsection

@push('head')
<style>
    /* ===================== ABOUT (2026-10-18) ===================== */
    .ab-link { font-weight: 800; color: var(--hp-green); text-decoration: underline; text-decoration-color: rgb(74 124 42 / .35); text-underline-offset: 3px;
        transition: text-decoration-color .28s var(--hp-ease); }
    .ab-link:hover { text-decoration-color: var(--hp-green); }
    .ab-textlink { display: inline-flex; align-items: center; gap: .4rem; font-weight: 800; color: var(--hp-deep); text-decoration: none; }
    .ab-textlink svg { width: 1.05rem; height: 1.05rem; flex: none; transition: transform .28s var(--hp-ease); }
    .ab-textlink:hover svg { transform: translateX(4px); }

    /* Hero: the farmers' photo under a shade darkest behind the words; the facts on a card across its foot. */
    .ab-hero { position: relative; isolation: isolate; overflow: hidden; padding: 3.5rem 0 6.5rem; background-color: #16290d; }
    @media (min-width: 1024px) { .ab-hero { padding: 5rem 0 8rem; min-height: 42rem; display: flex; align-items: center; } .ab-hero > div { width: 100%; } }
    .ab-hero-bg { position: absolute; inset: 0; z-index: -2; width: 100%; height: 100%; object-fit: cover; object-position: 62% 38%;
        transform: scale(1.04); animation: abDrift 28s ease-in-out infinite alternate; }
    @keyframes abDrift { to { transform: scale(1.1) translate(-1%, -.6%); } }
    .ab-hero-shade { position: absolute; inset: 0; z-index: -1; pointer-events: none;
        background: linear-gradient(180deg, rgb(10 22 6 / .72) 0%, rgb(12 26 7 / .8) 55%, rgb(10 22 6 / .9) 100%); }
    @media (min-width: 1024px) { .ab-hero-shade {
        background: linear-gradient(90deg, rgb(9 20 5 / .9) 0%, rgb(12 26 7 / .82) 34%, rgb(14 30 8 / .42) 64%, rgb(14 30 8 / .18) 100%),
            linear-gradient(180deg, rgb(10 22 6 / .35) 0%, transparent 30%, transparent 62%, rgb(10 22 6 / .7) 100%); } }
    /* The words keep to the left; the right of the band is the family and their sign, left in plain sight. */
    .ab-hero-grid { display: grid; gap: 1.75rem; }
    .ab-hero-copy, .ab-hero-side { max-width: 40rem; }
    .ab-chip { display: inline-flex; align-items: center; gap: .5rem; padding: .4rem .95rem; border-radius: 999px; font-size: .76rem; font-weight: 800;
        letter-spacing: .1em; text-transform: uppercase; color: #fff; background: rgb(255 255 255 / .12); box-shadow: inset 0 0 0 1px rgb(255 255 255 / .28);
        -webkit-backdrop-filter: blur(6px); backdrop-filter: blur(6px); }
    .ab-chip-dot { width: .5rem; height: .5rem; border-radius: 999px; background: var(--hp-sun); box-shadow: 0 0 0 3px rgb(245 197 24 / .3); }
    .ab-h1 { margin-top: 1.25rem; font-family: var(--font-heading); font-weight: 800; letter-spacing: -.025em; line-height: 1.04; color: #fff;
        font-size: clamp(2.35rem, 5.6vw, 4.2rem); text-wrap: balance; text-shadow: 0 2px 28px rgb(0 0 0 / .35); }
    /* Gradient text paints its fill as a background: a shadow would sit on top of it, so the gold goes without. */
    .ab-h1 .hp-shimmer { text-shadow: none; filter: drop-shadow(0 2px 14px rgb(0 0 0 / .35)); }
    .ab-lede { margin-top: 1.75rem; max-width: 34rem; font-size: clamp(1.02rem, 1.6vw, 1.18rem); line-height: 1.7; color: #e3ecd8; text-wrap: pretty; }
    .ab-ctas { margin-top: 1.9rem; display: flex; flex-wrap: wrap; gap: .75rem; }
    .ab-ticks { margin-top: 1.4rem; display: flex; flex-wrap: wrap; gap: .45rem 1.2rem; font-size: .88rem; font-weight: 700; color: #e3ecd8; }
    .ab-ticks li { display: inline-flex; align-items: center; gap: .4rem; }
    .ab-ticks li::before { content: ''; width: 1.05rem; height: 1.05rem; flex: none; border-radius: 999px; background: var(--hp-sun)
        url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%2314210c' stroke-width='3.4' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M5 13l4 4L19 7'/%3E%3C/svg%3E") center / .66rem no-repeat; }
    @media (max-width: 639.98px) { .ab-ctas > * { width: 100%; } }

    /* Under the words: the word ani and Anee, side by side. */
    .ab-hero-side { position: relative; display: flex; flex-wrap: wrap; align-items: center; gap: .75rem; }
    .ab-float { display: flex; align-items: center; gap: .65rem; padding: .6rem .9rem .6rem .6rem; border-radius: 1.1rem;
        background: #fff; box-shadow: 0 20px 40px -22px rgb(0 0 0 / .7); animation: abBob 6s ease-in-out infinite; }
    .ab-float.is-anee img { width: 2.6rem; height: 2.6rem; border-radius: 999px; object-fit: cover; box-shadow: 0 0 0 3px var(--hp-sun); }
    .ab-float.is-anee b { display: block; font-family: var(--font-heading); font-weight: 800; color: var(--hp-ink); line-height: 1.1; }
    .ab-float.is-anee small { display: block; font-size: .74rem; font-weight: 700; color: var(--hp-green); }
    .ab-float.is-ani { flex-direction: column; align-items: flex-start; gap: 0; padding: .55rem .85rem; background: var(--hp-ink);
        box-shadow: inset 0 0 0 1px rgb(245 197 24 / .35), 0 20px 40px -22px rgb(0 0 0 / .7); animation-delay: -3s; }
    .ab-float.is-ani b { font-family: var(--font-heading); font-size: 1.35rem; font-weight: 800; color: var(--hp-sun); line-height: 1; }
    .ab-float.is-ani small { font-size: .7rem; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; color: #dbe7cd; }
    @keyframes abBob { 0%, 100% { translate: 0 0; } 50% { translate: 0 -6px; } }

    /* The facts card rides up over the header's foot; the band is white below the photo. */
    .ab-facts-band { position: relative; z-index: 2; margin-top: -4rem; background: linear-gradient(180deg, transparent 0 4rem, #fff 4rem); }
    .ab-facts { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); border-radius: 1.4rem; overflow: hidden;
        background: #fff; border: 1px solid #e4ecda; box-shadow: 0 30px 60px -36px rgb(20 33 12 / .6); }
    @media (min-width: 768px) { .ab-facts { grid-template-columns: repeat(4, minmax(0, 1fr)); } }
    .ab-facts div { padding: 1.15rem .9rem; text-align: center; }
    .ab-facts div + div { border-left: 1px solid #edf2e6; }
    @media (max-width: 767.98px) { .ab-facts div:nth-child(3) { border-left: 0; } .ab-facts div:nth-child(n + 3) { border-top: 1px solid #edf2e6; } }
    .ab-facts dt { font-family: var(--font-heading); font-size: clamp(1.7rem, 3.4vw, 2.2rem); font-weight: 800; line-height: 1; color: var(--hp-deep); }
    .ab-facts dd { margin-top: .4rem; font-size: .8rem; font-weight: 700; color: #6b7280; }

    /* A section head with its words to the side on a wide screen. */
    .ab-head-split { display: grid; gap: .6rem; }
    @media (min-width: 1024px) { .ab-head-split { grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); gap: 3rem; align-items: end; }
        .ab-head-split .hp-p { margin-top: 0; padding-bottom: .35rem; } }

    /* Story: three chapters, each with its photo. */
    .ab-chapters { margin-top: 2.6rem; display: grid; gap: 1.25rem; }
    @media (min-width: 640px) { .ab-chapters { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
    @media (min-width: 1024px) { .ab-chapters { grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 1.5rem; } }
    .ab-chapter { display: flex; flex-direction: column; border-radius: 1.5rem; overflow: hidden; background: #fff; border: 1px solid #e6ede0;
        box-shadow: 0 24px 48px -40px rgb(20 33 12 / .6); transition: transform .28s var(--hp-ease), box-shadow .28s var(--hp-ease); }
    .ab-chapter:hover { transform: translateY(-4px); box-shadow: 0 30px 56px -38px rgb(20 33 12 / .65); }
    .ab-chapter-pic { position: relative; margin: 0; aspect-ratio: 4 / 3; overflow: hidden; background: #e6efda; }
    .ab-chapter-pic img { width: 100%; height: 100%; object-fit: cover; display: block; transition: transform .6s var(--hp-ease); }
    .ab-chapter:hover .ab-chapter-pic img { transform: scale(1.04); }
    .ab-chapter-n { position: absolute; left: 1rem; top: 1rem; padding: .3rem .7rem; border-radius: 999px; font-family: var(--font-heading);
        font-size: .85rem; font-weight: 800; color: var(--hp-ink); background: var(--hp-sun); box-shadow: 0 8px 18px -8px rgb(0 0 0 / .45); }
    .ab-chapter-body { padding: 1.25rem 1.35rem 1.45rem; }
    .ab-chapter-body h3 { font-family: var(--font-heading); font-size: 1.2rem; font-weight: 800; color: var(--hp-ink); }
    .ab-chapter-body p { margin-top: .45rem; font-size: .95rem; line-height: 1.65; color: #4b5563; }
    @media (min-width: 640px) and (max-width: 1023.98px) { .ab-chapter:last-child { grid-column: 1 / -1; flex-direction: row; }
        .ab-chapter:last-child .ab-chapter-pic { width: 45%; flex: none; aspect-ratio: auto; } .ab-chapter:last-child .ab-chapter-body { align-self: center; } }

    /* The name, as a dictionary has it. */
    .ab-name-sec { background: linear-gradient(180deg, #f4f8ee, #fbfcf8); }
    .ab-two { display: grid; gap: 2.75rem; align-items: center; }
    @media (min-width: 1024px) { .ab-two { grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); gap: 4.5rem; }
        .ab-two.is-flip { grid-template-columns: minmax(0, 1.05fr) minmax(0, .95fr); align-items: start; } }
    .ab-dict { position: relative; padding: 2rem 1.6rem 1.6rem; border-radius: 1.6rem; background: #fff; border: 1px solid #e3ebd8;
        box-shadow: 0 34px 64px -44px rgb(20 33 12 / .65); }
    @media (min-width: 640px) { .ab-dict { padding: 2.4rem 2.3rem 2rem; } }
    .ab-dict::before { content: ''; position: absolute; left: 0; top: 2rem; bottom: 2rem; width: 5px; border-radius: 0 5px 5px 0;
        background: linear-gradient(180deg, var(--hp-sun), var(--hp-green)); }
    .ab-dict-word { display: flex; align-items: baseline; flex-wrap: wrap; gap: .3rem .9rem; }
    .ab-dict-word b { font-family: var(--font-heading); font-size: clamp(3.6rem, 9vw, 5.2rem); font-weight: 800; letter-spacing: -.03em; line-height: .95; color: var(--hp-ink); }
    .ab-dict-say { font-size: 1.05rem; font-weight: 700; color: #6b7280; }
    .ab-dict-kind { margin-top: .6rem; font-size: .8rem; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; color: var(--hp-green); }
    .ab-dict-means { margin-top: 1.1rem; display: grid; gap: .55rem; counter-reset: m; }
    .ab-dict-means li { position: relative; padding-left: 2rem; font-size: 1.02rem; line-height: 1.6; color: #374151; counter-increment: m; }
    .ab-dict-means li::before { content: counter(m); position: absolute; left: 0; top: .1rem; width: 1.4rem; height: 1.4rem; border-radius: 999px;
        display: grid; place-items: center; font-size: .75rem; font-weight: 800; color: var(--hp-deep); background: #e8f2dc; }
    .ab-dict-eg { margin-top: 1rem; padding: .85rem 1rem; border-radius: .9rem; background: #fbf7e6; font-size: .93rem; line-height: 1.6; color: #4b5563; }
    .ab-dict-eg i { font-weight: 800; color: var(--hp-ink); }
    .ab-dict-anee { margin-top: 1.3rem; padding-top: 1.1rem; border-top: 1px dashed #dfe8d3; display: flex; align-items: center; gap: .8rem;
        font-size: .92rem; color: #4b5563; }
    .ab-dict-anee img { width: 3rem; height: 3rem; flex: none; border-radius: 999px; object-fit: cover; box-shadow: 0 0 0 3px var(--hp-sun); }
    .ab-dict-anee b { color: var(--hp-ink); }
    .ab-inline-cta { margin-top: 1.8rem; display: flex; flex-wrap: wrap; align-items: center; gap: .7rem 1rem; }
    .ab-inline-cta > span { font-size: .86rem; color: #6b7280; }
    .ab-inline-cta.is-dark > span { color: #b9c8aa; }
    .ab-inline-cta .hp-face { margin-left: -.2rem; }
    @media (max-width: 639.98px) { .ab-inline-cta .btn { width: 100%; } }

    /* Mission: the quote on one side, the three promises on the other. */
    .ab-mission { position: relative; overflow: hidden;
        background: radial-gradient(90% 120% at 15% 0%, #2d4a1a 0%, transparent 60%), linear-gradient(160deg, #10160c 0%, #1c2416 55%, #24301a 100%); }
    .ab-mission-grid { display: grid; gap: 2.5rem; align-items: center; }
    @media (min-width: 1024px) { .ab-mission-grid { grid-template-columns: minmax(0, 1.1fr) minmax(0, .9fr); gap: 4rem; } }
    .ab-mission .hp-p { color: #c9d5bd; }
    .ab-quote { margin-top: 1.4rem; padding-left: 1.2rem; border-left: 4px solid var(--hp-sun); font-family: var(--font-heading);
        font-size: clamp(1.25rem, 2.3vw, 1.6rem); font-weight: 700; line-height: 1.4; color: #fff; text-wrap: pretty; }
    .ab-pillars { display: grid; gap: .9rem; }
    .ab-pillar { display: flex; gap: 1rem; align-items: flex-start; padding: 1.25rem 1.3rem; border-radius: 1.3rem; background: rgb(255 255 255 / .06);
        box-shadow: inset 0 0 0 1px rgb(255 255 255 / .12); transition: background-color .28s var(--hp-ease), transform .28s var(--hp-ease); }
    .ab-pillar:hover { background: rgb(255 255 255 / .1); transform: translateX(4px); }
    .ab-pillar-ico { flex: none; width: 2.8rem; height: 2.8rem; border-radius: .95rem; display: grid; place-items: center; color: var(--hp-ink); background: var(--hp-sun); }
    .ab-pillar-ico svg { width: 1.4rem; height: 1.4rem; }
    .ab-pillar h3 { font-family: var(--font-heading); font-size: 1.15rem; font-weight: 800; color: #fff; }
    .ab-pillar p { margin-top: .3rem; font-size: .93rem; line-height: 1.6; color: #c9d5bd; }

    /* Philippine farms: the words and guides, then the photo with its crops. */
    .ab-guides-k { margin-top: 2rem; font-size: .76rem; font-weight: 800; letter-spacing: .1em; text-transform: uppercase; color: var(--hp-green); }
    .ab-guides { margin-top: .8rem; display: grid; gap: .55rem; }
    @media (min-width: 640px) { .ab-guides { grid-template-columns: repeat(2, minmax(0, 1fr)); } .ab-guide:last-child { grid-column: 1 / -1; } }
    .ab-guide { display: flex; align-items: center; gap: .8rem; padding: .85rem 1rem; border-radius: 1rem; background: #f7faf3; border: 1px solid #e5ecdc;
        text-decoration: none; transition: border-color .28s var(--hp-ease), background-color .28s var(--hp-ease), transform .28s var(--hp-ease); }
    .ab-guide:hover { border-color: #b9d69a; background: #fff; transform: translateY(-2px); }
    .ab-guide-t { flex: 1 1 auto; min-width: 0; }
    .ab-guide-t b { display: block; font-family: var(--font-heading); font-weight: 800; font-size: .96rem; color: var(--hp-ink); }
    .ab-guide-t small { display: block; margin-top: .1rem; font-size: .82rem; color: #6b7280; line-height: 1.45; }
    .ab-guide-go { flex: none; color: #9ca3af; transition: color .28s var(--hp-ease), transform .28s var(--hp-ease); }
    .ab-guide-go svg { width: 1.05rem; height: 1.05rem; }
    .ab-guide:hover .ab-guide-go { color: var(--hp-green); transform: translateX(3px); }
    .ab-ph { margin: 0; overflow: hidden; border-radius: 1.6rem; background: #e6efda; box-shadow: 0 34px 70px -40px rgb(20 33 12 / .75); }
    .ab-ph img { width: 100%; height: 100%; object-fit: cover; display: block; }
    .ab-ph-farm { position: relative; padding-bottom: 1.6rem; }
    @media (min-width: 1024px) { .ab-ph-farm { position: sticky; top: 6rem; } }
    .ab-ph.is-farm { aspect-ratio: 4 / 4.6; }
    @media (max-width: 1023.98px) { .ab-ph.is-farm { aspect-ratio: 4 / 3.2; } }
    .ab-crops { position: absolute; left: 50%; bottom: 0; transform: translateX(-50%); display: flex; gap: .4rem; padding: .45rem; border-radius: 999px;
        background: #fff; box-shadow: 0 20px 40px -22px rgb(20 33 12 / .6); white-space: nowrap; width: max-content; }
    .ab-crop { flex: none; display: inline-flex; align-items: center; gap: .4rem; padding: .3rem .7rem .3rem .3rem; border-radius: 999px; background: #f3f8ec;
        font-size: .84rem; font-weight: 800; color: var(--hp-deep); }
    .ab-crop img { width: 1.7rem; height: 1.7rem; border-radius: 999px; object-fit: cover; }
    @media (max-width: 379.98px) { .ab-crop { padding-right: .5rem; font-size: .76rem; } .ab-crop img { width: 1.4rem; height: 1.4rem; } }

    /* The season, as one rail of numbered stops. */
    .ab-season-sec { background: #f6f8f3; }
    .ab-rail { margin-top: 2.6rem; display: grid; gap: .75rem; }
    @media (min-width: 640px) { .ab-rail { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
    @media (min-width: 1024px) { .ab-rail { grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 1rem; } }
    .ab-stop { height: 100%; display: grid; grid-template-columns: auto minmax(0, 1fr); grid-template-areas: "n t" "n i"; column-gap: .9rem; row-gap: .7rem;
        align-items: center; padding: 1rem 1.1rem; border-radius: 1.2rem; background: #fff; border: 1px solid #e3eada; text-decoration: none;
        transition: transform .28s var(--hp-ease), border-color .28s var(--hp-ease), box-shadow .28s var(--hp-ease); }
    .ab-stop:hover { transform: translateY(-3px); border-color: #b9d69a; box-shadow: 0 22px 40px -32px rgb(20 33 12 / .6); }
    .ab-stop-n { grid-area: n; align-self: start; width: 2.5rem; height: 2.5rem; border-radius: 999px; display: grid; place-items: center;
        font-family: var(--font-heading); font-weight: 800; color: #fff; background: var(--hp-deep); box-shadow: 0 0 0 4px #e6f0dc; }
    .ab-stop.is-auto { border-color: #f0dc95; background: linear-gradient(160deg, #fff8e1, #fff 70%); }
    .ab-stop.is-auto .ab-stop-n { color: var(--hp-ink); background: var(--hp-sun); box-shadow: 0 0 0 4px #fff1bf; }
    .ab-stop-t { grid-area: t; min-width: 0; }
    .ab-stop-t b { display: block; font-family: var(--font-heading); font-size: 1.02rem; font-weight: 800; color: var(--hp-ink); line-height: 1.25; }
    .ab-stop-t small { display: block; margin-top: .15rem; font-size: .78rem; font-weight: 700; color: var(--hp-green); }
    .ab-stop-icons { grid-area: i; display: flex; gap: .3rem; }
    .ab-stop-icons img { width: 1.75rem; height: 1.75rem; padding: .22rem; border-radius: .55rem; object-fit: contain; background: #f3f8ec; box-shadow: inset 0 0 0 1px #dcead0; }
    .ab-stop-icons img.is-face { padding: 0; object-fit: cover; }
    .ab-links { margin-top: 2.2rem; display: flex; flex-wrap: wrap; align-items: center; justify-content: center; gap: .9rem 1.6rem; }

    /* Values: the head on the left, four numbered rules on the right. */
    .ab-values-grid { display: grid; gap: 2.4rem; }
    @media (min-width: 1024px) { .ab-values-grid { grid-template-columns: minmax(0, .8fr) minmax(0, 1.2fr); gap: 4rem; align-items: start; }
        .ab-values-head { position: sticky; top: 6rem; } }
    .ab-values-head .ab-textlink { margin-top: 1.5rem; }
    .ab-values { display: grid; gap: 1rem; }
    @media (min-width: 640px) { .ab-values { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
    .ab-value { padding: 1.4rem 1.4rem 1.5rem; border-radius: 1.3rem; background: linear-gradient(170deg, #f6faf1, #fff); border: 1px solid #e4ecdb;
        transition: transform .28s var(--hp-ease), box-shadow .28s var(--hp-ease); }
    .ab-value:hover { transform: translateY(-4px); box-shadow: 0 20px 40px -30px rgb(20 33 12 / .55); }
    .ab-value-n { font-family: var(--font-heading); font-size: 2rem; font-weight: 800; line-height: 1; color: transparent;
        -webkit-text-stroke: 1.5px var(--hp-green); }
    .ab-value h3 { margin-top: .8rem; font-family: var(--font-heading); font-size: 1.12rem; font-weight: 800; color: var(--hp-ink); }
    .ab-value p { margin-top: .4rem; font-size: .93rem; line-height: 1.6; color: #4b5563; }

    /* Last call over the ripe palay. */
    .ab-final { position: relative; isolation: isolate; overflow: hidden; }
    .ab-final-bg { position: absolute; inset: 0; z-index: -2; width: 100%; height: 100%; object-fit: cover; }
    .ab-final-shade { position: absolute; inset: 0; z-index: -1; background: linear-gradient(160deg, rgb(20 36 12 / .92), rgb(29 51 15 / .82) 55%, rgb(47 82 25 / .74)); }
    .ab-final-face { width: 5rem; height: 5rem; margin: 0 auto 1rem; border-radius: 999px; object-fit: cover;
        box-shadow: 0 0 0 4px var(--hp-sun), 0 0 0 12px rgb(245 197 24 / .18); animation: abBob 5s ease-in-out infinite; }

    @media (prefers-reduced-motion: reduce) {
        .ab-float, .ab-final-face, .ab-hero-bg { animation: none !important; }
        .ab-chapter, .ab-chapter-pic img, .ab-pillar, .ab-guide, .ab-guide-go, .ab-stop, .ab-value, .ab-link, .ab-textlink svg { transition: none !important; }
    }
</style>
@endpush
