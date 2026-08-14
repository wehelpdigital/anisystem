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
            // The saved map this picture came from, when the page knows it —
            // the chip then opens the map itself rather than just its picture.
            'mapUrl' => ($mapUrlByPath ?? [])[$m['path']] ?? null,
            // Strokes ride along in data-media (the JS twin keeps them too),
            // or editing the note would save the drawing back flattened.
            'strokes' => ($m['type'] ?? '') === 'drawing' ? ($m['strokes'] ?? null) : null,
        ])
        ->filter()->values();
@endphp
<div class="inline-note" data-inline-note="{{ $note->id }}" data-date="{{ $note->noteDate->format('Y-m-d') }}"
     data-sort-key="{{ (int) $note->sortKey }}" data-media="{{ $noteMedia->toJson() }}"
     data-title="{{ $note->title }}"
     @if ($note->lotId) data-lot-id="{{ (int) $note->lotId }}" @endif
     @if ($note->activityId) data-activity-id="{{ (int) $note->activityId }}" @endif
     title="Drag the grip to move · tap the pencil to edit">
    <span class="inline-note-grip" aria-hidden="true"><svg viewBox="0 0 24 24" fill="currentColor" class="w-3.5 h-3.5"><circle cx="9" cy="6" r="1.5"/><circle cx="15" cy="6" r="1.5"/><circle cx="9" cy="12" r="1.5"/><circle cx="15" cy="12" r="1.5"/><circle cx="9" cy="18" r="1.5"/><circle cx="15" cy="18" r="1.5"/></svg></span>
    <span class="inline-note-tag" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M7 3h10a2 2 0 012 2v14a2 2 0 01-2 2H7a2 2 0 01-2-2V5a2 2 0 012-2zM9 8h6M9 12h6M9 16h3"/></svg>Note</span>
    @if (filled($note->title))
        <div class="inline-note-title">{{ $note->title }}</div>
    @endif
    {{-- What this note is about. A pointer, not a sentence — chosen by
         tapping a lot or a task, so it can be filtered on later. --}}
    @php
        $tagLot = $note->lotId ? (($lotNames ?? [])[$note->lotId] ?? null) : null;
        $tagAct = $note->activityId ? (($activityNames ?? [])[$note->activityId] ?? null) : null;
    @endphp
    @if ($tagLot || $tagAct)
        <div class="inline-note-tags">
            @if ($tagLot)<span class="note-tag note-tag-lot">{{ $tagLot }}</span>@endif
            @if ($tagAct)<span class="note-tag note-tag-act">{{ $tagAct }}</span>@endif
        </div>
    @endif
    <div class="inline-note-body">{!! $note->content !!}</div>
    {{-- Attachments as chips, not a wall of thumbnails: a note about a broken
         pump is about the pump, and the photo is evidence you open when you
         want it. Mirrors buildInlineNote() in the JS renderer. --}}
    <div class="inline-note-media">@include('sm.partials.note-attachments', ['media' => $noteMedia])</div>
    <button type="button" class="inline-note-edit" title="Edit note" aria-label="Edit note">
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
    </button>
    <button type="button" class="inline-note-del" title="Delete note" aria-label="Delete note">
        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.9 12.1a2 2 0 01-2 1.9H7.9a2 2 0 01-2-1.9L5 7m3 0V5a2 2 0 012-2h4a2 2 0 012 2v2m-11 0h16"/></svg>
    </button>
</div>
