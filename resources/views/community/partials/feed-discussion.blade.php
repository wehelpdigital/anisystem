{{-- A discussion, dealt into the wall as if it were a post.

     A rail card is furniture; a card in the stream is something a reader stops
     at. So this wears the post card's shape — same width, same rhythm — and
     differs only where it must: a banner instead of a photo, and Join instead
     of the usual actions.

     Expects: $discussion (with member_count, post_count, joined). --}}
@php use App\Support\CommunityAvatar; @endphp
@php $hue = CommunityAvatar::hue($discussion->name); @endphp
<article class="card p-0 mb-5 feed-post fp-card fd-card" data-discussion-card="{{ $discussion->id }}">
    {{-- A banner only when there is one to show. Empty, it was a dark strip
         with the word "Discussion" pinned to the very edge of the screen. --}}
    @if ($discussion->bannerImagePath)
        <div class="fd-banner {{ $hue }}">
            <img src="{{ \App\Support\MediaStore::url($discussion->bannerImagePath) }}" alt="" loading="lazy">
        </div>
    @endif
    <div class="p-4">
        <span class="fd-kicker">Discussion</span>
        <div class="flex items-start gap-3">
            <span class="avatar avatar-md avatar-sq overflow-hidden {{ $hue }} shrink-0">
                @if ($discussion->coverImagePath)
                    <img src="{{ \App\Support\MediaStore::url($discussion->coverImagePath) }}" alt="" class="w-full h-full object-cover">
                @else
                    {{ CommunityAvatar::monogram($discussion->name) }}
                @endif
            </span>
            <div class="min-w-0 grow">
                <a href="{{ route('community.groups.show', ['id' => $discussion->id]) }}"
                   class="block font-bold text-gray-900 leading-snug hover:text-brand-700" style="font-family:var(--font-heading)">{{ $discussion->name }}</a>
                <p class="text-xs text-gray-400 mt-0.5">
                    🧑‍🌾 {{ $discussion->member_count }} {{ \Illuminate\Support\Str::plural('member', $discussion->member_count) }}
                    · 💬 {{ $discussion->post_count }} {{ \Illuminate\Support\Str::plural('post', $discussion->post_count) }}
                </p>
            </div>
            {{-- The same tag-shaped toggle Follow uses, because it is the same
                 kind of decision: one tap in, one tap out. --}}
            <button type="button" class="fp-follow js-join-group {{ $discussion->joined ? 'is-on' : '' }}"
                    data-group-id="{{ $discussion->id }}" data-name="{{ $discussion->name }}"
                    aria-pressed="{{ $discussion->joined ? 'true' : 'false' }}">
                <span class="on">Joined</span><span class="off">Join</span>
            </button>
        </div>
        @if ($discussion->description)
            <p class="text-sm text-gray-500 mt-2 line-clamp-2">{{ $discussion->description }}</p>
        @endif
        {{-- The way in, across the card: this is the one thing the card is
             for, and it was a small button sitting off to one side. --}}
        <a href="{{ route('community.groups.show', ['id' => $discussion->id]) }}" class="btn btn-white btn-sm fd-open">Open the discussion</a>
    </div>
</article>
