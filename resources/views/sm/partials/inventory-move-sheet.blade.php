{{-- Moving stock, in one sheet.

     Four errands share it — put some in, take some out, spend some against a
     day on the board, log a delivery against a day on the board — because
     they are one act with a sign on it and a date. Four sheets would be four
     places that have to remember to ask the same three questions.

     Opened with window.ivOpenMove({ direction, itemId, date, title }).
     Shared by the Inventory module and the activities board's day menu, so
     the partial is guarded against being included twice. --}}
@once
<div class="sheet hidden" id="ivMoveSheet" style="--sheet-width:28rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title" id="ivMoveTitle">Take some out</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body space-y-3">
        <input type="hidden" id="ivMoveDir" value="out">

        <div>
            <label for="ivMoveItem" class="form-label">Which item?</label>
            <select id="ivMoveItem" class="form-select"></select>
            {{-- What is left, right under the choice, because the next thing
                 typed is a number and this is what it has to fit inside. --}}
            <p class="form-hint" id="ivMoveHave"></p>
        </div>

        {{-- SOMETHING NOT ON THE SHELF YET.
             Chosen from the picker above, and only when stock is coming in —
             a delivery of something the shed has never held before is an
             ordinary Tuesday, and it used to be the one thing this sheet could
             not record. The three questions are the same three the item form
             asks, because it is the same item. --}}
        <div id="ivMoveNewWrap" class="hidden rounded-xl border border-dashed border-gray-300 p-3 space-y-2.5">
            <div>
                <label for="ivMoveNewName" class="form-label text-xs! mb-1!">What is it? <span class="text-red-500">*</span></label>
                <input type="text" id="ivMoveNewName" maxlength="150" class="form-input bg-white!" placeholder="e.g. Urea 46-0-0" autocomplete="off">
            </div>
            <div class="iv-newrow">
                <div>
                    <label for="ivMoveNewKind" class="form-label text-xs! mb-1!">Kind</label>
                    <select id="ivMoveNewKind" class="form-select bg-white!">
                        @foreach (\App\Models\AsInventoryItem::KINDS as $key => $k)
                            <option value="{{ $key }}">{{ $k['icon'] }} {{ $k['label'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="ivMoveNewUnit" class="form-label text-xs! mb-1!">Counted in</label>
                    <select id="ivMoveNewUnit" class="form-select bg-white!"></select>
                </div>
            </div>
            <div>
                <label for="ivMoveNewPrice" class="form-label text-xs! mb-1!">Price <span class="text-gray-400 font-normal">(optional)</span></label>
                <div class="relative">
                    <input type="number" id="ivMoveNewPrice" min="0" step="any" class="form-input bg-white!" placeholder="0.00" inputmode="decimal">
                    <span class="iv-qty-u" id="ivMoveNewPriceUnit"></span>
                </div>
                {{-- Stored on the item, spent by the report: expenses will
                     multiply this by what the moves say was used. --}}
            </div>
        </div>

        <div>
            <label for="ivMoveQty" class="form-label">How much? <span class="text-red-500">*</span></label>
            {{-- The amount, in whatever unit it was measured. A bag-counted
                 item takes kilos, a litre-counted one takes ml: the picker
                 holds the item's kin and the book converts. When a unit has
                 no kin, the picker gives way to a plain suffix. --}}
            <div class="iv-qtyrow">
                <div class="relative flex-1">
                    <input type="number" id="ivMoveQty" min="0" step="any" class="form-input" placeholder="0" inputmode="decimal">
                    <span class="iv-qty-u" id="ivMoveUnit"></span>
                </div>
                <select id="ivMoveUnitSel" class="form-select hidden iv-unit-sel" aria-label="Unit of the amount"></select>
            </div>
            <p class="form-hint" id="ivMovePacks"></p>
        </div>

        <div id="ivMoveDateWrap">
            <label class="form-label">When?</label>
            {{-- A tag that raises the real datepicker, like the lot form's
                 counters: the date reads as a fact wearing a face, and the
                 input underneath stays the value everything else reads. --}}
            <div class="relative">
                <button type="button" class="crop-tag" id="ivMoveDateBtn">
                    <span class="crop-tag-e">📅</span>
                    <span class="crop-tag-t" id="ivMoveDateNow">Today</span>
                    <svg class="crop-tag-c" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/></svg>
                </button>
                <input type="date" id="ivMoveDate" class="iv-start-date" tabindex="-1" aria-label="When">
            </div>
        </div>

        {{-- WHEN THE BOOK BEGINS.
             Shown instead of the plain date for a FIRST count on a board that
             already has ticked activities: that number needs a day, because
             the season has a past and the past spent things. A tag that opens
             a chooser, like the crop and the day counter on the lot form. --}}
        <div id="ivStartWrap" class="hidden">
            <label class="form-label">Counted from</label>
            <button type="button" class="crop-tag" id="ivStartBtn">
                <span class="crop-tag-e" id="ivStartIcon">🗓️</span>
                <span class="crop-tag-t" id="ivStartNow">Today</span>
                <svg class="crop-tag-c" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/></svg>
            </button>
            <p class="form-hint" id="ivStartHint"></p>
        </div>

        <div>
            <label for="ivMoveNote" class="form-label">Note <span class="text-gray-400 font-normal">(optional)</span></label>
            <textarea id="ivMoveNote" rows="2" maxlength="500" class="form-textarea" placeholder="What it went on, who delivered it, anything worth remembering"></textarea>
        </div>

        <p class="iv-move-warn hidden" id="ivMoveWarn"></p>
    </div>
    <div class="sheet-footer">
        <button type="button" class="btn btn-ghost" data-sheet-close>Cancel</button>
        <button type="button" id="ivMoveGo" class="btn btn-primary">Save</button>
    </div>
</div>

{{-- EDITING ONE HAND-TYPED LINE: the amount (in any kin unit), the day, the
     note. The Start never lands here — its date decides what the season
     retro-spends, so it keeps its own editor. --}}
<div class="sheet hidden" id="ivMoveEditSheet" style="--sheet-width:26rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title">Edit this entry</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body space-y-3">
        <input type="hidden" id="ivMEdId">
        <p class="form-hint mt-0!" id="ivMEdWhat"></p>
        <div>
            <label for="ivMEdQty" class="form-label">How much? <span class="text-red-500">*</span></label>
            <div class="iv-qtyrow">
                <div class="relative flex-1">
                    <input type="number" id="ivMEdQty" min="0" step="any" class="form-input" placeholder="0" inputmode="decimal">
                    <span class="iv-qty-u" id="ivMEdUnit"></span>
                </div>
                <select id="ivMEdUnitSel" class="form-select hidden iv-unit-sel" aria-label="Unit of the amount"></select>
            </div>
        </div>
        <div>
            <label class="form-label">When?</label>
            <div class="relative">
                <button type="button" class="crop-tag" id="ivMEdDateBtn">
                    <span class="crop-tag-e">📅</span>
                    <span class="crop-tag-t" id="ivMEdDateNow">Today</span>
                    <svg class="crop-tag-c" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/></svg>
                </button>
                <input type="date" id="ivMEdDate" class="iv-start-date" tabindex="-1" aria-label="When">
            </div>
        </div>
        <div>
            <label for="ivMEdNote" class="form-label">Note <span class="text-gray-400 font-normal">(optional)</span></label>
            <textarea id="ivMEdNote" rows="2" maxlength="500" class="form-textarea" placeholder="What it went on, who delivered it"></textarea>
        </div>
    </div>
    <div class="sheet-footer">
        <button type="button" class="btn btn-ghost" data-sheet-close>Cancel</button>
        <button type="button" id="ivMEdGo" class="btn btn-primary">Save</button>
    </div>
</div>

{{-- THE SAME AMOUNT, IN EVERY UNIT OF ITS KIND.
     Opened by tapping any quantity that has kin: the book's figure leads,
     and each row below is the identical amount wearing a different unit. --}}
<div class="sheet hidden" id="ivConvSheet" style="--sheet-width:24rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <div class="min-w-0">
            <h3 class="sheet-title" id="ivConvTitle">Amount</h3>
            <p class="text-xs text-gray-400">The same amount, said in its other units.</p>
        </div>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full shrink-0" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body">
        <div id="ivConvRows"></div>
    </div>
</div>

{{-- ONE ITEM'S MENU — the four verbs behind the card's ⋮.
     Each row carries its sentence, because "edit" and "add stock" are
     different errands and the difference is worth a line. --}}
<div class="sheet hidden" id="ivMenuSheet" style="--sheet-width:26rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <div class="min-w-0">
            <h3 class="sheet-title truncate" id="ivMenuTitle">Item</h3>
            <p class="text-xs text-gray-400" id="ivMenuSays"></p>
        </div>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full shrink-0" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body">
        <div class="dt-rows">
            <button type="button" class="dt-row" data-iv-menu-act="edit">
                <span class="dt-row-e">✏️</span>
                <span class="dt-row-body"><b>Edit current</b><i>Name, kind, unit, price, the low-stock warning.</i></span>
            </button>
            <button type="button" class="dt-row" data-iv-menu-act="in">
                <span class="dt-row-e">📥</span>
                <span class="dt-row-body"><b>Add inventory of this</b><i>A delivery, a purchase, a carry-over — the count goes up.</i></span>
            </button>
            <button type="button" class="dt-row" data-iv-menu-act="out">
                <span class="dt-row-e">📤</span>
                <span class="dt-row-body"><b>Subtract inventory of this</b><i>Used or lost outside an activity — the count goes down.</i></span>
            </button>
            <button type="button" class="dt-row" data-iv-menu-act="delete">
                <span class="dt-row-e">🗑️</span>
                <span class="dt-row-body"><b>Delete this</b><i>Off the shed's list. Its log lines stay.</i></span>
            </button>
        </div>
    </div>
</div>

{{-- WHEN COUNTING BEGINS — the chooser the tag opens.
     Three answers, each with its own sentence, because the sentence is the
     point: what happens to the activities already ticked done is decided
     here, and it should be read at the moment it is decided. --}}
<div class="sheet hidden" id="ivStartSheet" style="--sheet-width:30rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title">When does counting begin?</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body">
        <div class="dt-rows" id="ivStartRows">
            <button type="button" class="dt-row" data-start="today">
                <span class="dt-row-e">🗓️</span>
                <span class="dt-row-body">
                    <b>Today onward</b>
                    <i>The count starts now. Activities ticked done before today are not taken from it.</i>
                </span>
                <svg class="dt-row-tick hidden" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
            </button>
            <button type="button" class="dt-row" data-start="date">
                <span class="dt-row-e">📅</span>
                <span class="dt-row-body">
                    <b>From a day I pick</b>
                    <i>Activities ticked done on or after that day come off the count automatically.</i>
                </span>
                {{-- The picker itself, reached from the row rather than shown
                     as a field: showPicker() where the browser has it, the
                     bare input where it does not. --}}
                <input type="date" id="ivStartDateInput" class="iv-start-date" tabindex="-1" aria-label="Start date">
                <svg class="dt-row-tick hidden" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
            </button>
            <button type="button" class="dt-row" data-start="beginning">
                <span class="dt-row-e">⏮️</span>
                <span class="dt-row-body">
                    <b>From the season's beginning</b>
                    <i id="ivStartBeginningSays">Everything already ticked done that used this item comes off the count.</i>
                </span>
                <svg class="dt-row-tick hidden" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
            </button>
        </div>
        <p class="form-hint mt-3">What the season has already used is worked out from the day you choose — the log will show each activity's take as its own line.</p>
    </div>
</div>

{{-- MOVING THE START. The log's Start line opens this: a different amount, a
     different day, or both — and the season is recalculated from the new
     answer. Hand-typed deliveries and uses survive; they happened regardless
     of where the book opens. --}}
<div class="sheet hidden" id="ivStartEditSheet" style="--sheet-width:26rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title">Move the start</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body space-y-3">
        <input type="hidden" id="ivStartEditItem" value="">
        <p class="form-hint" id="ivStartEditWhat"></p>
        <div>
            <label for="ivStartEditQty" class="form-label">Started with <span class="text-red-500">*</span></label>
            <div class="relative">
                <input type="number" id="ivStartEditQty" min="0" step="any" class="form-input" inputmode="decimal">
                <span class="iv-qty-u" id="ivStartEditUnit"></span>
            </div>
        </div>
        <div>
            <label class="form-label">Counted from</label>
            <button type="button" class="crop-tag" id="ivStartEditBtn">
                <span class="crop-tag-e" id="ivStartEditIcon">🗓️</span>
                <span class="crop-tag-t" id="ivStartEditNow">Today</span>
                <svg class="crop-tag-c" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/></svg>
            </button>
        </div>
        <p class="form-hint">The season is recalculated from the new answer: activities ticked done from that day take from the count, earlier ones do not. Deliveries and uses you typed by hand stay as they are.</p>
    </div>
    <div class="sheet-footer">
        <button type="button" class="btn btn-ghost" data-sheet-close>Cancel</button>
        <button type="button" id="ivStartEditGo" class="btn btn-primary">Recalculate</button>
    </div>
</div>

<style>
    /* THE TAG — the lot form's crop-tag family, re-declared for pages that
       never load the lot form. Same names, one house style. */
    .crop-tag { display: flex; align-items: center; gap: .5rem; width: 100%;
        padding: .55rem .7rem; border-radius: .75rem; cursor: pointer; text-align: left;
        border: 1px solid var(--color-gray-200); background: var(--color-white);
        transition: border-color .28s cubic-bezier(.22,1,.36,1), background .28s cubic-bezier(.22,1,.36,1); }
    .crop-tag:hover { border-color: var(--color-brand-300); background: var(--color-brand-50); }
    .crop-tag-e { font-size: 1.1rem; line-height: 1; flex: none; }
    .crop-tag-t { flex: 1 1 auto; min-width: 0; font-size: .9rem; font-weight: 700; color: #3d6823;
        overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .crop-tag-c { width: 1rem; height: 1rem; flex: none; color: var(--color-gray-400); }
    html.dark .crop-tag { background: #1c2416; border-color: #2b3a1c; }
    @media (prefers-reduced-motion: reduce) { .crop-tag { transition: none; } }

    /* THE CHOOSER'S ROWS — the lot form's dt-row family, re-declared here
       because this partial is also loaded by pages that never load the lot
       form. Same names, same look, one house style. */
    .dt-rows { display: flex; flex-direction: column; gap: .4rem; }
    .dt-row { display: flex; align-items: flex-start; gap: .6rem; width: 100%; text-align: left;
        padding: .6rem .7rem; border-radius: .75rem; cursor: pointer; position: relative;
        border: 1px solid var(--color-gray-200); background: var(--color-white); }
    .dt-row:hover { border-color: var(--color-brand-300); background: var(--color-brand-50); }
    .dt-row.is-on { border-color: var(--color-brand-500); background: var(--color-brand-50); }
    .dt-row-e { font-size: 1.1rem; line-height: 1.3; flex: none; }
    .dt-row-body { flex: 1 1 auto; min-width: 0; }
    .dt-row-body b { display: block; font-size: .88rem; font-weight: 800; color: var(--color-gray-900); }
    .dt-row-body i { display: block; font-style: normal; font-size: .75rem; color: var(--color-gray-500); margin-top: .1rem; }
    .dt-row-tick { width: 1.1rem; height: 1.1rem; flex: none; color: var(--color-brand-600); margin-top: .15rem; }
    html.dark .dt-row { background: #1c2417; border-color: #2f3a26; }
    html.dark .dt-row-body b { color: #e8efe1; }
    /* The date input hides under its row; showPicker() raises the real
       calendar from it. Kept in the layout (not display:none) because some
       browsers refuse to open a picker for an element that has no box. */
    .iv-start-date { position: absolute; inset: 0; width: 100%; height: 100%;
        opacity: 0; pointer-events: none; }

    .iv-qty-u { position: absolute; right: .8rem; top: 50%; transform: translateY(-50%);
        font-size: .78rem; font-weight: 700; color: var(--color-gray-400); pointer-events: none;
        max-width: 45%; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .iv-newrow { display: grid; grid-template-columns: 1fr 1fr; gap: .5rem; }
    .iv-qtyrow { display: flex; gap: .5rem; align-items: stretch; }
    /* A figure that can translate itself says so quietly. */
    .iv-convable { cursor: pointer; text-decoration: underline dotted; text-underline-offset: 3px;
        text-decoration-color: var(--color-gray-300); }
    .iv-convable:hover { text-decoration-color: currentColor; }
    .iv-conv-row { display: flex; justify-content: space-between; gap: .8rem; padding: .55rem .2rem;
        border-bottom: 1px dashed var(--color-gray-100); font-size: .9rem; }
    .iv-conv-row:last-child { border-bottom: 0; }
    .iv-conv-row b { font-weight: 800; color: var(--color-gray-900); font-variant-numeric: tabular-nums; }
    .iv-conv-row span { color: var(--color-gray-500); }
    html.dark .iv-conv-row b { color: #e8efe1; }
    .iv-unit-sel { flex: 0 0 auto; width: auto; max-width: 11rem; }
    html.dark #ivMoveNewWrap { border-color: #3a4630; }
    /* Said, not refused. A farm can run into the negative on paper — the bag
       in the shed was opened last week and nobody wrote it down — and a form
       that will not let somebody record what actually happened just gets
       worked around. */
    .iv-move-warn { font-size: .78rem; font-weight: 600; color: #b45309;
        background: #fffbeb; border: 1px solid #fde68a; border-radius: .7rem; padding: .55rem .7rem; }
</style>
@endonce
