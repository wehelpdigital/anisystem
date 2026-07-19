@extends('layouts.app')

@section('title', 'New Cropping Schedule')
@section('page-title', 'New Cropping Schedule')
@section('page-subtitle', 'Set up your season')
@section('back', route('sm.index'))

@php
    $skills = \App\Models\AsScheduleWorker::SKILLS;
    $materialTypes = ['granular','foliar','pesticide','herbicide','molluscicide','fungicide','fertilizer','seed','other'];
    $materialUnits = ['kg','g','ml','l','bottle','sachet','piece','pack'];
@endphp

@section('content')
<div class="max-w-2xl mx-auto" x-data="setupWizard(@js([
    'storeUrl'     => route('sm.store'),
    'lotsUrl'      => route('sm.lots.store'),
    'workersUrl'   => route('sm.workers.store'),
    'materialsUrl' => route('sm.materials.store'),
    'servicesUrl'  => route('sm.services.store'),
]))">

    {{-- Intro --}}
    <div class="mb-5">
        <p class="text-sm text-gray-600">
            Start with the basics, then add your lots, workers and materials right here — or
            <span class="font-semibold text-gray-700">skip any step</span> and add them later.
        </p>
    </div>

    <div class="space-y-3">

        {{-- ============ 1. BASIC DETAILS ============ --}}
        <section class="card overflow-hidden">
            <button type="button" @click="toggle('basics')" class="w-full flex items-center justify-between gap-3 p-4 text-left">
                <div class="flex items-center gap-3 min-w-0">
                    <span class="shrink-0 w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold"
                          :class="scheduleId ? 'bg-brand-600 text-white' : 'bg-brand-100 text-brand-700'">
                        <template x-if="scheduleId"><svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg></template>
                        <template x-if="!scheduleId"><span>1</span></template>
                    </span>
                    <div class="min-w-0">
                        <h3 class="font-bold text-gray-900">Basic details</h3>
                        <p class="text-xs text-gray-500 truncate" x-text="scheduleId ? basics.title : 'Name, crop type & variety'"></p>
                    </div>
                </div>
                <svg class="w-5 h-5 text-gray-400 transition-transform shrink-0" :class="open==='basics' ? 'rotate-180' : ''" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
            </button>

            <div x-show="open==='basics'" x-collapse>
                <div class="border-t border-gray-100 p-4 space-y-4">
                    <div>
                        <label class="form-label">Title <span class="text-red-500">*</span></label>
                        <input type="text" x-model="basics.title" maxlength="255" :disabled="scheduleId"
                               class="form-input" :class="errors.title ? 'border-red-400!' : ''"
                               placeholder="e.g. Wet Season 2026 — Rice Cropping">
                        <p class="form-error" x-show="errors.title" x-text="errors.title"></p>
                    </div>

                    <div class="grid sm:grid-cols-2 gap-4">
                        <div>
                            <label class="form-label">Type of crop</label>
                            <select x-model="basics.cropType" :disabled="scheduleId" class="form-select">
                                <option value="">Select crop type…</option>
                                @foreach ($cropTypes as $ct)
                                    <option value="{{ $ct }}">{{ $ct }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="form-label">Variety</label>
                            <input type="text" x-model="basics.cropVariety" maxlength="150" :disabled="scheduleId"
                                   class="form-input" placeholder="e.g. IR64, NSIC Rc222…">
                        </div>
                    </div>

                    <div>
                        <label class="form-label">Day-count type</label>
                        <select x-model="basics.dayType" :disabled="scheduleId" class="form-select">
                            <option value="DAS">DAS — Days After Seeding</option>
                            <option value="DAP">DAP — Days After Planting</option>
                            <option value="DAT">DAT — Days After Transplanting</option>
                        </select>
                    </div>

                    <div>
                        <label class="form-label">Description <span class="text-gray-400 font-normal">(optional)</span></label>
                        <textarea x-model="basics.description" rows="3" maxlength="5000" :disabled="scheduleId"
                                  class="form-textarea" placeholder="Notes about this season, the field, the plan…"></textarea>
                    </div>

                    <div class="flex justify-end gap-2 pt-1" x-show="!scheduleId">
                        <a href="{{ route('sm.index') }}" class="btn btn-ghost">Cancel</a>
                        <button type="button" @click="saveBasics()" class="btn btn-primary" :disabled="saving">
                            <span x-show="!saving">Save &amp; continue</span>
                            <span x-show="saving">Saving…</span>
                        </button>
                    </div>
                    <p class="text-xs text-brand-700 font-semibold" x-show="scheduleId">
                        ✓ Saved. Add lots, workers and materials below, or finish anytime.
                    </p>
                </div>
            </div>
        </section>

        {{-- ============ 2. LOTS ============ --}}
        @include('sm.partials.wizard-section', ['key' => 'lots', 'num' => 2, 'title' => 'Lots', 'hint' => 'Your fields / plots', 'next' => 'workers'])

        {{-- ============ 3. WORKERS ============ --}}
        @include('sm.partials.wizard-section', ['key' => 'workers', 'num' => 3, 'title' => 'Workers', 'hint' => 'Your labor roster', 'next' => 'materials'])

        {{-- ============ 4. MATERIALS ============ --}}
        @include('sm.partials.wizard-section', ['key' => 'materials', 'num' => 4, 'title' => 'Materials', 'hint' => 'Fertilizers, seeds, chemicals', 'next' => 'services'])

        {{-- ============ 5. SERVICES ============ --}}
        @include('sm.partials.wizard-section', ['key' => 'services', 'num' => 5, 'title' => 'Services', 'hint' => 'Hired services', 'next' => ''])

    </div>

    {{-- Finish bar --}}
    <div class="sticky bottom-0 mt-4 -mx-4 sm:mx-0 px-4 py-3 sm:rounded-2xl bg-white/90 backdrop-blur border-t sm:border border-gray-200 flex items-center justify-between gap-3"
         style="padding-bottom: calc(0.75rem + env(safe-area-inset-bottom, 0));">
        <span class="text-xs text-gray-500" x-show="!scheduleId">Save the basic details to continue.</span>
        <span class="text-xs text-gray-500" x-show="scheduleId" x-text="summary()"></span>
        <button type="button" @click="finish()" class="btn btn-accent ml-auto" :disabled="!scheduleId">
            Finish &amp; open schedule
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5-5 5M6 12h12"/></svg>
        </button>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const WIZARD_SKILLS = @js($skills);
    const WIZARD_MATERIAL_TYPES = @js($materialTypes);
    const WIZARD_MATERIAL_UNITS = @js($materialUnits);

    function setupWizard(cfg) {
        return {
            cfg,
            scheduleId: null,
            hubUrl: null,
            open: 'basics',
            saving: false,
            busy: false,
            errors: {},

            basics: { title: '', description: '', cropType: '', cropVariety: '', dayType: 'DAS' },

            lots: [], workers: [], materials: [], services: [],

            lotForm: { lotName: '', lotSize: '', lotSizeUnit: 'hectare', variety: '', notes: '' },
            workerForm: { workerName: '', costPerHalfDay: '', priority: 1, notes: '' },
            materialForm: { materialName: '', materialType: 'fertilizer', unitOfMeasure: 'kg', priceAmount: '', priceQuantity: 1 },
            serviceForm: { serviceName: '', serviceCost: '' },

            canOpen(section) { return section === 'basics' || !!this.scheduleId; },

            toggle(section) {
                if (!this.canOpen(section)) { toast('Save the basic details first.', 'info'); return; }
                this.open = this.open === section ? '' : section;
            },

            skip(next) { this.open = next || ''; },
            next(section) { this.open = section; },

            url(base) { return `${base}?scheduleId=${this.scheduleId}`; },

            async saveBasics() {
                this.errors = {};
                if (!this.basics.title.trim()) { this.errors.title = 'Title is required.'; return; }
                this.saving = true;
                try {
                    const res = await api(this.cfg.storeUrl, { method: 'POST', body: this.basics });
                    this.scheduleId = res.data.id;
                    this.hubUrl = res.data.hubUrl;
                    if (this.basics.cropVariety) this.lotForm.variety = this.basics.cropVariety;
                    toast('Schedule created.');
                    this.open = 'lots';
                } catch (e) {
                    this.errors.title = e.message;
                    toast(e.message, 'error');
                } finally {
                    this.saving = false;
                }
            },

            async addLot() {
                if (!this.lotForm.lotName.trim()) { toast('Lot name is required.', 'error'); return; }
                this.busy = true;
                try {
                    const res = await api(this.url(this.cfg.lotsUrl), {
                        method: 'POST',
                        body: { ...this.lotForm, lotSize: this.lotForm.lotSize || 0 },
                    });
                    const d = res.data;
                    this.lots.push({
                        id: d.id,
                        name: d.lotName,
                        meta: `${(+d.lotSize || 0)} ${d.lotSizeUnit || ''}`.trim() + (d.variety ? ` · ${d.variety}` : ''),
                    });
                    this.lotForm.lotName = ''; this.lotForm.lotSize = ''; this.lotForm.notes = '';
                    toast('Lot added.');
                } catch (e) { toast(e.message, 'error'); } finally { this.busy = false; }
            },

            async addWorker() {
                if (!this.workerForm.workerName.trim()) { toast('Worker name is required.', 'error'); return; }
                this.busy = true;
                try {
                    const res = await api(this.url(this.cfg.workersUrl), {
                        method: 'POST',
                        body: { ...this.workerForm, costPerHalfDay: this.workerForm.costPerHalfDay || 0, skills: [] },
                    });
                    const d = res.data;
                    this.workers.push({
                        id: d.id,
                        name: d.workerName,
                        meta: `Priority ${d.priority} · ${fmtPeso(d.costPerHalfDay)}/half-day`,
                    });
                    this.workerForm.workerName = ''; this.workerForm.costPerHalfDay = ''; this.workerForm.notes = '';
                    toast('Worker added.');
                } catch (e) { toast(e.message, 'error'); } finally { this.busy = false; }
            },

            async addMaterial() {
                if (!this.materialForm.materialName.trim()) { toast('Material name is required.', 'error'); return; }
                this.busy = true;
                try {
                    const res = await api(this.url(this.cfg.materialsUrl), {
                        method: 'POST',
                        body: {
                            ...this.materialForm,
                            priceAmount: this.materialForm.priceAmount || 0,
                            priceQuantity: this.materialForm.priceQuantity || 1,
                        },
                    });
                    const d = res.data;
                    this.materials.push({
                        id: d.id,
                        name: d.materialName,
                        meta: `${d.materialType} · ${fmtPeso(d.priceAmount)} per ${(+d.priceQuantity || 1)} ${d.unitOfMeasure}`,
                    });
                    this.materialForm.materialName = ''; this.materialForm.priceAmount = '';
                    toast('Material added.');
                } catch (e) { toast(e.message, 'error'); } finally { this.busy = false; }
            },

            async addService() {
                if (!this.serviceForm.serviceName.trim()) { toast('Service name is required.', 'error'); return; }
                this.busy = true;
                try {
                    const res = await api(this.url(this.cfg.servicesUrl), {
                        method: 'POST',
                        body: { ...this.serviceForm, serviceCost: this.serviceForm.serviceCost || 0 },
                    });
                    const d = res.data;
                    this.services.push({ id: d.id, name: d.serviceName, meta: fmtPeso(d.serviceCost) });
                    this.serviceForm.serviceName = ''; this.serviceForm.serviceCost = '';
                    toast('Service added.');
                } catch (e) { toast(e.message, 'error'); } finally { this.busy = false; }
            },

            summary() {
                const parts = [];
                if (this.lots.length) parts.push(`${this.lots.length} lot${this.lots.length > 1 ? 's' : ''}`);
                if (this.workers.length) parts.push(`${this.workers.length} worker${this.workers.length > 1 ? 's' : ''}`);
                if (this.materials.length) parts.push(`${this.materials.length} material${this.materials.length > 1 ? 's' : ''}`);
                if (this.services.length) parts.push(`${this.services.length} service${this.services.length > 1 ? 's' : ''}`);
                return parts.length ? 'Added: ' + parts.join(', ') : 'You can add more later.';
            },

            finish() {
                if (!this.hubUrl) return;
                window.location = this.hubUrl;
            },
        };
    }
</script>
@endpush
