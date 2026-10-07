@extends('layouts.public')

{{-- /weeds (2026-10-06): the weeds of Philippine rice fields. The three
     groups, the catalogue (one card per weed profile, filtered by group and
     searched by any of its names), the weed control helper by rice age, and
     the long guides. The words of each weed live on its page; the card facts
     come from App\Support\WeedCatalogue and the helper's from
     App\Support\WeedControl. --}}
@include('public.partials.site-css')
@include('public.site.css')

@php
    $S = \App\Support\SitePages::class;
    $W = \App\Support\WeedControl::class;
    $C = \App\Support\WeedCatalogue::class;
    $live = $pages->keyBy('slug');
    // The weeds, in the catalogue's order, that have a live page.
    $weeds = collect($C::WEEDS)->filter(fn ($w, $slug) => $live->has($slug))
        ->map(function ($w, $slug) use ($live, $S) {
            $h = is_array($live[$slug]->heroImage) ? $live[$slug]->heroImage : [];
            return $w + ['slug' => $slug, 'url' => $S::pageUrl($live[$slug]),
                'thumb' => $S::img($h['thumb'] ?? ($h['src'] ?? null)), 'alt' => $h['alt'] ?? $w['name']];
        });
    $guides = $pages->filter(fn ($p) => ! $C::get($p->slug))->values();
    $count = fn ($g) => $weeds->where('group', $g)->count();
    // One photo per group for the hero, the first weed that has one.
    $faces = collect(array_keys($W::GROUPS))->map(fn ($g) => $weeds->where('group', $g)->first(fn ($w) => $w['thumb']))->filter()->values();
    $groupIcons = [
        'grasses' => 'M6 21c0-6 1.2-11 4-15M12 21V3.5M18 21c0-6-1.2-11-4-15',
        'sedges' => 'M12 4.5l7.5 13h-15zM12 17.5V21',
        'broadleaves' => 'M5 19C5 10 10 5 19 5c0 9-5 14-14 14zM5 19L15 9M9.5 14.5h4M12 12V8.5',
    ];
    $groupHue = ['grasses' => 98, 'sedges' => 168, 'broadleaves' => 38];
@endphp

@section('title_full', $meta['metaTitle'] . ' | anee.io')
@section('meta_description', $meta['metaDescription'])

@push('head')
    <link rel="canonical" href="{{ $S::url($section) }}">
    <meta property="og:title" content="{{ $meta['metaTitle'] }}">
    <meta property="og:description" content="{{ $meta['metaDescription'] }}">
    <meta property="og:url" content="{{ $S::url($section) }}">
    @if ($faces->count())<meta property="og:image" content="{{ $faces[0]['thumb'] }}">@endif
    {{-- @@context: a bare @context is a Blade directive, and printed PHP into this JSON. --}}
    <script type="application/ld+json">{!! json_encode([
        '@@context' => 'https://schema.org',
        '@type' => 'CollectionPage',
        'name' => $meta['hubTitle'],
        'description' => $meta['metaDescription'],
        'url' => $S::url($section),
        'hasPart' => $pages->map(fn ($p) => ['@type' => 'Article', 'headline' => $p->title, 'url' => $S::pageUrl($p)])->values()->all(),
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
    @include('public.site.catalogue-css')
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
                        <a href="#catalogue"><svg fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="6.5"/><path stroke-linecap="round" d="M20 20l-4.2-4.2"/></svg>Find a weed</a>
                        <a href="#control"><svg fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3M4 11h16M5 5h14a1 1 0 011 1v13a1 1 0 01-1 1H5a1 1 0 01-1-1V6a1 1 0 011-1z"/></svg>Control by rice age</a>
                        <a href="#guides"><svg fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.5C10.5 5 8 4.5 4 4.5v13c4 0 6.5.5 8 2m0-13c1.5-1.5 4-2 8-2v13c-4 0-6.5.5-8 2m0-13v13"/></svg>Read the guides</a>
                    </div>
                </div>
                @if ($faces->count() === 3)
                    <div class="wk-mosaic" aria-hidden="true">
                        @foreach ($faces as $f)
                            <figure style="--g: {{ $groupHue[$f['group']] }}">
                                <img src="{{ $f['thumb'] }}" alt="" @if ($loop->first) fetchpriority="high" @else loading="lazy" @endif>
                                <figcaption><i></i>{{ $W::GROUPS[$f['group']]['label'] }}: {{ $f['name'] }}</figcaption>
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
                    <a href="{{ $S::url('pests') }}">Crop pests</a>
                    <a href="{{ $S::url('diseases') }}">Crop diseases</a>
                    <a href="{{ $S::url('weeds') }}" class="is-on">Weeds and grasses</a>
                    <a href="{{ $S::url('blog') }}">Latest in Agriculture</a>
                </div>
            </div>
        </div>
    </section>

    {{-- The three groups: the first thing to know about any weed. --}}
    <section class="bg-white">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 py-12 sm:py-16">
            <div class="wk-sec-h">
                <h2>Grasses, Sedges or Broadleaves?</h2>
                <p>
                    Every weed in a rice field belongs to one of three groups, and the group decides what kills it. Look at the stem and the leaf first.
                    Our guide to the <a href="{{ $S::url('weeds', 'types-of-weeds') }}" class="font-bold text-brand-700 underline decoration-[#a8cc7e] underline-offset-4">types of weeds</a> goes further.
                </p>
            </div>
            <div class="wk-groups mt-8">
                @php($marks = [
                    'grasses' => ['Round, hollow stems with clear joints', 'Long, narrow leaves in two rows, with parallel veins', 'A leaf sheath split open around the stem'],
                    'sedges' => ['Solid, three sided stems with no joints', 'Narrow leaves with no ligule at the collar', 'A leaf sheath closed all the way around the stem'],
                    'broadleaves' => ['Wide leaves with a net of veins', 'Many plant families and many shapes', 'Flowers, stems and branches of every color'],
                ])
                @foreach ($W::GROUPS as $g => $info)
                    <div class="wk-group" style="--g: {{ $groupHue[$g] }}">
                        <div class="wk-group-top">
                            <span class="wk-gico"><svg fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true"><path d="{{ $groupIcons[$g] }}"/></svg></span>
                            <h3>{{ $info['label'] }}<small>{{ $count($g) }} weeds in the catalogue</small></h3>
                        </div>
                        <ul>@foreach ($marks[$g] as $m)<li>{{ $m }}</li>@endforeach</ul>
                        <button type="button" data-show-group="{{ $g }}">See the {{ strtolower($info['label']) }}
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
                <h2>Weeds of Philippine Rice Fields</h2>
                <p>{{ $weeds->count() }} weeds that grow in Philippine rice fields. Search by any name you know, English, scientific or local, like bayakibok or gabi gabi.</p>
            </div>
            <div class="wk-tools mt-7">
                <label class="wk-search">
                    <span class="sr-only">Search the weeds</span>
                    <svg fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="6.5"/><path stroke-linecap="round" d="M20 20l-4.2-4.2"/></svg>
                    <input type="search" id="wkQ" placeholder="Search a name, like humay humay" autocomplete="off">
                </label>
                <div class="sp-cats wk-cats" id="wkCats" role="group" aria-label="Weed group">
                    <button type="button" class="is-on" data-group="">All <i>{{ $weeds->count() }}</i></button>
                    @foreach ($W::GROUPS as $g => $info)
                        <button type="button" data-group="{{ $g }}">{{ $info['label'] }} <i>{{ $count($g) }}</i></button>
                    @endforeach
                </div>
                <span class="wk-count" id="wkCount" aria-live="polite"></span>
            </div>
            <div class="wk-grid mt-6" id="wkGrid">
                @foreach ($weeds as $w)
                    <a href="{{ $w['url'] }}" class="wk-card" style="--g: {{ $groupHue[$w['group']] }}" data-group="{{ $w['group'] }}"
                       data-q="{{ \Illuminate\Support\Str::lower($w['name'] . ' ' . $w['sci'] . ' ' . $w['local']) }}">
                        <span class="wk-ph">
                            @if ($w['thumb'])
                                <img src="{{ $w['thumb'] }}" alt="{{ $w['alt'] }}" loading="lazy" width="480" height="360">
                            @else
                                <span class="none"><svg fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true"><path d="{{ $groupIcons[$w['group']] }}"/></svg></span>
                            @endif
                            <span class="wk-tag">{{ $W::GROUPS[$w['group']]['label'] }}</span>
                        </span>
                        <div class="wk-in">
                            <b>{{ $w['name'] }}</b>
                            @if ($w['sci'] !== $w['name'])<em>{{ $w['sci'] }}</em>@endif
                            @if ($w['local'] !== '')<p><span>Local names:</span> {{ $w['local'] }}</p>@endif
                        </div>
                    </a>
                @endforeach
            </div>
            <div class="wk-empty mt-6" id="wkEmpty">
                No weed by that name in the catalogue yet. Try its scientific name, or take a photo and
                <a href="{{ url('/features/ai-agricultural-technician') }}">ask Anee, the smart farm technician</a>.
            </div>
        </div>
    </section>

    {{-- The weed control helper --}}
    <section class="wc-sec" id="control">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 py-12 sm:py-16">
            <div class="wk-sec-h">
                <span class="sp-chip">Weed control helper</span>
                <h2 class="mt-3">Weed Control by Rice Age</h2>
                <p>
                    Tell us how you planted, how old your rice is and which weeds you see. You get what to do first and the active ingredients that work at that age,
                    from PhilRice recommendations. Active ingredients only, never brands.
                </p>
            </div>
            @include('public.site.partials.weed-helper', ['gate' => true])
        </div>
    </section>

    {{-- The long guides --}}
    @if ($guides->count())
        <section class="bg-white" id="guides" style="scroll-margin-top: 5rem">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 py-12 sm:py-16">
                <div class="wk-sec-h">
                    <h2>Weed Guides for Rice Farmers</h2>
                    <p>The whole plan, start to finish: how to tell the weeds apart, how to keep them out across a season, and how to use herbicides safely.</p>
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
            <p class="text-sm font-bold uppercase tracking-wider text-accent-400">Not sure which weed it is?</p>
            <h2 class="mt-2 font-heading text-3xl sm:text-4xl font-bold text-white text-balance">Send Anee a Photo From the Field</h2>
            <p class="mt-4 text-[#cdd8c0] leading-relaxed max-w-2xl mx-auto">
                Anee, the smart farm technician in anee.io, tells you which weed it most likely is, its group and what to do at the age of your rice.
                Then anee.io puts the weeding days on your season calendar. In Tagalog or English.
            </p>
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
    /* The catalogue: a group chip and the search box narrow the cards
       together; the cards that stay rise into place. ?group= and #catalogue
       open it already narrowed (a weed page's group link). */
    const grid = document.getElementById('wkGrid');
    const cats = document.getElementById('wkCats');
    const q = document.getElementById('wkQ');
    const count = document.getElementById('wkCount');
    const empty = document.getElementById('wkEmpty');
    if (!grid || !cats || !q) return;
    const cards = [...grid.querySelectorAll('.wk-card')];
    let group = '';
    const norm = (s) => s.toLowerCase().replace(/[^a-z0-9ñ ]+/g, ' ').replace(/\s+/g, ' ').trim();
    const apply = () => {
        const words = norm(q.value).split(' ').filter(Boolean);
        let shown = 0;
        cards.forEach((c) => {
            const hay = norm(c.dataset.q);
            const on = (!group || c.dataset.group === group) && words.every((w) => hay.includes(w));
            const was = !c.classList.contains('is-out');
            c.classList.toggle('is-out', !on);
            if (on && !was) { c.classList.remove('is-in'); c.style.animationDelay = Math.min(shown, 12) * 25 + 'ms'; void c.offsetWidth; c.classList.add('is-in'); }
            if (on) shown++;
        });
        count.textContent = shown === cards.length ? cards.length + ' weeds' : 'Showing ' + shown + ' of ' + cards.length + ' weeds';
        empty.classList.toggle('is-on', shown === 0);
    };
    const setGroup = (g) => {
        group = g;
        cats.querySelectorAll('button').forEach((b) => b.classList.toggle('is-on', b.dataset.group === g));
        apply();
    };
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
