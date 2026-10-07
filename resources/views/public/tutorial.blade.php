@extends('layouts.public')

{{-- TUTORIAL. Rewritten 2026-10-07 (audit) to match the app as it is now: an
     account is confirmed by an emailed link, Libre is free forever so
     nothing is paid to start, workers and inventory are Solo Farmer and up,
     Anee is Libre + Anee and up, all reports past Labor are Solo Farmer and
     up, and a plan is paid from My Subscription (GCash or bank at home,
     PayPal abroad) with the receipt checked by Anee or by a person. The ten
     steps follow a season from sign up to the reports; paying has its own
     band after them, read from the same shelf the checkout reads. --}}
@php
    $R = \App\Support\Region::class;
    $ph = $R::ph();
    $pay = $R::payMethod();
    $payCfg = \App\Support\ManualPay::settings();
    $hours = (int) $payCfg['reviewHours'];
    $fast = $ph && $payCfg['aiAutoApprove'];
    $fee = (float) $payCfg['gcashFee'];
    $payWays = $ph ? 'GCash or a bank transfer' : $pay;
    $price = fn ($tier) => $R::priceTag($R::tierPrice($tier, 'month'));
    $tierName = fn ($tier) => config('tiers.' . $tier . '.name');
@endphp

@section('title', 'Tutorial: How anee.io Works')
@section('meta_description', 'A step by step guide to anee.io: create your free account, start a season, add your lots, plan every task, follow the growth stage and weather, ask Anee, record the harvest and read your reports.')

@section('content')

    {{-- ================= HERO BAND ================= --}}
    <section class="relative isolate overflow-hidden bg-gradient-to-br from-brand-700 via-brand-800 to-brand-900">
        <img src="{{ asset('images/top-yield.webp') }}" alt="" aria-hidden="true"
             class="absolute inset-0 -z-20 h-full w-full object-cover opacity-20" loading="eager">
        <div class="absolute inset-0 -z-10 bg-dot-grid opacity-40" aria-hidden="true"></div>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 py-20 sm:py-28 text-center animate-fade-up">
            <span class="inline-flex items-center gap-2 rounded-full bg-white/10 backdrop-blur px-4 py-1.5 text-xs sm:text-sm font-bold uppercase tracking-wider text-accent-400 ring-1 ring-white/20">
                Tutorial
            </span>
            <h1 class="mt-5 font-heading text-3xl sm:text-5xl font-bold text-white leading-tight text-balance">
                From Sign Up to Harvest, <span class="bg-gradient-to-r from-accent-300 to-accent-500 bg-clip-text text-transparent">Step by Step</span>
            </h1>
            <p class="mt-5 max-w-2xl mx-auto text-brand-100 text-base sm:text-lg">
                Your first season on anee.io in ten short steps, from a free account to the reports at the end. Nothing to pay to start.
            </p>
        </div>
    </section>

    {{-- ================= STEPS ================= --}}
    <section class="py-16 sm:py-24 bg-white">
        <div class="max-w-3xl mx-auto px-4 sm:px-6">
            @php
                $steps = [
                    [
                        'title' => 'Create your free account',
                        'text' => 'Tap "Create your free account" and fill in your name, mobile number, email and a password. We send a link to your email: open it to confirm, and you are in. The Libre plan is free forever, so there is nothing to pay to start.',
                        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>',
                    ],
                    [
                        'title' => 'Start your first season',
                        'text' => 'Open Cropping Schedules and tap New Cropping Schedule. Give the season a name, like "' . ($ph ? 'Wet Season 2026, San Isidro' : 'Spring 2026, North Field') . '", and choose how its days are counted: DAS then DAT for rice sown and then transplanted, DAS for direct seeding, DAP for crops you plant, or tree age for an orchard. The season opens right away, with a checklist of what is still missing.',
                        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3M4 11h16M5 5h14a1 1 0 011 1v13a1 1 0 01-1 1H5a1 1 0 01-1-1V6a1 1 0 011-1z"/>',
                    ],
                    [
                        'title' => 'Add your lots',
                        'text' => 'Add each field as a lot with its name, size, crop and variety, and set its day zero: the day it was sown or planted. Every task then falls on the right date for that lot, even when lots are sown a week apart. Libre has one lot per season, ' . $tierName('solo') . ' five, and ' . $tierName('owner') . ' as many as you need.',
                        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 20l-5-2V6l5 2m0 12l6-2m-6 2V8m6 10l5 2V8l-5-2m0 12V6m0 0L9 8"/>',
                    ],
                    [
                        'title' => 'Plan every task on the board',
                        'text' => 'This is the heart of anee.io. Add each task on its day and lot: land preparation, sowing, fertilizer, sprays and harvest. Irrigation and hired services, like a tractor or a drone spray, go on the same board. Keep ideas as drafts and save versions to compare two plans. You can also write the whole season first in the Protocol Builder and make the season from it.',
                        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>',
                    ],
                    [
                        'title' => 'Add your workers and materials',
                        'text' => 'On the ' . $tierName('solo') . ' plan and up, add your workers with their daily rates, and stock the Inventory with your seed, fertilizer and sprays and what they cost. Put people and materials on each task, and the labor and material costs add themselves up.',
                        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-1.13a4 4 0 10-4-4 4 4 0 004 4zm6-2a3 3 0 10-3-3"/>',
                    ],
                    [
                        'title' => 'Work the day from the board',
                        'text' => 'Each morning, open today on the board and see every task on its lot. Tick it done, mark who worked, and add a photo or a voice note. Every tick is kept, so the record of your season builds up as you go.',
                        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4M7 3h10a2 2 0 012 2v14a2 2 0 01-2 2H7a2 2 0 01-2-2V5a2 2 0 012-2z"/>',
                    ],
                    [
                        'title' => 'Follow the growth stage and the weather',
                        'text' => 'Each lot shows its growth stage today, what to do now and what to watch for, with the weather for your farm beside it, so a spray is not wasted on the day before the rain. Libre shows the weather for today and tomorrow, and the paid plans show the days after.',
                        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.001 4.001 0 003 15z"/>',
                    ],
                    [
                        'title' => 'Ask Anee when something looks wrong',
                        'text' => 'Send Anee a photo of the sick leaf and ask ' . ($ph ? 'in Tagalog or English' : 'in plain English') . '. She answers with your season in mind: the lot, its stage, the weather and what you already applied. Anee comes with ' . $tierName('libreAnee') . ' for ' . $price('libreAnee') . ' a month, and with every plan above it.',
                        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>',
                    ],
                    [
                        'title' => 'Write down the harvest',
                        'text' => 'After harvest, open Observations and note each lot\'s yield, moisture, price and buyer. Next season you plan from real numbers, not from memory.',
                        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M5 9h14l-1.5 10a2 2 0 01-2 1.7h-7a2 2 0 01-2-1.7L5 9zm3 0V7a4 4 0 018 0v2"/>',
                    ],
                    [
                        'title' => 'See what the season earned',
                        'text' => 'Your records add up on their own. The Labor Report comes with every plan, and the Expenses and Profit reports and Anee\'s Season Report come with ' . $tierName('solo') . ' and up. To share the plan, export it as a document or a presentation for your workers.',
                        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M4 19h16M7 16v-4m5 4V8m5 8v-6"/>',
                    ],
                ];
            @endphp

            {{-- Connected vertical timeline --}}
            <ol class="relative space-y-5 sm:space-y-6 before:absolute before:top-2 before:bottom-2 before:left-6 before:w-0.5 before:bg-gradient-to-b before:from-brand-200 before:via-brand-300 before:to-brand-100 before:content-[''] sm:before:left-7">
                @foreach ($steps as $i => $step)
                    <li class="relative pl-16 sm:pl-20 reveal" style="--reveal-delay: {{ min($i, 6) * 0.05 }}s">
                        {{-- Number node sits on the line --}}
                        <div class="absolute left-0 top-1 z-10 w-12 h-12 sm:w-14 sm:h-14 rounded-full bg-brand-600 text-white font-heading text-lg sm:text-xl font-bold flex items-center justify-center shadow-md ring-4 ring-white">
                            {{ $i + 1 }}
                        </div>
                        <div class="card card-hover">
                            <div class="card-body">
                                <div class="flex items-center gap-2.5">
                                    <svg class="w-6 h-6 text-brand-600 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">{!! $step['icon'] !!}</svg>
                                    <h2 class="font-heading text-lg sm:text-xl font-bold text-ink">{{ $step['title'] }}</h2>
                                </div>
                                <p class="mt-2 text-sm sm:text-base text-gray-600 leading-relaxed">{{ $step['text'] }}</p>
                            </div>
                        </div>
                    </li>
                @endforeach
            </ol>

            <div class="mt-12 text-center reveal">
                <a href="{{ route('signup') }}" class="btn btn-accent btn-lg">Create your free account</a>
                <p class="mt-3 text-sm text-gray-500">Free forever on Libre. No card needed.</p>
            </div>
        </div>
    </section>

    {{-- ================= PLANS AND PAYMENT ================= --}}
    {{-- The plans from config/tiers (names and prices never typed twice), then
         how a plan is paid for, in the order the app asks for it. --}}
    <style>
        .tu-plans { display: grid; gap: .75rem; margin-top: 2rem; }
        @media (min-width: 640px) { .tu-plans { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 1rem; } }
        .tu-plan { display: flex; flex-direction: column; gap: .35rem; padding: 1.1rem 1.2rem; border-radius: 1.1rem; background: #fff; border: 1px solid #e5ebdf;
            transition: transform .28s cubic-bezier(.22,1,.36,1), box-shadow .28s cubic-bezier(.22,1,.36,1), border-color .28s cubic-bezier(.22,1,.36,1); }
        .tu-plan:hover { transform: translateY(-3px); border-color: #b9d69a; box-shadow: 0 18px 34px -26px rgb(20 33 12 / .55); }
        .tu-plan.is-free { border-color: #b9d69a; background: linear-gradient(170deg, #f3f8ec, #fff 70%); }
        .tu-plan-top { display: flex; align-items: baseline; justify-content: space-between; gap: .75rem; }
        .tu-plan-top b { font-family: var(--font-heading); font-size: 1.1rem; font-weight: 800; color: #14210c; }
        .tu-plan-top span { flex: none; font-weight: 800; color: #2f5219; }
        .tu-plan-top span small { font-weight: 600; color: #6b7280; }
        .tu-plan p { font-size: .92rem; line-height: 1.55; color: #4b5563; }
        .tu-pay { margin-top: 2.5rem; padding: 1.4rem 1.2rem; border-radius: 1.3rem; background: #fff; border: 1px solid #e5ebdf; }
        @media (min-width: 640px) { .tu-pay { padding: 1.8rem; } }
        .tu-pay h3 { font-family: var(--font-heading); font-size: 1.25rem; font-weight: 800; color: #14210c; }
        .tu-pay ol { margin-top: 1rem; display: grid; gap: .9rem; counter-reset: tp; }
        .tu-pay li { position: relative; padding-left: 2.6rem; font-size: .95rem; line-height: 1.6; color: #4b5563; counter-increment: tp; }
        .tu-pay li::before { content: counter(tp); position: absolute; left: 0; top: -.1rem; width: 1.9rem; height: 1.9rem; border-radius: 999px; display: grid; place-items: center;
            font-family: var(--font-heading); font-weight: 800; font-size: .9rem; color: #14210c; background: #f5c518; }
        .tu-pay li b { color: #14210c; }
        .tu-pay-note { margin-top: 1rem; padding-top: .9rem; border-top: 1px solid #eef2ea; font-size: .88rem; color: #6b7280; }
        .tu-links { margin-top: 1.6rem; display: flex; flex-wrap: wrap; justify-content: center; gap: .6rem; }
        @media (prefers-reduced-motion: reduce) { .tu-plan { transition: none; } .tu-plan:hover { transform: none; } }
    </style>
    <section class="py-16 sm:py-20 bg-gray-50">
        <div class="max-w-3xl mx-auto px-4 sm:px-6">
            <div class="text-center reveal">
                <p class="text-sm font-bold uppercase tracking-wider text-brand-600">Plans and payment</p>
                <h2 class="mt-2 font-heading text-3xl sm:text-4xl font-bold text-ink text-balance">Start Free, Upgrade When You Need More</h2>
                <p class="mt-3 text-gray-600">Every step above works on the free plan, except where it names a paid one.</p>
            </div>
            @php
                $plans = [
                    ['libre', 'One season with one lot, the board, notes, growth stages, weather for today and tomorrow, and the Labor Report.'],
                    ['libreAnee', 'Everything in Libre, plus Anee: chat with photos, the four analyses, and Realign by Anee.'],
                    ['solo', 'Three active seasons with five lots each, workers, inventory, all the reports, full weather and offline mode.'],
                    ['owner', 'No limit on seasons or lots, a login for each worker, and the Collab Room for your team.'],
                ];
            @endphp
            <div class="tu-plans">
                @foreach ($plans as $i => [$key, $line])
                    @php $p = $R::tierPrice($key, 'month'); @endphp
                    <div class="tu-plan reveal {{ ! $p ? 'is-free' : '' }}" style="--reveal-delay: {{ $i * 0.06 }}s">
                        <div class="tu-plan-top">
                            <b>{{ $tierName($key) }}</b>
                            <span>@if ($p){{ $R::priceTag($p) }} <small>a month</small>@else Free <small>forever</small>@endif</span>
                        </div>
                        <p>{{ $line }}</p>
                    </div>
                @endforeach
            </div>

            <div class="tu-pay reveal">
                <h3>How to pay for a plan</h3>
                <ol>
                    <li>In the app, open <b>My Subscription</b> and pick a plan, by the month or by the year.</li>
                    <li>Pay with <b>{{ $payWays }}</b>.@if ($ph && $fee > 0) GCash adds a {{ $R::priceTag($fee) }} processing fee.@endif</li>
                    @if ($fast)
                        <li>Send a <b>screenshot of the receipt</b> on the same screen. Anee checks a GCash receipt in about a minute, and your plan starts right away. A bank transfer, or a reference number with no screenshot, is checked by a person, usually within {{ $hours }} hours.</li>
                    @else
                        <li>Send the <b>receipt</b> on the same screen. A person checks it, usually within {{ $hours }} hours, and your plan turns on by itself.</li>
                    @endif
                </ol>
                <p class="tu-pay-note">You get an email once it is approved. Nothing renews by itself, so you are never charged without knowing.</p>
            </div>

            <div class="tu-links reveal">
                <a href="{{ route('pricing') }}" class="btn btn-accent">See the prices</a>
                <a href="{{ route('pricing.compare') }}" class="btn btn-outline">Compare every plan</a>
            </div>
        </div>
    </section>

    {{-- ================= FAQ ================= --}}
    <section class="py-16 sm:py-24 bg-brand-mesh">
        <div class="max-w-3xl mx-auto px-4 sm:px-6">
            <div class="text-center reveal">
                <p class="text-sm font-bold uppercase tracking-wider text-brand-600">Common questions</p>
                <h2 class="mt-2 font-heading text-3xl sm:text-4xl font-bold text-ink text-balance">Frequently Asked Questions</h2>
            </div>

            @php
                $faqs = [
                    [
                        'q' => 'How long until my plan starts?',
                        'a' => $fast
                            ? 'A GCash receipt screenshot is the fastest: Anee checks it in about a minute and your plan starts right away. A bank transfer, or a reference number with no screenshot, is checked by a person, usually within ' . $hours . ' hours. You get an email once it is approved, so there is no need to keep checking.'
                            : 'A person checks your payment, usually within ' . $hours . ' hours. You get an email once it is approved, and your plan turns on by itself, so there is no need to keep checking.',
                    ],
                    [
                        'q' => 'How can I pay?',
                        'a' => $ph
                            ? 'With GCash or a bank transfer, from My Subscription in the app. GCash is the fastest way, because Anee can check its receipt in about a minute.'
                            : 'With ' . $pay . ', from My Subscription in the app. Send the receipt on the same screen and a person checks it.',
                    ],
                    [
                        'q' => 'How do I renew my plan?',
                        'a' => 'Open My Subscription, pick the same plan and pay again, the same way as before. If your plan is still running, the new one waits and starts the day the old one ends, so renewing early never wastes days.',
                    ],
                    [
                        'q' => 'What happens when my plan ends?',
                        'a' => 'You go back to the free Libre plan. Nothing is deleted: your seasons, lots, workers and records stay exactly where they are. The tools above Libre lock until you renew, and you can renew any time.',
                    ],
                    [
                        'q' => 'Is my farm data safe?',
                        'a' => 'Yes. Your farm records are private to your account: only you, the people you invite to your farm, and our support team can open them. We never sell your information. Our Privacy Policy explains the rest.',
                    ],
                    [
                        'q' => 'Can I use anee.io on my phone?',
                        'a' => 'Yes. anee.io is made for phones first. Every screen works in your phone\'s browser, so you can check today\'s tasks, tick them done and add photos right from the field. No app to install.',
                    ],
                    [
                        'q' => 'Can I manage more than one farm or season?',
                        'a' => 'Yes. Each season is its own cropping schedule, and you switch between them any time. Libre keeps one active season, ' . $tierName('solo') . ' three, and ' . $tierName('owner') . ' as many as you need. Closed seasons stay on record so you can look back and compare.',
                    ],
                    [
                        'q' => 'What are DAS, DAP and DAT?',
                        'a' => 'They are ways of counting days from your crop\'s starting point: Days After Sowing, Days After Planting and Days After Transplanting. You choose one for each season, set each lot\'s day zero, and anee.io works out the calendar date of every task for every lot.',
                    ],
                ];
            @endphp

            <style>
                /* The answer unrolls and rolls back up on the house easing. */
                .tq-a { display: grid; grid-template-rows: 0fr; opacity: 0;
                    transition: grid-template-rows .3s cubic-bezier(.22,1,.36,1), opacity .3s cubic-bezier(.22,1,.36,1); }
                .tq-a.is-open { grid-template-rows: 1fr; opacity: 1; }
                .tq-a > div { min-height: 0; overflow: hidden; }
                .tq-chev { transition: transform .28s cubic-bezier(.22,1,.36,1); }
                @media (prefers-reduced-motion: reduce) { .tq-a, .tq-chev { transition: none; } }
            </style>
            <div class="mt-10 space-y-3" x-data="{ openFaq: null }">
                @foreach ($faqs as $i => $faq)
                    <div class="card overflow-hidden reveal" style="--reveal-delay: {{ min($i, 6) * 0.04 }}s">
                        <button type="button"
                                class="w-full flex items-center justify-between gap-4 text-left px-5 py-4 sm:px-6 cursor-pointer hover:bg-brand-50/40 transition"
                                @click="openFaq = openFaq === {{ $i }} ? null : {{ $i }}"
                                :aria-expanded="openFaq === {{ $i }} ? 'true' : 'false'">
                            <span class="font-heading font-bold text-ink">{{ $faq['q'] }}</span>
                            <svg class="w-5 h-5 shrink-0 text-brand-600 tq-chev"
                                 :class="openFaq === {{ $i }} ? 'rotate-180' : ''"
                                 fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        <div class="tq-a" :class="{ 'is-open': openFaq === {{ $i }} }">
                            <div><p class="px-5 sm:px-6 pb-5 -mt-1 text-sm sm:text-base text-gray-600 leading-relaxed">{{ $faq['a'] }}</p></div>
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
            <h2 class="font-heading text-3xl sm:text-4xl font-bold text-white text-balance">Still Have Questions?</h2>
            <p class="mt-4 max-w-xl mx-auto text-brand-100">
                Our team is happy to help you get started. Write to us any time, and a real person answers.
            </p>
            <div class="mt-8 flex flex-col sm:flex-row justify-center gap-3">
                <a href="{{ route('contact') }}" class="btn btn-accent btn-lg">Contact us</a>
                <a href="{{ route('signup') }}" class="btn btn-lg border-2 border-white/70 text-white bg-white/5 hover:bg-white/15">Create your free account</a>
            </div>
        </div>
    </section>

@endsection
