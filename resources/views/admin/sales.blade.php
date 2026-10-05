@extends('layouts.admin')

@section('title', 'Sales Analysis')
@section('subtitle', 'Sales, payments, and what each ad peso bought')

@section('content')
    {{-- Which analysis room, worn as the house tag. The Sales Dashboard is
         the first and the default (2026-09-30): money in, at a glance. The
         last room chosen is remembered. --}}
    <button type="button" class="ad-navtag" id="saTabBtn" aria-haspopup="dialog" title="Which analysis?">
        <span id="saTabIcon">💰</span>
        <span id="saTabNow">Sales Dashboard</span>
        <svg class="ad-navtag-c" fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24" style="width:1rem;height:1rem"><path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/></svg>
    </button>

    {{-- ---------------- Sales Dashboard ---------------- --}}
    <div class="mt-3 sa-room" data-room="sales">
        <div class="sd-top">
            <div class="sd-range" id="sdRange" role="tablist" aria-label="Which period">
                <button type="button" data-range="7d" role="tab">7 days</button>
                <button type="button" data-range="30d" role="tab" class="is-on">30 days</button>
                <button type="button" data-range="90d" role="tab">90 days</button>
                <button type="button" data-range="12m" role="tab">12 months</button>
                <button type="button" data-range="all" role="tab">All time</button>
            </div>
            <p class="sd-when" id="sdWhen"></p>
        </div>
        <div class="sd-kpis" id="sdKpis">
            @for ($i = 0; $i < 4; $i++)
                <div class="card sd-kpi"><div class="ad-skel h-3 w-20 mb-2"></div><div class="ad-skel h-6 w-24"></div></div>
            @endfor
        </div>
        <div class="card sd-card">
            <div class="sd-card-h"><b id="sdChartTitle">Revenue</b><span id="sdChartSub"></span></div>
            <div class="sd-chart" id="sdChart"><div class="ad-skel w-full h-40"></div></div>
        </div>
        <div class="sd-two">
            <div class="card sd-card"><div class="sd-card-h"><b>By product</b></div><div id="sdByProduct"><div class="ad-skel w-full h-16"></div></div></div>
            <div class="card sd-card"><div class="sd-card-h"><b>By payment method</b></div><div id="sdByMethod"><div class="ad-skel w-full h-16"></div></div></div>
        </div>
        <div class="card sd-card">
            <div class="sd-card-h"><b>Latest payments</b><a class="sd-link" id="sdOrdersLink" href="{{ route('admin.orders') }}">All orders ›</a></div>
            <div id="sdRecent"><div class="ad-skel w-full h-24"></div></div>
        </div>
    </div>

    {{-- ---------------- Acquisition Analysis ---------------- --}}
    <div class="mt-3 sa-room" data-room="acq" hidden>
        <div class="card p-4 mb-4">
            <p class="font-bold text-gray-900">New analysis</p>
            <p class="text-xs text-gray-500 mt-0.5 mb-3">Name the campaign, pick the dates it ran, and enter what the ads cost. The rest comes from the app's own records.</p>

            <div class="sa-form">
                <input type="text" id="saName" class="form-input" maxlength="191" placeholder="e.g. Facebook Ads, September push">

                <div class="sa-row">
                    {{-- The two dates wear tags; the native pickers stand behind them. --}}
                    <input type="date" id="saFrom" class="sa-date-native" tabindex="-1" aria-hidden="true">
                    <button type="button" class="crop-tag" id="saFromBtn">
                        <span class="crop-tag-e">📅</span>
                        <span class="crop-tag-t is-none" id="saFromNow">From date</span>
                        <svg class="crop-tag-c" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/></svg>
                    </button>
                    <input type="date" id="saTo" class="sa-date-native" tabindex="-1" aria-hidden="true">
                    <button type="button" class="crop-tag" id="saToBtn">
                        <span class="crop-tag-e">📅</span>
                        <span class="crop-tag-t is-none" id="saToNow">To date</span>
                        <svg class="crop-tag-c" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/></svg>
                    </button>
                </div>

                <div class="sa-row">
                    <div class="relative grow min-w-0">
                        <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-500 font-semibold pointer-events-none">₱</span>
                        <input type="number" id="saCost" min="0" step="0.01" class="form-input pl-8! w-full" placeholder="Ad cost" inputmode="decimal">
                    </div>
                    <input type="hidden" id="saCadence" value="daily">
                    <button type="button" class="crop-tag" id="saCadenceBtn">
                        <span class="crop-tag-e">⏱️</span>
                        <span class="crop-tag-t" id="saCadenceNow">Daily cost</span>
                        <svg class="crop-tag-c" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/></svg>
                    </button>
                </div>

                <button type="button" class="btn btn-primary w-full" id="saCreate">Create the analysis</button>
            </div>
        </div>

        <div id="saList" class="grid gap-3"></div>
        <p id="saEmpty" class="text-sm text-gray-400 text-center py-8" hidden>No analyses yet. Name your first campaign above.</p>

        {{-- The computed read of one batch. --}}
        <div id="saReport" class="mt-4" hidden></div>
    </div>
@endsection

@push('sheets')
<div class="sheet hidden" id="saTabSheet" style="--sheet-width:22rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title">Which analysis?</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body dt-rows" id="saRoomList">
        <button type="button" class="dt-row is-on" data-sa-room="sales" data-icon="💰" data-title="Sales Dashboard">
            <span class="dt-row-e">💰</span>
            <span class="dt-row-body"><b>Sales Dashboard</b><i>Money in: revenue over time, payments, what sells and how people pay.</i></span>
            <svg class="dt-row-tick" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
        </button>
        <button type="button" class="dt-row" data-sa-room="acq" data-icon="📈" data-title="Acquisition Analysis">
            <span class="dt-row-e">📈</span>
            <span class="dt-row-body"><b>Acquisition Analysis</b><i>Ad spend against registrations, conversions and revenue.</i></span>
            <svg class="dt-row-tick" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
        </button>
    </div>
</div>

<div class="sheet hidden" id="saCadenceSheet" style="--sheet-width:22rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title">How was the cost counted?</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body dt-rows" id="saCadenceList">
        <button type="button" class="dt-row is-on" data-sa-cadence="daily">
            <span class="dt-row-e">☀️</span>
            <span class="dt-row-body"><b>Daily cost</b><i>The amount is what one day of ads cost.</i></span>
            <svg class="dt-row-tick" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
        </button>
        <button type="button" class="dt-row" data-sa-cadence="weekly">
            <span class="dt-row-e">🗓️</span>
            <span class="dt-row-body"><b>Weekly cost</b><i>The amount is what one week of ads cost.</i></span>
            <svg class="dt-row-tick" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
        </button>
        <button type="button" class="dt-row" data-sa-cadence="monthly">
            <span class="dt-row-e">📆</span>
            <span class="dt-row-body"><b>Monthly cost</b><i>The amount is what one month of ads cost.</i></span>
            <svg class="dt-row-tick" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
        </button>
    </div>
</div>
@endpush

@push('head')
@include('partials.tag-sheet-css')
<style>
    .sa-form { display: grid; gap: .6rem; }
    .sa-row { display: flex; gap: .5rem; flex-wrap: wrap; align-items: center; }
    /* The tags stand the same height as the fields beside them. */
    .sa-row .crop-tag { flex: 1 1 10rem; min-width: 0; min-height: var(--sa-field-h, 2.75rem); }
    /* The native pickers stand invisibly behind their tags — showPicker()
       needs them rendered, not display:none. */
    .sa-date-native { position: absolute; width: 1px; height: 1px; opacity: 0; pointer-events: none; }

    .sa-card { text-align: left; width: 100%; display: flex; align-items: center; gap: .7rem;
        padding: .85rem .9rem; border-radius: .9rem; border: 1px solid var(--color-gray-200);
        background: var(--color-white); cursor: pointer;
        transition: border-color .28s cubic-bezier(.22,1,.36,1), box-shadow .28s cubic-bezier(.22,1,.36,1); }
    .sa-card:hover { border-color: #a8cc7e; box-shadow: 0 10px 24px -18px rgb(0 0 0 / .4); }
    .sa-card.is-open { border-color: var(--color-brand-500); }
    .sa-card b { display: block; font-size: .9rem; color: var(--color-gray-900); }
    .sa-card i { display: block; font-style: normal; font-size: .72rem; color: var(--color-gray-500); margin-top: .1rem; }
    .sa-del { flex: none; width: 2rem; height: 2rem; border-radius: .55rem; display: inline-flex;
        align-items: center; justify-content: center; color: var(--color-gray-400);
        transition: color .28s cubic-bezier(.22,1,.36,1), background-color .28s cubic-bezier(.22,1,.36,1); }
    .sa-del:hover { color: #dc2626; background: var(--color-red-50, #fef2f2); }
    html.dark .sa-del:hover { color: #fca5a5; }
    .sa-del svg { width: 1rem; height: 1rem; }
    .sa-go { width: 1rem; height: 1rem; flex: none; color: var(--color-gray-400); }
    /* Night: every colour above is a token already -- the cards take the
       same surface as the form card over them instead of a green-black. */

    /* The read: hero, funnel, unit economics, plan mix. */
    .sa-hero { border-radius: 1.1rem; padding: 1.1rem 1.2rem; color: #fff;
        background: linear-gradient(130deg, #4a7c2a, #2d5016 70%); }
    .sa-hero.is-loss { background: linear-gradient(130deg, #b45309, #92400e 70%); }
    .sa-hero h2 { font-size: 1.1rem; font-weight: 800; }
    .sa-hero .sub { font-size: .8rem; opacity: .92; margin-top: .25rem; }
    .sa-hero .net { font-size: 1.6rem; font-weight: 800; margin-top: .6rem; font-variant-numeric: tabular-nums; }
    .sa-hero .netword { font-size: .68rem; font-weight: 800; letter-spacing: .06em; text-transform: uppercase; opacity: .85; }

    .sa-funnel { margin-top: .9rem; display: grid; gap: .55rem; }
    .sa-step { display: grid; gap: .25rem; }
    .sa-step .lab { display: flex; justify-content: space-between; font-size: .76rem; font-weight: 700;
        color: var(--color-gray-600); }
    .sa-step .lab b { color: var(--color-gray-900); font-variant-numeric: tabular-nums; }
    .sa-step .bar { height: 1.5rem; border-radius: .5rem; background: var(--color-gray-100); overflow: hidden; }
    .sa-step .bar i { display: block; height: 100%; border-radius: .5rem; width: 0;
        transition: width .7s cubic-bezier(.22,1,.36,1); }
    .sa-step:nth-child(1) .bar i { background: linear-gradient(90deg, #6b9f3d, #4a7c2a); }
    .sa-step:nth-child(2) .bar i { background: linear-gradient(90deg, #f0b429, #d98214); }
    html.dark .sa-step .bar { background: rgb(255 255 255 / .07); }

    .sa-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: .55rem; margin-top: .9rem; }
    @media (min-width: 640px) { .sa-grid { grid-template-columns: repeat(4, 1fr); } }
    .sa-stat { border-radius: .85rem; border: 1px solid var(--color-gray-200); background: var(--color-white);
        padding: .65rem .75rem; }
    .sa-stat i { display: block; font-style: normal; font-size: .64rem; font-weight: 800;
        letter-spacing: .05em; text-transform: uppercase; color: var(--color-gray-400); }
    .sa-stat b { display: block; font-size: 1.05rem; font-weight: 800; color: var(--color-gray-900);
        font-variant-numeric: tabular-nums; margin-top: .15rem; overflow: hidden; text-overflow: ellipsis; }
    .sa-stat small { display: block; font-size: .66rem; color: var(--color-gray-400); margin-top: .1rem; }
    html:not(.dark) .sa-stat i, html:not(.dark) .sa-stat small { color: var(--color-gray-500); }

    .sa-mix { margin-top: .9rem; border-radius: .9rem; border: 1px solid var(--color-gray-200);
        background: var(--color-white); padding: .8rem .9rem; }
    .sa-mix h3 { font-size: .8rem; font-weight: 800; color: var(--color-gray-900); margin-bottom: .4rem; }
    .sa-mix-row { display: flex; justify-content: space-between; gap: .6rem; font-size: .8rem;
        color: var(--color-gray-600); padding: .22rem 0; }
    .sa-mix-row b { color: var(--color-gray-900); font-variant-numeric: tabular-nums; }
    @media (prefers-reduced-motion: reduce) { .sa-card, .sa-del, .sa-step .bar i { transition: none; } }

    /* ---- rooms ---- */
    .sa-room.is-entering { animation: saRoomIn .28s cubic-bezier(.22,1,.36,1); }
    @keyframes saRoomIn { from { opacity: 0; } to { opacity: 1; } }

    /* ---- Sales Dashboard ---- */
    .sd-top { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: .5rem; margin-bottom: .75rem; }
    .sd-range { display: inline-flex; gap: .2rem; padding: .2rem; border-radius: .8rem; background: var(--color-gray-100); max-width: 100%; overflow-x: auto; }
    .sd-range button { flex: none; padding: .38rem .7rem; border-radius: .6rem; font-size: .76rem; font-weight: 700; color: var(--color-gray-600);
        transition: background-color .28s cubic-bezier(.22,1,.36,1), color .28s cubic-bezier(.22,1,.36,1), box-shadow .28s cubic-bezier(.22,1,.36,1); }
    .sd-range button.is-on { background: var(--color-white); color: var(--color-gray-900); box-shadow: 0 2px 8px -4px rgb(0 0 0 / .35); }
    .sd-when { font-size: .74rem; color: var(--color-gray-500); }
    .sd-kpis { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .6rem; }
    @media (min-width: 768px) { .sd-kpis { grid-template-columns: repeat(4, minmax(0, 1fr)); } }
    .sd-kpi { padding: .8rem .9rem; min-width: 0; animation: saRoomIn .32s cubic-bezier(.22,1,.36,1) both; }
    .sd-kpi i { display: block; font-style: normal; font-size: .68rem; font-weight: 800; letter-spacing: .04em; text-transform: uppercase; color: var(--color-gray-500); }
    .sd-kpi b { display: block; font-size: 1.35rem; font-weight: 800; color: var(--color-gray-900); font-variant-numeric: tabular-nums;
        margin-top: .2rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .sd-kpi small { display: block; font-size: .7rem; color: var(--color-gray-500); margin-top: .15rem; }
    .sd-kpi a { color: inherit; text-decoration: none; }
    .sd-delta { display: inline-flex; align-items: center; gap: .15rem; font-weight: 800; }
    .sd-delta.up { color: #2f6b1d; } .sd-delta.down { color: #b42318; } .sd-delta.flat { color: var(--color-gray-500); }
    html.dark .sd-delta.up { color: #a5d17c; } html.dark .sd-delta.down { color: #fca5a5; }
    .sd-card { padding: .9rem 1rem; margin-top: .75rem; min-width: 0; }
    .sd-card-h { display: flex; align-items: baseline; justify-content: space-between; gap: .6rem; margin-bottom: .6rem; }
    .sd-card-h b { font-size: .88rem; font-weight: 800; color: var(--color-gray-900); }
    .sd-card-h span { font-size: .72rem; color: var(--color-gray-500); }
    .sd-link { font-size: .74rem; font-weight: 700; color: var(--color-brand-700); text-decoration: none; }
    .sd-two { display: grid; gap: 0 .75rem; }
    @media (min-width: 768px) { .sd-two { grid-template-columns: 1fr 1fr; } }
    /* The chart: one series, one hue, thin bars rising from the baseline. */
    .sd-chart { position: relative; --sd-bar: #4a7c2a; --sd-grid: #eef1ea; --sd-axis: #6b7280; }
    html.dark .sd-chart { --sd-bar: #8fbf5f; --sd-grid: rgb(255 255 255 / .07); --sd-axis: #93a684; }
    .sd-chart svg { display: block; width: 100%; height: 13rem; overflow: visible; }
    .sd-chart .bar { fill: var(--sd-bar); transform-origin: bottom; transform-box: fill-box; transform: scaleY(0);
        transition: transform .5s cubic-bezier(.22,1,.36,1), opacity .2s; }
    .sd-chart.is-grown .bar { transform: scaleY(1); }
    .sd-chart .hit { fill: transparent; cursor: default; }
    .sd-chart .hit:hover + .bar, .sd-chart .bar.is-hot { opacity: .78; }
    .sd-chart .grid { stroke: var(--sd-grid); stroke-width: 1; }
    .sd-chart .base { stroke: var(--sd-axis); stroke-width: 1; opacity: .5; }
    .sd-chart text { fill: var(--sd-axis); font-size: 10px; font-variant-numeric: tabular-nums; }
    .sd-tip { position: absolute; z-index: 3; pointer-events: none; padding: .4rem .55rem; border-radius: .55rem; font-size: .72rem; line-height: 1.35;
        background: var(--color-gray-900); color: #fff; white-space: nowrap; transform: translate(-50%, calc(-100% - 8px)); opacity: 0;
        transition: opacity .15s; box-shadow: 0 8px 20px -10px rgb(0 0 0 / .5); }
    .sd-tip.is-on { opacity: 1; }
    .sd-tip b { display: block; font-size: .8rem; }
    html.dark .sd-tip { background: #e8efe1; color: #14210c; }
    .sd-empty { text-align: center; font-size: .8rem; color: var(--color-gray-500); padding: 1.4rem .5rem; }
    /* Breakdown rows: a label, a bar in the same hue, the figure in text. */
    .sd-rows { display: grid; gap: .55rem; }
    .sd-row { display: grid; gap: .25rem; }
    .sd-row-top { display: flex; justify-content: space-between; gap: .6rem; font-size: .78rem; color: var(--color-gray-700); }
    .sd-row-top span { min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .sd-row-top b { color: var(--color-gray-900); font-variant-numeric: tabular-nums; flex: none; }
    .sd-row-top small { color: var(--color-gray-500); font-weight: 600; }
    .sd-track { height: .55rem; border-radius: 999px; background: var(--color-gray-100); overflow: hidden; }
    .sd-track i { display: block; height: 100%; width: 0; border-radius: 999px; background: #4a7c2a; transition: width .6s cubic-bezier(.22,1,.36,1); }
    html.dark .sd-track { background: rgb(255 255 255 / .07); } html.dark .sd-track i { background: #8fbf5f; }
    /* Latest payments: a table on a wide screen, stacked rows on a phone. */
    .sd-table-wrap { overflow-x: auto; }
    .sd-table { width: 100%; border-collapse: collapse; font-size: .78rem; }
    .sd-table th { text-align: left; font-size: .66rem; font-weight: 800; letter-spacing: .04em; text-transform: uppercase; color: var(--color-gray-500);
        padding: .35rem .5rem; border-bottom: 1px solid var(--color-gray-200); white-space: nowrap; }
    .sd-table td { padding: .5rem; border-bottom: 1px solid var(--color-gray-100); color: var(--color-gray-700); vertical-align: top; }
    .sd-table td.amt { text-align: right; font-weight: 800; color: var(--color-gray-900); font-variant-numeric: tabular-nums; white-space: nowrap; }
    .sd-table tr:last-child td { border-bottom: 0; }
    .sd-table a { color: var(--color-brand-700); font-weight: 700; text-decoration: none; }
    .sd-table small { display: block; color: var(--color-gray-500); }
    @media (max-width: 639px) {
        .sd-table thead { display: none; }
        .sd-table tr { display: grid; grid-template-columns: 1fr auto; gap: .1rem .6rem; padding: .55rem 0; border-bottom: 1px solid var(--color-gray-100); }
        .sd-table td { padding: 0; border: 0; }
        .sd-table td.when { grid-column: 1 / -1; font-size: .7rem; color: var(--color-gray-500); }
        .sd-table td.buyer { grid-column: 1; } .sd-table td.amt { grid-column: 2; grid-row: 2 / span 2; align-self: center; }
        .sd-table td.item { grid-column: 1; } .sd-table td.method { grid-column: 1; font-size: .7rem; }
    }
    html.dark .sd-range { background: rgb(255 255 255 / .06); }
    html.dark .sd-range button.is-on { background: #243019; color: #e8efe1; }
    @media (prefers-reduced-motion: reduce) {
        .sa-room.is-entering, .sd-kpi { animation: none; }
        .sd-chart .bar, .sd-track i { transition: none; }
    }
</style>
@endpush

@push('scripts')
<script>
(() => {
    const $id = (x) => document.getElementById(x);
    const esc = window.adminEsc || ((s) => String(s ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;'));
    const CSRF = document.querySelector('meta[name=csrf-token]')?.content || '';
    const U = {
        list: '{{ route('admin.data.sales') }}',
        one: (id) => '{{ url('/admin/data/sales') }}/' + id,
        store: '{{ route('admin.sales.store') }}',
        del: (id) => '{{ url('/admin/sales') }}/' + id,
    };
    const peso = (n) => '₱' + Number(n || 0).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const ask = async (url, opts) => {
        const res = await fetch(url, Object.assign({
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        }, opts || {}));
        const j = await res.json();
        if (!res.ok || !j.success) throw new Error(j.message || 'Something went wrong.');
        return j;
    };
    const say = (m, k) => (window.toast ? toast(m, k) : alert(m));

    /* ---- the rooms ----------------------------------------------------
       One page, several analyses; the tag says which is showing and the
       sheet swaps them with a fade. The last one chosen is remembered. */
    const ROOM_KEY = 'adminSalesRoom';
    function showRoom(room, animate) {
        const row = document.querySelector(`[data-sa-room="${room}"]`) || document.querySelector('[data-sa-room="sales"]');
        room = row.getAttribute('data-sa-room');
        document.querySelectorAll('.sa-room').forEach((el) => {
            const on = el.getAttribute('data-room') === room;
            el.hidden = !on;
            el.classList.remove('is-entering');
            if (on && animate) { void el.offsetWidth; el.classList.add('is-entering'); }
        });
        document.querySelectorAll('#saRoomList [data-sa-room]').forEach((r) => r.classList.toggle('is-on', r === row));
        $id('saTabNow').textContent = row.getAttribute('data-title');
        $id('saTabIcon').textContent = row.getAttribute('data-icon');
        try { localStorage.setItem(ROOM_KEY, room); } catch (_) {}
        if (room === 'sales') loadDash();
    }
    $id('saRoomList').addEventListener('click', (e) => {
        const row = e.target.closest('[data-sa-room]');
        if (!row) return;
        window.closeSheet && closeSheet('saTabSheet');
        showRoom(row.getAttribute('data-sa-room'), true);
    });

    /* ---- the Sales Dashboard ------------------------------------------ */
    const DASH_URL = '{{ route('admin.data.sales.dashboard') }}';
    let dashRange = '30d', dashBusy = false, dashFor = null, lastDash = null;
    const pesoShort = (n) => {
        n = Number(n || 0);
        if (n >= 1e6) return '₱' + (Math.round(n / 1e5) / 10) + 'M';
        if (n >= 1e4) return '₱' + Math.round(n / 1e3) + 'k';
        if (n >= 1e3) return '₱' + (Math.round(n / 100) / 10) + 'k';
        return '₱' + Math.round(n);
    };
    /** "+12% vs the 30 days before", said plainly; nothing to compare, nothing said. */
    function delta(now, before, compare) {
        if (!compare) return '';
        if (!before && !now) return `<span class="sd-delta flat">No change</span> vs the ${esc(compare.replace(/^last /, ''))} before`;
        if (!before) return `<span class="sd-delta up">▲ New</span> vs the ${esc(compare.replace(/^last /, ''))} before`;
        const pct = Math.round(((now - before) / before) * 100);
        const cls = pct > 0 ? 'up' : (pct < 0 ? 'down' : 'flat');
        const mark = pct > 0 ? '▲' : (pct < 0 ? '▼' : '');
        return `<span class="sd-delta ${cls}">${mark} ${Math.abs(pct)}%</span> vs the ${esc(compare.replace(/^last /, ''))} before`;
    }
    function paintKpis(d) {
        const k = d.kpis;
        const other = Object.entries(k.otherCurrency || {}).map(([c, v]) => `${c} ${Number(v).toLocaleString('en-PH', { minimumFractionDigits: 2 })}`).join(', ');
        $id('sdKpis').innerHTML = [
            `<div class="card sd-kpi"><i>Revenue</i><b>${peso(k.revenue)}</b><small>${delta(k.revenue, k.revenuePrev, d.compare) || (other ? 'Plus ' + esc(other) : 'Since the first sale')}</small></div>`,
            `<div class="card sd-kpi" style="animation-delay:40ms"><i>Payments</i><b>${k.count.toLocaleString()}</b><small>${delta(k.count, k.countPrev, d.compare) || (k.buyers + ' buyer' + (k.buyers === 1 ? '' : 's'))}</small></div>`,
            `<div class="card sd-kpi" style="animation-delay:80ms"><i>Average payment</i><b>${peso(k.average)}</b><small>${k.buyers} buyer${k.buyers === 1 ? '' : 's'} · today ${peso(k.today)}</small></div>`,
            `<div class="card sd-kpi" style="animation-delay:120ms"><a href="${esc(d.ordersUrl)}?status=review"><i>Waiting for review</i><b>${k.inReview.count.toLocaleString()}</b><small>${peso(k.inReview.amount)} to check${k.revoked.count ? ' · ' + k.revoked.count + ' revoked' : ''} ›</small></a></div>`,
        ].join('');
    }
    // Hover tooltip, shared by every bar.
    const tip = document.createElement('div');
    tip.className = 'sd-tip';
    function paintChart(d) {
        const box = $id('sdChart');
        const s = d.series || [];
        $id('sdChartTitle').textContent = d.bucket === 'day' ? 'Revenue by day' : 'Revenue by month';
        $id('sdChartSub').textContent = (d.kpis.plans || d.kpis.credits)
            ? `Plans ${peso(d.kpis.plans)} · AI credits ${peso(d.kpis.credits)}` : '';
        if (!s.length || !s.some((b) => b.amount > 0)) {
            box.innerHTML = '<p class="sd-empty">No payments in this period yet.</p>';
            return;
        }
        const W = Math.max(320, box.clientWidth || 640), H = 208, L = 44, R = 6, T = 10, B = 22;
        const max = Math.max(...s.map((b) => b.amount));
        // Round the top of the scale to a friendly number.
        const mag = Math.pow(10, Math.floor(Math.log10(max)));
        const top = Math.ceil(max / mag) * mag;
        const pw = W - L - R, ph = H - T - B;
        const slot = pw / s.length;
        const gap = Math.min(2, slot * 0.25);
        const bw = Math.max(1, slot - gap);
        const y = (v) => T + ph - (v / top) * ph;
        const grid = [0, 0.5, 1].map((f) => {
            const v = top * f, yy = y(v);
            return `<line class="${f === 0 ? 'base' : 'grid'}" x1="${L}" x2="${W - R}" y1="${yy}" y2="${yy}"/><text x="${L - 6}" y="${yy + 3}" text-anchor="end">${pesoShort(v)}</text>`;
        }).join('');
        // Label every nth bucket so the words never collide.
        const every = Math.max(1, Math.ceil(s.length / Math.max(2, Math.floor(pw / 58))));
        const bars = s.map((b, i) => {
            const x = L + i * slot + gap / 2;
            const h = Math.max(b.amount > 0 ? 2 : 0, ph - (y(b.amount) - T));
            const r = Math.min(4, bw / 2, h);
            const yy = T + ph - h;
            // A bar with a rounded top, flat on the baseline.
            const path = h > 0
                ? `M${x},${T + ph} L${x},${yy + r} Q${x},${yy} ${x + r},${yy} L${x + bw - r},${yy} Q${x + bw},${yy} ${x + bw},${yy + r} L${x + bw},${T + ph} Z`
                : '';
            const label = i % every === 0 ? `<text x="${x + bw / 2}" y="${H - 6}" text-anchor="middle">${esc(b.label)}</text>` : '';
            return `<rect class="hit" x="${L + i * slot}" y="${T}" width="${slot}" height="${ph}" data-i="${i}"/>${path ? `<path class="bar" d="${path}" data-i="${i}"/>` : ''}${label}`;
        }).join('');
        box.classList.remove('is-grown');
        box.innerHTML = `<svg viewBox="0 0 ${W} ${H}" preserveAspectRatio="none" role="img" aria-label="${esc($id('sdChartTitle').textContent)}">${grid}${bars}</svg>`;
        box.appendChild(tip);
        requestAnimationFrame(() => requestAnimationFrame(() => box.classList.add('is-grown')));
        const svg = box.querySelector('svg');
        const hide = () => { tip.classList.remove('is-on'); box.querySelectorAll('.bar.is-hot').forEach((b) => b.classList.remove('is-hot')); };
        svg.addEventListener('mousemove', (e) => {
            const hit = e.target.closest('.hit');
            if (!hit) { hide(); return; }
            const b = s[+hit.getAttribute('data-i')];
            box.querySelectorAll('.bar.is-hot').forEach((x) => x.classList.remove('is-hot'));
            box.querySelector(`.bar[data-i="${hit.getAttribute('data-i')}"]`)?.classList.add('is-hot');
            tip.innerHTML = `<b>${peso(b.amount)}</b>${esc(b.label)} · ${b.count} payment${b.count === 1 ? '' : 's'}`;
            const rect = box.getBoundingClientRect(), hr = hit.getBoundingClientRect();
            const bx = hr.left + hr.width / 2 - rect.left;
            tip.style.left = Math.min(rect.width - 70, Math.max(70, bx)) + 'px';
            tip.style.top = Math.max(10, (y(b.amount) / H) * rect.height) + 'px';
            tip.classList.add('is-on');
        });
        svg.addEventListener('mouseleave', hide);
        // A tap on a phone shows the same answer.
        svg.addEventListener('click', (e) => { const hit = e.target.closest('.hit'); if (hit) svg.dispatchEvent(new MouseEvent('mousemove', { clientX: e.clientX, clientY: e.clientY, bubbles: true })); });
    }
    function paintRows(el, rows, empty) {
        if (!rows.length) { el.innerHTML = `<p class="sd-empty">${esc(empty)}</p>`; return; }
        const max = Math.max(...rows.map((r) => r.amount), 1);
        el.innerHTML = '<div class="sd-rows">' + rows.map((r) => `
            <div class="sd-row">
                <div class="sd-row-top"><span>${esc(r.label)} <small>· ${r.count}</small></span><b>${peso(r.amount)}</b></div>
                <div class="sd-track"><i data-w="${Math.max(2, Math.round((r.amount / max) * 100))}%"></i></div>
            </div>`).join('') + '</div>';
        requestAnimationFrame(() => requestAnimationFrame(() => el.querySelectorAll('.sd-track i').forEach((i) => { i.style.width = i.getAttribute('data-w'); })));
    }
    function paintRecent(rows) {
        const el = $id('sdRecent');
        if (!rows.length) { el.innerHTML = '<p class="sd-empty">No payments in this period yet.</p>'; return; }
        el.innerHTML = `<div class="sd-table-wrap"><table class="sd-table">
            <thead><tr><th>When</th><th>Buyer</th><th>Bought</th><th>Paid by</th><th style="text-align:right">Amount</th></tr></thead>
            <tbody>${rows.map((r) => `<tr>
                <td class="when">${esc(r.when)}</td>
                <td class="buyer">${esc(r.buyer)}${r.email && r.email !== r.buyer ? `<small>${esc(r.email)}</small>` : ''}</td>
                <td class="item">${r.href ? `<a href="${esc(r.href)}">${esc(r.item)}</a>` : esc(r.item)}</td>
                <td class="method">${esc(r.method)}</td>
                <td class="amt">${r.currency === 'PHP' ? peso(r.amount) : esc(r.currency) + ' ' + Number(r.amount).toFixed(2)}</td>
            </tr>`).join('')}</tbody></table></div>`;
    }
    async function loadDash(force) {
        if (dashBusy || (!force && dashFor === dashRange)) return;
        dashBusy = true;
        const want = dashRange;
        document.querySelectorAll('#sdRange [data-range]').forEach((b) => b.classList.toggle('is-on', b.getAttribute('data-range') === want));
        try {
            const j = await ask(DASH_URL + '?range=' + encodeURIComponent(want));
            const d = j.data;
            dashFor = want;
            lastDash = d;
            $id('sdWhen').textContent = d.from + ' to ' + d.to;
            paintKpis(d);
            paintChart(d);
            paintRows($id('sdByProduct'), d.byProduct || [], 'Nothing sold in this period.');
            paintRows($id('sdByMethod'), d.byMethod || [], 'No payments in this period.');
            paintRecent(d.recent || []);
        } catch (err) {
            say(err.message || 'Could not load the sales.', 'error');
        } finally {
            dashBusy = false;
            if (dashRange !== want) loadDash(true);
        }
    }
    $id('sdRange').addEventListener('click', (e) => {
        const b = e.target.closest('[data-range]');
        if (!b || b.getAttribute('data-range') === dashRange) return;
        dashRange = b.getAttribute('data-range');
        document.querySelectorAll('#sdRange [data-range]').forEach((x) => x.classList.toggle('is-on', x === b));
        loadDash(true);
    });
    // The chart is drawn to the width it has; a new width redraws it from
    // what was already read.
    let resizeT = null;
    window.addEventListener('resize', () => {
        clearTimeout(resizeT);
        resizeT = setTimeout(() => { if (lastDash && !$id('sdChart').closest('.sa-room').hidden) paintChart(lastDash); }, 250);
    });

    /* ---- the chooser tags ---- */
    $id('saTabBtn').addEventListener('click', () => window.openSheet && openSheet('saTabSheet'));
    $id('saCadenceBtn').addEventListener('click', () => window.openSheet && openSheet('saCadenceSheet'));
    const CADENCE_SAYS = { daily: 'Daily cost', weekly: 'Weekly cost', monthly: 'Monthly cost' };
    $id('saCadenceList').addEventListener('click', (e) => {
        const row = e.target.closest('[data-sa-cadence]');
        if (!row) return;
        $id('saCadence').value = row.getAttribute('data-sa-cadence');
        $id('saCadenceNow').textContent = CADENCE_SAYS[$id('saCadence').value];
        document.querySelectorAll('#saCadenceList .dt-row').forEach((r) => r.classList.toggle('is-on', r === row));
        window.closeSheet && closeSheet('saCadenceSheet');
    });

    /* ---- the date tags drive the native pickers ---- */
    const wireDate = (btnId, inputId, nowId, word) => {
        const btn = $id(btnId), input = $id(inputId), now = $id(nowId);
        btn.addEventListener('click', () => {
            if (input.showPicker) { try { input.showPicker(); return; } catch (_) { } }
            input.focus();
            input.click();
        });
        input.addEventListener('change', () => {
            now.textContent = input.value
                ? new Date(input.value + 'T00:00:00').toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
                : word;
            now.classList.toggle('is-none', !input.value);
        });
    };
    wireDate('saFromBtn', 'saFrom', 'saFromNow', 'From date');
    wireDate('saToBtn', 'saTo', 'saToNow', 'To date');

    /* ---- the shelf ---- */
    async function load() {
        const j = await ask(U.list).catch((e) => { say(e.message, 'error'); return null; });
        if (!j) return;
        const rows = j.data.rows || [];
        $id('saEmpty').hidden = rows.length > 0;
        $id('saList').innerHTML = rows.map((r) => `
            <div class="sa-card" data-sa-open="${r.id}">
                <span class="min-w-0 grow">
                    <b>${esc(r.name)}</b>
                    <i>${esc(r.fromSays)} → ${esc(r.toSays)} · ${peso(r.adCost)} ${esc(r.adCadence)}</i>
                </span>
                <span role="button" tabindex="0" class="sa-del" data-sa-del="${r.id}" aria-label="Delete ${esc(r.name)}">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 6h18M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6M8 6V4a2 2 0 012-2h4a2 2 0 012 2v2M10 11v6M14 11v6"/></svg>
                </span>
                <svg class="sa-go" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
            </div>`).join('');
        window.adminRise && adminRise($id('saList'));
    }

    /* ---- the read of one batch ---- */
    async function open(id) {
        document.querySelectorAll('.sa-card').forEach((c) => c.classList.toggle('is-open', c.getAttribute('data-sa-open') === String(id)));
        const host = $id('saReport');
        host.hidden = false;
        host.innerHTML = '<p class="text-sm text-gray-400 text-center py-4 flex items-center justify-center gap-2"><span class="ad-spin"></span> Reading the records…</p>';
        window.adminRise && adminRise(host);
        let d;
        try { d = (await ask(U.one(id))).data; } catch (e) { say(e.message, 'error'); host.hidden = true; return; }
        const convPct = d.registrations > 0 ? Math.max(2, Math.round(d.converted / d.registrations * 100)) : 0;
        host.innerHTML = `
            <div class="sa-hero ${d.net < 0 ? 'is-loss' : ''}">
                <h2>${esc(d.name)}</h2>
                <p class="sub">${esc(d.from)} → ${esc(d.to)} · ${d.days} days · ${peso(d.adCost)} ${esc(d.adCadence)} × ${d.units} = <b>${peso(d.spend)}</b> spent</p>
                <p class="netword">${d.net < 0 ? 'Net so far' : 'Net return'}</p>
                <p class="net">${d.net < 0 ? '−' : '+'}${peso(Math.abs(d.net))}</p>
            </div>
            <div class="sa-funnel card p-4">
                <div class="sa-step">
                    <span class="lab"><span>Registered <small>(workers excluded)</small></span><b>${d.registrations}</b></span>
                    <span class="bar"><i data-w="${d.registrations > 0 ? 100 : 0}"></i></span>
                </div>
                <div class="sa-step">
                    <span class="lab"><span>Became paying clients</span><b>${d.converted} · ${d.conversionRate}%</b></span>
                    <span class="bar"><i data-w="${convPct}"></i></span>
                </div>
            </div>
            <div class="sa-grid">
                <div class="sa-stat"><i>Ad spend</i><b>${peso(d.spend)}</b><small>${peso(d.adCost)} ${esc(d.adCadence)} × ${d.units}</small></div>
                <div class="sa-stat"><i>Registrations</i><b>${d.registrations}</b><small>${d.costPerRegistration !== null ? peso(d.costPerRegistration) + ' each' : 'no sign ups yet'}</small></div>
                <div class="sa-stat"><i>Clients won</i><b>${d.converted}</b><small>${d.costPerClient !== null ? peso(d.costPerClient) + ' to acquire one' : 'none converted yet'}</small></div>
                <div class="sa-stat"><i>Revenue</i><b>${peso(d.revenue)}</b><small>${d.avgRevenuePerClient !== null ? peso(d.avgRevenuePerClient) + ' per client' : 'no clients yet'}</small></div>
                <div class="sa-stat"><i>Conversion rate</i><b>${d.conversionRate}%</b><small>of registrations paid</small></div>
                <div class="sa-stat"><i>Cost per registration</i><b>${d.costPerRegistration !== null ? peso(d.costPerRegistration) : 'None yet'}</b><small>spend ÷ registrations</small></div>
                <div class="sa-stat"><i>Acquisition cost</i><b>${d.costPerClient !== null ? peso(d.costPerClient) : 'None yet'}</b><small>spend ÷ clients</small></div>
                <div class="sa-stat"><i>Return on ad spend</i><b>${d.roas !== null ? d.roas + '×' : 'None yet'}</b><small>revenue ÷ spend</small></div>
            </div>
            ${(d.planMix && d.planMix.length) ? `
            <div class="sa-mix">
                <h3>What they bought</h3>
                ${d.planMix.map((m) => `<div class="sa-mix-row"><span>${esc(m.plan)} × ${m.count}</span><b>${peso(m.revenue)}</b></div>`).join('')}
            </div>` : ''}`;
        window.adminRise && adminRise(host);
        requestAnimationFrame(() => requestAnimationFrame(() => host.querySelectorAll('.sa-step .bar i').forEach((i) => { i.style.width = i.getAttribute('data-w') + '%'; })));
        host.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    $id('saList').addEventListener('click', async (e) => {
        const del = e.target.closest('[data-sa-del]');
        if (del) {
            e.stopPropagation();
            const ok = window.confirmAction
                ? await confirmAction({ title: 'Delete this analysis?', message: 'The registrations and sales it reads stay untouched.', confirmText: 'Delete', danger: true })
                : confirm('Delete this analysis? The registrations and sales it reads stay untouched.');
            if (!ok) return;
            try {
                await ask(U.del(del.getAttribute('data-sa-del')), { method: 'DELETE' });
                $id('saReport').hidden = true;
                load();
            } catch (err) { say(err.message, 'error'); }
            return;
        }
        const card = e.target.closest('[data-sa-open]');
        if (card) open(card.getAttribute('data-sa-open'));
    });

    $id('saCreate').addEventListener('click', async (e) => {
        const btn = e.currentTarget;
        btn.disabled = true;
        try {
            const j = await ask(U.store, { method: 'POST', body: JSON.stringify({
                name: $id('saName').value.trim(),
                dateFrom: $id('saFrom').value,
                dateTo: $id('saTo').value,
                adCost: $id('saCost').value,
                adCadence: $id('saCadence').value,
            }) });
            say(j.message);
            $id('saName').value = '';
            await load();
            open(j.data.id);
        } catch (err) { say(err.message, 'error'); }
        finally { btn.disabled = false; }
    });

    const fieldH = $id('saCost').getBoundingClientRect().height;
    if (fieldH) $id('saCost').closest('.sa-form').style.setProperty('--sa-field-h', fieldH + 'px');

    load();
    showRoom((() => { try { return new URLSearchParams(location.search).get('room') || localStorage.getItem(ROOM_KEY) || 'sales'; } catch (_) { return 'sales'; } })(), false);
})();
</script>
@endpush
