{{-- A member's rank, worn beside their name.

     One chip, drawn wherever the community says who is speaking: the wall's
     cards, the homepage wall, a discussion's topics and replies, the profile
     header. It reads from the same five-minute scoreboard every other badge
     reads (CommunityRank::map), so twenty authors on a page cost one lookup.

     Links to the ladder's guide so a badge nobody recognises explains
     itself. The assistant wears none: it is not a member and holds no rank.

     Expects: $rankUser (a User). Optional: $rankBig (the profile's larger
     cut). --}}
@php
    $rbUser = $rankUser ?? null;
    $rb = null;
    if ($rbUser && $rbUser->id && ! $rbUser->is_assistant) {
        $rb = \App\Support\CommunityRank::rankFor((int) $rbUser->id);
    }
@endphp
@if ($rb)
    <a class="rankb rankb-a{{ $rb['arc'] }} {{ ($rankBig ?? false) ? 'rankb-big' : '' }}"
       href="{{ route('community.ranking') }}#guide"
       title="Rank {{ $rb['n'] }} of {{ count(\App\Support\CommunityRank::TIERS) }} · {{ $rb['name'] }} ({{ $rb['en'] }})">
        <span class="rankb-e" aria-hidden="true">{{ $rb['emoji'] }}</span>
        <span class="rankb-t">{{ $rb['name'] }}</span>
    </a>
@endif
