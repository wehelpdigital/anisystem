@extends('layouts.public')

{{-- A short fact sheet (2026-10-08): one pest, disease or weed of the field
     catalogue that has no profile page written yet. Everything on it comes
     from its row (App\Support\FieldCatalogue): its names, the crops it
     troubles, where it shows, what to spray or why not, and the sources it
     was taken from. Kept out of search engines until a full page replaces it
     (SitePageController::show). --}}
@include('public.partials.site-css')
@include('public.site.css')

@php
    $S = \App\Support\SitePages::class;
    $F = \App\Support\FieldCatalogue::class;
    $P = \App\Support\ProblemCatalogue::class;
    $W = \App\Support\WeedControl::class;
    $isWeed = $section === 'weeds';
    $isPest = $section === 'pests';
    $crops = collect($e['crops'])->filter(fn ($c) => isset($F::crops()[$c]))->values();
    $firstCrop = $crops->first() ?? 'rice';
    $noun = $isWeed ? 'weed' : ($isPest ? 'pest' : 'disease');
    // The local names, when they are names and not a sentence about them.
    $local = trim((string) $e['local']);
    $local = preg_match('/\b(is|are|call|called|means)\b/i', $local) ? '' : $local;
    $shelf = $isWeed ? null : ($F::SHELVES[$e['group']] ?? null);
    $hue = $isWeed ? (['grasses' => 98, 'sedges' => 168, 'broadleaves' => 38][$e['group']] ?? 98) : ($P::KINDS[$e['kind']] ?? 98);
    $where = $isWeed ? collect() : collect($isPest ? $e['parts'] : $e['signs'])->map(fn ($k) => ($isPest ? $P::PARTS : $P::SIGNS)[$k] ?? null)->filter()->values();
    $title = $e['name'] . ($isWeed ? ': How to Tell It and Control It' : ': Signs and What to Spray');
    $desc = \Illuminate\Support\Str::limit(trim($e['hint'] . ' ' . ($isWeed ? 'A weed of ' : 'Found on ') . $crops->take(4)->map(fn ($c) => $F::cropWord($c))->join(', ', ' and ') . ' in the Philippines.'), 155);
    $canonical = $S::url($section, $slug);
@endphp

@section('title_full', $title . ' | anee.io')
@section('meta_description', $desc)

@push('head')
    <link rel="canonical" href="{{ $canonical }}">
    <meta property="og:type" content="article">
    <meta property="og:title" content="{{ $title }}">
    <meta property="og:description" content="{{ $desc }}">
    <meta property="og:url" content="{{ $canonical }}">
    <style>
        .fs-kind { display: inline-flex; align-items: center; gap: .35rem; padding: .2rem .6rem; border-radius: 999px; font-size: .72rem; font-weight: 800; letter-spacing: .05em;
            text-transform: uppercase; color: hsl(var(--g) 55% 22%); background: hsl(var(--g) 55% 91%); }
        .fs-sheet { display: inline-flex; align-items: center; gap: .35rem; font-size: .8rem; font-weight: 700; color: #6b7280; }
        .fs-sheet svg { width: .95rem; height: .95rem; }
        .fs-box { margin-top: 1.4rem; padding: 1.1rem 1.15rem; border-radius: 1.1rem; background: #fff; border: 1px solid #e5ebdf; }
        .fs-box:first-child { margin-top: 0; }
        .fs-box > h2 { margin: 0 0 .7rem !important; font-family: var(--font-heading); font-size: 1.15rem !important; font-weight: 800; color: #14210c; }
        .fs-box > p { margin: 0; font-size: .97rem; line-height: 1.65; color: #374151; }
        .fs-chips { display: flex; flex-wrap: wrap; gap: .4rem; }
        .fs-chips a, .fs-chips span { display: inline-flex; align-items: center; gap: .4rem; padding: .4rem .7rem; border-radius: .8rem; font-size: .88rem; font-weight: 700;
            color: #14210c; background: #f6f8f3; border: 1px solid #e5ebdf; text-decoration: none; transition: border-color .28s cubic-bezier(.22,1,.36,1), background-color .28s cubic-bezier(.22,1,.36,1); }
        .fs-chips a:hover { border-color: #a8cc7e; background: #fff; }
        .fs-chips i { font-style: normal; font-size: 1.05rem; }
        .fs-ais { display: flex; flex-wrap: wrap; gap: .4rem; }
        .fs-ais li { display: inline-flex; align-items: center; gap: .45rem; padding: .35rem .4rem .35rem .7rem; border-radius: .8rem; background: #f6f8f3; border: 1px solid #e1e9d7;
            font-size: .9rem; font-weight: 700; color: #14210c; }
        .fs-ais li i { font-style: normal; font-size: .7rem; font-weight: 800; padding: .14rem .45rem; border-radius: 999px; color: #2d5016; background: #e4efd4; white-space: nowrap; }
        .fs-ais li.is-plain { padding-right: .7rem; }
        .fs-legend { margin-top: .7rem !important; font-size: .84rem !important; color: #6b7280 !important; }
        .fs-no { display: flex; gap: .55rem; align-items: flex-start; padding: .75rem .85rem; border-radius: .9rem; font-size: .93rem; line-height: 1.55; color: #6b4a00; background: #fff8e6; border: 1px solid #f5d98a; }
        .fs-no svg { flex: none; width: 1.1rem; height: 1.1rem; margin-top: .15rem; color: #c79e00; }
        .fs-src { display: grid; gap: .35rem; margin: 0; padding: 0; list-style: none; }
        .fs-src a { font-size: .9rem; font-weight: 700; color: #3d6823; text-decoration: underline; text-underline-offset: 3px; overflow-wrap: anywhere; }
        .fs-more { display: grid; gap: .1rem; }
        .fs-more a { display: flex; align-items: center; gap: .4rem; min-height: 2.4rem; font-size: .92rem; font-weight: 700; color: #3d6823; text-decoration: none; }
        .fs-more a:hover { text-decoration: underline; }
        .fs-more a svg { flex: none; width: .85rem; height: .85rem; color: #86b556; }
        @media (prefers-reduced-motion: reduce) { .fs-chips a { transition: none; } }
    </style>
@endpush

@section('content')
    <article>
        <header class="sp-hero">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 pt-8 sm:pt-10 pb-8">
                <nav class="sp-crumbs" aria-label="Breadcrumb">
                    <a href="{{ url('/') }}">Home</a>
                    <svg fill="none" stroke="currentColor" stroke-width="2.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                    <a href="{{ $S::url($section) }}">{{ $meta['crumb'] }}</a>
                    <span class="sp-here">
                        <svg fill="none" stroke="currentColor" stroke-width="2.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                        <span class="truncate max-w-[14rem] sm:max-w-none" aria-current="page">{{ $e['name'] }}</span>
                    </span>
                </nav>
                <div class="mt-5">
                    @if ($isWeed)
                        <a href="{{ $S::url('weeds') }}?group={{ $e['group'] }}#catalogue" class="sp-chip">{{ $W::GROUPS[$e['group']]['label'] ?? 'Weeds' }}</a>
                    @else
                        <a href="{{ $S::url($section) }}?group={{ $e['group'] }}#catalogue" class="sp-chip">{{ $shelf[0] ?? ucfirst($section) }}</a>
                        <a href="{{ $S::url($section) }}?crop={{ $firstCrop }}#finder" class="sp-finder-link">{{ $isPest ? 'Not sure it is this pest? Use the Pest Finder' : 'Not sure it is this disease? Use the Disease Finder' }} ›</a>
                    @endif
                    <h1 class="sp-h1 mt-3">{{ $e['name'] }}</h1>
                    @if (trim($e['hint']) !== '')<p class="sp-lead mt-4">{{ $e['hint'] }}</p>@endif
                    <div class="sp-meta mt-4">
                        @if ($e['sci'] !== '' && $e['sci'] !== $e['name'])<span class="italic">{{ $e['sci'] }}</span>@endif
                        <span class="fs-kind" style="--g: {{ $hue }}">{{ $isWeed ? ($W::GROUPS[$e['group']]['label'] ?? '') . ($e['life'] !== '' ? ', ' . strtolower($e['life']) : '') : $e['kind'] }}</span>
                        <span class="fs-sheet"><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.6a1 1 0 01.7.3l5.4 5.4a1 1 0 01.3.7V19a2 2 0 01-2 2z"/></svg>Fact sheet</span>
                    </div>
                </div>
            </div>
        </header>

        <div class="max-w-6xl mx-auto px-4 sm:px-6 pt-7 pb-10 sm:py-14">
            <div class="sp-wrap">
                <div class="sp-body">
                    @if ($local !== '')
                        <section class="fs-box">
                            <h2>Also called</h2>
                            <p>{{ $local }}</p>
                        </section>
                    @endif

                    <section class="fs-box">
                        <h2>{{ $isWeed ? 'Crops it troubles' : ($isPest ? 'Crops it attacks' : 'Crops it strikes') }}</h2>
                        <div class="fs-chips">
                            @foreach ($crops as $c)
                                @php([$cl, $ce, $ci] = $F::crops()[$c])
                                <a href="{{ $isWeed ? $S::url('weeds') . '?crop=' . $c . '#control' : $S::url($section) . '?crop=' . $c . '#finder' }}"><i aria-hidden="true">{{ $ci }}</i>{{ $cl }}@if ($cl !== $ce) <small class="text-gray-500 font-semibold">{{ $ce }}</small>@endif</a>
                            @endforeach
                        </div>
                    </section>

                    @if ($where->count())
                        <section class="fs-box">
                            <h2>{{ $isPest ? 'Where the damage shows' : 'What you see' }}</h2>
                            <div class="fs-chips">@foreach ($where as $wl)<span>{{ $wl }}</span>@endforeach</div>
                        </section>
                    @endif

                    @if ($isWeed)
                        <section class="fs-box">
                            <h2>How to tell it is {{ ['grasses' => 'a grass', 'sedges' => 'a sedge', 'broadleaves' => 'a broadleaf'][$e['group']] ?? 'this kind' }}</h2>
                            <p>{{ $W::GROUPS[$e['group']]['hint'] ?? '' }} The group decides what kills it, so check the stem and the leaf before you buy a herbicide.</p>
                        </section>
                        <section class="fs-box">
                            <h2>How to control it</h2>
                            <p>What to do and which active ingredients work depend on your crop and how old it is. The weed control helper gives both, for {{ $F::cropWord($firstCrop) }} and every other crop it grows in.</p>
                            <p class="mt-3"><a href="{{ $S::url('weeds') }}?crop={{ $firstCrop }}&amp;group={{ $e['group'] }}#control" class="btn btn-outline btn-sm">Open the weed control helper</a></p>
                        </section>
                    @elseif (count($e['ai']))
                        <section class="fs-box">
                            <h2>What to spray</h2>
                            <ul class="fs-ais">
                                @foreach ($e['ai'] as [$ing, $grp])
                                    <li class="{{ $grp ? '' : 'is-plain' }}">{{ $ing }}@if ($grp)<i>{{ $grp }}</i>@endif</li>
                                @endforeach
                            </ul>
                            <p class="fs-legend">Active ingredients only, never brands. The {{ $isPest ? 'IRAC' : 'FRAC' }} group tells how it works: switch to a different group next time, so the {{ $noun }} does not learn to survive it. Spray only when the damage is worth the cost, and only with products registered with the <a href="{{ $S::url('blog', 'fertilizer-and-pesticide-authority') }}" class="underline">Fertilizer and Pesticide Authority</a> for your crop. Follow the label.</p>
                            @if ($e['no'] !== '')<p class="fs-no mt-3"><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.3 3.9L1.8 18a2 2 0 001.7 3h17a2 2 0 001.7-3L13.7 3.9a2 2 0 00-3.4 0z"/></svg><span>{{ $e['no'] }}</span></p>@endif
                        </section>
                    @elseif ($e['no'] !== '')
                        <section class="fs-box">
                            <h2>Before you spray</h2>
                            <p class="fs-no"><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.3 3.9L1.8 18a2 2 0 001.7 3h17a2 2 0 001.7-3L13.7 3.9a2 2 0 00-3.4 0z"/></svg><span>{{ $e['no'] }}</span></p>
                        </section>
                    @endif

                    @if (count($e['sources']))
                        <section class="fs-box">
                            <h2>Where this comes from</h2>
                            <ul class="fs-src">
                                @foreach ($e['sources'] as $src)
                                    @if (! empty($src['url']))<li><a href="{{ $src['url'] }}" target="_blank" rel="noopener nofollow">{{ $src['label'] ?: $src['url'] }}</a></li>@elseif (! empty($src['label']))<li>{{ $src['label'] }}</li>@endif
                                @endforeach
                            </ul>
                        </section>
                    @endif

                    <section class="fs-box">
                        <h2>Not sure it is this one?</h2>
                        <p>Many {{ $isWeed ? 'weeds' : ($isPest ? 'pests' : 'diseases') }} look alike. Send a photo to Anee, the smart farm technician in anee.io. She reads it with your crop, its age and the weather, and tells you what it most likely is and what to do first. In Tagalog or English.</p>
                        <p class="mt-3"><a href="{{ url('/features/ai-agricultural-technician') }}" class="btn btn-accent btn-sm">Meet Anee</a></p>
                    </section>
                </div>
                <aside class="sp-side">
                    <div class="sp-card sp-wcard">
                        @if ($isWeed)
                            <h2 class="sp-card-h">Weed control by crop age</h2>
                            <p>Pick your crop and how old it is, and see the active ingredients that work on {{ strtolower($W::GROUPS[$e['group']]['label'] ?? 'weeds') }} at that age.</p>
                            <a href="{{ $S::url('weeds') }}?crop={{ $firstCrop }}&amp;group={{ $e['group'] }}#control" class="btn btn-outline btn-sm">Open the helper</a>
                        @else
                            <h2 class="sp-card-h">{{ $isPest ? 'What is attacking my crop?' : 'What is wrong with my crop?' }}</h2>
                            <p>Pick your crop and what you see, and compare the {{ $section }} that match.</p>
                            <a href="{{ $S::url($section) }}?crop={{ $firstCrop }}#finder" class="btn btn-outline btn-sm">Open the finder</a>
                        @endif
                    </div>
                    @if ($more->count())
                        <div class="sp-card">
                            <h2 class="sp-card-h">More {{ $isWeed ? 'weeds' : $section }} of {{ $F::cropWord($firstCrop) }}</h2>
                            <div class="fs-more">
                                @foreach ($more as $m)
                                    <a href="{{ $m['url'] }}"><svg fill="none" stroke="currentColor" stroke-width="2.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>{{ $m['name'] }}</a>
                                @endforeach
                            </div>
                        </div>
                    @endif
                    <div class="sp-promo">
                        <b>Your whole season in one app</b>
                        <p>A cropping calendar that dates every task, a record of your fertilizer and costs, and Anee, the smart farm technician who answers in Tagalog or English.</p>
                        <a href="{{ route('signup') }}" class="btn btn-accent">Start free</a>
                    </div>
                </aside>
            </div>
        </div>
    </article>
@endsection
