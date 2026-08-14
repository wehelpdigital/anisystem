{{-- Collab Room "AI Technician" tab: shared AI conversations for the team.
     A sessions sidebar (visible to everyone) lets the team keep several named
     threads; any member asks, the question broadcasts instantly and the answer
     when it returns. Funded by the schedule owner's credits. Sessions can be
     saved to the schedule notebook. Expects: $schedule. --}}
<div class="sai-wrap" id="saiWrap" data-schedule="{{ $schedule->id }}">
    <div class="sai-layout">
        {{-- Sessions sidebar (team-visible) --}}
        <aside class="sai-sessions" id="saiSessions">
            <div class="sai-sessions-head">
                <span>Sessions</span>
                <button type="button" id="saiNewSession" class="sai-new" title="Start a new session">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/></svg>
                </button>
            </div>
            <div class="sai-sessions-list" id="saiSessionsList">
                <div class="sai-sessions-empty">Loading…</div>
            </div>
        </aside>

        <div class="sai-main">
            <div class="sai-head">
                <button type="button" id="saiSessToggle" class="sai-sess-toggle" title="Show sessions" aria-label="Show sessions">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
                <span class="sai-title">🤖 AI Technician <span class="sai-sub">· shared with your team</span></span>
                <span class="sai-spacer"></span>
                <button type="button" id="saiSaveSession" class="sai-save" title="Save this session to the schedule notes">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 5a1 1 0 011-1h9l4 4v10a1 1 0 01-1 1H6a1 1 0 01-1-1V5z"/><path stroke-linecap="round" stroke-linejoin="round" d="M8 4v4h6M8 19v-5h8v5"/></svg>
                    <span class="hidden sm:inline">Save</span>
                </button>
                <a href="{{ route('ai.credits') }}" class="sai-credits" title="Owner's AI credits fund the team">
                    <svg class="w-3 h-3" viewBox="0 0 20 20" fill="currentColor"><path d="M10 2a8 8 0 100 16 8 8 0 000-16zm.75 4.5v.63a2.5 2.5 0 01.2 4.84v.78a.75.75 0 01-1.5 0v-.75a2.6 2.6 0 01-1.83-1.1.75.75 0 011.24-.84c.24.35.63.57 1.09.57.6 0 1.05-.36 1.05-.83 0-.44-.3-.7-1.2-.95-1.13-.32-2.05-.8-2.05-2.05a2.2 2.2 0 011.5-2.03V6.5a.75.75 0 011.5 0z"/></svg>
                    <span id="saiBalance">…</span>&nbsp;<span class="hidden sm:inline">credits</span>
                </a>
            </div>
            <div class="sai-thread" id="saiThread"><div class="sai-loading" id="saiLoading">Loading…</div></div>
            <div class="sai-composer">
                <div id="saiPhotoChip" class="sai-photochip hidden"><img src="" alt="" id="saiPhotoThumb"><span class="grow">Photo attached</span><button type="button" id="saiPhotoRemove" class="text-red-600 font-bold">Remove</button></div>
                <div class="sai-box">
                    <label class="sai-cam" title="Attach a photo">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        <input type="file" id="saiPhoto" accept="image/*" class="hidden">
                    </label>
                    <textarea id="saiText" rows="1" maxlength="4000" placeholder="Ask the AI — the whole team sees the reply…"></textarea>
                    <button type="button" id="saiSend" class="sai-send" aria-label="Send">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14m0 0l-6-6m6 6l-6 6"/></svg>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .sai-wrap { display: flex; flex-direction: column; height: 100%; width: 100%; min-height: 0; }
    .sai-layout { flex: 1 1 auto; display: flex; min-height: 0; position: relative; }
    /* Sessions sidebar */
    .sai-sessions { width: 13.5rem; flex-shrink: 0; display: flex; flex-direction: column; min-height: 0; background: var(--color-gray-50); border-right: 1px solid var(--color-gray-100); transition: transform .28s cubic-bezier(.22,1,.36,1); }
    .sai-sessions-head { display: flex; align-items: center; justify-content: space-between; padding: .6rem .7rem .4rem; font-size: .7rem; font-weight: 800; letter-spacing: .03em; text-transform: uppercase; color: var(--color-gray-400); }
    .sai-new { width: 1.6rem; height: 1.6rem; border-radius: .45rem; display: inline-flex; align-items: center; justify-content: center; color: var(--color-brand-700); background: var(--color-brand-50); }
    .sai-new:hover { background: var(--color-brand-100); }
    .sai-sessions-list { flex: 1 1 auto; overflow-y: auto; padding: 0 .4rem .5rem; scrollbar-width: thin; display: flex; flex-direction: column; gap: .2rem; }
    .sai-sessions-empty { color: var(--color-gray-400); font-size: .78rem; padding: .5rem .4rem; }
    .sai-sess { text-align: left; padding: .4rem .5rem; border-radius: .55rem; display: flex; flex-direction: column; gap: .05rem; color: var(--color-gray-700); transition: background .15s ease; }
    .sai-sess:hover { background: var(--color-gray-100); }
    .sai-sess.is-active { background: var(--color-white); box-shadow: 0 1px 3px rgb(0 0 0 / .1); }
    .sai-sess-title { font-size: .82rem; font-weight: 700; color: var(--color-gray-900); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .sai-sess.is-active .sai-sess-title { color: var(--color-brand-700); }
    .sai-sess-meta { font-size: .62rem; color: var(--color-gray-400); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    /* Main column */
    .sai-main { flex: 1 1 auto; display: flex; flex-direction: column; min-width: 0; min-height: 0; }
    .sai-head { display: flex; align-items: center; gap: .5rem; padding: .55rem .8rem; border-bottom: 1px solid var(--color-gray-100); }
    .sai-spacer { flex: 1 1 auto; }
    .sai-sess-toggle { display: none; width: 2rem; height: 2rem; border-radius: .5rem; align-items: center; justify-content: center; color: var(--color-gray-500); background: var(--color-gray-100); flex-shrink: 0; }
    .sai-save { display: inline-flex; align-items: center; gap: .3rem; padding: .3rem .55rem; border-radius: .6rem; font-size: .74rem; font-weight: 700; color: var(--color-gray-600); background: var(--color-gray-100); flex-shrink: 0; }
    .sai-save:hover { background: var(--color-gray-200); color: var(--color-gray-800); }
    .sai-save:disabled { opacity: .5; }
    .sai-title { font-weight: 800; font-size: .9rem; color: var(--color-gray-900); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .sai-sub { font-weight: 600; font-size: .72rem; color: var(--color-gray-400); }
    .sai-credits { display: inline-flex; align-items: center; gap: .25rem; padding: .12rem .5rem; border-radius: 999px; background: rgb(245 197 24 / .16); color: #8a6100; font-size: .72rem; font-weight: 800; font-variant-numeric: tabular-nums; flex-shrink: 0; }
    .sai-thread { flex: 1 1 auto; overflow-y: auto; padding: .8rem; display: flex; flex-direction: column; gap: .1rem; scrollbar-width: thin; }
    .sai-loading { margin: auto; color: var(--color-gray-400); font-size: .85rem; }
    /* Intro card shown when a session is empty */
    .sai-intro { margin: auto; max-width: 30rem; text-align: center; color: var(--color-gray-500); padding: 1.5rem 1rem; animation: saiRise .28s cubic-bezier(.22,1,.36,1) both; }
    .sai-intro-badge { width: 3rem; height: 3rem; border-radius: 999px; margin: 0 auto .7rem; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; background: linear-gradient(150deg, #6b9f3d, #3d6823); color: #fff; box-shadow: 0 6px 18px rgb(61 104 35 / .3); }
    .sai-intro h4 { font-family: var(--font-heading); font-weight: 800; font-size: 1rem; color: var(--color-gray-900); margin-bottom: .35rem; }
    .sai-intro p { font-size: .85rem; line-height: 1.55; }
    .sai-intro .sai-chips { display: flex; flex-wrap: wrap; gap: .35rem; justify-content: center; margin-top: .9rem; }
    .sai-chip { padding: .3rem .6rem; border-radius: 999px; background: var(--color-gray-100); color: var(--color-gray-700); font-size: .74rem; font-weight: 700; transition: background .15s ease; }
    .sai-chip:hover { background: var(--color-brand-50); color: var(--color-brand-700); }
    .sai-msg { display: flex; gap: .45rem; margin-bottom: .6rem; align-items: flex-end; animation: saiRise .24s cubic-bezier(.22,1,.36,1) both; }
    .sai-msg.me { flex-direction: row-reverse; }
    @keyframes saiRise { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: none; } }
    .sai-face { width: 1.8rem; height: 1.8rem; border-radius: 999px; flex-shrink: 0; display: flex; align-items: center; justify-content: center; font-size: .62rem; font-weight: 800; background: var(--color-brand-50); color: var(--color-brand-700); }
    .sai-face.bot { background: linear-gradient(150deg, #6b9f3d, #3d6823); color: #fff; }
    .sai-col { max-width: 82%; display: flex; flex-direction: column; }
    .sai-msg.me .sai-col { align-items: flex-end; }
    .sai-who { font-size: .64rem; font-weight: 700; color: var(--color-gray-500); margin: 0 .25rem .1rem; }
    .sai-b { padding: .5rem .75rem; font-size: .92rem; line-height: 1.5; background: var(--color-gray-100); color: var(--color-gray-900); border-radius: .9rem .9rem .9rem .25rem; word-break: break-word; }
    .sai-msg.me .sai-b { background: linear-gradient(135deg, #4a7c2a, #3d6823); color: #fff; border-radius: .9rem .9rem .25rem .9rem; }
    .sai-b.bot { background: var(--color-white); border: 1px solid var(--color-gray-100); box-shadow: 0 1px 2px rgb(26 26 26 / .06); }
    .sai-b p { margin: .25rem 0; } .sai-b p:first-child { margin-top: 0; } .sai-b p:last-child { margin-bottom: 0; }
    .sai-b ul { list-style: disc; padding-left: 1.1rem; margin: .25rem 0; } .sai-b ol { list-style: decimal; padding-left: 1.25rem; margin: .25rem 0; }
    .sai-b img { max-width: 100%; max-height: 180px; border-radius: .5rem; margin-top: .3rem; }
    .sai-cost { display: inline-flex; align-items: center; gap: .25rem; margin-top: .35rem; padding: .1rem .45rem; border-radius: 999px; font-size: .62rem; font-weight: 800; color: #8a6100; background: rgb(245 197 24 / .15); }
    .sai-dots { display: inline-flex; gap: .2rem; align-items: center; height: 1rem; }
    .sai-dots i { width: .35rem; height: .35rem; border-radius: 999px; background: var(--color-brand-500); opacity: .35; animation: saidot .9s cubic-bezier(.4,0,.2,1) infinite; }
    .sai-dots i:nth-child(2) { animation-delay: .15s; } .sai-dots i:nth-child(3) { animation-delay: .3s; }
    @keyframes saidot { 0%,60%,100% { opacity: .3; transform: translateY(0); } 30% { opacity: 1; transform: translateY(-3px); } }
    .sai-composer { flex-shrink: 0; padding: .55rem .7rem .7rem; border-top: 1px solid var(--color-gray-100); }
    .sai-photochip { display: flex; align-items: center; gap: .4rem; font-size: .72rem; font-weight: 600; color: var(--color-gray-500); margin-bottom: .4rem; background: var(--color-gray-100); border-radius: .6rem; padding: .3rem .5rem; }
    .sai-photochip.hidden { display: none; }
    .sai-photochip img { width: 1.9rem; height: 1.9rem; border-radius: .4rem; object-fit: cover; }
    .sai-box { display: flex; align-items: flex-end; gap: .25rem; border: 1.5px solid var(--color-gray-200); border-radius: 1.1rem; padding: .2rem .2rem .2rem .4rem; background: var(--color-white); }
    .sai-box:focus-within { border-color: var(--color-brand-500); box-shadow: 0 0 0 3px rgb(107 159 61 / .18); }
    .sai-cam { width: 2.15rem; height: 2.15rem; border-radius: .7rem; display: flex; align-items: center; justify-content: center; flex-shrink: 0; background: var(--color-brand-50); color: var(--color-brand-700); cursor: pointer; }
    #saiText { resize: none; border: 0; outline: none; background: transparent; flex: 1 1 auto; max-height: 6rem; padding: .4rem .25rem; font-size: .92rem; color: inherit; }
    .sai-send { width: 2.15rem; height: 2.15rem; border-radius: 999px; background: linear-gradient(140deg, #6b9f3d, #3d6823); color: #fff; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .sai-send:disabled { opacity: .4; }
    /* Sidebar backdrop on mobile */
    .sai-backdrop { position: absolute; inset: 0; z-index: 4; background: rgb(17 24 39 / .35); opacity: 0; visibility: hidden; transition: opacity .28s cubic-bezier(.22,1,.36,1), visibility .28s; }
    .sai-backdrop.on { opacity: 1; visibility: visible; }
    @media (max-width: 640px) {
        .sai-sess-toggle { display: inline-flex; }
        .sai-sessions { position: absolute; inset: 0 auto 0 0; z-index: 5; width: min(15rem, 82%); box-shadow: 8px 0 26px rgb(0 0 0 / .18); }
        .sai-sessions.collapsed { transform: translateX(-102%); }
    }
    @media (prefers-reduced-motion: reduce) {
        .sai-sessions, .sai-backdrop { transition: none; }
    }
</style>

<script>
(() => {
    const init = () => {
        const wrap = document.getElementById('saiWrap');
        if (!wrap || wrap.dataset.bound) return;
        wrap.dataset.bound = '1';
        const $ = (id) => document.getElementById(id);
        const thread = $('saiThread');

        const SCHEDULE_ID = @json($schedule->id);
        const ME = @json((int) auth()->id());
        const MY_INITIALS = @json(auth()->user()->initials ?? '');
        const U = {
            messages: @json(route('sm.ai.group.messages')),
            ask: @json(route('sm.ai.group.ask')),
            photo: @json(route('ai.photo')),
            sessions: @json(route('sm.ai.group.sessions')),
            sessionCreate: @json(route('sm.ai.group.session-create')),
            sessionNote: @json(route('sm.ai.group.session-note')),
        };

        const rendered = new Set();
        let lastId = 0, photoPath = null, busy = false, started = false, pollTimer = null, sessTimer = null, channel = null, thinkingEl = null;
        let currentSession = null, sessions = [], sessReq = 0;

        const esc = (s) => String(s == null ? '' : s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
        const scrollDown = () => { thread.scrollTop = thread.scrollHeight; };

        // Tiny markdown-ish formatter (same behaviour as the AI float).
        function render(text) {
            const lines = esc(text || '').split(/\r?\n/); let html = ''; let list = null;
            const close = () => { if (list) { html += `</${list}>`; list = null; } };
            const inline = (s) => s.replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>');
            for (const raw of lines) {
                const line = raw.trim();
                if (!line) { close(); continue; }
                const b = line.match(/^[-*•]\s+(.*)$/), n = line.match(/^\d+[.)]\s+(.*)$/);
                if (b) { if (list !== 'ul') { close(); html += '<ul>'; list = 'ul'; } html += '<li>' + inline(b[1]) + '</li>'; }
                else if (n) { if (list !== 'ol') { close(); html += '<ol>'; list = 'ol'; } html += '<li>' + inline(n[1]) + '</li>'; }
                else { close(); html += '<p>' + inline(line) + '</p>'; }
            }
            close(); return html || '<p></p>';
        }
        const faceHtml = (m) => m.role === 'assistant'
            ? '<span class="sai-face bot">AI</span>'
            : `<span class="sai-face">${esc(m.mine ? MY_INITIALS : (m.initials || '·'))}</span>`;

        /* ---------- intro (shown when a session is empty) ---------- */
        function showIntro() {
            if ($('saiIntro')) return;
            $('saiLoading')?.remove();
            const el = document.createElement('div');
            el.className = 'sai-intro'; el.id = 'saiIntro';
            el.innerHTML = `
                <div class="sai-intro-badge">🤖</div>
                <h4>Hi team — I'm your AI Technician</h4>
                <p>Ask me anything about this cropping plan: pests &amp; diseases, fertilizer rates and timing, irrigation, planting and harvest windows, or troubleshooting a problem. Everyone on the team sees the questions and answers, and you can save a whole session to your schedule notes.</p>
                <div class="sai-chips">
                    <button type="button" class="sai-chip" data-ask="What pests should I watch for at this crop stage?">Pests to watch</button>
                    <button type="button" class="sai-chip" data-ask="Suggest a fertilizer schedule for this crop.">Fertilizer plan</button>
                    <button type="button" class="sai-chip" data-ask="How much should I irrigate this week?">Irrigation</button>
                </div>`;
            thread.appendChild(el);
            el.querySelectorAll('.sai-chip').forEach((c) => c.addEventListener('click', () => {
                $('saiText').value = c.getAttribute('data-ask'); send();
            }));
        }
        const clearIntro = () => $('saiIntro')?.remove();

        function addMsg(m) {
            if (m.id) { if (rendered.has(m.id)) return; rendered.add(m.id); if (m.id > lastId) lastId = m.id; }
            $('saiLoading')?.remove(); clearIntro();
            const me = !!m.mine;
            const el = document.createElement('div');
            el.className = 'sai-msg' + (me ? ' me' : '');
            const who = (!me && m.role === 'user') ? `<span class="sai-who">${esc(m.name || 'Member')}</span>` : '';
            const body = m.role === 'assistant' ? render(m.content) : ('<p>' + esc(m.content).replace(/\r?\n/g, '<br>') + '</p>');
            const img = m.image ? `<img src="${esc(m.image)}" alt="">` : '';
            const cost = (m.role === 'assistant' && m.creditsCharged) ? `<p class="sai-cost">${esc(String(Math.round(m.creditsCharged * 100) / 100))} credits</p>` : '';
            el.innerHTML = `${faceHtml(m)}<div class="sai-col">${who}<div class="sai-b ${m.role === 'assistant' ? 'bot' : ''}">${body}${img}${cost}</div></div>`;
            thread.appendChild(el); scrollDown();
        }
        function showThinking() {
            clearThinking(); clearIntro();
            const el = document.createElement('div');
            el.className = 'sai-msg';
            el.innerHTML = '<span class="sai-face bot">AI</span><div class="sai-col"><div class="sai-b bot"><span class="sai-dots"><i></i><i></i><i></i></span></div></div>';
            thread.appendChild(el); thinkingEl = el; scrollDown();
        }
        function clearThinking() { if (thinkingEl) { thinkingEl.remove(); thinkingEl = null; } }
        function setBalance(v) { if (v !== undefined && v !== null) $('saiBalance').textContent = String(Math.round(v * 100) / 100); }

        /* ---------- sessions sidebar ---------- */
        function renderSessions() {
            const list = $('saiSessionsList');
            if (!sessions.length) { list.innerHTML = '<div class="sai-sessions-empty">No sessions yet.</div>'; return; }
            list.innerHTML = '';
            sessions.forEach((s) => {
                const b = document.createElement('button');
                b.type = 'button';
                b.className = 'sai-sess' + (s.id === currentSession ? ' is-active' : '');
                b.innerHTML = `<span class="sai-sess-title">${esc(s.title)}</span><span class="sai-sess-meta">${esc(s.startedBy)} · ${esc(s.at || '')}</span>`;
                b.addEventListener('click', () => { openSession(s.id); closeSidebarMobile(); });
                list.appendChild(b);
            });
        }
        function upsertSession(s) {
            const i = sessions.findIndex((x) => x.id === s.id);
            // Replace in place on update; a brand-new session goes to the top (newest).
            if (i >= 0) sessions[i] = s; else sessions.unshift(s);
            renderSessions();
        }
        async function loadSessions(selectId) {
            const my = ++sessReq;
            try {
                const r = await api(`${U.sessions}?scheduleId=${SCHEDULE_ID}${currentSession ? '&sessionId=' + currentSession : ''}`);
                if (my !== sessReq) return;
                sessions = r.data.sessions || [];
                if (selectId) currentSession = selectId;
                else if (!currentSession) currentSession = r.data.currentId;
                renderSessions();
            } catch (_) { /* keep last */ }
        }

        async function openSession(id) {
            if (id === currentSession && started && !$('saiLoading')) return;
            currentSession = id;
            renderSessions();
            rendered.clear(); lastId = 0; clearThinking();
            thread.innerHTML = '<div class="sai-loading" id="saiLoading">Loading…</div>';
            try {
                const res = await api(`${U.messages}?scheduleId=${SCHEDULE_ID}&sessionId=${currentSession}&after=0`);
                currentSession = res.data.sessionId || currentSession;
                $('saiLoading')?.remove();
                const msgs = res.data.messages || [];
                if (!msgs.length) showIntro(); else msgs.forEach(addMsg);
                if (res.data.maxId > lastId) lastId = res.data.maxId;
                setBalance(res.data.balance);
            } catch (_) { const l = $('saiLoading'); if (l) l.textContent = 'Could not load.'; }
        }

        async function newSession() {
            try {
                const r = await api(`${U.sessionCreate}?scheduleId=${SCHEDULE_ID}`, { method: 'POST', body: {} });
                if (r.data && r.data.session) { upsertSession(r.data.session); await openSession(r.data.session.id); closeSidebarMobile(); }
            } catch (err) { if (window.toast) toast(err.message || 'Could not start a session.', 'error'); }
        }

        async function saveSession(e) {
            const btn = e.currentTarget; if (btn.disabled || !currentSession) return;
            btn.disabled = true;
            try {
                const r = await api(`${U.sessionNote}?scheduleId=${SCHEDULE_ID}`, { method: 'POST', body: { sessionId: currentSession } });
                if (window.toast) toast((r && r.message) || 'Saved to the schedule notebook.', 'success');
            } catch (err) { if (window.toast) toast(err.message || 'Could not save this session.', 'error'); }
            finally { btn.disabled = false; }
        }

        const refreshSessionsSoon = (() => {
            let t = null;
            return () => { clearTimeout(t); t = setTimeout(() => loadSessions(), 400); };
        })();

        /* ---------- ask / realtime ---------- */
        async function send() {
            if (busy) return;
            const text = ($('saiText').value || '').trim();
            if (!text && !photoPath) return;
            if (!currentSession) { await loadSessions(); }
            busy = true; $('saiSend').disabled = true; clearIntro();
            addMsg({ id: null, role: 'user', mine: true, content: text, image: photoPath ? $('saiPhotoThumb').src : null });
            $('saiText').value = ''; $('saiText').style.height = 'auto';
            showThinking();
            try {
                const res = await api(U.ask + `?scheduleId=${SCHEDULE_ID}`, { method: 'POST', body: { message: text, imagePath: photoPath, sessionId: currentSession } });
                if (res.data.question && res.data.question.id) { rendered.add(res.data.question.id); lastId = Math.max(lastId, res.data.question.id); }
                clearThinking();
                addMsg(res.data.answer); setBalance(res.data.balance);
                photoPath = null; $('saiPhotoChip').classList.add('hidden');
                refreshSessionsSoon();
            } catch (err) {
                clearThinking();
                addMsg({ role: 'assistant', content: err.message || 'The AI could not answer.' });
                if (err.data && err.data.outOfCredits) setBalance(err.data.balance);
            } finally { busy = false; $('saiSend').disabled = false; $('saiText').focus(); }
        }

        function onQuestion(m) {
            if (m.sessionId && m.sessionId !== currentSession) { refreshSessionsSoon(); return; }
            if (m.id && rendered.has(m.id)) { showThinking(); return; }
            if (!(m.userId === ME)) addMsg(m); else if (m.id) { rendered.add(m.id); if (m.id > lastId) lastId = m.id; }
            showThinking();
        }
        function onAnswer(m) {
            if (m.sessionId && m.sessionId !== currentSession) { refreshSessionsSoon(); return; }
            clearThinking();
            if (m.error) { addMsg({ role: 'assistant', content: m.content || 'The AI could not answer.' }); return; }
            addMsg(m); setBalance(m.balance);
        }
        function onSession(payload) {
            if (payload && payload.session) upsertSession(payload.session);
        }

        async function reconcile() {
            if (!currentSession) return;
            try {
                const res = await api(`${U.messages}?scheduleId=${SCHEDULE_ID}&sessionId=${currentSession}&after=${lastId}`);
                (res.data.messages || []).forEach(addMsg);
                setBalance(res.data.balance);
            } catch (_) {}
        }
        async function start() {
            if (started) return; started = true;
            await loadSessions();
            await openSession(currentSession);
            if (window.Echo) {
                try {
                    channel = window.Echo.private('schedule-board.' + SCHEDULE_ID);
                    channel.listen('.ai.question', onQuestion).listen('.ai.answer', onAnswer).listen('.ai.session', onSession);
                } catch (_) {}
            }
            // Cadence follows the socket's real state, so a Pusher key that
            // never connects polls fast rather than backing off to 6s.
            const live = () => window.realtimeReady?.() ?? false;
            const tick = async () => { await reconcile(); pollTimer = setTimeout(tick, live() ? 6000 : 2500); };
            const sessTick = async () => { await loadSessions(); sessTimer = setTimeout(sessTick, live() ? 20000 : 12000); };
            pollTimer = setTimeout(tick, 2500);
            sessTimer = setTimeout(sessTick, 12000);
        }

        /* ---------- sidebar toggle (mobile) ---------- */
        function ensureBackdrop() {
            let bd = $('saiBackdrop');
            if (!bd) { bd = document.createElement('div'); bd.id = 'saiBackdrop'; bd.className = 'sai-backdrop'; bd.addEventListener('click', closeSidebarMobile); wrap.querySelector('.sai-layout').appendChild(bd); }
            return bd;
        }
        function openSidebarMobile() { $('saiSessions').classList.remove('collapsed'); ensureBackdrop().classList.add('on'); }
        function closeSidebarMobile() { if (window.matchMedia('(max-width: 640px)').matches) { $('saiSessions').classList.add('collapsed'); $('saiBackdrop')?.classList.remove('on'); } }
        // Start collapsed on mobile.
        if (window.matchMedia('(max-width: 640px)').matches) $('saiSessions').classList.add('collapsed');

        // Composer + control wiring.
        const input = $('saiText');
        input.addEventListener('input', () => { input.style.height = 'auto'; input.style.height = Math.min(input.scrollHeight, 96) + 'px'; });
        input.addEventListener('keydown', (e) => { if (e.key === 'Enter' && !e.shiftKey && window.matchMedia('(min-width: 768px)').matches) { e.preventDefault(); send(); } });
        $('saiSend').addEventListener('click', send);
        $('saiNewSession').addEventListener('click', newSession);
        $('saiSaveSession').addEventListener('click', saveSession);
        $('saiSessToggle').addEventListener('click', () => {
            const collapsed = $('saiSessions').classList.contains('collapsed');
            if (collapsed) openSidebarMobile(); else closeSidebarMobile();
        });
        $('saiPhoto').addEventListener('change', async (e) => {
            const f = e.target.files && e.target.files[0]; if (!f) return;
            const form = new FormData(); form.append('image', f);
            try { const r = await api(U.photo, { method: 'POST', body: form }); photoPath = r.data.path; $('saiPhotoThumb').src = r.data.url; $('saiPhotoChip').classList.remove('hidden'); }
            catch (err) { if (window.toast) toast(err.message, 'error'); } finally { e.target.value = ''; }
        });
        $('saiPhotoRemove').addEventListener('click', () => { photoPath = null; $('saiPhotoChip').classList.add('hidden'); });

        // Start when the AI tab is first shown.
        document.addEventListener('collab:show', (e) => { if (e.detail && e.detail.tab === 'ai') start(); });
    };
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init, { once: true });
    else init();
})();
</script>
