{{-- A person's standing, as chips: whose account this is, whether you two
     already farm together, who they farm with, how much of that you share,
     and how many listen.

     Split out of author-facts so the very same chips can stand on a line of
     their own or, when there is only one of them, join the line above rather
     than start a second one for a single fact.

     Expects: $user, and the already-resolved $afMine, $afMate, $afMates,
     $afMutual, $afFollowers. --}}
@if ($afMine)<span class="af-mate af-mine">🙋 Your account</span>@endif
@if ($afMate)<span class="af-mate">🤝 Co-farmer</span>@endif
@if ($afMates > 0)
    <span class="af-fact"><b>{{ $afMates }}</b> {{ \Illuminate\Support\Str::plural('co-farmer', $afMates) }}</span>
@endif
@if ($afMutual > 0)
    {{-- The number is a door: tap it and the shared faces slide up
         (community.partials.mutual-js, included by the pages that draw
         cards). --}}
    <button type="button" class="af-fact js-mutual" data-mutual-user="{{ $user->id }}"
            data-mutual-name="{{ $user->firstName }}"><b>{{ $afMutual }}</b> mutual {{ \Illuminate\Support\Str::plural('co-farmer', $afMutual) }}</button>
@endif
@if ($afFollowers > 0)
    <span class="af-fact"><b>{{ $afFollowers }}</b> {{ \Illuminate\Support\Str::plural('follower', $afFollowers) }}</span>
@endif
