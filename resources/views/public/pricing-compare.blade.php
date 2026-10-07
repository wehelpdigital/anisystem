@extends('layouts.public')

{{-- Pricing comparison (2026-10-01): every plan side by side, a row per
     limit or tool, read off config/tiers.php by App\Support\PlanCompare
     (the numbers the app's gates read). A desk shows all the plans; a phone
     picks two to set against each other. The header row stays on screen
     while the rows scroll under it. --}}
@include('public.partials.site-css')

@section('title', 'Pricing Comparison: Every anee.io Plan Side by Side')
@section('meta_description', 'Compare anee.io plans side by side: seasons, lots, maps, weather, Anee the smart farm technician, workers, inventory, reports and storage. Libre is free forever.')

@push('head')
    <link rel="canonical" href="{{ route('pricing.compare') }}">
@endpush

@php
    $pcPlans = \App\Support\PlanCompare::plans();
    $pcGroups = \App\Support\PlanCompare::groups();
    $pcKeys = array_keys($pcPlans);
    $pcTint = ['libre' => '#86b556', 'libreAnee' => '#f5c518', 'solo' => '#4a7c2a', 'owner' => '#2d5016'];
    $pcFree = collect($pcPlans)->search(fn ($t) => empty($t['price']));
    $pcB = array_search('solo', $pcKeys, true);
    // The most a year saves against twelve months, rounded down so it never
    // promises more than the dearest yearly price gives.
    $pcSave = (int) collect($pcKeys)->map(function ($k) {
        $m = \App\Support\Region::tierPrice($k, 'month');
        $y = \App\Support\Region::tierPrice($k, 'year');

        return ($m && $y) ? floor((1 - $y / ($m * 12)) * 100 + 1e-9) : 0;
    })->max();
    $pcTick = '<svg fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>';
    $pcCross = '<svg fill="none" stroke="currentColor" stroke-width="2.6" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" d="M7 7l10 10M17 7L7 17"/></svg>';
@endphp

@section('content')

    {{-- ================= HERO ================= --}}
    <section class="relative isolate overflow-hidden bg-gradient-to-br from-brand-700 via-brand-800 to-brand-900 spark-field">
        <div class="absolute inset-0 bg-dot-grid opacity-50" aria-hidden="true"></div>
        <div class="relative max-w-3xl mx-auto px-4 sm:px-6 py-14 sm:py-16 text-center animate-fade-up" style="z-index:1">
            <p class="text-sm font-bold uppercase tracking-wider text-accent-400">Compare plans</p>
            <h1 class="mt-2 font-heading text-4xl sm:text-5xl font-bold text-white text-balance">Every Plan, Side by Side</h1>
            <p class="mt-5 text-brand-100 text-base sm:text-lg">
                Every limit below is the same one the app uses. {{ $pcFree !== false ? $pcPlans[$pcFree]['name'] : 'Libre' }} is free forever.
                The other plans are paid in {{ \App\Support\Region::currencyName() }} through {{ \App\Support\Region::payMethod() }}.
            </p>
            <a href="{{ route('pricing') }}" class="pc-back">
                <svg fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M19 12H5m6-6l-6 6 6 6"/></svg>
                See the plans as cards
            </a>
        </div>
    </section>

    {{-- ================= THE TABLE ================= --}}
    <section class="py-12 sm:py-16 bg-gray-50 pc" x-data="pcCompare({{ $pcFree !== false ? array_search($pcFree, $pcKeys, true) : 0 }}, {{ $pcB !== false ? $pcB : 1 }})">
        <div class="max-w-5xl mx-auto px-4 sm:px-6">
            <div class="pc-bar reveal">
                <div class="pc-seg" role="group" aria-label="Billing period" :class="yearly && 'is-year'">
                    <span class="pc-thumb" aria-hidden="true"></span>
                    <button type="button" :aria-pressed="(!yearly).toString()" aria-pressed="true" @click="yearly = false">Monthly</button>
                    <button type="button" :aria-pressed="yearly.toString()" aria-pressed="false" @click="yearly = true">Yearly @if ($pcSave > 0)<span class="pc-soft">save up to {{ $pcSave }}%</span>@endif</button>
                </div>
                {{-- A phone sets two plans against each other. --}}
                <div class="pc-pick">
                    <label for="pcA">Compare</label>
                    <select id="pcA" x-model.number="a">
                        @foreach ($pcPlans as $key => $t)<option value="{{ $loop->index }}">{{ $t['name'] }}</option>@endforeach
                    </select>
                    <span>with</span>
                    <select id="pcB" x-model.number="b" aria-label="with">
                        @foreach ($pcPlans as $key => $t)<option value="{{ $loop->index }}">{{ $t['name'] }}</option>@endforeach
                    </select>
                </div>
                {{-- What the two marks mean, said once above the table. --}}
                <p class="pc-key">
                    <span><i class="pc-yes">{!! $pcTick !!}</i>Included</span>
                    <span><i class="pc-no">{!! $pcCross !!}</i>Not in this plan</span>
                </p>
            </div>

            {{-- No scroll reveal here: the table is taller than a phone's screen,
                 so the reveal's threshold left a blank space under the pickers
                 until the reader had scrolled a third of a screen. --}}
            <div class="pc-wrap">
                <table class="pc-table" :data-hl="hl" @mouseleave="hl = null">
                    <caption class="sr-only">What each anee.io plan includes</caption>
                    <thead>
                        <tr>
                            <th class="pc-corner" scope="col"><span>What you get</span></th>
                            @foreach ($pcPlans as $key => $t)
                                @php $i = $loop->index; $star = $key === 'owner'; @endphp
                                <th scope="col" class="pc-plan {{ $star ? 'is-star' : '' }}" data-c="{{ $i }}" style="--tint: {{ $pcTint[$key] ?? '#86b556' }}"
                                    :class="{ 'is-off': ![a, b].includes({{ $i }}) }" @mouseenter="hl = {{ $i }}">
                                    <span class="pc-band" aria-hidden="true"></span>
                                    @if ($star)<em>Most complete</em>@endif
                                    <b>{{ $t['name'] }}</b>
                                    {{-- A phone: the plan's name in the header that stays on
                                         screen is itself a picker, so the reader can change a
                                         column halfway down the table without going back up. --}}
                                    <select class="pc-swap" aria-label="Change the {{ $t['name'] }} column to another plan"
                                            @change="swap({{ $i }}, Number($event.target.value)); $event.target.value = '{{ $i }}'">
                                        @foreach ($pcPlans as $t2)<option value="{{ $loop->index }}" @selected($loop->index === $i)>{{ $t2['name'] }}</option>@endforeach
                                    </select>
                                    <span class="pc-price">
                                        @if (empty($t['price']))
                                            <strong>Free</strong><small>forever</small>
                                        @else
                                            <strong x-show="!yearly">{{ \App\Support\Region::priceTag(\App\Support\Region::tierPrice($key, 'month')) }}</strong><small x-show="!yearly">a month</small>
                                            <strong x-show="yearly" x-cloak>{{ \App\Support\Region::priceTag(\App\Support\Region::tierPrice($key, 'year')) }}</strong><small x-show="yearly" x-cloak>a year</small>
                                        @endif
                                    </span>
                                    <span class="pc-change" aria-hidden="true">Change<svg fill="none" stroke="currentColor" stroke-width="2.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/></svg></span>
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    @foreach ($pcGroups as $g)
                        @php [$gName, $gIcon, $rows] = $g; $gNote = $g[3] ?? null; @endphp
                        <tbody>
                            <tr class="pc-g">
                                <th colspan="{{ count($pcPlans) + 1 }}" scope="colgroup">
                                    <span class="pc-gi"><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $gIcon }}"/></svg></span>{{ $gName }}
                                </th>
                            </tr>
                            @if ($gNote)
                                <tr class="pc-note">
                                    <td colspan="{{ count($pcPlans) + 1 }}">
                                        <span class="pc-note-in">
                                            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path stroke-linecap="round" d="M12 11v5m0-8.5h.01"/></svg>
                                            <span>{{ $gNote }}</span>
                                        </span>
                                    </td>
                                </tr>
                            @endif
                            @foreach ($rows as [$label, $hint, $read])
                                <tr class="pc-row">
                                    <th scope="row"><b>{{ $label }}</b>@if ($hint)<small>{{ $hint }}</small>@endif</th>
                                    @foreach ($pcPlans as $key => $t)
                                        @php $v = $read($t); $i = $loop->index; @endphp
                                        <td data-c="{{ $i }}" class="{{ $v === true ? 'is-yes' : ($v === false ? 'is-no' : 'is-val') }} {{ $key === 'owner' ? 'is-star' : '' }}"
                                            :class="{ 'is-off': ![a, b].includes({{ $i }}) }" @mouseenter="hl = {{ $i }}">
                                            @if ($v === true)
                                                <span class="pc-yes">{!! $pcTick !!}</span><span class="sr-only">Included</span>
                                            @elseif ($v === false)
                                                <span class="pc-no" aria-hidden="true">{!! $pcCross !!}</span><span class="sr-only">Not included</span>
                                            @else
                                                <span class="pc-val">{{ $v }}</span>
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    @endforeach
                </table>
            </div>

            <div class="pc-cta reveal">
                <a href="{{ route('signup') }}" class="btn btn-accent btn-lg pc-go">
                    <svg fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M11 20A7 7 0 0 1 9.8 6.1C15.5 5 17 4.48 19 2c1 2 2 4.18 2 8 0 5.5-4.78 10-10 10Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2 21c0-3 1.85-5.36 5.08-6C9.5 14.52 12 13 13 12"/></svg>
                    Start for free forever on {{ $pcFree !== false ? $pcPlans[$pcFree]['name'] : 'Libre' }}
                </a>
                <p>No payment. No trial that runs out. Upgrade inside the app when your farm needs more.</p>
            </div>
        </div>
    </section>

@endsection

@push('head')
<style>
    .pc { --pc-ease: cubic-bezier(.22,1,.36,1); }
    .pc-back { display: inline-flex; align-items: center; gap: .45rem; margin-top: 1.4rem; padding: .55rem 1rem; border-radius: 999px;
        font-size: .9rem; font-weight: 800; color: #fff; border: 1.5px solid rgb(255 255 255 / .3); transition: background .28s cubic-bezier(.22,1,.36,1); }
    .pc-back:hover { background: rgb(255 255 255 / .1); }
    .pc-back svg { width: 1rem; height: 1rem; transition: transform .28s cubic-bezier(.22,1,.36,1); }
    .pc-back:hover svg { transform: translateX(-3px); }

    /* The bar over the table: billing, and on a phone the two plans to set side by side. */
    .pc-bar { display: flex; flex-direction: column; align-items: center; gap: .9rem; margin-bottom: 1.4rem; }
    .pc-seg { position: relative; display: inline-grid; grid-template-columns: 1fr 1fr; padding: .25rem; border-radius: 999px; background: #fff; box-shadow: 0 0 0 1px #e1e8d7, 0 10px 24px -20px rgb(20 33 12 / .5); }
    .pc-seg button { position: relative; z-index: 1; display: flex; flex-direction: column; align-items: center; justify-content: center;
        min-height: 2.75rem; padding: .45rem 1.3rem; border-radius: 999px; font-size: .88rem; font-weight: 800; line-height: 1.15; color: #4b5563;
        transition: color .3s var(--pc-ease); }
    .pc-seg button[aria-pressed="true"] { color: #fff; }
    /* What a year saves, under the word, so the two halves stay equal. */
    .pc-soft { display: block; margin-top: .1rem; font-size: .72rem; font-weight: 700; opacity: .85; white-space: nowrap; }
    /* The two marks, explained once. */
    .pc-key { display: flex; flex-wrap: wrap; justify-content: center; gap: .4rem 1.1rem; font-size: .82rem; font-weight: 700; color: #4b5563; }
    .pc-key span { display: inline-flex; align-items: center; gap: .4rem; }
    .pc-key i { width: 1.35rem; height: 1.35rem; }
    .pc-key i svg { width: .8rem; height: .8rem; }
    .pc-thumb { position: absolute; top: .25rem; bottom: .25rem; left: .25rem; width: calc(50% - .25rem); border-radius: 999px; background: #4a7c2a;
        box-shadow: 0 8px 18px -10px rgb(47 82 25 / .8); transition: transform .45s var(--pc-ease); }
    .pc-seg.is-year .pc-thumb { transform: translateX(100%); }
    .pc-pick { display: flex; flex-wrap: wrap; align-items: center; justify-content: center; gap: .45rem; font-size: .88rem; font-weight: 700; color: #4b5563; }
    .pc-pick select { appearance: none; -webkit-appearance: none; padding: .5rem 2rem .5rem .85rem; border-radius: .85rem; border: 1px solid #d6dfcb; background: #fff
        url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' stroke='%234a7c2a' stroke-width='2.6' viewBox='0 0 24 24'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' d='M6 9l6 6 6-6'/%3E%3C/svg%3E") no-repeat right .6rem center / .9rem;
        font-weight: 800; color: #14210c; transition: border-color .28s var(--pc-ease), box-shadow .28s var(--pc-ease); }
    .pc-pick select:focus { outline: none; border-color: #6b9f3d; box-shadow: 0 0 0 4px rgb(107 159 61 / .15); }
    @media (min-width: 768px) { .pc-pick { display: none; } }

    /* The table. Separate borders so the sticky header keeps its own. */
    /* overflow: clip rounds the corners without making a scroll box, so the header row can still stick to the page. */
    .pc-wrap { border-radius: 1.4rem; overflow: clip; background: #fff; box-shadow: 0 0 0 1px #e1e8d7, 0 30px 60px -40px rgb(20 33 12 / .45); }
    .pc-table { width: 100%; border-collapse: separate; border-spacing: 0; table-layout: fixed; }
    .pc-table th, .pc-table td { padding: .8rem .7rem; text-align: center; vertical-align: middle; border-bottom: 1px solid #eef2ea;
        transition: background-color .28s var(--pc-ease); }
    .pc-table thead th { position: sticky; top: calc(4rem + 1px); z-index: 3; background: #fff; border-bottom: 1.5px solid #dfe8d4; vertical-align: bottom; padding-top: 1.1rem; }
    @media (min-width: 768px) { .pc-table thead th { top: calc(5rem + 1px); } }
    .pc-corner { width: 34%; text-align: left !important; }
    .pc-corner span { font-size: .72rem; font-weight: 900; letter-spacing: .12em; text-transform: uppercase; color: #6b8f4a; }
    .pc-plan { position: relative; }
    .pc-band { position: absolute; left: .7rem; right: .7rem; top: 0; height: 4px; border-radius: 0 0 4px 4px; background: var(--tint); }
    .pc-plan em { display: inline-block; margin-bottom: .3rem; padding: .14rem .5rem; border-radius: 999px; background: #4a7c2a; color: #fff; font-style: normal;
        font-size: .64rem; font-weight: 900; letter-spacing: .06em; text-transform: uppercase; }
    .pc-plan b { display: block; font-family: var(--font-heading); font-size: 1.02rem; font-weight: 800; line-height: 1.2; color: #14210c; }
    /* The phone's column pickers live in the header; a desk shows every plan. */
    .pc-change, .pc-swap { display: none; }
    .pc-price { display: block; margin-top: .25rem; }
    .pc-price strong { font-family: var(--font-heading); font-size: 1.2rem; font-weight: 800; color: #2f5219; animation: pcIn .4s var(--pc-ease) both; }
    .pc-price small { display: block; font-size: .7rem; font-weight: 600; color: #6b7280; }
    .pc-g th { padding: 1.4rem .7rem .55rem; text-align: left; border-bottom: 1px solid #dfe8d4; background: #fafcf7;
        font-family: var(--font-heading); font-size: .95rem; font-weight: 800; color: #1f3a0f; }
    .pc-gi { display: inline-grid; place-items: center; width: 1.7rem; height: 1.7rem; margin-right: .55rem; border-radius: .55rem; vertical-align: -.45rem;
        background: #fff; color: #4a7c2a; box-shadow: 0 1px 0 #d9e9c6, 0 6px 14px -10px rgb(20 33 12 / .5); }
    .pc-gi svg { width: 1rem; height: 1rem; }
    /* A group's note: said once, across the whole row. */
    .pc-table .pc-note td { padding: .7rem .7rem .75rem; text-align: left; background: #fffbea; border-bottom: 1px solid #f6e7b0; }
    .pc-note-in { display: flex; align-items: flex-start; gap: .55rem; font-size: .84rem; line-height: 1.5; font-weight: 600; color: #5c4a00; }
    .pc-note-in svg { flex: none; width: 1.1rem; height: 1.1rem; margin-top: .1rem; color: #c79e00; }
    .pc-row th { text-align: left; font-weight: 400; }
    .pc-row th b { display: block; font-size: .92rem; font-weight: 700; color: #1f2937; line-height: 1.3; }
    .pc-row th small { display: block; margin-top: .15rem; font-size: .76rem; line-height: 1.35; color: #6b7280; }
    .pc-row:hover > * { background: #f7faf3; }
    .pc-table td.is-star { background: rgb(243 248 236 / .7); }
    /* The header row is solid: rows slide under it, never show through. */
    .pc-table th.pc-plan.is-star { background: #f5f9f0; }
    .pc-row:hover > td.is-star { background: #eef5e5; }
    /* A plan's column lights up under the pointer. */
    .pc-table[data-hl="0"] [data-c="0"], .pc-table[data-hl="1"] [data-c="1"],
    .pc-table[data-hl="2"] [data-c="2"], .pc-table[data-hl="3"] [data-c="3"] { background: #eef6e5; }
    .pc-yes { display: inline-grid; place-items: center; width: 1.6rem; height: 1.6rem; border-radius: 999px; background: #e4f0d6; color: #3d6823; }
    .pc-yes svg { width: .95rem; height: .95rem; }
    /* Not in the plan: a quiet cross, the same mark the plan cards use, so it
       reads as "no" and not as a value nobody filled in. */
    .pc-no { display: inline-grid; place-items: center; width: 1.6rem; height: 1.6rem; border-radius: 999px; background: #f3f4f1; color: #a3ab9b; }
    .pc-no svg { width: .8rem; height: .8rem; }
    .pc-val { font-size: .88rem; font-weight: 800; color: #1f3a0f; }
    .pc-table tbody:last-child tr:last-child > * { border-bottom: 0; }

    /* A phone shows the two chosen plans; the others step out. */
    @media (max-width: 767.98px) {
        .pc-table .is-off { display: none; }
        /* Auto layout here: a fixed one keeps a width for the hidden plans' columns. */
        .pc-table { table-layout: auto; }
        .pc-table th, .pc-table td { padding: .7rem .45rem; }
        .pc-corner { width: 44%; }
        .pc-plan, .pc-table td { width: 28%; }
        .pc-row th b { font-size: .86rem; }
        .pc-row th small { font-size: .72rem; }
        .pc-plan b { font-size: .92rem; }
        .pc-price strong { font-size: 1.05rem; }
        .pc-table thead th:not(.is-off) { animation: pcIn .4s var(--pc-ease) both; }
        .pc-table td:not(.is-off) { animation: pcIn .4s var(--pc-ease) both; }
        /* Each plan's header is a picker: the Change chip says so, and the
           native select lies over the whole header cell for the thumb. */
        .pc-change { display: inline-flex; align-items: center; gap: .1rem; margin-top: .35rem; padding: .14rem .45rem .14rem .55rem; border-radius: 999px;
            font-size: .72rem; font-weight: 800; color: #2f5219; background: #eef5e5; white-space: nowrap; }
        .pc-change svg { width: .75rem; height: .75rem; }
        /* Two plans side by side: the flag would widen its column and wrap
           the other plan's name; the tinted column still marks it. */
        .pc-plan em { display: none; }
        .pc-swap { display: block; position: absolute; inset: 0; z-index: 2; width: 100%; height: 100%; opacity: 0; cursor: pointer;
            font-size: 16px; -webkit-appearance: none; appearance: none; }
        .pc-plan:has(.pc-swap:focus-visible) { box-shadow: inset 0 0 0 2px #86b556; }
    }
    @keyframes pcIn { from { opacity: 0; transform: translateY(4px); } to { opacity: 1; transform: none; } }

    .pc-cta { margin-top: 2.4rem; display: flex; flex-direction: column; align-items: center; gap: .7rem; text-align: center; }
    .pc-go { gap: .55rem; box-shadow: 0 18px 36px -18px rgb(199 158 0 / .85); transition: transform .28s var(--pc-ease), box-shadow .28s var(--pc-ease); }
    .pc-go:hover { transform: translateY(-2px); }
    .pc-go svg { width: 1.2rem; height: 1.2rem; }
    .pc-cta p { font-size: .9rem; font-weight: 700; color: #4a7c2a; }
    @media (max-width: 479.98px) { .pc-go { width: 100%; justify-content: center; } }

    @media (prefers-reduced-motion: reduce) {
        .pc *, .pc-back, .pc-back svg { animation: none !important; transition: none !important; }
    }
</style>
@endpush

@push('scripts')
<script>
    /* Billing, the two plans a phone compares, and the column under the
       pointer. Choosing the plan already in the other box swaps the two. */
    window.pcCompare = (a, b) => ({
        yearly: false, a, b, hl: null,
        init() {
            this.$watch('a', (v, old) => { if (v === this.b) this.b = old; });
            this.$watch('b', (v, old) => { if (v === this.a) this.a = old; });
        },
        // A header picker on a phone: the column it sits in becomes `to`.
        swap(col, to) {
            if (Number.isNaN(to) || to === col) return;
            if (col === this.a) this.a = to;
            else if (col === this.b) this.b = to;
        },
    });
</script>
@endpush
