@extends('layouts.app')

@section('title', 'Lots — ' . $schedule->title)
@section('page-title', 'Lots')
@section('page-subtitle', $schedule->title)
@section('back', route('sm.hub', ['id' => $schedule->id]))

@section('content')
    @include('sm.partials.module-header', ['schedule' => $schedule, 'module' => 'lots'])

    <div class="max-w-3xl">
        <div class="hidden md:flex justify-end mb-4">
            <button type="button" class="btn btn-primary" data-add-lot>
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14m-7-7h14"/></svg>
                Add Lot
            </button>
        </div>

        {{-- JS fills this; it must be closed here or renderList() would wipe
             whatever follows it (empty state, groupings). --}}
        <div id="lotsList" class="space-y-3" data-animate-list></div>

        <div id="lotsEmpty" class="card hidden">
            <div class="card-body text-center py-12">
                <div class="mx-auto w-14 h-14 rounded-2xl bg-brand-50 flex items-center justify-center mb-3">
                    <svg class="w-7 h-7 text-brand-600" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 20l-5-2V6l5 2m0 12l6-2m-6 2V8m6 10l5 2V8l-5-2m0 12V6M9 8l6-2"/></svg>
                </div>
                <h2 class="font-bold text-gray-900 mb-1">No lots yet</h2>
                <p class="text-sm text-gray-500 mb-4">Lots are the field areas this schedule covers. Activities and groupings attach to them.</p>
                <button type="button" class="btn btn-primary" data-add-lot>Add your first lot</button>
            </div>
        </div>

        {{-- Default Groupings --}}
        <div class="card mt-6">
            <div class="card-body space-y-4">
                <div>
                    <h2 class="font-bold text-gray-900">Default Groupings</h2>
                    <p class="text-sm text-gray-500">Group lots that start together and give the group its start date. Irrigation day ranges are counted from these dates.</p>
                </div>

                <div id="groupsNoLots" class="rounded-xl bg-blue-50 border border-blue-100 text-blue-800 text-sm px-4 py-3 {{ $schedule->lots->isEmpty() ? '' : 'hidden' }}">
                    Add at least one lot first — groupings are collections of lots.
                </div>

                <div id="groupsList" class="space-y-3"></div>
                <p id="groupsEmpty" class="text-sm text-gray-400 hidden">No default groupings yet. Add your first group below.</p>

                <div class="flex flex-col sm:flex-row sm:justify-between gap-2">
                    <button type="button" id="addGroupBtn" class="btn btn-white">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14m-7-7h14"/></svg>
                        Add Group
                    </button>
                    <button type="button" id="saveGroupingsBtn" class="btn btn-primary">Save Groupings</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Mobile floating action button --}}
    <button type="button" data-add-lot
        class="md:hidden fixed bottom-24 right-4 z-30 w-14 h-14 rounded-full btn-primary shadow-lg flex items-center justify-center"
        aria-label="Add lot">
        <svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14m-7-7h14"/></svg>
    </button>
@endsection

@push('sheets')
<div class="sheet hidden" id="lotSheet" style="--sheet-width:36rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title" id="lotSheetTitle">Add Lot</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body space-y-4">
        <input type="hidden" id="lotId" value="">

        <div>
            <label for="lotName" class="form-label">Lot Name <span class="text-red-500">*</span></label>
            <input type="text" id="lotName" maxlength="255" class="form-input" placeholder="e.g. Lot A — riverside">
        </div>

        <div class="grid grid-cols-2 gap-3">
            <div>
                <label for="lotSize" class="form-label">Size</label>
                <input type="number" id="lotSize" min="0" step="0.0001" class="form-input" placeholder="0">
            </div>
            <div>
                <label for="lotSizeUnit" class="form-label">Unit</label>
                <select id="lotSizeUnit" class="form-select">
                    <option value="hectare">Hectare</option>
                    <option value="sqm">Square meter</option>
                    <option value="acre">Acre</option>
                </select>
            </div>
        </div>

        <div>
            <label for="lotVariety" class="form-label">Variety <span class="text-gray-400 font-normal">(optional)</span></label>
            <input type="text" id="lotVariety" maxlength="255" class="form-input" placeholder="e.g. IR64">
        </div>

        <div>
            <label for="lotDayZeroDate" class="form-label">Day 0 Date <span class="text-gray-400 font-normal">(optional)</span></label>
            <div class="flex gap-2">
                <input type="date" id="lotDayZeroDate" class="form-input">
                <button type="button" id="lotDayZeroDateClear" class="btn btn-ghost shrink-0" title="Clear date">Clear</button>
            </div>
            <p class="form-hint">Anchor for {{ $schedule->dayType }} labels — day numbers count from this date.</p>
        </div>

        <div>
            <label for="lotNotes" class="form-label">Notes</label>
            <textarea id="lotNotes" rows="3" maxlength="2000" class="form-textarea" placeholder="Anything worth remembering about this lot…"></textarea>
        </div>
    </div>
    <div class="sheet-footer">
        <button type="button" class="btn btn-ghost" data-sheet-close>Cancel</button>
        <button type="button" id="saveLotBtn" class="btn btn-primary">Save Lot</button>
    </div>
</div>
@endpush

@push('scripts')
@php
    $jsLots = $schedule->lots->map(fn ($l) => [
        'id' => $l->id,
        'lotName' => $l->lotName,
        'lotSize' => $l->lotSize,
        'lotSizeUnit' => $l->lotSizeUnit,
        'variety' => $l->variety,
        'dayZeroDate' => $l->dayZeroDate ? $l->dayZeroDate->format('Y-m-d') : null,
        'notes' => $l->notes,
    ])->values();
    $jsGroups = $schedule->defaultGroupings->map(fn ($g) => [
        'name' => $g->groupName,
        'startDate' => $g->startDate ? $g->startDate->format('Y-m-d') : null,
        'lotIds' => $g->lots->pluck('id'),
    ])->values();
@endphp
<script>
document.addEventListener('DOMContentLoaded', () => {
    const SCHEDULE_ID = {{ $schedule->id }};
    const DAY_TYPE = @json($schedule->dayType);
    let LOTS = @json($jsLots);
    let GROUPS = @json($jsGroups);

    const list = document.getElementById('lotsList');
    const empty = document.getElementById('lotsEmpty');

    const UNIT_LABELS = { hectare: 'ha', sqm: 'sqm', acre: 'ac' };

    const fmtDate = (iso) => {
        if (!iso) return '';
        return new Date(`${iso}T00:00:00`).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
    };
    const fmtSize = (v) => {
        const n = parseFloat(v);
        return Number.isFinite(n) ? n.toLocaleString('en-PH', { maximumFractionDigits: 4 }) : '0';
    };

    function lotCardHtml(lot) {
        const dayZero = lot.dayZeroDate ? `
            <span class="badge badge-blue" title="Anchor for ${escapeHtml(DAY_TYPE)} labels">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="8"/><circle cx="12" cy="12" r="3.5"/><path stroke-linecap="round" d="M12 2v2m0 16v2M2 12h2m16 0h2"/></svg>
                Day 0: ${escapeHtml(fmtDate(lot.dayZeroDate))}
            </span>` : '';
        const variety = lot.variety ? `
            <span class="badge badge-green">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 21c0-7 4-13 14-16-1 10-6 15-13 15m-1 1c2-5 5-8 9-10"/></svg>
                ${escapeHtml(lot.variety)}
            </span>` : '';

        return `
            <div class="card-body py-4!">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <div class="flex items-center gap-2 flex-wrap mb-1">
                            <h3 class="font-bold text-gray-900">${escapeHtml(lot.lotName)}</h3>
                            ${dayZero}
                        </div>
                        <div class="flex items-center gap-2 flex-wrap text-sm text-gray-600">
                            <span>${fmtSize(lot.lotSize)} ${escapeHtml(UNIT_LABELS[lot.lotSizeUnit] || lot.lotSizeUnit || '')}</span>
                            ${variety}
                        </div>
                        ${lot.notes ? `<p class="text-xs text-gray-500 mt-1.5">${escapeHtml(lot.notes)}</p>` : ''}
                    </div>
                    <div class="flex items-center gap-1.5 shrink-0">
                        <button type="button" class="btn btn-white btn-sm" data-edit-lot="${lot.id}">Edit</button>
                        <button type="button" class="btn btn-ghost btn-sm px-2.5! text-red-500 hover:bg-red-50!" data-delete-lot="${lot.id}" aria-label="Delete lot">
                            <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.9 12.1a2 2 0 01-2 1.9H7.9a2 2 0 01-2-1.9L5 7m3 0V5a2 2 0 012-2h4a2 2 0 012 2v2m-11 0h16m-10 4v6m4-6v6"/></svg>
                        </button>
                    </div>
                </div>
            </div>`;
    }

    function renderList() {
        list.innerHTML = '';
        LOTS.forEach((lot) => {
            const card = document.createElement('div');
            card.className = 'card';
            card.dataset.lotCard = lot.id;
            card.innerHTML = lotCardHtml(lot);
            list.appendChild(card);
        });
        empty.classList.toggle('hidden', LOTS.length > 0);
        // Keep the grouping lot-chips in step with the current lots.
        refreshGroupChips();
        document.getElementById('groupsNoLots')?.classList.toggle('hidden', LOTS.length > 0);
    }

    /* ---------------- Sheet open / fill ---------------- */

    function openLotSheet(lot = null) {
        document.getElementById('lotSheetTitle').textContent = lot ? 'Edit Lot' : 'Add Lot';
        document.getElementById('lotId').value = lot ? lot.id : '';
        document.getElementById('lotName').value = lot ? (lot.lotName || '') : '';
        document.getElementById('lotSize').value = lot ? parseFloat(lot.lotSize) || 0 : '';
        document.getElementById('lotSizeUnit').value = lot ? (lot.lotSizeUnit || 'hectare') : 'hectare';
        document.getElementById('lotVariety').value = lot ? (lot.variety || '') : '';
        document.getElementById('lotDayZeroDate').value = lot ? (lot.dayZeroDate || '') : '';
        document.getElementById('lotNotes').value = lot ? (lot.notes || '') : '';
        openSheet('lotSheet');
    }

    document.getElementById('lotDayZeroDateClear').addEventListener('click', () => {
        document.getElementById('lotDayZeroDate').value = '';
    });

    document.addEventListener('click', async (e) => {
        if (e.target.closest('[data-add-lot]')) {
            openLotSheet();
            return;
        }

        const editBtn = e.target.closest('[data-edit-lot]');
        if (editBtn) {
            const lot = LOTS.find((l) => String(l.id) === editBtn.getAttribute('data-edit-lot'));
            if (lot) openLotSheet(lot);
            return;
        }

        const delBtn = e.target.closest('[data-delete-lot]');
        if (delBtn) {
            const id = delBtn.getAttribute('data-delete-lot');
            const lot = LOTS.find((l) => String(l.id) === id);
            const ok = await confirmAction({
                title: 'Delete lot?',
                message: `"${lot?.lotName || 'This lot'}" will be removed from the schedule.`,
                detail: 'Existing data tied to it is preserved.',
                confirmText: 'Delete',
            });
            if (!ok) return;
            try {
                const res = await api(`{{ route('sm.lots.destroy') }}?scheduleId=${SCHEDULE_ID}&id=${id}`, { method: 'DELETE' });
                toast(res.message);
                LOTS = LOTS.filter((l) => String(l.id) !== id);
                renderList();
            } catch (err) {
                toast(err.message, 'error');
            }
        }
    });

    /* ---------------- Save ---------------- */

    document.getElementById('saveLotBtn').addEventListener('click', async (e) => {
        const btn = e.currentTarget;
        const id = document.getElementById('lotId').value;
        const body = {
            lotName: document.getElementById('lotName').value.trim(),
            lotSize: document.getElementById('lotSize').value || 0,
            lotSizeUnit: document.getElementById('lotSizeUnit').value,
            variety: document.getElementById('lotVariety').value.trim() || null,
            dayZeroDate: document.getElementById('lotDayZeroDate').value || null,
            notes: document.getElementById('lotNotes').value || null,
        };

        if (!body.lotName) {
            toast('Lot name is required.', 'error');
            document.getElementById('lotName').focus();
            return;
        }

        const url = id
            ? `{{ route('sm.lots.update') }}?scheduleId=${SCHEDULE_ID}&id=${id}`
            : `{{ route('sm.lots.store') }}?scheduleId=${SCHEDULE_ID}`;

        btn.disabled = true;
        try {
            const res = await api(url, { method: id ? 'PUT' : 'POST', body });
            toast(res.message);
            const saved = {
                id: res.data.id,
                lotName: res.data.lotName,
                lotSize: res.data.lotSize,
                lotSizeUnit: res.data.lotSizeUnit,
                variety: res.data.variety,
                dayZeroDate: res.data.dayZeroDate || null,
                notes: res.data.notes,
            };
            const idx = LOTS.findIndex((l) => String(l.id) === String(saved.id));
            if (idx >= 0) LOTS[idx] = saved; else LOTS.push(saved);
            renderList();
            closeSheet('lotSheet');
        } catch (err) {
            toast(err.message, 'error');
        } finally {
            btn.disabled = false;
        }
    });

    /* ---------------- Default Groupings ---------------- */

    const groupsList = document.getElementById('groupsList');
    const groupsEmptyHint = document.getElementById('groupsEmpty');

    function lotChipsHtml(selectedIds) {
        const sel = (selectedIds || []).map(Number);
        if (!LOTS.length) return '<span class="text-sm text-gray-400">No lots available.</span>';
        return LOTS.map((l) => `
            <button type="button" class="chip ${sel.includes(Number(l.id)) ? 'is-selected' : ''}"
                data-value="${l.id}">${escapeHtml(l.lotName)}</button>`).join('');
    }

    /** Re-render each group's lot chips from the live LOTS list, keeping picks. */
    function refreshGroupChips() {
        if (!groupsList) return;
        groupsList.querySelectorAll('.group-card').forEach((card) => {
            const wrap = card.querySelector('.group-lots');
            const selected = chipValues(wrap).map(Number);
            wrap.innerHTML = lotChipsHtml(selected.filter((id) => LOTS.some((l) => Number(l.id) === id)));
        });
    }

    function renderGroupCard(g) {
        const card = document.createElement('div');
        card.className = 'group-card border border-gray-200 rounded-xl p-4 space-y-3';
        card.innerHTML = `
            <div class="flex items-center gap-2">
                <input type="text" maxlength="255" class="form-input group-name" placeholder="Group name (e.g. Group A)"
                    value="${escapeHtml(g.name || '')}">
                <button type="button" class="btn btn-ghost px-3! text-red-500 hover:bg-red-50! group-remove shrink-0" aria-label="Remove group">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.9 12.1a2 2 0 01-2 1.9H7.9a2 2 0 01-2-1.9L5 7m3 0V5a2 2 0 012-2h4a2 2 0 012 2v2m-11 0h16m-10 4v6m4-6v6"/></svg>
                </button>
            </div>
            <div>
                <label class="form-label">Start date</label>
                <input type="date" class="form-input group-start-date" value="${escapeHtml(g.startDate || '')}">
                <p class="form-hint">Day 0 for this group — irrigation day ranges count from here.</p>
            </div>
            <div>
                <span class="form-label">Lots in this group</span>
                <div data-chip-group class="group-lots flex flex-wrap gap-2">${lotChipsHtml(g.lotIds)}</div>
            </div>`;

        card.querySelector('.group-remove').addEventListener('click', () => {
            card.remove();
            refreshGroupsEmptyHint();
        });

        groupsList.appendChild(card);
        refreshGroupsEmptyHint();
    }

    function refreshGroupsEmptyHint() {
        groupsEmptyHint.classList.toggle('hidden', groupsList.children.length > 0);
    }

    document.getElementById('addGroupBtn')?.addEventListener('click', () => {
        if (!LOTS.length) {
            toast('Add at least one lot first.', 'error');
            return;
        }
        renderGroupCard({ name: '', startDate: null, lotIds: [] });
        groupsList.lastElementChild?.querySelector('.group-name')?.focus();
    });

    document.getElementById('saveGroupingsBtn')?.addEventListener('click', async (e) => {
        const btn = e.currentTarget;
        const groupings = [];
        for (const card of groupsList.querySelectorAll('.group-card')) {
            const name = card.querySelector('.group-name').value.trim();
            if (!name) {
                toast('Every group needs a name.', 'error');
                card.querySelector('.group-name').focus();
                return;
            }
            groupings.push({
                name,
                staggerDays: 0,
                startDate: card.querySelector('.group-start-date').value || null,
                lotIds: chipValues(card.querySelector('.group-lots')).map(Number),
            });
        }

        btn.disabled = true;
        try {
            const res = await api(`{{ route('sm.default-groupings.save') }}?scheduleId=${SCHEDULE_ID}`, {
                method: 'POST',
                body: { groupings },
            });
            toast(res.message);
            GROUPS = (res.data || []).map((g) => ({
                name: g.groupName, startDate: g.startDate, lotIds: g.lotIds,
            }));
            groupsList.innerHTML = '';
            GROUPS.forEach(renderGroupCard);
            refreshGroupsEmptyHint();
        } catch (err) {
            toast(err.message, 'error');
        } finally {
            btn.disabled = false;
        }
    });

    GROUPS.forEach(renderGroupCard);
    refreshGroupsEmptyHint();

    renderList();
});
</script>
@endpush
