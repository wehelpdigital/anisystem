{{-- A seat on the podium, worn beside a member's name.

     Only twenty of these exist in the whole community at once, which is the
     entire point: the level chip beside it says how far somebody has walked
     and everybody can eventually wear a high one, but this says who is in
     front TODAY and somebody has to lose it for somebody else to gain it.

     The metal narrows as it climbs — nickel and bronze hold five seats each,
     silver four, gold three, platinum two, and diamond is only ever first.
     The number is always shown, because "#4" and "#6" are both gold and the
     difference between them is the thing worth reading.

     Reads the same five-minute scoreboard every other badge reads, through
     one cached podium, so a wall of thirty cards costs one lookup rather than
     thirty. Links to the board so a badge nobody recognises explains itself.

     Expects: $topUser (a User). Optional: $topBig (the profile's larger cut),
     $topFlat (a plain span, for the cards whose whole line is already a link
     — an <a> inside an <a> tears the card in two). --}}
@php
    $tpUser = $topUser ?? null;
    $tp = null;
    if ($tpUser && $tpUser->id && ! $tpUser->is_assistant) {
        $tp = \App\Support\CommunityRank::podiumFor((int) $tpUser->id);
    }
    $tpFlat = $topFlat ?? false;
    $tpTag = $tpFlat ? 'span' : 'a';
@endphp
@if ($tp)
    <{{ $tpTag }} class="topb topb-{{ $tp['key'] }} {{ ($topBig ?? false) ? 'topb-big' : '' }}"
       @unless ($tpFlat) href="{{ route('community.ranking') }}#rankings" @endunless
       title="{{ $tp['name'] }} · number {{ $tp['place'] }} in the community">
        {{-- The medal, drawn. It used to be two strokes and a filled disc in
             currentColor, so the mark took the metal's own tint; the drawing
             does not, and the metal is still said by the chip around it —
             its colour, its border and the sheen the top three carry. --}}
        <span class="topb-m" aria-hidden="true"></span>
        <span class="topb-n">#{{ $tp['place'] }}</span>
        {{-- Its own hiding class, not the framework's: this chip is drawn on
             pages whose stylesheet is built separately, and a utility that
             does not survive that build turns the whole sentence visible. --}}
        <span class="topb-say">{{ $tp['name'] }}, number {{ $tp['place'] }} in the community</span>
    </{{ $tpTag }}>
@endif
