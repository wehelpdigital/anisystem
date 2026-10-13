@extends('layouts.app')
@section('title', 'LCC Checker')
@section('page-title', 'LCC Checker')
@section('page-subtitle', 'Is it time for nitrogen?')
@section('back', \App\Support\BackTo::url(route('app.dashboard')))

{{-- The LCC Checker (2026-10-14): the leaf color chart read for a rice
     farmer, free from their own chart or by Anee from leaf photos. The page
     measures each photo's dark green color index (DGCI) itself and joins the
     photos into one numbered picture for Anee; the rule (more than half below
     the critical panel) is LccCheckerController::decide(). --}}
@push('head')
<style>
    .lc-hero { position: relative; overflow: hidden; display: flex; align-items: center; gap: 1rem; padding: 1.05rem 1.15rem; border-radius: 1.3rem; color: #e8f1de;
        background: radial-gradient(120% 140% at 100% 0%, #4a7c2a 0%, #1d3310 55%, #0f1d08 100%); }
    .lc-hero img { flex: none; width: 3.4rem; height: 3.4rem; padding: .45rem; border-radius: 1rem; background: rgb(255 255 255 / .12); }
    .lc-hero h2 { font-family: var(--font-heading); font-weight: 800; font-size: 1.15rem; color: #fff; }
    .lc-hero p { margin-top: .2rem; font-size: .84rem; line-height: 1.5; color: #c9dcb5; }
    .lc-card { margin-top: 1rem; padding: 1rem; border-radius: 1.2rem; background: #fff; border: 1px solid var(--color-gray-100); }
    .lc-card h3 { font-weight: 800; font-size: .95rem; color: var(--color-gray-900); }
    .lc-grid { display: grid; gap: .8rem; margin-top: .7rem; }
    @media (min-width: 640px) { .lc-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
    .lc-lab { display: block; font-size: .7rem; font-weight: 900; letter-spacing: .08em; text-transform: uppercase; color: var(--color-brand-700); margin-bottom: .35rem; }
    .lc-seg { display: flex; gap: .3rem; padding: .25rem; border-radius: .9rem; background: var(--color-gray-50); border: 1px solid var(--color-gray-100); }
    .lc-seg button { flex: 1; padding: .5rem .4rem; border-radius: .7rem; font-size: .82rem; font-weight: 800; color: var(--color-gray-600);
        transition: background-color .28s cubic-bezier(.22,1,.36,1), color .28s cubic-bezier(.22,1,.36,1); }
    .lc-seg button.is-on { background: var(--color-brand-600); color: #fff; }
    .lc-tabs { display: grid; grid-template-columns: 1fr 1fr; gap: .4rem; margin-top: 1rem; }
    .lc-tab { padding: .7rem .6rem; border-radius: 1rem; border: 1px solid var(--color-gray-200); background: #fff; text-align: left; transition: border-color .28s cubic-bezier(.22,1,.36,1), background-color .28s cubic-bezier(.22,1,.36,1); }
    .lc-tab b { display: block; font-size: .88rem; color: var(--color-gray-900); }
    .lc-tab small { display: block; font-size: .72rem; color: var(--color-gray-500); margin-top: .1rem; }
    .lc-tab.is-on { border-color: var(--color-brand-500); background: #f4f9ee; box-shadow: 0 0 0 3px rgb(134 181 86 / .18); }
    .lc-tips { margin-top: .7rem; display: grid; gap: .3rem; font-size: .8rem; line-height: 1.5; color: var(--color-gray-600); }
    .lc-tips li { list-style: '🌾 '; margin-left: 1.2rem; }
    .lc-shots { display: grid; grid-template-columns: repeat(auto-fill, minmax(4.6rem, 1fr)); gap: .45rem; margin-top: .8rem; }
    .lc-shot { position: relative; aspect-ratio: 1; border-radius: .8rem; overflow: hidden; background: var(--color-gray-100); }
    .lc-shot img { width: 100%; height: 100%; object-fit: cover; }
    .lc-shot b { position: absolute; left: .3rem; top: .3rem; min-width: 1.3rem; height: 1.3rem; display: grid; place-items: center; border-radius: 999px; font-size: .7rem; color: #fff; background: rgb(0 0 0 / .55); }
    .lc-shot i { position: absolute; left: .3rem; bottom: .3rem; padding: .05rem .35rem; border-radius: .4rem; font-style: normal; font-size: .6rem; font-weight: 800; color: #fff; background: rgb(0 0 0 / .5); }
    .lc-shot button { position: absolute; right: .25rem; top: .25rem; width: 1.4rem; height: 1.4rem; border-radius: 999px; color: #fff; background: rgb(0 0 0 / .55); font-size: .7rem; }
    .lc-add { aspect-ratio: 1; border-radius: .8rem; border: 2px dashed var(--color-brand-300, #b9d69a); display: grid; place-items: center; text-align: center; font-size: .72rem; font-weight: 800; color: var(--color-brand-700); cursor: pointer; }
    .lc-add span { display: block; font-size: 1.4rem; line-height: 1; }
    .lc-chart { display: grid; grid-template-columns: repeat(4, 1fr); border-radius: .8rem; overflow: hidden; margin-top: .7rem; }
    .lc-chart span { padding: .55rem .2rem; text-align: center; font-size: .78rem; font-weight: 900; }
    .lc-leaves { display: grid; gap: .45rem; margin-top: .8rem; }
    .lc-leaf { display: flex; align-items: center; gap: .5rem; }
    .lc-leaf > b { flex: none; width: 3.4rem; font-size: .78rem; color: var(--color-gray-600); }
    .lc-pick { flex: 1; display: grid; grid-template-columns: repeat(7, minmax(0, 1fr)); gap: .2rem; }
    .lc-pick button { padding: .4rem 0; border-radius: .5rem; font-size: .72rem; font-weight: 900; border: 2px solid transparent; transition: border-color .28s cubic-bezier(.22,1,.36,1), transform .28s cubic-bezier(.22,1,.36,1); }
    .lc-pick button.is-on { border-color: #f59e0b; box-shadow: 0 0 0 2px rgba(245,158,11,.35); transform: scale(1.06); }
    .lc-go { margin-top: 1rem; width: 100%; }
    .lc-res { margin-top: 1rem; }
    .lc-verdict { padding: 1.1rem 1.15rem; border-radius: 1.2rem; color: #fff; opacity: 0; transform: translateY(8px); transition: opacity .45s cubic-bezier(.22,1,.36,1), transform .45s cubic-bezier(.22,1,.36,1); }
    .lc-verdict.is-in { opacity: 1; transform: none; }
    .lc-verdict.is-due { background: linear-gradient(140deg, #a16207, #ca8a04); }
    .lc-verdict.is-ok { background: linear-gradient(140deg, #2d5016, #4a7c2a); }
    .lc-verdict.is-wait { background: linear-gradient(140deg, #374151, #4b5563); }
    .lc-verdict h3 { font-family: var(--font-heading); font-size: 1.2rem; font-weight: 800; color: #fff; }
    .lc-verdict p { margin-top: .35rem; font-size: .88rem; line-height: 1.55; color: rgb(255 255 255 / .9); }
    .lc-dose { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: .4rem; margin-top: .8rem; }
    .lc-dose span { padding: .55rem .4rem; border-radius: .8rem; background: rgb(255 255 255 / .16); text-align: center; font-size: .66rem; font-weight: 800; text-transform: uppercase; letter-spacing: .04em; }
    .lc-dose b { display: block; font-family: var(--font-heading); font-size: 1.15rem; text-transform: none; letter-spacing: 0; }
    .lc-dots { display: flex; flex-wrap: wrap; gap: .3rem; margin-top: .8rem; }
    .lc-dots span { min-width: 2.2rem; padding: .25rem .4rem; border-radius: .6rem; text-align: center; font-size: .72rem; font-weight: 900; border: 2px solid rgb(255 255 255 / .5); }
    .lc-dots span.is-low { border-color: #fde68a; }
    .lc-note { margin-top: .7rem; padding: .7rem .8rem; border-radius: .9rem; background: var(--color-gray-50); font-size: .84rem; line-height: 1.55; color: var(--color-gray-700); }
    .lc-note b { color: var(--color-gray-900); }
    .lc-hist { margin-top: .5rem; display: grid; gap: .3rem; }
    .lc-hist div { display: flex; align-items: center; gap: .5rem; padding: .5rem .2rem; border-bottom: 1px solid var(--color-gray-100); font-size: .8rem; color: var(--color-gray-600); }
    .lc-hist div b { flex: 1; font-weight: 700; color: var(--color-gray-800); }
    .lc-hist button { font-size: .72rem; color: var(--color-gray-400); }
    .lc-learn { display: inline-block; margin-top: .8rem; font-size: .82rem; font-weight: 800; color: var(--color-brand-700); text-decoration: underline; text-underline-offset: 3px; }
    html.dark .lc-card, html.dark .lc-tab { background: #151b12; border-color: #2b3a1c; }
    html.dark .lc-card h3, html.dark .lc-tab b { color: #e8efe1; }
    html.dark .lc-tab.is-on { background: #1a2414; }
    html.dark .lc-seg { background: #10150d; border-color: #2b3a1c; }
    html.dark .lc-seg button { color: #b7c2ad; }
    html.dark .lc-tips, html.dark .lc-leaf > b, html.dark .lc-hist div { color: #b7c2ad; }
    html.dark .lc-note { background: #1a2414; color: #d5e3c5; }
    html.dark .lc-note b, html.dark .lc-hist div b { color: #e8efe1; }
    html.dark .lc-hist div { border-color: #2b3a1c; }
    @media (prefers-reduced-motion: reduce) { .lc-seg button, .lc-tab, .lc-pick button, .lc-verdict { transition: none; } .lc-verdict { opacity: 1; transform: none; } }
</style>
@endpush

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="lc-hero">
        <img src="{{ asset('images/icons/leaf-color.svg') }}" alt="">
        <div><h2>Leaf color, read for you</h2><p>Check your rice leaves every week from two weeks after planting. When most of them run pale, it is time for nitrogen, and the checker says how much urea.</p></div>
    </div>

    <div class="lc-card">
        <h3>Your field</h3>
        <div class="lc-grid">
            <div><span class="lc-lab">How it was planted</span>
                <div class="lc-seg" id="lcMethod"><button type="button" data-v="tpr" class="is-on">Transplanted</button><button type="button" data-v="dsr">Direct seeded</button></div></div>
            <div><span class="lc-lab">Season</span>
                <div class="lc-seg" id="lcSeason"><button type="button" data-v="wet">Wet season</button><button type="button" data-v="dry">Dry season</button></div></div>
            <label><span class="lc-lab" id="lcDaysLab">Days since transplanting</span><input type="number" min="0" max="200" class="form-input" id="lcDays" placeholder="For example 21"></label>
            <label><span class="lc-lab">Area in hectares</span><input type="number" min="0.01" step="0.01" class="form-input" id="lcArea" value="1"></label>
            <label><span class="lc-lab">Variety's maturity</span>
                <select class="form-input" id="lcMat"><option value="">Not sure</option></select></label>
            <label><span class="lc-lab">Variety (optional)</span><input type="text" class="form-input" id="lcVariety" maxlength="60" placeholder="For example NSIC Rc 222"></label>
        </div>
    </div>

    <div class="lc-tabs" role="tablist">
        <button type="button" class="lc-tab is-on" data-tab="photo" role="tab"><b>📷 Photos, read by Anee</b><small id="lcPriceLine">Uses credits</small></button>
        <button type="button" class="lc-tab" data-tab="chart" role="tab"><b>🎨 I have a leaf color chart</b><small>Free: tap each leaf's panel</small></button>
    </div>

    <div class="lc-card" id="lcPhoto">
        <h3>Photograph 10 leaves</h3>
        <ul class="lc-tips">
            <li>Pick 10 hills spread across the field, and on each the youngest leaf that is fully open.</li>
            <li>Lay the leaf flat on white paper and shade it with your body. Morning light, about 8 to 10 am, is best.</li>
            <li>Skip leaves with insect bites or spots. Anee will still tell you what she sees.</li>
        </ul>
        <div class="lc-shots" id="lcShots"></div>
        <input type="file" accept="image/*" multiple id="lcFiles" hidden>
        <button type="button" class="btn btn-primary lc-go" id="lcRead" disabled>Read my leaves with Anee</button>
        <p class="text-xs text-gray-500 mt-2" id="lcWhy" hidden></p>
    </div>

    <div class="lc-card" id="lcChart" hidden>
        <h3>Tap the panel each leaf matches</h3>
        <ul class="lc-tips">
            <li>Hold the chart against the middle of the youngest fully open leaf, shaded by your body.</li>
            <li>Between two panels? Tap the half in between.</li>
        </ul>
        <div class="lc-chart" aria-hidden="true">
            <span style="background:#c8d96f;color:#3f4a12">2</span><span style="background:#8db544;color:#1f2d07">3</span><span style="background:#4d8a2a;color:#fff">4</span><span style="background:#2c5a17;color:#fff">5</span>
        </div>
        <div class="lc-leaves" id="lcLeaves"></div>
        <button type="button" class="btn btn-primary lc-go" id="lcCount">Check my readings</button>
    </div>

    <div class="lc-res" id="lcRes"></div>

    <div class="lc-card">
        <h3>Your earlier checks</h3>
        <div class="lc-hist" id="lcHist"><p class="text-sm text-gray-500">No checks yet.</p></div>
        <a class="lc-learn" href="{{ url('/blog/leaf-color-chart-rice') }}" target="_blank" rel="noopener">How the leaf color chart works ›</a>
    </div>
    @include('sm.partials.anee-wait')
</div>
@endsection

@push('scripts')
<script>
(() => {
    const U = {
        options: '{{ route('lcc.options') }}',
        manual: '{{ route('lcc.manual') }}',
        generate: '{{ route('lcc.generate') }}',
        job: (id) => '{{ url('/app/lcc-checker/job') }}/' + id,
        del: (id) => '{{ url('/app/lcc-checker') }}/' + id,
    };
    const $ = (id) => document.getElementById(id);
    const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const toast = (m, t) => (window.toast ? window.toast(m, t) : alert(m));
    const PANEL_COLOR = { 2: ['#c8d96f', '#3f4a12'], 2.5: ['#abc85a', '#2e3a0d'], 3: ['#8db544', '#1f2d07'], 3.5: ['#6d9f37', '#fff'], 4: ['#4d8a2a', '#fff'], 4.5: ['#3b7020', '#fff'], 5: ['#2c5a17', '#fff'] };
    let OPT = null;
    const st = { method: 'tpr', season: 'wet', tab: 'photo', shots: [], chart: Array(10).fill(null) };

    const seg = (id, key) => $(id).addEventListener('click', (e) => {
        const b = e.target.closest('button[data-v]');
        if (!b) return;
        st[key] = b.dataset.v;
        $(id).querySelectorAll('button').forEach((x) => x.classList.toggle('is-on', x === b));
        if (key === 'method') $('lcDaysLab').textContent = st.method === 'dsr' ? 'Days since sowing' : 'Days since transplanting';
    });
    seg('lcMethod', 'method');
    seg('lcSeason', 'season');
    document.querySelectorAll('.lc-tab').forEach((t) => t.addEventListener('click', () => {
        st.tab = t.dataset.tab;
        document.querySelectorAll('.lc-tab').forEach((x) => x.classList.toggle('is-on', x === t));
        $('lcPhoto').hidden = st.tab !== 'photo';
        $('lcChart').hidden = st.tab !== 'chart';
    }));

    const caseBody = () => ({ method: st.method, season: st.season, days: $('lcDays').value === '' ? null : Number($('lcDays').value),
        maturity: $('lcMat').value || null, areaHa: Number($('lcArea').value) || 1, variety: $('lcVariety').value.trim(), notes: '' });

    /* ---- photos: each one squared, measured (DGCI of its green pixels) and kept small ---- */
    function dgciOf(ctx, w, h) {
        const d = ctx.getImageData(0, 0, w, h).data;
        let sum = 0, n = 0;
        for (let i = 0; i < d.length; i += 16) {
            const r = d[i] / 255, g = d[i + 1] / 255, b = d[i + 2] / 255;
            const max = Math.max(r, g, b), min = Math.min(r, g, b), c = max - min;
            if (max < 0.12 || c < 0.08 || g < r || g < b) continue;   // not leaf: paper, shadow, soil
            let hue = 60 * (((b - r) / c) + 2);
            if (hue < 60 || hue > 180) continue;
            const s = max ? c / max : 0;
            sum += ((hue - 60) / 60 + (1 - s) + (1 - max)) / 3;
            n++;
        }
        return n > 200 ? Math.round((sum / n) * 1000) / 1000 : null;
    }
    function addFiles(files) {
        [...files].slice(0, 12 - st.shots.length).forEach((f) => {
            const img = new Image();
            img.onload = () => {
                const s = Math.min(img.width, img.height), size = 320;
                const c = document.createElement('canvas'); c.width = size; c.height = size;
                const ctx = c.getContext('2d');
                ctx.drawImage(img, (img.width - s) / 2, (img.height - s) / 2, s, s, 0, 0, size, size);
                st.shots.push({ url: c.toDataURL('image/jpeg', 0.85), canvas: c, dgci: dgciOf(ctx, size, size) });
                URL.revokeObjectURL(img.src);
                paintShots();
            };
            img.src = URL.createObjectURL(f);
        });
    }
    function paintShots() {
        $('lcShots').innerHTML = st.shots.map((s, i) => `<div class="lc-shot"><img src="${s.url}" alt="Leaf ${i + 1}"><b>${i + 1}</b>${s.dgci != null ? `<i>${s.dgci.toFixed(2)}</i>` : ''}<button type="button" data-drop="${i}" aria-label="Remove leaf ${i + 1}">✕</button></div>`).join('')
            + (st.shots.length < 12 ? `<label class="lc-add" for="lcFiles"><div><span>＋</span>${st.shots.length ? 'Add more' : 'Add leaf photos'}</div></label>` : '');
        const ok = st.shots.length >= 3 && OPT && OPT.canUse;
        $('lcRead').disabled = !ok;
        $('lcRead').textContent = st.shots.length < 3 ? 'Add at least 3 leaves (10 is best)' : `Read my ${st.shots.length} leaves with Anee · ${OPT ? OPT.price : ''} credits`;
    }
    $('lcFiles').addEventListener('change', (e) => { addFiles(e.target.files); e.target.value = ''; });
    $('lcShots').addEventListener('click', (e) => { const b = e.target.closest('[data-drop]'); if (!b) return; st.shots.splice(Number(b.dataset.drop), 1); paintShots(); });
    function collage() {
        const n = st.shots.length, cols = n > 6 ? 4 : 3, rows = Math.ceil(n / cols), t = 300;
        const c = document.createElement('canvas'); c.width = cols * t; c.height = rows * t;
        const ctx = c.getContext('2d');
        ctx.fillStyle = '#fff'; ctx.fillRect(0, 0, c.width, c.height);
        st.shots.forEach((s, i) => {
            const x = (i % cols) * t, y = Math.floor(i / cols) * t;
            ctx.drawImage(s.canvas, x + 4, y + 4, t - 8, t - 8);
            ctx.fillStyle = 'rgba(0,0,0,.65)'; ctx.fillRect(x + 8, y + 8, 46, 34);
            ctx.fillStyle = '#fff'; ctx.font = 'bold 24px sans-serif'; ctx.fillText(String(i + 1), x + 18, y + 34);
        });
        return c.toDataURL('image/jpeg', 0.82);
    }
    $('lcRead').addEventListener('click', async () => {
        if (!OPT || !OPT.canUse) return;
        window.aneeWait.show({ title: 'Anee is reading your leaves…', lines: ['Matching each leaf to the chart…', 'Looking for spots, bites and other shortages…', 'Working out the urea for your area…'], sub: 'This takes about half a minute.' });
        try {
            const res = await window.api(U.generate, { method: 'POST', body: { ...caseBody(), image: collage(), leaves: st.shots.length, dgci: st.shots.map((s) => s.dgci) } });
            let data = res.data || {};
            if (data.pending) data = await window.aneeWait.poll({ id: data.id, job: U.job, phases: window.aneeWait.phases.plain, every: 2500 });
            await window.aneeWait.done({ title: 'Done!', line: `${data.charged} credits used.` });
            show(data);
        } catch (err) {
            window.aneeWait.fail && window.aneeWait.fail();
            toast(err.message, 'error');
        }
    });

    /* ---- the chart: a row of panels per leaf ---- */
    function paintLeaves() {
        const P = (OPT && OPT.panels) || [2, 2.5, 3, 3.5, 4, 4.5, 5];
        $('lcLeaves').innerHTML = st.chart.map((v, i) => `<div class="lc-leaf"><b>Leaf ${i + 1}</b><div class="lc-pick">${P.map((p) => `<button type="button" data-leaf="${i}" data-p="${p}" class="${v === p ? 'is-on' : ''}" style="background:${PANEL_COLOR[p][0]};color:${PANEL_COLOR[p][1]}">${p}</button>`).join('')}</div></div>`).join('');
    }
    $('lcLeaves').addEventListener('click', (e) => {
        const b = e.target.closest('[data-leaf]'); if (!b) return;
        const i = Number(b.dataset.leaf), p = Number(b.dataset.p);
        st.chart[i] = st.chart[i] === p ? null : p;
        paintLeaves();
    });
    $('lcCount').addEventListener('click', async () => {
        const panels = st.chart.filter((p) => p !== null);
        if (panels.length < 3) { toast('Tap a panel for at least 3 leaves (10 is best).', 'error'); return; }
        try {
            const res = await window.api(U.manual, { method: 'POST', body: { ...caseBody(), panels } });
            show(res.data);
        } catch (err) { toast(err.message, 'error'); }
    });

    /* ---- the answer ---- */
    function show(data) {
        const r = data.report || {}, d = r.decision || {}, a = r.anee || null;
        const days = st.method === 'dsr' ? 'sowing' : 'transplanting';
        let cls = 'is-ok', head = 'No nitrogen needed yet', line = `${d.below} of ${d.leaves} leaves read below panel ${d.critical}. That is not more than half, so hold the urea and check again on ${esc(d.nextCheck)}.`;
        if (d.stage === 'early') { cls = 'is-wait'; head = 'Too early to read'; line = `Start reading about ${d.startDay} days after ${days}, then every week. Before that, young leaves are naturally pale.`; }
        else if (d.stage === 'late') { cls = 'is-wait'; head = 'Past the nitrogen window'; line = 'The crop is at or past flowering. Nitrogen now feeds leaves, not grain, and invites pests and lodging. Keep the water steady instead.'; }
        else if (d.due) { cls = 'is-due'; head = 'Time for nitrogen'; line = `${d.below} of ${d.leaves} leaves read below panel ${d.critical}, more than half. Top dress about ${d.kgN} kg of nitrogen per hectare for the ${st.season} season, on 2 to 3 cm of water, late in the afternoon.`; }
        $('lcRes').innerHTML = `
            <div class="lc-verdict ${cls}">
                <h3>${esc(head)}</h3>
                <p>${line}</p>
                ${d.due ? `<div class="lc-dose"><span><b>${d.ureaPerHa} kg</b>urea per ha</span><span><b>${d.bagsPerHa}</b>bags per ha</span><span><b>${d.ureaForArea} kg</b>for ${d.areaHa} ha</span></div>` : ''}
                ${(r.panels || []).length ? `<div class="lc-dots">${r.panels.map((p) => `<span class="${p < d.critical ? 'is-low' : ''}" style="background:${(PANEL_COLOR[p] || ['#999'])[0]};color:${(PANEL_COLOR[p] || ['', '#fff'])[1]}">${p}</span>`).join('')}</div>` : ''}
            </div>
            ${a ? `
                ${a.health ? `<div class="lc-note"><b>What Anee sees:</b> ${esc(a.health)}</div>` : ''}
                ${(a.otherCauses || []).length ? `<div class="lc-note"><b>Not only nitrogen:</b><ul class="mt-1 ml-4 list-disc">${a.otherCauses.map((c) => `<li><b>${esc(c.cause)}</b>: ${esc(c.sign)}. ${esc(c.action)}</li>`).join('')}</ul></div>` : ''}
                ${(a.leaves || []).some((l) => l.note) ? `<div class="lc-note"><b>Leaf by leaf:</b> ${a.leaves.filter((l) => l.note).map((l) => `leaf ${l.n}: ${esc(l.note)}`).join('; ')}</div>` : ''}
                ${a.tip ? `<div class="lc-note"><b>This week:</b> ${esc(a.tip)}</div>` : ''}
                ${a.photoNote ? `<div class="lc-note"><b>Your photos (${esc(a.photoQuality || '')}):</b> ${esc(a.photoNote)}</div>` : ''}` : ''}`;
        requestAnimationFrame(() => document.querySelector('.lc-verdict')?.classList.add('is-in'));
        $('lcRes').scrollIntoView({ behavior: 'smooth', block: 'start' });
        if (data.history) paintHist(data.history);
    }
    function paintHist(rows) {
        $('lcHist').innerHTML = rows.length ? rows.map((h) => `<div><b>${esc(h.title)}</b><span>${esc(h.at)}</span><button type="button" data-del="${h.id}" aria-label="Delete">✕</button></div>`).join('') : '<p class="text-sm text-gray-500">No checks yet.</p>';
    }
    $('lcHist').addEventListener('click', async (e) => {
        const b = e.target.closest('[data-del]'); if (!b) return;
        try { const res = await window.api(U.del(b.dataset.del), { method: 'DELETE' }); paintHist(res.data.history || []); } catch (err) { toast(err.message, 'error'); }
    });

    async function boot() {
        // The chart and the photo tiles first: the options only add the price and history.
        paintShots();
        paintLeaves();
        try {
            const res = await window.api(U.options, { method: 'GET' });
            OPT = res.data;
            st.season = OPT.season;
            $('lcSeason').querySelectorAll('button').forEach((x) => x.classList.toggle('is-on', x.dataset.v === st.season));
            $('lcMat').innerHTML = '<option value="">Not sure</option>' + Object.entries(OPT.maturity).map(([k, v]) => `<option value="${k}">${esc(v)}</option>`).join('');
            $('lcPriceLine').textContent = OPT.canUse ? `${OPT.price} credits a reading` : 'Not on your plan yet';
            if (!OPT.canUse && OPT.whyNot) { $('lcWhy').hidden = false; $('lcWhy').textContent = OPT.whyNot; }
            paintHist(OPT.history || []);
        } catch (err) { toast(err.message, 'error'); }
        paintShots();
    }
    if (window.api) boot(); else window.addEventListener('load', boot, { once: true });
})();
</script>
@endpush
