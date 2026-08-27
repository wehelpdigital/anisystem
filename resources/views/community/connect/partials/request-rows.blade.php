{{-- One page of pending co-farmer requests: face, name, where they farm
     (the pin in red), and the two answers — Accept breathing its ripple.
     Expects: $rows (paginator page), $requesters (users keyed by id). --}}
@foreach ($rows as $row)
    @php $u = $requesters->get($row->userId); @endphp
    @if ($u)
        <div class="card flex items-center gap-3 p-3.5 rq-card" data-member-card="{{ $u->id }}">
            <a href="{{ route('community.connect.profile', ['userId' => $u->id]) }}" class="flex items-center gap-3 min-w-0 grow">
                @include('community.partials.avatar', ['user' => $u, 'size' => 'avatar-md', 'link' => false])
                <span class="min-w-0">
                    <span class="block font-semibold text-gray-900 truncate">{{ $u->full_name }}</span>
                    @include('community.partials.top-badge', ['topUser' => $u, 'topFlat' => true])
                    @if (filled($u->location))
                        <span class="rq-loc">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a2 2 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            {{ $u->location }}
                        </span>
                    @endif
                </span>
            </a>
            @include('community.connect.partials.action', ['status' => 'pending_in', 'memberId' => $u->id])
        </div>
    @endif
@endforeach
