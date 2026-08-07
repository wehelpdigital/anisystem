{{-- App-styled pagination (Tailwind). Used via $paginator->links(...). --}}
@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Pagination" class="flex items-center justify-center flex-wrap gap-1.5">
        @php
            $btn = 'inline-flex items-center justify-center h-9 min-w-9 px-3 rounded-lg text-sm font-semibold transition';
        @endphp

        {{-- Previous --}}
        @if ($paginator->onFirstPage())
            <span aria-disabled="true" class="{{ $btn }} text-gray-300 cursor-default select-none">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
            </span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Previous" class="{{ $btn }} text-gray-600 hover:bg-gray-100">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
            </a>
        @endif

        {{-- Page numbers --}}
        @foreach ($elements as $element)
            @if (is_string($element))
                <span class="px-1.5 text-gray-400 select-none">…</span>
            @endif
            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <span aria-current="page" class="{{ $btn }} bg-brand-600 text-white shadow-sm">{{ $page }}</span>
                    @else
                        <a href="{{ $url }}" class="{{ $btn }} text-gray-600 hover:bg-gray-100">{{ $page }}</a>
                    @endif
                @endforeach
            @endif
        @endforeach

        {{-- Next --}}
        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Next" class="{{ $btn }} text-gray-600 hover:bg-gray-100">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
            </a>
        @else
            <span aria-disabled="true" class="{{ $btn }} text-gray-300 cursor-default select-none">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
            </span>
        @endif
    </nav>
@endif
