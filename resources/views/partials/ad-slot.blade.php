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
            /* The head wears a colour band like the other cards on the
               free screens (the owner's ask, 2026-09-16) -- a warm amber
               sweep so it reads as a sponsor's, not the farm's green.
               White on amber-800/700 clears 4.5:1 in either mode; the
               band is its own ground, so day and night look the same. */
            .ad-slot-head { display: flex; align-items: center; justify-content: space-between; gap: .75rem;
                padding: .5rem .8rem; font-size: .625rem; font-weight: 800; letter-spacing: .08em; text-transform: uppercase;
                color: #fff; text-shadow: 0 1px 1px rgb(0 0 0 / .18);
                --ad-1: #7c2d12; --ad-2: #9a3412; --ad-3: #b45309;
                background-image: linear-gradient(120deg, var(--ad-1), var(--ad-2) 28%, var(--ad-3) 52%, var(--ad-2) 76%, var(--ad-1));
                background-size: 220% 100%; animation: adSweep 11s ease-in-out infinite alternate; }
            @keyframes adSweep { from { background-position: 0% 50%; } to { background-position: 100% 50%; } }
            .ad-slot-head > span::before { content: '★'; margin-right: .35rem; opacity: .85; }
            .ad-slot-upsell { text-transform: none; letter-spacing: 0; font-size: .7rem; font-weight: 700;
                color: #fff; white-space: nowrap; padding: .15rem .55rem; border-radius: 999px;
                background: rgb(255 255 255 / .16); border: 1px solid rgb(255 255 255 / .28);
                transition: background .28s cubic-bezier(.22,1,.36,1); }
            .ad-slot-upsell:hover { background: rgb(255 255 255 / .28); color: #fff; }
            .ad-slot-body { display: flex; align-items: center; justify-content: center; min-height: 5rem; padding: .5rem; }
            .ad-slot-body .adsbygoogle { width: 100%; }
            .ad-slot-image { display: block; max-width: 100%; }
            .ad-slot-image img { display: block; max-width: 100%; height: auto; border-radius: .6rem; margin: 0 auto; }
            .ad-slot.is-compact { margin: .75rem 0; }
            .ad-slot.is-compact .ad-slot-body { min-height: 3.5rem; padding: .35rem; }
            html.dark .ad-slot { background: #151b12; border-color: #2b3a1c; }
            html.dark .ad-slot-head { --ad-1: #6c2710; --ad-2: #8a2e0f; --ad-3: #a1490a; }
            @media (prefers-reduced-motion: reduce) { .ad-slot, .ad-slot-head { animation: none; } }
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
