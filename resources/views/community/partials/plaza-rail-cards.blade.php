{{-- THE COMMUNITY'S DESKTOP RAIL — the cards.

     What goes in the column beside every community page on a wide screen:
     cards chosen per page so the column is company for what you are
     reading rather than a copy of it. The wall does not need a card of its
     own posts; the discussions page does not need a card of rooms. Fetched
     by plaza-rail (the slot) through community.rail, only where the column
     is on screen, so a phone never asks. The answers come from
     App\Support\CommunityRail, so no page's controller has to know the
     rail exists.

     Expects: $rail — the cards, in order, from:
       requests     co-farmer requests waiting on you (only when there are any)
       chats        the people you last spoke with
       discussions  rooms where people are talking now
       posts        a few of what the wall is saying, drawn at random
       blog         what is new in the Technician's Blog
       sponsors     whatever is sponsored (only when there is any) --}}
@php
    $railData = new \App\Support\CommunityRail();
    $rail = $rail ?? ['chats', 'discussions', 'blog'];
@endphp
@foreach ($rail as $card)
    @if ($card === 'requests')
        @php [$railRequests, $railRequestCount] = $railData->requests(); @endphp
        @if ($railRequestCount > 0)
            @include('community.partials.side-requests', ['requests' => $railRequests, 'requestCount' => $railRequestCount])
        @endif
    @elseif ($card === 'chats')
        @include('community.partials.side-chats', ['chats' => $railData->recentChats()])
    @elseif ($card === 'discussions')
        @include('community.partials.side-groups', ['groups' => $railData->latestDiscussions(), 'mine' => true, 'title' => 'Latest Discussions'])
    @elseif ($card === 'posts')
        @include('community.partials.side-posts', ['posts' => $railData->recentPosts()])
    @elseif ($card === 'blog')
        @include('community.partials.side-blog', ['articles' => $railData->newInBlog()])
    @elseif ($card === 'sponsors')
        @if (($sponsors ?? collect())->isNotEmpty())
            @include('community.partials.side-sponsors', ['sponsors' => $sponsors])
        @endif
    @endif
@endforeach
