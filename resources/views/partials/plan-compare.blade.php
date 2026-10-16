{{-- Every plan side by side (2026-10-01 on /pricing/compare; shared with the
     app's My Subscription since 2026-10-18, so the two can never tell it two
     ways). A row per limit or tool, read off config/tiers.php by
     App\Support\PlanCompare (the numbers the app's gates read). A desk shows
     all the plans; a phone picks two to set against each other. The header
     row stays on screen while the rows scroll under it.
     Takes $pcApp (true inside the app: the app bar's height for the sticky
     header, dark mode, Taglish pills on technical rows) and $pcCurrent (the
     member's own plan key, marked in the header). --}}
@php
    $pcApp = $pcApp ?? false;
    $pcCurrent = $pcCurrent ?? null;
    $pcPlans = \App\Support\PlanCompare::plans();
    $pcGroups = \App\Support\PlanCompare::groups();
    $pcKeys = array_keys($pcPlans);
    $pcTint = ['libre' => '#86b556', 'libreAnee' => '#f5c518', 'solo' => '#4a7c2a', 'owner' => '#2d5016'];
    $pcFree = collect($pcPlans)->search(fn ($t) => empty($t['price']));
    $pcB = array_search('solo', $pcKeys, true);
    // On a phone the member's own plan is the first of the two columns.
    $pcA = ($pcCurrent && ($ci = array_search($pcCurrent, $pcKeys, true)) !== false) ? $ci : ($pcFree !== false ? array_search($pcFree, $pcKeys, true) : 0);
    if ($pcB === false || $pcB === $pcA) {
        $pcB = $pcA + 1 < count($pcKeys) ? $pcA + 1 : max(0, $pcA - 1);
    }
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

<div class="pc {{ $pcApp ? 'is-app' : '' }}" x-data="pcCompare({{ $pcA }}, {{ $pcB }})">
    <div class="pc-bar {{ $pcApp ? '' : 'reveal' }}">
        <div class="pc-seg" role="group" aria-label="Billing period" :class="yearly && 'is-year'">
            <span class="pc-thumb" aria-hidden="true"></span>
            <button type="button" :aria-pressed="(!yearly).toString()" aria-pressed="true" @click="yearly = false">Monthly</button>
            <button type="button" :aria-pressed="yearly.toString()" aria-pressed="false" @click="yearly = true">Yearly @if ($pcSave > 0)<span class="pc-soft">save up to {{ $pcSave }}%</span>@endif</button>
        </div>
        {{-- A phone sets two plans against each other. --}}
        <div class="pc-pick">
            <label for="pcA{{ $pcApp ? 'App' : '' }}">Compare</label>
            <select id="pcA{{ $pcApp ? 'App' : '' }}" x-model.number="a">
                @foreach ($pcPlans as $key => $t)<option value="{{ $loop->index }}">{{ $t['name'] }}</option>@endforeach
            </select>
            <span>with</span>
            <select x-model.number="b" aria-label="with">
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
                        @php $i = $loop->index; $star = $key === 'owner'; $mine = $pcCurrent === $key; @endphp
                        <th scope="col" class="pc-plan {{ $star ? 'is-star' : '' }} {{ $mine ? 'is-mine' : '' }}" data-c="{{ $i }}" style="--tint: {{ $pcTint[$key] ?? '#86b556' }}"
                            :class="{ 'is-off': ![a, b].includes({{ $i }}) }" @mouseenter="hl = {{ $i }}">
                            <span class="pc-band" aria-hidden="true"></span>
                            @if ($mine)<em class="is-mine">Your plan</em>@elseif ($star)<em>Most complete</em>@endif
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
                    @foreach ($rows as $row)
                        @php [$label, $hint, $read] = $row; $tlKey = $row[3] ?? null; @endphp
                        <tr class="pc-row">
                            {{-- The Taglish pill only draws inside the app (Tagalog::on). --}}
                            <th scope="row"><b>{{ $label }}@if ($tlKey){{ \App\Support\Tagalog::flag($tlKey, $label) }}@endif</b>@if ($hint)<small>{{ $hint }}</small>@endif</th>
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
</div>

@once
@push('head')
<style>
    .pc { --pc-ease: cubic-bezier(.22,1,.36,1); --pc-top: calc(4rem + 1px);
        --pc-card: #fff; --pc-line: #eef2ea; --pc-head-line: #dfe8d4; --pc-group: #fafcf7; --pc-ink: #14210c; --pc-text: #1f2937; --pc-mute: #6b7280;
        --pc-star: rgb(243 248 236 / .7); --pc-star-head: #f5f9f0; --pc-hover: #f7faf3; --pc-hl: #eef6e5; }
    @media (min-width: 768px) { .pc { --pc-top: calc(5rem + 1px); } }
    /* Inside the app the header row sticks under the app bar (h-14, md:h-16). */
    .pc.is-app { --pc-top: calc(3.5rem + 1px); }
    @media (min-width: 768px) { .pc.is-app { --pc-top: calc(4rem + 1px); } }
    html.dark .pc { --pc-card: #151b12; --pc-line: #232e1b; --pc-head-line: #2b3a1c; --pc-group: #1a2215; --pc-ink: #eef4e6; --pc-text: #e2ead8; --pc-mute: #9aae8a;
        --pc-star: rgb(47 82 25 / .18); --pc-star-head: #1b2614; --pc-hover: #1c2617; --pc-hl: #20301a; }

    /* The bar over the table: billing, and on a phone the two plans to set side by side. */
    .pc-bar { display: flex; flex-direction: column; align-items: center; gap: .9rem; margin-bottom: 1.4rem; }
    .pc-seg { position: relative; display: inline-grid; grid-template-columns: 1fr 1fr; padding: .25rem; border-radius: 999px; background: var(--pc-card); box-shadow: 0 0 0 1px #e1e8d7, 0 10px 24px -20px rgb(20 33 12 / .5); }
    html.dark .pc-seg { box-shadow: 0 0 0 1px var(--pc-head-line); }
    .pc-seg button { position: relative; z-index: 1; display: flex; flex-direction: column; align-items: center; justify-content: center;
        min-height: 2.75rem; padding: .45rem 1.3rem; border-radius: 999px; font-size: .88rem; font-weight: 800; line-height: 1.15; color: #4b5563;
        transition: color .3s var(--pc-ease); }
    html.dark .pc-seg button { color: var(--pc-mute); }
    .pc-seg button[aria-pressed="true"], html.dark .pc-seg button[aria-pressed="true"] { color: #fff; }
    /* What a year saves, under the word, so the two halves stay equal. */
    .pc-soft { display: block; margin-top: .1rem; font-size: .72rem; font-weight: 700; opacity: .85; white-space: nowrap; }
    /* The two marks, explained once. */
    .pc-key { display: flex; flex-wrap: wrap; justify-content: center; gap: .4rem 1.1rem; font-size: .82rem; font-weight: 700; color: #4b5563; }
    html.dark .pc-key { color: var(--pc-mute); }
    .pc-key span { display: inline-flex; align-items: center; gap: .4rem; }
    .pc-key i { width: 1.35rem; height: 1.35rem; }
    .pc-key i svg { width: .8rem; height: .8rem; }
    .pc-thumb { position: absolute; top: .25rem; bottom: .25rem; left: .25rem; width: calc(50% - .25rem); border-radius: 999px; background: #4a7c2a;
        box-shadow: 0 8px 18px -10px rgb(47 82 25 / .8); transition: transform .45s var(--pc-ease); }
    .pc-seg.is-year .pc-thumb { transform: translateX(100%); }
    .pc-pick { display: flex; flex-wrap: wrap; align-items: center; justify-content: center; gap: .45rem; font-size: .88rem; font-weight: 700; color: #4b5563; }
    html.dark .pc-pick { color: var(--pc-mute); }
    .pc-pick select { appearance: none; -webkit-appearance: none; padding: .5rem 2rem .5rem .85rem; border-radius: .85rem; border: 1px solid #d6dfcb; background: var(--pc-card)
        url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' stroke='%234a7c2a' stroke-width='2.6' viewBox='0 0 24 24'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' d='M6 9l6 6 6-6'/%3E%3C/svg%3E") no-repeat right .6rem center / .9rem;
        font-size: 16px; font-weight: 800; color: var(--pc-ink); transition: border-color .28s var(--pc-ease), box-shadow .28s var(--pc-ease); }
    html.dark .pc-pick select { border-color: var(--pc-head-line); }
    .pc-pick select:focus { outline: none; border-color: #6b9f3d; box-shadow: 0 0 0 4px rgb(107 159 61 / .15); }
    @media (min-width: 768px) { .pc-pick { display: none; } }

    /* The table. Separate borders so the sticky header keeps its own. */
    /* overflow: clip rounds the corners without making a scroll box, so the header row can still stick to the page. */
    .pc-wrap { border-radius: 1.4rem; overflow: clip; background: var(--pc-card); box-shadow: 0 0 0 1px #e1e8d7, 0 30px 60px -40px rgb(20 33 12 / .45); }
    html.dark .pc-wrap { box-shadow: 0 0 0 1px var(--pc-head-line); }
    .pc-table { width: 100%; border-collapse: separate; border-spacing: 0; table-layout: fixed; }
    .pc-table th, .pc-table td { padding: .8rem .7rem; text-align: center; vertical-align: middle; border-bottom: 1px solid var(--pc-line);
        transition: background-color .28s var(--pc-ease); }
    .pc-table thead th { position: sticky; top: var(--pc-top); z-index: 3; background: var(--pc-card); border-bottom: 1.5px solid var(--pc-head-line); vertical-align: bottom; padding-top: 1.1rem; }
    .pc-corner { width: 34%; text-align: left !important; }
    .pc-corner span { font-size: .72rem; font-weight: 900; letter-spacing: .12em; text-transform: uppercase; color: #6b8f4a; }
    .pc-plan { position: relative; }
    .pc-band { position: absolute; left: .7rem; right: .7rem; top: 0; height: 4px; border-radius: 0 0 4px 4px; background: var(--tint); }
    /* The badge's capitals sit in the middle of the pill (2026-10-13, the owner):
       the line box is trimmed to the capitals where the browser can, and the
       padding leans down a touch where it cannot (capitals have no descenders). */
    .pc-plan em { display: inline-block; margin-bottom: .35rem; padding: .3rem .6rem .24rem; border-radius: 999px; background: #4a7c2a; color: #fff; font-style: normal;
        font-size: .64rem; font-weight: 900; line-height: 1; letter-spacing: .06em; text-transform: uppercase; }
    /* The member's own plan wears the house yellow. */
    .pc-plan em.is-mine { background: #f5c518; color: #3b2f00; }
    @supports (text-box: trim-both cap alphabetic) { .pc-plan em { text-box: trim-both cap alphabetic; padding: .32rem .6rem; } }
    .pc-plan b { display: block; font-family: var(--font-heading); font-size: 1.02rem; font-weight: 800; line-height: 1.2; color: var(--pc-ink); }
    /* The phone's column pickers live in the header; a desk shows every plan. */
    .pc-change, .pc-swap { display: none; }
    .pc-price { display: block; margin-top: .25rem; }
    .pc-price strong { font-family: var(--font-heading); font-size: 1.2rem; font-weight: 800; color: #2f5219; animation: pcIn .4s var(--pc-ease) both; }
    html.dark .pc-price strong { color: #b7d98f; }
    .pc-price small { display: block; font-size: .7rem; font-weight: 600; color: var(--pc-mute); }
    .pc-g th { padding: 1.4rem .7rem .55rem; text-align: left; border-bottom: 1px solid var(--pc-head-line); background: var(--pc-group);
        font-family: var(--font-heading); font-size: .95rem; font-weight: 800; color: #1f3a0f; }
    html.dark .pc-g th { color: #cfe3b8; }
    .pc-gi { display: inline-grid; place-items: center; width: 1.7rem; height: 1.7rem; margin-right: .55rem; border-radius: .55rem; vertical-align: -.45rem;
        background: var(--pc-card); color: #4a7c2a; box-shadow: 0 1px 0 #d9e9c6, 0 6px 14px -10px rgb(20 33 12 / .5); }
    html.dark .pc-gi { color: #a5c97e; box-shadow: 0 0 0 1px var(--pc-head-line); }
    .pc-gi svg { width: 1rem; height: 1rem; }
    /* A group's note: said once, across the whole row. */
    .pc-table .pc-note td { padding: .7rem .7rem .75rem; text-align: left; background: #fffbea; border-bottom: 1px solid #f6e7b0; }
    html.dark .pc-table .pc-note td { background: rgb(245 197 24 / .07); border-bottom-color: rgb(245 197 24 / .2); }
    .pc-note-in { display: flex; align-items: flex-start; gap: .55rem; font-size: .84rem; line-height: 1.5; font-weight: 600; color: #5c4a00; }
    html.dark .pc-note-in { color: #ecd58a; }
    .pc-note-in svg { flex: none; width: 1.1rem; height: 1.1rem; margin-top: .1rem; color: #c79e00; }
    .pc-row th { text-align: left; font-weight: 400; }
    .pc-row th b { display: block; font-size: .92rem; font-weight: 700; color: var(--pc-text); line-height: 1.3; }
    .pc-row th small { display: block; margin-top: .15rem; font-size: .76rem; line-height: 1.35; color: var(--pc-mute); }
    .pc-row:hover > * { background: var(--pc-hover); }
    .pc-table td.is-star { background: var(--pc-star); }
    /* The header row is solid: rows slide under it, never show through. */
    .pc-table th.pc-plan.is-star { background: var(--pc-star-head); }
    .pc-row:hover > td.is-star { background: var(--pc-hl); }
    /* A plan's column lights up under the pointer. */
    .pc-table[data-hl="0"] [data-c="0"], .pc-table[data-hl="1"] [data-c="1"],
    .pc-table[data-hl="2"] [data-c="2"], .pc-table[data-hl="3"] [data-c="3"] { background: var(--pc-hl); }
    .pc-yes { display: inline-grid; place-items: center; width: 1.6rem; height: 1.6rem; border-radius: 999px; background: #e4f0d6; color: #3d6823; }
    html.dark .pc-yes { background: rgb(107 159 61 / .22); color: #b7d98f; }
    .pc-yes svg { width: .95rem; height: .95rem; }
    /* Not in the plan: a quiet cross, the same mark the plan cards use, so it
       reads as "no" and not as a value nobody filled in. */
    .pc-no { display: inline-grid; place-items: center; width: 1.6rem; height: 1.6rem; border-radius: 999px; background: #f3f4f1; color: #a3ab9b; }
    html.dark .pc-no { background: #222a1c; color: #6f7d64; }
    .pc-no svg { width: .8rem; height: .8rem; }
    .pc-val { font-size: .88rem; font-weight: 800; color: #1f3a0f; }
    html.dark .pc-val { color: #dbe9c9; }
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
        html.dark .pc-change { color: #cfe3b8; background: #22301a; }
        .pc-change svg { width: .75rem; height: .75rem; }
        /* Two plans side by side: the flag would widen its column and wrap
           the other plan's name; the tinted column still marks it. */
        .pc-plan em { display: none; }
        .pc-swap { display: block; position: absolute; inset: 0; z-index: 2; width: 100%; height: 100%; opacity: 0; cursor: pointer;
            font-size: 16px; -webkit-appearance: none; appearance: none; }
        .pc-plan:has(.pc-swap:focus-visible) { box-shadow: inset 0 0 0 2px #86b556; }
    }
    @keyframes pcIn { from { opacity: 0; transform: translateY(4px); } to { opacity: 1; transform: none; } }

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
@endonce
