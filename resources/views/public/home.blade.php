@extends('layouts.public')

@include('public.partials.site-css')
@include('public.partials.hp-base')

@section('title', \App\Support\Region::ph() ? 'Cropping Calendar App for Palay, Mais and Gulay' : 'Cropping Schedule Manager for ' . \App\Support\Region::t('farmersOfTitle'))
@section('meta_description', \App\Support\Region::ph()
    ? 'anee.io is the farm app for Filipino farmers: plan pagtatanim ng palay and mais by day count, track fertilizer and workers, and ask Anee, the AI technician.'
    : 'anee.io helps you plan every cropping season like a pro. Manage lots, activities, workers and costs, ask the AI Technician inside the app, and learn from a community of ' . \App\Support\Region::t('farmersOf') . '. All in one web app that works on any phone. Start free.')

{{-- THE HOMEPAGE (rebuilt 2026-10-05). One argument, told in order:
     the promise (hero), the proof (facts), why plans must bend, what is
     squeezing every farm, what guessing costs, then the answer: the season
     in seven steps, Anee, the app on film, the farm as a business, the plans,
     the crops and guides, the questions, and the last call. Every section
     ends with a way to start, and the owner's narrative (intervention, the
     cost of guessing, the farm as a business) is kept whole.

     The step and film data are How It Works' own (App\Support\HowItWorks),
     so a new tool or a new film shows up here by itself. The page's own
     styles are hp- and live in the push below: utility classes this page
     never used before would paint nothing until the bundle is rebuilt. --}}
@php
    $R = \App\Support\Region::class;
    $HW = \App\Support\HowItWorks::class;
    $ph = $R::ph();
    $peso = $ph ? 'peso' : 'dollar';
    $signup = route('signup');
    $ask = route('ask.page');
    $stages = $HW::stages();
    $tools = collect($stages)->flatMap(fn ($s) => $s['items'])->keyBy('key');
    $toolUrl = fn ($it) => (! empty($it['page']) && $ph) ? \App\Support\SitePages::url('features', $it['page']) : route('how');
    $filmOf = function (string $key, ?string $name = null) use ($HW, $tools) {
        $v = $HW::video($key);
        return $v ? ['key' => $key, 'src' => $v[0], 'poster' => $v[1], 'name' => $name ?? ($tools[$key]['name'] ?? '')] : null;
    };
    $heroFilms = collect([['board', 'Today on the board'], ['growth', 'Growth stages'], ['chat', 'Chat with Anee']])
        ->map(fn ($f) => $filmOf($f[0], $f[1]))->filter()->values();
    $aneePrice = $R::priceTag($R::tierPrice('libreAnee', 'month'));
    $face = asset('images/anee/avatar-160.jpg');
    $faceLg = asset('images/anee/avatar-512.jpg');
    $arrow = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5-5 5M6 12h12"/></svg>';
    $tick = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>';
@endphp

@section('content')

    {{-- ================= HERO ================= --}}
    <section class="hp-hero" data-hero>
        <img src="{{ asset('images/site/photos/hero-planting.jpg') }}" alt="{{ $R::t('farmersOfTitle') }} planting rice, one checking anee.io on his phone"
             class="hp-hero-bg" loading="eager" fetchpriority="high">
        <div class="hp-hero-shade" aria-hidden="true"></div>
        <div class="hp-hero-glow" aria-hidden="true"></div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 hp-hero-in">
            <div class="hp-hero-copy">
                <span class="hp-chip animate-fade-up">
                    <svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M10 2a1 1 0 011 1v1.07A6 6 0 0116 10c0 4-3 6-6 8-3-2-6-4-6-8a6 6 0 015-5.93V3a1 1 0 011-1z"/></svg>
                    {{ $R::t('cropsLine') }}
                </span>
                <h1 class="hp-h1 animate-fade-up" style="animation-delay:.06s">
                    Everything Your Farm Needs for a
                    {{-- The promise, underlined by hand: one gold brush stroke that
                         draws itself under the words once the page has settled
                         (one line only, on the owner's word). The first word
                         takes turns (2026-10-06): higher, stable, bigger... each
                         fading out and the next fading in, the space between
                         them easing to the new word's width. The page itself
                         only ever says "higher"; the others come from
                         data-words, so the heading reads the same to search
                         engines and screen readers. Each word wears its own
                         shimmer: a gradient clipped to text does not reach
                         into a moving child. --}}
                    <span class="hp-mark"><span class="hp-rot" data-words="Higher,Stable,Bigger,Better,Steady,Record,Greater,Maximum"><span class="hp-rot-w hp-shimmer">Higher</span></span> <span class="hp-shimmer">Yield.</span><svg class="hp-mark-line" viewBox="0 0 300 24" preserveAspectRatio="none" aria-hidden="true"><path class="a" d="M5 15 C 55 7, 105 19, 160 12 S 255 6, 295 13"/></svg></span>
                </h1>
                <p class="hp-lede animate-fade-up" style="animation-delay:.12s">
                    @if ($ph)
                        anee.io is the farm app designed for Filipino farmers who grow palay, mais and gulay. From
                        pagtatanim to ani, it helps you manage your crop, keeps track of every peso, and lets you ask
                        Anee, your smart farm technician, any time, in Filipino or English. Just as serious businesses
                        become successful and accurate through systems, smart, successful farmers use anee.io.
                    @else
                        anee.io puts your whole season on your phone: every field counted from its own day zero, every
                        task on its right day, every dollar written down, and Anee, your AI farm technician, ready with
                        answers day and night.
                    @endif
                </p>
                <div class="hp-hero-acts animate-fade-up" style="animation-delay:.18s">
                    <a href="{{ $signup }}" class="btn btn-accent btn-lg hp-go">Create your free account {!! $arrow !!}</a>
                    <a href="{{ $ask }}" class="hp-alt is-glass"><img src="{{ $face }}" alt="" class="hp-face">Ask Anee a free question</a>
                </div>
                <ul class="hp-trust animate-fade-up" style="animation-delay:.24s">
                    <li>{!! $tick !!}Free forever on Libre</li>
                    <li>{!! $tick !!}No card needed</li>
                    <li>{!! $tick !!}Set up in minutes</li>
                </ul>
                <button type="button" class="hp-tour animate-fade-up" style="animation-delay:.3s" data-tour-open>
                    <span class="hp-tour-dot"><svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M8 5.14v13.72L19 12 8 5.14z"/></svg></span>
                    Watch the tour
                </button>
            </div>

            <div class="hp-hero-stage animate-fade-up" style="animation-delay:.15s" data-tilt>
                <div class="hp-stage-ring" aria-hidden="true"></div>
                <div class="hp-phone is-hero">
                    <div class="hp-phone-scr">
                        @if ($heroFilms->isNotEmpty())
                            <video class="hp-film" data-hero-film muted playsinline preload="metadata"
                                   poster="{{ $heroFilms[0]['poster'] }}" data-films="{{ json_encode($heroFilms) }}" aria-label="The anee.io app on a phone">
                                <source src="{{ $heroFilms[0]['src'] }}" type="video/mp4">
                            </video>
                        @endif
                    </div>
                    <span class="hp-film-tag" data-hero-tag>{{ $heroFilms[0]['name'] ?? 'anee.io' }}</span>
                </div>
                <div class="hp-float f1" aria-hidden="true">
                    <span class="hp-float-ico is-green"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3M4 11h16M5 5h14a1 1 0 011 1v13a1 1 0 01-1 1H5a1 1 0 01-1-1V6a1 1 0 011-1z"/></svg></span>
                    <span><b>Today, DAT 21</b><small>Top dress urea on Lot 2</small></span>
                </div>
                <div class="hp-float f2" aria-hidden="true">
                    <span class="hp-float-ico is-sky"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 15a4 4 0 004 4h9a5 5 0 10-.9-9.95A5.5 5.5 0 006.5 8 4.5 4.5 0 003 15z"/></svg></span>
                    <span><b>Rain after 3 PM</b><small>Spray in the morning</small></span>
                </div>
                <div class="hp-float f3" aria-hidden="true">
                    <img src="{{ $face }}" alt="" class="hp-face is-lg">
                    <span><b>Anee</b><small>This looks like sheath blight. Here is what to do.</small></span>
                </div>
            </div>
        </div>

        <a href="#hp-truth" class="hp-down" aria-label="Scroll to read more">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/></svg>
        </a>
    </section>

    {{-- The tour: the long landscape film, in a window over the page. --}}
    <div class="hp-modal" data-tour role="dialog" aria-modal="true" aria-label="The anee.io tour" hidden>
        <div class="hp-modal-veil" data-tour-close></div>
        <div class="hp-modal-box">
            <button type="button" class="hp-modal-x" data-tour-close aria-label="Close the tour">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true"><path stroke-linecap="round" d="M6 6l12 12M18 6L6 18"/></svg>
            </button>
            <video controls playsinline preload="none" poster="{{ asset('images/top-yield.webp') }}" data-tour-video>
                <source src="{{ asset('videos/how-it-works.mp4') }}" type="video/mp4">
            </video>
        </div>
    </div>

    {{-- ================= THE TRUTH ================= --}}
    {{-- The owner's thesis, said first (2026-10-06): traditional farming does
         not pay any more. Costs go up and prices stay low, so the only way up
         is a higher yield, through precision farming, with anee.io. Shown as a
         sum: costs up, plus prices down, equals the one way out. --}}
    <section class="hp-sec hp-truth2" id="hp-truth">
        <div class="max-w-6xl mx-auto px-4 sm:px-6">
            <div class="hp-head reveal">
                <p class="hp-kick is-red">The inconvenient truth</p>
                <h2 class="hp-h2">Traditional Farming Is <em class="is-red">Not Profitable</em> Anymore.</h2>
                <p class="hp-p">
                    Fertilizer, diesel and the extra sprays and work that unpredictable weather forces on you cost
                    more every season. But the price you get for your {{ $ph ? 'palay' : 'harvest' }} stays low. When
                    costs go up and prices stay down, guessing is too expensive. <b>The only way to survive and succeed
                    is a higher yield from every hectare, through precision farming.</b> anee.io helps you do exactly that.
                </p>
            </div>

            <div class="hp-sum">
                <div class="hp-sum-card is-up reveal">
                    <span class="hp-sum-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 19V5m0 0l-6 6m6-6l6 6"/></svg></span>
                    <p class="hp-sum-k">Costs keep going up.</p>
                    <ul class="hp-sum-list">
                        <li>{{ $ph ? 'A sack of urea or complete fertilizer 14 14 14 costs more every season.' : 'Every sack of fertilizer costs more each season.' }}</li>
                        <li>Diesel for the tractor, the pump and every trip to town costs more too.</li>
                        <li>Rain, heat or pests that come early mean extra sprays and work.</li>
                    </ul>
                </div>
                <span class="hp-sum-op" aria-hidden="true">+</span>
                <div class="hp-sum-card is-down reveal" style="--reveal-delay: .12s">
                    <span class="hp-sum-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14m0 0l6-6m-6 6l-6-6"/></svg></span>
                    <p class="hp-sum-k">Prices stay low.</p>
                    <ul class="hp-sum-list">
                        <li>{{ $ph ? 'The palay price at harvest barely moves.' : 'The price at harvest barely moves.' }}</li>
                        <li>Imports can push it down before you sell.</li>
                        <li>You cannot set the price you get.</li>
                    </ul>
                </div>
                <span class="hp-sum-op" aria-hidden="true">=</span>
                <div class="hp-sum-card is-way reveal" style="--reveal-delay: .24s">
                    <span class="hp-sum-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 17l6-6 4 4 8-8m0 0h-5m5 0v5"/></svg></span>
                    <p class="hp-sum-k">The only way up is a <span class="hp-hl">higher yield.</span></p>
                    <p class="hp-sum-p">Yield more from the same hectare with precision farming: the right work, in the right amount, on the right day.</p>
                    <span class="hp-sum-brand"><img src="{{ asset('images/logo-mark.png') }}" alt="" onerror="this.remove()">anee.io shows you how.</span>
                    <ul class="hp-sum-how">
                        <li>It dates every task from each lot's own planting day, so fertilizer and sprays go in on time.</li>
                        <li>Anee checks your crop, its growth stage and the weather before you spend.</li>
                        <li>It writes down every {{ $peso }}, so you can see what works and do it again.</li>
                    </ul>
                </div>
            </div>

            <div class="hp-cta reveal">
                <div class="hp-cta-row">
                    <a href="{{ $signup }}" class="btn btn-accent btn-lg hp-go">Start precision farming, free {!! $arrow !!}</a>
                    <a href="{{ route('how') }}" class="hp-alt">See how it works</a>
                </div>
                <p class="hp-cta-note">Free forever on Libre. No card needed.</p>
            </div>
        </div>
    </section>

    {{-- ================= WHAT GUESSING COSTS ================= --}}
    {{-- The stakes in numbers: what a season bleeds when nobody intervenes,
         and the anee.io answer to each leak. The counters count up and the
         red bars fill when the cards scroll into view. --}}
    <section class="hp-sec hp-loss-dark on-dark">
        <div class="max-w-6xl mx-auto px-4 sm:px-6">
            <div class="hp-head reveal">
                <p class="hp-kick is-red">The real cost of guessing</p>
                <h2 class="hp-h2">Guessing Is the Most <em class="is-red">Expensive</em> Thing on Your Farm.</h2>
                <p class="hp-p">
                    A spray a few days late, a dose that was guessed, planting in the wrong week. Each one looks small,
                    but crop studies show how much harvest they take from every hectare. The harvest just comes in a
                    little smaller, season after season, until it feels normal.
                </p>
                {{-- The owner's point (2026-10-06): the loss is invisible, so the
                     farmer is capping their own income without knowing it. --}}
                <p class="hp-loss-warn">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 3l18 18M10.6 10.6a2 2 0 002.8 2.8M9.9 5.1A9.8 9.8 0 0112 5c4.5 0 8.3 2.9 9.5 7a10 10 0 01-2.9 4.4M6.6 6.6A10 10 0 002.5 12c1.2 4.1 5 7 9.5 7 1.6 0 3.1-.4 4.4-1"/></svg>
                    <span><b>You could be limiting your own income and not even know it.</b></span>
                </p>
            </div>

            <div class="mt-12 grid gap-5 md:grid-cols-2">
                @foreach ([
                    ['n' => 40, 'img' => 'palay-heads.jpg', 'l' => 'Lost to pests and diseases', 'p' => 'When the rice bug, thrips or fall armyworm are treated late, or not at all.', 'peso' => '₱25,000 to ₱40,000'],
                    ['n' => 30, 'img' => 'sacks.jpg', 'l' => 'Wasted on the wrong fix', 'p' => 'If you guess the problem wrong, you pay full price for the wrong product while the real problem grows.', 'peso' => '₱18,000 to ₱30,000'],
                    ['n' => 30, 'img' => 'palay-phone.jpg', 'l' => 'Lost to farm myths', 'p' => 'Remedies heard from others, or lucky planting days, tried on a whole field before anyone checked.', 'peso' => '₱18,000 to ₱30,000'],
                    ['n' => 30, 'img' => 'sacks-shed.jpg', 'l' => 'Lost to spending nobody tracked', 'p' => 'Small costs you never write down add up all season, and you only see them at the end.', 'peso' => '₱18,000 to ₱30,000'],
                    ['n' => 25, 'img' => 'transplant.jpg', 'l' => 'Lost to fertilizer on the wrong day', 'p' => 'Urea fertilizer applied in the wrong week gives the crop only a small part of what you paid for.', 'peso' => '₱15,000 to ₱25,000'],
                    ['n' => 25, 'img' => 'storm-paddies.jpg', 'l' => 'Lost to water at the wrong time', 'p' => 'Too dry at flowering or too wet at ripening, and that harvest does not come back.', 'peso' => '₱15,000 to ₱25,000'],
                    ['n' => 20, 'img' => 'farmer-hijab.jpg', 'l' => 'Lost to waiting for answers', 'p' => 'Days spent waiting for advice while the problem keeps growing.', 'peso' => '₱12,000 to ₱20,000'],
                    ['n' => 20, 'img' => 'hero-planting.jpg', 'l' => 'Lost to planting at the wrong time', 'p' => 'Pagtatanim by habit instead of by the weather shows up as a smaller ani.', 'peso' => '₱12,000 to ₱20,000'],
                ] as $i => $loss)
                    <div class="loss-card loss-card2 reveal" style="--loss: {{ $loss['n'] }}%; --reveal-delay: {{ ($i % 2) * 0.08 }}s">
                        <div class="loss-img"><img src="{{ asset('images/site/photos/' . $loss['img']) }}" alt="" loading="lazy"></div>
                        <div class="loss-body">
                            <p class="loss-upto">Up to</p>
                            <p class="loss-n"><span data-countup="{{ $loss['n'] }}">0</span><small>%</small></p>
                            <p class="loss-l">{{ $loss['l'] }}</p>
                            <p class="loss-p">{{ $loss['p'] }}</p>
                            <p class="loss-peso">{{ $ph ? $loss['peso'] . ' lost per hectare' : 'up to ' . $loss['n'] . '% of what a hectare earns, lost' }}</p>
                            <div class="loss-bar" aria-hidden="true"><i></i></div>
                        </div>
                    </div>
                @endforeach
            </div>

            <p class="hp-loss-src reveal">
                Percent ranges come from FAO crop loss and {{ $ph ? 'Philippine rice' : 'published crop' }} research estimates{{ $ph ? '. Peso ranges assume a palay hectare that earns ₱85,000 to ₱100,000 before costs' : '' }}. Your own numbers will be different.
            </p>

            <div class="hp-cta reveal">
                <div class="hp-cta-row">
                    <a href="{{ $signup }}" class="btn btn-accent btn-lg hp-go">Start free and stop the losses {!! $arrow !!}</a>
                    <a href="{{ $ask }}" class="hp-alt"><img src="{{ $face }}" alt="" class="hp-face">Ask Anee about your crop</a>
                </div>
                <p class="hp-cta-note">The daily task list, the growth stages and the labor report are free.</p>
            </div>
        </div>
    </section>

    {{-- ================= WHY: FARMING WITH PRECISION ================= --}}
    {{-- The argument the whole site rests on: the old calendar stopped being
         enough. Modern farming wins by precision (the right work, the right
         amount, the right day), and that is the app's job. The owner asked
         for the word "precision" and for plain words (2026-10-05). --}}
    <section class="hp-why spark-field on-dark">
        <img src="{{ asset('images/site/photos/storm-paddies.jpg') }}" alt="Farmers transplanting rice under a heavy grey sky" class="hp-why-bg" loading="lazy">
        <div class="hp-why-shade" aria-hidden="true"></div>
        <div class="relative max-w-6xl mx-auto px-4 sm:px-6 hp-sec" style="z-index:1">
            <div class="hp-head reveal">
                <p class="hp-kick">The old calendar is not enough</p>
                <h2 class="hp-h2">Modern Farming Wins by <em>Precision.</em></h2>
                <p class="hp-p">
                    The weather no longer follows the old planting calendar. Rain comes early, dry spells last
                    longer, and pests show up before you expect them. The farmers who do well today do <b>the right
                    work, in the right amount, on the right day</b>. anee.io helps you do exactly that.
                </p>
            </div>

            <div class="hp-why-grid">
                @foreach ([
                    ['The weather changes.', 'A dry spell goes on, or rain comes right before your spray day.',
                     'You see the forecast for each lot ahead of time. If you need to move the plan, drag it and every date moves with it.',
                     '<path stroke-linecap="round" stroke-linejoin="round" d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999A5.002 5.002 0 105.9 12.1 4 4 0 003 15zM13 21l-2 2m6-4l-2 2m-8-2l-2 2"/>'],
                    ['Pests come early.', 'Yellow leaves or holes appear, like from the rice bug or thrips, and help is days away.',
                     'Take a photo and Anee tells you what it is and what to do, so you treat the right problem with the right dose today.',
                     '<path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2m0 0a7 7 0 017 7v3a3 3 0 01-3 3H8a3 3 0 01-3-3v-3a7 7 0 017-7zM9 12h.01M15 12h.01M9.5 17h5"/>'],
                    ['The crop grows faster or slower.', 'Hot days make it grow faster than the plan. Cool days slow it down.',
                     'See the growth stage of each lot on any day, with what to do and what to watch for. Anee can check the real stage for you.',
                     '<path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>'],
                    ['Costs go up.', 'One extra spray, one job done twice, and your profit gets smaller without you noticing.',
                     'Labor, materials and services add up in ' . $R::symbol() . ' as you go, so you know the cost before you spend.',
                     '<path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>'],
                ] as $i => [$t, $w, $a, $ico])
                    <div class="hp-why-card reveal" style="--reveal-delay: {{ $i * 0.08 }}s">
                        <div class="hp-why-top">
                            <span class="hp-why-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">{!! $ico !!}</svg></span>
                            <div>
                                <p class="hp-why-when">The problem</p>
                                <h3 class="hp-why-t">{{ $t }}</h3>
                            </div>
                        </div>
                        <p class="hp-why-w">{{ $w }}</p>
                        <div class="hp-why-a">
                            <span class="hp-why-badge">{!! $tick !!}What anee.io does</span>
                            <p>{{ $a }}</p>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="hp-cta reveal">
                <div class="hp-cta-row">
                    <a href="{{ $signup }}" class="btn btn-accent btn-lg hp-go">Start free and farm with precision {!! $arrow !!}</a>
                </div>
                <p class="hp-cta-note">Your first season plan is free, and it moves when the weather does.</p>
            </div>
        </div>
    </section>

    {{-- ================= THE SEASON IN SEVEN STEPS ================= --}}
    {{-- The answer, shown: How It Works' seven steps as tabs. Each step lists
         its tools, and the phone plays the step's films (the same phone
         recordings the How It Works modal plays). The tabs walk on by
         themselves while the section is in view, until a visitor takes over. --}}
    <section class="hp-sec bg-brand-mesh hp-steps" data-steps>
        <div class="max-w-7xl mx-auto px-4 sm:px-6">
            <div class="hp-head reveal">
                <p class="hp-kick">{{ $ph ? 'From pagtatanim to ani' : 'From planting to harvest' }}</p>
                <h2 class="hp-h2">Your Whole Season in <em>{{ count($stages) }} Steps.</em></h2>
                <p class="hp-p">Each step has its own tools, and Anee helps in every one. Tap a step, then a tool, to see it work on a real phone.</p>
            </div>

            <div class="hp-st reveal">
                <div class="hp-st-list" role="tablist" aria-label="The steps of a season">
                    @foreach ($stages as $i => $st)
                        <button type="button" role="tab" class="hp-st-tab{{ $i === 0 ? ' is-on' : '' }}" data-step="{{ $i }}"
                                id="hpTab{{ $i }}" aria-controls="hpPane{{ $i }}" aria-selected="{{ $i === 0 ? 'true' : 'false' }}">
                            <span class="hp-st-n">{{ $i + 1 }}</span>
                            <span class="hp-st-tt"><b>{{ $st['title'] }}</b><small>{{ $st['when'] }}</small></span>
                            <span class="hp-st-bar" aria-hidden="true"><i></i></span>
                        </button>
                    @endforeach
                </div>

                <div class="hp-st-body">
                    <div class="hp-st-panes">
                        @foreach ($stages as $i => $st)
                            <div class="hp-st-pane{{ $i === 0 ? ' is-on' : '' }}" role="tabpanel" id="hpPane{{ $i }}" aria-labelledby="hpTab{{ $i }}" data-pane="{{ $i }}">
                                <p class="hp-st-when">Step {{ $i + 1 }} · {{ $st['when'] }}</p>
                                <h3 class="hp-st-h">{{ $st['title'] }}</h3>
                                <p class="hp-st-p">{{ $st['lede'] }}</p>
                                <div class="hp-tools">
                                    @foreach ($st['items'] as $j => $it)
                                        @php $f = $filmOf($it['key']); @endphp
                                        <button type="button" class="hp-tool{{ $j === 0 ? ' is-on' : '' }}" style="--j: {{ $j }}"
                                                @if ($f) data-src="{{ $f['src'] }}" data-poster="{{ $f['poster'] }}" @endif
                                                data-name="{{ $it['name'] }}" data-short="{{ $it['short'] }}" data-link="{{ $toolUrl($it) }}">
                                            <span class="hp-tool-ico{{ str_starts_with($it['icon'], 'anee/') ? ' is-face' : '' }}"><img src="{{ asset('images/' . $it['icon']) }}" alt="" loading="lazy"></span>
                                            <span class="hp-tool-t">{{ $it['name'] }}@if ($it['anee'])<i class="hp-anee-tag">Anee</i>@endif</span>
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="hp-st-show">
                        @php $first = $filmOf($stages[0]['items'][0]['key']); @endphp
                        <div class="hp-phone">
                            <div class="hp-phone-scr">
                                <video class="hp-film" data-step-film muted playsinline loop preload="none" poster="{{ $first['poster'] ?? '' }}" aria-label="{{ $stages[0]['items'][0]['name'] }} on a phone">
                                    @if ($first)<source src="{{ $first['src'] }}" type="video/mp4">@endif
                                </video>
                            </div>
                            <span class="hp-film-tag" data-step-tag>{{ $stages[0]['items'][0]['name'] }}</span>
                        </div>
                        <p class="hp-st-short" data-step-short>{{ $stages[0]['items'][0]['short'] }}</p>
                        <a href="{{ $toolUrl($stages[0]['items'][0]) }}" class="hp-st-more" data-step-link>Read more about it {!! $arrow !!}</a>
                    </div>
                </div>
            </div>

            <div class="hp-cta reveal">
                <div class="hp-cta-row">
                    <a href="{{ $signup }}" class="btn btn-accent btn-lg hp-go">Start your first season free {!! $arrow !!}</a>
                    <a href="{{ route('how') }}" class="hp-alt">See all {{ $tools->count() }} tools step by step</a>
                </div>
                <p class="hp-cta-note">The season board, your lot and the growth stages are free. The other tools come with the paid plans below.</p>
            </div>
        </div>
    </section>

    {{-- ================= MEET ANEE ================= --}}
    {{-- Anee in action: a farmer's photo question, what she reads first, and
         her answer, played once as the band scrolls into view (the How It
         Works chat, word for word). Her price is said plainly: she is not on
         Libre, and the free question needs no account. --}}
    @php $chat = $HW::chat(); $aneeTools = $tools->filter(fn ($it) => $it['anee'])->values(); @endphp
    <section class="hp-anee spark-field on-dark">
        <div class="relative max-w-6xl mx-auto px-4 sm:px-6 hp-sec" style="z-index:1">
            <div class="hp-anee-grid">
                <div class="reveal">
                    <p class="hp-kick">Meet Anee</p>
                    <h2 class="hp-h2">Your AI Farm Technician <em>Knows Your Farm.</em></h2>
                    <p class="hp-p">
                        Anee is not a regular chatbot. Before she answers, she looks at your lots, their growth stages,
                        your records and the weather. So when you ask "should I spray tomorrow?", she answers for your
                        own field. Ask {{ $ph ? 'in Tagalog or English, ' : '' }}and send a photo if you like.
                    </p>
                    <div class="hp-powers">
                        @foreach ($aneeTools as $i => $it)
                            <a href="{{ $toolUrl($it) }}" class="hp-power reveal" style="--reveal-delay: {{ $i * 0.04 }}s">
                                <img src="{{ asset('images/' . $it['icon']) }}" alt="" loading="lazy">{{ $it['name'] }}
                            </a>
                        @endforeach
                    </div>
                    <p class="hp-anee-price">
                        Anee comes with Libre + Anee for {{ $aneePrice }} a month, and with every plan above it.
                        Want to try her first? Ask one question free each week. No account needed.
                    </p>
                    <div class="hp-cta is-left">
                        <div class="hp-cta-row">
                            <a href="{{ $ask }}" class="btn btn-accent btn-lg hp-go">Ask Anee a free question {!! $arrow !!}</a>
                            <a href="{{ $signup }}" class="hp-alt">Create your free account</a>
                        </div>
                    </div>
                </div>

                <div class="reveal">
                    <div class="hp-chat" data-chat>
                        <div class="hp-chat-top">
                            <img src="{{ $face }}" alt="" class="hp-face is-lg">
                            <span><b>Anee</b><small><i class="hp-live"></i>Your AI farm technician</small></span>
                        </div>
                        <div class="hp-chat-body">
                            <div class="hp-msg is-me">
                                <img src="{{ asset($chat['photo']) }}" alt="A rice leaf with pale blotches that have brown edges" class="hp-msg-photo" loading="lazy">
                                <p>{{ $chat['question'] }}</p>
                            </div>
                            <div class="hp-reading">
                                @foreach ($chat['reading'] as $k => $r)
                                    <span class="hp-read" style="--k: {{ $k }}"><i aria-hidden="true"></i>{{ $r }}</span>
                                @endforeach
                            </div>
                            <div class="hp-msg is-anee">
                                <b>{{ $chat['lead'] }}</b>
                                <p>{{ $chat['body'] }}</p>
                                <ol>
                                    @foreach ($chat['steps'] as $s)<li>{!! $s !!}</li>@endforeach
                                </ol>
                            </div>
                        </div>
                        <button type="button" class="hp-chat-again" data-chat-again>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h5M5.1 15a7.5 7.5 0 1 0 1.4-7.5L4 9"/></svg>
                            Play again
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ================= SEE IT WORK ================= --}}
    {{-- Eight of the phone recordings, each playing while it is on screen. On
         a phone they sit in a row you swipe through. --}}
    @php
        $reel = collect(['board', 'growth', 'weather', 'offline', 'stock', 'capture', 'profit', 'community'])
            ->map(fn ($k) => isset($tools[$k]) ? array_merge($tools[$k], ['film' => $filmOf($k)]) : null)
            ->filter(fn ($x) => $x && $x['film'])->values();
    @endphp
    @if ($reel->isNotEmpty())
    <section class="hp-sec bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6">
            <div class="hp-head reveal">
                <p class="hp-kick">See the app</p>
                <h2 class="hp-h2">See the Real App <em>on a Real Phone.</em></h2>
                <p class="hp-p">Short recordings of anee.io, exactly as you will use it in the field.</p>
            </div>

            <div class="hp-reel" data-reel>
                @foreach ($reel as $i => $it)
                    <figure class="hp-reel-card reveal" style="--reveal-delay: {{ ($i % 4) * 0.07 }}s">
                        <div class="hp-phone is-sm">
                            <div class="hp-phone-scr">
                                <video class="hp-film" muted playsinline loop preload="none" poster="{{ $it['film']['poster'] }}" data-reel-film aria-label="{{ $it['name'] }} on a phone">
                                    <source src="{{ $it['film']['src'] }}" type="video/mp4">
                                </video>
                            </div>
                        </div>
                        <figcaption>
                            <b>{{ $it['name'] }}</b>
                            <span>{{ $it['short'] }}</span>
                            <a href="{{ $toolUrl($it) }}">Read more {!! $arrow !!}</a>
                        </figcaption>
                    </figure>
                @endforeach
            </div>

            <div class="hp-cta reveal">
                <div class="hp-cta-row">
                    <a href="{{ $signup }}" class="btn btn-accent btn-lg hp-go">Try it on your own farm, free {!! $arrow !!}</a>
                    <a href="{{ route('features') }}" class="hp-alt">See every feature</a>
                </div>
                <p class="hp-cta-note">Some tools come with the paid plans. The plans below say which.</p>
            </div>
        </div>
    </section>
    @endif

    {{-- ================= YOUR FARM IS A BUSINESS, BUILT BY FARMERS ================= --}}
    {{-- The second half of the argument (every business already upgraded,
         it is the farm's turn), and who is saying it: farmers, whose own
         farms run on this system. --}}
    <section class="hp-sec bg-brand-mesh bg-drift">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 hp-biz">
            <div class="hp-biz-pics reveal">
                <img src="{{ asset('images/site/photos/powered-by.jpg') }}" alt="Three farmers in their rice field with a Powered by anee.io sign" class="hp-biz-a" loading="lazy">
                <img src="{{ asset('images/site/photos/inspect.jpg') }}" alt="A farmer in her rice field, checking the season on anee.io" class="hp-biz-b" loading="lazy">
                <span class="hp-biz-pill"><i class="hp-live"></i>In our own fields, every day</span>
            </div>
            <div class="reveal">
                <p class="hp-kick">Your farm is a business</p>
                <h2 class="hp-h2">Your Farm Is a Business. <em>Run It Like One.</em></h2>
                <p class="hp-p">
                    The sari-sari store takes GCash, tricycles are booked by app, and the trader who buys your
                    {{ $R::t('rice') }} keeps records on a computer. They all earn more because they keep good
                    records. Most farms still run on memory and an old notebook. {{ $ph ? 'From the palayan of Nueva Ecija to the vegetable farms of Benguet, ' : '' }}your
                    farm deserves the same tools, made for the field and priced for farmers.
                </p>
                <div class="hp-biz-built">
                    <p class="hp-biz-h">Built by farmers. Run on our own farms.</p>
                    <p class="hp-biz-p">anee.io was not made in an office. We use it on our own farms every day, and every tool in it is there because we needed it first.</p>
                </div>
                <div class="hp-gains">
                    @foreach ([
                        ['Higher yield', 'Every job on its right day.', '<path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>'],
                        ['Lower cost', 'Every ' . $peso . ' written down.', '<path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>'],
                        ['Exact numbers', 'Reports that match to the ' . ($ph ? 'peso' : 'cent') . '.', '<path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>'],
                        ['A smarter next season', 'Each season teaches the next.', '<path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>'],
                    ] as $i => [$t, $p, $ico])
                        <div class="hp-gain reveal" style="--reveal-delay: {{ $i * 0.07 }}s">
                            <span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">{!! $ico !!}</svg></span>
                            <div><b>{{ $t }}</b><small>{{ $p }}</small></div>
                        </div>
                    @endforeach
                </div>
                <div class="hp-cta is-left">
                    <div class="hp-cta-row">
                        <a href="{{ $signup }}" class="btn btn-accent btn-lg hp-go">Start free and upgrade your farm {!! $arrow !!}</a>
                        <a href="{{ route('about') }}" class="hp-alt">Read our story</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ================= TRADITIONAL vs ANEE.IO ================= --}}
    <section class="hp-sec bg-gray-50">
        <div class="max-w-6xl mx-auto px-4 sm:px-6">
            <div class="hp-head reveal">
                <p class="hp-kick">Why farmers switch</p>
                <h2 class="hp-h2">Traditional Farming <em>vs anee.io</em></h2>
                <p class="hp-p">Your season does not have to live in your head or on loose paper. Here is what changes when the whole plan is in one place.</p>
            </div>

            @php
                $compare = [
                    ['Season planning', 'Kept in your head or scattered across notebooks.', 'One clear plan for each season and each farm, always with you.'],
                    ['Activity timing', 'Guessed from memory. Easy to spray or fertilize a few days late.', 'Every task dated from each lot\'s day zero. Right day, every time.'],
                    ['Labor cost', 'Added up by hand at the end, often a nasty surprise.', 'Worker rates add up in ' . $R::symbol() . ' as the work gets done.'],
                    ['Materials and budget', 'Rough estimates. Overspending creeps in unnoticed.', 'What the plan needs against what is in the shed, priced ahead.'],
                    ['Expert advice', $R::t('techVisit'), 'Anee answers crop questions anytime, even from a photo of a leaf.'],
                    ['Records and photos', 'Little proof of what was done, and when.', 'Photos, voice notes and notes kept with every task and stage.'],
                    ['The whole team', 'Hard to hand over to family or workers.', 'Each worker gets the day\'s plan by email at 6 AM, and their own login if you allow it.'],
                    ['Next season', 'Starts from memory again.', 'Starts from your best lot\'s real record, ready to repeat.'],
                ];
            @endphp

            <div class="hp-vs reveal">
                <div class="hp-vs-head">
                    <span></span>
                    <span class="is-old">By memory and paper</span>
                    <span class="is-new"><img src="{{ asset('images/logo-mark.png') }}" alt="" onerror="this.remove()">With anee.io</span>
                </div>
                @foreach ($compare as $i => [$dim, $old, $new])
                    <div class="hp-vs-row" style="--i: {{ $i }}">
                        <b class="hp-vs-dim">{{ $dim }}</b>
                        <p class="hp-vs-old"><span aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6"><path stroke-linecap="round" d="M6 6l12 12M18 6L6 18"/></svg></span><em class="hp-vs-label">Traditional</em>{{ $old }}</p>
                        <p class="hp-vs-new"><span aria-hidden="true">{!! $tick !!}</span><em class="hp-vs-label">With anee.io</em>{{ $new }}</p>
                    </div>
                @endforeach
            </div>

            <div class="hp-cta reveal">
                <div class="hp-cta-row">
                    <a href="{{ $signup }}" class="btn btn-accent btn-lg hp-go">Start planning the anee.io way, free {!! $arrow !!}</a>
                </div>
                <p class="hp-cta-note">Your notebook can retire gently.</p>
            </div>
        </div>
    </section>

    {{-- ================= PRICING: THE TIERS ================= --}}
    <section class="hp-sec bg-white bg-drift" id="pricing" x-data="{ yearly: false }">
        <div class="max-w-6xl mx-auto px-4 sm:px-6">
            <div class="hp-head reveal">
                <p class="hp-kick">Simple pricing</p>
                <h2 class="hp-h2">Start Free. <em>Upgrade When You Need More.</em></h2>
                <p class="hp-p">Libre is free forever. When your farm needs more, upgrade inside the app and pay with {{ $R::payMethod() }}.</p>
                <div class="hp-billing" role="group" aria-label="Billing">
                    <button type="button" :class="yearly ? '' : 'is-on'" @click="yearly = false">Monthly</button>
                    <button type="button" :class="yearly ? 'is-on' : ''" @click="yearly = true">Yearly <span>save more</span></button>
                </div>
            </div>

            <div class="mt-12 pr-grid">
                @foreach ($tiers as $key => $tier)
                    @php $isStar = $key === 'owner'; @endphp
                    <div class="pr-card hp-pr reveal {{ $isStar ? 'is-star' : '' }}" style="--reveal-delay: {{ $loop->index * 0.07 }}s">
                        @if ($isStar)<span class="pr-flag">Most complete</span>@endif
                        <span class="pr-name">{{ $tier['name'] }}</span>
                        <span class="pr-for">{{ $tier['tagline'] }}</span>

                        @if (empty($tier['price']))
                            <span class="pr-price">
                                <span class="pr-amount is-free">Free</span>
                                <span class="pr-per">forever</span>
                            </span>
                            <span class="pr-year">No card. No time limit. Yours to keep.</span>
                        @else
                            @php $hpM = $R::tierPrice($key, 'month'); $hpY = $R::tierPrice($key, 'year'); @endphp
                            <span class="pr-price" x-show="!yearly">
                                <span class="pr-amount">{{ $R::priceTag($hpM) }}</span>
                                <span class="pr-per">a month</span>
                            </span>
                            <span class="pr-price" x-show="yearly" x-cloak>
                                <span class="pr-amount">{{ $R::priceTag($hpY) }}</span>
                                <span class="pr-per">a year</span>
                            </span>
                            <span class="pr-year" x-show="!yearly">or {{ $R::priceTag($hpY) }} a year, about {{ $R::priceTag(round($hpY / 12, 2)) }} a month</span>
                            <span class="pr-year" x-show="yearly" x-cloak>About {{ $R::priceTag(round($hpY / 12, 2)) }} a month, paid once with {{ $R::payMethod() }}</span>
                        @endif

                        <ul class="pr-list">
                            @foreach ($tier['features'] as $feature)
                                <li>{!! $tick !!}{{ $feature }}</li>
                            @endforeach
                            @foreach ($tier['excludes'] as $missing)
                                <li class="is-off"><svg fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>{{ $missing }}</li>
                            @endforeach
                        </ul>

                        <a href="{{ $signup }}" class="btn {{ $isStar ? 'btn-accent hp-go' : (empty($tier['price']) ? 'btn-primary' : 'btn-outline') }}">
                            {{ empty($tier['price']) ? 'Start for free' : 'Start free, then upgrade' }}
                        </a>
                    </div>
                @endforeach
            </div>

            <div class="hp-cta reveal">
                <div class="hp-cta-row">
                    <a href="{{ $signup }}" class="btn btn-accent btn-lg hp-go">Create your free account {!! $arrow !!}</a>
                    <a href="{{ route('pricing') }}" class="hp-alt">See the full pricing page</a>
                </div>
                <p class="hp-cta-note">Every account starts free on Libre. You upgrade inside the app, pay with {{ $R::payMethod() }}, and our team checks the payment.</p>
            </div>
        </div>
    </section>

    {{-- ================= EVERY CROP YOU GROW (the Philippine face) ================= --}}
    @if ($ph)
    @php
        $crops = [
            ['Palay', 'M12 21v-9m0 0C12 7 8 5 4 5c0 4 3 7 8 7zm0 0c0-4 3-7 8-7 0 4-4 7-8 7z', 100,
                'Plan pagtatanim ng palay from the rice seeds and the punla to the ani. Fertilizer urea and complete fertilizer 14 14 14 go on their day after transplanting, and a reminder to check for the rice bug (alitangya) comes before the milk stage.',
                [['/crops/palay', 'Palay guide'], ['/crops/rice-varieties-philippines', 'Rice varieties'], ['/problems/rice-bug', 'Rice bug']]],
            ['Mais', 'M12 3c-2.5 2-4 5-4 9s1.5 7 4 9c2.5-2 4-5 4-9s-1.5-7-4-9zm0 4v10M9.5 9.5L12 11l2.5-1.5M9.5 13.5L12 15l2.5-1.5', 45,
                'Yellow or white corn, counted by days after planting: corn seeds and spacing, fertilizer days, fall armyworm checks, and the corn kernel at harvest.',
                [['/crops/corn-seeds', 'Corn seeds'], ['/crops/corn-kernel', 'Corn kernel'], ['/problems/fall-armyworm', 'Fall armyworm']]],
            ['Gulay', 'M12 21c-4.4 0-8-3.1-8-7 0-3.3 2.6-6 6-6.8V4h4v3.2c3.4.8 6 3.5 6 6.8 0 3.9-3.6 7-8 7z', 150,
                'Pechay, tomato, eggplant and ampalaya, the vegetables in the Philippines that farms grow most, on one calendar with foliar fertilizer days and thrips and anthracnose checks.',
                [['/crops/vegetables-philippines', 'Vegetables guide'], ['/crops/pagtatanim-ng-gulay', 'Pagtatanim ng gulay'], ['/problems/thrips', 'Thrips']]],
            ['Niyog, saging at puno', 'M12 21v-8m0 0c-3 0-6-2-7-5 3 0 5 1 7 3m0 2c3 0 6-2 7-5-3 0-5 1-7 3m0-3V3', 30,
                'Coconut, banana and fruit trees count their age in months, with fertilizer plans that follow the PCA and DA guides.',
                [['/crops/coconut-fertilizer', 'Coconut fertilizer'], ['/crops/banana-farming-philippines', 'Banana farming'], ['/crops/pagtatanim-ng-puno', 'Pagtatanim ng puno']]],
        ];
    @endphp
    <section class="hp-sec bg-gray-50">
        <div class="max-w-6xl mx-auto px-4 sm:px-6">
            <div class="hp-head reveal">
                <p class="hp-kick">Palay, mais, gulay and more</p>
                <h2 class="hp-h2">One Cropping Calendar Works for <em>Every Crop You Grow.</em></h2>
                <p class="hp-p">anee.io knows 85 Philippine crops, from palay and mais to gulay and fruit trees. Set the day you sow, transplant or plant, and every task after it gets its day count.</p>
            </div>
            <div class="mt-12 grid gap-5 sm:grid-cols-2">
                @foreach ($crops as $i => [$name, $icon, $hue, $text, $links])
                    <div class="hc-card reveal" style="--h: {{ $hue }}; --reveal-delay: {{ ($i % 2) * 0.06 }}s">
                        <span class="hc-ico"><svg fill="none" stroke="currentColor" stroke-width="1.9" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}"/></svg></span>
                        <div class="min-w-0">
                            <h3 class="font-heading text-xl font-bold text-ink">{{ $name }}</h3>
                            <p class="mt-2 text-sm sm:text-[15px] text-gray-600 leading-relaxed">{{ $text }}</p>
                            <div class="mt-3 flex flex-wrap gap-2">
                                @foreach ($links as [$href, $label])
                                    <a href="{{ url($href) }}" class="hc-link">{{ $label }} ›</a>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="hp-cta reveal">
                <div class="hp-cta-row">
                    <a href="{{ $signup }}" class="btn btn-accent btn-lg hp-go">Plan your crop free {!! $arrow !!}</a>
                </div>
                <p class="hp-cta-note">Pick your crop when you add a lot. The day counts and the growth stages follow.</p>
            </div>
        </div>
    </section>
    @endif

    {{-- ================= GUIDES (the Philippine face) ================= --}}
    @if (! empty($guides))
    @include('public.site.css')
    <section class="hp-sec bg-white">
        <div class="max-w-6xl mx-auto px-4 sm:px-6">
            <div class="hp-head reveal">
                <p class="hp-kick">Free farm guides</p>
                <h2 class="hp-h2">Read Free Guides Made <em>for Filipino Farmers.</em></h2>
                <p class="hp-p">How to plant palay and mais, what to do about the rice bug and the rice black bug, and how much fertilizer a hectare needs. Written for Philippine farms and free to read.</p>
            </div>
            {{-- What farmers search for most, each a link to the guide that answers it. --}}
            <div class="hp-topics reveal">
                @foreach ([
                    ['/crops/palay', 'Palay'], ['/crops/pagtatanim-ng-palay', 'Pagtatanim ng palay'], ['/blog/palayan-nueva-ecija', 'Palayan in Nueva Ecija'],
                    ['/blog/ani-meaning', 'Ani meaning'], ['/blog/urea-fertilizer', 'Fertilizer urea'], ['/blog/complete-fertilizer-14-14-14', 'Fertilizer 14 14 14'],
                    ['/blog/16-20-0-fertilizer', '16 20 0 fertilizer'], ['/blog/ammonium-sulfate-21-0-0', '21 0 0 fertilizer'], ['/blog/foliar-fertilizer', 'Foliar fertilizer'], ['/blog/organic-fertilizer-examples', 'Examples of organic fertilizer'],
                    ['/blog/fertilizer-for-plants', 'Fertilizer for plants'], ['/blog/fungicides', 'Fungicide guide'], ['/blog/fertilizer-and-pesticide-authority', 'Fertilizer and Pesticide Authority'], ['/problems/rice-bug', 'Rice bug'],
                    ['/problems/rice-black-bug', 'Rice black bug'], ['/problems/thrips', 'Thrips insect'], ['/problems/fall-armyworm', 'Fall armyworm'], ['/problems/cutworm', 'Cutworm'],
                    ['/crops/rice-varieties-philippines', 'Rice varieties in the Philippines'], ['/blog/palay-price-philippines', 'Palay price in the Philippines'],
                    ['/crops/corn-kernel', 'Corn kernel'], ['/crops/corn-seeds', 'Corn seeds'], ['/crops/vegetables-philippines', 'Vegetables in the Philippines'],
                ] as $i => [$href, $label])
                    <a href="{{ url($href) }}" class="hp-topic" style="--h: {{ ($i * 41) % 150 + 30 }}">{{ $label }}</a>
                @endforeach
            </div>
            <div class="mt-12 grid gap-8 lg:grid-cols-3">
                @foreach ($guides as $sec => $pages)
                    @php $SP = \App\Support\SitePages::class; @endphp
                    <div class="hg-col reveal" style="--reveal-delay: {{ $loop->index * 0.07 }}s">
                        <p class="hg-kick">{{ $SP::SECTIONS[$sec]['label'] }}</p>
                        @include('public.site.tile', ['p' => $pages->first()])
                        <ul class="hg-list">
                            @foreach ($pages->slice(1) as $p)
                                <li><a href="{{ $SP::pageUrl($p) }}">{{ $SP::shortTitle($p) }}</a></li>
                            @endforeach
                        </ul>
                        <a href="{{ $SP::url($sec) }}" class="hg-all">{{ ['crops' => 'All crop guides', 'problems' => 'All crop problems', 'blog' => 'The whole blog'][$sec] }} ›</a>
                    </div>
                @endforeach
            </div>
            <div class="hp-cta reveal">
                <div class="hp-cta-row">
                    <a href="{{ $signup }}" class="btn btn-accent btn-lg hp-go">Put the guide on your calendar {!! $arrow !!}</a>
                    <a href="{{ $ask }}" class="hp-alt"><img src="{{ $face }}" alt="" class="hp-face">Ask Anee about your crop</a>
                </div>
                <p class="hp-cta-note">Reading the guides is free. Planning your own season with them is free too.</p>
            </div>
        </div>
    </section>
    @endif

    {{-- ================= QUESTIONS (the Philippine face) ================= --}}
    @if ($ph)
    @php
        $faqs = [
            ['What is anee.io?',
             'anee.io is a farm app for Filipino farmers. It keeps your cropping calendar, lots, workers, fertilizer and costs for the whole season in one place, and Anee, the AI agricultural technician, answers questions about your crop.'],
            ['Can I plan pagtatanim ng palay in anee.io?',
             'Yes. Set the day you sow or transplant and every task gets its day count: basal fertilizer, urea top dressing, weeding, water and harvest. Each lot keeps its own day zero, so a lot planted a week late keeps its own timing.'],
            ['Does it work for mais, gulay and fruit trees?',
             'Yes. anee.io knows 85 Philippine crops, from palay and mais to vegetables, coconut, banana and fruit trees. Trees and other perennials count their age in months.'],
            ['Can Anee answer in Tagalog?',
             'Yes. Anee answers in Tagalog or English. She reads your schedule, growth stages and weather first, and she can look at a photo of a pest or a sick leaf. She comes with the Libre + Anee plan and up, and anyone can ask her one free question a week on the Try and Ask Anee page.'],
            ['Is anee.io free?',
             'Yes. The Libre plan is free forever with one active cropping schedule. Libre + Anee adds the AI technician for ' . $aneePrice . ' a month, and the Solo Farmer and Farm Owner plans add workers, inventory, all reports and offline mode.'],
            ['Do I need a computer?',
             'No. anee.io runs in the browser of any phone, so you plan and tick tasks right in the field. On the Solo Farmer and Farm Owner plans it keeps working where there is no signal and syncs when the signal returns.'],
            ['Can my workers use it too?',
             'Yes. Every morning at 6 AM the team gets the day\'s plan by email. On the Farm Owner plan each worker can have their own login, and you decide what they may see and change.'],
            ['Can anee.io help with the rice bug, thrips and fall armyworm?',
             'Yes. Take a photo and Anee tells you what the pest or disease is and what to do, including when a fungicide or insecticide is needed. Your season board also reminds you when to check for the rice bug and other pests at each growth stage.'],
            ['Where can I read about fertilizer and pests?',
             'Our free guides cover fertilizer urea, complete fertilizer 14 14 14 and 16 20 0, the rice bug, thrips, fall armyworm and more. Start from the crop guides, the crop problems or the blog.'],
        ];
    @endphp
    <section class="hp-sec bg-gray-50">
        <div class="max-w-3xl mx-auto px-4 sm:px-6">
            <div class="hp-head reveal">
                <p class="hp-kick">Questions</p>
                <h2 class="hp-h2">Here Is What Farmers Ask <em>About anee.io.</em></h2>
            </div>
            <div class="mt-10 space-y-3" x-data="{ open: 0 }">
                @foreach ($faqs as $i => [$q, $a])
                    <div class="hp-q reveal" :class="{ 'is-open': open === {{ $i }} }" style="--reveal-delay: {{ $i * 0.04 }}s">
                        <button type="button" class="hp-q-btn" @click="open = open === {{ $i }} ? -1 : {{ $i }}" :aria-expanded="open === {{ $i }}">
                            <span>{{ $q }}</span>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true"><path stroke-linecap="round" d="M12 5v14M5 12h14"/></svg>
                        </button>
                        {{-- Always in the page (search engines read it), folded by height. --}}
                        <div class="hq-body" :class="{ 'is-open': open === {{ $i }} }">
                            <div><p class="px-5 pb-5 text-gray-600 leading-relaxed">{{ $a }}</p></div>
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="mt-6 flex flex-wrap justify-center gap-2 text-sm">
                <a href="{{ url('/crops') }}" class="hc-link" style="--h: 100">Crop guides ›</a>
                <a href="{{ url('/problems') }}" class="hc-link" style="--h: 30">Crop problems ›</a>
                <a href="{{ url('/blog') }}" class="hc-link" style="--h: 150">The blog ›</a>
            </div>
            <div class="hp-cta reveal">
                <div class="hp-cta-row">
                    <a href="{{ $signup }}" class="btn btn-accent btn-lg hp-go">Create your free account {!! $arrow !!}</a>
                    <a href="{{ $ask }}" class="hp-alt"><img src="{{ $face }}" alt="" class="hp-face">Still unsure? Ask Anee</a>
                </div>
            </div>
        </div>
    </section>
    @push('head')
    <script type="application/ld+json">{!! json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'FAQPage',
        'mainEntity' => array_map(fn ($f) => ['@type' => 'Question', 'name' => $f[0], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $f[1]]], $faqs),
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
    @endpush
    @endif

    {{-- ================= FINAL CTA ================= --}}
    <section class="hp-final" data-final>
        <img src="{{ asset('images/site/photos/team-thumbs.jpg') }}" alt="Two farmers giving a thumbs up beside their rice field" class="hp-final-bg" loading="lazy">
        <div class="hp-final-shade" aria-hidden="true"></div>
        <div class="relative max-w-4xl mx-auto px-4 sm:px-6 hp-sec text-center reveal on-dark" style="z-index:1">
            <img src="{{ $faceLg }}" alt="" class="hp-final-face">
            <h2 class="hp-h2">Your Best Season Starts With <em>a Free Account.</em></h2>
            <p class="hp-p">Set up your first season tonight. Tomorrow morning, anee.io already knows what each lot needs.</p>
            <div class="hp-cta">
                <div class="hp-cta-row">
                    <a href="{{ $signup }}" class="btn btn-accent btn-lg hp-go">Create your free account {!! $arrow !!}</a>
                    <a href="{{ $ask }}" class="hp-alt"><img src="{{ $face }}" alt="" class="hp-face">Ask Anee a free question</a>
                </div>
                <p class="hp-cta-note">Free forever on Libre. No card needed. <a href="{{ route('contact') }}">Talk to us</a> if you have a question for a person.</p>
            </div>
        </div>
    </section>

    {{-- The way in, always one tap away on a phone once the hero has gone by. --}}
    <div class="hp-sticky" data-sticky>
        <a href="{{ $signup }}" class="btn btn-accent hp-go">Create your free account</a>
        <a href="{{ $ask }}" class="hp-sticky-ask" aria-label="Ask Anee a free question"><img src="{{ $face }}" alt="" class="hp-face is-lg"></a>
    </div>

@endsection

@push('head')
<style>
    /* ===================== THE HOMEPAGE ===================== */
    /* ---- hero ---- */
    .hp-hero { position: relative; isolation: isolate; overflow: hidden; color: #fff; }
    .hp-hero-bg { position: absolute; inset: 0; z-index: -3; width: 100%; height: 100%; object-fit: cover;
        transform: scale(1.06); animation: hpKen 22s ease-in-out infinite alternate; }
    @keyframes hpKen { from { transform: scale(1.06) translate3d(0, 0, 0); } to { transform: scale(1.14) translate3d(-1.5%, -1%, 0); } }
    .hp-hero-shade { position: absolute; inset: 0; z-index: -2;
        background: linear-gradient(180deg, rgb(8 14 5 / .55), rgb(8 14 5 / .78) 60%, rgb(8 14 5 / .92)),
                    linear-gradient(90deg, rgb(26 44 18 / .75), transparent 70%); }
    .hp-hero-glow { position: absolute; z-index: -1; width: 42rem; height: 42rem; right: -10rem; top: 10%; border-radius: 999px;
        background: radial-gradient(closest-side, rgb(168 204 126 / .3), transparent 70%); filter: blur(10px);
        animation: aneeBreath 8s ease-in-out infinite; pointer-events: none; }
    .hp-hero-in { display: grid; gap: 3rem; align-items: center; padding-top: 4rem; padding-bottom: 5.5rem; }
    @media (min-width: 1024px) { .hp-hero-in { grid-template-columns: 1.08fr .92fr; gap: 3.5rem; padding-top: 5.5rem; padding-bottom: 6.5rem; } }
    .hp-chip { display: inline-flex; align-items: center; gap: .5rem; padding: .4rem .95rem; border-radius: 999px;
        font-size: .85rem; font-weight: 700; color: #f4d778; background: rgb(255 255 255 / .1);
        box-shadow: inset 0 0 0 1px rgb(255 255 255 / .2); backdrop-filter: blur(8px); }
    .hp-chip svg { width: 1rem; height: 1rem; }
    .hp-h1 { margin-top: 1.4rem; font-family: var(--font-heading); font-weight: 800; letter-spacing: -.02em; line-height: 1.02;
        font-size: clamp(2.6rem, 7vw, 4.6rem); text-wrap: balance; }
    .hp-lede { margin-top: 1.9rem; max-width: 36rem; font-size: clamp(1.02rem, 1.7vw, 1.18rem); line-height: 1.7; color: #dde6d4; }
    .hp-hero-acts { margin-top: 2rem; display: flex; flex-wrap: wrap; gap: .8rem; }
    .hp-trust { margin-top: 1.4rem; display: flex; flex-wrap: wrap; gap: .5rem 1.2rem; font-size: .9rem; font-weight: 700; color: #e6eddd; }
    .hp-trust li { display: inline-flex; align-items: center; gap: .4rem; }
    .hp-trust svg { width: 1rem; height: 1rem; color: #a8cc7e; }
    .hp-tour { margin-top: 1.6rem; display: inline-flex; align-items: center; gap: .7rem; font-weight: 800; color: #fff;
        background: none; border: 0; cursor: pointer; padding: 0; }
    .hp-tour-dot { position: relative; width: 2.6rem; height: 2.6rem; border-radius: 999px; display: grid; place-items: center;
        background: rgb(255 255 255 / .14); box-shadow: inset 0 0 0 1px rgb(255 255 255 / .3);
        transition: transform .28s var(--hp-ease), background-color .28s var(--hp-ease); }
    .hp-tour-dot::before { content: ''; position: absolute; inset: -4px; border-radius: inherit; border: 2px solid rgb(245 197 24 / .6);
        animation: hpRing 2.4s var(--hp-ease) infinite; }
    @keyframes hpRing { from { transform: scale(.85); opacity: 1; } to { transform: scale(1.45); opacity: 0; } }
    .hp-tour-dot svg { width: 1rem; height: 1rem; margin-left: .15rem; color: var(--hp-sun); }
    .hp-tour:hover .hp-tour-dot { transform: scale(1.08); background: rgb(255 255 255 / .22); }

    .hp-hero-stage { position: relative; display: grid; place-items: center; padding: 1.5rem 0;
        --rx: 0deg; --ry: 0deg; perspective: 1200px; }
    .hp-stage-ring { position: absolute; width: min(28rem, 92%); aspect-ratio: 1; border-radius: 999px;
        border: 1px dashed rgb(255 255 255 / .16); animation: hpSpin 60s linear infinite; }
    .hp-stage-ring::before { content: ''; position: absolute; inset: 12%; border-radius: inherit; border: 1px solid rgb(168 204 126 / .18); }
    @keyframes hpSpin { to { transform: rotate(360deg); } }
    .hp-phone.is-hero { transform: rotateX(var(--rx)) rotateY(var(--ry)); transition: transform .5s var(--hp-ease); }

    /* ---- the phone that plays the films ---- */
    .hp-phone { position: relative; width: min(16.5rem, 64vw); aspect-ratio: 390 / 844; padding: .5rem; border-radius: 2.5rem;
        background: linear-gradient(155deg, #2a3820, #0b1207 60%); flex: none;
        box-shadow: 0 50px 90px -40px rgb(0 0 0 / .85), 0 0 0 1px rgb(255 255 255 / .1) inset, 0 0 0 1px rgb(0 0 0 / .4); }
    .hp-phone.is-sm { width: 100%; max-width: 13.5rem; border-radius: 2.1rem; padding: .42rem; }
    .hp-phone-scr { position: relative; width: 100%; height: 100%; overflow: hidden; border-radius: 2rem; background: #eef2ea; }
    .hp-phone.is-sm .hp-phone-scr { border-radius: 1.7rem; }
    .hp-film { display: block; width: 100%; height: 100%; object-fit: cover; transition: opacity .35s var(--hp-ease); }
    .hp-film.is-swapping { opacity: 0; }
    .hp-film-tag { position: absolute; left: 50%; bottom: -1.1rem; transform: translateX(-50%); white-space: nowrap;
        padding: .45rem .95rem; border-radius: 999px; font-size: .8rem; font-weight: 800; color: var(--hp-ink);
        background: var(--hp-sun); box-shadow: 0 12px 26px -12px rgb(0 0 0 / .6);
        transition: opacity .3s var(--hp-ease), transform .3s var(--hp-ease); }
    .hp-film-tag.is-swapping { opacity: 0; transform: translateX(-50%) translateY(6px); }

    .hp-float { position: absolute; z-index: 2; display: flex; align-items: center; gap: .65rem; max-width: 15rem;
        padding: .7rem .85rem; border-radius: 1.1rem; color: var(--hp-ink); background: rgb(255 255 255 / .94);
        box-shadow: 0 24px 48px -24px rgb(0 0 0 / .7); backdrop-filter: blur(8px);
        animation: hpFloatIn .9s var(--hp-ease) both, hpBob 6s ease-in-out infinite; }
    .hp-float b { display: block; font-size: .82rem; font-weight: 800; line-height: 1.25; }
    .hp-float small { display: block; font-size: .74rem; color: #4b5563; line-height: 1.35; }
    .hp-float-ico { flex: none; width: 2.3rem; height: 2.3rem; border-radius: .8rem; display: grid; place-items: center; }
    .hp-float-ico svg { width: 1.2rem; height: 1.2rem; }
    .hp-float-ico.is-green { color: #2f5219; background: #e4f0d6; }
    .hp-float-ico.is-sky { color: #1d4ed8; background: #dbeafe; }
    .hp-float.f1 { left: 0; top: 14%; animation-delay: .7s, 1.6s; }
    .hp-float.f2 { right: 0; top: 42%; animation-delay: 1s, 0s; }
    .hp-float.f3 { left: 4%; bottom: 8%; max-width: 16rem; animation-delay: 1.3s, .8s; }
    @keyframes hpFloatIn { from { opacity: 0; transform: translateY(18px) scale(.94); } to { opacity: 1; transform: none; } }
    @keyframes hpBob { 0%, 100% { translate: 0 0; } 50% { translate: 0 -8px; } }
    @media (max-width: 1023.98px) {
        .hp-float { max-width: 12.5rem; padding: .55rem .7rem; }
        .hp-float.f1 { left: 0; top: 6%; }
        .hp-float.f2 { right: 0; top: 46%; }
        .hp-float.f3 { left: 0; bottom: 15%; }
    }
    @media (max-width: 420px) {
        .hp-float { max-width: 10.5rem; }
        .hp-float small { font-size: .7rem; }
        .hp-float-ico { width: 1.9rem; height: 1.9rem; }
        .hp-face.is-lg { width: 2rem; height: 2rem; }
    }
    .hp-down { position: absolute; left: 50%; bottom: 1.2rem; transform: translateX(-50%); width: 2.6rem; height: 2.6rem;
        display: grid; place-items: center; border-radius: 999px; color: #fff; background: rgb(255 255 255 / .1);
        box-shadow: inset 0 0 0 1px rgb(255 255 255 / .25); animation: hpNudge 2.4s var(--hp-ease) infinite; }
    .hp-down svg { width: 1.2rem; height: 1.2rem; }
    @keyframes hpNudge { 0%, 100% { transform: translate(-50%, 0); } 50% { transform: translate(-50%, 6px); } }

    /* ---- what guessing costs: a dark, warm band so the red cards burn ---- */
    .hp-loss-dark { position: relative; overflow: hidden;
        background: radial-gradient(70% 55% at 50% 0%, rgb(185 28 28 / .32), transparent 72%),
                    radial-gradient(60% 50% at 100% 100%, rgb(127 29 29 / .25), transparent 70%),
                    linear-gradient(180deg, #1c1311 0%, #120c0a 100%); }
    .hp-loss-dark > * { position: relative; }
    .on-dark .hp-kick.is-red { color: #fca5a5; }
    .on-dark .hp-h2 em.is-red { color: #f87171; }
    .hp-loss-dark .loss-card { border-color: rgb(254 202 202 / .5); box-shadow: 0 24px 50px -30px rgb(0 0 0 / .8); }
    .hp-loss-warn { margin: 1.6rem auto 0; display: inline-flex; align-items: flex-start; gap: .7rem; max-width: 40rem; text-align: left;
        padding: .9rem 1.15rem; border-radius: 1.1rem; font-size: 1rem; line-height: 1.55; color: #f3e3e1;
        background: rgb(248 113 113 / .12); box-shadow: inset 0 0 0 1px rgb(248 113 113 / .4), 0 0 0 0 rgb(248 113 113 / .35);
        animation: hpWarn 3.2s ease-in-out infinite 1s; }
    .hp-loss-warn svg { flex: none; width: 1.4rem; height: 1.4rem; margin-top: .05rem; color: #fca5a5; }
    .hp-loss-warn b { color: #fff; }
    @keyframes hpWarn { 0%, 100% { box-shadow: inset 0 0 0 1px rgb(248 113 113 / .4), 0 0 0 0 rgb(248 113 113 / .3); }
        50% { box-shadow: inset 0 0 0 1px rgb(248 113 113 / .6), 0 0 0 8px rgb(248 113 113 / 0); } }
    @media (prefers-reduced-motion: reduce) { .hp-loss-warn { animation: none; } }
    .hp-loss-src { margin-top: 1.1rem; text-align: center; font-size: .78rem; line-height: 1.5; color: rgb(255 255 255 / .5); }

    /* ---- the turning word in the headline ---- */
    .hp-rot { display: inline-block; position: relative; white-space: nowrap; vertical-align: top;
        transition: width .55s var(--hp-ease); }
    .hp-rot-w { display: inline-block; will-change: opacity, transform;
        transition: opacity .38s ease, transform .45s var(--hp-ease), filter .38s ease; }
    .hp-rot-w.is-out { opacity: 0; transform: translateY(-.28em); filter: blur(5px); }
    .hp-rot-w.is-in { opacity: 0; transform: translateY(.28em); filter: blur(5px); transition: none; }
    .hp-rot-probe { position: absolute; left: 0; top: 0; visibility: hidden; pointer-events: none; }
    @media (prefers-reduced-motion: reduce) { .hp-rot, .hp-rot-w { transition: none !important; } }

    /* ---- the tour window ---- */
    .hp-modal { position: fixed; inset: 0; z-index: 80; display: grid; place-items: center; padding: 1rem;
        visibility: hidden; opacity: 0; transition: opacity .3s var(--hp-ease), visibility .3s; }
    .hp-modal[hidden] { display: grid !important; }
    .hp-modal.is-open { visibility: visible; opacity: 1; }
    .hp-modal-veil { position: absolute; inset: 0; background: rgb(6 10 4 / .82); backdrop-filter: blur(6px); }
    .hp-modal-box { position: relative; width: min(64rem, 100%); border-radius: 1.4rem; overflow: hidden; background: #000;
        box-shadow: 0 40px 100px -30px rgb(0 0 0 / .9); transform: scale(.94) translateY(12px); transition: transform .4s var(--hp-ease); }
    .hp-modal.is-open .hp-modal-box { transform: none; }
    .hp-modal-box video { display: block; width: 100%; aspect-ratio: 16 / 9; background: #000; }
    .hp-modal-x { position: absolute; top: .7rem; right: .7rem; z-index: 2; width: 2.5rem; height: 2.5rem; border-radius: 999px;
        display: grid; place-items: center; color: #fff; background: rgb(0 0 0 / .55); border: 0; cursor: pointer;
        transition: background-color .28s var(--hp-ease), transform .28s var(--hp-ease); }
    .hp-modal-x:hover { background: rgb(0 0 0 / .8); transform: rotate(90deg); }
    .hp-modal-x svg { width: 1.1rem; height: 1.1rem; }

    /* ---- why (intervention) ---- */
    .hp-why { position: relative; isolation: isolate; overflow: hidden; }
    .hp-why-bg { position: absolute; inset: 0; z-index: -2; width: 100%; height: 100%; object-fit: cover; }
    .hp-why-shade { position: absolute; inset: 0; z-index: -1; background: linear-gradient(180deg, rgb(20 36 12 / .95), rgb(20 36 12 / .86) 50%, rgb(20 36 12 / .96)); }
    .hp-why-grid { margin-top: 3rem; display: grid; gap: 1.1rem; }
    @media (min-width: 720px) { .hp-why-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 1.25rem; } }
    .hp-why-card { display: flex; flex-direction: column; border-radius: 1.4rem; padding: 1.3rem; background: rgb(255 255 255 / .07);
        box-shadow: inset 0 0 0 1px rgb(255 255 255 / .14); backdrop-filter: blur(10px);
        transition: transform .28s var(--hp-ease), background-color .28s var(--hp-ease); }
    .hp-why-card:hover { transform: translateY(-4px); background: rgb(255 255 255 / .1); }
    .hp-why-top { display: flex; align-items: center; gap: .85rem; }
    .hp-why-ico { flex: none; width: 2.9rem; height: 2.9rem; border-radius: 1rem; display: grid; place-items: center;
        color: #fca5a5; background: rgb(248 113 113 / .14); box-shadow: inset 0 0 0 1px rgb(248 113 113 / .3); }
    .hp-why-ico svg { width: 1.5rem; height: 1.5rem; }
    .hp-why-when { font-size: .68rem; font-weight: 800; letter-spacing: .1em; text-transform: uppercase; color: #fca5a5; }
    .hp-why-t { font-family: var(--font-heading); font-size: 1.15rem; font-weight: 800; color: #fff; line-height: 1.25; }
    .hp-why-w { margin-top: .8rem; font-size: .92rem; line-height: 1.6; color: #c9d5bd; }
    .hp-why-a { margin-top: 1rem; padding: .9rem 1rem; border-radius: 1rem; background: rgb(168 204 126 / .12);
        box-shadow: inset 0 0 0 1px rgb(168 204 126 / .28); flex: 1 1 auto; }
    .hp-why-badge { display: inline-flex; align-items: center; gap: .35rem; font-size: .72rem; font-weight: 800; letter-spacing: .06em;
        text-transform: uppercase; color: var(--hp-sun); }
    .hp-why-badge svg { width: .85rem; height: .85rem; }
    .hp-why-a p { margin-top: .35rem; font-size: .92rem; line-height: 1.6; color: #fff; }


    /* ---- the seven steps ---- */
    .hp-st { margin-top: 3rem; display: grid; gap: 1.5rem; }
    @media (min-width: 1024px) { .hp-st { grid-template-columns: 19rem minmax(0, 1fr); gap: 2rem; align-items: start; } }
    .hp-st-list { display: flex; gap: .6rem; overflow-x: auto; scroll-snap-type: x mandatory; padding: .3rem .1rem .8rem;
        scrollbar-width: none; margin: 0 -1rem; padding-left: 1rem; padding-right: 1rem; }
    .hp-st-list::-webkit-scrollbar { display: none; }
    @media (min-width: 1024px) { .hp-st-list { flex-direction: column; overflow: visible; margin: 0; padding: 0; } }
    .hp-st-tab { position: relative; flex: none; scroll-snap-align: start; display: flex; align-items: center; gap: .8rem; text-align: left;
        min-width: 14.5rem; padding: .85rem 1rem; border-radius: 1.1rem; background: #fff; border: 1px solid #e1ead6; cursor: pointer; overflow: hidden;
        transition: border-color .28s var(--hp-ease), box-shadow .28s var(--hp-ease), transform .28s var(--hp-ease), background-color .28s var(--hp-ease); }
    @media (min-width: 1024px) { .hp-st-tab { min-width: 0; width: 100%; } }
    .hp-st-tab:hover { transform: translateY(-2px); border-color: #b9d69a; }
    .hp-st-tab.is-on { border-color: var(--hp-green); background: #fbfdf8; box-shadow: 0 18px 36px -24px rgb(47 82 25 / .6); }
    .hp-st-n { flex: none; width: 2.2rem; height: 2.2rem; border-radius: 999px; display: grid; place-items: center; font-weight: 800;
        font-family: var(--font-heading); color: var(--hp-deep); background: #eef5e5;
        transition: background-color .28s var(--hp-ease), color .28s var(--hp-ease); }
    .hp-st-tab.is-on .hp-st-n { color: var(--hp-ink); background: var(--hp-sun); }
    .hp-st-tt { min-width: 0; }
    .hp-st-tt b { display: block; font-size: .95rem; font-weight: 800; color: var(--hp-ink); line-height: 1.25; }
    .hp-st-tt small { display: block; margin-top: .1rem; font-size: .75rem; font-weight: 700; color: #6b7f5a; }
    .hp-st-bar { position: absolute; left: 0; right: 0; bottom: 0; height: 3px; background: transparent; }
    .hp-st-bar i { display: block; height: 100%; width: 100%; transform-origin: left; transform: scaleX(0); background: var(--hp-green); }
    .hp-st-tab.is-on.is-timing .hp-st-bar i { animation: hpBar var(--hp-dwell, 9s) linear forwards; }
    @keyframes hpBar { to { transform: scaleX(1); } }
    .hp-st-body { display: grid; gap: 2rem; align-items: center; border-radius: 1.75rem; padding: 1.4rem; background: #fff;
        border: 1px solid #e1ead6; box-shadow: 0 30px 60px -44px rgb(20 33 12 / .55); }
    @media (min-width: 768px) { .hp-st-body { grid-template-columns: minmax(0, 1fr) auto; padding: 2rem; gap: 2.4rem; } }
    .hp-st-panes { display: grid; }
    .hp-st-pane { grid-area: 1 / 1; opacity: 0; visibility: hidden; transform: translateY(10px);
        transition: opacity .45s var(--hp-ease), transform .45s var(--hp-ease), visibility .45s; }
    .hp-st-pane.is-on { opacity: 1; visibility: visible; transform: none; }
    .hp-st-when { font-size: .74rem; font-weight: 800; letter-spacing: .1em; text-transform: uppercase; color: var(--hp-green); }
    .hp-st-h { margin-top: .4rem; font-family: var(--font-heading); font-size: clamp(1.4rem, 3vw, 1.9rem); font-weight: 800; color: var(--hp-ink); line-height: 1.15; }
    .hp-st-p { margin-top: .7rem; color: #4b5563; line-height: 1.65; }
    .hp-tools { margin-top: 1.3rem; display: grid; gap: .55rem; grid-template-columns: repeat(2, minmax(0, 1fr)); }
    @media (min-width: 1200px) { .hp-tools { grid-template-columns: repeat(auto-fill, minmax(13rem, 1fr)); } }
    @media (max-width: 639.98px) {
        .hp-tool { flex-direction: column; align-items: flex-start; gap: .4rem; padding: .6rem; }
        .hp-tool-t { font-size: .8rem; }
        .hp-anee-tag { display: table; margin: .25rem 0 0; }
    }
    .hp-tool { display: flex; align-items: center; gap: .65rem; padding: .55rem .7rem; border-radius: .9rem; text-align: left; cursor: pointer;
        background: #f7faf3; border: 1px solid #e4ecdb; opacity: 0; transform: translateY(8px);
        transition: opacity .4s var(--hp-ease), transform .4s var(--hp-ease), border-color .28s var(--hp-ease), background-color .28s var(--hp-ease), box-shadow .28s var(--hp-ease); }
    .hp-st-pane.is-on .hp-tool { opacity: 1; transform: none; transition-delay: calc(var(--j) * 50ms + .1s), calc(var(--j) * 50ms + .1s), 0s, 0s, 0s; }
    .hp-tool:hover { border-color: #b9d69a; background: #fff; }
    .hp-tool.is-on { border-color: var(--hp-green); background: #fff; box-shadow: 0 10px 22px -16px rgb(47 82 25 / .7); }
    .hp-tool-ico { flex: none; width: 2.2rem; height: 2.2rem; border-radius: .7rem; display: grid; place-items: center; background: #fff;
        box-shadow: inset 0 0 0 1px #e4ecdb; }
    .hp-tool-ico img { width: 1.45rem; height: 1.45rem; object-fit: contain; }
    .hp-tool-ico.is-face img { width: 100%; height: 100%; border-radius: inherit; object-fit: cover; }
    .hp-tool-t { min-width: 0; font-size: .88rem; font-weight: 800; color: var(--hp-ink); line-height: 1.25; }
    .hp-anee-tag { display: inline-block; margin-left: .35rem; padding: .05rem .4rem; border-radius: 999px; font-style: normal; font-size: .62rem;
        font-weight: 800; letter-spacing: .04em; text-transform: uppercase; vertical-align: .1em; color: #5c4400; background: #fdeeb2; }
    .hp-st-show { display: flex; flex-direction: column; align-items: center; gap: 1.6rem; }
    .hp-st-show .hp-phone { width: min(15rem, 54vw); }
    .hp-st-short { max-width: 15rem; text-align: center; font-size: .88rem; font-weight: 700; color: #5b6b50; line-height: 1.45; min-height: 2.6em;
        transition: opacity .3s var(--hp-ease); }
    .hp-st-more { display: inline-flex; align-items: center; gap: .35rem; margin-top: -1rem; font-size: .88rem; font-weight: 800; color: var(--hp-green); }
    .hp-st-more svg { width: 1rem; height: 1rem; transition: transform .28s var(--hp-ease); }
    .hp-st-more:hover svg { transform: translateX(3px); }

    /* ---- Anee ---- */
    .hp-anee { position: relative; overflow: hidden; color: #e8efe1;
        background: radial-gradient(90% 120% at 85% 10%, #2d4a1a 0%, transparent 60%), linear-gradient(160deg, #10160c 0%, #1c2416 55%, #24301a 100%); }
    .hp-anee-grid { display: grid; gap: 3rem; align-items: center; }
    @media (min-width: 1024px) { .hp-anee-grid { grid-template-columns: 1.05fr .95fr; gap: 4rem; } }
    .hp-powers { margin-top: 1.5rem; display: flex; flex-wrap: wrap; gap: .5rem; }
    .hp-power { display: inline-flex; align-items: center; gap: .45rem; padding: .42rem .8rem .42rem .45rem; border-radius: 999px;
        font-size: .82rem; font-weight: 800; color: #fff; text-decoration: none; background: rgb(255 255 255 / .08);
        box-shadow: inset 0 0 0 1px rgb(255 255 255 / .16); transition: background-color .28s var(--hp-ease), transform .28s var(--hp-ease); }
    .hp-power:hover { background: rgb(245 197 24 / .18); transform: translateY(-2px); }
    .hp-power img { width: 1.5rem; height: 1.5rem; padding: .15rem; border-radius: 999px; background: #fff; object-fit: contain; }
    .hp-anee-price { margin-top: 1.4rem; padding: .85rem 1rem; border-radius: 1rem; font-size: .9rem; line-height: 1.6; color: #e6eddd;
        background: rgb(245 197 24 / .1); box-shadow: inset 0 0 0 1px rgb(245 197 24 / .3); }
    .hp-chat { position: relative; max-width: 28rem; margin: 0 auto; border-radius: 1.6rem; background: #fff; color: var(--hp-ink); overflow: hidden;
        box-shadow: 0 50px 90px -40px rgb(0 0 0 / .85), 0 0 0 1px rgb(255 255 255 / .1); }
    .hp-chat-top { display: flex; align-items: center; gap: .7rem; padding: .9rem 1.1rem; background: linear-gradient(135deg, #2f5219, #4a7c2a); color: #fff; }
    .hp-chat-top b { display: block; font-size: 1rem; }
    .hp-chat-top small { display: flex; align-items: center; font-size: .75rem; color: #dceccb; }
    .hp-chat-body { display: flex; flex-direction: column; gap: .8rem; padding: 1.1rem; background: #f6f8f3; min-height: 26rem; }
    .hp-msg { max-width: 88%; padding: .75rem .9rem; border-radius: 1.1rem; font-size: .88rem; line-height: 1.55;
        opacity: 0; transform: translateY(10px) scale(.98); transition: opacity .5s var(--hp-ease), transform .5s var(--hp-ease); }
    .hp-msg.is-me { align-self: flex-end; color: #fff; background: #4a7c2a; border-bottom-right-radius: .35rem; }
    .hp-msg-photo { width: 100%; max-height: 8.5rem; object-fit: cover; border-radius: .7rem; margin-bottom: .55rem; }
    .hp-msg.is-anee { align-self: flex-start; background: #fff; box-shadow: 0 8px 20px -14px rgb(0 0 0 / .4); border-bottom-left-radius: .35rem; }
    .hp-msg.is-anee b { display: block; color: var(--hp-deep); }
    .hp-msg.is-anee p { margin-top: .3rem; color: #4b5563; }
    .hp-msg.is-anee ol { margin-top: .5rem; padding-left: 1.1rem; list-style: decimal; display: grid; gap: .3rem; color: #374151; }
    .hp-reading { display: flex; flex-direction: column; gap: .35rem; }
    .hp-read { display: inline-flex; align-items: center; gap: .5rem; align-self: flex-start; padding: .35rem .7rem; border-radius: 999px;
        font-size: .76rem; font-weight: 700; color: #5b6b50; background: #fff; box-shadow: inset 0 0 0 1px #e4ecdb;
        opacity: 0; transform: translateX(-8px); transition: opacity .4s var(--hp-ease), transform .4s var(--hp-ease); }
    .hp-read i { width: .8rem; height: .8rem; border-radius: 999px; border: 2px solid #cfe3b8; border-top-color: var(--hp-green); animation: hpSpinner .7s linear infinite; }
    .hp-read.is-done i { animation: none; border-color: var(--hp-green); background: var(--hp-green); }
    @keyframes hpSpinner { to { transform: rotate(360deg); } }
    .hp-chat.s1 .hp-msg.is-me, .hp-chat.s5 .hp-msg.is-anee { opacity: 1; transform: none; }
    .hp-chat.s2 .hp-read:nth-child(1), .hp-chat.s3 .hp-read:nth-child(-n+2), .hp-chat.s4 .hp-read, .hp-chat.s5 .hp-read { opacity: 1; transform: none; }
    .hp-chat-again { position: absolute; right: .8rem; top: .9rem; display: inline-flex; align-items: center; gap: .3rem; padding: .3rem .7rem;
        border-radius: 999px; border: 0; font-size: .72rem; font-weight: 800; color: #fff; background: rgb(255 255 255 / .16); cursor: pointer;
        opacity: 0; pointer-events: none; transition: opacity .3s var(--hp-ease), background-color .28s var(--hp-ease); }
    .hp-chat-again svg { width: .85rem; height: .85rem; }
    .hp-chat.is-done .hp-chat-again { opacity: 1; pointer-events: auto; }
    .hp-chat-again:hover { background: rgb(255 255 255 / .28); }

    /* ---- the reel ---- */
    .hp-reel { margin-top: 3rem; display: grid; grid-auto-flow: column; grid-auto-columns: 62%; gap: 1.1rem; overflow-x: auto;
        scroll-snap-type: x mandatory; padding: .5rem 1rem 1.5rem; margin-left: -1rem; margin-right: -1rem; scrollbar-width: none; }
    .hp-reel::-webkit-scrollbar { display: none; }
    @media (min-width: 640px) { .hp-reel { grid-auto-columns: 38%; } }
    @media (min-width: 1024px) { .hp-reel { grid-auto-flow: row; grid-template-columns: repeat(4, minmax(0, 1fr)); overflow: visible; margin: 3rem 0 0; padding: 0; gap: 2.2rem 1.5rem; } }
    .hp-reel-card { scroll-snap-align: center; display: flex; flex-direction: column; align-items: center; text-align: center; margin: 0; }
    .hp-reel-card .hp-phone { transition: transform .4s var(--hp-ease), box-shadow .4s var(--hp-ease); }
    .hp-reel-card:hover .hp-phone { transform: translateY(-6px) rotate(-1deg); }
    .hp-reel-card figcaption { margin-top: 1rem; display: flex; flex-direction: column; align-items: center; gap: .2rem; max-width: 14rem; }
    .hp-reel-card figcaption b { font-size: 1rem; font-weight: 800; color: var(--hp-ink); }
    .hp-reel-card figcaption span { font-size: .84rem; color: #6b7280; line-height: 1.45; }
    .hp-reel-card figcaption a { margin-top: .3rem; display: inline-flex; align-items: center; gap: .3rem; font-size: .82rem; font-weight: 800; color: var(--hp-green); }
    .hp-reel-card figcaption a svg { width: .9rem; height: .9rem; transition: transform .28s var(--hp-ease); }
    .hp-reel-card figcaption a:hover svg { transform: translateX(3px); }

    /* ---- the farm as a business ---- */
    .hp-biz { display: grid; gap: 3rem; align-items: center; }
    @media (min-width: 1024px) { .hp-biz { grid-template-columns: .95fr 1.05fr; gap: 4rem; } }
    .hp-biz-pics { position: relative; padding: 0 0 3.5rem 0; }
    .hp-biz-a { width: 88%; border-radius: 1.6rem; object-fit: cover; aspect-ratio: 4 / 3; box-shadow: 0 30px 60px -36px rgb(20 33 12 / .7); }
    .hp-biz-b { position: absolute; right: 0; bottom: 0; width: 46%; aspect-ratio: 3 / 4; object-fit: cover; border-radius: 1.3rem;
        border: 6px solid #fff; box-shadow: 0 30px 60px -30px rgb(20 33 12 / .7); animation: hpBob 7s ease-in-out infinite; }
    .hp-biz-pill { position: absolute; left: 1rem; bottom: 4.6rem; display: inline-flex; align-items: center; padding: .45rem .85rem; border-radius: 999px;
        font-size: .8rem; font-weight: 800; color: var(--hp-ink); background: rgb(255 255 255 / .94); box-shadow: 0 12px 26px -14px rgb(0 0 0 / .5); }
    .hp-biz-built { margin-top: 1.6rem; padding: 1.1rem 1.2rem; border-radius: 1.2rem; background: #fff; border: 1px solid #dcead0;
        border-left: 4px solid var(--hp-green); }
    .hp-biz-h { font-family: var(--font-heading); font-weight: 800; font-size: 1.1rem; color: var(--hp-ink); }
    .hp-biz-p { margin-top: .35rem; font-size: .92rem; color: #4b5563; line-height: 1.6; }
    .hp-gains { margin-top: 1rem; display: grid; gap: .7rem; grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .hp-gain { display: flex; align-items: flex-start; gap: .7rem; padding: .85rem; border-radius: 1rem; background: #fff; border: 1px solid #e4ecdb;
        transition: transform .28s var(--hp-ease), box-shadow .28s var(--hp-ease); }
    .hp-gain:hover { transform: translateY(-3px); box-shadow: 0 16px 30px -22px rgb(20 33 12 / .5); }
    .hp-gain > span { flex: none; width: 2.2rem; height: 2.2rem; border-radius: .75rem; display: grid; place-items: center; color: var(--hp-green); background: #eef5e5; }
    .hp-gain > span svg { width: 1.2rem; height: 1.2rem; }
    .hp-gain b { display: block; font-size: .9rem; font-weight: 800; color: var(--hp-ink); line-height: 1.25; }
    .hp-gain small { display: block; margin-top: .15rem; font-size: .78rem; color: #6b7280; line-height: 1.4; }

    /* ---- versus ---- */
    .hp-vs { margin-top: 3rem; border-radius: 1.6rem; overflow: hidden; background: #fff; border: 1px solid #e5e7eb;
        box-shadow: 0 30px 60px -48px rgb(20 33 12 / .6); }
    .hp-vs-head { display: none; }
    .hp-vs-row { display: grid; gap: .5rem; padding: 1.1rem 1.2rem; border-top: 1px solid #f0f2ed; }
    .hp-vs-row:first-of-type { border-top: 0; }
    .hp-vs-dim { font-family: var(--font-heading); font-weight: 800; color: var(--hp-ink); }
    .hp-vs-old, .hp-vs-new { display: flex; gap: .6rem; align-items: flex-start; font-size: .9rem; line-height: 1.5; }
    .hp-vs-old { color: #6b7280; }
    .hp-vs-new { color: #1f2937; font-weight: 600; padding: .6rem .7rem; border-radius: .9rem; background: #f3f8ec; }
    .hp-vs-old > span, .hp-vs-new > span { flex: none; width: 1.35rem; height: 1.35rem; border-radius: 999px; display: grid; place-items: center; margin-top: .05rem; }
    .hp-vs-old > span { color: #9ca3af; background: #f3f4f6; }
    .hp-vs-new > span { color: #fff; background: var(--hp-green); }
    .hp-vs-old svg, .hp-vs-new > span svg { width: .75rem; height: .75rem; }
    .hp-vs-label { display: none; }
    @media (min-width: 768px) {
        .hp-vs-head { display: grid; grid-template-columns: 1fr 1.1fr 1.2fr; }
        .hp-vs-head span { padding: 1.1rem 1.4rem; font-family: var(--font-heading); font-weight: 800; }
        .hp-vs-head .is-old { color: #6b7280; background: #f9fafb; }
        .hp-vs-head .is-new { display: flex; align-items: center; gap: .5rem; color: #fff; background: var(--hp-deep); }
        .hp-vs-head .is-new img { width: 1.3rem; height: 1.3rem; object-fit: contain; }
        .hp-vs-row { grid-template-columns: 1fr 1.1fr 1.2fr; gap: 0; padding: 0; align-items: stretch; }
        .hp-vs-row > * { padding: 1rem 1.4rem; }
        .hp-vs-dim { display: flex; align-items: center; }
        .hp-vs-old { border-left: 1px solid #f0f2ed; }
        .hp-vs-new { border-radius: 0; background: #f6faf1; }
        .hp-vs-row:first-of-type { border-top: 1px solid #f0f2ed; }
    }
    .hp-vs.is-visible .hp-vs-new > span { animation: hpPop .5s var(--hp-ease) both; animation-delay: calc(var(--i, 0) * 90ms + .2s); }
    .hp-vs-row { --i: 0; }
    @keyframes hpPop { from { transform: scale(0); } 60% { transform: scale(1.2); } to { transform: scale(1); } }

    /* ---- pricing toggle, questions ---- */
    .hp-billing { margin-top: 1.6rem; display: inline-flex; gap: .25rem; padding: .3rem; border-radius: 999px; background: #fff; box-shadow: inset 0 0 0 1px #e1ead6; }
    .hp-billing button { border: 0; background: transparent; border-radius: 999px; padding: .45rem 1.05rem; font-size: .88rem; font-weight: 800; color: #6b7f5a; cursor: pointer;
        transition: background-color .28s var(--hp-ease), color .28s var(--hp-ease); }
    .hp-billing button.is-on { background: var(--hp-green); color: #fff; }
    .hp-billing button span { font-weight: 600; opacity: .8; }
    .hp-pr { transition: transform .28s var(--hp-ease), box-shadow .28s var(--hp-ease); }
    .hp-pr:hover { transform: translateY(-4px); }
    .hp-q { border-radius: 1.1rem; background: #fff; border: 1px solid #e5e7eb; overflow: hidden; transition: border-color .28s var(--hp-ease), box-shadow .28s var(--hp-ease); }
    .hp-q.is-open { border-color: #b9d69a; box-shadow: 0 16px 34px -26px rgb(47 82 25 / .55); }
    .hp-q-btn { width: 100%; display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: 1rem 1.25rem; text-align: left;
        font-family: var(--font-heading); font-weight: 800; color: var(--hp-ink); background: none; border: 0; cursor: pointer; }
    .hp-q-btn svg { flex: none; width: 1.25rem; height: 1.25rem; color: var(--hp-green); transition: transform .28s var(--hp-ease); }
    .hp-q.is-open .hp-q-btn svg { transform: rotate(45deg); }
    .hc-card { display: flex; gap: 1rem; align-items: flex-start; padding: 1.4rem; border-radius: 1.25rem; background: #fff; border: 1px solid #e5ebdf;
        transition: transform .28s var(--hp-ease), box-shadow .28s var(--hp-ease), border-color .28s var(--hp-ease); }
    .hc-card:hover { transform: translateY(-3px); border-color: hsl(var(--h) 45% 75%); box-shadow: 0 20px 40px -30px hsl(var(--h) 40% 20% / .55); }
    .hc-ico { flex: none; width: 3.1rem; height: 3.1rem; border-radius: 1rem; display: grid; place-items: center; color: hsl(var(--h) 60% 30%);
        background: linear-gradient(145deg, hsl(var(--h) 70% 94%), hsl(var(--h) 60% 85%)); box-shadow: inset 0 0 0 1px hsl(var(--h) 50% 80%); }
    .hc-ico svg { width: 1.55rem; height: 1.55rem; }
    .hc-link { font-size: .8rem; font-weight: 800; color: hsl(var(--h) 55% 28%); background: hsl(var(--h) 60% 95%); border-radius: 999px; padding: .3rem .75rem; text-decoration: none;
        transition: background .28s var(--hp-ease); }
    .hc-link:hover { background: hsl(var(--h) 60% 89%); }
    .hq-body { display: grid; grid-template-rows: 0fr; transition: grid-template-rows .28s var(--hp-ease); }
    .hq-body.is-open { grid-template-rows: 1fr; }
    .hq-body > div { overflow: hidden; }
    .hg-col { display: flex; flex-direction: column; gap: .8rem; min-width: 0; }
    .hg-kick { font-size: .75rem; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; color: #3d6823; }
    .hg-list { display: grid; gap: .1rem; border-top: 1px solid #e5ebdf; padding-top: .4rem; }
    .hg-list a { display: block; padding: .5rem .2rem; font-size: .92rem; font-weight: 600; color: #14210c; text-decoration: none; border-bottom: 1px dashed #e5ebdf;
        transition: color .28s var(--hp-ease), padding .28s var(--hp-ease); }
    .hg-list a:hover { color: #3d6823; padding-left: .45rem; }
    .hg-all { font-size: .88rem; font-weight: 800; color: #3d6823; text-decoration: none; }
    .hg-all:hover { text-decoration: underline; }

    /* ---- the truth: costs up, plus prices down, equals one way out ---- */
    .hp-truth2 { background: linear-gradient(180deg, #ffffff 0%, #fbf8f1 100%); }
    .hp-h2 em.is-red { color: #b91c1c; }
    .hp-sum { margin-top: 3rem; display: grid; gap: .8rem; justify-items: stretch; }
    @media (min-width: 960px) { .hp-sum { grid-template-columns: 1fr auto 1fr auto 1.2fr; gap: 1.1rem; align-items: stretch; } }
    .hp-sum-card { position: relative; display: flex; flex-direction: column; border-radius: 1.5rem; padding: 1.4rem 1.4rem 1.5rem;
        background: #fff; border: 1px solid #e5e7eb; box-shadow: 0 24px 50px -40px rgb(20 33 12 / .6);
        transition: transform .28s var(--hp-ease), box-shadow .28s var(--hp-ease); }
    .hp-sum-card:hover { transform: translateY(-4px); }
    .hp-sum-card.is-up { background: #fff6f5; border-color: #fecaca; }
    .hp-sum-card.is-down { background: #fffbeb; border-color: #fde68a; }
    .hp-sum-card.is-way { color: #fff; border: 0; background: radial-gradient(120% 120% at 0% 0%, #5c9434 0%, #3d6823 45%, #24420f 100%);
        box-shadow: 0 30px 60px -30px rgb(36 66 15 / .85), 0 0 0 3px rgb(245 197 24 / .55); }
    .hp-sum-ico { width: 3rem; height: 3rem; border-radius: 1rem; display: grid; place-items: center; }
    .hp-sum-ico svg { width: 1.6rem; height: 1.6rem; }
    .is-up .hp-sum-ico { color: #dc2626; background: #fee2e2; }
    .is-down .hp-sum-ico { color: #b45309; background: #fef3c7; }
    .is-way .hp-sum-ico { color: var(--hp-ink); background: var(--hp-sun); }
    .is-up .hp-sum-ico svg { animation: hpRise 2.2s var(--hp-ease) infinite; }
    .is-down .hp-sum-ico svg { animation: hpSink 2.2s var(--hp-ease) infinite .4s; }
    .is-way .hp-sum-ico { animation: hpGlowSun 2.8s ease-in-out infinite; }
    @keyframes hpRise { 0%, 100% { transform: translateY(2px); } 50% { transform: translateY(-4px); } }
    @keyframes hpSink { 0%, 100% { transform: translateY(-2px); } 50% { transform: translateY(4px); } }
    @keyframes hpGlowSun { 0%, 100% { box-shadow: 0 0 0 0 rgb(245 197 24 / .55); } 50% { box-shadow: 0 0 0 9px rgb(245 197 24 / 0); } }
    .hp-sum-k { margin-top: 1rem; font-family: var(--font-heading); font-size: 1.2rem; font-weight: 800; line-height: 1.25; color: var(--hp-ink); }
    .is-up .hp-sum-k { color: #991b1b; }
    .is-down .hp-sum-k { color: #92400e; }
    .is-way .hp-sum-k { color: #fff; font-size: 1.3rem; }
    /* Each line on its own row, a thin rule between them, for easy reading. */
    .hp-sum-list { margin-top: .5rem; display: grid; }
    .hp-sum-list li { position: relative; padding: .8rem 0 .8rem 1.1rem; font-size: .93rem; line-height: 1.55; color: #4b5563; }
    .hp-sum-list li + li { border-top: 1px solid rgb(0 0 0 / .08); }
    .hp-sum-list li:last-child { padding-bottom: 0; }
    .is-up .hp-sum-list li + li { border-top-color: #fbd0d0; }
    .is-down .hp-sum-list li + li { border-top-color: #f8e3a3; }
    .hp-sum-list li::before { content: ''; position: absolute; left: 0; top: calc(.8rem + .55em); width: .42rem; height: .42rem; border-radius: 999px; background: currentColor; opacity: .45; }
    .is-up .hp-sum-list li::before { background: #dc2626; opacity: .7; }
    .is-down .hp-sum-list li::before { background: #d97706; opacity: .7; }
    .hp-sum-p { margin-top: .7rem; font-size: .98rem; line-height: 1.6; color: #e4f0d6; }
    /* "higher yield." in the green card: gold, with an underline that
       draws itself in once the card has scrolled into view. */
    .hp-hl { color: var(--hp-sun); background-image: linear-gradient(var(--hp-sun), var(--hp-sun)); background-repeat: no-repeat;
        background-position: 0 100%; background-size: 0% 3px; padding-bottom: .12em;
        transition: background-size .9s var(--hp-ease) .45s; }
    .hp-sum-card.is-visible .hp-hl, html:not(.js) .hp-hl { background-size: 100% 3px; }
    .hp-sum-how { margin-top: .75rem; display: grid; gap: .55rem; }
    .hp-sum-how li { position: relative; padding-left: 1.6rem; font-size: .92rem; line-height: 1.55; color: #e4f0d6; }
    .hp-sum-how li::before { content: ''; position: absolute; left: 0; top: .2em; width: 1.05rem; height: 1.05rem; border-radius: 999px;
        background: var(--hp-sun) url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%2314210c' stroke-width='3.4' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M5 13l4 4L19 7'/%3E%3C/svg%3E") center / 70% no-repeat; }
    @media (prefers-reduced-motion: reduce) { .hp-hl { transition: none; background-size: 100% 3px; } }
    .hp-sum-brand { margin-top: auto; padding-top: 1.1rem; display: inline-flex; align-items: center; gap: .5rem; font-weight: 800; color: var(--hp-sun); }
    .hp-sum-brand img { width: 1.5rem; height: 1.5rem; object-fit: contain; }
    .hp-sum-op { align-self: center; justify-self: center; font-family: var(--font-heading); font-size: 2.6rem; font-weight: 800; line-height: 1;
        color: #9ca3af; }

    /* ---- the most searched guides, as links ---- */
    .hp-topics { margin: 2.2rem auto 0; max-width: 60rem; display: flex; flex-wrap: wrap; justify-content: center; gap: .5rem; }
    .hp-topic { padding: .45rem .9rem; border-radius: 999px; font-size: .86rem; font-weight: 800; text-decoration: none;
        color: hsl(var(--h) 50% 26%); background: hsl(var(--h) 55% 95%); box-shadow: inset 0 0 0 1px hsl(var(--h) 45% 86%);
        transition: background-color .28s var(--hp-ease), transform .28s var(--hp-ease), box-shadow .28s var(--hp-ease); }
    .hp-topic:hover { transform: translateY(-2px); background: hsl(var(--h) 60% 90%); box-shadow: inset 0 0 0 1px hsl(var(--h) 45% 72%); }

    /* ---- final call, sticky bar ---- */
    .hp-final { position: relative; isolation: isolate; overflow: hidden; }
    .hp-final-bg { position: absolute; inset: 0; z-index: -2; width: 100%; height: 100%; object-fit: cover; }
    .hp-final-shade { position: absolute; inset: 0; z-index: -1; background: linear-gradient(160deg, rgb(20 36 12 / .93), rgb(29 51 15 / .82) 55%, rgb(47 82 25 / .78)); }
    .hp-final-face { width: 5rem; height: 5rem; margin: 0 auto 1rem; border-radius: 999px; object-fit: cover;
        box-shadow: 0 0 0 4px var(--hp-sun), 0 0 0 12px rgb(245 197 24 / .18); animation: hpBob 5s ease-in-out infinite; }
    .hp-sticky { position: fixed; left: .75rem; right: .75rem; bottom: calc(.75rem + env(safe-area-inset-bottom, 0px)); z-index: 38;
        display: flex; gap: .6rem; align-items: center; padding: .55rem; border-radius: 1.3rem; background: rgb(16 22 12 / .92);
        backdrop-filter: blur(10px); box-shadow: 0 20px 40px -18px rgb(0 0 0 / .7);
        transform: translateY(140%); opacity: 0; visibility: hidden;
        transition: transform .45s var(--hp-ease), opacity .45s var(--hp-ease), visibility .45s; }
    .hp-sticky.is-on { transform: none; opacity: 1; visibility: visible; }
    .hp-sticky .btn { flex: 1 1 auto; justify-content: center; min-height: 3rem; }
    .hp-sticky-ask { flex: none; display: grid; place-items: center; width: 3rem; height: 3rem; border-radius: 999px; background: rgb(255 255 255 / .1); }
    @media (min-width: 768px) { .hp-sticky { display: none; } }

    @media (prefers-reduced-motion: reduce) {
        html { scroll-behavior: auto; }
        .hp-hero-bg, .hp-hero-glow, .hp-stage-ring, .hp-shimmer, .hp-mark-line, .hp-mark-line path, .hp-go::after, .hp-tour-dot::before, .hp-float, .hp-down,
        .hp-biz-b, .hp-final-face, .hp-live, .hp-read i, .hp-sum-ico, .hp-sum-ico svg, .hp-vs.is-visible .hp-vs-new > span { animation: none !important; }
        .hp-st-tab.is-on.is-timing .hp-st-bar i { animation: none; }
        .hp-phone.is-hero, .hp-st-pane, .hp-tool, .hp-msg, .hp-read, .hp-film, .hp-film-tag, .hp-modal, .hp-modal-box, .hp-sticky,
        .hp-go, .hp-alt, .hp-why-card, .hp-st-tab, .hp-gain, .hp-reel-card .hp-phone, .hp-q, .hc-card, .hg-list a, .hq-body, .hp-topic { transition: none !important; }
        .hp-chat .hp-msg, .hp-chat .hp-read { opacity: 1; transform: none; }
        .hp-mark-line { -webkit-clip-path: none; clip-path: none; }
    }
</style>
@endpush

@push('scripts')
<script>
/* The homepage's moving parts. Every one of them rests when the visitor asks
   for less motion: the films do not play by themselves, the counters show
   their numbers, the chat shows its whole answer and the steps stay put. */
(() => {
    const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const fine = window.matchMedia('(pointer: fine)').matches;
    const seen = (el, fn, opts) => {
        if (!el) return;
        if (!('IntersectionObserver' in window)) { fn(true); return; }
        new IntersectionObserver((es) => es.forEach((e) => fn(e.isIntersecting, e)), opts || { threshold: 0.35 }).observe(el);
    };
    const play = (v) => { if (v && !reduce) { const p = v.play(); if (p && p.catch) p.catch(() => {}); } };
    const swapFilm = (video, tag, src, poster, name) => {
        if (!video || !src) return;
        video.classList.add('is-swapping');
        if (tag) tag.classList.add('is-swapping');
        setTimeout(() => {
            video.poster = poster || '';
            video.src = src;
            video.load();
            if (tag && name) tag.textContent = name;
            const show = () => { video.classList.remove('is-swapping'); if (tag) tag.classList.remove('is-swapping'); };
            video.addEventListener('loadeddata', show, { once: true });
            setTimeout(show, 900);
            if (video.dataset.live === '1') play(video);
        }, 260);
    };

    /* Counters count up from zero as they come into view (the loss cards'
       red bars fill at the same moment). */
    document.querySelectorAll('[data-countup]').forEach((el) => {
        const end = parseInt(el.dataset.countup, 10) || 0;
        el.textContent = reduce ? end : 0;
        let done = false;
        seen(el, (on) => {
            if (!on || done) return;
            done = true;
            el.closest('.loss-card')?.classList.add('is-lit');
            if (reduce) { el.textContent = end; return; }
            const t0 = performance.now(), dur = 1400;
            const tick = (t) => { const p = Math.min(1, (t - t0) / dur); el.textContent = Math.round(end * (1 - Math.pow(1 - p, 3))); if (p < 1) requestAnimationFrame(tick); };
            requestAnimationFrame(tick);
        }, { threshold: 0.4 });
    });

    /* The headline's first word takes turns: higher, stable, bigger... The
       old word fades up and away, the gap eases to the new word's width, and
       the new one rises in. It rests while the hero is off screen or the tab
       is hidden, and never turns for a visitor who asked for less motion. */
    const rot = document.querySelector('.hp-rot');
    if (rot && !reduce) {
        const words = (rot.dataset.words || '').split(',').map((w) => w.trim()).filter(Boolean);
        const el = rot.querySelector('.hp-rot-w');
        let n = 0, timer = null, visible = true;
        const widthOf = (w) => {
            const probe = el.cloneNode(false);
            probe.classList.add('hp-rot-probe');
            probe.classList.remove('is-out', 'is-in');
            probe.textContent = w;
            rot.appendChild(probe);
            const px = probe.getBoundingClientRect().width;
            probe.remove();
            return px;
        };
        const settle = () => { rot.style.width = widthOf(el.textContent) + 'px'; };
        const turn = () => {
            if (!visible || document.hidden) return;
            n = (n + 1) % words.length;
            const next = words[n];
            el.classList.add('is-out');
            setTimeout(() => {
                rot.style.width = widthOf(next) + 'px';
                el.textContent = next;
                el.classList.remove('is-out');
                el.classList.add('is-in');
                void el.offsetWidth;
                el.classList.remove('is-in');
            }, 380);
        };
        const start = () => { clearInterval(timer); timer = setInterval(turn, 2800); };
        if (words.length > 1) {
            // Begin once the underline has drawn itself under the first word.
            setTimeout(() => {
                settle();
                start();
                if (document.fonts && document.fonts.ready) document.fonts.ready.then(settle);
                window.addEventListener('resize', settle);
                seen(rot, (on) => { visible = on; }, { threshold: 0.1 });
            }, 2300);
        }
    }

    /* The hero phone plays its films one after another while it is on screen. */
    const hero = document.querySelector('[data-hero-film]');
    if (hero) {
        const films = JSON.parse(hero.dataset.films || '[]');
        const tag = document.querySelector('[data-hero-tag]');
        let n = 0;
        hero.addEventListener('ended', () => { n = (n + 1) % films.length; const f = films[n]; swapFilm(hero, tag, f.src, f.poster, f.name); });
        seen(hero, (on) => { hero.dataset.live = on ? '1' : '0'; on ? play(hero) : hero.pause(); }, { threshold: 0.2 });
    }

    /* The phone leans toward the pointer, a little. */
    const stage = document.querySelector('[data-tilt]');
    if (stage && fine && !reduce) {
        stage.addEventListener('pointermove', (e) => {
            const r = stage.getBoundingClientRect();
            const x = (e.clientX - r.left) / r.width - .5, y = (e.clientY - r.top) / r.height - .5;
            stage.style.setProperty('--ry', (x * 14).toFixed(2) + 'deg');
            stage.style.setProperty('--rx', (-y * 10).toFixed(2) + 'deg');
        });
        stage.addEventListener('pointerleave', () => { stage.style.setProperty('--ry', '0deg'); stage.style.setProperty('--rx', '0deg'); });
    }

    /* The tour window. */
    const tour = document.querySelector('[data-tour]');
    if (tour) {
        const tv = tour.querySelector('[data-tour-video]');
        let back = null;
        const open = () => {
            back = document.activeElement;
            tour.hidden = false;
            requestAnimationFrame(() => tour.classList.add('is-open'));
            document.documentElement.style.overflow = 'hidden';
            tour.querySelector('.hp-modal-x')?.focus();
            const p = tv.play(); if (p && p.catch) p.catch(() => {});
        };
        const close = () => {
            tour.classList.remove('is-open');
            tv.pause();
            document.documentElement.style.overflow = '';
            setTimeout(() => { tour.hidden = true; }, 320);
            back?.focus?.();
        };
        document.querySelectorAll('[data-tour-open]').forEach((b) => b.addEventListener('click', open));
        tour.querySelectorAll('[data-tour-close]').forEach((b) => b.addEventListener('click', close));
        document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && tour.classList.contains('is-open')) close(); });
    }

    /* The seven steps: tabs that walk on by themselves while the section is
       on screen, until the visitor picks a step or a tool. */
    const steps = document.querySelector('[data-steps]');
    if (steps) {
        const tabs = [...steps.querySelectorAll('.hp-st-tab')];
        const panes = [...steps.querySelectorAll('.hp-st-pane')];
        const video = steps.querySelector('[data-step-film]');
        const tag = steps.querySelector('[data-step-tag]');
        const short = steps.querySelector('[data-step-short]');
        const more = steps.querySelector('[data-step-link]');
        const DWELL = 9000;
        let at = 0, timer = null, live = false, held = false;
        const pickTool = (btn) => {
            if (!btn) return;
            btn.parentElement.querySelectorAll('.hp-tool').forEach((b) => b.classList.toggle('is-on', b === btn));
            if (short) short.textContent = btn.dataset.short || '';
            if (more) more.href = btn.dataset.link || more.href;
            if (btn.dataset.src) swapFilm(video, tag, btn.dataset.src, btn.dataset.poster, btn.dataset.name);
            else if (tag) tag.textContent = btn.dataset.name || '';
        };
        const go = (i, fromUser) => {
            at = (i + tabs.length) % tabs.length;
            tabs.forEach((t, k) => {
                const on = k === at;
                t.classList.toggle('is-on', on);
                t.classList.remove('is-timing');
                t.setAttribute('aria-selected', on ? 'true' : 'false');
            });
            panes.forEach((p, k) => p.classList.toggle('is-on', k === at));
            pickTool(panes[at].querySelector('.hp-tool'));
            const list = tabs[at].parentElement;
            if (list.scrollWidth > list.clientWidth) list.scrollTo({ left: tabs[at].offsetLeft - 16, behavior: reduce ? 'auto' : 'smooth' });
            if (fromUser) held = true;
            schedule();
        };
        const schedule = () => {
            clearTimeout(timer);
            if (held || !live || reduce) return;
            void tabs[at].offsetWidth;
            tabs[at].style.setProperty('--hp-dwell', DWELL + 'ms');
            tabs[at].classList.add('is-timing');
            timer = setTimeout(() => go(at + 1, false), DWELL);
        };
        tabs.forEach((t, i) => t.addEventListener('click', () => go(i, true)));
        steps.addEventListener('keydown', (e) => {
            if (!e.target.classList.contains('hp-st-tab')) return;
            if (e.key === 'ArrowRight' || e.key === 'ArrowDown') { e.preventDefault(); go(at + 1, true); tabs[at].focus(); }
            if (e.key === 'ArrowLeft' || e.key === 'ArrowUp') { e.preventDefault(); go(at - 1, true); tabs[at].focus(); }
        });
        steps.querySelectorAll('.hp-tool').forEach((b) => b.addEventListener('click', () => {
            held = true; clearTimeout(timer); tabs[at].classList.remove('is-timing'); pickTool(b);
            // On a phone the film sits under the tools: bring it into view.
            const r = video.getBoundingClientRect();
            if (r.top > innerHeight * .55 || r.bottom < 0) video.closest('.hp-phone').scrollIntoView({ behavior: reduce ? 'auto' : 'smooth', block: 'center' });
        }));
        seen(steps.querySelector('.hp-st'), (on) => {
            live = on;
            video.dataset.live = on ? '1' : '0';
            if (on) { play(video); schedule(); } else { video.pause(); clearTimeout(timer); tabs[at].classList.remove('is-timing'); }
        }, { threshold: 0.3 });
    }

    /* Anee's answer, played once as the band comes into view. */
    const chat = document.querySelector('[data-chat]');
    if (chat) {
        const reads = [...chat.querySelectorAll('.hp-read')];
        let timers = [];
        const run = () => {
            timers.forEach(clearTimeout); timers = [];
            chat.className = 'hp-chat';
            reads.forEach((r) => r.classList.remove('is-done'));
            if (reduce) { chat.classList.add('s1', 's5', 'is-done'); reads.forEach((r) => r.classList.add('is-done')); return; }
            const at = (ms, fn) => timers.push(setTimeout(fn, ms));
            at(200, () => chat.classList.add('s1'));
            at(1100, () => chat.classList.add('s2'));
            at(2000, () => { reads[0]?.classList.add('is-done'); chat.classList.add('s3'); });
            at(2900, () => { reads[1]?.classList.add('is-done'); chat.classList.add('s4'); });
            at(3800, () => { reads[2]?.classList.add('is-done'); chat.classList.add('s5'); });
            at(4400, () => chat.classList.add('is-done'));
        };
        let ran = false;
        seen(chat, (on) => { if (on && !ran) { ran = true; run(); } }, { threshold: 0.45 });
        chat.querySelector('[data-chat-again]')?.addEventListener('click', run);
    }

    /* The reel: each film plays while it is on screen. */
    document.querySelectorAll('[data-reel-film]').forEach((v) => {
        seen(v, (on) => { if (on) { if (v.preload === 'none') v.preload = 'metadata'; play(v); } else v.pause(); }, { threshold: 0.6 });
    });

    /* The versus table ticks its answers in, row by row. */
    const vs = document.querySelector('.hp-vs');
    if (vs) vs.querySelectorAll('.hp-vs-row').forEach((r, i) => r.style.setProperty('--i', i));

    /* On a phone, the way in stays one tap away once the hero has gone by,
       and steps aside for the last call and the footer. */
    const sticky = document.querySelector('[data-sticky]');
    const heroEl = document.querySelector('[data-hero]');
    const finalEl = document.querySelector('[data-final]');
    if (sticky && heroEl && 'IntersectionObserver' in window) {
        let pastHero = false, atEnd = false;
        const paint = () => sticky.classList.toggle('is-on', pastHero && !atEnd);
        new IntersectionObserver(([e]) => { pastHero = !e.isIntersecting && e.boundingClientRect.top < 0; paint(); }).observe(heroEl);
        if (finalEl) new IntersectionObserver(([e]) => { atEnd = e.isIntersecting || e.boundingClientRect.top < 0; paint(); }, { threshold: 0.05 }).observe(finalEl);
    }
})();
</script>
@endpush
