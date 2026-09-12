@extends('layouts.public')

@include('public.partials.site-css')

@section('title', 'Pricing — Plans for Every Farm')
@section('meta_description', 'anee.io pricing: simple plans paid via GCash, plus AI credits so you only pay Anee for what you ask. No hidden fees, cancel anytime.')

@section('content')

    {{-- ================= HERO ================= --}}
    <section class="relative isolate overflow-hidden bg-gradient-to-br from-brand-700 via-brand-800 to-brand-900 spark-field">
        <div class="absolute inset-0 bg-dot-grid opacity-50" aria-hidden="true"></div>
        <div class="relative max-w-3xl mx-auto px-4 sm:px-6 py-16 sm:py-20 text-center animate-fade-up" style="z-index:1">
            <p class="text-sm font-bold uppercase tracking-wider text-accent-400">Simple, farmer-sized pricing</p>
            <h1 class="mt-2 font-heading text-4xl sm:text-5xl font-bold text-white text-balance">Start Free. Grow When You're Ready.</h1>
            <p class="mt-5 text-brand-100 text-base sm:text-lg">
                The Libre plan is free forever — no card, no trial clock. Upgrades are paid in pesos,
                through GCash. The AI technician runs on credits on top, so you only ever pay Anee
                for what you actually ask.
            </p>
        </div>
    </section>

    {{-- ================= THE THREE TIERS ================= --}}
    <section class="py-16 sm:py-20 bg-gray-50 bg-drift" x-data="{ yearly: false }">
        <div class="max-w-6xl mx-auto px-4 sm:px-6">
            <div class="text-center reveal">
                <div class="inline-flex rounded-full bg-white ring-1 ring-gray-200 p-1 gap-1">
                    <button type="button" class="rounded-full px-4 py-1.5 text-sm font-bold transition"
                            :class="yearly ? 'text-gray-500' : 'bg-brand-600 text-white'" @click="yearly = false">Monthly</button>
                    <button type="button" class="rounded-full px-4 py-1.5 text-sm font-bold transition"
                            :class="yearly ? 'bg-brand-600 text-white' : 'text-gray-500'" @click="yearly = true">Yearly <span class="font-normal">· save more</span></button>
                </div>
            </div>
            <div class="pr-grid mt-8">
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
                            <span class="pr-day">₱0.00 a day, for as long as you like</span>
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
                            {{-- What it actually costs to run, said the way a farmer
                                 counts: by the day. A month is an abstraction; a peso a
                                 day is a number you can hold against anything else you
                                 buy on a Tuesday. --}}
                            <span class="pr-day" x-show="!yearly">That is about ₱{{ number_format($tier['price'] / 30, 2) }} a day</span>
                            <span class="pr-day" x-show="yearly" x-cloak>That is about ₱{{ number_format($tier['priceYear'] / 365, 2) }} a day</span>
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
            <p class="mt-6 text-center text-sm text-gray-500 reveal">
                Every plan includes the activities board, lots, notes with photos and voice, growth stages,
                the gallery and the farmer community. Upgrading happens inside the app, verified by our team.
            </p>
        </div>
    </section>

    {{-- ================= CREDITS ================= --}}
    <section class="anee-band">
        <div class="relative max-w-6xl mx-auto px-4 sm:px-6 py-16 sm:py-20">
            <div class="fx-row">
                <div class="reveal">
                    <p class="text-sm font-bold uppercase tracking-wider text-accent-400">AI credits</p>
                    <h2 class="mt-2 font-heading text-3xl sm:text-4xl font-bold text-white text-balance">Anee works by the question, not by the month</h2>
                    <p class="mt-4 text-[#cdd8c0] leading-relaxed">
                        The AI technician runs on credits so a quiet month costs you nothing extra.
                        A chat with Anee costs a few credits; the deep analyses — when to plant,
                        what to plant, the full season report — cost more because they read everything
                        you've recorded. Credits never expire.
                    </p>
                    <ul class="fx-list mt-5">
                        <li style="color:#e8efe1"><svg fill="none" stroke="#a8cc7e" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>Packs start at ₱99 — paid through GCash like everything else</li>
                        <li style="color:#e8efe1"><svg fill="none" stroke="#a8cc7e" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>Bigger packs carry bonus credits</li>
                        <li style="color:#e8efe1"><svg fill="none" stroke="#a8cc7e" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>Every feature shows its price in credits before you run it</li>
                    </ul>
                </div>
                <div class="reveal">
                    <div class="rounded-2xl bg-white/5 ring-1 ring-white/15 backdrop-blur p-6 max-w-sm mx-auto">
                        <p class="text-xs font-bold uppercase tracking-wider text-[#a8cc7e]">What credits buy</p>
                        <ul class="mt-4 space-y-3 text-sm text-[#e8efe1]">
                            <li class="flex justify-between gap-4"><span>A chat with Anee</span><span class="font-bold text-white whitespace-nowrap">a few credits</span></li>
                            <li class="flex justify-between gap-4"><span>When to Plant analysis</span><span class="font-bold text-white whitespace-nowrap">50 cr</span></li>
                            <li class="flex justify-between gap-4"><span>What to Plant analysis</span><span class="font-bold text-white whitespace-nowrap">100 cr</span></li>
                            <li class="flex justify-between gap-4"><span>Analyze the season so far</span><span class="font-bold text-white whitespace-nowrap">200 cr</span></li>
                            <li class="flex justify-between gap-4"><span>Full season report</span><span class="font-bold text-white whitespace-nowrap">300 cr</span></li>
                            <li class="flex justify-between gap-4"><span>Compare two reports</span><span class="font-bold text-white whitespace-nowrap">30 cr</span></li>
                        </ul>
                        <p class="mt-4 text-[11px] text-[#8fa383]">Credit prices are shown in-app before every run.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ================= HOW PAYING WORKS ================= --}}
    <section class="py-16 sm:py-20 bg-white">
        <div class="max-w-4xl mx-auto px-4 sm:px-6">
            <div class="max-w-2xl mx-auto text-center reveal">
                <p class="text-sm font-bold uppercase tracking-wider text-brand-600">No card needed</p>
                <h2 class="mt-2 font-heading text-3xl sm:text-4xl font-bold text-ink">Paying is a GCash send away</h2>
            </div>
            <div class="mt-10 grid gap-4 sm:grid-cols-3">
                @foreach ([
                    ['1', 'Pick your plan', 'Choose inside the app after signing up — plans and credit packs live in the same shop.'],
                    ['2', 'Send via GCash', 'Send the amount and upload your receipt right in the checkout.'],
                    ['3', 'We activate you', 'Our team verifies and emails you — renewals stack on your remaining days.'],
                ] as [$n, $t, $p])
                    <div class="card card-hover reveal text-center">
                        <div class="card-body">
                            <div class="mx-auto w-10 h-10 rounded-full bg-accent-500 text-ink font-heading font-bold text-lg flex items-center justify-center">{{ $n }}</div>
                            <h3 class="mt-3 font-heading font-bold text-ink">{{ $t }}</h3>
                            <p class="mt-2 text-sm text-gray-600 leading-relaxed">{{ $p }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ================= FAQ ================= --}}
    <section class="py-16 sm:py-20 bg-gray-50">
        <div class="max-w-3xl mx-auto px-4 sm:px-6">
            <h2 class="font-heading text-3xl font-bold text-ink text-center reveal">Fair questions</h2>
            <div class="mt-8 space-y-3">
                @foreach ([
                    ['Do my workers need their own subscriptions?', 'No. Worker logins ride on the owner\'s plan — you invite them, set what each may see or edit, and they use anee.io free.'],
                    ['What happens when my plan lapses?', 'Your data stays safe and readable. Renew any time and pick up exactly where the season left off — days from early renewals stack, nothing is wasted.'],
                    ['Do credits expire?', 'No. Credits sit on your account until you spend them, across seasons.'],
                    ['Can I use it on a computer too?', 'Yes — anee.io is a web app. It is built phone-first for the field, and the same account works in any browser.'],
                    ['Is my farm data private?', 'Yes. Your schedules, notes and money figures are yours alone unless you publish something to the community on purpose.'],
                ] as [$q, $a])
                    <details class="group rounded-2xl bg-white ring-1 ring-gray-200 px-5 py-4 reveal">
                        <summary class="cursor-pointer list-none flex items-center justify-between gap-3 font-heading font-bold text-ink">
                            {{ $q }}
                            <svg class="w-5 h-5 shrink-0 text-brand-600 transition group-open:rotate-45" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M12 5v14M5 12h14"/></svg>
                        </summary>
                        <p class="mt-3 text-sm text-gray-600 leading-relaxed">{{ $a }}</p>
                    </details>
                @endforeach
            </div>
            <div class="mt-10 text-center reveal">
                <a href="{{ route('signup') }}" class="btn btn-primary btn-lg">Start your season</a>
            </div>
        </div>
    </section>

@endsection
