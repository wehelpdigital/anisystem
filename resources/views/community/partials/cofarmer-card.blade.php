{{-- One co-farmer card: identity, farm chips, and their latest wall post.
     Shared by the page and the pager rows, so the two can never drift.
     Expects: $friend, $latestPosts (keyed by wallUserId). --}}
    @php
        $latest = $latestPosts->get($friend->id);
        $place = trim(implode(', ', array_filter([$friend->city, $friend->province])));
    @endphp
    <div class="card p-4 flex flex-col">
        {{-- Name + photo link straight to the wall — no separate "Visit wall" button. --}}
        <div class="flex items-start gap-3">
            @include('community.partials.avatar-status', ['user' => $friend, 'size' => 'avatar-lg'])
            <div class="min-w-0 grow">
                <a href="{{ route('community.connect.profile', ['userId' => $friend->id]) }}" class="font-bold text-gray-900 hover:text-brand-700 leading-snug block" style="font-family:var(--font-heading)">{{ $friend->full_name }}</a>
                @if (filled($friend->headline))<p class="text-xs text-gray-600 font-medium mt-0.5 line-clamp-1">{{ $friend->headline }}</p>@endif
                @if ($place)<p class="text-xs text-gray-500 mt-0.5">📍 {{ $place }}</p>@endif
                @if ($friend->bio)<p class="text-xs text-gray-400 mt-1 line-clamp-2">{{ $friend->bio }}</p>@endif
            </div>
            @include('community.partials.dm-btn', ['user' => $friend])
        </div>
        @include('community.partials.farm-chips', ['user' => $friend])
        <div class="mt-3 pt-3 border-t border-gray-100 grow">
            @if ($latest)
                <p class="text-xs font-bold text-gray-400 uppercase tracking-wide mb-1">Latest</p>
                {{-- Commentable inline: react, comment, reply (with emoji + photo) right here. --}}
                <div class="wall-post" id="wallpost-{{ $latest->id }}" data-post-id="{{ $latest->id }}">
                    @if ($latest->body)
                        <div class="plaza-clamp" data-clamp>
                            <div class="plaza-clamp-body text-sm text-gray-700 whitespace-pre-line break-words">{!! \App\Support\CommunityText::render($latest->body) !!}</div>
                            <button type="button" class="plaza-clamp-toggle hidden">Read more</button>
                        </div>
                    @endif
                    @if ($latest->imagePath)
                        {{-- media-skel: shimmer while it decodes, vanish if it 404s.
                             The inline cap keeps the placeholder at this card's
                             thumbnail height, not the feed's full-photo box. --}}
                        <div class="post-media media-skel" style="max-height:10rem">
                            <img src="{{ \App\Support\MediaStore::url($latest->imagePath) }}"
                                 alt="Photo from {{ $friend->full_name }}" loading="lazy"
                                 class="post-img rounded-lg max-h-40 w-auto border border-gray-100"
                                 onload="this.classList.add('is-loaded')"
                                 onerror="this.closest('.media-skel')?.classList.add('is-gone')">
                        </div>
                    @elseif (!$latest->body)
                        <p class="text-sm text-gray-400">📷 Shared a photo.</p>
                    @endif
                    <p class="text-[0.688rem] text-gray-400 mt-1">{{ $latest->created_at?->diffForHumans() }}</p>
                    @include('community.partials.react-bar', ['type' => 'wallpost', 'id' => $latest->id, 'summary' => $latest->reactionSummary ?? null])
                    {{-- Threads are not loaded here any more — touching
                         $latest->comments would lazy-load one thread per
                         card, quietly. The count came with the post; the
                         words are one tap away through the same fetch
                         the feed uses. --}}
                    <div class="mt-2 space-y-1.5 wall-comments">
                        @if (($latest->comments_count ?? 0) > 0)
                            <button type="button" class="js-view-all-comments text-xs font-semibold text-brand-700 hover:text-brand-800" data-post-id="{{ $latest->id }}">View all {{ $latest->comments_count }} {{ \Illuminate\Support\Str::plural('comment', $latest->comments_count) }}</button>
                        @endif
                    </div>
                    @include('community.partials.wall-comment-form', ['postId' => $latest->id])
                </div>
            @else
                <p class="text-sm text-gray-400">Wala pang bago sa wall.</p>
            @endif
        </div>
    </div>
