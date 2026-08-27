@extends(request()->boolean('partial') ? 'layouts.partial' : 'layouts.app')

@section('title', 'Lots — ' . $schedule->title)
@section('page-title', 'Lots')
@section('page-subtitle', $schedule->title)
@section('help-key', 'lots')
@section('back', route('sm.hub', ['id' => $schedule->id]))

@section('content')
    @include('sm.partials.module-header', ['schedule' => $schedule, 'module' => 'lots'])

    <div>
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
            <p class="text-sm text-gray-500">
                <span id="lotCount" class="font-bold text-gray-900">0</span> <span id="lotCountLabel">lots</span> on this schedule
            </p>
            {{-- A lot is a piece of the farm itself, not a day's work on it:
                 adding one is the owner's to do. A worker sees the lots and
                 no button that would only be refused — the same way the
                 Workers module and "+ Version" are not drawn for them. --}}
            @unless (\App\Support\WorkerContext::inWorkerContext())
                <button type="button" class="btn btn-primary w-full sm:w-auto shrink-0" data-add-lot>
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14m-7-7h14"/></svg>
                    Add Lot
                </button>
            @endunless
        </div>

        {{-- Full-width responsive grid — one card per lot. The empty state below
             is a sibling so renderList()'s innerHTML reset can't wipe it. --}}
        <div id="lotsList" class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3" data-animate-list></div>

        <div id="lotsEmpty" class="card hidden">
            <div class="card-body text-center py-12">
                <div class="mx-auto w-14 h-14 rounded-2xl bg-brand-50 flex items-center justify-center mb-3">
                    <svg class="w-7 h-7 text-brand-600" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 20l-5-2V6l5 2m0 12l6-2m-6 2V8m6 10l5 2V8l-5-2m0 12V6M9 8l6-2"/></svg>
                </div>
                <h2 class="font-bold text-gray-900 mb-1">No lots yet</h2>
                @if (\App\Support\WorkerContext::inWorkerContext())
                    <p class="text-sm text-gray-500">Lots are the field areas this schedule covers. The farm owner adds them.</p>
                @else
                    <p class="text-sm text-gray-500 mb-4">Lots are the field areas this schedule covers. Activities attach to them.</p>
                    <button type="button" class="btn btn-primary" data-add-lot>Add your first lot</button>
                @endif
            </div>
        </div>

    </div>

@endsection

@push('sheets')
<div class="sheet hidden" id="lotSheet" style="--sheet-width:36rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title" id="lotSheetTitle">Add Lot</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body space-y-4">
        <input type="hidden" id="lotId" value="">

        <div>
            <label for="lotName" class="form-label">Lot Name <span class="text-red-500">*</span></label>
            <input type="text" id="lotName" maxlength="255" class="form-input" placeholder="e.g. Lot A — riverside">
        </div>

        <div class="grid grid-cols-2 gap-3">
            <div>
                <label for="lotSize" class="form-label">Size</label>
                <input type="number" id="lotSize" min="0" step="0.0001" class="form-input" placeholder="0">
            </div>
            <div>
                <label for="lotSizeUnit" class="form-label">Unit</label>
                <select id="lotSizeUnit" class="form-select">
                    <option value="hectare">Hectare</option>
                    <option value="sqm">Square meter</option>
                    <option value="acre">Acre</option>
                </select>
            </div>
        </div>

        {{-- What is actually growing here. It belongs to the lot, not to the
             season: one farm can have corn on the upper block and rice in the
             paddy, and a single answer for the whole schedule was wrong for
             one of them. It is also what makes a growth stage answerable. --}}
        {{-- One tag, not eighty-five chips.
             The list is the whole of Philippine farming now; laid out flat it
             would bury the form it was meant to sit in. So the field says what
             is chosen, and the choosing happens in a sheet with a search box
             and the crops in their families. --}}
        <div>
            <label class="form-label">Crop <span class="text-gray-400 font-normal">(optional)</span></label>
            <button type="button" class="crop-tag" id="lotCropBtn">
                <span class="crop-tag-e" id="lotCropIcon">🌱</span>
                <span class="crop-tag-t" id="lotCropNow">Choose a crop</span>
                <svg class="crop-tag-c" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/></svg>
            </button>
            <input type="hidden" id="lotCrop" value="">
            <p class="form-hint">Sets the growth stages this lot is read against.</p>
        </div>

        {{-- HOW LONG, or HOW OLD — never both.
             A crop that is harvested once is planned by its days to maturity;
             a tree is read by its age. Which of the two is asked follows from
             the crop, and the other is not merely hidden but cleared, so a
             lot cannot carry an answer to a question it is no longer being
             asked. --}}
        <div id="lotMaturityWrap" class="hidden">
            <label for="lotMaturity" class="form-label">Days to maturity <span class="text-gray-400 font-normal">(optional)</span></label>
            <input type="number" id="lotMaturity" min="1" max="999" class="form-input" placeholder="">
            <p class="form-hint" id="lotMaturityHint"></p>
        </div>

        <div id="lotTreeWrap" class="hidden">
            <label class="form-label">How old are the trees?</label>
            <div class="tree-age">
                <div>
                    <input type="number" id="lotTreeYears" min="0" max="120" class="form-input" placeholder="0">
                    <span class="tree-age-u">years</span>
                </div>
                <div>
                    <input type="number" id="lotTreeMonths" min="0" max="11" class="form-input" placeholder="0">
                    <span class="tree-age-u">months</span>
                </div>
            </div>
            <p class="form-hint" id="lotTreeHint"></p>
            {{-- The age is what somebody standing in the orchard knows; the
                 date it implies is what is stored, so it stays right next
                 season instead of quietly ageing into a lie. --}}
            <input type="hidden" id="lotTreePlanted" value="">
        </div>

        <div>
            <label for="lotVariety" class="form-label">Variety <span class="text-gray-400 font-normal">(optional)</span></label>
            <input type="text" id="lotVariety" maxlength="255" class="form-input" placeholder="e.g. IR64">
        </div>

        {{-- A tree has no day counter, so it is not asked for one: its stages
             are read against its age, which the field above already knows. --}}
        <div id="lotDayTypeWrap">
            <label for="lotDayType" class="form-label">Day counter</label>
            {{-- Three answers, because a field is established in one of three
                 ways and each is counted differently. Direct-seeded rice never
                 gets a DAT number at all, and reading it against a transplanted
                 calendar puts every stage in the wrong week. --}}
            <select id="lotDayType" class="form-select">
                <option value="DAT">DAS → DAT — sown, then transplanted</option>
                <option value="DAS">DAS only — direct seeded (DSR)</option>
                <option value="DAP">DAP — days after planting</option>
            </select>
            <p class="form-hint" id="lotDayTypeHint"></p>
            <p class="form-hint">How this lot's day numbers are counted. <strong>DAP</strong> is a single count from planting. <strong>DAS/DAT</strong> counts DAS from sowing, then flips to DAT once you flag the transplant activity.</p>
        </div>

        {{-- Lot address — town + province power the local weather forecast. --}}
        <div class="rounded-xl border border-gray-100 p-3 space-y-3">
            <div class="flex items-center gap-1.5">
                <svg class="w-4 h-4 text-brand-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a2 2 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                <span class="form-label mb-0">Location <span class="text-gray-400 font-normal">(optional)</span></span>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label for="lotBarangay" class="form-label text-xs">Barangay</label>
                    <input type="text" id="lotBarangay" maxlength="120" class="form-input" placeholder="e.g. San Jose">
                </div>
                <div>
                    <label for="lotZone" class="form-label text-xs">Zone #</label>
                    <input type="text" id="lotZone" maxlength="60" class="form-input" placeholder="e.g. 3">
                </div>
                <div>
                    <label for="lotProvince" class="form-label text-xs">Province</label>
                    <select id="lotProvince" class="form-select"><option value="">— Select —</option></select>
                </div>
                <div>
                    <label for="lotTown" class="form-label text-xs">Town / City</label>
                    <select id="lotTown" class="form-select" disabled><option value="">Select province first</option></select>
                </div>
            </div>
            <p class="form-hint">Add the town &amp; province to see this lot's 5-day weather on your dashboard.</p>
        </div>

        {{-- Day 0 (DAS) and transplant (DAT) anchors are set on the activities
             themselves (via the "Mark as Day 0" / "Mark as transplant" toggles),
             so they live in one place only. No date fields on the lot. --}}

        <div>
            <label for="lotNotes" class="form-label">Notes</label>
            <textarea id="lotNotes" rows="3" maxlength="2000" class="form-textarea" placeholder="Anything worth remembering about this lot…"></textarea>
        </div>
    </div>
    <div class="sheet-footer">
        <button type="button" class="btn btn-ghost" data-sheet-close>Cancel</button>
        <button type="button" id="saveLotBtn" class="btn btn-primary">Save Lot</button>
    </div>
</div>

{{-- WHICH CROP.

     Eighty-five of them, in their families, with a search box — because a
     farmer looking for sayote should type "sayote" rather than read past
     twelve fruit vegetables to find it, and one looking for "something in
     the ampalaya line" should be able to browse the family instead.

     Stacked over the lot sheet rather than replacing it, so the half-filled
     form is still behind and comes back untouched. --}}
<div class="sheet hidden" id="cropPickSheet" style="--sheet-width:30rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title">Choose a crop</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body">
        <div class="crop-search">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z"/></svg>
            <input type="text" id="cropSearch" class="form-input" autocomplete="off"
                   placeholder="Search — palay, sayote, mangga…">
            <button type="button" class="crop-search-x hidden" id="cropSearchX" aria-label="Clear">✕</button>
        </div>

        {{-- "None" is a real answer: a lot may genuinely not have a crop set
             yet, and taking that away would leave no way back to it. --}}
        <button type="button" class="crop-row is-none" data-crop="">
            <span class="crop-row-e">🌱</span>
            <span class="crop-row-t"><b>Not set</b><small>No growth-stage guidance for this lot</small></span>
        </button>

        <div id="cropPickList">
            @foreach (\App\Support\CropStages::grouped() as $group => $rows)
                <div class="crop-group" data-crop-group>
                    <p class="crop-group-h">{{ $group }}</p>
                    @foreach ($rows as $c)
                        @php
                            // Worked out here rather than inline: an @else glued
                            // to the end of a word is not a directive to Blade,
                            // it is the six characters "@else" printed out.
                            $years = $c['bearingAt'] ? rtrim(rtrim(number_format($c['bearingAt'] / 12, 1), '0'), '.') : null;
                            $says = $c['perennial']
                                ? ($years ? 'Tree — bears at about ' . $years . ' years old' : 'Tree — read by its age')
                                : trim(($c['maturity'] ? $c['maturity'] . ' days to harvest' : '') . ' · counted in ' . $c['counter'], ' ·');
                        @endphp
                        <button type="button" class="crop-row" data-crop="{{ $c['value'] }}"
                                data-find="{{ strtolower($c['label'] . ' ' . $group) }}"
                                data-tree="{{ $c['perennial'] ? '1' : '' }}"
                                data-maturity="{{ $c['maturity'] ?? '' }}"
                                data-bearing="{{ $c['bearingAt'] ?? '' }}"
                                data-counter="{{ $c['counter'] }}">
                            <span class="crop-row-e">{{ $c['icon'] }}</span>
                            <span class="crop-row-t">
                                <b>{{ $c['label'] }}</b>
                                <small>{{ $says }}</small>
                            </span>
                            <svg class="crop-row-tick hidden" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        </button>
                    @endforeach
                </div>
            @endforeach
        </div>
        <p class="crop-none hidden" id="cropPickNone">Nothing matches that. Try the local name, or pick “Vegetables — mixed”.</p>
    </div>
</div>
@endpush

@push('head')
<style>
    /* A field that is still filling itself, saying so.
     *
     * A disabled select already reads as "not yet" — this adds the movement
     * that separates "still coming" from "not for you". Slow and shallow: it
     * is a field waiting, not a page loading. */
    .form-select.is-waiting { animation: lotWait 1.4s ease-in-out infinite; }
    @keyframes lotWait { 0%, 100% { opacity: .55; } 50% { opacity: .85; } }
    @media (prefers-reduced-motion: reduce) {
        .form-select.is-waiting { animation: none; opacity: .6; }
    }

    /* THE CROP TAG — one field that says what is chosen and opens the list. */
    .crop-tag { display: flex; align-items: center; gap: .5rem; width: 100%;
        padding: .55rem .7rem; border-radius: .75rem; cursor: pointer; text-align: left;
        border: 1px solid var(--color-gray-200); background: var(--color-white);
        transition: border-color .28s cubic-bezier(.22,1,.36,1), background .28s cubic-bezier(.22,1,.36,1); }
    .crop-tag:hover { border-color: var(--color-brand-300); background: var(--color-brand-50); }
    .crop-tag-e { font-size: 1.1rem; line-height: 1; flex: none; }
    .crop-tag-t { flex: 1 1 auto; min-width: 0; font-size: .9rem; font-weight: 700; color: #3d6823;
        overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .crop-tag-t.is-none { color: var(--color-gray-400); font-weight: 500; }
    .crop-tag-c { width: 1rem; height: 1rem; flex: none; color: var(--color-gray-400); }
    html.dark .crop-tag { background: #1c2416; border-color: #2b3a1c; }

    /* THE PICKER — a search box, then the families. */
    .crop-search { position: relative; margin-bottom: .6rem; }
    .crop-search svg { position: absolute; left: .65rem; top: 50%; transform: translateY(-50%);
        width: 1rem; height: 1rem; color: var(--color-gray-400); pointer-events: none; }
    .crop-search .form-input { padding-left: 2.1rem; padding-right: 2.1rem; }
    .crop-search-x { position: absolute; right: .45rem; top: 50%; transform: translateY(-50%);
        width: 1.5rem; height: 1.5rem; border-radius: 999px; font-size: .75rem;
        color: var(--color-gray-400); cursor: pointer; }
    .crop-search-x:hover { background: var(--color-gray-100); color: var(--color-gray-700); }
    .crop-group-h { font-size: .68rem; font-weight: 800; letter-spacing: .04em; text-transform: uppercase;
        color: var(--color-gray-400); padding: .7rem .2rem .25rem; }
    .crop-row { display: flex; align-items: center; gap: .65rem; width: 100%; text-align: left;
        padding: .5rem .45rem; border-radius: .7rem; cursor: pointer;
        transition: background .2s ease; }
    .crop-row:hover { background: var(--color-gray-50); }
    .crop-row.is-on { background: var(--color-brand-50); }
    .crop-row-e { font-size: 1.15rem; line-height: 1; flex: none; width: 1.6rem; text-align: center; }
    .crop-row-t { flex: 1 1 auto; min-width: 0; }
    .crop-row-t b { display: block; font-size: .87rem; font-weight: 700; color: var(--color-gray-900);
        overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .crop-row-t small { display: block; font-size: .69rem; color: var(--color-gray-400); }
    .crop-row-tick { width: 1.1rem; height: 1.1rem; flex: none; color: var(--color-brand-600); }
    .crop-row.is-none { border: 1px dashed var(--color-gray-200); margin-bottom: .2rem; }
    .crop-none { text-align: center; font-size: .82rem; color: var(--color-gray-400); padding: 1.4rem .5rem; }
    .crop-group[hidden], .crop-row[hidden] { display: none; }
    html.dark .crop-row:hover { background: #1c2416; }
    html.dark .crop-row.is-on { background: #25311b; }
    html.dark .crop-row-t b { color: #e8efe1; }
    @media (prefers-reduced-motion: reduce) { .crop-tag, .crop-row { transition: none; } }

    /* HOW OLD — two numbers that mean one thing, so they share a line. */
    .tree-age { display: grid; grid-template-columns: 1fr 1fr; gap: .5rem; }
    .tree-age > div { position: relative; }
    .tree-age .form-input { padding-right: 3.6rem; }
    .tree-age-u { position: absolute; right: .7rem; top: 50%; transform: translateY(-50%);
        font-size: .75rem; font-weight: 600; color: var(--color-gray-400); pointer-events: none; }

    .crop-pick { display: flex; flex-wrap: wrap; gap: .4rem; }
    .crop-opt { display: inline-flex; align-items: center; gap: .35rem; padding: .4rem .7rem;
        border: 2px solid var(--color-gray-200); background: var(--color-white); border-radius: 999px;
        font-size: .8rem; font-weight: 600; color: #374151; cursor: pointer;
        transition: background .25s ease, border-color .25s ease, color .25s ease; }
    .crop-opt:hover { border-color: #a8cc7e; background: #f3f8ec; }
    .crop-emoji { font-size: 1rem; line-height: 1; }
    .lot-crop-badge { display: inline-flex; align-items: center; gap: .25rem; }
    #lotCropNow { color: #3d6823; }
    #lotCropNow.is-none { color: var(--color-gray-400); font-weight: 500; }
    html.dark .crop-opt { background: #1c2416; border-color: #2b3a1c; color: #cdd8c0; }
    /* Last, and one class heavier than the dark-mode rule above it. Tapping a
       crop did select it — the chip just kept the unselected colours, because
       `html.dark .crop-opt` tied with `.crop-opt.is-selected` and won on
       source order. A tick makes the state readable without relying on colour
       alone. */
    .crop-pick .crop-opt.is-selected,
    .crop-pick .crop-opt.is-selected:hover,
    html.dark .crop-pick .crop-opt.is-selected {
        background: #4a7c2a; border-color: #4a7c2a; color: #fff;
        box-shadow: 0 2px 10px rgb(74 124 42 / .32);
    }
    .crop-pick .crop-opt.is-selected::after {
        content: ''; width: .95rem; height: .95rem; margin-left: .1rem; flex: none;
        background: currentColor; border-radius: 999px;
        -webkit-mask: var(--tick) center / .7rem no-repeat; mask: var(--tick) center / .7rem no-repeat;
    }
    .crop-pick { --tick: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='4' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M5 13l4 4L19 7'/%3E%3C/svg%3E"); }
    @media (prefers-reduced-motion: reduce) { .crop-opt { transition: none; } }
</style>
@endpush

@push('scripts')
@php
    $jsLots = $schedule->lots->map(fn ($l) => [
        'id' => $l->id,
        'lotName' => $l->lotName,
        'lotSize' => $l->lotSize,
        'lotSizeUnit' => $l->lotSizeUnit,
        'variety' => $l->variety,
        // Normalised, not raw: a lot whose crop was stored as free text (an
        // import, or an older build) would light no chip at all, which reads
        // exactly like a picker that does not work.
        'crop' => \App\Support\CropStages::normalize($l->crop),
        'cropLabel' => \App\Support\CropStages::label($l->crop),
        'cropIcon' => \App\Support\CropStages::icon($l->crop),
        'locBarangay' => $l->locBarangay,
        'locZone' => $l->locZone,
        'locTown' => $l->locTown,
        'locProvince' => $l->locProvince,
        'fullAddress' => $l->full_address,
        'dayType' => $l->dayType ?: 'DAT',
        'notes' => $l->notes,
    ])->values();
@endphp
<script>
(() => {
const __init = () => {
    const SCHEDULE_ID = {{ $schedule->id }};
    const DAY_TYPE = @json($schedule->dayType);
    let LOTS = @json($jsLots);

    // Date fields (.cal-only): open the native calendar on click and block
    // manual typing, so the date always comes from the picker.
    if (!window.__calOnlyBound) {
        window.__calOnlyBound = true;
        document.addEventListener('click', (e) => {
            const inp = e.target.closest && e.target.closest('input.cal-only[type="date"]');
            if (inp && typeof inp.showPicker === 'function') { try { inp.showPicker(); } catch (_) {} }
        });
        document.addEventListener('keydown', (e) => {
            const inp = e.target.closest && e.target.closest('input.cal-only[type="date"]');
            if (inp && !['Tab', 'Escape', 'Enter'].includes(e.key)) e.preventDefault();
        });
    }

    const list = document.getElementById('lotsList');
    const empty = document.getElementById('lotsEmpty');

    const UNIT_LABELS = { hectare: 'ha', sqm: 'sqm', acre: 'ac' };

    /* ---- Philippine province → town/city cascading dropdowns ---- */
    const PH_URL = @json(asset('data/ph-locations.json'));
    const provinceSel = document.getElementById('lotProvince');
    const townSel = document.getElementById('lotTown');
    let PH = null, phPromise = null;

    /* WHILE THE LIST IS COMING.
     *
     * The eighty-seven provinces and their towns arrive as one file, and
     * until it lands both dropdowns are empty. They used to sit there
     * looking ready: tapping one opened nothing and closed again, which
     * reads as a broken control rather than as a list still on its way.
     *
     * So they say what is true. Coming: shut, and the word says why. Failed:
     * shut, and the word says that too — silence was the worst of the three,
     * because the fetch swallows its own error and an empty province list is
     * indistinguishable from a farm with no provinces in it.
     */
    const sayLoading = (state) => {
        const words = {
            loading: 'Loading the list…',
            failed: 'Could not load the list',
        };
        [provinceSel, townSel].forEach((sel) => {
            sel.classList.toggle('is-waiting', state === 'loading');
        });
        if (state === 'ready') return;
        provinceSel.disabled = true;
        townSel.disabled = true;
        provinceSel.innerHTML = `<option value="">${words[state]}</option>`;
        townSel.innerHTML = '<option value="">Select province first</option>';
    };

    const ensureLocations = () => {
        if (PH) return Promise.resolve(PH);
        if (!phPromise) {
            sayLoading('loading');
            phPromise = fetch(PH_URL, { headers: { Accept: 'application/json' } })
                .then((r) => r.json())
                .then((data) => {
                    PH = data || {};
                    // The province list is open; the town list waits for a
                    // province, which is what its own disable already says.
                    provinceSel.classList.remove('is-waiting');
                    townSel.classList.remove('is-waiting');
                    // Painted now rather than at the next sheet open, or the
                    // field goes on saying "Loading…" about a list that is
                    // already sitting in memory behind it.
                    fillProvinces(provinceSel.value || '');
                    return PH;
                })
                .catch(() => { PH = {}; sayLoading('failed'); return PH; });
        }
        return phPromise;
    };
    const optionList = (values, selected) => {
        let has = false;
        let html = '<option value="">— Select —</option>';
        values.forEach((v) => { if (v === selected) has = true; html += `<option value="${escapeHtml(v)}">${escapeHtml(v)}</option>`; });
        // Preserve a saved value (e.g. older free-text) that isn't in the list.
        if (selected && !has) html += `<option value="${escapeHtml(selected)}">${escapeHtml(selected)}</option>`;
        return html;
    };
    const fillProvinces = (selected) => {
        // Nothing to paint yet: leave whatever sayLoading() has written, or
        // the field flashes an empty "— Select —" that is not the truth.
        if (!PH) return;
        provinceSel.disabled = false;
        provinceSel.innerHTML = optionList(Object.keys(PH).sort((a, b) => a.localeCompare(b)), selected || '');
        provinceSel.value = selected || '';
    };
    const fillTowns = (province, selected) => {
        if (!PH) return;
        townSel.innerHTML = province
            ? optionList(PH[province] || [], selected || '')
            : '<option value="">Select province first</option>';
        // Locked until there is a province to narrow it to — a town list of
        // every town in the country is not a list anybody can use.
        townSel.disabled = !province;
        townSel.value = province ? (selected || '') : '';
    };
    provinceSel.addEventListener('change', () => fillTowns(provinceSel.value, ''));
    // Warmed on arrival, so by the time the sheet opens the list is usually
    // already here and nobody sees the waiting state at all.
    ensureLocations(); // warm the cache

    const fmtDate = (iso) => {
        if (!iso) return '';
        return new Date(`${iso}T00:00:00`).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
    };
    const fmtSize = (v) => {
        const n = parseFloat(v);
        return Number.isFinite(n) ? n.toLocaleString('en-PH', { maximumFractionDigits: 4 }) : '0';
    };
    // Mirrors AsScheduleLot::getFullAddressAttribute so a freshly-saved lot shows
    // its address without a reload.
    const composeAddress = (l) => [
        l.locBarangay ? 'Brgy. ' + l.locBarangay : null,
        l.locZone ? 'Zone ' + l.locZone : null,
        l.locTown || null,
        l.locProvince || null,
    ].filter(Boolean).join(', ');

    // Initials for the avatar (first letters of up to two name words, e.g.
    // "Masin 3" → "M3", "Lot A — riverside" → "LA").
    function lotInitials(name) {
        const parts = String(name || '').trim().split(/\s+/).filter(Boolean);
        const ini = parts.slice(0, 2).map((p) => p[0] || '').join('');
        return (ini || '?').toUpperCase();
    }

    function lotCardHtml(lot) {
        // Same golden-angle hue the lot gets on its activity cards, so the colour
        // reads as "this lot" consistently across modules.
        const hue = ((Number(lot.id) || 0) * 137) % 360;
        const sizeText = `${fmtSize(lot.lotSize)} ${escapeHtml(UNIT_LABELS[lot.lotSizeUnit] || lot.lotSizeUnit || '')}`.trim();
        const cropBadge = lot.cropLabel
            ? `<span class="badge badge-green lot-crop-badge"><span>${lot.cropIcon || '🌱'}</span>${escapeHtml(lot.cropLabel)}</span>`
            : '';
        const variety = lot.variety ? `
            <span class="badge badge-green">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 21c0-7 4-13 14-16-1 10-6 15-13 15m-1 1c2-5 5-8 9-10"/></svg>
                ${escapeHtml(lot.variety)}
            </span>` : '';
        // Three answers, the same three the sheet offers. Saying "DAS → DAT"
        // for anything that was not DAP is how a lot the grower had just
        // changed to direct seeding went on claiming it transplants.
        const DAY_TYPE_BADGE = { DAP: 'DAP', DAS: 'DSR · DAS only', DAT: 'DAS → DAT' };
        const dayTypeBadge = '<span class="badge badge-gray">'
            + (DAY_TYPE_BADGE[String(lot.dayType || 'DAT').toUpperCase()] || DAY_TYPE_BADGE.DAT)
            + '</span>';

        return `
            <div class="card-body h-full flex flex-col py-4! gap-3">
                <div class="flex items-start gap-3">
                    <span class="w-11 h-11 rounded-xl flex items-center justify-center text-white font-bold text-sm shrink-0" style="background:hsl(${hue}, 55%, 40%)" aria-hidden="true">${escapeHtml(lotInitials(lot.lotName))}</span>
                    <div class="min-w-0 grow">
                        <h3 class="font-bold text-gray-900 truncate">${escapeHtml(lot.lotName)}</h3>
                        <p class="text-sm text-gray-600 mt-0.5"><span class="font-semibold text-gray-900">${sizeText}</span></p>
                    </div>
                </div>

                <div class="min-w-0 grow space-y-1.5">
                    <div class="flex flex-wrap gap-1.5">${cropBadge}${variety}${dayTypeBadge}</div>
                    ${lot.fullAddress ? `<p class="text-xs text-gray-500 flex items-start gap-1.5"><svg class="w-3.5 h-3.5 mt-px shrink-0 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a2 2 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg><span>${escapeHtml(lot.fullAddress)}</span></p>` : ''}
                    ${lot.notes ? `<p class="text-xs text-gray-500 line-clamp-2">${escapeHtml(lot.notes)}</p>` : ''}
                </div>

                ${MAY_EDIT_LOTS ? `
                <div class="flex items-center gap-1.5 pt-3 border-t border-gray-100">
                    <button type="button" class="btn btn-white btn-sm" data-edit-lot="${lot.id}">Edit</button>
                    <button type="button" class="btn btn-ghost btn-sm px-2.5! text-red-500 hover:bg-red-50! ml-auto" data-delete-lot="${lot.id}" aria-label="Delete lot">
                        <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 6h18M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6M8 6V4a2 2 0 012-2h4a2 2 0 012 2v2M10 11v6M14 11v6"/></svg>
                    </button>
                </div>` : ''}
            </div>`;
    }

    /* A lot is a piece of the farm itself. Editing or removing one is the
       owner's, the same as adding: a worker who cannot add a lot has no
       business being offered a Delete beside every one of them. */
    const MAY_EDIT_LOTS = @json(! \App\Support\WorkerContext::inWorkerContext());

    function renderList() {
        list.innerHTML = '';
        LOTS.forEach((lot) => {
            const card = document.createElement('div');
            card.className = 'card h-full';   // h-full → equal-height cards across a grid row
            card.dataset.lotCard = lot.id;
            card.innerHTML = lotCardHtml(lot);
            list.appendChild(card);
        });
        empty.classList.toggle('hidden', LOTS.length > 0);
        const countEl = document.getElementById('lotCount');
        if (countEl) countEl.textContent = LOTS.length;
        const labelEl = document.getElementById('lotCountLabel');
        if (labelEl) labelEl.textContent = LOTS.length === 1 ? 'lot' : 'lots';
    }

    /* ---------------- Sheet open / fill ---------------- */

    async function openLotSheet(lot = null) {
        document.getElementById('lotSheetTitle').textContent = lot ? 'Edit Lot' : 'Add Lot';
        document.getElementById('lotId').value = lot ? lot.id : '';
        document.getElementById('lotName').value = lot ? (lot.lotName || '') : '';
        document.getElementById('lotSize').value = lot ? parseFloat(lot.lotSize) || 0 : '';
        document.getElementById('lotSizeUnit').value = lot ? (lot.lotSizeUnit || 'hectare') : 'hectare';
        document.getElementById('lotVariety').value = lot ? (lot.variety || '') : '';
        // The crop first: it decides which of the timing questions is asked,
        // and setLotCrop clears whichever one no longer applies.
        setLotCrop(lot ? (lot.crop || '') : '');
        document.getElementById('lotMaturity').value = lot && lot.daysToMaturity ? lot.daysToMaturity : '';
        treeAge(lot && lot.treeAgeMonths ? Number(lot.treeAgeMonths) : 0);
        // TREE is not one of the counter's three answers; a tree lot simply
        // does not show the field, and its stored value stays as it is.
        const dt = lot ? (lot.dayType || 'DAT') : 'DAT';
        document.getElementById('lotDayType').value = dt === 'TREE' ? 'DAT' : dt;
        sayDayType();
        document.getElementById('lotBarangay').value = lot ? (lot.locBarangay || '') : '';
        document.getElementById('lotZone').value = lot ? (lot.locZone || '') : '';
        document.getElementById('lotNotes').value = lot ? (lot.notes || '') : '';
        // Province → town/city selects (async: the dataset loads once, cached).
        const prov = lot ? (lot.locProvince || '') : '';
        const town = lot ? (lot.locTown || '') : '';
        /* Painted once the list is actually here.
         *
         * This used to paint before the fetch and again after, so on a cold
         * open the two fields rendered empty and enabled for the length of
         * the round trip — which is the whole complaint: they looked ready,
         * and tapping them did nothing. Now they say they are loading until
         * they have something to show, and are painted once. */
        fillProvinces(prov);
        fillTowns(prov, town);
        openSheet('lotSheet');
        await ensureLocations();
        fillProvinces(prov);
        fillTowns(prov, town);
    }

    document.addEventListener('click', async (e) => {
        if (e.target.closest('[data-add-lot]')) {
            openLotSheet();
            return;
        }

        const editBtn = e.target.closest('[data-edit-lot]');
        if (editBtn) {
            const lot = LOTS.find((l) => String(l.id) === editBtn.getAttribute('data-edit-lot'));
            if (lot) openLotSheet(lot);
            return;
        }

        const delBtn = e.target.closest('[data-delete-lot]');
        if (delBtn) {
            const id = delBtn.getAttribute('data-delete-lot');
            const lot = LOTS.find((l) => String(l.id) === id);
            const ok = await confirmAction({
                title: 'Delete lot?',
                message: `"${lot?.lotName || 'This lot'}" will be removed from the schedule.`,
                detail: 'Existing data tied to it is preserved.',
                confirmText: 'Delete',
            });
            if (!ok) return;
            try {
                const res = await api(`{{ route('sm.lots.destroy') }}?scheduleId=${SCHEDULE_ID}&id=${id}`, { method: 'DELETE' });
                toast(res.message);
                LOTS = LOTS.filter((l) => String(l.id) !== id);
                renderList();
            } catch (err) {
                toast(err.message, 'error');
            }
        }
    });

    /** Say in words what the chosen counter will do, so it is not a guess. */
    const DAY_TYPE_SAYS = {
        DAT: 'Counts DAS from day zero, then restarts as DAT on the transplant date. Stages read against the transplanted calendar once it does.',
        DAS: 'One count from sowing, all season. Stages read against the direct-seeded calendar — a transplant date is ignored.',
        DAP: 'One count from planting, all season.',
    };
    function sayDayType() {
        const sel = document.getElementById('lotDayType');
        const hint = document.getElementById('lotDayTypeHint');
        if (sel && hint) hint.textContent = DAY_TYPE_SAYS[sel.value] || '';
    }
    document.getElementById('lotDayType')?.addEventListener('change', sayDayType);

    /* ---------------- Save ---------------- */

    /* ---------------- Which crop, and what that means ----------------
     *
     * One crop per lot. The tag says what is chosen; the sheet does the
     * choosing. What follows the choice is the point: a crop that is
     * harvested once is planned by its days to maturity, and a tree is read
     * by its age, so the form asks whichever of the two applies and clears
     * the other — a lot must not carry an answer to a question it is no
     * longer being asked.
     */
    const cropRow = (key) => key
        ? document.querySelector(`#cropPickList .crop-row[data-crop="${CSS.escape(key)}"]`)
        : null;

    /** Which crop is chosen, and what the form will send. */
    function setLotCrop(value) {
        const want = matchCrop(value);
        document.getElementById('lotCrop').value = want;
        const row = cropRow(want);

        const say = document.getElementById('lotCropNow');
        const emoji = document.getElementById('lotCropIcon');
        say.textContent = row ? row.querySelector('b').textContent.trim() : 'Choose a crop';
        say.classList.toggle('is-none', !row);
        emoji.textContent = row ? row.querySelector('.crop-row-e').textContent.trim() : '🌱';

        document.querySelectorAll('#cropPickSheet .crop-row').forEach((b) => {
            const on = b.getAttribute('data-crop') === want;
            b.classList.toggle('is-on', on);
            b.querySelector('.crop-row-tick')?.classList.toggle('hidden', !on);
        });

        sayCropTiming(row);
    }

    /** Show the one question this crop is actually asked, and hide the rest. */
    function sayCropTiming(row) {
        const isTree = !!row?.getAttribute('data-tree');
        const maturity = row?.getAttribute('data-maturity') || '';
        const bearing = Number(row?.getAttribute('data-bearing') || 0);

        document.getElementById('lotMaturityWrap').classList.toggle('hidden', !row || isTree);
        document.getElementById('lotTreeWrap').classList.toggle('hidden', !isTree);
        // A tree has no day counter — its stages are read against its age.
        document.getElementById('lotDayTypeWrap').classList.toggle('hidden', isTree);

        if (row && !isTree) {
            const box = document.getElementById('lotMaturity');
            box.placeholder = maturity ? `${maturity} — the usual for this crop` : '';
            document.getElementById('lotMaturityHint').textContent = maturity
                ? `Leave it empty and ${maturity} days is assumed. Varieties are sold by their duration — put yours in and every growth stage moves with it.`
                : 'Put in your variety’s duration and every growth stage moves with it.';
        }
        if (isTree) {
            document.getElementById('lotTreeHint').textContent = bearing
                ? `This one usually starts bearing at about ${(bearing / 12).toFixed(1).replace(/\.0$/, '')} years old.`
                : 'Its age is what the growth-stage guidance is read against.';
        }
        // Clearing the one that no longer applies, so nothing stale is sent.
        if (isTree) document.getElementById('lotMaturity').value = '';
        else { treeAge(0); }
    }

    /** The tree's age, in the two boxes and in the hidden date they imply. */
    function treeAge(months) {
        const y = document.getElementById('lotTreeYears');
        const m = document.getElementById('lotTreeMonths');
        if (!months) { y.value = ''; m.value = ''; document.getElementById('lotTreePlanted').value = ''; return; }
        y.value = Math.floor(months / 12);
        m.value = months % 12;
        stampTreeDate();
    }

    /** Turn the typed age into the planting date that will be stored. */
    function stampTreeDate() {
        const y = Number(document.getElementById('lotTreeYears').value || 0);
        const m = Number(document.getElementById('lotTreeMonths').value || 0);
        const months = (y * 12) + m;
        const out = document.getElementById('lotTreePlanted');
        if (!months && !document.getElementById('lotTreeYears').value && !document.getElementById('lotTreeMonths').value) {
            out.value = '';
            return;
        }
        const d = new Date();
        d.setMonth(d.getMonth() - months);
        out.value = d.toISOString().slice(0, 10);
    }
    ['lotTreeYears', 'lotTreeMonths'].forEach((id) => {
        document.getElementById(id)?.addEventListener('input', stampTreeDate);
    });

    /**
     * A stored crop turned into a key in the list. Exact first, then the
     * whole label — the old loose "contains" match turned "corn" into
     * whichever corn happened to be first once there were three of them.
     */
    function matchCrop(value) {
        const v = String(value || '').trim().toLowerCase();
        if (!v) return '';
        if (cropRow(v)) return v;
        const rows = [...document.querySelectorAll('#cropPickList .crop-row')];
        const byLabel = rows.find((b) => b.querySelector('b').textContent.trim().toLowerCase() === v);
        return byLabel ? byLabel.getAttribute('data-crop') : '';
    }

    document.getElementById('lotCropBtn')?.addEventListener('click', () => {
        openSheet('cropPickSheet');
        // Straight into the search box on a keyboard; on a phone the field is
        // left alone so the list is not immediately hidden behind one.
        if (!window.matchMedia('(hover: none)').matches) {
            setTimeout(() => document.getElementById('cropSearch')?.focus(), 280);
        }
    });

    document.getElementById('cropPickSheet')?.addEventListener('click', (e) => {
        const row = e.target.closest('.crop-row');
        if (!row) return;
        setLotCrop(row.getAttribute('data-crop') || '');
        closeSheet('cropPickSheet');
    });

    /* Searching hides rows, and a family with nothing left hides too — a
       heading standing over an empty space reads as a broken list. */
    const cropSearch = document.getElementById('cropSearch');
    const cropSift = () => {
        const q = (cropSearch.value || '').trim().toLowerCase();
        document.getElementById('cropSearchX').classList.toggle('hidden', !q);
        let shown = 0;
        document.querySelectorAll('#cropPickList [data-crop-group]').forEach((g) => {
            let left = 0;
            g.querySelectorAll('.crop-row').forEach((r) => {
                const hit = !q || (r.getAttribute('data-find') || '').includes(q);
                r.hidden = !hit;
                if (hit) left++;
            });
            g.hidden = left === 0;
            shown += left;
        });
        document.getElementById('cropPickNone').classList.toggle('hidden', shown > 0);
    };
    cropSearch?.addEventListener('input', cropSift);
    document.getElementById('cropSearchX')?.addEventListener('click', () => {
        cropSearch.value = '';
        cropSift();
        cropSearch.focus();
    });

    document.getElementById('saveLotBtn').addEventListener('click', async (e) => {
        const btn = e.currentTarget;
        const id = document.getElementById('lotId').value;
        const body = {
            lotName: document.getElementById('lotName').value.trim(),
            lotSize: document.getElementById('lotSize').value || 0,
            lotSizeUnit: document.getElementById('lotSizeUnit').value,
            crop: document.getElementById('lotCrop').value || null,
            // The server decides which of these two survives, from the crop —
            // this only sends what the form was actually showing.
            daysToMaturity: Number(document.getElementById('lotMaturity').value) || null,
            treePlantedAt: document.getElementById('lotTreePlanted').value || null,
            variety: document.getElementById('lotVariety').value.trim() || null,
            locBarangay: document.getElementById('lotBarangay').value.trim() || null,
            locZone: document.getElementById('lotZone').value.trim() || null,
            locTown: document.getElementById('lotTown').value.trim() || null,
            locProvince: document.getElementById('lotProvince').value.trim() || null,
            dayType: document.getElementById('lotDayType').value || 'DAT',
            notes: document.getElementById('lotNotes').value || null,
        };

        if (!body.lotName) {
            toast('Lot name is required.', 'error');
            document.getElementById('lotName').focus();
            return;
        }

        const url = id
            ? `{{ route('sm.lots.update') }}?scheduleId=${SCHEDULE_ID}&id=${id}`
            : `{{ route('sm.lots.store') }}?scheduleId=${SCHEDULE_ID}`;

        btn.disabled = true;
        try {
            const res = await api(url, { method: id ? 'PUT' : 'POST', body });
            toast(res.message);
            const saved = {
                id: res.data.id,
                lotName: res.data.lotName,
                lotSize: res.data.lotSize,
                lotSizeUnit: res.data.lotSizeUnit,
                crop: res.data.crop,
                cropLabel: res.data.cropLabel,
                cropIcon: res.data.cropIcon,
                variety: res.data.variety,
                locBarangay: res.data.locBarangay,
                locZone: res.data.locZone,
                locTown: res.data.locTown,
                locProvince: res.data.locProvince,
                fullAddress: composeAddress(res.data),
                dayType: res.data.dayType || 'DAT',
                notes: res.data.notes,
            };
            const idx = LOTS.findIndex((l) => String(l.id) === String(saved.id));
            if (idx >= 0) LOTS[idx] = saved; else LOTS.push(saved);
            renderList();
            closeSheet('lotSheet');
        } catch (err) {
            toast(err.message, 'error');
        } finally {
            btn.disabled = false;
        }
    });

    renderList();
};
    // First load: wait for app.js (deferred) to define the globals.
    // SPA injection: document is already complete, so run now.
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', __init, { once: true });
    else __init();
})();
</script>
@endpush
