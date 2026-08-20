{{-- A tech-blog article, dealt into the wall as a post.

     One card, one invitation: Read more. It used to carry a row of reactions
     and a comment door as well — but you cannot react to something you have
     not read, and three ways in is two too many. The body is cut to an
     excerpt on purpose: the whole article belongs on its own page, and a
     wall that prints it in full stops being a wall.

     Expects: $article. --}}
@php
    // Longer than it shows: the clamp decides where it ends, and ending on
    // a cut line with an ellipsis is what tells a reader there is more.
    $excerpt = trim((string) $article->excerpt) !== ''
        ? $article->excerpt
        : \Illuminate\Support\Str::limit(trim(strip_tags((string) $article->body)), 420);
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
</article>
