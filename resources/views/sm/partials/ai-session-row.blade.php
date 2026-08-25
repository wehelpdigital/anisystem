{{-- One chat in the AI module's sessions rail.

     Rendered by the page for the chats it knows about, and once more as the
     <template> the page clones when a new session is answered into being —
     so the row the server draws and the row JavaScript adds cannot drift.

     Expects: $id, $href, $title, $when; optional $link, $active. --}}
<div class="ai-session-row {{ ($active ?? false) ? 'is-active' : '' }}" data-convo-row="{{ $id }}">
    <a href="{{ $href }}" class="t js-ai-convo" data-c="{{ $id }}">
        <span data-session-title>{{ $title }}</span>
        <span class="meta" data-session-when>{{ $when }}</span>
        @if (! empty($link))<span class="meta" style="color:var(--color-brand-600)">{{ $link }}</span>@endif
    </a>
    <button type="button" class="ai-session-act js-side-rename" data-id="{{ $id }}" title="Rename chat" aria-label="Rename chat">
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
    </button>
    <button type="button" class="ai-session-act js-ai-del" data-id="{{ $id }}" title="Delete chat" aria-label="Delete chat">
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 6h18M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6M8 6V4a2 2 0 012-2h4a2 2 0 012 2v2M10 11v6M14 11v6"/></svg>
    </button>
</div>
