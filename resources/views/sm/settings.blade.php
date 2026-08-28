@extends(request()->boolean('partial') ? 'layouts.partial' : 'layouts.app')

@section('title', 'Settings — ' . $schedule->title)
@section('page-title', 'Settings')
@section('page-subtitle', $schedule->title)
@section('help-key', 'settings')
@section('back', route('sm.hub', ['id' => $schedule->id]))

@push('head')
    <style>
        /* Same segmented strip the rest of the app uses for panes. */
        .set-tabs { display: inline-flex; gap: .25rem; padding: .25rem; border-radius: .8rem;
            background: var(--color-gray-100); width: 100%; }
        .set-tab { flex: 1 1 0; padding: .5rem .6rem; border-radius: .6rem; font-size: .85rem; font-weight: 700;
            color: var(--color-gray-500); cursor: pointer;
            transition: background .28s cubic-bezier(.22,1,.36,1), color .28s cubic-bezier(.22,1,.36,1); }
        .set-tab.is-on { background: var(--color-white); color: var(--color-brand-700);
            box-shadow: 0 1px 3px rgb(0 0 0 / .08); }
        html.dark .set-tab.is-on { background: #1c2416; }
        @media (prefers-reduced-motion: reduce) { .set-tab { transition: none; } }
    </style>
@endpush

@section('content')
    @include('sm.partials.module-header', ['schedule' => $schedule, 'module' => 'settings'])

    @php
        // What this season is called, what it is, and who gets told about it
        // each morning: the owner's answers. A worker reads them — the fields
        // are theirs to see, because they explain the board — and is offered
        // nothing that would only be refused on the way back.
        $setWorker = \App\Support\WorkerContext::inWorkerContext();
    @endphp

    <div class="max-w-3xl space-y-4">

        @if ($setWorker)
            <p class="card card-body text-sm text-gray-500">
                👁️ These are the farm owner's settings. You can read them here; changing them is theirs to do.
            </p>
        @endif

        {{-- Two things live here now, and they are not the same job: what this
             schedule IS, and who hears about it each morning. --}}
        <div class="set-tabs" id="setTabs" role="tablist">
            <button type="button" class="set-tab is-on" data-set-tab="basic" aria-selected="true">Basic info</button>
            <button type="button" class="set-tab" data-set-tab="notify" aria-selected="false">Notifications</button>
        </div>

        <div data-set-pane="basic">
        {{-- Basic Info --}}
        <div class="card">
            <div class="card-body space-y-4">
                <div>
                    <h2 class="font-bold text-gray-900">Basic Info</h2>
                    <p class="text-sm text-gray-500">Title, description and how day numbers are labeled.</p>
                </div>

                <div>
                    <label for="settingsTitle" class="form-label">Title <span class="text-red-500">*</span></label>
                    {{-- Readable, not editable: a field that takes typing and
                         then has nowhere to send it is a small lie. --}}
                    <input type="text" id="settingsTitle" maxlength="255" class="form-input" value="{{ $schedule->title }}" @readonly($setWorker)>
                </div>

                <div>
                    <label for="settingsDescription" class="form-label">Description</label>
                    <textarea id="settingsDescription" rows="3" maxlength="5000" class="form-textarea" @readonly($setWorker)>{{ $schedule->description }}</textarea>
                </div>

                {{-- HOW DAYS ARE COUNTED.
                     The heading above has promised this since the module was
                     written, and it was only ever askable at creation — so a
                     season set up wrong stayed wrong, and an orchard had no
                     way to say it keeps no day count at all.

                     It is the season's default. Each lot can still answer for
                     itself in Lots, which is where a farm with rice in the
                     paddy and mangoes on the ridge sorts itself out. --}}
                <div>
                    <label for="settingsDayType" class="form-label">How days are counted</label>
                    <select id="settingsDayType" class="form-select" @disabled($setWorker)>
                        <option value="DAT" @selected(($schedule->dayType ?: 'DAS') === 'DAT')>DAS → DAT — sown, then transplanted</option>
                        <option value="DAS" @selected(($schedule->dayType ?: 'DAS') === 'DAS')>DAS only — direct seeded (DSR)</option>
                        <option value="DAP" @selected($schedule->dayType === 'DAP')>DAP — days after planting</option>
                        <option value="TREE" @selected($schedule->dayType === 'TREE')>Mature trees — no day count, read by age</option>
                    </select>
                    <p class="form-hint" id="settingsDayTypeHint"></p>
                    <p class="form-hint">This is the season's default. A lot can still be set differently in <a href="{{ route('sm.lots', ['id' => $schedule->id]) }}" class="text-brand-700 font-semibold">Lots</a>.</p>
                </div>

                @unless ($setWorker)
                <div class="flex justify-end">
                    <button type="button" id="saveBasicBtn" class="btn btn-primary w-full sm:w-auto">Save Basic Info</button>
                </div>
                @endunless
            </div>
        </div>

        </div>

        <div data-set-pane="notify" hidden>
            <div class="card">
                <div class="card-body">
                    {{-- The head says what the whole pane is for before any
                         switch does, because "notifications" on its own could
                         mean six different things. --}}
                    <div class="nt-head">
                        <span class="nt-head-mark" aria-hidden="true">
                            <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        </span>
                        <div class="min-w-0">
                            <h2 class="nt-head-h">Daily schedule email</h2>
                            <p class="nt-head-p">One message each morning with what is on today and what is
                                coming tomorrow, so nobody has to open the app to find out where to be.</p>
                        </div>
                    </div>

                    {{-- Each answer is a card you can tap anywhere on, not a
                         checkbox with a paragraph standing beside it. Two of
                         them side by side once there is room. --}}
                    <div class="nt-picks">
                        <label class="nt-pick">
                            <input type="checkbox" id="notifyWorkersDaily" @disabled($setWorker) @checked($schedule->notifyWorkersDaily)>
                            <span class="nt-pick-body">
                                <b>Email the workers</b>
                                <i>Each worker gets only the activities they are actually on. Anyone with
                                   no address on file is skipped.</i>
                            </span>
                        </label>

                        <label class="nt-pick">
                            <input type="checkbox" id="notifyOwnerDaily" @disabled($setWorker) @checked($schedule->notifyOwnerDaily)>
                            <span class="nt-pick-body">
                                <b>Email me</b>
                                <i>The whole day — every activity, and whoever is on it.</i>
                            </span>
                        </label>
                    </div>

                    <div class="nt-when">
                        <div class="min-w-0">
                            <label class="form-label mb-1!" for="notifyHour">Send at</label>
                            <p class="nt-when-p">Philippine time. Once a day — a re-run never sends twice.</p>
                        </div>
                        <select id="notifyHour" class="form-select nt-hour" @disabled($setWorker)>
                            @for ($h = 0; $h < 24; $h++)
                                <option value="{{ $h }}" @selected((int) $schedule->notifyHour === $h)>
                                    {{ \Carbon\Carbon::createFromTime($h)->format('g:00 A') }}
                                </option>
                            @endfor
                        </select>
                    </div>

                    <div class="nt-acts">
                        @unless ($setWorker)
                        <button type="button" class="btn btn-primary" id="saveNotifyBtn">Save notifications</button>
                        @endunless
                        <button type="button" class="btn btn-white" id="testNotifyBtn">Send me one now</button>
                    </div>
                </div>
            </div>
        </div>

    </div>

@push('head')
<style>
    /* ---- The notifications pane -----------------------------------------
       It was a heading, two checkboxes with paragraphs beside them, a select,
       a grey box explaining the plumbing, and two buttons: a form read top to
       bottom, for a screen that asks three short questions.

       The grey box has gone with the plumbing it described. Mail leaves
       through Resend now, and a farmer setting the morning email has no use
       for the name of a transport. */
    .nt-head { display: flex; align-items: flex-start; gap: .85rem; }
    .nt-head-mark { flex: none; width: 2.6rem; height: 2.6rem; border-radius: .85rem;
        display: inline-flex; align-items: center; justify-content: center;
        background: #eef6e6; color: #3d6823; }
    .nt-head-mark svg { width: 1.35rem; height: 1.35rem; }
    .nt-head-h { font-family: var(--font-heading); font-size: 1.02rem; font-weight: 800;
        color: var(--color-gray-900); line-height: 1.25; }
    .nt-head-p { margin-top: .2rem; font-size: .82rem; line-height: 1.55; color: var(--color-gray-500); }

    .nt-picks { display: grid; gap: .6rem; margin-top: 1.1rem; }
    @media (min-width: 640px) { .nt-picks { grid-template-columns: 1fr 1fr; } }
    .nt-pick { display: flex; align-items: flex-start; gap: .7rem; cursor: pointer;
        padding: .85rem .9rem; border-radius: .9rem;
        border: 1px solid var(--color-gray-200); background: var(--color-white);
        transition: border-color .22s cubic-bezier(.22,1,.36,1), background .22s cubic-bezier(.22,1,.36,1); }
    .nt-pick:hover { border-color: #a8cc7e; background: #f8fbf4; }
    /* What is ON says so without being read: somebody glancing at this pane
       wants to know what it is doing, not to audit two checkboxes. */
    .nt-pick:has(input:checked) { border-color: #4a7c2a; background: #f2f8ec; }
    .nt-pick:has(input:disabled) { cursor: default; opacity: .7; }
    .nt-pick input { flex: none; margin-top: .15rem; width: 1.15rem; height: 1.15rem; border-radius: .35rem; }
    .nt-pick-body { min-width: 0; }
    .nt-pick-body b { display: block; font-size: .88rem; font-weight: 800; color: var(--color-gray-900); }
    .nt-pick-body i { display: block; font-style: normal; margin-top: .18rem;
        font-size: .76rem; line-height: 1.55; color: var(--color-gray-500); }

    /* The hour stands beside its own sentence rather than under it. */
    .nt-when { display: flex; align-items: center; gap: 1rem; flex-wrap: wrap;
        margin-top: 1.1rem; padding: .9rem; border-radius: .9rem;
        background: var(--color-gray-50); border: 1px solid var(--color-gray-200); }
    .nt-when-p { font-size: .74rem; line-height: 1.5; color: var(--color-gray-500); }
    .nt-hour { width: 9.5rem; flex: none; margin-left: auto; }
    .nt-acts { display: flex; flex-wrap: wrap; gap: .5rem; margin-top: 1.1rem; }
    .nt-acts .btn { flex: 1 1 auto; justify-content: center; }
    @media (min-width: 480px) { .nt-acts .btn { flex: 0 0 auto; } }

    html.dark .nt-head-mark { background: rgb(107 159 61 / .18); color: #a5c97e; }
    html.dark .nt-pick { background: #151b12; border-color: #2b3a1c; }
    html.dark .nt-pick:has(input:checked) { background: rgb(74 124 42 / .18); border-color: #4a7c2a; }
    html.dark .nt-pick-body b { color: #e8efe1; }
    html.dark .nt-when { background: rgb(255 255 255 / .04); border-color: #2b3a1c; }
    @media (prefers-reduced-motion: reduce) { .nt-pick { transition: none; } }
</style>
@endpush
@endsection

@push('scripts')
<script>
(() => {
const __init = () => {
    const SCHEDULE_ID = {{ $schedule->id }};

    /* ---------------- Panes ---------------- */
    document.getElementById('setTabs')?.addEventListener('click', (e) => {
        const tab = e.target.closest('[data-set-tab]');
        if (!tab) return;
        const which = tab.getAttribute('data-set-tab');
        document.querySelectorAll('[data-set-tab]').forEach((b) => {
            const on = b === tab;
            b.classList.toggle('is-on', on);
            b.setAttribute('aria-selected', on ? 'true' : 'false');
        });
        document.querySelectorAll('[data-set-pane]').forEach((p) => {
            p.hidden = p.getAttribute('data-set-pane') !== which;
        });
    });

    /* ---------------- Daily digest ---------------- */
    document.getElementById('saveNotifyBtn')?.addEventListener('click', async (e) => {
        const btn = e.currentTarget;
        btn.disabled = true;
        try {
            const res = await api(`{{ route('sm.update') }}?id=${SCHEDULE_ID}`, {
                method: 'PUT',
                body: {
                    // The title comes along because the endpoint requires it;
                    // sending the current value keeps this save from changing it.
                    title: document.getElementById('settingsTitle').value.trim(),
                    description: document.getElementById('settingsDescription').value,
                    notifyWorkersDaily: document.getElementById('notifyWorkersDaily').checked,
                    notifyOwnerDaily: document.getElementById('notifyOwnerDaily').checked,
                    notifyHour: parseInt(document.getElementById('notifyHour').value, 10),
                },
            });
            toast(res.message);
        } catch (err) { toast(err.message, 'error'); } finally { btn.disabled = false; }
    });

    document.getElementById('testNotifyBtn')?.addEventListener('click', async (e) => {
        const btn = e.currentTarget;
        btn.disabled = true;
        try {
            const res = await api(`{{ route('sm.digest.test') }}?id=${SCHEDULE_ID}`, { method: 'POST' });
            toast(res.message);
        } catch (err) { toast(err.message, 'error'); } finally { btn.disabled = false; }
    });

    /* ---------------- Basic Info ---------------- */

    /* What the season is set to now, so a save that does not touch the day
       counter does not spend a request saying so. */
    let CURRENT_DAY_TYPE = @json($schedule->dayType ?: 'DAS');

    /* Each answer, in a sentence — the codes are three letters and the
       difference between them is a whole calendar. */
    const DAY_TYPE_SAYS = {
        DAT: 'Counts DAS from sowing, then restarts as DAT on the transplant date.',
        DAS: 'One count from sowing, all season. Direct-seeded rice never becomes DAT.',
        DAP: 'One count from the day it went in the ground.',
        TREE: 'No day count at all. The trees are read by their age, which each lot gives in Lots.',
    };
    const sayDayType = () => {
        const sel = document.getElementById('settingsDayType');
        const hint = document.getElementById('settingsDayTypeHint');
        if (sel && hint) hint.textContent = DAY_TYPE_SAYS[sel.value] || '';
    };
    document.getElementById('settingsDayType')?.addEventListener('change', sayDayType);
    sayDayType();

    document.getElementById('saveBasicBtn').addEventListener('click', async (e) => {
        const btn = e.currentTarget;
        btn.disabled = true;
        try {
            /* Two calls, because they are two endpoints.
             *
             * The day counter has always had its own — it relabels every day
             * number on the board for everyone, and it refuses on a locked
             * season, which a title change does not. Sending it through the
             * general update would mean teaching that endpoint a rule it does
             * not have. It goes first: if it is refused, the save says so
             * rather than reporting success over a setting that did not take. */
            const dayType = document.getElementById('settingsDayType')?.value;
            if (dayType && dayType !== CURRENT_DAY_TYPE) {
                await api(`{{ route('sm.day-type') }}?id=${SCHEDULE_ID}`, {
                    method: 'POST',
                    body: { dayType },
                });
                CURRENT_DAY_TYPE = dayType;
            }

            const res = await api(`{{ route('sm.update') }}?id=${SCHEDULE_ID}`, {
                method: 'PUT',
                body: {
                    title: document.getElementById('settingsTitle').value.trim(),
                    description: document.getElementById('settingsDescription').value,
                },
            });
            toast(res.message);
            const t = res.data?.title;
            if (t) {
                // Live-update the app-bar subtitle (schedule title) + tab title.
                const sub = document.querySelector('header .min-w-0 p.text-xs');
                if (sub) sub.textContent = t;
                document.title = `Settings — ${t} | anee.io`;
            }
        } catch (err) {
            toast(err.message, 'error');
        } finally {
            btn.disabled = false;
        }
    });

};
    // First load: wait for app.js (deferred) to define the globals.
    // SPA injection: document is already complete, so run now.
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', __init, { once: true });
    else __init();
})();
</script>
@endpush
