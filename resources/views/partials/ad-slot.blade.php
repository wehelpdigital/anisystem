{{-- AN ADVERTISEMENT, WHERE THE FREE PLAN CARRIES ONE.

         @include('partials.ad-slot', ['placement' => 'dashboard'])

     Draws nothing at all unless the person looking is one who sees ads
     (App\Support\Ads::showTo — a Libre account on its own farm, or a guest
     on the public pages) and a unit fits this surface. What it draws is
     the mother app's business (AniSystem > Ads): a Google AdSense slot, a
     picture that links somewhere, or a network's own script. The slot
     wears a label so it is never mistaken for the app's own content, and
     a way to the paid plans, which is the other half of why it is here.

     Optional: 'compact' => true for a tighter slot (the rail, a sheet). --}}
@php
    $adUnit = \App\Support\Ads::draw($placement ?? 'modules');
    $adSettings = \App\Support\Ads::settings();
    $adUpsellHref = auth()->check() ? route('purchase.plans') : route('signup');
@endphp
@if ($adUnit)
    @once
        <style>
            .ad-slot { position: relative; margin: 1rem 0; border-radius: 1rem; overflow: hidden;
                border: 1px dashed var(--color-gray-200, #e5e7eb); background: var(--color-white, #fff);
                animation: adSlotIn .38s cubic-bezier(.22,1,.36,1) both; }
            @keyframes adSlotIn { from { opacity: 0; transform: translateY(6px); } to { opacity: 1; transform: none; } }
            .ad-slot-head { display: flex; align-items: center; justify-content: space-between; gap: .75rem;
                padding: .35rem .75rem; font-size: .625rem; font-weight: 800; letter-spacing: .08em; text-transform: uppercase;
                color: var(--color-gray-400, #9ca3af); border-bottom: 1px dashed var(--color-gray-100, #f3f4f6); }
            .ad-slot-upsell { text-transform: none; letter-spacing: 0; font-size: .7rem; font-weight: 700;
                color: var(--color-brand-700, #3d6823); white-space: nowrap; }
            .ad-slot-upsell:hover { color: var(--color-brand-800, #2f5219); text-decoration: underline; }
            .ad-slot-body { display: flex; align-items: center; justify-content: center; min-height: 5rem; padding: .5rem; }
            .ad-slot-body .adsbygoogle { width: 100%; }
            .ad-slot-image { display: block; max-width: 100%; }
            .ad-slot-image img { display: block; max-width: 100%; height: auto; border-radius: .6rem; margin: 0 auto; }
            .ad-slot.is-compact { margin: .75rem 0; }
            .ad-slot.is-compact .ad-slot-body { min-height: 3.5rem; padding: .35rem; }
            html.dark .ad-slot { background: #151b12; border-color: #2b3a1c; }
            html.dark .ad-slot-head { border-color: #1f2917; color: #8a9a80; }
            html.dark .ad-slot-upsell { color: #a8cc7e; }
            @media (prefers-reduced-motion: reduce) { .ad-slot { animation: none; } }
        </style>
    @endonce
    <aside class="ad-slot ad-slot-{{ $adUnit->kind }} {{ ($compact ?? false) ? 'is-compact' : '' }} {{ $class ?? '' }}"
           data-ad-unit="{{ $adUnit->id }}" aria-label="{{ $adSettings->label }}">
        <div class="ad-slot-head">
            <span>{{ $adSettings->label }}</span>
            <a class="ad-slot-upsell" href="{{ $adUpsellHref }}">{{ $adSettings->upsell }} →</a>
        </div>
        <div class="ad-slot-body">
            @if ($adUnit->kind === 'adsense')
                <ins class="adsbygoogle" style="display:block"
                     data-ad-client="{{ $adUnit->adClient ?: \App\Support\Ads::adsenseClient() }}"
                     data-ad-slot="{{ $adUnit->adSlot }}"
                     data-ad-format="{{ $adUnit->adFormat ?: 'auto' }}"
                     data-full-width-responsive="true"></ins>
                <script>(window.adsbygoogle = window.adsbygoogle || []).push({});</script>
            @elseif ($adUnit->kind === 'image')
                @if (filled($adUnit->linkUrl))
                    <a class="ad-slot-image" href="{{ route('ads.go', ['id' => $adUnit->id]) }}" target="_blank" rel="noopener sponsored">
                        <img src="{{ $adUnit->imageUrl() }}" alt="{{ $adUnit->imageAlt ?: $adUnit->name }}" loading="lazy">
                    </a>
                @else
                    <span class="ad-slot-image"><img src="{{ $adUnit->imageUrl() }}" alt="{{ $adUnit->imageAlt ?: $adUnit->name }}" loading="lazy"></span>
                @endif
            @else
                {!! $adUnit->scriptHtml !!}
            @endif
        </div>
    </aside>
@endif
