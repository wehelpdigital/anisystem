{{-- A tech-blog article, dealt into the wall as a post.

     No Join here — an article is not a room you enter — but it keeps the two
     things that make a wall card worth stopping at: you can react to it and
     you can talk about it. The body is cut to an excerpt on purpose: the whole
     article belongs on its own page, and a wall that prints it in full stops
     being a wall.

     Expects: $article. --}}
@php
    $excerpt = trim((string) $article->excerpt) !== ''
        ? $article->excerpt
        : \Illuminate\Support\Str::limit(trim(strip_tags((string) $article->body)), 220);
@endphp
<article class="card p-0 mb-5 feed-post fp-card fa-card overflow-hidden" data-article-card="{{ $article->id }}">
    @if ($article->coverImagePath)
        <a href="{{ route('community.blog.show', ['id' => $article->id]) }}" class="fa-cover media-skel">
            <img src="{{ \App\Support\MediaStore::url($article->coverImagePath) }}" alt="" loading="lazy"
                 onload="this.classList.add('is-loaded')"
                 onerror="this.closest('.media-skel')?.classList.add('is-gone')">
        </a>
    @endif
    <div class="p-4">
        <span class="fa-kicker">From the tech blog</span>
        <a href="{{ route('community.blog.show', ['id' => $article->id]) }}"
           class="block font-bold text-gray-900 leading-snug mt-1 hover:text-brand-700" style="font-family:var(--font-heading)">{{ $article->title }}</a>
        <p class="text-xs text-gray-400 mt-0.5">
            {{ $article->authorName ?: 'AniSenso' }}@if ($article->publishedAt) · {{ \Illuminate\Support\Carbon::parse($article->publishedAt)->diffForHumans() }}@endif
        </p>
        <p class="text-sm text-gray-600 mt-2 leading-relaxed">{{ $excerpt }}</p>
        <a href="{{ route('community.blog.show', ['id' => $article->id]) }}" class="btn btn-white btn-sm mt-3">Read more</a>
    </div>
    @include('community.partials.react-bar', ['type' => 'blogpost', 'id' => $article->id, 'summary' => null])
    <div class="fa-foot">
        <a href="{{ route('community.blog.show', ['id' => $article->id]) }}#comments" class="fp-act">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><path stroke-linecap="round" stroke-linejoin="round" d="M8 12h8m-8-4h5m-5 8h3m-6 4V6a2 2 0 012-2h12a2 2 0 012 2v8a2 2 0 01-2 2H8l-4 4z"/></svg>
            <span class="fp-lbl">Join the discussion on this article</span>
        </a>
    </div>
</article>
