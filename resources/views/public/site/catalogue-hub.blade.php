@extends('layouts.public')

{{-- /pests and /diseases (2026-10-07), built like /weeds: the crop shelves,
     the catalogue (one card per profile page, searched by any name and
     filtered by shelf), the finder ("What is attacking my crop?": the crop,
     then where the damage shows or what the farmer sees, and the matching
     pages), the guides and Anee. The words around it come from
     App\Support\ProblemCatalogue::HUB, the card facts from its data. --}}
@include('public.partials.site-css')
@include('public.site.css')

@php
    $S = \App\Support\SitePages::class;
    $P = \App\Support\ProblemCatalogue::class;
    $live = $pages->keyBy('slug');
    $thumbOf = function ($p) use ($S) {
        $h = is_array($p->heroImage) ? $p->heroImage : [];
        $src = (string) ($h['thumb'] ?? ($h['src'] ?? ''));
        if ($src !== '' && ! isset($h['thumb']) && str_starts_with($src, '/images/')) {
            $twin = preg_replace('/\.(jpe?g|png|webp)$/i', '-480.webp', $src);
            if ($twin && is_file(public_path(ltrim($twin, '/')))) { $src = $twin; }
        }
        return $S::img($src);
    };
    $items = collect($P::entries($section))->filter(fn ($e, $slug) => $live->has($slug))
        ->map(fn ($e, $slug) => $e + ['slug' => $slug, 'url' => $S::pageUrl($live[$slug]), 'thumb' => $thumbOf($live[$slug]),
            'alt' => (is_array($live[$slug]->heroImage) ? ($live[$slug]->heroImage['alt'] ?? null) : null) ?: $e['name']]);
    $guides = $pages->filter(fn ($p) => ! $P::get($section, $p->slug))->values();
    $count = fn ($g) => $items->where('group', $g)->count();
    $faces = collect(array_keys($P::GROUPS))->map(fn ($g) => $items->where('group', $g)->first(fn ($w) => $w['thumb']))->filter()->take(3)->values();
    $isPests = $section === 'pests';
    $askOpts = $isPests ? $P::PARTS : $P::SIGNS;
    $usedCrops = $items->pluck('crops')->flatten()->unique()->all();
    $crops = array_filter($P::CROPS, fn ($k) => in_array($k, $usedCrops, true), ARRAY_FILTER_USE_KEY);
    $words = $P::HUB[$section];
    $kindHue = $P::KINDS;
@endphp

@section('title_full', $meta['metaTitle'] . ' | anee.io')
@section('meta_description', $meta['metaDescription'])

@push('head')
    <link rel="canonical" href="{{ $S::url($section) }}">
    <meta property="og:title" content="{{ $meta['metaTitle'] }}">
    <meta property="og:description" content="{{ $meta['metaDescription'] }}">
    <meta property="og:url" content="{{ $S::url($section) }}">
    @if ($faces->count())<meta property="og:image" content="{{ $faces[0]['thumb'] }}">@endif
    <script type="application/ld+json">{!! json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'CollectionPage',
        'name' => $meta['hubTitle'],
        'description' => $meta['metaDescription'],
        'url' => $S::url($section),
        'hasPart' => $pages->map(fn ($p) => ['@type' => 'Article', 'headline' => $p->title, 'url' => $S::pageUrl($p)])->values()->all(),
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
    @include('public.site.catalogue-css')
    <style>
        .wk-group p.ex { font-size: .86rem; line-height: 1.5; color: #4b5563; }
        .wk-group p.ex b { color: #14210c; }
        .wk-tag.is-kind { left: auto; right: .55rem; }
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
            <div class="wk-hero mt-5">
                <div>
                    <span class="sp-chip">{{ $meta['kicker'] }}</span>
                    <h1 class="sp-h1 mt-3">{{ $meta['hubTitle'] }}</h1>
                    <p class="sp-lead mt-4">{{ $meta['intro'] }}</p>
                    <div class="wk-jump mt-6">
                        <a href="#catalogue"><svg fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="6.5"/><path stroke-linecap="round" d="M20 20l-4.2-4.2"/></svg>Find a {{ $words['noun'] }}</a>
                        <a href="#finder"><svg fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9.5 9a2.5 2.5 0 115 0c0 1.5-2.5 2-2.5 3.5m0 3h.01M12 21a9 9 0 110-18 9 9 0 010 18z"/></svg>{{ $words['finder'] }}</a>
                        @if ($guides->count())<a href="#guides"><svg fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.5C10.5 5 8 4.5 4 4.5v13c4 0 6.5.5 8 2m0-13c1.5-1.5 4-2 8-2v13c-4 0-6.5.5-8 2m0-13v13"/></svg>Read the guides</a>@endif
                    </div>
                </div>
                @if ($faces->count() === 3)
                    <div class="wk-mosaic" aria-hidden="true">
                        @foreach ($faces as $f)
                            <figure style="--g: {{ $P::GROUPS[$f['group']][2] }}">
                                <img src="{{ $f['thumb'] }}" alt="" referrerpolicy="no-referrer" @if ($loop->first) fetchpriority="high" @else loading="lazy" @endif>
                                <figcaption><i></i>{{ $P::GROUPS[$f['group']][0] }}: {{ $f['name'] }}</figcaption>
                            </figure>
                        @endforeach
                    </div>
                @endif
            </div>
            {{-- The sections, one row under the whole hero (it wrapped in the text column). --}}
            <div class="mt-8">
                <div class="sp-tabs">
                    <a href="{{ $S::url('crops') }}">Crop guides</a>
                    <a href="{{ $S::url('land-preparation') }}">Land preparation</a>
                    <a href="{{ $S::url('pests') }}" class="{{ $section === 'pests' ? 'is-on' : '' }}">Crop pests</a>
                    <a href="{{ $S::url('diseases') }}" class="{{ $section === 'diseases' ? 'is-on' : '' }}">Crop diseases</a>
                    <a href="{{ $S::url('weeds') }}">Weeds and grasses</a>
                    <a href="{{ $S::url('blog') }}">Latest in Agriculture</a>
                </div>
            </div>
        </div>
    </section>

    {{-- The shelves: one card per crop group. --}}
    <section class="bg-white">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 py-12 sm:py-16">
            <div class="wk-sec-h">
                <h2>{{ ucfirst($words['nouns']) }} by Crop</h2>
                <p>Start from the crop in front of you. Each shelf holds the {{ $words['nouns'] }} that matter most on Philippine farms, with their local names and how to manage them.</p>
            </div>
            <div class="wk-groups mt-8" style="grid-template-columns: repeat(auto-fit, minmax(min(100%, 15rem), 1fr))">
                @foreach ($P::GROUPS as $g => [$gLabel, $gLocal, $gHue, $gIcon])
                    @php($inG = $items->where('group', $g)->values())
                    @continue($inG->isEmpty())
                    <div class="wk-group" style="--g: {{ $gHue }}">
                        <div class="wk-group-top">
                            <span class="wk-gico"><svg fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true"><path d="{{ $gIcon }}"/></svg></span>
                            <h3>{{ $gLabel }}<small>{{ $inG->count() }} {{ $words['nouns'] }} · {{ $gLocal }}</small></h3>
                        </div>
                        <p class="ex">Like <b>{{ $inG->take(3)->pluck('name')->map(fn ($n) => preg_replace('/\s*\(.*\)$/', '', $n))->join(', ', ' and ') }}</b>.</p>
                        <button type="button" data-show-group="{{ $g }}">See them all
                            <svg fill="none" stroke="currentColor" stroke-width="2.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg></button>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- The catalogue --}}
    <section class="bg-[#fbfcf9] border-t border-[#eef3e8]" id="catalogue" style="scroll-margin-top: 5rem">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 py-12 sm:py-16">
            <div class="wk-sec-h">
                <h2>{{ $words['catalogue'] }}</h2>
                <p>{{ $items->count() }} {{ $words['nouns'] }} of palay, mais, gulay and the fruit and plantation crops. {{ $words['catalogueLead'] }}</p>
            </div>
            <div class="wk-tools mt-7">
                <label class="wk-search">
                    <span class="sr-only">Search the {{ $words['nouns'] }}</span>
                    <svg fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="6.5"/><path stroke-linecap="round" d="M20 20l-4.2-4.2"/></svg>
                    <input type="search" id="wkQ" placeholder="Search a name" autocomplete="off">
                </label>
                <div class="sp-cats wk-cats" id="wkCats" role="group" aria-label="Crop">
                    <button type="button" class="is-on" data-group="">All <i>{{ $items->count() }}</i></button>
                    @foreach ($P::GROUPS as $g => [$gLabel])
                        @if ($count($g))<button type="button" data-group="{{ $g }}">{{ $g === 'fruits' ? 'Fruits and plantation' : $gLabel }} <i>{{ $count($g) }}</i></button>@endif
                    @endforeach
                </div>
                <span class="wk-count" id="wkCount" aria-live="polite"></span>
            </div>
            <div class="wk-grid mt-6" id="wkGrid">
                @foreach ($items as $w)
                    @php($gh = $P::GROUPS[$w['group']][2])
                    <a href="{{ $w['url'] }}" class="wk-card" style="--g: {{ $gh }}" data-group="{{ $w['group'] }}"
                       data-q="{{ \Illuminate\Support\Str::lower($w['name'] . ' ' . $w['sci'] . ' ' . $w['local'] . ' ' . $w['kind']) }}">
                        <span class="wk-ph">
                            @if ($w['thumb'])
                                <img src="{{ $w['thumb'] }}" alt="{{ $w['alt'] }}" loading="lazy" width="480" height="360" referrerpolicy="no-referrer">
                            @else
                                <span class="none"><svg fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true"><path d="{{ $P::GROUPS[$w['group']][3] }}"/></svg></span>
                            @endif
                            <span class="wk-tag">{{ $P::GROUPS[$w['group']][0] === 'Fruits and Plantation Crops' ? 'Fruits' : $P::GROUPS[$w['group']][0] }}</span>
                            <span class="wk-tag is-kind" style="--g: {{ $kindHue[$w['kind']] ?? 98 }}">{{ $w['kind'] }}</span>
                        </span>
                        <span class="wk-in">
                            <b>{{ $w['name'] }}</b>
                            <em>{{ $w['sci'] }}</em>
                            @if ($w['local'] !== '')<p><span>Local names:</span> {{ $w['local'] }}</p>@elseif ($w['hint'] !== '')<p>{{ $w['hint'] }}</p>@endif
                        </span>
                    </a>
                @endforeach
            </div>
            <div class="wk-empty mt-6" id="wkEmpty">
                Nothing by that name in the catalogue yet. Try its scientific name, or take a photo and
                <a href="{{ url('/features/ai-agricultural-technician') }}">ask Anee, the smart farm technician</a>.
            </div>
        </div>
    </section>

    {{-- The finder --}}
    <section class="wc-sec" id="finder">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 py-12 sm:py-16">
            <div class="wk-sec-h">
                <span class="sp-chip">{{ $isPests ? 'Pest finder' : 'Disease finder' }}</span>
                <h2 class="mt-3">{{ $words['finder'] }}</h2>
                <p>{{ $words['finderLead'] }}</p>
            </div>
            @include('public.site.partials.problem-finder', ['gate' => true])
        </div>
    </section>

    @if ($guides->count())
        <section class="bg-white" id="guides" style="scroll-margin-top: 5rem">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 py-12 sm:py-16">
                <div class="wk-sec-h">
                    <h2>{{ $words['guides'] }}</h2>
                    <p>{{ $words['guidesLead'] }}</p>
                </div>
                <div class="sp-grid mt-8">
                    @foreach ($guides as $p)
                        @include('public.site.tile', ['p' => $p])
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    <section class="anee-band">
        <div class="relative max-w-4xl mx-auto px-4 sm:px-6 py-14 sm:py-16 text-center">
            <p class="text-sm font-bold uppercase tracking-wider text-accent-400">{{ $words['bandKick'] }}</p>
            <h2 class="mt-2 font-heading text-3xl sm:text-4xl font-bold text-white text-balance">{{ $words['bandTitle'] }}</h2>
            <p class="mt-4 text-[#cdd8c0] leading-relaxed max-w-2xl mx-auto">{{ $words['bandText'] }}</p>
            <div class="mt-8 flex flex-col sm:flex-row justify-center gap-3">
                <a href="{{ route('signup') }}" class="btn btn-accent btn-lg">Start free</a>
                <a href="{{ url('/features/ai-agricultural-technician') }}" class="btn btn-lg btn-on-dark">Meet Anee</a>
            </div>
        </div>
    </section>

@endsection

@push('scripts')
<script>
(() => {
    /* The catalogue: a crop chip and the search box narrow the cards together. */
    const grid = document.getElementById('wkGrid'), cats = document.getElementById('wkCats'), q = document.getElementById('wkQ');
    const count = document.getElementById('wkCount'), empty = document.getElementById('wkEmpty');
    if (!grid || !cats || !q) return;
    const cards = [...grid.querySelectorAll('.wk-card')];
    let group = '';
    const norm = (s) => s.toLowerCase().replace(/[^a-z0-9ñ ]+/g, ' ').replace(/\s+/g, ' ').trim();
    const apply = () => {
        const words = norm(q.value).split(' ').filter(Boolean);
        let shown = 0;
        cards.forEach((c) => {
            const on = (!group || c.dataset.group === group) && words.every((w) => norm(c.dataset.q).includes(w));
            const was = !c.classList.contains('is-out');
            c.classList.toggle('is-out', !on);
            if (on && !was) { c.classList.remove('is-in'); c.style.animationDelay = Math.min(shown, 12) * 25 + 'ms'; void c.offsetWidth; c.classList.add('is-in'); }
            if (on) shown++;
        });
        count.textContent = shown === cards.length ? cards.length + ' in all' : 'Showing ' + shown + ' of ' + cards.length;
        empty.classList.toggle('is-on', shown === 0);
    };
    const setGroup = (g) => { group = g; cats.querySelectorAll('button').forEach((b) => b.classList.toggle('is-on', b.dataset.group === g)); apply(); };
    cats.addEventListener('click', (e) => { const b = e.target.closest('button[data-group]'); if (b) setGroup(b.dataset.group); });
    q.addEventListener('input', apply);
    document.querySelectorAll('[data-show-group]').forEach((b) => b.addEventListener('click', () => {
        setGroup(b.dataset.showGroup);
        document.getElementById('catalogue').scrollIntoView({ behavior: matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth' });
    }));
    const want = new URLSearchParams(location.search).get('group');
    if (want && cats.querySelector('[data-group="' + CSS.escape(want) + '"]')) setGroup(want); else apply();
})();
</script>
@endpush
