{{-- One community identity: photo if the member has one, else initials on
     their crop hue. Links to the member's profile/wall unless link=false
     (e.g. when already inside an <a>). Expects: $user, $size (avatar-sm|md|lg),
     optional $link (default true). --}}
@php
    $sz = $size ?? 'avatar-md';
    $doLink = ($link ?? true) && $user;
    $hue = \App\Support\CommunityAvatar::hue(optional($user)->full_name ?: '?');
    $photo = optional($user)->avatarPath;
    $tag = $doLink ? 'a' : 'span';
    $onlineDot = ($showOnline ?? false) && $user && method_exists($user, 'isOnline') && $user->isOnline();
@endphp
@if ($onlineDot)<span class="avatar-online-wrap shrink-0">@endif
<{{ $tag }} @if($doLink) href="{{ route('community.connect.profile', ['userId' => $user->id]) }}" @endif
    class="avatar {{ $sz }} {{ $hue }} overflow-hidden" title="{{ optional($user)->full_name }}{{ $onlineDot ? ' · Online' : '' }}">
    @if ($photo)
        <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($photo) }}" alt="{{ optional($user)->full_name }}" class="w-full h-full object-cover">
    @else
        {{ optional($user)->initials ?: '?' }}
    @endif
</{{ $tag }}>
@if ($onlineDot)<span class="avatar-online-dot" title="Online"></span></span>@endif
