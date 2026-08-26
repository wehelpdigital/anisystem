{{-- One shared co-farmer in the mutual list: face, name, where they farm,
     and the way to their profile. Expects: $user. --}}
<a class="mut-row" href="{{ route('community.connect.profile', ['userId' => $user->id]) }}">
    @include('community.partials.avatar', ['user' => $user, 'size' => 'avatar-md', 'link' => false])
    <span class="mut-row-mid">
        <b>{{ $user->full_name }}</b>
        @php $mutPlace = trim(implode(', ', array_filter([$user->city, $user->province]))); @endphp
        @if ($mutPlace)<i>📍 {{ $mutPlace }}</i>@endif
    </span>
    <svg class="mut-row-go" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
</a>
