{{-- A single reply on a group post. Expects: $reply. --}}
<div class="flex items-start gap-2.5 group-reply" data-reply-id="{{ $reply->id }}">
    <span class="w-7 h-7 rounded-full bg-gray-200 text-gray-600 text-[10px] font-bold flex items-center justify-center shrink-0">
        {{ optional($reply->author)->initials ?: '?' }}
    </span>
    <div class="min-w-0 bg-gray-50 rounded-xl px-3 py-2 grow">
        <p class="text-xs font-semibold text-gray-900">{{ optional($reply->author)->full_name ?: 'Member' }}
            <span class="text-gray-400 font-normal">· {{ $reply->created_at?->diffForHumans() }}</span></p>
        <p class="text-sm text-gray-700 whitespace-pre-line break-words">{{ $reply->body }}</p>
    </div>
</div>
