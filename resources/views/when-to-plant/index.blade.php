@extends('layouts.app')
@section('title', 'When to Plant')
@section('page-title', 'When to Plant')
@section('page-subtitle', 'The right window, argued from the climate')

@section('content')
<style>
    /* ---- WHEN TO PLANT -------------------------------------------------
       A wizard that walks, a report that draws itself. Everything animates
       on the house curve and holds still under reduced motion. */
    .wtp-tabs { display: flex; gap: .4rem; margin-bottom: 1rem; }
    .wtp-tab { flex: 1 1 0; padding: .6rem; border-radius: .8rem; font-weight: 800; font-size: .9rem;
        text-align: center; color: var(--color-gray-500); background: var(--color-white);
        border: 1px solid var(--color-gray-200); cursor: pointer; }
    .wtp-tab.is-on { background: var(--color-brand-600); border-color: var(--color-brand-600); color: #fff; }

    /* The price, said before anything is spent. */
    .wtp-quote { display: flex; align-items: center; gap: .7rem; padding: .8rem .9rem; border-radius: .9rem;
        background: linear-gradient(115deg, #f3f8ec, #e4efd4); border: 1px solid #cfe3b8; margin-bottom: 1rem; }
    .wtp-quote b { color: #2d5016; }
    .wtp-quote .q-ico { font-size: 1.4rem; }
    .wtp-quote .q-t { font-size: .82rem; color: #3d5226; line-height: 1.45; }

    /* The wizard: steps slide past each other; the rail says where you are. */
    .wtp-wiz { position: relative; overflow: hidden; }
    .wtp-step { display: none; }
    .wtp-step.is-on { display: block; animation: wtpIn .32s cubic-bezier(.22,1,.36,1) both; }
    @keyframes wtpIn { from { opacity: 0; transform: translateX(24px); } to { opacity: 1; transform: none; } }
    .wtp-step.is-back { animation-name: wtpBack; }
    @keyframes wtpBack { from { opacity: 0; transform: translateX(-24px); } to { opacity: 1; transform: none; } }
    .wtp-dots { display: flex; gap: .35rem; justify-content: center; margin: 1rem 0 .2rem; }
    .wtp-dot { width: .5rem; height: .5rem; border-radius: 999px; background: var(--color-gray-200);
        transition: all .28s cubic-bezier(.22,1,.36,1); }
    .wtp-dot.is-on { width: 1.4rem; background: var(--color-brand-600); }
    .wtp-q { font-size: 1.05rem; font-weight: 800; color: var(--color-gray-900); margin-bottom: .2rem; }
    .wtp-sub { font-size: .8rem; color: var(--color-gray-500); margin-bottom: .8rem; }

    .wtp-choices { display: grid; gap: .5rem; }
    .wtp-choice { display: flex; align-items: center; gap: .7rem; padding: .75rem .85rem; border-radius: .9rem;
        border: 1.5px solid var(--color-gray-200); background: var(--color-white); cursor: pointer;
        text-align: left; font-weight: 700; color: var(--color-gray-800); font-size: .9rem;
        transition: transform .28s cubic-bezier(.22,1,.36,1), border-color .2s, background .2s; }
    .wtp-choice:hover { transform: translateY(-1px); }
    .wtp-choice.is-on { border-color: var(--color-brand-600); background: var(--color-brand-50); color: var(--color-brand-800); }
    .wtp-choice .c-e { font-size: 1.3rem; }
    .wtp-choice small { display: block; font-weight: 500; font-size: .72rem; color: var(--color-gray-500); }

    .wtp-probs { display: grid; grid-template-columns: 1fr; gap: .4rem; }
    @media (min-width: 640px) { .wtp-probs { grid-template-columns: 1fr 1fr; } }
    .wtp-prob { display: flex; align-items: center; gap: .55rem; padding: .55rem .7rem; border-radius: .7rem;
        border: 1.5px solid var(--color-gray-200); background: var(--color-white); cursor: pointer;
        font-size: .8rem; font-weight: 600; color: var(--color-gray-700); }
    .wtp-prob input { accent-color: #4a7c2a; width: 1rem; height: 1rem; flex: none; }
    .wtp-prob.is-on { border-color: var(--color-brand-500); background: var(--color-brand-50); }

    .wtp-nav { display: flex; gap: .6rem; margin-top: 1.1rem; }

    /* The run button breathes the moving green the app's doors wear. */
    .wtp-run { display: flex; align-items: center; justify-content: center; gap: .5rem; width: 100%;
        padding: .85rem 1rem; border-radius: 1rem; color: #fff; font-weight: 800; font-size: .95rem;
        background: linear-gradient(115deg, #7bb24a, #4a7c2a 30%, #3d6823 55%, #6b9f3d 80%, #8fc96a);
        background-size: 260% 100%; animation: wtpTide 5.5s ease-in-out infinite alternate;
        box-shadow: 0 10px 22px -12px rgb(61 104 35 / .65); }
    .wtp-run:disabled { opacity: .6; }
    @keyframes wtpTide { from { background-position: 0% 50%; } to { background-position: 100% 50%; } }

    /* While the model thinks: the wait-card idiom, one scene deep. */
    .wtp-wait { display: none; flex-direction: column; align-items: center; gap: .6rem; padding: 2rem 1rem; text-align: center; }
    .wtp-wait.is-on { display: flex; }
    .wtp-wait .w-spin { width: 2.4rem; height: 2.4rem; border-radius: 999px; border: 3px solid var(--color-brand-200);
        border-top-color: var(--color-brand-600); animation: wtpSpin .8s linear infinite; }
    @keyframes wtpSpin { to { transform: rotate(360deg); } }
    .wtp-wait p { font-size: .85rem; color: var(--color-gray-500); max-width: 22rem; }

    /* ---- THE REPORT ---- */
    .wtp-report { display: grid; gap: .9rem; }
    .wtp-hero { border-radius: 1.1rem; padding: 1.1rem 1.2rem; color: #fff;
        background: linear-gradient(130deg, #4a7c2a, #2d5016 70%); }
    .wtp-hero h2 { font-size: 1.15rem; font-weight: 800; margin-bottom: .15rem; }
    .wtp-hero .h-win { font-size: 1.5rem; font-weight: 800; letter-spacing: .01em; }
    .wtp-hero .h-why { font-size: .84rem; opacity: .92; line-height: 1.55; margin-top: .45rem; }
    .wtp-chips { display: flex; flex-wrap: wrap; gap: .35rem; margin-top: .6rem; }
    .wtp-chip { font-size: .68rem; font-weight: 700; padding: .18rem .55rem; border-radius: 999px;
        background: rgb(255 255 255 / .18); }

    .wtp-card { border-radius: 1rem; border: 1px solid var(--color-gray-200); background: var(--color-white);
        padding: 1rem 1.1rem; }
    .wtp-card h3 { font-weight: 800; font-size: .92rem; color: var(--color-gray-900); margin-bottom: .6rem; }

    /* Twelve bars: the year, scored. They grow when the report lands. */
    .wtp-months { display: flex; align-items: flex-end; gap: .3rem; height: 8rem; }
    .wtp-mcol { flex: 1 1 0; display: flex; flex-direction: column; align-items: center; gap: .25rem;
        height: 100%; justify-content: flex-end; min-width: 0; }
    .wtp-mbar { width: 100%; max-width: 1.6rem; border-radius: .3rem .3rem 0 0; background: var(--color-gray-200);
        transform-origin: bottom; transform: scaleY(0); min-height: 3px;
        transition: transform .6s cubic-bezier(.22,1,.36,1); position: relative; }
    .wtp-report.is-drawn .wtp-mbar { transform: scaleY(1); }
    .wtp-mbar.is-best { background: var(--color-brand-600); }
    .wtp-mbar.is-good { background: var(--color-brand-300); }
    .wtp-mbar.is-poor { background: #f0c274; }
    .wtp-mbar.is-bad { background: #fca5a5; }
    .wtp-mlbl { font-size: .58rem; font-weight: 700; color: var(--color-gray-500); }
    .wtp-mnote { font-size: .68rem; color: var(--color-gray-500); margin-top: .5rem; line-height: 1.5; }

    /* Planting to harvest: stage bands, widths in days. */
    .wtp-line { display: flex; border-radius: .6rem; overflow: hidden; height: 2.3rem; }
    .wtp-seg { display: flex; align-items: center; justify-content: center; font-size: .62rem; font-weight: 800;
        color: #fff; white-space: nowrap; overflow: hidden; min-width: 0;
        transform-origin: left; transform: scaleX(0); transition: transform .7s cubic-bezier(.22,1,.36,1); }
    .wtp-report.is-drawn .wtp-seg { transform: scaleX(1); }
    .wtp-legend { display: flex; flex-wrap: wrap; gap: .5rem .9rem; margin-top: .55rem; }
    .wtp-legend span { display: inline-flex; align-items: center; gap: .35rem; font-size: .72rem; color: var(--color-gray-600); }
    .wtp-legend i { width: .7rem; height: .7rem; border-radius: .2rem; }

    .wtp-threat { display: flex; gap: .6rem; padding: .6rem .7rem; border-radius: .7rem; margin-bottom: .45rem;
        font-size: .8rem; line-height: 1.5; border: 1px solid; opacity: 0; transform: translateY(6px);
        transition: all .45s cubic-bezier(.22,1,.36,1); }
    .wtp-report.is-drawn .wtp-threat { opacity: 1; transform: none; }
    .wtp-threat.sev-high { background: #fef2f2; border-color: #fecaca; color: #7f1d1d; }
    .wtp-threat.sev-moderate { background: #fffbeb; border-color: #fde68a; color: #713f12; }
    .wtp-threat.sev-low { background: var(--color-gray-50); border-color: var(--color-gray-200); color: var(--color-gray-600); }
    .wtp-threat b { display: block; font-size: .72rem; text-transform: uppercase; letter-spacing: .03em; opacity: .8; }

    .wtp-gap { font-size: .78rem; color: var(--color-gray-500); line-height: 1.55; }
    .wtp-gap li { list-style: disc; margin-left: 1.1rem; }
    .wtp-fine { font-size: .72rem; color: var(--color-gray-400); line-height: 1.55; }

    .wtp-acts { display: grid; gap: .5rem; }
    @media (min-width: 640px) { .wtp-acts { grid-template-columns: 1fr 1fr; } }

    /* Saved rows. */
    .wtp-saved { display: flex; align-items: center; gap: .7rem; width: 100%; text-align: left;
        padding: .8rem .9rem; border-bottom: 1px solid var(--color-gray-100); cursor: pointer; }
    .wtp-saved:hover { background: var(--color-gray-50); }
    .wtp-saved b { display: block; font-size: .88rem; color: var(--color-gray-900); }
    .wtp-saved small { color: var(--color-gray-400); font-size: .72rem; }

    /* Night. */
    html.dark .wtp-tab { background: #151b12; border-color: #2b3a1c; color: #93a684; }
    html.dark .wtp-tab.is-on { background: #4a7c2a; border-color: #4a7c2a; color: #fff; }
    html.dark .wtp-quote { background: linear-gradient(115deg, #1c2913, #22301a); border-color: #2b3a1c; }
    html.dark .wtp-quote b { color: #cfe6b8; }
    html.dark .wtp-quote .q-t { color: #a8bd93; }
    html.dark .wtp-q { color: #e8efe1; }
    html.dark .wtp-choice, html.dark .wtp-prob { background: #151b12; border-color: #2b3a1c; color: #d5e3c5; }
    html.dark .wtp-choice.is-on { background: #22301a; border-color: #6b9f3d; color: #cfe6b8; }
    html.dark .wtp-prob.is-on { background: #22301a; border-color: #6b9f3d; }
    html.dark .wtp-card { background: #151b12; border-color: #2b3a1c; }
    html.dark .wtp-card h3 { color: #e8efe1; }
    html.dark .wtp-mbar { background: #2b3a1c; }
    html.dark .wtp-saved { border-color: #222b1a; }
    html.dark .wtp-saved:hover { background: #161e10; }
    html.dark .wtp-saved b { color: #e8efe1; }
    html.dark .wtp-threat.sev-high { background: #2a1414; border-color: #4c1d1d; color: #fca5a5; }
    html.dark .wtp-threat.sev-moderate { background: #241d10; border-color: #4d3a12; color: #f0c274; }
    html.dark .wtp-threat.sev-low { background: #161e10; border-color: #2b3a1c; color: #a8bd93; }
    /* The calendar, plainly: one green row to plant by, red rows to keep
       away from. */
    /* Flowing prose, not columns: the bold dates lead and the reason runs
       on after them, however narrow the screen. */
    .wtp-win { display: block; padding: .6rem .7rem;
        border-radius: .7rem; margin-bottom: .45rem; font-size: .84rem; line-height: 1.55;
        border: 1px solid; }
    .wtp-win b { margin-right: .3rem; }
    .wtp-win.is-go { background: #f0f7e8; border-color: #cfe3b8; color: #2d5016; }
    .wtp-win.is-no { background: #fef2f2; border-color: #fecaca; color: #7f1d1d; }
    .wtp-win.is-no.sev-moderate { background: #fffbeb; border-color: #fde68a; color: #713f12; }
    html.dark .wtp-win.is-go { background: #1c2913; border-color: #2b3a1c; color: #cfe6b8; }
    html.dark .wtp-win.is-no { background: #2a1414; border-color: #4c1d1d; color: #fca5a5; }
    html.dark .wtp-win.is-no.sev-moderate { background: #241d10; border-color: #4d3a12; color: #f0c274; }

    /* The summary in its own voice: the dark remap outguns dark: utilities,
       so the words carry their own class. */
    .wtp-plain { font-size: .875rem; line-height: 1.65; color: var(--color-gray-700); }
    html.dark .wtp-plain { color: #d5e3c5; }

    /* The attach button wears Anee's own face. */
    .wtp-anee-face { width: 1.15rem; height: 1.15rem; border-radius: 999px; object-fit: cover; }

    /* A report that fits a hand: tighter hero, chart labels kept, the
       timeline's in-band words stand down and the legend speaks for them. */
    @media (max-width: 639px) {
        .wtp-hero { padding: .9rem 1rem; }
        .wtp-hero .h-win { font-size: 1.15rem; }
        .wtp-card { padding: .8rem .85rem; }
        .wtp-months { gap: .2rem; height: 6.5rem; }
        .wtp-seg { font-size: 0; }
        .wtp-line { height: 1.5rem; }
    }

    @media (prefers-reduced-motion: reduce) {
        .wtp-step.is-on { animation: none; }
        .wtp-run { animation: none; }
        .wtp-mbar, .wtp-seg, .wtp-threat, .wtp-dot { transition: none; transform: none; opacity: 1; }
    }
</style>

<div class="max-w-2xl mx-auto">
    <div class="wtp-tabs" role="tablist">
        <button type="button" class="wtp-tab is-on" id="wtpTabGen">Generate</button>
        <button type="button" class="wtp-tab" id="wtpTabSaved">Saved</button>
    </div>

    <div id="wtpGen">
        <div class="wtp-quote" id="wtpQuote" hidden>
            <span class="q-ico">🔎</span>
            <span class="q-t" id="wtpQuoteText"></span>
        </div>

        <div class="card p-5 wtp-wiz" id="wtpWiz">
            {{-- Step 1: the year --}}
            <section class="wtp-step is-on" data-step="0">
                <p class="wtp-q">What year will you plant?</p>
                <p class="wtp-sub">The analysis reads the climate's patterns for that calendar year.</p>
                <div class="wtp-choices" id="wtpYears"></div>
            </section>
            {{-- Step 2: the season --}}
            <section class="wtp-step" data-step="1">
                <p class="wtp-q">Which cropping season?</p>
                <p class="wtp-sub">The window is searched inside the season you actually farm.</p>
                <div class="wtp-choices" id="wtpSeasons"></div>
            </section>
            {{-- Step 3: the crop --}}
            <section class="wtp-step" data-step="2">
                <p class="wtp-q">What will you plant?</p>
                <p class="wtp-sub">The same catalogue your lots choose from.</p>
                <select id="wtpCrop" class="form-select"></select>
            </section>
            {{-- Step 4: the variety --}}
            <section class="wtp-step" data-step="3">
                <p class="wtp-q">Which variety?</p>
                <p class="wtp-sub">Type it as it is sold — e.g. NSIC Rc222. If its data is not published, the analysis will say so rather than guess.</p>
                <input type="text" id="wtpVariety" class="form-input" maxlength="80" placeholder="Variety name (optional)">
            </section>
            {{-- Step 5: the place --}}
            <section class="wtp-step" data-step="4">
                <p class="wtp-q">Where is the field?</p>
                <p class="wtp-sub">Town and province is enough — the climate patterns differ by region.</p>
                <input type="text" id="wtpLocation" class="form-input" maxlength="160" placeholder="e.g. Urdaneta, Pangasinan">
            </section>
            {{-- Step 6: the troubles --}}
            <section class="wtp-step" data-step="5">
                <p class="wtp-q">What does this field struggle with?</p>
                <p class="wtp-sub">Tick what you have seen — each one moves the window.</p>
                <div class="wtp-probs" id="wtpProbs"></div>
            </section>
            {{-- Step 7: the decision --}}
            <section class="wtp-step" data-step="6">
                <p class="wtp-q">Ready to run it?</p>
                <p class="wtp-sub" id="wtpReview"></p>
                <button type="button" class="wtp-run" id="wtpRun">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    <span id="wtpRunSays">Run the analysis</span>
                </button>
                <p class="text-xs text-gray-400 mt-2 text-center" id="wtpRunFine"></p>
            </section>

            <div class="wtp-wait" id="wtpWait">
                <span class="w-spin"></span>
                <p><b>Reading the climate for your field…</b><br>Typhoon seasonality, the wet and dry rhythm, and your crop's own calendar. Half a minute, usually.</p>
            </div>

            <div class="wtp-dots" id="wtpDots"></div>
            <div class="wtp-nav" id="wtpNav">
                <button type="button" class="btn btn-white flex-1" id="wtpBack" disabled>Back</button>
                <button type="button" class="btn btn-primary flex-1" id="wtpNext">Next</button>
            </div>
        </div>

        <div class="wtp-report mt-4" id="wtpReport" hidden></div>
    </div>

    <div id="wtpSavedPane" class="hidden">
        <div class="card !p-0 overflow-hidden">
            <div id="wtpSavedList"></div>
            <div id="wtpSavedEmpty" class="hidden text-center py-10">
                <p class="font-bold text-gray-900 dark:text-gray-100">Nothing saved yet</p>
                <p class="text-sm text-gray-400">Run an analysis and keep the ones worth keeping.</p>
            </div>
        </div>
        <div class="wtp-report mt-4" id="wtpSavedReport" hidden></div>
    </div>
</div>

<script>
(() => {
    const $id = (x) => document.getElementById(x);
    const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const U = {
        options: '{{ route('wtp.options') }}',
        generate: '{{ route('wtp.generate') }}',
        save: '{{ route('wtp.save') }}',
        list: '{{ route('wtp.list') }}',
        one: (id) => '{{ url('/app/when-to-plant/one') }}/' + id,
        del: (id) => '{{ url('/app/when-to-plant') }}/' + id,
        anee: '{{ route('ai.index') }}',
    };
    const MONTHS = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    const SEG_HUES = ['#4a7c2a', '#6b9f3d', '#b45309', '#1d4ed8', '#5b21b6', '#0e7490', '#9f1239'];

    let OPT = null;
    const state = { year: null, season: null, crop: '', variety: '', location: '', problems: [] };
    let step = 0;
    const STEPS = 7;
    let LAST = null;   // {report, params, charged} — what Save keeps

    /* ---------------- boot ---------------- */
    async function boot() {
        try {
            const res = await api(U.options, { method: 'GET' });
            OPT = res.data;
            paintOptions();
        } catch (err) { toast(err.message, 'error'); }
    }

    function paintOptions() {
        $id('wtpYears').innerHTML = OPT.years.map((y) => `
            <button type="button" class="wtp-choice" data-year="${y}"><span class="c-e">🗓️</span><span>${y}${y === OPT.years[0] ? '<small>This year</small>' : ''}</span></button>`).join('');
        const seasonIcons = { dry: '☀️', wet: '🌧️', third: '🌗' };
        const seasonSubs = {
            dry: 'Roughly November to April in most lowland regions',
            wet: 'Roughly May to October in most lowland regions',
            third: 'The in-between window after the main two',
        };
        $id('wtpSeasons').innerHTML = Object.entries(OPT.seasons).map(([k, label]) => `
            <button type="button" class="wtp-choice" data-season="${k}"><span class="c-e">${seasonIcons[k] || '🌱'}</span><span>${esc(label)}<small>${esc(seasonSubs[k] || '')}</small></span></button>`).join('');
        const groups = {};
        OPT.crops.forEach((c) => { (groups[c.group] = groups[c.group] || []).push(c); });
        $id('wtpCrop').innerHTML = '<option value="">Pick the crop…</option>' + Object.entries(groups).map(([g, list]) => `
            <optgroup label="${esc(g)}">${list.map((c) => `<option value="${esc(c.key)}">${esc(c.icon)} ${esc(c.label)}</option>`).join('')}</optgroup>`).join('');
        $id('wtpProbs').innerHTML = Object.entries(OPT.problems).map(([k, label]) => `
            <label class="wtp-prob" data-prob="${k}"><input type="checkbox" value="${k}"><span>${esc(label)}</span></label>`).join('');
        $id('wtpDots').innerHTML = Array.from({ length: STEPS }, (_, i) => `<span class="wtp-dot${i === 0 ? ' is-on' : ''}"></span>`).join('');

        const q = $id('wtpQuote');
        if (OPT.canUse && OPT.quote) {
            $id('wtpQuoteText').innerHTML = `One analysis spends about <b>${OPT.quote} credits</b>. You have <b>${Number(OPT.balance).toLocaleString()}</b> — nothing is charged until you press Run. It is a guide, not a promise — weather and climate always carry uncertainty — but a window argued from the data beats deciding with nothing to compare against.`;
            q.hidden = false;
        } else if (!OPT.canUse) {
            $id('wtpQuoteText').innerHTML = esc(OPT.whyNot || 'The analysis is not available right now.');
            q.hidden = false;
        }
    }

    /* ---------------- the walk ---------------- */
    function show(n, backwards) {
        step = Math.max(0, Math.min(STEPS - 1, n));
        document.querySelectorAll('.wtp-step').forEach((s) => {
            const on = Number(s.getAttribute('data-step')) === step;
            s.classList.toggle('is-on', on);
            s.classList.toggle('is-back', on && !!backwards);
        });
        document.querySelectorAll('.wtp-dot').forEach((d, i) => d.classList.toggle('is-on', i <= step));
        $id('wtpBack').disabled = step === 0;
        $id('wtpNext').style.display = step === STEPS - 1 ? 'none' : '';
        if (step === STEPS - 1) review();
    }

    function stepReady() {
        switch (step) {
            case 0: return !!state.year || (toast('Pick the year first.', 'error'), false);
            case 1: return !!state.season || (toast('Pick the season.', 'error'), false);
            case 2: state.crop = $id('wtpCrop').value; return !!state.crop || (toast('Pick the crop.', 'error'), false);
            case 3: state.variety = $id('wtpVariety').value.trim(); return true;
            case 4: state.location = $id('wtpLocation').value.trim();
                return !!state.location || (toast('Say where the field is.', 'error'), false);
            case 5: state.problems = [...document.querySelectorAll('#wtpProbs input:checked')].map((i) => i.value); return true;
            default: return true;
        }
    }

    function review() {
        const crop = (OPT.crops.find((c) => c.key === state.crop) || {});
        $id('wtpReview').innerHTML = `${esc(crop.icon || '')} <b>${esc(crop.label || '')}</b>`
            + `${state.variety ? ' · ' + esc(state.variety) : ''} · ${esc(OPT.seasons[state.season] || '')} ${state.year}`
            + ` · ${esc(state.location)}`
            + (state.problems.length ? `<br><span class="text-xs">${state.problems.length} field problem${state.problems.length === 1 ? '' : 's'} considered</span>` : '');
        $id('wtpRunSays').textContent = OPT.canUse && OPT.quote ? `Run the analysis (~${OPT.quote} credits)` : 'Run the analysis';
        $id('wtpRunFine').textContent = OPT.canUse
            ? 'Charged to the same AI credits your questions use — it shows in your subscription’s credit log.'
            : (OPT.whyNot || '');
        $id('wtpRun').disabled = !OPT.canUse;
    }

    $id('wtpNext').addEventListener('click', () => { if (stepReady()) show(step + 1); });
    $id('wtpBack').addEventListener('click', () => show(step - 1, true));
    $id('wtpYears').addEventListener('click', (e) => {
        const b = e.target.closest('[data-year]');
        if (!b) return;
        state.year = Number(b.getAttribute('data-year'));
        document.querySelectorAll('#wtpYears .wtp-choice').forEach((c) => c.classList.toggle('is-on', c === b));
        setTimeout(() => show(1), 180);
    });
    $id('wtpSeasons').addEventListener('click', (e) => {
        const b = e.target.closest('[data-season]');
        if (!b) return;
        state.season = b.getAttribute('data-season');
        document.querySelectorAll('#wtpSeasons .wtp-choice').forEach((c) => c.classList.toggle('is-on', c === b));
        setTimeout(() => show(2), 180);
    });
    $id('wtpProbs').addEventListener('change', (e) => {
        const l = e.target.closest('.wtp-prob');
        if (l) l.classList.toggle('is-on', e.target.checked);
    });

    /* ---------------- the run ---------------- */
    $id('wtpRun').addEventListener('click', async () => {
        if (!stepReady()) return;
        const wiz = $id('wtpWiz');
        wiz.querySelectorAll('.wtp-step, .wtp-nav, .wtp-dots').forEach((el) => el.style.display = 'none');
        $id('wtpWait').classList.add('is-on');
        $id('wtpReport').hidden = true;
        let landed = false;
        try {
            const res = await api(U.generate, { method: 'POST', body: {
                year: state.year, season: state.season, crop: state.crop,
                variety: state.variety, location: state.location, problems: state.problems,
            } });
            LAST = { report: res.data.report, params: res.data.params, charged: res.data.charged, savedId: null };
            OPT.balance = res.data.balance;
            landed = true;
            drawReport($id('wtpReport'), LAST, 'fresh');
            toast(`Done — ${res.data.charged} credits used.`);
        } catch (err) {
            toast(err.message, 'error');
        } finally {
            $id('wtpWait').classList.remove('is-on');
            wiz.querySelectorAll('.wtp-step, .wtp-nav, .wtp-dots').forEach((el) => el.style.display = '');
            show(step);
            // The report has the floor: the form and its price bow out until
            // the farmer asks for another run.
            if (landed) { wiz.hidden = true; $id('wtpQuote').hidden = true; }
        }
    });

    function wizardBack() {
        $id('wtpWiz').hidden = false;
        if (OPT && OPT.canUse && OPT.quote) $id('wtpQuote').hidden = false;
        $id('wtpReport').hidden = true;
        $id('wtpReport').classList.remove('is-drawn');
        show(0);
        $id('wtpWiz').scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    /* ---------------- the report, drawn ---------------- */
    function drawReport(host, item, mode) {
        const r = item.report;
        const p = item.params;
        // Older saved reports carry the persona's :anee-…: shortcodes, which
        // have no renderer here — swept so prose reads as prose.
        const sweep = (t) => String(t || '').replace(/:[a-z0-9_-]+:/gi, '').replace(/\s{2,}/g, ' ').trim();
        const crop = (OPT ? OPT.crops.find((c) => c.key === p.crop) : null) || {};
        const bw = r.bestWindow || {};
        const m1 = MONTHS[(bw.fromMonth || 1) - 1];
        const m2 = MONTHS[(bw.toMonth || 1) - 1];
        const bestMonths = new Set();
        for (let m = bw.fromMonth; m; m = (m === bw.toMonth ? 0 : (m % 12) + 1)) { bestMonths.add(m); if (bestMonths.size > 12) break; }

        const scores = (r.monthScores || []).slice(0, 12);

        const windowsCard = `
            <div class="wtp-card">
                <h3>The calendar, plainly</h3>
                <div class="wtp-win is-go"><b>🌱 Plant: ${esc(bw.label || '')}</b><span>${esc(sweep(bw.why))}</span></div>
                ${(r.avoidWindows || []).map((w) => `
                    <div class="wtp-win is-no sev-${esc(w.severity === 'moderate' ? 'moderate' : 'high')}">
                        <b>⛔ Avoid ${esc(w.label || '')}:</b><span>${esc(w.why || '')}</span>
                    </div>`).join('')}
            </div>`;

        host.innerHTML = `
            <div class="wtp-hero">
                <h2>${esc(crop.icon || '🌱')} ${esc(crop.label || 'Your crop')} — ${esc(OPT ? OPT.seasons[p.season] || '' : '')} ${esc(String(p.year || ''))}</h2>
                <p class="h-win">${esc(bw.label || (m1 + ' ' + (bw.fromDay || '') + ' – ' + m2 + ' ' + (bw.toDay || '')))}</p>
                <p class="h-why">${esc(sweep(bw.why))}</p>
                <div class="wtp-chips">
                    <span class="wtp-chip">📍 ${esc(p.location || '')}</span>
                    ${p.variety ? `<span class="wtp-chip">🧬 ${esc(p.variety)}</span>` : ''}
                    <span class="wtp-chip">Confidence: ${esc(r.confidence || 'moderate')}</span>
                    ${item.charged ? `<span class="wtp-chip">${item.charged} credits</span>` : ''}
                </div>
            </div>

            ${windowsCard}

            <div class="wtp-card">
                <h3>How each month scores for planting</h3>
                <div class="wtp-months">
                    ${scores.map((s) => {
                        const cls = bestMonths.has(s.month) ? 'is-best' : (s.score >= 65 ? 'is-good' : (s.score >= 35 ? 'is-poor' : 'is-bad'));
                        return `<div class="wtp-mcol">
                            <div class="wtp-mbar ${cls}" style="height:${Math.max(4, s.score)}%" title="${esc(s.note || '')}"></div>
                            <span class="wtp-mlbl">${MONTHS[(s.month || 1) - 1]}</span>
                        </div>`;
                    }).join('')}
                </div>
                <p class="wtp-mnote">Green is the recommended window; lighter green still works, amber is risky, red is asking for trouble. Hover a bar for its note.</p>
            </div>

            ${(r.threats || []).length ? `
            <div class="wtp-card">
                <h3>If you plant outside the window</h3>
                ${(r.threats || []).map((t, i) => `
                    <div class="wtp-threat sev-${esc(t.severity || 'moderate')}" style="transition-delay:${i * 80}ms">
                        <span>⚠️</span>
                        <span><b>${esc(t.whenNot || '')}</b>${esc(t.threat || '')}</span>
                    </div>`).join('')}
            </div>` : ''}

            <div class="wtp-card">
                <h3>In plain words</h3>
                <p class="wtp-plain">${esc(sweep(r.summary))}</p>
                ${(r.dataGaps || []).length ? `
                    <h3 class="mt-4">What this analysis could not know</h3>
                    <ul class="wtp-gap">${(r.dataGaps || []).map((g) => `<li>${esc(g)}</li>`).join('')}</ul>` : ''}
            </div>

            <div class="wtp-card">
                <h3>🧭 A guide, not a promise</h3>
                <p class="wtp-fine">Weather and climate carry real uncertainty, and no analysis can see a particular storm. What this gives you is a data-grounded starting point — the patterns of past seasons weighed against your crop and your field — which beats deciding with nothing to compare against. Check PAGASA advisories as planting approaches.</p>
            </div>

            <div class="wtp-acts">
                ${mode === 'fresh' ? `<button type="button" class="btn btn-primary w-full" id="wtpSave">💾 Save this analysis</button>` : ''}
                <button type="button" class="btn btn-white w-full" id="${mode === 'fresh' ? 'wtpAttach' : 'wtpAttachSaved'}" ${mode === 'fresh' ? 'disabled title="Save it first — Anee reads the saved copy"' : ''}>
                    ${OPT && OPT.aneeFace ? `<img class="wtp-anee-face" src="${esc(OPT.aneeFace)}" alt="">` : '🤖'} Attach to Anee
                </button>
                ${mode === 'fresh' ? `<button type="button" class="btn btn-white w-full" id="wtpAgain">⚡ Run another analysis</button>` : ''}
                ${mode === 'saved' ? `<button type="button" class="btn btn-white w-full" id="wtpDelete">🗑 Delete</button>` : ''}
            </div>`;

        host.hidden = false;
        requestAnimationFrame(() => requestAnimationFrame(() => host.classList.add('is-drawn')));
        host.scrollIntoView({ behavior: 'smooth', block: 'start' });

        if (mode === 'fresh') {
            host.querySelector('#wtpSave').addEventListener('click', async (e) => {
                e.currentTarget.disabled = true;
                try {
                    const res = await api(U.save, { method: 'POST', body: { params: item.params, report: item.report, charged: item.charged } });
                    toast(res.message);
                    LAST.savedId = res.data.id;
                    const at = host.querySelector('#wtpAttach');
                    at.disabled = false;
                    at.removeAttribute('title');
                } catch (err) { toast(err.message, 'error'); e.currentTarget.disabled = false; }
            });
            host.querySelector('#wtpAttach').addEventListener('click', () => {
                if (LAST.savedId) window.location.href = U.anee + '?analysis=' + LAST.savedId;
            });
            host.querySelector('#wtpAgain').addEventListener('click', wizardBack);
        } else {
            host.querySelector('#wtpAttachSaved').addEventListener('click', () => {
                window.location.href = U.anee + '?analysis=' + item.savedId;
            });
            host.querySelector('#wtpDelete').addEventListener('click', async () => {
                const ok = window.confirmAction
                    ? await confirmAction({ title: 'Delete this analysis?', message: 'The credits it cost are already spent; only the report goes.', confirmText: 'Delete', danger: true })
                    : confirm('Delete this analysis?');
                if (!ok) return;
                try {
                    const res = await api(U.del(item.savedId), { method: 'DELETE' });
                    toast(res.message);
                    host.hidden = true;
                    loadSaved();
                } catch (err) { toast(err.message, 'error'); }
            });
        }
    }

    /* ---------------- saved ---------------- */
    async function loadSaved() {
        try {
            const res = await api(U.list, { method: 'GET' });
            const rows = res.data.rows || [];
            $id('wtpSavedList').innerHTML = rows.map((r) => `
                <button type="button" class="wtp-saved" data-saved="${r.id}">
                    <span class="grow min-w-0"><b>${esc(r.title)}</b><small>${esc(r.at)} · ${r.credits} credits</small></span>
                    <svg class="w-4 h-4 text-gray-300 shrink-0" fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                </button>`).join('');
            $id('wtpSavedEmpty').classList.toggle('hidden', rows.length > 0);
        } catch (err) { toast(err.message, 'error'); }
    }

    document.addEventListener('click', async (e) => {
        const b = e.target.closest('[data-saved]');
        if (!b) return;
        try {
            const res = await api(U.one(b.getAttribute('data-saved')), { method: 'GET' });
            const host = $id('wtpSavedReport');
            host.classList.remove('is-drawn');
            drawReport(host, {
                report: res.data.report, params: res.data.params,
                charged: res.data.credits, savedId: res.data.id,
            }, 'saved');
        } catch (err) { toast(err.message, 'error'); }
    });

    /* ---------------- tabs ---------------- */
    const tab = (which) => {
        $id('wtpGen').classList.toggle('hidden', which !== 'gen');
        $id('wtpSavedPane').classList.toggle('hidden', which !== 'saved');
        $id('wtpTabGen').classList.toggle('is-on', which === 'gen');
        $id('wtpTabSaved').classList.toggle('is-on', which === 'saved');
        if (which === 'saved') loadSaved();
    };
    $id('wtpTabGen').addEventListener('click', () => tab('gen'));
    $id('wtpTabSaved').addEventListener('click', () => tab('saved'));

    if (window.api) boot();
    else window.addEventListener('load', boot, { once: true });
})();
</script>
@endsection
