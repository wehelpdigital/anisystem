{{-- Shared AI conversation history: the desktop rail and the mobile sheet both
     render this. Expects: $conversations, $conversation (current, nullable).
     The "new" button uses .js-ai-new and deletes use .js-del-convo so a single
     delegated handler covers both copies on the page. --}}
<button type="button" class="js-ai-new w-full flex items-center gap-3 rounded-xl px-3 py-3 text-left font-semibold text-brand-700 hover:bg-gray-50">
    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
    Start a new question
</button>
@foreach ($conversations as $c)
    <div class="flex items-center gap-1">
        <a href="{{ route('ai.index', ['c' => $c->id]) }}"
           class="grow min-w-0 rounded-xl px-3 py-3 font-semibold text-gray-700 hover:bg-gray-50 {{ $conversation && $conversation->id === $c->id ? 'bg-brand-50 text-brand-700' : '' }}">
            <span class="block truncate">{{ $c->title }}</span>
            <span class="block text-xs font-normal text-gray-400">{{ $c->updated_at?->diffForHumans() }}</span>
            @if ($c->link_label)<span class="block text-xs font-normal text-brand-600">{{ $c->link_label }}</span>@endif
        </a>
        <button type="button" class="icon-btn text-red-600 shrink-0 js-del-convo" data-id="{{ $c->id }}" aria-label="Delete conversation">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
        </button>
    </div>
@endforeach
@if ($conversations->isEmpty())
    <p class="text-sm text-gray-500 text-center py-6">Nothing yet.</p>
@endif
