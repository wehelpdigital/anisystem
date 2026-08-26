@extends('layouts.app')

@section('title', 'Requests — Community')
@section('body-class', 'plaza-ground')
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

@if ($rows->total() === 0)
    <div class="card p-8 text-center text-sm text-gray-500">No pending requests.</div>
@else
    <div class="space-y-2" id="requestsList">
        @include('community.connect.partials.request-rows', ['rows' => $rows, 'requesters' => $requesters])
    </div>
    @include('partials.list-pager', ['noun' => 'request', 'paginator' => $rows,
        'rowsUrl' => route('community.connect.requests') . '?rows=1'])
@endif
@endsection

@push('styles')
<style>
    .rq-loc { display:flex; align-items:center; gap:.25rem; font-size:.72rem; color:var(--color-gray-500);
        margin-top:.15rem; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    .rq-loc svg { width:.8rem; height:.8rem; flex:none; color:#e11d48; }
    /* The two answers, each breathing its own ripple — Accept in the brand
       green, Decline a quiet grey, out of step so they read as two asks. */
    .rq-card [data-action="accept"] { animation:rqRippleGo 2.2s cubic-bezier(.22,1,.36,1) infinite; }
    .rq-card [data-action="decline"] { animation:rqRippleNo 2.2s cubic-bezier(.22,1,.36,1) 1.1s infinite; }
    @keyframes rqRippleGo {
        0% { box-shadow:0 0 0 0 rgb(74 124 42 / .45); }
        60% { box-shadow:0 0 0 .55rem rgb(74 124 42 / 0); }
        100% { box-shadow:0 0 0 0 rgb(74 124 42 / 0); } }
    @keyframes rqRippleNo {
        0% { box-shadow:0 0 0 0 rgb(107 114 128 / .3); }
        60% { box-shadow:0 0 0 .45rem rgb(107 114 128 / 0); }
        100% { box-shadow:0 0 0 0 rgb(107 114 128 / 0); } }
    @media (prefers-reduced-motion: reduce) {
        .rq-card [data-action="accept"], .rq-card [data-action="decline"] { animation:none; }
    }
</style>
@endpush

@include('community.connect.partials.connect-js')
