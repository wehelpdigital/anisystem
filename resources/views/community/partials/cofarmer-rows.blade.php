{{-- Cards alone, for the co-farmers pager. --}}
@foreach ($friends as $friend)
    @include('community.partials.cofarmer-card', ['friend' => $friend, 'latestPosts' => $latestPosts])
@endforeach
