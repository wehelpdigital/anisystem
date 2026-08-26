{{-- One shared co-farmer, as a card: face and name, the level they climbed
     to, where they farm, the thought they pinned, their numbers — and the
     wall's own Follow pill, live. The gradient edge is the wrapper; the
     white card sits inside it. Expects: $user, $followers, $coFarmers,
     $mutual, $isFollowed. --}}
@php
    $mutPlace = trim(implode(', ', array_filter([$user->city, $user->province])));
    $mutBubble = trim((string) ($user->statusBubble ?? ''));
@endphp
<div class="mut-card" data-mut-find="{{ mb_strtolower($user->full_name . ' ' . $mutPlace . ' ' . ($user->profession ?? '')) }}">
    <div class="mut-card-in">
        <div class="mut-head">
            <a href="{{ route('community.connect.profile', ['userId' => $user->id]) }}" class="mut-face">
                @include('community.partials.avatar', ['user' => $user, 'size' => 'avatar-md', 'link' => false])
            </a>
            <div class="mut-mid">
                <span class="mut-name-row">
                    <a class="mut-name" href="{{ route('community.connect.profile', ['userId' => $user->id]) }}">{{ $user->full_name }}</a>
                    @include('community.partials.rank-badge', ['rankUser' => $user])
                </span>
                @if ($mutPlace)
                    <span class="mut-loc">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a2 2 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        {{ $mutPlace }}
                    </span>
                @endif
            </div>
            <button type="button" class="fp-follow {{ $isFollowed ? 'is-on' : '' }}"
                    data-follow="{{ $user->id }}" data-name="{{ $user->full_name }}"
                    aria-pressed="{{ $isFollowed ? 'true' : 'false' }}">
                <span class="on">Following</span><span class="off">+ Follow</span>
            </button>
        </div>
        @if ($mutBubble !== '')
            <p class="mut-bubble">💭 <i>{{ \Illuminate\Support\Str::limit($mutBubble, 70) }}</i></p>
        @endif
        <p class="mut-counts">
            <span><b>{{ $followers }}</b> {{ \Illuminate\Support\Str::plural('follower', $followers) }}</span>
            <span><b>{{ $coFarmers }}</b> {{ \Illuminate\Support\Str::plural('co-farmer', $coFarmers) }}</span>
            @if ($mutual > 0)<span><b>{{ $mutual }}</b> mutual</span>@endif
        </p>
    </div>
</div>
