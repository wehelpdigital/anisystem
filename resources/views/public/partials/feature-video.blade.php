{{-- One feature video, dressed the same everywhere. Drop a real clip at
     public/videos/site/{slug}.mp4 and this frame picks it up on its own;
     until then every slug plays the branded placeholder. Expects: $slug,
     $poster (asset path), $label. --}}
@php
    $__vidSrc = file_exists(public_path("videos/site/{$slug}.mp4"))
        ? asset("videos/site/{$slug}.mp4")
        : asset('videos/site/placeholder.mp4');
@endphp
<div class="vid-card" x-data="{ playing: false }">
    <video x-ref="vid" poster="{{ asset($poster) }}" controls preload="none" playsinline
           @play="playing = true" @pause="playing = false" @ended="playing = false">
        <source src="{{ $__vidSrc }}" type="video/mp4">
        Sorry, your browser does not support embedded videos.
    </video>
    <button type="button" class="vid-play" x-show="!playing" @click="$refs.vid.play()"
            aria-label="Play: {{ $label }}">
        <span>
            <svg fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M8 5.14v13.72L19 12 8 5.14z"/></svg>
        </span>
    </button>
    <span class="vid-tag" x-show="!playing">
        <svg fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path d="M10 2a1 1 0 011 1v1.07A6 6 0 0116 10c0 4-3 6-6 8-3-2-6-4-6-8a6 6 0 015-5.93V3a1 1 0 011-1z"/></svg>
        {{ $label }}
    </span>
</div>
