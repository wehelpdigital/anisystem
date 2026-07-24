@extends(request()->boolean('partial') ? 'layouts.partial' : 'layouts.app')

@section('title', 'Workers — ' . $schedule->title)
@section('page-title', 'Workers')
@section('page-subtitle', $schedule->title)
@section('back', route('sm.hub', ['id' => $schedule->id]))

@section('content')
    @include('sm.partials.module-header', ['schedule' => $schedule, 'module' => 'workers'])

    <div class="max-w-3xl">
        <div class="hidden md:flex justify-end mb-4">
            <button type="button" class="btn btn-primary" data-add-worker>
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14m-7-7h14"/></svg>
                Add Worker
            </button>
        </div>

        <div id="workersList" class="space-y-3" data-animate-list>

        <div id="workersEmpty" class="card hidden">
            <div class="card-body text-center py-12">
                <div class="mx-auto w-14 h-14 rounded-2xl bg-brand-50 flex items-center justify-center mb-3">
                    <svg class="w-7 h-7 text-brand-600" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-1a4 4 0 00-4-4h-1M9 11a4 4 0 100-8 4 4 0 000 8zm8 0a3 3 0 100-6M2 20v-1a5 5 0 015-5h4a5 5 0 015 5v1H2z"/></svg>
                </div>
                <h2 class="font-bold text-gray-900 mb-1">No workers yet</h2>
                <p class="text-sm text-gray-500 mb-4">Add the people who will work this schedule — their cost, skills and off days feed labor costs and assignments.</p>
                <button type="button" class="btn btn-primary" data-add-worker>Add your first worker</button>
            </div>
        </div>
    </div>

    {{-- Mobile floating action button --}}
    <button type="button" data-add-worker
        class="md:hidden fixed bottom-24 right-4 z-30 w-14 h-14 rounded-full btn-primary shadow-lg flex items-center justify-center"
        aria-label="Add worker">
        <svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14m-7-7h14"/></svg>
    </button>
@endsection

@push('sheets')
{{-- Add / edit worker --}}
<div class="sheet hidden" id="workerSheet" style="--sheet-width:36rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title" id="workerSheetTitle">Add Worker</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body space-y-4">
        <input type="hidden" id="workerId" value="">

        <div>
            <label for="workerName" class="form-label">Worker Name <span class="text-red-500">*</span></label>
            <input type="text" id="workerName" maxlength="255" class="form-input" placeholder="e.g. Juan Dela Cruz">
        </div>

        <div>
            <label for="workerEmail" class="form-label">Email <span class="text-gray-400 font-normal">(optional)</span></label>
            <input type="email" id="workerEmail" maxlength="255" class="form-input" placeholder="e.g. juan@email.com">
            <p class="form-hint">Used to email this worker today's or tomorrow's plan from Quick Share.</p>
        </div>

        <div class="grid grid-cols-2 gap-3">
            <div>
                <label for="workerCost" class="form-label">Cost / Half Day</label>
                <div class="relative">
                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-500 font-semibold pointer-events-none">₱</span>
                    <input type="number" id="workerCost" min="0" step="0.01" class="form-input pl-9!" placeholder="0.00">
                </div>
            </div>
            <div>
                <label for="workerPriority" class="form-label">Priority</label>
                <input type="number" id="workerPriority" min="1" class="form-input" value="1">
                <p class="form-hint">1 = first pick</p>
            </div>
        </div>

        <div>
            <span class="form-label">Skills</span>
            <div id="workerSkills" data-chip-group class="flex flex-wrap gap-2">
                @foreach (\App\Models\AsScheduleWorker::SKILLS as $slug => $label)
                    <button type="button" class="chip" data-value="{{ $slug }}">{{ $label }}</button>
                @endforeach
            </div>
        </div>

        <div>
            <label for="workerNotes" class="form-label">Notes</label>
            <textarea id="workerNotes" rows="3" maxlength="2000" class="form-textarea" placeholder="Anything worth remembering about this worker…"></textarea>
        </div>
    </div>
    <div class="sheet-footer">
        <button type="button" class="btn btn-ghost" data-sheet-close>Cancel</button>
        <button type="button" id="saveWorkerBtn" class="btn btn-primary">Save Worker</button>
    </div>
</div>

{{-- Availability rules --}}
<div class="sheet hidden" id="rulesSheet" style="--sheet-width:34rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title" id="rulesSheetTitle">Availability Rules</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body space-y-5">
        <input type="hidden" id="rulesWorkerId" value="">

        <div>
            <span class="form-label">Weekly off days</span>
            <p class="form-hint mt-0! mb-2">Tap the days this worker is NOT available.</p>
            <div id="rulesDayGroup" data-chip-group class="flex flex-wrap gap-2">
                @foreach (['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'] as $i => $day)
                    <button type="button" class="chip px-3!" data-value="{{ $i }}" data-day="{{ $i }}">{{ $day }}</button>
                @endforeach
            </div>
        </div>

        <div>
            <span class="form-label">Specific off dates</span>
            <div class="flex gap-2 mb-3">
                <input type="date" id="rulesDateInput" class="form-input">
                <button type="button" id="rulesAddDateBtn" class="btn btn-white shrink-0">Add</button>
            </div>
            <div id="offDatesList" class="flex flex-wrap gap-2"></div>
            <p id="offDatesEmpty" class="text-sm text-gray-400">No off dates added.</p>
        </div>
    </div>
    <div class="sheet-footer">
        <button type="button" class="btn btn-ghost" data-sheet-close>Cancel</button>
        <button type="button" id="saveRulesBtn" class="btn btn-primary">Save Rules</button>
    </div>
</div>
@endpush

@push('scripts')
@php
    $jsWorkers = $schedule->workers->map(fn ($w) => [
        'id' => $w->id,
        'workerName' => $w->workerName,
        'email' => $w->email,
        'costPerHalfDay' => $w->costPerHalfDay,
        'priority' => (int) $w->priority,
        'skills' => $w->skills ?? [],
        'notes' => $w->notes,
        'offDays' => $w->offDays->pluck('dayOfWeek')->map(fn ($d) => (int) $d)->values(),
        'offDates' => $w->offDates->map(fn ($d) => $d->offDate->format('Y-m-d'))->values(),
    ])->values();
@endphp
<script>
(() => {
const __init = () => {
    const SCHEDULE_ID = {{ $schedule->id }};
    const SKILLS = @json(\App\Models\AsScheduleWorker::SKILLS);
    const DAY_NAMES = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

    let WORKERS = @json($jsWorkers);

    const list = document.getElementById('workersList');
    const empty = document.getElementById('workersEmpty');

    const fmtDate = (iso) => new Date(`${iso}T00:00:00`).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });

    function offRulesSummary(w) {
        const parts = [];
        if ((w.offDays || []).length) {
            parts.push('Off: ' + [...w.offDays].sort((a, b) => a - b).map((d) => DAY_NAMES[d]).join(', '));
        }
        if ((w.offDates || []).length) {
            parts.push(`${w.offDates.length} off ${w.offDates.length === 1 ? 'date' : 'dates'}`);
        }
        return parts.length ? parts.join(' · ') : 'No off rules';
    }

    function workerCardHtml(w) {
        const skills = (w.skills || [])
            .map((s) => `<span class="badge badge-gray">${escapeHtml(SKILLS[s] || s)}</span>`)
            .join(' ');

        return `
            <div class="card-body py-4!">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <div class="flex items-center gap-2 flex-wrap mb-1">
                            <span class="badge bg-brand-600 text-white" title="Priority — 1 is first pick">#${Number(w.priority) || 1}</span>
                            <h3 class="font-bold text-gray-900">${escapeHtml(w.workerName)}</h3>
                        </div>
                        <p class="text-sm text-gray-600 mb-1.5">${fmtPeso(w.costPerHalfDay)} <span class="text-gray-400">/ half day</span></p>
                        ${w.email ? `<p class="text-xs text-gray-500 mb-1.5 flex items-center gap-1 truncate"><svg class="w-3.5 h-3.5 shrink-0 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>${escapeHtml(w.email)}</p>` : ''}
                        ${skills ? `<div class="flex flex-wrap gap-1.5 mb-1.5">${skills}</div>` : ''}
                        <p class="text-xs ${offRulesSummary(w) === 'No off rules' ? 'text-gray-400' : 'text-orange-700 font-medium'} off-rules-line">${escapeHtml(offRulesSummary(w))}</p>
                        ${w.notes ? `<p class="text-xs text-gray-500 mt-1.5">${escapeHtml(w.notes)}</p>` : ''}
                    </div>
                    <div class="flex flex-col sm:flex-row items-end sm:items-center gap-1.5 shrink-0">
                        <button type="button" class="btn btn-white btn-sm" data-rules-worker="${w.id}">Rules</button>
                        <button type="button" class="btn btn-white btn-sm" data-edit-worker="${w.id}">Edit</button>
                        <button type="button" class="btn btn-ghost btn-sm px-2.5! text-red-500 hover:bg-red-50!" data-delete-worker="${w.id}" aria-label="Delete worker">
                            <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.9 12.1a2 2 0 01-2 1.9H7.9a2 2 0 01-2-1.9L5 7m3 0V5a2 2 0 012-2h4a2 2 0 012 2v2m-11 0h16m-10 4v6m4-6v6"/></svg>
                        </button>
                    </div>
                </div>
            </div>`;
    }

    function renderList() {
        WORKERS.sort((a, b) => (a.priority - b.priority) || String(a.workerName).localeCompare(String(b.workerName)));
        list.innerHTML = '';
        WORKERS.forEach((w) => {
            const card = document.createElement('div');
            card.className = 'card';
            card.dataset.workerCard = w.id;
            card.innerHTML = workerCardHtml(w);
            list.appendChild(card);
        });
        empty.classList.toggle('hidden', WORKERS.length > 0);
    }

    /* ---------------- Worker sheet ---------------- */

    function openWorkerSheet(w = null) {
        document.getElementById('workerSheetTitle').textContent = w ? 'Edit Worker' : 'Add Worker';
        document.getElementById('workerId').value = w ? w.id : '';
        document.getElementById('workerName').value = w ? (w.workerName || '') : '';
        document.getElementById('workerEmail').value = w ? (w.email || '') : '';
        document.getElementById('workerCost').value = w ? (parseFloat(w.costPerHalfDay) || 0) : '';
        document.getElementById('workerPriority').value = w ? (w.priority || 1) : 1;
        document.getElementById('workerNotes').value = w ? (w.notes || '') : '';
        const selected = (w?.skills || []).map(String);
        document.querySelectorAll('#workerSkills .chip').forEach((c) => {
            c.classList.toggle('is-selected', selected.includes(c.getAttribute('data-value')));
        });
        openSheet('workerSheet');
    }

    document.getElementById('saveWorkerBtn').addEventListener('click', async (e) => {
        const btn = e.currentTarget;
        const id = document.getElementById('workerId').value;
        const body = {
            workerName: document.getElementById('workerName').value.trim(),
            email: document.getElementById('workerEmail').value.trim() || null,
            costPerHalfDay: document.getElementById('workerCost').value || 0,
            priority: Number(document.getElementById('workerPriority').value) || 1,
            skills: chipValues(document.getElementById('workerSkills')),
            notes: document.getElementById('workerNotes').value || null,
        };

        if (!body.workerName) {
            toast('Worker name is required.', 'error');
            document.getElementById('workerName').focus();
            return;
        }

        const url = id
            ? `{{ route('sm.workers.update') }}?scheduleId=${SCHEDULE_ID}&id=${id}`
            : `{{ route('sm.workers.store') }}?scheduleId=${SCHEDULE_ID}`;

        btn.disabled = true;
        try {
            const res = await api(url, { method: id ? 'PUT' : 'POST', body });
            toast(res.message);
            const prev = WORKERS.find((w) => String(w.id) === String(res.data.id));
            const saved = {
                id: res.data.id,
                workerName: res.data.workerName,
                email: res.data.email,
                costPerHalfDay: res.data.costPerHalfDay,
                priority: Number(res.data.priority) || 1,
                skills: res.data.skills || [],
                notes: res.data.notes,
                offDays: prev ? prev.offDays : [],
                offDates: prev ? prev.offDates : [],
            };
            if (prev) {
                WORKERS[WORKERS.indexOf(prev)] = saved;
            } else {
                WORKERS.push(saved);
            }
            renderList();
            closeSheet('workerSheet');
        } catch (err) {
            toast(err.message, 'error');
        } finally {
            btn.disabled = false;
        }
    });

    /* ---------------- Rules sheet ---------------- */

    let offDatesState = [];

    function renderOffDates() {
        const wrap = document.getElementById('offDatesList');
        wrap.innerHTML = offDatesState
            .map((d) => `
                <span class="badge badge-orange py-1.5! px-3! text-sm!">
                    ${escapeHtml(fmtDate(d))}
                    <button type="button" class="ml-1 font-bold" data-remove-off-date="${escapeHtml(d)}" aria-label="Remove ${escapeHtml(d)}">✕</button>
                </span>`)
            .join('');
        document.getElementById('offDatesEmpty').classList.toggle('hidden', offDatesState.length > 0);
    }

    async function openRulesSheet(worker) {
        document.getElementById('rulesWorkerId').value = worker.id;
        document.getElementById('rulesSheetTitle').textContent = `Rules — ${worker.workerName}`;
        document.getElementById('rulesDateInput').value = '';

        // Prefill from local state, then refresh from the server.
        let offDays = worker.offDays || [];
        offDatesState = [...(worker.offDates || [])].sort();
        applyDayPills(offDays);
        renderOffDates();
        openSheet('rulesSheet');

        try {
            const res = await api(`{{ route('sm.workers.rules') }}?scheduleId=${SCHEDULE_ID}&id=${worker.id}`);
            offDays = (res.data.offDays || []).map(Number);
            offDatesState = (res.data.offDates || [])
                .map((r) => String(r.offDate).substring(0, 10))
                .sort();
            applyDayPills(offDays);
            renderOffDates();
        } catch (err) {
            toast(err.message, 'error');
        }
    }

    function applyDayPills(offDays) {
        const set = (offDays || []).map(Number);
        document.querySelectorAll('#rulesDayGroup .chip').forEach((c) => {
            c.classList.toggle('is-selected', set.includes(Number(c.getAttribute('data-day'))));
        });
    }

    document.getElementById('rulesAddDateBtn').addEventListener('click', () => {
        const input = document.getElementById('rulesDateInput');
        const v = input.value;
        if (!v) return;
        if (!offDatesState.includes(v)) {
            offDatesState.push(v);
            offDatesState.sort();
            renderOffDates();
        }
        input.value = '';
    });

    document.getElementById('offDatesList').addEventListener('click', (e) => {
        const btn = e.target.closest('[data-remove-off-date]');
        if (!btn) return;
        offDatesState = offDatesState.filter((d) => d !== btn.getAttribute('data-remove-off-date'));
        renderOffDates();
    });

    document.getElementById('saveRulesBtn').addEventListener('click', async (e) => {
        const btn = e.currentTarget;
        const id = document.getElementById('rulesWorkerId').value;
        const offDays = chipValues(document.getElementById('rulesDayGroup')).map(Number);

        btn.disabled = true;
        try {
            const res = await api(`{{ route('sm.workers.rules.save') }}?scheduleId=${SCHEDULE_ID}&id=${id}`, {
                method: 'POST',
                body: { offDays, offDates: offDatesState },
            });
            toast(res.message);
            const w = WORKERS.find((x) => String(x.id) === String(id));
            if (w) {
                w.offDays = offDays;
                w.offDates = [...offDatesState];
                renderList();
            }
            closeSheet('rulesSheet');
        } catch (err) {
            toast(err.message, 'error');
        } finally {
            btn.disabled = false;
        }
    });

    /* ---------------- List actions ---------------- */

    document.addEventListener('click', async (e) => {
        if (e.target.closest('[data-add-worker]')) {
            openWorkerSheet();
            return;
        }

        const editBtn = e.target.closest('[data-edit-worker]');
        if (editBtn) {
            const w = WORKERS.find((x) => String(x.id) === editBtn.getAttribute('data-edit-worker'));
            if (w) openWorkerSheet(w);
            return;
        }

        const rulesBtn = e.target.closest('[data-rules-worker]');
        if (rulesBtn) {
            const w = WORKERS.find((x) => String(x.id) === rulesBtn.getAttribute('data-rules-worker'));
            if (w) openRulesSheet(w);
            return;
        }

        const delBtn = e.target.closest('[data-delete-worker]');
        if (delBtn) {
            const id = delBtn.getAttribute('data-delete-worker');
            const w = WORKERS.find((x) => String(x.id) === id);
            const ok = await confirmAction({
                title: 'Delete worker?',
                message: `"${w?.workerName || 'This worker'}" will be removed from the schedule.`,
                detail: 'Existing assignments tied to them are preserved.',
                confirmText: 'Delete',
            });
            if (!ok) return;
            try {
                const res = await api(`{{ route('sm.workers.destroy') }}?scheduleId=${SCHEDULE_ID}&id=${id}`, { method: 'DELETE' });
                toast(res.message);
                WORKERS = WORKERS.filter((x) => String(x.id) !== id);
                renderList();
            } catch (err) {
                toast(err.message, 'error');
            }
        }
    });

    renderList();
};
    // First load: wait for app.js (deferred) to define the globals.
    // SPA injection: document is already complete, so run now.
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', __init, { once: true });
    else __init();
})();
</script>
@endpush
