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
                <div class="card-body space-y-4">
                    <div>
                        <h2 class="font-bold text-gray-900">Daily schedule email</h2>
                        <p class="text-sm text-gray-500">
                            One message each morning with what is on today and what is coming tomorrow, so nobody
                            has to open the app to find out where to be.
                        </p>
                    </div>

                    <label class="flex items-start gap-3 cursor-pointer select-none">
                        <input type="checkbox" id="notifyWorkersDaily" @disabled($setWorker) class="mt-1 w-5 h-5 rounded"
                               @checked($schedule->notifyWorkersDaily)>
                        <span class="text-sm text-gray-700">
                            <strong class="text-gray-900">Email the workers</strong><br>
                            Each worker gets only the activities they are actually on. A worker with no email
                            address on file is skipped.
                        </span>
                    </label>

                    <label class="flex items-start gap-3 cursor-pointer select-none">
                        <input type="checkbox" id="notifyOwnerDaily" @disabled($setWorker) class="mt-1 w-5 h-5 rounded"
                               @checked($schedule->notifyOwnerDaily)>
                        <span class="text-sm text-gray-700">
                            <strong class="text-gray-900">Email me</strong><br>
                            The whole day, every activity, whoever is on it.
                        </span>
                    </label>

                    <div>
                        <label class="form-label" for="notifyHour">Send at</label>
                        <select id="notifyHour" class="form-select" style="max-width:12rem" @disabled($setWorker)>
                            @for ($h = 0; $h < 24; $h++)
                                <option value="{{ $h }}" @selected((int) $schedule->notifyHour === $h)>
                                    {{ \Carbon\Carbon::createFromTime($h)->format('g:00 A') }}
                                </option>
                            @endfor
                        </select>
                        <p class="form-hint">Philippine time. Sent once a day — a re-run never sends twice.</p>
                    </div>

                    <div class="rounded-xl bg-gray-50 border border-gray-200 p-3 text-xs text-gray-500">
                        Mail goes out through the SMTP set up in the mother app, and the layout is the template
                        written there — so changing either changes what everyone receives.
                    </div>

                    <div class="flex flex-wrap gap-2">
                        @unless ($setWorker)
                        <button type="button" class="btn btn-primary" id="saveNotifyBtn">Save notifications</button>
                        @endunless
                        <button type="button" class="btn btn-white" id="testNotifyBtn">Send me one now</button>
                    </div>
                </div>
            </div>
        </div>

    </div>
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

    document.getElementById('saveBasicBtn').addEventListener('click', async (e) => {
        const btn = e.currentTarget;
        btn.disabled = true;
        try {
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
                document.title = `Settings — ${t} | AniSystem`;
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
