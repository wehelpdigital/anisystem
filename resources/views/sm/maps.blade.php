@extends(request()->boolean('partial') ? 'layouts.partial' : 'layouts.app')

@section('title', 'Maps — ' . $schedule->title)
@section('page-title', 'Maps')
@section('page-subtitle', $schedule->title)
@section('help-key', 'maps')
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
        /* The AI bubble parks itself bottom-right, which on a map is directly
           over the ground you are drawing on — and unlike a list, a map has no
           dead corner. It stands down while the map is open. */
        body.smap-open #aiFloat { display: none !important; }

        @media (max-width: 767px) {
            /* The map already fills everything below the toolbar, so there is
               nothing under it to reach — but a stray pixel of overflow still
               let the page drag down to a blank band. With the module open the
               page itself does not scroll; the map handles its own gestures. */
            body.smap-open { overflow: hidden; }
        }

        @media (max-width: 767px) {
            /* 81px of chrome sat between the header and the map for a single
               row of two buttons: the page's top padding, the bar's own
               padding and its bottom margin all stacked. Trimmed to about
               half here — the map is the page. */
            /* Nothing above the toolbar — the row tucks under the header —
               but the map keeps clear of the bar's divider underneath it,
               which otherwise looked like the map was hanging off the row. */
            body.smap-open main { padding-top: 0; }
            body.smap-open main > .sticky { margin-bottom: .5rem; }
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
                /* Half the page's padding back, not all of it: a thin even
                   frame reads as deliberate where a raw bleed reads as broken,
                   and the map still gets all but 8px of the screen. No blind
                   negative margin on top — in the module shell the stage sits
                   under the toolbar, and pulling it up closed that gap. */
                margin-left: -.5rem; margin-right: -.5rem;
                /* The stage ends 8px above the floor; without this the page's
                   own bottom padding would sit under it and leave the page
                   scrollable by exactly that much. */
                margin-bottom: -1rem;
                border-radius: .6rem;
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
            const GAP = 8;   // the same thin frame the sides get
            function fit() {
                if (!stage || !window.matchMedia('(max-width: 767px)').matches) return;
                // A tab bar that is not rendered still answers with a rect —
                // top 0 — which collapsed the map to its minimum height. The
                // activities shell hides the bar, so its presence has to be
                // measured. Height, not offsetParent: the bar is
                // position:fixed, and fixed elements report no offsetParent
                // even when they are plainly on screen.
                const bar = document.querySelector('.tabbar');
                const barBox = bar ? bar.getBoundingClientRect() : null;
                const floor = barBox && barBox.height > 0 ? barBox.top : window.innerHeight;
                const h = Math.max(260, Math.round(floor - stage.getBoundingClientRect().top - GAP));
                if (stage.style.height === h + 'px') return;   // no resize loop
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
                // Its own class rather than the shell's ai-float-off: the shell
                // drives that one for the AI module, and two owners toggling
                // one class fight each other when you switch between them.
                document.body.classList.toggle('smap-open', shown);
                document.body.classList.toggle('no-footer', shown && phone());
            }
            if (stage && window.ResizeObserver) {
                const ro = new ResizeObserver(() => { chrome(); fit(); });
                ro.observe(stage);
                // The stage keeping its size is not the same as the page
                // keeping its shape: the toolbar collapsing, fonts landing or
                // Google's own chrome arriving all move the stage without
                // resizing it, and only a fit afterwards is the right height.
                ro.observe(document.body);
            }
            requestAnimationFrame(() => { chrome(); fit(); });
            // First paint is the worst moment to measure — the module is
            // injected, scripts re-run, then the map boots. A few passes catch
            // whatever settles late, and fit() is a no-op once it agrees.
            [60, 200, 500, 1000, 1800].forEach((ms) => setTimeout(() => { chrome(); fit(); }, ms));
            window.addEventListener('resize', () => { chrome(); fit(); });
            window.addEventListener('orientationchange', () => setTimeout(fit, 200));
        })();
    </script>
@endsection
