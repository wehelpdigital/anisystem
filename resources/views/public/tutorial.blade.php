@extends('layouts.public')

@section('title', 'Tutorial — How AniSystem Works')
@section('meta_description', 'Step-by-step guide to AniSystem: create an account, pay via GCash, set up lots, workers, materials and services, build your activities timeline, plan irrigation and export your season.')

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
                From Sign-Up to Harvest, <span class="bg-gradient-to-r from-accent-300 to-accent-500 bg-clip-text text-transparent">Step by Step</span>
            </h1>
            <p class="mt-5 max-w-2xl mx-auto text-brand-100 text-base sm:text-lg">
                Everything you need to plan your first cropping season with AniSystem — in ten short steps.
            </p>
        </div>
    </section>

    {{-- ================= STEPS ================= --}}
    <section class="py-16 sm:py-24 bg-white">
        <div class="max-w-3xl mx-auto px-4 sm:px-6">
            @php
                $steps = [
                    [
                        'title' => 'Create your account',
                        'text' => 'Tap "Get Started" and sign up with your name, email address and a password. Your account is created instantly — no payment needed yet.',
                        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>',
                    ],
                    [
                        'title' => 'Choose a plan & pay via GCash',
                        'text' => 'Pick the plan that fits your season, send the exact amount through GCash, then upload a screenshot of your receipt (or type the reference number) on the payment page.',
                        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M5 6h14a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2zm2 8h4"/>',
                    ],
                    [
                        'title' => 'Wait for your verification email',
                        'text' => 'Our team checks your GCash payment manually — usually within a few hours during business days. The moment it\'s verified, you get an email and your subscription becomes active.',
                        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.9 5.3a2 2 0 002.2 0L21 8M5 5h14a2 2 0 012 2v10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2z"/>',
                    ],
                    [
                        'title' => 'Create your first cropping schedule',
                        'text' => 'One schedule = one season on one farm. Give it a title (for example "Wet Season 2026 — San Isidro") and choose how days are counted: DAS (days after sowing), DAP or DAT.',
                        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3M4 11h16M5 5h14a1 1 0 011 1v13a1 1 0 01-1 1H5a1 1 0 01-1-1V6a1 1 0 011-1z"/>',
                    ],
                    [
                        'title' => 'Add your lots',
                        'text' => 'Register each lot with its name, size, unit and rice variety. Each lot can have its own Day-0 (sowing) date — that anchor is what keeps every activity on the correct day per lot.',
                        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 20l-5-2V6l5 2m0 12l6-2m-6 2V8m6 10l5 2V8l-5-2m0 12V6m0 0L9 8"/>',
                    ],
                    [
                        'title' => 'Set up workers, materials & services',
                        'text' => 'Add your workers with their skills and daily rates, list the materials you\'ll use (seeds, fertilizers, biostimulants) with prices, and record hired services like tractor or drone spraying. These feed your cost summaries automatically.',
                        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-1.13a4 4 0 10-4-4 4 4 0 004 4zm6-2a3 3 0 10-3-3"/>',
                    ],
                    [
                        'title' => 'Build your activities timeline',
                        'text' => 'This is the heart of AniSystem. Add every activity — land prep, sowing, fertilization, spraying, harvest — with dates, priorities and assigned lots. Keep unfinished ideas as drafts, mark an activity as the Day-0 anchor, and save whole timelines as versions so you can compare plans or reuse them next season.',
                        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>',
                    ],
                    [
                        'title' => 'Plan your irrigation',
                        'text' => 'Create irrigation entries per lot group using DAS day ranges (for example day 7 to day 10) or exact calendar dates. The planner shows watering bands alongside your activities so nothing overlaps or gets missed.',
                        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 3s6 6.5 6 11a6 6 0 11-12 0c0-4.5 6-11 6-11z"/>',
                    ],
                    [
                        'title' => 'Keep your documentation',
                        'text' => 'Attach photos, protocol notes and critical rules to your schedule. Field documentation stays with the season, so you always have proof of what was done and when.',
                        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6M7 3h7l5 5v11a2 2 0 01-2 2H7a2 2 0 01-2-2V5a2 2 0 012-2z"/>',
                    ],
                    [
                        'title' => 'Export & share',
                        'text' => 'Export your activities schedule as a document, print worker presentations, or open the card viewer to walk your team through the plan. Your season, on paper or on screen — ready for anyone who needs it.',
                        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3M6.75 19.25h10.5A2.25 2.25 0 0019.5 17V9.75L14.25 4.5H6.75A2.25 2.25 0 004.5 6.75V17a2.25 2.25 0 002.25 2.25z"/>',
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
                                    <h3 class="font-heading text-lg sm:text-xl font-bold text-ink">{{ $step['title'] }}</h3>
                                </div>
                                <p class="mt-2 text-sm sm:text-base text-gray-600 leading-relaxed">{{ $step['text'] }}</p>
                            </div>
                        </div>
                    </li>
                @endforeach
            </ol>

            <div class="mt-12 text-center reveal">
                <a href="{{ route('signup') }}" class="btn btn-accent btn-lg">Start Step 1 — Create Your Account</a>
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
                        'q' => 'How long does payment verification take?',
                        'a' => 'Payments are verified manually by our team, usually within a few hours on business days and no longer than 24 hours. You\'ll receive an email the moment your subscription is activated — no need to keep checking.',
                    ],
                    [
                        'q' => 'Is GCash the only payment method?',
                        'a' => 'Yes, for now we accept GCash only, since it\'s the most accessible option for farmers across the Philippines. Simply send the exact plan amount and upload your receipt screenshot or reference number.',
                    ],
                    [
                        'q' => 'How do I renew my subscription?',
                        'a' => 'Go to your Account page, choose a plan and pay via GCash again — same simple process. Renewal days are added on top of your remaining time, so renewing early never wastes days.',
                    ],
                    [
                        'q' => 'What happens when my subscription expires?',
                        'a' => 'Your account gets locked from the schedule manager, but your data is NOT deleted. All your schedules, lots, workers and activities are kept safe — the moment you renew, everything is exactly where you left it.',
                    ],
                    [
                        'q' => 'Is my farm data safe?',
                        'a' => 'Yes. Your data is stored securely on our servers and is visible only to your account and the AniSenso support team. We never share your farm information with anyone else.',
                    ],
                    [
                        'q' => 'Can I use AniSystem on my phone?',
                        'a' => 'Absolutely — AniSystem is built mobile-first. Every screen works on any smartphone browser, so you can check today\'s activities, mark progress and add photos right from the field. No app installation needed.',
                    ],
                    [
                        'q' => 'Can I manage more than one farm or season?',
                        'a' => 'Yes. You can create multiple cropping schedules — one per season per farm — and switch between them anytime. Past seasons stay in your archive for reference and comparison.',
                    ],
                    [
                        'q' => 'What are DAS, DAP and DAT?',
                        'a' => 'They\'re ways of counting days from your crop\'s starting point: Days After Sowing, Days After Planting, and Days After Transplanting. You choose one per schedule, set each lot\'s Day-0 date, and AniSystem computes the exact calendar date of every activity for every lot.',
                    ],
                ];
            @endphp

            <div class="mt-10 space-y-3" x-data="{ openFaq: null }">
                @foreach ($faqs as $i => $faq)
                    <div class="card overflow-hidden reveal" style="--reveal-delay: {{ min($i, 6) * 0.04 }}s">
                        <button type="button"
                                class="w-full flex items-center justify-between gap-4 text-left px-5 py-4 sm:px-6 cursor-pointer hover:bg-brand-50/40 transition"
                                @click="openFaq = openFaq === {{ $i }} ? null : {{ $i }}"
                                :aria-expanded="openFaq === {{ $i }} ? 'true' : 'false'">
                            <span class="font-heading font-bold text-ink">{{ $faq['q'] }}</span>
                            <svg class="w-5 h-5 shrink-0 text-brand-600 transition-transform duration-200"
                                 :class="openFaq === {{ $i }} ? 'rotate-180' : ''"
                                 fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        <div x-show="openFaq === {{ $i }}" x-cloak x-transition.opacity.duration.150ms
                             class="px-5 sm:px-6 pb-5 -mt-1">
                            <p class="text-sm sm:text-base text-gray-600 leading-relaxed">{{ $faq['a'] }}</p>
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
                Our team is happy to help you get started — send us a message anytime.
            </p>
            <div class="mt-8 flex flex-col sm:flex-row justify-center gap-3">
                <a href="{{ route('contact') }}" class="btn btn-accent btn-lg">Contact Us</a>
                <a href="{{ route('signup') }}" class="btn btn-lg border-2 border-white/70 text-white bg-white/5 hover:bg-white/15">Get Started</a>
            </div>
        </div>
    </section>

@endsection
