{{-- Cards alone, for the co-farmers pager — the same member card the page
     itself draws, so a scrolled-in row cannot look like a different app. --}}
@include('community.connect.partials.members', ['members' => $friends])
