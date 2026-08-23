{{-- One community identity: photo if the member has one, else initials on
     their crop hue. Links to the member's profile/wall unless link=false
     (e.g. when already inside an <a>). Expects: $user, $size (avatar-sm|md|lg),
     optional $link (default true). --}}
@php
    $sz = $size ?? 'avatar-md';
    // The assistant's account exists so its answers have a name and a face,
    // not so anyone can go and look at it: there is no wall to read, nobody
    // to follow, and nothing there but the answers already on this page.
    // Gated here, so every screen that draws a member gets it for free.
    $doLink = ($link ?? true) && $user && ! $user->is_assistant;
    $hue = \App\Support\CommunityAvatar::hue(optional($user)->full_name ?: '?');
    $photo = optional($user)->avatarPath;
    $tag = $doLink ? 'a' : 'span';
    $onlineDot = ($showOnline ?? false) && $user && method_exists($user, 'isOnline') && $user->isOnline();
@endphp
@if ($onlineDot)<span class="avatar-online-wrap shrink-0">@endif
<{{ $tag }} @if($doLink) href="{{ route('community.connect.profile', ['userId' => $user->id]) }}" @endif
    class="avatar {{ $sz }} {{ $hue }} overflow-hidden" title="{{ optional($user)->full_name }}{{ $onlineDot ? ' · Online' : '' }}">
    @if ($photo)
        {{-- data-initials: a photo whose file is gone leaves a broken-image
             glyph, which reads as a broken screen rather than a member with
             no picture. app.js swaps the letters back in. --}}
        <img data-avatar-fallback data-initials="{{ optional($user)->initials ?: '?' }}"
             src="{{ \App\Support\MediaStore::url($photo) }}" alt="{{ optional($user)->full_name }}" class="w-full h-full object-cover">
    @else
        {{ optional($user)->initials ?: '?' }}
    @endif
</{{ $tag }}>
@if ($onlineDot)<span class="avatar-online-dot" title="Online"></span></span>@endif
