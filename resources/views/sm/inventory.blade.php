@extends(request()->boolean('partial') ? 'layouts.partial' : 'layouts.app')

@section('title', 'Inventory — ' . $schedule->title)
@section('page-title', 'Inventory')
@section('page-subtitle', $schedule->title)
@section('help-key', 'inventory')
@section('back', route('sm.hub', ['id' => $schedule->id]))

@push('head')
<style>
    /* THREE VIEWS OF ONE LEDGER.
       What you keep, every change to it, and what is left — three tabs and
       not three pages, because a farmer standing in the shed asks all three
       within a minute of each other. */
    .iv-tabs { display: flex; gap: .35rem; margin: 1rem 0 .9rem;
        border-bottom: 1px solid var(--color-gray-200); }
    .iv-tab { padding: .55rem .8rem; font-weight: 700; font-size: .88rem;
        color: var(--color-gray-500); border-bottom: 2px solid transparent;
        margin-bottom: -1px; cursor: pointer; white-space: nowrap; }
    .iv-tab:hover { color: var(--color-gray-700); }
    .iv-tab.is-active { color: var(--color-brand-700); border-bottom-color: var(--color-brand-600); }
    .iv-pane { display: none; }
    .iv-pane.is-active { display: block; animation: ivPaneIn .28s cubic-bezier(.22,1,.36,1); }
    @keyframes ivPaneIn { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: none; } }

    /* ONE ITEM ON THE SHELF. */
    .iv-card { display: flex; align-items: flex-start; gap: .75rem; padding: .85rem .9rem; }
    .iv-face { width: 2.6rem; height: 2.6rem; flex: none; border-radius: .8rem;
        display: flex; align-items: center; justify-content: center; font-size: 1.25rem;
        background: var(--color-brand-50); }
    .iv-mid { flex: 1 1 auto; min-width: 0; }
    .iv-name { font-weight: 800; color: var(--color-gray-900); font-size: .95rem;
        overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .iv-kind { font-size: .72rem; font-weight: 600; color: var(--color-gray-400); }
    /* The number is the whole point of the card, so it is the biggest thing
       on it — and it says both the pack count and the base amount, because
       "have I enough" and "how much do I order" are different questions. */
    .iv-have { font-size: 1.05rem; font-weight: 800; color: var(--color-brand-800); margin-top: .25rem; }
    .iv-have.is-low { color: #b45309; }
    .iv-have.is-none { color: var(--color-gray-400); }
    .iv-low { display: inline-flex; align-items: center; gap: .25rem; margin-left: .35rem;
        font-size: .66rem; font-weight: 800; text-transform: uppercase; letter-spacing: .03em;
        padding: .1rem .4rem; border-radius: 999px; background: #fffbeb; color: #b45309;
        border: 1px solid #fde68a; }
    .iv-note { font-size: .75rem; color: var(--color-gray-400); margin-top: .2rem; }
    .iv-acts { display: flex; flex-direction: column; gap: .3rem; flex: none; }
    .iv-btn { display: inline-flex; align-items: center; justify-content: center; gap: .25rem;
        min-height: 1.9rem; padding: 0 .55rem; border-radius: .6rem; cursor: pointer;
        font-size: .74rem; font-weight: 800; border: 1px solid var(--color-gray-200);
        background: #fff; color: var(--color-gray-600);
        transition: background .28s cubic-bezier(.22,1,.36,1), color .28s cubic-bezier(.22,1,.36,1),
            border-color .28s cubic-bezier(.22,1,.36,1); }
    .iv-btn:hover { background: var(--color-gray-100); color: var(--color-gray-900); }
    .iv-btn.is-in:hover { color: var(--color-brand-800); border-color: var(--color-brand-300); background: var(--color-brand-50); }
    .iv-btn.is-out:hover { color: #b45309; border-color: #fde68a; background: #fffbeb; }

    /* THE LOG — a day, a change, and what the stock was either side of it. */
    .iv-log { display: flex; flex-direction: column; gap: .25rem; }
    .iv-day-h { font-size: .7rem; font-weight: 800; letter-spacing: .04em; text-transform: uppercase;
        color: var(--color-gray-400); padding: .8rem .2rem .3rem; }
    .iv-move { display: flex; align-items: center; gap: .65rem; padding: .55rem .6rem;
        border-radius: .7rem; background: var(--color-white); border: 1px solid var(--color-gray-100); }
    .iv-move-e { font-size: 1rem; flex: none; width: 1.6rem; text-align: center; }
    .iv-move-t { flex: 1 1 auto; min-width: 0; }
    .iv-move-n { font-size: .85rem; font-weight: 700; color: var(--color-gray-900);
        overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    /* from → to, which is the part that makes a log worth reading. */
    .iv-move-s { font-size: .72rem; color: var(--color-gray-400); }
    .iv-move-s b { font-weight: 700; color: var(--color-gray-600); }
    .iv-move-d { font-size: .9rem; font-weight: 800; flex: none; }
    .iv-move-d.is-in { color: var(--color-brand-700); }
    .iv-move-d.is-out { color: #b45309; }
    .iv-move-x { flex: none; width: 1.6rem; height: 1.6rem; border-radius: 999px;
        color: var(--color-gray-300); cursor: pointer; font-size: .7rem; }
    .iv-move-x:hover { background: #fef2f2; color: #dc2626; }

    /* TOTALS — the shortest answer, and the one most often wanted. */
    .iv-total { display: flex; align-items: center; gap: .7rem; padding: .6rem .2rem;
        border-bottom: 1px dashed var(--color-gray-100); }
    .iv-total:last-child { border-bottom: 0; }
    .iv-total-e { font-size: 1.05rem; width: 1.7rem; text-align: center; flex: none; }
    .iv-total-n { flex: 1 1 auto; min-width: 0; font-size: .88rem; font-weight: 700;
        color: var(--color-gray-800); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .iv-total-q { font-size: .88rem; font-weight: 800; color: var(--color-brand-800); flex: none; }
    .iv-total-q.is-low { color: #b45309; }
    .iv-total-q.is-none { color: var(--color-gray-300); }

    .iv-empty { text-align: center; padding: 2.2rem 1rem; }
    .iv-unitrow { display: grid; grid-template-columns: 1fr 1fr; gap: .5rem; }

    html.dark .iv-face { background: #25311b; }
    html.dark .iv-name, html.dark .iv-move-n { color: #e8efe1; }
    html.dark .iv-move { background: #1c2417; border-color: #2b3423; }
    html.dark .iv-btn { background: #1c2417; border-color: #2f3a26; color: #b9c6ad; }
    html.dark .iv-tabs { border-color: #2b3423; }
    @media (prefers-reduced-motion: reduce) {
        .iv-pane.is-active { animation: none; }
        .iv-btn { transition: none; }
    }
</style>
@endpush

@section('content')
    @include('sm.partials.module-header', ['schedule' => $schedule, 'module' => 'inventory'])

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <p class="text-sm text-gray-500">
            <span id="ivCount" class="font-bold text-gray-900">0</span> <span id="ivCountLabel">items</span> in this season's shed
        </p>
        <button type="button" class="btn btn-primary w-full sm:w-auto shrink-0" data-add-item>
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14m-7-7h14"/></svg>
            Add an item
        </button>
    </div>

    <div class="iv-tabs" role="tablist">
        <button type="button" class="iv-tab is-active" data-pane="ivPaneManage" role="tab">Management</button>
        <button type="button" class="iv-tab" data-pane="ivPaneLogs" role="tab">Logs</button>
        <button type="button" class="iv-tab" data-pane="ivPaneTotals" role="tab">Current totals</button>
    </div>

    {{-- WHAT YOU KEEP. Each item with what is left of it and the two things
         you ever do to it: put some in, take some out. --}}
    <div class="iv-pane is-active" id="ivPaneManage">
        <div id="ivList" class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3" data-animate-list></div>
        <div id="ivEmpty" class="card hidden">
            <div class="card-body iv-empty">
                <div class="mx-auto w-14 h-14 rounded-2xl bg-brand-50 flex items-center justify-center mb-3">
                    <svg class="w-7 h-7 text-brand-600" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                </div>
                <h2 class="font-bold text-gray-900 mb-1">Nothing on the shelf yet</h2>
                <p class="text-sm text-gray-500 mb-4">Add the fertiliser, chemicals and seed this season will spend. Ticking an activity done then takes what it used straight off the count.</p>
                <button type="button" class="btn btn-primary" data-add-item>Add the first item</button>
            </div>
        </div>
    </div>

    {{-- EVERY CHANGE, newest first, grouped by the day it happened. --}}
    <div class="iv-pane" id="ivPaneLogs">
        <div id="ivLog" class="iv-log"></div>
        <div id="ivLogEmpty" class="card hidden">
            <div class="card-body iv-empty">
                <div class="mx-auto w-14 h-14 rounded-2xl bg-brand-50 flex items-center justify-center mb-3">
                    <svg class="w-7 h-7 text-brand-600" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                </div>
                <h2 class="font-bold text-gray-900 mb-1">Nothing has moved yet</h2>
                <p class="text-sm text-gray-500">Every delivery, every use and every activity ticked done writes a line here, with what the stock was before and after.</p>
            </div>
        </div>
    </div>

    {{-- WHAT IS LEFT. The shortest answer, and the one most often wanted. --}}
    <div class="iv-pane" id="ivPaneTotals">
        <div class="card"><div class="card-body">
            <div id="ivTotals"></div>
            <p id="ivTotalsEmpty" class="text-sm text-gray-400 text-center py-6 hidden">Nothing on the shelf yet.</p>
        </div></div>
    </div>
@endsection

@push('sheets')
{{-- data-static: typing happens here, and a stray tap on the dimmed page
     must not eat a half-written item. ✕ and Cancel are the doors. --}}
<div class="sheet hidden" id="ivItemSheet" data-static="true" style="--sheet-width:32rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title" id="ivItemTitle">Add an item</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body space-y-3.5">
        <input type="hidden" id="ivItemId" value="">
        <div>
            <label for="ivName" class="form-label">What is it? <span class="text-red-500">*</span></label>
            <input type="text" id="ivName" maxlength="150" class="form-input" placeholder="e.g. Urea 46-0-0">
        </div>
        <div>
            <label for="ivKind" class="form-label">Kind</label>
            <select id="ivKind" class="form-select">
                @foreach (\App\Models\AsInventoryItem::KINDS as $key => $k)
                    <option value="{{ $key }}">{{ $k['icon'] }} {{ $k['label'] }}</option>
                @endforeach
            </select>
            <p class="form-hint" id="ivKindHint"></p>
        </div>
        {{-- COUNTED IN WHAT.
             One answer, and the pack is inside it: "bags (50 kg)" sits in the
             same list as "kg", so a farm that buys bags counts bags and a farm
             that buys loose counts kilos. Which answers appear follows from
             the kind above — a fuel is not sold in sachets — the way the day
             counter follows from the crop on the lot form. The options are
             filled in by the script; the list lives in one place, on the
             model, and nothing here keeps a second copy of it. --}}
        <div class="iv-unitrow">
            <div>
                <label for="ivUnit" class="form-label">Counted in</label>
                <select id="ivUnit" class="form-select"></select>
            </div>
            <div>
                <label for="ivLowAt" class="form-label">Tell me at <span class="text-gray-400 font-normal">(optional)</span></label>
                {{-- The unit beside it, because "5" is not a quantity until it
                     says five of what. It is the only number left on this
                     form, so it is the only place the unit has to be. --}}
                <div class="relative">
                    <input type="number" id="ivLowAt" min="0" step="any" class="form-input" placeholder="e.g. 5">
                    <span class="iv-qty-u" id="ivLowUnit"></span>
                </div>
            </div>
        </div>

        <div>
            <label for="ivPrice" class="form-label">Price <span class="text-gray-400 font-normal">(optional)</span></label>
            <div class="relative">
                <input type="number" id="ivPrice" min="0" step="any" class="form-input" placeholder="0.00" inputmode="decimal">
                <span class="iv-qty-u" id="ivPriceUnit"></span>
            </div>
            <p class="form-hint">What one costs. The expense report will multiply it by what the moves say was used.</p>
        </div>

        {{-- No opening count.
             Adding a thing to the shed and receiving a delivery are two acts,
             and this form is only the first. "How much have you now?" reads as
             a running total somebody has to keep correct; the answer is just
             + In, as often as you like, and each one writes its own line in
             the log — which one opening figure never would. --}}

        {{-- STOCK, FROM THE EDIT SHEET. The shelf card says Edit or Delete
             and nothing else, so moving stock lives here — where the item is
             already open — and in the day menu's two doors as before. --}}
        <div id="ivStockRow" class="hidden rounded-xl border border-gray-100 p-3">
            <div class="flex items-center justify-between gap-2">
                <div class="min-w-0">
                    <p class="form-label !mb-0">Stock</p>
                    <p class="form-hint !mt-0" id="ivStockSays"></p>
                </div>
                <div class="flex gap-2 shrink-0">
                    <button type="button" class="btn btn-white btn-sm" id="ivStockIn">+ In</button>
                    <button type="button" class="btn btn-white btn-sm" id="ivStockOut">− Out</button>
                </div>
            </div>
        </div>

        <div>
            <label for="ivNote" class="form-label">Note <span class="text-gray-400 font-normal">(optional)</span></label>
            <input type="text" id="ivNote" maxlength="500" class="form-input" placeholder="Where it is kept, the supplier, anything worth remembering">
        </div>
    </div>
    <div class="sheet-footer">
        <button type="button" class="btn btn-ghost" data-sheet-close>Cancel</button>
        <button type="button" id="ivSaveItem" class="btn btn-primary">Save item</button>
    </div>
</div>

@include('sm.partials.inventory-move-sheet')
@include('sm.partials.inventory-js', ['schedule' => $schedule, 'standalone' => true])
@endpush
