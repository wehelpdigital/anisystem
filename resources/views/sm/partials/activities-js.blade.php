{{--
    Activities module page JS. Vanilla JS against the global helpers in
    resources/js/app.js (api, toast, openSheet/closeSheet, confirmAction,
    chipValues, escapeHtml, fmtPeso, fmtNumber).

    Sections:
      1. Constants, lookups, URL map, tiny helpers
      2. Day-0 / DAS machinery (recomputeLotDayZero, computeDasLabel)
      3. Undo stack (10-step LIFO + Ctrl+Z)
      4. Card renderer + timeline rebuild (dual-render twin of the blade partials)
      5. Filters (search / type chips / hide-lots chips / show-hidden toggle)
      6. Add–edit activity sheet (lots, workers, DAS lens, Day-0, Quill, image, items)
      7. Per-card actions (edit, duplicate, draft, delete, hide, mobile menu, move-to-date)
      8. Date-group actions (add-to-date, change date, delete group)
      9. Date notes
     10. Progress markers
     11. Drag & drop
     12. Drafts
     13. Labor report (navigates to the full report page)
     14. Versions
--}}
<script>
document.addEventListener('DOMContentLoaded', () => {
    'use strict';

    /* ================================================================
     * 1. CONSTANTS + LOOKUPS
     * ================================================================ */

    const SCHEDULE_ID = @json($schedule->id);
    // The Maps module and what it has already saved — the day's "Add a map"
    // attaches one of those rather than making the day draw its own.
    const MAPS_URL = @json(route('sm.maps', ['id' => $schedule->id]));
    const MAP_SAVES_URL = @json(route('sm.map.saves')) + '?scheduleId=' + @json($schedule->id);
    const DAY_TYPE_DEFAULT = @json($schedule->dayType ?: 'DAS');
    const STORAGE_BASE = @json(asset('storage'));

    const ACTIVITY_TYPE_LABELS = @json($activityTypes);
    const WATER_TASK_LABELS = @json(\App\Models\AsScheduleActivity::WATER_TASKS);
    const WATER_TASK_COLORS = @json(\App\Models\AsScheduleActivity::WATER_TASK_COLORS);
    let activityMode = 'task';   // 'task' | 'irrigation' — the add-activity sheet mode
    // Who is looking, in the two terms a checklist cares about: may they
    // change the plan at all, and which roster row is theirs. An owner has no
    // roster row and needs none — CAN_EDIT already covers them.
    const CAN_EDIT = @json(\App\Support\WorkerContext::canEdit());
    const ME_WORKER_ID = @json(optional(\App\Support\WorkerContext::activeGrant())->scheduleWorkerId);
    // The third answer the board needs: a worker can be trusted with the day's
    // notes without being trusted with the plan. Anyone who may edit may write
    // notes, so the two are folded into one question for the note controls.
    const CAN_ADD_NOTES = @json(\App\Support\WorkerContext::canAddNotes());
    const MAY_NOTE = CAN_EDIT || CAN_ADD_NOTES;
    /* A control a viewer may not use is drawn where everyone else has it,
       greyed and inert, rather than left out: a board whose buttons come and
       go per person is one nobody can be told how to use, and a missing button
       reads as a bug. Twin of $mayEdit/$editTitle in activity-card.blade.php.
       An activity note is an edit (appendNote goes through the write gate) —
       only the DAY's note is what "notes-only" buys. */
    const WHY_NO_EDIT = 'Only someone who can edit the plan may do this';
    const WHY_NO_NOTE = 'You are not allowed to write notes on this schedule';
    const LOCK_EDIT = CAN_EDIT ? '' : ' disabled';
    const LOCK_EDIT_CLS = CAN_EDIT ? '' : ' is-locked';
    const LOCK_NOTE = MAY_NOTE ? '' : ' disabled';
    const LOCK_NOTE_CLS = MAY_NOTE ? '' : ' is-locked';
    const editTitle = (plain) => (CAN_EDIT ? plain : WHY_NO_EDIT);
    const noteTitle = (plain) => (MAY_NOTE ? plain : WHY_NO_NOTE);
    const LOT_NAMES = @json($schedule->lots->mapWithKeys(fn ($l) => [$l->id => $l->lotName]));
    const LOT_VARIETIES = @json($schedule->lots->mapWithKeys(fn ($l) => [$l->id => $l->variety]));
    // How each lot counts: 'DAT' sown-then-transplanted (DAS, then a fresh
    // DAT count from the transplant date), 'DAS' direct seeded and never
    // flipping, 'DAP' planted. The lot answers this, not the crop — the same
    // rice is a different calendar depending on how the field was established.
    const LOT_DAY_TYPE = @json($schedule->lots->mapWithKeys(fn ($l) => [$l->id => ($l->dayType ?: 'DAT')]));
    const lotDayType = (lotId) => {
        const v = String(LOT_DAY_TYPE[lotId] || 'DAT').toUpperCase();
        return (v === 'DAP' || v === 'DAS') ? v : 'DAT';
    };
    // What a lot's count is called while it is still before any transplant.
    const lotBaseCounter = (lotId) => (lotDayType(lotId) === 'DAP' ? 'DAP' : 'DAS');
    const WORKER_NAMES = @json($schedule->workers->mapWithKeys(fn ($w) => [$w->id => $w->workerName]));
    // The half-day rate each worker is normally paid; a whole day is two of
    // them, and a custom amount on the activity overrides both.
    const WORKER_RATES = @json($schedule->workers->mapWithKeys(fn ($w) => [$w->id => (float) ($w->costPerHalfDay ?? 0)]));
    // Days off, from Workers → Rules: a weekday they never work, and one-off
    // dates. The board needs them to say who cannot be put on a given day.
    @php $offMap = \App\Models\AsScheduleWorker::offMapFor((int) $schedule->id); @endphp
    @php
        $workerOff = $schedule->workers->mapWithKeys(fn ($w) => [$w->id => [
            'days' => array_values($offMap[$w->id]['days'] ?? []),
            'dates' => array_values(array_filter($offMap[$w->id]['dates'] ?? [])),
        ]])->all();
    @endphp
    const WORKER_OFF = @json($workerOff);
    /** Is this worker marked off on that date? dateStr is 'YYYY-MM-DD'. */
    function workerOffOn(workerId, dateStr) {
        const off = WORKER_OFF[workerId];
        if (!off || !dateStr) return false;
        if ((off.dates || []).includes(dateStr)) return true;
        const d = parseLocalDate(dateStr);
        return !!d && (off.days || []).includes(d.getDay());
    }
    // What is planted where, and what each crop does at each age. The stage
    // tables come from the server so one catalogue serves the board, the
    // sheet, the reports and anything added later.
    const LOT_CROP = @json($schedule->lots->mapWithKeys(fn ($l) => [$l->id => $l->crop]));
    @php
        // Built here rather than inside @json: the directive takes one
        // expression on one line, and a nested map over the catalogue is
        // neither.
        $shapeStages = fn ($rows) => collect($rows)->map(fn ($st) => [
            'from' => $st[0], 'label' => $st[1], 'what' => $st[2], 'needs' => $st[3],
        ])->all();
        $cropTables = collect(\App\Support\CropStages::CROPS)->map(fn ($c) => [
            'label' => $c['label'],
            'icon' => $c['icon'],
            'counter' => $c['counter'],
            'stages' => $shapeStages($c['stages']),
            // Rice grown from seed in the field keeps its own calendar; every
            // other crop has one, and falls back to it.
            'stagesDirect' => isset($c['stagesDirect']) ? $shapeStages($c['stagesDirect']) : null,
        ])->all();
    @endphp
    const CROP_STAGES = @json($cropTables);
    const LOT_MANUAL_DAY_ZERO = @json($schedule->lots->mapWithKeys(fn ($l) => [$l->id => $l->dayZeroDate ? $l->dayZeroDate->format('Y-m-d') : null]));
    const LOT_MANUAL_TRANSPLANT = @json($schedule->lots->mapWithKeys(fn ($l) => [$l->id => $l->transplantDate ? $l->transplantDate->format('Y-m-d') : null]));

    const U = {
        store:            ()  => `{{ route('sm.activities.store') }}?scheduleId=${SCHEDULE_ID}`,
        show:             (id) => `{{ route('sm.activities.show') }}?scheduleId=${SCHEDULE_ID}&id=${id}`,
        update:           (id) => `{{ route('sm.activities.update') }}?scheduleId=${SCHEDULE_ID}&id=${id}`,
        destroy:          (id) => `{{ route('sm.activities.destroy') }}?scheduleId=${SCHEDULE_ID}&id=${id}`,
        imageUpload:      ()  => `{{ route('sm.activities.image-upload') }}?scheduleId=${SCHEDULE_ID}`,
        lotStore:         ()  => `{{ route('sm.lots.store') }}?scheduleId=${SCHEDULE_ID}`,
        workerStore:      ()  => `{{ route('sm.workers.store') }}?scheduleId=${SCHEDULE_ID}`,
        toggleHidden:     (id) => `{{ route('sm.activities.toggle-hidden') }}?scheduleId=${SCHEDULE_ID}&id=${id}`,
        toggleDone:       (id) => `{{ route('sm.activities.toggle-done') }}?scheduleId=${SCHEDULE_ID}&id=${id}`,
        appendNote:       (id) => `{{ route('sm.activities.append-note') }}?scheduleId=${SCHEDULE_ID}&id=${id}`,
        duplicate:        (id) => `{{ route('sm.activities.duplicate') }}?scheduleId=${SCHEDULE_ID}&id=${id}`,
        reorder:          ()  => `{{ route('sm.activities.reorder') }}?scheduleId=${SCHEDULE_ID}`,
        restore:          (id) => `{{ route('sm.activities.restore') }}?scheduleId=${SCHEDULE_ID}&id=${id}`,
        toDraft:          (id) => `{{ route('sm.activities.to-draft') }}?scheduleId=${SCHEDULE_ID}&id=${id}`,
        fromDraft:        (id) => `{{ route('sm.activities.from-draft') }}?scheduleId=${SCHEDULE_ID}&id=${id}`,
        drafts:           ()  => `{{ route('sm.activities.drafts') }}?scheduleId=${SCHEDULE_ID}`,
        labor:            ()  => `{{ route('sm.activities.labor') }}?scheduleId=${SCHEDULE_ID}`,
        dateNoteSave:     ()  => `{{ route('sm.activities.date-note.save') }}?scheduleId=${SCHEDULE_ID}`,
        dateNoteDelete:   ()  => `{{ route('sm.activities.date-note.delete') }}?scheduleId=${SCHEDULE_ID}`,
        inlineNoteSave:   ()  => `{{ route('sm.activities.inline-note.save') }}?scheduleId=${SCHEDULE_ID}`,
        inlineNoteDelete: (id) => `{{ route('sm.activities.inline-note.delete') }}?scheduleId=${SCHEDULE_ID}&id=${id}`,
        noteImageUpload:  ()  => `{{ route('sm.notes.image-upload') }}?scheduleId=${SCHEDULE_ID}`,
        noteVideoUpload:  ()  => `{{ route('sm.notes.video-upload') }}?scheduleId=${SCHEDULE_ID}`,
        weather:          ()  => `{{ route('sm.weather') }}?scheduleId=${SCHEDULE_ID}`,
        attendance:       (d) => `{{ route('sm.attendance') }}?scheduleId=${SCHEDULE_ID}&date=${encodeURIComponent(d)}`,
        attendanceMark:   ()  => `{{ route('sm.attendance.mark') }}?scheduleId=${SCHEDULE_ID}`,
        reminderToggle:   ()  => `{{ route('sm.activities.reminder-toggle') }}?scheduleId=${SCHEDULE_ID}`,
        taggables:        ()  => `{{ route('sm.activities.taggables') }}?scheduleId=${SCHEDULE_ID}`,
        tag:              ()  => `{{ route('sm.activities.tag') }}?scheduleId=${SCHEDULE_ID}`,
        untag:            ()  => `{{ route('sm.activities.untag') }}?scheduleId=${SCHEDULE_ID}`,
        dayIncomeList:    ()  => `{{ route('sm.activities.day-income.list') }}?id=${SCHEDULE_ID}`,
        dayIncomeSave:    ()  => `{{ route('sm.activities.day-income.save') }}?scheduleId=${SCHEDULE_ID}`,
        dayIncomeDelete:  (id) => `{{ route('sm.activities.day-income.delete') }}?scheduleId=${SCHEDULE_ID}&id=${id}`,
        dayExpenseList:   (d) => `{{ route('sm.activities.day-expense.list') }}?id=${SCHEDULE_ID}&expenseDate=${encodeURIComponent(d)}`,
        dayExpenseSave:   ()  => `{{ route('sm.activities.day-expense.save') }}?scheduleId=${SCHEDULE_ID}`,
        dayExpenseDelete: ()  => `{{ route('sm.activities.day-expense.delete') }}?scheduleId=${SCHEDULE_ID}`,
        markerSave:       ()  => `{{ route('sm.markers.save') }}?scheduleId=${SCHEDULE_ID}`,
        markerDelete:     (id) => `{{ route('sm.markers.destroy') }}?scheduleId=${SCHEDULE_ID}&id=${id}`,
        versionStore:     ()  => `{{ route('sm.activity-versions.store') }}?scheduleId=${SCHEDULE_ID}`,
        versionUpdate:    (id) => `{{ route('sm.activity-versions.update') }}?scheduleId=${SCHEDULE_ID}&id=${id}`,
        versionDelete:    (id) => `{{ route('sm.activity-versions.destroy') }}?scheduleId=${SCHEDULE_ID}&id=${id}`,
        versionSetActive: (id) => `{{ route('sm.activity-versions.set-active') }}?scheduleId=${SCHEDULE_ID}&id=${id}`,
    };

    const MONTH_SHORT = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    const DAY_SHORT = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
    const MONTH_LONG = ['January','February','March','April','May','June','July','August','September','October','November','December'];
    const DAY_LONG = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];

    const $id = (i) => document.getElementById(i);
    const $qs = (sel, root) => (root || document).querySelector(sel);
    const $qsa = (sel, root) => Array.from((root || document).querySelectorAll(sel));
    const money = (n) => '₱' + Number(n || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const esc = window.escapeHtml;

    const dayType = () => ($qs('.day-type-label')?.textContent || DAY_TYPE_DEFAULT).trim() || DAY_TYPE_DEFAULT;

    function parseLocalDate(s) {
        if (!s) return null;
        const [y, m, d] = String(s).slice(0, 10).split('-').map((n) => parseInt(n, 10));
        if (!y || !m || !d) return null;
        return new Date(y, m - 1, d);
    }
    function isoFromDate(d) {
        return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
    }
    function isoAddDays(iso, days) {
        const d = parseLocalDate(iso);
        if (!d) return '';
        d.setDate(d.getDate() + days);
        return isoFromDate(d);
    }
    function isoDaysBetween(a, b) {
        const da = parseLocalDate(a);
        const db = parseLocalDate(b);
        if (!da || !db) return 0;
        return Math.round((db - da) / 86400000);
    }
    function prettyDate(iso) {   // "Mar 5, 2026"
        const d = parseLocalDate(iso);
        return d ? `${MONTH_SHORT[d.getMonth()]} ${d.getDate()}, ${d.getFullYear()}` : iso;
    }
    function prettyDateFull(iso) {   // "Thu, Mar 5, 2026"
        const d = parseLocalDate(iso);
        return d ? `${DAY_SHORT[d.getDay()]}, ${MONTH_SHORT[d.getMonth()]} ${d.getDate()}, ${d.getFullYear()}` : iso;
    }
    function prettyDateLong(iso) {   // "Thursday, March 5, 2026"
        const d = parseLocalDate(iso);
        return d ? `${DAY_LONG[d.getDay()]}, ${MONTH_LONG[d.getMonth()]} ${d.getDate()}, ${d.getFullYear()}` : iso;
    }
    function timeRequiredLabel(v) {
        if (v === 'whole') return 'Whole day';
        if (v === 'n/a') return 'N/A';
        return 'Half day';
    }
    function timeRequiredShortLabel(v) {
        if (v === 'whole') return 'Whole';
        if (v === 'n/a') return 'N/A';
        return 'Half';
    }
    function trimQty(q) {
        const n = Number(q);
        return isFinite(n) ? String(n) : String(q || '1');
    }
    function boolFlag(v) {
        return (v === true || v === 1 || v === '1') ? 1 : 0;
    }
    async function copyToClipboard(txt) {
        if (navigator.clipboard && navigator.clipboard.writeText) {
            return navigator.clipboard.writeText(txt);
        }
        const ta = document.createElement('textarea');
        ta.value = txt;
        ta.style.position = 'fixed';
        ta.style.opacity = '0';
        document.body.appendChild(ta);
        ta.select();
        document.execCommand('copy');
        ta.remove();
    }

    // ---- Shared inline SVG snippets (identical to the blade partials) ----
    const SVG = {
        reminder: '<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l1.5 1.5L15 12"/></svg>',
        moon: '<svg class="w-5 h-5 text-gray-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>',
        plus: '<svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>',
        note: '<svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>',
        notePlus: '<svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.5 20H7a2 2 0 01-2-2V5a2 2 0 012-2h6l4 4v3M9 8h3M9 12h3"/><path stroke-linecap="round" stroke-linejoin="round" d="M17 15v5m2.5-2.5h-5"/></svg>',
        coin: '<svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="8.5"/><path stroke-linecap="round" stroke-linejoin="round" d="M12 7v10M14.4 9.4a2.3 2.3 0 00-2.4-1.3c-1.3.1-2.3.8-2.3 1.9s1 1.7 2.5 1.9 2.6.8 2.6 2-1.1 1.9-2.5 1.9a2.4 2.4 0 01-2.4-1.3"/></svg>',
        bookmark: '<svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/></svg>',
        bookmarkSolid: '<svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="currentColor"><path d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/></svg>',
        calendarEdit: '<svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>',
        trash: '<svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>',
        edit: '<svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>',
        eye: '<svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>',
        tag: '<svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M3 11V4a1 1 0 011-1h7l9 9-8 8-9-9z"/></svg>',
        people: '<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-1a4 4 0 00-4-4h-1M9 11a4 4 0 100-8 4 4 0 000 8zm8 0a3 3 0 100-6M2 20v-1a5 5 0 015-5h4a5 5 0 015 5v1H2z"/></svg>',
        wallet: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8a2 2 0 012-2h12a2 2 0 012 2M3 8v9a2 2 0 002 2h13a2 2 0 002-2v-2M3 8h16a2 2 0 012 2v1h-4a2 2 0 100 4h4"/></svg>',
        duplicate: '<svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>',
        archive: '<svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/></svg>',
        kebab: '<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="5" r="1.6"/><circle cx="12" cy="12" r="1.6"/><circle cx="12" cy="19" r="1.6"/></svg>',
        star: '<svg class="w-3 h-3" viewBox="0 0 20 20" fill="currentColor"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>',
        clock: '<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
        water: '<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3s6 6.686 6 11a6 6 0 11-12 0c0-4.314 6-11 6-11z"/></svg>',
        service: '<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5a4 4 0 105.03 5.03l4.35 4.35a2 2 0 11-2.83 2.83l-4.35-4.35A4 4 0 0111 5zM5 19l4-4"/></svg>',
        task: '<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>',
        spinner: '<svg class="w-4 h-4 btn-spin" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2.5" opacity=".25"/><path d="M21 12a9 9 0 00-9-9" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"/></svg>',
        dayNumber: '<svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
        share: '<svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.68 13.34a3 3 0 100-2.68m0 2.68l6.64 3.86m-6.64-6.54l6.64-3.86m0 0a3 3 0 105.32-2.68 3 3 0 00-5.32 2.68zm0 13.08a3 3 0 105.32 2.68 3 3 0 00-5.32-2.68z"/></svg>',
    };

    /* ================================================================
     * 2. DAY-0 / DAS MACHINERY
     * Effective anchor per lot = manual lot dayZeroDate overridden by the
     * EARLIEST isDayZero activity covering that lot.
     * ================================================================ */

    let LOT_DAY_ZERO_DATES = Object.assign({}, LOT_MANUAL_DAY_ZERO);
    let LOT_TRANSPLANT_DATES = Object.assign({}, LOT_MANUAL_TRANSPLANT);
    let LOT_DAY_ZERO_SOURCE = {};
    let LOT_TRANSPLANT_SOURCE = {};

    function computeDasLabel(lotId, targetDate) {
        if (!targetDate) return '';
        const b = parseLocalDate(targetDate);
        if (!b) return '';
        const mode = lotDayType(lotId);   // 'DAT' | 'DAS' | 'DAP'
        // Only a sown-then-transplanted lot flips to a fresh DAT count. A
        // direct-seeded lot keeps one count even if a transplant activity
        // exists on the board, and a planted lot never had one to flip.
        if (mode === 'DAT') {
            const tp = LOT_TRANSPLANT_DATES[lotId];
            if (tp) {
                const t = parseLocalDate(tp);
                if (t && b >= t) {
                    const datDelta = Math.round((b - t) / 86400000);
                    if (datDelta === 0) {
                        // At the pivot, show the DAS it converts from → DAT0.
                        const z = LOT_DAY_ZERO_DATES[lotId] ? parseLocalDate(LOT_DAY_ZERO_DATES[lotId]) : null;
                        if (z) { const dd = Math.round((b - z) / 86400000); return ' · DAS' + (dd > 0 ? '+' : '') + dd + ' → DAT0'; }
                    }
                    return ' · DAT' + (datDelta > 0 ? '+' : '') + datDelta;
                }
            }
        }
        // Base phase (DAS before transplant, or DAP throughout).
        const anchor = LOT_DAY_ZERO_DATES[lotId];
        if (!anchor) return '';
        const a = parseLocalDate(anchor);
        if (!a) return '';
        const delta = Math.round((b - a) / 86400000);
        return ' · ' + lotBaseCounter(lotId) + (delta > 0 ? '+' : '') + delta;
    }

    /* ---- Growth stages ----------------------------------------------------
     * The board has always known how old a lot is; it never said what that
     * age means for the plant. Day 21 of rice is active tillering and wants
     * its second nitrogen — growers know it, and the app made them hold it in
     * their heads while reading a screen that only counted days.
     *
     * Rice is read from transplanting where a lot transplants, and from
     * sowing where it does not; everything else counts from planting. That is
     * the same rule the counters on the cards already follow. */
    function lotDayNumberOn(lotId, dateStr) {
        const b = parseLocalDate(dateStr);
        if (!b) return null;
        // The lot says how it was established; the crop only says what the
        // calendars are. A lot that was direct seeded is read against the
        // direct-seeded table for the whole season even though the same crop
        // transplants elsewhere on the farm.
        if (lotDayType(lotId) === 'DAT') {
            const tp = LOT_TRANSPLANT_DATES[lotId];
            const t = tp ? parseLocalDate(tp) : null;
            if (t && b >= t) return { day: Math.round((b - t) / 86400000), counter: 'DAT' };
        }
        const anchor = LOT_DAY_ZERO_DATES[lotId];
        const a = anchor ? parseLocalDate(anchor) : null;
        if (!a) return null;
        const delta = Math.round((b - a) / 86400000);
        if (delta < 0) return null;   // before day zero there is no plant yet
        return { day: delta, counter: lotBaseCounter(lotId) };
    }

    /**
     * @param {string} counter which count `day` is in. Rice in DAS was direct
     *   seeded — no transplant, and every stage lands on a different day of
     *   its own count than it does for a transplanted crop.
     */
    function stageOf(crop, day, counter) {
        const table = CROP_STAGES[crop];
        if (!table || day === null || day === undefined) return null;
        const stages = (counter && String(counter).toUpperCase() !== 'DAT' && table.stagesDirect)
            ? table.stagesDirect
            : table.stages;
        let at = -1;
        stages.forEach((st, i) => { if (day >= st.from) at = i; });
        if (at < 0) return null;
        const cur = stages[at];
        const next = stages[at + 1] || null;
        const length = next ? next.from - cur.from : null;
        const inStage = day - cur.from;
        return {
            index: at, label: cur.label, what: cur.what, needs: cur.needs,
            dayInStage: inStage, lengthDays: length,
            progress: length ? Math.min(1, Math.max(0, inStage / length)) : null,
            next: next ? { label: next.label, inDays: next.from - day } : null,
            counter: table.counter, icon: table.icon, cropLabel: table.label,
            stages,
        };
    }

    /** Which lots have activities on this day, or all of them if none do. */
    function lotsOnDay(group) {
        const ids = new Set();
        // The lot chips on each card are where a card says which ground it
        // covers — there is no separate attribute, and adding one would give
        // the two renderers something else to keep in step.
        $qsa('.activity-card .lot-tag[data-lot-id]', group).forEach((tag) => {
            const n = parseInt(tag.getAttribute('data-lot-id'), 10);
            if (!Number.isNaN(n)) ids.add(n);
        });
        if (!ids.size) Object.keys(LOT_CROP).forEach((id) => ids.add(parseInt(id, 10)));
        return [...ids];
    }

    /** One pill per day: what the crop is doing, or nothing if we cannot say. */
    function paintDayStage(group) {
        const pill = group.querySelector('.date-header-stage');
        if (!pill) return;
        const dateKey = (group.getAttribute('data-date') || '').trim();
        const rows = dayStageRows(dateKey, group);
        if (!rows.length) { pill.hidden = true; pill.textContent = ''; return; }

        // One lot: name the stage. Several: say how many, because a header is
        // not the place to list four different answers. A bare icon was tried
        // and read as decoration — the word is what makes it information.
        const first = rows[0];
        const label = rows.length === 1
            ? first.stage.label
            : rows.length + ' lots';
        const detail = rows.length === 1
            ? rows[0].stage.label
            : rows.map((r) => r.lotName + ': ' + r.stage.label).join(' · ');
        pill.hidden = false;
        pill.innerHTML = `<span class="dhs-emoji">${first.stage.icon || '🌱'}</span><span>${esc(label)}</span>`;
        pill.title = detail;
        pill.setAttribute('aria-label', 'Growth stage — ' + detail);
        pill.setAttribute('data-date', dateKey);
    }

    /** The stage of every lot with a crop on a given day. */
    function dayStageRows(dateKey, group) {
        const ids = group ? lotsOnDay(group) : Object.keys(LOT_CROP).map(Number);
        return ids.map((id) => {
            const crop = LOT_CROP[id];
            if (!crop) return null;
            const age = lotDayNumberOn(id, dateKey);
            if (!age) return null;
            const stage = stageOf(crop, age.day, age.counter);
            if (!stage) return null;
            return {
                lotId: id, lotName: LOT_NAMES[id] || ('Lot #' + id),
                day: age.day, counter: age.counter, crop, stage,
            };
        }).filter(Boolean);
    }

    function openDayStage(dateKey, group) {
        const rows = dayStageRows(dateKey, group);
        const box = $id('growthStageList');
        if (!box) return;
        const scope = group ? '' : ' · every lot';
        $id('growthStageDate').textContent = prettyDateFull(dateKey) + scope;

        // Lots with no crop or no day zero are named rather than dropped —
        // "nothing here" is confusing when you know you own three lots.
        const quiet = group ? [] : Object.keys(LOT_CROP).map(Number)
            .filter((id) => !rows.some((r) => r.lotId === id))
            .map((id) => ({ id, name: LOT_NAMES[id] || ('Lot #' + id), crop: LOT_CROP[id] }));

        box.innerHTML = rows.length ? rows.map((r) => {
            const st = r.stage;
            const steps = (st.stages || CROP_STAGES[r.crop].stages).map((step, i) => `
                <div class="gs-step${i === st.index ? ' is-now' : (i < st.index ? ' is-past' : '')}">
                    <span class="gs-dot"></span>
                    <span class="grow">${esc(step.label)}</span>
                    <span class="gs-when">${r.counter} ${step.from}+</span>
                </div>`).join('');
            return `<div class="gs-lot" data-lot="${r.lotId}">
                <div class="gs-head" title="Tap to fold or open this lot">
                    <svg class="gs-chev" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                    <span class="gs-emoji">${st.icon || '🌱'}</span>
                    <span class="grow min-w-0">
                        <span class="gs-lotname">${esc(r.lotName)}</span>
                        <span class="gs-day block">${esc(st.cropLabel)} · ${r.counter} ${r.day}<span class="gs-fold-stage"> · ${esc(st.label)}</span></span>
                    </span>
                </div>
                <div class="gs-fold"><div class="gs-fold-inner">
                <div class="gs-body">
                    <div class="gs-now">${esc(st.label)}</div>
                    <div class="gs-what">${esc(st.what)}</div>
                    <div class="gs-needs"><b>What it needs now</b>${esc(st.needs)}</div>
                    ${st.progress !== null ? `<div class="gs-bar"><span style="width:${Math.round(st.progress * 100)}%"></span></div>` : ''}
                    <div class="gs-next">${st.next
                        ? `Day ${st.dayInStage + 1} of this stage · ${esc(st.next.label)} in about ${st.next.inDays} day${st.next.inDays === 1 ? '' : 's'}`
                        : 'The last stage — harvest window.'}</div>
                    <div class="gs-steps">${steps}</div>
                </div>
                </div></div>
            </div>`;
        }).join('') + (quiet.length ? `<div class="gs-quiet"><b>Not readable yet</b>${quiet.map((q) => `
            <span>${esc(q.name)} — ${q.crop ? 'no day zero yet' : 'no crop set'}</span>`).join('')}</div>` : '')
        + '<p class="gs-foot">These stages are counted from the calendar, not from the plant. A crop runs late or early with the weather it gets — a cold spell, a drought, flooding, a typhoon, pest damage or a hungry field all shift it, and so do the variety and how it was established. Walk the field and believe what you see there over what this page says.</p>'
        : `<p class="gs-none">No lot here has a crop set, or the count has not started yet.
            Set the crop on a lot in the Lots module and give it a day zero.</p>`;

        gsApplyFolds();
        $id('gsFoldAll')?.classList.toggle('hidden', !rows.length);
        openSheet('growthStageSheet');
    }

    /* The lots fold here exactly as they do in the Growth Stages module —
       same accordion, same remembered set per schedule, so a lot folded in
       one place arrives folded in the other. */
    const GS_FOLD_KEY = 'growthFolded:' + SCHEDULE_ID;
    const gsFolded = () => {
        try { return new Set(JSON.parse(localStorage.getItem(GS_FOLD_KEY) || '[]')); } catch (_) { return new Set(); }
    };
    const gsSaveFolded = (set) => { try { localStorage.setItem(GS_FOLD_KEY, JSON.stringify([...set])); } catch (_) { /* private mode */ } };
    const gsLots = () => Array.from(document.querySelectorAll('#growthStageList .gs-lot[data-lot]'));
    function gsSayBtn() {
        const b = $id('gsFoldAll');
        if (b) b.textContent = gsLots().some((c) => !c.classList.contains('is-folded')) ? 'Collapse all' : 'Expand all';
    }
    function gsApplyFolds() {
        const box = $id('growthStageList');
        if (!box) return;
        const folded = gsFolded();
        // Restoring is not a change — no wave of closing animations.
        box.classList.add('no-fold-anim');
        gsLots().forEach((c) => c.classList.toggle('is-folded', folded.has(c.getAttribute('data-lot'))));
        void box.offsetWidth;
        requestAnimationFrame(() => box.classList.remove('no-fold-anim'));
        gsSayBtn();
    }
    document.addEventListener('click', (e) => {
        const head = e.target.closest('#growthStageList .gs-head');
        if (head) {
            const lot = head.closest('.gs-lot[data-lot]');
            if (!lot) return;
            const set = gsFolded();
            const id = lot.getAttribute('data-lot');
            if (lot.classList.toggle('is-folded')) set.add(id);
            else set.delete(id);
            gsSaveFolded(set);
            gsSayBtn();
            return;
        }
        if (e.target.closest('#gsFoldAll')) {
            const fold = gsLots().some((c) => !c.classList.contains('is-folded'));
            const set = gsFolded();
            gsLots().forEach((c) => {
                c.classList.toggle('is-folded', fold);
                const id = c.getAttribute('data-lot');
                if (fold) set.add(id);
                else set.delete(id);
            });
            gsSaveFolded(set);
            gsSayBtn();
        }
    });

    // Tools → Growth stage: every lot, read today.
    //
    // It used to hand over today's date-group, which scopes the answer to the
    // lots that happen to have an activity on that day — a farm with three
    // lots and one job today saw one lot and thought the other two had gone
    // missing. Asking the tool is asking about the farm, so it answers for the
    // whole farm, the way the Growth Stages module does. (A day header's own
    // pill still answers for that day, which is the question it was asked.)
    $id('growthStageBtn')?.addEventListener('click', () => {
        const today = new Date();
        const key = today.getFullYear() + '-'
            + String(today.getMonth() + 1).padStart(2, '0') + '-'
            + String(today.getDate()).padStart(2, '0');
        openDayStage(key, null);
    });

    document.addEventListener('click', (e) => {
        const pill = e.target.closest('.date-header-stage');
        if (!pill) return;
        e.stopPropagation();
        const group = pill.closest('.date-group');
        openDayStage((group?.getAttribute('data-date') || '').trim(), group);
    });

    // Painted alongside the day's money, from the same hook: both read the
    // cards, and a board that repaints one without the other tells two
    // different stories about the same day.

    function refreshActivityCardDasLabels() {
        $qsa('#activitiesList .activity-card[data-id]').forEach((card) => {
            const targetDate = (card.getAttribute('data-target-date') || '').trim();
            // Header chips stay the lot NAME only.
            const headTags = $qsa('.activity-card-lothead .lot-tag[data-lot-id]', card);
            headTags.forEach((tag) => { tag.textContent = tag.getAttribute('data-lot-name') || ''; });
            // Variety + DAS live in the meta row below the title — rebuild it with
            // the DAS recomputed for this card's current date.
            const metaBox = card.querySelector('.activity-card-lotmeta');
            if (!metaBox) return;
            const multi = headTags.length > 1;
            let html = '';
            headTags.forEach((tag) => {
                const lotId = parseInt(tag.getAttribute('data-lot-id'), 10);
                const name = tag.getAttribute('data-lot-name') || '';
                const variety = tag.getAttribute('data-lot-variety') || '';
                const das = computeDasLabel(lotId, targetDate).replace(/^\s*·\s*/, '');
                const parts = [];
                if (variety) parts.push(variety);
                if (das) parts.push(das);
                if (parts.length) html += `<span class="item-tag lot-meta-tag">${esc((multi ? name + ' · ' : '') + parts.join(' · '))}</span>`;
            });
            metaBox.innerHTML = html;
        });
    }

    function recomputeLotDayZero() {
        const map = Object.assign({}, LOT_MANUAL_DAY_ZERO || {});
        const source = {};
        Object.keys(map).forEach((lotId) => {
            if (map[lotId]) source[lotId] = 'manual';
        });
        // Transplant (DAT 0) anchors: manual lot dates, then earliest isTransplant.
        const tmap = Object.assign({}, LOT_MANUAL_TRANSPLANT || {});
        const tsource = {};
        Object.keys(tmap).forEach((lotId) => { if (tmap[lotId]) tsource[lotId] = 'manual'; });
        $qsa('#activitiesList .activity-card').forEach((card) => {
            const targetDate = (card.getAttribute('data-target-date') || '').trim();
            if (!targetDate) return;
            const isDz = card.getAttribute('data-is-day-zero') === '1';
            const isTp = card.getAttribute('data-is-transplant') === '1';
            if (!isDz && !isTp) return;
            const activityId = parseInt(card.getAttribute('data-id'), 10);
            $qsa('.activity-card-lots .item-tag[data-lot-id]', card).forEach((tag) => {
                const lotId = parseInt(tag.getAttribute('data-lot-id'), 10);
                if (!lotId) return;
                if (isDz && (!map[lotId] || targetDate < map[lotId])) { map[lotId] = targetDate; source[lotId] = activityId; }
                if (isTp && (!tmap[lotId] || targetDate < tmap[lotId])) { tmap[lotId] = targetDate; tsource[lotId] = activityId; }
            });
        });
        LOT_DAY_ZERO_DATES = map;
        LOT_DAY_ZERO_SOURCE = source;
        LOT_TRANSPLANT_DATES = tmap;
        LOT_TRANSPLANT_SOURCE = tsource;
        refreshActivityCardDasLabels();
    }

    /* ================================================================
     * 3. UNDO / REDO STACKS — 10-step LIFO each, Ctrl+Z / Ctrl+Shift+Z
     * ================================================================ */

    const UNDO_STACK = [];
    const REDO_STACK = [];
    const UNDO_MAX = 10;

    /**
     * @param {string}    label
     * @param {Function}  undoFn  reverts the action
     * @param {Function} [redoFn] re-applies it. Omit for actions that only move
     *                            cards around: the board state as it stands right
     *                            now (i.e. after the action) is captured and
     *                            replayed instead. Actions that create or destroy
     *                            activities must pass their own — a board replay
     *                            cannot resurrect a row that no longer exists.
     */
    function pushUndo(label, undoFn, redoFn) {
        if (!redoFn) {
            const after = captureBoardSnapshot();
            redoFn = () => restoreBoardSnapshot(after);
        }
        UNDO_STACK.push({ label, undoFn, redoFn });
        if (UNDO_STACK.length > UNDO_MAX) UNDO_STACK.shift();
        REDO_STACK.length = 0;   // a fresh action abandons the redo branch
        refreshHistoryBtns();
    }

    function refreshHistoryBtns() {
        [['activityUndoBtn', 'activityUndoCount', UNDO_STACK, 'undo', 'Ctrl+Z'],
         ['activityRedoBtn', 'activityRedoCount', REDO_STACK, 'redo', 'Ctrl+Shift+Z']]
        .forEach(([btnId, countId, stack, verb, keys]) => {
            const btn = $id(btnId);
            const count = $id(countId);
            if (!btn || !count) return;
            const n = stack.length;
            btn.disabled = n === 0;
            if (n === 0) {
                btn.title = 'Nothing to ' + verb;
                count.classList.add('hidden');
                count.classList.remove('inline-flex');
            } else {
                btn.title = verb[0].toUpperCase() + verb.slice(1) + ': ' + stack[n - 1].label
                    + ' (' + n + ' available, ' + keys + ')';
                count.textContent = n;
                count.classList.remove('hidden');
                count.classList.add('inline-flex');
            }
        });
    }

    /** Shared body for undo and redo — they differ only in direction. */
    async function travelHistory(from, to, key, verb, doneWord) {
        const action = from.pop();
        refreshHistoryBtns();
        if (!action) {
            toast('Nothing to ' + verb, 'info');
            return;
        }
        try {
            await action[key]();
            to.push(action);
            if (to.length > UNDO_MAX) to.shift();
            toast(doneWord + ': ' + action.label);
        } catch (err) {
            // Put it back so the user can retry rather than silently losing a step.
            from.push(action);
            toast(verb[0].toUpperCase() + verb.slice(1) + ' failed: '
                + (err && err.message ? err.message : 'unknown error'), 'error');
        }
        refreshHistoryBtns();
    }

    const performUndo = () => travelHistory(UNDO_STACK, REDO_STACK, 'undoFn', 'undo', 'Undone');
    const performRedo = () => travelHistory(REDO_STACK, UNDO_STACK, 'redoFn', 'redo', 'Redone');

    $id('activityUndoBtn')?.addEventListener('click', () => {
        if (!$id('activityUndoBtn').disabled) performUndo();
    });
    $id('activityRedoBtn')?.addEventListener('click', () => {
        if (!$id('activityRedoBtn').disabled) performRedo();
    });

    document.addEventListener('keydown', (e) => {
        if (!(e.ctrlKey || e.metaKey)) return;
        const k = (e.key || '').toLowerCase();
        const isUndo = k === 'z' && !e.shiftKey;
        const isRedo = (k === 'z' && e.shiftKey) || k === 'y';
        if (!isUndo && !isRedo) return;
        const tag = (e.target.tagName || '').toLowerCase();
        if (tag === 'input' || tag === 'textarea' || tag === 'select' || e.target.isContentEditable) return;
        e.preventDefault();
        (isUndo ? performUndo : performRedo)();
    });

    /* ================================================================
     * 4. CARD RENDERER + TIMELINE REBUILD
     * renderActivityCard(a) emits markup IDENTICAL to
     * sm/partials/activity-card.blade.php; reorderAndRenumberActivities()
     * rebuilds the whole timeline (groups, colors, rest days, notes,
     * markers, all-hidden substitutes) from the DOM cards.
     * ================================================================ */

    function renderActivityCard(a) {
        const priority = a.priority || 'medium';
        const priorityCap = priority.charAt(0).toUpperCase() + priority.slice(1);
        const targetDateStr = (a.targetDate || '').slice(0, 10);
        const targetEndDateStr = (a.targetEndDate || '').slice(0, 10);
        const startObj = parseLocalDate(targetDateStr);
        const endObj = parseLocalDate(targetEndDateStr);
        const isRange = !!(startObj && endObj && endObj > startObj);
        const rangeDays = isRange ? Math.round((endObj - startObj) / 86400000) + 1 : 0;
        const typeLabel = a.activityType && ACTIVITY_TYPE_LABELS[a.activityType] ? ACTIVITY_TYPE_LABELS[a.activityType] : '';
        const dt = dayType();
        const isDayZeroFlag = boolFlag(a.isDayZero);
        const isTransplantFlag = boolFlag(a.isTransplant);
        const isHiddenFlag = boolFlag(a.isHidden);
        const seqOrder = (a.sequenceOrder !== undefined && a.sequenceOrder !== null) ? parseInt(a.sequenceOrder, 10) || 0 : 0;

        const lotIds = (a.lotIds || (a.lots || []).map((l) => l.id ?? l)).map(Number);
        // Left accent = the (first) lot's auto colour, matching the server render.
        const lotAccentStyle = lotIds.length ? ` style="--lot-accent: hsl(${(lotIds[0] * 137) % 360}, 55%, 40%)"` : '';
        const workerIds = (a.workerIds || (a.workers || []).map((w) => w.id ?? w)).map(Number);
        const lotSig = lotIds.slice().sort((x, y) => x - y).join(',');

        // data-search: lowercased title + type + lots(name variety) + workers + items
        const searchBits = [String(a.activityTitle || '').toLowerCase(), typeLabel.toLowerCase()];
        lotIds.forEach((id) => searchBits.push(((LOT_NAMES[id] || '') + ' ' + (LOT_VARIETIES[id] || '')).toLowerCase()));
        workerIds.forEach((id) => searchBits.push((WORKER_NAMES[id] || '').toLowerCase()));
        (a.items || []).forEach((it) => {
            searchBits.push(String(it.itemName || it.material?.materialName || it.service?.serviceName || '').toLowerCase());
        });
        const searchText = searchBits.filter(Boolean).join(' ').trim();

        // Lots row
        // Starts empty, not undefined: a payroll card has no lot row at all,
        // and an unassigned variable printed the word "undefined" straight into
        // the card, right above the title.
        const isReminderCard = a.activityType === 'reminder_checklist';
        let lotsRow = '';
        if (lotIds.length) {
            lotsRow = lotIds.map((id) => {
                const name = LOT_NAMES[id] || ('Lot #' + id);
                const variety = LOT_VARIETIES[id] || '';
                const text = name;   // show only the lot name (variety/DAS kept in data + editor)
                const hue = (id * 137) % 360;   // golden-angle → distinct, stable per lot
                return `<span class="item-tag lot-tag" data-lot-id="${id}" data-lot-name="${esc(name)}" data-lot-variety="${esc(variety)}" style="background:hsl(${hue}, 55%, 40%)">${esc(text)}</span>`;
            }).join('');
        } else if (a.activityType !== 'worker_payroll') {
            // A payroll day has no lot by nature, so saying so is noise.
            lotsRow = '<span class="item-tag activity-na-tag" title="Applies generally — not tied to any specific lot">N/A — Not lot-specific</span>';
        }
        // Variety + DAS below the title, as regular neutral tags.
        let lotMetaRow = '';
        if (lotIds.length) {
            const metas = lotIds.map((id) => {
                const name = LOT_NAMES[id] || ('Lot #' + id);
                const variety = LOT_VARIETIES[id] || '';
                const das = computeDasLabel(id, targetDateStr).replace(/^\s*·\s*/, '');
                const parts = [];
                if (variety) parts.push(variety);
                if (das) parts.push(das);
                return parts.length ? { name, text: parts.join(' · ') } : null;
            }).filter(Boolean);
            const multi = metas.length > 1;
            const inner = metas.map((m) => `<span class="item-tag lot-meta-tag">${esc((multi ? m.name + ' · ' : '') + m.text)}</span>`).join('');
            // Always render the box (even empty) so the DAS refresh can fill it.
            lotMetaRow = `<div class="activity-card-lots activity-card-lotmeta">${inner}</div>`;
        }

        // Badges — irrigation activities show a water-task badge instead of
        // the generic type badge.
        let typeBadge;
        if (a.activityType === 'irrigation') {
            const wt = (a.waterTask && WATER_TASK_LABELS[a.waterTask]) ? a.waterTask : 'irrigate';
            const color = WATER_TASK_COLORS[wt] || '#2f8fd8';
            typeBadge = `<span class="badge water-task-badge" style="--wt:${color}">${SVG.water || '💧'} ${esc(WATER_TASK_LABELS[wt])}</span>`;
        } else if (a.activityType === 'service') {
            const priceTxt = (a.servicePrice != null && a.servicePrice !== '') ? ` <span class="item-tag-price">₱${esc(fmtMoney(a.servicePrice))}</span>` : '';
            typeBadge = `<span class="badge service-badge">${SVG.service || '🛠'} Service${priceTxt}</span>`;
        } else if (a.activityType === 'worker_payroll') {
            typeBadge = '';        // it lives beside the kebab now
        } else if (a.activityType === 'reminder_checklist') {
            typeBadge = '';        // the ticked list says what this is
        } else {
            typeBadge = typeLabel ? `<span class="badge badge-green activity-type-badge">${esc(typeLabel)}</span>` : '';
        }
        // Type chip before the title — keep in sync with activity-card.blade.php.
        const typeIcoClass = a.activityType === 'irrigation' ? 'type-ico-irrigation'
            : (a.activityType === 'service' ? 'type-ico-service'
            : (a.activityType === 'reminder_checklist' ? 'type-ico-reminder' : 'type-ico-task'));
        const typeIcoSvg = a.activityType === 'irrigation' ? SVG.water
            : (a.activityType === 'service' ? SVG.service
            : (a.activityType === 'reminder_checklist' ? SVG.reminder : SVG.task));
        const typeIco = `<span class="type-ico ${typeIcoClass}" aria-hidden="true">${typeIcoSvg}</span>`;
        // Day-0 badge label follows the covered lots: DAP 0 only if every lot is
        // DAP, otherwise DAS 0 (the seeding anchor for DAS/DAT lots).
        const dzMode = (lotIds.length && lotIds.every((id) => lotDayType(id) === 'DAP')) ? 'DAP' : 'DAS';
        const dayZeroBadge = isDayZeroFlag
            ? `<span class="badge day-zero-badge" title="This activity's start date becomes ${dzMode} 0 for every lot it covers">${SVG.star} ${dzMode} 0</span>`
            : '';
        const transplantBadge = isTransplantFlag
            ? `<span class="badge transplant-badge" title="Transplant day — starts a fresh DAT counter for every lot it covers">${SVG.star} DAT 0</span>`
            : '';
        const rangeBadge = isRange
            ? `<span class="badge badge-gray range-badge" title="Multi-day range">&rarr; ${esc(MONTH_SHORT[endObj.getMonth()] + ' ' + endObj.getDate())} (${rangeDays}d)</span>`
            : '';
        const hiddenTag = `<span class="badge badge-gray hide-activity-tag"${isHiddenFlag ? '' : ' style="display:none;"'}>Hidden</span>`;

        // Meta strip: workers then materials/services (time chip is added inline below).
        // Not on a payroll card: its checklist above is the roster, and the
        // half/whole chip beside these means nothing for attendance.
        const workerTags = a.activityType === 'worker_payroll' ? '' : workerIds
            .map((id) => `<span class="item-tag worker-tag">${esc(WORKER_NAMES[id] || ('Worker #' + id))}`
                + (workerOffOn(Number(id), (a.targetDate || '').slice(0, 10))
                    ? '<span class="w-forced" title="Marked off this day — working anyway">forced</span>' : '')
                + '</span>')
            .join('');

        let itemTags = '';
        (a.items || []).forEach((it) => {
            const name = it.itemName || it.material?.materialName || it.service?.serviceName || 'Item';
            const unit = it.unitOfMeasure || it.material?.unitOfMeasure || '';
            const qty = (it.quantity != null && it.quantity !== '') ? ' &times;' + esc(trimQty(it.quantity)) + (unit ? ' ' + esc(unit) : '') : '';
            const price = (it.unitPrice != null && it.unitPrice !== '') ? ` <span class="item-tag-price">@ ₱${esc(fmtMoney(it.unitPrice))}</span>` : '';
            itemTags += `<span class="item-tag material-tag">${esc(name)}${qty}${price}</span>`;
        });

        const descHtml = a.description || '';
        const imageUrl = a.imageUrl || (a.imagePath ? STORAGE_BASE + '/' + String(a.imagePath).replace(/^\/+/, '') : '');
        const cardImages = (a.images && a.images.length) ? a.images : (imageUrl ? [{ url: imageUrl }] : []);
        const imagesHtml = cardImages.length
            ? `<div class="activity-card-images mt-2" data-lightbox>${cardImages.map((im) => `<img src="${esc(im.url)}" alt="Reference image" loading="lazy">`).join('')}</div>`
            : '';
        const nameAttr = esc(a.activityTitle || '');

        const isDoneFlag = boolFlag(a.isDone) ? 1 : 0;
        return `<div class="activity-card prio-${esc(priority)}${isHiddenFlag ? ' is-hidden' : ''}${isDoneFlag ? ' is-done' : ''}" draggable="${isDoneFlag ? 'false' : 'true'}"
     data-id="${a.id}"
     data-is-done="${isDoneFlag}"
     data-tags="${esc(JSON.stringify(Array.isArray(a.tags) ? a.tags : []))}"
     data-labour="${Number(a.labourTotal || 0)}"
     data-target-date="${esc(targetDateStr)}"
     data-target-end-date="${esc(targetEndDateStr)}"
     data-lot-signature="${esc(lotSig)}"
     data-sequence-order="${seqOrder}"
     data-is-day-zero="${isDayZeroFlag}"
     data-is-transplant="${isTransplantFlag}"
     data-activity-type="${esc(a.activityType || '')}"
     data-activity-types="${esc([a.activityType, ...(Array.isArray(a.extraTypes) ? a.extraTypes : [])].filter(Boolean).join(','))}"
     data-is-hidden="${isHiddenFlag}"
     data-search="${esc(searchText)}"${lotAccentStyle}>
    <div class="flex items-start justify-between gap-2">
        <div class="flex items-start gap-2.5 min-w-0 grow">
            <button type="button" class="done-check${isDoneFlag ? ' is-checked' : ''}${LOCK_EDIT_CLS}" data-id="${a.id}"${LOCK_EDIT}
                title="${esc(editTitle(isDoneFlag ? 'Mark as not done (unlocks editing)' : 'Mark this activity as done'))}"
                aria-pressed="${isDoneFlag ? 'true' : 'false'}" aria-label="Mark activity as done">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
            </button>
            ${typeIco}
            <div class="min-w-0 grow">
            <div class="activity-card-lots activity-card-lothead">${isReminderCard
                ? '<span class="badge reminder-head-badge">Reminder Checklist</span>'
                : lotsRow}</div>
            <h3 class="activity-card-title">${esc(a.activityTitle || '')}</h3>
            <div class="activity-card-badges">
                <span class="pill pill-${esc(priority)}">${esc(priorityCap)}</span>
                ${isReminderCard && lotIds.length ? lotsRow : ''}
                ${typeBadge}
                ${dayZeroBadge}
                ${transplantBadge}
                ${rangeBadge}
                ${hiddenTag}
            </div>
            ${lotMetaRow}
            </div>
        </div>
        <div class="flex items-center shrink-0">
            ${hasChecklist(a) ? '<span class="badge payroll-badge mr-1">Worker checklist</span>' : ''}
            <button type="button" class="icon-btn add-note-activity-btn${LOCK_EDIT_CLS}" data-id="${a.id}" data-name="${nameAttr}"${LOCK_EDIT} title="${esc(editTitle('Add a note (activity is locked)'))}">${SVG.note}</button>
            <div class="hidden md:flex items-center gap-0.5 done-hide">
                <button type="button" class="icon-btn hide-activity-toggle${LOCK_EDIT_CLS}" data-id="${a.id}"${LOCK_EDIT} title="${esc(editTitle('Toggle visibility in presentations and exports'))}" aria-pressed="${isHiddenFlag ? 'true' : 'false'}">${SVG.eye}</button>
                <button type="button" class="icon-btn edit-activity-btn${LOCK_EDIT_CLS}" data-id="${a.id}"${LOCK_EDIT} title="${esc(editTitle('Edit'))}">${SVG.edit}</button>
                <button type="button" class="icon-btn tag-activity-btn${LOCK_EDIT_CLS}" data-id="${a.id}" data-name="${nameAttr}"${LOCK_EDIT} title="${esc(editTitle('Tag a drawing, map or note'))}">${SVG.tag}</button>
                <button type="button" class="icon-btn duplicate-activity-btn${LOCK_EDIT_CLS}" data-id="${a.id}" data-name="${nameAttr}"${LOCK_EDIT} title="${esc(editTitle('Duplicate'))}">${SVG.duplicate}</button>
                <button type="button" class="icon-btn to-draft-activity-btn${LOCK_EDIT_CLS}" data-id="${a.id}" data-name="${nameAttr}"${LOCK_EDIT} title="${esc(editTitle('Move to drafts (hide without deleting)'))}">${SVG.archive}</button>
                <button type="button" class="icon-btn icon-btn-danger delete-activity-btn${LOCK_EDIT_CLS}" data-id="${a.id}" data-name="${nameAttr}"${LOCK_EDIT} title="${esc(editTitle('Delete'))}">${SVG.trash}</button>
            </div>
            <button type="button" class="icon-btn card-menu-btn md:hidden done-hide" data-id="${a.id}" data-name="${nameAttr}" title="Actions">${SVG.kebab}</button>
        </div>
    </div>
    ${descHtml ? `<div class="activity-description-content text-sm text-gray-700 mt-2" data-lightbox>${descHtml}</div>` : ''}
    ${imagesHtml}
    <div class="activity-meta">
        ${(hasChecklist(a) || isReminderCard) ? ''
            : `<span class="meta-time">${SVG.clock} ${esc(timeRequiredLabel(a.timeRequired))}</span>`}
        ${workerTags}
        ${itemTags}
    </div>
    ${payrollChecklist(a)}
    ${reminderChecklist(a)}
    ${hasChecklist(a) ? '' : labourLine(a.labourTotal, a.workerPay)}
    <div class="activity-tags">${activityTagChips(a.tags)}</div>
</div>`;
    }

    /* Things an activity points at — a drawing, a map, a note. The chip holds
       only a name and a way in; the thing itself stays where it lives. */
    const TAG_ICON = {
        drawing: 'M4 20l4-1L20 7a2 2 0 00-3-3L5 16l-1 4zM14 6l4 4',
        map: 'M9 20l-5-2V6l5 2m0 12l6-2m-6 2V8m6 10l5 2V8l-5-2m0 12V6M9 8l6-2',
        note: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
    };
    /* A payroll day's roster, on the card. Same markup as the Blade renderer:
       tick who turned up without opening anything. */
    /** A reminder checklist on the card. Mirrors activity-card.blade.php. */
    function reminderChecklist(a) {
        if (a.activityType !== 'reminder_checklist') return '';
        const rows = (a.reminders || []).filter((r) => r && r.text);
        if (!rows.length) return '';
        let spend = 0, earn = 0;
        const body = rows.map((r, i) => {
            const kind = r.kind === 'expense' || r.kind === 'income' ? r.kind : 'none';
            const amt = Number(r.amount) || 0;
            if (r.done && kind === 'expense') spend += amt;
            if (r.done && kind === 'income') earn += amt;
            const money = kind === 'none' || !amt ? ''
                : `<span class="act-rem-amt is-${kind}">${kind === 'income' ? '+' : '−'}${esc(money2(amt))}</span>`;
            /* Ticking an errand writes a money row, so it takes the same right
               as any other change to the plan — no per-worker exception like
               the payroll roster has. Drawn greyed rather than dropped, and
               mirrored in activity-card.blade.php. */
            return `<label class="act-rem-row${r.done ? ' is-done' : ''}${LOCK_EDIT_CLS}" data-rem-index="${i}" title="${esc(editTitle('Tick this errand off'))}">
                <input type="checkbox"${r.done ? ' checked' : ''}${LOCK_EDIT}>
                <span class="act-rem-name">${esc(r.text)}</span>
                ${money}
            </label>`;
        }).join('');
        const totals = [];
        if (spend > 0) totals.push(`<span>To spend</span><span class="act-rem-amt is-expense">${esc(money2(spend))}</span>`);
        if (earn > 0) totals.push(`<span>To collect</span><span class="act-rem-amt is-income">${esc(money2(earn))}</span>`);
        const totalRow = totals.map((t) => `<div class="act-rem-total">${t}</div>`).join('');
        return `<div class="act-rem" data-rem-activity="${esc(a.id)}">${body}${totalRow}</div>`;
    }
    const money2 = (n) => money(n);

    const hasChecklist = (a) => a.activityType === 'worker_payroll' || !!a.workerChecklist;

    function payrollChecklist(a) {
        // A payroll day always keeps a roster; any other task keeps one if it
        // was asked to. Mirrors hasWorkerChecklist() on the model.
        if (a.activityType !== 'worker_payroll' && !a.workerChecklist) return '';
        const pay = a.workerPay || {};
        const ids = Object.keys(pay);
        if (!ids.length) return '';
        const date = (a.targetDate || '').slice(0, 10);
        return `<div class="act-check" data-att-date="${esc(date)}">`
            + ids.map((id) => {
                const w = pay[id];
                const here = w.present !== false;
                const forced = workerOffOn(Number(id), date)
                    ? '<span class="w-forced" title="Marked off this day">forced</span>' : '';
                // Anyone who can edit may move any tick; a worker may move
                // only their own, and only where the task allows it.
                const mine = ME_WORKER_ID && Number(ME_WORKER_ID) === Number(id);
                const mayTick = CAN_EDIT || (mine && a.workerSelfCheck);
                return `<label class="act-check-row${here ? '' : ' is-out'}${mine ? ' is-me' : ''}${mayTick ? '' : ' is-locked'}" data-att-worker="${esc(id)}">
                    <input type="checkbox"${here ? ' checked' : ''}${mayTick ? '' : ' disabled'}>
                    <span class="act-check-name">${esc(w.name || 'Worker')}${forced}</span>
                    <span class="act-check-pay">${esc(money(w.total || 0))}</span>
                </label>`;
            }).join('')
            + `<div class="act-check-total"><span>To pay</span><span data-att-total>${esc(money(
                ids.reduce((t, id) => t + (pay[id].present === false ? 0 : (pay[id].total || 0)), 0)
            ))}</span></div>`
            + '</div>';
    }

    /* Ticking on the card is the same act as ticking in the attendance sheet,
       so it goes through the same endpoint and the day's figure follows. */
    document.addEventListener('change', async (e) => {
        const box = e.target.closest('.act-check input[type=checkbox]');
        if (!box) return;
        const row = box.closest('[data-att-worker]');
        const date = box.closest('[data-att-date]')?.getAttribute('data-att-date');
        if (!row || !date) return;
        row.classList.toggle('is-out', !box.checked);

        // Move the day's figure now, not when the server answers. The card
        // carries what this person costs, so the sum can be adjusted here and
        // the header follows in the same breath as the tick.
        const card = row.closest('.activity-card');
        const worth = Number((row.querySelector('.act-check-pay')?.textContent || '').replace(/[^0-9.]/g, '')) || 0;
        const retotal = () => {
            const block = row.closest('.act-check');
            const out = block?.querySelector('[data-att-total]');
            if (!out) return;
            let sum = 0;
            block.querySelectorAll('.act-check-row').forEach((r) => {
                if (r.querySelector('input')?.checked) {
                    sum += Number((r.querySelector('.act-check-pay')?.textContent || '').replace(/[^0-9.]/g, '')) || 0;
                }
            });
            out.textContent = money(sum);
        };
        if (card) {
            const was = Number(card.getAttribute('data-labour')) || 0;
            card.setAttribute('data-labour', String(Math.max(0, was + (box.checked ? worth : -worth))));
            retotal();
            paintAllDayCash();
        }

        try {
            await api(U.attendanceMark(), {
                method: 'POST',
                body: { date, workerId: parseInt(row.getAttribute('data-att-worker'), 10), present: box.checked ? 1 : 0 },
            });
        } catch (err) {
            // Put the tick and the figure back exactly as they were.
            box.checked = !box.checked;
            row.classList.toggle('is-out', !box.checked);
            if (card) {
                const now = Number(card.getAttribute('data-labour')) || 0;
                card.setAttribute('data-labour', String(Math.max(0, now + (box.checked ? worth : -worth))));
                retotal();
                paintAllDayCash();
            }
            toast(err.message || 'Could not save that.', 'error');
        }
    });

    /* ---- Ticking a reminder off the card ----------------------------------
     * The tick is the point of a reminder checklist, so it happens on the
     * card, and the money follows straight away: the server turns a ticked
     * line into an ordinary day expense or day income row, which is what the
     * day header is reading. The board reloads that day's cash from the
     * server rather than guessing at it here. */
    document.addEventListener('change', async (e) => {
        const box = e.target.closest('.act-rem-row input[type=checkbox]');
        if (!box) return;
        const row = box.closest('[data-rem-index]');
        const wrap = box.closest('[data-rem-activity]');
        if (!row || !wrap) return;

        row.classList.toggle('is-done', box.checked);
        try {
            const res = await api(U.reminderToggle(), {
                method: 'POST',
                body: {
                    activityId: parseInt(wrap.getAttribute('data-rem-activity'), 10),
                    index: parseInt(row.getAttribute('data-rem-index'), 10),
                    done: box.checked ? 1 : 0,
                },
            });
            paintReminderTotals(wrap, res.data);
            // A ticked line may have added money to the day; ask the server
            // what the day costs now rather than adding it up twice.
            await refreshDayMoney((box.closest('.date-group')?.getAttribute('data-date') || '').trim());
        } catch (err) {
            box.checked = !box.checked;
            row.classList.toggle('is-done', box.checked);
            toast(err.message || 'Could not save that.', 'error');
        }
    });

    /** Re-read one day's expenses, so the header pill and the expense strip
     *  catch up with a tick that just added or removed money. */
    async function refreshDayMoney(dateKey) {
        if (!dateKey) return;
        try {
            const res = await api(U.dayExpenseList(dateKey));
            DAY_EXPENSES[dateKey] = (res && res.data) || [];
            renderExpenseBlockFor(dateKey);
            paintAllDayCash();
        } catch (err) { /* the tick still stands; the pill catches up on reload */ }
    }

    /** Redraw the "to spend" / "to collect" lines under one checklist. */
    function paintReminderTotals(wrap, data) {
        $qsa('.act-rem-total', wrap).forEach((el) => el.remove());
        const add = (label, amount, tone) => {
            if (!(amount > 0)) return;
            wrap.insertAdjacentHTML('beforeend',
                `<div class="act-rem-total"><span>${label}</span><span class="act-rem-amt is-${tone}">${esc(money(amount))}</span></div>`);
        };
        add('To spend', Number(data?.expenseTotal) || 0, 'expense');
        add('To collect', Number(data?.incomeTotal) || 0, 'income');
    }

    /* What the day's labour on this activity costs, spelled out per worker so
       the number can be checked rather than just trusted. */
    function labourLine(total, pay) {
        if (!total || Number(total) <= 0) return '';
        const parts = Object.values(pay || {})
            .map((p) => {
                const len = p.effectivePart === 'half' ? '½d' : (p.effectivePart === 'whole' ? '1d' : '—');
                return `${esc(p.name || 'Worker')} ${len} ${money(p.total)}`;
            })
            .join(' · ');
        return `<div class="activity-labour"><span class="al-total">${money(total)}</span>${parts ? `<span class="al-parts">${parts}</span>` : ''}</div>`;
    }

    function activityTagChips(tags) {
        return (Array.isArray(tags) ? tags : []).map((t) => {
            const kind = t.kind || 'note';
            return `<a class="act-tag" href="${esc(t.url || '#')}" data-kind="${esc(kind)}" data-url="${esc(t.url || '')}" title="Open this ${esc(kind)}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="${TAG_ICON[kind] || TAG_ICON.note}"/></svg>
                ${esc(t.label || kind)}
            </a>`;
        }).join('');
    }

    /* ---- Tagging an activity --------------------------------------------
     * The drawing, the map and the note already exist somewhere. Tagging
     * points at one; it never copies it, so editing it later is still done in
     * the one place it lives. */
    let TAG_FOR = null, TAGGABLES = null, TAG_TAB = 'drawing';

    function paintCardTags(id, tags) {
        const card = $qs(`#activitiesList .activity-card[data-id="${id}"]`);
        const row = card && card.querySelector('.activity-tags');
        if (row) row.innerHTML = activityTagChips(tags);
    }

    function paintTagCurrent(tags) {
        const box = $id('activityTagCurrent');
        if (!box) return;
        box.innerHTML = (tags && tags.length)
            ? '<p class="text-xs font-bold text-gray-400 mb-1">On this activity</p>'
                + tags.map((t, i) => `<div class="flex items-center gap-2 py-1">
                        <span class="act-tag" style="cursor:default">${esc(t.label || t.kind)}</span>
                        <button type="button" class="icon-btn icon-btn-danger ml-auto" data-untag="${i}" title="Remove this tag">${SVG.trash}</button>
                    </div>`).join('')
            : '';
    }

    function paintTagList() {
        const box = $id('activityTagList');
        if (!box) return;
        const rows = (TAGGABLES && TAGGABLES[TAG_TAB === 'drawing' ? 'drawings' : (TAG_TAB === 'map' ? 'maps' : 'notes')]) || [];
        box.innerHTML = rows.length
            ? rows.map((r) => `<button type="button" class="w-full flex items-center gap-2.5 rounded-xl p-2 text-left hover:bg-gray-50" data-pick="${esc(r.ref)}">
                    ${r.url && TAG_TAB === 'drawing' ? `<span class="w-11 h-9 rounded-lg bg-gray-100 overflow-hidden shrink-0"><img src="${esc(r.url)}" alt="" class="w-full h-full object-cover"></span>` : ''}
                    <span class="min-w-0 font-semibold text-gray-800 text-sm truncate">${esc(r.label || 'Untitled')}</span>
                </button>`).join('')
            : `<p class="text-sm text-gray-400 py-2">Nothing to tag yet — make a ${TAG_TAB} first and it will be listed here.</p>`;
    }

    /* ---- Advanced info: what went on this ground before ---------------
     * The question in front of a task is nearly always "when did this lot
     * last get a herbicide" — and the board held the answer while making a
     * person scroll weeks of it to find out. */
    const ADV_URL = @json(route('sm.activity.advanced')) + '?scheduleId=' + SCHEDULE_ID;

    function advGap(n) {
        // Counted back from this task's own date, so "ago" would be a lie for
        // anything planned. "Before" is true whichever side of today it sits.
        if (n === 0) return 'same day';
        return n + ' day' + (n === 1 ? '' : 's') + ' before';
    }

    function advRow(r) {
        const none = r.daysBefore === null || r.daysBefore === undefined;
        return `<div class="adv-row${none ? ' is-none' : ''}">
            <span class="adv-lbl">${esc(r.label)}</span>
            ${none
                ? '<span class="adv-none">never on this ground</span>'
                : `<span class="adv-n">${r.daysBefore}</span>
                   <span class="adv-when">${esc(advGap(r.daysBefore))}<small>${esc(r.when || '')}${r.title ? ' · ' + esc(r.title) : ''}</small></span>`}
        </div>`;
    }

    function advList(rows) {
        return '<div class="adv-list">' + rows.map(advRow).join('') + '</div>';
    }

    /* The answer, in words, before the table says it again in numbers. The
     * person opening this sheet has one question and it is nearly always this
     * one; making them read a table to find it was the whole complaint. */
    function advLede(r) {
        const none = r.daysBefore === null || r.daysBefore === undefined;
        return `<div class="adv-lede${none ? ' is-none' : ''}">
            <div class="adv-lede-k">Last ${esc(String(r.label).toLowerCase())} on this ground</div>
            <div class="adv-lede-a">${none ? 'Never — this is the first' : esc(advGap(r.daysBefore))}</div>
            ${none ? '' : `<div class="adv-lede-s">${esc(r.when || '')}${r.title ? ' · ' + esc(r.title) : ''}</div>`}
        </div>`;
    }

    async function openAdvancedInfo(id, name) {
        $id('advInfoTitle').textContent = name || 'Advanced info';
        $id('advInfoBody').innerHTML = '<p class="text-sm text-gray-500 text-center py-6">Loading…</p>';
        $id('advInfoSub').textContent = '';
        openSheet('advInfoSheet');
        try {
            const res = await api(ADV_URL + '&id=' + id);
            const d = res.data || {};
            const lots = (d.lots || []).length ? d.lots.join(', ') : 'every lot';
            $id('advInfoSub').textContent = `${d.activity?.date || ''} · ${lots}`
                + (d.activity?.types?.length ? ' · ' + d.activity.types.join(' + ') : '');
            /* Three tiers, and only the first two are shown. A foliar spray
             * raises questions about what else has been on those leaves; the
             * last granular answers a different question, so it waits behind
             * a button instead of crowding the one they asked. */
            const rows = d.rows || [];
            const own = rows.filter((r) => r.own);
            const kin = rows.filter((r) => !r.own && r.related);
            const rest = rows.filter((r) => !r.own && !r.related);

            let html = '';
            if (own.length) html += advLede(own[0]);
            if (own.length > 1) html += '<p class="adv-head">Also this kind of work</p>' + advList(own.slice(1));
            if (kin.length) {
                html += `<p class="adv-head">${d.narrowed ? 'Worth knowing before you mix' : 'On this ground'}</p>`
                    + advList(kin);
            }
            if (rest.length) {
                html += `<button type="button" class="adv-more" id="advMore" aria-expanded="false" aria-controls="advRest">`
                    + `Show ${rest.length} more, from other kinds of work</button>`
                    + `<div class="adv-rest" id="advRest" hidden>${advList(rest)}</div>`;
            }
            html += '<p class="adv-foot">Counted from this task’s own date, against the lots it covers. Drafts are not counted — a draft has not happened.</p>';
            $id('advInfoBody').innerHTML = html;

            const more = $id('advMore');
            if (more) {
                more.addEventListener('click', () => {
                    const box = $id('advRest');
                    const open = box.hasAttribute('hidden');
                    if (open) box.removeAttribute('hidden'); else box.setAttribute('hidden', '');
                    more.setAttribute('aria-expanded', open ? 'true' : 'false');
                    more.textContent = open
                        ? 'Hide the other kinds of work'
                        : `Show ${rest.length} more, from other kinds of work`;
                });
            }
        } catch (err) {
            $id('advInfoBody').innerHTML = `<p class="text-sm text-red-600 text-center py-6">${esc(err.message || 'Could not read that.')}</p>`;
        }
    }

    async function openActivityTagSheet(id, name) {
        TAG_FOR = id;
        $id('activityTagTitle').textContent = name ? `Tag: ${name}` : 'Tag this activity';
        const card = $qs(`#activitiesList .activity-card[data-id="${id}"]`);
        let current = [];
        try { current = JSON.parse(card?.getAttribute('data-tags') || '[]'); } catch (_) { current = []; }
        paintTagCurrent(current);
        $id('activityTagList').innerHTML = '<p class="text-sm text-gray-400 py-2">Loading…</p>';
        openSheet('activityTagSheet');
        try {
            const res = await api(U.taggables());
            TAGGABLES = res.data || {};
            paintTagList();
        } catch (err) {
            $id('activityTagList').innerHTML = '<p class="text-sm text-red-500 py-2">Could not load what there is to tag.</p>';
        }
    }

    function rememberTags(id, tags) {
        const card = $qs(`#activitiesList .activity-card[data-id="${id}"]`);
        if (card) card.setAttribute('data-tags', JSON.stringify(tags || []));
        paintCardTags(id, tags);
        paintTagCurrent(tags || []);
    }

    document.addEventListener('click', async (e) => {
        const tab = e.target.closest('[data-tag-tab]');
        if (tab) {
            TAG_TAB = tab.getAttribute('data-tag-tab');
            $qsa('#activityTagTabs [data-tag-tab]').forEach((b) => b.classList.toggle('is-on', b === tab));
            paintTagList();
            return;
        }
        const pick = e.target.closest('[data-pick]');
        if (pick && TAG_FOR) {
            const ref = pick.getAttribute('data-pick');
            const rows = (TAGGABLES && TAGGABLES[TAG_TAB === 'drawing' ? 'drawings' : (TAG_TAB === 'map' ? 'maps' : 'notes')]) || [];
            const row = rows.find((r) => String(r.ref) === ref);
            if (!row) return;
            try {
                const res = await api(U.tag(), { method: 'POST', body: { activityId: TAG_FOR, kind: TAG_TAB, ref, label: row.label || TAG_TAB, url: row.url } });
                rememberTags(TAG_FOR, res.data.tags);
                toast('Tagged.');
            } catch (err) { toast(err.message || 'Could not tag that.', 'error'); }
            return;
        }
        const untag = e.target.closest('[data-untag]');
        if (untag && TAG_FOR) {
            try {
                const res = await api(U.untag(), { method: 'DELETE', body: { activityId: TAG_FOR, index: parseInt(untag.getAttribute('data-untag'), 10) } });
                rememberTags(TAG_FOR, res.data.tags);
            } catch (err) { toast(err.message || 'Could not remove that tag.', 'error'); }
            return;
        }
        // A drawing opens where it can be looked at; a map and a note are
        // modules, so their chip is a plain link the shell already handles.
        const chip = e.target.closest('.act-tag[data-kind="drawing"]');
        if (chip && chip.getAttribute('data-url')) {
            e.preventDefault();
            if (typeof window.openNoteLightbox === 'function') window.openNoteLightbox('image', chip.getAttribute('data-url'));
            else window.open(chip.getAttribute('data-url'), '_blank', 'noopener');
        }
    });

    /* ---- What to bring to the field that day ------------------------------
     * Wages for everyone on the day's activities plus any extra expense logged
     * against it. Read off the board rather than recomputed: each card already
     * carries the total the server worked out for it, so the pill can never
     * disagree with the lines under the cards. */
    function dayCashTotal(group) {
        const dateKey = (group.getAttribute('data-date') || '').trim();
        const labour = $qsa('.activity-card[data-labour]', group)
            .reduce((t, c) => t + (Number(c.getAttribute('data-labour')) || 0), 0);
        const extra = _expenseRowsFor(dateKey).reduce((t, r) => t + (Number(r.amount) || 0), 0);
        return labour + extra;
    }
    function paintDayCash(group) {
        const pill = group.querySelector('.date-header-cash');
        if (!pill) return;
        const total = dayCashTotal(group);

        pill.hidden = total <= 0;
        if (total > 0) {
            pill.innerHTML = `${SVG.wallet}<span>${esc(money(total))}</span>`;
            pill.title = 'Cash to prepare for this day — wages for everyone on it, plus any extra expense logged against it';
        } else {
            pill.textContent = '';
        }

        // The forecast and the cost share a second line. CSS order puts them
        // after everything else; this is the break that makes "after" mean
        // "next line" — added only when there is something down there, so a
        // day with neither does not grow an empty row.
        const header = group.querySelector('.date-header');
        if (!header) return;
        const wx = header.querySelector('.date-header-weather, .wx-mini-btn');
        ensureRowBreak(header, !!wx || total > 0);
    }
    /* A number nobody can check is a number nobody trusts. Tapping it shows
       the same arithmetic in longhand: one line per activity with who is on
       it, one line per extra expense, and the total they add up to. */
    function openDayCash(group) {
        const dateKey = (group.getAttribute('data-date') || '').trim();
        const wages = [];
        $qsa('.activity-card[data-labour]', group).forEach((card) => {
            const amount = Number(card.getAttribute('data-labour')) || 0;
            if (amount <= 0) return;
            wages.push({
                name: (card.querySelector('.activity-card-title')?.textContent || 'Activity').trim(),
                detail: (card.querySelector('.activity-labour .al-parts')?.textContent || '').trim(),
                amount,
            });
        });
        const extras = _expenseRowsFor(dateKey).map((r) => ({
            name: r.note || 'Extra expense', detail: '', amount: Number(r.amount) || 0,
        }));

        const wageSum = wages.reduce((t, r) => t + r.amount, 0);
        const extraSum = extras.reduce((t, r) => t + r.amount, 0);
        const grand = wageSum + extraSum;
        const share = grand > 0 ? Math.round((wageSum / grand) * 100) : 0;

        const line = (r, tone) => `<div class="dc-row">
            <span class="dc-dot dc-dot-${tone}"></span>
            <span class="dc-name">${esc(r.name)}${r.detail ? `<span class="dc-detail">${esc(r.detail)}</span>` : ''}</span>
            <span class="dc-amt">${esc(money(r.amount))}</span>
        </div>`;

        const section = (title, tone, icon, list, sum) => !list.length ? '' : `
            <div class="dc-sec dc-sec-${tone}">
                <div class="dc-sec-head">
                    <span class="dc-sec-ico">${icon}</span>
                    <span class="dc-sec-title">${esc(title)}</span>
                    <span class="dc-sec-sum">${esc(money(sum))}</span>
                </div>
                <div class="dc-sec-body">${list.map((r) => line(r, tone)).join('')}</div>
            </div>`;

        const ICON_WAGES = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-1a4 4 0 00-4-4h-1M9 11a4 4 0 100-8 4 4 0 000 8zm8 0a3 3 0 100-6M2 20v-1a5 5 0 015-5h4a5 5 0 015 5v1H2z"/></svg>';
        const ICON_EXTRA = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="8.5"/><path stroke-linecap="round" stroke-linejoin="round" d="M12 7v10M14.4 9.4a2.3 2.3 0 00-2.4-1.3c-1.3.1-2.3.8-2.3 1.9s1 1.7 2.5 1.9 2.6.8 2.6 2-1.1 1.9-2.5 1.9a2.4 2.4 0 01-2.4-1.3"/></svg>';

        const counts = [
            wages.length ? wages.length + (wages.length === 1 ? ' activity' : ' activities') : '',
            extras.length ? extras.length + (extras.length === 1 ? ' expense' : ' expenses') : '',
        ].filter(Boolean).join(' · ');

        $id('dayCashTitle').textContent = 'Cash for ' + prettyDateFull(dateKey);
        $id('dayCashBody').innerHTML = `
            <div class="dc-hero">
                <span class="dc-hero-label">Cash to prepare</span>
                <span class="dc-hero-amt">${esc(money(grand))}</span>
                <span class="dc-hero-sub">${esc(prettyDateFull(dateKey))}${counts ? ' · ' + esc(counts) : ''}</span>
                ${wageSum > 0 && extraSum > 0 ? `<span class="dc-split" title="Wages ${share}% · expenses ${100 - share}%"><span class="dc-split-wages" style="width:${share}%"></span></span>` : ''}
            </div>
            ${section('Wages', 'wages', ICON_WAGES, wages, wageSum)}
            ${section('Extra expenses', 'extra', ICON_EXTRA, extras, extraSum)}
            <p class="dc-foot">${wages.length
                ? 'Wages come from each activity: a worker with no half or whole day of their own is paid for as long as the task itself takes.'
                : 'No wages here yet — nobody is assigned to this day.'}</p>`;
        openSheet('dayCashSheet');
    }
    document.addEventListener('click', (e) => {
        const pill = e.target.closest('.date-header-cash');
        if (!pill) return;
        e.preventDefault();
        e.stopPropagation();          // not a fold of the day
        const group = pill.closest('.date-group');
        if (group) openDayCash(group);
    });

    /* True when a mutation record only moved our own decorations around — the
       cash line's break, the forecast strip, or the button it collapses into. */
    function ownBookkeeping(rec) {
        const ours = (n) => n.nodeType === 1 && (
            n.classList.contains('dh-rowbreak')
            || n.classList.contains('date-header-weather')
            || n.classList.contains('wx-mini-btn')
            || n.classList.contains('date-header-cash')
        );
        const added = Array.from(rec.addedNodes);
        const removed = Array.from(rec.removedNodes);
        return (added.length + removed.length) > 0 && added.every(ours) && removed.every(ours);
    }
    window.__ownBookkeeping = ownBookkeeping;

    /* The zero-height item that starts the header's second row. Both the
       forecast and the day's cost need it there before they can be measured
       or placed, so it has one owner and is safe to ask for twice. */
    function ensureRowBreak(header, want) {
        if (!header) return;
        let brk = header.querySelector('.dh-rowbreak');
        if (want && !brk) {
            brk = document.createElement('span');
            brk.className = 'dh-rowbreak';
            header.appendChild(brk);
        } else if (!want && brk) {
            brk.remove();
        }
    }
    window.__ensureRowBreak = ensureRowBreak;

    /* ---- The veil -----------------------------------------------------------
     * Two things have to land before the board reads correctly: the day
     * figures (drawn from what is already on the cards, so effectively
     * immediate) and the forecast (a real round trip). The veil lifts when
     * both report in, and lifts anyway after four seconds — a board you can
     * read with one pill missing beats a spinner that never goes away. */
    const boardReady = { cash: false, weather: false };
    let veilLifted = false;
    function liftVeil(why) {
        if (veilLifted) return;
        veilLifted = true;
        const veil = $id('boardVeil');
        if (!veil) return;
        veil.classList.add('is-done');
        setTimeout(() => { veil.hidden = true; }, 320);
    }
    window.boardVeilStep = (what) => {
        boardReady[what] = true;
        const text = $id('boardVeilText');
        if (text && what === 'cash' && !boardReady.weather) text.textContent = 'Fetching the forecast…';
        if (boardReady.cash && boardReady.weather) liftVeil('both');
    };
    // Nothing may ever answer — no network, no API key, an empty board. The
    // veil is a courtesy, not a gate.
    setTimeout(() => liftVeil('timeout'), 4000);
    document.addEventListener('DOMContentLoaded', () => setTimeout(() => {
        if (!$qs('#activitiesList .date-group')) liftVeil('nothing to wait for');
    }, 400));

    function paintAllDayCash() {
        // The forecast goes first: a day that changed lots is about different
        // ground now, and the cash line's break depends on whether a forecast
        // ends up on that second row.
        window.__wxRepaint?.();
        $qsa('#activitiesList .date-group').forEach((g) => { paintDayCash(g); paintDayStage(g); });
    }
    // The weather arrives on its own schedule; when it lands, the second line
    // has to be recomputed so the break exists for it too.
    window.__repaintCash = paintAllDayCash;
    // One hook instead of a call at every render site: cards are rebuilt by
    // saves, deletes, reorders, version switches and the live board feed, and
    // a total that only updates on some of those is worse than none.
    (() => {
        // Never during this script's own run: the day's expenses are a const
        // declared thousands of lines below, and touching it early throws
        // before it exists — taking every listener after this point with it.
        const start = () => {
            const list = document.getElementById('activitiesList');
            if (!list) return;
            paintAllDayCash();
            window.boardVeilStep?.('cash');
            if (!window.MutationObserver) return;
            let pending = null;
            new MutationObserver((records) => {
                if (records.every(ownBookkeeping)) return;
                if (pending) return;
                pending = setTimeout(() => { pending = null; paintAllDayCash(); }, 120);
            }).observe(list, { childList: true, subtree: true });
        };
        if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', start, { once: true });
        else setTimeout(start, 0);
    })();

    window.__renderCardForTest = renderActivityCard;

    function buildRestDayHtml(dateKey, substitute) {
        return `<div class="rest-day-marker${substitute ? ' rest-day-substitute' : ''}" data-date="${esc(dateKey)}">
            ${SVG.moon}
            <div class="grow min-w-0">
                <span class="rest-day-date">${esc(prettyDateLong(dateKey))}</span>
                <span class="rest-day-tag">No activities scheduled</span>
            </div>
            <button type="button" class="btn btn-white btn-sm rest-day-add-btn shrink-0${LOCK_EDIT_CLS}" data-date="${esc(dateKey)}"${LOCK_EDIT} title="${esc(editTitle('Add an activity to this day'))}">+ Add</button>
        </div>`;
    }

    function buildMarkerHtml(dateKey, info) {
        const noteRaw = info.note || '';
        return `<div class="progress-marker" data-marker-id="${esc(String(info.id || ''))}" data-date="${esc(dateKey)}" draggable="true" title="Drag to move this marker to another day">
            <div class="progress-marker-line">
                <span class="progress-marker-bookmark">${SVG.bookmarkSolid} Resume here — ${esc(prettyDate(dateKey))}</span>
                <span class="flex items-center gap-0.5">
                    <button type="button" class="icon-btn progress-marker-edit-btn" data-date="${esc(dateKey)}" title="Edit marker note">${SVG.edit}</button>
                    <button type="button" class="icon-btn icon-btn-danger progress-marker-delete-btn" data-marker-id="${esc(String(info.id || ''))}" data-date="${esc(dateKey)}" title="Remove marker">${SVG.trash}</button>
                </span>
            </div>
            ${noteRaw ? `<div class="progress-marker-note">${esc(noteRaw)}</div>` : ''}
        </div>`;
    }

    const EMPTY_STATE_HTML = `<div id="activitiesEmpty" class="card card-body text-center text-gray-500 py-10">
        <p class="font-bold text-gray-800 mb-1">No activities defined yet.</p>
        <p class="text-sm">Tap <strong>Add Activity</strong> to define your first step.</p>
    </div>`;

    function buildDateGroupShell(dateKey, colorIdx, cards, noteContent, hasMarker, allHidden, noteMediaJson) {
        const isNoDate = dateKey === '__no-date__';
        const dateObj = isNoDate ? null : parseLocalDate(dateKey);
        const count = cards.length;

        // Latest end-date across the group's cards → range badge.
        let latestEndObj = null;
        if (dateObj) {
            cards.forEach((el) => {
                const endStr = (el.getAttribute('data-target-end-date') || '').trim();
                if (!endStr) return;
                const end = parseLocalDate(endStr);
                if (end && end > dateObj && (!latestEndObj || end > latestEndObj)) latestEndObj = end;
            });
        }
        let rangeBadge = '';
        if (latestEndObj) {
            const spanDays = Math.round((latestEndObj - dateObj) / 86400000) + 1;
            const showYear = latestEndObj.getFullYear() !== dateObj.getFullYear();
            const endLabel = `${MONTH_SHORT[latestEndObj.getMonth()]} ${latestEndObj.getDate()}${showYear ? ', ' + latestEndObj.getFullYear() : ''}`;
            rangeBadge = `<span class="date-header-range" title="At least one activity extends through ${esc(prettyDate(isoFromDate(latestEndObj)))}">&rarr; ${esc(endLabel)} (${spanDays}d)</span>`;
        }

        // Two spellings of the same date, picked by CSS rather than a resize
        // listener. On a phone the full year plus the word "activities" pushed
        // the kebab onto a second line, so the short forms carry the mobile
        // header: "Jun 17, 26" and a bare count.
        const yy = dateObj ? String(dateObj.getFullYear()).slice(-2) : '';
        const dateShort = dateObj
            ? `${MONTH_SHORT[dateObj.getMonth()]} ${dateObj.getDate()}, ${yy}`
            : '';
        // A multi-day group spelled as one range — "Sep 25–28, 26" — instead of
        // the start date plus an arrow badge repeating it. Same information in
        // roughly half the width, which is what kept pushing the kebab off the
        // line. The month is repeated only when the range crosses one.
        const rangeShort = (dateObj && latestEndObj)
            ? (latestEndObj.getMonth() === dateObj.getMonth() && latestEndObj.getFullYear() === dateObj.getFullYear()
                ? `${MONTH_SHORT[dateObj.getMonth()]} ${dateObj.getDate()}–${latestEndObj.getDate()}, ${yy}`
                : `${MONTH_SHORT[dateObj.getMonth()]} ${dateObj.getDate()} – ${MONTH_SHORT[latestEndObj.getMonth()]} ${latestEndObj.getDate()}, ${yy}`)
            : '';
        const headerDate = dateObj
            ? `<span class="date-header-day">${DAY_SHORT[dateObj.getDay()]}</span><span class="date-header-date${rangeShort ? ' has-range' : ''}"><span class="dh-long">${esc(prettyDate(dateKey))}</span><span class="dh-short">${esc(dateShort)}</span>${rangeShort ? `<span class="dh-rangeshort">${esc(rangeShort)}</span>` : ''}</span>${rangeBadge}`
            : '<span class="date-header-date">No date</span>';

        // A day note can be attachments alone — chips with no words are
        // still a note, and hiding the block would hide them with it. The
        // chips are built first so "has a note" can mean "has something to
        // SHOW": media that renders no chip must not leave a blank block.
        let noteMediaArr = [];
        try { noteMediaArr = JSON.parse(noteMediaJson || '[]') || []; } catch (_) { noteMediaArr = []; }
        const noteChipsHtml = noteMediaArr.length ? inlineAttachments(noteMediaArr) : '';
        const hasNote = !isNoDate && ((noteContent || '') !== '' || noteChipsHtml !== '');
        // Sharing a day is a read, so it stays live for everyone; the kebab
        // stays live too because the sheet behind it holds read-only rows and
        // gates its own actions. Everything else here writes.
        const buttons = isNoDate ? '' : `
            <button type="button" class="date-header-btn group-add-activity-btn${LOCK_EDIT_CLS}" data-date="${esc(dateKey)}"${LOCK_EDIT} title="${esc(editTitle('Add a new activity to this date'))}">${SVG.plus}</button>
            <span class="hidden md:flex items-center gap-0.5">
                <button type="button" class="date-header-btn date-note-btn${LOCK_NOTE_CLS}" data-date="${esc(dateKey)}"${LOCK_NOTE} title="${esc(noteTitle('Add a note to this day'))}">${SVG.notePlus}</button>
                <button type="button" class="date-header-btn day-expense-btn${LOCK_EDIT_CLS}" data-date="${esc(dateKey)}"${LOCK_EDIT} title="${esc(editTitle('Add an extra expense for this day'))}">${SVG.coin}</button>
                <button type="button" class="date-header-btn date-marker-btn${hasMarker ? ' has-marker' : ''}${LOCK_EDIT_CLS}" data-date="${esc(dateKey)}"${LOCK_EDIT} title="${esc(editTitle(hasMarker ? 'Edit the resume-here marker' : 'Drop a resume-here marker after this date'))}">${SVG.bookmark}</button>
                ${dateObj ? `<button type="button" class="date-header-btn share-day-btn" data-date="${esc(dateKey)}" title="Share this day's schedule (public link)">${SVG.share}</button>` : ''}
                <button type="button" class="date-header-btn change-group-date-btn${LOCK_EDIT_CLS}" data-date="${esc(dateKey)}"${LOCK_EDIT} title="${esc(editTitle('Change date for all activities in this group'))}">${SVG.calendarEdit}</button>
                <button type="button" class="date-header-btn move-group-das-btn${LOCK_EDIT_CLS}" data-date="${esc(dateKey)}"${LOCK_EDIT} title="${esc(editTitle('Move this whole day to a specific day number'))}">${SVG.dayNumber}</button>
                <button type="button" class="date-header-btn date-header-delete-btn delete-group-date-btn${LOCK_EDIT_CLS}" data-date="${esc(dateKey)}"${LOCK_EDIT} title="${esc(editTitle('Delete every activity in this group'))}">${SVG.trash}</button>
            </span>
            <button type="button" class="date-header-btn day-menu-btn md:hidden" data-date="${esc(dateKey)}" title="More actions for this day">${SVG.kebab}</button>`;

        // Chips sit BESIDE the inner, not in it — the phone clamps the inner
        // to one line and chips inside it were trimmed away with the words.
        // Twin of the date-note-block markup in activities.blade.php.
        const noteChips = noteChipsHtml ? `<div class="date-note-media">${noteChipsHtml}</div>` : '';
        const noteBlock = isNoDate ? ''
            : `<div class="date-note-block" data-date="${esc(dateKey)}" data-content="${esc(noteContent || '')}" data-media="${esc(JSON.stringify(noteMediaArr))}" title="Drag to place it between activities · click to edit"${hasNote ? '' : ' style="display:none;"'}><div class="date-note-inner rich-text">${noteContent || ''}</div>${noteChips}${DATE_NOTE_EDIT}${DATE_NOTE_DEL}</div>`;
        const expenseBlock = isNoDate ? ''
            : `<div class="day-expense-block" data-date="${esc(dateKey)}"></div>`
              + `<div class="day-income-block" data-date="${esc(dateKey)}" hidden></div>`;

        const wrap = document.createElement('div');
        wrap.innerHTML = `<div class="date-group date-color-${colorIdx}${allHidden ? ' all-hidden' : ''}${OPEN_DAYS.has(dateKey) ? '' : ' is-folded'}" data-date="${esc(dateKey)}">
            <div class="date-header"${dateObj ? ' draggable="true" title="Drag this header to move the whole day to another date"' : ''}>
                <svg class="date-chevron" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                ${headerDate}
                <span class="date-header-count">${count}<span class="dh-word"> ${count === 1 ? 'activity' : 'activities'}</span></span>
                ${buttons}
                <span class="date-header-stage" hidden title="What the crop is doing on this day"></span>
                <span class="date-header-cash" hidden></span>
            </div>
            <div class="date-body"><div class="date-body-inner">
                ${noteBlock}
                ${expenseBlock}
                <div class="date-activities" data-date="${esc(dateKey)}"></div>
            </div></div>
        </div>`;
        const el = wrap.firstElementChild;
        const exBlock = el.querySelector('.day-expense-block');
        if (exBlock && typeof renderExpenseBlock === 'function') renderExpenseBlock(exBlock);
        return el;
    }

    // Accordion: days start folded; the set of opened days persists per
    // schedule, so a reload shows the same days expanded.
    const OPEN_DAYS_KEY = 'openDays:' + @json($schedule->id);
    const OPEN_DAYS = new Set((() => {
        try { return JSON.parse(localStorage.getItem(OPEN_DAYS_KEY) || '[]'); } catch (_) { return []; }
    })());
    function saveOpenDays() {
        try { localStorage.setItem(OPEN_DAYS_KEY, JSON.stringify([...OPEN_DAYS])); } catch (_) { /* private mode */ }
    }
    // Apply the remembered state to the server-rendered board — without the
    // fold animation, so restoring doesn't play a wave of transitions on load.
    function restoreOpenDays() {
        const list = document.getElementById('activitiesList');
        if (!list) return;
        list.classList.add('no-fold-anim');
        $qsa('.date-group', list).forEach((g) => {
            g.classList.toggle('is-folded', !OPEN_DAYS.has((g.getAttribute('data-date') || '').trim()));
        });
        void list.offsetWidth;
        requestAnimationFrame(() => list.classList.remove('no-fold-anim'));
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', restoreOpenDays, { once: true });
    else restoreOpenDays();

    /* ---- What this viewer may not touch ---------------------------------
     * The two renderers above already draw their own controls locked, but the
     * board arrives server-rendered first and its DAY HEADER is built in
     * sm/activities.blade.php — a third place the rule would have to be
     * repeated and would eventually drift out of. So sweep the live list
     * instead: whatever a renderer already locked is skipped, and whatever
     * the page shipped gets the same treatment. */
    const WRITE_CONTROLS = [
        '.done-check', '.edit-activity-btn', '.duplicate-activity-btn', '.to-draft-activity-btn',
        '.delete-activity-btn', '.tag-activity-btn', '.hide-activity-toggle', '.add-note-activity-btn',
        '.group-add-activity-btn', '.rest-day-add-btn', '.day-expense-btn', '.date-marker-btn',
        '.change-group-date-btn', '.move-group-das-btn', '.delete-group-date-btn',
        // The resume-here marker's own pencil and bin. They carry .icon-btn so
        // they LOOKED locked already — but nothing disabled them, and the
        // handlers below reached markerSave/markerDelete with no check.
        '.progress-marker-edit-btn', '.progress-marker-delete-btn',
        // A reminder tick is a write like any other: it books the errand's
        // money. Not a button, but `disabled` greys a checkbox just as well.
        '.act-rem-row input[type=checkbox]',
    ].join(', ');
    // Writing on the day itself — which is exactly what "notes only" buys.
    // The inline notes belong here too: their ✕ and pencil write the same
    // rows the per-day note's do.
    const NOTE_CONTROLS = '.date-note-btn, .date-note-edit, .date-note-del, .inline-note-edit, .inline-note-del';
    function lockBoardControls() {
        if (CAN_EDIT && MAY_NOTE) return;
        const list = $id('activitiesList');
        if (!list) return;
        const lock = (sel, why) => $qsa(sel, list).forEach((el) => {
            if (el.disabled) return;   // a renderer already said this
            el.disabled = true;
            el.title = why;
            el.classList.add('is-locked');
        });
        if (!CAN_EDIT) lock(WRITE_CONTROLS, WHY_NO_EDIT);
        if (!MAY_NOTE) lock(NOTE_CONTROLS, WHY_NO_NOTE);
    }
    document.addEventListener('activities:rendered', lockBoardControls);
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', lockBoardControls, { once: true });
    else lockBoardControls();

    /* A note's body is words, not a button, so nothing above can grey it out —
       and clicking the words is the usual way into the editor. Without this the
       gate only catches the pencil: a viewer clicks the note, gets the whole
       editor, writes a paragraph, hits Save and meets the 403 there. Ask
       before the editor opens instead. */
    function mayWriteNotes() {
        if (MAY_NOTE) return true;
        toast(WHY_NO_NOTE, 'error');
        return false;
    }

    /* The same door for the plan itself. A drag is the one write with no button
       to grey out, so it has to be turned away where it lands. */
    function mayEditBoard() {
        if (CAN_EDIT) return true;
        toast(WHY_NO_EDIT, 'error');
        return false;
    }

    document.addEventListener('click', (e) => {
        const header = e.target.closest && e.target.closest('#activitiesList .date-header');
        if (!header || e.target.closest('.date-header-btn, .date-header-weather, .day-warn-btn, .date-header-cash')) return;
        const group = header.closest('.date-group');
        if (!group) return;
        const key = (group.getAttribute('data-date') || '').trim();
        if (group.classList.toggle('is-folded')) OPEN_DAYS.delete(key);
        else OPEN_DAYS.add(key);
        saveOpenDays();
    });

    function reorderAndRenumberActivitiesCore() {
        const list = $id('activitiesList');
        if (!list) return;
        const cards = $qsa('.activity-card[data-id]', list);

        if (cards.length === 0) {
            // Preserve marker rows even when the board is empty (orphans).
            const markerSnaps = snapshotMarkers(list);
            list.innerHTML = EMPTY_STATE_HTML;
            Object.keys(markerSnaps).forEach((k) => list.insertAdjacentHTML('beforeend', buildMarkerHtml(k, markerSnaps[k])));
            return;
        }

        // Group by date (sentinel key sorts last).
        const groups = {};
        cards.forEach((el) => {
            const key = (el.getAttribute('data-target-date') || '').trim() || '__no-date__';
            (groups[key] = groups[key] || []).push(el);
        });

        // Within a date: manual sequenceOrder → lot signature → id.
        Object.values(groups).forEach((arr) => arr.sort((a, b) => {
            const seqA = parseInt(a.getAttribute('data-sequence-order'), 10) || 0;
            const seqB = parseInt(b.getAttribute('data-sequence-order'), 10) || 0;
            if (seqA !== seqB) return seqA - seqB;
            const sa = a.getAttribute('data-lot-signature') || '';
            const sb = b.getAttribute('data-lot-signature') || '';
            if (sa !== sb) return sa.localeCompare(sb);
            return (parseInt(a.getAttribute('data-id'), 10) || 0) - (parseInt(b.getAttribute('data-id'), 10) || 0);
        }));

        // Covered-day set + overall span for rest-day interleaving.
        const covered = new Set();
        let firstDate = null;
        let lastDate = null;
        cards.forEach((el) => {
            const startStr = (el.getAttribute('data-target-date') || '').trim();
            if (!startStr) return;
            const endStr = (el.getAttribute('data-target-end-date') || '').trim() || startStr;
            const start = parseLocalDate(startStr);
            const end = parseLocalDate(endStr);
            if (!start || !end) return;
            const cur = new Date(start.getTime());
            while (cur <= end) {
                covered.add(isoFromDate(cur));
                cur.setDate(cur.getDate() + 1);
            }
            if (!firstDate || start < firstDate) firstDate = new Date(start.getTime());
            if (!lastDate || end > lastDate) lastDate = new Date(end.getTime());
        });

        const timeline = [];
        let colorCursor = 0;
        if (firstDate && lastDate) {
            const cur = new Date(firstDate.getTime());
            while (cur <= lastDate) {
                const key = isoFromDate(cur);
                if (groups[key]) {
                    timeline.push({ type: 'group', key, color: colorCursor });
                    colorCursor = (colorCursor + 1) % 8;
                } else if (!covered.has(key)) {
                    timeline.push({ type: 'rest', key });
                }
                cur.setDate(cur.getDate() + 1);
            }
        }
        if (groups['__no-date__']) timeline.push({ type: 'group', key: '__no-date__', color: 0 });

        // Snapshot notes + markers BEFORE the wipe.
        const notesByDate = {};
        $qsa('.date-note-block[data-date]', list).forEach((el) => {
            const key = (el.getAttribute('data-date') || '').trim();
            // The note's BODY, not the block's innerHTML — that also holds the
            // edit and delete buttons, and feeding them back in nested a copy
            // of the note inside itself and doubled its buttons every reorder.
            const content = (el.querySelector('.date-note-inner')?.innerHTML || '').trim();
            // Attachments count as content: a chips-only note that was kept
            // out of the snapshot vanished from the board on every reorder.
            const media = el.getAttribute('data-media') || '[]';
            let hasMedia = false;
            try { hasMedia = (JSON.parse(media) || []).length > 0; } catch (_) { hasMedia = false; }
            if (key && el.style.display !== 'none' && (content !== '' || hasMedia)) {
                notesByDate[key] = { html: content, media };
            }
        });
        // Inline notes sit BETWEEN the cards, so the wipe below takes them with
        // it — nothing rebuilt them, which is why a note vanished the moment
        // its day was reordered. Remember each one with the card it sits above,
        // so it can land back in the same slot whatever the new order is.
        const inlineByDate = {};
        $qsa('.inline-note', list).forEach((el) => {
            const key = (el.closest('.date-activities')?.getAttribute('data-date') || '').trim();
            if (!key) return;
            let anchor = el.nextElementSibling;
            while (anchor && !anchor.matches('.activity-card[data-id]')) anchor = anchor.nextElementSibling;
            (inlineByDate[key] = inlineByDate[key] || []).push({
                el, beforeId: anchor ? anchor.getAttribute('data-id') : null,
            });
        });
        const markersByDate = snapshotMarkers(list);

        // Wipe + rebuild.
        list.innerHTML = '';
        timeline.forEach((item) => {
            if (item.type === 'rest') {
                list.insertAdjacentHTML('beforeend', buildRestDayHtml(item.key, false));
                return;
            }
            const key = item.key;
            const groupCards = groups[key];
            const allHidden = key !== '__no-date__'
                && groupCards.length > 0
                && groupCards.every((el) => el.classList.contains('is-hidden'));
            if (allHidden) {
                list.insertAdjacentHTML('beforeend', buildRestDayHtml(key, true));
            }
            const markerInfo = key !== '__no-date__' ? (markersByDate[key] || null) : null;
            const noteInfo = notesByDate[key] || null;
            const groupEl = buildDateGroupShell(key, item.color, groupCards, noteInfo ? noteInfo.html : '', !!markerInfo, allHidden, noteInfo ? noteInfo.media : '[]');
            const holder = $qs('.date-activities', groupEl);
            groupCards.forEach((el) => holder.appendChild(el));
            // The day's inline notes go back between the cards they belonged
            // to; one whose card left the day lands at the end of it.
            (inlineByDate[key] || []).forEach((n) => {
                const anchor = n.beforeId ? $qs(`.activity-card[data-id="${n.beforeId}"]`, holder) : null;
                holder.insertBefore(n.el, anchor);
            });
            // A note's stored key is derived from its neighbours' order, so a
            // reorder can leave it pointing at the wrong slot — persist the new
            // one, or a reload would show the note somewhere else.
            if (/^\d{4}-\d{2}-\d{2}$/.test(key)) {
                (inlineByDate[key] || []).forEach((n) => {
                    if (!n.el.getAttribute('data-inline-note')) return;
                    const fresh = inlineNoteKey(n.el);
                    if (fresh === (parseInt(n.el.getAttribute('data-sort-key') || '0', 10) || 0)) return;
                    n.el.setAttribute('data-sort-key', String(fresh));
                    saveInlineNote(n.el, key, fresh);
                });
            }
            delete inlineByDate[key];
            list.appendChild(groupEl);
            if (markerInfo) {
                list.insertAdjacentHTML('beforeend', buildMarkerHtml(key, markerInfo));
                delete markersByDate[key];
            }
        });

        // Orphan markers (no matching group) at the bottom.
        Object.keys(markersByDate).forEach((k) => list.insertAdjacentHTML('beforeend', buildMarkerHtml(k, markersByDate[k])));
    }

    function snapshotMarkers(list) {
        const map = {};
        $qsa('.progress-marker[data-date]', list).forEach((el) => {
            const key = (el.getAttribute('data-date') || '').trim();
            if (!key) return;
            map[key] = {
                id: el.getAttribute('data-marker-id') || '',
                note: ($qs('.progress-marker-note', el)?.textContent || '').trim(),
            };
        });
        return map;
    }

    // FLIP: the rebuild reuses the same card nodes, so measuring each card's box
    // before and after lets us slide it from its old spot to the new one — a
    // moved activity glides into its date instead of snapping. Honors
    // reduced-motion. `mutate` is the DOM-rewriting rebuild.
    const REORDER_EASE = 'cubic-bezier(.22,1,.36,1)';
    function flipReorder(mutate) {
        const list = $id('activitiesList');
        const reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        if (!list || reduce) { mutate(); return; }

        // First: where every card sits right now.
        const before = new Map();
        $qsa('.activity-card[data-id]', list).forEach((el) => {
            before.set(el.getAttribute('data-id'), el.getBoundingClientRect());
        });

        mutate();   // Last: the reused nodes land in their new positions.

        // Invert: park each moved card back at its old spot, transition off.
        const moved = [];
        $qsa('.activity-card[data-id]', list).forEach((el) => {
            const prev = before.get(el.getAttribute('data-id'));
            if (!prev) return;   // brand-new card — leave its own enter effect
            const now = el.getBoundingClientRect();
            const dx = prev.left - now.left;
            const dy = prev.top - now.top;
            if (Math.abs(dx) < 1 && Math.abs(dy) < 1) return;
            el.style.transition = 'none';
            el.style.transform = `translate(${dx}px, ${dy}px)`;
            moved.push(el);
        });
        if (!moved.length) return;

        // Play: flush the inverted positions, then release them to ease home.
        void list.offsetWidth;
        moved.forEach((el) => {
            el.style.transition = `transform .28s ${REORDER_EASE}`;
            el.style.transform = '';
            el.addEventListener('transitionend', function done() {
                el.style.transition = '';
                el.removeEventListener('transitionend', done);
            }, { once: true });
        });
    }

    // Wrapped so the active filters re-apply after every rebuild. Pass
    // animate=true (drag drops) to slide cards to their new places via FLIP.
    function reorderAndRenumberActivities(animate) {
        if (animate) flipReorder(reorderAndRenumberActivitiesCore);
        else reorderAndRenumberActivitiesCore();
        refreshHiddenActivityCount();
        if (hasActiveFilters()) applyActivityFilter();
        else announceBoardChange();
        refreshDayWarnings();   // date groups changed → recompute per-day reminders
    }

    /* ---- Task types: several at once ---------------------------------
     * The order is the record: the first one picked leads, and leading means
     * colouring the card, answering the filters and deciding which tab the
     * task belongs to. The rest are what else went in the tank.
     *
     * Some things must not share one: copper burns leaves when it meets an
     * oil or an acidic partner, and a herbicide in a knapsack that later
     * sprays a crop is a mistake you only make once. Saying so here is
     * cheaper than saying it after the field turns. */
    const TT_SOLO = { copper_fungicide: 'Copper-based products', herbicide: 'Herbicide' };
    const TT_SOLO_WHY = {
        copper_fungicide: 'Copper burns leaves when it meets oils or acidic partners, and it puts most biologicals down. Spray it on its own.',
        herbicide: 'A herbicide should not share a tank with anything meant to help the crop — and the knapsack wants rinsing before it is used for anything else.',
    };

    let TASK_TYPES = [];   // slugs, in the order they were picked

    const taskTypes = () => TASK_TYPES.slice();

    function paintTaskTypes() {
        const tags = $qsa('#activityTypeTags .tt-tag');
        tags.forEach((t) => {
            const slug = t.getAttribute('data-type');
            const at = TASK_TYPES.indexOf(slug);
            t.classList.toggle('is-on', at >= 0);
            t.classList.toggle('is-lead', at === 0);
            t.setAttribute('aria-pressed', at >= 0 ? 'true' : 'false');
        });
        // The hidden select stays the primary type for everything downstream.
        const sel = $id('activityType');
        if (sel) sel.value = TASK_TYPES[0] || '';

        const warn = $id('activityTypeWarn');
        if (warn) {
            const clash = TASK_TYPES.length > 1 ? TASK_TYPES.find((t) => TT_SOLO[t]) : null;
            warn.hidden = !clash;
            if (clash) {
                warn.innerHTML = '<b>' + esc(TT_SOLO[clash]) + ' should go out alone.</b> ' + esc(TT_SOLO_WHY[clash]);
            }
        }
        const hint = $id('activityTypeHint');
        if (hint) {
            hint.textContent = TASK_TYPES.length > 1
                ? 'The first one leads: ' + (ACTIVITY_TYPE_LABELS[TASK_TYPES[0]] || TASK_TYPES[0]) + '. Tap it again to drop it.'
                : 'Pick one, or several if they go in the same tank.';
        }
    }

    function setTaskTypes(list) {
        const known = new Set($qsa('#activityTypeTags .tt-tag').map((t) => t.getAttribute('data-type')));
        TASK_TYPES = (list || []).filter((t) => t && known.has(t))
            .filter((t, i, a) => a.indexOf(t) === i);
        paintTaskTypes();
    }

    $id('activityTypeTags')?.addEventListener('click', (e) => {
        const tag = e.target.closest('.tt-tag');
        if (!tag) return;
        const slug = tag.getAttribute('data-type');
        const at = TASK_TYPES.indexOf(slug);
        if (at >= 0) TASK_TYPES.splice(at, 1);
        else TASK_TYPES.push(slug);
        paintTaskTypes();
    });

    /* ================================================================
     * DAY REMINDERS — advisory "double-check this" flags per date group.
     * Pure client-side: reads each card's date / type / lots (+ the weather
     * feed for rain), renders an amber pill in the date header, and lists the
     * reasons in a sheet where each can be marked as read (localStorage).
     * ================================================================ */
    // Chemical groupings by activityType slug.
    const WARN_SPRAYS_ALL   = new Set(['foliar_spray', 'herbicide', 'pesticide', 'copper_fungicide', 'fungicide']);
    const WARN_TODAY_STRONG = new Set(['herbicide', 'copper_fungicide']);                       // herbicide / bactericide today
    const WARN_PREV_SPRAY   = new Set(['herbicide', 'pesticide', 'fungicide', 'copper_fungicide']); // any spray yesterday (foliar is fine)
    const WARN_GRANULAR     = new Set(['fertilizer']);
    const WARN_RAIN_CODES   = new Set([51, 53, 55, 56, 57, 61, 63, 65, 66, 67, 80, 81, 82, 95, 96, 99]);
    const WARN_ICON = '<svg fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>';

    const WARN_READ_KEY = 'warnRead:' + @json($schedule->id);
    function loadWarnRead() {
        try { return new Set(JSON.parse(localStorage.getItem(WARN_READ_KEY) || '[]')); } catch (_) { return new Set(); }
    }
    function saveWarnRead(set) {
        try { localStorage.setItem(WARN_READ_KEY, JSON.stringify([...set])); } catch (_) { /* private mode */ }
    }

    // Per-lot, per-date rain forecast, filled in by the weather IIFE once its
    // fetch resolves. { lotId: { 'YYYY-MM-DD': dayObj } }.
    let WX_BY_LOT_DATE = {};
    function warnIsRainy(d) {
        if (!d) return false;
        if (d.code != null && WARN_RAIN_CODES.has(Number(d.code))) return true;
        return d.pop != null && Number(d.pop) >= 60;
    }
    const warnChem = (type) => (ACTIVITY_TYPE_LABELS[type] || type || 'spray');
    const warnLotName = (lotId) => (LOT_NAMES[lotId] || ('Lot #' + lotId));
    function warnTodayKey() {
        const n = new Date();
        return `${n.getFullYear()}-${String(n.getMonth() + 1).padStart(2, '0')}-${String(n.getDate()).padStart(2, '0')}`;
    }

    let DAY_WARNINGS = {};   // dateKey → [{ sig, ico, title, lots:[ids], detail }]
    function computeDayWarnings() {
        const out = {};
        const list = $id('activitiesList');
        if (!list) return out;

        // Index cards: date → lot → [{type,id,title}], and date → [acts].
        const byDateLot = {};   // byDateLot[date][lotId] = [{type,id,title}]
        const byDate = {};      // byDate[date] = [{type,id,title,lots:[ids]}]
        $qsa('.activity-card[data-id]', list).forEach((card) => {
            const date = (card.getAttribute('data-target-date') || '').trim();
            if (!date) return;
            const type = card.getAttribute('data-activity-type') || '';
            // A task can be several things at once; the rules ask about all of
            // them, not just the one that colours the card.
            const types = (card.getAttribute('data-activity-types') || type)
                .split(',').map((t) => t.trim()).filter(Boolean);
            const id = card.getAttribute('data-id');
            const title = ($qs('.activity-card-title', card)?.textContent || 'Activity').trim();
            const lots = $qsa('.activity-card-lothead .lot-tag[data-lot-id]', card)
                .map((t) => parseInt(t.getAttribute('data-lot-id'), 10)).filter(Boolean);
            (byDate[date] = byDate[date] || []).push({ type, types, id, title, lots });
            (byDateLot[date] = byDateLot[date] || {});
            lots.forEach((lid) => { (byDateLot[date][lid] = byDateLot[date][lid] || []).push({ type, types, id, title }); });
        });

        // "Is this task any of these?" — a task carrying a fungicide and an
        // insecticide answers yes to both.
        const actIs = (a, set) => (a.types || [a.type]).some((t) => set.has(t));
        const actTypes = (a) => (a.types && a.types.length ? a.types : [a.type]).filter(Boolean);

        const seen = new Set();
        const push = (date, w) => {
            if (seen.has(date + '|' + w.sig)) return;
            seen.add(date + '|' + w.sig);
            (out[date] = out[date] || []).push(w);
        };
        const today = warnTodayKey();

        Object.keys(byDate).forEach((date) => {
            const prev = isoFromDate(new Date(parseLocalDate(date).getTime() - 86400000));
            const lotsToday = byDateLot[date] || {};
            const lotsPrev = byDateLot[prev] || {};

            // What is in one tank. This is about the task itself rather than
            // the ground it lands on, so it is asked once per task and names
            // every lot that task covers.
            (byDate[date] || []).forEach((a) => {
                const mine = actTypes(a);
                if (mine.length < 2) return;
                const solo = mine.find((t) => t === 'copper_fungicide' || t === 'herbicide');
                if (!solo) return;
                const others = mine.filter((t) => t !== solo).map((t) => warnChem(t)).join(', ');
                const where = (a.lots || []).length
                    ? a.lots.map((l) => warnLotName(l)).join(', ')
                    : 'the whole schedule';
                push(date, {
                    sig: `tank|${date}|${a.id}`,
                    ico: '🧪',
                    title: warnChem(solo) + ' is mixed with something else',
                    lots: a.lots || [],
                    detail: `"${a.title}" on ${where} puts ${warnChem(solo)} in the same tank as ${others}. `
                        + (solo === 'copper_fungicide'
                            ? 'Copper burns leaves when it meets oils or acidic partners and knocks most biologicals out. Spray it on its own.'
                            : 'A herbicide should not share a tank with anything meant to help the crop, and the knapsack wants rinsing before its next job.'),
                });
            });

            Object.keys(lotsToday).forEach((lidStr) => {
                const lid = parseInt(lidStr, 10);
                const todays = lotsToday[lid];
                const prevs = lotsPrev[lid] || [];

                // Rule 2 — two+ activities on the same lot, same day.
                if (todays.length >= 2) {
                    const ids = todays.map((a) => a.id).sort().join(',');
                    push(date, {
                        sig: `sameday|${date}|${lid}|${ids}`,
                        ico: '🔁',
                        title: 'Two activities on the same lot today',
                        lots: [lid],
                        detail: `${warnLotName(lid)} has ${todays.length} activities scheduled today (${todays.map((a) => a.title).join(', ')}). Double-check they don't clash (e.g. spraying while irrigating) or aren't accidental duplicates.`,
                    });
                }

                // Rules that need yesterday on the same lot.
                if (prevs.length) {
                    // Rule 1 — herbicide/bactericide today after any spray yesterday.
                    const strongToday = todays.find((a) => actIs(a, WARN_TODAY_STRONG));
                    const sprayPrev = prevs.find((a) => actIs(a, WARN_PREV_SPRAY));
                    if (strongToday && sprayPrev) {
                        push(date, {
                            sig: `overload|${date}|${lid}|${strongToday.type}>${sprayPrev.type}`,
                            ico: '🧪',
                            title: 'Back-to-back chemical sprays',
                            lots: [lid],
                            detail: `${warnLotName(lid)} was sprayed with ${warnChem(sprayPrev.type)} yesterday, and today you're applying ${warnChem(strongToday.type)}. Strong sprays on consecutive days can overload the crop and cause chemical stress or leaf burn (phytotoxicity). Consider spacing them a few days apart.`,
                        });
                    }
                    // Rule 3 — granular fertilizer two days running.
                    const granToday = todays.find((a) => actIs(a, WARN_GRANULAR));
                    const granPrev = prevs.find((a) => actIs(a, WARN_GRANULAR));
                    if (granToday && granPrev) {
                        push(date, {
                            sig: `granular2|${date}|${lid}`,
                            ico: '🧂',
                            title: 'Granular fertilizer two days running',
                            lots: [lid],
                            detail: `Granular fertilizer was applied to ${warnLotName(lid)} yesterday and again today. Back-to-back granular applications risk over-fertilizing — nutrient burn or salt build-up. Confirm the rates are intentional.`,
                        });
                    }
                }
            });

            // Rule 4 — spraying before forecast rain (today + future only).
            if (date >= today) {
                byDate[date].forEach((a) => {
                    if (!actIs(a, WARN_SPRAYS_ALL)) return;
                    a.lots.forEach((lid) => {
                        const d = (WX_BY_LOT_DATE[lid] || {})[date];
                        if (!warnIsRainy(d)) return;
                        push(date, {
                            sig: `sprayrain|${date}|${lid}`,
                            ico: '🌧️',
                            title: 'Rain forecast on a spray day',
                            lots: [lid],
                            detail: `${d.emoji || '🌧️'} ${d.text || 'Rain'}${d.pop != null ? ` (${d.pop}% chance)` : ''} is forecast for ${warnLotName(lid)} on this day. Spraying ${warnChem(a.type)} right before rain can wash it off and waste the application — consider rescheduling to a drier day.`,
                        });
                    });
                });
            }
        });
        return out;
    }

    function refreshDayWarnings() {
        DAY_WARNINGS = computeDayWarnings();
        const read = loadWarnRead();
        $qsa('#activitiesList .date-group[data-date]').forEach((g) => {
            const date = (g.getAttribute('data-date') || '').trim();
            const header = $qs('.date-header', g);
            if (!header) return;
            const all = DAY_WARNINGS[date] || [];
            let btn = header.querySelector('.day-warn-btn');
            // No reminders at all → no pill. Once acknowledged the pill stays
            // (so it can be reopened) but goes quiet — only unread ones pulse.
            if (!all.length) { if (btn) btn.remove(); return; }
            const unread = all.filter((w) => !read.has(w.sig)).length;
            if (!btn) {
                btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'day-warn-btn';
                btn.setAttribute('data-warn-date', date);
                const count = header.querySelector('.date-header-count');
                if (count) count.insertAdjacentElement('beforebegin', btn);
                else header.appendChild(btn);
            }
            btn.classList.toggle('has-unread', unread > 0);
            btn.innerHTML = WARN_ICON + (unread > 1 ? `<span class="cnt">${unread}</span>` : '');
            btn.title = unread > 0
                ? (unread === 1 ? (all.find((w) => !read.has(w.sig)) || {}).title : `${unread} things to double-check on this day`)
                : 'All reminders reviewed — tap to see them again';
        });
    }

    // ---- Warning sheet: list ALL of a day's reminders, each with a read
    // checkbox. Read ones dim + strike through but stay visible so they can be
    // un-read; the header pill goes quiet once none remain unread.
    let warnSheetDate = null;
    function updateDayWarnMarkAll() {
        const markAll = $id('dayWarnMarkAll');
        if (!markAll || warnSheetDate == null) return;
        const read = loadWarnRead();
        const all = DAY_WARNINGS[warnSheetDate] || [];
        const unread = all.filter((w) => !read.has(w.sig)).length;
        markAll.style.display = all.length > 1 ? '' : 'none';
        markAll.disabled = unread === 0;
        markAll.textContent = unread === 0 ? 'All read' : 'Mark all as read';
    }
    function renderDayWarnSheet() {
        const body = $id('dayWarnBody');
        if (!body || warnSheetDate == null) return;
        const read = loadWarnRead();
        const all = DAY_WARNINGS[warnSheetDate] || [];
        const sub = $id('dayWarnSubtitle');
        if (sub) sub.textContent = prettyDate(warnSheetDate);
        if (!all.length) { closeSheet('dayWarnSheet'); return; }
        body.innerHTML = all.map((w) => {
            const isRead = read.has(w.sig);
            const lots = w.lots.map((lid) => `<span class="lot" style="background:hsl(${(lid * 137) % 360}, 55%, 40%)">${esc(warnLotName(lid))}</span>`).join('');
            return `<div class="warn-item${isRead ? ' is-read' : ''}" data-sig="${esc(w.sig)}">
                <div class="warn-item-ico" aria-hidden="true">${w.ico}</div>
                <div class="min-w-0 grow">
                    <p class="warn-item-title">${esc(w.title)}</p>
                    ${lots ? `<div class="warn-item-lots">${lots}</div>` : ''}
                    <p class="warn-item-detail">${esc(w.detail)}</p>
                    <label class="warn-read-check">
                        <input type="checkbox" data-toggle-read="${esc(w.sig)}"${isRead ? ' checked' : ''}>
                        <span>Notification is read</span>
                    </label>
                </div>
            </div>`;
        }).join('');
        updateDayWarnMarkAll();
    }
    document.addEventListener('click', (e) => {
        const openBtn = e.target.closest('.day-warn-btn');
        if (openBtn) {
            warnSheetDate = openBtn.getAttribute('data-warn-date');
            openSheet('dayWarnSheet');
            renderDayWarnSheet();
        }
    });
    // Per-notification read toggle — update state + row in place (no full re-render
    // so the checkbox doesn't lose focus), then refresh the header pill.
    document.addEventListener('change', (e) => {
        const cb = e.target.closest('[data-toggle-read]');
        if (!cb) return;
        const read = loadWarnRead();
        if (cb.checked) read.add(cb.getAttribute('data-toggle-read'));
        else read.delete(cb.getAttribute('data-toggle-read'));
        saveWarnRead(read);
        cb.closest('.warn-item')?.classList.toggle('is-read', cb.checked);
        refreshDayWarnings();
        updateDayWarnMarkAll();
    });
    $id('dayWarnMarkAll')?.addEventListener('click', () => {
        if (warnSheetDate == null) return;
        const read = loadWarnRead();
        (DAY_WARNINGS[warnSheetDate] || []).forEach((w) => read.add(w.sig));
        saveWarnRead(read);
        refreshDayWarnings();
        renderDayWarnSheet();   // re-render so every checkbox reflects "read"
    });

    function _renderCardOrReplace(activityData) {
        const list = $id('activitiesList');
        const html = renderActivityCard(activityData);
        const existing = $qs(`.activity-card[data-id="${activityData.id}"]`, list);
        if (existing) {
            existing.outerHTML = html;
        } else {
            $id('activitiesEmpty')?.remove();
            list.insertAdjacentHTML('beforeend', html);
        }
        reorderAndRenumberActivities();
        recomputeLotDayZero();
    }

    function _removeCardById(id) {
        const el = $qs(`#activitiesList .activity-card[data-id="${id}"]`);
        const finish = () => {
            el?.remove();
            reorderAndRenumberActivities();
            recomputeLotDayZero();
        };
        // Animate the card out so the change is visible rather than instant.
        if (el && window.animateOut) window.animateOut(el, finish);
        else finish();
    }

    function captureBoardSnapshot() {
        return $qsa('#activitiesList .activity-card[data-id]').map((el) => ({
            id: parseInt(el.getAttribute('data-id'), 10),
            targetDate: (el.getAttribute('data-target-date') || '').trim(),
            targetEndDate: (el.getAttribute('data-target-end-date') || '').trim() || null,
            sequenceOrder: parseInt(el.getAttribute('data-sequence-order'), 10) || 0,
        }));
    }

    async function restoreBoardSnapshot(snapshot) {
        const r = await api(U.reorder(), { method: 'POST', body: { items: snapshot } });
        if (!r || !r.success) throw new Error((r && r.message) || 'reorder failed');
        snapshot.forEach((it) => {
            const el = $qs(`#activitiesList .activity-card[data-id="${it.id}"]`);
            if (!el) return;
            el.setAttribute('data-target-date', it.targetDate || '');
            el.setAttribute('data-target-end-date', it.targetEndDate || '');
            el.setAttribute('data-sequence-order', it.sequenceOrder);
        });
        reorderAndRenumberActivities();
        recomputeLotDayZero();
    }

    /* ================================================================
     * 5. FILTERS — search, type chips, hide-lots chips, show-hidden
     * ================================================================ */

    // "Quick today & tomorrow" narrows the board to the next two days.
    let ttActive = false;
    function _ttDates() {
        const d = new Date();
        const iso = (x) => x.getFullYear() + '-' + String(x.getMonth() + 1).padStart(2, '0') + '-' + String(x.getDate()).padStart(2, '0');
        const tom = new Date(d.getFullYear(), d.getMonth(), d.getDate() + 1);
        return { today: iso(d), tomorrow: iso(tom) };
    }
    function _cardInTodayTomorrow(card) {
        const start = (card.getAttribute('data-target-date') || '').trim();
        if (!start) return false;
        const end = (card.getAttribute('data-target-end-date') || '').trim() || start;
        const { today, tomorrow } = _ttDates();
        return start <= tomorrow && end >= today;   // overlaps [today, tomorrow]
    }

    function hasActiveFilters() {
        const q = ($id('activitySearchInput')?.value || '').trim();
        const types = $id('typeFilterChips') ? chipValues($id('typeFilterChips')) : [];
        const lots = $id('lotFilterChips') ? chipValues($id('lotFilterChips')) : [];
        return q !== '' || types.length > 0 || lots.length > 0 || ttActive;
    }

    function _cardAllLotsHidden(card, hiddenLotIds) {
        const sig = String(card.getAttribute('data-lot-signature') || '').trim();
        const lotIds = sig ? sig.split(',').filter(Boolean) : [];
        if (lotIds.length === 0) return hiddenLotIds.includes('__na__');
        return lotIds.every((id) => hiddenLotIds.includes(id));
    }

    function applyActivityFilter() {
        const list = $id('activitiesList');
        if (!list) return;
        const raw = ($id('activitySearchInput')?.value || '').trim().toLowerCase();
        const needle = raw.replace(/\s+/g, ' ');
        const activeTypes = $id('typeFilterChips') ? chipValues($id('typeFilterChips')) : [];
        const hiddenLotIds = $id('lotFilterChips') ? chipValues($id('lotFilterChips')) : [];
        const hasType = activeTypes.length > 0;
        const hasLots = hiddenLotIds.length > 0;
        const cards = $qsa('.activity-card[data-id]', list);

        $id('lotFilterClearBtn')?.classList.toggle('hidden', !hasLots);

        if (!needle && !hasType && !hasLots && !ttActive) {
            cards.forEach((c) => c.classList.remove('filter-hidden'));
            $qsa('.date-group', list).forEach((g) => {
                g.classList.remove('group-collapsed');
                // Clearing filters restores each day's own accordion state.
                g.classList.toggle('is-folded', !OPEN_DAYS.has((g.getAttribute('data-date') || '').trim()));
            });
            $qsa('.rest-day-marker', list).forEach((r) => r.classList.remove('filters-active'));
            const count = $id('activitySearchCount');
            if (count) count.textContent = '';
            announceBoardChange();   // clearing filters is a change too
            return;
        }

        let visible = 0;
        cards.forEach((card) => {
            const text = ((card.getAttribute('data-search') || '') + ' ' + card.textContent.toLowerCase()).replace(/\s+/g, ' ');
            const cardType = String(card.getAttribute('data-activity-type') || '');
            const matches = (!needle || text.includes(needle))
                && (!hasType || activeTypes.includes(cardType))
                && (!ttActive || _cardInTodayTomorrow(card))
                && (!hasLots || !_cardAllLotsHidden(card, hiddenLotIds));
            card.classList.toggle('filter-hidden', !matches);
            if (matches) visible++;
        });

        // Collapse date groups whose every card is filtered out; hide rest days.
        // Days with matches unfold so the results are actually visible.
        $qsa('.date-group', list).forEach((g) => {
            const hasVisible = $qsa('.activity-card[data-id]:not(.filter-hidden)', g).length > 0;
            g.classList.toggle('group-collapsed', !hasVisible);
            if (hasVisible) g.classList.remove('is-folded');
        });
        $qsa('.rest-day-marker', list).forEach((r) => r.classList.add('filters-active'));

        const count = $id('activitySearchCount');
        if (count) count.textContent = `${visible} shown`;
        announceBoardChange();
    }

    /**
     * The calendar view mirrors whatever the list is currently showing, so it
     * redraws whenever the board is rebuilt or refiltered. Coalesced to one
     * notification per frame — a drag can trigger several in a row.
     */
    let boardChangeQueued = false;
    function announceBoardChange() {
        if (boardChangeQueued) return;
        boardChangeQueued = true;
        requestAnimationFrame(() => {
            boardChangeQueued = false;
            document.dispatchEvent(new CustomEvent('activities:rendered'));
        });
    }

    let searchTimer = null;
    $id('activitySearchInput')?.addEventListener('input', () => {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(applyActivityFilter, 80);
    });

    document.addEventListener('chips:change', (e) => {
        const group = e.target;
        if (!group || !group.id) return;
        if (group.id === 'typeFilterChips' || group.id === 'lotFilterChips') applyActivityFilter();
        if (group.id === 'laborGroupsContainer' || group.id === 'laborWorkersContainer') updateLaborFilterHint();
    });

    $id('lotFilterAllBtn')?.addEventListener('click', () => {
        $qsa('#lotFilterChips .chip').forEach((c) => c.classList.add('is-selected'));
        applyActivityFilter();
    });
    $id('lotFilterClearBtn')?.addEventListener('click', () => {
        $qsa('#lotFilterChips .chip').forEach((c) => c.classList.remove('is-selected'));
        applyActivityFilter();
    });

    // ---- Show-hidden toggle (persisted per schedule) ----
    const HIDDEN_TOGGLE_KEY = 'showHiddenActivities:' + SCHEDULE_ID;

    function refreshHiddenActivityCount() {
        const n = $qsa('#activitiesList .activity-card.is-hidden').length;
        const btn = $id('toggleHiddenBtn');
        if (!btn) return;
        const showing = document.body.classList.contains('show-hidden-activities');
        $id('toggleHiddenLabel').textContent = (showing ? 'Hide Hidden' : 'Show Hidden') + ' (' + n + ')';
        if (window.animToggleHidden) window.animToggleHidden(btn, n === 0);
        else btn.classList.toggle('hidden', n === 0);
        if (n === 0 && showing) {
            document.body.classList.remove('show-hidden-activities');
            localStorage.setItem(HIDDEN_TOGGLE_KEY, '0');
        }
    }

    $id('toggleHiddenBtn')?.addEventListener('click', () => {
        const next = !document.body.classList.contains('show-hidden-activities');
        document.body.classList.toggle('show-hidden-activities', next);
        localStorage.setItem(HIDDEN_TOGGLE_KEY, next ? '1' : '0');
        refreshHiddenActivityCount();
    });

    if (localStorage.getItem(HIDDEN_TOGGLE_KEY) === '1') {
        document.body.classList.add('show-hidden-activities');
    }
    refreshHiddenActivityCount();

    /* ================================================================
     * 6. ADD / EDIT ACTIVITY SHEET
     * ================================================================ */

    /* ---- Lot chips --------------------------------------------------------
     * Nothing picked means everywhere, which is what the label now says. A
     * chip that said the same thing was a second way to answer one question,
     * and it could disagree with the first. */
    $id('activityLotsContainer')?.addEventListener('click', (e) => {
        const chip = e.target.closest('.lot-chip');
        if (!chip) return;
        chip.classList.toggle('is-selected');
        chip.setAttribute('aria-pressed', chip.classList.contains('is-selected') ? 'true' : 'false');
        refreshActivityModalLotState();
    });

    function setActivityLots(lotIds) {
        const ids = (lotIds || []).map(Number);
        $qsa('#activityLotsContainer .lot-chip').forEach((c) => {
            const on = ids.includes(parseInt(c.getAttribute('data-lot-id'), 10));
            c.classList.toggle('is-selected', on);
            c.setAttribute('aria-pressed', on ? 'true' : 'false');
        });
    }

    function getActivityLotIds() {
        return $qsa('#activityLotsContainer .lot-chip.is-selected')
            .map((c) => parseInt(c.getAttribute('data-lot-id'), 10))
            .filter((n) => !Number.isNaN(n));
    }

    /* ---- The date pill ----------------------------------------------------
     * One place paints it, and every write to the field goes through
     * setTargetDate so the words never fall behind the value — including the
     * writes the DAS pane makes when you type a day number instead. */
    /** Any date pill: the words follow the value it holds. */
    function paintPill(pill, text, value) {
        if (!pill || !text) return;
        const v = (value || '').trim();
        // "January 25, 2026" — the day name is on the board already, and
        // this is a field, not a sentence.
        const d = parseLocalDate(v);
        text.textContent = d ? (MONTH_LONG[d.getMonth()] + ' ' + d.getDate() + ', ' + d.getFullYear()) : 'Pick a date';
        pill.classList.toggle('is-empty', !v);
    }
    function paintDateField() {
        paintPill($id('activityDateField'), $id('activityTargetDateText'), $id('activityTargetDate')?.value);
    }
    function setTargetDate(v) {
        const input = $id('activityTargetDate');
        if (input) input.value = v || '';
        paintDateField();
    }
    $id('activityTargetDate')?.addEventListener('change', paintDateField);
    $id('activityTargetDate')?.addEventListener('input', paintDateField);

    // ---- Worker chips ----
    /** The day this activity is for, as the chips need to know it. */
    function sheetWorkDate() {
        return ($id('activityTargetDate')?.value || '').trim().slice(0, 10);
    }
    /**
     * Grey out whoever is down as off that day, and put a red "forced" on
     * anyone picked anyway. Called whenever the sheet opens or the date moves,
     * because a worker free on Tuesday may be off on Wednesday.
     */
    function markWorkerAvailability() {
        const dateStr = sheetWorkDate();
        $qsa('#activityWorkersContainer .worker-chip[data-worker-id]').forEach((chip) => {
            const id = parseInt(chip.getAttribute('data-worker-id'), 10);
            const off = workerOffOn(id, dateStr);
            const picked = chip.classList.contains('is-selected');
            chip.classList.toggle('is-off', off && !picked);
            // Picked and off means someone said yes to the question below —
            // or the date moved onto a rest day after the fact. Either way the
            // name carries the flag rather than quietly disappearing.
            chip.classList.toggle('is-forced', off && picked);
            let tag = chip.querySelector('.chip-forced');
            if (off && picked && !tag) {
                tag = document.createElement('span');
                tag.className = 'chip-forced';
                tag.textContent = 'forced';
                chip.appendChild(tag);
            } else if ((!off || !picked) && tag) {
                tag.remove();
            }
            chip.title = off
                ? (picked ? 'Marked off this day — working anyway' : 'Marked off this day')
                : '';
        });
    }
    $id('activityTargetDate')?.addEventListener('change', markWorkerAvailability);
    $id('activityStartDas')?.addEventListener('change', () => setTimeout(markWorkerAvailability, 0));

    $id('activityWorkersContainer')?.addEventListener('click', async (e) => {
        const chip = e.target.closest('.worker-chip');
        if (!chip) return;

        // A worker down as off that day is not simply unavailable — the farm
        // may still need them. So the chip refuses the ordinary tap and asks
        // first, and a yes leaves the reason visible on the name.
        if (chip.hasAttribute('data-worker-id')
            && !chip.classList.contains('is-selected')
            && workerOffOn(parseInt(chip.getAttribute('data-worker-id'), 10), sheetWorkDate())) {
            const name = chip.textContent.trim();
            const when = prettyDateFull(sheetWorkDate()) || 'that day';
            const ok = await confirmAction({
                title: 'Force ' + name + ' to work?',
                message: name + ' is marked off on ' + when + '. Add them anyway?',
                confirmText: 'Force',
            });
            if (!ok) return;
        }

        chip.classList.toggle('is-selected');
        chip.setAttribute('aria-pressed', chip.classList.contains('is-selected') ? 'true' : 'false');
        markWorkerAvailability();
        renderWorkerPay();
    });

    function setActivityWorkers(workerIds) {
        const ids = (workerIds || []).map(Number);
        $qsa('#activityWorkersContainer .worker-chip').forEach((c) => {
            const on = ids.includes(parseInt(c.getAttribute('data-worker-id'), 10));
            c.classList.toggle('is-selected', on);
            c.setAttribute('aria-pressed', on ? 'true' : 'false');
        });
        markWorkerAvailability();
        renderWorkerPay();
    }

    function getActivityWorkerIds() {
        return $qsa('#activityWorkersContainer .worker-chip.is-selected')
            .map((c) => parseInt(c.getAttribute('data-worker-id'), 10))
            .filter((n) => !Number.isNaN(n));
    }

    /* ---- Worker checklist -------------------------------------------------
     * Picking a worker says they were there; this says for how much of the day
     * and at what price. Blank means the usual arrangement — a whole day at
     * the worker's own rate — so the common case needs no filling in. */
    let workerPayState = {};
    // No answer in the checklist means "as long as the task" — the same rule
    // the server applies, so the figure in the sheet matches the one saved.
    function inheritedPart() {
        const v = $id('activityTimeRequired')?.value;
        // "N/A" has no length to inherit, so nothing is assumed — the same
        // answer the server gives.
        return (v === 'whole' || v === 'half') ? v : null;
    }
    function defaultPay(id, dayPart) {
        const half = Number(WORKER_RATES[id] || 0);
        // On a checklist, being there is a day — half and whole are a task's
        // idea, not attendance's.
        if (activityMode === 'payroll') return half * 2;
        const part = dayPart || inheritedPart();
        if (!part) return 0;
        return part === 'half' ? half : half * 2;
    }
    function payFor(id) {
        const st = workerPayState[id] || {};
        const amount = st.amount;
        return (amount === null || amount === undefined || amount === '')
            ? defaultPay(id, st.dayPart)
            : Number(amount) || 0;
    }
    /**
     * The checklist.
     *
     * On a payroll activity this is the whole form: every worker on the
     * schedule with a box to tick for "worked today". Ticking one puts them on
     * the activity and their pay into the day's cost; leaving it clear costs
     * nothing. The half/whole choice and the agreed amount belong to a ticked
     * worker only — offering them next to an unticked name reads as though
     * that person is being paid to stay home.
     *
     * For every other kind of activity the panel lists whoever is already on
     * it, because there the crew is picked with the chips above.
     */
    /* The roster questions belong to a roster. An empty task has nobody to
       tick, and a payroll day keeps its list whatever anyone says — so the
       first question hides there and only the second is worth asking. */
    function paintChecklistPane() {
        const pane = $id('workerChecklistPane');
        if (!pane) return;
        const anyone = getActivityWorkerIds().length > 0;
        const payroll = activityMode === 'payroll';
        pane.classList.toggle('hidden', !anyone);
        $id('actWorkerChecklist')?.closest('label')?.classList.toggle('hidden', payroll);
    }

    function renderWorkerPay() {
        const panel = $id('workerPayPanel'), rows = $id('workerPayRows');
        if (!panel || !rows) return;

        const payroll = activityMode === 'payroll';
        paintChecklistPane();

        // Only a payroll day is priced per worker. On a task, an irrigation or
        // a service the crew is already chosen with the chips, and each of
        // them earns their own rate for however long the task takes — so a
        // second panel asking the same question in different words is just a
        // way to disagree with yourself.
        if (!payroll) {
            panel.classList.add('hidden');
            rows.innerHTML = '';
            paintWorkerCount();
            return;
        }

        const on = new Set(getActivityWorkerIds().map(String));
        const listed = Object.keys(WORKER_NAMES);
        paintChecklistPane();

        panel.classList.toggle('hidden', listed.length === 0);
        if (!listed.length) { $id('workerPayTotal').textContent = money(0); paintWorkerCount(); return; }

        rows.innerHTML = listed.map((id) => {
            const st = workerPayState[id] || {};
            const part = st.dayPart || inheritedPart();   // null when N/A
            const ticked = on.has(String(id));
            const rate = money(defaultPay(id, part));
            return `<div class="wp-row${ticked ? ' is-on' : ''}" data-pay-row="${id}">
                ${payroll ? `<label class="wp-tick">
                    <input type="checkbox" data-pay-on ${ticked ? 'checked' : ''}>
                    <span></span>
                </label>` : ''}
                <span class="wp-name">${esc(WORKER_NAMES[id] || 'Worker')}${!ticked ? `<span class="wp-rate">${esc(rate)} if they worked</span>` : ''}</span>
                ${ticked ? `<input type="number" class="form-input wp-amount text-right" data-pay-amount min="0" step="any" inputmode="decimal"
                    value="${st.amount === null || st.amount === undefined ? '' : esc(st.amount)}" placeholder="${defaultPay(id, part).toFixed(2)}">` : ''}
            </div>`;
        }).join('');

        $id('workerPayTotal').textContent = money([...on].reduce((t, id) => t + payFor(id), 0));
        paintWorkerCount();
    }

    /* Ticking a name is the same act as picking that worker — the chips above
       stay the one record of who is on the job, so nothing can disagree. */
    $id('workerPayRows')?.addEventListener('change', (e) => {
        const box = e.target.closest('[data-pay-on]');
        if (!box) return;
        const id = box.closest('[data-pay-row]').getAttribute('data-pay-row');
        const chip = $qs(`#activityWorkersContainer .worker-chip[data-worker-id="${id}"]`);
        if (chip) {
            chip.classList.toggle('is-selected', box.checked);
            chip.setAttribute('aria-pressed', box.checked ? 'true' : 'false');
        }
        renderWorkerPay();
    });
    $id('workerPayRows')?.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-pay-part]');
        if (!btn) return;
        const id = btn.closest('[data-pay-row]').getAttribute('data-pay-row');
        workerPayState[id] = Object.assign({}, workerPayState[id], { dayPart: btn.getAttribute('data-pay-part') });
        renderWorkerPay();
    });
    $id('workerPayRows')?.addEventListener('input', (e) => {
        const input = e.target.closest('[data-pay-amount]');
        if (!input) return;
        const id = input.closest('[data-pay-row]').getAttribute('data-pay-row');
        workerPayState[id] = Object.assign({}, workerPayState[id], { amount: input.value === '' ? null : input.value });
        $id('workerPayTotal').textContent = money(getActivityWorkerIds().reduce((t, w) => t + payFor(w), 0));
    });
    /* ---- The sheet's two panes ---- */
    function setActPane(which) {
        const host = $qs('#activitySheet .sheet-body > .space-y-4');
        if (!host) return;
        const onWorkers = which === 'workers';
        host.classList.toggle('on-workers', onWorkers);
        // The sheet scrolls; switching pane while halfway down a long form
        // would drop you into the middle of the other one.
        const body = $qs('#activitySheet .sheet-body');
        if (body) body.scrollTop = 0;
    }
    // Re-price anyone still following the task's own length when that changes.
    $id('activityTimeRequired')?.addEventListener('change', () => renderWorkerPay());

    function paintWorkerCount() {
        const badge = $id('activityWorkerCount');
        if (!badge) return;
        const n = getActivityWorkerIds().length;
        badge.textContent = n;
        badge.hidden = n === 0;
    }

    function setWorkerPay(pay) {
        workerPayState = {};
        Object.entries(pay || {}).forEach(([id, v]) => {
            // Keep "nothing chosen" as nothing. Reading it as a choice is what
            // froze a worker at half a day on a task that later became whole.
            const part = (v.dayPart === 'half' || v.dayPart === 'whole') ? v.dayPart : null;
            workerPayState[id] = { dayPart: part, amount: v.amount ?? null };
        });
        renderWorkerPay();
    }
    function getWorkerPay() {
        const out = {};
        getActivityWorkerIds().forEach((id) => {
            const st = workerPayState[id] || {};
            // Only send a choice that was actually made; silence means the
            // activity's own length decides.
            out[id] = { dayPart: st.dayPart || null, amount: (st.amount === '' ? null : st.amount) ?? null };
        });
        return out;
    }

    // ---- Day 0 toggle visibility ----
    // Show unless a selected lot is already anchored by ANOTHER source
    // (a different activity or a manual lot date).
    function shouldShowDayZeroToggle() {
        const currentId = parseInt($id('activityId').value, 10);
        const hasCurrentId = !isNaN(currentId) && currentId > 0;
        const selected = getActivityLotIds();
        if (selected.length === 0) return true;
        for (const lotId of selected) {
            const src = LOT_DAY_ZERO_SOURCE[lotId];
            if (!src) continue;
            if (hasCurrentId && src === currentId) continue;
            return false;
        }
        return true;
    }

    // ---- Transplant (DAT 0) toggle visibility ----
    // A distinct, later anchor. Hidden when it would make no sense to offer it:
    //   • a DAT 0 is already set for a selected lot by ANOTHER source — to move it,
    //     the user unchecks the existing transplant activity first; or
    //   • the lot is already deep in DAS (>= 40) at this activity's date, so the
    //     transplant window has clearly passed.
    const TRANSPLANT_DAS_CUTOFF = 40;
    function shouldShowTransplantToggle() {
        const currentId = parseInt($id('activityId').value, 10);
        const hasCurrentId = !isNaN(currentId) && currentId > 0;
        const selected = getActivityLotIds();
        if (selected.length === 0) return true;
        // Transplant (DAT) only applies to DAS/DAT lots — never DAP-only.
        if (selected.every((id) => lotDayType(id) === 'DAP')) return false;
        const start = parseLocalDate(($id('activityTargetDate').value || '').trim());
        for (const lotId of selected) {
            const tSrc = LOT_TRANSPLANT_SOURCE[lotId];
            if (tSrc && !(hasCurrentId && tSrc === currentId)) return false;
            const anchor = LOT_DAY_ZERO_DATES[lotId];
            if (anchor && start) {
                const a = parseLocalDate(anchor);
                if (a && Math.round((start - a) / 86400000) >= TRANSPLANT_DAS_CUTOFF) return false;
            }
        }
        return true;
    }

    function refreshDayZeroToggleVisibility() {
        const panel = $id('activityDayZeroPanel');
        if (!panel) return;
        // Day 0 applies to task activities, hidden once a selected lot is already
        // anchored by another activity/manual date (only one Day 0 per lot).
        if (activityMode === 'task' && shouldShowDayZeroToggle()) {
            panel.classList.remove('hidden');
        } else {
            $id('activityIsDayZero').checked = false;
            panel.classList.add('hidden');
        }
        const tpPanel = $id('activityTransplantPanel');
        if (tpPanel) {
            if (activityMode === 'task' && shouldShowTransplantToggle()) {
                tpPanel.classList.remove('hidden');
            } else {
                $id('activityIsTransplant').checked = false;
                tpPanel.classList.add('hidden');
            }
        }
    }

    // ---- Date ↔ DAS chooser (inner tabs above the date fields) ----
    let WHEN_MODE = 'date';
    function setWhenTab(mode, opts = {}) {
        WHEN_MODE = mode;
        const tabs = { date: $id('whenTabDate'), das: $id('whenTabDas') };
        const panes = { date: $id('whenPaneDate'), das: $id('activityDasRow') };
        if (!tabs.date || !panes.date) return;
        Object.keys(tabs).forEach((k) => {
            tabs[k]?.classList.toggle('is-active', k === mode);
            tabs[k]?.setAttribute('aria-selected', k === mode ? 'true' : 'false');
            panes[k]?.classList.toggle('hidden', k !== mode);
        });
        if (!opts.instant) {
            const pane = panes[mode];
            pane.classList.remove('when-pane-in');
            void pane.offsetWidth;
            pane.classList.add('when-pane-in');
        }
    }
    $id('whenTabDate')?.addEventListener('click', () => { if (WHEN_MODE !== 'date') setWhenTab('date'); });
    $id('whenTabDas')?.addEventListener('click', () => {
        if (WHEN_MODE === 'das') return;
        setWhenTab('das');
        refreshActivityDasRow();
    });

    // ---- DAS day-number lens over the date inputs ----
    function _activityDasAnchor() {
        const lotId = parseInt($id('activityDasRefLot')?.value, 10);
        if (!lotId) return null;
        return LOT_DAY_ZERO_DATES[lotId] || null;
    }

    function _dasToDateStr(das, anchorStr) {
        const a = parseLocalDate(anchorStr);
        const n = parseInt(das, 10);
        if (!a || isNaN(n)) return '';
        return isoFromDate(new Date(a.getFullYear(), a.getMonth(), a.getDate() + n));
    }

    function _dateStrToDas(dateStr, anchorStr) {
        const a = parseLocalDate(anchorStr);
        const b = parseLocalDate(dateStr);
        if (!a || !b) return '';
        return Math.round((b - a) / 86400000);
    }

    function syncActivityDasFromDates() {
        const anchor = _activityDasAnchor();
        if (!anchor) return;
        $id('activityStartDas').value = _dateStrToDas($id('activityTargetDate').value, anchor);
        $id('activityEndDas').value = _dateStrToDas($id('activityTargetEndDate').value, anchor);
        updateActivityDasNote();
    }

    // The date fields live on the other tab now, so echo the resolved dates here.
    function updateActivityDasNote() {
        const anchor = _activityDasAnchor();
        if (!anchor) return;
        const dt = dayType();
        const sel = $id('activityDasRefLot');
        const lotName = sel.options[sel.selectedIndex]?.textContent || '';
        const s = $id('activityTargetDate').value;
        const e = $id('activityTargetEndDate').value;
        const dates = s
            ? ` Start = <strong>${esc(prettyDate(s))}</strong>${e ? ` · End = <strong>${esc(prettyDate(e))}</strong>` : ''} — the date is what gets saved.`
            : '';
        $id('activityDasAnchorNote').innerHTML =
            `<strong>${esc(dt)} 0</strong> for <strong>${esc(lotName)}</strong> = ${esc(prettyDate(anchor))}.${dates}`;
    }

    function refreshActivityDasRow() {
        const row = $id('activityDasRow');
        if (!row) return;
        const currentId = parseInt($id('activityId').value, 10);
        const hasCurrentId = !isNaN(currentId) && currentId > 0;

        const anchored = (lotId) => !!LOT_DAY_ZERO_DATES[lotId]
            && !(hasCurrentId && LOT_DAY_ZERO_SOURCE[lotId] === currentId);
        let candidates = getActivityLotIds().filter(anchored);
        // No anchored lot selected (common for irrigation/service on N/A):
        // fall back to any anchored lot on the schedule as the reference.
        if (candidates.length === 0) {
            candidates = Object.keys(LOT_DAY_ZERO_DATES).map(Number).filter(anchored);
        }

        const dasTab = $id('whenTabDas');
        if ($id('activityIsDayZero').checked) {
            // A Day-0 activity IS the anchor — day-number planning doesn't apply.
            $id('activityStartDas').value = '';
            $id('activityEndDas').value = '';
            if (dasTab) { dasTab.disabled = true; dasTab.title = 'Not available for a ' + dayType() + ' 0 activity'; }
            if (WHEN_MODE === 'das') setWhenTab('date');
            row.classList.add('hidden');
            return;
        }
        if (dasTab) { dasTab.disabled = false; dasTab.title = ''; }

        // The lens is always offered; without any anchor it locks with a hint.
        row.classList.toggle('hidden', WHEN_MODE !== 'das');
        const locked = candidates.length === 0;
        row.classList.toggle('das-locked', locked);
        ['activityDasRefLot', 'activityStartDas', 'activityEndDas'].forEach((i) => {
            const el = $id(i);
            if (el) el.disabled = locked;
        });
        if (locked) {
            const dt = dayType();
            $id('activityDasRefLot').innerHTML = '';
            $id('activityStartDas').value = '';
            $id('activityEndDas').value = '';
            $id('activityDasAnchorNote').innerHTML =
                `<strong>No ${esc(dt)} 0 set yet.</strong> Mark a planting/sowing activity as ${esc(dt)} 0 `
                + `(or give a lot its Day-0 date in Lots) — then you can plan by ${esc(dt)} number here.`;
            return;
        }

        const sel = $id('activityDasRefLot');
        const prev = parseInt(sel.value, 10);
        sel.innerHTML = '';
        candidates.forEach((lotId) => {
            const opt = document.createElement('option');
            opt.value = lotId;
            opt.textContent = LOT_NAMES[lotId] || ('Lot #' + lotId);
            sel.appendChild(opt);
        });
        if (candidates.includes(prev)) sel.value = prev;
        syncActivityDasFromDates();
    }

    function refreshActivityModalLotState() {
        refreshDayZeroToggleVisibility();   // may force-uncheck Day 0…
        refreshActivityDasRow();            // …which this then reads
    }

    $id('activityStartDas')?.addEventListener('input', function () {
        const anchor = _activityDasAnchor();
        if (!anchor || this.value === '') return;
        setTargetDate(_dasToDateStr(this.value, anchor));
        updateActivityDasNote();
    });
    $id('activityEndDas')?.addEventListener('input', function () {
        const anchor = _activityDasAnchor();
        if (!anchor) return;
        if (this.value === '') {
            $id('activityTargetEndDate').value = '';
            updateActivityDasNote();
            return;
        }
        $id('activityTargetEndDate').value = _dasToDateStr(this.value, anchor);
        updateActivityDasNote();
    });
    $id('activityTargetDate')?.addEventListener('change', syncActivityDasFromDates);
    $id('activityTargetEndDate')?.addEventListener('change', syncActivityDasFromDates);
    $id('activityDasRefLot')?.addEventListener('change', syncActivityDasFromDates);
    $id('activityIsDayZero')?.addEventListener('change', refreshActivityDasRow);
    // Start date feeds the transplant cutoff (>= 40 DAS hides the DAT 0 toggle).
    $id('activityTargetDate')?.addEventListener('change', refreshDayZeroToggleVisibility);

    // ---- Calendar-only date fields (.cal-only): open the native picker on
    // click, block manual typing. Bound once at the document level. ----
    if (!window.__calOnlyBound) {
        window.__calOnlyBound = true;
        document.addEventListener('click', (ev) => {
            const inp = ev.target.closest && ev.target.closest('input.cal-only[type="date"]');
            if (inp && typeof inp.showPicker === 'function') { try { inp.showPicker(); } catch (_) {} }
        });
        document.addEventListener('keydown', (ev) => {
            const inp = ev.target.closest && ev.target.closest('input.cal-only[type="date"]');
            if (inp && !['Tab', 'Escape', 'Enter'].includes(ev.key)) ev.preventDefault();
        });
    }

    // ---- Task / Irrigation mode tabs ----
    // Each mode reveals only the fields that apply:
    //   task       → activity type, materials/items, reference images, Day 0
    //   irrigation → water task, materials only (no images, no Day 0)
    //   service    → service price + lot (no type/items/images/Day 0)
    function animateField(el) {
        if (!el || el.classList.contains('hidden')) return;
        el.classList.remove('mode-field-in');
        void el.offsetWidth;            // reflow so the animation re-runs
        el.classList.add('mode-field-in');
    }
    function setActivityMode(mode) {
        activityMode = ['irrigation', 'service', 'payroll', 'reminders'].includes(mode) ? mode : 'task';
        $qsa('#activityModeTabs .activity-mode-tab[data-mode]').forEach((tab) => {
            const on = tab.getAttribute('data-mode') === activityMode;
            tab.classList.toggle('is-active', on);
            tab.setAttribute('aria-selected', on ? 'true' : 'false');
        });
        const task = activityMode === 'task';
        const irr = activityMode === 'irrigation';
        const svc = activityMode === 'service';
        const payroll = activityMode === 'payroll';
        const reminders = activityMode === 'reminders';
        // Payroll is its own kind of day's work: the pay is per worker, so the
        // checklist IS the form and the rest of the task fields step aside.
        setActPane(payroll ? 'workers' : 'details');
        // A reminder checklist is a list of errands: no crew, no materials, no
        // lots — the list IS the activity, so everything else steps aside.
        $id('activityRemindersPane')?.classList.toggle('hidden', !reminders);
        $id('activityWorkersPane')?.classList.toggle('hidden', reminders);
        if (reminders && !$qs('#reminderRows .rem-row')) addReminderRow();
        // Its crew is chosen by ticking names, so the chip row would be a
        // second way to say the same thing — and a payroll day is about who
        // turned up, not which patch of ground.
        $id('activityWorkersContainer')?.classList.toggle('hidden', payroll);
        $qs('#activityWorkersPane .form-label')?.classList.toggle('hidden', payroll);
        renderWorkerPay();
        $id('activityTypeWrap')?.classList.toggle('hidden', !task);
        // How much of a day it takes is not a question a list of errands has.
        $qs('#activitySheet .js-time-required')?.classList.toggle('hidden', reminders);
        if (reminders) $id('activityTimeRequired').value = 'n/a';
        $id('activityWaterTaskWrap')?.classList.toggle('hidden', !irr);
        $id('activityServicePriceWrap')?.classList.toggle('hidden', !svc);
        $id('activityImagesSection')?.classList.toggle('hidden', !task);
        $id('activityItemsSection')?.classList.toggle('hidden', svc || reminders);
        const secLabel = $id('itemsSectionLabel');
        if (secLabel) secLabel.textContent = irr ? 'Materials' : 'Materials & Items';
        const titleEl = $id('activityTitle');
        if (titleEl) titleEl.setAttribute('placeholder',
            reminders ? 'e.g. Errands for the day'
            : activityMode === 'payroll' ? 'e.g. Weeding crew — Lot B'
            : svc ? 'e.g. Land preparation (tractor)'
            : irr ? 'e.g. Irrigate Lot A — Day 20–35'
            : 'e.g. Basal Fertilizer Application');
        if (!task) setActivityImages([]);   // reference images are task-only
        refreshDayZeroToggleVisibility();
        // Animate whichever mode-specific field is now visible.
        animateField($id('activityTypeWrap'));
        animateField($id('activityWaterTaskWrap'));
        animateField($id('activityServicePriceWrap'));
    }
    $qsa('#activityModeTabs .activity-mode-tab[data-mode]').forEach((tab) => {
        tab.addEventListener('click', () => setActivityMode(tab.getAttribute('data-mode')));
    });

    /* ---- Reminder checklist builder ---------------------------------------
     * One row per errand. Money is opt-in per line: pick "costs" or "brings
     * in" and an amount appears; leave it alone and the line is just a thing
     * to remember. */
    function reminderRowHtml(r) {
        const kind = (r && r.kind) || 'none';
        const amount = r && r.amount ? r.amount : '';
        return `<div class="rem-row">
            <input type="text" class="form-input rem-text" maxlength="255" placeholder="e.g. Collect the seed permit" value="${esc((r && r.text) || '')}">
            <button type="button" class="btn-ghost rem-del" aria-label="Remove this reminder">✕</button>
            <div class="rem-money${kind === 'none' ? ' is-free' : ''}">
                <select class="form-select rem-kind">
                    <option value="none"${kind === 'none' ? ' selected' : ''}>No money</option>
                    <option value="expense"${kind === 'expense' ? ' selected' : ''}>Costs money</option>
                    <option value="income"${kind === 'income' ? ' selected' : ''}>Brings money in</option>
                </select>
                <input type="number" class="form-input rem-amount" step="0.01" min="0" placeholder="Amount" value="${esc(amount)}">
            </div>
        </div>`;
    }
    function addReminderRow(r) {
        const host = $id('reminderRows');
        if (!host) return;
        host.insertAdjacentHTML('beforeend', reminderRowHtml(r));
        paintReminderCount();
    }
    function setActivityReminders(rows) {
        const host = $id('reminderRows');
        if (!host) return;
        host.innerHTML = '';
        (rows || []).forEach((r) => {
            addReminderRow(r);
            // Editing the wording of a line you already ticked off should not
            // untick it, so the row carries that fact through the form.
            if (r && r.done) host.lastElementChild?.setAttribute('data-done', '1');
        });
        if (!host.children.length) addReminderRow();
        paintReminderCount();
    }
    function getActivityReminders() {
        return $qsa('#reminderRows .rem-row').map((row) => {
            const kind = row.querySelector('.rem-kind')?.value || 'none';
            return {
                text: (row.querySelector('.rem-text')?.value || '').trim(),
                kind,
                amount: kind === 'none' ? 0 : parseFloat(row.querySelector('.rem-amount')?.value || '0') || 0,
                // Editing the list never changes what has already been ticked
                // off on the board; that lives on the card.
                done: row.getAttribute('data-done') === '1',
            };
        }).filter((r) => r.text);
    }
    function paintReminderCount() {
        const n = $qsa('#reminderRows .rem-row').filter((r) => (r.querySelector('.rem-text')?.value || '').trim()).length;
        const badge = $id('activityReminderCount');
        if (!badge) return;
        badge.textContent = n;
        badge.hidden = n === 0;
    }
    $id('addReminderRow')?.addEventListener('click', () => {
        addReminderRow();
        $qsa('#reminderRows .rem-text').slice(-1)[0]?.focus();
    });
    $id('reminderRows')?.addEventListener('click', (e) => {
        const del = e.target.closest('.rem-del');
        if (!del) return;
        const row = del.closest('.rem-row');
        if ($qsa('#reminderRows .rem-row').length === 1) {
            row.querySelector('.rem-text').value = '';
        } else if (window.animateOut) {
            window.animateOut(row, () => { row.remove(); paintReminderCount(); });
            return;
        } else {
            row.remove();
        }
        paintReminderCount();
    });
    $id('reminderRows')?.addEventListener('change', (e) => {
        const kind = e.target.closest('.rem-kind');
        if (!kind) return;
        kind.closest('.rem-money')?.classList.toggle('is-free', kind.value === 'none');
    });
    $id('reminderRows')?.addEventListener('input', (e) => {
        if (e.target.closest('.rem-text')) paintReminderCount();
    });

    // ---- Quill description editor (+ HTML source mode) ----
    let descQuill = null;
    let descMode = 'visual';
    let pendingDescription;

    function initDescriptionEditor() {
        if (typeof Quill === 'undefined' || descQuill) return;
        descQuill = new Quill('#activityDescription', {
            theme: 'snow',
            placeholder: 'Describe this activity…',
            // The app's one toolbar (resources/js/app.js), read at build time
            // because this script parses before the module bundle runs.
            modules: { toolbar: window.SM_RICH_TOOLBAR },
        });
        window.smQuillTouch?.(descQuill);
        if (pendingDescription !== undefined) {
            descQuill.clipboard.dangerouslyPasteHTML(pendingDescription || '');
            pendingDescription = undefined;
        }
    }

    function destroyDescriptionEditor() {
        if (!descQuill) return;
        // Quill has no destroy — strip the injected toolbar/container.
        $qs('#activityDescriptionWrap .ql-toolbar')?.remove();
        const host = $id('activityDescription');
        host.innerHTML = '';
        host.classList.remove('ql-container', 'ql-snow');
        host.removeAttribute('style');
        descQuill = null;
    }

    function getDescriptionContent() {
        if (descMode === 'html') return $id('activityDescriptionSource').value || '';
        if (descQuill) {
            const html = descQuill.root.innerHTML;
            return html === '<p><br></p>' ? '' : html;
        }
        return pendingDescription || '';
    }

    function setDescriptionContent(html) {
        if (descMode === 'html') {
            $id('activityDescriptionSource').value = html || '';
            return;
        }
        if (descQuill) {
            descQuill.clipboard.dangerouslyPasteHTML(html || '');
        } else {
            pendingDescription = html || '';
        }
    }

    function setDescriptionMode(mode) {
        if (mode === descMode) return;
        const wrap = $id('activityDescriptionWrap');
        if (mode === 'html') {
            const html = getDescriptionContent();
            descMode = 'html';
            $id('activityDescriptionSource').value = html;
            wrap.classList.add('is-html-mode');
            $id('toggleDescriptionModeLabel').textContent = 'Back to visual editor';
        } else {
            const html = $id('activityDescriptionSource').value || '';
            descMode = 'visual';
            wrap.classList.remove('is-html-mode');
            if (descQuill) descQuill.clipboard.dangerouslyPasteHTML(html);
            $id('toggleDescriptionModeLabel').textContent = 'Edit HTML source';
        }
    }

    $id('toggleDescriptionMode')?.addEventListener('click', (e) => {
        e.preventDefault();
        setDescriptionMode(descMode === 'visual' ? 'html' : 'visual');
    });

    const activitySheetEl = $id('activitySheet');
    activitySheetEl?.addEventListener('sheet:open', () => {
        setTimeout(initDescriptionEditor, 40);   // Quill needs a visible mount
        // Mounting the editor takes focus, which on a phone throws the keyboard
        // up the instant the sheet appears — covering half the form before you
        // have picked a lot or a type. Drop focus on touch devices; tapping the
        // field you actually want still brings the keyboard up.
        if (window.matchMedia('(pointer: coarse)').matches) {
            const drop = () => document.activeElement?.blur?.();
            setTimeout(drop, 90);
            setTimeout(drop, 300);   // after the sheet's open animation settles
        }
    });
    activitySheetEl?.addEventListener('sheet:close', () => {
        destroyDescriptionEditor();
        descMode = 'visual';
        $id('activityDescriptionWrap').classList.remove('is-html-mode');
        const _src = $id('activityDescriptionSource');
        if (_src) _src.value = '';
    });

    // ---- Reference images (multiple; upload immediately, persist on save) ----
    let ACTIVITY_IMAGES = [];   // [{ path, url }]
    function renderActivityImages() {
        const grid = $id('activityImagesGrid');
        if (!grid) return;
        grid.innerHTML = ACTIVITY_IMAGES.map((img, i) => `
            <div class="activity-image-thumb">
                <img src="${esc(img.url)}" alt="Reference image" loading="lazy">
                <button type="button" class="activity-image-x" data-remove-image="${i}" aria-label="Remove image">✕</button>
            </div>`).join('');
        grid.classList.toggle('hidden', ACTIVITY_IMAGES.length === 0);
    }
    function setActivityImages(list) {
        ACTIVITY_IMAGES = (list || []).filter((x) => x && x.path).map((x) => ({ path: x.path, url: x.url }));
        renderActivityImages();
    }

    $id('activityImagesGrid')?.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-remove-image]');
        if (!btn) return;
        ACTIVITY_IMAGES.splice(Number(btn.dataset.removeImage), 1);
        renderActivityImages();
    });

    document.addEventListener('click', (e) => {
        if (e.target.closest('#activityImageUploadBtn')) {
            const fi = $id('activityImageFileInput');
            fi.value = '';
            fi.click();
        }
    });

    $id('activityImageFileInput')?.addEventListener('change', async (e) => {
        const files = Array.from(e.target.files || []);
        if (!files.length) return;
        const uploadBtn = $id('activityImageUploadBtn');
        const label = $id('activityImageUploadLabel');
        const prev = label ? label.textContent : '';
        uploadBtn.disabled = true;
        if (label) label.textContent = 'Uploading…';
        try {
            for (const file of files) {
                if (!/^image\//.test(file.type)) {
                    toast(`"${file.name}" is not an image — skipped.`, 'error');
                    continue;
                }
                const fd = new FormData();
                fd.append('image', file);
                const res = await api(U.imageUpload(), { method: 'POST', body: fd });
                ACTIVITY_IMAGES.push({ path: res.data.imagePath, url: res.data.imageUrl });
                renderActivityImages();
            }
            toast('Image(s) uploaded.');
        } catch (err) {
            toast(err.message, 'error');
        } finally {
            uploadBtn.disabled = false;
            if (label) label.textContent = prev || 'Add images';
        }
    });

    // ---- Materials & services item picker ----
    function refreshItemsEmptyState() {
        const hasItems = $qsa('#itemsContainer > span').length > 0;
        $id('itemsContainerEmpty').classList.toggle('hidden', hasItems);
    }

    // Reusable per-schedule item catalog: names, prices seen per name, unit.
    const ITEM_CATALOG = Object.assign({ names: [], prices: {}, units: {} }, @json($itemCatalog ?? ['names'=>[], 'prices'=>[], 'units'=>[]]));
    if (Array.isArray(ITEM_CATALOG.prices)) ITEM_CATALOG.prices = {};
    if (Array.isArray(ITEM_CATALOG.units)) ITEM_CATALOG.units = {};

    function rememberItem(name, price, unit) {
        name = (name || '').trim();
        if (!name) return;
        if (!ITEM_CATALOG.names.some((n) => n.toLowerCase() === name.toLowerCase())) ITEM_CATALOG.names.push(name);
        if (price !== '' && price != null) {
            const p = String(trimQty(price));
            (ITEM_CATALOG.prices[name] = ITEM_CATALOG.prices[name] || []);
            if (!ITEM_CATALOG.prices[name].includes(p)) ITEM_CATALOG.prices[name].push(p);
        }
        if (unit) ITEM_CATALOG.units[name] = unit;
    }

    function refreshNameDatalist() {
        const dl = $id('itemNameList');
        if (!dl) return;
        dl.innerHTML = ITEM_CATALOG.names.slice().sort((a, b) => a.localeCompare(b))
            .map((n) => `<option value="${esc(n)}"></option>`).join('');
    }

    // When a known item name is picked, offer its past prices + suggest a unit.
    function refreshPriceDatalistFor(name) {
        const dl = $id('itemPriceList');
        if (!dl) return;
        const key = Object.keys(ITEM_CATALOG.prices).find((k) => k.toLowerCase() === (name || '').toLowerCase());
        const prices = (key ? ITEM_CATALOG.prices[key] : []) || [];
        dl.innerHTML = prices.map((p) => `<option value="${esc(p)}"></option>`).join('');
        const ukey = Object.keys(ITEM_CATALOG.units).find((k) => k.toLowerCase() === (name || '').toLowerCase());
        const unitEl = $id('itemUnitInput');
        if (ukey && unitEl && !unitEl.value) unitEl.value = ITEM_CATALOG.units[ukey];
    }

    $id('itemNameInput')?.addEventListener('input', (e) => refreshPriceDatalistFor(e.target.value));

    function appendItemTag(name, price, qty, unit) {
        const unitSafe = unit || '';
        const priceNum = (price !== '' && price != null && !isNaN(parseFloat(price))) ? parseFloat(price) : null;
        const qtyText = `&nbsp;×${esc(trimQty(qty || 1))}${unitSafe ? ' ' + esc(unitSafe) : ''}`;
        const priceText = priceNum != null ? ` <span class="item-tag-price">@ ₱${esc(fmtMoney(priceNum))}</span>` : '';
        const html = `<span class="item-tag material-tag"
            data-name="${esc(name)}" data-price="${priceNum != null ? esc(String(priceNum)) : ''}" data-qty="${esc(trimQty(qty || 1))}" data-unit="${esc(unitSafe)}">
            <strong>${esc(name)}</strong>${qtyText}${priceText}
            <button type="button" class="remove-item-tag" aria-label="Remove">✕</button>
        </span>`;
        $id('itemsContainer').insertAdjacentHTML('beforeend', html);
        refreshItemsEmptyState();
    }

    function fmtMoney(n) {
        return Number(n || 0).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    $id('itemsContainer')?.addEventListener('click', (e) => {
        const btn = e.target.closest('.remove-item-tag');
        if (!btn) return;
        btn.closest('span').remove();
        refreshItemsEmptyState();
    });

    // Expand / collapse the add-item panel.
    /* ---- Quick-add lots and workers without leaving the sheet ---- */
    function toggleQuickForm(btnId, formId, focusId) {
        $id(btnId)?.addEventListener('click', () => {
            const form = $id(formId);
            const open = !form.classList.contains('hidden');
            form.classList.toggle('hidden', open);
            if (!open) { animateField(form); window.smFocus($id(focusId), { delay: 60 }); }
        });
    }
    toggleQuickForm('quickAddLotBtn', 'quickAddLotForm', 'qalName');
    toggleQuickForm('quickAddWorkerBtn', 'quickAddWorkerForm', 'qawName');
    document.addEventListener('click', (e) => {
        const x = e.target.closest('.js-quick-form-close');
        if (!x) return;
        const formId = x.getAttribute('data-form');
        $id(formId)?.classList.add('hidden');
        if (formId === 'itemPickerPanel') {
            $id('itemsToggleBtn')?.setAttribute('aria-expanded', 'false');
            if ($id('itemsToggleLabel')) $id('itemsToggleLabel').textContent = '+ Item';
        }
    });

    $id('qalSave')?.addEventListener('click', async (e) => {
        const btn = e.currentTarget;
        const lotName = ($id('qalName').value || '').trim();
        if (!lotName) { toast('Give the lot a name.', 'error'); return; }
        btn.disabled = true;
        try {
            const res = await api(U.lotStore(), { method: 'POST', body: {
                lotName,
                lotSize: parseFloat($id('qalSize').value) || 1,
                lotSizeUnit: ($id('qalUnit').value || 'ha').trim() || 'ha',
                variety: ($id('qalVariety').value || '').trim() || null,
            } });
            const lot = res.data;
            LOT_NAMES[lot.id] = lot.lotName + (lot.variety ? ' · ' + lot.variety : '');
            const chip = document.createElement('button');
            chip.type = 'button';
            chip.className = 'chip lot-chip';
            chip.setAttribute('data-lot-id', lot.id);
            chip.setAttribute('aria-pressed', 'false');
            chip.textContent = LOT_NAMES[lot.id];
            $id('activityLotsContainer').insertBefore(chip, $id('quickAddLotBtn'));
            chip.click();   // select it right away
            ['qalName', 'qalVariety'].forEach((i) => { $id(i).value = ''; });
            $id('quickAddLotForm').classList.add('hidden');
            toast(res.message);
        } catch (err) { toast(err.message, 'error'); }
        finally { btn.disabled = false; }
    });

    $id('qawSave')?.addEventListener('click', async (e) => {
        const btn = e.currentTarget;
        const workerName = ($id('qawName').value || '').trim();
        if (!workerName) { toast('Give the worker a name.', 'error'); return; }
        btn.disabled = true;
        try {
            const body = {
                workerName,
                costPerHalfDay: parseFloat($id('qawRate').value) || 0,
            };
            const email = ($id('qawEmail').value || '').trim();
            const phone = ($id('qawPhone').value || '').trim();
            if (email) body.email = email;
            if (phone) body.phone = phone;
            const res = await api(U.workerStore(), { method: 'POST', body });
            const w = res.data;
            WORKER_NAMES[w.id] = w.workerName;
            const chip = document.createElement('button');
            chip.type = 'button';
            chip.className = 'chip worker-chip';
            chip.setAttribute('data-worker-id', w.id);
            chip.setAttribute('aria-pressed', 'false');
            chip.textContent = w.workerName;
            $id('activityWorkersContainer').insertBefore(chip, $id('quickAddWorkerBtn'));
            chip.click();   // select it right away
            ['qawName', 'qawRate', 'qawEmail', 'qawPhone'].forEach((i) => { $id(i).value = ''; });
            $id('quickAddWorkerForm').classList.add('hidden');
            toast(res.message);
        } catch (err) { toast(err.message, 'error'); }
        finally { btn.disabled = false; }
    });

    $id('itemsToggleBtn')?.addEventListener('click', () => {
        const panel = $id('itemPickerPanel');
        const open = panel.classList.toggle('hidden');
        $id('itemsToggleBtn').setAttribute('aria-expanded', open ? 'false' : 'true');
        $id('itemsToggleLabel').textContent = open ? '+ Item' : 'Cancel';
        if (!open) { refreshNameDatalist(); window.smFocus($id('itemNameInput'), { delay: 50 }); }
    });

    $id('addItemBtn')?.addEventListener('click', () => {
        const name = ($id('itemNameInput').value || '').trim();
        if (!name) { toast('Enter an item name', 'error'); $id('itemNameInput').focus(); return; }
        const price = ($id('itemPriceInput').value || '').trim();
        const qty = parseFloat($id('itemQtyInput').value) || 1;
        const unit = ($id('itemUnitInput').value || '').trim();
        if ($qs(`#itemsContainer span[data-name="${cssEsc(name)}"]`)) {
            toast('That item is already added — remove it first to change it.', 'info');
            return;
        }
        appendItemTag(name, price, qty, unit);
        rememberItem(name, price, unit);
        refreshNameDatalist();
        // Clear the fields for the next item, keep the panel open.
        $id('itemNameInput').value = '';
        $id('itemPriceInput').value = '';
        $id('itemQtyInput').value = '1';
        $id('itemUnitInput').value = '';
        $id('itemNameInput').focus();
    });

    // Escape a value for use inside an attribute selector.
    function cssEsc(v) { return String(v).replace(/["\\\]]/g, '\\$&'); }

    // ---- Sheet open/reset/fill ----
    function resetActivitySheet() {
        setActivityReminders([]);
        $id('activityId').value = '';
        $id('activityTitle').value = '';
        setTargetDate('');
        $id('activityTargetEndDate').value = '';
        $id('activityPriority').value = 'medium';
        $id('activityType').value = '';
        setTaskTypes([]);
        if ($id('activityWaterTask')) $id('activityWaterTask').value = 'irrigate';
        if ($id('activityServicePrice')) $id('activityServicePrice').value = '';
        setActivityMode('task');
        $id('activityTimeRequired').value = 'half';
        if ($id('actWorkerChecklist')) $id('actWorkerChecklist').checked = false;
        if ($id('actWorkerSelfCheck')) $id('actWorkerSelfCheck').checked = false;
        $id('activityIsDayZero').checked = false;
        if ($id('activityIsTransplant')) $id('activityIsTransplant').checked = false;
        setWhenTab('date', { instant: true });
        setDescriptionContent('');
        pendingDescription = '';
        setActPane('details');
        $id('activityModeTabs')?.classList.remove('is-locked');
        setActivityLots([]);
        setWorkerPay({});
        setActivityWorkers([]);
        setActivityImages([]);
        $id('itemsContainer').innerHTML = '';
        // Collapse + clear the add-item panel.
        $id('itemPickerPanel')?.classList.add('hidden');
        $id('itemsToggleBtn')?.setAttribute('aria-expanded', 'false');
        if ($id('itemsToggleLabel')) $id('itemsToggleLabel').textContent = '+ Item';
        ['itemNameInput', 'itemPriceInput', 'itemUnitInput'].forEach((idv) => { if ($id(idv)) $id(idv).value = ''; });
        if ($id('itemQtyInput')) $id('itemQtyInput').value = '1';
        refreshItemsEmptyState();
    }

    let BEFORE_SNAPSHOT = null;   // pre-edit payload for the edit-undo path

    // Keep the Drafts counters (toolbar + mobile menu mirror) in step when a
    // draft is added/removed without reopening the drafts list.
    function bumpDraftsBadge(delta) {
        ['draftsBadge', 'actDraftsBadge'].forEach((idv) => {
            const el = $id(idv);
            if (!el) return;
            const n = Math.max(0, (parseInt(el.textContent, 10) || 0) + delta);
            el.textContent = n;
            if (idv === 'actDraftsBadge') el.style.display = n > 0 ? '' : 'none';
        });
    }

    let ADD_AS_DRAFT = false;   // set by the "Add to drafts" menu option
    function openAddActivitySheet(prefillDate, asDraft) {
        ADD_AS_DRAFT = !!asDraft;
        $id('activitySheetTitle').textContent = ADD_AS_DRAFT ? 'Add to Drafts' : 'Add Activity';
        resetActivitySheet();
        BEFORE_SNAPSHOT = null;
        if (prefillDate) setTargetDate(prefillDate);
        const hint = $id('activityDraftHint');
        if (hint) hint.classList.toggle('hidden', !ADD_AS_DRAFT);
        refreshActivityModalLotState();
        markWorkerAvailability();
        openSheet('activitySheet');
    }

    async function openEditActivitySheet(id) {
        try {
            const res = await api(U.show(id));
            const a = res.data;
            ADD_AS_DRAFT = false;
            $id('activityDraftHint')?.classList.add('hidden');
            $id('activitySheetTitle').textContent = 'Edit Activity';
            resetActivitySheet();
            BEFORE_SNAPSHOT = JSON.parse(JSON.stringify(a));
            $id('activityId').value = a.id;
            $id('activityTitle').value = a.activityTitle || '';
            setTargetDate((a.targetDate || '').slice(0, 10));
            $id('activityTargetEndDate').value = (a.targetEndDate || '').slice(0, 10);
            $id('activityPriority').value = a.priority || 'medium';
            if (a.activityType === 'irrigation') {
                setActivityMode('irrigation');
                if ($id('activityWaterTask')) $id('activityWaterTask').value = a.waterTask || 'irrigate';
            } else if (a.activityType === 'service') {
                setActivityMode('service');
                if ($id('activityServicePrice')) $id('activityServicePrice').value = a.servicePrice != null ? a.servicePrice : '';
            } else if (a.activityType === 'worker_payroll') {
                setActivityMode('payroll');
            } else if (a.activityType === 'reminder_checklist') {
                setActivityMode('reminders');
                setActivityReminders(a.reminders || []);
            } else {
                setActivityMode('task');
                $id('activityType').value = a.activityType || '';
                setTaskTypes([a.activityType, ...(Array.isArray(a.extraTypes) ? a.extraTypes : [])]);
            }
            $id('activityTimeRequired').value = a.timeRequired || 'half';
            if ($id('actWorkerChecklist')) $id('actWorkerChecklist').checked = !!boolFlag(a.workerChecklist);
            if ($id('actWorkerSelfCheck')) $id('actWorkerSelfCheck').checked = !!boolFlag(a.workerSelfCheck);
            $id('activityIsDayZero').checked = !!boolFlag(a.isDayZero);
            $id('activityIsTransplant').checked = !!boolFlag(a.isTransplant);
            setActivityLots(a.lotIds || (a.lots || []).map((l) => l.id));
            setWorkerPay(a.workerPay || {});
            setActivityWorkers(a.workerIds || (a.workers || []).map((w) => w.id));
            if (a.activityType === 'worker_payroll') setActPane('workers');
            // What an activity IS was decided when it was made. Offering to
            // change it here invites turning a day's payroll into an
            // irrigation task by mistake, and the fields behind the two have
            // nothing in common.
            $id('activityModeTabs')?.classList.add('is-locked');
            setDescriptionContent(a.description || '');
            setActivityImages(a.images || (a.imagePath ? [{ path: a.imagePath, url: a.imageUrl }] : []));
            (a.items || []).forEach((it) => {
                const name = it.itemName || it.material?.materialName || it.service?.serviceName;
                if (name) {
                    appendItemTag(name, it.unitPrice != null ? it.unitPrice : '', it.quantity, it.unitOfMeasure || '');
                    rememberItem(name, it.unitPrice != null ? it.unitPrice : '', it.unitOfMeasure || '');
                }
            });
            refreshActivityModalLotState();
            openSheet('activitySheet');
        } catch (err) {
            toast(err.message, 'error');
        }
    }

    // Add Activity opens the sheet directly (a fresh, non-draft activity).
    $id('addActivityBtn')?.addEventListener('click', () => openAddActivitySheet());
    // The FAB (phones) always adds a normal activity.
    $id('fabAddActivity')?.addEventListener('click', () => openAddActivitySheet());

    // Convert an Activity model JSON back into an /update payload — edit-undo path.
    function activityToPayload(a) {
        const lotIds = a.lotIds || (a.lots || []).map((l) => l.id);
        const workerIds = a.workerIds || (a.workers || []).map((w) => w.id);
        const items = (a.items || []).map((it) => ({
            itemName: it.itemName || it.material?.materialName || it.service?.serviceName || '',
            unitPrice: it.unitPrice != null ? it.unitPrice : '',
            quantity: it.quantity,
            unitOfMeasure: it.unitOfMeasure || '',
            notes: it.notes || '',
        })).filter((it) => it.itemName);
        return {
            activityTitle: a.activityTitle,
            targetDate: (a.targetDate || '').slice(0, 10),
            targetEndDate: a.targetEndDate ? a.targetEndDate.slice(0, 10) : null,
            priority: a.priority,
            activityType: a.activityType || '',
            extraTypes: Array.isArray(a.extraTypes) ? a.extraTypes.slice() : [],
            waterTask: a.waterTask || '',
            servicePrice: a.servicePrice != null ? a.servicePrice : '',
            description: a.description || '',
            imagePaths: (a.images && a.images.length ? a.images.map((img) => img.path) : (a.imagePaths || (a.imagePath ? [a.imagePath] : []))).filter(Boolean),
            timeRequired: a.timeRequired,
            workerChecklist: boolFlag(a.workerChecklist),
            workerSelfCheck: boolFlag(a.workerSelfCheck),
            isDayZero: boolFlag(a.isDayZero),
            isTransplant: boolFlag(a.isTransplant),
            lotIds,
            workerIds,
            items,
        };
    }

    $id('saveActivityBtn')?.addEventListener('click', async (e) => {
        const btn = e.currentTarget;
        const id = $id('activityId').value;
        const startDateVal = ($id('activityTargetDate').value || '').trim();
        const endDateVal = ($id('activityTargetEndDate').value || '').trim();
        if (endDateVal && startDateVal && endDateVal < startDateVal) {
            toast('End date must be on or after the start date.', 'error');
            return;
        }
        const items = $qsa('#itemsContainer > span').map((tag) => ({
            itemName: tag.getAttribute('data-name') || '',
            unitPrice: tag.getAttribute('data-price') || '',
            quantity: tag.getAttribute('data-qty'),
            unitOfMeasure: tag.getAttribute('data-unit') || '',
        })).filter((it) => it.itemName);
        const isIrrigation = activityMode === 'irrigation';
        const isService = activityMode === 'service';
        const isPayroll = activityMode === 'payroll';
        const isReminders = activityMode === 'reminders';
        const activityType = isReminders ? 'reminder_checklist'
            : (isPayroll ? 'worker_payroll'
            : (isIrrigation ? 'irrigation' : (isService ? 'service' : (taskTypes()[0] || ''))));
        const payload = {
            activityTitle: $id('activityTitle').value,
            targetDate: startDateVal,
            targetEndDate: endDateVal || null,
            priority: $id('activityPriority').value,
            activityType,
            // Everything else in the same tank (empty unless this is a task).
            extraTypes: (isIrrigation || isService || isPayroll || isReminders)
                ? [] : taskTypes().slice(1),
            waterTask: isIrrigation ? ($id('activityWaterTask').value || 'irrigate') : '',
            servicePrice: isService ? ($id('activityServicePrice').value || '') : '',
            description: getDescriptionContent(),
            imagePaths: ACTIVITY_IMAGES.map((img) => img.path),
            timeRequired: $id('activityTimeRequired').value,
            // A payroll day always keeps a roster, so the flag is only asked
            // of everything else.
            workerChecklist: (!isPayroll && $id('actWorkerChecklist')?.checked) ? 1 : 0,
            workerSelfCheck: $id('actWorkerSelfCheck')?.checked ? 1 : 0,
            isDayZero: $id('activityIsDayZero').checked ? 1 : 0,
            isTransplant: $id('activityIsTransplant').checked ? 1 : 0,
            isDraft: (!id && ADD_AS_DRAFT) ? 1 : 0,
            lotIds: getActivityLotIds(),   // empty = N/A (not lot-specific)
            workerIds: getActivityWorkerIds(),
            workerPay: getWorkerPay(),
            items: (isService || isReminders) ? [] : items,
            reminders: isReminders ? getActivityReminders() : null,
        };
        if (isReminders && !payload.reminders.length) {
            toast('Add at least one reminder to the checklist.', 'error');
            return;
        }
        if (!payload.activityTitle) {
            toast('Activity title is required', 'error');
            return;
        }
        if (!payload.targetDate) {
            toast('Pick a start date', 'error');
            return;
        }

        btn.disabled = true;
        const _saveLabel = btn.innerHTML;
        btn.innerHTML = '<svg class="w-4 h-4 animate-spin inline-block align-[-3px] mr-1.5" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/></svg>Saving…';
        try {
            const res = await api(id ? U.update(id) : U.store(), { method: id ? 'PUT' : 'POST', body: payload });
            toast(res.message);
            closeSheet('activitySheet');
            const savedTitle = res.data.activityTitle || payload.activityTitle || 'activity';
            const html = renderActivityCard(res.data);

            if (id) {
                const existing = $qs(`#activitiesList .activity-card[data-id="${id}"]`);
                if (existing) existing.outerHTML = html;
                const before = BEFORE_SNAPSHOT;
                const after = res.data;
                if (before) {
                    pushUndo(`Edit '${savedTitle}'`, async () => {
                        const r = await api(U.update(before.id), { method: 'PUT', body: activityToPayload(before) });
                        if (!r || !r.success) throw new Error((r && r.message) || 'restore failed');
                        _renderCardOrReplace(r.data);
                    }, async () => {
                        const r = await api(U.update(after.id), { method: 'PUT', body: activityToPayload(after) });
                        if (!r || !r.success) throw new Error((r && r.message) || 'reapply failed');
                        _renderCardOrReplace(r.data);
                    });
                }
            } else if (boolFlag(res.data.isDraft)) {
                // Created straight into the Drafts bin — it stays off the board
                // (it keeps its target date; the Drafts list shows that day).
                const newId = res.data.id;
                bumpDraftsBadge(1);
                toast('Saved to drafts');
                pushUndo(`Draft '${savedTitle}'`, async () => {
                    const r = await api(U.destroy(newId), { method: 'DELETE' });
                    if (!r || !r.success) throw new Error((r && r.message) || 'delete failed');
                    bumpDraftsBadge(-1);
                }, async () => {
                    const r = await api(U.restore(newId), { method: 'POST' });
                    if (!r || !r.success) throw new Error((r && r.message) || 'restore failed');
                    bumpDraftsBadge(1);
                });
            } else {
                $id('activitiesEmpty')?.remove();
                $id('activitiesList').insertAdjacentHTML('beforeend', html);
                const newId = res.data.id;
                pushUndo(`Add '${savedTitle}'`, async () => {
                    const r = await api(U.destroy(newId), { method: 'DELETE' });
                    if (!r || !r.success) throw new Error((r && r.message) || 'delete failed');
                    _removeCardById(newId);
                }, async () => {
                    const r = await api(U.restore(newId), { method: 'POST' });
                    if (!r || !r.success) throw new Error((r && r.message) || 'restore failed');
                    _renderCardOrReplace(r.data);
                });
            }
            // The saved activity's day unfolds so the result is visible.
            if (res.data && res.data.targetDate) { OPEN_DAYS.add(String(res.data.targetDate).slice(0, 10)); saveOpenDays(); }
            reorderAndRenumberActivities();
            recomputeLotDayZero();
        } catch (err) {
            toast(err.message, 'error');
        } finally {
            btn.disabled = false;
            btn.innerHTML = _saveLabel;
        }
    });

    /* ================================================================
     * 7. PER-CARD ACTIONS
     * ================================================================ */

    async function duplicateActivity(id, name) {
        try {
            const res = await api(U.duplicate(id), { method: 'POST' });
            toast(`Duplicated "${name}". Edit and save when ready.`);
            $id('activitiesEmpty')?.remove();
            $id('activitiesList').insertAdjacentHTML('beforeend', renderActivityCard(res.data));
            if (res.data && res.data.targetDate) { OPEN_DAYS.add(String(res.data.targetDate).slice(0, 10)); saveOpenDays(); }
            reorderAndRenumberActivities();
            recomputeLotDayZero();
            const copyId = res.data.id;
            pushUndo(`Duplicate '${name}'`, async () => {
                const r = await api(U.destroy(copyId), { method: 'DELETE' });
                if (!r || !r.success) throw new Error((r && r.message) || 'delete failed');
                _removeCardById(copyId);
            }, async () => {
                const r = await api(U.restore(copyId), { method: 'POST' });
                if (!r || !r.success) throw new Error((r && r.message) || 'restore failed');
                _renderCardOrReplace(r.data);
            });
            openEditActivitySheet(copyId);   // open the copy for editing right away
        } catch (err) {
            toast(err.message, 'error');
        }
    }

    async function deleteActivity(id, name) {
        const ok = await confirmAction({
            title: 'Delete activity',
            message: `Delete activity "${name}"?`,
            detail: 'You can immediately undo this (Ctrl+Z) — the activity is soft-deleted and can be restored.',
            confirmText: 'Delete Activity',
        });
        if (!ok) return;
        try {
            const res = await api(U.destroy(id), { method: 'DELETE' });
            toast(res.message);
            _removeCardById(id);
            pushUndo(`Delete '${name}'`, async () => {
                const r = await api(U.restore(id), { method: 'POST' });
                if (!r || !r.success) throw new Error((r && r.message) || 'restore failed');
                _renderCardOrReplace(r.data);
            }, async () => {
                const r = await api(U.destroy(id), { method: 'DELETE' });
                if (!r || !r.success) throw new Error((r && r.message) || 'delete failed');
                _removeCardById(id);
            });
        } catch (err) {
            toast(err.message, 'error');
        }
    }

    async function moveActivityToDrafts(id, name) {
        try {
            await api(U.toDraft(id), { method: 'POST' });
            toast(`"${name}" moved to drafts`);
            _removeCardById(id);
            bumpDraftsBadge(1);
            pushUndo(`Move '${name}' to drafts`, async () => {
                const r = await api(U.fromDraft(id), { method: 'POST' });
                if (!r || !r.success) throw new Error((r && r.message) || 'restore failed');
                _renderCardOrReplace(r.data);
                bumpDraftsBadge(-1);
            }, async () => {
                const r = await api(U.toDraft(id), { method: 'POST' });
                if (!r || !r.success) throw new Error((r && r.message) || 'move failed');
                _removeCardById(id);
                bumpDraftsBadge(1);
            });
        } catch (err) {
            toast(err.message, 'error');
        }
    }

    async function toggleActivityHidden(id) {
        const card = $qs(`#activitiesList .activity-card[data-id="${id}"]`);
        if (!card) return;
        const wantHide = !card.classList.contains('is-hidden');

        // Optimistic flip; revert on failure.
        const applyState = (hidden) => {
            card.classList.toggle('is-hidden', hidden);
            card.setAttribute('data-is-hidden', hidden ? 1 : 0);
            const tag = $qs('.hide-activity-tag', card);
            if (tag) tag.style.display = hidden ? '' : 'none';
            const btn = $qs('.hide-activity-toggle', card);
            if (btn) btn.setAttribute('aria-pressed', hidden ? 'true' : 'false');
        };
        applyState(wantHide);
        reorderAndRenumberActivities();   // recompute all-hidden groups + substitutes

        try {
            await api(U.toggleHidden(id), { method: 'POST' });
            toast(wantHide ? 'Activity hidden' : 'Activity shown');
        } catch (err) {
            toast(err.message, 'error');
            const cardNow = $qs(`#activitiesList .activity-card[data-id="${id}"]`);
            if (cardNow) {
                cardNow.classList.toggle('is-hidden', !wantHide);
                cardNow.setAttribute('data-is-hidden', !wantHide ? 1 : 0);
                const tag = $qs('.hide-activity-tag', cardNow);
                if (tag) tag.style.display = !wantHide ? '' : 'none';
                const btn = $qs('.hide-activity-toggle', cardNow);
                if (btn) btn.setAttribute('aria-pressed', !wantHide ? 'true' : 'false');
            }
            reorderAndRenumberActivities();
        }
    }

    // Single-card move-to-date (mobile fallback for drag & drop).
    let CARD_MENU = { id: null, name: '' };
    let INFO_ID = null;
    let INFO_EDIT = null;   // what the sheet's Edit button opens for a note

    /**
     * Show one activity in full on a phone.
     *
     * The body is a clone of the card rather than a rebuilt copy: the card is
     * already rendered twice (Blade and JS) and a third spelling would be a
     * third thing to keep in step. Cloning also means anything added to a card
     * later shows up here for free. Controls are stripped from the clone —
     * acting on the activity belongs to the kebab sheet, not to a read view.
     */
    function openActivityInfo(card) {
        const body = $id('activityInfoBody');
        if (!body || !card) return;

        INFO_ID = card.getAttribute('data-id');
        $id('activityInfoTitle').textContent = 'Activity details';
        $id('activityInfoEdit').classList.remove('hidden');
        showInfoClone(body, card);
    }

    /**
     * The same read view for a note. Notes are clamped to one line on a phone
     * for the same reason cards are, so a tap has to be able to show the rest
     * without dropping the reader straight into an editor.
     */
    function openNoteInfo(noteEl, editFn) {
        const body = $id('activityInfoBody');
        if (!body || !noteEl) return;

        INFO_ID = null;
        $id('activityInfoTitle').textContent = 'Note';
        const edit = $id('activityInfoEdit');
        edit.classList.toggle('hidden', !editFn);
        INFO_EDIT = editFn || null;
        showInfoClone(body, noteEl);
    }

    function showInfoClone(body, el) {
        const clone = el.cloneNode(true);
        clone.removeAttribute('draggable');
        // Strip the controls: this is somewhere to read, and its own footer
        // carries the one action that makes sense from here. Attachment chips
        // (.na) stay — opening a photo or a map is reading, not editing.
        clone.querySelectorAll('button:not(.na), [data-sheet-open], .inline-note-grip').forEach((n) => n.remove());
        clone.classList.remove('is-editing');
        body.innerHTML = '';
        body.appendChild(clone);
        openSheet('activityInfoSheet');
    }

    $id('activityInfoEdit')?.addEventListener('click', () => {
        const id = INFO_ID;
        const noteEdit = INFO_EDIT;
        closeSheet('activityInfoSheet');
        if (noteEdit) { noteEdit(); return; }
        if (id) openEditActivitySheet(id);
    });

    function moveSingleActivity(id, newDate) {
        const card = $qs(`#activitiesList .activity-card[data-id="${id}"]`);
        if (!card || !newDate) return;
        const oldDate = (card.getAttribute('data-target-date') || '').trim();
        if (newDate === oldDate) {
            toast('That is already the current date.', 'info');
            return;
        }
        const snapshot = captureBoardSnapshot();
        const oldEnd = (card.getAttribute('data-target-end-date') || '').trim();
        let newEnd = '';
        if (oldEnd && oldDate) {
            newEnd = isoAddDays(oldEnd, isoDaysBetween(oldDate, newDate));
        }
        card.setAttribute('data-target-date', newDate);
        card.setAttribute('data-target-end-date', newEnd);
        card.setAttribute('data-sequence-order', 0);

        const items = [{ id: parseInt(id, 10), targetDate: newDate, targetEndDate: newEnd || null, sequenceOrder: 0 }];
        reorderAndRenumberActivities();

        api(U.reorder(), { method: 'POST', body: { items } })
            .then(() => {
                toast('Moved to ' + newDate);
                recomputeLotDayZero();
                pushUndo('Move activity to ' + newDate, () => restoreBoardSnapshot(snapshot));
            })
            .catch((err) => {
                // The server refused the move (a full day, most likely), so put
                // the card back where it came from rather than leaving the
                // board showing a move that was never saved.
                restoreBoardSnapshot(snapshot);
                toast(err.message, 'error');
            });
    }

    // Day overflow menu (phones). Rows forward to the real date-header buttons,
    // which stay in the DOM (desktop-only visually), so no handler is duplicated.
    let dayMenuDate = null;
    document.addEventListener('click', (e) => {
        const btn = e.target.closest('.day-menu-btn');
        if (!btn) return;
        dayMenuDate = btn.getAttribute('data-date') || '';
        const label = $id('dayMenuTitle');
        if (label) label.textContent = dayMenuDate ? prettyDateFull(dayMenuDate) : 'This day';
        // The saved-weather row is offered only for days the store actually
        // holds a reading for — an empty sheet is worse than no row.
        $id('daySavedWxRow')?.classList.toggle('hidden', !window.SAVED_WX_DATES?.has(dayMenuDate));
        openSheet('dayMenuSheet');
    });

    document.addEventListener('click', (e) => {
        const row = e.target.closest('.day-menu-action');
        if (!row || !dayMenuDate) return;
        const cls = row.getAttribute('data-action');
        closeSheet('dayMenuSheet');
        // This one has no twin button on the day header — it reads the store.
        if (cls === 'view-saved-weather') {
            const date = dayMenuDate;
            setTimeout(() => openSavedWeather(date), 260);
            return;
        }
        if (cls === 'add-income') {
            const date = dayMenuDate;
            setTimeout(() => openDayIncome(date), 260);
            return;
        }
        if (cls === 'capture-photo') {
            const date = dayMenuDate;
            setTimeout(() => captureDayPhoto(date), 260);
            return;
        }
        if (cls === 'record-video') {
            const date = dayMenuDate;
            setTimeout(() => recordDayVideo(date), 260);
            return;
        }
        if (cls === 'add-drawing') {
            const date = dayMenuDate;
            setTimeout(() => addDayDrawing(date), 260);
            return;
        }
        if (cls === 'add-map') {
            const date = dayMenuDate;
            setTimeout(() => openDayMapPick(date), 260);
            return;
        }
        const target = $qs(`#activitiesList .date-group[data-date="${dayMenuDate}"] .${cls}`);
        // Defer so the sheet is closed before the next one opens.
        setTimeout(() => target?.click(), 260);
    });

    /* ---- A drawing or a map, kept with the day ---------------------------
     * Both used to be reachable only from inside the note editor, which made
     * them feel like formatting rather than like things the day has. They hang
     * off the day now and land in that day's note, so nothing new has to be
     * invented to store or show them. */
    /* ---- The camera, kept with the day ----------------------------------
     * A photo and a clip are the fastest notes anyone writes: you are already
     * standing in front of the thing. Both land the same way a drawing does —
     * as a note of their own, with a name and words, so the Gallery, the
     * global notes and the day all show it without anything new being
     * invented to hold it.
     *
     * One host serves both. The recorder partial writes whatever it captures
     * into a `.js-video-file` inside a host element, so borrowing that here
     * means the record button, the size limit and the preview are the ones
     * already used everywhere else. */
    let captureHost = null;
    function ensureCaptureHost() {
        if (captureHost) return captureHost;
        captureHost = document.createElement('span');
        captureHost.setAttribute('data-video-host', '');
        captureHost.style.display = 'none';
        captureHost.innerHTML = '<input type="file" class="js-video-file" accept="video/*">'
            + '<button type="button" class="js-video-record"></button>'
            + '<input type="file" class="js-photo-file" accept="image/*" capture="environment">';
        document.body.appendChild(captureHost);
        return captureHost;
    }

    /** Upload one file with a visible wait, then start a note carrying it. */
    async function noteFromCapture(dateKey, file, url, field, kind, suggested) {
        const done = window.smBusy(kind === 'video' ? 'Uploading the video…' : 'Uploading the photo…');
        try {
            const form = new FormData();
            form.append(field, file);
            const res = await fetch(url, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, Accept: 'application/json' },
                body: form,
                credentials: 'same-origin',
            });
            const json = await res.json().catch(() => ({}));
            if (!json.success || !json.data?.path) throw new Error(json.message || 'Upload failed.');
            done.close();
            const entry = kind === 'video'
                ? { type: 'video', path: json.data.path, poster: json.data.poster, url: json.data.url, posterUrl: json.data.posterUrl }
                : { type: 'image', path: json.data.path, url: json.data.url };
            newInlineNoteWith(dateKey, [entry], suggested);
        } catch (err) {
            done.close();
            toast(err.message || 'Could not save that.', 'error');
        }
    }

    function captureDayPhoto(dateKey) {
        dateKey = (dateKey || '').trim() || isoFromDate(new Date());
        const host = ensureCaptureHost();
        const input = host.querySelector('.js-photo-file');
        input.value = '';
        // Bound fresh each time: the date it belongs to changes per call, and
        // a stale listener would file the photo on the wrong day.
        input.onchange = () => {
            const f = input.files && input.files[0];
            input.value = '';
            if (!f) return;
            noteFromCapture(dateKey, f, U.noteImageUpload(), 'image', 'image', 'Photo');
        };
        input.click();
    }

    function recordDayVideo(dateKey) {
        dateKey = (dateKey || '').trim() || isoFromDate(new Date());
        const host = ensureCaptureHost();
        const input = host.querySelector('.js-video-file');
        input.value = '';
        // The shared recorder writes its result into this input. Listening for
        // that write is how a recording becomes a note without the recorder
        // needing to know anything about notes.
        input.onchange = () => {
            const f = input.files && input.files[0];
            input.value = '';
            if (!f) return;
            noteFromCapture(dateKey, f, U.noteVideoUpload(), 'video', 'video', 'Video');
        };
        host.querySelector('.js-video-record').click();
    }

    // Tools offers both for today, so the camera does not require first
    // finding today on a board that may be scrolled somewhere else.
    $id('captureTodayPhotoBtn')?.addEventListener('click', () => captureDayPhoto(isoFromDate(new Date())));
    $id('recordTodayVideoBtn')?.addEventListener('click', () => recordDayVideo(isoFromDate(new Date())));

    async function addDayDrawing(dateKey) {
        if (typeof window.openDrawCanvas !== 'function') { toast('Drawing pad unavailable.', 'error'); return; }
        window.openDrawCanvas(async (dataUrl, objects) => {
            try {
                const res = await api(NOTES_DRAW_URL, { method: 'POST', body: { image: dataUrl } });
                const path = res && res.data && res.data.path;
                if (!path) throw new Error('Upload failed.');
                // The pad's two exits again: strokes come back only from "Save
                // as drawing", and only then can it be reopened and changed.
                const entry = objects
                    ? { type: 'drawing', path, url: res.data.url, strokes: objects }
                    : { type: 'image', path, url: res.data.url };
                // Its own note, with its own name and words — piling every
                // drawing of a day into one note left a single block nobody
                // could tell apart, and a wall of pictures under it.
                newInlineNoteWith(dateKey, [entry], objects ? 'Drawing' : 'Sketch');
            } catch (err) { toast(err.message || 'Could not add the drawing.', 'error'); }
        }, null, { editable: true });
    }

    let mapPickDate = null;
    async function openDayMapPick(dateKey) {
        mapPickDate = dateKey;
        const list = $id('dayMapList');
        const link = $id('dayMapNew');
        if (link) link.setAttribute('href', MAPS_URL);
        if (list) list.innerHTML = '<p class="text-sm text-gray-400 py-2">Loading…</p>';
        openSheet('dayMapPickSheet');
        try {
            const res = await api(MAP_SAVES_URL);
            const saves = ((res.data && res.data.saves) || []).filter((s) => s.imagePath);
            if (!list) return;
            list.innerHTML = saves.length
                ? saves.map((s) => `<button type="button" class="w-full flex items-center gap-3 rounded-xl p-2 text-left hover:bg-gray-50" data-map="${s.id}">
                        <span class="w-14 h-11 rounded-lg bg-gray-100 overflow-hidden shrink-0">${s.imageUrl ? `<img src="${esc(s.imageUrl)}" alt="" class="w-full h-full object-cover">` : ''}</span>
                        <span class="min-w-0">
                            <span class="block font-semibold text-gray-800 text-sm truncate">${esc(s.title || 'Map')}</span>
                            <span class="block text-xs text-gray-400">${esc(s.by || '')} · ${esc(s.when || '')}</span>
                        </span>
                    </button>`).join('')
                : '<p class="text-sm text-gray-400 py-2">No saved maps yet. Draw one in the Maps module and save it, then it can be attached here.</p>';
            list.querySelectorAll('[data-map]').forEach((btn) => {
                btn.addEventListener('click', () => {
                    const save = saves.find((s) => String(s.id) === btn.getAttribute('data-map'));
                    if (!save) return;
                    closeSheet('dayMapPickSheet');
                    // The same road a fresh drawing takes: the editor opens
                    // with the map already attached and asks for the note's
                    // name and words — instead of the map landing silently in
                    // the day note to be explained in a later edit.
                    const entry = {
                        type: 'map', path: save.imagePath, url: save.imageUrl,
                        mapUrl: MAPS_URL + '&save=' + save.id,
                    };
                    setTimeout(() => newInlineNoteWith(mapPickDate, [entry], save.title || 'Map'), 260);
                });
            });
        } catch (err) {
            if (list) list.innerHTML = '<p class="text-sm text-red-500 py-2">Could not load saved maps.</p>';
        }
    }

    /* ---- Income for one day ---------------------------------------------
     * The mirror of extra expenses: money the day brought in, for services a
     * farm sells alongside the crop. Entries already logged for that day are
     * listed inside the sheet, so a second one does not overwrite the first
     * and an existing one can be corrected. */
    let incomeDate = null, incomeRows = [];
    const peso = (n) => '₱' + Number(n || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });

    function paintIncomeList() {
        const box = $id('dayIncomeList');
        if (!box) return;
        if (!incomeRows.length) { box.innerHTML = ''; return; }
        const total = incomeRows.reduce((a, r) => a + Number(r.amount || 0), 0);
        box.innerHTML = '<p class="form-label mb-1">Already logged this day</p>'
            + incomeRows.map((r) => `
                <button type="button" class="w-full flex items-center gap-2 rounded-lg border border-gray-200 px-2.5 py-2 text-left hover:border-brand-400" data-income-edit="${r.id}">
                    <span class="grow min-w-0">
                        <span class="block text-sm font-bold text-gray-900 truncate">${esc(r.title || 'Income')}</span>
                        ${r.note ? `<span class="block text-xs text-gray-500 truncate">${esc(r.note)}</span>` : ''}
                    </span>
                    <span class="text-sm font-extrabold text-brand-700 shrink-0">${peso(r.amount)}</span>
                </button>`).join('')
            + `<p class="text-xs text-gray-500 pt-1">Total for the day: <b class="text-gray-800">${peso(total)}</b></p>`;
    }

    function resetIncomeForm() {
        $id('dayIncomeId').value = '';
        $id('dayIncomeAmount').value = '';
        $id('dayIncomeTitle').value = '';
        $id('dayIncomeNote').value = '';
        $id('dayIncomeDeleteBtn').classList.add('hidden');
        $id('dayIncomeSheetTitle').textContent = 'Add income';
    }

    async function openDayIncome(date) {
        incomeDate = date;
        $id('dayIncomeDate').value = date;
        $id('dayIncomeForDate').textContent = prettyDateFull(date);
        resetIncomeForm();
        incomeRows = [];
        paintIncomeList();
        openSheet('dayIncomeSheet');
        // A mouse lands ready to type; a phone does not. The keyboard rising
        // with the sheet covers half of what you opened before you have read
        // it, so on touch the first tap is yours to make.
        if (!window.matchMedia('(pointer: coarse)').matches) {
            setTimeout(() => $id('dayIncomeAmount')?.focus({ preventScroll: true }), 340);
        }
        try {
            const res = await api(`${U.dayIncomeList()}&incomeDate=${encodeURIComponent(date)}`);
            incomeRows = res.data || [];
            paintIncomeList();
        } catch (_) { /* an empty list is a fine starting point */ }
    }

    document.addEventListener('click', (e) => {
        const row = e.target.closest('[data-income-edit]');
        if (!row) return;
        const r = incomeRows.find((x) => String(x.id) === row.getAttribute('data-income-edit'));
        if (!r) return;
        $id('dayIncomeId').value = r.id;
        $id('dayIncomeAmount').value = r.amount;
        $id('dayIncomeTitle').value = r.title || '';
        $id('dayIncomeNote').value = r.note || '';
        $id('dayIncomeDeleteBtn').classList.remove('hidden');
        $id('dayIncomeSheetTitle').textContent = 'Edit income';
    });

    $id('dayIncomeSaveBtn')?.addEventListener('click', async (e) => {
        const btn = e.currentTarget;
        const amount = parseFloat($id('dayIncomeAmount').value);
        if (!(amount >= 0)) { toast('Enter the amount first.', 'error'); return; }
        btn.disabled = true;
        try {
            const res = await api(U.dayIncomeSave(), { method: 'POST', body: {
                incomeId: $id('dayIncomeId').value || null,
                incomeDate: incomeDate,
                amount,
                title: $id('dayIncomeTitle').value.trim(),
                note: $id('dayIncomeNote').value.trim(),
            } });
            incomeRows = res.data || [];
            resetIncomeForm();
            paintIncomeList();
            renderDayIncome(incomeDate);
            toast(res.message || 'Income saved.');
        } catch (err) { toast(err.message, 'error'); }
        btn.disabled = false;
    });

    $id('dayIncomeDeleteBtn')?.addEventListener('click', async () => {
        const id = $id('dayIncomeId').value;
        if (!id) return;
        const ok = window.confirmAction
            ? await confirmAction({ title: 'Remove this income?', message: 'It will no longer count towards the day.', confirmText: 'Remove' })
            : confirm('Remove this income entry?');
        if (!ok) return;
        try {
            const res = await api(U.dayIncomeDelete(id), { method: 'DELETE', body: { incomeId: id } });
            incomeRows = res.data || [];
            resetIncomeForm();
            paintIncomeList();
            renderDayIncome(incomeDate);
            toast(res.message || 'Income removed.');
        } catch (err) { toast(err.message, 'error'); }
    });

    /* The day's own strip, under the expenses one, so a day that earned
       something says so without opening anything. */
    async function renderDayIncome(date) {
        const host = $qs(`#activitiesList .day-income-block[data-date="${date}"]`);
        if (!host) return;
        try {
            const res = await api(`${U.dayIncomeList()}&incomeDate=${encodeURIComponent(date)}`);
            const rows = res.data || [];
            if (!rows.length) { host.innerHTML = ''; host.hidden = true; return; }
            host.hidden = false;
            host.innerHTML = `<span class="day-income-total">${peso(res.total)} in</span>`
                + rows.map((r) => `<span class="day-income-chip">${esc(r.title || 'Income')} &middot; ${peso(r.amount)}</span>`).join('');
        } catch (_) { /* leave whatever is there */ }
    }
    window.renderDayIncome = renderDayIncome;

    /* ---- Saved weather for one day --------------------------------------
     * What the forecast said, as last written by a weather load. Read-only:
     * this is the record a report or the AI technician looks back at, so it
     * shows when it was captured rather than pretending to be live. */
    window.SAVED_WX_DATES = new Set(@json($savedWeatherDates ?? []));
    async function openSavedWeather(date) {
        const body = $id('savedWeatherBody');
        const title = $id('savedWeatherTitle');
        if (!body) return;
        if (title) title.textContent = 'Saved weather — ' + prettyDateFull(date);
        body.innerHTML = '<p class="text-sm text-gray-500 text-center py-6">Loading…</p>';
        openSheet('savedWeatherSheet');
        try {
            const res = await fetch(`{{ route('sm.weather.saved') }}?scheduleId=${SCHEDULE_ID}&date=${encodeURIComponent(date)}`,
                { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
            const json = await res.json();
            const entries = (json && json.data && json.data.entries) || [];
            if (!entries.length) {
                body.innerHTML = '<p class="text-sm text-gray-500 text-center py-6">Nothing saved for this day yet.</p>';
                return;
            }
            body.innerHTML = entries.map((e) => {
                const d = e.day || {};
                const hrs = e.hours || [];
                const rail = hrs.length ? `<div class="wx-hours mt-3">${hrs.map((h) => `
                        <div class="wx-hour" title="${esc(h.text || '')}">
                            <div class="wx-hour-time">${esc(h.hour || '')}</div>
                            <div class="wx-hour-emoji">${h.emoji || ''}</div>
                            <div class="wx-hour-temp">${h.temp != null ? h.temp + '&deg;' : '&ndash;'}</div>
                            <div class="wx-hour-pop ${(h.pop || 0) < 20 ? 'is-dry' : ''}">&#128167;${h.pop != null ? h.pop + '%' : '&mdash;'}</div>
                        </div>`).join('')}</div>`
                    : '<p class="wx-legend mt-2">No hour-by-hour was saved for this day.</p>';
                return `<div class="card mb-3"><div class="card-body">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <p class="font-bold text-gray-900 text-sm">${esc(e.place || 'Location')}</p>
                            <p class="wx-legend">Captured ${esc(e.capturedAt || 'earlier')}</p>
                        </div>
                        <span class="text-3xl leading-none">${d.emoji || '&#9925;'}</span>
                    </div>
                    <div class="wx-verdict mt-3"><span class="wx-verdict-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M8 3H6a2 2 0 00-2 2v14a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2h-2M9 3h6v3H9V3zM8 12h8M8 16h5"/></svg></span><span class="wx-verdict-text">
                        ${esc(d.text || 'No reading')}${d.max != null ? `, high of <b>${d.max}&deg;</b>` : ''}${d.min != null ? `, low of <b>${d.min}&deg;</b>` : ''}.
                        Chance of rain that day was <b>${d.pop != null ? d.pop + '%' : '&mdash;'}</b>.
                    </span></div>
                    ${rail}
                </div></div>`;
            }).join('');
        } catch (_) {
            body.innerHTML = '<p class="text-sm text-gray-500 text-center py-6">Could not load the saved reading.</p>';
        }
    }

    // Tapping the card body opens the editor — the primary action on a phone,
    // where aiming for a small pencil icon is awkward. Interactive bits and the
    // lot chips (used by the lot filter) keep their own behaviour.
    /* ---- Card accordion (phones) ----------------------------------------
     * Cards start folded to their head row so a day scans at a glance and a
     * drag has short distances to travel; a tap expands one in place. Which
     * cards are open persists per schedule, exactly like the day accordion.
     */
    const CARD_OPEN_KEY = 'cardOpen:' + @json($schedule->id);
    const CARD_OPEN = new Set((() => {
        try { return JSON.parse(localStorage.getItem(CARD_OPEN_KEY) || '[]'); } catch (_) { return []; }
    })());
    function saveCardOpen() {
        try { localStorage.setItem(CARD_OPEN_KEY, JSON.stringify([...CARD_OPEN])); } catch (_) { /* private mode */ }
    }
    function applyCardCollapse() {
        if (!window.matchMedia('(pointer: coarse)').matches) return;
        $qsa('#activitiesList .activity-card[data-id]').forEach((c) => {
            c.classList.toggle('act-collapsed', !CARD_OPEN.has(c.getAttribute('data-id')));
        });
    }
    function toggleCardExpand(card) {
        const id = card.getAttribute('data-id');
        const opening = card.classList.contains('act-collapsed');
        if (opening) CARD_OPEN.add(id); else CARD_OPEN.delete(id);
        saveCardOpen();

        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            card.classList.toggle('act-collapsed', !opening);
            return;
        }
        // Height is animated from measured to measured because the card's
        // children cannot be re-wrapped: the markup is rendered twice (Blade
        // and JS) and both copies would have to change in step forever.
        const from = card.getBoundingClientRect().height;
        card.classList.toggle('act-collapsed', !opening);
        const to = card.getBoundingClientRect().height;
        card.style.height = from + 'px';
        card.style.overflow = 'hidden';
        void card.offsetHeight;
        card.style.transition = 'height .28s cubic-bezier(.22,1,.36,1)';
        card.style.height = to + 'px';
        setTimeout(() => {
            card.style.height = ''; card.style.overflow = ''; card.style.transition = '';
        }, 300);
    }
    // Re-rendered and freshly added cards fold according to the saved set —
    // without this every JS re-render came back fully expanded.
    if (document.getElementById('activitiesList')) {
        new MutationObserver(() => applyCardCollapse())
            .observe(document.getElementById('activitiesList'), { childList: true, subtree: true });
    }
    applyCardCollapse();

    // Long boards: one tap to either end.
    $id('actJumpTop')?.addEventListener('click', () => window.scrollTo({ top: 0, behavior: 'smooth' }));
    $id('actJumpBottom')?.addEventListener('click', () => window.scrollTo({ top: document.documentElement.scrollHeight, behavior: 'smooth' }));

    // The floating + forwards to the real Add Activity button.
    $id('actFabAdd')?.addEventListener('click', () => $id('addActivityBtn')?.click());

    // Phones: one Versions button opens a sheet of the strip's chips. Rows
    // are rebuilt from the strip on every open, so renames, adds and the
    // current selection are always fresh; taps forward to the real chips.
    (() => {
        const btn = $id('versionsSheetBtn');
        if (!btn) return;
        function buildRows() {
            const list = $id('versionsSheetList');
            if (!list) return;
            list.innerHTML = '';
            $qsa('#versionStrip .version-chip').forEach((chip) => {
                const current = chip.classList.contains('is-selected');
                const row = document.createElement('button');
                row.type = 'button';
                row.className = 'version-sheet-row' + (current ? ' is-current' : '');
                const name = document.createElement('span');
                name.className = 'vsr-name';
                name.textContent = (chip.dataset.isOriginal === '1' ? '★ ' : '') + (chip.dataset.versionName || chip.textContent.trim());
                row.appendChild(name);
                if (current) {
                    const now = document.createElement('span');
                    now.className = 'vsr-now';
                    now.textContent = 'current';
                    row.appendChild(now);
                }
                row.addEventListener('click', () => { closeSheet('versionsSheet'); setTimeout(() => chip.click(), 240); });
                list.appendChild(row);
            });
            const add = document.createElement('button');
            add.type = 'button';
            add.className = 'version-sheet-row vsr-add';
            add.textContent = '+ Add a new version';
            add.addEventListener('click', () => { closeSheet('versionsSheet'); setTimeout(() => $id('addVersionBtn')?.click(), 240); });
            list.appendChild(add);
        }
        btn.addEventListener('click', () => { buildRows(); openSheet('versionsSheet'); });
    })();

    // The buttons only earn their place once the header bar (versions +
    // Today) has scrolled off screen — while it shows, they stay hidden.
    // The same page runs inside the Collab Room iframe, so both get it.
    (() => {
        const jumps = document.querySelector('.act-jumps');
        // The ROW, not the chip strip: closest() starts at the element itself,
        // and the strip is display:none on phones (folded behind the Versions
        // button) — a hidden element never intersects, so the stack showed
        // permanently. The row is always laid out, so it reports honestly.
        const bar = $id('actHeaderBar');
        if (!jumps || !bar || !('IntersectionObserver' in window)) return;
        jumps.classList.add('bar-visible');
        new IntersectionObserver(([e]) => {
            jumps.classList.toggle('bar-visible', e.isIntersecting);
        }, { threshold: 0 }).observe(bar);
    })();

    // Tools → Contract All: every card folded, every day folded, both saved.
    $id('contractAllBtn')?.addEventListener('click', () => {
        CARD_OPEN.clear(); saveCardOpen(); applyCardCollapse();
        $qsa('#activitiesList .date-group').forEach((g) => g.classList.add('is-folded'));
        OPEN_DAYS.clear(); saveOpenDays();
        if (window.toast) toast('Everything folded up.');
    });

    document.addEventListener('click', (e) => {
        if (e.target.closest('button, a, input, textarea, select, label, .item-tag')) return;
        if (e.target.closest('[data-lightbox] img')) return;   // image clicks open the lightbox
        const card = e.target.closest('#activitiesList .activity-card[data-id]');
        if (!card) return;
        // A drag that just ended never reaches here: the touch-drag system
        // swallows its trailing click at capture phase (see swallowNextClick).
        // On a phone a tap folds the card open or shut — the accordion IS the
        // read view, so this applies to done cards too. Editing and the done
        // note stay a deliberate action away (kebab / desktop click).
        if (window.matchMedia('(pointer: coarse)').matches) {
            toggleCardExpand(card);
            return;
        }
        if (card.getAttribute('data-is-done') === '1') {
            openDoneNoteSheet(card.getAttribute('data-id'), $qs('.activity-card-title', card)?.textContent || 'Activity');
            return;
        }
        openEditActivitySheet(card.getAttribute('data-id'));
    });

    // Swap a clicked icon button for a spinner until its async work settles.
    // Doubles as a double-click guard via data-busy.
    function spinBtn(btn) {
        if (!btn || btn.dataset.busy) return null;
        btn.dataset.busy = '1';
        const prev = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = SVG.spinner;
        return () => { btn.innerHTML = prev; btn.disabled = false; delete btn.dataset.busy; };
    }

    /* ---- Done checkbox: lock a finished activity (notes still allowed) ---- */
    let DONE_NOTE = { id: null };

    function setDoneMeta(card, done) {
        card.setAttribute('data-is-done', done ? 1 : 0);
        card.setAttribute('draggable', done ? 'false' : 'true');
        const check = $qs('.done-check', card);
        if (check) {
            check.classList.toggle('is-checked', done);
            check.setAttribute('aria-pressed', done ? 'true' : 'false');
            check.title = done ? 'Mark as not done (unlocks editing)' : 'Mark this activity as done';
        }
    }

    function applyDoneState(card, done) {
        setDoneMeta(card, done);
        card.classList.toggle('is-done', done);
        // The done-hide/add-note-activity-btn CSS pair swaps the action
        // buttons automatically off the card's is-done class.
    }

    // Interactive check/uncheck: fade the outgoing buttons, flip the class
    // (the CSS pair does the display swap), then pop the incoming one in.
    // The lock metadata flips immediately so a mid-animation drag can't start.
    function animateDoneSwap(card, done) {
        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            applyDoneState(card, done);
            return;
        }
        if (card.__doneSwapTimer) { clearTimeout(card.__doneSwapTimer); card.__doneSwapTimer = null; }
        setDoneMeta(card, done);
        const out = $qsa(done ? '.done-hide' : '.add-note-activity-btn', card)
            .filter((el) => el.offsetParent !== null);
        out.forEach((el) => {
            el.style.transition = 'opacity .18s cubic-bezier(.22,1,.36,1), transform .18s cubic-bezier(.22,1,.36,1)';
            el.style.opacity = '0';
            el.style.transform = 'scale(.7)';
        });
        card.__doneSwapTimer = setTimeout(() => {
            card.__doneSwapTimer = null;
            card.classList.toggle('is-done', done);
            out.forEach((el) => { el.style.transition = ''; el.style.opacity = ''; el.style.transform = ''; });
            $qsa(done ? '.add-note-activity-btn' : '.done-hide', card).forEach((el) => {
                el.classList.remove('btn-pop-in');
                void el.offsetWidth;
                el.classList.add('btn-pop-in');
                el.addEventListener('animationend', () => el.classList.remove('btn-pop-in'), { once: true });
            });
        }, out.length ? 180 : 0);
    }

    async function toggleActivityDone(id) {
        const card = $qs(`#activitiesList .activity-card[data-id="${id}"]`);
        if (!card) return;
        const wantDone = card.getAttribute('data-is-done') !== '1';
        animateDoneSwap(card, wantDone);   // optimistic; revert on failure
        try {
            const res = await api(U.toggleDone(id), { method: 'POST' });
            toast(res.message);
        } catch (err) {
            toast(err.message, 'error');
            const cardNow = $qs(`#activitiesList .activity-card[data-id="${id}"]`);
            if (cardNow) animateDoneSwap(cardNow, !wantDone);
        }
    }

    function openDoneNoteSheet(id, name) {
        DONE_NOTE.id = id;
        DONE_NOTE.images = [];
        $id('doneNoteTitle').textContent = name || 'This activity';
        $id('doneNoteText').value = '';
        renderDoneNoteThumbs();
        openSheet('doneNoteSheet');
        window.smFocus($id('doneNoteText'), { delay: 250 });
    }

    function renderDoneNoteThumbs() {
        const grid = $id('doneNoteThumbs');
        if (!grid) return;
        grid.textContent = '';
        (DONE_NOTE.images || []).forEach((img, i) => {
            const cell = document.createElement('div');
            cell.className = 'relative';
            const im = document.createElement('img');
            im.src = img.url;
            im.className = 'w-full h-16 object-cover rounded-lg';
            const x = document.createElement('button');
            x.type = 'button';
            x.textContent = '✕';
            x.className = 'absolute -top-1.5 -right-1.5 w-6 h-6 rounded-full bg-red-500 text-white text-xs font-bold';
            x.addEventListener('click', () => { DONE_NOTE.images.splice(i, 1); renderDoneNoteThumbs(); });
            cell.appendChild(im); cell.appendChild(x);
            grid.appendChild(cell);
        });
    }

    $id('doneNoteAddImages')?.addEventListener('click', () => $id('doneNoteImages').click());
    $id('doneNoteImages')?.addEventListener('change', async (e) => {
        const files = [...(e.target.files || [])];
        e.target.value = '';
        for (const file of files) {
            const fd = new FormData();
            fd.append('image', file);
            try {
                const res = await api(U.imageUpload(), { method: 'POST', body: fd });
                DONE_NOTE.images.push({ path: res.data.imagePath, url: res.data.imageUrl });
                renderDoneNoteThumbs();
            } catch (err) { toast(err.message, 'error'); }
        }
    });

    $id('saveDoneNoteBtn')?.addEventListener('click', async (e) => {
        const btn = e.currentTarget;
        const note = ($id('doneNoteText').value || '').trim();
        if (!note && !(DONE_NOTE.images || []).length) { toast('Write a note or add a photo first.', 'error'); return; }
        btn.disabled = true;
        try {
            const res = await api(U.appendNote(DONE_NOTE.id), { method: 'POST', body: { note, images: (DONE_NOTE.images || []).map((i) => i.path) } });
            closeSheet('doneNoteSheet');
            const card = $qs(`#activitiesList .activity-card[data-id="${DONE_NOTE.id}"]`);
            if (card) {
                let desc = $qs('.activity-description-content', card);
                if (!desc) {
                    desc = document.createElement('div');
                    desc.className = 'activity-description-content text-sm text-gray-700 mt-2';
                    desc.setAttribute('data-lightbox', '');
                    $qs('.flex.items-start.justify-between', card)?.after(desc);
                }
                desc.innerHTML = res.data.description;
            }
            toast('Note added.');
        } catch (err) {
            toast(err.message, 'error');
        } finally {
            btn.disabled = false;
        }
    });

    // Card + timeline click delegation.
    document.addEventListener('click', (e) => {
        const doneCheck = e.target.closest('.done-check');
        if (doneCheck) {
            if (!doneCheck.dataset.busy) {
                doneCheck.dataset.busy = '1';
                toggleActivityDone(doneCheck.getAttribute('data-id')).finally(() => delete doneCheck.dataset.busy);
            }
            return;
        }
        const noteBtn = e.target.closest('.add-note-activity-btn');
        if (noteBtn) {
            openDoneNoteSheet(noteBtn.getAttribute('data-id'), noteBtn.getAttribute('data-name') || 'Activity');
            return;
        }
        const editBtn = e.target.closest('.edit-activity-btn');
        if (editBtn) {
            const done = spinBtn(editBtn);
            if (done) openEditActivitySheet(editBtn.getAttribute('data-id')).finally(done);
            return;
        }
        const dupBtn = e.target.closest('.duplicate-activity-btn');
        if (dupBtn) {
            const done = spinBtn(dupBtn);
            if (done) duplicateActivity(dupBtn.getAttribute('data-id'), dupBtn.getAttribute('data-name') || 'activity').finally(done);
            return;
        }
        const draftBtn = e.target.closest('.to-draft-activity-btn');
        if (draftBtn) {
            moveActivityToDrafts(draftBtn.getAttribute('data-id'), draftBtn.getAttribute('data-name') || 'activity');
            return;
        }
        const delBtn = e.target.closest('.delete-activity-btn');
        if (delBtn) {
            deleteActivity(delBtn.getAttribute('data-id'), delBtn.getAttribute('data-name') || 'activity');
            return;
        }
        const hideBtn = e.target.closest('.hide-activity-toggle');
        if (hideBtn) {
            const done = spinBtn(hideBtn);
            if (done) Promise.resolve(toggleActivityHidden(hideBtn.getAttribute('data-id'))).finally(done);
            return;
        }
        const menuBtn = e.target.closest('.card-menu-btn');
        if (menuBtn) {
            CARD_MENU = { id: menuBtn.getAttribute('data-id'), name: menuBtn.getAttribute('data-name') || 'Activity' };
            $id('cardMenuTitle').textContent = CARD_MENU.name;
            const card = $qs(`#activitiesList .activity-card[data-id="${CARD_MENU.id}"]`);
            $id('cardMenuHideLabel').textContent = card && card.classList.contains('is-hidden')
                ? 'Show in presentations'
                : 'Hide from presentations';
            openSheet('cardMenuSheet');
            return;
        }
        const tagBtn = e.target.closest('.tag-activity-btn');
        if (tagBtn) {
            openActivityTagSheet(parseInt(tagBtn.getAttribute('data-id'), 10), tagBtn.getAttribute('data-name') || '');
            return;
        }
        const menuAction = e.target.closest('[data-card-menu-action]');
        if (menuAction && CARD_MENU.id) {
            const action = menuAction.getAttribute('data-card-menu-action');
            const { id, name } = CARD_MENU;
            closeSheet('cardMenuSheet');
            // Mobile path: the sheet is gone, so the card's kebab icon spins
            // while the editor/duplicate fetch is in flight.
            const kebab = $qs(`#activitiesList .activity-card[data-id="${id}"] .card-menu-btn`);
            const cardIsDone = $qs(`#activitiesList .activity-card[data-id="${id}"]`)?.getAttribute('data-is-done') === '1';
            if (cardIsDone && (action === 'edit' || action === 'move')) { openDoneNoteSheet(id, name); return; }
            if (action === 'edit') { const done = spinBtn(kebab); openEditActivitySheet(id).finally(() => done && done()); }
            else if (action === 'duplicate') { const done = spinBtn(kebab); duplicateActivity(id, name).finally(() => done && done()); }
            else if (action === 'advanced') openAdvancedInfo(id, name);
            else if (action === 'tag') openActivityTagSheet(id, name);
            else if (action === 'draft') moveActivityToDrafts(id, name);
            else if (action === 'delete') deleteActivity(id, name);
            else if (action === 'hide') toggleActivityHidden(id);
            else if (action === 'move') openMoveSheet(id, name);
            return;
        }
    });

    /* ---- Move an activity: by date, or by the count the lot keeps -------
     * Moving is the same question the add sheet asks — "when?" — so it is
     * asked the same way, including the day-number lens. A grower planning in
     * DAS had to convert to a calendar date in their head before they could
     * answer, which is exactly the arithmetic this app exists to do. */
    let MOVE_MODE = 'date';        // 'date' | 'das'
    let MOVE_COUNTER = 'DAS';      // which count the number is in

    /** The lots this move can be counted against: they need an anchor. */
    function moveAnchorLots() {
        return Object.keys(LOT_DAY_ZERO_DATES).map(Number).filter((id) => LOT_DAY_ZERO_DATES[id]);
    }

    /** Which counts the chosen lot keeps, in the order they run. */
    function moveCountersFor(lotId) {
        const mode = lotDayType(lotId);
        if (mode === 'DAP') return ['DAP'];
        // A sown-then-transplanted lot keeps two, and only once it has a
        // transplant date to count the second one from.
        return (mode === 'DAT' && LOT_TRANSPLANT_DATES[lotId]) ? ['DAS', 'DAT'] : ['DAS'];
    }

    /** Where day 0 of the chosen count sits. */
    function moveAnchorFor(lotId, counter) {
        return counter === 'DAT'
            ? (LOT_TRANSPLANT_DATES[lotId] || null)
            : (LOT_DAY_ZERO_DATES[lotId] || null);
    }

    function paintMoveCounters() {
        const lotId = parseInt($id('moveDasLot').value, 10);
        const list = moveCountersFor(lotId);
        if (!list.includes(MOVE_COUNTER)) MOVE_COUNTER = list[0];
        // One count, nothing to choose — the label already says which.
        $id('moveCounters').innerHTML = list.length < 2 ? '' : list.map((c) =>
            `<button type="button" class="move-counter${c === MOVE_COUNTER ? ' is-on' : ''}" data-counter="${c}">${c}</button>`).join('');
        $id('moveDasLabel').textContent = MOVE_COUNTER + ' day';
        updateMoveNote();
    }

    function updateMoveNote() {
        const lotId = parseInt($id('moveDasLot').value, 10);
        const anchor = moveAnchorFor(lotId, MOVE_COUNTER);
        const note = $id('moveDasNote');
        if (!anchor) {
            note.innerHTML = 'That lot has no ' + esc(MOVE_COUNTER) + ' 0 yet, so it cannot be counted from.';
            return;
        }
        const n = $id('moveDasDay').value;
        const dateStr = n === '' ? '' : _dasToDateStr(n, anchor);
        note.innerHTML = `<strong>${esc(MOVE_COUNTER)} 0</strong> for <strong>${esc(LOT_NAMES[lotId] || ('Lot #' + lotId))}</strong>`
            + ` = ${esc(prettyDate(anchor))}.`
            + (dateStr ? ` ${esc(MOVE_COUNTER)} ${esc(String(n))} is <strong>${esc(prettyDate(dateStr))}</strong> — that is the date that gets saved.` : '');
    }

    function setMoveMode(mode) {
        MOVE_MODE = mode === 'das' ? 'das' : 'date';
        const das = MOVE_MODE === 'das';
        $id('moveTabDate').classList.toggle('is-active', !das);
        $id('moveTabDate').setAttribute('aria-selected', das ? 'false' : 'true');
        $id('moveTabDas').classList.toggle('is-active', das);
        $id('moveTabDas').setAttribute('aria-selected', das ? 'true' : 'false');
        $id('moveWhenDate').classList.toggle('hidden', das);
        $id('moveWhenDas').classList.toggle('hidden', !das);
        if (das) {
            // Start from where the activity already is, so the number is a
            // correction rather than a blank to fill.
            const anchor = moveAnchorFor(parseInt($id('moveDasLot').value, 10), MOVE_COUNTER);
            const cur = $id('moveDateInput').value;
            if (anchor && cur) $id('moveDasDay').value = _dateStrToDas(cur, anchor);
            updateMoveNote();
        }
    }

    function openMoveSheet(id, name) {
        const card = $qs(`#activitiesList .activity-card[data-id="${id}"]`);
        const current = card ? (card.getAttribute('data-target-date') || '') : '';
        $id('moveDateName').textContent = name;
        $id('moveDateInput').value = current;
        paintPill($id('moveDateField'), $id('moveDateText'), current);

        // The lots to count against: the ones this activity covers first,
        // since that is what a day number about it would mean.
        const own = card
            ? $qsa('.activity-card-lothead .lot-tag[data-lot-id], .activity-card-badges .lot-tag[data-lot-id]', card)
                .map((t) => parseInt(t.getAttribute('data-lot-id'), 10))
            : [];
        const anchored = moveAnchorLots();
        const ordered = own.filter((l) => anchored.includes(l)).concat(anchored.filter((l) => !own.includes(l)));
        const sel = $id('moveDasLot');
        sel.innerHTML = ordered.map((l) => `<option value="${l}">${esc(LOT_NAMES[l] || ('Lot #' + l))}</option>`).join('');

        // No lot has a day zero — the day-number lens has nothing to count
        // from, so it says so instead of offering an empty form.
        const canCount = ordered.length > 0;
        $id('moveTabDas').disabled = !canCount;
        $id('moveTabDas').title = canCount ? '' : 'No lot has a day zero yet';
        MOVE_COUNTER = 'DAS';
        if (canCount) paintMoveCounters();
        setMoveMode('date');
        openSheet('moveDateSheet');
    }

    $id('moveDasLot')?.addEventListener('change', paintMoveCounters);
    $id('moveDasDay')?.addEventListener('input', updateMoveNote);
    $id('moveCounters')?.addEventListener('click', (e) => {
        const b = e.target.closest('.move-counter');
        if (!b) return;
        MOVE_COUNTER = b.getAttribute('data-counter');
        paintMoveCounters();
    });
    $id('moveTabDate')?.addEventListener('click', () => setMoveMode('date'));
    $id('moveTabDas')?.addEventListener('click', () => setMoveMode('das'));
    $id('moveDateInput')?.addEventListener('change', () => {
        paintPill($id('moveDateField'), $id('moveDateText'), $id('moveDateInput').value);
    });

    $id('confirmMoveDateBtn')?.addEventListener('click', () => {
        let newDate = $id('moveDateInput').value;
        if (MOVE_MODE === 'das') {
            const lotId = parseInt($id('moveDasLot').value, 10);
            const anchor = moveAnchorFor(lotId, MOVE_COUNTER);
            const n = $id('moveDasDay').value;
            if (!anchor) { toast('That lot has no ' + MOVE_COUNTER + ' 0 to count from.', 'error'); return; }
            if (n === '') { toast('Enter a ' + MOVE_COUNTER + ' day number.', 'error'); return; }
            newDate = _dasToDateStr(n, anchor);
        }
        if (!newDate) {
            toast('Pick a date.', 'error');
            return;
        }
        closeSheet('moveDateSheet');
        moveSingleActivity(CARD_MENU.id, newDate);
    });

    /* ================================================================
     * SHARE A DAY — public, link-based (no login for the viewer). The
     * unguessable schedule token + the ISO date form the URL.
     * ================================================================ */
    function openDayShareSheet(dateKey) {
        const S = window.SM_SHARE || {};
        if (!S.scheduleUrl) { toast('Share link unavailable.', 'error'); return; }
        if (!dateKey || dateKey === '__no-date__') { toast('This day has no date to share.', 'error'); return; }
        const url = S.scheduleUrl + '/d/' + dateKey;
        const pretty = prettyDateFull ? prettyDateFull(dateKey) : dateKey;
        $id('dayShareTitle').textContent = 'Share ' + pretty;
        $id('dayShareLink').value = url;
        const enc = encodeURIComponent(url);
        const text = encodeURIComponent((S.title || 'Cropping plan') + ' — ' + pretty);
        $id('dayShareFb').href = 'https://www.facebook.com/sharer/sharer.php?u=' + enc;
        $id('dayShareWa').href = 'https://wa.me/?text=' + text + '%20' + enc;
        $id('dayShareEmail').href = 'mailto:?subject=' + text + '&body=' + enc;
        const nativeBtn = $id('dayShareNative');
        if (navigator.share) {
            nativeBtn.style.display = '';
            nativeBtn.onclick = () => navigator.share({ title: S.title || 'Cropping plan', url }).catch(() => {});
        } else {
            nativeBtn.style.display = 'none';
        }
        openSheet('dayShareSheet');
        loadShareCofarmers($qs('#dayShareSheet .js-share-cofarmers'));
    }

    $id('dayShareCopy')?.addEventListener('click', async () => {
        const input = $id('dayShareLink');
        try {
            await navigator.clipboard.writeText(input.value);
        } catch (_) {
            input.select();
            document.execCommand('copy');
        }
        toast('Link copied');
    });

    /* ================================================================
     * QUICK SHARE — share the whole plan (public page) or email the
     * day's activities to workers who have a registered email.
     * ================================================================ */
    $id('quickShareBtn')?.addEventListener('click', () => {
        const S = window.SM_SHARE || {};
        if (!S.scheduleUrl) { toast('Share link unavailable.', 'error'); return; }
        const enc = encodeURIComponent(S.scheduleUrl);
        const text = encodeURIComponent(S.title || 'My cropping plan');
        $id('quickShareLink').value = S.scheduleUrl;
        $id('quickShareFb').href = 'https://www.facebook.com/sharer/sharer.php?u=' + enc;
        $id('quickShareWa').href = 'https://wa.me/?text=' + text + '%20' + enc;
        $id('quickShareEmail').href = 'mailto:?subject=' + text + '&body=' + enc;
        openSheet('quickShareSheet');
        loadShareCofarmers($qs('#quickShareSheet .js-share-cofarmers'));
    });

    $id('quickShareCopy')?.addEventListener('click', async () => {
        const input = $id('quickShareLink');
        try {
            await navigator.clipboard.writeText(input.value);
        } catch (_) {
            input.select();
            document.execCommand('copy');
        }
        toast('Link copied');
    });

    $qsa('.quick-email-btn').forEach((btn) => {
        btn.addEventListener('click', async () => {
            const S = window.SM_SHARE || {};
            const scope = btn.getAttribute('data-scope');
            const orig = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = 'Sending…';
            try {
                const res = await fetch(S.emailUrl, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                    },
                    body: JSON.stringify({ scheduleId: S.scheduleId, scope }),
                });
                const data = await res.json().catch(() => ({}));
                toast(data.message || (data.success ? 'Emailed.' : 'Could not send.'), data.success ? 'success' : 'error');
                if (data.success) closeSheet('quickShareSheet');
            } catch (_) {
                toast('Network error — try again.', 'error');
            } finally {
                btn.disabled = false;
                btn.innerHTML = orig;
            }
        });
    });

    /* ---- Send a plan/day link to a community connection (co-farmers) ----
     * The picker only lists accepted connections; the recipient opens the
     * public link, so nothing here needs the viewer to be a member. */
    const SHARE_COFARMERS_URL = @json(route('community.cofarmers.list'));
    const SHARE_MSG_BASE = @json(url('/app/community/messages'));
    async function loadShareCofarmers(box) {
        if (!box || box.dataset.loaded) return;
        box.dataset.loaded = '1';
        try {
            const res = await fetch(SHARE_COFARMERS_URL, { headers: { Accept: 'application/json' } });
            const data = await res.json().catch(() => ({}));
            const items = (data.data && data.data.items) || [];
            if (!items.length) {
                box.innerHTML = '<p class="text-sm text-gray-400 px-2 py-3 text-center">No co-farmers yet. Connect with members in the Community.</p>';
                return;
            }
            box.innerHTML = items.map((u) => {
                const av = u.avatar
                    ? `<img src="${esc(u.avatar)}" alt="" class="w-8 h-8 rounded-full object-cover shrink-0">`
                    : `<span class="w-8 h-8 rounded-full bg-brand-100 text-brand-700 text-xs font-bold flex items-center justify-center shrink-0">${esc(u.initials || '?')}</span>`;
                const btn = u.allowMessages
                    ? `<button type="button" class="btn btn-white btn-sm shrink-0" data-share-send="${u.id}">Send</button>`
                    : '<span class="text-xs text-gray-400 shrink-0">Messages off</span>';
                return `<div class="flex items-center gap-2 px-2 py-1.5 rounded-lg hover:bg-gray-50">${av}<span class="grow min-w-0 truncate text-sm font-semibold text-gray-800">${esc(u.name)}</span>${btn}</div>`;
            }).join('');
        } catch (_) {
            box.dataset.loaded = '';
            box.innerHTML = '<p class="text-sm text-red-500 px-2 py-3 text-center">Could not load co-farmers.</p>';
        }
    }
    document.addEventListener('click', async (e) => {
        const send = e.target.closest('.js-share-cofarmers [data-share-send]');
        if (!send) return;
        const box = send.closest('.js-share-cofarmers');
        const linkInput = $id(box.getAttribute('data-link-input'));
        const url = linkInput ? linkInput.value : (window.SM_SHARE || {}).scheduleUrl;
        if (!url) { toast('Share link unavailable.', 'error'); return; }
        const title = (window.SM_SHARE || {}).title || 'Cropping plan';
        const userId = send.getAttribute('data-share-send');
        send.disabled = true;
        const orig = send.textContent;
        send.textContent = 'Sending…';
        try {
            const res = await fetch(SHARE_MSG_BASE + '/' + userId, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                },
                body: JSON.stringify({ body: title + '\n' + url }),
            });
            const data = await res.json().catch(() => ({}));
            if (res.ok && data.success !== false) {
                send.textContent = 'Sent ✓';
                toast('Shared with your co-farmer.');
            } else {
                send.disabled = false; send.textContent = orig;
                toast(data.message || 'Could not send.', 'error');
            }
        } catch (_) {
            send.disabled = false; send.textContent = orig;
            toast('Network error — try again.', 'error');
        }
    });

    /* ================================================================
     * 8. DATE-GROUP ACTIONS
     * ================================================================ */

    document.addEventListener('click', (e) => {
        const addBtn = e.target.closest('.rest-day-add-btn, .group-add-activity-btn');
        if (addBtn) {
            e.preventDefault();
            openAddActivitySheet((addBtn.getAttribute('data-date') || '').trim());
            return;
        }
        const changeBtn = e.target.closest('.change-group-date-btn');
        if (changeBtn) {
            e.preventDefault();
            const oldDate = (changeBtn.getAttribute('data-date') || '').trim();
            if (!oldDate || oldDate === '__no-date__') return;
            const cards = $qsa(`#activitiesList .date-group[data-date="${oldDate}"] .activity-card[data-id]`);
            $id('changeGroupDateCount').textContent = cards.length;
            $id('changeGroupDateCurrent').textContent = prettyDateFull(oldDate);
            $id('changeGroupDateOld').value = oldDate;
            $id('changeGroupDateNew').value = oldDate;
            openSheet('changeGroupDateSheet');
            return;
        }
        const dasBtn = e.target.closest('.move-group-das-btn');
        if (dasBtn) {
            e.preventDefault();
            openMoveGroupDasSheet((dasBtn.getAttribute('data-date') || '').trim());
            return;
        }
        const shareDayBtn = e.target.closest('.share-day-btn');
        if (shareDayBtn) {
            e.preventDefault();
            openDayShareSheet((shareDayBtn.getAttribute('data-date') || '').trim());
            return;
        }
        const delGroupBtn = e.target.closest('.delete-group-date-btn');
        if (delGroupBtn) {
            e.preventDefault();
            deleteDateGroup((delGroupBtn.getAttribute('data-date') || '').trim());
            return;
        }
    });

    /* ---- Move a whole day to a DAS/DAT number ----
     * Same move as "change group date", but the target is expressed as "day N
     * after this lot's day 0" instead of a calendar date, which is how the
     * agronomy is actually written down. */

    let MOVE_DAS_DATE = '';

    function moveGroupDasAnchor() {
        const lotId = parseInt($id('moveGroupDasRefLot')?.value, 10);
        return lotId ? (LOT_DAY_ZERO_DATES[lotId] || null) : null;
    }

    function refreshMoveGroupDasPreview() {
        const hint = $id('moveGroupDasPreview');
        const anchor = moveGroupDasAnchor();
        const n = parseInt($id('moveGroupDasValue').value, 10);
        if (!hint) return;
        if (!anchor) { hint.textContent = ''; return; }
        const dt = dayType();
        if (isNaN(n)) {
            hint.innerHTML = `<strong>${esc(dt)} 0</strong> = ${esc(prettyDate(anchor))}.`;
            return;
        }
        const target = _dasToDateStr(n, anchor);
        const same = target === MOVE_DAS_DATE;
        hint.innerHTML = `<strong>${esc(dt)} ${n}</strong> = <strong>${esc(prettyDate(target))}</strong>`
            + (same ? ' — that is where this day already sits.' : '');
    }

    function openMoveGroupDasSheet(dateKey) {
        if (!dateKey || dateKey === '__no-date__') return;
        const sel = $id('moveGroupDasRefLot');
        if (!sel) return;

        // Only lots with a day 0 can anchor a day number.
        const lotIds = Object.keys(LOT_DAY_ZERO_DATES).filter((id) => LOT_DAY_ZERO_DATES[id]);
        if (lotIds.length === 0) {
            toast(`No lot has a ${dayType()} 0 yet. Mark an activity as day zero, or set a lot's day-0 date, first.`, 'error');
            return;
        }

        MOVE_DAS_DATE = dateKey;
        sel.innerHTML = '';
        lotIds.forEach((lotId) => {
            const opt = document.createElement('option');
            opt.value = lotId;
            opt.textContent = (LOT_NAMES[lotId] || ('Lot #' + lotId))
                + ' · day 0 = ' + prettyDate(LOT_DAY_ZERO_DATES[lotId]);
            sel.appendChild(opt);
        });

        const cards = $qsa(`#activitiesList .date-group[data-date="${dateKey}"] .activity-card[data-id]`);
        $id('moveGroupDasCount').textContent = cards.length;
        $id('moveGroupDasCurrent').textContent = prettyDateFull(dateKey);
        $id('moveGroupDasOld').value = dateKey;
        // Start from where the day already sits, so the field reads as an edit.
        $id('moveGroupDasValue').value = _dateStrToDas(dateKey, LOT_DAY_ZERO_DATES[lotIds[0]]);
        refreshMoveGroupDasPreview();
        openSheet('moveGroupDasSheet');
    }

    $id('moveGroupDasRefLot')?.addEventListener('change', () => {
        // Keep the same calendar day when the reference lot changes.
        const anchor = moveGroupDasAnchor();
        if (anchor && MOVE_DAS_DATE) $id('moveGroupDasValue').value = _dateStrToDas(MOVE_DAS_DATE, anchor);
        refreshMoveGroupDasPreview();
    });
    $id('moveGroupDasValue')?.addEventListener('input', refreshMoveGroupDasPreview);

    $id('confirmMoveGroupDasBtn')?.addEventListener('click', async (e) => {
        const btn = e.currentTarget;   // null once the await resumes — capture it now
        const oldDate = ($id('moveGroupDasOld').value || '').trim();
        const anchor = moveGroupDasAnchor();
        const n = parseInt($id('moveGroupDasValue').value, 10);
        if (!oldDate || !anchor) return;
        if (isNaN(n)) { toast(`Enter a ${dayType()} number.`, 'error'); return; }

        const newDate = _dasToDateStr(n, anchor);
        if (!newDate) { toast('That day number is out of range.', 'error'); return; }
        if (newDate === oldDate) { toast('That is already the current date.', 'info'); return; }

        btn.disabled = true;
        try {
            if (await moveGroupToDate(oldDate, newDate)) closeSheet('moveGroupDasSheet');
        } finally {
            btn.disabled = false;
        }
    });

    /**
     * Move every activity of one date group to another date, preserving each
     * card's duration and order. Shared by the "change group date" sheet and by
     * dragging a date header onto another day.
     */
    async function moveGroupToDate(oldDate, newDate) {
        if (!oldDate || !newDate) return false;
        if (newDate === oldDate) {
            toast('That is already the current date.', 'info');
            return false;
        }
        const allCards = $qsa(`#activitiesList .date-group[data-date="${oldDate}"] .activity-card[data-id]`);
        // Done activities are locked in place — a whole-day move leaves them behind.
        const cards = allCards.filter((c) => c.getAttribute('data-is-done') !== '1');
        const lockedCount = allCards.length - cards.length;
        if (allCards.length === 0) {
            toast('No activities to move.', 'error');
            return false;
        }
        if (cards.length === 0) {
            toast('Every activity on this day is marked done — they stay locked in place.', 'info');
            return false;
        }

        const delta = isoDaysBetween(oldDate, newDate);
        const snapshot = captureBoardSnapshot();
        const items = cards.map((card) => {
            const oldEnd = (card.getAttribute('data-target-end-date') || '').trim();
            return {
                id: parseInt(card.getAttribute('data-id'), 10),
                targetDate: newDate,
                targetEndDate: oldEnd ? isoAddDays(oldEnd, delta) : null,
                sequenceOrder: parseInt(card.getAttribute('data-sequence-order'), 10) || 0,
            };
        });

        try {
            await api(U.reorder(), { method: 'POST', body: { items } });
            items.forEach((it) => {
                const el = $qs(`#activitiesList .activity-card[data-id="${it.id}"]`);
                if (!el) return;
                el.setAttribute('data-target-date', it.targetDate);
                el.setAttribute('data-target-end-date', it.targetEndDate || '');
            });
            reorderAndRenumberActivities();
            recomputeLotDayZero();
            toast(`Moved ${items.length} ${items.length === 1 ? 'activity' : 'activities'} to ${newDate}`
                + (lockedCount ? ` · ${lockedCount} done stayed locked` : ''));
            pushUndo(`Move group from ${oldDate} to ${newDate}`, () => restoreBoardSnapshot(snapshot));
            return true;
        } catch (err) {
            toast(err.message, 'error');
            return false;
        }
    }

    $id('confirmChangeGroupDateBtn')?.addEventListener('click', async (e) => {
        const btn = e.currentTarget;
        const oldDate = ($id('changeGroupDateOld').value || '').trim();
        const newDate = ($id('changeGroupDateNew').value || '').trim();
        if (!oldDate) return;
        if (!newDate) {
            toast('Pick a new date.', 'error');
            return;
        }

        btn.disabled = true;
        try {
            if (await moveGroupToDate(oldDate, newDate)) {
                closeSheet('changeGroupDateSheet');
            }
        } finally {
            btn.disabled = false;
        }
    });

    async function deleteDateGroup(dateKey) {
        if (!dateKey || dateKey === '__no-date__') return;
        const cards = $qsa(`#activitiesList .date-group[data-date="${dateKey}"] .activity-card[data-id]`);
        if (cards.length === 0) {
            toast('No activities to delete in this group.', 'error');
            return;
        }
        const targets = cards.map((card) => ({
            id: parseInt(card.getAttribute('data-id'), 10),
            name: ($qs('h3', card)?.textContent || '').trim() || ('Activity #' + card.getAttribute('data-id')),
        }));
        const ok = await confirmAction({
            title: 'Delete entire date group',
            message: `Delete all ${targets.length} ${targets.length === 1 ? 'activity' : 'activities'} on ${prettyDateFull(dateKey)}?`,
            detail: 'You can immediately undo this (Ctrl+Z) — every activity is soft-deleted and can be restored together.',
            confirmText: targets.length === 1 ? 'Delete 1 Activity' : `Delete ${targets.length} Activities`,
        });
        if (!ok) return;

        // Parallel deletes; only act on the ones that succeed.
        const results = await Promise.all(targets.map((t) =>
            api(U.destroy(t.id), { method: 'DELETE' })
                .then(() => ({ id: t.id, name: t.name, ok: true }))
                .catch(() => ({ id: t.id, name: t.name, ok: false }))
        ));
        const succeeded = results.filter((r) => r.ok);
        const failed = results.filter((r) => !r.ok);

        succeeded.forEach((r) => $qs(`#activitiesList .activity-card[data-id="${r.id}"]`)?.remove());
        reorderAndRenumberActivities();
        recomputeLotDayZero();

        if (succeeded.length > 0) toast(`Deleted ${succeeded.length} ${succeeded.length === 1 ? 'activity' : 'activities'} on ${dateKey}`);
        if (failed.length > 0) toast(`${failed.length} could not be deleted — refresh and try again.`, 'error');

        if (succeeded.length > 0) {
            const ids = succeeded.map((r) => r.id);
            const label = succeeded.length === 1
                ? `Delete '${succeeded[0].name}'`
                : `Delete ${succeeded.length} activities on ${dateKey}`;
            pushUndo(label, async () => {
                const restored = (await Promise.all(ids.map((id) =>
                    api(U.restore(id), { method: 'POST' }).then((r) => r.data).catch(() => null)
                ))).filter(Boolean);
                if (restored.length === 0) throw new Error('no activities could be restored');
                restored.forEach((d) => _renderCardOrReplace(d));
            }, async () => {
                const gone = (await Promise.all(ids.map((id) =>
                    api(U.destroy(id), { method: 'DELETE' }).then(() => id).catch(() => null)
                ))).filter(Boolean);
                if (gone.length === 0) throw new Error('no activities could be deleted');
                gone.forEach((id) => _removeCardById(id));
            });
        }
    }

    /* ================================================================
     * 9. DATE NOTES (per-date, version-scoped)
     * ================================================================ */

    function _dateNoteContentFor(dateKey) {
        const block = $qs(`#activitiesList .date-note-block[data-date="${dateKey}"]`);
        if (!block || block.style.display === 'none') return '';
        // data-content is authoritative WHEN PRESENT, empty included: an
        // attachments-only note has "" there, and falling through || to
        // block.innerHTML fed the note's own chrome — chips, edit and delete
        // buttons — back into the editor as if it were the note's words.
        if (block.hasAttribute('data-content')) return (block.getAttribute('data-content') || '').trim();
        return (block.innerHTML || '').trim();
    }
    function _dateNoteMediaFor(dateKey) {
        const block = $qs(`#activitiesList .date-note-block[data-date="${dateKey}"]`);
        if (!block) return [];
        try { return JSON.parse(block.getAttribute('data-media') || '[]'); } catch (_) { return []; }
    }

    function _refreshDateNoteUI(dateKey, content, media) {
        const btn = $qs(`#activitiesList .date-note-btn[data-date="${dateKey}"]`);
        const block = $qs(`#activitiesList .date-note-block[data-date="${dateKey}"]`);
        const safe = String(content || '').trim();
        const mediaArr = media || [];
        const has = safe !== '' || mediaArr.length > 0;
        if (btn) { btn.classList.toggle('has-note', has); }
        if (block) {
            block.setAttribute('data-content', safe);
            block.setAttribute('data-media', JSON.stringify(mediaArr));
            // Update only the content wrapper so the edit/delete buttons survive.
            const inner = block.querySelector('.date-note-inner') || block;
            // Tags, not tiles: a drawing attached to a day's note is a thing
            // the note refers to, and a full-width picture of it buries the
            // words that explain why it is there. The chips live NEXT TO the
            // inner, not in it — the phone clamps the inner to one line, and
            // chips inside it were trimmed away with the words.
            inner.innerHTML = safe;
            let mediaBox = block.querySelector('.date-note-media');
            if (mediaArr.length) {
                if (!mediaBox) {
                    mediaBox = document.createElement('div');
                    mediaBox.className = 'date-note-media';
                    inner.after(mediaBox);
                }
                mediaBox.innerHTML = inlineAttachments(mediaArr);
            } else if (mediaBox) {
                mediaBox.remove();
            }
            block.style.display = has ? '' : 'none';
        }
    }

    // ---- Date-note WYSIWYG (Quill, reused toolbar) ----
    let dateNoteQuill = null;
    function ensureDateNoteEditor() {
        if (typeof Quill === 'undefined' || dateNoteQuill) return;
        dateNoteQuill = new Quill('#dateNoteEditor', {
            theme: 'snow',
            placeholder: 'What happens on this day?',
            // The app's one toolbar (resources/js/app.js), read at build time
            // because this script parses before the module bundle runs.
            modules: { toolbar: window.SM_RICH_TOOLBAR },
        });
        // This editor opens read-only on touch so the keyboard does not cover
        // the note; without this a toolbar tap would format nothing.
        window.smQuillTouch?.(dateNoteQuill);
    }
    function setDateNoteContent(html) {
        ensureDateNoteEditor();
        if (!dateNoteQuill) return;
        dateNoteQuill.setContents([]);
        if (html && html.trim() !== '') dateNoteQuill.clipboard.dangerouslyPasteHTML(html);

        disarmDateNoteEditorOnTouch();
    }

    /**
     * On a phone, open the note read-only.
     *
     * Chasing the focus did not work: Quill takes it while mounting, again when
     * dangerouslyPasteHTML() drops the caret in, and a blur on a timer has to
     * win a race it does not reliably win. A contenteditable element is what
     * the keyboard follows, so simply do not present one until it is wanted —
     * then nothing can steal focus, because there is nothing focusable.
     *
     * The first tap on the editor turns editing on and puts the caret where the
     * finger landed, so writing a note still takes one tap, exactly as before.
     */
    function disarmDateNoteEditorOnTouch() {
        if (!dateNoteQuill || !window.matchMedia('(pointer: coarse)').matches) return;

        const root = dateNoteQuill.root;
        if (!root) return;

        root.setAttribute('contenteditable', 'false');
        try { dateNoteQuill.blur(); } catch (_) {}
        root.blur?.();

        const arm = () => {
            root.setAttribute('contenteditable', 'true');
            // Focus after the tap has been processed, so the caret lands where
            // it was tapped instead of at the start of the note.
            setTimeout(() => { try { dateNoteQuill.focus(); } catch (_) {} }, 0);
        };
        root.addEventListener('pointerdown', arm, { once: true });
    }
    function getDateNoteContent() {
        if (!dateNoteQuill) return '';
        const html = dateNoteQuill.root.innerHTML;
        return html === '<p><br></p>' ? '' : html;
    }

    function openDateNoteSheet(dateKey) {
        dateKey = (dateKey || '').trim();
        if (!dateKey || dateKey === '__no-date__') dateKey = isoFromDate(new Date());
        const existing = _dateNoteContentFor(dateKey);
        $id('dateNoteDate').value = dateKey;
        if ($id('dateNoteDatePicker')) $id('dateNoteDatePicker').value = dateKey;
        $id('dateNoteSheetTitle').textContent = existing ? 'Edit note' : 'Add note';
        $id('dateNoteClearBtn').classList.toggle('hidden', !existing);
        openSheet('dateNoteSheet');
        setDateNoteContent(existing);
        // On a phone the keyboard would cover the note you just tapped to read,
        // before you have decided whether to change it. Skipping our own focus
        // call is not enough: mounting Quill and filling it takes focus by
        // itself, so hand it back to nothing once the sheet has settled. A
        // mouse still lands ready to type, and a deliberate tap on the editor
        // brings the keyboard up as expected.
        // Touch opens read-only (see disarmDateNoteEditorOnTouch), so there is
        // nothing to blur here — and a delayed blur would now fire after a
        // quick tap and shut the keyboard just as you started typing.
        if (!window.matchMedia('(pointer: coarse)').matches) {
            setTimeout(() => dateNoteQuill && dateNoteQuill.focus(), 250);
        }
    }

    // The per-day note now uses the shared rich editor (draw + emoji + media).
    function openDateNoteEditor(dateKey) {
        dateKey = (dateKey || '').trim();
        if (!dateKey || dateKey === '__no-date__') dateKey = isoFromDate(new Date());
        const existingBody = _dateNoteContentFor(dateKey);
        const existingMedia = _dateNoteMediaFor(dateKey);
        const hasExisting = (String(existingBody || '').trim() !== '')
            || (Array.isArray(existingMedia) && existingMedia.length > 0);
        window.openNoteEditor({
            title: 'Note for this day',
            bodyHtml: existingBody,
            media: existingMedia,
            imageUploadUrl: U.noteImageUpload(),
            videoUploadUrl: U.noteVideoUpload(),
            drawUploadUrl: NOTES_DRAW_URL,
            deleteLabel: 'Delete note',
            // Show a Delete button only when there's an existing note; it asks
            // for confirmation before removing.
            onDelete: hasExisting ? () => confirmDeleteDateNote(dateKey) : null,
            tags: noteTagChoices(dateKey),
            onSave: ({ body, media, lotId, activityId }) => saveDateNoteMedia(dateKey, body, media, lotId, activityId),
        });
    }
    async function saveDateNoteMedia(dateKey, body, media, lotId, activityId) {
        try {
            const res = await api(U.dateNoteSave(), { method: 'POST', body: { noteDate: dateKey, noteContent: body, media, lotId: lotId || null, activityId: activityId || null } });
            const data = res && res.data;
            _refreshDateNoteUI(dateKey, data ? (data.noteContent || '') : '', data ? data.media : []);
            toast(data ? 'Note saved.' : 'Note cleared.');
        } catch (err) { toast(err.message, 'error'); }
    }

    document.addEventListener('click', (e) => {
        const btn = e.target.closest('.date-note-btn');
        if (btn) {
            e.preventDefault();
            addInlineNote(btn.getAttribute('data-date') || '');
            return;
        }
        // Explicit edit / delete buttons on the per-day note block.
        const dnEdit = e.target.closest('.date-note-edit');
        if (dnEdit) { e.preventDefault(); e.stopPropagation(); const b = dnEdit.closest('.date-note-block'); if (b) openDateNoteEditor(b.getAttribute('data-date') || ''); return; }
        const dnDel = e.target.closest('.date-note-del');
        if (dnDel) { e.preventDefault(); e.stopPropagation(); const b = dnDel.closest('.date-note-block'); if (b) confirmDeleteDateNote(b.getAttribute('data-date') || ''); return; }
        // Click the note body to edit it in the modal editor.
        const block = e.target.closest('.date-note-block[data-date]');
        if (block && block.style.display !== 'none' && !e.target.closest('a, .na, .nm, .date-note-edit, .date-note-del')) {
            e.preventDefault();
            const dk = block.getAttribute('data-date') || '';
            // Same rule as the inline note: on a phone the body is clamped to
            // one line, so tapping it opens the note to read, not to edit.
            // Reading stays open to everyone; the sheet just loses its Edit
            // button for someone who may not write.
            if (window.matchMedia('(pointer: coarse)').matches) {
                openNoteInfo(block, MAY_NOTE ? () => openDateNoteEditor(dk) : null);
                return;
            }
            if (!mayWriteNotes()) return;
            openDateNoteEditor(dk);
        }
    });

    // Delete a per-day note after a confirmation prompt.
    async function confirmDeleteDateNote(dateKey) {
        dateKey = (dateKey || '').trim();
        if (!dateKey) return;
        // The touch long-press gets here with no button to grey out, so the ✕
        // being greyed is not enough on its own. This gate covers this route
        // and no other — dateNoteDelete is also reached from moveNoteToDate and
        // from the drag that converts a per-day note, each gated where it sits.
        if (!mayWriteNotes()) return;
        const ok = (typeof confirmAction === 'function')
            ? await confirmAction({ title: 'Delete this note?', message: 'The note for this day will be permanently removed.', confirmText: 'Delete', danger: true })
            : confirm('Delete the note for this day?');
        if (!ok) return;
        try {
            await api(U.dateNoteDelete(), { method: 'DELETE', body: { noteDate: dateKey } });
            // Animate the note out before hiding it (the block is reused, so clean
            // up the leave class afterwards).
            const block = $qs(`#activitiesList .date-note-block[data-date="${dateKey}"]`);
            const done = () => { _refreshDateNoteUI(dateKey, '', []); if (block) block.classList.remove('list-item-leave'); };
            if (block && block.style.display !== 'none' && window.animateOut) window.animateOut(block, done);
            else done();
            toast('Note deleted.');
        } catch (err) { toast(err.message, 'error'); }
    }

    /* ---- Day-counter type converter: DAS / DAT / DAP, relabels all counters.
       Reachable from the toolbar dropdown AND the activity editor's By-DAS panel. ---- */
    (function dayTypeConverter() {
        const DAY_TYPE_URL = @json(route('sm.day-type')) + '?id=' + SCHEDULE_ID;
        const currentDayType = () => { const el = $qs('.day-type-label'); return ((el && el.textContent) || DAY_TYPE_DEFAULT).trim(); };
        function markCurrent(dt) {
            document.querySelectorAll('.day-type-opt[data-day-type], .das-dt-opt[data-day-type]')
                .forEach((o) => o.classList.toggle('is-current', o.getAttribute('data-day-type') === dt));
        }
        async function applyDayType(dt) {
            if (!dt || dt === currentDayType()) return;
            try {
                const res = await api(DAY_TYPE_URL, { method: 'POST', body: { dayType: dt } });
                // Update EVERY day-type label (toolbar, By-DAS tab, Start/End labels…).
                document.querySelectorAll('.day-type-label').forEach((el) => { el.textContent = dt; });
                markCurrent(dt);
                refreshActivityCardDasLabels();   // every counter relabels live
                toast((res && res.message) || ('Counters now use ' + dt + '.'));
            } catch (err) { toast((err && err.message) || 'Could not change the day type.', 'error'); }
        }
        markCurrent(currentDayType());

        // Toolbar dropdown.
        const btn = $id('dayTypeBtn');
        const menu = $id('dayTypeMenu');
        if (btn && menu) {
            const close = () => menu.classList.add('hidden');
            btn.addEventListener('click', (e) => { e.stopPropagation(); menu.classList.toggle('hidden'); });
            document.addEventListener('click', (e) => { if (!menu.classList.contains('hidden') && !e.target.closest('#dayTypeMenu, #dayTypeBtn')) close(); });
            menu.addEventListener('click', (e) => { const o = e.target.closest('.day-type-opt'); if (!o) return; close(); applyDayType(o.getAttribute('data-day-type')); });
        }
        // In-sheet switcher, inside the activity editor's By-DAS panel.
        $id('dasDayTypeSwitch')?.addEventListener('click', (e) => { const o = e.target.closest('.das-dt-opt'); if (o) applyDayType(o.getAttribute('data-day-type')); });
    })();
    // Toolbar quick "Add Note" — defaults to today, date is changeable.
    $id('addDateNoteBtn')?.addEventListener('click', () => openDateNoteSheet(''));
    // Picking a different date loads that date's existing note.
    $id('dateNoteDatePicker')?.addEventListener('change', () => {
        const dk = ($id('dateNoteDatePicker').value || '').trim();
        if (!dk) return;
        $id('dateNoteDate').value = dk;
        const existing = _dateNoteContentFor(dk);
        setDateNoteContent(existing);
        $id('dateNoteSheetTitle').textContent = existing ? 'Edit note' : 'Add note';
        $id('dateNoteClearBtn').classList.toggle('hidden', !existing);
    });

    $id('dateNoteSaveBtn')?.addEventListener('click', async (e) => {
        const btn = e.currentTarget;
        const dateKey = $id('dateNoteDate').value;
        const content = getDateNoteContent();
        if (!dateKey) return;
        btn.disabled = true;
        try {
            const res = await api(U.dateNoteSave(), { method: 'POST', body: { noteDate: dateKey, noteContent: content } });
            // This sheet edits only the words; the server keeps the note's
            // attachments and its answer says what the note now holds.
            const d = res && res.data;
            _refreshDateNoteUI(dateKey, d ? (d.noteContent || '') : '', d ? (d.media || []) : []);
            toast(d ? 'Note saved.' : 'Note cleared.');
            closeSheet('dateNoteSheet');
        } catch (err) {
            toast(err.message, 'error');
        } finally {
            btn.disabled = false;
        }
    });

    $id('dateNoteClearBtn')?.addEventListener('click', async (e) => {
        const btn = e.currentTarget;
        const dateKey = $id('dateNoteDate').value;
        if (!dateKey) return;
        btn.disabled = true;
        try {
            await api(U.dateNoteDelete(), { method: 'DELETE', body: { noteDate: dateKey } });
            _refreshDateNoteUI(dateKey, '');
            toast('Note cleared.');
            closeSheet('dateNoteSheet');
        } catch (err) {
            toast(err.message, 'error');
        } finally {
            btn.disabled = false;
        }
    });

    // Draw → upload the sketch → embed it straight into this day's note.
    $id('dateNoteDrawBtn')?.addEventListener('click', () => {
        if (typeof window.openDrawCanvas !== 'function') { toast('Drawing tool unavailable.', 'error'); return; }
        window.openDrawCanvas(async (dataUrl) => {
            try {
                const res = await api(@json(route('notes.hub.draw')), { method: 'POST', body: { image: dataUrl } });
                const url = res && res.data && res.data.url;
                if (!url) throw new Error('Upload failed.');
                ensureDateNoteEditor();
                if (dateNoteQuill) {
                    const range = dateNoteQuill.getSelection(true) || { index: dateNoteQuill.getLength() };
                    dateNoteQuill.insertEmbed(range.index, 'image', url, 'user');
                    dateNoteQuill.setSelection(range.index + 1, 0);
                }
                toast('Drawing added.');
            } catch (err) {
                toast(err.message || 'Could not add drawing.', 'error');
            }
        });
    });

    /* ================================================================
     * 9b. PER-DAY EXTRA EXPENSES (amount + note)
     * ================================================================ */

    const DAY_EXPENSES = (window.DAY_EXPENSES && typeof window.DAY_EXPENSES === 'object' && !Array.isArray(window.DAY_EXPENSES))
        ? window.DAY_EXPENSES : {};
    window.DAY_EXPENSES = DAY_EXPENSES;

    function _expenseRowsFor(dateKey) {
        const rows = DAY_EXPENSES[dateKey];
        return Array.isArray(rows) ? rows : [];
    }

    // Render one day's expense strip from DAY_EXPENSES. Hoisted so the JS board
    // renderer (renderDateGroup) can paint freshly-created day groups too.
    function renderExpenseBlock(block) {
        if (!block) return;
        const dateKey = (block.getAttribute('data-date') || '').trim();
        if (!dateKey || dateKey === '__no-date__') { block.innerHTML = ''; return; }
        const rows = _expenseRowsFor(dateKey);
        // Adding is done from the day-header coin icon now — the strip only
        // lists what's already logged (edit/delete inline).
        if (!rows.length) { block.innerHTML = ''; return; }
        const total = rows.reduce((s, r) => s + Number(r.amount || 0), 0);
        const items = rows.map((r) => `<div class="dx-row" data-expense-id="${r.id}">
            <span class="dx-amt">₱${esc(fmtMoney(r.amount))}</span>
            <span class="dx-note">${r.note ? esc(r.note) : '<span style="opacity:.55">No note</span>'}</span>
            <span class="dx-actions">
                <button type="button" class="dx-btn dx-edit" data-expense-edit="${r.id}" data-date="${esc(dateKey)}" title="Edit expense">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                </button>
                <button type="button" class="dx-btn dx-del" data-expense-del="${r.id}" data-date="${esc(dateKey)}" title="Delete expense">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </span>
        </div>`).join('');
        block.innerHTML = `<div class="dx-card">
            <div class="dx-head"><span>💸 Extra expenses</span><span class="dx-total">₱${esc(fmtMoney(total))}</span></div>
            <div class="dx-list">${items}</div>
        </div>`;
    }

    function renderExpenseBlockFor(dateKey) {
        $qsa(`#activitiesList .day-expense-block[data-date="${dateKey}"]`).forEach(renderExpenseBlock);
        if (typeof window.renderDayIncome === 'function') window.renderDayIncome(dateKey);
    }

    function hydrateAllExpenseBlocks() {
        $qsa('#activitiesList .day-expense-block').forEach(renderExpenseBlock);
        $qsa('#activitiesList .day-income-block[data-date]').forEach((el) => {
            if (typeof window.renderDayIncome === 'function') window.renderDayIncome(el.getAttribute('data-date'));
        });
    }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', hydrateAllExpenseBlocks, { once: true });
    else hydrateAllExpenseBlocks();

    function openExpenseSheet(dateKey, expenseId) {
        if (!dateKey || dateKey === '__no-date__') return;
        const existing = expenseId
            ? _expenseRowsFor(dateKey).find((r) => String(r.id) === String(expenseId))
            : null;
        $id('dayExpenseDate').value = dateKey;
        $id('dayExpenseId').value = existing ? existing.id : '';
        $id('dayExpenseForDate').textContent = 'For ' + prettyDateFull(dateKey);
        $id('dayExpenseAmount').value = existing ? existing.amount : '';
        $id('dayExpenseNote').value = existing ? (existing.note || '') : '';
        $id('dayExpenseSheetTitle').textContent = existing ? 'Edit expense' : 'Add extra expense';
        $id('dayExpenseDeleteBtn').classList.toggle('hidden', !existing);
        openSheet('dayExpenseSheet');
        window.smFocus($id('dayExpenseAmount'), { delay: 250 });
    }

    async function deleteExpense(dateKey, expenseId) {
        if (!expenseId) return;
        const ok = await confirmAction({
            title: 'Delete expense?',
            message: 'This extra expense will be removed from this day.',
            confirmText: 'Delete',
            danger: true,
        });
        if (!ok) return;
        try {
            const res = await api(U.dayExpenseDelete(), { method: 'DELETE', body: { expenseId } });
            DAY_EXPENSES[dateKey] = (res && res.data) || [];
            renderExpenseBlockFor(dateKey);
            toast('Expense deleted.');
        } catch (err) {
            toast(err.message, 'error');
        }
    }

    // Delegated: add/edit/delete buttons live inside dynamically-rendered strips.
    document.addEventListener('click', (e) => {
        const hdrExp = e.target.closest('.day-expense-btn');
        if (hdrExp) { e.preventDefault(); openExpenseSheet(hdrExp.getAttribute('data-date') || '', ''); return; }
        const add = e.target.closest('[data-expense-add]');
        if (add) { openExpenseSheet(add.getAttribute('data-expense-add') || '', ''); return; }
        const edit = e.target.closest('[data-expense-edit]');
        if (edit) { openExpenseSheet(edit.getAttribute('data-date') || '', edit.getAttribute('data-expense-edit')); return; }
        const del = e.target.closest('[data-expense-del]');
        if (del) { deleteExpense(del.getAttribute('data-date') || '', del.getAttribute('data-expense-del')); return; }
    });

    $id('dayExpenseSaveBtn')?.addEventListener('click', async (e) => {
        const btn = e.currentTarget;
        const dateKey = $id('dayExpenseDate').value;
        const expenseId = $id('dayExpenseId').value;
        const amount = parseFloat($id('dayExpenseAmount').value);
        const note = $id('dayExpenseNote').value.trim();
        if (!dateKey) return;
        if (Number.isNaN(amount) || amount < 0) { toast('Enter a valid amount.', 'error'); return; }
        btn.disabled = true;
        const origLabel = btn.innerHTML;
        btn.innerHTML = '<svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/></svg> Saving…';
        try {
            const res = await api(U.dayExpenseSave(), {
                method: 'POST',
                body: { expenseId: expenseId || '', expenseDate: dateKey, amount, note },
            });
            DAY_EXPENSES[dateKey] = (res && res.data) || [];
            renderExpenseBlockFor(dateKey);
            toast(res.message || 'Expense saved.');
            closeSheet('dayExpenseSheet');
        } catch (err) {
            toast(err.message, 'error');
        } finally {
            btn.disabled = false;
            btn.innerHTML = origLabel;
        }
    });

    $id('dayExpenseDeleteBtn')?.addEventListener('click', (e) => {
        const dateKey = $id('dayExpenseDate').value;
        const expenseId = $id('dayExpenseId').value;
        if (!expenseId) return;
        closeSheet('dayExpenseSheet');
        deleteExpense(dateKey, expenseId);
    });

    /* ================================================================
     * 9c. WEATHER — per-lot 6-day forecast (Open-Meteo, server-cached).
     * Chips on the current-week date headers (+ empty rest days), and a
     * modal listing each lot's own forecast. Fetched once, after paint.
     * ================================================================ */
    (function activityWeather() {
        let WX = null;            // { locations, lots }
        let wxByDate = null;      // isoDate → [{ key, place, day }]  (distinct locations)
        let WX_LOT_PLACE = {};    // lotId → locationKey
        let loaded = false;

        let loadedHours = false;
        /* The day-header chips only need days, and hours would treble that
           payload on every board load — so they are fetched once, when the
           sheet that shows them is actually opened. */
        async function ensureWeather(withHours) {
            if (loaded && (!withHours || loadedHours)) return WX;
            loaded = true;
            if (withHours) loadedHours = true;
            try {
                const res = await fetch(U.weather() + (withHours ? '&hourly=1' : ''), { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
                const json = await res.json();
                WX = (json && json.success && json.data) ? json.data : null;
                // Every load writes the forecast to the store; take the dates
                // it wrote so the day menu offers them without a reload.
                (WX && WX.savedDates || []).forEach((d) => window.SAVED_WX_DATES?.add(d));
            } catch (_) { if (!withHours) WX = null; }
            buildByDate();
            // Answered, one way or the other — the board can be shown now.
            window.boardVeilStep?.('weather');
            return WX;
        }

        function buildByDate() {
            wxByDate = {};
            if (!WX || !WX.locations) return;
            Object.entries(WX.locations).forEach(([key, loc]) => {
                if (!loc || loc.ok === false || !loc.days) return;
                loc.days.forEach((d) => {
                    (wxByDate[d.date] = wxByDate[d.date] || []).push({ key, place: loc.place, day: d });
                });
            });
            // Which place each lot sits in, so a day can be shown the weather
            // for the ground its work is actually on.
            WX_LOT_PLACE = {};
            (WX.lots || []).forEach((lot) => {
                if (lot && lot.id && lot.locationKey) WX_LOT_PLACE[String(lot.id)] = lot.locationKey;
            });
            // Per-lot, per-date forecast for the day-reminder rain rule.
            const perLot = {};
            (WX.lots || []).forEach((lot) => {
                const loc = lot.locationKey ? WX.locations[lot.locationKey] : null;
                if (!loc || loc.ok === false || !loc.days) return;
                const m = {};
                loc.days.forEach((d) => { m[d.date] = d; });
                perLot[lot.id] = m;
            });
            WX_BY_LOT_DATE = perLot;
            if (typeof refreshDayWarnings === 'function') refreshDayWarnings();
        }

        /* Which sky, over which ground, and how warm. An icon on its own was
         * tried and is not enough: two lots with different weather look
         * identical when both are a cloud, and the place name is half the
         * point of having a forecast per lot at all. */
        function wxChip(place, d) {
            const bits = [esc(place), esc(d.text)];
            if (d.max != null) bits.push(d.max + '°' + (d.min != null ? '/' + d.min + '°' : ''));
            if (d.pop != null) bits.push('💧' + d.pop + '%');
            const label = bits.join(' · ');
            return `<span class="wx-chip js-wx-chip" title="${label}" aria-label="${label}">`
                + `<span class="wx-emoji">${d.emoji}</span><span class="wx-loc">${esc(place)}</span>`
                + (d.max != null ? `<span class="wx-temp">${d.max}°</span>` : '') + '</span>';
        }

        /**
         * The forecast for one day.
         *
         * Only the places that day's work is actually on: a day whose single
         * activity is in Apartado has no use for Masin's sky, and a row of
         * forecasts for ground nobody is standing on is worse than noise —
         * it is wrong often enough to be believed.
         *
         * A day with no lot-specific work (or no work at all) falls back to
         * every place, because nothing has said otherwise.
         */
        function stripFor(dateKey, scope) {
            let list = wxByDate && wxByDate[dateKey];
            if (!list || !list.length) return null;

            if (scope) {
                const wanted = new Set();
                scope.querySelectorAll('.activity-card [data-lot-id]').forEach((tag) => {
                    const key = WX_LOT_PLACE[String(tag.getAttribute('data-lot-id'))];
                    if (key) wanted.add(key);
                });
                if (wanted.size) {
                    const only = list.filter((x) => wanted.has(x.key));
                    if (only.length) list = only;
                }
            }

            const strip = document.createElement('div');
            strip.className = 'date-header-weather scroll-chips';
            // What it was built for, so a day that later gains or loses a lot
            // can be told apart from one that has not changed.
            strip.dataset.wxFor = list.map((x) => x.key).sort().join(',');
            strip.innerHTML = list.map((x) => wxChip(x.place, x.day)).join('');
            return strip;
        }

        /** The places a day's work is on right now, as stripFor would compute them. */
        function placesFor(dateKey, scope) {
            const list = (wxByDate && wxByDate[dateKey]) || [];
            const wanted = new Set();
            scope.querySelectorAll('.activity-card [data-lot-id]').forEach((tag) => {
                const key = WX_LOT_PLACE[String(tag.getAttribute('data-lot-id'))];
                if (key) wanted.add(key);
            });
            const only = wanted.size ? list.filter((x) => wanted.has(x.key)) : list;
            return (only.length ? only : list).map((x) => x.key).sort().join(',');
        }

        /* A farm with many lots has many forecasts, and on a phone the strip
           then runs off the edge — a row of chips you can only reach by
           swiping something most people never try. When it genuinely does not
           fit, it becomes one button showing the day's first sky, which opens
           the full per-lot forecast. Measured, not guessed: only a strip that
           actually overflows is folded away. */
        function collapseIfCramped(strip) {
            if (!window.matchMedia('(max-width: 767px)').matches) return;
            // Two frames: the first lets the break and the cost pill take their
            // places, the second measures what is left. One frame measured a
            // half-built row and folded strips that fit perfectly well.
            requestAnimationFrame(() => requestAnimationFrame(() => {
                if (!strip.isConnected || strip.scrollWidth <= strip.clientWidth + 4) return;
                const first = strip.querySelector('.wx-emoji');
                const n = strip.querySelectorAll('.js-wx-chip').length;
                // One forecast is never worth hiding. "Weather 1" tells you
                // nothing you did not already know — the day has weather —
                // where the chip itself tells you what it is. If it is a
                // little too wide it can scroll, which is what the strip is
                // for.
                if (n <= 1) return;
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'wx-mini-btn';
                btn.title = 'Weather for each lot';
                btn.setAttribute('aria-label', 'Weather for each lot');
                // "3 lots", not "Weather 3": the icon already says weather,
                // and the number means places, which the word never did.
                btn.innerHTML = '<span class="wx-emoji">' + (first ? first.textContent : '⛅') + '</span>'
                    + '<span>' + n + ' lots</span>';
                btn.dataset.wxFor = strip.dataset.wxFor || '';
                btn.addEventListener('click', (e) => {
                    e.stopPropagation();          // not a fold of the day
                    $id('weatherBtn')?.click();
                });
                strip.replaceWith(btn);
            }));
        }

        // Decorate every in-window date header + empty rest-day marker.
        // A day already wearing its forecast, in either form. The strip becomes
        // a button when it does not fit (see collapseIfCramped), so looking
        // only for the strip meant a collapsed day counted as bare — and got
        // decorated again, and again, because the replacement is itself a DOM
        // change that wakes this up. That is where "Weather 1  Weather 1" came
        // from.
        const WX_ANY = '.date-header-weather, .wx-mini-btn';

        function renderHeaderWeather() {
            if (!wxByDate) return;
            $qsa('#activitiesList .date-group[data-date]').forEach((g) => {
                const header = $qs('.date-header', g);
                if (! header) return;
                // Heal a header that already collected duplicates, so a board
                // showing them does not need a reload to come right.
                const dateKey = (g.getAttribute('data-date') || '').trim();
                const already = header.querySelectorAll(WX_ANY);
                if (already.length) {
                    for (let i = 1; i < already.length; i++) already[i].remove();
                    // Move an activity to another lot and the day is about
                    // different ground now — the forecast has to follow.
                    const want = placesFor(dateKey, g);
                    if ((already[0].dataset.wxFor || '') === want) return;
                    already[0].remove();
                }
                const strip = stripFor(dateKey, g);
                if (!strip) return;
                // The break first: without it the strip lands on the crowded
                // top row for a moment, and measuring it there is what folded
                // forecasts that had a whole line waiting for them.
                window.__ensureRowBreak?.(header, true);
                header.appendChild(strip);
                collapseIfCramped(strip);
            });
            $qsa('#activitiesList .rest-day-marker[data-date]').forEach((m) => {
                const seen = m.querySelectorAll(WX_ANY);
                if (seen.length) {
                    for (let i = 1; i < seen.length; i++) seen[i].remove();
                    return;
                }
                const strip = stripFor((m.getAttribute('data-date') || '').trim());
                if (!strip) return;
                strip.classList.add('rest-day-weather');
                (m.querySelector('.grow') || m).appendChild(strip);
            });
        }

        /* The sheet shows the SAME panels as the Weather module — the six
           days, and the hours inside whichever one you open — through the
           shared renderer, so there is one implementation of the forecast
           rather than two that drift. */
        function renderModal() {
            const body = $id('weatherBody');
            if (!body || typeof window.wxRenderPanels !== 'function') return;
            window.wxRenderPanels(body, WX || {});
        }

        async function openModal() {
            openSheet('weatherSheet');
            await ensureWeather(true);
            renderModal();
        }
        $id('weatherBtn')?.addEventListener('click', openModal);
        document.addEventListener('click', (e) => { if (e.target.closest('.js-wx-chip')) { e.preventDefault(); openModal(); } });

        // A named way in, so the board can ask for a redraw after it has
        // rearranged itself rather than waiting to be noticed.
        window.__wxRepaint = renderHeaderWeather;

        /* Folding is a decision about the width available, and that changes:
           turn the phone, or open the same board on a wider screen, and a
           button that was the right answer is now hiding chips that fit. The
           buttons are dropped on resize so the next pass measures again. */
        let wxResize = null;
        window.addEventListener('resize', () => {
            clearTimeout(wxResize);
            wxResize = setTimeout(() => {
                $qsa('#activitiesList .wx-mini-btn').forEach((b) => b.remove());
                renderHeaderWeather();
            }, 200);
        });

        // Re-decorate whenever the board changes (add activity, new day, a
        // rest-day turning into a group, drag/reorder, calendar → list…).
        // renderHeaderWeather() is idempotent, so re-running is safe + cheap.
        const list = document.getElementById('activitiesList');
        if (list && window.MutationObserver) {
            let pending = false;
            new MutationObserver((records) => {
                // Ignore what the two decorators do to each other: the cash
                // line's break element and the forecast's own collapse are
                // both DOM changes, and reacting to them is how a loop starts.
                if (records.every(ownBookkeeping)) return;
                if (pending) return;
                pending = true;
                requestAnimationFrame(() => { pending = false; renderHeaderWeather(); });
            }).observe(list, { childList: true, subtree: true });
        }

        // On load: fetch once (after paint), then decorate the headers.
        (async () => { await ensureWeather(); renderHeaderWeather(); })();
    })();

    /* ================================================================
     * 10. PROGRESS MARKERS ("resume here" bookmarks)
     * ================================================================ */

    function _markerInfoFor(dateKey) {
        const row = $qs(`#activitiesList .progress-marker[data-date="${dateKey}"]`);
        if (!row) return null;
        return {
            id: row.getAttribute('data-marker-id') || '',
            note: ($qs('.progress-marker-note', row)?.textContent || '').trim(),
        };
    }

    function _refreshProgressMarkerUI(dateKey, info) {
        const btn = $qs(`#activitiesList .date-marker-btn[data-date="${dateKey}"]`);
        const row = $qs(`#activitiesList .progress-marker[data-date="${dateKey}"]`);
        if (!info) {
            if (btn) {
                btn.classList.remove('has-marker');
                btn.title = 'Drop a resume-here marker after this date';
            }
            row?.remove();
            return;
        }
        if (btn) {
            btn.classList.add('has-marker');
            btn.title = 'Edit the resume-here marker';
        }
        const html = buildMarkerHtml(dateKey, info);
        if (row) {
            row.outerHTML = html;
        } else {
            const group = $qs(`#activitiesList .date-group[data-date="${dateKey}"]`);
            if (group) {
                group.insertAdjacentHTML('afterend', html);
            } else {
                $id('activitiesList').insertAdjacentHTML('beforeend', html);
            }
        }
    }

    function openMarkerSheet(dateKey) {
        const info = _markerInfoFor(dateKey);
        $id('progressMarkerDate').value = dateKey;
        $id('progressMarkerId').value = info ? info.id : '';
        $id('markerSheetDate').textContent = prettyDateFull(dateKey);
        $id('progressMarkerNote').value = info ? info.note : '';
        $id('markerSheetTitle').textContent = info ? 'Edit resume-here marker' : 'Drop resume-here marker';
        $id('progressMarkerClearBtn').classList.toggle('hidden', !info);
        openSheet('markerSheet');
        window.smFocus($id('progressMarkerNote'), { delay: 250 });
    }

    document.addEventListener('click', async (e) => {
        const openBtn = e.target.closest('.date-marker-btn, .progress-marker-edit-btn');
        if (openBtn) {
            e.preventDefault();
            // Ask before the sheet opens, not after they have written in it.
            if (!mayEditBoard()) return;
            const dateKey = (openBtn.getAttribute('data-date') || '').trim();
            if (dateKey && dateKey !== '__no-date__') openMarkerSheet(dateKey);
            return;
        }
        const delBtn = e.target.closest('.progress-marker-delete-btn');
        if (delBtn) {
            e.preventDefault();
            if (!mayEditBoard()) return;
            const markerId = delBtn.getAttribute('data-marker-id') || '';
            const dateKey = (delBtn.getAttribute('data-date') || '').trim();
            if (!markerId) return;
            const ok = await confirmAction({
                title: 'Remove resume-here marker',
                message: `Remove the marker on ${prettyDateFull(dateKey)}?`,
                detail: 'The note attached to it (if any) will be cleared too.',
                confirmText: 'Remove Marker',
            });
            if (!ok) return;
            try {
                await api(U.markerDelete(markerId), { method: 'DELETE' });
                _refreshProgressMarkerUI(dateKey, null);
                toast('Marker removed.');
            } catch (err) {
                toast(err.message, 'error');
            }
        }
    });

    $id('progressMarkerSaveBtn')?.addEventListener('click', async (e) => {
        const btn = e.currentTarget;
        const dateKey = $id('progressMarkerDate').value;
        const content = $id('progressMarkerNote').value;
        if (!dateKey) return;
        btn.disabled = true;
        try {
            const res = await api(U.markerSave(), { method: 'POST', body: { markerDate: dateKey, noteContent: content || '' } });
            const data = res.data || {};
            _refreshProgressMarkerUI(dateKey, { id: String(data.id || ''), note: data.noteContent || '' });
            toast('Marker saved.');
            closeSheet('markerSheet');
        } catch (err) {
            toast(err.message, 'error');
        } finally {
            btn.disabled = false;
        }
    });

    $id('progressMarkerClearBtn')?.addEventListener('click', async (e) => {
        const btn = e.currentTarget;
        const dateKey = $id('progressMarkerDate').value;
        const markerId = $id('progressMarkerId').value;
        if (!markerId) return;
        btn.disabled = true;
        try {
            await api(U.markerDelete(markerId), { method: 'DELETE' });
            _refreshProgressMarkerUI(dateKey, null);
            toast('Marker removed.');
            closeSheet('markerSheet');
        } catch (err) {
            toast(err.message, 'error');
        } finally {
            btn.disabled = false;
        }
    });

    /* ================================================================
     * 11. DRAG & DROP (desktop) — reorder within a date, move across
     * dates, or drop onto a rest-day marker. Multi-day cards keep their
     * duration; all groups renumber sequenceOrder = idx * 10; one POST.
     * ================================================================ */

    let dragSourceCard = null;
    let dragOrigin = null;
    let dragBoardSnapshot = null;
    let dragGroupDate = null; // set while dragging a date header (whole-day move)
    let dragNoteDate = null;   // set while dragging a date-note block to another day
    let dragMarkerDate = null; // set while dragging a resume-here marker to another day

    // Spinner + dim on the block whose save is in flight; a landing animation
    // on arrival so a moved note/marker never blank-snaps into place.
    function _setMoving(el, on) {
        if (!el) return;
        el.classList.toggle('is-moving', on);
        if (on) {
            if (!el.querySelector('.note-move-spin')) {
                const s = document.createElement('span');
                s.className = 'note-move-spin';
                s.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" d="M12 3a9 9 0 109 9"/></svg>';
                el.appendChild(s);
            }
        } else {
            el.querySelector('.note-move-spin')?.remove();
        }
    }
    function _land(el) { if (!el) return; el.classList.remove('note-landed'); void el.offsetWidth; el.classList.add('note-landed'); }

    // Move a date note to another day: write it on the target, clear the source.
    async function moveNoteToDate(sourceDate, targetDate) {
        if (!sourceDate || !targetDate || sourceDate === targetDate) return;
        // Two writes wearing a drag — a save on the target day and a delete on
        // the source — and neither goes anywhere near the pencil and ✕ the gate
        // greys out. This drop is something the person just did, so say why.
        if (!mayWriteNotes()) return;
        const content = _dateNoteContentFor(sourceDate);
        // The attachments move WITH the note — posting only the words was
        // silently deleting every chip the note carried, and a chips-only
        // note could not be moved at all.
        const media = _dateNoteMediaFor(sourceDate);
        if (!content && !media.length) return;
        if (_dateNoteContentFor(targetDate) || _dateNoteMediaFor(targetDate).length) { toast('That day already has a note.', 'error'); return; }
        const srcBlk = $qs(`#activitiesList .date-note-block[data-date="${sourceDate}"]`);
        _setMoving(srcBlk, true);
        try {
            const mediaSend = media.map((m) => ({ type: m.type, path: m.path, poster: m.poster || null, strokes: m.strokes || null }));
            await api(U.dateNoteSave(), { method: 'POST', body: { noteDate: targetDate, noteContent: content, media: mediaSend } });
            await api(U.dateNoteDelete(), { method: 'DELETE', body: { noteDate: sourceDate } });
            _setMoving(srcBlk, false);
            _refreshDateNoteUI(sourceDate, '', []);
            _refreshDateNoteUI(targetDate, content, media);
            _land($qs(`#activitiesList .date-note-block[data-date="${targetDate}"]`));
            toast('Note moved to ' + prettyDate(targetDate) + '.');
        } catch (err) { _setMoving(srcBlk, false); toast(err.message || 'Could not move the note.', 'error'); }
    }

    // Move a resume-here marker to another day (carries its note along).
    async function moveMarkerToDate(sourceDate, targetDate) {
        // Dragging a marker is two writes wearing one gesture — a save on the
        // new day and a delete on the old — and neither asked whether this
        // account may write. A drop is deliberate, so say why nothing moved.
        if (!mayEditBoard()) return;
        if (!sourceDate || !targetDate || sourceDate === targetDate) return;
        const info = _markerInfoFor(sourceDate);
        if (!info) return;
        if (_markerInfoFor(targetDate)) { toast('That day already has a marker.', 'error'); return; }
        const srcMk = $qs(`#activitiesList .progress-marker[data-date="${sourceDate}"]`);
        _setMoving(srcMk, true);
        try {
            const res = await api(U.markerSave(), { method: 'POST', body: { markerDate: targetDate, noteContent: info.note || '' } });
            if (info.id) { try { await api(U.markerDelete(info.id), { method: 'DELETE' }); } catch (_) { /* best effort */ } }
            _setMoving(srcMk, false);
            _refreshProgressMarkerUI(sourceDate, null);
            _refreshProgressMarkerUI(targetDate, { id: (res.data && res.data.id) || info.id, note: info.note || '' });
            _land($qs(`#activitiesList .progress-marker[data-date="${targetDate}"]`));
            toast('Marker moved to ' + prettyDate(targetDate) + '.');
        } catch (err) { _setMoving(srcMk, false); toast(err.message || 'Could not move the marker.', 'error'); }
    }

    /* ================================================================
     * 11a. INLINE STICKY NOTES — multiple per day, dropped between cards.
     * ================================================================ */
    let dragInlineEl = null;

    const NOTES_DRAW_URL = @json(route('notes.hub.draw'));
    const INLINE_GRIP = '<span class="inline-note-grip" aria-hidden="true"><svg viewBox="0 0 24 24" fill="currentColor" class="w-3.5 h-3.5"><circle cx="9" cy="6" r="1.5"/><circle cx="15" cy="6" r="1.5"/><circle cx="9" cy="12" r="1.5"/><circle cx="15" cy="12" r="1.5"/><circle cx="9" cy="18" r="1.5"/><circle cx="15" cy="18" r="1.5"/></svg></span>';
    const INLINE_TAG = '<span class="inline-note-tag" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M7 3h10a2 2 0 012 2v14a2 2 0 01-2 2H7a2 2 0 01-2-2V5a2 2 0 012-2zM9 8h6M9 12h6M9 16h3"/></svg>Note</span>';
    const INLINE_EDIT = '<button type="button" class="inline-note-edit" title="Edit note" aria-label="Edit note"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg></button>';
    const INLINE_DEL = '<button type="button" class="inline-note-del" title="Delete note" aria-label="Delete note"><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.9 12.1a2 2 0 01-2 1.9H7.9a2 2 0 01-2-1.9L5 7m3 0V5a2 2 0 012-2h4a2 2 0 012 2v2m-11 0h16"/></svg></button>';
    const DATE_NOTE_EDIT = '<button type="button" class="date-note-edit" title="Edit note" aria-label="Edit note"><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg></button>';
    const DATE_NOTE_DEL = '<button type="button" class="date-note-del" title="Delete note" aria-label="Delete note"><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.9 12.1a2 2 0 01-2 1.9H7.9a2 2 0 01-2-1.9L5 7m3 0V5a2 2 0 012-2h4a2 2 0 012 2v2m-11 0h16"/></svg></button>';

    function inlineMediaCells(mediaArr) {
        return window.noteMediaCells ? window.noteMediaCells(mediaArr) : '';
    }
    function inlineNoteMedia(el) {
        try { return JSON.parse(el.getAttribute('data-media') || '[]'); } catch (_) { return []; }
    }
    /** Attachments as chips. Twin of note-attachments.blade.php. */
    function inlineAttachments(mediaArr) {
        return window.noteAttachmentChips
            ? window.noteAttachmentChips(mediaArr || [])
            : inlineMediaCells(mediaArr);
    }
    function inlineTitleHtml(title) {
        return title ? '<div class="inline-note-title">' + esc(title) + '</div>' : '';
    }
    function setInlineNoteData(el, bodyHtml, mediaArr, title) {
        const old = el.querySelector('.inline-note-title');
        if (old) old.remove();
        const body = el.querySelector('.inline-note-body');
        body.innerHTML = bodyHtml || '';
        if (title) body.insertAdjacentHTML('beforebegin', inlineTitleHtml(title));
        el.querySelector('.inline-note-media').innerHTML = inlineAttachments(mediaArr);
        el.setAttribute('data-media', JSON.stringify(mediaArr || []));
        el.setAttribute('data-title', title || '');
        paintInlineNoteTags(el);
    }

    /** Remember what a note points at, and show it. */
    function setInlineNoteTags(el, lotId, activityId) {
        if (!el) return;
        if (lotId) el.setAttribute('data-lot-id', lotId); else el.removeAttribute('data-lot-id');
        if (activityId) el.setAttribute('data-activity-id', activityId); else el.removeAttribute('data-activity-id');
        paintInlineNoteTags(el);
    }

    /** Draw (or clear) the chips that say what a note is about. */
    function paintInlineNoteTags(el) {
        if (!el) return;
        const html = noteTagChips(
            parseInt(el.getAttribute('data-lot-id') || '', 10) || null,
            parseInt(el.getAttribute('data-activity-id') || '', 10) || null,
        );
        let host = el.querySelector('.inline-note-tags');
        if (!html) { host?.remove(); return; }
        if (!host) {
            host = document.createElement('div');
            host.className = 'inline-note-tags';
            const body = el.querySelector('.inline-note-body');
            body ? body.before(host) : el.appendChild(host);
        }
        host.innerHTML = html;
    }

    function buildInlineNote(id, bodyHtml, mediaArr, date, title) {
        const el = document.createElement('div');
        el.className = 'inline-note';
        if (id) el.setAttribute('data-inline-note', id);
        el.setAttribute('data-date', date);
        el.setAttribute('data-sort-key', '0');
        el.setAttribute('data-media', JSON.stringify(mediaArr || []));
        el.setAttribute('data-title', title || '');
        el.setAttribute('title', 'Drag the grip to move · tap the pencil to edit');
        el.innerHTML = INLINE_GRIP + INLINE_TAG
            + inlineTitleHtml(title)
            + '<div class="inline-note-body">' + (bodyHtml || '') + '</div>'
            + '<div class="inline-note-media">' + inlineAttachments(mediaArr) + '</div>'
            + INLINE_EDIT + INLINE_DEL;
        return el;
    }

    // A note's key sits between its DOM neighbours' orders (card seq or note key).
    function inlineNoteKey(el) {
        const orderOf = (n) => {
            if (!n || !n.matches) return null;
            if (n.matches('.activity-card[data-id]')) return parseInt(n.getAttribute('data-sequence-order') || '0', 10);
            if (n.matches('.inline-note')) return parseInt(n.getAttribute('data-sort-key') || '0', 10);
            return null;
        };
        let prev = el.previousElementSibling, next = el.nextElementSibling;
        while (prev && orderOf(prev) === null) prev = prev.previousElementSibling;
        while (next && orderOf(next) === null) next = next.nextElementSibling;
        const p = orderOf(prev), n = orderOf(next);
        if (p === null && n === null) return 0;
        if (p === null) return n - 5;
        if (n === null) return p + 5;
        return Math.floor((p + n) / 2);
    }

    function inlineDragPosition(container, cursorY, exclude) {
        exclude = exclude || dragInlineEl;
        const items = $qsa('.activity-card[data-id], .inline-note', container).filter((c) => c !== exclude);
        for (const it of items) { const r = it.getBoundingClientRect(); if (cursorY < r.top + r.height / 2) return it; }
        return null;
    }

    /* The writer is the gate, not the button. Every route into an inline note
       ends here — the editor, the pointer drag, the HTML5 drag, the touch drag,
       the re-key after a reorder — so a route added later inherits the rule
       instead of forgetting it. Silent, and answering false: the gestures a
       person actually started say their own piece, the reflow at render time is
       nobody's doing, and callers that move something on the board first need a
       yes/no so they can put it back. */
    async function saveInlineNote(el, date, key) {
        if (!MAY_NOTE) return false;
        const id = el.getAttribute('data-inline-note');
        const content = el.querySelector('.inline-note-body')?.innerHTML || '';
        // Strokes travel too: dragging a note was re-saving its media without
        // them, which flattened every editable drawing the note carried.
        const mediaSend = inlineNoteMedia(el).map((m) => ({ type: m.type, path: m.path, poster: m.poster || null, strokes: m.strokes || null }));
        _setMoving(el, true);
        try {
            const res = await api(U.inlineNoteSave(), { method: 'POST', body: {
                id: id ? parseInt(id, 10) : null, noteDate: date, sortKey: key,
                title: el.getAttribute('data-title') || '', content, media: mediaSend,
                lotId: parseInt(el.getAttribute('data-lot-id') || '', 10) || null,
                activityId: parseInt(el.getAttribute('data-activity-id') || '', 10) || null,
            } });
            _setMoving(el, false);
            if (res && res.data && res.data.id) {
                el.setAttribute('data-inline-note', res.data.id);
                el.setAttribute('data-sort-key', res.data.sortKey);
                if (res.data.media) setInlineNoteData(el, res.data.content != null ? res.data.content : content, res.data.media, res.data.title || el.getAttribute('data-title') || '');
                _land(el);
                return true;
            }
            if (res && res.removed) el.remove();
            return false;
        } catch (err) { _setMoving(el, false); toast(err.message || 'Could not save the note.', 'error'); return false; }
    }

    async function deleteInlineNote(el, silent) {
        // Same gate, same reason as saveInlineNote — and asked before the
        // confirmation, because there is no point putting a question to
        // somebody whose answer the server is going to refuse either way.
        if (!MAY_NOTE) return;
        // The ✕ sits a thumb's width from the drag grip, and a note is words
        // somebody chose to keep — it asks, the same way the per-day note
        // does. `silent` marks a programmatic cleanup, which never asks.
        if (!silent) {
            const ok = (typeof confirmAction === 'function')
                ? await confirmAction({ title: 'Delete this note?', message: 'The note and everything attached to it will be permanently removed.', confirmText: 'Delete', danger: true })
                : confirm('Delete this note?');
            if (!ok) return;
        }
        const id = el.getAttribute('data-inline-note');
        const finish = () => el.remove();
        if (window.animateOut) window.animateOut(el, finish); else finish();
        if (id) {
            try { await api(U.inlineNoteDelete(id), { method: 'DELETE' }); if (!silent) toast('Note removed.'); }
            catch (err) { toast(err.message, 'error'); }
        }
    }

    /* ---- What a note on this day can point at -------------------------
     * The lots belong to the schedule; the tasks belong to the day, read
     * off the board so the list is whatever is actually there. Both are
     * offered as things to tap, never as things to type. */
    function noteTagChoices(dateKey) {
        const lots = Object.keys(LOT_NAMES).map((id) => ({ id: parseInt(id, 10), name: LOT_NAMES[id] }));
        const group = dateKey ? $qs(`#activitiesList .date-group[data-date="${dateKey}"]`) : null;
        const activities = group
            ? $qsa('.activity-card[data-id]', group).map((c) => ({
                id: parseInt(c.getAttribute('data-id'), 10),
                title: (c.querySelector('.activity-card-title')?.textContent || '').trim() || 'Untitled task',
            })).filter((a) => a.id)
            : [];
        return { lots, activities };
    }

    /** The pointers a note is already carrying, read back off its element. */
    function noteTagsOf(el) {
        return {
            lotId: el ? (parseInt(el.getAttribute('data-lot-id') || '', 10) || null) : null,
            activityId: el ? (parseInt(el.getAttribute('data-activity-id') || '', 10) || null) : null,
        };
    }

    /** Paint the chips that say what a note is about. */
    function noteTagChips(lotId, activityId, dateKey) {
        let html = '';
        if (lotId && LOT_NAMES[lotId]) {
            html += '<span class="note-tag note-tag-lot">' + escapeHtml(LOT_NAMES[lotId]) + '</span>';
        }
        if (activityId) {
            const card = $qs(`#activitiesList .activity-card[data-id="${activityId}"]`);
            const title = (card?.querySelector('.activity-card-title')?.textContent || '').trim();
            if (title) html += '<span class="note-tag note-tag-act">' + escapeHtml(title) + '</span>';
        }
        return html;
    }

    // Open the shared modal editor for an existing note (el) or a new one.
    function openInlineNoteEditor(el, date) {
        date = (date || (el && el.getAttribute('data-date')) || '').trim();
        window.openNoteEditor({
            title: el ? 'Edit note' : 'Add a note',
            // Every note on a day carries its own name and its own words, so
            // three notes on one day stay three notes.
            askTitle: true,
            noteTitle: el ? (el.getAttribute('data-title') || '') : '',
            bodyHtml: el ? (el.querySelector('.inline-note-body')?.innerHTML || '') : '',
            media: el ? inlineNoteMedia(el) : [],
            imageUploadUrl: U.noteImageUpload(),
            videoUploadUrl: U.noteVideoUpload(),
            drawUploadUrl: NOTES_DRAW_URL,
            onDelete: el ? () => deleteInlineNote(el, false) : null,
            tags: Object.assign(noteTagChoices(date || (el && el.getAttribute('data-date')) || ''), noteTagsOf(el)),
            onSave: ({ body, media, noteTitle, lotId, activityId }) => {
                if (el) {
                    setInlineNoteTags(el, lotId, activityId);
                    setInlineNoteData(el, body, media, noteTitle);
                    saveInlineNote(el, (el.getAttribute('data-date') || date).trim(), parseInt(el.getAttribute('data-sort-key') || '0', 10));
                    return;
                }
                const d = (date || '').trim();
                if (!d || d === '__no-date__') { toast('Pick a real day to add a note.', 'error'); return; }
                const group = $qs(`#activitiesList .date-group[data-date="${d}"]`);
                if (group && group.classList.contains('is-folded')) { group.classList.remove('is-folded'); OPEN_DAYS.add(d); saveOpenDays(); }
                const container = group ? $qs('.date-activities', group) : null;
                if (!container) return;
                const newEl = buildInlineNote('', body, media, d, noteTitle);
                setInlineNoteTags(newEl, lotId, activityId);
                container.appendChild(newEl);
                newEl.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
                const key = inlineNoteKey(newEl);
                newEl.setAttribute('data-sort-key', key);
                saveInlineNote(newEl, d, key);
            },
        });
    }

    /**
     * Start a new note on a day already carrying an attachment — a drawing
     * just made, a map just picked. The editor opens with the attachment in
     * place and asks what to call it, so the thing lands as a note of its own
     * rather than being appended to whatever was there before.
     */
    function newInlineNoteWith(dateKey, mediaArr, suggested) {
        window.openNoteEditor({
            title: 'Add a note',
            askTitle: true,
            noteTitle: suggested ? (suggested + ' — ' + prettyDate(dateKey)) : '',
            bodyHtml: '',
            media: mediaArr || [],
            imageUploadUrl: U.noteImageUpload(),
            videoUploadUrl: U.noteVideoUpload(),
            drawUploadUrl: NOTES_DRAW_URL,
            tags: noteTagChoices(dateKey),
            onSave: ({ body, media, noteTitle, lotId, activityId }) => {
                const group = $qs(`#activitiesList .date-group[data-date="${dateKey}"]`);
                if (group && group.classList.contains('is-folded')) {
                    group.classList.remove('is-folded'); OPEN_DAYS.add(dateKey); saveOpenDays();
                }
                const container = group ? $qs('.date-activities', group) : null;
                if (!container) { toast('Could not find that day on the board.', 'error'); return; }
                const el = buildInlineNote('', body, media, dateKey, noteTitle);
                setInlineNoteTags(el, lotId, activityId);
                container.appendChild(el);
                el.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
                const key = inlineNoteKey(el);
                el.setAttribute('data-sort-key', key);
                saveInlineNote(el, dateKey, key);
            },
        });
    }

    // Header +note button.
    function addInlineNote(date) {
        date = (date || '').trim();
        if (!date || date === '__no-date__') { toast('Pick a real day to add a note.', 'error'); return; }
        openInlineNoteEditor(null, date);
    }

    document.addEventListener('click', (e) => {
        const del = e.target.closest && e.target.closest('.inline-note-del');
        if (del) { e.preventDefault(); const note = del.closest('.inline-note'); if (note) deleteInlineNote(note, false); return; }
        const edit = e.target.closest && e.target.closest('.inline-note-edit');
        if (edit) { e.preventDefault(); const note = edit.closest('.inline-note'); if (note) openInlineNoteEditor(note); return; }
        const note = e.target.closest && e.target.closest('.inline-note');
        if (note && !e.target.closest('.inline-note-grip, .na, .nm, a')) {
            // Clamped to one line on a phone, so a tap means "let me read it".
            // Editing is a button away, here and in the sheet's footer — and
            // that footer button goes away for someone who may not write.
            if (window.matchMedia('(pointer: coarse)').matches && !note.classList.contains('is-editing')) {
                openNoteInfo(note, MAY_NOTE ? () => openInlineNoteEditor(note) : null);
                return;
            }
            if (!mayWriteNotes()) return;
            openInlineNoteEditor(note);
        }
    });

    /* ---- Pointer-based drag for inline notes (mouse + touch + pen) --------
     * Native HTML5 drag was unreliable for notes inside the board, so they use
     * a direct pointer drag: a ghost follows the pointer while the real note is
     * live-repositioned among the day's cards, then its slot is persisted.
     * On touch the grip is the handle (touch-action:none); a mouse can grab
     * anywhere on the note. */
    (function inlineNotePointerDrag() {
        let d = null;
        // How far a pointer must travel before a press becomes a drag. A mouse
        // is precise, but a finger rolls several pixels on a plain tap — at 5px
        // that made tapping a note start a drag, so the note dimmed, a ghost
        // appeared and both were undone on release. That flinch is the "shake".
        const THRESH = 5;
        const THRESH_TOUCH = 14;
        let swallowClick = false;
        document.addEventListener('click', (e) => {
            if (!swallowClick) return;
            swallowClick = false;
            e.stopPropagation(); e.preventDefault();
        }, true);

        function restoreDateNoteToTop(block) {
            const date = (block.getAttribute('data-date') || '').trim();
            const inner = $qs(`#activitiesList .date-group[data-date="${date}"] .date-body-inner`);
            if (inner && block.parentNode !== inner) inner.insertBefore(block, inner.firstChild);
        }
        document.addEventListener('pointerdown', (e) => {
            if (e.button != null && e.button !== 0) return;
            if (e.target.closest && e.target.closest('.inline-note-del, .inline-note-edit, .date-note-edit, .date-note-del, .na, .nm, a')) return;
            const inlineNote = e.target.closest && e.target.closest('.inline-note[data-inline-note]');
            const dateNote = inlineNote ? null : (e.target.closest && e.target.closest('.date-note-block[data-date]'));
            const note = inlineNote || dateNote;
            if (!note || note.classList.contains('is-editing')) return;
            // Every way this drag can end is a write: reposition a note, convert
            // a per-day note into an inline one, or long-press to delete. So it
            // never arms for someone who may not write. Nothing is said here —
            // a press is not a request; the click that follows explains.
            if (!MAY_NOTE) return;
            if (dateNote) {
                if (dateNote.style.display === 'none' || !(dateNote.textContent || '').trim()) return;
                if (e.target.closest('a')) return;   // let links inside the note work
            }
            d = { note, isDateNote: !!dateNote, origDate: (note.getAttribute('data-date') || '').trim(), id: e.pointerId, startX: e.clientX, startY: e.clientY, active: false, ghost: null, offX: 0, offY: 0, lpTimer: null };
            // Touch long-press on a per-day note → delete it (with confirmation).
            if (dateNote && e.pointerType === 'touch') {
                d.lpTimer = setTimeout(() => {
                    if (!d || d.active) return;
                    const dk = d.origDate; d = null;
                    swallowClick = true; setTimeout(() => { swallowClick = false; }, 500);
                    if (navigator.vibrate) { try { navigator.vibrate(15); } catch (_) { /* noop */ } }
                    confirmDeleteDateNote(dk);
                }, 500);
            }
        });
        document.addEventListener('pointermove', (e) => {
            if (!d || e.pointerId !== d.id) return;
            if (!d.active) {
                const slop = e.pointerType === 'touch' ? THRESH_TOUCH : THRESH;
                if (Math.hypot(e.clientX - d.startX, e.clientY - d.startY) < slop) return;
                d.active = true;
                if (d.lpTimer) { clearTimeout(d.lpTimer); d.lpTimer = null; }
                try { d.note.setPointerCapture(e.pointerId); } catch (_) { /* noop */ }
                const rect = d.note.getBoundingClientRect();
                d.offX = d.startX - rect.left;
                d.offY = d.startY - rect.top;
                d.ghost = buildDragGhost(d.note);
                d.note.classList.add('dragging');
                document.body.classList.add('is-touch-dragging');
            }
            e.preventDefault();
            d.ghost.style.transform = `translate(${e.clientX - d.offX}px, ${e.clientY - d.offY}px) scale(1.03)`;
            const target = document.elementFromPoint(e.clientX, e.clientY);
            const container = target && target.closest && target.closest('.date-activities');
            $qsa('.date-activities.drag-over').forEach((c) => c.classList.remove('drag-over'));
            if (container) {
                container.classList.add('drag-over');
                const before = inlineDragPosition(container, e.clientY, d.note);
                if (before) { if (before !== d.note && before.previousElementSibling !== d.note) container.insertBefore(d.note, before); }
                else if (container.lastElementChild !== d.note) container.appendChild(d.note);
            }
        });
        function finish(commit) {
            if (!d) return;
            const cur = d; d = null;
            if (cur.lpTimer) { clearTimeout(cur.lpTimer); cur.lpTimer = null; }
            $qsa('.date-activities.drag-over').forEach((c) => c.classList.remove('drag-over'));
            document.body.classList.remove('is-touch-dragging');
            cur.ghost && cur.ghost.remove();
            cur.note.classList.remove('dragging');
            if (!cur.active) return;
            swallowClick = true;
            setTimeout(() => { swallowClick = false; }, 350);
            const container = cur.note.closest('.date-activities');
            const newDate = container ? (container.getAttribute('data-date') || '').trim() : '';
            const ok = commit && container && newDate && newDate !== '__no-date__';

            if (cur.isDateNote) {
                if (!ok) { restoreDateNoteToTop(cur.note); return; }
                // Upgrade the legacy per-day note (text + drawings + media) into
                // a positioned inline note.
                const dnContent = cur.note.getAttribute('data-content') || '';
                let dnMedia = []; try { dnMedia = JSON.parse(cur.note.getAttribute('data-media') || '[]'); } catch (_) { /* noop */ }
                const el = buildInlineNote('', dnContent, dnMedia, newDate);
                cur.note.replaceWith(el);
                const key = inlineNoteKey(el);
                el.setAttribute('data-sort-key', key);
                saveInlineNote(el, newDate, key).then((saved) => {
                    if (!saved) {
                        // The board moved before the server agreed, and it did
                        // not agree. Put the day's note back rather than leave a
                        // note sitting there that a reload will not show — a
                        // board that lies is worse than a drag that failed.
                        if (el.isConnected) el.replaceWith(cur.note);
                        restoreDateNoteToTop(cur.note);
                        return;
                    }
                    // Only retire the old per-day note once the inline copy saved.
                    // Unreachable without MAY_NOTE: saveInlineNote answers false.
                    _refreshDateNoteUI(cur.origDate, '');
                    api(U.dateNoteDelete(), { method: 'DELETE', body: { noteDate: cur.origDate } }).catch(() => {});
                });
                return;
            }

            if (!ok) return;
            cur.note.setAttribute('data-date', newDate);
            const key = inlineNoteKey(cur.note);
            cur.note.setAttribute('data-sort-key', key);
            saveInlineNote(cur.note, newDate, key);
        }
        // Only the finger that started the drag may end it. These listened to
        // every pointer, so scrolling with a second finger while dragging ended
        // the drag the moment that finger lifted — or cancelled it outright
        // when the browser claimed that pointer for the scroll.
        document.addEventListener('pointerup', (e) => {
            if (d && e.pointerId !== d.id) return;
            finish(true);
        });
        document.addEventListener('pointercancel', (e) => {
            if (d && e.pointerId !== d.id) return;
            finish(false);
        });
    })();

    document.addEventListener('dragstart', (e) => {
        // Dragging a date header moves that whole day's activities.
        const header = e.target.closest && e.target.closest('.date-header[draggable="true"]');
        if (header) {
            const groupDate = (header.closest('.date-group')?.getAttribute('data-date') || '').trim();
            if (!groupDate || groupDate === '__no-date__') return;
            dragGroupDate = groupDate;
            header.classList.add('dragging');
            if (e.dataTransfer) {
                e.dataTransfer.effectAllowed = 'move';
                try { e.dataTransfer.setData('text/plain', 'group:' + groupDate); } catch (_) { /* noop */ }
            }
            return;
        }

        // Dragging a date-note block moves the note to another day.
        const noteBlk = e.target.closest && e.target.closest('.date-note-block[data-date]');
        if (noteBlk && noteBlk.style.display !== 'none' && (noteBlk.innerHTML || '').trim()) {
            if (e.target.closest('a')) return;   // let links inside the note behave
            // Dropping this rewrites two days' notes. Refuse the gesture rather
            // than the drop, the same way the pointer drag does.
            if (!MAY_NOTE) { e.preventDefault(); return; }
            dragNoteDate = (noteBlk.getAttribute('data-date') || '').trim();
            noteBlk.classList.add('dragging');
            if (e.dataTransfer) { e.dataTransfer.effectAllowed = 'move'; try { e.dataTransfer.setData('text/plain', 'note:' + dragNoteDate); } catch (_) { /* noop */ } }
            return;
        }

        // Dragging a resume-here marker moves it to another day.
        const mk = e.target.closest && e.target.closest('.progress-marker[data-date]');
        if (mk) {
            if (e.target.closest('button, a')) return;   // its own edit/delete buttons
            dragMarkerDate = (mk.getAttribute('data-date') || '').trim();
            mk.classList.add('dragging');
            if (e.dataTransfer) { e.dataTransfer.effectAllowed = 'move'; try { e.dataTransfer.setData('text/plain', 'marker:' + dragMarkerDate); } catch (_) { /* noop */ } }
            return;
        }

        // Dragging an inline sticky note to another slot/day.
        const inl = e.target.closest && e.target.closest('.inline-note[data-inline-note]');
        if (inl) {
            if (inl.classList.contains('is-editing') || (e.target.closest && e.target.closest('.inline-note-del'))) { e.preventDefault(); return; }
            if (!MAY_NOTE) { e.preventDefault(); return; }   // its slot is a saved field like any other
            dragInlineEl = inl;
            setTimeout(() => inl.classList.add('dragging'), 0);
            if (e.dataTransfer) { e.dataTransfer.effectAllowed = 'move'; try { e.dataTransfer.setData('text/plain', 'inline:' + inl.getAttribute('data-inline-note')); } catch (_) { /* noop */ } }
            return;
        }

        const card = e.target.closest && e.target.closest('.activity-card[data-id]');
        if (!card) return;
        if (card.getAttribute('data-is-done') === '1') { e.preventDefault(); return; }   // done = locked in place
        dragSourceCard = card;
        dragOrigin = {
            date: (card.getAttribute('data-target-date') || '').trim(),
            endDate: (card.getAttribute('data-target-end-date') || '').trim(),
            parent: card.parentNode,
            nextSibling: card.nextElementSibling,
        };
        dragBoardSnapshot = captureBoardSnapshot();
        if (e.dataTransfer) {
            e.dataTransfer.effectAllowed = 'move';
            try { e.dataTransfer.setData('text/plain', card.getAttribute('data-id')); } catch (_) { /* noop */ }
            // Carry a faded copy of the card under the cursor. The browser's own
            // snapshot would pick up the .dragging fade below and end up almost
            // invisible, so hand it an explicit ghost instead.
            const ghost = buildDragGhost(card);
            ghost.style.transform = 'translate(-10000px, 0)';   // park it off-screen to be photographed
            try {
                e.dataTransfer.setDragImage(ghost, e.clientX - card.getBoundingClientRect().left, 24);
            } catch (_) { /* noop */ }
            setTimeout(() => ghost.remove(), 0);
        }
        // Fade the original only after the drag image has been captured.
        setTimeout(() => card.classList.add('dragging'), 0);
    });

    document.addEventListener('dragend', (e) => {
        const card = e.target.closest && e.target.closest('.activity-card');
        card?.classList.remove('dragging');
        $qsa('.date-header.dragging').forEach((el) => el.classList.remove('dragging'));
        $qsa('.date-activities.drag-over, .rest-day-marker.drag-over, .date-group.drag-over-group')
            .forEach((el) => el.classList.remove('drag-over', 'drag-over-group'));
        $qsa('.date-note-block.dragging, .progress-marker.dragging, .inline-note.dragging').forEach((el) => el.classList.remove('dragging'));
        dragSourceCard = null;
        dragOrigin = null;
        dragGroupDate = null;
        dragNoteDate = null;
        dragMarkerDate = null;
        dragInlineEl = null;
    });

    function dragoverPosition(container, cursorY) {
        const cards = $qsa('.activity-card[data-id]', container).filter((c) => c !== dragSourceCard);
        for (const card of cards) {
            const rect = card.getBoundingClientRect();
            if (cursorY < rect.top + rect.height / 2) return card;
        }
        return null;
    }

    document.addEventListener('dragover', (e) => {
        // Whole-day move: highlight the day (or empty day) being hovered.
        if (dragGroupDate) {
            const targetGroup = e.target.closest && e.target.closest('.date-group');
            const targetRest = e.target.closest && e.target.closest('.rest-day-marker');
            const targetDate = (targetGroup?.getAttribute('data-date') || targetRest?.getAttribute('data-date') || '').trim();
            if (!targetDate || targetDate === '__no-date__' || targetDate === dragGroupDate) return;
            e.preventDefault();
            if (e.dataTransfer) e.dataTransfer.dropEffect = 'move';
            $qsa('.date-group.drag-over-group, .rest-day-marker.drag-over')
                .forEach((el) => el.classList.remove('drag-over-group', 'drag-over'));
            if (targetRest) targetRest.classList.add('drag-over');
            else targetGroup.classList.add('drag-over-group');
            return;
        }

        // Note / marker move: highlight the target day.
        if (dragNoteDate || dragMarkerDate) {
            const targetGroup = e.target.closest && e.target.closest('.date-group');
            const targetDate = (targetGroup?.getAttribute('data-date') || '').trim();
            const src = dragNoteDate || dragMarkerDate;
            if (!targetDate || targetDate === '__no-date__' || targetDate === src) return;
            e.preventDefault();
            if (e.dataTransfer) e.dataTransfer.dropEffect = 'move';
            $qsa('.date-group.drag-over-group').forEach((el) => el.classList.remove('drag-over-group'));
            targetGroup.classList.add('drag-over-group');
            return;
        }

        // Inline sticky note: live-position it among a day's cards/notes.
        if (dragInlineEl) {
            const container = e.target.closest && e.target.closest('.date-activities');
            if (!container) return;
            e.preventDefault();
            if (e.dataTransfer) e.dataTransfer.dropEffect = 'move';
            $qsa('.date-activities.drag-over').forEach((c) => c.classList.remove('drag-over'));
            container.classList.add('drag-over');
            const before = inlineDragPosition(container, e.clientY);
            if (before) { if (before !== dragInlineEl && before.previousElementSibling !== dragInlineEl) container.insertBefore(dragInlineEl, before); }
            else if (container.lastElementChild !== dragInlineEl) container.appendChild(dragInlineEl);
            return;
        }

        if (!dragSourceCard) return;
        // A folded day springs open under a dragged card so it can be dropped in.
        const foldedGroup = e.target.closest && e.target.closest('.date-group.is-folded');
        if (foldedGroup) {
            foldedGroup.classList.remove('is-folded');
            OPEN_DAYS.add((foldedGroup.getAttribute('data-date') || '').trim());
            saveOpenDays();
        }
        const container = e.target.closest && e.target.closest('.date-activities');
        if (container) {
            e.preventDefault();
            if (e.dataTransfer) e.dataTransfer.dropEffect = 'move';
            container.classList.add('drag-over');
            const insertBefore = dragoverPosition(container, e.clientY);
            if (insertBefore) {
                if (insertBefore.previousElementSibling !== dragSourceCard) {
                    insertBefore.parentNode.insertBefore(dragSourceCard, insertBefore);
                }
            } else if (container.lastElementChild !== dragSourceCard) {
                container.appendChild(dragSourceCard);
            }
            return;
        }
        const rest = e.target.closest && e.target.closest('.rest-day-marker');
        if (rest) {
            e.preventDefault();
            if (e.dataTransfer) e.dataTransfer.dropEffect = 'move';
            rest.classList.add('drag-over');
        }
    });

    document.addEventListener('dragleave', (e) => {
        const el = e.target.closest && e.target.closest('.date-activities, .rest-day-marker');
        if (el && e.target === el) el.classList.remove('drag-over');
    });

    document.addEventListener('drop', (e) => {
        // Whole-day move via the date header.
        if (dragGroupDate) {
            const targetGroup = e.target.closest && e.target.closest('.date-group');
            const targetRest = e.target.closest && e.target.closest('.rest-day-marker');
            const targetDate = (targetGroup?.getAttribute('data-date') || targetRest?.getAttribute('data-date') || '').trim();
            const sourceDate = dragGroupDate;
            $qsa('.date-group.drag-over-group, .rest-day-marker.drag-over')
                .forEach((el) => el.classList.remove('drag-over-group', 'drag-over'));
            dragGroupDate = null;
            if (!targetDate || targetDate === '__no-date__' || targetDate === sourceDate) return;
            e.preventDefault();
            moveGroupToDate(sourceDate, targetDate);
            return;
        }

        // Note / marker move drop.
        if (dragNoteDate || dragMarkerDate) {
            const targetGroup = e.target.closest && e.target.closest('.date-group');
            const targetDate = (targetGroup?.getAttribute('data-date') || '').trim();
            const noteSrc = dragNoteDate, markerSrc = dragMarkerDate;
            dragNoteDate = null; dragMarkerDate = null;
            $qsa('.date-group.drag-over-group').forEach((el) => el.classList.remove('drag-over-group'));
            $qsa('.date-note-block.dragging, .progress-marker.dragging').forEach((el) => el.classList.remove('dragging'));
            if (!targetDate || targetDate === '__no-date__') return;
            e.preventDefault();
            if (noteSrc) moveNoteToDate(noteSrc, targetDate);
            else if (markerSrc) moveMarkerToDate(markerSrc, targetDate);
            return;
        }

        // Inline sticky note drop → persist its new day + position.
        if (dragInlineEl) {
            const container = e.target.closest && e.target.closest('.date-activities');
            const el = dragInlineEl; dragInlineEl = null;
            $qsa('.date-activities.drag-over').forEach((c) => c.classList.remove('drag-over'));
            el.classList.remove('dragging');
            if (!container) return;
            e.preventDefault();
            const newDate = (container.getAttribute('data-date') || '').trim();
            if (!newDate || newDate === '__no-date__') { toast('Cannot drop a note on "No date".', 'error'); return; }
            el.setAttribute('data-date', newDate);
            const key = inlineNoteKey(el);
            el.setAttribute('data-sort-key', key);
            saveInlineNote(el, newDate, key);
            return;
        }

        if (!dragSourceCard) return;
        const container = e.target.closest && e.target.closest('.date-activities');
        if (container) {
            e.preventDefault();
            container.classList.remove('drag-over');
            handleDropIntoContainer(container);
            return;
        }
        const rest = e.target.closest && e.target.closest('.rest-day-marker');
        if (rest) {
            e.preventDefault();
            rest.classList.remove('drag-over');
            handleDropOntoRestDay(rest);
        }
    });

    function handleDropIntoContainer(container) {
        const card = dragSourceCard;
        const group = container.closest('.date-group');
        const newDate = (group?.getAttribute('data-date') || '').trim();
        const oldDate = (dragOrigin && dragOrigin.date) || '';

        if (!newDate || newDate === '__no-date__') {
            toast('Cannot drop on "No date". Edit the activity instead.', 'error');
            if (dragOrigin && dragOrigin.parent) {
                if (dragOrigin.nextSibling) dragOrigin.parent.insertBefore(card, dragOrigin.nextSibling);
                else dragOrigin.parent.appendChild(card);
            }
            return;
        }

        // Multi-day cards keep duration: shift end by the same delta.
        let newEndDate = '';
        if (dragOrigin.endDate && dragOrigin.date) {
            newEndDate = isoAddDays(dragOrigin.endDate, isoDaysBetween(dragOrigin.date, newDate));
        }
        card.setAttribute('data-target-date', newDate);
        card.setAttribute('data-target-end-date', newEndDate);

        // Renumber destination + (when the date changed) source containers.
        const containers = new Set([container]);
        if (dragOrigin && dragOrigin.parent && dragOrigin.parent !== container) containers.add(dragOrigin.parent);
        const items = [];
        containers.forEach((cont) => {
            const contDate = (cont.closest?.('.date-group')?.getAttribute('data-date') || '').trim();
            if (!contDate || contDate === '__no-date__') return;
            Array.from(cont.children).filter((el) => el.matches?.('.activity-card[data-id]')).forEach((el, idx) => {
                items.push({
                    id: parseInt(el.getAttribute('data-id'), 10),
                    targetDate: contDate,
                    targetEndDate: (el.getAttribute('data-target-end-date') || '').trim() || null,
                    sequenceOrder: idx * 10,
                });
                el.setAttribute('data-sequence-order', idx * 10);
            });
        });

        reorderAndRenumberActivities(true);   // optimistic rebuild, animated

        const snapshot = dragBoardSnapshot;
        dragBoardSnapshot = null;
        api(U.reorder(), { method: 'POST', body: { items } })
            .then(() => {
                toast(oldDate && oldDate !== newDate ? 'Moved to ' + newDate : 'Order saved');
                recomputeLotDayZero();
                if (snapshot && snapshot.length) {
                    const label = (oldDate && oldDate !== newDate) ? 'Move activity to ' + newDate : 'Reorder activities';
                    pushUndo(label, () => restoreBoardSnapshot(snapshot));
                }
            })
            .catch((err) => toast(err.message + ' — refresh to see saved order.', 'error'));
    }

    function handleDropOntoRestDay(rest) {
        const card = dragSourceCard;
        const newDate = (rest.getAttribute('data-date') || '').trim();
        if (!newDate) return;

        // An empty date becomes a real day group on drop — open it so the moved
        // card lands in view instead of hidden behind a freshly-folded header.
        OPEN_DAYS.add(newDate);
        saveOpenDays();

        let newEndDate = '';
        if (dragOrigin && dragOrigin.endDate && dragOrigin.date) {
            newEndDate = isoAddDays(dragOrigin.endDate, isoDaysBetween(dragOrigin.date, newDate));
        }
        card.setAttribute('data-target-date', newDate);
        card.setAttribute('data-target-end-date', newEndDate);
        card.setAttribute('data-sequence-order', 0);

        const items = [{
            id: parseInt(card.getAttribute('data-id'), 10),
            targetDate: newDate,
            targetEndDate: newEndDate || null,
            sequenceOrder: 0,
        }];
        // Tighten the source group's remaining sequence numbers.
        if (dragOrigin && dragOrigin.parent && dragOrigin.parent.nodeType === 1) {
            const sourceDate = (dragOrigin.parent.closest?.('.date-group')?.getAttribute('data-date') || '').trim();
            if (sourceDate && sourceDate !== newDate && sourceDate !== '__no-date__') {
                Array.from(dragOrigin.parent.children).filter((el) => el.matches?.('.activity-card[data-id]')).forEach((el, idx) => {
                    const cid = parseInt(el.getAttribute('data-id'), 10);
                    if (cid === items[0].id) return;
                    items.push({
                        id: cid,
                        targetDate: sourceDate,
                        targetEndDate: (el.getAttribute('data-target-end-date') || '').trim() || null,
                        sequenceOrder: idx * 10,
                    });
                    el.setAttribute('data-sequence-order', idx * 10);
                });
            }
        }

        reorderAndRenumberActivities(true);

        const snapshot = dragBoardSnapshot;
        dragBoardSnapshot = null;
        api(U.reorder(), { method: 'POST', body: { items } })
            .then(() => {
                toast('Moved to ' + newDate);
                recomputeLotDayZero();
                if (snapshot && snapshot.length) {
                    pushUndo('Move activity to ' + newDate, () => restoreBoardSnapshot(snapshot));
                }
            })
            .catch((err) => toast(err.message + ' — refresh to see saved order.', 'error'));
    }

    /* ================================================================
     * 11b. TOUCH DRAG — HTML5 drag events never fire on phones, so a
     * long-press starts an equivalent gesture: a faded copy of the card
     * (or date header) follows the finger while the real one stays put,
     * greyed, as the live insertion slot. Dropping hands off to the same
     * handlers the desktop path uses, so ordering, dates, the reorder
     * POST and undo all behave identically.
     * ================================================================ */

    const LONG_PRESS_MS = 320;
    const TOUCH_SLOP = 10;          // px of finger travel that cancels the press
    const EDGE_SCROLL_ZONE = 90;    // px from a viewport edge that auto-scrolls
    const EDGE_SCROLL_SPEED = 12;

    let touchDrag = null;

    /** Translucent clone of `el`, sized to match and parked on <body>. */
    /* ---- Touch-drag guards ----------------------------------------------
     * The long-press touch drag for cards, day headers and notes lives below
     * (touchstart / beginTouchDrag / endTouchDrag). What kept killing it on
     * real phones: cards are draggable="true" for the desktop mouse path, and
     * a STATIONARY long-press on such an element is exactly the gesture
     * mobile browsers answer with a NATIVE HTML5 drag - cancelling the touch
     * moments after the hold granted it. Long-press text selection and the
     * context menu do the same. While a press is armed or a drag is live,
     * veto all three; capture phase on dragstart so the desktop DnD handlers
     * never see the touch-born event. touchDrag only exists while a finger is
     * down, so the mouse path is blind to these guards.
     */
    document.addEventListener("contextmenu", (e) => {
        if (touchDrag) e.preventDefault();
    });
    document.addEventListener("dragstart", (e) => {
        if (touchDrag) { e.preventDefault(); return; }
        // Native HTML5 drag has no touch story at all: when it half-starts on
        // a phone (dense days make this easy to trigger) the card is left at
        // drag opacity with no drag behind it. On coarse pointers the long-
        // press system owns cards — the native path is vetoed outright.
        if (window.matchMedia("(pointer: coarse)").matches && e.target.closest && e.target.closest(".activity-card")) {
            e.preventDefault();
        }
    }, true);
    // Whatever ends a native drag, no card stays stranded translucent.
    document.addEventListener("dragend", () => {
        document.querySelectorAll("#activitiesList .activity-card.dragging").forEach((c) => c.classList.remove("dragging"));
    });
    document.addEventListener("selectstart", (e) => {
        if (touchDrag) e.preventDefault();
    });

    function buildDragGhost(el) {
        const rect = el.getBoundingClientRect();
        const ghost = el.cloneNode(true);
        ghost.classList.add('drag-ghost');
        ghost.classList.remove('dragging');
        ghost.removeAttribute('id');
        ghost.removeAttribute('draggable');
        ghost.style.width = rect.width + 'px';
        $qsa('[id]', ghost).forEach((n) => n.removeAttribute('id'));
        document.body.appendChild(ghost);
        return ghost;
    }

    function positionGhost(x, y) {
        if (!touchDrag) return;
        touchDrag.ghost.style.transform =
            `translate(${x - touchDrag.offsetX}px, ${y - touchDrag.offsetY}px) scale(1.03)`;
    }

    /** Keeps the page moving while a finger is parked near the top/bottom edge. */
    function edgeScrollTick() {
        if (!touchDrag) return;
        const y = touchDrag.lastY;
        if (y < EDGE_SCROLL_ZONE) window.scrollBy(0, -EDGE_SCROLL_SPEED);
        else if (y > window.innerHeight - EDGE_SCROLL_ZONE - 60) window.scrollBy(0, EDGE_SCROLL_SPEED);
        touchDrag.raf = requestAnimationFrame(edgeScrollTick);
    }

    function clearDropHighlights() {
        $qsa('.date-activities.drag-over, .rest-day-marker.drag-over, .date-group.drag-over-group')
            .forEach((el) => el.classList.remove('drag-over', 'drag-over-group'));
    }

    // Lifting a finger after a drag also fires a click; that must not be read
    // as "tap the card to edit it".
    let swallowNextClick = false;
    document.addEventListener('click', (e) => {
        if (!swallowNextClick) return;
        swallowNextClick = false;
        e.stopPropagation();
        e.preventDefault();
    }, true);

    function endTouchDrag(commit) {
        if (!touchDrag) return;
        const td = touchDrag;
        touchDrag = null;
        if (td.active) swallowNextClick = true;
        clearTimeout(td.timer);
        if (td.raf) cancelAnimationFrame(td.raf);
        td.ghost?.remove();
        document.body.classList.remove('is-touch-dragging');

        if (td.active) {
            const target = document.elementFromPoint(td.lastX, td.lastY);
            const rest = target?.closest?.('.rest-day-marker');
            if (td.header) {
                const group = target?.closest?.('.date-group');
                const to = (group?.getAttribute('data-date') || rest?.getAttribute('data-date') || '').trim();
                td.header.classList.remove('dragging');
                clearDropHighlights();
                dragGroupDate = null;
                if (commit && to && to !== '__no-date__' && to !== td.groupDate) moveGroupToDate(td.groupDate, to);
            } else if (td.note) {
                const container = target?.closest?.('.date-activities');
                td.note.classList.remove('dragging');
                clearDropHighlights();
                dragInlineEl = null;
                if (commit && container) {
                    const newDate = (container.getAttribute('data-date') || '').trim();
                    if (newDate && newDate !== '__no-date__') {
                        td.note.setAttribute('data-date', newDate);
                        const key = inlineNoteKey(td.note);
                        td.note.setAttribute('data-sort-key', key);
                        saveInlineNote(td.note, newDate, key);
                    }
                }
            } else {
                const container = target?.closest?.('.date-activities');
                td.card.classList.remove('dragging');
                clearDropHighlights();
                if (commit && container) handleDropIntoContainer(container);
                else if (commit && rest) handleDropOntoRestDay(rest);
                else if (dragOrigin?.parent) {
                    // Cancelled: put the card back exactly where it started.
                    if (dragOrigin.nextSibling) dragOrigin.parent.insertBefore(td.card, dragOrigin.nextSibling);
                    else dragOrigin.parent.appendChild(td.card);
                    reorderAndRenumberActivities();
                }
                dragSourceCard = null;
                dragOrigin = null;
                dragBoardSnapshot = null;
            }
        }
    }

    function beginTouchDrag(td) {
        if (td.card && td.card.getAttribute('data-is-done') === '1') return;   // done = locked in place
        td.active = true;
        document.body.classList.add('is-touch-dragging');
        navigator.vibrate?.(15);

        const el = td.header || td.note || td.card;
        td.ghost = buildDragGhost(el);
        const rect = el.getBoundingClientRect();
        td.offsetX = td.lastX - rect.left;
        td.offsetY = td.lastY - rect.top;
        positionGhost(td.lastX, td.lastY);

        if (td.header) {
            td.header.classList.add('dragging');
            dragGroupDate = td.groupDate;
        } else if (td.note) {
            td.note.classList.add('dragging');
            dragInlineEl = td.note;
        } else {
            td.card.classList.add('dragging');
            dragSourceCard = td.card;
            dragOrigin = {
                date: (td.card.getAttribute('data-target-date') || '').trim(),
                endDate: (td.card.getAttribute('data-target-end-date') || '').trim(),
                parent: td.card.parentNode,
                nextSibling: td.card.nextElementSibling,
            };
            dragBoardSnapshot = captureBoardSnapshot();
        }
        td.raf = requestAnimationFrame(edgeScrollTick);
    }

    document.addEventListener('touchstart', (e) => {
        if (e.touches.length !== 1 || touchDrag) return;
        const t = e.touches[0];
        // Taps on controls stay taps.
        if (t.target.closest?.('button, a, input, select, textarea, [contenteditable], .sheet')) return;

        const header = t.target.closest?.('.date-header[draggable="true"]');
        const card = header ? null : t.target.closest?.('.activity-card[data-id]');
        if (!header && !card) return;

        const groupDate = header ? (header.closest('.date-group')?.getAttribute('data-date') || '').trim() : '';
        if (header && (!groupDate || groupDate === '__no-date__')) return;

        touchDrag = {
            card, header, groupDate, active: false, raf: null,
            startX: t.clientX, startY: t.clientY, lastX: t.clientX, lastY: t.clientY,
            offsetX: 0, offsetY: 0, ghost: null,
        };
        touchDrag.timer = setTimeout(() => { if (touchDrag) beginTouchDrag(touchDrag); }, LONG_PRESS_MS);
    }, { passive: true });

    document.addEventListener('touchmove', (e) => {
        if (!touchDrag || e.touches.length !== 1) return;
        const t = e.touches[0];
        touchDrag.lastX = t.clientX;
        touchDrag.lastY = t.clientY;

        if (!touchDrag.active) {
            // Still waiting on the long press — any real movement means "scroll".
            const moved = Math.hypot(t.clientX - touchDrag.startX, t.clientY - touchDrag.startY);
            if (moved > TOUCH_SLOP) { clearTimeout(touchDrag.timer); touchDrag = null; }
            return;
        }

        e.preventDefault();   // the page must not scroll under the finger
        positionGhost(t.clientX, t.clientY);

        const target = document.elementFromPoint(t.clientX, t.clientY);
        const rest = target?.closest?.('.rest-day-marker');
        clearDropHighlights();

        if (touchDrag.header) {
            const group = target?.closest?.('.date-group');
            const to = (group?.getAttribute('data-date') || rest?.getAttribute('data-date') || '').trim();
            if (!to || to === '__no-date__' || to === touchDrag.groupDate) return;
            if (rest) rest.classList.add('drag-over');
            else group.classList.add('drag-over-group');
            return;
        }

        if (touchDrag.note) {
            const container = target?.closest?.('.date-activities');
            if (container) {
                container.classList.add('drag-over');
                const before = inlineDragPosition(container, t.clientY);
                if (before) { if (before !== touchDrag.note && before.previousElementSibling !== touchDrag.note) container.insertBefore(touchDrag.note, before); }
                else if (container.lastElementChild !== touchDrag.note) container.appendChild(touchDrag.note);
            }
            return;
        }

        const container = target?.closest?.('.date-activities');
        if (container) {
            container.classList.add('drag-over');
            const before = dragoverPosition(container, t.clientY);
            if (before) {
                if (before.previousElementSibling !== touchDrag.card) before.parentNode.insertBefore(touchDrag.card, before);
            } else if (container.lastElementChild !== touchDrag.card) {
                container.appendChild(touchDrag.card);
            }
        } else if (rest) {
            rest.classList.add('drag-over');
        }
    }, { passive: false });

    document.addEventListener('touchend', () => endTouchDrag(true), { passive: true });
    document.addEventListener('touchcancel', () => endTouchDrag(false), { passive: true });
    // A native scroll means the press was never a drag.
    window.addEventListener('scroll', () => {
        if (touchDrag && !touchDrag.active) { clearTimeout(touchDrag.timer); touchDrag = null; }
    }, { passive: true });

    /* ================================================================
     * 12. DRAFTS
     * ================================================================ */

    function bumpDraftsBadge(delta) {
        const badge = $id('draftsBadge');
        if (!badge) return;
        badge.textContent = Math.max(0, (parseInt(badge.textContent, 10) || 0) + delta);
    }

    function renderDraftRow(d) {
        const lots = (d.lots || []).map((l) => esc(l.lotName)).join(', ') || '—';
        const dateLabel = d.targetDate ? prettyDateFull(d.targetDate) : 'No date';
        const priority = d.priority || 'medium';
        const priorityCap = priority.charAt(0).toUpperCase() + priority.slice(1);
        return `<div class="card p-3 draft-row" data-id="${d.id}" style="border-left:3px solid #50a5f1;">
            <div class="flex items-start justify-between gap-3 flex-wrap">
                <div class="min-w-0 grow">
                    <p class="font-bold text-gray-900 text-sm">${esc(d.activityTitle)}</p>
                    <p class="text-xs text-gray-500 mt-0.5">${esc(dateLabel)} · Lots: ${lots}</p>
                    ${d.updatedAt ? `<p class="text-xs text-gray-400 mt-0.5">Drafted ${esc(d.updatedAt)}</p>` : ''}
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <span class="pill pill-${esc(priority)}">${esc(priorityCap)}</span>
                    <button type="button" class="btn btn-primary btn-sm restore-draft-btn" data-id="${d.id}" data-name="${esc(d.activityTitle)}">Restore</button>
                    <button type="button" class="icon-btn icon-btn-danger delete-draft-btn" data-id="${d.id}" data-name="${esc(d.activityTitle)}" title="Delete draft">${SVG.trash}</button>
                </div>
            </div>
        </div>`;
    }

    function renderDraftsList(drafts) {
        const container = $id('draftsListContainer');
        if (!drafts || drafts.length === 0) {
            container.innerHTML = '';
            container.classList.add('hidden');
            $id('draftsEmpty').classList.remove('hidden');
            return;
        }
        $id('draftsEmpty').classList.add('hidden');
        container.classList.remove('hidden');
        container.innerHTML = drafts.map(renderDraftRow).join('');
    }

    $id('openDraftsBtn')?.addEventListener('click', async () => {
        const container = $id('draftsListContainer');
        container.classList.remove('hidden');
        container.innerHTML = '<div class="text-center text-gray-400 py-6 text-sm">Loading…</div>';
        $id('draftsEmpty').classList.add('hidden');
        openSheet('draftsSheet');
        try {
            const res = await api(U.drafts());
            const n = (res.data || []).length;
            const badge = $id('draftsBadge');
            if (badge) badge.textContent = n;
            renderDraftsList(res.data || []);
        } catch (err) {
            toast(err.message, 'error');
            renderDraftsList([]);
        }
    });

    document.addEventListener('click', async (e) => {
        const restoreBtn = e.target.closest('.restore-draft-btn');
        if (restoreBtn) {
            const id = restoreBtn.getAttribute('data-id');
            const name = restoreBtn.getAttribute('data-name') || 'activity';
            restoreBtn.disabled = true;
            try {
                const res = await api(U.fromDraft(id), { method: 'POST' });
                toast(`"${name}" restored`);
                _renderCardOrReplace(res.data);
                bumpDraftsBadge(-1);
                const row = restoreBtn.closest('.draft-row');
                row?.remove();
                if ($qsa('#draftsListContainer .draft-row').length === 0) renderDraftsList([]);
                pushUndo(`Restore '${name}' from drafts`, async () => {
                    const r = await api(U.toDraft(id), { method: 'POST' });
                    if (!r || !r.success) throw new Error((r && r.message) || 'undo failed');
                    _removeCardById(id);
                    bumpDraftsBadge(1);
                }, async () => {
                    const r = await api(U.fromDraft(id), { method: 'POST' });
                    if (!r || !r.success) throw new Error((r && r.message) || 'restore failed');
                    _renderCardOrReplace(r.data);
                    bumpDraftsBadge(-1);
                    $qs(`#draftsListContainer .draft-row[data-id="${id}"]`)?.remove();
                });
            } catch (err) {
                toast(err.message, 'error');
                restoreBtn.disabled = false;
            }
            return;
        }
        const deleteBtn = e.target.closest('.delete-draft-btn');
        if (deleteBtn) {
            const id = deleteBtn.getAttribute('data-id');
            const name = deleteBtn.getAttribute('data-name') || 'draft';
            const ok = await confirmAction({
                title: 'Delete drafted activity',
                message: `Permanently delete drafted activity "${name}"?`,
                detail: 'You can immediately undo this (Ctrl+Z) — the draft is soft-deleted and can be restored back into the drafts list.',
                confirmText: 'Delete Draft',
            });
            if (!ok) return;
            try {
                await api(U.destroy(id), { method: 'DELETE' });
                toast(`Draft "${name}" deleted`);
                $qs(`#draftsListContainer .draft-row[data-id="${id}"]`)?.remove();
                bumpDraftsBadge(-1);
                if ($qsa('#draftsListContainer .draft-row').length === 0) renderDraftsList([]);
                pushUndo(`Delete draft '${name}'`, async () => {
                    const r = await api(U.restore(id), { method: 'POST' });
                    if (!r || !r.success) throw new Error((r && r.message) || 'restore failed');
                    bumpDraftsBadge(1);
                    // If the drafts sheet is open, surface the returned row again.
                    const sheet = $id('draftsSheet');
                    if (sheet && sheet.classList.contains('is-open')) {
                        $id('draftsEmpty').classList.add('hidden');
                        const container = $id('draftsListContainer');
                        container.classList.remove('hidden');
                        container.insertAdjacentHTML('beforeend', renderDraftRow({
                            id: r.data.id,
                            activityTitle: r.data.activityTitle,
                            targetDate: r.data.targetDate,
                            lots: r.data.lots || [],
                            priority: r.data.priority,
                            updatedAt: r.data.updated_at || null,
                        }));
                    }
                }, async () => {
                    const r = await api(U.destroy(id), { method: 'DELETE' });
                    if (!r || !r.success) throw new Error((r && r.message) || 'delete failed');
                    bumpDraftsBadge(-1);
                    $qs(`#draftsListContainer .draft-row[data-id="${id}"]`)?.remove();
                    if ($qsa('#draftsListContainer .draft-row').length === 0) renderDraftsList([]);
                });
            } catch (err) {
                toast(err.message, 'error');
            }
        }
    });

    /* ================================================================
     * 13. LABOR REPORT — a full page now; the picker just navigates.
     * ================================================================ */

    $id('openReportBtn')?.addEventListener('click', () => openSheet('reportPickerSheet'));
    $id('reportLaborBtn')?.addEventListener('click', () => {
        closeSheet('reportPickerSheet');
        window.location.href = @json(route('sm.labor.report')) + '?id=' + @json($schedule->id);
    });

    /* ================================================================
     * 14. VERSIONS
     * ================================================================ */

    function activeVersionChip() {
        return $qs('#versionStrip .version-chip[data-is-active="1"]');
    }

    // A schedule may hold at most 4 versions (Original + 3). Hide the add
    // button once the limit is reached; the server enforces it regardless.
    const MAX_VERSIONS = 4;
    function enforceVersionLimit() {
        const addBtn = $id('addVersionBtn');
        if (!addBtn) return;
        const atLimit = $qsa('#versionStrip .version-chip').length >= MAX_VERSIONS;
        addBtn.classList.toggle('hidden', atLimit);
    }
    enforceVersionLimit();

    $id('versionStrip')?.addEventListener('click', async (e) => {
        if (e.target.closest('#addVersionBtn')) {
            if ($qsa('#versionStrip .version-chip').length >= MAX_VERSIONS) {
                toast(`You can have at most ${MAX_VERSIONS} versions. Delete one to make room.`, 'error');
                return;
            }
            $id('newVersionName').value = '';
            $id('newVersionDescription').value = '';
            const active = activeVersionChip();
            if (active) $id('newVersionSource').value = active.getAttribute('data-version-id');
            $id('newVersionSetActive').checked = true;
            openSheet('versionSheet');
            window.smFocus($id('newVersionName'), { delay: 250 });
            return;
        }
        const chip = e.target.closest('.version-chip');
        if (!chip) return;
        if (chip.getAttribute('data-is-active') === '1') return;
        const id = chip.getAttribute('data-version-id');
        const name = chip.getAttribute('data-version-name') || 'version';
        const ok = await confirmAction({
            title: 'Switch version?',
            message: `Make "${name}" the active version? The whole timeline (plus exports, presentations and labor) will follow it.`,
            confirmText: 'Switch',
            confirmClass: 'btn-primary',
        });
        if (!ok) return;
        const loader = screenLoader(`Switching to "${name}"…`);
        try {
            await api(U.versionSetActive(id), { method: 'POST' });
            setTimeout(() => location.reload(), 150);
        } catch (err) {
            loader.hide();
            toast(err.message, 'error');
        }
    });

    $id('saveNewVersionBtn')?.addEventListener('click', async (e) => {
        const btn = e.currentTarget;
        const versionName = ($id('newVersionName').value || '').trim();
        if (!versionName) {
            toast('Give the version a name.', 'error');
            $id('newVersionName').focus();
            return;
        }
        const body = {
            versionName,
            description: $id('newVersionDescription').value || '',
            setActive: $id('newVersionSetActive').checked ? 1 : 0,
        };
        const sourceId = $id('newVersionSource').value;
        if (sourceId) body.sourceVersionId = parseInt(sourceId, 10);
        btn.disabled = true;
        const loader = screenLoader(`Creating "${versionName}"…`);
        try {
            await api(U.versionStore(), { method: 'POST', body });
            closeSheet('versionSheet');
            setTimeout(() => location.reload(), 150);
        } catch (err) {
            loader.hide();
            toast(err.message, 'error');
            btn.disabled = false;
        }
    });

    $id('manageVersionBtn')?.addEventListener('click', () => {
        const active = activeVersionChip();
        if (!active) {
            toast('No active version to manage.', 'error');
            return;
        }
        const isOriginal = active.getAttribute('data-is-original') === '1';
        $id('renameVersionId').value = active.getAttribute('data-version-id');
        $id('renameVersionName').value = active.getAttribute('data-version-name') || '';
        $id('renameVersionDescription').value = active.getAttribute('data-version-description') || '';
        $id('deleteVersionZone').classList.toggle('hidden', isOriginal);
        $id('originalVersionHint').classList.toggle('hidden', !isOriginal);
        // Duplicate is only offered while there's room for another version.
        $id('duplicateVersionZone')?.classList.toggle('hidden', $qsa('#versionStrip .version-chip').length >= MAX_VERSIONS);
        openSheet('manageVersionSheet');
    });

    // Duplicate the version currently open in the manage sheet — a full fork
    // (activities + items + lots/workers + date notes), left inactive.
    $id('duplicateVersionBtn')?.addEventListener('click', async (e) => {
        const btn = e.currentTarget;
        const id = $id('renameVersionId').value;
        const name = ($id('renameVersionName').value || 'Version').trim();
        if (!id) return;
        if ($qsa('#versionStrip .version-chip').length >= MAX_VERSIONS) {
            toast(`You can have at most ${MAX_VERSIONS} versions. Delete one to make room.`, 'error');
            return;
        }
        btn.disabled = true;
        const loader = screenLoader(`Duplicating "${name}"…`);
        try {
            await api(U.versionStore(), {
                method: 'POST',
                body: {
                    versionName: `Copy of ${name}`.slice(0, 120),
                    description: $id('renameVersionDescription').value || '',
                    setActive: 0,
                    sourceVersionId: parseInt(id, 10),
                },
            });
            closeSheet('manageVersionSheet');
            setTimeout(() => location.reload(), 150);
        } catch (err) {
            loader.hide();
            toast(err.message, 'error');
            btn.disabled = false;
        }
    });

    $id('saveRenameVersionBtn')?.addEventListener('click', async (e) => {
        const btn = e.currentTarget;
        const id = $id('renameVersionId').value;
        const versionName = ($id('renameVersionName').value || '').trim();
        if (!id || !versionName) {
            toast('Give the version a name.', 'error');
            return;
        }
        btn.disabled = true;
        const loader = screenLoader('Saving version…');
        try {
            await api(U.versionUpdate(id), {
                method: 'PUT',
                body: { versionName, description: $id('renameVersionDescription').value || '' },
            });
            closeSheet('manageVersionSheet');
            setTimeout(() => location.reload(), 150);
        } catch (err) {
            loader.hide();
            toast(err.message, 'error');
            btn.disabled = false;
        }
    });

    $id('deleteVersionBtn')?.addEventListener('click', async () => {
        const active = activeVersionChip();
        if (!active) return;
        if (active.getAttribute('data-is-original') === '1') {
            toast('The Original version is the baseline and cannot be deleted.', 'error');
            return;
        }
        const id = active.getAttribute('data-version-id');
        const name = active.getAttribute('data-version-name') || 'version';
        const ok = await confirmAction({
            title: 'Delete version',
            message: `Delete the entire "${name}" version?`,
            detail: 'Every activity inside this version will be soft-deleted with it. The Original version will become active again. This cannot be undone from the activity-level Undo stack.',
            confirmText: 'Delete Version',
        });
        if (!ok) return;
        const loader = screenLoader(`Deleting "${name}"…`);
        try {
            await api(U.versionDelete(id), { method: 'DELETE' });
            closeSheet('manageVersionSheet');
            setTimeout(() => location.reload(), 150);
        } catch (err) {
            loader.hide();
            toast(err.message, 'error');
        }
    });

    /* ================================================================
     * INIT
     * ================================================================ */

    recomputeLotDayZero();
    refreshNameDatalist();
    refreshHistoryBtns();
    refreshItemsEmptyState();
    refreshDayWarnings();   // seed the per-day reminder pills (rain rule fills in once weather loads)

    // The calendar view lives in its own script and drives these.
    window.smOpenActivity = (id) => {
        const card = $qs(`#activitiesList .activity-card[data-id="${id}"]`);
        if (card && card.getAttribute('data-is-done') === '1') {
            openDoneNoteSheet(id, $qs('.activity-card-title', card)?.textContent || 'Activity');
            return Promise.resolve();
        }
        return openEditActivitySheet(id);
    };
    window.smAddActivityOn = openAddActivitySheet;
    window.smMoveActivityToDate = moveSingleActivity;

    // Quick "today & tomorrow" — jump straight to those days for fast access.
    // It scrolls rather than filters, so the rest of the plan stays visible.
    const ttBtn = $id('todayTomorrowBtn');
    function _findGroup(dateIso) {
        return $qs(`#activitiesList .date-group[data-date="${dateIso}"]`);
    }
    function _nearestUpcomingGroup(todayIso) {
        let best = null;
        $qsa('#activitiesList .date-group[data-date]').forEach((g) => {
            const d = g.getAttribute('data-date');
            if (!d || d === '__no-date__' || d < todayIso) return;
            if (!best || d < best.getAttribute('data-date')) best = g;
        });
        return best;
    }
    ttBtn?.addEventListener('click', () => {
        // Scrolling needs the list; hop out of calendar view first.
        if (window.smSetActivitiesView) window.smSetActivitiesView('list');
        const { today, tomorrow } = _ttDates();
        const todayGroup = _findGroup(today);
        const tomorrowGroup = _findGroup(tomorrow);
        const target = todayGroup || tomorrowGroup || _nearestUpcomingGroup(today);
        if (!target) { toast('No upcoming activities to jump to.', 'error'); return; }
        // Defer so a just-triggered view switch has rendered the list.
        setTimeout(() => {
            target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            [todayGroup, tomorrowGroup].filter(Boolean).forEach((g) => {
                // Jumping to a day should show its work, not a folded header.
                g.classList.remove('is-folded');
                OPEN_DAYS.add((g.getAttribute('data-date') || '').trim());
                saveOpenDays();
                g.classList.add('tt-highlight');
                setTimeout(() => g.classList.remove('tt-highlight'), 2200);
            });
        }, 60);
        toast(todayGroup || tomorrowGroup ? 'Jumped to today' : 'Jumped to the next scheduled day');
    });
    window.smTodayTomorrowActive = () => false;

    /* ================================================================
     * 14. Collab Room LIVE MERGE — apply teammates' board changes in
     * place (no reload). Broadcast from the mutation endpoints over the
     * shared private board channel; each type maps to the same local
     * apply function the acting client used. Own echoes are skipped.
     * ================================================================ */
    (function collabLiveMerge() {
        if (!window.Echo) return;   // no realtime configured → nothing to do
        const ME = @json((int) auth()->id());
        const cardOf = (id) => $qs(`#activitiesList .activity-card[data-id="${id}"]`);
        const activeVersion = () => {
            const el = document.querySelector('.version-chip[data-is-active="1"], .version-chip.is-active');
            return el ? (parseInt(el.getAttribute('data-version-id'), 10) || null) : null;
        };
        let reloadTimer = null;
        const softReload = () => { if (!reloadTimer) reloadTimer = setTimeout(() => location.reload(), 400); };
        function applyReorder(items) {
            (items || []).forEach((it) => {
                const c = cardOf(it.id); if (!c) return;
                if (it.targetDate) c.setAttribute('data-target-date', it.targetDate);
                if (Object.prototype.hasOwnProperty.call(it, 'targetEndDate')) c.setAttribute('data-target-end-date', it.targetEndDate || '');
                if (it.sequenceOrder != null) c.setAttribute('data-sequence-order', it.sequenceOrder);
            });
            reorderAndRenumberActivities(true);
        }

        /* A 'reload' says something changed and names what, so the page reload
         * its name promises is the last resort rather than the first move —
         * a teammate ticking a reminder should not cost everyone else their
         * scroll position, their open sheets and a second of blank board.
         * Each branch below is a thing the payload can be applied to directly. */
        async function applyReload(d) {
            if (d.attendanceDate) { applyAttendanceTick(d); return; }
            // Tags travel whole, so the chip row is repainted from the event.
            if (d.id && Array.isArray(d.tags) && cardOf(d.id)) {
                const c = cardOf(d.id);
                c.setAttribute('data-tags', JSON.stringify(d.tags));
                // paintCardTags, not rememberTags: the tag sheet on THIS screen
                // belongs to whatever activity this viewer opened, if any.
                paintCardTags(d.id, d.tags);
                return;
            }
            const moneyDate = d.expenseDate || d.incomeDate;
            if (moneyDate) { refreshDayMoney(moneyDate); return; }
            // Anything else about one card — an appended note, a ticked
            // reminder, a changed roster — comes back complete from show().
            if (d.id && cardOf(d.id)) {
                try {
                    const res = await api(U.show(d.id));
                    if (res && res.data) { _renderCardOrReplace(res.data); return; }
                } catch (_) { /* fall through to the blunt instrument */ }
            }
            softReload();   // day notes, inline notes, markers — page-level
        }

        /* One name, one day, ticked or unticked. Every card already carries
         * what that person is worth, so the row, the block total and the day's
         * cash pill all follow from the DOM — nothing to ask the server. */
        function applyAttendanceTick(d) {
            const rows = $qsa(`#activitiesList .act-check[data-att-date="${d.attendanceDate}"] .act-check-row[data-att-worker="${d.workerId}"]`);
            if (!rows.length) return;
            rows.forEach((row) => {
                const box = row.querySelector('input[type=checkbox]');
                if (box) box.checked = !!d.present;
                row.classList.toggle('is-out', !d.present);
                const block = row.closest('.act-check');
                if (!block) return;
                let sum = 0;
                $qsa('.act-check-row', block).forEach((r) => {
                    if (r.querySelector('input')?.checked) {
                        sum += Number((r.querySelector('.act-check-pay')?.textContent || '').replace(/[^0-9.]/g, '')) || 0;
                    }
                });
                const out = block.querySelector('[data-att-total]');
                if (out) out.textContent = money(sum);
                // A checklist card's labour IS its payable sum, which is what
                // the day header adds up.
                row.closest('.activity-card')?.setAttribute('data-labour', String(sum));
            });
            paintAllDayCash();
        }

        let ch;
        try { ch = window.Echo.private('schedule-board.' + SCHEDULE_ID); } catch (_) { return; }
        ch.listen('.activity', (p) => {
            if (!p || p.actorUserId === ME) return;
            if (p.type === 'version-active') { location.reload(); return; }
            const cur = activeVersion();
            if (p.versionId && cur && p.versionId !== cur) return;   // change is for another version
            const d = p.data || {};
            try {
                switch (p.type) {
                    case 'saved': if (d.id) _renderCardOrReplace(d); break;
                    case 'deleted': if (d.id) _removeCardById(d.id); break;
                    case 'toggle-done': { const c = cardOf(d.id); if (c) applyDoneState(c, !!d.isDone); break; }
                    case 'toggle-hidden': { const c = cardOf(d.id); if (c) { c.classList.toggle('is-hidden', !!d.isHidden); refreshHiddenActivityCount(); } break; }
                    case 'set-date': { const c = cardOf(d.id); if (c) { c.setAttribute('data-target-date', d.targetDate); reorderAndRenumberActivities(true); } break; }
                    case 'reordered': applyReorder(d.items); break;
                    // Async, so the switch's own try/catch cannot see it fail:
                    // it carries its own fallback to the page reload.
                    case 'reload': applyReload(d).catch(softReload); break;
                    default: softReload(); break;   // markers, version bookkeeping, etc.
                }
            } catch (e) { softReload(); }
        });
    })();

    /* ---- Hide fully-done days -------------------------------------------
     * One toolbar toggle folds away every date where ALL activities are
     * checked done. Height-animated with the house easing, remembered per
     * schedule, re-applied after every re-render (both renderers land in
     * the same DOM, so this filter only ever reads the live list). */
    (() => {
        const btn = $id('toggleDoneDaysBtn');
        if (!btn) return;
        const KEY = `actHideDoneDays:${SCHEDULE_ID}`;
        let on = false;
        try { on = localStorage.getItem(KEY) === '1'; } catch (e) { /* private mode */ }
        const EASE = '.28s cubic-bezier(.22,1,.36,1)';
        const allDone = (g) => {
            const cards = $qsa('.activity-card', g);
            return cards.length > 0 && cards.every((c) => c.getAttribute('data-is-done') === '1');
        };
        function fold(g, hide, animate) {
            if (g.classList.contains('done-day-away') === hide) return;
            const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            if (!animate || reduce) { g.classList.toggle('done-day-away', hide); return; }
            if (hide) {
                g.style.height = `${g.offsetHeight}px`;
                g.style.overflow = 'hidden';
                void g.offsetHeight;
                g.style.transition = `height ${EASE}, opacity ${EASE}, margin ${EASE}`;
                g.style.height = '0px'; g.style.opacity = '0';
                g.style.marginTop = '0'; g.style.marginBottom = '0';
                setTimeout(() => { g.classList.add('done-day-away'); g.style.cssText = ''; }, 300);
            } else {
                g.classList.remove('done-day-away');
                const h = g.offsetHeight;
                g.style.height = '0px'; g.style.opacity = '0'; g.style.overflow = 'hidden';
                g.style.marginTop = '0'; g.style.marginBottom = '0';
                void g.offsetHeight;
                g.style.transition = `height ${EASE}, opacity ${EASE}, margin ${EASE}`;
                g.style.height = `${h}px`; g.style.opacity = '1';
                g.style.marginTop = ''; g.style.marginBottom = '';
                setTimeout(() => { g.style.cssText = ''; }, 320);
            }
        }
        function paint() {
            btn.setAttribute('aria-pressed', on ? 'true' : 'false');
            btn.title = on ? 'Show the days where every activity is done' : 'Hide the days where every activity is already done';
            const label = on ? 'Show done days' : 'Hide done days';
            const lbl = $id('toggleDoneDaysLabel');
            if (lbl) lbl.textContent = label;
            const sheetLbl = $id('actDoneDaysLabel');
            if (sheetLbl) sheetLbl.textContent = label;
        }
        function apply(animate) {
            $qsa('#activitiesList .date-group').forEach((g) => fold(g, on && allDone(g), animate));
            paint();
        }
        btn.addEventListener('click', () => {
            on = !on;
            try { localStorage.setItem(KEY, on ? '1' : '0'); } catch (e) { /* fine */ }
            apply(true);
        });
        // Checking a day's last open activity folds it away on the spot;
        // unchecking one brings the day back.
        document.addEventListener('click', (e) => {
            if (on && e.target.closest('.done-check')) setTimeout(() => apply(true), 350);
        });
        document.addEventListener('activities:rendered', () => apply(false));
        apply(false);
    })();

    /* ---- "What the board shows" (phones) ------------------------------
     * One eye button instead of two toggles in a crowded toolbar. The rows
     * forward to the real buttons — which still own the logic and the
     * persistence — and the sheet stays open so both can be set in one go,
     * with the board visibly changing behind it. */
    (() => {
        const btn = $id('viewFilterBtn');
        if (!btn) return;
        const VF_TARGETS = {
            empty: 'toggleEmptyDatesBtn',
            done: 'toggleDoneDaysBtn',
            hidden: 'toggleHiddenBtn',
            contract: 'contractAllBtn',
        };

        /* Day-zero only: the anchors every other date is counted from. Not a
         * toggle over some other button's state — it owns its own, kept per
         * schedule like the rest, and folds away any day left with nothing to
         * show so the board does not fill with empty headers. */
        const DZ_KEY = `actDayZeroOnly:${SCHEDULE_ID}`;
        let dzOnly = false;
        try { dzOnly = localStorage.getItem(DZ_KEY) === '1'; } catch (e) { /* private mode */ }
        function isAnchor(card) {
            return card.getAttribute('data-is-day-zero') === '1'
                || card.getAttribute('data-is-transplant') === '1';
        }
        function applyDayZero() {
            document.body.classList.toggle('only-day-zero', dzOnly);
            let shown = 0;
            $qsa('#activitiesList .date-group').forEach((g) => {
                const cards = $qsa('.activity-card', g);
                const keep = cards.filter(isAnchor).length;
                shown += keep;
                g.classList.toggle('dz-away', dzOnly && keep === 0);
            });
            const state = $id('vfDayZeroState');
            if (state) {
                state.textContent = dzOnly ? 'On' : 'Off';
                state.classList.toggle('is-off', !dzOnly);
            }
            const sub = $id('vfDayZeroSub');
            if (sub) {
                sub.textContent = dzOnly
                    ? (shown === 1 ? '1 anchor on the board' : shown + ' anchors on the board')
                    : 'The DAS 0 / DAP 0 / DAT 0 anchors';
            }
            const empty = $id('dayZeroNone');
            if (empty) empty.classList.toggle('hidden', !dzOnly || shown > 0);
        }
        function paint() {
            const emptyHidden = document.body.classList.contains('hide-empty-dates');
            const doneHidden = $id('toggleDoneDaysBtn')?.getAttribute('aria-pressed') === 'true';
            const hiddenShown = document.body.classList.contains('show-hidden-activities');
            [['vfEmptyState', emptyHidden], ['vfDoneState', doneHidden], ['vfHiddenState', !hiddenShown]].forEach(([id, hidden]) => {
                const el = $id(id);
                if (!el) return;
                el.textContent = hidden ? 'Hidden' : 'Shown';
                el.classList.toggle('is-off', hidden);
            });
            // Nothing hidden, nothing to offer.
            const n = $qsa('#activitiesList .activity-card.is-hidden').length;
            $id('vfHiddenRow')?.classList.toggle('is-gone', n === 0);
            const sub = $id('vfHiddenSub');
            if (sub) sub.textContent = n + (n === 1 ? ' activity kept out of prints' : ' activities kept out of prints');
            btn.classList.toggle('is-filtering', emptyHidden || doneHidden || dzOnly);
            applyDayZero();
        }
        btn.addEventListener('click', () => { paint(); openSheet('viewFilterSheet'); });
        document.addEventListener('click', (e) => {
            const row = e.target.closest('.view-filter-row');
            if (!row) return;
            if (row.dataset.viewFilter === 'dayzero') {
                dzOnly = !dzOnly;
                try { localStorage.setItem(DZ_KEY, dzOnly ? '1' : '0'); } catch (e) { /* fine */ }
                paint();
                return;
            }
            const target = VF_TARGETS[row.dataset.viewFilter];
            if (!target) return;
            $id(target)?.click();
            // Contract all is a one-shot action — get out of the way so the
            // folded board is what you see. The rest are toggles: stay open.
            if (row.dataset.viewFilter === 'contract') closeSheet('viewFilterSheet');
            else setTimeout(paint, 60);   // let the real toggle repaint first
        });
        document.addEventListener('activities:rendered', paint);
        paint();
    })();
});
</script>
