{{-- NPK Plus in the Protocol Builder (2026-10-07): the protocol's fertilizer
     task by task on its day count, a running total and the whole per hectare
     against the crop's usual rate (free); Anee's reading of it (paid). The
     button is #pbNpkTop in the tools bar; this partial owns the sheet. The
     editor's own save is flushed first (window.pbFlushSave), so the sheet
     reads what is on screen. Expects $protocol (the editor's shape). --}}
@php $pnLocked = ! \App\Support\Tier::farmCan('aiAnalyses'); $pnRung = \App\Support\Tier::farmUnlocksAt('aiAnalyses'); @endphp
<div class="sheet hidden" id="pbNpkSheet" style="--sheet-width:44rem" aria-labelledby="pnTitle">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title flex items-center gap-2" id="pnTitle"><img src="{{ asset('images/icons/npk.svg') }}" alt="" class="w-6 h-6">NPK Plus for this protocol</h3>
        <button data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body">
        <div class="pn-area">
            <label for="pnArea">The amounts in this protocol are for</label>
            <div class="flex items-center gap-2"><input type="number" id="pnArea" class="form-input" min="0.01" step="any" value="1" inputmode="decimal" style="width:6.5rem"><span class="text-sm font-bold text-gray-600">hectare</span></div>
        </div>
        <div id="pnBody"><div class="pn-empty">Adding up the protocol…</div></div>
        <div class="pn-ask" id="pnAsk" hidden>
            <h4>Ask Anee to read it</h4>
            <p class="pn-sub">Anee weighs each application against the crop's stage, the soil and the water, and ENSO, then says what to move, cut, add or split. Tell her what you know; the rest is optional.</p>
            <label class="pn-label" for="pnLoc">Where it will be used</label>
            <input type="text" id="pnLoc" class="form-input" maxlength="160" placeholder="Town and province">
            <label class="pn-label">The soil</label>
            <div class="pn-pills" id="pnSoil"></div>
            <label class="pn-label">Water</label>
            <div class="pn-pills" id="pnWater"></div>
            <label class="pn-label" for="pnNotes">Anything else</label>
            <textarea id="pnNotes" class="form-textarea" rows="2" maxlength="800" placeholder="Like: we plant this in the dry season"></textarea>
            <button type="button" class="pn-go" id="pnGo" @if ($pnLocked) data-tier-lock="{{ $pnRung }}" data-lock-say="{{ \App\Support\Tier::say($pnRung, 'Anee\'s reading of a protocol\'s fertilizer comes with {plan}. She checks every application against the stage, the soil, the water and ENSO.') }}" @endif>
                <img src="{{ \App\Models\AiSetting::current()->faceUrl() }}" alt=""><span id="pnGoSays">Ask Anee</span>
            </button>
            <p class="pn-fine" id="pnFine"></p>
        </div>
        <div id="pnRep" hidden></div>
        <div class="pn-saved" id="pnSaved"></div>
    </div>
</div>

<style>
    /* ---- NPK Plus sheet in the builder. House curve, still under reduced motion. ---- */
    #pbNpkSheet { --pn-ease: cubic-bezier(.22,1,.36,1); }
    .pn-area { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: .5rem; padding: .7rem .8rem; border-radius: .9rem; background: var(--color-gray-50); border: 1px solid var(--color-gray-100); }
    html.dark .pn-area { background: #121a0d; border-color: #2b3a1c; }
    .pn-area label { font-size: .8rem; font-weight: 800; color: var(--color-gray-700); }
    .pn-empty { margin-top: .8rem; padding: 1.3rem 1rem; text-align: center; font-size: .84rem; color: var(--color-gray-500); border: 1px dashed var(--color-gray-200); border-radius: .9rem; }
    .pn-sum { margin-top: .9rem; display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: .45rem; }
    .pn-sum div { border-radius: .95rem; padding: .6rem .4rem; text-align: center; color: #fff; animation: pnIn .32s var(--pn-ease) both; }
    .pn-sum div:nth-child(1) { background: linear-gradient(135deg, #2d5016, #4a7c2a); }
    .pn-sum div:nth-child(2) { background: linear-gradient(135deg, #9a3412, #ea580c); animation-delay: 40ms; }
    .pn-sum div:nth-child(3) { background: linear-gradient(135deg, #5b21b6, #8b5cf6); animation-delay: 80ms; }
    .pn-sum small { display: block; font-size: .64rem; font-weight: 800; letter-spacing: .06em; text-transform: uppercase; opacity: .85; }
    .pn-sum b { display: block; font-family: var(--font-heading); font-size: 1.35rem; font-weight: 800; line-height: 1.15; }
    .pn-sum i { display: block; font-style: normal; font-size: .64rem; opacity: .85; }
    @keyframes pnIn { from { opacity: 0; transform: translateY(6px); } }
    .pn-bars { display: grid; gap: .45rem; margin-top: .8rem; }
    .pn-bar { display: grid; grid-template-columns: 3rem minmax(0, 1fr) 4.4rem; gap: .5rem; align-items: center; font-size: .74rem; font-weight: 800; color: var(--color-gray-700); }
    .pn-bar i { position: relative; display: block; height: .65rem; border-radius: 999px; background: var(--color-gray-100); }
    .pn-bar i b { position: absolute; top: 0; bottom: 0; border-radius: 999px; background: rgb(102 189 99 / .3); }
    .pn-bar i u { position: absolute; left: 0; top: .14rem; bottom: .14rem; border-radius: 999px; background: #4a7c2a; text-decoration: none; transition: width .5s var(--pn-ease); }
    .pn-bar em { font-style: normal; text-align: right; font-size: .7rem; }
    .pn-bar.is-short em { color: #b45309; } .pn-bar.is-over em { color: #b91c1c; } .pn-bar.is-right em { color: #2d5016; }
    html.dark .pn-bar.is-right em { color: #a8cc7e; }
    .pn-more { display: flex; flex-wrap: wrap; gap: .3rem; margin-top: .6rem; }
    .pn-more span { padding: .18rem .5rem; border-radius: 999px; font-size: .7rem; font-weight: 700; color: #5b21b6; background: #ede9fe; }
    html.dark .pn-more span { color: #ddd6fe; background: #2e1065; }
    .pn-h { margin-top: 1.1rem; font-size: .72rem; font-weight: 800; letter-spacing: .06em; text-transform: uppercase; color: var(--color-gray-500); }
    .pn-time { position: relative; margin-top: .5rem; display: grid; gap: .5rem; padding-left: 1.1rem; }
    .pn-time::before { content: ''; position: absolute; left: .32rem; top: .4rem; bottom: .4rem; width: 2px; border-radius: 2px; background: var(--color-gray-200); }
    .pn-task { position: relative; padding: .65rem .75rem; border-radius: .9rem; background: var(--color-white); border: 1px solid var(--color-gray-200); animation: pnIn .32s var(--pn-ease) both; }
    .pn-task::before { content: ''; position: absolute; left: -1.03rem; top: .95rem; width: .6rem; height: .6rem; border-radius: 999px; background: #4a7c2a; box-shadow: 0 0 0 3px var(--color-white); }
    .pn-task-h { display: flex; align-items: baseline; justify-content: space-between; gap: .5rem; }
    .pn-task-h b { font-size: .86rem; color: var(--color-gray-900); }
    .pn-task-h b small { margin-right: .35rem; font-size: .66rem; font-weight: 800; padding: .1rem .4rem; border-radius: 999px; color: #2d5016; background: #e4efd4; }
    .pn-task-h span { flex: none; font-size: .72rem; font-weight: 800; color: var(--color-gray-500); }
    .pn-task ul { margin-top: .3rem; display: grid; gap: .15rem; font-size: .76rem; color: var(--color-gray-600); }
    .pn-unread { margin-top: .8rem; padding: .55rem .65rem; border-radius: .7rem; font-size: .76rem; line-height: 1.45; color: #713f12; background: #fdf6e6; border: 1px solid #f3d9a4; }
    html.dark .pn-unread { color: #f3d9a4; background: #241d10; border-color: #5c4a24; }
    .pn-ask { margin-top: 1.2rem; padding-top: 1rem; border-top: 1px dashed var(--color-gray-200); }
    .pn-ask[hidden] { display: none; }
    .pn-ask h4 { font-family: var(--font-heading); font-weight: 800; font-size: 1rem; color: var(--color-gray-900); }
    .pn-sub { margin-top: .2rem; font-size: .8rem; line-height: 1.5; color: var(--color-gray-500); }
    .pn-label { display: block; margin: .8rem 0 .35rem; font-size: .76rem; font-weight: 800; color: var(--color-gray-700); }
    .pn-pills { display: flex; flex-wrap: wrap; gap: .35rem; }
    .pn-pill { padding: .4rem .7rem; border-radius: 999px; font-size: .78rem; font-weight: 700; cursor: pointer; color: var(--color-gray-700); background: var(--color-white); border: 1px solid var(--color-gray-200);
        transition: background-color .28s var(--pn-ease), border-color .28s var(--pn-ease), color .28s var(--pn-ease); }
    .pn-pill[aria-pressed="true"] { background: var(--color-brand-600); border-color: var(--color-brand-600); color: #fff; }
    .pn-go { margin-top: 1rem; display: flex; align-items: center; justify-content: center; gap: .5rem; width: 100%; padding: .85rem 1rem; border-radius: .95rem; border: 0; cursor: pointer; font-weight: 800;
        color: #1a1a1a; background: linear-gradient(135deg, #f7d23a, #f5c518); transition: transform .28s var(--pn-ease); }
    .pn-go:hover { transform: translateY(-1px); } .pn-go:disabled { opacity: .5; transform: none; cursor: default; }
    .pn-go img { width: 1.6rem; height: 1.6rem; border-radius: 999px; }
    .pn-fine { margin-top: .4rem; font-size: .72rem; text-align: center; color: var(--color-gray-500); }
    #pnRep { margin-top: 1rem; }
    #pnRep[hidden] { display: none; }
    .pn-rhead { border-radius: 1rem; padding: .95rem 1rem; color: #fff; background: radial-gradient(120% 140% at 100% 0%, #4a7c2a 0%, #14250a 65%); animation: pnIn .32s var(--pn-ease) both; }
    .pn-rhead.v-needs-changes, .pn-rhead.v-unbalanced { background: radial-gradient(120% 140% at 100% 0%, #b45309 0%, #3d1d02 65%); }
    .pn-rhead small { font-size: .68rem; font-weight: 800; letter-spacing: .06em; text-transform: uppercase; opacity: .85; }
    .pn-rhead h4 { margin-top: .2rem; font-family: var(--font-heading); font-weight: 800; font-size: 1.05rem; color: #fff; }
    .pn-rhead p { margin-top: .3rem; font-size: .84rem; line-height: 1.55; opacity: .92; }
    .pn-rsec h6 { margin-top: .85rem; font-size: .7rem; font-weight: 800; letter-spacing: .05em; text-transform: uppercase; color: var(--color-gray-500); }
    .pn-rsec ul { margin-top: .35rem; display: grid; gap: .3rem; }
    .pn-rsec li { font-size: .8rem; line-height: 1.45; color: var(--color-gray-700); padding: .45rem .6rem; border-radius: .65rem; background: var(--color-gray-50); }
    html.dark .pn-rsec li { background: #121a0d; }
    .pn-st { display: inline-block; margin-right: .3rem; font-size: .6rem; font-weight: 800; text-transform: uppercase; padding: .08rem .4rem; border-radius: 999px; background: #e4efd4; color: #2d5016; }
    .pn-st.s-short { background: #fff1c2; color: #8a5a00; } .pn-st.s-over { background: #fde2e1; color: #b42318; }
    .pn-saved { margin-top: 1rem; display: grid; gap: .4rem; }
    .pn-saved button { display: flex; justify-content: space-between; gap: .6rem; width: 100%; padding: .55rem .7rem; border-radius: .8rem; border: 1px solid var(--color-gray-200); background: var(--color-white);
        font-size: .78rem; font-weight: 700; color: var(--color-gray-700); text-align: left; cursor: pointer; }
    .pn-saved button small { flex: none; color: var(--color-gray-500); font-weight: 600; }
    @media (prefers-reduced-motion: reduce) { .pn-sum div, .pn-task, .pn-rhead { animation: none; } .pn-pill, .pn-go, .pn-bar i u { transition: none; } }
</style>

<script>
(() => {
    const ID = @json((int) ($protocol['id'] ?? 0));
    const LOCKED = @json($pnLocked);
    const U = {
        sum: @json(url('/app/protocol-builder')) + '/' + ID + '/npk', run: @json(url('/app/protocol-builder')) + '/' + ID + '/npk',
        list: @json(url('/app/protocol-builder')) + '/' + ID + '/npk-list', job: (id) => @json(url('/app/protocol-builder/npk-job')) + '/' + id,
        calc: @json(route('npk.page')),
    };
    const $ = (id) => document.getElementById(id);
    const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const lab = (n) => n === 'P2O5' ? 'P₂O₅' : n === 'K2O' ? 'K₂O' : n;
    const fmt = (v, dp = 0) => (Math.round((Number(v) || 0) * 10 ** dp) / 10 ** dp).toLocaleString(undefined, { maximumFractionDigits: dp });
    const day = (n) => String(Math.round(Number(n) * 10) / 10);
    let D = null, busy = false;
    const ans = { soil: [], water: '' };

    const paint = () => {
        if (!D) return;
        if (!D.tasks.length) {
            $('pnBody').innerHTML = '<div class="pn-empty">No fertilizer in this protocol yet. Add fertilizer to a task (its items) and it is added up here.</div>'
                + ((D.unread || []).length ? '<div class="pn-unread"><b>Not counted:</b> ' + D.unread.map((u) => esc(u.name) + ' (' + esc(u.why) + ')').join('; ') + '</div>' : '');
            $('pnAsk').hidden = true;
            return;
        }
        const t = D.total || {}, usual = D.usual || {};
        const bars = ['N', 'P2O5', 'K2O'].filter((n) => Array.isArray(usual[n])).map((n) => {
            const [lo, hi] = usual[n], v = Number(t[n]) || 0;
            const state = v < lo * 0.9 ? 'short' : v > hi * 1.15 ? 'over' : 'right';
            const sc = Math.max(hi * 1.5, v * 1.05, 1);
            return '<div class="pn-bar is-' + state + '" title="Usual rate ' + fmt(lo) + (hi !== lo ? ' to ' + fmt(hi) : '') + ' kg per ha"><span>' + lab(n) + '</span><i><b style="left:' + (lo / sc * 100) + '%;width:' + (Math.max(hi - lo, sc * .015) / sc * 100) + '%"></b><u style="width:' + Math.min(100, v / sc * 100) + '%"></u></i><em>' + (state === 'short' ? 'Short' : state === 'over' ? 'Too much' : 'Right') + '</em></div>';
        }).join('');
        const extra = Object.entries(t).filter(([n]) => !['N', 'P2O5', 'K2O', 'Cl'].includes(n));
        let run = { N: 0, P2O5: 0, K2O: 0 };
        $('pnBody').innerHTML = '<div class="pn-sum"><div><small>N</small><b>' + fmt(t.N) + '</b><i>kg per ha</i></div><div><small>P₂O₅</small><b>' + fmt(t.P2O5) + '</b><i>kg per ha</i></div><div><small>K₂O</small><b>' + fmt(t.K2O) + '</b><i>kg per ha</i></div></div>'
            + (extra.length ? '<div class="pn-more">' + extra.map(([n, v]) => '<span>' + esc(lab(n)) + ' ' + fmt(v, v >= 10 ? 0 : v >= 1 ? 1 : v >= .1 ? 2 : 3) + ' kg/ha</span>').join('') + '</div>' : '')
            + (bars ? '<p class="pn-h">Against the usual rate for ' + esc(String(D.protocol.cropLabel || 'this crop').replace(' — ', ', ')) + (D.trees ? ', ' + D.trees + ' trees per ha' : '') + '</p><div class="pn-bars">' + bars + '</div>'
                : '<p class="pn-sub mt-2">' + (D.protocol.crop ? 'No usual rate for this crop in NPK Plus yet.' : 'Set the protocol\'s crop to compare it with the usual rate.') + '</p>')
            + '<p class="pn-h">Task by task, per hectare (running total)</p><div class="pn-time">'
            + D.tasks.map((x, i) => {
                Object.keys(run).forEach((n) => { run[n] += Number((x.npk || {})[n]) || 0; });
                return '<div class="pn-task" style="animation-delay:' + Math.min(i, 10) * 30 + 'ms"><div class="pn-task-h"><b><small>' + esc(x.counter) + ' ' + esc(day(x.day)) + '</small>' + esc(x.title) + '</b><span>'
                    + ['N', 'P2O5', 'K2O'].map((n) => fmt((x.npk || {})[n])).join('-') + '</span></div><ul>'
                    + x.lines.map((l) => '<li>' + esc(l.name) + ' · ' + esc(l.amount) + (l.estimate ? ' (estimate)' : '') + '</li>').join('')
                    + '</ul><p class="pn-sub" style="margin-top:.3rem">So far: ' + ['N', 'P2O5', 'K2O'].map((n) => lab(n) + ' ' + fmt(run[n])).join(', ') + '</p></div>';
            }).join('') + '</div>'
            + ((D.unread || []).length ? '<div class="pn-unread"><b>Not counted:</b> ' + D.unread.map((u) => esc(u.name) + ' (' + esc(u.why) + ')').join('; ') + ' <a href="' + esc(U.calc) + '" class="underline font-bold">Open NPK Plus</a></div>' : '');
        $('pnAsk').hidden = false;
        foot();
    };
    const foot = () => {
        if (LOCKED) { $('pnGoSays').textContent = 'Ask Anee · paid plans'; return; }
        $('pnGo').disabled = busy || !D || !D.canUse;
        $('pnGoSays').textContent = D && D.canUse ? 'Ask Anee · ' + D.price + ' credits' : 'Anee is not available right now';
        $('pnFine').innerHTML = D && D.canUse ? 'You have ' + (window.creditCoin ? window.creditCoin(D.unlimited ? '∞' : fmt(D.balance)) : fmt(D.balance)) + '. Charged only when the reading is ready.' : '';
    };
    const pills = (box, items, multi, key) => {
        box.innerHTML = Object.entries(items).map(([k, v]) => '<button type="button" class="pn-pill" data-k="' + esc(k) + '">' + esc(String(v).split(' —')[0]) + '</button>').join('');
        const paintP = () => box.querySelectorAll('.pn-pill').forEach((b) => b.setAttribute('aria-pressed', String(multi ? ans[key].includes(b.dataset.k) : ans[key] === b.dataset.k)));
        box.onclick = (e) => { const b = e.target.closest('.pn-pill'); if (!b) return; if (multi) ans[key] = ans[key].includes(b.dataset.k) ? ans[key].filter((x) => x !== b.dataset.k) : ans[key].concat(b.dataset.k); else ans[key] = ans[key] === b.dataset.k ? '' : b.dataset.k; paintP(); };
        paintP();
    };
    const load = async () => {
        $('pnBody').innerHTML = '<div class="pn-empty">Adding up the protocol…</div>';
        try {
            await window.pbFlushSave?.();
            const r = await window.api(U.sum + '?areaHa=' + encodeURIComponent(Number($('pnArea').value) || 1));
            D = r.data;
            if (!$('pnSoil').children.length) { pills($('pnSoil'), D.soilConditions, true, 'soil'); pills($('pnWater'), D.water, false, 'water'); }
            paint();
        } catch (err) { $('pnBody').innerHTML = '<div class="pn-empty">' + esc(err.message || 'Could not add up the protocol.') + '</div>'; }
    };
    let at = null;
    $('pnArea').addEventListener('input', () => { clearTimeout(at); at = setTimeout(load, 400); });
    const showReport = (d) => {
        const a = (d.report || {}).anee || {};
        const sec = (t, items) => items && items.length ? '<div class="pn-rsec"><h6>' + t + '</h6><ul>' + items.join('') + '</ul></div>' : '';
        $('pnRep').hidden = false;
        $('pnRep').innerHTML = '<div class="pn-rhead v-' + esc(String(a.verdict || '').toLowerCase().replace(/\s+/g, '-')) + '"><small>Anee\'s verdict: ' + esc(a.verdict) + ' · ' + esc((d.report || {}).at || '') + '</small><h4>' + esc(a.headline) + '</h4><p>' + esc(a.summary) + '</p></div>'
            + sec('Nutrient by nutrient', (a.nutrients || []).map((n) => '<li><span class="pn-st s-' + esc(String(n.status || '').toLowerCase()) + '">' + esc(n.status) + '</span><b>' + esc(lab(n.nutrient)) + '</b> · ' + esc(n.comment) + '</li>'))
            + sec('When to apply', (a.timing || []).map((n) => '<li><b>' + esc(n.when) + ':</b> ' + esc(n.what) + ' <span class="text-gray-500">' + esc(n.why) + '</span></li>'))
            + sec('What to change in the protocol', (a.changes || []).map((n) => '<li><b>' + esc(n.change) + '</b> <span class="text-gray-500">' + esc(n.why) + '</span></li>'))
            + sec('The soil and the season', [a.soil, a.weather].filter(Boolean).map((x) => '<li>' + esc(x) + '</li>'))
            + sec('Be careful', (a.warnings || []).filter(Boolean).map((x) => '<li>' + esc(x) + '</li>'))
            + '<p class="pn-fine mt-3">Confidence: ' + esc(a.confidence || '') + '. A guide, not a promise: the weather, the variety and the soil still decide the harvest.</p>';
        requestAnimationFrame(() => $('pnRep').scrollIntoView({ behavior: matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth', block: 'start' }));
    };
    $('pnGo').addEventListener('click', async () => {
        if (LOCKED || busy || !D) return;
        busy = true; foot();
        try {
            await window.pbFlushSave?.();
            window.aneeWait.show({ title: 'Anee is reading your fertilizer plan…', sub: 'Each application against the stage, the soil and the season. About a minute.',
                lines: ['Adding up each nutrient…', 'Checking each split against the stage…', 'Weighing the soil and the water…', 'Deciding what to move or add…'] });
            const r = await window.api(U.run, { method: 'POST', body: { areaHa: Number($('pnArea').value) || 1, location: $('pnLoc').value.trim(), soilConditions: ans.soil, water: ans.water || null, notes: $('pnNotes').value.trim() } });
            const d = r.data && r.data.status === 'ready' ? r.data : await window.aneeWait.poll({ id: r.data.id, job: U.job, phases: window.aneeWait.phases.plain });
            await window.aneeWait.done({ title: 'Your plan, read.', line: 'What to keep and what to change.' });
            D.balance = d.balance;
            showReport(d); loadSaved();
        } catch (err) { window.aneeWait.fail(); window.toast?.(err.message || 'The reading did not finish. Nothing was charged.', 'error'); }
        finally { busy = false; foot(); }
    });
    const loadSaved = async () => {
        try {
            const r = await window.api(U.list);
            const rows = r.data.rows || [];
            $('pnSaved').innerHTML = rows.length ? '<p class="pn-h" style="margin-top:0">Earlier readings</p>' + rows.map((x) => '<button type="button" data-id="' + x.id + '"><span>' + esc(x.title) + '</span><small>' + esc(x.at) + '</small></button>').join('') : '';
        } catch (_) {}
    };
    $('pnSaved').addEventListener('click', async (e) => {
        const b = e.target.closest('button[data-id]');
        if (!b) return;
        try { const r = await window.api(U.job(b.dataset.id)); showReport(r.data); } catch (err) { window.toast?.(err.message, 'error'); }
    });
    document.addEventListener('click', (e) => {
        if (!e.target.closest('#pbNpkTop')) return;
        window.openSheet('pbNpkSheet');
        $('pnRep').hidden = true;
        load(); loadSaved();
    });
})();
</script>
