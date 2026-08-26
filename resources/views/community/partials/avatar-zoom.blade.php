{{-- Tap a member's photo to actually see it.

     An avatar is drawn at 2.5rem and a face at 2.5rem is a guess. On the
     pages that include this, tapping any avatar that HAS a photo opens it
     large in the middle of the screen — still round, because that is the
     shape the face has everywhere else — over its own dimmed backdrop.
     Initial-letter avatars keep their old behaviour (usually a link to the
     profile): there is nothing bigger to show of two letters.

     Delegated and guarded, so it works on cards that arrive by fetch and
     can be included by any page that draws members. --}}
@once
<style>
    .avz { position: fixed; inset: 0; z-index: 230; display: flex; flex-direction: column;
        align-items: center; justify-content: center; gap: 1rem;
        background: rgb(8 12 5 / .8); -webkit-backdrop-filter: blur(3px); backdrop-filter: blur(3px);
        opacity: 0; pointer-events: none; cursor: zoom-out;
        transition: opacity .28s cubic-bezier(.22, 1, .36, 1); }
    .avz.is-open { opacity: 1; pointer-events: auto; }
    .avz-pic { width: min(78vw, 21rem); height: min(78vw, 21rem); border-radius: 999px;
        object-fit: cover; background: #202a1b;
        box-shadow: 0 30px 90px rgb(0 0 0 / .55), 0 0 0 5px rgb(255 255 255 / .16);
        transform: scale(.82); transition: transform .34s cubic-bezier(.22, 1, .36, 1); }
    .avz.is-open .avz-pic { transform: scale(1); }
    .avz-name { color: #fff; font-weight: 700; font-size: .95rem; letter-spacing: .01em;
        text-shadow: 0 1px 6px rgb(0 0 0 / .5); }
    .avz-x { position: absolute; top: max(1rem, env(safe-area-inset-top)); right: 1rem;
        width: 2.5rem; height: 2.5rem; border: 0; border-radius: 999px; cursor: pointer;
        background: rgb(255 255 255 / .14); color: #fff; font-size: 1.05rem;
        display: inline-flex; align-items: center; justify-content: center;
        transition: background .28s cubic-bezier(.22, 1, .36, 1); }
    .avz-x:hover { background: rgb(255 255 255 / .26); }
    @media (prefers-reduced-motion: reduce) { .avz, .avz-pic { transition-duration: .01s; } }
</style>
<div class="avz" id="avatarZoom" role="dialog" aria-modal="true" aria-label="Profile photo">
    <button type="button" class="avz-x" aria-label="Close">✕</button>
    <img class="avz-pic" id="avatarZoomPic" src="" alt="">
    <p class="avz-name" id="avatarZoomName"></p>
</div>
<script>
(function () {
    if (window.__avatarZoomBound) return;
    window.__avatarZoomBound = true;

    const box = document.getElementById('avatarZoom');
    const pic = document.getElementById('avatarZoomPic');
    const name = document.getElementById('avatarZoomName');

    function close() {
        box.classList.remove('is-open');
        document.removeEventListener('keydown', onKey);
    }
    function onKey(e) { if (e.key === 'Escape') close(); }

    document.addEventListener('click', (e) => {
        // Anywhere on the open viewer closes it — it is a look, not a place.
        if (box.classList.contains('is-open') && e.target.closest('#avatarZoom')) {
            e.preventDefault(); close(); return;
        }
        const face = e.target.closest('.avatar');
        if (!face) return;
        const img = face.querySelector('img');
        if (!img || !img.getAttribute('src')) return;   // letters stay letters
        // The photo outranks the link the avatar may be wrapped in: the name
        // beside it still goes to the profile, the face shows the face.
        e.preventDefault();
        e.stopPropagation();
        /* Sized to what the file can honestly fill. A 512px avatar drawn at
         * 670 device pixels is mush; the circle grows no further than the
         * source's short side (the cover-crop's real resolution) plus a 40%
         * grace, and never below a readable floor. Sharp-and-smaller beats
         * big-and-blurry. */
        pic.style.width = pic.style.height = '';
        pic.onload = () => {
            const dpr = window.devicePixelRatio || 1;
            const native = Math.min(pic.naturalWidth || 0, pic.naturalHeight || 0);
            if (!native) return;
            const cap = Math.min(336, window.innerWidth * 0.78);
            const size = Math.max(176, Math.min(cap, (native / dpr) * 1.4));
            pic.style.width = pic.style.height = Math.round(size) + 'px';
        };
        pic.src = img.currentSrc || img.src;
        if (pic.complete) pic.onload();
        pic.alt = img.alt || '';
        name.textContent = img.alt || face.getAttribute('title') || '';
        box.classList.add('is-open');
        document.addEventListener('keydown', onKey);
    });
})();
</script>
@endonce
