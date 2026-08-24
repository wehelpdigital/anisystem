{{-- Wall comment composer: text + photo + emoji + send. Expects: $postId. --}}
<form class="wall-comment-form flex flex-wrap items-center gap-2 mt-2" data-post-id="{{ $postId }}">
    <span class="reply-shell">
        <input type="text" placeholder="Write a comment…" maxlength="2000">
        <button type="button" class="emoji-btn js-comment-photo" aria-label="Attach a photo" title="Photo">
            <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
        </button>
        {{-- The composer's own accept list, not "image/*".
             On a phone those two are not the same door: image/* hands the tap
             to the chooser, which can land on an app that returns one picture
             however many you tick, while a list of real types opens the photo
             picker that honours "several". The server takes exactly these
             three anyway, so the narrower list is also the truer one. --}}
        <input type="file" class="js-comment-file hidden" accept="image/jpeg,image/png,image/webp" multiple>
        <button type="button" class="emoji-btn js-video-attach" aria-label="Upload a video" title="Video">
            <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
        </button>
        <button type="button" class="emoji-btn js-video-record" aria-label="Record a video" title="Record">
            <svg class="w-5 h-5 text-red-500" fill="none" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2"/><circle cx="12" cy="12" r="4.5" fill="currentColor"/></svg>
        </button>
        <input type="file" class="js-video-file hidden" accept="video/*">
        <button type="button" class="emoji-btn js-emoji-btn" aria-label="Add an emoji" title="Emoji">
            <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </button>
        <button type="submit" class="reply-send" aria-label="Send">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14m0 0l-6-6m6 6l-6 6"/></svg>
        </button>
    </span>
    <span class="comment-shots js-comment-shots hidden"></span>
    <span class="attach-chip hidden js-comment-chip"><span class="js-chip-name"></span><button type="button" class="js-chip-clear" aria-label="Remove photo">✕</button></span>
    <span class="js-video-chip attach-chip items-center gap-1 text-xs font-semibold text-gray-600" style="display:none"><span class="js-video-name"></span><button type="button" class="js-video-clear text-red-600 font-bold" aria-label="Remove video">✕</button></span>
</form>
