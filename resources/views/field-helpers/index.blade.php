@extends('layouts.app')
@php [$fhName, $fhLead, $fhIcon] = $tools[$tool]; @endphp
@section('title', $fhName)
@section('page-title', $fhName)
@section('page-subtitle', 'Field helpers')
@section('back', \App\Support\BackTo::url(route('app.dashboard')))

@push('head')
    @include('public.site.catalogue-css')
    <style>
        /* ---- Field helpers in the app (2026-10-07) -------------------------
           The public helpers' own parts, dressed for the app: a hero that
           says which helper this is, a switch between the three, and the
           helper's light cards given a dark face in night mode. */
        .fh-hero { position: relative; overflow: hidden; display: flex; align-items: center; gap: 1rem; padding: 1.05rem 1.15rem; border-radius: 1.3rem; color: #e8f1de;
            background: radial-gradient(120% 140% at 100% 0%, #4a7c2a 0%, #1d3310 55%, #0f1d08 100%); }
        .fh-hero img { flex: none; width: 3.4rem; height: 3.4rem; padding: .45rem; border-radius: 1rem; background: rgb(255 255 255 / .12); animation: fhBob 4s ease-in-out infinite; }
        @keyframes fhBob { 50% { transform: translateY(-3px) rotate(-2deg); } }
        .fh-hero h2 { font-family: var(--font-heading); font-weight: 800; font-size: 1.15rem; color: #fff; }
        .fh-hero p { margin-top: .2rem; font-size: .84rem; line-height: 1.5; color: #c9dcb5; }
        .fh-switch { position: relative; display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: .25rem; margin: .9rem 0 1.1rem; padding: .25rem; border-radius: 1rem;
            background: var(--color-white); border: 1px solid var(--color-gray-200); }
        .fh-switch a { display: flex; align-items: center; justify-content: center; gap: .4rem; padding: .55rem .4rem; border-radius: .8rem; font-size: .82rem; font-weight: 800; text-align: center;
            color: var(--color-gray-600); text-decoration: none; transition: background-color .28s cubic-bezier(.22,1,.36,1), color .28s cubic-bezier(.22,1,.36,1); }
        .fh-switch a img { width: 1.15rem; height: 1.15rem; }
        .fh-switch a.is-on { background: var(--color-brand-600); color: #fff; }
        .fh-switch a:not(.is-on):hover { background: var(--color-gray-50); }
        @media (max-width: 479.98px) { .fh-switch a { flex-direction: column; gap: .2rem; font-size: .72rem; } }
        .fh-body .wc { margin-top: 0 !important; }
        @media (min-width: 1024px) { .fh-body .wc-ask { top: calc(var(--app-head, 4rem) + 1rem); } }
        .fh-more { margin-top: 1rem; display: flex; flex-wrap: wrap; gap: .5rem; }
        .fh-more a { display: inline-flex; align-items: center; gap: .4rem; padding: .5rem .85rem; border-radius: 999px; font-size: .82rem; font-weight: 800; text-decoration: none;
            color: var(--color-brand-700); background: var(--color-white); border: 1px solid var(--color-gray-200); transition: border-color .28s cubic-bezier(.22,1,.36,1); }
        .fh-more a:hover { border-color: var(--color-brand-600); }
        /* Night: the helper's cards were drawn for a white page. */
        html.dark .fh-body .wc-ask, html.dark .fh-body .wc-card { background: #151b12; border-color: #2b3a1c; box-shadow: none; }
        html.dark .fh-body .wc-q > b, html.dark .fh-body .wc-body h3, html.dark .fh-body .fd-item b { color: #e8efe1; }
        html.dark .fh-body .wc-opts button { background: #1c2616; border-color: #2b3a1c; color: #d5e3c5; }
        html.dark .fh-body .wc-opts button[aria-checked="true"], html.dark .fh-body .wc-opts button[aria-pressed="true"] { background: #4a7c2a; border-color: #4a7c2a; color: #fff; }
        html.dark .fh-body .wc-steps li, html.dark .fh-body .fd-item small, html.dark .fh-body .wc-legend, html.dark .fh-body .wc-fine { color: #b9caa8; }
        html.dark .fh-body .wc-ings li, html.dark .fh-body .fd-card { background: #1c2616; border-color: #2b3a1c; color: #e8efe1; }
        html.dark .fh-body .fd-card:hover { background: #22301a; }
        html.dark .fh-body .fd-ais li { background: #151b12; border-color: #2b3a1c; color: #e8efe1; }
        html.dark .fh-body .wc-split > div { background: #1c2616; border-color: #2b3a1c; }
        html.dark .fh-body .wc-note { background: #241d10; border-color: #5c4a24; color: #f3d9a4; }
        html.dark .fh-body .wc-note.ok { background: #17220f; border-color: #2b3a1c; color: #d5e3c5; }
        html.dark .fh-body .fd-no { color: #f3d9a4; }
        @media (prefers-reduced-motion: reduce) { .fh-hero img { animation: none; } .fh-switch a, .fh-more a { transition: none; } }
    </style>
@endpush

@section('content')
<div class="max-w-6xl mx-auto">
    <div class="fh-hero">
        <img src="{{ asset('images/icons/' . $fhIcon . '.svg') }}" alt="">
        <div><h2>{{ $fhName }}</h2><p>{{ $fhLead }}. Active ingredients only, never brands. Free with your account.</p></div>
    </div>
    <nav class="fh-switch" aria-label="Field helpers">
        @foreach ($tools as $k => [$n, $l, $i])
            <a href="{{ route('fh.page', $k) }}" class="{{ $k === $tool ? 'is-on' : '' }}" @if ($k === $tool) aria-current="page" @endif><img src="{{ asset('images/icons/' . $i . '.svg') }}" alt="">{{ str_replace([' Control Helper', ' Finder'], ['s', 's'], $n) }}</a>
        @endforeach
    </nav>
    <div class="fh-body">
        @if ($tool === 'weeds')
            @include('public.site.partials.weed-helper', ['gate' => false, 'defaultCrop' => $defaultCrop])
        @else
            @include('public.site.partials.problem-finder', $facts + ['gate' => false, 'defaultCrop' => $defaultCrop])
        @endif
    </div>
    <div class="fh-more">
        @if ($tool === 'weeds')
            <a href="{{ url('/weeds') }}#catalogue">Every weed, with pictures ›</a>
            <a href="{{ url('/weeds/herbicides-for-rice-weeds') }}">The herbicide groups explained ›</a>
        @else
            <a href="{{ url('/' . $tool) }}#catalogue">The whole {{ $tool === 'pests' ? 'pest' : 'disease' }} catalogue ›</a>
        @endif
        @if (\Illuminate\Support\Facades\Route::has('ai.index'))
            <a href="{{ route('ai.index') }}">Send Anee a photo ›</a>
        @endif
    </div>
</div>
@endsection
