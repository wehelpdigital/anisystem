{{-- One blog comment. Expects $comment (with author + reactionSummary). --}}
<div class="flex items-start gap-2.5" data-blog-comment="{{ $comment->id }}">
    @include('community.partials.avatar', ['user' => $comment->author, 'size' => 'avatar-md'])
    <div class="min-w-0 grow">
        <div class="bg-gray-50 rounded-lg px-3 py-2">
            <p class="text-sm">
                <span class="font-semibold text-gray-900">{{ optional($comment->author)->full_name ?: 'Member' }}</span>
                <span class="text-[11px] text-gray-400 ms-1">· {{ $comment->created_at?->diffForHumans() }}</span>
            </p>
            @if (filled($comment->body))
                <p class="text-sm text-gray-700 whitespace-pre-line break-words mt-0.5">{!! \App\Support\CommunityText::render($comment->body) !!}</p>
            @endif
            @if ($comment->imagePath)
                <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($comment->imagePath) }}" alt="Comment photo" loading="lazy" data-lightbox class="post-img rounded-lg mt-2 max-h-64 w-auto">
            @endif
        </div>
        <div class="mt-1 ml-1">
            @include('community.partials.react-bar', ['type' => 'blogcomment', 'id' => $comment->id, 'summary' => $comment->reactionSummary ?? null, 'mini' => true])
        </div>
    </div>
</div>
