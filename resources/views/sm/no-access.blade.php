{{-- The honest answer when a worker reaches a module their grant does not
     cover — by typing the URL, by a stale bookmark, or by a link that outlived
     its permission. A 404 would have been a lie (the page exists), and letting
     it render would have been worse; this says so plainly and points at the
     way back rather than leaving a dead end.

     Expects: $what (the module's name, for the sentence), and optionally
     $backUrl / $backLabel. --}}
@extends('layouts.app')

@section('title', 'No access')
@section('page-title', 'No access')

@section('content')
<div class="max-w-md mx-auto text-center py-10">
    <span class="na-badge" aria-hidden="true">
        <svg class="w-8 h-8" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
        </svg>
    </span>
    <h2 class="na-title">Sorry — you do not have access to {{ $what ?? 'this module' }}.</h2>
    <p class="na-sub">
        Your worker account was given the rights it needs for the work assigned to you.
        If you need this, ask the farm owner to open it for you.
    </p>
    <a href="{{ $backUrl ?? route('app.dashboard') }}" class="btn btn-primary mt-5" data-nav-loader>
        {{ $backLabel ?? 'Back to my work' }}
    </a>
</div>
@endsection

@push('styles')
<style>
    .na-badge { display:inline-flex; align-items:center; justify-content:center;
        width:4.5rem; height:4.5rem; border-radius:999px; color:var(--color-gray-400);
        background:var(--color-gray-100); }
    .na-title { font-family:var(--font-heading); font-size:1.15rem; font-weight:700;
        color:var(--color-gray-900); margin-top:1rem; line-height:1.35; }
    .na-sub { font-size:.92rem; color:var(--color-gray-500); margin-top:.5rem; line-height:1.6; }
    html.dark .na-badge { background:rgb(255 255 255 / .07); color:#9fb08e; }
</style>
@endpush
