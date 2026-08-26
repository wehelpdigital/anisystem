{{--
    All bottom-sheets for the Activities module.
    Expects: $schedule (lots, workers, materials, services, versions,
    defaultGroupings loaded), $activityTypes, $activeVersion.
--}}
@php
    // The phone's card and day menus are the only way to reach half these
    // actions on a small screen, so the same rule the card buttons follow has
    // to hold here too — otherwise locking the desktop row just moves the 403
    // one tap further in. Same treatment: shown, disabled, greyed.
    $mayEdit = \App\Support\WorkerContext::canEdit();
    // Notes answer for themselves; editing the plan is not permission to
    // write in the notebook.
    $mayNote = \App\Support\WorkerContext::canWriteModule('notes');
    // A separate question from the two above, and asked of every tier: the
    // owner closed the camera, the recorder, the drawing board, the maps and
    // the technician to workers outright, not to the ones who may not write.
    $isWorker = \App\Support\WorkerContext::activeGrant() !== null;
    $sheetLock = $mayEdit ? '' : ' is-locked';
    $sheetNoteLock = $mayNote ? '' : ' is-locked';
    $whyNoEdit = 'Only someone who can edit the plan may do this';
    $whyNoNote = 'You are not allowed to write notes on this schedule';
@endphp

{{-- How a control this viewer may not use looks. It lives here, beside the
     first thing that needs it, because the board is drawn by three partials
     and none of them owns the page's stylesheet. The component classes below
     set their own cursor and hover in an unlayered block, so Tailwind's
     utilities lose to them — this has to say it in the same voice. --}}
<style>
    .icon-btn.is-locked, .done-check.is-locked, .date-header-btn.is-locked,
    .btn.is-locked, .day-menu-action.is-locked,
    .note-kebab.is-locked,
    /* The families the gate reached last: the day's expense strip, the phone's
       floating +, the Versions sheet rows and the strip's own "+ Version". */
    .dx-btn.is-locked, .act-fab-add.is-locked,
    .version-sheet-row.is-locked, .chip.is-locked { opacity: .4; cursor: not-allowed; }
    /* No hover lift and no press bounce: nothing is going to happen. */
    .icon-btn.is-locked:hover, .done-check.is-locked:hover, .date-header-btn.is-locked:hover,
    .btn.is-locked:hover, .day-menu-action.is-locked:hover { background: transparent; }
    .done-check.is-locked:hover { border-color: var(--color-gray-300); }
    .icon-btn.is-locked:active, .done-check.is-locked:active { transform: none; background: transparent; }
    /* A note's three dots pay for their own hover tint, so `disabled` alone
       leaves a locked one looking live. Take the tint away too. */
    .note-kebab.is-locked:hover { background: transparent; }
    /* A reminder tick is locked in two shapes: the renderers put the class on
       the row, the sweep puts it on the checkbox it matched. Both mean the same
       thing, and neither meant anything at all until now — the row kept its
       finger cursor and the greying was whatever the browser felt like doing to
       a disabled checkbox. Twin of .act-check-row.is-locked. */
    .act-rem-row.is-locked { cursor: default; }
    .act-rem-row.is-locked input, .act-rem-row input.is-locked { opacity: .4; cursor: not-allowed; }
    .act-rem-row.is-locked .act-rem-name { opacity: .6; }
</style>

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
            {{-- Task / Irrigation / Service — each saved as an activity of the
                 matching type and shown on the timeline like any other. --}}
            <div class="activity-mode-tabs" id="activityModeTabs" role="tablist">
                <button type="button" class="activity-mode-tab is-active" data-mode="task" aria-selected="true">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
                    Task
                </button>
                <button type="button" class="activity-mode-tab" data-mode="irrigation" aria-selected="false">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3s6 6.686 6 11a6 6 0 11-12 0c0-4.314 6-11 6-11z"/></svg>
                    Irrigation
                </button>
                <button type="button" class="activity-mode-tab" data-mode="service" aria-selected="false">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5a4 4 0 105.03 5.03l4.35 4.35a2 2 0 11-2.83 2.83l-4.35-4.35A4 4 0 0111 5zM5 19l4-4"/></svg>
                    Service
                </button>
                {{-- Alongside the three kinds, not underneath one of them: who
                     is on the job is asked the same way as what the job is. It
                     does not change the activity's type — that stays whatever
                     was picked to its left. --}}
                <button type="button" class="activity-mode-tab" data-mode="payroll" data-act-tab="workers" aria-selected="false">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-1a4 4 0 00-4-4h-1M9 11a4 4 0 100-8 4 4 0 000 8zm8 0a3 3 0 100-6M2 20v-1a5 5 0 015-5h4a5 5 0 015 5v1H2z"/></svg>
                    Worker checklist
                    <span class="act-pane-count" id="activityWorkerCount" hidden>0</span>
                </button>
                {{-- The day's errands: things that are nobody's task and
                     nobody's wage, but still have to happen — and some of
                     which cost or bring in money once they do. --}}
                <button type="button" class="activity-mode-tab" data-mode="reminders" aria-selected="false">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l1.5 1.5L15 12"/></svg>
                    Reminder checklist
                    <span class="act-pane-count" id="activityReminderCount" hidden>0</span>
                </button>
            </div>

            <div id="activityDraftHint" class="hidden rounded-lg bg-blue-50 border border-blue-200 text-blue-800 text-xs px-3 py-2">
                Saved to <strong>Drafts</strong> for the date you pick below — kept off the timeline until you restore it. The Drafts list shows this date.
            </div>
            <div class="keep-on-workers">
                <label class="form-label" for="activityTitle">Title <span class="text-red-500">*</span></label>
                <input type="text" id="activityTitle" class="form-input" maxlength="255" placeholder="e.g. Basal Fertilizer Application">
            </div>

            {{-- When: choose between calendar dates and day-number (DAS/DAP/DAT) planning.
                 Both write the same date inputs — the date is what gets saved.
                 Stays on the worker checklist too: a day's pay is pay for a
                 particular day, and saving used to fail on a date field that
                 tab was hiding. --}}
            <div class="keep-on-workers">
                <div class="when-tabs" id="whenTabs" role="tablist">
                    <button type="button" class="when-tab is-active" id="whenTabDate" aria-selected="true">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        Date
                    </button>
                    <button type="button" class="when-tab" id="whenTabDas" aria-selected="false">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"/></svg>
                        By <span class="day-type-label">{{ $schedule->dayType }}</span>
                    </button>
                </div>
                <div id="whenPaneDate" class="when-pane">
                    <div>
                        <label class="form-label" for="activityTargetDate">Date <span class="text-red-500">*</span></label>
                        {{-- A date is one short fact, and a full-width dropdown
                             box announced it like a paragraph. The real input is
                             still here, laid over the pill and invisible, so a
                             tap opens the phone's own picker exactly as before
                             — only the reading of it changed. --}}
                        <div class="date-pill is-empty" id="activityDateField">
                            <svg class="dp-ico" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3M4 11h16M5 21h14a1 1 0 001-1V6a1 1 0 00-1-1H5a1 1 0 00-1 1v14a1 1 0 001 1z"/></svg>
                            <span class="dp-text" id="activityTargetDateText">Pick a date</span>
                            <input type="date" id="activityTargetDate" class="dp-input cal-only" inputmode="none" aria-label="Date">
                        </div>
                    </div>
                    {{-- An activity happens on a day. The end date is kept in the
                         markup, unasked, so work already saved as a range keeps
                         its span instead of silently losing it on the next
                         save. --}}
                    <input type="hidden" id="activityTargetEndDate">
                </div>
            {{-- DAS day-number lens over the date inputs (second pane of the chooser above) --}}
            <div id="activityDasRow" class="das-panel when-pane hidden">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="form-label" for="activityDasRefLot">Reference lot</label>
                        <select id="activityDasRefLot" class="form-select"></select>
                    </div>
                    <div>
                        <label class="form-label" for="activityStartDas"><span class="day-type-label">{{ $schedule->dayType }}</span> day</label>
                        <input type="number" id="activityStartDas" class="form-input" step="1" placeholder="e.g. 21">
                    </div>
                </div>
                {{-- Same reasoning as the end date: still here, never asked. --}}
                <input type="hidden" id="activityEndDas">
                <p class="text-xs text-blue-800 mt-2" id="activityDasAnchorNote"></p>
            </div>
            </div>

            <div>
                <span class="form-label">Lots
                    <span class="text-gray-400 font-normal">— pick none and it applies everywhere (N/A)</span>
                </span>
                <div id="activityLotsContainer" class="flex flex-wrap gap-2">
                    {{-- The name alone. The variety made every chip a sentence,
                         and a row of sentences is not a row you can scan. It is
                         on the tooltip for anyone who needs it. --}}
                    @foreach ($schedule->lots as $lot)
                        <button type="button" class="chip lot-chip" data-lot-id="{{ $lot->id }}" aria-pressed="false"
                            @if(!empty($lot->variety)) title="{{ $lot->variety }}" @endif>
                            {{ $lot->lotName }}
                        </button>
                    @endforeach
                    <button type="button" class="chip chip-dashed" id="quickAddLotBtn" data-chip-manual>+ Lot</button>
                </div>
                <div id="quickAddLotForm" class="hidden mt-2 p-3 rounded-xl border border-dashed border-gray-300 space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-gray-500 uppercase">New lot</span>
                        <button type="button" class="btn-ghost rounded-full w-7 h-7 flex items-center justify-center text-gray-400 hover:text-gray-700 js-quick-form-close" data-form="quickAddLotForm" aria-label="Close">✕</button>
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <input type="text" id="qalName" class="form-input" placeholder="Lot name *" maxlength="255">
                        <input type="text" id="qalVariety" class="form-input" placeholder="Variety (optional)" maxlength="255">
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <input type="number" id="qalSize" class="form-input" placeholder="Size" value="1" min="0" step="any" inputmode="decimal">
                        <input type="text" id="qalUnit" class="form-input" placeholder="Unit" value="ha" maxlength="50">
                    </div>
                    <button type="button" id="qalSave" class="btn btn-primary btn-sm w-full">Add lot</button>
                </div>
            </div>

            <div id="activityTypeWrap">
                <label class="form-label">Task type</label>
                {{-- Tags, not a dropdown, because one tank is often two
                     chemicals: a fungicide and an insecticide go out together
                     in the same knapsack all the time, and a single-choice
                     list forced that into two tasks or a half-truth. The first
                     one picked leads — it is what colours the card and what
                     the filters read — and the rest ride with it. --}}
                <div class="tt-tags" id="activityTypeTags" role="group" aria-label="Task type">
                    @foreach ($activityTypes as $slug => $label)
                        {{-- The kinds with a tab of their own are chosen there,
                             and the reminder checklist is not a task type at
                             all. --}}
                        @if (!in_array($slug, ['irrigation', 'service', 'worker_payroll', 'reminder_checklist'], true))
                            <button type="button" class="tt-tag" data-type="{{ $slug }}" aria-pressed="false">{{ $label }}</button>
                        @endif
                    @endforeach
                </div>
                <p class="form-hint" id="activityTypeHint">Pick one, or several if they go in the same tank.</p>
                <p class="tt-warn" id="activityTypeWarn" hidden></p>
                {{-- Kept, hidden, as the single source of the primary type:
                     everything from card colour to the DAS filters reads it. --}}
                <select id="activityType" class="hidden" tabindex="-1" aria-hidden="true">
                    <option value=""></option>
                    @foreach ($activityTypes as $slug => $label)
                        <option value="{{ $slug }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div id="activityWaterTaskWrap" class="hidden">
                <label class="form-label" for="activityWaterTask">Water task</label>
                <select id="activityWaterTask" class="form-select">
                    @foreach ($waterTasks as $slug => $label)
                        <option value="{{ $slug }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div id="activityServicePriceWrap" class="hidden">
                <label class="form-label" for="activityServicePrice">Service price (₱)</label>
                <input type="number" id="activityServicePrice" class="form-input" min="0" step="any" placeholder="0.00" inputmode="decimal">
                <p class="form-hint">The cost of this hired service for the lot(s) it applies to.</p>
            </div>

            {{-- Priority stays on the worker checklist too: a crew day can be
                 the critical one of the week, and its length cannot — which is
                 why the field beside it is not offered there. --}}
            <div class="grid grid-cols-2 gap-3 keep-on-workers">
                <div>
                    <label class="form-label" for="activityPriority">Priority</label>
                    <select id="activityPriority" class="form-select">
                        <option value="critical">Critical</option>
                        <option value="high">High</option>
                        <option value="medium" selected>Medium</option>
                        <option value="low">Low</option>
                    </select>
                </div>
                <div class="js-time-required">
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
                        <strong>Mark this activity as Day 0</strong><br>
                        <span class="text-amber-800/80">Its start date becomes the day-counter's Day 0 for every lot it covers (DAS 0 for DAS/DAT lots, DAP 0 for DAP lots). When several anchors conflict, the earliest date wins.</span>
                    </span>
                </label>
            </div>

            <div class="transplant-panel" id="activityTransplantPanel">
                <label class="flex items-start gap-3 cursor-pointer select-none">
                    <input type="checkbox" id="activityIsTransplant" class="mt-1 w-5 h-5 rounded border-green-400 text-green-600 focus:ring-green-300">
                    <span class="text-sm text-green-900">
                        <strong>Mark this activity as the transplant (DAT 0)</strong><br>
                        <span class="text-green-800/80">For transplanted crops (e.g. rice): activities before this date count in DAS from sowing; on or after it they count in DAT — a fresh counter starting from this activity's start date.</span>
                    </span>
                </label>
            </div>

            {{-- Reminder checklist builder. One line per errand: what it is,
                 and — only if it involves money — whether ticking it costs or
                 earns, and how much. --}}
            <div id="activityRemindersPane" class="hidden">
                <span class="form-label">Reminders
                    <span class="text-gray-400 font-normal">— ticked on the day, as they happen</span>
                </span>
                <div id="reminderRows" class="rem-rows"></div>
                <button type="button" id="addReminderRow" class="btn btn-white btn-sm mt-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                    Add a reminder
                </button>
                <p class="form-hint">A line with money attached only counts on the day it is ticked — an expense joins the cash to prepare, income joins the day's earnings.</p>
            </div>

            <div id="activityWorkersPane">
                <span class="form-label">Workers
                    <span class="text-gray-400 font-normal">— pick none for nobody assigned (N/A)</span>
                </span>
                <div id="activityWorkersContainer" class="flex flex-wrap gap-2">
                    @foreach ($schedule->workers as $w)
                        <button type="button" class="chip worker-chip" data-worker-id="{{ $w->id }}" aria-pressed="false">
                            {{ $w->workerName }}
                        </button>
                    @endforeach
                    <button type="button" class="chip chip-dashed" id="quickAddWorkerBtn" data-chip-manual>+ Worker</button>
                </div>
                {{-- Who is on this, for how much of the day, and at what rate.
                     It appears once someone is picked: an empty checklist is
                     just a heading in the way. --}}
                <div id="workerPayPanel" class="hidden mt-2 rounded-xl border border-gray-200 overflow-hidden">
                    <div class="flex items-center justify-between px-3 py-2 bg-gray-50">
                        <span class="text-xs font-bold text-gray-500 uppercase">Who will work in this schedule</span>
                        <span class="text-sm font-bold text-gray-800" id="workerPayTotal">₱0.00</span>
                    </div>
                    <div id="workerPayRows" class="p-2 space-y-1.5"></div>
                </div>
                {{-- Two questions about the roster, asked where the roster is.
                     They only appear once somebody is on the task, because
                     neither means anything about an empty list. --}}
                <div id="workerChecklistPane" class="hidden mt-2 space-y-2">
                    <label for="actWorkerChecklist" class="flex items-start gap-3 rounded-xl border border-gray-200 bg-white p-3 cursor-pointer select-none">
                        <input type="checkbox" id="actWorkerChecklist" class="w-5 h-5 rounded border-gray-300 mt-0.5 shrink-0">
                        <span class="min-w-0">
                            <span class="block text-sm font-semibold text-gray-900">Keep a worker checklist</span>
                            <span class="block text-xs text-gray-500">The card carries the roster. Ticking a name records that they worked, and their pay for the day counts.</span>
                        </span>
                    </label>
                    <label for="actWorkerSelfCheck" class="flex items-start gap-3 rounded-xl border border-gray-200 bg-white p-3 cursor-pointer select-none">
                        <input type="checkbox" id="actWorkerSelfCheck" class="w-5 h-5 rounded border-gray-300 mt-0.5 shrink-0">
                        <span class="min-w-0">
                            <span class="block text-sm font-semibold text-gray-900">Let workers tick their own name</span>
                            <span class="block text-xs text-gray-500">Workers with a login can sign themselves in on this task — their own row only.</span>
                        </span>
                    </label>
                </div>
                <div id="quickAddWorkerForm" class="hidden mt-2 p-3 rounded-xl border border-dashed border-gray-300 space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-gray-500 uppercase">New worker</span>
                        <button type="button" class="btn-ghost rounded-full w-7 h-7 flex items-center justify-center text-gray-400 hover:text-gray-700 js-quick-form-close" data-form="quickAddWorkerForm" aria-label="Close">✕</button>
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <input type="text" id="qawName" class="form-input" placeholder="Worker name *" maxlength="255">
                        <input type="number" id="qawRate" class="form-input" placeholder="₱ per half-day" min="0" step="any" inputmode="decimal">
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <input type="email" id="qawEmail" class="form-input" placeholder="Email (optional)" maxlength="255">
                        <input type="tel" id="qawPhone" class="form-input" placeholder="Phone (optional)" maxlength="32">
                    </div>
                    <button type="button" id="qawSave" class="btn btn-primary btn-sm w-full">Add worker</button>
                </div>
            </div>

            <div class="keep-on-workers">
                <div class="mb-1.5">
                    <label class="form-label mb-0!" for="activityDescription">Description</label>
                </div>
                <div class="sm-quill-wrap" id="activityDescriptionWrap">
                    <div class="quill-host-wrap"><div id="activityDescription"></div></div>
                    <textarea id="activityDescriptionSource" class="form-textarea quill-source font-mono text-xs" rows="8" placeholder="Raw HTML…"></textarea>
                </div>
            </div>

            {{-- Materials & Items — same dashed quick-add pattern as lots and
                 workers. Items are free-form: name + price + quantity + unit,
                 with names/prices remembered per schedule (datalists). --}}
            <div id="activityItemsSection">
                <span class="form-label"><span id="itemsSectionLabel">Materials &amp; Items</span> <span class="text-gray-400 font-normal">(optional)</span></span>
                <div class="flex flex-wrap items-center gap-1.5">
                    <div id="itemsContainer" class="contents"></div>
                    <button type="button" id="itemsToggleBtn" class="chip chip-dashed" aria-expanded="false" data-chip-manual>
                        <span id="itemsToggleLabel">+ Item</span>
                    </button>
                </div>
                <p id="itemsContainerEmpty" class="text-xs text-gray-400 mt-1.5">No items added yet.</p>

                <div id="itemPickerPanel" class="hidden mt-2 p-3 rounded-xl border border-dashed border-gray-300 space-y-2.5">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-gray-500 uppercase">New item</span>
                        <button type="button" class="btn-ghost rounded-full w-7 h-7 flex items-center justify-center text-gray-400 hover:text-gray-700 js-quick-form-close" data-form="itemPickerPanel" aria-label="Close">✕</button>
                    </div>
                    <div>
                        <label class="form-label text-xs! mb-1!" for="itemNameInput">Item name</label>
                        <input type="text" id="itemNameInput" class="form-input bg-white!" list="itemNameList" maxlength="255" placeholder="e.g. Urea 46-0-0" autocomplete="off">
                        <datalist id="itemNameList"></datalist>
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="form-label text-xs! mb-1!" for="itemPriceInput">Price (₱)</label>
                            <input type="number" id="itemPriceInput" class="form-input bg-white!" list="itemPriceList" min="0" step="any" placeholder="0.00" inputmode="decimal">
                            <datalist id="itemPriceList"></datalist>
                        </div>
                        <div>
                            <label class="form-label text-xs! mb-1!" for="itemQtyInput">Quantity</label>
                            <input type="number" id="itemQtyInput" class="form-input bg-white!" value="1" min="0" step="any" placeholder="1" inputmode="decimal">
                        </div>
                    </div>
                    <div>
                        <label class="form-label text-xs! mb-1!" for="itemUnitInput">Unit</label>
                        <input type="text" id="itemUnitInput" class="form-input bg-white!" list="itemUnitList" maxlength="30" placeholder="e.g. kg, bottle, pack" autocomplete="off">
                        <datalist id="itemUnitList">
                            @foreach (['kg','g','ml','l','bottle','sachet','piece','pack','bag','sack'] as $u)
                                <option value="{{ $u }}"></option>
                            @endforeach
                        </datalist>
                    </div>
                    <button type="button" id="addItemBtn" class="btn btn-primary btn-sm w-full">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                        Add to list
                    </button>
                </div>
            </div>

            {{-- Reference images — at the bottom, multiple allowed. --}}
            <div id="activityImagesSection">
                <span class="form-label">Reference images <span class="text-gray-400 font-normal">(optional, max 8 MB each)</span></span>
                <input type="file" id="activityImageFileInput" accept="image/jpeg,image/png,image/webp,image/gif" class="hidden" multiple>
                <div id="activityImagesGrid" class="grid grid-cols-3 sm:grid-cols-4 gap-2 mb-2"></div>
                <button type="button" id="activityImageUploadBtn" class="btn btn-white w-full">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    <span id="activityImageUploadLabel">Add images</span>
                </button>
            </div>
        </div>
    </div>
    <div class="sheet-footer">
        <button type="button" class="btn btn-ghost" data-sheet-close>Cancel</button>
        {{-- The sheet itself refuses to open for a viewer; if it is ever reached
             another way, the Save is the last door and it is shut too. --}}
        <button type="button" id="saveActivityBtn" class="btn btn-primary{{ $sheetLock }}"
                @disabled(! $mayEdit) @if (! $mayEdit) title="{{ $whyNoEdit }}" @endif>Save Activity</button>
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
                ['documentation', 'Documentation', 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
                ['post-harvest', 'Post-harvest', 'M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z'],
                ['notes', 'Notes', 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
                ['maps', 'Maps', 'M9 20l-5-2V6l5 2m0 12l6-2m-6 2V8m6 10l5 2V8l-5-2m0 12V6M9 8l6-2M15 11.5a2 2 0 11-4 0 2 2 0 014 0z'],
                ['gallery', 'Gallery', 'M4 6a2 2 0 012-2h5l2 2h5a2 2 0 012 2v9a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM8 14l2.5-3 2 2.5L15 10l3 4'],
                ['growth', 'Growth Stages', 'M12 21c0-4 1-7 4-9M12 21c0-5-2-8-6-9m6 9V8m0 0c0-2.5 1.5-4 4-4 0 2.5-1.5 4-4 4zm0 0C12 5.5 10.5 4 6.5 4c0 2.5 1.5 4 5.5 4z'],
                ['weather', 'Weather', 'M3 15a4 4 0 004 4h9a5 5 0 10-.9-9.95A5.5 5.5 0 006.5 8 4.5 4.5 0 003 15z'],
                ['ai', 'AI Technician', 'M12 3v2m0 0a7 7 0 017 7v3a3 3 0 01-3 3H8a3 3 0 01-3-3v-3a7 7 0 017-7zM9 12h.01M15 12h.01M9.5 17h5'],
            ];
            // Filtered rather than wrapped row by row: the shell's own MODULES
            // table drops the same three, so a row left here would open onto
            // nothing at all.
            if ($isWorker) {
                $closed = ['workers', 'maps', 'ai'];
                $modNav = array_values(array_filter($modNav, fn ($m) => ! in_array($m[0], $closed, true)));
            }
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

{{-- Tools menu: one sheet holding the toolbar actions (Drafts, Report, Search,
     Calendar, Weather, …). Each row forwards to the real button, so every
     handler is reused. Opens on every screen size. --}}
{{-- What the board shows (phones): both day filters behind one eye button,
     so the toolbar carries one control instead of two toggles. Rows forward
     to the real toolbar buttons and the sheet stays open, so you can set both
     and watch the board change behind it. --}}
{{-- What the crop is doing, and what it wants, on one day. Opened from the
     plant pill in a day header and from Tools. --}}
<div class="sheet sheet-full hidden" id="growthStageSheet" style="--sheet-width:30rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <div class="min-w-0">
            <h3 class="sheet-title">Growth stage</h3>
            <p class="text-xs text-gray-500" id="growthStageDate"></p>
        </div>
        <button type="button" id="gsFoldAll" class="btn btn-white btn-sm shrink-0 mr-1">Collapse all</button>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full -mr-1" aria-label="Close">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M18 6L6 18"/></svg>
        </button>
    </div>
    <div class="sheet-body" id="growthStageList" style="padding-bottom:1rem"></div>
</div>

<div class="sheet hidden" id="viewFilterSheet" style="--sheet-width:26rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title">What the board shows</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full -mr-1" aria-label="Close">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M18 6L6 18"/></svg>
        </button>
    </div>
    <div class="sheet-body" style="padding-bottom:1rem">
        {{-- The board's whole shape, first: list or month grid. Lived in the
             Tools menu, where "Calendar" next to Drafts and Weather read as
             a place rather than a way of seeing — this sheet is exactly the
             "how do I see the board" question. The name is the view a tap
             switches TO, mirrored from the real toolbar toggle by paint(). --}}
        <button type="button" class="view-filter-row" data-view-filter="view">
            <span class="vf-ico">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3M4 11h16M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            </span>
            <span class="grow min-w-0">
                <span class="vf-name" id="vfViewName">Calendar view</span>
                <span class="vf-sub" id="vfViewSub">See the days on a month grid</span>
            </span>
            <span class="vf-go">Switch</span>
        </button>
        <button type="button" class="view-filter-row" data-view-filter="empty">
            <span class="vf-ico">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
            </span>
            <span class="grow min-w-0">
                <span class="vf-name">Empty dates</span>
                <span class="vf-sub">Days with nothing scheduled</span>
            </span>
            <span class="vf-state" id="vfEmptyState">Shown</span>
        </button>
        <button type="button" class="view-filter-row" data-view-filter="done">
            <span class="vf-ico">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
            </span>
            <span class="grow min-w-0">
                <span class="vf-name">Finished days</span>
                <span class="vf-sub">Days where every activity is done</span>
            </span>
            <span class="vf-state" id="vfDoneState">Shown</span>
        </button>
        {{-- Only worth offering when something is actually hidden; the JS
             folds this row away when the count is zero. --}}
        <button type="button" class="view-filter-row is-gone" data-view-filter="hidden" id="vfHiddenRow">
            <span class="vf-ico">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.542 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
            </span>
            <span class="grow min-w-0">
                <span class="vf-name">Hidden activities</span>
                <span class="vf-sub" id="vfHiddenSub">Kept out of prints and exports</span>
            </span>
            <span class="vf-state is-off" id="vfHiddenState">Hidden</span>
        </button>
        {{-- The anchors the whole calendar counts from. Seeing them alone
             answers "where does this schedule actually start?" without
             scrolling the board looking for a badge. --}}
        <button type="button" class="view-filter-row" data-view-filter="dayzero">
            <span class="vf-ico">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3l2.4 4.9 5.4.8-3.9 3.8.9 5.4-4.8-2.5-4.8 2.5.9-5.4L4.2 8.7l5.4-.8L12 3z"/></svg>
            </span>
            <span class="grow min-w-0">
                <span class="vf-name">Day-zero activities only</span>
                <span class="vf-sub" id="vfDayZeroSub">The DAS 0 / DAP 0 / DAT 0 anchors</span>
            </span>
            <span class="vf-state is-off" id="vfDayZeroState">Off</span>
        </button>
        <button type="button" class="view-filter-row" data-view-filter="contract">
            <span class="vf-ico">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 11l7-7 7 7M5 19l7-7 7 7"/></svg>
            </span>
            <span class="grow min-w-0">
                <span class="vf-name">Contract all</span>
                <span class="vf-sub">Fold every day and card shut</span>
            </span>
            <span class="vf-go">Fold</span>
        </button>
    </div>
</div>

{{-- Plan versions (phones): pick one or start a new one; rows forward to
     the real chips in #versionStrip so all the existing logic runs. --}}
<div class="sheet hidden" id="versionsSheet" style="--sheet-width:26rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title">Plan versions</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full -mr-1" aria-label="Close">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M18 6L6 18"/></svg>
        </button>
    </div>
    <div class="sheet-body" id="versionsSheetList" style="padding-bottom:1rem"></div>
</div>

<div class="sheet hidden" id="activityActionsSheet" style="--sheet-width:26rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title">Tools</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full -mr-1" aria-label="Close">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M18 6L6 18"/></svg>
        </button>
    </div>
    <div class="sheet-body space-y-1">
        @php
            $actRows = [
                ['openDraftsBtn', 'Drafts', 'M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4', 'actDraftsBadge', ''],
                ['openReportBtn', 'Report', 'M11 3.055A9 9 0 1020.945 13H12a1 1 0 01-1-1V3.055zM15 3.936A9.02 9.02 0 0120.064 9H15V3.936z', '', ''],
                ['quickShareBtn', 'Quick Share', 'M8.68 13.34a3 3 0 100-2.68m0 2.68l6.64 3.86m-6.64-6.54l6.64-3.86m0 0a3 3 0 105.32-2.68 3 3 0 00-5.32 2.68zm0 13.08a3 3 0 105.32 2.68 3 3 0 00-5.32-2.68z', '', ''],
                ['weatherBtn', 'Weather', 'M3 15a4 4 0 004 4h9a5 5 0 10-.9-9.95A5.5 5.5 0 006.5 8 4.5 4.5 0 003 15z', '', ''],
                // The same view the plant pill in a day header opens, for
                // today — so it can be reached without hunting for the day.
                ['growthStageBtn', 'Growth stage', 'M12 21c0-4 1-7 4-9M12 21c0-5-2-8-6-9m6 9V8m0 0c0-2.5 1.5-4 4-4 0 2.5-1.5 4-4 4zm0 0C12 5.5 10.5 4 6.5 4c0 2.5 1.5 4 5.5 4z', '', ''],
                ['openNotesBtn', 'Notes', 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z', '', ''],
                // Today's camera, without first finding today on the board.
                ['captureTodayPhotoBtn', 'Capture a photo', 'M3 9a2 2 0 012-2h.93a2 2 0 001.66-.9l.82-1.2A2 2 0 0110.07 4h3.86a2 2 0 011.66.9l.82 1.2a2 2 0 001.66.9H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9zm12 4a3 3 0 11-6 0 3 3 0 016 0z', '', ''],
                ['recordTodayVideoBtn', 'Record a video', 'M15 10l4.55-2.28A1 1 0 0121 8.62v6.76a1 1 0 01-1.45.9L15 14M5 6h8a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2z', '', ''],
                ['openDrawBtn', 'Draw', 'M4 20l4-1L20 7a2 2 0 00-3-3L5 16l-1 4zM14 6l4 4', '', ''],
                ['openMapsBtn', 'Maps', 'M9 20l-5-2V6l5 2m0 12l6-2m-6 2V8m6 10l5 2V8l-5-2m0 12V6M9 8l6-2', '', ''],
                ['collabRoomBtn', 'Collab Room', 'M3 4h18M5 4v11a2 2 0 002 2h10a2 2 0 002-2V4M8 9h8M8 12h5M12 17v4m-3 0h6', '', ''],
                ['contractAllBtn', 'Contract All', 'M5 11l7-7 7 7M5 19l7-7 7 7', '', ''],
                ['toggleHiddenBtn', 'Show Hidden', 'M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21', '', 'actHiddenLabel'],
            ];
            // The AI chat bubble is hidden on phones (it covered the board), so
            // the menu is how it opens there. Forwarding to the bubble's own
            // launcher keeps a single open/close code path.
            if (\App\Models\AiSetting::current()?->isUsable() && ! $isWorker) {
                $actRows[] = ['aiFloatFab', 'AI Technician', 'M12 3v2m0 0a7 7 0 017 7v3a3 3 0 01-3 3H8a3 3 0 01-3-3v-3a7 7 0 017-7zM9 12h.01M15 12h.01M9.5 17h5', '', ''];
            }
            // The other four the owner closed (the technician is dropped just
            // above). Each row only forwards a click to a real toolbar button,
            // and those are not rendered for a worker either — a row left
            // standing would be a tap that does nothing, which reads as a
            // broken menu rather than a closed door.
            if ($isWorker) {
                $closedTools = ['captureTodayPhotoBtn', 'recordTodayVideoBtn', 'openDrawBtn', 'openMapsBtn'];
                $actRows = array_values(array_filter($actRows, fn ($r) => ! in_array($r[0], $closedTools, true)));
            }
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
    {{-- Writing on the day — a note, a photo, a clip, a drawing, a map — is
         exactly what "notes only" buys, so those rows follow $mayNote. Money,
         markers and moving the day are the plan, and follow $mayEdit. --}}
    <div class="sheet-body space-y-1">
        @if (! \App\Support\WorkerContext::activeGrant() || $mayNote)
        <button type="button" class="day-menu-action w-full flex items-center gap-3 rounded-xl px-3 py-3 text-left font-semibold text-gray-700 hover:bg-gray-50{{ $sheetNoteLock }}" data-action="date-note-btn" @disabled(! $mayNote) @if(! $mayNote) title="{{ $whyNoNote }}" @endif>
            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.5 20H7a2 2 0 01-2-2V5a2 2 0 012-2h6l4 4v3M9 8h3M9 12h3"/><path stroke-linecap="round" stroke-linejoin="round" d="M17 15v5m2.5-2.5h-5"/></svg>
            Add a note to this day
        </button>
        @endif
        {{-- The camera is the fastest note anyone writes. Standing in front of
             the thing you want to record, opening a note and hunting for an
             attach button is three steps too many — so the picture and the
             clip are offered where the day is, and become a note once they
             exist. --}}
        @if (! $isWorker)
        <button type="button" class="day-menu-action w-full flex items-center gap-3 rounded-xl px-3 py-3 text-left font-semibold text-gray-700 hover:bg-gray-50{{ $sheetNoteLock }}" data-action="capture-photo" @disabled(! $mayNote) @if(! $mayNote) title="{{ $whyNoNote }}" @endif>
            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.66-.9l.82-1.2A2 2 0 0110.07 4h3.86a2 2 0 011.66.9l.82 1.2a2 2 0 001.66.9H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            Capture a photo
        </button>
        @endif
        @if (! $isWorker)
        <button type="button" class="day-menu-action w-full flex items-center gap-3 rounded-xl px-3 py-3 text-left font-semibold text-gray-700 hover:bg-gray-50{{ $sheetNoteLock }}" data-action="record-video" @disabled(! $mayNote) @if(! $mayNote) title="{{ $whyNoNote }}" @endif>
            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10l4.55-2.28A1 1 0 0121 8.62v6.76a1 1 0 01-1.45.9L15 14M5 6h8a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2z"/></svg>
            Record a video
        </button>
        @endif
        {{-- A drawing and a map are things a day has, like a note — not things
             buried inside the note editor, which is where they used to hide. --}}
        @if (! $isWorker)
        <button type="button" class="day-menu-action w-full flex items-center gap-3 rounded-xl px-3 py-3 text-left font-semibold text-gray-700 hover:bg-gray-50{{ $sheetNoteLock }}" data-action="add-drawing" @disabled(! $mayNote) @if(! $mayNote) title="{{ $whyNoNote }}" @endif>
            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 20l4-1L20 7a2 2 0 00-3-3L5 16l-1 4zM14 6l4 4"/></svg>
            Add a drawing
        </button>
        @endif
        @if (! $isWorker)
        <button type="button" class="day-menu-action w-full flex items-center gap-3 rounded-xl px-3 py-3 text-left font-semibold text-gray-700 hover:bg-gray-50{{ $sheetNoteLock }}" data-action="add-map" @disabled(! $mayNote) @if(! $mayNote) title="{{ $whyNoNote }}" @endif>
            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 20l-5-2V6l5 2m0 12l6-2m-6 2V8m6 10l5 2V8l-5-2m0 12V6M9 8l6-2"/></svg>
            Add a map
        </button>
        @endif
        @if (! \App\Support\WorkerContext::activeGrant() || $mayEdit)
        <button type="button" class="day-menu-action w-full flex items-center gap-3 rounded-xl px-3 py-3 text-left font-semibold text-gray-700 hover:bg-gray-50{{ $sheetLock }}" data-action="day-expense-btn" @disabled(! $mayEdit) @if(! $mayEdit) title="{{ $whyNoEdit }}" @endif>
            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="8.5"/><path stroke-linecap="round" stroke-linejoin="round" d="M12 7v10M14.4 9.4a2.3 2.3 0 00-2.4-1.3c-1.3.1-2.3.8-2.3 1.9s1 1.7 2.5 1.9 2.6.8 2.6 2-1.1 1.9-2.5 1.9a2.4 2.4 0 01-2.4-1.3"/></svg>
            Add extra expense
        </button>
        @endif
        {{-- Only shown for days the forecast store actually holds a reading
             for; the board hides it otherwise (see savedWeatherDates). --}}
        <button type="button" id="daySavedWxRow" class="day-menu-action w-full flex items-center gap-3 rounded-xl px-3 py-3 text-left font-semibold text-gray-700 hover:bg-gray-50 hidden" data-action="view-saved-weather">
            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 15a4 4 0 004 4h9a5 5 0 10-.9-9.95A5.5 5.5 0 006.5 8 4.5 4.5 0 003 15z"/></svg>
            View saved weather
        </button>
        @if (! \App\Support\WorkerContext::activeGrant() || $mayEdit)
        <button type="button" class="day-menu-action w-full flex items-center gap-3 rounded-xl px-3 py-3 text-left font-semibold text-gray-700 hover:bg-gray-50{{ $sheetLock }}" data-action="add-income" @disabled(! $mayEdit) @if(! $mayEdit) title="{{ $whyNoEdit }}" @endif>
            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <rect x="2.5" y="7.5" width="19" height="11" rx="2"/>
                <circle cx="12" cy="13" r="2.4"/>
                <path stroke-linecap="round" d="M6 11.5v3M18 11.5v3"/>
            </svg>
            Add income
        </button>
        @endif
        @if (! \App\Support\WorkerContext::activeGrant() || $mayEdit)
        <button type="button" class="day-menu-action w-full flex items-center gap-3 rounded-xl px-3 py-3 text-left font-semibold text-gray-700 hover:bg-gray-50{{ $sheetLock }}" data-action="date-marker-btn" @disabled(! $mayEdit) @if(! $mayEdit) title="{{ $whyNoEdit }}" @endif>
            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/></svg>
            Resume-here marker
        </button>
        @endif
        <button type="button" class="day-menu-action w-full flex items-center gap-3 rounded-xl px-3 py-3 text-left font-semibold text-gray-700 hover:bg-gray-50" data-action="share-day-btn">
            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.68 13.34a3 3 0 100-2.68m0 2.68l6.64 3.86m-6.64-6.54l6.64-3.86m0 0a3 3 0 105.32-2.68 3 3 0 00-5.32 2.68zm0 13.08a3 3 0 105.32 2.68 3 3 0 00-5.32-2.68z"/></svg>
            Share this day
        </button>
        @if (! \App\Support\WorkerContext::activeGrant() || $mayEdit)
        <button type="button" class="day-menu-action w-full flex items-center gap-3 rounded-xl px-3 py-3 text-left font-semibold text-gray-700 hover:bg-gray-50{{ $sheetLock }}" data-action="change-group-date-btn" @disabled(! $mayEdit) @if(! $mayEdit) title="{{ $whyNoEdit }}" @endif>
            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            Move this day to…
        </button>
        @endif
        {{-- "Move this day to <day number>" lived here too. Moving a day by
             its date is the same move said in the way people actually think
             about it, and two doors to one action only invited the wrong
             one. --}}
        @if (! \App\Support\WorkerContext::activeGrant() || $mayEdit)
        <button type="button" class="day-menu-action w-full flex items-center gap-3 rounded-xl px-3 py-3 text-left font-semibold text-red-600 hover:bg-red-50{{ $sheetLock }}" data-action="delete-group-date-btn" @disabled(! $mayEdit) @if(! $mayEdit) title="{{ $whyNoEdit }}" @endif>
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 6h18M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6M8 6V4a2 2 0 012-2h4a2 2 0 012 2v2M10 11v6M14 11v6"/></svg>
            Delete all activities this day
        </button>
        @endif
    </div>
</div>

{{-- What the forecast said for one day, as it was last saved. Read-only: it
     is the record a report or the AI technician would look back at. --}}
<div class="sheet hidden" id="savedWeatherSheet" style="--sheet-width:34rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title truncate" id="savedWeatherTitle">Saved weather</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full -mr-1" aria-label="Close">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M18 6L6 18"/></svg>
        </button>
    </div>
    <div class="sheet-body" id="savedWeatherBody">
        <p class="text-sm text-gray-500 text-center py-6">Loading…</p>
    </div>
</div>

{{-- Activity details (phones). Cards clamp their title and description to one
     line to stay scannable, so tapping one opens the whole thing here. The body
     is filled with a clone of the card itself, which keeps this in step with
     whatever a card shows without a third copy of that markup to maintain. --}}
<div class="sheet hidden" id="activityInfoSheet" style="--sheet-width:30rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title" id="activityInfoTitle">Activity details</h3>
        <button type="button" class="icon-btn" data-sheet-close aria-label="Close">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
    </div>
    <div class="sheet-body activity-info-body" id="activityInfoBody"></div>
    <div class="sheet-footer">
        <button type="button" class="btn btn-ghost" data-sheet-close>Close</button>
        <button type="button" class="btn btn-primary" id="activityInfoEdit">Edit</button>
    </div>
</div>

<div class="sheet hidden" id="cardMenuSheet" style="--sheet-width:24rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        {{-- Not truncated: this sheet exists to act on one activity, so which
             one it is has to be legible. Two lines, then an ellipsis. --}}
        <h3 class="sheet-title card-menu-title" id="cardMenuTitle">Activity</h3>
        <button data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body">
        <div class="grid gap-1">
            @if (! \App\Support\WorkerContext::activeGrant() || $mayEdit)
            <button type="button" class="btn btn-ghost justify-start!{{ $sheetLock }}" data-card-menu-action="edit" @disabled(! $mayEdit) @if(! $mayEdit) title="{{ $whyNoEdit }}" @endif>Edit</button>
            @endif
            @if (! \App\Support\WorkerContext::activeGrant() || $mayEdit)
            <button type="button" class="btn btn-ghost justify-start!{{ $sheetLock }}" data-card-menu-action="move" @disabled(! $mayEdit) @if(! $mayEdit) title="{{ $whyNoEdit }}" @endif>Move to date…</button>
            @endif
            @if (! \App\Support\WorkerContext::activeGrant() || $mayEdit)
            <button type="button" class="btn btn-ghost justify-start!{{ $sheetLock }}" data-card-menu-action="duplicate" @disabled(! $mayEdit) @if(! $mayEdit) title="{{ $whyNoEdit }}" @endif>Duplicate</button>
            @endif
            {{-- Reading what went on this ground before is a read. It stays. --}}
            <button type="button" class="btn btn-ghost justify-start!" data-card-menu-action="advanced">Advanced info</button>
            @if (! \App\Support\WorkerContext::activeGrant() || $mayEdit)
            <button type="button" class="btn btn-ghost justify-start!{{ $sheetLock }}" data-card-menu-action="tag" @disabled(! $mayEdit) @if(! $mayEdit) title="{{ $whyNoEdit }}" @endif>Tag a drawing, map or note</button>
            @endif
            @if (! \App\Support\WorkerContext::activeGrant() || $mayEdit)
            <button type="button" class="btn btn-ghost justify-start!{{ $sheetLock }}" data-card-menu-action="draft" @disabled(! $mayEdit) @if(! $mayEdit) title="{{ $whyNoEdit }}" @endif>Move to drafts</button>
            @endif
            @if (! \App\Support\WorkerContext::activeGrant() || $mayEdit)
            <button type="button" class="btn btn-ghost justify-start! text-red-600!{{ $sheetLock }}" data-card-menu-action="delete" @disabled(! $mayEdit) @if(! $mayEdit) title="{{ $whyNoEdit }}" @endif>Delete</button>
            @endif
        </div>
    </div>
</div>

{{-- ============== WHAT WENT ON THIS GROUND BEFORE, AND WHEN ============== --}}
<div class="sheet hidden" id="advInfoSheet" style="--sheet-width:26rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title truncate" id="advInfoTitle">Advanced info</h3>
        <button data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body" style="padding-bottom:1rem">
        <p class="text-xs text-gray-500 mb-2" id="advInfoSub"></p>
        <div id="advInfoBody"><p class="text-sm text-gray-500 text-center py-6">Loading…</p></div>
    </div>
</div>

{{-- ==================== WHY THE DAY COSTS WHAT IT COSTS ==================== --}}
<div class="sheet hidden" id="dayCashSheet" style="--sheet-width:26rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title truncate" id="dayCashTitle">Cash for this day</h3>
        <button data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body" style="padding-bottom:1rem">
        <div id="dayCashBody"></div>
    </div>
</div>

{{-- ==================== TAG A DRAWING, MAP OR NOTE ONTO AN ACTIVITY ==================== --}}
<div class="sheet hidden" id="activityTagSheet" style="--sheet-width:26rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title truncate" id="activityTagTitle">Tag this activity</h3>
        <button data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body space-y-3" style="padding-bottom:1rem">
        <div id="activityTagCurrent"></div>
        <div class="flex gap-1 p-1 rounded-xl bg-gray-100" id="activityTagTabs">
            <button type="button" class="btn btn-sm grow is-on" data-tag-tab="drawing">Drawings</button>
            <button type="button" class="btn btn-sm grow" data-tag-tab="map">Maps</button>
            <button type="button" class="btn btn-sm grow" data-tag-tab="note">Notes</button>
        </div>
        <div id="activityTagList" class="space-y-1"></div>
    </div>
</div>

{{-- ============================ SHARE A DAY (public link) ============================ --}}
<div class="sheet hidden" id="dayShareSheet" style="--sheet-width:26rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title truncate" id="dayShareTitle">Share this day</h3>
        <button data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body space-y-3">
        <p class="text-sm text-gray-600">Anyone with this link can view this day's schedule — no login needed. Great for Facebook, Messenger, or email.</p>
        <div class="flex gap-2">
            <input type="text" id="dayShareLink" class="form-input grow" readonly onclick="this.select()">
            <button type="button" id="dayShareCopy" class="btn btn-secondary shrink-0">Copy</button>
        </div>
        <div class="grid grid-cols-2 gap-2">
            <a id="dayShareFb" href="#" target="_blank" rel="noopener" class="btn btn-ghost justify-start!">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M22 12a10 10 0 10-11.56 9.88v-6.99H7.9V12h2.54V9.8c0-2.5 1.49-3.89 3.78-3.89 1.09 0 2.24.2 2.24.2v2.46h-1.26c-1.24 0-1.63.77-1.63 1.56V12h2.78l-.44 2.89h-2.34v6.99A10 10 0 0022 12z"/></svg>
                Facebook
            </a>
            <a id="dayShareWa" href="#" target="_blank" rel="noopener" class="btn btn-ghost justify-start!">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17.47 14.38c-.3-.15-1.75-.86-2.02-.96-.27-.1-.47-.15-.67.15-.2.3-.77.96-.94 1.16-.17.2-.35.22-.64.07-.3-.15-1.25-.46-2.38-1.47-.88-.78-1.47-1.75-1.64-2.05-.17-.3-.02-.46.13-.6.13-.13.3-.35.45-.52.15-.17.2-.3.3-.5.1-.2.05-.37-.02-.52-.08-.15-.67-1.6-.92-2.2-.24-.58-.49-.5-.67-.5l-.57-.01c-.2 0-.52.07-.8.37-.27.3-1.04 1.02-1.04 2.48 0 1.46 1.07 2.87 1.22 3.07.15.2 2.1 3.2 5.08 4.49.71.3 1.26.49 1.69.62.71.23 1.36.2 1.87.12.57-.08 1.75-.72 2-1.4.25-.7.25-1.28.17-1.4-.07-.13-.27-.2-.57-.35zM12 2a10 10 0 00-8.5 15.3L2 22l4.8-1.5A10 10 0 1012 2z"/></svg>
                WhatsApp
            </a>
            <a id="dayShareEmail" href="#" class="btn btn-ghost justify-start!">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                Email
            </a>
            <button type="button" id="dayShareNative" class="btn btn-ghost justify-start!" style="display:none">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.68 13.34a3 3 0 100-2.68m0 2.68l6.64 3.86m-6.64-6.54l6.64-3.86m0 0a3 3 0 105.32-2.68 3 3 0 00-5.32 2.68zm0 13.08a3 3 0 105.32 2.68 3 3 0 00-5.32-2.68z"/></svg>
                More…
            </button>
        </div>
        {{-- Send this day straight to a community connection --}}
        <div>
            <label class="form-label">Send to a co-farmer</label>
            <div class="js-share-cofarmers space-y-1 max-h-52 overflow-y-auto rounded-xl border border-gray-100 p-1" data-link-input="dayShareLink">
                <p class="text-sm text-gray-400 px-2 py-3 text-center">Loading…</p>
            </div>
            <p class="form-hint">Only your accepted connections show here. They open the public link — no login needed.</p>
        </div>
    </div>
</div>

{{-- ============================ QUICK SHARE (whole plan / email workers) ============================ --}}
<div class="sheet hidden" id="quickShareSheet" style="--sheet-width:27rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title">Quick Share</h3>
        <button data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body space-y-5">
        <div>
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">Share the whole plan</p>
            <p class="text-sm text-gray-600 mb-2">A public page anyone can open — no login needed. Great for Facebook or Messenger.</p>
            <div class="flex gap-2">
                <input type="text" id="quickShareLink" class="form-input grow" readonly onclick="this.select()">
                <button type="button" id="quickShareCopy" class="btn btn-secondary shrink-0">Copy</button>
            </div>
            <div class="grid grid-cols-3 gap-2 mt-2">
                <a id="quickShareFb" href="#" target="_blank" rel="noopener" class="btn btn-ghost">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M22 12a10 10 0 10-11.56 9.88v-6.99H7.9V12h2.54V9.8c0-2.5 1.49-3.89 3.78-3.89 1.09 0 2.24.2 2.24.2v2.46h-1.26c-1.24 0-1.63.77-1.63 1.56V12h2.78l-.44 2.89h-2.34v6.99A10 10 0 0022 12z"/></svg>
                    Facebook
                </a>
                <a id="quickShareWa" href="#" target="_blank" rel="noopener" class="btn btn-ghost">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17.47 14.38c-.3-.15-1.75-.86-2.02-.96-.27-.1-.47-.15-.67.15-.2.3-.77.96-.94 1.16-.17.2-.35.22-.64.07-.3-.15-1.25-.46-2.38-1.47-.88-.78-1.47-1.75-1.64-2.05-.17-.3-.02-.46.13-.6.13-.13.3-.35.45-.52.15-.17.2-.3.3-.5.1-.2.05-.37-.02-.52-.08-.15-.67-1.6-.92-2.2-.24-.58-.49-.5-.67-.5l-.57-.01c-.2 0-.52.07-.8.37-.27.3-1.04 1.02-1.04 2.48 0 1.46 1.07 2.87 1.22 3.07.15.2 2.1 3.2 5.08 4.49.71.3 1.26.49 1.69.62.71.23 1.36.2 1.87.12.57-.08 1.75-.72 2-1.4.25-.7.25-1.28.17-1.4-.07-.13-.27-.2-.57-.35zM12 2a10 10 0 00-8.5 15.3L2 22l4.8-1.5A10 10 0 1012 2z"/></svg>
                    WhatsApp
                </a>
                <a id="quickShareEmail" href="#" class="btn btn-ghost">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    Email
                </a>
            </div>
        </div>

        <div class="border-t border-gray-200"></div>

        <div>
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">Send to a co-farmer</p>
            <p class="text-sm text-gray-600 mb-2">Send the public plan link to one of your community connections as a message.</p>
            <div class="js-share-cofarmers space-y-1 max-h-52 overflow-y-auto rounded-xl border border-gray-100 p-1" data-link-input="quickShareLink">
                <p class="text-sm text-gray-400 px-2 py-3 text-center">Loading…</p>
            </div>
        </div>

        <div class="border-t border-gray-200"></div>

        <div>
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">Email the plan to workers</p>
            {{-- The parenthesis is directions to a module a worker no longer
                 has, so it is dropped for them rather than left pointing at a
                 door that is not there. --}}
            @php $emailHint = $isWorker ? '' : ' (add emails in the Workers module)'; @endphp
            <p class="text-sm text-gray-600 mb-2">Sends the day's activities to workers who have a registered email{{ $emailHint }}.</p>
            <div class="grid gap-2">
                {{-- Copying the link is reading; posting the day to every worker
                     on the roster is not, so these two follow $mayEdit while the
                     rest of the sheet stays open to anyone. --}}
                <button type="button" class="btn btn-white justify-start! quick-email-btn{{ $sheetLock }}" data-scope="today"
                        @disabled(! $mayEdit) @if (! $mayEdit) title="{{ $whyNoEdit }}" @endif>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    Email <strong class="mx-1">today's</strong> plan
                </button>
                <button type="button" class="btn btn-white justify-start! quick-email-btn{{ $sheetLock }}" data-scope="tomorrow"
                        @disabled(! $mayEdit) @if (! $mayEdit) title="{{ $whyNoEdit }}" @endif>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    Email <strong class="mx-1">tomorrow's</strong> plan
                </button>
            </div>
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
        <p class="text-sm text-gray-600">Move <strong id="moveDateName" class="text-gray-900"></strong> to a new day.</p>

        {{-- The same two ways of saying "when" the add-activity sheet offers.
             A move is the same question — and a grower who plans by day number
             had to work out the calendar date themselves before they could
             answer it. --}}
        <div class="when-tabs" id="moveWhenTabs" role="tablist">
            <button type="button" class="when-tab is-active" id="moveTabDate" aria-selected="true">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                Date
            </button>
            <button type="button" class="when-tab" id="moveTabDas" aria-selected="false">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"/></svg>
                By day number
            </button>
        </div>

        <div class="when-pane" id="moveWhenDate">
            <label class="form-label" for="moveDateInput">New date</label>
            {{-- A pill you tap, with the real input laid over it invisibly, so
                 the phone opens its own picker — the same control the add
                 sheet uses. --}}
            <div class="date-pill is-empty" id="moveDateField">
                <svg class="dp-ico" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3M4 11h16M5 21h14a1 1 0 001-1V6a1 1 0 00-1-1H5a1 1 0 00-1 1v14a1 1 0 001 1z"/></svg>
                <span class="dp-text" id="moveDateText">Pick a date</span>
                <input type="date" id="moveDateInput" class="dp-input cal-only" inputmode="none" aria-label="New date">
            </div>
        </div>

        <div class="when-pane hidden das-panel" id="moveWhenDas">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="form-label" for="moveDasLot">Reference lot</label>
                    <select id="moveDasLot" class="form-select"></select>
                </div>
                <div>
                    <label class="form-label" for="moveDasDay"><span id="moveDasLabel">Day</span></label>
                    <input type="number" id="moveDasDay" class="form-input" step="1" placeholder="e.g. 21">
                </div>
            </div>
            {{-- Which count, when the lot keeps two: a transplanted lot is DAS
                 until the transplant and DAT after it, and the same number
                 means a different day in each. --}}
            <div class="move-counters" id="moveCounters"></div>
            <p class="text-xs text-blue-800 mt-2" id="moveDasNote"></p>
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
        @include('partials.date-tag', ['id' => 'changeGroupDateNew', 'empty' => 'Pick a date'])
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
        {{-- Dismiss: silences the pulsing Notice without hiding it. Re-arms
             itself if the outstanding items later change. --}}
        <div id="readinessMuteBar" class="hidden mt-4 pt-3 border-t border-gray-100 items-center justify-between gap-3">
            <span class="text-xs text-gray-500" id="readinessMuteHint"></span>
            <button type="button" id="readinessMuteBtn" class="btn btn-white btn-sm shrink-0"></button>
        </div>
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
<div class="sheet sheet-full hidden" id="dateNoteSheet" style="--sheet-width:30rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title" id="dateNoteSheetTitle">Note for this date</h3>
        <button data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body space-y-3">
        <input type="hidden" id="dateNoteDate">
        <div>
            <label class="form-label" for="dateNoteDatePicker">Date</label>
            <input type="date" id="dateNoteDatePicker" class="form-input cal-only" inputmode="none">
        </div>
        <div>
            <div class="flex items-center justify-between gap-2">
                <label class="form-label mb-0">Note</label>
                <button type="button" id="dateNoteDrawBtn" class="btn btn-white btn-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 20l4-1 10-10-3-3L5 16l-1 4z"/></svg>
                    Draw
                </button>
            </div>
            <div class="rich-editor mt-1.5">
                <div id="dateNoteEditor" style="min-height: 8rem;"></div>
            </div>
        </div>
        <p class="form-hint">Notes are scoped to the current version — forks carry their own copies. They render on printed documents too. Use <b>Draw</b> to sketch and drop it straight into the note.</p>
    </div>
    <div class="sheet-footer">
        <button type="button" id="dateNoteClearBtn" class="btn btn-danger-outline mr-auto hidden">Clear Note</button>
        <button type="button" class="btn btn-ghost" data-sheet-close>Cancel</button>
        <button type="button" id="dateNoteSaveBtn" class="btn btn-primary">Save Note</button>
    </div>
</div>

{{-- ============================ DAY EXPENSE ============================ --}}
<div class="sheet hidden" id="dayExpenseSheet" style="--sheet-width:28rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title" id="dayExpenseSheetTitle">Add extra expense</h3>
        <button data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body space-y-3">
        <input type="hidden" id="dayExpenseDate">
        <input type="hidden" id="dayExpenseId">
        <p class="text-sm text-gray-600" id="dayExpenseForDate"></p>
        <div>
            <label class="form-label" for="dayExpenseAmount">Amount (₱)</label>
            <input type="number" id="dayExpenseAmount" class="form-input" inputmode="decimal" step="0.01" min="0" placeholder="0.00">
        </div>
        <div>
            <label class="form-label" for="dayExpenseNote">Note / description</label>
            <input type="text" id="dayExpenseNote" class="form-input" maxlength="500" placeholder="e.g. Fuel for the water pump">
        </div>
        <p class="form-hint">Extra costs beyond materials &amp; labour — logged against this date and rolled into your reports.</p>
    </div>
    <div class="sheet-footer">
        <button type="button" id="dayExpenseDeleteBtn" class="btn btn-danger-outline mr-auto hidden">Delete</button>
        <button type="button" class="btn btn-ghost" data-sheet-close>Cancel</button>
        <button type="button" id="dayExpenseSaveBtn" class="btn btn-primary">Save Expense</button>
    </div>
</div>

{{-- Money the day brought in: the mirror of the expense sheet above, for
     the services a farm sells alongside the crop. --}}
<div class="sheet hidden" id="dayMapPickSheet" style="--sheet-width:26rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title">Add a map to this day</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full -mr-1" aria-label="Close">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M18 6L6 18"/></svg>
        </button>
    </div>
    <div class="sheet-body" style="padding-bottom:1rem">
        <div id="dayMapList" class="space-y-1.5"></div>
        <a href="#" id="dayMapNew" class="btn btn-white w-full mt-3">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/></svg>
            Draw a new map
        </a>
    </div>
</div>

<div class="sheet hidden" id="dayIncomeSheet" style="--sheet-width:28rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title" id="dayIncomeSheetTitle">Add income</h3>
        <button data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">&#10005;</button>
    </div>
    <div class="sheet-body space-y-3">
        <input type="hidden" id="dayIncomeDate">
        <input type="hidden" id="dayIncomeId">
        <p class="text-sm text-gray-600" id="dayIncomeForDate"></p>
        <div>
            <label class="form-label" for="dayIncomeAmount">Amount (&#8369;)</label>
            <input type="number" id="dayIncomeAmount" class="form-input" inputmode="decimal" step="0.01" min="0" placeholder="0.00">
        </div>
        <div>
            <label class="form-label" for="dayIncomeTitle">Title</label>
            <input type="text" id="dayIncomeTitle" class="form-input" maxlength="191" placeholder="e.g. Tractor rental to a neighbour">
        </div>
        <div>
            <label class="form-label" for="dayIncomeNote">Description (optional)</label>
            <textarea id="dayIncomeNote" class="form-input" maxlength="500" rows="3" style="min-height:5rem; resize:vertical" placeholder="Anything worth remembering about it"></textarea>
        </div>
        <div id="dayIncomeList" class="space-y-1"></div>
        <p class="form-hint">Earnings during the season that are not the harvest itself &mdash; logged against this date.</p>
    </div>
    <div class="sheet-footer">
        <button type="button" id="dayIncomeDeleteBtn" class="btn btn-danger-outline mr-auto hidden">Delete</button>
        <button type="button" class="btn btn-ghost" data-sheet-close>Cancel</button>
        <button type="button" id="dayIncomeSaveBtn" class="btn btn-primary">Save income</button>
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
                <span class="block">Labor Report</span>
                <span class="block text-xs font-normal text-gray-400">Costs, busiest months and worker earnings</span>
            </span>
            <svg class="w-4 h-4 text-gray-300 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
        </button>
    </div>
</div>

{{-- ==================== NOTE ON A DONE (LOCKED) ACTIVITY ==================== --}}
<div class="sheet hidden" id="doneNoteSheet" style="--sheet-width:26rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title">Add a note</h3>
        <button data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body">
        <p class="text-sm text-gray-500 mb-2">
            <strong class="text-gray-800" id="doneNoteTitle">This activity</strong> is marked done and locked.
            You can still add a note — it is stamped with the date and appended to the activity.
        </p>
        <textarea id="doneNoteText" class="form-textarea" rows="4" maxlength="2000"
            placeholder="e.g. Harvested 40 sacks; left the wet rows for tomorrow."></textarea>
        <div class="mt-3">
            <input type="file" id="doneNoteImages" accept="image/*" class="hidden" multiple>
            <div id="doneNoteThumbs" class="grid grid-cols-4 gap-2 mb-2"></div>
            <button type="button" id="doneNoteAddImages" class="btn btn-white btn-sm w-full">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.66-.9l.82-1.2A2 2 0 0110.07 4h3.86a2 2 0 011.66.9l.82 1.2a2 2 0 001.66.9H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                Add photos or use camera
            </button>
        </div>
    </div>
    <div class="sheet-footer">
        <button type="button" class="btn btn-ghost" data-sheet-close>Cancel</button>
        <button type="button" id="saveDoneNoteBtn" class="btn btn-primary">Save note</button>
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
        <div class="rounded-xl border border-gray-200 bg-gray-50 p-3" id="duplicateVersionZone">
            <p class="text-sm text-gray-600 mb-2">Make a full copy of this version — every activity (items, lots, workers) and its date notes.</p>
            <button type="button" id="duplicateVersionBtn" class="btn btn-white btn-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                Duplicate This Version
            </button>
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
