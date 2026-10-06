{{-- One page as a card: hubs, "more guides", the home page. Expects $p. --}}
@php
    $S = \App\Support\SitePages::class;
    $h = is_array($p->heroImage) ? $p->heroImage : [];
    // A small copy when the page has one (the weed profiles do).
    $src = $S::img($h['thumb'] ?? ($h['src'] ?? null)) ?: asset('images/site/fields-aerial.jpg');
@endphp
<a href="{{ $S::pageUrl($p) }}" class="sp-tile" data-cat="{{ $p->category }}">
    <img src="{{ $src }}" alt="{{ $h['alt'] ?? $p->title }}" loading="lazy" referrerpolicy="no-referrer">
    <span class="in">
        @if ($p->category)<span class="cat">{{ $p->category }}</span>@endif
        <b>{{ $p->title }}</b>
        <p>{{ \Illuminate\Support\Str::limit($S::plain($p->excerpt), 170) }}</p>
        <span class="go">{{ $p->section === 'questions' ? ($p->lang === 'tl' ? 'Basahin ang sagot' : 'Read the answer') : ($p->lang === 'tl' ? 'Basahin' : 'Read the guide') }} ›</span>
    </span>
</a>
