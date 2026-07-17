@extends('layouts.app')

@section('title', 'Settings — ' . $schedule->title)
@section('page-title', 'Settings')
@section('page-subtitle', $schedule->title)
@section('back', route('sm.hub', ['id' => $schedule->id]))

@section('content')
    @include('sm.partials.module-header', ['schedule' => $schedule, 'module' => 'settings'])

    <div class="max-w-3xl space-y-4">

        {{-- Basic Info --}}
        <div class="card">
            <div class="card-body space-y-4">
                <div>
                    <h2 class="font-bold text-gray-900">Basic Info</h2>
                    <p class="text-sm text-gray-500">Title, description and how day numbers are labeled.</p>
                </div>

                <div>
                    <label for="settingsTitle" class="form-label">Title <span class="text-red-500">*</span></label>
                    <input type="text" id="settingsTitle" maxlength="255" class="form-input" value="{{ $schedule->title }}">
                </div>

                <div>
                    <label for="settingsDescription" class="form-label">Description</label>
                    <textarea id="settingsDescription" rows="3" maxlength="5000" class="form-textarea">{{ $schedule->description }}</textarea>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="settingsDayType" class="form-label">Day Type</label>
                        <select id="settingsDayType" class="form-select">
                            <option value="DAP" @selected($schedule->dayType === 'DAP')>DAP — Days After Planting</option>
                            <option value="DAS" @selected($schedule->dayType === 'DAS')>DAS — Days After Seeding</option>
                            <option value="DAT" @selected($schedule->dayType === 'DAT')>DAT — Days After Transplanting</option>
                        </select>
                        <p class="form-hint">Label used for day numbers across the schedule.</p>
                    </div>
                    <div>
                        <label for="settingsDefaultStaggerDays" class="form-label">Default Stagger Days</label>
                        <input type="number" id="settingsDefaultStaggerDays" min="0" class="form-input" value="{{ (int) $schedule->defaultStaggerDays }}">
                        <p class="form-hint">Default gap in days between lot groups.</p>
                    </div>
                </div>

                <div class="flex justify-end">
                    <button type="button" id="saveBasicBtn" class="btn btn-primary w-full sm:w-auto">Save Basic Info</button>
                </div>
            </div>
        </div>

        {{-- Default Groupings --}}
        <div class="card">
            <div class="card-body space-y-4">
                <div>
                    <h2 class="font-bold text-gray-900">Default Groupings</h2>
                    <p class="text-sm text-gray-500">Group lots that start together. Each group starts either a number of stagger days after the season start, or on a specific date. Irrigation windows anchor on these groups.</p>
                </div>

                @if ($schedule->lots->isEmpty())
                    <div class="rounded-xl bg-blue-50 border border-blue-100 text-blue-800 text-sm px-4 py-3">
                        Add at least one lot first — groupings are collections of lots.
                        <a href="{{ route('sm.lots', ['id' => $schedule->id]) }}" class="font-semibold underline">Go to Lots</a>
                    </div>
                @endif

                <div id="groupsList" class="space-y-3"></div>
                <p id="groupsEmpty" class="text-sm text-gray-400 hidden">No default groupings yet. Add your first group below.</p>

                <div class="flex flex-col sm:flex-row sm:justify-between gap-2">
                    <button type="button" id="addGroupBtn" class="btn btn-white" @disabled($schedule->lots->isEmpty())>
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14m-7-7h14"/></svg>
                        Add Group
                    </button>
                    <button type="button" id="saveGroupingsBtn" class="btn btn-primary" @disabled($schedule->lots->isEmpty())>Save Groupings</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
@php
    $jsLots = $schedule->lots->map(fn ($l) => ['id' => $l->id, 'lotName' => $l->lotName])->values();
    $jsGroups = $schedule->defaultGroupings->map(fn ($g) => [
        'name' => $g->groupName,
        'staggerDays' => (int) $g->staggerDays,
        'startDate' => $g->startDate ? $g->startDate->format('Y-m-d') : null,
        'lotIds' => $g->lots->pluck('id'),
    ])->values();
@endphp
<script>
document.addEventListener('DOMContentLoaded', () => {
    const SCHEDULE_ID = {{ $schedule->id }};
    const LOTS = @json($jsLots);
    let GROUPS = @json($jsGroups);

    const list = document.getElementById('groupsList');
    const emptyHint = document.getElementById('groupsEmpty');

    /* ---------------- Basic Info ---------------- */

    document.getElementById('saveBasicBtn').addEventListener('click', async (e) => {
        const btn = e.currentTarget;
        btn.disabled = true;
        try {
            const res = await api(`{{ route('sm.update') }}?id=${SCHEDULE_ID}`, {
                method: 'PUT',
                body: {
                    title: document.getElementById('settingsTitle').value.trim(),
                    description: document.getElementById('settingsDescription').value,
                    dayType: document.getElementById('settingsDayType').value,
                    defaultStaggerDays: document.getElementById('settingsDefaultStaggerDays').value || 0,
                },
            });
            toast(res.message);
            const t = res.data?.title;
            if (t) {
                // Live-update the app-bar subtitle (schedule title) + tab title.
                const sub = document.querySelector('header .min-w-0 p.text-xs');
                if (sub) sub.textContent = t;
                document.title = `Settings — ${t} | AniSystem`;
            }
        } catch (err) {
            toast(err.message, 'error');
        } finally {
            btn.disabled = false;
        }
    });

    /* ---------------- Default Groupings ---------------- */

    function lotChipsHtml(selectedIds) {
        const sel = (selectedIds || []).map(Number);
        if (!LOTS.length) return '<span class="text-sm text-gray-400">No lots available.</span>';
        return LOTS.map((l) => `
            <button type="button" class="chip ${sel.includes(Number(l.id)) ? 'is-selected' : ''}"
                data-value="${l.id}">${escapeHtml(l.lotName)}</button>`).join('');
    }

    function renderGroupCard(g) {
        const mode = g.startDate ? 'date' : 'stagger';
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
                <span class="form-label">Start mode</span>
                <div data-chip-group data-single="true" class="group-mode flex gap-2">
                    <button type="button" class="chip ${mode === 'stagger' ? 'is-selected' : ''}" data-value="stagger">Staggered</button>
                    <button type="button" class="chip ${mode === 'date' ? 'is-selected' : ''}" data-value="date">Specific date</button>
                </div>
            </div>
            <div class="stagger-wrap ${mode === 'date' ? 'hidden' : ''}">
                <label class="form-label">Stagger days</label>
                <div class="flex items-center gap-2">
                    <input type="number" min="0" class="form-input group-stagger w-28" value="${Number(g.staggerDays || 0)}">
                    <span class="text-sm text-gray-500">days after season start</span>
                </div>
            </div>
            <div class="date-wrap ${mode === 'date' ? '' : 'hidden'}">
                <label class="form-label">Start date</label>
                <input type="date" class="form-input group-start-date" value="${escapeHtml(g.startDate || '')}">
            </div>
            <div>
                <span class="form-label">Lots in this group</span>
                <div data-chip-group class="group-lots flex flex-wrap gap-2">${lotChipsHtml(g.lotIds)}</div>
            </div>`;

        card.querySelector('.group-remove').addEventListener('click', () => {
            card.remove();
            refreshEmptyHint();
        });

        list.appendChild(card);
        refreshEmptyHint();
    }

    function refreshEmptyHint() {
        emptyHint.classList.toggle('hidden', list.children.length > 0);
    }

    // Mode toggle: show/hide stagger vs date inputs.
    document.addEventListener('chips:change', (e) => {
        const modeGroup = e.target.closest?.('.group-mode');
        if (!modeGroup) return;
        const card = modeGroup.closest('.group-card');
        const mode = chipValues(modeGroup)[0] || 'stagger';
        card.querySelector('.stagger-wrap').classList.toggle('hidden', mode !== 'stagger');
        card.querySelector('.date-wrap').classList.toggle('hidden', mode !== 'date');
    });

    document.getElementById('addGroupBtn').addEventListener('click', () => {
        renderGroupCard({ name: '', staggerDays: 0, startDate: null, lotIds: [] });
        list.lastElementChild?.querySelector('.group-name')?.focus();
    });

    document.getElementById('saveGroupingsBtn').addEventListener('click', async (e) => {
        const btn = e.currentTarget;
        const groupings = [];
        for (const card of list.querySelectorAll('.group-card')) {
            const name = card.querySelector('.group-name').value.trim();
            if (!name) {
                toast('Every group needs a name.', 'error');
                card.querySelector('.group-name').focus();
                return;
            }
            const mode = chipValues(card.querySelector('.group-mode'))[0] || 'stagger';
            const startDate = mode === 'date' ? (card.querySelector('.group-start-date').value || null) : null;
            groupings.push({
                name,
                staggerDays: mode === 'stagger' ? Number(card.querySelector('.group-stagger').value || 0) : 0,
                startDate,
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
            // Re-hydrate from the server's canonical response.
            GROUPS = (res.data || []).map((g) => ({
                name: g.groupName, staggerDays: g.staggerDays, startDate: g.startDate, lotIds: g.lotIds,
            }));
            list.innerHTML = '';
            GROUPS.forEach(renderGroupCard);
        } catch (err) {
            toast(err.message, 'error');
        } finally {
            btn.disabled = false;
        }
    });

    // Initial render
    GROUPS.forEach(renderGroupCard);
    refreshEmptyHint();
});
</script>
@endpush
