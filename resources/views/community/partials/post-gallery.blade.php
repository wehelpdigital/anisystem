{{-- The pictures on a post that carries more than one.

     A carousel rather than a wall of thumbnails: several photos are usually
     one thing said from three angles, and a stack of them turned a post into
     a page. They come round slowly on their own, and a thumb can push them
     either way at any time — the auto-advance gets out of the way the moment
     somebody touches it, because a picture that moves while you are looking
     at it is a picture you cannot look at.

     Tapping one opens it whole in the lightbox; the lightbox binds to
     [data-lightbox] img, so nothing here needs a handler of its own. A
     picture whose file has gone takes itself out rather than leaving the
     browser's broken-image glyph in the row.

     Expects: $shots (an array of stored paths). --}}
@php $pgShots = array_values(array_filter((array) ($shots ?? []))); @endphp
@if (count($pgShots) > 1)
    <div class="post-carousel{{ ($mini ?? false) ? ' pc-mini' : '' }}" data-shots data-lightbox>
        <div class="pc-track">
            @foreach ($pgShots as $pgPath)
                <img src="{{ \App\Support\MediaStore::url($pgPath) }}" alt="" loading="lazy"
                     onerror="this.remove()">
            @endforeach
        </div>
        <span class="pc-count" aria-hidden="true"><b>1</b>/{{ count($pgShots) }}</span>
        <div class="pc-dots" aria-hidden="true">
            @foreach ($pgShots as $i => $pgPath)
                <span class="pc-dot{{ $i === 0 ? ' is-on' : '' }}"></span>
            @endforeach
        </div>
    </div>
@endif
