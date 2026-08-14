{{-- One "People you may know" card. Expects: $u (with recoReason, connStatus). --}}
<div class="reco-card card p-3 text-center shrink-0" data-member-card="{{ $u->id }}" style="width:11.5rem;">
    <a href="{{ route('community.connect.profile', ['userId' => $u->id]) }}" class="block">
        <span class="flex justify-center">@include('community.partials.avatar', ['user' => $u, 'size' => 'avatar-lg', 'link' => false, 'showOnline' => true])</span>
        <span class="block font-semibold text-gray-900 truncate mt-2">{{ $u->full_name }}</span>
    </a>
    <span class="block text-[0.688rem] text-brand-700 font-semibold truncate mt-0.5" title="{{ $u->recoReason }}">{{ $u->recoReason }}</span>
    <div class="mt-2 flex justify-center">
        @include('community.connect.partials.action', ['status' => $u->connStatus, 'memberId' => $u->id])
    </div>
</div>
