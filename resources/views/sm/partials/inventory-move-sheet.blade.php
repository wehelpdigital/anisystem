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
        </div>

        <div>
            <label for="ivMoveQty" class="form-label">How much? <span class="text-red-500">*</span></label>
            <div class="relative">
                <input type="number" id="ivMoveQty" min="0" step="any" class="form-input" placeholder="0" inputmode="decimal">
                <span class="iv-qty-u" id="ivMoveUnit"></span>
            </div>
            <p class="form-hint" id="ivMovePacks"></p>
        </div>

        <div id="ivMoveDateWrap">
            <label for="ivMoveDate" class="form-label">When?</label>
            <input type="date" id="ivMoveDate" class="form-input">
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

<style>
    .iv-qty-u { position: absolute; right: .8rem; top: 50%; transform: translateY(-50%);
        font-size: .78rem; font-weight: 700; color: var(--color-gray-400); pointer-events: none;
        max-width: 45%; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .iv-newrow { display: grid; grid-template-columns: 1fr 1fr; gap: .5rem; }
    html.dark #ivMoveNewWrap { border-color: #3a4630; }
    /* Said, not refused. A farm can run into the negative on paper — the bag
       in the shed was opened last week and nobody wrote it down — and a form
       that will not let somebody record what actually happened just gets
       worked around. */
    .iv-move-warn { font-size: .78rem; font-weight: 600; color: #b45309;
        background: #fffbeb; border: 1px solid #fde68a; border-radius: .7rem; padding: .55rem .7rem; }
</style>
@endonce
