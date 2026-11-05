@extends('layouts.app')
{{-- What a locked member sees at any Anee Auto address (App\Support\AutoLock). --}}
@section('title', 'Anee Auto')
@section('page-title', 'Anee Auto')
@section('back', route('sm.index'))

@section('content')
    <div class="card max-w-xl mx-auto mt-4">
        <div class="card-body text-center">
            <img src="{{ \App\Models\AiSetting::current()->faceUrl() }}" alt="" class="w-20 h-20 mx-auto mb-3 object-contain">
            <h2 class="text-lg font-bold text-gray-900">{{ \App\Support\AutoLock::TITLE }}</h2>
            <p class="text-sm text-gray-600 mt-2">Anee Auto is the season Anee plans for you and keeps planning every morning. It is not open yet, and it opens here as soon as it is ready. Everything else in anee.io is ready to use.</p>
            <a href="{{ route('sm.index') }}" class="btn btn-primary mt-4">Go to my cropping schedules</a>
        </div>
    </div>
@endsection
