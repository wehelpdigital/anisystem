{{-- A member's level, worn beside their name.

     One chip, drawn wherever the community says who is speaking: the wall's
     cards, the homepage wall, a discussion's topics and replies, the profile
     header. The LEVEL is the number, the TITLE is the word — Lv 54 wears
     "Community Knight" and every level in that decade wears it too. It reads
     from the same five-minute scoreboard every other badge reads
     (CommunityRank::map), so twenty authors on a page cost one lookup.

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
       title="Level {{ $rb['n'] }} of {{ \App\Support\CommunityRank::MAX_LEVEL }} · {{ $rb['name'] }}">
        <span class="rankb-e" aria-hidden="true">{{ $rb['emoji'] }}</span>
        <span class="rankb-lv">Lv {{ $rb['n'] }}</span>
        <span class="rankb-t">{{ $rb['name'] }}</span>
    </a>
@endif
