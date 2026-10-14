{{-- A Community page reached from inside somebody's farm (2026-10-15): by a
     typed address, a bookmark or a link opened in a new tab, where the
     notice's link catcher never saw the tap. The notice opens over this page
     on load; the card under it says the same, for when it is closed.

     Expects: $next (the Community address that was asked for). --}}
@extends('layouts.app')

@section('title', 'Community')
@section('page-title', 'Community')

@php
    $csGrant = \App\Support\WorkerContext::activeGrant();
    $csBoss = trim((string) optional(optional($csGrant)->boss)->full_name);
    $csFarm = $csBoss !== '' ? $csBoss . '\'s farm' : 'this farm';
@endphp

@section('content')
<div class="cs-card">
    <span class="cs-badge" aria-hidden="true">
        <svg class="w-8 h-8" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-1a4 4 0 00-4-4h-1M9 11a4 4 0 100-8 4 4 0 000 8zm8 0a3 3 0 100-6M2 20v-1a5 5 0 015-5h4a5 5 0 015 5v1H2z"/></svg>
    </span>
    <h2 class="cs-title">The Community is in your own account</h2>
    <p class="cs-sub">You are working in {{ $csFarm }} right now. Switch to your own account to open the Community, with your own feed, cofarmers and name.</p>
    <form method="POST" action="{{ route('worker.switch') }}" class="mt-5">
        @csrf
        <input type="hidden" name="bossId" value="0">
        <input type="hidden" name="next" value="{{ $next }}">
        <button type="submit" class="btn btn-primary">Switch to my account</button>
    </form>
    <a href="{{ route('app.dashboard') }}" class="cs-stay" data-nav-loader>Stay in {{ $csFarm }}</a>
</div>
@endsection

@push('styles')
<style>
    .cs-card { max-width: 28rem; margin: 0 auto; padding: 2.5rem 1rem; text-align: center; }
    .cs-badge { display: inline-flex; align-items: center; justify-content: center; width: 4.5rem; height: 4.5rem; border-radius: 999px; color: #3d6823; background: #eef6e4; }
    .cs-title { margin-top: 1rem; font-family: var(--font-heading); font-size: 1.15rem; font-weight: 700; line-height: 1.35; color: var(--color-gray-900); }
    .cs-sub { margin-top: .5rem; font-size: .92rem; line-height: 1.6; color: var(--color-gray-500); }
    .cs-stay { display: inline-block; margin-top: .9rem; font-size: .85rem; font-weight: 700; color: var(--color-gray-500); text-decoration: underline; text-underline-offset: 3px; }
    html.dark .cs-badge { background: rgb(255 255 255 / .07); color: #a5c97e; }
</style>
@endpush

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => window.aneeWorkerCommunity?.(@json($next)));
</script>
@endpush
