@extends('layouts.app')

@section('title', 'New Cropping Schedule')
@section('page-title', 'New Cropping Schedule')
@section('page-subtitle', 'Set up your season, step by step')
@section('back', route('sm.index'))

@php
    $wizardConfig = [
        'storeUrl' => route('sm.store.wizard'),
        'cancelUrl' => route('sm.index'),
        'crops' => [
            ['value' => 'Rice (Palay)', 'icon' => '🌾'],
            ['value' => 'Corn (Mais)', 'icon' => '🌽'],
            ['value' => 'Banana (Saging)', 'icon' => '🍌'],
            ['value' => 'Mango (Mangga)', 'icon' => '🥭'],
            ['value' => 'Sugarcane (Tubo)', 'icon' => '🎋'],
            ['value' => 'Coconut (Niyog)', 'icon' => '🥥'],
            ['value' => 'Vegetables (Gulay)', 'icon' => '🥬'],
        ],
        'lotUnits' => ['hectare', 'sqm', 'acre'],
        'dayTypes' => [
            ['value' => 'DAP', 'label' => 'DAP — Days After Planting'],
            ['value' => 'DAS', 'label' => 'DAS — Days After Seeding'],
            ['value' => 'DAT', 'label' => 'DAT — Days After Transplanting'],
        ],
        'skills' => \App\Models\AsScheduleWorker::SKILLS,
        'materialTypes' => ['granular', 'foliar', 'pesticide', 'herbicide', 'molluscicide', 'fungicide', 'fertilizer', 'seed', 'other'],
        'materialUnits' => ['kg', 'g', 'ml', 'l', 'bottle', 'sachet', 'piece', 'pack'],
    ];
@endphp

@section('content')
    <div class="max-w-2xl mx-auto" x-data="scheduleWizard({{ \Illuminate\Support\Js::from($wizardConfig) }})">

        {{-- ===== Dotted stepper ===== --}}
        <div class="mb-6">
            <div class="flex items-center justify-between text-xs font-semibold mb-2.5">
                <span class="text-gray-500">Step <span x-text="step"></span> of <span x-text="steps.length"></span></span>
                <span class="text-brand-700" x-text="steps[step - 1].label"></span>
            </div>
            <div class="relative px-1">
                <div class="absolute left-1 right-1 top-4 h-1 rounded-full bg-gray-200"></div>
                <div class="absolute left-1 top-4 h-1 rounded-full bg-brand-500 transition-all duration-500 ease-out"
                     :style="`width: calc((100% - 0.5rem) * ${(step - 1) / (steps.length - 1)})`"></div>
                <div class="relative flex items-start justify-between">
                    <template x-for="(s, i) in steps" :key="i">
                        <button type="button" @click="goTo(i + 1)" :disabled="i + 1 > maxReached"
                                class="flex flex-col items-center gap-1.5"
                                :class="i + 1 > maxReached ? 'cursor-default' : 'cursor-pointer'">
                            <span class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold ring-4 ring-gray-50 transition-all duration-300"
                                  :class="dotClass(i + 1)">
                                <template x-if="i + 1 < step">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                </template>
                                <template x-if="i + 1 >= step"><span x-text="i + 1"></span></template>
                            </span>
                            <span class="hidden sm:block text-[11px] font-medium transition-colors"
                                  :class="i + 1 <= step ? 'text-brand-700' : 'text-gray-400'" x-text="s.label"></span>
                        </button>
                    </template>
                </div>
            </div>
        </div>

        {{-- ===== Step card ===== --}}
        <div class="card overflow-hidden">
            <div class="card-body">

                {{-- Step 1 — Details --}}
                <div x-show="step === 1"
                     x-transition:enter="transition transform ease-out duration-300"
                     x-transition:enter-start="opacity-0 translate-x-8"
                     x-transition:enter-end="opacity-100 translate-x-0">
                    <h2 class="text-lg font-bold text-gray-900">Basic details</h2>
                    <p class="text-sm text-gray-500 mt-1 mb-5">Give your cropping schedule a name. Everything else is optional and can be changed anytime.</p>

                    <div class="space-y-4">
                        <div>
                            <label class="form-label">Title <span class="text-red-500">*</span></label>
                            <input type="text" x-model="form.title" maxlength="255"
                                   @keydown.enter.prevent="canNext && next()"
                                   class="form-input" placeholder="e.g. Wet Season 2026 — Rice Cropping" autofocus>
                        </div>
                        <div>
                            <label class="form-label">Description <span class="text-gray-400 font-normal">(optional)</span></label>
                            <textarea x-model="form.description" rows="4" maxlength="5000"
                                      class="form-textarea" placeholder="Notes about this season, the field, the plan…"></textarea>
                        </div>
                    </div>
                </div>

                {{-- Step 2 — Crop & cycle --}}
                <div x-show="step === 2" x-cloak
                     x-transition:enter="transition transform ease-out duration-300"
                     x-transition:enter-start="opacity-0 translate-x-8"
                     x-transition:enter-end="opacity-100 translate-x-0">
                    <h2 class="text-lg font-bold text-gray-900">Crop &amp; cycle</h2>
                    <p class="text-sm text-gray-500 mt-1 mb-5">What are you growing this season? You can skip this and set it later.</p>

                    <label class="form-label">Crop type</label>
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-2.5">
                        <template x-for="c in crops" :key="c.value">
                            <button type="button" @click="selectCrop(c.value)"
                                    class="flex items-center gap-2.5 rounded-xl border-2 px-3 py-2.5 text-left text-sm font-semibold transition"
                                    :class="form.cropType === c.value ? 'border-brand-600 bg-brand-50 text-brand-800' : 'border-gray-200 bg-white text-gray-700 hover:border-brand-300'">
                                <span class="text-xl" x-text="c.icon"></span>
                                <span x-text="c.value"></span>
                            </button>
                        </template>
                        <button type="button" @click="selectCrop('__other__')"
                                class="flex items-center gap-2.5 rounded-xl border-2 px-3 py-2.5 text-left text-sm font-semibold transition"
                                :class="isOtherCrop ? 'border-brand-600 bg-brand-50 text-brand-800' : 'border-gray-200 bg-white text-gray-700 hover:border-brand-300'">
                            <span class="text-xl">➕</span>
                            <span>Other</span>
                        </button>
                    </div>

                    <div x-show="isOtherCrop" x-transition class="mt-3">
                        <input type="text" x-model="form.cropOther" maxlength="100"
                               class="form-input" placeholder="Type your crop (e.g. Coffee, Tomato)">
                    </div>

                    <div class="mt-5">
                        <label class="form-label">Variety <span class="text-gray-400 font-normal">(optional)</span></label>
                        <input type="text" x-model="form.cropVariety" maxlength="255"
                               class="form-input" placeholder="e.g. IR64, NSIC Rc222, hybrid…">
                    </div>

                    <div class="mt-5">
                        <label class="form-label">How do you count crop days?</label>
                        <div class="grid grid-cols-3 gap-2">
                            <template x-for="d in dayTypes" :key="d.value">
                                <button type="button" @click="form.dayType = d.value"
                                        class="rounded-xl border-2 px-2 py-2.5 text-center text-sm font-bold transition"
                                        :class="form.dayType === d.value ? 'border-brand-600 bg-brand-600 text-white' : 'border-gray-200 bg-white text-gray-700 hover:border-brand-300'"
                                        x-text="d.value"></button>
                            </template>
                        </div>
                        <p class="form-hint" x-text="dayTypes.find(d => d.value === form.dayType)?.label"></p>
                    </div>
                </div>

                {{-- Step 3 — Lots --}}
                <div x-show="step === 3" x-cloak
                     x-transition:enter="transition transform ease-out duration-300"
                     x-transition:enter-start="opacity-0 translate-x-8"
                     x-transition:enter-end="opacity-100 translate-x-0">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h2 class="text-lg font-bold text-gray-900">Lots / fields</h2>
                            <p class="text-sm text-gray-500 mt-1">Add the fields you'll be planting. Skip if you'd rather do this later.</p>
                        </div>
                        <span class="badge badge-green shrink-0" x-show="lots.length" x-text="lots.length + ' added'"></span>
                    </div>

                    <div class="mt-4 space-y-3">
                        <template x-for="(lot, i) in lots" :key="lot._id">
                            <div class="rounded-2xl border border-gray-200 bg-gray-50/70 p-3.5"
                                 x-transition:enter="transition ease-out duration-300"
                                 x-transition:enter-start="opacity-0 -translate-y-2"
                                 x-transition:enter-end="opacity-100 translate-y-0">
                                <div class="flex items-center justify-between mb-2.5">
                                    <span class="text-xs font-bold text-gray-400 uppercase tracking-wide">Lot <span x-text="i + 1"></span></span>
                                    <button type="button" @click="lots.splice(i, 1)" class="text-red-500 hover:text-red-600 p-1" aria-label="Remove lot">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 7h12M9 7V5a1 1 0 011-1h4a1 1 0 011 1v2m-7 0v12a1 1 0 001 1h6a1 1 0 001-1V7"/></svg>
                                    </button>
                                </div>
                                <div class="space-y-2.5">
                                    <input type="text" x-model="lot.lotName" maxlength="255" class="form-input" placeholder="Lot name (e.g. Lot A, North Field)">
                                    <div class="grid grid-cols-2 gap-2.5">
                                        <input type="number" step="0.0001" min="0" x-model="lot.lotSize" class="form-input" placeholder="Size">
                                        <select x-model="lot.lotSizeUnit" class="form-select">
                                            <template x-for="u in lotUnits" :key="u"><option :value="u" x-text="u"></option></template>
                                        </select>
                                    </div>
                                    <input type="text" x-model="lot.notes" maxlength="2000" class="form-input" placeholder="Description (optional)">
                                </div>
                            </div>
                        </template>
                    </div>

                    <button type="button" @click="addLot()"
                            class="mt-3 w-full rounded-xl border-2 border-dashed border-brand-300 text-brand-700 font-semibold py-2.5 hover:bg-brand-50 transition flex items-center justify-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/></svg>
                        Add a lot
                    </button>
                </div>

                {{-- Step 4 — Workers --}}
                <div x-show="step === 4" x-cloak
                     x-transition:enter="transition transform ease-out duration-300"
                     x-transition:enter-start="opacity-0 translate-x-8"
                     x-transition:enter-end="opacity-100 translate-x-0">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h2 class="text-lg font-bold text-gray-900">Workers</h2>
                            <p class="text-sm text-gray-500 mt-1">Add your workers and their daily rates. Optional — you can add them anytime.</p>
                        </div>
                        <span class="badge badge-green shrink-0" x-show="workers.length" x-text="workers.length + ' added'"></span>
                    </div>

                    <div class="mt-4 space-y-3">
                        <template x-for="(w, i) in workers" :key="w._id">
                            <div class="rounded-2xl border border-gray-200 bg-gray-50/70 p-3.5"
                                 x-transition:enter="transition ease-out duration-300"
                                 x-transition:enter-start="opacity-0 -translate-y-2"
                                 x-transition:enter-end="opacity-100 translate-y-0">
                                <div class="flex items-center justify-between mb-2.5">
                                    <span class="text-xs font-bold text-gray-400 uppercase tracking-wide">Worker <span x-text="i + 1"></span></span>
                                    <button type="button" @click="workers.splice(i, 1)" class="text-red-500 hover:text-red-600 p-1" aria-label="Remove worker">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 7h12M9 7V5a1 1 0 011-1h4a1 1 0 011 1v2m-7 0v12a1 1 0 001 1h6a1 1 0 001-1V7"/></svg>
                                    </button>
                                </div>
                                <div class="space-y-2.5">
                                    <input type="text" x-model="w.workerName" maxlength="255" class="form-input" placeholder="Worker name">
                                    <div class="relative">
                                        <input type="number" step="0.01" min="0" x-model="w.costPerHalfDay" class="form-input" placeholder="Cost per half-day work">
                                    </div>
                                    <input type="number" min="1" x-model="w.priority" class="form-input" placeholder="Priority (1 = first pick)">
                                    <div>
                                        <span class="text-xs text-gray-500">Skills</span>
                                        <div class="flex flex-wrap gap-1.5 mt-1">
                                            <template x-for="(label, key) in skills" :key="key">
                                                <button type="button" @click="toggleSkill(w, key)"
                                                        class="rounded-full border-2 px-3 py-1 text-xs font-medium transition"
                                                        :class="w.skills.includes(key) ? 'border-brand-600 bg-brand-600 text-white' : 'border-gray-200 bg-white text-gray-600 hover:border-brand-300'"
                                                        x-text="label"></button>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>

                    <button type="button" @click="addWorker()"
                            class="mt-3 w-full rounded-xl border-2 border-dashed border-brand-300 text-brand-700 font-semibold py-2.5 hover:bg-brand-50 transition flex items-center justify-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/></svg>
                        Add a worker
                    </button>
                </div>

                {{-- Step 5 — Materials & Services --}}
                <div x-show="step === 5" x-cloak
                     x-transition:enter="transition transform ease-out duration-300"
                     x-transition:enter-start="opacity-0 translate-x-8"
                     x-transition:enter-end="opacity-100 translate-x-0">
                    <h2 class="text-lg font-bold text-gray-900">Materials &amp; services</h2>
                    <p class="text-sm text-gray-500 mt-1 mb-4">List fertilizers, seeds and hired services with prices. All optional.</p>

                    {{-- Materials --}}
                    <div class="flex items-center justify-between">
                        <h3 class="font-bold text-gray-800">Materials</h3>
                        <span class="badge badge-green" x-show="materials.length" x-text="materials.length"></span>
                    </div>
                    <div class="mt-2.5 space-y-3">
                        <template x-for="(m, i) in materials" :key="m._id">
                            <div class="rounded-2xl border border-gray-200 bg-gray-50/70 p-3.5"
                                 x-transition:enter="transition ease-out duration-300"
                                 x-transition:enter-start="opacity-0 -translate-y-2"
                                 x-transition:enter-end="opacity-100 translate-y-0">
                                <div class="flex items-center justify-between mb-2.5">
                                    <span class="text-xs font-bold text-gray-400 uppercase tracking-wide">Material <span x-text="i + 1"></span></span>
                                    <button type="button" @click="materials.splice(i, 1)" class="text-red-500 hover:text-red-600 p-1" aria-label="Remove material">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 7h12M9 7V5a1 1 0 011-1h4a1 1 0 011 1v2m-7 0v12a1 1 0 001 1h6a1 1 0 001-1V7"/></svg>
                                    </button>
                                </div>
                                <div class="space-y-2.5">
                                    <input type="text" x-model="m.materialName" maxlength="255" class="form-input" placeholder="Material name (e.g. Urea, 14-14-14)">
                                    <div class="grid grid-cols-2 gap-2.5">
                                        <select x-model="m.materialType" class="form-select">
                                            <template x-for="t in materialTypes" :key="t"><option :value="t" x-text="t"></option></template>
                                        </select>
                                        <select x-model="m.unitOfMeasure" class="form-select">
                                            <template x-for="u in materialUnits" :key="u"><option :value="u" x-text="u"></option></template>
                                        </select>
                                    </div>
                                    <div class="grid grid-cols-2 gap-2.5 items-center">
                                        <div class="relative">
                                                <input type="number" step="0.01" min="0" x-model="m.priceAmount" class="form-input" placeholder="Price">
                                        </div>
                                        <div class="flex items-center gap-1.5 text-sm text-gray-500">
                                            <span>per</span>
                                            <input type="number" step="0.0001" min="0.0001" x-model="m.priceQuantity" class="form-input" placeholder="1">
                                            <span x-text="m.unitOfMeasure"></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                    <button type="button" @click="addMaterial()"
                            class="mt-2.5 w-full rounded-xl border-2 border-dashed border-brand-300 text-brand-700 font-semibold py-2 text-sm hover:bg-brand-50 transition flex items-center justify-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/></svg>
                        Add a material
                    </button>

                    {{-- Services --}}
                    <div class="flex items-center justify-between mt-6">
                        <h3 class="font-bold text-gray-800">Services</h3>
                        <span class="badge badge-green" x-show="services.length" x-text="services.length"></span>
                    </div>
                    <div class="mt-2.5 space-y-3">
                        <template x-for="(sv, i) in services" :key="sv._id">
                            <div class="rounded-2xl border border-gray-200 bg-gray-50/70 p-3.5"
                                 x-transition:enter="transition ease-out duration-300"
                                 x-transition:enter-start="opacity-0 -translate-y-2"
                                 x-transition:enter-end="opacity-100 translate-y-0">
                                <div class="flex items-center justify-between mb-2.5">
                                    <span class="text-xs font-bold text-gray-400 uppercase tracking-wide">Service <span x-text="i + 1"></span></span>
                                    <button type="button" @click="services.splice(i, 1)" class="text-red-500 hover:text-red-600 p-1" aria-label="Remove service">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 7h12M9 7V5a1 1 0 011-1h4a1 1 0 011 1v2m-7 0v12a1 1 0 001 1h6a1 1 0 001-1V7"/></svg>
                                    </button>
                                </div>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                                    <input type="text" x-model="sv.serviceName" maxlength="255" class="form-input" placeholder="Service (e.g. Tractor rental)">
                                    <div class="relative">
                                        <input type="number" step="0.01" min="0" x-model="sv.serviceCost" class="form-input" placeholder="Cost">
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                    <button type="button" @click="addService()"
                            class="mt-2.5 w-full rounded-xl border-2 border-dashed border-brand-300 text-brand-700 font-semibold py-2 text-sm hover:bg-brand-50 transition flex items-center justify-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/></svg>
                        Add a service
                    </button>
                </div>

                {{-- Step 6 — Review --}}
                <div x-show="step === 6" x-cloak
                     x-transition:enter="transition transform ease-out duration-300"
                     x-transition:enter-start="opacity-0 translate-x-8"
                     x-transition:enter-end="opacity-100 translate-x-0">
                    <h2 class="text-lg font-bold text-gray-900">Review &amp; create</h2>
                    <p class="text-sm text-gray-500 mt-1 mb-5">Here's what we'll set up. You can add or change anything after.</p>

                    <div class="rounded-2xl bg-brand-50/70 ring-1 ring-brand-100 p-4 space-y-3">
                        <div class="flex items-start gap-3">
                            <span class="text-2xl" x-text="cropIcon"></span>
                            <div class="min-w-0">
                                <p class="font-bold text-gray-900 truncate" x-text="form.title || 'Untitled schedule'"></p>
                                <p class="text-sm text-gray-600" x-text="cropSummary"></p>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 pt-1">
                            <template x-for="stat in reviewStats" :key="stat.label">
                                <div class="rounded-xl bg-white ring-1 ring-gray-100 px-3 py-2 text-center">
                                    <div class="font-heading text-xl font-bold text-brand-700" x-text="stat.count"></div>
                                    <div class="text-[11px] text-gray-500" x-text="stat.label"></div>
                                </div>
                            </template>
                        </div>
                    </div>

                    <p class="text-xs text-gray-400 mt-4 text-center">You can generate the activity timeline and everything else from your schedule's modules.</p>
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
                    <button type="button" @click="next()" x-show="isSkippable"
                            class="btn btn-white">Skip</button>

                    <button type="button" @click="next()" x-show="step < steps.length"
                            :disabled="!canNext"
                            class="btn btn-primary">
                        <span x-text="nextLabel"></span>
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                    </button>

                    <button type="button" @click="submit()" x-show="step === steps.length"
                            :disabled="saving"
                            class="btn btn-accent">
                        <template x-if="!saving">
                            <span class="inline-flex items-center gap-2">
                                Create Schedule
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
            crops: config.crops,
            lotUnits: config.lotUnits,
            dayTypes: config.dayTypes,
            skills: config.skills,
            materialTypes: config.materialTypes,
            materialUnits: config.materialUnits,
            steps: [
                { label: 'Details' }, { label: 'Crop' }, { label: 'Lots' },
                { label: 'Workers' }, { label: 'Inputs' }, { label: 'Review' },
            ],
            form: {
                title: '', description: '',
                cropType: '', cropOther: '', cropVariety: '', dayType: 'DAS',
            },
            lots: [], workers: [], materials: [], services: [],

            get isOtherCrop() {
                return this.form.cropType === '__other__';
            },
            get canNext() {
                if (this.step === 1) return this.form.title.trim().length > 0;
                return true;
            },
            get isSkippable() {
                // Steps 2..5 (crop, lots, workers, inputs) are optional.
                return this.step >= 2 && this.step <= 5 && this.step < this.steps.length;
            },
            get nextLabel() {
                return this.step === this.steps.length - 1 ? 'Review' : 'Next';
            },
            get resolvedCrop() {
                if (this.isOtherCrop) return this.form.cropOther.trim();
                return this.form.cropType;
            },
            get cropIcon() {
                const c = this.crops.find((x) => x.value === this.form.cropType);
                return c ? c.icon : '🌱';
            },
            get cropSummary() {
                const parts = [];
                if (this.resolvedCrop) parts.push(this.resolvedCrop);
                if (this.form.cropVariety.trim()) parts.push(this.form.cropVariety.trim());
                parts.push(this.form.dayType);
                return parts.join(' · ');
            },
            get reviewStats() {
                return [
                    { label: 'Lots', count: this.lots.filter((l) => l.lotName.trim()).length },
                    { label: 'Workers', count: this.workers.filter((w) => w.workerName.trim()).length },
                    { label: 'Materials', count: this.materials.filter((m) => m.materialName.trim()).length },
                    { label: 'Services', count: this.services.filter((s) => s.serviceName.trim()).length },
                ];
            },

            dotClass(n) {
                if (n === this.step) return 'bg-brand-600 text-white scale-110 shadow-md';
                if (n < this.step) return 'bg-brand-600 text-white';
                return 'bg-white text-gray-400 border border-gray-300';
            },
            selectCrop(value) {
                this.form.cropType = value;
                if (value !== '__other__') this.form.cropOther = '';
            },
            toggleSkill(worker, key) {
                const i = worker.skills.indexOf(key);
                if (i >= 0) worker.skills.splice(i, 1);
                else worker.skills.push(key);
            },
            addLot() {
                this.lots.push({ _id: uid(), lotName: '', lotSize: '', lotSizeUnit: 'hectare', notes: '' });
            },
            addWorker() {
                this.workers.push({ _id: uid(), workerName: '', costPerHalfDay: '', priority: this.workers.length + 1, skills: [] });
            },
            addMaterial() {
                this.materials.push({ _id: uid(), materialName: '', materialType: 'fertilizer', unitOfMeasure: 'kg', priceAmount: '', priceQuantity: 1 });
            },
            addService() {
                this.services.push({ _id: uid(), serviceName: '', serviceCost: '' });
            },

            goTo(n) {
                if (n <= this.maxReached) this.step = n;
            },
            next() {
                if (this.step === 1 && !this.canNext) {
                    toast('Please give your schedule a title.', 'error');
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
                    toast('Please give your schedule a title.', 'error');
                    return;
                }
                this.saving = true;
                const payload = {
                    title: this.form.title.trim(),
                    description: this.form.description.trim() || null,
                    cropType: this.resolvedCrop || null,
                    cropVariety: this.form.cropVariety.trim() || null,
                    dayType: this.form.dayType,
                    lots: this.lots.filter((l) => l.lotName.trim()),
                    workers: this.workers.filter((w) => w.workerName.trim()),
                    materials: this.materials.filter((m) => m.materialName.trim()),
                    services: this.services.filter((s) => s.serviceName.trim()),
                };
                try {
                    const res = await api(config.storeUrl, { method: 'POST', body: payload });
                    toast(res.message || 'Schedule created.', 'success');
                    window.location.href = res.data.redirect;
                } catch (e) {
                    this.saving = false;
                    toast(e.message || 'Could not create your schedule.', 'error');
                }
            },
        };
    }
</script>
@endpush
