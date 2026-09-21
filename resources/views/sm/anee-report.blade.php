@extends('layouts.app')

@php
    $isSofar = ($kind ?? 'season') === 'sofar';
    $price = \App\Support\AiPrices::of($isSofar ? 'sofar' : 'season');
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

    /* The wait while she works is the shared one -- sm/partials/anee-wait. */
    @media (prefers-reduced-motion: reduce) { .ar-run { transition: none; animation: none; } }

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
    /* The so-far hero: a standing word with a drawn mark, a score ring, a short headline. */
    .ar-hero-row { display: flex; align-items: center; gap: .9rem; }
    .ar-hero-t { min-width: 0; flex: 1 1 auto; }
    .ar-stand { display: inline-flex; align-items: center; gap: .35rem; font-size: .68rem; font-weight: 800; letter-spacing: .05em; text-transform: uppercase;
        padding: .22rem .65rem .22rem .5rem; border-radius: 999px; background: rgb(255 255 255 / .2); margin-bottom: .45rem; }
    .ar-stand svg { width: .95rem; height: .95rem; }
    .ar-ring { flex: none; width: 4.2rem; height: 4.2rem; border-radius: 999px; display: grid; place-items: center; position: relative;
        background: conic-gradient(rgb(255 255 255 / .95) calc(var(--p, 0) * 1%), rgb(255 255 255 / .22) 0); }
    .ar-ring::before { content: ''; position: absolute; inset: .38rem; border-radius: 999px; background: rgb(0 0 0 / .22); backdrop-filter: blur(2px); }
    .ar-ring b { position: relative; font-size: 1.2rem; font-weight: 900; font-variant-numeric: tabular-nums; }
    .ar-ring small { position: absolute; bottom: .5rem; font-size: .5rem; font-weight: 800; opacity: .8; letter-spacing: .04em; }
    /* The graphs: the crop on its clock, the plan to today, the money by category. */
    .ar-prog { display: grid; gap: .55rem; }
    .ar-prog-row { display: grid; gap: .25rem; }
    .ar-prog-h { display: flex; align-items: baseline; justify-content: space-between; gap: .5rem; font-size: .8rem; color: var(--color-gray-700); }
    .ar-prog-h b { color: var(--color-gray-900); }
    .ar-prog-h small { color: var(--color-gray-500); font-size: .72rem; white-space: nowrap; }
    .ar-prog .track { display: block; height: 10px; border-radius: 999px; background: var(--color-gray-100); overflow: hidden; }
    .ar-prog .fill { display: block; height: 100%; border-radius: 999px; background: linear-gradient(90deg, #6b9f3d, #3d6823); width: 0; transition: width .7s cubic-bezier(.22,1,.36,1); }
    .ar-prog .fill.is-plan { background: linear-gradient(90deg, #60a5fa, #2563eb); }
    .ar-kv { display: flex; flex-wrap: wrap; gap: .35rem; margin-top: .5rem; }
    .ar-kv span { display: inline-flex; align-items: baseline; gap: .3rem; padding: .22rem .6rem; border-radius: 999px; font-size: .72rem; font-weight: 700; border: 1px solid var(--color-gray-200); color: var(--color-gray-600); background: var(--color-white); }
    .ar-kv span b { font-size: .84rem; font-weight: 800; color: var(--color-gray-900); font-variant-numeric: tabular-nums; }
    .ar-kv span.is-bad b { color: #b91c1c; }
    .ar-stack { display: flex; height: 14px; border-radius: 999px; overflow: hidden; background: var(--color-gray-100); margin: .4rem 0 .5rem; }
    .ar-stack i { display: block; height: 100%; }
    .ar-legend { display: flex; flex-wrap: wrap; gap: .3rem .7rem; font-size: .72rem; color: var(--color-gray-600); }
    .ar-legend i { display: inline-block; width: .6rem; height: .6rem; border-radius: .2rem; margin-right: .3rem; vertical-align: -1px; }
    .ar-legend b { color: var(--color-gray-900); font-variant-numeric: tabular-nums; }
    /* The good and the bad, as rows. */
    .ar-gb { display: grid; gap: .4rem; }
    .ar-gb-row { padding: .55rem .7rem; border-radius: .75rem; font-size: .8rem; line-height: 1.5; border: 1px solid var(--color-gray-200); color: var(--color-gray-700); background: var(--color-white); }
    .ar-gb-row b { display: block; color: var(--color-gray-900); }
    .ar-gb-row.is-good { border-color: #cfe3bd; background: #f6fbf0; }
    .ar-gb-row.is-bad { border-color: #f3d9a4; background: #fffbf0; }
    .ar-gb-row.is-bad em { display: block; font-style: normal; color: #92400e; margin-top: .15rem; }
    .ar-two { display: grid; grid-template-columns: minmax(0, 1fr); gap: .5rem; margin-top: .5rem; }
    @media (min-width: 560px) { .ar-two { grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); } }
    .ar-two > div { border-radius: .75rem; padding: .55rem .7rem; font-size: .8rem; line-height: 1.5; border: 1px solid var(--color-gray-200); }
    .ar-two > div b { display: block; font-size: .7rem; font-weight: 800; letter-spacing: .05em; text-transform: uppercase; margin-bottom: .25rem; }
    .ar-two .is-ok { border-color: #cfe3bd; background: #f6fbf0; color: #2f5219; }
    .ar-two .is-miss { border-color: #f5c2c2; background: #fff7f7; color: #7f1d1d; }
    .ar-two ul { margin: 0; padding-left: 1.05rem; }
    .ar-drift { margin-top: .5rem; font-size: .8rem; color: var(--color-gray-700); line-height: 1.5; padding: .5rem .7rem; border-radius: .7rem; background: var(--color-gray-50); }
    html.dark .ar-prog-h, html.dark .ar-drift { color: #cbd5c0; }
    html.dark .ar-prog-h b, html.dark .ar-kv span b, html.dark .ar-legend b, html.dark .ar-gb-row b { color: #e8efe1; }
    html.dark .ar-prog .track, html.dark .ar-stack { background: #22301a; }
    html.dark .ar-kv span, html.dark .ar-gb-row { background: #151b12; border-color: #2b3a1c; color: #cbd5c0; }
    html.dark .ar-gb-row.is-good { background: #1a2513; border-color: #3f5a2a; }
    html.dark .ar-gb-row.is-bad { background: #262012; border-color: #6b4f16; }
    html.dark .ar-gb-row.is-bad em { color: #f0d9a8; }
    html.dark .ar-two .is-ok { background: #1a2513; border-color: #3f5a2a; color: #cfe6b8; }
    html.dark .ar-two .is-miss { background: #2a1717; border-color: #6b2b2b; color: #f0a3a3; }
    html.dark .ar-drift { background: #1c2416; }
    html.dark .ar-legend { color: #a5b89a; }
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
@php
    // A view-level worker reads the shelf; running a new report is edit work.
    $arMayGen = \App\Support\WorkerContext::canWriteModule('reports');
@endphp
@include('sm.partials.tag-picker')
@include('sm.partials.report-view')
<div class="ar-wrap">
    <div class="ar-tabs" role="tablist">
        <button type="button" class="ar-tab is-on" id="arTabGen" @unless($arMayGen) hidden @endunless>Generate</button>
        <button type="button" class="ar-tab" id="arTabSaved">Saved</button>
    </div>

    <div id="arGen">
        {{-- What this report is, before the price and the checks. --}}
        <div class="rx-about">
            <span class="rx-about-e"><img src="{{ \App\Models\AiSetting::current()->faceUrl() }}" alt=""></span>
            <div class="rx-about-t">
                @if ($isSofar)
                    <b>What Analyze So Far tells you</b>
                    <p>{{ \App\Models\AiSetting::current()->assistantName }} reads the season as it stands today — the work done and still to do, the money so far, the sky's recent records — and writes where the crop is and what comes next.</p>
                    <ul>
                        <li><b>Where the crop stands</b> against its own clock, lot by lot or the whole season</li>
                        <li><b>The risks in front of it</b> — weather, pests, timing — and what to watch</li>
                        <li><b>What to do next</b>, in order, and what to stop doing</li>
                        <li><b>How the money is running</b> against the plan</li>
                    </ul>
                @else
                    <b>What the {{ \App\Models\AiSetting::current()->assistantName }} Season Report tells you</b>
                    <p>{{ \App\Models\AiSetting::current()->assistantName }} reads the whole finished season — every activity, the money, the harvest, your notes and photos, the sky's actual records and ENSO — and writes the season's story.</p>
                    <ul>
                        <li><b>What went right</b> and what it was worth</li>
                        <li><b>What went wrong</b>, when, and what it cost</li>
                        <li><b>What to change next season</b> — timing, inputs, labor, water</li>
                        <li><b>A score</b> for the season, with the reasons</li>
                    </ul>
                @endif
                <p class="rx-about-note">This is a deep AI read and spends credits; the price is said before anything runs. Every report is saved on the shelf, where you can rename and describe it.</p>
            </div>
        </div>

        {{-- The price, said before anything is spent — folding, like wtp. --}}
        <div class="ar-quote" id="arQuote">
            <button type="button" class="arq-head" id="arQuoteHead">
                <span class="e">🔎</span>
                <span class="arq-title">Before you run one</span>
                <svg style="width:1rem;height:1rem;flex:none;color:#3d5226;opacity:.6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/></svg>
            </button>
            <div class="arq-body" id="arQuoteBody">
                <div class="arq-card">
                    This is a <b>deep AI analysis</b> — one report spends <b>{{ $price }} credits</b>, and you have @if (\App\Support\WorkerContext::inWorkerContext())<span class="credit-coin">@else<a class="credit-coin" href="{{ route('ai.credits') }}" title="My Credits — the log, and credits to buy">@endif<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9.2" fill="#f0b429" stroke="#c98a12" stroke-width="1.6"/><circle cx="12" cy="12" r="5" fill="none" stroke="#c98a12" stroke-width="1.3" opacity=".75"/></svg><b id="arBalance">…</b>@if (\App\Support\WorkerContext::inWorkerContext())</span>@else</a>@endif. Nothing is charged until you press Run{{ $isSofar ? '' : ', and the finished report saves itself to the shelf' }}.
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
            <p class="text-sm font-bold text-gray-900 mb-2" id="arReadyTitle">Checking the season…</p>
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
            <div id="arSavedEmpty" class="hidden rx-empty">
                <span class="rx-empty-e"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M9 12h6m-6 4h6m2 5H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l5.414 5.414a1 1 0 0 1 .293.707V19a2 2 0 0 1-2 2z"/></svg></span>
                <p class="rx-empty-t">Nothing saved yet</p>
                <p class="rx-empty-p">Run a report and it lands here by itself — every one you make, newest first, ready to rename and describe.</p>
            </div>
        </div>
        <div class="ar-report mt-4" id="arSavedReport" hidden></div>
    </div>

    {{-- The wait: Anee's face at work, shared by every AI run. --}}
    @include('sm.partials.anee-wait')
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
    // A visitor who may only read lands on the shelf, not on a Run button
    // that could only ever answer no.
    if (@json(! $arMayGen)) showTab(false);

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
    /* ---------------- generate + poll ---------------- */
    $id('arRunBtn').addEventListener('click', async () => {
        if (!STATUS || !STATUS.ready) return;
        window.aneeWait.show({
            title: KIND === 'sofar' ? 'Anee is reading the season so far…' : 'Anee is reading the whole season…',
            lines: LINES,
            sub: 'This is a deep read — a few minutes is normal.',
        });
        let landed = false;
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
            landed = true;
            // Her face lights up over the finished report; the veil lifts after.
            await window.aneeWait.done({ title: 'Done!', line: `${data.credits} credits used — saved to the shelf.` });
            toast(`Done — ${data.credits} credits used. Saved to the shelf.`);
            showInView(data, $id('arReport'), 'fresh');
        } catch (err) {
            toast(err.message, 'error');
        } finally {
            if (!landed) window.aneeWait.fail();
        }
    });

    /* ---------------- the report, drawn ---------------- */
    const li = (e, t) => `<div class="ar-li"><span class="e">${e}</span><span>${esc(t)}</span></div>`;
    function drawReport(host, r, meta, mode) {
        r = r || {};
        const parts = [];
        const standing = (r.standing || '').toLowerCase();
        const heroCls = KIND === 'sofar' ? (standing === 'rescue' ? ' is-rescue' : (standing === 'watch' ? ' is-watch' : '')) : '';
        // The standing, said with a drawn mark and plain words.
        const STAND = {
            'on-track': { word: 'On track', icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="m8.5 12.5 2.5 2.5 4.5-5"/></svg>' },
            watch: { word: 'Needs attention', icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3 2.5 20h19L12 3z"/><path d="M12 9v5m0 3h.01"/></svg>' },
            rescue: { word: 'Needs rescue', icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="4"/><path d="m5.6 5.6 3.6 3.6m5.6 5.6 3.6 3.6m0-12.8-3.6 3.6m-5.6 5.6-3.6 3.6"/></svg>' },
        };
        const st = KIND === 'sofar' ? (STAND[standing] || null) : null;
        const score = Number.isFinite(Number(r.score)) ? Math.max(0, Math.min(100, Math.round(Number(r.score)))) : null;
        parts.push(`<div class="ar-hero${heroCls}">
            <div class="ar-hero-row">
                ${KIND === 'sofar' && score !== null ? `<div class="ar-ring" style="--p:${score}"><b>${score}</b><small>/100</small></div>` : ''}
                <div class="ar-hero-t">
                    ${st ? `<span class="ar-stand">${st.icon}${st.word}</span>` : ''}
                    <h2>${esc(r.headline || meta.title || '')}</h2>
                </div>
            </div>
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
            // The scores, five ways.
            if (r.scores && typeof r.scores === 'object') {
                const S = r.scores;
                const rows = [['protocol', 'Protocol'], ['timing', 'Timing'], ['weather', 'Weather'], ['money', 'Money'], ['records', 'Records']].filter(([k]) => S[k] !== undefined && S[k] !== null);
                if (rows.length) parts.push(`<div class="ar-card"><h3>📈 The season so far, scored</h3>${rows.map(([k, label]) => `
                    <div class="ar-score"><span>${label}</span><span class="track"><span class="fill" data-w="${Math.max(0, Math.min(100, Number(S[k]) || 0))}"></span></span><b>${Math.max(0, Math.min(100, Number(S[k]) || 0))}</b></div>`).join('')}</div>`);
            }
            // The graphs: the app's own arithmetic, kept with the report.
            const F = r.facts || null;
            if (F && (F.lots || []).length) {
                parts.push(`<div class="ar-card"><h3>🌱 Where the crop stands</h3><div class="ar-prog">${F.lots.map((l) => `
                    <div class="ar-prog-row">
                        <div class="ar-prog-h"><span><b>${esc(l.name)}</b> · ${esc(l.icon || '')} ${esc(l.crop || '')}</span><small>${l.day !== null && l.day !== undefined ? `${esc(l.counter)} ${l.day}` : 'no day zero'}${l.maturity ? ` of ~${l.maturity}` : ''}${l.stage ? ` · ${esc(l.stage)}` : ''}</small></div>
                        <span class="track"><span class="fill" data-w="${l.pct === null || l.pct === undefined ? 0 : l.pct}"></span></span>
                    </div>`).join('')}</div><p class="text-xs text-gray-400 mt-2">As of ${esc(F.asOf || '')} — the bar is the crop's calendar, day zero to typical maturity.</p></div>`);
            }
            if (F && F.plan && F.plan.total) {
                const P = F.plan;
                const pct = P.planned ? Math.round(P.done / P.planned * 100) : 0;
                parts.push(`<div class="ar-card"><h3>📋 The plan to today</h3>
                    <div class="ar-prog"><div class="ar-prog-row"><div class="ar-prog-h"><span><b>${P.done}</b> of ${P.planned} planned to date ticked done</span><small>${pct}%</small></div><span class="track"><span class="fill is-plan" data-w="${pct}"></span></span></div></div>
                    <div class="ar-kv"><span class="${P.overdue ? 'is-bad' : ''}"><b>${P.overdue}</b> overdue</span><span><b>${P.coming}</b> in the next 14 days</span><span><b>${P.doneAll}</b> of ${P.total} done overall</span></div></div>`);
            }
            if (F && F.money && (Number(F.money.cost) > 0 || Number(F.money.revenue) > 0)) {
                const M = F.money; const C = M.cats || {};
                const CATS = [['materials', 'Materials', '#15803d'], ['labor', 'Labor', '#d97706'], ['services', 'Services', '#2563eb'], ['expense', 'Extra expenses', '#b91c1c'], ['purchase', 'Stock buys', '#7c3aed']].filter(([k]) => Number(C[k]) > 0);
                const total = CATS.reduce((n, [k]) => n + Number(C[k]), 0) || 1;
                const peso = (n) => ((window.ANEE_REGION || {}).symbol || '₱') + Math.round(Number(n || 0)).toLocaleString(((window.ANEE_REGION || {}).locale || 'en-PH'));
                parts.push(`<div class="ar-card"><h3>💸 The money so far</h3>
                    <div class="ar-kv"><span>Spent <b>${peso(M.cost)}</b></span><span>Earned <b>${peso(M.revenue)}</b></span><span class="${Number(M.profit) < 0 ? 'is-bad' : ''}">${Number(M.profit) < 0 ? 'Loss' : 'Net'} <b>${peso(Math.abs(Number(M.profit)))}</b></span>${r.money && r.money.verdict ? `<span>Spend is <b>${esc(r.money.verdict)}</b></span>` : ''}</div>
                    ${CATS.length ? `<div class="ar-stack">${CATS.map(([k, , c]) => `<i style="width:${(Number(C[k]) / total * 100).toFixed(1)}%;background:${c}" title="${k}"></i>`).join('')}</div>
                    <div class="ar-legend">${CATS.map(([k, label, c]) => `<span><i style="background:${c}"></i>${label} <b>${peso(C[k])}</b></span>`).join('')}</div>` : ''}
                    ${r.money && r.money.summary ? `<p class="ar-prose mt-3">${esc(r.money.summary)}</p>` : ''}</div>`);
            } else if (r.money && r.money.summary) {
                parts.push(`<div class="ar-card"><h3>💸 The money so far</h3><p class="ar-prose">${esc(r.money.summary)}</p></div>`);
            }
            // The good and the bad.
            if ((r.good || []).length) parts.push(`<div class="ar-card"><h3>💪 What's good</h3><div class="ar-gb">${r.good.map((g) => `<div class="ar-gb-row is-good"><b>${esc(g.point || '')}</b>${esc(g.why || '')}</div>`).join('')}</div></div>`);
            if ((r.bad || []).length) parts.push(`<div class="ar-card"><h3>🩹 What needs work</h3><div class="ar-gb">${r.bad.map((b) => `<div class="ar-gb-row is-bad"><b>${esc(b.point || '')}</b>${esc(b.why || '')}${b.fix ? `<em>Fix: ${esc(b.fix)}</em>` : ''}</div>`).join('')}</div></div>`);
            // The protocol so far.
            if (r.protocol && typeof r.protocol === 'object') {
                const Pp = r.protocol;
                parts.push(`<div class="ar-card"><h3>📋 The protocol so far</h3>
                    ${Pp.summary ? `<p class="ar-prose">${esc(Pp.summary)}</p>` : ''}
                    ${((Pp.followed || []).length || (Pp.missed || []).length) ? `<div class="ar-two">
                        ${(Pp.followed || []).length ? `<div class="is-ok"><b>Done as planned</b><ul>${Pp.followed.map((x) => `<li>${esc(x)}</li>`).join('')}</ul></div>` : ''}
                        ${(Pp.missed || []).length ? `<div class="is-miss"><b>Missed, late or never planned</b><ul>${Pp.missed.map((x) => `<li>${esc(x)}</li>`).join('')}</ul></div>` : ''}
                    </div>` : ''}
                    ${Pp.drift ? `<p class="ar-drift">${esc(Pp.drift)}</p>` : ''}</div>`);
            }
            if (r.timing && typeof r.timing === 'object' && (r.timing.summary || r.timing.stage)) {
                const T = r.timing;
                const behind = Number.isFinite(Number(T.daysBehind)) && T.daysBehind !== null ? Number(T.daysBehind) : null;
                parts.push(`<div class="ar-card"><h3>⏱️ Timing</h3>
                    <div class="ar-kv" style="margin:0 0 .5rem">${T.stage ? `<span>Stage <b>${esc(T.stage)}</b></span>` : ''}${behind !== null ? `<span class="${behind > 0 ? 'is-bad' : ''}"><b>${behind > 0 ? behind + ' days behind' : (behind < 0 ? (-behind) + ' days ahead' : 'On time')}</b></span>` : ''}</div>
                    ${T.summary ? `<p class="ar-prose">${esc(T.summary)}</p>` : ''}</div>`);
            }
            if (r.weather && typeof r.weather === 'object' && (r.weather.summary || r.weather.outlook)) {
                const Wx = r.weather;
                parts.push(`<div class="ar-card"><h3>🌦️ The weather</h3>
                    ${Wx.summary ? `<p class="ar-prose">${esc(Wx.summary)}</p>` : ''}
                    ${Wx.outlook ? `<p class="ar-prose mt-2"><b>Ahead:</b> ${esc(Wx.outlook)}</p>` : ''}
                    ${(Wx.risks || []).length ? `<div class="ar-kv">${Wx.risks.map((x) => `<span>${esc(x)}</span>`).join('')}</div>` : ''}</div>`);
            } else if (r.weatherStory) {
                parts.push(`<div class="ar-card"><h3>🌦️ The weather ahead</h3><p class="ar-prose">${esc(r.weatherStory)}</p></div>`);
            }
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
        }

        if (r.encouragement) {
            parts.push(`<div class="ar-heart"><img src="${esc(FACE)}" alt="">
                <span><b>A word from ${esc(ANEE)}</b><br>${esc(r.encouragement)}</span></div>`);
        }

        host.innerHTML = parts.join('');
        requestAnimationFrame(() => host.querySelectorAll('.ar-score .fill, .ar-prog .fill').forEach((f) => { f.style.width = f.dataset.w + '%'; }));
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

    /* ---------------- the full-screen view ----------------
     * A fresh report and a shelf row land in the same screen, with the
     * actions under the report. Closed, the farmer is on the Saved shelf. */
    let VIEWING = null;
    function showInView(meta, host, mode) {
        VIEWING = { id: meta.id, title: meta.title || '', description: meta.description || '', mine: meta.mine !== false };
        const actions = [
            { label: 'Ask ' + ANEE + ' about it', face: FACE, kind: 'primary', href: U.ai + '?freport=' + meta.id },
        ];
        if (@json($arMayGen) && VIEWING.mine) {
            actions.push({ label: 'Name & description', icon: 'pen', onClick: () => openReportMeta(meta.id) });
            actions.push({ label: 'Delete', icon: 'trash', kind: 'danger', onClick: async () => {
                const ok = window.confirmAction ? await window.confirmAction({ title: 'Delete this report?', message: 'It leaves the shelf. The credits it used are already spent.', confirmText: 'Delete' }) : confirm('Delete this report?');
                if (!ok) return;
                try { await api(U.del(meta.id), { method: 'DELETE' }); toast('Report removed.'); window.reportView.close(); }
                catch (err) { toast(err.message, 'error'); }
            } });
        }
        actions.push({ label: mode === 'fresh' ? 'Run another' : 'Close', icon: mode === 'fresh' ? 'plus' : 'close', onClick: () => window.reportView.close() });
        window.reportView.open({
            title: VIEWING.title || (KIND === 'sofar' ? 'Analyze So Far' : ANEE + ' Season Report'),
            node: host,
            actions,
            onClose: () => { VIEWING = null; $id('arTabSaved')?.click(); },
        });
    }

    /* ---------------- saved shelf ---------------- */
    async function loadSaved() {
        try {
            const res = await api(U.list + '&_=' + Date.now());
            const rows = res.data.rows || [];
            $id('arSavedEmpty').classList.toggle('hidden', rows.length > 0);
            SAVED_ROWS = rows;
            $id('arSavedList').innerHTML = rows.map((r) => `
                <button type="button" class="ar-saved-row" data-ar-open="${r.id}">
                    <img src="${esc(FACE)}" alt="" style="width:1.6rem;height:1.6rem;border-radius:999px;object-fit:cover;flex:none;">
                    <span class="min-w-0 grow"><b>${esc(r.title)}</b><small>${r.description ? esc(r.description) + ' · ' : ''}${esc(r.when || '')} · ${r.credits} credits</small></span>
                    ${@json($arMayGen) ? `<span role="button" tabindex="0" class="ar-pen" data-ar-meta="${r.id}" title="Edit name, description and tags" aria-label="Edit ${esc(r.title)}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:.85rem;height:.85rem"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    </span>` : ''}
                    <svg style="width:1rem;height:1rem;flex:none;color:var(--color-gray-300)" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                </button>`).join('');
        } catch (err) { toast(err.message, 'error'); }
    }
    let SAVED_ROWS = [];
    let META_ID = null;
    async function openReportMeta(id) {
        let r = SAVED_ROWS.find((x) => String(x.id) === String(id));
        if (!r) {
            try { const res = await api(U.one(id)); r = { id: res.data.id, title: res.data.title || '', description: res.data.description || '' }; }
            catch (err) { toast(err.message, 'error'); return; }
        }
        META_ID = r.id;
        document.getElementById('arMetaTitle').value = r.title || '';
        document.getElementById('arMetaDesc').value = r.description || '';
        const mount = document.getElementById('arMetaTags');
        if (window.smTags && mount) {
            window.smTags.mount(mount);
            window.smTags.load(mount, 'report', r.id);
        }
        openSheet('arMetaSheet');
    }
    document.addEventListener('click', async (e) => {
        const saveBtn = e.target.closest('#arMetaSave');
        if (!saveBtn || META_ID === null) return;
        saveBtn.disabled = true;
        try {
            const res = await api(@json(route('sm.anee.meta')), {
                method: 'POST',
                body: {
                    id: META_ID,
                    title: document.getElementById('arMetaTitle').value.trim(),
                    description: document.getElementById('arMetaDesc').value.trim(),
                    tags: window.smTags ? window.smTags.value(document.getElementById('arMetaTags')) : [],
                },
            });
            toast(res.message);
            closeSheet('arMetaSheet');
            if (VIEWING && VIEWING.id === META_ID) {
                VIEWING.title = document.getElementById('arMetaTitle').value.trim();
                window.reportView?.setTitle(VIEWING.title);
            }
            loadSaved();
        } catch (err) { toast(err.message, 'error'); }
        finally { saveBtn.disabled = false; }
    });
    $id('arSavedList').addEventListener('click', async (e) => {
        const pen = e.target.closest('[data-ar-meta]');
        if (pen) { e.stopPropagation(); openReportMeta(pen.getAttribute('data-ar-meta')); return; }
        const row = e.target.closest('[data-ar-open]');
        if (!row) return;
        try {
            const res = await api(U.one(row.getAttribute('data-ar-open')));
            drawReport($id('arSavedReport'), res.data.report, res.data, 'saved');
            showInView(res.data, $id('arSavedReport'), 'saved');
        } catch (err) { toast(err.message, 'error'); }
    });

    loadStatus();

    // A tag shelf names one saved report (?open=<id>): draw it straight
    // away, exactly as tapping its row on the shelf below would.
    {
        const want = new URLSearchParams(location.search).get('open');
        if (want) (async () => {
            try {
                const res = await api(U.one(String(want).replace(/[^\d]/g, '')));
                drawReport($id('arSavedReport'), res.data.report, res.data, 'saved');
                showInView(res.data, $id('arSavedReport'), 'saved');
            } catch (err) { toast(err.message || 'That saved report could not be opened.', 'error'); }
        })();
    }
};
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', __init, { once: true });
    else __init();
})();
</script>
@endpush

@push('head')
<style>
    .ar-pen { flex: none; width: 1.6rem; height: 1.6rem; border-radius: .45rem; display: inline-flex;
        align-items: center; justify-content: center; color: var(--color-gray-400); }
    .ar-pen:hover { color: var(--color-brand-700); background: var(--color-brand-50); }
    html.dark .ar-pen:hover { background: rgb(107 159 61 / .18); color: #a5c97e; }
</style>
@endpush

@push('sheets')
{{-- Rename a saved report, describe it, retie its tags. --}}
<div class="sheet hidden" id="arMetaSheet" style="--sheet-width:28rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title">Edit this report</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body space-y-4">
        <div>
            <label class="form-label" for="arMetaTitle">Name</label>
            <input type="text" id="arMetaTitle" class="form-input" maxlength="191">
        </div>
        <div>
            <label class="form-label" for="arMetaDesc">Description <span class="text-gray-400 font-normal">(optional)</span></label>
            <textarea id="arMetaDesc" class="form-textarea" rows="3" maxlength="2000"></textarea>
        </div>
        <div>
            <span class="form-label">Tags</span>
            <div class="tp-mount" data-tags data-tags-kind="report" id="arMetaTags"></div>
        </div>
    </div>
    <div class="sheet-footer">
        <button type="button" class="btn btn-ghost" data-sheet-close>Cancel</button>
        <button type="button" class="btn btn-primary" id="arMetaSave">Save changes</button>
    </div>
</div>
@endpush
