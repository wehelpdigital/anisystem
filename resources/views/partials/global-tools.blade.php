{{-- ================================================================
     Global and Quick Tools — the cross-season doors (notes, analyses,
     gallery, contacts) and the quick add tools, folded behind one
     remembered heading. Lived on the schedules page; lives on the
     dashboard now. Expects: $allSchedules (id, title) for the quick
     tools' schedule picker. Brings its own CSS, sheets and fold JS.
     ================================================================ --}}
@once
@push('head')
<style>
        /* ---- the three quick doors, one to a row ------------------------
           Shaped like the Hub's module tiles, because they do the same job:
           a tinted icon you recognise before you read, a name, and a line
           saying why you would tap it. Across a phone in a single strip
           there was room for none of that. ---- */
        /* --- Global and Quick Tools: one heading over the four doors ---
           Folds with a grid row rather than max-height, so the panel is
           exactly as tall as what is in it and the animation has a real end
           to reach. */
        .qa-panel { border: 1px solid var(--color-gray-200); border-radius: 1rem;
            background: var(--color-white); overflow: hidden; }
        .qa-panel-head { display: flex; align-items: center; gap: .7rem; width: 100%;
            text-align: left; padding: .7rem .8rem; cursor: pointer; background: none; border: 0; }
        .qa-panel-head:hover { background: var(--color-gray-50); }
        .qa-panel-ico { width: 2.4rem; height: 2.4rem; border-radius: .7rem; flex: none;
            display: inline-flex; align-items: center; justify-content: center;
            background: var(--color-brand-50); color: var(--color-brand-700); }
        .qa-panel-ico svg { width: 1.25rem; height: 1.25rem; }
        .qa-panel-txt { min-width: 0; flex: 1 1 auto; }
        .qa-panel-txt b { display: block; font-size: .875rem; font-weight: 700; color: var(--color-gray-900); }
        .qa-panel-txt i { display: block; font-style: normal; font-size: .75rem; color: var(--color-gray-500);
            overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .qa-panel-chev { width: 1.1rem; height: 1.1rem; flex: none; color: var(--color-gray-400);
            transition: transform .28s cubic-bezier(.22,1,.36,1); }
        .qa-panel.is-folded .qa-panel-chev { transform: rotate(-90deg); }
        .qa-panel-fold { overflow: hidden;
            transition: max-height .28s cubic-bezier(.22,1,.36,1); }
        .qa-panel.is-folded .qa-panel-fold { max-height: 0; }
        .qa-panel-fold > div { min-height: 0; }
        .qa-panel .qa-stack { padding: 0 .55rem .55rem; }
        /* Inside the panel the tiles are rows of a list, not cards on a page. */
        .qa-panel .qa-tile { border-color: var(--color-gray-100); }
        @media (prefers-reduced-motion: reduce) {
            .qa-panel-fold, .qa-panel-chev { transition: none; }
        }

        .qa-stack { display: grid; gap: .5rem; }
        .qa-tile { display: flex; align-items: center; gap: .7rem; width: 100%; text-align: left;
            padding: .7rem .8rem; border-radius: .9rem; cursor: pointer; text-decoration: none;
            background: var(--color-white); border: 1px solid var(--color-gray-200);
            transition: transform .28s cubic-bezier(.22,1,.36,1), box-shadow .28s cubic-bezier(.22,1,.36,1),
                border-color .28s cubic-bezier(.22,1,.36,1); }
        .qa-tile:hover { transform: translateY(-1px); box-shadow: 0 10px 24px -16px rgb(0 0 0 / .5); }
        .qa-tile .qa-ico { width: 2.6rem; height: 2.6rem; border-radius: .75rem; flex: none;
            display: inline-flex; align-items: center; justify-content: center; }
        .qa-tile .qa-ico svg { width: 1.3rem; height: 1.3rem; }
        .qa-tile .qa-txt { display: flex; flex-direction: column; gap: .1rem; min-width: 0; flex: 1 1 auto; }
        .qa-tile .qa-txt b { font-size: .88rem; font-weight: 800; color: var(--color-gray-900); line-height: 1.25; }
        .qa-tile .qa-txt i { font-style: normal; font-size: .72rem; font-weight: 500; line-height: 1.4;
            color: var(--color-gray-500); }
        .qa-go { width: .95rem; height: .95rem; flex: none; color: var(--color-gray-300);
            transition: transform .28s cubic-bezier(.22,1,.36,1), color .28s cubic-bezier(.22,1,.36,1); }
        .qa-tile:hover .qa-go { transform: translateX(2px); }
        .qa-notes:hover { border-color: #f0dcae; } .qa-notes:hover .qa-go { color: #b45309; }
        .qa-gallery:hover { border-color: #c7dbf5; } .qa-gallery:hover .qa-go { color: #1d4ed8; }
        .qa-cap:hover { border-color: #cfe3b8; } .qa-cap:hover .qa-go { color: #3d6823; }
        .qa-rec:hover { border-color: #f3c4c4; } .qa-rec:hover .qa-go { color: #b91c1c; }
        html.dark .qa-tile { background: #151b12; border-color: #2b3a1c; }
        html.dark .qa-tile .qa-txt b { color: #e8efe1; }
        html.dark .qa-tile .qa-txt i { color: #93a684; }
        @media (prefers-reduced-motion: reduce) { .qa-tile, .qa-go { transition: none; } }

        /* The hues the three doors are told apart by, and the icon plate
           they sit on. Kept out of .qa-tile so the colour of a door and the
           shape of one stay separate things. */
        .qa-ico { border-radius: .75rem; }
        .qa-notes .qa-ico { background: #fdf6e6; color: #b45309; }
        .qa-gallery .qa-ico { background: #eaf1fd; color: #1d4ed8; }
        .qa-cap .qa-ico { background: #eef6e6; color: #3d6823; }
        .qa-wtp .qa-ico { background: #eef2fd; color: #4c65e0; }
        .qa-wtp:hover { border-color: #c7d2f5; } .qa-wtp:hover .qa-go { color: #4c65e0; }
        html.dark .qa-wtp .qa-ico { background: rgb(76 101 224 / .22); color: #a5b6f2; }
        .qa-rec .qa-ico { background: #fdecec; color: #b91c1c; }
        html.dark .qa-notes .qa-ico { background: rgb(180 83 9 / .18); color: #e0b457; }
        html.dark .qa-gallery .qa-ico { background: rgb(29 78 216 / .22); color: #9fc0f5; }
        html.dark .qa-cap .qa-ico { background: rgb(61 104 35 / .25); color: #a5c97e; }
        html.dark .qa-rec .qa-ico { background: rgb(185 28 28 / .2); color: #f0a3a3; }
</style>
@endpush
@endonce

        {{-- Three doors, one to a row. They were a squeezed strip of three
             across a phone, where every word fell to an ellipsis and none of
             them said what the thing was for. Given a row each they can be
             what the Hub's tiles are: an icon you recognise, a name, and a
             line saying why you would tap it. --}}
        {{-- Folded behind one heading: this page is a list of seasons, and
             four full-width doors above it pushed the list off a phone
             screen. The choice is remembered per farm, because whether these
             are useful depends on how somebody works, not on which visit it
             is. --}}
        <section class="qa-panel" id="globalTools">
            <button type="button" class="qa-panel-head" id="globalToolsHead" aria-expanded="true" aria-controls="globalToolsBody">
                <span class="qa-panel-ico"><img src="{{ asset('images/time.png') }}" alt="" style="width:1.5rem;height:1.5rem;object-fit:contain"></span>
                <span class="qa-panel-txt">
                    <b>Global and Quick Tools</b>
                    <i>Notes and pictures across every season, and the two ways to add one now.</i>
                </span>
                <svg class="qa-panel-chev" fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
            </button>
            <div class="qa-panel-fold" id="globalToolsBody">
              <div>
        <div class="qa-stack">
            <a href="{{ route('notes.hub') }}" class="qa-tile qa-notes">
                <span class="qa-ico"><img src="{{ asset('images/sticky-note.png') }}" alt="" style="width:1.4rem;height:1.4rem;object-fit:contain"></span>
                <span class="qa-txt">
                    <b>Global Notes</b>
                    <i>Every note from every schedule, gathered in one place.</i>
                </span>
                <svg class="qa-go" fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
            </a>
            {{-- Its twin: the notes hub gathers the words, this gathers the
                 pictures. Looking for a photo is remembering a picture, not a
                 season, so it does not ask which one first. --}}
            {{-- On Libre these two stay in the row, wearing a lock where the
                 chevron sat — the tap opens the upgrade sheet, not the page. --}}
            @php $qWtpLocked = ! \App\Support\Tier::can('reportsAll'); @endphp
            <a href="{{ route('wtp.page') }}" class="qa-tile qa-wtp"
               @if ($qWtpLocked) data-tier-lock="solo" data-lock-say="The When to Plant analysis comes with the Solo Farmer plan — Anee reads your town's climate and ENSO outlook to name your safest planting window." @endif>
                <span class="qa-ico"><img src="{{ asset('images/appointment.png') }}" alt="" style="width:1.4rem;height:1.4rem;object-fit:contain"></span>
                <span class="qa-txt {{ $qWtpLocked ? 'tl-dim' : '' }}">
                    <b>When to Plant Analysis</b>
                    <i>The best planting window for your crop and place, argued from the climate. Uses AI credits.</i>
                </span>
                @if ($qWtpLocked)
                    <span class="tl-lock"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="4" y="10.5" width="16" height="10" rx="2.5"/><path d="M8 10.5V7a4 4 0 0 1 8 0v3.5"/></svg></span>
                @else
                    <svg class="qa-go" fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                @endif
            </a>
            <a href="{{ route('whatp.page') }}" class="qa-tile qa-wtp"
               @if ($qWtpLocked) data-tier-lock="solo" data-lock-say="The What to Plant analysis comes with the Solo Farmer plan — Anee weighs your location, season forecast and soil to recommend the crop." @endif>
                <span class="qa-ico"><img src="{{ asset('images/plant.png') }}" alt="" style="width:1.4rem;height:1.4rem;object-fit:contain"></span>
                <span class="qa-txt {{ $qWtpLocked ? 'tl-dim' : '' }}">
                    <b>What to Plant Analysis</b>
                    <i>The crops your ground argues for — soil, water and season weighed, ranked by fit. Uses AI credits.</i>
                </span>
                @if ($qWtpLocked)
                    <span class="tl-lock"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="4" y="10.5" width="16" height="10" rx="2.5"/><path d="M8 10.5V7a4 4 0 0 1 8 0v3.5"/></svg></span>
                @else
                    <svg class="qa-go" fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                @endif
            </a>
            <a href="{{ route('gallery.hub') }}" class="qa-tile qa-gallery">
                <span class="qa-ico"><img src="{{ asset('images/gallery.png') }}" alt="" style="width:1.4rem;height:1.4rem;object-fit:contain"></span>
                <span class="qa-txt">
                    <b>Global Gallery</b>
                    <i>Every photo, drawing and saved map, from every schedule.</i>
                </span>
                <svg class="qa-go" fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
            </a>
            {{-- The farm's phonebook — every tier, no locks. Workers, tractor
                 rentals, harvesters, buyers: tagged, searchable, one tap away. --}}
            <a href="{{ route('contacts.page') }}" class="qa-tile qa-contacts">
                <span class="qa-ico"><img src="{{ asset('images/list.png') }}" alt="" style="width:1.4rem;height:1.4rem;object-fit:contain"></span>
                <span class="qa-txt">
                    <b>Contact List</b>
                    <i>Your farm's phonebook — workers, tractor rentals, harvesters, buyers, all tagged and one tap from a call.</i>
                </span>
                <svg class="qa-go" fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
            </a>
            @php
                /* The camera and the recorder are modules the owner grants,
                   and the Hub has always drawn these two tiles only for
                   somebody who holds them. This list did not, so a worker
                   with no recorder still had a Quick Record button here — a
                   door with nothing behind it. */
                $qMayCamera = \App\Support\WorkerContext::canUseModule('camera');
                $qMayVideo = \App\Support\WorkerContext::canUseModule('video');
            @endphp
            @if ($allSchedules->isNotEmpty() && $qMayCamera)
                <button type="button" id="quickCaptureBtn" class="qa-tile qa-cap">
                    <span class="qa-ico"><img src="{{ asset('images/camera.png') }}" alt="" style="width:1.4rem;height:1.4rem;object-fit:contain"></span>
                    <span class="qa-txt">
                        <b>Quick Capture</b>
                        <i>Photograph what you are standing in front of and file it now.</i>
                    </span>
                    <svg class="qa-go" fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                </button>
            @endif
            @php
                /* The tier's own wall on video, judged for an OWNER acting as
                   themselves — a worker rides the schedule owner's plan, so
                   their doors are the grant's business, not this lock's. */
                $qVidLocked = \App\Support\WorkerContext::effectiveOwnerId() === (int) auth()->id()
                    && ! \App\Support\Tier::can('videoRecording');
            @endphp
            @if ($allSchedules->isNotEmpty() && $qMayVideo)
                <button type="button" id="quickRecordBtn" class="qa-tile qa-rec"
                        @if ($qVidLocked) data-tier-lock="solo" data-lock-say="Video recording comes with the Solo Farmer plan. Photos and voice notes are yours on Libre." @endif>
                    <span class="qa-ico"><img src="{{ asset('images/video-camera-b.png') }}" alt="" style="width:1.4rem;height:1.4rem;object-fit:contain"></span>
                    <span class="qa-txt {{ $qVidLocked ? 'tl-dim' : '' }}">
                        <b>Quick Record</b>
                        <i>Record a video if a picture is not enough, explain your observations while recording.</i>
                    </span>
                    @if ($qVidLocked)
                        <span class="tl-lock"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="4" y="10.5" width="16" height="10" rx="2.5"/><path d="M8 10.5V7a4 4 0 0 1 8 0v3.5"/></svg></span>
                    @else
                        <svg class="qa-go" fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                    @endif
                </button>
            @endif
            {{-- The microphone has its own switch. This tile used to live
                 inside the video block above, so it came and went with a
                 permission that was never about speaking. --}}
            @if ($allSchedules->isNotEmpty() && \App\Support\WorkerContext::canUseModule('voice'))
                <button type="button" id="quickVoiceBtn" class="qa-tile qa-rec">
                    <span class="qa-ico"><img src="{{ asset('images/voice-recorder.png') }}" alt="" style="width:1.4rem;height:1.4rem;object-fit:contain"></span>
                    <span class="qa-txt">
                        <b>Quick Voice</b>
                        <i>Say what you are seeing and file it as a note, faster than typing in the field.</i>
                    </span>
                    <svg class="qa-go" fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                </button>
            @endif
        </div>
              </div>
            </div>
        </section>

    @if (\App\Support\WorkerContext::canUseModule('camera'))
        @include('sm.partials.quick-capture', ['allSchedules' => $allSchedules])
    @endif
    @if (\App\Support\WorkerContext::canUseModule('video'))
        @include('sm.partials.quick-record', ['allSchedules' => $allSchedules])
    @endif
    @if (\App\Support\WorkerContext::canUseModule('voice'))
        @include('sm.partials.quick-voice', ['allSchedules' => $allSchedules])
    @endif
    {{-- Quick Record borrows the shared recorder, so the panel needs it. --}}
    @include('community.partials.video-js')

    {{-- Whether the tools panel is open. Kept per farm beside the folds this
         page already remembers, so a worker standing in somebody else's farm
         does not inherit the owner's choice. --}}
    <script>
    (function globalToolsFold() {
        const panel = document.getElementById('globalTools');
        const head = document.getElementById('globalToolsHead');
        if (!panel || !head) return;

        const KEY = 'smToolsFolded:' + @json(\App\Support\WorkerContext::effectiveOwnerId());
        const paint = (folded) => {
            panel.classList.toggle('is-folded', folded);
            head.setAttribute('aria-expanded', folded ? 'false' : 'true');
        };

        // Painted before the first frame where possible; the class only
        // changes a grid row, so there is nothing to flash.
        let folded = false;
        try { folded = localStorage.getItem(KEY) === '1'; } catch (_) {}
        paint(folded);

        head.addEventListener('click', () => {
            folded = !panel.classList.contains('is-folded');
            paint(folded);
            try { localStorage.setItem(KEY, folded ? '1' : '0'); } catch (_) {}
        });
    })();
    </script>
