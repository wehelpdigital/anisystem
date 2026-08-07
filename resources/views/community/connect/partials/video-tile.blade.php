{{-- One profile-album video tile (already compressed to ≤720p).
     Expects: $item = ['url', 'poster', 'deletable', 'deleteId'].
     Album videos are deletable by the owner; wall-sourced videos are read-only. --}}
<div class="profile-video-tile" @if (! empty($item['deleteId'])) data-video-id="{{ $item['deleteId'] }}" @endif>
    <video class="profile-video-el" preload="none" playsinline controls
           @if (! empty($item['poster'])) poster="{{ $item['poster'] }}" @endif
           src="{{ $item['url'] }}"></video>
    @if (! empty($item['deletable']) && ! empty($item['deleteId']))
        <button type="button" class="profile-video-del js-video-delete" data-video-id="{{ $item['deleteId'] }}" aria-label="Delete video" title="Delete">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.9 12.1a2 2 0 01-2 1.9H7.9a2 2 0 01-2-1.9L5 7m3 0V5a2 2 0 012-2h4a2 2 0 012 2v2m-11 0h16"/></svg>
        </button>
    @endif
</div>
