@extends('layouts.app')

@section('title', 'Pay via GCash')
@section('page-title', 'Pay via GCash')
@section('page-subtitle', $plan->planName.' · ₱ '.number_format((float) $plan->price, 2))
@section('back', route('purchase.plans'))

@section('content')
@include('purchase.partials.gcash-checkout', [
    'gcash' => $gcash,
    'price' => (float) $plan->price,
    'summaryLabel' => $plan->planName,
    'summaryMeta' => $plan->duration_label,
    'submitUrl' => route('purchase.submit', $plan->planKey),
    'user' => $user,
])
@endsection

@push('scripts')
    @include('purchase.partials.gcash-checkout-js')
@endpush
