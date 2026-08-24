{{-- A compressed wall/comment video. Expects $src (public-disk path) and an
     optional $poster. Streams progressively (faststart) and never autoplays. --}}
@php
    $vurl = \App\Support\MediaStore::url($src);
    $vposter = !empty($poster) ? \App\Support\MediaStore::url($poster) : null;
@endphp
{{-- The box is the clip's own shape.
     It used to be full width with a height cap, which is a fixed rectangle:
     a portrait clip played in the middle of it with black down both sides,
     and a very wide one with black above and below. Letting the player size
     itself from the film and capping how tall it may get means the black is
     never seen, because there is none of the box left over. --}}
{{-- The wrapper is a plain block that takes the width it is given; the
     rounding and the black belong to the player, which is the only thing
     that should be visible. A wrapper carrying its own background is a
     rectangle behind the film, and a rectangle behind a film of another
     shape is the black edge this was reported as. --}}
<div class="post-video mt-2">
    {{-- The film decides the shape, not the still.
         A poster of a different shape than the clip it belongs to — and they
         do differ: a still cut before a rotation was applied, one carried over
         from another encode — sized the box wrong, so a landscape clip sat in
         a portrait frame until it was played and then jumped. The element is
         told the film's own ratio the moment its metadata arrives, which is
         before anybody presses play. --}}
    <video controls preload="metadata" playsinline class="post-video-el rounded-xl"
           onloadedmetadata="if(this.videoWidth&&this.videoHeight){this.style.setProperty('--vr', this.videoWidth / this.videoHeight);}"
           @if ($vposter) poster="{{ $vposter }}" @endif>
        <source src="{{ $vurl }}" type="video/mp4">
        Your browser can't play this video.
    </video>
</div>
