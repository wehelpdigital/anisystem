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
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 6h18M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6M8 6V4a2 2 0 012-2h4a2 2 0 012 2v2M10 11v6M14 11v6"/></svg>
        </button>
    </div>
@endforeach
@if ($conversations->isEmpty())
    <p class="ai-hempty">No questions yet — ask your first one below and it will name itself here.</p>
@endif
