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
    /* What the photo viewer says about the person under the face (see
     * community.partials.avatar-zoom): where they farm, what they do, the
     * rank they have climbed to, the thought they pinned. Carried as data
     * attributes so the viewer needs no request — the rank is a lookup in
     * CommunityRank's cached map, the same one every badge already reads. */
    $zRank = null;
    if ($photo && $user && ! $user->is_assistant) {
        try { $zRank = \App\Support\CommunityRank::rankFor((int) $user->id); } catch (\Throwable $e) { $zRank = null; }
    }
    $zPlace = $user ? trim(implode(', ', array_filter([$user->city, $user->province]))) : '';
@endphp
@if ($onlineDot)<span class="avatar-online-wrap shrink-0">@endif
<{{ $tag }} @if($doLink) href="{{ route('community.connect.profile', ['userId' => $user->id]) }}" @endif
    class="avatar {{ $sz }} {{ $hue }} overflow-hidden" title="{{ optional($user)->full_name }}{{ $onlineDot ? ' · Online' : '' }}"
    @if ($photo && $user && ! $user->is_assistant)
        @if ($zPlace) data-z-place="{{ $zPlace }}" @endif
        @if (filled($user->profession)) data-z-prof="{{ \Illuminate\Support\Str::limit($user->profession, 40) }}" @endif
        @if (filled($user->statusBubble)) data-z-bubble="{{ \Illuminate\Support\Str::limit($user->statusBubble, 60) }}" @endif
        @if ($zRank) data-z-rank="{{ $zRank['emoji'] }} Lv {{ $zRank['n'] }} · {{ $zRank['name'] }}" data-z-arc="{{ $zRank['arc'] }}" @endif
    @endif>
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
