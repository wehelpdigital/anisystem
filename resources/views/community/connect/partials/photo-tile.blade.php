{{-- One profile-album photo tile. Expects: $item = ['url', 'deletable', 'deleteId'].
     Album photos are deletable by the owner; wall-sourced photos are read-only. --}}
<div class="profile-photo-tile" @if (! empty($item['deleteId'])) data-photo-id="{{ $item['deleteId'] }}" @endif>
    <img src="{{ $item['url'] }}" alt="Photo" loading="lazy" data-lightbox>
    @if (! empty($item['deletable']) && ! empty($item['deleteId']))
        <button type="button" class="profile-photo-del js-photo-delete" data-photo-id="{{ $item['deleteId'] }}" aria-label="Delete photo" title="Delete">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.9 12.1a2 2 0 01-2 1.9H7.9a2 2 0 01-2-1.9L5 7m3 0V5a2 2 0 012-2h4a2 2 0 012 2v2m-11 0h16"/></svg>
        </button>
    @endif
</div>
