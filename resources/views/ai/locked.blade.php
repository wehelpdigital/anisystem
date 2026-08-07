@extends('layouts.app')

@section('title', 'AI Technician')
@section('page-title', 'AI Technician')
@section('back', route('app.dashboard'))

@section('content')
<div class="max-w-lg mx-auto">
    <div class="card p-8 text-center">
        <div class="mx-auto w-16 h-16 rounded-2xl bg-brand-50 flex items-center justify-center mb-4 text-3xl">🤖</div>
        <h2 class="text-xl font-bold text-gray-900" style="font-family:var(--font-heading)">AI Technician is a Boss feature</h2>
        <p class="text-sm text-gray-500 mt-2">
            Your <strong class="capitalize">{{ $tier === 'none' ? 'current' : $tier }}</strong> plan doesn't include the AI Technician.
            Upgrade to <strong>Boss</strong> or <strong>Lifetime</strong> to ask the AI about your crops and buy AI credits.
        </p>
        <div class="mt-6 flex flex-col sm:flex-row gap-2 justify-center">
            <a href="{{ route('account.subscription') }}" class="btn btn-primary">See plans</a>
            <a href="{{ route('app.dashboard') }}" class="btn btn-white">Back to dashboard</a>
        </div>
    </div>
</div>
@endsection
