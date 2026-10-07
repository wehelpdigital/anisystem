@extends('layouts.public')

@include('public.partials.site-css')

@section('title', \App\Support\Region::ph() ? 'Farm App Features: Calendar, Satellite, NPK and Anee' : 'Farm Management App Features')
@section('meta_description', \App\Support\Region::ph()
    ? 'Every anee.io feature for Filipino farmers: the cropping calendar, satellite field health, typhoon watch, the NPK calculator, pest finders and Anee.'
    : 'Every anee.io feature: a cropping calendar by day count, satellite field health, storm tracking, a fertilizer calculator, workers, reports and Anee.')

@php
    $R = \App\Support\Region::class;
    $ph = $R::ph();
    $fCount = $featurePages->count();
    $fNew = ['satellite-analysis', 'satellite-weather', 'npk-plus-calculator', 'pest-and-disease-finders', 'the-stash'];
    // The filter's order; only the categories the pages use are shown.
    $fCats = collect(['Planning', 'Agronomy', 'Weather', 'Crop care', 'Anee', 'Workers', 'Records', 'Money', 'Community', 'Resources'])
        ->filter(fn ($c) => $featurePages->contains('category', $c))->values();
@endphp

@push('head')
<style>
    /* ---- FEATURES (redrawn 2026-10-07) ------------------------------------
       A hero that moves, a band of every feature sliding past (each one
       opens its guide), every feature as a card with a filter by part of
       the farm, Anee, then the way in. The tour and its sticky chip bar were
       taken out at the owner's word (2026-10-07). The house curve
       throughout; still when asked. */
    .fz-hero { position: relative; isolation: isolate; overflow: hidden; color: #eef4e6; background: radial-gradient(70rem 40rem at 85% -10%, #4a7c2a 0%, transparent 60%), linear-gradient(160deg, #1d3310, #0d1609 70%); }
    .fz-hero::before { content: ''; position: absolute; inset: 0; z-index: -1; background-image: radial-gradient(rgb(255 255 255 / .1) 1px, transparent 1.5px); background-size: 22px 22px;
        -webkit-mask-image: linear-gradient(180deg, #000, transparent 85%); mask-image: linear-gradient(180deg, #000, transparent 85%); }
    .fz-orb { position: absolute; z-index: -1; border-radius: 999px; filter: blur(40px); opacity: .5; animation: fzDrift 14s ease-in-out infinite alternate; }
    .fz-orb.a { width: 22rem; height: 22rem; left: -6rem; top: 8rem; background: #f5c518; opacity: .14; }
    .fz-orb.b { width: 28rem; height: 28rem; right: -8rem; bottom: -10rem; background: #66bd63; opacity: .2; animation-duration: 18s; }
    @keyframes fzDrift { to { transform: translate(3rem, -2rem) scale(1.1); } }
    .fz-hero-in { display: grid; gap: 2.5rem; align-items: center; }
    @media (min-width: 1024px) { .fz-hero-in { grid-template-columns: minmax(0, 1.05fr) minmax(0, .95fr); } }
    .fz-kick { display: inline-flex; align-items: center; gap: .5rem; font-size: .78rem; font-weight: 900; letter-spacing: .14em; text-transform: uppercase; color: #f5c518; }
    .fz-kick i { width: .5rem; height: .5rem; border-radius: 999px; background: #f5c518; box-shadow: 0 0 0 .3rem rgb(245 197 24 / .2); animation: fzPulse 2s ease-in-out infinite; }
    @keyframes fzPulse { 50% { box-shadow: 0 0 0 .55rem rgb(245 197 24 / 0); } }
    .fz-h1 { margin-top: .7rem; font-family: var(--font-heading); font-weight: 800; color: #fff; font-size: clamp(2.3rem, 5.4vw, 3.9rem); line-height: 1.04; letter-spacing: -.02em; text-wrap: balance; }
    .fz-h1 em { font-style: normal; background: linear-gradient(100deg, #c08a12, #f7d774 30%, #fff3b8 45%, #e0aa2a 65%, #f7d774); -webkit-background-clip: text; background-clip: text; color: transparent;
        background-size: 200% 100%; animation: fzGold 7s ease-in-out infinite alternate; }
    @keyframes fzGold { to { background-position: 100% 50%; } }
    .fz-lede { margin-top: 1.1rem; max-width: 36rem; font-size: 1.05rem; line-height: 1.7; color: #c9dcb5; }
    .fz-stats { margin-top: 1.6rem; display: flex; flex-wrap: wrap; gap: 1.6rem; }
    .fz-stats div b { display: block; font-family: var(--font-heading); font-size: 2.1rem; font-weight: 800; line-height: 1; color: #fff; }
    .fz-stats div small { display: block; margin-top: .35rem; font-size: .78rem; font-weight: 700; color: #a8cc7e; }
    .fz-cta { margin-top: 1.8rem; display: flex; flex-wrap: wrap; gap: .7rem; }
    .fz-phones { position: relative; height: 31rem; }
    .fz-phones .ph-frame { position: absolute; width: min(240px, 46vw); }
    .fz-phones .p1 { left: 50%; top: 0; transform: translateX(-50%); z-index: 3; animation: fzFloat 6s ease-in-out infinite; }
    .fz-phones .p2 { left: 2%; top: 3.5rem; transform: rotate(-8deg); z-index: 2; opacity: .92; animation: fzFloat2 7s ease-in-out infinite; }
    .fz-phones .p3 { right: 2%; top: 3.5rem; transform: rotate(8deg); z-index: 2; opacity: .92; animation: fzFloat3 7.5s ease-in-out infinite; }
    @keyframes fzFloat { 50% { transform: translateX(-50%) translateY(-10px); } }
    @keyframes fzFloat2 { 50% { transform: rotate(-8deg) translateY(-8px); } }
    @keyframes fzFloat3 { 50% { transform: rotate(8deg) translateY(-8px); } }
    /* Below a desk the box is as tall as the phones it holds (a frame is
       about 1.95 times as tall as it is wide), so the tilted side phones
       end above the band of features instead of sitting on its chips. */
    @media (max-width: 1023.98px) {
        .fz-phones { height: calc(min(200px, 44vw) * 1.95 + 2.75rem); }
        .fz-phones .ph-frame { width: min(200px, 44vw); }
        .fz-phones .p2, .fz-phones .p3 { top: 2rem; }
    }
    @media (max-width: 479.98px) { .fz-phones .p2 { left: -4%; } .fz-phones .p3 { right: -4%; } }
    /* A phone: the three numbers side by side, not two and one. */
    @media (max-width: 639.98px) {
        .fz-stats { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 1rem; }
        .fz-stats div b { font-size: 1.85rem; }
        .fz-stats div small { line-height: 1.35; }
    }

    /* Every feature, sliding past. */
    .fz-band { position: relative; overflow: hidden; padding: 1rem 0; background: #0d1609; border-top: 1px solid rgb(255 255 255 / .06); border-bottom: 1px solid rgb(255 255 255 / .06);
        -webkit-mask-image: linear-gradient(90deg, transparent, #000 8%, #000 92%, transparent); mask-image: linear-gradient(90deg, transparent, #000 8%, #000 92%, transparent); }
    .fz-track { display: flex; gap: .55rem; width: max-content; animation: fzSlide 190s linear infinite; }
    .fz-track.rev { animation-direction: reverse; animation-duration: 220s; margin-top: .55rem; }
    .fz-band:hover .fz-track, .fz-band:focus-within .fz-track { animation-play-state: paused; }
    .fz-track a { flex: none; text-decoration: none; transition: background-color .28s cubic-bezier(.22,1,.36,1), color .28s cubic-bezier(.22,1,.36,1), border-color .28s cubic-bezier(.22,1,.36,1); }
    .fz-track a:hover, .fz-track a:focus-visible { color: #fff; background: rgb(255 255 255 / .14); border-color: rgb(255 255 255 / .3); }
    .fz-track a.is-new:hover, .fz-track a.is-new:focus-visible { color: #1f1500; background: #ffd84a; }
    .fz-track a { flex: none; padding: .5rem .95rem; border-radius: 999px; font-size: .85rem; font-weight: 800; color: #dbe7cf; background: rgb(255 255 255 / .06); border: 1px solid rgb(255 255 255 / .1); white-space: nowrap; }
    .fz-track a.is-new { color: #1f1500; background: #f5c518; border-color: #f5c518; }
    @keyframes fzSlide { to { transform: translateX(-50%); } }

    /* Every feature, filtered by part of the farm. */
    .fz-sec { scroll-margin-top: 5rem; }
    .fz-cats { display: flex; flex-wrap: wrap; gap: .45rem; margin-top: 1.6rem; }
    .fz-cats button { display: inline-flex; align-items: center; gap: .4rem; padding: .5rem .9rem; border-radius: 999px; border: 1px solid #e1ead6; background: #fff; cursor: pointer;
        font-size: .86rem; font-weight: 800; color: #374151; transition: background-color .28s cubic-bezier(.22,1,.36,1), color .28s cubic-bezier(.22,1,.36,1), border-color .28s cubic-bezier(.22,1,.36,1), transform .28s cubic-bezier(.22,1,.36,1); }
    .fz-cats button:hover { border-color: #a8cc7e; transform: translateY(-1px); }
    .fz-cats button b { min-width: 1.4rem; padding: .05rem .4rem; border-radius: 999px; font-size: .72rem; text-align: center; background: #eef6e6; color: #2f5219; }
    .fz-cats button[aria-pressed="true"] { background: #2d5016; border-color: #2d5016; color: #fff; }
    .fz-cats button[aria-pressed="true"] b { background: #f5c518; color: #1f1500; }
    /* A phone: the same chips a size smaller, so the parts of the farm fit in three rows. */
    @media (max-width: 639.98px) {
        .fz-cats { gap: .4rem; margin-top: 1.3rem; }
        .fz-cats button { min-height: 2.5rem; padding: .4rem .7rem; font-size: .82rem; gap: .35rem; }
    }

    @media (prefers-reduced-motion: reduce) {
        .fz-orb, .fz-kick i, .fz-h1 em, .fz-phones .ph-frame, .fz-track { animation: none !important; }
        .fz-track { flex-wrap: wrap; width: auto; justify-content: center; }
        .fz-track.rev { display: none; }
        .fz-cats button { transition: none !important; }
    }
</style>
@endpush

@section('content')

    {{-- ================= HERO ================= --}}
    <section class="fz-hero">
        <i class="fz-orb a" aria-hidden="true"></i><i class="fz-orb b" aria-hidden="true"></i>
        <div class="max-w-6xl mx-auto px-4 sm:px-6 pt-14 pb-12 sm:pt-20 sm:pb-16 fz-hero-in">
            <div class="animate-fade-up">
                <span class="fz-kick"><i aria-hidden="true"></i>Every feature</span>
                <h1 class="fz-h1">Everything Your Farm <em>Runs On.</em></h1>
                <p class="fz-lede">
                    One farm app for the whole season: the cropping calendar, your field seen from space, the typhoon on its way, every nutrient in your fertilizer,
                    your workers and every {{ $ph ? 'peso' : 'dollar' }}, with Anee, your smart farm technician, beside you. Every screen below is the real thing.
                </p>
                <div class="fz-stats">
                    <div><b>{{ $fCount }}</b><small>features, each with its own guide</small></div>
                    <div><b>{{ $ph ? '85' : '100' }}</b><small>crops it knows by stage</small></div>
                    <div><b>2</b><small>satellites over your field</small></div>
                </div>
                <div class="fz-cta">
                    <a href="{{ route('signup') }}" class="btn btn-accent btn-lg">Start free</a>
                    <a href="#all" class="btn btn-lg btn-on-dark">See every feature</a>
                </div>
            </div>
            <div class="fz-phones" aria-hidden="true">
                <span class="ph-frame p2"><img src="{{ asset('images/site/app/board.png') }}" alt="" loading="lazy" width="780" height="1520"></span>
                <span class="ph-frame p3"><img src="{{ asset('images/site/app/npk.webp') }}" alt="" loading="lazy" width="780" height="1520"></span>
                <span class="ph-frame p1"><img src="{{ asset('images/site/app/sky.webp') }}" alt="" width="780" height="1520"></span>
            </div>
        </div>
        {{-- Every feature, sliding past slowly; each chip opens its guide. --}}
        <nav class="fz-band" aria-label="Every feature">
            <div class="fz-track">
                @foreach ($featurePages->concat($featurePages) as $i => $fp)
                    <a href="{{ \App\Support\SitePages::pageUrl($fp) }}" class="{{ in_array($fp->slug, $fNew, true) ? 'is-new' : '' }}" @if ($i >= $fCount) aria-hidden="true" tabindex="-1" @endif>{{ \App\Support\SitePages::feature($fp)['name'] }}</a>
                @endforeach
            </div>
            <div class="fz-track rev" aria-hidden="true">
                @foreach ($featurePages->reverse()->concat($featurePages->reverse()) as $fp)
                    <a href="{{ \App\Support\SitePages::pageUrl($fp) }}" class="{{ in_array($fp->slug, $fNew, true) ? 'is-new' : '' }}" tabindex="-1">{{ \App\Support\SitePages::feature($fp)['name'] }}</a>
                @endforeach
            </div>
        </nav>
    </section>

    {{-- ================= EVERY FEATURE, ONE CARD EACH ================= --}}
    @if ($featurePages->isNotEmpty())
    <section class="relative py-14 sm:py-16 bg-[#fbfcf9] border-b border-gray-100 fz-sec" id="all">
        <div class="max-w-6xl mx-auto px-4 sm:px-6">
            <div class="max-w-2xl reveal">
                <p class="text-sm font-bold uppercase tracking-wider text-brand-600">Everything in one farm app</p>
                <h2 class="mt-2 font-heading text-2xl sm:text-3xl font-bold text-ink text-balance">{{ $fCount }} Features for One Cropping Season</h2>
                <p class="mt-3 text-gray-600">Each one works on its own and with the rest: plan the season, keep the records, and ask for advice from the same phone. Pick a part of the farm, then open any feature for its full guide.</p>
            </div>
            <div class="fz-cats reveal" role="group" aria-label="Show features for">
                <button type="button" data-cat="" aria-pressed="true">All <b>{{ $fCount }}</b></button>
                @foreach ($fCats as $c)
                    <button type="button" data-cat="{{ $c }}" aria-pressed="false">{{ $c }} <b>{{ $featurePages->where('category', $c)->count() }}</b></button>
                @endforeach
            </div>
            <div class="mt-6">@include('public.site.feature-grid', ['pages' => $featurePages])</div>
        </div>
    </section>
    @endif

    {{-- ================= ANEE ================= --}}
    <section class="anee-band fz-sec" id="anee">
        <div class="relative max-w-4xl mx-auto px-4 sm:px-6 py-16 sm:py-20 text-center">
            <p class="text-sm font-bold uppercase tracking-wider text-accent-400 reveal">And through all of it</p>
            <h2 class="mt-2 font-heading text-3xl sm:text-4xl font-bold text-white text-balance reveal">Anee, the smart farm technician who knows your farm</h2>
            <p class="mt-4 text-[#cdd8c0] leading-relaxed max-w-2xl mx-auto reveal">
                She reads your schedules, stages and weather before answering. Ask in {{ $ph ? 'Tagalog or English' : 'plain English' }},
                send a photo of the problem, run When to Plant and What to Plant for your town, read your field from space,
                check your fertilizer plan, or have her write the whole season's report. Anee runs on credits, so you pay only for what you ask.
            </p>
            <div class="mt-8 reveal">
                <a href="{{ route('pricing') }}" class="btn btn-accent btn-lg">See plans and credits</a>
            </div>
        </div>
    </section>

    {{-- ================= CTA ================= --}}
    <section class="py-16 sm:py-20 bg-white">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 text-center reveal">
            <h2 class="font-heading text-3xl sm:text-4xl font-bold text-ink text-balance">Bring your next season here</h2>
            <p class="mt-4 text-gray-600">Set up your first cropping schedule in minutes: lots, workers and the whole calendar. Libre is free forever.</p>
            <div class="mt-8 flex flex-col sm:flex-row justify-center gap-3">
                <a href="{{ route('signup') }}" class="btn btn-primary btn-lg">Get Started</a>
                <a href="{{ route('tutorial') }}" class="btn btn-outline btn-lg">Watch the tutorial</a>
            </div>
        </div>
    </section>

@endsection

@push('scripts')
<script>
(() => {
    /* The filter: the cards of one part of the farm, coming back softly. */
    const bar = document.querySelector('.fz-cats');
    const cards = [...document.querySelectorAll('#all .fg-card')];
    if (!bar || !cards.length) return;
    bar.addEventListener('click', (e) => {
        const b = e.target.closest('button[data-cat]');
        if (!b) return;
        bar.querySelectorAll('button').forEach((x) => x.setAttribute('aria-pressed', String(x === b)));
        let n = 0;
        cards.forEach((c) => {
            const show = !b.dataset.cat || c.dataset.cat === b.dataset.cat;
            c.classList.toggle('is-out', !show);
            c.classList.remove('is-in');
            if (show) { c.style.setProperty('--n', n++); void c.offsetWidth; c.classList.add('is-in'); }
        });
    });
})();
</script>
@endpush
