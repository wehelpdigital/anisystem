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
{{-- The script itself is resources/js/board.js, a file the phone keeps (2026-11-26). Only what the server knows is
     written here: a line in this list, read there as SRV.name. --}}
    @php $offMap = \App\Models\AsScheduleWorker::offMapFor((int) $schedule->id); @endphp
    @php
        $workerOff = $schedule->workers->mapWithKeys(fn ($w) => [$w->id => [
            'days' => array_values($offMap[$w->id]['days'] ?? []),
            'dates' => array_values(array_filter($offMap[$w->id]['dates'] ?? [])),
        ]])->all();
    @endphp
    @php
        /* Built here rather than inside @json: the directive takes one
         * expression on one line, and a nested map over the catalogue is
         * neither.
         *
         * ONLY THE CROPS THIS SCHEDULE ACTUALLY GROWS.
         *
         * The whole catalogue used to be written into every board — which was
         * fine at seven crops and is eighty-five now, most of them for farms
         * that will never plant them. A schedule needs the tables for the
         * crops on its own lots and nothing else.
         *
         * Each is laid out against that lot's own days to maturity, so the
         * board's pill and the growth module agree on where the crop is. Two
         * lots of the same crop with different varieties get their own table,
         * keyed by lot rather than by crop, because that is the level at
         * which the answer actually differs. (CropStages::boardTable is the
         * one shape; a lot made later brings its own in the same shape.)
         */
        $cropTables = [];
        $lotStages = [];
        foreach ($schedule->lots as $lot) {
            $board = \App\Support\CropStages::boardTable($lot->crop, $lot->maturityDays());
            if (! $board) { continue; }
            [$tag, $table] = $board;
            $lotStages[$lot->id] = $tag;
            $cropTables[$tag] ??= $table;
        }
    @endphp
<script>
window.__BOARD = {
    SCHEDULE_ID: @json($schedule->id),
    MAPS_URL: @json(route('maps.page')),
    MAP_SAVES_URL: @json(route('sm.lots.map.choices')),
    DAY_TYPE_DEFAULT: @json($schedule->dayType ?: 'DAS'),
    TODAY_KEY: @json(now('Asia/Manila')->toDateString()),
    STORAGE_BASE: @json(asset('storage')),
    ACTIVITY_TYPE_LABELS: @json($activityTypes),
    SPRAY_TYPES: @json(\App\Models\AsScheduleActivity::SPRAY_TYPES),
    SPRAY_DIR_LABELS: @json(\App\Models\AsScheduleActivity::SPRAY_DIRECTIONS),
    WATER_TASK_LABELS: @json(\App\Models\AsScheduleActivity::WATER_TASKS),
    WATER_TASK_COLORS: @json(\App\Models\AsScheduleActivity::WATER_TASK_COLORS),
    CAN_EDIT: @json(\App\Support\WorkerContext::canEdit()),
    ME_WORKER_ID: @json(optional(\App\Support\WorkerContext::activeGrant())->scheduleWorkerId),
    IS_WORKER: @json((bool) \App\Support\WorkerContext::activeGrant()),
    MONEY_HIDDEN: @json(\App\Support\MoneyView::hidden()),
    MONEY_RO: @json(! \App\Support\MoneyView::writable()),
    CAN_ADD_NOTES: @json(\App\Support\WorkerContext::canAddNotes()),
    LOT_NAMES: @json($schedule->lots->mapWithKeys(fn ($l) => [$l->id => $l->lotName])),
    LOT_VARIETIES: @json($schedule->lots->mapWithKeys(fn ($l) => [$l->id => $l->variety])),
    LOT_DAY_TYPE: @json($schedule->lots->mapWithKeys(fn ($l) => [$l->id => ($l->dayType ?: 'DAT')])),
    WORKER_NAMES: @json($schedule->workers->mapWithKeys(fn ($w) => [$w->id => $w->workerName])),
    WORKER_RATES: @json($schedule->workers->mapWithKeys(fn ($w) => [$w->id => (float) ($w->costPerHalfDay ?? 0)])),
    WORKER_OFF: @json($workerOff),
    LOT_CROP: @json($schedule->lots->mapWithKeys(fn ($l) => [$l->id => $l->crop])),
    CROP_STAGES: @json((object) $cropTables),
    LOT_STAGE_KEY: @json((object) $lotStages),
    LOT_TREE_AGE: @json((object) $schedule->lots->mapWithKeys(fn ($l) => [$l->id => $l->treeAgeMonths()])->all()),
    LOT_STAGE_SHIFT: @json((object) $schedule->lots->mapWithKeys(fn ($l) => [$l->id => (int) ($l->growthShiftDays ?? 0)])->all()),
    LOT_REALIGN: @json((object) $schedule->lots->filter(fn ($l) => $l->growthRealignedAt && is_array($l->growthRealign))->mapWithKeys(fn ($l) => [$l->id => $l->realignPayload()])->all()),
    LOT_MANUAL_DAY_ZERO: @json($schedule->lots->mapWithKeys(fn ($l) => [$l->id => $l->dayZeroDate ? $l->dayZeroDate->format('Y-m-d') : null])),
    LOT_MANUAL_TRANSPLANT: @json($schedule->lots->mapWithKeys(fn ($l) => [$l->id => $l->transplantDate ? $l->transplantDate->format('Y-m-d') : null])),
    LOT_DELAY: @json($schedule->lots->mapWithKeys(fn ($l) => [$l->id => (int) ($l->delayDays ?? 0)])),
    u_store: @json(e(route('sm.activities.store'))),
    u_show: @json(e(route('sm.activities.show'))),
    u_update: @json(e(route('sm.activities.update'))),
    u_destroy: @json(e(route('sm.activities.destroy'))),
    u_imageUpload: @json(e(route('sm.activities.image-upload'))),
    u_lotStore: @json(e(route('sm.lots.store'))),
    u_workerStore: @json(e(route('sm.workers.store'))),
    u_toggleHidden: @json(e(route('sm.activities.toggle-hidden'))),
    u_marker: @json(e(route('sm.activities.marker'))),
    u_toggleDone: @json(e(route('sm.activities.toggle-done'))),
    u_appendNote: @json(e(route('sm.activities.append-note'))),
    u_duplicate: @json(e(route('sm.activities.duplicate'))),
    u_reorder: @json(e(route('sm.activities.reorder'))),
    u_restore: @json(e(route('sm.activities.restore'))),
    u_toDraft: @json(e(route('sm.activities.to-draft'))),
    u_fromDraft: @json(e(route('sm.activities.from-draft'))),
    u_drafts: @json(e(route('sm.activities.drafts'))),
    u_labor: @json(e(route('sm.activities.labor'))),
    u_dateNoteSave: @json(e(route('sm.activities.date-note.save'))),
    u_dateNoteDelete: @json(e(route('sm.activities.date-note.delete'))),
    u_inlineNoteSave: @json(e(route('sm.activities.inline-note.save'))),
    u_noteCapture: @json(e(route('sm.activities.note-capture'))),
    u_inlineNoteDelete: @json(e(route('sm.activities.inline-note.delete'))),
    u_noteImageUpload: @json(e(route('sm.notes.image-upload'))),
    u_noteVideoUpload: @json(e(route('sm.notes.video-upload'))),
    u_noteAudioUpload: @json(e(route('sm.notes.audio-upload'))),
    u_weather: @json(e(route('sm.weather'))),
    u_attendance: @json(e(route('sm.attendance'))),
    u_attendanceMark: @json(e(route('sm.attendance.mark'))),
    u_reminderToggle: @json(e(route('sm.activities.reminder-toggle'))),
    u_taggables: @json(e(route('sm.activities.taggables'))),
    u_tag: @json(e(route('sm.activities.tag'))),
    u_untag: @json(e(route('sm.activities.untag'))),
    u_dayIncomeList: @json(e(route('sm.activities.day-income.list'))),
    u_dayIncomeSave: @json(e(route('sm.activities.day-income.save'))),
    u_dayIncomeDelete: @json(e(route('sm.activities.day-income.delete'))),
    u_dayExpenseList: @json(e(route('sm.activities.day-expense.list'))),
    u_dayExpenseSave: @json(e(route('sm.activities.day-expense.save'))),
    u_dayExpenseDelete: @json(e(route('sm.activities.day-expense.delete'))),
    u_dayExpenseOrder: @json(e(route('sm.activities.day-expense.reorder'))),
    u_dayIncomeOrder: @json(e(route('sm.activities.day-income.reorder'))),
    u_dayMoneyBlockMove: @json(e(route('sm.activities.day-money.block-move'))),
    u_markerSave: @json(e(route('sm.markers.save'))),
    u_markerDelete: @json(e(route('sm.markers.destroy'))),
    u_versionStore: @json(e(route('sm.activity-versions.store'))),
    u_versionUpdate: @json(e(route('sm.activity-versions.update'))),
    u_versionDelete: @json(e(route('sm.activity-versions.destroy'))),
    u_versionSetActive: @json(e(route('sm.activity-versions.set-active'))),
    UNDO_JOURNAL_URL: @json(route('sm.undo.get')),
    x_images_voice_recorder_png: @json(e(asset('images/voice-recorder.png'))),
    ADV_URL: @json(route('sm.activity.advanced')),
    MAY_DRAW: @json(\App\Support\WorkerContext::canWriteModule('draw')),
    MAY_MAP: @json(\App\Support\WorkerContext::canWriteModule('maps')),
    MAY_SHOOT: @json(\App\Support\WorkerContext::canWriteModule('camera')),
    MAY_FILM: @json(\App\Support\WorkerContext::canWriteModule('video')),
    MAY_SPEAK: @json(\App\Support\WorkerContext::canWriteModule('voice')),
    x_names: @json($itemCatalog ?? ['names'=>[], 'prices'=>[], 'units'=>[]]),
    x_App_Support_Region_symbol: @json(e(\App\Support\Region::symbol())),
    GW_LIST_URL: @json(route('workers.global.list')),
    x_savedWeatherDates: @json($savedWeatherDates ?? []),
    x_sm_weather_saved: @json(e(route('sm.weather.saved'))),
    x_sm_email_audience: @json(route('sm.email.audience')),
    x_sm_email_activity: @json(route('sm.email.activity')),
    x_sm_email_day: @json(route('sm.email.day')),
    LOT_DELAY_URL: @json(route('sm.lots.delay')),
    SHARE_COFARMERS_URL: @json(route('community.cofarmers.list')),
    SHARE_MSG_BASE: @json(url('/app/community/messages')),
    DAY_TYPE_URL: @json(route('sm.day-type')),
    x_notes_hub_draw: @json(route('notes.hub.draw')),
    x_sm_inventory_move_update: @json(e(route('sm.inventory.move.update'))),
    x_sm_inventory_list: @json(e(route('sm.inventory.list'))),
    x_sm_labor_report: @json(route('sm.labor.report')),
    ME: @json((int) auth()->id()),
};
</script>
<script src="{{ \Illuminate\Support\Facades\Vite::asset('resources/js/board.js') }}"></script>
