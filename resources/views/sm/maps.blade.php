@extends(request()->boolean('partial') ? 'layouts.partial' : 'layouts.app')

@section('title', 'Maps — ' . $schedule->title)
@section('page-title', 'Maps')
@section('page-subtitle', $schedule->title)
@section('back', route('sm.hub', ['id' => $schedule->id]))

@push('head')
    <style>
        /* The map wants height, and inside the Activities shell it has no
           parent that gives it any — so the stage claims the viewport below
           the header rather than collapsing to nothing. */
        .smap-stage {
            height: calc(100dvh - 11rem); min-height: 22rem;
            border: 1px solid var(--color-gray-200); border-radius: 1rem; overflow: hidden;
            background: var(--color-white);
        }
        @media (max-width: 767px) { .smap-stage { height: calc(100dvh - 9.5rem); } }
    </style>
@endpush

@section('content')
    <div class="smap-stage">
        @include('sm.partials.schedule-map', ['schedule' => $schedule])
    </div>

    <script>
        /* Same partial the Collab Room uses, so every tool, the saved maps and
           the live team drawing come with it. It boots on demand there (when
           its tab opens); here the module IS the map, so boot it now — and on
           the next frame, since the shell injects this markup and re-runs the
           script before the stage has been laid out. */
        (() => {
            const boot = () => {
                if (typeof window.initCollabMap === 'function') window.initCollabMap();
                else setTimeout(boot, 120);
            };
            requestAnimationFrame(boot);
        })();
    </script>
@endsection
