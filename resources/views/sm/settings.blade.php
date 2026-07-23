@extends(request()->boolean('partial') ? 'layouts.partial' : 'layouts.app')

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
                </div>

                <div class="flex justify-end">
                    <button type="button" id="saveBasicBtn" class="btn btn-primary w-full sm:w-auto">Save Basic Info</button>
                </div>
            </div>
        </div>

        {{-- Default groupings now live on the Lots page, next to the lots they group. --}}
        <div class="card">
            <div class="card-body flex items-center justify-between gap-3">
                <div>
                    <h2 class="font-bold text-gray-900">Default Groupings</h2>
                    <p class="text-sm text-gray-500">Grouping lots by start date has moved to the Lots page.</p>
                </div>
                <a href="{{ route('sm.lots', ['id' => $schedule->id]) }}" class="btn btn-white shrink-0">Go to Lots</a>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
(() => {
const __init = () => {
    const SCHEDULE_ID = {{ $schedule->id }};

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
