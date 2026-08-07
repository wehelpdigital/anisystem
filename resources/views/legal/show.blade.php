@extends('layouts.public')

@section('title', $page->title)

@section('content')
<div class="bg-gray-50 py-10 md:py-16 px-4">
    <div class="max-w-3xl mx-auto">
        <div class="mb-6">
            <a href="{{ url()->previous() }}" class="text-sm font-semibold text-brand-600 hover:text-brand-700">← Back</a>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 md:p-10">
            <h1 class="text-2xl md:text-3xl font-bold text-gray-900" style="font-family:var(--font-heading)">{{ $page->title }}</h1>
            <p class="text-xs text-gray-400 mt-1">Last updated {{ $page->updated_at?->format('F j, Y') }}</p>
            <div class="legal-body mt-6 text-gray-700 leading-relaxed space-y-3">
                {!! \App\Support\CommunityText::safeHtml($page->body) !!}
            </div>
        </div>
    </div>
</div>

<style>
    .legal-body h3, .legal-body h4 { font-family:var(--font-heading); font-weight:700; color:var(--color-gray-900); margin:1.4rem 0 .5rem; }
    .legal-body p { margin:0 0 .9rem; }
    .legal-body ul, .legal-body ol { margin:0 0 .9rem 1.3rem; list-style:disc; }
    .legal-body a { color:var(--color-brand-600); font-weight:600; }
</style>
@endsection
