@extends('layouts.public')

@php
    $ph = \App\Support\Region::ph();
@endphp

@section('title', $ph ? 'About anee.io: Named After Ani, the Palay Harvest' : 'About anee.io: Named After the Harvest')
@section('meta_description', $ph
    ? 'anee.io takes its name from ani, the harvest. A farm app built by agronomists for Filipino palay, mais and gulay farmers, from pagtatanim to the last sack.'
    : 'anee.io takes its name from ani, the Filipino word for harvest. A farm app built by agronomists to plan every cropping season, from sowing to the last sack.')

@section('content')

    {{-- ================= HERO BAND ================= --}}
    <section class="relative isolate overflow-hidden bg-gradient-to-br from-brand-700 via-brand-800 to-brand-900">
        <img src="{{ asset('images/grains-min.webp') }}" alt="" aria-hidden="true"
             class="absolute inset-0 -z-20 h-full w-full object-cover opacity-20" loading="eager">
        <div class="absolute inset-0 -z-10 bg-dot-grid opacity-40" aria-hidden="true"></div>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 py-20 sm:py-28 text-center animate-fade-up">
            <span class="inline-flex items-center gap-2 rounded-full bg-white/10 backdrop-blur px-4 py-1.5 text-xs sm:text-sm font-bold uppercase tracking-wider text-accent-400 ring-1 ring-white/20">
                About anee.io
            </span>
            <h1 class="mt-5 font-heading text-3xl sm:text-5xl font-bold text-white max-w-3xl mx-auto leading-tight text-balance">
                Helping {{ \App\Support\Region::t('farmersOfTitle') }} Reach <span class="bg-gradient-to-r from-accent-300 to-accent-500 bg-clip-text text-transparent">Maximum Yield</span> and Income
            </h1>
            <p class="mt-5 max-w-2xl mx-auto text-brand-100 text-base sm:text-lg">
                anee.io is the cropping schedule manager our agronomists and technicians use on client farms,
                now open to every farmer as a simple farm app on the phone.
            </p>
        </div>
    </section>

    {{-- ================= STORY / BRAND ================= --}}
    <section class="py-16 sm:py-24 bg-white overflow-hidden">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 grid gap-10 lg:gap-14 lg:grid-cols-2 items-center">
            <div class="reveal">
                <div class="relative">
                    <div class="absolute -inset-3 rounded-[1.75rem] bg-gradient-to-br from-accent-500/25 to-brand-100 rotate-1"></div>
                    <img src="{{ asset('images/palay-08.jpg') }}" alt="{{ $ph ? 'Ripe palay in a palayan, ready for the ani' : 'Ripe rice field ready for harvest' }}"
                         class="relative rounded-2xl shadow-card-lg w-full object-cover ring-1 ring-black/5" loading="lazy">
                </div>
            </div>
            <div class="reveal">
                <p class="text-sm font-bold uppercase tracking-wider text-brand-600">Our story</p>
                <h2 class="mt-2 font-heading text-3xl sm:text-4xl font-bold text-ink text-balance">Where the Name Comes From</h2>
                <p class="mt-4 text-gray-600 leading-relaxed">
                    <span class="font-semibold text-ink">Ani</span> means harvest. It is the whole point of a season,
                    and the one score every farmer keeps.
                    @if ($ph)
                        When a neighbor asks <em>kumusta ang ani?</em>, they mean how many cavans of palay came off the field.
                        Our page on the <a href="{{ url('/blog/ani-meaning') }}" class="font-semibold text-brand-700 hover:underline">ani meaning</a> tells the rest.
                    @endif
                    Ani also gives its name to the technician inside the app: ask <span class="font-semibold text-ink">Anee</span>
                    about your crop and she answers.
                </p>
                <p class="mt-4 text-gray-600 leading-relaxed">
                    For years our team has helped farmers grow bigger harvests of {{ \App\Support\Region::t('rice') }}, {{ \App\Support\Region::t('corn') }} and more
                    through technical research, technician support, fertilization and management technologies,
                    with results recognized here and abroad.
                </p>
                <p class="mt-4 text-gray-600 leading-relaxed">
                    <span class="font-semibold text-ink">anee.io</span> is the next step: the cropping schedule manager our team
                    uses to run client farms, now a web app for you. Plan your lots, workers, materials, activities and
                    irrigation for the whole season, then follow the plan day by day from your phone.
                </p>
            </div>
        </div>
    </section>

    @if ($ph)
    {{-- ================= BUILT FOR PHILIPPINE AGRICULTURE ================= --}}
    <section class="py-16 sm:py-24 bg-gray-50 border-y border-gray-100">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 grid gap-10 lg:grid-cols-[minmax(0,1.1fr)_minmax(0,1fr)] items-start">
            <div class="reveal">
                <p class="text-sm font-bold uppercase tracking-wider text-brand-600">Why we built it</p>
                <h2 class="mt-2 font-heading text-3xl sm:text-4xl font-bold text-ink text-balance">Built for Agriculture in the Philippines</h2>
                <p class="mt-4 text-gray-600 leading-relaxed">
                    Farming still employs about one in five working Filipinos, and most farms here are small.
                    Our guide to <a href="{{ url('/blog/agriculture-in-the-philippine-economy') }}" class="font-semibold text-brand-700 hover:underline">agriculture in the Philippine economy</a>
                    has the numbers. A small farm has no room for a missed fertilizer day or a pest found a week late,
                    so the plan has to be simple enough to follow in the field.
                </p>
                <p class="mt-4 text-gray-600 leading-relaxed">
                    That is why anee.io counts every task from each lot's own day zero, the way agronomists write it:
                    days after sowing, after transplanting, or after planting. It works for
                    <a href="{{ url('/crops/pagtatanim-ng-palay') }}" class="font-semibold text-brand-700 hover:underline">pagtatanim ng palay</a>
                    in a Nueva Ecija palayan, for mais in Isabela and Mindanao, and for gulay on the
                    <a href="{{ url('/crops/vegetables-philippines') }}" class="font-semibold text-brand-700 hover:underline">vegetable farms of the Philippines</a>.
                </p>
                <p class="mt-4 text-gray-600 leading-relaxed">
                    And because advice is only good when it comes on time, Anee, the AI agricultural technician,
                    reads your season before she answers. Ask her in Tagalog or English about a rice bug, a
                    fertilizer like urea or complete 14 14 14, or a leaf that looks wrong.
                </p>
            </div>
            <div class="reveal grid gap-3">
                @php
                    $guides = [
                        ['/crops/palay', 'Palay', 'Palay in English, its growth stages, and how palay becomes rice.'],
                        ['/crops/corn-kernel', 'Corn kernel and mais', 'Parts of the corn plant, corn types and the corn kernel price.'],
                        ['/blog/urea-fertilizer', 'Fertilizer urea and 14 14 14', 'What each fertilizer does and when it goes in.'],
                        ['/problems/rice-bug', 'Rice bug and thrips', 'Signs, thresholds and control of the pests that hit hardest.'],
                        ['/blog/fertilizer-and-pesticide-authority', 'Fertilizer and Pesticide Authority', 'How to check that a product is registered before you buy.'],
                    ];
                @endphp
                <p class="text-sm font-bold uppercase tracking-wider text-brand-600">Free guides from our team</p>
                @foreach ($guides as [$href, $name, $line])
                    <a href="{{ url($href) }}" class="group flex items-start gap-3 rounded-2xl bg-white px-4 py-3.5 ring-1 ring-gray-100 shadow-sm hover:ring-brand-200 hover:shadow-card transition">
                        <span class="mt-0.5 w-8 h-8 shrink-0 rounded-xl bg-brand-50 text-brand-700 flex items-center justify-center">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21v-9m0 0C12 7 8 5 4 5c0 4 3 7 8 7zm0 0c0-4 3-7 8-7 0 4-4 7-8 7z"/></svg>
                        </span>
                        <span class="min-w-0">
                            <span class="block font-heading font-bold text-ink group-hover:text-brand-700">{{ $name }}</span>
                            <span class="block text-sm text-gray-600">{{ $line }}</span>
                        </span>
                    </a>
                @endforeach
            </div>
        </div>
    </section>
    @endif

    {{-- ================= WHAT ANISYSTEM DOES ================= --}}
    <section class="py-16 sm:py-24 bg-brand-mesh">
        <div class="max-w-7xl mx-auto px-4 sm:px-6">
            <div class="max-w-2xl mx-auto text-center reveal">
                <p class="text-sm font-bold uppercase tracking-wider text-brand-600">The app</p>
                <h2 class="mt-2 font-heading text-3xl sm:text-4xl font-bold text-ink text-balance">What anee.io Does for You</h2>
            </div>
            <div class="mt-12 grid gap-6 md:grid-cols-3">
                @php
                    $does = [
                        [
                            'img' => 'images/icons/fertilizer.png',
                            'title' => 'One Plan for the Whole Season',
                            'text' => 'Land preparation, sowing, fertilizer, crop protection and harvest on one cropping calendar, every task counted from your day zero.',
                        ],
                        [
                            'img' => 'images/icons/soil-restoration.png',
                            'title' => 'Costs You Can Actually See',
                            'text' => 'Workers, materials and services are priced in ' . \App\Support\Region::symbol() . ' as you plan, so you know your season budget before you spend a single ' . ($ph ? 'peso' : 'dollar') . '.',
                        ],
                        [
                            'img' => 'images/icons/technician-support.png',
                            'title' => 'The Way Our Technicians Work',
                            'text' => 'Built on the same protocols anee.io technicians follow on client farms, with their key rules and records.',
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
                            'text' => 'Everything we build starts with the realities of ' . ($ph ? 'Filipino farms' : 'working farms') . ': budgets, weather, labor and all.',
                            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>',
                        ],
                        [
                            'title' => 'Backed by Science',
                            'text' => 'Our schedules and protocols come from technical research and years of field results, not guesswork.',
                            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 3h6m-5 0v5.3L4.7 17a2 2 0 001.7 3h11.2a2 2 0 001.7-3L14 8.3V3"/>',
                        ],
                        [
                            'title' => 'Income and the Land',
                            'text' => 'Yield matters, and so does the soil. We plan for this season and the many seasons after it.',
                            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>',
                        ],
                        [
                            'title' => 'Simple on a Phone',
                            'text' => 'If it does not work on a phone in the middle of a ' . ($ph ? 'palayan' : 'rice field') . ', it does not go into the app.',
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
                Start planning with anee.io today, the schedule manager our own technicians run on.
            </p>
            <div class="mt-8 flex flex-col sm:flex-row justify-center gap-3">
                <a href="{{ route('signup') }}" class="btn btn-accent btn-lg">Get Started</a>
                <a href="{{ route('tutorial') }}" class="btn btn-lg border-2 border-white/70 text-white bg-white/5 hover:bg-white/15">Read the Tutorial</a>
            </div>
        </div>
    </section>

@endsection
