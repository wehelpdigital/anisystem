{{--
    One accordion section of the create/setup wizard.
    Vars: $key (lots|workers|materials|services), $num, $title, $hint, $next
    Alpine state lives on the parent setupWizard() component.
--}}
<section class="card overflow-hidden transition" :class="!scheduleId ? 'opacity-60' : ''">
    <button type="button" @click="toggle('{{ $key }}')" class="w-full flex items-center justify-between gap-3 p-4 text-left">
        <div class="flex items-center gap-3 min-w-0">
            <span class="shrink-0 w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold"
                  :class="{{ $key }}.length ? 'bg-brand-600 text-white' : (scheduleId ? 'bg-brand-100 text-brand-700' : 'bg-gray-100 text-gray-400')">
                <template x-if="!scheduleId">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                </template>
                <template x-if="scheduleId"><span>{{ $num }}</span></template>
            </span>
            <div class="min-w-0">
                <h3 class="font-bold text-gray-900">{{ $title }}
                    <span class="text-xs font-normal text-gray-400">· optional</span>
                </h3>
                <p class="text-xs text-gray-500 truncate"
                   x-text="{{ $key }}.length ? ({{ $key }}.length + ' added') : '{{ $hint }}'"></p>
            </div>
        </div>
        <svg class="w-5 h-5 text-gray-400 transition-transform shrink-0" :class="open==='{{ $key }}' ? 'rotate-180' : ''" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
    </button>

    <div x-show="open==='{{ $key }}'" x-collapse>
        <div class="border-t border-gray-100 p-4 space-y-4">

            {{-- Added items --}}
            <div x-show="{{ $key }}.length" class="space-y-2" data-animate-list>
                <template x-for="item in {{ $key }}" :key="item.id">
                    <div class="flex items-center justify-between gap-3 rounded-xl bg-brand-50/70 border border-brand-100 px-3 py-2">
                        <div class="min-w-0">
                            <p class="font-semibold text-sm text-gray-900 truncate" x-text="item.name"></p>
                            <p class="text-xs text-gray-500 truncate" x-text="item.meta"></p>
                        </div>
                        <span class="badge badge-green shrink-0">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            Added
                        </span>
                    </div>
                </template>
            </div>

            {{-- Quick-add form --}}
            <div class="rounded-xl border border-dashed border-gray-300 p-3 space-y-3">
                @if ($key === 'lots')
                    <div>
                        <label class="form-label">Lot name <span class="text-red-500">*</span></label>
                        <input type="text" x-model="lotForm.lotName" maxlength="255" class="form-input" placeholder="e.g. Lot A / North field">
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="form-label">Size</label>
                            <input type="number" step="0.0001" min="0" x-model="lotForm.lotSize" class="form-input" placeholder="0">
                        </div>
                        <div>
                            <label class="form-label">Unit</label>
                            <select x-model="lotForm.lotSizeUnit" class="form-select">
                                <option value="hectare">hectare</option>
                                <option value="sqm">sqm</option>
                                <option value="acre">acre</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="form-label">Variety <span class="text-gray-400 font-normal">(optional)</span></label>
                        <input type="text" x-model="lotForm.variety" maxlength="255" class="form-input" placeholder="e.g. IR64">
                    </div>
                    <button type="button" @click="addLot()" class="btn btn-primary btn-sm w-full" :disabled="busy">+ Add lot</button>

                @elseif ($key === 'workers')
                    <div>
                        <label class="form-label">Worker name <span class="text-red-500">*</span></label>
                        <input type="text" x-model="workerForm.workerName" maxlength="255" class="form-input" placeholder="e.g. Juan Dela Cruz">
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="form-label">Cost / half-day (₱)</label>
                            <input type="number" step="0.01" min="0" x-model="workerForm.costPerHalfDay" class="form-input" placeholder="0">
                        </div>
                        <div>
                            <label class="form-label">Priority</label>
                            <input type="number" min="1" x-model="workerForm.priority" class="form-input" placeholder="1">
                        </div>
                    </div>
                    <button type="button" @click="addWorker()" class="btn btn-primary btn-sm w-full" :disabled="busy">+ Add worker</button>

                @elseif ($key === 'materials')
                    <div>
                        <label class="form-label">Material name <span class="text-red-500">*</span></label>
                        <input type="text" x-model="materialForm.materialName" maxlength="255" class="form-input" placeholder="e.g. Urea 46-0-0">
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="form-label">Type</label>
                            <select x-model="materialForm.materialType" class="form-select">
                                @foreach ($materialTypes as $t)
                                    <option value="{{ $t }}">{{ ucfirst($t) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="form-label">Unit</label>
                            <select x-model="materialForm.unitOfMeasure" class="form-select">
                                @foreach ($materialUnits as $u)
                                    <option value="{{ $u }}">{{ $u }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="form-label">Price (₱)</label>
                            <input type="number" step="0.01" min="0" x-model="materialForm.priceAmount" class="form-input" placeholder="0">
                        </div>
                        <div>
                            <label class="form-label">per quantity</label>
                            <input type="number" step="0.0001" min="0.0001" x-model="materialForm.priceQuantity" class="form-input" placeholder="1">
                        </div>
                    </div>
                    <button type="button" @click="addMaterial()" class="btn btn-primary btn-sm w-full" :disabled="busy">+ Add material</button>

                @elseif ($key === 'services')
                    <div>
                        <label class="form-label">Service name <span class="text-red-500">*</span></label>
                        <input type="text" x-model="serviceForm.serviceName" maxlength="255" class="form-input" placeholder="e.g. Tractor / Land preparation">
                    </div>
                    <div>
                        <label class="form-label">Cost (₱)</label>
                        <input type="number" step="0.01" min="0" x-model="serviceForm.serviceCost" class="form-input" placeholder="0">
                    </div>
                    <button type="button" @click="addService()" class="btn btn-primary btn-sm w-full" :disabled="busy">+ Add service</button>
                @endif
            </div>

            {{-- Skip / Next --}}
            <div class="flex items-center justify-between pt-1">
                <button type="button" @click="skip('{{ $next }}')" class="btn btn-ghost btn-sm">Skip for now</button>
                @if ($next)
                    <button type="button" @click="next('{{ $next }}')" class="btn btn-outline btn-sm">Next: {{ ucfirst($next) }}</button>
                @else
                    <button type="button" @click="finish()" class="btn btn-accent btn-sm">Finish</button>
                @endif
            </div>
        </div>
    </div>
</section>
