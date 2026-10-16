@extends('layouts.app')
@php [$fhName, $fhLead, $fhIcon] = $tools[$tool]; @endphp
@section('title', $fhName)
@section('page-title', $fhName)
@section('page-subtitle', 'Field helpers')
@section('back', \App\Support\BackTo::url(route('app.dashboard')))
{{-- A tool: the whole phone screen, no tab bar, no footer. --}}
@section('body-class', 'hide-tabbar no-footer')

@push('head')
    @include('public.site.catalogue-css')
    <style>
        /* ---- Field helpers in the app (2026-10-07; their own partials since
           2026-10-14) ------------------------------------------------------
           A hero that says which helper this is, then the helper. Each one
           stands alone: no switch between them. The questions and the answer
           card wear the public hubs' looks (catalogue-css), given a dark
           face in night mode; the rest is field-helpers/partials/kit. */
        .fh-hero { position: relative; overflow: hidden; display: flex; align-items: center; gap: 1rem; padding: 1.05rem 1.15rem; border-radius: 1.3rem; color: #e8f1de;
            background: radial-gradient(120% 140% at 100% 0%, #4a7c2a 0%, #1d3310 55%, #0f1d08 100%); }
        .fh-hero img { flex: none; width: 3.4rem; height: 3.4rem; padding: .45rem; border-radius: 1rem; background: rgb(255 255 255 / .12); animation: fhBobHero 4s ease-in-out infinite; }
        @keyframes fhBobHero { 50% { transform: translateY(-3px) rotate(-2deg); } }
        .fh-hero h2 { font-family: var(--font-heading); font-weight: 800; font-size: 1.15rem; color: #fff; }
        .fh-hero p { margin-top: .2rem; font-size: .84rem; line-height: 1.5; color: #c9dcb5; }
        .fh-body { margin-top: .9rem; }
        .fh-body .wc { margin-top: 0 !important; }
        @media (min-width: 1024px) { .fh-body .wc-ask { top: calc(var(--app-head, 4rem) + 1rem); } }
        .fh-more { margin-top: 1rem; display: flex; flex-wrap: wrap; gap: .5rem; }
        .fh-more a { display: inline-flex; align-items: center; gap: .4rem; padding: .5rem .85rem; border-radius: 999px; font-size: .82rem; font-weight: 800; text-decoration: none;
            color: var(--color-brand-700); background: var(--color-white); border: 1px solid var(--color-gray-200); transition: border-color .28s cubic-bezier(.22,1,.36,1); }
        .fh-more a:hover { border-color: var(--color-brand-600); }
        /* Night: the helper's cards were drawn for a white page. */
        html.dark .fh-body .wc-ask, html.dark .fh-body .wc-card { background: #151b12; border-color: #2b3a1c; box-shadow: none; }
        html.dark .fh-body .wc-q > b, html.dark .fh-body .wc-body h3 { color: #e8efe1; }
        html.dark .fh-body .wc-opts button { background: #1c2616; border-color: #2b3a1c; color: #d5e3c5; }
        html.dark .fh-body .wc-opts button[aria-checked="true"], html.dark .fh-body .wc-opts button[aria-pressed="true"] { background: #4a7c2a; border-color: #4a7c2a; color: #fff; }
        html.dark .fh-body .wc-steps li, html.dark .fh-body .wc-legend, html.dark .fh-body .wc-fine { color: #b9caa8; }
        html.dark .fh-body .wc-split > div { background: #1c2616; border-color: #2b3a1c; }
        html.dark .fh-body .wc-note { background: #241d10; border-color: #5c4a24; color: #f3d9a4; }
        html.dark .fh-body .wc-note.ok { background: #17220f; border-color: #2b3a1c; color: #d5e3c5; }
        @media (prefers-reduced-motion: reduce) { .fh-hero img { animation: none; } .fh-more a { transition: none; } }
    </style>
@endpush

@section('content')
<div class="max-w-6xl mx-auto">
    <div class="fh-hero">
        <img src="{{ asset('images/icons/' . $fhIcon . '.svg') }}" alt="">
        <div><h2>{{ $fhName }}</h2><p>{{ $fhLead }}. {{ $tool === 'varieties' ? 'Every registered variety, with its trial days and yield.' : ($tool === 'landprep' ? 'Following standard practice.' : ($tool === 'nutrients' ? 'Signs, causes and fixes by crop, with fertilizers registered here.' : ($tool === 'compat' ? 'Checked against every registered spray and fertilizer sold here, and the rules of safe mixing.' : 'Active ingredients, their groups and products sold here.'))) }}</p></div>
    </div>
    <div class="fh-body">
        @if ($tool === 'weeds')
            @include('field-helpers.partials.weeds', ['book' => $book, 'defaultCrop' => $defaultCrop])
        @elseif ($tool === 'landprep')
            @include('field-helpers.land-prep', ['book' => $landBook, 'defaultCrop' => $defaultCrop])
        @elseif ($tool === 'varieties')
            @include('field-helpers.partials.varieties', ['vBook' => $vBook, 'vCrops' => $vCrops])
        @elseif ($tool === 'nutrients')
            @include('field-helpers.partials.nutrients', ['book' => $book, 'defaultCrop' => $defaultCrop])
        @elseif ($tool === 'compat')
            @include('field-helpers.partials.compat', ['book' => $book])
        @else
            @include('field-helpers.partials.problems', ['book' => $book, 'defaultCrop' => $defaultCrop])
        @endif
    </div>
    <div class="fh-more">
        @if ($tool === 'landprep')
            <a href="{{ url('/land-preparation') }}">Every land preparation guide ›</a>
        @elseif ($tool === 'varieties' && \Illuminate\Support\Facades\Route::has('vary.page'))
            <a href="{{ route('vary.page') }}">Ask Anee to research varieties for your town ›</a>
        @endif
        @if (\Illuminate\Support\Facades\Route::has('ai.index') && ! in_array($tool, ['varieties', 'compat'], true))
            <a href="{{ route('ai.index') }}">Send Anee a photo ›</a>
        @endif
    </div>
</div>
@endsection
