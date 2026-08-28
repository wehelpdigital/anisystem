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
    /* Her face, wherever she turns up.
     *
     * She was drawn as initials on a hue here while wearing her portrait on
     * every other screen, which made the technician in the community look
     * like a different account from the one in the chat. The photo comes from
     * the AI settings rather than her user row: that is where the app keeps
     * it, and it is the same one the chat header and the floating button
     * already use.
     *
     * The no-LINK rule below is untouched — a face is not a door, and there
     * is still no wall behind hers to visit. */
    $aiFace = ($user && $user->is_assistant)
        ? \App\Models\AiSetting::current()?->faceUrl()
        : null;
    $tag = $doLink ? 'a' : 'span';
    $onlineDot = ($showOnline ?? false) && $user && method_exists($user, 'isOnline') && $user->isOnline();
    /* What the photo viewer says about the person under the face (see
     * community.partials.avatar-zoom): where they farm and the rank they
     * have climbed to. Carried as data attributes so the viewer needs no
     * request for them — the rank is a lookup in CommunityRank's cached
     * map, the same one every badge already reads. */
    $zRank = null;
    if ($photo && $user && ! $user->is_assistant) {
        try { $zRank = \App\Support\CommunityRank::rankFor((int) $user->id); } catch (\Throwable $e) { $zRank = null; }
    }
    $zPlace = $user ? trim(implode(', ', array_filter([$user->city, $user->province]))) : '';
@endphp
@if ($onlineDot)<span class="avatar-online-wrap shrink-0">@endif
<{{ $tag }} @if($doLink) href="{{ route('community.connect.profile', ['userId' => $user->id]) }}" @endif
    class="avatar {{ $sz }} {{ $hue }} overflow-hidden" title="{{ optional($user)->full_name }}{{ $onlineDot ? ' · Online' : '' }}"
    @if ($aiFace)
    @elseif ($photo && $user && ! $user->is_assistant)
        data-z-id="{{ $user->id }}"
        @if ($zPlace) data-z-place="{{ $zPlace }}" @endif
        @if ($zRank) data-z-rank="{{ $zRank['emoji'] }} Lv {{ $zRank['n'] }} · {{ $zRank['name'] }}" data-z-arc="{{ $zRank['arc'] }}" @endif
    @endif>
    @if ($aiFace)
        {{-- Hers comes from the AI settings, not from her user row: it is a
             file this app ships rather than one anybody uploaded. --}}
        <img src="{{ $aiFace }}" alt="{{ optional($user)->full_name }}" class="w-full h-full object-cover">
    @elseif ($photo)
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
