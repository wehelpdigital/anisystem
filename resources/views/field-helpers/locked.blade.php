@extends('layouts.app')
{{-- A field helper opened on a farm without the field helpers pack
     (2026-10-14): the offer, in place of the tool. App\Http\Middleware\FieldPackGate. --}}
@section('title', $fhName ?? 'Field helpers')
@section('page-title', $fhName ?? 'Field helpers')
@section('back', \App\Support\BackTo::url(route('app.dashboard')))
@section('body-class', 'hide-tabbar no-footer')

@section('content')
<div class="max-w-md mx-auto px-4 pt-4 pb-10">
    @include('partials.field-pack-offer', ['mode' => 'page'])
</div>
@endsection
