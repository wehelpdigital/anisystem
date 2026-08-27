{{-- The band a person is introduced on: their cover photo if they have set
     one, and the house green — deep, and turning slowly — if they never did
     or have since taken theirs down. Every surface that shows a member wears
     the same band, so a cover means the same thing on the wall, in the
     directory, on the dashboard and on a profile.

     Expects: $coverUser. Optional: $coverClass — the surface's own modifier,
     which is where the height and the bleed live; this partial only decides
     what is painted inside it. --}}
@php
    $cbPath = optional($coverUser)->coverPath;
    /* Where the photo is anchored vertically, exactly as the profile reads
     * it: a cover framed by its owner should be framed the same everywhere. */
    $cbPos = (int) (optional($coverUser)->coverPos ?? 50);
@endphp
<span class="mem-cover {{ $coverClass ?? '' }}" aria-hidden="true">
    @if (filled($cbPath))
        {{-- A cover whose file has gone would leave the browser's broken-image
             glyph across the top of the card. Taking the picture out leaves
             the green, which is what a member without one gets anyway. --}}
        <img src="{{ \App\Support\MediaStore::url($cbPath) }}" alt="" loading="lazy"
             style="object-position:50% {{ $cbPos }}%" onerror="this.remove()">
    @endif
</span>
