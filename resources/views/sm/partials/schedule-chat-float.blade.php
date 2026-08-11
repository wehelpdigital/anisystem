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
            <div class="team-loading" id="teamLoading">Loading messages…</div>
        </div>

        <div class="team-composer">
            <div id="teamPhotoChip" class="team-photochip hidden">
                <img src="" alt="" id="teamPhotoThumb"><span class="grow">Photo attached</span>
                <button type="button" id="teamPhotoRemove" class="text-red-600 font-bold">Remove</button>
            </div>
            <div class="team-box">
                <label class="team-cam shrink-0" title="Attach a photo">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    <input type="file" id="teamPhoto" accept="image/*" class="hidden">
                </label>
                <textarea id="teamText" rows="1" maxlength="4000" placeholder="Message the team…"></textarea>
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
    .team-panel.team-panel-docked { position: static; inset: auto; width: 100%; height: 100%; max-height: none; border: 0; border-radius: 0; box-shadow: none; animation: none; }
    /* The board owns close + whiteboard toggles while docked. */
    .team-panel.team-panel-docked #teamClose,
    .team-panel.team-panel-docked #teamBoardBtn { display: none; }
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
    .team-mem { position: relative; flex-shrink: 0; display: flex; flex-direction: column; align-items: center; gap: .15rem; width: 3rem; cursor: default; }
    .team-mem.pmable { cursor: pointer; }
    .team-mem-face { width: 2.1rem; height: 2.1rem; border-radius: 999px; display: flex; align-items: center; justify-content: center; background: var(--color-brand-50); color: var(--color-brand-700); font-size: .7rem; font-weight: 800; position: relative; }
    /* Clip the photo via its own radius (not the parent's overflow) so the
       online dot at the edge is never cut off. */
    .team-mem-face img { width: 100%; height: 100%; object-fit: cover; border-radius: inherit; }
    .team-mem-dot { position: absolute; right: -1px; bottom: -1px; width: .62rem; height: .62rem; border-radius: 999px; border: 2px solid var(--color-white); background: var(--color-gray-300); box-shadow: 0 0 0 1px rgb(0 0 0 / .04); }
    .team-mem-dot.on { background: #22c55e; }
    .team-mem-name { font-size: .6rem; color: var(--color-gray-600); max-width: 3rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    /* Click a member to open their PM (with a call button inside). Ring on hover. */
    .team-mem.pmable:hover .team-mem-face { box-shadow: 0 0 0 2px var(--color-brand-500); }

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
    .team-box { display: flex; align-items: flex-end; gap: .25rem; border: 1.5px solid var(--color-gray-200); border-radius: 1.1rem; padding: .2rem .2rem .2rem .4rem; background: var(--color-white); }
    .team-box:focus-within { border-color: #3f7fb0; box-shadow: 0 0 0 3px rgb(63 127 176 / .16); }
    .team-cam { width: 2.15rem; height: 2.15rem; border-radius: .7rem; display: flex; align-items: center; justify-content: center; flex-shrink: 0; background: var(--color-brand-50); color: var(--color-brand-700); cursor: pointer; }
    #teamText { resize: none; border: 0; outline: none; background: transparent; flex: 1 1 auto; max-height: 5.5rem; padding: .4rem .25rem; font-size: .92rem; color: inherit; }
    .team-send { width: 2.15rem; height: 2.15rem; border-radius: 999px; background: linear-gradient(140deg, #3f7fb0, #2b567c); color: #fff; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .team-send:disabled { opacity: .4; }

    @media (prefers-reduced-motion: reduce) { .team-panel, .team-msg { animation: none; } }
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
        const U = {
            messages: @json(route('sm.chat')),
            send: @json(route('sm.chat.send')),
            members: @json(route('sm.chat.members')),
            dmBase: @json(url('/app/community/messages')),
        };
        const CSRF = document.querySelector('meta[name=csrf-token]')?.content || '';

        let mode = 'group';           // 'group' | 'pm'
        let pmUser = null, pmName = '';
        let lastGroupId = 0;          // highest group message id seen
        let photoFile = null;
        let msgTimer = null, memTimer = null;
        let canSend = true;

        const scrollDown = () => { thread.scrollTop = thread.scrollHeight; };
        const faceHtml = (avatar, initials) => avatar
            ? `<img src="${escapeHtml(avatar)}" alt="">`
            : escapeHtml(initials || '·');

        /* ---------- rendering ---------- */
        function clearThread() { thread.innerHTML = ''; }
        function bubble(m, opts) {
            const el = document.createElement('div');
            el.className = 'team-msg' + (m.mine ? ' me' : '');
            const showWho = opts && opts.showSender && !m.mine;
            const face = showWho ? `<span class="face">${faceHtml(m.avatar, m.initials)}</span>` : '';
            const who = showWho ? `<span class="who">${escapeHtml(m.name || 'Member')}</span>` : '';
            const img = m.image ? `<img src="${escapeHtml(m.image)}" alt="">` : '';
            const body = m.body ? escapeHtml(m.body) : '';
            el.innerHTML = `${face}<div class="col">${who}<div class="b"${m.image ? ' data-lightbox' : ''}>${body}${img}</div><span class="at">${escapeHtml(m.at || '')}</span></div>`;
            thread.appendChild(el);
            return el;
        }

        /* ---------- group mode ---------- */
        async function loadMembers() {
            try {
                const res = await api(`${U.members}?scheduleId=${SCHEDULE_ID}`);
                const d = res.data;
                if (mode === 'group') $('teamSub').textContent = `${d.online} of ${d.total} online`;
                const wrap = $('teamMembers');
                wrap.innerHTML = (d.members || []).map((m) => {
                    const pmable = !m.isMe;
                    return `<div class="team-mem ${pmable ? 'pmable' : ''}" ${pmable ? `data-pm="${m.id}" data-name="${escapeHtml(m.name)}"` : ''} title="${escapeHtml(m.name)}${m.isOwner ? ' (owner)' : ''}">
                        <span class="team-mem-face">${faceHtml(m.avatar, m.initials)}<span class="team-mem-dot ${m.online ? 'on' : ''}"></span></span>
                        <span class="team-mem-name">${m.isMe ? 'You' : escapeHtml(m.name.split(' ')[0])}</span>
                    </div>`;
                }).join('');
            } catch (_) { /* keep last roster */ }
        }

        async function pollGroup() {
            try {
                const res = await api(`${U.messages}?scheduleId=${SCHEDULE_ID}&after=${lastGroupId}`);
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
                $('teamLoading')?.remove();
            } catch (_) { /* transient */ }
        }

        async function sendGroup() {
            const text = ($('teamText').value || '').trim();
            if (!text && !photoFile) return;
            const fd = new FormData();
            if (text) fd.append('body', text);
            if (photoFile) fd.append('image', photoFile);
            $('teamSend').disabled = true;
            try {
                const res = await api(`${U.send}?scheduleId=${SCHEDULE_ID}`, { method: 'POST', body: fd });
                const m = res.data.message;
                bubble(m, { showSender: true });
                lastGroupId = Math.max(lastGroupId, m.id);
                resetComposer(); scrollDown();
            } catch (err) { toast(err.message || 'Could not send.', 'error'); }
            finally { $('teamSend').disabled = false; }
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
        }
        function currentSend() { return mode === 'pm' ? sendPm() : sendGroup(); }

        /* ---------- open / close ---------- */
        function openPanel(open) {
            panel.classList.toggle('hidden', !open);
            $('teamChat').classList.toggle('is-open', open);
            if (open) {
                $('teamChatDot').classList.add('hidden');
                if (mode === 'group') { toGroup(); } else { startTimers(); }
                // On a phone the keyboard would swallow half the room before a
                // word of chat has been read; a tap on the box still opens it.
                if (!window.matchMedia('(pointer: coarse)').matches) {
                    setTimeout(() => $('teamText')?.focus(), 60);
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
