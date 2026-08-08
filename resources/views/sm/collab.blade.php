@extends('layouts.app')

@section('title', 'Collab Room — ' . $schedule->title)
@section('page-title', 'Collab Room')
@section('page-subtitle', $schedule->title)
@section('back', route('sm.hub', ['id' => $schedule->id]))

{{-- The room is a workspace, not a page you scroll: on a phone the bottom tab
     bar covers the drawing surface and chat composer, so drop it here and give
     the panels that strip back. Navigation is still one tap away via Back. --}}
@section('body-class', 'hide-tabbar')

@section('content')
<div class="collab-wrap" id="collabRoom" data-schedule="{{ $schedule->id }}" data-owner="{{ (int) $schedule->anisystemUserId }}">

    {{-- Who's in the room (live online dots). --}}
    <div class="collab-presence" id="collabPresence">
        @foreach ($members as $m)
            <span class="collab-mem {{ $m->isOnline() ? 'on' : '' }}" data-uid="{{ $m->id }}" title="{{ $m->full_name }}{{ (int) $m->id === (int) $schedule->anisystemUserId ? ' (owner)' : '' }}">
                <span class="collab-mem-face">
                    @if ($m->avatarPath)<img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($m->avatarPath) }}" alt="">@else{{ $m->initials }}@endif
                    <span class="collab-mem-dot"></span>
                </span>
                <span class="collab-mem-name">{{ \Illuminate\Support\Str::of($m->full_name)->explode(' ')->first() }}</span>
            </span>
        @endforeach
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
        <button type="button" class="collab-tab" data-tab="activities" role="tab" aria-selected="false">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
            <span>Activities</span>
        </button>
        <button type="button" class="collab-tab" data-tab="ai" role="tab" aria-selected="false">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2m0 0a7 7 0 017 7v3a3 3 0 01-3 3H8a3 3 0 01-3-3v-3a7 7 0 017-7zM9 12h.01M15 12h.01M9.5 17h5"/></svg>
            <span>AI Technician</span>
        </button>
    </div>

    {{-- Panels (each fills the area; only the active one shows). --}}
    <div class="collab-panels">
        <div class="collab-panel is-active" data-panel="chat" id="collabChat"></div>
        <div class="collab-panel" data-panel="drawing" id="collabDrawing"></div>
        <div class="collab-panel" data-panel="activities" id="collabActivities">
            {{-- Eager (no lazy) so it preloads in the background the moment the
                 Collab Room opens — switching to Activities is then instant. --}}
            <iframe id="collabActivitiesFrame" title="Activities" loading="eager"
                    src="{{ route('sm.activities', ['id' => $schedule->id, 'embed' => 1]) }}"></iframe>
        </div>
        <div class="collab-panel" data-panel="ai" id="collabAi">@include('sm.partials.schedule-ai-tab', ['schedule' => $schedule])</div>

        {{-- Shown while a tab's content loads (the Activities iframe especially). --}}
        <div class="collab-loader" id="collabLoader" aria-hidden="true">
            <span class="collab-spin" aria-hidden="true"></span>
            <span class="collab-loader-text" id="collabLoaderText">Loading…</span>
        </div>
    </div>
</div>

{{-- Reused realtime widgets: the group chat docks into the Chat tab; the
     whiteboard mounts inline into the Drawing tab. --}}
@include('sm.partials.schedule-chat-float', ['schedule' => $schedule])
@include('sm.partials.schedule-board', ['schedule' => $schedule])
{{-- Persistent call widget: lives at the room root so a call survives tab switches. --}}
@include('sm.partials.schedule-call', ['schedule' => $schedule])
@endsection

@push('head')
<style>
    .collab-wrap { display: flex; flex-direction: column; gap: .5rem; height: calc(100dvh - 8.5rem); min-height: 26rem; }
    /* 7rem allowed for the header plus the tab bar; with the bar gone (see the
       hide-tabbar body class above) 5rem lands the panel exactly on the bottom
       edge — measured at 390x780, the surface gains 32px and stops being
       clipped by the bar it used to sit under. */
    @media (max-width: 767px) { .collab-wrap { height: calc(100dvh - 5rem); } }

    .collab-presence { display: flex; gap: .55rem; overflow-x: auto; padding: .15rem .1rem .35rem; scrollbar-width: none; flex-shrink: 0; }
    .collab-presence::-webkit-scrollbar { display: none; }
    .collab-mem { display: flex; flex-direction: column; align-items: center; gap: .15rem; width: 3rem; flex-shrink: 0; }
    .collab-mem-face { position: relative; width: 2.2rem; height: 2.2rem; border-radius: 999px; display: flex; align-items: center; justify-content: center; background: var(--color-brand-50); color: var(--color-brand-700); font-size: .72rem; font-weight: 800; }
    .collab-mem-face img { width: 100%; height: 100%; object-fit: cover; border-radius: inherit; }
    .collab-mem-dot { position: absolute; right: -1px; bottom: -1px; width: .62rem; height: .62rem; border-radius: 999px; border: 2px solid var(--color-white); background: var(--color-gray-300); }
    .collab-mem.on .collab-mem-dot { background: #22c55e; }
    .collab-mem-name { font-size: .6rem; color: var(--color-gray-600); max-width: 3rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

    .collab-tabs { display: flex; gap: .25rem; padding: .25rem; background: var(--color-gray-100); border-radius: .8rem; flex-shrink: 0; overflow-x: auto; scrollbar-width: none; }
    .collab-tabs::-webkit-scrollbar { display: none; }
    .collab-tab { flex: 1 1 auto; display: inline-flex; align-items: center; justify-content: center; gap: .4rem; padding: .55rem .6rem; border-radius: .6rem; font-size: .9rem; font-weight: 700; color: var(--color-gray-500); white-space: nowrap; transition: background .15s ease, color .15s ease; }
    .collab-tab:hover { color: var(--color-gray-800); }
    .collab-tab.is-active { background: var(--color-white); color: var(--color-gray-900); box-shadow: 0 1px 3px rgb(0 0 0 / .1); }
    @media (max-width: 520px) { .collab-tab span { display: none; } .collab-tab { flex: 1 1 0; } }

    .collab-panels { flex: 1 1 auto; position: relative; min-height: 0; background: var(--color-white); border: 1px solid var(--color-gray-200); border-radius: 1rem; overflow: hidden; }
    .collab-panel { position: absolute; inset: 0; display: none; }
    .collab-panel.is-active { display: block; }
    #collabActivities { display: none; }
    #collabActivities.is-active { display: block; }
    #collabActivitiesFrame { width: 100%; height: 100%; border: 0; display: block; }
    /* Chat + AI panels host a column layout; drawing hosts the canvas board. */
    #collabChat.is-active, #collabAi.is-active, #collabDrawing.is-active { display: flex; }
    #collabChat, #collabAi, #collabDrawing { flex-direction: column; }
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

    /* ---------- per-tab loading overlay ---------- */
    const LABELS = { chat: 'Loading chat…', drawing: 'Loading board…', activities: 'Loading activities…', ai: 'Loading AI…' };
    const loader = document.getElementById('collabLoader');
    const loaderText = document.getElementById('collabLoaderText');
    const frame = document.getElementById('collabActivitiesFrame');
    const ready = new Set();
    let frameReady = false, loaderShownAt = 0;

    function showLoader(text) { loaderText.textContent = text || 'Loading…'; loader.classList.add('on'); loader.setAttribute('aria-hidden', 'false'); loaderShownAt = performance.now(); }
    function hideLoader() { loader.classList.remove('on'); loader.setAttribute('aria-hidden', 'true'); }
    // Mark a tab loaded; keep the loader up for a short minimum so it doesn't flash.
    function markReady(tab) {
        ready.add(tab);
        if (current !== tab) return;
        setTimeout(hideLoader, Math.max(0, 280 - (performance.now() - loaderShownAt)));
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
    async function refreshPresence() {
        try {
            const res = await fetch(`{{ route('sm.chat.members') }}?scheduleId={{ $schedule->id }}`, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
            const d = (await res.json()).data;
            const online = new Set((d.members || []).filter((m) => m.online).map((m) => String(m.id)));
            presence.querySelectorAll('.collab-mem').forEach((el) => el.classList.toggle('on', online.has(el.dataset.uid)));
        } catch (_) { /* keep last */ }
    }
    setInterval(refreshPresence, 30000);
})();
</script>
@endpush
