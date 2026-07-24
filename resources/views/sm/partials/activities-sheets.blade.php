{{--
    All bottom-sheets for the Activities module.
    Expects: $schedule (lots, workers, materials, services, versions,
    defaultGroupings loaded), $activityTypes, $activeVersion.
--}}

{{-- ============================ ADD / EDIT ACTIVITY ============================ --}}
<div class="sheet hidden" id="activitySheet" style="--sheet-width:44rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title" id="activitySheetTitle">Add Activity</h3>
        <button data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body">
        <input type="hidden" id="activityId">
        <div class="space-y-4">
            <div>
                <label class="form-label" for="activityTitle">Title <span class="text-red-500">*</span></label>
                <input type="text" id="activityTitle" class="form-input" maxlength="255" placeholder="e.g. Basal Fertilizer Application">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="form-label" for="activityTargetDate">Start date <span class="text-red-500">*</span></label>
                    <input type="date" id="activityTargetDate" class="form-input">
                </div>
                <div>
                    <label class="form-label" for="activityTargetEndDate">End date <span class="text-gray-400 font-normal">(optional)</span></label>
                    <div class="relative">
                        <input type="date" id="activityTargetEndDate" class="form-input pr-10">
                        <button type="button" id="activityTargetEndDateClear" class="absolute right-2 top-1/2 -translate-y-1/2 p-1 text-gray-400 hover:text-gray-600" title="Clear end date (single-day activity)">✕</button>
                    </div>
                </div>
            </div>

            {{-- DAS day-number lens over the date inputs (shown only when an anchored lot is selected) --}}
            <div id="activityDasRow" class="das-panel hidden">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div>
                        <label class="form-label text-blue-900!" for="activityDasRefLot">Reference lot</label>
                        <select id="activityDasRefLot" class="form-select"></select>
                    </div>
                    <div>
                        <label class="form-label text-blue-900!" for="activityStartDas">Start <span class="day-type-label">{{ $schedule->dayType }}</span></label>
                        <input type="number" id="activityStartDas" class="form-input" step="1" placeholder="e.g. 21">
                    </div>
                    <div>
                        <label class="form-label text-blue-900!" for="activityEndDas">End <span class="day-type-label">{{ $schedule->dayType }}</span></label>
                        <input type="number" id="activityEndDas" class="form-input" step="1" placeholder="optional">
                    </div>
                </div>
                <p class="text-xs text-blue-800 mt-2" id="activityDasAnchorNote"></p>
            </div>

            <div>
                <span class="form-label">Lots</span>
                @if ($schedule->lots->count())
                    <div id="activityLotsContainer" class="flex flex-wrap gap-2">
                        <button type="button" class="chip chip-dashed lot-chip lot-chip-na" data-lot-na="1" aria-pressed="false"
                            title="Applies generally — not tied to any specific lot">N/A — Not lot-specific</button>
                        @foreach ($schedule->lots as $lot)
                            <button type="button" class="chip lot-chip" data-lot-id="{{ $lot->id }}" aria-pressed="false">
                                {{ $lot->lotName }}@if(!empty($lot->variety)) · {{ $lot->variety }}@endif
                            </button>
                        @endforeach
                    </div>
                @else
                    <div class="rounded-xl bg-amber-50 border border-amber-200 text-amber-800 text-sm px-4 py-3">
                        No lots defined yet — the activity will be saved as general (N/A).
                        <a href="{{ route('sm.lots', ['id' => $schedule->id]) }}" class="font-semibold underline">Add lots</a>
                    </div>
                    <div id="activityLotsContainer" class="hidden"></div>
                @endif
            </div>

            <div>
                <label class="form-label" for="activityType">Activity type</label>
                <select id="activityType" class="form-select">
                    <option value="">— select a type —</option>
                    @foreach ($activityTypes as $slug => $label)
                        <option value="{{ $slug }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="form-label" for="activityPriority">Priority</label>
                    <select id="activityPriority" class="form-select">
                        <option value="critical">Critical</option>
                        <option value="high">High</option>
                        <option value="medium" selected>Medium</option>
                        <option value="low">Low</option>
                    </select>
                </div>
                <div>
                    <label class="form-label" for="activityTimeRequired">Time required</label>
                    <select id="activityTimeRequired" class="form-select">
                        <option value="half" selected>Half Day</option>
                        <option value="whole">Whole Day</option>
                        <option value="n/a">N/A</option>
                    </select>
                </div>
            </div>

            <div class="day-zero-panel" id="activityDayZeroPanel">
                <label class="flex items-start gap-3 cursor-pointer select-none">
                    <input type="checkbox" id="activityIsDayZero" class="mt-1 w-5 h-5 rounded border-amber-400 text-amber-600 focus:ring-amber-300">
                    <span class="text-sm text-amber-900">
                        <strong>Mark this activity as <span class="day-type-label">{{ $schedule->dayType }}</span> 0</strong><br>
                        <span class="text-amber-800/80">Its start date becomes Day 0 for every lot it covers. When several anchors conflict, the earliest date wins.</span>
                    </span>
                </label>
            </div>

            <div>
                <span class="form-label">Workers <span class="text-gray-400 font-normal">(optional)</span></span>
                <div id="activityWorkersContainer" class="flex flex-wrap gap-2">
                    @foreach ($schedule->workers as $w)
                        <button type="button" class="chip worker-chip" data-worker-id="{{ $w->id }}" aria-pressed="false">
                            {{ $w->workerName }} <span class="opacity-70">#{{ $w->priority }}</span>
                        </button>
                    @endforeach
                    @if ($schedule->workers->isEmpty())
                        <p class="text-xs text-gray-400">No workers defined yet.</p>
                    @endif
                </div>
            </div>

            <div>
                <div class="flex items-center justify-between mb-1.5">
                    <label class="form-label mb-0!" for="activityDescription">Description</label>
                    <button type="button" id="toggleDescriptionMode" class="text-xs font-semibold text-brand-700">
                        <span id="toggleDescriptionModeLabel">Edit HTML source</span>
                    </button>
                </div>
                <div class="sm-quill-wrap" id="activityDescriptionWrap">
                    <div class="quill-host-wrap"><div id="activityDescription"></div></div>
                    <textarea id="activityDescriptionSource" class="form-textarea quill-source font-mono text-xs" rows="8" placeholder="Raw HTML…"></textarea>
                </div>
            </div>

            <div>
                <span class="form-label">Reference image <span class="text-gray-400 font-normal">(optional, max 8 MB)</span></span>
                <input type="hidden" id="activityImagePath">
                <input type="file" id="activityImageFileInput" accept="image/jpeg,image/png,image/webp,image/gif" class="hidden">
                <div id="activityImageEmpty">
                    <button type="button" id="activityImageUploadBtn" class="btn btn-white w-full">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        Upload Image
                    </button>
                </div>
                <div id="activityImageWrap" class="hidden">
                    <img id="activityImagePreview" src="" alt="Activity image preview" class="w-full max-h-64 object-contain rounded-xl border border-gray-200 bg-gray-50">
                    <div class="flex gap-2 mt-2">
                        <button type="button" id="activityImageReplaceBtn" class="btn btn-white btn-sm grow">Replace</button>
                        <button type="button" id="activityImageRemoveBtn" class="btn btn-danger-outline btn-sm grow">Remove</button>
                    </div>
                </div>
            </div>

            <div>
                <span class="form-label">Materials &amp; Services <span class="text-gray-400 font-normal">(optional)</span></span>
                <div class="rounded-xl border border-gray-200 p-3 space-y-2">
                    <div class="grid grid-cols-2 gap-2">
                        <select id="itemPickerType" class="form-select">
                            <option value="material">Material</option>
                            <option value="service">Service</option>
                        </select>
                        <select id="itemPickerId" class="form-select"></select>
                    </div>
                    <div class="grid grid-cols-[1fr_1fr_auto] gap-2">
                        <input type="number" id="itemPickerQty" class="form-input" value="1" min="0" step="any" placeholder="Qty">
                        <select id="itemPickerUnit" class="form-select">
                            <option value="">— unit —</option>
                            @foreach (['kg','g','ml','l','bottle','sachet','piece','pack'] as $u)
                                <option value="{{ $u }}">{{ $u }}</option>
                            @endforeach
                        </select>
                        <button type="button" id="addItemBtn" class="btn btn-outline btn-sm">Add</button>
                    </div>
                    <div id="itemsContainer" class="flex flex-wrap gap-1.5"></div>
                    <p id="itemsContainerEmpty" class="text-xs text-gray-400">No materials or services added. That's fine — you can attach them later.</p>
                </div>
            </div>
        </div>
    </div>
    <div class="sheet-footer">
        <button type="button" class="btn btn-ghost" data-sheet-close>Cancel</button>
        <button type="button" id="saveActivityBtn" class="btn btn-primary">Save Activity</button>
    </div>
</div>

{{-- ============================ MOBILE CARD ACTION MENU ============================ --}}
{{-- Module switcher — opened by the toolbar hamburger. Rows are handled by the
     SPA engine, which fetches the module as a partial and injects it. --}}
<div class="sheet hidden" id="modulesSheet" style="--sheet-width:26rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title">Schedule modules</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full -mr-1" aria-label="Close">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M18 6L6 18"/></svg>
        </button>
    </div>
    <div class="sheet-body space-y-1">
        @php
            $modNav = [
                ['activities', 'Activities', 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01'],
                ['settings', 'Settings', 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z'],
                ['lots', 'Lots', 'M9 20l-5-2V6l5 2m0 12l6-2m-6 2V8m6 10l5 2V8l-5-2m0 12V6M9 8l6-2'],
                ['workers', 'Workers', 'M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-1.13a4 4 0 10-4-4 4 4 0 004 4z'],
                ['materials', 'Materials', 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4'],
                ['services', 'Services', 'M11.42 15.17L17.25 21A2.652 2.652 0 0021 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 11-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 004.486-6.336l-3.276 3.277a3.004 3.004 0 01-2.25-2.25l3.276-3.276a4.5 4.5 0 00-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085m-1.745 1.437L5.909 7.5H4.5L2.25 3.75l1.5-1.5L7.5 4.5v1.409l4.26 4.26m-1.745 1.437l1.745-1.437'],
                ['documentation', 'Documentation', 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
                ['irrigations', 'Irrigation', 'M12 3s6 6.686 6 11a6 6 0 11-12 0c0-4.314 6-11 6-11z'],
                ['post-harvest', 'Post-harvest', 'M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z'],
                ['notes', 'Notes', 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
                ['ai', 'AI Technician', 'M12 3v2m0 0a7 7 0 017 7v3a3 3 0 01-3 3H8a3 3 0 01-3-3v-3a7 7 0 017-7zM9 12h.01M15 12h.01M9.5 17h5'],
            ];
        @endphp
        @foreach ($modNav as [$key, $label, $icon])
            <button type="button" class="module-nav-row w-full flex items-center gap-3 rounded-xl px-3 py-3 text-left font-semibold text-gray-700 hover:bg-gray-50"
                    data-module="{{ $key }}">
                <span class="w-9 h-9 rounded-xl bg-brand-50 text-brand-600 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}"/></svg>
                </span>
                <span class="grow">{{ $label }}</span>
                <svg class="w-4 h-4 text-gray-300 module-nav-check hidden" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
            </button>
        @endforeach
        <a href="{{ route('sm.index') }}" class="w-full flex items-center gap-3 rounded-xl px-3 py-3 text-left font-semibold text-gray-500 hover:bg-gray-50">
            <span class="w-9 h-9 rounded-xl bg-gray-100 text-gray-500 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
            </span>
            All cropping schedules
        </a>
    </div>
</div>

{{-- Activity tools (phones): one menu holding the toolbar actions. Each row
     forwards to the real (desktop-only) button, so every handler is reused. --}}
<div class="sheet hidden" id="activityActionsSheet" style="--sheet-width:26rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title">Activity tools</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full -mr-1" aria-label="Close">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M18 6L6 18"/></svg>
        </button>
    </div>
    <div class="sheet-body space-y-1">
        @php
            $actRows = [
                ['openDraftsBtn', 'Drafts', 'M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4', 'actDraftsBadge', ''],
                ['openReportBtn', 'Report', 'M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z', '', ''],
                ['openSearchBtn', 'Search & filter', 'M21 21l-4.35-4.35M17 10.5a6.5 6.5 0 11-13 0 6.5 6.5 0 0113 0z', 'actFilterBadge', ''],
                ['viewToggleBtn', 'Calendar', 'M8 7V3m8 4V3M4 11h16M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z', '', 'actViewLabel'],
                ['toggleHiddenBtn', 'Show Hidden', 'M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21', '', 'actHiddenLabel'],
                ['activityUndoBtn', 'Undo', 'M3 10h10a5 5 0 015 5v1m-15-6l4-4m-4 4l4 4', 'actUndoBadge', ''],
                ['activityRedoBtn', 'Redo', 'M21 10H11a5 5 0 00-5 5v1m15-6l-4-4m4 4l-4 4', 'actRedoBadge', ''],
            ];
        @endphp
        @foreach ($actRows as [$target, $label, $icon, $badgeId, $labelId])
            <button type="button" class="activity-action-row w-full flex items-center gap-3 rounded-xl px-3 py-3 text-left font-semibold text-gray-700 hover:bg-gray-50 disabled:opacity-40 disabled:pointer-events-none"
                    data-forward="{{ $target }}">
                <span class="w-9 h-9 rounded-xl bg-brand-50 text-brand-600 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}"/></svg>
                </span>
                <span class="grow"@if ($labelId) id="{{ $labelId }}"@endif>{{ $label }}</span>
                @if ($badgeId)
                    <span id="{{ $badgeId }}" class="badge badge-gray" style="display:none">0</span>
                @endif
            </button>
        @endforeach
    </div>
</div>

{{-- Day actions (phones): mirrors the desktop-only date-header buttons. Each row
     forwards to the real button so all existing handlers are reused as-is. --}}
<div class="sheet hidden" id="dayMenuSheet" style="--sheet-width:24rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title truncate" id="dayMenuTitle">This day</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full -mr-1" aria-label="Close">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M18 6L6 18"/></svg>
        </button>
    </div>
    <div class="sheet-body space-y-1">
        <button type="button" class="day-menu-action w-full flex items-center gap-3 rounded-xl px-3 py-3 text-left font-semibold text-gray-700 hover:bg-gray-50" data-action="date-note-btn">
            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            Note for this day
        </button>
        <button type="button" class="day-menu-action w-full flex items-center gap-3 rounded-xl px-3 py-3 text-left font-semibold text-gray-700 hover:bg-gray-50" data-action="date-marker-btn">
            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/></svg>
            Resume-here marker
        </button>
        <button type="button" class="day-menu-action w-full flex items-center gap-3 rounded-xl px-3 py-3 text-left font-semibold text-gray-700 hover:bg-gray-50" data-action="change-group-date-btn">
            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            Move this day to…
        </button>
        <button type="button" class="day-menu-action w-full flex items-center gap-3 rounded-xl px-3 py-3 text-left font-semibold text-gray-700 hover:bg-gray-50" data-action="move-group-das-btn">
            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            Move this day to {{ $schedule->dayType }}…
        </button>
        <button type="button" class="day-menu-action w-full flex items-center gap-3 rounded-xl px-3 py-3 text-left font-semibold text-red-600 hover:bg-red-50" data-action="delete-group-date-btn">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
            Delete all activities this day
        </button>
    </div>
</div>

<div class="sheet hidden" id="cardMenuSheet" style="--sheet-width:24rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title truncate" id="cardMenuTitle">Activity</h3>
        <button data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body">
        <div class="grid gap-1">
            <button type="button" class="btn btn-ghost justify-start!" data-card-menu-action="edit">Edit</button>
            <button type="button" class="btn btn-ghost justify-start!" data-card-menu-action="move">Move to date…</button>
            <button type="button" class="btn btn-ghost justify-start!" data-card-menu-action="duplicate">Duplicate</button>
            <button type="button" class="btn btn-ghost justify-start!" data-card-menu-action="hide"><span id="cardMenuHideLabel">Hide from presentations</span></button>
            <button type="button" class="btn btn-ghost justify-start!" data-card-menu-action="draft">Move to drafts</button>
            <button type="button" class="btn btn-ghost justify-start! text-red-600!" data-card-menu-action="delete">Delete</button>
        </div>
    </div>
</div>

{{-- ============================ MOVE SINGLE CARD TO DATE ============================ --}}
<div class="sheet hidden" id="moveDateSheet" style="--sheet-width:24rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title">Move activity</h3>
        <button data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body space-y-3">
        <p class="text-sm text-gray-600">Move <strong id="moveDateName" class="text-gray-900"></strong> to a new date.</p>
        <div>
            <label class="form-label" for="moveDateInput">New date</label>
            <input type="date" id="moveDateInput" class="form-input">
        </div>
        <p class="form-hint">Multi-day activities keep their duration — the end date shifts by the same number of days.</p>
    </div>
    <div class="sheet-footer">
        <button type="button" class="btn btn-ghost" data-sheet-close>Cancel</button>
        <button type="button" id="confirmMoveDateBtn" class="btn btn-primary">Move</button>
    </div>
</div>

{{-- ============================ CHANGE GROUP DATE ============================ --}}
<div class="sheet hidden" id="changeGroupDateSheet" style="--sheet-width:26rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title">Change group date</h3>
        <button data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body space-y-3">
        <input type="hidden" id="changeGroupDateOld">
        <p class="text-sm text-gray-600">
            Move all <strong id="changeGroupDateCount" class="text-gray-900">0</strong> activities on
            <strong id="changeGroupDateCurrent" class="text-gray-900"></strong> to:
        </p>
        <input type="date" id="changeGroupDateNew" class="form-input">
        <p class="form-hint">Multi-day activities keep their duration — end dates shift by the same number of days.</p>
    </div>
    <div class="sheet-footer">
        <button type="button" class="btn btn-ghost" data-sheet-close>Cancel</button>
        <button type="button" id="confirmChangeGroupDateBtn" class="btn btn-primary">Move Activities</button>
    </div>
</div>

{{-- ==================== CALENDAR: ONE DAY ==================== --}}
<div class="sheet hidden" id="calDaySheet" style="--sheet-width:30rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <div class="min-w-0">
            <h3 class="sheet-title" id="calDayTitle"></h3>
            <p class="text-xs text-gray-500" id="calDayMeta"></p>
        </div>
        <button data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body space-y-2" id="calDayList"></div>
    <div class="sheet-footer">
        <button type="button" class="btn btn-ghost" data-sheet-close>Close</button>
        <button type="button" class="btn btn-primary" id="calDayAddBtn">Add activity here</button>
    </div>
</div>

{{-- ======================== SETUP / READINESS ======================== --}}
<div class="sheet hidden" id="readinessSheet" style="--sheet-width:30rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title">Finish setting up</h3>
        <button data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body">
        <p class="text-sm text-gray-600 mb-2" id="readinessIntro"></p>
        <div id="readinessList"></div>
        <div id="readinessAllClear" class="hidden text-center py-8">
            <div class="w-14 h-14 mx-auto rounded-full bg-brand-50 text-brand-600 flex items-center justify-center mb-3">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
            </div>
            <p class="font-bold text-gray-900">Nothing left to set up</p>
            <p class="text-sm text-gray-500 mt-1">This cropping plan has everything it needs.</p>
        </div>
    </div>
</div>

{{-- ==================== MOVE DAY TO DAS/DAT ==================== --}}
<div class="sheet hidden" id="moveGroupDasSheet" style="--sheet-width:26rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title">Move day to <span class="day-type-label">{{ $schedule->dayType }}</span></h3>
        <button data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body space-y-3">
        <input type="hidden" id="moveGroupDasOld">
        <p class="text-sm text-gray-600">
            Move all <strong id="moveGroupDasCount" class="text-gray-900">0</strong> activities on
            <strong id="moveGroupDasCurrent" class="text-gray-900"></strong> to a day number instead of a calendar date.
        </p>
        <div>
            <label class="form-label" for="moveGroupDasRefLot">Counted from which lot's day 0</label>
            <select id="moveGroupDasRefLot" class="form-select"></select>
        </div>
        <div>
            <label class="form-label" for="moveGroupDasValue">
                <span class="day-type-label">{{ $schedule->dayType }}</span> number
            </label>
            <input type="number" id="moveGroupDasValue" class="form-input" step="1" placeholder="e.g. 21">
        </div>
        <p class="form-hint" id="moveGroupDasPreview"></p>
        <p class="form-hint">Multi-day activities keep their duration — end dates shift by the same number of days.</p>
    </div>
    <div class="sheet-footer">
        <button type="button" class="btn btn-ghost" data-sheet-close>Cancel</button>
        <button type="button" id="confirmMoveGroupDasBtn" class="btn btn-primary">Move Activities</button>
    </div>
</div>

{{-- ============================ DATE NOTE ============================ --}}
<div class="sheet hidden" id="dateNoteSheet" style="--sheet-width:30rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title" id="dateNoteSheetTitle">Note for this date</h3>
        <button data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body space-y-3">
        <input type="hidden" id="dateNoteDate">
        <p class="text-sm font-semibold text-gray-700" id="dateNoteSheetDate"></p>
        <textarea id="dateNoteContent" class="form-textarea" rows="5" maxlength="20000" placeholder="What happens on this day? Notes render on printed documents too."></textarea>
        <p class="form-hint">Notes are scoped to the current version — forks carry their own copies.</p>
    </div>
    <div class="sheet-footer">
        <button type="button" id="dateNoteClearBtn" class="btn btn-danger-outline mr-auto hidden">Clear Note</button>
        <button type="button" class="btn btn-ghost" data-sheet-close>Cancel</button>
        <button type="button" id="dateNoteSaveBtn" class="btn btn-primary">Save Note</button>
    </div>
</div>

{{-- ============================ PROGRESS MARKER ============================ --}}
<div class="sheet hidden" id="markerSheet" style="--sheet-width:30rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title" id="markerSheetTitle">Resume-here marker</h3>
        <button data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body space-y-3">
        <input type="hidden" id="progressMarkerDate">
        <input type="hidden" id="progressMarkerId">
        <p class="text-sm text-gray-600">A dashed amber "Resume here" line will render after <strong id="markerSheetDate" class="text-gray-900"></strong>.</p>
        <textarea id="progressMarkerNote" class="form-textarea" rows="4" maxlength="5000" placeholder="Optional note — where you left off, what's next…"></textarea>
    </div>
    <div class="sheet-footer">
        <button type="button" id="progressMarkerClearBtn" class="btn btn-danger-outline mr-auto hidden">Remove Marker</button>
        <button type="button" class="btn btn-ghost" data-sheet-close>Cancel</button>
        <button type="button" id="progressMarkerSaveBtn" class="btn btn-primary">Save Marker</button>
    </div>
</div>

{{-- ============================ DRAFTS ============================ --}}
<div class="sheet hidden" id="draftsSheet" style="--sheet-width:36rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title">Drafts</h3>
        <button data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body">
        <div id="draftsListContainer" class="space-y-2"></div>
        <div id="draftsEmpty" class="text-center text-gray-500 py-8 hidden">
            <p class="font-bold text-gray-800 mb-1">No drafts.</p>
            <p class="text-sm">Use the archive button on an activity card to park it here without deleting.</p>
        </div>
    </div>
</div>

{{-- ============================ REPORT PICKER ============================ --}}
<div class="sheet hidden" id="reportPickerSheet" style="--sheet-width:28rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title">Generate a report</h3>
        <button data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body space-y-2">
        <p class="text-sm text-gray-500 mb-1">Which report do you want to generate?</p>
        <button type="button" id="reportLaborBtn"
            class="w-full flex items-center gap-3 rounded-xl px-3 py-3 text-left font-semibold text-gray-700 hover:bg-gray-50 border border-gray-200">
            <span class="w-9 h-9 rounded-xl bg-brand-50 text-brand-600 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </span>
            <span class="grow">
                <span class="block">Labor Costings</span>
                <span class="block text-xs font-normal text-gray-400">Cost of workers across the plan</span>
            </span>
            <svg class="w-4 h-4 text-gray-300 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
        </button>
    </div>
</div>

{{-- ============================ LABOR SUMMARY ============================ --}}
<div class="sheet hidden" id="laborSheet" style="--sheet-width:56rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title">Labor Expenses</h3>
        <button data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body">
        <div class="rounded-xl bg-gray-100 text-gray-600 text-xs px-4 py-2.5 mb-3">
            <strong class="text-gray-800">cost = Σ(worker half-day rates) × units/day × days</strong>
            &nbsp;·&nbsp; whole = 2 units, half = 1, N/A = 0 &nbsp;·&nbsp; drafts excluded
        </div>

        <div class="card p-3 mb-3 space-y-3">
            @if ($schedule->defaultGroupings->count())
                <div>
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-gray-500 uppercase">Groups</span>
                        <span>
                            <button type="button" id="laborSelectAllGroups" class="text-xs font-semibold text-brand-700">All</button>
                            <span class="text-gray-300">·</span>
                            <button type="button" id="laborClearGroups" class="text-xs font-semibold text-brand-700">None</button>
                        </span>
                    </div>
                    <div class="scroll-chips mt-1" id="laborGroupsContainer" data-chip-group>
                        @foreach ($schedule->defaultGroupings as $g)
                            <button type="button" class="chip shrink-0 min-h-9! py-1! text-xs" data-value="{{ $g->id }}"
                                data-lot-ids="{{ $g->lots->pluck('id')->implode(',') }}">{{ $g->groupName }}</button>
                        @endforeach
                    </div>
                </div>
            @endif
            @if ($schedule->workers->count())
                <div>
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-gray-500 uppercase">Workers</span>
                        <span>
                            <button type="button" id="laborSelectAllWorkers" class="text-xs font-semibold text-brand-700">All</button>
                            <span class="text-gray-300">·</span>
                            <button type="button" id="laborClearWorkers" class="text-xs font-semibold text-brand-700">None</button>
                        </span>
                    </div>
                    <div class="scroll-chips mt-1" id="laborWorkersContainer" data-chip-group>
                        @foreach ($schedule->workers as $w)
                            <button type="button" class="chip shrink-0 min-h-9! py-1! text-xs" data-value="{{ $w->id }}">{{ $w->workerName }} <span class="opacity-70">#{{ $w->priority }}</span></button>
                        @endforeach
                    </div>
                </div>
            @endif
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                <div>
                    <label class="form-label text-xs!" for="laborDasMin"><span class="day-type-label">{{ $schedule->dayType }}</span> min</label>
                    <input type="number" id="laborDasMin" class="form-input" step="1" placeholder="−∞">
                </div>
                <div>
                    <label class="form-label text-xs!" for="laborDasMax"><span class="day-type-label">{{ $schedule->dayType }}</span> max</label>
                    <input type="number" id="laborDasMax" class="form-input" step="1" placeholder="+∞">
                </div>
                <div>
                    <label class="form-label text-xs!" for="laborStartDate">From date</label>
                    <input type="date" id="laborStartDate" class="form-input">
                </div>
                <div>
                    <label class="form-label text-xs!" for="laborEndDate">To date</label>
                    <input type="date" id="laborEndDate" class="form-input">
                </div>
            </div>
            <div class="flex items-center gap-2 flex-wrap">
                <button type="button" id="laborApplyFiltersBtn" class="btn btn-primary btn-sm">Apply Filters</button>
                <button type="button" id="laborResetFiltersBtn" class="btn btn-white btn-sm">Reset</button>
                <span id="laborFilterHint" class="text-xs text-gray-500"></span>
            </div>
        </div>

        <div id="laborSummaryBody"></div>
    </div>
    <div class="sheet-footer">
        <button type="button" id="laborCopyBtn" class="btn btn-white mr-auto">Copy as Text</button>
        <button type="button" id="laborPrintBtn" class="btn btn-white">Print</button>
        <button type="button" class="btn btn-ghost" data-sheet-close>Close</button>
    </div>
</div>

{{-- ============================ NEW VERSION ============================ --}}
<div class="sheet hidden" id="versionSheet" style="--sheet-width:30rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title">New Version</h3>
        <button data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body space-y-4">
        <div>
            <label class="form-label" for="newVersionName">Version name <span class="text-red-500">*</span></label>
            <input type="text" id="newVersionName" class="form-input" maxlength="120" placeholder='e.g. "Budget Cut V1"'>
        </div>
        <div>
            <label class="form-label" for="newVersionDescription">Description <span class="text-gray-400 font-normal">(optional)</span></label>
            <textarea id="newVersionDescription" class="form-textarea" rows="3" maxlength="5000" placeholder="Why this branch exists"></textarea>
        </div>
        <div>
            <label class="form-label" for="newVersionSource">Fork from</label>
            <select id="newVersionSource" class="form-select">
                @foreach ($schedule->versions as $v)
                    <option value="{{ $v->id }}" @if($v->isActive) selected @endif>{{ $v->versionName }}@if($v->isOriginal) (Original)@endif</option>
                @endforeach
                <option value="">Blank — start with no activities</option>
            </select>
            <p class="form-hint">Forking deep-clones every activity (items, lots, workers) plus the date notes of the source version.</p>
        </div>
        <label class="flex items-center gap-3 cursor-pointer select-none">
            <input type="checkbox" id="newVersionSetActive" class="w-5 h-5 rounded border-gray-300 text-brand-600 focus:ring-brand-300" checked>
            <span class="text-sm text-gray-700">Switch to this version after creating</span>
        </label>
    </div>
    <div class="sheet-footer">
        <button type="button" class="btn btn-ghost" data-sheet-close>Cancel</button>
        <button type="button" id="saveNewVersionBtn" class="btn btn-primary">Create Version</button>
    </div>
</div>

{{-- ============================ MANAGE (RENAME / DELETE) CURRENT VERSION ============================ --}}
<div class="sheet hidden" id="manageVersionSheet" style="--sheet-width:30rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title">Manage Version</h3>
        <button data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body space-y-4">
        <input type="hidden" id="renameVersionId">
        <div>
            <label class="form-label" for="renameVersionName">Version name <span class="text-red-500">*</span></label>
            <input type="text" id="renameVersionName" class="form-input" maxlength="120">
        </div>
        <div>
            <label class="form-label" for="renameVersionDescription">Description</label>
            <textarea id="renameVersionDescription" class="form-textarea" rows="3" maxlength="5000"></textarea>
        </div>
        <div id="deleteVersionZone" class="rounded-xl border border-red-200 bg-red-50 p-3 hidden">
            <p class="text-sm text-red-800 mb-2">Deleting this version soft-deletes every activity inside it. The Original version becomes active again.</p>
            <button type="button" id="deleteVersionBtn" class="btn btn-danger-outline btn-sm">Delete This Version</button>
        </div>
        <p id="originalVersionHint" class="form-hint hidden">The Original version is the baseline and cannot be deleted.</p>
    </div>
    <div class="sheet-footer">
        <button type="button" class="btn btn-ghost" data-sheet-close>Cancel</button>
        <button type="button" id="saveRenameVersionBtn" class="btn btn-primary">Save Changes</button>
    </div>
</div>
