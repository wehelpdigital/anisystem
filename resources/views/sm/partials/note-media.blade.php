{{-- Canonical note attachment thumbnails (server render). Click opens the
     shared lightbox (needs note-lightbox on the page). Expects:
     $media = [['type'=>'image|video','url'=>..,'posterUrl'=>..], ...]. --}}
@foreach (($media ?? []) as $m)
    @if (($m['type'] ?? '') === 'map' && ! empty($m['mapUrl']))
        {{-- A saved map opens in the Maps module: it is a place to work on,
             not a picture to squint at, and the module can pan, measure and
             draw where a thumbnail cannot. --}}
        <a class="nm nm-map" href="{{ $m['mapUrl'] ?? '#' }}" title="Open this map in the Maps module">
            <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 20l-5-2V6l5 2m0 12l6-2m-6 2V8m6 10l5 2V8l-5-2m0 12V6M9 8l6-2"/></svg>
            <span>View map</span>
        </a>
    @elseif (($m['type'] ?? '') === 'drawing')
        <div class="nm nm-draw" data-lb-type="image" data-lb-url="{{ $m['url'] }}">
            <img src="{{ $m['url'] }}" alt="" loading="lazy" onload="this.classList.add('is-loaded')" onerror="this.closest('.nm')?.classList.add('is-gone'); this.remove();">
            <span class="nm-badge"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 20l4-1 10-10-3-3L5 16l-1 4z"/></svg></span>
        </div>
    @elseif (($m['type'] ?? '') === 'video')
        <div class="nm nm-video" data-lb-type="video" data-lb-url="{{ $m['url'] }}" data-lb-poster="{{ $m['posterUrl'] ?? '' }}">
            @if (! empty($m['posterUrl']))<img src="{{ $m['posterUrl'] }}" alt="" onload="this.classList.add('is-loaded')">@endif
            <span class="nm-play"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg></span>
        </div>
    @else
        <div class="nm" data-lb-type="image" data-lb-url="{{ $m['url'] }}"><img src="{{ $m['url'] }}" alt="" loading="lazy" onload="this.classList.add('is-loaded')" onerror="this.closest('.nm')?.classList.add('is-gone'); this.remove();"></div>
    @endif
@endforeach
