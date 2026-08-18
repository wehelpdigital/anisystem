{{-- Cards alone, for the post-harvest pager. --}}
@foreach ($observations as $o)
    @include('sm.partials.post-harvest-card', ['o' => $o, 'categories' => \App\Models\AsSchedulePostHarvest::CATEGORIES, 'schedule' => $schedule])
@endforeach
