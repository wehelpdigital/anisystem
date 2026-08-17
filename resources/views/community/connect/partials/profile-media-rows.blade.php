{{-- Tiles alone, for the profile media pager. Which tab is asking decides
     which paginator's current page goes out; the driver appends these
     straight into the grid the pager sits under. --}}
@if (($tab ?? 'photos') === 'videos')
    @foreach ($videos as $video)
        @include('community.connect.partials.video-tile', ['item' => $video])
    @endforeach
@else
    @foreach ($photos as $photo)
        @include('community.connect.partials.photo-tile', ['item' => $photo])
    @endforeach
@endif
