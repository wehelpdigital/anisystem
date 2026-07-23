@extends('layouts.app')

@section('title', 'Pay via GCash')
@section('page-title', 'Pay via GCash')
@section('page-subtitle', $pack->packName.' · ₱ '.number_format((float) $pack->price, 2))
@section('back', route('ai.credits'))

@section('content')
@include('purchase.partials.gcash-checkout', [
    'gcash' => $gcash,
    'price' => (float) $pack->price,
    'summaryLabel' => $pack->packName.' AI Credits',
    'summaryMeta' => number_format($pack->credits).' credits',
    'submitUrl' => route('ai.credits.submit', $pack->packKey),
    'user' => auth()->user(),
])
@endsection

@push('scripts')
    @include('purchase.partials.gcash-checkout-js')
@endpush
