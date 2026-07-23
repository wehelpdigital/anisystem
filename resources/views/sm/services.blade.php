@extends(request()->boolean('partial') ? 'layouts.partial' : 'layouts.app')

@section('title', 'Services — ' . $schedule->title)
@section('page-title', 'Services')
@section('page-subtitle', $schedule->title)
@section('back', route('sm.hub', ['id' => $schedule->id]))

@section('content')
@include('sm.partials.module-header', ['schedule' => $schedule, 'module' => 'services'])

{{-- Desktop add button --}}
<button type="button" class="btn btn-primary w-full mb-4 hidden md:inline-flex" data-service-add>
    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
    Add Service
</button>

{{-- Card list --}}
<div class="space-y-3" id="servicesList" data-animate-list>
    @foreach ($schedule->services as $s)
        <div class="card p-4 service-card" data-id="{{ $s->id }}">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0 grow">
                    <h3 class="font-bold text-gray-900 leading-snug js-name">{{ $s->serviceName }}</h3>
                    <p class="text-sm font-semibold text-brand-700 mt-1 js-cost">₱ {{ number_format((float) $s->serviceCost, 2) }}</p>
                    @if (filled($s->description))
                        <p class="text-sm text-gray-500 mt-1 js-desc">{{ $s->description }}</p>
                    @else
                        <p class="text-sm text-gray-500 mt-1 js-desc hidden"></p>
                    @endif
                </div>
                <div class="flex gap-1 shrink-0">
                    <button type="button" class="btn btn-sm btn-ghost js-edit" aria-label="Edit service">
                        <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    </button>
                    <button type="button" class="btn btn-sm btn-ghost text-red-600 js-delete" aria-label="Delete service">
                        <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </button>
                </div>
            </div>
        </div>
    @endforeach
</div>

{{-- Empty state --}}
<div class="card p-8 text-center {{ $schedule->services->isEmpty() ? '' : 'hidden' }}" id="servicesEmpty">
    <svg class="w-12 h-12 mx-auto text-gray-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17L17.25 21A2.652 2.652 0 0021 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 11-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 004.486-6.336l-3.276 3.277a3.004 3.004 0 01-2.25-2.25l3.276-3.276a4.5 4.5 0 00-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085"/></svg>
    <p class="font-semibold text-gray-700 mt-3">No services yet</p>
    <p class="text-sm text-gray-500 mt-1">Add outside services — tractor rental, drone spray, hauling — so you can attach them to activities.</p>
    <button type="button" class="btn btn-primary mt-4" data-service-add>Add Service</button>
</div>

{{-- Mobile FAB --}}
<button type="button" data-service-add aria-label="Add service"
    class="fixed bottom-24 right-4 z-30 w-14 h-14 rounded-full btn-primary shadow-lg md:hidden flex items-center justify-center bg-brand-600 text-white hover:bg-brand-700 active:bg-brand-800">
    <svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
</button>
@endsection

@push('sheets')
<div class="sheet hidden" id="serviceSheet" style="--sheet-width:32rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title" id="serviceSheetTitle">Add Service</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body">
        <input type="hidden" id="serviceId" value="">
        <div class="mb-4">
            <label class="form-label" for="serviceName">Service Name <span class="text-red-500">*</span></label>
            <input type="text" id="serviceName" class="form-input" maxlength="255" placeholder="e.g. Tractor Rental">
        </div>
        <div class="mb-4">
            <label class="form-label" for="serviceDescription">Description</label>
            <textarea id="serviceDescription" class="form-textarea" rows="2" maxlength="2000" placeholder="Optional notes about this service…"></textarea>
        </div>
        <div class="mb-2">
            <label class="form-label" for="serviceCost">Cost <span class="text-red-500">*</span></label>
            <div class="flex items-center gap-2">
                <span class="text-gray-600 font-semibold">₱</span>
                <input type="number" id="serviceCost" class="form-input" step="0.01" min="0" value="0" inputmode="decimal">
            </div>
        </div>
    </div>
    <div class="sheet-footer">
        <button type="button" class="btn btn-ghost" data-sheet-close>Cancel</button>
        <button type="button" class="btn btn-primary" id="serviceSaveBtn">Save Service</button>
    </div>
</div>
@endpush

@push('scripts')
<script>
(function () {
    const SCHEDULE_ID = @json($schedule->id);
    const URLS = {
        store: @json(route('sm.services.store')) + '?scheduleId=' + SCHEDULE_ID,
        update: (id) => @json(route('sm.services.update')) + '?scheduleId=' + SCHEDULE_ID + '&id=' + id,
        destroy: (id) => @json(route('sm.services.destroy')) + '?scheduleId=' + SCHEDULE_ID + '&id=' + id,
    };

    @php
        $servicesSeed = $schedule->services->mapWithKeys(fn ($ss) => [$ss->id => [
            'id' => $ss->id,
            'serviceName' => $ss->serviceName,
            'description' => $ss->description,
            'serviceCost' => (float) $ss->serviceCost,
        ]]);
    @endphp
    const SERVICES = @json($servicesSeed->isEmpty() ? new stdClass() : $servicesSeed);

    const list = document.getElementById('servicesList');
    const emptyEl = document.getElementById('servicesEmpty');
    const fld = (id) => document.getElementById(id);

    function renderCard(s) {
        const el = document.createElement('div');
        el.className = 'card p-4 service-card';
        el.dataset.id = s.id;
        el.innerHTML = `
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0 grow">
                    <h3 class="font-bold text-gray-900 leading-snug js-name">${escapeHtml(s.serviceName)}</h3>
                    <p class="text-sm font-semibold text-brand-700 mt-1 js-cost">${escapeHtml(fmtPeso(s.serviceCost))}</p>
                    <p class="text-sm text-gray-500 mt-1 js-desc ${s.description ? '' : 'hidden'}">${escapeHtml(s.description || '')}</p>
                </div>
                <div class="flex gap-1 shrink-0">
                    <button type="button" class="btn btn-sm btn-ghost js-edit" aria-label="Edit service">
                        <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    </button>
                    <button type="button" class="btn btn-sm btn-ghost text-red-600 js-delete" aria-label="Delete service">
                        <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </button>
                </div>
            </div>`;
        return el;
    }

    function refreshEmptyState() {
        emptyEl.classList.toggle('hidden', list.querySelectorAll('.service-card').length > 0);
    }

    function openServiceSheet(s = null) {
        fld('serviceId').value = s ? s.id : '';
        fld('serviceSheetTitle').textContent = s ? 'Edit Service' : 'Add Service';
        fld('serviceName').value = s ? s.serviceName : '';
        fld('serviceDescription').value = s ? (s.description || '') : '';
        fld('serviceCost').value = s ? s.serviceCost : 0;
        openSheet('serviceSheet');
    }

    document.querySelectorAll('[data-service-add]').forEach((btn) => {
        btn.addEventListener('click', () => openServiceSheet(null));
    });

    document.getElementById('serviceSaveBtn').addEventListener('click', async () => {
        const id = fld('serviceId').value;
        const payload = {
            serviceName: fld('serviceName').value.trim(),
            description: fld('serviceDescription').value.trim() || null,
            serviceCost: fld('serviceCost').value,
        };
        if (!payload.serviceName) { toast('Service name is required.', 'error'); return; }
        if (payload.serviceCost === '' || Number(payload.serviceCost) < 0) { toast('Enter a valid cost.', 'error'); return; }

        const btn = document.getElementById('serviceSaveBtn');
        btn.disabled = true;
        try {
            const res = await api(id ? URLS.update(id) : URLS.store, { method: id ? 'PUT' : 'POST', body: payload });
            const s = {
                id: res.data.id,
                serviceName: res.data.serviceName,
                description: res.data.description,
                serviceCost: Number(res.data.serviceCost),
            };
            SERVICES[s.id] = s;
            const fresh = renderCard(s);
            const existing = list.querySelector('.service-card[data-id="' + s.id + '"]');
            if (existing) existing.replaceWith(fresh);
            else list.prepend(fresh);
            refreshEmptyState();
            closeSheet('serviceSheet');
            toast(res.message);
        } catch (e) {
            toast(e.message, 'error');
        } finally {
            btn.disabled = false;
        }
    });

    list.addEventListener('click', async (e) => {
        const card = e.target.closest('.service-card');
        if (!card) return;
        const id = card.dataset.id;

        if (e.target.closest('.js-edit')) {
            openServiceSheet(SERVICES[id] || null);
            return;
        }
        if (e.target.closest('.js-delete')) {
            const name = SERVICES[id] ? SERVICES[id].serviceName : 'this service';
            const ok = await confirmAction({
                title: 'Delete service?',
                message: '"' + name + '" will be removed from this schedule.',
                detail: 'Activities that already reference it keep their history.',
                confirmText: 'Delete',
            });
            if (!ok) return;
            try {
                const res = await api(URLS.destroy(id), { method: 'DELETE' });
                delete SERVICES[id];
                card.remove();
                refreshEmptyState();
                toast(res.message);
            } catch (err) {
                toast(err.message, 'error');
            }
        }
    });
})();
</script>
@endpush
