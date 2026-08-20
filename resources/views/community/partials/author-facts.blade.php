{{-- Who is speaking: the small print under a name.

     Where they farm, what they do, whether the reader already farms with
     them, and how many people follow them. A name on its own tells a reader
     nothing about whether the answer under it is worth taking.

     One partial for the wall and for a discussion's topics, so the two lines
     cannot drift into saying different things about the same person.

     Expects: $user. Optional: $isCoFarmer, $followers, and $fallback (shown
     when there is nothing else to say — the wall uses "member since"). --}}
@php
    $afPlace = trim(implode(', ', array_filter([optional($user)->city, optional($user)->province])));
    $afWork = trim((string) (optional($user)->profession ?: optional($user)->headline));
    $afFollowers = (int) ($followers ?? 0);
    $afMates = (int) ($coFarmers ?? 0);
    $afMutual = (int) ($mutual ?? 0);
    $afMate = (bool) ($isCoFarmer ?? false);
    $afAny = $afPlace !== '' || $afWork !== '' || $afFollowers > 0 || $afMates > 0 || $afMate;
@endphp
@if ($afAny)
    <p class="af-line">
        @if ($afMate)<span class="af-mate">🤝 Co-farmer</span>@endif
        @if ($afPlace)<span class="af-fact">📍 {{ $afPlace }}</span>@endif
        @if ($afWork)<span class="af-fact">🧑‍🌾 {{ \Illuminate\Support\Str::limit($afWork, 34) }}</span>@endif
        @if ($afMates > 0)
            {{-- Who they work with, and how much of that you already share.
                 The mutual number is the one that means something to the
                 reader, so it travels with the total rather than replacing
                 it. --}}
            {{-- "farms with 12" rather than "12 co-farmers": the badge to its
                 left already says co-farmer, and the line should not say the
                 same word twice about two different things. --}}
            <span class="af-fact">farms with <b>{{ $afMates }}</b>@if ($afMutual > 0) · <b>{{ $afMutual }}</b> mutual @endif</span>
        @endif
        @if ($afFollowers > 0)
            <span class="af-fact"><b>{{ $afFollowers }}</b> {{ \Illuminate\Support\Str::plural('follower', $afFollowers) }}</span>
        @endif
    </p>
@elseif (filled($fallback ?? null))
    {{-- Something under the name either way: an empty line tells a stranger
         nothing twice. --}}
    <p class="af-line"><span class="af-fact">{{ $fallback }}</span></p>
@endif
