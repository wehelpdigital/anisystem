@extends('layouts.public')

{{-- /how-to-plant (2026-10-19, the owner): every crop's traditional planting
     guide, shelf by shelf, with a search. Each card opens the crop's page
     (gated for guests like the other guides, every word in the HTML). The
     cards read their icon, names and ways from App\Support\HowToPlant; the
     pages are database/site-pages/how-to-plant, made by
     database/how-to-plant/build_pages.py. The list itself stays open, like
     /crops and /land-preparation. --}}
@include('public.partials.site-css')
@include('public.site.css')

@php
    $S = \App\Support\SitePages::class;
    $F = \App\Support\FieldCatalogue::class;
    $H = \App\Support\HowToPlant::class;
    $index = $H::index();
    // One card a page: its crop from the guide that shares its slug.
    $cards = $pages->map(function ($p) use ($index, $H) {
        $crop = collect($index)->search(fn ($g) => $g['slug'] === $p->slug);
        $g = $crop ? $index[$crop] : null;
        $ways = $g ? $g['ways'] : [];
        $first = $ways[0] ?? null;

        return [
            'page' => $p,
            'crop' => $crop ?: '',
            'icon' => $crop ? (\App\Support\CropCatalog::CROPS[$crop]['icon'] ?? '🌱') : '🌱',
            'local' => $g ? (trim((string) $g['local']) ?: $g['name']) : $p->title,
            'name' => $g['name'] ?? '',
            'shelf' => $crop ? $H::shelfOf($crop) : 'fruits',
            'ways' => count($ways),
            'how' => $first ? $H::wayShort($first) : '',
            'harvest' => $first ? $H::harvestWords($first, $crop && $H::perennial($crop)) : null,
            'q' => mb_strtolower(implode(' ', array_filter([$g['local'] ?? '', $g['name'] ?? '', implode(' ', (array) ($g['aka'] ?? [])), str_replace('_', ' ', (string) $crop), $p->title]))),
        ];
    });
    $shelves = collect($F::SHELVES)->filter(fn ($s, $k) => $cards->contains('shelf', $k));
    $more = [['/crops/pagtatanim-ng-palay', 'Pagtatanim ng palay, the full season'], ['/crops/pagtatanim-ng-mais', 'Pagtatanim ng mais'], ['/crops/pagtatanim-ng-gulay', 'Pagtatanim ng gulay'],
        ['/crops/pagtatanim-ng-puno', 'Pagtatanim ng puno'], ['/crops/banana-farming-philippines', 'Banana farming in the Philippines'], ['/land-preparation', 'Land preparation by crop']];
@endphp

@section('title_full', $meta['metaTitle'] . ' | anee.io')
@section('meta_description', $meta['metaDescription'])

@push('head')
    <link rel="canonical" href="{{ $S::url($section) }}">
    <meta property="og:title" content="{{ $meta['metaTitle'] }}">
    <meta property="og:description" content="{{ $meta['metaDescription'] }}">
    <meta property="og:url" content="{{ $S::url($section) }}">
    <script type="application/ld+json">{!! json_encode([
        '@@context' => 'https://schema.org',
        '@type' => 'CollectionPage',
        'name' => $meta['hubTitle'],
        'description' => $meta['metaDescription'],
        'url' => $S::url($section),
        'hasPart' => $pages->map(fn ($p) => ['@type' => 'Article', 'headline' => $p->title, 'url' => $S::pageUrl($p)])->values()->all(),
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
    <style>
        .ht-note { display: flex; gap: .75rem; align-items: flex-start; margin-top: 1.4rem; max-width: 46rem; padding: .9rem 1rem; border-radius: 1.1rem; background: #fff7e0; border: 1px solid #f3d27a; color: #5b3d00; }
        .ht-note i { flex: none; width: 2.1rem; height: 2.1rem; border-radius: .75rem; display: grid; place-items: center; background: #f5c518; color: #3b2f00; }
        .ht-note i svg { width: 1.1rem; height: 1.1rem; }
        .ht-note b { display: block; font-weight: 800; color: #4a3200; }
        .ht-note p { margin-top: .15rem; font-size: .92rem; line-height: 1.55; }
        .ht-tools { position: sticky; top: 4.2rem; z-index: 5; display: grid; gap: .65rem; padding: .8rem; margin-bottom: 1.4rem; border-radius: 1.2rem; background: rgb(255 255 255 / .94);
            border: 1px solid #e5ebdf; box-shadow: 0 16px 30px -28px rgb(20 33 12 / .6); backdrop-filter: blur(6px); }
        .ht-find { display: flex; align-items: center; gap: .55rem; padding: 0 .85rem; border-radius: .9rem; background: #f6f8f3; border: 1px solid #e1e9d7; transition: border-color .28s cubic-bezier(.22,1,.36,1), background-color .28s cubic-bezier(.22,1,.36,1); }
        .ht-find:focus-within { border-color: #86b556; background: #fff; }
        .ht-find svg { flex: none; width: 1.1rem; height: 1.1rem; color: #6b9f3d; }
        .ht-find input { flex: 1; min-width: 0; min-height: 2.9rem; border: 0; background: transparent; font-size: 1rem; color: #14210c; outline: none; box-shadow: none; }
        .ht-chips { display: flex; gap: .4rem; overflow-x: auto; scrollbar-width: none; padding-bottom: .1rem; }
        .ht-chips::-webkit-scrollbar { display: none; }
        .ht-chips button { flex: none; display: inline-flex; align-items: center; gap: .35rem; min-height: 2.3rem; padding: 0 .8rem; border-radius: 999px; font-size: .84rem; font-weight: 700; color: #3d4a33;
            background: #f3f6ee; border: 1px solid #e1e9d7; transition: background-color .28s cubic-bezier(.22,1,.36,1), color .28s cubic-bezier(.22,1,.36,1), border-color .28s cubic-bezier(.22,1,.36,1); }
        .ht-chips button small { font-size: .72rem; font-weight: 800; color: #6b7280; }
        .ht-chips button.is-on { background: #3d6823; border-color: #3d6823; color: #fff; }
        .ht-chips button.is-on small { color: #d9ecc4; }
        .ht-shelf { margin-top: 1.8rem; }
        .ht-shelf:first-of-type { margin-top: .4rem; }
        .ht-shelf h2 { display: flex; align-items: center; gap: .55rem; font-family: var(--font-heading); font-size: 1.25rem; font-weight: 800; color: #14210c; }
        .ht-shelf h2 span { display: grid; place-items: center; width: 2.1rem; height: 2.1rem; border-radius: .75rem; color: hsl(var(--g) 45% 30%); background: hsl(var(--g) 50% 93%); }
        .ht-shelf h2 svg { width: 1.15rem; height: 1.15rem; }
        .ht-shelf h2 small { font-family: var(--font-body, inherit); font-size: .85rem; font-weight: 600; color: #6b7280; }
        .ht-grid { margin-top: .85rem; display: grid; gap: .7rem; grid-template-columns: repeat(auto-fill, minmax(15.5rem, 1fr)); }
        .ht-card { position: relative; display: flex; gap: .8rem; align-items: center; padding: .85rem .95rem; border-radius: 1.1rem; background: #fff; border: 1px solid #e5ebdf; text-decoration: none;
            transition: transform .28s cubic-bezier(.22,1,.36,1), box-shadow .28s cubic-bezier(.22,1,.36,1), border-color .28s cubic-bezier(.22,1,.36,1), opacity .28s cubic-bezier(.22,1,.36,1); }
        .ht-card:hover { transform: translateY(-2px); box-shadow: 0 16px 30px -24px rgb(20 33 12 / .6); border-color: #c9e0ad; }
        .ht-card:focus-visible { outline: 3px solid #f5c518; outline-offset: 2px; }
        .ht-ico { flex: none; width: 3.1rem; height: 3.1rem; border-radius: 1rem; display: grid; place-items: center; font-size: 1.65rem; background: linear-gradient(135deg, #f3f8ec, #e4efd4); }
        .ht-t { min-width: 0; flex: 1; }
        .ht-t b { display: block; font-family: var(--font-heading); font-size: 1.05rem; line-height: 1.2; color: #14210c; }
        .ht-t em { display: block; font-style: normal; font-size: .8rem; font-weight: 600; color: #6b7280; }
        .ht-t span { display: block; margin-top: .25rem; font-size: .76rem; font-weight: 700; color: #3d6823; }
        .ht-go { flex: none; width: 1.8rem; height: 1.8rem; border-radius: 999px; display: grid; place-items: center; color: #3d6823; background: #eef5e6; transition: transform .28s cubic-bezier(.22,1,.36,1); }
        .ht-card:hover .ht-go { transform: translateX(2px); }
        .ht-go svg { width: .85rem; height: .85rem; }
        .ht-card.is-out { display: none; }
        .ht-card.is-in { animation: htIn .32s cubic-bezier(.22,1,.36,1) both; }
        @keyframes htIn { from { opacity: 0; transform: translateY(8px); } }
        .ht-none { display: none; padding: 2rem 1rem; text-align: center; color: #6b7280; }
        .ht-none.is-on { display: block; }
        .ht-more { margin-top: 2.6rem; padding: 1.2rem; border-radius: 1.2rem; background: #f9fbf6; border: 1px solid #e4efd4; }
        .ht-more h2 { font-family: var(--font-heading); font-size: 1.2rem; font-weight: 800; color: #14210c; }
        .ht-more ul { margin-top: .7rem; display: grid; gap: .45rem; grid-template-columns: repeat(auto-fill, minmax(15rem, 1fr)); }
        .ht-more a { display: block; padding: .6rem .8rem; border-radius: .8rem; background: #fff; border: 1px solid #e5ebdf; font-weight: 700; color: #2d5016; text-decoration: none; }
        .ht-more a:hover { border-color: #c9e0ad; }
        @media (prefers-reduced-motion: reduce) { .ht-card, .ht-go, .ht-find, .ht-chips button { transition: none; } .ht-card.is-in { animation: none; } }
    </style>
@endpush

@section('content')
    <section class="sp-hero">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 pt-10 pb-10 sm:pt-14">
            <nav class="sp-crumbs" aria-label="Breadcrumb">
                <a href="{{ url('/') }}">Home</a>
                <svg fill="none" stroke="currentColor" stroke-width="2.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                <span>{{ $meta['crumb'] }}</span>
            </nav>
            <div class="mt-5 max-w-3xl">
                <span class="sp-chip">{{ $meta['kicker'] }}</span>
                <h1 class="sp-h1 mt-3">{{ $meta['hubTitle'] }}</h1>
                <p class="sp-lead mt-4">{{ $meta['intro'] }}</p>
            </div>
            <div class="ht-note" role="note">
                <i aria-hidden="true"><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.3 3.9L2.4 17.5A2 2 0 004.1 20.5h15.8a2 2 0 001.7-3L13.7 3.9a2 2 0 00-3.4 0z"/></svg></i>
                <div>
                    <b>These are the traditional ways</b>
                    <p>Each guide shows how traditional farmers usually plant the crop. Use it as a starting point, then update it for a higher yield: certified seed or clean planting material, a soil test, and the right spacing and fertilizer.</p>
                </div>
            </div>
            <div class="mt-7 flex flex-wrap items-center gap-3">
                <div class="sp-tabs">
                    <a href="{{ $S::url('crops') }}">Crop guides</a>
                    <a href="{{ $S::url('how-to-plant') }}" class="is-on">How to plant</a>
                    <a href="{{ $S::url('crop-varieties') }}">Crop varieties</a>
                    <a href="{{ $S::url('land-preparation') }}">Land preparation</a>
                    <a href="{{ $S::url('pests') }}">Crop pests</a>
                    <a href="{{ $S::url('diseases') }}">Crop diseases</a>
                    <a href="{{ $S::url('weeds') }}">Weeds and grasses</a>
                    <a href="{{ $S::url('nutrient-deficiencies') }}">Nutrient deficiencies</a>
                    <a href="{{ $S::url('blog') }}">Latest in Agriculture</a>
                </div>
            </div>
        </div>
    </section>

    <section class="bg-white">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 py-10 sm:py-12">
            @if ($cards->isEmpty())
                <p class="text-gray-500">The guides are coming soon.</p>
            @else
                <div class="ht-tools">
                    <label class="ht-find">
                        <svg fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="6.5"/><path stroke-linecap="round" d="M20 20l-4.2-4.2"/></svg>
                        <span class="sr-only">Search a crop</span>
                        <input type="search" id="htFind" placeholder="Search a crop, like kamote, sitaw or mangga" autocomplete="off">
                    </label>
                    <div class="ht-chips" id="htChips" role="group" aria-label="Show only">
                        <button type="button" class="is-on" data-shelf="" aria-pressed="true">All <small>{{ $cards->count() }}</small></button>
                        @foreach ($shelves as $sk => [$sLabel])
                            <button type="button" data-shelf="{{ $sk }}" aria-pressed="false">{{ $sLabel }} <small>{{ $cards->where('shelf', $sk)->count() }}</small></button>
                        @endforeach
                    </div>
                </div>
                @foreach ($shelves as $sk => [$sLabel, $sLocal, $sHue, $sIcon])
                    <div class="ht-shelf" data-shelf="{{ $sk }}" style="--g: {{ $sHue }}">
                        <h2><span aria-hidden="true"><svg fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="{{ $sIcon }}"/></svg></span>{{ $sLabel }} <small>{{ $sLocal }}</small></h2>
                        <div class="ht-grid">
                            @foreach ($cards->where('shelf', $sk) as $c)
                                <a href="{{ $S::pageUrl($c['page']) }}" class="ht-card" data-q="{{ $c['q'] }}">
                                    <span class="ht-ico" aria-hidden="true">{{ $c['icon'] }}</span>
                                    <span class="ht-t">
                                        <b>{{ $c['local'] }}</b>
                                        @if ($c['name'] && mb_strtolower($c['name']) !== mb_strtolower($c['local']))<em>{{ $c['name'] }}</em>@endif
                                        <span>{{ $c['ways'] > 1 ? $c['ways'] . ' ways to plant' : $c['how'] }}@if ($c['harvest']) · {{ preg_replace('/^About /', '', $c['harvest']) }}@endif</span>
                                    </span>
                                    <span class="ht-go" aria-hidden="true"><svg fill="none" stroke="currentColor" stroke-width="2.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg></span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endforeach
                <p class="ht-none" id="htNone">No crop by that name. Try its English or Filipino name.</p>
            @endif

            <div class="ht-more">
                <h2>More planting guides</h2>
                <ul>
                    @foreach ($more as [$u, $l])
                        <li><a href="{{ url($u) }}">{{ $l }} ›</a></li>
                    @endforeach
                </ul>
            </div>
        </div>
    </section>

    <section class="anee-band">
        <div class="relative max-w-4xl mx-auto px-4 sm:px-6 py-14 sm:py-16 text-center">
            <p class="text-sm font-bold uppercase tracking-wider text-accent-400">Make the traditional way better</p>
            <h2 class="mt-2 font-heading text-3xl sm:text-4xl font-bold text-white text-balance">Send any guide to your own protocol</h2>
            <p class="mt-4 text-[#cdd8c0] leading-relaxed max-w-2xl mx-auto">
                In anee.io, every guide opens in the How to Plant tool. One tap sends its steps to the Protocol Builder, each on its own day,
                so you can change the seed, the spacing and the fertilizer, then turn it into a cropping calendar for your lots.
            </p>
            <div class="mt-8 flex flex-col sm:flex-row justify-center gap-3">
                <a href="{{ route('signup') }}" class="btn btn-accent btn-lg">Start free</a>
                <a href="{{ $S::url('features', 'protocol-builder') }}" class="btn btn-lg btn-on-dark">See the Protocol Builder</a>
            </div>
        </div>
    </section>
@endsection

@push('scripts')
<script>
(() => {
    /* The search and the shelf chips narrow the cards; the ones that stay rise into place. */
    const find = document.getElementById('htFind');
    const chips = document.getElementById('htChips');
    if (!find || !chips) return;
    let shelf = '';
    const apply = () => {
        const q = find.value.toLowerCase().trim();
        let any = false, i = 0;
        document.querySelectorAll('.ht-shelf').forEach((sh) => {
            let n = 0;
            const onShelf = !shelf || sh.dataset.shelf === shelf;
            sh.querySelectorAll('.ht-card').forEach((c) => {
                const show = onShelf && (!q || c.dataset.q.includes(q));
                c.classList.toggle('is-out', !show);
                c.classList.remove('is-in');
                if (show) { n++; c.style.animationDelay = Math.min(i++, 12) * 25 + 'ms'; void c.offsetWidth; c.classList.add('is-in'); }
            });
            sh.hidden = !n;
            any = any || n > 0;
        });
        document.getElementById('htNone').classList.toggle('is-on', !any);
    };
    chips.addEventListener('click', (e) => {
        const b = e.target.closest('button[data-shelf]');
        if (!b) return;
        shelf = b.dataset.shelf;
        chips.querySelectorAll('button').forEach((x) => { x.classList.toggle('is-on', x === b); x.setAttribute('aria-pressed', String(x === b)); });
        apply();
    });
    find.addEventListener('input', apply);
})();
</script>
@endpush
