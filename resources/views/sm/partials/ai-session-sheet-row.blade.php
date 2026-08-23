{{-- One chat in the phone's "Past questions" sheet. The rail's twin — see
     ai-session-row.blade.php for why it is a partial.

     Expects: $id, $href, $title, $when; optional $link, $active. --}}
<div class="flex items-center gap-1" data-convo-sheet-row="{{ $id }}">
    <a href="{{ $href }}"
       class="grow min-w-0 rounded-xl px-3 py-3 font-semibold text-gray-700 hover:bg-gray-50 {{ ($active ?? false) ? 'bg-brand-50 text-brand-700' : '' }} js-ai-convo" data-c="{{ $id }}">
        <span class="block truncate" data-session-title>{{ $title }}</span>
        <span class="block text-xs font-normal text-gray-400" data-session-when>{{ $when }}</span>
        @if (! empty($link))<span class="block text-xs font-normal text-brand-600">{{ $link }}</span>@endif
    </a>
    <button type="button" class="icon-btn text-red-600 shrink-0 js-ai-del" data-id="{{ $id }}" aria-label="Delete conversation">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
    </button>
</div>
