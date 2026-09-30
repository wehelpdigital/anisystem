{{-- One page as a card: hubs, "more guides", the home page. Expects $p. --}}
@php
    $S = \App\Support\SitePages::class;
    $h = is_array($p->heroImage) ? $p->heroImage : [];
    $src = $S::img($h['src'] ?? null) ?: asset('images/site/fields-aerial.jpg');
@endphp
<a href="{{ $S::pageUrl($p) }}" class="sp-tile" data-cat="{{ $p->category }}">
    <img src="{{ $src }}" alt="{{ $h['alt'] ?? $p->title }}" loading="lazy">
    <span class="in">
        @if ($p->category)<span class="cat">{{ $p->category }}</span>@endif
        <b>{{ $p->title }}</b>
        <p>{{ \Illuminate\Support\Str::limit($S::plain($p->excerpt), 170) }}</p>
        <span class="go">{{ $p->lang === 'tl' ? 'Basahin' : 'Read the guide' }} ›</span>
    </span>
</a>
