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
                <div class="rfx-row">
                    <span class="rfx-label">Try</span>
                    <div class="rfx-tries">
                        <div class="rfx-chips" data-rfx-tries></div>
                        <button type="button" class="rfx-add" data-rfx-try-open><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true"><path stroke-linecap="round" d="M12 5v14M5 12h14"/></svg>A product you plan to apply</button>
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
                            <div class="rfx-picked-f">
                                <label><span>Apply on</span><input type="date" data-rfx-date></label>
                                <label><span>Amount for the lot <i>(optional)</i></span><span class="rfx-amt"><input type="number" min="0" step="any" inputmode="decimal" data-rfx-qty placeholder="e.g. 2"><select data-rfx-unit><option value="bags">bags</option><option value="kg">kg</option><option value="L">liters</option><option value="mL">mL</option><option value="g">grams</option></select></span></label>
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
    .rfx { position: fixed; inset: 0; z-index: 420; display: grid; place-items: center; }
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
    .rfx-tries { display: flex; flex-wrap: wrap; align-items: center; gap: .4rem; }
    .rfx-add { display: inline-flex; align-items: center; gap: .35rem; padding: .45rem .8rem; border-radius: 999px; font-size: .84rem; font-weight: 800; color: #5c4500; background: #fffbe8;
        box-shadow: inset 0 0 0 1.5px dashed #f5c518; border: 1.5px dashed #e9c64a; transition: background-color .28s, transform .28s cubic-bezier(.22,1,.36,1); }
    .rfx-add:hover { background: #fff3c4; transform: translateY(-1px); }
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
    .rfx-chart .cov { fill: url(#rfxCovG); }
    .rfx-chart .cov-l { fill: none; stroke: #4a7c2a; stroke-width: 2; stroke-linejoin: round; }
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
    .rfx-cell.is-today { box-shadow: inset 0 0 0 2px #2563eb; }
    .rfx-fill { position: absolute; left: 0; right: 0; bottom: 0; height: 0; transition: height .7s cubic-bezier(.22,1,.36,1); transition-delay: calc(var(--k, 0) * 6ms); }
    .rfx-cal.is-in .rfx-fill { height: var(--h, 0%); }
    .rfx-cell[data-s=ok] .rfx-fill { background: linear-gradient(180deg, #86c25a, #4a7c2a); }
    .rfx-cell[data-s=thin] .rfx-fill { background: linear-gradient(180deg, #fcd34d, #f59e0b); }
    .rfx-cell[data-s=late] .rfx-fill, .rfx-cell[data-s=extra] .rfx-fill { background: linear-gradient(180deg, #c4b5fd, #8b5cf6); }
    .rfx-cell[data-s=pre] .rfx-fill, .rfx-cell[data-s=watch] .rfx-fill, .rfx-cell[data-s=none] .rfx-fill { background: linear-gradient(180deg, #bfe0a6, #86c25a); }
    .rfx-cell[data-s=gap] { background: repeating-linear-gradient(135deg, #fff1f1 0 6px, #ffe1e1 6px 12px); box-shadow: inset 0 0 0 1.5px #fca5a5; }
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
    .rfx-ap { position: absolute; z-index: 1; left: .3rem; bottom: .3rem; display: flex; gap: 2px; }
    .rfx-ap i { display: grid; place-items: center; min-width: .95rem; height: .95rem; padding: 0 2px; border-radius: 999px; font-style: normal; font-size: .5rem; font-weight: 900; color: #fff; background: #14210c; box-shadow: 0 0 0 1.5px #fff; }
    .rfx-ap i.is-plan { color: #14210c; background: #fff; box-shadow: inset 0 0 0 1.5px #14210c, 0 0 0 1.5px #fff; }
    .rfx-ap i.is-try { color: #14210c; background: #f5c518; }
    @media (max-width: 639.98px) {
        .rfx-cal { padding: .2rem .4rem .8rem; }
        .rfx-wk { gap: .18rem; }
        .rfx-cell { min-height: 3.6rem; padding: .2rem .22rem; border-radius: .5rem; }
        .rfx-n { font-size: .7rem; }
        .rfx-dl { font-size: .48rem; }
        .rfx-crit i { width: .26rem; height: .26rem; }
    }
    .rfx-legend { display: flex; flex-wrap: wrap; gap: .4rem .8rem; padding: .3rem 1rem 1rem; font-size: .74rem; font-weight: 700; color: #4b5563; }
    .rfx-legend span { display: inline-flex; align-items: center; gap: .35rem; }
    .rfx-legend i { display: inline-block; width: .9rem; height: .9rem; border-radius: .25rem; }
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
    .rfx-bar { position: relative; height: .55rem; margin-top: .5rem; border-radius: 999px; background: #e9efe3; overflow: hidden; }
    .rfx-bar i { position: absolute; top: 0; bottom: 0; border-radius: 999px; background: linear-gradient(90deg, #4a7c2a, #86c25a 70%, rgb(134 194 90 / .2)); transform-origin: left; animation: rfxGrow .8s cubic-bezier(.22,1,.36,1) both; animation-delay: calc(var(--d, 0s) + .2s); }
    .rfx-app.is-try .rfx-bar i { background: linear-gradient(90deg, #e0a800, #f5c518 70%, rgb(245 197 24 / .2)); }
    @keyframes rfxGrow { from { transform: scaleX(0); } }
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
    @media (prefers-reduced-motion: reduce) {
        .rfx *, .rfx *::before, .rfx *::after { animation: none !important; transition: none !important; }
        .rfx-chart .reveal { clip-path: none; }
        .rfx-fill { height: var(--h, 0%) !important; }
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
    const S = { opts: null, lot: null, check: 'n:N', tab: 'nut', tries: [], res: null, saved: null, picked: null, q: '', busy: false };

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
        if (S.check.startsWith('p:') && !(lotOf()?.threats || []).some((t) => 'p:' + t.slug === S.check)) S.check = 't:insect';
        paintLots();
        paintPicks();
        run();
    });
    const paintPicks = () => {
        const o = S.opts;
        $$('[data-rfx-tabs] button').forEach((b) => b.classList.toggle('is-on', b.dataset.tab === S.tab));
        let html = '';
        if (S.tab === 'nut' || S.tab === 'micro') {
            html = o.nutrients.filter((n) => (S.tab === 'micro') === n.micro).map((n, k) => `<button type="button" class="rfx-chip ${S.check === n.key ? 'is-on' : ''}" data-check="${n.key}" style="--k:${k}"><b>${esc(n.short)}</b> <small>${esc(n.name)}</small></button>`).join('');
        } else if (S.tab === 'prot') {
            html = o.threats.map((t, k) => `<button type="button" class="rfx-chip ${S.check === t.key ? 'is-on' : ''}" data-check="${t.key}" style="--k:${k}">${t.icon} ${esc(t.name)}</button>`).join('');
        } else {
            const list = lotOf()?.threats || [];
            html = list.length
                ? `<input type="search" data-rfx-pest-q placeholder="Find a pest or disease" value="${esc(S.q)}">` + list.filter((t) => !S.q || (t.name + ' ' + t.local).toLowerCase().includes(S.q.toLowerCase()))
                    .map((t, k) => `<button type="button" class="rfx-chip ${S.check === 'p:' + t.slug ? 'is-on' : ''}" data-check="p:${esc(t.slug)}" style="--k:${Math.min(k, 20)}" title="${esc(t.local)}">${esc(t.name)} <small>${esc(t.kind)}</small></button>`).join('')
                : '<small style="color:#6b7280">This lot\'s crop has no pests or diseases in our book yet. Use Protection instead.</small>';
        }
        $('[data-rfx-picks]').innerHTML = html;
    };
    $('[data-rfx-tabs]').addEventListener('click', (e) => {
        const b = e.target.closest('[data-tab]');
        if (!b || b.dataset.tab === S.tab) return;
        S.tab = b.dataset.tab;
        S.q = '';
        paintPicks();
    });
    $('[data-rfx-picks]').addEventListener('click', (e) => {
        const b = e.target.closest('[data-check]');
        if (!b || b.dataset.check === S.check) return;
        S.check = b.dataset.check;
        $$('[data-rfx-picks] [data-check]').forEach((x) => x.classList.toggle('is-on', x === b));
        run();
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
        $('[data-rfx-tries]').innerHTML = S.tries.map((t, i) => `<span class="rfx-chip is-try">✨ ${esc(t.name)} <small>${esc(say(t.date))}</small><button type="button" data-untry="${i}" aria-label="Remove"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" d="M6 6l12 12M18 6L6 18"/></svg></button></span>`).join('');
        $('[data-rfx-try-open]').hidden = S.tries.length >= 4;
    };
    $('[data-rfx-tries]').addEventListener('click', (e) => {
        const b = e.target.closest('[data-untry]');
        if (!b) return;
        S.tries.splice(Number(b.dataset.untry), 1);
        paintTries();
        run();
    });
    $('[data-rfx-try-open]').addEventListener('click', () => {
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
    };
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
        $('[data-rfx-results]').innerHTML = '';
        $('[data-rfx-look]').hidden = true;
        $('[data-rfx-picked]').hidden = false;
    };
    $('[data-rfx-try-cancel]').addEventListener('click', () => { tryBox.hidden = true; });
    $('[data-rfx-try-add]').addEventListener('click', () => {
        const p = S.picked;
        const date = $('[data-rfx-date]').value;
        if (!p || !date) { window.toast?.('Pick the day you would apply it.', 'error'); return; }
        S.tries.push({ productId: p.productId || null, ai: p.ai || null, name: p.name, date, qty: Number($('[data-rfx-qty]').value) || 0, unit: $('[data-rfx-unit]').value });
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
            const r = await window.api(U.run, { method: 'POST', body: { scheduleId: SID, lotId: S.lot, check: S.check, tries: S.tries } });
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
        const R = S.res;
        const sm = R.summary;
        const ck = R.check;
        const isNut = ck.kind === 'nutrient';
        const parts = [];
        if (S.saved) parts.push(`<div class="rfx-banner is-saved"><span>📌 Saved ${esc(S.saved.at)}: <b>${esc(S.saved.title)}</b>, as it stood then.</span><button type="button" class="rfx-btn is-ghost" data-rfx-rerun>Read it with today's plan</button></div>`);
        if (!R.lot.hasDay) parts.push(`<div class="rfx-banner">📅 This lot has no planting day yet, so the stages and what matters when cannot be shown. Set its day zero in Lots, then open this again.</div>`);
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
        parts.push(chartHtml(R));
        parts.push(calHtml(R));
        parts.push(appsHtml(R));
        body(parts.join(''));
        // Bring it in: the ring, the count, the cells.
        requestAnimationFrame(() => {
            const v = $('.rfx-ring .v');
            if (v) v.style.strokeDasharray = `${(Number(v.dataset.pct) / 100) * 326.7} 400`;
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
        let months = '';
        days.forEach((d, i) => { if (d.d.endsWith('-01') || i === 0) months += `<text class="mo" x="${x(i).toFixed(1)}" y="${base + 16}">${MON[D(d.d).getMonth()]}</text><line class="grid" x1="${x(i).toFixed(1)}" x2="${x(i).toFixed(1)}" y1="${base}" y2="${base + 5}"/>`; });
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
            dots += `<circle class="app" cx="${x(i).toFixed(1)}" cy="${base + 26}" r="5" fill="${fill}" stroke="${a.try ? '#b8860b' : '#14210c'}" data-jump="${a.date}"><title>${esc(a.as)} · ${say(a.date)}</title></circle>`;
        });
        return `<div class="rfx-card" style="--d:.08s"><div class="rfx-card-h">The season <small>coverage of ${esc(R.check.word)}, day by day</small></div><div class="rfx-chart"><div class="rfx-chart-w">
            <svg viewBox="0 -4 ${W} ${H + 18}" aria-label="Coverage over the season">
              <defs><linearGradient id="rfxCovG" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#86c25a" stop-opacity=".75"/><stop offset="1" stop-color="#86c25a" stop-opacity=".06"/></linearGradient></defs>
              ${bands}${labels}
              <line x1="0" x2="${W}" y1="${full.toFixed(1)}" y2="${full.toFixed(1)}" stroke="#9ca3af" stroke-dasharray="2 4"/><text class="mo" x="2" y="${(full - 4).toFixed(1)}">one full dose</text>
              <g class="reveal"><path class="cov" d="${area}"/><path class="cov-l" d="${line}"/>${tryLine ? `<path class="try-l" d="${tryLine}"/>` : ''}</g>
              <line x1="0" x2="${W}" y1="${base}" y2="${base}" stroke="#d1d5db"/>
              ${months}${today}${harv}${dots}
            </svg></div></div>
            <div class="rfx-legend"><span><i style="background:rgba(220,38,38,.25)"></i>Critical stage</span><span><i style="background:rgba(249,115,22,.22)"></i>Matters</span><span><i style="background:#86c25a"></i>Coverage</span>${R.days.some((d) => d.t) ? '<span><i style="background:#f5c518"></i>Your try</span>' : ''}<span><i style="background:#14210c;border-radius:999px"></i>Done</span><span><i style="background:#fff;border:1.5px solid #14210c;border-radius:999px"></i>Planned</span></div></div>`;
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
                const ap = (appsOn[ds] || []).slice(0, 3).map((a) => `<i class="${a.try ? 'is-try' : a.done ? '' : 'is-plan'}">${esc(initials(a.as))}</i>`).join('');
                cells.push(`<button type="button" class="rfx-cell ${ds === R.today ? 'is-today' : ''} ${d.v >= 0.7 && d.s !== 'gap' ? 'is-full' : ''}" data-day="${ds}" data-s="${d.s}" data-t="${d.t ? 1 : 0}" style="--k:${k++};--h:${Math.round(Math.min(1, d.v) * 100)}%">
                    <span class="rfx-fill"></span><span class="rfx-n">${dd}</span>${d.dl ? `<span class="rfx-dl">${esc(d.dl)}</span>` : ''}
                    ${d.c ? `<span class="rfx-crit c${d.c}" aria-label="${CRIT[d.c]}"><i></i><i></i><i></i></span>` : ''}${ap ? `<span class="rfx-ap">${ap}</span>` : ''}</button>`);
            }
            html += `<div class="rfx-month"><div class="rfx-month-h"><b>${MON[m.getMonth()]} ${m.getFullYear()}</b>${crit ? `<small>${crit} critical day${crit === 1 ? '' : 's'}</small>` : ''}</div>
                <div class="rfx-wk">${DOW.map((w) => `<span class="rfx-wd">${w}</span>`).join('')}${cells.join('')}</div></div>`;
        }
        const leg = (s, extra = '') => `<span><i style="background:${STATE[s][1]};${extra}"></i>${STATE[s][0]}</span>`;
        return `<div class="rfx-card" style="--d:.14s"><div class="rfx-card-h">The calendar <small>tap a day</small></div><div class="rfx-cal">${html}</div>
            <div class="rfx-legend">${leg('ok')}${leg('thin')}<span><i style="background:repeating-linear-gradient(135deg,#fff1f1 0 3px,#fca5a5 3px 5px)"></i>${STATE.gap[0]}</span>${R.check.kind === 'nutrient' ? leg('late') : leg('extra')}
              <span><span class="rfx-crit c3" style="position:static"><i></i><i></i><i></i></span>&nbsp;How critical the stage is</span></div></div>`;
    };
    const initials = (s) => { const w = String(s || '').replace(/[^A-Za-z0-9 ]/g, ' ').trim().split(/\s+/); return ((w[0] || '?')[0] + (w[1] ? w[1][0] : '')).toUpperCase(); };

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
            const doseWord = e.dose === 'unknown' ? 'amount not read, counted as one usual dose' : e.dose ? e.dose + ' dose' : '';
            const by = e.by && R.check.kind !== 'nutrient' ? ` by ${esc(e.by)}` : '';
            return `<div class="rfx-app ${a.try ? 'is-try' : ''}" style="--d:${.18 + Math.min(k, 10) * .04}s">
                <span class="rfx-app-d"><small>${MON[d.getMonth()]}</small><b>${d.getDate()}</b></span>
                <div class="rfx-app-t"><b>${esc(a.as)}</b>${a.try ? '<span class="rfx-tag is-try">Your try</span>' : a.done ? '<span class="rfx-tag is-done">Done</span>' : '<span class="rfx-tag">Planned</span>'}${e.conf === 'estimate' || e.conf === 'low' ? '<span class="rfx-tag is-est">Estimated</span>' : ''}
                  <small>${esc([a.title !== 'If you apply it' ? a.title : '', a.amount, a.formWord, doseWord].filter(Boolean).join(' · '))}</small>
                  <small>Works${by} from <b>${say(e.from)}</b> to about <b>${say(e.full)}</b>, fading out by ${say(e.until)} (${e.days[1]} days, ${e.days[0]} to ${e.days[2]}).</small>
                  <div class="rfx-bar"><i style="left:${pos(e.from)}%;width:${Math.max(1.5, pos(e.until) - pos(e.from))}%"></i></div>
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
        const jump = e.target.closest('[data-jump]');
        if (jump) {
            const cell = $(`.rfx-cell[data-day="${jump.dataset.jump}"]`);
            if (cell) { cell.scrollIntoView({ behavior: calm ? 'auto' : 'smooth', block: 'center' }); setTimeout(() => showDay(jump.dataset.jump), calm ? 0 : 450); }
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
        const apps = d.a.map((i) => R.apps.find((a) => a.i === i)).filter(Boolean);
        const pct = Math.round(d.v * 100);
        const col = STATE[d.s] ? STATE[d.s][1] : '#86c25a';
        dayBox.innerHTML = `<div class="rfx-day-h"><div><b>${DOW[D(ds).getDay()]}, ${sayY(ds)}</b><small>${esc([d.dl, g.label].filter(Boolean).join(' · '))}</small></div><button type="button" class="rfx-x" data-rfx-day-x aria-label="Close"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path stroke-linecap="round" d="M6 6l12 12M18 6L6 18"/></svg></button></div>
            <div class="rfx-meter"><i style="width:0;background:${col}" data-w="${Math.min(100, pct)}"></i></div>
            <p><b>${STATE[d.s] ? STATE[d.s][0] : ''}</b>. Coverage ${pct}% of one full dose. ${d.c ? 'This stage: ' + CRIT[d.c].toLowerCase() + '.' : ''} ${esc(g.why || '')}</p>
            ${apps.length ? `<ul>${apps.map((a) => `<li>• ${esc(a.as)} (${say(a.date)}${a.try ? ', your try' : ''}): working until about ${say(a.effect.full)}</li>`).join('')}</ul>` : '<p style="color:#6b7280">Nothing applied is working on this day.</p>'}`;
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
            const title = `${S.res.check.label} · ${S.res.lot.name}` + (S.tries.length ? ` · with ${S.tries.map((t) => t.name).join(', ')}` : '');
            const r = await window.api(U.save, { method: 'POST', body: { scheduleId: SID, lotId: S.lot, check: S.check, tries: S.tries, title } });
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
            S.tries = (d.params.tries || []).map((t) => ({ ...t, name: t.name || t.ai || 'Product' }));
            S.tab = S.check.startsWith('t:') ? 'prot' : S.check.startsWith('p:') ? 'pest' : ((S.opts.nutrients.find((n) => n.key === S.check) || {}).micro ? 'micro' : 'nut');
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
