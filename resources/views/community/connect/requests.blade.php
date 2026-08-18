@extends('layouts.app')

@section('title', 'Requests — Community')
@section('page-title', 'Community')
@section('page-subtitle', 'Members who want to connect')
@section('back', route('community.connect.members'))

{{-- The shared plaza styles were missing here, so this page's avatars were
     hand-rolled brand circles instead of the one community identity. --}}
@push('head')
@include('community.partials.plaza-css')
@endpush

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
                        @include('community.partials.avatar', ['user' => $u, 'size' => 'avatar-md', 'link' => false])
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
