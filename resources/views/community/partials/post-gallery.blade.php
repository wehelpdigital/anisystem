{{-- The pictures on a post that carries more than one.

     Small, in columns, each one whole in the lightbox when it is tapped —
     the lightbox binds to [data-lightbox] img, so nothing here needs a
     handler of its own. A picture whose file has gone takes itself out
     rather than leaving the browser's broken-image glyph in the row.

     Expects: $shots (an array of stored paths). --}}
@php $pgShots = array_values(array_filter((array) ($shots ?? []))); @endphp
@if (count($pgShots) > 1)
    <div class="post-shots" data-lightbox>
        @foreach ($pgShots as $pgPath)
            <img src="{{ \App\Support\MediaStore::url($pgPath) }}" alt="" loading="lazy"
                 onerror="this.remove()">
        @endforeach
    </div>
@endif
