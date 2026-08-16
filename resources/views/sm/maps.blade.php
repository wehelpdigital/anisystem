@extends(request()->boolean('partial') ? 'layouts.partial' : 'layouts.app')

@section('title', 'Maps — ' . $schedule->title)
@section('page-title', 'Maps')
@section('page-subtitle', $schedule->title)
@section('help-key', 'maps')
@section('back', route('sm.hub', ['id' => $schedule->id]))

@push('head')
    <style>
        /* ---- The shelf: every saved map as a card, the way Draw shows its
           drawings — recognised by picture and name before the stage opens.
           The stage is only entered by choosing a map or starting one. ---- */
        .mp-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(9.5rem, 1fr)); gap: .75rem; }
        .mp-card { display: flex; flex-direction: column; overflow: hidden; background: var(--color-white);
            border: 1px solid var(--color-gray-200); border-radius: .85rem; text-align: left; cursor: pointer;
            transition: box-shadow .28s cubic-bezier(.22,1,.36,1), transform .28s cubic-bezier(.22,1,.36,1); }
        .mp-card:hover { box-shadow: 0 10px 26px -18px rgb(0 0 0 / .45); transform: translateY(-1px); }
        /* The mark sits under the picture: while the thumb loads (or if it
           cannot be drawn at all) the card shows the map mark, never a
           "missing" apology. */
        .mp-thumb { position: relative; aspect-ratio: 4 / 3; background: var(--color-gray-50); overflow: hidden;
            display: flex; align-items: center; justify-content: center; color: #6b9f3d; }
        .mp-thumb > svg { width: 2.2rem; height: 2.2rem; }
        .mp-thumb img { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; opacity: 0;
            transition: opacity .28s cubic-bezier(.22,1,.36,1); }
        .mp-thumb img.is-loaded { opacity: 1; }
        .mp-meta { padding: .5rem .6rem .6rem; display: flex; flex-direction: column; gap: .3rem; }
        .mp-name { font-size: .8rem; font-weight: 700; color: var(--color-gray-900); line-height: 1.25;
            display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
        .mp-tags { display: flex; flex-wrap: wrap; gap: .25rem; }
        .mp-innote { display: inline-flex; align-items: center; gap: .2rem; text-decoration: none; }
        .mp-innote:hover { background: #e4efd4; color: #3d6823; }
        .mp-when { font-size: .66rem; color: var(--color-gray-400); }
        /* The way in is a tile of the same size as the maps, so the grid reads
           as one row of things rather than a button and then a list. */
        .mp-new { display: flex; flex-direction: column; align-items: center; justify-content: center; gap: .4rem;
            min-height: 9.5rem; border: 2px dashed var(--color-gray-300); border-radius: .85rem;
            color: var(--color-brand-700); font-weight: 700; font-size: .8rem; background: var(--color-white); cursor: pointer;
            transition: border-color .28s cubic-bezier(.22,1,.36,1), background .28s cubic-bezier(.22,1,.36,1); }
        .mp-new:hover { border-color: var(--color-brand-500); background: var(--color-brand-50); }
        .mp-new svg { width: 1.6rem; height: 1.6rem; }
        .mp-empty { text-align: center; color: var(--color-gray-400); font-size: .8rem; padding: 1.25rem .5rem 0; }
        /* Grid and stage swap with the house ease rather than snapping. */
        @keyframes mpViewIn { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: none; } }
        .mp-view-in { animation: mpViewIn .28s cubic-bezier(.22,1,.36,1); }
        .mp-stagebar { display: flex; align-items: center; gap: .5rem; margin-bottom: .5rem; }
        .mp-stagehint { font-size: .72rem; color: var(--color-gray-400); min-width: 0; flex: 1 1 auto;
            overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        /* Everything that is not a drawing tool lives here, out of the map's
           own toolbar — which on a phone had no room left for the tools. */
        .mp-stageacts { display: flex; align-items: center; gap: .25rem; flex-shrink: 0; margin-left: auto; }
        .mp-act { min-width: 2.15rem; height: 2.15rem; border-radius: .6rem; flex-shrink: 0;
            display: inline-flex; align-items: center; justify-content: center; gap: .3rem;
            background: var(--color-gray-100); color: var(--color-gray-600);
            transition: background .15s ease, color .15s ease, transform .1s ease; }
        .mp-act svg { width: 1.15rem; height: 1.15rem; }
        .mp-act:hover { background: var(--color-gray-200); color: var(--color-gray-800); }
        .mp-act:active { transform: scale(.92); }
        .mp-act.is-off { opacity: .35; pointer-events: none; }
        /* Wins over .is-off, which the proxy also wears while the real button is
           disabled: the press has been taken, not turned down. */
        .mp-act.is-busy { opacity: 1; pointer-events: none; }
        .mp-act.is-busy svg { animation: mpSeek 1.1s ease-in-out infinite; transform-origin: 50% 50%; }
        @keyframes mpSeek {
            0%, 100% { opacity: .45; transform: scale(.88); }
            50% { opacity: 1; transform: scale(1.06); }
        }
        @media (prefers-reduced-motion: reduce) {
            .mp-act.is-busy svg { animation: none; opacity: .55; }
        }
        .mp-act.is-on, html.dark .mp-act.is-on { background: var(--color-brand-100); color: var(--color-brand-800); }
        .mp-act.is-save { width: auto; padding: 0 .7rem; background: #4a7c2a; color: #fff; font-size: .78rem; font-weight: 800; }
        .mp-act.is-save:hover { background: #3d6823; color: #fff; }
        .mp-act.is-danger { color: #dc2626; }
        .mp-act.is-danger:hover { background: #fee2e2; color: #b91c1c; }
        .mp-actdiv { width: 1px; height: 1.4rem; background: var(--color-gray-200); flex-shrink: 0; margin: 0 .15rem; }
        html.dark .mp-act { background: rgb(255 255 255 / .06); color: #cdd8c0; }
        html.dark .mp-act:hover { background: rgb(255 255 255 / .12); color: #e8efe1; }
        html.dark .mp-actdiv { background: #2b3a1c; }
        /* Their originals stay in the map's toolbar for the Collab Room, but
           inside this module they would be the same button twice. */
        #smapStageWrap .cmap-bar #cmapUndo,
        #smapStageWrap .cmap-bar #cmapRedo,
        #smapStageWrap .cmap-bar #cmapFindMe,
        #smapStageWrap .cmap-bar #cmapSaveMenuBtn,
        #smapStageWrap .cmap-bar .cmap-div { display: none; }
        @media (max-width: 520px) {
            /* The words stay: an arrow on its own said nothing about where
               it went, and "Save" is the whole point of the green button. */
            .mp-stagehint { display: none; }
        }
        @media (prefers-reduced-motion: reduce) {
            .mp-card, .mp-new, .mp-thumb img { transition: none; }
            .mp-view-in { animation: none; }
        }
        html.dark .mp-card, html.dark .mp-new { background: #151b12; border-color: #2b3a1c; }
        html.dark .mp-name { color: #e8efe1; }

        /* The map wants height, and inside the Activities shell it has no
           parent that gives it any — so the stage claims the viewport below
           the header rather than collapsing to nothing. The height is set in
           JS from what is actually on screen (see below); this is the fallback
           before that runs, and the desktop framing. */
        .smap-stage {
            height: calc(100dvh - 11rem); min-height: min(22rem, 55dvh);
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
    @php
        // A note's "View map" tag names the save it means; arriving with one
        // skips the shelf and opens the stage on that map.
        $openSaveQ = (int) request()->query('save');
    @endphp
    {{-- The shelf explains itself; the stage does not need explaining, and on
         a phone this paragraph was eating the map. It lives with the grid. --}}
    <div id="smapHome" @if ($openSaveQ) class="hidden" @endif>
        @include('sm.partials.module-note', [
            'say' => 'Every map this schedule has saved — the team’s and your own. Open one to keep working on it, or start a new map. A saved map files its picture in the notebook, so a save can carry a note explaining what it was for.',
        ])
        <div class="mp-grid" id="mpGrid"></div>
        <p class="mp-empty hidden" id="mpEmpty">No maps yet. Start one above — draw over the real ground, measure it, and save the plan with a name.</p>
    </div>

    <div id="smapStageWrap" @unless ($openSaveQ) class="hidden" @endunless>
        {{-- One row for everything that is not a drawing tool: the way back,
             then history, saving, and the map's own switches. They used to sit
             in the map's toolbar, which on a phone pushed the drawing tools off
             the screen. Each one drives the real control inside the map. --}}
        <div class="mp-stagebar">
            <button type="button" class="btn btn-white btn-sm" id="mpBackHome">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                <span class="mp-backword">All maps</span>
            </button>
            <span class="mp-stagehint" id="mpStageHint"></span>
            <div class="mp-stageacts">
                <button type="button" class="mp-act" data-proxy="cmapUndo" title="Undo" aria-label="Undo">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h10a5 5 0 015 5v1m-15-6l4-4m-4 4l4 4"/></svg>
                </button>
                <button type="button" class="mp-act" data-proxy="cmapRedo" title="Redo" aria-label="Redo">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 10H11a5 5 0 00-5 5v1m15-6l-4-4m4 4l-4 4"/></svg>
                </button>
                {{-- The map's own Centre-on-me, driven from out here like the
                     rest. On this screen the map's toolbar is off looking after
                     the drawing tools, and "take me to where I am standing" is
                     the one thing a person in a field wants without hunting. --}}
                <button type="button" class="mp-act" data-proxy="cmapFindMe" title="Centre the map on me" aria-label="Centre the map on my position">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="3.25"/><circle cx="12" cy="12" r="8"/><path stroke-linecap="round" d="M12 1.5v2.5M12 20v2.5M1.5 12h2.5M20 12h2.5"/></svg>
                </button>
                <button type="button" class="mp-act is-save" data-proxy="cmapSaveMenuBtn" title="Open or save a map" aria-label="Open or save a map">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 5a2 2 0 012-2h8l4 4v12a2 2 0 01-2 2H7a2 2 0 01-2-2V5z"/><path stroke-linecap="round" stroke-linejoin="round" d="M8 3v5h6M8 14h8v6H8z"/></svg>
                    <span class="mp-actword">Save</span>
                </button>
            </div>
        </div>
        <div class="smap-stage">
            @include('sm.partials.schedule-map', ['schedule' => $schedule])
        </div>
    </div>

    <script>
        (() => {
            const SAVES_URL = @json(route('sm.map.saves')) + '?scheduleId=' + @json($schedule->id);
            const OPEN_SAVE = @json($openSaveQ);
            let saves = @json($saves ?? []);
            let liveCount = @json((int) ($liveCount ?? 0));
            const esc = window.escapeHtml || ((s) => String(s ?? '')
                .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;'));

            const home = document.getElementById('smapHome');
            const stageWrap = document.getElementById('smapStageWrap');
            const grid = document.getElementById('mpGrid');
            const empty = document.getElementById('mpEmpty');
            const hint = document.getElementById('mpStageHint');

            const MAP_ICON = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="M9 20l-5-2V6l5 2m0 12l6-2m-6 2V8m6 10l5 2V8l-5-2m0 12V6M9 8l6-2"/></svg>';
            const NEW_TILE = '<button type="button" class="mp-new" data-new>'
                + '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/></svg>'
                + '<span>New map</span></button>';

            /* The live canvas is not a file, but it is where unsaved work
               lives — so when it holds shapes it gets a card of its own. */
            function currentCard() {
                if (!liveCount) return '';
                return `<div class="mp-card" data-current>
                    <div class="mp-thumb">${MAP_ICON}</div>
                    <div class="mp-meta">
                        <span class="mp-name">The canvas</span>
                        <div class="mp-tags"><span class="badge badge-green">${liveCount} shape${liveCount === 1 ? '' : 's'}</span></div>
                        <span class="mp-when">Continue where the map was left</span>
                    </div>
                </div>`;
            }

            function card(sv) {
                // The thumb endpoint always tries: filed picture, else one
                // redrawn from the shapes. A failure just leaves the mark.
                const src = sv.thumbUrl || sv.imageUrl;
                const thumb = MAP_ICON + (src
                    ? `<img src="${esc(src)}" alt="" loading="lazy" onload="this.classList.add('is-loaded')" onerror="this.remove()">`
                    : '');
                return `<div class="mp-card" data-save="${sv.id}">
                    <div class="mp-thumb">${thumb}</div>
                    <div class="mp-meta">
                        <span class="mp-name">${esc(sv.title || 'Map')}</span>
                        <div class="mp-tags">
                            <span class="badge ${sv.source === 'team' ? 'badge-blue' : 'badge-green'}">${sv.source === 'team' ? 'Team map' : 'My map'}</span>
                            <span class="badge badge-gray">${sv.count} shape${sv.count === 1 ? '' : 's'}</span>
                            ${sv.noteHref ? `<a class="badge badge-gray mp-innote" href="${esc(sv.noteHref)}" title="Open the note this map filed its picture in">`
                                + '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" style="width:.7rem;height:.7rem"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.6L19 9.4V19a2 2 0 01-2 2z"/></svg>'
                                + 'In a note</a>' : ''}
                        </div>
                        <span class="mp-when">${esc(sv.by || '')}${sv.by && sv.when ? ' · ' : ''}${esc(sv.when || '')}</span>
                    </div>
                </div>`;
            }

            function paint() {
                grid.innerHTML = NEW_TILE + currentCard() + saves.map(card).join('');
                empty.classList.toggle('hidden', saves.length > 0 || liveCount > 0);
            }

            async function refresh() {
                try {
                    const r = await api(SAVES_URL);
                    saves = (r.data && r.data.saves) || [];
                    // The endpoint says how many shapes the live canvas holds
                    // NOW — a teammate may have drawn or cleared it meanwhile.
                    if (r.data && typeof r.data.liveCount === 'number') liveCount = r.data.liveCount;
                    paint();
                } catch (_) { /* the shelf keeps what it had */ }
            }

            /* ---------- entering and leaving the stage ---------- */
            const boot = () => {
                if (typeof window.initCollabMap === 'function') window.initCollabMap();
                else setTimeout(boot, 120);
            };
            const easeIn = (el) => {
                el.classList.remove('mp-view-in');
                void el.offsetWidth;
                el.classList.add('mp-view-in');
            };

            /* Whether the current stage visit came from a note's "View map"
               link — leaving it then returns to the note, not the shelf. */
            let arrivedByLink = false;
            function enterStage(ask) {
                home.classList.add('hidden');
                stageWrap.classList.remove('hidden');
                easeIn(stageWrap);
                // No claim about what the stage will show — the ask may still
                // be cancelled at its confirm, and the canvas stays as it was.
                hint.textContent = '';
                requestAnimationFrame(boot);
                if (ask === 'blank') window.cmapStartBlank?.();
                else if (typeof ask === 'number' && ask > 0) window.cmapOpenSaveById?.(ask);
                window.scrollTo({ top: 0, behavior: 'auto' });
            }

            function goHome() {
                stageWrap.classList.add('hidden');
                home.classList.remove('hidden');
                easeIn(home);
                // The visit may have drawn, cleared or saved — believe the map.
                if (typeof window.cmapShapeCount === 'function') liveCount = window.cmapShapeCount();
                paint();
                refresh();
            }

            /* ---- the stage bar drives the map's own controls -------------
               The buttons live out here so the map's toolbar keeps its width
               for drawing tools, but the controls themselves are still the
               map's. Each proxy clicks its original and wears its state. */
            (function wireStageActs() {
                const acts = Array.from(document.querySelectorAll('.mp-act[data-proxy]'));
                const realOf = (name) => document.getElementById(name);

                acts.forEach((btn) => {
                    btn.addEventListener('click', () => {
                        const real = realOf(btn.getAttribute('data-proxy'));
                        if (real) real.click();
                    });
                });

                // Undo/redo go dim with their originals; GPS and satellite
                // light up with theirs. Watched rather than guessed — the map
                // owns those states and changes them from a dozen places.
                const sync = () => acts.forEach((btn) => {
                    const real = realOf(btn.getAttribute('data-proxy'));
                    if (!real) return;
                    btn.classList.toggle('is-off', !!real.disabled);
                    // Hunting for a satellite is not the same as being refused,
                    // and the proxy is the only one of the pair on screen here,
                    // so it has to carry the difference itself.
                    btn.classList.toggle('is-busy', real.classList.contains('is-busy'));
                    if (!btn.classList.contains('is-save') && !btn.classList.contains('is-danger')) {
                        btn.classList.toggle('is-on', real.classList.contains('is-active'));
                    }
                });
                const watch = new MutationObserver(sync);
                ['cmapUndo', 'cmapRedo', 'cmapFindMe'].forEach((id) => {
                    const real = document.getElementById(id);
                    if (real) watch.observe(real, { attributes: true, attributeFilter: ['disabled', 'class'] });
                });
                sync();
            })();

            document.getElementById('mpBackHome').addEventListener('click', () => {
                // Sent here by a note's "View map" tag: leaving the map is
                // being done with the errand, so it hands you back to the
                // note rather than parking you on the shelf.
                if (arrivedByLink) {
                    arrivedByLink = false;
                    if (window.smReturnToOrigin?.()) return;
                }
                goHome();
            });
            grid.addEventListener('click', (e) => {
                if (e.target.closest('a[href]')) return;   // "In a note" goes where it says
                if (e.target.closest('[data-new]')) { enterStage('blank'); return; }
                if (e.target.closest('[data-current]')) { enterStage(null); return; }
                const c = e.target.closest('.mp-card[data-save]');
                if (c) enterStage(parseInt(c.getAttribute('data-save'), 10));
            });

            paint();
            if (OPEN_SAVE) { arrivedByLink = true; enterStage(OPEN_SAVE); }

            /* The shell keeps this pane alive between visits; when it shows
               again the shelf re-reads the saves so nothing new is missing.
               The very first show arrives right after the server rendered
               these exact rows — skip that one, it would only repeat them. */
            let firstShow = true;
            document.addEventListener('sm:module-shown', (e) => {
                if (!e.detail || e.detail.key !== 'maps') return;
                if (firstShow) { firstShow = false; return; }
                if (!home.classList.contains('hidden')) refresh();
            });

            /* Deep links while the pane is kept alive: the shell hands the
               query here instead of re-fetching the pane — a re-injection
               would orphan the already-booted Google map. */
            (window.smModuleDeepLink = window.smModuleDeepLink || {}).maps = (q) => {
                const id = parseInt(new URLSearchParams(q).get('save') || '', 10);
                if (Number.isFinite(id) && id > 0) { arrivedByLink = true; enterStage(id); }
            };

            /* Fill whatever is left of the screen, measured rather than
               guessed: the header, the tab bar and the browser's own chrome
               differ per device, and a hard calc() left a band of dead page
               under the map on some and clipped it on others. */
            const stage = document.querySelector('.smap-stage');
            const GAP = 8;   // the same thin frame the sides get
            function fit() {
                if (!stage || !window.matchMedia('(max-width: 767px)').matches) return;
                if (stage.getBoundingClientRect().height === 0) return;   // stage is off — nothing to size
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
            let stageWasShown = false;
            function chrome() {
                const shown = !!stage && stage.getBoundingClientRect().height > 0;
                // Coming back from display:none at an unchanged height, fit()'s
                // no-change guard would skip the kick Google needs to repaint.
                if (shown && !stageWasShown && window.google?.maps?.event) {
                    window.google.maps.event.trigger(window, 'resize');
                }
                stageWasShown = shown;
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
