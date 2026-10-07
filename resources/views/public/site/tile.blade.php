{{-- One page as a card: hubs, "more guides", the home page. Expects $p;
     optional $hideCat (the hub's pages all share one category, so the label
     on every card would only repeat the hub's name). --}}
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
    // A news roundup is news: it says when, and it is read as news, not a guide.
    $isNews = ($p->kind ?? null) === 'roundup';
    $when = $isNews && $p->publishedAt ? $p->publishedAt->copy()->timezone('Asia/Manila') : null;
    $tl = $p->lang === 'tl';
    $go = $p->section === 'questions'
        ? ($tl ? 'Basahin ang sagot' : 'Read the answer')
        : ($isNews ? 'Read the news' : ($tl ? 'Basahin' : 'Read the guide'));
    $cat = empty($hideCat) ? (string) $p->category : '';
@endphp
<a href="{{ $S::pageUrl($p) }}" class="sp-tile" data-cat="{{ $p->category }}">
    <img src="{{ $src }}" alt="{{ $h['alt'] ?? $p->title }}" loading="lazy" referrerpolicy="no-referrer">
    <div class="in">
        @if ($cat !== '' || $when)
            <span class="cat">
                {{ $cat }}
                @if ($when)
                    @if ($cat !== '')<span aria-hidden="true">·</span>@endif
                    <time datetime="{{ $when->toDateString() }}">{{ $when->format('M j, Y') }}</time>
                @endif
            </span>
        @endif
        <b>{{ $p->title }}</b>
        <p>{{ \Illuminate\Support\Str::limit($S::plain($p->excerpt), 170) }}</p>
        <span class="go">{{ $go }} ›</span>
    </div>
</a>
