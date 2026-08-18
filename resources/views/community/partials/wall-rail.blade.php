{{-- The wall's rail, kept in one place because it is drawn twice: as the
     sticky right column on desktop, and once inside the feed on phones, where
     there is no column for it to sit in.

     Expects: $railGroups, $railGroupsAreMine, $railArticles, $sponsors and
     $withRequests — the requests card rides the rail on desktop only, because
     on a phone the same requests already announce themselves above the
     composer (they are the one item that goes stale while unread). When true,
     $friendRequests and $friendRequestCount come with it. --}}
@if ($withRequests ?? false)
    @include('community.partials.side-requests', ['requests' => $friendRequests, 'requestCount' => $friendRequestCount])
@endif
@include('community.partials.side-groups', ['groups' => $railGroups, 'mine' => $railGroupsAreMine])
@include('community.partials.side-blog', ['articles' => $railArticles])
@if (($sponsors ?? collect())->isNotEmpty())
    @include('community.partials.side-sponsors', ['sponsors' => $sponsors])
@endif
