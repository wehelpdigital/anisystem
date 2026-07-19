@extends('layouts.app')

@section('title', 'Materials — ' . $schedule->title)
@section('page-title', 'Materials')
@section('page-subtitle', $schedule->title)
@section('back', route('sm.hub', ['id' => $schedule->id]))

@section('content')
@include('sm.partials.module-header', ['schedule' => $schedule, 'module' => 'materials'])

{{-- Desktop add button --}}
<button type="button" class="btn btn-primary w-full mb-4 hidden md:inline-flex" data-material-add id="materialAddBtnDesktop">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
    Add Material
</button>

{{-- Search --}}
<div class="mb-4">
    <div class="relative">
        <svg class="w-5 h-5 text-gray-400 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 10.5a6.5 6.5 0 11-13 0 6.5 6.5 0 0113 0z"/></svg>
        <input type="text" id="materialsSearch" class="form-input pl-11 pr-10" placeholder="Search name, type, unit, description…" autocomplete="off">
        <button type="button" id="materialsSearchClear" class="hidden absolute right-2 top-1/2 -translate-y-1/2 p-1.5 rounded-full text-gray-400 hover:bg-gray-100" aria-label="Clear search">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
    </div>
    <p class="form-hint hidden" id="materialsSearchCount"></p>
</div>

{{-- Card list --}}
<div class="space-y-3" id="materialsList" data-animate-list>
    @foreach ($schedule->materials as $m)
        <div class="card p-4 material-card" data-id="{{ $m->id }}"
            data-search="{{ strtolower($m->materialName . ' ' . $m->materialType . ' ' . $m->unitOfMeasure . ' ' . ($m->description ?? '')) }}">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0 grow">
                    <div class="flex items-center gap-2 flex-wrap">
                        <h3 class="font-bold text-gray-900 leading-snug js-name">{{ $m->materialName }}</h3>
                        <span class="badge badge-blue capitalize js-type">{{ $m->materialType }}</span>
                    </div>
                    <p class="text-sm font-semibold text-brand-700 mt-1 js-price">
                        ₱ {{ number_format((float) $m->priceAmount, 2) }} per {{ rtrim(rtrim(number_format((float) $m->priceQuantity, 4, '.', ','), '0'), '.') }} {{ $m->unitOfMeasure }}
                    </p>
                    @if (filled($m->description))
                        <p class="text-sm text-gray-500 mt-1 js-desc">{{ $m->description }}</p>
                    @else
                        <p class="text-sm text-gray-500 mt-1 js-desc hidden"></p>
                    @endif
                </div>
                <div class="flex gap-1 shrink-0">
                    <button type="button" class="btn btn-sm btn-ghost js-edit" aria-label="Edit material">
                        <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    </button>
                    <button type="button" class="btn btn-sm btn-ghost text-red-600 js-delete" aria-label="Delete material">
                        <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </button>
                </div>
            </div>
        </div>
    @endforeach
</div>

{{-- Empty state --}}
<div class="card p-8 text-center {{ $schedule->materials->isEmpty() ? '' : 'hidden' }}" id="materialsEmpty">
    <svg class="w-12 h-12 mx-auto text-gray-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
    <p class="font-semibold text-gray-700 mt-3">No materials yet</p>
    <p class="text-sm text-gray-500 mt-1">Add fertilizers, sprays, seeds and other inputs so you can attach them to activities.</p>
    <button type="button" class="btn btn-primary mt-4" data-material-add>Add Material</button>
</div>

{{-- No search matches --}}
<div class="card p-6 text-center hidden" id="materialsNoMatch">
    <p class="text-sm text-gray-500">No materials match your search.</p>
</div>

{{-- Mobile FAB --}}
<button type="button" data-material-add aria-label="Add material"
    class="fixed bottom-24 right-4 z-30 w-14 h-14 rounded-full btn-primary shadow-lg md:hidden flex items-center justify-center bg-brand-600 text-white hover:bg-brand-700 active:bg-brand-800">
    <svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
</button>
@endsection

@push('sheets')
<div class="sheet hidden" id="materialSheet" style="--sheet-width:36rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title" id="materialSheetTitle">Add Material</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body">
        <input type="hidden" id="materialId" value="">
        <div class="mb-4">
            <label class="form-label" for="materialName">Material Name <span class="text-red-500">*</span></label>
            <input type="text" id="materialName" class="form-input" maxlength="255" placeholder="e.g. Urea 46-0-0">
        </div>
        <div class="mb-4">
            <label class="form-label" for="materialDescription">Description</label>
            <textarea id="materialDescription" class="form-textarea" rows="2" maxlength="2000" placeholder="Optional notes about this material…"></textarea>
        </div>
        <div class="grid grid-cols-2 gap-3 mb-4">
            <div>
                <label class="form-label" for="materialType">Type <span class="text-red-500">*</span></label>
                <select id="materialType" class="form-select">
                    @foreach ($allowedTypes as $t)
                        <option value="{{ $t }}">{{ ucfirst($t) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="form-label" for="materialUnit">Unit of Measure <span class="text-red-500">*</span></label>
                <select id="materialUnit" class="form-select">
                    @foreach ($allowedUnits as $u)
                        <option value="{{ $u }}">{{ $u }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="mb-2">
            <label class="form-label">Price <span class="text-red-500">*</span></label>
            <div class="flex items-center gap-2 flex-wrap">
                <span class="text-gray-600 font-semibold">₱</span>
                <input type="number" id="materialAmount" class="form-input flex-1 min-w-[7rem]" step="0.01" min="0" value="0" inputmode="decimal">
                <span class="text-gray-500 text-sm shrink-0">per</span>
                <input type="number" id="materialQuantity" class="form-input w-24" step="0.0001" min="0.0001" value="1" inputmode="decimal">
                <span class="text-sm font-semibold text-gray-700 shrink-0" id="materialUnitMirror">kg</span>
            </div>
            <p class="form-hint">e.g. ₱ 3,000.00 per 50 kg — the price of one purchase pack.</p>
        </div>
    </div>
    <div class="sheet-footer">
        <button type="button" class="btn btn-ghost" data-sheet-close>Cancel</button>
        <button type="button" class="btn btn-primary" id="materialSaveBtn">Save Material</button>
    </div>
</div>
@endpush

@push('scripts')
<script>
(function () {
    const SCHEDULE_ID = @json($schedule->id);
    const URLS = {
        store: @json(route('sm.materials.store')) + '?scheduleId=' + SCHEDULE_ID,
        update: (id) => @json(route('sm.materials.update')) + '?scheduleId=' + SCHEDULE_ID + '&id=' + id,
        destroy: (id) => @json(route('sm.materials.destroy')) + '?scheduleId=' + SCHEDULE_ID + '&id=' + id,
    };

    /* Local data map so edits don't need a round-trip. */
    @php
        $materialsSeed = $schedule->materials->mapWithKeys(fn ($mm) => [$mm->id => [
            'id' => $mm->id,
            'materialName' => $mm->materialName,
            'description' => $mm->description,
            'materialType' => $mm->materialType,
            'unitOfMeasure' => $mm->unitOfMeasure,
            'priceAmount' => (float) $mm->priceAmount,
            'priceQuantity' => (float) $mm->priceQuantity,
        ]]);
    @endphp
    const MATERIALS = @json($materialsSeed->isEmpty() ? new stdClass() : $materialsSeed);

    const list = document.getElementById('materialsList');
    const emptyEl = document.getElementById('materialsEmpty');
    const noMatchEl = document.getElementById('materialsNoMatch');
    const searchInput = document.getElementById('materialsSearch');
    const searchClear = document.getElementById('materialsSearchClear');
    const searchCount = document.getElementById('materialsSearchCount');

    const fmtQty = (v) => Number(v || 0).toLocaleString('en-PH', { maximumFractionDigits: 4 });

    function priceLine(m) {
        return fmtPeso(m.priceAmount) + ' per ' + fmtQty(m.priceQuantity) + ' ' + m.unitOfMeasure;
    }

    function searchKey(m) {
        return (m.materialName + ' ' + m.materialType + ' ' + m.unitOfMeasure + ' ' + (m.description || '')).toLowerCase();
    }

    function renderCard(m) {
        const el = document.createElement('div');
        el.className = 'card p-4 material-card';
        el.dataset.id = m.id;
        el.dataset.search = searchKey(m);
        el.innerHTML = `
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0 grow">
                    <div class="flex items-center gap-2 flex-wrap">
                        <h3 class="font-bold text-gray-900 leading-snug js-name">${escapeHtml(m.materialName)}</h3>
                        <span class="badge badge-blue capitalize js-type">${escapeHtml(m.materialType)}</span>
                    </div>
                    <p class="text-sm font-semibold text-brand-700 mt-1 js-price">${escapeHtml(priceLine(m))}</p>
                    <p class="text-sm text-gray-500 mt-1 js-desc ${m.description ? '' : 'hidden'}">${escapeHtml(m.description || '')}</p>
                </div>
                <div class="flex gap-1 shrink-0">
                    <button type="button" class="btn btn-sm btn-ghost js-edit" aria-label="Edit material">
                        <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    </button>
                    <button type="button" class="btn btn-sm btn-ghost text-red-600 js-delete" aria-label="Delete material">
                        <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </button>
                </div>
            </div>`;
        return el;
    }

    function refreshEmptyState() {
        const total = list.querySelectorAll('.material-card').length;
        emptyEl.classList.toggle('hidden', total > 0);
    }

    /* ------------------------------ search ------------------------------ */
    function applySearch() {
        const q = searchInput.value.trim().toLowerCase();
        const cards = [...list.querySelectorAll('.material-card')];
        let shown = 0;
        cards.forEach((c) => {
            const match = !q || (c.dataset.search || '').includes(q);
            c.classList.toggle('hidden', !match);
            if (match) shown++;
        });
        searchClear.classList.toggle('hidden', !q);
        if (q) {
            searchCount.textContent = shown + ' of ' + cards.length + ' material' + (cards.length === 1 ? '' : 's') + ' shown';
            searchCount.classList.remove('hidden');
        } else {
            searchCount.classList.add('hidden');
        }
        noMatchEl.classList.toggle('hidden', !(q && shown === 0 && cards.length > 0));
    }
    searchInput.addEventListener('input', applySearch);
    searchClear.addEventListener('click', () => { searchInput.value = ''; applySearch(); searchInput.focus(); });

    /* ------------------------------- sheet ------------------------------ */
    const fld = (id) => document.getElementById(id);
    const unitMirror = fld('materialUnitMirror');
    fld('materialUnit').addEventListener('change', () => { unitMirror.textContent = fld('materialUnit').value; });

    function openMaterialSheet(m = null) {
        fld('materialId').value = m ? m.id : '';
        fld('materialSheetTitle').textContent = m ? 'Edit Material' : 'Add Material';
        fld('materialName').value = m ? m.materialName : '';
        fld('materialDescription').value = m ? (m.description || '') : '';
        fld('materialType').value = m ? m.materialType : 'granular';
        fld('materialUnit').value = m ? m.unitOfMeasure : 'kg';
        fld('materialAmount').value = m ? m.priceAmount : 0;
        fld('materialQuantity').value = m ? m.priceQuantity : 1;
        unitMirror.textContent = fld('materialUnit').value;
        openSheet('materialSheet');
    }

    /* Reset to "add" mode whenever the sheet is opened via data-sheet-open. */
    document.querySelectorAll('[data-material-add]').forEach((btn) => {
        btn.addEventListener('click', () => openMaterialSheet(null));
    });

    /* ------------------------------- save ------------------------------- */
    document.getElementById('materialSaveBtn').addEventListener('click', async () => {
        const id = fld('materialId').value;
        const payload = {
            materialName: fld('materialName').value.trim(),
            description: fld('materialDescription').value.trim() || null,
            materialType: fld('materialType').value,
            unitOfMeasure: fld('materialUnit').value,
            priceAmount: fld('materialAmount').value,
            priceQuantity: fld('materialQuantity').value,
        };
        if (!payload.materialName) { toast('Material name is required.', 'error'); return; }
        if (payload.priceAmount === '' || Number(payload.priceAmount) < 0) { toast('Enter a valid price amount.', 'error'); return; }
        if (!(Number(payload.priceQuantity) > 0)) { toast('Price quantity must be greater than zero.', 'error'); return; }

        const btn = document.getElementById('materialSaveBtn');
        btn.disabled = true;
        try {
            const res = await api(id ? URLS.update(id) : URLS.store, { method: id ? 'PUT' : 'POST', body: payload });
            const m = {
                id: res.data.id,
                materialName: res.data.materialName,
                description: res.data.description,
                materialType: res.data.materialType,
                unitOfMeasure: res.data.unitOfMeasure,
                priceAmount: Number(res.data.priceAmount),
                priceQuantity: Number(res.data.priceQuantity),
            };
            MATERIALS[m.id] = m;
            const fresh = renderCard(m);
            const existing = list.querySelector('.material-card[data-id="' + m.id + '"]');
            if (existing) existing.replaceWith(fresh);
            else list.prepend(fresh);
            refreshEmptyState();
            applySearch();
            closeSheet('materialSheet');
            toast(res.message);
        } catch (e) {
            toast(e.message, 'error');
        } finally {
            btn.disabled = false;
        }
    });

    /* --------------------------- edit / delete -------------------------- */
    list.addEventListener('click', async (e) => {
        const card = e.target.closest('.material-card');
        if (!card) return;
        const id = card.dataset.id;

        if (e.target.closest('.js-edit')) {
            openMaterialSheet(MATERIALS[id] || null);
            return;
        }
        if (e.target.closest('.js-delete')) {
            const name = MATERIALS[id] ? MATERIALS[id].materialName : 'this material';
            const ok = await confirmAction({
                title: 'Delete material?',
                message: '"' + name + '" will be removed from this schedule.',
                detail: 'Activities that already reference it keep their history.',
                confirmText: 'Delete',
            });
            if (!ok) return;
            try {
                const res = await api(URLS.destroy(id), { method: 'DELETE' });
                delete MATERIALS[id];
                card.remove();
                refreshEmptyState();
                applySearch();
                toast(res.message);
            } catch (err) {
                toast(err.message, 'error');
            }
        }
    });
})();
</script>
@endpush
