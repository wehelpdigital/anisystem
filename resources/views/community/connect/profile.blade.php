@extends('layouts.app')

@section('title', $member->full_name . ' — Community')
@section('page-title', $member->full_name)
@section('page-subtitle', 'Member profile')
@section('back', route('community.connect.members'))

@section('content')
<div class="max-w-2xl mx-auto">

    {{-- Profile header --}}
    <div class="card p-5 mb-4">
        <div class="flex items-start gap-4">
            <span class="w-16 h-16 rounded-full bg-brand-600 text-white text-xl font-bold flex items-center justify-center shrink-0">{{ $member->initials ?: '?' }}</span>
            <div class="min-w-0 grow">
                <h2 class="text-xl font-bold text-gray-900 leading-tight">{{ $member->full_name }}</h2>
                @if (filled($member->location))
                    <p class="text-sm text-gray-500 mt-0.5 flex items-center gap-1">
                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a2 2 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        {{ $member->location }}
                    </p>
                @endif
                <div class="flex items-center gap-4 text-xs text-gray-500 font-medium mt-2">
                    <span>{{ $connectionCount }} {{ \Illuminate\Support\Str::plural('connection', $connectionCount) }}</span>
                    <span>{{ $plans->count() }} shared {{ \Illuminate\Support\Str::plural('plan', $plans->count()) }}</span>
                </div>
            </div>
            <div class="shrink-0">
                @if ($isSelf)
                    <a href="{{ route('account.index') }}" class="btn btn-white btn-sm">Edit profile</a>
                @else
                    @include('community.connect.partials.action', ['status' => $status, 'memberId' => $member->id])
                @endif
            </div>
        </div>

        @if (filled($member->bio))
            <p class="text-sm text-gray-700 mt-4 whitespace-pre-line">{{ $member->bio }}</p>
        @endif
    </div>

    {{-- Shared plans --}}
    @if ($plans->isNotEmpty())
        <div class="card p-4 mb-4">
            <h3 class="font-bold text-gray-900 mb-2">Shared plans</h3>
            <div class="space-y-2">
                @foreach ($plans as $plan)
                    <a href="{{ route('community.show', ['id' => $plan->id]) }}" class="flex items-center justify-between gap-2 p-2.5 rounded-lg hover:bg-gray-50 transition">
                        <span class="min-w-0">
                            <span class="block font-semibold text-gray-900 text-sm truncate">{{ $plan->title }}</span>
                            @if ($plan->cropType)<span class="block text-xs text-gray-500">{{ $plan->cropType }}@if($plan->publicRegion) · {{ $plan->publicRegion }}@endif</span>@endif
                        </span>
                        <svg class="w-4 h-4 text-gray-300 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                    </a>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Wall --}}
    @include('community.connect.partials.wall', ['member' => $member, 'isSelf' => $isSelf])
</div>
@endsection

@include('community.connect.partials.connect-js')
