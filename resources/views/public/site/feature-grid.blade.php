{{-- The feature pages as a product grid. Expects $pages (feature AsSitePage
     rows); optional $except (a slug to leave out) and $compact (smaller
     cards, no screen).

     Each card (2026-10-07, the owner: "use the icons in the app"): the
     icon the tool wears in the app, its name and one line, and on the full
     size a glimpse of its real screen rising from the card's top. Cards
     carry their category for the filter on /features. --}}
@php
    $S = \App\Support\SitePages::class;
    $HW = \App\Support\HowItWorks::class;
    $cards = $pages->filter(fn ($p) => ($except ?? null) !== $p->slug)->values();
    $fgNew = ['satellite-analysis', 'satellite-weather', 'npk-plus-calculator', 'pest-and-disease-finders', 'the-stash'];
    $fgShot = function ($p) use ($S) {
        $h = is_array($p->heroImage) ? $p->heroImage : [];
        $src = (string) ($h['src'] ?? '');
        if ($src === '' || preg_match('#^https?://#i', $src)) {
            return null;
        }
        $small = preg_replace('#\.(jpe?g|png|webp)$#i', '-480.webp', $src);

        return $S::img(is_file(public_path(ltrim($small, '/'))) ? $small : $src);
    };
@endphp
@once
@push('head')
<style>
    .fg-grid { display: grid; gap: 1.1rem; grid-template-columns: repeat(auto-fill, minmax(15.5rem, 1fr)); }
    .fg-card { position: relative; display: flex; flex-direction: column; border-radius: 1.4rem; background: #fff; border: 1px solid #e5ebdf; text-decoration: none;
        overflow: hidden; isolation: isolate; box-shadow: 0 18px 40px -36px rgb(20 33 12 / .5);
        transition: transform .28s cubic-bezier(.22,1,.36,1), box-shadow .28s cubic-bezier(.22,1,.36,1), border-color .28s cubic-bezier(.22,1,.36,1), opacity .28s cubic-bezier(.22,1,.36,1); }
    .fg-card:hover { transform: translateY(-4px); border-color: hsl(var(--h) 45% 72%); box-shadow: 0 28px 50px -32px hsl(var(--h) 40% 20% / .6); }
    /* The screen, rising out of a soft band in the card's own colour. */
    .fg-shot { position: relative; height: 9.5rem; overflow: hidden;
        background: radial-gradient(80% 120% at 50% 110%, hsl(var(--h) 65% 82%), transparent 70%), linear-gradient(160deg, hsl(var(--h) 60% 96%), hsl(var(--h) 45% 90%)); }
    .fg-shot::after { content: ""; position: absolute; inset: auto 0 0; height: 2.2rem; background: linear-gradient(180deg, transparent, #fff); }
    .fg-shot img { position: absolute; left: 50%; top: 1.1rem; width: 62%; translate: -50% 0; border-radius: 1rem 1rem 0 0; background: #fff;
        box-shadow: 0 0 0 4px #1d2a15, 0 24px 40px -18px rgb(20 33 12 / .55); transition: transform .5s cubic-bezier(.22,1,.36,1); }
    .fg-card:hover .fg-shot img { transform: translateY(-.6rem) rotate(-1.5deg); }
    .fg-body { position: relative; display: flex; flex: 1; flex-direction: column; gap: .45rem; padding: 0 1.25rem 1.2rem; }
    .fg-ico { width: 3.1rem; height: 3.1rem; margin-top: -1.55rem; border-radius: 1rem; display: grid; place-items: center; background: #fff;
        box-shadow: 0 0 0 1px hsl(var(--h) 40% 86%), 0 10px 22px -12px hsl(var(--h) 40% 25% / .55); transition: transform .28s cubic-bezier(.22,1,.36,1); }
    .fg-ico img { width: 2.05rem; height: 2.05rem; object-fit: contain; }
    .fg-ico img.is-face { width: 100%; height: 100%; border-radius: 1rem; object-fit: cover; }
    .fg-ico svg { width: 1.45rem; height: 1.45rem; color: hsl(var(--h) 60% 32%); }
    .fg-card:hover .fg-ico { transform: translateY(-3px) rotate(-4deg); }
    .fg-card b { font-family: var(--font-heading); font-size: 1.08rem; line-height: 1.3; color: #14210c; margin-top: .25rem; }
    .fg-card p { font-size: .88rem; line-height: 1.55; color: #4b5563; }
    .fg-card .go { margin-top: auto; padding-top: .35rem; font-size: .82rem; font-weight: 800; color: hsl(var(--h) 55% 30%); display: inline-flex; align-items: center; gap: .25rem; }
    .fg-card .go svg { width: .9rem; height: .9rem; transition: transform .28s cubic-bezier(.22,1,.36,1); }
    .fg-card:hover .go svg { transform: translateX(3px); }
    .fg-new { position: absolute; z-index: 2; top: .8rem; right: .8rem; padding: .18rem .55rem; border-radius: 999px; font-size: .64rem; font-weight: 900; letter-spacing: .08em;
        text-transform: uppercase; color: #1f1500; background: #f5c518; box-shadow: 0 6px 14px -6px rgb(120 90 0 / .6); }
    /* The filter on /features: cards leave and come back softly. */
    .fg-card.is-out { display: none; }
    .fg-card.is-in { animation: fgIn .42s cubic-bezier(.22,1,.36,1) both; animation-delay: calc(var(--n, 0) * 28ms); }
    @keyframes fgIn { from { opacity: 0; transform: translateY(14px) scale(.98); } to { opacity: 1; transform: none; } }
    /* A phone: two to a row, the name and the screen (the line is in the guide). */
    @media (max-width: 559.98px) {
        .fg-grid:not(.is-compact) { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .7rem; }
        .fg-grid:not(.is-compact) .fg-shot { height: 7rem; }
        .fg-grid:not(.is-compact) .fg-shot img { width: 72%; top: .8rem; }
        .fg-grid:not(.is-compact) .fg-body { padding: 0 .8rem .9rem; gap: .3rem; }
        .fg-grid:not(.is-compact) .fg-ico { width: 2.6rem; height: 2.6rem; margin-top: -1.3rem; }
        .fg-grid:not(.is-compact) .fg-ico img { width: 1.7rem; height: 1.7rem; }
        .fg-grid:not(.is-compact) .fg-card b { font-size: .94rem; }
        .fg-grid:not(.is-compact) .fg-card p { display: none; }
        .fg-grid:not(.is-compact) .fg-card .go { font-size: .76rem; }
    }
    /* Compact: no screen, the icon beside the words. */
    .fg-grid.is-compact { grid-template-columns: repeat(auto-fill, minmax(13.5rem, 1fr)); }
    .fg-grid.is-compact .fg-shot { display: none; }
    .fg-grid.is-compact .fg-body { padding: 1rem 1.05rem; }
    .fg-grid.is-compact .fg-ico { margin-top: 0; width: 2.6rem; height: 2.6rem; }
    .fg-grid.is-compact .fg-ico img { width: 1.7rem; height: 1.7rem; }
    .fg-grid.is-compact .fg-card p { display: none; }
    @media (prefers-reduced-motion: reduce) { .fg-card, .fg-shot img, .fg-ico, .fg-card .go svg { transition: none; } .fg-card.is-in { animation: none; } }
</style>
@endpush
@endonce
<div class="fg-grid {{ ! empty($compact) ? 'is-compact' : '' }}" data-fg>
    @foreach ($cards as $i => $p)
        @php
            $f = $S::feature($p);
            $icon = $HW::iconOfPage($p->slug);
            $shot = empty($compact) ? $fgShot($p) : null;
        @endphp
        <a href="{{ $S::pageUrl($p) }}" class="fg-card" style="--h: {{ $f['hue'] }}; --n: {{ $i }}" data-cat="{{ $p->category }}">
            @if (in_array($p->slug, $fgNew, true) && empty($compact))<span class="fg-new">New</span>@endif
            @if ($shot)<span class="fg-shot"><img src="{{ $shot }}" alt="" loading="lazy" decoding="async" width="480" height="935"></span>@endif
            <div class="fg-body">
                <span class="fg-ico" @if (! $shot) style="margin-top: 0" @endif>
                    @if ($icon)
                        <img src="{{ asset('images/' . $icon) }}" alt="" class="{{ str_starts_with($icon, 'anee/') ? 'is-face' : '' }}" loading="lazy" width="40" height="40">
                    @else
                        <svg fill="none" stroke="currentColor" stroke-width="1.9" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $f['icon'] }}"/></svg>
                    @endif
                </span>
                <b>{{ $f['name'] }}</b>
                <p>{{ $f['blurb'] }}</p>
                <span class="go">Read the guide <svg fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg></span>
            </div>
        </a>
    @endforeach
</div>
