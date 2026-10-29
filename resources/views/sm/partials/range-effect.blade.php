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
        'copyAnswers' => route('sm.effect.copyAnswers') . '?scheduleId=' . $schedule->id,
    ];
    // Granular and foliar, as the try box and the edit dialog both draw them.
    $rfxHowG = '<svg viewBox="0 0 32 32" aria-hidden="true"><path d="M3 25c4-2 8-2 13 0s9 2 13 0v4H3z" fill="#a8723f"/><circle cx="9" cy="13" r="3" fill="#e9d6b0" stroke="#8a5a2b" stroke-width="1.4"/><circle cx="17" cy="9" r="3" fill="#e9d6b0" stroke="#8a5a2b" stroke-width="1.4"/><circle cx="23" cy="16" r="3" fill="#e9d6b0" stroke="#8a5a2b" stroke-width="1.4"/><circle cx="14" cy="19" r="2.4" fill="#e9d6b0" stroke="#8a5a2b" stroke-width="1.4"/></svg>';
    $rfxHowF = '<svg viewBox="0 0 32 32" aria-hidden="true"><path d="M7 27c0-8 5-15 15-19-2 8-5 15-15 19z" fill="#86c25a" stroke="#3b7a1f" stroke-width="1.4" stroke-linejoin="round"/><path d="M7 27c4-5 7-9 12-14" fill="none" stroke="#3b7a1f" stroke-width="1.4" stroke-linecap="round"/><path d="M24 3c1.6 2.4 2.6 3.8 2.6 5a2.6 2.6 0 01-5.2 0c0-1.2 1-2.6 2.6-5z" fill="#38bdf8"/><path d="M28 11c1 1.5 1.6 2.4 1.6 3.1a1.6 1.6 0 01-3.2 0c0-.7.6-1.6 1.6-3.1z" fill="#38bdf8"/></svg>';
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
                {{-- What the reading counts besides coverage (the owner, 2026-10-30: "these options to get these logic
                     calculations should be a selectable option in the filtering"). --}}
                <div class="rfx-row is-sub">
                    <span class="rfx-label">Logic</span>
                    <div class="rfx-logic" data-rfx-logic role="group" aria-label="What the reading counts">
                        <button type="button" class="rfx-tog is-on" data-logic="amt" aria-pressed="true"><em aria-hidden="true">⚖️</em><span><b>Amounts</b><small>Kg a day against the need</small></span><i class="rfx-tog-s" aria-hidden="true"></i></button>
                        <button type="button" class="rfx-tog is-on" data-logic="soil" aria-pressed="true"><em aria-hidden="true">🪨</em><span><b>Your soil</b><small>Its type, pH and what it holds</small></span><i class="rfx-tog-s" aria-hidden="true"></i></button>
                        <button type="button" class="rfx-tog is-on" data-logic="links" aria-pressed="true"><em aria-hidden="true">🔗</em><span><b>Nutrient links</b><small>What one product does to another</small></span><i class="rfx-tog-s" aria-hidden="true"></i></button>
                        <button type="button" class="rfx-tog is-on" data-logic="health" aria-pressed="true"><em aria-hidden="true">🛡️</em><span><b>Crop health</b><small>Nutrients against pests and diseases</small></span><i class="rfx-tog-s" aria-hidden="true"></i></button>
                    </div>
                </div>
                <div class="rfx-row is-sub">
                    {{-- Called Fertilizers since 2026-10-30 (the owner: "change try to fertilizers"), with no limit on how many. --}}
                    <span class="rfx-label">Fertilizers</span>
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
                                    {!! $rfxHowG !!}
                                    <span><b>Granular</b><small>Spread on the soil or the water</small></span>
                                </button>
                                <button type="button" role="radio" aria-checked="false" data-how="foliar">
                                    {!! $rfxHowF !!}
                                    <span><b>Foliar</b><small>Sprayed on the leaves</small></span>
                                </button>
                            </div>
                            <div class="rfx-picked-f">
                                <div class="rfx-when"><span>Apply on</span><button type="button" class="rfx-datetag" data-date-for="data-rfx-date"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><rect x="3.5" y="5" width="17" height="15.5" rx="2.5"/><path stroke-linecap="round" d="M3.5 10h17M8 3v4M16 3v4"/></svg><b data-date-say>Pick a day</b><svg class="rfx-dt-c" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/></svg></button><input type="date" class="rfx-date-in" data-rfx-date tabindex="-1" aria-label="Apply on"></div>
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
            {{-- A saved reading opened: Save puts the changes over it; it can be renamed here or in Saved. --}}
            <div class="rfx-editing" data-rfx-editing hidden>
                <span class="rfx-editing-l">Editing</span>
                <b data-rfx-editing-t></b>
                <input type="text" data-rfx-editing-in maxlength="190" aria-label="Name of this saved reading" hidden>
                <button type="button" class="rfx-ico" data-rfx-rename-cur aria-label="Rename"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4 20h4l10.5-10.5a2.8 2.8 0 00-4-4L4 16v4z"/></svg></button>
                <button type="button" class="rfx-ico" data-rfx-stop-edit aria-label="Stop editing this saved reading" title="Start a new reading"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true"><path stroke-linecap="round" d="M6 6l12 12M18 6L6 18"/></svg></button>
            </div>
            <button type="button" class="rfx-btn is-ghost" data-rfx-save-new hidden>Save as new</button>
            <button type="button" class="rfx-btn is-go" data-rfx-save><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 4h14v17l-7-4-7 4V4z"/></svg><span data-rfx-save-t>Save this reading</span></button>
        </footer>

        {{-- Saved readings. --}}
        <div class="rfx-drawer" data-rfx-drawer hidden>
            <div class="rfx-drawer-h"><b>Saved readings</b><button type="button" class="rfx-x" data-rfx-saved-close aria-label="Close"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true"><path stroke-linecap="round" d="M6 6l12 12M18 6L6 18"/></svg></button></div>
            <div class="rfx-drawer-b" data-rfx-saved></div>
        </div>
        {{-- One day, opened. --}}
        <div class="rfx-day" data-rfx-day hidden></div>
        {{-- One growth stage, opened from its mark over the season chart (the owner, 2026-10-30: "when that is clicked,
             show a modal showing the dates where that falls"): its days, its count, Anee's realign and the delay
             counter when the lot has them, how much it matters, the coverage and what the plan puts on then. --}}
        <div class="rfx-edit rfx-stgm" data-rfx-stg hidden>
            <div class="rfx-edit-veil" data-rfx-stg-x></div>
            <div class="rfx-edit-c" role="dialog" aria-modal="true" aria-labelledby="rfxStgT" data-rfx-stg-b></div>
        </div>
        {{-- A try, opened to change it (the owner, 2026-10-30: "if i click the item that is added make it editable like
             show a modal to edit it"): its day, granular or foliar, how much; or take it out. --}}
        <div class="rfx-edit" data-rfx-edit hidden>
            <div class="rfx-edit-veil" data-rfx-edit-x></div>
            <div class="rfx-edit-c" role="dialog" aria-modal="true" aria-labelledby="rfxEditT">
                <div class="rfx-day-h"><div><b id="rfxEditT">Change this fertilizer</b><small>Its day, how it goes on, and how much</small></div><button type="button" class="rfx-x" data-rfx-edit-x aria-label="Close"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true"><path stroke-linecap="round" d="M6 6l12 12M18 6L6 18"/></svg></button></div>
                <div class="rfx-picked-p" data-rfx-edit-p></div>
                <div class="rfx-how" role="radiogroup" aria-label="How you apply it" data-rfx-edit-how data-on="granular">
                    <i class="rfx-how-pill" aria-hidden="true"></i>
                    <button type="button" role="radio" aria-checked="true" data-how="granular">{!! $rfxHowG !!}<span><b>Granular</b><small>Spread on the soil or the water</small></span></button>
                    <button type="button" role="radio" aria-checked="false" data-how="foliar">{!! $rfxHowF !!}<span><b>Foliar</b><small>Sprayed on the leaves</small></span></button>
                </div>
                <div class="rfx-picked-f">
                    <div class="rfx-when"><span>Apply on</span><button type="button" class="rfx-datetag" data-date-for="data-rfx-edit-date"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><rect x="3.5" y="5" width="17" height="15.5" rx="2.5"/><path stroke-linecap="round" d="M3.5 10h17M8 3v4M16 3v4"/></svg><b data-date-say>Pick a day</b><svg class="rfx-dt-c" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/></svg></button><input type="date" class="rfx-date-in" data-rfx-edit-date tabindex="-1" aria-label="Apply on"></div>
                    <label><span>Amount for the lot <i data-rfx-edit-ha>(optional)</i></span><span class="rfx-amt"><input type="number" min="0" step="any" inputmode="decimal" data-rfx-edit-qty placeholder="e.g. 2"><select data-rfx-edit-unit><option value="bags">bags</option><option value="kg">kg</option><option value="L">liters</option><option value="mL">mL</option><option value="g">grams</option></select></span></label>
                </div>
                <div class="rfx-edit-b">
                    <button type="button" class="rfx-btn is-ghost is-del" data-rfx-edit-del><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 7h12M9 7V5h6v2M8 7l1 12h6l1-12"/></svg>Take it out</button>
                    <button type="button" class="rfx-btn is-ghost" data-rfx-edit-x>Cancel</button>
                    <button type="button" class="rfx-btn is-go" data-rfx-edit-save>Save changes</button>
                </div>
            </div>
        </div>
    </div>
</div>

@once
@push('styles')
<style>
    /* ===================== RANGE EFFECT (rfx-) =====================
       Every colour is a token on .rfx, set again for dark mode (2026-10-30, the owner: "please fix readability and
       fonts in dark or white mode"): body text stays at 4.5:1 or better on its card, labels and axis text at 3:1 or
       better, in both modes. */
    .rfx { position: fixed; inset: 0; z-index: 420; display: grid; place-items: center;
        --rx-bg: #f4f7f0; --rx-card: #fff; --rx-soft: #f5f8f1; --rx-line: #e1ead6; --rx-line-2: #cddcbd; --rx-hover: #a9c98c;
        --rx-ink: #132009; --rx-text: #2e3929; --rx-muted: #525e4e; --rx-faint: #687463; --rx-accent: #3b6b1f;
        --rx-chip-ink: #2f5219; --rx-on: #2f5219; --rx-track: #e6eedf; --rx-dot: #d1d5db; --rx-veil: rgb(10 16 7 / .55);
        --rx-gap-a: #fff1f1; --rx-gap-b: #ffe0e0; --rx-gap-edge: #f19c9c; --rx-gap-bg: #fef2f2; --rx-gap-line: #fecaca; --rx-gap-ink: #991b1b;
        --rx-thin-bg: #fffbeb; --rx-thin-line: #fde68a; --rx-thin-ink: #92400e; --rx-thin-edge: #f59e0b;
        --rx-late-bg: #f5f3ff; --rx-late-line: #ddd6fe; --rx-late-ink: #5b21b6; --rx-late-edge: #a78bfa;
        --rx-gold-bg: #fff6d6; --rx-gold-line: #f3e3a3; --rx-gold-ink: #5c4500;
        --rx-try-bg: #fffdf3; --rx-try-line: #f0dfa0; --rx-field: #fff; --rx-field-line: #e3d9b0; --rx-res-icon: #f6f1dc; --rx-how-bg: #f6efd2; --rx-how-ink: #6e5a1c;
        --rx-saved-bg: #eef2ff; --rx-saved-line: #c7d2fe; --rx-saved-ink: #1e3a8a;
        --rx-done-bg: #dcfce7; --rx-done-ink: #14532d; --rx-tag-bg: #eef1ea;
        --rx-badge: #1f3a10; --rx-badge-ink: #fff; --rx-today: #2563eb;
        /* What an application is doing: in the soil, being taken up, strongest, easing, fading away. */
        --ph-soil: linear-gradient(180deg, #c99158, #8a5a2b); --ph-absorb: linear-gradient(180deg, #5fcfba, #12806f); --ph-strong: linear-gradient(180deg, #4f9030, #22500e);
        --ph-easing: linear-gradient(180deg, #a6d67c, #6aa843); --ph-fading: linear-gradient(180deg, #e9f5dc, #c6e3ab); }
    html.dark .rfx {
        --rx-bg: #0d120a; --rx-card: #151c11; --rx-soft: #1b2416; --rx-line: #2c3b22; --rx-line-2: #3d5230; --rx-hover: #6b8f50;
        --rx-ink: #eef5e8; --rx-text: #d6e2cc; --rx-muted: #b1c0a5; --rx-faint: #93a487; --rx-accent: #a9d982;
        --rx-chip-ink: #d3e8bf; --rx-on: #4a8128; --rx-track: #26331d; --rx-dot: #3d4c34; --rx-veil: rgb(0 0 0 / .65);
        --rx-gap-a: #3a1717; --rx-gap-b: #2b1010; --rx-gap-edge: #b33a3a; --rx-gap-bg: rgb(220 38 38 / .14); --rx-gap-line: rgb(248 113 113 / .38); --rx-gap-ink: #fca5a5;
        --rx-thin-bg: rgb(245 158 11 / .13); --rx-thin-line: rgb(251 191 36 / .38); --rx-thin-ink: #fcd34d; --rx-thin-edge: #f59e0b;
        --rx-late-bg: rgb(139 92 246 / .16); --rx-late-line: rgb(167 139 250 / .42); --rx-late-ink: #c9bcff; --rx-late-edge: #a78bfa;
        --rx-gold-bg: rgb(245 197 24 / .12); --rx-gold-line: rgb(245 197 24 / .38); --rx-gold-ink: #f7dc7c;
        --rx-try-bg: #1c1a0e; --rx-try-line: #4d4421; --rx-field: #11170d; --rx-field-line: #4d4421; --rx-res-icon: #2a2614; --rx-how-bg: #2a2512; --rx-how-ink: #e3cf86;
        --rx-saved-bg: rgb(99 102 241 / .16); --rx-saved-line: rgb(129 140 248 / .42); --rx-saved-ink: #c7d2fe;
        --rx-done-bg: rgb(34 197 94 / .18); --rx-done-ink: #86efac; --rx-tag-bg: #26331d;
        --rx-badge: #e3f1d6; --rx-badge-ink: #1f3a10; --rx-today: #60a5fa; }
    .rfx-sw { display: inline-block; flex: none; width: .9rem; height: .9rem; border-radius: .25rem; background: var(--ph-strong); vertical-align: -.1rem; }
    .rfx .is-soil { --ph: var(--ph-soil); } .rfx .is-absorb { --ph: var(--ph-absorb); } .rfx .is-strong { --ph: var(--ph-strong); } .rfx .is-easing { --ph: var(--ph-easing); } .rfx .is-fading { --ph: var(--ph-fading); }
    .rfx .rfx-sw[class*=is-] { background: var(--ph); }
    .rfx[hidden] { display: none; }
    .rfx-veil { position: absolute; inset: 0; background: var(--rx-veil); backdrop-filter: blur(3px); opacity: 0; transition: opacity .28s cubic-bezier(.22,1,.36,1); }
    .rfx-box { position: relative; display: flex; flex-direction: column; width: 100%; height: 100%; color: var(--rx-text); background: var(--rx-bg); overflow: hidden;
        opacity: 0; transform: translateY(18px) scale(.985); transition: opacity .32s cubic-bezier(.22,1,.36,1), transform .32s cubic-bezier(.22,1,.36,1); }
    @media (min-width: 900px) { .rfx-box { width: min(96vw, 82rem); height: min(94vh, 60rem); border-radius: 1.4rem; box-shadow: 0 40px 90px -30px rgb(0 0 0 / .6); } }
    .rfx.is-on .rfx-veil { opacity: 1; }
    .rfx.is-on .rfx-box { opacity: 1; transform: none; }
    html.rfx-lock { overflow: hidden; }
    .rfx-head { display: flex; align-items: center; gap: .8rem; padding: .85rem 1rem; background: linear-gradient(135deg, #1f3a12, #2f5219 60%, #3b6620); color: #fff; }
    .rfx-logo { display: grid; place-items: center; flex: none; width: 2.6rem; height: 2.6rem; border-radius: .9rem; color: #14210c; background: #f5c518; box-shadow: 0 0 0 4px rgb(245 197 24 / .25); }
    .rfx-logo svg { width: 1.45rem; height: 1.45rem; }
    .rfx-head-t { min-width: 0; flex: 1; }
    .rfx-head h2 { font-family: var(--font-heading); font-size: 1.2rem; font-weight: 800; line-height: 1.2; color: #fff; }
    .rfx-head p { margin-top: .1rem; font-size: .82rem; color: #dcebd0; line-height: 1.35; }
    .rfx-hbtn { position: relative; display: inline-flex; align-items: center; gap: .4rem; padding: .5rem .8rem; border-radius: .8rem; font-size: .86rem; font-weight: 800; color: #fff;
        background: rgb(255 255 255 / .12); box-shadow: inset 0 0 0 1px rgb(255 255 255 / .25); transition: background-color .28s cubic-bezier(.22,1,.36,1); }
    .rfx-hbtn:hover { background: rgb(255 255 255 / .2); }
    .rfx-hbtn svg { width: 1.05rem; height: 1.05rem; }
    .rfx-hbtn b { position: absolute; top: -.35rem; right: -.35rem; min-width: 1.2rem; height: 1.2rem; padding: 0 .3rem; border-radius: 999px; font-size: .68rem; line-height: 1.2rem; color: #14210c; background: #f5c518; }
    .rfx-x { display: grid; place-items: center; flex: none; width: 2.4rem; height: 2.4rem; border-radius: 999px; color: inherit; background: rgb(255 255 255 / .12); transition: background-color .28s, transform .28s cubic-bezier(.22,1,.36,1); }
    .rfx-x:hover { background: rgb(255 255 255 / .22); transform: rotate(90deg); }
    .rfx-x svg { width: 1.15rem; height: 1.15rem; }
    @media (max-width: 639.98px) { .rfx-head p { display: none; } .rfx-hbtn span { display: none; } .rfx-hbtn { padding: .55rem; } }
    .rfx-scroll { flex: 1; overflow: auto; overscroll-behavior: contain; }
    .rfx-controls { position: relative; z-index: 3; display: grid; gap: .6rem; padding: .9rem 1rem; background: var(--rx-card); border-bottom: 1px solid var(--rx-line); }
    .rfx-row { display: grid; grid-template-columns: 4.6rem minmax(0, 1fr); gap: .6rem; align-items: center; }
    .rfx-row.is-sub { align-items: start; }
    .rfx-row.is-sub .rfx-label { padding-top: .5rem; }
    .rfx-label { font-size: .72rem; font-weight: 900; letter-spacing: .08em; text-transform: uppercase; color: var(--rx-faint); }
    @media (max-width: 639.98px) { .rfx-row { grid-template-columns: minmax(0, 1fr); gap: .35rem; } .rfx-row.is-sub .rfx-label { display: none; } }
    .rfx-chips, .rfx-picks { display: flex; gap: .4rem; overflow-x: auto; scrollbar-width: none; padding: .15rem .1rem; }
    .rfx-picks { flex-wrap: wrap; overflow: visible; }
    .rfx-chips::-webkit-scrollbar { display: none; }
    .rfx-chip { flex: none; display: inline-flex; align-items: center; gap: .35rem; padding: .45rem .8rem; border-radius: 999px; font-size: .86rem; font-weight: 800; white-space: nowrap; color: var(--rx-chip-ink); background: var(--rx-card);
        box-shadow: inset 0 0 0 1.5px var(--rx-line-2); cursor: pointer; transition: background-color .28s cubic-bezier(.22,1,.36,1), color .28s, box-shadow .28s, transform .28s cubic-bezier(.22,1,.36,1); }
    .rfx-chip:hover { transform: translateY(-1px); box-shadow: inset 0 0 0 1.5px var(--rx-hover); }
    .rfx-chip.is-on { color: #fff; background: var(--rx-on); box-shadow: 0 8px 18px -10px rgb(47 82 25 / .8); }
    .rfx-chip small { font-size: .74rem; font-weight: 700; opacity: .8; }
    .rfx-chip.is-try { color: var(--rx-gold-ink); background: var(--rx-gold-bg); box-shadow: inset 0 0 0 1.5px #f5c518; cursor: pointer; }
    .rfx-chip.is-try:hover { box-shadow: inset 0 0 0 2px #f5c518, 0 8px 16px -10px rgb(184 138 0 / .8); }
    .rfx-chip.is-try .rfx-pen { width: .8rem; height: .8rem; opacity: .7; }
    .rfx-chip.is-try button, .rfx-sel .rfx-chip button { display: grid; place-items: center; width: 1.25rem; height: 1.25rem; border-radius: 999px; background: rgb(128 128 128 / .2); }
    .rfx-chip.is-try button { margin-right: -.3rem; }
    .rfx-chip.is-try button svg, .rfx-sel .rfx-chip button svg { width: .68rem; height: .68rem; }
    .rfx-tabs { display: flex; gap: .3rem; padding: .25rem; border-radius: 1rem; background: var(--rx-soft); box-shadow: inset 0 0 0 1px var(--rx-line); overflow-x: auto; scrollbar-width: none; }
    .rfx-tabs button { flex: 1 0 auto; padding: .52rem .8rem; border-radius: .75rem; font-size: .86rem; font-weight: 800; white-space: nowrap; color: var(--rx-muted); transition: background-color .28s cubic-bezier(.22,1,.36,1), color .28s, box-shadow .28s; }
    .rfx-tabs button:hover { color: var(--rx-ink); }
    .rfx-tabs button.is-on { color: var(--rx-ink); background: var(--rx-card); box-shadow: 0 4px 12px -6px rgb(0 0 0 / .35), inset 0 0 0 1px var(--rx-line); }
    @media (max-width: 559.98px) { .rfx-tabs { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); } .rfx-tabs button { padding: .52rem .4rem; font-size: .82rem; } }
    .rfx-picks .rfx-chip { animation: rfxPop .32s cubic-bezier(.22,1,.36,1) both; animation-delay: calc(var(--k, 0) * 25ms); }
    @keyframes rfxPop { from { opacity: 0; transform: translateY(6px) scale(.96); } }
    .rfx-picks input[type=search] { flex: 1 1 100%; padding: .55rem .8rem; border-radius: .8rem; border: 1.5px solid var(--rx-line-2); font-size: .92rem; color: var(--rx-ink); background: var(--rx-field); }
    .rfx-tries { display: grid; justify-items: start; gap: .45rem; }
    .rfx-chips.is-wrap { flex-wrap: wrap; overflow: visible; padding: 0; }
    .rfx-chips.is-wrap:empty { display: none; }
    .rfx-chips.is-wrap .rfx-chip { max-width: 100%; white-space: normal; animation: rfxPop .32s cubic-bezier(.22,1,.36,1) both; }
    .rfx-add { display: inline-flex; align-items: center; gap: .35rem; padding: .52rem .95rem; border-radius: 999px; font-size: .86rem; font-weight: 800; color: #14210c; background: #f5c518;
        box-shadow: 0 8px 18px -10px rgb(184 138 0 / .9); transition: background-color .28s, transform .28s cubic-bezier(.22,1,.36,1), box-shadow .28s; }
    .rfx-add:hover { background: #ffd43b; transform: translateY(-1px); }
    .rfx-add svg { width: .9rem; height: .9rem; }
    /* Several checks: the one the calendar shows is filled, the rest are outlined in their own colour. */
    .rfx-sel { display: flex; flex-wrap: wrap; gap: .4rem; }
    .rfx-sel .rfx-chip { gap: .4rem; padding: .35rem .45rem .35rem .7rem; animation: rfxPop .32s cubic-bezier(.22,1,.36,1) both; }
    .rfx-sel .rfx-chip i { width: .65rem; height: .65rem; border-radius: 999px; background: var(--sc); }
    .rfx-sel .rfx-chip.is-on { background: var(--sc); color: #fff; }
    .rfx-sel .rfx-chip.is-on i { background: #fff; }
    .rfx-picks .rfx-chip.is-on::after { content: '✓'; font-size: .78rem; font-weight: 900; }
    .rfx-hint { align-self: center; font-size: .78rem; font-weight: 600; color: var(--rx-faint); }
    .rfx-muted { font-size: .84rem; color: var(--rx-muted); }
    .rfx-try { overflow: hidden; }
    .rfx-try-in { display: grid; gap: .6rem; padding: .8rem; border-radius: 1.1rem; background: var(--rx-try-bg); box-shadow: inset 0 0 0 1.5px var(--rx-try-line); animation: rfxSlide .32s cubic-bezier(.22,1,.36,1) both; }
    @keyframes rfxSlide { from { opacity: 0; transform: translateY(-8px); } }
    .rfx-search { display: flex; align-items: center; gap: .5rem; padding: 0 .8rem; border-radius: .9rem; background: var(--rx-field); box-shadow: inset 0 0 0 1.5px var(--rx-field-line); }
    .rfx-search svg { width: 1.1rem; height: 1.1rem; color: #b8941c; flex: none; }
    .rfx-search input { flex: 1; min-width: 0; padding: .65rem 0; border: 0; background: transparent; font-size: .96rem; color: var(--rx-ink); outline: none; }
    .rfx input::placeholder { color: var(--rx-faint); opacity: 1; }
    .rfx-results { display: grid; gap: .35rem; max-height: 16rem; overflow: auto; }
    .rfx-res { display: flex; align-items: center; gap: .6rem; padding: .6rem .7rem; border-radius: .8rem; text-align: left; background: var(--rx-card); box-shadow: inset 0 0 0 1px var(--rx-field-line);
        transition: box-shadow .2s, transform .2s cubic-bezier(.22,1,.36,1); animation: rfxPop .28s cubic-bezier(.22,1,.36,1) both; animation-delay: calc(var(--k, 0) * 22ms); }
    .rfx-res:hover { box-shadow: inset 0 0 0 1.5px #f5c518; transform: translateX(2px); }
    .rfx-res em { flex: none; display: grid; place-items: center; width: 2.2rem; height: 2.2rem; border-radius: .7rem; font-style: normal; font-size: 1.05rem; background: var(--rx-res-icon); }
    .rfx-res b, .rfx-picked-p b { display: block; font-size: .9rem; color: var(--rx-ink); }
    .rfx-res small, .rfx-picked-p small { display: block; font-size: .76rem; color: var(--rx-muted); line-height: 1.35; }
    .rfx-picked { display: grid; gap: .6rem; }
    .rfx-picked-p { display: flex; align-items: center; gap: .6rem; padding: .7rem; border-radius: .9rem; background: var(--rx-card); box-shadow: inset 0 0 0 1.5px #f5c518; }
    .rfx-picked-p em { font-style: normal; font-size: 1.3rem; }
    .rfx-picked-f { display: grid; gap: .6rem; grid-template-columns: repeat(2, minmax(0, 1fr)); }
    @media (max-width: 559.98px) { .rfx-picked-f { grid-template-columns: minmax(0, 1fr); } }
    .rfx-picked-f label { display: grid; gap: .25rem; font-size: .78rem; font-weight: 800; color: var(--rx-muted); }
    .rfx-picked-f label i { font-style: normal; font-weight: 600; }
    .rfx-picked-f input, .rfx-picked-f select, .rfx-look-f input { width: 100%; padding: .6rem .7rem; border-radius: .75rem; border: 1.5px solid var(--rx-field-line); font-size: .94rem; background: var(--rx-field); color: var(--rx-ink); color-scheme: light; }
    html.dark .rfx-picked-f input, html.dark .rfx-picked-f select { color-scheme: dark; }
    .rfx-amt { display: grid; grid-template-columns: minmax(0, 1fr) 6.5rem; gap: .4rem; }
    .rfx-how { position: relative; display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .3rem; padding: .25rem; border-radius: 1rem; background: var(--rx-how-bg); }
    .rfx-how button { position: relative; z-index: 1; display: flex; align-items: center; gap: .55rem; min-width: 0; padding: .55rem .7rem; border-radius: .8rem; text-align: left; color: var(--rx-how-ink);
        transition: color .28s cubic-bezier(.22,1,.36,1); }
    .rfx-how button[aria-checked=true] { color: var(--rx-ink); }
    .rfx-how svg { flex: none; width: 1.7rem; height: 1.7rem; transition: transform .28s cubic-bezier(.22,1,.36,1); }
    .rfx-how button[aria-checked=true] svg { transform: scale(1.12) rotate(-4deg); }
    .rfx-how b { display: block; font-size: .9rem; }
    .rfx-how small { display: block; font-size: .74rem; font-weight: 600; line-height: 1.3; opacity: .9; }
    .rfx-how-pill { position: absolute; z-index: 0; top: .25rem; bottom: .25rem; left: .25rem; width: calc(50% - .4rem); border-radius: .8rem; background: var(--rx-card);
        box-shadow: inset 0 0 0 1.5px #f5c518, 0 6px 14px -8px rgb(92 69 0 / .5); transition: transform .28s cubic-bezier(.22,1,.36,1); }
    .rfx-how[data-on=foliar] .rfx-how-pill { transform: translateX(calc(100% + .3rem)); }
    .rfx-picked-b { display: flex; justify-content: flex-end; gap: .5rem; }
    .rfx-btn { display: inline-flex; align-items: center; justify-content: center; gap: .45rem; padding: .62rem 1rem; border-radius: .85rem; font-size: .9rem; font-weight: 800;
        transition: transform .28s cubic-bezier(.22,1,.36,1), box-shadow .28s, background-color .28s; }
    .rfx-btn svg { width: 1.05rem; height: 1.05rem; }
    .rfx-btn:hover { transform: translateY(-1px); }
    .rfx-btn.is-go { color: #fff; background: #2f5219; box-shadow: 0 10px 20px -12px rgb(47 82 25 / .8); }
    html.dark .rfx-btn.is-go { background: #3f7322; }
    .rfx-btn.is-gold { color: #14210c; background: #f5c518; box-shadow: 0 10px 20px -12px rgb(184 138 0 / .8); }
    .rfx-btn.is-ghost { color: var(--rx-text); background: var(--rx-card); box-shadow: inset 0 0 0 1.5px var(--rx-line-2); }
    .rfx-btn:disabled { opacity: .55; transform: none; }
    .rfx-look { display: flex; gap: .8rem; align-items: flex-start; padding: .8rem; border-radius: .9rem; background: var(--rx-card); box-shadow: inset 0 0 0 1px var(--rx-field-line); }
    .rfx-look > img { flex: none; width: 3rem; height: 3rem; }
    .rfx-look b { font-size: .94rem; color: var(--rx-ink); }
    .rfx-look p { margin-top: .2rem; font-size: .84rem; line-height: 1.5; color: var(--rx-muted); }
    .rfx-look-f { display: grid; gap: .45rem; grid-template-columns: repeat(2, minmax(0, 1fr)); margin: .6rem 0; }
    @media (max-width: 559.98px) { .rfx-look-f { grid-template-columns: minmax(0, 1fr); } }
    .rfx-look small { display: block; margin-top: .45rem; font-size: .76rem; color: var(--rx-muted); }

    /* ---- the reading ---- */
    .rfx-body { padding: 1rem; display: grid; gap: 1rem; }
    @media (min-width: 900px) { .rfx-body { padding: 1.2rem 1.4rem 1.6rem; } }
    /* Grid items keep their content's width unless told: the chart's scroll box would widen the whole sheet. */
    .rfx-body > *, .rfx-controls > *, .rfx-row > * { min-width: 0; }
    .rfx-loading { display: flex; justify-content: center; gap: .4rem; padding: 4rem 0; }
    .rfx-loading span { width: .7rem; height: .7rem; border-radius: 999px; background: #4a7c2a; animation: rfxDot 1s ease-in-out infinite; }
    .rfx-loading span:nth-child(2) { animation-delay: .15s; } .rfx-loading span:nth-child(3) { animation-delay: .3s; }
    @keyframes rfxDot { 50% { transform: translateY(-8px); opacity: .5; } }
    .rfx-card { border-radius: 1.2rem; background: var(--rx-card); box-shadow: 0 0 0 1px var(--rx-line), 0 18px 36px -30px rgb(0 0 0 / .5); animation: rfxIn .45s cubic-bezier(.22,1,.36,1) both; animation-delay: var(--d, 0s); }
    @keyframes rfxIn { from { opacity: 0; transform: translateY(12px); } }
    .rfx-card-h { display: flex; align-items: center; gap: .5rem; padding: .95rem 1rem .25rem; font-size: .76rem; font-weight: 900; letter-spacing: .08em; text-transform: uppercase; color: var(--rx-accent); }
    .rfx-card-h small { margin-left: auto; font-size: .78rem; font-weight: 700; letter-spacing: 0; text-transform: none; color: var(--rx-muted); }
    .rfx-banner { display: flex; align-items: center; gap: .7rem; padding: .75rem 1rem; border-radius: 1rem; font-size: .88rem; font-weight: 700; line-height: 1.5; color: var(--rx-gold-ink); background: var(--rx-gold-bg); box-shadow: inset 0 0 0 1px var(--rx-gold-line); }
    .rfx-banner b { color: inherit; }
    .rfx-banner.is-saved { color: var(--rx-saved-ink); background: var(--rx-saved-bg); box-shadow: inset 0 0 0 1px var(--rx-saved-line); }
    .rfx-banner .rfx-btn { margin-left: auto; padding: .45rem .8rem; font-size: .82rem; }
    .rfx-banner.is-miss { flex-wrap: wrap; font-weight: 600; animation: rfxIn .4s cubic-bezier(.22,1,.36,1) both; }
    .rfx-banner.is-miss > span:first-child { flex: 1 1 16rem; }
    .rfx-acts { display: flex; flex-wrap: wrap; gap: .35rem; }
    .rfx-acts .rfx-chip { padding: .38rem .75rem; font-size: .8rem; }
    /* The summary. */
    .rfx-sum { display: grid; gap: 1rem; padding: 1rem; grid-template-columns: auto minmax(0, 1fr); align-items: center; }
    @media (max-width: 559.98px) { .rfx-sum { grid-template-columns: minmax(0, 1fr); justify-items: center; text-align: center; } }
    .rfx-ring { position: relative; width: 8.2rem; aspect-ratio: 1; }
    .rfx-ring svg { width: 100%; height: 100%; transform: rotate(-90deg); }
    .rfx-ring circle { fill: none; stroke-width: 10; }
    .rfx-ring .t, .rfx-mini .t { stroke: var(--rx-track); }
    .rfx-ring .v { stroke-linecap: round; stroke-dasharray: 0 400; transition: stroke-dasharray 1.2s cubic-bezier(.22,1,.36,1); }
    .rfx-ring-n { position: absolute; inset: 0; display: grid; place-items: center; align-content: center; text-align: center; }
    .rfx-ring-n b { font-family: var(--font-heading); font-size: 2rem; font-weight: 900; line-height: 1; color: var(--rx-ink); }
    .rfx-ring-n small { margin-top: .25rem; font-size: .7rem; font-weight: 800; color: var(--rx-muted); line-height: 1.25; max-width: 5.8rem; }
    .rfx-say h3 { font-family: var(--font-heading); font-size: 1.18rem; font-weight: 800; line-height: 1.3; color: var(--rx-ink); }
    .rfx-say p { margin-top: .35rem; font-size: .92rem; line-height: 1.6; color: var(--rx-muted); }
    .rfx-counts { display: flex; flex-wrap: wrap; gap: .4rem; margin-top: .7rem; }
    .rfx-counts span { display: inline-flex; align-items: center; gap: .35rem; padding: .32rem .7rem; border-radius: 999px; font-size: .8rem; font-weight: 800; background: var(--rx-soft); color: var(--rx-text); box-shadow: inset 0 0 0 1px var(--rx-line); }
    .rfx-counts i { width: .6rem; height: .6rem; border-radius: 3px; }
    @media (max-width: 559.98px) { .rfx-counts { justify-content: center; } }
    .rfx-runs { display: grid; gap: .45rem; padding: 0 1rem 1rem; }
    .rfx-run { display: flex; align-items: flex-start; gap: .6rem; padding: .65rem .8rem; border-radius: .85rem; font-size: .88rem; line-height: 1.5; color: var(--rx-text); background: var(--rx-gap-bg); box-shadow: inset 0 0 0 1px var(--rx-gap-line); cursor: pointer;
        transition: transform .2s cubic-bezier(.22,1,.36,1); animation: rfxIn .4s cubic-bezier(.22,1,.36,1) both; animation-delay: var(--d, 0s); }
    .rfx-run:hover { transform: translateX(3px); }
    .rfx-run b { color: var(--rx-gap-ink); }
    .rfx-run.is-thin { background: var(--rx-thin-bg); box-shadow: inset 0 0 0 1px var(--rx-thin-line); } .rfx-run.is-thin b { color: var(--rx-thin-ink); }
    .rfx-run.is-late { background: var(--rx-late-bg); box-shadow: inset 0 0 0 1px var(--rx-late-line); } .rfx-run.is-late b { color: var(--rx-late-ink); }
    .rfx-run em { flex: none; font-style: normal; }
    /* The season chart. Its text is sized for the drawing, which a phone scrolls sideways at 46rem. */
    .rfx-chart { padding: .2rem 1rem 1rem; }
    .rfx-chart-w { position: relative; overflow-x: auto; scrollbar-width: thin; }
    .rfx-chart svg { display: block; min-width: 46rem; width: 100%; height: auto; overflow: visible; }
    .rfx-chart .cov-l { fill: none; stroke: var(--rx-accent); stroke-width: 2.2; stroke-linejoin: round; }
    .rfx-chart .rib { transform-box: fill-box; transform-origin: left; animation: rfxRib .9s cubic-bezier(.22,1,.36,1) both; animation-delay: calc(.3s + var(--k, 0) * 40ms); }
    @keyframes rfxRib { from { transform: scaleX(0); opacity: 0; } }
    .rfx-chart .try-l { fill: none; stroke: #e0a800; stroke-width: 2.2; stroke-dasharray: 5 4; }
    .rfx-chart .reveal { clip-path: inset(0 100% 0 0); animation: rfxWipe 1.3s cubic-bezier(.45,0,.25,1) .15s forwards; }
    @keyframes rfxWipe { to { clip-path: inset(0 0 0 0); } }
    .rfx-chart .ln { transition: opacity .28s cubic-bezier(.22,1,.36,1); }
    .rfx-chart .ln.is-off { opacity: 0; pointer-events: none; }
    /* The chart's switches: filled in its colour when shown, a ring of it when hidden, a plus when not picked yet. */
    .rfx-lnrow { display: flex; flex-wrap: wrap; align-items: center; gap: .4rem; padding: 0 1rem .55rem; }
    .rfx-ln { display: inline-flex; align-items: center; gap: .4rem; min-height: 2rem; padding: .3rem .75rem .3rem .55rem; border-radius: 999px; font-size: .82rem; font-weight: 800; color: var(--rx-text); background: var(--rx-card); box-shadow: inset 0 0 0 1.5px var(--rx-line-2);
        transition: background-color .28s cubic-bezier(.22,1,.36,1), color .28s cubic-bezier(.22,1,.36,1), box-shadow .28s cubic-bezier(.22,1,.36,1), transform .2s, opacity .2s; animation: rfxPop .32s cubic-bezier(.22,1,.36,1) both; animation-delay: calc(var(--k, 0) * 30ms); }
    .rfx-ln:hover { transform: translateY(-1px); box-shadow: inset 0 0 0 1.5px var(--rx-hover); }
    .rfx-ln:focus-visible { outline: 2px solid var(--rx-accent); outline-offset: 2px; }
    .rfx-ln i { flex: none; width: .7rem; height: .7rem; border-radius: 999px; box-shadow: inset 0 0 0 2px var(--sc); transition: background-color .28s cubic-bezier(.22,1,.36,1), box-shadow .28s cubic-bezier(.22,1,.36,1); }
    .rfx-ln svg { flex: none; width: .75rem; height: .75rem; opacity: .7; }
    .rfx-ln small { font-size: .68rem; font-weight: 700; opacity: .85; }
    .rfx-ln.is-on { color: #fff; background: var(--sc); box-shadow: 0 8px 16px -12px var(--sc); }
    .rfx-ln.is-on i { background: #fff; box-shadow: none; }
    html.dark .rfx-ln.is-on { color: #111a0c; }
    html.dark .rfx-ln.is-on i { background: #111a0c; }
    .rfx-ln:not(.is-on):not(.is-add) { color: var(--rx-muted); }
    .rfx-ln.is-add { color: var(--rx-muted); background: transparent; box-shadow: inset 0 0 0 1.5px var(--rx-line); }
    .rfx-ln.is-busy { opacity: .55; pointer-events: none; }
    .rfx-chart .grid { stroke: var(--rx-line); stroke-width: 1; }
    .rfx-chart .base { stroke: var(--rx-line-2); stroke-width: 1.2; }
    .rfx-chart .dose { stroke: var(--rx-faint); stroke-width: 1; stroke-dasharray: 2 4; }
    .rfx-chart .stg { font-size: 12px; font-weight: 800; fill: var(--rx-muted); }
    .rfx-chart .mo { font-size: 12px; font-weight: 800; fill: var(--rx-faint); }
    .rfx-chart .today { stroke: var(--rx-today); stroke-width: 1.5; stroke-dasharray: 3 3; }
    .rfx-chart .t-today { fill: var(--rx-today); }
    .rfx-chart .harv { stroke: #d97706; stroke-width: 1.5; }
    .rfx-chart .t-harv { fill: #c2650a; }
    html.dark .rfx-chart .t-harv { fill: #fbbf24; }
    .rfx-chart .app { stroke-width: 1.8; cursor: pointer; transition: r .2s; }
    .rfx-chart .app.is-done { fill: var(--rx-ink); stroke: var(--rx-card); }
    .rfx-chart .app.is-plan { fill: var(--rx-card); stroke: var(--rx-ink); }
    .rfx-chart .app.is-try { fill: #f5c518; stroke: #b8860b; }
    .rfx-chart .app:hover { r: 6.5; }
    /* The calendar. */
    .rfx-cal { display: grid; gap: .4rem 1.1rem; grid-template-columns: repeat(auto-fill, minmax(min(100%, 21rem), 1fr)); padding: .2rem .8rem 1rem; }
    .rfx-month { margin-top: .8rem; min-width: 0; }
    .rfx-month-h { display: flex; align-items: baseline; gap: .6rem; padding: 0 .2rem .4rem; }
    .rfx-month-h b { font-family: var(--font-heading); font-size: 1.05rem; font-weight: 800; color: var(--rx-ink); }
    .rfx-month-h small { font-size: .78rem; color: var(--rx-muted); }
    .rfx-wk { display: grid; grid-template-columns: repeat(7, minmax(0, 1fr)); gap: .3rem; }
    .rfx-wd { padding: 0 .2rem .2rem; font-size: .66rem; font-weight: 900; letter-spacing: .06em; text-align: center; text-transform: uppercase; color: var(--rx-faint); }
    .rfx-cell { position: relative; min-height: 3.5rem; padding: .3rem .35rem; border-radius: .7rem; overflow: hidden; text-align: left; background: var(--rx-card); box-shadow: inset 0 0 0 1px var(--rx-line);
        transition: transform .2s cubic-bezier(.22,1,.36,1), box-shadow .2s; }
    .rfx-cell:hover { transform: translateY(-2px); box-shadow: inset 0 0 0 1.5px var(--rx-hover), 0 10px 18px -12px rgb(0 0 0 / .5); z-index: 1; }
    .rfx-cell.is-out { background: transparent; box-shadow: none; pointer-events: none; }
    .rfx-cell.is-out .rfx-n { color: var(--rx-faint); opacity: .6; }
    .rfx-fill { position: absolute; left: 0; right: 0; bottom: 0; height: 0; overflow: hidden; background: linear-gradient(180deg, #86c25a, #4a7c2a);
        transition: height .7s cubic-bezier(.22,1,.36,1); transition-delay: calc(var(--k, 0) * 6ms); }
    .rfx-cal.is-in .rfx-fill { height: var(--h, 0%); }
    /* The fill's colour is what the strongest product is doing that day; the cell's edge is what it means for the crop. */
    .rfx-cell[data-p=absorb] .rfx-fill { background: var(--ph-absorb); }
    .rfx-cell[data-p=strong] .rfx-fill { background: var(--ph-strong); }
    .rfx-cell[data-p=easing] .rfx-fill { background: var(--ph-easing); }
    .rfx-cell[data-p=fading] .rfx-fill { background: var(--ph-fading); }
    .rfx-cell[data-p=absorb] .rfx-fill::before { content: ''; position: absolute; left: 0; right: 0; top: -100%; height: 300%;
        background: linear-gradient(0deg, transparent 38%, rgb(255 255 255 / .38) 50%, transparent 62%); animation: rfxRise 2.4s linear infinite; }
    @keyframes rfxRise { from { transform: translateY(33%); } to { transform: translateY(-33%); } }
    /* Still in the soil: an earth band at the foot of the day (urea turning into ammonium, a salt dissolving, manure breaking down). */
    .rfx-soil { position: absolute; left: 0; right: 0; bottom: 0; height: 30%; transform: scaleY(0); transform-origin: bottom;
        background: radial-gradient(circle, rgb(255 236 200 / .55) 0 1px, transparent 1.6px) 0 0 / 6px 5px, var(--ph-soil); animation: rfxSoil 7s linear infinite;
        transition: transform .6s cubic-bezier(.22,1,.36,1); transition-delay: calc(var(--k, 0) * 6ms + .15s); }
    .rfx-cal.is-in .rfx-soil { transform: none; }
    .rfx-cell[data-p=soil] .rfx-soil { height: 42%; }
    @keyframes rfxSoil { to { background-position: 12px -10px, 0 0; } }
    .rfx-cell[data-s=gap] { background: repeating-linear-gradient(135deg, var(--rx-gap-a) 0 6px, var(--rx-gap-b) 6px 12px); box-shadow: inset 0 0 0 1.5px var(--rx-gap-edge); }
    .rfx-cell[data-s=thin] { box-shadow: inset 0 0 0 2px var(--rx-thin-edge); }
    .rfx-cell[data-s=late], .rfx-cell[data-s=extra] { box-shadow: inset 0 0 0 2px var(--rx-late-edge); }
    .rfx-cell[data-s=late] .rfx-fill::after, .rfx-cell[data-s=extra] .rfx-fill::after { content: ''; position: absolute; inset: 0; background: repeating-linear-gradient(135deg, rgb(124 58 237 / .5) 0 3px, transparent 3px 7px); }
    .rfx-cell.is-today { box-shadow: inset 0 0 0 2px var(--rx-today); }
    .rfx-cell[data-t="1"]::after { content: ''; position: absolute; inset: 0; border-radius: inherit; box-shadow: inset 0 0 0 2px #f5c518; pointer-events: none; animation: rfxTry 1.8s ease-in-out infinite; }
    @keyframes rfxTry { 50% { box-shadow: inset 0 0 0 2px rgb(245 197 24 / .35); } }
    .rfx-n { position: relative; z-index: 1; display: block; font-size: .84rem; font-weight: 900; line-height: 1.2; color: var(--rx-ink); }
    .rfx-dl { position: relative; z-index: 1; display: block; font-size: .6rem; font-weight: 800; color: var(--rx-muted); white-space: nowrap; }
    /* A day filled past its numbers: white on the deep fills, near black on the pale ones, in either mode. */
    .rfx-cell.is-full .rfx-n { color: #fff; text-shadow: 0 1px 2px rgb(0 0 0 / .35); } .rfx-cell.is-full .rfx-dl { color: rgb(255 255 255 / .92); text-shadow: 0 1px 2px rgb(0 0 0 / .35); }
    .rfx-cell.is-full.is-lt .rfx-n { color: #132009; text-shadow: none; } .rfx-cell.is-full.is-lt .rfx-dl { color: #2e3e24; text-shadow: none; }
    .rfx-cell.is-half .rfx-dl { color: #fff; text-shadow: 0 1px 2px rgb(0 0 0 / .35); }
    .rfx-cell.is-half.is-lt .rfx-dl { color: #2e3e24; text-shadow: none; }
    .rfx-cell.is-full .rfx-n, .rfx-cell.is-full .rfx-dl, .rfx-cell.is-half .rfx-dl { transition: color .4s ease .5s; }
    .rfx-crit { position: absolute; z-index: 1; top: .34rem; right: .3rem; display: flex; gap: 2px; }
    .rfx-crit i { width: .34rem; height: .34rem; border-radius: 999px; background: var(--rx-dot); }
    .rfx-cell.is-full .rfx-crit i { box-shadow: 0 0 0 1px rgb(255 255 255 / .55); }
    .rfx-crit.c1 i:nth-child(-n+1), .rfx-crit.c2 i:nth-child(-n+2), .rfx-crit.c3 i:nth-child(-n+3) { background: #dc2626; }
    .rfx-crit.c1 i:nth-child(-n+1) { background: #f59e0b; } .rfx-crit.c2 i:nth-child(-n+2) { background: #f97316; }
    html.dark .rfx-crit.c1 i:nth-child(-n+1), html.dark .rfx-crit.c2 i:nth-child(-n+2), html.dark .rfx-crit.c3 i:nth-child(-n+3) { background: #f87171; }
    html.dark .rfx-crit.c1 i:nth-child(-n+1) { background: #fbbf24; } html.dark .rfx-crit.c2 i:nth-child(-n+2) { background: #fb923c; }
    /* What went on that day: a small badge per product, its mark saying how (granules, a spray, organic), filled when
       done, outlined while planned, gold for a try. */
    .rfx-ap { position: absolute; z-index: 2; right: .25rem; bottom: .25rem; display: flex; align-items: center; }
    .rfx-ap i { display: grid; place-items: center; width: 1.2rem; height: 1.2rem; border-radius: 999px; color: var(--rx-badge-ink); background: var(--rx-badge); box-shadow: 0 0 0 1.5px var(--rx-card), 0 3px 6px -2px rgb(0 0 0 / .45);
        animation: rfxPop .4s cubic-bezier(.22,1,.36,1) both; animation-delay: calc(.4s + var(--k, 0) * 6ms); }
    .rfx-ap i + i { margin-left: -.35rem; }
    .rfx-ap i svg { width: .74rem; height: .74rem; fill: currentColor; }
    .rfx-ap i.is-plan { color: var(--rx-ink); background: var(--rx-card); box-shadow: inset 0 0 0 1.5px var(--rx-ink), 0 0 0 1.5px var(--rx-card), 0 3px 6px -2px rgb(0 0 0 / .3); }
    .rfx-ap i.is-try { color: #5c4500; background: #f5c518; }
    .rfx-ap b { margin-left: .1rem; padding: 0 .25rem; border-radius: 999px; font-size: .58rem; font-weight: 900; line-height: 1.05rem; color: var(--rx-ink); background: var(--rx-card); box-shadow: 0 0 0 1px var(--rx-line-2); }
    @media (max-width: 639.98px) { .rfx-ap { right: .12rem; bottom: .12rem; } .rfx-ap i { width: 1rem; height: 1rem; } .rfx-ap i svg { width: .62rem; height: .62rem; } }
    /* Side by side. */
    .rfx-minis { display: grid; grid-template-columns: repeat(auto-fill, minmax(min(100%, 9.4rem), 1fr)); gap: .5rem; padding: .7rem 1rem .3rem; }
    .rfx-mini { display: flex; align-items: center; gap: .6rem; min-width: 0; padding: .55rem .65rem; border-radius: .95rem; text-align: left; background: var(--rx-soft); box-shadow: inset 0 0 0 1px var(--rx-line);
        transition: box-shadow .28s cubic-bezier(.22,1,.36,1), transform .28s cubic-bezier(.22,1,.36,1), background-color .28s; animation: rfxPop .35s cubic-bezier(.22,1,.36,1) both; animation-delay: calc(var(--k, 0) * 50ms); }
    .rfx-mini:hover { transform: translateY(-1px); }
    .rfx-mini.is-on { background: var(--rx-card); box-shadow: inset 0 0 0 2px var(--sc), 0 12px 24px -18px var(--sc); }
    .rfx-mini svg { flex: none; width: 2.8rem; height: 2.8rem; transform: rotate(-90deg); }
    .rfx-mini circle { fill: none; stroke-width: 5; }
    .rfx-mini .v { stroke-linecap: round; stroke-dasharray: 0 200; transition: stroke-dasharray 1s cubic-bezier(.22,1,.36,1); }
    .rfx-mini > span { min-width: 0; display: grid; }
    .rfx-mini b { font-family: var(--font-heading); font-size: 1.1rem; font-weight: 900; line-height: 1.1; color: var(--rx-ink); }
    .rfx-mini span span { font-size: .8rem; font-weight: 800; color: var(--rx-text); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .rfx-mini small { font-size: .72rem; color: var(--rx-muted); }
    .rfx-lanes { display: grid; gap: .4rem; padding: .7rem 1rem .5rem; }
    .rfx-lane { display: grid; grid-template-columns: 5.6rem minmax(0, 1fr); gap: .55rem; align-items: center; animation: rfxIn .4s cubic-bezier(.22,1,.36,1) both; animation-delay: calc(.08s + var(--k, 0) * 60ms); }
    .rfx-lane-n { display: flex; align-items: center; gap: .35rem; min-width: 0; font-size: .76rem; font-weight: 800; line-height: 1.15; color: var(--rx-muted); text-align: left; overflow: hidden; }
    .rfx-lane-n i { flex: none; width: .6rem; height: .6rem; border-radius: 999px; background: var(--sc); }
    .rfx-lane.is-on .rfx-lane-n { color: var(--rx-ink); }
    .rfx-lane-s { display: block; width: 100%; height: 2rem; border-radius: .45rem; background: var(--rx-soft); box-shadow: inset 0 0 0 1px var(--rx-line); cursor: pointer; clip-path: inset(0 100% 0 0 round .45rem);
        animation: rfxLane 1.1s cubic-bezier(.45,0,.25,1) forwards; animation-delay: calc(.15s + var(--k, 0) * 90ms); }
    @keyframes rfxLane { to { clip-path: inset(0 0 0 0 round .45rem); } }
    .rfx-lane.is-on .rfx-lane-s { box-shadow: 0 0 0 2px var(--sc); }
    .rfx-lane.is-head { animation: none; }
    .rfx-stg { display: flex; overflow: hidden; }
    .rfx-stg .rfx-sb { position: relative; flex: none; height: 1.5rem; border-left: 1px solid var(--rx-line-2); }
    /* A stage's mark: a symbol and two letters in a pill over its band; a narrow band keeps the letters, a very narrow
       one the symbol alone (each mark is its own size container). */
    .rfx-chart-in { min-width: 46rem; }
    .rfx-sbar { position: relative; height: 1.9rem; }
    .rfx-sb { position: absolute; top: 0; bottom: 0; display: grid; place-items: center; padding: 0 1px; container-type: inline-size; cursor: pointer; }
    .rfx-sb > span { display: inline-flex; align-items: center; gap: .2rem; max-width: 100%; padding: .18rem .45rem; border-radius: 999px; font-size: .72rem; font-weight: 900; line-height: 1.1; white-space: nowrap; overflow: hidden;
        color: var(--rx-muted); background: var(--rx-soft); box-shadow: inset 0 0 0 1px var(--rx-line); transition: transform .28s cubic-bezier(.22,1,.36,1), box-shadow .28s, color .28s, background-color .28s; }
    .rfx-sb i { font-style: normal; font-size: .82rem; }
    .rfx-sb b { letter-spacing: .03em; color: inherit; }
    .rfx-sb.c3 > span { color: var(--rx-gap-ink); background: var(--rx-gap-bg); box-shadow: inset 0 0 0 1px var(--rx-gap-line); }
    .rfx-sb.c2 > span { color: var(--rx-thin-ink); background: var(--rx-thin-bg); box-shadow: inset 0 0 0 1px var(--rx-thin-line); }
    .rfx-sb.is-now > span { box-shadow: inset 0 0 0 2px var(--rx-today), 0 6px 14px -10px var(--rx-today); }
    .rfx-sb:hover > span { transform: translateY(-1px); box-shadow: inset 0 0 0 1.5px var(--rx-hover), 0 8px 16px -12px rgb(0 0 0 / .5); }
    .rfx-sb:focus-visible { outline: none; }
    .rfx-sb:focus-visible > span { outline: 2px solid var(--rx-accent); outline-offset: 1px; }
    .rfx-sb.is-sm > span { padding: .1rem .3rem; font-size: .64rem; }
    .rfx-sb.is-sm i { font-size: .7rem; }
    @container (max-width: 3.3rem) { .rfx-sb i { display: none; } .rfx-sb > span { padding: .15rem .3rem; } }
    @container (max-width: 1.7rem) { .rfx-sb i { display: inline; } .rfx-sb b { display: none; } .rfx-sb > span { padding: .15rem; } }
    /* A stage, opened. */
    .rfx-stgm .rfx-edit-c { gap: .7rem; width: min(100%, 32rem); }
    .rfx-stgm .rfx-edit-c.is-step { animation: rfxPop .3s cubic-bezier(.22,1,.36,1); }
    .rfx-stgm .rfx-day-h b { display: flex; flex-wrap: wrap; align-items: center; gap: .4rem; }
    .rfx-stg-ic { font-size: 1.35rem; line-height: 1; }
    .rfx-stg-ab { padding: .1rem .45rem; border-radius: 999px; font-style: normal; font-size: .72rem; font-weight: 900; letter-spacing: .05em; color: var(--rx-ink); background: var(--rx-soft); box-shadow: inset 0 0 0 1px var(--rx-line-2); }
    .rfx-stg-when { display: flex; flex-wrap: wrap; align-items: flex-end; justify-content: space-between; gap: .3rem .8rem; }
    .rfx-stg-when b { display: block; font-family: var(--font-heading); font-size: 1.2rem; color: var(--rx-ink); }
    .rfx-stg-when small { display: block; font-size: .8rem; font-weight: 700; color: var(--rx-muted); }
    .rfx-stg-when > span { font-size: .8rem; font-weight: 800; color: var(--rx-today); }
    .rfx-stgm .rfx-meter { margin-top: -.3rem; }
    .rfx-stg-ra { padding: .7rem .8rem; border-radius: .9rem; background: var(--rx-try-bg); box-shadow: inset 0 0 0 1.5px var(--rx-try-line); }
    .rfx-stg-ra.is-delay { background: var(--rx-soft); box-shadow: inset 0 0 0 1px var(--rx-line-2); }
    .rfx-stg-ra > b { display: block; font-size: .88rem; color: var(--rx-ink); }
    .rfx-stg-ra p { margin-top: .3rem; font-size: .84rem; line-height: 1.5; color: var(--rx-text); }
    .rfx-stg-ra p b { color: var(--rx-ink); }
    .rfx-stg-cmp { display: grid; grid-template-columns: 1fr 1fr; gap: .4rem; margin-top: .55rem; }
    .rfx-stg-cmp span { display: grid; gap: .1rem; padding: .45rem .6rem; border-radius: .7rem; background: var(--rx-card); box-shadow: inset 0 0 0 1px var(--rx-line); }
    .rfx-stg-cmp span.is-ra { box-shadow: inset 0 0 0 1.5px #f5c518; }
    .rfx-stg-cmp em { font-style: normal; font-size: .72rem; font-weight: 800; color: var(--rx-muted); }
    .rfx-stg-cmp b { font-size: .9rem; color: var(--rx-ink); }
    .rfx-stg-saw { margin-top: .45rem; }
    .rfx-stg-saw summary { cursor: pointer; font-size: .8rem; font-weight: 800; color: var(--rx-accent); }
    .rfx-stg-crit { padding: .65rem .8rem; border-radius: .9rem; background: var(--rx-soft); box-shadow: inset 0 0 0 1px var(--rx-line); }
    .rfx-stg-crit.c3 { background: var(--rx-gap-bg); box-shadow: inset 0 0 0 1px var(--rx-gap-line); }
    .rfx-stg-crit.c2 { background: var(--rx-thin-bg); box-shadow: inset 0 0 0 1px var(--rx-thin-line); }
    .rfx-stg-crit > b { display: block; font-size: .88rem; color: var(--rx-ink); }
    .rfx-stg-crit p { margin-top: .25rem; font-size: .82rem; line-height: 1.5; color: var(--rx-text); }
    .rfx-stg-cov { display: flex; flex-wrap: wrap; gap: .3rem; margin-top: .45rem; }
    .rfx-stg-cov span { padding: .15rem .55rem; border-radius: 999px; font-size: .74rem; font-weight: 800; color: var(--rx-text); background: var(--rx-card); box-shadow: inset 0 0 0 1px var(--rx-line); }
    .rfx-stg-cov .is-ok { color: var(--rx-ink); }
    .rfx-stg-cov .is-gap { color: var(--rx-gap-ink); }
    .rfx-stg-cov .is-thin { color: var(--rx-thin-ink); }
    .rfx-stg-do p { font-size: .84rem; line-height: 1.5; color: var(--rx-text); }
    .rfx-stg-do p + p { margin-top: .25rem; }
    .rfx-stg-do b, .rfx-stg-apps > b { color: var(--rx-ink); }
    .rfx-stg-apps > b { display: block; font-size: .82rem; }
    .rfx-stg-apps > p { margin-top: .2rem; font-size: .82rem; color: var(--rx-muted); }
    .rfx-stg-apps ul { margin: .35rem 0 0; padding: 0; list-style: none; display: grid; gap: .3rem; }
    .rfx-stg-apps li { display: grid; grid-template-columns: 4.6rem 1fr auto; align-items: center; gap: .5rem; padding: .4rem .55rem; border-radius: .7rem; font-size: .82rem; color: var(--rx-text); background: var(--rx-soft); }
    .rfx-stg-apps li.is-rel { box-shadow: inset 3px 0 0 var(--rx-accent); }
    .rfx-stg-apps li span { font-weight: 800; color: var(--rx-ink); }
    .rfx-stg-apps li small { display: block; font-size: .68rem; font-weight: 700; color: var(--rx-muted); }
    .rfx-stg-apps li em { font-style: normal; min-width: 0; }
    .rfx-stg-apps li i { font-style: normal; font-size: .72rem; font-weight: 800; color: var(--rx-muted); }
    .rfx-mo { position: relative; height: 1rem; font-size: .68rem; font-weight: 800; color: var(--rx-faint); }
    .rfx-mo span { position: absolute; top: 0; padding-left: .15rem; border-left: 1px solid var(--rx-line-2); }
    @media (max-width: 639.98px) {
        .rfx-mini { gap: .45rem; padding: .5rem; } .rfx-mini svg { width: 2.3rem; height: 2.3rem; }
        .rfx-lane { grid-template-columns: 4.8rem minmax(0, 1fr); gap: .4rem; }
    }
    .rfx-day-cmp { display: grid; gap: .45rem; margin-top: .7rem; padding-top: .6rem; border-top: 1px solid var(--rx-line); }
    .rfx-day-cmp > div { display: grid; grid-template-columns: auto minmax(0, 1fr) 4.5rem; align-items: center; gap: .1rem .45rem; font-size: .84rem; }
    .rfx-day-cmp > div > i { width: .6rem; height: .6rem; border-radius: 999px; background: var(--sc); }
    .rfx-day-cmp b { min-width: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; color: var(--rx-ink); }
    .rfx-day-cmp .rfx-meter { margin: 0; height: .45rem; }
    .rfx-day-cmp small { grid-column: 2 / -1; font-size: .76rem; color: var(--rx-muted); }
    @media (max-width: 639.98px) {
        .rfx-cal { padding: .2rem .4rem .8rem; }
        .rfx-wk { gap: .18rem; }
        .rfx-cell { min-height: 3.6rem; padding: .22rem .24rem; border-radius: .5rem; }
        .rfx-n { font-size: .76rem; }
        .rfx-dl { font-size: .54rem; letter-spacing: -.01em; }
        .rfx-crit i { width: .28rem; height: .28rem; }
    }
    .rfx-legend { display: flex; flex-wrap: wrap; align-items: center; gap: .45rem .85rem; padding: .3rem 1rem 1rem; font-size: .78rem; font-weight: 700; color: var(--rx-text); }
    .rfx-legend + .rfx-legend { margin-top: -.5rem; }
    .rfx-legend span { display: inline-flex; align-items: center; gap: .35rem; }
    .rfx-legend i { display: inline-block; width: .9rem; height: .9rem; border-radius: .25rem; }
    .rfx-leg-h { flex: 0 0 100%; font-size: .68rem; font-weight: 900; letter-spacing: .08em; text-transform: uppercase; color: var(--rx-faint); }
    .rfx-leg-dot { border-radius: 999px !important; }
    .rfx-leg-dot.is-done { background: var(--rx-ink); }
    .rfx-leg-dot.is-plan { background: var(--rx-card); box-shadow: inset 0 0 0 1.5px var(--rx-ink); }
    .rfx-leg-band.is-crit { background: rgb(220 38 38 / .28); } .rfx-leg-band.is-mid { background: rgb(249 115 22 / .26); }
    /* The life of one application, left to right. */
    .rfx-flow { gap: .35rem .3rem; }
    .rfx-flow > span:not(.rfx-leg-h) { padding: .28rem .6rem .28rem .32rem; border-radius: 999px; color: var(--rx-text); background: var(--rx-soft); box-shadow: inset 0 0 0 1px var(--rx-line); animation: rfxPop .32s cubic-bezier(.22,1,.36,1) both; animation-delay: calc(var(--k, 0) * 60ms); }
    .rfx-flow > em { font-style: normal; font-weight: 900; color: var(--rx-faint); }
    .rfx-leg-cell { width: 1.1rem !important; height: .9rem; border-radius: .25rem; background: var(--rx-card); }
    .rfx-legend .rfx-crit i { width: .4rem; height: .4rem; border-radius: 999px; }
    .rfx-leg-ap .rfx-ap { position: static; }
    .rfx-leg-ap .rfx-ap i { display: grid; width: 1.2rem; height: 1.2rem; border-radius: 999px; animation: none; }
    /* The products. */
    .rfx-apps { display: grid; gap: .55rem; padding: 0 1rem 1rem; }
    .rfx-app { display: grid; grid-template-columns: auto minmax(0, 1fr); gap: .75rem; padding: .8rem .85rem; border-radius: .95rem; background: var(--rx-soft); box-shadow: inset 0 0 0 1px var(--rx-line);
        animation: rfxIn .4s cubic-bezier(.22,1,.36,1) both; animation-delay: var(--d, 0s); }
    .rfx-app.is-try { background: var(--rx-gold-bg); box-shadow: inset 0 0 0 1.5px #f5c518; }
    .rfx-app-d { display: grid; place-items: center; align-self: start; width: 3.2rem; padding: .45rem 0; border-radius: .75rem; background: var(--rx-card); box-shadow: inset 0 0 0 1px var(--rx-line); text-align: center; }
    .rfx-app-d b { font-family: var(--font-heading); font-size: 1.15rem; font-weight: 900; line-height: 1; color: var(--rx-ink); }
    .rfx-app-d small { font-size: .64rem; font-weight: 900; text-transform: uppercase; color: var(--rx-muted); }
    .rfx-app-t b { font-size: .94rem; color: var(--rx-ink); }
    .rfx-app-t small { display: block; margin-top: .15rem; font-size: .8rem; color: var(--rx-muted); line-height: 1.45; }
    .rfx-app-t small b { font-size: inherit; color: var(--rx-ink); }
    .rfx-bar { position: relative; height: .62rem; margin-top: .55rem; border-radius: 999px; background: var(--rx-track); overflow: hidden; }
    .rfx-segs { position: absolute; inset: 0; clip-path: inset(0 100% 0 0); animation: rfxWipe 1s cubic-bezier(.45,0,.25,1) forwards; animation-delay: calc(var(--d, 0s) + .2s); }
    .rfx-segs i { position: absolute; top: 0; bottom: 0; background: var(--ph, var(--ph-strong)); }
    .rfx-segs i:first-child { border-radius: 999px 0 0 999px; } .rfx-segs i:last-child { border-radius: 0 999px 999px 0; } .rfx-segs i:only-child { border-radius: 999px; }
    .rfx-phs { display: flex; flex-wrap: wrap; gap: .3rem .75rem; margin-top: .5rem; font-size: .76rem; font-weight: 700; color: var(--rx-text); }
    .rfx-phs span { display: inline-flex; align-items: center; gap: .3rem; }
    .rfx-phs .rfx-sw { width: .72rem; height: .72rem; }
    .rfx-phs small { font-weight: 600; color: var(--rx-faint); }
    .rfx-trace { display: block; margin-top: .4rem; padding: .4rem .6rem; border-radius: .6rem; font-size: .78rem; line-height: 1.45; color: var(--rx-thin-ink); background: var(--rx-thin-bg); }
    .rfx-app-n { margin-top: .45rem; font-size: .82rem; line-height: 1.5; color: var(--rx-muted); }
    .rfx-tag { display: inline-block; margin-left: .3rem; padding: .08rem .5rem; border-radius: 999px; font-size: .68rem; font-weight: 900; vertical-align: middle; color: var(--rx-muted); background: var(--rx-tag-bg); }
    .rfx-tag.is-done { color: var(--rx-done-ink); background: var(--rx-done-bg); } .rfx-tag.is-try { color: var(--rx-gold-ink); background: var(--rx-gold-bg); box-shadow: inset 0 0 0 1px var(--rx-gold-line); }
    .rfx-other { padding: 0 1rem 1rem; }
    .rfx-other summary { cursor: pointer; font-size: .86rem; font-weight: 800; color: var(--rx-text); }
    .rfx-other ul { margin: .5rem 0 0; padding: 0; list-style: none; display: grid; gap: .3rem; }
    .rfx-other li { font-size: .84rem; color: var(--rx-muted); }
    .rfx-empty { padding: 2.5rem 1rem; text-align: center; }
    .rfx-empty img { width: 5rem; height: 5rem; margin: 0 auto; }
    .rfx-empty b { display: block; margin-top: .8rem; font-family: var(--font-heading); font-size: 1.1rem; color: var(--rx-ink); }
    .rfx-empty p { margin-top: .35rem; font-size: .9rem; line-height: 1.55; color: var(--rx-muted); }
    /* The foot. */
    .rfx-foot { display: flex; align-items: center; gap: .8rem; padding: .7rem 1rem calc(.7rem + env(safe-area-inset-bottom)); background: var(--rx-card); border-top: 1px solid var(--rx-line); }
    .rfx-foot-note { flex: 1; min-width: 0; font-size: .8rem; line-height: 1.4; color: var(--rx-muted); }
    @media (max-width: 559.98px) { .rfx-foot-note { display: none; } .rfx-foot .rfx-btn { flex: 1; } }
    /* Saved, and one day opened. */
    .rfx-drawer { position: absolute; z-index: 6; top: 0; right: 0; bottom: 0; width: min(100%, 24rem); display: flex; flex-direction: column; color: var(--rx-text); background: var(--rx-card); box-shadow: -20px 0 50px -20px rgb(0 0 0 / .5);
        transform: translateX(100%); transition: transform .32s cubic-bezier(.22,1,.36,1); }
    .rfx-drawer.is-on { transform: none; }
    .rfx-drawer-h { display: flex; align-items: center; justify-content: space-between; padding: .9rem 1rem; border-bottom: 1px solid var(--rx-line); color: var(--rx-ink); }
    .rfx-drawer-h b { font-family: var(--font-heading); font-size: 1.05rem; }
    .rfx-drawer-h .rfx-x, .rfx-day-h .rfx-x { background: var(--rx-soft); color: var(--rx-text); box-shadow: inset 0 0 0 1px var(--rx-line); }
    .rfx-drawer-b { flex: 1; overflow: auto; padding: .8rem; display: grid; align-content: start; gap: .5rem; }
    .rfx-sv:hover { box-shadow: inset 0 0 0 1.5px var(--rx-hover); }
    .rfx-sv b { display: block; font-size: .9rem; color: var(--rx-ink); }
    .rfx-sv small { display: block; font-size: .76rem; color: var(--rx-muted); }
    .rfx-sv-x { flex: none; display: grid; place-items: center; width: 1.9rem; height: 1.9rem; border-radius: 999px; color: var(--rx-faint); }
    .rfx-sv-x:hover { color: #dc2626; background: var(--rx-gap-bg); }
    .rfx-sv-x svg { width: .95rem; height: .95rem; }
    .rfx-day { position: absolute; z-index: 7; left: 50%; bottom: 1rem; width: min(calc(100% - 2rem), 26rem); max-height: calc(100% - 6rem); overflow: auto; padding: 1rem; border-radius: 1.2rem; color: var(--rx-text); background: var(--rx-card); transform: translateX(-50%);
        box-shadow: 0 0 0 1px var(--rx-line), 0 30px 60px -20px rgb(0 0 0 / .55); animation: rfxUp .3s cubic-bezier(.22,1,.36,1) both; }
    @keyframes rfxUp { from { opacity: 0; transform: translate(-50%, 16px); } }
    .rfx-day-h { display: flex; align-items: flex-start; gap: .6rem; }
    .rfx-day-h b { display: block; font-family: var(--font-heading); font-size: 1.08rem; color: var(--rx-ink); }
    .rfx-day-h small { display: block; font-size: .8rem; color: var(--rx-muted); }
    .rfx-day-h .rfx-x { margin-left: auto; width: 2rem; height: 2rem; }
    .rfx-day p { margin-top: .5rem; font-size: .88rem; line-height: 1.55; color: var(--rx-text); }
    .rfx-day p b { color: var(--rx-ink); }
    .rfx-day ul { margin: .5rem 0 0; padding: 0; list-style: none; display: grid; gap: .35rem; }
    .rfx-day li { font-size: .84rem; line-height: 1.45; color: var(--rx-text); }
    .rfx-day li b { color: var(--rx-ink); }
    .rfx-edit { position: absolute; inset: 0; z-index: 8; display: grid; place-items: center; padding: 1rem; }
    .rfx-edit[hidden] { display: none; }
    .rfx-edit-veil { position: absolute; inset: 0; background: rgb(0 0 0 / .45); opacity: 0; transition: opacity .25s cubic-bezier(.22,1,.36,1); }
    .rfx-edit-c { position: relative; display: grid; gap: .75rem; width: min(100%, 30rem); max-height: 100%; overflow: auto; padding: 1rem; border-radius: 1.2rem; color: var(--rx-text); background: var(--rx-card);
        box-shadow: 0 0 0 1px var(--rx-line), 0 30px 70px -20px rgb(0 0 0 / .6); opacity: 0; transform: translateY(14px) scale(.98); transition: opacity .28s cubic-bezier(.22,1,.36,1), transform .28s cubic-bezier(.22,1,.36,1); }
    .rfx-edit.is-on .rfx-edit-veil { opacity: 1; }
    .rfx-edit.is-on .rfx-edit-c { opacity: 1; transform: none; }
    .rfx-edit-b { display: flex; flex-wrap: wrap; justify-content: flex-end; gap: .5rem; }
    .rfx-edit-b .is-del { margin-right: auto; color: var(--rx-gap-ink); }
    @media (max-width: 559.98px) {
        .rfx-edit { place-items: end center; padding: 0; }
        .rfx-edit-c { width: 100%; border-radius: 1.2rem 1.2rem 0 0; padding-bottom: calc(1rem + env(safe-area-inset-bottom)); transform: translateY(40px); }
    }
    /* The logic switches. */
    .rfx-logic { display: grid; grid-template-columns: repeat(auto-fill, minmax(min(100%, 12.5rem), 1fr)); gap: .4rem; }
    .rfx-tog { display: flex; align-items: center; gap: .55rem; min-width: 0; padding: .5rem .6rem; border-radius: .9rem; text-align: left; color: var(--rx-muted); background: var(--rx-card);
        box-shadow: inset 0 0 0 1.5px var(--rx-line-2); transition: background-color .28s cubic-bezier(.22,1,.36,1), color .28s, box-shadow .28s, transform .28s cubic-bezier(.22,1,.36,1); }
    .rfx-tog:hover { transform: translateY(-1px); }
    .rfx-tog em { flex: none; font-style: normal; font-size: 1.1rem; filter: grayscale(1); opacity: .6; transition: filter .28s, opacity .28s; }
    .rfx-tog > span { flex: 1; min-width: 0; }
    .rfx-tog b { display: block; font-size: .86rem; color: inherit; }
    .rfx-tog small { display: block; font-size: .72rem; line-height: 1.3; opacity: .85; }
    .rfx-tog-s { position: relative; flex: none; width: 2.1rem; height: 1.2rem; border-radius: 999px; background: var(--rx-track); box-shadow: inset 0 0 0 1px var(--rx-line-2); transition: background-color .28s; }
    .rfx-tog-s::after { content: ''; position: absolute; top: .15rem; left: .15rem; width: .9rem; height: .9rem; border-radius: 999px; background: #fff; box-shadow: 0 1px 3px rgb(0 0 0 / .35); transition: transform .28s cubic-bezier(.22,1,.36,1); }
    .rfx-tog.is-on { color: var(--rx-ink); box-shadow: inset 0 0 0 1.5px var(--rx-hover); background: var(--rx-soft); }
    .rfx-tog.is-on em { filter: none; opacity: 1; }
    .rfx-tog.is-on .rfx-tog-s { background: #3f7a22; box-shadow: none; }
    .rfx-tog.is-on .rfx-tog-s::after { transform: translateX(.9rem); }
    @media (max-width: 559.98px) { .rfx-logic { grid-template-columns: repeat(2, minmax(0, 1fr)); } .rfx-tog small { display: none; } .rfx-tog { padding: .5rem; gap: .4rem; } }
    /* Apply on: a tag that opens the date picker. */
    .rfx-when { position: relative; display: grid; gap: .25rem; align-content: start; font-size: .78rem; font-weight: 800; color: var(--rx-muted); }
    .rfx-datetag { justify-self: start; display: inline-flex; align-items: center; gap: .45rem; padding: .55rem .8rem; border-radius: 999px; font-size: .92rem; font-weight: 800; color: var(--rx-ink);
        background: var(--rx-field); box-shadow: inset 0 0 0 1.5px #f5c518, 0 6px 14px -10px rgb(184 138 0 / .8); transition: transform .28s cubic-bezier(.22,1,.36,1), box-shadow .28s, background-color .28s; }
    .rfx-datetag:hover { transform: translateY(-1px); box-shadow: inset 0 0 0 2px #f5c518, 0 10px 18px -10px rgb(184 138 0 / .9); }
    .rfx-datetag svg { width: 1.05rem; height: 1.05rem; color: #b8941c; }
    .rfx-datetag .rfx-dt-c { width: .9rem; height: .9rem; opacity: .7; transition: transform .28s cubic-bezier(.22,1,.36,1); }
    .rfx-datetag.is-pop .rfx-dt-c { transform: rotate(180deg); }
    .rfx-datetag.is-flash { animation: rfxFlash .6s cubic-bezier(.22,1,.36,1); }
    @keyframes rfxFlash { 40% { transform: scale(1.06); } }
    .rfx-date-in { position: absolute; left: 0; bottom: 0; width: 1px; height: 1px; padding: 0; border: 0; opacity: 0; pointer-events: none; }
    /* Editing a saved reading, in the foot. */
    .rfx-editing { display: flex; align-items: center; gap: .4rem; min-width: 0; flex: 1; padding: .3rem .4rem .3rem .7rem; border-radius: 999px; background: var(--rx-saved-bg); box-shadow: inset 0 0 0 1px var(--rx-saved-line); color: var(--rx-saved-ink); animation: rfxPop .3s cubic-bezier(.22,1,.36,1) both; }
    .rfx-editing[hidden] { display: none; }
    .rfx-editing-l { flex: none; font-size: .68rem; font-weight: 900; letter-spacing: .06em; text-transform: uppercase; opacity: .8; }
    .rfx-editing b { min-width: 0; flex: 1; font-size: .86rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .rfx-editing input, .rfx-sv-in { flex: 1; min-width: 0; padding: .35rem .6rem; border-radius: .6rem; border: 1.5px solid var(--rx-saved-line); font-size: .88rem; color: var(--rx-ink); background: var(--rx-field); }
    .rfx-ico { display: grid; place-items: center; flex: none; width: 1.9rem; height: 1.9rem; border-radius: 999px; color: inherit; transition: background-color .2s; }
    .rfx-ico:hover { background: rgb(128 128 128 / .18); }
    .rfx-ico svg { width: .95rem; height: .95rem; }
    @media (max-width: 559.98px) { .rfx-foot { flex-wrap: wrap; } .rfx-editing { flex: 1 1 100%; } .rfx-foot .rfx-btn.is-ghost { flex: 1; } }
    /* The guide notice. */
    .rfx-banner.is-guide { align-items: flex-start; font-size: .82rem; font-weight: 600; color: var(--rx-muted); background: var(--rx-soft); box-shadow: inset 0 0 0 1px var(--rx-line); }
    .rfx-banner.is-guide b { color: var(--rx-ink); }
    /* How much: the season line in the summary, and the card of the days. */
    .rfx-amtline { margin-top: .8rem; display: grid; gap: .35rem; font-size: .86rem; line-height: 1.5; color: var(--rx-text); }
    .rfx-amtline b { color: var(--rx-ink); }
    .rfx-amtbar { position: relative; height: .7rem; border-radius: 999px; background: var(--rx-track); overflow: hidden; }
    .rfx-amtbar i { position: absolute; left: 0; top: 0; bottom: 0; width: 0; border-radius: 999px; transition: width 1.1s cubic-bezier(.22,1,.36,1) .2s; }
    .rfx-amtbar em { position: absolute; top: -2px; bottom: -2px; width: 2px; background: var(--rx-ink); opacity: .5; }
    .rfx-amt-g { display: grid; gap: 1rem; padding: .5rem 1rem 1rem; }
    @media (min-width: 900px) { .rfx-amt-g { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
    .rfx-amt-g h4 { font-size: .8rem; font-weight: 800; color: var(--rx-text); margin-bottom: .35rem; }
    .rfx-amt-g h4 small { font-weight: 600; color: var(--rx-faint); }
    .rfx-amt-g svg { display: block; width: 100%; height: auto; overflow: visible; }
    .rfx-amt-g .ax { font-size: 13px; font-weight: 800; fill: var(--rx-faint); }
    .rfx-amt-g .bar { transform-box: fill-box; transform-origin: bottom; animation: rfxBar .7s cubic-bezier(.22,1,.36,1) both; animation-delay: calc(var(--k, 0) * 4ms); }
    @keyframes rfxBar { from { transform: scaleY(0); } }
    .rfx-amt-g .needl { fill: none; stroke: var(--rx-ink); stroke-width: 2.4; stroke-dasharray: 6 5; }
    .rfx-amt-g .givel { fill: none; stroke: #3f9a26; stroke-width: 2.8; stroke-linejoin: round; }
    html.dark .rfx-amt-g .givel { stroke: #8fd163; }
    .rfx-amt-g .givea { fill: rgb(63 154 38 / .14); }
    /* How it connects. */
    .rfx-notes { display: grid; gap: .9rem; padding: .5rem 1rem 1rem; }
    .rfx-notes h4 { display: flex; align-items: center; gap: .4rem; font-size: .74rem; font-weight: 900; letter-spacing: .06em; text-transform: uppercase; color: var(--rx-faint); margin-bottom: .45rem; }
    .rfx-notes h4 i { font-style: normal; font-size: .62rem; padding: .05rem .4rem; border-radius: 999px; letter-spacing: 0; text-transform: none; color: var(--rx-muted); background: var(--rx-soft); }
    .rfx-note { display: grid; grid-template-columns: auto minmax(0, 1fr); gap: .65rem; padding: .7rem .8rem; border-radius: .9rem; background: var(--rx-soft); box-shadow: inset 0 0 0 1px var(--rx-line);
        animation: rfxIn .4s cubic-bezier(.22,1,.36,1) both; animation-delay: calc(var(--k, 0) * 50ms); }
    .rfx-note + .rfx-note { margin-top: .45rem; }
    .rfx-note > em { font-style: normal; font-size: 1.15rem; line-height: 1.3; }
    .rfx-note b { display: block; font-size: .9rem; line-height: 1.4; color: var(--rx-ink); }
    .rfx-note p { margin-top: .2rem; font-size: .84rem; line-height: 1.55; color: var(--rx-text); }
    .rfx-note.is-warn { background: var(--rx-thin-bg); box-shadow: inset 0 0 0 1px var(--rx-thin-line); }
    .rfx-note.is-good { box-shadow: inset 3px 0 0 #4f9030, inset 0 0 0 1px var(--rx-line); }
    .rfx-note[data-jump] { cursor: pointer; transition: transform .2s cubic-bezier(.22,1,.36,1); }
    .rfx-note[data-jump]:hover { transform: translateX(3px); }
    .rfx-note small { display: inline-block; margin-top: .35rem; font-size: .74rem; font-weight: 800; color: var(--rx-accent); }
    .rfx-others { display: grid; gap: .35rem; }
    .rfx-others li { display: flex; flex-wrap: wrap; align-items: center; gap: .3rem .5rem; list-style: none; font-size: .84rem; line-height: 1.45; color: var(--rx-text); }
    .rfx-others li b { color: var(--rx-ink); }
    .rfx-others .rfx-chip { padding: .25rem .65rem; font-size: .76rem; }
    .rfx-way { padding: .05rem .45rem; border-radius: 999px; font-size: .7rem; font-weight: 900; }
    .rfx-way.is-less { color: var(--rx-thin-ink); background: var(--rx-thin-bg); } .rfx-way.is-more { color: var(--rx-done-ink); background: var(--rx-done-bg); }
    .rfx-soilline { display: flex; flex-wrap: wrap; align-items: center; gap: .4rem .6rem; }
    .rfx-soilline .rfx-chip { padding: .3rem .7rem; font-size: .78rem; text-decoration: none; }
    @media (max-width: 559.98px) { .rfx-soilline { justify-content: center; } }
    .rfx-note-acts { display: flex; flex-wrap: wrap; gap: .35rem; margin-top: .5rem; }
    .rfx-note-acts .rfx-chip { padding: .35rem .75rem; font-size: .8rem; text-decoration: none; }
    .rfx-notes-more { justify-self: start; font-size: .82rem; font-weight: 800; color: var(--rx-accent); }
    .rfx-day-amt { margin-top: .6rem; padding: .55rem .7rem; border-radius: .8rem; font-size: .84rem; line-height: 1.5; color: var(--rx-text); background: var(--rx-soft); box-shadow: inset 0 0 0 1px var(--rx-line); }
    .rfx-day-amt b { color: var(--rx-ink); }
    /* A day's health mark: nitrogen lush (risk), or potassium and silicon helping. */
    .rfx-hm { position: absolute; z-index: 2; left: .25rem; bottom: .25rem; display: grid; place-items: center; min-width: 1rem; height: 1rem; padding: 0 .2rem; border-radius: .35rem; font-size: .58rem; font-weight: 900; color: #fff; box-shadow: 0 0 0 1.5px var(--rx-card); }
    .rfx-hm.is-n { background: #ea580c; } .rfx-hm.is-k { background: #2563eb; }
    @media (max-width: 639.98px) { .rfx-hm { left: .12rem; bottom: .12rem; min-width: .85rem; height: .85rem; font-size: .5rem; } }
    .rfx-cell[data-p=soil] .rfx-fill { background: var(--ph-soil); }
    .rfx-sv { display: flex; align-items: center; gap: .3rem; padding: .35rem .4rem .35rem .3rem; border-radius: .9rem; background: var(--rx-soft); box-shadow: inset 0 0 0 1px var(--rx-line); transition: box-shadow .2s, transform .2s cubic-bezier(.22,1,.36,1); }
    .rfx-sv.is-cur { box-shadow: inset 0 0 0 2px var(--rx-saved-line); background: var(--rx-saved-bg); }
    .rfx-sv-open { flex: 1; min-width: 0; padding: .35rem .45rem; border-radius: .7rem; text-align: left; }
    .rfx-sv-open:hover { background: rgb(128 128 128 / .08); }
    /* Folding sections. */
    .rfx-fold-h { cursor: pointer; user-select: none; padding-bottom: .95rem; border-radius: 1.2rem; transition: padding .3s cubic-bezier(.22,1,.36,1), background-color .2s; }
    .rfx-fold-h:hover { background: rgb(128 128 128 / .06); }
    .rfx-fold-h:focus-visible { outline: 2px solid var(--rx-accent); outline-offset: -2px; }
    .rfx-fold.is-open > .rfx-fold-h { padding-bottom: .25rem; }
    .rfx-fold-h { white-space: nowrap; }
    .rfx-fold-h small { flex: 1 1 auto; min-width: 0; text-align: right; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .rfx-chev { flex: none; width: 1.1rem; height: 1.1rem; color: var(--rx-muted); transition: transform .3s cubic-bezier(.22,1,.36,1); }
    .rfx-fold.is-open .rfx-chev { transform: rotate(180deg); }
    .rfx-fold-b { display: grid; grid-template-rows: 0fr; transition: grid-template-rows .34s cubic-bezier(.22,1,.36,1); }
    .rfx-fold.is-open > .rfx-fold-b { grid-template-rows: 1fr; }
    .rfx-fold-in { min-height: 0; overflow: hidden; opacity: 0; transition: opacity .25s ease; }
    .rfx-fold.is-open > .rfx-fold-b > .rfx-fold-in { opacity: 1; transition: opacity .3s ease .08s; }
    .rfx-foldbar { display: flex; justify-content: flex-end; gap: .4rem; margin: -.4rem 0 -.5rem; }
    .rfx-foldbar button { padding: .3rem .7rem; border-radius: 999px; font-size: .78rem; font-weight: 800; color: var(--rx-muted); background: var(--rx-card); box-shadow: inset 0 0 0 1px var(--rx-line-2); transition: color .2s, box-shadow .2s; }
    .rfx-foldbar button:hover { color: var(--rx-ink); box-shadow: inset 0 0 0 1.5px var(--rx-hover); }
    .rfx-trows { display: grid; gap: .45rem; padding: .3rem 1rem 1rem; }
    .rfx-trow { display: flex; flex-wrap: wrap; align-items: center; gap: .4rem .6rem; padding: .6rem .75rem; border-radius: .85rem; font-size: .86rem; line-height: 1.5; color: var(--rx-gold-ink); background: var(--rx-gold-bg); box-shadow: inset 0 0 0 1px var(--rx-gold-line); }
    .rfx-trow > span:first-child { flex: 1 1 16rem; }
    .rfx-trow b { color: inherit; }
    .rfx-meter { position: relative; height: .6rem; margin-top: .6rem; border-radius: 999px; background: var(--rx-track); overflow: hidden; }
    .rfx-meter i { position: absolute; left: 0; top: 0; bottom: 0; border-radius: 999px; transition: width .5s cubic-bezier(.22,1,.36,1); }
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
    // What an application is doing, in its order, and each phase's colour where CSS cannot reach (the chart's SVG).
    const PH_ORDER = ['soil', 'absorb', 'strong', 'easing', 'fading'];
    const PHC = { soil: '#a8723f', absorb: '#22b09a', strong: '#3b7a1f', easing: '#86c25a', fading: '#cfe8b8' };
    const EL = { P2O5: 'P₂O₅', K2O: 'K₂O' };
    const tabOf = (key) => key.startsWith('t:') ? 'prot' : key.startsWith('p:') ? 'pest' : ((S.opts?.nutrients || []).find((n) => n.key === key) || {}).micro ? 'micro' : 'nut';
    // What the reading counts besides coverage (RangeEffectLogic): every part on until the farmer switches it off.
    const LOGIC_DEF = { amt: true, soil: true, links: true, health: true };
    const S = { opts: null, lot: null, check: 'n:N', checks: ['n:N'], tab: 'nut', tries: [], res: null, saved: null, picked: null, q: '', busy: false,
        logic: { ...LOGIC_DEF }, savedId: null, savedTitle: '', recounted: null, hide: [] };
    const PEN = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4 20h4l10.5-10.5a2.8 2.8 0 00-4-4L4 16v4z"/></svg>';
    const TRASH = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 7h12M9 7V5h6v2M8 7l1 12h6l1-12"/></svg>';
    const kgSay = (v) => { v = Number(v) || 0; return v >= 10 ? String(Math.round(v)) : v >= 1 ? String(Math.round(v * 10) / 10) : String(Math.round(v * 100) / 100); };

    // The app's theme flipped while the sheet is open: the drawings carry colours, so paint them again.
    let wasDark = document.documentElement.classList.contains('dark');
    new MutationObserver(() => {
        const d = document.documentElement.classList.contains('dark');
        if (d === wasDark) return;
        wasDark = d;
        if (!M.hidden && S.res) { paint(); paintSel(); }
    }).observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });

    /* ---------------- open and close ---------------- */
    /* Where the farmer left off (the owner, 2026-10-30: "if i click the close, and click open again ... i'll just
       continue where i left off"): the lot, the checks and their tab, the tries, the logic switches, the saved reading
       being edited and how far down the sheet was, kept per season on this device; closed and opened again on the same
       page, nothing is even counted again. */
    const KEEP = 'rfx-left-' + SID;
    const scroller = () => $('[data-rfx-scroll]');
    const keep = () => {
        if (!S.opts) return;
        try {
            localStorage.setItem(KEEP, JSON.stringify({ lot: S.lot, check: S.check, checks: S.checks, tab: S.tab, tries: S.tries, logic: S.logic, hide: S.hide,
                // A hidden sheet reads as scrolled to the top: keep where it was when it closed.
                savedId: S.savedId, savedTitle: S.savedTitle, top: Math.round(M.hidden || !M.classList.contains('is-on') ? (S.lastTop || 0) : scroller().scrollTop), at: Date.now() }));
        } catch (_) {}
    };
    const leftOff = () => {
        try {
            const st = JSON.parse(localStorage.getItem(KEEP) || 'null');
            // A month later the season has moved on: start fresh.
            return st && Date.now() - (st.at || 0) < 30 * 864e5 ? st : null;
        } catch (_) { return null; }
    };
    let keepTimer = 0;
    scroller().addEventListener('scroll', () => { clearTimeout(keepTimer); keepTimer = setTimeout(keep, 400); }, { passive: true });
    window.addEventListener('pagehide', keep);
    const open = async () => {
        M.hidden = false;
        document.documentElement.classList.add('rfx-lock');
        requestAnimationFrame(() => requestAnimationFrame(() => M.classList.add('is-on')));
        if (!S.opts) await boot();
        else if (!S.res) run();
        else if (S.lastTop) requestAnimationFrame(() => { scroller().scrollTop = S.lastTop; });
    };
    const close = () => {
        S.lastTop = scroller().scrollTop;
        keep();
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
        if (!$('[data-rfx-stg]').hidden) closeStage();
        else if (!$('[data-rfx-edit]').hidden) closeEdit();
        else if (!$('[data-rfx-day]').hidden) hideDay();
        else if ($('[data-rfx-drawer]').classList.contains('is-on')) $('[data-rfx-drawer]').classList.remove('is-on');
        else close();
    });

    /* ---------------- the controls ---------------- */
    const boot = async () => {
        try {
            const r = await window.api(U.options);
            S.opts = r.data;
            S.lot = (S.opts.lots[0] || {}).id || null;
            // Pick up where the farmer left off, as far as it still fits this season.
            const st = leftOff();
            const lot = st ? (S.opts.lots || []).find((l) => l.id === st.lot) : null;
            if (lot) {
                const fits = (k) => typeof k === 'string' && k !== '' && (!k.startsWith('p:') || (lot.threats || []).some((t) => 'p:' + t.slug === k));
                S.lot = lot.id;
                S.checks = (Array.isArray(st.checks) ? st.checks : []).filter(fits).slice(0, SERIES.length);
                if (!S.checks.length) S.checks = [fits(st.check) ? st.check : 'n:N'];
                S.check = S.checks.includes(st.check) ? st.check : S.checks[0];
                S.tab = ['nut', 'micro', 'prot', 'pest'].includes(st.tab) ? st.tab : tabOf(S.check);
                S.tries = (Array.isArray(st.tries) ? st.tries : []).filter((t) => t && t.date && (t.productId || t.ai));
                S.logic = { ...LOGIC_DEF, ...(st.logic || {}) };
                S.hide = (Array.isArray(st.hide) ? st.hide : []).filter((k) => S.checks.includes(k));
                if (st.savedId && (S.opts.saved || []).some((x) => x.id === st.savedId)) { S.savedId = st.savedId; S.savedTitle = st.savedTitle || ''; }
                S.restoreTop = Number(st.top) || 0;
            }
            paintSavedCount();
            paintLots();
            paintPicks();
            paintTries();
            paintLogic();
            paintFoot();
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
                : '<small class="rfx-muted">This lot\'s crop has no pests or diseases in our book yet. Use Protection instead.</small>';
        }
        if (S.checks.length === 1) html += '<small class="rfx-hint">Tap more to compare them</small>';
        $('[data-rfx-picks]').innerHTML = html;
        paintSel();
    };
    // Picked checks: each keeps the colour of its place in the pick, the one the calendar shows is filled.
    // Lighter on the dark sheet so each line and ring stays clear of the background.
    const SERIES = ['#3b7a1f', '#2563eb', '#c2650a', '#9333ea', '#db2777', '#0e7490'];
    const SERIES_DARK = ['#8fd163', '#60a5fa', '#fbbf24', '#c084fc', '#f472b6', '#22d3ee'];
    const isDark = () => document.documentElement.classList.contains('dark');
    const colorOf = (key) => (isDark() ? SERIES_DARK : SERIES)[Math.max(0, S.checks.indexOf(key))] || '#6b7280';
    // A colour token of the sheet, for the SVG drawings CSS cannot reach.
    const tok = (name) => getComputedStyle(M).getPropertyValue(name).trim();
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
        $('[data-rfx-sel]').innerHTML = S.checks.map((k, i) => `<span class="rfx-chip ${k === S.check ? 'is-on' : ''}" style="--sc:${colorOf(k)};--k:${i}" role="button" tabindex="0" data-focus="${esc(k)}" title="Show it in the calendar"><i></i>${esc(labelOf(k))}<button type="button" data-unpick="${esc(k)}" aria-label="Remove from the comparison"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" d="M6 6l12 12M18 6L6 18"/></svg></button></span>`).join('');
    };
    const focusCheck = (key) => {
        if (key === S.check || !S.checks.includes(key)) return;
        S.check = key;
        paintSel();
        run();
    };
    // A tap adds a check to the comparison (and shows it), or takes it out; one always stays.
    // keepMain: added beside the one the calendar shows (the season chart's switches), not in its place.
    const togglePick = (key, keepMain = false) => {
        if (S.checks.includes(key)) {
            if (S.checks.length === 1) return;
            S.checks = S.checks.filter((k) => k !== key);
            S.hide = S.hide.filter((k) => k !== key);
            if (S.check === key) S.check = S.checks[S.checks.length - 1];
        } else {
            if (S.checks.length >= SERIES.length) { window.toast?.(`Up to ${SERIES.length} at a time. Take one out first.`, 'error'); return; }
            S.checks = [...S.checks, key];
            if (!keepMain) S.check = key;
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

    const paintLogic = () => $$('[data-logic]').forEach((b) => { const v = !!S.logic[b.dataset.logic]; b.classList.toggle('is-on', v); b.setAttribute('aria-pressed', String(v)); });
    $('[data-rfx-logic]').addEventListener('click', (e) => {
        const b = e.target.closest('[data-logic]');
        if (!b) return;
        S.logic[b.dataset.logic] = !S.logic[b.dataset.logic];
        paintLogic();
        run();
    });

    /* ---------------- Apply on: a tag that opens the date picker ---------------- */
    const paintDate = (input) => { const t = input.parentElement.querySelector('[data-date-say]'); if (t) t.textContent = input.value ? sayY(input.value) : 'Pick a day'; };
    const setDate = (input, v) => { input.value = v || ''; paintDate(input); };
    $$('[data-date-for]').forEach((btn) => {
        const input = btn.parentElement.querySelector('.rfx-date-in');
        btn.addEventListener('click', () => {
            btn.classList.add('is-pop');
            try { if (typeof input.showPicker === 'function') input.showPicker(); else { input.focus(); input.click(); } }
            catch (_) { input.focus(); input.click(); }
        });
        input.addEventListener('change', () => {
            paintDate(input);
            btn.classList.remove('is-pop');
            if (!calm) { btn.classList.remove('is-flash'); void btn.offsetWidth; btn.classList.add('is-flash'); }
        });
        input.addEventListener('blur', () => btn.classList.remove('is-pop'));
    });

    /* ---------------- tries: a product you plan to apply ---------------- */
    const tryBox = $('[data-rfx-try]');
    const paintTries = () => {
        $('[data-rfx-tries]').innerHTML = S.tries.map((t, i) => `<span class="rfx-chip is-try" data-try="${i}" role="button" tabindex="0" title="Tap to change it">${t.method === 'foliar' ? '💧' : '🟤'} ${esc(t.name)} <small>${esc(say(t.date))}${t.method ? ' · ' + t.method : ''}${t.qty ? ' · ' + t.qty + ' ' + esc(t.unit) : ''}</small><svg class="rfx-pen" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4 20h4l10.5-10.5a2.8 2.8 0 00-4-4L4 16v4z"/></svg><button type="button" data-untry="${i}" aria-label="Remove"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" d="M6 6l12 12M18 6L6 18"/></svg></button></span>`).join('');
    };
    $('[data-rfx-tries]').addEventListener('click', (e) => {
        const b = e.target.closest('[data-untry]');
        if (b) {
            S.tries.splice(Number(b.dataset.untry), 1);
            paintTries();
            run();
            return;
        }
        const c = e.target.closest('[data-try]');
        if (c) openEdit(Number(c.dataset.try));
    });
    $('[data-rfx-tries]').addEventListener('keydown', (e) => {
        const c = e.target.closest('[data-try]');
        if (c && (e.key === 'Enter' || e.key === ' ')) { e.preventDefault(); openEdit(Number(c.dataset.try)); }
    });
    /* A try, opened to change it. */
    const editBox = $('[data-rfx-edit]');
    const editHow = $('[data-rfx-edit-how]');
    let editing = -1;
    const openEdit = (i) => {
        const t = S.tries[i];
        if (!t) return;
        editing = i;
        $('[data-rfx-edit-p]').innerHTML = `<em>${t.method === 'foliar' ? '💧' : '🟤'}</em><span><b>${esc(t.name)}</b><small>${esc(t.line || (t.ai && !t.productId ? 'The active ingredient on its own' : 'Added by you'))}</small></span>`;
        setDate($('[data-rfx-edit-date]'), t.date);
        $('[data-rfx-edit-qty]').value = t.qty || '';
        $('[data-rfx-edit-unit]').value = t.unit || 'kg';
        const ha = lotOf()?.ha;
        $('[data-rfx-edit-ha]').textContent = ha ? `(${Math.round(ha * 100) / 100} ha, optional)` : '(optional)';
        setHow(t.method || 'granular', false, editHow, $('[data-rfx-edit-unit]'));
        editBox.hidden = false;
        requestAnimationFrame(() => requestAnimationFrame(() => editBox.classList.add('is-on')));
        setTimeout(() => $('[data-date-for="data-rfx-edit-date"]').focus({ preventScroll: true }), calm ? 0 : 200);
    };
    const closeEdit = () => {
        editing = -1;
        editBox.classList.remove('is-on');
        setTimeout(() => { if (editing < 0) editBox.hidden = true; }, calm ? 0 : 260);
    };
    editHow.addEventListener('click', (e) => { const b = e.target.closest('[data-how]'); if (b) setHow(b.dataset.how, true, editHow, $('[data-rfx-edit-unit]')); });
    editBox.addEventListener('click', (e) => {
        if (e.target.closest('[data-rfx-edit-x]')) { closeEdit(); return; }
        if (e.target.closest('[data-rfx-edit-del]') && editing >= 0) {
            S.tries.splice(editing, 1);
            closeEdit();
            paintTries();
            run();
            return;
        }
        if (e.target.closest('[data-rfx-edit-save]') && editing >= 0) {
            const date = $('[data-rfx-edit-date]').value;
            if (!date) { window.toast?.('Pick the day you would apply it.', 'error'); return; }
            S.tries[editing] = { ...S.tries[editing], date, qty: Number($('[data-rfx-edit-qty]').value) || 0, unit: $('[data-rfx-edit-unit]').value, method: editHow.dataset.on || 'granular' };
            closeEdit();
            paintTries();
            run();
        }
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
        setDate($('[data-rfx-date]'), iso(new Date()));
        $('[data-rfx-qty]').value = '';
        const ha = lotOf()?.ha;
        $('[data-rfx-ha]').textContent = ha ? `(${Math.round(ha * 100) / 100} ha, optional)` : '(optional)';
    };
    // Granular or foliar: the pill slides to the pick; a unit that cannot go with it changes too.
    const setHow = (m, fixUnit = true, box = $('[data-rfx-how]'), u = $('[data-rfx-unit]')) => {
        if (box.hasAttribute('data-rfx-how') && S.picked) S.picked.method = m;
        box.dataset.on = m;
        $$('[data-how]', box).forEach((b) => b.setAttribute('aria-checked', String(b.dataset.how === m)));
        if (!fixUnit) return;
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
                $('[data-rfx-results]').innerHTML = html || '<small class="rfx-muted" style="padding:.3rem">Nothing in our book by that name.</small>';
                S.found = { items, ing };
                const look = $('[data-rfx-look]');
                look.hidden = false;
                $('[data-rfx-look-name]').value = items.length ? '' : q;
                paintLookWhy();
            } catch (err) { $('[data-rfx-results]').innerHTML = `<small class="rfx-muted" style="color:var(--rx-gap-ink)">${esc(err.message)}</small>`; }
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
        $('[data-rfx-picked-p]').innerHTML = `<em>${typeIcon(p.t)}</em><span><b>${esc(p.name)}</b><small>${esc(p.line)}</small></span>`;
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
        S.tries.push({ productId: p.productId || null, ai: p.ai || null, name: p.name, line: p.line || '', date, qty: Number($('[data-rfx-qty]').value) || 0, unit: $('[data-rfx-unit]').value, method: p.method || 'granular' });
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
    const run = async (keepNote = false) => {
        if (!S.lot) return;
        const seq = ++runSeq;
        S.saved = null;
        if (!keepNote) S.recounted = null;
        $('[data-rfx-body]').style.opacity = S.res ? '.55' : '1';
        if (!S.res) body('<div class="rfx-loading"><span></span><span></span><span></span></div>');
        try {
            const r = await window.api(U.run, { method: 'POST', body: { scheduleId: SID, lotId: S.lot, check: S.check, also: S.checks.filter((k) => k !== S.check), tries: S.tries, logic: S.logic } });
            if (seq !== runSeq) return;
            S.res = r.data;
            paint();
            keep();
            // Back to where the sheet was scrolled, once, after the first reading is drawn.
            if (S.restoreTop) { const t = S.restoreTop; S.restoreTop = 0; requestAnimationFrame(() => requestAnimationFrame(() => { scroller().scrollTop = t; })); }
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
        // A saved reading shows its numbers as they were when it was saved (the owner, 2026-10-30: "i don't get the
        // idea of the read it with today's plan button"): the button counts it again with the tasks on the board now.
        if (S.recounted) parts.push(`<div class="rfx-banner is-saved"><span>📌 <b>${esc(S.recounted.title)}</b>, saved ${esc(S.recounted.at)}, was counted again just now: the way Range Effect counts has improved since, and your tasks may have changed. Your lot, checks and fertilizers are as you saved them. Tap <b>Update saved reading</b> to keep these numbers.</span></div>`);
        if (S.saved) parts.push(`<div class="rfx-banner is-saved"><span>📌 <b>${esc(S.saved.title)}</b>, saved ${esc(S.saved.at)}. These are its numbers from that day: tasks you added or changed since, and app updates, are not in them.</span><button type="button" class="rfx-btn is-ghost" data-rfx-rerun>Recount with my tasks now</button></div>`);
        if (!R.lot.hasDay) parts.push(`<div class="rfx-banner">📅 This lot has no planting day yet, so the stages and what matters when cannot be shown. Set its day zero in Lots, then open this again.</div>`);
        // A guide only (the owner, 2026-10-30): said every time, above the numbers.
        parts.push(`<div class="rfx-banner is-guide"><span>🧭 <b>A guide only.</b> It reads our books, your answers about this lot and your tasks. Your field and the weather can differ: check with a soil test, the label and your own eyes.</span></div>`);
        // Every section folds; these open or close them all.
        parts.push('<div class="rfx-foldbar"><button type="button" data-fold-all="1">Open all</button><button type="button" data-fold-all="0">Close all</button></div>');
        // The tries in one folding card: those that miss what is checked say what they act on, one tap away.
        parts.push(triesHtml(R, ck));
        // The summary.
        const pct = sm.pct;
        const ringCol = pct === null ? '#9ca3af' : pct >= 80 ? (isDark() ? '#7cc24f' : '#4a7c2a') : pct >= 50 ? '#f59e0b' : (isDark() ? '#f87171' : '#dc2626');
        const head = pct === null ? `Nothing in this season is critical for ${esc(ck.word)} yet.`
            : pct >= 80 ? `${esc(ck.short)} is well covered when it matters.`
            : pct >= 50 ? `${esc(ck.short)} is covered on most critical days, with gaps.`
            : (isNut ? `${esc(ck.short)} runs short when the crop needs it most.` : `The crop is unguarded against ${esc(ck.word)} on most critical days.`);
        const sub = isNut
            ? `${sm.apps} application${sm.apps === 1 ? '' : 's'} carry ${esc(ck.word)} on this lot${sm.tries ? `, ${sm.tries} of them added by you` : ''}. Each keeps feeding the crop for a while, then fades.`
            : `${sm.apps} application${sm.apps === 1 ? '' : 's'} act on ${esc(ck.word)}${sm.tries ? `, ${sm.tries} of them added by you` : ''}. Protection is only needed when the pest shows up: on a gap, scout first and spray only when you find it.`;
        parts.push(`<div class="rfx-card" data-fold="sum" style="--d:0s"><div class="rfx-card-h">At a glance <small>${pct === null ? 'nothing critical yet' : pct + '% of critical days covered'}${sm.gapDays ? ' · ' + sm.gapDays + ' gap day' + (sm.gapDays === 1 ? '' : 's') : ''}</small></div><div class="rfx-sum">
            <div class="rfx-ring"><svg viewBox="0 0 120 120"><circle class="t" cx="60" cy="60" r="52"/><circle class="v" cx="60" cy="60" r="52" stroke="${ringCol}" data-pct="${pct ?? 0}"/></svg>
              <div class="rfx-ring-n"><b data-count="${pct ?? 0}">${pct === null ? '–' : '0'}</b><small>${pct === null ? 'no critical days' : '% of critical days covered'}</small></div></div>
            <div class="rfx-say"><h3>${head}</h3><p>${sub}</p>
              <div class="rfx-counts">
                <span><i style="background:#4a7c2a"></i>${sm.coveredDays} covered</span>
                <span><i style="background:#f59e0b"></i>${sm.thinDays} thin</span>
                <span><i style="background:#dc2626"></i>${sm.gapDays} gap</span>
                ${isNut ? `<span><i style="background:#8b5cf6"></i>${(sm.late || []).reduce((a, r) => a + r.n, 0)} late</span>` : ''}
              </div>${amountLine(R)}${soilLine(R)}</div></div>
            ${runsHtml(sm, isNut)}</div>`);
        if ((R.series || []).length) parts.push(compareHtml(R));
        parts.push(logicHtml(R));
        parts.push(amountsHtml(R));
        parts.push(chartHtml(R));
        parts.push(calHtml(R));
        parts.push(appsHtml(R));
        body(parts.join(''));
        wrapFolds();
        // Bring it in: the ring, the count, the cells.
        requestAnimationFrame(() => {
            const v = $('.rfx-ring .v');
            if (v) v.style.strokeDasharray = `${(Number(v.dataset.pct) / 100) * 326.7} 400`;
            $$('.rfx-mini .v').forEach((m) => { m.style.strokeDasharray = `${(Number(m.dataset.pct) / 100) * 113.1} 200`; });
            $$('.rfx-amtbar i').forEach((m) => { m.style.width = m.dataset.w + '%'; });
            const n = $('[data-count]');
            if (n && pct !== null) countUp(n, pct);
            const cal = $('.rfx-cal');
            if (cal) requestAnimationFrame(() => cal.classList.add('is-in'));
        });
    };
    /* The tries, one card: what each misses or gives too little of. */
    const triesHtml = (R, ck) => {
        const tries = R.apps.filter((a) => a.try);
        const miss = tries.filter((a) => !a.relevant);
        const trace = tries.filter((a) => a.relevant && a.effect && a.effect.dose === 'trace');
        if (!miss.length && !trace.length) return '';
        const working = tries.length - miss.length - trace.length;
        const meta = [miss.length ? `${miss.length} ${miss.length === 1 ? 'does' : 'do'} not act on ${esc(ck.word)}` : '', trace.length ? `${trace.length} ${trace.length === 1 ? 'gives' : 'give'} very little` : '', working ? `${working} working` : ''].filter(Boolean).join(' · ');
        const rows = miss.map((a) => {
            const acts = a.acts || [];
            return `<div class="rfx-trow"><span>✨ <b>${esc(a.as)}</b> does not act on ${esc(ck.word)}.${acts.length ? ' It acts on:' : ' We could not read what it carries or guards against. Try its active ingredient, or let Anee look it up.'}</span>${acts.length ? `<span class="rfx-acts">${acts.map((x) => `<button type="button" class="rfx-chip" data-goto="${esc(x.key)}">${esc(x.label)}</button>`).join('')}</span>` : ''}</div>`;
        }).concat(trace.map((a) => `<div class="rfx-trow"><span>✨ <b>${esc(a.as)}</b>${a.amount ? ' at ' + esc(a.amount) : ''} gives very little ${esc(ck.word)}: about ${a.effect.kgHa} kg of ${EL[a.effect.el] || esc(a.effect.el)} a hectare, where one usual ${a.form === 'foliar' ? 'leaf spray gives' : 'dose on the soil gives'} about ${a.effect.ref}. Its days still show, faintly.</span></div>`));
        return `<div class="rfx-card" data-fold="tries" style="--d:0s"><div class="rfx-card-h">Your fertilizers <small>${meta}</small></div><div class="rfx-trows">${rows.join('')}</div></div>`;
    };
    /* Folding sections (the owner, 2026-10-30: "make that part expandable contractable including the other sections below
       it for easier reading and not info overload"): the answer and the calendar open, the rest a line each until opened.
       What a farmer opens or closes is remembered on this device. */
    const FOLD_DEF = { sum: true, tries: false, cmp: true, logic: false, amt: false, chart: false, cal: true, apps: false };
    let foldPref = {};
    try { foldPref = JSON.parse(localStorage.getItem('rfx-folds') || '{}') || {}; } catch (_) { foldPref = {}; }
    const setFold = (card, open, remember = true) => {
        if (!card) return;
        card.classList.toggle('is-open', open);
        const h = card.querySelector(':scope > .rfx-fold-h');
        if (h) h.setAttribute('aria-expanded', String(open));
        if (remember) {
            foldPref[card.dataset.fold] = open;
            try { localStorage.setItem('rfx-folds', JSON.stringify(foldPref)); } catch (_) {}
        }
    };
    const wrapFolds = () => {
        $$('[data-rfx-body] .rfx-card[data-fold]').forEach((card) => {
            const h = card.querySelector(':scope > .rfx-card-h');
            if (!h || card.querySelector(':scope > .rfx-fold-b')) return;
            const b = document.createElement('div');
            b.className = 'rfx-fold-b';
            const inner = document.createElement('div');
            inner.className = 'rfx-fold-in';
            while (h.nextSibling) inner.appendChild(h.nextSibling);
            b.appendChild(inner);
            card.appendChild(b);
            card.classList.add('rfx-fold');
            h.classList.add('rfx-fold-h');
            h.setAttribute('role', 'button');
            h.tabIndex = 0;
            h.insertAdjacentHTML('beforeend', '<svg class="rfx-chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/></svg>');
            const key = card.dataset.fold;
            setFold(card, key in foldPref ? !!foldPref[key] : !!FOLD_DEF[key], false);
        });
    };
    /* The soil the reading counts, in the summary, so a lot read by the area's typical soil says so up front. */
    const soilLine = (R) => {
        const L = R.logic;
        if (!L || !L.opts || !L.opts.soil || !L.soil) return '';
        const note = (L.notes || []).find((x) => x.k === 'soil' && (x.acts || []).length);
        const from = L.soil.from;
        const how = from === 'answers' || from === 'both' ? 'from your answers' : from === 'area' ? "the area's typical soil: this lot is not answered yet" : 'not known yet';
        const act = note && note.acts[0] && (from === 'area' || from === 'none') ? `<a class="rfx-chip" href="${esc(note.acts[0].href)}">${esc(note.acts[0].label)}</a>` : '';
        return `<div class="rfx-amtline rfx-soilline"><span>🪨 <b>Soil</b>: ${esc(L.soil.say)} (${esc(how)})</span>${act}</div>`;
    };
    /* How much over the season, in the summary: given against needed from the bag. */
    const amountLine = (R) => {
        const A = R.logic?.amt;
        if (!A) return '';
        if (!A.known) return '<div class="rfx-amtline"><span>⚖️ This crop has no numbers in NPK Plus yet, so the amounts cannot be weighed.</span></div>';
        if (A.soilGives) return `<div class="rfx-amtline"><span>⚖️ The soil likely gives all the ${esc(A.word)} this crop needs, so none has to come from the bag${A.applied ? `. The season puts on about ${kgSay(A.applied)} kg ${esc(A.say)} a hectare anyway` : ''}.</span></div>`;
        const pct = A.pct ?? 0;
        const col = pct >= 90 ? '#3f9a26' : pct >= 60 ? '#f59e0b' : '#dc2626';
        return `<div class="rfx-amtline"><span>⚖️ Over the season your products give about <b>${kgSay(A.given)} of the ${kgSay(A.need)} kg ${esc(A.say)}</b> a hectare this field needs from the bag (<b>${pct}%</b>).</span>
            <div class="rfx-amtbar" title="The line is the need"><i data-w="${Math.min(100, pct / 1.5).toFixed(1)}" style="background:${col}"></i><em style="left:${(100 / 1.5).toFixed(1)}%"></em></div></div>`;
    };
    /* How much, day by day: what each day gets against what it takes, and the two added up. */
    const amountsHtml = (R) => {
        const A = R.logic?.amt;
        if (!A || !A.known || !A.n || A.soilGives) return '';
        const days = R.days, n = days.length, W = 600, H = 170, top = 14, base = 140, bw = W / n;
        const maxD = Math.max(0.0001, ...A.g, ...A.n) * 1.12;
        const yD = (v) => base - v / maxD * (base - top);
        let bg = '', bars = '', k = 0;
        days.forEach((d, i) => {
            const x = i * bw;
            if (d.c >= 2 && (A.s[i] === 'short' || A.s[i] === 'low')) bg += `<rect x="${x.toFixed(1)}" y="${top}" width="${(bw + 0.4).toFixed(1)}" height="${base - top}" fill="${A.s[i] === 'short' ? 'rgb(220 38 38 / .13)' : 'rgb(245 158 11 / .12)'}"/>`;
            const g = A.g[i];
            if (g > 0.0005) bars += `<rect class="bar" style="--k:${k++}" x="${(x + bw * 0.1).toFixed(2)}" y="${yD(g).toFixed(1)}" width="${(bw * 0.8).toFixed(2)}" height="${(base - yD(g)).toFixed(1)}" fill="${PHC[d.p] || PHC.strong}"><title>${say(d.d)}: gives ${kgSay(g)} kg, the crop needs ${kgSay(A.n[i])}</title></rect>`;
        });
        const line = (arr, y) => arr.map((v, i) => (i ? 'L' : 'M') + ((i + 0.5) * bw).toFixed(1) + ' ' + y(v).toFixed(1)).join(' ');
        let mo = '';
        days.forEach((d, i) => { if (d.d.endsWith('-01')) mo += `<text class="ax" x="${(i * bw + 2).toFixed(1)}" y="${base + 18}">${MON[D(d.d).getMonth()]}</text><line x1="${(i * bw).toFixed(1)}" x2="${(i * bw).toFixed(1)}" y1="${base}" y2="${base + 5}" stroke="${tok('--rx-line-2')}"/>`; });
        const ti = days.findIndex((d) => d.d === R.today);
        const todayL = ti >= 0 ? `<line x1="${((ti + 0.5) * bw).toFixed(1)}" x2="${((ti + 0.5) * bw).toFixed(1)}" y1="${top}" y2="${base}" stroke="${tok('--rx-today')}" stroke-width="1.5" stroke-dasharray="3 3"/>` : '';
        // Added up over the crop's own days.
        const CG = [], CN = [];
        let cg = 0, cn = 0;
        days.forEach((d, i) => { if (!A.in || A.in[i]) { cg += A.g[i]; cn += A.n[i]; } CG.push(cg); CN.push(cn); });
        const maxC = Math.max(0.0001, cg, cn) * 1.1;
        const yC = (v) => base - v / maxC * (base - top);
        const area = `M${(0.5 * bw).toFixed(1)} ${base} ` + CG.map((v, i) => 'L' + ((i + 0.5) * bw).toFixed(1) + ' ' + yC(v).toFixed(1)).join(' ') + ` L${((n - 0.5) * bw).toFixed(1)} ${base} Z`;
        const nowSay = ti >= 0 ? `<text class="ax" x="${Math.min(W - 4, (ti + 0.5) * bw + 4).toFixed(1)}" y="${top + 12}" text-anchor="${ti > n * 0.7 ? 'end' : 'start'}" fill="${tok('--rx-today')}">By today ${kgSay(CG[ti])} of ${kgSay(CN[ti])}</text>` : '';
        const axis = `<line x1="0" x2="${W}" y1="${base}" y2="${base}" stroke="${tok('--rx-line-2')}"/>${mo}`;
        const runs = (A.short || []).map((r, i) => `<div class="rfx-run" data-jump="${r.from}" style="--d:${0.1 + i * 0.05}s"><em>⚖️</em><span><b>${say(r.from)}${r.to !== r.from ? ' to ' + say(r.to) : ''}</b>: the products give under half of what the crop takes these days.</span></div>`).join('')
            + (A.low || []).map((r, i) => `<div class="rfx-run is-thin" data-jump="${r.from}" style="--d:${0.25 + i * 0.05}s"><em>⚖️</em><span><b>${say(r.from)}${r.to !== r.from ? ' to ' + say(r.to) : ''}</b>: a little under what the crop takes.</span></div>`).join('');
        return `<div class="rfx-card" data-fold="amt" style="--d:.1s"><div class="rfx-card-h">How much, day by day <small>${A.pct !== null && A.pct !== undefined ? `${kgSay(A.given)} of ${kgSay(A.need)} kg ${esc(A.say)} a hectare (${A.pct}%)` : `kg of ${esc(A.say)} a hectare`}</small></div>
            <div class="rfx-amt-g">
              <div><h4>Each day <small>bars: what your products give · dashes: what the crop takes</small></h4><div class="rfx-chart-w"><svg viewBox="0 -4 ${W} ${H + 8}" style="min-width:28rem" aria-label="What each day gets against what it takes">${bg}${bars}<path class="needl" d="${line(A.n, yD)}"/>${todayL}${axis}<text class="ax" x="2" y="${top - 2}">${kgSay(maxD / 1.12)} kg a day</text></svg></div></div>
              <div><h4>Adding up <small>given so far against needed so far</small></h4><div class="rfx-chart-w"><svg viewBox="0 -4 ${W} ${H + 8}" style="min-width:28rem" aria-label="Given and needed, added up"><path class="givea" d="${area}"/><path class="givel" d="${line(CG, yC)}"/><path class="needl" d="${line(CN, yC)}"/>${todayL}${nowSay}${axis}<text class="ax" x="2" y="${top - 2}">${kgSay(maxC / 1.1)} kg</text></svg></div></div>
            </div>
            ${runs ? `<div class="rfx-runs">${runs}</div>` : ''}
            <div class="rfx-legend"><span><i style="background:${PHC.strong}"></i>Given, coloured by what it is doing</span><span><i style="background:none;border-top:2.5px dashed ${tok('--rx-ink')};height:0;border-radius:0"></i>Needed</span><span><i style="background:rgb(220 38 38 / .25)"></i>Short on a day that matters</span><span><i style="background:rgb(245 158 11 / .25)"></i>A little short</span></div></div>`;
    };
    /* How it connects: the soil, the links between products and nutrients, amounts and the crop's health, in words. */
    const GROUPS = [['soil', '🪨', 'Your soil'], ['links', '🔗', 'How your products connect'], ['amt', '⚖️', 'Amounts'], ['health', '🛡️', 'Crop health']];
    const logicHtml = (R) => {
        const L = R.logic;
        if (!L) return '';
        const notes = L.notes || [];
        const others = L.others || [];
        if (!notes.length && !others.length) return '';
        let html = '', k = 0;
        GROUPS.forEach(([key, icon, title]) => {
            const list = notes.filter((x) => x.k === key);
            if (!list.length) return;
            html += `<section><h4>${icon} ${title}</h4>${list.map((x) => `<div class="rfx-note is-${esc(x.tone || 'info')}" ${x.from ? `data-jump="${esc(x.from)}"` : ''} style="--k:${k++}"><em>${x.icon || '•'}</em><div><b>${esc(x.title)}</b><p>${esc(x.text)}</p>${(x.acts || []).length ? `<span class="rfx-note-acts">${x.acts.map((a) => a.href ? `<a class="rfx-chip" href="${esc(a.href)}">${esc(a.label)}</a>` : `<button type="button" class="rfx-chip is-on" data-copy-ans data-to="${a.copy.to}" data-from="${a.copy.from}">${esc(a.label)}</button>`).join('')}</span>` : ''}${x.from ? `<small>${say(x.from)}${x.to && x.to !== x.from ? ' to ' + say(x.to) : ''} · show it in the calendar</small>` : ''}</div></div>`).join('')}</section>`;
        });
        if (others.length) html += `<section><h4>🔁 What your products do to other nutrients</h4><ul class="rfx-others">${others.map((x) => `<li><b>${esc(x.name)}</b><span class="rfx-way is-${x.way === 'more' ? 'more' : 'less'}">${x.way === 'more' ? 'freed' : 'held back'}</span><span>${esc(x.why)}</span>${x.key ? `<button type="button" class="rfx-chip" data-goto="${esc(x.key)}">Check it</button>` : ''}</li>`).join('')}</ul></section>`;
        const on = Object.entries(L.opts || {}).filter(([, v]) => v).map(([kk]) => ({ amt: 'amounts', soil: 'soil', links: 'links', health: 'health' }[kk])).join(' · ');
        const warns = notes.filter((x) => x.tone === 'warn').length;
        return `<div class="rfx-card" data-fold="logic" style="--d:.06s"><div class="rfx-card-h">How it connects <small>${notes.length + others.length} note${notes.length + others.length === 1 ? '' : 's'}${warns ? ' · ' + warns + ' to watch' : ''}</small></div><div class="rfx-notes">${html}</div></div>`;
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
        const gapFill = tok('--rx-gap-line') || '#fecaca';
        const lane = (x) => {
            let r = '';
            for (let i = 0; i < n; i++) {
                if (x.c[i] >= 2) r += `<rect x="${i}" y="0" width="1.02" height="1.4" fill="${x.c[i] >= 3 ? '#dc2626' : '#f97316'}"/>`;
                if (x.s[i] === 'gap') r += `<rect x="${i}" y="1.8" width="1.02" height="8.2" fill="${gapFill}"/>`;
                const p = x.p[i];
                if (p && x.v[i] > 0.01) { const h = Math.max(1.2, Math.min(1, x.v[i]) * 8.2); r += `<rect x="${i}" y="${(10 - h).toFixed(2)}" width="1.02" height="${h.toFixed(2)}" fill="${PHC[p]}"/>`; }
                else if (p === 'soil') r += `<rect x="${i}" y="7.4" width="1.02" height="2.6" fill="${PHC.soil}"/>`;
            }
            if (ti >= 0) r += `<line x1="${ti + .5}" x2="${ti + .5}" y1="0" y2="10" stroke="${tok('--rx-today')}" stroke-width="1.5" vector-effect="non-scaling-stroke"/>`;
            if (hi >= 0) r += `<line x1="${hi + .5}" x2="${hi + .5}" y1="0" y2="10" stroke="#d97706" stroke-width="1.5" vector-effect="non-scaling-stroke"/>`;
            return r;
        };
        const lanes = all.map((x, k) => `<div class="rfx-lane ${x.main ? 'is-on' : ''}" style="--sc:${colorOf(x.key)};--k:${k}">
            <button type="button" class="rfx-lane-n" data-focus="${esc(x.key)}" title="${esc(x.label)}"><i></i>${esc(x.short)}</button>
            <svg class="rfx-lane-s" viewBox="0 0 ${n} 10" preserveAspectRatio="none" data-lane role="img" aria-label="${esc(x.label)} over the season">${lane(x)}</svg></div>`).join('');
        const stg = R.stages.map((g, gi) => {
            const i0 = R.days.findIndex((d) => d.d === g.from), i1 = R.days.findIndex((d) => d.d === g.to);
            return i0 < 0 ? '' : stageMark(g, gi, R, `width:${((i1 - i0 + 1) / n * 100).toFixed(3)}%`, true);
        }).join('');
        let mo = '';
        R.days.forEach((d, i) => { if (d.d.endsWith('-01')) mo += `<span style="left:${(i / n * 100).toFixed(2)}%">${MON[D(d.d).getMonth()]}</span>`; });
        return `<div class="rfx-card rfx-cmp" data-fold="cmp" style="--d:.04s"><div class="rfx-card-h">Side by side <small>${all.length} checks · tap one to open it below</small></div>
            <div class="rfx-minis">${rings}</div>
            <div class="rfx-lanes"><div class="rfx-lane is-head"><span></span><div class="rfx-stg">${stg}</div></div>${lanes}<div class="rfx-lane is-head"><span></span><div class="rfx-mo">${mo}</div></div></div>
            ${phaseLegend(R, all)}
            <div class="rfx-legend"><span><i style="background:linear-gradient(180deg,#dc2626 0 22%,var(--rx-soft) 22%);box-shadow:inset 0 0 0 1px var(--rx-line)"></i>A line on top: that one is critical now</span><span><i style="background:var(--rx-gap-line)"></i>A gap</span><span><i style="background:var(--rx-today);width:3px"></i>Today</span></div></div>`;
    };

    /* Quick switches over the season chart (the owner, 2026-10-30: "the season have a quick toggle buttons to show and
       hide the nutrients"). A picked check shows or hides its line at a tap, nothing counted again. With a nutrient
       shown, N, P, K and every nutrient the season's products carry are offered too: one not picked yet is added to
       the comparison (counted then) and shown, beside the shaded one. One always stays on the chart. */
    const shownOn = (key) => S.checks.includes(key) && !S.hide.includes(key);
    const seasonKeys = (R) => {
        const nuts = ((S.opts || {}).nutrients || []).map((n) => n.key);
        const carried = new Set(['n:N', 'n:P', 'n:K']);
        (R.apps || []).forEach((a) => (a.carries || []).forEach((c) => carried.add('n:' + String(c).split(' ')[0])));
        const offer = R.check.kind === 'nutrient' ? nuts.filter((k) => carried.has(k) && !S.checks.includes(k)) : [];
        return [...S.checks, ...offer];
    };
    const lnRowHtml = (R) => {
        const keys = seasonKeys(R);
        if (keys.length < 2) return '';
        const nuts = (S.opts || {}).nutrients || [];
        const name = (k) => { const n = nuts.find((x) => x.key === k); return n ? n.short : labelOf(k); };
        const add = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" aria-hidden="true"><path stroke-linecap="round" d="M12 5v14M5 12h14"/></svg>';
        return `<div class="rfx-lnrow" role="group" aria-label="Show or hide on the chart">${keys.map((k, i) => {
            const picked = S.checks.includes(k), on = shownOn(k);
            return `<button type="button" class="rfx-ln${on ? ' is-on' : ''}${picked ? '' : ' is-add'}" data-ln-t="${esc(k)}" aria-pressed="${on}" style="--sc:${picked ? colorOf(k) : 'var(--rx-faint)'};--k:${i}" title="${esc(labelOf(k))}${picked ? (on ? ': tap to hide it' : ': tap to show it') : ': tap to add it to the chart'}">${picked ? '<i></i>' : add}${esc(name(k))}${k === R.check.key && S.checks.length > 1 ? '<small>shaded</small>' : ''}</button>`;
        }).join('')}</div>`;
    };
    const toggleLine = (key) => {
        if (!S.checks.includes(key)) {
            if (S.checks.length >= SERIES.length) { window.toast?.(`Up to ${SERIES.length} on the chart. Take one out first.`, 'error'); return; }
            const b = $(`[data-ln-t="${key}"]`);
            if (b) b.classList.add('is-busy');
            togglePick(key, true);
            return;
        }
        const off = !S.hide.includes(key);
        if (off && S.checks.filter((k) => !S.hide.includes(k)).length <= 1) { window.toast?.('One stays on the chart.'); return; }
        S.hide = off ? [...S.hide, key] : S.hide.filter((k) => k !== key);
        $$(`.rfx-chart [data-ln="${key}"]`).forEach((el) => el.classList.toggle('is-off', off));
        const b = $(`[data-ln-t="${key}"]`);
        if (b) {
            b.classList.toggle('is-on', !off);
            b.setAttribute('aria-pressed', String(!off));
            b.title = labelOf(key) + (off ? ': tap to show it' : ': tap to hide it');
        }
        keep();
    };

    /* The growth stages said short (the owner, 2026-10-30: "in the season, just use symbols and acronym for the growth
       stages"): a symbol and two letters each, the same on the chart and side by side; a tap opens the stage's sheet.
       Every stage name of the crop book is here; one it lacks gets its initials. */
    const STAGE_MARK = {
        'Before planting': ['🚜', 'BP'], 'No planting day yet': ['❔', 'ND'], 'After harvest': ['🧺', 'AH'],
        'Germination': ['🫘', 'GR'], 'Germination & emergence': ['🌱', 'GE'], 'Emergence': ['🌱', 'EM'], 'Seedling': ['🌱', 'SD'],
        'Seedling establishment': ['🪴', 'SE'], 'Establishment': ['🪴', 'ES'], 'Recovery': ['🪴', 'RC'], 'Root initiation': ['🪴', 'RI'],
        'Early vegetative': ['🌿', 'EV'], 'Vegetative': ['🌿', 'VG'], 'Vegetative growth': ['🌿', 'VG'], 'Late vegetative': ['🌿', 'LV'],
        'Rapid growth': ['🌿', 'RG'], 'Grand growth': ['🌿', 'GG'], 'Juvenile growth': ['🌱', 'JG'], 'Leaf growth': ['🍃', 'LG'], 'Vine growth': ['🍃', 'VN'],
        'Tillering': ['🌿', 'TL'], 'Early tillering': ['🌿', 'ET'], 'Active tillering': ['🌿', 'AT'],
        'Panicle initiation': ['🎯', 'PI'], 'Booting & heading': ['🎋', 'BH'], 'Heading & filling': ['🎋', 'HF'], 'Shooting': ['🎋', 'SH'], 'Tasselling': ['🎋', 'TS'],
        'Reproductive': ['🌼', 'RP'], 'Flowering': ['🌼', 'FL'], 'Silking & pollination': ['🌼', 'SP'], 'Fruit set & filling': ['🍅', 'FS'],
        'Pod filling': ['🫛', 'PF'], 'Grain filling': ['🍚', 'GF'], 'Milk stage': ['🥛', 'MK'], 'Bunch filling': ['🍌', 'BF'], 'Bulking': ['🥔', 'BK'], 'Bulbing': ['🧅', 'BB'],
        'Maturation': ['🌕', 'MT'], 'Maturity & curing': ['🌕', 'MC'], 'Maturity & harvest': ['🧺', 'MH'], 'Ripening & harvest': ['🧺', 'RH'], 'Harvest': ['🧺', 'HV'],
        'First bearing': ['🍊', 'FB'], 'Mature bearing': ['🌳', 'MB'], 'Old and declining': ['🍂', 'OD'],
    };
    const markOf = (label) => {
        if (STAGE_MARK[label]) return STAGE_MARK[label];
        const w = String(label || '?').split(/[\s&,/]+/).filter((x) => x && !['and', 'of', 'the'].includes(x.toLowerCase()));
        const ab = w.length > 1 ? w[0][0] + w[1][0] : (w[0] || '?')[0] + (((w[0] || '').slice(1).match(/[^aeiou]/i) || [''])[0]);
        return ['🌿', ab.toUpperCase()];
    };
    const stageMark = (g, gi, R, style, small = false) => {
        const [ic, ab] = markOf(g.label);
        const now = R.today >= g.from && R.today <= g.to;
        return `<button type="button" class="rfx-sb${small ? ' is-sm' : ''} c${g.crit || 0}${now ? ' is-now' : ''}" data-stage="${gi}" style="${style}" title="${esc(g.label)}: ${say(g.from)} to ${say(g.to)}" aria-label="${esc(g.label)}, ${say(g.from)} to ${say(g.to)}"><span><i aria-hidden="true">${ic}</i><b>${esc(ab)}</b></span></button>`;
    };
    const CRIT_SAY = ['Not needed now', 'Matters a little', 'Needed', 'Critical'];
    const plural = (n, w) => n + ' ' + w + (n === 1 ? '' : 's');
    // "DAS 15" and "DAS 36" as "DAS 15 to 36"; a count the delay counter moves, as the board writes it.
    const countRange = (a, b, minus = 0) => {
        const m0 = /^([A-Z]+) (-?\d+)$/.exec(a || ''), m1 = /^([A-Z]+) (-?\d+)$/.exec(b || '');
        if (m0 && m1 && m0[1] === m1[1]) { const f = Number(m0[2]) - minus, t = Number(m1[2]) - minus; return `${m0[1]} ${f}${t !== f ? ' to ' + t : ''}`; }
        return minus ? '' : [a, b].filter(Boolean).filter((v, i, x) => x.indexOf(v) === i).join(' to ');
    };
    const stageBox = $('[data-rfx-stg]');
    let stageAt = -1;
    const stageHtml = (R, gi) => {
        const g = R.stages[gi];
        const [ic, ab] = markOf(g.label);
        const ds = R.days.filter((d) => d.d >= g.from && d.d <= g.to);
        const len = ds.length;
        const cnt = ds.length ? countRange(ds[0].dl, ds[ds.length - 1].dl) : '';
        let when;
        if (R.today < g.from) when = `Starts in ${plural(Math.round((D(g.from) - D(R.today)) / 864e5), 'day')}`;
        else if (R.today > g.to) when = `Ended ${plural(Math.round((D(R.today) - D(g.to)) / 864e5), 'day')} ago`;
        else { const k = Math.round((D(R.today) - D(g.from)) / 864e5) + 1; when = `Today is day ${k} of ${len}`; }
        const nowPct = R.today >= g.from && R.today <= g.to ? Math.round((Math.round((D(R.today) - D(g.from)) / 864e5) + 1) / Math.max(1, len) * 100) : (R.today > g.to ? 100 : 0);
        let h = `<div class="rfx-day-h"><div><b id="rfxStgT"><span class="rfx-stg-ic" aria-hidden="true">${ic}</span>${esc(g.label)}<em class="rfx-stg-ab">${esc(ab)}</em></b>
            <small>${g.index != null && g.count ? `Stage ${g.index + 1} of ${g.count} · ` : ''}${esc(R.lot.name)}, ${esc(R.lot.crop)}</small></div>
            <button type="button" class="rfx-x" data-rfx-stg-x aria-label="Close">${'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true"><path stroke-linecap="round" d="M6 6l12 12M18 6L6 18"/></svg>'}</button></div>`;
        // Its days: the dates, how long, the count, where today is.
        h += `<div class="rfx-stg-when"><div><b>${say(g.from)}${g.to !== g.from ? ' to ' + say(g.to) : ''}</b><small>${plural(len, 'day')}${cnt ? ' · ' + esc(cnt) : ''}</small></div><span>${esc(when)}</span></div>
            <div class="rfx-meter" aria-hidden="true"><i style="width:${nowPct}%;background:var(--rx-accent)"></i></div>`;
        // Anee's realign: these dates follow it; the calendar's own beside them.
        const ra = R.lot.realign;
        if (ra && g.stage) {
            const sh = Number(ra.shift) || 0, sure = ra.confidence ? `, ${ra.confidence}% sure` : '';
            h += `<div class="rfx-stg-ra"><b>🎯 Anee's realign, ${say(ra.at)}${sure}</b>`;
            if (!sh) h += `<p>She found the crop on time with the calendar, so these are the calendar's own dates.</p>`;
            else {
                h += `<p>She found the crop <b>${plural(Math.abs(sh), 'day')} ${sh < 0 ? 'behind' : 'ahead of'}</b> the calendar, so the dates above follow her reading.</p>`;
                if (g.cal && g.cal.from) {
                    h += `<div class="rfx-stg-cmp"><span><em>📅 The calendar alone</em><b>${say(g.cal.from)}${g.cal.to && g.cal.to !== g.cal.from ? ' to ' + say(g.cal.to) : ''}</b></span>
                        <span class="is-ra"><em>🎯 With the realign</em><b>${say(g.from)}${g.to !== g.from ? ' to ' + say(g.to) : ''}</b></span></div>`;
                }
            }
            if (ra.summary) h += `<details class="rfx-stg-saw"><summary>What Anee saw</summary><p>${esc(ra.summary)}</p></details>`;
            h += '</div>';
        }
        // The farmer's own delay counter, as the board counts it beside the calendar's.
        const dl = Number(R.lot.delay) || 0;
        if (dl > 0 && g.stage && ds.length) {
            const dc = countRange(ds[0].dl, ds[ds.length - 1].dl, dl);
            h += `<div class="rfx-stg-ra is-delay"><b>⏳ Your delay counter: ${plural(dl, 'day')} behind</b><p>${dc ? `By its count this stage is <b>DELAY ${esc(dc)}</b>. ` : ''}The board shows both counts on each task.</p></div>`;
        }
        // How much the check matters here, and how well it is covered.
        if (g.stage) {
            const by = {};
            ds.forEach((d) => { by[d.s] = (by[d.s] || 0) + 1; });
            const bits = [];
            if (by.ok) bits.push(`<span class="is-ok">${plural(by.ok, 'day')} covered</span>`);
            if (by.thin) bits.push(`<span class="is-thin">${by.thin} partly</span>`);
            if (by.gap) bits.push(`<span class="is-gap">${plural(by.gap, 'gap day')}</span>`);
            if (by.late) bits.push(`<span>${by.late} still working, not needed</span>`);
            h += `<div class="rfx-stg-crit c${g.crit || 0}"><b>${CRIT_SAY[g.crit || 0]} for ${esc(R.check.word)}</b>${g.why ? `<p>${esc(g.why)}</p>` : ''}${bits.length ? `<div class="rfx-stg-cov">${bits.join('')}</div>` : ''}</div>`;
        }
        if (g.what || g.needs) h += `<div class="rfx-stg-do">${g.what ? `<p><b>The crop:</b> ${esc(g.what)}</p>` : ''}${g.needs ? `<p><b>It needs:</b> ${esc(g.needs)}</p>` : ''}</div>`;
        // What the plan puts on in these days: where the stage sits in the protocol.
        const apps = (R.apps || []).filter((a) => a.date >= g.from && a.date <= g.to);
        h += `<div class="rfx-stg-apps"><b>On the plan in these days</b>${apps.length ? `<ul>${apps.map((a) => {
            const at = R.days.find((d) => d.d === a.date);
            return `<li class="${a.relevant ? 'is-rel' : ''}"><span>${say(a.date)}${at && at.dl ? `<small>${esc(at.dl)}</small>` : ''}</span><em>${esc(a.as)}</em><i>${a.try ? 'Added by you' : a.done ? 'Done' : 'Planned'}</i></li>`;
        }).join('')}</ul>` : '<p>Nothing on the tasks in these days.</p>'}</div>`;
        // Step to the stage before or after.
        const prev = R.stages[gi - 1], next = R.stages[gi + 1];
        h += `<div class="rfx-edit-b">${prev ? `<button type="button" class="rfx-btn is-ghost" data-stg-go="${gi - 1}">‹ ${markOf(prev.label)[0]} ${esc(markOf(prev.label)[1])}</button>` : ''}<span style="flex:1"></span>${next ? `<button type="button" class="rfx-btn is-ghost" data-stg-go="${gi + 1}">${markOf(next.label)[0]} ${esc(markOf(next.label)[1])} ›</button>` : ''}</div>`;
        return h;
    };
    const openStage = (gi) => {
        const R = S.res;
        if (!R || !R.stages || !R.stages[gi]) return;
        const was = stageAt;
        stageAt = gi;
        const c = $('[data-rfx-stg-b]');
        c.innerHTML = stageHtml(R, gi);
        c.scrollTop = 0;
        if (was >= 0 && !stageBox.hidden) { if (!calm) { c.classList.remove('is-step'); void c.offsetWidth; c.classList.add('is-step'); } return; }
        stageBox.hidden = false;
        requestAnimationFrame(() => requestAnimationFrame(() => stageBox.classList.add('is-on')));
        setTimeout(() => c.querySelector('[data-rfx-stg-x]')?.focus({ preventScroll: true }), calm ? 0 : 200);
    };
    const closeStage = () => {
        stageAt = -1;
        stageBox.classList.remove('is-on');
        setTimeout(() => { if (stageAt < 0) stageBox.hidden = true; }, calm ? 0 : 260);
    };
    stageBox.addEventListener('click', (e) => {
        if (e.target.closest('[data-rfx-stg-x]')) { closeStage(); return; }
        const go = e.target.closest('[data-stg-go]');
        if (go) openStage(Number(go.dataset.stgGo));
    });

    /* The season as a chart: coverage per day over the stages, each stage tinted by how critical it is. */
    const chartHtml = (R) => {
        const days = R.days;
        if (!days.length) return '';
        // Every line hidden (the last shown one taken out above): all of them come back.
        if (S.checks.every((k) => S.hide.includes(k))) S.hide = [];
        const mk = esc(R.check.key), off = (k) => (shownOn(k) ? '' : ' is-off'), offMain = off(R.check.key);
        const W = 1000, H = 176, top = 8, base = 136, n = days.length;
        const x = (i) => (i / Math.max(1, n - 1)) * W;
        const y = (v) => base - Math.min(1.6, v) / 1.6 * (base - top);
        const critCol = ['rgba(0,0,0,0)', 'rgba(245,158,11,.10)', 'rgba(249,115,22,.16)', 'rgba(220,38,38,.18)'];
        let bands = '', marks = '';
        R.stages.forEach((g, gi) => {
            const i0 = days.findIndex((d) => d.d === g.from), i1 = days.findIndex((d) => d.d === g.to);
            if (i0 < 0) return;
            const x0 = x(i0) - (W / n) / 2, x1 = x(i1) + (W / n) / 2;
            bands += `<rect x="${x0.toFixed(1)}" y="${top}" width="${Math.max(0, x1 - x0).toFixed(1)}" height="${base - top}" fill="${critCol[g.crit] || critCol[0]}"/>`;
            // Each stage a symbol and two letters over its band, never words cut short; a tap opens the stage.
            const l0 = Math.max(0, x0), l1 = Math.min(W, x1);
            marks += stageMark(g, gi, R, `left:${(l0 / W * 100).toFixed(3)}%;width:${((l1 - l0) / W * 100).toFixed(3)}%`);
            bands += `<line class="grid" x1="${x0.toFixed(1)}" x2="${x0.toFixed(1)}" y1="${top}" y2="${base}"/>`;
        });
        let line = '', area = `M0 ${base}`, tryLine = '';
        days.forEach((d, i) => { const p = `${x(i).toFixed(1)} ${y(d.v).toFixed(1)}`; line += (i ? ' L' : 'M') + p; area += ' L' + p; });
        area += ` L${W} ${base} Z`;
        if (days.some((d) => d.t)) days.forEach((d, i) => { tryLine += (i ? ' L' : 'M') + `${x(i).toFixed(1)} ${y(d.t).toFixed(1)}`; });
        // The other checks being compared, a line each in its own colour.
        const cmp = (R.series || []).length;
        const others = (R.series || []).map((sr) => `<path class="ln${off(sr.check.key)}" data-ln="${esc(sr.check.key)}" d="${sr.v.map((v, i) => (i ? 'L' : 'M') + x(i).toFixed(1) + ' ' + y(v).toFixed(1)).join(' ')}" fill="none" stroke="${colorOf(sr.check.key)}" stroke-width="1.8" stroke-linejoin="round" opacity=".9"/>`).join('');
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
        const today = ti >= 0 ? `<line class="today" x1="${x(ti)}" x2="${x(ti)}" y1="${top - 2}" y2="${base}"/><text class="mo t-today" x="${x(ti) + 4}" y="${base - 5}">Today</text>` : '';
        const hi = R.lot.harvest ? days.findIndex((d) => d.d === R.lot.harvest) : -1;
        const harv = hi >= 0 ? `<line class="harv" x1="${x(hi)}" x2="${x(hi)}" y1="${top}" y2="${base}"/><text class="mo t-harv" x="${x(hi) - 4}" y="${top + 12}" text-anchor="end">Harvest</text>` : '';
        const full = y(1);
        let dots = '';
        R.apps.filter((a) => a.relevant).forEach((a) => {
            const i = days.findIndex((d) => d.d === a.date);
            if (i < 0) return;
            dots += `<circle class="app ${a.try ? 'is-try' : a.done ? 'is-done' : 'is-plan'}" cx="${x(i).toFixed(1)}" cy="${base + 32}" r="5.5" data-jump="${a.date}"><title>${esc(a.as)} · ${say(a.date)}</title></circle>`;
        });
        return `<div class="rfx-card" data-fold="chart" style="--d:.08s"><div class="rfx-card-h">The season <small>coverage of ${esc(R.check.word)}, day by day</small></div>${lnRowHtml(R)}<div class="rfx-chart"><div class="rfx-chart-w"><div class="rfx-chart-in">
            <div class="rfx-sbar" role="group" aria-label="Growth stages: tap one to see its dates">${marks}</div>
            <svg viewBox="0 -4 ${W} ${H + 26}" aria-label="Coverage over the season">
              <defs><linearGradient id="rfxPhG" gradientUnits="userSpaceOnUse" x1="0" y1="0" x2="${W}" y2="0">${stops}</linearGradient>
                <linearGradient id="rfxCovV" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#fff" stop-opacity=".9"/><stop offset="1" stop-color="#fff" stop-opacity=".12"/></linearGradient>
                <mask id="rfxCovM" maskUnits="userSpaceOnUse" x="0" y="0" width="${W}" height="${base + 1}"><rect x="0" y="0" width="${W}" height="${base + 1}" fill="url(#rfxCovV)"/></mask></defs>
              ${bands}
              <line class="dose" x1="0" x2="${W}" y1="${full.toFixed(1)}" y2="${full.toFixed(1)}"/><text class="mo" x="2" y="${(full - 4).toFixed(1)}">one full dose</text>
              <g class="reveal"><path class="ln${offMain}" data-ln="${mk}" d="${area}" fill="url(#rfxPhG)" mask="url(#rfxCovM)"/>${others}<path class="cov-l ln${offMain}" data-ln="${mk}" d="${line}" ${cmp ? `style="stroke:${colorOf(R.check.key)};stroke-width:2.6"` : ''}/>${tryLine ? `<path class="try-l ln${offMain}" data-ln="${mk}" d="${tryLine}"/>` : ''}</g>
              <line class="base" x1="0" x2="${W}" y1="${base}" y2="${base}"/>
              <g class="ln${offMain}" data-ln="${mk}">${ribbon}</g>${months}${today}${harv}${dots}
            </svg></div></div></div>
            ${phaseLegend(R)}
            <div class="rfx-legend"><span><i class="rfx-leg-band is-crit"></i>Critical stage</span><span><i class="rfx-leg-band is-mid"></i>Matters</span>${R.days.some((d) => d.t) ? '<span><i style="background:#f5c518"></i>Added by you</span>' : ''}<span><i class="rfx-leg-dot is-done"></i>Done</span><span><i class="rfx-leg-dot is-plan"></i>Planned</span></div></div>`;
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
                const ap = here.slice(0, 2).map((a) => `<i class="${a.try ? 'is-try' : a.done ? '' : 'is-plan'}" title="${esc(a.as)}${a.try ? ', added by you' : a.done ? ', done' : ', planned'}"><svg><use href="#rfxI-${apMark(a)}"/></svg></i>`).join('')
                    + (here.length > 2 ? `<b>+${here.length - 2}</b>` : '');
                // Text over the fill: the number when it is nearly full, the day count from about half; white on the
                // deep fills, near black on the pale ones.
                const full = d.v >= 0.7 && d.s !== 'gap';
                const half = !full && d.v >= 0.42 && d.s !== 'gap';
                const pale = (full || half) && ['easing', 'fading'].includes(d.p);
                cells.push(`<button type="button" class="rfx-cell ${ds === R.today ? 'is-today' : ''} ${full ? 'is-full' : ''} ${half ? 'is-half' : ''} ${pale ? 'is-lt' : ''}" data-day="${ds}" data-s="${d.s}" data-p="${d.p || ''}" data-t="${d.t ? 1 : 0}" style="--k:${k++};--h:${Math.round(Math.min(1, d.v) * 100)}%">
                    <span class="rfx-fill"></span>${d.sl ? '<span class="rfx-soil"></span>' : ''}<span class="rfx-n">${dd}</span>${d.dl ? `<span class="rfx-dl">${esc(d.dl)}</span>` : ''}
                    ${d.c ? `<span class="rfx-crit c${d.c}" aria-label="${CRIT[d.c]}"><i></i><i></i><i></i></span>` : ''}${ap ? `<span class="rfx-ap">${ap}</span>` : ''}${d.h ? `<span class="rfx-hm is-${d.h}" title="${d.h === 'n' ? 'Nitrogen is lush: scout more often' : 'Potassium or silicon is helping the crop hold out'}">${d.h === 'n' ? 'N' : 'K'}</span>` : ''}</button>`);
            }
            html += `<div class="rfx-month"><div class="rfx-month-h"><b>${MON[m.getMonth()]} ${m.getFullYear()}</b>${crit ? `<small>${crit} critical day${crit === 1 ? '' : 's'}</small>` : ''}</div>
                <div class="rfx-wk">${DOW.map((w) => `<span class="rfx-wd">${w}</span>`).join('')}${cells.join('')}</div></div>`;
        }
        const late = R.check.kind === 'nutrient' ? 'late' : 'extra';
        return `<div class="rfx-card" data-fold="cal" style="--d:.14s"><div class="rfx-card-h">The calendar <small>tap a day</small></div><div class="rfx-cal">${html}</div>
            ${phaseLegend(R)}
            <div class="rfx-legend"><b class="rfx-leg-h">What it means for the crop</b>
              <span><i style="background:repeating-linear-gradient(135deg,var(--rx-gap-a) 0 3px,var(--rx-gap-edge) 3px 5px);box-shadow:inset 0 0 0 1.5px var(--rx-gap-edge)"></i>${STATE.gap[0]}</span>
              <span><i class="rfx-leg-cell" style="box-shadow:inset 0 0 0 2px #f59e0b"></i>${STATE.thin[0]}, only partly covered</span>
              <span><i class="rfx-leg-cell" style="box-shadow:inset 0 0 0 2px #a78bfa;background:repeating-linear-gradient(135deg,rgb(124 58 237 / .45) 0 2px,#fff 2px 5px)"></i>${STATE[late][0]}</span>
              <span><span class="rfx-crit c3" style="position:static"><i></i><i></i><i></i></span>&nbsp;How critical the stage is</span>
              <span><i class="rfx-leg-cell" style="box-shadow:inset 0 0 0 2px var(--rx-today)"></i>Today</span>
              ${R.days.some((x) => x.h === 'n') ? '<span><span class="rfx-hm is-n" style="position:static">N</span>Nitrogen lush: pests and disease like it</span>' : ''}
              ${R.days.some((x) => x.h === 'k') ? '<span><span class="rfx-hm is-k" style="position:static">K</span>Potassium or silicon helping the crop hold out</span>' : ''}</div>
            <div class="rfx-legend rfx-leg-ap"><b class="rfx-leg-h">What went on</b>
              <span><span class="rfx-ap"><i><svg><use href="#rfxI-gran"/></svg></i></span>Spread on the soil</span>
              <span><span class="rfx-ap"><i><svg><use href="#rfxI-drop"/></svg></i></span>Sprayed or poured</span>
              <span><span class="rfx-ap"><i><svg><use href="#rfxI-leaf"/></svg></i></span>Organic or biological</span>
              <span><span class="rfx-ap"><i class="is-try"><svg><use href="#rfxI-star"/></svg></i></span>Added by you</span>
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
                <div class="rfx-app-t"><b>${esc(a.as)}</b>${a.try ? '<span class="rfx-tag is-try">Added by you</span>' : a.done ? '<span class="rfx-tag is-done">Done</span>' : '<span class="rfx-tag">Planned</span>'}${e.conf === 'estimate' || e.conf === 'low' ? '<span class="rfx-tag is-est">Estimated</span>' : ''}
                  <small>${esc([a.title !== 'If you apply it' ? a.title : '', a.amount, a.formWord, doseWord].filter(Boolean).join(' · '))}</small>
                  <small>Works${by} from <b>${say(e.from)}</b> to about <b>${say(e.full)}</b>, fading out by ${say(e.until)} (${e.days[1]} days, ${e.days[0]} to ${e.days[2]}).</small>
                  <div class="rfx-bar"><span class="rfx-segs">${segs}</span></div>${phRow}${trace}
                  ${e.note ? `<div class="rfx-app-n">${esc(e.note)}</div>` : ''}</div></div>`;
        }).join('');
        const none = !rel.length ? `<div class="rfx-empty" style="padding:1.2rem"><p>Nothing on this lot's tasks acts on ${esc(R.check.word)} yet. Add it to a task, or add a product under Fertilizers above to see what it would do.</p></div>` : '';
        const oth = other.length ? `<details class="rfx-other"><summary>${other.length} other product${other.length === 1 ? '' : 's'} on this lot do not act on ${esc(R.check.word)}</summary><ul>${other.map((a) => `<li>${say(a.date)} · ${esc(a.as)}${a.carries.length ? ' (' + esc(a.carries.join(', ')) + ')' : a.ai.length ? ' (' + esc(a.ai.join(' + ')) + ')' : ''}</li>`).join('')}</ul></details>` : '';
        return `<div class="rfx-card" data-fold="apps" style="--d:.2s"><div class="rfx-card-h">What is working <small>${rel.length} product${rel.length === 1 ? '' : 's'}</small></div><div class="rfx-apps">${rows}${none}</div>${oth}</div>`;
    };

    /* One day, opened. */
    const dayBox = $('[data-rfx-day]');
    const hideDay = () => { dayBox.hidden = true; };
    $('[data-rfx-body]').addEventListener('keydown', (e) => {
        const fh = e.target.closest('.rfx-fold-h');
        if (fh && (e.key === 'Enter' || e.key === ' ')) { e.preventDefault(); const c = fh.closest('[data-fold]'); setFold(c, !c.classList.contains('is-open')); }
    });
    $('[data-rfx-body]').addEventListener('click', (e) => {
        const fa = e.target.closest('[data-fold-all]');
        if (fa) {
            const v = fa.dataset.foldAll === '1';
            $$('[data-rfx-body] .rfx-card[data-fold]').forEach((c) => setFold(c, v));
            return;
        }
        const fh = e.target.closest('.rfx-fold-h');
        if (fh) {
            const c = fh.closest('[data-fold]');
            setFold(c, !c.classList.contains('is-open'));
            return;
        }
        const lt = e.target.closest('[data-ln-t]');
        if (lt) { toggleLine(lt.dataset.lnT); return; }
        const sb = e.target.closest('[data-stage]');
        if (sb) { openStage(Number(sb.dataset.stage)); return; }
        const cp = e.target.closest('[data-copy-ans]');
        if (cp) {
            cp.disabled = true;
            window.api(U.copyAnswers, { method: 'POST', body: { to: Number(cp.dataset.to), from: Number(cp.dataset.from) } })
                .then((r) => { window.toast?.(r.message); run(); })
                .catch((err) => { window.toast?.(err.message, 'error'); cp.disabled = false; });
            return;
        }
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
            if (cell) {
                // A closed calendar opens first, then the day comes into view.
                const f = cell.closest('[data-fold]');
                const wait = f && !f.classList.contains('is-open') ? (calm ? 0 : 340) : 0;
                if (wait || (f && !f.classList.contains('is-open'))) setFold(f, true);
                setTimeout(() => { cell.scrollIntoView({ behavior: calm ? 'auto' : 'smooth', block: 'center' }); setTimeout(() => showDay(jump.dataset.jump), calm ? 0 : 450); }, wait);
            }
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
            ${apps.length ? `<ul>${apps.map((a) => { const ph = phaseOn(a); return `<li><i class="rfx-sw is-${ph ? ph.p : 'strong'}"></i> <b>${esc(a.as)}</b> (${say(a.date)}${a.try ? ', added by you' : ''}): ${ph ? esc(ph.w.charAt(0).toLowerCase() + ph.w.slice(1)) + (ph.p === 'soil' ? ', taken up from ' + say(a.effect.from) : ph.p === 'fading' ? ', gone by about ' + say(a.effect.until) : ', working until about ' + say(a.effect.full)) : 'working until about ' + say(a.effect.full)}</li>`; }).join('')}</ul>` : '<p style="color:#6b7280">Nothing applied is working on this day.</p>'}`;
        // How much that day and so far; the crop's health that day.
        const A = R.logic?.amt;
        const ix = R.days.indexOf(d);
        let more = '';
        if (A && A.known && A.n && !A.soilGives) {
            let cg = 0, cn = 0;
            for (let j = 0; j <= ix; j++) { if (!A.in || A.in[j]) { cg += A.g[j]; cn += A.n[j]; } }
            const g = A.g[ix], nd = A.n[ix];
            more += `<div class="rfx-day-amt">⚖️ <b>That day</b> your products give about ${kgSay(g)} kg ${esc(A.say)} a hectare; the crop takes about ${kgSay(nd)}${nd > 0.0005 ? ` (${Math.round(g / nd * 100)}%)` : ''}. <b>So far</b>: ${kgSay(cg)} of ${kgSay(cn)} kg.</div>`;
        }
        if (d.h) more += `<div class="rfx-day-amt">${d.h === 'n' ? '🌿 <b>Nitrogen is lush</b> on this day: soft leaves draw pests and disease. Scout more often.' : '🛡️ <b>Potassium or silicon</b> is working on this day, helping the crop hold out.'}</div>`;
        if (more) dayBox.insertAdjacentHTML('beforeend', more);
        // The other checks on the same day.
        if (cmpOn) {
            const i = R.days.indexOf(d);
            dayBox.insertAdjacentHTML('beforeend', `<div class="rfx-day-cmp">${R.series.map((sr) => {
                const p = sr.p[i], st = sr.s[i];
                return `<div style="--sc:${colorOf(sr.check.key)}"><i></i><b>${esc(sr.check.label)}</b><span class="rfx-meter"><i style="width:${Math.min(100, Math.round(sr.v[i] * 100))}%;background:${p ? `var(--ph-${p})` : 'var(--rx-dot)'}"></i></span><small>${Math.round(sr.v[i] * 100)}% · ${esc(STATE[st] ? STATE[st][0].split(':')[0] : '')}${p ? ' · ' + esc((sr.words || {})[p] || '') : ''}</small></div>`;
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
    // A saved reading opened (or just saved) is the one Save puts the changes over (the owner, 2026-10-30).
    const defaultTitle = () => [S.res.check.label, ...(S.res.series || []).map((x) => x.check.label)].join(', ') + ` · ${S.res.lot.name}` + (S.tries.length ? ` · with ${S.tries.map((t) => t.name).join(', ')}` : '');
    const paintFoot = () => {
        const ed = !!S.savedId;
        $('[data-rfx-editing]').hidden = !ed;
        $('[data-rfx-note]').hidden = ed;
        $('[data-rfx-save-new]').hidden = !ed;
        $('[data-rfx-save-t]').textContent = ed ? 'Update saved reading' : 'Save this reading';
        $('[data-rfx-editing-t]').textContent = S.savedTitle || '';
    };
    const doSave = async (asNew) => {
        if (!S.res || S.busy) return;
        S.busy = true;
        const btns = [$('[data-rfx-save]'), $('[data-rfx-save-new]')];
        btns.forEach((b) => { b.disabled = true; });
        try {
            const id = asNew ? null : S.savedId;
            const r = await window.api(U.save, { method: 'POST', body: { scheduleId: SID, id, lotId: S.lot, check: S.check, also: S.checks.filter((k) => k !== S.check), tries: S.tries, logic: S.logic, title: id ? S.savedTitle : defaultTitle() } });
            S.opts.saved = r.data.saved;
            S.savedId = r.data.id;
            S.savedTitle = r.data.title;
            paintSavedCount();
            paintFoot();
            window.toast?.(r.data.updated ? 'Updated. The saved reading now holds these changes.' : 'Saved. Rename it below, or open it any time from Saved.');
        } catch (err) { window.toast?.(err.message || 'It could not be saved.', 'error'); }
        finally { S.busy = false; btns.forEach((b) => { b.disabled = false; }); }
    };
    $('[data-rfx-save]').addEventListener('click', () => doSave(false));
    $('[data-rfx-save-new]').addEventListener('click', () => doSave(true));
    $('[data-rfx-stop-edit]').addEventListener('click', () => { S.savedId = null; S.savedTitle = ''; S.saved = null; paintFoot(); if (S.res) paint(); });
    const renameSaved = async (id, title) => {
        const t = String(title || '').trim();
        if (!t) return false;
        try {
            const r = await window.api(U.saved + id + '/name', { method: 'POST', body: { scheduleId: SID, title: t } });
            S.opts.saved = r.data.saved;
            if (S.savedId === id) { S.savedTitle = r.data.title; paintFoot(); }
            return true;
        } catch (err) { window.toast?.(err.message || 'It could not be renamed.', 'error'); return false; }
    };
    // Renaming the one being edited, in the foot.
    const curIn = $('[data-rfx-editing-in]');
    $('[data-rfx-rename-cur]').addEventListener('click', () => {
        curIn.value = S.savedTitle;
        curIn.hidden = false;
        $('[data-rfx-editing-t]').hidden = true;
        curIn.focus();
        curIn.select();
    });
    const endCur = async (keep) => {
        if (curIn.hidden) return;
        const v = curIn.value.trim();
        curIn.hidden = true;
        $('[data-rfx-editing-t]').hidden = false;
        if (keep && v && v !== S.savedTitle && await renameSaved(S.savedId, v)) window.toast?.('Renamed.');
    };
    curIn.addEventListener('keydown', (e) => { if (e.key === 'Enter') { e.preventDefault(); endCur(true); } else if (e.key === 'Escape') { e.stopPropagation(); endCur(false); } });
    curIn.addEventListener('blur', () => endCur(true));
    const openSaved = () => {
        const list = S.opts?.saved || [];
        const lots = Object.fromEntries((S.opts?.lots || []).map((l) => [l.id, l.name]));
        $('[data-rfx-saved]').innerHTML = list.length ? list.map((s) => `<div class="rfx-sv ${s.id === S.savedId ? 'is-cur' : ''}"><button type="button" class="rfx-sv-open" data-open-saved="${s.id}"><b>${esc(s.title)}</b><small>${esc(s.at)}${s.edited ? ' · changed ' + esc(s.edited) : ''}${lots[s.lotId] ? ' · ' + esc(lots[s.lotId]) : ''}${s.id === S.savedId ? ' · open now' : ''}</small></button><button type="button" class="rfx-sv-x" data-ren-saved="${s.id}" aria-label="Rename">${PEN}</button><button type="button" class="rfx-sv-x" data-del-saved="${s.id}" aria-label="Remove">${TRASH}</button></div>`).join('')
            : '<p class="rfx-muted" style="padding:.5rem">Nothing saved yet. Save a reading to keep it as it stands today.</p>';
        $('[data-rfx-drawer]').hidden = false;
        requestAnimationFrame(() => $('[data-rfx-drawer]').classList.add('is-on'));
    };
    $('[data-rfx-saved]').addEventListener('click', async (e) => {
        const ren = e.target.closest('[data-ren-saved]');
        if (ren) {
            // Its name, changed in place.
            const row = ren.closest('.rfx-sv');
            const id = Number(ren.dataset.renSaved);
            const cur = (S.opts.saved || []).find((x) => x.id === id);
            const openB = row.querySelector('.rfx-sv-open');
            openB.hidden = true;
            const inp = document.createElement('input');
            inp.className = 'rfx-sv-in';
            inp.maxLength = 190;
            inp.value = cur?.title || '';
            inp.setAttribute('aria-label', 'Name of this saved reading');
            row.insertBefore(inp, openB);
            inp.focus();
            inp.select();
            let done = false;
            const end = async (keep) => {
                if (done) return;
                done = true;
                if (keep && inp.value.trim() && inp.value.trim() !== cur?.title) await renameSaved(id, inp.value);
                openSaved();
            };
            inp.addEventListener('keydown', (ev) => { if (ev.key === 'Enter') { ev.preventDefault(); end(true); } else if (ev.key === 'Escape') { ev.stopPropagation(); end(false); } });
            inp.addEventListener('blur', () => end(true));
            return;
        }
        const del = e.target.closest('[data-del-saved]');
        if (del) {
            e.stopPropagation();
            try {
                const r = await window.api(U.saved + del.dataset.delSaved + '?scheduleId=' + SID, { method: 'DELETE' });
                S.opts.saved = r.data.saved;
                if (Number(del.dataset.delSaved) === S.savedId) { S.savedId = null; S.savedTitle = ''; paintFoot(); }
                paintSavedCount();
                openSaved();
            } catch (err) { window.toast?.(err.message, 'error'); }
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
            S.logic = { ...LOGIC_DEF, ...(d.params.logic || {}) };
            paintLots(); paintPicks(); paintTries(); paintLogic();
            S.savedId = d.id;
            S.savedTitle = d.title;
            paintFoot();
            $('[data-rfx-drawer]').classList.remove('is-on');
            if ((d.result.version || '') !== d.current) {
                // Saved with an older way of counting: counted again now, with the lot, checks and tries as saved.
                S.recounted = { title: d.title, at: d.at };
                run(true);
            } else {
                S.saved = { title: d.title, at: d.at };
                paint();
            }
            $('[data-rfx-scroll]').scrollTo({ top: 0, behavior: calm ? 'auto' : 'smooth' });
        } catch (err) { window.toast?.(err.message, 'error'); }
    });
})();
</script>
@endpush
@endonce
