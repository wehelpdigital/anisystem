{{-- Rail: your discussions, freshest talk first. Expects: $groups (with
     member_count, optional last_post_at) and $mine — true when these are
     groups the reader belongs to, false when we are showing what is out
     there because they have joined none yet. --}}
@php use App\Support\CommunityAvatar; @endphp
@php $mine = $mine ?? true; @endphp
<div class="card p-3 mb-3">
    <div class="flex items-center justify-between mb-1">
        <h3 class="text-sm font-bold text-gray-900" style="font-family:var(--font-heading)">💬 {{ $mine ? 'Your Discussions' : 'Discussions to Join' }}</h3>
        <a href="{{ route('community.groups.index') }}" class="text-xs font-semibold text-brand-700 hover:text-brand-800">See all</a>
    </div>
    @forelse ($groups as $g)
        <a href="{{ route('community.groups.show', ['id' => $g->id]) }}" class="rail-row">
            {{-- data-gz-*: a tapped photo opens the photo viewer with the
                 room's facts instead of following the row's link. --}}
            <span class="avatar avatar-md avatar-sq overflow-hidden {{ CommunityAvatar::hue($g->name) }}"
                @if ($g->coverImagePath)
                    data-gz-name="{{ $g->name }}"
                    data-gz-members="{{ $g->member_count }}"
                    data-gz-url="{{ route('community.groups.show', ['id' => $g->id]) }}"
                @endif>@if ($g->coverImagePath)<img src="{{ \App\Support\MediaStore::url($g->coverImagePath) }}" alt="" class="w-full h-full object-cover">@else{{ CommunityAvatar::monogram($g->name) }}@endif</span>
            <span class="min-w-0 grow">
                <span class="block text-sm font-semibold text-gray-900 truncate">{{ $g->name }}</span>
                {{-- What a member actually wants from this row: is there
                     anything new in there. Member count is the answer only
                     when nobody has posted yet. --}}
                <span class="rail-meta">
                    @if (filled($g->last_post_at ?? null))
                        🗨️ {{ \Illuminate\Support\Carbon::parse($g->last_post_at)->diffForHumans(null, true) }} ago
                    @else
                        🧑‍🌾 {{ $g->member_count }} {{ \Illuminate\Support\Str::plural('member', $g->member_count) }}
                    @endif
                </span>
            </span>
        </a>
    @empty
        <p class="text-xs text-gray-400 py-2">Wala pang usapan.</p>
    @endforelse
    @if (! $mine && $groups->isNotEmpty())
        <p class="text-[0.688rem] text-gray-400 mt-1.5 px-0.5">Wala ka pang sinasalihan — pumili ng isa.</p>
    @endif
</div>
