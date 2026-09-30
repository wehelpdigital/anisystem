{{-- Opened from a plan's own page, where a member chooses to share it. --}}
<div class="sheet hidden" id="publishSheet" style="--sheet-width:30rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title">Share to the Community</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body space-y-4">
        <input type="hidden" id="publishScheduleId">
        <p class="text-sm text-gray-600">
            All members can read <strong class="text-gray-900" id="publishScheduleTitle"></strong>.
            Workers, costs and notes stay private.
        </p>
        <div>
            <label class="form-label" for="publishSummary">Short summary</label>
            <textarea id="publishSummary" class="form-textarea" rows="3" maxlength="500"
                placeholder="e.g. Wet season inbred rice, 1.2 ha, direct seeded, low input."></textarea>
            <p class="form-hint">Optional. Shown on the plan card.</p>
        </div>
        <div>
            <label class="form-label" for="publishRegion">Where it was grown</label>
            <input type="text" id="publishRegion" class="form-input" maxlength="120" placeholder="{{ \App\Support\Region::address()['region']['placeholder'] ?? 'e.g. Illinois' }}">
            <p class="form-hint">Optional. Helps others find plans like yours.</p>
        </div>
    </div>
    <div class="sheet-footer">
        <button type="button" class="btn btn-ghost" data-sheet-close>Cancel</button>
        <button type="button" class="btn btn-primary" id="publishConfirmBtn">Share plan</button>
    </div>
</div>
