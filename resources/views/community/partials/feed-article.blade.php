{{-- A tech-blog article, dealt into the wall as a post.

     No Join here — an article is not a room you enter — but it keeps the two
     things that make a wall card worth stopping at: you can react to it and
     you can talk about it. The body is cut to an excerpt on purpose: the whole
     article belongs on its own page, and a wall that prints it in full stops
     being a wall.

     Expects: $article (optionally with comment_count). --}}
@php
    // Longer than it shows: the clamp decides where it ends, and ending on
    // a cut line with an ellipsis is what tells a reader there is more.
    $excerpt = trim((string) $article->excerpt) !== ''
        ? $article->excerpt
        : \Illuminate\Support\Str::limit(trim(strip_tags((string) $article->body)), 420);
    $comments = (int) ($article->comment_count ?? 0);
@endphp
<article class="card p-0 mb-5 feed-post fp-card fa-card" data-article-card="{{ $article->id }}"
         data-view="blog:{{ $article->id }}">
    @if ($article->coverImagePath)
        {{-- The same shape a post's photo has: full width of the card, 4:3.
             A cover that picks its own height makes every article card a
             different card. --}}
        <a href="{{ route('community.blog.show', ['id' => $article->id]) }}" class="fa-cover media-skel">
            <img src="{{ \App\Support\MediaStore::url($article->coverImagePath) }}" alt="" loading="lazy"
                 onload="this.classList.add('is-loaded')"
                 onerror="this.closest('.media-skel')?.classList.add('is-gone')">
            <span class="fa-kicker">From the tech blog</span>
        </a>
    @endif
    <div class="fa-body">
        @unless ($article->coverImagePath)
            <span class="fa-kicker fa-kicker-flat">From the tech blog</span>
        @endunless
        <a href="{{ route('community.blog.show', ['id' => $article->id]) }}" class="fa-title">{{ $article->title }}</a>
        <p class="fa-by">
            {{ $article->authorName ?: 'AniSenso' }}@if ($article->publishedAt) · {{ \Illuminate\Support\Carbon::parse($article->publishedAt)->diffForHumans() }}@endif
        </p>
        <p class="fa-excerpt">{{ $excerpt }}</p>
        {{-- Green, and the width of the card: this is the one thing the card
             is for, and it was a pale outline sitting in a corner. --}}
        <a href="{{ route('community.blog.show', ['id' => $article->id]) }}" class="btn btn-primary btn-sm fa-read">Read more</a>
    </div>
    @include('community.partials.react-bar', ['type' => 'blogpost', 'id' => $article->id, 'summary' => null])
    <div class="fa-foot">
        <a href="{{ route('community.blog.show', ['id' => $article->id]) }}#comments" class="fp-act">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><path stroke-linecap="round" stroke-linejoin="round" d="M8 12h8m-8-4h5m-5 8h3m-6 4V6a2 2 0 012-2h12a2 2 0 012 2v8a2 2 0 01-2 2H8l-4 4z"/></svg>
            <span class="fp-lbl">{{ $comments > 0 ? $comments . ' ' . \Illuminate\Support\Str::plural('comment', $comments) : 'Join the discussion' }}</span>
        </a>
        @if (($article->viewCount ?? 0) > 0)
            <span class="fa-views">@include('community.partials.views-count', ['kind' => 'blog', 'id' => $article->id, 'count' => $article->viewCount])</span>
        @endif
    </div>
</article>
