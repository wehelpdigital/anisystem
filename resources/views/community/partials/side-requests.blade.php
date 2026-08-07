{{-- Left rail: incoming co-farmer requests. Expects: $requests (User
     collection), $requestCount (int total). Buttons reuse connect-js. --}}
<div class="card p-3 mb-3">
    <div class="flex items-center justify-between mb-1">
        <h3 class="text-sm font-bold text-gray-900" style="font-family:var(--font-heading)">Co-Farmer Requests</h3>
        @if ($requestCount > 0)<span class="badge badge-green">{{ $requestCount }}</span>@endif
    </div>
    @forelse ($requests as $u)
        <div class="py-2 border-t border-gray-50 first:border-t-0" data-member-card="{{ $u->id }}">
            <div class="flex items-center gap-2 min-w-0">
                @include('community.partials.avatar', ['user' => $u, 'size' => 'avatar-sm'])
                <div class="min-w-0 grow">
                    <a href="{{ route('community.connect.profile', ['userId' => $u->id]) }}" class="block text-sm font-semibold text-gray-900 truncate hover:text-brand-700">{{ $u->full_name }}</a>
                    @if (filled($u->location))<span class="block text-[11px] text-gray-500 truncate">{{ $u->location }}</span>@endif
                </div>
            </div>
            <div class="mt-1.5">
                @include('community.connect.partials.action', ['status' => 'pending_in', 'memberId' => $u->id])
            </div>
        </div>
    @empty
        <p class="text-xs text-gray-400 py-2">Walang bagong request.</p>
    @endforelse
    @if ($requestCount > $requests->count())
        <a href="{{ route('community.connect.requests') }}" class="block text-xs font-semibold text-brand-700 hover:text-brand-800 mt-2">See all {{ $requestCount }} requests →</a>
    @endif
</div>
