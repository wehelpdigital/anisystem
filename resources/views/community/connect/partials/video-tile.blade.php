{{-- One profile-album video tile (already compressed to ≤720p).
     Expects: $item = ['url', 'poster', 'deletable', 'deleteId'].
     Album videos are deletable by the owner; wall-sourced videos are read-only. --}}
<div class="profile-video-tile" @if (! empty($item['deleteId'])) data-video-id="{{ $item['deleteId'] }}" @endif>
    <video class="profile-video-el" preload="none" playsinline controls
           @if (! empty($item['poster'])) poster="{{ $item['poster'] }}" @endif
           src="{{ $item['url'] }}"></video>
    @if (! empty($item['deletable']) && ! empty($item['deleteId']))
        <button type="button" class="profile-video-del js-video-delete" data-video-id="{{ $item['deleteId'] }}" aria-label="Delete video" title="Delete">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 6h18M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6M8 6V4a2 2 0 012-2h4a2 2 0 012 2v2M10 11v6M14 11v6"/></svg>
        </button>
    @endif
</div>
