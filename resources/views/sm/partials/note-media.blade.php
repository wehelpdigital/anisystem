{{-- Canonical note attachment thumbnails (server render). Click opens the
     shared lightbox (needs note-lightbox on the page). Expects:
     $media = [['type'=>'image|video','url'=>..,'posterUrl'=>..], ...]. --}}
@foreach (($media ?? []) as $m)
    @if (($m['type'] ?? '') === 'video')
        <div class="nm nm-video" data-lb-type="video" data-lb-url="{{ $m['url'] }}" data-lb-poster="{{ $m['posterUrl'] ?? '' }}">
            @if (! empty($m['posterUrl']))<img src="{{ $m['posterUrl'] }}" alt="" onload="this.classList.add('is-loaded')">@endif
            <span class="nm-play"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg></span>
        </div>
    @else
        <div class="nm" data-lb-type="image" data-lb-url="{{ $m['url'] }}"><img src="{{ $m['url'] }}" alt="" loading="lazy" onload="this.classList.add('is-loaded')"></div>
    @endif
@endforeach
