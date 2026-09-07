@extends('layouts.public')

@include('public.partials.site-css')

@section('title', 'Cropping Schedule Manager for Filipino Farmers')
@section('meta_description', 'anee.io — plan every cropping season like a pro. Manage lots, activities, workers and costs, ask the built-in AI Technician, and learn from a community of Filipino farmers — all in one mobile-friendly web app. Start free.')

@section('content')

    {{-- ================= HERO ================= --}}
    <section class="relative isolate overflow-hidden">
        <img src="{{ asset('images/site/photos/hero-planting.jpg') }}" alt="Filipino farmers planting rice, one checking anee.io on his phone"
             class="absolute inset-0 -z-20 h-full w-full object-cover" loading="eager" fetchpriority="high">
        {{-- Layered overlays: legibility gradient + brand tint --}}
        <div class="absolute inset-0 -z-10 bg-gradient-to-t from-black/85 via-black/60 to-black/35"></div>
        <div class="absolute inset-0 -z-10 bg-gradient-to-r from-brand-900/60 via-brand-900/20 to-transparent"></div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 py-20 sm:py-24 lg:py-28">
            <div class="grid lg:grid-cols-[1fr_1.35fr] gap-10 lg:gap-14 items-center">

                {{-- Left column: message --}}
                <div class="animate-fade-up">
                    <span class="inline-flex items-center gap-2 rounded-full bg-white/10 backdrop-blur px-4 py-1.5 text-xs sm:text-sm font-semibold text-accent-400 ring-1 ring-white/20">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path d="M10 2a1 1 0 011 1v1.07A6 6 0 0116 10c0 4-3 6-6 8-3-2-6-4-6-8a6 6 0 015-5.93V3a1 1 0 011-1z"/></svg>
                        For Palay, Mais, and more
                    </span>

                    <h1 class="mt-6 font-heading text-4xl sm:text-5xl lg:text-6xl font-bold text-white leading-[1.08] text-balance">
                        Plan Every Cropping Season
                        <span class="bg-gradient-to-r from-accent-300 to-accent-500 bg-clip-text text-transparent">Like a Pro</span>
                    </h1>

                    <p class="mt-5 text-base sm:text-lg text-gray-200 leading-relaxed max-w-xl">
                        anee.io is the cropping schedule manager our technicians run on, now in your hands. Map your lots,
                        schedule every activity from land prep to harvest, track workers and costs, and ask the built-in AI Technician —
                        all from your phone, wherever your farm is.
                    </p>

                    <div class="mt-8 flex flex-col items-start gap-2">
                        <a href="{{ route('signup') }}" class="btn btn-accent btn-lg shadow-lg shadow-accent-500/20">
                            Start for Free
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5-5 5M6 12h12"/></svg>
                        </a>
                        <span class="text-xs text-gray-300">Free forever on the Libre plan — no card, no trial clock.</span>
                    </div>

                    {{-- Capability strip — generic capability statements, not fabricated metrics --}}
                    <dl class="mt-10 grid grid-cols-2 gap-3 max-w-md">
                        @php
                            $trust = [
                                ['t' => 'Mobile-first', 's' => 'Runs on any phone'],
                                ['t' => 'Day-0 / DAS', 's' => 'Accurate timing'],
                                ['t' => '₱ Costing', 's' => 'Built right in'],
                                ['t' => 'AI Technician', 's' => 'Ask anytime, 24/7'],
                            ];
                        @endphp
                        @foreach ($trust as $item)
                            <div class="rounded-2xl bg-white/10 backdrop-blur px-3.5 py-3 ring-1 ring-white/15">
                                <dt class="font-heading text-sm sm:text-base font-bold text-white">{{ $item['t'] }}</dt>
                                <dd class="text-[11px] sm:text-xs text-gray-300 mt-0.5">{{ $item['s'] }}</dd>
                            </div>
                        @endforeach
                    </dl>

                    <p class="mt-6 text-sm text-gray-300 max-w-xl">
                        <span class="font-semibold text-white">Ani</span> (Yield) + <span class="font-semibold text-white">Senso</span>
                        (Sensei means Teacher, Asenso means Success) — science-backed farm planning for Filipino farmers.
                    </p>
                </div>

                {{-- Right column: "how it works" video --}}
                <div class="animate-fade-up" style="animation-delay: 0.12s" x-data="{ playing: false }">
                    <div class="relative">
                        <div class="absolute -inset-4 rounded-[2rem] bg-brand-500/25 blur-2xl -z-10" aria-hidden="true"></div>
                        <div class="relative rounded-2xl overflow-hidden ring-1 ring-white/20 shadow-2xl bg-black aspect-video">
                            <video x-ref="heroVideo"
                                   class="h-full w-full object-cover"
                                   poster="{{ asset('images/top-yield.webp') }}"
                                   controls preload="none" playsinline
                                   @play="playing = true" @pause="playing = false" @ended="playing = false">
                                <source src="{{ asset('videos/how-it-works.mp4') }}" type="video/mp4">
                                Sorry, your browser does not support embedded videos.
                            </video>

                            {{-- Custom play overlay; hidden once the video is playing --}}
                            <button type="button"
                                    x-show="!playing"
                                    @click="$refs.heroVideo.play()"
                                    class="group absolute inset-0 flex items-center justify-center bg-black/35 hover:bg-black/25 transition"
                                    aria-label="Play the how-it-works video">
                                <span class="flex items-center justify-center w-16 h-16 sm:w-20 sm:h-20 rounded-full bg-accent-500 text-ink shadow-xl ring-4 ring-white/20 group-hover:scale-105 transition">
                                    <svg class="w-7 h-7 sm:w-9 sm:h-9 ml-1" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M8 5.14v13.72L19 12 8 5.14z"/></svg>
                                </span>
                            </button>

                            {{-- Corner label --}}
                            <span x-show="!playing" class="absolute left-3 top-3 inline-flex items-center gap-1.5 rounded-full bg-black/45 backdrop-blur px-3 py-1 text-xs font-semibold text-white ring-1 ring-white/20">
                                <svg class="w-3.5 h-3.5 text-accent-400" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path d="M10 2a1 1 0 011 1v1.07A6 6 0 0116 10c0 4-3 6-6 8-3-2-6-4-6-8a6 6 0 015-5.93V3a1 1 0 011-1z"/></svg>
                                How it works
                            </span>
                        </div>
                    </div>
                    <p class="mt-3 text-center text-sm text-gray-300">
                        Watch how anee.io works — from sign-up to your first full season plan.
                    </p>
                </div>

            </div>
        </div>
    </section>

    {{-- ================= WHY: FARMING BY INTERVENTION ================= --}}
    {{-- The argument the whole site rests on: the calendar stopped being
         enough. Successful modern farming is intervention-based — read the
         change early, act on the right day — and that is precisely the job
         this app does. --}}
    <section class="relative isolate overflow-hidden spark-field">
        <img src="{{ asset('images/site/photos/storm-paddies.jpg') }}" alt="Farmers transplanting rice under a heavy grey sky"
             class="absolute inset-0 -z-20 h-full w-full object-cover" loading="lazy">
        <div class="absolute inset-0 -z-10 bg-gradient-to-b from-brand-900/95 via-brand-900/85 to-brand-900/95"></div>
        <div class="relative max-w-6xl mx-auto px-4 sm:px-6 py-16 sm:py-24" style="z-index:1">
            <div class="max-w-3xl mx-auto text-center reveal">
                <p class="text-sm font-bold uppercase tracking-wider text-accent-400">Why plans must bend</p>
                <h2 class="mt-2 font-heading text-3xl sm:text-4xl lg:text-5xl font-bold text-white text-balance">
                    Modern Farming Wins by <span class="bg-gradient-to-r from-accent-300 to-accent-500 bg-clip-text text-transparent">Intervention</span>
                </h2>
                <p class="mt-5 text-brand-100 text-base sm:text-lg leading-relaxed">
                    The old way follows a fixed calendar and hopes. But the seasons stopped cooperating —
                    El Niño and La Niña swing, storms land early, pests arrive before the book says they should,
                    prices move after harvest is already committed. The farmers who succeed today are the ones
                    who <span class="font-semibold text-white">see the change coming and intervene on the right day</span>.
                    That is exactly the job anee.io was built to do.
                </p>
            </div>

            <div class="mt-12 grid gap-4 sm:gap-5 sm:grid-cols-2">
                @php
                    $interventions = [
                        [
                            't' => 'The weather turns',
                            'w' => 'A dry spell stretches, or a week of rain moves in ahead of your spray day.',
                            'a' => 'Per-lot forecasts and ENSO-aware planting analyses see it early — and when the plan must move, you drag it and every date follows.',
                            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999A5.002 5.002 0 105.9 12.1 4 4 0 003 15zM13 21l-2 2m6-4l-2 2m-8-2l-2 2"/>',
                        ],
                        [
                            't' => 'A pest lands first',
                            'w' => 'Yellowing leaves, streaks, holes — and the technician\'s next visit is days away.',
                            'a' => 'Snap a photo and Anee reads it against your crop and stage, so you treat the right problem at the right dose, today.',
                            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2m0 0a7 7 0 017 7v3a3 3 0 01-3 3H8a3 3 0 01-3-3v-3a7 7 0 017-7zM9 12h.01M15 12h.01M9.5 17h5"/>',
                        ],
                        [
                            't' => 'The crop runs ahead — or behind',
                            'w' => 'Heat pushed the stages faster than the plan; a cold snap held them back.',
                            'a' => 'Growth stages are read per date, per lot, with do-lists and watch-lists — so you act on what the crop is, not what the calendar assumed.',
                            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>',
                        ],
                        [
                            't' => 'Costs drift mid-season',
                            'w' => 'An extra spray here, a rework there — and the margin quietly disappears.',
                            'a' => 'Labor, materials and services total live in ₱ as you adjust, so every intervention is decided knowing what it costs.',
                            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>',
                        ],
                    ];
                @endphp
                @foreach ($interventions as $i => $iv)
                    <div class="rounded-2xl bg-white/10 backdrop-blur ring-1 ring-white/15 p-5 sm:p-6 reveal" style="--reveal-delay: {{ $i * 0.07 }}s">
                        <div class="flex items-center gap-3">
                            <span class="w-11 h-11 shrink-0 rounded-2xl bg-accent-500/15 ring-1 ring-accent-500/30 text-accent-400 flex items-center justify-center">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">{!! $iv['icon'] !!}</svg>
                            </span>
                            <h3 class="font-heading text-lg font-bold text-white">{{ $iv['t'] }}</h3>
                        </div>
                        <p class="mt-3 text-sm text-brand-100/90 leading-relaxed">{{ $iv['w'] }}</p>
                        <p class="mt-2.5 text-sm text-white leading-relaxed flex items-start gap-2">
                            <svg class="w-4 h-4 mt-0.5 shrink-0 text-accent-400" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5-5 5M6 12h12"/></svg>
                            <span>{{ $iv['a'] }}</span>
                        </p>
                    </div>
                @endforeach
            </div>

            <div class="sec-cta on-dark reveal">
                <a href="{{ route('signup') }}" class="btn btn-accent btn-lg shadow-lg shadow-black/20">Start for Free</a>
                <span class="sec-cta-note">Farm by intervention, not by hope — from your first free season.</span>
            </div>
        </div>
    </section>

    {{-- ================= YOUR FARM IS A BUSINESS ================= --}}
    {{-- The second half of the argument: every other business already took
         the technology upgrade and pulled ahead. Agriculture is a business
         too — this is its turn. --}}
    <section class="py-16 sm:py-24 bg-brand-mesh bg-drift">
        <div class="max-w-6xl mx-auto px-4 sm:px-6">
            <div class="grid gap-10 lg:gap-14 lg:grid-cols-[1.15fr_1fr] items-center">
                <div class="reveal">
                    <p class="text-sm font-bold uppercase tracking-wider text-brand-600">Your farm is a business</p>
                    <h2 class="mt-2 font-heading text-3xl sm:text-4xl font-bold text-ink text-balance">
                        Every Business Already Upgraded. <span class="text-brand-600">It's the Farm's Turn.</span>
                    </h2>
                    <p class="mt-4 text-gray-600 leading-relaxed">
                        The sari-sari store takes e-wallet payments. The tricycle line runs on an app.
                        The trader who buys your palay works from a spreadsheet. Every business that took
                        the technology step got faster, leaner and more profitable — while most farms
                        still run from memory and a worn notebook.
                    </p>
                    <p class="mt-3 text-gray-600 leading-relaxed">
                        Agriculture is a business too: inputs, labor, timing, margins. It deserves the
                        same upgrade — sized for the field, priced for the farmer, in your own pocket.
                    </p>
                    <div class="mt-8 flex flex-col items-start gap-2">
                        <a href="{{ route('signup') }}" class="btn btn-accent btn-lg">Start for Free — take the upgrade</a>
                        <span class="text-xs text-gray-500">Free forever on Libre. Your notebook can retire gently.</span>
                    </div>
                </div>
                <div class="grid gap-4 reveal">
                    @foreach ([
                        ['t' => 'Efficiency', 'p' => 'No wasted days and no forgotten tasks — every activity lands on the right date, counted from each lot\'s own Day-0.', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/>'],
                        ['t' => 'Output', 'p' => 'Science-backed timing and stage-by-stage guidance — the same protocol our technicians use to chase maximum yield.', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>'],
                        ['t' => 'Control', 'p' => 'Know your margin before you spend, not after — labor, materials and services totalled live in ₱ across the season.', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>'],
                    ] as $i => $b)
                        <div class="card card-hover reveal" style="--reveal-delay: {{ $i * 0.08 }}s">
                            <div class="card-body flex items-start gap-4">
                                <span class="w-11 h-11 shrink-0 rounded-2xl bg-gradient-to-br from-brand-50 to-brand-100 text-brand-600 ring-1 ring-brand-100 flex items-center justify-center">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">{!! $b['icon'] !!}</svg>
                                </span>
                                <div>
                                    <h3 class="font-heading text-lg font-bold text-ink">{{ $b['t'] }}</h3>
                                    <p class="mt-1 text-sm text-gray-600 leading-relaxed">{{ $b['p'] }}</p>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    {{-- ================= FEATURES (nine, balanced) ================= --}}
    <section class="py-16 sm:py-24 bg-white bg-drift">
        <div class="max-w-7xl mx-auto px-4 sm:px-6">
            <div class="max-w-2xl mx-auto text-center reveal">
                <p class="text-sm font-bold uppercase tracking-wider text-brand-600">Everything in one place</p>
                <h2 class="mt-2 font-heading text-3xl sm:text-4xl font-bold text-ink text-balance">Your Whole Season, Organized</h2>
                <p class="mt-4 text-gray-600">
                    The same schedule manager our technicians use — built mobile-first so you can run it
                    right from the field.
                </p>
            </div>

            <div class="mt-12 grid gap-5 sm:gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @php
                    $features = [
                        [
                            'title' => 'Cropping Schedules',
                            'text' => 'Create a schedule per season and per farm. Keep wet and dry seasons, varieties and protocols neatly separated.',
                            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3M4 11h16M5 5h14a1 1 0 011 1v13a1 1 0 01-1 1H5a1 1 0 01-1-1V6a1 1 0 011-1z"/>',
                        ],
                        [
                            'title' => 'Lots & Day-0 Anchoring',
                            'text' => 'Register every lot with size, variety and its own Day-0 (sowing) date so activity timings stay accurate per lot.',
                            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 20l-5-2V6l5 2m0 12l6-2m-6 2V8m6 10l5 2V8l-5-2m0 12V6m0 0L9 8"/>',
                        ],
                        [
                            'title' => 'Activities Timeline',
                            'text' => 'Build the full timeline — land prep, sowing, fertilization, spraying, harvest — with dates, priorities, drafts and versions.',
                            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>',
                        ],
                        [
                            'title' => 'Workers & Labor Costs',
                            'text' => 'Keep a roster of workers with skills and daily rates, assign them to activities and see labor cost summaries in ₱.',
                            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-1.13a4 4 0 10-4-4 4 4 0 004 4zm6-2a3 3 0 10-3-3"/>',
                        ],
                        [
                            'title' => 'Materials & Inventory',
                            'text' => 'List fertilizers, seeds and services with quantities and prices, and watch stock move in and out as the season runs.',
                            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>',
                        ],
                        [
                            'title' => 'Farm Maps & Drawings',
                            'text' => 'Pin your lots on a live map, sketch layouts and plans, and keep every drawing tied to the note it explains.',
                            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a2 2 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0zM15 11a3 3 0 11-6 0 3 3 0 016 0z"/>',
                        ],
                        [
                            'title' => 'Weather & Growth Stages',
                            'text' => 'A forecast for every lot and a reading of where your crop stands — what the stage means and what to do now.',
                            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999A5.002 5.002 0 105.9 12.1 4 4 0 003 15z"/>',
                        ],
                        [
                            'title' => 'AI Technician',
                            'text' => 'Ask crop questions anytime — fertilizer rates, pests, timing — or snap a photo of a leaf and let Anee take a look.',
                            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2m0 0a7 7 0 017 7v3a3 3 0 01-3 3H8a3 3 0 01-3-3v-3a7 7 0 017-7zM9 12h.01M15 12h.01M9.5 17h5"/>',
                        ],
                        [
                            'title' => 'Farmer Community',
                            'text' => 'Join crop groups, post questions with photos or GIFs, react to advice that works, and learn from plans other farmers share.',
                            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-1a4 4 0 00-4-4h-1M9 11a4 4 0 100-8 4 4 0 000 8zm8 0a3 3 0 100-6M2 20v-1a5 5 0 015-5h4a5 5 0 015 5v1H2z"/>',
                        ],
                    ];
                @endphp

                @foreach ($features as $i => $f)
                    <div class="group card card-hover reveal relative overflow-hidden" style="--reveal-delay: {{ $i * 0.06 }}s">
                        <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-brand-500 to-accent-500 opacity-0 transition group-hover:opacity-100"></div>
                        <div class="card-body">
                            <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-brand-50 to-brand-100 text-brand-600 ring-1 ring-brand-100 flex items-center justify-center transition group-hover:scale-105">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">{!! $f['icon'] !!}</svg>
                            </div>
                            <h3 class="mt-4 font-heading text-lg font-bold text-ink">{{ $f['title'] }}</h3>
                            <p class="mt-2 text-sm text-gray-600 leading-relaxed">{{ $f['text'] }}</p>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="sec-cta reveal">
                <a href="{{ route('signup') }}" class="btn btn-accent btn-lg">Start for Free</a>
                <span class="sec-cta-note">Every one of these is waiting on the free plan.</span>
            </div>
        </div>
    </section>

    {{-- ================= THE PRODUCT, SHOWN ON VIDEO ================= --}}
    <section class="py-16 sm:py-24 bg-gray-50 overflow-hidden">
        <div class="max-w-6xl mx-auto px-4 sm:px-6">
            <div class="max-w-2xl mx-auto text-center reveal">
                <p class="text-sm font-bold uppercase tracking-wider text-brand-600">See it working</p>
                <h2 class="mt-2 font-heading text-3xl sm:text-4xl font-bold text-ink text-balance">Watch the App Do Its Job</h2>
                <p class="mt-4 text-gray-600">Short clips of the live app, exactly as it runs in the field.</p>
            </div>

            <div class="mt-14 space-y-20 sm:space-y-24">
                <div class="fx-row reveal">
                    <div class="fx-media fx-glow w-full">
                        @include('public.partials.feature-video', ['slug' => 'board', 'poster' => 'images/site/photos/palay-phone.jpg', 'label' => 'The activities board'])
                    </div>
                    <div>
                        <p class="fx-kicker">The activities board</p>
                        <h3 class="fx-h">Your whole season, day by day, drag by drag</h3>
                        <p class="fx-p">Every task from land prep to harvest lands on the right date, counted from each lot's own Day-0. Drag to move, tick to finish, and undo survives even a logout.</p>
                        <ul class="fx-list">
                            <li><svg fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>Tasks, irrigation, services, payroll and reminders — one board</li>
                            <li><svg fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>Photos, videos and voice notes ride on any activity</li>
                            <li><svg fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>Workers see exactly what the owner lets them see</li>
                        </ul>
                        <a href="{{ route('signup') }}" class="btn btn-outline mt-6">Start for free
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5-5 5M6 12h12"/></svg></a>
                    </div>
                </div>

                <div class="fx-row is-flip reveal">
                    <div class="fx-media fx-glow w-full">
                        @include('public.partials.feature-video', ['slug' => 'growth-weather', 'poster' => 'images/site/photos/transplant.jpg', 'label' => 'Growth stages & weather'])
                    </div>
                    <div>
                        <p class="fx-kicker">Growth stages &amp; weather</p>
                        <h3 class="fx-h">The app reads your crop so you don't have to guess</h3>
                        <p class="fx-p">Pick a date and anee tells you where every lot stands — what the stage means, what to do now, and what to watch for, with the forecast beside it.</p>
                        <ul class="fx-list">
                            <li><svg fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>85 Philippine crops, from palay to mango</li>
                            <li><svg fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>Do-lists and watch-lists written per stage</li>
                            <li><svg fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>Weather panels for the week ahead, lot by lot</li>
                        </ul>
                        <a href="{{ route('signup') }}" class="btn btn-outline mt-6">Start for free
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5-5 5M6 12h12"/></svg></a>
                    </div>
                </div>

                <div class="fx-row reveal">
                    <div class="fx-media fx-glow w-full">
                        @include('public.partials.feature-video', ['slug' => 'reports', 'poster' => 'images/site/photos/sacks.jpg', 'label' => 'Reports & money'])
                    </div>
                    <div>
                        <p class="fx-kicker">Reports &amp; money</p>
                        <h3 class="fx-h">Know your true cost — and your true profit</h3>
                        <p class="fx-p">Labor, expenses and profit reports add themselves up from the records you keep, to the peso. At season's end, Anee reads everything and tells you what to change.</p>
                        <ul class="fx-list">
                            <li><svg fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>Every peso spent this season, itemized</li>
                            <li><svg fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>Harvest income vs your whole spend</li>
                            <li><svg fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>Anee's season report: what went wrong, what to improve</li>
                        </ul>
                        <a href="{{ route('signup') }}" class="btn btn-outline mt-6">Start for free
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5-5 5M6 12h12"/></svg></a>
                    </div>
                </div>

                <div class="fx-row is-flip reveal">
                    <div class="fx-media fx-glow w-full">
                        @include('public.partials.feature-video', ['slug' => 'community', 'poster' => 'images/site/photos/farmer-hijab.jpg', 'label' => 'The farmer community'])
                    </div>
                    <div>
                        <p class="fx-kicker">The farmer community</p>
                        <h3 class="fx-h">Thousands of seasons of experience, one tap away</h3>
                        <p class="fx-p">A news feed, focused discussions, direct messages with photos, clips and voice notes — and a ranking ladder that celebrates the farmers who help the most.</p>
                        <ul class="fx-list">
                            <li><svg fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>Ask with a photo of the problem, not just words</li>
                            <li><svg fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>Follow co-farmers growing the same crops</li>
                            <li><svg fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>Level up from Bagong Binhi to the top of the ladder</li>
                        </ul>
                        <a href="{{ route('signup') }}" class="btn btn-outline mt-6">Start for free
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5-5 5M6 12h12"/></svg></a>
                    </div>
                </div>
            </div>

            <div class="sec-cta reveal">
                <a href="{{ route('features') }}" class="btn btn-outline btn-lg">Tour every feature</a>
            </div>
        </div>
    </section>

    {{-- ================= ANEE, THE AI TECHNICIAN ================= --}}
    <section class="anee-band spark-field">
        <div class="relative max-w-6xl mx-auto px-4 sm:px-6 py-16 sm:py-24" style="z-index:1">
            <div class="fx-row">
                <div class="reveal">
                    <p class="text-sm font-bold uppercase tracking-wider text-accent-400">Meet Anee</p>
                    <h2 class="mt-2 font-heading text-3xl sm:text-4xl font-bold text-white text-balance">An AI technician who already knows your farm</h2>
                    <p class="mt-4 text-[#cdd8c0] leading-relaxed">
                        Anee isn't a generic chatbot. She reads your schedules, your lots, your growth stages and your
                        weather before she answers — so "should I spray tomorrow?" gets an answer about <em>your</em>
                        tomorrow, on <em>your</em> field. Ask anything, anytime, from the floating button on every screen.
                    </p>
                    <ul class="fx-list mt-5">
                        <li style="color:#e8efe1"><svg fill="none" stroke="#a8cc7e" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>Send a photo of a sick leaf and get a reading</li>
                        <li style="color:#e8efe1"><svg fill="none" stroke="#a8cc7e" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>When-to-plant and what-to-plant analyses for your exact town</li>
                        <li style="color:#e8efe1"><svg fill="none" stroke="#a8cc7e" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>Full season reports that read every record you kept</li>
                        <li style="color:#e8efe1"><svg fill="none" stroke="#a8cc7e" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>Runs on credits — pay only for what you ask</li>
                    </ul>
                    <div class="mt-8 flex flex-col items-start gap-2">
                        <a href="{{ route('signup') }}" class="btn btn-accent btn-lg">Start free &amp; ask Anee your first question</a>
                        <span class="text-xs text-[#8fa383]">Every new account gets starter credits on the house.</span>
                    </div>
                </div>
                <div class="reveal">
                    <div class="grid gap-6 max-w-md mx-auto">
                        <div class="anee-halo">
                            <div class="anee-portrait">
                                <img src="{{ asset('images/site/anee-feature.jpg') }}" alt="Anee, the anee.io AI technician, giving a thumbs up in a rice field">
                            </div>
                        </div>
                        @include('public.partials.feature-video', ['slug' => 'anee-how', 'poster' => 'images/site/photos/anee-chat-hand.jpg', 'label' => 'How we use Anee'])
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ================= HOW IT WORKS ================= --}}
    <section class="py-16 sm:py-24 bg-brand-mesh bg-drift">
        <div class="max-w-7xl mx-auto px-4 sm:px-6">
            <div class="max-w-2xl mx-auto text-center reveal">
                <p class="text-sm font-bold uppercase tracking-wider text-brand-600">Getting started is easy</p>
                <h2 class="mt-2 font-heading text-3xl sm:text-4xl font-bold text-ink text-balance">How It Works</h2>
                <p class="mt-4 text-gray-600">Three simple steps between you and a fully planned season — and the first one is free.</p>
            </div>

            <div class="relative mt-14">
                {{-- Connector line (desktop) --}}
                <div class="hidden md:block absolute left-0 right-0 top-7" aria-hidden="true">
                    <div class="mx-[16.667%] h-0.5 bg-gradient-to-r from-brand-200 via-brand-400 to-brand-200"></div>
                </div>

                <div class="grid gap-8 md:gap-6 md:grid-cols-3">
                    @php
                        $steps = [
                            ['n' => '1', 'title' => 'Sign Up Free', 'text' => 'Create your account with your email or your Google account — under a minute, no card, and the Libre plan is free forever.'],
                            ['n' => '2', 'title' => 'Set Up Your Farm', 'text' => 'Add your first cropping schedule, register your lots with their Day-0 dates, and list your workers and materials.'],
                            ['n' => '3', 'title' => 'Grow — and Upgrade When Ready', 'text' => 'Run your whole season from any phone. When the farm needs more, upgrade in-app via GCash — Solo at ₱200/mo or Farm Owner at ₱600/mo.'],
                        ];
                    @endphp
                    @foreach ($steps as $i => $s)
                        <div class="relative flex flex-col items-center text-center reveal" style="--reveal-delay: {{ $i * 0.1 }}s">
                            <div class="relative z-10 w-14 h-14 rounded-full bg-accent-500 text-ink font-heading text-2xl font-bold flex items-center justify-center shadow-md ring-4 ring-white">
                                {{ $s['n'] }}
                            </div>
                            <div class="mt-5 card card-hover w-full">
                                <div class="card-body">
                                    <h3 class="font-heading text-xl font-bold text-ink">{{ $s['title'] }}</h3>
                                    <p class="mt-2 text-sm text-gray-600 leading-relaxed">{{ $s['text'] }}</p>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="sec-cta reveal">
                <a href="{{ route('signup') }}" class="btn btn-accent btn-lg">Create your free account</a>
                <span class="sec-cta-note">Or <a href="{{ route('tutorial') }}" class="font-semibold text-brand-700 hover:underline">see the full tutorial</a> first.</span>
            </div>
        </div>
    </section>

    {{-- ================= RESULTS ================= --}}
    <section class="py-16 sm:py-24 bg-white overflow-hidden">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 grid gap-10 lg:gap-14 lg:grid-cols-2 items-center">
            <div class="order-2 lg:order-1 reveal">
                <p class="text-sm font-bold uppercase tracking-wider text-brand-600">Proven in the field</p>
                <h2 class="mt-2 font-heading text-3xl sm:text-4xl font-bold text-ink text-balance">
                    Planned Seasons Produce <span class="text-brand-600">Better Harvests</span>
                </h2>
                <p class="mt-4 text-gray-600 leading-relaxed">
                    Our team has a proven track record of helping Filipino farmers achieve maximum crop yields
                    through science-backed fertilization and management technologies. anee.io puts the same
                    disciplined season plan — the exact protocol our technicians follow — into your own hands.
                </p>
                <ul class="mt-6 space-y-3">
                    @foreach ([
                        'Never miss a critical activity — every task lands on the right day from Day-0.',
                        'Know your true cost per season: labor, materials and services, all in ₱.',
                        'Keep photos and documentation of every stage for your own records.',
                    ] as $point)
                        <li class="flex items-start gap-3">
                            <span class="mt-0.5 w-6 h-6 shrink-0 rounded-full bg-brand-100 text-brand-700 flex items-center justify-center">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            </span>
                            <span class="text-gray-700">{{ $point }}</span>
                        </li>
                    @endforeach
                </ul>
                <div class="mt-8 flex flex-wrap gap-3">
                    <a href="{{ route('signup') }}" class="btn btn-accent">Start for Free</a>
                    <a href="{{ route('about') }}" class="btn btn-outline">Learn more about anee.io</a>
                </div>
            </div>
            <div class="order-1 lg:order-2 reveal">
                <div class="relative">
                    <div class="absolute -inset-3 rounded-[1.75rem] bg-gradient-to-br from-brand-100 to-accent-500/20 -rotate-1"></div>
                    <img src="{{ asset('images/site/photos/powered-by.jpg') }}" alt="Three Filipino farmers in their rice field with a Powered by anee.io sign"
                         class="relative rounded-2xl shadow-card-lg w-full object-cover ring-1 ring-black/5" loading="lazy">
                </div>
            </div>
        </div>
    </section>

    {{-- ================= TRADITIONAL vs ANISYSTEM ================= --}}
    <section class="py-16 sm:py-24 bg-gray-50">
        <div class="max-w-6xl mx-auto px-4 sm:px-6">
            <div class="max-w-2xl mx-auto text-center reveal">
                <p class="text-sm font-bold uppercase tracking-wider text-brand-600">Why farmers switch</p>
                <h2 class="mt-2 font-heading text-3xl sm:text-4xl font-bold text-ink text-balance">Traditional Farming vs anee.io</h2>
                <p class="mt-4 text-gray-600">
                    The season doesn't have to live in your head and on scattered paper. See what changes when
                    the whole plan is in one place.
                </p>
            </div>

            @php
                $compare = [
                    ['dim' => 'Season planning',        'trad' => 'Kept in your head or scattered across paper notebooks.',          'gain' => 'One clear plan per season, per farm — always with you.'],
                    ['dim' => 'Activity timing',        'trad' => 'Guessed from memory — easy to spray or fertilize a few days late.', 'gain' => 'Every task auto-dated from each lot\'s Day-0. Right day, every time.'],
                    ['dim' => 'Labor cost tracking',    'trad' => 'Totalled by hand at the end — often a nasty surprise.',            'gain' => 'Worker rates add up live in ₱ as you build the plan.'],
                    ['dim' => 'Materials & budget',     'trad' => 'Rough estimates; overspending creeps in unnoticed.',              'gain' => 'Fertilizers, seeds and services priced upfront — know the budget first.'],
                    ['dim' => 'Worker scheduling',      'trad' => 'Called in last-minute; clashes and idle days happen.',            'gain' => 'Assign workers to activities ahead of time, by skill.'],
                    ['dim' => 'Expert advice',          'trad' => 'Wait for a technician visit or ask around the barangay.',         'gain' => 'The AI Technician answers crop questions anytime — even from a leaf photo.'],
                    ['dim' => 'Records & photos',       'trad' => 'Little proof of what was done, and when.',                        'gain' => 'Keep photos and notes attached to every stage.'],
                    ['dim' => 'Sharing the plan',       'trad' => 'Hard to hand over to family or workers.',                        'gain' => 'Export, print, or walk your team through it on screen.'],
                    ['dim' => 'Missed / late tasks',    'trad' => 'Critical steps slip through the cracks.',                        'gain' => 'Nothing falls off — every critical activity lands on time.'],
                ];
            @endphp

            {{-- MOBILE: stacked paired cards --}}
            <div class="mt-10 space-y-3 md:hidden">
                @foreach ($compare as $row)
                    <div class="rounded-2xl bg-white ring-1 ring-gray-200 overflow-hidden reveal">
                        <div class="px-4 py-2.5 bg-gray-50 border-b border-gray-100">
                            <span class="font-heading text-sm font-bold text-ink">{{ $row['dim'] }}</span>
                        </div>
                        <div class="p-4 space-y-3">
                            <div class="flex items-start gap-3">
                                <span class="mt-0.5 w-6 h-6 shrink-0 rounded-full bg-gray-100 text-gray-400 flex items-center justify-center">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                </span>
                                <div>
                                    <p class="text-[11px] font-bold uppercase tracking-wide text-gray-400">Traditional</p>
                                    <p class="text-sm text-gray-500 leading-snug">{{ $row['trad'] }}</p>
                                </div>
                            </div>
                            <div class="flex items-start gap-3 rounded-xl bg-brand-50 p-3 ring-1 ring-brand-100">
                                <span class="mt-0.5 w-6 h-6 shrink-0 rounded-full bg-brand-600 text-white flex items-center justify-center">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                </span>
                                <div>
                                    <p class="text-[11px] font-bold uppercase tracking-wide text-brand-700">With anee.io</p>
                                    <p class="text-sm text-gray-700 font-medium leading-snug">{{ $row['gain'] }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- DESKTOP: comparison table --}}
            <div class="mt-12 hidden md:block reveal">
                <div class="rounded-3xl bg-white ring-1 ring-gray-200 overflow-hidden shadow-card">
                    {{-- Header row --}}
                    <div class="grid grid-cols-[1.1fr_1fr_1.1fr]">
                        <div class="px-6 py-5 bg-gray-50">
                            <span class="text-xs font-bold uppercase tracking-wider text-gray-400">How you work</span>
                        </div>
                        <div class="px-6 py-5 bg-gray-50 border-l border-gray-100">
                            <span class="font-heading font-bold text-gray-500 inline-flex items-center gap-2">
                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6M7 3h7l5 5v11a2 2 0 01-2 2H7a2 2 0 01-2-2V5a2 2 0 012-2z"/></svg>
                                By Memory &amp; Paper
                            </span>
                        </div>
                        <div class="px-6 py-5 bg-brand-600">
                            <span class="font-heading font-bold text-white inline-flex items-center gap-2">
                                <svg class="w-5 h-5 text-accent-400" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.7-9.3a1 1 0 00-1.4-1.4L9 10.6 7.7 9.3a1 1 0 00-1.4 1.4l2 2a1 1 0 001.4 0l4-4z" clip-rule="evenodd"/></svg>
                                With anee.io
                            </span>
                        </div>
                    </div>
                    {{-- Body rows --}}
                    <div class="divide-y divide-gray-100">
                        @foreach ($compare as $row)
                            <div class="grid grid-cols-[1.1fr_1fr_1.1fr]">
                                <div class="px-6 py-4 flex items-center">
                                    <span class="font-heading font-semibold text-ink">{{ $row['dim'] }}</span>
                                </div>
                                <div class="px-6 py-4 border-l border-gray-100 flex items-start gap-3">
                                    <span class="mt-0.5 w-5 h-5 shrink-0 rounded-full bg-gray-100 text-gray-400 flex items-center justify-center">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </span>
                                    <span class="text-sm text-gray-500 leading-snug">{{ $row['trad'] }}</span>
                                </div>
                                <div class="px-6 py-4 bg-brand-50/70 flex items-start gap-3">
                                    <span class="mt-0.5 w-5 h-5 shrink-0 rounded-full bg-brand-600 text-white flex items-center justify-center">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                    </span>
                                    <span class="text-sm text-gray-700 font-medium leading-snug">{{ $row['gain'] }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="sec-cta reveal">
                <a href="{{ route('signup') }}" class="btn btn-primary btn-lg">
                    Start planning the anee.io way — free
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5-5 5M6 12h12"/></svg>
                </a>
            </div>
        </div>
    </section>

    {{-- ================= BENEFITS CHECKLIST ================= --}}
    <section class="py-16 sm:py-24 bg-white">
        <div class="max-w-6xl mx-auto px-4 sm:px-6">
            <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-brand-700 via-brand-800 to-brand-900 shadow-card-lg reveal spark-field">
                <div class="absolute inset-0 bg-dot-grid opacity-60" aria-hidden="true"></div>
                <div class="relative px-5 sm:px-10 lg:px-14 py-12 sm:py-16" style="z-index:1">
                    <div class="max-w-2xl">
                        <p class="text-sm font-bold uppercase tracking-wider text-accent-400">Everything you gain</p>
                        <h2 class="mt-2 font-heading text-3xl sm:text-4xl font-bold text-white text-balance">
                            What You Get with anee.io
                        </h2>
                        <p class="mt-4 text-brand-100 leading-relaxed">
                            The concrete benefits that keep your season on track from land prep to harvest —
                            starting on the free plan.
                        </p>
                    </div>

                    @php
                        $benefits = [
                            'Never miss a critical activity — every task lands on the right day.',
                            'Know your true cost per season in ₱ — labor, materials and services.',
                            'Plan by Day-0 / DAS accurately for each and every lot.',
                            'Keep every lot on its own schedule, variety and sowing date.',
                            'Track workers and labor by skill and daily rate.',
                            'Ask the AI Technician anytime — even with a photo of a sick leaf.',
                            'Keep photos and documentation with every stage.',
                            'Export, print and share the plan with your whole team.',
                            'Run it from any phone, right in the middle of the field.',
                            'Follow the same protocol our technicians use.',
                            'Start free, upgrade easily via GCash — remaining days stack.',
                            'Your data stays organized and safe, season to season.',
                        ];
                    @endphp

                    <ul class="mt-10 grid gap-3 sm:gap-4 sm:grid-cols-2">
                        @foreach ($benefits as $i => $benefit)
                            <li class="flex items-start gap-3 rounded-2xl bg-white/10 backdrop-blur px-4 py-3.5 ring-1 ring-white/10 reveal"
                                style="--reveal-delay: {{ ($i % 2) * 0.05 + intdiv($i, 2) * 0.04 }}s">
                                <span class="mt-0.5 w-6 h-6 shrink-0 rounded-full bg-accent-500 text-brand-900 flex items-center justify-center shadow-sm">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                </span>
                                <span class="text-sm sm:text-[15px] text-white/95 leading-snug">{{ $benefit }}</span>
                            </li>
                        @endforeach
                    </ul>

                    <div class="mt-10 flex flex-col sm:flex-row gap-3">
                        <a href="{{ route('signup') }}" class="btn btn-accent btn-lg">Start for Free</a>
                        <a href="{{ route('tutorial') }}" class="btn btn-lg border-2 border-white/70 text-white bg-white/5 hover:bg-white/15">See how it works</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ================= PRICING: THE THREE TIERS ================= --}}
    <section class="py-16 sm:py-24 bg-gray-50 bg-drift" id="pricing" x-data="{ yearly: false }">
        <div class="max-w-6xl mx-auto px-4 sm:px-6">
            <div class="max-w-2xl mx-auto text-center reveal">
                <p class="text-sm font-bold uppercase tracking-wider text-brand-600">Simple pricing</p>
                <h2 class="mt-2 font-heading text-3xl sm:text-4xl font-bold text-ink text-balance">Choose Your Plan</h2>
                <p class="mt-4 text-gray-600">Start free forever. Upgrade in-app via GCash when the farm asks for more.</p>
                <div class="mt-6 inline-flex rounded-full bg-white ring-1 ring-gray-200 p-1 gap-1">
                    <button type="button" class="rounded-full px-4 py-1.5 text-sm font-bold transition"
                            :class="yearly ? 'text-gray-500' : 'bg-brand-600 text-white'" @click="yearly = false">Monthly</button>
                    <button type="button" class="rounded-full px-4 py-1.5 text-sm font-bold transition"
                            :class="yearly ? 'bg-brand-600 text-white' : 'text-gray-500'" @click="yearly = true">Yearly <span class="font-normal">· save more</span></button>
                </div>
            </div>

            <div class="mt-12 pr-grid">
                @foreach ($tiers as $key => $tier)
                    @php $isStar = $key === 'owner'; @endphp
                    <div class="pr-card reveal {{ $isStar ? 'is-star' : '' }}" style="--reveal-delay: {{ $loop->index * 0.07 }}s">
                        @if ($isStar)<span class="pr-flag">Most complete</span>@endif
                        <span class="pr-name">{{ $tier['name'] }}</span>
                        <span class="pr-for">{{ $tier['tagline'] }}</span>

                        @if (empty($tier['price']))
                            <span class="pr-price">
                                <span class="pr-amount is-free">Free</span>
                                <span class="pr-per">forever</span>
                            </span>
                            <span class="pr-year">No card. No trial clock. Yours to keep.</span>
                        @else
                            <span class="pr-price" x-show="!yearly">
                                <span class="pr-amount">₱{{ number_format($tier['price']) }}</span>
                                <span class="pr-per">/ month</span>
                            </span>
                            <span class="pr-price" x-show="yearly" x-cloak>
                                <span class="pr-amount">₱{{ number_format($tier['priceYear']) }}</span>
                                <span class="pr-per">/ year</span>
                            </span>
                            <span class="pr-year" x-show="!yearly">or ₱{{ number_format($tier['priceYear']) }}/year — about ₱{{ number_format((int) round($tier['priceYear'] / 12)) }}/mo</span>
                            <span class="pr-year" x-show="yearly" x-cloak>That's about ₱{{ number_format((int) round($tier['priceYear'] / 12)) }}/mo, paid once via GCash</span>
                        @endif

                        <ul class="pr-list">
                            @foreach ($tier['features'] as $feature)
                                <li><svg fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>{{ $feature }}</li>
                            @endforeach
                            @foreach ($tier['excludes'] as $missing)
                                <li class="is-off"><svg fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>{{ $missing }}</li>
                            @endforeach
                        </ul>

                        <a href="{{ route('signup') }}" class="btn {{ $isStar ? 'btn-accent' : (empty($tier['price']) ? 'btn-primary' : 'btn-outline') }}">
                            {{ empty($tier['price']) ? 'Start for Free' : 'Start free, then upgrade' }}
                        </a>
                    </div>
                @endforeach
            </div>

            <p class="mt-8 text-center text-sm text-gray-500 reveal">
                Every account starts on Libre, free — upgrading happens inside the app, paid via GCash and verified by our team.
                <a href="{{ route('pricing') }}" class="font-semibold text-brand-700 hover:text-brand-800">See the full pricing page →</a>
            </p>
        </div>
    </section>

    {{-- ================= LIVE NUMBERS ================= --}}
    @if (! empty($stats))
    <section class="py-14 sm:py-16 bg-white">
        <div class="max-w-6xl mx-auto px-4 sm:px-6">
            <div class="stat-band reveal">
                <div class="stat-card"><div class="stat-n">{{ $stats['seasons'] }}</div><div class="stat-l">Cropping seasons planned</div></div>
                <div class="stat-card"><div class="stat-n">{{ $stats['activities'] }}</div><div class="stat-l">Farm activities scheduled</div></div>
                <div class="stat-card"><div class="stat-n">{{ $stats['notes'] }}</div><div class="stat-l">Field notes &amp; records kept</div></div>
                <div class="stat-card"><div class="stat-n">{{ $stats['members'] }}</div><div class="stat-l">Members in the community</div></div>
            </div>
            <p class="mt-3 text-center text-xs text-gray-400">Live counts from the platform, refreshed hourly.</p>
        </div>
    </section>
    @endif

    {{-- ================= FINAL CTA ================= --}}
    <section class="relative isolate overflow-hidden">
        <img src="{{ asset('images/site/photos/team-thumbs.jpg') }}" alt="Two Filipino farmers giving a thumbs up beside their rice field"
             class="absolute inset-0 -z-20 h-full w-full object-cover" loading="lazy">
        <div class="absolute inset-0 -z-10 bg-gradient-to-br from-brand-900/90 via-brand-900/75 to-brand-800/70"></div>
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 py-16 sm:py-24 text-center reveal">
            <h2 class="font-heading text-3xl sm:text-4xl lg:text-5xl font-bold text-white text-balance">
                Ready for Your Best Season Yet?
            </h2>
            <p class="mt-4 max-w-xl mx-auto text-brand-100 text-base sm:text-lg">
                Join the farmers already planning smarter with anee.io. Start free today —
                reach your crop's maximum potential this season.
            </p>
            <div class="mt-8 flex flex-col sm:flex-row justify-center gap-3">
                <a href="{{ route('signup') }}" class="btn btn-accent btn-lg shadow-lg shadow-black/20">Start for Free</a>
                <a href="{{ route('contact') }}" class="btn btn-lg border-2 border-white/70 text-white bg-white/5 hover:bg-white/15">Talk to Us</a>
            </div>
        </div>
    </section>

@endsection
