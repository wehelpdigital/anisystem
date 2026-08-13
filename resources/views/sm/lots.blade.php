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
            <button type="button" class="btn btn-primary w-full sm:w-auto shrink-0" data-add-lot>
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14m-7-7h14"/></svg>
                Add Lot
            </button>
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
                <p class="text-sm text-gray-500 mb-4">Lots are the field areas this schedule covers. Activities attach to them.</p>
                <button type="button" class="btn btn-primary" data-add-lot>Add your first lot</button>
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
        <div>
            <label class="form-label">Crop <span class="text-gray-400 font-normal">(optional)</span></label>
            <div class="crop-pick" id="lotCropPick">
                @foreach (\App\Support\CropStages::options() as $c)
                    <button type="button" class="crop-opt" data-crop="{{ $c['value'] }}">
                        <span class="crop-emoji">{{ $c['icon'] }}</span>
                        <span>{{ $c['label'] }}</span>
                    </button>
                @endforeach
            </div>
            <input type="hidden" id="lotCrop" value="">
            <p class="form-hint">
                Currently: <strong id="lotCropNow" class="is-none">Not set</strong>.
                Sets the growth stages this lot is read against — tap a chosen crop again to clear it.
            </p>
        </div>

        <div>
            <label for="lotVariety" class="form-label">Variety <span class="text-gray-400 font-normal">(optional)</span></label>
            <input type="text" id="lotVariety" maxlength="255" class="form-input" placeholder="e.g. IR64">
        </div>

        <div>
            <label for="lotDayType" class="form-label">Day counter</label>
            <select id="lotDayType" class="form-select">
                <option value="DAP">DAP — Days After Planting</option>
                <option value="DAS">DAS / DAT — Seeded, then Transplanted</option>
            </select>
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
@endpush

@push('head')
<style>
    .crop-pick { display: flex; flex-wrap: wrap; gap: .4rem; }
    .crop-opt { display: inline-flex; align-items: center; gap: .35rem; padding: .4rem .7rem;
        border: 2px solid var(--color-gray-200); background: var(--color-white); border-radius: 999px;
        font-size: .8rem; font-weight: 600; color: #374151; cursor: pointer;
        transition: background .25s ease, border-color .25s ease, color .25s ease; }
    .crop-opt:hover { border-color: #a8cc7e; background: #f3f8ec; }
    .crop-opt.is-selected { background: #4a7c2a; border-color: #4a7c2a; color: #fff; }
    .crop-emoji { font-size: 1rem; line-height: 1; }
    .lot-crop-badge { display: inline-flex; align-items: center; gap: .25rem; }
    #lotCropNow { color: #3d6823; }
    #lotCropNow.is-none { color: var(--color-gray-400); font-weight: 500; }
    html.dark .crop-opt { background: #1c2416; border-color: #2b3a1c; color: #cdd8c0; }
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
        'dayType' => $l->dayType ?: 'DAS',
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

    const ensureLocations = () => {
        if (PH) return Promise.resolve(PH);
        if (!phPromise) {
            phPromise = fetch(PH_URL, { headers: { Accept: 'application/json' } })
                .then((r) => r.json())
                .then((data) => (PH = data || {}))
                .catch(() => (PH = {}));
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
        provinceSel.innerHTML = optionList(Object.keys(PH || {}).sort((a, b) => a.localeCompare(b)), selected || '');
        provinceSel.value = selected || '';
    };
    const fillTowns = (province, selected) => {
        townSel.innerHTML = optionList((PH && PH[province]) ? PH[province] : [], selected || '');
        townSel.disabled = !province;
        townSel.value = selected || '';
    };
    provinceSel.addEventListener('change', () => fillTowns(provinceSel.value, ''));
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
        const dayTypeBadge = (lot.dayType === 'DAP')
            ? '<span class="badge badge-gray">DAP</span>'
            : '<span class="badge badge-gray">DAS → DAT</span>';

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

                <div class="flex items-center gap-1.5 pt-3 border-t border-gray-100">
                    <button type="button" class="btn btn-white btn-sm" data-edit-lot="${lot.id}">Edit</button>
                    <button type="button" class="btn btn-ghost btn-sm px-2.5! text-red-500 hover:bg-red-50! ml-auto" data-delete-lot="${lot.id}" aria-label="Delete lot">
                        <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.9 12.1a2 2 0 01-2 1.9H7.9a2 2 0 01-2-1.9L5 7m3 0V5a2 2 0 012-2h4a2 2 0 012 2v2m-11 0h16m-10 4v6m4-6v6"/></svg>
                    </button>
                </div>
            </div>`;
    }

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
        setLotCrop(lot ? (lot.crop || '') : '');
        document.getElementById('lotDayType').value = lot ? (lot.dayType || 'DAS') : 'DAS';
        document.getElementById('lotBarangay').value = lot ? (lot.locBarangay || '') : '';
        document.getElementById('lotZone').value = lot ? (lot.locZone || '') : '';
        document.getElementById('lotNotes').value = lot ? (lot.notes || '') : '';
        // Province → town/city selects (async: the dataset loads once, cached).
        const prov = lot ? (lot.locProvince || '') : '';
        const town = lot ? (lot.locTown || '') : '';
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

    /* ---------------- Save ---------------- */

    /* One crop per lot, and tapping the chosen one again clears it — a lot
       whose crop was set by mistake needs a way back to "not set". */
    /** Which crop chip is lit, and what the form will send. */
    function setLotCrop(value) {
        const want = matchCrop(value);
        document.getElementById('lotCrop').value = want;
        let lit = null;
        document.querySelectorAll('#lotCropPick .crop-opt').forEach((b) => {
            const on = b.getAttribute('data-crop') === want;
            b.classList.toggle('is-selected', on);
            if (on) lit = b;
        });
        const say = document.getElementById('lotCropNow');
        if (say) {
            say.textContent = lit ? lit.textContent.trim() : 'Not set';
            say.classList.toggle('is-none', !lit);
        }
    }

    /**
     * A stored crop turned into the key of a chip. Exact key first, then a
     * loose match on the label, so "Rice (Palay)" or "RICE" still lights the
     * rice chip instead of leaving the row looking broken.
     */
    function matchCrop(value) {
        const v = String(value || '').trim().toLowerCase();
        if (!v) return '';
        const opts = [...document.querySelectorAll('#lotCropPick .crop-opt')];
        const exact = opts.find((b) => b.getAttribute('data-crop') === v);
        if (exact) return v;
        const loose = opts.find((b) => {
            const key = b.getAttribute('data-crop');
            const label = b.textContent.trim().toLowerCase();
            return v.includes(key) || label.includes(v) || v.includes(label);
        });
        return loose ? loose.getAttribute('data-crop') : '';
    }
    document.getElementById('lotCropPick')?.addEventListener('click', (e) => {
        const opt = e.target.closest('.crop-opt');
        if (!opt) return;
        const want = opt.getAttribute('data-crop');
        setLotCrop(document.getElementById('lotCrop').value === want ? '' : want);
    });

    document.getElementById('saveLotBtn').addEventListener('click', async (e) => {
        const btn = e.currentTarget;
        const id = document.getElementById('lotId').value;
        const body = {
            lotName: document.getElementById('lotName').value.trim(),
            lotSize: document.getElementById('lotSize').value || 0,
            lotSizeUnit: document.getElementById('lotSizeUnit').value,
            crop: document.getElementById('lotCrop').value || null,
            variety: document.getElementById('lotVariety').value.trim() || null,
            locBarangay: document.getElementById('lotBarangay').value.trim() || null,
            locZone: document.getElementById('lotZone').value.trim() || null,
            locTown: document.getElementById('lotTown').value.trim() || null,
            locProvince: document.getElementById('lotProvince').value.trim() || null,
            dayType: document.getElementById('lotDayType').value || 'DAS',
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
                dayType: res.data.dayType || 'DAS',
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
