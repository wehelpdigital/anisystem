@extends('layouts.app')

@section('title', 'Support')
@section('page-title', 'Support')
@section('page-subtitle', 'Get help from our team')

@section('content')
<div class="flex items-center justify-between gap-3 mb-4">
    <h2 class="text-lg font-bold text-gray-900">Your tickets</h2>
    <button type="button" class="btn btn-primary btn-sm" onclick="document.getElementById('newTicketCard').classList.toggle('hidden')">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
        New ticket
    </button>
</div>

<div class="card p-4 mb-4 {{ $errors->any() ? '' : 'hidden' }}" id="newTicketCard">
    <form method="POST" action="{{ route('support.store') }}" class="space-y-3">
        @csrf
        <div>
            <label class="form-label" for="subject">Subject <span class="text-red-500">*</span></label>
            <input type="text" id="subject" name="subject" class="form-input" maxlength="200" value="{{ old('subject') }}" placeholder="Short summary of your issue">
            @error('subject') <p class="form-error">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="form-label" for="category">Category</label>
            <select id="category" name="category" class="form-select">
                @foreach (\App\Models\SupportTicket::CATEGORIES as $key => $label)
                    <option value="{{ $key }}" @selected(old('category') === $key)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="form-label" for="body">Describe the issue <span class="text-red-500">*</span></label>
            <textarea id="body" name="body" rows="4" class="form-textarea" maxlength="8000" placeholder="Tell us what's happening — steps, error messages, screenshots links…">{{ old('body') }}</textarea>
            @error('body') <p class="form-error">{{ $message }}</p> @enderror
        </div>
        <div class="flex justify-end">
            <button type="submit" class="btn btn-primary">Submit ticket</button>
        </div>
    </form>
</div>

@forelse ($tickets as $ticket)
    <a href="{{ route('support.show', ['id' => $ticket->id]) }}" class="card card-hover p-4 mb-2 flex items-center gap-3">
        <div class="min-w-0 grow">
            <p class="font-semibold text-gray-900 truncate">{{ $ticket->subject }}</p>
            <p class="text-xs text-gray-500 mt-0.5">
                {{ \App\Models\SupportTicket::CATEGORIES[$ticket->category] ?? 'General' }}
                · {{ $ticket->messages_count }} {{ \Illuminate\Support\Str::plural('message', $ticket->messages_count) }}
                · updated {{ $ticket->updated_at?->diffForHumans() }}
            </p>
        </div>
        @php $statusStyle = ['open' => 'badge-gray', 'answered' => 'badge-green', 'closed' => 'badge-gray'][$ticket->status] ?? 'badge-gray'; @endphp
        <span class="badge {{ $statusStyle }} shrink-0">{{ ucfirst($ticket->status) }}</span>
    </a>
@empty
    <div class="card p-8 text-center">
        <div class="text-4xl mb-2">🎫</div>
        <p class="font-bold text-gray-900">No tickets yet</p>
        <p class="text-sm text-gray-500 mt-1">Have an issue or a question? Tap <strong>New ticket</strong> and our team will help.</p>
    </div>
@endforelse
@endsection
