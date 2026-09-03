@extends('layouts.app')

@section('title', 'Ticket — Support')
@section('page-title', 'Support')
@section('page-subtitle', $ticket->subject)
@section('back', route('support.index'))

@section('content')
<div class="card p-4 mb-4">
    <div class="flex items-start justify-between gap-3">
        <div class="min-w-0">
            <h2 class="text-lg font-bold text-gray-900 leading-snug">{{ $ticket->subject }}</h2>
            <p class="text-xs text-gray-500 mt-1">
                @if ($ticket->ticketNumber)
                    <span class="font-mono font-bold text-brand-700">{{ $ticket->ticketNumber }}</span> ·
                @endif
                {{ \App\Models\SupportTicket::CATEGORIES[$ticket->category] ?? 'General' }} · opened {{ $ticket->created_at?->diffForHumans() }}
            </p>
        </div>
        @php $statusStyle = ['open' => 'badge-gray', 'answered' => 'badge-green', 'closed' => 'badge-gray'][$ticket->status] ?? 'badge-gray'; @endphp
        <span class="badge {{ $statusStyle }} shrink-0">{{ ucfirst($ticket->status) }}</span>
    </div>
</div>

<div class="space-y-3 mb-4">
    @foreach ($messages as $m)
        @php $isAdmin = $m->authorType === 'admin'; @endphp
        <div class="flex {{ $isAdmin ? 'justify-start' : 'justify-end' }}">
            <div class="max-w-[85%] {{ $isAdmin ? 'bg-brand-50 border border-brand-100' : 'bg-white border border-gray-100' }} rounded-2xl px-4 py-3">
                <p class="text-xs font-bold {{ $isAdmin ? 'text-brand-700' : 'text-gray-500' }} mb-1">
                    {{ $isAdmin ? '🛟 ' . ($m->authorName ?: 'Support team') : ($m->authorName ?: 'You') }}
                    <span class="font-normal text-gray-400">· {{ $m->created_at?->diffForHumans() }}</span>
                </p>
                @if (($m->bodyFormat ?? 'text') === 'html')
                    {{-- An admin reply composed rich: purified at write, so
                         rendering it raw here shows formatting, not risk. --}}
                    <div class="text-sm text-gray-800 break-words rich-text sup-rich">{!! $m->body !!}</div>
                @else
                    <p class="text-sm text-gray-800 whitespace-pre-line break-words">{{ $m->body }}</p>
                @endif
            </div>
        </div>
    @endforeach
</div>

@if ($ticket->status !== 'closed')
    <form method="POST" action="{{ route('support.reply', ['id' => $ticket->id]) }}" class="card p-3">
        @csrf
        <textarea name="body" rows="3" class="form-textarea" maxlength="8000" placeholder="Add a reply…" required></textarea>
        @error('body') <p class="form-error">{{ $message }}</p> @enderror
        <div class="flex justify-end mt-2">
            <button type="submit" class="btn btn-primary btn-sm">Send reply</button>
        </div>
    </form>
@else
    <div class="card p-4 text-center text-sm text-gray-500">This ticket is closed. Open a new ticket if you need more help.</div>
@endif
@endsection
