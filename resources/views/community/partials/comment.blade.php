{{--
    One thread entry with its replies.
    Expects: $comment (with ->replies), $isOwner (bool), $plan
    Must stay structurally identical to renderComment() in community/show.blade.php.
--}}
@php
    $canDelete = (int) $comment->anisystemUserId === (int) auth()->id() || $isOwner;
@endphp
<div class="cp-comment" data-comment-id="{{ $comment->id }}">
    <span class="cp-avatar">{{ optional($comment->author)->initials ?: '?' }}</span>
    <div class="min-w-0 grow">
        <p class="text-sm">
            <span class="font-bold text-gray-900">{{ optional($comment->author)->full_name ?: 'Member' }}</span>
            <span class="text-xs text-gray-400 ml-1">{{ $comment->created_at?->diffForHumans() }}</span>
            @if ($comment->isQuestion)
                <span class="badge badge-gray ml-1">Question</span>
            @endif
        </p>
        <p class="text-sm text-gray-600 mt-1 whitespace-pre-line">{{ $comment->body }}</p>
        <div class="flex gap-3 mt-1.5">
            <button type="button" class="text-xs font-bold text-brand-700 js-reply">Reply</button>
            @if ($canDelete)
                <button type="button" class="text-xs font-bold text-red-600 js-del-comment">Delete</button>
            @endif
        </div>

        <div class="cp-replies mt-3 space-y-3">
            @foreach ($comment->replies as $reply)
                @php
                    $canDeleteReply = (int) $reply->anisystemUserId === (int) auth()->id() || $isOwner;
                @endphp
                <div class="cp-comment" data-comment-id="{{ $reply->id }}">
                    <span class="cp-avatar">{{ optional($reply->author)->initials ?: '?' }}</span>
                    <div class="min-w-0 grow">
                        <p class="text-sm">
                            <span class="font-bold text-gray-900">{{ optional($reply->author)->full_name ?: 'Member' }}</span>
                            <span class="text-xs text-gray-400 ml-1">{{ $reply->created_at?->diffForHumans() }}</span>
                        </p>
                        <p class="text-sm text-gray-600 mt-1 whitespace-pre-line">{{ $reply->body }}</p>
                        <div class="flex gap-3 mt-1.5">
                            @if ($canDeleteReply)
                                <button type="button" class="text-xs font-bold text-red-600 js-del-comment">Delete</button>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
