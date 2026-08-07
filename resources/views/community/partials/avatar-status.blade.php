{{-- A member avatar with their "cloud" status floating above it (read-only),
     for use wherever members appear — wall, members, co-farmers, discussions.
     Falls back to a plain avatar when the member has no status.
     Expects: $user, $size (default avatar-md), optional $link. --}}
@php $sz = $size ?? 'avatar-md'; @endphp
@if (filled(optional($user)->statusBubble))
    <span class="status-cloud-wrap shrink-0">
        @include('community.partials.avatar', ['user' => $user, 'size' => $sz, 'link' => $link ?? true, 'showOnline' => true])
        <span class="status-cloud" title="{{ $user->statusBubble }}"><span class="status-cloud-text">💭 {{ \Illuminate\Support\Str::limit($user->statusBubble, 60) }}</span></span>
    </span>
@else
    @include('community.partials.avatar', ['user' => $user, 'size' => $sz, 'link' => $link ?? true])
@endif
