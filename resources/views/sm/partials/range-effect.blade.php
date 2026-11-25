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
{{-- The field questions, for a what-if lot's answers (its dialogs sit in the browser's top layer, over the sheet). --}}
@include('partials.field-profile')
@php
    // In the Protocol Builder (2026-10-30, the owner: "this module should also be in the protocol builder"): the same sheet
    // on a protocol, @include('sm.partials.range-effect', ['schedule' => null, 'rfxProtocol' => $protocol]); its lots are
    // the farm's lots in Global Lots, its days a day zero picked here; its readings are saved with the protocol (no season).
    $rfxPb = $rfxProtocol ?? null;
    $rfxSid = $rfxPb ? 'pb' . (int) $rfxPb['id'] : (int) $schedule->id;
    $rfxU = $rfxPb ? [
        'pb' => true,
        'dayType' => strtoupper((string) ($rfxPb['dayType'] ?? 'DAS')),
        'options' => route('pb.coverage', (int) $rfxPb['id']),
        'run' => route('pb.coverage.run', (int) $rfxPb['id']),
        'products' => route('sm.effect.products') . '?pb=1',
        'lookup' => route('sm.effect.lookup'),
        'job' => url('/app/sm-range-effect/lookup') . '/',
        'save' => route('pb.coverage.save', (int) $rfxPb['id']), 'saved' => url('/app/protocol-builder/' . (int) $rfxPb['id'] . '/coverage/saved') . '/',
        'copyAnswers' => null, 'applyCheck' => null, 'apply' => null,
    ] : [
        'options' => route('sm.effect') . '?scheduleId=' . $schedule->id,
        'run' => route('sm.effect.run') . '?scheduleId=' . $schedule->id,
        'products' => route('sm.effect.products') . '?scheduleId=' . $schedule->id,
        'save' => route('sm.effect.save') . '?scheduleId=' . $schedule->id,
        'saved' => url('/app/sm-range-effect/saved') . '/',
        'lookup' => route('sm.effect.lookup') . '?scheduleId=' . $schedule->id,
        'job' => url('/app/sm-range-effect/lookup') . '/',
        'copyAnswers' => route('sm.effect.copyAnswers') . '?scheduleId=' . $schedule->id,
        'applyCheck' => route('sm.effect.applyCheck') . '?scheduleId=' . $schedule->id,
        'apply' => route('sm.effect.apply') . '?scheduleId=' . $schedule->id,
    ];
    // Granular and foliar, as the try box and the edit dialog both draw them.
    $rfxHowG = '<svg viewBox="0 0 32 32" aria-hidden="true"><path d="M3 25c4-2 8-2 13 0s9 2 13 0v4H3z" fill="#a8723f"/><circle cx="9" cy="13" r="3" fill="#e9d6b0" stroke="#8a5a2b" stroke-width="1.4"/><circle cx="17" cy="9" r="3" fill="#e9d6b0" stroke="#8a5a2b" stroke-width="1.4"/><circle cx="23" cy="16" r="3" fill="#e9d6b0" stroke="#8a5a2b" stroke-width="1.4"/><circle cx="14" cy="19" r="2.4" fill="#e9d6b0" stroke="#8a5a2b" stroke-width="1.4"/></svg>';
    $rfxHowF = '<svg viewBox="0 0 32 32" aria-hidden="true"><path d="M7 27c0-8 5-15 15-19-2 8-5 15-15 19z" fill="#86c25a" stroke="#3b7a1f" stroke-width="1.4" stroke-linejoin="round"/><path d="M7 27c4-5 7-9 12-14" fill="none" stroke="#3b7a1f" stroke-width="1.4" stroke-linecap="round"/><path d="M24 3c1.6 2.4 2.6 3.8 2.6 5a2.6 2.6 0 01-5.2 0c0-1.2 1-2.6 2.6-5z" fill="#38bdf8"/><path d="M28 11c1 1.5 1.6 2.4 1.6 3.1a1.6 1.6 0 01-3.2 0c0-.7.6-1.6 1.6-3.1z" fill="#38bdf8"/></svg>';
@endphp
<div class="rfx{{ $rfxPb ? ' is-pb' : '' }}" id="rfxModal" role="dialog" aria-modal="true" aria-labelledby="rfxTitle" hidden data-u='@json($rfxU)' data-sid="{{ $rfxSid }}">
    {{-- The calendar's application marks: granules spread, a spray, organic, a try. --}}
    <svg width="0" height="0" style="position:absolute" aria-hidden="true" focusable="false">
        <symbol id="rfxI-gran" viewBox="0 0 16 16"><circle cx="4.6" cy="6.2" r="2.3"/><circle cx="10.6" cy="4.8" r="2.1"/><circle cx="8.2" cy="10.9" r="2.5"/><circle cx="13" cy="10.4" r="1.5"/></symbol>
        <symbol id="rfxI-drop" viewBox="0 0 16 16"><path d="M8 1.5c2.8 3.6 4.5 5.9 4.5 8.1a4.5 4.5 0 01-9 0c0-2.2 1.7-4.5 4.5-8.1z"/></symbol>
        <symbol id="rfxI-leaf" viewBox="0 0 16 16"><path d="M2.5 13.5C2.5 7 6.6 3 13.8 2.2 13.4 9.4 9.4 13.5 2.5 13.5zm1.6-1.1c2.4-2.6 4.6-4.6 7.1-6.3-2.9 1.3-5.2 3.4-7.1 6.3z" fill-rule="evenodd"/></symbol>
        <symbol id="rfxI-star" viewBox="0 0 16 16"><path d="M8 1.3l2 4.3 4.6.5-3.5 3.1 1 4.6L8 11.5l-4.1 2.3 1-4.6L1.4 6.1 6 5.6z"/></symbol>
        {{-- A watering (a drop over the water), and the rain of a day. --}}
        <symbol id="rfxI-wave" viewBox="0 0 16 16"><path d="M8 1c2 2.6 3.2 4.3 3.2 5.9a3.2 3.2 0 01-6.4 0C4.8 5.3 6 3.6 8 1zM1 12.2c1.2-.8 2.3-.8 3.5 0s2.3.8 3.5 0 2.3-.8 3.5 0 2.3.8 3.5 0v2.3c-1.2.8-2.3.8-3.5 0s-2.3-.8-3.5 0-2.3.8-3.5 0-2.3-.8-3.5 0z"/></symbol>
        <symbol id="rfxI-rain" viewBox="0 0 16 16"><path d="M4.4 9.6a3 3 0 01.2-6 4 4 0 017.6 1.3 2.4 2.4 0 01-.4 4.7zM5.3 11h1.3l-1.1 3H4.2zM8.7 11H10l-1.1 3H7.6zM12.1 11h1.3l-1.1 3H11z"/></symbol>
    </svg>
    <div class="rfx-veil" data-rfx-close></div>
    <div class="rfx-box">
        <header class="rfx-head">
            <span class="rfx-logo" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4.5" width="18" height="16" rx="2.5"/><path d="M3 9.5h18M8 3v3M16 3v3"/><path d="M6.5 15.5c1.6-3.2 3.4-3.2 5 0s3.4 3.2 5 0" stroke-width="2.2"/></svg>
            </span>
            <div class="rfx-head-t">
                {{-- Called the Coverage Planner since 2026-10-30 (the owner: "change the modal title to Coverage Planner"). --}}
                <h2 id="rfxTitle">Coverage Planner</h2>
                <p>@if ($rfxPb){{ $rfxPb['title'] }}: how long everything it puts on keeps working, against what the crop needs each day.@else How long everything you put on keeps working, against what your crop needs each day.@endif</p>
            </div>
            <button type="button" class="rfx-hbtn" data-rfx-saved-open><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 4h14v17l-7-4-7 4V4z"/></svg><span>Saved</span><b data-rfx-saved-n hidden></b></button>
            <button type="button" class="rfx-x" data-rfx-close aria-label="Close"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true"><path stroke-linecap="round" d="M6 6l12 12M18 6L6 18"/></svg></button>
        </header>

        <div class="rfx-scroll" data-rfx-scroll>
            {{-- ================= THE CONTROLS ================= --}}
            <section class="rfx-controls">
                <div class="rfx-row">
                    <span class="rfx-label">{{ $rfxPb ? 'Its lot' : 'Lot' }}</span>
                    {{-- One tag for the lot read, a window to pick another (the owner, 2026-10-30: "lot selection is cut off in
                         desktop if its too long lots of lots to select. make it a taglike button with modal box to select lot"). --}}
                    <div class="rfx-lotpick" data-rfx-lots></div>
                </div>
                @if ($rfxPb)
                {{-- The protocol's days on the calendar: its day zero (and the transplant of a DAT protocol), and the area its
                     amounts are written for. --}}
                <div class="rfx-row is-sub">
                    <span class="rfx-label">Its days</span>
                    <div class="rfx-pbdays">
                        <div class="rfx-when"><span>{{ strtoupper((string) ($rfxPb['dayType'] ?? 'DAS')) === 'DAP' ? 'Planted on (DAP 0)' : 'Sown on (DAS 0)' }}</span><button type="button" class="rfx-datetag" data-date-for="data-rfx-pb-start"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><rect x="3.5" y="5" width="17" height="15.5" rx="2.5"/><path stroke-linecap="round" d="M3.5 10h17M8 3v4M16 3v4"/></svg><b data-date-say>Pick a day</b><svg class="rfx-dt-c" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/></svg></button><input type="date" class="rfx-date-in" data-rfx-pb-start tabindex="-1" aria-label="Day zero"></div>
                        @if (strtoupper((string) ($rfxPb['dayType'] ?? '')) === 'DAT')
                        <div class="rfx-when"><span>Transplanted on (DAT 0)</span><button type="button" class="rfx-datetag" data-date-for="data-rfx-pb-tr"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><rect x="3.5" y="5" width="17" height="15.5" rx="2.5"/><path stroke-linecap="round" d="M3.5 10h17M8 3v4M16 3v4"/></svg><b data-date-say>Pick a day</b><svg class="rfx-dt-c" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/></svg></button><input type="date" class="rfx-date-in" data-rfx-pb-tr tabindex="-1" aria-label="Transplant day"></div>
                        @endif
                        <label class="rfx-pbha"><span>Its amounts are for</span><span><input type="number" min="0.01" step="any" inputmode="decimal" data-rfx-pb-ha value="1"> ha</span></label>
                    </div>
                </div>
                @endif
                <div class="rfx-row">
                    <span class="rfx-label">Check</span>
                    <div class="rfx-tabs" role="tablist" data-rfx-tabs>
                        <button type="button" role="tab" data-tab="nut" class="is-on">🧪 Nutrients</button>
                        <button type="button" role="tab" data-tab="micro">✨ Micros</button>
                        <button type="button" role="tab" data-tab="prot">🛡️ Protection</button>
                        <button type="button" role="tab" data-tab="pest">🐛 A pest or disease</button>
                        {{-- Water (2026-10-30, the owner: "add range coverage for irrigation where plant gets stress days after irrigating"). --}}
                        <button type="button" role="tab" data-tab="water">💧 Water</button>
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
                        {{-- Its own button, first, so a long list never pushes it away. It opens the picker. --}}
                        <div class="rfx-tries-b">
                            <button type="button" class="rfx-add" data-rfx-try-open><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true"><path stroke-linecap="round" d="M12 5v14M5 12h14"/></svg>A product you plan to apply</button>
                            {{-- Onto the plan (the owner, 2026-10-30): each one on its day, into the lot's tasks, never twice. --}}
                            <button type="button" class="rfx-add is-plan" data-rfx-apply hidden>@if ($rfxPb)<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><rect x="3.5" y="5" width="17" height="15.5" rx="2.5"/><path stroke-linecap="round" d="M3.5 10h17M8 3v4M16 3v4"/></svg>Put them in the protocol @else<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><rect x="3.5" y="5" width="17" height="15.5" rx="2.5"/><path stroke-linecap="round" stroke-linejoin="round" d="M3.5 10h17M8 3v4M16 3v4M8.5 15l2.3 2.3 4.7-4.6"/></svg>Put them on the plan @endif</button>
                        </div>
                        {{-- How the list is grouped (the owner, 2026-10-30: "have options to group by date of application, group
                             by fertilizer type, or group by fertilizer"), shown once there are two to group. --}}
                        <div class="rfx-gby" role="radiogroup" aria-label="Group them by" data-rfx-gby data-on="type" hidden>
                            <span class="rfx-gby-l">Group by</span>
                            <span class="rfx-gby-s"><i class="rfx-gby-pill" aria-hidden="true"></i>
                                <button type="button" role="radio" data-gby="date" aria-checked="false">📅 Date applied</button>
                                <button type="button" role="radio" data-gby="type" aria-checked="true">🧺 Type</button>
                                <button type="button" role="radio" data-gby="name" aria-checked="false">🏷️ Fertilizer</button>
                            </span>
                        </div>
                        {{-- Grouped by kind, each kind by date (the owner, 2026-10-30); or by the day, or by the product. --}}
                        <div class="rfx-tgs" data-rfx-tries></div>
                        {{-- What the plan puts on already (the owner, 2026-11-02: a saved reading opened with its fertilizers in
                             the results and none listed here): the protocol's or the season's own, folded, so this list says
                             everything the reading counts. Changed where they live, not here. --}}
                        <div class="rfx-pick-added rfx-plan" data-rfx-plan></div>
                    </div>
                </div>
                {{-- The waterings planned here, shown while water is checked: each on its day, how much and how it goes on. --}}
                <div class="rfx-row is-sub" data-rfx-wrow hidden>
                    <span class="rfx-label">Watering</span>
                    <div class="rfx-tries">
                        <div class="rfx-tries-b">
                            <button type="button" class="rfx-add is-water" data-rfx-wat-open><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true"><path stroke-linecap="round" d="M12 5v14M5 12h14"/></svg>A watering you plan</button>
                        </div>
                        <div class="rfx-chips is-wrap" data-rfx-wtries></div>
                    </div>
                </div>
            </section>

            {{-- ================= THE READING ================= --}}
            <section class="rfx-body" data-rfx-body aria-live="polite">
                <div class="rfx-loading" data-rfx-loading><span></span><span></span><span></span></div>
            </section>
        </div>

        <footer class="rfx-foot">
            <span class="rfx-foot-note" data-rfx-note>{{ $rfxPb ? 'Counts what each task of the protocol applies, on its day of the count, from the days picked above.' : 'Counts the products written on the tasks of each day, from your shed or typed in, on the day they are used.' }}</span>
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
        {{-- The picker (the owner, 2026-10-30: "when adding a fertilizer using the + a product you plan to apply, show a
             module for the search of the fertilizer in the db and have already selected items in it and tabs on the type
             for easier searching"): what is added so far, a tab a kind, the search, then the day, how and how much. --}}
        <div class="rfx-edit rfx-pickm" data-rfx-try hidden>
            <div class="rfx-edit-veil" data-rfx-try-x></div>
            <div class="rfx-edit-c" role="dialog" aria-modal="true" aria-labelledby="rfxPickT">
                <div class="rfx-day-h"><div><b id="rfxPickT">Products you plan to apply</b><small>Find each one, pick its day, how it goes on and how much. Add as many as you like.</small></div><button type="button" class="rfx-x" data-rfx-try-x aria-label="Close"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true"><path stroke-linecap="round" d="M6 6l12 12M18 6L6 18"/></svg></button></div>
                <div class="rfx-pick-added" data-rfx-pick-added></div>
                <div class="rfx-ptabs" role="tablist" aria-label="Kinds of product" data-rfx-ptabs></div>
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
                        <button type="button" class="rfx-btn is-ghost" data-rfx-try-cancel>Back to the list</button>
                        <button type="button" class="rfx-btn is-go" data-rfx-try-add>Add it</button>
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
                <div class="rfx-pick-foot"><span data-rfx-pick-n></span><button type="button" class="rfx-btn is-go" data-rfx-try-x>Done</button></div>
            </div>
        </div>
        {{-- A what-if lot (the owner, 2026-10-30: "have a custom button just to see if what it will look in a custom soil"):
             a lot's crop, days and tasks with its own name, place and every field question, never saved as a lot. --}}
        <div class="rfx-edit rfx-whatm" data-rfx-what hidden>
            <div class="rfx-edit-veil" data-rfx-what-x></div>
            <div class="rfx-edit-c" role="dialog" aria-modal="true" aria-labelledby="rfxWhatT" data-rfx-what-c></div>
        </div>
        {{-- The lots, to pick the one read: a search once there are many, then the what-if lot. --}}
        <div class="rfx-edit rfx-lotm" data-rfx-lotm hidden>
            <div class="rfx-edit-veil" data-rfx-lotm-x></div>
            <div class="rfx-edit-c" role="dialog" aria-modal="true" aria-labelledby="rfxLotT">
                <div class="rfx-day-h"><div><b id="rfxLotT">{{ $rfxPb ? 'The lot it is for' : 'Pick a lot' }}</b><small>{{ $rfxPb ? 'Its soil, water and place are what the reading counts from. A lot of Global Lots, no lot, or a what-if lot of your own.' : 'The calendar reads one lot at a time: its crop, its days, its soil and the products on its tasks.' }}</small></div><button type="button" class="rfx-x" data-rfx-lotm-x aria-label="Close"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true"><path stroke-linecap="round" d="M6 6l12 12M18 6L6 18"/></svg></button></div>
                <label class="rfx-search" data-rfx-lot-s hidden>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path stroke-linecap="round" d="M20 20l-3.5-3.5"/></svg>
                    <input type="search" data-rfx-lot-q placeholder="Find a lot by its name, crop or place" autocomplete="off" aria-label="Find a lot">
                </label>
                <div class="rfx-lotl" data-rfx-lotl></div>
                <div class="rfx-lotw" data-rfx-lotw></div>
            </div>
        </div>
        {{-- The planned products onto the season's plan: what each would do first, then done. --}}
        <div class="rfx-edit rfx-applym" data-rfx-apply-box hidden>
            <div class="rfx-edit-veil" data-rfx-apply-x></div>
            <div class="rfx-edit-c" role="dialog" aria-modal="true" aria-labelledby="rfxApplyT" data-rfx-apply-c></div>
        </div>
        {{-- A watering planned here: its day, how much, and how it goes on (a paddy is flooded). --}}
        <div class="rfx-edit rfx-watm" data-rfx-wat hidden>
            <div class="rfx-edit-veil" data-rfx-wat-x></div>
            <div class="rfx-edit-c" role="dialog" aria-modal="true" aria-labelledby="rfxWatT">
                <div class="rfx-day-h"><div><b id="rfxWatT" data-rfx-wat-t>A watering you plan</b><small>Pick its day, how much and how it goes on. The planner counts how long it keeps the crop out of stress.</small></div><button type="button" class="rfx-x" data-rfx-wat-x aria-label="Close"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true"><path stroke-linecap="round" d="M6 6l12 12M18 6L6 18"/></svg></button></div>
                <div class="rfx-picked-f">
                    <div class="rfx-when"><span>Water on</span><button type="button" class="rfx-datetag" data-date-for="data-rfx-wat-date"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><rect x="3.5" y="5" width="17" height="15.5" rx="2.5"/><path stroke-linecap="round" d="M3.5 10h17M8 3v4M16 3v4"/></svg><b data-date-say>Pick a day</b><svg class="rfx-dt-c" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/></svg></button><input type="date" class="rfx-date-in" data-rfx-wat-date tabindex="-1" aria-label="Water on"></div>
                    <label><span data-rfx-wat-ql>How much water <i>(optional)</i></span><span class="rfx-amt"><input type="number" min="0" step="any" inputmode="decimal" data-rfx-wat-qty placeholder="e.g. 5"><select data-rfx-wat-unit><option value="cm">cm</option><option value="mm">mm</option></select></span></label>
                </div>
                <div class="rfx-wmeth" role="radiogroup" aria-label="How the water goes on" data-rfx-wat-how></div>
                <p class="rfx-pick-hint" data-rfx-wat-hint></p>
                <div class="rfx-edit-b">
                    <button type="button" class="rfx-btn is-ghost" data-rfx-wat-dupe hidden>Make a copy</button>
                    <button type="button" class="rfx-btn is-ghost is-del" data-rfx-wat-del hidden>Take it out</button>
                    <button type="button" class="rfx-btn is-ghost" data-rfx-wat-x>Cancel</button>
                    <button type="button" class="rfx-btn is-go" data-rfx-wat-save>Add it</button>
                </div>
            </div>
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
                <div class="rfx-day-h"><div><b id="rfxEditT">Change this fertilizer</b><small data-rfx-edit-sub>Its day, how it goes on, and how much</small></div><button type="button" class="rfx-x" data-rfx-edit-x aria-label="Close"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true"><path stroke-linecap="round" d="M6 6l12 12M18 6L6 18"/></svg></button></div>
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
                    <button type="button" class="rfx-btn is-ghost" data-rfx-edit-dupe><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><rect x="8.5" y="8.5" width="11" height="11" rx="2.4"/><path stroke-linecap="round" stroke-linejoin="round" d="M15.5 8.5V6.6a2.1 2.1 0 00-2.1-2.1H6.6a2.1 2.1 0 00-2.1 2.1v6.8a2.1 2.1 0 002.1 2.1h1.9"/></svg>Make a copy</button>
                    <button type="button" class="rfx-btn is-ghost is-del" data-rfx-edit-del><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 7h12M9 7V5h6v2M8 7l1 12h6l1-12"/></svg><span data-rfx-edit-del-t>Take it out</span></button>
                    <button type="button" class="rfx-btn is-ghost" data-rfx-edit-x>Cancel</button>
                    <button type="button" class="rfx-btn is-go" data-rfx-edit-save>Save changes</button>
                </div>
            </div>
        </div>
    </div>
</div>

@once
@push('styles')
@vite('resources/css/range-effect.css')
@endpush
@push('scripts')
<script>
window.__RFX = {
    x_happy: @json(e(\App\Support\AneeEmoji::url('happy'))),
    x_thinking: @json(e(\App\Support\AneeEmoji::url('thinking'))),
};
</script>
<script src="{{ \Illuminate\Support\Facades\Vite::asset('resources/js/range-effect.js') }}"></script>
@endpush
@endonce
