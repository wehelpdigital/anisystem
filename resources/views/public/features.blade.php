@extends('layouts.public')

@include('public.partials.site-css')

@section('title', \App\Support\Region::ph() ? 'Farm App Features: Calendar, Satellite, NPK and Anee' : 'Farm Management App Features')
@section('meta_description', \App\Support\Region::ph()
    ? 'Every anee.io feature for Filipino farmers: the cropping calendar, satellite field health, typhoon watch, the NPK calculator, pest finders and Anee.'
    : 'Every anee.io feature: a cropping calendar by day count, satellite field health, storm tracking, a fertilizer calculator, workers, reports and Anee.')

@php
    $R = \App\Support\Region::class;
    $ph = $R::ph();
    $tick = '<svg fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>';
    // The sections, in order: anchor, chip word.
    $fxNav = [['plan', 'Plan'], ['space', 'From space'], ['fertilizer', 'Fertilizer'], ['crop-care', 'Crop care'], ['people', 'People'], ['records', 'Records'],
        ['agronomy', 'Agronomy'], ['money', 'Money'], ['community', 'Community'], ['resources', 'Resources'], ['anee', 'Anee']];
    $fxMarquee = ['Cropping calendar', 'Day counts per lot', 'Satellite Analysis', 'Satellite Weather', 'Typhoon watch', 'NPK Plus', 'Pest Finder', 'Disease Finder',
        'Weed Control Helper', 'Spray direction', 'Protocol Builder', 'When to Plant', 'What to Plant', 'Variety Research', 'Crop Protocol Analysis', 'Realign by Anee',
        'Growth stages', 'Farm maps', 'Draw', 'Offline mode', 'Workers and payroll', 'Team logins', 'Collab Room', 'Morning plan email', 'Quick Voice',
        'Season gallery', 'Inventory', 'Labor Report', 'Expenses Report', 'Profit Report', 'Compare Reports', 'Anee Season Report', 'The Stash', 'Contact List',
        'Farmer community', 'Tags', 'Undo that survives', 'Logs'];
    $fxNew = [
        ['space', 'Satellite Analysis', 'Your field read from space: greenness, weak spots, radar through clouds.', 'satellite'],
        ['space', 'Satellite Weather', 'Clouds, rain and every typhoon, with its path and how far it is.', 'storm'],
        ['fertilizer', 'NPK Plus', 'Every nutrient in your fertilizer plan, and the yield it can feed.', 'npk'],
        ['crop-care', 'Pest and disease finders', 'From what you see in the field to the active ingredient to spray.', 'pest'],
        ['resources', 'The Stash', 'Magazines and guides shared by anee.io\'s partners, free to read.', 'stash'],
    ];
@endphp

@push('head')
<style>
    /* ---- FEATURES (redrawn 2026-10-07) ------------------------------------
       A hero that moves, a band of every feature sliding past, a sticky
       chip bar that follows the reader, what is new up front, then each part
       of the farm in turn. The house curve throughout; still when asked. */
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
    @media (max-width: 1023.98px) { .fz-phones { height: 25rem; } .fz-phones .ph-frame { width: min(200px, 44vw); } }
    @media (max-width: 479.98px) { .fz-phones { height: 21rem; } .fz-phones .p2 { left: -4%; } .fz-phones .p3 { right: -4%; } }

    /* Every feature, sliding past. */
    .fz-band { position: relative; overflow: hidden; padding: 1rem 0; background: #0d1609; border-top: 1px solid rgb(255 255 255 / .06); border-bottom: 1px solid rgb(255 255 255 / .06);
        -webkit-mask-image: linear-gradient(90deg, transparent, #000 8%, #000 92%, transparent); mask-image: linear-gradient(90deg, transparent, #000 8%, #000 92%, transparent); }
    .fz-track { display: flex; gap: .55rem; width: max-content; animation: fzSlide 70s linear infinite; }
    .fz-track.rev { animation-direction: reverse; animation-duration: 80s; margin-top: .55rem; }
    .fz-band:hover .fz-track { animation-play-state: paused; }
    .fz-track span { flex: none; padding: .5rem .95rem; border-radius: 999px; font-size: .85rem; font-weight: 800; color: #dbe7cf; background: rgb(255 255 255 / .06); border: 1px solid rgb(255 255 255 / .1); white-space: nowrap; }
    .fz-track span.is-new { color: #1f1500; background: #f5c518; border-color: #f5c518; }
    @keyframes fzSlide { to { transform: translateX(-50%); } }

    /* The chip bar that follows the reader. */
    .fz-nav { position: sticky; top: 4.5rem; z-index: 20; background: rgb(255 255 255 / .9); backdrop-filter: blur(10px); -webkit-backdrop-filter: blur(10px); border-bottom: 1px solid #eef2ea; }
    @media (min-width: 1024px) { .fz-nav { top: 5rem; } }
    .fz-nav-in { display: flex; gap: .35rem; overflow-x: auto; scrollbar-width: none; padding: .65rem 0; }
    .fz-nav-in::-webkit-scrollbar { display: none; }
    .fz-nav a { flex: none; padding: .45rem .85rem; border-radius: 999px; font-size: .84rem; font-weight: 800; text-decoration: none; color: #4b5563;
        transition: background-color .28s cubic-bezier(.22,1,.36,1), color .28s cubic-bezier(.22,1,.36,1); }
    .fz-nav a:hover { background: #f3f8ec; color: #2d5016; }
    .fz-nav a.is-on { background: #2d5016; color: #fff; }

    /* What is new, up front. */
    .fz-new { display: grid; gap: .9rem; grid-template-columns: repeat(auto-fit, minmax(14rem, 1fr)); }
    .fz-new a { position: relative; display: flex; flex-direction: column; gap: .5rem; padding: 1.1rem; border-radius: 1.2rem; text-decoration: none; background: #fff; border: 1px solid #e4efd4;
        box-shadow: 0 20px 40px -34px rgb(20 33 12 / .6); transition: transform .28s cubic-bezier(.22,1,.36,1), box-shadow .28s cubic-bezier(.22,1,.36,1), border-color .28s cubic-bezier(.22,1,.36,1); }
    .fz-new a:hover { transform: translateY(-4px); border-color: #a8cc7e; box-shadow: 0 26px 44px -30px rgb(20 33 12 / .7); }
    .fz-new img { width: 2.6rem; height: 2.6rem; }
    .fz-new b { font-family: var(--font-heading); font-size: 1.05rem; font-weight: 800; color: #14210c; }
    .fz-new p { font-size: .88rem; line-height: 1.55; color: #4b5563; }
    .fz-new em { position: absolute; top: .9rem; right: .9rem; padding: .18rem .55rem; border-radius: 999px; font-style: normal; font-size: .66rem; font-weight: 900; letter-spacing: .08em; text-transform: uppercase; color: #1f1500; background: #f5c518; }

    /* Each part of the farm. */
    .fz-sec { scroll-margin-top: 9rem; }
    .fz-sec .fx-kicker { display: inline-flex; align-items: center; gap: .45rem; }
    .fz-sec .fx-kicker i { font-style: normal; padding: .12rem .5rem; border-radius: 999px; font-size: .64rem; letter-spacing: .08em; color: #1f1500; background: #f5c518; }
    html.js .fz-sec.reveal .fx-list li { opacity: 0; transform: translateX(-8px); transition: opacity .5s cubic-bezier(.22,1,.36,1), transform .5s cubic-bezier(.22,1,.36,1); }
    html.js .fz-sec.reveal.is-visible .fx-list li { opacity: 1; transform: none; }
    html.js .fz-sec.reveal.is-visible .fx-list li:nth-child(2) { transition-delay: .08s; }
    html.js .fz-sec.reveal.is-visible .fx-list li:nth-child(3) { transition-delay: .16s; }
    html.js .fz-sec.reveal.is-visible .fx-list li:nth-child(4) { transition-delay: .24s; }
    html.js .fz-sec.reveal.is-visible .fx-list li:nth-child(5) { transition-delay: .32s; }
    .fz-duo { position: relative; display: flex; justify-content: center; gap: 0; }
    .fz-duo .ph-frame { width: min(230px, 42vw); }
    .fz-duo .ph-frame + .ph-frame { margin-left: -2.5rem; margin-top: 3rem; }
    .fz-partner { display: inline-flex; align-items: center; gap: .7rem; margin-top: 1rem; padding: .6rem .9rem .6rem .6rem; border-radius: 1rem; background: #f6faf1; border: 1px solid #e1edd3; }
    .fz-partner img { width: 3rem; height: 3rem; border-radius: .7rem; }
    .fz-partner b { display: block; font-size: .92rem; color: #14210c; }
    .fz-partner small { display: block; font-size: .78rem; color: #6b7280; }
    .fz-link { display: inline-flex; align-items: center; gap: .35rem; margin-top: 1.1rem; font-weight: 800; color: #3d6823; text-decoration: none; }
    .fz-link:hover { text-decoration: underline; }

    @media (prefers-reduced-motion: reduce) {
        .fz-orb, .fz-kick i, .fz-h1 em, .fz-phones .ph-frame, .fz-track { animation: none !important; }
        .fz-track { flex-wrap: wrap; width: auto; justify-content: center; }
        .fz-track.rev { display: none; }
        html.js .fz-sec.reveal .fx-list li { opacity: 1; transform: none; transition: none; }
        .fz-new a, .fz-nav a { transition: none; }
    }
</style>
@endpush

@section('content')

    {{-- ================= HERO ================= --}}
    <section class="fz-hero">
        <i class="fz-orb a" aria-hidden="true"></i><i class="fz-orb b" aria-hidden="true"></i>
        <div class="max-w-6xl mx-auto px-4 sm:px-6 pt-14 pb-12 sm:pt-20 sm:pb-16 fz-hero-in">
            <div class="animate-fade-up">
                <span class="fz-kick"><i aria-hidden="true"></i>The full tour</span>
                <h1 class="fz-h1">Everything Your Farm <em>Runs On.</em></h1>
                <p class="fz-lede">
                    One farm app for the whole season: the cropping calendar, your field seen from space, the typhoon on its way, every nutrient in your fertilizer,
                    your workers and every {{ $ph ? 'peso' : 'dollar' }}, with Anee, your smart farm technician, beside you. Every screen below is the real thing.
                </p>
                <div class="fz-stats">
                    <div><b data-count="50">50</b><small>features and counting</small></div>
                    <div><b>{{ $ph ? '85' : '100' }}</b><small>crops it knows by stage</small></div>
                    <div><b>2</b><small>satellites over your field</small></div>
                </div>
                <div class="fz-cta">
                    <a href="{{ route('signup') }}" class="btn btn-accent btn-lg">Start free</a>
                    <a href="#new" class="btn btn-lg btn-on-dark">See what is new</a>
                </div>
            </div>
            <div class="fz-phones" aria-hidden="true">
                <span class="ph-frame p2"><img src="{{ asset('images/site/app/board.png') }}" alt="" loading="lazy" width="780" height="1520"></span>
                <span class="ph-frame p3"><img src="{{ asset('images/site/app/npk.webp') }}" alt="" loading="lazy" width="780" height="1520"></span>
                <span class="ph-frame p1"><img src="{{ asset('images/site/app/sky.webp') }}" alt="" width="780" height="1520"></span>
            </div>
        </div>
        <div class="fz-band" aria-label="Every feature">
            <div class="fz-track">
                @foreach (array_merge($fxMarquee, $fxMarquee) as $i => $m)
                    <span class="{{ in_array($m, ['Satellite Analysis', 'Satellite Weather', 'NPK Plus', 'Pest Finder', 'Disease Finder', 'The Stash', 'Spray direction'], true) ? 'is-new' : '' }}" @if ($i >= count($fxMarquee)) aria-hidden="true" @endif>{{ $m }}</span>
                @endforeach
            </div>
            <div class="fz-track rev" aria-hidden="true">
                @foreach (array_merge(array_reverse($fxMarquee), array_reverse($fxMarquee)) as $m)
                    <span>{{ $m }}</span>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ================= THE CHIP BAR ================= --}}
    <nav class="fz-nav" aria-label="Feature sections">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 fz-nav-in" id="fzNav">
            @foreach ($fxNav as [$k, $l])<a href="#{{ $k }}" data-to="{{ $k }}">{{ $l }}</a>@endforeach
        </div>
    </nav>

    {{-- ================= NEW ================= --}}
    <section class="py-14 sm:py-16 bg-gray-50 border-b border-gray-100 fz-sec" id="new">
        <div class="max-w-6xl mx-auto px-4 sm:px-6">
            <div class="max-w-2xl reveal">
                <p class="text-sm font-bold uppercase tracking-wider text-brand-600">New this season</p>
                <h2 class="mt-2 font-heading text-2xl sm:text-3xl font-bold text-ink text-balance">Five new tools, built for the hardest weeks of the season</h2>
            </div>
            <div class="fz-new mt-8">
                @foreach ($fxNew as $i => [$to, $name, $what, $icon])
                    <a href="#{{ $to }}" class="reveal" style="--reveal-delay: {{ $i * .06 }}s"><em>New</em><img src="{{ asset('images/icons/' . $icon . '.svg') }}" alt="" width="42" height="42" loading="lazy"><b>{{ $name }}</b><p>{{ $what }}</p></a>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ================= EVERY FEATURE, ONE CARD EACH ================= --}}
    @if ($featurePages->isNotEmpty())
    <section class="relative py-14 sm:py-16 bg-white border-b border-gray-100">
        <div class="max-w-6xl mx-auto px-4 sm:px-6">
            <div class="max-w-2xl reveal">
                <p class="text-sm font-bold uppercase tracking-wider text-brand-600">Everything in one farm app</p>
                <h2 class="mt-2 font-heading text-2xl sm:text-3xl font-bold text-ink text-balance">{{ $featurePages->count() }} Feature Guides for One Cropping Season</h2>
                <p class="mt-3 text-gray-600">Each one works on its own and with the rest: plan the season, keep the records, and ask for advice from the same phone. Open any of them for the full story.</p>
            </div>
            <div class="mt-8 reveal">@include('public.site.feature-grid', ['pages' => $featurePages])</div>
        </div>
    </section>
    @endif

    <section class="py-16 sm:py-24 bg-white overflow-hidden">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 space-y-24 sm:space-y-32">

            {{-- 1. The board --}}
            <div class="fx-row reveal fz-sec" id="plan">
                <div class="fx-media fx-glow">
                    <span class="ph-frame ph-tilt-l"><img src="{{ asset('images/site/app/board.png') }}" alt="The activities board of the anee.io cropping calendar" loading="lazy" width="780" height="1520"></span>
                </div>
                <div>
                    <p class="fx-kicker">Plan</p>
                    <h2 class="fx-h">The activities board: your season, day by day</h2>
                    <p class="fx-p">Build the whole calendar from land prep to harvest. Every task is dated from each lot's own day zero, so the timing stays right even when lots were sown a week apart.</p>
                    <ul class="fx-list">
                        <li>{!! $tick !!}Tasks, irrigation, hired services, payroll days and reminder checklists</li>
                        <li>{!! $tick !!}Drag to reschedule, drafts for plans not yet decided, versions to try other plans</li>
                        <li>{!! $tick !!}Each spray task says where the spray goes: under the leaves, over the canopy, at the base</li>
                        <li>{!! $tick !!}Undo that still works after you log out, because it is saved on the server</li>
                    </ul>
                </div>
            </div>

            {{-- 2. From space --}}
            <div class="fx-row is-flip reveal fz-sec" id="space">
                <div class="fx-media fz-duo">
                    <span class="ph-frame ph-tilt-l"><img src="{{ asset('images/site/app/satellite.webp') }}" alt="Satellite Analysis report with a crop health score of a corn field" loading="lazy" width="780" height="1520"></span>
                    <span class="ph-frame ph-tilt-r"><img src="{{ asset('images/site/app/sky.webp') }}" alt="Satellite Weather showing clouds over Luzon and a 300 km typhoon ring around the farm" loading="lazy" width="780" height="1520"></span>
                </div>
                <div>
                    <p class="fx-kicker">From space <i>New</i></p>
                    <h2 class="fx-h">Your field and your sky, seen from space</h2>
                    <p class="fx-p"><b>Satellite Analysis</b> reads the field you draw from the newest Sentinel 2 picture and Sentinel 1 radar, which sees through typhoon clouds, and Anee tells you what it means. <b>Satellite Weather</b> plays back the clouds and rain over your farm, fast forwards the forecast and follows every typhoon.</p>
                    <ul class="fx-list">
                        <li>{!! $tick !!}Crop health score, greenness (NDVI) heatmap and the weak spots to walk first</li>
                        <li>{!! $tick !!}Radar readings when clouds hide the field, the last 90 days as a line</li>
                        <li>{!! $tick !!}Typhoon paths with their cone, and a warning when one comes within 300 km</li>
                        <li>{!! $tick !!}Anee reads it against your lot: its crop, its stage, the soil and ENSO</li>
                    </ul>
                </div>
            </div>

            {{-- 3. Fertilizer --}}
            <div class="fx-row reveal fz-sec" id="fertilizer">
                <div class="fx-media fx-glow">
                    <span class="ph-frame ph-tilt-l"><img src="{{ asset('images/site/app/npk.webp') }}" alt="NPK Plus showing nitrogen, phosphorus and potassium per hectare against the usual rate for rice" loading="lazy" width="780" height="1520"></span>
                </div>
                <div>
                    <p class="fx-kicker">Fertilizer <i>New</i></p>
                    <h2 class="fx-h">NPK Plus: every nutrient in your fertilizer plan</h2>
                    <p class="fx-p">Tap the fertilizers you plan to use, from urea and complete to manure and inoculants, or add your own from its label. NPK Plus adds up every nutrient as the element and the oxide, checks it against what your crop needs and shows the yield it can feed. Free, and as often as you like.</p>
                    <ul class="fx-list">
                        <li>{!! $tick !!}Nitrogen, phosphorus, potassium, and the micronutrients only when you use them</li>
                        <li>{!! $tick !!}Biofertilizers like Azospirillum counted from field trial estimates</li>
                        <li>{!! $tick !!}Your soil test moves the target, low or high</li>
                        <li>{!! $tick !!}On the board and in the Protocol Builder: what each lot has had and what is still planned</li>
                        <li>{!! $tick !!}Anee reads the plan against your place, soil, water and season</li>
                    </ul>
                </div>
            </div>

            {{-- 4. Crop care --}}
            <div class="fx-row is-flip reveal fz-sec" id="crop-care">
                <div class="fx-media fx-glow">
                    <span class="ph-frame ph-tilt-r"><img src="{{ asset('images/site/app/finder.webp') }}" alt="The Pest Finder listing rice pests with the active ingredients to spray and their IRAC groups" loading="lazy" width="780" height="1520"></span>
                </div>
                <div>
                    <p class="fx-kicker">Crop care <i>New</i></p>
                    <h2 class="fx-h">From what you see to what to spray</h2>
                    <p class="fx-p">Pick the crop and what you see in the field. The Pest Finder and the Disease Finder name what fits, with the active ingredients that work and their groups, so you can switch groups and keep them working. The Weed Control Helper says what to do at the age of your rice.</p>
                    <ul class="fx-list">
                        <li>{!! $tick !!}{{ $ph ? 'Pests and diseases of palay, mais, gulay and fruit trees' : 'Pests and diseases of the main crops' }}</li>
                        <li>{!! $tick !!}IRAC and FRAC groups, active ingredients only, never brands</li>
                        <li>{!! $tick !!}When not to spray at all, said plainly</li>
                        <li>{!! $tick !!}Send Anee a photo when you are not sure</li>
                    </ul>
                    <a href="{{ url('/pests') }}#finder" class="fz-link">Try the Pest Finder ›</a>
                </div>
            </div>

            {{-- 5. Workers --}}
            <div class="fx-row reveal fz-sec" id="people">
                <div class="fx-media">
                    <img src="{{ asset('images/site/harvest-hands.jpg') }}" alt="Farm workers harvesting in the field"
                         class="rounded-2xl shadow-card-lg ring-1 ring-black/5 w-full max-w-md object-cover aspect-[4/3]" loading="lazy">
                </div>
                <div>
                    <p class="fx-kicker">People</p>
                    <h2 class="fx-h">Workers, payroll and permissions that fit a real farm</h2>
                    <p class="fx-p">Keep a list of your workers with their rates and skills, assign them to activities, and watch the labor cost add up as you plan. Give a worker their own login and decide, part by part, what they can see and what they can change.</p>
                    <ul class="fx-list">
                        <li>{!! $tick !!}None, view or edit, for each part of the app and each worker</li>
                        <li>{!! $tick !!}Payroll days with each worker's own rate, for half or whole days</li>
                        <li>{!! $tick !!}Your bell rings when a worker finishes a task, adds a photo or records a voice note</li>
                        <li>{!! $tick !!}The morning email tells the whole team today's plan at 6 AM</li>
                    </ul>
                </div>
            </div>

            {{-- 6. Notes & media --}}
            <div class="fx-row is-flip reveal fz-sec" id="records">
                <div class="fx-media fx-glow">
                    <span class="ph-frame ph-tilt-r"><img src="{{ asset('images/site/app/notes.png') }}" alt="Notes with photos and voice recordings" loading="lazy" width="780" height="1520"></span>
                </div>
                <div>
                    <p class="fx-kicker">Records</p>
                    <h2 class="fx-h">Notes, photos, videos and your own voice</h2>
                    <p class="fx-p">The fastest record is the one you can make standing in the mud. Take a photo, film it, or just say it. Quick Voice saves a spoken note in seconds, and everything lands in a gallery you can search.</p>
                    <ul class="fx-list">
                        <li>{!! $tick !!}Notes for each season and each day, plus Global Notes for everything else</li>
                        <li>{!! $tick !!}Voice notes play right on the card in notes, activities and chat</li>
                        <li>{!! $tick !!}A drawing pad for sketching over field photos</li>
                        <li>{!! $tick !!}Works without signal, and syncs when it comes back</li>
                    </ul>
                </div>
            </div>

            {{-- 7. Growth & weather --}}
            <div class="fx-row reveal fz-sec" id="agronomy">
                <div class="fx-media fx-glow">
                    <span class="ph-frame ph-tilt-l"><img src="{{ asset('images/site/app/growth.png') }}" alt="Growth stages of each lot" loading="lazy" width="780" height="1520"></span>
                </div>
                <div>
                    <p class="fx-kicker">Agronomy</p>
                    <h2 class="fx-h">Growth stages and weather that read your fields</h2>
                    <p class="fx-p">anee.io knows {{ $ph ? '85 Philippine crops' : 'nearly a hundred crops' }}. Pick any date and it says where every lot stands: the growth stage, what it needs and what to watch for, with the week's forecast beside it.</p>
                    <ul class="fx-list">
                        <li>{!! $tick !!}{{ $ph ? 'Palay, mais, gulay and fruit trees' : 'Rice, corn, vegetables and fruit trees' }}, annuals and perennials both</li>
                        <li>{!! $tick !!}Realign by Anee when a crop runs ahead of or behind the calendar</li>
                        <li>{!! $tick !!}Maps: draw and measure your lots, drop pins, save team maps</li>
                    </ul>
                </div>
            </div>

            {{-- 8. Money --}}
            <div class="fx-row is-flip reveal fz-sec" id="money">
                <div class="fx-media fx-glow">
                    <span class="ph-frame ph-tilt-r"><img src="{{ asset('images/site/app/dashboard.png') }}" alt="The dashboard with money and season summaries" loading="lazy" width="780" height="1520"></span>
                </div>
                <div>
                    <p class="fx-kicker">Money</p>
                    <h2 class="fx-h">Inventory, expenses and reports that agree to the {{ $ph ? 'peso' : 'cent' }}</h2>
                    <p class="fx-p">The shed keeps your stock, and every move in or out is recorded with a name. Labor, expenses and profit reports are worked out straight from the plan, and Anee can write a full report of the season on top.</p>
                    <ul class="fx-list">
                        <li>{!! $tick !!}Inventory items and moves, with an audit trail of who did what</li>
                        <li>{!! $tick !!}Labor, expenses and profit, computed and never guessed</li>
                        <li>{!! $tick !!}Post harvest observations with yields, buyers and prices</li>
                        <li>{!! $tick !!}Compare any two reports side by side</li>
                    </ul>
                </div>
            </div>

            {{-- 9. Community --}}
            <div class="fx-row reveal fz-sec" id="community">
                <div class="fx-media fx-glow">
                    <span class="ph-frame ph-tilt-l"><img src="{{ asset('images/site/app/community.png') }}" alt="The farmer community feed" loading="lazy" width="780" height="1520"></span>
                </div>
                <div>
                    <p class="fx-kicker">Community</p>
                    <h2 class="fx-h">Cofarmers, discussions and a ladder worth climbing</h2>
                    <p class="fx-p">A news feed for wins and warnings, focused discussion rooms, direct messages with photos, clips and voice notes, and a ladder of 100 levels that turns helping into a game.</p>
                    <ul class="fx-list">
                        <li>{!! $tick !!}Public, password and approval rooms for private groups</li>
                        <li>{!! $tick !!}A team Collab Room per season: chat, whiteboard and calls</li>
                        <li>{!! $tick !!}The latest farm news, with what it means for your farm</li>
                    </ul>
                </div>
            </div>

            {{-- 10. Resources --}}
            <div class="fx-row is-flip reveal fz-sec" id="resources">
                <div class="fx-media fx-glow">
                    <span class="ph-frame ph-tilt-r"><img src="{{ asset('images/site/app/stash.webp') }}" alt="The Stash shelf of PhilRice e-magazines" loading="lazy" width="780" height="1520"></span>
                </div>
                <div>
                    <p class="fx-kicker">Resources <i>New</i></p>
                    <h2 class="fx-h">The Stash: resources shared by anee.io's partners</h2>
                    <p class="fx-p">Magazines, guides and studies from the institutions that work beside Filipino farmers, kept in one shelf inside the app. Search by title or year and read them in anee.io's own reader, free for every member.</p>
                    <div class="fz-partner"><img src="{{ asset('images/partners/philrice.webp') }}" alt="PhilRice logo" width="48" height="48" loading="lazy"><span><b>PhilRice</b><small>Every PhilRice Magazine issue, 75 and counting</small></span></div>
                    <ul class="fx-list">
                        <li>{!! $tick !!}A shelf per partner and type, growing as partners join</li>
                        <li>{!! $tick !!}Opens at page one even on a slow signal</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

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
    /* The chip bar follows the reader: the section in view lights its chip,
       and the chip slides into view in the bar. */
    const nav = document.getElementById('fzNav');
    if (!nav) return;
    // The bar hangs right under the site header, whatever its height today.
    const hdr = document.querySelector('header');
    const hang = () => { if (hdr) nav.parentElement.style.top = Math.round(hdr.getBoundingClientRect().height) + 'px'; };
    hang();
    addEventListener('resize', hang, { passive: true });
    if (!('IntersectionObserver' in window)) return;
    const chips = [...nav.querySelectorAll('a[data-to]')];
    const secs = chips.map((a) => document.getElementById(a.dataset.to)).filter(Boolean);
    let on = null;
    const light = (id) => {
        if (id === on) return;
        on = id;
        chips.forEach((a) => a.classList.toggle('is-on', a.dataset.to === id));
        const a = chips.find((x) => x.dataset.to === id);
        if (a) nav.scrollTo({ left: a.offsetLeft - nav.clientWidth / 2 + a.clientWidth / 2, behavior: matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth' });
    };
    const io = new IntersectionObserver((es) => {
        const vis = es.filter((e) => e.isIntersecting).sort((a, b) => a.boundingClientRect.top - b.boundingClientRect.top)[0];
        if (vis) light(vis.target.id);
    }, { rootMargin: '-40% 0px -55% 0px' });
    secs.forEach((s) => io.observe(s));
})();
</script>
@endpush
