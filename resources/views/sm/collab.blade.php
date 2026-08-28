@extends('layouts.app')

@section('title', 'Collab Room — ' . $schedule->title)
@section('page-title', 'Collab Room')
@section('page-subtitle', $schedule->title)
@section('help-key', 'collab')
@section('back', route('sm.hub', ['id' => $schedule->id]))

{{-- The room is a workspace, not a page you scroll: on a phone the bottom tab
     bar covers the drawing surface and chat composer, so drop it here and give
     the panels that strip back. Navigation is still one tap away via Back. --}}
@section('body-class', 'hide-tabbar no-footer')

@section('content')
<div class="collab-wrap" id="collabRoom" data-schedule="{{ $schedule->id }}" data-owner="{{ (int) $schedule->anisystemUserId }}">

    {{-- Members live in the page header as a stack of faces (moved there by JS
         — Blade cannot reach into the layout's header). The strip they used to
         occupy here goes back to the tabs, which need every vertical pixel on
         a phone. Tapping the stack opens the full list. --}}
    <button type="button" id="collabMembersPill" class="collab-mems-pill" hidden
            aria-label="Show room members" title="Room members">
        @foreach ($members->take(4) as $m)
            <span class="collab-pill-face {{ $m->isOnline() ? 'on' : '' }}" data-uid="{{ $m->id }}">
                @if ($m->avatarPath)<img src="{{ \App\Support\MediaStore::url($m->avatarPath) }}" alt="">@else{{ $m->initials }}@endif
                <span class="collab-mem-dot"></span>
            </span>
        @endforeach
        @if ($members->count() > 4)<span class="collab-pill-more">+{{ $members->count() - 4 }}</span>@endif
    </button>

    {{-- Full member list, opened from the pill. --}}
    <div class="sheet hidden" id="collabMembersSheet" style="--sheet-width:24rem">
        <div class="sheet-handle"></div>
        <div class="sheet-header">
            <h3 class="sheet-title">In this room</h3>
            <button type="button" class="icon-btn" data-sheet-close aria-label="Close">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div class="sheet-body" id="collabPresence">
            {{-- A teammate's row opens their PM in the Chat tab. The chat
                 panel's own member strip is hidden while it is docked in this
                 room (the header already shows those faces), so this list is
                 where a private word starts. --}}
            @foreach ($members as $m)
                @php $isSelf = (int) $m->id === (int) auth()->id(); @endphp
                <div class="collab-mem {{ $m->isOnline() ? 'on' : '' }}{{ $isSelf ? '' : ' pmable' }}" data-uid="{{ $m->id }}"
                     @unless ($isSelf) data-pm-name="{{ $m->full_name }}" role="button" tabindex="0" title="Message {{ $m->full_name }} privately" @endunless>
                    <span class="collab-mem-face">
                        @if ($m->avatarPath)<img src="{{ \App\Support\MediaStore::url($m->avatarPath) }}" alt="">@else{{ $m->initials }}@endif
                        <span class="collab-mem-dot"></span>
                    </span>
                    <span class="collab-mem-name">{{ $m->full_name }}{{ (int) $m->id === (int) $schedule->anisystemUserId ? ' · owner' : '' }}</span>
                    <span class="collab-mem-state">{{ $m->isOnline() ? 'online' : 'offline' }}</span>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Tabs --}}
    <div class="collab-tabs" role="tablist">
        <button type="button" class="collab-tab is-active" data-tab="chat" role="tab" aria-selected="true">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12a8 8 0 01-11.6 7.1L3 20l1-5.5A8 8 0 1121 12z"/></svg>
            <span>Chat</span>
        </button>
        <button type="button" class="collab-tab" data-tab="drawing" role="tab" aria-selected="false">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536M9 11l6-6 3 3-6 6H9v-3z"/><path stroke-linecap="round" stroke-linejoin="round" d="M4 20h16"/></svg>
            <span>Drawing</span>
        </button>
        {{-- A photo everyone draws on together — distinct from Drawing, whose
             sheet is blank on purpose. Here the picture IS the subject. --}}
        <button type="button" class="collab-tab" data-tab="photo" role="tab" aria-selected="false">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 7a2 2 0 012-2h12a2 2 0 012 2v10a2 2 0 01-2 2H6a2 2 0 01-2-2V7z"/><path stroke-linecap="round" stroke-linejoin="round" d="M4 15l4-4 4 4 3-3 5 5"/><circle cx="9" cy="9" r="1.2"/></svg>
            <span>Photo</span>
        </button>
        {{-- Cameras and positions are their own tabs rather than corners of
             the call, because they are used while other things are happening:
             somebody points a phone at a bund while the chat argues about it. --}}
        <button type="button" class="collab-tab" data-tab="camera" role="tab" aria-selected="false">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10l4.55-2.28A1 1 0 0121 8.62v6.76a1 1 0 01-1.45.9L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
            <span>Cameras</span>
        </button>
        <button type="button" class="collab-tab" data-tab="location" role="tab" aria-selected="false">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a2 2 0 01-2.828 0l-4.243-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            <span>Where we are</span>
        </button>
        <button type="button" class="collab-tab" data-tab="map" role="tab" aria-selected="false">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>
            <span>Map</span>
        </button>
        <button type="button" class="collab-tab" data-tab="activities" role="tab" aria-selected="false">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
            <span>Activities</span>
        </button>
        {{-- Her face, not a robot head. Every other surface in the app now
             shows the person you are about to talk to, and a tab that draws a
             generic machine beside seven drawn icons reads as a different
             feature from the one it opens. --}}
        <button type="button" class="collab-tab" data-tab="ai" role="tab" aria-selected="false">
            <img class="collab-tab-face" src="{{ \App\Models\AiSetting::current()?->faceUrl() }}" alt="">
            <span>{{ \App\Models\AiSetting::current()?->assistantName ?? 'Anee' }}</span>
        </button>
    </div>

    {{-- Panels (each fills the area; only the active one shows). --}}
    <div class="collab-panels">
        <div class="collab-panel is-active" data-panel="chat" id="collabChat"></div>
        <div class="collab-panel" data-panel="drawing" id="collabDrawing"></div>
        <div class="collab-panel" data-panel="photo" id="collabPhoto">@include('sm.partials.schedule-photo', ['schedule' => $schedule])</div>
        <div class="collab-panel" data-panel="camera" id="collabCamera">@include('sm.partials.collab-camera', ['schedule' => $schedule, 'isOwner' => $isOwner])</div>
        <div class="collab-panel" data-panel="location" id="collabLocation">@include('sm.partials.collab-location', ['schedule' => $schedule])</div>
        <div class="collab-panel" data-panel="map" id="collabMap">@include("sm.partials.schedule-map", ["schedule" => $schedule])</div>
        <div class="collab-panel" data-panel="activities" id="collabActivities">
            {{-- The tab says its own name, as the chat and the whiteboard do.
                 Here it lives outside the frame: the page inside is the whole
                 Activities module and has a head of its own. --}}
            <div class="collab-tabhead"><span class="collab-tabtitle">✅ Team tasks</span></div>
            {{-- Eager (no lazy) so it preloads in the background the moment the
                 Collab Room opens — switching to Activities is then instant. --}}
            <iframe id="collabActivitiesFrame" title="Activities" loading="eager"
                    src="{{ route('sm.activities', ['id' => $schedule->id, 'embed' => 1]) }}"></iframe>
        </div>
        <div class="collab-panel" data-panel="ai" id="collabAi">@include('sm.partials.schedule-ai-tab', ['schedule' => $schedule])</div>

        {{-- Shown while a tab's content loads (the Activities iframe especially). --}}
        <div class="collab-loader" id="collabLoader" aria-hidden="true">
            @include('sm.partials.wait-card')
            {{-- The tab's own name still goes somewhere: the card says what
                 the farm is doing, this says which room you are opening. --}}
            <span class="collab-loader-text" id="collabLoaderText"></span>
        </div>
    </div>
</div>

{{-- Reused realtime widgets: the group chat docks into the Chat tab; the
     whiteboard mounts inline into the Drawing tab. --}}
@include('sm.partials.schedule-chat-float', ['schedule' => $schedule])
@include('sm.partials.schedule-board', ['schedule' => $schedule])
{{-- Persistent call widget: lives at the room root so a call survives tab switches. --}}
@include('sm.partials.schedule-call', ['schedule' => $schedule])
{{-- What to call a recording, asked the moment it stops. --}}
@include('sm.partials.recording-save')
@endsection

@push('head')
<style>
    .collab-wrap { display: flex; flex-direction: column; gap: .5rem; height: calc(100dvh - 8.5rem); min-height: min(26rem, 60dvh); }
    /* 7rem allowed for the header plus the tab bar; with the bar gone (see the
       hide-tabbar body class above) 5rem lands the panel exactly on the bottom
       edge — measured at 390x780, the surface gains 32px and stops being
       clipped by the bar it used to sit under. */
    @media (max-width: 767px) { .collab-wrap { height: calc(100dvh - 5rem); } }

    /* Face stack in the page header: overlapped circles, newest under. */
    .collab-mems-pill { display: inline-flex; align-items: center; flex-shrink: 0; padding: .2rem .3rem; border-radius: 999px; }
    .collab-mems-pill:active { transform: scale(.95); }
    .collab-pill-face { position: relative; width: 1.75rem; height: 1.75rem; border-radius: 999px; display: inline-flex; align-items: center; justify-content: center; background: var(--color-brand-50); color: var(--color-brand-700); font-size: .6rem; font-weight: 800; border: 2px solid var(--color-white); overflow: visible; }
    .collab-pill-face + .collab-pill-face, .collab-pill-face + .collab-pill-more { margin-left: -.5rem; }
    .collab-pill-face img { width: 100%; height: 100%; object-fit: cover; border-radius: inherit; }
    .collab-pill-more { position: relative; width: 1.75rem; height: 1.75rem; border-radius: 999px; display: inline-flex; align-items: center; justify-content: center; background: var(--color-gray-100); color: var(--color-gray-600); font-size: .62rem; font-weight: 800; border: 2px solid var(--color-white); }

    /* Member rows inside the sheet. */
    .collab-mem { display: flex; align-items: center; gap: .65rem; padding: .5rem .25rem; }
    .collab-mem + .collab-mem { border-top: 1px solid var(--color-gray-100); }
    .collab-mem-face { position: relative; width: 2.2rem; height: 2.2rem; border-radius: 999px; display: flex; align-items: center; justify-content: center; background: var(--color-brand-50); color: var(--color-brand-700); font-size: .72rem; font-weight: 800; flex-shrink: 0; }
    .collab-mem-face img { width: 100%; height: 100%; object-fit: cover; border-radius: inherit; }
    .collab-mem-dot { position: absolute; right: -1px; bottom: -1px; width: .62rem; height: .62rem; border-radius: 999px; border: 2px solid var(--color-white); background: var(--color-gray-300); }
    .collab-mem.on .collab-mem-dot, .collab-pill-face.on .collab-mem-dot { background: #22c55e; }
    .collab-mem-name { font-size: .85rem; font-weight: 700; color: var(--color-gray-800); min-width: 0; flex: 1 1 auto; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .collab-mem-state { font-size: .68rem; font-weight: 800; color: var(--color-gray-400); text-transform: uppercase; letter-spacing: .04em; }
    .collab-mem.on .collab-mem-state { color: #16a34a; }
    /* Teammate rows open a PM (see the sheet-body note) — say so on hover. */
    .collab-mem.pmable { cursor: pointer; border-radius: .55rem; transition: background .28s cubic-bezier(.22,1,.36,1); }
    .collab-mem.pmable:hover, .collab-mem.pmable:focus-visible { background: var(--color-gray-50); }
    .collab-mem.pmable:hover .collab-mem-face { box-shadow: 0 0 0 2px var(--color-brand-500); }
    @media (prefers-reduced-motion: reduce) { .collab-mem.pmable { transition: none; } }

    /* Phones: the room takes the whole width — the page gutters were dead
       space beside a full-height workspace. */
    /* The page itself never scrolls in the room — a stray scrollbar from the
       (hidden) footer slack broke the workspace feel. Panels scroll inside. */
    body.is-collab { overflow: hidden; height: 100dvh; }
    @media (max-width: 767px) {
        body.is-collab main { padding: .35rem 0 0; }
        body.is-collab .collab-panels { border-radius: 0; border-left: 0; border-right: 0; }
        body.is-collab .collab-tabs { margin: 0; border-radius: 0; }
        body.is-collab .collab-wrap { gap: .35rem; height: calc(100dvh - 4.35rem); }
    }

    .collab-tabs { display: flex; gap: .25rem; padding: .25rem; background: var(--color-gray-100); border-radius: .8rem; flex-shrink: 0; overflow-x: auto; scrollbar-width: none; }
    .collab-tabs::-webkit-scrollbar { display: none; }
    .collab-tab-face { width: 1.15rem; height: 1.15rem; border-radius: 999px;
        object-fit: cover; flex-shrink: 0; }
    .collab-tab { flex: 1 1 auto; display: inline-flex; align-items: center; justify-content: center; gap: .4rem; padding: .55rem .6rem; border-radius: .6rem; font-size: .9rem; font-weight: 700; color: var(--color-gray-500); white-space: nowrap; transition: background .15s ease, color .15s ease; }
    .collab-tab:hover { color: var(--color-gray-800); }
    .collab-tab.is-active { background: var(--color-white); color: var(--color-gray-900); box-shadow: 0 1px 3px rgb(0 0 0 / .1); }
    @media (max-width: 767px) { .collab-tab span { display: none; } .collab-tab { flex: 1 1 0; } }

    .collab-panels { flex: 1 1 auto; position: relative; min-height: 0; background: var(--color-white); border: 1px solid var(--color-gray-200); border-radius: 1rem; overflow: hidden; }
    /* Panels stay rendered and only swap visibility — display:none threw
       away scroll positions, so the activities tab greeted every return
       scrolled back to the top. */
    .collab-panel { position: absolute; inset: 0; visibility: hidden; pointer-events: none; }
    .collab-panel.is-active { visibility: visible; pointer-events: auto; }
    #collabActivitiesFrame { width: 100%; height: 100%; border: 0; display: block; }
    /* Every tab says its own name.
     *
     * Three of them already did — the chat, the whiteboard and the AI carry
     * a title in their own bars — and the rest opened straight onto their
     * controls, so a reader who tapped the wrong tab had nothing telling
     * them where they had landed. This is the same line in the same place
     * for the five that were missing it. */
    .collab-tabhead { display: flex; align-items: center; gap: .5rem;
        padding: .55rem .75rem .35rem; flex: none; }
    .collab-tabtitle { font-size: .85rem; font-weight: 800; color: var(--color-gray-800); }
    html.dark .collab-tabtitle { color: #e8efe1; }
    /* The tasks tab is a frame that fills its panel, so the title needs the
       panel to be a column or the frame would sit on top of it. */
    #collabActivities { display: flex; flex-direction: column; }
    #collabActivities #collabActivitiesFrame { flex: 1 1 auto; height: auto; }
    /* Chat + AI panels host a column layout; drawing hosts the canvas board. */
    #collabChat, #collabAi, #collabDrawing, #collabMap { display: flex; flex-direction: column; }
    /* Chat/whiteboard are tabs here, not floats — hide the floating launcher. */
    body.is-collab #teamChat .team-fab { display: none !important; }

    /* Per-tab loading overlay (fades per the house easing). */
    .collab-loader { position: absolute; inset: 0; z-index: 30; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: .75rem; background: var(--color-white); opacity: 0; visibility: hidden; transition: opacity .28s cubic-bezier(.22, 1, .36, 1), visibility .28s cubic-bezier(.22, 1, .36, 1); }
    .collab-loader.on { opacity: 1; visibility: visible; }
    .collab-spin { width: 2.4rem; height: 2.4rem; border-radius: 999px; border: 3px solid var(--color-gray-200); border-top-color: var(--color-brand-600); animation: collab-spin .7s linear infinite; }
    .collab-loader-text { font-size: .8rem; font-weight: 700; color: var(--color-gray-500); }
    @keyframes collab-spin { to { transform: rotate(360deg); } }
    @media (prefers-reduced-motion: reduce) {
        .collab-loader { transition: none; }
        .collab-spin { animation: collab-pulse 1.3s ease-in-out infinite; }
        @keyframes collab-pulse { 0%, 100% { opacity: .35; } 50% { opacity: 1; } }
    }
</style>
@endpush

@push('scripts')
<script>
(() => {
    const room = document.getElementById('collabRoom');
    if (!room) return;
    const tabs = room.querySelectorAll('.collab-tab');
    const panels = room.querySelectorAll('.collab-panel');
    let current = 'chat';

    document.body.classList.add('is-collab');

    // The member stack belongs beside the page title, but Blade sections
    // cannot reach the layout's header — so it renders in the content and is
    // walked up there here. Tapping it opens the full list.
    const pill = document.getElementById('collabMembersPill');
    const titleEl = document.getElementById('appPageTitle');
    if (pill && titleEl) {
        const titleWrap = titleEl.closest('div');
        titleWrap?.parentElement?.insertBefore(pill, titleWrap.nextSibling);
        pill.hidden = false;
        pill.addEventListener('click', () => window.openSheet?.('collabMembersSheet'));
    }

    /* ---------- per-tab loading overlay ---------- */
    /* Every tab must appear here AND get a branch in the collab:show handler
       below that calls markReady(). The loader is raised for whatever tab is
       opened, and only markReady() lowers it — so a tab with no branch shows
       a spinner over its own working content, for ever. That is exactly what
       Cameras and Where-we-are did when they were added. */
    const LABELS = {
        chat: 'Loading…', drawing: 'Loading…', map: 'Loading the map…',
        activities: 'Loading…', ai: 'Loading…',
        camera: 'Starting the cameras…', location: 'Loading the map…',
        photo: 'Loading the photo…',
    };
    const loader = document.getElementById('collabLoader');
    const loaderText = document.getElementById('collabLoaderText');
    const frame = document.getElementById('collabActivitiesFrame');
    const ready = new Set();
    let frameReady = false, loaderShownAt = 0;

    function showLoader(text) {
        loaderText.textContent = text || 'Loading…';
        loader.classList.add('on');
        loader.setAttribute('aria-hidden', 'false');
        loaderShownAt = performance.now();
        // A different reminder each time the room waits.
        window.rollWaitLine?.(loader.querySelector('.bv-card'));
    }
    function hideLoader() { loader.classList.remove('on'); loader.setAttribute('aria-hidden', 'true'); }
    // Mark a tab loaded; keep the loader up for a short minimum so it doesn't flash.
    function markReady(tab) {
        ready.add(tab);
        if (current !== tab) return;
        /* Was 280ms, which is enough not to flicker and not enough to read.
         * The shared floor is a full second, and half a second more when the
         * tab was ready almost before it was asked for. */
        if (window.waitCardRelease) window.waitCardRelease(loader, hideLoader);
        else setTimeout(hideLoader, Math.max(0, 280 - (performance.now() - loaderShownAt)));
    }
    const rafReady = (tab) => requestAnimationFrame(() => markReady(tab));
    function frameIsLoaded() {
        try { return !!(frame && frame.contentWindow && frame.contentWindow.document && frame.contentWindow.document.readyState === 'complete'); }
        catch (_) { return frameReady; }
    }
    // The embedded activities page posts this once it has actually painted, which is
    // later (and more accurate) than the iframe's 'load' event — see activities.blade.php.
    window.addEventListener('message', (ev) => {
        if (frame && ev.source === frame.contentWindow && ev.data && ev.data.type === 'collab:activities-ready') {
            frameReady = true; markReady('activities');
        }
    });

    // Mount the reused realtime widgets into their tabs on first show; drive the loader.
    let chatDocked = false;
    document.addEventListener('collab:show', (e) => {
        const tab = e.detail && e.detail.tab;
        if (!tab) return;

        // Show the loader unless this tab is already loaded.
        if (ready.has(tab)) hideLoader();
        else showLoader(LABELS[tab]);

        if (tab === 'chat') {
            if (!chatDocked && typeof window.teamChatDock === 'function') {
                chatDocked = true;
                window.teamChatDock(document.getElementById('collabChat'));
            }
            rafReady('chat');
        } else if (tab === 'drawing') {
            if (typeof window.mountScheduleBoard === 'function') window.mountScheduleBoard(document.getElementById('collabDrawing'));
            rafReady('drawing');
        } else if (tab === 'photo') {
            // Boots on first show — the state fetch and the poll only start
            // once somebody actually opens the tab.
            if (typeof window.initCollabPhoto === 'function') window.initCollabPhoto();
            rafReady('photo');
        } else if (tab === 'map') {
            if (typeof window.initCollabMap === 'function') window.initCollabMap();
            rafReady('map');
        } else if (tab === 'camera') {
            // The grid draws from the live room; it has nothing to fetch, so
            // it is ready as soon as it has been painted once.
            rafReady('camera');
        } else if (tab === 'location') {
            // The map boots on first show and sizes itself against a panel
            // that is now actually on screen; the partial handles that on the
            // same event. Ready once it has had a frame to do it in.
            rafReady('location');
        } else if (tab === 'ai') {
            rafReady('ai');
        } else if (tab === 'activities') {
            // The iframe posts 'collab:activities-ready' once painted; hide now if already ready.
            if (frameReady || frameIsLoaded()) markReady('activities');
            // Safety net: never leave the spinner stuck if the signal never arrives.
            else setTimeout(() => { if (!ready.has('activities')) markReady('activities'); }, 15000);
        }
    });

    function show(tab) {
        tabs.forEach((t) => { const on = t.dataset.tab === tab; t.classList.toggle('is-active', on); t.setAttribute('aria-selected', on ? 'true' : 'false'); });
        panels.forEach((p) => p.classList.toggle('is-active', p.dataset.panel === tab));
        if (tab !== current) {
            document.dispatchEvent(new CustomEvent('collab:hide', { detail: { tab: current } }));
            current = tab;
        }
        // Fire show every time so a tab can (re)start its realtime when revisited.
        document.dispatchEvent(new CustomEvent('collab:show', { detail: { tab } }));
    }

    room.querySelector('.collab-tabs').addEventListener('click', (e) => {
        const t = e.target.closest('.collab-tab');
        if (t) show(t.dataset.tab);
    });

    // Fire the initial tab so its content mounts on load.
    document.addEventListener('DOMContentLoaded', () => document.dispatchEvent(new CustomEvent('collab:show', { detail: { tab: current } })), { once: true });
    if (document.readyState !== 'loading') document.dispatchEvent(new CustomEvent('collab:show', { detail: { tab: current } }));

    // Live presence: reuse the team-chat members endpoint to refresh online dots.
    const presence = document.getElementById('collabPresence');

    // A teammate's row in the sheet opens their PM in the docked chat: the
    // panel's own member strip is hidden while docked, so without this the
    // room would have no way to say something privately. The listener sits on
    // the element itself because openSheet() re-parents the sheet to <body> —
    // delegation from the room wrapper would lose it on first open.
    const pmFromRow = (row) => {
        window.closeSheet?.('collabMembersSheet');
        show('chat');
        window.scheduleTeamPm?.(parseInt(row.dataset.uid, 10), row.dataset.pmName || 'Member');
    };
    presence?.addEventListener('click', (e) => {
        const row = e.target.closest('.collab-mem.pmable');
        if (row) pmFromRow(row);
    });
    presence?.addEventListener('keydown', (e) => {
        if (e.key !== 'Enter' && e.key !== ' ') return;
        const row = e.target.closest('.collab-mem.pmable');
        if (row) { e.preventDefault(); pmFromRow(row); }
    });

    async function refreshPresence() {
        try {
            // This roster poll is the room's second presence heartbeat (the
            // docked chat's message poll is the first) — either one keeps a
            // member "in the room", so a hiccup in one does not start ringing
            // bells at somebody who is right here. A hidden tab sends no beat
            // on purpose: someone not looking at the room SHOULD get the bell.
            const beat = document.visibilityState === 'visible' ? '&inRoom=1' : '';
            const res = await fetch(`{{ route('sm.chat.members') }}?scheduleId={{ $schedule->id }}${beat}`, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
            const d = (await res.json()).data;
            const online = new Set((d.members || []).filter((m) => m.online).map((m) => String(m.id)));
            presence.querySelectorAll('.collab-mem').forEach((el) => {
                const on = online.has(el.dataset.uid);
                el.classList.toggle('on', on);
                const state = el.querySelector('.collab-mem-state');
                if (state) state.textContent = on ? 'online' : 'offline';
            });
            // The header stack shows the same truth at a glance.
            document.querySelectorAll('.collab-pill-face[data-uid]').forEach((el) => {
                el.classList.toggle('on', online.has(el.dataset.uid));
            });
        } catch (_) { /* keep last */ }
    }
    setInterval(refreshPresence, 30000);
})();
</script>
@endpush
