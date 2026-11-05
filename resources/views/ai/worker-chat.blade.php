{{-- One worker's chat with Anee, to read and not to continue. See WorkerChatController. --}}
@extends('layouts.app')

@section('title', $chat->title ?: 'Worker\'s chat')
@section('page-title', 'Worker\'s chat')
@section('page-subtitle', \Illuminate\Support\Str::limit($chat->title ?: 'New question', 40))
@section('back', route('ai.worker-chats'))

@section('content')
@include('ai.partials.worker-chat-css')
<div class="wc-wrap">
    <p class="wc-say">By {{ \App\Support\FarmShelf::by($chat->userId) ?: 'you' }}@if ($season) · {{ $season }}@endif. To read only.</p>
    @foreach ($messages as $m)
        @php
            $shots = array_values(array_filter((array) ($m->imagePaths ?: ($m->imagePath ? [$m->imagePath] : []))));
        @endphp
        <div class="wc-turn {{ $m->role === 'user' ? 'is-them' : '' }}">
            <span class="wc-face">
                @if ($m->role === 'user')
                    {{ optional($chat->user)->initials ?: '?' }}
                @else
                    <img src="{{ $settings->faceUrl() }}" alt="">
                @endif
            </span>
            <div class="wc-bubble">
                {!! \App\Support\AiMarkdown::toHtml($m->content) !!}
                @foreach ($shots as $shot)
                    <img class="wc-shot" src="{{ \App\Support\MediaStore::url($shot) }}" alt="" loading="lazy" onerror="this.remove()">
                @endforeach
                <span class="wc-when">{{ $m->created_at?->timezone('Asia/Manila')->format('M j, g:i A') }}</span>
            </div>
        </div>
    @endforeach
    @if ($messages->isEmpty())
        <p class="wc-none">Nothing was asked in this chat.</p>
    @endif
</div>
@endsection
