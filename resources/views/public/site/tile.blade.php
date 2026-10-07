{{-- One page as a card: hubs, "more guides", the home page. Expects $p. --}}
@php
    $S = \App\Support\SitePages::class;
    $h = is_array($p->heroImage) ? $p->heroImage : [];
    // A small copy when the page has one: the weed profiles name it, the
    // others keep it beside the picture as "-480.webp". The full picture
    // then belongs to its own page alone (an audit counts a page whose every
    // picture shows elsewhere too).
    $thumb = $h['thumb'] ?? null;
    if (! $thumb && ! empty($h['src']) && ! preg_match('#^https?://#i', $h['src'])) {
        $small = preg_replace('#\.(jpe?g|png|webp)$#i', '-480.webp', $h['src']);
        $thumb = $small !== $h['src'] && is_file(public_path(ltrim($small, '/'))) ? $small : null;
    }
    $src = $S::img($thumb ?? ($h['src'] ?? null)) ?: asset('images/site/fields-aerial.jpg');
@endphp
<a href="{{ $S::pageUrl($p) }}" class="sp-tile" data-cat="{{ $p->category }}">
    <img src="{{ $src }}" alt="{{ $h['alt'] ?? $p->title }}" loading="lazy" referrerpolicy="no-referrer">
    <div class="in">
        @if ($p->category)<span class="cat">{{ $p->category }}</span>@endif
        <b>{{ $p->title }}</b>
        <p>{{ \Illuminate\Support\Str::limit($S::plain($p->excerpt), 170) }}</p>
        <span class="go">{{ $p->section === 'questions' ? ($p->lang === 'tl' ? 'Basahin ang sagot' : 'Read the answer') : ($p->lang === 'tl' ? 'Basahin' : 'Read the guide') }} ›</span>
    </div>
</a>
