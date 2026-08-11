{{-- Persistent LiveKit call widget for the Collab Room. Mounted at the room
     root (NOT inside a tab), fixed-positioned, so a call keeps running while you
     switch tabs. Exposes window.startTeamCall() and window.callWorker(id,name);
     listens for incoming rings over the shared Pusher board channel.
     Expects: $schedule. --}}
<div id="collabCall" class="ccall hidden" data-schedule="{{ $schedule->id }}" aria-live="polite">
    <div class="ccall-head">
        <span class="ccall-dot"></span>
        <span class="ccall-title" id="ccallTitle">Call</span>
        <span class="ccall-count" id="ccallCount">1</span>
        <button type="button" id="ccallMin" class="ccall-mini" title="Minimize / expand" aria-label="Minimize">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14"/></svg>
        </button>
    </div>
    <div class="ccall-body">
        <div class="ccall-tiles" id="ccallTiles"></div>
        <div class="ccall-controls">
            <button type="button" id="ccallMic" class="ccall-btn" title="Mute / unmute" aria-label="Microphone">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15a3 3 0 003-3V6a3 3 0 00-6 0v6a3 3 0 003 3z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19 10v2a7 7 0 01-14 0v-2M12 19v3"/></svg>
            </button>
            <button type="button" id="ccallCam" class="ccall-btn" title="Camera on / off" aria-label="Camera">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10l4.55-2.28A1 1 0 0121 8.62v6.76a1 1 0 01-1.45.9L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
            </button>
            <button type="button" id="ccallLeave" class="ccall-btn ccall-leave" title="Leave the call" aria-label="Leave">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 9c-1.6 0-3.15.25-4.6.72v3.1c0 .39-.23.74-.56.9-.98.49-1.87 1.12-2.66 1.85a.9.9 0 01-1.27-.02L.29 13.4a.9.9 0 01.02-1.28C3.34 9.28 7.46 7.5 12 7.5s8.66 1.78 11.69 4.62a.9.9 0 01.02 1.28l-2.62 3.05a.9.9 0 01-1.27.02 12.4 12.4 0 00-2.66-1.85.99.99 0 01-.56-.9v-3.1A15.7 15.7 0 0012 9z"/></svg>
            </button>
        </div>
    </div>
    <div id="ccallAudioSink" style="display:none"></div>
</div>

{{-- Incoming-call prompt --}}
<div id="ccallRing" class="ccall-ring hidden">
    <span class="ccall-ring-face" id="ccallRingFace">📞</span>
    <div class="min-w-0 grow">
        <p class="ccall-ring-title" id="ccallRingTitle">Incoming call</p>
        <p class="ccall-ring-sub" id="ccallRingSub"></p>
    </div>
    <button type="button" id="ccallRingJoin" class="ccall-ring-join">Join</button>
    <button type="button" id="ccallRingNo" class="ccall-ring-no" aria-label="Dismiss">✕</button>
</div>

{{-- Connecting pill: a call takes a moment to stand up — say so, offer out. --}}
<div id="ccallConnecting" class="ccall-connecting hidden" role="status">
    <span class="ccall-conn-spin"></span>
    <span class="ccall-conn-text" id="ccallConnectingText">Starting the call…</span>
    <button type="button" id="ccallConnectingCancel" class="ccall-conn-cancel">Cancel</button>
</div>

<style>
    .ccall { position: fixed; right: 1rem; bottom: 1rem; z-index: 150; width: min(30rem, calc(100vw - 2rem));
        background: #111827; color: #fff; border-radius: 1rem; box-shadow: 0 16px 44px rgba(0,0,0,.4); overflow: hidden; }
    .ccall.hidden { display: none; }
    @media (max-width: 640px) { .ccall { left: .5rem; right: .5rem; width: auto; } }
    .ccall-head { display: flex; align-items: center; gap: .5rem; padding: .5rem .75rem; background: #0b1220; cursor: default; }
    .ccall-dot { width: .55rem; height: .55rem; border-radius: 999px; background: #22c55e; box-shadow: 0 0 0 0 rgb(34 197 94 / .5); animation: ccallPulse 1.8s ease-out infinite; }
    @keyframes ccallPulse { to { box-shadow: 0 0 0 8px rgb(34 197 94 / 0); } }
    .ccall-title { font-weight: 800; font-size: .9rem; }
    .ccall-count { font-size: .68rem; font-weight: 700; background: rgb(255 255 255 / .14); padding: .05rem .45rem; border-radius: 999px; }
    .ccall-mini { margin-left: auto; width: 1.9rem; height: 1.9rem; border-radius: .5rem; display: flex; align-items: center; justify-content: center; color: #cbd5e1; }
    .ccall-mini:hover { background: rgb(255 255 255 / .1); }
    .ccall.min .ccall-body { display: none; }
    .ccall-body { padding: .6rem; }
    .ccall-tiles { display: grid; grid-template-columns: repeat(auto-fill, minmax(7.5rem, 1fr)); gap: .5rem; max-height: 46vh; overflow-y: auto; }
    .ccall-tile { position: relative; aspect-ratio: 4/3; border-radius: .6rem; overflow: hidden; background: #1f2937; display: flex; align-items: center; justify-content: center; }
    .ccall-tile .ccall-av { width: 2.6rem; height: 2.6rem; border-radius: 999px; background: #374151; color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: .85rem; }
    .ccall-tile-media { position: absolute; inset: 0; }
    .ccall-tile-media video { width: 100%; height: 100%; object-fit: cover; background: #000; }
    .ccall-name { position: absolute; left: .35rem; bottom: .3rem; font-size: .62rem; font-weight: 700; background: rgb(0 0 0 / .5); padding: .05rem .4rem; border-radius: .4rem; max-width: 80%; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .ccall-tile .ccall-muted { position: absolute; right: .3rem; top: .3rem; width: 1.1rem; height: 1.1rem; border-radius: 999px; background: rgb(239 68 68 / .9); display: none; align-items: center; justify-content: center; }
    .ccall-tile.is-muted .ccall-muted { display: flex; }
    .ccall-tile-mute { position: absolute; left: .3rem; top: .3rem; width: 1.55rem; height: 1.55rem; border-radius: 999px; background: rgb(0 0 0 / .5); color: #fff; display: flex; align-items: center; justify-content: center; opacity: .55; transition: opacity .15s ease, background .15s ease; }
    .ccall-tile:hover .ccall-tile-mute { opacity: 1; }
    .ccall-tile-mute:hover { background: #ef4444; opacity: 1; }
    .ccall-tile-mute:disabled { opacity: .4; }
    .ccall-controls { display: flex; align-items: center; justify-content: center; gap: .6rem; padding-top: .6rem; }
    .ccall-btn { width: 2.7rem; height: 2.7rem; border-radius: 999px; display: flex; align-items: center; justify-content: center; background: rgb(255 255 255 / .12); color: #fff; }
    .ccall-btn:hover { background: rgb(255 255 255 / .2); }
    .ccall-btn.is-off { background: #ef4444; }
    .ccall-leave { background: #ef4444; } .ccall-leave:hover { background: #dc2626; }

    .ccall-connecting { position: fixed; top: 1rem; left: 50%; transform: translateX(-50%); z-index: 160;
        display: flex; align-items: center; gap: .65rem; padding: .6rem .8rem; border-radius: 999px;
        background: #0b1220; color: #fff; box-shadow: 0 16px 44px rgb(0 0 0 / .45);
        animation: ccallConnIn .28s cubic-bezier(.22,1,.36,1) both; }
    .ccall-connecting.hidden { display: none; }
    @keyframes ccallConnIn { from { opacity: 0; transform: translateX(-50%) translateY(-.5rem); } }
    .ccall-conn-spin { width: 1.15rem; height: 1.15rem; border-radius: 999px; flex-shrink: 0;
        border: 2.5px solid rgb(255 255 255 / .2); border-top-color: #22c55e;
        animation: ccallConnSpin .7s linear infinite; }
    @keyframes ccallConnSpin { to { transform: rotate(360deg); } }
    .ccall-conn-text { font-size: .82rem; font-weight: 700; }
    .ccall-conn-cancel { font-size: .74rem; font-weight: 800; color: #fca5a5; padding: .25rem .55rem;
        border-radius: 999px; background: rgb(255 255 255 / .08); }
    .ccall-conn-cancel:hover { background: rgb(255 255 255 / .16); }
    @media (prefers-reduced-motion: reduce) {
        .ccall-connecting { animation: none; }
        .ccall-conn-spin { animation-duration: 1.4s; }
    }

    .ccall-ring { position: fixed; top: 1rem; left: 50%; transform: translateX(-50%); z-index: 160; display: flex; align-items: center; gap: .7rem;
        width: min(24rem, calc(100vw - 2rem)); background: #fff; border: 1px solid var(--color-gray-200); border-radius: 1rem; box-shadow: 0 16px 44px rgba(0,0,0,.28); padding: .7rem .85rem; animation: ccallDrop .2s ease both; }
    @keyframes ccallDrop { from { opacity: 0; transform: translate(-50%, -12px); } to { opacity: 1; transform: translate(-50%, 0); } }
    .ccall-ring.hidden { display: none; }
    .ccall-ring-face { width: 2.4rem; height: 2.4rem; border-radius: 999px; background: var(--color-brand-50); display: flex; align-items: center; justify-content: center; font-size: 1.1rem; flex-shrink: 0; }
    .ccall-ring-title { font-weight: 800; font-size: .9rem; color: var(--color-gray-900); }
    .ccall-ring-sub { font-size: .78rem; color: var(--color-gray-500); }
    .ccall-ring-join { background: #22c55e; color: #fff; font-weight: 800; font-size: .85rem; padding: .45rem .9rem; border-radius: 999px; flex-shrink: 0; }
    .ccall-ring-join:hover { background: #16a34a; }
    .ccall-ring-no { width: 2rem; height: 2rem; border-radius: 999px; color: var(--color-gray-400); flex-shrink: 0; }
    .ccall-ring-no:hover { background: var(--color-gray-100); }
</style>

<script>
(() => {
    const init = () => {
        const panel = document.getElementById('collabCall');
        if (!panel || panel.dataset.bound) return;
        panel.dataset.bound = '1';
        const $ = (id) => document.getElementById(id);

        const SCHEDULE_ID = @json($schedule->id);
        const ME = @json((int) auth()->id());
        const OWNER = @json((int) $schedule->anisystemUserId);
        const IS_OWNER = ME === OWNER;   // only the boss may force-mute others
        const MY_INITIALS = @json(auth()->user()->initials ?? '·');
        const URLS = { token: @json(route('sm.call.token')), ring: @json(route('sm.call.ring')), end: @json(route('sm.call.end')), mute: @json(route('sm.call.mute')) };

        let LK = null, room = null, active = false, roomName = null, micOn = true, camOn = false;
        const tiles = new Map();   // participant sid → tile element
        let pendingRing = null;

        const initials = (name) => (String(name || '').trim().split(/\s+/).map((w) => w[0] || '').join('').slice(0, 2).toUpperCase() || '·');

        /* ---------- tiles ---------- */
        function ensureTile(p, isLocal) {
            const key = isLocal ? 'local' : p.sid;
            if (tiles.has(key)) return tiles.get(key);
            const el = document.createElement('div');
            el.className = 'ccall-tile';
            if (isLocal) el.dataset.local = '1'; else { el.dataset.identity = p.identity; el.dataset.sid = p.sid; }
            // Local tile: mute yourself. Remote tiles: the owner can force-mute.
            const canMute = isLocal || IS_OWNER;
            const muteBtn = canMute
                ? `<button type="button" class="ccall-tile-mute" title="${isLocal ? 'Mute yourself' : 'Mute this member'}" aria-label="Mute"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15a3 3 0 003-3V6a3 3 0 00-6 0v6a3 3 0 003 3z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19 10v2a7 7 0 01-11.9 5M5 10v2a7 7 0 001 3.6M12 19v3"/><path stroke-linecap="round" d="M3 3l18 18"/></svg></button>`
                : '';
            el.innerHTML = `<span class="ccall-av">${escapeHtml(isLocal ? MY_INITIALS : initials(p.name || p.identity))}</span>
                <div class="ccall-tile-media"></div>
                <span class="ccall-muted"><svg class="w-3 h-3" fill="none" stroke="#fff" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" d="M4 4l16 16"/></svg></span>
                ${muteBtn}
                <span class="ccall-name">${escapeHtml(isLocal ? 'You' : (p.name || 'Member'))}</span>`;
            $('ccallTiles').appendChild(el);
            tiles.set(key, el);
            return el;
        }
        function removeTile(sid) { const el = tiles.get(sid); if (el) { el.remove(); tiles.delete(sid); } }
        function setTileVideo(tile, track) {
            const holder = tile.querySelector('.ccall-tile-media');
            holder.innerHTML = '';
            const v = track.attach(); v.autoplay = true; v.playsInline = true; v.muted = true;
            holder.appendChild(v);
        }
        function refreshMuteBadges() {
            room?.remoteParticipants?.forEach((p) => {
                const el = tiles.get(p.sid); if (!el) return;
                el.classList.toggle('is-muted', !p.isMicrophoneEnabled);
            });
        }
        function updateCount() { $('ccallCount').textContent = String(1 + (room ? room.remoteParticipants.size : 0)); }

        /* ---------- LiveKit events ---------- */
        function wire() {
            room.on(LK.RoomEvent.TrackSubscribed, (track, pub, participant) => {
                if (track.kind === 'video') setTileVideo(ensureTile(participant), track);
                else if (track.kind === 'audio') { const a = track.attach(); a.autoplay = true; $('ccallAudioSink').appendChild(a); }
            });
            room.on(LK.RoomEvent.TrackUnsubscribed, (track) => { try { track.detach().forEach((el) => el.remove()); } catch (_) {} });
            room.on(LK.RoomEvent.ParticipantConnected, (p) => { ensureTile(p); updateCount(); });
            room.on(LK.RoomEvent.ParticipantDisconnected, (p) => { removeTile(p.sid); updateCount(); });
            room.on(LK.RoomEvent.TrackMuted, refreshMuteBadges);
            room.on(LK.RoomEvent.TrackUnmuted, refreshMuteBadges);
            room.on(LK.RoomEvent.LocalTrackPublished, (pub) => { if (pub.track && pub.track.kind === 'video') setTileVideo(ensureTile(room.localParticipant, true), pub.track); });
            room.on(LK.RoomEvent.Disconnected, () => cleanup());
        }

        /* ---------- join / leave ---------- */
        /* Standing a call up takes visible seconds (token, module, connect) —
           the pill says so the moment the button is pressed, and Cancel is
           honoured at every await boundary. */
        let joining = false, joinCancelled = false;
        const showConnecting = (t) => { $('ccallConnectingText').textContent = t || 'Starting the call…'; $('ccallConnecting').classList.remove('hidden'); };
        const hideConnecting = () => $('ccallConnecting').classList.add('hidden');
        $('ccallConnectingCancel').addEventListener('click', () => { joinCancelled = true; hideConnecting(); });
        async function joinRoom(kind, withUserId, title) {
            if (active) { showPanel(); return true; }
            if (joining) return false;
            joining = true; joinCancelled = false;
            showConnecting(kind === 'group' ? 'Starting the team call…' : 'Calling…');
            try {
            let data;
            try {
                const res = await api(URLS.token, { method: 'POST', body: { scheduleId: SCHEDULE_ID, kind, withUserId } });
                data = res.data;
            } catch (err) { if (window.toast) toast(err.message || 'Could not start the call.', 'error'); return false; }
            if (joinCancelled) return false;
            try { LK = LK || await window.loadLivekit(); } catch (_) { if (window.toast) toast('Could not load the call module.', 'error'); return false; }
            if (joinCancelled) return false;

            room = new LK.Room({ adaptiveStream: true, dynacast: true });
            roomName = data.room; active = true;
            $('ccallTitle').textContent = title || 'Call';
            wire();
            try {
                await room.connect(data.url || window.LIVEKIT_URL, data.token);
            } catch (err) {
                if (window.toast) toast('Could not connect: ' + (err && err.message ? err.message : err), 'error');
                cleanup(); return false;
            }
            if (joinCancelled) { cleanup(); return false; }
            // Connected. Enabling the mic needs a SECURE context (https:// or
            // localhost) + permission — if it fails, stay in the call, muted.
            camOn = false;
            const secureCtx = window.isSecureContext || /^(localhost|127\.|\[::1\])/.test(location.hostname);
            try {
                await room.localParticipant.setMicrophoneEnabled(true);
                micOn = true;
            } catch (err) {
                micOn = false;
                if (window.toast) toast(secureCtx ? 'Joined — but mic permission was blocked.' : 'Joined — but your mic/camera need HTTPS (open the app over https://).', 'error');
            }

            ensureTile(room.localParticipant, true);
            room.remoteParticipants.forEach((p) => {
                ensureTile(p);
                p.trackPublications.forEach((pub) => { if (pub.track) { if (pub.track.kind === 'video') setTileVideo(tiles.get(p.sid), pub.track); else if (pub.track.kind === 'audio') { const a = pub.track.attach(); a.autoplay = true; $('ccallAudioSink').appendChild(a); } } });
            });
            showPanel(); updateCount(); refreshMuteBadges(); paintMic(); paintCam();
            return true;
            } finally { joining = false; hideConnecting(); }
        }
        function cleanup() {
            try { room && room.disconnect(); } catch (_) {}
            tiles.forEach((el) => el.remove()); tiles.clear();
            $('ccallAudioSink').innerHTML = '';
            room = null; active = false; roomName = null;
            panel.classList.add('hidden');
        }
        async function leave() {
            const rn = roomName;
            cleanup();
            try { await api(URLS.end, { method: 'POST', body: { scheduleId: SCHEDULE_ID } }); } catch (_) {}
        }

        /* ---------- controls ---------- */
        const showPanel = () => { panel.classList.remove('hidden', 'min'); };
        function paintMic() { $('ccallMic').classList.toggle('is-off', !micOn); const t = tiles.get('local'); if (t) t.classList.toggle('is-muted', !micOn); }
        function paintCam() { $('ccallCam').classList.toggle('is-off', !camOn); }
        async function toggleMic() { if (!room) return; micOn = !micOn; await room.localParticipant.setMicrophoneEnabled(micOn); paintMic(); }
        $('ccallMic').addEventListener('click', toggleMic);

        /* Force-mute (owner only) via the LiveKit server, from a remote tile. */
        function micPubOf(p) {
            let found = null;
            const src = (LK && LK.Track && LK.Track.Source) ? LK.Track.Source.Microphone : 'microphone';
            p.trackPublications.forEach((pub) => { if (!found && (pub.kind === 'audio' || pub.source === src)) found = pub; });
            return found;
        }
        async function forceMute(identity, btn) {
            if (!room || !identity) return;
            const p = room.remoteParticipants.get(identity);
            if (!p) return;
            const pub = micPubOf(p);
            if (!pub || !pub.trackSid) { if (window.toast) toast('That member has no active microphone.', 'error'); return; }
            if (btn) btn.disabled = true;
            try {
                await api(URLS.mute, { method: 'POST', body: { scheduleId: SCHEDULE_ID, room: roomName, identity, trackSid: pub.trackSid, muted: true } });
                if (window.toast) toast('Muted ' + (p.name || 'member') + '.', 'success');
            } catch (err) { if (window.toast) toast(err.message || 'Could not mute that member.', 'error'); }
            finally { if (btn) btn.disabled = false; }
        }
        // Tile mute buttons: your own tile self-mutes; remote tiles force-mute.
        $('ccallTiles').addEventListener('click', (e) => {
            const b = e.target.closest('.ccall-tile-mute'); if (!b) return;
            const tile = b.closest('.ccall-tile');
            if (tile && tile.dataset.local === '1') { toggleMic(); return; }
            forceMute(tile && tile.dataset.identity, b);
        });
        $('ccallCam').addEventListener('click', async () => {
            if (!room) return; camOn = !camOn;
            try { await room.localParticipant.setCameraEnabled(camOn); }
            catch (_) { camOn = !camOn; if (window.toast) toast('Camera not available.', 'error'); }
            if (!camOn) { const t = tiles.get('local'); if (t) t.querySelector('.ccall-tile-media').innerHTML = ''; }
            paintCam();
        });
        $('ccallLeave').addEventListener('click', leave);
        $('ccallMin').addEventListener('click', () => panel.classList.toggle('min'));

        /* ---------- public triggers ---------- */
        window.startTeamCall = async () => {
            if (await joinRoom('group', null, 'Team call')) { try { await api(URLS.ring, { method: 'POST', body: { scheduleId: SCHEDULE_ID, kind: 'group' } }); } catch (_) {} }
        };
        window.callWorker = async (userId, name) => {
            if (await joinRoom('worker', userId, 'Call · ' + (name || 'worker'))) { try { await api(URLS.ring, { method: 'POST', body: { scheduleId: SCHEDULE_ID, kind: 'worker', withUserId: userId } }); } catch (_) {} }
        };

        /* ---------- incoming ring ---------- */
        function showRing(p) {
            pendingRing = p;
            $('ccallRingFace').textContent = p.kind === 'group' ? '👥' : '📞';
            $('ccallRingTitle').textContent = p.kind === 'group' ? 'Team call' : (p.fromName + ' is calling');
            $('ccallRingSub').textContent = p.kind === 'group' ? (p.fromName + ' started a team call') : 'Tap Join to answer';
            $('ccallRing').classList.remove('hidden');
        }
        const hideRing = () => { $('ccallRing').classList.add('hidden'); pendingRing = null; };
        $('ccallRingNo').addEventListener('click', hideRing);
        $('ccallRingJoin').addEventListener('click', () => {
            const p = pendingRing; hideRing();
            if (!p) return;
            if (p.kind === 'group') window.startTeamCall ? joinRoom('group', null, 'Team call') : null;
            else joinRoom('worker', p.fromUserId, 'Call · ' + p.fromName);
        });

        if (window.Echo) {
            try {
                const ch = window.Echo.private('schedule-board.' + SCHEDULE_ID);
                ch.listen('.call.ring', (p) => {
                    if (!p || p.fromUserId === ME) return;
                    if (active && roomName === p.room) return;
                    if (p.kind === 'worker' && p.toUserId !== ME) return;
                    showRing(p);
                });
                ch.listen('.call.ended', (p) => { if (pendingRing && pendingRing.room === p.room) hideRing(); });
            } catch (_) {}
        }
    };
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init, { once: true });
    else init();
})();
</script>
