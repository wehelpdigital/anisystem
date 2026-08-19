{{-- One page of Global Gallery tiles, in the season Gallery's own tile shape:
     a square picture, what it was called, where it came from — plus the one
     thing a global shelf has to say that a season's shelf never does, which
     is which season it came from.

     Rendered with the page and, in spirit, by the loader below it — kept here
     so the first screenful and the rest cannot drift apart. Expects: $items. --}}
@foreach ($items as $it)
    @php
        $kind = (string) ($it['kind'] ?? 'image');
        $shot = $it['posterUrl'] ?: ($it['type'] === 'image' ? $it['url'] : null);
        // A drawing or a map opens where it can be worked on; everything else
        // opens in the lightbox, the same rule the season's Gallery follows.
        $goesToModule = in_array($kind, ['drawing', 'map'], true) && filled($it['href'] ?? null);
        $href = $goesToModule ? $it['href'] : $it['url'];
    @endphp
    <div class="ga-wrap">
        <a class="ga-item" href="{{ $href }}"
           @if (! $goesToModule && $it['type'] === 'image') data-lightbox
           @elseif (! $goesToModule) target="_blank" rel="noopener" @endif>
            <span class="ga-shot">
                @if ($shot)
                    <img src="{{ $shot }}" alt="" loading="lazy"
                         onload="this.classList.add('is-loaded')"
                         onerror="this.closest('.ga-shot')?.classList.add('is-gone'); this.remove();">
                @elseif ($it['type'] === 'video')
                    <video src="{{ $it['url'] }}#t=0.1" preload="metadata" playsinline muted
                           onloadeddata="this.classList.add('is-loaded')"
                           onerror="this.closest('.ga-shot')?.classList.add('is-gone'); this.remove();"></video>
                @endif
                @if ($it['type'] === 'video')
                    <span class="ga-play"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg></span>
                @endif
                <span class="ga-kind is-{{ $kind }}">{{ $kind }}</span>
            </span>
            <span class="ga-info">
                <span class="ga-it">{{ $it['title'] ?: 'Untitled' }}</span>
                <span class="gh-season">{{ $it['scheduleTitle'] }}</span>
                <span class="ga-is">{{ $it['source'] }}@if (! empty($it['when'])) · {{ $it['when'] }}@endif</span>
            </span>
        </a>
    </div>
@endforeach
