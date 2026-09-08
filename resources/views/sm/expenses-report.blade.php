@extends('layouts.app')

@section('title', 'Expenses Report — ' . $schedule->title)
@section('page-title', 'Expenses Report')
@section('page-subtitle', $schedule->title)
@section('back', route('sm.reports', ['id' => $schedule->id]))

@push('head')
@include('partials.tag-sheet-css')
<style>
    /* ===== Expenses Report ==========================================
       The labor report's bones — hero, tiles, chooser tags, cards — worn
       by the season's whole ledger. Theme vars everywhere, dark for free. */
    .xr-wrap { max-width: 64rem; margin: 0 auto; }
    .xr-hero { border-radius: 1.25rem; border: 1px solid var(--color-brand-100);
        background: linear-gradient(115deg, var(--color-brand-50) 0%, var(--color-white) 70%);
        padding: 1.1rem 1.25rem; }
    .xr-hero-label { font-size: .72rem; font-weight: 800; letter-spacing: .07em; text-transform: uppercase; color: var(--color-gray-500); }
    .xr-hero-value { font-weight: 700; font-size: 2.2rem; line-height: 1.1; color: var(--color-gray-900); }
    .xr-hero-net { font-size: .85rem; font-weight: 700; margin-top: .2rem; }
    .xr-hero-net.is-up { color: #15803d; }
    .xr-hero-net.is-down { color: #b91c1c; }
    .xr-tiles { display: grid; grid-template-columns: repeat(auto-fill, minmax(9.5rem, 1fr)); gap: .6rem; margin-top: .9rem; }
    .xr-tile { border-radius: .9rem; background: var(--color-white); border: 1px solid var(--color-gray-100); padding: .6rem .8rem; }
    .xr-tile .k { display: flex; align-items: center; gap: .4rem; font-size: .72rem; font-weight: 700; color: var(--color-gray-500); }
    .xr-tile .k i { width: .6rem; height: .6rem; border-radius: .2rem; flex: none; }
    .xr-tile .v { font-weight: 700; font-size: 1.05rem; color: var(--color-gray-900); margin-top: .1rem; font-variant-numeric: tabular-nums; }

    .xr-card { border-radius: 1rem; border: 1px solid var(--color-gray-100); background: var(--color-white);
        box-shadow: var(--shadow-card); padding: 1rem 1.1rem; margin-top: .9rem; }
    .xr-card h3 { font-weight: 700; font-size: 1.02rem; color: var(--color-gray-900); }
    .xr-card .sub { font-size: .8rem; color: var(--color-gray-500); }

    /* Month bars — horizontal, because a phone reads down, not across. */
    .xr-months { display: flex; flex-direction: column; gap: .5rem; margin-top: .8rem; }
    .xr-mrow { display: grid; grid-template-columns: 4.2rem 1fr auto; gap: .6rem; align-items: center; }
    .xr-mlabel { font-size: .75rem; font-weight: 700; color: var(--color-gray-500); }
    .xr-mtrack { display: flex; flex-direction: column; gap: 3px; }
    .xr-mbar { height: 9px; border-radius: 999px; background: var(--color-brand-600); min-width: 2px;
        transition: width .5s cubic-bezier(.22,1,.36,1); }
    .xr-mbar.is-income { background: #f0b429; }
    .xr-mamt { font-size: .72rem; font-weight: 700; color: var(--color-gray-700); font-variant-numeric: tabular-nums; text-align: right; }
    .xr-mamt small { display: block; color: #a16207; font-weight: 700; }

    /* The ledger, card by card, grouped under month headings. */
    .xr-mh { display: flex; align-items: baseline; justify-content: space-between; gap: .6rem;
        margin: 1rem 0 .45rem; }
    .xr-mh b { font-size: .8rem; font-weight: 800; letter-spacing: .05em; text-transform: uppercase; color: var(--color-gray-500); }
    .xr-mh span { font-size: .75rem; font-weight: 700; color: var(--color-gray-400); font-variant-numeric: tabular-nums; }
    .xr-rows { display: grid; gap: .45rem; }
    .xr-row { display: flex; align-items: flex-start; gap: .6rem; border: 1px solid var(--color-gray-100);
        border-radius: .8rem; background: var(--color-white); padding: .6rem .75rem; }
    .xr-row-e { flex: none; width: 2rem; height: 2rem; border-radius: .6rem; display: inline-flex;
        align-items: center; justify-content: center; font-size: 1rem; background: var(--color-gray-50); }
    .xr-row-t { flex: 1 1 auto; min-width: 0; }
    .xr-row-t b { display: block; font-size: .86rem; color: var(--color-gray-900); overflow-wrap: anywhere; }
    .xr-row-t small { display: block; font-size: .72rem; color: var(--color-gray-500); margin-top: .05rem; }
    .xr-row-amt { flex: none; text-align: right; }
    .xr-row-amt b { font-size: .88rem; color: var(--color-gray-900); font-variant-numeric: tabular-nums; white-space: nowrap; }
    .xr-row-amt.is-income b { color: #15803d; }
    .xr-row-amt small { display: block; font-size: .66rem; color: var(--color-gray-400); }
    .xr-row.is-pending { opacity: .75; }
    html.dark .xr-hero { background: linear-gradient(115deg, #1c2913, #151b12 70%); border-color: #2b3a1c; }
    html.dark .xr-hero-value, html.dark .xr-tile .v, html.dark .xr-card h3 { color: #e8efe1; }
    html.dark .xr-tile, html.dark .xr-card, html.dark .xr-row { background: #151b12; border-color: #2b3a1c; }
    html.dark .xr-row-t b, html.dark .xr-row-amt b { color: #e8efe1; }
    html.dark .xr-row-e { background: #1c2416; }
    .xr-refetch { opacity: .5; pointer-events: none; }

    /* The page's two doors: make a report, or read the shelf. */
    .xr-mtabs { display: flex; gap: .4rem; margin-bottom: 1rem; }
    .xr-mtab { flex: 1 1 0; padding: .6rem; border-radius: .8rem; font-weight: 800; font-size: .9rem;
        text-align: center; color: var(--color-gray-500); background: var(--color-white);
        border: 1px solid var(--color-gray-200); cursor: pointer; }
    .xr-mtab.is-on { background: var(--color-brand-600); border-color: var(--color-brand-600); color: #fff; }
    html.dark .xr-mtab { background: #151b12; border-color: #2b3a1c; color: #93a684; }
    html.dark .xr-mtab.is-on { background: #4a7c2a; border-color: #4a7c2a; color: #fff; }

    .xr-saved-row { display: flex; align-items: center; gap: .6rem; width: 100%; text-align: left;
        padding: .7rem .8rem; border-bottom: 1px solid var(--color-gray-100); cursor: pointer; }
    .xr-saved-row:hover { background: var(--color-brand-50); }
    .xr-saved-row b { display: block; font-size: .86rem; color: var(--color-gray-900); }
    .xr-saved-row small { color: var(--color-gray-400); font-size: .72rem; }
    html.dark .xr-saved-row { border-color: #222b1a; }
    html.dark .xr-saved-row:hover { background: #161e10; }
    html.dark .xr-saved-row b { color: #e8efe1; }
    .ar-pen { flex: none; width: 1.6rem; height: 1.6rem; border-radius: .45rem; display: inline-flex;
        align-items: center; justify-content: center; color: var(--color-gray-400); }
    .ar-pen:hover { color: var(--color-brand-700); background: var(--color-brand-50); }
    html.dark .ar-pen:hover { background: rgb(107 159 61 / .18); color: #a5c97e; }

    @media print {
        header, nav, .xr-filters, .xr-mtabs, .xr-actions, #xrSavedPane, .bottom-nav, .tabbar, #aiFloat { display: none !important; }
        .xr-card { box-shadow: none; page-break-inside: avoid; }
    }
</style>
@endpush

@section('content')
@php
    // A view-level worker reads the shelf; generating a report writes to it.
    $xrMayGen = \App\Support\WorkerContext::canWriteModule('reports');
@endphp
@include('sm.partials.tag-picker')
<div class="xr-wrap">
    <div class="xr-mtabs" role="tablist">
        <button type="button" class="xr-mtab is-on" id="xrTabGen" @unless($xrMayGen) hidden @endunless>Generate</button>
        <button type="button" class="xr-mtab" id="xrTabSaved">Saved Reports</button>
    </div>

    <div id="xrGenPane">
    {{-- The wizard: set the slice, then generate. Results come after, not under. --}}
    <div class="card p-4 mb-4 xr-filters" id="xrWizard">
        <p class="text-sm font-bold text-gray-900">What should the report cover?</p>
        <p class="text-xs text-gray-500 mt-1 mb-3">Every filter is optional — left alone, the report adds up the whole season. The finished report lands on the Saved shelf by itself.</p>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 mb-2">
            <div>
                <span class="form-label text-xs! mb-1!">Lots</span>
                <button type="button" class="crop-tag" id="xrLotsBtn">
                    <span class="crop-tag-e">🌾</span>
                    <span class="crop-tag-t is-none" id="xrLotsNow">All lots</span>
                    <svg class="crop-tag-c" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/></svg>
                </button>
            </div>
            <div>
                <span class="form-label text-xs! mb-1!">Categories</span>
                <button type="button" class="crop-tag" id="xrCatsBtn">
                    <span class="crop-tag-e">🧾</span>
                    <span class="crop-tag-t is-none" id="xrCatsNow">Everything</span>
                    <svg class="crop-tag-c" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/></svg>
                </button>
            </div>
            <div>
                <span class="form-label text-xs! mb-1!">Inventory kind</span>
                <button type="button" class="crop-tag" id="xrKindBtn">
                    <span class="crop-tag-e">📦</span>
                    <span class="crop-tag-t is-none" id="xrKindNow">Any kind</span>
                    <svg class="crop-tag-c" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/></svg>
                </button>
            </div>
            <div>
                <span class="form-label text-xs! mb-1!">Work status</span>
                <button type="button" class="crop-tag" id="xrStatusBtn">
                    <span class="crop-tag-e">✅</span>
                    <span class="crop-tag-t is-none" id="xrStatusNow">Planned + done</span>
                    <svg class="crop-tag-c" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/></svg>
                </button>
            </div>
        </div>
        <div class="grid grid-cols-2 gap-2">
            <div>
                <label class="form-label text-xs!" for="xrFrom">From date</label>
                @include('partials.date-tag', ['id' => 'xrFrom', 'empty' => 'From'])
            </div>
            <div>
                <label class="form-label text-xs!" for="xrTo">To date</label>
                @include('partials.date-tag', ['id' => 'xrTo', 'empty' => 'To'])
            </div>
        </div>
        <p id="xrHint" class="text-xs text-gray-500 mt-2"></p>
        <div class="grid grid-cols-1 sm:grid-cols-[2fr_1fr] gap-2 mt-3">
            <button type="button" id="xrGenerateBtn" class="btn btn-primary w-full">Generate the report</button>
            <button type="button" id="xrResetBtn" class="btn btn-white w-full">Reset</button>
        </div>
    </div>
    </div>

    {{-- The shelf: every generated expenses report, newest first. --}}
    <div id="xrSavedPane" class="hidden">
        <div class="card !p-0 overflow-hidden">
            <div id="xrSavedList"></div>
            <div id="xrSavedEmpty" class="hidden text-center py-10">
                <p class="font-bold text-gray-900">Nothing saved yet</p>
                <p class="text-sm text-gray-400">Every expenses report that is generated lands here by itself.</p>
            </div>
        </div>
    </div>

    <div id="xrBody" hidden>
        <div class="xr-actions grid grid-cols-2 sm:grid-cols-5 gap-2 mb-4">
            <a class="btn btn-primary w-full" id="xrAskBtn" href="#">
                <img src="{{ \App\Models\AiSetting::current()->faceUrl() }}" alt="" class="w-4 h-4 rounded-full object-cover mr-1" style="width:1rem;height:1rem;">
                Ask {{ \App\Models\AiSetting::current()->assistantName }}
            </a>
            <button type="button" id="xrCopyBtn" class="btn btn-white w-full">Copy as Text</button>
            <button type="button" id="xrPrintBtn" class="btn btn-white w-full">Print</button>
            <button type="button" id="xrDeleteBtn" class="btn btn-white w-full !text-red-600" hidden>Delete</button>
            <button type="button" id="xrBackBtn" class="btn btn-white w-full">New report</button>
        </div>

        {{-- An older save carries only its text; it is shown as it was written. --}}
        <div id="xrBodyText" class="xr-card" hidden><pre class="whitespace-pre-wrap text-sm text-gray-700" id="xrBodyPre" style="font-family:inherit"></pre></div>

    <div id="xrContent" class="hidden">
        <div class="xr-hero">
            <div class="xr-hero-label">Total spent</div>
            <div class="xr-hero-value" id="xrTotal">₱0</div>
            <div class="xr-hero-net" id="xrNet"></div>
            <div class="text-xs text-gray-500 mt-1" id="xrMeta"></div>
            <div class="xr-tiles" id="xrTiles"></div>
        </div>

        <div class="xr-card">
            <h3>Month by month</h3>
            <p class="sub">Green is money out; gold underneath is money in.</p>
            <div class="xr-months" id="xrMonths"></div>
        </div>

        <div class="xr-card">
            <h3>The ledger</h3>
            <p class="sub" id="xrLedgerSub"></p>
            <div id="xrLedger"></div>
        </div>
    </div>
    </div>
</div>
@endsection

@push('sheets')
<div class="sheet hidden" id="xrLotsSheet" style="--sheet-width:24rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title">Which lots?</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body">
        <p class="text-xs text-gray-500 mb-2">Costs on an activity that touches several lots are shared between them. Nothing chosen = every lot, plus general costs.</p>
        <div class="dt-rows" id="xrLotsList">
            @foreach ($schedule->lots as $lot)
                <button type="button" class="dt-row" data-xr-lot="{{ $lot->id }}">
                    <span class="dt-row-e">🌾</span>
                    <span class="dt-row-body"><b>{{ $lot->lotName }}</b><i>{{ \App\Support\CropStages::label($lot->crop) ?: 'No crop set' }}</i></span>
                    <svg class="dt-row-tick" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                </button>
            @endforeach
        </div>
        <button type="button" class="btn btn-primary w-full mt-3" data-sheet-close>Done</button>
    </div>
</div>

<div class="sheet hidden" id="xrCatsSheet" style="--sheet-width:24rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title">Which categories?</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body">
        <div class="dt-rows" id="xrCatsList"></div>
        <button type="button" class="btn btn-primary w-full mt-3" data-sheet-close>Done</button>
    </div>
</div>

<div class="sheet hidden" id="xrKindSheet" style="--sheet-width:24rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title">Which inventory kind?</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body dt-rows" id="xrKindList">
        <button type="button" class="dt-row is-on" data-xr-kind="">
            <span class="dt-row-e">📦</span>
            <span class="dt-row-body"><b>Any kind</b><i>The whole report, unfiltered</i></span>
            <svg class="dt-row-tick" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
        </button>
        @foreach (\App\Models\AsInventoryItem::KINDS as $key => $k)
            <button type="button" class="dt-row" data-xr-kind="{{ $key }}">
                <span class="dt-row-e">{{ $k['icon'] }}</span>
                <span class="dt-row-body"><b>{{ $k['label'] }}</b><i>Material lines and stock buys of this kind only</i></span>
                <svg class="dt-row-tick" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
            </button>
        @endforeach
    </div>
</div>

<div class="sheet hidden" id="xrStatusSheet" style="--sheet-width:22rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title">Which work?</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body dt-rows" id="xrStatusList">
        <button type="button" class="dt-row is-on" data-xr-status="all">
            <span class="dt-row-e">🗓️</span>
            <span class="dt-row-body"><b>Planned + done</b><i>The whole plan's money, spent or still ahead</i></span>
            <svg class="dt-row-tick" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
        </button>
        <button type="button" class="dt-row" data-xr-status="done">
            <span class="dt-row-e">✅</span>
            <span class="dt-row-body"><b>Done only</b><i>Money the ticked work has actually spent</i></span>
            <svg class="dt-row-tick" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
        </button>
        <button type="button" class="dt-row" data-xr-status="pending">
            <span class="dt-row-e">⏳</span>
            <span class="dt-row-body"><b>Still ahead</b><i>What the unticked plan is going to cost</i></span>
            <svg class="dt-row-tick" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
        </button>
    </div>
</div>

{{-- Rename a saved report, describe it, retie its tags. --}}
<div class="sheet hidden" id="xrMetaSheet" style="--sheet-width:28rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title">Edit this report</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body space-y-4">
        <div>
            <label class="form-label" for="xrMetaTitle">Name</label>
            <input type="text" id="xrMetaTitle" class="form-input" maxlength="191">
        </div>
        <div>
            <label class="form-label" for="xrMetaDesc">Description <span class="text-gray-400 font-normal">(optional)</span></label>
            <textarea id="xrMetaDesc" class="form-textarea" rows="3" maxlength="2000"></textarea>
        </div>
        <div>
            <span class="form-label">Tags</span>
            <div class="tp-mount" data-tags data-tags-kind="report" id="xrMetaTags"></div>
        </div>
    </div>
    <div class="sheet-footer">
        <button type="button" class="btn btn-ghost" data-sheet-close>Cancel</button>
        <button type="button" class="btn btn-primary" id="xrMetaSave">Save changes</button>
    </div>
</div>
@endpush

@push('scripts')
<script>
(() => {
const __init = () => {
    const $id = (i) => document.getElementById(i);
    const esc = window.escapeHtml || ((s) => String(s));
    const DATA_URL = @json(route('sm.expenses.report.data') . '?id=' . $schedule->id);
    const fmtPeso = (n) => '₱' + Number(n || 0).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const fmtPeso0 = (n) => '₱' + Math.round(Number(n || 0)).toLocaleString('en-PH');
    const MONTHS = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    const CATS = {
        materials: { label: 'Materials', e: '🧂', color: '#15803d' },
        labor:     { label: 'Labor', e: '👷', color: '#d97706' },
        services:  { label: 'Services', e: '🚜', color: '#2563eb' },
        expense:   { label: 'Day expenses', e: '💸', color: '#b91c1c' },
        purchase:  { label: 'Stock buys', e: '📦', color: '#7c3aed' },
        income:    { label: 'Income', e: '💰', color: '#a16207' },
    };
    const LOT_NAMES = @json($schedule->lots->pluck('lotName', 'id'));
    const SCHEDULE_TITLE = @json($schedule->title);
    const MAY_GEN = @json($xrMayGen);
    const U = {
        snapshot: @json(route('sm.report.snapshot')),
        list: @json(route('sm.anee.list') . '?id=' . $schedule->id . '&kind=expenses'),
        one: (id) => @json(route('sm.anee.one', ['id' => '__ID__'])).replace('__ID__', id),
        del: (id) => @json(route('sm.anee.delete', ['id' => '__ID__'])).replace('__ID__', id),
        meta: @json(route('sm.anee.meta')),
        ai: @json(route('ai.index')),
    };

    let DATA = null;
    const LOT_SEL = new Set();
    const CAT_SEL = new Set();
    let KIND = '';
    let STATUS = 'all';
    // What the report area is showing: a fresh generate, or a shelf row.
    let MODE = 'fresh';
    let SAVED = { id: null, mine: true };
    let XR_ROWS = [];
    let META_ID = null;

    /* ---------------- the four chooser tags ---------------- */
    $id('xrCatsList').innerHTML = Object.entries(CATS).map(([k, c]) => `
        <button type="button" class="dt-row" data-xr-cat="${k}">
            <span class="dt-row-e">${c.e}</span>
            <span class="dt-row-body"><b>${c.label}</b></span>
            <svg class="dt-row-tick" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
        </button>`).join('');

    const sayMulti = (el, sel, all, word) => {
        if (!sel.size) { el.textContent = all; el.classList.add('is-none'); }
        else if (sel.size === 1) { el.textContent = word([...sel][0]); el.classList.remove('is-none'); }
        else { el.textContent = `${sel.size} chosen`; el.classList.remove('is-none'); }
    };
    const sayTags = () => {
        sayMulti($id('xrLotsNow'), LOT_SEL, 'All lots', (id) => LOT_NAMES[id] || ('Lot #' + id));
        sayMulti($id('xrCatsNow'), CAT_SEL, 'Everything', (k) => CATS[k].label);
        const kt = $id('xrKindNow');
        kt.textContent = KIND === '' ? 'Any kind' : (document.querySelector(`#xrKindList [data-xr-kind="${KIND}"] b`)?.textContent || KIND);
        kt.classList.toggle('is-none', KIND === '');
        const st = $id('xrStatusNow');
        st.textContent = document.querySelector(`#xrStatusList [data-xr-status="${STATUS}"] b`)?.textContent || 'Planned + done';
        st.classList.toggle('is-none', STATUS === 'all');
        const bits = [];
        if (LOT_SEL.size) bits.push(`${LOT_SEL.size} ${LOT_SEL.size === 1 ? 'lot' : 'lots'}`);
        if (CAT_SEL.size) bits.push(`${CAT_SEL.size} ${CAT_SEL.size === 1 ? 'category' : 'categories'}`);
        if (KIND) bits.push('kind: ' + KIND);
        if (STATUS !== 'all') bits.push(STATUS === 'done' ? 'done only' : 'still ahead');
        if ($id('xrFrom')?.value || $id('xrTo')?.value) bits.push(`dates [${$id('xrFrom').value || '—'}, ${$id('xrTo').value || '—'}]`);
        $id('xrHint').textContent = bits.length ? 'Filters active: ' + bits.join(' · ') : '';
    };

    $id('xrLotsBtn').addEventListener('click', () => openSheet('xrLotsSheet'));
    $id('xrLotsList').addEventListener('click', (e) => {
        const row = e.target.closest('[data-xr-lot]');
        if (!row) return;
        const id = Number(row.dataset.xrLot);
        LOT_SEL.has(id) ? LOT_SEL.delete(id) : LOT_SEL.add(id);
        row.classList.toggle('is-on', LOT_SEL.has(id));
        sayTags();
    });
    $id('xrCatsBtn').addEventListener('click', () => openSheet('xrCatsSheet'));
    $id('xrCatsList').addEventListener('click', (e) => {
        const row = e.target.closest('[data-xr-cat]');
        if (!row) return;
        const k = row.dataset.xrCat;
        CAT_SEL.has(k) ? CAT_SEL.delete(k) : CAT_SEL.add(k);
        row.classList.toggle('is-on', CAT_SEL.has(k));
        sayTags();
    });
    $id('xrKindBtn').addEventListener('click', () => openSheet('xrKindSheet'));
    $id('xrKindList').addEventListener('click', (e) => {
        const row = e.target.closest('[data-xr-kind]');
        if (!row) return;
        KIND = row.dataset.xrKind;
        document.querySelectorAll('#xrKindList [data-xr-kind]').forEach((r) => r.classList.toggle('is-on', r === row));
        closeSheet('xrKindSheet');
        sayTags();
    });
    $id('xrStatusBtn').addEventListener('click', () => openSheet('xrStatusSheet'));
    $id('xrStatusList').addEventListener('click', (e) => {
        const row = e.target.closest('[data-xr-status]');
        if (!row) return;
        STATUS = row.dataset.xrStatus;
        document.querySelectorAll('#xrStatusList [data-xr-status]').forEach((r) => r.classList.toggle('is-on', r === row));
        closeSheet('xrStatusSheet');
        sayTags();
    });

    function queryString() {
        const parts = [];
        LOT_SEL.forEach((id) => parts.push(`lotIds[]=${id}`));
        CAT_SEL.forEach((k) => parts.push(`cats[]=${encodeURIComponent(k)}`));
        if (KIND) parts.push('invKind=' + encodeURIComponent(KIND));
        if (STATUS !== 'all') parts.push('status=' + STATUS);
        if ($id('xrFrom')?.value) parts.push('from=' + encodeURIComponent($id('xrFrom').value));
        if ($id('xrTo')?.value) parts.push('to=' + encodeURIComponent($id('xrTo').value));
        return parts.length ? '&' + parts.join('&') : '';
    }

    /* Generate: compute the slice, save it to the shelf, then show it. */
    async function generate() {
        sayTags();
        const loader = screenLoader('Adding the season up…');
        try {
            const res = await api(DATA_URL + queryString());
            DATA = res.data;
            render();
            // The shelf copy: the text Anee reads plus the dataset that lets
            // the Saved tab redraw this exact report later.
            const params = { lotIds: [...LOT_SEL], cats: [...CAT_SEL], invKind: KIND, status: STATUS, from: $id('xrFrom')?.value || null, to: $id('xrTo')?.value || null };
            const filtered = LOT_SEL.size || CAT_SEL.size || KIND || STATUS !== 'all' || params.from || params.to;
            let savedNote = '';
            try {
                const snap = await api(U.snapshot, { method: 'POST', body: {
                    scheduleId: @json($schedule->id),
                    kind: 'expenses',
                    title: 'Expenses Report — ' + SCHEDULE_TITLE + (filtered ? ' (filtered)' : ''),
                    body: buildText(),
                    params,
                    report: DATA,
                } });
                SAVED = { id: snap.data.id, mine: true };
                savedNote = ' It is saved on the Saved Reports shelf — rename it there any time.';
            } catch (err) {
                SAVED = { id: null, mine: true };
                toast(err.message, 'error');
            }
            showReport('fresh');
            toast('Expenses report generated.' + savedNote);
        } catch (err) { toast(err.message, 'error'); }
        finally { loader.hide(); }
    }

    /* One report area, two ways in. */
    function showReport(mode) {
        MODE = mode;
        $id('xrGenPane').classList.add('hidden');
        $id('xrSavedPane').classList.add('hidden');
        $id('xrBody').hidden = false;
        $id('xrBackBtn').textContent = mode === 'fresh' ? 'New report' : 'Back to the shelf';
        $id('xrDeleteBtn').hidden = ! (mode === 'saved' && SAVED.mine && MAY_GEN);
        const ask = $id('xrAskBtn');
        ask.hidden = ! SAVED.id;
        if (SAVED.id) ask.href = U.ai + '?freport=' + SAVED.id;
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    /* ---------------- rendering ---------------- */
    function render() {
        $id('xrContent').classList.remove('hidden');
        $id('xrBodyText').hidden = true;
        const d = DATA;
        $id('xrTotal').textContent = fmtPeso(d.spend);
        const net = $id('xrNet');
        net.textContent = d.totals.income > 0 || d.net !== -d.spend
            ? `Income ${fmtPeso(d.totals.income)} · Net ${d.net >= 0 ? '+' : '−'}${fmtPeso(Math.abs(d.net))}`
            : '';
        net.className = 'xr-hero-net ' + (d.net >= 0 ? 'is-up' : 'is-down');
        $id('xrMeta').textContent = `${d.rowCount} ${d.rowCount === 1 ? 'entry' : 'entries'}`;
        $id('xrTiles').innerHTML = Object.entries(CATS)
            .filter(([k]) => k !== 'income' ? d.totals[k] > 0 : d.totals.income > 0)
            .map(([k, c]) => `<div class="xr-tile"><div class="k"><i style="background:${c.color}"></i>${c.label}</div><div class="v">${fmtPeso(d.totals[k])}</div></div>`)
            .join('') || '<p class="text-xs text-gray-400">Nothing matched the filters.</p>';

        // Months
        const months = Object.entries(d.perMonth || {});
        const max = Math.max(1, ...months.map(([, v]) => Math.max(v.spend, v.income)));
        $id('xrMonths').innerHTML = months.map(([ym, v]) => {
            const label = ym === '—' ? 'No date' : `${MONTHS[Number(ym.slice(5, 7)) - 1]} '${ym.slice(2, 4)}`;
            return `<div class="xr-mrow">
                <span class="xr-mlabel">${esc(label)}</span>
                <span class="xr-mtrack">
                    <span class="xr-mbar" style="width:${Math.max(2, (v.spend / max) * 100)}%"></span>
                    ${v.income > 0 ? `<span class="xr-mbar is-income" style="width:${Math.max(2, (v.income / max) * 100)}%"></span>` : ''}
                </span>
                <span class="xr-mamt">${fmtPeso0(v.spend)}${v.income > 0 ? `<small>+${fmtPeso0(v.income)}</small>` : ''}</span>
            </div>`;
        }).join('') || '<p class="text-sm text-gray-400 py-4 text-center">Nothing to plot.</p>';

        // Ledger, grouped by month.
        const groups = new Map();
        (d.rows || []).forEach((r) => {
            const ym = r.on ? r.on.slice(0, 7) : '—';
            if (!groups.has(ym)) groups.set(ym, []);
            groups.get(ym).push(r);
        });
        $id('xrLedgerSub').textContent = d.rowCount > (d.rows || []).length
            ? `Showing the latest ${(d.rows || []).length} of ${d.rowCount} entries — tighten the filters to see the rest.`
            : `${d.rowCount} ${d.rowCount === 1 ? 'entry' : 'entries'}, newest first.`;
        $id('xrLedger').innerHTML = [...groups.entries()].map(([ym, rows]) => {
            const label = ym === '—' ? 'No date' : `${MONTHS[Number(ym.slice(5, 7)) - 1]} ${ym.slice(0, 4)}`;
            const sub = rows.reduce((t, r) => t + (r.cat === 'income' ? 0 : r.amount), 0);
            return `<div class="xr-mh"><b>${esc(label)}</b><span>${fmtPeso0(sub)} out</span></div>
            <div class="xr-rows">${rows.map((r) => {
                const c = CATS[r.cat] || CATS.expense;
                const lots = (r.lotIds || []).map((id) => LOT_NAMES[id]).filter(Boolean).join(', ');
                return `<div class="xr-row${r.done === false ? ' is-pending' : ''}">
                    <span class="xr-row-e">${c.e}</span>
                    <span class="xr-row-t"><b>${esc(r.label)}</b><small>${esc(r.meta || '')}${lots ? ' · ' + esc(lots) : ''}${r.done === false ? ' · not yet done' : ''}</small></span>
                    <span class="xr-row-amt${r.cat === 'income' ? ' is-income' : ''}"><b>${r.cat === 'income' ? '+' : ''}${fmtPeso(r.amount)}</b><small>${esc(r.on || '')}</small></span>
                </div>`;
            }).join('')}</div>`;
        }).join('') || '<p class="text-sm text-gray-400 py-6 text-center">Nothing matched the filters.</p>';
    }

    /* ---------------- copy / print / attach ---------------- */
    function buildText() {
        const d = DATA;
        const lines = [];
        lines.push(`EXPENSES REPORT — ${d.scheduleTitle || ''}`);
        lines.push('='.repeat(50));
        lines.push(`Generated: ${new Date().toLocaleString('en-PH', { dateStyle: 'medium', timeStyle: 'short' })}`);
        lines.push('');
        lines.push(`TOTAL SPENT: ${fmtPeso(d.spend)}`);
        Object.entries(CATS).forEach(([k, c]) => {
            if (k === 'income' || !d.totals[k]) return;
            lines.push(`  ${c.label}: ${fmtPeso(d.totals[k])}`);
        });
        lines.push(`Income: ${fmtPeso(d.totals.income)} · Net: ${fmtPeso(d.net)}`);
        lines.push('');
        lines.push('BY MONTH');
        lines.push('-'.repeat(50));
        Object.entries(d.perMonth || {}).forEach(([ym, v]) => lines.push(`${ym}: out ${fmtPeso(v.spend)}${v.income ? ' · in ' + fmtPeso(v.income) : ''}`));
        lines.push('');
        lines.push('BY LOT (activity costs shared across their lots)');
        lines.push('-'.repeat(50));
        Object.entries(d.perLot || {}).forEach(([lid, v]) => lines.push(`${lid === '0' ? 'General (no lot)' : (LOT_NAMES[lid] || 'Lot #' + lid)}: ${fmtPeso(v)}`));
        lines.push('');
        lines.push(`ENTRIES (${d.rowCount})`);
        lines.push('-'.repeat(50));
        (d.rows || []).forEach((r) => {
            const c = CATS[r.cat] || {};
            lines.push(`${r.on || 'no date'} · ${c.label || r.cat} · ${r.label}${r.meta ? ' (' + r.meta + ')' : ''} — ${r.cat === 'income' ? '+' : ''}${fmtPeso(r.amount)}`);
        });
        return lines.join('\n');
    }
    $id('xrGenerateBtn').addEventListener('click', generate);
    $id('xrResetBtn').addEventListener('click', () => {
        LOT_SEL.clear(); CAT_SEL.clear(); KIND = ''; STATUS = 'all';
        document.querySelectorAll('#xrLotsList .is-on, #xrCatsList .is-on').forEach((r) => r.classList.remove('is-on'));
        document.querySelectorAll('#xrKindList [data-xr-kind]').forEach((r) => r.classList.toggle('is-on', r.dataset.xrKind === ''));
        document.querySelectorAll('#xrStatusList [data-xr-status]').forEach((r) => r.classList.toggle('is-on', r.dataset.xrStatus === 'all'));
        ['xrFrom', 'xrTo'].forEach((i) => { if ($id(i)) $id(i).value = ''; });
        sayTags();
    });
    $id('xrFrom')?.addEventListener('change', sayTags);
    $id('xrTo')?.addEventListener('change', sayTags);
    $id('xrCopyBtn').addEventListener('click', () => {
        // A rich report copies its text rendering; an older text-only save
        // copies exactly what it holds.
        const text = $id('xrBodyText').hidden ? (DATA ? buildText() : '') : $id('xrBodyPre').textContent;
        if (!text) { toast('Nothing to copy yet.', 'info'); return; }
        (navigator.clipboard?.writeText(text) || Promise.reject(new Error('no')))
            .then(() => toast('Expenses report copied to clipboard.'))
            .catch(() => toast('Copy failed on this browser.', 'error'));
    });
    $id('xrPrintBtn').addEventListener('click', () => window.print());

    /* ---------------- the two doors: Generate | Saved ---------------- */
    const showTab = (gen) => {
        $id('xrTabGen').classList.toggle('is-on', gen);
        $id('xrTabSaved').classList.toggle('is-on', !gen);
        $id('xrGenPane').classList.toggle('hidden', !gen);
        $id('xrSavedPane').classList.toggle('hidden', gen);
        $id('xrBody').hidden = true;
        if (!gen) loadSaved();
    };
    $id('xrTabGen').addEventListener('click', () => showTab(true));
    $id('xrTabSaved').addEventListener('click', () => showTab(false));

    $id('xrBackBtn').addEventListener('click', () => showTab(MODE === 'fresh' && MAY_GEN));

    /* ---------------- the shelf ---------------- */
    async function loadSaved() {
        try {
            const res = await api(U.list + '&_=' + Date.now());
            XR_ROWS = res.data.rows || [];
            $id('xrSavedEmpty').classList.toggle('hidden', XR_ROWS.length > 0);
            $id('xrSavedList').innerHTML = XR_ROWS.map((r) => `
                <button type="button" class="xr-saved-row" data-xr-open="${r.id}">
                    <span style="font-size:1.2rem;flex:none;">💸</span>
                    <span class="min-w-0 grow"><b>${esc(r.title)}</b><small>${r.description ? esc(r.description) + ' · ' : ''}${esc(r.when || '')}</small></span>
                    ${(MAY_GEN && r.mine) ? `<span role="button" tabindex="0" class="ar-pen" data-xr-meta="${r.id}" title="Edit name, description and tags" aria-label="Edit ${esc(r.title)}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:.85rem;height:.85rem"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    </span>` : ''}
                    <svg style="width:1rem;height:1rem;flex:none;color:var(--color-gray-300)" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                </button>`).join('');
        } catch (err) { toast(err.message, 'error'); }
    }

    async function openSaved(id) {
        try {
            const res = await api(U.one(id));
            SAVED = { id: res.data.id, mine: res.data.mine !== false };
            if (res.data.report) {
                // The dataset rode along when it was saved — redraw it whole.
                DATA = res.data.report;
                render();
            } else {
                // An older save: only its text came down the years.
                DATA = null;
                $id('xrContent').classList.add('hidden');
                $id('xrBodyPre').textContent = res.data.body || 'This saved report holds no text.';
                $id('xrBodyText').hidden = false;
            }
            showReport('saved');
        } catch (err) { toast(err.message, 'error'); }
    }

    $id('xrSavedList').addEventListener('click', (e) => {
        const pen = e.target.closest('[data-xr-meta]');
        if (pen) {
            e.stopPropagation();
            const r = XR_ROWS.find((x) => String(x.id) === pen.getAttribute('data-xr-meta'));
            if (!r) return;
            META_ID = r.id;
            $id('xrMetaTitle').value = r.title || '';
            $id('xrMetaDesc').value = r.description || '';
            const mount = $id('xrMetaTags');
            if (window.smTags && mount) { window.smTags.mount(mount); window.smTags.load(mount, 'report', r.id); }
            openSheet('xrMetaSheet');
            return;
        }
        const row = e.target.closest('[data-xr-open]');
        if (row) openSaved(row.getAttribute('data-xr-open'));
    });

    $id('xrMetaSave')?.addEventListener('click', async (e) => {
        if (META_ID === null) return;
        const btn = e.currentTarget;
        btn.disabled = true;
        try {
            const res = await api(U.meta, { method: 'POST', body: {
                id: META_ID,
                title: $id('xrMetaTitle').value.trim(),
                description: $id('xrMetaDesc').value.trim(),
                tags: window.smTags ? window.smTags.value($id('xrMetaTags')) : [],
            } });
            toast(res.message);
            closeSheet('xrMetaSheet');
            loadSaved();
        } catch (err) { toast(err.message, 'error'); }
        finally { btn.disabled = false; }
    });

    $id('xrDeleteBtn').addEventListener('click', async () => {
        if (!SAVED.id) return;
        const ok = window.confirmAction ? await window.confirmAction({ title: 'Delete this saved report?', message: 'It leaves the shelf. The season\'s numbers stay — a new report can always be generated.', confirmText: 'Delete' }) : true;
        if (!ok) return;
        try {
            await api(U.del(SAVED.id), { method: 'DELETE' });
            toast('Report removed.');
            showTab(false);
        } catch (err) { toast(err.message, 'error'); }
    });

    /* A view-level worker lands on the shelf; a tag shelf can name one
       saved report (?open=<id>) and it opens as if tapped. */
    const want = new URLSearchParams(location.search).get('open');
    if (want) {
        showTab(false);
        openSaved(String(want).replace(/[^\d]/g, ''));
    } else if (!MAY_GEN) {
        showTab(false);
    } else {
        sayTags();
    }
};
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', __init, { once: true });
    else __init();
})();
</script>
@endpush
