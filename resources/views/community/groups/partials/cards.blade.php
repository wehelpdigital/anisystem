{{-- One page of discussion cards. Drawn by the list on first paint and by the
     JSON page endpoint for every page after, so a card is described once and
     the two paths can never drift apart.

     Its own `use`: a compiled partial does not inherit the parent's imports.

     Expects: $groups (each carrying member_count, post_count and joined). --}}
@php use App\Support\CommunityAvatar; @endphp
@foreach ($groups as $g)
    @php $hue = CommunityAvatar::hue($g->name); @endphp
    {{-- Its own colour along the top and bottom, by id: the same room wears
         the same colour on every visit, and 0 keeps the house green. --}}
    <div class="card card-hover disc-card dc-hue-{{ $g->id % 6 }} flex flex-col" data-group-card="{{ $g->id }}"
         data-view="group:{{ $g->id }}">
        {{-- The room's cover, with its face standing on the edge of it. A
             coloured stripe said nothing about the room; this says where you
             are about to walk into. --}}
        <div class="dc-top">
            <div class="dc-cover {{ $hue }}">
                @if ($g->bannerImagePath)
                    <img src="{{ \App\Support\MediaStore::url($g->bannerImagePath) }}" alt="" loading="lazy">
                @endif
            </div>
            <a href="{{ route('community.groups.show', ['id' => $g->id]) }}" class="dc-face {{ $hue }}" aria-hidden="true" tabindex="-1">
                @if ($g->coverImagePath)
                    <img src="{{ \App\Support\MediaStore::url($g->coverImagePath) }}" alt="">
                @else
                    {{ CommunityAvatar::monogram($g->name) }}
                @endif
            </a>
        </div>
        <div class="card-body flex flex-col grow dc-body">
            <a href="{{ route('community.groups.show', ['id' => $g->id]) }}" class="min-w-0 block">
                <h3 class="font-bold text-gray-900 leading-snug" style="font-family:var(--font-heading)">{{ $g->name }}
                    @if (($g->unreadCount ?? 0) > 0)
                        {{-- New topics since you were last in this room. --}}
                        <span class="disc-new" title="{{ $g->unreadCount }} new since your last visit">{{ $g->unreadCount > 99 ? '99+' : $g->unreadCount }}</span>
                    @endif
                    <span class="badge badge-green group-joined-tag align-middle {{ $g->joined ? '' : 'hidden' }}" data-group-id="{{ $g->id }}">Joined</span>
                </h3>
            </a>
            @if ($g->description)
                <p class="text-sm text-gray-500 mt-1 line-clamp-2 min-h-[2.5rem]">{{ $g->description }}</p>
            @else
                <p class="mt-1 min-h-[2.5rem]"></p>
            @endif
            <div class="flex items-center flex-wrap gap-x-3 gap-y-1 text-xs text-gray-500 font-semibold mt-2 mb-3">
                <span>🧑‍🌾 {{ $g->member_count }} {{ \Illuminate\Support\Str::plural('member', $g->member_count) }}</span>
                <span>💬 {{ $g->post_count }} {{ \Illuminate\Support\Str::plural('topic', $g->post_count) }}</span>
                <span title="Replies across every topic">↩ {{ $g->reply_count ?? 0 }} {{ \Illuminate\Support\Str::plural('reply', $g->reply_count ?? 0) }}</span>
                @if (($g->viewCount ?? 0) > 0)
                    @include('community.partials.views-count', ['kind' => 'group', 'id' => $g->id, 'count' => $g->viewCount])
                @endif
            </div>
            {{-- "Open" is a promise you can only keep for a member; for
                 everyone else the honest word is Join. --}}
            <div class="disc-act">
                <a href="{{ route('community.groups.show', ['id' => $g->id]) }}"
                   class="btn btn-primary disc-open {{ $g->joined ? '' : 'is-off' }}">Open</a>
                <button type="button" class="btn btn-primary disc-join {{ $g->joined ? 'is-off' : '' }}"
                        data-group-id="{{ $g->id }}" data-name="{{ $g->name }}">Join</button>
            </div>
        </div>
    </div>
@endforeach
