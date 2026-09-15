{{-- Rail: a few of what the wall is saying. Expects: $posts
     (CommunityRail::recentPosts — author loaded, comment_count). Each row
     is the post on its own page. --}}
@php use App\Support\CommunityText; @endphp
<div class="card p-3 mb-3" data-rail-posts>
    <div class="flex items-center justify-between mb-1">
        <h3 class="text-sm font-bold text-gray-900" style="font-family:var(--font-heading)">📰 From the News Feed</h3>
        <a href="{{ route('community.index') }}" class="text-xs font-semibold text-brand-700 hover:text-brand-800">See all</a>
    </div>
    @forelse ($posts as $post)
        @php
            $said = trim(CommunityText::plain($post->body ?? '', 160));
            $pic = $post->imagePath ? \App\Support\MediaStore::url($post->imagePath) : null;
            if ($said === '') {
                $said = $pic ? '📷 Shared a photo' : ($post->videoPath ? '🎬 Shared a video' : 'Shared a post');
            }
        @endphp
        <a href="{{ route('community.post.show', ['id' => $post->id]) }}" class="rail-row rail-post">
            <span class="rail-face">@include('community.partials.avatar', ['user' => $post->author, 'size' => 'avatar-md', 'link' => false])</span>
            <span class="min-w-0 grow">
                <span class="rail-name">{{ $post->author->full_name }}</span>
                <span class="rail-title">{{ \Illuminate\Support\Str::limit($said, 84) }}</span>
                <span class="rail-meta">{{ $post->created_at?->diffForHumans(null, true) }} ago · 💬 {{ (int) ($post->comment_count ?? 0) }}</span>
            </span>
            @if ($pic)
                <span class="rail-thumb rail-thumb-sm"><img src="{{ $pic }}" alt="" loading="lazy"></span>
            @endif
        </a>
    @empty
        <p class="text-xs text-gray-400 py-2">Tahimik pa ang kapitbahayan.</p>
    @endforelse
</div>
