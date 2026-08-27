{{-- A single reply on a group post, with its child replies when top-level.
     Expects: $reply; optional $children (nested replies), $isReply. The
     Sumagot button carries the thread's top reply id so replies-to-replies
     stay in the same thread. --}}
@php
    $rAuthor = optional($reply->author);
    $rSummary = $reply->reactionSummary ?? ['counts' => [], 'mine' => null];
    $rMine = $rSummary['mine'] ?? null;
    $rCounts = $rSummary['counts'] ?? [];
    $isReply = $isReply ?? false;
    $threadId = $reply->parentId ?: $reply->id;
    $rIsGif = $reply->imagePath && str_ends_with(mb_strtolower($reply->imagePath), '.gif');
    $isMine = (int) $reply->userId === (int) auth()->id();
    $gone = (bool) ($reply->isDeleted ?? false);
@endphp
<div class="group-reply" data-reply-id="{{ $reply->id }}">
    <div class="flex items-start gap-2.5">
        @include('community.partials.avatar', ['user' => $reply->author, 'size' => 'avatar-sm'])
        <div class="min-w-0 grow">
            @if ($gone)
                <div class="bg-gray-50 rounded-xl rounded-tl-md px-3 py-2 text-xs text-gray-400 italic group-reply-tombstone">
                    This comment was deleted
                </div>
            @else
                <div class="bg-gray-50 rounded-xl rounded-tl-md px-3 py-2">
                    @php $rIsAi = (bool) optional($reply->author)->is_assistant; @endphp
                    {{-- The assistant's answers are named in green: a reader
                         scanning a thread should be able to tell an answer
                         from the AI apart from a neighbour's without reading
                         the words first. --}}
                    <p class="text-xs font-semibold text-gray-900">@if ($rIsAi)<span class="author-ai" title="Answered by the AI Technician">{{ $rAuthor->full_name }}</span>@elseif ($reply->author)<a href="{{ route('community.connect.profile', ['userId' => $reply->author->id]) }}" class="hover:text-brand-700">{{ $rAuthor->full_name }}</a>
                        @include('community.partials.rank-badge', ['rankUser' => $reply->author])@include('community.partials.top-badge', ['topUser' => $reply->author])@else{{ 'Member' }}@endif
                        <span class="text-gray-400 font-normal" title="{{ $reply->created_at }}">· {{ $reply->created_at?->diffForHumans(null, true) }}</span></p>
                    @if ($reply->isRestricted ?? false)
                        @include('community.partials.restricted', ['reason' => $reply->restrictedReason ?? null])
                    @else
                        @if ($reply->body)
                            <p class="text-sm text-gray-700 whitespace-pre-line break-words">{!! \App\Support\CommunityText::render($reply->body) !!}</p>
                        @endif
                        @php $rShots = method_exists($reply, 'shots') ? $reply->shots() : array_filter([$reply->imagePath]); @endphp
                        @if (count($rShots) > 1)
                            {{-- Several pictures answer the way several on a
                                 post do: one at a time, slowly, pushable by a
                                 thumb, whole in the lightbox when tapped. --}}
                            @include('community.partials.post-gallery', ['shots' => $rShots, 'mini' => true])
                        @elseif ($reply->imagePath)
                            <span class="post-media reply-media"><img src="{{ \App\Support\MediaStore::url($reply->imagePath) }}" alt="Reply photo" loading="lazy">@if ($rIsGif)<span class="gif-badge">GIF</span>@endif</span>
                        @endif
                        @php $rClips = method_exists($reply, 'clips') ? $reply->clips() : array_filter([['video' => $reply->videoPath ?? null, 'poster' => $reply->videoPoster ?? null]], fn ($c) => ! empty($c['video'])); @endphp
                        @include('community.partials.clip-carousel', ['clips' => $rClips, 'mini' => true])
                    @endif
                </div>
                <div class="react-bar react-bar-mini">
                    @foreach (\App\Models\CommunityReaction::REACTION_META as $rk => $meta)
                        <button type="button" class="react-btn {{ $rMine === $rk ? 'is-mine' : '' }}"
                            data-react="{{ $rk }}" data-target-type="reply" data-target-id="{{ $reply->id }}"
                            aria-pressed="{{ $rMine === $rk ? 'true' : 'false' }}"
                            title="{{ $meta['label'] }}" aria-label="{{ $meta['label'] }}">
                            <span class="e">{{ $meta['emoji'] }}</span><span class="react-count">{{ ($rCounts[$rk] ?? 0) > 0 ? $rCounts[$rk] : '' }}</span>
                        </button>
                    @endforeach
                    <button type="button" class="js-group-reply reply-link" data-parent-id="{{ $threadId }}"
                            @if ($reply->author && ! $isMine) data-author-id="{{ $reply->author->id }}" data-author-name="{{ $reply->author->full_name }}" @endif>Reply</button>
                    @if ($isMine)
                        <button type="button" class="js-reply-delete reply-link" data-reply-id="{{ $reply->id }}">Delete</button>
                    @endif
                    @include('community.partials.dm-btn', ['user' => $reply->author])
                </div>
            @endif
        </div>
    </div>
    @unless ($isReply)
        <div class="reply-thread" data-parent-id="{{ $reply->id }}">
            @foreach (($children ?? collect()) as $child)
                @include('community.groups.partials.reply', ['reply' => $child, 'isReply' => true, 'children' => collect()])
            @endforeach
        </div>
    @endunless
</div>
