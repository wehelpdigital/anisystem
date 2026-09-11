{{-- The wall's rail, kept in one place because it is drawn twice: as the
     sticky right column on desktop, and once inside the feed on phones, where
     there is no column for it to sit in.

     The owner took "Your discussions" and "New in the blog" out of the rail —
     both now ride IN the wall as cards, where they are actually read. What is
     left is what belongs beside the feed rather than in it: people waiting on
     an answer from you, and whatever is sponsored.

     Expects: $sponsors and $withRequests — the requests card rides the rail on
     desktop only, because on a phone the same requests already announce
     themselves above the composer. When true, $friendRequests and
     $friendRequestCount come with it. --}}
{{-- Nobody is waiting: the card goes, rather than standing there to say so.
     A heading with "walang bagong request" under it is a box that reports its
     own emptiness every day the reader has no requests, which is most days. --}}
@if (($withRequests ?? false) && (int) ($friendRequestCount ?? 0) > 0)
    @include('community.partials.side-requests', ['requests' => $friendRequests, 'requestCount' => $friendRequestCount])
@endif
@if (($sponsors ?? collect())->isNotEmpty())
    @include('community.partials.side-sponsors', ['sponsors' => $sponsors])
@endif
