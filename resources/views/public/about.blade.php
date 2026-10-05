@extends('layouts.public')

@include('public.partials.site-css')
@include('public.partials.hp-base')

@php
    $ph = \App\Support\Region::ph();
    $signup = route('signup');
    $ask = route('ask.page');
    $face = asset('images/anee/avatar-160.jpg');
    $faceLg = asset('images/anee/avatar-512.jpg');
    $feature = fn ($slug) => $ph ? \App\Support\SitePages::url('features', $slug) : route('features');
    $arrow = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5-5 5M6 12h12"/></svg>';
@endphp

@section('title', $ph ? 'About anee.io: Named After Ani, the Palay Harvest' : 'About anee.io: Named After the Harvest')
@section('meta_description', $ph
    ? 'anee.io takes its name from ani, the harvest. A farm app built by agronomists for Filipino palay, mais and gulay farmers, from pagtatanim to the last sack.'
    : 'anee.io takes its name from ani, the Filipino word for harvest. A farm app built by agronomists to plan every cropping season, from sowing to the last sack.')

{{-- ABOUT (rebuilt 2026-10-06 in the homepage's look, hp- styles from
     public.partials.hp-base): who we are, where the name comes from, the
     mission, how the app came out of our own field work, why it is built
     for Philippine farms, what it does, what we stand for, and a way to
     start under every section. Plain words, no dashes, no invented numbers. --}}
@section('content')

    {{-- ================= HERO ================= --}}
    <section class="ab-hero">
        <img src="{{ asset('images/site/fields-aerial.jpg') }}" alt="{{ $ph ? 'Rice fields in the Philippines seen from above' : 'Rice fields seen from above' }}" class="ab-hero-bg" loading="eager" fetchpriority="high">
        <div class="ab-hero-shade" aria-hidden="true"></div>
        <div class="relative max-w-5xl mx-auto px-4 sm:px-6 ab-hero-in text-center on-dark" style="z-index:1">
            <span class="ab-chip animate-fade-up">About anee.io</span>
            <h1 class="ab-h1 animate-fade-up" style="animation-delay:.06s">
                Built by farmers, for
                <span class="hp-mark"><span class="hp-shimmer">{{ $ph ? 'Filipino farmers.' : 'every farmer.' }}</span><svg class="hp-mark-line" viewBox="0 0 300 24" preserveAspectRatio="none" aria-hidden="true"><path class="a" d="M5 15 C 55 7, 105 19, 160 12 S 255 6, 295 13"/></svg></span>
            </h1>
            <p class="ab-lede animate-fade-up" style="animation-delay:.12s">
                anee.io started as the system our own agronomists and technicians use on client farms. Now it is open
                to every farmer, with one goal: a higher yield and a better income from the same land.
            </p>
            <div class="hp-cta-row animate-fade-up" style="animation-delay:.18s; margin-top: 2rem">
                <a href="{{ $signup }}" class="btn btn-accent btn-lg hp-go">Create your free account {!! $arrow !!}</a>
                <a href="{{ route('how') }}" class="hp-alt is-glass">See how it works</a>
            </div>
        </div>
        <a href="#ab-name" class="ab-down" aria-label="Scroll to read more">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/></svg>
        </a>
    </section>

    {{-- ================= THE NAME ================= --}}
    <section class="hp-sec bg-white" id="ab-name">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 ab-two">
            <div class="ab-pics reveal">
                <img src="{{ asset('images/palay-08.jpg') }}" alt="{{ $ph ? 'Ripe palay in a palayan, ready for the ani' : 'A ripe rice field ready for harvest' }}" class="ab-pic-a" loading="lazy">
                <div class="ab-namecard">
                    <img src="{{ $faceLg }}" alt="Anee, the anee.io farm technician">
                    <div>
                        <b>ani <span>(ah nee)</span></b>
                        <small>Filipino for harvest</small>
                    </div>
                </div>
            </div>
            <div class="reveal">
                <p class="hp-kick">Our story</p>
                <h2 class="hp-h2">Our name comes from <em>ani, the harvest.</em></h2>
                <p class="hp-p">
                    <b>Ani</b> means harvest. It is the whole point of a season, and the one score every farmer keeps.
                    @if ($ph)
                        When a neighbor asks <em>kumusta ang ani?</em>, they mean how many cavans of palay came off the
                        field. Our page on the <a href="{{ url('/blog/ani-meaning') }}" class="ab-link">ani meaning</a> tells the rest.
                    @endif
                </p>
                <p class="hp-p">
                    Ani also gives its name to the technician inside the app. Ask <b>Anee</b> about your crop, send a photo
                    of a leaf, and she answers{{ $ph ? ' in Filipino or English' : '' }}, any time.
                </p>
                <div class="hp-cta is-left">
                    <div class="hp-cta-row">
                        <a href="{{ $ask }}" class="btn btn-accent btn-lg hp-go">Ask Anee a free question {!! $arrow !!}</a>
                    </div>
                    <p class="hp-cta-note">No account needed. One free question a week.</p>
                </div>
            </div>
        </div>
    </section>

    {{-- ================= THE MISSION ================= --}}
    <section class="hp-sec ab-mission spark-field on-dark">
        <div class="relative max-w-6xl mx-auto px-4 sm:px-6" style="z-index:1">
            <div class="hp-head reveal">
                <p class="hp-kick">Our mission</p>
                <h2 class="hp-h2">Help every {{ $ph ? 'Filipino ' : '' }}farmer grow <em>more from the same land.</em></h2>
                <p class="hp-p">
                    Fertilizer and fuel cost more every year, and the price at harvest does not keep up. We believe the
                    answer is not another product to buy. It is better farm management, made simple enough to follow
                    from a phone in the middle of the field.
                </p>
            </div>
            <div class="ab-pillars">
                @foreach ([
                    ['A higher yield', 'Every task on its right day, counted from each lot\'s own day zero, so the crop gets what it needs when it needs it.', '<path stroke-linecap="round" stroke-linejoin="round" d="M3 17l6-6 4 4 8-8m0 0h-5m5 0v5"/>'],
                    ['A lower cost', 'Every ' . ($ph ? 'peso' : 'dollar') . ' written down as it is spent, so nothing is wasted and nothing is forgotten.', '<path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>'],
                    ['A smarter next season', 'Every season leaves a record, so the next one starts from what worked on your own farm.', '<path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h5M20 20v-5h-5M5.1 15a7.5 7.5 0 0013.4 1.5M18.9 9A7.5 7.5 0 005.5 7.5"/>'],
                ] as $i => [$t, $p, $ico])
                    <div class="ab-pillar reveal" style="--reveal-delay: {{ $i * 0.1 }}s">
                        <span class="ab-pillar-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">{!! $ico !!}</svg></span>
                        <h3>{{ $t }}</h3>
                        <p>{{ $p }}</p>
                    </div>
                @endforeach
            </div>
            <div class="hp-cta reveal">
                <div class="hp-cta-row">
                    <a href="{{ $signup }}" class="btn btn-accent btn-lg hp-go">Start free and grow more {!! $arrow !!}</a>
                </div>
                <p class="hp-cta-note">Free forever on Libre. No card needed.</p>
            </div>
        </div>
    </section>

    {{-- ================= FROM OUR FIELDS TO YOURS ================= --}}
    <section class="hp-sec bg-brand-mesh bg-drift">
        <div class="max-w-5xl mx-auto px-4 sm:px-6">
            <div class="hp-head reveal">
                <p class="hp-kick">How anee.io began</p>
                <h2 class="hp-h2">From our fields <em>to yours.</em></h2>
                <p class="hp-p">anee.io was not made in an office. It grew out of years of work on real farms.</p>
            </div>
            <ol class="ab-path">
                @foreach ([
                    ['Years in the field', 'Our team has helped farmers grow bigger harvests of ' . \App\Support\Region::t('rice') . ', ' . \App\Support\Region::t('corn') . ' and more through research, technician support, fertilization and farm management, with results recognized here and abroad.', 'images/icons/soil-restoration.png'],
                    ['The system behind the results', 'To run client farms well, our technicians built a cropping schedule manager: every task counted from day zero, every cost written down, every season kept on record.', 'images/icons/calendar.png'],
                    ['Now in your hands', 'anee.io puts that same system on your phone, with Anee, the AI farm technician, to answer when a technician cannot be there.', 'images/icons/technician-support.png'],
                ] as $i => [$t, $p, $img])
                    <li class="ab-step reveal" style="--reveal-delay: {{ $i * 0.12 }}s">
                        <span class="ab-step-n">{{ $i + 1 }}</span>
                        <div class="ab-step-card">
                            <img src="{{ asset($img) }}" alt="" loading="lazy">
                            <div>
                                <h3>{{ $t }}</h3>
                                <p>{{ $p }}</p>
                            </div>
                        </div>
                    </li>
                @endforeach
            </ol>
            <div class="hp-cta reveal">
                <div class="hp-cta-row">
                    <a href="{{ $signup }}" class="btn btn-accent btn-lg hp-go">Use the same system, free {!! $arrow !!}</a>
                    <a href="{{ route('how') }}" class="hp-alt">See how it works</a>
                </div>
            </div>
        </div>
    </section>

    @if ($ph)
    {{-- ================= BUILT FOR PHILIPPINE FARMS ================= --}}
    <section class="hp-sec bg-white">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 ab-two is-top">
            <div class="reveal">
                <p class="hp-kick">Why we built it</p>
                <h2 class="hp-h2">Made for farms <em>in the Philippines.</em></h2>
                <p class="hp-p">
                    Farming still employs about one in five working Filipinos, and most farms here are small. Our guide to
                    <a href="{{ url('/blog/agriculture-in-the-philippine-economy') }}" class="ab-link">agriculture in the Philippine economy</a>
                    has the numbers. A small farm has no room for a missed fertilizer day or a pest found a week late, so
                    the plan has to be simple enough to follow in the field.
                </p>
                <p class="hp-p">
                    That is why anee.io counts every task from each lot's own day zero, the way agronomists do: days after
                    sowing, after transplanting or after planting. It works for
                    <a href="{{ url('/crops/pagtatanim-ng-palay') }}" class="ab-link">pagtatanim ng palay</a> in a Nueva Ecija
                    palayan, for mais in Isabela and Mindanao, and for gulay on the
                    <a href="{{ url('/crops/vegetables-philippines') }}" class="ab-link">vegetable farms of the Philippines</a>.
                </p>
                <p class="hp-p">
                    And because advice only helps when it comes on time, Anee reads your season before she answers. Ask
                    her in Filipino or English about a rice bug, a fertilizer like urea or complete 14 14 14, or a leaf that
                    looks wrong.
                </p>
                <div class="hp-cta is-left">
                    <div class="hp-cta-row">
                        <a href="{{ $signup }}" class="btn btn-accent btn-lg hp-go">Plan your crop free {!! $arrow !!}</a>
                    </div>
                </div>
            </div>
            <div class="reveal">
                <p class="ab-guides-k">Free guides from our team</p>
                <div class="ab-guides">
                    @foreach ([
                        ['/crops/palay', 'Palay', 'Palay in English, its growth stages, and how palay becomes rice.'],
                        ['/crops/corn-kernel', 'Corn kernel and mais', 'Parts of the corn plant, corn types and the corn kernel price.'],
                        ['/blog/urea-fertilizer', 'Fertilizer urea and 14 14 14', 'What each fertilizer does and when it goes in.'],
                        ['/problems/rice-bug', 'Rice bug and thrips', 'Signs, thresholds and control of the pests that hit hardest.'],
                        ['/blog/fertilizer-and-pesticide-authority', 'Fertilizer and Pesticide Authority', 'How to check that a product is registered before you buy.'],
                    ] as $i => [$href, $name, $line])
                        <a href="{{ url($href) }}" class="ab-guide reveal" style="--reveal-delay: {{ $i * 0.06 }}s">
                            <span class="ab-guide-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21v-9m0 0C12 7 8 5 4 5c0 4 3 7 8 7zm0 0c0-4 3-7 8-7 0 4-4 7-8 7z"/></svg></span>
                            <span class="ab-guide-t"><b>{{ $name }}</b><small>{{ $line }}</small></span>
                            <span class="ab-guide-go">{!! $arrow !!}</span>
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    </section>
    @endif

    {{-- ================= WHAT IT DOES ================= --}}
    <section class="hp-sec bg-gray-50">
        <div class="max-w-6xl mx-auto px-4 sm:px-6">
            <div class="hp-head reveal">
                <p class="hp-kick">The app</p>
                <h2 class="hp-h2">What anee.io <em>does for you.</em></h2>
                <p class="hp-p">One app for the whole season, from planning to the final report.</p>
            </div>
            <div class="ab-does">
                @foreach ([
                    ['cropping-calendar', 'images/icons/calendar.png', 'One plan for the whole season', 'Land preparation, sowing, fertilizer, crop protection and harvest on one cropping calendar.'],
                    ['ai-agricultural-technician', 'images/icons/chat.png', 'Anee, your AI farm technician', 'Ask about pests, fertilizer or a sick leaf, even with a photo, and get an answer for your own field.'],
                    ['farm-reports', 'images/icons/profit.png', 'Costs and profit you can see', 'Labor, materials and services add up as you go, and the reports match to the ' . ($ph ? 'peso' : 'cent') . '.'],
                    ['growth-stages-and-weather', 'images/icons/soil-restoration.png', 'Growth stages and weather', 'See where each lot stands today, what to do now, and the forecast for your field.'],
                    ['farm-workers-and-payroll', 'images/icons/tea.png', 'Workers and payroll', 'Put the right people on each task, tick who came, and the labor cost adds itself up.'],
                    ['offline-farm-app', 'images/icons/offline.png', 'Works without signal', 'Keep ticking tasks and writing notes at the far lot. It syncs when the signal comes back.'],
                ] as $i => [$slug, $img, $t, $p])
                    <a href="{{ $feature($slug) }}" class="ab-do reveal" style="--reveal-delay: {{ ($i % 3) * 0.07 }}s">
                        <span class="ab-do-ico"><img src="{{ asset($img) }}" alt="" loading="lazy"></span>
                        <h3>{{ $t }}</h3>
                        <p>{{ $p }}</p>
                        <span class="ab-do-more">Read more {!! $arrow !!}</span>
                    </a>
                @endforeach
            </div>
            <div class="hp-cta reveal">
                <div class="hp-cta-row">
                    <a href="{{ $signup }}" class="btn btn-accent btn-lg hp-go">Try it on your own farm, free {!! $arrow !!}</a>
                    <a href="{{ route('features') }}" class="hp-alt">See every feature</a>
                </div>
            </div>
        </div>
    </section>

    {{-- ================= VALUES ================= --}}
    <section class="hp-sec bg-white">
        <div class="max-w-6xl mx-auto px-4 sm:px-6">
            <div class="hp-head reveal">
                <p class="hp-kick">What we stand for</p>
                <h2 class="hp-h2">The values <em>behind every tool.</em></h2>
            </div>
            <div class="ab-values">
                @foreach ([
                    ['Farmer first', 'Everything we build starts with the real life of ' . ($ph ? 'Filipino farms' : 'working farms') . ': the budget, the weather and the labor.', '<path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>'],
                    ['Backed by science', 'Our schedules and protocols come from research and years of field results, not guesswork.', '<path stroke-linecap="round" stroke-linejoin="round" d="M9 3h6m-5 0v5.3L4.7 17a2 2 0 001.7 3h11.2a2 2 0 001.7-3L14 8.3V3"/>'],
                    ['Income and the land', 'Yield matters, and so does the soil. We plan for this season and the many seasons after it.', '<path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>'],
                    ['Simple on a phone', 'If it does not work on a phone in the middle of a ' . ($ph ? 'palayan' : 'rice field') . ', it does not go into the app.', '<path stroke-linecap="round" stroke-linejoin="round" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/>'],
                ] as $i => [$t, $p, $ico])
                    <div class="ab-value reveal" style="--reveal-delay: {{ $i * 0.07 }}s">
                        <span class="ab-value-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">{!! $ico !!}</svg></span>
                        <h3>{{ $t }}</h3>
                        <p>{{ $p }}</p>
                    </div>
                @endforeach
            </div>
            <div class="hp-cta reveal">
                <div class="hp-cta-row">
                    <a href="{{ $signup }}" class="btn btn-accent btn-lg hp-go">Create your free account {!! $arrow !!}</a>
                    <a href="{{ route('contact') }}" class="hp-alt">Talk to us</a>
                </div>
            </div>
        </div>
    </section>

    {{-- ================= LAST CALL ================= --}}
    <section class="ab-final">
        <img src="{{ asset('images/site/photos/team-thumbs.jpg') }}" alt="Two farmers giving a thumbs up beside their rice field" class="ab-final-bg" loading="lazy">
        <div class="ab-final-shade" aria-hidden="true"></div>
        <div class="relative max-w-4xl mx-auto px-4 sm:px-6 hp-sec text-center reveal on-dark" style="z-index:1">
            <img src="{{ $faceLg }}" alt="" class="ab-final-face">
            <h2 class="hp-h2">Reach your crop's full potential <em>this season.</em></h2>
            <p class="hp-p">Start planning with the same system our own technicians use. It is free to start.</p>
            <div class="hp-cta">
                <div class="hp-cta-row">
                    <a href="{{ $signup }}" class="btn btn-accent btn-lg hp-go">Create your free account {!! $arrow !!}</a>
                    <a href="{{ $ask }}" class="hp-alt"><img src="{{ $face }}" alt="" class="hp-face">Ask Anee a free question</a>
                </div>
                <p class="hp-cta-note">Free forever on Libre. No card needed.</p>
            </div>
        </div>
    </section>

@endsection

@push('head')
<style>
    /* ===================== ABOUT ===================== */
    .ab-hero { position: relative; isolation: isolate; overflow: hidden; color: #fff; }
    .ab-hero-bg { position: absolute; inset: 0; z-index: -2; width: 100%; height: 100%; object-fit: cover;
        animation: hpKenAb 24s ease-in-out infinite alternate; }
    @keyframes hpKenAb { from { transform: scale(1.05); } to { transform: scale(1.14) translate3d(1.5%, -1%, 0); } }
    .ab-hero-shade { position: absolute; inset: 0; z-index: -1;
        background: radial-gradient(80% 90% at 50% 40%, rgb(16 28 10 / .55), rgb(10 18 6 / .9)); }
    .ab-hero-in { padding-top: 6rem; padding-bottom: 7rem; }
    @media (min-width: 1024px) { .ab-hero-in { padding-top: 8rem; padding-bottom: 8.5rem; } }
    .ab-chip { display: inline-flex; align-items: center; padding: .4rem 1rem; border-radius: 999px; font-size: .78rem; font-weight: 800;
        letter-spacing: .1em; text-transform: uppercase; color: #f4d778; background: rgb(255 255 255 / .1);
        box-shadow: inset 0 0 0 1px rgb(255 255 255 / .22); backdrop-filter: blur(8px); }
    .ab-h1 { margin-top: 1.4rem; font-family: var(--font-heading); font-weight: 800; letter-spacing: -.02em; line-height: 1.04;
        font-size: clamp(2.4rem, 6.4vw, 4.3rem); text-wrap: balance; }
    .ab-lede { margin: 1.6rem auto 0; max-width: 40rem; font-size: clamp(1.02rem, 1.7vw, 1.2rem); line-height: 1.7; color: #dde6d4; text-wrap: pretty; }
    .ab-down { position: absolute; left: 50%; bottom: 1.4rem; transform: translateX(-50%); width: 2.6rem; height: 2.6rem; display: grid; place-items: center;
        border-radius: 999px; color: #fff; background: rgb(255 255 255 / .1); box-shadow: inset 0 0 0 1px rgb(255 255 255 / .25);
        animation: abNudge 2.4s var(--hp-ease) infinite; }
    .ab-down svg { width: 1.2rem; height: 1.2rem; }
    @keyframes abNudge { 0%, 100% { transform: translate(-50%, 0); } 50% { transform: translate(-50%, 6px); } }
    .ab-link { font-weight: 800; color: var(--hp-green); text-decoration: underline; text-decoration-color: rgb(74 124 42 / .35); text-underline-offset: 3px;
        transition: text-decoration-color .28s var(--hp-ease); }
    .ab-link:hover { text-decoration-color: var(--hp-green); }

    .ab-two { display: grid; gap: 3rem; align-items: center; }
    .ab-two.is-top { align-items: start; }
    @media (min-width: 1024px) { .ab-two { grid-template-columns: 1fr 1fr; gap: 4rem; } }
    .ab-pics { position: relative; padding-bottom: 3rem; }
    .ab-pic-a { width: 100%; aspect-ratio: 4 / 3; object-fit: cover; border-radius: 1.6rem; box-shadow: 0 30px 60px -36px rgb(20 33 12 / .7); }
    .ab-namecard { position: absolute; right: -.5rem; bottom: 0; display: flex; align-items: center; gap: .9rem; padding: .9rem 1.2rem .9rem .9rem;
        border-radius: 1.3rem; background: #fff; box-shadow: 0 24px 48px -24px rgb(20 33 12 / .6); animation: hpBobAb 6s ease-in-out infinite; }
    @media (max-width: 639.98px) { .ab-namecard { right: .5rem; } }
    .ab-namecard img { width: 3.6rem; height: 3.6rem; border-radius: 999px; object-fit: cover; box-shadow: 0 0 0 3px var(--hp-sun); }
    .ab-namecard b { display: block; font-family: var(--font-heading); font-size: 1.5rem; font-weight: 800; color: var(--hp-ink); line-height: 1; }
    .ab-namecard b span { font-size: .85rem; font-weight: 700; color: #6b7280; }
    .ab-namecard small { display: block; margin-top: .3rem; font-size: .85rem; font-weight: 700; color: var(--hp-green); }
    @keyframes hpBobAb { 0%, 100% { translate: 0 0; } 50% { translate: 0 -7px; } }

    .ab-mission { position: relative; overflow: hidden;
        background: radial-gradient(90% 120% at 15% 0%, #2d4a1a 0%, transparent 60%), linear-gradient(160deg, #10160c 0%, #1c2416 55%, #24301a 100%); }
    .ab-pillars { margin-top: 3rem; display: grid; gap: 1.1rem; }
    @media (min-width: 768px) { .ab-pillars { grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 1.3rem; } }
    .ab-pillar { padding: 1.5rem; border-radius: 1.4rem; background: rgb(255 255 255 / .07); box-shadow: inset 0 0 0 1px rgb(255 255 255 / .14);
        backdrop-filter: blur(10px); transition: transform .28s var(--hp-ease), background-color .28s var(--hp-ease); }
    .ab-pillar:hover { transform: translateY(-4px); background: rgb(255 255 255 / .1); }
    .ab-pillar-ico { width: 3rem; height: 3rem; border-radius: 1rem; display: grid; place-items: center; color: var(--hp-ink); background: var(--hp-sun); }
    .ab-pillar-ico svg { width: 1.5rem; height: 1.5rem; }
    .ab-pillar h3 { margin-top: 1rem; font-family: var(--font-heading); font-size: 1.25rem; font-weight: 800; color: #fff; }
    .ab-pillar p { margin-top: .5rem; font-size: .95rem; line-height: 1.6; color: #c9d5bd; }

    .ab-path { position: relative; margin-top: 3rem; display: grid; gap: 1.4rem; counter-reset: s; }
    .ab-path::before { content: ''; position: absolute; left: 1.35rem; top: 1.5rem; bottom: 1.5rem; width: 3px; border-radius: 3px;
        background: linear-gradient(180deg, var(--hp-sun), var(--hp-green)); opacity: .6; }
    .ab-step { position: relative; display: flex; gap: 1.1rem; align-items: flex-start; }
    .ab-step-n { position: relative; z-index: 1; flex: none; width: 2.8rem; height: 2.8rem; border-radius: 999px; display: grid; place-items: center;
        font-family: var(--font-heading); font-weight: 800; font-size: 1.15rem; color: var(--hp-ink); background: var(--hp-sun);
        box-shadow: 0 0 0 6px #f3f8ec; }
    .ab-step-card { flex: 1 1 auto; min-width: 0; display: flex; gap: 1rem; align-items: flex-start; padding: 1.2rem 1.3rem; border-radius: 1.3rem;
        background: #fff; border: 1px solid #e1ead6; box-shadow: 0 20px 40px -32px rgb(20 33 12 / .55);
        transition: transform .28s var(--hp-ease), box-shadow .28s var(--hp-ease); }
    .ab-step-card:hover { transform: translateX(4px); box-shadow: 0 24px 44px -30px rgb(20 33 12 / .6); }
    .ab-step-card img { flex: none; width: 3rem; height: 3rem; object-fit: contain; }
    .ab-step-card h3 { font-family: var(--font-heading); font-size: 1.15rem; font-weight: 800; color: var(--hp-ink); }
    .ab-step-card p { margin-top: .35rem; font-size: .95rem; line-height: 1.6; color: #4b5563; }
    @media (max-width: 639.98px) { .ab-step-card { flex-direction: column; } }

    .ab-guides-k { font-size: .76rem; font-weight: 800; letter-spacing: .1em; text-transform: uppercase; color: var(--hp-green); }
    .ab-guides { margin-top: 1rem; display: grid; gap: .7rem; }
    .ab-guide { display: flex; align-items: center; gap: .9rem; padding: .95rem 1rem; border-radius: 1.1rem; background: #fff; border: 1px solid #e5ebdf;
        text-decoration: none; transition: border-color .28s var(--hp-ease), transform .28s var(--hp-ease), box-shadow .28s var(--hp-ease); }
    .ab-guide:hover { border-color: #b9d69a; transform: translateX(4px); box-shadow: 0 16px 30px -24px rgb(20 33 12 / .55); }
    .ab-guide-ico { flex: none; width: 2.4rem; height: 2.4rem; border-radius: .85rem; display: grid; place-items: center; color: var(--hp-green); background: #eef5e5; }
    .ab-guide-ico svg { width: 1.1rem; height: 1.1rem; }
    .ab-guide-t { flex: 1 1 auto; min-width: 0; }
    .ab-guide-t b { display: block; font-family: var(--font-heading); font-weight: 800; color: var(--hp-ink); }
    .ab-guide-t small { display: block; margin-top: .15rem; font-size: .86rem; color: #6b7280; line-height: 1.45; }
    .ab-guide-go { flex: none; color: #9ca3af; transition: color .28s var(--hp-ease), transform .28s var(--hp-ease); }
    .ab-guide-go svg { width: 1.1rem; height: 1.1rem; }
    .ab-guide:hover .ab-guide-go { color: var(--hp-green); transform: translateX(3px); }

    .ab-does { margin-top: 3rem; display: grid; gap: 1.1rem; }
    @media (min-width: 640px) { .ab-does { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
    @media (min-width: 1024px) { .ab-does { grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 1.3rem; } }
    .ab-do { display: flex; flex-direction: column; padding: 1.5rem; border-radius: 1.4rem; background: #fff; border: 1px solid #e5ebdf; text-decoration: none;
        transition: transform .28s var(--hp-ease), box-shadow .28s var(--hp-ease), border-color .28s var(--hp-ease); }
    .ab-do:hover { transform: translateY(-4px); border-color: #b9d69a; box-shadow: 0 24px 44px -30px rgb(20 33 12 / .6); }
    .ab-do-ico { width: 3.4rem; height: 3.4rem; border-radius: 1rem; display: grid; place-items: center; background: #f3f8ec; box-shadow: inset 0 0 0 1px #dcead0;
        transition: transform .28s var(--hp-ease); }
    .ab-do:hover .ab-do-ico { transform: scale(1.06) rotate(-3deg); }
    .ab-do-ico img { width: 2.1rem; height: 2.1rem; object-fit: contain; }
    .ab-do h3 { margin-top: 1rem; font-family: var(--font-heading); font-size: 1.15rem; font-weight: 800; color: var(--hp-ink); }
    .ab-do p { margin-top: .45rem; font-size: .93rem; line-height: 1.6; color: #4b5563; flex: 1 1 auto; }
    .ab-do-more { margin-top: 1rem; display: inline-flex; align-items: center; gap: .3rem; font-size: .88rem; font-weight: 800; color: var(--hp-green); }
    .ab-do-more svg { width: 1rem; height: 1rem; transition: transform .28s var(--hp-ease); }
    .ab-do:hover .ab-do-more svg { transform: translateX(3px); }

    .ab-values { margin-top: 3rem; display: grid; gap: 1.1rem; }
    @media (min-width: 640px) { .ab-values { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
    @media (min-width: 1024px) { .ab-values { grid-template-columns: repeat(4, minmax(0, 1fr)); } }
    .ab-value { padding: 1.4rem; border-radius: 1.3rem; background: linear-gradient(170deg, #f6faf1, #fff); border: 1px solid #e4ecdb;
        transition: transform .28s var(--hp-ease), box-shadow .28s var(--hp-ease); }
    .ab-value:hover { transform: translateY(-4px); box-shadow: 0 20px 40px -30px rgb(20 33 12 / .55); }
    .ab-value-ico { width: 2.8rem; height: 2.8rem; border-radius: .9rem; display: grid; place-items: center; color: var(--hp-green); background: #e4f0d6; }
    .ab-value-ico svg { width: 1.4rem; height: 1.4rem; }
    .ab-value h3 { margin-top: .9rem; font-family: var(--font-heading); font-size: 1.1rem; font-weight: 800; color: var(--hp-ink); }
    .ab-value p { margin-top: .4rem; font-size: .92rem; line-height: 1.6; color: #4b5563; }

    .ab-final { position: relative; isolation: isolate; overflow: hidden; }
    .ab-final-bg { position: absolute; inset: 0; z-index: -2; width: 100%; height: 100%; object-fit: cover; }
    .ab-final-shade { position: absolute; inset: 0; z-index: -1; background: linear-gradient(160deg, rgb(20 36 12 / .93), rgb(29 51 15 / .82) 55%, rgb(47 82 25 / .78)); }
    .ab-final-face { width: 5rem; height: 5rem; margin: 0 auto 1rem; border-radius: 999px; object-fit: cover;
        box-shadow: 0 0 0 4px var(--hp-sun), 0 0 0 12px rgb(245 197 24 / .18); animation: hpBobAb 5s ease-in-out infinite; }

    @media (prefers-reduced-motion: reduce) {
        .ab-hero-bg, .ab-down, .ab-namecard, .ab-final-face { animation: none !important; }
        .ab-pillar, .ab-step-card, .ab-guide, .ab-guide-go, .ab-do, .ab-do-ico, .ab-value, .ab-link { transition: none !important; }
    }
</style>
@endpush
