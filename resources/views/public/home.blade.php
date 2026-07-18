@extends('layouts.public')

@section('title', 'Cropping Schedule Manager for Filipino Farmers')
@section('meta_description', 'AniSystem by AniSenso — plan every cropping season like a pro. Manage lots, workers, materials, activities and irrigation in one mobile-friendly web app built for Filipino farmers.')

@section('content')

    {{-- ================= HERO ================= --}}
    <section class="relative isolate overflow-hidden">
        <img src="{{ asset('images/hero-bg.jpg') }}" alt="Rice field in the Philippines at golden hour"
             class="absolute inset-0 -z-20 h-full w-full object-cover" loading="eager" fetchpriority="high">
        {{-- Layered overlays: legibility gradient + brand tint --}}
        <div class="absolute inset-0 -z-10 bg-gradient-to-t from-black/85 via-black/60 to-black/35"></div>
        <div class="absolute inset-0 -z-10 bg-gradient-to-r from-brand-900/60 via-brand-900/20 to-transparent"></div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 py-20 sm:py-24 lg:py-28">
            <div class="grid lg:grid-cols-2 gap-10 lg:gap-14 items-center">

                {{-- Left column: message --}}
                <div class="animate-fade-up">
                    <span class="inline-flex items-center gap-2 rounded-full bg-white/10 backdrop-blur px-4 py-1.5 text-xs sm:text-sm font-semibold text-accent-400 ring-1 ring-white/20">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path d="M10 2a1 1 0 011 1v1.07A6 6 0 0116 10c0 4-3 6-6 8-3-2-6-4-6-8a6 6 0 015-5.93V3a1 1 0 011-1z"/></svg>
                        From the makers of AniSenso — for Palay, Mais, and more
                    </span>

                    <h1 class="mt-6 font-heading text-4xl sm:text-5xl lg:text-6xl font-bold text-white leading-[1.08] text-balance">
                        Plan Every Cropping Season
                        <span class="bg-gradient-to-r from-accent-300 to-accent-500 bg-clip-text text-transparent">Like a Pro</span>
                    </h1>

                    <p class="mt-5 text-base sm:text-lg text-gray-200 leading-relaxed max-w-xl">
                        AniSystem is the AniSenso cropping schedule manager, now in your hands. Map your lots,
                        schedule every activity from land prep to harvest, and track workers, materials and irrigation —
                        all from your phone, wherever your farm is.
                    </p>

                    <div class="mt-8">
                        <a href="{{ route('signup') }}" class="btn btn-accent btn-lg shadow-lg shadow-accent-500/20">
                            Get Started
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5-5 5M6 12h12"/></svg>
                        </a>
                    </div>

                    {{-- Capability strip — generic capability statements, not fabricated metrics --}}
                    <dl class="mt-10 grid grid-cols-2 gap-3 max-w-md">
                        @php
                            $trust = [
                                ['t' => 'Mobile-first', 's' => 'Runs on any phone'],
                                ['t' => 'Day-0 / DAS', 's' => 'Accurate timing'],
                                ['t' => '₱ Costing', 's' => 'Built right in'],
                                ['t' => 'AniSenso', 's' => 'Technician protocol'],
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
                        Watch how AniSystem works — from sign-up to your first full season plan.
                    </p>
                </div>

            </div>
        </div>
    </section>

    {{-- ================= FEATURES ================= --}}
    <section class="py-16 sm:py-24 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6">
            <div class="max-w-2xl mx-auto text-center reveal">
                <p class="text-sm font-bold uppercase tracking-wider text-brand-600">Everything in one place</p>
                <h2 class="mt-2 font-heading text-3xl sm:text-4xl font-bold text-ink text-balance">Your Whole Season, Organized</h2>
                <p class="mt-4 text-gray-600">
                    The same schedule manager AniSenso technicians use — built mobile-first so you can run it
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
                            'title' => 'Workers & Labor Costs',
                            'text' => 'Keep a roster of workers with skills and daily rates, assign them to activities and see labor cost summaries in ₱.',
                            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-1.13a4 4 0 10-4-4 4 4 0 004 4zm6-2a3 3 0 10-3-3"/>',
                        ],
                        [
                            'title' => 'Materials & Services',
                            'text' => 'List fertilizers, biostimulants, seeds and hired services with quantities and prices so the season budget is always clear.',
                            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>',
                        ],
                        [
                            'title' => 'Activities Timeline',
                            'text' => 'Build the full timeline — land prep, sowing, fertilization, spraying, harvest — with dates, priorities, drafts and versions.',
                            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>',
                        ],
                        [
                            'title' => 'Irrigation Planner',
                            'text' => 'Plan watering windows by DAS day ranges or exact dates per lot group, so no lot ever misses its irrigation.',
                            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 3s6 6.5 6 11a6 6 0 11-12 0c0-4.5 6-11 6-11z"/>',
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
        </div>
    </section>

    {{-- ================= HOW IT WORKS ================= --}}
    <section class="py-16 sm:py-24 bg-brand-mesh">
        <div class="max-w-7xl mx-auto px-4 sm:px-6">
            <div class="max-w-2xl mx-auto text-center reveal">
                <p class="text-sm font-bold uppercase tracking-wider text-brand-600">Getting started is easy</p>
                <h2 class="mt-2 font-heading text-3xl sm:text-4xl font-bold text-ink text-balance">How It Works</h2>
                <p class="mt-4 text-gray-600">Three simple steps between you and a fully planned season.</p>
            </div>

            <div class="relative mt-14">
                {{-- Connector line (desktop) --}}
                <div class="hidden md:block absolute left-0 right-0 top-7" aria-hidden="true">
                    <div class="mx-[16.667%] h-0.5 bg-gradient-to-r from-brand-200 via-brand-400 to-brand-200"></div>
                </div>

                <div class="grid gap-8 md:gap-6 md:grid-cols-3">
                    @php
                        $steps = [
                            ['n' => '1', 'title' => 'Sign Up', 'text' => 'Create your free account in under a minute — just your name, email and a password.'],
                            ['n' => '2', 'title' => 'Pay via GCash', 'text' => 'Choose a plan, send payment through GCash and upload your receipt. Our team verifies it and emails you once your access is active.'],
                            ['n' => '3', 'title' => 'Manage Your Season', 'text' => 'Set up your lots, workers and materials, then run your whole cropping calendar from any phone or computer.'],
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

            <div class="mt-12 text-center reveal">
                <a href="{{ route('tutorial') }}" class="btn btn-outline btn-lg">See the full tutorial</a>
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
                    AniSenso has a proven track record of helping Filipino farmers achieve maximum crop yields
                    through science-backed fertilization and management technologies. AniSystem puts the same
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
                <div class="mt-8">
                    <a href="{{ route('about') }}" class="btn btn-primary">Learn more about AniSenso</a>
                </div>
            </div>
            <div class="order-1 lg:order-2 reveal">
                <div class="relative">
                    <div class="absolute -inset-3 rounded-[1.75rem] bg-gradient-to-br from-brand-100 to-accent-500/20 -rotate-1"></div>
                    <img src="{{ asset('images/rice-comparison.png') }}" alt="Rice yield comparison — before and after following the AniSenso protocol"
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
                <h2 class="mt-2 font-heading text-3xl sm:text-4xl font-bold text-ink text-balance">Traditional Farming vs AniSystem</h2>
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
                    ['dim' => 'Irrigation timing',      'trad' => 'Watered by feel; some lots miss their window.',                   'gain' => 'Irrigation windows planned per lot by DAS range or exact date.'],
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
                                    <p class="text-[11px] font-bold uppercase tracking-wide text-brand-700">With AniSystem</p>
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
                                With AniSystem
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

            <div class="mt-10 text-center reveal">
                <a href="{{ route('signup') }}" class="btn btn-primary btn-lg">
                    Start planning the AniSystem way
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5-5 5M6 12h12"/></svg>
                </a>
            </div>
        </div>
    </section>

    {{-- ================= BENEFITS CHECKLIST ================= --}}
    <section class="py-16 sm:py-24 bg-white">
        <div class="max-w-6xl mx-auto px-4 sm:px-6">
            <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-brand-700 via-brand-800 to-brand-900 shadow-card-lg reveal">
                <div class="absolute inset-0 bg-dot-grid opacity-60" aria-hidden="true"></div>
                <div class="relative px-5 sm:px-10 lg:px-14 py-12 sm:py-16">
                    <div class="max-w-2xl">
                        <p class="text-sm font-bold uppercase tracking-wider text-accent-400">Everything you gain</p>
                        <h2 class="mt-2 font-heading text-3xl sm:text-4xl font-bold text-white text-balance">
                            What You Get with AniSystem
                        </h2>
                        <p class="mt-4 text-brand-100 leading-relaxed">
                            One subscription, every feature — the concrete benefits that keep your season on track
                            from land prep to harvest.
                        </p>
                    </div>

                    @php
                        $benefits = [
                            'Never miss a critical activity — every task lands on the right day.',
                            'Know your true cost per season in ₱ — labor, materials and services.',
                            'Plan by Day-0 / DAS accurately for each and every lot.',
                            'Keep every lot on its own schedule, variety and sowing date.',
                            'Track workers and labor by skill and daily rate.',
                            'Plan irrigation windows so no lot misses its watering.',
                            'Keep photos and documentation with every stage.',
                            'Export, print and share the plan with your whole team.',
                            'Run it from any phone, right in the middle of the field.',
                            'Follow the same protocol AniSenso technicians use.',
                            'Renew easily via GCash — remaining days stack, nothing wasted.',
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
                        <a href="{{ route('signup') }}" class="btn btn-accent btn-lg">Get Started</a>
                        <a href="{{ route('tutorial') }}" class="btn btn-lg border-2 border-white/70 text-white bg-white/5 hover:bg-white/15">See how it works</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ================= PRICING ================= --}}
    @if ($plans->isNotEmpty())
        <section class="py-16 sm:py-24 bg-gray-50" id="pricing">
            <div class="max-w-7xl mx-auto px-4 sm:px-6">
                <div class="max-w-2xl mx-auto text-center reveal">
                    <p class="text-sm font-bold uppercase tracking-wider text-brand-600">Simple pricing</p>
                    <h2 class="mt-2 font-heading text-3xl sm:text-4xl font-bold text-ink text-balance">Choose Your Plan</h2>
                    <p class="mt-4 text-gray-600">Pay easily via GCash. One subscription, every feature included.</p>
                </div>

                <div class="mt-14 grid gap-6 sm:grid-cols-2 lg:grid-cols-3 max-w-5xl mx-auto items-start">
                    @foreach ($plans as $plan)
                        @php $isBest = $loop->count > 1 && $loop->iteration === $loop->count; @endphp
                        <div class="relative card card-hover flex flex-col reveal {{ $isBest ? 'ring-2 ring-accent-500 lg:-translate-y-3 shadow-card-lg' : '' }}"
                             style="--reveal-delay: {{ $loop->index * 0.06 }}s">
                            @if ($isBest)
                                <div class="absolute -top-3 left-1/2 -translate-x-1/2">
                                    <span class="badge badge-yellow shadow-sm ring-1 ring-accent-500/40 px-3 py-1">★ Best value</span>
                                </div>
                            @endif
                            <div class="card-body flex flex-col grow {{ $isBest ? 'pt-7' : '' }}">
                                <h3 class="font-heading text-xl font-bold text-ink">{{ $plan->planName }}</h3>
                                @if ($plan->description)
                                    <p class="mt-1 text-sm text-gray-500">{{ $plan->description }}</p>
                                @endif
                                <div class="mt-4 flex items-baseline gap-1.5">
                                    <span class="font-heading text-4xl font-bold {{ $isBest ? 'text-brand-700' : 'text-ink' }}">₱{{ number_format((float) $plan->price, fmod((float) $plan->price, 1) > 0 ? 2 : 0) }}</span>
                                    <span class="text-sm text-gray-500">/ {{ $plan->duration_label }}</span>
                                </div>
                                @if (is_array($plan->features) && count($plan->features))
                                    <ul class="mt-5 space-y-2.5 text-sm text-gray-700">
                                        @foreach ($plan->features as $feature)
                                            <li class="flex items-start gap-2.5">
                                                <svg class="w-5 h-5 shrink-0 text-brand-600" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                                <span>{{ $feature }}</span>
                                            </li>
                                        @endforeach
                                    </ul>
                                @endif
                                <div class="mt-6 pt-4 grow flex items-end">
                                    <a href="{{ route('signup') }}" class="btn {{ $isBest ? 'btn-accent' : 'btn-primary' }} w-full">
                                        Choose {{ $plan->planName }}
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <p class="mt-8 text-center text-sm text-gray-500">
                    Payments are verified manually by our team — you'll receive an email as soon as your access is activated.
                </p>
            </div>
        </section>
    @endif

    {{-- ================= FINAL CTA ================= --}}
    <section class="relative isolate overflow-hidden bg-gradient-to-br from-brand-700 via-brand-800 to-brand-900">
        <div class="absolute inset-0 bg-dot-grid opacity-50" aria-hidden="true"></div>
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 py-16 sm:py-24 text-center reveal">
            <h2 class="font-heading text-3xl sm:text-4xl lg:text-5xl font-bold text-white text-balance">
                Ready for Your Best Season Yet?
            </h2>
            <p class="mt-4 max-w-xl mx-auto text-brand-100 text-base sm:text-lg">
                Join the farmers already planning smarter with AniSystem. Reach your crop's maximum potential this season.
            </p>
            <div class="mt-8 flex flex-col sm:flex-row justify-center gap-3">
                <a href="{{ route('signup') }}" class="btn btn-accent btn-lg shadow-lg shadow-black/20">Get Started Now</a>
                <a href="{{ route('contact') }}" class="btn btn-lg border-2 border-white/70 text-white bg-white/5 hover:bg-white/15">Talk to Us</a>
            </div>
        </div>
    </section>

@endsection
