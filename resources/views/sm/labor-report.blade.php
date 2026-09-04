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
    .lr-hero-value { font-family: var(--font-sans); font-weight: 700; font-size: 2.5rem; line-height: 1.1; color: var(--color-gray-900); }
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
    .lr-row { display: flex; align-items: center; gap: .6rem; cursor: default; }
    .lr-rowname { width: 9.5rem; min-width: 6rem; font-size: .85rem; font-weight: 600; color: var(--color-gray-800); text-align: right; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .lr-track { flex: 1 1 auto; display: flex; align-items: center; height: 22px; }
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
        header, nav, .lr-filters, .lr-tabs, .lr-actions, .bottom-nav, #aiFloat { display: none !important; }
        .lr-pane { display: block !important; page-break-inside: avoid; margin-bottom: 1rem; }
        .lr-card { box-shadow: none; }
    }
</style>
@endpush

@section('content')
<div class="lr-wrap">

    {{-- One filter row scoping everything below --}}
    <div class="card p-4 mb-4 lr-filters">
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
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
            <div>
                <label class="form-label text-xs!" for="laborDasMin">{{ $schedule->dayType }} min</label>
                <input type="number" id="laborDasMin" class="form-input" step="1" placeholder="−∞">
            </div>
            <div>
                <label class="form-label text-xs!" for="laborDasMax">{{ $schedule->dayType }} max</label>
                <input type="number" id="laborDasMax" class="form-input" step="1" placeholder="+∞">
            </div>
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
        {{-- Full-width actions: two rows of whole buttons, no thumb-hunting. --}}
        <div class="grid grid-cols-2 gap-2 mt-3">
            <button type="button" id="laborApplyFiltersBtn" class="btn btn-primary w-full">Apply Filters</button>
            <button type="button" id="laborResetFiltersBtn" class="btn btn-white w-full">Reset</button>
        </div>
        <div class="lr-actions grid grid-cols-1 sm:grid-cols-3 gap-2 mt-2">
            <button type="button" id="laborCopyBtn" class="btn btn-white w-full">Copy as Text</button>
            <button type="button" id="laborPrintBtn" class="btn btn-white w-full">Print</button>
            <button type="button" id="laborAttachBtn" class="btn btn-white w-full">
                <img src="{{ \App\Models\AiSetting::current()->faceUrl() }}" alt="" class="w-4 h-4 rounded-full object-cover mr-1" style="width:1rem;height:1rem;">
                Attach to {{ \App\Models\AiSetting::current()->assistantName }}
            </button>
        </div>
    </div>

    <div id="lrBody">
        <div class="text-center text-gray-400 py-16 text-sm" id="lrLoading">Calculating…</div>

        <div id="lrContent" class="hidden">
            {{-- Hero --}}
            <div class="lr-hero mb-1">
                <div class="lr-hero-label">Total labor expense</div>
                <div class="lr-hero-value" id="lrTotal">₱0</div>
                <div class="text-xs text-gray-500 mt-1" id="lrMeta"></div>
                <div class="lr-tiles" id="lrTiles"></div>
            </div>

            {{-- The view, chosen from a sheet — three underlined words in a
                 row read fine on a desk and jostled on a phone. --}}
            <div class="my-4 lr-tabs">
                <span class="form-label text-xs! mb-1!">Report view</span>
                <button type="button" class="crop-tag" id="lrPaneBtn">
                    <span class="crop-tag-e" id="lrPaneIcon">📊</span>
                    <span class="crop-tag-t" id="lrPaneNow">Busiest Months</span>
                    <svg class="crop-tag-c" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/></svg>
                </button>
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
                    <div class="lr-legend">
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
                    <span class="dt-row-body"><b>{{ $w->workerName }}</b><i>₱{{ number_format((float) $w->costPerHalfDay, 2) }} per half day</i></span>
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
@endpush

@push('scripts')
<script>
(() => {
const __init = () => {
    const $id = (i) => document.getElementById(i);
    const esc = window.escapeHtml || ((s) => String(s));
    const LABOR_URL = @json(route('sm.activities.labor') . '?scheduleId=' . $schedule->id);
    const DAY_TYPE = @json($schedule->dayType);
    const MONTH_SHORT = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    const PHASE = { pre: '#d97706', crop: '#15803d', una: '#2563eb' };
    const fmtPeso = (n) => '₱' + Number(n || 0).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const fmtPeso0 = (n) => '₱' + Math.round(Number(n || 0)).toLocaleString('en-PH');
    const parseD = (iso) => { const [y, m, d] = String(iso).slice(0, 10).split('-').map(Number); return (y && m && d) ? new Date(y, m - 1, d, 12) : null; };

    let DATA = null;
    let METRIC = 'cost';

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

    function filterPayload() {
        const p = {};
        const w = [...WORKER_SEL];
        if (w.length) p.workerIds = w;
        const dmin = ($id('laborDasMin')?.value || '').trim();
        const dmax = ($id('laborDasMax')?.value || '').trim();
        if (dmin !== '' && !isNaN(parseInt(dmin, 10))) p.dasMin = parseInt(dmin, 10);
        if (dmax !== '' && !isNaN(parseInt(dmax, 10))) p.dasMax = parseInt(dmax, 10);
        if ($id('laborStartDate')?.value) p.startDate = $id('laborStartDate').value;
        if ($id('laborEndDate')?.value) p.endDate = $id('laborEndDate').value;
        return p;
    }
    function queryString() {
        const f = filterPayload();
        const parts = [];
        (f.workerIds || []).forEach((id) => parts.push(`workerIds[]=${id}`));
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
        if (f.dasMin !== undefined || f.dasMax !== undefined) parts.push(`${DAY_TYPE} [${f.dasMin ?? '−∞'}, ${f.dasMax ?? '+∞'}]`);
        if (f.startDate || f.endDate) parts.push(`Date [${f.startDate || '—'}, ${f.endDate || '—'}]`);
        $id('laborFilterHint').textContent = parts.length ? `Filters active: ${parts.join(' · ')}` : '';
    }

    async function reload() {
        updateHint();
        const content = $id('lrContent');
        // Refetch keeps the frame (previous render dims) under a blocking
        // loader, so applying filters never looks like nothing happened.
        const refetching = !content.classList.contains('hidden');
        let loader = null;
        if (refetching) {
            content.classList.add('lr-refetch');
            loader = screenLoader('Updating labor report…');
        }
        try {
            const res = await api(LABOR_URL + queryString());
            DATA = res.data;
            renderAll();
            $id('lrLoading').classList.add('hidden');
            content.classList.remove('hidden');
        } catch (err) {
            toast(err.message, 'error');
        } finally {
            content.classList.remove('lr-refetch');
            loader?.hide();
        }
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
        $id('lrMeta').textContent = `${d.totalActivities} ${d.totalActivities === 1 ? 'activity' : 'activities'} · ${t.totalAssignments || 0} worker assignments · ${t.halfDays || 0} half-day / ${t.wholeDays || 0} whole-day / ${t.naCount || 0} N/A`;
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
        const fmtTick = (v) => METRIC === 'cost' ? '₱' + (v >= 1000 ? (v / 1000).toLocaleString() + 'k' : v.toLocaleString()) : String(v);
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
        $id('lrWorkersSub').textContent = (workers[0].total || 0) > 0
            ? `${workers[0].name} leads with ${fmtPeso0(workers[0].total)} across the plan.` : 'No paid assignments in this slice.';
        host.innerHTML = workers.map((w, i) => {
            const segs = [
                [PHASE.pre, w.preDayZeroTotal || 0, 'Land Preparation'],
                [PHASE.crop, w.croppingTotal || 0, 'Main Cropping'],
                [PHASE.una, w.unanchoredTotal || 0, 'Unanchored'],
            ].filter(([, v]) => v > 0);
            const width = ((w.total || 0) / max) * 100;
            return `<div class="lr-row" data-i="${i}">
                <span class="lr-rowname" title="${esc(w.name)}">${esc(w.name)}</span>
                <span class="lr-track"><span style="display:flex;width:${width}%;height:100%">${segs.map(([c, v]) => `<span class="lr-seg" style="background:${c};flex:${v} ${v} 0"></span>`).join('')}</span></span>
                <span class="lr-rowtotal">${esc(fmtPeso0(w.total))}${i === 0 && (w.total || 0) > 0 ? '<span class="lr-toptag">Top earner</span>' : ''}</span>
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
        $id('lrBreakdown').innerHTML = `
            <h3>By worker</h3>
            <div class="lr-bcards">${workerCards}</div>
            <h3 class="mt-5">By activity</h3>
            ${section((d.perActivity || []).filter((a) => a.phase === 'preDayZero'), 'Land Preparation', PHASE.pre, (ph.preDayZero || {}).cost || 0)}
            ${section((d.perActivity || []).filter((a) => a.phase === 'cropping'), 'Main Cropping', PHASE.crop, (ph.cropping || {}).cost || 0)}
            ${section((d.perActivity || []).filter((a) => a.phase === 'unanchored'), 'Unanchored', PHASE.una, (ph.unanchored || {}).cost || 0)}`;
    }

    function renderAll() {
        if (!DATA || DATA.totalActivities === 0) {
            $id('lrLoading').classList.add('hidden');
            $id('lrContent').classList.remove('hidden');
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
    $id('laborApplyFiltersBtn')?.addEventListener('click', reload);
    $id('laborResetFiltersBtn')?.addEventListener('click', () => {
        WORKER_SEL.clear();
        document.querySelectorAll('#lrWorkersList [data-lr-worker]').forEach((r) => r.classList.remove('is-on'));
        sayWorkersTag();
        ['laborDasMin', 'laborDasMax', 'laborStartDate', 'laborEndDate'].forEach((i) => { if ($id(i)) $id(i).value = ''; });
        reload();
    });
    $id('laborStartDate')?.addEventListener('change', reload);
    $id('laborEndDate')?.addEventListener('change', reload);

    /* ---------------- copy + print + attach ---------------- */
    function buildText() {
        const d = DATA, t = d.totals || {}, ph = d.phases || {};
        const pre = ph.preDayZero || { count: 0, cost: 0 };
        const main = ph.cropping || { count: 0, cost: 0 };
        const una = ph.unanchored || { count: 0, cost: 0 };
        const lines = [];
        lines.push(`LABOR REPORT — ${d.scheduleTitle || ''}`);
        lines.push('='.repeat(50));
        lines.push(`Generated: ${new Date().toLocaleString('en-PH', { dateStyle: 'medium', timeStyle: 'short' })}`);
        lines.push('');
        lines.push(`TOTAL: ${fmtPeso(d.grandTotal)}`);
        lines.push(`  Land Preparation (${DAY_TYPE} < 0):    ${fmtPeso(pre.cost)}  (${pre.count})`);
        lines.push(`  Main Cropping (${DAY_TYPE} 0 onwards): ${fmtPeso(main.cost)}  (${main.count})`);
        if (una.count > 0) lines.push(`  Unanchored (no ${DAY_TYPE} 0):         ${fmtPeso(una.cost)}  (${una.count})`);
        lines.push(`Activities: ${d.totalActivities} · Assignments: ${t.totalAssignments || 0} · ${t.halfDays || 0}H / ${t.wholeDays || 0}W / ${t.naCount || 0}N`);
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
    $id('laborCopyBtn')?.addEventListener('click', () => {
        if (!DATA) { toast('Wait for the report to finish loading.', 'info'); return; }
        const text = buildText();
        (navigator.clipboard?.writeText(text) || Promise.reject(new Error('Clipboard unavailable')))
            .then(() => toast('Labor report copied to clipboard.'))
            .catch(() => toast('Copy failed on this browser.', 'error'));
    });
    $id('laborPrintBtn')?.addEventListener('click', () => window.print());

    /* Freeze the report exactly as computed and walk it into the chat —
       the when-to-plant attach, worn by a report. */
    $id('laborAttachBtn')?.addEventListener('click', async (e) => {
        if (!DATA) { toast('Wait for the report to finish loading.', 'info'); return; }
        const btn = e.currentTarget;
        btn.disabled = true;
        try {
            const res = await api(@json(route('sm.report.snapshot')), { method: 'POST', body: {
                scheduleId: @json($schedule->id),
                kind: 'labor',
                title: @json('Labor Report — ' . $schedule->title),
                body: buildText(),
                params: filterPayload(),
            } });
            window.location.href = @json(route('ai.index')) + '?freport=' + res.data.id;
        } catch (err) {
            toast(err.message, 'error');
            btn.disabled = false;
        }
    });

    reload();
};
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', __init, { once: true });
    else __init();
})();
</script>
@endpush
