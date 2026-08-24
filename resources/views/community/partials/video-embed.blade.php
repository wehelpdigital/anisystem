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
<div class="post-video mt-2 rounded-xl overflow-hidden bg-black/90">
    <video controls preload="metadata" playsinline class="post-video-el"
           @if ($vposter) poster="{{ $vposter }}" @endif>
        <source src="{{ $vurl }}" type="video/mp4">
        Your browser can't play this video.
    </video>
</div>
