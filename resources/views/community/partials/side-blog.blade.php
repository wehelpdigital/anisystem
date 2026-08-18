{{-- Rail: what's new in the Technician's Blog. Expects: $articles (newest
     published AsCommunityBlogPost, with comments_count). --}}
<div class="card p-3 mb-3" data-rail-blog>
    <div class="flex items-center justify-between mb-1">
        <h3 class="text-sm font-bold text-gray-900" style="font-family:var(--font-heading)">📰 New in the Blog</h3>
        <a href="{{ route('community.blog') }}" class="text-xs font-semibold text-brand-700 hover:text-brand-800">See all</a>
    </div>
    @forelse ($articles as $article)
        <a href="{{ route('community.blog.show', ['id' => $article->id]) }}" class="rail-row">
            <span class="rail-thumb">
                @if ($article->coverUrl())
                    {{-- The mother site owns these covers; a deploy here may not
                         have the file, so the local copy falls back to hers. --}}
                    <img src="{{ $article->coverUrl() }}" alt="" loading="lazy"
                         data-cover data-cover-alt="{{ $article->coverUrlOnMother() }}">
                @else
                    🌾
                @endif
            </span>
            <span class="min-w-0 grow">
                <span class="rail-title">{{ $article->title }}</span>
                <span class="rail-meta">
                    @if ($article->publishedAt){{ $article->publishedAt->format('M j') }} · @endif💬 {{ $article->comments_count ?? 0 }}
                </span>
            </span>
        </a>
    @empty
        <p class="text-xs text-gray-400 py-2">Wala pang artikulo — check back soon.</p>
    @endforelse
</div>

@once
    @push('scripts')
    <script>
    (function () {
        if (window.__railCoverBound) return;
        window.__railCoverBound = true;
        /* A broken-image icon is the one outcome nobody should see: try the
           mother site's copy of the cover, then the quiet 🌾 placeholder. */
        document.querySelectorAll('[data-cover]').forEach((img) => {
            img.addEventListener('error', function onErr() {
                const alt = img.getAttribute('data-cover-alt');
                if (alt && img.src !== alt) { img.src = alt; return; }
                img.removeEventListener('error', onErr);
                const holder = img.parentElement;
                if (holder) holder.textContent = '🌾';
            });
        });
    })();
    </script>
    @endpush
@endonce
