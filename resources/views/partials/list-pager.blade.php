{{-- How a long list ends, on both kinds of screen.

     A desktop reads a list with pages: you can see how much there is and
     jump. A phone reads by scrolling, and a row of small numbered links
     under a thumb is a poor target — so the same paginator drives an
     auto-loader there instead, and the links stay only as the fallback for
     when script is off.

     Expects $paginator, plus $rowsUrl — the same listing with ?rows=1,
     which returns the cards alone. --}}
@if ($paginator->hasPages())
    <div class="lp-wrap" data-infinite-list
        data-rows-url="{{ $rowsUrl }}"
        data-next="{{ $paginator->currentPage() + 1 }}"
        data-last="{{ $paginator->lastPage() }}">
        {{-- Phones: the sentinel the scroller watches, and what it says
             while it is fetching or once there is nothing more. --}}
        <div class="lp-more" data-lp-more>
            <span class="lp-spin" aria-hidden="true"></span>
            <span data-lp-msg>Loading more…</span>
        </div>
        <button type="button" class="btn btn-white btn-sm lp-manual hidden" data-lp-manual>Load more</button>
        <div class="lp-links">{{ $paginator->onEachSide(1)->links('vendor.pagination.sm') }}</div>
    </div>
@elseif ($paginator->total() > 0)
    {{-- $noun lets any list say what it holds; notes were only the first. --}}
    <p class="lp-end">That is everything — {{ $paginator->total() }} {{ \Illuminate\Support\Str::plural($noun ?? 'item', $paginator->total()) }}.</p>
@endif
