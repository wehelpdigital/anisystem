@extends(request()->boolean('partial') ? 'layouts.partial' : 'layouts.app')

@section('title', 'Weather — ' . $schedule->title)
@section('page-title', 'Weather')
@section('page-subtitle', $schedule->title)
@section('back', route('sm.hub', ['id' => $schedule->id]))

@section('content')
    {{-- The panels (and their tabs) are shared with the activities weather
         sheet — see the partial. This page only has to fetch and hand over. --}}
    @include('sm.partials.weather-panels')

    <div id="wxModuleHost">
        <div class="card"><div class="card-body text-center text-sm text-gray-500">Loading the forecast…</div></div>
    </div>

    <script>
    (() => {
        const host = document.getElementById('wxModuleHost');
        fetch(@json(route('sm.weather')) + '?scheduleId=' + @json($schedule->id) + '&hourly=1',
            { headers: { Accept: 'application/json' }, credentials: 'same-origin' })
            .then((r) => r.json())
            .then((res) => window.wxRenderPanels(host, (res && res.data) || {}))
            .catch(() => {
                host.innerHTML = '<div class="card"><div class="card-body text-center text-sm text-gray-500">'
                    + 'Could not load the forecast just now.</div></div>';
            });
    })();
    </script>
@endsection
