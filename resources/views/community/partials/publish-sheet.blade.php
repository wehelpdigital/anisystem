{{-- Shared by the locked gate, the browse page and a plan's own page. --}}
<div class="sheet hidden" id="publishSheet" style="--sheet-width:30rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title">Share to the Community</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body space-y-4">
        <input type="hidden" id="publishScheduleId">
        <p class="text-sm text-gray-600">
            <strong class="text-gray-900" id="publishScheduleTitle"></strong> will be readable by every member.
            Workers, costs and post-harvest figures are not shared.
        </p>
        <div>
            <label class="form-label" for="publishSummary">What should people know about it?</label>
            <textarea id="publishSummary" class="form-textarea" rows="3" maxlength="500"
                placeholder="e.g. Wet-season inbred rice, 1.2 ha, direct seeded, low-input."></textarea>
            <p class="form-hint">Optional — shown on the browse card.</p>
        </div>
        <div>
            <label class="form-label" for="publishRegion">Where was it grown?</label>
            <input type="text" id="publishRegion" class="form-input" maxlength="120" placeholder="e.g. Nueva Ecija">
            <p class="form-hint">Optional — helps people find plans for similar conditions.</p>
        </div>
    </div>
    <div class="sheet-footer">
        <button type="button" class="btn btn-ghost" data-sheet-close>Cancel</button>
        <button type="button" class="btn btn-primary" id="publishConfirmBtn">Share plan</button>
    </div>
</div>
