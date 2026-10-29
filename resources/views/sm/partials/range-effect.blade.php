{{-- THE RANGE EFFECT CALENDAR (2026-10-29, the owner: "we want another feature called range effect calendar. but just say
     range effect in desktop and put it after the calendar view, but just an icon in mobile, and put it after harvest
     button ... when it is clicked, it shows a full modal box full screen that shows the current coverage of the
     following: N, P, K, Micros, pest protection, fungus protection, and so on based on the inputs ... also show the
     criticality in the calendar ... user selects what lot, what to check ... and or specific product that the user
     plans to apply ... if its not in our db yet ... ask for credits ... make it look good, smooth animations, and user
     should be able to save that instance of the results").

     Opened by #rangeEffectBtn (the toolbar, desktop, after Calendar view) and #rangeEffectMobileBtn (the header bar,
     phones, after Harvest). One full screen sheet:
       the controls   the lot, then what to check (Nutrients N P K, Micros, Protection by kind, or one pest or disease
                      of the lot's crop), then products to try on a date (the product book searched; one the book lacks
                      is looked up by Anee, paid, AiPrices 'effect', and kept)
       the reading    a summary ring (how much of the critical days is covered), the gaps and the late ones; the season
                      as a chart (coverage per day over the crop's stages and how critical each is); a calendar month
                      by month (each day filled to its coverage, its criticality as dots, the products on it); the
                      products themselves, each with the days it keeps working
       keep it        Save keeps the reading as it stands (as_range_effects); Saved opens one again, or reruns it with
                      today's plan
     Engine: App\Support\RangeEffect, book App\Support\RangeEffectBook, answers Manager\RangeEffectController.
     Prefix rfx-. Plain words, no dashes. --}}
@include('sm.partials.anee-wait')
@php
    $rfxU = [
        'options' => route('sm.effect') . '?scheduleId=' . $schedule->id,
        'run' => route('sm.effect.run') . '?scheduleId=' . $schedule->id,
        'products' => route('sm.effect.products') . '?scheduleId=' . $schedule->id,
        'save' => route('sm.effect.save') . '?scheduleId=' . $schedule->id,
        'saved' => url('/app/sm-range-effect/saved') . '/',
        'lookup' => route('sm.effect.lookup') . '?scheduleId=' . $schedule->id,
        'job' => url('/app/sm-range-effect/lookup') . '/',
    ];
@endphp
<div class="rfx" id="rfxModal" role="dialog" aria-modal="true" aria-labelledby="rfxTitle" hidden data-u='@json($rfxU)' data-sid="{{ $schedule->id }}">
    {{-- The calendar's application marks: granules spread, a spray, organic, a try. --}}
    <svg width="0" height="0" style="position:absolute" aria-hidden="true" focusable="false">
        <symbol id="rfxI-gran" viewBox="0 0 16 16"><circle cx="4.6" cy="6.2" r="2.3"/><circle cx="10.6" cy="4.8" r="2.1"/><circle cx="8.2" cy="10.9" r="2.5"/><circle cx="13" cy="10.4" r="1.5"/></symbol>
        <symbol id="rfxI-drop" viewBox="0 0 16 16"><path d="M8 1.5c2.8 3.6 4.5 5.9 4.5 8.1a4.5 4.5 0 01-9 0c0-2.2 1.7-4.5 4.5-8.1z"/></symbol>
        <symbol id="rfxI-leaf" viewBox="0 0 16 16"><path d="M2.5 13.5C2.5 7 6.6 3 13.8 2.2 13.4 9.4 9.4 13.5 2.5 13.5zm1.6-1.1c2.4-2.6 4.6-4.6 7.1-6.3-2.9 1.3-5.2 3.4-7.1 6.3z" fill-rule="evenodd"/></symbol>
        <symbol id="rfxI-star" viewBox="0 0 16 16"><path d="M8 1.3l2 4.3 4.6.5-3.5 3.1 1 4.6L8 11.5l-4.1 2.3 1-4.6L1.4 6.1 6 5.6z"/></symbol>
    </svg>
    <div class="rfx-veil" data-rfx-close></div>
    <div class="rfx-box">
        <header class="rfx-head">
            <span class="rfx-logo" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4.5" width="18" height="16" rx="2.5"/><path d="M3 9.5h18M8 3v3M16 3v3"/><path d="M6.5 15.5c1.6-3.2 3.4-3.2 5 0s3.4 3.2 5 0" stroke-width="2.2"/></svg>
            </span>
            <div class="rfx-head-t">
                <h2 id="rfxTitle">Range Effect</h2>
                <p>How long everything you put on keeps working, against what your crop needs each day.</p>
            </div>
            <button type="button" class="rfx-hbtn" data-rfx-saved-open><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 4h14v17l-7-4-7 4V4z"/></svg><span>Saved</span><b data-rfx-saved-n hidden></b></button>
            <button type="button" class="rfx-x" data-rfx-close aria-label="Close"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true"><path stroke-linecap="round" d="M6 6l12 12M18 6L6 18"/></svg></button>
        </header>

        <div class="rfx-scroll" data-rfx-scroll>
            {{-- ================= THE CONTROLS ================= --}}
            <section class="rfx-controls">
                <div class="rfx-row">
                    <span class="rfx-label">Lot</span>
                    <div class="rfx-chips" data-rfx-lots></div>
                </div>
                <div class="rfx-row">
                    <span class="rfx-label">Check</span>
                    <div class="rfx-tabs" role="tablist" data-rfx-tabs>
                        <button type="button" role="tab" data-tab="nut" class="is-on">🧪 Nutrients</button>
                        <button type="button" role="tab" data-tab="micro">✨ Micros</button>
                        <button type="button" role="tab" data-tab="prot">🛡️ Protection</button>
                        <button type="button" role="tab" data-tab="pest">🐛 A pest or disease</button>
                    </div>
                </div>
                <div class="rfx-row is-sub">
                    <span class="rfx-label" aria-hidden="true"></span>
                    <div class="rfx-picks" data-rfx-picks></div>
                </div>
                {{-- Several checks side by side (the owner, 2026-10-30): which one the calendar shows, and the rest. --}}
                <div class="rfx-row is-sub" data-rfx-sel-row hidden>
                    <span class="rfx-label">Compare</span>
                    <div class="rfx-sel" data-rfx-sel></div>
                </div>
                <div class="rfx-row is-sub">
                    <span class="rfx-label">Try</span>
                    <div class="rfx-tries">
                        {{-- Its own button, first, so a long list of tries never pushes it away. --}}
                        <button type="button" class="rfx-add" data-rfx-try-open><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true"><path stroke-linecap="round" d="M12 5v14M5 12h14"/></svg>A product you plan to apply</button>
                        <div class="rfx-chips is-wrap" data-rfx-tries></div>
                    </div>
                </div>
                {{-- Trying a product: find it, give the day (and the amount, if you know it). --}}
                <div class="rfx-try" data-rfx-try hidden>
                    <div class="rfx-try-in">
                        <label class="rfx-search">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path stroke-linecap="round" d="M20 20l-3.5-3.5"/></svg>
                            <input type="search" data-rfx-q placeholder="Product name, company or active ingredient" autocomplete="off">
                        </label>
                        <div class="rfx-results" data-rfx-results></div>
                        <div class="rfx-picked" data-rfx-picked hidden>
                            <div class="rfx-picked-p" data-rfx-picked-p></div>
                            {{-- How it goes on (the owner, 2026-10-30): granular on the soil, or sprayed on the leaves. --}}
                            <div class="rfx-how" role="radiogroup" aria-label="How you apply it" data-rfx-how data-on="granular">
                                <i class="rfx-how-pill" aria-hidden="true"></i>
                                <button type="button" role="radio" aria-checked="true" data-how="granular">
                                    <svg viewBox="0 0 32 32" aria-hidden="true"><path d="M3 25c4-2 8-2 13 0s9 2 13 0v4H3z" fill="#a8723f"/><circle cx="9" cy="13" r="3" fill="#e9d6b0" stroke="#8a5a2b" stroke-width="1.4"/><circle cx="17" cy="9" r="3" fill="#e9d6b0" stroke="#8a5a2b" stroke-width="1.4"/><circle cx="23" cy="16" r="3" fill="#e9d6b0" stroke="#8a5a2b" stroke-width="1.4"/><circle cx="14" cy="19" r="2.4" fill="#e9d6b0" stroke="#8a5a2b" stroke-width="1.4"/></svg>
                                    <span><b>Granular</b><small>Spread on the soil or the water</small></span>
                                </button>
                                <button type="button" role="radio" aria-checked="false" data-how="foliar">
                                    <svg viewBox="0 0 32 32" aria-hidden="true"><path d="M7 27c0-8 5-15 15-19-2 8-5 15-15 19z" fill="#86c25a" stroke="#3b7a1f" stroke-width="1.4" stroke-linejoin="round"/><path d="M7 27c4-5 7-9 12-14" fill="none" stroke="#3b7a1f" stroke-width="1.4" stroke-linecap="round"/><path d="M24 3c1.6 2.4 2.6 3.8 2.6 5a2.6 2.6 0 01-5.2 0c0-1.2 1-2.6 2.6-5z" fill="#38bdf8"/><path d="M28 11c1 1.5 1.6 2.4 1.6 3.1a1.6 1.6 0 01-3.2 0c0-.7.6-1.6 1.6-3.1z" fill="#38bdf8"/></svg>
                                    <span><b>Foliar</b><small>Sprayed on the leaves</small></span>
                                </button>
                            </div>
                            <div class="rfx-picked-f">
                                <label><span>Apply on</span><input type="date" data-rfx-date></label>
                                <label><span>Amount for the lot <i data-rfx-ha>(optional)</i></span><span class="rfx-amt"><input type="number" min="0" step="any" inputmode="decimal" data-rfx-qty placeholder="e.g. 2"><select data-rfx-unit><option value="bags">bags</option><option value="kg">kg</option><option value="L">liters</option><option value="mL">mL</option><option value="g">grams</option></select></span></label>
                            </div>
                            <div class="rfx-picked-b">
                                <button type="button" class="rfx-btn is-ghost" data-rfx-try-cancel>Cancel</button>
                                <button type="button" class="rfx-btn is-go" data-rfx-try-add>See its effect</button>
                            </div>
                        </div>
                        {{-- Not in the book: Anee looks it up, once, for everyone. --}}
                        <div class="rfx-look" data-rfx-look hidden>
                            <img src="{{ \App\Support\AneeEmoji::url('thinking') }}" alt="" width="64" height="64">
                            <div>
                                <b>Not in our book yet?</b>
                                <p>Tell Anee its name, its active ingredient, or both. She looks it up, and it stays in the book for every farmer after you.</p>
                                <div class="rfx-look-f">
                                    <input type="text" data-rfx-look-name placeholder="Product name" maxlength="120">
                                    <input type="text" data-rfx-look-ai placeholder="Active ingredient" maxlength="160">
                                </div>
                                <button type="button" class="rfx-btn is-gold" data-rfx-look-go>Look it up</button>
                                <small data-rfx-look-why></small>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            {{-- ================= THE READING ================= --}}
            <section class="rfx-body" data-rfx-body aria-live="polite">
                <div class="rfx-loading" data-rfx-loading><span></span><span></span><span></span></div>
            </section>
        </div>

        <footer class="rfx-foot">
            <span class="rfx-foot-note" data-rfx-note>Counts the products written on each day's tasks, from your shed or typed in, on the day they are used.</span>
            <button type="button" class="rfx-btn is-go" data-rfx-save><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 4h14v17l-7-4-7 4V4z"/></svg>Save this reading</button>
        </footer>

        {{-- Saved readings. --}}
        <div class="rfx-drawer" data-rfx-drawer hidden>
            <div class="rfx-drawer-h"><b>Saved readings</b><button type="button" class="rfx-x" data-rfx-saved-close aria-label="Close"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true"><path stroke-linecap="round" d="M6 6l12 12M18 6L6 18"/></svg></button></div>
            <div class="rfx-drawer-b" data-rfx-saved></div>
        </div>
        {{-- One day, opened. --}}
        <div class="rfx-day" data-rfx-day hidden></div>
    </div>
</div>

@once
@push('styles')
<style>
    /* ===================== RANGE EFFECT (rfx-) ===================== */
    .rfx { position: fixed; inset: 0; z-index: 420; display: grid; place-items: center;
        /* What an application is doing: in the soil, being taken up, strongest, easing, fading away. */
        --ph-soil: linear-gradient(180deg, #c99158, #8a5a2b); --ph-absorb: linear-gradient(180deg, #62d4bf, #138a77); --ph-strong: linear-gradient(180deg, #4f9030, #22500e);
        --ph-easing: linear-gradient(180deg, #a2d477, #62a03c); --ph-fading: linear-gradient(180deg, #e6f4d8, #c2e1a6); }
    .rfx-sw { display: inline-block; flex: none; width: .9rem; height: .9rem; border-radius: .25rem; background: var(--ph-strong); vertical-align: -.1rem; }
    .rfx .is-soil { --ph: var(--ph-soil); } .rfx .is-absorb { --ph: var(--ph-absorb); } .rfx .is-strong { --ph: var(--ph-strong); } .rfx .is-easing { --ph: var(--ph-easing); } .rfx .is-fading { --ph: var(--ph-fading); }
    .rfx .rfx-sw[class*=is-] { background: var(--ph); }
    .rfx[hidden] { display: none; }
    .rfx-veil { position: absolute; inset: 0; background: rgb(10 16 7 / .55); backdrop-filter: blur(3px); opacity: 0; transition: opacity .28s cubic-bezier(.22,1,.36,1); }
    .rfx-box { position: relative; display: flex; flex-direction: column; width: 100%; height: 100%; background: #f6f8f3; overflow: hidden;
        opacity: 0; transform: translateY(18px) scale(.985); transition: opacity .32s cubic-bezier(.22,1,.36,1), transform .32s cubic-bezier(.22,1,.36,1); }
    @media (min-width: 900px) { .rfx-box { width: min(96vw, 82rem); height: min(94vh, 60rem); border-radius: 1.4rem; box-shadow: 0 40px 90px -30px rgb(0 0 0 / .6); } }
    .rfx.is-on .rfx-veil { opacity: 1; }
    .rfx.is-on .rfx-box { opacity: 1; transform: none; }
    html.rfx-lock { overflow: hidden; }
    .rfx-head { display: flex; align-items: center; gap: .8rem; padding: .85rem 1rem; background: linear-gradient(135deg, #1f3a12, #2f5219 60%, #3b6620); color: #fff; }
    .rfx-logo { display: grid; place-items: center; flex: none; width: 2.6rem; height: 2.6rem; border-radius: .9rem; color: #14210c; background: #f5c518; box-shadow: 0 0 0 4px rgb(245 197 24 / .25); }
    .rfx-logo svg { width: 1.45rem; height: 1.45rem; }
    .rfx-head-t { min-width: 0; flex: 1; }
    .rfx-head h2 { font-family: var(--font-heading); font-size: 1.2rem; font-weight: 800; line-height: 1.2; }
    .rfx-head p { margin-top: .1rem; font-size: .8rem; color: #cfe0c0; line-height: 1.35; }
    .rfx-hbtn { position: relative; display: inline-flex; align-items: center; gap: .4rem; padding: .5rem .8rem; border-radius: .8rem; font-size: .84rem; font-weight: 800; color: #fff;
        background: rgb(255 255 255 / .12); box-shadow: inset 0 0 0 1px rgb(255 255 255 / .25); transition: background-color .28s cubic-bezier(.22,1,.36,1); }
    .rfx-hbtn:hover { background: rgb(255 255 255 / .2); }
    .rfx-hbtn svg { width: 1.05rem; height: 1.05rem; }
    .rfx-hbtn b { position: absolute; top: -.35rem; right: -.35rem; min-width: 1.2rem; height: 1.2rem; padding: 0 .3rem; border-radius: 999px; font-size: .66rem; line-height: 1.2rem; color: #14210c; background: #f5c518; }
    .rfx-x { display: grid; place-items: center; flex: none; width: 2.4rem; height: 2.4rem; border-radius: 999px; color: inherit; background: rgb(255 255 255 / .12); transition: background-color .28s, transform .28s cubic-bezier(.22,1,.36,1); }
    .rfx-x:hover { background: rgb(255 255 255 / .22); transform: rotate(90deg); }
    .rfx-x svg { width: 1.15rem; height: 1.15rem; }
    @media (max-width: 639.98px) { .rfx-head p { display: none; } .rfx-hbtn span { display: none; } .rfx-hbtn { padding: .55rem; } }
    .rfx-scroll { flex: 1; overflow: auto; overscroll-behavior: contain; }
    .rfx-controls { position: relative; z-index: 3; display: grid; gap: .55rem; padding: .9rem 1rem; background: #fff; border-bottom: 1px solid #e3ecd9; }
    .rfx-row { display: grid; grid-template-columns: 4.2rem minmax(0, 1fr); gap: .6rem; align-items: center; }
    .rfx-row.is-sub { align-items: start; }
    .rfx-label { font-size: .7rem; font-weight: 900; letter-spacing: .08em; text-transform: uppercase; color: #6b7280; }
    @media (max-width: 639.98px) { .rfx-row { grid-template-columns: minmax(0, 1fr); gap: .35rem; } .rfx-row.is-sub .rfx-label { display: none; } }
    .rfx-chips, .rfx-picks { display: flex; gap: .4rem; overflow-x: auto; scrollbar-width: none; padding: .15rem .1rem; }
    .rfx-picks { flex-wrap: wrap; overflow: visible; }
    .rfx-chips::-webkit-scrollbar { display: none; }
    .rfx-chip { flex: none; display: inline-flex; align-items: center; gap: .35rem; padding: .45rem .8rem; border-radius: 999px; font-size: .84rem; font-weight: 800; white-space: nowrap; color: #2f5219; background: #fff;
        box-shadow: inset 0 0 0 1.5px #d6e5c6; cursor: pointer; transition: background-color .28s cubic-bezier(.22,1,.36,1), color .28s, box-shadow .28s, transform .28s cubic-bezier(.22,1,.36,1); }
    .rfx-chip:hover { transform: translateY(-1px); box-shadow: inset 0 0 0 1.5px #a9c98c; }
    .rfx-chip.is-on { color: #fff; background: #2f5219; box-shadow: 0 8px 18px -10px rgb(47 82 25 / .8); }
    .rfx-chip small { font-size: .7rem; font-weight: 700; opacity: .75; }
    .rfx-chip.is-try { color: #5c4500; background: #fff6d6; box-shadow: inset 0 0 0 1.5px #f5c518; cursor: default; }
    .rfx-chip.is-try button { display: grid; place-items: center; width: 1.2rem; height: 1.2rem; margin-right: -.3rem; border-radius: 999px; background: rgb(0 0 0 / .08); }
    .rfx-chip.is-try button svg { width: .7rem; height: .7rem; }
    .rfx-tabs { display: flex; gap: .3rem; padding: .25rem; border-radius: 1rem; background: #eef3e8; overflow-x: auto; scrollbar-width: none; }
    .rfx-tabs button { flex: 1 0 auto; padding: .5rem .8rem; border-radius: .75rem; font-size: .84rem; font-weight: 800; white-space: nowrap; color: #4b5563; transition: background-color .28s cubic-bezier(.22,1,.36,1), color .28s, box-shadow .28s; }
    .rfx-tabs button.is-on { color: #14210c; background: #fff; box-shadow: 0 4px 12px -6px rgb(20 33 12 / .35); }
    @media (max-width: 559.98px) { .rfx-tabs { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); } .rfx-tabs button { padding: .5rem .4rem; font-size: .8rem; } }
    .rfx-picks .rfx-chip { animation: rfxPop .32s cubic-bezier(.22,1,.36,1) both; animation-delay: calc(var(--k, 0) * 25ms); }
    @keyframes rfxPop { from { opacity: 0; transform: translateY(6px) scale(.96); } }
    .rfx-picks input[type=search] { flex: 1 1 100%; padding: .55rem .8rem; border-radius: .8rem; border: 1.5px solid #d6e5c6; font-size: .9rem; }
    .rfx-tries { display: grid; justify-items: start; gap: .45rem; }
    .rfx-chips.is-wrap { flex-wrap: wrap; overflow: visible; padding: 0; }
    .rfx-chips.is-wrap:empty { display: none; }
    .rfx-chips.is-wrap .rfx-chip { max-width: 100%; white-space: normal; animation: rfxPop .32s cubic-bezier(.22,1,.36,1) both; }
    .rfx-add { display: inline-flex; align-items: center; gap: .35rem; padding: .5rem .9rem; border-radius: 999px; font-size: .84rem; font-weight: 800; color: #14210c; background: #f5c518;
        box-shadow: 0 8px 18px -10px rgb(184 138 0 / .9); transition: background-color .28s, transform .28s cubic-bezier(.22,1,.36,1), box-shadow .28s; }
    .rfx-add.is-full { color: #6b7280; background: #f3f4f6; box-shadow: none; }
    /* Several checks: the one the calendar shows is filled, the rest are outlined in their own colour. */
    .rfx-sel { display: flex; flex-wrap: wrap; gap: .4rem; }
    .rfx-sel .rfx-chip { gap: .4rem; padding: .35rem .45rem .35rem .7rem; animation: rfxPop .32s cubic-bezier(.22,1,.36,1) both; }
    .rfx-sel .rfx-chip i { width: .65rem; height: .65rem; border-radius: 999px; background: var(--sc); }
    .rfx-sel .rfx-chip.is-on { background: var(--sc); color: #fff; }
    .rfx-sel .rfx-chip.is-on i { background: #fff; }
    .rfx-sel .rfx-chip button { display: grid; place-items: center; width: 1.25rem; height: 1.25rem; border-radius: 999px; background: rgb(0 0 0 / .08); }
    .rfx-sel .rfx-chip button svg { width: .65rem; height: .65rem; }
    .rfx-picks .rfx-chip.is-on::after { content: '✓'; font-size: .75rem; font-weight: 900; }
    .rfx-hint { align-self: center; font-size: .74rem; font-weight: 600; color: #9ca3af; }
    .rfx-add:hover { background: #ffd43b; transform: translateY(-1px); }
    .rfx-add svg { width: .9rem; height: .9rem; }
    .rfx-try { overflow: hidden; }
    .rfx-try-in { display: grid; gap: .6rem; padding: .8rem; border-radius: 1.1rem; background: #fffdf3; box-shadow: inset 0 0 0 1.5px #f3e3a3; animation: rfxSlide .32s cubic-bezier(.22,1,.36,1) both; }
    @keyframes rfxSlide { from { opacity: 0; transform: translateY(-8px); } }
    .rfx-search { display: flex; align-items: center; gap: .5rem; padding: 0 .8rem; border-radius: .9rem; background: #fff; box-shadow: inset 0 0 0 1.5px #e7d9a5; }
    .rfx-search svg { width: 1.1rem; height: 1.1rem; color: #9a7b14; flex: none; }
    .rfx-search input { flex: 1; min-width: 0; padding: .65rem 0; border: 0; background: transparent; font-size: .95rem; outline: none; }
    .rfx-results { display: grid; gap: .35rem; max-height: 16rem; overflow: auto; }
    .rfx-res { display: flex; align-items: center; gap: .6rem; padding: .6rem .7rem; border-radius: .8rem; text-align: left; background: #fff; box-shadow: inset 0 0 0 1px #efe6c6;
        transition: box-shadow .2s, transform .2s cubic-bezier(.22,1,.36,1); animation: rfxPop .28s cubic-bezier(.22,1,.36,1) both; animation-delay: calc(var(--k, 0) * 22ms); }
    .rfx-res:hover { box-shadow: inset 0 0 0 1.5px #f5c518; transform: translateX(2px); }
    .rfx-res em { flex: none; display: grid; place-items: center; width: 2.2rem; height: 2.2rem; border-radius: .7rem; font-style: normal; font-size: 1.05rem; background: #f6f1dc; }
    .rfx-res b { display: block; font-size: .88rem; color: #14210c; }
    .rfx-res small { display: block; font-size: .74rem; color: #6b7280; line-height: 1.35; }
    .rfx-picked { display: grid; gap: .6rem; }
    .rfx-picked-p { display: flex; align-items: center; gap: .6rem; padding: .7rem; border-radius: .9rem; background: #fff; box-shadow: inset 0 0 0 1.5px #f5c518; }
    .rfx-picked-f { display: grid; gap: .6rem; grid-template-columns: repeat(2, minmax(0, 1fr)); }
    @media (max-width: 559.98px) { .rfx-picked-f { grid-template-columns: minmax(0, 1fr); } }
    .rfx-picked-f label { display: grid; gap: .25rem; font-size: .74rem; font-weight: 800; color: #6b7280; }
    .rfx-picked-f label i { font-style: normal; font-weight: 600; }
    .rfx-picked-f input, .rfx-picked-f select { width: 100%; padding: .6rem .7rem; border-radius: .75rem; border: 1.5px solid #e3d9b0; font-size: .92rem; background: #fff; color: #14210c; }
    .rfx-amt { display: grid; grid-template-columns: minmax(0, 1fr) 6.5rem; gap: .4rem; }
    .rfx-how { position: relative; display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .3rem; padding: .25rem; border-radius: 1rem; background: #f6efd2; }
    .rfx-how button { position: relative; z-index: 1; display: flex; align-items: center; gap: .55rem; min-width: 0; padding: .55rem .7rem; border-radius: .8rem; text-align: left; color: #7a6420;
        transition: color .28s cubic-bezier(.22,1,.36,1); }
    .rfx-how button[aria-checked=true] { color: #14210c; }
    .rfx-how svg { flex: none; width: 1.7rem; height: 1.7rem; transition: transform .28s cubic-bezier(.22,1,.36,1); }
    .rfx-how button[aria-checked=true] svg { transform: scale(1.12) rotate(-4deg); }
    .rfx-how b { display: block; font-size: .88rem; }
    .rfx-how small { display: block; font-size: .7rem; font-weight: 600; line-height: 1.3; opacity: .8; }
    .rfx-how-pill { position: absolute; z-index: 0; top: .25rem; bottom: .25rem; left: .25rem; width: calc(50% - .4rem); border-radius: .8rem; background: #fff;
        box-shadow: inset 0 0 0 1.5px #f5c518, 0 6px 14px -8px rgb(92 69 0 / .5); transition: transform .28s cubic-bezier(.22,1,.36,1); }
    .rfx-how[data-on=foliar] .rfx-how-pill { transform: translateX(calc(100% + .3rem)); }
    .rfx-picked-b { display: flex; justify-content: flex-end; gap: .5rem; }
    .rfx-btn { display: inline-flex; align-items: center; justify-content: center; gap: .45rem; padding: .62rem 1rem; border-radius: .85rem; font-size: .88rem; font-weight: 800;
        transition: transform .28s cubic-bezier(.22,1,.36,1), box-shadow .28s, background-color .28s; }
    .rfx-btn svg { width: 1.05rem; height: 1.05rem; }
    .rfx-btn:hover { transform: translateY(-1px); }
    .rfx-btn.is-go { color: #fff; background: #2f5219; box-shadow: 0 10px 20px -12px rgb(47 82 25 / .8); }
    .rfx-btn.is-gold { color: #14210c; background: #f5c518; box-shadow: 0 10px 20px -12px rgb(184 138 0 / .8); }
    .rfx-btn.is-ghost { color: #4b5563; background: #fff; box-shadow: inset 0 0 0 1.5px #e5e7eb; }
    .rfx-btn:disabled { opacity: .55; transform: none; }
    .rfx-look { display: flex; gap: .8rem; align-items: flex-start; padding: .8rem; border-radius: .9rem; background: #fff; box-shadow: inset 0 0 0 1px #efe6c6; }
    .rfx-look > img { flex: none; width: 3rem; height: 3rem; }
    .rfx-look b { font-size: .92rem; color: #14210c; }
    .rfx-look p { margin-top: .2rem; font-size: .82rem; line-height: 1.5; color: #4b5563; }
    .rfx-look-f { display: grid; gap: .45rem; grid-template-columns: repeat(2, minmax(0, 1fr)); margin: .6rem 0; }
    @media (max-width: 559.98px) { .rfx-look-f { grid-template-columns: minmax(0, 1fr); } }
    .rfx-look-f input { width: 100%; padding: .6rem .7rem; border-radius: .75rem; border: 1.5px solid #e3d9b0; font-size: .9rem; }
    .rfx-look small { display: block; margin-top: .45rem; font-size: .74rem; color: #6b7280; }

    /* ---- the reading ---- */
    .rfx-body { padding: 1rem; display: grid; gap: 1rem; }
    @media (min-width: 900px) { .rfx-body { padding: 1.2rem 1.4rem 1.6rem; } }
    /* Grid items keep their content's width unless told: the chart's scroll box would widen the whole sheet. */
    .rfx-body > *, .rfx-controls > *, .rfx-row > * { min-width: 0; }
    .rfx-loading { display: flex; justify-content: center; gap: .4rem; padding: 4rem 0; }
    .rfx-loading span { width: .7rem; height: .7rem; border-radius: 999px; background: #4a7c2a; animation: rfxDot 1s ease-in-out infinite; }
    .rfx-loading span:nth-child(2) { animation-delay: .15s; } .rfx-loading span:nth-child(3) { animation-delay: .3s; }
    @keyframes rfxDot { 50% { transform: translateY(-8px); opacity: .5; } }
    .rfx-card { border-radius: 1.2rem; background: #fff; box-shadow: 0 0 0 1px #e3ecd9, 0 18px 36px -30px rgb(20 33 12 / .5); animation: rfxIn .45s cubic-bezier(.22,1,.36,1) both; animation-delay: var(--d, 0s); }
    @keyframes rfxIn { from { opacity: 0; transform: translateY(12px); } }
    .rfx-card-h { display: flex; align-items: center; gap: .5rem; padding: .9rem 1rem .2rem; font-size: .72rem; font-weight: 900; letter-spacing: .08em; text-transform: uppercase; color: #4a7c2a; }
    .rfx-card-h small { margin-left: auto; font-size: .72rem; font-weight: 700; letter-spacing: 0; text-transform: none; color: #6b7280; }
    .rfx-banner { display: flex; align-items: center; gap: .7rem; padding: .75rem 1rem; border-radius: 1rem; font-size: .86rem; font-weight: 700; color: #5c4500; background: #fff6d6; box-shadow: inset 0 0 0 1px #f3e3a3; }
    .rfx-banner.is-saved { color: #1e3a8a; background: #eef2ff; box-shadow: inset 0 0 0 1px #c7d2fe; }
    .rfx-banner .rfx-btn { margin-left: auto; padding: .45rem .8rem; font-size: .8rem; }
    .rfx-banner.is-miss { flex-wrap: wrap; font-weight: 600; animation: rfxIn .4s cubic-bezier(.22,1,.36,1) both; }
    .rfx-banner.is-miss > span:first-child { flex: 1 1 16rem; }
    .rfx-acts { display: flex; flex-wrap: wrap; gap: .35rem; }
    .rfx-acts .rfx-chip { padding: .35rem .7rem; font-size: .78rem; }
    /* The summary. */
    .rfx-sum { display: grid; gap: 1rem; padding: 1rem; grid-template-columns: auto minmax(0, 1fr); align-items: center; }
    @media (max-width: 559.98px) { .rfx-sum { grid-template-columns: minmax(0, 1fr); justify-items: center; text-align: center; } }
    .rfx-ring { position: relative; width: 8.2rem; aspect-ratio: 1; }
    .rfx-ring svg { width: 100%; height: 100%; transform: rotate(-90deg); }
    .rfx-ring circle { fill: none; stroke-width: 10; }
    .rfx-ring .t { stroke: #eef3e8; }
    .rfx-ring .v { stroke-linecap: round; stroke-dasharray: 0 400; transition: stroke-dasharray 1.2s cubic-bezier(.22,1,.36,1); }
    .rfx-ring-n { position: absolute; inset: 0; display: grid; place-items: center; align-content: center; text-align: center; }
    .rfx-ring-n b { font-family: var(--font-heading); font-size: 1.9rem; font-weight: 900; line-height: 1; color: #14210c; }
    .rfx-ring-n small { margin-top: .2rem; font-size: .66rem; font-weight: 800; color: #6b7280; line-height: 1.2; max-width: 5.5rem; }
    .rfx-say h3 { font-family: var(--font-heading); font-size: 1.15rem; font-weight: 800; line-height: 1.3; color: #14210c; }
    .rfx-say p { margin-top: .35rem; font-size: .88rem; line-height: 1.55; color: #4b5563; }
    .rfx-counts { display: flex; flex-wrap: wrap; gap: .4rem; margin-top: .7rem; }
    .rfx-counts span { display: inline-flex; align-items: center; gap: .35rem; padding: .3rem .65rem; border-radius: 999px; font-size: .76rem; font-weight: 800; background: #f5f7f2; color: #374151; }
    .rfx-counts i { width: .6rem; height: .6rem; border-radius: 3px; }
    @media (max-width: 559.98px) { .rfx-counts { justify-content: center; } }
    .rfx-runs { display: grid; gap: .45rem; padding: 0 1rem 1rem; }
    .rfx-run { display: flex; align-items: flex-start; gap: .6rem; padding: .65rem .75rem; border-radius: .85rem; font-size: .84rem; line-height: 1.45; color: #374151; background: #fef2f2; box-shadow: inset 0 0 0 1px #fecaca; cursor: pointer;
        transition: transform .2s cubic-bezier(.22,1,.36,1); animation: rfxIn .4s cubic-bezier(.22,1,.36,1) both; animation-delay: var(--d, 0s); }
    .rfx-run:hover { transform: translateX(3px); }
    .rfx-run b { color: #991b1b; }
    .rfx-run.is-thin { background: #fffbeb; box-shadow: inset 0 0 0 1px #fde68a; } .rfx-run.is-thin b { color: #92400e; }
    .rfx-run.is-late { background: #f5f3ff; box-shadow: inset 0 0 0 1px #ddd6fe; } .rfx-run.is-late b { color: #5b21b6; }
    .rfx-run em { flex: none; font-style: normal; }
    /* The season chart. */
    .rfx-chart { padding: .2rem 1rem 1rem; }
    .rfx-chart-w { position: relative; overflow-x: auto; scrollbar-width: thin; }
    .rfx-chart svg { display: block; min-width: 40rem; width: 100%; height: auto; overflow: visible; }
    .rfx-chart .cov-l { fill: none; stroke: #2f5219; stroke-width: 2; stroke-linejoin: round; }
    .rfx-chart .rib { transform-box: fill-box; transform-origin: left; animation: rfxRib .9s cubic-bezier(.22,1,.36,1) both; animation-delay: calc(.3s + var(--k, 0) * 40ms); }
    @keyframes rfxRib { from { transform: scaleX(0); opacity: 0; } }
    .rfx-chart .try-l { fill: none; stroke: #e0a800; stroke-width: 2; stroke-dasharray: 5 4; }
    .rfx-chart .reveal { clip-path: inset(0 100% 0 0); animation: rfxWipe 1.3s cubic-bezier(.45,0,.25,1) .15s forwards; }
    @keyframes rfxWipe { to { clip-path: inset(0 0 0 0); } }
    .rfx-chart .grid { stroke: #eef3e8; stroke-width: 1; }
    .rfx-chart .stg { font-size: 9px; font-weight: 800; fill: #4b5563; }
    .rfx-chart .mo { font-size: 9px; font-weight: 800; fill: #9ca3af; }
    .rfx-chart .today { stroke: #2563eb; stroke-width: 1.5; stroke-dasharray: 3 3; }
    .rfx-chart .harv { stroke: #b45309; stroke-width: 1.5; }
    .rfx-chart .app { stroke: #fff; stroke-width: 1.5; cursor: pointer; transition: r .2s; }
    .rfx-chart .app:hover { r: 6; }
    /* The calendar. */
    .rfx-cal { display: grid; gap: .4rem 1.1rem; grid-template-columns: repeat(auto-fill, minmax(min(100%, 21rem), 1fr)); padding: .2rem .8rem 1rem; }
    .rfx-month { margin-top: .8rem; min-width: 0; }
    .rfx-month-h { display: flex; align-items: baseline; gap: .6rem; padding: 0 .2rem .4rem; }
    .rfx-month-h b { font-family: var(--font-heading); font-size: 1rem; font-weight: 800; color: #14210c; }
    .rfx-month-h small { font-size: .74rem; color: #6b7280; }
    .rfx-wk { display: grid; grid-template-columns: repeat(7, minmax(0, 1fr)); gap: .3rem; }
    .rfx-wd { padding: 0 .2rem .2rem; font-size: .62rem; font-weight: 900; letter-spacing: .06em; text-align: center; text-transform: uppercase; color: #9ca3af; }
    .rfx-cell { position: relative; min-height: 3.5rem; padding: .3rem .35rem; border-radius: .7rem; overflow: hidden; text-align: left; background: #fff; box-shadow: inset 0 0 0 1px #e9efe3;
        transition: transform .2s cubic-bezier(.22,1,.36,1), box-shadow .2s; }
    .rfx-cell:hover { transform: translateY(-2px); box-shadow: inset 0 0 0 1.5px #a9c98c, 0 10px 18px -12px rgb(20 33 12 / .5); z-index: 1; }
    .rfx-cell.is-out { background: transparent; box-shadow: none; pointer-events: none; }
    .rfx-fill { position: absolute; left: 0; right: 0; bottom: 0; height: 0; overflow: hidden; background: linear-gradient(180deg, #86c25a, #4a7c2a);
        transition: height .7s cubic-bezier(.22,1,.36,1); transition-delay: calc(var(--k, 0) * 6ms); }
    .rfx-cal.is-in .rfx-fill { height: var(--h, 0%); }
    /* The fill's colour is what the strongest product is doing that day; the cell's edge is what it means for the crop. */
    .rfx-cell[data-p=absorb] .rfx-fill { background: var(--ph-absorb); }
    .rfx-cell[data-p=strong] .rfx-fill { background: var(--ph-strong); }
    .rfx-cell[data-p=easing] .rfx-fill { background: var(--ph-easing); }
    .rfx-cell[data-p=fading] .rfx-fill { background: var(--ph-fading); }
    .rfx-cell[data-p=absorb] .rfx-fill::before { content: ''; position: absolute; left: 0; right: 0; top: -100%; height: 300%;
        background: linear-gradient(0deg, transparent 38%, rgb(255 255 255 / .42) 50%, transparent 62%); animation: rfxRise 2.4s linear infinite; }
    @keyframes rfxRise { from { transform: translateY(33%); } to { transform: translateY(-33%); } }
    /* Still in the soil: an earth band at the foot of the day (urea turning into ammonium, a salt dissolving, manure breaking down). */
    .rfx-soil { position: absolute; left: 0; right: 0; bottom: 0; height: 30%; transform: scaleY(0); transform-origin: bottom;
        background: radial-gradient(circle, rgb(255 236 200 / .55) 0 1px, transparent 1.6px) 0 0 / 6px 5px, var(--ph-soil); animation: rfxSoil 7s linear infinite;
        transition: transform .6s cubic-bezier(.22,1,.36,1); transition-delay: calc(var(--k, 0) * 6ms + .15s); }
    .rfx-cal.is-in .rfx-soil { transform: none; }
    .rfx-cell[data-p=soil] .rfx-soil { height: 42%; }
    @keyframes rfxSoil { to { background-position: 12px -10px, 0 0; } }
    .rfx-cell[data-s=gap] { background: repeating-linear-gradient(135deg, #fff1f1 0 6px, #ffe1e1 6px 12px); box-shadow: inset 0 0 0 1.5px #fca5a5; }
    .rfx-cell[data-s=thin] { box-shadow: inset 0 0 0 2px #f59e0b; }
    .rfx-cell[data-s=late], .rfx-cell[data-s=extra] { box-shadow: inset 0 0 0 2px #a78bfa; }
    .rfx-cell[data-s=late] .rfx-fill::after, .rfx-cell[data-s=extra] .rfx-fill::after { content: ''; position: absolute; inset: 0; background: repeating-linear-gradient(135deg, rgb(124 58 237 / .5) 0 3px, transparent 3px 7px); }
    .rfx-cell.is-today { box-shadow: inset 0 0 0 2px #2563eb; }
    .rfx-cell[data-t="1"]::after { content: ''; position: absolute; inset: 0; border-radius: inherit; box-shadow: inset 0 0 0 2px #f5c518; pointer-events: none; animation: rfxTry 1.8s ease-in-out infinite; }
    @keyframes rfxTry { 50% { box-shadow: inset 0 0 0 2px rgb(245 197 24 / .35); } }
    .rfx-n { position: relative; z-index: 1; display: block; font-size: .78rem; font-weight: 900; color: #14210c; }
    .rfx-dl { position: relative; z-index: 1; display: block; font-size: .56rem; font-weight: 800; color: #6b7280; white-space: nowrap; }
    .rfx-cell.is-full .rfx-n { color: #fff; } .rfx-cell.is-full .rfx-dl { color: rgb(255 255 255 / .85); }
    .rfx-cell.is-full .rfx-n, .rfx-cell.is-full .rfx-dl { transition: color .4s ease .5s; }
    .rfx-crit { position: absolute; z-index: 1; top: .32rem; right: .3rem; display: flex; gap: 2px; }
    .rfx-crit i { width: .32rem; height: .32rem; border-radius: 999px; background: #d1d5db; }
    .rfx-crit.c1 i:nth-child(-n+1), .rfx-crit.c2 i:nth-child(-n+2), .rfx-crit.c3 i:nth-child(-n+3) { background: #dc2626; }
    .rfx-crit.c1 i:nth-child(-n+1) { background: #f59e0b; } .rfx-crit.c2 i:nth-child(-n+2) { background: #f97316; }
    /* What went on that day: a small badge per product, its mark saying how (granules, a spray, organic), filled when
       done, outlined while planned, gold for a try. */
    .rfx-ap { position: absolute; z-index: 2; right: .25rem; bottom: .25rem; display: flex; align-items: center; }
    .rfx-ap i { display: grid; place-items: center; width: 1.15rem; height: 1.15rem; border-radius: 999px; color: #fff; background: #1f3a10; box-shadow: 0 0 0 1.5px #fff, 0 3px 6px -2px rgb(0 0 0 / .45);
        animation: rfxPop .4s cubic-bezier(.22,1,.36,1) both; animation-delay: calc(.4s + var(--k, 0) * 6ms); }
    .rfx-ap i + i { margin-left: -.35rem; }
    .rfx-ap i svg { width: .72rem; height: .72rem; fill: currentColor; }
    .rfx-ap i.is-plan { color: #1f3a10; background: #fff; box-shadow: inset 0 0 0 1.5px #1f3a10, 0 0 0 1.5px #fff, 0 3px 6px -2px rgb(0 0 0 / .3); }
    .rfx-ap i.is-try { color: #5c4500; background: #f5c518; }
    .rfx-ap b { margin-left: .1rem; padding: 0 .22rem; border-radius: 999px; font-size: .52rem; font-weight: 900; line-height: 1rem; color: #1f3a10; background: #fff; box-shadow: 0 0 0 1px #d6e5c6; }
    @media (max-width: 639.98px) { .rfx-ap { right: .12rem; bottom: .12rem; } .rfx-ap i { width: .98rem; height: .98rem; } .rfx-ap i svg { width: .6rem; height: .6rem; } }
    /* Side by side. */
    .rfx-minis { display: grid; grid-template-columns: repeat(auto-fill, minmax(min(100%, 9.2rem), 1fr)); gap: .5rem; padding: .7rem 1rem .3rem; }
    @media (max-width: 639.98px) {
        .rfx-mini { gap: .45rem; padding: .5rem; } .rfx-mini svg { width: 2.3rem; height: 2.3rem; }
        .rfx-lane { grid-template-columns: 4.6rem minmax(0, 1fr); gap: .4rem; }
        .rfx-lanes .rfx-lane.is-head:first-child { display: none; }   /* the stage names do not fit a phone; the ticks on top say it */
    }
    .rfx-mini { display: flex; align-items: center; gap: .6rem; min-width: 0; padding: .55rem .65rem; border-radius: .95rem; text-align: left; background: #f8faf6; box-shadow: inset 0 0 0 1px #e3ecd9;
        transition: box-shadow .28s cubic-bezier(.22,1,.36,1), transform .28s cubic-bezier(.22,1,.36,1), background-color .28s; animation: rfxPop .35s cubic-bezier(.22,1,.36,1) both; animation-delay: calc(var(--k, 0) * 50ms); }
    .rfx-mini:hover { transform: translateY(-1px); }
    .rfx-mini.is-on { background: #fff; box-shadow: inset 0 0 0 2px var(--sc), 0 12px 24px -18px var(--sc); }
    .rfx-mini svg { flex: none; width: 2.8rem; height: 2.8rem; transform: rotate(-90deg); }
    .rfx-mini circle { fill: none; stroke-width: 5; }
    .rfx-mini .t { stroke: #e9efe3; }
    .rfx-mini .v { stroke-linecap: round; stroke-dasharray: 0 200; transition: stroke-dasharray 1s cubic-bezier(.22,1,.36,1); }
    .rfx-mini > span { min-width: 0; display: grid; }
    .rfx-mini b { font-family: var(--font-heading); font-size: 1.05rem; font-weight: 900; line-height: 1.1; color: #14210c; }
    .rfx-mini span span { font-size: .78rem; font-weight: 800; color: #374151; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .rfx-mini small { font-size: .68rem; color: #6b7280; }
    .rfx-lanes { display: grid; gap: .4rem; padding: .7rem 1rem .5rem; }
    .rfx-lane { display: grid; grid-template-columns: 5.4rem minmax(0, 1fr); gap: .55rem; align-items: center; animation: rfxIn .4s cubic-bezier(.22,1,.36,1) both; animation-delay: calc(.08s + var(--k, 0) * 60ms); }
    .rfx-lane-n { display: flex; align-items: center; gap: .35rem; min-width: 0; font-size: .72rem; font-weight: 800; line-height: 1.15; color: #4b5563; text-align: left; overflow: hidden; }
    .rfx-lane-n i { flex: none; width: .6rem; height: .6rem; border-radius: 999px; background: var(--sc); }
    .rfx-lane.is-on .rfx-lane-n { color: #14210c; }
    .rfx-lane-s { display: block; width: 100%; height: 2rem; border-radius: .45rem; background: #f3f6ef; cursor: pointer; clip-path: inset(0 100% 0 0 round .45rem);
        animation: rfxLane 1.1s cubic-bezier(.45,0,.25,1) forwards; animation-delay: calc(.15s + var(--k, 0) * 90ms); }
    @keyframes rfxLane { to { clip-path: inset(0 0 0 0 round .45rem); } }
    .rfx-lane.is-on .rfx-lane-s { box-shadow: 0 0 0 2px var(--sc); }
    .rfx-lane.is-head { animation: none; }
    .rfx-stg { display: flex; overflow: hidden; font-size: .58rem; font-weight: 800; color: #6b7280; }
    .rfx-stg span { flex: none; min-width: 0; padding: 0 .2rem; border-left: 1px solid #dfe8d5; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .rfx-mo { position: relative; height: .9rem; font-size: .6rem; font-weight: 800; color: #9ca3af; }
    .rfx-mo span { position: absolute; top: 0; padding-left: .15rem; border-left: 1px solid #dfe8d5; }
    .rfx-day-cmp { display: grid; gap: .45rem; margin-top: .7rem; padding-top: .6rem; border-top: 1px solid #eef3e8; }
    .rfx-day-cmp > div { display: grid; grid-template-columns: auto minmax(0, 1fr) 4.5rem; align-items: center; gap: .1rem .45rem; font-size: .8rem; }
    .rfx-day-cmp > div > i { width: .6rem; height: .6rem; border-radius: 999px; background: var(--sc); }
    .rfx-day-cmp b { min-width: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; color: #14210c; }
    .rfx-day-cmp .rfx-meter { margin: 0; height: .45rem; }
    .rfx-day-cmp small { grid-column: 2 / -1; font-size: .72rem; color: #6b7280; }
    @media (max-width: 639.98px) {
        .rfx-cal { padding: .2rem .4rem .8rem; }
        .rfx-wk { gap: .18rem; }
        .rfx-cell { min-height: 3.6rem; padding: .2rem .22rem; border-radius: .5rem; }
        .rfx-n { font-size: .7rem; }
        .rfx-dl { font-size: .48rem; }
        .rfx-crit i { width: .26rem; height: .26rem; }
    }
    .rfx-legend { display: flex; flex-wrap: wrap; align-items: center; gap: .4rem .8rem; padding: .3rem 1rem 1rem; font-size: .74rem; font-weight: 700; color: #4b5563; }
    .rfx-legend + .rfx-legend { margin-top: -.5rem; }
    .rfx-legend span { display: inline-flex; align-items: center; gap: .35rem; }
    .rfx-legend i { display: inline-block; width: .9rem; height: .9rem; border-radius: .25rem; }
    .rfx-leg-h { flex: 0 0 100%; font-size: .64rem; font-weight: 900; letter-spacing: .08em; text-transform: uppercase; color: #9ca3af; }
    /* The life of one application, left to right. */
    .rfx-flow { gap: .35rem .3rem; }
    .rfx-flow > span:not(.rfx-leg-h) { padding: .25rem .55rem .25rem .3rem; border-radius: 999px; background: #f5f7f2; animation: rfxPop .32s cubic-bezier(.22,1,.36,1) both; animation-delay: calc(var(--k, 0) * 60ms); }
    .rfx-flow > em { font-style: normal; font-weight: 900; color: #c0c8b8; }
    .rfx-leg-cell { width: 1.1rem !important; height: .9rem; border-radius: .25rem; background: #fff; }
    .rfx-legend .rfx-crit i { width: .38rem; height: .38rem; border-radius: 999px; }
    .rfx-leg-ap .rfx-ap { position: static; }
    .rfx-leg-ap .rfx-ap i { display: grid; width: 1.15rem; height: 1.15rem; border-radius: 999px; animation: none; }
    /* The products. */
    .rfx-apps { display: grid; gap: .55rem; padding: 0 1rem 1rem; }
    .rfx-app { display: grid; grid-template-columns: auto minmax(0, 1fr); gap: .7rem; padding: .75rem .8rem; border-radius: .95rem; background: #f8faf6; box-shadow: inset 0 0 0 1px #e3ecd9;
        animation: rfxIn .4s cubic-bezier(.22,1,.36,1) both; animation-delay: var(--d, 0s); }
    .rfx-app.is-try { background: #fffbe8; box-shadow: inset 0 0 0 1.5px #f5c518; }
    .rfx-app-d { display: grid; place-items: center; width: 3.1rem; padding: .35rem 0; border-radius: .75rem; background: #fff; box-shadow: inset 0 0 0 1px #e3ecd9; text-align: center; }
    .rfx-app-d b { font-family: var(--font-heading); font-size: 1.1rem; font-weight: 900; line-height: 1; color: #14210c; }
    .rfx-app-d small { font-size: .6rem; font-weight: 900; text-transform: uppercase; color: #6b7280; }
    .rfx-app-t b { font-size: .9rem; color: #14210c; }
    .rfx-app-t small { display: block; margin-top: .1rem; font-size: .76rem; color: #6b7280; line-height: 1.4; }
    .rfx-bar { position: relative; height: .6rem; margin-top: .5rem; border-radius: 999px; background: #e9efe3; overflow: hidden; }
    .rfx-segs { position: absolute; inset: 0; clip-path: inset(0 100% 0 0); animation: rfxWipe 1s cubic-bezier(.45,0,.25,1) forwards; animation-delay: calc(var(--d, 0s) + .2s); }
    .rfx-segs i { position: absolute; top: 0; bottom: 0; background: var(--ph, var(--ph-strong)); }
    .rfx-segs i:first-child { border-radius: 999px 0 0 999px; } .rfx-segs i:last-child { border-radius: 0 999px 999px 0; } .rfx-segs i:only-child { border-radius: 999px; }
    .rfx-phs { display: flex; flex-wrap: wrap; gap: .3rem .7rem; margin-top: .45rem; font-size: .72rem; font-weight: 700; color: #4b5563; }
    .rfx-phs span { display: inline-flex; align-items: center; gap: .3rem; }
    .rfx-phs .rfx-sw { width: .7rem; height: .7rem; }
    .rfx-phs small { font-weight: 600; color: #9ca3af; }
    .rfx-trace { display: block; margin-top: .35rem; padding: .35rem .55rem; border-radius: .6rem; font-size: .74rem; line-height: 1.4; color: #92400e; background: #fffbeb; }
    .rfx-app-n { margin-top: .4rem; font-size: .78rem; line-height: 1.45; color: #4b5563; }
    .rfx-tag { display: inline-block; margin-left: .3rem; padding: .05rem .45rem; border-radius: 999px; font-size: .64rem; font-weight: 900; vertical-align: middle; color: #374151; background: #e5e7eb; }
    .rfx-tag.is-done { color: #14532d; background: #dcfce7; } .rfx-tag.is-try { color: #5c4500; background: #fde68a; } .rfx-tag.is-est { color: #6b7280; background: #f3f4f6; }
    .rfx-other { padding: 0 1rem 1rem; }
    .rfx-other summary { cursor: pointer; font-size: .82rem; font-weight: 800; color: #4b5563; }
    .rfx-other ul { margin: .5rem 0 0; padding: 0; list-style: none; display: grid; gap: .3rem; }
    .rfx-other li { font-size: .8rem; color: #6b7280; }
    .rfx-empty { padding: 2.5rem 1rem; text-align: center; }
    .rfx-empty img { width: 5rem; height: 5rem; margin: 0 auto; }
    .rfx-empty b { display: block; margin-top: .8rem; font-family: var(--font-heading); font-size: 1.1rem; color: #14210c; }
    .rfx-empty p { margin-top: .35rem; font-size: .88rem; color: #4b5563; }
    /* The foot. */
    .rfx-foot { display: flex; align-items: center; gap: .8rem; padding: .7rem 1rem calc(.7rem + env(safe-area-inset-bottom)); background: #fff; border-top: 1px solid #e3ecd9; }
    .rfx-foot-note { flex: 1; min-width: 0; font-size: .74rem; line-height: 1.4; color: #6b7280; }
    @media (max-width: 559.98px) { .rfx-foot-note { display: none; } .rfx-foot .rfx-btn { flex: 1; } }
    /* Saved, and one day opened. */
    .rfx-drawer { position: absolute; z-index: 6; top: 0; right: 0; bottom: 0; width: min(100%, 24rem); display: flex; flex-direction: column; background: #fff; box-shadow: -20px 0 50px -20px rgb(0 0 0 / .4);
        transform: translateX(100%); transition: transform .32s cubic-bezier(.22,1,.36,1); }
    .rfx-drawer.is-on { transform: none; }
    .rfx-drawer-h { display: flex; align-items: center; justify-content: space-between; padding: .9rem 1rem; border-bottom: 1px solid #eef3e8; color: #14210c; }
    .rfx-drawer-h .rfx-x { background: #f3f4f6; color: #374151; }
    .rfx-drawer-b { flex: 1; overflow: auto; padding: .8rem; display: grid; align-content: start; gap: .5rem; }
    .rfx-sv { display: flex; align-items: center; gap: .6rem; padding: .7rem .75rem; border-radius: .9rem; background: #f8faf6; box-shadow: inset 0 0 0 1px #e3ecd9; text-align: left; transition: box-shadow .2s, transform .2s cubic-bezier(.22,1,.36,1); }
    .rfx-sv:hover { box-shadow: inset 0 0 0 1.5px #86c25a; transform: translateX(-2px); }
    .rfx-sv b { display: block; font-size: .86rem; color: #14210c; }
    .rfx-sv small { display: block; font-size: .72rem; color: #6b7280; }
    .rfx-sv-x { margin-left: auto; flex: none; display: grid; place-items: center; width: 1.9rem; height: 1.9rem; border-radius: 999px; color: #9ca3af; }
    .rfx-sv-x:hover { color: #dc2626; background: #fef2f2; }
    .rfx-sv-x svg { width: .95rem; height: .95rem; }
    .rfx-day { position: absolute; z-index: 7; left: 50%; bottom: 1rem; width: min(calc(100% - 2rem), 26rem); padding: 1rem; border-radius: 1.2rem; background: #fff; transform: translateX(-50%);
        box-shadow: 0 0 0 1px #e3ecd9, 0 30px 60px -20px rgb(0 0 0 / .45); animation: rfxUp .3s cubic-bezier(.22,1,.36,1) both; }
    @keyframes rfxUp { from { opacity: 0; transform: translate(-50%, 16px); } }
    .rfx-day-h { display: flex; align-items: flex-start; gap: .6rem; }
    .rfx-day-h b { display: block; font-family: var(--font-heading); font-size: 1.05rem; color: #14210c; }
    .rfx-day-h small { display: block; font-size: .78rem; color: #6b7280; }
    .rfx-day-h .rfx-x { margin-left: auto; background: #f3f4f6; color: #374151; width: 2rem; height: 2rem; }
    .rfx-day p { margin-top: .5rem; font-size: .84rem; line-height: 1.5; color: #374151; }
    .rfx-day ul { margin: .5rem 0 0; padding: 0; list-style: none; display: grid; gap: .3rem; }
    .rfx-day li { font-size: .8rem; color: #374151; }
    .rfx-meter { position: relative; height: .6rem; margin-top: .6rem; border-radius: 999px; background: #eef3e8; overflow: hidden; }
    .rfx-meter i { position: absolute; left: 0; top: 0; bottom: 0; border-radius: 999px; transition: width .5s cubic-bezier(.22,1,.36,1); }
    html.dark .rfx-box, html.dark .rfx-controls, html.dark .rfx-card, html.dark .rfx-foot, html.dark .rfx-drawer, html.dark .rfx-day { background: #151b12; color: #e8efe1; }
    html.dark .rfx-box { background: #10150d; }
    html.dark .rfx-cell { background: #151b12; box-shadow: inset 0 0 0 1px #2b3a1c; }
    html.dark .rfx-n, html.dark .rfx-say h3, html.dark .rfx-app-t b, html.dark .rfx-month-h b, html.dark .rfx-ring-n b, html.dark .rfx-day-h b { color: #e8efe1; }
    html.dark .rfx-app, html.dark .rfx-sv { background: #1b2416; box-shadow: inset 0 0 0 1px #2b3a1c; }
    html.dark .rfx-chip { background: #151b12; color: #cfe0c0; box-shadow: inset 0 0 0 1.5px #2b3a1c; }
    html.dark .rfx-chip.is-on { background: #4a7c2a; color: #fff; }
    html.dark .rfx-tabs { background: #1b2416; } html.dark .rfx-tabs button.is-on { background: #2b3a1c; color: #fff; }
    html.dark .rfx-flow > span:not(.rfx-leg-h) { background: #1b2416; color: #cfe0c0; }
    html.dark .rfx-legend, html.dark .rfx-phs { color: #b8c7ab; }
    html.dark .rfx-leg-cell { background: #151b12; }
    html.dark .rfx-mini { background: #1b2416; box-shadow: inset 0 0 0 1px #2b3a1c; } html.dark .rfx-mini.is-on { background: #151b12; box-shadow: inset 0 0 0 2px var(--sc); }
    html.dark .rfx-mini b, html.dark .rfx-day-cmp b { color: #e8efe1; } html.dark .rfx-mini span span, html.dark .rfx-lane-n { color: #cfe0c0; }
    html.dark .rfx-lane-s { background: #1b2416; }
    html.dark .rfx-trace { color: #fcd34d; background: rgb(245 158 11 / .12); }
    @media (prefers-reduced-motion: reduce) {
        .rfx *, .rfx *::before, .rfx *::after { animation: none !important; transition: none !important; }
        .rfx-chart .reveal, .rfx-segs { clip-path: none; }
        .rfx-fill { height: var(--h, 0%) !important; }
        .rfx-soil { transform: none !important; }
        .rfx-lane-s { clip-path: none !important; }
    }
</style>
@endpush
@push('scripts')
<script>
(() => {
    const M = document.getElementById('rfxModal');
    if (!M) return;
    // The board's shell can move this under a transformed parent; the modal belongs to the body.
    document.body.appendChild(M);
    const U = JSON.parse(M.dataset.u);
    const SID = M.dataset.sid;
    const $ = (s, r = M) => r.querySelector(s);
    const $$ = (s, r = M) => [...r.querySelectorAll(s)];
    const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const calm = matchMedia('(prefers-reduced-motion: reduce)').matches;
    const MON = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    const DOW = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
    const D = (s) => { const [y, m, d] = s.split('-').map(Number); return new Date(y, m - 1, d); };
    const say = (s) => { const d = D(s); return MON[d.getMonth()] + ' ' + d.getDate(); };
    const sayY = (s) => { const d = D(s); return MON[d.getMonth()] + ' ' + d.getDate() + ', ' + d.getFullYear(); };
    const iso = (d) => d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
    const STATE = { ok: ['Covered', '#4a7c2a'], thin: ['Thin', '#f59e0b'], gap: ['Gap: critical, not covered', '#dc2626'], late: ['Late: the crop no longer takes it', '#8b5cf6'],
        extra: ['Covered when it matters little', '#8b5cf6'], watch: ['Worth watching', '#86c25a'], pre: ['Before the crop', '#86c25a'], none: ['Nothing needed', '#d1d5db'] };
    const CRIT = ['Not needed now', 'Low', 'Matters', 'Critical'];
    const MAX_TRIES = 8;
    // What an application is doing, in its order, and each phase's colour where CSS cannot reach (the chart's SVG).
    const PH_ORDER = ['soil', 'absorb', 'strong', 'easing', 'fading'];
    const PHC = { soil: '#a8723f', absorb: '#22b09a', strong: '#3b7a1f', easing: '#86c25a', fading: '#cfe8b8' };
    const EL = { P2O5: 'P₂O₅', K2O: 'K₂O' };
    const tabOf = (key) => key.startsWith('t:') ? 'prot' : key.startsWith('p:') ? 'pest' : ((S.opts?.nutrients || []).find((n) => n.key === key) || {}).micro ? 'micro' : 'nut';
    const S = { opts: null, lot: null, check: 'n:N', checks: ['n:N'], tab: 'nut', tries: [], res: null, saved: null, picked: null, q: '', busy: false };

    /* ---------------- open and close ---------------- */
    const open = async () => {
        M.hidden = false;
        document.documentElement.classList.add('rfx-lock');
        requestAnimationFrame(() => requestAnimationFrame(() => M.classList.add('is-on')));
        if (!S.opts) await boot();
        else if (!S.res) run();
    };
    const close = () => {
        M.classList.remove('is-on');
        document.documentElement.classList.remove('rfx-lock');
        setTimeout(() => { M.hidden = true; }, calm ? 0 : 320);
        hideDay();
    };
    document.addEventListener('click', (e) => {
        if (e.target.closest('#rangeEffectBtn, #rangeEffectMobileBtn, [data-range-effect-open]')) { e.preventDefault(); open(); }
    });
    M.addEventListener('click', (e) => {
        if (e.target.closest('[data-rfx-close]')) close();
        if (e.target.closest('[data-rfx-saved-open]')) openSaved();
        if (e.target.closest('[data-rfx-saved-close]')) $('[data-rfx-drawer]').classList.remove('is-on');
    });
    document.addEventListener('keydown', (e) => {
        if (e.key !== 'Escape' || M.hidden) return;
        if (!$('[data-rfx-day]').hidden) hideDay();
        else if ($('[data-rfx-drawer]').classList.contains('is-on')) $('[data-rfx-drawer]').classList.remove('is-on');
        else close();
    });

    /* ---------------- the controls ---------------- */
    const boot = async () => {
        try {
            const r = await window.api(U.options);
            S.opts = r.data;
            S.lot = (S.opts.lots[0] || {}).id || null;
            paintSavedCount();
            paintLots();
            paintPicks();
            if (!S.lot) { body(empty('Add a lot first', 'The calendar reads one lot at a time: its crop, its planting day and the products on its tasks.')); return; }
            run();
        } catch (err) {
            if (err.tierLock) { close(); return; }
            body(empty('The calendar could not open', err.message || 'Please try again.'));
        }
    };
    const lotOf = () => (S.opts?.lots || []).find((l) => l.id === S.lot) || null;
    const paintLots = () => {
        $('[data-rfx-lots]').innerHTML = (S.opts.lots || []).map((l) => `<button type="button" class="rfx-chip ${l.id === S.lot ? 'is-on' : ''}" data-lot="${l.id}">${esc(l.icon)} ${esc(l.name)} <small>${esc(l.crop.split(',')[0])}</small></button>`).join('');
    };
    $('[data-rfx-lots]').addEventListener('click', (e) => {
        const b = e.target.closest('[data-lot]');
        if (!b) return;
        S.lot = Number(b.dataset.lot);
        // A named pest of one crop means nothing on another.
        const mine = (k) => !k.startsWith('p:') || (lotOf()?.threats || []).some((t) => 'p:' + t.slug === k);
        S.checks = S.checks.filter(mine);
        if (!S.checks.length) S.checks = ['t:insect'];
        if (!S.checks.includes(S.check)) S.check = S.checks[0];
        paintLots();
        paintPicks();
        run();
    });
    const paintPicks = () => {
        const o = S.opts;
        $$('[data-rfx-tabs] button').forEach((b) => b.classList.toggle('is-on', b.dataset.tab === S.tab));
        let html = '';
        if (S.tab === 'nut' || S.tab === 'micro') {
            html = o.nutrients.filter((n) => (S.tab === 'micro') === n.micro).map((n, k) => `<button type="button" class="rfx-chip ${on(n.key)}" data-check="${n.key}" aria-pressed="${S.checks.includes(n.key)}" style="--k:${k}"><b>${esc(n.short)}</b> <small>${esc(n.name)}</small></button>`).join('');
        } else if (S.tab === 'prot') {
            html = o.threats.map((t, k) => `<button type="button" class="rfx-chip ${on(t.key)}" data-check="${t.key}" aria-pressed="${S.checks.includes(t.key)}" style="--k:${k}">${t.icon} ${esc(t.name)}</button>`).join('');
        } else {
            const list = lotOf()?.threats || [];
            html = list.length
                ? `<input type="search" data-rfx-pest-q placeholder="Find a pest or disease" value="${esc(S.q)}">` + list.filter((t) => !S.q || (t.name + ' ' + t.local).toLowerCase().includes(S.q.toLowerCase()))
                    .map((t, k) => `<button type="button" class="rfx-chip ${on('p:' + t.slug)}" data-check="p:${esc(t.slug)}" aria-pressed="${S.checks.includes('p:' + t.slug)}" style="--k:${Math.min(k, 20)}" title="${esc(t.local)}">${esc(t.name)} <small>${esc(t.kind)}</small></button>`).join('')
                : '<small style="color:#6b7280">This lot\'s crop has no pests or diseases in our book yet. Use Protection instead.</small>';
        }
        if (S.checks.length === 1) html += '<small class="rfx-hint">Tap more to compare them</small>';
        $('[data-rfx-picks]').innerHTML = html;
        paintSel();
    };
    // Picked checks: each keeps the colour of its place in the pick, the one the calendar shows is filled.
    const SERIES = ['#3b7a1f', '#2563eb', '#d97706', '#9333ea', '#db2777', '#0891b2'];
    const colorOf = (key) => SERIES[Math.max(0, S.checks.indexOf(key))] || '#6b7280';
    const on = (key) => S.checks.includes(key) ? 'is-on' : '';
    const labelOf = (key) => {
        const o = S.opts || {};
        const n = (o.nutrients || []).find((x) => x.key === key);
        if (n) return n.short === n.name ? n.name : `${n.short} ${n.name}`;
        const t = (o.threats || []).find((x) => x.key === key);
        if (t) return `${t.icon} ${t.name}`;
        const p = (lotOf()?.threats || []).find((x) => 'p:' + x.slug === key);
        return p ? p.name : key;
    };
    const paintSel = () => {
        const row = $('[data-rfx-sel-row]');
        row.hidden = S.checks.length < 2;
        if (row.hidden) return;
        $('[data-rfx-sel]').innerHTML = S.checks.map((k, i) => `<span class="rfx-chip ${k === S.check ? 'is-on' : ''}" style="--sc:${SERIES[i]};--k:${i}" role="button" tabindex="0" data-focus="${esc(k)}" title="Show it in the calendar"><i></i>${esc(labelOf(k))}<button type="button" data-unpick="${esc(k)}" aria-label="Remove from the comparison"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" d="M6 6l12 12M18 6L6 18"/></svg></button></span>`).join('');
    };
    const focusCheck = (key) => {
        if (key === S.check || !S.checks.includes(key)) return;
        S.check = key;
        paintSel();
        run();
    };
    // A tap adds a check to the comparison (and shows it), or takes it out; one always stays.
    const togglePick = (key) => {
        if (S.checks.includes(key)) {
            if (S.checks.length === 1) return;
            S.checks = S.checks.filter((k) => k !== key);
            if (S.check === key) S.check = S.checks[S.checks.length - 1];
        } else {
            if (S.checks.length >= SERIES.length) { window.toast?.(`Up to ${SERIES.length} at a time. Take one out first.`, 'error'); return; }
            S.checks = [...S.checks, key];
            S.check = key;
        }
        // The chips stay where they are; only their marks change.
        $$('[data-rfx-picks] [data-check]').forEach((x) => { const k = x.dataset.check; x.classList.toggle('is-on', S.checks.includes(k)); x.setAttribute('aria-pressed', String(S.checks.includes(k))); });
        const hint = $('[data-rfx-picks] .rfx-hint');
        if (hint) hint.hidden = S.checks.length > 1;
        paintSel();
        run();
    };
    $('[data-rfx-sel]').addEventListener('click', (e) => {
        const x = e.target.closest('[data-unpick]');
        if (x) { togglePick(x.dataset.unpick); return; }
        const f = e.target.closest('[data-focus]');
        if (f) focusCheck(f.dataset.focus);
    });
    $('[data-rfx-tabs]').addEventListener('click', (e) => {
        const b = e.target.closest('[data-tab]');
        if (!b || b.dataset.tab === S.tab) return;
        S.tab = b.dataset.tab;
        S.q = '';
        paintPicks();
    });
    $('[data-rfx-picks]').addEventListener('click', (e) => {
        const b = e.target.closest('[data-check]');
        if (b) togglePick(b.dataset.check);
    });
    $('[data-rfx-picks]').addEventListener('input', (e) => {
        if (!e.target.matches('[data-rfx-pest-q]')) return;
        S.q = e.target.value;
        const at = e.target.selectionStart;
        paintPicks();
        const q = $('[data-rfx-pest-q]');
        q.focus();
        try { q.setSelectionRange(at, at); } catch (_) {}
    });

    /* ---------------- tries: a product you plan to apply ---------------- */
    const tryBox = $('[data-rfx-try]');
    const paintTries = () => {
        $('[data-rfx-tries]').innerHTML = S.tries.map((t, i) => `<span class="rfx-chip is-try">${t.method === 'foliar' ? '💧' : '🟤'} ${esc(t.name)} <small>${esc(say(t.date))}${t.method ? ' · ' + t.method : ''}</small><button type="button" data-untry="${i}" aria-label="Remove"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" d="M6 6l12 12M18 6L6 18"/></svg></button></span>`).join('');
        $('[data-rfx-try-open]').classList.toggle('is-full', S.tries.length >= MAX_TRIES);
    };
    $('[data-rfx-tries]').addEventListener('click', (e) => {
        const b = e.target.closest('[data-untry]');
        if (!b) return;
        S.tries.splice(Number(b.dataset.untry), 1);
        paintTries();
        run();
    });
    $('[data-rfx-try-open]').addEventListener('click', () => {
        if (S.tries.length >= MAX_TRIES && tryBox.hidden) { window.toast?.(`Up to ${MAX_TRIES} tries at a time. Take one out first.`, 'error'); return; }
        tryBox.hidden = !tryBox.hidden;
        if (!tryBox.hidden) { resetTry(); setTimeout(() => $('[data-rfx-q]').focus(), 60); }
    });
    const resetTry = () => {
        S.picked = null;
        $('[data-rfx-q]').value = '';
        $('[data-rfx-results]').innerHTML = '';
        $('[data-rfx-picked]').hidden = true;
        $('[data-rfx-look]').hidden = true;
        $('[data-rfx-date]').value = iso(new Date());
        $('[data-rfx-qty]').value = '';
        const ha = lotOf()?.ha;
        $('[data-rfx-ha]').textContent = ha ? `(${Math.round(ha * 100) / 100} ha, optional)` : '(optional)';
    };
    // Granular or foliar: the pill slides to the pick; a unit that cannot go with it changes too.
    const setHow = (m, fixUnit = true) => {
        if (S.picked) S.picked.method = m;
        const box = $('[data-rfx-how]');
        box.dataset.on = m;
        $$('[data-how]', box).forEach((b) => b.setAttribute('aria-checked', String(b.dataset.how === m)));
        if (!fixUnit) return;
        const u = $('[data-rfx-unit]');
        if (m === 'granular' && ['L', 'mL'].includes(u.value)) u.value = 'kg';
        if (m === 'foliar' && u.value === 'bags') u.value = 'kg';
    };
    $('[data-rfx-how]').addEventListener('click', (e) => { const b = e.target.closest('[data-how]'); if (b) setHow(b.dataset.how); });
    const typeIcon = (t) => ({ insecticide: '🐛', fungicide: '🍄', herbicide: '🌿', molluscicide: '🐌', rodenticide: '🐀', nematicide: '🪱', acaricide: '🕷️', foliar: '💧', soluble: '💧', granular: '🧪', biostimulant: '🌱', biological: '🦠', amendment: '🪨' }[t] || '🧴');
    let qTimer = 0;
    $('[data-rfx-q]').addEventListener('input', () => {
        clearTimeout(qTimer);
        const q = $('[data-rfx-q]').value.trim();
        $('[data-rfx-picked]').hidden = true;
        if (q.length < 2) { $('[data-rfx-results]').innerHTML = ''; $('[data-rfx-look]').hidden = true; return; }
        qTimer = setTimeout(async () => {
            try {
                const lot = lotOf();
                const r = await window.api(U.products + '&q=' + encodeURIComponent(q) + '&crop=' + encodeURIComponent(lot?.cropKey || ''));
                const items = r.data.items || [];
                const ing = r.data.ingredient;
                let html = items.map((p, k) => `<button type="button" class="rfx-res" data-pid="${p.id}" style="--k:${k}"><em>${typeIcon(p.t)}</em><span><b>${esc(p.b)}</b><small>${esc([p.type, p.grade, (p.ai || []).join(' + '), p.c].filter(Boolean).join(' · '))}</small></span></button>`).join('');
                if (ing) html = `<button type="button" class="rfx-res" data-ai="${esc(ing.name)}"><em>⚗️</em><span><b>${esc(ing.name)}</b><small>The active ingredient on its own${ing.known ? '' : ' · days estimated from its group'}</small></span></button>` + html;
                $('[data-rfx-results]').innerHTML = html || '<small style="color:#6b7280;padding:.3rem">Nothing in our book by that name.</small>';
                S.found = { items, ing };
                const look = $('[data-rfx-look]');
                look.hidden = false;
                $('[data-rfx-look-name]').value = items.length ? '' : q;
                paintLookWhy();
            } catch (err) { $('[data-rfx-results]').innerHTML = `<small style="color:#b91c1c">${esc(err.message)}</small>`; }
        }, 260);
    });
    $('[data-rfx-results]').addEventListener('click', (e) => {
        const b = e.target.closest('[data-pid], [data-ai]');
        if (!b) return;
        if (b.dataset.pid) {
            const p = S.found.items.find((x) => String(x.id) === b.dataset.pid);
            S.picked = { productId: p.id, name: p.b, line: [p.type, p.grade, (p.ai || []).join(' + ')].filter(Boolean).join(' · '), t: p.t, granular: !!p.fit?.granular && !p.fit?.spray };
        } else {
            S.picked = { ai: b.dataset.ai, name: b.dataset.ai, line: 'The active ingredient on its own', t: '' };
        }
        showPicked();
    });
    const showPicked = () => {
        const p = S.picked;
        $('[data-rfx-picked-p]').innerHTML = `<em style="font-style:normal;font-size:1.3rem">${typeIcon(p.t)}</em><span><b style="font-size:.92rem;color:#14210c">${esc(p.name)}</b><small style="display:block;font-size:.76rem;color:#6b7280">${esc(p.line)}</small></span>`;
        $('[data-rfx-unit]').value = p.granular || ['granular', 'amendment'].includes(p.t) ? 'bags' : (['insecticide', 'fungicide', 'herbicide', 'foliar', 'soluble'].includes(p.t) ? 'mL' : 'kg');
        // A spray or a leaf feed starts as foliar; fertilizer, salts, baits and anything else start as granular.
        setHow(p.method || (['insecticide', 'fungicide', 'herbicide', 'acaricide', 'nematicide', 'foliar', 'soluble', 'biostimulant', 'biological'].includes(p.t) && !p.granular ? 'foliar' : 'granular'), false);
        $('[data-rfx-results]').innerHTML = '';
        $('[data-rfx-look]').hidden = true;
        $('[data-rfx-picked]').hidden = false;
    };
    $('[data-rfx-try-cancel]').addEventListener('click', () => { tryBox.hidden = true; });
    $('[data-rfx-try-add]').addEventListener('click', () => {
        const p = S.picked;
        const date = $('[data-rfx-date]').value;
        if (!p || !date) { window.toast?.('Pick the day you would apply it.', 'error'); return; }
        S.tries.push({ productId: p.productId || null, ai: p.ai || null, name: p.name, date, qty: Number($('[data-rfx-qty]').value) || 0, unit: $('[data-rfx-unit]').value, method: p.method || 'granular' });
        tryBox.hidden = true;
        paintTries();
        run();
    });

    /* ---------------- Anee looks it up ---------------- */
    const paintLookWhy = () => {
        const L = S.opts.lookup || {};
        const btn = $('[data-rfx-look-go]');
        btn.disabled = !L.canUse;
        btn.textContent = L.canUse ? `Look it up · ${L.price} credits` : 'Look it up';
        $('[data-rfx-look-why]').textContent = L.canUse ? (L.unlimited ? '' : `You have ${Math.floor(L.balance)} credits. Nothing is charged if she cannot finish.`) : (L.whyNot || '');
    };
    $('[data-rfx-look-go]').addEventListener('click', async () => {
        const name = $('[data-rfx-look-name]').value.trim(), ai = $('[data-rfx-look-ai]').value.trim();
        if (!name && !ai) { window.toast?.('Type the product\'s name, its active ingredient, or both.', 'error'); return; }
        const L = S.opts.lookup;
        window.aneeWait?.show({ title: 'Anee is looking it up…', sub: 'The label, the maker and how long it keeps working. About a minute.', lines: ['Reading the label…', 'Finding its active ingredients…', 'Checking how long one spray lasts in our heat and rain…'] });
        try {
            let r = await window.api(U.lookup, { method: 'POST', body: { scheduleId: SID, name, ai, quote: L.price } });
            if (r.data && r.data.known) {
                window.aneeWait?.fail();
                takeFound(r.data);
                window.toast?.('That one is already in our book. Nothing was charged.');
                return;
            }
            const d = r.data && r.data.status === 'ready' ? r.data : await window.aneeWait.poll({ id: r.data.id, job: (id) => U.job + id, phases: window.aneeWait.phases.research });
            await window.aneeWait.done({ title: 'Found it.', line: 'It is in the book now, for you and every farmer after you.' });
            if (typeof d.balance === 'number') L.balance = d.balance;
            const rep = d.report || {};
            takeFound({ product: rep.product, ingredient: (rep.ingredients || [])[0] });
            if (!rep.product && !(rep.ingredients || []).length) window.noticeSheet?.({ title: 'Anee could not confirm it', message: rep.summary || 'She could not find this product. Check the spelling on the label, or try its active ingredient.' });
        } catch (err) {
            window.aneeWait?.fail();
            if (err.data && err.data.quote) { L.price = err.data.quote; paintLookWhy(); }
            window.toast?.(err.message || 'The look up did not finish. Nothing was charged.', 'error');
        }
    });
    const takeFound = (d) => {
        if (d.product) {
            const p = d.product;
            S.picked = { productId: p.id, name: p.b, line: [p.type, p.grade, (p.ai || []).join(' + ')].filter(Boolean).join(' · '), t: p.t };
            showPicked();
        } else if (d.ingredient) {
            S.picked = { ai: d.ingredient.name, name: d.ingredient.name, line: 'The active ingredient on its own', t: '' };
            showPicked();
        }
    };

    /* ---------------- the reading ---------------- */
    const body = (html) => { $('[data-rfx-body]').innerHTML = html; };
    const empty = (h, p) => `<div class="rfx-card rfx-empty"><img src="{{ \App\Support\AneeEmoji::url('thinking') }}" alt=""><b>${esc(h)}</b><p>${esc(p)}</p></div>`;
    let runSeq = 0;
    const run = async () => {
        if (!S.lot) return;
        const seq = ++runSeq;
        S.saved = null;
        $('[data-rfx-body]').style.opacity = S.res ? '.55' : '1';
        if (!S.res) body('<div class="rfx-loading"><span></span><span></span><span></span></div>');
        try {
            const r = await window.api(U.run, { method: 'POST', body: { scheduleId: SID, lotId: S.lot, check: S.check, also: S.checks.filter((k) => k !== S.check), tries: S.tries } });
            if (seq !== runSeq) return;
            S.res = r.data;
            paint();
        } catch (err) {
            if (seq !== runSeq) return;
            body(empty('The calendar could not be read', err.message || 'Please try again.'));
        } finally {
            if (seq === runSeq) $('[data-rfx-body]').style.opacity = '1';
        }
    };

    const paint = () => {
        // A day opened on the reading being replaced would speak of the old one.
        hideDay();
        const R = S.res;
        const sm = R.summary;
        const ck = R.check;
        const isNut = ck.kind === 'nutrient';
        const parts = [];
        if (S.saved) parts.push(`<div class="rfx-banner is-saved"><span>📌 Saved ${esc(S.saved.at)}: <b>${esc(S.saved.title)}</b>, as it stood then.</span><button type="button" class="rfx-btn is-ghost" data-rfx-rerun>Read it with today's plan</button></div>`);
        if (!R.lot.hasDay) parts.push(`<div class="rfx-banner">📅 This lot has no planting day yet, so the stages and what matters when cannot be shown. Set its day zero in Lots, then open this again.</div>`);
        // A try that misses what is checked says what it does act on, one tap away; a try too small to count says so.
        R.apps.filter((a) => a.try).forEach((a) => {
            if (!a.relevant) {
                const acts = a.acts || [];
                parts.push(`<div class="rfx-banner is-miss"><span>✨ <b>${esc(a.as)}</b> does not act on ${esc(ck.word)}. ${acts.length ? 'It acts on:' : 'We could not read what it carries or guards against. Try its active ingredient, or let Anee look it up.'}</span>${acts.length ? `<span class="rfx-acts">${acts.map((x) => `<button type="button" class="rfx-chip" data-goto="${esc(x.key)}">${esc(x.label)}</button>`).join('')}</span>` : ''}</div>`);
            } else if (a.effect && a.effect.dose === 'trace') {
                parts.push(`<div class="rfx-banner is-miss"><span>✨ <b>${esc(a.as)}</b>${a.amount ? ' at ' + esc(a.amount) : ''} gives very little ${esc(ck.word)}: about ${a.effect.kgHa} kg of ${EL[a.effect.el] || esc(a.effect.el)} a hectare, where one usual ${a.form === 'foliar' ? 'leaf spray gives' : 'dose on the soil gives'} about ${a.effect.ref}. Its days still show, faintly.</span></div>`);
            }
        });
        // The summary.
        const pct = sm.pct;
        const ringCol = pct === null ? '#9ca3af' : pct >= 80 ? '#4a7c2a' : pct >= 50 ? '#f59e0b' : '#dc2626';
        const head = pct === null ? `Nothing in this season is critical for ${esc(ck.word)} yet.`
            : pct >= 80 ? `${esc(ck.short)} is well covered when it matters.`
            : pct >= 50 ? `${esc(ck.short)} is covered on most critical days, with gaps.`
            : (isNut ? `${esc(ck.short)} runs short when the crop needs it most.` : `The crop is unguarded against ${esc(ck.word)} on most critical days.`);
        const sub = isNut
            ? `${sm.apps} application${sm.apps === 1 ? '' : 's'} carry ${esc(ck.word)} on this lot${sm.tries ? `, ${sm.tries} of them a try` : ''}. Each keeps feeding the crop for a while, then fades.`
            : `${sm.apps} application${sm.apps === 1 ? '' : 's'} act on ${esc(ck.word)}${sm.tries ? `, ${sm.tries} of them a try` : ''}. Protection is only needed when the pest shows up: on a gap, scout first and spray only when you find it.`;
        parts.push(`<div class="rfx-card" style="--d:0s"><div class="rfx-sum">
            <div class="rfx-ring"><svg viewBox="0 0 120 120"><circle class="t" cx="60" cy="60" r="52"/><circle class="v" cx="60" cy="60" r="52" stroke="${ringCol}" data-pct="${pct ?? 0}"/></svg>
              <div class="rfx-ring-n"><b data-count="${pct ?? 0}">${pct === null ? '–' : '0'}</b><small>${pct === null ? 'no critical days' : '% of critical days covered'}</small></div></div>
            <div class="rfx-say"><h3>${head}</h3><p>${sub}</p>
              <div class="rfx-counts">
                <span><i style="background:#4a7c2a"></i>${sm.coveredDays} covered</span>
                <span><i style="background:#f59e0b"></i>${sm.thinDays} thin</span>
                <span><i style="background:#dc2626"></i>${sm.gapDays} gap</span>
                ${isNut ? `<span><i style="background:#8b5cf6"></i>${(sm.late || []).reduce((a, r) => a + r.n, 0)} late</span>` : ''}
              </div></div></div>
            ${runsHtml(sm, isNut)}</div>`);
        if ((R.series || []).length) parts.push(compareHtml(R));
        parts.push(chartHtml(R));
        parts.push(calHtml(R));
        parts.push(appsHtml(R));
        body(parts.join(''));
        // Bring it in: the ring, the count, the cells.
        requestAnimationFrame(() => {
            const v = $('.rfx-ring .v');
            if (v) v.style.strokeDasharray = `${(Number(v.dataset.pct) / 100) * 326.7} 400`;
            $$('.rfx-mini .v').forEach((m) => { m.style.strokeDasharray = `${(Number(m.dataset.pct) / 100) * 113.1} 200`; });
            const n = $('[data-count]');
            if (n && pct !== null) countUp(n, pct);
            const cal = $('.rfx-cal');
            if (cal) requestAnimationFrame(() => cal.classList.add('is-in'));
        });
    };
    const countUp = (el, to) => {
        if (calm) { el.textContent = to; return; }
        const t0 = performance.now();
        const step = (t) => { const k = Math.min(1, (t - t0) / 1100); el.textContent = Math.round(to * (1 - Math.pow(1 - k, 3))); if (k < 1) requestAnimationFrame(step); };
        requestAnimationFrame(step);
    };
    const runsHtml = (sm, isNut) => {
        const rows = [];
        (sm.gaps || []).slice(0, 4).forEach((r, k) => rows.push(`<div class="rfx-run" data-jump="${r.from}" style="--d:${.1 + k * .05}s"><em>🔴</em><span><b>${say(r.from)}${r.to !== r.from ? ' to ' + say(r.to) : ''}</b>, ${esc(r.stage)}: ${isNut ? 'not covered' : 'not protected'} while it is ${r.c >= 3 ? 'critical' : 'needed'}. ${esc(r.why)}</span></div>`));
        (sm.thin || []).slice(0, 2).forEach((r, k) => rows.push(`<div class="rfx-run is-thin" data-jump="${r.from}" style="--d:${.25 + k * .05}s"><em>🟠</em><span><b>${say(r.from)}${r.to !== r.from ? ' to ' + say(r.to) : ''}</b>, ${esc(r.stage)}: only partly covered.</span></div>`));
        if (isNut) (sm.late || []).slice(0, 2).forEach((r, k) => rows.push(`<div class="rfx-run is-late" data-jump="${r.from}" style="--d:${.35 + k * .05}s"><em>🟣</em><span><b>${say(r.from)}${r.to !== r.from ? ' to ' + say(r.to) : ''}</b>, ${esc(r.stage)}: still working when the crop no longer needs it. ${esc(sm.lateNote || '')}</span></div>`));
        return rows.length ? `<div class="rfx-runs">${rows.join('')}</div>` : '';
    };

    /* Every picked check, in the order picked: a ring each, then one lane each over the same days. */
    const allChecks = (R) => [{ key: R.check.key, label: R.check.label, short: R.check.short, pct: R.summary.pct, gap: R.summary.gapDays, words: R.phaseWords || {}, main: true,
            v: R.days.map((d) => d.v), s: R.days.map((d) => d.s), p: R.days.map((d) => d.p), c: R.days.map((d) => d.c) }]
        .concat((R.series || []).map((x) => ({ key: x.check.key, label: x.check.label, short: x.check.short, pct: x.pct, gap: x.gapDays, words: x.words || {}, v: x.v, s: x.s, p: x.p, c: x.c })))
        .sort((a, b) => S.checks.indexOf(a.key) - S.checks.indexOf(b.key));
    const compareHtml = (R) => {
        const all = allChecks(R);
        const n = R.days.length;
        const ti = R.days.findIndex((d) => d.d === R.today);
        const hi = R.lot.harvest ? R.days.findIndex((d) => d.d === R.lot.harvest) : -1;
        const rings = all.map((x, k) => `<button type="button" class="rfx-mini ${x.main ? 'is-on' : ''}" data-focus="${esc(x.key)}" style="--sc:${colorOf(x.key)};--k:${k}" ${x.main ? 'aria-current="true"' : ''}>
            <svg viewBox="0 0 44 44" aria-hidden="true"><circle cx="22" cy="22" r="18" class="t"/><circle cx="22" cy="22" r="18" class="v" stroke="${colorOf(x.key)}" data-pct="${x.pct ?? 0}"/></svg>
            <span><b>${x.pct === null ? '–' : x.pct + '%'}</b><span>${esc(x.label)}</span><small>${x.pct === null ? 'nothing critical yet' : x.gap ? x.gap + ' gap day' + (x.gap === 1 ? '' : 's') : 'no gaps'}</small></span></button>`).join('');
        // One lane: a tick on top where the stage needs it, pink where it is a gap, and each day filled to its
        // coverage in the colour of what the product is doing.
        const lane = (x) => {
            let r = '';
            for (let i = 0; i < n; i++) {
                if (x.c[i] >= 2) r += `<rect x="${i}" y="0" width="1.02" height="1.4" fill="${x.c[i] >= 3 ? '#dc2626' : '#f97316'}"/>`;
                if (x.s[i] === 'gap') r += `<rect x="${i}" y="1.8" width="1.02" height="8.2" fill="#fecaca"/>`;
                const p = x.p[i];
                if (p && x.v[i] > 0.01) { const h = Math.max(1.2, Math.min(1, x.v[i]) * 8.2); r += `<rect x="${i}" y="${(10 - h).toFixed(2)}" width="1.02" height="${h.toFixed(2)}" fill="${PHC[p]}"/>`; }
                else if (p === 'soil') r += `<rect x="${i}" y="7.4" width="1.02" height="2.6" fill="${PHC.soil}"/>`;
            }
            if (ti >= 0) r += `<line x1="${ti + .5}" x2="${ti + .5}" y1="0" y2="10" stroke="#2563eb" stroke-width="1.5" vector-effect="non-scaling-stroke"/>`;
            if (hi >= 0) r += `<line x1="${hi + .5}" x2="${hi + .5}" y1="0" y2="10" stroke="#b45309" stroke-width="1.5" vector-effect="non-scaling-stroke"/>`;
            return r;
        };
        const lanes = all.map((x, k) => `<div class="rfx-lane ${x.main ? 'is-on' : ''}" style="--sc:${colorOf(x.key)};--k:${k}">
            <button type="button" class="rfx-lane-n" data-focus="${esc(x.key)}" title="${esc(x.label)}"><i></i>${esc(x.short)}</button>
            <svg class="rfx-lane-s" viewBox="0 0 ${n} 10" preserveAspectRatio="none" data-lane role="img" aria-label="${esc(x.label)} over the season">${lane(x)}</svg></div>`).join('');
        const stg = R.stages.map((g) => {
            const i0 = R.days.findIndex((d) => d.d === g.from), i1 = R.days.findIndex((d) => d.d === g.to);
            return i0 < 0 ? '' : `<span style="width:${((i1 - i0 + 1) / n * 100).toFixed(3)}%" title="${esc(g.label)}">${esc(g.label)}</span>`;
        }).join('');
        let mo = '';
        R.days.forEach((d, i) => { if (d.d.endsWith('-01')) mo += `<span style="left:${(i / n * 100).toFixed(2)}%">${MON[D(d.d).getMonth()]}</span>`; });
        return `<div class="rfx-card rfx-cmp" style="--d:.04s"><div class="rfx-card-h">Side by side <small>tap one to open it below</small></div>
            <div class="rfx-minis">${rings}</div>
            <div class="rfx-lanes"><div class="rfx-lane is-head"><span></span><div class="rfx-stg">${stg}</div></div>${lanes}<div class="rfx-lane is-head"><span></span><div class="rfx-mo">${mo}</div></div></div>
            ${phaseLegend(R, all)}
            <div class="rfx-legend"><span><i style="background:linear-gradient(180deg,#dc2626 0 22%,#f3f6ef 22%)"></i>A line on top: that one is critical now</span><span><i style="background:#fecaca"></i>A gap</span><span><i style="background:#2563eb;width:3px"></i>Today</span></div></div>`;
    };

    /* The season as a chart: coverage per day over the stages, each stage tinted by how critical it is. */
    const chartHtml = (R) => {
        const days = R.days;
        if (!days.length) return '';
        const W = 1000, H = 190, top = 22, base = 150, n = days.length;
        const x = (i) => (i / Math.max(1, n - 1)) * W;
        const y = (v) => base - Math.min(1.6, v) / 1.6 * (base - top);
        const critCol = ['rgba(0,0,0,0)', 'rgba(245,158,11,.10)', 'rgba(249,115,22,.16)', 'rgba(220,38,38,.18)'];
        let bands = '', labels = '';
        R.stages.forEach((g) => {
            const i0 = days.findIndex((d) => d.d === g.from), i1 = days.findIndex((d) => d.d === g.to);
            if (i0 < 0) return;
            const x0 = x(i0) - (W / n) / 2, x1 = x(i1) + (W / n) / 2;
            bands += `<rect x="${x0.toFixed(1)}" y="${top}" width="${Math.max(0, x1 - x0).toFixed(1)}" height="${base - top}" fill="${critCol[g.crit] || critCol[0]}"/>`;
            if (x1 - x0 > 46) labels += `<text class="stg" x="${((x0 + x1) / 2).toFixed(1)}" y="${top - 8}" text-anchor="middle">${esc(g.label.length > 18 ? g.label.slice(0, 17) + '…' : g.label)}</text>`;
            bands += `<line class="grid" x1="${x0.toFixed(1)}" x2="${x0.toFixed(1)}" y1="${top}" y2="${base}"/>`;
        });
        let line = '', area = `M0 ${base}`, tryLine = '';
        days.forEach((d, i) => { const p = `${x(i).toFixed(1)} ${y(d.v).toFixed(1)}`; line += (i ? ' L' : 'M') + p; area += ' L' + p; });
        area += ` L${W} ${base} Z`;
        if (days.some((d) => d.t)) days.forEach((d, i) => { tryLine += (i ? ' L' : 'M') + `${x(i).toFixed(1)} ${y(d.t).toFixed(1)}`; });
        // The other checks being compared, a line each in its own colour.
        const cmp = (R.series || []).length;
        const others = (R.series || []).map((sr) => `<path d="${sr.v.map((v, i) => (i ? 'L' : 'M') + x(i).toFixed(1) + ' ' + y(v).toFixed(1)).join(' ')}" fill="none" stroke="${colorOf(sr.check.key)}" stroke-width="1.8" stroke-linejoin="round" opacity=".9"/>`).join('');
        // The coverage takes the colour of what the strongest product is doing that day, and a ribbon under the
        // baseline shows every phase, the days in the soil too (nothing covers yet, so the area has no height there).
        let stops = '', prev = PHC.strong;
        days.forEach((d, i) => { const c = PHC[d.p] || prev; prev = c; stops += `<stop offset="${(i / Math.max(1, n - 1)).toFixed(4)}" stop-color="${c}"/>`; });
        let ribbon = '', run0 = 0, rk = 0;
        days.forEach((d, i) => {
            const nx = days[i + 1];
            if (nx && nx.p === d.p) return;
            if (d.p) {
                const x0 = x(run0) - (W / n) / 2, x1 = x(i) + (W / n) / 2;
                ribbon += `<rect class="rib" style="--k:${rk++}" x="${Math.max(0, x0).toFixed(1)}" y="${base + 3}" width="${Math.max(1.5, Math.min(W, x1) - Math.max(0, x0)).toFixed(1)}" height="6" rx="3" fill="${PHC[d.p]}"><title>${esc((R.phaseWords || {})[d.p] || d.p)} · ${say(days[run0].d)}${i > run0 ? ' to ' + say(d.d) : ''}</title></rect>`;
            }
            run0 = i + 1;
        });
        let months = '';
        days.forEach((d, i) => { if (d.d.endsWith('-01') || i === 0) months += `<text class="mo" x="${x(i).toFixed(1)}" y="${base + 21}">${MON[D(d.d).getMonth()]}</text><line class="grid" x1="${x(i).toFixed(1)}" x2="${x(i).toFixed(1)}" y1="${base + 10}" y2="${base + 13}"/>`; });
        const ti = days.findIndex((d) => d.d === R.today);
        const today = ti >= 0 ? `<line class="today" x1="${x(ti)}" x2="${x(ti)}" y1="${top - 2}" y2="${base}"/><text class="mo" x="${x(ti) + 3}" y="${base - 4}" fill="#2563eb">Today</text>` : '';
        const hi = R.lot.harvest ? days.findIndex((d) => d.d === R.lot.harvest) : -1;
        const harv = hi >= 0 ? `<line class="harv" x1="${x(hi)}" x2="${x(hi)}" y1="${top}" y2="${base}"/><text class="mo" x="${x(hi) - 3}" y="${top + 10}" text-anchor="end" fill="#b45309">Harvest</text>` : '';
        const full = y(1);
        let dots = '';
        R.apps.filter((a) => a.relevant).forEach((a) => {
            const i = days.findIndex((d) => d.d === a.date);
            if (i < 0) return;
            const fill = a.try ? '#f5c518' : a.done ? '#14210c' : '#fff';
            dots += `<circle class="app" cx="${x(i).toFixed(1)}" cy="${base + 32}" r="5" fill="${fill}" stroke="${a.try ? '#b8860b' : '#14210c'}" data-jump="${a.date}"><title>${esc(a.as)} · ${say(a.date)}</title></circle>`;
        });
        return `<div class="rfx-card" style="--d:.08s"><div class="rfx-card-h">The season <small>coverage of ${esc(R.check.word)}, day by day</small></div><div class="rfx-chart"><div class="rfx-chart-w">
            <svg viewBox="0 -4 ${W} ${H + 26}" aria-label="Coverage over the season">
              <defs><linearGradient id="rfxPhG" gradientUnits="userSpaceOnUse" x1="0" y1="0" x2="${W}" y2="0">${stops}</linearGradient>
                <linearGradient id="rfxCovV" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#fff" stop-opacity=".9"/><stop offset="1" stop-color="#fff" stop-opacity=".12"/></linearGradient>
                <mask id="rfxCovM" maskUnits="userSpaceOnUse" x="0" y="0" width="${W}" height="${base + 1}"><rect x="0" y="0" width="${W}" height="${base + 1}" fill="url(#rfxCovV)"/></mask></defs>
              ${bands}${labels}
              <line x1="0" x2="${W}" y1="${full.toFixed(1)}" y2="${full.toFixed(1)}" stroke="#9ca3af" stroke-dasharray="2 4"/><text class="mo" x="2" y="${(full - 4).toFixed(1)}">one full dose</text>
              <g class="reveal"><path d="${area}" fill="url(#rfxPhG)" mask="url(#rfxCovM)"/>${others}<path class="cov-l" d="${line}" ${cmp ? `style="stroke:${colorOf(R.check.key)};stroke-width:2.6"` : ''}/>${tryLine ? `<path class="try-l" d="${tryLine}"/>` : ''}</g>
              <line x1="0" x2="${W}" y1="${base}" y2="${base}" stroke="#d1d5db"/>
              ${ribbon}${months}${today}${harv}${dots}
            </svg></div></div>
            ${phaseLegend(R)}
            ${cmp ? `<div class="rfx-legend"><b class="rfx-leg-h">Lines</b>${allChecks(R).map((c) => `<span><i style="background:${colorOf(c.key)};height:.25rem;border-radius:999px"></i>${esc(c.label)}${c.main ? ' (shaded, its stages tinted)' : ''}</span>`).join('')}</div>` : ''}
            <div class="rfx-legend"><span><i style="background:rgba(220,38,38,.25)"></i>Critical stage</span><span><i style="background:rgba(249,115,22,.22)"></i>Matters</span>${R.days.some((d) => d.t) ? '<span><i style="background:#f5c518"></i>Your try</span>' : ''}<span><i style="background:#14210c;border-radius:999px"></i>Done</span><span><i style="background:#fff;border:1.5px solid #14210c;border-radius:999px"></i>Planned</span></div></div>`;
    };
    /* The phases this reading shows, in the order one application lives them. */
    const phaseLegend = (R, all = null) => {
        const used = new Set();
        R.days.forEach((d) => { if (d.p) used.add(d.p); if (d.sl) used.add('soil'); });
        (all || []).forEach((x) => x.p.forEach((p) => { if (p) used.add(p); }));
        // Side by side, the checks may be nutrients and sprays at once: plain names that fit both.
        const words = all ? { soil: 'In the soil, or taking hold', absorb: 'Being taken in', strong: 'Strongest', easing: 'Easing', fading: 'Fading away' } : (R.phaseWords || {});
        const items = PH_ORDER.filter((p) => used.has(p));
        if (!items.length) return '';
        return `<div class="rfx-legend rfx-flow"><b class="rfx-leg-h">What each product is doing</b>${items.map((p, k) => `${k ? '<em aria-hidden="true">›</em>' : ''}<span style="--k:${k}"><i class="rfx-sw is-${p}"></i>${esc(words[p] || p)}</span>`).join('')}</div>`;
    };

    /* The calendar: month by month, each day filled to its coverage, with its criticality as dots. */
    const calHtml = (R) => {
        const byDay = Object.fromEntries(R.days.map((d) => [d.d, d]));
        const appsOn = {};
        R.apps.filter((a) => a.relevant).forEach((a) => { (appsOn[a.date] ||= []).push(a); });
        const first = D(R.days[0].d), last = D(R.days[R.days.length - 1].d);
        let html = '', k = 0;
        for (let m = new Date(first.getFullYear(), first.getMonth(), 1); m <= last; m = new Date(m.getFullYear(), m.getMonth() + 1, 1)) {
            const cells = [];
            const lead = m.getDay();
            for (let i = 0; i < lead; i++) cells.push('<span class="rfx-cell is-out"></span>');
            const dim = new Date(m.getFullYear(), m.getMonth() + 1, 0).getDate();
            let crit = 0;
            for (let dd = 1; dd <= dim; dd++) {
                const ds = iso(new Date(m.getFullYear(), m.getMonth(), dd));
                const d = byDay[ds];
                if (!d) { cells.push(`<span class="rfx-cell is-out"><span class="rfx-n" style="opacity:.35">${dd}</span></span>`); continue; }
                crit += d.c >= 2 ? 1 : 0;
                const here = appsOn[ds] || [];
                const ap = here.slice(0, 2).map((a) => `<i class="${a.try ? 'is-try' : a.done ? '' : 'is-plan'}" title="${esc(a.as)}${a.try ? ', your try' : a.done ? ', done' : ', planned'}"><svg><use href="#rfxI-${apMark(a)}"/></svg></i>`).join('')
                    + (here.length > 2 ? `<b>+${here.length - 2}</b>` : '');
                const full = d.v >= 0.7 && d.s !== 'gap' && d.p !== 'fading';
                cells.push(`<button type="button" class="rfx-cell ${ds === R.today ? 'is-today' : ''} ${full ? 'is-full' : ''}" data-day="${ds}" data-s="${d.s}" data-p="${d.p || ''}" data-t="${d.t ? 1 : 0}" style="--k:${k++};--h:${Math.round(Math.min(1, d.v) * 100)}%">
                    <span class="rfx-fill"></span>${d.sl ? '<span class="rfx-soil"></span>' : ''}<span class="rfx-n">${dd}</span>${d.dl ? `<span class="rfx-dl">${esc(d.dl)}</span>` : ''}
                    ${d.c ? `<span class="rfx-crit c${d.c}" aria-label="${CRIT[d.c]}"><i></i><i></i><i></i></span>` : ''}${ap ? `<span class="rfx-ap">${ap}</span>` : ''}</button>`);
            }
            html += `<div class="rfx-month"><div class="rfx-month-h"><b>${MON[m.getMonth()]} ${m.getFullYear()}</b>${crit ? `<small>${crit} critical day${crit === 1 ? '' : 's'}</small>` : ''}</div>
                <div class="rfx-wk">${DOW.map((w) => `<span class="rfx-wd">${w}</span>`).join('')}${cells.join('')}</div></div>`;
        }
        const late = R.check.kind === 'nutrient' ? 'late' : 'extra';
        return `<div class="rfx-card" style="--d:.14s"><div class="rfx-card-h">The calendar <small>tap a day</small></div><div class="rfx-cal">${html}</div>
            ${phaseLegend(R)}
            <div class="rfx-legend"><b class="rfx-leg-h">What it means for the crop</b>
              <span><i style="background:repeating-linear-gradient(135deg,#fff1f1 0 3px,#fca5a5 3px 5px);box-shadow:inset 0 0 0 1.5px #fca5a5"></i>${STATE.gap[0]}</span>
              <span><i class="rfx-leg-cell" style="box-shadow:inset 0 0 0 2px #f59e0b"></i>${STATE.thin[0]}, only partly covered</span>
              <span><i class="rfx-leg-cell" style="box-shadow:inset 0 0 0 2px #a78bfa;background:repeating-linear-gradient(135deg,rgb(124 58 237 / .45) 0 2px,#fff 2px 5px)"></i>${STATE[late][0]}</span>
              <span><span class="rfx-crit c3" style="position:static"><i></i><i></i><i></i></span>&nbsp;How critical the stage is</span>
              <span><i class="rfx-leg-cell" style="box-shadow:inset 0 0 0 2px #2563eb"></i>Today</span></div>
            <div class="rfx-legend rfx-leg-ap"><b class="rfx-leg-h">What went on</b>
              <span><span class="rfx-ap"><i><svg><use href="#rfxI-gran"/></svg></i></span>Spread on the soil</span>
              <span><span class="rfx-ap"><i><svg><use href="#rfxI-drop"/></svg></i></span>Sprayed or poured</span>
              <span><span class="rfx-ap"><i><svg><use href="#rfxI-leaf"/></svg></i></span>Organic or biological</span>
              <span><span class="rfx-ap"><i class="is-try"><svg><use href="#rfxI-star"/></svg></i></span>Your try</span>
              <span><span class="rfx-ap"><i class="is-plan"><svg><use href="#rfxI-gran"/></svg></i></span>Outlined: still planned</span></div></div>`;
    };
    // How a product went on, as its mark: a try's star, a spray's drop, organic's leaf, else granules.
    const apMark = (a) => a.try ? 'star' : ['foliar', 'drench'].includes(a.form) ? 'drop' : ['organic', 'bio'].includes(a.form) ? 'leaf' : 'gran';

    /* The products, each with how long it keeps working. */
    const appsHtml = (R) => {
        const rel = R.apps.filter((a) => a.relevant);
        const other = R.apps.filter((a) => !a.relevant);
        const span = R.days.length;
        const first = D(R.days[0].d).getTime();
        const pos = (s) => Math.max(0, Math.min(100, ((D(s).getTime() - first) / 864e5) / Math.max(1, span - 1) * 100));
        const rows = rel.map((a, k) => {
            const e = a.effect;
            const d = D(a.date);
            const doseWord = e.dose === 'unknown' ? 'amount not read, counted as one usual dose' : e.dose === 'trace' ? 'very light dose' : e.dose ? e.dose + ' dose' : '';
            const by = e.by && R.check.kind !== 'nutrient' ? ` by ${esc(e.by)}` : '';
            // The bar in its phases: in the soil, taken up, strongest, easing, fading (one plain bar for an old saved reading).
            const dayW = 100 / Math.max(1, span - 1);
            const phs = e.phases || [];
            const segs = phs.length ? phs.map((ph) => `<i class="is-${ph.p}" style="left:${pos(ph.from)}%;width:${Math.max(.6, pos(ph.to) - pos(ph.from) + dayW).toFixed(2)}%"></i>`).join('')
                : `<i class="is-strong" style="left:${pos(e.from)}%;width:${Math.max(1.5, pos(e.until) - pos(e.from))}%"></i>`;
            const phRow = phs.length ? `<div class="rfx-phs">${phs.map((ph) => `<span><i class="rfx-sw is-${ph.p}"></i>${esc(ph.w)} <small>${ph.n} day${ph.n === 1 ? '' : 's'}</small></span>`).join('')}</div>` : '';
            const trace = e.dose === 'trace' && e.kgHa !== null ? `<small class="rfx-trace">Very little at this amount: about ${e.kgHa} kg of ${EL[e.el] || esc(e.el)} a hectare, where one usual ${a.form === 'foliar' ? 'leaf spray' : 'dose on the soil'} gives about ${e.ref}.</small>` : '';
            return `<div class="rfx-app ${a.try ? 'is-try' : ''}" style="--d:${.18 + Math.min(k, 10) * .04}s">
                <span class="rfx-app-d"><small>${MON[d.getMonth()]}</small><b>${d.getDate()}</b></span>
                <div class="rfx-app-t"><b>${esc(a.as)}</b>${a.try ? '<span class="rfx-tag is-try">Your try</span>' : a.done ? '<span class="rfx-tag is-done">Done</span>' : '<span class="rfx-tag">Planned</span>'}${e.conf === 'estimate' || e.conf === 'low' ? '<span class="rfx-tag is-est">Estimated</span>' : ''}
                  <small>${esc([a.title !== 'If you apply it' ? a.title : '', a.amount, a.formWord, doseWord].filter(Boolean).join(' · '))}</small>
                  <small>Works${by} from <b>${say(e.from)}</b> to about <b>${say(e.full)}</b>, fading out by ${say(e.until)} (${e.days[1]} days, ${e.days[0]} to ${e.days[2]}).</small>
                  <div class="rfx-bar"><span class="rfx-segs">${segs}</span></div>${phRow}${trace}
                  ${e.note ? `<div class="rfx-app-n">${esc(e.note)}</div>` : ''}</div></div>`;
        }).join('');
        const none = !rel.length ? `<div class="rfx-empty" style="padding:1.2rem"><p>Nothing on this lot's tasks acts on ${esc(R.check.word)} yet. Add it to a task, or try a product above to see what it would do.</p></div>` : '';
        const oth = other.length ? `<details class="rfx-other"><summary>${other.length} other product${other.length === 1 ? '' : 's'} on this lot do not act on ${esc(R.check.word)}</summary><ul>${other.map((a) => `<li>${say(a.date)} · ${esc(a.as)}${a.carries.length ? ' (' + esc(a.carries.join(', ')) + ')' : a.ai.length ? ' (' + esc(a.ai.join(' + ')) + ')' : ''}</li>`).join('')}</ul></details>` : '';
        return `<div class="rfx-card" style="--d:.2s"><div class="rfx-card-h">What is working <small>${rel.length} product${rel.length === 1 ? '' : 's'}</small></div><div class="rfx-apps">${rows}${none}</div>${oth}</div>`;
    };

    /* One day, opened. */
    const dayBox = $('[data-rfx-day]');
    const hideDay = () => { dayBox.hidden = true; };
    $('[data-rfx-body]').addEventListener('click', (e) => {
        const go = e.target.closest('[data-goto]');
        if (go) {
            const k = go.dataset.goto;
            S.tab = tabOf(k);
            paintPicks();
            if (S.checks.includes(k)) focusCheck(k); else togglePick(k);
            $('[data-rfx-scroll]').scrollTo({ top: 0, behavior: calm ? 'auto' : 'smooth' });
            return;
        }
        const jump = e.target.closest('[data-jump]');
        if (jump) {
            const cell = $(`.rfx-cell[data-day="${jump.dataset.jump}"]`);
            if (cell) { cell.scrollIntoView({ behavior: calm ? 'auto' : 'smooth', block: 'center' }); setTimeout(() => showDay(jump.dataset.jump), calm ? 0 : 450); }
            return;
        }
        const f = e.target.closest('[data-focus]');
        if (f) { focusCheck(f.dataset.focus); return; }
        // A lane of the side by side view: the day under the tap.
        const lane = e.target.closest('[data-lane]');
        if (lane && S.res) {
            const r = lane.getBoundingClientRect();
            const i = Math.max(0, Math.min(S.res.days.length - 1, Math.floor((e.clientX - r.left) / r.width * S.res.days.length)));
            showDay(S.res.days[i].d);
            return;
        }
        const c = e.target.closest('[data-day]');
        if (c) showDay(c.dataset.day);
        if (e.target.closest('[data-rfx-rerun]')) { S.saved = null; run(); }
    });
    const showDay = (ds) => {
        const R = S.res;
        const d = R.days.find((x) => x.d === ds);
        if (!d) return;
        const g = R.stages[d.g] || {};
        // Every product at work that day, and those still in the soil, each with what it is doing.
        const phaseOn = (a) => (a.effect?.phases || []).find((ph) => ph.from <= ds && ds <= ph.to) || null;
        const apps = d.a.map((i) => R.apps.find((a) => a.i === i)).filter(Boolean)
            .concat(R.apps.filter((a) => a.relevant && !d.a.includes(a.i) && phaseOn(a)?.p === 'soil'));
        const pct = Math.round(d.v * 100);
        const col = d.p ? `var(--ph-${d.p})` : (STATE[d.s] ? STATE[d.s][1] : '#86c25a');
        const cmpOn = (R.series || []).length > 0;
        dayBox.innerHTML = `<div class="rfx-day-h"><div><b>${DOW[D(ds).getDay()]}, ${sayY(ds)}</b><small>${esc([d.dl, g.label].filter(Boolean).join(' · '))}</small></div><button type="button" class="rfx-x" data-rfx-day-x aria-label="Close"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path stroke-linecap="round" d="M6 6l12 12M18 6L6 18"/></svg></button></div>
            <div class="rfx-meter"><i style="width:0;background:${col}" data-w="${Math.min(100, pct)}"></i></div>
            <p>${cmpOn ? `<b style="color:${colorOf(R.check.key)}">${esc(R.check.label)}</b>: ` : ''}<b>${STATE[d.s] ? STATE[d.s][0] : ''}</b>. Coverage ${pct}% of one full dose. ${d.c ? 'This stage: ' + CRIT[d.c].toLowerCase() + '.' : ''} ${esc(g.why || '')}</p>
            ${apps.length ? `<ul>${apps.map((a) => { const ph = phaseOn(a); return `<li><i class="rfx-sw is-${ph ? ph.p : 'strong'}"></i> <b>${esc(a.as)}</b> (${say(a.date)}${a.try ? ', your try' : ''}): ${ph ? esc(ph.w.charAt(0).toLowerCase() + ph.w.slice(1)) + (ph.p === 'soil' ? ', taken up from ' + say(a.effect.from) : ph.p === 'fading' ? ', gone by about ' + say(a.effect.until) : ', working until about ' + say(a.effect.full)) : 'working until about ' + say(a.effect.full)}</li>`; }).join('')}</ul>` : '<p style="color:#6b7280">Nothing applied is working on this day.</p>'}`;
        // The other checks on the same day.
        if (cmpOn) {
            const i = R.days.indexOf(d);
            dayBox.insertAdjacentHTML('beforeend', `<div class="rfx-day-cmp">${R.series.map((sr) => {
                const p = sr.p[i], st = sr.s[i];
                return `<div style="--sc:${colorOf(sr.check.key)}"><i></i><b>${esc(sr.check.label)}</b><span class="rfx-meter"><i style="width:${Math.min(100, Math.round(sr.v[i] * 100))}%;background:${p ? `var(--ph-${p})` : '#d1d5db'}"></i></span><small>${Math.round(sr.v[i] * 100)}% · ${esc(STATE[st] ? STATE[st][0].split(':')[0] : '')}${p ? ' · ' + esc((sr.words || {})[p] || '') : ''}</small></div>`;
            }).join('')}</div>`);
        }
        dayBox.hidden = false;
        requestAnimationFrame(() => { const m = dayBox.querySelector('[data-w]'); if (m) m.style.width = m.dataset.w + '%'; });
    };
    dayBox.addEventListener('click', (e) => { if (e.target.closest('[data-rfx-day-x]')) hideDay(); });

    /* ---------------- save, and saved ---------------- */
    const paintSavedCount = () => {
        const n = (S.opts?.saved || []).length;
        const b = $('[data-rfx-saved-n]');
        b.hidden = !n;
        b.textContent = n;
    };
    $('[data-rfx-save]').addEventListener('click', async () => {
        if (!S.res || S.busy) return;
        S.busy = true;
        const btn = $('[data-rfx-save]');
        btn.disabled = true;
        try {
            const title = [S.res.check.label, ...(S.res.series || []).map((x) => x.check.label)].join(', ') + ` · ${S.res.lot.name}` + (S.tries.length ? ` · with ${S.tries.map((t) => t.name).join(', ')}` : '');
            const r = await window.api(U.save, { method: 'POST', body: { scheduleId: SID, lotId: S.lot, check: S.check, also: S.checks.filter((k) => k !== S.check), tries: S.tries, title } });
            S.opts.saved = r.data.saved;
            paintSavedCount();
            window.toast?.('Saved. Open it any time from Saved.');
        } catch (err) { window.toast?.(err.message || 'It could not be saved.', 'error'); }
        finally { S.busy = false; btn.disabled = false; }
    });
    const openSaved = () => {
        const list = S.opts?.saved || [];
        const lots = Object.fromEntries((S.opts?.lots || []).map((l) => [l.id, l.name]));
        $('[data-rfx-saved]').innerHTML = list.length ? list.map((s) => `<button type="button" class="rfx-sv" data-open-saved="${s.id}"><span><b>${esc(s.title)}</b><small>${esc(s.at)}${lots[s.lotId] ? ' · ' + esc(lots[s.lotId]) : ''}</small></span><span class="rfx-sv-x" data-del-saved="${s.id}" role="button" aria-label="Remove"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 7h12M9 7V5h6v2M8 7l1 12h6l1-12"/></svg></span></button>`).join('')
            : '<p style="font-size:.86rem;color:#6b7280;padding:.5rem">Nothing saved yet. Save a reading to keep it as it stands today.</p>';
        $('[data-rfx-drawer]').hidden = false;
        requestAnimationFrame(() => $('[data-rfx-drawer]').classList.add('is-on'));
    };
    $('[data-rfx-saved]').addEventListener('click', async (e) => {
        const del = e.target.closest('[data-del-saved]');
        if (del) {
            e.stopPropagation();
            try { const r = await window.api(U.saved + del.dataset.delSaved + '?scheduleId=' + SID, { method: 'DELETE' }); S.opts.saved = r.data.saved; paintSavedCount(); openSaved(); }
            catch (err) { window.toast?.(err.message, 'error'); }
            return;
        }
        const b = e.target.closest('[data-open-saved]');
        if (!b) return;
        try {
            const r = await window.api(U.saved + b.dataset.openSaved + '?scheduleId=' + SID);
            const d = r.data;
            S.res = d.result;
            S.lot = d.result.lot.id;
            S.check = d.params.check || d.result.check.key;
            S.checks = [S.check, ...(d.params.also || [])];
            S.tries = (d.params.tries || []).map((t) => ({ ...t, name: t.name || t.ai || 'Product' }));
            S.tab = tabOf(S.check);
            paintLots(); paintPicks(); paintTries();
            S.saved = { title: d.title, at: d.at };
            paint();
            $('[data-rfx-drawer]').classList.remove('is-on');
            $('[data-rfx-scroll]').scrollTo({ top: 0, behavior: calm ? 'auto' : 'smooth' });
        } catch (err) { window.toast?.(err.message, 'error'); }
    });
})();
</script>
@endpush
@endonce
