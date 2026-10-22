{{-- A saved plan put to work (2026-10-22, the owner): into a cropping
     schedule (a new one, or one you have, lot by lot) or into the Protocol
     Builder. Shared by the Land Prep Planner and the Crop Protocol Analysis:
     window.planPort.season(opts) and window.planPort.builder(opts), opts =
     {source: 'landprep'|'protocol', id, title, lots: [{i, name, crop,
     cropLabel, planting}], prev: {id, title, at}|null}.

     Into a season that already has work, the plan goes onto lots that are
     not harvested; a lot whose part of the season is over refuses it; else
     Anee checks it against the board for credits, and the farmer places it by
     the crew's free days or by her dates. App\Http\Controllers\PlanPortController. --}}
@once
@php $ppFace = \App\Models\AiSetting::current()->faceUrl(); @endphp
<style>
    .pp { --pp-e: cubic-bezier(.22,1,.36,1); --pp-g: #4a7c2a; --pp-gx: #f4f9ee; --pp-line: var(--color-gray-200); }
    html.dark .pp { --pp-gx: #1a2414; --pp-line: #2b3a1c; }
    .pp { padding: 0; border: 0; background: transparent; max-width: none; max-height: none; width: 100%; height: 100%; margin: 0; inset: 0; overflow: hidden; }
    .pp::backdrop { background: rgb(10 18 6 / .5); animation: ppFade .28s var(--pp-e); }
    .pp.is-closing::backdrop { animation: ppFade .22s ease reverse forwards; }
    .pp-box { position: absolute; left: 0; right: 0; bottom: 0; max-height: 92vh; max-height: 92dvh; display: flex; flex-direction: column; border-radius: 1.4rem 1.4rem 0 0;
        background: #fff; box-shadow: 0 -18px 40px -24px rgb(0 0 0 / .45); animation: ppUp .32s var(--pp-e); }
    .pp.is-closing .pp-box { animation: ppUp .24s var(--pp-e) reverse forwards; }
    @media (min-width: 640px) {
        .pp-box { left: 50%; top: 50%; bottom: auto; right: auto; width: min(34rem, calc(100vw - 2rem)); max-height: min(48rem, 90vh); border-radius: 1.4rem; transform: translate(-50%, -50%); animation-name: ppPop; }
        .pp.is-closing .pp-box { animation-name: ppPop; }
    }
    @keyframes ppFade { from { opacity: 0; } }
    @keyframes ppUp { from { transform: translateY(100%); } }
    @keyframes ppPop { from { opacity: 0; transform: translate(-50%, -46%) scale(.97); } }
    .pp-grip { width: 2.6rem; height: .3rem; margin: .55rem auto 0; border-radius: 999px; background: #d7dfcd; flex: none; }
    @media (min-width: 640px) { .pp-grip { display: none; } }
    .pp-head { flex: none; display: flex; align-items: center; gap: .6rem; padding: .7rem 1rem .55rem 1.15rem; }
    .pp-head h2 { flex: 1; min-width: 0; font-family: var(--font-heading); font-weight: 800; font-size: 1.1rem; line-height: 1.3; color: #14210c; }
    .pp-head h2 small { display: block; margin-top: .1rem; font-family: inherit; font-size: .76rem; font-weight: 600; line-height: 1.4; color: var(--color-gray-500); }
    .pp-x { flex: none; width: 2.4rem; height: 2.4rem; border-radius: 999px; display: grid; place-items: center; color: #4b5563; background: #f3f5ef; transition: background-color .28s var(--pp-e); }
    .pp-x:hover { background: #e7ecdf; }
    .pp-x svg { width: 1.1rem; height: 1.1rem; }
    .pp-body { flex: 1; min-height: 0; overflow-y: auto; overscroll-behavior: contain; padding: .2rem 1rem 1rem; }
    .pp-pane { animation: ppIn .3s var(--pp-e) both; }
    .pp-pane[hidden] { display: none; }
    @keyframes ppIn { from { opacity: 0; transform: translateY(8px); } }
    .pp-foot { flex: none; display: flex; flex-wrap: wrap; gap: .5rem; justify-content: flex-end; padding: .75rem 1rem calc(.75rem + env(safe-area-inset-bottom)); border-top: 1px solid var(--pp-line); }
    .pp-foot .btn { min-height: 2.75rem; justify-content: center; }
    .pp-foot .btn[hidden] { display: none; }
    @media (max-width: 479px) { .pp-foot .btn { flex: 1 1 auto; } }
    .btn.pp-cta { color: #3b2f00; background: #f5c518; border-color: #f5c518; box-shadow: 0 10px 22px -14px rgb(245 197 24 / .9); }
    .btn.pp-cta:hover { background: #ffd23f; border-color: #ffd23f; }
    .btn.pp-cta:disabled { opacity: .6; }
    .pp-label { display: block; margin: 1rem 0 .45rem; font-size: .8rem; font-weight: 800; color: var(--color-gray-700); }
    .pp-label:first-child { margin-top: .2rem; }
    .pp-choices { display: grid; gap: .55rem; }
    .pp-choice { width: 100%; display: flex; align-items: center; gap: .75rem; padding: .75rem .8rem; border-radius: 1rem; text-align: left; background: #fff; border: 1.5px solid var(--color-gray-200);
        transition: border-color .28s var(--pp-e), background-color .28s var(--pp-e), transform .28s var(--pp-e), box-shadow .28s var(--pp-e); }
    .pp-choice:hover { border-color: #a8cc7e; transform: translateY(-1px); }
    .pp-choice.is-on { border-color: var(--pp-g); background: var(--pp-gx); box-shadow: 0 0 0 3px rgb(107 159 61 / .16); }
    .pp-choice[disabled] { opacity: .55; cursor: not-allowed; transform: none; }
    .pp-choice .ce { flex: none; width: 2.5rem; height: 2.5rem; border-radius: .85rem; display: grid; place-items: center; font-size: 1.25rem; background: var(--color-gray-50); border: 1px solid var(--color-gray-100); }
    .pp-choice .ct { min-width: 0; flex: 1; }
    .pp-choice .ct b { display: block; font-size: .92rem; font-weight: 800; color: var(--color-gray-900); line-height: 1.25; }
    .pp-choice .ct small { display: block; margin-top: .12rem; font-size: .75rem; line-height: 1.4; color: var(--color-gray-500); }
    .pp-choice .ck { flex: none; width: 1.4rem; height: 1.4rem; border-radius: 999px; display: grid; place-items: center; color: #3b2f00; background: #f5c518; opacity: 0; transform: scale(.5);
        transition: opacity .28s var(--pp-e), transform .28s var(--pp-e); }
    .pp-choice .ck svg { width: .8rem; height: .8rem; }
    .pp-choice.is-on .ck { opacity: 1; transform: none; }
    .pp-fold { display: grid; grid-template-rows: 0fr; opacity: 0; transition: grid-template-rows .28s var(--pp-e), opacity .28s var(--pp-e); }
    .pp-fold.is-open { grid-template-rows: 1fr; opacity: 1; }
    .pp-fold > div { min-height: 0; overflow: hidden; }
    .pp-list { display: grid; gap: .45rem; max-height: 19rem; overflow-y: auto; padding: .1rem; }
    .pp-none { padding: .8rem .9rem; border-radius: .9rem; border: 1px dashed var(--color-gray-300); font-size: .82rem; line-height: 1.5; color: var(--color-gray-600); }
    .pp-lot { padding: .7rem .75rem; border-radius: 1rem; border: 1px solid var(--color-gray-200); background: var(--color-gray-50); }
    .pp-lot + .pp-lot { margin-top: .55rem; }
    .pp-lot-h b { display: block; font-size: .88rem; font-weight: 800; color: var(--color-gray-900); }
    .pp-lot-h small { display: block; font-size: .74rem; color: var(--color-gray-500); }
    .pp-chips { display: flex; flex-wrap: wrap; gap: .35rem; margin-top: .5rem; }
    .pp-chip { padding: .38rem .7rem; border-radius: 999px; font-size: .78rem; font-weight: 700; color: var(--color-gray-700); background: #fff; border: 1.5px solid var(--color-gray-200);
        transition: background-color .28s var(--pp-e), border-color .28s var(--pp-e), color .28s var(--pp-e); }
    .pp-chip.is-on { color: #fff; background: var(--pp-g); border-color: var(--pp-g); }
    .pp-chip[disabled] { opacity: .5; cursor: not-allowed; text-decoration: line-through; }
    .pp-chip small { font-weight: 600; opacity: .8; }
    .pp-say { font-size: .86rem; line-height: 1.55; color: var(--color-gray-700); }
    .pp-say b { color: var(--color-gray-900); }
    .pp-facts { display: grid; gap: .4rem; margin-top: .8rem; }
    .pp-fact { display: flex; gap: .6rem; align-items: flex-start; padding: .55rem .7rem; border-radius: .85rem; background: var(--color-gray-50); border: 1px solid var(--color-gray-100); font-size: .8rem; line-height: 1.45; color: var(--color-gray-700); }
    .pp-fact > span:first-child { flex: none; font-size: 1rem; line-height: 1.3; }
    .pp-fact > span + span { flex: 1 1 auto; min-width: 0; overflow-wrap: anywhere; }
    .pp-fact b { color: var(--color-gray-900); }
    .pp-hero { display: flex; gap: .8rem; align-items: center; padding: .85rem .9rem; border-radius: 1.1rem; background: var(--pp-gx); border: 1px solid #d2e5bd; }
    .pp-hero img { flex: none; width: 3rem; height: 3rem; border-radius: 999px; object-fit: cover; box-shadow: 0 0 0 3px #fff; }
    .pp-hero b { display: block; font-size: .95rem; font-weight: 800; color: #14210c; }
    .pp-hero small { display: block; margin-top: .15rem; font-size: .78rem; line-height: 1.45; color: var(--color-gray-600); }
    .pp-hero.is-stop { background: #fdf0ee; border-color: #f7d4cf; }
    .pp-hero.is-stop b { color: #7a1a12; }
    .pp-stop-ico { flex: none; width: 3rem; height: 3rem; border-radius: 999px; display: grid; place-items: center; font-size: 1.4rem; background: #fff; }
    .pp-price { display: flex; align-items: center; justify-content: space-between; gap: .6rem; margin-top: .8rem; padding: .7rem .85rem; border-radius: 1rem; background: #fffbeb; border: 1px solid #fde68a; }
    .pp-price b { font-size: .9rem; color: #78350f; }
    .pp-price small { display: block; font-size: .74rem; color: #92400e; }
    .pp-price .pp-amt { font-size: 1.15rem; font-weight: 900; color: #78350f; white-space: nowrap; }
    .pp-wait { display: grid; justify-items: center; gap: .7rem; padding: 1.6rem .5rem 1rem; text-align: center; }
    .pp-wait img { width: 4.2rem; height: 4.2rem; border-radius: 999px; object-fit: cover; animation: ppBob 2.4s ease-in-out infinite; box-shadow: 0 0 0 4px #e4efd4; }
    @keyframes ppBob { 50% { transform: translateY(-4px); } }
    .pp-wait b { font-size: 1rem; color: #14210c; }
    .pp-wait small { font-size: .8rem; color: var(--color-gray-500); max-width: 22rem; line-height: 1.5; }
    .pp-dots { display: flex; gap: .3rem; }
    .pp-dots i { width: .45rem; height: .45rem; border-radius: 999px; background: var(--pp-g); animation: ppDot 1.2s ease-in-out infinite; }
    .pp-dots i:nth-child(2) { animation-delay: .15s; } .pp-dots i:nth-child(3) { animation-delay: .3s; }
    @keyframes ppDot { 0%, 80%, 100% { opacity: .25; transform: scale(.8); } 40% { opacity: 1; transform: none; } }
    .pp-verdict { display: inline-flex; align-items: center; gap: .35rem; padding: .25rem .65rem; border-radius: 999px; font-size: .74rem; font-weight: 800; }
    .pp-verdict.is-ok { color: #1f5c12; background: #e1f0cc; } .pp-verdict.is-caution { color: #8a5a00; background: #fff0c2; } .pp-verdict.is-stop { color: #9a1d13; background: #fde3df; }
    .pp-rows { display: grid; gap: .4rem; margin-top: .6rem; }
    .pp-row { padding: .55rem .7rem; border-radius: .85rem; border: 1px solid var(--color-gray-100); background: #fff; font-size: .8rem; line-height: 1.45; color: var(--color-gray-700); border-left-width: 4px; }
    .pp-row b { display: block; font-size: .8rem; color: var(--color-gray-900); }
    .pp-row.is-high { border-left-color: #dc2626; } .pp-row.is-medium { border-left-color: #f59e0b; } .pp-row.is-low { border-left-color: #84cc16; } .pp-row.is-skip { border-left-color: #94a3b8; }
    .pp-h3 { margin: 1rem 0 .1rem; font-size: .78rem; font-weight: 800; letter-spacing: .04em; text-transform: uppercase; color: var(--color-gray-500); }
    .pp-done { display: grid; justify-items: center; gap: .5rem; padding: 1.4rem .5rem .6rem; text-align: center; }
    .pp-done .ok { width: 3.2rem; height: 3.2rem; border-radius: 999px; display: grid; place-items: center; color: #fff; background: var(--pp-g); animation: ppPopIn .4s var(--pp-e); }
    .pp-done .ok svg { width: 1.6rem; height: 1.6rem; }
    @keyframes ppPopIn { from { transform: scale(.4); opacity: 0; } }
    .pp-done b { font-size: 1.05rem; color: #14210c; }
    .pp-done p { font-size: .84rem; line-height: 1.5; color: var(--color-gray-600); max-width: 24rem; }
    .pp-input { width: 100%; }
    html.dark .pp-box { background: #11160d; }
    html.dark .pp-head h2, html.dark .pp-hero b, html.dark .pp-wait b, html.dark .pp-done b { color: #e8efe1; }
    html.dark .pp-x { background: #1d2616; color: #cfe3bd; }
    html.dark .pp-choice, html.dark .pp-chip, html.dark .pp-row { background: #151b12; border-color: #2b3a1c; }
    html.dark .pp-choice.is-on { background: #1d2a14; border-color: #6b9f3d; }
    html.dark .pp-chip.is-on { background: #3f5626; border-color: #6b9f3d; color: #e8efe1; }
    html.dark .pp-choice .ct b, html.dark .pp-lot-h b, html.dark .pp-row b, html.dark .pp-say b, html.dark .pp-fact b { color: #e8efe1; }
    html.dark .pp-lot, html.dark .pp-fact { background: #151b12; border-color: #2b3a1c; }
    html.dark .pp-say, html.dark .pp-fact, html.dark .pp-row { color: #b7c9a3; }
    html.dark .pp-hero { background: #1a2414; border-color: #2f4d24; }
    html.dark .pp-hero.is-stop { background: rgb(180 35 24 / .14); border-color: rgb(243 165 156 / .28); }
    html.dark .pp-hero.is-stop b { color: #ffd3cc; }
    html.dark .pp-price { background: rgb(180 83 9 / .14); border-color: rgb(180 83 9 / .35); }
    html.dark .pp-price b, html.dark .pp-price .pp-amt { color: #fcd34d; } html.dark .pp-price small { color: #eec155; }
    html.dark .pp-choice .ce { background: #1d2616; border-color: #2b3a1c; }
    @media (prefers-reduced-motion: reduce) {
        .pp-box, .pp::backdrop, .pp-pane, .pp-wait img, .pp-dots i, .pp-done .ok { animation: none !important; }
        .pp-choice, .pp-chip, .pp-fold, .pp-x { transition: none; }
    }
</style>

<dialog class="pp" id="ppSeason" aria-labelledby="ppSeasonT">
    <div class="pp-box" tabindex="-1">
        <div class="pp-grip" aria-hidden="true"></div>
        <div class="pp-head"><h2 id="ppSeasonT">Put it on a cropping schedule<small id="ppSeasonS"></small></h2>
            <button type="button" class="pp-x" data-pp-close aria-label="Close"><svg fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24"><path stroke-linecap="round" d="M6 6l12 12M18 6L6 18"/></svg></button></div>
        <div class="pp-body">
            {{-- Where: a new season, or one you have. --}}
            <div class="pp-pane" data-pane="where">
                <div class="pp-choices">
                    <button type="button" class="pp-choice is-on" data-where="new"><span class="ce">✨</span><span class="ct"><b>A new cropping schedule</b><small>Made out of this plan, with its lots</small></span><span class="ck"><svg fill="none" stroke="currentColor" stroke-width="3.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg></span></button>
                    <div class="pp-fold is-open" id="ppNewFold"><div><label class="pp-label" for="ppName">Its name</label><input type="text" id="ppName" class="form-input pp-input" maxlength="255"></div></div>
                    <button type="button" class="pp-choice" data-where="old"><span class="ce">📅</span><span class="ct"><b>One you already have</b><small>Onto its lots that are not harvested yet</small></span><span class="ck"><svg fill="none" stroke="currentColor" stroke-width="3.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg></span></button>
                    <div class="pp-fold" id="ppOldFold"><div><div class="pp-list" id="ppSeasons" role="listbox" aria-label="Your seasons"></div></div></div>
                </div>
                <p class="pp-say" id="ppPrev" style="margin-top:.8rem" hidden></p>
            </div>
            {{-- Which lot each plan lot goes onto. --}}
            <div class="pp-pane" data-pane="lots" hidden>
                <p class="pp-say">Pick the lot of <b id="ppLotsSeason"></b> each lot of the plan goes onto. Harvested lots are done for the season.</p>
                <div id="ppLots" style="margin-top:.75rem"></div>
            </div>
            {{-- It cannot go in any more. --}}
            <div class="pp-pane" data-pane="past" hidden>
                <div class="pp-hero is-stop"><span class="pp-stop-ico">⛔</span><span><b>This plan can no longer be added</b><small id="ppPastS"></small></span></div>
                <div class="pp-rows" id="ppPastRows"></div>
            </div>
            {{-- Anee checks it first. --}}
            <div class="pp-pane" data-pane="offer" hidden>
                <div class="pp-hero"><img src="{{ $ppFace }}" alt=""><span><b>This season already has work on it</b><small>Adding the plan can clash with what is there: the same work twice, your crew on a busy day, another lot's work. Anee checks it against the board first, then places it.</small></span></div>
                <div class="pp-facts" id="ppOfferFacts"></div>
                <div class="pp-price"><span><b>Anee's check</b><small id="ppBal"></small></span><span class="pp-amt" id="ppPrice"></span></div>
            </div>
            <div class="pp-pane" data-pane="wait" hidden>
                <div class="pp-wait"><img src="{{ $ppFace }}" alt=""><b id="ppWaitT">Anee is reading the board</b><div class="pp-dots" aria-hidden="true"><i></i><i></i><i></i></div><small id="ppWaitS">The other lots, your crew and what is already there. This takes about a minute.</small></div>
            </div>
            {{-- Her reading. --}}
            <div class="pp-pane" data-pane="result" hidden>
                <div class="pp-hero" id="ppResHero"><img src="{{ $ppFace }}" alt=""><span><span class="pp-verdict" id="ppVerdict"></span><b id="ppHeadline" style="margin-top:.35rem"></b><small id="ppSummary"></small></span></div>
                <div id="ppResRows"></div>
            </div>
            {{-- How it is placed. --}}
            <div class="pp-pane" data-pane="place" hidden>
                <p class="pp-say">How should the work go on the board?</p>
                <div class="pp-choices" style="margin-top:.7rem">
                    <button type="button" class="pp-choice is-on" data-place="crew"><span class="ce">👷</span><span class="ct"><b>By your crew's free days</b><small id="ppCrewS"></small></span><span class="ck"><svg fill="none" stroke="currentColor" stroke-width="3.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg></span></button>
                    <button type="button" class="pp-choice" data-place="anee"><span class="ce"><img src="{{ $ppFace }}" alt="" style="width:1.8rem;height:1.8rem;border-radius:999px;object-fit:cover"></span><span class="ct"><b>Let Anee place it</b><small id="ppAneeS"></small></span><span class="ck"><svg fill="none" stroke="currentColor" stroke-width="3.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg></span></button>
                </div>
                <div id="ppPlaceRows"></div>
            </div>
            <div class="pp-pane" data-pane="done" hidden>
                <div class="pp-done"><div class="ok"><svg fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg></div><b id="ppDoneT">It is on the board</b><p id="ppDoneP"></p></div>
            </div>
        </div>
        <div class="pp-foot">
            <button type="button" class="btn btn-white" id="ppBack" hidden>Back</button>
            <button type="button" class="btn btn-white" data-pp-close id="ppCancel">Cancel</button>
            <button type="button" class="btn btn-primary" id="ppGo">Make the season</button>
            <a class="btn btn-primary" id="ppOpen" href="#" hidden>Open the season</a>
        </div>
    </div>
</dialog>

<dialog class="pp" id="ppBuilder" aria-labelledby="ppBuilderT">
    <div class="pp-box" tabindex="-1">
        <div class="pp-grip" aria-hidden="true"></div>
        <div class="pp-head"><h2 id="ppBuilderT">Send to Protocol Builder<small>Every step lands on the protocol's own day count, in the order of its days</small></h2>
            <button type="button" class="pp-x" data-pp-close aria-label="Close"><svg fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24"><path stroke-linecap="round" d="M6 6l12 12M18 6L6 18"/></svg></button></div>
        <div class="pp-body">
            <div class="pp-pane" data-pane="form">
                <div id="ppBLotWrap" hidden><p class="pp-label">Which lot's program?</p><div class="pp-chips" id="ppBLots"></div></div>
                <p class="pp-label">Where?</p>
                <div class="pp-choices">
                    <button type="button" class="pp-choice is-on" data-bmode="new"><span class="ce">✨</span><span class="ct"><b>A new protocol</b><small>Counted the way this crop is planted</small></span><span class="ck"><svg fill="none" stroke="currentColor" stroke-width="3.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg></span></button>
                    <div class="pp-fold is-open" id="ppBNewFold"><div><label class="pp-label" for="ppBName">Its name</label><input type="text" id="ppBName" class="form-input pp-input" maxlength="190"></div></div>
                    <button type="button" class="pp-choice" data-bmode="existing"><span class="ce">📋</span><span class="ct"><b>Add to one of mine</b><small>Each step on its day among the protocol's own, undoable there</small></span><span class="ck"><svg fill="none" stroke="currentColor" stroke-width="3.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg></span></button>
                    <div class="pp-fold" id="ppBOldFold"><div><div class="pp-list" id="ppBList" role="listbox" aria-label="Your protocols"></div></div></div>
                </div>
            </div>
            <div class="pp-pane" data-pane="done" hidden>
                <div class="pp-done"><div class="ok"><svg fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg></div><b id="ppBDoneT">Sent</b><p id="ppBDoneP"></p></div>
            </div>
        </div>
        <div class="pp-foot">
            <button type="button" class="btn btn-white" data-pp-close id="ppBCancel">Cancel</button>
            <button type="button" class="btn btn-primary" id="ppBGo">Send</button>
            <a class="btn btn-primary" id="ppBOpen" href="#" hidden>Open it</a>
        </div>
    </div>
</dialog>

<script>
(() => {
    const $ = (id) => document.getElementById(id);
    const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const U = {
        targets: @json(route('planport.targets')), protocols: @json(route('planport.protocols')), season: @json(route('planport.season')),
        preview: @json(route('planport.preview')), check: @json(route('planport.check')), job: (id) => @json(url('/app/plan-port/job')) + '/' + id,
        apply: @json(route('planport.apply')), builder: @json(route('planport.builder')), hub: (id) => @json(url('/app/sm-hub')) + '?id=' + id,
    };
    const day = (d) => { try { return new Date(d + 'T00:00:00').toLocaleDateString('en-PH', { weekday: 'short', month: 'short', day: 'numeric' }); } catch (_) { return d; } };
    const api = (url, opts) => window.api(url, opts);
    const toast = (m, k) => window.toast?.(m, k);

    /* ---- the dialogs open and close on the house curve ---- */
    function open(d) { if (!d.open) d.showModal(); d.classList.remove('is-closing'); setTimeout(() => d.querySelector('.pp-box')?.focus(), 60); }
    function close(d) {
        if (!d.open) return;
        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) { d.close(); return; }
        d.classList.add('is-closing');
        setTimeout(() => { d.close(); d.classList.remove('is-closing'); }, 230);
    }
    document.querySelectorAll('.pp').forEach((d) => {
        d.addEventListener('click', (e) => { if (e.target === d || e.target.closest('[data-pp-close]')) { e.preventDefault(); close(d); } });
        d.addEventListener('cancel', (e) => { e.preventDefault(); close(d); });
    });
    const fold = (el, on) => el.classList.toggle('is-open', on);

    /* =============================== into a season =============================== */
    const S = { opts: null, where: 'new', seasons: null, season: null, map: {}, pane: 'where', preview: null, port: null, place: 'crew', busy: false };
    const D = $('ppSeason');
    function pane(name) {
        S.pane = name;
        D.querySelectorAll('[data-pane]').forEach((p) => { p.hidden = p.dataset.pane !== name; });
        const go = $('ppGo'), back = $('ppBack'), cancel = $('ppCancel'), openA = $('ppOpen');
        openA.hidden = name !== 'done';
        go.hidden = ['past', 'wait', 'done'].includes(name) || (name === 'result' && S.port && S.port.result.verdict === 'stop');
        back.hidden = !['lots', 'offer'].includes(name);
        cancel.textContent = ['past', 'done'].includes(name) || (name === 'result' && S.port && S.port.result.verdict === 'stop') ? 'Close' : 'Cancel';
        cancel.hidden = name === 'wait';
        go.classList.toggle('pp-cta', name === 'offer');
        go.classList.toggle('btn-primary', name !== 'offer');
        go.textContent = { where: S.where === 'new' ? 'Make the season' : 'Next', lots: 'Continue', offer: S.preview && S.preview.prior ? "See Anee's check" : 'Check with Anee · ' + (S.preview ? S.preview.price : '') + ' credits', result: 'Continue', place: 'Put it on the board' }[name] || 'Continue';
        D.querySelector('.pp-body').scrollTop = 0;
    }
    function paintWhere() {
        D.querySelectorAll('[data-where]').forEach((b) => b.classList.toggle('is-on', b.dataset.where === S.where));
        fold($('ppNewFold'), S.where === 'new');
        fold($('ppOldFold'), S.where === 'old');
        pane('where');
    }
    async function loadSeasons() {
        $('ppSeasons').innerHTML = '<p class="pp-none">Loading your seasons…</p>';
        try { S.seasons = (await api(U.targets)).data.seasons || []; } catch (err) { S.seasons = []; $('ppSeasons').innerHTML = '<p class="pp-none">' + esc(err.message) + '</p>'; return; }
        paintSeasons();
    }
    function paintSeasons() {
        const rows = S.seasons || [];
        $('ppSeasons').innerHTML = rows.length ? rows.map((s) => {
            const open = s.lots.filter((l) => !l.harvested).length;
            return '<button type="button" class="pp-choice' + (S.season && S.season.id === s.id ? ' is-on' : '') + '" data-season="' + s.id + '" role="option"><span class="ce">🌾</span><span class="ct"><b>' + esc(s.title) + '</b><small>'
                + esc([s.crop, s.work ? s.work + (s.work === 1 ? ' activity' : ' activities') + ' on its board' : 'Nothing on its board yet', s.lots.length ? open + ' of ' + s.lots.length + (s.lots.length === 1 ? ' lot' : ' lots') + ' open' : 'No lots yet'].filter(Boolean).join(' · '))
                + '</small></span><span class="ck"><svg fill="none" stroke="currentColor" stroke-width="3.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg></span></button>';
        }).join('') : '<p class="pp-none">You have no open seasons. Make a new one instead.</p>';
    }
    /* Each plan lot's best guess: the season's lot of the same name, else one of the same crop with no planting day, else a new lot. */
    function guessMap() {
        const used = new Set();
        S.map = {};
        (S.opts.lots || []).forEach((pl) => {
            const lots = S.season.lots.filter((l) => !l.harvested && !used.has(l.id));
            const name = (pl.name || '').trim().toLowerCase();
            const hit = lots.find((l) => l.name.trim().toLowerCase() === name) || lots.find((l) => pl.crop && l.crop && l.crop.split('_')[0] === String(pl.crop).split('_')[0] && !l.planting);
            if (hit) used.add(hit.id);
            S.map[pl.i] = hit ? hit.id : 'new';
        });
    }
    function paintLots() {
        $('ppLotsSeason').textContent = S.season.title;
        $('ppLots').innerHTML = (S.opts.lots || []).map((pl) => '<div class="pp-lot"><div class="pp-lot-h"><b>' + esc(pl.name) + '</b><small>' + esc([pl.cropLabel, pl.planting ? 'planting ' + day(pl.planting) : ''].filter(Boolean).join(' · ')) + '</small></div><div class="pp-chips">'
            + S.season.lots.map((l) => '<button type="button" class="pp-chip' + (S.map[pl.i] === l.id ? ' is-on' : '') + '" data-lot="' + pl.i + '" data-to="' + l.id + '"' + (l.harvested ? ' disabled title="Harvested"' : '') + '>' + esc(l.name)
                + (l.cropLabel ? ' <small>· ' + esc(l.cropLabel) + '</small>' : '') + (l.harvested ? ' <small>· harvested</small>' : '') + '</button>').join('')
            + '<button type="button" class="pp-chip' + (S.map[pl.i] === 'new' ? ' is-on' : '') + '" data-lot="' + pl.i + '" data-to="new">+ A new lot</button>'
            + ((S.opts.lots || []).length > 1 ? '<button type="button" class="pp-chip' + (S.map[pl.i] === 'skip' ? ' is-on' : '') + '" data-lot="' + pl.i + '" data-to="skip">Leave out</button>' : '')
            + '</div></div>').join('');
    }
    D.addEventListener('click', (e) => {
        const w = e.target.closest('[data-where]');
        if (w) { S.where = w.dataset.where; paintWhere(); if (S.where === 'old' && !S.seasons) loadSeasons(); return; }
        const s = e.target.closest('[data-season]');
        if (s) { S.season = (S.seasons || []).find((x) => String(x.id) === s.dataset.season); paintSeasons(); return; }
        const c = e.target.closest('[data-lot]');
        if (c && !c.disabled) { S.map[c.dataset.lot] = c.dataset.to === 'new' || c.dataset.to === 'skip' ? c.dataset.to : Number(c.dataset.to); paintLots(); return; }
        if (e.target.closest('[data-pp-recheck]')) { S.preview.prior = null; e.target.closest('.pp-fact')?.remove(); pane('offer'); return; }
        const p = e.target.closest('[data-place]');
        if (p) { S.place = p.dataset.place; D.querySelectorAll('[data-place]').forEach((b) => b.classList.toggle('is-on', b === p)); paintPlaceRows(); }
    });
    $('ppBack').addEventListener('click', () => { if (S.pane === 'lots') pane('where'); else if (S.pane === 'offer') { paintLots(); pane('lots'); } });

    const body = () => ({ source: S.opts.source, id: S.opts.id, scheduleId: S.season ? S.season.id : null, map: S.map });
    async function makeNew() {
        const name = $('ppName').value.trim();
        if (!name) { toast('Give the season a name.', 'error'); $('ppName').focus(); return; }
        const res = await api(U.season, { method: 'POST', body: { source: S.opts.source, id: S.opts.id, title: name } });
        done('The season is made', res.message, res.data.url, 'Open the season', res.data.season);
    }
    async function preview() {
        const res = await api(U.preview, { method: 'POST', body: body() });
        S.preview = res.data;
        const over = res.data.over || [];
        if (res.data.state === 'past') {
            $('ppPastS').textContent = 'Its part of ' + S.season.title + ' is already over.';
            $('ppPastRows').innerHTML = over.map((o) => '<div class="pp-row is-high"><b>' + esc(o.name) + '</b>' + esc(o.why) + '</div>').join('');
            pane('past');
            return;
        }
        if (res.data.state === 'direct') { await apply(null, 'plan'); return; }
        const lots = res.data.lots || [];
        const facts = lots.map((l) => ['🌱', '<b>' + esc(l.name) + '</b> onto ' + esc(l.into) + ': ' + l.tasks + (l.tasks === 1 ? ' step' : ' steps') + (l.past ? ', ' + l.past + ' past ' + (l.past === 1 ? 'one' : 'ones') + ' left out' : '')
            + (l.moved ? '. Moved ' + Math.abs(l.moved) + (Math.abs(l.moved) === 1 ? ' day ' : ' days ') + (l.moved > 0 ? 'later' : 'earlier') + ' to meet its planting day, ' + esc(day(l.anchor)) : '') + '.']);
        over.forEach((o) => facts.push(['⛔', '<b>' + esc(o.name) + '</b>: ' + esc(o.why) + ' It is left out.']));
        facts.push(['📋', '<b>' + res.data.board + (res.data.board === 1 ? ' activity' : ' activities') + '</b> already on the board around those days, on every lot.']);
        if (res.data.prior) facts.push(['✅', '<b>Anee already checked this</b> on ' + esc(res.data.prior.at) + '. See her check again for free, or <button type="button" class="underline font-bold" data-pp-recheck>check again</button> if the board has changed.']);
        $('ppOfferFacts').innerHTML = facts.map(([i, t]) => '<div class="pp-fact"><span>' + i + '</span><span>' + t + '</span></div>').join('');
        $('ppPrice').textContent = res.data.price + ' credits';
        $('ppBal').textContent = res.data.unlimited ? 'Your credits are unlimited.' : 'You have ' + Math.floor(res.data.balance).toLocaleString() + ' credits.' + (res.data.balance < res.data.price ? ' Not enough yet.' : '');
        pane('offer');
    }
    let pollT = null;
    async function check() {
        if (S.preview.locked) { window.aneeUpgrade?.('Anee\'s check comes with a plan with Anee.', 'libreAnee'); return; }
        if (!S.preview.aiUsable) { toast('Anee is not available right now.', 'error'); return; }
        // The wait shows at once: on a server that cannot answer before the work is done, the request itself is the wait.
        pane('wait');
        let res;
        try { res = await api(U.check, { method: 'POST', body: Object.assign(body(), { quote: S.preview.quote }) }); }
        catch (err) { pane('offer'); throw err; }
        if (res.data.status === 'ready' && res.data.result) { S.port = { id: res.data.id, result: res.data.result, credits: res.data.credits }; paintResult(); return; }
        poll(res.data.id);
    }
    const WAIT = { read: ['Anee is reading the board', 'The other lots, your crew and what is already there.'], think: ['Anee is checking the plan', 'The same work twice, a busy crew, the order of the work.'], place: ['Anee is placing the work', 'Each step on a day that clashes with nothing.'] };
    async function poll(id) {
        clearTimeout(pollT);
        try {
            const res = await api(U.job(id));
            if (res.data.pending) {
                const w = WAIT[(res.data.phase || {}).phase] || WAIT.read;
                $('ppWaitT').textContent = w[0]; $('ppWaitS').textContent = w[1] + ' This takes about a minute.';
                pollT = setTimeout(() => poll(id), 2500);
                return;
            }
            S.port = { id, result: res.data.result, credits: res.data.credits };
            paintResult();
        } catch (err) { toast(err.message, 'error'); pane('offer'); }
    }
    function paintResult() {
        const r = S.port.result;
        const v = { ok: ['is-ok', '✓ Fits as planned'], caution: ['is-caution', '⚠ Fits, with care'], stop: ['is-stop', '⛔ Better not'] }[r.verdict] || ['is-caution', 'Checked'];
        $('ppVerdict').className = 'pp-verdict ' + v[0]; $('ppVerdict').textContent = v[1];
        $('ppResHero').classList.toggle('is-stop', r.verdict === 'stop');
        $('ppHeadline').textContent = r.headline || ''; $('ppSummary').textContent = r.summary || '';
        let html = '';
        if ((r.conflicts || []).length) html += '<p class="pp-h3">What she found</p><div class="pp-rows">' + r.conflicts.map((c) => '<div class="pp-row is-' + esc(c.severity) + '"><b>' + esc(c.task || ({ crew: 'The crew', lots: 'Other lots', crop: 'The crop', order: 'The order of work', spacing: 'Too close together', timing: 'Timing', duplicate: 'Already there' }[c.kind] || 'Note')) + '</b>' + esc(c.text) + '</div>').join('') + '</div>';
        if ((r.skipped || []).length) html += '<p class="pp-h3">Already on the board, left out</p><div class="pp-rows">' + r.skipped.map((s) => '<div class="pp-row is-skip">' + esc(s) + '</div>').join('') + '</div>';
        if ((r.tips || []).length) html += '<p class="pp-h3">Tips</p><div class="pp-rows">' + r.tips.map((t) => '<div class="pp-row is-low">' + esc(t) + '</div>').join('') + '</div>';
        $('ppResRows').innerHTML = html;
        pane('result');
    }
    function paintPlace() {
        const r = S.port.result, crew = r.crew || {};
        $('ppCrewS').textContent = crew.workers ? 'Each step keeps its day while a worker is free that day, after the other lots\' work. ' + (crew.moved ? crew.moved + (crew.moved === 1 ? ' step moves' : ' steps move') + ' to the next free day.' : 'Nothing has to move.')
            + ((crew.short || []).length ? ' ' + crew.short.length + ' still find no free worker within two weeks.' : '')
            : 'No workers are listed on this season, so every step keeps the plan\'s day.';
        $('ppAneeS').textContent = 'Her days, set around your other lots so nothing clashes. ' + (r.aneeMoved ? r.aneeMoved + (r.aneeMoved === 1 ? ' step moves.' : ' steps move.') : 'Nothing has to move.');
        D.querySelectorAll('[data-place]').forEach((b) => b.classList.toggle('is-on', b.dataset.place === S.place));
        paintPlaceRows();
        pane('place');
    }
    function paintPlaceRows() {
        const r = S.port.result;
        const rows = S.place === 'crew' ? (r.crew.short || []).map((s) => '<div class="pp-row is-medium"><b>No free worker yet</b>' + esc(s) + ' keeps its day. Add a worker or move it on the board.</div>').join('') : '';
        $('ppPlaceRows').innerHTML = rows ? '<div class="pp-rows" style="margin-top:.8rem">' + rows + '</div>' : '';
    }
    async function apply(portId, placement) {
        const res = await api(U.apply, { method: 'POST', body: portId ? { portId, placement } : Object.assign(body(), { placement }) });
        done('It is on the board', res.message, res.data.url, 'Open the board', res.data.season);
    }
    function done(title, text, url, label, season) {
        $('ppDoneT').textContent = title; $('ppDoneP').textContent = text;
        $('ppOpen').href = url; $('ppOpen').textContent = label;
        pane('done');
        if (S.opts.onDone) S.opts.onDone(season);
    }
    $('ppGo').addEventListener('click', async () => {
        if (S.busy) return;
        const go = $('ppGo');
        S.busy = true; go.disabled = true;
        try {
            if (S.pane === 'where') {
                if (S.where === 'new') await makeNew();
                else if (!S.season) toast('Pick the season to add it to.', 'error');
                else { guessMap(); paintLots(); pane('lots'); }
            } else if (S.pane === 'lots') {
                if (!Object.values(S.map).some((v) => v !== 'skip')) toast('Pick where at least one lot goes.', 'error');
                else {
                    // A lot that grows another crop is the farmer's call, asked once before anything else.
                    const fam = (c) => String(c || '').split('_')[0];
                    const odd = (S.opts.lots || []).map((pl) => [pl, S.season.lots.find((l) => l.id === S.map[pl.i])]).filter(([pl, l]) => l && l.crop && pl.crop && fam(l.crop) !== fam(pl.crop));
                    const go2 = !odd.length || !window.confirmAction || await window.confirmAction({
                        title: 'Another crop on that lot',
                        message: odd.map(([pl, l]) => l.name + ' grows ' + (l.cropLabel || l.crop) + ', and the plan for ' + pl.name + ' is for ' + (pl.cropLabel || pl.crop) + '.').join(' ') + ' Put it there anyway?',
                        confirmText: 'Put it there', confirmClass: 'btn-primary',
                    });
                    if (go2) await preview();
                }
            } else if (S.pane === 'offer') {
                if (S.preview.prior) { pane('wait'); await poll(S.preview.prior.id); } else await check();
            }
            else if (S.pane === 'result') paintPlace();
            else if (S.pane === 'place') await apply(S.port.id, S.place);
        } catch (err) { if (!err || !err.tierLock) toast(err.message, 'error'); }
        finally { S.busy = false; go.disabled = false; }
    });
    D.addEventListener('close', () => clearTimeout(pollT));

    /* =============================== the builder =============================== */
    const B = { opts: null, mode: 'new', pid: null, rows: null, lot: null };
    const BD = $('ppBuilder');
    function paintB() {
        BD.querySelectorAll('[data-bmode]').forEach((b) => b.classList.toggle('is-on', b.dataset.bmode === B.mode));
        fold($('ppBNewFold'), B.mode === 'new');
        fold($('ppBOldFold'), B.mode === 'existing');
        const lots = B.opts.lots || [];
        $('ppBLotWrap').hidden = lots.length < 2;
        $('ppBLots').innerHTML = lots.map((l) => '<button type="button" class="pp-chip' + (B.lot === l.i ? ' is-on' : '') + '" data-blot="' + l.i + '">' + esc(l.name) + (l.cropLabel ? ' <small>· ' + esc(l.cropLabel) + '</small>' : '') + '</button>').join('');
        const crop = (lots.find((l) => l.i === B.lot) || lots[0] || {}).crop;
        const rows = (B.rows || []).slice().sort((a, b) => ((b.crop === crop) - (a.crop === crop)));
        $('ppBList').innerHTML = B.rows === null ? '<p class="pp-none">Loading your protocols…</p>' : rows.length ? rows.map((p) => '<button type="button" class="pp-choice' + (B.pid === p.id ? ' is-on' : '') + '" data-pid="' + p.id + '" role="option"><span class="ce">' + esc(p.cropIcon || '🌱') + '</span><span class="ct"><b>' + esc(p.title) + '</b><small>'
            + esc([p.cropLabel, p.dayType === 'TREE' ? 'DOS' : p.dayType, 'updated ' + p.updated].filter(Boolean).join(' · ')) + '</small></span><span class="ck"><svg fill="none" stroke="currentColor" stroke-width="3.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg></span></button>').join('')
            : '<p class="pp-none">You have no protocols yet. Make a new one.</p>';
    }
    function paneB(name) { BD.querySelectorAll('[data-pane]').forEach((p) => { p.hidden = p.dataset.pane !== name; }); $('ppBGo').hidden = name === 'done'; $('ppBOpen').hidden = name !== 'done'; $('ppBCancel').textContent = name === 'done' ? 'Close' : 'Cancel'; }
    BD.addEventListener('click', (e) => {
        const m = e.target.closest('[data-bmode]');
        if (m) { B.mode = m.dataset.bmode; paintB(); return; }
        const p = e.target.closest('[data-pid]');
        if (p) { B.pid = Number(p.dataset.pid); paintB(); return; }
        const l = e.target.closest('[data-blot]');
        if (l) { B.lot = Number(l.dataset.blot); paintB(); }
    });
    $('ppBGo').addEventListener('click', async () => {
        if (B.mode === 'existing' && !B.pid) { toast('Pick the protocol to add the steps to.', 'error'); return; }
        $('ppBGo').disabled = true;
        try {
            const res = await api(U.builder, { method: 'POST', body: { source: B.opts.source, id: B.opts.id, mode: B.mode, protocolId: B.pid, title: $('ppBName').value.trim(), lot: B.lot } });
            $('ppBDoneT').textContent = res.data.added ? 'Sent' : 'Already there';
            $('ppBDoneP').textContent = res.message;
            $('ppBOpen').href = res.data.url;
            paneB('done');
        } catch (err) { toast(err.message, 'error'); }
        finally { $('ppBGo').disabled = false; }
    });

    window.planPort = {
        season(opts) {
            Object.assign(S, { opts, where: 'new', season: null, map: {}, preview: null, port: null, place: 'crew', busy: false });
            S.seasons = null;
            $('ppSeasonS').textContent = opts.title || '';
            $('ppName').value = opts.title || '';
            const prev = opts.prev;
            $('ppPrev').hidden = !prev;
            if (prev) $('ppPrev').innerHTML = 'You put this plan on <a class="font-bold underline" href="' + esc(U.hub(prev.id)) + '">' + esc(prev.title) + '</a> on ' + esc(prev.at) + '. Doing it again adds it again.';
            paintWhere();
            open(D);
        },
        async builder(opts) {
            Object.assign(B, { opts, mode: 'new', pid: null, rows: null, lot: (opts.lots && opts.lots[0]) ? opts.lots[0].i : null });
            $('ppBName').value = (opts.source === 'landprep' ? 'Land prep: ' : 'Protocol: ') + (opts.title || '');
            paneB('form'); paintB();
            open(BD);
            try { B.rows = (await api(U.protocols)).data.rows || []; } catch (_) { B.rows = []; }
            paintB();
        },
    };
})();
</script>
@endonce
