{{-- REALIGN BY ANEE — the sheets and the block, shared by the Growth Stages
     module and the activities board's growth-stage sheet (the Tools row
     and a day header's plant pill both open that one).

     The calendar counts days and reads a stage off the count; the plant
     does not always agree. Anee reads the lot's whole history and says
     where the crop actually is; the lot keeps her answer as a shift
     applied to every stage reading. A paid plan's: on Libre the button
     shows, locked, and opens the upgrade sheet.

     Expects: $schedule. One API for the pages:

         window.growthRealign.block({ lotId, lotName, realign })  -> HTML for a lot's card
         window.growthRealign.onApplied = (lotId, realign) => {} -> what a page does after a run

     The wait is the shared one (sm/partials/anee-wait). --}}
@php
    $grxLocked = \App\Support\Tier::forSchedule($schedule) === 'libre';
    $grxPrice = \App\Support\AiPrices::of('realign');
@endphp
@once
@include('sm.partials.anee-wait')

{{-- Before the run: what it costs and what she will do. --}}
<div class="sheet hidden" id="grRealignSheet" style="--sheet-width:30rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title">Realign by Anee</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body">
        <div class="grx-lead">
            <img class="grx-face" src="{{ \App\Models\AiSetting::current()->faceUrl() }}" alt="">
            <div class="min-w-0">
                <p class="grx-lot" id="grxLotName">Lot</p>
                <p class="grx-cal" id="grxCalendar">The calendar says…</p>
            </div>
        </div>
        <p class="grx-what">Anee reads everything this lot has been through since day zero — every activity and what was applied, your notes and tags, the observations, the weather the field had — and says which stage the crop is <b>actually</b> in today, and how many days it runs ahead of or behind the calendar. A herbicide that set it back, a heatwave that stunted it, a hungry field: she weighs all of it.</p>
        <p class="grx-what">Her answer becomes this lot's growth stage everywhere — the board's day headers, the Tools sheet, the Growth Stages module — until you ask her again.</p>
        <div class="grx-prev" id="grxPrev" hidden></div>
        <div class="grx-price">
            <span class="grx-coins" aria-hidden="true">🪙</span>
            <span class="grow"><b>About {{ $grxPrice }} credits</b> for this lot<span id="grxBalance"></span></span>
        </div>
        <p class="grx-blocked" id="grxBlocked" hidden></p>
    </div>
    <div class="sheet-footer">
        <button type="button" class="btn btn-primary w-full" id="grxRun">✨ Realign this lot ({{ $grxPrice }} credits)</button>
    </div>
</div>

{{-- After the run: what she found, laid out to be read in the field. --}}
<div class="sheet hidden" id="grRealignResultSheet" style="--sheet-width:34rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title" id="grxResultTitle">Anee's reading</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body" id="grxResultBody"></div>
    <div class="sheet-footer">
        <button type="button" class="btn btn-primary w-full" data-sheet-close>Got it</button>
    </div>
</div>

<style>
    .grx-lead { display: flex; align-items: center; gap: .75rem; margin-bottom: .8rem; }
    .grx-face { width: 3rem; height: 3rem; border-radius: 999px; object-fit: cover; flex: none; box-shadow: 0 0 0 3px #fff, 0 8px 20px -10px rgb(40 70 15 / .5); }
    .grx-lot { font-family: var(--font-heading); font-size: 1.05rem; font-weight: 800; color: var(--color-gray-900); }
    .grx-cal { font-size: .8rem; color: var(--color-gray-500); margin-top: .1rem; }
    .grx-what { font-size: .84rem; line-height: 1.55; color: var(--color-gray-600); margin-bottom: .6rem; }
    .grx-what b { color: var(--color-gray-900); }
    .grx-prev { margin: .2rem 0 .7rem; padding: .6rem .8rem; border-radius: .85rem; font-size: .78rem; line-height: 1.5;
        background: var(--color-gray-50); border: 1px solid var(--color-gray-200); color: var(--color-gray-600); }
    .grx-prev b { color: var(--color-gray-900); }
    .grx-price { display: flex; align-items: center; gap: .6rem; padding: .7rem .85rem; border-radius: .9rem; font-size: .85rem;
        background: var(--color-brand-50); border: 1px solid var(--color-brand-200); color: var(--color-gray-700); }
    .grx-price b { color: var(--color-gray-900); }
    .grx-coins { font-size: 1.3rem; }
    .grx-blocked { margin-top: .7rem; padding: .6rem .8rem; border-radius: .85rem; font-size: .8rem; font-weight: 600;
        color: #9a1d13; background: #fdf0ee; border: 1px solid #f7d4cf; }

    /* The lot card's block: the button, and the note once she has spoken. */
    .grx-block { margin-top: .8rem; padding-top: .7rem; border-top: 1px dashed var(--color-gray-200); }
    .grx-btn { display: inline-flex; align-items: center; gap: .45rem; padding: .55rem .9rem; border-radius: .85rem; border: 0; cursor: pointer;
        font: inherit; font-size: .82rem; font-weight: 800; color: #fff;
        background: linear-gradient(115deg, #7bb24a, #4a7c2a 40%, #3d6823 70%, #6b9f3d); background-size: 220% 100%;
        animation: grxTide 6s ease-in-out infinite alternate; box-shadow: 0 8px 18px -10px rgb(61 104 35 / .6);
        transition: transform .28s var(--ease-house, cubic-bezier(.22,1,.36,1)); }
    .grx-btn:hover { transform: translateY(-1px); }
    .grx-btn img { width: 1.35rem; height: 1.35rem; border-radius: 999px; object-fit: cover; box-shadow: 0 0 0 2px rgb(255 255 255 / .6); }
    .grx-btn.is-locked { background: var(--color-gray-100); color: var(--color-gray-500); animation: none; box-shadow: none; }
    .grx-btn.is-locked img { filter: grayscale(1); opacity: .7; }
    .grx-btn small { font-weight: 600; opacity: .85; }
    @keyframes grxTide { from { background-position: 0% 50%; } to { background-position: 100% 50%; } }
    .grx-note { margin-top: .6rem; padding: .65rem .8rem; border-radius: .9rem; font-size: .8rem; line-height: 1.5;
        background: linear-gradient(120deg, #f4faee, #e8f3dc); border: 1px solid var(--color-brand-200); color: var(--color-gray-700); }
    .grx-note-head { display: flex; align-items: center; gap: .5rem; flex-wrap: wrap; margin-bottom: .25rem; }
    .grx-note-head b { color: var(--color-gray-900); font-size: .82rem; }
    .grx-note-when { font-size: .7rem; color: var(--color-gray-400); font-weight: 600; }
    .grx-note-more { margin-top: .35rem; font-size: .78rem; font-weight: 800; color: var(--color-brand-700); background: none; border: 0; padding: 0; cursor: pointer; }
    .grx-shift { display: inline-flex; align-items: center; gap: .3rem; padding: .15rem .55rem; border-radius: 999px; font-size: .7rem; font-weight: 800; }
    .grx-shift.is-behind { background: #fff1e6; color: #b45309; border: 1px solid #fdd7b0; }
    .grx-shift.is-ahead { background: #e6f5ff; color: #1d4ed8; border: 1px solid #bfdbfe; }
    .grx-shift.is-even { background: #eaf6e1; color: #2f5219; border: 1px solid #cfe3bd; }

    /* The reading, laid out. */
    .grx-hero { border-radius: 1.1rem; padding: 1rem 1.1rem; color: #fff; margin-bottom: .85rem;
        background: linear-gradient(120deg, #3d6823, #6b9f3d 45%, #4a7c2a 75%, #2f5219); background-size: 220% 220%; animation: grxTide 9s ease-in-out infinite alternate; }
    .grx-hero-k { font-size: .66rem; font-weight: 800; letter-spacing: .1em; text-transform: uppercase; opacity: .85; }
    .grx-hero-stage { font-family: var(--font-heading); font-size: 1.45rem; font-weight: 800; line-height: 1.15; margin-top: .15rem; }
    .grx-hero-line { margin-top: .35rem; font-size: .82rem; opacity: .95; }
    .grx-hero .grx-shift { margin-top: .5rem; background: rgb(255 255 255 / .18); color: #fff; border-color: rgb(255 255 255 / .35); }
    .grx-two { display: grid; grid-template-columns: 1fr 1fr; gap: .5rem; margin-bottom: .85rem; }
    .grx-cell { padding: .6rem .75rem; border-radius: .85rem; background: var(--color-gray-50); border: 1px solid var(--color-gray-200); }
    .grx-cell i { display: block; font-style: normal; font-size: .64rem; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; color: var(--color-gray-400); }
    .grx-cell b { display: block; font-size: .92rem; color: var(--color-gray-900); margin-top: .1rem; }
    .grx-cell span { display: block; font-size: .72rem; color: var(--color-gray-500); }
    .grx-cell.is-anee { background: var(--color-brand-50); border-color: var(--color-brand-200); }
    .grx-conf { display: flex; align-items: center; gap: .6rem; font-size: .76rem; font-weight: 700; color: var(--color-gray-600); margin-bottom: .85rem; }
    .grx-conf-bar { flex: 1; height: .45rem; border-radius: 999px; background: var(--color-gray-200); overflow: hidden; }
    .grx-conf-bar span { display: block; height: 100%; border-radius: 999px; background: linear-gradient(90deg, #6b9f3d, #a9d383); width: 0; transition: width .9s var(--ease-house, cubic-bezier(.22,1,.36,1)) .2s; }
    .grx-sum { font-size: .9rem; line-height: 1.55; color: var(--color-gray-800); margin-bottom: .9rem; }
    .grx-card { border-radius: 1rem; border: 1px solid var(--color-gray-200); padding: .8rem .9rem; margin-bottom: .7rem; background: var(--color-white); }
    .grx-card h4 { display: flex; align-items: center; gap: .4rem; font-size: .8rem; font-weight: 800; color: var(--color-gray-900); margin-bottom: .4rem; }
    .grx-card ul { display: flex; flex-direction: column; gap: .35rem; }
    .grx-card li { display: flex; gap: .5rem; font-size: .82rem; line-height: 1.45; color: var(--color-gray-700); }
    .grx-card li .e { flex: none; }
    .grx-card.is-do { border-color: var(--color-brand-200); background: #f7fbf2; }
    .grx-card.is-watch { border-color: #fde4b8; background: #fffaf0; }
    .grx-applied { font-size: .76rem; line-height: 1.5; color: var(--color-gray-500); padding: .6rem .8rem; border-radius: .85rem; background: var(--color-gray-50); border: 1px dashed var(--color-gray-200); }
    html.dark .grx-lot, html.dark .grx-what b, html.dark .grx-price b, html.dark .grx-prev b, html.dark .grx-note-head b, html.dark .grx-cell b, html.dark .grx-card h4, html.dark .grx-sum { color: #e8efe1; }
    html.dark .grx-cal, html.dark .grx-what, html.dark .grx-prev, html.dark .grx-note, html.dark .grx-cell span, html.dark .grx-card li, html.dark .grx-conf, html.dark .grx-applied { color: #b7c2ad; }
    html.dark .grx-prev, html.dark .grx-cell, html.dark .grx-applied { background: #151b12; border-color: #2b3a1c; }
    html.dark .grx-price, html.dark .grx-note, html.dark .grx-cell.is-anee { background: rgb(107 159 61 / .12); border-color: #2f4d24; }
    html.dark .grx-card { background: #151b12; border-color: #2b3a1c; }
    html.dark .grx-card.is-do { background: rgb(107 159 61 / .1); border-color: #2f4d24; }
    html.dark .grx-card.is-watch { background: rgb(180 83 9 / .1); border-color: rgb(251 191 36 / .25); }
    html.dark .grx-btn.is-locked { background: #1c2416; color: #93a58b; }
    @media (prefers-reduced-motion: reduce) { .grx-btn, .grx-hero { animation: none; } .grx-conf-bar span { transition: none; } }
</style>

<script>
(() => {
    if (window.growthRealign) return;
    const SCHEDULE_ID = @json((int) $schedule->id);
    const LOCKED = @json($grxLocked);
    const PRICE = @json($grxPrice);
    const FACE = @json(\App\Models\AiSetting::current()->faceUrl());
    const U = {
        quote: @json(route('sm.growth.realign.quote')),
        run: @json(route('sm.growth.realign')),
        job: (id) => @json(route('sm.growth.realign.job', ['id' => '__ID__'])).replace('__ID__', id),
    };
    const esc = window.escapeHtml || ((s) => String(s == null ? '' : s));
    const $id = (s) => document.getElementById(s);
    let current = null;     // { lotId, lotName } for the open confirm sheet

    const shiftWords = (n) => {
        n = Number(n) || 0;
        if (n === 0) return 'on the calendar';
        return Math.abs(n) + ' day' + (Math.abs(n) === 1 ? '' : 's') + (n < 0 ? ' behind' : ' ahead');
    };
    const shiftChip = (n) => `<span class="grx-shift ${n < 0 ? 'is-behind' : (n > 0 ? 'is-ahead' : 'is-even')}">${n < 0 ? '🐢' : (n > 0 ? '🐇' : '✅')} ${esc(shiftWords(n))}</span>`;
    const when = (iso) => { try { const d = new Date(iso); return isNaN(d) ? '' : d.toLocaleDateString([], { month: 'short', day: 'numeric' }); } catch (_) { return ''; } };

    /* The block a lot's card carries: the button, and her note once she has spoken. */
    function block({ lotId, lotName, realign }) {
        const btn = LOCKED
            ? `<button type="button" class="grx-btn is-locked" data-tier-lock="solo" data-lock-say="Realign by Anee comes with a paid plan. Upgrade and she reads your lot's whole history to say where the crop really is."><img src="${esc(FACE)}" alt="">Realign by Anee <small>· 🔒 paid plans</small></button>`
            : `<button type="button" class="grx-btn" data-grx-open="${Number(lotId)}" data-grx-name="${esc(lotName)}"><img src="${esc(FACE)}" alt="">${realign ? 'Realign again' : 'Realign by Anee'} <small>· about ${PRICE} credits</small></button>`;
        const note = realign ? `<div class="grx-note">
                <div class="grx-note-head"><b>Realigned by Anee</b>${shiftChip(realign.shiftDays)}<span class="grx-note-when">${esc(when(realign.at || realign.asOf))}</span></div>
                <div>${esc(realign.summary || '')}</div>
                <button type="button" class="grx-note-more" data-grx-show="${Number(lotId)}">Read her full reading →</button>
            </div>` : '';
        return `<div class="grx-block" data-grx-lot="${Number(lotId)}">${btn}${note}</div>`;
    }

    /* The reading, drawn into the result sheet. */
    function draw(r, lotName) {
        const conf = Math.max(0, Math.min(100, Number(r.confidence) || 0));
        const li = (e, t) => `<li><span class="e">${e}</span><span>${esc(t)}</span></li>`;
        const listCard = (cls, title, arr, e) => (arr || []).length ? `<div class="grx-card ${cls}"><h4>${title}</h4><ul>${arr.map((t) => li(e, t)).join('')}</ul></div>` : '';
        const counter = r.counter || 'Day';
        $id('grxResultTitle').textContent = `Anee's reading — ${lotName || ''}`;
        $id('grxResultBody').innerHTML = `
            <div class="grx-hero">
                <div class="grx-hero-k">The crop is in</div>
                <div class="grx-hero-stage">${esc(r.stageLabel || '')}</div>
                <div class="grx-hero-line">as if it were ${esc(counter)} ${esc(String(r.physiologicalDay ?? ''))} — the calendar counts ${esc(counter)} ${esc(String(r.calendarDay ?? ''))}</div>
                ${shiftChip(r.shiftDays)}
            </div>
            <div class="grx-two">
                <div class="grx-cell"><i>The calendar said</i><b>${esc(r.calendarStageLabel || '—')}</b><span>${esc(counter)} ${esc(String(r.calendarDay ?? ''))}</span></div>
                <div class="grx-cell is-anee"><i>Anee says</i><b>${esc(r.stageLabel || '')}</b><span>${esc(shiftWords(r.shiftDays))}</span></div>
            </div>
            <div class="grx-conf"><span>Confidence</span><span class="grx-conf-bar"><span data-w="${conf}"></span></span><b>${conf}%</b></div>
            <p class="grx-sum">${esc(r.summary || '')}</p>
            ${listCard('', '🔎 Why she reads it this way', r.reasons, '•')}
            ${listCard('is-do', '✅ What to do now', r.recommendations, '👉')}
            ${listCard('is-watch', '👀 What to watch for this week', r.watch, '⚠️')}
            <p class="grx-applied">Applied to <b>${esc(lotName || 'this lot')}</b>: the board's day headers, the Tools sheet and the Growth Stages module now read this stage. The day count stays the calendar's; only the stage read off it has moved. Ask her again whenever the field tells a different story.</p>`;
        openSheet('grRealignResultSheet');
        requestAnimationFrame(() => setTimeout(() => { $id('grxResultBody').querySelectorAll('.grx-conf-bar span').forEach((el) => { el.style.width = el.dataset.w + '%'; }); }, 60));
    }

    async function open(lotId, lotName) {
        current = { lotId: Number(lotId), lotName: lotName || '' };
        $id('grxLotName').textContent = current.lotName || 'Lot';
        $id('grxCalendar').textContent = 'Reading the calendar…';
        $id('grxBalance').textContent = '';
        $id('grxPrev').hidden = true;
        $id('grxBlocked').hidden = true;
        $id('grxRun').disabled = true;
        openSheet('grRealignSheet');
        try {
            const res = await api(`${U.quote}?scheduleId=${SCHEDULE_ID}&lotId=${current.lotId}`);
            const d = res.data;
            current.quote = d;
            if (d.calendar) {
                $id('grxCalendar').textContent = `${d.lot.crop || 'Crop'} · the calendar says ${d.calendar.counter} ${d.calendar.day} — ${d.calendar.stage || '?'}`;
            } else {
                $id('grxCalendar').textContent = d.lot.crop || 'No crop set';
            }
            if (d.realign) {
                const p = $id('grxPrev');
                p.innerHTML = `<b>Her last reading</b> (${esc(when(p.dataset.at = d.realign.at || d.realign.asOf))}): ${esc(shiftWords(d.realign.shiftDays))} — ${esc(d.realign.summary || '')}`;
                p.hidden = false;
            }
            $id('grxBalance').textContent = d.unlimited ? ' · unlimited credits' : ` · you have ${Number(d.balance).toLocaleString()}`;
            if (d.blocked) { $id('grxBlocked').textContent = d.blocked; $id('grxBlocked').hidden = false; return; }
            if (!d.aiUsable) { $id('grxBlocked').textContent = 'The AI Technician is not available right now.'; $id('grxBlocked').hidden = false; return; }
            if (!d.unlimited && Number(d.balance) < Number(d.price)) {
                $id('grxBlocked').innerHTML = `You need ${esc(String(d.price))} credits for this and have ${esc(String(d.balance))}. <a href="${@json(route('sm.ai', ['id' => $schedule->id]))}" class="underline font-bold">Top up</a>.`;
                $id('grxBlocked').hidden = false;
                return;
            }
            $id('grxRun').disabled = false;
        } catch (err) {
            $id('grxBlocked').textContent = err.message;
            $id('grxBlocked').hidden = false;
        }
    }

    async function run() {
        if (!current) return;
        const { lotId, lotName } = current;
        closeSheet('grRealignSheet');
        window.aneeWait.show({
            title: `Anee is reading ${lotName || 'the lot'}…`,
            lines: ['Reading every activity since day zero…', 'Weighing what was applied, and when…', 'Reading your notes, tags and observations…', 'Checking the sky the field had…', 'Placing the crop on its own clock…'],
            sub: 'A deep read of one lot — about a minute.',
        });
        let landed = false;
        try {
            // The schedule rides the query string: that is where every module write reads it.
            const res = await api(`${U.run}?scheduleId=${SCHEDULE_ID}`, { method: 'POST', body: { lotId } });
            let data = res.data;
            if (data.pending) {
                for (let i = 0; i < 80 && (!data || data.status !== 'ready'); i++) {
                    await new Promise((r) => setTimeout(r, 3000));
                    const st = await api(U.job(data.id || res.data.id));
                    if (st.data && st.data.status === 'ready') { data = st.data; break; }
                }
                if (!data || data.status !== 'ready') throw new Error('Still working — give it a minute and open the lot again.');
            }
            landed = true;
            const realign = data.realign || data.result;
            try { window.growthRealign.onApplied?.(lotId, realign); } catch (_) {}
            await window.aneeWait.done({ title: 'Done!', line: `${data.credits} credits used — ${lotName || 'the lot'} is realigned.` });
            draw(realign, lotName);
        } catch (err) {
            toast(err.message, 'error');
        } finally {
            if (!landed) window.aneeWait.fail();
        }
    }

    document.addEventListener('click', (e) => {
        const o = e.target.closest('[data-grx-open]');
        if (o) { open(o.dataset.grxOpen, o.dataset.grxName); return; }
        const s = e.target.closest('[data-grx-show]');
        if (s) {
            const lotId = Number(s.dataset.grxShow);
            const r = (window.growthRealign.known || {})[lotId];
            if (r) draw(r, (window.growthRealign.names || {})[lotId] || '');
        }
    });
    $id('grxRun').addEventListener('click', run);

    window.growthRealign = {
        block, draw, open,
        // What each page knows: the applied readings and the lot names, so
        // "read her full reading" can open without another request.
        known: {}, names: {},
        onApplied: null,
        shiftWords,
    };
})();
</script>
@endonce
