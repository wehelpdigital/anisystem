{{-- A run of group posts. Reused by the group page, "load more", and the
     just-posted append. Expects: $posts (collection), $group. --}}
@php use App\Support\CommunityAvatar; @endphp
@foreach ($posts as $post)
    @php
        $canDelete = (int) $post->userId === (int) auth()->id()
            || (int) $group->createdByUserId === (int) auth()->id();
        $author = optional($post->author);
        $pSummary = $post->reactionSummary ?? ['counts' => [], 'mine' => null];
        $pMine = $pSummary['mine'] ?? null;
        $pCounts = $pSummary['counts'] ?? [];
        $isGif = $post->imagePath && str_ends_with(mb_strtolower($post->imagePath), '.gif');
        $replyCount = $post->replies ? $post->replies->count() : 0;
    @endphp
    {{-- The id is what a notification's #post-N lands on. Without it the
         reader is dropped at the top of the group to go and find the thing
         they were told about. --}}
    {{-- fp-card and a hue per topic: the same coloured edge the wall's posts
         wear, so a column of topics has a rhythm and the eye can see where
         one ends and the next begins. --}}
    <article class="card p-3 sm:p-4 mb-4 sm:mb-5 group-post fp-card fp-hue-{{ $post->id % 6 }}"
             id="post-{{ $post->id }}" data-post-id="{{ $post->id }}"
             data-view="topic:{{ $post->id }}">
        <header class="flex items-start gap-3">
            {{-- The cloud over the face, as the wall draws it; the card
                 pads its head to hold it. --}}
            @include('community.partials.avatar-status', ['user' => $post->author, 'size' => 'avatar-md'])
            <div class="min-w-0 grow">
                <p class="text-sm leading-tight">
                    @if ($post->author)
                        <a href="{{ route('community.connect.profile', ['userId' => $post->author->id]) }}" class="font-semibold text-gray-900 hover:text-brand-700">{{ $author->full_name }}</a>
                    @else
                        <span class="font-semibold text-gray-900">Member</span>
                    @endif
                </p>
                <p class="text-xs text-gray-400" title="{{ $post->created_at }}">{{ $post->created_at?->diffForHumans() }}</p>
                {{-- Who is asking, in the line the wall draws too. --}}
                @include('community.partials.author-facts', [
                    'user' => $post->author,
                    'isCoFarmer' => $post->authorIsCoFarmer ?? false,
                    'followers' => $post->authorFollowers ?? 0,
                ])
            </div>
            @include('community.partials.dm-btn', ['user' => $post->author])
            @if ($canDelete)
                <button type="button" class="post-delete-btn text-gray-300 hover:text-red-500 p-1 -mr-1 shrink-0" data-post-id="{{ $post->id }}" aria-label="Delete post">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.9 12.1a2 2 0 01-2 1.9H7.9a2 2 0 01-2-1.9L5 7m3 0V5a2 2 0 012-2h4a2 2 0 012 2v2m-11 0h16"/></svg>
                </button>
            @endif
        </header>

        <div class="mt-2">
            @if ($post->title)
                <h3 class="font-bold text-gray-900 leading-snug" style="font-family:var(--font-heading)">{{ $post->title }}</h3>
            @endif
            @if ($post->isRestricted ?? false)
                @include('community.partials.restricted', ['reason' => $post->restrictedReason ?? null])
            @else
                <div class="group-post-body text-sm text-gray-700 mt-1 whitespace-pre-line break-words">{!! \App\Support\CommunityText::safeHtml($post->body) !!}</div>
                @php $ytVid = \App\Support\CommunityText::youtubeId($post->body); @endphp
                @if ($ytVid)
                    @include('community.partials.youtube-card', ['vid' => $ytVid])
                @endif
                @if ($post->imagePath)
                    {{-- media-skel: shimmer while it decodes, vanish if it 404s. --}}
                    <div class="post-media media-skel">
                        <img src="{{ \App\Support\MediaStore::url($post->imagePath) }}" alt="Attachment" loading="lazy"
                            onload="this.classList.add('is-loaded')"
                            onerror="this.closest('.media-skel')?.classList.add('is-gone')">
                        @if ($isGif)<span class="gif-badge">GIF</span>@endif
                    </div>
                @endif
                @if ($post->videoPath ?? null)
                    @include('community.partials.video-embed', ['src' => $post->videoPath, 'poster' => $post->videoPoster ?? null])
                @endif
            @endif
        </div>

        @include('community.partials.react-bar', ['type' => 'post', 'id' => $post->id, 'summary' => $pSummary])

        {{-- The way into the conversation, and how much of one there is.
             Both open the thread; the replies themselves are in there. --}}
        <div class="topic-acts">
            <button type="button" class="topic-act js-view-all-replies" data-post-id="{{ $post->id }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M8 10h8M8 14h5m-8 6l3.2-3H17a3 3 0 003-3V7a3 3 0 00-3-3H7a3 3 0 00-3 3v13z"/></svg>
                <span><span data-reply-count="{{ $post->id }}">{{ $replyCount }}</span> {{ \Illuminate\Support\Str::plural('comment', $replyCount) }}</span>
            </button>
            <button type="button" class="topic-act js-view-all-replies" data-post-id="{{ $post->id }}" data-write="1">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.4-9.4a2 2 0 112.8 2.8L11 15l-4 1 1-4 8.6-8.4z"/></svg>
                <span>Write a comment</span>
            </button>
            @include('community.partials.views-count', ['kind' => 'topic', 'id' => $post->id, 'count' => $post->viewCount ?? 0])
            @if ((int) $post->userId !== (int) auth()->id())
                <button type="button" class="topic-act rp-door" data-report="topic:{{ $post->id }}"
                        title="Report this topic" aria-label="Report this topic">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><path stroke-linecap="round" stroke-linejoin="round" d="M4 21V4m0 1h11l-1.5 3L15 11H4"/></svg>
                </button>
            @endif
        </div>

        <div class="post-thread">
            @php
                $topReplies = $post->replies->whereNull('parentId')->sortBy('id')->values();
                $collapseReplies = $topReplies->count() > 3;
            @endphp
            @if ($replyCount > 0)
                <p class="replies-label">{{ $replyCount }} sagot</p>
            @endif
            @if ($collapseReplies)
                <button type="button" class="js-view-all-replies post-thread-more" data-post-id="{{ $post->id }}">
                    View all {{ $topReplies->count() }} replies
                </button>
            @endif
            <div class="space-y-2 post-replies {{ $collapseReplies ? 'is-collapsed' : '' }}">
                @foreach ($topReplies as $reply)
                    @include('community.groups.partials.reply', [
                        'reply' => $reply,
                        'isReply' => false,
                        'children' => $post->replies->where('parentId', $reply->id)->sortBy('id'),
                    ])
                @endforeach
            </div>
        </div>

        <form class="post-reply-form flex flex-wrap items-center gap-2 mt-3" data-post-id="{{ $post->id }}">
            <span class="avatar avatar-sm {{ CommunityAvatar::hue(auth()->user()->full_name ?? '?') }}">{{ auth()->user()->initials ?? '?' }}</span>
            <span class="reply-shell">
                <input type="text" placeholder="Sumagot ka…" maxlength="4000">
                <button type="button" class="emoji-btn js-comment-photo" aria-label="Attach a photo" title="Photo">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                </button>
                <input type="file" class="js-comment-file hidden" accept="image/*">
                <button type="button" class="emoji-btn js-emoji-btn" aria-label="Add an emoji" title="Emoji">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </button>
                <button type="submit" class="reply-send" aria-label="Reply">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14m0 0l-6-6m6 6l-6 6"/></svg>
                </button>
            </span>
            <span class="attach-chip hidden js-comment-chip"><span class="js-chip-name"></span><button type="button" class="js-chip-clear" aria-label="Remove photo">✕</button></span>
        </form>
    </article>
@endforeach
