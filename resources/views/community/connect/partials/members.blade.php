{{-- A run of member cards. Reused by the directory page and "load more".
     Expects: $members (collection, each with connStatus + profile fields). --}}
@foreach ($members as $m)
    <div class="card card-hover p-3.5 flex flex-col" data-member-card="{{ $m->id }}">
        <div class="flex items-start gap-3">
            <a href="{{ route('community.connect.profile', ['userId' => $m->id]) }}" class="shrink-0">
                @include('community.partials.avatar-status', ['user' => $m, 'size' => 'avatar-md', 'link' => false])
            </a>
            <div class="min-w-0 grow">
                <a href="{{ route('community.connect.profile', ['userId' => $m->id]) }}" class="block font-semibold text-gray-900 truncate leading-tight hover:text-brand-700">{{ $m->full_name }}</a>
                @if (filled($m->headline))
                    <p class="text-xs text-gray-600 font-medium truncate mt-0.5">{{ $m->headline }}</p>
                @endif
                @if (filled($m->location))
                    <span class="flex items-center gap-1 text-xs text-gray-500 mt-0.5 min-w-0">
                        <svg class="w-3 h-3 shrink-0 text-gray-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M9.69 18.933C9.89 19.02 10 19 10 19s.11.02.31-.067l.005-.002.018-.008a5.74 5.74 0 00.281-.14 13.73 13.73 0 002.288-1.582C15.02 14.828 17 12.353 17 9A7 7 0 103 9c0 3.353 1.98 5.828 4.098 7.201a13.73 13.73 0 002.29 1.582 5.74 5.74 0 00.28.14l.019.008.005.002zM10 11.25a2.25 2.25 0 100-4.5 2.25 2.25 0 000 4.5z" clip-rule="evenodd"/></svg>
                        <span class="truncate">{{ $m->location }}</span>
                    </span>
                @endif
            </div>
            <div class="shrink-0">
                @include('community.connect.partials.action', ['status' => $m->connStatus, 'memberId' => $m->id])
            </div>
        </div>

        @if (filled($m->bio))
            <p class="text-xs text-gray-600 mt-2 line-clamp-2">{{ $m->bio }}</p>
        @endif

        @include('community.partials.farm-chips', ['user' => $m])
    </div>
@endforeach
