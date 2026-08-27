{{-- One "People you may know" card, for the wall's suggestion strip and the
     members page. Expects: $u (with recoReason, connStatus). Optional:
     $following — the strip knows, older callers do not. --}}
{{-- Width, spacing and button shape all come from .reco-card in plaza-css,
     so this card looks the same wherever it is dealt out. --}}
<div class="reco-card card" data-member-card="{{ $u->id }}">
    {{-- Their cover with the face standing on it, or — until they have set
         one — the house green, turning slowly. A card that looks like the
         person it is about, rather than a name over two buttons. --}}
    <span class="reco-top" aria-hidden="true">
        @if ($u->coverPath)
            {{-- A cover whose file has gone leaves the browser's broken-image
                 glyph across the top of the card; taking it out leaves the
                 green, which is what a member without one gets anyway. --}}
            <img src="{{ \App\Support\MediaStore::url($u->coverPath) }}" alt="" loading="lazy"
                 onerror="this.remove()">
        @endif
    </span>
    <a href="{{ route('community.connect.profile', ['userId' => $u->id]) }}" class="reco-who">
        <span class="reco-face">@include('community.partials.avatar', ['user' => $u, 'size' => 'avatar-md', 'link' => false, 'showOnline' => true])</span>
        <span class="reco-name">{{ $u->full_name }}</span>
        @include('community.partials.top-badge', ['topUser' => $u, 'topFlat' => true])
    </a>
    {{-- Only when there is something to say. A suggestion with no reason
         behind it — somebody topped up to fill the strip — would otherwise
         carry a blank line and sit taller than its neighbours. --}}
    @if (filled($u->recoReason ?? null))
        <span class="reco-why" title="{{ $u->recoReason }}">{{ $u->recoReason }}</span>
    @endif
    {{-- Pushed to the bottom, so every card in the row ends level whatever
         its reason line did. --}}
    <div class="reco-acts">
        @include('community.connect.partials.action', ['status' => $u->connStatus, 'memberId' => $u->id])
        {{-- Following costs the other person nothing and needs no permission,
             so it sits under Connect as the lighter of the two gestures. --}}
        <button type="button" class="fp-follow reco-follow {{ ($following ?? false) ? 'is-on' : '' }}"
                data-follow="{{ $u->id }}" data-name="{{ $u->full_name }}"
                aria-pressed="{{ ($following ?? false) ? 'true' : 'false' }}">
            <span class="on">Following</span><span class="off">+ Follow</span>
        </button>
    </div>
</div>
