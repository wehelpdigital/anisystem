@extends('layouts.app')

@section('title', 'AI Credits')
@section('page-title', 'AI Credits')
@section('page-subtitle', 'Fuel for the AI Technician')
@section('back', route('ai.index'))

@section('content')

{{-- Balance --}}
<div class="card overflow-hidden mb-4">
    <div class="card-body bg-gradient-to-br from-brand-600 to-brand-800 !rounded-2xl text-white">
        <p class="text-sm text-brand-100">Your balance</p>
        <p class="text-3xl font-bold mt-0.5">{{ rtrim(rtrim(number_format($balance, 2), '0'), '.') }} <span class="text-lg font-semibold">credits</span></p>
        <p class="text-sm text-brand-100 mt-2">
            A text question costs about {{ (int) ceil($settings->creditsPerInputK + $settings->creditsPerOutputK * 0.6) }} credits;
            adding a photo costs about {{ (int) ceil($settings->creditsPerImage) }} more.
        </p>
    </div>
</div>

@if ($pending)
    <div class="card p-4 mb-4 border-l-4 border-accent-500">
        <p class="font-bold text-gray-900">Order {{ $pending->orderNumber }} is awaiting verification</p>
        <p class="text-sm text-gray-500 mt-1">
            {{ $pending->credits }} credits ({{ $pending->packName }}) will be added once your GCash payment is confirmed.
            This is usually within a few hours.
        </p>
    </div>
@endif

{{-- Packs --}}
<h3 class="font-bold text-gray-900 mb-2">Top up</h3>
@php $best = $packs->sortBy('per_credit')->first(); @endphp
<div class="grid gap-3 sm:grid-cols-3 mb-6">
    @foreach ($packs as $pack)
        <div class="card p-4 flex flex-col {{ $best && $best->id === $pack->id ? 'ring-2 ring-brand-500' : '' }}">
            @if ($best && $best->id === $pack->id)
                <span class="badge badge-green self-start mb-2">Best value</span>
            @endif
            <h4 class="font-bold text-gray-900">{{ $pack->packName }}</h4>
            <p class="text-2xl font-bold text-brand-700 mt-1">₱ {{ number_format((float) $pack->price, 2) }}</p>
            <p class="text-sm font-semibold text-gray-700 mt-0.5">{{ number_format($pack->credits) }} credits</p>
            <p class="text-xs text-gray-500 mt-0.5">₱ {{ number_format($pack->per_credit, 2) }} per credit</p>
            @if ($pack->description)
                <p class="text-sm text-gray-500 mt-2 grow">{{ $pack->description }}</p>
            @endif
            <a href="{{ route('ai.credits.payment', $pack->packKey) }}"
               class="btn {{ $best && $best->id === $pack->id ? 'btn-primary' : 'btn-outline' }} mt-4">Buy</a>
        </div>
    @endforeach
</div>

{{-- Ledger --}}
<h3 class="font-bold text-gray-900 mb-2">Recent activity</h3>
@if ($history->isEmpty())
    <div class="card p-8 text-center">
        <p class="font-semibold text-gray-700">Nothing yet</p>
        <p class="text-sm text-gray-500 mt-1">Credits you buy or spend will be listed here.</p>
    </div>
@else
    <div class="card divide-y divide-gray-100">
        @foreach ($history as $row)
            <div class="flex items-start justify-between gap-3 p-3.5">
                <div class="min-w-0">
                    <p class="text-sm font-semibold text-gray-900 truncate">{{ $row->reason }}</p>
                    <p class="text-xs text-gray-400 mt-0.5">{{ $row->created_at?->diffForHumans() }}</p>
                </div>
                <div class="shrink-0 text-right">
                    <p class="text-sm font-bold {{ (float) $row->delta >= 0 ? 'text-brand-700' : 'text-gray-700' }}">
                        {{ (float) $row->delta >= 0 ? '+' : '' }}{{ rtrim(rtrim(number_format((float) $row->delta, 2), '0'), '.') }}
                    </p>
                    <p class="text-xs text-gray-400">{{ rtrim(rtrim(number_format((float) $row->balanceAfter, 2), '0'), '.') }} left</p>
                </div>
            </div>
        @endforeach
    </div>
@endif
@endsection
