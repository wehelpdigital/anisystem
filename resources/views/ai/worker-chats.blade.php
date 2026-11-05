{{-- The chats workers had with Anee from their worker accounts. See WorkerChatController. --}}
@extends('layouts.app')

@section('title', 'Workers\' chats')
@section('page-title', 'Workers\' chats')
@section('page-subtitle', 'With ' . \App\Models\AiSetting::current()->assistantName)
@section('back', route('ai.index'))

@section('content')
@include('ai.partials.worker-chat-css')
<div class="wc-wrap">
    <p class="wc-say">What your workers asked from their worker accounts. You can read these. Only they can continue them.</p>
    @forelse ($chats as $c)
        <a class="wc-row" href="{{ route('ai.worker-chats.show', ['id' => $c->id]) }}" data-nav-loader>
            <span class="wc-face">{{ optional($c->user)->initials ?: '?' }}</span>
            <span class="wc-text">
                <b>{{ $c->title ?: 'New question' }}</b>
                <i>{{ \App\Support\FarmShelf::dated($c->userId, (string) $c->updated_at?->diffForHumans()) }}@if ($c->schedule) · {{ $c->schedule->title }}@endif</i>
            </span>
            <svg class="wc-go" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
        </a>
    @empty
        <p class="wc-none">No chats from your workers yet.</p>
    @endforelse
    @if ($chats->hasPages())
        <div class="wc-pages">{{ $chats->links() }}</div>
    @endif
</div>
@endsection
