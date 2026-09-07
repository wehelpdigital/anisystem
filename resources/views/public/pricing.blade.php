@extends('layouts.public')

@include('public.partials.site-css')

@section('title', 'Pricing — Plans for Every Farm')
@section('meta_description', 'anee.io pricing: simple plans paid via GCash, plus AI credits so you only pay Anee for what you ask. No hidden fees, cancel anytime.')

@section('content')

    {{-- ================= HERO ================= --}}
    <section class="relative isolate overflow-hidden bg-gradient-to-br from-brand-700 via-brand-800 to-brand-900">
        <div class="absolute inset-0 bg-dot-grid opacity-50" aria-hidden="true"></div>
        <div class="relative max-w-3xl mx-auto px-4 sm:px-6 py-16 sm:py-20 text-center animate-fade-up">
            <p class="text-sm font-bold uppercase tracking-wider text-accent-400">Simple, farmer-sized pricing</p>
            <h1 class="mt-2 font-heading text-4xl sm:text-5xl font-bold text-white text-balance">One Plan Runs the Whole Farm</h1>
            <p class="mt-5 text-brand-100 text-base sm:text-lg">
                Pay in pesos, through GCash, with no card required. The AI technician runs on credits
                on top — so you only ever pay Anee for what you actually ask.
            </p>
        </div>
    </section>

    {{-- ================= PLANS (live store rows) ================= --}}
    <section class="py-16 sm:py-20 bg-gray-50">
        <div class="max-w-5xl mx-auto px-4 sm:px-6">
            <div class="pr-grid">
                @foreach ($plans as $plan)
                    @php $isBest = $plans->count() > 1 && $loop->iteration === $plans->count(); @endphp
                    <div class="pr-card reveal {{ $isBest ? 'is-star' : '' }}" style="--reveal-delay: {{ $loop->index * 0.07 }}s">
                        @if ($isBest)<span class="pr-flag">Most complete</span>@endif
                        <span class="pr-name">{{ $plan->planName }}</span>
                        @if ($plan->description)<span class="pr-for">{{ $plan->description }}</span>@endif
                        <span class="pr-price">
                            <span class="pr-amount">₱{{ number_format((float) $plan->price, fmod((float) $plan->price, 1) > 0 ? 2 : 0) }}</span>
                            <span class="pr-per">/ {{ $plan->duration_label }}</span>
                        </span>
                        <span class="pr-year">Paid once via GCash — days stack when you renew early.</span>
                        @if (is_array($plan->features) && count($plan->features))
                            <ul class="pr-list">
                                @foreach ($plan->features as $feature)
                                    <li><svg fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>{{ $feature }}</li>
                                @endforeach
                            </ul>
                        @endif
                        <a href="{{ route('signup') }}" class="btn {{ $isBest ? 'btn-accent' : 'btn-primary' }}">Choose {{ $plan->planName }}</a>
                    </div>
                @endforeach
            </div>
            <p class="mt-6 text-center text-sm text-gray-500 reveal">
                Every plan includes the activities board, lots, notes with photos and voice, maps, weather,
                growth stages, reports, the gallery and the farmer community.
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
