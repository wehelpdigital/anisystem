{{-- A run of member cards. Reused by the directory page, by "load more", and
     by My Co-Farmers, so a member looks the same wherever they are met.

     A directory of names told a reader nothing about who was worth following.
     This one carries the person's cover, what they last said, and how that
     went — which is what actually makes somebody decide.

     Expects: $members (each with connStatus, and optionally latestPost +
     isFollowed, which the directory attaches and older callers do not). --}}
@foreach ($members as $m)
    @php
        $post = $m->latestPost ?? null;
        $reactions = $post ? \App\Models\CommunityReaction::where('targetType', 'wallpost')->where('targetId', $post->id)->count() : 0;
        $shares = $post ? \App\Models\CommunityWallPost::active()->where('sharedPostId', $post->id)->count() : 0;
    @endphp
    <div class="card card-hover mb-4 mc-card" data-member-card="{{ $m->id }}">
        {{-- The cover runs to the card's edge; the face overlaps it, the way a
             profile does, so a card reads as a person rather than a row. --}}
        <div class="mc-cover">
            @if ($m->coverPath)
                <img src="{{ \App\Support\MediaStore::url($m->coverPath) }}" alt="" loading="lazy">
            @endif
        </div>
        <div class="mc-body">
            <div class="flex items-start gap-3">
                <a href="{{ route('community.connect.profile', ['userId' => $m->id]) }}" class="shrink-0 mc-face">
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
                    @if (filled($m->statusBubble))
                        {{-- What is on their mind, in the same cloud the wall uses. --}}
                        <p class="mc-bubble">💭 {{ \Illuminate\Support\Str::limit($m->statusBubble, 70) }}</p>
                    @endif
                </div>
            </div>

            @if ($post)
                <a href="{{ route('community.connect.profile', ['userId' => $m->id]) }}#wallpost-{{ $post->id }}" class="mc-post">
                    <span class="mc-post-lbl">Latest post · {{ $post->created_at?->diffForHumans() }}</span>
                    @if (trim((string) $post->body) !== '')
                        <span class="mc-post-body">{{ \Illuminate\Support\Str::limit(strip_tags($post->body), 120) }}</span>
                    @elseif ($post->imagePath)
                        <span class="mc-post-body">Shared a photo.</span>
                    @elseif ($post->videoPath)
                        <span class="mc-post-body">Shared a video.</span>
                    @endif
                    @if ($post->imagePath)
                        <img class="mc-post-img" src="{{ \App\Support\MediaStore::url($post->imagePath) }}" alt="" loading="lazy">
                    @endif
                    <span class="mc-post-stats">
                        <span title="Reactions">👍 {{ $reactions }}</span>
                        <span title="Comments">💬 {{ $post->comments_count ?? 0 }}</span>
                        <span title="Shares">↗ {{ $shares }}</span>
                    </span>
                </a>
            @endif

            <div class="mc-acts">
                @include('community.connect.partials.action', ['status' => $m->connStatus, 'memberId' => $m->id])
                <button type="button" class="fp-follow {{ ($m->isFollowed ?? false) ? 'is-on' : '' }}"
                        data-follow="{{ $m->id }}" data-name="{{ $m->full_name }}"
                        aria-pressed="{{ ($m->isFollowed ?? false) ? 'true' : 'false' }}">
                    <span class="on">Following</span><span class="off">+ Follow</span>
                </button>
            </div>
        </div>
    </div>
@endforeach
