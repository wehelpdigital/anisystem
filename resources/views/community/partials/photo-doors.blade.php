@once
{{-- THE THREE DOORS A PICTURE COMES THROUGH.

     The camera, this phone, or the gallery the seasons already keep. The same
     three the composer, the messenger and the notes offer, in the same order,
     because a farmer who has learned them once should not have to learn them
     again on a different screen.

     Used by:  window.photoDoors({ title, onPick })
     onPick is handed either { file } — something to upload — or
     { path, url } — something already in the gallery, which travels as a
     path and never as bytes. Both endpoints in this app accept both, so the
     caller only has to know which of the two it got.

     One sheet and one pair of inputs for the whole page, reassigned per call:
     a page with four picture wells does not need four copies of a camera. --}}
<style>
    .pd-src { display: flex; align-items: center; gap: .75rem; width: 100%; padding: .7rem .8rem;
        border-radius: .9rem; text-align: left; font-weight: 700; font-size: .9rem;
        color: var(--color-gray-800); background: transparent; border: 0; cursor: pointer;
        transition: background .28s cubic-bezier(.22,1,.36,1); }
    .pd-src:hover { background: var(--color-gray-100); }
    .pd-src .ic { width: 2.4rem; height: 2.4rem; border-radius: .8rem; flex-shrink: 0;
        display: flex; align-items: center; justify-content: center;
        background: var(--color-brand-50); color: var(--color-brand-600); }
    .pd-src .ic svg { width: 1.25rem; height: 1.25rem; }
    .pd-src .sub { display: block; font-size: .72rem; font-weight: 600; color: var(--color-gray-400); }
    html.dark .pd-src { color: #e2e8f0; }
    html.dark .pd-src:hover { background: rgb(255 255 255 / .06); }
    html.dark .pd-src .ic { background: rgb(107 159 61 / .18); color: #a8cc7e; }
    @media (prefers-reduced-motion: reduce) { .pd-src { transition: none; } }
</style>

<div class="sheet hidden" id="photoDoorSheet" style="--sheet-width:24rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title" id="photoDoorTitle">Add a photo</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body space-y-1">
        <button type="button" class="pd-src" data-pd="cam">
            <span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg></span>
            <span>Take a photo<span class="sub">Use the camera now</span></span>
        </button>
        <button type="button" class="pd-src" data-pd="file">
            <span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg></span>
            <span>From this phone<span class="sub">Pick a picture you already have</span></span>
        </button>
        <button type="button" class="pd-src" data-pd="gallery">
            <span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 7h3l2-3h6l2 3h3v13H4V7z"/><path stroke-linecap="round" stroke-linejoin="round" d="M8 13l2.5-2.5L14 14l2-2 2 2"/></svg></span>
            <span>From the anee.io gallery<span class="sub">A photo your seasons already keep</span></span>
        </button>
    </div>
</div>
<input type="file" id="photoDoorFile" accept="image/jpeg,image/png,image/webp" class="hidden">
<input type="file" id="photoDoorCam" accept="image/*" capture="environment" class="hidden">

{{-- The gallery sheet the third door opens. @once inside it, so including it
     from more than one place on a page is safe. --}}
@include('sm.partials.media-picker')

<script>
(function photoDoors() {
    'use strict';

    const sheet = document.getElementById('photoDoorSheet');
    if (!sheet) return;

    const fileIn = document.getElementById('photoDoorFile');
    const camIn = document.getElementById('photoDoorCam');
    const title = document.getElementById('photoDoorTitle');

    /* Who asked. Held here rather than passed through the DOM, and cleared
       the moment it is used: a stale handler would drop the next picture
       into whichever well was last opened, which is the kind of bug that
       only shows up when somebody changes their mind. */
    let waiting = null;

    const done = (result) => {
        const cb = waiting;
        waiting = null;
        if (cb && result) cb(result);
    };

    const took = (input) => {
        const f = input.files && input.files[0];
        input.value = '';
        if (f) done({ file: f, url: URL.createObjectURL(f) });
    };
    fileIn?.addEventListener('change', () => took(fileIn));
    camIn?.addEventListener('change', () => took(camIn));

    /* Delegated, not bound at parse time: this partial is included wherever
       it is needed and the rows are inside it, but a listener per row is a
       listener that has to survive whatever else the page does to its sheets. */
    sheet.addEventListener('click', (e) => {
        const row = e.target.closest('.pd-src');
        if (!row) return;
        const which = row.getAttribute('data-pd');
        window.closeSheet && window.closeSheet('photoDoorSheet');

        if (which === 'cam') { camIn?.click(); return; }
        if (which === 'file') { fileIn?.click(); return; }

        // The gallery every season keeps. A farmer choosing a room's picture
        // is remembering a photo, not a schedule, so it is asked across all
        // of them at once.
        if (!window.smPickMedia) {
            window.toast?.('The gallery is not available here.', 'error');
            waiting = null;
            return;
        }
        window.smPickMedia({
            allSchedules: true,
            kinds: 'image',
            title: 'Choose from your gallery',
            onPick: (item) => { if (item) done({ path: item.path, url: item.url }); },
        });
    });

    /* Nothing is cleared when the sheet closes without a choice. A file
       chooser leaves no trace in the page, so "cancelled" and "the chooser
       is open" look identical from here — and clearing on the wrong one of
       those would throw away the pick somebody is in the middle of making.
       Every call overwrites `waiting` on its way in, so a walked-away-from
       ask costs nothing but a variable holding a stale function. */

    /**
     * Ask where a picture is coming from.
     *
     * @param opts.title  what the sheet is headed
     * @param opts.onPick handed { file, url } or { path, url }
     */
    window.photoDoors = function (opts) {
        waiting = (opts && opts.onPick) || null;
        if (title) title.textContent = (opts && opts.title) || 'Add a photo';
        window.openSheet && window.openSheet('photoDoorSheet');
    };
})();
</script>
@endonce
