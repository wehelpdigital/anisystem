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
    // Your own post: "mutual co-farmers with yourself" is not a number that
    // means anything, and the badge should say who this is instead.
    $afMine = optional($user)->id && (int) $user->id === (int) auth()->id();
    if ($afMine) {
        $afMutual = 0;
        $afMate = false;
    }
    $afAny = $afMine || $afPlace !== '' || $afWork !== '' || $afFollowers > 0 || $afMates > 0 || $afMate;
    /* One chip does not deserve a line of its own.
     *
     * A member with nothing but "1 follower" was given a whole second row
     * for it, which reads as a paragraph break between two half-empty
     * lines. When exactly one chip would be drawn — and there is a line
     * above for it to join — it goes up there beside the place and the
     * work instead. Opt-in, because the wall's posts want their standing
     * on its own line whatever it says. */
    $afChips = ($afMine ? 1 : 0) + ($afMate ? 1 : 0) + ($afMates > 0 ? 1 : 0)
        + ($afMutual > 0 ? 1 : 0) + ($afFollowers > 0 ? 1 : 0);
    $afMerge = ($mergeSingleCount ?? false) && $afChips === 1 && ($afPlace !== '' || $afWork !== '');
    $afCountVars = compact('user', 'afMine', 'afMate', 'afMates', 'afMutual', 'afFollowers');
@endphp
@if ($afAny)
    {{-- Where they farm and what they do: what the person IS. --}}
    @if ($afPlace || $afWork)
        <p class="af-line">
            @if ($afPlace)<span class="af-fact">📍 {{ $afPlace }}</span>@endif
            @if ($afWork)<span class="af-fact">🧑‍🌾 {{ \Illuminate\Support\Str::limit($afWork, 34) }}</span>@endif
            {{-- A lone chip rides up here rather than opening a second row. --}}
            @if ($afMerge)@include('community.partials.author-counts', $afCountVars)@endif
        </p>
    @endif
    {{-- Then their standing: who they farm with, and how many listen. Each
         count names what it counts — a reader should not have to work out
         what a number is about. --}}
    @if (! $afMerge && $afChips > 0)
        <p class="af-line af-counts">
            @include('community.partials.author-counts', $afCountVars)
        </p>
    @endif
@elseif (filled($fallback ?? null))
    {{-- Something under the name either way: an empty line tells a stranger
         nothing twice. --}}
    <p class="af-line"><span class="af-fact">{{ $fallback }}</span></p>
@endif
