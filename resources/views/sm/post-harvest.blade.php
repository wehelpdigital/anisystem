@extends(request()->boolean('partial') ? 'layouts.partial' : 'layouts.app')

@section('title', 'Post-harvest — ' . $schedule->title)
@section('page-title', 'Post-harvest')
@section('page-subtitle', $schedule->title)
@section('back', route('sm.hub', ['id' => $schedule->id]))

@push('head')
    <style>
        .ph-card { position: relative; }
        .ph-cat {
            display: inline-flex; align-items: center; gap: .3rem; border-radius: 999px;
            padding: .15rem .55rem; font-size: 11px; font-weight: 700;
            background: var(--ph-cat-bg, #eef0fb); color: var(--ph-cat-fg, #3a4699);
        }
        .ph-cat-yield   { --ph-cat-bg: #e8f5e9; --ph-cat-fg: #1f6b32; }
        .ph-cat-quality { --ph-cat-bg: #eef0fb; --ph-cat-fg: #3a4699; }
        .ph-cat-pest    { --ph-cat-bg: #fdecea; --ph-cat-fg: #a52a1f; }
        .ph-cat-weather { --ph-cat-bg: #e6f4fb; --ph-cat-fg: #10618a; }
        .ph-cat-storage { --ph-cat-bg: #fef3e8; --ph-cat-fg: #a66200; }
        .ph-cat-market  { --ph-cat-bg: #e6f7f1; --ph-cat-fg: #0f6f4d; }
        .ph-cat-lesson  { --ph-cat-bg: #f3e8fd; --ph-cat-fg: #6b21a8; }
        .ph-cat-other   { --ph-cat-bg: #f3f4f6; --ph-cat-fg: #4b5563; }

        html.dark .ph-cat-yield   { --ph-cat-bg: #122c18; --ph-cat-fg: #7fd398; }
        html.dark .ph-cat-quality { --ph-cat-bg: #1c2044; --ph-cat-fg: #a9b3f0; }
        html.dark .ph-cat-pest    { --ph-cat-bg: #341613; --ph-cat-fg: #f09287; }
        html.dark .ph-cat-weather { --ph-cat-bg: #10283a; --ph-cat-fg: #79c2ea; }
        html.dark .ph-cat-storage { --ph-cat-bg: #33240f; --ph-cat-fg: #e9b563; }
        html.dark .ph-cat-market  { --ph-cat-bg: #0e2b23; --ph-cat-fg: #63c8a5; }
        html.dark .ph-cat-lesson  { --ph-cat-bg: #281338; --ph-cat-fg: #cb9af0; }
        html.dark .ph-cat-other   { --ph-cat-bg: #262c34; --ph-cat-fg: #b4bdc8; }

        .ph-figure { display: flex; flex-direction: column; gap: .1rem; }
        .ph-figure dt { font-size: 10.5px; font-weight: 700; letter-spacing: .03em; text-transform: uppercase; }
        .ph-figure dd { font-size: .95rem; font-weight: 800; }
        .ph-notes { font-size: .875rem; line-height: 1.5; }
        .ph-notes p { margin: .25rem 0; }
        .ph-notes ul { list-style: disc; padding-left: 1.25rem; }
        .ph-notes ol { list-style: decimal; padding-left: 1.25rem; }
        .ph-photo { max-height: 220px; border-radius: .6rem; }
    </style>
@endpush

@section('content')
@include('sm.partials.module-header', ['schedule' => $schedule, 'module' => 'post-harvest'])

{{-- Season totals: only shown once something has been recorded --}}
<div class="card p-4 mb-4 {{ $summary['count'] > 0 ? '' : 'hidden' }}" id="phSummary">
    <h2 class="font-bold text-gray-900 mb-3">Season so far</h2>
    <dl class="grid grid-cols-2 sm:grid-cols-4 gap-4" id="phSummaryGrid">
        @foreach ($summary['yields'] as $unit => $amount)
            <div class="ph-figure">
                <dt class="text-gray-400">Total ({{ $unit }})</dt>
                <dd class="text-brand-700">{{ number_format($amount, 2) }}</dd>
            </div>
        @endforeach
        @if ($summary['revenue'] !== null)
            <div class="ph-figure">
                <dt class="text-gray-400">Gross value</dt>
                <dd class="text-brand-700">₱ {{ number_format($summary['revenue'], 2) }}</dd>
            </div>
        @endif
        @if ($summary['avgMoisture'] !== null)
            <div class="ph-figure">
                <dt class="text-gray-400">Avg moisture</dt>
                <dd class="text-gray-900">{{ $summary['avgMoisture'] }}%</dd>
            </div>
        @endif
        <div class="ph-figure">
            <dt class="text-gray-400">Observations</dt>
            <dd class="text-gray-900" id="phSummaryCount">{{ $summary['count'] }}</dd>
        </div>
    </dl>
</div>

{{-- Desktop add button --}}
<button type="button" class="btn btn-primary w-full mb-4 hidden md:inline-flex" data-ph-add>
    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
    Record an observation
</button>

<div class="space-y-3" id="phList" data-animate-list>
    @foreach ($observations as $o)
        <div class="card p-4 ph-card" data-id="{{ $o->id }}">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0 grow">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="ph-cat ph-cat-{{ $o->category }}">{{ $categories[$o->category] ?? $o->category }}</span>
                        @if ($o->observationDate)
                            <span class="text-xs font-semibold text-gray-500">{{ $o->observationDate->format('M j, Y') }}</span>
                        @endif
                        @if ($o->lotId && $o->lot)
                            <span class="text-xs font-semibold text-gray-500">· {{ $o->lot->lotName }}</span>
                        @endif
                    </div>
                    <h3 class="font-bold text-gray-900 leading-snug mt-1.5">{{ $o->title }}</h3>
                </div>
                <div class="flex gap-1 shrink-0">
                    <button type="button" class="btn btn-sm btn-ghost js-edit" aria-label="Edit observation">
                        <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    </button>
                    <button type="button" class="btn btn-sm btn-ghost text-red-600 js-delete" aria-label="Delete observation">
                        <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </button>
                </div>
            </div>

            @php
                $figures = [];
                if ($o->yieldAmount !== null) {
                    $figures[] = ['Yield', number_format((float) $o->yieldAmount, 2) . ' ' . ($o->yieldUnit ?: ''), 'text-brand-700'];
                }
                if ($o->moisturePercent !== null) {
                    $figures[] = ['Moisture', rtrim(rtrim(number_format((float) $o->moisturePercent, 2), '0'), '.') . '%', 'text-gray-900'];
                }
                if ($o->pricePerUnit !== null) {
                    $figures[] = ['Price', '₱ ' . number_format((float) $o->pricePerUnit, 2), 'text-gray-900'];
                }
                if ($o->gross_value !== null) {
                    $figures[] = ['Gross value', '₱ ' . number_format($o->gross_value, 2), 'text-brand-700'];
                }
            @endphp
            @if ($figures)
                <dl class="grid grid-cols-2 sm:grid-cols-4 gap-3 mt-3">
                    @foreach ($figures as [$label, $value, $tone])
                        <div class="ph-figure">
                            <dt class="text-gray-400">{{ $label }}</dt>
                            <dd class="{{ $tone }}">{{ $value }}</dd>
                        </div>
                    @endforeach
                </dl>
            @endif

            @if (filled($o->buyer))
                <p class="text-sm text-gray-500 mt-2">Sold to <span class="font-semibold text-gray-700">{{ $o->buyer }}</span></p>
            @endif
            @if (filled($o->notes))
                <div class="ph-notes text-gray-600 mt-2">{!! $o->notes !!}</div>
            @endif
            @if (filled($o->imagePath))
                <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($o->imagePath) }}"
                     alt="" class="ph-photo mt-3" loading="lazy">
            @endif
        </div>
    @endforeach
</div>

{{-- Empty state --}}
<div class="card p-8 text-center {{ $observations->isEmpty() ? '' : 'hidden' }}" id="phEmpty">
    <svg class="w-12 h-12 mx-auto text-gray-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z"/></svg>
    <p class="font-semibold text-gray-700 mt-3">Nothing recorded yet</p>
    <p class="text-sm text-gray-500 mt-1">After harvest, note what you actually got — yield, moisture, price, what went wrong — so next season is planned from real numbers.</p>
    <button type="button" class="btn btn-primary mt-4" data-ph-add>Record an observation</button>
</div>

{{-- Mobile FAB --}}
<button type="button" data-ph-add aria-label="Record an observation"
    class="fixed bottom-24 right-4 z-30 w-14 h-14 rounded-full shadow-lg md:hidden flex items-center justify-center bg-brand-600 text-white hover:bg-brand-700 active:bg-brand-800">
    <svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
</button>
@endsection

@push('sheets')
<div class="sheet hidden" id="phSheet" style="--sheet-width:34rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title" id="phSheetTitle">Record an observation</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body">
        <input type="hidden" id="phId" value="">
        <input type="hidden" id="phImagePath" value="">

        <div class="mb-4">
            <label class="form-label" for="phTitle">What are you recording? <span class="text-red-500">*</span></label>
            <input type="text" id="phTitle" class="form-input" maxlength="191" placeholder="e.g. Lot A harvest — 92 sacks">
        </div>
        <div class="mb-4">
            <label class="form-label" for="phCategory">Category</label>
            <select id="phCategory" class="form-select">
                @foreach ($categories as $key => $label)
                    <option value="{{ $key }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="mb-4">
            <label class="form-label" for="phDate">Date</label>
            <input type="date" id="phDate" class="form-input">
        </div>
        <div class="mb-4">
            <label class="form-label" for="phLot">Lot</label>
            <select id="phLot" class="form-select">
                <option value="">Whole schedule</option>
                @foreach ($schedule->lots as $lot)
                    <option value="{{ $lot->id }}">{{ $lot->lotName }}</option>
                @endforeach
            </select>
        </div>

        <div class="mb-4">
            <label class="form-label" for="phYieldAmount">Yield</label>
            <input type="number" id="phYieldAmount" class="form-input" step="0.01" min="0" inputmode="decimal" placeholder="e.g. 4600">
        </div>
        <div class="mb-4">
            <label class="form-label" for="phYieldUnit">Unit</label>
            <input type="text" id="phYieldUnit" class="form-input" maxlength="24" list="phUnitOptions" placeholder="e.g. kg, sacks, cavans">
            <datalist id="phUnitOptions">
                <option value="kg"></option>
                <option value="sacks"></option>
                <option value="cavans"></option>
                <option value="tons"></option>
                <option value="pieces"></option>
            </datalist>
        </div>
        <div class="mb-4">
            <label class="form-label" for="phMoisture">Moisture (%)</label>
            <input type="number" id="phMoisture" class="form-input" step="0.1" min="0" max="100" inputmode="decimal" placeholder="e.g. 14">
        </div>
        <div class="mb-4">
            <label class="form-label" for="phPrice">Price per unit</label>
            <input type="number" id="phPrice" class="form-input" step="0.01" min="0" inputmode="decimal" placeholder="e.g. 23.50">
        </div>
        <p class="form-hint -mt-2 mb-4" id="phValueHint"></p>
        <div class="mb-4">
            <label class="form-label" for="phBuyer">Buyer</label>
            <input type="text" id="phBuyer" class="form-input" maxlength="191" placeholder="e.g. NFA, local trader">
        </div>

        <div class="mb-4">
            <label class="form-label" for="phNotes">Notes</label>
            <textarea id="phNotes" class="form-textarea" rows="4" maxlength="20000"
                placeholder="What happened, what you would do differently…"></textarea>
        </div>

        <div class="mb-2">
            <label class="form-label" for="phPhoto">Photo</label>
            <input type="file" id="phPhoto" class="form-input" accept="image/*" capture="environment">
            <p class="form-hint">Snap the harvest, the sacks, or a problem worth remembering.</p>
            <div id="phPhotoPreview" class="mt-2 hidden">
                <img src="" alt="" class="ph-photo">
                <button type="button" class="btn btn-sm btn-ghost text-red-600 mt-1" id="phPhotoRemove">Remove photo</button>
            </div>
        </div>
    </div>
    <div class="sheet-footer">
        <button type="button" class="btn btn-ghost" data-sheet-close>Cancel</button>
        <button type="button" class="btn btn-primary" id="phSaveBtn">Save observation</button>
    </div>
</div>
@endpush

@push('scripts')
<script>
(() => {
const __init = () => {
    const SCHEDULE_ID = @json($schedule->id);
    const CATEGORIES = @json($categories);
    const LOT_NAMES = @json($schedule->lots->mapWithKeys(fn ($l) => [$l->id => $l->lotName]));
    const URLS = {
        store: @json(route('sm.post-harvest.store')) + '?scheduleId=' + SCHEDULE_ID,
        update: (id) => @json(route('sm.post-harvest.update')) + '?scheduleId=' + SCHEDULE_ID + '&id=' + id,
        destroy: (id) => @json(route('sm.post-harvest.destroy')) + '?scheduleId=' + SCHEDULE_ID + '&id=' + id,
        upload: @json(route('sm.post-harvest.image-upload')) + '?scheduleId=' + SCHEDULE_ID,
    };

    @php
        $seed = $observations->mapWithKeys(fn ($o) => [$o->id => [
            'id' => $o->id,
            'title' => $o->title,
            'category' => $o->category,
            'observationDate' => $o->observationDate?->format('Y-m-d'),
            'lotId' => $o->lotId,
            'yieldAmount' => $o->yieldAmount === null ? null : (float) $o->yieldAmount,
            'yieldUnit' => $o->yieldUnit,
            'moisturePercent' => $o->moisturePercent === null ? null : (float) $o->moisturePercent,
            'pricePerUnit' => $o->pricePerUnit === null ? null : (float) $o->pricePerUnit,
            'buyer' => $o->buyer,
            'notes' => $o->notes,
            'imagePath' => $o->imagePath,
            'imageUrl' => $o->imagePath ? \Illuminate\Support\Facades\Storage::disk('public')->url($o->imagePath) : null,
        ]]);
    @endphp
    const OBS = @json($seed->isEmpty() ? new stdClass() : $seed);

    const list = document.getElementById('phList');
    const emptyEl = document.getElementById('phEmpty');
    const fld = (id) => document.getElementById(id);
    const num = (v) => (v === null || v === undefined || v === '' ? null : Number(v));

    const money = (v) => '₱ ' + Number(v).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const qty = (v) => Number(v).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const prettyDay = (iso) => {
        if (!iso) return '';
        const [y, m, d] = iso.split('-').map(Number);
        return new Date(y, m - 1, d).toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' });
    };

    function figuresFor(o) {
        const out = [];
        if (o.yieldAmount !== null) out.push(['Yield', qty(o.yieldAmount) + ' ' + (o.yieldUnit || ''), 'text-brand-700']);
        if (o.moisturePercent !== null) out.push(['Moisture', o.moisturePercent + '%', 'text-gray-900']);
        if (o.pricePerUnit !== null) out.push(['Price', money(o.pricePerUnit), 'text-gray-900']);
        if (o.yieldAmount !== null && o.pricePerUnit !== null) {
            out.push(['Gross value', money(o.yieldAmount * o.pricePerUnit), 'text-brand-700']);
        }
        return out;
    }

    /** Mirrors the Blade card above — keep the two in step. */
    function renderCard(o) {
        const el = document.createElement('div');
        el.className = 'card p-4 ph-card';
        el.dataset.id = o.id;
        const figures = figuresFor(o);
        el.innerHTML = `
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0 grow">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="ph-cat ph-cat-${escapeHtml(o.category)}">${escapeHtml(CATEGORIES[o.category] || o.category)}</span>
                        ${o.observationDate ? `<span class="text-xs font-semibold text-gray-500">${escapeHtml(prettyDay(o.observationDate))}</span>` : ''}
                        ${o.lotId && LOT_NAMES[o.lotId] ? `<span class="text-xs font-semibold text-gray-500">· ${escapeHtml(LOT_NAMES[o.lotId])}</span>` : ''}
                    </div>
                    <h3 class="font-bold text-gray-900 leading-snug mt-1.5">${escapeHtml(o.title)}</h3>
                </div>
                <div class="flex gap-1 shrink-0">
                    <button type="button" class="btn btn-sm btn-ghost js-edit" aria-label="Edit observation">
                        <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    </button>
                    <button type="button" class="btn btn-sm btn-ghost text-red-600 js-delete" aria-label="Delete observation">
                        <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </button>
                </div>
            </div>
            ${figures.length ? `<dl class="grid grid-cols-2 sm:grid-cols-4 gap-3 mt-3">${figures.map(([l, v, tone]) =>
                `<div class="ph-figure"><dt class="text-gray-400">${escapeHtml(l)}</dt><dd class="${tone}">${escapeHtml(v)}</dd></div>`).join('')}</dl>` : ''}
            ${o.buyer ? `<p class="text-sm text-gray-500 mt-2">Sold to <span class="font-semibold text-gray-700">${escapeHtml(o.buyer)}</span></p>` : ''}
            ${o.notes ? `<div class="ph-notes text-gray-600 mt-2">${o.notes}</div>` : ''}
            ${o.imageUrl ? `<img src="${escapeHtml(o.imageUrl)}" alt="" class="ph-photo mt-3" loading="lazy">` : ''}`;
        return el;
    }

    function refreshEmptyState() {
        const n = list.querySelectorAll('.ph-card').length;
        emptyEl.classList.toggle('hidden', n > 0);
        document.getElementById('phSummary').classList.toggle('hidden', n === 0);
        recomputeSummary();
    }

    /** Recompute season totals client-side so the header stays live. */
    function recomputeSummary() {
        const rows = Object.values(OBS);
        const byUnit = {};
        let revenue = 0;
        const moistures = [];
        rows.forEach((o) => {
            if (o.yieldAmount !== null) {
                const unit = (o.yieldUnit || '').trim() || 'unit';
                byUnit[unit] = (byUnit[unit] || 0) + o.yieldAmount;
            }
            if (o.yieldAmount !== null && o.pricePerUnit !== null) revenue += o.yieldAmount * o.pricePerUnit;
            if (o.moisturePercent !== null) moistures.push(o.moisturePercent);
        });

        const cells = Object.entries(byUnit).map(([unit, amount]) =>
            `<div class="ph-figure"><dt class="text-gray-400">Total (${escapeHtml(unit)})</dt><dd class="text-brand-700">${escapeHtml(qty(amount))}</dd></div>`);
        if (revenue > 0) {
            cells.push(`<div class="ph-figure"><dt class="text-gray-400">Gross value</dt><dd class="text-brand-700">${escapeHtml(money(revenue))}</dd></div>`);
        }
        if (moistures.length) {
            const avg = Math.round((moistures.reduce((a, b) => a + b, 0) / moistures.length) * 10) / 10;
            cells.push(`<div class="ph-figure"><dt class="text-gray-400">Avg moisture</dt><dd class="text-gray-900">${avg}%</dd></div>`);
        }
        cells.push(`<div class="ph-figure"><dt class="text-gray-400">Observations</dt><dd class="text-gray-900">${rows.length}</dd></div>`);
        document.getElementById('phSummaryGrid').innerHTML = cells.join('');
    }

    function refreshValueHint() {
        const y = num(fld('phYieldAmount').value);
        const p = num(fld('phPrice').value);
        fld('phValueHint').textContent = (y !== null && p !== null)
            ? 'Gross value: ' + money(y * p)
            : '';
    }

    function setPhoto(path, url) {
        fld('phImagePath').value = path || '';
        const wrap = document.getElementById('phPhotoPreview');
        wrap.classList.toggle('hidden', !path);
        wrap.querySelector('img').src = url || '';
    }

    function openPhSheet(o = null) {
        fld('phId').value = o ? o.id : '';
        fld('phSheetTitle').textContent = o ? 'Edit observation' : 'Record an observation';
        fld('phTitle').value = o ? o.title : '';
        fld('phCategory').value = o ? o.category : 'yield';
        fld('phDate').value = o ? (o.observationDate || '') : new Date().toISOString().slice(0, 10);
        fld('phLot').value = o && o.lotId ? String(o.lotId) : '';
        fld('phYieldAmount').value = o && o.yieldAmount !== null ? o.yieldAmount : '';
        fld('phYieldUnit').value = o ? (o.yieldUnit || '') : '';
        fld('phMoisture').value = o && o.moisturePercent !== null ? o.moisturePercent : '';
        fld('phPrice').value = o && o.pricePerUnit !== null ? o.pricePerUnit : '';
        fld('phBuyer').value = o ? (o.buyer || '') : '';
        // Notes round-trip as sanitised HTML; the textarea edits the source.
        fld('phNotes').value = o ? (o.notes || '').replace(/<br\s*\/?>/gi, '\n').replace(/<[^>]+>/g, '').trim() : '';
        fld('phPhoto').value = '';
        setPhoto(o ? o.imagePath : '', o ? o.imageUrl : '');
        refreshValueHint();
        openSheet('phSheet');
    }

    document.querySelectorAll('[data-ph-add]').forEach((btn) => btn.addEventListener('click', () => openPhSheet(null)));
    fld('phYieldAmount').addEventListener('input', refreshValueHint);
    fld('phPrice').addEventListener('input', refreshValueHint);
    document.getElementById('phPhotoRemove').addEventListener('click', () => setPhoto('', ''));

    fld('phPhoto').addEventListener('change', async (e) => {
        const file = e.target.files && e.target.files[0];
        if (!file) return;
        const form = new FormData();
        form.append('image', file);
        try {
            const res = await fetch(URLS.upload, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, Accept: 'application/json' },
                body: form,
                credentials: 'same-origin',
            });
            const json = await res.json();
            if (!json.success) throw new Error(json.message || 'Upload failed.');
            setPhoto(json.data.path, json.data.url);
            toast(json.message);
        } catch (err) {
            toast(err.message, 'error');
            e.target.value = '';
        }
    });

    document.getElementById('phSaveBtn').addEventListener('click', async () => {
        const id = fld('phId').value;
        const title = fld('phTitle').value.trim();
        if (!title) { toast('Give this observation a title.', 'error'); return; }

        // Line breaks survive the sanitiser as <br>; everything else is escaped.
        const rawNotes = fld('phNotes').value.trim();
        const payload = {
            title,
            category: fld('phCategory').value,
            observationDate: fld('phDate').value || null,
            lotId: fld('phLot').value || null,
            yieldAmount: num(fld('phYieldAmount').value),
            yieldUnit: fld('phYieldUnit').value.trim() || null,
            moisturePercent: num(fld('phMoisture').value),
            pricePerUnit: num(fld('phPrice').value),
            buyer: fld('phBuyer').value.trim() || null,
            notes: rawNotes
                ? escapeHtml(rawNotes).replace(/\r?\n/g, '<br>')
                : null,
            imagePath: fld('phImagePath').value || null,
        };

        const btn = document.getElementById('phSaveBtn');
        btn.disabled = true;
        try {
            const res = await api(id ? URLS.update(id) : URLS.store, { method: id ? 'PUT' : 'POST', body: payload });
            const o = {
                id: res.data.id,
                title: res.data.title,
                category: res.data.category,
                observationDate: res.data.observationDate ? String(res.data.observationDate).slice(0, 10) : null,
                lotId: res.data.lotId,
                yieldAmount: num(res.data.yieldAmount),
                yieldUnit: res.data.yieldUnit,
                moisturePercent: num(res.data.moisturePercent),
                pricePerUnit: num(res.data.pricePerUnit),
                buyer: res.data.buyer,
                notes: res.data.notes,
                imagePath: res.data.imagePath,
                imageUrl: res.data.imageUrl,
            };
            OBS[o.id] = o;
            const fresh = renderCard(o);
            const existing = list.querySelector('.ph-card[data-id="' + o.id + '"]');
            if (existing) existing.replaceWith(fresh);
            else list.prepend(fresh);
            refreshEmptyState();
            closeSheet('phSheet');
            toast(res.message);
        } catch (err) {
            toast(err.message, 'error');
        } finally {
            btn.disabled = false;
        }
    });

    list.addEventListener('click', async (e) => {
        const card = e.target.closest('.ph-card');
        if (!card) return;
        const id = card.dataset.id;

        if (e.target.closest('.js-edit')) {
            openPhSheet(OBS[id] || null);
            return;
        }
        if (e.target.closest('.js-delete')) {
            const name = OBS[id] ? OBS[id].title : 'this observation';
            const ok = await confirmAction({
                title: 'Delete observation?',
                message: '"' + name + '" will be removed from this schedule.',
                detail: 'Season totals are recalculated without it.',
                confirmText: 'Delete',
            });
            if (!ok) return;
            try {
                const res = await api(URLS.destroy(id), { method: 'DELETE' });
                delete OBS[id];
                const finish = () => { card.remove(); refreshEmptyState(); };
                if (window.animateOut) window.animateOut(card, finish); else finish();
                toast(res.message);
            } catch (err) {
                toast(err.message, 'error');
            }
        }
    });
};
    // First load: wait for app.js (deferred) to define the globals.
    // SPA injection: document is already complete, so run now.
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', __init, { once: true });
    else __init();
})();
</script>
@endpush
