{{-- Our own page links.

     Laravel's stock paginator is written in Tailwind utility classes, and
     this project only compiles the utilities it finds under resources/ —
     a vendor file is never scanned, so those links arrived unstyled. These
     wear plain classes of our own, defined in app.css beside the rest. --}}
@if ($paginator->hasPages())
    <nav class="pg" role="navigation" aria-label="Pagination">
        @if ($paginator->onFirstPage())
            <span class="pg-btn is-off" aria-disabled="true">
                <svg fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
            </span>
        @else
            <a class="pg-btn" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Previous page">
                <svg fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
            </a>
        @endif

        @foreach ($elements as $element)
            @if (is_string($element))
                <span class="pg-gap">{{ $element }}</span>
            @endif
            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <span class="pg-btn is-now" aria-current="page">{{ $page }}</span>
                    @else
                        <a class="pg-btn" href="{{ $url }}">{{ $page }}</a>
                    @endif
                @endforeach
            @endif
        @endforeach

        @if ($paginator->hasMorePages())
            <a class="pg-btn" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Next page">
                <svg fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
            </a>
        @else
            <span class="pg-btn is-off" aria-disabled="true">
                <svg fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
            </span>
        @endif

        <span class="pg-count">{{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} of {{ $paginator->total() }}</span>
    </nav>
@endif
