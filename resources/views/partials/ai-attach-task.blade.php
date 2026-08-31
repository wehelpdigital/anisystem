{{-- Filing a chat onto a day, or onto a task on that day.

     Three questions, asked as three tags rather than three screens: which
     season, which day, and which task on it — with "just this day" among the
     answers, because plenty of what a chat is worth keeping for happened on a
     day and not on a job.

     The season tag is drawn only where the season is genuinely unknown. A chat
     opened inside one already knows which; a chat opened from the homepage
     does not, and asking a farmer to confirm what the page already says is a
     tap spent on nothing.

     Used by all three chats. Each one hands it a way to save:

         window.aiAttachOpen({
             scheduleId,          // null when the caller does not know
             askSchedule,         // whether to draw the season tag
             save({ scheduleId, date, activityId, title, description })
         })

     Expects: nothing. Drawn once per page. --}}
@once
<style>
    /* Three facts, each one a tap. Reads as a sentence being filled in
       rather than a form being completed. */
    .aiat-rows { display: flex; flex-direction: column; gap: .55rem; }
    .aiat-row { display: flex; align-items: center; gap: .6rem; }
    .aiat-row > b { flex: none; width: 4.6rem; font-size: .78rem; font-weight: 800;
        color: var(--color-gray-500); }
    html.dark .aiat-row > b { color: #a8bd93; }
    .aiat-tag { flex: 1 1 auto; min-width: 0; display: flex; align-items: center;
        justify-content: space-between; gap: .5rem;
        padding: .5rem .75rem; border-radius: 999px;
        border: 1px solid var(--color-gray-200); background: var(--color-white);
        font-size: .82rem; font-weight: 700; color: var(--color-gray-800);
        text-align: left; cursor: pointer;
        transition: border-color .28s cubic-bezier(.22,1,.36,1),
                    background .28s cubic-bezier(.22,1,.36,1); }
    .aiat-tag:hover { border-color: var(--color-brand-300); background: var(--color-brand-50); }
    .aiat-tag.is-set { border-color: var(--color-brand-400); background: var(--color-brand-50);
        color: #2f5219; }
    .aiat-tag span { min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .aiat-tag svg { width: .9rem; height: .9rem; flex: none; opacity: .6; }
    .aiat-tag[disabled] { opacity: .5; cursor: default; }
    html.dark .aiat-tag { background: #151b12; border-color: #2b3a1c; color: #e8efe1; }
    html.dark .aiat-tag.is-set { background: rgb(107 159 61 / .16); border-color: var(--color-brand-600); color: #bfe19a; }
    @media (prefers-reduced-motion: reduce) { .aiat-tag { transition: none; } }
    .aiat-none { font-size: .78rem; color: var(--color-gray-400); padding: .4rem .1rem; }
</style>

<div class="sheet hidden" id="aiAtSheet" style="--sheet-width:24rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title">Attach to a task</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body space-y-3">
        <div class="aiat-rows">
            <div class="aiat-row" id="aiAtSchedRow" hidden>
                <b>Season</b>
                <button type="button" class="aiat-tag" id="aiAtSched">
                    <span>Choose a season</span>
                    <svg fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                </button>
            </div>
            <div class="aiat-row">
                <b>Day</b>
                <button type="button" class="aiat-tag" id="aiAtDate">
                    <span>Pick a day</span>
                    <svg fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                </button>
            </div>
            <div class="aiat-row" id="aiAtTaskRow" hidden>
                <b>Task</b>
                <button type="button" class="aiat-tag" id="aiAtTask">
                    <span>Select a task</span>
                    <svg fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                </button>
            </div>
        </div>

        <div>
            <label class="form-label" for="aiAtTitle">Title</label>
            <input type="text" id="aiAtTitle" class="form-input" maxlength="180" placeholder="Name this note">
        </div>
        <div>
            <label class="form-label" for="aiAtDesc">Description <span class="text-gray-400 font-normal">(optional)</span></label>
            <textarea id="aiAtDesc" class="form-textarea" rows="2" maxlength="2000" placeholder="Why this chat is worth keeping…"></textarea>
        </div>
        <p class="text-xs text-gray-400">The whole conversation is attached underneath.</p>
        <button type="button" id="aiAtGo" class="btn w-full sweep-fill sweep-green"
                style="--sw-t: 11s; --sw-d: -2s; color: #fff; border: 0">Keep this chat</button>
        {{-- The date lives in a real input so the phone opens its own picker,
             which is the one everybody already knows how to use. --}}
        <input type="date" id="aiAtDateInput" class="sr-only" tabindex="-1" aria-hidden="true">
    </div>
</div>

<div class="sheet hidden" id="aiAtPickSheet" style="--sheet-width:22rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title" id="aiAtPickTitle">Choose</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body space-y-1" id="aiAtPickBody"></div>
</div>

<script>
(() => {
    if (window.aiAttachOpen) return;
    const byId = (id) => document.getElementById(id);
    const esc = window.escapeHtml || ((t) => String(t == null ? '' : t));
    const URL_OPTS = @json(route('ai.attach.options'));

    /* What is being filled in. Reset on every open, because a sheet that
       remembers last week's answers is a sheet that files a chat under the
       wrong day. */
    let cfg = null;
    let pick = { scheduleId: null, scheduleName: '', date: '', activityId: null, activityName: '' };

    const setTag = (id, text, isSet) => {
        const b = byId(id);
        if (!b) return;
        b.querySelector('span').textContent = text;
        b.classList.toggle('is-set', !!isSet);
    };

    function draw() {
        byId('aiAtSchedRow').hidden = !cfg.askSchedule;
        setTag('aiAtSched', pick.scheduleName || 'Choose a season', !!pick.scheduleId);
        setTag('aiAtDate', pick.date ? niceDay(pick.date) : 'Pick a day', !!pick.date);
        // The task tag exists only once there is a day to have tasks on.
        byId('aiAtTaskRow').hidden = !(pick.scheduleId && pick.date);
        setTag('aiAtTask', pick.activityId ? pick.activityName : (pick.date ? 'Select a task' : 'Select a task'),
            pick.activityId !== null);
    }

    const niceDay = (d) => {
        try {
            return new Date(d + 'T00:00:00').toLocaleDateString(undefined,
                { weekday: 'short', month: 'short', day: 'numeric', year: 'numeric' });
        } catch (_) { return d; }
    };

    const openPick = (title, html) => {
        byId('aiAtPickTitle').textContent = title;
        byId('aiAtPickBody').innerHTML = html;
        window.openSheet?.('aiAtPickSheet');
    };

    async function ask(params) {
        const q = new URLSearchParams(params).toString();
        const res = await window.api(URL_OPTS + (q ? '?' + q : ''));
        return res.data || {};
    }

    /* ---- season ---- */
    byId('aiAtSched')?.addEventListener('click', async () => {
        try {
            const d = await ask({});
            const rows = (d.schedules || []).map((s) =>
                `<button type="button" class="ai-attach-opt" data-aiat-sched="${s.id}" data-name="${esc(s.title)}">
                    <span class="min-w-0">${esc(s.title)}</span>
                 </button>`).join('');
            openPick('Which season?', rows || '<p class="aiat-none">No seasons yet.</p>');
        } catch (e) { window.toast?.(e.message || 'Could not read your seasons.', 'error'); }
    });

    /* ---- day: the phone's own picker ---- */
    byId('aiAtDate')?.addEventListener('click', () => {
        const inp = byId('aiAtDateInput');
        inp.value = pick.date || new Date().toISOString().slice(0, 10);
        // showPicker where it exists; a click is the fallback everywhere else.
        try { inp.showPicker ? inp.showPicker() : inp.click(); } catch (_) { inp.click(); }
    });
    byId('aiAtDateInput')?.addEventListener('change', (e) => {
        pick.date = e.target.value || '';
        pick.activityId = null; pick.activityName = '';
        draw();
    });

    /* ---- task on that day ---- */
    byId('aiAtTask')?.addEventListener('click', async () => {
        if (!pick.scheduleId || !pick.date) return;
        try {
            const d = await ask({ scheduleId: pick.scheduleId, date: pick.date });
            const rows = (d.tasks || []).map((t) =>
                `<button type="button" class="ai-attach-opt" data-aiat-task="${t.id}" data-name="${esc(t.title)}">
                    <span class="min-w-0">${esc(t.title)}</span>
                 </button>`).join('');
            /* Always offered, and offered LAST: a day with three tasks on it
               is still a day somebody may want the chat filed under rather
               than onto any one of them. */
            const dayOpt = `<button type="button" class="ai-attach-opt" data-aiat-task="day">
                    <span class="min-w-0">Save a note in this day<span class="sub">Not on a task — on the day itself</span></span>
                 </button>`;
            openPick('What on ' + niceDay(pick.date) + '?',
                (rows || '<p class="aiat-none">Nothing is scheduled on this day.</p>') + dayOpt);
        } catch (e) { window.toast?.(e.message || 'Could not read that day.', 'error'); }
    });

    byId('aiAtPickBody')?.addEventListener('click', (e) => {
        const sched = e.target.closest('[data-aiat-sched]');
        if (sched) {
            pick.scheduleId = parseInt(sched.dataset.aiatSched, 10);
            pick.scheduleName = sched.dataset.name || '';
            pick.activityId = null; pick.activityName = '';
            window.closeSheet?.('aiAtPickSheet');
            draw();

            return;
        }
        const task = e.target.closest('[data-aiat-task]');
        if (task) {
            const v = task.dataset.aiatTask;
            pick.activityId = v === 'day' ? 0 : parseInt(v, 10);
            pick.activityName = v === 'day' ? 'This day' : (task.dataset.name || 'Task');
            window.closeSheet?.('aiAtPickSheet');
            draw();
        }
    });

    /* ---- keep it ---- */
    byId('aiAtGo')?.addEventListener('click', async () => {
        if (!pick.scheduleId) { window.toast?.('Choose a season first.', 'error'); return; }
        if (!pick.date) { window.toast?.('Pick a day first.', 'error'); return; }
        if (pick.activityId === null) { window.toast?.('Choose a task, or "Save a note in this day".', 'error'); return; }
        const btn = byId('aiAtGo');
        btn.disabled = true;
        try {
            await cfg.save({
                scheduleId: pick.scheduleId,
                date: pick.date,
                // 0 is the day itself, which the server reads as "no task".
                activityId: pick.activityId || null,
                title: byId('aiAtTitle').value.trim(),
                description: byId('aiAtDesc').value.trim(),
            });
            window.closeSheet?.('aiAtSheet');
        } catch (err) {
            window.toast?.(err.message || 'Could not keep that.', 'error');
        } finally { btn.disabled = false; }
    });

    window.aiAttachOpen = (options) => {
        cfg = options || {};
        pick = {
            scheduleId: cfg.scheduleId || null,
            scheduleName: cfg.scheduleName || '',
            date: '',
            activityId: null,
            activityName: '',
        };
        byId('aiAtTitle').value = '';
        byId('aiAtDesc').value = '';
        draw();
        window.openSheet?.('aiAtSheet');
    };
})();
</script>
@endonce
