{{-- What a note has attached, said rather than shown.

     A row of thumbnails looked like the note was mostly pictures, cost a
     download each before anyone asked to see them, and turned into a wall of
     grey boxes the moment a file went missing. A chip says what is there —
     photo, video, drawing, map — and opens the right thing when tapped: a
     photo and a video in the zoomable lightbox, a drawing in the Draw module
     where it can be changed, a map in Maps where it can be worked on.

     Expects $media = [['type'=>'image|video|drawing|map', 'url'=>…,
     'posterUrl'=>…, 'mapUrl'=>…, 'drawUrl'=>…], …] --}}
@php
    $atts = collect($media ?? [])->filter(fn ($m) => filled($m['url'] ?? null) || filled($m['mapUrl'] ?? null));
@endphp
@if ($atts->count())
    <div class="note-atts">
        @foreach ($atts as $m)
            @php $type = $m['type'] ?? 'image'; @endphp
            @if ($type === 'map' && ! empty($m['mapUrl']))
                <a class="na na-map" href="{{ $m['mapUrl'] }}" title="Open this map in the Maps module">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><path stroke-linecap="round" stroke-linejoin="round" d="M9 20l-5-2V6l5 2m0 12l6-2m-6 2V8m6 10l5 2V8l-5-2m0 12V6M9 8l6-2"/></svg>
                    <span>Map</span>
                </a>
            @elseif ($type === 'drawing')
                <a class="na na-draw" href="{{ $m['drawUrl'] ?? '#' }}" title="Open this drawing in the Draw module">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><path stroke-linecap="round" stroke-linejoin="round" d="M4 20l4-1L20 7a2 2 0 00-3-3L5 16l-1 4zM14 6l4 4"/></svg>
                    <span>Drawing</span>
                </a>
            @elseif ($type === 'video')
                <button type="button" class="na na-video" data-lb-type="video" data-lb-url="{{ $m['url'] }}" data-lb-poster="{{ $m['posterUrl'] ?? '' }}" title="Play this video">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10l4.55-2.28A1 1 0 0121 8.62v6.76a1 1 0 01-1.45.9L15 14M5 6h8a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2z"/></svg>
                    <span>Video</span>
                </button>
            @else
                <button type="button" class="na na-photo" data-lb-type="image" data-lb-url="{{ $m['url'] }}" title="Open this photo">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6a2 2 0 012-2h12a2 2 0 012 2v12a2 2 0 01-2 2H6a2 2 0 01-2-2V6z"/><path stroke-linecap="round" stroke-linejoin="round" d="M4 15l4-4 4 4 3-3 5 5"/><circle cx="9" cy="8.5" r="1.3"/></svg>
                    <span>Photo</span>
                </button>
            @endif
        @endforeach
    </div>
@endif
