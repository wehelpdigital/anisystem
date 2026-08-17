{{-- Plan cards alone, for the browse pager. --}}
@foreach ($plans as $plan)
    @include('community.partials.plan-card-row', ['plan' => $plan])
@endforeach
