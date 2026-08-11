@extends(request()->boolean('partial') ? 'layouts.partial' : 'layouts.app')

@section('title', 'Maps — ' . $schedule->title)
@section('page-title', 'Maps')
@section('page-subtitle', $schedule->title)
@section('back', route('sm.hub', ['id' => $schedule->id]))

@push('head')
    <style>
        /* The map wants height, and inside the Activities shell it has no
           parent that gives it any — so the stage claims the viewport below
           the header rather than collapsing to nothing. The height is set in
           JS from what is actually on screen (see below); this is the fallback
           before that runs, and the desktop framing. */
        .smap-stage {
            height: calc(100dvh - 11rem); min-height: 22rem;
            border: 1px solid var(--color-gray-200); border-radius: 1rem; overflow: hidden;
            background: var(--color-white);
        }
        @media (max-width: 767px) {
            /* A map is the one thing that wants the whole screen: the page's
               padding and the stage's own frame were taking 47px of width and
               36px of height, and the rounded corners cut into the imagery.
               It runs to all four edges here — bled sideways, and pulled up
               and down over the page padding so the toolbar sits right under
               the header. */
            .smap-stage {
                height: calc(100dvh - 9.5rem);
                width: 100vw; margin-left: 50%; transform: translateX(-50%);
                margin-top: -1rem; margin-bottom: -1rem;
                border-radius: 0; border-left: 0; border-right: 0;
            }
        }
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

            /* Fill whatever is left of the screen, measured rather than
               guessed: the header, the tab bar and the browser's own chrome
               differ per device, and a hard calc() left a band of dead page
               under the map on some and clipped it on others. */
            const stage = document.querySelector('.smap-stage');
            function fit() {
                if (!stage || !window.matchMedia('(max-width: 767px)').matches) return;
                const bar = document.querySelector('.tabbar');
                const floor = bar ? bar.getBoundingClientRect().top : window.innerHeight;
                const h = Math.max(260, Math.round(floor - stage.getBoundingClientRect().top));
                stage.style.height = h + 'px';
                // Google needs telling when its container changes size.
                if (window.google?.maps?.event) window.google.maps.event.trigger(window, 'resize');
            }
            /* A full-screen map with a site footer under it means the page
               scrolls away from the map you are working on. The footer goes
               while the map is on screen — and comes back the moment it is
               not, which matters in the module shell, where switching modules
               only hides this one rather than removing it. A ResizeObserver
               catches that: a hidden stage measures zero. */
            const phone = () => window.matchMedia('(max-width: 767px)').matches;
            function chrome() {
                const shown = !!stage && stage.getBoundingClientRect().height > 0;
                document.body.classList.toggle('no-footer', shown && phone());
            }
            if (stage && window.ResizeObserver) {
                new ResizeObserver(() => { chrome(); fit(); }).observe(stage);
            }
            requestAnimationFrame(() => { chrome(); fit(); });
            window.addEventListener('resize', () => { chrome(); fit(); });
            window.addEventListener('orientationchange', () => setTimeout(fit, 200));
        })();
    </script>
@endsection
