@extends('layouts.app')

@php
    $isSofar = ($kind ?? 'season') === 'sofar';
    $price = $isSofar ? \App\Http\Controllers\Manager\FarmReportController::PRICE_SOFAR
        : \App\Http\Controllers\Manager\FarmReportController::PRICE_SEASON;
    $aneeName = \App\Models\AiSetting::current()->assistantName;
    $pageName = $isSofar ? 'Analyze So Far' : 'Anee Season Report';
@endphp

@section('title', $pageName . ' — ' . $schedule->title)
@section('page-title', $pageName)
@section('page-subtitle', $schedule->title)
@section('back', route('sm.reports', ['id' => $schedule->id]))

@push('head')
@include('partials.tag-sheet-css')
<style>
    /* ===== Anee's own reports ========================================
       The when-to-plant idiom: two tabs, a folding price note, a full
       page veil while she works, then a report drawn in cards. */
    .ar-wrap { max-width: 44rem; margin: 0 auto; }
    .ar-tabs { display: flex; gap: .4rem; margin-bottom: 1rem; }
    .ar-tab { flex: 1 1 0; padding: .6rem; border-radius: .8rem; font-weight: 800; font-size: .9rem;
        text-align: center; color: var(--color-gray-500); background: var(--color-white);
        border: 1px solid var(--color-gray-200); cursor: pointer; }
    .ar-tab.is-on { background: var(--color-brand-600); border-color: var(--color-brand-600); color: #fff; }
    html.dark .ar-tab { background: #151b12; border-color: #2b3a1c; color: #93a684; }
    html.dark .ar-tab.is-on { background: #4a7c2a; border-color: #4a7c2a; color: #fff; }

    /* The price note — the wtp fold, same clothes. */
    .ar-quote { border-radius: .9rem; margin-bottom: 1rem; overflow: hidden;
        background: linear-gradient(115deg, #f3f8ec, #e4efd4); border: 1px solid #cfe3b8; }
    .ar-quote b { color: #2d5016; }
    .arq-head { display: flex; align-items: center; gap: .6rem; width: 100%; text-align: left; padding: .7rem .9rem; cursor: pointer; }
    .arq-head .e { font-size: 1.15rem; flex: none; }
    .arq-title { flex: 1 1 auto; min-width: 0; font-size: .84rem; font-weight: 800; color: #2d5016; }
    .arq-body { display: grid; gap: .5rem; padding: 0 .9rem .6rem; }
    .arq-card { border-radius: .7rem; padding: .6rem .75rem; font-size: .82rem; color: #3d5226;
        line-height: 1.5; background: rgb(255 255 255 / .6); border: 1px solid rgb(207 227 184 / .8); }
    html.dark .ar-quote { background: linear-gradient(115deg, #1c2913, #22301a); border-color: #2b3a1c; }
    html.dark .ar-quote b { color: #cfe6b8; }
    html.dark .arq-title { color: #cfe6b8; }
    html.dark .arq-card { background: rgb(255 255 255 / .05); border-color: #2b3a1c; color: #a8bd93; }

    /* Readiness checklist */
    .ar-check { border-radius: .8rem; padding: .65rem .8rem; font-size: .8rem; line-height: 1.5; }
    .ar-check + .ar-check { margin-top: .45rem; }
    .ar-check.is-block { border: 1px solid #f0caca; background: #fdf1f1; color: #8a2626; }
    .ar-check.is-warn { border: 1px solid #f3e3b7; background: #fdf8ec; color: #92610e; }
    html.dark .ar-check.is-block { background: #271414; border-color: #4c2222; color: #e79c9c; }
    html.dark .ar-check.is-warn { background: #241f10; border-color: #43391b; color: #e0b95c; }

    .ar-run { display: flex; align-items: center; justify-content: center; gap: .5rem; width: 100%;
        padding: .85rem 1rem; border-radius: 1rem; color: #fff; font-weight: 800; font-size: .95rem;
        background: linear-gradient(115deg, #7bb24a, #4a7c2a 30%, #3d6823 55%, #6b9f3d 80%, #8fc96a);
        background-size: 260% 100%; animation: arTide 5.5s ease-in-out infinite alternate;
        box-shadow: 0 10px 22px -12px rgb(61 104 35 / .65); }
    .ar-run:disabled { opacity: .55; animation: none; }
    @keyframes arTide { from { background-position: 0% 50%; } to { background-position: 100% 50%; } }

    /* The veil, while she works — near opaque, whole page, stay put. */
    .ar-wait { position: fixed; inset: 0; z-index: 110; display: flex; flex-direction: column;
        align-items: center; justify-content: center; gap: .6rem; padding: 2rem 1.2rem; text-align: center;
        background: rgb(250 250 248 / .98); backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px);
        opacity: 0; visibility: hidden; pointer-events: none;
        transition: opacity .28s cubic-bezier(.22,1,.36,1), visibility 0s linear .28s; }
    .ar-wait.is-on { opacity: 1; visibility: visible; pointer-events: auto; transition-delay: 0s; }
    .ar-wait .face { width: 3.2rem; height: 3.2rem; border-radius: 999px; object-fit: cover;
        animation: arBreathe 2.2s ease-in-out infinite; }
    @keyframes arBreathe { 0%,100% { transform: scale(1); } 50% { transform: scale(1.08); } }
    .ar-wait .spin { width: 2.2rem; height: 2.2rem; border-radius: 999px; border: 3px solid var(--color-brand-200);
        border-top-color: var(--color-brand-600); animation: arSpin .8s linear infinite; }
    @keyframes arSpin { to { transform: rotate(360deg); } }
    .ar-wait p { font-size: .85rem; color: var(--color-gray-500); max-width: 24rem; }
    .ar-wait .line { font-weight: 700; color: var(--color-gray-800); min-height: 1.4em; transition: opacity .28s ease; }
    .ar-wait .stay { font-size: .8rem; font-weight: 700; color: #b45309; }
    html.dark .ar-wait { background: rgb(13 17 9 / .98); }
    html.dark .ar-wait .line { color: #e8efe1; }
    html.dark .ar-wait .stay { color: #fbbf24; }
    @media (prefers-reduced-motion: reduce) { .ar-wait, .ar-run, .ar-wait .face { transition: none; animation: none; } }

    /* The report, drawn */
    .ar-report { display: grid; gap: .9rem; }
    .ar-hero { border-radius: 1.1rem; padding: 1.1rem 1.2rem; color: #fff;
        background: linear-gradient(130deg, #4a7c2a, #2d5016 70%); }
    .ar-hero.is-watch { background: linear-gradient(130deg, #b45309, #92400e 70%); }
    .ar-hero.is-rescue { background: linear-gradient(130deg, #b91c1c, #7f1d1d 70%); }
    .ar-hero h2 { font-size: 1.15rem; font-weight: 800; }
    .ar-hero .why { font-size: .85rem; opacity: .93; line-height: 1.55; margin-top: .45rem; }
    .ar-hero .chip { display: inline-block; margin-top: .55rem; font-size: .68rem; font-weight: 800;
        letter-spacing: .05em; text-transform: uppercase; padding: .2rem .6rem; border-radius: 999px;
        background: rgb(255 255 255 / .18); }
    .ar-card { border-radius: 1rem; border: 1px solid var(--color-gray-200); background: var(--color-white);
        padding: 1rem 1.1rem; }
    .ar-card h3 { font-weight: 800; font-size: .92rem; color: var(--color-gray-900); margin-bottom: .6rem; }
    .ar-li { display: flex; gap: .5rem; font-size: .84rem; color: var(--color-gray-700); line-height: 1.55; }
    .ar-li + .ar-li { margin-top: .45rem; }
    .ar-li .e { flex: none; }
    .ar-prose { font-size: .84rem; color: var(--color-gray-700); line-height: 1.6; }
    .ar-score { display: grid; grid-template-columns: 7.4rem 1fr auto; gap: .55rem; align-items: center; font-size: .78rem; color: var(--color-gray-600); }
    .ar-score + .ar-score { margin-top: .45rem; }
    .ar-score .track { display: block; height: 9px; border-radius: 999px; background: var(--color-gray-100); overflow: hidden; }
    .ar-score .fill { display: block; height: 100%; border-radius: 999px; background: var(--color-brand-600); width: 0;
        transition: width .7s cubic-bezier(.22,1,.36,1); }
    .ar-score b { font-variant-numeric: tabular-nums; color: var(--color-gray-900); }
    .ar-proto { border: 1px solid var(--color-gray-100); border-radius: .8rem; padding: .65rem .8rem; }
    .ar-proto + .ar-proto { margin-top: .5rem; }
    .ar-proto b { display: block; font-size: .85rem; color: var(--color-gray-900); }
    .ar-proto .swap { font-size: .78rem; color: var(--color-gray-600); margin-top: .3rem; line-height: 1.5; }
    .ar-proto .swap s { color: #b91c1c; text-decoration-thickness: 2px; }
    .ar-proto .swap em { font-style: normal; color: #15803d; font-weight: 700; }
    .ar-next { border: 1px solid var(--color-gray-100); border-radius: .8rem; padding: .6rem .75rem;
        display: flex; gap: .6rem; align-items: flex-start; }
    .ar-next + .ar-next { margin-top: .5rem; }
    .ar-next .n { flex: none; width: 1.6rem; height: 1.6rem; border-radius: 999px; background: var(--color-brand-50);
        color: var(--color-brand-800); display: inline-flex; align-items: center; justify-content: center;
        font-size: .78rem; font-weight: 800; }
    .ar-next .t { min-width: 0; }
    .ar-next .t b { display: block; font-size: .85rem; color: var(--color-gray-900); }
    .ar-next .t small { display: block; font-size: .74rem; color: var(--color-gray-500); margin-top: .15rem; line-height: 1.5; }
    .ar-heart { border-radius: 1rem; border: 1px solid #cfe3b8; padding: 1rem 1.1rem; font-size: .86rem;
        color: #3d5226; line-height: 1.6; background: linear-gradient(115deg, #f3f8ec, #e4efd4);
        display: flex; gap: .7rem; align-items: flex-start; }
    .ar-heart img { width: 2.2rem; height: 2.2rem; border-radius: 999px; object-fit: cover; flex: none; }
    .ar-acts { display: grid; grid-template-columns: 1fr; gap: .5rem; }
    @media (min-width: 640px) { .ar-acts { grid-template-columns: 1fr 1fr 1fr; } }
    html.dark .ar-card { background: #151b12; border-color: #2b3a1c; }
    html.dark .ar-card h3, html.dark .ar-li b, html.dark .ar-score b, html.dark .ar-proto b, html.dark .ar-next .t b { color: #e8efe1; }
    html.dark .ar-li, html.dark .ar-prose { color: #b7c2ad; }
    html.dark .ar-heart { background: linear-gradient(115deg, #1c2913, #22301a); border-color: #2b3a1c; color: #a8bd93; }
    html.dark .ar-score .track { background: #222b1a; }
    html.dark .ar-proto, html.dark .ar-next { border-color: #2b3a1c; }

    .ar-saved-row { display: flex; align-items: center; gap: .6rem; width: 100%; text-align: left;
        padding: .7rem .8rem; border-bottom: 1px solid var(--color-gray-100); cursor: pointer; }
    .ar-saved-row:hover { background: var(--color-brand-50); }
    .ar-saved-row b { display: block; font-size: .86rem; color: var(--color-gray-900); }
    .ar-saved-row small { color: var(--color-gray-400); font-size: .72rem; }
    html.dark .ar-saved-row { border-color: #222b1a; }
    html.dark .ar-saved-row:hover { background: #161e10; }
    html.dark .ar-saved-row b { color: #e8efe1; }

    .badge-sev-high { background: #fee2e2; color: #b91c1c; }
    .badge-sev-moderate { background: #fef3c7; color: #92400e; }
    .badge-sev-low { background: #ecfdf5; color: #047857; }
</style>
@endpush

@section('content')
<div class="ar-wrap">
    <div class="ar-tabs" role="tablist">
        <button type="button" class="ar-tab is-on" id="arTabGen">Generate</button>
        <button type="button" class="ar-tab" id="arTabSaved">Saved</button>
    </div>

    <div id="arGen">
        {{-- The price, said before anything is spent — folding, like wtp. --}}
        <div class="ar-quote" id="arQuote">
            <button type="button" class="arq-head" id="arQuoteHead">
                <span class="e">🔎</span>
                <span class="arq-title">Before you run one</span>
                <svg style="width:1rem;height:1rem;flex:none;color:#3d5226;opacity:.6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/></svg>
            </button>
            <div class="arq-body" id="arQuoteBody">
                <div class="arq-card">
                    This is a <b>deep AI analysis</b> — one report spends <b>{{ $price }} credits</b>, and you have <b id="arBalance">…</b>. Nothing is charged until you press Run{{ $isSofar ? '' : ', and the finished report saves itself to the shelf' }}.
                </div>
                <div class="arq-card">
                    {{ $isSofar
                        ? 'Anee reads the season as it stands — the work, the money, the sky\'s recent records — and says where it is, what is at risk, and what to do next. Treat it as a guide with an honest tongue: it will say "rescue" when that is the truth.'
                        : 'Anee reads the whole finished season — every activity, the money, the harvest, your notes and photos, the sky\'s actual records and ENSO — and writes the debrief: what went well, what went wrong, what to change and when. A guide, not a verdict.' }}
                </div>
            </div>
        </div>

        {{-- Readiness --}}
        <div class="card p-4 mb-4" id="arReadyCard">
            <p class="text-sm font-bold text-gray-900 dark:text-gray-100 mb-2" id="arReadyTitle">Checking the season…</p>
            <div id="arChecks"></div>
            @if ($isSofar && $schedule->lots->count())
                <div class="mt-3">
                    <span class="form-label text-xs! mb-1!">Analyze which lot?</span>
                    <button type="button" class="crop-tag" id="arLotBtn">
                        <span class="crop-tag-e">🌾</span>
                        <span class="crop-tag-t is-none" id="arLotNow">The whole season</span>
                        <svg class="crop-tag-c" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/></svg>
                    </button>
                </div>
            @endif
            <button type="button" class="ar-run mt-4" id="arRunBtn" disabled>
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2m0 0a7 7 0 017 7v3a3 3 0 01-3 3H8a3 3 0 01-3-3v-3a7 7 0 017-7zM9 12h.01M15 12h.01M9.5 17h5"/></svg>
                <span id="arRunSays">Run the analysis ({{ $price }} credits)</span>
            </button>
        </div>

        <div class="ar-report" id="arReport" hidden></div>
    </div>

    <div id="arSavedPane" class="hidden">
        <div class="card !p-0 overflow-hidden">
            <div id="arSavedList"></div>
            <div id="arSavedEmpty" class="hidden text-center py-10">
                <p class="font-bold text-gray-900 dark:text-gray-100">Nothing saved yet</p>
                <p class="text-sm text-gray-400">Every finished report lands here by itself.</p>
            </div>
        </div>
        <div class="ar-report mt-4" id="arSavedReport" hidden></div>
    </div>

    {{-- The veil --}}
    <div class="ar-wait" id="arWait" aria-live="polite">
        <img class="face" src="{{ \App\Models\AiSetting::current()->faceUrl() }}" alt="">
        <span class="spin" aria-hidden="true"></span>
        <p class="line" id="arWaitLine">Reading the whole season…</p>
        <p>This is a deep read — a few minutes is normal.</p>
        <p class="stay">Please stay on this screen and don't close the page — leaving loses this run.</p>
    </div>
</div>
@endsection

@push('sheets')
@if ($isSofar && $schedule->lots->count())
<div class="sheet hidden" id="arLotSheet" style="--sheet-width:24rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title">Analyze which lot?</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body dt-rows" id="arLotList">
        <button type="button" class="dt-row is-on" data-ar-lot="0">
            <span class="dt-row-e">🗺️</span>
            <span class="dt-row-body"><b>The whole season</b><i>Every lot, weighed together</i></span>
            <svg class="dt-row-tick" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
        </button>
        @foreach ($schedule->lots as $lot)
            <button type="button" class="dt-row" data-ar-lot="{{ $lot->id }}">
                <span class="dt-row-e">🌾</span>
                <span class="dt-row-body"><b>{{ $lot->lotName }}</b><i>{{ \App\Support\CropStages::label($lot->crop) ?: 'No crop set' }}{{ $lot->variety ? ' · ' . $lot->variety : '' }}</i></span>
                <svg class="dt-row-tick" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
            </button>
        @endforeach
    </div>
</div>
@endif
@endpush

@push('scripts')
<script>
(() => {
const __init = () => {
    const $id = (i) => document.getElementById(i);
    const esc = window.escapeHtml || ((s) => String(s ?? ''));
    const KIND = @json($isSofar ? 'sofar' : 'season');
    const PRICE = @json($price);
    const SCHEDULE_ID = @json($schedule->id);
    const ANEE = @json($aneeName);
    const FACE = @json(\App\Models\AiSetting::current()->faceUrl());
    const U = {
        status: @json(route('sm.anee.status') . '?id=' . $schedule->id . '&kind=' . ($isSofar ? 'sofar' : 'season')),
        generate: @json(route('sm.anee.generate')),
        job: (id) => @json(route('sm.anee.job', ['id' => '__ID__'])).replace('__ID__', id),
        list: @json(route('sm.anee.list') . '?id=' . $schedule->id . '&kind=' . ($isSofar ? 'sofar' : 'season')),
        one: (id) => @json(route('sm.anee.one', ['id' => '__ID__'])).replace('__ID__', id),
        del: (id) => @json(route('sm.anee.delete', ['id' => '__ID__'])).replace('__ID__', id),
        ai: @json(route('ai.index')),
    };
    let STATUS = null;
    let LOT_ID = 0;

    /* ---------------- fold ---------------- */
    $id('arQuoteHead').addEventListener('click', () => {
        const b = $id('arQuoteBody');
        b.hidden = !b.hidden;
    });

    /* ---------------- tabs ---------------- */
    const showTab = (gen) => {
        $id('arTabGen').classList.toggle('is-on', gen);
        $id('arTabSaved').classList.toggle('is-on', !gen);
        $id('arGen').classList.toggle('hidden', !gen);
        $id('arSavedPane').classList.toggle('hidden', gen);
        if (!gen) loadSaved();
    };
    $id('arTabGen').addEventListener('click', () => showTab(true));
    $id('arTabSaved').addEventListener('click', () => showTab(false));

    /* ---------------- readiness ---------------- */
    async function loadStatus() {
        try {
            const res = await api(U.status);
            STATUS = res.data;
            $id('arBalance').textContent = STATUS.unlimited ? '∞' : Number(STATUS.balance).toLocaleString();
            const checks = [];
            (STATUS.blockers || []).forEach((t) => checks.push(`<div class="ar-check is-block">⛔ ${esc(t)}</div>`));
            (STATUS.warnings || []).forEach((t) => checks.push(`<div class="ar-check is-warn">⚠️ ${esc(t)}</div>`));
            $id('arChecks').innerHTML = checks.join('');
            $id('arReadyTitle').textContent = STATUS.ready
                ? (checks.length ? 'Ready — with footnotes' : 'The season is ready for its read')
                : 'Not ready yet';
            $id('arRunBtn').disabled = !STATUS.ready;
        } catch (err) { toast(err.message, 'error'); }
    }

    /* ---------------- lot picker (sofar) ---------------- */
    $id('arLotBtn')?.addEventListener('click', () => openSheet('arLotSheet'));
    $id('arLotList')?.addEventListener('click', (e) => {
        const row = e.target.closest('[data-ar-lot]');
        if (!row) return;
        LOT_ID = Number(row.dataset.arLot);
        document.querySelectorAll('#arLotList [data-ar-lot]').forEach((r) => r.classList.toggle('is-on', r === row));
        const t = $id('arLotNow');
        t.textContent = LOT_ID ? row.querySelector('b').textContent : 'The whole season';
        t.classList.toggle('is-none', !LOT_ID);
        closeSheet('arLotSheet');
    });

    /* ---------------- the veil's rotating lines ---------------- */
    const LINES = KIND === 'sofar'
        ? ['Reading the season as it stands…', 'Weighing the work against the crop\'s clock…', 'Checking the sky\'s recent records…', 'Sizing up the risks…', 'Writing the what\'s-next list…']
        : ['Reading the whole season…', 'Adding up the money…', 'Checking the sky\'s records and ENSO…', 'Reading your notes and photos…', 'Comparing with your past seasons…', 'Writing it up, the honest way…'];
    let lineTimer = null;
    function veil(on) {
        $id('arWait').classList.toggle('is-on', on);
        clearInterval(lineTimer);
        if (on) {
            let i = 0;
            $id('arWaitLine').textContent = LINES[0];
            lineTimer = setInterval(() => {
                i = (i + 1) % LINES.length;
                const el = $id('arWaitLine');
                el.style.opacity = 0;
                setTimeout(() => { el.textContent = LINES[i]; el.style.opacity = 1; }, 280);
            }, 5200);
        }
    }

    /* ---------------- generate + poll ---------------- */
    $id('arRunBtn').addEventListener('click', async () => {
        if (!STATUS || !STATUS.ready) return;
        veil(true);
        try {
            const res = await api(U.generate, { method: 'POST', body: { scheduleId: SCHEDULE_ID, kind: KIND, lotId: LOT_ID || null } });
            let data = res.data;
            if (data.pending) {
                for (let i = 0; i < 160 && (!data || data.status !== 'ready'); i++) {
                    await new Promise((r) => setTimeout(r, 3000));
                    const st = await api(U.job(data.id));
                    if (st.data && st.data.status === 'ready') { data = st.data; break; }
                }
                if (!data || data.status !== 'ready') {
                    throw new Error('Still working — give it a minute, then look on the Saved tab.');
                }
            }
            drawReport($id('arReport'), data.report, data, 'fresh');
            $id('arReport').hidden = false;
            $id('arReadyCard').hidden = true;
            $id('arQuote').hidden = true;
            toast(`Done — ${data.credits} credits used. Saved to the shelf.`);
        } catch (err) {
            toast(err.message, 'error');
        } finally {
            veil(false);
        }
    });

    /* ---------------- the report, drawn ---------------- */
    const li = (e, t) => `<div class="ar-li"><span class="e">${e}</span><span>${esc(t)}</span></div>`;
    function drawReport(host, r, meta, mode) {
        r = r || {};
        const parts = [];
        const standing = (r.standing || '').toLowerCase();
        const heroCls = KIND === 'sofar' ? (standing === 'rescue' ? ' is-rescue' : (standing === 'watch' ? ' is-watch' : '')) : '';
        parts.push(`<div class="ar-hero${heroCls}">
            <h2>${esc(r.headline || meta.title || '')}</h2>
            ${KIND === 'sofar' && standing ? `<span class="chip">${standing === 'rescue' ? '🚨 needs rescue' : (standing === 'watch' ? '👀 on watch' : '✅ on track')}</span>` : ''}
            <p class="why">${esc(r.verdict || '')}</p>
        </div>`);

        if (KIND === 'season' && r.scores) {
            const S = r.scores;
            const rows = [['overall', 'Overall'], ['planning', 'Planning'], ['execution', 'Execution'], ['costControl', 'Cost control'], ['timing', 'Timing'], ['recordKeeping', 'Record keeping']]
                .filter(([k]) => S[k] !== undefined);
            parts.push(`<div class="ar-card"><h3>📈 The season, scored</h3>${rows.map(([k, label]) => `
                <div class="ar-score"><span>${label}</span><span class="track"><span class="fill" data-w="${Math.max(0, Math.min(100, Number(S[k]) || 0))}"></span></span><b>${Math.max(0, Math.min(100, Number(S[k]) || 0))}</b></div>`).join('')}</div>`);
        }

        const listCard = (title, arr, e) => (arr || []).length
            ? `<div class="ar-card"><h3>${title}</h3>${arr.map((t) => li(e, t)).join('')}</div>` : '';
        if (KIND === 'season') {
            parts.push(listCard('💪 What went well', r.strengths, '✅'));
            parts.push(listCard('🥀 What went wrong', r.wentWrong, '⚠️'));
            parts.push(listCard('💡 What to improve', r.improvements, '👉'));
            if ((r.protocolChanges || []).length) {
                parts.push(`<div class="ar-card"><h3>🔁 Protocol changes</h3>${r.protocolChanges.map((p) => `
                    <div class="ar-proto"><b>${esc(p.change || '')}</b>
                        <div class="swap"><s>${esc(p.current || '')}</s><br><em>${esc(p.suggested || '')}</em>
                        ${p.timing ? ` <span class="badge badge-gray">${esc(p.timing)}</span>` : ''}</div>
                        ${p.why ? `<div class="swap">${esc(p.why)}</div>` : ''}</div>`).join('')}</div>`);
            }
            parts.push(listCard('🧾 What was lacking', r.lacking, '▫️'));
            if (r.weatherStory) parts.push(`<div class="ar-card"><h3>🌦️ The weather's part</h3><p class="ar-prose">${esc(r.weatherStory)}</p></div>`);
            if (r.delays) parts.push(`<div class="ar-card"><h3>⏱️ Delays, honestly</h3><p class="ar-prose">${esc(r.delays)}</p></div>`);
            if (r.comparison) parts.push(`<div class="ar-card"><h3>📊 Against your past seasons</h3><p class="ar-prose">${esc(r.comparison)}</p></div>`);
            parts.push(listCard('📋 Next season checklist', r.nextSeason, '☑️'));
        } else {
            if ((r.risks || []).length) {
                parts.push(`<div class="ar-card"><h3>⚠️ The risks</h3>${r.risks.map((x) => `
                    <div class="ar-li"><span class="e">•</span><span><b>${esc(x.risk || '')}</b>
                        <span class="badge badge-sev-${esc((x.severity || 'low').toLowerCase())}">${esc(x.severity || '')}</span><br>${esc(x.why || '')}</span></div>`).join('')}</div>`);
            }
            if ((r.whatsNext || []).length) {
                parts.push(`<div class="ar-card"><h3>🧭 What's next</h3>${r.whatsNext.map((x, i) => `
                    <div class="ar-next"><span class="n">${i + 1}</span><span class="t"><b>${esc(x.action || '')}
                        ${x.urgency === 'now' ? '<span class="badge badge-sev-high">now</span>' : (x.urgency === 'soon' ? '<span class="badge badge-sev-moderate">soon</span>' : '')}</b>
                        <small>${esc(x.when || '')}${x.why ? ' — ' + esc(x.why) : ''}</small></span></div>`).join('')}</div>`);
            }
            parts.push(listCard('🧾 What the records lack', r.lacking, '▫️'));
            if (r.weatherStory) parts.push(`<div class="ar-card"><h3>🌦️ The weather ahead</h3><p class="ar-prose">${esc(r.weatherStory)}</p></div>`);
        }

        if (r.encouragement) {
            parts.push(`<div class="ar-heart"><img src="${esc(FACE)}" alt="">
                <span><b>A word from ${esc(ANEE)}</b><br>${esc(r.encouragement)}</span></div>`);
        }

        parts.push(`<div class="ar-acts">
            <a class="btn btn-primary w-full" href="${U.ai}?freport=${meta.id}">
                <img src="${esc(FACE)}" alt="" style="width:1rem;height:1rem;border-radius:999px;object-fit:cover;margin-right:.35rem;">
                Ask ${esc(ANEE)} about it
            </a>
            ${mode === 'fresh' ? `<button type="button" class="btn btn-white w-full" data-ar-again>Run another</button>` : ''}
            <button type="button" class="btn btn-white w-full" data-ar-del="${meta.id}">Delete</button>
        </div>`);

        host.innerHTML = parts.join('');
        requestAnimationFrame(() => host.querySelectorAll('.ar-score .fill').forEach((f) => { f.style.width = f.dataset.w + '%'; }));
        host.querySelector('[data-ar-again]')?.addEventListener('click', () => {
            host.hidden = true;
            $id('arReadyCard').hidden = false;
            $id('arQuote').hidden = false;
            loadStatus();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
        host.querySelector('[data-ar-del]')?.addEventListener('click', async (e) => {
            const id = e.currentTarget.getAttribute('data-ar-del');
            const ok = window.confirmAction ? await window.confirmAction({ title: 'Delete this report?', message: 'It leaves the shelf. The credits it used are already spent.', confirmText: 'Delete' }) : true;
            if (!ok) return;
            try {
                await api(U.del(id), { method: 'DELETE' });
                toast('Report removed.');
                host.hidden = true;
                $id('arReadyCard').hidden = false;
                $id('arQuote').hidden = false;
                loadStatus();
            } catch (err) { toast(err.message, 'error'); }
        });
    }

    /* ---------------- saved shelf ---------------- */
    async function loadSaved() {
        try {
            const res = await api(U.list);
            const rows = res.data.rows || [];
            $id('arSavedEmpty').classList.toggle('hidden', rows.length > 0);
            $id('arSavedList').innerHTML = rows.map((r) => `
                <button type="button" class="ar-saved-row" data-ar-open="${r.id}">
                    <img src="${esc(FACE)}" alt="" style="width:1.6rem;height:1.6rem;border-radius:999px;object-fit:cover;flex:none;">
                    <span class="min-w-0 grow"><b>${esc(r.title)}</b><small>${esc(r.when || '')} · ${r.credits} credits</small></span>
                    <svg style="width:1rem;height:1rem;flex:none;color:var(--color-gray-300)" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                </button>`).join('');
        } catch (err) { toast(err.message, 'error'); }
    }
    $id('arSavedList').addEventListener('click', async (e) => {
        const row = e.target.closest('[data-ar-open]');
        if (!row) return;
        try {
            const res = await api(U.one(row.getAttribute('data-ar-open')));
            drawReport($id('arSavedReport'), res.data.report, res.data, 'saved');
            $id('arSavedReport').hidden = false;
            $id('arSavedReport').scrollIntoView({ behavior: 'smooth', block: 'start' });
        } catch (err) { toast(err.message, 'error'); }
    });

    loadStatus();
};
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', __init, { once: true });
    else __init();
})();
</script>
@endpush
