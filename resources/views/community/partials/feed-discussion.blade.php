{{-- A discussion, dealt into the wall as if it were a post.

     A rail card is furniture; a card in the stream is something a reader stops
     at. So this wears the post card's shape — same width, same rhythm — and
     differs only where it must: a cover instead of a photo, the room's own
     face over it, and the last thing said in there, which is the honest
     answer to "is this worth walking into".

     Expects: $discussion (with member_count, post_count, joined, latestTopic
     carrying reply_count). --}}
@php use App\Support\CommunityAvatar; @endphp
@php
    $hue = CommunityAvatar::hue($discussion->name);
    $topic = $discussion->latestTopic ?? null;
    $fdHue = $discussion->id % 6;
@endphp
<article class="card p-0 mb-5 feed-post fp-card fd-card fp-hue-{{ $fdHue }}"
         data-discussion-card="{{ $discussion->id }}" data-view="group:{{ $discussion->id }}">
    {{-- The cover, with the room's face standing on it — the shape every
         reader already knows a group by. --}}
    <div class="fd-top">
        <div class="fd-banner {{ $hue }}">
            @if ($discussion->bannerImagePath)
                <img src="{{ \App\Support\MediaStore::url($discussion->bannerImagePath) }}" alt="" loading="lazy">
            @endif
            <span class="fd-kicker">Discussion</span>
        </div>
        <a href="{{ route('community.groups.show', ['id' => $discussion->id]) }}" class="fd-face {{ $hue }}">
            @if ($discussion->coverImagePath)
                <img src="{{ \App\Support\MediaStore::url($discussion->coverImagePath) }}" alt="">
            @else
                {{ CommunityAvatar::monogram($discussion->name) }}
            @endif
        </a>
    </div>

    <div class="fd-body">
        <div class="fd-head">
            <div class="min-w-0 grow">
                <a href="{{ route('community.groups.show', ['id' => $discussion->id]) }}" class="fd-name">{{ $discussion->name }}</a>
                <p class="fd-meta">
                    🧑‍🌾 {{ $discussion->member_count }} {{ \Illuminate\Support\Str::plural('member', $discussion->member_count) }}
                    · 💬 {{ $discussion->post_count }} {{ \Illuminate\Support\Str::plural('topic', $discussion->post_count) }}
                    @if (($discussion->viewCount ?? 0) > 0)
                        · @include('community.partials.views-count', ['kind' => 'group', 'id' => $discussion->id, 'count' => $discussion->viewCount])
                    @endif
                </p>
            </div>
            {{-- The same tag-shaped toggle Follow uses, because it is the same
                 kind of decision: one tap in, and the room is yours. --}}
            <button type="button" class="fp-follow js-join-group {{ $discussion->joined ? 'is-on' : '' }}"
                    data-group-id="{{ $discussion->id }}" data-name="{{ $discussion->name }}"
                    aria-pressed="{{ $discussion->joined ? 'true' : 'false' }}">
                <span class="on">Joined</span><span class="off">Join</span>
            </button>
        </div>

        @if ($discussion->description)
            <p class="fd-desc">{{ $discussion->description }}</p>
        @endif

        {{-- The last thing said in there. A room is its conversation, not its
             member count, so this is the part of the card that earns the tap. --}}
        @if ($topic)
            <a href="{{ route('community.groups.show', ['id' => $discussion->id]) }}#topic-{{ $topic->id }}" class="fd-topic">
                <span class="fd-topic-tag">Latest topic</span>
                <span class="fd-topic-title">{{ $topic->title ?: \Illuminate\Support\Str::limit(strip_tags($topic->body), 70) }}</span>
                <span class="fd-topic-meta">
                    @if ($topic->author)
                        {{-- link => false: this whole line is already a link,
                             and an <a> inside an <a> splits the card in two. --}}
                        @include('community.partials.avatar', ['user' => $topic->author, 'size' => 'avatar-sm', 'link' => false])
                        <b>{{ $topic->author->firstName }}</b>
                    @endif
                    <span>💬 {{ $topic->reply_count }} {{ \Illuminate\Support\Str::plural('comment', $topic->reply_count) }}</span>
                    <span>· {{ $topic->created_at?->diffForHumans(null, true) }} ago</span>
                </span>
            </a>
        @endif

        {{-- The way in, across the card: this is the one thing the card is
             for, and it was a small button sitting off to one side.

             What it offers depends on where you stand. A stranger is asked
             to join — the same question, with the same confirmation, that
             the pill above asks. A member is invited to walk in. --}}
        @if ($discussion->joined)
            <a href="{{ route('community.groups.show', ['id' => $discussion->id]) }}"
               class="btn btn-primary btn-sm fd-open">Take a look inside</a>
        @else
            <button type="button" class="btn btn-primary btn-sm fd-open js-join-group"
                    data-group-id="{{ $discussion->id }}" data-name="{{ $discussion->name }}">Join this discussion</button>
        @endif
    </div>
</article>
