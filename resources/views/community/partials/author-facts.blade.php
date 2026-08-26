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
@endphp
@if ($afAny)
    {{-- Where they farm and what they do: what the person IS. --}}
    @if ($afPlace || $afWork)
        <p class="af-line">
            @if ($afPlace)<span class="af-fact">📍 {{ $afPlace }}</span>@endif
            @if ($afWork)<span class="af-fact">🧑‍🌾 {{ \Illuminate\Support\Str::limit($afWork, 34) }}</span>@endif
        </p>
    @endif
    {{-- Then their standing: who they farm with, and how many listen. Each
         count names what it counts — a reader should not have to work out
         what a number is about. --}}
    @if ($afMine || $afMate || $afMates > 0 || $afMutual > 0 || $afFollowers > 0)
        <p class="af-line af-counts">
            @if ($afMine)<span class="af-mate af-mine">🙋 Your account</span>@endif
            @if ($afMate)<span class="af-mate">🤝 Co-farmer</span>@endif
            @if ($afMates > 0)
                <span class="af-fact"><b>{{ $afMates }}</b> {{ \Illuminate\Support\Str::plural('co-farmer', $afMates) }}</span>
            @endif
            @if ($afMutual > 0)
                {{-- The number is a door: tap it and the shared faces slide
                     up (community.partials.mutual-js, included by the pages
                     that draw cards). --}}
                <button type="button" class="af-fact js-mutual" data-mutual-user="{{ $user->id }}"
                        data-mutual-name="{{ $user->firstName }}"><b>{{ $afMutual }}</b> mutual {{ \Illuminate\Support\Str::plural('co-farmer', $afMutual) }}</button>
            @endif
            @if ($afFollowers > 0)
                <span class="af-fact"><b>{{ $afFollowers }}</b> {{ \Illuminate\Support\Str::plural('follower', $afFollowers) }}</span>
            @endif
        </p>
    @endif
@elseif (filled($fallback ?? null))
    {{-- Something under the name either way: an empty line tells a stranger
         nothing twice. --}}
    <p class="af-line"><span class="af-fact">{{ $fallback }}</span></p>
@endif
