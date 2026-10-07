@extends('layouts.app')
@section('title', 'NPK Plus Calculator')
@section('page-title', 'NPK Plus')
@section('page-subtitle', 'Every nutrient in your fertilizer plan')
@section('back', \App\Support\BackTo::url(route('app.dashboard')))

@section('content')
<style>
    /* ---- NPK PLUS (2026-10-07) -------------------------------------------
       A calculator that answers as you type: the shelf of products as tags,
       the lines you build, the totals beside them. The house curve on every
       change, held still under reduced motion. */
    :root { --np-ease: cubic-bezier(.22,1,.36,1); }
    .np-wrap { max-width: 72rem; margin: 0 auto; }
    .np-hero { position: relative; overflow: hidden; border-radius: 1.2rem; padding: 1.1rem 1.2rem; margin-bottom: 1rem; color: #3b2a00;
        background: radial-gradient(120% 140% at 100% 0%, #fde68a 0%, #f5c518 45%, #d4a106 100%); }
    .np-hero h2 { font-family: var(--font-heading); font-weight: 800; font-size: 1.12rem; color: #1f1500; }
    .np-hero p { margin-top: .3rem; font-size: .84rem; line-height: 1.55; max-width: 38rem; color: #4a3600; }
    .np-hero .np-chem { position: absolute; right: 1rem; top: 50%; transform: translateY(-50%); display: flex; gap: .35rem; opacity: .9; }
    .np-hero .np-chem span { display: grid; place-items: center; width: 2.6rem; height: 2.6rem; border-radius: .7rem; font-family: var(--font-heading); font-weight: 800; color: #fff;
        background: rgb(31 21 0 / .78); box-shadow: 0 8px 20px -10px rgb(0 0 0 / .6); animation: npBob 4s ease-in-out infinite; }
    .np-hero .np-chem span:nth-child(2) { animation-delay: .4s; } .np-hero .np-chem span:nth-child(3) { animation-delay: .8s; }
    @keyframes npBob { 50% { transform: translateY(-4px); } }
    @media (max-width: 639.98px) { .np-hero .np-chem { display: none; } }
    .np-tabs { display: flex; gap: .4rem; margin-bottom: 1rem; }
    .np-tab { flex: 1 1 0; padding: .6rem; border-radius: .8rem; font-weight: 800; font-size: .9rem; text-align: center; color: var(--color-gray-500); background: var(--color-white);
        border: 1px solid var(--color-gray-200); cursor: pointer; transition: background-color .28s var(--np-ease), color .28s var(--np-ease); }
    .np-tab.is-on { background: var(--color-brand-600); border-color: var(--color-brand-600); color: #fff; }
    .np-grid { display: grid; gap: 1rem; grid-template-columns: minmax(0, 1fr); align-items: start; }
    @media (min-width: 1024px) { .np-grid { grid-template-columns: minmax(0, 1.15fr) minmax(0, .85fr); } .np-out { position: sticky; top: 5.5rem; } }
    .np-card { border-radius: 1.1rem; background: var(--color-white); border: 1px solid var(--color-gray-200); padding: 1rem 1.05rem; min-width: 0; }
    .np-card + .np-card { margin-top: 1rem; }
    .np-card h3 { display: flex; align-items: center; gap: .5rem; font-family: var(--font-heading); font-weight: 800; font-size: 1rem; color: var(--color-gray-900); }
    .np-card h3 i { display: grid; place-items: center; flex: none; width: 1.6rem; height: 1.6rem; border-radius: .55rem; font-style: normal; font-size: .78rem; font-weight: 800;
        color: #fff; background: var(--color-brand-600); }
    .np-sub { margin-top: .25rem; font-size: .8rem; line-height: 1.5; color: var(--color-gray-500); }
    .np-label { display: block; margin: .8rem 0 .35rem; font-size: .76rem; font-weight: 800; color: var(--color-gray-700); }
    .np-row { display: flex; gap: .5rem; } .np-row > * { min-width: 0; }
    .np-two { display: grid; gap: 0 .8rem; grid-template-columns: minmax(0, 1fr); }
    @media (min-width: 480px) { .np-two { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
    .np-pills { display: flex; flex-wrap: wrap; gap: .35rem; }
    .np-pill { display: inline-flex; align-items: center; gap: .3rem; padding: .42rem .72rem; border-radius: 999px; font-size: .8rem; font-weight: 700; cursor: pointer;
        color: var(--color-gray-700); background: var(--color-white); border: 1px solid var(--color-gray-200);
        transition: background-color .28s var(--np-ease), border-color .28s var(--np-ease), color .28s var(--np-ease), transform .28s var(--np-ease); }
    .np-pill:hover { border-color: var(--color-brand-600); }
    .np-pill[aria-pressed="true"] { background: var(--color-brand-600); border-color: var(--color-brand-600); color: #fff; }
    .np-pill small { font-weight: 800; opacity: .7; }
    .np-crops { max-height: 9.5rem; overflow-y: auto; margin-top: .5rem; padding: .1rem; }
    .np-cats { display: flex; gap: .3rem; overflow-x: auto; scrollbar-width: none; margin-top: .7rem; padding-bottom: .1rem; }
    .np-cats::-webkit-scrollbar { display: none; }
    @media (min-width: 640px) { .np-cats { flex-wrap: wrap; overflow: visible; } }
    .np-cat { flex: none; padding: .38rem .7rem; border-radius: 999px; font-size: .74rem; font-weight: 800; color: var(--color-gray-600); background: var(--color-gray-100); border: 0; cursor: pointer;
        transition: background-color .28s var(--np-ease), color .28s var(--np-ease); }
    .np-cat.is-on { background: #1f2937; color: #fff; }
    html.dark .np-cat { background: #1c2616; } html.dark .np-cat.is-on { background: #e8efe1; color: #151b12; }
    .np-shelf { display: flex; flex-wrap: wrap; gap: .4rem; margin-top: .7rem; min-height: 2.5rem; }
    .np-tag { display: inline-flex; align-items: center; gap: .4rem; padding: .45rem .7rem; border-radius: .8rem; font-size: .8rem; font-weight: 700; cursor: pointer; text-align: left;
        color: var(--color-gray-800); background: var(--color-gray-50); border: 1px dashed var(--color-gray-300, #cbd5e1);
        transition: border-color .28s var(--np-ease), background-color .28s var(--np-ease), transform .28s var(--np-ease); animation: npIn .3s var(--np-ease) both; }
    .np-tag:hover { border-style: solid; border-color: var(--color-brand-600); transform: translateY(-1px); }
    .np-tag b { font-size: .7rem; font-weight: 800; padding: .1rem .4rem; border-radius: 999px; color: #2d5016; background: #e4efd4; }
    .np-tag.is-in { border-style: solid; border-color: var(--color-brand-600); background: #f3f8ec; }
    html.dark .np-tag { background: #121a0d; } html.dark .np-tag.is-in { background: #1c2c10; }
    .np-tag svg { width: .85rem; height: .85rem; color: var(--color-brand-600); }
    @keyframes npIn { from { opacity: 0; transform: translateY(4px); } }
    .np-lines { display: grid; gap: .5rem; margin-top: .8rem; }
    .np-line { display: grid; grid-template-columns: minmax(0, 1fr) auto; gap: .5rem .7rem; align-items: center; padding: .7rem .75rem; border-radius: .9rem;
        background: var(--color-gray-50); border: 1px solid var(--color-gray-100); animation: npIn .3s var(--np-ease) both; }
    html.dark .np-line { background: #121a0d; border-color: #2b3a1c; }
    .np-line b { display: block; font-size: .86rem; color: var(--color-gray-900); }
    .np-line small { display: block; font-size: .72rem; color: var(--color-gray-500); }
    .np-amt { display: flex; gap: .35rem; align-items: center; }
    .np-amt input { width: 5.5rem; } .np-amt select { width: auto; }
    .np-x { width: 1.9rem; height: 1.9rem; border-radius: 999px; display: grid; place-items: center; border: 1px solid var(--color-gray-200); background: var(--color-white); color: var(--color-gray-500); cursor: pointer; }
    .np-empty { padding: 1rem; border-radius: .9rem; text-align: center; font-size: .84rem; color: var(--color-gray-500); border: 1px dashed var(--color-gray-200); }
    .np-custom { display: none; margin-top: .8rem; padding: .8rem; border-radius: .9rem; background: var(--color-gray-50); border: 1px solid var(--color-gray-200); }
    .np-custom.is-on { display: block; animation: npIn .3s var(--np-ease) both; }
    html.dark .np-custom { background: #121a0d; border-color: #2b3a1c; }
    .np-cgrid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: .45rem; margin-top: .5rem; }
    @media (min-width: 640px) { .np-cgrid { grid-template-columns: repeat(5, minmax(0, 1fr)); } }
    .np-cgrid label { font-size: .7rem; font-weight: 800; color: var(--color-gray-600); }
    .np-cgrid input { margin-top: .15rem; }
    .np-soil { display: none; } .np-soil.is-on { display: block; animation: npIn .3s var(--np-ease) both; }
    .np-switch { display: flex; align-items: center; gap: .6rem; cursor: pointer; font-size: .84rem; font-weight: 700; color: var(--color-gray-700); }
    .np-switch input { width: 1.1rem; height: 1.1rem; accent-color: var(--color-brand-600); }

    .np-big { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: .5rem; margin-top: .8rem; }
    .np-big div { border-radius: .95rem; padding: .7rem .5rem; text-align: center; color: #fff; }
    .np-big div:nth-child(1) { background: linear-gradient(135deg, #2d5016, #4a7c2a); }
    .np-big div:nth-child(2) { background: linear-gradient(135deg, #9a3412, #ea580c); }
    .np-big div:nth-child(3) { background: linear-gradient(135deg, #5b21b6, #8b5cf6); }
    .np-big small { display: block; font-size: .66rem; font-weight: 800; letter-spacing: .06em; text-transform: uppercase; opacity: .85; }
    .np-big b { display: block; font-family: var(--font-heading); font-size: 1.45rem; font-weight: 800; line-height: 1.15; transition: transform .28s var(--np-ease); }
    .np-big i { display: block; font-style: normal; font-size: .68rem; opacity: .85; }
    .np-table { width: 100%; margin-top: .8rem; font-size: .8rem; border-collapse: collapse; }
    .np-table th, .np-table td { padding: .45rem .35rem; border-bottom: 1px solid var(--color-gray-100); text-align: right; color: var(--color-gray-700); }
    .np-table th:first-child, .np-table td:first-child { text-align: left; }
    .np-table th { font-size: .68rem; text-transform: uppercase; letter-spacing: .04em; color: var(--color-gray-500); }
    .np-table tr.is-sec td { color: var(--color-gray-600); }
    .np-need { display: grid; gap: .6rem; margin-top: .6rem; }
    .np-need-row { display: grid; grid-template-columns: 3.3rem minmax(0, 1fr) 4.3rem; gap: .5rem; align-items: center; font-size: .76rem; font-weight: 800; color: var(--color-gray-700); }
    .np-need-bar { position: relative; height: .7rem; border-radius: 999px; background: var(--color-gray-100); overflow: visible; }
    .np-need-bar .band { position: absolute; top: 0; bottom: 0; border-radius: 999px; background: rgb(102 189 99 / .28); }
    .np-need-bar .have { position: absolute; left: 0; top: .15rem; bottom: .15rem; border-radius: 999px; background: #4a7c2a; transition: width .5s var(--np-ease); }
    .np-need-row.is-short .have { background: #f59e0b; } .np-need-row.is-over .have { background: #dc2626; }
    .np-need-row em { font-style: normal; text-align: right; font-size: .7rem; }
    .np-need-row.is-short em { color: #b45309; } .np-need-row.is-over em { color: #b91c1c; } .np-need-row.is-right em { color: #2d5016; }
    .np-say { margin-top: .8rem; padding: .75rem .85rem; border-radius: .9rem; font-size: .84rem; line-height: 1.55; color: #24400f; background: #f3f8ec; border: 1px solid #c9e0ad; }
    html.dark .np-say { color: #d5e3c5; background: #17220f; border-color: #2b3a1c; }
    .np-src { margin-top: .7rem; font-size: .76rem; line-height: 1.55; color: var(--color-gray-600); }
    .np-src summary { cursor: pointer; font-weight: 800; color: var(--color-brand-700); }
    .np-src p, .np-src ul { margin-top: .4rem; }
    .np-src ul { padding-left: 1.1rem; list-style: disc; }
    .np-src a { color: var(--color-brand-700); text-decoration: underline; overflow-wrap: anywhere; }
    .np-fine { margin-top: .8rem; font-size: .72rem; line-height: 1.55; color: var(--color-gray-500); }
    .np-acts { display: grid; gap: .5rem; margin-top: .9rem; }
    .np-anee { display: flex; align-items: center; justify-content: center; gap: .5rem; padding: .85rem 1rem; border-radius: .95rem; border: 0; cursor: pointer; font-weight: 800;
        color: #1a1a1a; background: linear-gradient(135deg, #f7d23a, #f5c518); transition: transform .28s var(--np-ease); }
    .np-anee:hover { transform: translateY(-1px); } .np-anee:disabled { opacity: .5; transform: none; cursor: default; }
    .np-anee img { width: 1.6rem; height: 1.6rem; border-radius: 999px; }
    .np-mbar { position: fixed; left: 0; right: 0; bottom: calc(4.2rem + env(safe-area-inset-bottom)); z-index: 30; display: flex; gap: .5rem; align-items: center; justify-content: space-between;
        margin: 0 .8rem; padding: .6rem .8rem; border-radius: 1rem; color: #fff; background: rgb(21 33 12 / .94); box-shadow: 0 14px 30px -16px rgb(0 0 0 / .8);
        transform: translateY(150%); transition: transform .32s var(--np-ease); }
    .np-mbar.is-on { transform: none; }
    .np-mbar b { font-family: var(--font-heading); font-size: 1rem; }
    .np-mbar button { padding: .45rem .8rem; border-radius: 999px; border: 0; font-size: .76rem; font-weight: 800; color: #1a1a1a; background: #f5c518; cursor: pointer; }
    @media (min-width: 1024px) { .np-mbar { display: none; } }
    .np-sheet { position: fixed; inset: 0; z-index: 95; display: grid; place-items: end center; background: rgb(0 0 0 / .45); opacity: 0; pointer-events: none; transition: opacity .28s var(--np-ease); }
    .np-sheet.is-on { opacity: 1; pointer-events: auto; }
    .np-sheet-in { width: min(36rem, 100%); max-height: 92vh; overflow-y: auto; padding: 1.1rem 1.1rem calc(1.2rem + env(safe-area-inset-bottom)); border-radius: 1.3rem 1.3rem 0 0; background: var(--color-white);
        transform: translateY(30px); transition: transform .32s var(--np-ease); }
    .np-sheet.is-on .np-sheet-in { transform: none; }
    @media (min-width: 640px) { .np-sheet { place-items: center; } .np-sheet-in { border-radius: 1.3rem; } }
    .np-rep { margin-top: 1rem; }
    .np-rep[hidden] { display: none; }
    .np-rhead { border-radius: 1.1rem; padding: 1rem 1.1rem; color: #fff; background: radial-gradient(120% 140% at 100% 0%, #4a7c2a 0%, #14250a 65%); }
    .np-rhead.v-needs-changes, .np-rhead.v-unbalanced { background: radial-gradient(120% 140% at 100% 0%, #b45309 0%, #3d1d02 65%); }
    .np-rhead small { font-size: .7rem; font-weight: 800; letter-spacing: .06em; text-transform: uppercase; opacity: .85; }
    .np-rhead h3 { margin-top: .2rem; font-family: var(--font-heading); font-weight: 800; font-size: 1.12rem; color: #fff; }
    .np-rhead p { margin-top: .35rem; font-size: .84rem; line-height: 1.55; opacity: .92; }
    .np-rlist { display: grid; gap: .45rem; margin-top: .5rem; }
    .np-ritem { padding: .6rem .7rem; border-radius: .8rem; background: var(--color-gray-50); border: 1px solid var(--color-gray-100); font-size: .84rem; color: var(--color-gray-700); line-height: 1.5; }
    html.dark .np-ritem { background: #121a0d; border-color: #2b3a1c; }
    .np-ritem b { color: var(--color-gray-900); }
    .np-st { display: inline-block; margin-right: .35rem; font-size: .64rem; font-weight: 800; text-transform: uppercase; padding: .12rem .45rem; border-radius: 999px; background: #e4efd4; color: #2d5016; }
    .np-st.s-short { background: #fff1c2; color: #8a5a00; } .np-st.s-over { background: #fde2e1; color: #b42318; }
    .np-saved { display: grid; gap: .5rem; }
    .np-srow { display: flex; gap: .7rem; align-items: center; width: 100%; padding: .7rem .8rem; border-radius: .95rem; border: 1px solid var(--color-gray-200); background: var(--color-white);
        text-align: left; cursor: pointer; transition: transform .28s var(--np-ease); }
    .np-srow:hover { transform: translateY(-1px); }
    .np-srow span { min-width: 0; flex: 1 1 auto; }
    .np-srow b { display: block; font-size: .86rem; color: var(--color-gray-900); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .np-srow small { font-size: .74rem; color: var(--color-gray-500); }
    .np-srow em { flex: none; font-style: normal; font-size: .7rem; font-weight: 800; padding: .2rem .5rem; border-radius: 999px; background: #fef3c7; color: #8a5a00; }
    @media (prefers-reduced-motion: reduce) {
        .np-hero .np-chem span, .np-tag, .np-line, .np-custom.is-on, .np-soil.is-on { animation: none; }
        .np-mbar, .np-sheet, .np-sheet-in, .np-need-bar .have, .np-pill, .np-tag { transition: none; }
    }
</style>

<div class="np-wrap">
    <div class="np-hero">
        <div class="np-chem" aria-hidden="true"><span>N</span><span>P</span><span>K</span></div>
        <h2>Know exactly what your fertilizer gives your crop</h2>
        <p>Pick your crop and the products you plan to use. NPK Plus adds up every nutrient as the element and as the oxide, checks it against what the crop needs, and shows the yield it can feed. Free, and as many times as you like.</p>
    </div>

    <div class="np-tabs" role="tablist">
        <button type="button" class="np-tab is-on" id="npTabCalc" role="tab">Calculator</button>
        <button type="button" class="np-tab" id="npTabSaved" role="tab">Saved</button>
    </div>

    <div id="npCalc">
        <div class="np-grid">
            <div>
                <div class="np-card">
                    <h3><i>1</i>Crop and area</h3>
                    <input type="search" id="npCropQ" class="form-input mt-3" placeholder="Find a crop" autocomplete="off">
                    <div class="np-pills np-crops" id="npCrops"></div>
                    <div class="np-two mt-3">
                        <div><label class="np-label" for="npArea">Area</label>
                            <div class="np-row"><input type="number" id="npArea" class="form-input flex-1" min="0" step="any" value="1" inputmode="decimal">
                                <select id="npAreaUnit" class="form-select" style="width:5.6rem;flex:none"><option value="ha">ha</option><option value="sqm">m²</option></select></div></div>
                        <div><label class="np-label" for="npTarget">Target yield, t/ha <span class="text-gray-400 font-normal">(optional)</span></label>
                            <input type="number" id="npTarget" class="form-input w-full" min="0" step="any" inputmode="decimal" placeholder="Like 6"></div>
                    </div>
                </div>

                <div class="np-card">
                    <h3><i>2</i>Your fertilizers</h3>
                    <p class="np-sub">Tap a product to add it, then type how much you will use for the whole area. Not on the list? Add it from its label.</p>
                    <div class="np-cats" id="npCats"></div>
                    <div class="np-shelf" id="npShelf"></div>
                    <button type="button" class="np-pill mt-3" id="npCustomBtn">+ A product not on the list</button>
                    <div class="np-custom" id="npCustom">
                        <b class="text-sm">What the label says</b>
                        <div class="np-row mt-2"><input type="text" id="npCName" class="form-input flex-1" maxlength="120" placeholder="Product name">
                            <select id="npCForm" class="form-select" style="max-width:8rem"><option value="granular">Granular</option><option value="powder">Powder</option><option value="liquid">Liquid</option></select></div>
                        <div class="np-cgrid" id="npCGrid"></div>
                        <p class="np-sub">Percent by weight, as printed. Leave the rest empty.</p>
                        <div class="np-row mt-2"><button type="button" class="btn btn-primary flex-1" id="npCSave">Save and add</button><button type="button" class="btn btn-white" id="npCCancel">Cancel</button></div>
                    </div>
                    <div class="np-lines" id="npLines"></div>
                </div>

                <div class="np-card">
                    <label class="np-switch"><input type="checkbox" id="npSoilOn"> <span><b>I have a soil test</b><br><span class="text-xs text-gray-500 font-normal">The result decides whether your soil needs the low or the high end of the rate.</span></span></label>
                    <div class="np-soil" id="npSoil">
                        <div class="np-cgrid" id="npSoilGrid"></div>
                        <div class="np-row mt-2">
                            <label class="text-xs font-bold text-gray-600 flex items-center gap-2">P method <select id="npPMethod" class="form-select" style="width:auto"><option value="olsen">Olsen</option><option value="bray">Bray</option></select></label>
                            <label class="text-xs font-bold text-gray-600 flex items-center gap-2">K in <select id="npKUnit" class="form-select" style="width:auto"><option value="ppm">ppm</option><option value="meq">meq/100 g</option></select></label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="np-out">
                <div class="np-card" id="npOut">
                    <h3><i>3</i>What your plan gives</h3>
                    <p class="np-sub" id="npOutSub">Add a fertilizer to see the totals.</p>
                    <div class="np-big"><div><small>N</small><b id="npN">0</b><i>kg per ha</i></div><div><small>P₂O₅</small><b id="npP">0</b><i>kg per ha</i></div><div><small>K₂O</small><b id="npK">0</b><i>kg per ha</i></div></div>
                    <div id="npTable"></div>
                    <div id="npNeed"></div>
                    <div id="npSay"></div>
                    <p class="np-fine">A guide, not a promise. The crop's real need and the yield it gives depend on the weather, the variety, the soil type and how and when the fertilizer goes on. Biofertilizer values are field trial estimates that vary a lot. A soil test makes this surer.</p>
                    <div class="np-acts">
                        <button type="button" class="btn btn-white" id="npSave">Save this calculation</button>
                        <button type="button" class="np-anee" id="npAnee"><img src="{{ \App\Models\AiSetting::current()->faceUrl() }}" alt="">Analyze further with Anee</button>
                    </div>
                </div>
                <div class="np-rep" id="npRep" hidden></div>
            </div>
        </div>
        <div class="np-mbar" id="npMbar"><span>N-P-K per ha <b id="npMbarV">0-0-0</b></span><button type="button" id="npMbarGo">See the totals</button></div>
    </div>

    <div id="npSavedPane" hidden>
        <input type="search" id="npSavedQ" class="form-input mb-3" placeholder="Search your calculations" autocomplete="off">
        <div class="np-saved" id="npSaved"></div>
        <p class="text-center text-sm text-gray-400 py-8" id="npSavedEmpty" hidden>Saved calculations land here.</p>
        <button type="button" class="btn btn-white w-full mt-3" id="npSavedMore" hidden>Show more</button>
    </div>
</div>

<div class="np-sheet" id="npSheet" aria-hidden="true">
    <div class="np-sheet-in" role="dialog" aria-modal="true" aria-labelledby="npSheetH">
        <div class="flex items-center justify-between"><h3 class="font-heading font-extrabold text-lg" id="npSheetH">Tell Anee about the field</h3><button type="button" class="np-x" id="npSheetX" aria-label="Close">✕</button></div>
        <p class="np-sub">Anee checks your plan against the place, the soil, the water, the season and ENSO, then says what to keep, cut, add or split, and when.</p>
        <label class="np-label" for="npLoc">Where is the field?</label>
        <input type="text" id="npLoc" class="form-input" maxlength="160" placeholder="Town and province">
        <label class="np-label">The soil</label>
        <div class="np-pills" id="npSoilCond"></div>
        <label class="np-label" for="npPh">Soil pH <span class="text-gray-400 font-normal">(optional)</span></label>
        <input type="number" id="npPh" class="form-input" min="2" max="12" step="0.1" inputmode="decimal">
        <label class="np-label">Water</label>
        <div class="np-pills" id="npWater"></div>
        <div class="np-row">
            <div class="flex-1"><label class="np-label" for="npPlant">Planting date</label><input type="date" id="npPlant" class="form-input"></div>
            <div class="flex-1"><label class="np-label" for="npVariety">Variety <span class="text-gray-400 font-normal">(optional)</span></label><input type="text" id="npVariety" class="form-input" maxlength="80"></div>
        </div>
        <label class="np-label">How will you apply it?</label>
        <div class="np-pills" id="npTiming"></div>
        <label class="np-label" for="npNotes">Anything else <span class="text-gray-400 font-normal">(optional)</span></label>
        <textarea id="npNotes" class="form-textarea" rows="2" maxlength="800" placeholder="Like: the field floods in August"></textarea>
        <button type="button" class="np-anee w-full mt-4" id="npRun"><span id="npRunSays">Ask Anee</span></button>
        <p class="np-fine text-center" id="npRunFine"></p>
    </div>
</div>
@include('sm.partials.anee-wait')
@endsection

@push('scripts')
<script>
(() => {
    const $ = (id) => document.getElementById(id);
    const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const U = {
        options: @json(route('npk.options')), product: @json(route('npk.product')), productDel: (id) => @json(url('/app/npk-plus/product')) + '/' + id,
        save: @json(route('npk.save')), list: @json(route('npk.list')), one: (id) => @json(url('/app/npk-plus/one')) + '/' + id,
        analyze: @json(route('npk.analyze')), job: (id) => @json(url('/app/npk-plus/job')) + '/' + id,
    };
    const NUTRIENTS = ['N', 'P2O5', 'K2O', 'Ca', 'Mg', 'S', 'Zn', 'B', 'Fe', 'Mn', 'Cu', 'Mo', 'Si', 'Cl'];
    // Element and oxide: what each label nutrient is as the other.
    const AS = { N: { el: 'N', f: 1 }, P2O5: { el: 'P', ox: 'P₂O₅', f: 0.4364 }, K2O: { el: 'K', ox: 'K₂O', f: 0.8301 }, Ca: { el: 'Ca', ox: 'CaO', g: 1.3992 }, Mg: { el: 'Mg', ox: 'MgO', g: 1.6583 }, S: { el: 'S', ox: 'SO₄', g: 2.9959 } };
    const MICRO = ['Zn', 'B', 'Fe', 'Mn', 'Cu', 'Mo'];
    const NAMES = { N: 'Nitrogen', P2O5: 'Phosphorus', K2O: 'Potassium', Ca: 'Calcium', Mg: 'Magnesium', S: 'Sulfur', Zn: 'Zinc', B: 'Boron', Fe: 'Iron', Mn: 'Manganese', Cu: 'Copper', Mo: 'Molybdenum', Si: 'Silicon', Cl: 'Chloride' };
    let OPT = null, cat = 'complete', calcId = null;
    const st = { crop: null, lines: [] };
    const lab = (n) => n === 'P2O5' ? 'P₂O₅' : n === 'K2O' ? 'K₂O' : n;
    const fmt = (v, dp = 1) => (Math.round(v * 10 ** dp) / 10 ** dp).toLocaleString(undefined, { maximumFractionDigits: dp });

    /* ---- crop ---- */
    const crops = () => {
        const q = $('npCropQ').value.trim().toLowerCase();
        const list = OPT.crops.filter((c) => !q || c.label.toLowerCase().includes(q));
        $('npCrops').innerHTML = list.map((c) => '<button type="button" class="np-pill" data-k="' + esc(c.key) + '" aria-pressed="' + (st.crop === c.key) + '">' + esc(c.icon) + ' ' + esc(c.label.split(' (')[0].replace(' — ', ', ')) + '</button>').join('')
            || '<p class="np-sub">No crop data for that yet. Pick the closest one, or leave it blank to see the totals only.</p>';
    };
    $('npCropQ').addEventListener('input', crops);
    $('npCrops').addEventListener('click', (e) => { const b = e.target.closest('.np-pill'); if (!b) return; st.crop = st.crop === b.dataset.k ? null : b.dataset.k; crops(); calc(); });
    ['npArea', 'npAreaUnit', 'npTarget'].forEach((id) => ['input', 'change'].forEach((ev) => $(id).addEventListener(ev, () => calc())));

    /* ---- the shelf and the lines ---- */
    const grade = (p) => p.local && /^\d/.test(p.local) ? p.local : (p.pct.N || p.pct.P2O5 || p.pct.K2O ? [p.pct.N || 0, p.pct.P2O5 || 0, p.pct.K2O || 0].join('-') : '');
    const shelf = () => {
        const list = OPT.products.filter((p) => p.category === cat);
        $('npShelf').innerHTML = list.map((p) => {
            const g = grade(p);
            const tip = Object.entries(p.pct).map(([k, v]) => k + ' ' + v + '%').join(', ') || (p.estimate ? 'estimate per dose: ' + Object.entries(p.estimate).map(([k, v]) => k + ' ' + v + ' kg').join(', ') : '');
            return '<button type="button" class="np-tag' + (st.lines.some((l) => l.id === p.id) ? ' is-in' : '') + '" data-id="' + p.id + '" title="' + esc(tip) + '">'
                + '<svg fill="none" stroke="currentColor" stroke-width="2.6" viewBox="0 0 24 24"><path stroke-linecap="round" d="M12 5v14M5 12h14"/></svg>' + esc(p.name) + (g ? ' <b>' + esc(g) + '</b>' : '') + '</button>';
        }).join('') || '<p class="np-sub">Nothing here yet. Add your own from its label.</p>';
    };
    $('npCats').addEventListener('click', (e) => { const b = e.target.closest('.np-cat'); if (!b) return; cat = b.dataset.c; document.querySelectorAll('.np-cat').forEach((x) => x.classList.toggle('is-on', x === b)); shelf(); });
    $('npShelf').addEventListener('click', (e) => {
        const b = e.target.closest('.np-tag');
        if (!b) return;
        const p = OPT.products.find((x) => String(x.id) === b.dataset.id);
        if (!p) return;
        st.lines.push({ id: p.id, amount: p.unit === 'dose' ? 1 : (p.bagKg ? 1 : 10), unit: p.unit === 'dose' ? 'dose' : (p.unit === 'L' ? 'L' : (p.bagKg ? 'bag' : 'kg')) });
        lines(); shelf(); calc();
        const last = $('npLines').lastElementChild?.querySelector('input');
        last?.focus(); last?.select();
    });
    const unitOpts = (p, u) => {
        const opts = p.unit === 'dose' ? [['dose', 'ha doses']] : (p.unit === 'L' ? [['L', 'liters']] : [['kg', 'kg'], ['bag', 'bags (' + (p.bagKg || 50) + ' kg)'], ['g', 'grams']]);
        return opts.map(([v, l]) => '<option value="' + v + '"' + (v === u ? ' selected' : '') + '>' + esc(l) + '</option>').join('');
    };
    const lines = () => {
        $('npLines').innerHTML = st.lines.length ? st.lines.map((l, i) => {
            const p = OPT.products.find((x) => x.id === l.id);
            if (!p) return '';
            const comp = Object.entries(p.pct).map(([k, v]) => lab(k) + ' ' + v + '%').join(' · ') || (p.estimate ? 'About ' + Object.entries(p.estimate).map(([k, v]) => v + ' kg ' + k).join(', ') + ' per hectare dose (estimate)' : '');
            return '<div class="np-line" data-i="' + i + '"><div><b>' + esc(p.name) + (grade(p) ? ' ' + esc(grade(p)) : '') + '</b><small>' + esc(comp) + '</small></div>'
                + '<button type="button" class="np-x" data-rm="' + i + '" aria-label="Remove">✕</button>'
                + '<div class="np-amt"><input type="number" class="form-input" min="0" step="any" inputmode="decimal" value="' + esc(l.amount) + '" data-amt="' + i + '"><select class="form-select" data-unit="' + i + '">' + unitOpts(p, l.unit) + '</select></div></div>';
        }).join('') : '<div class="np-empty">No fertilizer yet. Tap one above.</div>';
    };
    $('npLines').addEventListener('input', (e) => {
        const a = e.target.dataset.amt, u = e.target.dataset.unit;
        if (a != null) st.lines[a].amount = Number(e.target.value) || 0;
        if (u != null) st.lines[u].unit = e.target.value;
        calc();
    });
    $('npLines').addEventListener('click', (e) => { const r = e.target.closest('[data-rm]'); if (r) { st.lines.splice(Number(r.dataset.rm), 1); lines(); shelf(); calc(); } });

    /* ---- a product from its label ---- */
    $('npCGrid').innerHTML = NUTRIENTS.filter((n) => n !== 'Cl').map((n) => '<label>' + (n === 'P2O5' ? 'P₂O₅' : n === 'K2O' ? 'K₂O' : n) + ' %<input type="number" class="form-input" min="0" max="100" step="any" data-n="' + n + '"></label>').join('');
    $('npCustomBtn').addEventListener('click', () => $('npCustom').classList.toggle('is-on'));
    $('npCCancel').addEventListener('click', () => $('npCustom').classList.remove('is-on'));
    $('npCSave').addEventListener('click', async () => {
        const body = { name: $('npCName').value.trim(), form: $('npCForm').value };
        $('npCGrid').querySelectorAll('input').forEach((i) => { if (i.value) body[i.dataset.n] = Number(i.value); });
        if (!body.name) return window.toast?.('Give the product a name.', 'error');
        try {
            const r = await window.api(U.product, { method: 'POST', body });
            OPT.products.push(r.data.product);
            st.lines.push({ id: r.data.product.id, amount: 1, unit: r.data.product.unit === 'L' ? 'L' : 'bag' });
            $('npCustom').classList.remove('is-on'); $('npCName').value = ''; $('npCGrid').querySelectorAll('input').forEach((i) => { i.value = ''; });
            cat = 'mine'; document.querySelectorAll('.np-cat').forEach((x) => x.classList.toggle('is-on', x.dataset.c === 'mine'));
            lines(); shelf(); calc();
            window.toast?.(r.message, 'success');
        } catch (err) { window.toast?.(err.message, 'error'); }
    });

    /* ---- the soil test ---- */
    const SOIL = [['ph', 'pH'], ['om', 'Organic matter %'], ['n', 'Total N %'], ['p', 'P ppm'], ['k', 'K'], ['zn', 'Zn ppm'], ['b', 'B ppm'], ['s', 'S ppm']];
    $('npSoilGrid').innerHTML = SOIL.map(([k, l]) => '<label>' + esc(l) + '<input type="number" class="form-input" step="any" min="0" data-s="' + k + '"></label>').join('');
    $('npSoilOn').addEventListener('change', () => { $('npSoil').classList.toggle('is-on', $('npSoilOn').checked); calc(); });
    $('npSoil').addEventListener('input', () => calc());
    $('npSoil').addEventListener('change', () => calc());
    const soil = () => {
        if (!$('npSoilOn').checked) return null;
        const s = {};
        $('npSoilGrid').querySelectorAll('input').forEach((i) => { if (i.value !== '') s[i.dataset.s] = Number(i.value); });
        s.pMethod = $('npPMethod').value; s.kUnit = $('npKUnit').value;
        return Object.keys(s).length > 2 ? s : null;
    };
    // Low, medium or high, from the usual soil test bands.
    const soilClass = (s) => {
        if (!s) return {};
        const out = {};
        if (s.p != null) out.P2O5 = s.pMethod === 'bray' ? (s.p < 15 ? 'low' : s.p <= 30 ? 'medium' : 'high') : (s.p < 10 ? 'low' : s.p <= 20 ? 'medium' : 'high');
        if (s.k != null) { const meq = s.kUnit === 'meq' ? s.k : s.k / 391; out.K2O = meq < 0.2 ? 'low' : meq <= 0.4 ? 'medium' : 'high'; }
        if (s.om != null) out.N = s.om < 2 ? 'low' : s.om <= 4 ? 'medium' : 'high';
        else if (s.n != null) out.N = s.n < 0.1 ? 'low' : s.n <= 0.2 ? 'medium' : 'high';
        if (s.zn != null) out.Zn = s.zn < 0.8 ? 'low' : 'ok';
        if (s.b != null) out.B = s.b < 0.5 ? 'low' : 'ok';
        return out;
    };

    /* ---- the arithmetic ---- */
    let RES = null;
    const calc = () => {
        const areaRaw = Number($('npArea').value) || 0;
        const area = $('npAreaUnit').value === 'sqm' ? areaRaw / 10000 : areaRaw;
        const tot = Object.fromEntries(NUTRIENTS.map((n) => [n, 0]));
        let estimated = false;
        const out = st.lines.map((l) => {
            const p = OPT.products.find((x) => x.id === l.id);
            if (!p) return null;
            let kg = 0;
            if (l.unit === 'bag') kg = l.amount * (p.bagKg || 50);
            else if (l.unit === 'g') kg = l.amount / 1000;
            else if (l.unit === 'L') kg = l.amount * (p.density || 1);
            else if (l.unit === 'kg') kg = l.amount;
            Object.entries(p.pct).forEach(([n, v]) => { tot[n] += kg * v / 100; });
            if (p.estimate && l.unit === 'dose') { estimated = true; Object.entries(p.estimate).forEach(([n, v]) => { tot[n] += v * l.amount * Math.max(area, 0); }); }
            return { id: p.id, name: p.name, grade: grade(p), amount: l.amount, unit: l.unit, kg: Math.round(kg * 100) / 100, pct: p.pct, estimate: p.estimate };
        }).filter(Boolean);
        const perHa = Object.fromEntries(NUTRIENTS.map((n) => [n, area > 0 ? tot[n] / area : 0]));
        $('npN').textContent = fmt(perHa.N, 0); $('npP').textContent = fmt(perHa.P2O5, 0); $('npK').textContent = fmt(perHa.K2O, 0);
        const npk = fmt(perHa.N, 0) + '-' + fmt(perHa.P2O5, 0) + '-' + fmt(perHa.K2O, 0);
        $('npMbarV').textContent = npk;
        $('npMbar').classList.toggle('is-on', out.length > 0);
        $('npOutSub').textContent = out.length ? 'For ' + fmt(area, 3) + ' ha. Per hectare and for the whole area, as the element and as the oxide.' + (estimated ? ' Includes biofertilizer estimates.' : '') : 'Add a fertilizer to see the totals.';

        // The table: N, P, K always; secondary and micronutrients only when present.
        const rows = [];
        ['N', 'P2O5', 'K2O'].forEach((n) => {
            const a = AS[n];
            const el = n === 'N' ? tot.N : tot[n] * a.f;
            rows.push('<tr><td><b>' + NAMES[n] + '</b></td><td>' + fmt(el) + ' kg ' + a.el + '</td><td>' + (a.ox ? fmt(tot[n]) + ' kg ' + a.ox : '—') + '</td><td>' + fmt(area > 0 ? el / area : 0) + ' kg</td></tr>');
        });
        ['Ca', 'Mg', 'S'].filter((n) => tot[n] > 0).forEach((n) => rows.push('<tr class="is-sec"><td>' + NAMES[n] + '</td><td>' + fmt(tot[n]) + ' kg ' + n + '</td><td>' + fmt(tot[n] * AS[n].g) + ' kg ' + AS[n].ox + '</td><td>' + fmt(area > 0 ? tot[n] / area : 0) + ' kg</td></tr>'));
        MICRO.concat(['Si']).filter((n) => tot[n] > 0).forEach((n) => rows.push('<tr class="is-sec"><td>' + NAMES[n] + '</td><td>' + fmt(tot[n] * 1000, 0) + ' g ' + n + '</td><td>—</td><td>' + fmt(area > 0 ? tot[n] * 1000 / area : 0, 0) + ' g</td></tr>'));
        $('npTable').innerHTML = out.length ? '<table class="np-table"><tr><th>Nutrient</th><th>Element, total</th><th>Oxide, total</th><th>Element per ha</th></tr>' + rows.join('') + '</table>' : '';

        // Against the crop.
        const crop = OPT.crops.find((c) => c.key === st.crop);
        const cls = soilClass(soil());
        let needHtml = '', say = '';
        const support = {}, needs = {};
        if (crop && out.length) {
            const name = esc(crop.label.split(' (')[0].replace(' — ', ', '));
            const rec = crop.recommendedPerHa || {};
            const bars = ['N', 'P2O5', 'K2O'].filter((n) => Array.isArray(rec[n])).map((n) => {
                let [lo, hi] = rec[n];
                if (cls[n] === 'low') lo = (lo + hi) / 2; else if (cls[n] === 'high') hi = (lo + hi) / 2;
                const have = perHa[n];
                const state = have < lo * 0.9 ? 'short' : have > hi * 1.15 ? 'over' : 'right';
                needs[n] = { usual: [Math.round(lo), Math.round(hi)], plan: Math.round(have), state };
                const scale = Math.max(hi * 1.5, have * 1.05, 1);
                return '<div class="np-need-row is-' + state + '" title="Usual rate ' + fmt(lo, 0) + (hi !== lo ? ' to ' + fmt(hi, 0) : '') + ' kg per ha"><span>' + lab(n) + '</span><span class="np-need-bar"><span class="band" style="left:' + (lo / scale * 100) + '%;width:' + (Math.max(hi - lo, scale * 0.015) / scale * 100) + '%"></span><span class="have" style="width:' + Math.min(100, have / scale * 100) + '%"></span></span><em>' + (state === 'short' ? 'Short' : state === 'over' ? 'Too much' : 'Right') + '</em></div>';
            }).join('');
            needHtml = bars ? '<h4 class="np-label">Against the usual rate for ' + name + ' (green band)' + (crop.treesPerHa ? ', at ' + crop.treesPerHa + ' trees per hectare' : '') + (Object.keys(cls).length ? ', adjusted by your soil test' : '') + '</h4><div class="np-need">' + bars + '</div>' : '';
            // What the fertilizer alone can grow: each nutrient, times the share
            // the crop recovers, over what a ton of harvest takes up.
            const up = crop.uptakePerTon || {};
            const eff = Object.assign({ N: 0.4, P2O5: 0.2, K2O: 0.5 }, crop.efficiency || {});
            const use = ['N', 'P2O5', 'K2O'].filter((n) => up[n] > 0 && !(n === 'N' && crop.legume));
            use.forEach((n) => { support[n] = perHa[n] * eff[n] / up[n]; });
            const ty = crop.typicalYield || null;
            const unit = ty ? esc(ty.unit) : 't/ha';
            const tyWords = ty ? ' Typical yields in the Philippines (national average): ' + fmt(ty.lo) + ' to ' + fmt(ty.hi) + ' ' + unit + '.' : '';
            if (use.length && !crop.removalOnly) {
                const lim = use.reduce((a, b) => support[a] <= support[b] ? a : b);
                const target = Number($('npTarget').value) || 0;
                const parts = use.map((n) => '<b>' + fmt(support[n]) + ' t/ha</b> (' + NAMES[n].toLowerCase() + ')');
                say = '<div class="np-say">From this fertilizer alone, the crop can take up enough to grow about '
                    + (parts.length > 1 ? parts.slice(0, -1).join(', ') + ' and ' + parts[parts.length - 1] : parts[0]) + '. '
                    + (use.length > 1 ? NAMES[lim] + ' runs out first. ' : '')
                    + 'Your soil gives its own share on top of this, often most of the harvest, so the real yield is higher; a soil test tells how much.' + tyWords
                    + (crop.legume ? ' A legume makes most of its own nitrogen from the air, so nitrogen is left out of this count.' : '')
                    + '</div>';
                if (target > 0) {
                    say += '<div class="np-say">For <b>' + fmt(target) + ' ' + unit + '</b> the crop takes up about '
                        + use.map((n) => fmt(target * up[n], 0) + ' kg ' + lab(n)).join(', ') + ' per hectare in all. This plan gives it about '
                        + use.map((n) => fmt(perHa[n] * eff[n], 0) + ' kg ' + lab(n)).join(', ') + ' of that; the rest has to come from the soil.</div>';
                }
            } else if (crop.removalOnly) {
                say = '<div class="np-say">For ' + name + ' the published numbers only count what the harvest carries away, not what the plant needs to grow, so NPK Plus does not turn this plan into a yield. Use the usual rate above as the guide.' + tyWords + '</div>';
            } else if (tyWords) {
                say = '<div class="np-say">' + tyWords.trim() + '</div>';
            }
            const src = (crop.sources || []).map((s) => '<li><a href="' + esc(s.url) + '" target="_blank" rel="noopener">' + esc(s.label) + '</a></li>').join('');
            if (crop.about || src) {
                say += '<details class="np-src"><summary>Where these numbers come from</summary>' + (crop.proxy ? '<p><b>Close stand in:</b> ' + esc(crop.proxy) + '.</p>' : '')
                    + (crop.about ? '<p>' + esc(crop.about) + '</p>' : '') + (src ? '<ul>' + src + '</ul>' : '')
                    + '<p>Where a source gives no recovery rate, NPK Plus assumes the crop recovers 40% of the nitrogen, 20% of the phosphorus and 50% of the potassium in the first season.</p></details>';
            }
        } else if (out.length && !crop) {
            say = '<div class="np-say">Pick the crop to see if this is enough and what yield it can feed.</div>';
        }
        if (cls.Zn === 'low' && !(tot.Zn > 0)) say += '<div class="np-say">Your soil test reads low in zinc and the plan has none. Zinc sulfate is the usual fix.</div>';
        if (cls.B === 'low' && !(tot.B > 0)) say += '<div class="np-say">Your soil test reads low in boron and the plan has none.</div>';
        $('npNeed').innerHTML = needHtml;
        $('npSay').innerHTML = say;
        RES = { perHa: Object.fromEntries(Object.entries(perHa).filter(([, v]) => v > 0).map(([k, v]) => [k, Math.round(v * 100) / 100])), total: Object.fromEntries(Object.entries(tot).filter(([, v]) => v > 0).map(([k, v]) => [k, Math.round(v * 100) / 100])),
            npkPerHa: npk, needs, support: Object.fromEntries(Object.entries(support).map(([k, v]) => [k, Math.round(v * 10) / 10])), lines: out, areaHa: area, soilClass: cls };
        $('npAnee').disabled = !out.length;
        $('npSave').disabled = !out.length;
    };
    $('npMbarGo').addEventListener('click', () => $('npOut').scrollIntoView({ behavior: 'smooth', block: 'start' }));

    /* ---- save, and Anee ---- */
    const save = async () => {
        if (!RES || !RES.lines.length) return null;
        const r = await window.api(U.save, { method: 'POST', body: { id: calcId, crop: st.crop, areaHa: RES.areaHa || 0.0001, targetYield: Number($('npTarget').value) || null, lines: RES.lines, soil: soil(), result: RES } });
        calcId = r.data.id;
        return r;
    };
    $('npSave').addEventListener('click', async () => { try { const r = await save(); window.toast?.(r.message + ' It is on the Saved tab.', 'success'); } catch (err) { window.toast?.(err.message, 'error'); } });
    const sheet = (on) => { $('npSheet').classList.toggle('is-on', on); $('npSheet').setAttribute('aria-hidden', String(!on)); document.documentElement.style.overflow = on ? 'hidden' : ''; };
    $('npAnee').addEventListener('click', () => {
        $('npRun').disabled = !OPT.canUse;
        $('npRunSays').textContent = OPT.canUse ? 'Ask Anee · ' + OPT.quote + ' credits' : 'Anee is not on your plan';
        $('npRunFine').innerHTML = OPT.canUse ? 'You have ' + (window.creditCoin ? window.creditCoin(OPT.unlimited ? '∞' : Number(OPT.balance).toLocaleString()) : OPT.balance) + '. Charged only when the reading is ready.' : esc(OPT.whyNot || '');
        sheet(true);
    });
    $('npSheetX').addEventListener('click', () => sheet(false));
    $('npSheet').addEventListener('click', (e) => { if (e.target === $('npSheet')) sheet(false); });
    const ans = { soil: [], water: 'irrigated', timing: 'split2' };
    const pills = (box, items, multi, key) => {
        box.innerHTML = Object.entries(items).map(([k, v]) => '<button type="button" class="np-pill" data-k="' + esc(k) + '">' + esc(String(v).split(' —')[0]) + '</button>').join('');
        const paint = () => box.querySelectorAll('.np-pill').forEach((b) => b.setAttribute('aria-pressed', String(multi ? ans[key].includes(b.dataset.k) : ans[key] === b.dataset.k)));
        box.addEventListener('click', (e) => { const b = e.target.closest('.np-pill'); if (!b) return; if (multi) ans[key] = ans[key].includes(b.dataset.k) ? ans[key].filter((x) => x !== b.dataset.k) : ans[key].concat(b.dataset.k); else ans[key] = b.dataset.k; paint(); });
        paint();
    };
    $('npRun').addEventListener('click', async () => {
        if (!$('npLoc').value.trim()) return window.toast?.('Say where the field is.', 'error');
        $('npRun').disabled = true;
        try {
            await save();
            sheet(false);
            window.aneeWait.show({ title: 'Anee is checking your plan…', sub: 'The soil, the season and the timing. About a minute.', lines: ['Weighing each nutrient against the crop…', 'Reading the soils of your area…', 'Checking the rains around planting…', 'Deciding what to split and when…'] });
            const r = await window.api(U.analyze, { method: 'POST', body: { calcId, location: $('npLoc').value.trim(), soilConditions: ans.soil, phValue: $('npPh').value ? Number($('npPh').value) : null,
                water: ans.water, plantingDate: $('npPlant').value || null, timing: ans.timing, variety: $('npVariety').value.trim(), notes: $('npNotes').value.trim() } });
            const d = r.data && r.data.status === 'ready' ? r.data : await window.aneeWait.poll({ id: r.data.id, job: U.job });
            await window.aneeWait.done({ title: 'Your plan, read.', line: 'Here is what to keep and what to change.' });
            OPT.balance = d.balance;
            showReport(d.report);
        } catch (err) { window.aneeWait.fail(); window.toast?.(err.message || 'The reading did not finish. Nothing was charged.', 'error'); }
        finally { $('npRun').disabled = !OPT.canUse; }
    });
    const showReport = (rep) => {
        const a = (rep || {}).anee || {};
        const v = String(a.verdict || '').toLowerCase().replace(/\s+/g, '-');
        const sec = (t, inner) => inner ? '<div class="np-card"><h3>' + esc(t) + '</h3><div class="np-rlist">' + inner + '</div></div>' : '';
        $('npRep').hidden = false;
        $('npRep').innerHTML = '<div class="np-rhead v-' + esc(v) + '"><small>Anee\'s verdict: ' + esc(a.verdict) + ' · ' + esc(rep.at || '') + '</small><h3>' + esc(a.headline) + '</h3><p>' + esc(a.summary) + '</p></div>'
            + sec('Nutrient by nutrient', (a.nutrients || []).map((x) => '<div class="np-ritem"><span class="np-st s-' + esc(String(x.status || '').toLowerCase()) + '">' + esc(x.status) + '</span><b>' + esc(x.nutrient) + '</b> · ' + esc(x.comment) + '</div>').join(''))
            + sec('When to apply each one', (a.timing || []).map((x) => '<div class="np-ritem"><b>' + esc(x.product) + '</b> · ' + esc(x.when) + '<br>' + esc(x.how) + ' <span class="text-gray-500">' + esc(x.why) + '</span></div>').join(''))
            + sec('What to change', (a.changes || []).map((x) => '<div class="np-ritem"><b>' + esc(x.change) + '</b><br><span class="text-gray-500">' + esc(x.why) + '</span></div>').join(''))
            + sec('Your soil, the season and the yield', ['soil', 'weather', 'biofertilizers', 'yield'].filter((k) => a[k]).map((k) => '<div class="np-ritem">' + esc(a[k]) + '</div>').join(''))
            + sec('Be careful', (a.warnings || []).filter(Boolean).map((w) => '<div class="np-ritem">' + esc(w) + '</div>').join(''))
            + '<p class="np-fine">Confidence: ' + esc(a.confidence || '') + '. A guide, not a promise: the weather, the variety and the soil still decide the harvest.</p>';
        $('npRep').scrollIntoView({ behavior: matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth', block: 'start' });
    };

    /* ---- saved ---- */
    let savedPage = 1, savedQ = '';
    const loadSaved = async (more = false) => {
        if (!more) { savedPage = 1; $('npSaved').innerHTML = ''; }
        try {
            const r = await window.api(U.list + '?page=' + savedPage + '&q=' + encodeURIComponent(savedQ));
            $('npSaved').insertAdjacentHTML('beforeend', (r.data.rows || []).map((x) => '<button type="button" class="np-srow" data-id="' + x.id + '"><span><b>' + esc(x.title) + '</b><small>' + esc(x.at) + (x.npk ? ' · ' + esc(x.npk) + ' per ha' : '') + '</small></span>' + (x.analyzed ? '<em>Read by Anee</em>' : '') + '</button>').join(''));
            $('npSavedEmpty').hidden = !!$('npSaved').children.length;
            $('npSavedMore').hidden = !r.data.hasMore;
        } catch (err) { window.toast?.(err.message, 'error'); }
    };
    $('npSaved').addEventListener('click', async (e) => {
        const b = e.target.closest('.np-srow');
        if (!b) return;
        try {
            const r = await window.api(U.one(b.dataset.id));
            const d = r.data;
            calcId = d.id; st.crop = d.crop; $('npTarget').value = d.targetYield ?? ''; $('npArea').value = d.areaHa; $('npAreaUnit').value = 'ha';
            st.lines = (d.lines || []).filter((l) => OPT.products.some((p) => p.id === l.id)).map((l) => ({ id: l.id, amount: l.amount, unit: l.unit }));
            tab(false); crops(); lines(); shelf(); calc();
            if (d.analysis) showReport(d.analysis.report); else $('npRep').hidden = true;
        } catch (err) { window.toast?.(err.message, 'error'); }
    });
    $('npSavedMore').addEventListener('click', () => { savedPage++; loadSaved(true); });
    let sq = null;
    $('npSavedQ').addEventListener('input', () => { clearTimeout(sq); sq = setTimeout(() => { savedQ = $('npSavedQ').value.trim(); loadSaved(); }, 300); });
    const tab = (saved) => {
        $('npTabCalc').classList.toggle('is-on', !saved); $('npTabSaved').classList.toggle('is-on', saved);
        $('npCalc').hidden = saved; $('npSavedPane').hidden = !saved;
        if (saved) loadSaved();
    };
    $('npTabCalc').addEventListener('click', () => tab(false));
    $('npTabSaved').addEventListener('click', () => tab(true));

    const boot = async () => {
        try {
            const r = await window.api(U.options);
            OPT = r.data;
            $('npCats').innerHTML = Object.entries(OPT.categories).map(([k, v]) => '<button type="button" class="np-cat' + (k === cat ? ' is-on' : '') + '" data-c="' + esc(k) + '">' + esc(v) + '</button>').join('');
            pills($('npSoilCond'), OPT.soilConditions, true, 'soil');
            pills($('npWater'), OPT.water, false, 'water');
            pills($('npTiming'), OPT.timing, false, 'timing');
            crops(); shelf(); lines(); calc();
        } catch (err) { window.toast?.(err.message || 'Could not load the calculator.', 'error'); }
    };
    if (window.api) boot(); else window.addEventListener('load', boot, { once: true });
})();
</script>
@endpush
