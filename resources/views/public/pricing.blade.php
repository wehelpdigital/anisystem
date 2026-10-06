@extends('layouts.public')

@include('public.partials.site-css')

@section('title', 'Pricing: Plans for Every Farm')
@section('meta_description', 'anee.io pricing: simple plans paid via ' . \App\Support\Region::payMethod() . ', plus AI credits so you only pay Anee for what you ask. No hidden fees, cancel anytime.')

@section('content')

    {{-- ================= HERO ================= --}}
    <section class="relative isolate overflow-hidden bg-gradient-to-br from-brand-700 via-brand-800 to-brand-900 spark-field">
        <div class="absolute inset-0 bg-dot-grid opacity-50" aria-hidden="true"></div>
        <div class="relative max-w-3xl mx-auto px-4 sm:px-6 py-16 sm:py-20 text-center animate-fade-up" style="z-index:1">
            <p class="text-sm font-bold uppercase tracking-wider text-accent-400">Simple prices made for farmers</p>
            <h1 class="mt-2 font-heading text-4xl sm:text-5xl font-bold text-white text-balance">Start Free. Grow When You're Ready.</h1>
            <p class="mt-5 text-brand-100 text-base sm:text-lg">
                The Libre plan is free forever. No card, and no trial that runs out. Upgrades are paid in {{ \App\Support\Region::currencyName() }}
                through {{ \App\Support\Region::payMethod() }}. Anee, the AI technician, uses credits on top of your plan, so you only pay her
                for what you ask.
            </p>
        </div>
    </section>

    {{-- ================= THE PLANS, AS A HAND OF CARDS =================
         One plan is read at a time. Libre stands in front when the page
         opens, and the other plans fan out behind it from left to right in
         plan order, like playing cards held in a hand: each turned a little,
         the one on the right always over the one on its left, so every card
         shows its corner (the name and the price). Tapping one (or its name
         in the row above, or swiping the front card on a phone) lifts it out
         of the hand and deals it to the front, and the card that was there
         slides back into its place in the fan. Every card is as tall as the
         one in front, so the deck glides when that changes. The motion lives
         in the script at the foot of this file, the dress in the pd- styles. --}}
    @php
        $pdKeys  = array_keys($tiers);
        $pdFree  = collect($tiers)->search(fn ($t) => empty($t['price']));
        $pdOrder = array_values(array_unique(array_merge($pdFree !== false ? [$pdFree] : [], $pdKeys)));
        $pdTint  = ['libre' => '#86b556', 'libreAnee' => '#f5c518', 'solo' => '#4a7c2a', 'owner' => '#2d5016'];
        $pdSpare = ['#86b556', '#f5c518', '#4a7c2a', '#2d5016', '#6b9f3d', '#c79e00'];
        // Front first, then the hand from left to right in plan order.
        $pdSlotOf = [$pdOrder[0] => 0];
        foreach (array_values(array_diff($pdKeys, [$pdOrder[0]])) as $i => $k) {
            $pdSlotOf[$k] = $i + 1;
        }
    @endphp
    <section class="py-16 sm:py-20 bg-gray-50 bg-drift pd-section" x-data="pdDeck()">
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
                <div class="pd-deck">
                    @foreach ($tiers as $key => $tier)
                        @php
                            $isStar = $key === 'owner';
                            $isFree = empty($tier['price']);
                            $pdSlot = $pdSlotOf[$key] ?? 0;
                            if (! $isFree) { $prM = \App\Support\Region::tierPrice($key, 'month'); $prY = \App\Support\Region::tierPrice($key, 'year'); }
                        @endphp
                        <div class="pd-card {{ $pdSlot === 0 ? 'is-front' : 'is-back' }} {{ $isStar ? 'is-star' : '' }}"
                             data-name="{{ $tier['name'] }}" data-slot="{{ $pdSlot }}"
                             style="--s: {{ $pdSlot }}; z-index: {{ 10 + (count($pdOrder) - $pdSlot) * 2 }}; --pd-tint: {{ $pdTint[$key] ?? $pdSpare[$loop->index % count($pdSpare)] }}"
                             @if ($pdSlot) role="button" tabindex="0" aria-label="Show the {{ $tier['name'] }} plan" @endif>
                            @if ($isStar)<span class="pr-flag">Most complete</span>@endif
                            <div class="pd-face" id="pd-panel-{{ $key }}" role="tabpanel" aria-labelledby="pd-tab-{{ $key }}"
                                 @if ($pdSlot) inert aria-hidden="true" @else tabindex="0" @endif>
                                <span class="pd-band" aria-hidden="true"></span>
                                {{-- The corner a card shows while it waits in the hand. --}}
                                <div class="pd-peek" aria-hidden="true">
                                    <b class="pd-peek-n">{{ $tier['name'] }}</b>
                                </div>
                                <div class="pd-inner">
                                    <span class="pr-name">{{ $tier['name'] }}</span>
                                    <span class="pr-for">{{ $tier['tagline'] }}</span>

                                    @if ($isFree)
                                        <span class="pr-price">
                                            <span class="pr-amount is-free">Free</span>
                                            <span class="pr-per">forever</span>
                                        </span>
                                        <span class="pr-year">No card. No trial that runs out. Yours to keep.</span>
                                        <span class="pr-day">{{ \App\Support\Region::money(0) }} a day, for as long as you like</span>
                                    @else
                                        {{-- Pesos at home, dollars on the international face (App\Support\Region). --}}
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

            <div class="pd-cta reveal">
                <a href="{{ route('signup') }}" class="btn btn-accent btn-lg pd-go">
                    <svg fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M11 20A7 7 0 0 1 9.8 6.1C15.5 5 17 4.48 19 2c1 2 2 4.18 2 8 0 5.5-4.78 10-10 10Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2 21c0-3 1.85-5.36 5.08-6C9.5 14.52 12 13 13 12"/></svg>
                    Start for free forever on {{ $pdFree !== false ? $tiers[$pdFree]['name'] : 'Libre' }}
                </a>
                <p class="pd-note">No payment. No trial that runs out.</p>
                <a href="{{ route('pricing.compare') }}" class="pd-more">Compare every plan side by side
                    <svg fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14M13 6l6 6-6 6"/></svg></a>
            </div>
        </div>
    </section>



    {{-- ================= FAQ ================= --}}
    <section class="py-16 sm:py-20 bg-white">
        <div class="max-w-3xl mx-auto px-4 sm:px-6">
            <h2 class="font-heading text-3xl font-bold text-ink text-center reveal">Common questions</h2>
            <div class="mt-8 space-y-3">
                @foreach ([
                    ['Do my workers need their own subscriptions?', 'No. Workers log in under the owner\'s plan. You invite them, choose what each one may see or edit, and they use anee.io for free.'],
                    ['What happens when my plan ends?', 'Your data stays safe and you can still read it. Renew any time and pick up where the season left off. If you renew early, the new days are added on top, so nothing is wasted.'],
                    ['Do credits expire?', 'No. Credits sit on your account until you spend them, across seasons.'],
                    ['Can I use it on a computer too?', 'Yes. anee.io is a web app. It is made first for phones in the field, and the same account works in any browser.'],
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

    /* ---- the hand ----
       Every card sits in the same grid cell, centred. --s is a card's place
       (0 is the front; 1, 2, 3 the hand from left to right). The script
       turns a place into --x, --y, --r and --k from the numbers here, which
       change with the screen: --pd-gap rem between cards in the hand,
       --pd-tilt degrees of turn per card, --pd-rise rem the hand stands
       above the front card, --pd-drop rem the outer cards sit lower, --pd-k
       their size. It keeps every card as tall as the front one (--pd-h). */
    .pd-section { overflow: hidden; }
    .pd-stage { margin-top: 1.8rem; }
    .pd-deck { --pd-gap: 3.8; --pd-tilt: 5; --pd-rise: 4.6; --pd-drop: .3; --pd-k: .8; --pd-cw: min(21rem, calc(100% - 2.4rem));
        position: relative; display: grid; justify-items: center; max-width: 54rem; margin: 0 auto;
        padding-top: calc(var(--pd-rise) * 1rem + 1.5rem); touch-action: pan-y; }
    @media (min-width: 640px) { .pd-deck { --pd-gap: 8.5; --pd-tilt: 7; --pd-rise: 4.6; --pd-drop: .9; --pd-k: .9; --pd-cw: 23rem; } }
    .pd-card { grid-area: 1 / 1; align-self: start; width: var(--pd-cw); position: relative; height: var(--pd-h, auto);
        border-radius: 1.35rem; transform-origin: 50% 0; outline: none; -webkit-tap-highlight-color: transparent;
        transform: translate3d(var(--x, 0px), calc(var(--y, 0px) - var(--pd-lift, 0px)), 0) rotate(var(--r, 0deg)) scale(var(--k, 1));
        box-shadow: -6px 14px 30px -22px rgb(20 33 12 / .55);
        transition: transform .6s var(--pd-ease), height .5s var(--pd-ease), box-shadow .45s var(--pd-ease); }
    .pd-card.is-front { box-shadow: 0 34px 70px -38px rgb(20 33 12 / .55), 0 3px 10px -6px rgb(20 33 12 / .16); }
    .pd-card.is-back { cursor: pointer; user-select: none; }
    .pd-face { position: relative; height: 100%; overflow: hidden; border-radius: inherit; outline: none;
        background: #fff; border: 1px solid #e1e8d7; transition: border-color .45s var(--pd-ease); }
    .pd-card.is-star.is-front .pd-face { border-color: #4a7c2a; }
    .pd-band { position: absolute; left: 0; right: 0; top: 0; z-index: 4; height: 6px; background: var(--pd-tint); }
    .pd-inner { display: flex; flex-direction: column; padding: 1.6rem 1.4rem 1.6rem;
        transition: opacity .4s var(--pd-ease), filter .4s var(--pd-ease); }
    /* A card in the hand shows what it holds, out of focus: its list is
       there to be guessed at, and its name sits sharp in the corner. Its
       price waits until it comes to the front. */
    .pd-card.is-back .pd-inner { opacity: .72; filter: blur(2.6px); }
    @media (max-width: 639.98px) { .pd-card.is-back .pd-inner { filter: blur(2px); } }
    .pd-inner .pr-name, .pd-inner .pr-price, .pd-inner .pr-year, .pd-inner .pr-day { transition: opacity .3s var(--pd-ease); }
    .pd-card.is-back .pd-inner .pr-name, .pd-card.is-back .pd-inner .pr-price,
    .pd-card.is-back .pd-inner .pr-year, .pd-card.is-back .pd-inner .pr-day { opacity: 0; }
    /* A card being dealt shows its name and price only as its corner fades, not over it. */
    .pd-card.is-flying.is-front .pd-inner .pr-name, .pd-card.is-flying.is-front .pd-inner .pr-price,
    .pd-card.is-flying.is-front .pd-inner .pr-year, .pd-card.is-flying.is-front .pd-inner .pr-day { transition-delay: .3s; }
    /* A light wash over a card in the hand, so the front one stands out. */
    .pd-fog { position: absolute; inset: 0; z-index: 2; pointer-events: none; opacity: 0; border-radius: inherit;
        background: linear-gradient(180deg, rgb(255 255 255 / .1) 0%, rgb(243 247 238 / .55) 100%);
        transition: opacity .45s var(--pd-ease); }
    .pd-card.is-back .pd-fog { opacity: 1; }
    @media (hover: hover) {
        .pd-card.is-back:not(.is-flying):hover { --pd-lift: 14px; }
    }
    /* The corner: the card's own name, sharp, where the blurred one would
       be (the way a playing card shows its rank). Narrow on a phone, where
       only a sliver of each card shows, so long names take two lines. */
    .pd-peek { position: absolute; left: 0; top: 6px; z-index: 3; padding: calc(1.6rem - 6px) 1rem 0 1.4rem;
        opacity: 0; pointer-events: none; transition: opacity .35s var(--pd-ease); }
    .pd-card.is-back .pd-peek { opacity: 1; }
    .pd-peek-n { display: block; max-width: calc(var(--pd-gap) * 1rem - 1.8rem); font-family: var(--font-heading); font-size: 1.06rem; font-weight: 800;
        line-height: 1.15; color: #14210c; text-shadow: 0 0 6px #fff, 0 0 2px #fff; }
    @media (max-width: 639.98px) {
        .pd-peek { padding: .6rem .35rem 0 .6rem; }
        .pd-peek-n { max-width: calc(var(--pd-gap) * 1rem - .9rem); font-size: .78rem; line-height: 1.12; }
    }
    .pd-card.is-back:not(.is-flying):focus-visible { --pd-lift: 10px; outline: 3px solid #86b556; outline-offset: 2px; }
    .pd-face:focus-visible { outline: 3px solid #86b556; outline-offset: 3px; }
    /* A card being dealt or dragged is moved by the script, not by these. */
    .pd-card.is-flying, .pd-card.is-dragging { transition: height .5s var(--pd-ease), box-shadow .45s var(--pd-ease); }
    .pd-card.is-flying .pd-inner, .pd-card.is-flying .pd-fog, .pd-card.is-flying .pd-peek { transition: none; }
    .pd-deck.pd-instant .pd-card, .pd-deck.pd-instant .pd-inner,
    .pd-deck.pd-instant .pd-fog, .pd-deck.pd-instant .pr-flag, .pd-deck.pd-instant .pd-peek { transition: none; }

    /* The flag rides above the card's coloured band and border (the band is
       z-index 4 inside the face), a little below the top edge, dark green, with a ring of
       white so the edge does not run through it. */
    .pd-card .pr-flag { z-index: 6; top: -.6rem; transform: translate(-50%, 0); line-height: 1.2; background: #1f3a0e;
        box-shadow: 0 0 0 3px #fff, 0 8px 18px -8px rgb(20 33 12 / .8);
        transition: opacity .35s var(--pd-ease) .2s, transform .45s var(--pd-ease) .2s; }
    .pd-card.is-back .pr-flag { opacity: 0; transform: translate(-50%, .5rem); transition-delay: 0s; }

    /* The card's own words, a little larger now that one is read at a time. */
    .pd-inner .pr-name { font-size: 1.3rem; }
    .pd-inner .pr-amount { font-size: 2.6rem; line-height: 1.1; }
    .pd-inner .pr-list { margin-top: 1.2rem; padding-top: 1.15rem; border-top: 1px dashed #dbe5cf; }
    /* Monthly and yearly swap by x-show; whichever appears rises in. */
    .pd-card.is-front .pr-price, .pd-card.is-front .pr-year, .pd-card.is-front .pr-day { animation: pdSwap .45s var(--pd-ease) both; }
    @keyframes pdSwap { from { opacity: 0; transform: translateY(6px); } to { opacity: 1; transform: none; } }

    /* The way in, under the deck: one button, and the promise under it. */
    .pd-cta { margin-top: 2.6rem; display: flex; flex-direction: column; align-items: center; gap: .7rem; text-align: center; }
    .pd-go { gap: .55rem; box-shadow: 0 18px 36px -18px rgb(199 158 0 / .85); transition: transform .28s var(--pd-ease), box-shadow .28s var(--pd-ease); }
    .pd-go:hover { transform: translateY(-2px); box-shadow: 0 22px 40px -18px rgb(199 158 0 / .95); }
    .pd-go svg { flex: none; width: 1.2rem; height: 1.2rem; }
    .pd-note { font-size: .92rem; font-weight: 700; color: #4a7c2a; }
    .pd-more { display: inline-flex; align-items: center; gap: .4rem; margin-top: .3rem; font-size: .9rem; font-weight: 800; color: #2f5219;
        text-decoration: underline; text-decoration-color: #b9d39b; text-underline-offset: 4px; transition: color .28s var(--pd-ease); }
    .pd-more:hover { color: #4a7c2a; }
    .pd-more svg { width: 1rem; height: 1rem; transition: transform .28s var(--pd-ease); }
    .pd-more:hover svg { transform: translateX(3px); }
    @media (max-width: 479.98px) { .pd-go { width: 100%; justify-content: center; } }

    @media (prefers-reduced-motion: reduce) {
        .pd-seg > button, .pd-thumb, .pd-card, .pd-face, .pd-inner, .pd-fog, .pd-peek, .pd-card .pr-flag, .pd-go { transition: none !important; }
        .pd-card .pr-price, .pd-card .pr-year, .pd-card .pr-day { animation: none; }
    }
    html.sm-still .pd-seg > button, html.sm-still .pd-thumb, html.sm-still .pd-card, html.sm-still .pd-face,
    html.sm-still .pd-inner, html.sm-still .pd-fog, html.sm-still .pd-card .pr-flag { transition: none !important; }
    html.sm-still .pd-card .pr-price, html.sm-still .pd-card .pr-year, html.sm-still .pd-card .pr-day { animation: none; }
</style>
@endpush

@push('scripts')
<script>
    /* The plan hand. `order` lists the front card, then the hand from left
       to right, always in plan order. place(s) turns a slot into where the
       card stands, from the --pd-* numbers on the deck. A card picked
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
                face: c.querySelector('.pd-face'), inner: c.querySelector('.pd-inner'), fog: c.querySelector('.pd-fog'), peek: c.querySelector('.pd-peek'),
            }));
            const tabs = Array.from(root.querySelectorAll('.pd-tab'));
            const tabRow = root.querySelector('.pd-tabs');
            const bill = root.querySelector('.pd-bill');
            const order = cards.map((c, i) => i).sort((a, b) => cards[a].dataset.slot - cards[b].dataset.slot);
            let busy = false, queued = null, drag = null;

            // Right over left in the hand, and the front card over all of it.
            const z = (s) => (s ? 10 + s : 12 + n * 2);
            const num = (name) => parseFloat(getComputedStyle(deck).getPropertyValue(name)) || 0;
            const rem = () => parseFloat(getComputedStyle(document.documentElement).fontSize) || 16;
            let G = null;
            const geo = () => {
                const r = rem();
                G = { gap: num('--pd-gap') * r, tilt: num('--pd-tilt'), rise: num('--pd-rise') * r, drop: num('--pd-drop') * r, k: num('--pd-k') || .9 };
            };
            // A slot as a place: the front stands upright; the hand fans out
            // from the middle, each card a gap over, a tilt more turned, the
            // outer ones a little lower.
            const place = (s) => {
                if (!G) geo();
                if (!s) return { x: 0, y: 0, r: 0, k: 1 };
                const t = (s - 1) - (n - 2) / 2;
                return { x: t * G.gap, y: -G.rise + Math.abs(t) * G.drop, r: t * G.tilt, k: G.k };
            };
            // A place as a transform, plus an optional push away from it. Same
            // shape as the CSS one (translate, rotate, scale), so they meet.
            const pose = (s, dx = 0, dy = 0, dr = 0, dk = 1) => {
                const p = place(s);
                return 'translate3d(' + (p.x + dx) + 'px, ' + (p.y + dy) + 'px, 0) rotate(' + (p.r + dr) + 'deg) scale(' + (p.k * dk) + ')';
            };

            const paint = () => {
                order.forEach((ci, s) => {
                    const c = cards[ci], f = parts[ci].face, front = s === 0;
                    const p = place(s);
                    c.style.setProperty('--s', s);
                    c.style.setProperty('--x', p.x + 'px');
                    c.style.setProperty('--y', p.y + 'px');
                    c.style.setProperty('--r', p.r + 'deg');
                    c.style.setProperty('--k', p.k);
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
                // How out of focus a waiting card is, read off the picked one before it moves (a phone blurs less).
                const blurA = (getComputedStyle(parts[ci].inner).filter || '').startsWith('blur') ? getComputedStyle(parts[ci].inner).filter : 'blur(2.6px)';
                if (!dir) dir = ci > old ? 1 : -1;
                // The picked card to the front; the rest back into the hand in plan order.
                const rest = order.filter((x) => x !== ci).sort((a, b) => a - b);
                order.splice(0, n, ci, ...rest);
                const sB = order.indexOf(old);
                const moving = !still() && typeof A.animate === 'function';
                if (moving) { A.classList.add('is-flying'); B.classList.add('is-flying'); }
                B.classList.remove('is-dragging');
                B.style.transform = '';
                paint();
                measure();
                if (focus) parts[ci].face.focus({ preventScroll: true });
                if (!moving) return;

                busy = true;
                const T = 720, F = .44;   // the whole deal, and the moment the two swap layers
                const lift = Math.max(48, G.rise * 1.15);
                const turnA = place(k).r;
                const o = { duration: T };
                const runs = [
                    // Out of the hand, straight up and straightening, then down to the front.
                    A.animate([
                        { transform: fromA, easing: EASE },
                        { transform: pose(k, 0, -lift, -turnA * .6, 1.03), offset: F, easing: EASE },
                        { transform: pose(0) },
                    ], o),
                    A.animate([{ zIndex: z(k) }, { zIndex: z(k), offset: F }, { zIndex: z(0) + 3, offset: F }, { zIndex: z(0) + 3 }], o),
                    // The old front card sinks a little, goes under, and slides into its place in the hand.
                    B.animate([
                        { transform: fromB, easing: EASE },
                        { transform: pose(0, dir * -24, 26, dir * -3, .96), offset: F, easing: EASE },
                        { transform: pose(sB) },
                    ], o),
                    B.animate([{ zIndex: z(0) + 2 }, { zIndex: z(0) + 2, offset: F }, { zIndex: z(sB), offset: F }, { zIndex: z(sB) }], o),
                    parts[ci].inner.animate([
                        { opacity: .72, filter: blurA }, { opacity: .72, filter: blurA, offset: F * .5 },
                        { opacity: 1, filter: 'blur(0px)', offset: .85 }, { opacity: 1, filter: 'blur(0px)' }], o),
                    parts[old].inner.animate([
                        { opacity: 1, filter: 'blur(0px)' }, { opacity: .72, filter: blurA, offset: F }, { opacity: .72, filter: blurA }], o),
                    parts[ci].fog.animate([{ opacity: fogA }, { opacity: fogA, offset: F * .5 }, { opacity: 0, offset: F }, { opacity: 0 }], o),
                    parts[old].fog.animate([{ opacity: 0 }, { opacity: 0, offset: F * .8 }, { opacity: 1, offset: F }, { opacity: 1 }], o),
                    parts[ci].peek.animate([{ opacity: 1 }, { opacity: 1, offset: F * .5 }, { opacity: 0, offset: F }, { opacity: 0 }], o),
                    parts[old].peek.animate([{ opacity: 0 }, { opacity: 0, offset: F }, { opacity: 1, offset: .8 }, { opacity: 1 }], o),
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

            const refresh = () => {
                const before = G && JSON.stringify(G);
                geo();
                if (JSON.stringify(G) !== before) paint();
                measure(); placeThumb(tabRow); placeThumb(bill);
            };
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
