@extends('layouts.app')

@section('title', 'Pay via ' . \App\Support\Region::payMethod())
@section('page-title', 'Pay via ' . \App\Support\Region::payMethod())
@section('page-subtitle', $pack->packName.' · '.\App\Support\Region::money(\App\Support\Region::packPrice($pack)))
@section('back', route('ai.credits'))

@section('content')
@include('purchase.partials.gcash-checkout', [
    'gcash' => $gcash,
    'price' => \App\Support\Region::packPrice($pack),
    'summaryLabel' => $pack->packName.' AI Credits',
    'summaryMeta' => number_format($pack->credits).' credits',
    'submitUrl' => route('ai.credits.submit', $pack->packKey),
    'user' => auth()->user(),
])
@endsection

@push('scripts')
    @include('purchase.partials.gcash-checkout-js')
@endpush
