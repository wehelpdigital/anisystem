{{-- A placement on the board, as the badge rather than the word.

     "Rank: 4" spends five characters saying what the column already is. The
     medal says it in one glance and, for the top twenty, says which metal it
     is worth at the same time — the same chip those members wear out in the
     community, so the board that granted it and the card that shows it are
     recognisably the same thing.

     Off the podium there is no metal, and inventing a twenty-first would
     make the six below it mean nothing. Those places keep the medal shape in
     plain grey: still a placement, still readable, and visibly not a prize.

     Expects: $place (1-based; 0 or less for unplaced). --}}
@php
    $pcPlace = (int) ($place ?? 0);
    $pcMetal = \App\Support\CommunityRank::metalAt($pcPlace);
    $pcSays = $pcPlace > 0
        ? ($pcMetal ? $pcMetal['name'] . ' · number ' . $pcPlace . ' in the community'
                    : 'Number ' . $pcPlace . ' in the community')
        : 'Not on the board yet';
@endphp
<span class="topb rk-place {{ $pcMetal ? 'topb-' . $pcMetal['key'] : 'topb-plain' }}" title="{{ $pcSays }}">
    <span class="topb-m" aria-hidden="true">
        <svg viewBox="0 0 24 24" fill="none">
            <path d="M7.4 2.4 11 9.4M16.6 2.4 13 9.4" stroke="currentColor"
                  stroke-width="2.6" stroke-linecap="round" />
            <circle cx="12" cy="15.2" r="6" fill="currentColor" />
        </svg>
    </span>
    <span class="topb-n">{{ $pcPlace > 0 ? '#' . $pcPlace : '—' }}</span>
    <span class="topb-say">{{ $pcSays }}</span>
</span>
