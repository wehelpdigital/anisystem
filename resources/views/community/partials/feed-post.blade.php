{{-- One feed post card. Drawn by the wall's first render, by the "older posts"
     endpoint, by a profile wall and by the saved-posts page, so everything a
     post can be must be expressible here.

     Expects: $post (with author, comments_count, reactionSummary). Optional:
     $friendIds, $followingIds, $savedIds — each defaulted, because older
     callers do not know about following or bookmarks and must keep working;
     $permalink, which adds a link back to the post's own page for lists that
     have lifted it out of the wall it belongs to. --}}
@php
    $author = $post->author;
    $meId = (int) auth()->id();
    $isFriend = in_array((int) $post->authorUserId, $friendIds ?? [], true);
    $isMine = (int) $post->authorUserId === $meId;
    $following = in_array((int) $post->authorUserId, $followingIds ?? [], true);
    $saved = in_array((int) $post->id, $savedIds ?? [], true);
    $place = trim(implode(', ', array_filter([$author->city, $author->province])));
    $shared = $post->relationLoaded('sharedPost') ? $post->sharedPost : ($post->sharedPostId ? $post->sharedPost : null);
    $commentCount = $post->comments_count ?? ($post->comment_count ?? 0);
@endphp
@php
    /* The post's own colour, chosen by its id.
     *
     * Six of them, taken in turn — the same post keeps the same edge on every
     * render and on every screen, and a column of posts gets a rhythm instead
     * of a single repeated green. Deterministic, so nothing shifts under a
     * reader when the wall pages in more. */
    $fpHue = $post->id % 6;
@endphp
<article class="card p-4 mb-5 feed-post wall-post fp-card fp-hue-{{ $fpHue }}" id="wallpost-{{ $post->id }}"
         data-post-id="{{ $post->id }}" data-view="post:{{ $post->id }}">
    <header class="flex items-start gap-3">
        {{-- What is on their mind, in the cloud over the face — the same
             place the composer at the top of the wall puts it. The card
             pads its head to hold it (see .feed-post:has(.status-cloud)),
             so it hangs over the photo without crossing the card's edge. --}}
        @include('community.partials.avatar-status', ['user' => $author, 'size' => 'avatar-md'])
        {{-- Centred against the face: with a place under it the block is the
             face's height anyway and nothing moves, but a member who has not
             said where they farm gets their name in the middle of the row
             instead of hanging from the top of it. --}}
        <div class="min-w-0 grow fp-head-txt">
            <p class="text-sm leading-tight flex items-center flex-wrap gap-x-1.5 gap-y-0.5">
                @if ($author->is_assistant)<span class="font-semibold author-ai">{{ $author->full_name }}</span>@else<a href="{{ route('community.connect.profile', ['userId' => $author->id]) }}" class="font-semibold text-gray-900 hover:text-brand-700">{{ $author->full_name }}</a>@endif
                @include('community.partials.dm-btn', ['user' => $author])
                {{-- The rank they have climbed to, before the follow button:
                     who somebody IS on the ladder comes before what you might
                     do about them. --}}
                @include('community.partials.rank-badge', ['rankUser' => $author])
                {{-- Following is one-sided, so it belongs on the post as well
                     as the profile: this is where you decide you want more of
                     somebody. It sits with the name and the way to write to
                     them — the three things you can do about a person, in one
                     place, rather than one of them alone in a corner. --}}
                @unless ($isMine)
                    <button type="button" class="fp-follow {{ $following ? 'is-on' : '' }}"
                            data-follow="{{ $author->id }}" data-name="{{ $author->full_name }}"
                            aria-pressed="{{ $following ? 'true' : 'false' }}">
                        <span class="on">Following</span><span class="off">+ Follow</span>
                    </button>
                @endunless
            </p>
        </div>
    </header>
    {{-- Who is speaking, across the whole card. In the column beside the
         face it had 278px of a 358px card and the third fact wrapped with
         the avatar's width sitting empty next to it. --}}
    @include('community.partials.author-facts', [
        'user' => $author,
        'isCoFarmer' => $isFriend,
        'followers' => $post->authorFollowers ?? 0,
        'coFarmers' => $post->authorCoFarmers ?? 0,
        'mutual' => $post->authorMutual ?? 0,
        'fallback' => $author?->created_at
            ? '🌱 Member since ' . $author->created_at->timezone('Asia/Manila')->format('M Y')
            : null,
    ])

    @if ($post->isRestricted ?? false)
        @include('community.partials.restricted', ['reason' => $post->restrictedReason ?? null])
    @else
        @if (trim((string) $post->body) !== '')
            <p class="fp-body text-sm text-gray-700 mt-2 whitespace-pre-line break-words">{!! \App\Support\CommunityText::render($post->body) !!}</p>
        @endif
        @php $ytVid = \App\Support\CommunityText::youtubeId($post->body); @endphp
        @if ($ytVid)
            @include('community.partials.youtube-card', ['vid' => $ytVid])
        @endif
        @php $fpShots = $post->shots(); @endphp
        @if (count($fpShots) > 1)
            @include('community.partials.post-gallery', ['shots' => $fpShots])
        @elseif ($post->imagePath)
            {{-- media-skel: shimmer while the photo decodes, vanish if it 404s. --}}
            {{-- Tapping the picture opens it whole in the lightbox — the crop
                 here is only what fits the column. The lightbox binds to the
                 image itself, so this box carries no handler of its own. --}}
            <div class="post-media media-skel">
                <img src="{{ \App\Support\MediaStore::url($post->imagePath) }}" alt="" loading="lazy"
                    onload="this.classList.add('is-loaded')"
                    onerror="this.closest('.media-skel')?.classList.add('is-gone')">
                <span class="post-media-full">
                    <svg style="width:.7rem;height:.7rem" fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 8V4h4M20 8V4h-4M4 16v4h4m12-4v4h-4"/></svg>
                    See it whole
                </span>
            </div>
        @endif
        @if ($post->videoPath ?? null)
            @include('community.partials.video-embed', ['src' => $post->videoPath, 'poster' => $post->videoPoster ?? null])
        @endif

        {{-- A share carries the original rather than copying it. Drawn by
             the shared partial, so the profile wall shows the same thing. --}}
        @if ($shared)
            @include('community.partials.shared-post', ['shared' => $shared])
        @endif
    @endif

    @include('community.partials.react-bar', ['type' => 'wallpost', 'id' => $post->id, 'summary' => $post->reactionSummary ?? null])

    {{-- The comments used to be printed under every post, which made the wall
         a wall of other people's conversations. The count is the door now, and
         the conversation opens in a sheet. --}}
    <div class="fp-acts">
        <button type="button" class="fp-act js-open-comments" data-post-id="{{ $post->id }}" aria-label="Comments">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><path stroke-linecap="round" stroke-linejoin="round" d="M8 12h8m-8-4h5m-5 8h3m-6 4V6a2 2 0 012-2h12a2 2 0 012 2v8a2 2 0 01-2 2H8l-4 4z"/></svg>
            <span class="fp-n" data-comment-count="{{ $post->id }}">{{ $commentCount }}</span>
            <span class="fp-lbl">{{ \Illuminate\Support\Str::plural('Comment', $commentCount) }}</span>
        </button>
        <button type="button" class="fp-act js-bookmark {{ $saved ? 'is-on' : '' }}" data-post-id="{{ $post->id }}"
                aria-pressed="{{ $saved ? 'true' : 'false' }}" aria-label="Save this post">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><path stroke-linecap="round" stroke-linejoin="round" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-4-7 4V5z"/></svg>
            <span class="fp-lbl"><span class="on">Saved</span><span class="off">Save</span></span>
        </button>
        <button type="button" class="fp-act js-share" data-post-id="{{ $post->id }}" aria-label="Share this post">
            {{-- The bent arrow everybody already reads as "share", rather than
                 the upload tray this carried, which reads as "send a file". --}}
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><path stroke-linecap="round" stroke-linejoin="round" d="M21 11L13 4v4C7.5 8.5 4.5 12.5 3.5 19c2.2-3.2 5.2-4.7 9.5-4.7V18l8-7z"/></svg>
            <span class="fp-lbl">Share</span>
        </button>
        {{-- Somebody else's post can be objected to. Never your own: for
             that the answer is delete, not report. --}}
        @unless ($isMine)
            <button type="button" class="fp-act rp-door" data-report="{{ ($post->isReel ?? false) ? 'story' : 'post' }}:{{ $post->id }}"
                    title="Report this post" aria-label="Report this post">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><path stroke-linecap="round" stroke-linejoin="round" d="M4 21V4m0 1h11l-1.5 3L15 11H4"/></svg>
            </button>
        @endunless
        {{-- The way back to where the post lives.
             Only where a card has been lifted out of its wall — the saved
             list — because everywhere else you are already there. --}}
        @if ($permalink ?? false)
            <a class="fp-act fp-open" href="{{ route('community.post.show', ['id' => $post->id]) }}"
               title="Open this post on the wall">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><path stroke-linecap="round" stroke-linejoin="round" d="M14 5h5v5m0-5l-7 7M18 13v5a1 1 0 01-1 1H6a1 1 0 01-1-1V7a1 1 0 011-1h5"/></svg>
                <span class="fp-lbl">See the post</span>
            </a>
        @endif
        {{-- How many have looked at it. Not a button: nothing happens when
             you press it, it is the post telling you how far it went. --}}
        <span class="fp-act fp-views" title="Views">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><path stroke-linecap="round" stroke-linejoin="round" d="M2.5 12S6 5.5 12 5.5 21.5 12 21.5 12 18 18.5 12 18.5 2.5 12 2.5 12z"/><circle cx="12" cy="12" r="3"/></svg>
            <span class="fp-n" data-view-count="post:{{ $post->id }}">{{ (int) ($post->viewCount ?? 0) }}</span>
        </span>
        {{-- When it was written, at the end of the row: a fact about the post
             rather than a third thing crowding the name. --}}
        <time class="fp-when" datetime="{{ $post->created_at?->toIso8601String() }}"
              title="{{ $post->created_at?->timezone('Asia/Manila')->format('M j, Y g:i A') }}">{{ $post->created_at?->diffForHumans() }}</time>
    </div>
</article>
