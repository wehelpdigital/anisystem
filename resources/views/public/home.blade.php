@extends('layouts.public')

@section('title', 'Cropping Schedule Manager for Filipino Farmers')
@section('meta_description', 'AniSystem by AniSenso — plan every cropping season like a pro. Manage lots, workers, materials, activities and irrigation in one mobile-friendly web app built for Filipino farmers.')

@section('content')

    {{-- ================= HERO ================= --}}
    <section class="relative isolate overflow-hidden">
        <img src="{{ asset('images/hero-bg.jpg') }}" alt="Rice field in the Philippines"
             class="absolute inset-0 -z-10 h-full w-full object-cover" loading="eager">
        <div class="absolute inset-0 -z-10 bg-gradient-to-r from-black/80 via-black/60 to-black/40"></div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 py-24 sm:py-32 lg:py-40">
            <div class="max-w-2xl">
                <span class="inline-flex items-center gap-2 rounded-full bg-white/10 backdrop-blur px-4 py-1.5 text-xs sm:text-sm font-semibold text-accent-400 ring-1 ring-white/20">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M10 2a1 1 0 011 1v1.07A6 6 0 0116 10c0 4-3 6-6 8-3-2-6-4-6-8a6 6 0 015-5.93V3a1 1 0 011-1z"/></svg>
                    From the makers of AniSenso — Maximizing Crop Yields for Palay, Mais, and More
                </span>

                <h1 class="mt-6 font-heading text-4xl sm:text-5xl lg:text-6xl font-bold text-white leading-tight">
                    Plan Every Cropping Season
                    <span class="text-accent-500">Like a Pro</span>
                </h1>

                <p class="mt-5 text-base sm:text-lg text-gray-200 leading-relaxed">
                    AniSystem is the AniSenso cropping schedule manager, now in your hands. Map your lots,
                    schedule every activity from land prep to harvest, track workers, materials and irrigation —
                    all from your phone, wherever your farm is.
                </p>

                <div class="mt-8 flex flex-col sm:flex-row gap-3">
                    <a href="{{ route('signup') }}" class="btn btn-accent btn-lg">
                        Get Started
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5-5 5M6 12h12"/></svg>
                    </a>
                    <a href="{{ route('tutorial') }}" class="btn btn-lg border-2 border-white/70 text-white bg-transparent hover:bg-white/10">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5.14v13.72L19 12 8 5.14z"/></svg>
                        Watch How It Works
                    </a>
                </div>

                <p class="mt-6 text-sm text-gray-300">
                    <span class="font-semibold text-white">Ani</span> (Yield) + <span class="font-semibold text-white">Senso</span>
                    (Sensei means Teacher, Asenso means Success) — science-backed farm planning for Filipino farmers.
                </p>
            </div>
        </div>
    </section>

    {{-- ================= FEATURES ================= --}}
    <section class="py-16 sm:py-24 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6">
            <div class="max-w-2xl mx-auto text-center">
                <p class="text-sm font-bold uppercase tracking-wider text-brand-600">Everything in one place</p>
                <h2 class="mt-2 font-heading text-3xl sm:text-4xl font-bold text-ink">Your Whole Season, Organized</h2>
                <p class="mt-4 text-gray-600">
                    The same schedule manager AniSenso technicians use — built mobile-first so you can run it
                    right from the field.
                </p>
            </div>

            <div class="mt-12 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
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

                @foreach ($features as $f)
                    <div class="card card-hover">
                        <div class="card-body">
                            <div class="w-12 h-12 rounded-2xl bg-brand-50 text-brand-600 flex items-center justify-center">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">{!! $f['icon'] !!}</svg>
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
    <section class="py-16 sm:py-24 bg-brand-50/60">
        <div class="max-w-7xl mx-auto px-4 sm:px-6">
            <div class="max-w-2xl mx-auto text-center">
                <p class="text-sm font-bold uppercase tracking-wider text-brand-600">Getting started is easy</p>
                <h2 class="mt-2 font-heading text-3xl sm:text-4xl font-bold text-ink">How It Works</h2>
            </div>

            <div class="mt-12 grid gap-6 md:grid-cols-3">
                @php
                    $steps = [
                        ['n' => '1', 'title' => 'Sign Up', 'text' => 'Create your free account in under a minute — just your name, email and a password.'],
                        ['n' => '2', 'title' => 'Pay via GCash', 'text' => 'Choose a plan, send payment through GCash and upload your receipt. Our team verifies it and emails you once your access is active.'],
                        ['n' => '3', 'title' => 'Manage Your Season', 'text' => 'Set up your lots, workers and materials, then run your whole cropping calendar from any phone or computer.'],
                    ];
                @endphp
                @foreach ($steps as $s)
                    <div class="card card-hover text-center">
                        <div class="card-body">
                            <div class="mx-auto w-14 h-14 rounded-full bg-accent-500 text-ink font-heading text-2xl font-bold flex items-center justify-center shadow-md">
                                {{ $s['n'] }}
                            </div>
                            <h3 class="mt-4 font-heading text-xl font-bold text-ink">{{ $s['title'] }}</h3>
                            <p class="mt-2 text-sm text-gray-600 leading-relaxed">{{ $s['text'] }}</p>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-10 text-center">
                <a href="{{ route('tutorial') }}" class="btn btn-outline">See the full tutorial</a>
            </div>
        </div>
    </section>

    {{-- ================= RESULTS ================= --}}
    <section class="py-16 sm:py-24 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 grid gap-10 lg:grid-cols-2 items-center">
            <div class="order-2 lg:order-1">
                <p class="text-sm font-bold uppercase tracking-wider text-brand-600">Proven in the field</p>
                <h2 class="mt-2 font-heading text-3xl sm:text-4xl font-bold text-ink">
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
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            </span>
                            <span class="text-gray-700">{{ $point }}</span>
                        </li>
                    @endforeach
                </ul>
                <div class="mt-8">
                    <a href="{{ route('about') }}" class="btn btn-primary">Learn more about AniSenso</a>
                </div>
            </div>
            <div class="order-1 lg:order-2">
                <div class="relative">
                    <div class="absolute -inset-3 rounded-3xl bg-brand-100/70 -rotate-1"></div>
                    <img src="{{ asset('images/rice-comparison.png') }}" alt="Rice yield comparison — before and after following the AniSenso protocol"
                         class="relative rounded-2xl shadow-lg w-full object-cover" loading="lazy">
                </div>
            </div>
        </div>
    </section>

    {{-- ================= PRICING ================= --}}
    @if ($plans->isNotEmpty())
        <section class="py-16 sm:py-24 bg-gray-50" id="pricing">
            <div class="max-w-7xl mx-auto px-4 sm:px-6">
                <div class="max-w-2xl mx-auto text-center">
                    <p class="text-sm font-bold uppercase tracking-wider text-brand-600">Simple pricing</p>
                    <h2 class="mt-2 font-heading text-3xl sm:text-4xl font-bold text-ink">Choose Your Plan</h2>
                    <p class="mt-4 text-gray-600">Pay easily via GCash. One subscription, every feature included.</p>
                </div>

                <div class="mt-12 grid gap-6 sm:grid-cols-2 lg:grid-cols-3 max-w-5xl mx-auto">
                    @foreach ($plans as $plan)
                        <div class="card card-hover flex flex-col {{ $loop->count > 1 && $loop->iteration === $loop->count ? 'ring-2 ring-accent-500' : '' }}">
                            <div class="card-body flex flex-col grow">
                                @if ($loop->count > 1 && $loop->iteration === $loop->count)
                                    <span class="badge badge-yellow self-start mb-2">Best value</span>
                                @endif
                                <h3 class="font-heading text-xl font-bold text-ink">{{ $plan->planName }}</h3>
                                @if ($plan->description)
                                    <p class="mt-1 text-sm text-gray-500">{{ $plan->description }}</p>
                                @endif
                                <div class="mt-4 flex items-baseline gap-1.5">
                                    <span class="font-heading text-4xl font-bold text-brand-700">₱{{ number_format((float) $plan->price, fmod((float) $plan->price, 1) > 0 ? 2 : 0) }}</span>
                                    <span class="text-sm text-gray-500">/ {{ $plan->duration_label }}</span>
                                </div>
                                @if (is_array($plan->features) && count($plan->features))
                                    <ul class="mt-5 space-y-2.5 text-sm text-gray-700">
                                        @foreach ($plan->features as $feature)
                                            <li class="flex items-start gap-2.5">
                                                <svg class="w-5 h-5 shrink-0 text-brand-600" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                                <span>{{ $feature }}</span>
                                            </li>
                                        @endforeach
                                    </ul>
                                @endif
                                <div class="mt-6 pt-4 grow flex items-end">
                                    <a href="{{ route('signup') }}" class="btn {{ $loop->count > 1 && $loop->iteration === $loop->count ? 'btn-accent' : 'btn-primary' }} w-full">
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
    <section class="bg-brand-800">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 py-16 sm:py-20 text-center">
            <h2 class="font-heading text-3xl sm:text-4xl font-bold text-white">
                Ready for Your Best Season Yet?
            </h2>
            <p class="mt-4 max-w-xl mx-auto text-brand-100">
                Join the farmers already planning smarter with AniSystem. Reach your crop's maximum potential this season.
            </p>
            <div class="mt-8 flex flex-col sm:flex-row justify-center gap-3">
                <a href="{{ route('signup') }}" class="btn btn-accent btn-lg">Get Started Now</a>
                <a href="{{ route('contact') }}" class="btn btn-lg border-2 border-white/70 text-white bg-transparent hover:bg-white/10">Talk to Us</a>
            </div>
        </div>
    </section>

@endsection
