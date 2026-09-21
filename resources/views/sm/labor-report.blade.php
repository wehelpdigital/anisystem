@extends('layouts.app')

@section('title', 'Labor Report — ' . $schedule->title)
@section('page-title', 'Labor Report')
@section('page-subtitle', $schedule->title)
@section('back', route('sm.reports', ['id' => $schedule->id]))

@push('head')
@include('partials.tag-sheet-css')
<style>
    /* ===== Labor Report =============================================
       Charts paint with entity colors validated for both surfaces:
       Land Prep #d97706 · Cropping #15803d · Unanchored #2563eb.
       Everything else rides the theme vars, so dark mode is automatic. */
    .lr-wrap { max-width: 64rem; margin: 0 auto; }

    /* Hero + phase tiles */
    .lr-hero { border-radius: 1.25rem; border: 1px solid var(--color-brand-100); background: linear-gradient(115deg, var(--color-brand-50) 0%, var(--color-white) 70%); padding: 1.1rem 1.25rem; }
    .lr-hero-label { font-size: .72rem; font-weight: 800; letter-spacing: .07em; text-transform: uppercase; color: var(--color-gray-500); }
    .lr-hero-value { font-family: var(--font-sans); font-weight: 800; font-size: 1.7rem; line-height: 1.15; color: var(--color-gray-900); }
    /* The slice: whole season, a day-count range, or a date range. */
    .lr-range { display: flex; flex-wrap: wrap; gap: .4rem; }
    .lr-range button { padding: .42rem .8rem; border-radius: 999px; font-size: .8rem; font-weight: 800; border: 1.5px solid var(--color-gray-200); background: var(--color-white); color: var(--color-gray-600); cursor: pointer;
        transition: transform .28s cubic-bezier(.22,1,.36,1), border-color .2s, background .2s; }
    .lr-range button:hover { transform: translateY(-1px); }
    .lr-range button.is-on { border-color: var(--color-brand-600); background: var(--color-brand-50); color: var(--color-brand-800); }
    html.dark .lr-range button { background: #151b12; border-color: #2b3a1c; color: #d5e3c5; }
    html.dark .lr-range button.is-on { background: #22301a; border-color: #6b9f3d; color: #cfe6b8; }
    /* Who carried the season: a donut of each worker's share of the labor cost. */
    .lr-donut-wrap { display: flex; gap: 1rem; align-items: center; flex-wrap: wrap; margin-top: .8rem; }
    .lr-donut { position: relative; width: 9.5rem; height: 9.5rem; border-radius: 999px; flex: none; }
    .lr-donut::after { content: ''; position: absolute; inset: 1.9rem; border-radius: 999px; background: var(--color-white); }
    .lr-donut-c { position: absolute; inset: 0; display: flex; flex-direction: column; align-items: center; justify-content: center; z-index: 1; text-align: center; }
    .lr-donut-c b { font-size: .95rem; font-weight: 800; color: var(--color-gray-900); }
    .lr-donut-c small { font-size: .66rem; color: var(--color-gray-500); }
    .lr-donut-l { flex: 1 1 10rem; min-width: 0; display: grid; gap: .3rem; }
    .lr-donut-l span { display: flex; align-items: center; gap: .45rem; font-size: .78rem; color: var(--color-gray-700); min-width: 0; }
    .lr-donut-l i { width: .7rem; height: .7rem; border-radius: .2rem; flex: none; }
    .lr-donut-l em { font-style: normal; flex: 1 1 auto; min-width: 0; overflow-wrap: anywhere; }
    .lr-donut-l b { font-variant-numeric: tabular-nums; color: var(--color-gray-900); }
    html.dark .lr-donut::after { background: #151b12; }
    html.dark .lr-donut-c b, html.dark .lr-donut-l b { color: #e8efe1; }
    html.dark .lr-donut-l span { color: #b7c2ad; }
    .lr-tiles { display: flex; flex-wrap: wrap; gap: .6rem; margin-top: .9rem; }
    .lr-tile { flex: 1 1 10rem; min-width: 10rem; border-radius: .9rem; background: var(--color-white); border: 1px solid var(--color-gray-100); padding: .6rem .8rem; }
    .lr-tile .k { display: flex; align-items: center; gap: .4rem; font-size: .72rem; font-weight: 700; color: var(--color-gray-500); }
    .lr-tile .k i { width: .6rem; height: .6rem; border-radius: .2rem; flex-shrink: 0; }
    .lr-tile .v { font-weight: 700; font-size: 1.15rem; color: var(--color-gray-900); margin-top: .1rem; }
    .lr-tile .m { font-size: .72rem; color: var(--color-gray-400); }

    /* Tabs */
    .lr-tabs { display: flex; gap: .35rem; margin: 1rem 0 .9rem; border-bottom: 1px solid var(--color-gray-200); }
    .lr-tab { padding: .55rem .9rem; font-weight: 700; font-size: .92rem; color: var(--color-gray-500); border-bottom: 2px solid transparent; margin-bottom: -1px; cursor: pointer; }
    .lr-tab:hover { color: var(--color-gray-700); }
    .lr-tab.is-active { color: var(--color-brand-700); border-bottom-color: var(--color-brand-600); }

    /* The page's two doors: make a report, or read the shelf. */
    .lr-mtabs { display: flex; gap: .4rem; margin-bottom: 1rem; }
    .lr-mtab { flex: 1 1 0; padding: .6rem; border-radius: .8rem; font-weight: 800; font-size: .9rem;
        text-align: center; color: var(--color-gray-500); background: var(--color-white);
        border: 1px solid var(--color-gray-200); cursor: pointer; }
    .lr-mtab.is-on { background: var(--color-brand-600); border-color: var(--color-brand-600); color: #fff; }
    html.dark .lr-mtab { background: #151b12; border-color: #2b3a1c; color: #93a684; }
    html.dark .lr-mtab.is-on { background: #4a7c2a; border-color: #4a7c2a; color: #fff; }

    .lr-saved-row { display: flex; align-items: center; gap: .6rem; width: 100%; text-align: left;
        padding: .7rem .8rem; border-bottom: 1px solid var(--color-gray-100); cursor: pointer; }
    .lr-saved-row:hover { background: var(--color-brand-50); }
    .lr-saved-row b { display: block; font-size: .86rem; color: var(--color-gray-900); }
    .lr-saved-row small { color: var(--color-gray-400); font-size: .72rem; }
    html.dark .lr-saved-row { border-color: #222b1a; }
    html.dark .lr-saved-row:hover { background: #161e10; }
    html.dark .lr-saved-row b { color: #e8efe1; }
    .ar-pen { flex: none; width: 1.6rem; height: 1.6rem; border-radius: .45rem; display: inline-flex;
        align-items: center; justify-content: center; color: var(--color-gray-400); }
    .ar-pen:hover { color: var(--color-brand-700); background: var(--color-brand-50); }
    html.dark .ar-pen:hover { background: rgb(107 159 61 / .18); color: #a5c97e; }

    .lr-pane { display: none; }
    .lr-pane.is-active { display: block; animation: lrPaneIn .28s cubic-bezier(.22,1,.36,1); }
    @keyframes lrPaneIn { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: none; } }
    @media (prefers-reduced-motion: reduce) { .lr-pane.is-active { animation: none; } }
    .lr-refetch { opacity: .5; pointer-events: none; transition: opacity .15s ease; }

    .lr-card { border-radius: 1rem; border: 1px solid var(--color-gray-100); background: var(--color-white); box-shadow: var(--shadow-card); padding: 1rem 1.1rem; }
    .lr-card h3 { font-family: var(--font-heading); font-weight: 700; font-size: 1.02rem; color: var(--color-gray-900); }
    .lr-card .sub { font-size: .8rem; color: var(--color-gray-500); }

    /* Column chart (months) */
    /* Extra headroom: the top y-tick pokes above the plot, so keep the chart
       clear of the title/subtitle text above it. */
    .lr-plot { position: relative; margin-top: 2.25rem; }
    .lr-grid { position: absolute; inset: 0 0 1.6rem 3.2rem; }
    .lr-grid i { position: absolute; left: 0; right: 0; height: 1px; background: var(--color-gray-100); }
    .lr-yticks { position: absolute; left: 0; top: 0; bottom: 1.6rem; width: 3rem; }
    .lr-yticks span { position: absolute; right: .2rem; transform: translateY(-50%); font-size: .66rem; color: var(--color-gray-400); font-variant-numeric: tabular-nums; }
    .lr-cols { position: relative; margin-left: 3.2rem; display: flex; gap: 6px; height: 13rem; overflow-x: auto; overflow-y: hidden; scrollbar-width: thin; scrollbar-color: var(--color-gray-300) transparent; }
    .lr-colband { flex: 1 1 0; min-width: 30px; max-width: 64px; display: flex; flex-direction: column; cursor: default; }
    .lr-colarea { flex: 1 1 auto; display: flex; align-items: flex-end; justify-content: center; }
    .lr-colpos { position: relative; width: 100%; max-width: 24px; }
    .lr-col { width: 100%; height: 100%; border-radius: 4px 4px 0 0; background: var(--color-brand-500); min-height: 2px; }
    .lr-colband:hover .lr-col { filter: brightness(1.08); }
    .lr-colband.is-max .lr-col { background: var(--color-brand-800); }
    .lr-collabel { height: 1.6rem; display: flex; align-items: center; justify-content: center; font-size: .66rem; color: var(--color-gray-500); white-space: nowrap; }
    .lr-colcap { position: absolute; bottom: 100%; left: 50%; transform: translateX(-50%); font-size: .66rem; font-weight: 700; color: var(--color-gray-800); white-space: nowrap; padding-bottom: 2px; }
    .lr-colcap .tag { display: inline-block; margin-left: .25rem; padding: 0 .35rem; border-radius: 999px; background: var(--color-brand-100); color: var(--color-brand-800); font-size: 9.5px; font-weight: 800; text-transform: uppercase; letter-spacing: .04em; }
    .lr-metric { display: inline-flex; border: 1px solid var(--color-gray-200); border-radius: .6rem; overflow: hidden; }
    .lr-metric button { padding: .3rem .7rem; font-size: .78rem; font-weight: 700; color: var(--color-gray-500); background: var(--color-white); cursor: pointer; }
    .lr-metric button.is-on { background: var(--color-brand-600); color: #fff; }

    /* Horizontal stacked bars (workers) */
    .lr-legend { display: flex; flex-wrap: wrap; gap: .9rem; margin-top: .6rem; font-size: .78rem; font-weight: 600; color: var(--color-gray-600); }
    .lr-legend i { display: inline-block; width: .7rem; height: .7rem; border-radius: .2rem; margin-right: .3rem; vertical-align: -1px; }
    .lr-rows { margin-top: .9rem; display: flex; flex-direction: column; gap: .55rem; }
    .lr-row { display: flex; flex-direction: column; gap: .25rem; cursor: default; }
    .lr-rowhead { display: flex; align-items: baseline; justify-content: space-between; gap: .6rem; min-width: 0; }
    .lr-rowname { flex: 1 1 auto; min-width: 0; font-size: .85rem; font-weight: 600; color: var(--color-gray-800); text-align: left; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .lr-track { width: 100%; display: flex; align-items: center; height: 18px; }
    .lr-seg { height: 100%; min-width: 2px; }
    .lr-seg + .lr-seg { margin-left: 2px; }           /* surface gap */
    .lr-seg:last-of-type { border-radius: 0 4px 4px 0; } /* data end */
    .lr-row:hover .lr-seg { filter: brightness(1.08); }
    .lr-rowtotal { font-size: .82rem; font-weight: 700; color: var(--color-gray-800); white-space: nowrap; font-variant-numeric: tabular-nums; }
    .lr-toptag { margin-left: .35rem; padding: .05rem .4rem; border-radius: 999px; background: var(--color-brand-100); color: var(--color-brand-800); font-size: 9.5px; font-weight: 800; text-transform: uppercase; letter-spacing: .04em; }

    /* Shared tooltip */
    .lr-tip { position: fixed; z-index: 80; pointer-events: none; background: var(--color-white); border: 1px solid var(--color-gray-200); border-radius: .6rem; box-shadow: var(--shadow-card-lg); padding: .45rem .65rem; font-size: .78rem; min-width: 9rem; }
    .lr-tip .t { font-weight: 700; color: var(--color-gray-500); font-size: .72rem; margin-bottom: .15rem; }
    .lr-tip .r { display: flex; align-items: center; gap: .4rem; margin-top: .1rem; }
    .lr-tip .r i { width: .7rem; height: 3px; border-radius: 2px; flex-shrink: 0; }
    .lr-tip .r b { color: var(--color-gray-900); font-variant-numeric: tabular-nums; }
    .lr-tip .r span { color: var(--color-gray-500); }

    /* Breakdown cards — tables asked a phone to scroll sideways twice. */
    .lr-bcards { display: grid; gap: .5rem; margin-top: .6rem; }
    .lr-bcard { border: 1px solid var(--color-gray-100); border-radius: .8rem; background: var(--color-white);
        padding: .65rem .8rem; }
    .lr-bcard-top { display: flex; align-items: baseline; justify-content: space-between; gap: .6rem; }
    .lr-bcard-top b { font-size: .9rem; color: var(--color-gray-900); min-width: 0; overflow-wrap: anywhere; }
    .lr-bcard-amt { font-weight: 800; font-size: .95rem; color: var(--color-gray-900); white-space: nowrap; font-variant-numeric: tabular-nums; }
    .lr-bcard-meta { display: flex; flex-wrap: wrap; gap: .3rem .5rem; margin-top: .35rem; align-items: center; }
    .lr-bcard-meta .badge { font-variant-numeric: tabular-nums; }
    .lr-bphase { display: flex; flex-wrap: wrap; gap: .35rem .9rem; margin-top: .4rem; font-size: .75rem; color: var(--color-gray-600); }
    .lr-bphase i { display: inline-block; width: .6rem; height: .6rem; border-radius: .2rem; margin-right: .3rem; vertical-align: -1px; font-style: normal; }
    .lr-bzero { color: var(--color-gray-400); }
    html.dark .lr-bcard { background: #151b12; border-color: #2b3a1c; }
    html.dark .lr-bcard-top b, html.dark .lr-bcard-amt { color: #e8efe1; }

    @media print {
        header, nav, .lr-filters, .lr-tabs, .lr-mtabs, .lr-actions, #lrSavedPane, .bottom-nav, #aiFloat { display: none !important; }
        .lr-pane { display: block !important; page-break-inside: avoid; margin-bottom: 1rem; }
        .lr-card { box-shadow: none; }
    }
</style>
@endpush

@section('content')
@php
    // A view-level worker reads the shelf; generating a report writes to it.
    $lrMayGen = \App\Support\WorkerContext::canWriteModule('reports');
@endphp
@include('sm.partials.tag-picker')
@include('sm.partials.report-view')
<div class="lr-wrap">

    <div class="lr-mtabs" role="tablist">
        <button type="button" class="lr-mtab is-on" id="lrTabGen" @unless($lrMayGen) hidden @endunless>Generate</button>
        <button type="button" class="lr-mtab" id="lrTabSaved">Saved Reports</button>
    </div>

    {{-- What this report is, before the form that makes one. --}}
    <div class="rx-about">
        <span class="rx-about-e">🧾</span>
        <div class="rx-about-t">
            <b>What the Labor Report tells you</b>
            <p>Everything the season paid its people, added up from the worker assignments on your activities — half days, whole days, and each worker's rate.</p>
            <ul><li><b>Total labor expense</b> for the slice you choose — the whole season, a day-count range, or a date range</li><li><b>Busiest months</b> — where the labor cost and the activity count peak</li><li><b>Total worker earnings</b> and who carried the most work, on a donut of each worker's share</li><li><b>Breakdown</b> by worker and by activity, land preparation apart from main cropping</li></ul>
            <p class="rx-about-note">Every report you generate is saved on the Saved Reports shelf, where you can rename and describe it.</p>
        </div>
    </div>
    <div id="lrGenPane">
    {{-- The wizard: set the slice, then generate. Results come after, not under. --}}
    <div class="card p-4 mb-4 lr-filters" id="lrWizard">
        <p class="text-sm font-bold text-gray-900">What should the report cover?</p>
        <p class="text-xs text-gray-500 mt-1 mb-3">Every filter is optional — left alone, the report covers the whole season's labor. The finished report lands on the Saved shelf by itself.</p>
        @if ($schedule->workers->count())
            <div class="mb-2">
                <span class="form-label text-xs! mb-1!">Workers</span>
                {{-- A tag that opens a chooser, not chips sliding sideways —
                     the owner's call: a scroll you cannot see the end of
                     hides half the crew. --}}
                <button type="button" class="crop-tag" id="lrWorkersBtn">
                    <span class="crop-tag-e">👥</span>
                    <span class="crop-tag-t is-none" id="lrWorkersNow">All workers</span>
                    <svg class="crop-tag-c" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/></svg>
                </button>
            </div>
        @endif
        @if ($schedule->lots->count())
            <div class="mb-2">
                <span class="form-label text-xs! mb-1!">Lots</span>
                {{-- One, a few, or all -- the same tag-and-chooser as the workers. --}}
                <button type="button" class="crop-tag" id="lrLotsBtn">
                    <span class="crop-tag-e">🌾</span>
                    <span class="crop-tag-t is-none" id="lrLotsNow">All lots</span>
                    <svg class="crop-tag-c" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/></svg>
                </button>
            </div>
        @endif
        <span class="form-label text-xs! mb-1!">Which part of the season?</span>
        {{-- The slice: the whole season, a stretch of the crop's own clock
             (DAT, DAS or DAP as the chosen lots count it), or two dates. --}}
        <div class="lr-range" id="lrRange" role="radiogroup" aria-label="Which part of the season">
            <button type="button" class="is-on" data-lr-range="season" aria-checked="true">🌱 Whole season</button>
            <button type="button" data-lr-range="day" aria-checked="false">⏱️ <span data-lr-dayword>{{ $schedule->dayType }}</span> range</button>
            <button type="button" data-lr-range="date" aria-checked="false">📅 Date range</button>
        </div>
        <div class="grid grid-cols-2 gap-2 mt-2" id="lrRangeDay" hidden>
            <div>
                <label class="form-label text-xs!" for="laborDasMin"><span data-lr-dayword>{{ $schedule->dayType }}</span> from</label>
                <input type="number" id="laborDasMin" class="form-input" step="1" placeholder="e.g. 0">
            </div>
            <div>
                <label class="form-label text-xs!" for="laborDasMax"><span data-lr-dayword>{{ $schedule->dayType }}</span> to</label>
                <input type="number" id="laborDasMax" class="form-input" step="1" placeholder="e.g. 45">
            </div>
        </div>
        <div class="grid grid-cols-2 gap-2 mt-2" id="lrRangeDate" hidden>
            <div>
                <label class="form-label text-xs!" for="laborStartDate">From date</label>
                @include('partials.date-tag', ['id' => 'laborStartDate', 'empty' => 'From'])
            </div>
            <div>
                <label class="form-label text-xs!" for="laborEndDate">To date</label>
                @include('partials.date-tag', ['id' => 'laborEndDate', 'empty' => 'To'])
            </div>
        </div>
        <p id="laborFilterHint" class="text-xs text-gray-500 mt-2"></p>
        {{-- Full-width actions: whole buttons, no thumb-hunting. --}}
        <div class="grid grid-cols-1 sm:grid-cols-[2fr_1fr] gap-2 mt-3">
            <button type="button" id="laborGenerateBtn" class="btn btn-primary w-full">Generate the report</button>
            <button type="button" id="laborResetFiltersBtn" class="btn btn-white w-full">Reset</button>
        </div>
    </div>
    </div>

    {{-- The shelf: every generated labor report, newest first. --}}
    <div id="lrSavedPane" class="hidden">
        <div class="card !p-0 overflow-hidden">
            <div id="lrSavedList"></div>
            <div id="lrSavedEmpty" class="hidden rx-empty">
                <span class="rx-empty-e"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M9 12h6m-6 4h6m2 5H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l5.414 5.414a1 1 0 0 1 .293.707V19a2 2 0 0 1-2 2z"/></svg></span>
                <p class="rx-empty-t">Nothing saved yet</p>
                <p class="rx-empty-p">Generate a labor report and it lands here by itself — every one you make, newest first, ready to rename and describe.</p>
            </div>
        </div>
    </div>

    {{-- The report itself. It is lifted into the full-screen view (see
         sm.partials.report-view) the moment it exists, with its actions
         under it; this is only where it lives while it is not shown. --}}
    <div id="lrBody" hidden>

        {{-- An older save carries only its text; it is shown as it was written. --}}
        <div id="lrBodyText" class="lr-card" hidden><pre class="whitespace-pre-wrap text-sm text-gray-700" id="lrBodyPre" style="font-family:inherit"></pre></div>

        <div id="lrContent" class="hidden">
            {{-- The view, chosen from a sheet, above the figures it shows. --}}
            <div class="mb-3">
                <span class="form-label text-xs! mb-1!">Report view</span>
                <button type="button" class="crop-tag" id="lrPaneBtn">
                    <span class="crop-tag-e" id="lrPaneIcon">📊</span>
                    <span class="crop-tag-t" id="lrPaneNow">Busiest Months</span>
                    <svg class="crop-tag-c" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/></svg>
                </button>
            </div>

            {{-- Hero: the total, then the figures as tags. --}}
            <div class="lr-hero mb-4">
                <div class="lr-hero-label">Total labor expense</div>
                <div class="lr-hero-value" id="lrTotal">{{ \App\Support\Region::symbol() }}0</div>
                <div class="rx-stats" id="lrMeta"></div>
                <div class="lr-tiles" id="lrTiles"></div>
            </div>

            <div class="lr-pane is-active" id="lrPaneMonths">
                <div class="lr-card">
                    <div class="flex items-start justify-between gap-2 flex-wrap">
                        <div>
                            <h3>Labor by month</h3>
                            <p class="sub" id="lrMonthsSub"></p>
                        </div>
                        <span class="lr-metric" id="lrMetricToggle">
                            <button type="button" data-metric="cost" class="is-on">By cost</button>
                            <button type="button" data-metric="count">By activities</button>
                        </span>
                    </div>
                    <div id="lrMonthsChart"></div>
                </div>
            </div>

            <div class="lr-pane" id="lrPaneWorkers">
                <div class="lr-card">
                    <h3>Who earns the most</h3>
                    <p class="sub" id="lrWorkersSub"></p>
                    {{-- Each worker's share of the labor cost, and who carried the most work. --}}
                    <div class="lr-donut-wrap" id="lrWorkersDonut"></div>
                    <div class="lr-legend mt-4">
                        <span><i style="background:#d97706"></i>Land Preparation</span>
                        <span><i style="background:#15803d"></i>Main Cropping</span>
                        <span id="lrLegendUna" class="hidden"><i style="background:#2563eb"></i>Unanchored</span>
                    </div>
                    <div class="lr-rows" id="lrWorkersChart"></div>
                </div>
            </div>

            <div class="lr-pane" id="lrPaneBreakdown">
                <div class="lr-card" id="lrBreakdown"></div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('sheets')
@if ($schedule->lots->count())
<div class="sheet hidden" id="lrLotsSheet" style="--sheet-width:24rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title">Which lots?</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body">
        <div class="flex items-center gap-3 mb-2">
            <button type="button" id="lrLotsAll" class="text-xs font-bold text-brand-700">Select all</button>
            <span class="text-gray-300">·</span>
            <button type="button" id="lrLotsNone" class="text-xs font-bold text-brand-700">None (= every lot)</button>
        </div>
        <div class="dt-rows" id="lrLotsList">
            @foreach ($schedule->lots as $lot)
                @php $lotCounter = $lot->transplantDate ? 'DAT' : ($lot->dayType ?: $schedule->dayType); @endphp
                <button type="button" class="dt-row" data-lr-lot="{{ $lot->id }}" data-lr-counter="{{ $lotCounter }}">
                    <span class="dt-row-e">🌾</span>
                    <span class="dt-row-body"><b>{{ $lot->lotName }}</b><i>{{ \App\Support\CropStages::label($lot->crop) ?: 'No crop set' }} · counts in {{ $lotCounter }}</i></span>
                    <svg class="dt-row-tick" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                </button>
            @endforeach
        </div>
        <button type="button" class="btn btn-primary w-full mt-3" data-sheet-close>Done</button>
    </div>
</div>
@endif
@if ($schedule->workers->count())
<div class="sheet hidden" id="lrWorkersSheet" style="--sheet-width:24rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title">Which workers?</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body">
        <div class="flex items-center gap-3 mb-2">
            <button type="button" id="lrWorkersAll" class="text-xs font-bold text-brand-700">Select all</button>
            <span class="text-gray-300">·</span>
            <button type="button" id="lrWorkersNone" class="text-xs font-bold text-brand-700">None (= everyone)</button>
        </div>
        <div class="dt-rows" id="lrWorkersList">
            @foreach ($schedule->workers as $w)
                <button type="button" class="dt-row" data-lr-worker="{{ $w->id }}">
                    <span class="dt-row-e">👤</span>
                    <span class="dt-row-body"><b>{{ $w->workerName }}</b><i>{{ \App\Support\Region::money((float) $w->costPerHalfDay) }} per half day</i></span>
                    <svg class="dt-row-tick" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                </button>
            @endforeach
        </div>
        <button type="button" class="btn btn-primary w-full mt-3" data-sheet-close>Done</button>
    </div>
</div>
@endif

<div class="sheet hidden" id="lrPaneSheet" style="--sheet-width:22rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title">Which view?</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body dt-rows" id="lrPaneList">
        <button type="button" class="dt-row is-on" data-lr-pane="lrPaneMonths" data-icon="📊">
            <span class="dt-row-e">📊</span>
            <span class="dt-row-body"><b>Busiest Months</b><i>Labor cost month by month</i></span>
            <svg class="dt-row-tick" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
        </button>
        <button type="button" class="dt-row" data-lr-pane="lrPaneWorkers" data-icon="🏅">
            <span class="dt-row-e">🏅</span>
            <span class="dt-row-body"><b>Worker Earnings</b><i>Who earns the most, by phase</i></span>
            <svg class="dt-row-tick" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
        </button>
        <button type="button" class="dt-row" data-lr-pane="lrPaneBreakdown" data-icon="🧾">
            <span class="dt-row-e">🧾</span>
            <span class="dt-row-body"><b>Breakdown</b><i>Every worker and every activity, card by card</i></span>
            <svg class="dt-row-tick" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
        </button>
    </div>
</div>

{{-- The breakdown's activity chooser: one at a time, or all of them. --}}
<div class="sheet hidden" id="lrActSheet" style="--sheet-width:26rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title">Which activity?</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body dt-rows" id="lrActList"></div>
</div>

{{-- Rename a saved report, describe it, retie its tags. --}}
<div class="sheet hidden" id="lrMetaSheet" style="--sheet-width:28rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title">Edit this report</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body space-y-4">
        <div>
            <label class="form-label" for="lrMetaTitle">Name</label>
            <input type="text" id="lrMetaTitle" class="form-input" maxlength="191">
        </div>
        <div>
            <label class="form-label" for="lrMetaDesc">Description <span class="text-gray-400 font-normal">(optional)</span></label>
            <textarea id="lrMetaDesc" class="form-textarea" rows="3" maxlength="2000"></textarea>
        </div>
        <div>
            <span class="form-label">Tags</span>
            <div class="tp-mount" data-tags data-tags-kind="report" id="lrMetaTags"></div>
        </div>
    </div>
    <div class="sheet-footer">
        <button type="button" class="btn btn-ghost" data-sheet-close>Cancel</button>
        <button type="button" class="btn btn-primary" id="lrMetaSave">Save changes</button>
    </div>
</div>
@endpush

@push('scripts')
<script>
(() => {
const __init = () => {
    const $id = (i) => document.getElementById(i);
    const esc = window.escapeHtml || ((s) => String(s));
    const LABOR_URL = @json(route('sm.activities.labor') . '?scheduleId=' . $schedule->id);
    const DAY_TYPE = @json($schedule->dayType);
    const SCHEDULE_TITLE = @json($schedule->title);
    const MAY_GEN = @json($lrMayGen);
    const U = {
        snapshot: @json(route('sm.report.snapshot')),
        list: @json(route('sm.anee.list') . '?id=' . $schedule->id . '&kind=labor'),
        one: (id) => @json(route('sm.anee.one', ['id' => '__ID__'])).replace('__ID__', id),
        del: (id) => @json(route('sm.anee.delete', ['id' => '__ID__'])).replace('__ID__', id),
        meta: @json(route('sm.anee.meta')),
        ai: @json(route('ai.index')),
    };
    const MONTH_SHORT = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    const PHASE = { pre: '#d97706', crop: '#15803d', una: '#2563eb' };
    const fmtPeso = (n) => ((window.ANEE_REGION || {}).symbol || '₱') + Number(n || 0).toLocaleString(((window.ANEE_REGION || {}).locale || 'en-PH'), { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const fmtPeso0 = (n) => ((window.ANEE_REGION || {}).symbol || '₱') + Math.round(Number(n || 0)).toLocaleString(((window.ANEE_REGION || {}).locale || 'en-PH'));
    const parseD = (iso) => { const [y, m, d] = String(iso).slice(0, 10).split('-').map(Number); return (y && m && d) ? new Date(y, m - 1, d, 12) : null; };

    let DATA = null;
    let METRIC = 'cost';
    // What the report area is showing: a fresh generate, or a shelf row.
    let MODE = 'fresh';
    let SAVED = { id: null, mine: true };
    let LR_ROWS = [];
    let META_ID = null;

    /* ---------------- filters (same contract as before) ---------------- */
    /* The chosen workers, held as a set the tag and the sheet both read.
       Empty means everyone — a filter, not a requirement. */
    const WORKER_SEL = new Set();
    function sayWorkersTag() {
        const t = $id('lrWorkersNow');
        if (!t) return;
        const rows = document.querySelectorAll('#lrWorkersList [data-lr-worker]');
        if (!WORKER_SEL.size) { t.textContent = 'All workers'; t.classList.add('is-none'); }
        else if (WORKER_SEL.size === 1) {
            const row = document.querySelector(`#lrWorkersList [data-lr-worker="${[...WORKER_SEL][0]}"] b`);
            t.textContent = row ? row.textContent : '1 worker';
            t.classList.remove('is-none');
        } else { t.textContent = `${WORKER_SEL.size} of ${rows.length} workers`; t.classList.remove('is-none'); }
    }
    $id('lrWorkersBtn')?.addEventListener('click', () => openSheet('lrWorkersSheet'));
    $id('lrWorkersList')?.addEventListener('click', (e) => {
        const row = e.target.closest('[data-lr-worker]');
        if (!row) return;
        const id = Number(row.getAttribute('data-lr-worker'));
        if (WORKER_SEL.has(id)) WORKER_SEL.delete(id); else WORKER_SEL.add(id);
        row.classList.toggle('is-on', WORKER_SEL.has(id));
        sayWorkersTag(); updateHint();
    });
    $id('lrWorkersAll')?.addEventListener('click', () => {
        document.querySelectorAll('#lrWorkersList [data-lr-worker]').forEach((r) => {
            WORKER_SEL.add(Number(r.getAttribute('data-lr-worker')));
            r.classList.add('is-on');
        });
        sayWorkersTag(); updateHint();
    });
    $id('lrWorkersNone')?.addEventListener('click', () => {
        WORKER_SEL.clear();
        document.querySelectorAll('#lrWorkersList [data-lr-worker]').forEach((r) => r.classList.remove('is-on'));
        sayWorkersTag(); updateHint();
    });

    /* The lots, held the way the workers are: empty means every lot. */
    const LOT_SEL = new Set();
    function sayLotsTag() {
        const t = $id('lrLotsNow');
        if (!t) return;
        const rows = document.querySelectorAll('#lrLotsList [data-lr-lot]');
        if (!LOT_SEL.size) { t.textContent = 'All lots'; t.classList.add('is-none'); }
        else if (LOT_SEL.size === 1) {
            const row = document.querySelector(`#lrLotsList [data-lr-lot="${[...LOT_SEL][0]}"] b`);
            t.textContent = row ? row.textContent : '1 lot';
            t.classList.remove('is-none');
        } else { t.textContent = `${LOT_SEL.size} of ${rows.length} lots`; t.classList.remove('is-none'); }
        sayDayWord();
    }
    /* The crop's own clock, as the chosen lots count it: one word when they
       agree, "Day" when they do not, the season's own when none is chosen. */
    function dayWord() {
        const rows = [...document.querySelectorAll('#lrLotsList [data-lr-lot]')];
        const chosen = rows.filter((r) => !LOT_SEL.size || LOT_SEL.has(Number(r.getAttribute('data-lr-lot'))));
        const words = [...new Set(chosen.map((r) => (r.getAttribute('data-lr-counter') || '').trim()).filter(Boolean))];
        if (!words.length) return DAY_TYPE || 'Day';
        return words.length === 1 ? words[0] : 'Day';
    }
    function sayDayWord() { document.querySelectorAll('[data-lr-dayword]').forEach((el) => { el.textContent = dayWord(); }); }
    $id('lrLotsBtn')?.addEventListener('click', () => openSheet('lrLotsSheet'));
    $id('lrLotsList')?.addEventListener('click', (e) => {
        const row = e.target.closest('[data-lr-lot]');
        if (!row) return;
        const id = Number(row.getAttribute('data-lr-lot'));
        if (LOT_SEL.has(id)) LOT_SEL.delete(id); else LOT_SEL.add(id);
        row.classList.toggle('is-on', LOT_SEL.has(id));
        sayLotsTag(); updateHint();
    });
    $id('lrLotsAll')?.addEventListener('click', () => {
        document.querySelectorAll('#lrLotsList [data-lr-lot]').forEach((r) => { LOT_SEL.add(Number(r.getAttribute('data-lr-lot'))); r.classList.add('is-on'); });
        sayLotsTag(); updateHint();
    });
    $id('lrLotsNone')?.addEventListener('click', () => {
        LOT_SEL.clear();
        document.querySelectorAll('#lrLotsList [data-lr-lot]').forEach((r) => r.classList.remove('is-on'));
        sayLotsTag(); updateHint();
    });
    /* Which part of the season: the range inputs of the other two modes
       are cleared when a mode is left, so a stale date cannot ride along. */
    let RANGE = 'season';
    $id('lrRange')?.addEventListener('click', (e) => {
        const b = e.target.closest('[data-lr-range]');
        if (!b) return;
        RANGE = b.getAttribute('data-lr-range');
        document.querySelectorAll('#lrRange [data-lr-range]').forEach((x) => { const on = x === b; x.classList.toggle('is-on', on); x.setAttribute('aria-checked', on ? 'true' : 'false'); });
        $id('lrRangeDay').hidden = RANGE !== 'day';
        $id('lrRangeDate').hidden = RANGE !== 'date';
        if (RANGE !== 'day') ['laborDasMin', 'laborDasMax'].forEach((i) => { if ($id(i)) $id(i).value = ''; });
        if (RANGE !== 'date') ['laborStartDate', 'laborEndDate'].forEach((i) => { if ($id(i)) { $id(i).value = ''; $id(i).dispatchEvent(new Event('change')); } });
        updateHint();
    });
    sayDayWord();
    setTimeout(() => updateHint(), 0);

    function filterPayload() {
        const p = {};
        const w = [...WORKER_SEL];
        if (w.length) p.workerIds = w;
        const l = [...LOT_SEL];
        if (l.length) p.lotIds = l;
        if (RANGE === 'day') {
            const dmin = ($id('laborDasMin')?.value || '').trim();
            const dmax = ($id('laborDasMax')?.value || '').trim();
            if (dmin !== '' && !isNaN(parseInt(dmin, 10))) p.dasMin = parseInt(dmin, 10);
            if (dmax !== '' && !isNaN(parseInt(dmax, 10))) p.dasMax = parseInt(dmax, 10);
        }
        if (RANGE === 'date') {
            if ($id('laborStartDate')?.value) p.startDate = $id('laborStartDate').value;
            if ($id('laborEndDate')?.value) p.endDate = $id('laborEndDate').value;
        }
        return p;
    }
    function queryString() {
        const f = filterPayload();
        const parts = [];
        (f.workerIds || []).forEach((id) => parts.push(`workerIds[]=${id}`));
        (f.lotIds || []).forEach((id) => parts.push(`lotIds[]=${id}`));
        // A DAT window is counted from the transplant on the lots that had one.
        if ((f.dasMin !== undefined || f.dasMax !== undefined) && dayWord() === 'DAT') parts.push('dayAnchor=transplant');
        if (f.dasMin !== undefined) parts.push(`dasMin=${f.dasMin}`);
        if (f.dasMax !== undefined) parts.push(`dasMax=${f.dasMax}`);
        if (f.startDate) parts.push(`startDate=${encodeURIComponent(f.startDate)}`);
        if (f.endDate) parts.push(`endDate=${encodeURIComponent(f.endDate)}`);
        return parts.length ? '&' + parts.join('&') : '';
    }
    function updateHint() {
        const f = filterPayload();
        const parts = [];
        if (f.workerIds) parts.push(`${f.workerIds.length} ${f.workerIds.length === 1 ? 'worker' : 'workers'}`);
        if (f.lotIds) parts.push(`${f.lotIds.length} ${f.lotIds.length === 1 ? 'lot' : 'lots'}`);
        if (f.dasMin !== undefined || f.dasMax !== undefined) parts.push(`${dayWord()} ${f.dasMin ?? '−∞'} to ${f.dasMax ?? '+∞'}`);
        if (f.startDate || f.endDate) parts.push(`${f.startDate || '…'} to ${f.endDate || '…'}`);
        $id('laborFilterHint').textContent = parts.length ? `Covers: ${parts.join(' · ')}` : 'Covers the whole season, every lot and every worker.';
    }

    /* Generate: compute the slice, save it to the shelf, then show it.
     * The wizard steps aside the way the protocol page's does — the report
     * is the page now, with its own actions row. */
    async function generate() {
        updateHint();
        const loader = screenLoader('Calculating the labor report…');
        try {
            const res = await api(LABOR_URL + queryString());
            DATA = res.data;
            renderAll();
            // The shelf copy: the text Anee reads plus the dataset that lets
            // the Saved tab redraw this exact report later.
            const filters = filterPayload();
            let savedNote = '';
            try {
                const snap = await api(U.snapshot, { method: 'POST', body: {
                    scheduleId: @json($schedule->id),
                    kind: 'labor',
                    title: 'Labor Report — ' + SCHEDULE_TITLE + (Object.keys(filters).length ? ' (filtered)' : ''),
                    body: buildText(),
                    params: filters,
                    report: DATA,
                } });
                SAVED = { id: snap.data.id, mine: true, title: 'Labor Report — ' + SCHEDULE_TITLE + (Object.keys(filters).length ? ' (filtered)' : ''), description: '' };
                savedNote = ' It is saved on the Saved Reports shelf.';
            } catch (err) {
                SAVED = { id: null, mine: true };
                toast(err.message, 'error');
            }
            showReport('fresh');
            toast('Labor report generated.' + savedNote);
        } catch (err) {
            toast(err.message, 'error');
        } finally {
            loader.hide();
        }
    }

    /* One report, two ways in -- and one screen: the full-screen view,
       with its actions under the report. A fresh one, once closed, leaves
       the farmer on the Saved shelf where it now sits. */
    const FACE = @json(\App\Models\AiSetting::current()->faceUrl());
    const ANEE = @json(\App\Models\AiSetting::current()->assistantName);
    function showReport(mode) {
        MODE = mode;
        const actions = [];
        if (SAVED.id) actions.push({ label: 'Ask ' + ANEE, face: FACE, kind: 'primary', href: U.ai + '?freport=' + SAVED.id });
        if (SAVED.id && SAVED.mine && MAY_GEN) actions.push({ label: 'Name & description', icon: 'pen', onClick: () => openMetaFor(SAVED) });
        if (SAVED.id && SAVED.mine && MAY_GEN) actions.push({ label: 'Delete', icon: 'trash', kind: 'danger', onClick: deleteShown });
        actions.push({ label: mode === 'fresh' ? 'New report' : 'Close', icon: mode === 'fresh' ? 'plus' : 'close', onClick: () => window.reportView.close() });
        window.reportView.open({
            title: SAVED.title || ('Labor Report — ' + SCHEDULE_TITLE),
            node: $id('lrBody'),
            actions,
            onClose: () => { showTab(mode !== 'fresh' ? false : false); },
        });
    }
    /* The pen, from inside the view: the same sheet the shelf's pen opens. */
    function openMetaFor(saved) {
        META_ID = saved.id;
        $id('lrMetaTitle').value = saved.title || '';
        $id('lrMetaDesc').value = saved.description || '';
        const mount = $id('lrMetaTags');
        if (window.smTags && mount) { window.smTags.mount(mount); window.smTags.load(mount, 'report', saved.id); }
        openSheet('lrMetaSheet');
    }
    async function deleteShown() {
        if (!SAVED.id) return;
        const ok = window.confirmAction ? await window.confirmAction({ title: 'Delete this saved report?', message: 'It leaves the shelf. The season\'s numbers stay — a new report can always be generated.', confirmText: 'Delete' }) : confirm('Delete this report?');
        if (!ok) return;
        try {
            await api(U.del(SAVED.id), { method: 'DELETE' });
            toast('Report removed.');
            SAVED = { id: null, mine: true };
            window.reportView.close();
        } catch (err) { toast(err.message, 'error'); }
    }

    /* ---------------- shared tooltip ---------------- */
    let tip = null;
    function showTip(x, y, title, rows) {
        if (!tip) { tip = document.createElement('div'); tip.className = 'lr-tip'; document.body.appendChild(tip); }
        tip.textContent = '';
        const t = document.createElement('div'); t.className = 't'; t.textContent = title; tip.appendChild(t);
        rows.forEach(([color, value, label]) => {
            const r = document.createElement('div'); r.className = 'r';
            if (color) { const i = document.createElement('i'); i.style.background = color; r.appendChild(i); }
            const b = document.createElement('b'); b.textContent = value; r.appendChild(b);
            const s = document.createElement('span'); s.textContent = label; r.appendChild(s);
            tip.appendChild(r);
        });
        tip.style.display = 'block';
        const rect = tip.getBoundingClientRect();
        tip.style.left = Math.min(x + 14, window.innerWidth - rect.width - 8) + 'px';
        tip.style.top = Math.max(8, Math.min(y - rect.height - 10, window.innerHeight - rect.height - 8)) + 'px';
    }
    function hideTip() { if (tip) tip.style.display = 'none'; }
    document.addEventListener('scroll', hideTip, true);

    /* ---------------- hero ---------------- */
    function renderHero() {
        const d = DATA, t = d.totals || {};
        $id('lrTotal').textContent = fmtPeso(d.grandTotal);
        const stat = (n, word) => `<span class="rx-stat"><b>${esc(String(n))}</b>${esc(word)}</span>`;
        $id('lrMeta').innerHTML = [
            stat(d.totalActivities, d.totalActivities === 1 ? 'activity' : 'activities'),
            stat(t.totalAssignments || 0, 'worker assignments'),
            stat(t.halfDays || 0, 'half days'),
            stat(t.wholeDays || 0, 'whole days'),
            (t.naCount || 0) > 0 ? stat(t.naCount, 'N/A') : '',
        ].join('');
        const ph = d.phases || {};
        const tiles = [
            ['Land Preparation', `${DAY_TYPE} < 0`, ph.preDayZero, PHASE.pre],
            ['Main Cropping', `${DAY_TYPE} 0 onwards`, ph.cropping, PHASE.crop],
        ];
        if ((ph.unanchored || {}).count > 0) tiles.push(['Unanchored', `no ${DAY_TYPE} 0`, ph.unanchored, PHASE.una]);
        $id('lrTiles').innerHTML = tiles.map(([label, sub, p, color]) => {
            p = p || { count: 0, cost: 0 };
            const pct = d.grandTotal > 0 ? Math.round((p.cost / d.grandTotal) * 100) : 0;
            return `<div class="lr-tile">
                <div class="k"><i style="background:${color}"></i>${esc(label)} <span class="font-normal">· ${esc(sub)}</span></div>
                <div class="v">${esc(fmtPeso(p.cost))}</div>
                <div class="m">${p.count} ${p.count === 1 ? 'activity' : 'activities'} · ${pct}% of total</div>
            </div>`;
        }).join('');
    }

    /* ---------------- months (column chart) ---------------- */
    function monthlySeries() {
        const map = new Map();
        const bucket = (k) => { if (!map.has(k)) map.set(k, { cost: 0, count: 0 }); return map.get(k); };
        (DATA.perActivity || []).forEach((a) => {
            const s = a.targetDate ? parseD(a.targetDate) : null;
            if (!s) return;
            const e = (a.targetEndDate && parseD(a.targetEndDate) > s) ? parseD(a.targetEndDate) : s;
            const days = Math.max(1, Math.round((e - s) / 86400000) + 1);
            const perDay = (a.cost || 0) / days;
            bucket(`${s.getFullYear()}-${s.getMonth()}`).count += 1;
            const cur = new Date(s);
            for (let i = 0; i < days; i++) { bucket(`${cur.getFullYear()}-${cur.getMonth()}`).cost += perDay; cur.setDate(cur.getDate() + 1); }
        });
        if (!map.size) return [];
        const keys = [...map.keys()].map((k) => k.split('-').map(Number));
        keys.sort((a, b) => (a[0] - b[0]) || (a[1] - b[1]));
        const [firstY, firstM] = keys[0];
        const [lastY, lastM] = keys[keys.length - 1];
        const out = [];
        for (let y = firstY, m = firstM; y < lastY || (y === lastY && m <= lastM); m === 11 ? (m = 0, y++) : m++) {
            const v = map.get(`${y}-${m}`) || { cost: 0, count: 0 };
            out.push({ y, m, label: `${MONTH_SHORT[m]} '${String(y).slice(2)}`, full: `${MONTH_SHORT[m]} ${y}`, ...v });
        }
        return out;
    }
    const niceMax = (v) => {
        if (v <= 0) return 1;
        const pow = Math.pow(10, Math.floor(Math.log10(v)));
        for (const s of [1, 2, 2.5, 5, 10]) if (v <= s * pow) return s * pow;
        return 10 * pow;
    };

    function renderMonths() {
        const series = monthlySeries();
        const host = $id('lrMonthsChart');
        if (!series.length) { host.innerHTML = '<p class="text-sm text-gray-400 py-8 text-center">No dated activities to plot.</p>'; $id('lrMonthsSub').textContent = ''; return; }
        const val = (s) => METRIC === 'cost' ? s.cost : s.count;
        const max = Math.max(...series.map(val));
        const top = niceMax(max);
        const busiest = series.reduce((a, b) => (val(b) > val(a) ? b : a), series[0]);
        $id('lrMonthsSub').textContent = max > 0
            ? `${busiest.full} is the busiest month ${METRIC === 'cost' ? `at ${fmtPeso0(busiest.cost)} of labor` : `with ${busiest.count} activities`}.`
            : 'Nothing scheduled in this slice.';

        const ticks = [0, .25, .5, .75, 1].map((f) => f * top);
        const fmtTick = (v) => METRIC === 'cost' ? ((window.ANEE_REGION || {}).symbol || '₱') + (v >= 1000 ? (v / 1000).toLocaleString() + 'k' : v.toLocaleString()) : String(v);
        host.innerHTML = `<div class="lr-plot">
            <div class="lr-grid">${ticks.slice(1).map((v) => `<i style="bottom:${(v / top) * 100}%"></i>`).join('')}</div>
            <div class="lr-yticks">${ticks.map((v) => `<span style="bottom:${(v / top) * 100}%">${fmtTick(v)}</span>`).join('')}</div>
            <div class="lr-cols">${series.map((s, i) => `
                <div class="lr-colband${s === busiest && val(s) > 0 ? ' is-max' : ''}" data-i="${i}">
                    <div class="lr-colarea">
                        <div class="lr-colpos" style="height:${top > 0 ? Math.max((val(s) / top) * 100, 1) : 1}%">
                            ${s === busiest && val(s) > 0 ? `<span class="lr-colcap">${METRIC === 'cost' ? fmtPeso0(s.cost) : s.count}<span class="tag">Busiest</span></span>` : ''}
                            <div class="lr-col"></div>
                        </div>
                    </div>
                    <span class="lr-collabel">${series.length > 14 && i % 2 ? '' : esc(s.label)}</span>
                </div>`).join('')}</div>
        </div>`;
        host.querySelectorAll('.lr-colband').forEach((band) => {
            const s = series[Number(band.dataset.i)];
            band.addEventListener('pointermove', (e) => showTip(e.clientX, e.clientY, s.full, [
                [PHASE.crop, fmtPeso(s.cost), 'labor cost'],
                [null, String(s.count), s.count === 1 ? 'activity starts' : 'activities start'],
            ]));
            band.addEventListener('pointerleave', hideTip);
        });
    }

    $id('lrMetricToggle').addEventListener('click', (e) => {
        const b = e.target.closest('button[data-metric]');
        if (!b || b.dataset.metric === METRIC) return;
        METRIC = b.dataset.metric;
        $id('lrMetricToggle').querySelectorAll('button').forEach((x) => x.classList.toggle('is-on', x === b));
        renderMonths();
    });

    /* ---------------- workers (stacked horizontal bars) ---------------- */
    function renderWorkers() {
        const host = $id('lrWorkersChart');
        const workers = [...(DATA.perWorker || [])].sort((a, b) => (b.total || 0) - (a.total || 0));
        const showUna = workers.some((w) => (w.unanchoredTotal || 0) > 0);
        $id('lrLegendUna').classList.toggle('hidden', !showUna);
        if (!workers.length) { host.innerHTML = '<p class="text-sm text-gray-400 py-8 text-center">No workers have been assigned yet.</p>'; $id('lrWorkersSub').textContent = ''; return; }
        const max = Math.max(...workers.map((w) => w.total || 0), 1);
        // Who carried the most work: half days count one, whole days two.
        const units = (w) => (w.halfDays || 0) + 2 * (w.wholeDays || 0);
        const busiest = workers.reduce((a, b) => (units(b) > units(a) ? b : a), workers[0]);
        $id('lrWorkersSub').textContent = (workers[0].total || 0) > 0
            ? `${workers[0].name} earns the most at ${fmtPeso0(workers[0].total)}` + (units(busiest) > 0 ? `; ${busiest.name} carries the most work, ${units(busiest)} half-day${units(busiest) === 1 ? '' : 's'} of it.` : '.')
            : 'No paid assignments in this slice.';
        renderDonut(workers, units);
        host.innerHTML = workers.map((w, i) => {
            const segs = [
                [PHASE.pre, w.preDayZeroTotal || 0, 'Land Preparation'],
                [PHASE.crop, w.croppingTotal || 0, 'Main Cropping'],
                [PHASE.una, w.unanchoredTotal || 0, 'Unanchored'],
            ].filter(([, v]) => v > 0);
            const width = ((w.total || 0) / max) * 100;
            return `<div class="lr-row" data-i="${i}">
                <div class="lr-rowhead">
                    <span class="lr-rowname" title="${esc(w.name)}">${esc(w.name)}</span>
                    <span class="lr-rowtotal">${esc(fmtPeso0(w.total))}${i === 0 && (w.total || 0) > 0 ? '<span class="lr-toptag">Top earner</span>' : ''}</span>
                </div>
                <span class="lr-track"><span style="display:flex;width:${width}%;height:100%">${segs.map(([c, v]) => `<span class="lr-seg" style="background:${c};flex:${v} ${v} 0"></span>`).join('')}</span></span>
            </div>`;
        }).join('');
        host.querySelectorAll('.lr-row').forEach((row) => {
            const w = workers[Number(row.dataset.i)];
            row.addEventListener('pointermove', (e) => {
                const rows = [
                    [PHASE.pre, fmtPeso(w.preDayZeroTotal || 0), 'Land Preparation'],
                    [PHASE.crop, fmtPeso(w.croppingTotal || 0), 'Main Cropping'],
                ];
                if ((w.unanchoredTotal || 0) > 0) rows.push([PHASE.una, fmtPeso(w.unanchoredTotal), 'Unanchored']);
                rows.push([null, `${w.halfDays}H / ${w.wholeDays}W${w.naCount ? ` / ${w.naCount}N` : ''}`, 'assignments']);
                showTip(e.clientX, e.clientY, w.name, rows);
            });
            row.addEventListener('pointerleave', hideTip);
        });
    }

    /* ---------------- the donut: each worker's share of the labor cost ----------------
     * A conic gradient on a ring, one slice per worker in earning order,
     * the small ones gathered as "others" so the legend stays readable. */
    const DONUT_COLORS = ['#15803d', '#d97706', '#2563eb', '#7c3aed', '#db2777', '#0891b2', '#65a30d', '#ea580c'];
    function renderDonut(workers, units) {
        const host = $id('lrWorkersDonut');
        if (!host) return;
        const total = workers.reduce((n, w) => n + (w.total || 0), 0);
        if (total <= 0) { host.innerHTML = ''; return; }
        const top = workers.slice(0, 7);
        const rest = workers.slice(7);
        const slices = top.map((w, i) => ({ name: w.name, v: w.total || 0, c: DONUT_COLORS[i % DONUT_COLORS.length], u: units(w) }));
        if (rest.length) slices.push({ name: `${rest.length} others`, v: rest.reduce((n, w) => n + (w.total || 0), 0), c: '#9ca3af', u: rest.reduce((n, w) => n + units(w), 0) });
        let at = 0;
        const stops = slices.map((sl) => { const from = at; at += (sl.v / total) * 100; return `${sl.c} ${from.toFixed(2)}% ${at.toFixed(2)}%`; });
        const busiest = slices.reduce((a, b) => (b.u > a.u ? b : a), slices[0]);
        host.innerHTML = `
            <div class="lr-donut" style="background: conic-gradient(${stops.join(', ')})" role="img" aria-label="Share of labor cost per worker">
                <div class="lr-donut-c"><b>${esc(fmtPeso0(total))}</b><small>${slices.length} ${slices.length === 1 ? 'worker' : 'workers'}</small></div>
            </div>
            <div class="lr-donut-l">${slices.map((sl) => `<span><i style="background:${sl.c}"></i><em title="${esc(sl.name)}">${esc(sl.name)}${sl === busiest && sl.u > 0 ? ' · busiest' : ''}</em><b>${Math.round((sl.v / total) * 100)}%</b></span>`).join('')}</div>`;
    }

    /* ---------------- breakdown cards ----------------
     * Tables asked a phone to scroll sideways twice; a card says one
     * worker or one activity whole, and stacks however narrow it gets. */
    function renderBreakdown() {
        const d = DATA;
        const showUna = ((d.phases || {}).unanchored || {}).count > 0;

        const workerCards = (d.perWorker || []).map((w) => `
            <div class="lr-bcard">
                <div class="lr-bcard-top"><b>${esc(w.name)}</b><span class="lr-bcard-amt">${fmtPeso(w.total)}</span></div>
                <div class="lr-bcard-meta">
                    <span class="badge badge-gray">${fmtPeso(w.costPerHalfDay)} / half-day</span>
                    <span class="badge badge-gray">${w.halfDays}H / ${w.wholeDays}W${w.naCount > 0 ? ` / ${w.naCount}N` : ''}</span>
                </div>
                <div class="lr-bphase">
                    <span><i style="background:${PHASE.pre}"></i>Land Prep ${fmtPeso(w.preDayZeroTotal || 0)}</span>
                    <span><i style="background:${PHASE.crop}"></i>Cropping ${fmtPeso(w.croppingTotal || 0)}</span>
                    ${showUna ? `<span><i style="background:${PHASE.una}"></i>Unanchored ${fmtPeso(w.unanchoredTotal || 0)}</span>` : ''}
                </div>
            </div>`).join('')
            || '<p class="text-sm text-gray-400 py-4 text-center">No workers assigned yet.</p>';

        const card = (a) => {
            const s = a.targetDate ? parseD(a.targetDate) : null;
            const e = a.targetEndDate ? parseD(a.targetEndDate) : null;
            let pretty = 'No date';
            if (s && e && e > s) pretty = `${MONTH_SHORT[s.getMonth()]} ${s.getDate()} → ${MONTH_SHORT[e.getMonth()]} ${e.getDate()}, ${e.getFullYear()}`;
            else if (s) pretty = `${MONTH_SHORT[s.getMonth()]} ${s.getDate()}, ${s.getFullYear()}`;
            const dasLbl = (a.das === null || a.das === undefined) ? null : `${DAY_TYPE}${a.das >= 0 ? '+' : ''}${a.das}`;
            const tr = a.timeRequired === 'whole' ? 'Whole day' : (a.timeRequired === 'half' ? 'Half day' : 'N/A');
            return `<div class="lr-bcard${a.cost === 0 ? ' lr-bzero' : ''}">
                <div class="lr-bcard-top"><b>${esc(a.activityTitle)}</b><span class="lr-bcard-amt">${fmtPeso(a.cost)}</span></div>
                <div class="lr-bcard-meta">
                    <span class="badge badge-gray">${esc(pretty)}</span>
                    ${(a.rangeDays || 1) > 1 ? `<span class="badge badge-yellow">${a.rangeDays} days</span>` : ''}
                    ${dasLbl ? `<span class="badge badge-gray">${esc(dasLbl)}</span>` : ''}
                    <span class="badge badge-gray">${tr}</span>
                    <span class="badge badge-gray">${a.workerCount} ${a.workerCount === 1 ? 'worker' : 'workers'}</span>
                </div>
            </div>`;
        };
        const section = (items, label, color, subtotal) => !items.length ? '' : `
            <div class="mt-4 pl-3" style="border-left:3px solid ${color}">
                <div class="flex items-center justify-between flex-wrap gap-2">
                    <p class="font-bold text-sm mb-0" style="color:${color}">${esc(label)} <span class="text-gray-400 font-normal">· ${items.length} ${items.length === 1 ? 'activity' : 'activities'}</span></p>
                    <p class="text-sm mb-0">Subtotal: <strong style="color:${color}">${fmtPeso(subtotal)}</strong></p>
                </div>
                <div class="lr-bcards">${items.map(card).join('')}</div>
            </div>`;
        const ph = d.phases || {};
        // One activity, or all of them: the chooser's pick narrows the list
        // and its phase's subtotal says that one activity's cost.
        const all = d.perActivity || [];
        const shown = BREAK_ACT === null ? all : all.filter((a) => String(a.id) === String(BREAK_ACT));
        const one = BREAK_ACT !== null && shown.length ? shown[0] : null;
        const sub = (key, fallback) => one ? (one.cost || 0) : fallback;
        $id('lrBreakdown').innerHTML = `
            <h3>By worker</h3>
            <div class="lr-bcards">${workerCards}</div>
            <h3 class="mt-5">By activity</h3>
            <div class="mb-3">
                <button type="button" class="crop-tag" id="lrActBtn">
                    <span class="crop-tag-e">🧾</span>
                    <span class="crop-tag-t${one ? '' : ' is-none'}" id="lrActNow">${one ? esc(one.activityTitle) : 'All activities'}</span>
                    <svg class="crop-tag-c" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/></svg>
                </button>
            </div>
            ${section(shown.filter((a) => a.phase === 'preDayZero'), 'Land Preparation', PHASE.pre, sub('preDayZero', (ph.preDayZero || {}).cost || 0))}
            ${section(shown.filter((a) => a.phase === 'cropping'), 'Main Cropping', PHASE.crop, sub('cropping', (ph.cropping || {}).cost || 0))}
            ${section(shown.filter((a) => a.phase === 'unanchored'), 'Unanchored', PHASE.una, sub('unanchored', (ph.unanchored || {}).cost || 0))}
            ${BREAK_ACT !== null && !shown.length ? '<p class="text-sm text-gray-400 py-4 text-center">That activity is not in this slice.</p>' : ''}`;
        // The chooser's rows: every activity in the slice, newest phase first as the list is.
        const list = $id('lrActList');
        if (list) {
            const when = (a) => { const s = a.targetDate ? parseD(a.targetDate) : null; return s ? `${MONTH_SHORT[s.getMonth()]} ${s.getDate()}, ${s.getFullYear()}` : 'No date'; };
            const phaseWord = { preDayZero: 'Land Preparation', cropping: 'Main Cropping', unanchored: 'Unanchored' };
            list.innerHTML = `
                <button type="button" class="dt-row${BREAK_ACT === null ? ' is-on' : ''}" data-lr-act="">
                    <span class="dt-row-e">🧾</span>
                    <span class="dt-row-body"><b>All activities</b><i>${all.length} in this slice</i></span>
                    <svg class="dt-row-tick" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                </button>` + all.map((a) => `
                <button type="button" class="dt-row${String(a.id) === String(BREAK_ACT) ? ' is-on' : ''}" data-lr-act="${esc(String(a.id))}">
                    <span class="dt-row-e">${a.phase === 'preDayZero' ? '🚜' : (a.phase === 'cropping' ? '🌱' : '📍')}</span>
                    <span class="dt-row-body"><b>${esc(a.activityTitle)}</b><i>${esc(when(a))} · ${esc(phaseWord[a.phase] || '')} · ${esc(fmtPeso0(a.cost))}</i></span>
                    <svg class="dt-row-tick" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                </button>`).join('');
        }
    }
    let BREAK_ACT = null;
    document.addEventListener('click', (e) => {
        if (e.target.closest('#lrActBtn')) { openSheet('lrActSheet'); return; }
        const row = e.target.closest('#lrActList [data-lr-act]');
        if (!row) return;
        const v = row.getAttribute('data-lr-act');
        BREAK_ACT = v === '' ? null : v;
        closeSheet('lrActSheet');
        if (DATA) renderBreakdown();
    });

    function renderAll() {
        BREAK_ACT = null;
        $id('lrContent').classList.remove('hidden');
        $id('lrBodyText').hidden = true;
        if (!DATA || DATA.totalActivities === 0) {
            $id('lrTotal').textContent = fmtPeso(0);
            $id('lrMeta').textContent = 'No activities matched the current filters.';
            $id('lrTiles').innerHTML = '';
            $id('lrMonthsChart').innerHTML = '<p class="text-sm text-gray-400 py-8 text-center">Nothing to plot yet.</p>';
            $id('lrWorkersChart').innerHTML = '';
            $id('lrBreakdown').innerHTML = '<p class="text-sm text-gray-400 py-6 text-center">No activities matched.</p>';
            return;
        }
        renderHero(); renderMonths(); renderWorkers(); renderBreakdown();
    }

    /* ---------------- the view chooser ---------------- */
    $id('lrPaneBtn')?.addEventListener('click', () => openSheet('lrPaneSheet'));
    $id('lrPaneList')?.addEventListener('click', (e) => {
        const row = e.target.closest('[data-lr-pane]');
        if (!row) return;
        document.querySelectorAll('#lrPaneList [data-lr-pane]').forEach((r) => r.classList.toggle('is-on', r === row));
        document.querySelectorAll('.lr-pane').forEach((p) => p.classList.toggle('is-active', p.id === row.dataset.lrPane));
        $id('lrPaneNow').textContent = row.querySelector('b').textContent;
        $id('lrPaneIcon').textContent = row.dataset.icon || '📊';
        closeSheet('lrPaneSheet');
    });

    /* ---------------- filter wiring ---------------- */
    $id('laborGenerateBtn')?.addEventListener('click', generate);
    $id('laborResetFiltersBtn')?.addEventListener('click', () => {
        WORKER_SEL.clear();
        document.querySelectorAll('#lrWorkersList [data-lr-worker]').forEach((r) => r.classList.remove('is-on'));
        sayWorkersTag();
        LOT_SEL.clear();
        document.querySelectorAll('#lrLotsList [data-lr-lot]').forEach((r) => r.classList.remove('is-on'));
        sayLotsTag();
        ['laborDasMin', 'laborDasMax', 'laborStartDate', 'laborEndDate'].forEach((i) => { if ($id(i)) $id(i).value = ''; });
        document.querySelector('#lrRange [data-lr-range="season"]')?.click();
        updateHint();
    });
    $id('laborStartDate')?.addEventListener('change', updateHint);
    $id('laborEndDate')?.addEventListener('change', updateHint);

    /* ---------------- copy + print + attach ---------------- */
    function buildText() {
        const d = DATA, t = d.totals || {}, ph = d.phases || {};
        const pre = ph.preDayZero || { count: 0, cost: 0 };
        const main = ph.cropping || { count: 0, cost: 0 };
        const una = ph.unanchored || { count: 0, cost: 0 };
        const lines = [];
        lines.push(`LABOR REPORT — ${d.scheduleTitle || ''}`);
        lines.push('='.repeat(50));
        lines.push(`Generated: ${new Date().toLocaleString(((window.ANEE_REGION || {}).locale || 'en-PH'), { dateStyle: 'medium', timeStyle: 'short' })}`);
        lines.push('');
        lines.push(`TOTAL: ${fmtPeso(d.grandTotal)}`);
        lines.push(`  Land Preparation (${DAY_TYPE} < 0):    ${fmtPeso(pre.cost)}  (${pre.count})`);
        lines.push(`  Main Cropping (${DAY_TYPE} 0 onwards): ${fmtPeso(main.cost)}  (${main.count})`);
        if (una.count > 0) lines.push(`  Unanchored (no ${DAY_TYPE} 0):         ${fmtPeso(una.cost)}  (${una.count})`);
        lines.push(`Activities: ${d.totalActivities} · Assignments: ${t.totalAssignments || 0} · ${t.halfDays || 0}H / ${t.wholeDays || 0}W / ${t.naCount || 0}N`);
        lines.push(`Covers: ${$id('laborFilterHint')?.textContent?.replace(/^Covers: /, '') || 'the whole season'}`);
        lines.push('');
        lines.push('BY WORKER');
        lines.push('-'.repeat(50));
        (d.perWorker || []).forEach((w) => lines.push(`${w.name}: ${fmtPeso(w.total)}  (rate ${fmtPeso(w.costPerHalfDay)} · ${w.halfDays}H/${w.wholeDays}W${w.naCount ? '/' + w.naCount + 'N' : ''})`));
        lines.push('');
        lines.push('BY ACTIVITY');
        lines.push('-'.repeat(50));
        (d.perActivity || []).forEach((a) => lines.push(`${a.activityTitle} — ${a.targetDate || 'no date'} — ${fmtPeso(a.cost)}`));
        return lines.join('\n');
    }

    /* ---------------- the two doors: Generate | Saved ---------------- */
    const showTab = (gen) => {
        $id('lrTabGen').classList.toggle('is-on', gen);
        $id('lrTabSaved').classList.toggle('is-on', !gen);
        $id('lrGenPane').classList.toggle('hidden', !gen);
        $id('lrSavedPane').classList.toggle('hidden', gen);
        if (!gen) loadSaved();
    };
    $id('lrTabGen').addEventListener('click', () => showTab(true));
    $id('lrTabSaved').addEventListener('click', () => showTab(false));


    /* ---------------- the shelf ---------------- */
    async function loadSaved() {
        try {
            const res = await api(U.list + '&_=' + Date.now());
            LR_ROWS = res.data.rows || [];
            $id('lrSavedEmpty').classList.toggle('hidden', LR_ROWS.length > 0);
            $id('lrSavedList').innerHTML = LR_ROWS.map((r) => `
                <button type="button" class="lr-saved-row" data-lr-open="${r.id}">
                    <span style="font-size:1.2rem;flex:none;">🧾</span>
                    <span class="min-w-0 grow"><b>${esc(r.title)}</b><small>${r.description ? esc(r.description) + ' · ' : ''}${esc(r.when || '')}</small></span>
                    ${(MAY_GEN && r.mine) ? `<span role="button" tabindex="0" class="ar-pen" data-lr-meta="${r.id}" title="Edit name, description and tags" aria-label="Edit ${esc(r.title)}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:.85rem;height:.85rem"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    </span>` : ''}
                    <svg style="width:1rem;height:1rem;flex:none;color:var(--color-gray-300)" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                </button>`).join('');
        } catch (err) { toast(err.message, 'error'); }
    }

    async function openSaved(id) {
        try {
            const res = await api(U.one(id));
            SAVED = { id: res.data.id, mine: res.data.mine !== false, title: res.data.title || '', description: res.data.description || '' };
            if (res.data.report) {
                // The dataset rode along when it was saved — redraw it whole.
                DATA = res.data.report;
                renderAll();
            } else {
                // An older save: only its text came down the years.
                DATA = null;
                $id('lrContent').classList.add('hidden');
                $id('lrBodyPre').textContent = res.data.body || 'This saved report holds no text.';
                $id('lrBodyText').hidden = false;
            }
            showReport('saved');
        } catch (err) { toast(err.message, 'error'); }
    }

    $id('lrSavedList').addEventListener('click', (e) => {
        const pen = e.target.closest('[data-lr-meta]');
        if (pen) {
            e.stopPropagation();
            const r = LR_ROWS.find((x) => String(x.id) === pen.getAttribute('data-lr-meta'));
            if (!r) return;
            META_ID = r.id;
            $id('lrMetaTitle').value = r.title || '';
            $id('lrMetaDesc').value = r.description || '';
            const mount = $id('lrMetaTags');
            if (window.smTags && mount) { window.smTags.mount(mount); window.smTags.load(mount, 'report', r.id); }
            openSheet('lrMetaSheet');
            return;
        }
        const row = e.target.closest('[data-lr-open]');
        if (row) openSaved(row.getAttribute('data-lr-open'));
    });

    $id('lrMetaSave')?.addEventListener('click', async (e) => {
        if (META_ID === null) return;
        const btn = e.currentTarget;
        btn.disabled = true;
        try {
            const res = await api(U.meta, { method: 'POST', body: {
                id: META_ID,
                title: $id('lrMetaTitle').value.trim(),
                description: $id('lrMetaDesc').value.trim(),
                tags: window.smTags ? window.smTags.value($id('lrMetaTags')) : [],
            } });
            toast(res.message);
            closeSheet('lrMetaSheet');
            if (SAVED.id === META_ID) {
                SAVED.title = $id('lrMetaTitle').value.trim();
                SAVED.description = $id('lrMetaDesc').value.trim();
                window.reportView?.setTitle(SAVED.title);
            }
            loadSaved();
        } catch (err) { toast(err.message, 'error'); }
        finally { btn.disabled = false; }
    });


    /* A view-level worker lands on the shelf; a tag shelf can name one
       saved report (?open=<id>) and it opens as if tapped. */
    const want = new URLSearchParams(location.search).get('open');
    if (want) {
        showTab(false);
        openSaved(String(want).replace(/[^\d]/g, ''));
    } else if (!MAY_GEN) {
        showTab(false);
    }
};
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', __init, { once: true });
    else __init();
})();
</script>
@endpush
