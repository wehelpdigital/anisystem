@extends('layouts.public')

@include('public.partials.site-css')

@section('title', 'Pricing: Plans for Every Farm')
@section('meta_description', 'anee.io pricing: simple plans paid via ' . \App\Support\Region::payMethod() . ', plus AI credits so you only pay Anee for what you ask. No hidden fees, cancel anytime.')

@section('content')

    {{-- ================= HERO ================= --}}
    <section class="relative isolate overflow-hidden bg-gradient-to-br from-brand-700 via-brand-800 to-brand-900 spark-field">
        <div class="absolute inset-0 bg-dot-grid opacity-50" aria-hidden="true"></div>
        <div class="relative max-w-3xl mx-auto px-4 sm:px-6 py-16 sm:py-20 text-center animate-fade-up" style="z-index:1">
            <p class="text-sm font-bold uppercase tracking-wider text-accent-400">Simple, farmer-sized pricing</p>
            <h1 class="mt-2 font-heading text-4xl sm:text-5xl font-bold text-white text-balance">Start Free. Grow When You're Ready.</h1>
            <p class="mt-5 text-brand-100 text-base sm:text-lg">
                The Libre plan is free forever, with no card and no trial clock. Upgrades are paid in {{ \App\Support\Region::currencyName() }},
                through {{ \App\Support\Region::payMethod() }}. The AI technician runs on credits on top, so you only ever pay Anee
                for what you actually ask.
            </p>
        </div>
    </section>

    {{-- ================= THE PLANS, AS A DECK =================
         One plan is read at a time. Libre sits in front when the page opens
         and the paid plans wait behind it as a faded stack; tapping one (or
         its name in the row above, or swiping the front card on a phone)
         deals it to the front, and the card that was there tucks back in
         just behind it. Every card is as tall as the one in front, so the
         deck glides when that changes. The motion lives in the script at
         the foot of this file, the dress in the pd- styles beside it. --}}
    @php
        $pdKeys  = array_keys($tiers);
        $pdFree  = collect($tiers)->search(fn ($t) => empty($t['price']));
        $pdOrder = array_values(array_unique(array_merge($pdFree !== false ? [$pdFree] : [], $pdKeys)));
        $pdTint  = ['libre' => '#86b556', 'libreAnee' => '#f5c518', 'solo' => '#4a7c2a', 'owner' => '#2d5016'];
        $pdSpare = ['#86b556', '#f5c518', '#4a7c2a', '#2d5016', '#6b9f3d', '#c79e00'];
    @endphp
    <section class="py-16 sm:py-20 bg-gray-50 bg-drift" x-data="pdDeck()">
        <div class="pd-scope max-w-6xl mx-auto px-4 sm:px-6">
            <div class="pd-head reveal">
                <div class="pd-seg pd-bill" role="group" aria-label="Billing period">
                    <span class="pd-thumb" aria-hidden="true"></span>
                    <button type="button" aria-pressed="true" :aria-pressed="yearly ? 'false' : 'true'" @click="yearly = false">Monthly</button>
                    <button type="button" aria-pressed="false" :aria-pressed="yearly ? 'true' : 'false'" @click="yearly = true">Yearly <span class="pd-soft">· save more</span></button>
                </div>
                <div class="pd-seg pd-tabs" role="tablist" aria-label="Plans">
                    <span class="pd-thumb" aria-hidden="true"></span>
                    @foreach ($tiers as $key => $tier)
                        @php $pdOn = $key === $pdOrder[0]; @endphp
                        <button type="button" class="pd-tab" role="tab" id="pd-tab-{{ $key }}" aria-controls="pd-panel-{{ $key }}"
                                aria-selected="{{ $pdOn ? 'true' : 'false' }}" tabindex="{{ $pdOn ? '0' : '-1' }}"
                                style="--pd-tint: {{ $pdTint[$key] ?? $pdSpare[$loop->index % count($pdSpare)] }}"><i class="pd-dot" aria-hidden="true"></i>{{ $tier['name'] }}</button>
                    @endforeach
                </div>
            </div>

            <div class="pd-stage reveal" style="--reveal-delay: .08s">
                <div class="pd-deck" style="--pd-n: {{ count($pdOrder) - 1 }}">
                    @foreach ($tiers as $key => $tier)
                        @php
                            $isStar = $key === 'owner';
                            $isFree = empty($tier['price']);
                            $pdSlot = array_search($key, $pdOrder, true);
                        @endphp
                        <div class="pd-card {{ $pdSlot === 0 ? 'is-front' : 'is-back' }} {{ $isStar ? 'is-star' : '' }}"
                             data-name="{{ $tier['name'] }}" data-slot="{{ $pdSlot }}"
                             style="--s: {{ $pdSlot }}; z-index: {{ 10 + (count($pdOrder) - $pdSlot) * 2 }}; --pd-tint: {{ $pdTint[$key] ?? $pdSpare[$loop->index % count($pdSpare)] }}"
                             @if ($pdSlot) role="button" tabindex="0" aria-label="Show the {{ $tier['name'] }} plan" @endif>
                            @if ($isStar)<span class="pr-flag">Most complete</span>@endif
                            <div class="pd-face" id="pd-panel-{{ $key }}" role="tabpanel" aria-labelledby="pd-tab-{{ $key }}"
                                 @if ($pdSlot) inert aria-hidden="true" @else tabindex="0" @endif>
                                <span class="pd-band" aria-hidden="true"></span>
                                <div class="pd-inner">
                                    <span class="pr-name">{{ $tier['name'] }}</span>
                                    <span class="pr-for">{{ $tier['tagline'] }}</span>

                                    @if ($isFree)
                                        <span class="pr-price">
                                            <span class="pr-amount is-free">Free</span>
                                            <span class="pr-per">forever</span>
                                        </span>
                                        <span class="pr-year">No card. No trial clock. Yours to keep.</span>
                                        <span class="pr-day">{{ \App\Support\Region::money(0) }} a day, for as long as you like</span>
                                    @else
                                        {{-- Pesos at home, dollars on the international face (App\Support\Region). --}}
                                        @php $prM = \App\Support\Region::tierPrice($key, 'month'); $prY = \App\Support\Region::tierPrice($key, 'year'); @endphp
                                        <span class="pr-price" x-show="!yearly">
                                            <span class="pr-amount">{{ \App\Support\Region::priceTag($prM) }}</span>
                                            <span class="pr-per">/ month</span>
                                        </span>
                                        <span class="pr-price" x-show="yearly" x-cloak>
                                            <span class="pr-amount">{{ \App\Support\Region::priceTag($prY) }}</span>
                                            <span class="pr-per">/ year</span>
                                        </span>
                                        <span class="pr-year" x-show="!yearly">or {{ \App\Support\Region::priceTag($prY) }} a year, about {{ \App\Support\Region::priceTag(round($prY / 12, 2)) }} a month</span>
                                        <span class="pr-year" x-show="yearly" x-cloak>That is about {{ \App\Support\Region::priceTag(round($prY / 12, 2)) }} a month, paid once through {{ \App\Support\Region::payMethod() }}</span>
                                        {{-- What it actually costs to run, said the way a farmer
                                             counts: by the day. A month is an abstraction; a peso a
                                             day is a number you can hold against anything else you
                                             buy on a Tuesday. --}}
                                        <span class="pr-day" x-show="!yearly">That is about {{ \App\Support\Region::money($prM / 30) }} a day</span>
                                        <span class="pr-day" x-show="yearly" x-cloak>That is about {{ \App\Support\Region::money($prY / 365) }} a day</span>
                                    @endif

                                    <ul class="pr-list">
                                        @foreach ($tier['features'] as $feature)
                                            <li><svg fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>{{ $feature }}</li>
                                        @endforeach
                                        @foreach ($tier['excludes'] as $missing)
                                            <li class="is-off"><svg fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg><span><span class="sr-only">Not included: </span>{{ $missing }}</span></li>
                                        @endforeach
                                    </ul>
                                </div>
                                <span class="pd-fog" aria-hidden="true"></span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <p class="pd-free reveal">
                <span class="pd-free-ico" aria-hidden="true"><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 20A7 7 0 0 1 9.8 6.1C15.5 5 17 4.48 19 2c1 2 2 4.18 2 8 0 5.5-4.78 10-10 10Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2 21c0-3 1.85-5.36 5.08-6C9.5 14.52 12 13 13 12"/></svg></span>
                <span>Start for free forever on the {{ $pdFree !== false ? $tiers[$pdFree]['name'] : 'Libre' }} plan. <strong>No payments, no trial time.</strong></span>
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
                        A chat with Anee costs a few credits. The deep analyses, like when to plant,
                        what to plant and the full season report, cost more because they read everything
                        you've recorded. Credits never expire.
                    </p>
                    <ul class="fx-list mt-5">
                        <li style="color:#e8efe1"><svg fill="none" stroke="#a8cc7e" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>Packs start at {{ \App\Support\Region::priceTag(\App\Support\Region::packPrice(\App\Models\AiCreditPack::where('deleteStatus', 1)->where('isActive', 1)->orderBy('price')->first() ?: (object) ['price' => 99, 'packKey' => 'starter'])) }}, paid through {{ \App\Support\Region::payMethod() }} like everything else</li>
                        <li style="color:#e8efe1"><svg fill="none" stroke="#a8cc7e" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>Bigger packs carry bonus credits</li>
                        <li style="color:#e8efe1"><svg fill="none" stroke="#a8cc7e" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>Every feature shows its price in credits before you run it</li>
                    </ul>
                </div>
                <div class="reveal">
                    <div class="rounded-2xl bg-white/5 ring-1 ring-white/15 backdrop-blur p-6 max-w-sm mx-auto">
                        <p class="text-xs font-bold uppercase tracking-wider text-[#a8cc7e]">What credits buy</p>
                        <ul class="mt-4 space-y-3 text-sm text-[#e8efe1]">
                            <li class="flex justify-between gap-4"><span>A chat with Anee</span><span class="font-bold text-white whitespace-nowrap">a few credits</span></li>
                            <li class="flex justify-between gap-4"><span>When to Plant analysis</span><span class="font-bold text-white whitespace-nowrap">{{ \App\Support\AiPrices::of('wtp') }} cr</span></li>
                            <li class="flex justify-between gap-4"><span>What to Plant analysis</span><span class="font-bold text-white whitespace-nowrap">{{ \App\Support\AiPrices::of('what') }} cr</span></li>
                            <li class="flex justify-between gap-4"><span>Analyze the season so far</span><span class="font-bold text-white whitespace-nowrap">{{ \App\Support\AiPrices::of('sofar') }} cr</span></li>
                            <li class="flex justify-between gap-4"><span>Full season report</span><span class="font-bold text-white whitespace-nowrap">{{ \App\Support\AiPrices::of('season') }} cr</span></li>
                            <li class="flex justify-between gap-4"><span>Compare two reports</span><span class="font-bold text-white whitespace-nowrap">{{ \App\Support\AiPrices::of('compare') }} cr</span></li>
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
                <h2 class="mt-2 font-heading text-3xl sm:text-4xl font-bold text-ink">Paying is a {{ \App\Support\Region::payMethod() }} send away</h2>
            </div>
            <div class="mt-10 grid gap-4 sm:grid-cols-3">
                @foreach ([
                    ['1', 'Pick your plan', 'Choose inside the app after signing up. Plans and credit packs live in the same shop.'],
                    ['2', 'Send via ' . \App\Support\Region::payMethod(), 'Send the amount and upload your receipt right in the checkout.'],
                    ['3', 'We activate you', 'Our team verifies and emails you. Renewals stack on your remaining days.'],
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
                    ['Do my workers need their own subscriptions?', 'No. Worker logins ride on the owner\'s plan. You invite them, set what each may see or edit, and they use anee.io free.'],
                    ['What happens when my plan lapses?', 'Your data stays safe and readable. Renew any time and pick up exactly where the season left off. Days from early renewals stack, so nothing is wasted.'],
                    ['Do credits expire?', 'No. Credits sit on your account until you spend them, across seasons.'],
                    ['Can I use it on a computer too?', 'Yes. anee.io is a web app. It is built phone-first for the field, and the same account works in any browser.'],
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

{{-- The plan deck's dress. Its own pd- names, so the shared .pr- card rules
     (site-css, also worn by home and features) are only ever overridden
     inside the deck, never changed. --}}
@push('head')
<style>
    .pd-scope { --pd-ease: cubic-bezier(.22,1,.36,1); }
    .pd-head { display: flex; flex-direction: column; align-items: center; gap: .85rem; }

    /* Two segmented rows (billing, plans), each with a thumb that slides
       under whichever button is on. Until the script places the thumb,
       the button itself wears the green. */
    .pd-seg { position: relative; display: inline-flex; align-items: center; gap: .2rem; max-width: 100%;
        padding: .25rem; border-radius: 999px; background: #fff;
        box-shadow: 0 0 0 1px #e5e7eb, 0 14px 30px -24px rgb(20 33 12 / .5); }
    .pd-seg > button { position: relative; z-index: 1; display: inline-flex; align-items: center; justify-content: center;
        gap: .45rem; border: 0; background: transparent; border-radius: 999px; padding: .45rem 1rem;
        font-size: .875rem; font-weight: 700; line-height: 1.25; color: #6b7280; white-space: nowrap; cursor: pointer;
        -webkit-tap-highlight-color: transparent;
        transition: color .3s var(--pd-ease), background-color .3s var(--pd-ease); }
    .pd-seg > button:hover { color: #2f5219; }
    .pd-seg > button[aria-pressed="true"], .pd-seg > button[aria-selected="true"] { color: #fff; }
    .pd-seg:not(.is-live) > button[aria-pressed="true"],
    .pd-seg:not(.is-live) > button[aria-selected="true"] { background: #4a7c2a; }
    .pd-seg > button:focus-visible { outline: 2px solid #86b556; outline-offset: 2px; }
    .pd-soft { font-weight: 500; opacity: .85; }
    .pd-thumb { position: absolute; left: 0; top: 0; width: 0; height: 0; border-radius: 999px; opacity: 0;
        background: #4a7c2a; box-shadow: 0 8px 18px -10px rgb(47 82 25 / .75); pointer-events: none;
        transition: transform .45s var(--pd-ease), width .45s var(--pd-ease), height .45s var(--pd-ease), opacity .3s var(--pd-ease); }
    .pd-seg.is-live .pd-thumb { opacity: 1; }
    .pd-tabs > button { padding: .45rem .9rem; font-size: .84rem; }
    .pd-dot { flex: none; width: .5rem; height: .5rem; border-radius: 999px; background: var(--pd-tint);
        transition: background-color .3s var(--pd-ease); }
    .pd-tab[aria-selected="true"] .pd-dot { background: #fff; }
    /* Four names will not sit in one row on a phone: a tidy two by two. */
    @media (max-width: 479.98px) {
        .pd-tabs { display: grid; grid-template-columns: 1fr 1fr; width: 100%; max-width: 22rem; border-radius: 1.15rem; }
        .pd-tabs > button { border-radius: .9rem; padding: .6rem .5rem; }
        .pd-tabs .pd-thumb { border-radius: .9rem; }
    }

    /* ---- the deck ----
       Every card sits in the same grid cell. --s is a card's place in the
       pile (0 is the front); each place back peeks up one --pd-step and
       shrinks by --pd-shrink. The script keeps the cards as tall as the
       front one (--pd-h), so the deck's height follows it. */
    .pd-stage { margin-top: 1.6rem; }
    .pd-deck { --pd-step: 13px; --pd-shrink: .05; position: relative; display: grid; max-width: 27rem; margin: 0 auto;
        padding-top: calc(var(--pd-step) * var(--pd-n, 3) + 8px); touch-action: pan-y; }
    @media (min-width: 640px) { .pd-deck { --pd-step: 18px; --pd-shrink: .045; max-width: 28rem; } }
    .pd-card { grid-area: 1 / 1; align-self: start; position: relative; height: var(--pd-h, auto);
        border-radius: 1.35rem; transform-origin: 50% 0; outline: none; -webkit-tap-highlight-color: transparent;
        transform: translate3d(0, calc(var(--s, 0) * var(--pd-step) * -1 - var(--pd-lift, 0px)), 0) rotate(0deg) scale(calc(1 - var(--s, 0) * var(--pd-shrink)));
        box-shadow: 0 10px 24px -18px rgb(20 33 12 / .45);
        transition: transform .55s var(--pd-ease), height .5s var(--pd-ease), box-shadow .45s var(--pd-ease); }
    .pd-card.is-front { box-shadow: 0 34px 70px -38px rgb(20 33 12 / .55), 0 3px 10px -6px rgb(20 33 12 / .16); }
    .pd-card.is-back { cursor: pointer; user-select: none; }
    .pd-face { position: relative; height: 100%; overflow: hidden; border-radius: inherit; outline: none;
        background: #fff; border: 1px solid #e1e8d7; transition: border-color .45s var(--pd-ease); }
    .pd-card.is-star.is-front .pd-face { border-color: #4a7c2a; }
    .pd-band { position: absolute; left: 0; right: 0; top: 0; z-index: 1; height: 6px; background: var(--pd-tint); }
    .pd-inner { display: flex; flex-direction: column; padding: 1.6rem 1.4rem 1.6rem;
        transition: opacity .35s var(--pd-ease); }
    .pd-card.is-back .pd-inner { opacity: 0; }
    /* The fade that sends a card into the background: deeper in the pile,
       further away. A hovered (or focused) card behind clears a little. */
    .pd-fog { position: absolute; inset: 0; z-index: 2; pointer-events: none; background: #e9eee2;
        opacity: calc((min(var(--s, 0), 1) * .24 + var(--s, 0) * .16) * var(--pd-fogk, 1));
        transition: opacity .45s var(--pd-ease); }
    @media (hover: hover) {
        .pd-card.is-back:not(.is-flying):hover { --pd-lift: 8px; --pd-fogk: .5; }
    }
    .pd-card.is-back:not(.is-flying):focus-visible { --pd-lift: 8px; --pd-fogk: .5; outline: 3px solid #86b556; outline-offset: 2px; }
    .pd-face:focus-visible { outline: 3px solid #86b556; outline-offset: 3px; }
    /* A card being dealt or dragged is moved by the script, not by these. */
    .pd-card.is-flying, .pd-card.is-dragging { transition: height .5s var(--pd-ease), box-shadow .45s var(--pd-ease); }
    .pd-card.is-flying .pd-inner, .pd-card.is-flying .pd-fog { transition: none; }
    .pd-deck.pd-instant .pd-card, .pd-deck.pd-instant .pd-inner,
    .pd-deck.pd-instant .pd-fog, .pd-deck.pd-instant .pr-flag { transition: none; }

    .pd-card .pr-flag { z-index: 3; transform: translate(-50%, 0);
        transition: opacity .35s var(--pd-ease) .2s, transform .45s var(--pd-ease) .2s; }
    .pd-card.is-back .pr-flag { opacity: 0; transform: translate(-50%, .5rem); transition-delay: 0s; }

    /* The card's own words, a little larger now that one is read at a time. */
    .pd-inner .pr-name { font-size: 1.3rem; }
    .pd-inner .pr-amount { font-size: 2.6rem; line-height: 1.1; }
    .pd-inner .pr-list { margin-top: 1.2rem; padding-top: 1.15rem; border-top: 1px dashed #dbe5cf; }
    /* Monthly and yearly swap by x-show; whichever appears rises in. */
    .pd-card .pr-price, .pd-card .pr-year, .pd-card .pr-day { animation: pdSwap .45s var(--pd-ease) both; }
    @keyframes pdSwap { from { opacity: 0; transform: translateY(6px); } to { opacity: 1; transform: none; } }

    /* The one line under the deck. */
    .pd-free { display: flex; align-items: center; justify-content: center; gap: .8rem; width: fit-content; max-width: 100%;
        margin: 2.4rem auto 0; padding: .8rem 1.3rem .8rem .85rem; border-radius: 1.3rem; background: #fff;
        border: 1px solid #dcead0; box-shadow: 0 18px 40px -30px rgb(20 33 12 / .5);
        font-family: var(--font-heading); font-size: 1rem; font-weight: 700; line-height: 1.4; color: #14210c; }
    .pd-free strong { color: #4a7c2a; font-weight: 800; }
    .pd-free-ico { flex: none; display: grid; place-items: center; width: 2.3rem; height: 2.3rem; border-radius: 999px;
        background: #eef5e5; color: #4a7c2a; }
    .pd-free-ico svg { width: 1.2rem; height: 1.2rem; }
    @media (max-width: 479.98px) { .pd-free { font-size: .93rem; padding-right: 1rem; } }

    @media (prefers-reduced-motion: reduce) {
        .pd-seg > button, .pd-thumb, .pd-card, .pd-face, .pd-inner, .pd-fog, .pd-card .pr-flag { transition: none !important; }
        .pd-card .pr-price, .pd-card .pr-year, .pd-card .pr-day { animation: none; }
    }
    html.sm-still .pd-seg > button, html.sm-still .pd-thumb, html.sm-still .pd-card, html.sm-still .pd-face,
    html.sm-still .pd-inner, html.sm-still .pd-fog, html.sm-still .pd-card .pr-flag { transition: none !important; }
    html.sm-still .pd-card .pr-price, html.sm-still .pd-card .pr-year, html.sm-still .pd-card .pr-day { animation: none; }
</style>
@endpush

@push('scripts')
<script>
    /* The plan deck. `order` lists the cards front to back. A card picked
       from the pile slides out to one side from behind it while the front
       card fades and drops toward the other; at the turn they swap layers,
       then the picked card settles in front and the old one tucks in right
       behind it. Anything between them in the pile moves back one place on
       its CSS transition. Picks made while a deal is running wait their
       turn (the last one wins); with reduced motion the deck just swaps. */
    (() => {
        const EASE = 'cubic-bezier(.22,1,.36,1)';
        const still = () => (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches)
            || document.documentElement.classList.contains('sm-still');

        // The thumb slides under whichever button in a segmented row is on.
        const placeThumb = (track) => {
            const thumb = track && track.querySelector('.pd-thumb');
            const on = track && track.querySelector('[aria-selected="true"], [aria-pressed="true"]');
            if (!thumb || !on || !on.offsetWidth) return;
            const first = !track.classList.contains('is-live');
            if (first) thumb.style.transition = 'none';
            thumb.style.width = on.offsetWidth + 'px';
            thumb.style.height = on.offsetHeight + 'px';
            thumb.style.transform = 'translate(' + on.offsetLeft + 'px, ' + on.offsetTop + 'px)';
            if (first) {
                track.classList.add('is-live');
                void thumb.offsetWidth;
                thumb.style.transition = '';
            }
        };

        const build = (root) => {
            const deck = root.querySelector('.pd-deck');
            if (!deck) return null;
            const cards = Array.from(deck.querySelectorAll('.pd-card'));
            const n = cards.length;
            const parts = cards.map((c) => ({
                face: c.querySelector('.pd-face'), inner: c.querySelector('.pd-inner'), fog: c.querySelector('.pd-fog'),
            }));
            const tabs = Array.from(root.querySelectorAll('.pd-tab'));
            const tabRow = root.querySelector('.pd-tabs');
            const bill = root.querySelector('.pd-bill');
            const order = cards.map((c, i) => i).sort((a, b) => cards[a].dataset.slot - cards[b].dataset.slot);
            let busy = false, queued = null, drag = null;

            const z = (s) => 10 + (n - s) * 2;
            const num = (name) => parseFloat(getComputedStyle(deck).getPropertyValue(name)) || 0;
            // A place in the pile as a transform, plus an optional push away
            // from it (x, y, a turn of r degrees about `pivot` px down the card,
            // and a scale k on top). Same shape every time, so it interpolates.
            const pose = (s, x = 0, y = 0, r = 0, k = 1, pivot = 0) =>
                'translate3d(' + x + 'px, ' + (y - s * num('--pd-step')) + 'px, 0) translateY(' + pivot + 'px) rotate(' + r
                + 'deg) translateY(' + (-pivot) + 'px) scale(' + ((1 - s * num('--pd-shrink')) * k) + ')';

            const paint = () => {
                order.forEach((ci, s) => {
                    const c = cards[ci], f = parts[ci].face, front = s === 0;
                    c.style.setProperty('--s', s);
                    c.style.zIndex = z(s);
                    c.dataset.slot = s;
                    c.classList.toggle('is-front', front);
                    c.classList.toggle('is-back', !front);
                    if (front) {
                        c.removeAttribute('role'); c.removeAttribute('tabindex'); c.removeAttribute('aria-label');
                        f.removeAttribute('inert'); f.removeAttribute('aria-hidden'); f.tabIndex = 0;
                    } else {
                        c.setAttribute('role', 'button'); c.tabIndex = 0;
                        c.setAttribute('aria-label', 'Show the ' + c.dataset.name + ' plan');
                        f.setAttribute('inert', ''); f.setAttribute('aria-hidden', 'true'); f.removeAttribute('tabindex');
                    }
                });
            };

            // The deck is as tall as the card in front, read off its words.
            const measure = () => {
                const p = parts[order[0]];
                const h = p.inner.offsetHeight + (p.face.offsetHeight - p.face.clientHeight);
                if (h > 0 && deck.style.getPropertyValue('--pd-h') !== h + 'px') deck.style.setProperty('--pd-h', h + 'px');
            };

            const mark = (ci) => {
                tabs.forEach((t, i) => {
                    const on = i === ci;
                    t.setAttribute('aria-selected', on ? 'true' : 'false');
                    t.tabIndex = on ? 0 : -1;
                });
                placeThumb(tabRow);
            };

            const go = (ci, dir, dragX, focus) => {
                if (ci == null || ci < 0 || ci >= n) return;
                mark(ci);
                if (busy) { queued = { ci, focus }; return; }
                const k = order.indexOf(ci);
                if (k <= 0) { if (focus) parts[ci].face.focus({ preventScroll: true }); return; }
                const old = order[0];
                const A = cards[ci], B = cards[old];
                const fromA = getComputedStyle(A).transform, fromB = getComputedStyle(B).transform;
                const fogA = getComputedStyle(parts[ci].fog).opacity;
                if (!dir) dir = ci > old ? 1 : -1;
                order.splice(k, 1);
                order.unshift(ci);
                const moving = !still() && typeof A.animate === 'function';
                if (moving) { A.classList.add('is-flying'); B.classList.add('is-flying'); }
                B.classList.remove('is-dragging');
                B.style.transform = '';
                paint();
                measure();
                if (focus) parts[ci].face.focus({ preventScroll: true });
                if (!moving) return;

                busy = true;
                const w = A.offsetWidth, h = A.offsetHeight, step = num('--pd-step');
                const T = 640, F = .42;   // the whole deal, and the moment the two swap layers
                const fogB = getComputedStyle(parts[old].fog).opacity;   // its resting fade, one place back
                const awayA = dir * w * (w < 400 ? .42 : .54);
                const awayB = -dir * Math.max(w * .14, Math.abs(dragX || 0) + 16);
                const o = { duration: T };
                const runs = [
                    A.animate([
                        { transform: fromA, easing: EASE },
                        { transform: pose(0, awayA, -(k * step) - 28, dir * 6, 1.02, h / 2), offset: F, easing: EASE },
                        { transform: pose(0, 0, 0, 0, 1, h / 2) },
                    ], o),
                    A.animate([{ zIndex: z(k) - 1 }, { zIndex: z(k) - 1, offset: F }, { zIndex: z(0) + 3, offset: F }, { zIndex: z(0) + 3 }], o),
                    B.animate([
                        { transform: fromB, easing: EASE },
                        { transform: pose(0, awayB, 14, -dir * 4, .95, h / 2), offset: F, easing: EASE },
                        { transform: pose(1, 0, 0, 0, 1, h / 2) },
                    ], o),
                    B.animate([{ zIndex: z(0) + 2 }, { zIndex: z(0) + 2, offset: F }, { zIndex: z(1), offset: F }, { zIndex: z(1) }], o),
                    parts[ci].inner.animate([{ opacity: 0 }, { opacity: 0, offset: F }, { opacity: 1, offset: .82 }, { opacity: 1 }], o),
                    parts[old].inner.animate([{ opacity: 1 }, { opacity: 0, offset: F * .8 }, { opacity: 0 }], o),
                    parts[ci].fog.animate([{ opacity: fogA }, { opacity: .16, offset: F }, { opacity: 0 }], o),
                    parts[old].fog.animate([{ opacity: 0 }, { opacity: .3, offset: F }, { opacity: fogB }], o),
                ];
                Promise.all(runs.map((a) => a.finished)).catch(() => {}).then(() => {
                    A.classList.remove('is-flying');
                    B.classList.remove('is-flying');
                    busy = false;
                    if (queued) { const q = queued; queued = null; go(q.ci, 0, 0, q.focus); }
                });
            };

            // Tap a card in the pile, or Enter / Space on it.
            cards.forEach((c, i) => {
                c.addEventListener('click', () => { if (c.classList.contains('is-back')) go(i); });
                c.addEventListener('keydown', (e) => {
                    if (e.target !== c || !c.classList.contains('is-back')) return;
                    if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); go(i, 0, 0, true); }
                });
            });

            // The row of names: a tablist, arrows move along it.
            tabs.forEach((t, i) => {
                t.addEventListener('click', () => go(i));
                t.addEventListener('keydown', (e) => {
                    let j = null;
                    if (e.key === 'ArrowRight') j = (i + 1) % n;
                    else if (e.key === 'ArrowLeft') j = (i - 1 + n) % n;
                    else if (e.key === 'Home') j = 0;
                    else if (e.key === 'End') j = n - 1;
                    if (j === null) return;
                    e.preventDefault();
                    tabs[j].focus();
                    go(j);
                });
            });

            // A swipe on the front card, on a touch screen: left for the next
            // plan, right for the one before. A short drag springs back.
            deck.addEventListener('pointerdown', (e) => {
                if (busy || (e.pointerType !== 'touch' && e.pointerType !== 'pen')) return;
                const c = e.target.closest('.pd-card');
                if (!c || !c.classList.contains('is-front')) return;
                drag = { id: e.pointerId, c, x: e.clientX, y: e.clientY, dx: 0, on: false, t: performance.now() };
            });
            deck.addEventListener('pointermove', (e) => {
                if (!drag || e.pointerId !== drag.id) return;
                const dx = e.clientX - drag.x, dy = e.clientY - drag.y;
                if (!drag.on) {
                    if (Math.abs(dx) < 10 && Math.abs(dy) < 10) return;
                    if (busy || Math.abs(dy) >= Math.abs(dx)) { drag = null; return; }
                    drag.on = true;
                    drag.c.classList.add('is-dragging');
                    try { drag.c.setPointerCapture(e.pointerId); } catch (_) {}
                }
                drag.dx = dx;
                const h = drag.c.offsetHeight;
                drag.c.style.transform = 'translate3d(' + dx + 'px, ' + Math.abs(dx) * .05 + 'px, 0) translateY(' + h
                    + 'px) rotate(' + dx * .025 + 'deg) translateY(' + (-h) + 'px) scale(1)';
            });
            const letGo = (e) => {
                if (!drag || e.pointerId !== drag.id) return;
                const d = drag;
                drag = null;
                if (!d.on) return;
                const far = Math.abs(d.dx) > Math.min(90, d.c.offsetWidth * .24);
                const flick = Math.abs(d.dx) > 28 && Math.abs(d.dx) / Math.max(1, performance.now() - d.t) > .45;
                if (e.type === 'pointerup' && (far || flick)) {
                    const cur = order[0];
                    go(d.dx < 0 ? (cur + 1) % n : (cur - 1 + n) % n, d.dx < 0 ? 1 : -1, d.dx);
                } else {
                    d.c.classList.remove('is-dragging');   // its own transition carries it home
                    d.c.style.transform = '';
                }
            };
            deck.addEventListener('pointerup', letGo);
            deck.addEventListener('pointercancel', letGo);

            const refresh = () => { measure(); placeThumb(tabRow); placeThumb(bill); };
            deck.classList.add('pd-instant');
            paint();
            mark(order[0]);
            refresh();
            requestAnimationFrame(() => requestAnimationFrame(() => deck.classList.remove('pd-instant')));
            if ('ResizeObserver' in window) {
                const ro = new ResizeObserver(refresh);
                parts.forEach((p) => ro.observe(p.inner));
                [tabRow, bill].forEach((el) => el && ro.observe(el));
            } else {
                window.addEventListener('resize', refresh);
            }
            if (document.fonts && document.fonts.ready) document.fonts.ready.then(refresh);
            return { bill: () => placeThumb(bill) };
        };

        window.pdDeck = () => {
            let api = null;
            return {
                yearly: false,
                init() {
                    this.$nextTick(() => { api = build(this.$el); });
                    this.$watch('yearly', () => this.$nextTick(() => { if (api) api.bill(); }));
                },
            };
        };
    })();
</script>
@endpush
