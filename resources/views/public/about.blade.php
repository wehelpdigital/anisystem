@extends('layouts.public')

@section('title', 'About')
@section('meta_description', 'AniSenso — Ani (Yield) + Senso (Sensei/Asenso). Learn how AniSystem, the AniSenso cropping schedule manager, helps Filipino farmers reach maximum yield and income.')

@section('content')

    {{-- ================= HERO BAND ================= --}}
    <section class="relative isolate overflow-hidden bg-gradient-to-br from-brand-700 via-brand-800 to-brand-900">
        <img src="{{ asset('images/grains-min.webp') }}" alt="" aria-hidden="true"
             class="absolute inset-0 -z-20 h-full w-full object-cover opacity-20" loading="eager">
        <div class="absolute inset-0 -z-10 bg-dot-grid opacity-40" aria-hidden="true"></div>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 py-20 sm:py-28 text-center animate-fade-up">
            <span class="inline-flex items-center gap-2 rounded-full bg-white/10 backdrop-blur px-4 py-1.5 text-xs sm:text-sm font-bold uppercase tracking-wider text-accent-400 ring-1 ring-white/20">
                About AniSystem
            </span>
            <h1 class="mt-5 font-heading text-3xl sm:text-5xl font-bold text-white max-w-3xl mx-auto leading-tight text-balance">
                Helping Filipino Farmers Reach <span class="bg-gradient-to-r from-accent-300 to-accent-500 bg-clip-text text-transparent">Maximum Yield</span> and Income
            </h1>
            <p class="mt-5 max-w-2xl mx-auto text-brand-100 text-base sm:text-lg">
                AniSystem is the cropping schedule manager by AniSenso — the same planning system our
                agronomists and technicians use, now available to every farmer.
            </p>
        </div>
    </section>

    {{-- ================= STORY / BRAND ================= --}}
    <section class="py-16 sm:py-24 bg-white overflow-hidden">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 grid gap-10 lg:gap-14 lg:grid-cols-2 items-center">
            <div class="reveal">
                <div class="relative">
                    <div class="absolute -inset-3 rounded-[1.75rem] bg-gradient-to-br from-accent-500/25 to-brand-100 rotate-1"></div>
                    <img src="{{ asset('images/palay-08.jpg') }}" alt="Palay field ready for harvest"
                         class="relative rounded-2xl shadow-card-lg w-full object-cover ring-1 ring-black/5" loading="lazy">
                </div>
            </div>
            <div class="reveal">
                <p class="text-sm font-bold uppercase tracking-wider text-brand-600">Our story</p>
                <h2 class="mt-2 font-heading text-3xl sm:text-4xl font-bold text-ink text-balance">What Does "AniSenso" Mean?</h2>
                <p class="mt-4 text-gray-600 leading-relaxed">
                    <span class="font-semibold text-ink">Ani</span> means <em>Yield</em>.
                    <span class="font-semibold text-ink">Senso</span> carries two meanings:
                    <em>Sensei</em> — a teacher — and <em>Asenso</em> — success. Put together, AniSenso is about
                    teaching Filipino farmers the science of maximum yield so their families can prosper.
                </p>
                <p class="mt-4 text-gray-600 leading-relaxed">
                    For years, AniSenso has helped farmers maximize their harvests of palay, mais and more
                    through exclusive technical research, technician support, fertilization and management
                    technologies — with locally and internationally recognized, award-winning results.
                </p>
                <p class="mt-4 text-gray-600 leading-relaxed">
                    <span class="font-semibold text-ink">AniSystem</span> is the next step: the exact cropping
                    schedule manager our team uses to run client farms, packaged as a simple web app. Plan your
                    lots, workers, materials, activities and irrigation for the whole season — and follow the
                    plan day by day, straight from your phone.
                </p>
            </div>
        </div>
    </section>

    {{-- ================= WHAT ANISYSTEM DOES ================= --}}
    <section class="py-16 sm:py-24 bg-brand-mesh">
        <div class="max-w-7xl mx-auto px-4 sm:px-6">
            <div class="max-w-2xl mx-auto text-center reveal">
                <p class="text-sm font-bold uppercase tracking-wider text-brand-600">The app</p>
                <h2 class="mt-2 font-heading text-3xl sm:text-4xl font-bold text-ink text-balance">What AniSystem Does for You</h2>
            </div>
            <div class="mt-12 grid gap-6 md:grid-cols-3">
                @php
                    $does = [
                        [
                            'img' => 'images/icons/fertilizer.png',
                            'title' => 'One Plan for the Whole Season',
                            'text' => 'Every activity — land preparation, sowing, fertilization, crop protection, harvest — laid out on a single timeline anchored to your Day-0.',
                        ],
                        [
                            'img' => 'images/icons/soil-restoration.png',
                            'title' => 'Costs You Can Actually See',
                            'text' => 'Workers, materials and services are priced in ₱ as you plan, so you know your season budget before you spend a single peso.',
                        ],
                        [
                            'img' => 'images/icons/technician-support.png',
                            'title' => 'The Technician\'s Discipline',
                            'text' => 'Built from the same protocol system AniSenso technicians follow on client farms — critical rules, documentation and all.',
                        ],
                    ];
                @endphp
                @foreach ($does as $i => $d)
                    <div class="card card-hover text-center reveal" style="--reveal-delay: {{ $i * 0.08 }}s">
                        <div class="card-body">
                            <div class="mx-auto w-16 h-16 rounded-2xl bg-white shadow-sm ring-1 ring-brand-100 flex items-center justify-center p-3">
                                <img src="{{ asset($d['img']) }}" alt="" class="max-h-full max-w-full object-contain" loading="lazy">
                            </div>
                            <h3 class="mt-4 font-heading text-lg font-bold text-ink">{{ $d['title'] }}</h3>
                            <p class="mt-2 text-sm text-gray-600 leading-relaxed">{{ $d['text'] }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ================= VALUES ================= --}}
    <section class="py-16 sm:py-24 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6">
            <div class="max-w-2xl mx-auto text-center reveal">
                <p class="text-sm font-bold uppercase tracking-wider text-brand-600">What we stand for</p>
                <h2 class="mt-2 font-heading text-3xl sm:text-4xl font-bold text-ink text-balance">Our Values</h2>
            </div>
            <div class="mt-12 grid gap-5 sm:gap-6 sm:grid-cols-2 lg:grid-cols-4">
                @php
                    $values = [
                        [
                            'title' => 'Farmer First',
                            'text' => 'Everything we build starts with the realities of Filipino farms — budgets, weather, labor and all.',
                            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>',
                        ],
                        [
                            'title' => 'Science-Backed',
                            'text' => 'Our schedules and protocols come from technical research and years of field results, not guesswork.',
                            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 3h6m-5 0v5.3L4.7 17a2 2 0 001.7 3h11.2a2 2 0 001.7-3L14 8.3V3"/>',
                        ],
                        [
                            'title' => 'Maximum Income & Sustainability',
                            'text' => 'Yield matters, but so does the land. We plan for this season and the many seasons after it.',
                            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>',
                        ],
                        [
                            'title' => 'Simple & Accessible',
                            'text' => 'If it doesn\'t work on a phone in the middle of a rice field, it doesn\'t ship. Mobile-first, always.',
                            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/>',
                        ],
                    ];
                @endphp
                @foreach ($values as $i => $v)
                    <div class="group card card-hover reveal" style="--reveal-delay: {{ $i * 0.06 }}s">
                        <div class="card-body">
                            <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-brand-50 to-brand-100 text-brand-600 ring-1 ring-brand-100 flex items-center justify-center transition group-hover:scale-105">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">{!! $v['icon'] !!}</svg>
                            </div>
                            <h3 class="mt-4 font-heading text-lg font-bold text-ink">{{ $v['title'] }}</h3>
                            <p class="mt-2 text-sm text-gray-600 leading-relaxed">{{ $v['text'] }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ================= CTA ================= --}}
    <section class="relative isolate overflow-hidden bg-gradient-to-br from-brand-700 via-brand-800 to-brand-900">
        <div class="absolute inset-0 bg-dot-grid opacity-50" aria-hidden="true"></div>
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 py-16 sm:py-20 text-center reveal">
            <h2 class="font-heading text-3xl sm:text-4xl font-bold text-white text-balance">
                Reach Your Crop's Maximum Potential This Season
            </h2>
            <p class="mt-4 max-w-xl mx-auto text-brand-100">
                Start planning with AniSystem today — the schedule manager built by the AniSenso team.
            </p>
            <div class="mt-8 flex flex-col sm:flex-row justify-center gap-3">
                <a href="{{ route('signup') }}" class="btn btn-accent btn-lg">Get Started</a>
                <a href="{{ route('tutorial') }}" class="btn btn-lg border-2 border-white/70 text-white bg-white/5 hover:bg-white/15">See How It Works</a>
            </div>
        </div>
    </section>

@endsection
