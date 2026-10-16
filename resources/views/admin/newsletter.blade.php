@extends('layouts.admin')

@section('title', 'Newsletter')
@section('subtitle', 'The daily email by priority and group')

@section('content')
    {{-- The newsletter module (2026-10-17). The screen is a kit shared with the
         mother app's AniSystem > Newsletter (public/nl-kit), pointed here at
         this panel's own door to the same API (NewsletterController). --}}
    <div id="nlk"></div>
@endsection

@push('head')
    <link rel="stylesheet" href="/nl-kit/nl.css?v={{ $kitVersion }}">
    <style>
        /* The module wants the panel's full width for its builder and its lists. */
        .ad-main:has(#nlk) { max-width: 1180px; }
    </style>
@endpush

@push('scripts')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.2/Sortable.min.js"></script>
    <script src="/nl-kit/nl.js?v={{ $kitVersion }}"></script>
    <script>
        window.NLK.mount(document.getElementById('nlk'), {
            api: @json(url('/admin/newsletter/api') . '/'),
            csrf: @json(csrf_token()),
            userEmail: @json((string) auth()->user()->email),
        });
    </script>
@endpush
