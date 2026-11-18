@extends('layouts.public')

@include('public.partials.site-css')
@include('public.partials.hp-base')

@section('title', 'Smart Farm Management App for ' . \App\Support\Region::t('farmersOfTitle'))
@section('meta_description', \App\Support\Region::ph()
    ? 'The smart farm app for Filipino farmers: plan pagtatanim ng palay and mais by day count, track every peso and worker, see your field from space, and ask Anee.'
    : 'The smart farm app for farmers: plan every season by day count, track every dollar and worker, see your field from space, and ask Anee, your smart farm technician.')

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
    // A tool's own page: its feature page, else its public page (the weeds
    // hub), else How It Works; Anee Auto opens its own section further down.
    $toolUrl = fn ($it) => $it['key'] === 'auto' ? '#anee-auto'
        : ((! empty($it['page']) && $ph) ? \App\Support\SitePages::url('features', $it['page']) : (! empty($it['url']) ? url($it['url']) : route('how')));
    $filmOf = function (string $key, ?string $name = null) use ($HW, $tools) {
        $v = $HW::video($key);
        return $v ? ['key' => $key, 'src' => $v[0], 'poster' => $v[1], 'name' => $name ?? ($tools[$key]['name'] ?? '')] : null;
    };
    $aneePrice = $R::priceTag($R::tierPrice('libreAnee', 'month'));
    $face = asset('images/anee/avatar-160.jpg');
    $faceLg = asset('images/anee/avatar-512.jpg');
    $arrow = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5-5 5M6 12h12"/></svg>';
    $pin = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21s-6.5-5.6-6.5-11a6.5 6.5 0 0113 0c0 5.4-6.5 11-6.5 11z"/><circle cx="12" cy="10" r="2.3"/></svg>';
    $tick = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>';
@endphp

@push('head')
    <link rel="preload" as="image" fetchpriority="high" imagesizes="(max-width: 639px) 150vw, 100vw" imagesrcset="{{ asset('images/site/photos/hero-planting-800.webp') }} 800w, {{ asset('images/site/photos/hero-planting-1200.webp') }} 1200w, {{ asset('images/site/photos/hero-planting-1600.webp') }} 1600w">
@endpush

@section('content')

    {{-- ================= HERO ================= --}}
    <section class="hp-hero" data-hero>
        {{-- The picture Google times a phone by. It covers a tall hero, so a phone shows it far wider than the screen: 150vw. --}}
        <img src="{{ asset('images/site/photos/hero-planting-1200.webp') }}" srcset="{{ asset('images/site/photos/hero-planting-800.webp') }} 800w, {{ asset('images/site/photos/hero-planting-1200.webp') }} 1200w, {{ asset('images/site/photos/hero-planting-1600.webp') }} 1600w" sizes="(max-width: 639px) 150vw, 100vw"
             alt="{{ $R::t('farmersOfTitle') }} planting rice, one checking anee.io on his phone"
             class="hp-hero-bg" loading="eager" fetchpriority="high">
        <div class="hp-hero-shade" aria-hidden="true"></div>
        <div class="hp-hero-glow" aria-hidden="true"></div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 hp-hero-in">
            {{-- Anee in the app, on a phone to the left of the words (2026-10-29, the owner: "add the phone again to the left
                 before the main hero text then adjust everything accordingly"; then: "show anee auto daily screen and
                 some analysis results like when to plant, pricing forecast, also satellite view"). One 28 second film,
                 crossfaded (public/videos/site/anee-phone-tour.mp4): Anee Auto's season and today's plan (recorded from
                 the demo season 17), the When to Plant ranking, the price forecast, a satellite reading and the farm on
                 the satellite map. The tag under the phone names what is showing (data-cues, seconds into the film).
                 Plays only while the hero is seen; wide screens only, where the hero becomes three columns. --}}
            @php
                $hpTour = 'videos/site/anee-phone-tour';
                $hpTourV = '?v=' . (@filemtime(public_path($hpTour . '.mp4')) ?: 1);
                $hpCues = [[0, 'Anee Auto, your day'], [7.1, 'When to plant'], [12.6, 'Price forecast'], [18.1, 'Satellite reading'], [22.1, 'Your farm from space']];
            @endphp
            <figure class="hp-hero-phone animate-fade-up" aria-hidden="true" data-hero-phone data-cues='@json($hpCues)'>
                <div class="hp-phone is-sm">
                    <div class="hp-phone-scr">
                        <video class="hp-film" muted loop playsinline preload="none" poster="{{ asset($hpTour . '.webp') . $hpTourV }}" width="420" height="910">
                            <source src="{{ asset($hpTour . '.mp4') . $hpTourV }}" type="video/mp4">
                        </video>
                    </div>
                </div>
                <figcaption><img src="{{ $face }}" alt="" width="40" height="40"><span data-hero-cue>{{ $hpCues[0][1] }}</span></figcaption>
            </figure>
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
                    <span class="hp-mark"><span class="hp-rot" data-words="Higher,Stable,Bigger,Better,Steady,Secured,Record,Greater,Maximum"><span class="hp-rot-w hp-shimmer">Higher</span></span> <span class="hp-shimmer">Yield.</span><svg class="hp-mark-line" viewBox="0 0 300 24" preserveAspectRatio="none" aria-hidden="true"><path class="a" d="M6 13 Q 150 27, 294 5"/></svg></span>
                </h1>
                <p class="hp-lede animate-fade-up" style="animation-delay:.12s">
                    @if ($ph)
                        {{-- "More than 50 tools" (2026-10-19, the owner), counted down to the ten from How It Works' own list,
                             marked so it is seen (2026-10-28, the owner: "make this bold or highlighted ... from the planning
                             to post harvest analysis ... to the space"). --}}
                        The farm app for Filipino farmers who grow palay, mais and gulay:
                        <strong class="hp-lede-tools"><span>more than {{ intdiv(max(10, $tools->count() - 1), 10) * 10 }} tools</span> to help you farm</strong>,
                        from planning the season to the analysis after the harvest, from the price ahead to your field seen from space,
                        with Anee, your smart farm technician, all in one place. Successful businesses run on systems. Smart farmers run on anee.io.
                    @else
                        anee.io puts your whole season on your phone: every field counted from its own day zero, every
                        task on its right day, every dollar written down, and Anee, your smart farm technician, ready with
                        answers day and night.
                    @endif
                </p>
                <div class="hp-hero-acts animate-fade-up" style="animation-delay:.18s">
                    {{-- One call here (2026-10-30, the owner: "remove the watch the tour and ask anee a free question from the
                         top section of the homepage"); the free question is still offered further down the page. --}}
                    <a href="{{ $signup }}" class="btn btn-accent btn-lg hp-go">Create your free account {!! $arrow !!}</a>
                </div>
                <ul class="hp-trust animate-fade-up" style="animation-delay:.24s">
                    <li>{!! $tick !!}Free forever on Libre</li>
                    <li>{!! $tick !!}No card needed</li>
                    <li>{!! $tick !!}Set up in minutes</li>
                </ul>
            </div>

            <div class="hp-hero-stage animate-fade-up" style="animation-delay:.15s" data-tilt>
                <div class="hp-stage-ring" aria-hidden="true"></div>
                {{-- Anee at the center (2026-10-29, the owner's choice; it replaced the phone film, which repeated the many below). --}}
                @include('public.partials.hero-anee')
            </div>
        </div>

        <a href="#bayani-ng-lupa" class="hp-down" aria-label="Scroll to read more">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/></svg>
        </a>
    </section>

    {{-- The first screen is whole here: show it now, and the rest streams in below it (partials.boot-veil, 2026-11-18
         speed audit: the hero waited for 1.2 MB of page). Any <style> written in the body goes to the head anyway
         (PublicSeoTags), so nothing that streams in shows undressed. --}}
    <script>window.__bootEarly && window.__bootEarly();</script>
    {{-- Everything below the hero is dressed by resources/css/home-rest.css (2026-11-18 speed audit). A stylesheet
         <link> in the body: the hero above paints without waiting for it, the content after it waits, never shown
         undressed. PublicSeoTags moves body <style> blocks to the head, never a <link>. --}}
    @vite('resources/css/home-rest.css')

    {{-- A tool's film on a phone, over the page (the steps' Watch buttons on a phone). --}}
    <div class="hp-fm" data-fm role="dialog" aria-modal="true" aria-labelledby="hpFmName" hidden>
        <div class="hp-fm-veil" data-fm-close></div>
        <div class="hp-fm-box">
            <button type="button" class="hp-modal-x" data-fm-close aria-label="Close the film">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true"><path stroke-linecap="round" d="M6 6l12 12M18 6L6 18"/></svg>
            </button>
            <div class="hp-fm-phone"><video muted playsinline loop preload="none"></video></div>
            <p class="hp-fm-name" id="hpFmName"></p>
        </div>
    </div>

    {{-- ================= BAYANI NG LUPA ================= --}}
    {{-- The anee.io album, featured right after the hero (2026-10-13, the
         owner): its own cover, seven songs, the first the main one. --}}
    <section class="hp-sec hp-album" id="bayani-ng-lupa">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 reveal">
            @include('partials.bayani-album', ['id' => 'hpAlbum', 'h' => 'h2', 'compact' => true])
        </div>
    </section>
    @push('head')
    <script type="application/ld+json">{!! json_encode(\App\Support\BayaniAlbum::schema(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
    @endpush

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
                        <li>Labor costs more every year: planting, weeding and harvest hands ask a higher daily rate, and are hard to find when every farm needs them at once.</li>
                        <li>Seed, sprays and the rent for a tractor or a harvester go up with everything else.</li>
                        <li>Rain, heat or pests that come early mean extra sprays and work.</li>
                    </ul>
                    @include('public.partials.sum-trend', ['cls' => 'is-up', 'label' => 'What one hectare costs you, season after season', 'heights' => [30, 40, 51, 63, 77, 94]])
                </div>
                <span class="hp-sum-op" aria-hidden="true">+</span>
                <div class="hp-sum-card is-down reveal" style="--reveal-delay: .12s">
                    <span class="hp-sum-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14m0 0l6-6m-6 6l-6-6"/></svg></span>
                    <p class="hp-sum-k">Prices stay low.</p>
                    <ul class="hp-sum-list">
                        <li>{{ $ph ? 'The palay price at harvest barely moves.' : 'The price at harvest barely moves.' }}</li>
                        <li>Imports can push it down before you sell.</li>
                        <li>Everyone harvests in the same weeks, so {{ $ph ? 'traders' : 'buyers' }} pay the least just when you sell.</li>
                        <li>{{ $ph ? 'Wet palay is docked for moisture, and the trader sets the weight.' : 'Wet grain is docked for moisture, and the buyer sets the weight.' }}</li>
                        <li>You cannot set the price you get.</li>
                    </ul>
                    @include('public.partials.sum-trend', ['cls' => 'is-down', 'label' => 'The net profit you get, season after season', 'heights' => [72, 63, 55, 47, 39, 31]])
                </div>
                <span class="hp-sum-op" aria-hidden="true">=</span>
                <div class="hp-sum-card is-way reveal" style="--reveal-delay: .24s">
                    <span class="hp-sum-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 17l6-6 4 4 8-8m0 0h-5m5 0v5"/></svg></span>
                    <p class="hp-sum-k">The only way up is a <span class="hp-hl">higher yield.</span></p>
                    <p class="hp-sum-p">Yield more from the same hectare with precision farming: the right work, in the right amount, on the right day.</p>
                    <span class="hp-sum-brand"><img src="{{ asset('images/logo-mark-96.webp') }}?v=anee2" alt="" onerror="this.remove()">anee.io shows you how.</span>
                    <ul class="hp-sum-how">
                        <li>It dates every task from each lot's own planting day, so fertilizer and sprays go in on time.</li>
                        <li>Anee checks your crop, its growth stage and the weather before you spend.</li>
                        <li>It writes down every {{ $peso }}, so you can see what works and do it again.</li>
                        <li>NPK Plus and the pest finders show what your crop is missing and what to spray, so nothing is bought for nothing.</li>
                    </ul>
                    {{-- Flat until now (the harvest that "stays the same, year after
                         year"), then up: the climb is the aim, not the past. --}}
                    @include('public.partials.sum-trend', ['cls' => 'is-way', 'label' => 'The aim: more from every hectare, season after season', 'heights' => [34, 35, 33, 46, 68, 96], 'mark' => 3, 'markLabel' => 'You start using anee.io', 'curve' => true])
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
        {{-- Behind it, faint under the red (owner, 2026-10-07): a farmer
             over palay a storm laid flat. --}}
        <img class="hp-loss-bg" src="{{ asset('images/site/photos/storm-lodged.webp') }}"
             srcset="{{ asset('images/site/photos/storm-lodged-900.webp') }} 900w, {{ asset('images/site/photos/storm-lodged.webp') }} 1600w" sizes="100vw"
             alt="A Filipino farmer on the phone looks over his palay field, flattened by a storm before harvest"
             title="Palay flattened by a storm: the harvest a late decision costs" width="1600" height="893" loading="lazy" decoding="async">
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

            <div class="mt-12 grid gap-5 md:grid-cols-2" data-swipe>
                @foreach ([
                    {{-- Every peso range is "up to n%" of a hectare that earns
                         ₱60,000 to ₱100,000 (the note under the cards says so):
                         keep the two in step when a number changes. --}}
                    ['n' => 40, 'img' => 'rice-field-pest-damage', 'alt' => 'A patch of rice turned brown and dry in the middle of a green field, eaten by pests', 'title' => 'Pest damage found too late', 'l' => 'Lost to pests and diseases', 'p' => 'When the rice bug, thrips or fall armyworm are treated late, or not at all.', 'peso' => '₱24,000 to ₱40,000'],
                    ['n' => 30, 'img' => 'farmer-wrong-spray-vegetables', 'alt' => 'A Filipino farmer in a vegetable field reads a pesticide bottle beside his knapsack sprayer, unsure it is the right one', 'title' => 'Paying for the wrong spray', 'l' => 'Wasted on the wrong fix', 'p' => 'If you guess the problem wrong, you pay full price for the wrong product while the real problem grows.', 'peso' => '₱18,000 to ₱30,000'],
                    ['n' => 30, 'img' => 'farm-myth-remedy-rice-field', 'alt' => 'A worried farmer stands by a part of his rice field burned white by a home remedy, with bottles and a bucket at his feet', 'title' => 'A remedy tried on the whole field', 'l' => 'Lost to farm myths', 'p' => 'Remedies heard from others, or lucky planting days, tried on a whole field before anyone checked.', 'peso' => '₱18,000 to ₱30,000'],
                    ['n' => 30, 'img' => 'farmer-untracked-farm-costs', 'alt' => 'A tired farmer sits on the porch of his bahay kubo with receipts and an empty wallet beside him', 'title' => 'Costs nobody wrote down', 'l' => 'Lost to spending nobody tracked', 'p' => 'Small costs you never write down add up all season, and you only see them at the end.', 'peso' => '₱18,000 to ₱30,000'],
                    ['n' => 25, 'img' => 'farmer-fertilizer-wrong-day-rice', 'alt' => 'A farmer broadcasts fertilizer by hand from a basin over young rice in a flooded paddy', 'title' => 'Fertilizer on the wrong day', 'l' => 'Lost to fertilizer on the wrong day', 'p' => 'Urea fertilizer applied in the wrong week gives the crop only a small part of what you paid for.', 'peso' => '₱15,000 to ₱25,000'],
                    ['n' => 25, 'img' => 'dry-cracked-rice-field', 'alt' => 'A farmer kneels on dry, cracked soil and checks rice plants dying from lack of water', 'title' => 'Too dry when the crop needed water', 'l' => 'Lost to water at the wrong time', 'p' => 'Too dry at flowering or too wet at ripening, and that harvest does not come back.', 'peso' => '₱15,000 to ₱25,000'],
                    ['n' => 20, 'img' => 'farmer-waiting-for-farm-advice', 'alt' => 'A farmer sits at a bamboo table outside his house, on the phone waiting for advice about his crop', 'title' => 'Days lost waiting for an answer', 'l' => 'Lost to waiting for answers', 'p' => 'Days spent waiting for advice while the problem keeps growing.', 'peso' => '₱12,000 to ₱20,000'],
                    ['n' => 20, 'img' => 'flooded-corn-field-wrong-planting-time', 'alt' => 'A farmer sits at the edge of his corn field, flooded and yellowing after heavy rain', 'title' => 'Planted in the wrong weeks', 'l' => 'Lost to planting at the wrong time', 'p' => 'Pagtatanim by habit instead of by the weather shows up as a smaller ani.', 'peso' => '₱12,000 to ₱20,000'],
                ] as $i => $loss)
                    <div class="loss-card loss-card2 reveal" style="--loss: {{ $loss['n'] }}%; --reveal-delay: {{ ($i % 2) * 0.08 }}s">
                        {{-- The owner's pictures (2026-10-19): WebP at 480 and 800 wide, 3:2. --}}
                        <div class="loss-img"><img src="{{ asset('images/site/loss/' . $loss['img'] . '-800.webp') }}"
                            srcset="{{ asset('images/site/loss/' . $loss['img'] . '-480.webp') }} 480w, {{ asset('images/site/loss/' . $loss['img'] . '-800.webp') }} 800w"
                            sizes="(max-width: 767px) 92vw, 10rem" width="800" height="533"
                            alt="{{ $loss['alt'] }}" title="{{ $loss['title'] }}" loading="lazy" decoding="async"></div>
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
                Percent ranges come from published crop loss estimates{{ $ph ? ' for Philippine rice' : '' }}{{ $ph ? '. Peso ranges assume a palay hectare that earns ₱60,000 to ₱100,000 before costs' : '' }}. Your own numbers will be different.
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

    {{-- Right after "Guessing Is the Most Expensive Thing on Your Farm": a real post where twenty strangers gave seven
         answers (2026-10-28, the owner: "move this section ... after this section"). --}}
    @include('public.partials.social-guess')

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

            <div class="hp-prec-rows" data-swipe>
                <div class="hp-prec-cols" aria-hidden="true"><span>When this happens</span><span>What anee.io does</span></div>
                @foreach ([
                    ['The weather changes.', 'A dry spell goes on, or rain comes right before your spray day.',
                     'You see the forecast for each lot ahead of time. If you need to move the plan, drag it and every date moves with it.',
                     '<path stroke-linecap="round" stroke-linejoin="round" d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999A5.002 5.002 0 105.9 12.1 4 4 0 003 15zM13 21l-2 2m6-4l-2 2m-8-2l-2 2"/>'],
                    ['Pests come early.', 'Yellow leaves or holes appear, like from the rice bug or thrips, and help is days away.',
                     'Take a photo and Anee tells you what it is and what to do. Or open the pest and disease finders: from the damage you see to the active ingredient to spray. The right problem, the right dose, today.',
                     '<path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2m0 0a7 7 0 017 7v3a3 3 0 01-3 3H8a3 3 0 01-3-3v-3a7 7 0 017-7zM9 12h.01M15 12h.01M9.5 17h5"/>'],
                    ['The crop grows faster or slower.', 'Hot days make it grow faster than the plan. Cool days slow it down.',
                     'See the growth stage of each lot on any day, with what to do and what to watch for. Anee can check the real stage for you.',
                     '<path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>'],
                    ['Costs go up.', 'One extra spray, one job done twice, and your profit gets smaller without you noticing.',
                     'Labor, materials and services add up as you go, ' . $peso . ' by ' . $peso . ', so you know the cost before you spend.',
                     '<path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>'],
                    ['Fertilizer costs more every season.', 'A bag too many is money left in the soil. A bag too few is yield you never harvest.',
                     'NPK Plus counts every nutrient in your fertilizer plan against what your crop needs, so you buy only what is missing. ' . $HW::planOf('npk')['says'] . '.',
                     '<path stroke-linecap="round" stroke-linejoin="round" d="M6 7h12l-1 13H7L6 7zm3 0V5a3 3 0 016 0v2M9.5 12.5h5M12 10v5"/>'],
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

    {{-- ================= A REAL FIELD, FILMED (2026-10-29) ================= --}}
    @include('public.partials.field-film', ['signup' => $signup])

    {{-- ================= SAME AGE, SAME VARIETY (2026-10-29) ================= --}}
    @include('public.partials.same-age-plots', ['signup' => $signup])

    {{-- ================= TYPHOON SEASON, DAYS BEFORE HARVEST (2026-10-29) ================= --}}
    @include('public.partials.typhoon-film', ['signup' => $signup])

    {{-- ================= THE SEASON IN EIGHT STEPS ================= --}}
    {{-- The answer, shown: How It Works' steps as tabs (eight since
         2026-10-13). Here Anee Auto, the easiest way, is step 1 (the owner,
         2026-10-13), and a divider under it says the rest is the way to do it
         by hand, from step 2 on. Each step lists
         its tools, and the phone plays the step's films (the same phone
         recordings the How It Works modal plays). The tabs walk on by
         themselves while the section is in view, until a visitor takes over. --}}
    @php
        // Anee Auto first, then the season by hand; on this page its small line says what it is.
        $hpSteps = collect($stages)->sortBy(fn ($s) => $s['key'] === 'auto' ? 0 : 1)->values()
            ->map(fn ($s) => $s['key'] === 'auto' ? array_replace($s, ['when' => 'The easiest way']) : $s)->all();
    @endphp
    <section class="hp-sec bg-brand-mesh hp-steps" data-steps>
        <div class="max-w-7xl mx-auto px-4 sm:px-6">
            <div class="hp-head reveal">
                <p class="hp-kick">{{ $ph ? 'From pagtatanim to ani' : 'From planting to harvest' }}</p>
                <h2 class="hp-h2">Your Whole Season in <em>{{ count($hpSteps) }} <span class="hp-nw">Steps.<span class="hp-tick" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M5.4 12.6l4.3 4.3 8.9-9.6" pathLength="1"/></svg></span></span></em></h2>
                <p class="hp-p"><b class="hp-st-count">{{ $tools->count() }} tools</b> for every part of the season, each step with its own, and Anee helps in every one. Tap a step, then a tool, to read what it does and watch it work on a real phone.</p>
            </div>

            <div class="hp-st reveal">
                <div class="hp-st-list" role="tablist" aria-label="The steps of a season">
                    @foreach ($hpSteps as $i => $st)
                        <button type="button" role="tab" class="hp-st-tab{{ $i === 0 ? ' is-on' : '' }}{{ $st['key'] === 'auto' ? ' is-auto' : '' }}" data-step="{{ $i }}"
                                id="hpTab{{ $i }}" aria-controls="hpPane{{ $i }}" aria-selected="{{ $i === 0 ? 'true' : 'false' }}">
                            <span class="hp-st-n">{{ $i + 1 }}</span>
                            <span class="hp-st-tt"><b>{{ $st['title'] }}</b><small>{{ $st['when'] }}</small></span>
                            @if ($st['key'] === 'auto')<img class="hp-st-face" src="{{ $face }}" alt="" width="40" height="40" loading="lazy">@endif
                            <span class="hp-st-bar" aria-hidden="true"><i></i></span>
                        </button>
                        @if ($st['key'] === 'auto' && $i === 0)
                            <div class="hp-st-or" role="presentation">
                                <b>Or do it yourself</b>
                                <small>Prefer to do it by hand? Start with step 2 and go on from there.</small>
                            </div>
                        @endif
                    @endforeach
                </div>

                <div class="hp-st-body">
                    <div class="hp-st-panes">
                        @foreach ($hpSteps as $i => $st)
                            <div class="hp-st-pane{{ $i === 0 ? ' is-on' : '' }}" role="tabpanel" id="hpPane{{ $i }}" aria-labelledby="hpTab{{ $i }}" data-pane="{{ $i }}">
                                <p class="hp-st-when">Step {{ $i + 1 }} · {{ $st['when'] }}</p>
                                <h3 class="hp-st-h">{{ $st['title'] }}</h3>
                                <p class="hp-st-p">{{ $st['lede'] }}</p>
                                {{-- Every tool says what it is for in one line, and the one
                                     picked opens its whole explanation below (2026-10-06,
                                     the owner: "see the explanation for each feature"). All
                                     of them are in the page; only the picked one shows. --}}
                                @php $on = collect($st['items'])->search(fn ($x) => ! empty($x['video'])); $on = $on === false ? 0 : $on; @endphp
                                <div class="hp-tools{{ count($st['items']) === 1 ? ' is-one' : '' }}">
                                    @foreach ($st['items'] as $j => $it)
                                        @php $f = $filmOf($it['key']); @endphp
                                        <button type="button" class="hp-tool{{ $j === $on ? ' is-on' : '' }}" style="--j: {{ $j }}" data-tool="{{ $it['key'] }}"
                                                aria-pressed="{{ $j === $on ? 'true' : 'false' }}" aria-controls="hpInfo-{{ $it['key'] }}"
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
                                        @php $shot = $HW::shot($it['key']); @endphp
                                        <div class="hp-info{{ $j === $on ? ' is-on' : '' }}" id="hpInfo-{{ $it['key'] }}" data-info="{{ $it['key'] }}" data-name="{{ $it['name'] }}" data-short="{{ $it['short'] }}" @if ($shot) data-shot="{{ $shot[0] }}" @endif>
                                            <p class="hp-info-k">
                                                <span class="hp-info-ico{{ str_starts_with($it['icon'], 'anee/') ? ' is-face' : '' }}"><img src="{{ asset('images/' . $it['icon']) }}" alt="" loading="lazy"></span>
                                                <span>{{ $it['name'] }}@if ($it['anee'])<i class="hp-anee-tag">Anee does it</i>@endif</span>
                                            </p>
                                            <p class="hp-info-w">{{ $it['what'] }}</p>
                                            <ul class="hp-info-g">
                                                @foreach ($it['gets'] as $g)<li><span class="hp-info-ok">{!! $tick !!}</span><span>{{ $g }}</span></li>@endforeach
                                            </ul>
                                            {{-- How it helps (2026-10-28, the owner): the tool's feature page's own points. --}}
                                            @if (! empty($it['helps']))
                                                <div class="hp-info-help"><p>How it helps your crops and your farm</p><ul>@foreach ($it['helps'] as $h)<li>{!! $HW::helpHtml($h) !!}</li>@endforeach</ul></div>
                                            @endif
                                            {{-- The plan it comes with, read off config/tiers.php (HowItWorks::planOf, 2026-10-18). --}}
                                            <p class="hp-info-plan is-{{ $it['plan']['tier'] }}"><i aria-hidden="true"></i><span>{{ $it['plan']['says'] }}@if ($it['plan']['more'])<small>{{ $it['plan']['more'] }}</small>@endif</span></p>
                                            @php $wf = $filmOf($it['key']); @endphp
                                            @if ($wf)
                                                {{-- On a phone the film is not beside the words (there is no room), so it opens over the page. --}}
                                                <button type="button" class="hp-info-watch" data-fm-src="{{ $wf['src'] }}" data-fm-poster="{{ $wf['poster'] }}" data-fm-name="{{ $it['name'] }}">
                                                    <span><svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M8 5.14v13.72L19 12 8 5.14z"/></svg></span>Watch it on a phone
                                                </button>
                                            @endif
                                            <a href="{{ $toolUrl($it) }}" class="hp-info-more">Read more about it {!! $arrow !!}</a>
                                        </div>
                                    @endforeach
                                </div>
                                {{-- Anee Auto has one tool, so its step says how it works in
                                     five and what you no longer do (2026-10-13, the owner:
                                     "add more content to make it look balanced"). --}}
                                @if ($st['key'] === 'auto')
                                    <div class="hp-st-auto">
                                        <p class="hp-st-auto-k">How Anee Auto works</p>
                                        <ol class="hp-st-auto-steps">
                                            @foreach ([
                                                ['Tell her once', 'Your lots, crop and variety, soil, water, fertilizer and workers.'],
                                                ['She analyzes it all', 'Your variety, El Niño or La Niña, the forecast for your town and your soil.'],
                                                ['She plans the season', 'Every task from land preparation to harvest, each on its day.'],
                                                ['You just follow', "Today's work every morning, in the app and by email to your workers."],
                                                ['She keeps it on track', "Rain coming or no workers? Tap Can't today and she moves it."],
                                                ['Show her the field', 'A photo, a video or a voice note, and she adjusts the plan.'],
                                            ] as $ak => [$at, $aw])
                                                <li style="--k: {{ $ak }}"><span class="hp-st-auto-n">{{ $ak + 1 }}</span><span><b>{{ $at }}</b><small>{{ $aw }}</small></span></li>
                                            @endforeach
                                        </ol>
                                        <ul class="hp-st-auto-skip" aria-label="What you no longer do">
                                            <li>No schedules to build</li><li>No analyses to run</li><li>No guessing the weather</li><li>No missed fertilizer days</li>
                                        </ul>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>

                    <div class="hp-st-show">
                        @php $first = $filmOf($hpSteps[0]['items'][0]['key']); @endphp
                        <div class="hp-phone">
                            <div class="hp-phone-scr">
                                <video class="hp-film" data-step-film muted playsinline loop preload="none" poster="{{ $first['poster'] ?? '' }}" aria-label="{{ $hpSteps[0]['items'][0]['name'] }} on a phone">
                                    @if ($first)<source src="{{ $first['src'] }}" type="video/mp4">@endif
                                </video>
                                {{-- A tool with no film yet (2026-10-18): its card on the phone's
                                     screen instead, so the film of the tool before it never plays
                                     under the wrong name. Filled from the tool's own explanation. --}}
                                <div class="hp-nofilm" data-step-nofilm aria-hidden="true">
                                    <span class="hp-nofilm-ico"><img src="" alt=""></span>
                                    <b class="hp-nofilm-t"></b>
                                    <span class="hp-nofilm-s"></span>
                                    <ul class="hp-nofilm-g"></ul>
                                    <span class="hp-nofilm-plan"></span>
                                    {{-- Its screen as the app is now, when there is one (2026-10-27). --}}
                                    <img class="hp-nofilm-shot" src="data:image/gif;base64,R0lGODlhAQABAAAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==" alt="">
                                </div>
                            </div>
                            <span class="hp-film-tag" data-step-tag>{{ $hpSteps[0]['items'][0]['name'] }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="hp-cta reveal">
                <div class="hp-cta-row">
                    <a href="{{ $signup }}" class="btn btn-accent btn-lg hp-go">Start your first season free {!! $arrow !!}</a>
                    <a href="{{ route('how') }}" class="hp-alt">See all {{ $tools->count() }} tools step by step</a>
                </div>
                <p class="hp-cta-note">Each tool says the plan it comes with. The season board, your lots, the growth stages, notes and the labor report are on every plan, Libre included.</p>
            </div>
        </div>
    </section>

    {{-- ================= EASY TO USE, MADE BY FILIPINO FARMERS (2026-10-29) ================= --}}
    @if ($ph)
    @include('public.partials.easy-section', ['signup' => $signup])
    @endif

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
            ['Your lots', 'Each field with its crop, variety and size.', 'map'],
            ['Your growth stage', 'Where each lot is today, its DAS or DAT count, and what that stage needs now.', 'sprout'],
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
        $svg = fn ($k, $w = '1.9') => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="' . $w . '" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="' . ['map' => 'M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7', 'plan' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01', 'cloud' => 'M3 15a4 4 0 004 4h9a5 5 0 10-.9-9.95A5.5 5.5 0 006.5 8 4.5 4.5 0 003 15z', 'chart' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z', 'camera' => 'M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9zM15 13a3 3 0 11-6 0 3 3 0 016 0z', 'search' => 'M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z', 'book' => 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253', 'clock' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z', 'peso' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z', 'target' => 'M12 21a9 9 0 100-18 9 9 0 000 18zm0-4a5 5 0 100-10 5 5 0 000 10zm0-4a1 1 0 100-2 1 1 0 000 2z', 'bolt' => 'M13 10V3L4 14h7v7l9-11h-7z', 'user' => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z', 'repeat' => 'M4 4v5h5M20 20v-5h-5M5.1 15a7.5 7.5 0 0013.4 2M18.9 9A7.5 7.5 0 005.5 7', 'shield' => 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z', 'sun' => 'M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z', 'sprout' => 'M12 21v-9m0 0C12 7 8 5 4 5c0 4 3 7 8 7zm0 0c0-4 3-7 8-7 0 4-4 7-8 7z'][$k] . '"/></svg>';
        $xMark = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.8" aria-hidden="true"><path stroke-linecap="round" d="M7 7l10 10M17 7L7 17"/></svg>';
    @endphp
    <section class="hp-anee spark-field on-dark">
        {{-- Her section moves too (2026-10-13, the owner: "a moving green
             gradient, like the daily tip's background", no shine): soft green
             glows drifting slowly across the dark ground. --}}
        <div class="hp-anee-bg" aria-hidden="true">
            <i class="hp-anee-aura is-a"></i><i class="hp-anee-aura is-b"></i><i class="hp-anee-aura is-c"></i>
        </div>
        <div class="relative max-w-6xl mx-auto px-4 sm:px-6 hp-sec" style="z-index:1">
            <div class="hp-anee-grid">
                <div class="reveal hp-anee-copy">
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
                </div>

                <div class="reveal hp-anee-media">
                    {{-- Her chat on a phone (2026-10-13, the owner: smaller, and like a
                         phone), with her hello film back over its top corner, small. --}}
                    <div class="hp-anee-phone">
                    <i class="hp-anee-key is-power"></i><i class="hp-anee-key is-up"></i><i class="hp-anee-key is-down"></i>
                    <div class="hp-chat" data-chat>
                        <div class="hp-chat-status" aria-hidden="true">
                            <b>9:41</b><span class="hp-chat-island"></span>
                            <span class="hp-chat-sig"><svg viewBox="0 0 18 12"><rect x="0" y="8" width="3" height="4" rx="1"/><rect x="5" y="5" width="3" height="7" rx="1"/><rect x="10" y="2.5" width="3" height="9.5" rx="1"/><rect x="15" y="0" width="3" height="12" rx="1" opacity=".35"/></svg><svg viewBox="0 0 25 12"><rect x=".5" y=".5" width="21" height="11" rx="3" fill="none" stroke="currentColor"/><rect x="2" y="2" width="15" height="8" rx="1.6"/><rect x="22.5" y="4" width="2" height="4" rx="1"/></svg></span>
                        </div>
                        <div class="hp-chat-top">
                            <img src="{{ $face }}" alt="" class="hp-face is-lg">
                            <span><b>Anee</b><small><i class="hp-live"></i>Your smart farm technician</small></span>
                        </div>
                        <div class="hp-chat-body">
                            <span class="hp-chat-day">Today</span>
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
                        <div class="hp-chat-compose" aria-hidden="true">
                            <i><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9zM15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg></i>
                            <span>Ask about your crop</span><b>➤</b>
                        </div>
                        <span class="hp-chat-home" aria-hidden="true"></span>
                    </div>
                    <figure class="hp-anee-film" data-anee-film>
                        <video playsinline preload="none" data-late-poster="{{ asset('videos/anee-hello-poster.webp') }}" aria-label="Anee says hello">
                            <source src="{{ asset('videos/anee-hello.mp4') }}" type="video/mp4">
                        </video>
                        <button type="button" class="hp-anee-film-play" aria-label="Play Anee's hello">
                            <span><svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M8 5.14v13.72L19 12 8 5.14z"/></svg></span>
                        </button>
                    </figure>
                    </div>
                </div>

                {{-- The ask comes after the proof (2026-10-07): under the chat on a
                     phone, where the words and the chat stack; under the words,
                     beside the chat, on a desk. --}}
                <div class="hp-cta is-left hp-anee-cta reveal">
                    <div class="hp-cta-row">
                        <a href="{{ $ask }}" class="btn btn-accent btn-lg hp-go">Ask Anee a free question {!! $arrow !!}</a>
                        <a href="{{ $signup }}" class="hp-alt">Create your free account</a>
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
                        <span class="is-old">{!! $svg('user', '2') !!}Your Local Agricultural Technician</span>
                        <span class="is-new"><img src="{{ $face }}" alt="">Anee</span>
                    </div>
                    @foreach ($aneeVs as $i => [$vk, $vi, $vo, $vn, $race])
                        <div class="hp-tvs-row" style="--i: {{ $i }}">
                            <b class="hp-tvs-k"><span>{!! $svg($vi) !!}</span>{{ $vk }}</b>
                            <div class="hp-tvs-old">
                                <i>{!! $xMark !!}</i>
                                <p><em class="hp-vs-label">Your Local Agricultural Technician:</em> {{ $vo }}
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
                <div class="hp-tvs-more"><button type="button" data-tvs-more>See all {{ count($aneeVs) }} differences</button></div>
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
    {{-- Anee Auto (2026-10-12): a section of its own, right after Meet Anee, so her
         section keeps the steps section right above it. --}}
    @include('public.partials.anee-auto-section', ['signup' => $signup])

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
                    <span class="hp-ak-core" aria-hidden="true"><video src="{{ asset('videos/anee/thinking.mp4') }}" poster="{{ asset('videos/anee/thinking.webp') }}"
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

            <div class="hp-biz-copy">
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
            </div>

            {{-- After the farmers' own words on a phone (the pictures, the quote
                 and the song stack under the story there); under the story,
                 beside the pictures, on a desk. --}}
            <div class="hp-cta is-left reveal hp-biz-cta">
                <div class="hp-cta-row">
                    <a href="{{ $signup }}" class="btn btn-accent btn-lg hp-go">Start free and upgrade your farm {!! $arrow !!}</a>
                    <a href="{{ route('about') }}" class="hp-alt">Read our story</a>
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

                <div class="hp-team-cards" data-swipe>
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
                        {{-- Group call: drawn Filipino farmers, not real people (2026-10-13,
                             the owner): the owner in a cap, Juan in a salakot, Maria in a
                             straw sun hat, Pedro with a bandana and a bimpo on his shoulder. --}}
                        @php
                            $farmer = function (array $o): string {
                                $sk = $o['skin']; $sd = $o['skinDark']; $hr = $o['hair'];
                                $svg = '<svg viewBox="0 0 64 64" aria-hidden="true"><rect width="64" height="64" fill="' . $o['bg'] . '"/>';
                                if (($o['hat'] ?? '') === 'sun') {
                                    $svg .= '<path d="M19.5 30c0-9 5.5-14 12.5-14s12.5 5 12.5 14v13c-3 2-6 2.6-8.5 2.2V32H28v13.2c-2.5.4-5.5-.2-8.5-2.2z" fill="' . $hr . '"/>';
                                }
                                $svg .= '<path d="M8 64c1-11 11-18 24-18s23 7 24 18z" fill="' . $o['shirt'] . '"/>'
                                    . '<path d="M26 46.5l6 7 6-7" fill="none" stroke="' . $o['shirtDark'] . '" stroke-width="1.6" stroke-linejoin="round"/>'
                                    . '<path d="M27 38h10v8.5c0 2-2.2 3.5-5 3.5s-5-1.5-5-3.5z" fill="' . $sd . '"/>'
                                    . '<circle cx="20.6" cy="31" r="2.6" fill="' . $sd . '"/><circle cx="43.4" cy="31" r="2.6" fill="' . $sd . '"/>'
                                    . '<ellipse cx="32" cy="30" rx="11.5" ry="13" fill="' . $sk . '"/>';
                                if (($o['hat'] ?? '') !== 'salakot') {
                                    $svg .= '<path d="M20.5 29c-.6-9 4.8-14.5 11.5-14.5S44.2 20 43.5 29c-1.6-4.6-4.3-7.6-11.5-7.6S22.1 24.4 20.5 29z" fill="' . $hr . '"/>';
                                }
                                $svg .= '<path d="M25.6 27.8q2-1.2 4 0M34.4 27.8q2-1.2 4 0" fill="none" stroke="' . $hr . '" stroke-width="1.3" stroke-linecap="round"/>'
                                    . '<ellipse cx="27.6" cy="31.4" rx="1.35" ry="1.6" fill="#24170f"/><ellipse cx="36.4" cy="31.4" rx="1.35" ry="1.6" fill="#24170f"/>'
                                    . '<circle cx="25.4" cy="35.2" r="1.9" fill="#e07b5a" opacity=".3"/><circle cx="38.6" cy="35.2" r="1.9" fill="#e07b5a" opacity=".3"/>'
                                    . '<path d="M28.6 36.8q3.4 2.6 6.8 0" fill="none" stroke="#6e3220" stroke-width="1.5" stroke-linecap="round"/>';
                                if (! empty($o['mustache'])) {
                                    $svg .= '<path d="M27.8 35q4.2-2 8.4 0q-1.3 1.1-4.2 1t-4.2-1z" fill="' . $hr . '"/>';
                                }
                                switch ($o['hat'] ?? '') {
                                    case 'salakot':
                                        $svg .= '<path d="M4 23 32 5l28 18c-6.5 3.3-16.5 4.8-28 4.8S10.5 26.3 4 23z" fill="#d8b46a"/>'
                                            . '<path d="M32 5v22.6M18 14.2l14 5.2M46 14.2 32 19.4M11 19l21 4.6M53 19 32 23.6" stroke="#b48d45" stroke-width=".9" fill="none"/>'
                                            . '<path d="M4 23c6.5 3.3 16.5 4.8 28 4.8S53.5 26.3 60 23" fill="none" stroke="#97743a" stroke-width="1.4"/>';
                                        break;
                                    case 'cap':
                                        $svg .= '<path d="M20.4 24.6c0-7.6 5-12.1 11.6-12.1s11.6 4.5 11.6 12.1z" fill="' . $o['cap'] . '"/>'
                                            . '<path d="M18.6 24h26.8c1.4 0 2.2 1.5 1.3 2.6-.7.8-1.6 1.2-2.6 1.2H19.9c-1 0-1.9-.4-2.6-1.2-.9-1.1-.1-2.6 1.3-2.6z" fill="' . $o['capDark'] . '"/>'
                                            . '<circle cx="32" cy="19" r="2.3" fill="#f5c518"/>';
                                        break;
                                    case 'sun':
                                        $svg .= '<ellipse cx="32" cy="21.8" rx="20.5" ry="4.8" fill="#d2a95c"/>'
                                            . '<path d="M22.2 21.6c0-6.6 4.4-10.6 9.8-10.6s9.8 4 9.8 10.6z" fill="#e4c47c"/>'
                                            . '<path d="M22.3 19.2h19.4v2.4H22.3z" fill="' . $o['ribbon'] . '"/>';
                                        break;
                                    case 'bandana':
                                        $svg .= '<path d="M20.4 24.2c3.1-3.1 7.1-4.6 11.6-4.6s8.5 1.5 11.6 4.6l-.7 3.1c-3-2.6-6.7-3.9-10.9-3.9s-7.9 1.3-10.9 3.9z" fill="#c2412d"/>'
                                            . '<path d="M42.6 23.6l4.6-1.6-1.2 3.4 2.4 2.8-4.9-.6z" fill="#a8341f"/>';
                                        break;
                                }
                                if (! empty($o['towel'])) {
                                    $svg .= '<path d="M13.4 53.5c4-4.3 9-6.6 13.4-6.9l-1.6 6.1c-4 .7-7.7 2.6-10.8 5.3z" fill="#f4efe2"/><path d="M15 55.6l10.9-6.4" stroke="#d9d1bd" stroke-width=".9"/>';
                                }

                                return $svg . '</svg>';
                            };
                            $callers = [
                                ['You', 120, ['bg' => '#d9ecc7', 'skin' => '#c68f63', 'skinDark' => '#b07a50', 'hair' => '#2b1d16', 'shirt' => '#4a7c2a', 'shirtDark' => '#2f5219', 'hat' => 'cap', 'cap' => '#2f5219', 'capDark' => '#24400f']],
                                ['Juan', 205, ['bg' => '#cfe3f3', 'skin' => '#a8714b', 'skinDark' => '#935f3c', 'hair' => '#3b2c22', 'shirt' => '#3b6ea8', 'shirtDark' => '#284f7c', 'hat' => 'salakot', 'mustache' => true]],
                                ['Maria', 330, ['bg' => '#f6d6e0', 'skin' => '#b9825a', 'skinDark' => '#a26e48', 'hair' => '#1f1510', 'shirt' => '#d65a7e', 'shirtDark' => '#a8405f', 'hat' => 'sun', 'ribbon' => '#4a7c2a']],
                                ['Pedro', 30, ['bg' => '#f8e2c4', 'skin' => '#9a6440', 'skinDark' => '#855332', 'hair' => '#20150f', 'shirt' => '#e59a2f', 'shirtDark' => '#b8761b', 'hat' => 'bandana', 'towel' => true]],
                            ];
                        @endphp
                        <div class="hp-room-pane" id="hpRoom-call" data-room-pane="call">
                            <div class="hp-call">
                                @foreach ($callers as $k => [$cn, $ch, $look])
                                    <div class="hp-call-tile{{ $k === 2 ? ' is-talking' : '' }}" style="--h: {{ $ch }}">
                                        <span class="hp-call-av is-drawn">{!! $farmer($look) !!}</span><small>{{ $cn }}</small>
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
                        anee.io uses satellites and cloud penetrating radar to analyze the health of your farm from above. Every
                        5 to 10 days you get a fresh reading of how each field is doing, so you spot hidden problems early, even
                        during heavy monsoon rains, without buying an expensive drone.
                    </p>
                    <div class="hp-sky-stats">
                        <div><b>Every 5 to 10 days</b><small>A fresh health reading of every lot you farm</small></div>
                        <div><b>Through clouds</b><small>Radar sees your crop in the monsoon</small></div>
                        <div><b>No drone needed</b><small>Nothing to buy, nothing to fly, nothing to charge</small></div>
                    </div>
                    <a href="{{ route('features') }}#space" class="hp-sky-go">
                        <img src="{{ asset('images/icons/satellite.svg') }}" alt="" width="28" height="28" loading="lazy">
                        <span><b>Satellite Analysis</b><small>Draw your field, see its health from space</small></span>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14m-6-6l6 6-6 6"/></svg>
                    </a>
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
                    <span class="hp-sat-tag is-photo"><i></i>Crop health analysis</span>
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
                    <a href="{{ route('features') }}#space" class="hp-sky-go">
                        <img src="{{ asset('images/icons/storm.svg') }}" alt="" width="28" height="28" loading="lazy">
                        <span><b>Satellite Weather</b><small>Clouds, rain and every typhoon's path over your farm</small></span>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14m-6-6l6 6-6 6"/></svg>
                    </a>
                </div>

                <div class="hp-storm-map reveal" aria-hidden="true">
                    <img class="hp-storm-base" src="{{ asset('images/site/storm/ph-satellite.webp') }}"
                         srcset="{{ asset('images/site/storm/ph-satellite-760.webp') }} 760w, {{ asset('images/site/storm/ph-satellite.webp') }} 1280w"
                         sizes="(min-width: 1024px) 40rem, 92vw" alt="" width="1280" height="1000" loading="lazy">
                    <span class="hp-storm-clouds" data-late-bg="{{ asset('images/site/storm/clouds.webp') }}"></span>
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
                                <image data-late-href="{{ asset('images/site/storm/typhoon.webp') }}" x="-170" y="-170" width="340" height="340"/>
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
                <p class="hp-cta-note">Workers come with Solo Farmer. Worker logins and the Collab Room come with Farm Owner. Offline mode is on every plan.</p>
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
                                {{-- A title covers ten levels (CommunityRank: Lv 11 to 20 is
                                     Rising Farmer, Lv 21 to 30 Green Thumb), so the new title
                                     comes with Lv 21, not 20. --}}
                                <span><small>Your level</small><b><span data-lv-from>Lv 20</span><span data-lv-to>Lv 21</span></b></span>
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

                <div class="hp-comm-feats" data-swipe>
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
                        ['Learning about pests', 'Ask around and hope the advice fits your crop.', 'Guides to the pests, diseases and weeds of your crops, with what to do step by step, and finders that match what you see.'],
                        ['Other farmers\' experience', 'Only the neighbors you happen to meet.', 'A community of growers who trade what worked, with photos and voice.'],
                        ['Halfway through the season', 'You find out how it went at harvest.', 'Anee checks the season so far and names the risks in the coming weeks.'],
                    ]],
                    ['Materials and money', 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z', [
                        ['Materials and budget', 'Rough estimates. Overspending creeps in unnoticed.', 'What the plan needs against what is in the shed, so you buy only what is missing.'],
                        ['Stock in the shed', 'Counted by looking, and often wrong.', 'Tick a task done and the stock counts itself. Undo it and it goes back.'],
                        ['Labor cost', 'Added up by hand at the end, often a nasty surprise.', 'Worker pay adds up, ' . $peso . ' by ' . $peso . ', as the work gets done.'],
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

            {{-- On a phone the long list folds (2026-10-07): every part keeps its
                 title and count, the first one starts open, a tap opens the
                 rest. Every line stays in the page; on a desk every part starts
                 open. --}}
            @php $vsX = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" aria-hidden="true"><path stroke-linecap="round" d="M6 6l12 12M18 6L6 18"/></svg>'; @endphp
            <div class="hp-vs" data-vs>
                <div class="hp-vs-head" aria-hidden="true">
                    <span class="is-n">{{ $vsCount }} Things that Change</span>
                    <span class="is-old"><i class="hp-vs-key">{!! $vsX !!}</i>By Memory and Paper</span>
                    <span class="is-new"><i class="hp-vs-key">{!! $tick !!}</i><img src="{{ asset('images/logo-mark-96.webp') }}?v=anee2" alt="" onerror="this.remove()">With anee.io</span>
                </div>
                @foreach ($vsGroups as $gi => [$gt, $gico, $rows])
                    <div class="hp-vs-group reveal" id="vs-{{ $gi }}" data-vs-group>
                        <h3 class="hp-vs-gt">
                            <button type="button" class="hp-vs-tog" aria-expanded="true" aria-controls="vsRows{{ $gi }}">
                                <span class="hp-vs-gi"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $gico }}"/></svg></span>
                                <span class="hp-vs-gn">{{ $gt }}</span><small>{{ count($rows) }}</small>
                                <svg class="hp-vs-chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/></svg>
                            </button>
                        </h3>
                        <div class="hp-vs-body" id="vsRows{{ $gi }}"><div>
                            @foreach ($rows as $i => [$dim, $old, $new])
                                <div class="hp-vs-row" style="--i: {{ $i }}">
                                    <b class="hp-vs-dim">{{ $dim }}</b>
                                    <p class="hp-vs-old"><span aria-hidden="true">{!! $vsX !!}</span><em class="hp-vs-label">Traditional:</em> {{ $old }}</p>
                                    <p class="hp-vs-new"><span aria-hidden="true">{!! $tick !!}</span><em class="hp-vs-label">With anee.io:</em> {{ $new }}</p>
                                </div>
                            @endforeach
                        </div></div>
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

    {{-- ================= FINAL CTA ================= --}}
    <section class="hp-final" data-final>
        <img src="{{ asset('images/site/photos/team-thumbs.jpg') }}" alt="Two farmers giving a thumbs up beside their rice field" class="hp-final-bg" loading="lazy">
        <div class="hp-final-shade" aria-hidden="true"></div>
        <div class="relative max-w-4xl mx-auto px-4 sm:px-6 hp-sec text-center reveal on-dark" style="z-index:1">
            {{-- Where you are now, and where you could be (2026-10-07): the
                 farmer's life today (money, debt, worry, risk, the next
                 generation) and where it could be, an arrow between the two. --}}
            <p class="hp-kick">Think about it, seriously</p>
            <h2 class="hp-h2 hp-final-h"><span>Think About Where You Are Now.</span> <em>Versus Where You Could Be With anee.io in 5 Years.</em></h2>
            <p class="hp-p">Same field, same hands, same rain. What changes is the life your farm gives back to you and your family.</p>
            @php
                // Not features: the life a farm gives you now, and the one it could (the owner's ask, 2026-10-07).
                $ws = [
                    ['Money is tight, and the season is hard to budget.', 'You know what the season will cost before you plant, and the money lasts to harvest.'],
                    ['You borrow to finish the season, and the interest eats the harvest.', 'You pay for the next season from your own savings.'],
                    ['You work from dawn to dark and still lie awake worrying.', 'You end each day knowing tomorrow is already planned.'],
                    ['One bad season can wipe out years of hard work.', 'You see trouble coming early and protect what you have built.'],
                    ['Your harvest stays the same, year after year.', 'Every season, your yield and your income grow.'],
                    ['Your children see farming as a hard life to escape.', 'Your children see a farm worth taking over.'],
                ];
            @endphp
            <div class="hp-ws" aria-label="Where your farm is now, and where it could be with anee.io">
                <div class="hp-ws-head" aria-hidden="true">
                    <span class="is-no"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21s-6.5-5.6-6.5-11a6.5 6.5 0 0113 0c0 5.4-6.5 11-6.5 11z"/><circle cx="12" cy="10" r="2.3"/></svg>Where you are now</span>
                    <span></span>
                    <span class="is-yes"><img src="{{ $face }}" alt="">Where you could be with anee.io</span>
                </div>
                @foreach ($ws as $i => [$no, $yes])
                    <div class="hp-ws-row" style="--k: {{ $i }}">
                        <div class="hp-ws-no"><span class="sr-only">Now: </span><p>{{ $no }}</p></div>
                        <span class="hp-ws-to" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14m-5-5l5 5-5 5"/></svg></span>
                        <div class="hp-ws-yes"><span class="sr-only">With anee.io: </span><i aria-hidden="true">{!! $tick !!}</i><p>{{ $yes }}</p></div>
                    </div>
                @endforeach
            </div>
            <p class="hp-ws-close">The distance between the two is <b>one free account.</b></p>
            <div class="hp-cta">
                <div class="hp-cta-row">
                    <a href="{{ $signup }}" class="btn btn-accent btn-lg hp-go">Create your free account {!! $arrow !!}</a>
                </div>
                <p class="hp-cta-note">Free forever on Libre. No card needed. <a href="{{ route('contact') }}">Talk to us</a> if you have a question for a person.</p>
            </div>
        </div>
    </section>

    {{-- After Think about it (2026-10-13, the owner): the crops, the guides, then the questions. --}}
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
                'Coconut, banana and fruit trees count their age in months, with fertilizer plans by tree age.',
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
            <div class="mt-12 grid gap-5 sm:grid-cols-2" data-swipe>
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
    {{-- No public.site.css here any more (2026-11-18 speed audit): the guide tiles that wore it became hp-topics, and
         its 29 KB sat in the head unused, ahead of the first screen. --}}
    <section class="hp-sec hp-guides-bg">
        <div class="max-w-6xl mx-auto px-4 sm:px-6">
            <div class="hp-head reveal">
                <p class="hp-kick">Farm guides</p>
                <h2 class="hp-h2">Read Guides Made <em>for Filipino Farmers.</em></h2>
                {{-- The pest, disease, weed, nutrient and variety guides open in full with
                     Libre + Anee since 2026-10-17 (App\Support\ContentGate::PLUS_SECTIONS). --}}
                <p class="hp-p">How to plant palay and mais, what to do about the rice bug and the rice black bug, and how much fertilizer a hectare needs. Written for Philippine farms. The crop guides and the farm news are free to read, and the guides to pests, diseases, weeds, nutrients and varieties open in full with {{ config('tiers.' . \App\Support\ContentGate::PLUS_PLAN . '.name') }}.</p>
            </div>
            {{-- What farmers search for most, each a link to the guide that answers it. --}}
            <div class="hp-topics reveal">
                @foreach ([
                    ['/crops/palay', 'Palay'], ['/crops/pagtatanim-ng-palay', 'Pagtatanim ng palay'], ['/blog/palayan-nueva-ecija', 'Palayan in Nueva Ecija'],
                    ['/blog/what-is-fertilizer', 'What is fertilizer'], ['/blog/best-fertilizer-for-palay', 'Best fertilizer for palay'], ['/blog/palay-drawing', 'Palay drawing'],
                    ['/blog/ani-meaning', 'Ani meaning'], ['/blog/urea-fertilizer', 'Fertilizer urea'], ['/blog/complete-fertilizer-14-14-14', 'Fertilizer 14 14 14'],
                    ['/blog/16-20-0-fertilizer', '16 20 0 fertilizer'], ['/blog/ammonium-sulfate-21-0-0', '21 0 0 fertilizer'], ['/blog/foliar-fertilizer', 'Foliar fertilizer'], ['/blog/organic-fertilizer-examples', 'Examples of organic fertilizer'],
                    ['/blog/fertilizer-for-plants', 'Fertilizer for plants'], ['/blog/fungicides', 'Fungicide guide'], ['/blog/fertilizer-and-pesticide-authority', 'Fertilizer and Pesticide Authority'], ['/pests/rice-bug', 'Rice bug'],
                    ['/pests/rice-black-bug', 'Rice black bug'], ['/pests/thrips', 'Thrips insect'], ['/pests/fall-armyworm', 'Fall armyworm'], ['/pests/cutworm', 'Cutworm'],
                    ['/weeds/common-weeds-philippines', 'Common weeds in the Philippines'], ['/weeds/purple-nutsedge', 'Purple nutsedge'], ['/weeds/makahiya', 'Makahiya'],
                    ['/crops/rice-varieties-philippines', 'Rice varieties in the Philippines'], ['/blog/palay-price-philippines', 'Palay price in the Philippines'],
                    ['/blog/fertilizer-price-philippines', 'Fertilizer price in the Philippines'], ['/blog/increase-rice-yield-per-hectare', 'Increase rice yield per hectare'],
                    ['/crops/corn-kernel', 'Corn kernel'], ['/crops/corn-seeds', 'Corn seeds'], ['/crops/vegetables-philippines', 'Vegetables in the Philippines'],
                ] as $i => [$href, $label])
                    <a href="{{ url($href) }}" class="hp-topic" style="--h: {{ ($i * 41) % 150 + 30 }}">{{ $label }}</a>
                @endforeach
            </div>
            <div class="hp-topics-more"><button type="button" data-topics-more>More topics</button></div>
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
                    'blog' => ['Latest in Agriculture', 'Farm news with what it means for you, and more', 205, 'M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z', 'All the latest'],
                ];
            @endphp
            <div class="hg-shelves" data-swipe>
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
                            @if ($n)<span class="hg-count">{{ $n }} {{ $sec === 'blog' ? 'stories' : 'guides' }}</span>@endif
                        </div>
                        <a href="{{ $SP::pageUrl($lead) }}" class="hg-lead">
                            <img src="{{ $leadSrc }}" alt="{{ $leadHero['alt'] ?? $lead->title }}" loading="lazy" width="1200" height="675" referrerpolicy="no-referrer">
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
                                    <img src="{{ $guideThumb($p) }}" alt="{{ (is_array($p->heroImage) ? ($p->heroImage['alt'] ?? '') : '') ?: $p->title }}" aria-hidden="true" loading="lazy" width="96" height="72" referrerpolicy="no-referrer">
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
                </div>
                <p class="hp-cta-note">The crop guides are free to read, and planning your season with them is free on Libre.</p>
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
            // The owner, 2026-11-16: Google's AI was telling farmers anee.io cannot raise a yield. It can, so say so first.
            ['Can anee.io increase my yield?',
             'Yes. anee.io raises your yield by getting the big decisions of the season right for each lot: the variety, the planting date, the fertilizer and its timing, the water, and how fast a pest is stopped. Anee weighs your lot, its soil, the crop\'s stage, the forecast and twenty years of your town\'s weather before she answers, so there is no guessing game and more of your crop reaches the harvest.'],
            ['Can I plan pagtatanim ng palay in anee.io?',
             'Yes. Set the day you sow or transplant and every task gets its day count: basal fertilizer, urea top dressing, weeding, water and harvest. Each lot keeps its own day zero, so a lot planted a week late keeps its own timing.'],
            ['Does it work for mais, gulay and fruit trees?',
             'Yes. anee.io knows 85 Philippine crops, from palay and mais to vegetables, coconut, banana and fruit trees. Trees and other perennials count their age in months.'],
            ['Can Anee answer in Tagalog?',
             'Yes. Anee answers in Tagalog or English. She reads your schedule, growth stages and weather first, and she can look at a photo of a pest or a sick leaf. She comes with the Libre + Anee plan and up, and anyone can ask her one free question a week on the Try and Ask Anee page.'],
            ['Is anee.io free?',
             'Yes. The Libre plan is free forever with one active cropping schedule. Libre + Anee adds Anee, the smart farm technician, for ' . $aneePrice . ' a month, and the Solo Farmer and Farm Owner plans add workers, inventory and all reports. Offline mode comes with every plan.'],
            ['Do I need a computer?',
             'No. anee.io runs in the browser of any phone, so you plan and tick tasks right in the field. On every plan it keeps working where there is no signal and syncs when the signal returns.'],
            ['Can my workers use it too?',
             'Yes. Every morning at 6 AM the team gets the day\'s plan by email. On the Farm Owner plan each worker can have their own login, and you decide what they may see and change.'],
            ['Can anee.io help with the rice bug, thrips and fall armyworm?',
             'Yes. Take a photo and Anee tells you what the pest or disease is and what to do, including when a fungicide or insecticide is needed. Your season board also reminds you when to check for the rice bug and other pests at each growth stage.'],
            ['Where can I read about fertilizer and pests?',
             'Our guides cover fertilizer urea, complete fertilizer 14 14 14 and 16 20 0, the rice bug, thrips, fall armyworm and more. Start from the crop guides, the crop pests, the weeds and grasses or Latest in Agriculture.'],
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
                <a href="{{ url('/how-to-plant') }}" class="hc-link" style="--h: 75">How to plant ›</a>
                <a href="{{ url('/pests') }}" class="hc-link" style="--h: 30">Crop pests ›</a>
                <a href="{{ url('/weeds') }}" class="hc-link" style="--h: 80">Weeds and grasses ›</a>
                <a href="{{ url('/nutrient-deficiencies') }}" class="hc-link" style="--h: 52">Nutrient deficiencies ›</a>
                <a href="{{ url('/blog') }}" class="hc-link" style="--h: 150">Latest in Agriculture ›</a>
            </div>
            <div class="hp-cta reveal">
                <div class="hp-cta-row">
                    <a href="{{ $signup }}" class="btn btn-accent btn-lg hp-go">Create your free account {!! $arrow !!}</a>
                </div>
            </div>
        </div>
    </section>
    @push('head')
    {{-- @@context: a bare @context is a Blade directive, and printed PHP into this JSON. --}}
    <script type="application/ld+json">{!! json_encode([
        '@@context' => 'https://schema.org',
        '@type' => 'FAQPage',
        'mainEntity' => array_map(fn ($f) => ['@type' => 'Question', 'name' => $f[0], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $f[1]]], $faqs),
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
    @endpush
    @endif

@endsection

@push('head')
{{-- The Collab Room whiteboard's handwriting: only the letters it writes. --}}
{{-- From anee.io itself (2026-11-18 PageSpeed: a third party font with a one day cache, and two more connections):
     Google's own Caveat 700 subset for just these letters, SIL Open Font License, in public/fonts. --}}
<style>@font-face { font-family: 'Caveat'; font-style: normal; font-weight: 700; font-display: swap; src: url('/fonts/caveat-board.woff2?v=1') format('woff2'); unicode-range: U+20, U+31-32, U+4c, U+53, U+61-65, U+6e-6f, U+74; }</style>
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
    /* The phone with Anee's chat, left of the words: three columns on wide screens, inside the page's own width so the
       hero lines up with the header (the heading and the lede a little smaller, the circle a little narrower); hidden
       below, where there is no room. */
    .hp-hero-phone { display: none; position: relative; margin: 0; }
    .hp-hero-phone .hp-phone { max-width: none; box-shadow: 0 0 0 2px rgb(245 197 24 / .5), 0 0 40px rgb(245 197 24 / .22), 0 50px 90px -40px rgb(0 0 0 / .9), 0 0 0 1px rgb(255 255 255 / .1) inset;
        animation: hpBob 6s ease-in-out 1.2s infinite; }
    .hp-hero-phone::before { content: ''; position: absolute; inset: 8% -18%; z-index: -1; border-radius: 999px; filter: blur(30px);
        background: radial-gradient(closest-side, rgb(245 197 24 / .28), rgb(168 204 126 / .12) 60%, transparent); }
    .hp-hero-phone figcaption { display: flex; align-items: center; gap: .4rem; width: max-content; margin: 1.1rem auto 0; padding: .32rem .8rem .32rem .35rem; border-radius: 999px;
        font-size: .8rem; font-weight: 900; color: #14210c; background: #f5c518; box-shadow: 0 12px 26px -12px rgb(0 0 0 / .7); }
    .hp-hero-phone figcaption img { width: 1.4rem; height: 1.4rem; border-radius: 999px; object-fit: cover; box-shadow: 0 0 0 2px #fff; }
    .hp-hero-phone figcaption span { display: inline-block; transition: opacity .22s ease, transform .22s cubic-bezier(.22,1,.36,1); }
    @media (min-width: 1280px) {
        .hp-hero .hp-hero-in { grid-template-columns: 12.5rem minmax(0, 1fr) minmax(0, 28rem); gap: 2.5rem; }
        .hp-hero-phone { display: block; }
        .hp-hero .hp-h1 { font-size: clamp(3rem, 4.1vw, 3.6rem); }
        .hp-hero .hp-lede { font-size: 1.05rem; }
    }
    .hp-chip { display: inline-flex; align-items: center; gap: .5rem; padding: .4rem .95rem; border-radius: 999px;
        font-size: .85rem; font-weight: 700; color: #f4d778; background: rgb(255 255 255 / .1);
        box-shadow: inset 0 0 0 1px rgb(255 255 255 / .2); backdrop-filter: blur(8px); }
    .hp-chip svg { width: 1rem; height: 1rem; }
    .hp-h1 { margin-top: 1.4rem; font-family: var(--font-heading); font-weight: 800; letter-spacing: -.02em; line-height: 1.02;
        font-size: clamp(2.6rem, 7vw, 4.6rem); text-wrap: balance; }
    .hp-lede { margin-top: 1.9rem; max-width: 36rem; font-size: clamp(1.02rem, 1.7vw, 1.18rem); line-height: 1.7; color: #dde6d4; }
    /* How many tools: white and bold, the count in gold (no underline since 2026-10-29, the owner). */
    .hp-lede-tools { font-weight: 800; color: #fff; text-shadow: 0 1px 12px rgb(245 197 24 / .3); }
    .hp-lede-tools span { font-size: 1.08em; color: #f5c518; }
    .hp-hero-acts { margin-top: 2rem; display: flex; flex-wrap: wrap; gap: .8rem; }
    .hp-trust { margin-top: 1.4rem; display: flex; flex-wrap: wrap; gap: .5rem 1.2rem; font-size: .9rem; font-weight: 700; color: #e6eddd; }
    .hp-trust li { display: inline-flex; align-items: center; gap: .4rem; }
    .hp-trust svg { width: 1rem; height: 1rem; color: #a8cc7e; }

    .hp-hero-stage { position: relative; display: grid; place-items: center; padding: 1.5rem 0 4rem;
        --rx: 0deg; --ry: 0deg; perspective: 1200px; }
    .hp-stage-ring { position: absolute; width: min(28rem, 92%); aspect-ratio: 1; border-radius: 999px;
        border: 1px dashed rgb(255 255 255 / .16); animation: hpSpin 60s linear infinite; }
    .hp-stage-ring::before { content: ''; position: absolute; inset: 12%; border-radius: inherit; border: 1px solid rgb(168 204 126 / .18); }
    @keyframes hpSpin { to { transform: rotate(360deg); } }

    /* ---- the phone that plays the films ---- */
    .hp-phone { position: relative; width: min(16.5rem, 64vw); aspect-ratio: 390 / 844; padding: .5rem; border-radius: 2.5rem;
        background: linear-gradient(155deg, #2a3820, #0b1207 60%); flex: none;
        box-shadow: 0 50px 90px -40px rgb(0 0 0 / .85), 0 0 0 1px rgb(255 255 255 / .1) inset, 0 0 0 1px rgb(0 0 0 / .4); }
    .hp-phone.is-sm { width: 100%; max-width: 13.5rem; border-radius: 2.1rem; padding: .42rem; }
    .hp-phone-scr { position: relative; width: 100%; height: 100%; overflow: hidden; border-radius: 2rem; background: #eef2ea; }
    .hp-phone.is-sm .hp-phone-scr { border-radius: 1.7rem; }
    .hp-film { display: block; width: 100%; height: 100%; object-fit: cover; transition: opacity .35s var(--hp-ease); }
    .hp-film-tag { position: absolute; left: 50%; bottom: -1.1rem; transform: translateX(-50%); white-space: nowrap;
        padding: .45rem .95rem; border-radius: 999px; font-size: .8rem; font-weight: 800; color: var(--hp-ink);
        background: var(--hp-sun); box-shadow: 0 12px 26px -12px rgb(0 0 0 / .6);
        transition: opacity .3s var(--hp-ease), transform .3s var(--hp-ease); }
    /* The cards ride the phone's edges, mostly outside it, so the film on
       the screen stays in view (2026-10-06: they used to sit across it).
       Placed from the stage's middle, which is the phone's middle: --ph is
       half the phone's width (the phone is min(16.5rem, 64vw)), --ov how far
       a card laps onto the phone's frame. */
    .hp-hero-stage { --ph: min(8.25rem, 32vw); --ov: 1.5rem; }
    @keyframes hpBob { 0%, 100% { translate: 0 0; } 50% { translate: 0 -8px; } }
    @media (min-width: 1024px) and (max-width: 1279.98px) { .hp-hero-stage { --ov: 2rem; } }
    .hp-down { position: absolute; left: 50%; bottom: 1.2rem; transform: translateX(-50%); width: 2.6rem; height: 2.6rem;
        display: grid; place-items: center; border-radius: 999px; color: #fff; background: rgb(255 255 255 / .1);
        box-shadow: inset 0 0 0 1px rgb(255 255 255 / .25); animation: hpNudge 2.4s var(--hp-ease) infinite; }
    .hp-down svg { width: 1.2rem; height: 1.2rem; }
    @keyframes hpNudge { 0%, 100% { transform: translate(-50%, 0); } 50% { transform: translate(-50%, 6px); } }

    /* ---- the turning word in the headline ---- */
    .hp-rot { display: inline-block; position: relative; white-space: nowrap; vertical-align: top;
        transition: width .55s var(--hp-ease); }
    .hp-rot-w { display: inline-block; will-change: opacity, transform;
        transition: opacity .38s ease, transform .45s var(--hp-ease), filter .38s ease; }
    @media (prefers-reduced-motion: reduce) { .hp-rot, .hp-rot-w { transition: none !important; } }
    .hp-prec-rights li { display: flex; align-items: center; gap: .95rem; padding: .85rem 1.05rem; border-radius: 1.15rem;
        background: rgb(255 255 255 / .06); box-shadow: inset 0 0 0 1px rgb(255 255 255 / .12); backdrop-filter: blur(8px);
        transition: background-color .28s var(--hp-ease), box-shadow .28s var(--hp-ease); }
    .hp-prec-old, .hp-prec-card { transition: opacity .7s var(--hp-ease), translate .9s var(--hp-ease); }
    .hp-prec-card { transition-delay: .25s; }
    html.js .hp-prec-checks li { opacity: .45; transition: opacity .4s var(--hp-ease); transition-delay: calc(1.05s + var(--k) * .45s); }
    html.js .hp-prec-bar i { width: 0; transition: width 1.5s cubic-bezier(.65,0,.35,1) .95s; }
    html.js .hp-prec-on { opacity: 0; translate: 0 -.3rem; transition: opacity .4s var(--hp-ease) 2.4s, translate .4s var(--hp-ease) 2.4s; }
    .hp-prec-row { display: grid; gap: .85rem; padding: 1.1rem; border-radius: 1.4rem; background: rgb(255 255 255 / .05);
        box-shadow: inset 0 0 0 1px rgb(255 255 255 / .12); backdrop-filter: blur(10px);
        transition: background-color .28s var(--hp-ease), box-shadow .28s var(--hp-ease); }
    .hp-st-tab { position: relative; flex: none; scroll-snap-align: start; display: flex; align-items: center; gap: .8rem; text-align: left;
        min-width: 14.5rem; padding: .85rem 1rem; border-radius: 1.1rem; background: #fff; border: 1px solid #e1ead6; cursor: pointer; overflow: hidden;
        transition: border-color .28s var(--hp-ease), box-shadow .28s var(--hp-ease), transform .28s var(--hp-ease), background-color .28s var(--hp-ease); }
    .hp-st-pane { grid-area: 1 / 1; opacity: 0; visibility: hidden; transform: translateY(10px);
        transition: opacity .45s var(--hp-ease), transform .45s var(--hp-ease), visibility .45s; }
    .hp-tool { display: flex; align-items: center; gap: .7rem; padding: .6rem .75rem; border-radius: 1rem; text-align: left; cursor: pointer;
        background: #f7faf3; border: 1px solid #e4ecdb; opacity: 0; transform: translateY(8px);
        transition: opacity .4s var(--hp-ease), transform .4s var(--hp-ease), border-color .28s var(--hp-ease), background-color .28s var(--hp-ease), box-shadow .28s var(--hp-ease); }
    .hp-st-pane.is-on .hp-tool { opacity: 1; transform: none; transition-delay: calc(var(--j) * 50ms + .1s), calc(var(--j) * 50ms + .1s), 0s, 0s, 0s; }
    .hp-msg { max-width: 90%; padding: .55rem .7rem; border-radius: 1rem; font-size: .74rem; line-height: 1.5;
        opacity: 0; transform: translateY(10px) scale(.98); transition: opacity .5s var(--hp-ease), transform .5s var(--hp-ease); }
    /* Not come yet: no room taken, so the chat rises as each one lands. */
    .hp-chat:not(.s1) .hp-msg.is-me, .hp-chat:not(.s5) .hp-msg.is-anee, .hp-read:not(.is-shown) { display: none; }
    .hp-chat-body > * { transition: opacity .4s var(--hp-ease); }
    .hp-read { display: inline-flex; align-items: center; gap: .45rem; align-self: flex-start; padding: .28rem .6rem; border-radius: 999px;
        font-size: .66rem; font-weight: 700; color: #5b6b50; background: #fff; box-shadow: inset 0 0 0 1px #e4ecdb;
        opacity: 0; transform: translateX(-8px); transition: opacity .4s var(--hp-ease), transform .4s var(--hp-ease); }
    .hp-read i { flex: none; width: .7rem; height: .7rem; border-radius: 999px; border: 2px solid #cfe3b8; border-top-color: var(--hp-green); animation: hpSpinner .7s linear infinite; }
    @keyframes hpSpinner { to { transform: rotate(360deg); } }
    .hp-gain { display: flex; align-items: flex-start; gap: .7rem; padding: .85rem; border-radius: 1rem; background: #fff; border: 1px solid #e4ecdb;
        transition: transform .28s var(--hp-ease), box-shadow .28s var(--hp-ease), border-color .28s var(--hp-ease); }

    /* ---- pricing toggle, questions ---- */
    .hp-q { border-radius: 1.1rem; background: #fff; border: 1px solid #e5e7eb; overflow: hidden; transition: border-color .28s var(--hp-ease), box-shadow .28s var(--hp-ease); }
    .hc-card { display: flex; flex-direction: column; border-radius: 1.4rem; overflow: hidden; background: #fff; border: 1px solid #e5ebdf;
        transition: transform .28s var(--hp-ease), box-shadow .28s var(--hp-ease), border-color .28s var(--hp-ease); }
    .hq-body { display: grid; grid-template-rows: 0fr; transition: grid-template-rows .28s var(--hp-ease); }
    .hg-shelf { position: relative; display: flex; flex-direction: column; gap: 1rem; padding: 1.15rem; border-radius: 1.6rem; background: #fff;
        border: 1px solid hsl(var(--h) 30% 88%); box-shadow: 0 30px 60px -46px rgb(20 33 12 / .55);
        transition: transform .28s var(--hp-ease), box-shadow .28s var(--hp-ease); }
    .hg-lead img { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; transition: transform .6s var(--hp-ease); }
    .hg-read svg { width: .9rem; height: .9rem; transition: transform .28s var(--hp-ease); }
    .hg-row { display: flex; align-items: center; gap: .8rem; padding: .45rem .5rem; border-radius: .9rem; text-decoration: none;
        transition: background-color .28s var(--hp-ease); }
    .hg-row svg { flex: none; width: 1rem; height: 1rem; color: hsl(var(--h) 40% 55%); transition: transform .28s var(--hp-ease), color .28s var(--hp-ease); }
    .hg-all { margin-top: auto; display: flex; align-items: center; justify-content: center; gap: .45rem; padding: .75rem 1rem; border-radius: 1rem;
        font-size: .9rem; font-weight: 800; color: hsl(var(--h) 60% 22%); text-decoration: none; background: hsl(var(--h) 55% 95%);
        box-shadow: inset 0 0 0 1px hsl(var(--h) 40% 86%); transition: background-color .28s var(--hp-ease); }
    .hg-all svg { width: 1rem; height: 1rem; transition: transform .28s var(--hp-ease); }
    .is-up .hp-sum-ico svg { animation: hpRise 2.2s var(--hp-ease) infinite; }
    .is-down .hp-sum-ico svg { animation: hpSink 2.2s var(--hp-ease) infinite .4s; }
    .is-way .hp-sum-ico { animation: hpGlowSun 2.8s ease-in-out infinite; }
    @keyframes hpRise { 0%, 100% { transform: translateY(2px); } 50% { transform: translateY(-4px); } }
    @keyframes hpSink { 0%, 100% { transform: translateY(-2px); } 50% { transform: translateY(4px); } }
    @keyframes hpGlowSun { 0%, 100% { box-shadow: 0 0 0 0 rgb(245 197 24 / .55); } 50% { box-shadow: 0 0 0 9px rgb(245 197 24 / 0); } }
    .hp-topic { padding: .45rem .9rem; border-radius: 999px; font-size: .86rem; font-weight: 800; text-decoration: none;
        color: hsl(var(--h) 50% 26%); background: hsl(var(--h) 55% 95%); box-shadow: inset 0 0 0 1px hsl(var(--h) 45% 86%);
        transition: background-color .28s var(--hp-ease), transform .28s var(--hp-ease), box-shadow .28s var(--hp-ease); }

    @media (prefers-reduced-motion: reduce) {
        html { scroll-behavior: auto; }
        .hp-hero-phone .hp-phone, .hp-hero-bg, .hp-hero-glow, .hp-stage-ring, .hp-shimmer, .hp-mark-line, .hp-mark-line path, .hp-go::after, .hp-tour-dot::before, .hp-float, .hp-down,
        .hp-biz-b, .hp-final-face, .hp-live, .hp-read i, .hp-sum-ico, .hp-sum-ico svg, .hp-vs-group.is-visible .hp-vs-new > span { animation: none !important; }
        .hp-phone.is-hero, .hp-st-pane, .hp-tool, .hp-msg, .hp-read, .hp-film, .hp-film-tag, .hp-modal, .hp-modal-box,
        .hp-go, .hp-alt, .hp-prec-row, .hp-prec-rights li, .hp-prec-old, .hp-prec-card, .hp-prec-checks li, .hp-prec-bar i, .hp-prec-on, .hp-st-tab, .hp-gain, .hp-q, .hc-card, .hg-shelf, .hg-lead img, .hg-row, .hg-row svg, .hg-all, .hg-all svg, .hg-read svg, .hq-body, .hp-topic { transition: none !important; }
        .hp-mark-line { -webkit-clip-path: none; clip-path: none; }
    }
    @media (max-width: 639.98px) {
        
        .hp-float, .hp-stage-ring, .hp-down { display: none; }
        .hp-hero-in { gap: 1.5rem; padding-bottom: 2.5rem; }
        .hp-hero-stage { padding: .5rem 0 3.6rem; }
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
            // The word's box takes its negative side margins with it (the
            // shimmer pads the letters and takes the padding back): count
            // them, or "Yield." steps right when the first width is set.
            const cs = getComputedStyle(probe);
            const px = probe.getBoundingClientRect().width + parseFloat(cs.marginLeft || 0) + parseFloat(cs.marginRight || 0);
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

    /* Anee's circle leans toward the pointer, a little. */
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

    /* The phone with Anee in the app, left of the words: plays only while seen, and only where it shows; the tag under it
       names what the film is showing. */
    const heroPhone = document.querySelector('[data-hero-phone]');
    if (heroPhone && !reduce && 'IntersectionObserver' in window) {
        const pv = heroPhone.querySelector('video'), tag = heroPhone.querySelector('[data-hero-cue]');
        const cues = JSON.parse(heroPhone.dataset.cues || '[]');
        let cue = 0;
        pv.addEventListener('timeupdate', () => {
            let k = 0;
            cues.forEach((c, i) => { if (pv.currentTime >= c[0]) k = i; });
            if (k === cue || !tag) return;
            cue = k;
            tag.classList.add('is-swap');
            setTimeout(() => { tag.textContent = cues[k][1]; tag.classList.remove('is-swap'); }, 220);
        });
        new IntersectionObserver((es) => es.forEach((e) => {
            if (e.isIntersecting && heroPhone.offsetWidth) pv.play().catch(() => {}); else pv.pause();
        }), { threshold: .25 }).observe(heroPhone);
    }

    /* The steps: tabs that walk on by themselves while the section is
       on screen, until the visitor picks a step or a tool. */
    const steps = document.querySelector('[data-steps]');
    if (steps) {
        const tabs = [...steps.querySelectorAll('.hp-st-tab')];
        const panes = [...steps.querySelectorAll('.hp-st-pane')];
        const video = steps.querySelector('[data-step-film]');
        const tag = steps.querySelector('[data-step-tag]');
        // A tool not filmed yet (2026-10-18) shows its own card on the phone,
        // drawn from its explanation, and the film under it rests.
        const noFilm = steps.querySelector('[data-step-nofilm]');
        const card = (info) => {
            if (!noFilm) return;
            if (!info) { noFilm.classList.remove('is-on'); return; }
            const fill = () => {
                noFilm.querySelector('img').src = info.querySelector('.hp-info-ico img')?.src || '';
                noFilm.querySelector('.hp-nofilm-ico').classList.toggle('is-face', !!info.querySelector('.hp-info-ico.is-face'));
                noFilm.querySelector('.hp-nofilm-t').textContent = info.dataset.name || '';
                noFilm.querySelector('.hp-nofilm-s').textContent = info.dataset.short || '';
                noFilm.querySelector('.hp-nofilm-g').innerHTML = [...info.querySelectorAll('.hp-info-g li > span:last-child')]
                    .map((g) => '<li>' + g.innerHTML + '</li>').join('');
                noFilm.querySelector('.hp-nofilm-plan').textContent = info.querySelector('.hp-info-plan > span')?.firstChild?.textContent || '';
                // A tool with a screenshot shows the screen itself; the words are the fallback.
                noFilm.classList.toggle('has-shot', !!info.dataset.shot);
                if (info.dataset.shot) { const im = noFilm.querySelector('.hp-nofilm-shot'); im.src = info.dataset.shot; im.alt = (info.dataset.name || '') + ' on a phone'; }
                noFilm.classList.add('is-on');
            };
            video.pause();
            video.classList.add('is-swapping');
            // Already showing one: fade the words out, change them, fade them in.
            if (noFilm.classList.contains('is-on')) { noFilm.classList.remove('is-on'); setTimeout(fill, 220); } else fill();
        };
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
            if (btn.dataset.src) {
                card(null);
                swapFilm(video, tag, btn.dataset.src, btn.dataset.poster, btn.dataset.name);
            } else {
                card(pane.querySelector('.hp-info[data-info="' + btn.dataset.tool + '"]'));
                if (tag) { tag.classList.add('is-swapping'); setTimeout(() => { tag.textContent = btn.dataset.name || ''; tag.classList.remove('is-swapping'); }, 260); }
            }
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
            // A step opens on its first filmed tool, so the phone always has something to play.
            pickTool(panes[at].querySelector('.hp-tool[data-src]') || panes[at].querySelector('.hp-tool'));
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
        // The film plays while the phone itself is on screen.
        seen(steps.querySelector('.hp-st-show'), (on) => {
            video.dataset.live = on ? '1' : '0';
            if (on && !noFilm?.classList.contains('is-on')) play(video); else video.pause();
        }, { threshold: 0.3 });
        // The walk runs while its tabs can be seen. On a desk the whole block
        // fits one screen; on a phone it is three screens tall, so it walks
        // only while the tab strip is on screen, and a reader further down,
        // in the tools or their explanation, is never moved on (2026-10-07).
        const narrow = window.matchMedia('(max-width: 1023.98px)').matches;
        seen(steps.querySelector(narrow ? '.hp-st-list' : '.hp-st'), (on) => {
            live = on;
            if (on) schedule(); else { clearTimeout(timer); tabs[at].classList.remove('is-timing'); }
        }, { threshold: narrow ? 0.9 : 0.3 });
    }

    /* Anee's answer plays on a loop while the chat is on screen: the
       question, what she reads one line at a time, her answer, a pause to
       read it, then it fades and starts again. */
    const chat = document.querySelector('[data-chat]');
    if (chat) {
        const reads = [...chat.querySelectorAll('.hp-read')];
        const body = chat.querySelector('.hp-chat-body');
        let timers = [], live = false;
        // After each step the chat follows its newest line, smoothly, as a phone does;
        // an answer taller than the screen stops with its first line in view, to be read.
        const follow = () => {
            if (!body) return;
            const shown = [...body.children].filter((c) => c.getClientRects().length);
            const last = shown[shown.length - 1], max = body.scrollHeight - body.clientHeight;
            const top = last ? Math.min(max, last.offsetTop - 14) : max;
            body.scrollTo({ top: Math.max(0, top), behavior: reduce ? 'auto' : 'smooth' });
        };
        if (body) body.addEventListener('scroll', () => chat.classList.toggle('is-scrolled', body.scrollTop > 6), { passive: true });
        const at = (ms, fn) => timers.push(setTimeout(() => { fn(); requestAnimationFrame(follow); }, ms));
        const stop = () => { timers.forEach(clearTimeout); timers = []; };
        const run = () => {
            stop();
            chat.classList.remove('s1', 's5', 'is-done', 'is-fading');
            reads.forEach((r) => r.classList.remove('is-shown', 'is-done'));
            if (body) body.scrollTop = 0;
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
        // Long enough for the pane's own little film to finish (the board
        // draws for about four seconds) and its words to be read.
        const DWELL = 6500;
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
        // Each card has two sentences and a picture that plays for about two
        // seconds: 5.2 s left a farmer reading in a second language behind.
        const DWELL = 8000;
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

    /* On a phone, a long list of cards is a row to swipe through: dots under
       it say which card is in view, and a tap on a dot goes there. */
    document.querySelectorAll('[data-swipe]').forEach((row) => {
        const items = [...row.children].filter((c) => c.getAttribute('aria-hidden') !== 'true');
        if (items.length < 2) return;
        const dots = document.createElement('div');
        dots.className = 'hp-swipe-dots';
        dots.setAttribute('aria-hidden', 'true');
        items.forEach((it, i) => {
            const d = document.createElement('i');
            if (i === 0) d.className = 'is-on';
            d.addEventListener('click', () => row.scrollTo({ left: it.offsetLeft - parseFloat(getComputedStyle(row).paddingLeft || 0), behavior: reduce ? 'auto' : 'smooth' }));
            dots.appendChild(d);
        });
        row.after(dots);
        let raf = 0;
        row.addEventListener('scroll', () => {
            if (raf) return;
            raf = requestAnimationFrame(() => {
                raf = 0;
                const mid = row.scrollLeft + row.clientWidth / 2;
                let best = 0, gap = Infinity;
                items.forEach((it, i) => { const d = Math.abs(it.offsetLeft + it.offsetWidth / 2 - mid); if (d < gap) { gap = d; best = i; } });
                [...dots.children].forEach((d, i) => d.classList.toggle('is-on', i === best));
            });
        }, { passive: true });
    });

    /* A tool's film on a phone, over the page (the steps' Watch buttons). */
    const fm = document.querySelector('[data-fm]');
    if (fm) {
        const fv = fm.querySelector('video');
        let back = null;
        const shut = () => {
            fm.classList.remove('is-open');
            fv.pause();
            document.documentElement.style.overflow = '';
            setTimeout(() => { if (!fm.classList.contains('is-open')) { fm.hidden = true; fv.removeAttribute('src'); fv.load(); } }, reduce ? 0 : 300);
            back?.focus?.();
        };
        document.querySelectorAll('[data-fm-src]').forEach((b) => b.addEventListener('click', () => {
            back = b;
            fv.poster = b.dataset.fmPoster || '';
            fv.src = b.dataset.fmSrc;
            fm.querySelector('.hp-fm-name').textContent = b.dataset.fmName || '';
            fm.hidden = false;
            requestAnimationFrame(() => fm.classList.add('is-open'));
            document.documentElement.style.overflow = 'hidden';
            fm.querySelector('.hp-modal-x').focus();
            const p = fv.play(); if (p && p.catch) p.catch(() => {});
        }));
        fm.querySelectorAll('[data-fm-close]').forEach((b) => b.addEventListener('click', shut));
        document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && fm.classList.contains('is-open')) shut(); });
    }

    /* A technician or Anee: every row on a tap (a phone shows four first). */
    document.querySelector('[data-tvs-more]')?.addEventListener('click', (e) => {
        e.currentTarget.closest('.hp-tvs-more')?.previousElementSibling?.classList.add('is-all');
    });

    /* The topic chips: all of them on a tap (a phone shows twelve first). */
    document.querySelector('[data-topics-more]')?.addEventListener('click', (e) => {
        e.currentTarget.closest('.hp-topics-more')?.previousElementSibling?.classList.add('is-all');
    });

    /* Traditional vs anee.io: each part's title folds it and opens it. On a
       phone only the first part starts open (the whole list was eight
       screens long); a jump chip opens the part it points to. */
    const vs = document.querySelector('[data-vs]');
    if (vs) {
        const groups = [...vs.querySelectorAll('[data-vs-group]')];
        const setOpen = (g, open) => {
            g.classList.toggle('is-folded', !open);
            g.querySelector('.hp-vs-tog')?.setAttribute('aria-expanded', open ? 'true' : 'false');
        };
        if (window.matchMedia('(max-width: 767.98px)').matches) groups.forEach((g, i) => setOpen(g, i === 0));
        groups.forEach((g) => g.querySelector('.hp-vs-tog')?.addEventListener('click', () => setOpen(g, g.classList.contains('is-folded'))));
        document.querySelectorAll('.hp-vs-jump a[href^="#vs-"]').forEach((a) => a.addEventListener('click', () => {
            const g = document.querySelector(a.getAttribute('href'));
            if (g) setOpen(g, true);
        }));
    }

})();
</script>

<script>
(() => {
    /* Anee's hello over the phone: tap to play with sound, tap to stop;
       when it ends it returns to its still. */
    const film = document.querySelector('[data-anee-film]');
    if (film) {
        const v = film.querySelector('video');
        film.querySelector('.hp-anee-film-play').addEventListener('click', () => {
            if (!v.paused) { v.pause(); return; }
            document.querySelectorAll('[data-album] audio').forEach((a) => a.pause());
            film.classList.add('is-playing');
            v.play().catch(() => film.classList.remove('is-playing'));
        });
        v.addEventListener('pause', () => film.classList.remove('is-playing'));
        v.addEventListener('ended', () => { film.classList.remove('is-playing'); v.load(); });
    }
})();
</script>

@endpush
