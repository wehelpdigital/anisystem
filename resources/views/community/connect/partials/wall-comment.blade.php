{{-- A single wall-post comment, with its replies when top-level.
     Expects: $comment; optional $replies (children), $isReply (render as a
     nested reply — no further nesting). Reply buttons carry the thread's top
     comment id so replies-to-replies stay in the same thread. --}}
@php
    $isReply = $isReply ?? false;
    $threadId = $comment->parentId ?: $comment->id;
    $isMine = (int) $comment->userId === (int) auth()->id();
    $gone = (bool) ($comment->isDeleted ?? false);
@endphp
<div class="wall-comment {{ $isReply ? 'wall-comment-reply' : '' }}" data-comment-id="{{ $comment->id }}">
    <div class="flex items-start gap-2">
        @include('community.partials.avatar', ['user' => $comment->author, 'size' => 'avatar-sm'])
        <div class="min-w-0">
            @if ($gone)
                <div class="bg-gray-50 rounded-lg px-2.5 py-1.5 text-xs text-gray-400 italic wall-comment-tombstone">
                    This comment was deleted
                </div>
            @else
                <div class="bg-gray-50 rounded-lg px-2.5 py-1.5">
                    @if ($comment->author)
                        <a href="{{ route('community.connect.profile', ['userId' => $comment->author->id]) }}" class="text-xs font-semibold text-gray-900 hover:text-brand-700">{{ $comment->author->full_name }}</a>
                    @else
                        <span class="text-xs font-semibold text-gray-900">Member</span>
                    @endif
                    <span class="text-[0.688rem] text-gray-400">· {{ $comment->created_at?->diffForHumans() }}</span>
                    @if ($comment->isRestricted ?? false)
                        @include('community.partials.restricted', ['reason' => $comment->restrictedReason ?? null])
                    @else
                        @if ($comment->body)
                            <p class="text-sm text-gray-700 whitespace-pre-line break-words">{!! \App\Support\CommunityText::render($comment->body) !!}</p>
                        @endif
                        @php $cShots = method_exists($comment, 'shots') ? $comment->shots() : array_filter([$comment->imagePath]); @endphp
                        @if (count($cShots) > 1)
                            {{-- Several pictures answer the way several
                                 pictures on a post do: one at a time, slowly,
                                 pushable by a thumb, and whole in the lightbox
                                 when tapped. Smaller, because a comment is. --}}
                            @include('community.partials.post-gallery', ['shots' => $cShots, 'mini' => true])
                        @elseif (count($cShots) === 1)
                            <img src="{{ \App\Support\MediaStore::url($cShots[0]) }}"
                                 alt="Comment photo" loading="lazy" class="post-img rounded-lg mt-1 max-h-48 w-auto">
                        @endif
                        @if ($comment->videoPath ?? null)
                            @include('community.partials.video-embed', ['src' => $comment->videoPath, 'poster' => $comment->videoPoster ?? null])
                        @endif
                    @endif
                </div>
                <div class="flex items-center gap-2 mt-0.5 ml-1">
                    @include('community.partials.react-bar', ['type' => 'wallcomment', 'id' => $comment->id, 'summary' => $comment->reactionSummary ?? null, 'mini' => true])
                    <button type="button" class="js-wall-reply text-[0.688rem] font-semibold text-gray-400 hover:text-brand-700"
                            data-parent-id="{{ $threadId }}"
                            @if ($comment->author && ! $isMine) data-author-id="{{ $comment->author->id }}" data-author-name="{{ $comment->author->full_name }}" @endif>Reply</button>
                    @if ($isMine)
                        <button type="button" class="js-comment-delete text-[0.688rem] font-semibold text-gray-400 hover:text-red-500"
                                data-comment-id="{{ $comment->id }}">Delete</button>
                    @endif
                    @include('community.partials.dm-btn', ['user' => $comment->author])
                </div>
            @endif
        </div>
    </div>
    @unless ($isReply)
        <div class="wall-replies" data-parent-id="{{ $comment->id }}">
            @foreach (($replies ?? collect()) as $reply)
                @include('community.connect.partials.wall-comment', ['comment' => $reply, 'isReply' => true, 'replies' => collect()])
            @endforeach
        </div>
    @endunless
</div>
