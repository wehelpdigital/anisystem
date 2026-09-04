@extends(request()->boolean('partial') ? 'layouts.partial' : 'layouts.app')

@section('title', 'Observations — ' . $schedule->title)
@section('page-title', 'Observations')
@section('page-subtitle', $schedule->title)
@section('help-key', 'post-harvest')
@section('back', route('sm.hub', ['id' => $schedule->id]))

@php
    // An observation now carries clips as well as photos, and they share the
    // one column of paths — there is nowhere else to put them without a
    // migration, and everything reading that column (the Gallery, by way of
    // App\Support\SeasonMedia) expects a flat list. So which is which is read
    // off the name, exactly as PostHarvestController::kindOf() does it.
    $phKind = fn ($p) => \App\Http\Controllers\Manager\PostHarvestController::kindOf($p);
    $phUrl = fn ($p) => \App\Support\MediaStore::url($p);
@endphp

@push('head')
    <style>
        .ph-card { position: relative; }
        .ph-cat {
            display: inline-flex; align-items: center; gap: .3rem; border-radius: 999px;
            padding: .15rem .55rem; font-size: .69rem; font-weight: 700;
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
        .ph-figure dt { font-size: .66rem; font-weight: 700; letter-spacing: .03em; text-transform: uppercase; }
        .ph-figure dd { font-size: .95rem; font-weight: 800; }
        .ph-notes { font-size: .875rem; line-height: 1.5; }
        .ph-notes p { margin: .25rem 0; }
        .ph-notes ul { list-style: disc; padding-left: 1.25rem; }
        .ph-notes ol { list-style: decimal; padding-left: 1.25rem; }
        .ph-photo { max-height: 220px; border-radius: .6rem; }
        /* Height and look come from the shared rules in app.css. */
        .ph-quill .ql-toolbar { border-top-left-radius: .75rem; border-top-right-radius: .75rem; }
        .ph-video { width: 100%; max-height: 60vh; border-radius: .6rem; background: #000; object-fit: cover; }
        .ph-thumb { position: relative; aspect-ratio: 1; border-radius: .5rem; overflow: hidden; background: #f3f4f6; }
        .ph-thumb img, .ph-thumb video { width: 100%; height: 100%; object-fit: cover; }
        /* One square per attachment, whichever kind it is — a card with a clip
           and two photos should read as three things, not one odd one out. */
        .ph-tile { width: 100%; aspect-ratio: 1; object-fit: cover; border-radius: .5rem; background: #000; }
        .ph-thumb button { position: absolute; top: .2rem; right: .2rem; width: 1.4rem; height: 1.4rem; border-radius: 999px; background: rgba(17,24,39,.7); color: #fff; display: flex; align-items: center; justify-content: center; font-size: .81rem; line-height: 1; }
        .ph-gallery-thumbs { display: grid; grid-template-columns: repeat(3, 1fr); gap: .4rem; }
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

{{-- The list's own add button. It stands down while the season is empty:
     the empty panel below already offers exactly this, and two of the same
     button on one screen reads as a mistake. --}}
<button type="button" id="phAddTop" class="btn btn-primary w-full mb-4 inline-flex {{ $observations->isEmpty() ? 'hidden' : '' }}" data-ph-add>
    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
    Record an observation
</button>

<div class="space-y-3" id="phList" data-animate-list>
    @foreach ($observations as $o)
        @include('sm.partials.post-harvest-card', ['o' => $o, 'categories' => $categories, 'schedule' => $schedule])
    @endforeach
</div>
@include('partials.list-pager', ['noun' => 'observation', 'paginator' => $observations,
    'rowsUrl' => route('sm.post-harvest', ['id' => $schedule->id]) . '&rows=1'])

{{-- Empty state --}}
<div class="card p-8 text-center {{ $observations->isEmpty() ? '' : 'hidden' }}" id="phEmpty">
    <svg class="w-12 h-12 mx-auto text-gray-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z"/></svg>
    <p class="font-semibold text-gray-700 mt-3">Nothing recorded yet</p>
    <p class="text-sm text-gray-500 mt-1">After harvest, note what you actually got — yield, moisture, price, what went wrong — so next season is planned from real numbers.</p>
    <button type="button" class="btn btn-primary mt-4" data-ph-add>Record an observation</button>
</div>

{{-- The attach bar in the sheet would pull this in on its own, but a sheet
     inside another sheet is a strange place for it to live. Included here so
     it starts out where it ends up. --}}
@include('sm.partials.media-picker')

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

        <div class="mb-4">
            <label class="form-label" for="phTitle">What are you recording? <span class="text-red-500">*</span></label>
            <input type="text" id="phTitle" class="form-input" maxlength="191" placeholder="e.g. Lot A harvest — 92 sacks">
        </div>
        {{-- What kind of observation this is — a tag that opens a chooser
             (the owner's call, 2026-09-05): it decides what the rest of the
             form asks, and eight pills wrapped to three cramped lines on a
             phone. --}}
        <div class="mb-4">
            <label class="form-label">What kind of observation?</label>
            <button type="button" class="crop-tag" id="phCatBtn">
                <span class="crop-tag-e" id="phCatIcon">🌾</span>
                <span class="crop-tag-t" id="phCatNow">{{ $categories[array_key_first($categories)] }}</span>
                <svg class="crop-tag-c" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/></svg>
            </button>
            <input type="hidden" id="phCategory" value="{{ array_key_first($categories) }}">
        </div>
        <div class="mb-4">
            <label class="form-label" for="phDate">Date <span class="text-gray-400 font-normal">(optional)</span></label>
            @include('partials.date-tag', ['id' => 'phDate', 'empty' => 'Pick a date'])
        </div>
        <div class="mb-4">
            <label class="form-label">Lot</label>
            <select id="phLot" class="hidden" aria-hidden="true" tabindex="-1">
                <option value="">Whole schedule</option>
                @foreach ($schedule->lots as $lot)
                    <option value="{{ $lot->id }}">{{ $lot->lotName }}</option>
                @endforeach
            </select>
            <button type="button" class="crop-tag" id="phLotBtn">
                <span class="crop-tag-e">🌾</span>
                <span class="crop-tag-t is-none" id="phLotNow">Whole schedule</span>
                <svg class="crop-tag-c" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/></svg>
            </button>
        </div>

        {{-- Built from the category above. A harvest figure and a lesson for
             next season have almost nothing to ask in common, and asking both
             sets every time is how most of these records ended up mostly
             empty. See App\Support\PostHarvestFields. --}}
        <div id="phFields" class="mb-1"></div>
        <p class="form-hint -mt-1 mb-4" id="phValueHint"></p>
        <datalist id="phUnitOptions">
            <option value="kg"></option>
            <option value="sacks"></option>
            <option value="cavans"></option>
            <option value="tons"></option>
            <option value="pieces"></option>
        </datalist>

        <div class="mb-4">
            <label class="form-label">Notes</label>
            <div class="ph-quill"><div id="phNotesEditor"></div></div>
        </div>

        <div class="mb-2">
            <label class="form-label">Photos &amp; clips <span class="text-gray-400 font-normal">(add as many as you like)</span></label>
            @include('sm.partials.attach-bar', [
                'barId' => 'phAttach',
                'scheduleId' => $schedule->id,
                // One endpoint for both: the route is named image-upload and
                // routes/web.php is the integrator's, so the controller sorts
                // the photo from the clip by what arrives.
                'imageUrl' => route('sm.post-harvest.image-upload') . '?scheduleId=' . $schedule->id,
                'videoUrl' => route('sm.post-harvest.image-upload') . '?scheduleId=' . $schedule->id,
                'kinds' => 'image,video',
                'label' => 'Attach to this observation',
                'hint' => 'Snap the harvest, the sacks, or a problem worth remembering — or reuse something the season already has.',
            ])
            <div id="phCameraWrap" class="hidden mt-2">
                <video id="phVideo" autoplay playsinline muted class="ph-video"></video>
                <div class="flex gap-2 mt-1">
                    <button type="button" class="btn btn-primary btn-sm" id="phShutter">Capture</button>
                    <button type="button" class="btn btn-ghost btn-sm" id="phCameraCancel">Done</button>
                </div>
                <p class="form-hint">Capture as many as you need, then tap Done.</p>
            </div>
            <div id="phGallery" class="mt-2 grid grid-cols-3 gap-2"></div>
        </div>
    </div>
    <div class="sheet-footer">
        <button type="button" class="btn btn-ghost" data-sheet-close>Cancel</button>
        <button type="button" class="btn btn-primary" id="phSaveBtn">Save observation</button>
    </div>
</div>

@php
    $phCatIcons = ['yield' => '🌾', 'quality' => '✨', 'pest' => '🐛', 'weather' => '🌦️',
        'storage' => '🏬', 'market' => '💰', 'lesson' => '📔', 'other' => '📝'];
@endphp
{{-- Which kind of observation — the category tag's chooser. --}}
<div class="sheet hidden" id="phCatSheet" style="--sheet-width:24rem; z-index:150">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title">What kind of observation?</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body dt-rows" id="phCatList">
        @foreach ($categories as $key => $label)
            <button type="button" class="dt-row{{ $loop->first ? ' is-on' : '' }}" data-ph-cat="{{ $key }}" data-icon="{{ $phCatIcons[$key] ?? '📝' }}">
                <span class="dt-row-e">{{ $phCatIcons[$key] ?? '📝' }}</span>
                <span class="dt-row-body"><b>{{ $label }}</b></span>
                <svg class="dt-row-tick" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
            </button>
        @endforeach
    </div>
</div>

{{-- Which lot — the lot tag's chooser. --}}
<div class="sheet hidden" id="phLotSheet" style="--sheet-width:24rem; z-index:150">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title">Which lot?</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body dt-rows" id="phLotList">
        <button type="button" class="dt-row is-on" data-ph-lot="">
            <span class="dt-row-e">🗺️</span>
            <span class="dt-row-body"><b>Whole schedule</b><i>Not tied to one lot</i></span>
            <svg class="dt-row-tick" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
        </button>
        @foreach ($schedule->lots as $lot)
            <button type="button" class="dt-row" data-ph-lot="{{ $lot->id }}">
                <span class="dt-row-e">🌾</span>
                <span class="dt-row-body"><b>{{ $lot->lotName }}</b><i>{{ \App\Support\CropStages::label($lot->crop) ?: 'No crop set' }}</i></span>
                <svg class="dt-row-tick" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
            </button>
        @endforeach
    </div>
</div>

{{-- One chooser serves every category-driven select (wet or dry, severity,
     drying method…) — the rows are poured in when a tag is tapped. --}}
<div class="sheet hidden" id="phSelSheet" style="--sheet-width:24rem; z-index:150">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title" id="phSelTitle">Choose</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body dt-rows" id="phSelList"></div>
</div>
@endpush

@push('head')
@include('partials.tag-sheet-css')
<style>
    .ph-detail { display: flex; gap: .4rem; font-size: .78rem; }
    .ph-detail dt { color: var(--color-gray-400); }
    .ph-detail dd { color: var(--color-gray-800); font-weight: 600; }
    html.dark .ph-detail dd { color: #e5e9f5; }
</style>
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
            'images' => collect(! empty($o->imagePaths) ? $o->imagePaths : array_filter([$o->imagePath]))
                ->map(fn ($p) => ['type' => $phKind($p), 'path' => $p, 'url' => $phUrl($p)])
                ->values(),
            'imageUrl' => ! empty($o->imagePaths)
                ? \App\Support\MediaStore::url($o->imagePaths[0])
                : ($o->imagePath ? \App\Support\MediaStore::url($o->imagePath) : null),
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

    /** One square per attachment, whichever kind it is. Mirrors the Blade card. */
    const tile = (im) => im.type === 'video'
        ? `<video src="${escapeHtml(im.url)}" class="ph-tile" controls preload="metadata" playsinline></video>`
        : `<img src="${escapeHtml(im.url)}" alt="" class="ph-tile" loading="lazy">`;

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
                        <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 6h18M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6M8 6V4a2 2 0 012-2h4a2 2 0 012 2v2M10 11v6M14 11v6"/></svg>
                    </button>
                </div>
            </div>
            ${figures.length ? `<dl class="grid grid-cols-2 sm:grid-cols-4 gap-3 mt-3">${figures.map(([l, v, tone]) =>
                `<div class="ph-figure"><dt class="text-gray-400">${escapeHtml(l)}</dt><dd class="${tone}">${escapeHtml(v)}</dd></div>`).join('')}</dl>` : ''}
            ${(o.detailRows || []).length ? `<dl class="mt-2 grid gap-1">${(o.detailRows || []).map((d) =>
                `<div class="ph-detail"><dt>${escapeHtml(d.label)}:</dt><dd>${escapeHtml(d.value)}</dd></div>`).join('')}</dl>` : ''}
            ${o.buyer ? `<p class="text-sm text-gray-500 mt-2">Sold to <span class="font-semibold text-gray-700">${escapeHtml(o.buyer)}</span></p>` : ''}
            ${o.notes ? `<div class="ph-notes text-gray-600 mt-2">${o.notes}</div>` : ''}
            ${(o.images && o.images.length)
                ? `<div class="ph-gallery-thumbs mt-3">${o.images.map(tile).join('')}</div>`
                : (o.imageUrl ? `<img src="${escapeHtml(o.imageUrl)}" alt="" class="ph-photo mt-3" loading="lazy">` : '')}`;
        return el;
    }

    function refreshEmptyState() {
        const n = list.querySelectorAll('.ph-card').length;
        emptyEl.classList.toggle('hidden', n > 0);
        document.getElementById('phAddTop')?.classList.toggle('hidden', n === 0);
        // The card panel follows the page; the totals follow the season, and
        // a season with rows on page two is not an empty season.
        document.getElementById('phSummary').classList.toggle('hidden', SEASON.count === 0);
        recomputeSummary();
    }

    /* The season's totals, kept live without lying about the season.
     *
     * These used to be recomputed from OBS — the rows THIS PAGE is holding.
     * The page shows ten at a time, so a season with twelve observations
     * repainted its header as ten the moment anything was added, and one
     * whose first page had not been touched at all could read zero. The
     * server counts every row; this starts from that answer and moves it by
     * the one row the reader just changed. */
    const SEASON = {
        count: @json((int) $summary['count']),
        yields: @json((object) $summary['yields']),
        revenue: @json((float) ($summary['revenue'] ?? 0)),
        moistureSum: @json((float) (($summary['avgMoisture'] ?? 0) * $summary['count'])),
        moistureRows: @json((int) $summary['count']),
    };

    /** Add a row's numbers to the season (sign -1 takes them away again). */
    function applyToSeason(o, sign) {
        if (!o) return;
        SEASON.count = Math.max(0, SEASON.count + sign);
        if (o.yieldAmount !== null && o.yieldAmount !== undefined) {
            const unit = (o.yieldUnit || '').trim() || 'unit';
            const next = (SEASON.yields[unit] || 0) + sign * o.yieldAmount;
            if (next > 0.0001) SEASON.yields[unit] = next;
            else delete SEASON.yields[unit];
            if (o.pricePerUnit !== null && o.pricePerUnit !== undefined) {
                SEASON.revenue = Math.max(0, SEASON.revenue + sign * o.yieldAmount * o.pricePerUnit);
            }
        }
        if (o.moisturePercent !== null && o.moisturePercent !== undefined) {
            SEASON.moistureSum = Math.max(0, SEASON.moistureSum + sign * o.moisturePercent);
            SEASON.moistureRows = Math.max(0, SEASON.moistureRows + sign);
        }
    }

    /** Paint what the season stands at. */
    function recomputeSummary() {
        const cells = Object.entries(SEASON.yields).map(([unit, amount]) =>
            `<div class="ph-figure"><dt class="text-gray-400">Total (${escapeHtml(unit)})</dt><dd class="text-brand-700">${escapeHtml(qty(amount))}</dd></div>`);
        if (SEASON.revenue > 0) {
            cells.push(`<div class="ph-figure"><dt class="text-gray-400">Gross value</dt><dd class="text-brand-700">${escapeHtml(money(SEASON.revenue))}</dd></div>`);
        }
        if (SEASON.moistureRows > 0) {
            const avg = Math.round((SEASON.moistureSum / SEASON.moistureRows) * 10) / 10;
            cells.push(`<div class="ph-figure"><dt class="text-gray-400">Avg moisture</dt><dd class="text-gray-900">${avg}%</dd></div>`);
        }
        cells.push(`<div class="ph-figure"><dt class="text-gray-400">Observations</dt><dd class="text-gray-900" id="phSummaryCount">${SEASON.count}</dd></div>`);
        document.getElementById('phSummaryGrid').innerHTML = cells.join('');
    }

    /* ---- The form the category asks for -----------------------------------
     * One definition on the server (App\Support\PostHarvestFields) drives the
     * inputs here and the way the answers read on the card, so the two can
     * never drift apart. */
    const PH_FIELDS = @json(\App\Support\PostHarvestFields::all());

    function fieldHtml(f, value) {
        const v = value === null || value === undefined ? '' : String(value);
        const ph = f.placeholder ? ` placeholder="${escapeHtml(f.placeholder)}"` : '';
        let input;
        if (f.type === 'select') {
            /* The select stays as the value store (readFields and the save
               read it unchanged); the tag is its face, one shared sheet its
               list — the house idiom, not a dropdown. */
            const opts = Object.keys(f.options || {})
                .map((k) => `<option value="${escapeHtml(k)}"${v === k ? ' selected' : ''}>${escapeHtml(f.options[k])}</option>`)
                .join('');
            const said = (f.options && f.options[v]) ? f.options[v] : '';
            input = `<select class="hidden" aria-hidden="true" tabindex="-1" data-ph-field="${f.key}"><option value="">—</option>${opts}</select>
                <button type="button" class="crop-tag" data-ph-selbtn="${f.key}">
                    <span class="crop-tag-e">🔹</span>
                    <span class="crop-tag-t${said ? '' : ' is-none'}">${said ? escapeHtml(said) : 'Choose…'}</span>
                    <svg class="crop-tag-c" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/></svg>
                </button>`;
        } else if (f.type === 'textarea') {
            // A lesson is sentences, not a phrase — give it room to breathe.
            input = `<textarea class="form-textarea" data-ph-field="${f.key}" rows="3" maxlength="2000"${ph}>${escapeHtml(v)}</textarea>`;
        } else if (f.type === 'number' || f.type === 'money') {
            input = `<input type="number" class="form-input" data-ph-field="${f.key}" step="0.01" min="0" inputmode="decimal" value="${escapeHtml(v)}"${ph}>`;
        } else if (f.type === 'percent') {
            input = `<input type="number" class="form-input" data-ph-field="${f.key}" step="0.1" min="0" max="100" inputmode="decimal" value="${escapeHtml(v)}"${ph}>`;
        } else if (f.type === 'unit') {
            input = `<input type="text" class="form-input" data-ph-field="${f.key}" maxlength="24" list="phUnitOptions" value="${escapeHtml(v)}"${ph}>`;
        } else {
            input = `<input type="text" class="form-input" data-ph-field="${f.key}" maxlength="191" value="${escapeHtml(v)}"${ph}>`;
        }
        const suffix = f.type === 'percent' ? ' <span class="text-gray-400 font-normal">(%)</span>'
            : (f.type === 'money' ? ' <span class="text-gray-400 font-normal">(₱)</span>' : '');
        return `<div class="mb-3"><label class="form-label">${escapeHtml(f.label)}${suffix}</label>${input}</div>`;
    }

    /** Draw the inputs this category asks for, keeping anything already typed
     *  that the new category also asks for. */
    function paintFields(category, source) {
        const host = fld('phFields');
        const keep = source || readFields();
        host.innerHTML = (PH_FIELDS[category] || [])
            .map((f) => fieldHtml(f, keep[f.key]))
            .join('') || '<p class="form-hint">Nothing else to fill in — write it in the notes below.</p>';
        refreshValueHint();
    }

    /** Whatever the visible inputs currently hold, keyed by field. */
    function readFields() {
        const out = {};
        document.querySelectorAll('#phFields [data-ph-field]').forEach((el) => {
            const v = (el.value || '').trim();
            if (v !== '') out[el.getAttribute('data-ph-field')] = v;
        });
        return out;
    }

    function refreshValueHint() {
        const vals = readFields();
        const y = num(vals.yieldAmount);
        const p = num(vals.pricePerUnit);
        fld('phValueHint').textContent = (y !== null && p !== null)
            ? 'Gross value: ' + money(y * p)
            : '';
    }

    /* ---- the three choosers: category, lot, and the generic select ---- */
    function sayPhCat(cat) {
        const row = document.querySelector(`#phCatList [data-ph-cat="${cat}"]`);
        document.querySelectorAll('#phCatList [data-ph-cat]').forEach((r) => r.classList.toggle('is-on', r === row));
        const icon = fld('phCatIcon');
        const now = fld('phCatNow');
        if (icon) icon.textContent = row?.getAttribute('data-icon') || '📝';
        if (now) now.textContent = row?.querySelector('b')?.textContent || cat;
    }
    document.getElementById('phCatBtn')?.addEventListener('click', () => openSheet('phCatSheet'));
    document.getElementById('phCatList')?.addEventListener('click', (e) => {
        const row = e.target.closest('[data-ph-cat]');
        if (!row) return;
        const cat = row.getAttribute('data-ph-cat');
        fld('phCategory').value = cat;
        sayPhCat(cat);
        closeSheet('phCatSheet');
        paintFields(cat);
    });

    function sayPhLot() {
        const sel = fld('phLot');
        const now = fld('phLotNow');
        if (!sel || !now) return;
        const row = document.querySelector(`#phLotList [data-ph-lot="${sel.value}"]`);
        document.querySelectorAll('#phLotList [data-ph-lot]').forEach((r) => r.classList.toggle('is-on', r === row));
        now.textContent = sel.value ? (row?.querySelector('b')?.textContent || sel.options[sel.selectedIndex]?.text || 'Lot') : 'Whole schedule';
        now.classList.toggle('is-none', !sel.value);
    }
    document.getElementById('phLotBtn')?.addEventListener('click', () => openSheet('phLotSheet'));
    document.getElementById('phLotList')?.addEventListener('click', (e) => {
        const row = e.target.closest('[data-ph-lot]');
        if (!row) return;
        fld('phLot').value = row.getAttribute('data-ph-lot');
        sayPhLot();
        closeSheet('phLotSheet');
    });

    /* One sheet for every category-driven select — poured on each open. */
    let phSelKey = null;
    document.getElementById('phFields')?.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-ph-selbtn]');
        if (!btn) return;
        phSelKey = btn.getAttribute('data-ph-selbtn');
        const f = (PH_FIELDS[fld('phCategory').value] || []).find((x) => x.key === phSelKey);
        if (!f) return;
        const sel = document.querySelector(`#phFields select[data-ph-field="${phSelKey}"]`);
        const cur = sel ? sel.value : '';
        fld('phSelTitle').textContent = f.label;
        fld('phSelList').innerHTML = `
            <button type="button" class="dt-row${cur === '' ? ' is-on' : ''}" data-ph-sel="">
                <span class="dt-row-e">➖</span>
                <span class="dt-row-body"><b>Not saying</b></span>
                <svg class="dt-row-tick" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
            </button>`
            + Object.keys(f.options || {}).map((k) => `
            <button type="button" class="dt-row${cur === k ? ' is-on' : ''}" data-ph-sel="${escapeHtml(k)}">
                <span class="dt-row-e">🔹</span>
                <span class="dt-row-body"><b>${escapeHtml(f.options[k])}</b></span>
                <svg class="dt-row-tick" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
            </button>`).join('');
        openSheet('phSelSheet');
    });
    document.getElementById('phSelList')?.addEventListener('click', (e) => {
        const row = e.target.closest('[data-ph-sel]');
        if (!row || !phSelKey) return;
        const val = row.getAttribute('data-ph-sel');
        const sel = document.querySelector(`#phFields select[data-ph-field="${phSelKey}"]`);
        if (sel) {
            sel.value = val;
            const btn = document.querySelector(`#phFields [data-ph-selbtn="${phSelKey}"] .crop-tag-t`);
            const said = val ? (sel.options[sel.selectedIndex]?.text || val) : '';
            if (btn) { btn.textContent = said || 'Choose…'; btn.classList.toggle('is-none', !said); }
            sel.dispatchEvent(new Event('input', { bubbles: true }));
        }
        closeSheet('phSelSheet');
    });

    document.getElementById('phFields')?.addEventListener('input', refreshValueHint);

    // Everything hanging off this observation, held as {type, path, url} while
    // the sheet is open. Only the paths are ever saved.
    let phImages = [];
    function renderGallery() {
        const g = document.getElementById('phGallery');
        g.innerHTML = phImages.map((im, i) => {
            // Muted and preload-metadata: a thumbnail should show its first
            // frame, not start playing at whoever opened the sheet.
            const shot = im.type === 'video'
                ? `<video src="${escapeHtml(im.url)}" muted playsinline preload="metadata"></video>`
                : `<img src="${escapeHtml(im.url)}" alt="Attachment ${i + 1}">`;
            return `<div class="ph-thumb">${shot}` +
                `<button type="button" data-rm="${i}" aria-label="Remove attachment">&times;</button></div>`;
        }).join('');
        g.querySelectorAll('[data-rm]').forEach((b) => b.addEventListener('click', () => {
            phImages.splice(parseInt(b.getAttribute('data-rm'), 10), 1);
            renderGallery();
        }));
    }
    function setImages(list) {
        phImages = (list || []).filter((im) => im && im.path)
            .map((im) => ({ type: im.type || 'image', path: im.path, url: im.url }));
        renderGallery();
    }
    function addImage(im) {
        phImages.push({ type: im.type || 'image', path: im.path, url: im.url });
        renderGallery();
    }

    /* ---- Quill (WYSIWYG) for the observation notes, lazy-loaded from CDN ---- */
    let quill = null;
    function loadQuill() {
        if (window.Quill) return Promise.resolve();
        return new Promise((resolve, reject) => {
            const css = document.createElement('link');
            css.rel = 'stylesheet'; css.href = 'https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.snow.css';
            document.head.appendChild(css);
            const s = document.createElement('script');
            s.src = 'https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.min.js';
            s.onload = resolve; s.onerror = reject; document.head.appendChild(s);
        });
    }
    async function ensureQuill() {
        await loadQuill();
        if (!quill) {
            quill = new Quill('#phNotesEditor', {
                theme: 'snow', placeholder: 'What happened, what you would do differently…',
                modules: { toolbar: window.SM_RICH_TOOLBAR },
            });
            window.smQuillTouch?.(quill);
        }
        return quill;
    }
    function notesHtml() {
        if (!quill) return null;
        return quill.getText().trim() ? quill.root.innerHTML : null;
    }

    /* ---- The four ways in -------------------------------------------------
     * The shared bar does the choosing, the uploading and the recording; this
     * page only says what to do with whatever comes back. */
    const attachBar = document.getElementById('phAttach');
    attachBar.addEventListener('attach:add', (e) => {
        const im = e.detail;
        if (!im || !im.path) return;
        addImage(im);
        toast(im.type === 'video' ? 'Clip attached.' : 'Photo added.');
    });

    /* ---- Live-camera capture (getUserMedia on secure origins) --------------
     * The bar's own camera hands the job to the phone's camera app, which is
     * the better photographer by a mile. A desktop has no camera app to hand
     * it to — capture= there just opens a file picker — so the webcam preview
     * this page has always had stands in, and takes several shots in a row
     * without reopening anything. */
    let phStream = null;
    async function uploadImageFile(file) {
        const form = new FormData();
        form.append('image', file);
        const res = await fetch(URLS.upload, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, Accept: 'application/json' },
            body: form, credentials: 'same-origin',
        });
        const json = await res.json();
        if (!json.success) throw new Error(json.message || 'Upload failed.');
        addImage({ type: 'image', path: json.data.path, url: json.data.url });
    }
    function stopCamera() {
        if (phStream) { phStream.getTracks().forEach((t) => t.stop()); phStream = null; }
        const v = document.getElementById('phVideo'); if (v) v.srcObject = null;
        document.getElementById('phCameraWrap').classList.add('hidden');
    }
    attachBar.addEventListener('attach:camera', async (e) => {
        const cameraApp = window.matchMedia('(pointer: coarse)').matches;
        if (cameraApp || !navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) return;
        e.preventDefault();
        try {
            phStream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' }, audio: false });
            document.getElementById('phVideo').srcObject = phStream;
            document.getElementById('phCameraWrap').classList.remove('hidden');
        } catch (_) {
            // Denied, or no camera at all: hand it back to the bar's input.
            attachBar.querySelector('[data-ab-camera]')?.click();
        }
    });
    document.getElementById('phCameraCancel').addEventListener('click', stopCamera);
    document.getElementById('phShutter').addEventListener('click', () => {
        const v = document.getElementById('phVideo');
        if (!v || !v.videoWidth) return;
        const canvas = document.createElement('canvas');
        canvas.width = v.videoWidth; canvas.height = v.videoHeight;
        canvas.getContext('2d').drawImage(v, 0, 0);
        canvas.toBlob(async (blob) => {
            if (!blob) return;
            // Keep the camera running so several shots can be taken in a row.
            try { await uploadImageFile(new File([blob], 'observation-' + Date.now() + '.jpg', { type: 'image/jpeg' })); toast('Photo added'); }
            catch (err) { toast(err.message, 'error'); }
        }, 'image/jpeg', 0.9);
    });
    document.querySelectorAll('#phSheet [data-sheet-close]').forEach((b) => b.addEventListener('click', stopCamera));

    async function openPhSheet(o = null) {
        fld('phId').value = o ? o.id : '';
        fld('phSheetTitle').textContent = o ? 'Edit observation' : 'Record an observation';
        fld('phTitle').value = o ? o.title : '';
        const cat = o ? (o.category || 'yield') : 'yield';
        fld('phCategory').value = cat;
        sayPhCat(cat);
        // The columns and the JSON details are one set of answers as far as
        // the form is concerned.
        paintFields(cat, o ? Object.assign({}, o.details || {}, {
            yieldAmount: o.yieldAmount, yieldUnit: o.yieldUnit,
            moisturePercent: o.moisturePercent, pricePerUnit: o.pricePerUnit, buyer: o.buyer,
        }) : {});
        fld('phDate').value = o ? (o.observationDate || '') : new Date().toISOString().slice(0, 10);
        fld('phLot').value = o && o.lotId ? String(o.lotId) : '';
        sayPhLot();
        // A failed upload leaves its red row behind on purpose; it should not
        // still be there next time the sheet opens.
        window.smAttachBar?.(attachBar).reset();
        stopCamera();
        setImages(o ? (o.images || []) : []);
        refreshValueHint();
        openSheet('phSheet');
        // Notes round-trip as sanitised rich HTML through the WYSIWYG editor.
        try { const q = await ensureQuill(); q.root.innerHTML = o ? (o.notes || '') : ''; } catch (_) { /* editor optional */ }
    }

    document.querySelectorAll('[data-ph-add]').forEach((btn) => btn.addEventListener('click', () => openPhSheet(null)));

    document.getElementById('phSaveBtn').addEventListener('click', async () => {
        const id = fld('phId').value;
        const title = fld('phTitle').value.trim();
        if (!title) { toast('Give this observation a title.', 'error'); return; }

        const vals = readFields();
        // Five of these have had columns since the beginning; the rest are
        // whatever this category asked for and go in as details.
        const COLUMN_KEYS = ['yieldAmount', 'yieldUnit', 'moisturePercent', 'pricePerUnit', 'buyer'];
        const details = {};
        Object.keys(vals).forEach((k) => { if (!COLUMN_KEYS.includes(k)) details[k] = vals[k]; });

        const payload = {
            title,
            category: fld('phCategory').value,
            observationDate: fld('phDate').value || null,
            lotId: fld('phLot').value || null,
            yieldAmount: num(vals.yieldAmount),
            yieldUnit: (vals.yieldUnit || '').trim() || null,
            moisturePercent: num(vals.moisturePercent),
            pricePerUnit: num(vals.pricePerUnit),
            buyer: (vals.buyer || '').trim() || null,
            details: Object.keys(details).length ? details : null,
            // Rich HTML from the WYSIWYG; the server sanitises it (HtmlSanitizer::rich).
            notes: notesHtml(),
            imagePaths: phImages.map((im) => im.path),
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
                images: res.data.images || [],
                imageUrl: res.data.imageUrl,
            };
            // An edit replaces what the season was told about this row.
            applyToSeason(OBS[o.id], -1);
            applyToSeason(o, +1);
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
                applyToSeason(OBS[id], -1);
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
