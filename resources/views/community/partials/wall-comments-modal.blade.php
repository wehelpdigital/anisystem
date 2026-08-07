{{-- "View all comments" modal — a wall-post-shaped shell so the shared
     delegated comment/reply/react/photo/emoji handlers all work inside it.
     Include once per page that needs the full-thread modal. --}}
<div id="wallCommentsModal" class="plaza-modal hidden" role="dialog" aria-modal="true" aria-label="Comments">
    <div class="plaza-modal-backdrop" data-close-modal></div>
    <div class="plaza-modal-card wall-post" id="wallCommentsModalPost" data-post-id="">
        <div class="plaza-modal-head">
            <p class="font-bold text-gray-900">Comments</p>
            <button type="button" class="btn-ghost rounded-full w-8 h-8 flex items-center justify-center text-gray-400 hover:text-gray-700" data-close-modal aria-label="Close">✕</button>
        </div>
        <div class="plaza-modal-body">
            <div class="wall-comments space-y-1.5"></div>
        </div>
        <div class="plaza-modal-foot">
            {{-- The post id is set on open by wall-comment-js. --}}
            @include('community.partials.wall-comment-form', ['postId' => ''])
        </div>
    </div>
</div>
