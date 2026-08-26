{{-- One profile-album video card, cut like the gallery picker's clip tiles:
     a square film with a clapperboard pill, and its words on their own line
     below. Expects: $item = ['url', 'poster', 'deletable', 'deleteId',
     'title', 'source', 'ts'] (title/source/ts optional for older callers).
     Album videos are deletable by the owner; wall-sourced videos are
     read-only. --}}
@php
    $pvtName = $item['title'] ?? 'Video';
    if ($pvtName === 'Video') { $pvtName = 'Profile video'; }
    $pvtWhen = ! empty($item['ts']) ? \Illuminate\Support\Carbon::parse($item['ts'])->format('M j, Y') : null;
    $pvtSub = implode(' · ', array_filter([$item['source'] ?? null, $pvtWhen]));
@endphp
<div class="profile-video-tile" @if (! empty($item['deleteId'])) data-video-id="{{ $item['deleteId'] }}" @endif>
    <span class="pvt-shot" @if (! empty($item['poster'])) style="background-image:url('{{ $item['poster'] }}')" @endif>
        {{-- The poster is painted as the shot's own background: an unplayed
             preload=none video renders transparent, so the frame shows
             through, and pressing play paints the film over it. A clip that
             never had a poster cut preloads metadata with a #t=0.5
             fragment instead — a frame from half a second in, not the
             (often black) opening frame. --}}
        <video class="profile-video-el" playsinline controls
               @if (! empty($item['poster']))
                   preload="none" poster="{{ $item['poster'] }}" src="{{ $item['url'] }}"
               @else
                   preload="metadata" src="{{ $item['url'] }}#t=0.5"
               @endif></video>
        <span class="pvt-badge" aria-hidden="true">🎬 Clip</span>
    </span>
    @if (! empty($item['deletable']) && ! empty($item['deleteId']))
        <button type="button" class="profile-video-del js-video-delete" data-video-id="{{ $item['deleteId'] }}" aria-label="Delete video" title="Delete">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 6h18M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6M8 6V4a2 2 0 012-2h4a2 2 0 012 2v2M10 11v6M14 11v6"/></svg>
        </button>
    @endif
    <span class="pvt-meta">
        <span class="pvt-name">{{ $pvtName }}</span>
        @if ($pvtSub !== '')
            <span class="pvt-sub">{{ $pvtSub }}</span>
        @endif
    </span>
</div>
