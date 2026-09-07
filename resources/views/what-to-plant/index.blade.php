@extends('layouts.app')
@section('title', 'What to Plant')
@section('page-title', 'What to Plant')
@section('page-subtitle', 'The right crop, argued from the ground')

@section('back', route('app.dashboard'))
@push('scripts')
<script>
    // Back goes to wherever you actually came from - the home card or the
    // schedules page - and only falls back to Home on a cold open.
    document.getElementById('appBackLink')?.addEventListener('click', (e) => {
        try {
            const ref = document.referrer ? new URL(document.referrer) : null;
            if (ref && ref.origin === location.origin && window.history.length > 1) {
                e.preventDefault();
                history.back();
            }
        } catch (_) { /* the href already points home */ }
    });
</script>
@endpush


@section('content')
<style>
    /* ---- WHAT TO PLANT -------------------------------------------------
       The sister of When to Plant: same wizard walk, same veil, same
       shelf — but the answer is a ranked list of crops, not a window.
       The wtp-* dress is copied whole so the two read as one family. */
    .wtp-tabs { display: flex; gap: .4rem; margin-bottom: 1rem; }
    .wtp-tab { flex: 1 1 0; padding: .6rem; border-radius: .8rem; font-weight: 800; font-size: .9rem;
        text-align: center; color: var(--color-gray-500); background: var(--color-white);
        border: 1px solid var(--color-gray-200); cursor: pointer; }
    .wtp-tab.is-on { background: var(--color-brand-600); border-color: var(--color-brand-600); color: #fff; }

    .wtp-quote { border-radius: .9rem; margin-bottom: 1rem; overflow: hidden;
        background: linear-gradient(115deg, #f3f8ec, #e4efd4); border: 1px solid #cfe3b8; }
    .wtp-quote b { color: #2d5016; }
    .q-head { display: flex; align-items: center; gap: .6rem; width: 100%; text-align: left;
        padding: .7rem .9rem; cursor: pointer; }
    .q-head .q-ico { font-size: 1.15rem; flex: none; }
    .q-title { flex: 1 1 auto; min-width: 0; font-size: .84rem; font-weight: 800; color: #2d5016; }
    .q-hint { flex: none; font-size: .74rem; font-weight: 700; color: #3d5226; opacity: 0;
        transition: opacity .28s cubic-bezier(.22,1,.36,1); }
    .is-min .q-hint { opacity: .8; }
    .q-c { flex: none; width: 1rem; height: 1rem; color: #3d5226; opacity: .6;
        transition: transform .28s cubic-bezier(.22,1,.36,1); }
    .is-min .q-c { transform: rotate(-90deg); }
    .q-body { overflow: hidden; opacity: 1;
        transition: max-height .28s cubic-bezier(.22,1,.36,1), opacity .28s cubic-bezier(.22,1,.36,1); }
    .is-min .q-body { max-height: 0; opacity: 0; }
    .q-body-in { overflow: hidden; min-height: 0; display: grid; gap: .5rem; padding: 0 .9rem; }
    .q-body-in::after { content: ''; height: .4rem; }
    .q-card { border-radius: .7rem; padding: .6rem .75rem; font-size: .82rem; color: #3d5226;
        line-height: 1.5; background: rgb(255 255 255 / .6); border: 1px solid rgb(207 227 184 / .8); }

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
    .wtp-choices.is-two { grid-template-columns: 1fr 1fr; }
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
        font-size: .8rem; font-weight: 600; color: var(--color-gray-700);
        transition: border-color .28s cubic-bezier(.22,1,.36,1), background .28s cubic-bezier(.22,1,.36,1); }
    .wtp-prob input { accent-color: #4a7c2a; width: 1rem; height: 1rem; flex: none; }
    .wtp-prob.is-on { border-color: var(--color-brand-500); background: var(--color-brand-50); }

    .wtp-nav { display: flex; gap: .6rem; margin-top: 1.1rem; }
    .wtp-run { display: flex; align-items: center; justify-content: center; gap: .5rem; width: 100%;
        padding: .85rem 1rem; border-radius: 1rem; color: #fff; font-weight: 800; font-size: .95rem;
        background: linear-gradient(115deg, #7bb24a, #4a7c2a 30%, #3d6823 55%, #6b9f3d 80%, #8fc96a);
        background-size: 260% 100%; animation: wtpTide 5.5s ease-in-out infinite alternate;
        box-shadow: 0 10px 22px -12px rgb(61 104 35 / .65); }
    .wtp-run:disabled { opacity: .6; }
    @keyframes wtpTide { from { background-position: 0% 50%; } to { background-position: 100% 50%; } }

    .wtp-wait { position: fixed; inset: 0; z-index: 110; display: flex; flex-direction: column;
        align-items: center; justify-content: center; gap: .6rem; padding: 2rem 1.2rem; text-align: center;
        background: rgb(250 250 248 / .98); backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px);
        opacity: 0; visibility: hidden; pointer-events: none;
        transition: opacity .28s cubic-bezier(.22,1,.36,1), visibility .28s; }
    .wtp-wait.is-on { opacity: 1; visibility: visible; pointer-events: auto; }
    .wtp-wait .w-spin { width: 2.4rem; height: 2.4rem; border-radius: 999px; border: 3px solid var(--color-brand-200);
        border-top-color: var(--color-brand-600); animation: wtpSpin .8s linear infinite; }
    @keyframes wtpSpin { to { transform: rotate(360deg); } }
    .wtp-wait p { font-size: .85rem; color: var(--color-gray-500); max-width: 22rem; }
    .wtp-wait .w-stay { font-size: .8rem; font-weight: 700; color: #b45309; }
    html.dark .wtp-wait { background: rgb(13 17 9 / .98); }
    html.dark .wtp-wait p b { color: #e8efe1; }
    html.dark .wtp-wait .w-stay { color: #fbbf24; }

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

    /* The ranked shelf: one row per crop, a score bar that grows. */
    .wp-rec { padding: .7rem 0; border-bottom: 1px solid var(--color-gray-100);
        opacity: 0; transform: translateY(6px); transition: all .45s cubic-bezier(.22,1,.36,1); }
    .wp-rec:last-child { border-bottom: 0; padding-bottom: .2rem; }
    .wtp-report.is-drawn .wp-rec { opacity: 1; transform: none; }
    .wp-rec-top { display: flex; align-items: baseline; gap: .5rem; }
    .wp-rank { flex: none; width: 1.5rem; height: 1.5rem; border-radius: 999px; display: inline-flex;
        align-items: center; justify-content: center; font-size: .74rem; font-weight: 800;
        background: var(--color-brand-100); color: var(--color-brand-800); align-self: center; }
    .wp-rec-top b { font-size: .92rem; color: var(--color-gray-900); }
    .wp-cat { font-size: .64rem; font-weight: 800; letter-spacing: .04em; text-transform: uppercase;
        padding: .12rem .45rem; border-radius: 999px; background: var(--color-gray-100); color: var(--color-gray-500); }
    .wp-win { margin-left: auto; font-size: .72rem; font-weight: 700; color: var(--color-brand-700); white-space: nowrap; }
    .wp-track { height: 7px; border-radius: 999px; background: var(--color-gray-100); margin: .4rem 0 .35rem; overflow: hidden; }
    .wp-fill { display: block; height: 100%; border-radius: 999px; background: var(--color-brand-500);
        transform-origin: left; transform: scaleX(0); transition: transform .7s cubic-bezier(.22,1,.36,1); }
    .wtp-report.is-drawn .wp-fill { transform: scaleX(1); }
    .wp-why { font-size: .8rem; color: var(--color-gray-600); line-height: 1.55; }
    .wp-watch { font-size: .74rem; color: #92610e; margin-top: .15rem; }
    html.dark .wp-rec { border-color: #222b1a; }
    html.dark .wp-rec-top b { color: #e8efe1; }
    html.dark .wp-cat { background: #222b1a; color: #93a684; }
    html.dark .wp-track { background: #222b1a; }
    html.dark .wp-why { color: #b7c2ad; }
    html.dark .wp-watch { color: #e0b95c; }

    .wtp-win-row { display: block; padding: .6rem .7rem; border-radius: .7rem; margin-bottom: .45rem;
        font-size: .84rem; line-height: 1.55; border: 1px solid; }
    .wtp-win-row b { margin-right: .3rem; }
    .wtp-win-row.is-no { background: #fef2f2; border-color: #fecaca; color: #7f1d1d; }
    html.dark .wtp-win-row.is-no { background: #2a1414; border-color: #4c1d1d; color: #fca5a5; }

    .wtp-gap { font-size: .78rem; color: var(--color-gray-500); line-height: 1.55; }
    .wtp-gap li { list-style: disc; margin-left: 1.1rem; }
    .wtp-fine { font-size: .72rem; color: var(--color-gray-400); line-height: 1.55; }
    .wtp-plain { font-size: .875rem; line-height: 1.65; color: var(--color-gray-700); }
    html.dark .wtp-plain { color: #d5e3c5; }

    .wtp-acts { display: grid; gap: .5rem; }
    @media (min-width: 640px) { .wtp-acts { grid-template-columns: 1fr 1fr; } }

    .wtp-saved { display: flex; align-items: center; gap: .7rem; width: 100%; text-align: left;
        padding: .8rem .9rem; border-bottom: 1px solid var(--color-gray-100); cursor: pointer; }
    .wtp-saved:hover { background: var(--color-gray-50); }
    .wtp-saved b { display: block; font-size: .88rem; color: var(--color-gray-900); }
    .wtp-saved small { color: var(--color-gray-400); font-size: .72rem; }

    .wtp-anee-face { width: 1.15rem; height: 1.15rem; border-radius: 999px; object-fit: cover; }

    html.dark .wtp-tab { background: #151b12; border-color: #2b3a1c; color: #93a684; }
    html.dark .wtp-tab.is-on { background: #4a7c2a; border-color: #4a7c2a; color: #fff; }
    html.dark .wtp-quote { background: linear-gradient(115deg, #1c2913, #22301a); border-color: #2b3a1c; }
    html.dark .wtp-quote b { color: #cfe6b8; }
    html.dark .q-title { color: #cfe6b8; }
    html.dark .q-hint, html.dark .q-c { color: #a8bd93; }
    html.dark .q-card { background: rgb(255 255 255 / .05); border-color: #2b3a1c; color: #a8bd93; }
    html.dark .wtp-q { color: #e8efe1; }
    html.dark .wtp-choice, html.dark .wtp-prob { background: #151b12; border-color: #2b3a1c; color: #d5e3c5; }
    html.dark .wtp-choice.is-on { background: #22301a; border-color: #6b9f3d; color: #cfe6b8; }
    html.dark .wtp-prob.is-on { background: #22301a; border-color: #6b9f3d; }
    html.dark .wtp-card { background: #151b12; border-color: #2b3a1c; }
    html.dark .wtp-card h3 { color: #e8efe1; }
    html.dark .wtp-saved { border-color: #222b1a; }
    html.dark .wtp-saved:hover { background: #161e10; }
    html.dark .wtp-saved b { color: #e8efe1; }

    @media (max-width: 639px) {
        .wtp-hero { padding: .9rem 1rem; }
        .wtp-hero .h-win { font-size: 1.15rem; }
        .wtp-card { padding: .8rem .85rem; }
        .wtp-choices.is-two { grid-template-columns: 1fr; }
        .wp-win { display: none; }
    }

    @media (prefers-reduced-motion: reduce) {
        .wtp-step.is-on { animation: none; }
        .wtp-run { animation: none; }
        .wp-rec, .wp-fill, .wtp-dot { transition: none; transform: none; opacity: 1; }
        .wtp-wait, .q-body, .q-c, .q-hint, .wtp-prob { transition: none; }
    }
</style>

<div class="max-w-2xl mx-auto">
    <div class="wtp-tabs" role="tablist">
        <button type="button" class="wtp-tab is-on" id="wpTabGen">Generate</button>
        <button type="button" class="wtp-tab" id="wpTabSaved">Saved</button>
    </div>

    <div id="wpGen">
        <div class="wtp-quote" id="wpQuote" hidden>
            <button type="button" class="q-head" id="wpQuoteHead" aria-expanded="true">
                <span class="q-ico">🔎</span>
                <span class="q-title">Before you run one</span>
                <span class="q-hint" id="wpQuoteHint"></span>
                <svg class="q-c" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/></svg>
            </button>
            <div class="q-body">
                <div class="q-body-in">
                    <div class="q-card" id="wpQuoteCost"></div>
                    <div class="q-card">Anee weighs your soil, water, timing and the region's climate against the crops a Philippine farm actually chooses between — grains, vegetables, root crops, legumes and fruit trees — and ranks what fits YOUR ground.</div>
                </div>
            </div>
        </div>

        <div class="card p-5 wtp-wiz" id="wpWiz">
            {{-- Step 1: the place --}}
            <section class="wtp-step is-on" data-step="0">
                <p class="wtp-q">Where is the field?</p>
                <p class="wtp-sub">Town and province is enough — the climate and the markets differ by region.</p>
                <input type="text" id="wpLocation" class="form-input" maxlength="160" placeholder="e.g. Urdaneta, Pangasinan">
            </section>
            {{-- Step 2: when they want to begin --}}
            <section class="wtp-step" data-step="1">
                <p class="wtp-q">When do you plan to start?</p>
                <p class="wtp-sub">The month you would prepare and plant — the ranking bends around it.</p>
                <div class="wtp-choices is-two" id="wpMonths"></div>
            </section>
            {{-- Step 3: the soil --}}
            <section class="wtp-step" data-step="2">
                <p class="wtp-q">What is the soil like?</p>
                <p class="wtp-sub">As your hands know it — no test needed.</p>
                <div class="wtp-choices" id="wpSoils"></div>
            </section>
            {{-- Step 4: the water --}}
            <section class="wtp-step" data-step="3">
                <p class="wtp-q">What water does the field get?</p>
                <p class="wtp-sub">Water decides more than anything else here.</p>
                <div class="wtp-choices" id="wpWaters"></div>
            </section>
            {{-- Step 5: the troubles --}}
            <section class="wtp-step" data-step="4">
                <p class="wtp-q">What does this ground struggle with?</p>
                <p class="wtp-sub">Tick what you have seen — each one moves the ranking.</p>
                <div class="wtp-probs" id="wpProbs"></div>
            </section>
            {{-- Step 6: the aim and the area --}}
            <section class="wtp-step" data-step="5">
                <p class="wtp-q">What is the harvest for?</p>
                <p class="wtp-sub">A market crop and a family table pull toward different answers.</p>
                <div class="wtp-choices" id="wpAims"></div>
                <label class="form-label mt-4" for="wpArea">How big is the ground? <span class="text-gray-400 font-normal">(optional)</span></label>
                <input type="text" id="wpArea" class="form-input" maxlength="60" placeholder="e.g. half a hectare, 800 sqm">
                <label class="form-label mt-3" for="wpNotes">Anything else worth knowing? <span class="text-gray-400 font-normal">(optional)</span></label>
                <textarea id="wpNotes" class="form-textarea" rows="2" maxlength="400" placeholder="e.g. thinking of ube; the neighbour grows onions well"></textarea>
            </section>
            {{-- Step 7: the decision --}}
            <section class="wtp-step" data-step="6">
                <p class="wtp-q">Ready to run it?</p>
                <p class="wtp-sub" id="wpReview"></p>
                <button type="button" class="wtp-run" id="wpRun">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21c0-4 1-7 4-9M12 21c0-5-2-8-6-9m6 9V8m0 0c0-2.5 1.5-4 4-4 0 2.5-1.5 4-4 4zm0 0C12 5.5 10.5 4 6.5 4c0 2.5 1.5 4 5.5 4z"/></svg>
                    <span id="wpRunSays">Run the analysis</span>
                </button>
                <p class="text-xs text-gray-400 mt-2 text-center" id="wpRunFine"></p>
            </section>

            <div class="wtp-wait" id="wpWait">
                <span class="w-spin"></span>
                <p><b>Reading your ground…</b><br>Soil, water, the region's climate, and every crop family a Philippine farm weighs. Half a minute, usually.</p>
                <p class="w-stay">Please stay on this screen — leaving it loses this run.</p>
            </div>

            <div class="wtp-dots" id="wpDots"></div>
            <div class="wtp-nav" id="wpNav">
                <button type="button" class="btn btn-white flex-1" id="wpBack" disabled>Back</button>
                <button type="button" class="btn btn-primary flex-1" id="wpNext">Next</button>
            </div>
        </div>

        <div class="wtp-report mt-4" id="wpReport" hidden></div>
    </div>

    <div id="wpSavedPane" class="hidden">
        <div class="card !p-0 overflow-hidden">
            <div id="wpSavedList"></div>
            <div id="wpSavedEmpty" class="hidden text-center py-10">
                <p class="font-bold text-gray-900">Nothing saved yet</p>
                <p class="text-sm text-gray-400">Every finished analysis lands here by itself.</p>
            </div>
        </div>
        <div class="wtp-report mt-4" id="wpSavedReport" hidden></div>
    </div>
</div>

<script>
(() => {
    const $id = (x) => document.getElementById(x);
    const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const U = {
        options: '{{ route('whatp.options') }}',
        generate: '{{ route('whatp.generate') }}',
        list: '{{ route('whatp.list') }}',
        one: (id) => '{{ url('/app/what-to-plant/one') }}/' + id,
        job: (id) => '{{ url('/app/what-to-plant/job') }}/' + id,
        del: (id) => '{{ url('/app/what-to-plant') }}/' + id,
        anee: '{{ route('ai.index') }}',
    };
    const WP_META_URL = '{{ route('whatp.meta') }}';
    const CAT_E = { 'Grain': '🌾', 'Vegetable': '🥬', 'Root crop': '🍠', 'Legume': '🫘', 'Fruit / tree': '🌳' };

    let OPT = null;
    const state = { location: '', startMonth: null, soil: null, water: null, aim: null, area: '', notes: '', problems: [] };
    let step = 0;
    const STEPS = 7;

    async function boot() {
        try {
            const res = await api(U.options, { method: 'GET' });
            OPT = res.data;
            paintOptions();
        } catch (err) { toast(err.message, 'error'); }
    }

    function paintOptions() {
        $id('wpMonths').innerHTML = OPT.months.map((m, i) => `
            <button type="button" class="wtp-choice" data-month="${esc(m.key)}"><span class="c-e">🗓️</span><span>${esc(m.label)}${i === 0 ? '<small>This month</small>' : ''}</span></button>`).join('');
        const soilIcons = { clay: '🧱', loam: '🟤', sandy: '🏖️', silty: '🌊', rocky: '⛰️', unsure: '🤷' };
        $id('wpSoils').innerHTML = Object.entries(OPT.soils).map(([k, label]) => `
            <button type="button" class="wtp-choice" data-soil="${k}"><span class="c-e">${soilIcons[k] || '🟫'}</span><span>${esc(label)}</span></button>`).join('');
        const waterIcons = { irrigated: '🚰', limited: '🚿', rainfed: '🌧️' };
        $id('wpWaters').innerHTML = Object.entries(OPT.waters).map(([k, label]) => `
            <button type="button" class="wtp-choice" data-water="${k}"><span class="c-e">${waterIcons[k] || '💧'}</span><span>${esc(label)}</span></button>`).join('');
        const aimIcons = { sell: '🏪', family: '🍚', both: '⚖️' };
        $id('wpAims').innerHTML = Object.entries(OPT.aims).map(([k, label]) => `
            <button type="button" class="wtp-choice" data-aim="${k}"><span class="c-e">${aimIcons[k] || '🌱'}</span><span>${esc(label)}</span></button>`).join('');
        $id('wpProbs').innerHTML = Object.entries(OPT.problems).map(([k, label]) => `
            <label class="wtp-prob" data-prob="${k}"><input type="checkbox" value="${k}"><span>${esc(label)}</span></label>`).join('');
        $id('wpDots').innerHTML = Array.from({ length: STEPS }, (_, i) => `<span class="wtp-dot${i === 0 ? ' is-on' : ''}"></span>`).join('');
        paintQuote();
    }

    const QUOTE_MIN_KEY = 'anee-whatp-quote-min';
    let quoteMin = false;
    try { quoteMin = localStorage.getItem(QUOTE_MIN_KEY) === '1'; } catch (_) { /* opens full */ }

    function paintQuote() {
        const q = $id('wpQuote');
        if (!OPT) return;
        if (!OPT.canUse) {
            $id('wpQuoteCost').innerHTML = esc(OPT.whyNot || 'The analysis is not available right now.');
            $id('wpQuoteHint').textContent = '';
            q.classList.remove('is-min');
            q.hidden = false;
            return;
        }
        if (!OPT.quote) { q.hidden = true; return; }
        q.classList.toggle('is-min', quoteMin);
        $id('wpQuoteHead').setAttribute('aria-expanded', quoteMin ? 'false' : 'true');
        $id('wpQuoteCost').innerHTML = `This deep read spends <b>${OPT.quote} credits</b>, and you have <b>${OPT.unlimited ? '∞' : Number(OPT.balance).toLocaleString()}</b>. Nothing is charged until you press Run.`;
        $id('wpQuoteHint').textContent = `${OPT.quote} credits`;
        q.hidden = false;
    }
    $id('wpQuoteHead').addEventListener('click', () => {
        quoteMin = !quoteMin;
        try { localStorage.setItem(QUOTE_MIN_KEY, quoteMin ? '1' : '0'); } catch (_) { /* not remembered */ }
        paintQuote();
    });

    /* ---------------- the walk ---------------- */
    function show(n, backwards) {
        step = Math.max(0, Math.min(STEPS - 1, n));
        document.querySelectorAll('.wtp-step').forEach((s) => {
            const on = Number(s.getAttribute('data-step')) === step;
            s.classList.toggle('is-on', on);
            s.classList.toggle('is-back', on && !!backwards);
        });
        document.querySelectorAll('.wtp-dot').forEach((d, i) => d.classList.toggle('is-on', i <= step));
        $id('wpBack').disabled = step === 0;
        $id('wpNext').style.display = step === STEPS - 1 ? 'none' : '';
        if (step === STEPS - 1) review();
    }

    function stepReady() {
        switch (step) {
            case 0: state.location = $id('wpLocation').value.trim();
                return !!state.location || (toast('Say where the field is.', 'error'), false);
            case 1: return !!state.startMonth || (toast('Pick the month you would start.', 'error'), false);
            case 2: return !!state.soil || (toast('Pick the soil that sounds most like yours.', 'error'), false);
            case 3: return !!state.water || (toast('Say what water the field gets.', 'error'), false);
            case 4: state.problems = [...document.querySelectorAll('#wpProbs input:checked')].map((i) => i.value); return true;
            case 5: state.area = $id('wpArea').value.trim(); state.notes = $id('wpNotes').value.trim();
                return !!state.aim || (toast('Say what the harvest is for.', 'error'), false);
            default: return true;
        }
    }

    function review() {
        const month = (OPT.months.find((m) => m.key === state.startMonth) || {}).label || '';
        $id('wpReview').innerHTML = `📍 <b>${esc(state.location)}</b> · starting ${esc(month)}`
            + `<br><span class="text-xs">${esc(OPT.soils[state.soil] || '')} · ${esc(OPT.waters[state.water] || '')} · ${esc(OPT.aims[state.aim] || '')}`
            + (state.problems.length ? ` · ${state.problems.length} trouble${state.problems.length === 1 ? '' : 's'} considered` : '') + '</span>';
        $id('wpRunSays').textContent = OPT.canUse && OPT.quote ? `Run the analysis (${OPT.quote} credits)` : 'Run the analysis';
        $id('wpRunFine').textContent = OPT.canUse
            ? 'Charged to the same AI credits your questions use — it shows in your subscription’s credit log.'
            : (OPT.whyNot || '');
        $id('wpRun').disabled = !OPT.canUse;
    }

    $id('wpNext').addEventListener('click', () => { if (stepReady()) show(step + 1); });
    $id('wpBack').addEventListener('click', () => show(step - 1, true));
    const pickWire = (hostId, attr, key, next) => {
        $id(hostId).addEventListener('click', (e) => {
            const b = e.target.closest(`[data-${attr}]`);
            if (!b) return;
            state[key] = b.getAttribute(`data-${attr}`);
            document.querySelectorAll(`#${hostId} .wtp-choice`).forEach((c) => c.classList.toggle('is-on', c === b));
            if (next !== null) setTimeout(() => show(next), 180);
        });
    };
    pickWire('wpMonths', 'month', 'startMonth', 2);
    pickWire('wpSoils', 'soil', 'soil', 3);
    pickWire('wpWaters', 'water', 'water', 4);
    pickWire('wpAims', 'aim', 'aim', null);
    $id('wpProbs').addEventListener('change', (e) => {
        const l = e.target.closest('.wtp-prob');
        if (l) l.classList.toggle('is-on', e.target.checked);
    });

    /* ---------------- the run ---------------- */
    $id('wpRun').addEventListener('click', async () => {
        if (!stepReady()) return;
        const wiz = $id('wpWiz');
        wiz.querySelectorAll('.wtp-step, .wtp-nav, .wtp-dots').forEach((el) => el.style.display = 'none');
        $id('wpWait').classList.add('is-on');
        $id('wpReport').hidden = true;
        let landed = false;
        try {
            const res = await api(U.generate, { method: 'POST', body: {
                location: state.location, startMonth: state.startMonth, soil: state.soil,
                water: state.water, aim: state.aim, area: state.area, notes: state.notes,
                problems: state.problems,
            } });
            let data = res.data;
            if (data.pending) {
                for (let i = 0; i < 100 && (!data || data.status !== 'ready'); i++) {
                    await new Promise((r) => setTimeout(r, 3000));
                    const st = await api(U.job(data.id || res.data.id), { method: 'GET' });
                    if (st.data && st.data.status === 'ready') { data = st.data; break; }
                }
                if (!data || data.status !== 'ready') {
                    throw new Error('Still working — give it a minute, then look on the Saved tab.');
                }
            }
            OPT.balance = data.balance;
            landed = true;
            drawReport($id('wpReport'), { report: data.report, params: data.params, charged: data.charged, savedId: data.savedId }, 'fresh');
            toast(`Done — ${data.charged} credits used. Saved to the shelf.`);
        } catch (err) {
            toast(err.message, 'error');
        } finally {
            $id('wpWait').classList.remove('is-on');
            wiz.querySelectorAll('.wtp-step, .wtp-nav, .wtp-dots').forEach((el) => el.style.display = '');
            show(step);
            if (landed) { wiz.hidden = true; $id('wpQuote').hidden = true; }
        }
    });

    function wizardBack() {
        $id('wpWiz').hidden = false;
        if (OPT && OPT.canUse && OPT.quote) $id('wpQuote').hidden = false;
        $id('wpReport').hidden = true;
        $id('wpReport').classList.remove('is-drawn');
        show(0);
        $id('wpWiz').scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    /* ---------------- the report, drawn ---------------- */
    function drawReport(host, item, mode) {
        const r = item.report || {};
        const p = item.params || {};
        const sweep = (t) => String(t || '').replace(/:[a-z0-9_-]+:/gi, '').replace(/\s{2,}/g, ' ').trim();
        const top = r.topPick || {};
        const month = (OPT ? (OPT.months.find((m) => m.key === p.startMonth) || {}).label : '') || p.startMonth || '';

        host.innerHTML = `
            <div class="wtp-hero">
                <h2>${esc(CAT_E[top.category] || '🌱')} Best for your ground</h2>
                <p class="h-win">${esc(top.crop || '')}</p>
                <p class="h-why">${esc(sweep(top.why))}${top.window ? ' Plant it ' + esc(top.window) + '.' : ''}</p>
                <div class="wtp-chips">
                    <span class="wtp-chip">📍 ${esc(p.location || '')}</span>
                    <span class="wtp-chip">🗓️ ${esc(month)}</span>
                    <span class="wtp-chip">Confidence: ${esc(r.confidence || 'moderate')}</span>
                    ${item.charged ? `<span class="wtp-chip">${item.charged} credits</span>` : ''}
                </div>
            </div>

            <div class="wtp-card">
                <h3>The ranking, best first</h3>
                ${(r.recommendations || []).map((x, i) => `
                    <div class="wp-rec" style="transition-delay:${i * 70}ms">
                        <div class="wp-rec-top">
                            <span class="wp-rank">${esc(String(x.rank || i + 1))}</span>
                            <b>${esc(CAT_E[x.category] || '🌱')} ${esc(x.crop || '')}</b>
                            <span class="wp-cat">${esc(x.category || '')}</span>
                            ${x.window ? `<span class="wp-win">${esc(x.window)}</span>` : ''}
                        </div>
                        <div class="wp-track"><span class="wp-fill" style="width:${Math.max(4, Math.min(100, Number(x.score) || 0))}%;transition-delay:${120 + i * 70}ms"></span></div>
                        <p class="wp-why">${esc(sweep(x.why))}</p>
                        ${x.watch ? `<p class="wp-watch">⚠️ ${esc(x.watch)}</p>` : ''}
                    </div>`).join('')}
            </div>

            ${(r.avoid || []).length ? `
            <div class="wtp-card">
                <h3>Better avoided on this ground</h3>
                ${(r.avoid || []).map((a) => `
                    <div class="wtp-win-row is-no"><b>⛔ ${esc(a.crop || '')}:</b><span>${esc(a.why || '')}</span></div>`).join('')}
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
                <p class="wtp-fine">No analysis can see your soil the way a soil test can, or a season the way it actually turns out. What this gives you is a data-grounded shortlist — the crops whose real needs match what you described — which beats planting on habit alone. Its sister tool, When to Plant, sharpens the timing once you have chosen.</p>
            </div>

            <div class="wtp-acts">
                <button type="button" class="btn btn-primary w-full" id="${mode === 'fresh' ? 'wpAttach' : 'wpAttachSaved'}">
                    ${OPT && OPT.aneeFace ? `<img class="wtp-anee-face" src="${esc(OPT.aneeFace)}" alt="">` : '🤖'} Attach to Anee
                </button>
                ${mode === 'fresh' ? `<button type="button" class="btn btn-white w-full" id="wpAgain">🌱 Run another analysis</button>` : ''}
                <button type="button" class="btn btn-white w-full" id="wpDelete">🗑 Delete</button>
            </div>`;

        host.hidden = false;
        requestAnimationFrame(() => requestAnimationFrame(() => host.classList.add('is-drawn')));
        host.scrollIntoView({ behavior: 'smooth', block: 'start' });

        host.querySelector(mode === 'fresh' ? '#wpAttach' : '#wpAttachSaved').addEventListener('click', () => {
            if (item.savedId) window.location.href = U.anee + '?analysis=' + item.savedId;
        });
        if (mode === 'fresh') host.querySelector('#wpAgain').addEventListener('click', wizardBack);
        host.querySelector('#wpDelete').addEventListener('click', async () => {
            const delId = item.savedId;
            const ok = window.confirmAction
                ? await confirmAction({ title: 'Delete this analysis?', message: 'The credits it cost are already spent; only the report goes.', confirmText: 'Delete', danger: true })
                : confirm('Delete this analysis?');
            if (!ok) return;
            try {
                const res = await api(U.del(delId), { method: 'DELETE' });
                toast(res.message);
                host.hidden = true;
                loadSaved().catch(() => {});
                if (mode === 'fresh') wizardBack();
            } catch (err) { toast(err.message, 'error'); }
        });
    }

    /* ---------------- saved ---------------- */
    async function loadSaved() {
        const res = await api(U.list + '?_=' + Date.now(), { method: 'GET' });
        const rows = res.data.rows || [];
        WP_ROWS = rows;
        $id('wpSavedList').innerHTML = rows.map((r) => `
            <button type="button" class="wtp-saved" data-saved="${r.id}">
                <span class="grow min-w-0"><b>${esc(r.title)}</b><small>${r.description ? esc(r.description) + ' · ' : ''}${esc(r.at)} · ${r.credits} credits</small></span>
                <span role="button" tabindex="0" class="wtp-pen" data-meta="${r.id}" title="Edit name and description" aria-label="Edit ${esc(r.title)}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:.85rem;height:.85rem"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                </span>
                <svg class="w-4 h-4 text-gray-300 shrink-0" fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
            </button>`).join('');
        $id('wpSavedEmpty').classList.toggle('hidden', rows.length > 0);
    }

    let WP_ROWS = [];
    let WP_META_ID = null;
    document.addEventListener('click', async (e) => {
        const saveBtn = e.target.closest('#wpMetaSave');
        if (saveBtn && WP_META_ID !== null) {
            saveBtn.disabled = true;
            try {
                const res = await api(WP_META_URL, { method: 'POST', body: {
                    id: WP_META_ID,
                    title: $id('wpMetaTitle').value.trim(),
                    description: $id('wpMetaDesc').value.trim(),
                } });
                toast(res.message);
                closeSheet('wpMetaSheet');
                loadSaved().catch(() => {});
            } catch (err) { toast(err.message, 'error'); }
            finally { saveBtn.disabled = false; }
            return;
        }
        const pen = e.target.closest('[data-meta]');
        if (pen && pen.closest('#wpSavedList')) {
            e.stopPropagation();
            const r = WP_ROWS.find((x) => String(x.id) === pen.getAttribute('data-meta'));
            if (!r) return;
            WP_META_ID = r.id;
            $id('wpMetaTitle').value = r.title || '';
            $id('wpMetaDesc').value = r.description || '';
            openSheet('wpMetaSheet');
            return;
        }
        const b = e.target.closest('[data-saved]');
        if (!b) return;
        try {
            const res = await api(U.one(b.getAttribute('data-saved')), { method: 'GET' });
            const host = $id('wpSavedReport');
            host.classList.remove('is-drawn');
            drawReport(host, {
                report: res.data.report, params: res.data.params,
                charged: res.data.credits, savedId: res.data.id,
            }, 'saved');
        } catch (err) { toast(err.message, 'error'); }
    });

    /* ---------------- tabs ---------------- */
    const tab = (which) => {
        $id('wpGen').classList.toggle('hidden', which !== 'gen');
        $id('wpSavedPane').classList.toggle('hidden', which !== 'saved');
        $id('wpTabGen').classList.toggle('is-on', which === 'gen');
        $id('wpTabSaved').classList.toggle('is-on', which === 'saved');
        if (which === 'saved') loadSaved().catch((err) => toast(err.message, 'error'));
    };
    $id('wpTabGen').addEventListener('click', () => tab('gen'));
    $id('wpTabSaved').addEventListener('click', () => tab('saved'));

    if (window.api) boot();
    else window.addEventListener('load', boot, { once: true });
})();
</script>
@endsection

@push('sheets')
{{-- Rename a saved analysis and describe it in your own words. --}}
<div class="sheet hidden" id="wpMetaSheet" style="--sheet-width:26rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title">Edit this analysis</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body space-y-4">
        <div>
            <label class="form-label" for="wpMetaTitle">Name</label>
            <input type="text" id="wpMetaTitle" class="form-input" maxlength="191">
        </div>
        <div>
            <label class="form-label" for="wpMetaDesc">Description <span class="text-gray-400 font-normal">(optional)</span></label>
            <textarea id="wpMetaDesc" class="form-textarea" rows="3" maxlength="2000"></textarea>
        </div>
    </div>
    <div class="sheet-footer">
        <button type="button" class="btn btn-ghost" data-sheet-close>Cancel</button>
        <button type="button" class="btn btn-primary" id="wpMetaSave">Save changes</button>
    </div>
</div>
@endpush

@push('head')
<style>
    .wtp-pen { flex: none; width: 1.6rem; height: 1.6rem; border-radius: .45rem; display: inline-flex;
        align-items: center; justify-content: center; color: var(--color-gray-400); }
    .wtp-pen:hover { color: var(--color-brand-700); background: var(--color-brand-50); }
    html.dark .wtp-pen:hover { background: rgb(107 159 61 / .18); color: #a5c97e; }
</style>
@endpush
