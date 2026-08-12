{{-- A positioned sticky note sitting among a day's activity cards. Drag the
     grip to move; the pencil opens the modal editor; ✕ deletes. Expects: $note. --}}
@php
    $noteMedia = collect(is_array($note->media) ? $note->media : [])
        ->map(fn ($m) => empty($m['path']) ? null : [
            'type' => $m['type'] ?? 'image',
            'path' => $m['path'],
            'url' => \App\Support\MediaStore::url($m['path']),
            'poster' => $m['poster'] ?? null,
            'posterUrl' => ! empty($m['poster']) ? \App\Support\MediaStore::url($m['poster']) : null,
        ])
        ->filter()->values();
@endphp
<div class="inline-note" data-inline-note="{{ $note->id }}" data-date="{{ $note->noteDate->format('Y-m-d') }}"
     data-sort-key="{{ (int) $note->sortKey }}" data-media="{{ $noteMedia->toJson() }}"
     title="Drag the grip to move · tap the pencil to edit">
    <span class="inline-note-grip" aria-hidden="true"><svg viewBox="0 0 24 24" fill="currentColor" class="w-3.5 h-3.5"><circle cx="9" cy="6" r="1.5"/><circle cx="15" cy="6" r="1.5"/><circle cx="9" cy="12" r="1.5"/><circle cx="15" cy="12" r="1.5"/><circle cx="9" cy="18" r="1.5"/><circle cx="15" cy="18" r="1.5"/></svg></span>
    <span class="inline-note-tag" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M7 3h10a2 2 0 012 2v14a2 2 0 01-2 2H7a2 2 0 01-2-2V5a2 2 0 012-2zM9 8h6M9 12h6M9 16h3"/></svg>Note</span>
    <div class="inline-note-body">{!! $note->content !!}</div>
    <div class="inline-note-media">@include('sm.partials.note-media', ['media' => $noteMedia])</div>
    <button type="button" class="inline-note-edit" title="Edit note" aria-label="Edit note">
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
    </button>
    <button type="button" class="inline-note-del" title="Delete note" aria-label="Delete note">
        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.9 12.1a2 2 0 01-2 1.9H7.9a2 2 0 01-2-1.9L5 7m3 0V5a2 2 0 012-2h4a2 2 0 012 2v2m-11 0h16"/></svg>
    </button>
</div>
