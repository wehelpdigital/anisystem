@extends('layouts.app')

@section('title', 'Pay via ' . \App\Support\Region::payMethod())
@section('page-title', 'Pay via ' . \App\Support\Region::payMethod())
@section('page-subtitle', $plan->planName.' · '.\App\Support\Region::money(\App\Support\Region::planPrice($plan)))
@section('back', route('purchase.plans'))

@section('content')
@include('purchase.partials.gcash-checkout', [
    'gcash' => $gcash,
    'price' => \App\Support\Region::planPrice($plan),
    'summaryLabel' => $plan->planName,
    'summaryMeta' => $plan->duration_label,
    'submitUrl' => route('purchase.submit', $plan->planKey),
    'user' => $user,
])
@endsection

@push('scripts')
    @include('purchase.partials.gcash-checkout-js')
@endpush
