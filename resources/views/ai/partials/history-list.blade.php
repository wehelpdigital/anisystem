{{-- Shared AI conversation history: the desktop rail and the mobile sheet both
     render this, in the messenger's two-line row language. Expects:
     $conversations, $conversation (current, nullable). The "new" button uses
     .js-ai-new and deletes use .js-del-convo so a single delegated handler
     covers both copies on the page; the JS in ai/index.blade.php builds rows
     of this same .ai-hrow shape when a new question starts. --}}
<button type="button" class="js-ai-new ai-newq">
    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
    Start a new question
</button>
@foreach ($conversations as $c)
    <div class="ai-hrow {{ $conversation && $conversation->id === $c->id ? 'is-active' : '' }}">
        <a href="{{ route('ai.index', ['c' => $c->id]) }}">
            <span class="t">{{ $c->title }}</span>
            <span class="meta">{{ $c->updated_at?->diffForHumans() }}@if ($c->link_label) · <span class="lnk">{{ $c->link_label }}</span>@endif</span>
        </a>
        <button type="button" class="ai-hact js-del-convo" data-id="{{ $c->id }}" aria-label="Delete conversation">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
        </button>
    </div>
@endforeach
@if ($conversations->isEmpty())
    <p class="ai-hempty">No questions yet — ask your first one below and it will name itself here.</p>
@endif
