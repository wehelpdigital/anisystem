{{-- A compressed wall/comment video. Expects $src (public-disk path) and an
     optional $poster. Streams progressively (faststart) and never autoplays. --}}
@php
    $vurl = \App\Support\MediaStore::url($src);
    $vposter = !empty($poster) ? \App\Support\MediaStore::url($poster) : null;
@endphp
<div class="post-video mt-2 rounded-xl overflow-hidden bg-black/90 max-w-full">
    <video controls preload="metadata" playsinline class="block w-full max-h-96 bg-black"
           @if ($vposter) poster="{{ $vposter }}" @endif>
        <source src="{{ $vurl }}" type="video/mp4">
        Your browser can't play this video.
    </video>
</div>
