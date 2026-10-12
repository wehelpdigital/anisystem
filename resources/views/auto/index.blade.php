@extends('layouts.app')
@section('title', 'Anee Auto')
@section('page-title', 'Anee Auto')
@section('page-subtitle', 'Anee plans, you follow')
@section('back', \App\Support\BackTo::url(route('sm.index')))

{{-- ANEE AUTO, THE SHELF (2026-10-12): what it is, the seasons it runs,
     and the door to a new one (auto.partials.start). --}}
@section('content')
@include('auto.partials.hero-css')
<div class="max-w-4xl mx-auto">
    @include('auto.partials.hero', ['cta' => true])

    <h3 class="font-extrabold text-gray-900 text-lg mt-6 mb-3">Your Anee Auto seasons</h3>
    @if ($seasons->isEmpty())
        <div class="rounded-2xl border border-dashed border-gray-300 p-6 text-center text-gray-500 text-sm">None yet. Start one and Anee plans your whole season, then keeps it on track every day.</div>
    @else
        <div class="grid gap-3 md:grid-cols-2">
            @foreach ($seasons as $i => $s)
                <a href="{{ route('auto.show', $s['id']) }}" class="aa-row" style="--i:{{ $i }}">
                    <span class="aa-row-e">{{ $s['status'] === 'setting_up' ? '🛠️' : ($s['status'] === 'finished' ? '🏁' : '🤖') }}</span>
                    <span class="min-w-0 flex-1">
                        <b>{{ $s['title'] }}</b>
                        <small>{{ $s['lots'] }} {{ \Illuminate\Support\Str::plural('lot', $s['lots']) }} ·
                            @if ($s['status'] === 'setting_up') Setting up: tap to continue
                            @elseif ($s['status'] === 'finished') Finished
                            @elseif ($s['next']) Next: {{ $s['next']->title }}, {{ $s['next']->plannedDate?->isToday() ? 'today' : $s['next']->plannedDate?->format('D, M j') }}
                            @else Running @endif</small>
                    </span>
                    <span class="aa-row-st is-{{ $s['status'] }}">{{ ['setting_up' => 'Setting up', 'active' => 'Running', 'finished' => 'Finished'][$s['status']] ?? $s['status'] }}</span>
                </a>
            @endforeach
        </div>
    @endif
</div>
@include('auto.partials.start')
@endsection
