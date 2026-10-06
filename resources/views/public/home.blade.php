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
    // The hero phone, in turn: ask Anee, draw the farm on a map, move a job
    // to another day and tick it done, then answer in a discussion. Short
    // cuts made for this spot (the full films play in How It Works).
    $heroFilms = collect([['hero-chat', 'Chat with Anee'], ['hero-maps', 'Draw your farm on a map'], ['hero-board', 'Move and finish activities'], ['hero-talk', 'Join the discussions']])
        ->map(fn ($f) => $filmOf($f[0], $f[1]))->filter()->values();
    $aneePrice = $R::priceTag($R::tierPrice('libreAnee', 'month'));
    $face = asset('images/anee/avatar-160.jpg');
    $faceLg = asset('images/anee/avatar-512.jpg');
    $arrow = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5-5 5M6 12h12"/></svg>';
    $pin = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21s-6.5-5.6-6.5-11a6.5 6.5 0 0113 0c0 5.4-6.5 11-6.5 11z"/><circle cx="12" cy="10" r="2.3"/></svg>';
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
                    <span class="hp-mark"><span class="hp-rot" data-words="Higher,Stable,Bigger,Better,Steady,Secured,Record,Greater,Maximum"><span class="hp-rot-w hp-shimmer">Higher</span></span> <span class="hp-shimmer">Yield.</span><svg class="hp-mark-line" viewBox="0 0 300 24" preserveAspectRatio="none" aria-hidden="true"><path class="a" d="M5 15 C 55 7, 105 19, 160 12 S 255 6, 295 13"/></svg></span>
                </h1>
                <p class="hp-lede animate-fade-up" style="animation-delay:.12s">
                    @if ($ph)
                        anee.io is the farm app designed for Filipino farmers who grow palay, mais and gulay. From
                        pagtatanim to ani, it helps you manage your crop, keeps track of every peso, and lets you ask
                        Anee, your smart farm technician, any time, in Filipino or English. Just as serious businesses
                        become successful and accurate through systems, smart, successful farmers use anee.io.
                    @else
                        anee.io puts your whole season on your phone: every field counted from its own day zero, every
                        task on its right day, every dollar written down, and Anee, your smart farm technician, ready with
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
                            <p class="loss-n"><span data-countup="{{ $loss['n'] }}">{{ $loss['n'] }}</span><small>%</small></p>
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
         for the word "precision" and for plain words (2026-10-05).

         Rebuilt 2026-10-06 ("make this section better"): the three rights
         are spelled out beside a picture of one of them happening (the old
         calendar's "Day 30" crossed out behind the day the crop is really
         ready, the same Lot 2 job the hero's card names), and the four
         problems read as one list, "when this happens" on the left and what
         anee.io does on the right, instead of four boxes that each repeated
         both labels. --}}
    <section class="hp-why spark-field on-dark">
        <img src="{{ asset('images/site/photos/storm-paddies.jpg') }}" alt="Farmers transplanting rice under a heavy grey sky" class="hp-why-bg" loading="lazy">
        <div class="hp-why-shade" aria-hidden="true"></div>
        <div class="relative max-w-6xl mx-auto px-4 sm:px-6 hp-sec" style="z-index:1">
            <div class="hp-prec-top">
                <div class="hp-prec-copy">
                    <div class="reveal">
                        <p class="hp-kick">The old calendar is not enough</p>
                        <h2 class="hp-h2">Modern Farming Wins by <em>Precision.</em></h2>
                        <p class="hp-p">
                            The weather no longer follows the old planting calendar. Rain comes early, dry spells last
                            longer, and pests show up before you expect them. The farms that earn well today do not work
                            harder. <b>They work with precision.</b>
                        </p>
                    </div>
                    <ol class="hp-prec-rights">
                        @foreach ([
                            ['The right work.', 'What your crop needs at the stage it is in, lot by lot.'],
                            ['The right amount.', 'How much of each material, and what it costs, before you buy.'],
                            ['The right day.', 'Each lot\'s growth stage and weather, so you act when it counts.'],
                        ] as $i => [$rt, $rw])
                            <li class="reveal" style="--reveal-delay: {{ 0.1 + $i * 0.1 }}s">
                                <span class="hp-prec-n" aria-hidden="true">{{ $i + 1 }}</span>
                                <span><b>{{ $rt }}</b><span>{{ $rw }}</span></span>
                            </li>
                        @endforeach
                    </ol>
                    <p class="hp-prec-close reveal" style="--reveal-delay: .4s">anee.io helps you get all three right, all season long.</p>
                </div>

                {{-- One right, happening: the calendar on the wall says day 30;
                     the crop says today. Drawn for the eye only (aria-hidden):
                     the copy beside it already says all of this in words. --}}
                <div class="hp-prec-vis reveal" aria-hidden="true">
                    <svg class="hp-prec-reticle" viewBox="0 0 200 200" fill="none">
                        <g class="hp-prec-spin">
                            <circle cx="100" cy="100" r="96" stroke-dasharray="3 7"/>
                            <path d="M100 0v14M100 186v14M0 100h14M186 100h14"/>
                        </g>
                        <circle cx="100" cy="100" r="70"/>
                        <circle cx="100" cy="100" r="42"/>
                        <path d="M100 40v22M100 138v22M40 100h22M138 100h22"/>
                    </svg>
                    <div class="hp-prec-old">
                        <span class="hp-prec-old-top">Old calendar</span>
                        <span class="hp-prec-old-day">30</span>
                        <span class="hp-prec-old-t">Top dress</span>
                        <svg class="hp-prec-old-x" viewBox="0 0 100 60" preserveAspectRatio="none"><path d="M8 50 L92 10"/></svg>
                    </div>
                    <div class="hp-prec-card">
                        <div class="hp-prec-hd">
                            <span class="hp-prec-lot"><i></i>Lot 2</span>
                            <span class="hp-prec-when">Today, {{ $ph ? 'DAT' : 'DAP' }} 21</span>
                            <span class="hp-prec-on"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="12" cy="12" r="8"/><circle cx="12" cy="12" r="3.2"/></svg>On target</span>
                        </div>
                        <p class="hp-prec-task">Top dress urea</p>
                        <ul class="hp-prec-checks">
                            @foreach ([
                                ['Right work', 'Active tillering. The crop needs nitrogen now.'],
                                ['Right amount', '2 bags of urea for Lot 2. No more, no less.'],
                                ['Right day', 'Today, not day 30. Warm days moved the crop ahead.'],
                            ] as $k => [$cl, $cv])
                                <li style="--k: {{ $k }}">
                                    <span class="hp-prec-chk"><svg viewBox="0 0 24 24"><path d="M5.4 12.6l4.3 4.3 8.9-9.6" pathLength="1"/></svg></span>
                                    <span><small>{{ $cl }}</small><b>{{ $cv }}</b></span>
                                </li>
                            @endforeach
                        </ul>
                        <div class="hp-prec-foot">
                            <span class="hp-prec-bar"><i></i></span>
                            <b>Nothing wasted. Nothing late.</b>
                        </div>
                    </div>
                </div>
            </div>

            <div class="hp-prec-rows">
                <div class="hp-prec-cols" aria-hidden="true"><span>When this happens</span><span>What anee.io does</span></div>
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
                    <div class="hp-prec-row reveal" style="--reveal-delay: {{ $i * 0.08 }}s; --i: {{ $i }}">
                        <div class="hp-prec-prob">
                            <span class="hp-prec-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">{!! $ico !!}</svg></span>
                            <div>
                                <h3>{{ $t }}</h3>
                                <p>{{ $w }}</p>
                            </div>
                        </div>
                        <span class="hp-prec-flow" aria-hidden="true">
                            <span class="hp-prec-flow-t">What anee.io does</span>
                            <i class="hp-prec-line"><b></b></i>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path stroke-linecap="round" stroke-linejoin="round" d="M9 6l6 6-6 6"/></svg>
                        </span>
                        <div class="hp-prec-ans">
                            <span class="hp-prec-ok">{!! $tick !!}</span>
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
                <h2 class="hp-h2">Your Whole Season in <em>{{ count($stages) }} <span class="hp-nw">Steps.<span class="hp-tick" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M5.4 12.6l4.3 4.3 8.9-9.6" pathLength="1"/></svg></span></span></em></h2>
                <p class="hp-p">Each step has its own tools, and Anee helps in every one. Tap a step, then a tool, to read what it does and watch it work on a real phone.</p>
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
                                {{-- Every tool says what it is for in one line, and the one
                                     picked opens its whole explanation below (2026-10-06,
                                     the owner: "see the explanation for each feature"). All
                                     of them are in the page; only the picked one shows. --}}
                                <div class="hp-tools">
                                    @foreach ($st['items'] as $j => $it)
                                        @php $f = $filmOf($it['key']); @endphp
                                        <button type="button" class="hp-tool{{ $j === 0 ? ' is-on' : '' }}" style="--j: {{ $j }}" data-tool="{{ $it['key'] }}"
                                                aria-pressed="{{ $j === 0 ? 'true' : 'false' }}" aria-controls="hpInfo-{{ $it['key'] }}"
                                                @if ($f) data-src="{{ $f['src'] }}" data-poster="{{ $f['poster'] }}" @endif
                                                data-name="{{ $it['name'] }}">
                                            <span class="hp-tool-ico{{ str_starts_with($it['icon'], 'anee/') ? ' is-face' : '' }}"><img src="{{ asset('images/' . $it['icon']) }}" alt="" loading="lazy"></span>
                                            <span class="hp-tool-tx">
                                                <span class="hp-tool-t">{{ $it['name'] }}@if ($it['anee'])<i class="hp-anee-tag">Anee</i>@endif</span>
                                                <span class="hp-tool-s">{{ $it['short'] }}</span>
                                            </span>
                                        </button>
                                    @endforeach
                                </div>
                                <div class="hp-infos">
                                    @foreach ($st['items'] as $j => $it)
                                        <div class="hp-info{{ $j === 0 ? ' is-on' : '' }}" id="hpInfo-{{ $it['key'] }}" data-info="{{ $it['key'] }}">
                                            <p class="hp-info-k">
                                                <span class="hp-info-ico{{ str_starts_with($it['icon'], 'anee/') ? ' is-face' : '' }}"><img src="{{ asset('images/' . $it['icon']) }}" alt="" loading="lazy"></span>
                                                <span>{{ $it['name'] }}@if ($it['anee'])<i class="hp-anee-tag">Anee does it</i>@endif</span>
                                            </p>
                                            <p class="hp-info-w">{{ $it['what'] }}</p>
                                            <ul class="hp-info-g">
                                                @foreach ($it['gets'] as $g)<li><span class="hp-info-ok">{!! $tick !!}</span><span>{{ $g }}</span></li>@endforeach
                                            </ul>
                                            <a href="{{ $toolUrl($it) }}" class="hp-info-more">Read more about it {!! $arrow !!}</a>
                                        </div>
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
                    </div>
                </div>
            </div>

            <div class="hp-cta reveal">
                <div class="hp-cta-row">
                    <a href="{{ $signup }}" class="btn btn-accent btn-lg hp-go">Start your first season free {!! $arrow !!}</a>
                    <a href="{{ route('how') }}" class="hp-alt">See all {{ $tools->count() }} tools step by step</a>
                </div>
                <p class="hp-cta-note">The season board, your lot and the growth stages are free. The other tools come with the paid plans.</p>
            </div>
        </div>
    </section>

    {{-- ================= MEET ANEE ================= --}}
    {{-- Anee, said the owner's way (2026-10-06): a smart farm technician, not
         "an AI": she reads the whole farm before she answers, so the answer
         is for this field and it comes in seconds. Three parts: what she
         reads (lit one by one, on a loop), her answer playing on a loop in
         the chat window, and what a technician visit costs against her.
         Her price is not said here (the owner took that note out); the
         pricing section says it. --}}
    @php
        $chat = $HW::chat();
        // On this page the chat shows what she reads, the same list as beside it.
        $chat['reading'] = ['Reading your photo', 'Checking Lot 2, its stage and your protocol', 'Checking the weather and your past seasons', 'Searching the latest research'];
        $aneeReads = [
            ['Your lots', 'Each field, its crop, variety and growth stage today.', 'map'],
            ['Your protocol', 'Your plan, and what you already applied.', 'plan'],
            ['Weather and climate', 'The forecast for your farm and your town\'s climate record.', 'cloud'],
            ['Your history', 'Past seasons, harvests and the notes you kept.', 'chart'],
            ['Your field today', 'The photos you send of what you see right now.', 'camera'],
            ['Online research', 'The latest studies, guides and product labels.', 'search'],
            ['Farm knowledge', 'What anee.io knows about 85 ' . ($ph ? 'Philippine ' : '') . 'crops, stage by stage.', 'book'],
        ];
        $aneeVs = [
            ['Time', 'clock', 'Days of waiting for a visit, if one is free.', 'An answer in seconds, day or night.', true],
            ['Cost', 'peso', 'A visit fee, plus travel and a day away from the field.', 'Comes with your plan. Each answer uses a few credits.', false],
            ['Precision', 'target', 'General advice from a short look at the field, without your records.', 'Built on your lot, its stage, the weather and what you already applied.', false],
            ['Efficiency', 'bolt', 'The problem keeps spreading while you wait.', 'Act today, while the problem is still small.', false],
            ['Knows your farm', 'user', 'Starts from zero at every visit.', 'Knows your lots, your plan and your past seasons.', false],
            ['Follow up questions', 'repeat', 'Another visit, another fee.', 'Ask again any time. She picks up where you left off.', false],
            ['Advice you can trust', 'shield', 'Hard to find one who is reliable and not there to sell you a product.', 'Unbiased. Her advice starts from what your field needs, not from a product to sell.', false],
            ['Always there', 'sun', 'Busy in planting season, when every farm needs help at once.', 'Always available, on weekends and holidays too.', false],
        ];
        $svg = fn ($k, $w = '1.9') => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="' . $w . '" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="' . ['map' => 'M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7', 'plan' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01', 'cloud' => 'M3 15a4 4 0 004 4h9a5 5 0 10-.9-9.95A5.5 5.5 0 006.5 8 4.5 4.5 0 003 15z', 'chart' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z', 'camera' => 'M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9zM15 13a3 3 0 11-6 0 3 3 0 016 0z', 'search' => 'M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z', 'book' => 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253', 'clock' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z', 'peso' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z', 'target' => 'M12 21a9 9 0 100-18 9 9 0 000 18zm0-4a5 5 0 100-10 5 5 0 000 10zm0-4a1 1 0 100-2 1 1 0 000 2z', 'bolt' => 'M13 10V3L4 14h7v7l9-11h-7z', 'user' => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z', 'repeat' => 'M4 4v5h5M20 20v-5h-5M5.1 15a7.5 7.5 0 0013.4 2M18.9 9A7.5 7.5 0 005.5 7', 'shield' => 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z', 'sun' => 'M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z'][$k] . '"/></svg>';
        $xMark = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.8" aria-hidden="true"><path stroke-linecap="round" d="M7 7l10 10M17 7L7 17"/></svg>';
    @endphp
    <section class="hp-anee spark-field on-dark">
        <div class="relative max-w-6xl mx-auto px-4 sm:px-6 hp-sec" style="z-index:1">
            <div class="hp-anee-grid">
                <div class="reveal">
                    <p class="hp-kick">Meet Anee</p>
                    <h2 class="hp-h2">Your Smart Farm Technician <em>Already Knows Your Farm.</em></h2>
                    <p class="hp-p">
                        Anee is not a chatbot that guesses. She is a <b>smart farm technician</b> who studies your whole farm
                        before she answers, then gives you the most accurate answer for your field in seconds. That gives you
                        what every farm needs more of: <b>time, precision, efficiency and lower cost.</b>
                    </p>

                    <p class="hp-anee-k">What Anee reads before she answers</p>
                    <ul class="hp-anee-reads" data-reads>
                        @foreach ($aneeReads as [$rt, $rw, $ri])
                            <li>
                                <span class="hp-anee-ri">{!! $svg($ri) !!}</span>
                                <span><b>{{ $rt }}</b><small>{{ $rw }}</small></span>
                            </li>
                        @endforeach
                    </ul>
                    <p class="hp-anee-out" data-reads-out>
                        <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                        Then your answer, in seconds
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
                            <span><b>Anee</b><small><i class="hp-live"></i>Your smart farm technician</small></span>
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
                    </div>
                </div>
            </div>

            {{-- A technician visit against Anee: what it costs, how long it
                 takes, and what the advice is built on. Fair to the people
                 who do the job; the point is the waiting and the guesswork. --}}
            <div class="hp-tvs">
                <div class="hp-tvs-head reveal">
                    <p class="hp-kick">A technician visit or Anee</p>
                    <h3 class="hp-tvs-h">Expert Help Should Not <em>Take Days.</em></h3>
                    <p class="hp-tvs-p">
                        A technician visit costs money and takes days to arrange, and the advice comes from a short look at
                        your field. A reliable one is hard to find, and some are really there to sell a product. By the time
                        help comes, the problem has spread. Anee is always available, unbiased, and answers right away from
                        everything she knows about your farm.
                    </p>
                </div>
                <div class="hp-tvs-t reveal">
                    <div class="hp-tvs-cols" aria-hidden="true">
                        <span></span>
                        <span class="is-old">{!! $svg('user', '2') !!}A technician visit</span>
                        <span class="is-new"><img src="{{ $face }}" alt="">Anee</span>
                    </div>
                    @foreach ($aneeVs as $i => [$vk, $vi, $vo, $vn, $race])
                        <div class="hp-tvs-row" style="--i: {{ $i }}">
                            <b class="hp-tvs-k"><span>{!! $svg($vi) !!}</span>{{ $vk }}</b>
                            <div class="hp-tvs-old">
                                <i>{!! $xMark !!}</i>
                                <p><em class="hp-vs-label">A technician visit:</em> {{ $vo }}
                                    @if ($race)<span class="hp-tvs-race is-slow" aria-hidden="true"><i></i><b>Days of waiting</b></span>@endif
                                </p>
                            </div>
                            <div class="hp-tvs-new">
                                <i>{!! $tick !!}</i>
                                <p><em class="hp-vs-label">Anee:</em> {{ $vn }}
                                    @if ($race)<span class="hp-tvs-race is-fast" aria-hidden="true"><i></i><b>Seconds</b></span>@endif
                                </p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    {{-- ================= ANEE KNOWS YOUR FARM ================= --}}
    {{-- Anee's farm analysis (2026-10-06): everything she checks (the
         tasks done, notes, photos, reports, weather, growth stages, deep farm
         knowledge and online research) circling her, and five of the tools
         that turn it into answers, each a tab with a small moving picture:
         Realign by Anee, Analyze So Far, the Season Report, Compare Reports
         and a report or analysis attached to a chat. All five are in the
         app (HowItWorks: realign, sofar, season, compare, and the attach chip
         on Anee's chat). --}}
    @php
        $akTools = [
            ['realign', 'Realign growth stages', 'When a crop runs ahead or behind, Anee reads the lot and finds the stage it is truly in.', '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h5M20 20v-5h-5M5.1 15a7.5 7.5 0 0013.4 1.5M18.9 9A7.5 7.5 0 005.5 7.5"/></svg>'],
            ['sofar', 'Analyze So Far', 'Halfway through the season, she checks each lot, the risks ahead and what to do next.', '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12h4l3-8 4 16 3-8h4"/></svg>'],
            ['season', 'Season Report', 'After harvest, a plain story of your season: what went well, what went wrong, what to change.', '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.5a.56.56 0 011.04 0l2.13 5.11 5.52.44a.56.56 0 01.32.99l-4.2 3.6 1.28 5.38a.56.56 0 01-.84.61L12 16.77l-4.73 2.86a.56.56 0 01-.84-.61l1.28-5.38-4.2-3.6a.56.56 0 01.32-.99l5.52-.44 2.13-5.11z"/></svg>'],
            ['compare', 'Compare Reports', 'Two seasons side by side, line by line, and Anee explains what changed and why.', '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v18M5 7h14M5 7l-3 7a4 4 0 006 0L5 7zm14 0l-3 7a4 4 0 006 0l-3-7z"/></svg>'],
            ['attach', 'Attach to a chat', 'Attach any report or analysis to a chat and ask Anee about it in your own words.', '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>'],
        ];
    @endphp
    <section class="hp-sec hp-ak">
        <div class="max-w-7xl mx-auto px-4 sm:px-6">
            <div class="hp-head reveal">
                <p class="hp-kick">Anee's farm analysis</p>
                <h2 class="hp-h2">Anee Knows Your Farm <em>Better Than Anyone.</em></h2>
                <p class="hp-p">
                    Anee never answers from one question alone. She checks everything you did this season, every note and
                    every photo, your reports and your weather, then adds deep farm knowledge and fresh research from the
                    internet. <b>That is how you get the most accurate answer possible, for your own field.</b>
                </p>
            </div>

            <div class="hp-ak-grid">
                {{-- Everything she checks, circling her. --}}
                <div class="hp-ak-orbit reveal" aria-label="What Anee checks: every task you did, your notes, photos and reports, weather data, growth stages, deep farm knowledge and online research">
                    <span class="hp-ak-ring is-1" aria-hidden="true"></span><span class="hp-ak-ring is-2" aria-hidden="true"></span>
                    {{-- Anee thinking, the same clip her wait screen plays in the app. --}}
                    <span class="hp-ak-core" aria-hidden="true"><video src="{{ asset('videos/anee/thinking.mp4') }}" poster="{{ asset('videos/anee/thinking.jpg') }}"
                        muted playsinline loop preload="metadata" disablepictureinpicture disableremoteplayback tabindex="-1" data-ak-think></video><i>Analyzing</i></span>
                    <span class="hp-ak-spin" aria-hidden="true"><span class="hp-ak-src" style="--n: 0"><span class="hp-ak-in"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg><b>Every task you did</b></span></span><span class="hp-ak-src" style="--n: 1"><span class="hp-ak-in"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg><b>Your notes</b></span></span><span class="hp-ak-src" style="--n: 2"><span class="hp-ak-in"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9zM15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg><b>Your photos</b></span></span><span class="hp-ak-src" style="--n: 3"><span class="hp-ak-in"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg><b>Your reports</b></span></span><span class="hp-ak-src" style="--n: 4"><span class="hp-ak-in"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 15a4 4 0 004 4h9a5 5 0 10-.9-9.95A5.5 5.5 0 006.5 8 4.5 4.5 0 003 15z"/></svg><b>Weather data</b></span></span><span class="hp-ak-src" style="--n: 5"><span class="hp-ak-in"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21v-9m0 0C12 7 8 5 4 5c0 4 3 7 8 7zm0 0c0-4 3-7 8-7 0 4-4 7-8 7z"/></svg><b>Growth stages</b></span></span><span class="hp-ak-src" style="--n: 6"><span class="hp-ak-in"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg><b>Deep farm knowledge</b></span></span><span class="hp-ak-src" style="--n: 7"><span class="hp-ak-in"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z"/></svg><b>Online research</b></span></span></span>
                </div>

                <p class="hp-ak-names" aria-hidden="true">@foreach (['Every task you did', 'Your notes', 'Your photos', 'Your reports', 'Weather data', 'Growth stages', 'Deep farm knowledge', 'Online research'] as $nm)<span>{{ $nm }}</span>@endforeach</p>

                {{-- What she turns it into. --}}
                {{-- One tool at a time, fading into the next; its name rides
                     in the card, and the dots under it pick one by hand. --}}
                <div class="hp-ak-tools reveal" data-ak>
                    <div class="hp-ak-panes">
                        @foreach ($akTools as $i => [$tk, $tt, $td, $ti])
                            <div class="hp-ak-pane{{ $i === 0 ? ' is-on' : '' }}" data-ak-pane="{{ $tk }}" id="hpAk-{{ $tk }}" role="tabpanel" aria-label="{{ $tt }}">
                                <div class="hp-ak-ph">
                                    <span class="hp-ak-ti">{!! $ti !!}</span>
                                    <h3>{{ $tt }}</h3>
                                    <span class="hp-ak-num">{{ $i + 1 }} of {{ count($akTools) }}</span>
                                </div>
                                <p class="hp-ak-desc">{{ $td }}</p>
                                <div class="hp-ak-show" aria-hidden="true">
                                    @if ($tk === 'realign')
                                        <div class="hp-ak-re">
                                            <p class="hp-ak-k">Lot 2 · NSIC Rc222</p>
                                            <div class="hp-ak-track">
                                                @foreach (['Seedling', 'Tillering', 'Panicle initiation', 'Flowering', 'Ripening'] as $si => $sn)<span class="hp-ak-stage" style="--s: {{ $si }}">{{ $sn }}</span>@endforeach
                                                <i class="hp-ak-mark is-cal"><b>Calendar</b></i><i class="hp-ak-mark is-real"><b>Anee found</b></i>
                                            </div>
                                            <p class="hp-ak-note"><span class="hp-ak-dot"></span>The crop is 6 days ahead of the calendar. Lot 2's stage count moves to match, and your calendar dates stay.</p>
                                        </div>
                                    @elseif ($tk === 'sofar')
                                        <div class="hp-ak-list">
                                            <div class="hp-ak-row" style="--k: 0"><b>Lot 1</b><span class="is-good">On track</span></div>
                                            <div class="hp-ak-row" style="--k: 1"><b>Lot 2</b><span class="is-warn">Water low, irrigate this week</span></div>
                                            <div class="hp-ak-row" style="--k: 2"><b>Risk ahead</b><span class="is-bad">Rice bug at flowering</span></div>
                                            <div class="hp-ak-row" style="--k: 3"><b>Next step</b><span class="is-good">Scout Lot 1 at 70 DAT</span></div>
                                        </div>
                                    @elseif ($tk === 'season')
                                        <div class="hp-ak-report">
                                            <p class="hp-ak-k">Season report · Wet season palay 2026</p>
                                            <div class="hp-ak-rep" style="--k: 0"><span class="is-good">What went well</span><i></i><i></i></div>
                                            <div class="hp-ak-rep" style="--k: 1"><span class="is-bad">What went wrong</span><i></i><i></i></div>
                                            <div class="hp-ak-rep" style="--k: 2"><span class="is-next">What to change next season</span><i></i><i></i></div>
                                        </div>
                                    @elseif ($tk === 'compare')
                                        <div class="hp-ak-cmp">
                                            @foreach ([['Yield', 72, 86], ['Fertilizer cost', 80, 64], ['Labor days', 70, 58]] as $ci => [$cl, $ca, $cb])
                                                <div class="hp-ak-cmprow" style="--k: {{ $ci }}">
                                                    <b>{{ $cl }}</b>
                                                    <span class="hp-ak-bar is-a" style="--w: {{ $ca }}%"><i></i></span>
                                                    <span class="hp-ak-bar is-b" style="--w: {{ $cb }}%"><i></i></span>
                                                </div>
                                            @endforeach
                                            <p class="hp-ak-legend"><span class="is-a">Dry season</span><span class="is-b">Wet season</span></p>
                                            <p class="hp-ak-note"><img src="{{ $face }}" alt="">Yield went up while fertilizer went down: the split urea doses landed on the right days.</p>
                                        </div>
                                    @else
                                        <div class="hp-ak-chat">
                                            <span class="hp-ak-chip"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>Profit Report · Wet season 2026</span>
                                            <p class="hp-ak-q">Why is Lot 2 earning less than Lot 1?</p>
                                            <p class="hp-ak-a"><img src="{{ $face }}" alt=""><span>Lot 2 spent more on labor for two extra weedings, and its yield per hectare was lower after the late top dress.</span></p>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div class="hp-ak-picks" role="tablist" aria-label="Anee's analysis tools">
                        @foreach ($akTools as $i => [$tk, $tt])
                            <button type="button" role="tab" class="hp-ak-pick{{ $i === 0 ? ' is-on' : '' }}" data-ak-tab="{{ $tk }}" aria-controls="hpAk-{{ $tk }}"
                                    aria-selected="{{ $i === 0 ? 'true' : 'false' }}" aria-label="{{ $tt }}"><i aria-hidden="true"></i></button>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="hp-cta reveal">
                <div class="hp-cta-row">
                    <a href="{{ $ask }}" class="btn btn-accent btn-lg hp-go">Ask Anee a free question {!! $arrow !!}</a>
                    <a href="{{ $signup }}" class="hp-alt">Create your free account</a>
                </div>
            </div>
        </div>
    </section>

    {{-- ================= YOUR FARM IS A BUSINESS, BUILT BY FARMERS ================= --}}
    {{-- The second half of the argument (every business around the farm
         already runs on a system, so what does the farmer have?), shown
         rather than told (2026-10-06): the businesses tick one by one and
         the farmer's "What do you have?" turns into anee.io; the four habits
         of a farm business; and who is saying it, the people who built
         anee.io, who are farmers themselves and run their farms on it. --}}
    @php
        $bizIco = [
            'store' => 'M3 9l1.5-5h15L21 9M3 9h18M3 9v10a1 1 0 001 1h16a1 1 0 001-1V9M9 20v-6h6v6',
            'phone' => 'M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z',
            'screen' => 'M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z',
            'sprout' => 'M12 21v-9m0 0C12 7 8 5 4 5c0 4 3 7 8 7zm0 0c0-4 3-7 8-7 0 4-4 7-8 7z',
            'food' => 'M6 3v18M3 3v5a3 3 0 006 0V3M17 21V3c-2 1-3 4-3 8h3',
            'gym' => 'M6 7v10M3 9v6M18 7v10M21 9v6M6 12h12',
            'ride' => 'M5 18a3 3 0 100-6 3 3 0 000 6zm14 0a3 3 0 100-6 3 3 0 000 6zM5 15h3l3-6h4l4 6M13 9l-1.5-3H9',
            'work' => 'M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z',
        ];
        // Every business around the farm already runs on a system (the
        // owner's list, 2026-10-06). The farm is the last one.
        $others = $ph
            ? [['Sari-sari store', 'Point of sale and inventory', 'store'], ['Habal-habal', 'A booking app', 'ride'], ['Restaurant', 'Online menu and ordering', 'food'],
               ['Gym', 'A membership app', 'gym'], ['Raketero', 'Booked online', 'work']]
            : [['Corner store', 'Point of sale and inventory', 'store'], ['Motorbike rides', 'A booking app', 'ride'], ['Restaurant', 'Online menu and ordering', 'food'],
               ['Gym', 'A membership app', 'gym'], ['Freelancer', 'Booked online', 'work']];
    @endphp
    <section class="hp-sec bg-brand-mesh bg-drift">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 hp-biz">
            <div class="hp-biz-left">
                <div class="hp-biz-pics reveal">
                    {{-- Planting day, run from a phone (the owner's photo, 2026-10-06:
                         brightened a touch, 1200 and 760 wide webp). --}}
                    <img src="{{ asset('images/site/photos/farmer-phone-transplanting.webp') }}"
                         srcset="{{ asset('images/site/photos/farmer-phone-transplanting-760.webp') }} 760w, {{ asset('images/site/photos/farmer-phone-transplanting.webp') }} 1200w"
                         sizes="(min-width: 1024px) 34rem, 100vw" width="1200" height="800"
                         alt="A smiling farmer reads anee.io on his phone while his crew transplants palay in the flooded paddy behind him"
                         title="Planting day, run from a phone with anee.io" class="hp-biz-a" loading="lazy" decoding="async">
                </div>

                {{-- Who is saying it: the people who built anee.io are farmers too. --}}
                <figure class="hp-biz-quote reveal">
                    <svg class="hp-biz-qm" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M9.6 6C6.5 7.3 4.5 10 4.5 13.4c0 2.6 1.6 4.6 3.9 4.6 1.9 0 3.3-1.4 3.3-3.2 0-1.8-1.3-3.1-3-3.1-.3 0-.6 0-.8.1.4-1.7 1.8-3.3 3.6-4.2L9.6 6zm9 0c-3.1 1.3-5.1 4-5.1 7.4 0 2.6 1.6 4.6 3.9 4.6 1.9 0 3.3-1.4 3.3-3.2 0-1.8-1.3-3.1-3-3.1-.3 0-.6 0-.8.1.4-1.7 1.8-3.3 3.6-4.2L18.6 6z"/></svg>
                    <p class="hp-biz-qk"><span class="hp-heart" aria-hidden="true"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M11.645 20.91l-.007-.003-.022-.012a15.247 15.247 0 01-.383-.218 25.18 25.18 0 01-4.244-3.17C4.688 15.36 2.25 12.174 2.25 8.25 2.25 5.322 4.714 3 7.688 3A5.5 5.5 0 0112 5.052 5.5 5.5 0 0116.313 3c2.973 0 5.437 2.322 5.437 5.25 0 3.925-2.438 7.111-4.739 9.256a25.175 25.175 0 01-4.244 3.17 15.247 15.247 0 01-.383.219l-.022.012-.007.004-.003.001a.752.752 0 01-.704 0l-.003-.001z"/></svg></span>Built by farmers, for farmers</p>
                    <blockquote>
                        We are farmers too. The guessing, the rising costs and the notebook that never adds up are our problems
                        as well, every season. So we built anee.io for our own farms, and we run them on it every day. Every tool
                        in it is there because we needed it first, and that's why we know you need it too.
                    </blockquote>
                    <figcaption>
                        <img src="{{ asset('images/site/photos/powered-by.jpg') }}" alt="The farmers behind anee.io in their own rice field" title="The farmers behind anee.io" loading="lazy" decoding="async">
                        <span><b>The farmers behind anee.io</b><small>Made in the field, not in an office.</small></span>
                    </figcaption>
                </figure>
            </div>

            <div>
                <div class="reveal">
                    <p class="hp-kick">Your farm is a business</p>
                    <h2 class="hp-h2">Your Farm Is a Business. <em>Run It Like One.</em></h2>
                    <p class="hp-p">
                        @if ($ph)
                            The sari-sari store has a point of sale and keeps its inventory. Habal-habal riders get booked by
                            app. The restaurant down the street has an online menu and takes orders. Even gyms and raketeros
                            run on an app.
                        @else
                            The corner store has a point of sale and keeps its inventory. Riders get booked by app. The
                            restaurant down the street has an online menu and takes orders. Even gyms and freelancers run on an
                            app.
                        @endif
                        <b>So what about you, the farmer? What do you have?</b> For most farms, it is still memory and an old
                        notebook. <b>Now you have anee.io:</b> one system for your whole farm, made for the field and priced
                        for farmers.
                    </p>
                </div>

                {{-- Everyone else upgraded: the businesses tick one by one, then
                     the farm's notebook turns into anee.io. --}}
                <p class="hp-biz-k reveal">Every business has a system. Now farms do too.</p>
                <div class="hp-biz-up reveal" aria-label="The businesses around you already run on a system. Now farms do too.">
                    @foreach ($others as $k => [$ot, $os, $oi])
                        <div class="hp-biz-u" style="--k: {{ $k }}">
                            <span class="hp-biz-ui"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $bizIco[$oi] }}"/></svg></span>
                            <b>{{ $ot }}</b><small>{{ $os }}</small>
                            <i class="hp-biz-ok" aria-hidden="true">{!! $tick !!}</i>
                        </div>
                    @endforeach
                    <div class="hp-biz-u is-farm" style="--k: {{ count($others) }}">
                        <span class="hp-biz-ui"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $bizIco['sprout'] }}"/></svg></span>
                        <b>You, the farmer</b>
                        <small class="hp-biz-swap"><span class="is-q">What do you have?</span><span class="is-a">Now you have anee.io</span></small>
                        <i class="hp-biz-ok" aria-hidden="true">{!! $tick !!}</i>
                    </div>
                </div>

                <p class="hp-biz-k reveal">The four habits of a farm business</p>
                <div class="hp-gains">
                    @foreach ([
                        ['Plan before you spend', 'Every bag and every job counted before the season starts.', '<path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>'],
                        ['Know every cost', 'Labor, materials and services add up as the work gets done.', '<path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>'],
                        ['See your real profit', 'Harvest sales against every cost, season after season.', '<path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>'],
                        ['Repeat what works', 'Your best lot becomes next season\'s recipe.', '<path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h5M20 20v-5h-5M5.1 15a7.5 7.5 0 0013.4 1.5M18.9 9A7.5 7.5 0 005.5 7.5"/>'],
                    ] as $i => [$t, $gp, $ico])
                        <div class="hp-gain reveal" style="--reveal-delay: {{ $i * 0.07 }}s">
                            <span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">{!! $ico !!}</svg></span>
                            <div><b>{{ $t }}</b><small>{{ $gp }}</small></div>
                        </div>
                    @endforeach
                </div>

                <div class="hp-cta is-left reveal">
                    <div class="hp-cta-row">
                        <a href="{{ $signup }}" class="btn btn-accent btn-lg hp-go">Start free and upgrade your farm {!! $arrow !!}</a>
                        <a href="{{ route('about') }}" class="hp-alt">Read our story</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ================= YOUR TEAM IN THE FIELD, YOUR FARM FROM SPACE ================= --}}
    {{-- For the farm owner (2026-10-06): be in the field without being in
         it. A feed of the day as the workers send it in (a tick, photos, a
         voice note, a note written with no signal, Anee in the Collab
         Room), the six team tools that make it happen, and the field health
         maps from space, with a satellite over the paddies and radar rings
         passing through the clouds.

         The satellite maps (optical plus radar, every 5 to 10 days) are the
         owner's coming feature, said here as the product; the owner asked
         for it this way. Keep the words in line with what ships. --}}
    <section class="hp-sec bg-white bg-drift">
        <div class="max-w-7xl mx-auto px-4 sm:px-6">
            <div class="hp-head reveal">
                <p class="hp-kick">For farm owners</p>
                <h2 class="hp-h2">Be in the Field <em>Without Being in the Field.</em></h2>
                <p class="hp-p">
                    Your workers carry the farm in their pocket. They tick each job as it gets done, snap photos, record short
                    videos and leave voice notes, even at the far lot with no signal. You see every update from wherever you
                    are, plan with them on maps and drawings, and talk it through in the Collab Room, where they have their own
                    accounts, share cameras and locations, call as a team and tick off their tasks. <b>It feels like you are
                    standing in your field, even when you are far away.</b>
                </p>
            </div>

            <div class="hp-team">
                {{-- The day as it comes in from the field. --}}
                <div class="hp-feed reveal" data-feed aria-label="An example of updates from your workers during the day">
                    <div class="hp-feed-top">
                        <span><b>Today on your farm</b><small>Wet season palay 2026</small></span>
                        <span class="hp-feed-live"><i class="hp-live"></i>Live from the field</span>
                    </div>
                    <ol class="hp-feed-list">
                        <li class="hp-feed-item">
                            <span class="hp-feed-av" style="--h: 205">JD</span>
                            <div class="hp-feed-tx">
                                <p><b>Juan</b> <span>on Lot 2</span><time>7:48 AM</time></p>
                                <p>Hand weeding is done.</p>
                                <span class="hp-feed-done">{!! $tick !!}Ticked done</span>
                            </div>
                        </li>
                        <li class="hp-feed-item">
                            <span class="hp-feed-av" style="--h: 330">MS</span>
                            <div class="hp-feed-tx">
                                <p><b>Maria</b> <span>on Lot 1</span><time>9:15 AM</time></p>
                                <p>Sent 2 photos from the field.</p>
                                <span class="hp-feed-pics">
                                    <img src="{{ asset('images/site/home-team/field.webp') }}" alt="" width="200" height="150" loading="lazy">
                                    <img src="{{ asset('images/site/home-team/palay-heads.webp') }}" alt="" width="200" height="150" loading="lazy">
                                </span>
                            </div>
                        </li>
                        <li class="hp-feed-item">
                            <span class="hp-feed-av" style="--h: 30">PR</span>
                            <div class="hp-feed-tx">
                                <p><b>Pedro</b> <span>on Lot 2</span><time>10:02 AM</time></p>
                                <p>Recorded a voice note.</p>
                                <span class="hp-feed-voice" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" fill="currentColor"><path d="M8 5.14v13.72L19 12 8 5.14z"/></svg>
                                    <span class="hp-feed-wave">@for ($w = 0; $w < 16; $w++)<i style="--w: {{ [5, 9, 13, 7, 11, 15, 8, 12, 6, 14, 10, 7, 12, 9, 5, 8][$w] }}; --d: {{ $w * 0.07 }}s"></i>@endfor</span>
                                    <b>0:18</b>
                                </span>
                            </div>
                        </li>
                        <li class="hp-feed-item">
                            <span class="hp-feed-av" style="--h: 205">JD</span>
                            <div class="hp-feed-tx">
                                <p><b>Juan</b> <span>at the far lot</span><time>11:20 AM</time></p>
                                <p>Wrote a note with no signal: the canal gate is leaking.</p>
                                <span class="hp-feed-sync"><i class="is-off">Saved on the phone</i><i class="is-on">{!! $tick !!}Synced</i></span>
                            </div>
                        </li>
                        <li class="hp-feed-item is-anee">
                            <img class="hp-feed-av" src="{{ $face }}" alt="">
                            <div class="hp-feed-tx">
                                <p><b>Anee</b> <span>in the Collab Room</span><time>11:31 AM</time></p>
                                <p>Rain after 3 PM. Spray Lot 1 this morning.</p>
                            </div>
                        </li>
                    </ol>
                </div>

                <div class="hp-team-cards">
                    @foreach ([
                        ['Collab Room', 'A room for each season: chat with photos and voice notes, a shared whiteboard, live cameras, and Anee answering your whole team.', 'M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a1.994 1.994 0 01-1.414-.586m0 0L11 14h4a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2v4l.586-.586z'],
                        ['Plan on the map', 'Trace each field on a satellite view, measure it, and pin the pump, the gate or the spot that floods.', 'M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7'],
                        ['Draw it together', 'Sketch the plan over a photo of the field, so every worker follows the same picture.', 'M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z'],
                        ['Offline mode', 'No signal at the far lot? Your workers keep ticking tasks and writing notes offline, and it all comes in once the signal is back.', 'M3 3l18 18M8.5 16.5a5 5 0 017 0M5 12.86a10 10 0 015.17-2.69M19 12.86a10 10 0 00-2.07-1.55M2 8.82a15 15 0 014.17-2.65M22 8.82a15 15 0 00-11.29-3.76M12 20h.01'],
                        ['Photos, videos and voice', 'They snap it, film it or just say it. You get proof from the field, saved with the day and the task.', 'M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z'],
                        ['A login for each worker', 'Each one sees only what you allow, and the day\'s plan lands in every inbox at 6 AM.', 'M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z'],
                    ] as $i => [$ct, $cp, $ci])
                        <div class="hp-team-card reveal" style="--reveal-delay: {{ ($i % 2) * 0.07 }}s">
                            <span class="hp-team-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $ci }}"/></svg></span>
                            <div><h3>{{ $ct }}</h3><p>{{ $cp }}</p></div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- The Collab Room, in a window of its own (2026-10-06): its seven
                 tools as tabs that take turns on a loop, each with a small
                 moving picture of what it does. Every tool here is in the
                 app's room (sm/collab: chat, call, cameras, where we are,
                 activities, drawing) or the team logins. --}}
            @php
                $rooms = [
                    ['accounts', 'Worker accounts', 'Each worker gets a login and sees only what you allow.', 'M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z'],
                    ['chat', 'Group chat', 'One chat per season, with photos, videos and voice notes.', 'M8 12h.01M12 12h.01M16 12h.01M21 12a8 8 0 01-11.6 7.1L3 20l1-5.5A8 8 0 1121 12z'],
                    ['call', 'Group call', 'Call the whole team at once, right from the room.', 'M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z'],
                    ['cameras', 'Camera sharing', 'A worker points the phone, and you see the field live.', 'M15 10l4.55-2.28A1 1 0 0121 8.62v6.76a1 1 0 01-1.45.9L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z'],
                    ['location', 'Location sharing', 'See who is near which lot, live on one map.', 'M17.657 16.657L13.414 20.9a2 2 0 01-2.828 0l-4.243-4.243a8 8 0 1111.314 0zM15 11a3 3 0 11-6 0 3 3 0 016 0z'],
                    ['tasks', 'Team tasks', 'Give each job to a worker and watch it get ticked done.', 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4'],
                    ['board', 'Whiteboard', 'Draw the plan together, on a blank board or a photo.', 'M15.232 5.232l3.536 3.536M9 11l6-6 3 3-6 6H9v-3zM4 20h16'],
                ];
            @endphp
            <div class="hp-room reveal" data-room>
                <div class="hp-room-copy">
                    <p class="hp-kick">The Collab Room</p>
                    <h3 class="hp-room-h">Your Whole Team <em>in One Room.</em></h3>
                    <p class="hp-room-p">
                        Every season gets its own room. Your workers sign in with their own accounts, and the team talks,
                        calls, shares cameras and locations, and runs the day's tasks in one place. Anee sits in the room
                        too, so anyone can ask her while the work goes on.
                    </p>
                    <div class="hp-room-tabs" role="tablist" aria-label="What the Collab Room does">
                        @foreach ($rooms as $i => [$rk, $rt, $rd, $ri])
                            <button type="button" role="tab" class="hp-room-tab{{ $i === 0 ? ' is-on' : '' }}" data-room-tab="{{ $rk }}"
                                    aria-selected="{{ $i === 0 ? 'true' : 'false' }}" aria-controls="hpRoom-{{ $rk }}">
                                <span class="hp-room-ti"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $ri }}"/></svg></span>
                                <span><b>{{ $rt }}</b><small>{{ $rd }}</small></span>
                                <i class="hp-room-bar" aria-hidden="true"></i>
                            </button>
                        @endforeach
                    </div>
                </div>

                {{-- The room itself: a window with a pane per tool. --}}
                <div class="hp-room-win" aria-hidden="true">
                    <div class="hp-room-top">
                        <span class="hp-room-dots"><i></i><i></i><i></i></span>
                        <b>Collab Room</b><small>Wet season palay 2026</small>
                        <span class="hp-room-on"><i class="hp-live"></i>4 online</span>
                    </div>
                    <div class="hp-room-panes">
                        {{-- Worker accounts --}}
                        <div class="hp-room-pane is-on" id="hpRoom-accounts" data-room-pane="accounts">
                            <p class="hp-room-ph">Team logins</p>
                            @foreach ([['JD', 205, 'Juan', 'Foreman', ['Activities' => 'Edit', 'Inventory' => 'View']], ['MS', 330, 'Maria', 'Worker', ['Activities' => 'Edit', 'Reports' => 'None']], ['PR', 30, 'Pedro', 'Worker', ['Activities' => 'View', 'Notes' => 'Edit']]] as $k => [$ia, $ih, $in, $ir, $perm])
                                <div class="hp-acc" style="--k: {{ $k }}">
                                    <span class="hp-feed-av" style="--h: {{ $ih }}">{{ $ia }}</span>
                                    <span class="hp-acc-who"><b>{{ $in }}</b><small>{{ $ir }}</small></span>
                                    <span class="hp-acc-perms">@foreach ($perm as $pm => $pv)<i class="is-{{ strtolower($pv) }}">{{ $pm }}: {{ $pv }}</i>@endforeach</span>
                                </div>
                            @endforeach
                        </div>
                        {{-- Group chat --}}
                        <div class="hp-room-pane" id="hpRoom-chat" data-room-pane="chat">
                            <div class="hp-rc is-them" style="--k: 0"><span class="hp-feed-av" style="--h: 330">MS</span><p>Good morning po. The water in Lot 2 is low near the gate.</p></div>
                            <div class="hp-rc is-me" style="--k: 1"><p>Thanks Maria. I will open the canal at 7.</p></div>
                            <div class="hp-rc is-them" style="--k: 2"><span class="hp-feed-av" style="--h: 30">PR</span><p class="hp-rc-voice">{!! '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M8 5.14v13.72L19 12 8 5.14z"/></svg>' !!}<span class="hp-feed-wave">@for ($w = 0; $w < 12; $w++)<i style="--w: {{ [6, 11, 8, 14, 9, 12, 5, 13, 7, 10, 6, 9][$w] }}; --d: {{ $w * 0.08 }}s"></i>@endfor</span><b>0:12</b></p></div>
                            <div class="hp-rc is-them" style="--k: 3"><span class="hp-feed-av" style="--h: 205">JD</span><p>Canal is open po. Lot 2 is filling up now.</p></div>
                        </div>
                        {{-- Group call --}}
                        <div class="hp-room-pane" id="hpRoom-call" data-room-pane="call">
                            <div class="hp-call">
                                @foreach ([['You', 120, 'ME'], ['Juan', 205, 'JD'], ['Maria', 330, 'MS'], ['Pedro', 30, 'PR']] as $k => [$cn, $ch, $ci])
                                    <div class="hp-call-tile{{ $k === 2 ? ' is-talking' : '' }}" style="--h: {{ $ch }}">
                                        <span class="hp-call-av">{{ $ci }}</span><small>{{ $cn }}</small>
                                    </div>
                                @endforeach
                            </div>
                            <div class="hp-call-bar"><span><i class="hp-live"></i>Team call</span><b data-call-clock>00:42</b><span class="hp-call-end">End</span></div>
                        </div>
                        {{-- Camera sharing --}}
                        <div class="hp-room-pane" id="hpRoom-cameras" data-room-pane="cameras">
                            <div class="hp-cams">
                                <figure class="hp-cam"><img src="{{ asset('images/site/home-team/cam-1.webp') }}" alt="" width="480" height="360" loading="lazy"><figcaption><i></i>LIVE · Pedro at Lot 2</figcaption></figure>
                                <figure class="hp-cam"><img src="{{ asset('images/site/home-team/cam-2.webp') }}" alt="" width="480" height="360" loading="lazy"><figcaption><i></i>LIVE · Maria at Lot 1</figcaption></figure>
                            </div>
                        </div>
                        {{-- Location sharing --}}
                        <div class="hp-room-pane" id="hpRoom-location" data-room-pane="location">
                            {{-- A satellite view of the farm, the way the app's map shows it. --}}
                            <div class="hp-loc">
                                <img class="hp-loc-map" src="{{ asset('images/site/home-team/sat.webp') }}" alt="" width="880" height="440" loading="lazy">
                                <span class="hp-loc-chip"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 20l-5.4-2.7A1 1 0 013 16.4V5.6a1 1 0 011.4-.9L9 7m0 13l6-3m-6 3V7m6 10l4.6 2.3a1 1 0 001.4-.9V7.6a1 1 0 00-.6-.9L15 4m0 13V4m0 0L9 7"/></svg>Satellite</span>
                                <span class="hp-loc-zoom"><i><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" aria-hidden="true"><path stroke-linecap="round" d="M12 6v12M6 12h12"/></svg></i><i><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" aria-hidden="true"><path stroke-linecap="round" d="M6 12h12"/></svg></i></span>
                                <span class="hp-loc-tag" style="left: 13%; top: 62%">Lot 1</span>
                                <span class="hp-loc-tag" style="left: 85%; top: 74%">Lot 2</span>
                                <span class="hp-loc-scale"><i></i>50 m</span>
                                <span class="hp-loc-pin is-a" style="--h: 205">JD</span>
                                <span class="hp-loc-pin is-b" style="--h: 330">MS</span>
                                <span class="hp-loc-pin is-c" style="--h: 30">PR</span>
                            </div>
                            <p class="hp-loc-say"><i class="hp-live"></i>Pedro is near the gate of Lot 2</p>
                        </div>
                        {{-- Team tasks --}}
                        <div class="hp-room-pane" id="hpRoom-tasks" data-room-pane="tasks">
                            {{-- The Activities board: the day, its stage and weather, then each task card. --}}
                            <div class="hp-act-day">
                                <span class="hp-act-dow">SAT</span><b>Oct 3</b>
                                <span class="hp-act-n">3</span>
                                <span class="hp-act-chip is-stage"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21v-9m0 0C12 7 8 5 4 5c0 4 3 7 8 7zm0 0c0-4 3-7 8-7 0 4-4 7-8 7z"/></svg>Active tillering</span>
                                <span class="hp-act-chip"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 15a4 4 0 004 4h9a5 5 0 10-.9-9.95A5.5 5.5 0 006.5 8 4.5 4.5 0 003 15z"/></svg>31°</span>
                            </div>
                            @foreach ([['Top dress urea', 'Lot 2', 'DAT+25', 'High', 'Fertilizer', [['JD', 205]], 'is-done'], ['Hand weeding', 'Lot 1', 'DAT+22', 'Medium', 'Weeding', [['MS', 330], ['PR', 30]], 'is-done2'], ['Scout for rice bug', 'Lot 1', 'DAT+22', 'Low', 'Monitoring', [['JD', 205]], '']] as $k => [$tt, $tl, $td, $tp, $ty, $tw, $tst])
                                <div class="hp-act {{ $tst }}" style="--k: {{ $k }}">
                                    <div class="hp-act-top">
                                        <span class="hp-act-box">{!! $tick !!}</span>
                                        <b class="hp-act-t">{{ $tt }}</b>
                                        <span class="hp-act-who">@foreach ($tw as [$wi, $wh])<span class="hp-feed-av" style="--h: {{ $wh }}">{{ $wi }}</span>@endforeach</span>
                                    </div>
                                    <div class="hp-act-meta">
                                        <span class="hp-act-lot">{!! $pin !!}{{ $tl }}<i></i>{{ $td }}</span>
                                        <span class="hp-act-pr is-{{ strtolower($tp) }}">{{ strtoupper($tp) }}</span>
                                        <span class="hp-act-ty">{{ $ty }}</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        {{-- Whiteboard --}}
                        <div class="hp-room-pane" id="hpRoom-board" data-room-pane="board">
                            {{-- A sketch of the farm, drawn stroke by stroke as a teammate
                                 would: crooked paddies with overshooting corners, rice tufts,
                                 the canal and an arrow, a scribbled seedbed, a tree, the shed. --}}
                            <svg class="hp-wb" viewBox="0 0 300 150">
                                <path class="hp-wb-ink is-lot" style="--o: 0" pathLength="1" d="M15 31 Q 66 23 117 16 M113 13 Q 120 44 127 75 M129 72 Q 79 80 25 87 M28 90 Q 21 59 18 27"/>
                                <path class="hp-wb-ink is-lot" style="--o: 1.3" pathLength="1" d="M137 24 Q 182 14 229 15 M226 12 Q 233 36 239 59 M241 56 Q 229 76 214 95 M217 93 Q 182 90 147 91 M150 93 Q 144 57 140 20"/>
                                <path class="hp-wb-ink is-tuft" style="--o: 2.7" pathLength="1" d="M34 74 l 3 -7 l 3 7 M92 66 l 3 -7 l 3 7 M98 36 l 3 -7 l 3 7 M156 80 l 3 -7 l 3 7 M204 36 l 3 -7 l 3 7 M211 76 l 3 -7 l 3 7"/>
                                <path class="hp-wb-ink is-canal" style="--o: 3.1" pathLength="1" d="M266 6 C 257 38, 273 68, 262 98 S 255 128, 269 148"/>
                                <path class="hp-wb-ink is-canal is-thin" style="--o: 3.6" pathLength="1" d="M273 30 q 4 -4 8 0 M270 120 q 4 -4 8 0"/>
                                <path class="hp-wb-ink is-arrow" style="--o: 4" pathLength="1" d="M261 84 C 251 83, 243 76, 237 66 M237 66 L 236 77 M237 66 L 246 71"/>
                                <path class="hp-wb-ink is-seed" style="--o: 4.8" pathLength="1" d="M34 113 c 7 -10 30 -10 37 1 c 4 10 -10 17 -22 16 c -14 -1 -21 -8 -15 -17 c 4 -5 12 -7 20 -6"/>
                                <path class="hp-wb-ink is-seed is-thin" style="--o: 5.3" pathLength="1" d="M42 120 l 9 -8 M47 126 l 14 -12 M58 126 l 8 -7"/>
                                <path class="hp-wb-ink is-tree" style="--o: 6" pathLength="1" d="M148 142 L 148 127 M148 127 c -10 1 -13 -10 -5 -13 c -1 -9 11 -12 14 -4 c 9 -2 12 9 4 12 c 1 6 -7 9 -13 5"/>
                                <path class="hp-wb-ink is-shed" style="--o: 6.5" pathLength="1" d="M182 142 L 182 122 L 194 111 L 206 122 L 206 142 Z M190 142 L 190 131 L 198 131 L 198 142"/>
                                <g class="hp-wb-hand">
                                    <text x="48" y="54" style="--o: 1.1">Lot 1</text>
                                    <text x="168" y="58" style="--o: 2.4">Lot 2</text>
                                    <text x="80" y="128" style="--o: 5.6">Seedbed</text>
                                    <text x="222" y="126" style="--o: 4.4">canal</text>
                                </g>
                            </svg>
                            <span class="hp-wb-note">Water goes in from the east canal</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Field health maps from space. --}}
            <div class="hp-sky on-dark">
                <div class="hp-sky-copy reveal">
                    <p class="hp-kick">Field health from space</p>
                    <h3 class="hp-sky-h">Watch Your Fields From Space, <em>Even Through the Clouds.</em></h3>
                    <p class="hp-sky-p">
                        anee.io is powered by advanced dual satellite technology. We combine high resolution satellite photos with
                        cloud penetrating radar to give you an updated field health map every 5 to 10 days. Track your crops from
                        above and spot hidden problems early, even during heavy monsoon rains, without buying an expensive drone.
                    </p>
                    <div class="hp-sky-stats">
                        <div><b>Every 5 to 10 days</b><small>A fresh field health map</small></div>
                        <div><b>Through the clouds</b><small>Radar sees your crop in the monsoon</small></div>
                        <div><b>No drone needed</b><small>Nothing to buy, nothing to fly</small></div>
                    </div>
                </div>

                <div class="hp-sat reveal" aria-hidden="true">
                    <span class="hp-sat-stars"></span>
                    <span class="hp-sat-ground"><span class="hp-sat-plane">
                        {{-- A real aerial map of rice paddies, and the field health map the scan
                             reveals over it: a vegetation index read from the same picture, with
                             canals, roads and trees left grey and two paddies showing stress. --}}
                        <img class="hp-sat-map" src="{{ asset('images/site/home-team/sat-field.webp') }}" alt="" width="1400" height="640" loading="lazy">
                        <img class="hp-sat-map is-health" src="{{ asset('images/site/home-team/sat-health.webp') }}" alt="" width="1400" height="640" loading="lazy">
                        <i class="hp-sat-scan"></i>
                    </span></span>
                    <span class="hp-sat-cloud is-a"></span>
                    <span class="hp-sat-cloud is-b"></span>
                    <span class="hp-sat-beam"></span>
                    <span class="hp-sat-wave"></span><span class="hp-sat-wave" style="--d: 1s"></span><span class="hp-sat-wave" style="--d: 2s"></span>
                    <span class="hp-sat-craft">
                        <svg viewBox="0 0 160 80">
                            <g class="hp-sat-panel"><rect x="4" y="26" width="50" height="28" rx="3"/><path d="M16.5 26v28M29 26v28M41.5 26v28M4 40h50"/></g>
                            <path class="hp-sat-arm" d="M54 40h14M92 40h14"/>
                            <rect class="hp-sat-body" x="68" y="24" width="24" height="32" rx="4"/>
                            <path class="hp-sat-foil" d="M70 32h20M70 40h20M70 48h20"/>
                            <path class="hp-sat-arm" d="M80 56v8"/>
                            <path class="hp-sat-dish" d="M70 64a10 6 0 0020 0z"/>
                            <circle class="hp-sat-blink" cx="80" cy="18" r="3"/><path class="hp-sat-arm" d="M80 24v-3"/>
                            <g class="hp-sat-panel"><rect x="106" y="26" width="50" height="28" rx="3"/><path d="M118.5 26v28M131 26v28M143.5 26v28M106 40h50"/></g>
                        </svg>
                    </span>
                    <span class="hp-sat-tag is-photo"><i></i>High resolution photos</span>
                    <span class="hp-sat-tag is-radar"><i></i>Radar through clouds</span>
                    <span class="hp-sat-legend">
                        <span><i style="--c: #2f9e44"></i>Healthy</span><span><i style="--c: #f2c94c"></i>Watch</span><span><i style="--c: #e8590c"></i>Problem</span>
                    </span>
                </div>
            </div>

            {{-- Typhoon watch (2026-10-06): a live satellite map of the
                 Philippines in the manner of Zoom Earth, the clouds drifting,
                 a typhoon turning along its curving forecast path (west over the sea,
                 bending north over Luzon, recurving northeast) inside its cone,
                 your farm pinned with how close the storm passes, and a day
                 by day timeline; beside it, Anee's plan for every lot. The
                 storm rides the SVG (SMIL), so it scales with the map; the
                 timeline reads the SVG's clock. NOT BUILT IN THE APP YET: the
                 owner's call is to show it as a feature (see the memory note
                 on satellite maps); match this when it is built. --}}
            <div class="hp-storm on-dark">
                <div class="hp-storm-copy reveal">
                    <p class="hp-kick">Typhoon watch</p>
                    <h3 class="hp-sky-h">See the Typhoon Coming, <em>and Know What to Do Before It Lands.</em></h3>
                    <p class="hp-sky-p">
                        Follow every storm on a live satellite map: the clouds, the rain and the forecast path, with your own farm
                        pinned on it. Then Anee, your smart farm technician, reads the path against each lot, its growth stage and
                        the week's work, and tells you what to decide while there is still time.
                    </p>
                    <div class="hp-sky-stats">
                        <div><b>Live clouds and rain</b><small>Satellite views of the whole country</small></div>
                        <div><b>The path, near you</b><small>How close the storm passes your farm, and when</small></div>
                        <div><b>Anee's plan</b><small>Harvest, drain, hold the fertilizer, protect the seedbed</small></div>
                    </div>
                </div>

                <div class="hp-storm-map reveal" aria-hidden="true">
                    <img class="hp-storm-base" src="{{ asset('images/site/storm/ph-satellite.webp') }}"
                         srcset="{{ asset('images/site/storm/ph-satellite-760.webp') }} 760w, {{ asset('images/site/storm/ph-satellite.webp') }} 1280w"
                         sizes="(min-width: 1024px) 40rem, 92vw" alt="" width="1280" height="1000" loading="lazy">
                    <span class="hp-storm-clouds"></span>
                    <svg class="hp-storm-svg" viewBox="0 0 1280 1000" data-storm>
                        <defs>
                            <path id="hpStormPath" d="M1255 880 C 1224 875, 1131 865, 1070 850 C 1009 835, 946 815, 890 790 C 834 765, 783 733, 735 700 C 687 667, 641 627, 600 590 C 559 553, 522 514, 490 478 C 458 442, 428 405, 410 372 C 392 339, 384 306, 382 278 C 380 250, 388 226, 398 205 C 408 184, 437 161, 445 152"/>
                        </defs>
                        <path class="hp-storm-cone" d="M1255 880 L1256 874 L1231 869 L1195 861 L1155 852 L1113 841 L1075 830 L1040 817 L1005 803 L970 789 L937 773 L905 756 L876 738 L847 718 L819 698 L792 677 L766 655 L741 633 L717 610 L693 586 L670 561 L648 537 L628 513 L608 490 L589 466 L572 443 L556 421 L540 398 L526 376 L514 356 L504 339 L499 324 L495 312 L493 300 L492 289 L492 279 L493 270 L494 268 L496 267 L498 265 L501 262 L504 258 L502 265 L506 263 L514 257 L524 249 L445 152 Q 460 137 445 152 L445 152 L352 68 L342 80 L328 96 L311 119 L292 152 L284 174 L277 198 L272 226 L270 255 L271 286 L276 312 L283 338 L293 365 L306 393 L321 420 L340 446 L360 470 L381 493 L403 514 L424 535 L447 557 L472 579 L498 600 L524 622 L552 643 L580 664 L609 685 L640 705 L671 726 L704 745 L736 763 L769 780 L803 796 L838 811 L875 824 L912 836 L950 847 L988 856 L1027 863 L1065 870 L1106 875 L1150 879 L1193 882 L1229 884 L1254 886 Z"/>
                        <use class="hp-storm-track" href="#hpStormPath"/>
                        @foreach ([[1255, 880, 'Mon'], [1006, 832, 'Tue'], [776, 727, 'Wed'], [578, 570, 'Thu'], [414, 379, 'Fri'], [445, 152, 'Sat']] as [$dx, $dy, $dn])
                            <g class="hp-storm-day"><circle cx="{{ $dx }}" cy="{{ $dy }}" r="9"/><text x="{{ $dx }}" y="{{ $dy - 22 }}">{{ $dn }}</text></g>
                        @endforeach
                        <path class="hp-storm-gap" d="M330 405 L 407 366"/>
                        <text class="hp-storm-km" x="356" y="370" text-anchor="end">110 km</text>
                        <g class="hp-storm-eye">
                            <animateMotion dur="16s" repeatCount="indefinite" rotate="0"><mpath href="#hpStormPath"/></animateMotion>
                            <g>
                                <image href="{{ asset('images/site/storm/typhoon.webp') }}" x="-170" y="-170" width="340" height="340"/>
                                <animateTransform attributeName="transform" type="rotate" from="0" to="-360" dur="7s" repeatCount="indefinite"/>
                            </g>
                            <text class="hp-storm-name" x="0" y="-128">Typhoon · 185 km/h</text>
                        </g>
                    </svg>
                    <span class="hp-storm-farm"><i></i><b>Your farm</b></span>
                    <span class="hp-storm-top"><i class="hp-live"></i>Typhoon watch<small>Satellite, updated 2:00 PM</small></span>
                    <span class="hp-storm-layers"><b class="is-on">Satellite</b><b>Wind</b><b>Rain</b></span>
                    <div class="hp-storm-time">
                        <span class="hp-storm-play"><svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M8 5.14v13.72L19 12 8 5.14z"/></svg></span>
                        <div class="hp-storm-bar">
                            <span class="hp-storm-days">@foreach (['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'] as $dn)<i>{{ $dn }}</i>@endforeach</span>
                            <span class="hp-storm-rail"><i data-storm-fill></i><b data-storm-head></b></span>
                        </div>
                        <span class="hp-storm-now" data-storm-now>Mon 8 AM</span>
                    </div>
                </div>

                <div class="hp-storm-plan reveal">
                    <div class="hp-storm-plan-hd">
                        <img src="{{ $face }}" alt="">
                        <span><b>Anee's typhoon plan</b><small>Lot 1 and Lot 2, wet season palay</small></span>
                    </div>
                    <p class="hp-storm-say">The typhoon bends north and crosses the east coast early Friday, passing about 110 km northeast of your farm. Expect strong wind and heavy rain from Thursday night.</p>
                    <ol class="hp-storm-steps">
                        <li style="--k: 0"><b>Tuesday</b>Harvest Lot 1. It is 30 days after heading and ready, so the grain is safer in sacks than in the field.</li>
                        <li style="--k: 1"><b>Wednesday</b>Clear the canals and open the drains, so the water has somewhere to go.</li>
                        <li style="--k: 2"><b>Thursday</b>Move the seedbed trays to higher ground and tie down the shed roof.</li>
                        <li style="--k: 3"><b>Saturday</b>Hold the urea on Lot 2 until the rain stops. Heavy rain would wash it away.</li>
                    </ol>
                    <p class="hp-storm-done">{!! $tick !!}Added to your season calendar</p>
                </div>
            </div>

            <div class="hp-cta reveal">
                <div class="hp-cta-row">
                    <a href="{{ $signup }}" class="btn btn-accent btn-lg hp-go">Start free and bring your team in {!! $arrow !!}</a>
                </div>
                <p class="hp-cta-note">Workers and offline mode come with Solo Farmer. Worker logins and the Collab Room come with Farm Owner.</p>
            </div>
        </div>
    </section>

    {{-- ================= THE COMMUNITY ================= --}}
    {{-- The anee.io community (2026-10-06): a feed post that gathers
         reactions and a reply from a fellow grower, the discussion rooms with new
         posts landing, a member levelling up on the ladder of 100 levels and
         ten titles, and a cofarmer request being accepted. It plays as one
         loop while on screen. Every part is in the app: the plaza feed,
         discussion rooms (open, password or approval), cofarmers and
         follows, direct messages, the ranking ladder, and Anee and the
         anee.io technicians answering in the rooms. --}}
    <section class="hp-sec hp-comm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6">
            <div class="hp-head reveal">
                <p class="hp-kick">The anee.io community</p>
                <h2 class="hp-h2">Grow Together With Farmers <em>Across the Philippines.</em></h2>
                <p class="hp-p">
                    You are not farming alone. Ask in a discussion room, share a photo of what worked, follow the growers you
                    learn from and connect as cofarmers. <b>Every helpful post moves you up a ladder of 100 levels.</b>
                </p>
            </div>

            <div class="hp-comm-grid">
                <div class="hp-comm-stage reveal" data-comm aria-hidden="true">
                    {{-- A post in the feed --}}
                    <div class="hp-cpost">
                        <div class="hp-cpost-hd">
                            <span class="hp-feed-av" style="--h: 330">RM</span>
                            <span><b>Rosa Mendoza</b><small>Rice Growers PH · 2 hours ago</small></span>
                            <span class="hp-crank-chip">Lv 23 · Green Thumb</span>
                        </div>
                        <p class="hp-cpost-t">Malinis na ang palayan bago mag 20 DAT. Salamat sa tips dito sa grupo!</p>
                        <img class="hp-cpost-img" src="{{ asset('images/site/home-crops/palay.webp') }}" alt="" width="720" height="405" loading="lazy">
                        <div class="hp-cpost-react">
                            <span class="is-thumb"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M14 10h4.764a2 2 0 011.789 2.894l-3.5 7A2 2 0 0115.263 21h-4.017c-.163 0-.326-.02-.485-.06L7 20m7-10V5a2 2 0 00-2-2h-.095c-.5 0-.905.405-.905.905 0 .714-.211 1.412-.608 2.006L7 11v9m7-10h-2M7 20H5a2 2 0 01-2-2v-6a2 2 0 012-2h2.5"/></svg><b data-comm-count="24" data-comm-to="31">24</b></span>
                            <span class="is-heart"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg><b data-comm-count="9" data-comm-to="14">9</b></span>
                            <span class="is-comment"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg><b data-comm-count="3" data-comm-to="4">3</b></span>
                        </div>
                        <div class="hp-cpost-reply">
                            <span class="hp-feed-av" style="--h: 165">BS</span>
                            <p><b>Ben Santos</b> Ang ganda ng tubo! Bantayan ang rice bug pag namumulaklak na.</p>
                        </div>
                    </div>

                    <div class="hp-cside">
                        {{-- Discussion rooms --}}
                        <div class="hp-crooms">
                            <p class="hp-room-ph">Discussion rooms</p>
                            <div class="hp-croom"><span class="hp-croom-i" style="--h: 100">RG</span><span><b>Rice Growers PH</b><small>Open to all</small></span><i class="hp-croom-new">3 new</i></div>
                            <div class="hp-croom"><span class="hp-croom-i" style="--h: 30">PM</span><span><b>Presyo at Merkado</b><small>Open to all</small></span><i class="hp-croom-new">5 new</i></div>
                            <div class="hp-croom"><span class="hp-croom-i" style="--h: 150">ON</span><span><b>Organic at Natural Farming</b><small class="is-lock"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>By approval</small></span><i class="hp-croom-new">1 new</i></div>
                        </div>

                        {{-- The ladder --}}
                        <div class="hp-clevel">
                            <div class="hp-clevel-hd">
                                <span class="hp-clevel-badge"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M8 21h8m-4-4v4m-5-17h10v4a5 5 0 01-10 0V4zm10 1h3v2a3 3 0 01-3 3M7 5H4v2a3 3 0 003 3"/></svg></span>
                                <span><small>Your level</small><b><span data-lv-from>Lv 19</span><span data-lv-to>Lv 20</span></b></span>
                                <span class="hp-clevel-title"><i class="is-old">Rising Farmer</i><i class="is-new">Green Thumb</i></span>
                            </div>
                            <span class="hp-clevel-bar"><i></i></span>
                            <span class="hp-clevel-ladder">@for ($t = 1; $t <= 10; $t++)<i class="{{ $t <= 2 ? 'is-done' : ($t === 3 ? 'is-next' : '') }}"></i>@endfor</span>
                            <small class="hp-clevel-k">100 levels, ten titles, from New Member to Farm Immortal</small>
                        </div>
                    </div>

                    {{-- A cofarmer request, accepted --}}
                    <div class="hp-cconn">
                        <span class="hp-feed-av" style="--h: 205">JD</span>
                        <span class="hp-cconn-t"><i class="is-ask"><b>Juan Dela Cruz</b> wants to be your cofarmer</i><i class="is-yes"><b>You and Juan</b> are now cofarmers</i></span>
                        <span class="hp-cconn-btn"><i class="is-ask">Accept</i><i class="is-yes">{!! $tick !!}</i></span>
                    </div>
                </div>

                <div class="hp-comm-feats">
                    @foreach ([
                        ['News feed', 'Share photos, videos and wins from your field, and see what other farmers are doing.', '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/></svg>'],
                        ['Discussion rooms', 'Join rooms by crop or topic. A room can be open to all, behind a password or by approval.', '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a1.994 1.994 0 01-1.414-.586m0 0L11 14h4a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2v4l.586-.586z"/></svg>'],
                        ['Cofarmers and followers', 'Connect with farmers you trust and follow the growers you learn from.', '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>'],
                        ['Messages', 'Talk one on one, with photos and voice notes.', '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>'],
                        ['A ladder of 100 levels', 'Ten titles from New Member to Farm Immortal. Posts, answers and help all count.', '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M8 21h8m-4-4v4m-5-17h10v4a5 5 0 01-10 0V4zm10 1h3v2a3 3 0 01-3 3M7 5H4v2a3 3 0 003 3"/></svg>'],
                        ['Answers in the rooms', 'Anee and the anee.io technicians join the rooms, so a question never waits long.', '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>'],
                    ] as $i => [$ft, $fp, $fi])
                        <div class="hp-team-card reveal" style="--reveal-delay: {{ ($i % 2) * 0.07 }}s">
                            <span class="hp-team-ico">{!! $fi !!}</span>
                            <div><h3>{{ $ft }}</h3><p>{{ $fp }}</p></div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="hp-cta reveal">
                <div class="hp-cta-row">
                    <a href="{{ $signup }}" class="btn btn-accent btn-lg hp-go">Join the community free {!! $arrow !!}</a>
                </div>
                <p class="hp-cta-note">Every plan joins the community, Libre included. Starting your own discussion room comes with Farm Owner.</p>
            </div>
        </div>
    </section>

    {{-- ================= TRADITIONAL vs ANEE.IO ================= --}}
    <section class="hp-sec bg-gray-50">
        <div class="max-w-6xl mx-auto px-4 sm:px-6">
            <div class="hp-head reveal">
                <p class="hp-kick">Why farmers switch</p>
                <h2 class="hp-h2">Traditional Farming <em>vs anee.io</em></h2>
                <p class="hp-p">Your season does not have to live in your head or on loose paper. Here is everything that changes when the whole season is in one place.</p>
            </div>

            {{-- The long list (2026-10-06, the owner: "not just a limited list, so
                 the farmer can see all the advantages"): every way the season
                 changes, in eight groups a visitor can jump between. Every
                 line is something the app does today; keep it that way. --}}
            @php
                $peso = $ph ? 'peso' : 'dollar';
                $vsGroups = [
                    ['Planning the season', 'M8 7V3m8 4V3M4 11h16M5 5h14a1 1 0 011 1v13a1 1 0 01-1 1H5a1 1 0 01-1-1V6a1 1 0 011-1z', [
                        ['When to plant', 'By habit, or when the neighbors start.', 'Anee names the best planting window for your town from its weather record and the months ahead.'],
                        ['What to plant', 'Whatever you planted last season.', 'Anee ranks the crops that fit your land, soil, water and season.'],
                        ['Choosing a variety', 'Whatever the seed store has, or what a friend says.', 'Varieties compared for your area: yield, maturity and resistance, with the sources.'],
                        ['Season planning', 'Kept in your head or scattered across notebooks.', 'Every task of the season written on its day, from land prep to harvest.'],
                        ['Fertilizer and spray plan', 'Copied from a neighbor or a product label.', 'Anee writes a protocol for your variety: the fertilizer and when, the sprays and the water.'],
                        ['Checking the plan', 'Nobody checks it until something goes wrong.', 'Anee reviews your plan and points out a rate too high, a spray too close to harvest or a stage with nothing planned.'],
                    ]],
                    ['Your fields', 'M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7', [
                        ['Field size', 'Estimated by paces or from an old title.', 'Trace the field on a satellite map and get its area and the length of each side.'],
                        ['Several fields', 'One timing for all, even when sown weeks apart.', 'Each lot counts from its own day zero, with its own crop and variety.'],
                        ['Explaining the plan', 'Said by mouth, and remembered differently by each person.', 'Draw the plan once, over a photo of the field if you like, and everyone works from the same picture.'],
                    ]],
                    ['Timing and growth', 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z', [
                        ['Activity timing', 'Guessed from memory. Easy to spray or fertilize a few days late.', 'Every task dated from each lot\'s day zero. Right day, every time.'],
                        ['Growth stage', 'Guessed by looking at the plants.', 'Each lot shows its growth stage on any day, with what to do and what to watch for.'],
                        ['A crop that runs ahead or behind', 'The old calendar goes on as if nothing changed.', 'Anee reads the lot and moves its stage count to match the crop.'],
                        ['Weather', 'The radio or the sky, for the whole province.', 'The forecast where your field is, beside your plan, with a reminder when rain falls on a spray day.'],
                        ['Daily reminders', 'Remembered, or forgotten.', 'A tip for your crop\'s stage on your dashboard every morning.'],
                        ['Changing the plan', 'Cross it out, write it again and hope everyone heard.', 'Drag a task to a new day and the board changes for the whole team.'],
                    ]],
                    ['Pests and problems', 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z', [
                        ['Expert advice', $R::t('techVisit'), 'Anee answers crop questions any time' . ($ph ? ', in Tagalog or English,' : '') . ' even from a photo of a leaf.'],
                        ['A sick plant', 'Guess the problem and buy what the store suggests.', 'Send a photo. Anee tells you what it is and what to do, with your lot and its stage in mind.'],
                        ['Learning about pests', 'Ask around and hope the advice fits your crop.', 'Free guides to the pests, diseases and weeds of your crops, with what to do step by step.'],
                        ['Other farmers\' experience', 'Only the neighbors you happen to meet.', 'A community of growers who trade what worked, with photos and voice.'],
                        ['Halfway through the season', 'You find out how it went at harvest.', 'Anee checks the season so far and names the risks in the coming weeks.'],
                    ]],
                    ['Materials and money', 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z', [
                        ['Materials and budget', 'Rough estimates. Overspending creeps in unnoticed.', 'What the plan needs against what is in the shed, so you buy only what is missing.'],
                        ['Stock in the shed', 'Counted by looking, and often wrong.', 'Tick a task done and the stock counts itself. Undo it and it goes back.'],
                        ['Labor cost', 'Added up by hand at the end, often a nasty surprise.', 'Worker rates add up in ' . $R::symbol() . ' as the work gets done.'],
                        ['Expenses', 'Receipts lost and small costs forgotten.', 'Every ' . $peso . ' the season cost, in one report built from your records.'],
                        ['Profit', 'Known roughly at the end, if at all.', 'Harvest sales against every cost, so you see the real profit of the season.'],
                    ]],
                    ['Your team', 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z', [
                        ['The day\'s work', 'Told one by one, and something gets missed.', 'Each worker gets the day\'s plan by email at 6 AM: what to do and on which lot.'],
                        ['Who worked', 'Remembered on payday, and argued about.', 'Tick who came on each task, whole day or half day, and the payroll adds itself up.'],
                        ['Who sees what', 'Share everything or nothing.', 'Each person gets a login that can view or edit only what you allow, and every change is kept in a diary.'],
                        ['Team talk', 'Scattered across texts and calls.', 'A room for each season with chat, a whiteboard and live cameras, and Anee in the room.'],
                    ]],
                    ['Records', 'M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9zM15 13a3 3 0 11-6 0 3 3 0 016 0z', [
                        ['Records and photos', 'Little proof of what was done, and when.', 'Photos, clips and voice notes kept with each task and each day.'],
                        ['Notes in the field', 'A scrap of paper in a pocket.', 'Snap it, film it or just say it, and it lands with the season.'],
                        ['No signal at the far lot', 'Write it down later, if you remember.', 'Keep working offline. Changes wait on the phone and go up when the signal comes back.'],
                        ['Papers and receipts', 'In a drawer, or lost.', 'The season\'s papers and files kept together, easy to find next year.'],
                        ['Buyers and suppliers', 'Numbers on old phones and paper.', 'One contact list of buyers, traders, workers and suppliers, with notes.'],
                    ]],
                    ['Harvest and next season', 'M13 7h8m0 0v8m0-8l-8 8-4-4-6 6', [
                        ['Harvest records', 'The yield is remembered. The moisture and the price are not.', 'Yield, moisture, price and buyer kept for each lot.'],
                        ['Learning from the season', 'Hard to say what went right and what went wrong.', 'Anee reads your finished season and shows what to keep and what to change.'],
                        ['Comparing seasons', 'Memory against memory.', 'Two seasons side by side, line by line, and Anee explains what changed.'],
                        ['Next season', 'Starts from memory again.', 'Your best lot\'s real record becomes next season\'s recipe.'],
                    ]],
                ];
                $vsCount = collect($vsGroups)->sum(fn ($g) => count($g[2]));
            @endphp

            <nav class="hp-vs-jump reveal" aria-label="Jump to a part of the list">
                @foreach ($vsGroups as $gi => [$gt, $gico, $rows])
                    <a href="#vs-{{ $gi }}" class="hp-vs-chip">{{ $gt }}<b>{{ count($rows) }}</b></a>
                @endforeach
            </nav>

            <div class="hp-vs">
                <div class="hp-vs-head" aria-hidden="true">
                    <span class="is-n">{{ $vsCount }} things that change</span>
                    <span class="is-old">By memory and paper</span>
                    <span class="is-new"><img src="{{ asset('images/logo-mark.png') }}" alt="" onerror="this.remove()">With anee.io</span>
                </div>
                @foreach ($vsGroups as $gi => [$gt, $gico, $rows])
                    <div class="hp-vs-group reveal" id="vs-{{ $gi }}">
                        <h3 class="hp-vs-gt">
                            <span class="hp-vs-gi"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $gico }}"/></svg></span>
                            {{ $gt }}<small>{{ count($rows) }}</small>
                        </h3>
                        @foreach ($rows as $i => [$dim, $old, $new])
                            <div class="hp-vs-row" style="--i: {{ $i }}">
                                <b class="hp-vs-dim">{{ $dim }}</b>
                                <p class="hp-vs-old"><span aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6"><path stroke-linecap="round" d="M6 6l12 12M18 6L6 18"/></svg></span><em class="hp-vs-label">Traditional:</em> {{ $old }}</p>
                                <p class="hp-vs-new"><span aria-hidden="true">{!! $tick !!}</span><em class="hp-vs-label">With anee.io:</em> {{ $new }}</p>
                            </div>
                        @endforeach
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

    {{-- ================= EVERY CROP YOU GROW (the Philippine face) ================= --}}
    @if ($ph)
    @php
        $crops = [
            ['Palay', 'M12 21v-9m0 0C12 7 8 5 4 5c0 4 3 7 8 7zm0 0c0-4 3-7 8-7 0 4-4 7-8 7z', 100, ['palay.webp', 'Ripe palay heads in a rice field'],
                'Plan pagtatanim ng palay from the rice seeds and the punla to the ani. Fertilizer urea and complete fertilizer 14 14 14 go on their day after transplanting, and a reminder to check for the rice bug (alitangya) comes before the milk stage.',
                [['/crops/palay', 'Palay guide'], ['/crops/rice-varieties-philippines', 'Rice varieties'], ['/pests/rice-bug', 'Rice bug']]],
            ['Mais', 'M12 3c-2.5 2-4 5-4 9s1.5 7 4 9c2.5-2 4-5 4-9s-1.5-7-4-9zm0 4v10M9.5 9.5L12 11l2.5-1.5M9.5 13.5L12 15l2.5-1.5', 45, ['mais.webp', 'Young corn plants in rows'],
                'Yellow or white corn, counted by days after planting: corn seeds and spacing, fertilizer days, fall armyworm checks, and the corn kernel at harvest.',
                [['/crops/corn-seeds', 'Corn seeds'], ['/crops/corn-kernel', 'Corn kernel'], ['/pests/fall-armyworm', 'Fall armyworm']]],
            ['Gulay', 'M12 21c-4.4 0-8-3.1-8-7 0-3.3 2.6-6 6-6.8V4h4v3.2c3.4.8 6 3.5 6 6.8 0 3.9-3.6 7-8 7z', 150, ['gulay.webp', 'Vegetable farms on a mountain slope'],
                'Pechay, tomato, eggplant and ampalaya, the vegetables in the Philippines that farms grow most, on one calendar with foliar fertilizer days and thrips and anthracnose checks.',
                [['/crops/vegetables-philippines', 'Vegetables guide'], ['/crops/pagtatanim-ng-gulay', 'Pagtatanim ng gulay'], ['/pests/thrips', 'Thrips']]],
            ['Niyog, saging at puno', 'M12 21v-8m0 0c-3 0-6-2-7-5 3 0 5 1 7 3m0 2c3 0 6-2 7-5-3 0-5 1-7 3m0-3V3', 30, ['puno.webp', 'Banana trees along a farm road'],
                'Coconut, banana and fruit trees count their age in months, with fertilizer plans that follow the PCA and DA guides.',
                [['/crops/coconut-fertilizer', 'Coconut fertilizer'], ['/crops/banana-farming-philippines', 'Banana farming'], ['/crops/pagtatanim-ng-puno', 'Pagtatanim ng puno']]],
        ];
    @endphp
    {{-- White, so it does not run on from the gray Traditional vs anee.io table above. --}}
    <section class="hp-sec bg-white">
        <div class="max-w-6xl mx-auto px-4 sm:px-6">
            <div class="hp-head reveal">
                <p class="hp-kick">Palay, mais, gulay and more</p>
                <h2 class="hp-h2">One Cropping Calendar Works for <em>Every Crop You Grow.</em></h2>
                <p class="hp-p">anee.io knows 85 Philippine crops, from palay and mais to gulay and fruit trees. Set the day you sow, transplant or plant, and every task after it gets its day count.</p>
            </div>
            <div class="mt-12 grid gap-5 sm:grid-cols-2">
                @foreach ($crops as $i => [$name, $icon, $hue, [$pic, $alt], $text, $links])
                    <div class="hc-card reveal" style="--h: {{ $hue }}; --reveal-delay: {{ ($i % 2) * 0.06 }}s">
                        <div class="hc-pic">
                            <img src="{{ asset('images/site/home-crops/' . $pic) }}" alt="{{ $alt }}" loading="lazy" width="720" height="405">
                            <span class="hc-ico"><svg fill="none" stroke="currentColor" stroke-width="1.9" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}"/></svg></span>
                        </div>
                        <div class="hc-body">
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
    <section class="hp-sec hp-guides-bg">
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
                    ['/blog/fertilizer-for-plants', 'Fertilizer for plants'], ['/blog/fungicides', 'Fungicide guide'], ['/blog/fertilizer-and-pesticide-authority', 'Fertilizer and Pesticide Authority'], ['/pests/rice-bug', 'Rice bug'],
                    ['/pests/rice-black-bug', 'Rice black bug'], ['/pests/thrips', 'Thrips insect'], ['/pests/fall-armyworm', 'Fall armyworm'], ['/pests/cutworm', 'Cutworm'],
                    ['/weeds/common-weeds-philippines', 'Common weeds in the Philippines'], ['/weeds/purple-nutsedge', 'Purple nutsedge'], ['/weeds/makahiya', 'Makahiya'],
                    ['/crops/rice-varieties-philippines', 'Rice varieties in the Philippines'], ['/blog/palay-price-philippines', 'Palay price in the Philippines'],
                    ['/crops/corn-kernel', 'Corn kernel'], ['/crops/corn-seeds', 'Corn seeds'], ['/crops/vegetables-philippines', 'Vegetables in the Philippines'],
                ] as $i => [$href, $label])
                    <a href="{{ url($href) }}" class="hp-topic" style="--h: {{ ($i * 41) % 150 + 30 }}">{{ $label }}</a>
                @endforeach
            </div>
            {{-- Four shelves, two by two (2026-10-07): each section's newest guide
                 as a picture card, then four more with small pictures, and
                 the way to the whole shelf. --}}
            @php
                $SP = \App\Support\SitePages::class;
                $guideCount = [];
                try {
                    $guideCount = \App\Models\AsSitePage::live()->whereIn('section', array_keys($guides))
                        ->selectRaw('section, count(*) as n')->groupBy('section')->pluck('n', 'section')->all();
                } catch (\Throwable $e) {}
                // A small copy for a small picture: the page's own thumb, or the -480 twin beside its picture.
                $guideThumb = function ($p) use ($SP) {
                    $h = is_array($p->heroImage) ? $p->heroImage : [];
                    $src = (string) ($h['thumb'] ?? ($h['src'] ?? ''));
                    if ($src !== '' && ! isset($h['thumb']) && str_starts_with($src, '/images/')) {
                        $twin = preg_replace('/\.(jpe?g|png|webp)$/i', '-480.webp', $src);
                        if ($twin && is_file(public_path(ltrim($twin, '/')))) { $src = $twin; }
                    }
                    return $SP::img($src) ?: asset('images/site/fields-aerial.jpg');
                };
                $shelf = [
                    'crops' => ['Crop Guides', 'Planting, feeding and harvest, crop by crop', 100, 'M12 21v-9m0 0C12 7 8 5 4 5c0 4 3 7 8 7zm0 0c0-4 3-7 8-7 0 4-4 7-8 7z', 'All crop guides'],
                    'pests' => ['Crop Pests', 'Know the insect before you buy a spray', 22, 'M12 8a3 3 0 100-6 3 3 0 000 6zm0 0v13m-6-9h12M7 7L4 4m13 3l3-3M6 16l-3 3m15-3l3 3M8 12a4 4 0 008 0', 'All crop pests'],
                    'weeds' => ['Weeds and Grasses', 'Every weed of the rice field, and its control', 75, 'M6 21c0-6 1.2-11 4-15M12 21V3.5M18 21c0-6-1.2-11-4-15', 'All weeds and grasses'],
                    'blog' => ['From the Blog', 'Fertilizer, prices, farm words and more', 205, 'M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z', 'The whole blog'],
                ];
            @endphp
            <div class="hg-shelves">
                @foreach ($guides as $sec => $pages)
                    @php
                        [$shName, $shSub, $shHue, $shIcon, $shAll] = $shelf[$sec] ?? [$SP::SECTIONS[$sec]['label'], '', 100, 'M5 13l4 4L19 7', 'See all'];
                        $lead = $pages->first();
                        $leadHero = is_array($lead->heroImage) ? $lead->heroImage : [];
                        $leadSrc = $SP::img($leadHero['src'] ?? null) ?: asset('images/site/fields-aerial.jpg');
                        $n = $guideCount[$sec] ?? null;
                    @endphp
                    <div class="hg-shelf reveal" style="--h: {{ $shHue }}; --reveal-delay: {{ $loop->index * 0.08 }}s">
                        <div class="hg-head">
                            <span class="hg-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="{{ $shIcon }}"/></svg></span>
                            <span class="hg-name"><b>{{ $shName }}</b><small>{{ $shSub }}</small></span>
                            @if ($n)<span class="hg-count">{{ $n }} {{ $sec === 'blog' ? 'posts' : 'guides' }}</span>@endif
                        </div>
                        <a href="{{ $SP::pageUrl($lead) }}" class="hg-lead">
                            <img src="{{ $leadSrc }}" alt="{{ $leadHero['alt'] ?? $lead->title }}" loading="lazy" width="1200" height="675">
                            <span class="hg-lead-in">
                                @if ($lead->category)<span class="hg-cat">{{ $lead->category }}</span>@endif
                                <b>{{ $lead->title }}</b>
                                <span class="hg-read">{{ $lead->lang === 'tl' ? 'Basahin' : 'Read the guide' }}
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14m-6-6l6 6-6 6"/></svg></span>
                            </span>
                        </a>
                        <ul class="hg-rows">
                            @foreach ($pages->slice(1)->take(4) as $p)
                                <li><a href="{{ $SP::pageUrl($p) }}" class="hg-row">
                                    <img src="{{ $guideThumb($p) }}" alt="" loading="lazy" width="96" height="72">
                                    <span>@if ($p->category)<small>{{ $p->category }}</small>@endif<b>{{ $SP::shortTitle($p) }}</b></span>
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                                </a></li>
                            @endforeach
                        </ul>
                        <a href="{{ $SP::url($sec) }}" class="hg-all">{{ $shAll }}
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14m-6-6l6 6-6 6"/></svg></a>
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
             'anee.io is a farm app for Filipino farmers. It keeps your cropping calendar, lots, workers, fertilizer and costs for the whole season in one place, and Anee, the smart farm technician, answers questions about your crop.'],
            ['Can I plan pagtatanim ng palay in anee.io?',
             'Yes. Set the day you sow or transplant and every task gets its day count: basal fertilizer, urea top dressing, weeding, water and harvest. Each lot keeps its own day zero, so a lot planted a week late keeps its own timing.'],
            ['Does it work for mais, gulay and fruit trees?',
             'Yes. anee.io knows 85 Philippine crops, from palay and mais to vegetables, coconut, banana and fruit trees. Trees and other perennials count their age in months.'],
            ['Can Anee answer in Tagalog?',
             'Yes. Anee answers in Tagalog or English. She reads your schedule, growth stages and weather first, and she can look at a photo of a pest or a sick leaf. She comes with the Libre + Anee plan and up, and anyone can ask her one free question a week on the Try and Ask Anee page.'],
            ['Is anee.io free?',
             'Yes. The Libre plan is free forever with one active cropping schedule. Libre + Anee adds Anee, the smart farm technician, for ' . $aneePrice . ' a month, and the Solo Farmer and Farm Owner plans add workers, inventory, all reports and offline mode.'],
            ['Do I need a computer?',
             'No. anee.io runs in the browser of any phone, so you plan and tick tasks right in the field. On the Solo Farmer and Farm Owner plans it keeps working where there is no signal and syncs when the signal returns.'],
            ['Can my workers use it too?',
             'Yes. Every morning at 6 AM the team gets the day\'s plan by email. On the Farm Owner plan each worker can have their own login, and you decide what they may see and change.'],
            ['Can anee.io help with the rice bug, thrips and fall armyworm?',
             'Yes. Take a photo and Anee tells you what the pest or disease is and what to do, including when a fungicide or insecticide is needed. Your season board also reminds you when to check for the rice bug and other pests at each growth stage.'],
            ['Where can I read about fertilizer and pests?',
             'Our free guides cover fertilizer urea, complete fertilizer 14 14 14 and 16 20 0, the rice bug, thrips, fall armyworm and more. Start from the crop guides, the crop pests, the weeds and grasses or the blog.'],
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
                <a href="{{ url('/pests') }}" class="hc-link" style="--h: 30">Crop pests ›</a>
                <a href="{{ url('/weeds') }}" class="hc-link" style="--h: 80">Weeds and grasses ›</a>
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
{{-- The Collab Room whiteboard's handwriting: only the letters it writes. --}}
<link href="https://fonts.googleapis.com/css2?family=Caveat:wght@700&display=swap&text=Lot12Sedbcan%20" rel="stylesheet">
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
        box-shadow: 0 2px 6px rgb(0 0 0 / .18), 0 14px 30px -10px rgb(0 0 0 / .55), 0 30px 60px -28px rgb(0 0 0 / .7); backdrop-filter: blur(8px);
        animation: hpFloatIn .9s var(--hp-ease) both, hpBob 6s ease-in-out infinite; }
    .hp-float b { display: block; font-size: .82rem; font-weight: 800; line-height: 1.25; }
    .hp-float small { display: block; font-size: .74rem; color: #4b5563; line-height: 1.35; }
    .hp-float-ico { flex: none; width: 2.3rem; height: 2.3rem; border-radius: .8rem; display: grid; place-items: center; }
    .hp-float-ico svg { width: 1.2rem; height: 1.2rem; }
    .hp-float-ico.is-green { color: #2f5219; background: #e4f0d6; }
    .hp-float-ico.is-sky { color: #1d4ed8; background: #dbeafe; }
    /* The cards ride the phone's edges, mostly outside it, so the film on
       the screen stays in view (2026-10-06: they used to sit across it).
       Placed from the stage's middle, which is the phone's middle: --ph is
       half the phone's width (the phone is min(16.5rem, 64vw)), --ov how far
       a card laps onto the phone's frame. */
    .hp-hero-stage { --ph: min(8.25rem, 32vw); --ov: 1.5rem; }
    .hp-float { width: max-content; max-width: 13rem; }
    .hp-float.f1 { right: calc(50% + var(--ph) - var(--ov)); top: 10%; animation-delay: .7s, 1.6s; }
    .hp-float.f2 { left: calc(50% + var(--ph) - var(--ov)); top: 46%; animation-delay: 1s, 0s; }
    .hp-float.f3 { right: calc(50% + var(--ph) - var(--ov)); bottom: 13%; animation-delay: 1.3s, .8s; }
    @keyframes hpFloatIn { from { opacity: 0; transform: translateY(18px) scale(.94); } to { opacity: 1; transform: none; } }
    @keyframes hpBob { 0%, 100% { translate: 0 0; } 50% { translate: 0 -8px; } }
    /* Where there is little room beside the phone (a phone's screen, and the
       narrow two column hero from 1024 to 1279), each card shrinks to its
       icon and its first line. */
    @media (max-width: 639.98px), (min-width: 1024px) and (max-width: 1279.98px) {
        .hp-float { gap: .45rem; max-width: none; white-space: nowrap; padding: .42rem .65rem .42rem .42rem; border-radius: .9rem; }
        .hp-float small { display: none; }
        .hp-float b { font-size: .76rem; }
        .hp-float-ico { width: 1.8rem; height: 1.8rem; border-radius: .6rem; }
        .hp-float-ico svg { width: 1rem; height: 1rem; }
        .hp-float .hp-face.is-lg { width: 1.8rem; height: 1.8rem; }
    }
    @media (min-width: 1024px) and (max-width: 1279.98px) { .hp-hero-stage { --ov: 2rem; } }
    /* On a phone the cards sit at the page's edge, and only their tips
       reach onto the phone. */
    @media (max-width: 639.98px) {
        .hp-float.f1, .hp-float.f3 { right: auto; left: -.75rem; }
        .hp-float.f2 { left: auto; right: -.75rem; }
    }
    @media (max-width: 420px) {
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

    /* ---- the check after "7 Steps." ----
       A green badge that pops in once the heading scrolls into view, draws
       its check, then breathes a soft ring now and then. The stroke is a
       fixed size icon (not stretched), so pathLength=1 dashes the same in
       every browser. */
    .hp-nw { white-space: nowrap; }
    .hp-tick { position: relative; display: inline-grid; place-items: center; width: .8em; height: .8em; margin-left: .26em;
        vertical-align: -.06em; border-radius: 999px; background: var(--hp-green);
        box-shadow: 0 8px 18px -8px rgb(47 82 25 / .75); transform: scale(0) rotate(-35deg); }
    .hp-tick svg { width: 74%; height: 74%; overflow: visible; }
    .hp-tick path { fill: none; stroke: #fff; stroke-width: 3.1; stroke-linecap: round; stroke-linejoin: round;
        stroke-dasharray: 1; stroke-dashoffset: 1; }
    .hp-tick::after { content: ''; position: absolute; inset: 0; border-radius: inherit; pointer-events: none;
        box-shadow: 0 0 0 0 rgb(95 160 50 / .55); }
    .is-visible .hp-tick { animation: hpTickPop .62s cubic-bezier(.34,1.56,.64,1) .45s forwards; }
    .is-visible .hp-tick path { animation: hpTickDraw .5s cubic-bezier(.65,0,.35,1) .9s forwards; }
    .is-visible .hp-tick::after { animation: hpTickRing 4s ease-out 1.3s infinite; }
    html:not(.js) .hp-tick { transform: none; }
    html:not(.js) .hp-tick path { stroke-dashoffset: 0; }
    @keyframes hpTickPop { to { transform: none; } }
    @keyframes hpTickDraw { to { stroke-dashoffset: 0; } }
    @keyframes hpTickRing { 0% { box-shadow: 0 0 0 0 rgb(95 160 50 / .55); } 30%, 100% { box-shadow: 0 0 0 .32em rgb(95 160 50 / 0); } }

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

    /* ---- why (precision) ---- */
    .hp-why { position: relative; isolation: isolate; overflow: hidden; }
    .hp-why-bg { position: absolute; inset: 0; z-index: -2; width: 100%; height: 100%; object-fit: cover; }
    .hp-why-shade { position: absolute; inset: 0; z-index: -1; background: linear-gradient(180deg, rgb(20 36 12 / .94), rgb(20 36 12 / .8) 45%, rgb(20 36 12 / .95)); }
    /* The three rights, beside one of them happening. */
    .hp-prec-top { display: grid; gap: 3rem; align-items: center; }
    @media (min-width: 1024px) { .hp-prec-top { grid-template-columns: minmax(0, 1.05fr) minmax(0, .95fr); gap: 4rem; } }
    .hp-prec-copy { max-width: 40rem; margin: 0 auto; text-align: center; }
    @media (min-width: 1024px) { .hp-prec-copy { margin: 0; text-align: left; } }
    .hp-prec-rights { margin-top: 1.9rem; display: grid; gap: .7rem; text-align: left; list-style: none; padding: 0; }
    .hp-prec-rights li { display: flex; align-items: center; gap: .95rem; padding: .85rem 1.05rem; border-radius: 1.15rem;
        background: rgb(255 255 255 / .06); box-shadow: inset 0 0 0 1px rgb(255 255 255 / .12); backdrop-filter: blur(8px);
        transition: background-color .28s var(--hp-ease), box-shadow .28s var(--hp-ease); }
    .hp-prec-rights li:hover { background: rgb(255 255 255 / .1); box-shadow: inset 0 0 0 1px rgb(245 197 24 / .45); }
    .hp-prec-n { flex: none; width: 2.3rem; height: 2.3rem; border-radius: 999px; display: grid; place-items: center;
        font-family: var(--font-heading); font-weight: 800; color: var(--hp-ink); background: var(--hp-sun);
        box-shadow: 0 0 0 5px rgb(245 197 24 / .16); }
    .hp-prec-rights b { display: block; font-family: var(--font-heading); font-size: 1.06rem; font-weight: 800; color: #fff; line-height: 1.25; }
    .hp-prec-rights b + span { display: block; margin-top: .15rem; font-size: .92rem; line-height: 1.5; color: #c9d5bd; }
    .hp-prec-close { margin-top: 1.2rem; font-weight: 800; color: var(--hp-sun); text-wrap: balance; }

    /* The picture: a target, the old calendar, and today's job on Lot 2. */
    .hp-prec-vis { position: relative; display: grid; place-items: center; padding: 6.6rem 0 1.5rem 2.2rem; }
    .hp-prec-reticle { position: absolute; left: 50%; top: 50%; width: min(30rem, 108%); translate: -50% -50%; color: rgb(245 197 24 / .3);
        pointer-events: none; }
    .hp-prec-reticle circle, .hp-prec-reticle path { stroke: currentColor; stroke-width: .8; }
    .hp-prec-spin { transform-origin: 100px 100px; animation: hpSpin 70s linear infinite; }
    .hp-prec-old { position: absolute; left: 0; top: .6rem; z-index: 0; width: 8.4rem; padding-bottom: .9rem; overflow: hidden;
        display: flex; flex-direction: column; align-items: center; border-radius: 1rem; background: #f6f0e1; color: #3f3423;
        box-shadow: 0 26px 50px -22px rgb(0 0 0 / .8); rotate: -9deg; }
    .hp-prec-old-top { align-self: stretch; padding: .45rem .5rem; text-align: center; font-size: .6rem; font-weight: 800;
        letter-spacing: .12em; text-transform: uppercase; white-space: nowrap; color: #fff; background: #b91c1c; }
    .hp-prec-old-day { margin-top: .5rem; font-family: var(--font-heading); font-size: 3rem; font-weight: 800; line-height: 1; }
    .hp-prec-old-t { margin-top: .2rem; font-size: .8rem; font-weight: 800; color: #6b5a40; }
    .hp-prec-old-x { position: absolute; left: 10%; top: 34%; width: 80%; height: 48%; overflow: visible;
        -webkit-clip-path: inset(-20% 102% -20% -2%); clip-path: inset(-20% 102% -20% -2%); }
    .hp-prec-old-x path { fill: none; stroke: #dc2626; stroke-width: 5px; stroke-linecap: round; vector-effect: non-scaling-stroke; }
    .hp-prec-card { position: relative; z-index: 1; width: min(21.5rem, 100%); padding: 1.15rem 1.15rem 1rem; border-radius: 1.4rem;
        color: var(--hp-ink); background: #fff; box-shadow: 0 44px 90px -34px rgb(0 0 0 / .9), 0 0 0 1px rgb(255 255 255 / .6); }
    .hp-prec-hd { display: flex; align-items: center; flex-wrap: wrap; gap: .45rem; font-size: .74rem; font-weight: 800; }
    .hp-prec-lot { display: inline-flex; align-items: center; gap: .35rem; padding: .22rem .6rem; border-radius: 999px; color: #fff; background: #5b3aa6; }
    .hp-prec-lot i { width: .4rem; height: .4rem; border-radius: 999px; background: #fff; }
    .hp-prec-when { color: #4b5563; }
    .hp-prec-on { margin-left: auto; display: inline-flex; align-items: center; gap: .3rem; padding: .22rem .6rem; border-radius: 999px;
        color: var(--hp-deep); background: #e4f0d6; }
    .hp-prec-on svg { width: .9rem; height: .9rem; }
    .hp-prec-task { margin-top: .7rem; font-family: var(--font-heading); font-size: 1.3rem; font-weight: 800; line-height: 1.2; }
    .hp-prec-checks { margin-top: .8rem; display: grid; gap: .55rem; list-style: none; padding: 0; }
    .hp-prec-checks li { display: flex; align-items: flex-start; gap: .65rem; padding: .6rem .7rem; border-radius: .9rem; background: #f4f7f0; }
    .hp-prec-checks small { display: block; font-size: .64rem; font-weight: 800; letter-spacing: .1em; text-transform: uppercase; color: var(--hp-green); }
    .hp-prec-checks b { display: block; margin-top: .1rem; font-size: .86rem; font-weight: 700; line-height: 1.4; color: var(--hp-ink); }
    .hp-prec-chk { flex: none; display: grid; place-items: center; width: 1.6rem; height: 1.6rem; margin-top: .1rem; border-radius: 999px;
        background: var(--hp-green); box-shadow: 0 6px 14px -6px rgb(47 82 25 / .8); }
    .hp-prec-chk svg { width: 70%; height: 70%; overflow: visible; }
    .hp-prec-chk path { fill: none; stroke: #fff; stroke-width: 3.2; stroke-linecap: round; stroke-linejoin: round; }
    .hp-prec-foot { margin-top: .9rem; display: flex; align-items: center; gap: .7rem; font-size: .82rem; }
    .hp-prec-bar { flex: 1; height: .4rem; border-radius: 999px; background: #e7ecdf; overflow: hidden; }
    .hp-prec-bar i { display: block; height: 100%; border-radius: inherit; background: linear-gradient(90deg, var(--hp-green), var(--hp-sun)); }

    /* How it plays once the picture scrolls into view: the old calendar
       tips in and is crossed out, today's job rises over it, and its three
       checks tick one after another while the bar fills. Before that (with
       scripts on) the pieces wait in their starting places; without scripts,
       or for a visitor who asked for less motion, everything is simply done. */
    html.js .hp-prec-vis:not(.is-visible) .hp-prec-old { opacity: 0; translate: -1rem 1rem; }
    html.js .hp-prec-vis:not(.is-visible) .hp-prec-card { opacity: 0; translate: 0 1.5rem; }
    .hp-prec-old, .hp-prec-card { transition: opacity .7s var(--hp-ease), translate .9s var(--hp-ease); }
    .hp-prec-card { transition-delay: .25s; }
    html.js .hp-prec-checks li { opacity: .45; transition: opacity .4s var(--hp-ease); transition-delay: calc(1.05s + var(--k) * .45s); }
    html.js .hp-prec-chk { scale: 0; }
    html.js .hp-prec-chk path { stroke-dasharray: 1; stroke-dashoffset: 1; }
    html.js .hp-prec-bar i { width: 0; transition: width 1.5s cubic-bezier(.65,0,.35,1) .95s; }
    html.js .hp-prec-on { opacity: 0; translate: 0 -.3rem; transition: opacity .4s var(--hp-ease) 2.4s, translate .4s var(--hp-ease) 2.4s; }
    .hp-prec-vis.is-visible .hp-prec-checks li { opacity: 1; }
    .hp-prec-vis.is-visible .hp-prec-chk { animation: hpPrecPop .5s cubic-bezier(.34,1.56,.64,1) calc(1s + var(--k) * .45s) forwards; }
    @keyframes hpPrecPop { to { scale: 1; } }
    .hp-prec-vis.is-visible .hp-prec-chk path { animation: hpTickDraw .4s cubic-bezier(.65,0,.35,1) calc(1.25s + var(--k) * .45s) forwards; }
    .hp-prec-vis.is-visible .hp-prec-bar i { width: 100%; }
    .hp-prec-vis.is-visible .hp-prec-on { opacity: 1; translate: none; }
    .hp-prec-vis.is-visible .hp-prec-old-x { animation: hpReveal .55s cubic-bezier(.65,0,.35,1) .75s forwards; }
    html:not(.js) .hp-prec-old-x { -webkit-clip-path: none; clip-path: none; }
    html:not(.js) .hp-prec-bar i { width: 100%; }
    @media (max-width: 479.98px) {
        .hp-prec-vis { padding: 6.9rem 0 1rem 1.2rem; }
        .hp-prec-old { width: 7.2rem; }
        .hp-prec-old-day { font-size: 2.5rem; }
    }

    /* When this happens, and what anee.io does. */
    .hp-prec-rows { margin-top: 4.5rem; }
    .hp-prec-cols { display: none; }
    .hp-prec-row { display: grid; gap: .85rem; padding: 1.1rem; border-radius: 1.4rem; background: rgb(255 255 255 / .05);
        box-shadow: inset 0 0 0 1px rgb(255 255 255 / .12); backdrop-filter: blur(10px);
        transition: background-color .28s var(--hp-ease), box-shadow .28s var(--hp-ease); }
    .hp-prec-row + .hp-prec-row { margin-top: .9rem; }
    .hp-prec-row:hover { background: rgb(255 255 255 / .09); box-shadow: inset 0 0 0 1px rgb(245 197 24 / .35); }
    .hp-prec-prob { display: flex; align-items: flex-start; gap: .85rem; }
    .hp-prec-ico { flex: none; width: 2.7rem; height: 2.7rem; border-radius: .95rem; display: grid; place-items: center;
        color: #fca5a5; background: rgb(248 113 113 / .14); box-shadow: inset 0 0 0 1px rgb(248 113 113 / .3); }
    .hp-prec-ico svg { width: 1.4rem; height: 1.4rem; }
    .hp-prec-prob h3 { font-family: var(--font-heading); font-size: 1.1rem; font-weight: 800; color: #fff; line-height: 1.25; }
    .hp-prec-prob p { margin-top: .25rem; font-size: .9rem; line-height: 1.55; color: #c9d5bd; }
    .hp-prec-flow { display: flex; align-items: center; gap: .55rem; color: var(--hp-sun); }
    .hp-prec-flow-t { flex: none; font-size: .68rem; font-weight: 800; letter-spacing: .1em; text-transform: uppercase; }
    .hp-prec-flow svg { flex: none; width: 1rem; height: 1rem; }
    .hp-prec-line { position: relative; flex: 1; height: 2px; border-radius: 2px;
        background: linear-gradient(90deg, rgb(248 113 113 / .5), rgb(245 197 24 / .85)); }
    .hp-prec-line b { position: absolute; left: 0; top: 50%; width: .45rem; height: .45rem; margin-top: -.225rem; border-radius: 999px;
        background: var(--hp-sun); box-shadow: 0 0 10px 1px rgb(245 197 24 / .8); opacity: 0;
        animation: hpPrecFlow 2.8s var(--hp-ease) infinite; animation-delay: calc(var(--i) * .4s); }
    @keyframes hpPrecFlow { 0% { left: 0; opacity: 0; } 15%, 80% { opacity: 1; } 100% { left: calc(100% - .45rem); opacity: 0; } }
    .hp-prec-ans { display: flex; align-items: flex-start; gap: .7rem; padding: .9rem 1rem; border-radius: 1rem;
        background: rgb(168 204 126 / .12); box-shadow: inset 0 0 0 1px rgb(168 204 126 / .28); }
    .hp-prec-ok { flex: none; width: 1.55rem; height: 1.55rem; margin-top: .05rem; border-radius: 999px; display: grid; place-items: center;
        color: var(--hp-ink); background: var(--hp-sun); }
    .hp-prec-ok svg { width: .85rem; height: .85rem; }
    .hp-prec-ans p { font-size: .93rem; line-height: 1.55; color: #fff; }
    @media (min-width: 900px) {
        .hp-prec-cols { display: grid; grid-template-columns: minmax(0, 1fr) 4.5rem minmax(0, 1.15fr); padding: 0 1.25rem .85rem;
            font-size: .72rem; font-weight: 800; letter-spacing: .1em; text-transform: uppercase; }
        .hp-prec-cols span:first-child { color: #fca5a5; }
        .hp-prec-cols span:last-child { grid-column: 3; color: var(--hp-sun); }
        .hp-prec-row { grid-template-columns: minmax(0, 1fr) 4.5rem minmax(0, 1.15fr); align-items: center; gap: 0; padding: 1.1rem 1.25rem; }
        .hp-prec-flow { padding: 0 .55rem; gap: .2rem; }
        .hp-prec-flow-t { display: none; }
    }


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
    /* The tools: each with its one line; as many columns as fit. */
    .hp-tools { margin-top: 1.3rem; display: grid; gap: .55rem; grid-template-columns: repeat(auto-fill, minmax(15.5rem, 1fr)); }
    .hp-tool { display: flex; align-items: center; gap: .7rem; padding: .6rem .75rem; border-radius: 1rem; text-align: left; cursor: pointer;
        background: #f7faf3; border: 1px solid #e4ecdb; opacity: 0; transform: translateY(8px);
        transition: opacity .4s var(--hp-ease), transform .4s var(--hp-ease), border-color .28s var(--hp-ease), background-color .28s var(--hp-ease), box-shadow .28s var(--hp-ease); }
    .hp-st-pane.is-on .hp-tool { opacity: 1; transform: none; transition-delay: calc(var(--j) * 50ms + .1s), calc(var(--j) * 50ms + .1s), 0s, 0s, 0s; }
    .hp-tool:hover { border-color: #b9d69a; background: #fff; }
    .hp-tool.is-on { border-color: var(--hp-green); background: #fff; box-shadow: 0 10px 22px -16px rgb(47 82 25 / .7), inset 3px 0 0 var(--hp-green); }
    .hp-tool-ico { flex: none; width: 2.3rem; height: 2.3rem; border-radius: .75rem; display: grid; place-items: center; background: #fff;
        box-shadow: inset 0 0 0 1px #e4ecdb; }
    .hp-tool-ico img { width: 1.5rem; height: 1.5rem; object-fit: contain; }
    .hp-tool-ico.is-face img { width: 100%; height: 100%; border-radius: inherit; object-fit: cover; }
    .hp-tool-tx { min-width: 0; }
    .hp-tool-t { display: block; font-size: .88rem; font-weight: 800; color: var(--hp-ink); line-height: 1.25; }
    .hp-tool-s { display: block; margin-top: .12rem; font-size: .78rem; line-height: 1.35; color: #6b7f5a; }
    .hp-anee-tag { display: inline-block; margin-left: .35rem; padding: .05rem .4rem; border-radius: 999px; font-style: normal; font-size: .62rem;
        font-weight: 800; letter-spacing: .04em; text-transform: uppercase; vertical-align: .1em; color: #5c4400; background: #fdeeb2; }

    /* The picked tool, explained. All of a step's explanations share one
       cell, so the box keeps the height of the longest and nothing below
       it jumps when another tool is picked. */
    .hp-infos { margin-top: 1rem; display: grid; }
    .hp-info { grid-area: 1 / 1; display: flex; flex-direction: column; padding: 1.15rem 1.25rem 1.1rem; border-radius: 1.25rem;
        background: linear-gradient(160deg, #f8fbf4, #eef6e4); box-shadow: inset 0 0 0 1px #d9e8c8;
        opacity: 0; visibility: hidden; translate: 0 8px; transition: opacity .35s var(--hp-ease), translate .35s var(--hp-ease), visibility .35s; }
    .hp-info.is-on { opacity: 1; visibility: visible; translate: none; }
    .hp-info-k { display: flex; align-items: center; gap: .6rem; font-family: var(--font-heading); font-size: 1.05rem; font-weight: 800;
        line-height: 1.25; color: var(--hp-ink); }
    .hp-info-ico { flex: none; width: 2.1rem; height: 2.1rem; border-radius: .7rem; display: grid; place-items: center; background: #fff;
        box-shadow: 0 6px 14px -8px rgb(47 82 25 / .5); }
    .hp-info-ico img { width: 1.35rem; height: 1.35rem; object-fit: contain; }
    .hp-info-ico.is-face img { width: 100%; height: 100%; border-radius: inherit; object-fit: cover; }
    .hp-info-w { margin-top: .65rem; font-size: .93rem; line-height: 1.62; color: #374151; }
    .hp-info-g { margin-top: .8rem; display: grid; gap: .42rem; list-style: none; padding: 0; }
    .hp-info-g li { display: flex; align-items: flex-start; gap: .5rem; font-size: .88rem; font-weight: 700; line-height: 1.4; color: var(--hp-deep); }
    /* Whole pixels and a thicker stroke: the tick used to be an 11.4px
       drawing inside a padded svg, which rendered soft (2026-10-06). */
    .hp-info-ok { flex: none; width: 20px; height: 20px; margin-top: 1px; border-radius: 999px; display: grid; place-items: center;
        color: #fff; background: var(--hp-green); }
    .hp-info-ok svg { width: 14px; height: 14px; stroke-width: 3.2; }
    /* backwards, not both: an animation that keeps holding its end keeps
       the row on its own layer, and a layer at a half pixel blurs. */
    .hp-info.is-on .hp-info-g li { animation: hpInfoIn .45s var(--hp-ease) backwards; }
    .hp-info.is-on .hp-info-g li:nth-child(2) { animation-delay: .07s; }
    .hp-info.is-on .hp-info-g li:nth-child(3) { animation-delay: .14s; }
    @keyframes hpInfoIn { from { opacity: 0; translate: -6px 0; } to { opacity: 1; translate: none; } }
    .hp-info-more { margin-top: auto; padding-top: .9rem; display: inline-flex; align-items: center; gap: .35rem; align-self: flex-start;
        font-size: .88rem; font-weight: 800; color: var(--hp-green); }
    .hp-info-more svg { width: 1rem; height: 1rem; transition: transform .28s var(--hp-ease); }
    .hp-info-more:hover svg { transform: translateX(3px); }
    .hp-st-show { display: flex; flex-direction: column; align-items: center; gap: 1.6rem; }
    .hp-st-show .hp-phone { width: min(15rem, 62vw); margin-bottom: 1.6rem; }
    @media (min-width: 1024px) { .hp-st-show .hp-phone { width: 17rem; } }
    /* Under the phone, not over its screen: the label used to cover the
       bottom of the film (2026-10-06). */
    .hp-st-show .hp-film-tag { bottom: -2.6rem; }

    /* ---- Anee ---- */
    .hp-anee { position: relative; overflow: hidden; color: #e8efe1;
        background: radial-gradient(90% 120% at 85% 10%, #2d4a1a 0%, transparent 60%), linear-gradient(160deg, #10160c 0%, #1c2416 55%, #24301a 100%); }
    .hp-anee-grid { display: grid; gap: 3rem; align-items: center; }
    @media (min-width: 1024px) { .hp-anee-grid { grid-template-columns: 1.05fr .95fr; gap: 4rem; } }
    /* What Anee reads: lit one after another, then the answer, on a loop. */
    .hp-anee-k { margin-top: 1.7rem; font-size: .72rem; font-weight: 800; letter-spacing: .1em; text-transform: uppercase; color: var(--hp-sun); }
    .hp-anee-reads { margin-top: .7rem; display: grid; gap: .5rem; grid-template-columns: repeat(auto-fill, minmax(15rem, 1fr)); list-style: none; padding: 0; }
    .hp-anee-reads li { display: flex; align-items: center; gap: .65rem; padding: .55rem .75rem .55rem .55rem; border-radius: 1rem;
        background: rgb(255 255 255 / .06); box-shadow: inset 0 0 0 1px rgb(255 255 255 / .13);
        transition: background-color .35s var(--hp-ease), box-shadow .35s var(--hp-ease); }
    .hp-anee-ri { flex: none; width: 2.15rem; height: 2.15rem; border-radius: .7rem; display: grid; place-items: center;
        color: var(--hp-sun); background: rgb(245 197 24 / .12); transition: color .35s var(--hp-ease), background-color .35s var(--hp-ease), scale .35s var(--hp-ease); }
    .hp-anee-ri svg { width: 1.15rem; height: 1.15rem; }
    .hp-anee-reads b { display: block; font-size: .86rem; font-weight: 800; line-height: 1.25; color: #fff; }
    .hp-anee-reads small { display: block; margin-top: .1rem; font-size: .76rem; line-height: 1.35; color: #c3d2b5; }
    .hp-anee-reads li.is-reading { background: rgb(245 197 24 / .13); box-shadow: inset 0 0 0 1px rgb(245 197 24 / .65), 0 0 26px -10px rgb(245 197 24 / .7); }
    .hp-anee-reads li.is-reading .hp-anee-ri { scale: 1.08; }
    .hp-anee-reads li.is-read .hp-anee-ri { color: var(--hp-ink); background: var(--hp-sun); }
    .hp-anee-out { margin-top: .9rem; display: inline-flex; align-items: center; gap: .45rem; padding: .55rem 1rem; border-radius: 999px;
        font-size: .88rem; font-weight: 800; color: var(--hp-ink); background: var(--hp-sun); opacity: .3; translate: 0 4px;
        transition: opacity .45s var(--hp-ease), translate .45s var(--hp-ease), box-shadow .45s var(--hp-ease); }
    .hp-anee-out svg { width: 1rem; height: 1rem; }
    .hp-anee-out.is-on { opacity: 1; translate: none; box-shadow: 0 0 0 6px rgb(245 197 24 / .18), 0 14px 30px -12px rgb(245 197 24 / .7); }

    /* A technician visit against Anee. */
    .hp-tvs { margin-top: 5.5rem; }
    .hp-tvs-head { max-width: 46rem; margin: 0 auto; text-align: center; }
    .hp-tvs-h { margin-top: .7rem; font-family: var(--font-heading); font-size: clamp(1.6rem, 3.6vw, 2.4rem); font-weight: 800; line-height: 1.12;
        letter-spacing: -.01em; color: #fff; text-wrap: balance; }
    .hp-tvs-h em { font-style: normal; color: var(--hp-sun); }
    .hp-tvs-p { margin-top: .9rem; color: #d3dec7; line-height: 1.7; text-wrap: pretty; }
    .hp-tvs-t { margin-top: 2.2rem; border-radius: 1.5rem; overflow: hidden; background: rgb(255 255 255 / .04);
        box-shadow: inset 0 0 0 1px rgb(255 255 255 / .12), 0 40px 80px -50px rgb(0 0 0 / .9); backdrop-filter: blur(10px); }
    .hp-tvs-cols { display: none; }
    .hp-tvs-row { display: grid; gap: .55rem; padding: 1rem 1.1rem; border-top: 1px solid rgb(255 255 255 / .12); }
    .hp-tvs-row:first-of-type { border-top: 0; }
    .hp-tvs-k { display: flex; align-items: center; gap: .6rem; font-family: var(--font-heading); font-weight: 800; color: #fff; }
    .hp-tvs-k span { flex: none; width: 2rem; height: 2rem; border-radius: .65rem; display: grid; place-items: center; color: var(--hp-sun); background: rgb(245 197 24 / .12); }
    .hp-tvs-k svg { width: 1.05rem; height: 1.05rem; }
    .hp-tvs-old, .hp-tvs-new { display: flex; align-items: flex-start; gap: .6rem; font-size: .9rem; line-height: 1.5; }
    .hp-tvs-old p, .hp-tvs-new p { min-width: 0; flex: 1; }
    .hp-tvs-old { color: #b9c4ad; }
    .hp-tvs-new { color: #fff; font-weight: 600; padding: .6rem .7rem; border-radius: .9rem; background: rgb(168 204 126 / .12); }
    .hp-tvs-old > i, .hp-tvs-new > i { flex: none; width: 20px; height: 20px; margin-top: 1px; border-radius: 999px; display: grid; place-items: center; }
    .hp-tvs-old > i { color: #fca5a5; background: rgb(248 113 113 / .18); }
    .hp-tvs-new > i { color: var(--hp-ink); background: var(--hp-sun); }
    .hp-tvs-old > i svg, .hp-tvs-new > i svg { width: 12px; height: 12px; }
    .hp-tvs-new > i svg { stroke-width: 3.4; }
    .hp-tvs-t.is-visible .hp-tvs-old > i, .hp-tvs-t.is-visible .hp-tvs-new > i { animation: hpPop .5s var(--hp-ease) backwards; }
    .hp-tvs-t.is-visible .hp-tvs-old > i { animation-delay: calc(var(--i) * 110ms + .2s); }
    .hp-tvs-t.is-visible .hp-tvs-new > i { animation-delay: calc(var(--i) * 110ms + .35s); }
    /* The race: the visit's bar crawls toward "days", Anee's fills at once. */
    .hp-tvs-race { display: block; margin-top: .6rem; }
    .hp-tvs-race i { position: relative; display: block; height: .45rem; border-radius: 999px; overflow: hidden; background: rgb(255 255 255 / .1); }
    .hp-tvs-race i::before { content: ''; position: absolute; inset: 0; border-radius: inherit; transform-origin: left; transform: scaleX(0); }
    .hp-tvs-race.is-slow i::before { background: linear-gradient(90deg, #f87171, #fca5a5); }
    .hp-tvs-race.is-fast i::before { background: linear-gradient(90deg, var(--hp-sun), #fde68a); }
    .hp-tvs-t.is-visible .hp-tvs-race.is-slow i::before { animation: hpRaceSlow 7s linear infinite; }
    .hp-tvs-t.is-visible .hp-tvs-race.is-fast i::before { animation: hpRaceFast 7s var(--hp-ease) infinite; }
    @keyframes hpRaceSlow { 0% { transform: scaleX(0); opacity: 1; } 88% { transform: scaleX(.9); opacity: 1; } 100% { transform: scaleX(.9); opacity: 0; } }
    @keyframes hpRaceFast { 0% { transform: scaleX(0); opacity: 1; } 7% { transform: scaleX(1); } 88% { transform: scaleX(1); opacity: 1; } 100% { transform: scaleX(1); opacity: 0; } }
    .hp-tvs-race b { display: block; margin-top: .3rem; font-size: .7rem; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; }
    .hp-tvs-race.is-slow b { color: #fca5a5; }
    .hp-tvs-race.is-fast b { color: var(--hp-sun); }
    @media (min-width: 820px) {
        .hp-tvs-cols { display: grid; grid-template-columns: 13rem minmax(0, 1fr) minmax(0, 1fr); }
        .hp-tvs-cols span { display: flex; align-items: center; gap: .55rem; padding: .95rem 1.3rem; font-family: var(--font-heading); font-size: .98rem; font-weight: 800; }
        .hp-tvs-cols svg { width: 1.2rem; height: 1.2rem; }
        .hp-tvs-cols .is-old { color: #fca5a5; background: rgb(248 113 113 / .07); }
        .hp-tvs-cols .is-new { color: var(--hp-ink); background: var(--hp-sun); }
        .hp-tvs-cols img { width: 1.7rem; height: 1.7rem; border-radius: 999px; object-fit: cover; box-shadow: 0 0 0 2px rgb(20 33 12 / .25); }
        .hp-tvs-row { grid-template-columns: 13rem minmax(0, 1fr) minmax(0, 1fr); gap: 0; padding: 0; align-items: stretch; }
        .hp-tvs-row > * { padding: 1rem 1.3rem; }
        .hp-tvs-old { border-left: 1px solid rgb(255 255 255 / .1); }
        /* The Anee column draws its own lines: the row's faint one
           disappeared against its green. */
        /* Drawn on the row's own line (1px above the cell), so it lines
           up with the line across the technician column. */
        .hp-tvs-row + .hp-tvs-row .hp-tvs-new { box-shadow: 0 -1px 0 rgb(245 197 24 / .32); }
        .hp-tvs-row + .hp-tvs-row { border-top-color: rgb(255 255 255 / .12); }
        .hp-tvs-new { border-radius: 0; background: rgb(168 204 126 / .1); }
        .hp-tvs-row:hover .hp-tvs-new { background: rgb(168 204 126 / .16); }
    }
    /* The window's own colour is the header's green at the top and the
       body's at the bottom: a white window showed through the rounded top
       corners as a thin white line (2026-10-06). */
    .hp-chat { position: relative; max-width: 28rem; margin: 0 auto; border-radius: 1.6rem; color: var(--hp-ink); overflow: hidden;
        background: linear-gradient(180deg, #34591c 50%, #f6f8f3 50%); box-shadow: 0 50px 90px -40px rgb(0 0 0 / .85); }
    .hp-chat-top { display: flex; align-items: center; gap: .7rem; padding: .9rem 1.1rem; background: linear-gradient(135deg, #2f5219, #4a7c2a); color: #fff; }
    .hp-chat-top b { display: block; font-size: 1rem; }
    .hp-chat-top small { display: flex; align-items: center; font-size: .75rem; color: #dceccb; }
    .hp-chat-body { display: flex; flex-direction: column; gap: .8rem; padding: 1.1rem; background: #f6f8f3; min-height: 26rem; }
    .hp-msg { max-width: 88%; padding: .75rem .9rem; border-radius: 1.1rem; font-size: .88rem; line-height: 1.55;
        opacity: 0; transform: translateY(10px) scale(.98); transition: opacity .5s var(--hp-ease), transform .5s var(--hp-ease); }
    .hp-msg.is-me { align-self: flex-end; color: #fff; background: #4a7c2a; border-bottom-right-radius: .35rem; }
    .hp-msg-photo { width: 100%; max-height: 8.5rem; object-fit: cover; border-radius: .7rem; margin-bottom: .55rem; }
    .hp-msg.is-anee { align-self: flex-start; background: #fff; box-shadow: 0 8px 20px -14px rgb(0 0 0 / .4); border-bottom-left-radius: .35rem; }
    .hp-msg.is-anee > b { display: block; color: var(--hp-deep); }
    .hp-msg.is-anee li b { color: var(--hp-deep); }
    .hp-msg.is-anee p { margin-top: .3rem; color: #4b5563; }
    .hp-msg.is-anee ol { margin-top: .5rem; padding-left: 1.1rem; list-style: decimal; display: grid; gap: .3rem; color: #374151; }
    .hp-reading { display: flex; flex-direction: column; gap: .35rem; }
    .hp-chat-body > * { transition: opacity .4s var(--hp-ease); }
    .hp-chat.is-fading .hp-chat-body > * { opacity: 0 !important; }
    .hp-read { display: inline-flex; align-items: center; gap: .5rem; align-self: flex-start; padding: .35rem .7rem; border-radius: 999px;
        font-size: .76rem; font-weight: 700; color: #5b6b50; background: #fff; box-shadow: inset 0 0 0 1px #e4ecdb;
        opacity: 0; transform: translateX(-8px); transition: opacity .4s var(--hp-ease), transform .4s var(--hp-ease); }
    .hp-read i { width: .8rem; height: .8rem; border-radius: 999px; border: 2px solid #cfe3b8; border-top-color: var(--hp-green); animation: hpSpinner .7s linear infinite; }
    .hp-read.is-done i { animation: none; border-color: var(--hp-green); background: var(--hp-green); }
    @keyframes hpSpinner { to { transform: rotate(360deg); } }
    .hp-chat.s1 .hp-msg.is-me, .hp-chat.s5 .hp-msg.is-anee { opacity: 1; transform: none; }
    .hp-read.is-shown { opacity: 1; transform: none; }

    /* ---- the farm as a business ---- */
    .hp-biz { display: grid; gap: 3rem; align-items: center; }
    @media (min-width: 1024px) { .hp-biz { grid-template-columns: .95fr 1.05fr; gap: 4rem; } }
    .hp-biz-pics { position: relative; }
    .hp-biz-a { display: block; width: 100%; height: auto; border-radius: 1.6rem; object-fit: cover; aspect-ratio: 3 / 2;
        box-shadow: 0 30px 60px -36px rgb(20 33 12 / .7); }
    /* Under 1024 the words come first and the pictures follow. */
    @media (max-width: 1023.98px) { .hp-biz-left { order: 2; } }

    .hp-biz-quote { position: relative; margin: 1.6rem 0 0; padding: 1.25rem 1.35rem 1.2rem; border-radius: 1.3rem; background: #fff;
        border: 1px solid #dcead0; box-shadow: 0 24px 50px -40px rgb(20 33 12 / .6); }
    .hp-biz-qm { position: absolute; right: 1rem; top: .9rem; width: 2.6rem; height: 2.6rem; color: #e9f2df; }
    .hp-biz-qk { position: relative; display: flex; align-items: center; gap: .55rem; font-size: .74rem; font-weight: 800; letter-spacing: .1em;
        text-transform: uppercase; color: var(--hp-green); }
    /* The heart beats twice, rests, and sends out a soft ring. */
    .hp-heart { position: relative; flex: none; width: 2rem; height: 2rem; border-radius: 999px; display: grid; place-items: center;
        color: #2f9e4f; background: #e5f4dc; }
    .hp-heart svg { width: 1.1rem; height: 1.1rem; transform-origin: 50% 60%; animation: hpHeart 1.6s ease-in-out infinite; }
    .hp-heart::after { content: ''; position: absolute; inset: 0; border-radius: inherit; animation: hpHeartRing 1.6s ease-out infinite; }
    @keyframes hpHeart { 0%, 100% { transform: scale(1); } 14% { transform: scale(1.24); } 28% { transform: scale(1); } 42% { transform: scale(1.15); } 70% { transform: scale(1); } }
    @keyframes hpHeartRing { 0% { box-shadow: 0 0 0 0 rgb(47 158 79 / .45); } 70%, 100% { box-shadow: 0 0 0 .6rem rgb(47 158 79 / 0); } }
    .hp-biz-quote blockquote { position: relative; margin-top: .75rem; font-size: 1rem; font-weight: 400;
        line-height: 1.7; color: #374151; text-wrap: pretty; }
    .hp-biz-quote figcaption { margin-top: 1rem; padding-top: .9rem; border-top: 1px dashed #dfe9d3; display: flex; align-items: center; gap: .7rem; }
    .hp-biz-quote figcaption img { flex: none; width: 2.7rem; height: 2.7rem; border-radius: 999px; object-fit: cover; box-shadow: 0 0 0 3px #e5f4dc; }
    .hp-biz-quote figcaption b { display: block; font-size: .88rem; font-weight: 800; color: var(--hp-ink); }
    .hp-biz-quote figcaption small { display: block; margin-top: .05rem; font-size: .78rem; color: #6b7f5a; }

    /* Everyone else upgraded; then the farm does. */
    .hp-biz-up { margin-top: .8rem; display: grid; gap: .6rem; grid-template-columns: repeat(3, minmax(0, 1fr)); }
    @media (max-width: 479.98px) { .hp-biz-up { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
    .hp-biz-u { position: relative; padding: .8rem .55rem .75rem; border-radius: 1rem; text-align: center; background: #fff; border: 1px solid #e4ecdb;
        transition: border-color .45s var(--hp-ease), background-color .45s var(--hp-ease), box-shadow .45s var(--hp-ease); }
    .hp-biz-ui { width: 2.3rem; height: 2.3rem; margin: 0 auto; border-radius: .8rem; display: grid; place-items: center; color: var(--hp-green); background: #eef5e5;
        transition: color .45s var(--hp-ease), background-color .45s var(--hp-ease); }
    .hp-biz-ui svg { width: 1.2rem; height: 1.2rem; }
    .hp-biz-u b { display: block; margin-top: .45rem; font-size: .8rem; font-weight: 800; line-height: 1.2; color: var(--hp-ink); }
    .hp-biz-u small { display: block; margin-top: .12rem; font-size: .72rem; line-height: 1.3; color: #6b7280; }
    .hp-biz-ok { position: absolute; top: -8px; right: -8px; width: 22px; height: 22px; border-radius: 999px; display: grid; place-items: center;
        color: #fff; background: var(--hp-green); box-shadow: 0 6px 12px -6px rgb(47 82 25 / .8); scale: 0;
        transition: scale .45s cubic-bezier(.34,1.56,.64,1); transition-delay: calc(.35s + var(--k) * .4s); }
    .hp-biz-ok svg { width: 12px; height: 12px; stroke-width: 3.4; }
    .hp-biz-ok { transition-delay: calc(.3s + var(--k) * .3s); }
    .hp-biz-u.is-farm .hp-biz-ok { transition-delay: 2.5s; }
    .hp-biz-u.is-farm { border-style: dashed; border-color: #f2b8b5; background: #fffafa; transition-delay: 2.1s; }
    .hp-biz-u.is-farm .hp-biz-ui { color: #b91c1c; background: #fdecea; transition-delay: 2.1s; }
    .hp-biz-swap { position: relative; display: grid !important; }
    .hp-biz-swap span { grid-area: 1 / 1; transition: opacity .4s var(--hp-ease), translate .4s var(--hp-ease); transition-delay: 2.1s; }
    .hp-biz-swap .is-q { color: #b91c1c; font-weight: 700; }
    .hp-biz-swap .is-a { color: var(--hp-green); font-weight: 800; opacity: 0; translate: 0 6px; }
    .hp-biz-up.is-visible .hp-biz-ok { scale: 1; }
    .hp-biz-up.is-visible .hp-biz-u.is-farm { border-style: solid; border-color: var(--hp-green); background: #f3f8ec; box-shadow: 0 14px 28px -20px rgb(47 82 25 / .8); }
    .hp-biz-up.is-visible .hp-biz-u.is-farm .hp-biz-ui { color: #fff; background: var(--hp-green); }
    .hp-biz-up.is-visible .hp-biz-swap .is-q { opacity: 0; translate: 0 -6px; }
    .hp-biz-up.is-visible .hp-biz-swap .is-a { opacity: 1; translate: none; }
    html:not(.js) .hp-biz-ok { scale: 1; }
    html:not(.js) .hp-biz-swap .is-q { opacity: 0; }
    html:not(.js) .hp-biz-swap .is-a { opacity: 1; translate: none; }

    .hp-biz-k { margin-top: 1.5rem; font-size: .72rem; font-weight: 800; letter-spacing: .1em; text-transform: uppercase; color: var(--hp-green); }
    .hp-gains { margin-top: .7rem; display: grid; gap: .7rem; grid-template-columns: repeat(2, minmax(0, 1fr)); }
    @media (max-width: 479.98px) { .hp-gains { grid-template-columns: 1fr; } }
    .hp-gain { display: flex; align-items: flex-start; gap: .7rem; padding: .85rem; border-radius: 1rem; background: #fff; border: 1px solid #e4ecdb;
        transition: transform .28s var(--hp-ease), box-shadow .28s var(--hp-ease), border-color .28s var(--hp-ease); }
    .hp-gain:hover { transform: translateY(-3px); border-color: #b9d69a; box-shadow: 0 16px 30px -22px rgb(20 33 12 / .5); }
    .hp-gain > span { flex: none; width: 2.2rem; height: 2.2rem; border-radius: .75rem; display: grid; place-items: center; color: var(--hp-green); background: #eef5e5; }
    .hp-gain > span svg { width: 1.2rem; height: 1.2rem; }
    .hp-gain b { display: block; font-size: .9rem; font-weight: 800; color: var(--hp-ink); line-height: 1.25; }
    .hp-gain small { display: block; margin-top: .15rem; font-size: .8rem; color: #6b7280; line-height: 1.4; }

    /* ---- your team in the field, your farm from space ---- */
    .hp-team { margin-top: 3rem; display: grid; gap: 2rem; }
    @media (min-width: 1024px) { .hp-team { grid-template-columns: minmax(0, .9fr) minmax(0, 1.1fr); gap: 3rem; align-items: center; } }
    /* Same trick as the chat window: green behind the green header, so no
       light line shows at the rounded top corners. */
    .hp-feed { border-radius: 1.6rem; overflow: hidden; background: linear-gradient(180deg, #34591c 50%, #f6f8f3 50%);
        box-shadow: 0 40px 80px -50px rgb(20 33 12 / .6), 0 1px 3px rgb(20 33 12 / .08); }
    .hp-feed-top { display: flex; align-items: center; justify-content: space-between; gap: .8rem; padding: .95rem 1.15rem; color: #fff;
        background: linear-gradient(135deg, #2f5219, #4a7c2a); }
    .hp-feed-top b { display: block; font-size: .98rem; }
    .hp-feed-top small { display: block; font-size: .74rem; color: #dceccb; }
    .hp-feed-live { flex: none; display: inline-flex; align-items: center; padding: .28rem .65rem; border-radius: 999px; font-size: .72rem; font-weight: 800;
        background: rgb(255 255 255 / .16); }
    .hp-feed-list { display: grid; align-content: start; gap: .6rem; min-height: 27rem; padding: .95rem; list-style: none; margin: 0; background: #f6f8f3; }
    .hp-feed-item { display: flex; align-items: flex-start; gap: .7rem; padding: .75rem .85rem; border-radius: 1rem; background: #fff;
        box-shadow: 0 8px 20px -16px rgb(0 0 0 / .45); opacity: 0; translate: 0 12px; transition: opacity .45s var(--hp-ease), translate .45s var(--hp-ease); }
    .hp-feed-item.is-in { opacity: 1; translate: none; }
    html:not(.js) .hp-feed-item { opacity: 1; translate: none; }
    .hp-feed-item.is-anee { background: #fffbea; box-shadow: inset 0 0 0 1px #f6e3a0, 0 8px 20px -16px rgb(0 0 0 / .45); }
    .hp-feed-av { flex: none; width: 2.2rem; height: 2.2rem; border-radius: 999px; display: grid; place-items: center; font-size: .74rem; font-weight: 800;
        color: #fff; background: hsl(var(--h, 120) 45% 42%); object-fit: cover; }
    .hp-feed-tx { min-width: 0; flex: 1; }
    .hp-feed-tx p { font-size: .86rem; line-height: 1.45; color: #374151; }
    .hp-feed-tx p:first-child { display: flex; align-items: baseline; gap: .35rem; font-size: .8rem; color: #6b7280; }
    .hp-feed-tx p:first-child b { font-size: .86rem; color: var(--hp-ink); }
    .hp-feed-tx time { margin-left: auto; font-size: .7rem; color: #9ca3af; white-space: nowrap; }
    .hp-feed-done, .hp-feed-sync i { display: inline-flex; align-items: center; gap: .3rem; margin-top: .4rem; padding: .2rem .55rem; border-radius: 999px;
        font-size: .72rem; font-weight: 800; font-style: normal; }
    .hp-feed-done { color: var(--hp-deep); background: #e7f3dc; }
    .hp-feed-done svg, .hp-feed-sync svg { width: 11px; height: 11px; stroke-width: 3.4; }
    .hp-feed-pics { margin-top: .45rem; display: flex; gap: .4rem; }
    .hp-feed-pics img { width: 4.4rem; height: 3.3rem; border-radius: .6rem; object-fit: cover; }
    .hp-feed-voice { margin-top: .45rem; display: inline-flex; align-items: center; gap: .5rem; padding: .35rem .7rem .35rem .45rem; border-radius: 999px;
        color: #fff; background: var(--hp-green); }
    .hp-feed-voice > svg { width: 1.2rem; height: 1.2rem; padding: .2rem; border-radius: 999px; background: rgb(255 255 255 / .2); }
    .hp-feed-voice b { font-size: .72rem; }
    .hp-feed-wave { display: inline-flex; align-items: center; gap: 2px; height: 16px; }
    .hp-feed-wave i { width: 2px; height: calc(var(--w) * 1px); border-radius: 2px; background: #fff; transform-origin: center; }
    .hp-feed-item.is-in .hp-feed-wave i { animation: hpWave 1.1s ease-in-out var(--d) infinite; }
    @keyframes hpWave { 0%, 100% { transform: scaleY(1); } 50% { transform: scaleY(.35); } }
    .hp-feed-sync { position: relative; display: inline-grid; }
    .hp-feed-sync i { grid-area: 1 / 1; transition: opacity .4s var(--hp-ease); }
    .hp-feed-sync .is-off { color: #92400e; background: #fef3c7; }
    .hp-feed-sync .is-on { color: var(--hp-deep); background: #e7f3dc; opacity: 0; }
    .hp-feed-item.is-in .hp-feed-sync .is-off { opacity: 0; transition-delay: 1.3s; }
    .hp-feed-item.is-in .hp-feed-sync .is-on { opacity: 1; transition-delay: 1.3s; }
    html:not(.js) .hp-feed-sync .is-off { opacity: 0; }
    html:not(.js) .hp-feed-sync .is-on { opacity: 1; }

    .hp-team-cards { display: grid; gap: .8rem; grid-template-columns: repeat(auto-fill, minmax(15.5rem, 1fr)); }
    .hp-team-card { display: flex; align-items: flex-start; gap: .8rem; padding: 1rem 1.1rem; border-radius: 1.15rem; background: #fff; border: 1px solid #e4ecdb;
        transition: border-color .28s var(--hp-ease), box-shadow .28s var(--hp-ease), opacity .6s ease, transform .6s var(--hp-ease); }
    .hp-team-card:hover { border-color: #b9d69a; box-shadow: 0 18px 34px -26px rgb(20 33 12 / .55); }
    /* The reveal's own transition would otherwise snap the hover. */
    html.js .hp-team-card.reveal { transition: opacity .6s ease, transform .6s var(--hp-ease), border-color .28s var(--hp-ease), box-shadow .28s var(--hp-ease);
        transition-delay: var(--reveal-delay, 0s), var(--reveal-delay, 0s), 0s, 0s; }
    .hp-team-ico { flex: none; width: 2.5rem; height: 2.5rem; border-radius: .85rem; display: grid; place-items: center; color: #fff; background: var(--hp-green);
        box-shadow: 0 10px 18px -12px rgb(47 82 25 / .9); }
    .hp-team-ico svg { width: 1.3rem; height: 1.3rem; }
    .hp-team-card h3 { font-family: var(--font-heading); font-size: 1rem; font-weight: 800; color: var(--hp-ink); line-height: 1.25; }
    .hp-team-card p { margin-top: .25rem; font-size: .86rem; line-height: 1.5; color: #4b5563; }

    /* The Collab Room window: its tools take turns on a loop. */
    .hp-room { margin-top: 4.5rem; display: grid; gap: 2rem; padding: 1.4rem; border-radius: 2rem;
        background: linear-gradient(150deg, #f4f9ee 0%, #ffffff 55%, #fbf7e6 100%); border: 1px solid #e1ead6; box-shadow: 0 40px 80px -60px rgb(20 33 12 / .6); }
    .hp-room { grid-template-columns: minmax(0, 1fr); }
    .hp-room > * { min-width: 0; }
    @media (min-width: 1024px) { .hp-room { grid-template-columns: minmax(0, 1fr) minmax(0, 1.05fr); padding: 2.4rem; gap: 3rem; align-items: center; } }
    .hp-room-h { margin-top: .6rem; font-family: var(--font-heading); font-size: clamp(1.55rem, 3.2vw, 2.2rem); font-weight: 800; line-height: 1.12;
        letter-spacing: -.01em; color: var(--hp-ink); text-wrap: balance; }
    .hp-room-h em { font-style: normal; color: var(--hp-green); }
    .hp-room-p { margin-top: .8rem; color: #4b5563; line-height: 1.7; text-wrap: pretty; }
    .hp-room-tabs { margin-top: 1.3rem; display: grid; gap: .45rem; grid-template-columns: repeat(auto-fill, minmax(14.5rem, 1fr)); }
    /* Under 1024 the tools are one swipeable row, so the room stays right under them. */
    @media (max-width: 1023.98px) {
        .hp-room-tabs { display: flex; overflow-x: auto; scroll-snap-type: x mandatory; scrollbar-width: none; margin-left: -.4rem; margin-right: -.4rem;
            padding: .2rem .4rem .5rem; }
        .hp-room-tabs::-webkit-scrollbar { display: none; }
        .hp-room-tab { flex: none; width: 15rem; scroll-snap-align: start; }
    }
    @media (max-width: 479.98px) { .hp-room-top small { display: none; } }
    .hp-room-tab { position: relative; overflow: hidden; display: flex; align-items: center; gap: .65rem; padding: .6rem .75rem; border-radius: 1rem; text-align: left;
        cursor: pointer; background: #fff; border: 1px solid #e4ecdb;
        transition: border-color .28s var(--hp-ease), box-shadow .28s var(--hp-ease), background-color .28s var(--hp-ease); }
    .hp-room-tab:hover { border-color: #b9d69a; }
    .hp-room-tab.is-on { border-color: var(--hp-green); background: #fbfdf8; box-shadow: 0 12px 24px -18px rgb(47 82 25 / .7); }
    .hp-room-ti { flex: none; width: 2.2rem; height: 2.2rem; border-radius: .75rem; display: grid; place-items: center; color: var(--hp-green); background: #eef5e5;
        transition: color .28s var(--hp-ease), background-color .28s var(--hp-ease); }
    .hp-room-tab.is-on .hp-room-ti { color: #fff; background: var(--hp-green); }
    .hp-room-ti svg { width: 1.15rem; height: 1.15rem; }
    .hp-room-tab b { display: block; font-size: .86rem; font-weight: 800; color: var(--hp-ink); line-height: 1.25; }
    .hp-room-tab small { display: block; margin-top: .08rem; font-size: .75rem; line-height: 1.35; color: #6b7f5a; }
    .hp-room-bar { position: absolute; left: 0; right: 0; bottom: 0; height: 3px; transform-origin: left; transform: scaleX(0); background: var(--hp-green); }
    .hp-room-tab.is-on.is-timing .hp-room-bar { animation: hpBar var(--room-dwell, 4.5s) linear forwards; }

    .hp-room-win { border-radius: 1.4rem; overflow: hidden; background: linear-gradient(180deg, #2f5219 50%, #f6f8f3 50%);
        box-shadow: 0 40px 80px -46px rgb(20 33 12 / .7), 0 1px 3px rgb(20 33 12 / .08); }
    .hp-room-top { display: flex; align-items: center; gap: .55rem; padding: .8rem 1rem; color: #fff; background: linear-gradient(135deg, #2f5219, #4a7c2a); }
    .hp-room-dots { display: inline-flex; gap: .3rem; margin-right: .2rem; }
    .hp-room-dots i { width: .55rem; height: .55rem; border-radius: 999px; background: rgb(255 255 255 / .35); }
    .hp-room-top b { font-size: .92rem; }
    .hp-room-top small { font-size: .74rem; color: #dceccb; }
    .hp-room-on { margin-left: auto; display: inline-flex; align-items: center; padding: .22rem .6rem; border-radius: 999px; font-size: .72rem; font-weight: 800; background: rgb(255 255 255 / .16); }
    .hp-room-panes { display: grid; grid-template-rows: minmax(0, 1fr); height: 22rem; overflow: hidden; background: #f6f8f3; }
    .hp-room-pane { grid-area: 1 / 1; padding: 1rem; opacity: 0; visibility: hidden; translate: 0 8px;
        transition: opacity .4s var(--hp-ease), translate .4s var(--hp-ease), visibility .4s; }
    .hp-room-pane.is-on { opacity: 1; visibility: visible; translate: none; }
    .hp-room-ph { font-size: .7rem; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; color: #6b7f5a; margin-bottom: .55rem; }
    /* Items inside a pane come in one after another each time it opens. */
    .hp-acc, .hp-rc, .hp-task { opacity: 0; translate: 0 8px; }
    .hp-room-pane.is-on .hp-acc, .hp-room-pane.is-on .hp-rc, .hp-room-pane.is-on .hp-task { animation: hpRoomIn .45s var(--hp-ease) calc(.15s + var(--k) * .35s) forwards; }
    @keyframes hpRoomIn { to { opacity: 1; translate: none; } }

    .hp-acc { display: flex; align-items: center; gap: .6rem; padding: .6rem .7rem; border-radius: .9rem; background: #fff; box-shadow: 0 6px 16px -14px rgb(0 0 0 / .5); }
    .hp-acc + .hp-acc { margin-top: .5rem; }
    .hp-acc-who b { display: block; font-size: .84rem; color: var(--hp-ink); }
    .hp-acc-who small { display: block; font-size: .72rem; color: #6b7280; }
    .hp-acc-perms { margin-left: auto; display: flex; flex-wrap: wrap; justify-content: flex-end; gap: .25rem; }
    .hp-acc-perms i { font-style: normal; font-size: .66rem; font-weight: 800; padding: .15rem .45rem; border-radius: 999px; }
    .hp-acc-perms .is-edit { color: var(--hp-deep); background: #e1eed2; }
    .hp-acc-perms .is-view { color: #1e40af; background: #dbeafe; }
    .hp-acc-perms .is-none { color: #6b7280; background: #f3f4f6; }

    .hp-rc { display: flex; align-items: flex-end; gap: .45rem; }
    .hp-rc + .hp-rc { margin-top: .55rem; }
    .hp-rc p { max-width: 80%; padding: .55rem .75rem; border-radius: 1rem; font-size: .82rem; line-height: 1.45; background: #fff; color: #374151;
        box-shadow: 0 6px 14px -12px rgb(0 0 0 / .5); border-bottom-left-radius: .3rem; }
    .hp-rc.is-me { justify-content: flex-end; }
    .hp-rc.is-me p { color: #fff; background: var(--hp-green); border-bottom-left-radius: 1rem; border-bottom-right-radius: .3rem; }
    .hp-rc.is-anee p { background: #fffbea; box-shadow: inset 0 0 0 1px #f6e3a0; }
    .hp-rc .hp-feed-av { width: 1.8rem; height: 1.8rem; font-size: .62rem; }
    .hp-rc-voice { display: inline-flex !important; align-items: center; gap: .45rem; color: #fff !important; background: var(--hp-green) !important; }
    .hp-rc-voice > svg { width: 1rem; height: 1rem; }
    .hp-rc-voice b { font-size: .7rem; }
    .hp-room-pane.is-on .hp-rc-voice .hp-feed-wave i { animation: hpWave 1.1s ease-in-out var(--d) infinite; }

    .hp-call { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .55rem; }
    .hp-call-tile { position: relative; display: grid; place-items: center; gap: .3rem; padding: 1.1rem .5rem .8rem; border-radius: 1rem;
        background: linear-gradient(160deg, hsl(var(--h) 35% 22%), hsl(var(--h) 30% 14%)); color: #fff; }
    .hp-call-av { width: 3rem; height: 3rem; border-radius: 999px; display: grid; place-items: center; font-weight: 800; font-size: .9rem;
        background: hsl(var(--h) 45% 45%); box-shadow: 0 0 0 0 rgb(74 222 128 / .7); }
    .hp-call-tile small { font-size: .74rem; font-weight: 700; color: #e2e8f0; }
    .hp-room-pane.is-on .hp-call-tile.is-talking .hp-call-av { animation: hpTalk 1.4s ease-out infinite; }
    @keyframes hpTalk { 0% { box-shadow: 0 0 0 0 rgb(74 222 128 / .75); } 70%, 100% { box-shadow: 0 0 0 .7rem rgb(74 222 128 / 0); } }
    .hp-call-bar { margin-top: .7rem; display: flex; align-items: center; gap: .6rem; padding: .55rem .75rem; border-radius: .9rem; background: #fff;
        font-size: .8rem; font-weight: 800; color: var(--hp-ink); box-shadow: 0 6px 16px -14px rgb(0 0 0 / .5); }
    .hp-call-bar b { font-variant-numeric: tabular-nums; color: var(--hp-green); }
    .hp-call-end { margin-left: auto; padding: .25rem .7rem; border-radius: 999px; color: #fff; background: #dc2626; font-size: .72rem; }

    /* The two feeds fit the window's fixed height: a 3 by 4 frame while it
       fits, shorter (the picture still covering it) where a wide window would
       push its bottom out of view. */
    .hp-cams { display: grid; gap: .55rem; grid-template-columns: repeat(2, minmax(0, 1fr)); grid-template-rows: minmax(0, 1fr); height: 100%; }
    .hp-cam { position: relative; margin: 0; overflow: hidden; border-radius: 1rem; aspect-ratio: 3 / 4; max-height: 100%; align-self: center; justify-self: center; background: #1f2937; }
    .hp-cam img { width: 100%; height: 100%; object-fit: cover; }
    .hp-room-pane.is-on .hp-cam img { animation: hpCamPan 9s ease-in-out infinite alternate; }
    @keyframes hpCamPan { from { scale: 1.02; translate: 0 0; } to { scale: 1.12; translate: -3% -2%; } }
    .hp-cam figcaption { position: absolute; left: .6rem; top: .6rem; display: inline-flex; align-items: center; gap: .35rem; padding: .2rem .55rem; border-radius: 999px;
        font-size: .68rem; font-weight: 800; color: #fff; background: rgb(0 0 0 / .55); }
    .hp-cam figcaption i { width: .45rem; height: .45rem; border-radius: 999px; background: #ef4444; animation: hpLive 1.6s ease-out infinite; box-shadow: 0 0 0 0 rgb(239 68 68 / .6); }

    .hp-loc { position: relative; height: 16.5rem; border-radius: 1rem; background: #2c3b20; overflow: hidden; box-shadow: inset 0 0 0 1px rgb(0 0 0 / .08); }
    .hp-loc-map { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; transform: scale(1.04); }
    .hp-room-pane.is-on .hp-loc-map { animation: hpLocDrift 16s ease-in-out infinite alternate; }
    @keyframes hpLocDrift { from { transform: scale(1.04); } to { transform: scale(1.1) translate(-1.5%, 1%); } }
    .hp-loc-chip { position: absolute; left: .6rem; top: .6rem; display: inline-flex; align-items: center; gap: .3rem; padding: .3rem .6rem; border-radius: .6rem;
        font-size: .7rem; font-weight: 800; color: var(--hp-ink); background: rgb(255 255 255 / .94); box-shadow: 0 6px 14px -8px rgb(0 0 0 / .6); }
    .hp-loc-chip svg { width: .95rem; height: .95rem; color: var(--hp-green); }
    .hp-loc-zoom { position: absolute; right: .6rem; top: .6rem; display: grid; border-radius: .6rem; overflow: hidden; background: rgb(255 255 255 / .94);
        box-shadow: 0 6px 14px -8px rgb(0 0 0 / .6); }
    .hp-loc-zoom i { width: 1.7rem; height: 1.7rem; display: grid; place-items: center; color: #374151; }
    .hp-loc-zoom i + i { border-top: 1px solid #e5e7eb; }
    .hp-loc-zoom svg { width: .85rem; height: .85rem; }
    .hp-loc-tag { position: absolute; translate: -50% -50%; white-space: nowrap; padding: .18rem .5rem; border-radius: .45rem; font-size: .66rem; font-weight: 800; color: #fff;
        background: rgb(15 26 10 / .55); border: 1px dashed rgb(245 197 24 / .9); backdrop-filter: blur(2px); }
    .hp-loc-scale { position: absolute; left: .6rem; bottom: .55rem; display: inline-flex; align-items: center; gap: .35rem; font-size: .62rem; font-weight: 800; color: #fff;
        text-shadow: 0 1px 2px rgb(0 0 0 / .8); }
    .hp-loc-scale i { width: 2.6rem; height: .35rem; border: 2px solid #fff; border-top: 0; box-shadow: 0 1px 2px rgb(0 0 0 / .5); }
    .hp-loc-pin { position: absolute; width: 1.9rem; height: 1.9rem; margin: -.95rem 0 0 -.95rem; border-radius: 999px; display: grid; place-items: center;
        font-size: .6rem; font-weight: 800; color: #fff; background: hsl(var(--h) 50% 42%); box-shadow: 0 0 0 3px #fff, 0 0 0 9px hsl(var(--h) 60% 55% / .3), 0 8px 16px -6px rgb(0 0 0 / .6); }
    .hp-loc-pin.is-a { left: 22%; top: 30%; }
    .hp-loc-pin.is-b { left: 70%; top: 72%; }
    .hp-loc-pin.is-c { left: 55%; top: 34%; }
    .hp-room-pane.is-on .hp-loc-pin.is-a { animation: hpPinA 7s ease-in-out infinite alternate; }
    .hp-room-pane.is-on .hp-loc-pin.is-b { animation: hpPinB 8s ease-in-out infinite alternate; }
    .hp-room-pane.is-on .hp-loc-pin.is-c { animation: hpPinC 6s ease-in-out infinite alternate; }
    @keyframes hpPinA { to { left: 38%; top: 58%; } }
    @keyframes hpPinB { to { left: 82%; top: 40%; } }
    @keyframes hpPinC { to { left: 62%; top: 22%; } }
    .hp-loc-say { margin-top: .6rem; display: inline-flex; align-items: center; padding: .35rem .7rem; border-radius: 999px; font-size: .76rem; font-weight: 800;
        color: var(--hp-deep); background: #fff; box-shadow: 0 6px 14px -12px rgb(0 0 0 / .5); }

    /* Team tasks, drawn like the app's Activities board: the day's green
       header with its stage and weather, then the task cards with the lot
       chip, the priority and the type; two get ticked off as you watch. */
    .hp-act-day { display: flex; flex-wrap: wrap; align-items: center; gap: .35rem .5rem; padding: .6rem .75rem; border-radius: .9rem;
        background: #dff2e1; box-shadow: inset 3px 0 0 #3fae5a; }
    .hp-act-dow { font-size: .78rem; font-weight: 800; color: #2f9a4b; }
    .hp-act-day b { font-size: .98rem; font-weight: 800; color: var(--hp-ink); }
    .hp-act-n { min-width: 1.4rem; height: 1.4rem; padding: 0 .35rem; border-radius: 999px; display: grid; place-items: center; font-size: .68rem; font-weight: 800;
        color: #2f9a4b; background: #fff; }
    .hp-act-chip { display: inline-flex; align-items: center; gap: .25rem; padding: .18rem .5rem; border-radius: 999px; font-size: .66rem; font-weight: 800; color: #374151; background: #fff; }
    .hp-act-chip.is-stage { margin-left: auto; color: #4d5a1c; background: #f4f6d8; box-shadow: inset 0 0 0 1px #e3e7b2; }
    .hp-act-chip svg { width: .8rem; height: .8rem; }
    .hp-act { position: relative; margin-top: .5rem; padding: .6rem .7rem .65rem .9rem; border-radius: .9rem; background: #fff; box-shadow: 0 6px 16px -14px rgb(0 0 0 / .5);
        opacity: 0; translate: 0 8px; }
    .hp-act::before { content: ''; position: absolute; left: 0; top: .5rem; bottom: .5rem; width: 3px; border-radius: 3px; background: #5b2fb3; }
    .hp-room-pane.is-on .hp-act { animation: hpRoomIn .45s var(--hp-ease) calc(.15s + var(--k) * .3s) forwards; }
    .hp-act-top { display: flex; align-items: center; gap: .5rem; }
    .hp-act-box { flex: none; width: 1.25rem; height: 1.25rem; border-radius: .4rem; display: grid; place-items: center; color: transparent; border: 2px solid #d1d5db;
        transition: background-color .3s var(--hp-ease), border-color .3s var(--hp-ease), color .3s var(--hp-ease); }
    .hp-act-box svg { width: 11px; height: 11px; stroke-width: 3.4; }
    .hp-act-t { flex: 1; min-width: 0; font-size: .86rem; font-weight: 800; color: var(--hp-ink); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        transition: color .3s var(--hp-ease); }
    .hp-act-who { display: inline-flex; }
    .hp-act-who .hp-feed-av { width: 1.5rem; height: 1.5rem; font-size: .55rem; box-shadow: 0 0 0 2px #fff; }
    .hp-act-who .hp-feed-av + .hp-feed-av { margin-left: -.35rem; }
    .hp-act-meta { margin-top: .4rem; display: flex; flex-wrap: wrap; align-items: center; gap: .3rem; }
    .hp-act-lot { display: inline-flex; align-items: center; gap: .3rem; padding: .16rem .5rem; border-radius: .45rem; font-size: .66rem; font-weight: 800; color: #fff; background: #5b2fb3; }
    .hp-act-lot svg { width: .75rem; height: .75rem; }
    .hp-act-lot i { width: 1px; align-self: stretch; background: rgb(255 255 255 / .4); }
    .hp-act-pr, .hp-act-ty { padding: .16rem .45rem; border-radius: .45rem; font-size: .62rem; font-weight: 800; }
    .hp-act-pr.is-high { color: #fff; background: #e1574a; }
    .hp-act-pr.is-medium { color: #5a3a00; background: #f4b546; }
    .hp-act-pr.is-low { color: #1e4d8c; background: #dbeafe; }
    .hp-act-ty { color: #3d6823; background: #e9f3dc; }
    /* Ticked off: the first once the cards are in, the second a beat later. */
    .hp-room-pane.is-on .hp-act.is-done .hp-act-box, .hp-room-pane.is-on .hp-act.is-done2 .hp-act-box { color: #fff; background: #2f9a4b; border-color: #2f9a4b; }
    .hp-room-pane.is-on .hp-act.is-done .hp-act-t, .hp-room-pane.is-on .hp-act.is-done2 .hp-act-t { color: #9ca3af; text-decoration: line-through; }
    .hp-room-pane.is-on .hp-act.is-done .hp-act-box, .hp-room-pane.is-on .hp-act.is-done .hp-act-t { transition-delay: 1.4s; }
    .hp-room-pane.is-on .hp-act.is-done2 .hp-act-box, .hp-room-pane.is-on .hp-act.is-done2 .hp-act-t { transition-delay: 2.4s; }

    /* The whiteboard is a sketch: wobbly hand drawn lots, a canal, an
       arrow and a scribbled seedbed, each stroke drawn in turn, then the
       handwriting. */
    .hp-wb { display: block; width: 100%; height: auto; border-radius: 1rem; box-shadow: 0 6px 16px -14px rgb(0 0 0 / .5);
        background: radial-gradient(circle, #e7ece1 1px, transparent 1.2px) 0 0 / 14px 14px, #fff; }
    .hp-wb-ink { fill: none; stroke-width: 2.6; stroke-linecap: round; stroke-linejoin: round; stroke-dasharray: 1; stroke-dashoffset: 1; }
    .hp-wb-ink.is-lot { stroke: #2f5219; }
    .hp-wb-ink.is-canal { stroke: #2563eb; stroke-width: 3.4; }
    .hp-wb-ink.is-arrow { stroke: #dc2626; stroke-width: 3; }
    .hp-wb-ink.is-seed { stroke: #e9a80b; stroke-width: 2.8; }
    .hp-wb-ink.is-thin { stroke-width: 1.8; }
    .hp-wb-ink.is-tuft { stroke: #4a7c2a; stroke-width: 1.8; }
    .hp-wb-ink.is-tree { stroke: #3f8f3a; stroke-width: 2.2; }
    .hp-wb-ink.is-shed { stroke: #7c4a1e; stroke-width: 2.2; }
    .hp-room-pane.is-on .hp-wb-ink { animation: hpWbDraw .7s cubic-bezier(.65,0,.35,1) calc(.2s + var(--o) * .38s) forwards; }
    @keyframes hpWbDraw { to { stroke-dashoffset: 0; } }
    .hp-wb-hand text { font-family: 'Caveat', 'Comic Sans MS', cursive; font-size: 17px; font-weight: 700; fill: #2f5219; opacity: 0; }
    .hp-wb-hand text:nth-child(3) { fill: #b07800; }
    .hp-wb-hand text:nth-child(4) { fill: #2563eb; font-size: 15px; }
    .hp-room-pane.is-on .hp-wb-hand text { animation: hpWbWrite .5s var(--hp-ease) calc(.4s + var(--o) * .38s) forwards; }
    @keyframes hpWbWrite { from { opacity: 0; translate: 0 3px; } to { opacity: 1; translate: none; } }
    .hp-wb-note { display: inline-block; margin-top: .6rem; padding: .45rem .7rem; border-radius: .6rem; font-size: .76rem; font-weight: 800; color: #713f12;
        background: #fef3c7; rotate: -2deg; box-shadow: 0 6px 14px -10px rgb(0 0 0 / .45); }

    /* The band from space. */
    .hp-sky { margin-top: 4.5rem; display: grid; gap: 2rem; padding: 1.5rem; border-radius: 2rem; color: #e2e8f0;
        background: radial-gradient(80% 90% at 85% 10%, #1f3b63 0%, transparent 60%), linear-gradient(160deg, #0b1324 0%, #0f1c33 55%, #13291c 100%);
        box-shadow: 0 50px 100px -60px rgb(11 19 36 / .9); }
    @media (min-width: 900px) { .hp-sky { grid-template-columns: minmax(0, .95fr) minmax(0, 1.05fr); padding: 2.4rem; gap: 2.8rem; align-items: center; } }
    .hp-sky-h { margin-top: .7rem; font-family: var(--font-heading); font-size: clamp(1.55rem, 3.2vw, 2.2rem); font-weight: 800; line-height: 1.12;
        letter-spacing: -.01em; color: #fff; text-wrap: balance; }
    .hp-sky-h em { font-style: normal; color: var(--hp-sun); }
    .hp-sky-p { margin-top: .9rem; color: #cbd5e1; line-height: 1.7; text-wrap: pretty; }
    .hp-sky-stats { margin-top: 1.4rem; display: grid; gap: .6rem; grid-template-columns: repeat(3, minmax(0, 1fr)); }
    @media (max-width: 559.98px) { .hp-sky-stats { grid-template-columns: 1fr; } }
    .hp-sky-stats div { padding: .8rem .9rem; border-radius: 1rem; background: rgb(255 255 255 / .06); box-shadow: inset 0 0 0 1px rgb(255 255 255 / .12); }
    .hp-sky-stats b { display: block; font-family: var(--font-heading); font-size: .95rem; font-weight: 800; color: var(--hp-sun); line-height: 1.25; }
    .hp-sky-stats small { display: block; margin-top: .2rem; font-size: .78rem; line-height: 1.4; color: #cbd5e1; }

    /* The satellite over the paddies: the health map is scanned in, radar
       rings pass through the clouds, and it all goes round again. */
    .hp-sat { position: relative; aspect-ratio: 5 / 4; border-radius: 1.4rem; overflow: hidden; isolation: isolate;
        background: radial-gradient(110% 80% at 50% 0%, #1d2d50 0%, #0b1324 55%, #070c16 100%); box-shadow: inset 0 0 0 1px rgb(255 255 255 / .08); }
    .hp-sat-stars { position: absolute; inset: 0 0 45% 0; opacity: .8; animation: hpTwinkle 3.5s ease-in-out infinite alternate;
        background-image: radial-gradient(1.2px 1.2px at 8% 20%, #fff 50%, transparent 51%), radial-gradient(1px 1px at 22% 8%, #fff 50%, transparent 51%),
            radial-gradient(1.4px 1.4px at 37% 30%, #dbeafe 50%, transparent 51%), radial-gradient(1px 1px at 55% 12%, #fff 50%, transparent 51%),
            radial-gradient(1.2px 1.2px at 72% 26%, #fff 50%, transparent 51%), radial-gradient(1px 1px at 88% 10%, #dbeafe 50%, transparent 51%),
            radial-gradient(1.3px 1.3px at 93% 38%, #fff 50%, transparent 51%), radial-gradient(1px 1px at 14% 44%, #fff 50%, transparent 51%),
            radial-gradient(1px 1px at 63% 42%, #fff 50%, transparent 51%), radial-gradient(1.2px 1.2px at 47% 5%, #fff 50%, transparent 51%); }
    @keyframes hpTwinkle { from { opacity: .45; } to { opacity: .95; } }
    .hp-sat-ground { position: absolute; left: -6%; right: -6%; bottom: -6%; height: 64%; perspective: 520px; }
    .hp-sat-plane { position: absolute; inset: 0; transform: rotateX(52deg) rotateZ(-8deg); transform-origin: 50% 75%; }
    .hp-sat-map { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; border-radius: 8px;
        box-shadow: 0 0 0 1px rgb(255 255 255 / .12), 0 0 60px 10px rgb(47 158 68 / .15); }
    .hp-sat-map.is-health { -webkit-clip-path: inset(0 100% 0 0); clip-path: inset(0 100% 0 0); }
    .hp-sat-scan { position: absolute; top: -3%; bottom: -3%; left: 0; width: 3px; opacity: 0; border-radius: 3px;
        background: linear-gradient(transparent, #7dd3fc, transparent); box-shadow: 0 0 16px 5px rgb(125 211 252 / .6); }
    .hp-sat.is-visible .hp-sat-map.is-health { animation: hpSatScan 9s linear infinite; }
    .hp-sat.is-visible .hp-sat-scan { animation: hpSatLine 9s linear infinite; }
    @keyframes hpSatScan { 0% { clip-path: inset(0 100% 0 0); opacity: 1; } 40%, 86% { clip-path: inset(0 0 0 0); opacity: 1; } 100% { clip-path: inset(0 0 0 0); opacity: 0; } }
    @keyframes hpSatLine { 0% { left: 0; opacity: 1; } 40% { left: 100%; opacity: 1; } 42%, 100% { left: 100%; opacity: 0; } }
    .hp-sat-craft { position: absolute; left: 50%; top: 9%; width: 34%; translate: -50% 0; z-index: 3; animation: hpSatFloat 6s ease-in-out infinite;
        filter: drop-shadow(0 10px 18px rgb(0 0 0 / .5)); }
    .hp-sat-craft svg { display: block; width: 100%; }
    @keyframes hpSatFloat { 0%, 100% { transform: translateY(0) rotate(-3deg); } 50% { transform: translateY(-8px) rotate(3deg); } }
    .hp-sat-panel rect { fill: #1e40af; stroke: #93c5fd; stroke-width: 1.5; }
    .hp-sat-panel path { stroke: #93c5fd; stroke-width: 1; opacity: .7; }
    .hp-sat-arm { stroke: #cbd5e1; stroke-width: 2.5; stroke-linecap: round; }
    .hp-sat-body { fill: #e9b949; stroke: #fde68a; stroke-width: 1.5; }
    .hp-sat-foil { stroke: #b7791f; stroke-width: 1.5; }
    .hp-sat-dish { fill: #e2e8f0; }
    .hp-sat-blink { fill: #4ade80; animation: hpLive 2s ease-out infinite; }
    .hp-sat-beam { position: absolute; left: 50%; top: 23%; width: 70%; height: 50%; translate: -50% 0; z-index: 1;
        -webkit-clip-path: polygon(46% 0, 54% 0, 100% 100%, 0 100%); clip-path: polygon(46% 0, 54% 0, 100% 100%, 0 100%);
        background: linear-gradient(rgb(250 204 21 / .42), rgb(250 204 21 / 0)); animation: hpSatBeam 3s ease-in-out infinite; }
    @keyframes hpSatBeam { 0%, 100% { opacity: .55; } 50% { opacity: 1; } }
    .hp-sat-wave { position: absolute; left: 50%; top: 26%; width: 80%; aspect-ratio: 1; translate: -50% -50%; z-index: 2; border-radius: 999px;
        border: 2px solid rgb(125 211 252 / .75); -webkit-clip-path: inset(50% 0 0 0); clip-path: inset(50% 0 0 0); opacity: 0; scale: .1;
        animation: hpSatWave 3s ease-out var(--d, 0s) infinite; }
    @keyframes hpSatWave { 0% { scale: .1; opacity: .9; } 100% { scale: 1.5; opacity: 0; } }
    .hp-sat-cloud { position: absolute; z-index: 2; height: 8%; width: 24%; border-radius: 999px; background: rgb(241 245 249 / .78); filter: blur(2.5px);
        animation: hpSatCloud 22s linear infinite; }
    .hp-sat-cloud::before, .hp-sat-cloud::after { content: ''; position: absolute; border-radius: 999px; background: inherit; }
    .hp-sat-cloud::before { width: 45%; height: 150%; left: 18%; bottom: 25%; }
    .hp-sat-cloud::after { width: 35%; height: 120%; left: 50%; bottom: 30%; }
    .hp-sat-cloud.is-a { top: 40%; left: -30%; }
    .hp-sat-cloud.is-b { top: 50%; left: -30%; width: 19%; opacity: .7; animation-duration: 30s; animation-delay: -14s; }
    @keyframes hpSatCloud { from { transform: translateX(0); } to { transform: translateX(650%); } }
    .hp-sat-tag { position: absolute; z-index: 4; display: inline-flex; align-items: center; gap: .35rem; padding: .3rem .6rem; border-radius: 999px;
        font-size: .68rem; font-weight: 800; color: #0b1324; background: rgb(255 255 255 / .92); box-shadow: 0 8px 18px -10px rgb(0 0 0 / .6); }
    .hp-sat-tag i { width: .5rem; height: .5rem; border-radius: 999px; }
    .hp-sat-tag.is-photo { left: 5%; top: 30%; }
    .hp-sat-tag.is-photo i { background: #facc15; }
    .hp-sat-tag.is-radar { right: 5%; top: 40%; }
    .hp-sat-tag.is-radar i { background: #38bdf8; }
    .hp-sat-legend { position: absolute; left: 50%; bottom: 4%; translate: -50% 0; z-index: 4; display: flex; gap: .7rem; padding: .35rem .8rem; border-radius: 999px;
        white-space: nowrap; font-size: .68rem; font-weight: 800; color: #0b1324; background: rgb(255 255 255 / .92); }
    .hp-sat-legend span { display: inline-flex; align-items: center; gap: .3rem; }
    .hp-sat-legend i { width: .55rem; height: .55rem; border-radius: 2px; background: var(--c); }
    @media (max-width: 479.98px) {
        .hp-sat-tag { font-size: .6rem; padding: .25rem .5rem; }
        .hp-sat-legend { font-size: .62rem; gap: .5rem; }
    }

    /* ---- typhoon watch: a Zoom Earth style map, the plan beside it ---- */
    .hp-storm { margin-top: 1.5rem; display: grid; gap: 1.6rem; padding: 1.5rem; border-radius: 2rem; color: #e2e8f0;
        background: radial-gradient(70% 80% at 10% 0%, #2a2f5c 0%, transparent 60%), linear-gradient(160deg, #0b1324 0%, #101a30 60%, #1a1630 100%);
        box-shadow: 0 50px 100px -60px rgb(11 19 36 / .9); }
    .hp-storm > * { min-width: 0; }
    /* The words across the top, then the map and Anee's plan side by side. */
    @media (min-width: 1024px) {
        .hp-storm { grid-template-columns: minmax(0, 1.2fr) minmax(0, .8fr); grid-template-areas: "copy copy" "map plan"; padding: 2.4rem; gap: 2rem 2.2rem; align-items: center; }
        .hp-storm-map { grid-area: map; }
        .hp-storm-copy { grid-area: copy; display: grid; grid-template-columns: minmax(0, .9fr) minmax(0, 1.1fr); gap: .4rem 2.6rem; align-items: end; }
        .hp-storm-copy .hp-kick, .hp-storm-copy .hp-sky-h { grid-column: 1; }
        .hp-storm-copy .hp-sky-p { grid-column: 2; grid-row: 1 / span 2; margin-top: 0; }
        .hp-storm-copy .hp-sky-stats { grid-column: 1 / -1; }
        .hp-storm-plan { grid-area: plan; }
    }
    @media (max-width: 559.98px) { .hp-storm .hp-sky-stats { grid-template-columns: 1fr; } }
    .hp-storm-map { position: relative; aspect-ratio: 1280 / 1000; border-radius: 1.4rem; overflow: hidden; background: #0a1222;
        box-shadow: 0 0 0 1px rgb(255 255 255 / .1), 0 40px 80px -40px rgb(0 0 0 / .8); container-type: inline-size; }
    .hp-storm-base { position: absolute; inset: 0; width: 100%; height: 100%; }
    .hp-storm-clouds { position: absolute; inset: -6% -12%; background: url('{{ asset('images/site/storm/clouds.webp') }}') center / cover no-repeat; opacity: .5;
        animation: hpStormDrift 46s ease-in-out infinite alternate; pointer-events: none; }
    @keyframes hpStormDrift { from { transform: translate(4%, 2%); } to { transform: translate(-6%, -2%); } }
    .hp-storm-svg { position: absolute; inset: 0; width: 100%; height: 100%; overflow: visible; }
    .hp-storm-cone { fill: rgb(255 255 255 / .1); stroke: rgb(255 255 255 / .35); stroke-width: 2; stroke-dasharray: 8 8; }
    .hp-storm-track { fill: none; stroke: #f5c518; stroke-width: 5; stroke-dasharray: 2 16; stroke-linecap: round; }
    .hp-storm-day circle { fill: #0b1324; stroke: #f5c518; stroke-width: 4; }
    .hp-storm-day text { fill: #fff; font: 800 26px var(--font-heading, sans-serif); text-anchor: middle; paint-order: stroke; stroke: rgb(11 19 36 / .75); stroke-width: 6px; }
    .hp-storm-gap { stroke: #fff; stroke-width: 3; stroke-dasharray: 6 8; }
    .hp-storm-km { fill: #fff; font: 800 24px var(--font-heading, sans-serif); paint-order: stroke; stroke: rgb(11 19 36 / .8); stroke-width: 6px; }
    .hp-storm-name { fill: #fff; font: 800 26px var(--font-heading, sans-serif); text-anchor: middle; paint-order: stroke; stroke: rgb(185 28 28 / .9); stroke-width: 10px; }
    .hp-storm-farm { position: absolute; left: 25.78%; top: 40.5%; width: 0; height: 0; }
    .hp-storm-farm i { position: absolute; left: -.55rem; top: -.55rem; width: 1.1rem; height: 1.1rem; border-radius: 999px; background: #f5c518; box-shadow: 0 0 0 3px #0b1324; }
    .hp-storm-farm i::after { content: ''; position: absolute; inset: -3px; border-radius: inherit; animation: hpAkPulse 2s ease-out infinite; }
    .hp-storm-farm b { position: absolute; right: .9rem; top: -.75rem; white-space: nowrap; padding: .2rem .55rem; border-radius: .5rem; font-size: .7rem; font-weight: 800;
        color: #0b1324; background: #f5c518; box-shadow: 0 8px 18px -8px rgb(0 0 0 / .6); }
    .hp-storm-top { position: absolute; left: .7rem; top: .7rem; display: inline-flex; align-items: center; gap: .1rem .45rem; flex-wrap: wrap; max-width: 60%;
        padding: .35rem .7rem; border-radius: .7rem; font-size: .74rem; font-weight: 800; color: #fff; background: rgb(11 19 36 / .72); backdrop-filter: blur(6px); }
    .hp-storm-top small { flex-basis: 100%; padding-left: .85rem; font-size: .64rem; font-weight: 700; color: #94a3b8; }
    .hp-storm-layers { position: absolute; right: .7rem; top: .7rem; display: flex; gap: .2rem; padding: .2rem; border-radius: .7rem; background: rgb(11 19 36 / .72); backdrop-filter: blur(6px); }
    .hp-storm-layers b { padding: .25rem .5rem; border-radius: .5rem; font-size: .66rem; font-weight: 800; color: #cbd5e1; }
    .hp-storm-layers b.is-on { color: #0b1324; background: #fff; }
    .hp-storm-time { position: absolute; left: .7rem; right: .7rem; bottom: .7rem; display: flex; align-items: center; gap: .6rem; padding: .45rem .6rem;
        border-radius: .9rem; background: rgb(11 19 36 / .78); backdrop-filter: blur(6px); }
    .hp-storm-play { flex: none; width: 1.8rem; height: 1.8rem; border-radius: 999px; display: grid; place-items: center; color: #0b1324; background: #f5c518; }
    .hp-storm-play svg { width: .9rem; height: .9rem; margin-left: 2px; }
    .hp-storm-bar { flex: 1; min-width: 0; }
    .hp-storm-days { display: flex; justify-content: space-between; font-size: .6rem; font-weight: 800; color: #94a3b8; }
    .hp-storm-days i { font-style: normal; }
    .hp-storm-rail { position: relative; display: block; margin-top: .3rem; height: 4px; border-radius: 4px; background: rgb(255 255 255 / .18); }
    .hp-storm-rail i { position: absolute; left: 0; top: 0; bottom: 0; width: calc(var(--p, 0) * 100%); border-radius: inherit; background: #f5c518; }
    .hp-storm-rail b { position: absolute; top: 50%; left: calc(var(--p, 0) * 100%); width: .8rem; height: .8rem; margin: -.4rem 0 0 -.4rem; border-radius: 999px;
        background: #fff; box-shadow: 0 0 0 3px rgb(245 197 24 / .5); }
    .hp-storm-now { flex: none; min-width: 4.6rem; text-align: right; font-size: .72rem; font-weight: 800; color: #fff; font-variant-numeric: tabular-nums; }
    @container (max-width: 420px) {
        .hp-storm-top small, .hp-storm-layers b:not(.is-on) { display: none; }
        .hp-storm-farm b { font-size: .62rem; }
    }
    .hp-storm-plan { padding: 1.1rem 1.15rem 1.2rem; border-radius: 1.3rem; color: #374151; background: #fff; box-shadow: 0 30px 60px -36px rgb(0 0 0 / .8); }
    .hp-storm-plan-hd { display: flex; align-items: center; gap: .6rem; }
    .hp-storm-plan-hd img { width: 2.6rem; height: 2.6rem; border-radius: 999px; object-fit: cover; box-shadow: 0 0 0 3px #fde68a; }
    .hp-storm-plan-hd b { display: block; font-family: var(--font-heading); font-size: 1rem; font-weight: 800; color: var(--hp-ink); }
    .hp-storm-plan-hd small { display: block; font-size: .74rem; color: #6b7280; }
    .hp-storm-say { margin-top: .8rem; padding: .6rem .75rem; border-radius: .8rem; font-size: .86rem; line-height: 1.55; background: #fff8e1; box-shadow: inset 0 0 0 1px #f6e3a0; }
    .hp-storm-steps { margin-top: .75rem; display: grid; gap: .45rem; counter-reset: st; }
    .hp-storm-steps li { position: relative; padding: .5rem .6rem .5rem 2.4rem; border-radius: .8rem; font-size: .84rem; line-height: 1.5; background: #f6f8f3; counter-increment: st;
        opacity: 0; translate: 0 8px; }
    .hp-storm-steps li::before { content: counter(st); position: absolute; left: .6rem; top: .55rem; width: 1.3rem; height: 1.3rem; border-radius: 999px; display: grid;
        place-items: center; font-size: .7rem; font-weight: 800; color: #fff; background: var(--hp-green); }
    .hp-storm-steps b { display: block; font-size: .7rem; font-weight: 800; letter-spacing: .05em; text-transform: uppercase; color: #6b7f5a; }
    .hp-storm-plan.is-visible .hp-storm-steps li { animation: hpRoomIn .5s var(--hp-ease) calc(.3s + var(--k) * .35s) forwards; }
    .hp-storm-done { margin-top: .75rem; display: inline-flex; align-items: center; gap: .4rem; padding: .3rem .65rem; border-radius: 999px; font-size: .74rem; font-weight: 800;
        color: #166534; background: #dcfce7; opacity: 0; transition: opacity .4s var(--hp-ease) 1.9s; }
    .hp-storm-done svg { width: .85rem; height: .85rem; }
    .hp-storm-plan.is-visible .hp-storm-done { opacity: 1; }
    html:not(.js) .hp-storm-steps li, html:not(.js) .hp-storm-done { opacity: 1; translate: none; }
    @media (prefers-reduced-motion: reduce) {
        .hp-storm-clouds, .hp-storm-farm i::after, .hp-storm-steps li { animation: none !important; }
        .hp-storm-steps li, .hp-storm-done { opacity: 1; translate: none; transition: none; }
    }

    /* The guides sit on a soft green, between the white crops and the gray questions. */
    .hp-guides-bg { background: radial-gradient(60% 50% at 90% 0%, #fdf6dc 0%, transparent 60%), linear-gradient(180deg, #f6faf1 0%, #eef5e6 100%); }

    /* ---- the community ---- */
    .hp-comm { background: radial-gradient(70% 60% at 10% 0%, #fdf6dc 0%, transparent 60%), linear-gradient(180deg, #fbfcf7 0%, #f1f7ea 100%); }
    .hp-comm-grid { margin-top: 3rem; display: grid; gap: 2rem; }
    @media (min-width: 1024px) { .hp-comm-grid { grid-template-columns: minmax(0, 1.15fr) minmax(0, .85fr); gap: 3rem; align-items: center; } }
    .hp-comm-feats { display: grid; gap: .8rem; grid-template-columns: repeat(auto-fill, minmax(15rem, 1fr)); }
    @media (min-width: 1024px) { .hp-comm-feats { grid-template-columns: 1fr; } }
    .hp-comm-stage { position: relative; display: grid; gap: 1rem; padding-bottom: 3.6rem; }
    @media (min-width: 640px) { .hp-comm-stage { grid-template-columns: minmax(0, 1.05fr) minmax(0, .95fr); align-items: start; } }
    .hp-cpost, .hp-crooms, .hp-clevel { border-radius: 1.3rem; background: #fff; border: 1px solid #e4ecdb; box-shadow: 0 30px 60px -44px rgb(20 33 12 / .6); }
    .hp-cpost { padding: .95rem; }
    .hp-cpost-hd { display: flex; align-items: center; gap: .55rem; flex-wrap: wrap; }
    .hp-cpost-hd b { display: block; font-size: .86rem; color: var(--hp-ink); }
    .hp-cpost-hd small { display: block; font-size: .72rem; color: #6b7280; }
    .hp-crank-chip { margin-left: auto; padding: .18rem .55rem; border-radius: 999px; font-size: .66rem; font-weight: 800; color: #14532d; background: #dcfce7; }
    .hp-cpost-t { margin-top: .6rem; font-size: .86rem; line-height: 1.5; color: #374151; }
    .hp-cpost-img { margin-top: .6rem; width: 100%; aspect-ratio: 16 / 9; object-fit: cover; border-radius: .9rem; }
    .hp-cpost-react { margin-top: .6rem; display: flex; gap: .45rem; }
    .hp-cpost-react span { display: inline-flex; align-items: center; gap: .3rem; padding: .25rem .6rem; border-radius: 999px; font-size: .76rem; font-weight: 800;
        color: #374151; background: #f3f4f6; transition: background-color .3s var(--hp-ease), color .3s var(--hp-ease), transform .3s var(--hp-ease); }
    .hp-cpost-react svg { width: 1rem; height: 1rem; }
    .hp-cpost-react b { font-variant-numeric: tabular-nums; }
    .hp-comm-stage.c2 .hp-cpost-react .is-thumb { color: #1d4ed8; background: #dbeafe; transform: scale(1.06); }
    .hp-comm-stage.c2 .hp-cpost-react .is-heart { color: #be123c; background: #ffe4e6; }
    .hp-cpost-reply { margin-top: .6rem; display: flex; align-items: flex-start; gap: .5rem; padding: .55rem .65rem; border-radius: .9rem; background: #fffbea;
        box-shadow: inset 0 0 0 1px #f6e3a0; opacity: 0; translate: 0 8px; transition: opacity .45s var(--hp-ease), translate .45s var(--hp-ease); }
    .hp-cpost-reply .hp-feed-av { width: 1.8rem; height: 1.8rem; }
    .hp-cpost-reply p { font-size: .8rem; line-height: 1.45; color: #374151; }
    .hp-cpost-reply b { color: var(--hp-deep); margin-right: .25rem; }
    .hp-comm-stage.c3 .hp-cpost-reply { opacity: 1; translate: none; }

    .hp-cside { display: grid; gap: 1rem; }
    .hp-crooms { padding: .9rem; }
    .hp-croom { display: flex; align-items: center; gap: .55rem; padding: .5rem .2rem; }
    .hp-croom + .hp-croom { border-top: 1px solid #f0f2ed; }
    .hp-croom-i { flex: none; width: 2.1rem; height: 2.1rem; border-radius: .7rem; display: grid; place-items: center; font-size: .66rem; font-weight: 800; color: #fff;
        background: hsl(var(--h) 45% 40%); }
    .hp-croom b { display: block; font-size: .82rem; color: var(--hp-ink); line-height: 1.25; }
    .hp-croom small { display: inline-flex; align-items: center; gap: .25rem; font-size: .7rem; color: #6b7280; }
    .hp-croom small svg { width: .8rem; height: .8rem; }
    .hp-croom-new { margin-left: auto; flex: none; padding: .15rem .5rem; border-radius: 999px; font-style: normal; font-size: .66rem; font-weight: 800; color: #fff;
        background: #dc2626; scale: 0; transition: scale .4s cubic-bezier(.34,1.56,.64,1); }
    .hp-comm-stage.c4 .hp-croom-new { scale: 1; }
    .hp-comm-stage.c4 .hp-croom:nth-child(3) .hp-croom-new { transition-delay: .2s; }
    .hp-comm-stage.c4 .hp-croom:nth-child(4) .hp-croom-new { transition-delay: .4s; }

    .hp-clevel { padding: .95rem; }
    .hp-clevel-hd { display: flex; align-items: center; gap: .6rem; }
    .hp-clevel-badge { flex: none; width: 2.4rem; height: 2.4rem; border-radius: .8rem; display: grid; place-items: center; color: #713f12; background: #fde68a;
        transition: transform .5s cubic-bezier(.34,1.56,.64,1), box-shadow .5s var(--hp-ease); }
    .hp-clevel-badge svg { width: 1.3rem; height: 1.3rem; }
    .hp-comm-stage.c5 .hp-clevel-badge { transform: rotate(-8deg) scale(1.12); box-shadow: 0 0 0 6px rgb(245 197 24 / .25); }
    .hp-clevel-hd small { display: block; font-size: .7rem; color: #6b7280; }
    .hp-clevel-hd b { position: relative; display: grid; font-family: var(--font-heading); font-size: 1.05rem; color: var(--hp-ink); }
    .hp-clevel-hd b span, .hp-clevel-title i { grid-area: 1 / 1; transition: opacity .35s var(--hp-ease), translate .35s var(--hp-ease); }
    [data-lv-to], .hp-clevel-title .is-new { opacity: 0; translate: 0 6px; }
    .hp-comm-stage.c5 [data-lv-from], .hp-comm-stage.c5 .hp-clevel-title .is-old { opacity: 0; translate: 0 -6px; transition-delay: 1.1s; }
    .hp-comm-stage.c5 [data-lv-to], .hp-comm-stage.c5 .hp-clevel-title .is-new { opacity: 1; translate: none; transition-delay: 1.1s; }
    .hp-clevel-title { margin-left: auto; display: grid; padding: .2rem .6rem; border-radius: 999px; font-size: .7rem; font-weight: 800; color: #14532d; background: #dcfce7; }
    .hp-clevel-title i { font-style: normal; }
    .hp-clevel-bar { margin-top: .7rem; display: block; height: .5rem; border-radius: 999px; overflow: hidden; background: #eef2ea; }
    .hp-clevel-bar i { display: block; height: 100%; width: 62%; border-radius: inherit; background: linear-gradient(90deg, var(--hp-green), var(--hp-sun));
        transition: width 1.1s cubic-bezier(.65,0,.35,1); }
    .hp-comm-stage.c5 .hp-clevel-bar i { width: 100%; }
    .hp-clevel-ladder { margin-top: .7rem; display: flex; gap: .3rem; }
    .hp-clevel-ladder i { flex: 1; height: .4rem; border-radius: 999px; background: #e5e7eb; transition: background-color .4s var(--hp-ease) 1.1s; }
    .hp-clevel-ladder .is-done { background: var(--hp-green); }
    .hp-comm-stage.c5 .hp-clevel-ladder .is-next { background: var(--hp-sun); }
    .hp-clevel-k { display: block; margin-top: .5rem; font-size: .72rem; color: #6b7280; }

    .hp-cconn { position: absolute; left: 50%; bottom: 0; translate: -50% 12px; width: min(23rem, 100%); display: flex; align-items: center; gap: .6rem; padding: .6rem .7rem;
        border-radius: 1rem; background: #fff; box-shadow: 0 24px 48px -26px rgb(20 33 12 / .6), 0 0 0 1px rgb(20 33 12 / .06);
        opacity: 0; transition: opacity .45s var(--hp-ease), translate .45s var(--hp-ease); }
    .hp-comm-stage.c6 .hp-cconn { opacity: 1; translate: -50% 0; }
    .hp-cconn-t { flex: 1; display: grid; font-size: .8rem; color: #374151; line-height: 1.35; }
    .hp-cconn-t i, .hp-cconn-btn i { grid-area: 1 / 1; font-style: normal; transition: opacity .35s var(--hp-ease); }
    .hp-cconn-t b { color: var(--hp-ink); }
    .hp-cconn-btn { flex: none; display: grid; place-items: center; min-width: 4.6rem; height: 2rem; padding: 0 .7rem; border-radius: 999px; font-size: .76rem; font-weight: 800;
        color: #fff; background: var(--hp-green); transition: background-color .35s var(--hp-ease); }
    .hp-cconn-btn svg { width: 14px; height: 14px; stroke-width: 3.2; }
    .hp-cconn .is-yes { opacity: 0; }
    .hp-comm-stage.c7 .hp-cconn .is-ask { opacity: 0; }
    .hp-comm-stage.c7 .hp-cconn .is-yes { opacity: 1; }
    .hp-comm-stage.c7 .hp-cconn-btn { background: #16a34a; }
    html:not(.js) .hp-cpost-reply, html:not(.js) .hp-cconn { opacity: 1; translate: none; }
    html:not(.js) .hp-cconn { translate: -50% 0; }

    /* ---- Anee knows your farm ---- */
    /* overflow-x clip: the turning ring of sources is a square whose corners
       swing past a phone's edge as it turns. */
    .hp-ak { overflow-x: clip; background: radial-gradient(60% 50% at 85% 10%, #fff6d6 0%, transparent 60%), linear-gradient(180deg, #ffffff 0%, #f4f9ee 100%); }
    .hp-ak-grid { margin-top: 3rem; display: grid; gap: 2.5rem; }
    .hp-ak-grid > * { min-width: 0; }
    @media (min-width: 1024px) { .hp-ak-grid { grid-template-columns: minmax(0, .9fr) minmax(0, 1.1fr); gap: 3.5rem; align-items: center; } }
    /* The orbit: Anee in the middle, what she checks going round her. */
    .hp-ak-orbit { position: relative; width: min(30rem, 100%); aspect-ratio: 1; margin: 0 auto; }
    .hp-ak-ring { position: absolute; inset: 9%; border-radius: 999px; border: 1.5px dashed #c8dcb2; }
    .hp-ak-ring.is-2 { inset: 30%; border-style: solid; border-color: #e1edd2; background: radial-gradient(circle, rgb(245 197 24 / .12), transparent 70%); }
    .hp-ak-core { position: absolute; left: 50%; top: 50%; width: 32%; aspect-ratio: 1; translate: -50% -50%; z-index: 2; border-radius: 999px; display: grid; place-items: center;
        background: #fff; box-shadow: 0 0 0 6px rgb(245 197 24 / .35), 0 30px 60px -30px rgb(20 33 12 / .7); }
    .hp-ak-core video { width: 86%; height: 86%; border-radius: 999px; object-fit: cover; background: #e4efd4; }
    .hp-ak-core i { position: absolute; bottom: -1.1rem; left: 50%; translate: -50% 0; white-space: nowrap; padding: .25rem .7rem; border-radius: 999px; font-style: normal;
        font-size: .72rem; font-weight: 800; color: var(--hp-ink); background: var(--hp-sun); box-shadow: 0 8px 18px -10px rgb(0 0 0 / .5); }
    .hp-ak-core::after { content: ''; position: absolute; inset: -6px; border-radius: inherit; animation: hpAkPulse 2.4s ease-out infinite; }
    @keyframes hpAkPulse { 0% { box-shadow: 0 0 0 0 rgb(245 197 24 / .5); } 80%, 100% { box-shadow: 0 0 0 1.4rem rgb(245 197 24 / 0); } }
    .hp-ak-spin { position: absolute; inset: 0; animation: hpAkSpin 48s linear infinite; }
    /* Each source sits on the ring (41 percent of the orbit's width from
       the middle) and its label stays upright while the ring turns. */
    .hp-ak-orbit { container-type: inline-size; }
    .hp-ak-src { position: absolute; left: 50%; top: 50%; width: 0; height: 0; --a: calc(var(--n) * 45deg);
        transform: rotate(var(--a)) translateY(-41cqw) rotate(calc(-1 * var(--a))); }
    .hp-ak-in { position: absolute; left: 0; top: 0; translate: -50% -50%; display: inline-flex; align-items: center; gap: .35rem; white-space: nowrap;
        animation: hpAkCounter 48s linear infinite; }
    .hp-ak-in > svg { flex: none; width: 2rem; height: 2rem; padding: .4rem; border-radius: .7rem; color: #fff; background: var(--hp-green); box-shadow: 0 10px 18px -10px rgb(47 82 25 / .9); }
    .hp-ak-in > b { font-size: .74rem; font-weight: 800; color: var(--hp-ink); padding: .2rem .5rem; border-radius: 999px; background: rgb(255 255 255 / .92);
        box-shadow: 0 6px 14px -10px rgb(0 0 0 / .45); }
    @keyframes hpAkSpin { to { transform: rotate(360deg); } }
    @keyframes hpAkCounter { to { transform: rotate(-360deg); } }
    .hp-ak-names { display: none; }
    @media (max-width: 639.98px) {
        .hp-ak-in > b { display: none; }
        .hp-ak-names { display: flex; flex-wrap: wrap; justify-content: center; gap: .35rem; margin-top: 1.6rem; }
        .hp-ak-names span { padding: .25rem .6rem; border-radius: 999px; font-size: .72rem; font-weight: 800; color: var(--hp-deep); background: #fff; border: 1px solid #dfe8d3; }
    }

    .hp-ak-tools { border-radius: 1.6rem; background: #fff; border: 1px solid #e1ead6; box-shadow: 0 40px 80px -56px rgb(20 33 12 / .65); overflow: hidden; }
    /* The tools take turns: the card cross fades from one to the next, and
       the dots under it show which is on and how long until the next. */
    .hp-ak-ph { display: flex; align-items: center; gap: .65rem; }
    .hp-ak-ph h3 { font-family: var(--font-heading); font-size: 1.15rem; font-weight: 800; line-height: 1.25; color: var(--hp-ink); }
    .hp-ak-num { margin-left: auto; flex: none; font-size: .72rem; font-weight: 800; color: #6b7f5a; padding: .2rem .55rem; border-radius: 999px; background: #f1f6ea; }
    .hp-ak-ti { flex: none; width: 2.2rem; height: 2.2rem; border-radius: .75rem; display: grid; place-items: center; color: #fff; background: var(--hp-green);
        box-shadow: 0 10px 18px -12px rgb(47 82 25 / .9); }
    .hp-ak-ti svg { width: 1.2rem; height: 1.2rem; }
    .hp-ak-picks { display: flex; justify-content: center; gap: .45rem; padding: 0 1rem 1.15rem; }
    .hp-ak-pick { position: relative; width: .6rem; height: .6rem; padding: 0; border: 0; border-radius: 999px; overflow: hidden; cursor: pointer; background: #dbe7cd;
        transition: width .4s var(--hp-ease), background-color .4s var(--hp-ease); }
    .hp-ak-pick::before { content: ''; position: absolute; inset: -.6rem -.25rem; }
    .hp-ak-pick:hover { background: #c4d9ab; }
    .hp-ak-pick.is-on { width: 2.2rem; background: #dbe7cd; }
    .hp-ak-pick i { position: absolute; inset: 0; border-radius: inherit; transform-origin: left; transform: scaleX(0); background: var(--hp-green); }
    .hp-ak-pick.is-on.is-held i { transform: scaleX(1); }
    .hp-ak-pick.is-on.is-timing i { animation: hpBar var(--ak-dwell, 5s) linear forwards; }
    .hp-ak-pick:focus-visible { outline: 2px solid var(--hp-green); outline-offset: 3px; }
    .hp-ak-panes { display: grid; }
    .hp-ak-pane { grid-area: 1 / 1; padding: 1.3rem 1.3rem 1.1rem; opacity: 0; visibility: hidden;
        transition: opacity .6s var(--hp-ease), visibility .6s; }
    .hp-ak-pane.is-on { opacity: 1; visibility: visible; }
    .hp-ak-desc { margin-top: .75rem; font-size: .95rem; line-height: 1.6; color: #374151; }
    .hp-ak-show { margin-top: 1rem; min-height: 15.5rem; padding: 1rem; border-radius: 1.1rem; background: #f6f8f3; }
    .hp-ak-k { font-size: .7rem; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; color: #6b7f5a; }
    .hp-ak-note { margin-top: .9rem; display: flex; align-items: flex-start; gap: .5rem; font-size: .84rem; line-height: 1.5; color: #374151; padding: .6rem .7rem;
        border-radius: .9rem; background: #fffbea; box-shadow: inset 0 0 0 1px #f6e3a0; opacity: 0; transition: opacity .45s var(--hp-ease) 1.6s; }
    .hp-ak-note img { width: 1.6rem; height: 1.6rem; border-radius: 999px; flex: none; }
    .hp-ak-pane.is-on .hp-ak-note { opacity: 1; }
    .hp-ak-dot { flex: none; width: .55rem; height: .55rem; margin-top: .4rem; border-radius: 999px; background: var(--hp-sun); }

    .hp-ak-track { position: relative; margin-top: 1rem; display: grid; grid-template-columns: repeat(5, 1fr); gap: 3px; padding: 1.9rem 0 1.9rem; }
    .hp-ak-stage { padding: .55rem .2rem; border-radius: .5rem; text-align: center; font-size: .66rem; font-weight: 800; color: #3d5a24; background: #e4efd6; }
    .hp-ak-stage:nth-child(2) { background: #d3e7bd; }
    .hp-ak-stage:nth-child(3) { background: #c2dda5; }
    .hp-ak-mark { position: absolute; top: 0; font-style: normal; translate: -50% 0; transition: left 1.2s cubic-bezier(.65,0,.35,1) .6s; }
    .hp-ak-mark b { display: block; padding: .15rem .5rem; border-radius: 999px; font-size: .64rem; font-weight: 800; white-space: nowrap; }
    .hp-ak-mark::after { content: ''; position: absolute; left: 50%; top: 100%; width: 2px; height: 1.9rem; translate: -50% 0; }
    .hp-ak-mark.is-cal { left: 30%; top: auto; bottom: 0; }
    .hp-ak-mark.is-cal::after { top: auto; bottom: 100%; }
    .hp-ak-mark.is-cal b { color: #6b7280; background: #e5e7eb; }
    .hp-ak-mark.is-cal::after { background: #9ca3af; }
    .hp-ak-mark.is-real { left: 30%; z-index: 1; }
    .hp-ak-mark.is-real b { color: var(--hp-ink); background: var(--hp-sun); }
    .hp-ak-mark.is-real::after { background: var(--hp-sun); }
    .hp-ak-pane.is-on .hp-ak-mark.is-real { left: 52%; }

    .hp-ak-list { display: grid; gap: .5rem; }
    .hp-ak-row, .hp-ak-rep, .hp-ak-cmprow { opacity: 0; translate: 0 8px; }
    .hp-ak-pane.is-on .hp-ak-row, .hp-ak-pane.is-on .hp-ak-rep, .hp-ak-pane.is-on .hp-ak-cmprow { animation: hpRoomIn .45s var(--hp-ease) calc(.2s + var(--k) * .4s) forwards; }
    .hp-ak-row { display: flex; align-items: center; justify-content: space-between; gap: .6rem; padding: .65rem .8rem; border-radius: .9rem; background: #fff;
        box-shadow: 0 6px 16px -14px rgb(0 0 0 / .5); font-size: .84rem; }
    .hp-ak-row b { color: var(--hp-ink); }
    .hp-ak-row span, .hp-ak-rep span { padding: .2rem .6rem; border-radius: 999px; font-size: .72rem; font-weight: 800; text-align: right; }
    .is-good { color: #14532d; background: #dcfce7; }
    .is-warn { color: #92400e; background: #fef3c7; }
    .is-bad { color: #991b1b; background: #fee2e2; }
    .is-next { color: #1e3a8a; background: #dbeafe; }
    .hp-ak-report { display: grid; gap: .6rem; }
    .hp-ak-rep { display: grid; gap: .35rem; padding: .7rem .8rem; border-radius: .9rem; background: #fff; box-shadow: 0 6px 16px -14px rgb(0 0 0 / .5); }
    .hp-ak-rep span { justify-self: start; }
    .hp-ak-rep i { display: block; height: .45rem; border-radius: 999px; background: #e5e7eb; }
    .hp-ak-rep i:last-child { width: 70%; }

    .hp-ak-cmp { display: grid; gap: .7rem; }
    .hp-ak-cmprow { display: grid; grid-template-columns: 7.5rem 1fr; gap: .25rem .7rem; align-items: center; font-size: .8rem; }
    .hp-ak-cmprow b { grid-row: span 2; color: var(--hp-ink); }
    .hp-ak-bar { display: block; height: .55rem; border-radius: 999px; background: #e5e7eb; overflow: hidden; }
    .hp-ak-bar i { display: block; height: 100%; width: 0; border-radius: inherit; transition: width 1s cubic-bezier(.65,0,.35,1); transition-delay: calc(.4s + var(--k) * .4s); }
    .hp-ak-bar.is-a i { background: #9ca3af; }
    .hp-ak-bar.is-b i { background: var(--hp-green); }
    .hp-ak-pane.is-on .hp-ak-bar i { width: var(--w); }
    .hp-ak-legend { display: flex; gap: 1rem; font-size: .72rem; font-weight: 800; color: #6b7280; }
    .hp-ak-legend span::before { content: ''; display: inline-block; width: .6rem; height: .6rem; margin-right: .3rem; border-radius: 2px; vertical-align: -.05rem; }
    .hp-ak-legend .is-a::before { background: #9ca3af; }
    .hp-ak-legend .is-b::before { background: var(--hp-green); }

    .hp-ak-chat { display: grid; gap: .7rem; }
    .hp-ak-chip { justify-self: end; display: inline-flex; align-items: center; gap: .35rem; padding: .35rem .7rem; border-radius: .8rem; font-size: .74rem; font-weight: 800;
        color: var(--hp-deep); background: #fff; box-shadow: inset 0 0 0 1px #cfe3b8; opacity: 0; translate: 0 6px; transition: opacity .4s var(--hp-ease) .2s, translate .4s var(--hp-ease) .2s; }
    .hp-ak-chip svg { width: .9rem; height: .9rem; }
    .hp-ak-q { justify-self: end; max-width: 85%; padding: .6rem .8rem; border-radius: 1rem; border-bottom-right-radius: .3rem; font-size: .84rem; color: #fff; background: var(--hp-green);
        opacity: 0; translate: 0 6px; transition: opacity .4s var(--hp-ease) .8s, translate .4s var(--hp-ease) .8s; }
    .hp-ak-a { display: flex; align-items: flex-start; gap: .5rem; opacity: 0; translate: 0 6px; transition: opacity .4s var(--hp-ease) 1.8s, translate .4s var(--hp-ease) 1.8s; }
    .hp-ak-a img { width: 1.9rem; height: 1.9rem; border-radius: 999px; flex: none; }
    .hp-ak-a span { padding: .6rem .8rem; border-radius: 1rem; border-bottom-left-radius: .3rem; font-size: .84rem; line-height: 1.5; color: #374151; background: #fff;
        box-shadow: 0 6px 16px -14px rgb(0 0 0 / .5); }
    .hp-ak-pane.is-on .hp-ak-chip, .hp-ak-pane.is-on .hp-ak-q, .hp-ak-pane.is-on .hp-ak-a { opacity: 1; translate: none; }

    /* ---- versus ---- */
    .hp-vs-jump { margin-top: 2.2rem; display: flex; flex-wrap: wrap; justify-content: center; gap: .5rem; }
    .hp-vs-chip { display: inline-flex; align-items: center; gap: .45rem; padding: .42rem .5rem .42rem .85rem; border-radius: 999px;
        font-size: .82rem; font-weight: 800; color: var(--hp-deep); text-decoration: none; background: #fff; border: 1px solid #dfe8d3;
        transition: border-color .28s var(--hp-ease), transform .28s var(--hp-ease), box-shadow .28s var(--hp-ease); }
    .hp-vs-chip:hover { border-color: var(--hp-green); transform: translateY(-2px); box-shadow: 0 10px 20px -14px rgb(47 82 25 / .6); }
    .hp-vs-chip b { min-width: 1.5rem; padding: .08rem .4rem; border-radius: 999px; text-align: center; font-size: .72rem; color: #fff; background: var(--hp-green); }
    /* clip, not hidden: hidden would make the table its own scroller and
       the sticky column titles would stop sticking. */
    .hp-vs { margin-top: 1.6rem; border-radius: 1.6rem; overflow: clip; background: #fff; border: 1px solid #e5e7eb;
        box-shadow: 0 30px 60px -48px rgb(20 33 12 / .6); }
    .hp-vs-head { display: none; }
    .hp-vs-group { scroll-margin-top: 6rem; }
    .hp-vs-group + .hp-vs-group { border-top: 1px solid #e3eada; }
    .hp-vs-gt { display: flex; align-items: center; gap: .65rem; padding: .85rem 1.2rem; font-family: var(--font-heading); font-size: 1.02rem;
        font-weight: 800; color: var(--hp-ink); background: linear-gradient(90deg, #f3f8ec, #fbfcf9); }
    .hp-vs-gt small { margin-left: auto; padding: .1rem .55rem; border-radius: 999px; font-family: inherit; font-size: .72rem; color: var(--hp-deep); background: #e1eed2; }
    .hp-vs-gi { flex: none; width: 2.1rem; height: 2.1rem; border-radius: .7rem; display: grid; place-items: center; color: #fff; background: var(--hp-green);
        box-shadow: 0 8px 16px -10px rgb(47 82 25 / .9); }
    .hp-vs-gi svg { width: 1.15rem; height: 1.15rem; }
    .hp-vs-row { display: grid; gap: .45rem; padding: .95rem 1.2rem; border-top: 1px solid #f0f2ed; transition: background-color .28s var(--hp-ease); }
    .hp-vs-dim { font-family: var(--font-heading); font-weight: 800; color: var(--hp-ink); }
    .hp-vs-old, .hp-vs-new { display: flex; gap: .6rem; align-items: flex-start; font-size: .9rem; line-height: 1.5; }
    .hp-vs-old { color: #6b7280; }
    .hp-vs-new { color: #1f2937; font-weight: 600; padding: .6rem .7rem; border-radius: .9rem; background: #f3f8ec; }
    .hp-vs-old > span, .hp-vs-new > span { flex: none; width: 1.35rem; height: 1.35rem; border-radius: 999px; display: grid; place-items: center; margin-top: .05rem; }
    .hp-vs-old > span { color: #9ca3af; background: #f3f4f6; }
    .hp-vs-new > span { color: #fff; background: var(--hp-green); }
    .hp-vs-old svg, .hp-vs-new > span svg { width: .75rem; height: .75rem; }
    /* Said for screen readers; the column titles say it for the eye. */
    .hp-vs-label { position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0 0 0 0); white-space: nowrap; }
    @media (min-width: 768px) {
        .hp-vs-head { display: grid; grid-template-columns: 1fr 1.1fr 1.25fr; position: sticky; top: 65px; z-index: 3;
            box-shadow: 0 10px 20px -18px rgb(20 33 12 / .5); }
        .hp-vs-head span { padding: 1rem 1.4rem; font-family: var(--font-heading); font-weight: 800; }
        .hp-vs-head .is-n { color: var(--hp-deep); background: #fff; font-size: .9rem; display: flex; align-items: center; }
        .hp-vs-head .is-old { color: #6b7280; background: #f9fafb; }
        .hp-vs-head .is-new { display: flex; align-items: center; gap: .5rem; color: #fff; background: var(--hp-deep); }
        .hp-vs-head .is-new img { width: 1.3rem; height: 1.3rem; object-fit: contain; }
        .hp-vs-group { scroll-margin-top: 8.5rem; }
        .hp-vs-gt { padding: .8rem 1.4rem; }
        .hp-vs-row { grid-template-columns: 1fr 1.1fr 1.25fr; gap: 0; padding: 0; align-items: stretch; }
        .hp-vs-row > * { padding: .9rem 1.4rem; }
        .hp-vs-row:hover { background: #fcfdfb; }
        .hp-vs-dim { display: flex; align-items: center; font-size: .95rem; }
        .hp-vs-old { border-left: 1px solid #f0f2ed; }
        .hp-vs-new { border-radius: 0; background: #f6faf1; }
        .hp-vs-row:hover .hp-vs-new { background: #eff7e6; }
    }
    @media (max-width: 767.98px) {
        .hp-vs-gt { padding: .75rem 1rem; }
        .hp-vs-row { gap: .35rem; padding: .8rem 1rem; }
        .hp-vs-old { font-size: .84rem; }
        .hp-vs-new { font-size: .88rem; padding: .5rem .6rem; }
    }
    @media (min-width: 1024px) {
        .hp-vs-head { top: 81px; }
        .hp-vs-group { scroll-margin-top: 9.5rem; }
    }
    .hp-vs-group.is-visible .hp-vs-new > span { animation: hpPop .5s var(--hp-ease) backwards; animation-delay: calc(var(--i, 0) * 80ms + .15s); }
    @keyframes hpPop { from { transform: scale(0); } 60% { transform: scale(1.2); } to { transform: scale(1); } }

    /* ---- pricing toggle, questions ---- */
    .hp-q { border-radius: 1.1rem; background: #fff; border: 1px solid #e5e7eb; overflow: hidden; transition: border-color .28s var(--hp-ease), box-shadow .28s var(--hp-ease); }
    .hp-q.is-open { border-color: #b9d69a; box-shadow: 0 16px 34px -26px rgb(47 82 25 / .55); }
    .hp-q-btn { width: 100%; display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: 1rem 1.25rem; text-align: left;
        font-family: var(--font-heading); font-weight: 800; color: var(--hp-ink); background: none; border: 0; cursor: pointer; }
    .hp-q-btn svg { flex: none; width: 1.25rem; height: 1.25rem; color: var(--hp-green); transition: transform .28s var(--hp-ease); }
    .hp-q.is-open .hp-q-btn svg { transform: rotate(45deg); }
    .hc-card { display: flex; flex-direction: column; border-radius: 1.4rem; overflow: hidden; background: #fff; border: 1px solid #e5ebdf;
        transition: transform .28s var(--hp-ease), box-shadow .28s var(--hp-ease), border-color .28s var(--hp-ease); }
    .hc-pic { position: relative; aspect-ratio: 16 / 7; overflow: hidden; }
    .hc-pic img { width: 100%; height: 100%; object-fit: cover; transition: scale .6s var(--hp-ease); }
    .hc-card:hover .hc-pic img { scale: 1.05; }
    .hc-pic::after { content: ''; position: absolute; inset: 0; background: linear-gradient(180deg, transparent 55%, rgb(20 33 12 / .35)); }
    .hc-pic .hc-ico { position: absolute; left: 1.2rem; bottom: .9rem; z-index: 1; box-shadow: 0 10px 20px -10px rgb(0 0 0 / .6), inset 0 0 0 1px hsl(var(--h) 50% 80%); }
    .hc-body { min-width: 0; padding: 1.15rem 1.4rem 1.4rem; }
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
    /* The guide shelves: two by two, each a card with its own tint. */
    .hg-shelves { margin-top: 2.8rem; display: grid; gap: 1.4rem; }
    @media (min-width: 768px) { .hg-shelves { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 1.6rem; } }
    .hg-shelf { position: relative; display: flex; flex-direction: column; gap: 1rem; padding: 1.15rem; border-radius: 1.6rem; background: #fff;
        border: 1px solid hsl(var(--h) 30% 88%); box-shadow: 0 30px 60px -46px rgb(20 33 12 / .55);
        transition: transform .28s var(--hp-ease), box-shadow .28s var(--hp-ease); }
    .hg-shelf::before { content: ''; position: absolute; left: 1.4rem; right: 1.4rem; top: -1px; height: 3px; border-radius: 0 0 3px 3px; background: hsl(var(--h) 55% 45%); }
    .hg-shelf:hover { transform: translateY(-3px); box-shadow: 0 36px 70px -44px rgb(20 33 12 / .6); }
    .hg-head { display: flex; align-items: center; gap: .75rem; }
    .hg-ico { flex: none; width: 2.6rem; height: 2.6rem; border-radius: .9rem; display: grid; place-items: center; color: hsl(var(--h) 60% 26%);
        background: linear-gradient(145deg, hsl(var(--h) 60% 95%), hsl(var(--h) 50% 87%)); box-shadow: inset 0 0 0 1px hsl(var(--h) 40% 80%); }
    .hg-ico svg { width: 1.35rem; height: 1.35rem; }
    .hg-name { min-width: 0; flex: 1; }
    .hg-name b { display: block; font-family: var(--font-heading); font-size: 1.15rem; font-weight: 800; color: var(--hp-ink); line-height: 1.2; }
    .hg-name small { display: block; margin-top: .1rem; font-size: .8rem; color: #6b7280; line-height: 1.35; }
    .hg-count { flex: none; padding: .25rem .6rem; border-radius: 999px; font-size: .72rem; font-weight: 800; color: hsl(var(--h) 55% 26%); background: hsl(var(--h) 55% 94%); }
    .hg-lead { position: relative; display: block; overflow: hidden; border-radius: 1.15rem; aspect-ratio: 16 / 9; background: hsl(var(--h) 30% 88%); text-decoration: none; isolation: isolate; }
    .hg-lead img { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; transition: transform .6s var(--hp-ease); }
    .hg-lead::after { content: ''; position: absolute; inset: 0; z-index: 1; background: linear-gradient(180deg, transparent 30%, rgb(10 18 6 / .25) 50%, rgb(10 18 6 / .86) 100%); }
    .hg-lead:hover img { transform: scale(1.05); }
    .hg-lead-in { position: absolute; left: 0; right: 0; bottom: 0; z-index: 2; display: flex; flex-direction: column; align-items: flex-start; gap: .4rem; padding: 1rem 1.1rem 1.05rem; }
    .hg-cat { padding: .2rem .55rem; border-radius: 999px; font-size: .66rem; font-weight: 800; letter-spacing: .06em; text-transform: uppercase; color: hsl(var(--h) 60% 20%);
        background: hsl(var(--h) 70% 90% / .95); }
    .hg-lead-in b { font-family: var(--font-heading); font-size: clamp(1.05rem, 1.7vw, 1.3rem); font-weight: 800; line-height: 1.25; color: #fff; text-wrap: balance;
        text-shadow: 0 2px 12px rgb(0 0 0 / .4); }
    .hg-read { display: inline-flex; align-items: center; gap: .35rem; font-size: .8rem; font-weight: 800; color: var(--hp-sun); }
    .hg-read svg { width: .9rem; height: .9rem; transition: transform .28s var(--hp-ease); }
    .hg-lead:hover .hg-read svg { transform: translateX(3px); }
    .hg-rows { display: grid; grid-template-columns: minmax(0, 1fr); gap: .15rem; }
    .hg-row { display: flex; align-items: center; gap: .8rem; padding: .45rem .5rem; border-radius: .9rem; text-decoration: none;
        transition: background-color .28s var(--hp-ease); }
    .hg-row:hover { background: hsl(var(--h) 50% 96%); }
    .hg-row img { flex: none; width: 4rem; height: 3rem; border-radius: .65rem; object-fit: cover; background: hsl(var(--h) 30% 90%); }
    .hg-row span { flex: 1; min-width: 0; }
    .hg-row small { display: block; font-size: .66rem; font-weight: 800; letter-spacing: .06em; text-transform: uppercase; color: hsl(var(--h) 45% 38%); }
    .hg-row b { display: block; font-size: .92rem; font-weight: 700; line-height: 1.3; color: var(--hp-ink); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .hg-row svg { flex: none; width: 1rem; height: 1rem; color: hsl(var(--h) 40% 55%); transition: transform .28s var(--hp-ease), color .28s var(--hp-ease); }
    .hg-row:hover svg { transform: translateX(3px); color: hsl(var(--h) 55% 32%); }
    .hg-all { margin-top: auto; display: flex; align-items: center; justify-content: center; gap: .45rem; padding: .75rem 1rem; border-radius: 1rem;
        font-size: .9rem; font-weight: 800; color: hsl(var(--h) 60% 22%); text-decoration: none; background: hsl(var(--h) 55% 95%);
        box-shadow: inset 0 0 0 1px hsl(var(--h) 40% 86%); transition: background-color .28s var(--hp-ease); }
    .hg-all:hover { background: hsl(var(--h) 55% 90%); }
    .hg-all svg { width: 1rem; height: 1rem; transition: transform .28s var(--hp-ease); }
    .hg-all:hover svg { transform: translateX(3px); }
    @media (max-width: 479.98px) {
        .hg-shelf { padding: .9rem; border-radius: 1.3rem; }
        .hg-row b { white-space: normal; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; }
    }

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
    .hp-sum-how { margin-top: .6rem; display: grid; }
    /* A thin line between the items, as in the two cards beside it. */
    .hp-sum-how li { position: relative; padding: .7rem 0 .7rem 1.6rem; font-size: .92rem; line-height: 1.55; color: #e4f0d6; }
    .hp-sum-how li + li { border-top: 1px solid rgb(255 255 255 / .16); }
    .hp-sum-how li:last-child { padding-bottom: 0; }
    .hp-sum-how li::before { content: ''; position: absolute; left: 0; top: calc(.7rem + .2em); width: 1.05rem; height: 1.05rem; border-radius: 999px;
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
        .hp-biz-b, .hp-final-face, .hp-live, .hp-read i, .hp-sum-ico, .hp-sum-ico svg, .hp-vs-group.is-visible .hp-vs-new > span { animation: none !important; }
        .hp-st-tab.is-on.is-timing .hp-st-bar i { animation: none; }
        .hp-phone.is-hero, .hp-st-pane, .hp-tool, .hp-msg, .hp-read, .hp-film, .hp-film-tag, .hp-modal, .hp-modal-box, .hp-sticky,
        .hp-go, .hp-alt, .hp-prec-row, .hp-prec-rights li, .hp-prec-old, .hp-prec-card, .hp-prec-checks li, .hp-prec-bar i, .hp-prec-on, .hp-st-tab, .hp-gain, .hp-q, .hc-card, .hg-shelf, .hg-lead img, .hg-row, .hg-row svg, .hg-all, .hg-all svg, .hg-read svg, .hq-body, .hp-topic { transition: none !important; }
        .hp-chat .hp-msg, .hp-chat .hp-read { opacity: 1; transform: none; }
        .hp-tvs-race i::before, .hp-tvs-old > i, .hp-tvs-new > i { animation: none !important; }
        .hp-tvs-race.is-slow i::before { transform: scaleX(.9); }
        .hp-tvs-race.is-fast i::before { transform: scaleX(1); }
        .hp-anee-reads li, .hp-anee-ri, .hp-anee-out { transition: none !important; }
        .hp-heart svg, .hp-heart::after { animation: none !important; }
        .hp-feed-item, .hp-feed-sync i, .hp-team-card { transition: none !important; }
        .hp-room-pane, .hp-room-tab, .hp-task-box, .hp-task-t { transition: none !important; }
        .hp-ak-spin, .hp-ak-in, .hp-ak-core::after, .hp-ak-pick i, .hp-ak-row, .hp-ak-rep, .hp-ak-cmprow { animation: none !important; }
        .hp-ak-row, .hp-ak-rep, .hp-ak-cmprow { opacity: 1; translate: none; }
        .hp-ak-pane, .hp-ak-pick, .hp-ak-note, .hp-ak-mark, .hp-ak-bar i, .hp-ak-chip, .hp-ak-q, .hp-ak-a { transition: none !important; }
        .hp-cpost-react span, .hp-cpost-reply, .hp-croom-new, .hp-clevel-badge, .hp-clevel-hd b span, .hp-clevel-title i, .hp-clevel-bar i,
        .hp-clevel-ladder i, .hp-cconn, .hp-cconn-t i, .hp-cconn-btn i, .hp-cconn-btn { transition: none !important; }
        .hp-acc, .hp-rc, .hp-act, .hp-loc-pin, .hp-loc-map, .hp-cam img, .hp-call-av, .hp-wb-ink, .hp-wb-hand text, .hp-room-bar { animation: none !important; }
        .hp-acc, .hp-rc, .hp-task { opacity: 1; translate: none; }
        .hp-wb-ink { stroke-dashoffset: 0; }
        .hp-feed-wave i, .hp-sat-stars, .hp-sat-craft, .hp-sat-blink, .hp-sat-beam, .hp-sat-wave, .hp-sat-cloud, .hp-sat-map.is-health, .hp-sat-scan { animation: none !important; }
        .hp-sat-map.is-health { -webkit-clip-path: none; clip-path: none; }
        .hp-sat-wave { opacity: .5; scale: .9; }
        .hp-biz-u, .hp-biz-ui, .hp-biz-ok, .hp-biz-swap span { transition: none !important; }
        .hp-mark-line { -webkit-clip-path: none; clip-path: none; }
        .hp-tick, .hp-tick path, .hp-tick::after { animation: none !important; }
        .hp-prec-spin, .hp-prec-line b, .hp-prec-chk, .hp-prec-chk path, .hp-prec-old-x { animation: none !important; }
        .hp-prec-line b { opacity: 0; }
        html.js .hp-prec-vis .hp-prec-old, html.js .hp-prec-vis .hp-prec-card { opacity: 1 !important; translate: none !important; }
        html.js .hp-prec-checks li, html.js .hp-prec-on { opacity: 1 !important; translate: none !important; }
        html.js .hp-prec-chk { scale: 1 !important; }
        html.js .hp-prec-chk path { stroke-dashoffset: 0 !important; }
        html.js .hp-prec-bar i { width: 100% !important; }
        .hp-prec-old-x { -webkit-clip-path: none; clip-path: none; }
        .hp-tick { transform: none; }
        .hp-tick path { stroke-dashoffset: 0; }
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
        // Long enough to read the first tool's explanation before the next step.
        const DWELL = 12000;
        let at = 0, timer = null, live = false, held = false;
        const pickTool = (btn) => {
            if (!btn) return;
            const pane = btn.closest('.hp-st-pane');
            pane.querySelectorAll('.hp-tool').forEach((b) => {
                const on = b === btn;
                b.classList.toggle('is-on', on);
                b.setAttribute('aria-pressed', on ? 'true' : 'false');
            });
            pane.querySelectorAll('.hp-info').forEach((x) => x.classList.toggle('is-on', x.dataset.info === btn.dataset.tool));
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
            // On a phone the explanation sits under the tools (and the film
            // under it): bring the explanation into view if it is not.
            const info = b.closest('.hp-st-pane').querySelector('.hp-info.is-on');
            if (info && innerWidth < 768) {
                const r = info.getBoundingClientRect();
                if (r.top > innerHeight * .7 || r.bottom < 80) info.scrollIntoView({ behavior: reduce ? 'auto' : 'smooth', block: 'center' });
            }
        }));
        seen(steps.querySelector('.hp-st'), (on) => {
            live = on;
            video.dataset.live = on ? '1' : '0';
            if (on) { play(video); schedule(); } else { video.pause(); clearTimeout(timer); tabs[at].classList.remove('is-timing'); }
        }, { threshold: 0.3 });
    }

    /* Anee's answer plays on a loop while the chat is on screen: the
       question, what she reads one line at a time, her answer, a pause to
       read it, then it fades and starts again. */
    const chat = document.querySelector('[data-chat]');
    if (chat) {
        const reads = [...chat.querySelectorAll('.hp-read')];
        let timers = [], live = false;
        const at = (ms, fn) => timers.push(setTimeout(fn, ms));
        const stop = () => { timers.forEach(clearTimeout); timers = []; };
        const run = () => {
            stop();
            chat.classList.remove('s1', 's5', 'is-done', 'is-fading');
            reads.forEach((r) => r.classList.remove('is-shown', 'is-done'));
            if (reduce) { chat.classList.add('s1', 's5', 'is-done'); reads.forEach((r) => r.classList.add('is-shown', 'is-done')); return; }
            at(250, () => chat.classList.add('s1'));
            reads.forEach((r, i) => {
                at(1200 + i * 850, () => r.classList.add('is-shown'));
                at(1200 + i * 850 + 800, () => r.classList.add('is-done'));
            });
            const end = 1200 + reads.length * 850 + 150;
            at(end, () => chat.classList.add('s5'));
            at(end + 600, () => chat.classList.add('is-done'));
            at(end + 8500, () => chat.classList.add('is-fading'));
            at(end + 9000, () => { if (live) run(); });
        };
        seen(chat, (on) => {
            live = on;
            if (on && !timers.length) run();
            if (!on) stop();
        }, { threshold: 0.35 });
    }

    /* What Anee reads, lit one after another, then the answer; again and
       again while it is on screen. */
    const readList = document.querySelector('[data-reads]');
    if (readList) {
        const items = [...readList.querySelectorAll('li')];
        const out = document.querySelector('[data-reads-out]');
        let k = 0, t = null, on = false;
        const reset = () => { items.forEach((li) => li.classList.remove('is-reading', 'is-read')); out?.classList.remove('is-on'); k = 0; };
        const step = () => {
            if (!on) return;
            if (k > 0) { items[k - 1].classList.remove('is-reading'); items[k - 1].classList.add('is-read'); }
            if (k < items.length) { items[k].classList.add('is-reading'); k++; t = setTimeout(step, 700); return; }
            out?.classList.add('is-on');
            t = setTimeout(() => { reset(); t = setTimeout(step, 700); }, 3400);
        };
        if (reduce) { items.forEach((li) => li.classList.add('is-read')); out?.classList.add('is-on'); }
        else seen(readList, (v) => { on = v; clearTimeout(t); if (v) { reset(); step(); } }, { threshold: 0.3 });
    }


    /* The day on the farm, coming in one update at a time while it is on
       screen; a pause to read it, then it starts the day again. */
    const feed = document.querySelector('[data-feed]');
    if (feed) {
        const items = [...feed.querySelectorAll('.hp-feed-item')];
        let k = 0, t = null, on = false;
        const reset = () => { items.forEach((it) => it.classList.remove('is-in')); k = 0; };
        const step = () => {
            if (!on) return;
            if (k < items.length) { items[k++].classList.add('is-in'); t = setTimeout(step, 1500); return; }
            t = setTimeout(() => { reset(); t = setTimeout(step, 700); }, 5500);
        };
        if (reduce) items.forEach((it) => it.classList.add('is-in'));
        else seen(feed, (v) => { on = v; clearTimeout(t); if (v) { reset(); step(); } }, { threshold: 0.3 });
    }

    /* The Collab Room's tools take turns while it is on screen, until the
       visitor picks one. The call's clock runs while its pane is open. */
    const room = document.querySelector('[data-room]');
    if (room) {
        const tabs = [...room.querySelectorAll('[data-room-tab]')];
        const panes = [...room.querySelectorAll('[data-room-pane]')];
        const clock = room.querySelector('[data-call-clock]');
        const DWELL = 4500;
        let at = 0, timer = null, live = false, held = false, secs = 42, tick = null;
        const show = (i) => {
            at = (i + tabs.length) % tabs.length;
            const key = tabs[at].dataset.roomTab;
            tabs.forEach((t, k) => { const on = k === at; t.classList.toggle('is-on', on); t.classList.remove('is-timing'); t.setAttribute('aria-selected', on ? 'true' : 'false'); });
            panes.forEach((p) => p.classList.toggle('is-on', p.dataset.roomPane === key));
            const strip = tabs[at].parentElement;
            if (strip.scrollWidth > strip.clientWidth) strip.scrollTo({ left: tabs[at].offsetLeft - 8, behavior: reduce ? 'auto' : 'smooth' });
            clearInterval(tick);
            if (key === 'call' && clock && !reduce) {
                tick = setInterval(() => { secs++; clock.textContent = String(Math.floor(secs / 60)).padStart(2, '0') + ':' + String(secs % 60).padStart(2, '0'); }, 1000);
            }
            schedule();
        };
        const schedule = () => {
            clearTimeout(timer);
            if (held || !live || reduce) return;
            void tabs[at].offsetWidth;
            tabs[at].style.setProperty('--room-dwell', DWELL + 'ms');
            tabs[at].classList.add('is-timing');
            timer = setTimeout(() => show(at + 1), DWELL);
        };
        tabs.forEach((t, i) => t.addEventListener('click', () => { held = true; show(i); }));
        seen(room, (on) => {
            live = on;
            if (on) schedule();
            else { clearTimeout(timer); clearInterval(tick); tabs[at].classList.remove('is-timing'); }
        }, { threshold: 0.3 });
    }

    /* Anee's analysis tools take turns while on screen, until the visitor
       picks one. */
    const ak = document.querySelector('[data-ak]');
    if (ak) {
        const tabs = [...ak.querySelectorAll('[data-ak-tab]')];
        const panes = [...ak.querySelectorAll('[data-ak-pane]')];
        const DWELL = 5200;
        let at = 0, timer = null, live = false, held = false;
        const show = (i) => {
            at = (i + tabs.length) % tabs.length;
            const key = tabs[at].dataset.akTab;
            tabs.forEach((t, k) => { const on = k === at; t.classList.toggle('is-on', on); t.classList.remove('is-timing'); t.classList.toggle('is-held', on && held); t.setAttribute('aria-selected', on ? 'true' : 'false'); });
            panes.forEach((p) => p.classList.toggle('is-on', p.dataset.akPane === key));
            schedule();
        };
        const schedule = () => {
            clearTimeout(timer);
            if (held || !live || reduce) return;
            void tabs[at].offsetWidth;
            tabs[at].style.setProperty('--ak-dwell', DWELL + 'ms');
            tabs[at].classList.add('is-timing');
            timer = setTimeout(() => show(at + 1), DWELL);
        };
        tabs.forEach((t, i) => t.addEventListener('click', () => { held = true; show(i); }));
        // Arrow keys move along the dots, as a tab list should.
        ak.querySelector('.hp-ak-picks').addEventListener('keydown', (e) => {
            const d = e.key === 'ArrowRight' ? 1 : e.key === 'ArrowLeft' ? -1 : 0;
            if (!d) return;
            e.preventDefault(); held = true; show(at + d); tabs[at].focus();
        });
        seen(ak, (on) => { live = on; if (on) schedule(); else { clearTimeout(timer); tabs[at].classList.remove('is-timing'); } }, { threshold: 0.3 });
    }

    /* Anee thinks in the middle of her sources, only while she is on screen
       (and holds still for a visitor who asked for less motion). */
    const think = document.querySelector('[data-ak-think]');
    if (think && !reduce) {
        seen(think, (on) => { if (on) { const p = think.play(); if (p && p.catch) p.catch(() => {}); } else think.pause(); }, { threshold: 0.2 });
    }

    /* Typhoon watch: the storm (SMIL in the SVG) runs only while the map is
       on screen; the timeline under it reads the SVG's own clock, so the
       playhead, the day and the storm never drift apart. A visitor who asked
       for less motion gets Wednesday, held still. */
    const storm = document.querySelector('[data-storm]');
    if (storm && storm.pauseAnimations) {
        const DUR = 16, HOURS = 120;
        const fill = document.querySelector('[data-storm-fill]');
        const now = document.querySelector('[data-storm-now]');
        const rail = fill && fill.parentElement;
        const DAYS = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
        let raf = 0;
        const paint = () => {
            const p = (storm.getCurrentTime() % DUR) / DUR;
            if (rail) rail.style.setProperty('--p', p.toFixed(4));
            const h = 8 + Math.floor(p * HOURS);
            const hr = h % 24, d = DAYS[Math.min(5, Math.floor(h / 24))];
            if (now) now.textContent = d + ' ' + ((hr % 12) || 12) + (hr < 12 ? ' AM' : ' PM');
        };
        const loop = () => { paint(); raf = requestAnimationFrame(loop); };
        storm.pauseAnimations();
        if (reduce) { storm.setCurrentTime(DUR * .45); paint(); }
        else {
            seen(storm, (on) => {
                cancelAnimationFrame(raf);
                if (on) { storm.unpauseAnimations(); loop(); } else storm.pauseAnimations();
            }, { threshold: 0.2 });
        }
    }

    /* The community plays as one loop while on screen: the post gathers
       reactions, Anee answers, the rooms light up, a level is reached, a
       cofarmer request is accepted; a pause, then it starts again. */
    const comm = document.querySelector('[data-comm]');
    if (comm) {
        const counts = [...comm.querySelectorAll('[data-comm-count]')];
        let timers = [], live = false;
        const steps = ['c1', 'c2', 'c3', 'c4', 'c5', 'c6', 'c7'];
        const at = (ms, fn) => timers.push(setTimeout(fn, ms));
        const stop = () => { timers.forEach(clearTimeout); timers = []; };
        const countUp = () => counts.forEach((b) => {
            const from = +b.dataset.commCount, to = +b.dataset.commTo, t0 = performance.now();
            const tick = (t) => { const p = Math.min(1, (t - t0) / 1200); b.textContent = Math.round(from + (to - from) * p); if (p < 1) requestAnimationFrame(tick); };
            requestAnimationFrame(tick);
        });
        const run = () => {
            stop();
            comm.classList.remove(...steps);
            counts.forEach((b) => { b.textContent = b.dataset.commCount; });
            if (reduce) { comm.classList.add(...steps); counts.forEach((b) => { b.textContent = b.dataset.commTo; }); return; }
            at(300, () => comm.classList.add('c1'));
            at(900, () => { comm.classList.add('c2'); countUp(); });
            at(2300, () => comm.classList.add('c3'));
            at(3500, () => comm.classList.add('c4'));
            at(4700, () => comm.classList.add('c5'));
            at(7000, () => comm.classList.add('c6'));
            at(8600, () => comm.classList.add('c7'));
            at(13000, () => { if (live) run(); });
        };
        seen(comm, (on) => { live = on; if (on && !timers.length) run(); if (!on) stop(); }, { threshold: 0.3 });
    }

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
