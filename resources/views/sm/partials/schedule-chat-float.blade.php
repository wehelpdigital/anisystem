{{--
    Floating TEAM chat for a schedule — a group message stream for the owner and
    their worker sub-members, plus 1:1 PMs that reuse the community DM (shared
    history). Sits above the AI float. Renders only when there's a real team
    (owner + ≥1 worker login) and the viewer is part of it.
    Expects: $schedule.
--}}
@php
    $teamHasTeam = \App\Support\ScheduleTeam::hasTeam($schedule);
    $teamCanAccess = \App\Support\ScheduleTeam::canAccess($schedule, (int) auth()->id());
    $teamAiSettings = \App\Models\AiSetting::current();
    $teamAiPresent = $teamAiSettings && $teamAiSettings->isUsable();
@endphp
@if ($teamHasTeam && $teamCanAccess)
<div id="teamChat" class="team-float{{ $teamAiPresent ? ' has-ai' : '' }}{{ request('module') === 'ai' ? ' team-float-off' : '' }}">
    <button type="button" id="teamChatFab" class="team-fab" aria-label="Open team chat" title="Team chat">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.9" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-1a4 4 0 00-3-3.87M9 20H4v-1a4 4 0 013-3.87m0 0a4 4 0 115.5-5.8M7 15.13A4 4 0 0012 8m5 7.13A4 4 0 0012 8m0 0a3 3 0 100-2"/></svg>
        <span id="teamChatDot" class="team-dot hidden"></span>
    </button>

    <div id="teamChatPanel" class="team-panel hidden">
        <div class="team-head">
            <button type="button" id="teamBack" class="team-icon hidden" aria-label="Back to team chat">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
            </button>
            <div class="min-w-0 grow">
                <p class="team-title truncate" id="teamTitle">Team chat</p>
                <p class="team-sub truncate" id="teamSub">Loading…</p>
            </div>
            <button type="button" id="teamCallBtn" class="team-callbtn" title="Start a group call" aria-label="Start a group call">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M6.6 10.8a15.5 15.5 0 006.6 6.6l2.2-2.2a1 1 0 011-.24 11.4 11.4 0 003.6.57 1 1 0 011 1V20a1 1 0 01-1 1A17 17 0 013 4a1 1 0 011-1h3.5a1 1 0 011 1c0 1.25.2 2.46.57 3.6a1 1 0 01-.24 1l-2.23 2.2z"/></svg>
                <span id="teamCallLabel">Group call</span>
            </button>
            <button type="button" id="teamBoardBtn" class="team-icon" title="Open the team whiteboard" aria-label="Team whiteboard">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 5a2 2 0 012-2h12a2 2 0 012 2v10a2 2 0 01-2 2h-5l-4 4v-4H6a2 2 0 01-2-2V5z"/><path stroke-linecap="round" stroke-linejoin="round" d="M8 9h8M8 12h5"/></svg>
            </button>
            <button type="button" id="teamClose" class="team-icon" aria-label="Close">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        {{-- Presence strip (group mode only): who's on the team + who's online. --}}
        <div class="team-members" id="teamMembers"></div>

        <div class="team-thread" id="teamThread">
            <div class="team-loading" id="teamLoading">Loading…</div>
        </div>

        <div class="team-composer">
            <div id="teamPhotoChip" class="team-photochip hidden">
                <img src="" alt="" id="teamPhotoThumb"><span class="grow">Photo attached</span>
                <button type="button" id="teamPhotoRemove" class="text-red-600 font-bold">Remove</button>
            </div>
            {{-- What is attached, before it is sent. --}}
            <div id="teamClipChip" class="team-photochip hidden">
                <span class="team-clipico" id="teamClipIco"></span>
                <span class="grow min-w-0" id="teamClipName">Attachment</span>
                <button type="button" id="teamClipRemove" class="text-red-600 font-bold">Remove</button>
            </div>
            <div id="teamRecBar" class="team-recbar hidden">
                <span class="team-recdot"></span>
                <span id="teamRecWhat">Recording…</span>
                <span class="grow"></span>
                <button type="button" id="teamRecStop" class="team-recstop">Stop</button>
            </div>
            {{-- The field gets the whole width; the buttons get their own row
                 under it. Crammed beside the text they left about eight
                 characters visible on a phone, which is no way to write a
                 sentence to your team. --}}
            <div class="team-box">
                <textarea id="teamText" rows="1" maxlength="4000" placeholder="Message the team…"></textarea>
            </div>
            <div class="team-tools">
                <label class="team-cam shrink-0" title="Attach a photo">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    <input type="file" id="teamPhoto" accept="image/*" class="hidden">
                </label>
                {{-- Say it instead of typing it: on a farm, hands are busy
                     and a voice note is faster than a keyboard. --}}
                <button type="button" id="teamMic" class="team-cam shrink-0" title="Record a voice note" aria-label="Record a voice note">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15a3 3 0 003-3V6a3 3 0 00-6 0v6a3 3 0 003 3z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19 11a7 7 0 01-14 0M12 18v3"/></svg>
                </button>
                <button type="button" id="teamVid" class="team-cam shrink-0" title="Record a video" aria-label="Record a video">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10l4.55-2.28A1 1 0 0121 8.62v6.76a1 1 0 01-1.45.9L15 14M5 6h8a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2z"/></svg>
                </button>
                <label class="team-cam shrink-0" title="Attach a file (up to 50 MB)">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21.4 11.05l-8.7 8.7a5 5 0 01-7.1-7.1l8.7-8.7a3.3 3.3 0 114.7 4.7l-8.7 8.7a1.7 1.7 0 11-2.4-2.4l8-8"/></svg>
                    <input type="file" id="teamFile" class="hidden">
                </label>
                <button type="button" id="teamEmoji" class="team-cam shrink-0" title="Add an emoji" aria-label="Add an emoji">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14.83 14.83a4 4 0 01-5.66 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </button>
                <button type="button" id="teamSend" class="team-send" aria-label="Send">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14m0 0l-6-6m6 6l-6 6"/></svg>
                </button>
            </div>
        </div>
    </div>
</div>

<style>
    .team-float { position: fixed; right: 1rem; bottom: 5.5rem; z-index: 61; }
    .team-float.has-ai { bottom: 9.5rem; }
    .team-float.team-float-off { display: none; }
    @media (min-width: 768px) {
        .team-float { right: 1.25rem; bottom: 1.25rem; }
        .team-float.has-ai { bottom: 5.75rem; }
    }
    .team-fab {
        width: 3.25rem; height: 3.25rem; border-radius: 999px; display: flex; align-items: center; justify-content: center;
        background: linear-gradient(140deg, #3f7fb0, #2b567c); color: #fff;
        box-shadow: 0 0 0 2px var(--color-white), 0 0 0 4px rgb(63 127 176 / .3), 0 6px 20px rgba(0,0,0,.22);
        transition: transform .15s ease, filter .15s ease; position: relative;
    }
    .team-fab:hover { filter: brightness(1.05); } .team-fab:active { transform: scale(.95); }
    .team-float.is-open .team-fab { display: none; }
    .team-dot { position: absolute; top: -1px; right: -1px; width: .85rem; height: .85rem; border-radius: 999px; background: #ef4444; border: 2px solid var(--color-white); }
    .team-dot.hidden { display: none; }

    /* Footer-docked window (like the community private chat). */
    .team-panel {
        position: fixed; right: 1.25rem; bottom: 1.25rem; width: min(24rem, calc(100vw - 2rem));
        height: min(33rem, calc(100dvh - 6rem)); display: flex; flex-direction: column;
        background: var(--color-white); border: 1px solid var(--color-gray-200);
        border-radius: 1.1rem; box-shadow: 0 16px 44px rgba(0,0,0,.26); overflow: hidden;
        animation: teamIn .18s ease both; z-index: 62;
    }
    .team-panel.hidden { display: none; }
    /* When docked into the whiteboard sidebar it fills the column, no float. */
    /* z-index: auto because the float's stack level (62) travels with the
       class otherwise — position:static does not neutralise z-index on a
       flex child, so the docked panel painted over the room's sheets (z-50):
       tap the member stack in the header and the list opened BEHIND the
       chat. Docked, the panel is furniture, not a float. */
    .team-panel.team-panel-docked { position: static; inset: auto; width: 100%; height: 100%; max-height: none; border: 0; border-radius: 0; box-shadow: none; animation: none; z-index: auto; }
    /* The board owns close + whiteboard toggles while docked. */
    .team-panel.team-panel-docked #teamClose,
    .team-panel.team-panel-docked #teamBoardBtn { display: none; }
    /* Docked means the Collab Room, and the room's page header already
       carries the member faces — the strip under this header was the same
       people twice, spending a row the thread needs. PMs from inside the
       room start from that header list instead. */
    .team-panel.team-panel-docked .team-members { display: none; }
    @keyframes teamIn { from { opacity: 0; transform: translateY(10px) scale(.98); } to { opacity: 1; transform: none; } }
    @media (max-width: 640px) {
        .team-panel { left: 0; right: 0; bottom: 0; width: 100%; height: 82dvh; border-radius: 1.1rem 1.1rem 0 0; }
    }

    .team-head { display: flex; align-items: center; gap: .5rem; padding: .6rem .75rem; border-bottom: 1px solid var(--color-gray-100); background: linear-gradient(115deg, #eef5fb, var(--color-white) 70%); }
    html.dark .team-head { background: linear-gradient(115deg, #1b2a3a, var(--color-white) 70%); }
    .team-title { font-family: var(--font-heading); font-weight: 700; font-size: .95rem; line-height: 1.2; color: var(--color-gray-900); }
    .team-sub { font-size: .72rem; color: var(--color-gray-500); }
    .team-icon { display: inline-flex; align-items: center; justify-content: center; width: 2rem; height: 2rem; border-radius: .55rem; color: var(--color-gray-500); flex-shrink: 0; }
    .team-icon:hover { background: var(--color-gray-100); color: var(--color-gray-700); }
    .team-icon.hidden { display: none; }
    .team-callbtn { display: inline-flex; align-items: center; gap: .3rem; padding: .32rem .6rem; border-radius: 999px; font-size: .74rem; font-weight: 800; color: #fff; background: linear-gradient(140deg, #6b9f3d, #3d6823); flex-shrink: 0; }
    .team-callbtn:hover { filter: brightness(1.06); }

    .team-members { display: flex; gap: .5rem; overflow-x: auto; padding: .5rem .75rem; border-bottom: 1px solid var(--color-gray-100); scrollbar-width: none; }
    .team-members::-webkit-scrollbar { display: none; }
    .team-members.hidden { display: none; }
    .team-mem { position: relative; flex-shrink: 0; display: flex; align-items: center; gap: .4rem;
        padding: .2rem .55rem .2rem .2rem; border-radius: 999px; cursor: default;
        background: var(--color-gray-50); border: 1px solid var(--color-gray-100); }
    .team-mem.pmable { cursor: pointer; }
    .team-mem-face { width: 2.1rem; height: 2.1rem; border-radius: 999px; display: flex; align-items: center; justify-content: center; background: var(--color-brand-50); color: var(--color-brand-700); font-size: .7rem; font-weight: 800; position: relative; }
    /* Clip the photo via its own radius (not the parent's overflow) so the
       online dot at the edge is never cut off. */
    .team-mem-face img { width: 100%; height: 100%; object-fit: cover; border-radius: inherit; }
    .team-mem-dot { position: absolute; right: -1px; bottom: -1px; width: .62rem; height: .62rem; border-radius: 999px; border: 2px solid var(--color-white); background: var(--color-gray-300); box-shadow: 0 0 0 1px rgb(0 0 0 / .04); }
    .team-mem-dot.on { background: #22c55e; }
    /* The whole name, not four letters of it: the strip scrolls sideways,
       so there is room for as many names as the team has. */
    .team-mem-name { font-size: .68rem; font-weight: 700; color: var(--color-gray-700); white-space: nowrap; }
    .team-mem-name b { font-weight: 800; }
    html.dark .team-mem { background: rgb(255 255 255 / .05); border-color: #2b3a1c; }
    html.dark .team-mem-name { color: #cdd8c0; }
    /* Click a member to open their PM (with a call button inside). Ring on hover. */
    .team-mem.pmable:hover .team-mem-face { box-shadow: 0 0 0 2px var(--color-brand-500); }

    /* What is attached, and what is being recorded right now. */
    .team-clipico { width: 1.6rem; height: 1.6rem; border-radius: .4rem; flex: none;
        display: inline-flex; align-items: center; justify-content: center;
        background: var(--color-brand-50); color: var(--color-brand-700); font-size: .8rem; }
    .team-recbar { display: flex; align-items: center; gap: .5rem; padding: .4rem .6rem; margin-bottom: .35rem;
        border-radius: .6rem; background: #fef2f2; border: 1px solid #fecaca; font-size: .78rem;
        font-weight: 700; color: #b91c1c; }
    .team-recbar.hidden { display: none; }
    .team-recdot { width: .55rem; height: .55rem; border-radius: 999px; background: #dc2626;
        animation: teamRecPulse 1.1s ease-in-out infinite; }
    @keyframes teamRecPulse { 0%, 100% { opacity: 1; } 50% { opacity: .25; } }
    .team-recstop { padding: .15rem .6rem; border-radius: 999px; background: #dc2626; color: #fff;
        font-size: .72rem; font-weight: 800; }
    html.dark .team-recbar { background: rgb(153 27 27 / .2); border-color: rgb(153 27 27 / .5); color: #fca5a5; }
    @media (prefers-reduced-motion: reduce) { .team-recdot { animation: none; } }
    /* An attachment inside a message. */
    .team-att { display: flex; align-items: center; gap: .45rem; margin-top: .35rem; padding: .35rem .5rem;
        border-radius: .5rem; background: rgb(255 255 255 / .15); font-size: .75rem; font-weight: 700;
        text-decoration: none; color: inherit; }
    .team-att:hover { background: rgb(255 255 255 / .28); }
    .team-msg:not(.me) .team-att { background: var(--color-gray-100); color: var(--color-gray-700); }
    .team-att svg { width: .95rem; height: .95rem; flex: none; }
    .team-att span { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .team-msg video, .team-msg audio { max-width: 100%; border-radius: .5rem; margin-top: .35rem; display: block; }
    /* Who has seen it — under your own last message only. */
    .team-seen { font-size: .62rem; color: var(--color-gray-400); align-self: flex-end;
        padding: 0 .2rem .25rem; }
    html.dark .team-seen { color: #7d8f6e; }

    /* Someone is mid-sentence: the classic three-dot bubble at the thread's
       foot, with the name beside it ("Maria is typing…"). Rises in like a
       message; the dots park under reduced motion but the words still tell. */
    .team-typing { display: flex; align-items: center; gap: .45rem; padding: .1rem .1rem .35rem;
        animation: teamRise .24s cubic-bezier(.22,1,.36,1) both; }
    .team-typing-dots { display: inline-flex; align-items: center; gap: .28rem; padding: .5rem .65rem;
        border-radius: .9rem .9rem .9rem .25rem; background: var(--color-gray-100); }
    .team-typing-dots i { width: .4rem; height: .4rem; border-radius: 999px; background: var(--color-gray-400);
        display: block; animation: teamTypingDot 1.2s ease-in-out infinite; }
    .team-typing-dots i:nth-child(2) { animation-delay: .15s; }
    .team-typing-dots i:nth-child(3) { animation-delay: .3s; }
    @keyframes teamTypingDot { 0%, 60%, 100% { transform: none; opacity: .45; } 30% { transform: translateY(-3px); opacity: 1; } }
    .team-typing-who { font-size: .68rem; font-weight: 600; color: var(--color-gray-500);
        overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    html.dark .team-typing-dots { background: rgb(255 255 255 / .08); }
    html.dark .team-typing-dots i { background: #9aa69a; }
    html.dark .team-typing-who { color: #9aa69a; }

    .team-thread { flex: 1 1 auto; overflow-y: auto; padding: .75rem; display: flex; flex-direction: column; gap: .1rem; scrollbar-width: thin; }
    .team-loading { margin: auto; color: var(--color-gray-400); font-size: .85rem; }
    .team-msg { display: flex; gap: .45rem; margin-bottom: .5rem; align-items: flex-end; animation: teamRise .24s cubic-bezier(.22,1,.36,1) both; }
    .team-msg.me { flex-direction: row-reverse; }
    @keyframes teamRise { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: none; } }
    .team-msg .face { width: 1.7rem; height: 1.7rem; border-radius: 999px; overflow: hidden; flex-shrink: 0; display: flex; align-items: center; justify-content: center; background: var(--color-brand-50); color: var(--color-brand-700); font-size: .62rem; font-weight: 800; }
    .team-msg .face img { width: 100%; height: 100%; object-fit: cover; }
    .team-msg .col { max-width: 78%; display: flex; flex-direction: column; }
    .team-msg.me .col { align-items: flex-end; }
    .team-msg .who { font-size: .64rem; font-weight: 700; color: var(--color-gray-500); margin: 0 .25rem .1rem; }
    .team-msg .b { padding: .45rem .7rem; font-size: .9rem; line-height: 1.45; background: var(--color-gray-100); color: var(--color-gray-900); border-radius: .9rem .9rem .9rem .25rem; word-break: break-word; white-space: pre-wrap; }
    .team-msg.me .b { background: linear-gradient(135deg, #3f7fb0, #2b567c); color: #fff; border-radius: .9rem .9rem .25rem .9rem; }
    .team-msg .b img { max-width: 100%; max-height: 190px; border-radius: .5rem; margin-top: .2rem; display: block; }
    .team-msg .at { font-size: .58rem; color: var(--color-gray-400); margin: .1rem .3rem 0; }

    .team-composer { flex-shrink: 0; padding: .55rem .7rem .7rem; border-top: 1px solid var(--color-gray-100); }
    .team-composer.is-disabled { opacity: .55; pointer-events: none; }
    .team-photochip { display: flex; align-items: center; gap: .4rem; font-size: .72rem; font-weight: 600; color: var(--color-gray-500); margin-bottom: .4rem; background: var(--color-gray-100); border-radius: .6rem; padding: .3rem .5rem; }
    .team-photochip.hidden { display: none; }
    .team-photochip img { width: 1.9rem; height: 1.9rem; border-radius: .4rem; object-fit: cover; }
    .team-box { display: flex; align-items: flex-end; border: 1.5px solid var(--color-gray-200); border-radius: 1.1rem; padding: .1rem .7rem; background: var(--color-white); }
    .team-box:focus-within { border-color: #3f7fb0; box-shadow: 0 0 0 3px rgb(63 127 176 / .16); }
    /* Attach / mic / video / emoji live under the field, not in it. Send sits
       at the far end of the same row so the thumb has one place to go. */
    .team-tools { display: flex; align-items: center; gap: .15rem; margin-top: .4rem; }
    .team-tools .team-send { margin-left: auto; }
    .team-cam { width: 2.15rem; height: 2.15rem; border-radius: .7rem; display: flex; align-items: center; justify-content: center; flex-shrink: 0; color: var(--color-brand-700); cursor: pointer;
        transition: background-color .28s cubic-bezier(.22,1,.36,1), color .28s cubic-bezier(.22,1,.36,1); }
    .team-cam:hover { background: var(--color-brand-50); }
    html.dark .team-cam { color: #a7c98a; }
    html.dark .team-cam:hover { background: rgb(255 255 255 / .07); }
    #teamText { resize: none; border: 0; outline: none; background: transparent; width: 100%; max-height: 5.5rem; padding: .5rem 0; font-size: .92rem; color: inherit; }
    .team-send { width: 2.15rem; height: 2.15rem; border-radius: 999px; background: linear-gradient(140deg, #3f7fb0, #2b567c); color: #fff; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .team-send:disabled { opacity: .4; }
    /* Emoji popover — above the docked panel and the board overlay it can sit in. */
    .team-emoji-pop { position: fixed; z-index: 250; display: none; grid-template-columns: repeat(8, 1fr); gap: .1rem;
        background: var(--color-white); border: 1px solid var(--color-gray-200); border-radius: .75rem; padding: .4rem;
        box-shadow: 0 12px 32px -8px rgb(0 0 0 / .35); max-width: 17rem; }
    .team-emoji-pop.is-open { display: grid; animation: teamIn .18s cubic-bezier(.22,1,.36,1) both; }
    .team-emoji-pop button { width: 2rem; height: 2rem; font-size: 1.15rem; border-radius: .4rem; cursor: pointer; background: transparent; }
    .team-emoji-pop button:hover { background: var(--color-gray-100); }
    html.dark .team-emoji-pop { background: #1c2416; border-color: #2b3a1c; }

    @media (prefers-reduced-motion: reduce) {
        .team-panel, .team-msg, .team-emoji-pop.is-open, .team-typing { animation: none; }
        .team-typing-dots i { animation: none; opacity: .7; }
        .team-cam { transition: none; }
    }
</style>

<script>
(() => {
    const init = () => {
        const $ = (id) => document.getElementById(id);
        const fab = $('teamChatFab'), panel = $('teamChatPanel'), thread = $('teamThread');
        if (!fab || !panel) return;

        const SCHEDULE_ID = @json($schedule->id);
        const ME = @json((int) auth()->id());
        const MY_INITIALS = @json(auth()->user()->initials ?? '');
        // First name only — it rides typing whispers, where "Maria is
        // typing…" is the whole message.
        const MY_NAME = @json(auth()->user()->firstName ?? 'Someone');
        const U = {
            messages: @json(route('sm.chat')),
            send: @json(route('sm.chat.send')),
            members: @json(route('sm.chat.members')),
            seen: @json(route('sm.chat.seen')),
            // Literal on purpose: route('sm.chat.typing') would take the whole
            // page down until the route lands, a 404 just means no dots yet.
            typing: @json(url('/app/sm-chat-typing')),
            dmBase: @json(url('/app/community/messages')),
        };
        const CSRF = document.querySelector('meta[name=csrf-token]')?.content || '';

        let mode = 'group';           // 'group' | 'pm'
        let pmUser = null, pmName = '';
        let lastGroupId = 0;          // highest group message id seen
        let photoFile = null;
        let msgTimer = null, memTimer = null;
        let canSend = true;
        // One attachment in flight at a time — see showClip().
        let clipFile = null, clipKind = 'file';

        const scrollDown = () => { thread.scrollTop = thread.scrollHeight; };
        // Docked = the Collab Room, and there the polls double as the room's
        // presence heartbeat — announce() spares whoever it marks from a bell
        // about a thread already in front of them. Only while the tab is
        // actually being looked at: a backgrounded room is not being watched,
        // and the bells the heartbeat suppresses should reach it. The same
        // rule markSeen() already lives by.
        const heartbeat = () => (panel.classList.contains('team-panel-docked')
            && document.visibilityState === 'visible') ? '&inRoom=1' : '';
        const faceHtml = (avatar, initials) => avatar
            ? `<img src="${escapeHtml(avatar)}" alt="">`
            : escapeHtml(initials || '·');

        /* ---------- rendering ---------- */
        function clearThread() { thread.innerHTML = ''; typingSeen = {}; }
        function bubble(m, opts) {
            const el = document.createElement('div');
            el.className = 'team-msg' + (m.mine ? ' me' : '');
            const showWho = opts && opts.showSender && !m.mine;
            const face = showWho ? `<span class="face">${faceHtml(m.avatar, m.initials)}</span>` : '';
            const who = showWho ? `<span class="who">${escapeHtml(m.name || 'Member')}</span>` : '';
            const img = m.image ? `<img src="${escapeHtml(m.image)}" alt="">` : '';
            const body = m.body ? escapeHtml(m.body) : '';
            // A clip plays where it sits; a file is a line you can tap.
            const files = (m.files || []).map((f) => {
                if (f.type === 'video') {
                    return `<video src="${escapeHtml(f.url)}"${f.posterUrl ? ` poster="${escapeHtml(f.posterUrl)}"` : ''} controls playsinline preload="metadata"></video>`;
                }
                if (f.type === 'audio') {
                    return `<audio src="${escapeHtml(f.url)}" controls preload="metadata"></audio>`;
                }
                return `<a class="team-att" href="${escapeHtml(f.url)}" target="_blank" rel="noopener" download>
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21.4 11.05l-8.7 8.7a5 5 0 01-7.1-7.1l8.7-8.7a3.3 3.3 0 114.7 4.7l-8.7 8.7a1.7 1.7 0 11-2.4-2.4l8-8"/></svg>
                    <span>${escapeHtml(f.name || 'Attachment')}</span></a>`;
            }).join('');
            el.innerHTML = `${face}<div class="col">${who}<div class="b"${m.image ? ' data-lightbox' : ''}>${body}${img}${files}</div><span class="at">${escapeHtml(m.at || '')}</span></div>`;
            el.dataset.mid = m.id;
            thread.appendChild(el);
            // The typing dots always sit at the foot — a message landing while
            // they show must not leave them stranded mid-thread.
            const ty = thread.querySelector('.team-typing');
            if (ty) thread.appendChild(ty);
            return el;
        }

        /* ---------- typing dots -------------------------------------------
         * Names arrive two ways and land in one place: the poll asserts the
         * whole set each beat (authoritative, a little late), a whisper drops
         * a single name in instantly. Every name carries its own expiry, so
         * whoever goes quiet just fades off the label without a "stopped
         * typing" message ever crossing the wire. */
        let typingSeen = {};   // first name -> epoch ms it stops being true
        function noteTyping(name) {
            if (!name) return;
            typingSeen[name] = Date.now() + 4000;
            paintTyping();
        }
        function setTyping(names) {
            typingSeen = {};
            // Past the poll's own beat by a little, so the label doesn't
            // flicker between two beats of one person's slow sentence.
            (names || []).forEach((n) => { if (n) typingSeen[n] = Date.now() + 7000; });
            paintTyping();
        }
        function paintTyping() {
            const now = Date.now();
            const names = Object.keys(typingSeen).filter((n) => typingSeen[n] > now);
            let el = thread.querySelector('.team-typing');
            if (!names.length) { el?.remove(); return; }
            // Only follow the dots down if the reader is already at the foot.
            const nearFoot = thread.scrollHeight - thread.scrollTop - thread.clientHeight < 80;
            if (!el) {
                el = document.createElement('div');
                el.className = 'team-typing';
            }
            const who = names.length > 2
                ? `${names.slice(0, 2).join(', ')} +${names.length - 2} are typing…`
                : `${names.join(' and ')} ${names.length > 1 ? 'are' : 'is'} typing…`;
            // Touch the DOM only on change — re-appending or rewriting an
            // unchanged strip every sweep would restart its animations.
            if (el._label !== who) {
                el.innerHTML = `<span class="team-typing-dots"><i></i><i></i><i></i></span><span class="team-typing-who">${escapeHtml(who)}</span>`;
                el._label = who;
            }
            if (thread.lastElementChild !== el) thread.appendChild(el);
            if (nearFoot) scrollDown();
        }
        // The quiet expiry sweep — lets a lapsed name disappear between polls.
        setInterval(paintTyping, 1500);

        /* ---------- group mode ---------- */
        async function loadMembers() {
            try {
                const res = await api(`${U.members}?scheduleId=${SCHEDULE_ID}${heartbeat()}`);
                const d = res.data;
                if (mode === 'group') $('teamSub').textContent = `${d.online} of ${d.total} online`;
                const wrap = $('teamMembers');
                wrap.innerHTML = (d.members || []).map((m) => {
                    const pmable = !m.isMe;
                    return `<div class="team-mem ${pmable ? 'pmable' : ''}" ${pmable ? `data-pm="${m.id}" data-name="${escapeHtml(m.name)}"` : ''} title="${escapeHtml(m.name)}${m.isOwner ? ' (owner)' : ''}">
                        <span class="team-mem-face">${faceHtml(m.avatar, m.initials)}<span class="team-mem-dot ${m.online ? 'on' : ''}"></span></span>
                        <span class="team-mem-name">${m.isMe ? 'You' : escapeHtml(m.name)}${m.isOwner ? ' <b>·</b> owner' : ''}</span>
                    </div>`;
                }).join('');
            } catch (_) { /* keep last roster */ }
        }

        async function pollGroup() {
            try {
                const res = await api(`${U.messages}?scheduleId=${SCHEDULE_ID}&after=${lastGroupId}${heartbeat()}`);
                const msgs = res.data.messages || [];
                if (lastGroupId === 0) clearThread();
                if (msgs.length) {
                    const first = !thread.querySelector('.team-msg');
                    msgs.forEach((m) => bubble(m, { showSender: true }));
                    lastGroupId = res.data.maxId || lastGroupId;
                    if (panel.classList.contains('hidden')) {
                        // new activity while closed → dot (ignore my own echoes)
                        if (msgs.some((m) => !m.mine)) $('teamChatDot').classList.remove('hidden');
                    } else { scrollDown(); }
                    if (first) scrollDown();
                }
                // The poll doubles as the typing wire: assert the whole set.
                setTyping(res.data.typing || []);
                $('teamLoading')?.remove();
            } catch (_) { /* transient */ }
        }

        async function sendGroup() {
            const text = ($('teamText').value || '').trim();
            if (!text && !photoFile && !clipFile) return;
            const fd = new FormData();
            if (text) fd.append('body', text);
            if (photoFile) fd.append('image', photoFile);
            if (clipFile) { fd.append('clip', clipFile, clipFile.name || 'clip'); fd.append('kind', clipKind); }
            $('teamSend').disabled = true;
            // A minute of video is a real wait on a farm's signal; say so
            // rather than leaving a dead button.
            if (clipFile) sayRec('Sending…', true);
            try {
                const res = await api(`${U.send}?scheduleId=${SCHEDULE_ID}`, { method: 'POST', body: fd });
                const m = res.data.message;
                bubble(m, { showSender: true });
                lastGroupId = Math.max(lastGroupId, m.id);
                resetComposer(); scrollDown();
            } catch (err) { toast(err.message || 'Could not send.', 'error'); }
            finally { $('teamSend').disabled = false; sayRec(null); }
        }

        /* ---------- PM mode (reuses community DM → shared history) ---------- */
        async function loadPmThread(initial) {
            try {
                const res = await fetch(`${U.dmBase}/${pmUser}`, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
                const d = (await res.json()).data;
                canSend = d.canMessage !== false;
                $('teamComposerState')?.remove();
                clearThread();
                (d.messages || []).forEach((m) => bubble({ mine: m.mine, body: m.body, image: m.image, at: m.at }, { showSender: false }));
                // The PM thread refetches on its own beat, so typing rides it.
                setTyping(d.typing ? [pmName] : []);
                setComposerEnabled(canSend);
                if (initial) scrollDown();
                else scrollDown();
            } catch (_) { /* transient */ }
        }
        async function sendPm() {
            const text = ($('teamText').value || '').trim();
            if (!text && !photoFile) return;
            const fd = new FormData();
            if (text) fd.append('body', text);
            if (photoFile) fd.append('image', photoFile);
            $('teamSend').disabled = true;
            try {
                const res = await fetch(`${U.dmBase}/${pmUser}`, { method: 'POST', headers: { 'X-CSRF-TOKEN': CSRF, Accept: 'application/json' }, body: fd });
                const j = await res.json();
                if (!j.success) throw new Error(j.message || 'Could not send.');
                const m = j.data;
                bubble({ mine: true, body: m.body, image: m.image, at: m.at }, { showSender: false });
                resetComposer(); scrollDown();
            } catch (err) { toast(err.message || 'Could not send.', 'error'); }
            finally { $('teamSend').disabled = false; }
        }

        /* ---------- mode switching ---------- */
        function setComposerEnabled(on) {
            document.querySelector('.team-composer').classList.toggle('is-disabled', !on);
            $('teamText').placeholder = on ? (mode === 'pm' ? `Message ${pmName}…` : 'Message the team…') : 'Messaging is turned off';
        }
        function toGroup() {
            mode = 'group'; pmUser = null;
            $('teamBack').classList.add('hidden');
            $('teamMembers').classList.remove('hidden');
            $('teamTitle').textContent = 'Team chat';
            $('teamCallLabel').textContent = 'Group call';
            $('teamCallBtn').title = 'Start a group call';
            lastGroupId = 0; clearThread();
            setComposerEnabled(true);
            startTimers();
            pollGroup(); loadMembers();
        }
        function toPm(userId, name) {
            mode = 'pm'; pmUser = userId; pmName = name || 'Worker';
            $('teamBack').classList.remove('hidden');
            $('teamMembers').classList.add('hidden');
            $('teamTitle').textContent = pmName;
            $('teamSub').textContent = 'Private message';
            $('teamCallLabel').textContent = 'Call';
            $('teamCallBtn').title = 'Call ' + pmName;
            clearThread();
            startTimers();
            loadPmThread(true);
        }

        function startTimers() {
            clearInterval(msgTimer); clearInterval(memTimer);
            msgTimer = setInterval(() => { if (mode === 'group') pollGroup(); else loadPmThread(false); }, 5000);
            if (mode === 'group') memTimer = setInterval(loadMembers, 30000);
        }
        function stopTimers() { clearInterval(msgTimer); clearInterval(memTimer); msgTimer = memTimer = null; }

        /* ---------- composer helpers ---------- */
        function resetComposer() {
            $('teamText').value = ''; $('teamText').style.height = 'auto';
            photoFile = null; $('teamPhotoChip').classList.add('hidden');
            clearClip();
        }
        function currentSend() { return mode === 'pm' ? sendPm() : sendGroup(); }

        /* ---------- open / close ---------- */
        function openPanel(open) {
            panel.classList.toggle('hidden', !open);
            $('teamChat').classList.toggle('is-open', open);
            if (open) {
                $('teamChatDot').classList.add('hidden');
                if (mode === 'group') { toGroup(); } else { startTimers(); }
                // Opening the thread IS seeing it.
                setTimeout(markSeen, 400);
                // On a phone the keyboard would swallow half the room before a
                // word of chat has been read; a tap on the box still opens it.
                if (!window.matchMedia('(pointer: coarse)').matches) {
                    window.smFocus($('teamText'), { delay: 60 });
                }
            } else { stopTimers(); }
        }

        fab.addEventListener('click', () => openPanel(panel.classList.contains('hidden')));
        $('teamClose').addEventListener('click', () => openPanel(false));
        $('teamBack').addEventListener('click', toGroup);
        $('teamBoardBtn')?.addEventListener('click', () => { if (typeof window.openScheduleBoard === 'function') window.openScheduleBoard(); });
        // Call button: in a PM it calls that worker; in group mode it's a team call.
        $('teamCallBtn')?.addEventListener('click', () => {
            if (mode === 'pm' && pmUser) {
                if (typeof window.callWorker === 'function') window.callWorker(pmUser, pmName);
                else if (window.toast) toast('Calls are only available in the Collab Room.', 'error');
                return;
            }
            if (typeof window.startTeamCall === 'function') window.startTeamCall();
            else if (window.toast) toast('Calls are only available in the Collab Room.', 'error');
        });

        // Click a member tile to open their private message (which has a Call button).
        $('teamMembers').addEventListener('click', (e) => {
            const m = e.target.closest('.team-mem.pmable');
            if (m) toPm(parseInt(m.getAttribute('data-pm'), 10), m.getAttribute('data-name'));
        });

        // Composer.
        const input = $('teamText');
        input.addEventListener('input', () => { input.style.height = 'auto'; input.style.height = Math.min(input.scrollHeight, 88) + 'px'; });

        // Typing pings: one every 2.5s while keys actually move. The server
        // flag lives four seconds, so a live typist keeps it renewed and
        // going quiet simply lets it lapse. The whisper makes the Pusher-live
        // case instant; the endpoint is the floor either way.
        let lastTypingPing = 0;
        input.addEventListener('input', () => {
            if (!input.value.trim()) return;   // clearing the field is not typing
            const now = Date.now();
            if (now - lastTypingPing < 2500) return;
            lastTypingPing = now;
            if (mode === 'pm' && pmUser) {
                // A PM is a community DM, so its typing flag lives there too.
                fetch(`${U.dmBase}/${pmUser}/typing`, { method: 'POST', headers: { 'X-CSRF-TOKEN': CSRF, Accept: 'application/json' }, credentials: 'same-origin' }).catch(() => {});
                return;
            }
            api(`${U.typing}?scheduleId=${SCHEDULE_ID}`, { method: 'POST' }).catch(() => {});
            try { window.Echo?.private('schedule-board.' + SCHEDULE_ID).whisper('typing', { userId: ME, name: MY_NAME }); } catch (_) { /* poll covers it */ }
        });
        input.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' && !e.shiftKey && window.matchMedia('(min-width: 768px)').matches) { e.preventDefault(); currentSend(); }
        });
        $('teamSend').addEventListener('click', currentSend);
        $('teamPhoto').addEventListener('change', (e) => {
            const f = e.target.files && e.target.files[0];
            if (!f) return;
            photoFile = f;
            $('teamPhotoThumb').src = URL.createObjectURL(f);
            $('teamPhotoChip').classList.remove('hidden');
            e.target.value = '';
        });
        $('teamPhotoRemove').addEventListener('click', () => { photoFile = null; $('teamPhotoChip').classList.add('hidden'); });

        /* ---------- emoji ------------------------------------------------
         * Its own popover rather than the community one: this panel can be
         * docked inside the whiteboard overlay, which the shared popover sits
         * underneath, and the farm emoji set is the same either way. */
        const EMOJIS = ['🌱','🌾','🌽','🍚','🍅','🍆','🥒','🥬','🌶️','🥭','🍌','🥥','☀️','🌤️','🌧️','⛈️','🌈','💧','🌡️','🐛','🐌','🐜','🐔','🐖','🐃','🚜','🧺','🧑‍🌾','😀','😄','😅','🤔','😮','😢','😍','🙏','👍','👏','💪','🤝','❤️','🔥','✅','⚠️'];
        const pop = document.createElement('div');
        pop.className = 'team-emoji-pop';
        pop.innerHTML = EMOJIS.map((em) => `<button type="button">${em}</button>`).join('');
        document.body.appendChild(pop);
        const closePop = () => pop.classList.remove('is-open');
        $('teamEmoji').addEventListener('click', (e) => {
            if (pop.classList.contains('is-open')) { closePop(); return; }
            pop.classList.add('is-open');
            const r = e.currentTarget.getBoundingClientRect();
            pop.style.left = Math.max(8, Math.min(r.left, window.innerWidth - pop.offsetWidth - 8)) + 'px';
            // Above the button unless there is no room up there.
            pop.style.top = (r.top > pop.offsetHeight + 16 ? r.top - pop.offsetHeight - 8 : r.bottom + 8) + 'px';
        });
        pop.addEventListener('click', (e) => {
            const b = e.target.closest('button');
            if (!b) return;
            const at = input.selectionStart ?? input.value.length;
            input.setRangeText(b.textContent, at, input.selectionEnd ?? at, 'end');
            input.dispatchEvent(new Event('input'));   // keep the auto-grow honest
            input.focus();
        });
        document.addEventListener('click', (e) => {
            if (!e.target.closest('.team-emoji-pop') && !e.target.closest('#teamEmoji')) closePop();
        });
        document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closePop(); });

        /* ---------- clips: recorded here, or picked off the device -------
         * One attachment at a time on purpose: a message with four things
         * hanging off it is a message nobody reads. 50 MB is the ceiling the
         * server enforces; it is checked here too so a doomed upload is not
         * started over a phone signal. */
        const MAX_BYTES = 50 * 1024 * 1024;
        const ICON = { video: '🎬', audio: '🎙️', file: '📎' };

        function showClip(file, kind) {
            if (!file) return;
            if (file.size > MAX_BYTES) {
                toast('That is over 50 MB — too big to send from a field.', 'error');
                return;
            }
            clipFile = file; clipKind = kind;
            $('teamClipIco').textContent = ICON[kind] || ICON.file;
            $('teamClipName').textContent = file.name || (kind === 'audio' ? 'Voice note' : 'Clip');
            $('teamClipChip').classList.remove('hidden');
        }
        function clearClip() {
            clipFile = null; clipKind = 'file';
            $('teamClipChip').classList.add('hidden');
        }
        function sayRec(text, busy) {
            const bar = $('teamRecBar');
            if (!text) { bar.classList.add('hidden'); return; }
            $('teamRecWhat').textContent = text;
            $('teamRecStop').hidden = !!busy;
            bar.classList.remove('hidden');
        }
        $('teamClipRemove').addEventListener('click', clearClip);
        $('teamFile').addEventListener('change', (e) => {
            const f = e.target.files && e.target.files[0];
            e.target.value = '';
            if (f) showClip(f, 'file');
        });

        /* Recording, of sound or of sound and picture. The same machinery
           either way; only the tracks asked for differ. */
        let recorder = null, recChunks = [], recStream = null, recKind = 'audio';
        const bestMime = (want) => {
            const list = want === 'video'
                ? ['video/webm;codecs=vp9,opus', 'video/webm;codecs=vp8,opus', 'video/webm', 'video/mp4']
                : ['audio/webm;codecs=opus', 'audio/webm', 'audio/mp4'];
            return list.find((t) => window.MediaRecorder && MediaRecorder.isTypeSupported(t)) || '';
        };

        async function startRec(kind) {
            if (recorder) return;
            if (!navigator.mediaDevices || !window.MediaRecorder) {
                toast('This browser cannot record here.', 'error');
                return;
            }
            try {
                recStream = await navigator.mediaDevices.getUserMedia(kind === 'video'
                    ? { audio: true, video: { facingMode: { ideal: 'environment' }, width: { ideal: 1280 }, height: { ideal: 720 } } }
                    : { audio: true });
            } catch (_) {
                toast('Microphone or camera blocked. Allow it for this site.', 'error');
                return;
            }
            recKind = kind;
            recChunks = [];
            const mime = bestMime(kind);
            try { recorder = new MediaRecorder(recStream, mime ? { mimeType: mime } : undefined); }
            catch (_) { recorder = new MediaRecorder(recStream); }
            recorder.ondataavailable = (e) => { if (e.data && e.data.size) recChunks.push(e.data); };
            recorder.onstop = () => {
                const type = recorder.mimeType || (kind === 'video' ? 'video/webm' : 'audio/webm');
                const ext = type.includes('mp4') ? (kind === 'video' ? 'mp4' : 'm4a') : 'webm';
                const blob = new Blob(recChunks, { type });
                recStream.getTracks().forEach((t) => t.stop());
                recStream = null; recorder = null; recChunks = [];
                sayRec(null);
                if (blob.size) {
                    showClip(new File([blob], (kind === 'video' ? 'clip.' : 'voice-note.') + ext, { type }), kind);
                }
            };
            recorder.start();
            sayRec(kind === 'video' ? 'Recording video…' : 'Recording…');
            // Never leave a recorder running: five minutes is longer than
            // anything worth sending to a team chat.
            setTimeout(() => { if (recorder) stopRec(); }, 5 * 60 * 1000);
        }
        function stopRec() { try { recorder && recorder.stop(); } catch (_) { /* already stopped */ } }

        $('teamMic').addEventListener('click', () => (recorder ? stopRec() : startRec('audio')));
        $('teamVid').addEventListener('click', () => (recorder ? stopRec() : startRec('video')));
        $('teamRecStop').addEventListener('click', stopRec);

        /* ---------- who has seen what -------------------------------------
         * Marks are only posted while the thread is genuinely on screen: a
         * message that arrived behind a shut panel has been seen by nobody. */
        let seenTimer = null;
        async function markSeen() {
            if (mode !== 'group' || panel.classList.contains('hidden')) return;
            if (document.visibilityState !== 'visible') return;
            try {
                const res = await api(`${U.seen}?scheduleId=${SCHEDULE_ID}`, {
                    method: 'POST', body: { upto: lastGroupId },
                });
                paintSeen((res.data && res.data.seen) || {});
            } catch (_) { /* not worth a toast */ }
        }
        /** The mark goes under the newest of my messages that anyone has seen. */
        function paintSeen(map) {
            thread.querySelectorAll('.team-seen').forEach((n) => n.remove());
            const ids = Object.keys(map).map(Number).filter((n) => map[n] && map[n].length);
            if (!ids.length) return;
            const newest = Math.max(...ids);
            const el = thread.querySelector(`.team-msg[data-mid="${newest}"]`);
            if (!el) return;
            const names = [...new Set(map[newest])];
            const said = names.length > 2
                ? `Seen by ${names.slice(0, 2).join(', ')} +${names.length - 2}`
                : `Seen by ${names.join(' and ')}`;
            const tag = document.createElement('span');
            tag.className = 'team-seen';
            tag.textContent = said;
            el.after(tag);
        }
        document.addEventListener('visibilitychange', () => { if (document.visibilityState === 'visible') markSeen(); });

        /* ---------- realtime ----------------------------------------------
         * The poll stays as the floor; this only makes the common case
         * instant. A nudge carries an id, never a body — the thread asks for
         * the message itself through the endpoint that authorises it. */
        try {
            window.Echo?.private('schedule-board.' + SCHEDULE_ID)
                .listen('.chat.message', (p) => {
                    if (p && p.userId === ME) return;   // my own echo
                    if (mode === 'group') pollGroup().then(() => markSeen());
                    else $('teamChatDot').classList.remove('hidden');
                })
                // A teammate's typing whisper: instant dots, own 4s decay.
                // Client events never touch the server, so this is free when
                // Pusher allows them and silently absent when it doesn't.
                .listenForWhisper('typing', (p) => {
                    if (!p || p.userId === ME || mode !== 'group') return;
                    noteTyping(String(p.name || 'Someone'));
                });
        } catch (_) { /* no realtime here — the poll covers it */ }

        // Public hook so worker cards / other UI can open a worker PM in this panel.
        window.scheduleTeamPm = (userId, name) => { openPanel(true); toPm(parseInt(userId, 10), name); };

        // Dock this whole panel into the whiteboard sidebar (chat while drawing),
        // then hand it back to its floating home on close. Reuses everything —
        // group chat, PM, presence, live sync — no duplicate chat code.
        window.teamChatDock = (container) => {
            if (!container) return;
            container.appendChild(panel);
            panel.classList.add('team-panel-docked');
            openPanel(true);
        };
        window.teamChatUndock = () => {
            const host = document.getElementById('teamChat');
            panel.classList.remove('team-panel-docked');
            if (host) host.appendChild(panel);
            openPanel(false);
        };

        // A quiet background poll for the unread dot even while closed.
        setInterval(() => { if (panel.classList.contains('hidden') && mode === 'group') pollGroup(); }, 20000);
    };
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init, { once: true });
    else init();
})();
</script>
{{-- Chat photos expand into the shared lightbox (include is self-guarded). --}}
@include('community.partials.lightbox-js')
@endif
