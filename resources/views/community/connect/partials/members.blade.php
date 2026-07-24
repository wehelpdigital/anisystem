{{-- A run of member cards. Reused by the directory page and "load more".
     Expects: $members (collection, each with connStatus). --}}
@foreach ($members as $m)
    <div class="card card-hover flex items-center gap-3 p-3.5" data-member-card="{{ $m->id }}">
        <a href="{{ route('community.connect.profile', ['userId' => $m->id]) }}" class="flex items-center gap-3 min-w-0 grow">
            <span class="w-11 h-11 rounded-full bg-brand-600 text-white text-sm font-bold flex items-center justify-center shrink-0">{{ $m->initials ?: '?' }}</span>
            <span class="min-w-0">
                <span class="block font-semibold text-gray-900 truncate">{{ $m->full_name }}</span>
                @if (filled($m->location))
                    <span class="block text-xs text-gray-500 truncate">{{ $m->location }}</span>
                @endif
            </span>
        </a>
        @include('community.connect.partials.action', ['status' => $m->connStatus, 'memberId' => $m->id])
    </div>
@endforeach
