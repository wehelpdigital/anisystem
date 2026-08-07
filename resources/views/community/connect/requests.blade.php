@extends('layouts.app')

@section('title', 'Requests — Community')
@section('page-title', 'Community')
@section('page-subtitle', 'Members who want to connect')
@section('back', route('community.connect.members'))

@section('content')
@include('community.partials.nav', ['active' => 'members'])

@if ($rows->isEmpty())
    <div class="card p-8 text-center text-sm text-gray-500">No pending requests.</div>
@else
    <div class="space-y-2">
        @foreach ($rows as $row)
            @php $u = $requesters->get($row->userId); @endphp
            @if ($u)
                <div class="card flex items-center gap-3 p-3.5" data-member-card="{{ $u->id }}">
                    <a href="{{ route('community.connect.profile', ['userId' => $u->id]) }}" class="flex items-center gap-3 min-w-0 grow">
                        <span class="w-11 h-11 rounded-full bg-brand-600 text-white text-sm font-bold flex items-center justify-center shrink-0">{{ $u->initials ?: '?' }}</span>
                        <span class="min-w-0">
                            <span class="block font-semibold text-gray-900 truncate">{{ $u->full_name }}</span>
                            @if (filled($u->location))<span class="block text-xs text-gray-500 truncate">{{ $u->location }}</span>@endif
                        </span>
                    </a>
                    @include('community.connect.partials.action', ['status' => 'pending_in', 'memberId' => $u->id])
                </div>
            @endif
        @endforeach
    </div>
@endif
@endsection

@include('community.connect.partials.connect-js')
