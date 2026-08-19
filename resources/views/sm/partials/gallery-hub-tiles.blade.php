{{-- One page of global-gallery tiles. Rendered with the page and, in spirit,
     by the loader below it — kept here so the first screenful and the rest
     cannot drift apart. Expects: $items. --}}
@foreach ($items as $it)
    @php $shot = $it['posterUrl'] ?: ($it['type'] === 'image' ? $it['url'] : null); @endphp
    <a class="gh-tile" href="{{ $it['url'] }}" @if ($it['type'] === 'image') data-lightbox @endif>
        <span class="gh-shot">
            @if ($shot)
                <img src="{{ $shot }}" alt="" loading="lazy">
            @else
                <span class="gh-blank">🎬</span>
            @endif
            <span class="gh-badge">{{ $it['kind'] }}</span>
        </span>
        <span class="gh-meta">
            <span class="gh-name">{{ $it['title'] ?: 'Untitled' }}</span>
            <span class="gh-sub">{{ $it['scheduleTitle'] }}</span>
        </span>
    </a>
@endforeach
