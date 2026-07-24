{{-- A single wall-post comment. Expects: $comment. --}}
<div class="flex items-start gap-2 wall-comment" data-comment-id="{{ $comment->id }}">
    <span class="w-6 h-6 rounded-full bg-gray-200 text-gray-600 text-[9px] font-bold flex items-center justify-center shrink-0">{{ optional($comment->author)->initials ?: '?' }}</span>
    <div class="bg-gray-50 rounded-lg px-2.5 py-1.5 min-w-0">
        <span class="text-xs font-semibold text-gray-900">{{ optional($comment->author)->full_name ?: 'Member' }}</span>
        <span class="text-[11px] text-gray-400">· {{ $comment->created_at?->diffForHumans() }}</span>
        <p class="text-sm text-gray-700 whitespace-pre-line break-words">{{ $comment->body }}</p>
    </div>
</div>
