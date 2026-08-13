@extends('layouts.app')

@section('title', 'Global Notes')
@section('page-title', 'Global Notes')
@section('page-subtitle', 'Everything you\'ve jotted down')
@section('back', route('sm.index'))

@push('head')
<style>
    .nh-tag { font-size:.62rem; font-weight:800; text-transform:uppercase; letter-spacing:.04em; padding:.1rem .4rem; border-radius:.35rem; }
    .nh-tag.global { background:#ede9fe; color:#6d28d9; }
    .nh-tag.schedule { background:#dcfce7; color:#15803d; }
    .nh-tag.day { background:#fef3c7; color:#b45309; }
    html.dark .nh-tag.global { background:rgb(109 40 217 / .2); color:#c4b5fd; }
    html.dark .nh-tag.schedule { background:rgb(21 128 61 / .2); color:#86efac; }
    html.dark .nh-tag.day { background:rgb(180 83 9 / .2); color:#eec155; }
    .nh-card { animation:nhIn .28s cubic-bezier(.22,1,.36,1) both; }
    @keyframes nhIn { from { opacity:0; transform:translateY(6px); } to { opacity:1; transform:none; } }
    @media (prefers-reduced-motion: reduce) { .nh-card { animation:none; } }
    .nh-draw-preview img { max-height:12rem; border-radius:.6rem; border:1px solid var(--color-gray-100); }
    .rich-text img { max-width:100%; max-height:12rem; border-radius:.5rem; }
    .nh-media { display:grid; grid-template-columns:repeat(auto-fill, minmax(6rem, 1fr)); gap:.4rem; margin-top:.5rem; }
    .nh-media .nm { position:relative; aspect-ratio:1; border-radius:.5rem; overflow:hidden; background:#000; }
    .nh-media .nm img, .nh-media .nm video { width:100%; height:100%; object-fit:cover; display:block; }
</style>
@endpush

@section('content')
<div class="flex items-center justify-between gap-2 mb-4">
    <p class="text-sm text-gray-500">All your notes — global, per-schedule and per-day — in one place, tags and all.</p>
    <button type="button" id="addNoteBtn" class="btn btn-primary btn-sm shrink-0">＋ New note</button>
</div>

@if ($notes->isEmpty())
    <div class="card p-8 text-center">
        <div class="empty-tile">🗒️</div>
        <p class="font-bold text-gray-900" style="font-family:var(--font-heading)">No notes yet</p>
        <p class="text-sm text-gray-500 mt-1">Tap “New note” to write, draw, add photos &amp; videos, or drop an emoji.</p>
    </div>
@endif

<div id="notesList" class="space-y-3">
    @foreach ($notes as $note)
        <div class="card p-4 nh-card" data-note-type="{{ $note['type'] }}" data-note-id="{{ $note['id'] }}">
            <div class="flex items-center gap-2 mb-1">
                <span class="nh-tag {{ $note['type'] }}">{{ $note['type'] }}</span>
                @if ($note['url'])
                    <a href="{{ $note['url'] }}" class="text-xs font-semibold text-brand-600 hover:text-brand-700">📍 {{ $note['address'] }} →</a>
                @else
                    <span class="text-xs text-gray-400">{{ $note['address'] }}</span>
                @endif
                <span class="text-xs text-gray-400 ms-auto">{{ $note['when'] }}</span>
                @if ($note['type'] === 'global')
                    <button type="button" class="text-gray-300 hover:text-red-500 nh-del" data-id="{{ $note['id'] }}" title="Delete">✕</button>
                @endif
            </div>
            <p class="font-bold text-gray-900">{{ $note['title'] }}</p>
            @if ($note['body'])
                <div class="text-sm text-gray-700 mt-1 rich-text whitespace-pre-line break-words">{!! \App\Support\CommunityText::safeHtml($note['body']) !!}</div>
            @endif
            @if ($note['imageUrl'])
                @include('sm.partials.note-attachments', ['media' => [
                    ['type' => 'image', 'url' => $note['imageUrl']],
                ]])
            @endif
            @if (! empty($note['media']))
                @include('sm.partials.note-attachments', ['media' => $note['media']])
            @endif
        </div>
    @endforeach
</div>

@include('sm.partials.draw-canvas')
@include('sm.partials.note-editor')
@include('sm.partials.note-lightbox')
@include('community.partials.video-js')
@endsection

@push('scripts')
<script>
(function notesHub() {
    const $ = (id) => document.getElementById(id);

    $('addNoteBtn').addEventListener('click', () => {
        if (typeof window.openNoteEditor !== 'function') { window.toast && toast('Editor unavailable.', 'error'); return; }
        window.openNoteEditor({
            title: 'New note',
            bodyHtml: '',
            media: [],
            imageUploadUrl: @json(route('notes.hub.image-upload')),
            videoUploadUrl: @json(route('notes.hub.video-upload')),
            drawUploadUrl: @json(route('notes.hub.draw')),
            onSave: async ({ body, media }) => {
                try {
                    await window.api(@json(route('notes.hub.store')), { method: 'POST', body: { body, media } });
                    window.toast && toast('Note saved.');
                    window.location.reload();
                } catch (err) { window.toast && toast(err.message, 'error'); }
            },
        });
    });

    // Delete a global note.
    $('notesList').addEventListener('click', async (e) => {
        const del = e.target.closest('.nh-del');
        if (!del) return;
        if (!confirm('Delete this note?')) return;
        try {
            await window.api(@json(route('notes.hub.destroy')) + '?id=' + del.getAttribute('data-id'), { method: 'DELETE' });
            del.closest('[data-note-id]')?.remove();
            window.toast && toast('Note deleted.');
        } catch (err) { window.toast && toast(err.message, 'error'); }
    });
})();
</script>
@endpush
