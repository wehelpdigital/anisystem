{{-- One "People you may know" card, for the wall's suggestion strip and the
     members page. Expects: $u (with recoReason, connStatus). Optional:
     $following — the strip knows, older callers do not. --}}
<div class="reco-card card p-3 text-center shrink-0" data-member-card="{{ $u->id }}" style="width:11.5rem;">
    <a href="{{ route('community.connect.profile', ['userId' => $u->id]) }}" class="block">
        <span class="flex justify-center">@include('community.partials.avatar', ['user' => $u, 'size' => 'avatar-lg', 'link' => false, 'showOnline' => true])</span>
        <span class="block font-semibold text-gray-900 truncate mt-2">{{ $u->full_name }}</span>
    </a>
    {{-- Only when there is something to say. A suggestion with no reason
         behind it — somebody topped up to fill the strip — would otherwise
         carry a blank line and sit taller than its neighbours. --}}
    @if (filled($u->recoReason ?? null))
        <span class="block text-[0.688rem] text-brand-700 font-semibold truncate mt-0.5" title="{{ $u->recoReason }}">{{ $u->recoReason }}</span>
    @endif
    <div class="mt-2 flex flex-col gap-1.5 items-stretch">
        @include('community.connect.partials.action', ['status' => $u->connStatus, 'memberId' => $u->id])
        {{-- Following costs the other person nothing and needs no permission,
             so it sits under Connect as the lighter of the two gestures. --}}
        <button type="button" class="fp-follow w-full {{ ($following ?? false) ? 'is-on' : '' }}"
                data-follow="{{ $u->id }}" data-name="{{ $u->full_name }}"
                aria-pressed="{{ ($following ?? false) ? 'true' : 'false' }}">
            <span class="on">Following</span><span class="off">+ Follow</span>
        </button>
    </div>
</div>
