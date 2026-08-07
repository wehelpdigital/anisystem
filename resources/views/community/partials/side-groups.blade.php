{{-- Right rail: recent discussions (groups). Expects: $groups (with
     member_count). --}}
@php use App\Support\CommunityAvatar; @endphp
<div class="card p-3 mb-3">
    <div class="flex items-center justify-between mb-1">
        <h3 class="text-sm font-bold text-gray-900" style="font-family:var(--font-heading)">Recent Discussions</h3>
        <a href="{{ route('community.groups.index') }}" class="text-xs font-semibold text-brand-700 hover:text-brand-800">See all</a>
    </div>
    @forelse ($groups as $g)
        <a href="{{ route('community.groups.show', ['id' => $g->id]) }}" class="flex items-center gap-2 py-2 border-t border-gray-50 first:border-t-0 hover:bg-gray-50 rounded-lg -mx-1 px-1 transition">
            <span class="avatar avatar-sm avatar-sq overflow-hidden {{ CommunityAvatar::hue($g->name) }}">@if ($g->coverImagePath)<img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($g->coverImagePath) }}" alt="" class="w-full h-full object-cover">@else{{ CommunityAvatar::monogram($g->name) }}@endif</span>
            <span class="min-w-0 grow">
                <span class="block text-sm font-semibold text-gray-900 truncate">{{ $g->name }}</span>
                <span class="block text-[11px] text-gray-500">🧑‍🌾 {{ $g->member_count }} {{ \Illuminate\Support\Str::plural('member', $g->member_count) }}</span>
            </span>
        </a>
    @empty
        <p class="text-xs text-gray-400 py-2">Wala pang usapan.</p>
    @endforelse
</div>
