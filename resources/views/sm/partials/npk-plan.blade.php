{{-- NPK Plus on the board (2026-10-07): the season's fertilizer lot by lot,
     applied and still planned, per hectare against the usual rate (free),
     and Anee's reading of it with each lot's stage, weather and ENSO (paid,
     per lot). The button is #npkPlanBtn in the board's header row; this
     partial owns the sheet and its script. Expects $schedule. --}}
@php
    $nkpLocked = ! \App\Support\Tier::scheduleCan($schedule, 'ai');
    $nkpRung = \App\Support\Tier::scheduleUnlocksAt($schedule, 'ai');
@endphp
@include('sm.partials.anee-wait')

<div class="sheet hidden" id="npkPlanSheet" style="--sheet-width:46rem" aria-labelledby="nkpTitle">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title flex items-center gap-2" id="nkpTitle"><img src="{{ asset('images/icons/npk.svg') }}" alt="" class="w-6 h-6">NPK Plus for this season</h3>
        <button data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body">
        <div class="nkp-tabs" role="tablist">
            <button type="button" class="nkp-tab is-on" data-nkp-tab="lots" role="tab">Fertilizer per lot</button>
            <button type="button" class="nkp-tab" data-nkp-tab="saved" role="tab">Anee's readings</button>
        </div>
        <div id="nkpLotsPane">
            <p class="nkp-sub">Every fertilizer on this board's tasks, lot by lot. <b>Applied</b> counts the tasks ticked done; <b>planned</b> the ones still ahead. A task with no lot covers every lot, shared by lot size.</p>
            <div class="nkp-chips" id="nkpChips"></div>
            <div id="nkpLots"><div class="nkp-empty">Adding up your season…</div></div>
            <div class="nkp-rep" id="nkpRep" hidden></div>
        </div>
        <div id="nkpSavedPane" hidden>
            <div id="nkpSaved"><div class="nkp-empty">Loading…</div></div>
        </div>
    </div>
    <div class="sheet-footer nkp-foot" id="nkpFoot">
        <div class="nkp-foot-in">
            <textarea id="nkpNotes" class="form-textarea" rows="1" maxlength="600" placeholder="Anything Anee should know? (optional)"></textarea>
            <button type="button" class="nkp-go" id="nkpGo"
                @if ($nkpLocked) data-tier-lock="{{ $nkpRung }}" data-lock-say="{{ \App\Support\Tier::say($nkpRung, 'Anee\'s fertilizer reading comes with {plan}. She checks every lot\'s nutrients against its stage, its weather and ENSO.') }}" @endif>
                <img src="{{ \App\Models\AiSetting::current()->faceUrl() }}" alt=""><span id="nkpGoSays">Ask Anee</span>
            </button>
            <p class="nkp-fine" id="nkpFine"></p>
        </div>
    </div>
</div>

@push('head')
<style>
    /* ---- NPK Plus sheet (2026-10-07). House curve, still under reduced motion. ---- */
    #npkPlanSheet { --nkp-ease: cubic-bezier(.22,1,.36,1); }
    .nkp-tabs { display: flex; gap: .35rem; margin-bottom: .9rem; }
    .nkp-tab { flex: 1 1 0; padding: .5rem; border-radius: .8rem; font-size: .84rem; font-weight: 800; color: var(--color-gray-500); background: var(--color-gray-100); border: 0; cursor: pointer;
        transition: background-color .28s var(--nkp-ease), color .28s var(--nkp-ease); }
    .nkp-tab.is-on { background: var(--color-brand-600); color: #fff; }
    .nkp-sub { font-size: .8rem; line-height: 1.5; color: var(--color-gray-500); }
    .nkp-chips { display: flex; flex-wrap: wrap; gap: .35rem; margin: .8rem 0; }
    .nkp-chip { padding: .38rem .7rem; border-radius: 999px; font-size: .78rem; font-weight: 800; cursor: pointer; color: var(--color-gray-700); background: var(--color-white);
        border: 1px solid var(--color-gray-200); transition: background-color .28s var(--nkp-ease), border-color .28s var(--nkp-ease), color .28s var(--nkp-ease); }
    .nkp-chip[aria-pressed="true"] { background: var(--color-brand-600); border-color: var(--color-brand-600); color: #fff; }
    .nkp-empty { padding: 1.4rem 1rem; text-align: center; font-size: .84rem; color: var(--color-gray-500); border: 1px dashed var(--color-gray-200); border-radius: .9rem; }
    .nkp-lot { border-radius: 1rem; border: 1px solid var(--color-gray-200); background: var(--color-white); padding: .85rem .9rem; animation: nkpIn .32s var(--nkp-ease) both; }
    .nkp-lot + .nkp-lot { margin-top: .7rem; }
    @keyframes nkpIn { from { opacity: 0; transform: translateY(6px); } }
    .nkp-lot h4 { font-family: var(--font-heading); font-weight: 800; font-size: .98rem; color: var(--color-gray-900); }
    .nkp-lot h4 small { display: block; margin-top: .1rem; font-family: var(--font-body, inherit); font-size: .74rem; font-weight: 600; color: var(--color-gray-500); }
    .nkp-tbl { width: 100%; margin-top: .6rem; border-collapse: collapse; font-size: .8rem; }
    .nkp-tbl th, .nkp-tbl td { padding: .38rem .3rem; text-align: right; border-bottom: 1px solid var(--color-gray-100); color: var(--color-gray-700); }
    .nkp-tbl th:first-child, .nkp-tbl td:first-child { text-align: left; }
    .nkp-tbl th { font-size: .66rem; letter-spacing: .04em; text-transform: uppercase; color: var(--color-gray-500); }
    .nkp-tbl tr.is-total td { font-weight: 800; color: var(--color-gray-900); }
    .nkp-more { display: flex; flex-wrap: wrap; gap: .3rem; margin-top: .5rem; }
    .nkp-more span { padding: .18rem .5rem; border-radius: 999px; font-size: .7rem; font-weight: 700; color: #5b21b6; background: #ede9fe; }
    html.dark .nkp-more span { color: #ddd6fe; background: #2e1065; }
    .nkp-bars { display: grid; gap: .45rem; margin-top: .7rem; }
    .nkp-bar { display: grid; grid-template-columns: 3rem minmax(0, 1fr) 4.4rem; gap: .5rem; align-items: center; font-size: .74rem; font-weight: 800; color: var(--color-gray-700); }
    .nkp-bar i { position: relative; display: block; height: .65rem; border-radius: 999px; background: var(--color-gray-100); }
    .nkp-bar i b { position: absolute; top: 0; bottom: 0; border-radius: 999px; background: rgb(102 189 99 / .3); }
    .nkp-bar i u { position: absolute; left: 0; top: .14rem; bottom: .14rem; border-radius: 999px; background: #4a7c2a; text-decoration: none; transition: width .5s var(--nkp-ease); }
    .nkp-bar i s { position: absolute; top: .14rem; bottom: .14rem; border-radius: 999px; background: repeating-linear-gradient(45deg, #9cc77a 0 4px, #c9e0ad 4px 8px); text-decoration: none; transition: left .5s var(--nkp-ease), width .5s var(--nkp-ease); }
    .nkp-bar em { font-style: normal; text-align: right; font-size: .7rem; }
    .nkp-bar.is-short em { color: #b45309; } .nkp-bar.is-over em { color: #b91c1c; } .nkp-bar.is-right em { color: #2d5016; }
    html.dark .nkp-bar.is-right em { color: #a8cc7e; }
    .nkp-key { display: flex; flex-wrap: wrap; gap: .8rem; margin-top: .4rem; font-size: .68rem; color: var(--color-gray-500); }
    .nkp-key span::before { content: ''; display: inline-block; width: .7rem; height: .45rem; margin-right: .3rem; border-radius: 999px; vertical-align: middle; }
    .nkp-key .k-a::before { background: #4a7c2a; } .nkp-key .k-p::before { background: repeating-linear-gradient(45deg, #9cc77a 0 3px, #c9e0ad 3px 6px); } .nkp-key .k-u::before { background: rgb(102 189 99 / .3); }
    .nkp-lines { margin-top: .6rem; font-size: .76rem; }
    .nkp-lines summary { cursor: pointer; font-weight: 800; color: var(--color-brand-700); }
    .nkp-lines ul { margin-top: .4rem; display: grid; gap: .3rem; }
    .nkp-lines li { display: flex; gap: .5rem; align-items: baseline; color: var(--color-gray-700); line-height: 1.4; }
    .nkp-lines li em { flex: none; font-style: normal; font-size: .64rem; font-weight: 800; text-transform: uppercase; padding: .08rem .4rem; border-radius: 999px; background: #e4efd4; color: #2d5016; }
    .nkp-lines li em.is-plan { background: var(--color-gray-100); color: var(--color-gray-600); }
    .nkp-unread { margin-top: .6rem; padding: .55rem .65rem; border-radius: .7rem; font-size: .76rem; line-height: 1.45; color: #713f12; background: #fdf6e6; border: 1px solid #f3d9a4; }
    html.dark .nkp-unread { color: #f3d9a4; background: #241d10; border-color: #5c4a24; }
    .nkp-foot-in { display: grid; gap: .5rem; width: 100%; }
    .nkp-foot-in textarea { resize: none; font-size: .84rem; }
    .nkp-go { display: flex; align-items: center; justify-content: center; gap: .5rem; width: 100%; padding: .8rem 1rem; border-radius: .95rem; border: 0; cursor: pointer; font-weight: 800;
        color: #1a1a1a; background: linear-gradient(135deg, #f7d23a, #f5c518); transition: transform .28s var(--nkp-ease), opacity .28s var(--nkp-ease); }
    .nkp-go:hover { transform: translateY(-1px); }
    .nkp-go:disabled { opacity: .5; transform: none; cursor: default; }
    .nkp-go img { width: 1.6rem; height: 1.6rem; border-radius: 999px; }
    .nkp-fine { font-size: .72rem; text-align: center; color: var(--color-gray-500); }
    .nkp-rep { margin-top: 1rem; }
    .nkp-rep[hidden] { display: none; }
    .nkp-rhead { border-radius: 1rem; padding: .95rem 1rem; color: #fff; background: radial-gradient(120% 140% at 100% 0%, #4a7c2a 0%, #14250a 65%); animation: nkpIn .32s var(--nkp-ease) both; }
    .nkp-rhead small { font-size: .68rem; font-weight: 800; letter-spacing: .06em; text-transform: uppercase; opacity: .85; }
    .nkp-rhead h4 { margin-top: .2rem; font-family: var(--font-heading); font-weight: 800; font-size: 1.05rem; color: #fff; }
    .nkp-rhead p { margin-top: .3rem; font-size: .84rem; line-height: 1.55; opacity: .92; }
    .nkp-rlot { margin-top: .7rem; border-radius: 1rem; border: 1px solid var(--color-gray-200); padding: .85rem .9rem; background: var(--color-white); animation: nkpIn .32s var(--nkp-ease) both; }
    .nkp-rlot h5 { display: flex; flex-wrap: wrap; align-items: center; gap: .4rem; font-weight: 800; font-size: .92rem; color: var(--color-gray-900); }
    .nkp-v { font-size: .64rem; font-weight: 800; text-transform: uppercase; padding: .12rem .5rem; border-radius: 999px; background: #e4efd4; color: #2d5016; }
    .nkp-v.v-short, .nkp-v.v-unbalanced { background: #fff1c2; color: #8a5a00; } .nkp-v.v-too-much { background: #fde2e1; color: #b42318; }
    .nkp-rlot p { margin-top: .35rem; font-size: .82rem; line-height: 1.5; color: var(--color-gray-700); }
    .nkp-rlot h6 { margin-top: .65rem; font-size: .7rem; font-weight: 800; letter-spacing: .05em; text-transform: uppercase; color: var(--color-gray-500); }
    .nkp-rlot ul { margin-top: .3rem; display: grid; gap: .3rem; }
    .nkp-rlot li { font-size: .8rem; line-height: 1.45; color: var(--color-gray-700); padding: .4rem .55rem; border-radius: .6rem; background: var(--color-gray-50); }
    html.dark .nkp-rlot li { background: #121a0d; }
    .nkp-st { display: inline-block; margin-right: .3rem; font-size: .6rem; font-weight: 800; text-transform: uppercase; padding: .08rem .4rem; border-radius: 999px; background: #e4efd4; color: #2d5016; }
    .nkp-st.s-short { background: #fff1c2; color: #8a5a00; } .nkp-st.s-over { background: #fde2e1; color: #b42318; }
    .nkp-srow { display: flex; align-items: center; gap: .6rem; width: 100%; padding: .65rem .75rem; border-radius: .9rem; border: 1px solid var(--color-gray-200); background: var(--color-white);
        text-align: left; cursor: pointer; transition: transform .28s var(--nkp-ease); }
    .nkp-srow + .nkp-srow { margin-top: .45rem; }
    .nkp-srow:hover { transform: translateY(-1px); }
    .nkp-srow b { display: block; font-size: .84rem; color: var(--color-gray-900); }
    .nkp-srow small { font-size: .72rem; color: var(--color-gray-500); }
    @media (prefers-reduced-motion: reduce) {
        .nkp-lot, .nkp-rhead, .nkp-rlot { animation: none; }
        .nkp-tab, .nkp-chip, .nkp-go, .nkp-srow, .nkp-bar i u, .nkp-bar i s { transition: none; }
    }
</style>
@endpush

@push('scripts')
<script>
(() => {
    if (window.npkPlan) return;
    const SCHEDULE_ID = @json((int) $schedule->id);
    const LOCKED = @json($nkpLocked);
    const U = {
        sum: @json(route('sm.npk')) + '?scheduleId=' + SCHEDULE_ID,
        run: @json(route('sm.npk.generate')) + '?scheduleId=' + SCHEDULE_ID,
        list: @json(route('sm.npk.list')) + '?scheduleId=' + SCHEDULE_ID,
        job: (id) => @json(url('/app/sm-npk-plan-job')) + '/' + id,
        calc: @json(route('npk.page')),
    };
    const $ = (id) => document.getElementById(id);
    const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const lab = (n) => n === 'P2O5' ? 'P₂O₅' : n === 'K2O' ? 'K₂O' : n;
    const fmt = (v, dp = 0) => (Math.round((Number(v) || 0) * 10 ** dp) / 10 ** dp).toLocaleString(undefined, { maximumFractionDigits: dp });
    let D = null, pick = new Set(), busy = false;

    const lotCard = (l) => {
        const row = (label, t, cls) => '<tr class="' + (cls || '') + '"><td>' + label + '</td>' + ['N', 'P2O5', 'K2O'].map((n) => '<td>' + fmt(t[n]) + '</td>').join('') + '</tr>';
        const extra = Object.entries(l.total || {}).filter(([n]) => !['N', 'P2O5', 'K2O', 'Cl'].includes(n));
        const usual = l.usual || {};
        const bars = ['N', 'P2O5', 'K2O'].filter((n) => Array.isArray(usual[n])).map((n) => {
            const [lo, hi] = usual[n];
            const a = Number((l.applied || {})[n]) || 0, p = Number((l.planned || {})[n]) || 0, t = a + p;
            const state = t < lo * 0.9 ? 'short' : t > hi * 1.15 ? 'over' : 'right';
            const sc = Math.max(hi * 1.5, t * 1.05, 1);
            return '<div class="nkp-bar is-' + state + '"><span>' + lab(n) + '</span><i><b style="left:' + (lo / sc * 100) + '%;width:' + (Math.max(hi - lo, sc * .015) / sc * 100) + '%"></b>'
                + '<u style="width:' + Math.min(100, a / sc * 100) + '%"></u><s style="left:' + Math.min(100, a / sc * 100) + '%;width:' + Math.min(100 - a / sc * 100, p / sc * 100) + '%"></s></i>'
                + '<em>' + (state === 'short' ? 'Short' : state === 'over' ? 'Too much' : 'Right') + '</em></div>';
        }).join('');
        const lines = (l.lines || []).map((x) => '<li><em class="' + (x.done ? '' : 'is-plan') + '">' + (x.done ? 'Done' : 'Planned') + '</em><span><b>' + esc(x.date) + '</b> ' + esc(x.title) + ': ' + esc(x.name)
            + ' ' + esc(x.amount) + (x.shared && x.kg != null ? ' (this lot ' + fmt(x.kg, 1) + ' kg)' : '') + (x.estimate ? ' (estimate)' : '') + '</span></li>').join('');
        return '<div class="nkp-lot"><h4>' + esc(l.name) + '<small>' + [l.crop, l.size, l.stage, l.age].filter(Boolean).map(esc).join(' · ') + '</small></h4>'
            + (l.areaHa ? '<table class="nkp-tbl"><tr><th>kg per ha</th><th>N</th><th>P₂O₅</th><th>K₂O</th></tr>' + row('Applied so far', l.applied) + row('Still planned', l.planned) + row('Season total', l.total, 'is-total') + '</table>'
                : '<p class="nkp-unread">Set this lot\'s size in Lots to see it per hectare. In all: applied ' + esc(Object.entries((l.kg || {}).applied || {}).map(([n, v]) => lab(n) + ' ' + fmt(v, 1)).join(', ') || 'nothing') + ' kg.</p>')
            + (extra.length ? '<div class="nkp-more">' + extra.map(([n, v]) => '<span>' + esc(lab(n)) + ' ' + fmt(v, v >= 10 ? 0 : v >= 1 ? 1 : v >= .1 ? 2 : 3) + ' kg/ha</span>').join('') + '</div>' : '')
            + (bars ? '<div class="nkp-bars">' + bars + '</div><div class="nkp-key"><span class="k-a">Applied</span><span class="k-p">Planned</span><span class="k-u">Usual rate' + (l.trees ? ', ' + l.trees + ' trees per ha' : '') + '</span></div>' : '')
            + (lines ? '<details class="nkp-lines"><summary>' + (l.lines.length) + ' fertilizer line' + (l.lines.length === 1 ? '' : 's') + ' on this lot</summary><ul>' + lines + '</ul></details>' : '<p class="nkp-sub mt-2">No fertilizer on this lot\'s tasks yet.</p>')
            + ((l.unread || []).length ? '<div class="nkp-unread"><b>Not counted:</b> ' + l.unread.map((u) => esc(u.name) + ' (' + esc(u.why) + ')').join('; ') + ' <a href="' + esc(U.calc) + '" class="underline font-bold">Open NPK Plus</a></div>' : '')
            + '</div>';
    };
    const paint = () => {
        if (!D) return;
        const lots = D.lots || [];
        if (!lots.length) { $('nkpLots').innerHTML = '<div class="nkp-empty">This season has no lots yet. Add one in Lots, then come back.</div>'; $('nkpChips').innerHTML = ''; foot(); return; }
        $('nkpChips').innerHTML = '<button type="button" class="nkp-chip" data-lot="all" aria-pressed="' + (pick.size === lots.length) + '">All lots</button>'
            + lots.map((l) => '<button type="button" class="nkp-chip" data-lot="' + l.id + '" aria-pressed="' + pick.has(l.id) + '">' + esc(l.name) + '</button>').join('');
        const shown = lots.filter((l) => pick.has(l.id));
        $('nkpLots').innerHTML = shown.length ? shown.map(lotCard).join('') : '<div class="nkp-empty">Pick a lot above.</div>';
        foot();
    };
    const foot = () => {
        const n = pick.size, price = D ? Number(D.price) || 60 : 60;
        const go = $('nkpGo');
        if (LOCKED) { $('nkpGoSays').textContent = 'Ask Anee · paid plans'; $('nkpFine').textContent = ''; return; }
        go.disabled = busy || !n || !D || !D.aiUsable;
        $('nkpGoSays').textContent = n ? 'Ask Anee about ' + (n === 1 ? 'this lot' : n + ' lots') + ' · ' + fmt(price * n) + ' credits' : 'Pick a lot to ask Anee';
        $('nkpFine').innerHTML = D && !D.aiUsable ? 'Anee is not available right now.'
            : (D ? (price + ' credits per lot. You have ' + (window.creditCoin ? window.creditCoin(D.unlimited ? '∞' : fmt(D.balance)) : fmt(D.balance)) + '. Charged only when the reading is ready.') : '');
    };
    $('nkpChips').addEventListener('click', (e) => {
        const b = e.target.closest('.nkp-chip');
        if (!b || !D) return;
        const all = (D.lots || []).map((l) => l.id);
        if (b.dataset.lot === 'all') pick = pick.size === all.length ? new Set([all[0]]) : new Set(all);
        else { const id = Number(b.dataset.lot); if (pick.has(id) && pick.size > 1) pick.delete(id); else pick.add(id); }
        if (pick.size > (D.maxLots || 8)) { window.toast?.('Up to ' + (D.maxLots || 8) + ' lots at a time.', 'error'); pick = new Set([...pick].slice(0, D.maxLots || 8)); }
        paint();
    });

    const showReport = (d) => {
        const rep = d.report || {}, a = rep.anee || {};
        const sec = (t, items) => items && items.length ? '<h6>' + t + '</h6><ul>' + items.join('') + '</ul>' : '';
        $('nkpRep').hidden = false;
        $('nkpRep').innerHTML = '<div class="nkp-rhead"><small>Anee\'s reading · ' + esc(rep.at || '') + '</small><h4>' + esc(a.headline) + '</h4><p>' + esc(a.summary) + '</p></div>'
            + (a.lots || []).map((x) => '<div class="nkp-rlot"><h5>' + esc(x.lot) + ' <span class="nkp-v v-' + esc(String(x.verdict || '').toLowerCase().replace(/\s+/g, '-')) + '">' + esc(x.verdict) + '</span></h5><p>' + esc(x.summary) + '</p>'
                + sec('Nutrient by nutrient', (x.nutrients || []).map((n) => '<li><span class="nkp-st s-' + esc(String(n.status || '').toLowerCase()) + '">' + esc(n.status) + '</span><b>' + esc(lab(n.nutrient)) + '</b> · ' + esc(n.comment) + '</li>'))
                + sec('What to apply next', (x.next || []).map((n) => '<li><b>' + esc(n.when) + ':</b> ' + esc(n.what) + ' <span class="text-gray-500">' + esc(n.why) + '</span></li>'))
                + sec('What to change in the plan', (x.changes || []).map((n) => '<li><b>' + esc(n.change) + '</b> <span class="text-gray-500">' + esc(n.why) + '</span></li>'))
                + (x.weather ? sec('The weather', ['<li>' + esc(x.weather) + '</li>']) : '') + '</div>').join('')
            + ((a.warnings || []).filter(Boolean).length ? '<div class="nkp-rlot">' + sec('Be careful', a.warnings.filter(Boolean).map((w) => '<li>' + esc(w) + '</li>')) + '</div>' : '')
            + '<p class="nkp-fine mt-3">Confidence: ' + esc(a.confidence || '') + '. A guide, not a promise: the weather, the variety and the soil still decide the harvest.</p>';
        requestAnimationFrame(() => $('nkpRep').scrollIntoView({ behavior: matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth', block: 'start' }));
    };
    $('nkpGo').addEventListener('click', async () => {
        if (LOCKED || busy || !pick.size) return;
        busy = true; foot();
        try {
            window.aneeWait.show({ title: 'Anee is checking your fertilizer…', sub: 'Each lot, its stage, its weather and ENSO. About a minute.',
                lines: ['Adding up what each lot has had…', 'Weighing it against the stage…', 'Reading the rain ahead…', 'Deciding what goes on next…'] });
            const r = await window.api(U.run, { method: 'POST', body: { lotIds: [...pick], notes: $('nkpNotes').value.trim() } });
            const d = r.data && r.data.status === 'ready' ? r.data : await window.aneeWait.poll({ id: r.data.id, job: U.job, phases: window.aneeWait.phases.plain });
            await window.aneeWait.done({ title: 'Your fertilizer, read.', line: 'Lot by lot, what to do next.' });
            if (D) D.balance = d.balance;
            showReport(d);
        } catch (err) { window.aneeWait.fail(); window.toast?.(err.message || 'The reading did not finish. Nothing was charged.', 'error'); }
        finally { busy = false; foot(); }
    });

    const loadSaved = async () => {
        $('nkpSaved').innerHTML = '<div class="nkp-empty">Loading…</div>';
        try {
            const r = await window.api(U.list);
            const rows = r.data.rows || [];
            $('nkpSaved').innerHTML = rows.length ? rows.map((x) => '<button type="button" class="nkp-srow" data-id="' + x.id + '"><span><b>' + esc(x.title) + '</b><small>' + esc(x.at) + '</small></span></button>').join('')
                : '<div class="nkp-empty">Anee\'s readings of this season land here.</div>';
        } catch (err) { $('nkpSaved').innerHTML = '<div class="nkp-empty">' + esc(err.message) + '</div>'; }
    };
    $('nkpSaved').addEventListener('click', async (e) => {
        const b = e.target.closest('.nkp-srow');
        if (!b) return;
        try { const r = await window.api(U.job(b.dataset.id)); tab('lots'); showReport(r.data); } catch (err) { window.toast?.(err.message, 'error'); }
    });
    const tab = (t) => {
        document.querySelectorAll('[data-nkp-tab]').forEach((x) => x.classList.toggle('is-on', x.dataset.nkpTab === t));
        $('nkpLotsPane').hidden = t !== 'lots'; $('nkpSavedPane').hidden = t !== 'saved';
        $('nkpFoot').style.display = t === 'lots' ? '' : 'none';
        if (t === 'saved') loadSaved();
    };
    document.querySelectorAll('[data-nkp-tab]').forEach((x) => x.addEventListener('click', () => tab(x.dataset.nkpTab)));

    const open = async () => {
        window.openSheet('npkPlanSheet');
        tab('lots');
        $('nkpRep').hidden = true;
        $('nkpLots').innerHTML = '<div class="nkp-empty">Adding up your season…</div>';
        try {
            const r = await window.api(U.sum);
            D = r.data;
            const ids = (D.lots || []).map((l) => l.id);
            if (!pick.size || [...pick].some((i) => !ids.includes(i))) pick = new Set(ids.slice(0, D.maxLots || 8));
            paint();
        } catch (err) { $('nkpLots').innerHTML = '<div class="nkp-empty">' + esc(err.message || 'Could not add up the season.') + '</div>'; }
    };
    document.addEventListener('click', (e) => { if (e.target.closest('#npkPlanBtn')) open(); });
    window.npkPlan = { open };
})();
</script>
@endpush
