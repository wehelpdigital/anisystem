{{-- A header's photo (2026-10-22, the owner: "header pages like the pricing
     has no background. add one instead of just the green color"). A farm
     picture fills the band behind the words, drifting slowly, under a deep
     green shade dark enough for the white heading and the gold kicker.
     Include it as the first thing inside a `relative isolate overflow-hidden`
     section: ['photo' => base name in images/site/photos (with -480/-800
     .webp copies beside the .jpg), 'pos' => object-position, 'lazy' => true for a band lower down]. --}}
@php
    $hpBase = 'images/site/photos/' . $photo;
@endphp
<img src="{{ asset($hpBase . '.jpg') }}" srcset="{{ asset($hpBase . '-480.webp') }} 480w, {{ asset($hpBase . '-800.webp') }} 800w, {{ asset($hpBase . '.jpg') }} 1600w" sizes="100vw"
     alt="" class="hero-photo" style="object-position: {{ $pos ?? '50% 40%' }}" @if ($lazy ?? false) loading="lazy" @else loading="eager" fetchpriority="high" @endif>
<div class="hero-photo-shade" aria-hidden="true"></div>
@once
@push('head')
<style>
    .hero-photo { position: absolute; inset: 0; z-index: -2; width: 100%; height: 100%; object-fit: cover; transform: scale(1.05);
        animation: heroPhotoDrift 28s ease-in-out infinite alternate; }
    @keyframes heroPhotoDrift { to { transform: scale(1.11) translate(-1.2%, -.8%); } }
    .hero-photo-shade { position: absolute; inset: 0; z-index: -1; pointer-events: none;
        background: radial-gradient(42rem 18rem at 50% 50%, rgb(8 18 4 / .5), transparent 80%),
            linear-gradient(180deg, rgb(14 28 8 / .74) 0%, rgb(20 40 11 / .56) 50%, rgb(14 28 8 / .8) 100%); }
    @media (prefers-reduced-motion: reduce) { .hero-photo { animation: none !important; } }
</style>
@endpush
@endonce
