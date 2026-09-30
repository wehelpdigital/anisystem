{{-- The feature pages as a product grid: an icon, a name, one line on what
     it does. Expects $pages (feature AsSitePage rows); optional $except (a
     slug to leave out) and $compact (smaller cards). --}}
@php
    $S = \App\Support\SitePages::class;
    $cards = $pages->filter(fn ($p) => ($except ?? null) !== $p->slug)->values();
@endphp
@once
@push('head')
<style>
    .fg-grid { display: grid; gap: 1rem; grid-template-columns: repeat(auto-fill, minmax(15.5rem, 1fr)); }
    .fg-card { position: relative; display: flex; flex-direction: column; gap: .55rem; padding: 1.35rem 1.3rem 1.25rem; border-radius: 1.1rem; background: #fff;
        border: 1px solid #e5ebdf; text-decoration: none; overflow: hidden; isolation: isolate;
        transition: transform .28s cubic-bezier(.22,1,.36,1), box-shadow .28s cubic-bezier(.22,1,.36,1), border-color .28s cubic-bezier(.22,1,.36,1); }
    .fg-card::before { content: ""; position: absolute; inset: 0 0 auto auto; width: 9rem; height: 9rem; border-radius: 999px; z-index: -1;
        background: radial-gradient(closest-side, hsl(var(--h) 70% 55% / .14), transparent); transform: translate(35%, -35%);
        transition: transform .28s cubic-bezier(.22,1,.36,1); }
    .fg-card:hover { transform: translateY(-3px); border-color: hsl(var(--h) 45% 72%); box-shadow: 0 20px 40px -28px hsl(var(--h) 40% 20% / .55); }
    .fg-card:hover::before { transform: translate(25%, -25%) scale(1.2); }
    .fg-ico { width: 2.9rem; height: 2.9rem; border-radius: .9rem; display: grid; place-items: center; color: hsl(var(--h) 60% 32%);
        background: linear-gradient(145deg, hsl(var(--h) 70% 94%), hsl(var(--h) 60% 86%)); box-shadow: inset 0 0 0 1px hsl(var(--h) 50% 80%); }
    .fg-ico svg { width: 1.45rem; height: 1.45rem; }
    .fg-card b { font-family: var(--font-heading); font-size: 1.05rem; line-height: 1.3; color: #14210c; margin-top: .25rem; }
    .fg-card p { font-size: .88rem; line-height: 1.55; color: #4b5563; }
    .fg-card .go { margin-top: auto; padding-top: .35rem; font-size: .82rem; font-weight: 800; color: hsl(var(--h) 55% 30%); display: inline-flex; align-items: center; gap: .25rem; }
    .fg-card .go svg { width: .9rem; height: .9rem; transition: transform .28s cubic-bezier(.22,1,.36,1); }
    .fg-card:hover .go svg { transform: translateX(3px); }
    .fg-grid.is-compact { grid-template-columns: repeat(auto-fill, minmax(13.5rem, 1fr)); }
    .fg-grid.is-compact .fg-card { padding: 1rem 1.05rem; }
    .fg-grid.is-compact .fg-card p { display: none; }
    @media (prefers-reduced-motion: reduce) { .fg-card, .fg-card::before, .fg-card .go svg { transition: none; } }
</style>
@endpush
@endonce
<div class="fg-grid {{ ! empty($compact) ? 'is-compact' : '' }}">
    @foreach ($cards as $p)
        @php $f = $S::feature($p); @endphp
        <a href="{{ $S::pageUrl($p) }}" class="fg-card" style="--h: {{ $f['hue'] }}">
            <span class="fg-ico"><svg fill="none" stroke="currentColor" stroke-width="1.9" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $f['icon'] }}"/></svg></span>
            <b>{{ $f['name'] }}</b>
            <p>{{ $f['blurb'] }}</p>
            <span class="go">Learn more <svg fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg></span>
        </a>
    @endforeach
</div>
