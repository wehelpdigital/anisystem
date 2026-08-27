{{-- One shared co-farmer, as a card: face with the thought floating over it
     in the wall's own cloud, name, then level and place on one line, their
     numbers — and the wall's Follow pill, live. The gradient edge is the
     wrapper (thicker along the top); the white card sits inside it.
     Expects: $user, $followers, $coFarmers, $mutual, $isFollowed. --}}
@php
    $mutPlace = trim(implode(', ', array_filter([$user->city, $user->province])));
    $mutBubble = trim((string) ($user->statusBubble ?? ''));
@endphp
<div class="mut-card" data-mut-find="{{ mb_strtolower($user->full_name . ' ' . $mutPlace . ' ' . ($user->profession ?? '')) }}">
    <div class="mut-card-in">
        {{-- The wall's cloud, floating in the air the card reserves at its
             top, tail pointing down at the face below. Anchored to the card
             rather than to the face itself: the face is centred against a
             taller column of words, so a cloud hung off it landed on the
             name instead of over the photo. --}}
        @if ($mutBubble !== '')
            <span class="status-cloud mut-cloud"><span class="status-cloud-text">{{ \Illuminate\Support\Str::limit($mutBubble, 60) }}</span></span>
        @endif
        <div class="mut-head">
            <a href="{{ route('community.connect.profile', ['userId' => $user->id]) }}" class="mut-face">
                @include('community.partials.avatar', ['user' => $user, 'size' => 'avatar-md', 'link' => false])
            </a>
            <div class="mut-mid">
                <a class="mut-name" href="{{ route('community.connect.profile', ['userId' => $user->id]) }}">{{ $user->full_name }}</a>
                <span class="mut-line">
                    @include('community.partials.rank-badge', ['rankUser' => $user])
                    @include('community.partials.top-badge', ['topUser' => $user])
                    @if ($mutPlace)
                        <span class="mut-loc">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a2 2 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            {{-- Its own box: a bare text node in a flex row
                                 cannot be told to trail off. --}}
                            <span class="mut-loc-t">{{ $mutPlace }}</span>
                        </span>
                    @endif
                </span>
            </div>
            <button type="button" class="fp-follow {{ $isFollowed ? 'is-on' : '' }}"
                    data-follow="{{ $user->id }}" data-name="{{ $user->full_name }}"
                    aria-pressed="{{ $isFollowed ? 'true' : 'false' }}">
                <span class="on">Following</span><span class="off">+ Follow</span>
            </button>
        </div>
        <p class="mut-counts">
            <span><b>{{ $followers }}</b> {{ \Illuminate\Support\Str::plural('follower', $followers) }}</span>
            <span><b>{{ $coFarmers }}</b> {{ \Illuminate\Support\Str::plural('co-farmer', $coFarmers) }}</span>
            @if ($mutual > 0)<span><b>{{ $mutual }}</b> mutual</span>@endif
        </p>
    </div>
</div>
