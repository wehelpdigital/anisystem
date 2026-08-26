{{-- One stretch of the blog: the article bands themselves and nothing else.

     Drawn by the page on first paint and by the search for every answer
     after it, so a card is described once and the two roads cannot drift.

     Expects: $posts. --}}
@foreach ($posts as $post)
    <a href="{{ route('community.blog.show', ['id' => $post->id]) }}" class="blog-card bl-hue-{{ $post->id % 6 }}">
        @php $covers = $post->covers(); @endphp
        {{-- Every cover the story wears, stacked: the page drifts through
             them on its own clock and a thumb can slide them left or right
             (blog-covers JS on the page). No dots, no arrows — the drift
             itself says there is more. --}}
        <div class="blog-cover" @if (count($covers) > 1) data-covers @endif>
            @forelse ($covers as $i => $c)
                <img src="{{ $c['url'] }}" alt="" loading="lazy"
                    class="bc-img {{ $i === 0 ? 'is-on' : '' }}"
                    data-cover @if ($c['mother']) data-cover-alt="{{ $c['mother'] }}" @endif
                    onload="this.classList.add('is-loaded')">
            @empty
                <div class="blog-cover-fallback">🌾</div>
            @endforelse
        </div>
        <div class="blog-body">
            <span class="blog-title">{{ $post->title }}</span>
            @if ($post->excerpt)<span class="blog-excerpt">{{ \Illuminate\Support\Str::limit($post->excerpt, 160) }}</span>@endif
            <span class="blog-more">Read More</span>
            <span class="blog-meta">
                @if ($post->authorName)<span>✍️ {{ $post->authorName }}</span>@endif
                @if ($post->publishedAt)<span>{{ $post->publishedAt->format('M j, Y') }}</span>@endif
                <span>💬 {{ $post->comments_count }}</span>
            </span>
        </div>
    </a>
@endforeach
