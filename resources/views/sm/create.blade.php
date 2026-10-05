@extends('layouts.app')

@section('title', 'New Cropping Schedule')
@section('page-title', 'New Cropping Schedule')
@section('page-subtitle', 'Set up your season')
@section('back', route('sm.index'))

@php
    $wizardConfig = [
        'storeUrl' => route('sm.store.wizard'),
        'cancelUrl' => route('sm.index'),
        // The crop moved to the lot, where it belongs, so the wizard has no
        // list of crops to offer and no lots to offer them for.
        // The same answers a lot can be set to later. A field is established
        // in one of three ways and each is counted differently; asking for two
        // meant direct-seeded fields were read against a transplanted calendar.
        //
        // The fourth is not a way of counting but the absence of one: an
        // orchard has no day the season started, and its trees are read by
        // their age instead. Put last, because it is the one that turns the
        // question off rather than answering it.
        'dayTypes' => [
            ['value' => 'DAT', 'label' => 'DAS → DAT: seeded, then transplanted'],
            ['value' => 'DAS', 'label' => 'DAS only: direct seeded (DSR)'],
            ['value' => 'DAP', 'label' => 'DAP: days after planting'],
            ['value' => 'TREE', 'label' => 'Mature trees: counted by tree age'],
        ],
    ];
@endphp

@section('content')
    <div class="max-w-4xl mx-auto" x-data="scheduleWizard({{ \Illuminate\Support\Js::from($wizardConfig) }})">

        {{-- ===== Step card ===== --}}
        <div class="card overflow-hidden">
            <div class="card-body">

                {{-- Step 1 — Details --}}
                <div x-show="step === 1"
                     x-transition:enter="transition transform ease-out duration-300"
                     x-transition:enter-start="opacity-0 translate-x-8"
                     x-transition:enter-end="opacity-100 translate-x-0">
                    <h2 class="text-lg font-bold text-gray-900">Basic details</h2>
                    <p class="text-sm text-gray-500 mt-1 mb-5">Name your season. Everything else is optional, and you can change it later.</p>

                    <div class="space-y-4">
                        <div>
                            <label class="form-label">Season name <span class="text-red-500">*</span></label>
                            <input type="text" x-model="form.title" maxlength="255"
                                   @keydown.enter.prevent="canNext && next()"
                                   {{-- No autofocus: on a phone it summoned the keypad over a form
                                        the farmer had not read yet. Tapping the field still focuses it. --}}
                                   class="form-input" placeholder="e.g. {{ \App\Support\Region::ph() ? 'Wet Season 2026 Rice' : 'Spring 2026 Corn' }}">
                        </div>
                        <div>
                            <label class="form-label">Description <span class="text-gray-400 font-normal">(optional)</span></label>
                            <textarea x-model="form.description" rows="4" maxlength="5000"
                                      class="form-textarea" placeholder="Notes about this season or field"></textarea>
                        </div>
                        {{-- The one thing the board cannot be drawn without,
                             and the one thing no later screen asks for. --}}
                        <div>
                            <label class="form-label">How days are counted</label>
                            <div class="space-y-2">
                                <template x-for="d in dayTypes" :key="d.value">
                                    <button type="button" @click="form.dayType = d.value"
                                            class="w-full flex items-center gap-3 rounded-xl border-2 px-3 py-3 text-left text-sm font-semibold transition"
                                            :class="form.dayType === d.value ? 'border-brand-600 bg-brand-50 text-brand-800' : 'border-gray-200 bg-white text-gray-700 hover:border-brand-300'">
                                        <span x-text="d.label"></span>
                                    </button>
                                </template>
                            </div>
                            {{-- A card, not a floating whisper: the promise
                                 that this answer is not forever is worth a
                                 frame of its own. --}}
                            <div class="mt-2 flex items-start gap-2 rounded-xl border border-brand-200 bg-brand-50 p-3 text-xs text-brand-800">
                                <svg class="w-4 h-4 shrink-0 mt-px" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                <span>Each lot can change this later.</span>
                            </div>
                        </div>
                    </div>

                    {{-- What used to be steps two to six. None of it was ever
                         required, all of it was asked before the grower had a
                         season to hang it on, and the wizard was five screens
                         of "skip". The schedule now exists after one screen and
                         asks for the rest from inside itself, where the answer
                         is in front of you: the board's own readiness list says
                         what is still missing and links straight to it. --}}
                    <div class="mt-5 rounded-xl border border-brand-200 bg-brand-50 p-4">
                        <p class="text-sm font-bold text-brand-900">What happens next</p>
                        <p class="text-sm text-brand-800/90 mt-1 leading-relaxed">
                            Your season opens right after you create it. Add lots, workers, materials
                            and crops there. A checklist shows what is still missing.
                        </p>
                    </div>
                </div>

            </div>

            {{-- ===== Footer nav ===== --}}
            <div class="flex items-center justify-between gap-3 px-4 sm:px-6 py-4 border-t border-gray-100 bg-gray-50/60">
                <button type="button" @click="back()" x-show="step > 1"
                        class="btn btn-ghost">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                    Back
                </button>
                <a href="{{ route('sm.index') }}" x-show="step === 1" class="btn btn-ghost">Cancel</a>

                <div class="flex items-center gap-2">
                    <button type="button" @click="submit()" x-show="step === steps.length"
                            :disabled="saving"
                            class="btn btn-accent">
                        <template x-if="!saving">
                            <span class="inline-flex items-center gap-2">
                                Create season
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            </span>
                        </template>
                        <template x-if="saving">
                            <span class="inline-flex items-center gap-2">
                                <svg class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/></svg>
                                Creating…
                            </span>
                        </template>
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    function scheduleWizard(config) {
        let seq = 0;
        const uid = () => ++seq;
        return {
            step: 1,
            maxReached: 1,
            saving: false,
            dayTypes: config.dayTypes,
            steps: [{ label: 'Details' }],
            form: {
                title: '', description: '',
                dayType: 'DAT',
            },

            get canNext() {
                if (this.step === 1) return this.form.title.trim().length > 0;
                return true;
            },

            dotClass(n) {
                if (n === this.step) return 'bg-brand-600 text-white scale-110 shadow-md';
                if (n < this.step) return 'bg-brand-600 text-white';
                return 'bg-white text-gray-400 border border-gray-300';
            },
            goTo(n) {
                if (n <= this.maxReached) this.step = n;
            },
            next() {
                if (this.step === 1 && !this.canNext) {
                    toast('Please add a season name.', 'error');
                    return;
                }
                if (this.step < this.steps.length) {
                    this.step++;
                    this.maxReached = Math.max(this.maxReached, this.step);
                    this.scrollTop();
                }
            },
            back() {
                if (this.step > 1) { this.step--; this.scrollTop(); }
            },
            scrollTop() {
                window.scrollTo({ top: 0, behavior: 'smooth' });
            },

            async submit() {
                if (!this.form.title.trim()) {
                    this.step = 1;
                    toast('Please add a season name.', 'error');
                    return;
                }
                this.saving = true;
                const payload = {
                    title: this.form.title.trim(),
                    description: this.form.description.trim() || null,
                    dayType: this.form.dayType,
                };
                try {
                    const res = await api(config.storeUrl, { method: 'POST', body: payload });
                    toast(res.message || 'Season created.', 'success');
                    window.location.href = res.data.redirect;
                } catch (e) {
                    this.saving = false;
                    toast(e.message || 'Could not create your season. Please try again.', 'error');
                }
            },
        };
    }
</script>
@endpush
