{{-- One feed post card. Drawn by the wall's first render, by the "older posts"
     endpoint, by a profile wall and by the saved-posts page, so everything a
     post can be must be expressible here.

     Expects: $post (with author, comments_count, reactionSummary). Optional:
     $friendIds, $followingIds, $savedIds — each defaulted, because older
     callers do not know about following or bookmarks and must keep working. --}}
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
<article class="card p-4 mb-5 feed-post wall-post fp-card" id="wallpost-{{ $post->id }}" data-post-id="{{ $post->id }}">
    <header class="flex items-start gap-3">
        @include('community.partials.avatar-status', ['user' => $author, 'size' => 'avatar-md'])
        <div class="min-w-0 grow">
            <p class="text-sm leading-tight flex items-center flex-wrap gap-x-1.5 gap-y-0.5">
                <a href="{{ route('community.connect.profile', ['userId' => $author->id]) }}" class="font-semibold text-gray-900 hover:text-brand-700">{{ $author->full_name }}</a>
                @if ($isFriend)<span class="badge badge-green align-middle">Co-farmer</span>@endif
                @include('community.partials.dm-btn', ['user' => $author])
            </p>
            <p class="text-xs text-gray-400">
                @if ($place)📍 {{ $place }} · @endif{{ $post->created_at?->diffForHumans() }}
            </p>
        </div>
        {{-- Following is one-sided, so it belongs on the post as well as the
             profile: this is where you decide you want more of somebody. --}}
        @unless ($isMine)
            <button type="button" class="fp-follow {{ $following ? 'is-on' : '' }}"
                    data-follow="{{ $author->id }}" data-name="{{ $author->full_name }}"
                    aria-pressed="{{ $following ? 'true' : 'false' }}">
                <span class="on">Following</span><span class="off">+ Follow</span>
            </button>
        @endunless
    </header>

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
        @if ($post->imagePath)
            {{-- media-skel: shimmer while the photo decodes, vanish if it 404s. --}}
            {{-- Tapping the picture opens it whole, with the post's comments
                 under it — the crop above is only what fits the column. --}}
            <div class="post-media media-skel js-post-photo" role="button" tabindex="0"
                 data-post-id="{{ $post->id }}"
                 data-full="{{ \App\Support\MediaStore::url($post->imagePath) }}"
                 aria-label="Open this photo and its comments">
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

        {{-- A share carries the original rather than copying it, so what is
             quoted here is always what its author currently says. --}}
        @if ($shared)
            <a class="fp-shared" href="{{ route('community.index') }}#wallpost-{{ $shared->id }}">
                <span class="fp-shared-head">
                    @if ($shared->author?->avatarPath)
                        <img src="{{ \App\Support\MediaStore::url($shared->author->avatarPath) }}" alt="">
                    @else
                        <i>{{ $shared->author?->initials ?: '?' }}</i>
                    @endif
                    <b>{{ $shared->author?->full_name ?: 'A farmer' }}</b>
                    <em>{{ $shared->created_at?->diffForHumans() }}</em>
                </span>
                @if (trim((string) $shared->body) !== '')
                    <span class="fp-shared-body">{{ \Illuminate\Support\Str::limit(strip_tags($shared->body), 220) }}</span>
                @endif
                @if ($shared->imagePath)
                    <img class="fp-shared-img" src="{{ \App\Support\MediaStore::url($shared->imagePath) }}" alt="" loading="lazy">
                @endif
            </a>
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
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><path stroke-linecap="round" stroke-linejoin="round" d="M4 12v7a2 2 0 002 2h12a2 2 0 002-2v-7M16 6l-4-4-4 4M12 2v14"/></svg>
            <span class="fp-lbl">Share</span>
        </button>
    </div>
</article>
