{{-- One stretch of the blog: the article bands themselves and nothing else.

     Drawn by the page on first paint and by the search for every answer
     after it, so a card is described once and the two roads cannot drift.

     Expects: $posts. --}}
@foreach ($posts as $post)
    <a href="{{ route('community.blog.show', ['id' => $post->id]) }}" class="blog-card bl-hue-{{ $post->id % 6 }}">
        <div class="blog-cover">
            @if ($post->coverUrl())
                <img src="{{ $post->coverUrl() }}" alt="" loading="lazy"
                    onload="this.classList.add('is-loaded')" onerror="this.remove()">
            @else
                <div class="blog-cover-fallback">🌾</div>
            @endif
        </div>
        <div class="blog-body">
            <span class="blog-title">{{ $post->title }}</span>
            @if ($post->excerpt)<span class="blog-excerpt">{{ \Illuminate\Support\Str::limit($post->excerpt, 120) }}</span>@endif
            <span class="blog-meta">
                @if ($post->authorName)<span>✍️ {{ $post->authorName }}</span>@endif
                @if ($post->publishedAt)<span>{{ $post->publishedAt->format('M j, Y') }}</span>@endif
                <span>💬 {{ $post->comments_count }}</span>
            </span>
        </div>
    </a>
@endforeach
