@extends(request()->boolean('partial') ? 'layouts.partial' : 'layouts.app')

@section('title', 'Workers — ' . $schedule->title)
@section('page-title', 'Workers')
@section('page-subtitle', $schedule->title)
@section('help-key', 'workers')
@section('back', route('sm.hub', ['id' => $schedule->id]))

@section('content')
    @php $canWorkerLogins = auth()->user()->canWorkerAccounts(); @endphp
    @include('sm.partials.module-header', ['schedule' => $schedule, 'module' => 'workers'])

    <div>
        {{-- Worker logins (Boss/Lifetime only) --}}
        @if (auth()->user()->canWorkerAccounts())
            <div class="card p-4 mb-4">
                <div class="flex items-center gap-2">
                    <div class="w-10 h-10 rounded-xl bg-brand-50 flex items-center justify-center shrink-0 text-lg">🔑</div>
                    <div class="min-w-0 grow">
                        <p class="font-bold text-gray-900">Worker logins</p>
                        <p class="text-xs text-gray-500">Give a worker their own login with view or edit access. They set their password from an emailed link.</p>
                    </div>
                    <button type="button" id="grantAccessBtn" class="btn btn-white btn-sm shrink-0">Give access</button>
                </div>
                <div id="grantForm" class="hidden mt-3 pt-3 border-t border-gray-100 space-y-2">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                        <div>
                            <label class="form-label" for="grantEmail">Worker email</label>
                            <input type="email" id="grantEmail" class="form-input" placeholder="worker@email.com">
                        </div>
                        <div>
                            <label class="form-label" for="grantAccess">Schedule access</label>
                            <select id="grantAccess" class="form-select">
                                <option value="view">View only</option>
                                <option value="edit">Can edit</option>
                                <option value="none">No schedule access</option>
                            </select>
                        </div>
                    </div>
                    <label class="flex items-center gap-2 text-sm text-gray-700">
                        <input type="checkbox" id="grantCommunity" checked class="rounded"> Allow community access (own profile &amp; posting)
                    </label>
                    @include('sm.partials.worker-rights', ['p' => 'grant'])
                    <div class="flex justify-end gap-2">
                        <button type="button" id="grantCancel" class="btn btn-ghost btn-sm">Cancel</button>
                        <button type="button" id="grantSubmit" class="btn btn-primary btn-sm">Send invite</button>
                    </div>
                </div>
            </div>
        @else
            <div class="card p-4 mb-4 border-amber-200">
                <p class="text-sm text-gray-700"><strong>🔒 Worker logins</strong> are a <strong>Boss/Lifetime</strong> feature. <a href="{{ route('account.subscription') }}" class="text-brand-600 font-semibold">Upgrade</a> to give workers their own login and email notifications.</p>
            </div>
        @endif

        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
            <p class="text-sm text-gray-500">
                <span id="workerCount" class="font-bold text-gray-900">0</span> <span id="workerCountLabel">workers</span> on this schedule
            </p>
            <button type="button" class="btn btn-primary w-full sm:w-auto shrink-0" data-add-worker>
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14m-7-7h14"/></svg>
                Add Worker
            </button>
        </div>

        {{-- Full-width responsive grid — one card per worker. Kept separate from
             the empty state below so renderList()'s innerHTML reset can't wipe it. --}}
        <div id="workersList" class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3" data-animate-list></div>

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

        <div class="grid grid-cols-2 gap-3">
            <div>
                <label for="workerEmail" class="form-label">Email <span class="text-gray-400 font-normal">(optional)</span></label>
                <input type="email" id="workerEmail" maxlength="255" class="form-input" placeholder="e.g. juan@email.com">
            </div>
            <div>
                <label for="workerPhone" class="form-label">Phone <span class="text-gray-400 font-normal">(optional)</span></label>
                <input type="tel" id="workerPhone" maxlength="32" class="form-input" placeholder="e.g. 0917 123 4567">
            </div>
        </div>
        <p class="form-hint -mt-2">Email is used to send this worker today's or tomorrow's plan from Quick Share.</p>

        <div>
            <label for="workerCost" class="form-label">Cost / Half Day</label>
            <div class="relative">
                <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-500 font-semibold pointer-events-none">₱</span>
                <input type="number" id="workerCost" min="0" step="0.01" class="form-input pl-9!" placeholder="0.00">
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

        @if ($canWorkerLogins)
        {{-- Login access: give an already-added worker their own login by
             sending a registration link OR setting a password for them. Shows
             only when editing an existing worker. --}}
        <div id="workerLoginSection" class="hidden mt-1 pt-4 border-t border-gray-100">
            <div class="rounded-2xl border border-gray-200 bg-gray-50/60 p-4 space-y-4">
                <div class="flex items-center gap-2.5">
                    <span class="w-9 h-9 rounded-xl bg-brand-50 flex items-center justify-center shrink-0 text-lg">🔑</span>
                    <div class="min-w-0 grow">
                        <p class="font-bold text-gray-900 text-sm">Login access</p>
                        <p class="text-xs text-gray-500" id="wlStatus">No login yet.</p>
                    </div>
                </div>

                <div>
                    <label class="form-label" for="wlAccess">Schedule access</label>
                    <select id="wlAccess" class="form-select">
                        <option value="view">View only</option>
                        <option value="edit">Can edit</option>
                        <option value="none">No schedule access</option>
                    </select>
                </div>

                {{-- Writing down what happened is not the same act as changing
                     what is supposed to happen, and the same is true of every
                     module below: a worker who may only look at the plan can
                     still be the right person to record the day, or to keep
                     the maps, and not the right person to spend the farm's AI
                     credits. Each answer stands on its own. --}}
                @include('sm.partials.worker-rights', ['p' => 'wl'])

                <label for="wlCommunity" class="flex items-start gap-3 rounded-xl border border-gray-200 bg-white p-3 cursor-pointer select-none">
                    <input type="checkbox" id="wlCommunity" checked class="w-5 h-5 rounded border-gray-300 mt-0.5 shrink-0">
                    <span class="min-w-0">
                        <span class="block text-sm font-semibold text-gray-900">Community access</span>
                        <span class="block text-xs text-gray-500">Let them use their own profile and post in the community.</span>
                    </span>
                </label>

                <div class="flex flex-wrap items-center gap-2">
                    <button type="button" id="wlSendLink" class="btn btn-white btn-sm">✉️ Send registration link</button>
                    <button type="button" id="wlSetPwToggle" class="btn btn-white btn-sm">🔒 Set a password</button>
                    <button type="button" id="wlRevoke" class="btn btn-ghost btn-sm text-red-500 hover:bg-red-50! hidden ml-auto">Revoke access</button>
                </div>

                <div id="wlPwRow" class="hidden pt-1">
                    <label class="form-label" for="wlPassword">Password for this worker</label>
                    <div class="flex flex-col sm:flex-row gap-2">
                        <input type="text" id="wlPassword" class="form-input grow" placeholder="At least 8 characters" autocomplete="new-password">
                        <button type="button" id="wlCreateLogin" class="btn btn-primary btn-sm shrink-0 whitespace-nowrap w-full sm:w-auto">Create login</button>
                    </div>
                    <p class="form-hint">Share the email above + this password so they can sign in.</p>
                </div>

                <p class="form-hint mt-0!">Uses the worker's <strong>email</strong> above — add one if it's blank.</p>
            </div>
        </div>
        @endif
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
                @include('partials.date-tag', ['id' => 'rulesDateInput', 'empty' => 'Pick a date'])
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

@push('head')
<style>
    /* The rights block: a row per module, the same shape whether the answer
       is a choice of three or a yes/no. */
    .wr-block { border:1px solid var(--color-gray-200); border-radius:1rem; background:var(--color-white); overflow:hidden; }
    .wr-head { padding:.6rem .85rem; font-size:.7rem; font-weight:800; letter-spacing:.04em;
        text-transform:uppercase; color:var(--color-gray-400); border-bottom:1px solid var(--color-gray-100); }
    .wr-row { display:flex; align-items:center; gap:.7rem; padding:.6rem .85rem;
        border-bottom:1px solid var(--color-gray-100); margin:0; }
    .wr-row:last-of-type { border-bottom:0; }
    .wr-switch { cursor:pointer; user-select:none; }
    .wr-mark { flex:none; width:2rem; height:2rem; border-radius:.6rem; display:flex;
        align-items:center; justify-content:center; font-size:1rem; background:var(--color-brand-50); }
    .wr-what { min-width:0; flex:1 1 auto; }
    .wr-what b { display:block; font-size:.82rem; font-weight:700; color:var(--color-gray-900); line-height:1.25; }
    .wr-what i { display:block; font-style:normal; font-size:.7rem; line-height:1.35; color:var(--color-gray-500); }
    .wr-pick { flex:none; width:11rem; min-height:2.25rem; padding-top:.3rem; padding-bottom:.3rem; font-size:.8rem; }
    .wr-check { flex:none; width:1.35rem; height:1.35rem; border-radius:.4rem; }
    .wr-foot { padding:.6rem .85rem; font-size:.7rem; line-height:1.4; color:var(--color-gray-400);
        border-top:1px solid var(--color-gray-100); background:var(--color-gray-50); }
    html.dark .wr-foot { background:rgb(255 255 255 / .03); }
    @media (max-width:480px) {
        .wr-row { flex-wrap:wrap; }
        .wr-pick { width:100%; }
    }
</style>
@endpush

@push('scripts')
@php
    $jsWorkers = $schedule->workers->map(fn ($w) => [
        'id' => $w->id,
        'workerName' => $w->workerName,
        'email' => $w->email,
        'phone' => $w->phone,
        'costPerHalfDay' => $w->costPerHalfDay,
        'priority' => (int) $w->priority,
        'skills' => $w->skills ?? [],
        'notes' => $w->notes,
        'offDays' => $w->offDays->pluck('dayOfWeek')->map(fn ($d) => (int) $d)->values(),
        'offDates' => $w->offDates->map(fn ($d) => $d->offDate->format('Y-m-d'))->values(),
        'login' => ($grantByWorker ?? [])[$w->id] ?? null,
    ])->values();
@endphp
<script>
/* Read and paint the module switches, by prefix.
 *
 * Both forms on this page set the same rights, and a right that is read by
 * one name and written by another is how a permission ends up not applying.
 * The keys are the grant's own column names. */
window.workerRights = (() => {
    const LEVELS = ['notesAccess', 'reportsAccess'];
    const SWITCHES = ['mapsAccess', 'drawAccess', 'aiAccess', 'cameraAccess', 'videoAccess'];
    const id = (p, key) => p + key.charAt(0).toUpperCase() + key.slice(1);
    return {
        read(p) {
            const out = {};
            LEVELS.forEach((k) => { const el = document.getElementById(id(p, k)); if (el) out[k] = el.value; });
            SWITCHES.forEach((k) => { const el = document.getElementById(id(p, k)); if (el) out[k] = el.checked ? 1 : 0; });
            return out;
        },
        /* No grant yet means a new worker, and a new worker starts where the
         * app has always put them: able to read the farm, with the owner's
         * tools closed until the owner opens them. */
        paint(p, grant) {
            LEVELS.forEach((k) => {
                const el = document.getElementById(id(p, k));
                if (el) el.value = (grant && grant[k]) || 'view';
            });
            SWITCHES.forEach((k) => {
                const el = document.getElementById(id(p, k));
                if (el) el.checked = !!(grant && grant[k]);
            });
        },
    };
})();
(() => {
const __init = () => {
    const SCHEDULE_ID = {{ $schedule->id }};
    const SKILLS = @json(\App\Models\AsScheduleWorker::SKILLS);
    const DAY_NAMES = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

    let WORKERS = @json($jsWorkers);
    const CAN_LOGINS = @json($canWorkerLogins);
    let editingWorker = null;   // the worker whose sheet is open (for login controls)

    const list = document.getElementById('workersList');
    const empty = document.getElementById('workersEmpty');

    function loginPillHtml(login) {
        if (!login) return '';
        if (login.status === 'active') return '<span class="inline-flex items-center gap-1 text-xs font-semibold text-green-700 bg-green-50 border border-green-100 rounded-full px-2 py-0.5">🔑 Login</span>';
        if (login.status === 'pending') return '<span class="inline-flex items-center gap-1 text-xs font-semibold text-amber-700 bg-amber-50 border border-amber-100 rounded-full px-2 py-0.5">🔑 Invite sent</span>';
        return '';
    }
    // PM a worker who has a login: reuse the in-schedule float if present (shell),
    // else deep-link into the community DM (same conversation + history).
    function openWorkerPm(userId, name) {
        if (typeof window.scheduleTeamPm === 'function') window.scheduleTeamPm(userId, name);
        else window.location.href = '{{ route('community.index') }}?dm=' + userId;
    }

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

    // Initials for the avatar (first letters of up to two name words).
    function workerInitials(name) {
        const parts = String(name || '').trim().split(/\s+/).filter(Boolean);
        const ini = parts.slice(0, 2).map((p) => p[0] || '').join('');
        return (ini || '?').toUpperCase();
    }

    function workerCardHtml(w) {
        const skills = (w.skills || [])
            .map((s) => `<span class="badge badge-gray">${escapeHtml(SKILLS[s] || s)}</span>`)
            .join(' ');
        const hue = ((Number(w.id) || 0) * 137) % 360;   // golden-angle → stable, distinct per worker
        const offRules = offRulesSummary(w);
        const contactLine = (icon, value) => `<p class="text-xs text-gray-500 flex items-center gap-1.5 truncate"><svg class="w-3.5 h-3.5 shrink-0 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">${icon}</svg><span class="truncate">${escapeHtml(value)}</span></p>`;

        return `
            <div class="card-body h-full flex flex-col py-4! gap-3">
                <div class="flex items-start gap-3">
                    <span class="w-11 h-11 rounded-xl flex items-center justify-center text-white font-bold text-sm shrink-0" style="background:hsl(${hue}, 55%, 45%)" aria-hidden="true">${escapeHtml(workerInitials(w.workerName))}</span>
                    <div class="min-w-0 grow">
                        <div class="flex items-center gap-2 flex-wrap">
                            <h3 class="font-bold text-gray-900 truncate">${escapeHtml(w.workerName)}</h3>
                            ${CAN_LOGINS ? loginPillHtml(w.login) : ''}
                        </div>
                        <p class="text-sm text-gray-600 mt-0.5"><span class="font-semibold text-gray-900">${fmtPeso(w.costPerHalfDay)}</span> <span class="text-gray-400">/ half day</span></p>
                    </div>
                </div>

                <div class="min-w-0 grow space-y-1.5">
                    ${w.email ? contactLine('<path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>', w.email) : ''}
                    ${w.phone ? contactLine('<path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>', w.phone) : ''}
                    ${skills ? `<div class="flex flex-wrap gap-1.5 pt-0.5">${skills}</div>` : ''}
                    <p class="text-xs ${offRules === 'No off rules' ? 'text-gray-400' : 'text-orange-700 font-medium'} off-rules-line flex items-center gap-1.5"><svg class="w-3.5 h-3.5 shrink-0 ${offRules === 'No off rules' ? 'text-gray-300' : 'text-orange-400'}" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="17" rx="2"/><path stroke-linecap="round" d="M16 2v4M8 2v4M3 10h18"/></svg><span class="truncate">${escapeHtml(offRules)}</span></p>
                    ${w.notes ? `<p class="text-xs text-gray-500 pt-0.5 line-clamp-2">${escapeHtml(w.notes)}</p>` : ''}
                </div>

                <div class="flex items-center gap-1.5 pt-3 border-t border-gray-100">
                    ${w.login && w.login.workerUserId ? `<button type="button" class="btn btn-white btn-sm px-2.5!" data-pm-worker="${w.login.workerUserId}" data-pm-name="${escapeHtml(w.workerName)}" title="Message ${escapeHtml(w.workerName)}" aria-label="Message ${escapeHtml(w.workerName)}"><svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 10h.01M12 10h.01M16 10h.01M21 12a8 8 0 01-11.6 7.1L3 20l1-5.5A8 8 0 1121 12z"/></svg></button>` : ''}
                    <button type="button" class="btn btn-white btn-sm" data-rules-worker="${w.id}">Rules</button>
                    <button type="button" class="btn btn-white btn-sm" data-edit-worker="${w.id}">Edit</button>
                    <button type="button" class="btn btn-ghost btn-sm px-2.5! text-red-500 hover:bg-red-50! ml-auto" data-delete-worker="${w.id}" aria-label="Delete worker">
                        <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.9 12.1a2 2 0 01-2 1.9H7.9a2 2 0 01-2-1.9L5 7m3 0V5a2 2 0 012-2h4a2 2 0 012 2v2m-11 0h16m-10 4v6m4-6v6"/></svg>
                    </button>
                </div>
            </div>`;
    }

    function renderList() {
        WORKERS.sort((a, b) => (a.priority - b.priority) || String(a.workerName).localeCompare(String(b.workerName)));
        list.innerHTML = '';
        WORKERS.forEach((w) => {
            const card = document.createElement('div');
            card.className = 'card h-full';   // h-full → equal-height cards across a grid row
            card.dataset.workerCard = w.id;
            card.innerHTML = workerCardHtml(w);
            list.appendChild(card);
        });
        empty.classList.toggle('hidden', WORKERS.length > 0);
        const countEl = document.getElementById('workerCount');
        if (countEl) countEl.textContent = WORKERS.length;
        const labelEl = document.getElementById('workerCountLabel');
        if (labelEl) labelEl.textContent = WORKERS.length === 1 ? 'worker' : 'workers';
    }

    /* ---------------- Worker sheet ---------------- */

    function openWorkerSheet(w = null) {
        document.getElementById('workerSheetTitle').textContent = w ? 'Edit Worker' : 'Add Worker';
        document.getElementById('workerId').value = w ? w.id : '';
        document.getElementById('workerName').value = w ? (w.workerName || '') : '';
        document.getElementById('workerEmail').value = w ? (w.email || '') : '';
        document.getElementById('workerPhone').value = w ? (w.phone || '') : '';
        document.getElementById('workerCost').value = w ? (parseFloat(w.costPerHalfDay) || 0) : '';
        document.getElementById('workerNotes').value = w ? (w.notes || '') : '';
        const selected = (w?.skills || []).map(String);
        document.querySelectorAll('#workerSkills .chip').forEach((c) => {
            c.classList.toggle('is-selected', selected.includes(c.getAttribute('data-value')));
        });
        // Login controls only make sense for a saved worker (needs an id to link).
        editingWorker = w;
        paintLogin(w);
        openSheet('workerSheet');
    }

    /* ---------------- Worker login controls ---------------- */

    function paintLogin(w) {
        const sec = document.getElementById('workerLoginSection');
        if (!sec) return;   // not the boss / no login controls rendered
        const show = !!(w && w.id);
        sec.classList.toggle('hidden', !show);
        if (!show) return;
        const login = w.login || null;
        const statusEl = document.getElementById('wlStatus');
        if (login && login.status === 'active') statusEl.textContent = 'Active — this worker can log in.';
        else if (login && login.status === 'pending') statusEl.textContent = 'Invite sent — waiting for them to set a password.';
        else statusEl.textContent = 'No login yet.';
        document.getElementById('wlAccess').value = (login && login.scheduleAccess) || 'view';
        document.getElementById('wlCommunity').checked = login ? !!login.communityAccess : true;
        window.workerRights.paint('wl', login);
        document.getElementById('wlRevoke').classList.toggle('hidden', !login);
        document.getElementById('wlPwRow').classList.add('hidden');
        document.getElementById('wlPassword').value = '';
    }

    function applyGrant(grant) {
        if (!editingWorker) return;
        editingWorker.login = grant || null;   // editingWorker is a live ref in WORKERS
        paintLogin(editingWorker);
        renderList();
    }
    const loginEmail = () => (document.getElementById('workerEmail').value || '').trim();

    document.getElementById('wlSendLink')?.addEventListener('click', async (e) => {
        const email = loginEmail();
        if (!email) { toast('Add the worker\'s email above first.', 'error'); document.getElementById('workerEmail').focus(); return; }
        const btn = e.currentTarget; btn.disabled = true;
        try {
            const res = await api(@json(route('sm.workers.access.grant')), { method: 'POST', body: {
                scheduleWorkerId: editingWorker && editingWorker.id,
                email,
                scheduleAccess: document.getElementById('wlAccess').value,
                communityAccess: document.getElementById('wlCommunity').checked ? 1 : 0,
                ...window.workerRights.read('wl'),
            } });
            toast(res.message);
            applyGrant(res.data && res.data.grant);
        } catch (err) { toast(err.message, 'error'); } finally { btn.disabled = false; }
    });

    document.getElementById('wlSetPwToggle')?.addEventListener('click', () => {
        const row = document.getElementById('wlPwRow');
        row.classList.toggle('hidden');
        if (!row.classList.contains('hidden')) document.getElementById('wlPassword').focus();
    });

    document.getElementById('wlCreateLogin')?.addEventListener('click', async (e) => {
        const email = loginEmail();
        if (!email) { toast('Add the worker\'s email above first.', 'error'); document.getElementById('workerEmail').focus(); return; }
        const pw = document.getElementById('wlPassword').value;
        if (pw.length < 8) { toast('Password must be at least 8 characters.', 'error'); return; }
        const btn = e.currentTarget; btn.disabled = true;
        try {
            const res = await api(@json(route('sm.workers.access.password')), { method: 'POST', body: {
                scheduleWorkerId: editingWorker && editingWorker.id,
                name: editingWorker && editingWorker.workerName,
                email, password: pw,
                scheduleAccess: document.getElementById('wlAccess').value,
                communityAccess: document.getElementById('wlCommunity').checked ? 1 : 0,
                ...window.workerRights.read('wl'),
            } });
            toast(res.message);
            applyGrant(res.data && res.data.grant);
        } catch (err) { toast(err.message, 'error'); } finally { btn.disabled = false; }
    });

    document.getElementById('wlRevoke')?.addEventListener('click', async (e) => {
        // Captured before the question is asked: `currentTarget` is null on
        // the far side of an await, and this used to throw instead of
        // revoking anything.
        const btn = e.currentTarget;
        if (!editingWorker || !editingWorker.login || !editingWorker.login.id) return;
        const ok = await confirmAction({ title: 'Revoke access?', message: 'This worker will no longer be able to log in.', confirmText: 'Revoke' });
        if (!ok) return;
        btn.disabled = true;
        try {
            const res = await api(@json(route('sm.workers.access.revoke')), { method: 'DELETE', body: { id: editingWorker.login.id } });
            toast(res.message);
            applyGrant(null);
        } catch (err) { toast(err.message, 'error'); } finally { btn.disabled = false; }
    });

    document.getElementById('saveWorkerBtn').addEventListener('click', async (e) => {
        const btn = e.currentTarget;
        const id = document.getElementById('workerId').value;
        const body = {
            workerName: document.getElementById('workerName').value.trim(),
            email: document.getElementById('workerEmail').value.trim() || null,
            phone: document.getElementById('workerPhone').value.trim() || null,
            costPerHalfDay: document.getElementById('workerCost').value || 0,
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
                phone: res.data.phone,
                costPerHalfDay: res.data.costPerHalfDay,
                priority: Number(res.data.priority) || 1,
                skills: res.data.skills || [],
                notes: res.data.notes,
                offDays: prev ? prev.offDays : [],
                offDates: prev ? prev.offDates : [],
                login: prev ? prev.login : null,
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

        const pmBtn = e.target.closest('[data-pm-worker]');
        if (pmBtn) {
            openWorkerPm(pmBtn.getAttribute('data-pm-worker'), pmBtn.getAttribute('data-pm-name') || 'Worker');
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

<script>
// Worker login grants (Boss/Lifetime)
(function workerGrants() {
    const $ = (id) => document.getElementById(id);
    const form = $('grantForm');
    if (!form) return;
    $('grantAccessBtn')?.addEventListener('click', () => { form.classList.toggle('hidden'); $('grantEmail').focus(); });
    $('grantCancel')?.addEventListener('click', () => form.classList.add('hidden'));
    $('grantSubmit')?.addEventListener('click', async (e) => {
        const btn = e.currentTarget;
        const email = $('grantEmail').value.trim();
        if (!email) { window.toast && toast('Enter the worker\'s email.', 'error'); return; }
        btn.disabled = true;
        try {
            const res = await window.api(@json(route('sm.workers.access.grant')), {
                method: 'POST',
                body: {
                    email,
                    scheduleAccess: $('grantAccess').value,
                    communityAccess: $('grantCommunity').checked ? 1 : 0,
                    ...window.workerRights.read('grant'),
                },
            });
            window.toast && toast(res.message || 'Invite sent.');
            $('grantEmail').value = '';
            form.classList.add('hidden');
        } catch (err) { window.toast && toast(err.message, 'error'); }
        finally { btn.disabled = false; }
    });
})();
</script>
@endpush
