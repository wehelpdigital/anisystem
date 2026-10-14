{{-- A whole catalogue in a full screen sheet (2026-10-14): the pests, the
     diseases or the weeds of every crop, searched by any name, narrowed by
     a crop tag and a kind (or group) tag, each card opening its entry in the
     same sheet, with a way back. The cards and the entries are drawn by
     FH.catalogue (field-helpers.partials.kit) from the page's data; this is
     the frame.

     Expects $id, $title, $sub (the line under the title), $placeholder,
     $crops (the crop tag's crops, partials.crop-pick's shape), $nouns,
     $allLine (the crop tag's first row), $facetAll and $facetTitle. --}}
<dialog class="fhm" id="{{ $id }}" aria-label="{{ $title }}">
    <div class="fhm-bar">
        <button type="button" class="fhm-back" aria-label="Back to the catalogue" hidden><svg fill="none" stroke="currentColor" stroke-width="2.6" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg></button>
        <div class="fhm-title"><b data-fhm-title>{{ $title }}</b><small data-fhm-sub>{{ $sub }}</small></div>
        <button type="button" class="fhm-x" data-fhm-close aria-label="Close"><svg fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" d="M6 6l12 12M18 6L6 18"/></svg></button>
    </div>
    <div class="fhm-panes">
        <section class="fhm-pane fhm-list" aria-label="{{ $title }}">
            <div class="fhm-in">
                <div class="fhc-tools">
                    <label class="fhc-search">
                        <svg fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="6.5"/><path stroke-linecap="round" d="M20 20l-4.2-4.2"/></svg>
                        <span class="sr-only">Search the catalogue</span>
                        <input type="search" placeholder="{{ $placeholder }}" autocomplete="off" enterkeyhint="search">
                    </label>
                    <div class="fhc-tags fh-tight">
                        <div class="fhc-crop">
                            @include('public.site.partials.crop-pick', ['id' => $id . 'Crop', 'crops' => $crops, 'current' => '', 'title' => 'Which crop?', 'noun' => $nouns, 'all' => ['All crops', $allLine]])
                        </div>
                        <button type="button" class="cp-tag fhc-facet" data-fhc-facet aria-haspopup="dialog">
                            <span class="cp-ico" aria-hidden="true"></span>
                            <span class="cp-t"><b>{{ $facetAll }}</b><small>{{ $facetTitle }}</small></span>
                            <span class="cp-chev">Change<svg fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg></span>
                        </button>
                    </div>
                </div>
                <div class="fhc-count" aria-live="polite"><span><b data-fhc-n>0</b> <span data-fhc-noun>{{ $nouns }}</span></span><button type="button" class="fhc-clear" data-fhc-clear hidden>Clear the filters</button></div>
                <div class="fhc-grid" data-fhc-grid></div>
                <p class="fhc-none" data-fhc-none hidden>Nothing in the catalogue matches. Try another name, or clear the filters.</p>
            </div>
        </section>
        <section class="fhm-pane fhm-detail" aria-live="polite">
            <div class="fhm-in" data-fhc-detail></div>
        </section>
    </div>
</dialog>
