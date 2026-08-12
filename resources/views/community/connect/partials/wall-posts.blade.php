{{-- A run of wall posts. Reused by first load, "load more", and new post.
     Expects: $posts (collection with author + comments.author). --}}
@foreach ($posts as $post)
    @php $canDelete = (int) $post->authorUserId === (int) auth()->id() || (int) $post->wallUserId === (int) auth()->id(); @endphp
    <article class="card p-4 mb-3 wall-post" id="wallpost-{{ $post->id }}" data-post-id="{{ $post->id }}">
        <header class="flex items-start gap-3">
            @include('community.partials.avatar', ['user' => $post->author, 'size' => 'avatar-md'])
            <div class="min-w-0 grow">
                <p class="text-sm leading-tight">
                    @if ($post->author)
                        <a href="{{ route('community.connect.profile', ['userId' => $post->author->id]) }}" class="font-semibold text-gray-900 hover:text-brand-700">{{ $post->author->full_name }}</a>
                    @else
                        <span class="font-semibold text-gray-900">Member</span>
                    @endif
                </p>
                <p class="text-xs text-gray-400">{{ $post->created_at?->diffForHumans() }}</p>
            </div>
            @include('community.partials.dm-btn', ['user' => $post->author])
            @if ($canDelete)
                <button type="button" class="wall-delete-btn text-gray-300 hover:text-red-500 p-1 -mr-1 shrink-0" data-post-id="{{ $post->id }}" aria-label="Delete">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.9 12.1a2 2 0 01-2 1.9H7.9a2 2 0 01-2-1.9L5 7m3 0V5a2 2 0 012-2h4a2 2 0 012 2v2m-11 0h16"/></svg>
                </button>
            @endif
        </header>

        @if ($post->isRestricted ?? false)
            @include('community.partials.restricted', ['reason' => $post->restrictedReason ?? null])
        @else
            @if ($post->body)
                <p class="text-sm text-gray-700 mt-2 whitespace-pre-line break-words">{!! \App\Support\CommunityText::render($post->body) !!}</p>
                @php $ytVid = \App\Support\CommunityText::youtubeId($post->body); @endphp
                @if ($ytVid)
                    @include('community.partials.youtube-card', ['vid' => $ytVid])
                @endif
            @endif
            @if ($post->imagePath)
                <div class="mt-2"><img src="{{ \App\Support\MediaStore::url($post->imagePath) }}" alt="Photo" loading="lazy" class="post-img rounded-lg max-h-72 w-auto"></div>
            @endif
            @if ($post->videoPath ?? null)
                @include('community.partials.video-embed', ['src' => $post->videoPath, 'poster' => $post->videoPoster ?? null])
            @endif
        @endif

        @include('community.partials.react-bar', ['type' => 'wallpost', 'id' => $post->id, 'summary' => $post->reactionSummary ?? null])

        @php
            $topComments = $post->comments->whereNull('parentId')->sortBy('id')->values();
            $totalComments = $post->comments->count();
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
@endforeach
