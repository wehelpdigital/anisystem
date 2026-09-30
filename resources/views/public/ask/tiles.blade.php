{{-- Answered questions as cards: the list's first page and every page the
     scroll draws after it. Expects $pages. --}}
@foreach ($pages as $p)
    @include('public.site.tile', ['p' => $p])
@endforeach
