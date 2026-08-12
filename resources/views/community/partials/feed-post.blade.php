{{-- One feed post card. Reused by the feed's first render and the
     infinite-scroll "older posts" endpoint. Expects: $post (with author,
     comments_count, reactionSummary), $friendIds. --}}
@php
    $author = $post->author;
    $isFriend = in_array((int) $post->authorUserId, $friendIds ?? [], true);
    $place = trim(implode(', ', array_filter([$author->city, $author->province])));
@endphp
<article class="card p-4 mb-5 feed-post wall-post" id="wallpost-{{ $post->id }}" data-post-id="{{ $post->id }}">
    <header class="flex items-start gap-3">
        @include('community.partials.avatar-status', ['user' => $author, 'size' => 'avatar-md'])
        <div class="min-w-0 grow">
            <p class="text-sm leading-tight flex items-center flex-wrap gap-x-1.5 gap-y-0.5">
                <a href="{{ route('community.connect.profile', ['userId' => $author->id]) }}" class="font-semibold text-gray-900 hover:text-brand-700">{{ $author->full_name }}</a>
                @if ($isFriend)<span class="badge badge-green align-middle">Co-farmer</span>@endif
                @include('community.partials.dm-btn', ['user' => $author])
            </p>
            <p class="text-xs text-gray-400">
                @if ($place)📍 {{ $place }} · @endif{{ $post->created_at?->diffForHumans() }}
            </p>
        </div>
    </header>
    @if ($post->isRestricted ?? false)
        @include('community.partials.restricted', ['reason' => $post->restrictedReason ?? null])
    @else
        <p class="text-sm text-gray-700 mt-2 whitespace-pre-line break-words">{!! \App\Support\CommunityText::render($post->body) !!}</p>
        @php $ytVid = \App\Support\CommunityText::youtubeId($post->body); @endphp
        @if ($ytVid)
            @include('community.partials.youtube-card', ['vid' => $ytVid])
        @endif
        @if ($post->imagePath)
            <div class="post-media">
                <img src="{{ \App\Support\MediaStore::url($post->imagePath) }}" alt="" loading="lazy">
            </div>
        @endif
        @if ($post->videoPath ?? null)
            @include('community.partials.video-embed', ['src' => $post->videoPath, 'poster' => $post->videoPoster ?? null])
        @endif
    @endif
    @include('community.partials.react-bar', ['type' => 'wallpost', 'id' => $post->id, 'summary' => $post->reactionSummary ?? null])

    @php
        $topComments = $post->relationLoaded('comments') ? $post->comments->whereNull('parentId')->sortBy('id')->values() : collect();
        $totalComments = $post->comments_count ?? $topComments->count();
        $previewCount = 2;
        $previewComments = $topComments->count() > $previewCount ? $topComments->slice(-$previewCount) : $topComments;
    @endphp
    <div class="mt-3 space-y-1.5 wall-comments">
        @if ($topComments->count() > $previewCount)
            <button type="button" class="js-view-all-comments text-xs font-semibold text-brand-700 hover:text-brand-800" data-post-id="{{ $post->id }}">
                View all {{ $totalComments }} comments
            </button>
        @endif
        @foreach ($previewComments as $comment)
            @include('community.connect.partials.wall-comment', [
                'comment' => $comment,
                'isReply' => false,
                'replies' => $post->comments->where('parentId', $comment->id)->sortBy('id'),
            ])
        @endforeach
    </div>

    @include('community.partials.wall-comment-form', ['postId' => $post->id])
</article>
