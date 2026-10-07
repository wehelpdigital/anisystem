@extends('layouts.public')

{{-- How It Works (2026-10-01): the season in seven steps, Anee at every one.
     The whole picture is the shared partial (public/how/journey), which the
     dashboard's tour uses too; this page adds the head and the way in. --}}
@include('public.partials.site-css')

@section('title', 'How anee.io Works: Anee at Every Step of the Season')
@section('meta_description', 'See how anee.io works from the first plan to the last sack: plan, plant, grow, protect, harvest and look back, with Anee, your smart farm technician, at every step.')

@push('head')
    <link rel="canonical" href="{{ route('how') }}">
    <meta property="og:title" content="How anee.io works">
    <meta property="og:description" content="Plan, build the plan, plant, grow, protect, harvest and look back, with Anee, your smart farm technician, at every step.">
    <meta property="og:url" content="{{ route('how') }}">
    <meta property="og:image" content="{{ asset('images/site/photos/palay-phone.jpg') }}">
    @php
        $hwSchema = [
            '@context' => 'https://schema.org',
            '@type' => 'HowTo',
            'name' => 'How a cropping season runs on anee.io',
            'description' => 'Seven steps from the first plan to the last sack, with Anee, your smart farm technician, at every step.',
            'step' => collect(\App\Support\HowItWorks::stages())->values()->map(fn ($s, $i) => [
                '@type' => 'HowToStep',
                'position' => $i + 1,
                'name' => $s['title'],
                'text' => $s['lede'],
                'url' => route('how') . '#hw-step-' . $s['key'],
            ])->all(),
        ];
    @endphp
    <script type="application/ld+json">{!! json_encode($hwSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
@endpush

@section('content')
    @include('public.how.journey', ['hwMode' => 'site'])

    {{-- The way in, on the light ground the rest of the site wears. --}}
    <section class="py-16 sm:py-20 bg-white">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 text-center reveal">
            <p class="text-sm font-bold uppercase tracking-wider text-brand-600">Your season, start to finish</p>
            <h2 class="mt-2 font-heading text-3xl sm:text-4xl font-bold text-ink text-balance">Bring your next season to anee.io</h2>
            <p class="mt-4 text-gray-600">Start free on the Libre plan, with no payment and no trial clock. Anee's analyses run on credits, and some tools come with paid plans.</p>
            <div class="mt-8 flex flex-col sm:flex-row justify-center gap-3">
                <a href="{{ route('signup') }}" class="btn btn-primary btn-lg">Start free</a>
                <a href="{{ route('pricing') }}" class="btn btn-outline btn-lg">See the plans</a>
            </div>
            <a href="{{ url('/ask-anee') }}" class="ask-pill mt-6" style="display:inline-flex">
                <img src="{{ asset('images/anee/avatar-160.jpg') }}" alt="" aria-hidden="true">
                <span>Or ask Anee one question for free</span>
            </a>
        </div>
    </section>
@endsection
