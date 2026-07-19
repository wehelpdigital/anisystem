@extends('layouts.app')

@section('title', 'Irrigation')
@section('page-title', 'Irrigation')
@section('page-subtitle', $schedule->title)
@section('back', route('sm.hub', ['id' => $schedule->id]))

@push('head')
<style>
    .irr-card { border-left: 4px solid var(--irr-color, #1976d2); }
    .irr-card.is-dragging { opacity: 0.45; }
    .irr-drag-handle { cursor: grab; touch-action: none; }
    .irr-drag-handle:active { cursor: grabbing; }
    /* Plain CSS (this block is not processed by Tailwind, so no @apply) */
    .irr-chip-lot, .irr-chip-worker {
        display: inline-flex; align-items: center; border-radius: 9999px;
        padding: 2px 10px; font-size: 0.75rem; line-height: 1.25rem; font-weight: 600;
    }
    .irr-chip-lot { background: #e0e7ff; color: #4338ca; }
    .irr-chip-worker { background: #fef3c7; color: #92400e; }
</style>
@endpush

@section('content')
    @include('sm.partials.module-header', ['schedule' => $schedule, 'module' => 'irrigations'])

    {{-- How the priority bands work --}}
    <div class="card p-4 mb-4 bg-brand-50/60 border-brand-100 flex items-start gap-3">
        <span class="text-xl leading-none shrink-0 mt-0.5">💧</span>
        <p class="text-sm text-gray-700">
            Irrigation entries paint water-management <strong>bands</strong> on the calendar — anchored to each
            lot group's start date (DAS mode) or to fixed dates. When bands overlap on a day, the
            <strong>lower priority number wins</strong> — a P1 Drain splits a P5 Irrigate band.
        </p>
    </div>

    {{-- Desktop add button --}}
    <div class="hidden md:block mb-4">
        <button type="button" class="btn btn-primary w-full" data-irr-add>
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            Add Irrigation Entry
        </button>
    </div>

    <div id="irrigationsList" data-animate-list></div>

    <div id="irrEmpty" class="card p-8 text-center hidden">
        <p class="text-3xl mb-2">💧</p>
        <p class="font-bold text-gray-900 mb-1">No irrigation entries yet</p>
        <p class="text-sm text-gray-500">Add your first water-management window — irrigate, maintain, drain and more.</p>
    </div>

    {{-- Mobile floating action button --}}
    <button type="button" data-irr-add aria-label="Add irrigation entry"
        class="md:hidden fixed bottom-24 right-4 z-30 w-14 h-14 rounded-full bg-brand-600 text-white shadow-lg flex items-center justify-center active:bg-brand-800 transition">
        <svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
    </button>
@endsection

@push('sheets')
    <div class="sheet hidden" id="irrigationSheet" style="--sheet-width:36rem">
        <div class="sheet-handle"></div>
        <div class="sheet-header">
            <h3 class="sheet-title" id="irrSheetTitle">Add Irrigation Entry</h3>
            <button data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
        </div>
        <div class="sheet-body">
            <div class="space-y-4">
                <div>
                    <label class="form-label" for="irrTitle">Title <span class="text-red-500">*</span></label>
                    <input type="text" id="irrTitle" class="form-input" maxlength="255" placeholder="e.g. Establish 3–5 cm water level">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="form-label" for="irrTaskType">Task type</label>
                        <select id="irrTaskType" class="form-select">
                            @foreach (\App\Models\AsScheduleIrrigation::TASK_TYPES as $slug => $label)
                                <option value="{{ $slug }}">{{ \App\Models\AsScheduleIrrigation::TASK_TYPE_ICONS[$slug] }} {{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="form-label" for="irrPriority">Priority</label>
                        <select id="irrPriority" class="form-select">
                            <option value="1">P1 — Highest</option>
                            <option value="2">P2</option>
                            <option value="3">P3</option>
                            <option value="4">P4</option>
                            <option value="5" selected>P5 — Lowest (default)</option>
                        </select>
                        <p class="form-hint">Lower number wins on overlapping days — a P1 Drain splits a P5 Irrigate band.</p>
                    </div>
                </div>

                <div>
                    <label class="form-label" for="irrDescription">Description</label>
                    <textarea id="irrDescription" class="form-textarea" rows="3" maxlength="2000" placeholder="Optional notes for this window"></textarea>
                </div>

                <div>
                    <span class="form-label">Range mode</span>
                    <div data-chip-group data-single="true" id="irrModeGroup" class="flex gap-2">
                        <button type="button" class="chip is-selected" data-value="das">DAS / Day Range</button>
                        <button type="button" class="chip" data-value="date">Specific Dates</button>
                    </div>
                </div>

                <div id="irrDasFields" class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="form-label" for="irrStartDay">Start day (DAS)</label>
                        <input type="number" id="irrStartDay" class="form-input" step="1" value="0">
                    </div>
                    <div>
                        <label class="form-label" for="irrEndDay">End day (DAS)</label>
                        <input type="number" id="irrEndDay" class="form-input" step="1" value="5">
                    </div>
                    <p class="form-hint col-span-2 -mt-2">Days relative to each lot group's start date. Negative values are allowed (before Day 0).</p>
                </div>

                <div id="irrDateFields" class="grid grid-cols-2 gap-4 hidden">
                    <div>
                        <label class="form-label" for="irrStartDate">Start date</label>
                        <input type="date" id="irrStartDate" class="form-input">
                    </div>
                    <div>
                        <label class="form-label" for="irrEndDate">End date</label>
                        <input type="date" id="irrEndDate" class="form-input">
                    </div>
                </div>

                <div>
                    <span class="form-label">Lots</span>
                    @if ($lots->isEmpty())
                        <p class="text-sm text-gray-500">No lots on this schedule yet.</p>
                    @else
                        <div data-chip-group id="irrLotsGroup" class="flex flex-wrap gap-2">
                            @foreach ($lots as $lot)
                                <button type="button" class="chip" data-value="{{ $lot->id }}">{{ $lot->lotName }}</button>
                            @endforeach
                        </div>
                    @endif
                    <p class="form-hint">Leave empty to apply to <strong>all lots</strong>.</p>
                </div>

                <div>
                    <span class="form-label">Workers</span>
                    @if ($workers->isEmpty())
                        <p class="text-sm text-gray-500">No workers on this schedule yet.</p>
                    @else
                        <div data-chip-group id="irrWorkersGroup" class="flex flex-wrap gap-2">
                            @foreach ($workers as $worker)
                                <button type="button" class="chip" data-value="{{ $worker->id }}">{{ $worker->workerName }}</button>
                            @endforeach
                        </div>
                    @endif
                    <p class="form-hint">Optional — assign one or more workers to this window.</p>
                </div>
            </div>
        </div>
        <div class="sheet-footer">
            <button type="button" class="btn btn-ghost" data-sheet-close>Cancel</button>
            <button type="button" class="btn btn-primary" id="irrSaveBtn">Save Entry</button>
        </div>
    </div>
@endpush

@push('scripts')
<script>
(function () {
    const SCHEDULE_ID = @json($schedule->id);
    const TASK_TYPES  = @json(\App\Models\AsScheduleIrrigation::TASK_TYPES);
    const TASK_COLORS = @json(\App\Models\AsScheduleIrrigation::TASK_TYPE_COLORS);
    const TASK_ICONS  = @json(\App\Models\AsScheduleIrrigation::TASK_TYPE_ICONS);
    const LOTS        = @json($lots->map(fn ($l) => ['id' => $l->id, 'lotName' => $l->lotName])->values());
    const WORKERS     = @json($workers->map(fn ($w) => ['id' => $w->id, 'workerName' => $w->workerName])->values());

    const URLS = {
        store:     @json(route('sm.irrigations.store')),
        update:    @json(route('sm.irrigations.update')),
        destroy:   @json(route('sm.irrigations.destroy')),
        duplicate: @json(route('sm.irrigations.duplicate')),
        reorder:   @json(route('sm.irrigations.reorder')),
    };
    const qs = (extra = '') => `?scheduleId=${SCHEDULE_ID}${extra}`;

    const LOT_NAMES    = Object.fromEntries(LOTS.map((l) => [l.id, l.lotName]));
    const WORKER_NAMES = Object.fromEntries(WORKERS.map((w) => [w.id, w.workerName]));

    // Mother palette: P1 darkest red → P5 light gray.
    const PRIO_STYLES = {
        1: ['#9c1c1c', '#ffffff'],
        2: ['#c0392b', '#ffffff'],
        3: ['#e67e22', '#ffffff'],
        4: ['#94a3b8', '#ffffff'],
        5: ['#e5e7eb', '#374151'],
    };
    const MONTHS = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

    /* ---------------- state ---------------- */

    let irrigations = (@json($irrigations)).map(normalizeRow);
    let editingId = null;

    const listEl  = document.getElementById('irrigationsList');
    const emptyEl = document.getElementById('irrEmpty');

    function normalizeRow(row) {
        return {
            id: Number(row.id),
            irrigationTitle: row.irrigationTitle || '',
            description: row.description || '',
            dayMode: row.dayMode === 'date' ? 'date' : 'das',
            startDay: Number(row.startDay ?? 0),
            endDay: Number(row.endDay ?? 0),
            startDate: row.startDate ? String(row.startDate).slice(0, 10) : null,
            endDate: row.endDate ? String(row.endDate).slice(0, 10) : null,
            taskType: row.taskType || 'irrigate',
            priority: Number(row.priority || 5),
            sortOrder: Number(row.sortOrder || 0),
            workerIds: (row.workerIds || []).map(Number),
            lotIds: (row.lotIds || []).map(Number),
        };
    }

    /* ---------------- rendering ---------------- */

    function fmtDatePart(iso, withYear) {
        const [y, m, d] = iso.split('-').map(Number);
        return `${MONTHS[m - 1]} ${d}${withYear ? ', ' + y : ''}`;
    }

    function fmtDateRange(start, end) {
        if (!start) return '';
        if (!end || end === start) return fmtDatePart(start, true);
        const sameYear = start.slice(0, 4) === end.slice(0, 4);
        return `${fmtDatePart(start, !sameYear)} — ${fmtDatePart(end, true)}`;
    }

    function taskMeta(slug) {
        const key = TASK_TYPES[slug] ? slug : 'irrigate';
        return { slug: key, label: TASK_TYPES[key], color: TASK_COLORS[key], icon: TASK_ICONS[key] };
    }

    function renderCard(irr) {
        const meta = taskMeta(irr.taskType);
        const prio = PRIO_STYLES[irr.priority] || PRIO_STYLES[5];
        const badgeText = meta.slug === 'overflow' ? '#3b2f10' : '#ffffff';

        const rangeBadge = irr.dayMode === 'date'
            ? `<span class="badge badge-gray">${escapeHtml(fmtDateRange(irr.startDate, irr.endDate))}</span>`
            : `<span class="badge badge-blue">DAS ${irr.startDay} — ${irr.endDay}</span>`;

        const lotChips = irr.lotIds.length
            ? irr.lotIds.map((id) => `<span class="irr-chip-lot">${escapeHtml(LOT_NAMES[id] || 'Lot #' + id)}</span>`).join(' ')
            : '<span class="text-xs text-gray-500 font-medium">All lots</span>';

        const workerChips = irr.workerIds.length
            ? irr.workerIds.map((id) => `<span class="irr-chip-worker">${escapeHtml(WORKER_NAMES[id] || 'Worker #' + id)}</span>`).join(' ')
            : '<span class="text-xs text-gray-500 font-medium">No workers assigned</span>';

        return `
        <div class="card irr-card mb-3" draggable="true" data-id="${irr.id}" style="--irr-color:${meta.color}">
            <div class="p-4 flex gap-3">
                <div class="irr-drag-handle hidden md:flex items-center text-gray-300 hover:text-gray-500 shrink-0" title="Drag to reorder">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><circle cx="9" cy="6" r="1.6"/><circle cx="15" cy="6" r="1.6"/><circle cx="9" cy="12" r="1.6"/><circle cx="15" cy="12" r="1.6"/><circle cx="9" cy="18" r="1.6"/><circle cx="15" cy="18" r="1.6"/></svg>
                </div>
                <div class="min-w-0 grow">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="font-bold text-gray-900">${escapeHtml(irr.irrigationTitle)}</span>
                        <span class="pill" style="background:${prio[0]};color:${prio[1]}">P${irr.priority}</span>
                        <span class="badge" style="background:${meta.color};color:${badgeText}">${meta.icon} ${escapeHtml(meta.label)}</span>
                        ${rangeBadge}
                    </div>
                    <div class="flex flex-wrap items-center gap-1.5 mt-2">
                        <span class="text-[11px] uppercase tracking-wide font-bold text-gray-400 mr-0.5">Lots</span>${lotChips}
                    </div>
                    <div class="flex flex-wrap items-center gap-1.5 mt-1.5">
                        <span class="text-[11px] uppercase tracking-wide font-bold text-gray-400 mr-0.5">Workers</span>${workerChips}
                    </div>
                    ${irr.description ? `<p class="text-sm text-gray-600 mt-2 whitespace-pre-line">${escapeHtml(irr.description)}</p>` : ''}
                    <div class="flex flex-wrap items-center gap-2 mt-3">
                        <button type="button" class="btn btn-sm btn-white" data-act="edit" data-id="${irr.id}">Edit</button>
                        <button type="button" class="btn btn-sm btn-white" data-act="duplicate" data-id="${irr.id}">Duplicate</button>
                        <button type="button" class="btn btn-sm btn-danger-outline" data-act="delete" data-id="${irr.id}">Delete</button>
                        <span class="md:hidden inline-flex items-center gap-1 ml-auto">
                            <button type="button" class="btn btn-sm btn-ghost px-3" data-act="up" data-id="${irr.id}" aria-label="Move up">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 15l7-7 7 7"/></svg>
                            </button>
                            <button type="button" class="btn btn-sm btn-ghost px-3" data-act="down" data-id="${irr.id}" aria-label="Move down">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                        </span>
                    </div>
                </div>
            </div>
        </div>`;
    }

    function renderList() {
        listEl.innerHTML = irrigations.map(renderCard).join('');
        emptyEl.classList.toggle('hidden', irrigations.length > 0);
    }

    /* ---------------- sheet form ---------------- */

    const modeGroup    = document.getElementById('irrModeGroup');
    const lotsGroup    = document.getElementById('irrLotsGroup');
    const workersGroup = document.getElementById('irrWorkersGroup');

    function currentMode() {
        return (modeGroup && chipValues(modeGroup)[0]) === 'date' ? 'date' : 'das';
    }

    function setMode(mode) {
        modeGroup.querySelectorAll('.chip').forEach((c) => {
            c.classList.toggle('is-selected', c.getAttribute('data-value') === mode);
        });
        applyModeVisibility();
    }

    function applyModeVisibility() {
        const mode = currentMode();
        document.getElementById('irrDasFields').classList.toggle('hidden', mode !== 'das');
        document.getElementById('irrDateFields').classList.toggle('hidden', mode !== 'date');
    }

    modeGroup?.addEventListener('chips:change', applyModeVisibility);

    function setChipSelection(groupEl, ids) {
        if (!groupEl) return;
        const set = new Set((ids || []).map(String));
        groupEl.querySelectorAll('.chip').forEach((c) => {
            c.classList.toggle('is-selected', set.has(c.getAttribute('data-value')));
        });
    }

    function openForm(irr = null) {
        editingId = irr ? irr.id : null;
        document.getElementById('irrSheetTitle').textContent = irr ? 'Edit Irrigation Entry' : 'Add Irrigation Entry';
        document.getElementById('irrTitle').value       = irr ? irr.irrigationTitle : '';
        document.getElementById('irrTaskType').value    = irr ? taskMeta(irr.taskType).slug : 'irrigate';
        document.getElementById('irrPriority').value    = String(irr ? irr.priority : 5);
        document.getElementById('irrDescription').value = irr ? irr.description : '';
        document.getElementById('irrStartDay').value    = irr ? irr.startDay : 0;
        document.getElementById('irrEndDay').value      = irr ? irr.endDay : 5;
        document.getElementById('irrStartDate').value   = irr?.startDate || '';
        document.getElementById('irrEndDate').value     = irr?.endDate || '';
        setMode(irr ? irr.dayMode : 'das');
        setChipSelection(lotsGroup, irr ? irr.lotIds : []);
        setChipSelection(workersGroup, irr ? irr.workerIds : []);
        openSheet('irrigationSheet');
    }

    document.querySelectorAll('[data-irr-add]').forEach((btn) => {
        btn.addEventListener('click', () => openForm(null));
    });

    /* ---------------- save ---------------- */

    document.getElementById('irrSaveBtn').addEventListener('click', async function () {
        const btn = this;
        const mode = currentMode();
        const title = document.getElementById('irrTitle').value.trim();
        const startDayRaw = document.getElementById('irrStartDay').value;
        const endDayRaw   = document.getElementById('irrEndDay').value;
        const startDate   = document.getElementById('irrStartDate').value || null;
        const endDate     = document.getElementById('irrEndDate').value || null;

        if (!title) { toast('Enter a title for this irrigation entry.', 'error'); return; }
        if (mode === 'das') {
            if (startDayRaw === '' || endDayRaw === '') { toast('Enter both start and end day for the DAS range.', 'error'); return; }
            if (parseInt(endDayRaw, 10) < parseInt(startDayRaw, 10)) { toast('End day must be greater than or equal to start day.', 'error'); return; }
        } else {
            if (!startDate || !endDate) { toast('Pick both start and end dates.', 'error'); return; }
            if (endDate < startDate) { toast('End date must be on or after the start date.', 'error'); return; }
        }

        const payload = {
            irrigationTitle: title,
            description: document.getElementById('irrDescription').value.trim() || null,
            dayMode: mode,
            startDay: startDayRaw === '' ? null : parseInt(startDayRaw, 10),
            endDay: endDayRaw === '' ? null : parseInt(endDayRaw, 10),
            startDate: startDate,
            endDate: endDate,
            taskType: document.getElementById('irrTaskType').value,
            priority: parseInt(document.getElementById('irrPriority').value, 10) || 5,
            assignedWorkerId: null, // legacy single-worker column — back-filled server-side from workerIds
            workerIds: workersGroup ? chipValues(workersGroup).map(Number) : [],
            lotIds: lotsGroup ? chipValues(lotsGroup).map(Number) : [],
        };

        const url = editingId
            ? URLS.update + qs('&id=' + editingId)
            : URLS.store + qs();

        btn.disabled = true;
        try {
            const res = await api(url, { method: editingId ? 'PUT' : 'POST', body: payload });
            const row = normalizeRow(res.data);
            const idx = irrigations.findIndex((i) => i.id === row.id);
            if (idx >= 0) irrigations[idx] = row; else irrigations.push(row);
            renderList();
            closeSheet('irrigationSheet');
            toast(res.message);
        } catch (e) {
            toast(e.message, 'error');
        } finally {
            btn.disabled = false;
        }
    });

    /* ---------------- card actions ---------------- */

    listEl.addEventListener('click', async (e) => {
        const btn = e.target.closest('[data-act]');
        if (!btn) return;
        const id = Number(btn.dataset.id);
        const irr = irrigations.find((i) => i.id === id);
        if (!irr) return;

        if (btn.dataset.act === 'edit') {
            openForm(irr);

        } else if (btn.dataset.act === 'duplicate') {
            btn.disabled = true;
            try {
                const res = await api(URLS.duplicate + qs('&id=' + id), { method: 'POST' });
                const copy = normalizeRow(res.data);
                const idx = irrigations.findIndex((i) => i.id === id);
                irrigations.splice(idx + 1, 0, copy);
                renderList();
                sendReorder();
                toast(res.message);
                // Mother behavior: auto-open the copy's edit form so the user
                // can adjust and save when ready.
                openForm(copy);
            } catch (err) {
                toast(err.message, 'error');
            } finally {
                btn.disabled = false;
            }

        } else if (btn.dataset.act === 'delete') {
            const ok = await confirmAction({
                title: 'Delete irrigation entry?',
                message: `"${irr.irrigationTitle}" will be removed from the schedule.`,
                confirmText: 'Delete',
            });
            if (!ok) return;
            try {
                const res = await api(URLS.destroy + qs('&id=' + id), { method: 'DELETE' });
                irrigations = irrigations.filter((i) => i.id !== id);
                renderList();
                toast(res.message);
            } catch (err) {
                toast(err.message, 'error');
            }

        } else if (btn.dataset.act === 'up' || btn.dataset.act === 'down') {
            const idx = irrigations.findIndex((i) => i.id === id);
            const to = btn.dataset.act === 'up' ? idx - 1 : idx + 1;
            if (to < 0 || to >= irrigations.length) return;
            [irrigations[idx], irrigations[to]] = [irrigations[to], irrigations[idx]];
            renderList();
            sendReorder();
        }
    });

    /* ---------------- reorder (silent success, toast on error) ---------------- */

    async function sendReorder() {
        const items = irrigations.map((irr, i) => {
            irr.sortOrder = i + 1;
            return { id: irr.id, sortOrder: i + 1 };
        });
        if (!items.length) return;
        try {
            await api(URLS.reorder + qs(), { method: 'POST', body: { items } });
        } catch (e) {
            toast(e.message, 'error');
        }
    }

    /* Desktop drag-drop: cards are draggable, but only when the drag starts
       on the handle — otherwise text selection inside the card would fight
       the drag gesture. */
    let dragCard = null;

    listEl.addEventListener('mousedown', (e) => {
        const card = e.target.closest('.irr-card');
        if (!card) return;
        card.dataset.dragArmed = e.target.closest('.irr-drag-handle') ? '1' : '';
    });

    listEl.addEventListener('dragstart', (e) => {
        const card = e.target.closest('.irr-card');
        if (!card || card.dataset.dragArmed !== '1') { e.preventDefault(); return; }
        dragCard = card;
        card.classList.add('is-dragging');
        e.dataTransfer.effectAllowed = 'move';
        try { e.dataTransfer.setData('text/plain', card.dataset.id); } catch {}
    });

    listEl.addEventListener('dragover', (e) => {
        if (!dragCard) return;
        e.preventDefault();
        e.dataTransfer.dropEffect = 'move';
        const over = e.target.closest('.irr-card');
        if (!over || over === dragCard) return;
        const rect = over.getBoundingClientRect();
        const before = e.clientY < rect.top + rect.height / 2;
        listEl.insertBefore(dragCard, before ? over : over.nextSibling);
    });

    listEl.addEventListener('drop', (e) => { if (dragCard) e.preventDefault(); });

    listEl.addEventListener('dragend', () => {
        if (!dragCard) return;
        dragCard.classList.remove('is-dragging');
        dragCard.dataset.dragArmed = '';
        dragCard = null;
        // Rebuild state from the DOM order, then persist 1..N.
        const order = [...listEl.querySelectorAll('.irr-card')].map((el) => Number(el.dataset.id));
        irrigations.sort((a, b) => order.indexOf(a.id) - order.indexOf(b.id));
        sendReorder();
    });

    renderList();
})();
</script>
@endpush
