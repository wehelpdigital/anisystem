@extends(request()->boolean('partial') ? 'layouts.partial' : 'layouts.app')

@section('title', 'Weather — ' . $schedule->title)
@section('page-title', 'Weather')
@section('page-subtitle', $schedule->title)
@section('back', route('sm.hub', ['id' => $schedule->id]))

@push('head')
    <style>
        /* On a phone the page's own padding plus the card's took 66px of 390 —
           a sixth of the screen — and the rounded corners clipped the ends of
           the day strip and the hour rail. The cards run edge to edge here
           instead: the forecast is a wide strip of numbers, and width is the
           thing it actually needs. Scoped to this module, so the same panels
           inside the activities sheet keep their inset card look. */
        @media (max-width: 767px) {
            .wx-bleed .card {
                width: 100vw; margin-left: 50%; transform: translateX(-50%);
                border-radius: 0; border-left: 0; border-right: 0;
            }
            .wx-bleed .card-body { padding-left: .85rem; padding-right: .85rem; }
            /* The rail may then start at the very edge, which reads as cut off
               unless its first card keeps a little air. */
            .wx-bleed .wx-hours { padding-left: 0; padding-right: .85rem; }
        }
    </style>
@endpush

@section('content')
    {{-- The panels (and their tabs) are shared with the activities weather
         sheet — see the partial. This page only has to fetch and hand over. --}}
    @include('sm.partials.weather-panels')

    <div id="wxModuleHost" class="wx-bleed">
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
