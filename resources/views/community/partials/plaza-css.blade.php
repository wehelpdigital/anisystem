{{-- "Ang Plaza" — shared community design system (avatars, reactions, emoji
     popover, join morph, composer shells, liveliness). Include once per page. --}}
<style>
    /* --- Where a thing comes from ---
     * A row per way in, each with a sentence saying what it is. The schedule
     * side of the app draws the same shape in its own words (.cph-src); this
     * is the community's copy, because a page here carries neither that
     * file's styles nor its assumptions.
     */
    .plaza-srcs { display:flex; flex-direction:column; gap:.5rem; }
    .plaza-src { display:flex; align-items:center; gap:.7rem; width:100%; text-align:left;
        padding:.7rem .8rem; border-radius:.85rem; border:1px solid var(--color-gray-200);
        background:var(--color-white); cursor:pointer;
        transition:background .28s cubic-bezier(.22,1,.36,1), border-color .28s cubic-bezier(.22,1,.36,1), transform .1s ease; }
    .plaza-src:hover { border-color:var(--color-brand-300); background:var(--color-brand-50); }
    .plaza-src:active { transform:scale(.985); }
    .plaza-src-ic { flex:0 0 auto; width:2.4rem; height:2.4rem; border-radius:.7rem;
        display:inline-flex; align-items:center; justify-content:center;
        background:var(--color-brand-100); color:var(--color-brand-800); }
    .plaza-src-ic svg { width:1.25rem; height:1.25rem; }
    .plaza-src-t { flex:1 1 auto; min-width:0; }
    .plaza-src-t b { display:block; font-size:.84rem; font-weight:800; color:var(--color-gray-800); }
    .plaza-src-t small { display:block; font-size:.7rem; color:var(--color-gray-400); margin-top:.05rem; }
    .plaza-src-go { width:1rem; height:1rem; flex:0 0 auto; color:var(--color-gray-300); }
    @media (prefers-reduced-motion: reduce) { .plaza-src { transition:none; } }
    html.dark .plaza-src { background:#1c2416; border-color:#2b3a1c; }
    html.dark .plaza-src-t b { color:#e8efe1; }

    /* --- Several pictures on one post ---
     *
     * A farmer's answer is rarely one photograph: the leaf, the whole hill,
     * and the bag of whatever they sprayed. Stacked at full width that is a
     * screen and a half of scrolling before the next post, so they are laid
     * out small in columns — masonry, so a tall photo and a wide one both
     * keep their own shape rather than being cropped square. Every one of
     * them opens whole in the lightbox, which binds to [data-lightbox] img
     * and so needs nothing here but the attribute.
     */
    /* One at a time, the way a phone reads a set of photographs — a wall of
       thumbnails made a post into a page and cropped every one of them into a
       square that answered nothing. The track snaps, so a swipe lands on a
       picture rather than between two. */
    .post-carousel { position:relative; margin-top:.6rem; }
    .pc-track { display:grid; grid-auto-flow:column; grid-auto-columns:100%;
        overflow-x:auto; scroll-snap-type:x mandatory; scroll-behavior:smooth;
        scrollbar-width:none; border-radius:.7rem; touch-action:pan-x pan-y; }
    .pc-track::-webkit-scrollbar { display:none; }
    .pc-track img { display:block; width:100%; aspect-ratio:4 / 3; object-fit:cover;
        /* start, not centre: with columns exactly a screenful wide the two
           rest in the same place, but centre leaves a hair of the next
           picture showing at the edge on a fractional width. */
        scroll-snap-align:start; scroll-snap-stop:always; cursor:zoom-in;
        background:var(--color-gray-100); border-radius:.7rem; }
    html.dark .pc-track img { background:rgb(255 255 255 / .06); }
    /* Where you are in the set, said twice: a number for the reader who wants
       to know how many, and dots for the one who only wants to feel it. */
    /* A card standing in for one that is being read in a sheet or a modal:
       it holds the space and takes no taps. */
    .is-stand-in { pointer-events:none; user-select:none; }

    /* What is attached to a comment before it is sent: small squares, each
       with its own way out. The composer's tray in miniature. */
    .comment-shots { display:flex; flex-wrap:wrap; gap:.3rem; flex:1 1 100%; margin-top:.3rem; }
    /* Room under whatever is attached.
       A comment box is the last thing in its card, and the card's bottom edge
       is a gradient line — so the thumbnails sat directly on it, looking
       stuck to it rather than inside the box. The room appears only when
       something is attached, so an empty box keeps its own tight shape. */
    .wall-comment-form:has(.js-comment-shots:not(.hidden)),
    .post-reply-form:has(.js-comment-shots:not(.hidden)),
    .wall-comment-form:has(.attach-chip:not(.hidden)),
    .post-reply-form:has(.attach-chip:not(.hidden)) { padding-bottom:.55rem; }
    .comment-shots.hidden { display:none; }
    .comment-shot { position:relative; display:inline-flex; }
    .comment-shot img, .comment-shot video { width:2.75rem; height:2.75rem; object-fit:cover;
        border-radius:.45rem; border:1px solid var(--color-gray-200); background:var(--color-gray-100);
        display:block; }
    /* A clip in the tray wears a play mark, so a wall of thumbnails says
       which of them will move when it is sent. */
    .comment-shot.is-clip .cs-play { position:absolute; inset:0; display:flex; align-items:center;
        justify-content:center; font-size:.7rem; font-style:normal; color:#fff;
        text-shadow:0 1px 3px rgb(0 0 0 / .6); pointer-events:none; }
    html.dark .comment-shot img { border-color:rgb(255 255 255 / .12); background:rgb(255 255 255 / .06); }
    .comment-shot button { position:absolute; top:-.3rem; right:-.3rem; width:1.05rem; height:1.05rem;
        display:flex; align-items:center; justify-content:center; border-radius:999px;
        font-size:.6rem; font-weight:800; line-height:1; color:#fff; background:rgb(17 24 39 / .78);
        transition:background .18s ease, transform .18s cubic-bezier(.22,1,.36,1); }
    .comment-shot button:hover { background:#dc2626; transform:scale(1.08); }
    @media (prefers-reduced-motion: reduce) { .comment-shot button { transition:none; } }

    /* The same carousel under a comment, at a comment's size: an answer
       should not out-shout the thing it is answering. */
    .post-carousel.pc-mini { max-width:20rem; margin-top:.35rem; }
    .pc-mini .pc-track img { aspect-ratio:3 / 2; }
    .pc-mini .pc-dots { margin-top:.3rem; }
    /* A slider of clips: same track, but each slide holds a player rather
       than a picture, and a player must not be cropped to a square. */
    .pc-clips .pc-track > .pc-slide { scroll-snap-align:start; scroll-snap-stop:always;
        display:flex; align-items:center; min-width:0; }
    .pc-clips .pc-track .post-video { margin-top:0; }
    /* Counted on the left: a posted clip wears the player's own fullscreen
       button in its top-right corner, and two badges in one corner is one
       badge nobody can read. */
    .pc-clips .pc-count { top:.65rem; left:.65rem; right:auto; }

    /* Room under a clip inside a comment. The bubble gives its words eight
       pixels of floor, which is fine under a line of text and pinched under
       a player whose controls sit on its bottom edge. */
    .group-reply .post-video, .wall-comment .post-video, .cp-comment .post-video,
    .group-reply .post-carousel, .wall-comment .post-carousel, .cp-comment .post-carousel { margin-bottom:.5rem; }
    /* A player shaped by its film: auto both ways so the browser keeps the
       clip's ratio, a ceiling on height so a portrait clip does not take the
       whole screen, and a box that shrinks to what is inside it — which is
       what stops any black showing at all. */
    /* A block, so a width of 100% inside it means the room on the page — an
       inline-block would have asked the film how wide to be and the film
       would have asked back. */
    .post-video { display:block; max-width:100%; line-height:0; padding-bottom:.3rem; }
    /* Sized by the film's own ratio, in both directions.
     *
     * --vr is the film's width over its height, set from its metadata before
     * anybody presses play. Width is the room available; height follows the
     * ratio. The ceiling is applied as a WIDTH — 24rem times the ratio —
     * because a max-height would clamp the height and leave the width where
     * it was, which is the letterbox this was meant to end: a portrait clip
     * comes out 13.5rem by 24rem and the box hugs it.
     *
     * Until the metadata lands the box guesses widescreen; the still is drawn
     * inside it rather than deciding it, so a poster of the wrong shape can
     * no longer set the frame. */
    /* The still fills the frame the film asked for.
     *
     * The box is the film's shape; a poster of a slightly different shape
     * left black down the sides of the resting player. Cover trims the still
     * instead — it is a frame of the film, so trimming it shows less of the
     * same picture rather than a different one, and nothing is stretched
     * because cover keeps the still's own proportions. When the film plays,
     * its shape and the box's are the same, so cover does nothing at all. */
    .post-video .post-video-el { display:block; background:#000; object-fit:cover;
        aspect-ratio:var(--vr, 1.7778); width:100%; height:auto;
        max-width:min(100%, calc(24rem * var(--vr, 1.7778))); }
    /* In a slider the slide is the full width; the film sits in the middle of
       it at its own size. */
    /* ---- A POSTED film fills the card ----
       On a post the frame is the card's width, always: a landscape film at
       its own shape, a portrait one as a full-width square with the film
       covered from the centre — cover crops the sides of the frame that do
       not fit, so nothing letterboxes and nothing stretches. Comments keep
       the smaller sized-to-the-film players; the .post-films wrapper is the
       whole difference. max() needs a browser that can do arithmetic inside
       aspect-ratio; one that cannot keeps the film's own shape from the
       line above. */
    .post-films .post-video-el { max-width:100%;
        aspect-ratio:max(var(--vr, 1.7778), 1); }
    .post-films .post-video,
    .post-films .pc-clips .pc-track .post-video { width:100%; }
    .pc-clips .pc-track .post-video { display:block; width:auto; margin-inline:auto; }

    .pc-count { position:absolute; top:.5rem; right:.5rem; pointer-events:none;
        padding:.1rem .45rem; border-radius:999px; font-size:.68rem; font-weight:800;
        color:#fff; background:rgb(17 24 39 / .55); backdrop-filter:blur(2px); }
    .pc-count b { font-weight:800; }
    .pc-dots { display:flex; justify-content:center; align-items:center; gap:.28rem; margin-top:.45rem; }
    .pc-dot { width:.36rem; height:.36rem; border-radius:999px; background:var(--color-gray-300);
        transition:width .28s cubic-bezier(.22,1,.36,1), background .28s cubic-bezier(.22,1,.36,1); }
    .pc-dot.is-on { width:1.05rem; background:var(--color-brand-600); }
    html.dark .pc-dot { background:rgb(255 255 255 / .22); }
    html.dark .pc-dot.is-on { background:var(--color-brand-400); }
    @media (prefers-reduced-motion: reduce) {
        .pc-dot { transition:none; }
        /* No smooth scroll either: the jump is the honest version of a
           movement somebody has asked not to see. */
        .pc-track { scroll-behavior:auto; }
    }

    /* --- The community's own ground ---
     *
     * The wall painted the page under it a soft field green and every other
     * room in the community stood on the app's plain grey, so walking from
     * the wall to the discussions, to the members, to the blog was walking
     * off one floor onto another. One colour, kept here because every one of
     * those pages already draws its furniture from this file. Each page says
     * it is standing on it through the layout's body-class section, which is
     * how the wall has always asked for this colour. */
    body.plaza-ground { background: #eef4e8; }
    html.dark body.plaza-ground { background: #0b140d; }

    :root { --ease-house: cubic-bezier(.22,1,.36,1); --dur: .28s; }

    /* Collapsed discussion replies: keep only the latest 3 until "View all". */
    .post-replies.is-collapsed > .group-reply:nth-last-child(n+4) { display: none; }

    /* Masonry: pack unequal-height cards with no row gaps (CSS columns). */
    .masonry-2 { column-count: 1; column-gap: 1rem; }
    @media (min-width: 640px) { .masonry-2 { column-count: 2; } }
    .masonry-2 > * { break-inside: avoid; margin-bottom: 1.25rem; width: 100%; }

    /* Group chat + members sidebar (sidebar shows on desktop only). */
    .chat-shell { display: grid; grid-template-columns: 1fr; gap: 1rem; align-items: start; }
    @media (min-width: 1024px) { .chat-shell { grid-template-columns: minmax(0, 1fr) 17rem; } }
    @keyframes chatBubbleIn { from { opacity: 0; transform: translateY(8px) scale(.97); } to { opacity: 1; transform: none; } }
    .chat-bubble-in { animation: chatBubbleIn .28s cubic-bezier(.22,1,.36,1) both; }
    @media (prefers-reduced-motion: reduce) { .chat-bubble-in { animation: none; } }

    /* Composer action buttons (Photo / Video / Record / Emoji) — colour-coded,
       icon-only, shared across every wall + discussion composer. */
    .wall-act { display: inline-flex; align-items: center; gap: .4rem; border: 0; background: transparent;
        padding: .4rem .55rem; border-radius: .6rem; font-size: .8rem; font-weight: 700;
        color: var(--color-gray-600); cursor: pointer; transition: background-color .15s ease, transform .12s ease; }
    .wall-act:hover { background: var(--color-gray-100); color: var(--color-gray-800); }
    .wall-act:active { transform: scale(.95); }
    @media (prefers-reduced-motion: reduce) { .wall-act:active { transform: none; } }

    /* Where somebody farms. The pin is sized in em, so one rule serves the
       .688rem line in a sidebar row and the 1rem one on a profile without
       either getting a mark the wrong size for its words. */
    .place-pin { display: inline-flex; align-items: center; gap: .25rem; min-width: 0; }
    .place-pin svg { width: 1.05em; height: 1.05em; flex: none; color: #e11d48; }
    html.dark .place-pin svg { color: #fb7185; }

    /* --- Initials avatars: crc32(lower(name))%8 → av-h0..7. Circle = person, squircle = place. --- */
    /* Every face in this app wears the same green ring.
       Two backgrounds in one box — the member's own hue clipped to the
       padding box, the house green clipped to the border box — which is how
       a border can hold a gradient at all, and the same trick .reco-edge
       uses. It works with the overflow:hidden every avatar carries because
       that clips its photo at the PADDING edge, so the picture sits inside
       the ring rather than under it.
       The width is a variable: a 28px face in a comment thread cannot wear
       the same ring as an 80px one on a member card without either
       disappearing or being swallowed. */
    /* The ring is DASHED, and the dashes are the gradient.
       A dashed border cannot hold a gradient — border-color:transparent shows
       the background straight through and the dashes vanish; border-image
       throws the dash style away altogether. So the dashes ARE the background:
       a repeating conic gradient whose lit wedges cycle through the house
       greens and whose gaps are transparent, clipped to the border box. The
       gaps show the page behind because the hue layer is clipped to the
       padding box — which is exactly what a dashed ring should do. */
    .avatar { display:inline-flex; align-items:center; justify-content:center; flex-shrink:0;
        border-radius:9999px; color:var(--av-fg);
        border: var(--av-ring, 2px) solid transparent;
        background:
            linear-gradient(var(--av-bg), var(--av-bg)) padding-box,
            repeating-conic-gradient(from 0deg,
                #2f5219 0deg 8deg, transparent 8deg 13deg,
                #4a7c2a 13deg 21deg, transparent 21deg 26deg,
                #6b9f3d 26deg 34deg, transparent 34deg 39deg,
                #8fc267 39deg 47deg, transparent 47deg 52deg,
                #4a7c2a 52deg 60deg, transparent 60deg 65deg) border-box;
        font-family:var(--font-heading); font-weight:800; letter-spacing:.02em;
        box-shadow: inset 0 0 0 1.5px rgb(255 255 255 / .35); user-select:none; }
    .avatar-sm { width:1.75rem; height:1.75rem; font-size:.6rem; --av-ring:1.5px; }
    .avatar-md { width:2.5rem;  height:2.5rem;  font-size:.8rem; }
    .avatar-lg { width:3.25rem; height:3.25rem; font-size:1.05rem; --av-ring:2.5px; }
    .avatar-sq { border-radius:.9rem; }
    .av-h0 { --av-bg:#e4efd4; --av-fg:#2d5016; } .av-h1 { --av-bg:#fdf0c7; --av-fg:#7a5b00; }
    .av-h2 { --av-bg:#dbeafe; --av-fg:#1e40af; } .av-h3 { --av-bg:#fde3e3; --av-fg:#9c1c1c; }
    .av-h4 { --av-bg:#ece4fb; --av-fg:#5b21b6; } .av-h5 { --av-bg:#d2f0ea; --av-fg:#0f5f54; }
    .av-h6 { --av-bg:#ffe8d1; --av-fg:#9a3f0f; } .av-h7 { --av-bg:#e2e8f0; --av-fg:#334155; }
    html.dark .av-h0 { --av-bg:#2b3a1c; --av-fg:#c9e0ad; } html.dark .av-h1 { --av-bg:#3a3212; --av-fg:#fadd6d; }
    html.dark .av-h2 { --av-bg:#1e2a44; --av-fg:#93c5fd; } html.dark .av-h3 { --av-bg:#3d1f1f; --av-fg:#fca5a5; }
    html.dark .av-h4 { --av-bg:#2c2344; --av-fg:#c4b5fd; } html.dark .av-h5 { --av-bg:#123430; --av-fg:#7dd3c4; }
    html.dark .av-h6 { --av-bg:#3d2a17; --av-fg:#fdba74; } html.dark .av-h7 { --av-bg:#252c36; --av-fg:#9aa3b2; }
    html.dark .avatar { box-shadow: inset 0 0 0 1.5px rgb(255 255 255 / .12); }

    /* --- Group place identity: color cap + hero wash (both take an av-h class).
       Both drift on the shared gradSweep tide (layout) — the cap like the hub
       tiles' side accents, the wash like a slow weather front. --- */
    .group-cap { height:6px; border-radius:1rem 1rem 0 0; opacity:.85;
        background:linear-gradient(90deg, var(--av-fg), color-mix(in srgb, var(--av-fg) 55%, var(--av-bg)) 55%, var(--av-fg));
        background-size:220% 100%; animation:gradSweep 12s ease-in-out infinite alternate; }
    html.dark .group-cap { opacity:.55; }
    .group-hero { position:relative; overflow:hidden; border-radius:inherit; }
    .group-hero::before { content:''; position:absolute; inset:0; pointer-events:none;
        background:linear-gradient(150deg, var(--av-bg) 0%, transparent 70%); opacity:.9;
        background-size:220% 220%; animation:gradSweep 14s ease-in-out infinite alternate; }
    /* The room's band is a band now, not a hairline laid over a photograph:
       at .3 it was a smudge on the picture, which is what a hairline wanted
       and a band does not. */
    html.dark .group-hero::before { opacity:.9; }
    .group-hero > * { position:relative; }
    @media (prefers-reduced-motion: reduce) { .group-cap, .group-hero::before { animation:none; } }

    /* --- Join → Open morph (group cards) --- */
    .group-join-btn { max-width:9rem; overflow:hidden; white-space:nowrap;
        transition: max-width var(--dur) var(--ease-house), padding var(--dur) var(--ease-house),
            opacity var(--dur) var(--ease-house), transform var(--dur) var(--ease-house); }
    .group-join-btn.is-morphing { max-width:0; padding-inline:0; opacity:0; transform:scale(.9); border-width:0; pointer-events:none; }
    .btn-open { transition: background-color var(--dur) var(--ease-house), color var(--dur) var(--ease-house),
            border-color var(--dur) var(--ease-house); }
    .btn-open.is-promoted { background:var(--color-brand-600); color:#fff; border-color:transparent; }
    .btn-open.is-promoted:hover { background:var(--color-brand-700); }
    .group-joined-tag:not(.hidden) { animation: joinedPop .45s var(--ease-house); }
    @keyframes joinedPop { 0%{transform:scale(.5); opacity:0} 60%{transform:scale(1.12)} 100%{transform:scale(1); opacity:1} }

    /* --- Reactions: 👍 Tama · ❤️ Salamat · 🌱 Nakatulong --- */
    .react-bar { display:flex; flex-wrap:wrap; gap:.375rem; margin-top:.625rem; }
    /* @mention + #hashtag links inside post/comment bodies. */
    .mention-link { color:var(--color-brand-700); font-weight:600; text-decoration:none; }
    .mention-link:hover { text-decoration:underline; }
    .hashtag-link { color:#2563eb; font-weight:600; text-decoration:none; }
    .hashtag-link:hover { text-decoration:underline; }
    html.dark .mention-link { color:var(--color-brand-300); }
    html.dark .hashtag-link { color:#7ab0ff; }

    /* @mention autocomplete dropdown. */
    .mention-pop { position:absolute; z-index:70; min-width:13rem; max-width:20rem;
        background:var(--color-white); border:1px solid var(--color-gray-200); border-radius:.75rem;
        box-shadow:0 12px 32px rgb(0 0 0 / .14); overflow-y:auto; max-height:15rem; }
    html.dark .mention-pop { background:#171f10; border-color:#24331a; }
    .mention-pop.hidden { display:none; }
    .mention-head { padding:.35rem .6rem .15rem; font-size:.62rem; font-weight:800;
        letter-spacing:.06em; text-transform:uppercase; color:var(--color-gray-400);
        border-top:1px solid var(--color-gray-100); margin-top:.15rem; }
    .mention-item { display:flex; align-items:center; gap:.5rem; padding:.4rem .6rem; cursor:pointer; }
    .mention-item.is-active, .mention-item:hover { background:var(--color-brand-50); }
    html.dark .mention-item.is-active, html.dark .mention-item:hover { background:rgb(74 124 42 / .2); }
    .mention-item .avatar { width:1.8rem; height:1.8rem; font-size:.7rem; }
    .mention-item .mm-name { font-size:.85rem; font-weight:600; color:var(--color-gray-800); }
    html.dark .mention-item .mm-name { color:var(--color-gray-900); }
    .mention-item .mm-badge { font-size:.58rem; font-weight:700; text-transform:uppercase; letter-spacing:.04em;
        color:var(--color-brand-700); background:var(--color-brand-50); padding:0 .3rem; border-radius:.3rem; }
    .mention-item .mm-badge-loc { color:#b45309; background:#fef3c7; }
    html.dark .mention-item .mm-badge-loc { color:#eec155; background:rgb(180 83 9 / .18); }
    .mention-item .mm-loc-pin { display:inline-flex; align-items:center; justify-content:center;
        width:1.8rem; height:1.8rem; font-size:1rem; background:#fef3c7; border-radius:999px; }
    html.dark .mention-item .mm-loc-pin { background:rgb(180 83 9 / .2); }

    /* Location links inside rendered post/comment text (📍Town, Province). */
    .location-link { color:#b45309; font-weight:700; text-decoration:none; }
    .location-link:hover { color:#92400e; }
    .location-link:hover { text-decoration:underline; }
    html.dark .location-link { color:#eec155; }

    /* Admin-restricted content placeholder. */
    .restricted-notice { display:flex; align-items:center; gap:.5rem; margin-top:.5rem; padding:.6rem .8rem;
        background:#fef2f2; border:1px solid #fecaca; border-radius:.6rem; color:#b91c1c; font-size:.82rem; font-weight:600; font-style:italic; }
    html.dark .restricted-notice { background:rgb(153 27 27 / .15); border-color:rgb(153 27 27 / .4); color:#f7a4a4; }

    /* Deep-link highlight when the bell scrolls you to a post. */
    @keyframes flashTarget { 0%,100% { box-shadow:0 0 0 0 rgba(74,124,42,0); } 15%,60% { box-shadow:0 0 0 3px rgba(74,124,42,.45); } }
    .flash-target { animation: flashTarget 2s ease; }
    @media (prefers-reduced-motion: reduce) { .flash-target { animation:none; } }
    .react-btn { display:inline-flex; align-items:center; gap:.3rem; min-height:2rem; padding:.15rem .7rem;
        border-radius:9999px; border:1.5px solid var(--color-gray-200); background:transparent;
        font-size:.8rem; font-weight:700; color:var(--color-gray-500); cursor:pointer; touch-action:manipulation;
        transition: background-color var(--dur) var(--ease-house), border-color var(--dur) var(--ease-house),
            color var(--dur) var(--ease-house), transform .15s var(--ease-house); }
    .react-btn:hover { background:var(--color-brand-50); }
    .react-btn:active { transform:scale(.94); }
    .react-btn .e { font-size:1rem; line-height:1; }
    .react-btn.is-mine { border-color:var(--color-brand-600); background:var(--color-brand-50); color:var(--color-brand-700); }
    html.dark .react-btn.is-mine { border-color:var(--color-brand-500); background:rgb(74 124 42 / .2); color:var(--color-brand-300); }
    .react-btn.just-reacted .e { animation: reactPop .45s var(--ease-house); }
    .react-count:empty { display:none; }
    .react-count.tick { animation: countTick var(--dur) var(--ease-house); }
    .react-bar-mini { margin-top:.25rem; }
    .react-bar-mini .react-btn { min-height:1.5rem; padding:.05rem .5rem; font-size:.7rem; border-width:1px; }
    .react-bar-mini .react-btn .e { font-size:.8rem; }
    @keyframes reactPop { 0%{transform:scale(1)} 40%{transform:scale(1.45) rotate(-8deg)} 100%{transform:scale(1)} }
    @keyframes countTick { from{transform:translateY(.45em); opacity:0} to{transform:none; opacity:1} }

    /* --- Emoji popover: ONE element on <body>, JS-positioned --- */
    .emoji-pop { position:fixed; z-index:130; width:16.5rem; padding:.5rem;
        display:grid; grid-template-columns:repeat(8, 1fr); gap:.125rem;
        background:#fff; border:1px solid var(--color-gray-200); border-radius:1rem;
        box-shadow:var(--shadow-card-lg); opacity:0; transform:scale(.92) translateY(4px);
        pointer-events:none; transform-origin:var(--pop-origin, bottom left);
        transition: opacity .22s var(--ease-house), transform .22s var(--ease-house); }
    .emoji-pop.is-open { opacity:1; transform:none; pointer-events:auto; }
    html.dark .emoji-pop { background:#232933; border-color:rgb(255 255 255 / .08); box-shadow:0 12px 32px rgb(0 0 0 / .5); }
    .emoji-pop button { font-size:1.15rem; line-height:1; min-height:2rem; border:0; background:transparent;
        border-radius:.5rem; cursor:pointer; transition: background-color .15s var(--ease-house), transform .15s var(--ease-house); }
    .emoji-pop button:hover { background:var(--color-brand-50); }
    .emoji-pop button:active { transform:scale(1.25); }
    html.dark .emoji-pop button:hover { background:rgb(255 255 255 / .07); }

    /* --- Giphy picker popover --- */
    .gif-pop { position:fixed; z-index:60; width:20rem; max-width:calc(100vw - 1rem); padding:.6rem;
        display:flex; flex-direction:column; gap:.5rem;
        background:#fff; border:1px solid var(--color-gray-200); border-radius:1rem;
        box-shadow:var(--shadow-card-lg); opacity:0; transform:scale(.92) translateY(4px);
        pointer-events:none; transform-origin:var(--pop-origin, bottom left);
        transition: opacity .22s var(--ease-house), transform .22s var(--ease-house); }
    .gif-pop.is-open { opacity:1; transform:none; pointer-events:auto; }
    html.dark .gif-pop { background:#232933; border-color:rgb(255 255 255 / .08); box-shadow:0 12px 32px rgb(0 0 0 / .5); }
    .gif-grid { display:grid; grid-template-columns:repeat(3, 1fr); gap:.375rem; overflow-y:auto; max-height:16rem;
        scrollbar-width:thin; scrollbar-color:var(--color-gray-300) transparent; }
    .gif-grid button { border:0; padding:0; background:var(--color-gray-100); border-radius:.5rem; overflow:hidden;
        cursor:pointer; aspect-ratio:1; transition: transform .15s var(--ease-house); }
    .gif-grid button:hover { transform:scale(1.04); }
    .gif-grid img { width:100%; height:100%; object-fit:cover; display:block; }
    .gif-credit { font-size:.65rem; font-weight:800; letter-spacing:.05em; color:var(--color-gray-400); text-align:right; margin:0; }

    /* --- Attachments: chip preview + GIF badge --- */
    .attach-chip { display:flex; align-items:center; gap:.625rem; margin-bottom:.5rem;
        padding:.25rem .5rem .25rem .25rem; border:1px solid var(--color-gray-200); border-radius:.75rem;
        background:var(--color-gray-50); animation: popIn var(--dur) var(--ease-house); }
    /* This <style> loads after Tailwind, so the flex above beats `.hidden` and
       an EMPTY chip (just its ✕) would show beside the send button. Re-assert. */
    .attach-chip.hidden { display:none !important; }
    .attach-chip img, .attach-chip video { width:3rem; height:3rem; object-fit:cover; border-radius:.5rem; }
    /* Beside a reply box the chip is smaller than beside a composer, and so
       is what it holds. A tap on it is a tap on the ✕ nine times in ten, so
       the thumbnail keeps out of the way of the button. */
    /* Not a button: it says when, it does not go anywhere. */
    .topic-act.topic-last { cursor:default; pointer-events:none; opacity:.85; }
    .topic-act.topic-last svg { width:.95rem; height:.95rem; }

    .js-comment-chip .js-chip-thumb, .js-video-chip .js-chip-thumb {
        width:2.25rem; height:2.25rem; object-fit:cover; border-radius:.45rem;
        background:var(--color-gray-100); flex:0 0 auto; pointer-events:none;
    }
    .js-chip-name.hidden { display:none; }
    html.dark .attach-chip img { box-shadow: inset 0 0 0 1px rgb(255 255 255 / .1); }
    /* A photo is the width of the post, always.
       As an inline-block sized to the file, a portrait shot sat in a narrow
       column with dead card either side and a landscape one nearly filled it
       — the same wall, two different shapes. One box, 4:3, filled by the
       picture: the crop is what makes a column of posts read as a column. */
    .post-media { position:relative; display:block; width:100%; margin-top:.5rem;
        aspect-ratio:4/3; max-height:22rem; border-radius:.75rem; overflow:hidden;
        background:var(--color-gray-100); cursor:zoom-in; }
    .post-media img { width:100%; height:100%; object-fit:cover; display:block; border-radius:0;
        transition:transform .4s cubic-bezier(.22,1,.36,1); }
    /* The lean-in: it says the picture has more to give than the crop shows. */
    .post-media:hover img { transform:scale(1.04); }
    @media (prefers-reduced-motion:reduce) { .post-media img { transition:none; } .post-media:hover img { transform:none; } }
    /* A tall picture is cropped hard by 4:3; say so rather than pretending the
       crop is the photo. */
    .post-media::after { content:''; position:absolute; inset:auto 0 0 0; height:35%; pointer-events:none;
        background:linear-gradient(to top, rgb(0 0 0 / .28), transparent); opacity:0;
        transition:opacity .28s cubic-bezier(.22,1,.36,1); }
    .post-media:hover::after { opacity:1; }
    .post-media-full { position:absolute; right:.5rem; bottom:.5rem; z-index:2; display:inline-flex;
        align-items:center; gap:.25rem; padding:.2rem .5rem; border-radius:999px; pointer-events:none;
        background:rgb(17 24 39 / .68); color:#fff; font-size:.62rem; font-weight:800;
        opacity:0; transition:opacity .28s cubic-bezier(.22,1,.36,1); }
    .post-media:hover .post-media-full { opacity:1; }
    @media (hover:none) { .post-media-full { opacity:.85; } }
    .gif-badge { position:absolute; top:.5rem; left:.5rem; padding:.25rem .4rem; border-radius:.375rem;
        background:rgb(26 26 26 / .72); color:#fff; font:800 .6rem/1 var(--font-heading); letter-spacing:.08em; }
    html.dark .gif-badge { border:1px solid rgb(255 255 255 / .15); }

    /* --- Composer + reply pill ---
       The shell is shared: the wall's comment forms and the group discussions'
       .post-reply-form both wrap their field in one. So the pill stays the pill
       here, and the two-row shape below is scoped to the wall — where it was
       asked for and where the tool row is long enough to need it. */
    .reply-shell { display:flex; align-items:center; gap:.375rem; flex-grow:1; min-width:0;
        border:1.5px solid var(--color-gray-200); border-radius:9999px; background:var(--color-gray-50);
        padding:.25rem .25rem .25rem .625rem;
        transition: border-color var(--dur) var(--ease-house), background-color var(--dur) var(--ease-house),
            box-shadow var(--dur) var(--ease-house); }
    .reply-shell:focus-within { border-color:var(--color-brand-500); background:#fff; box-shadow:0 0 0 3px rgb(107 159 61 / .18); }
    html.dark .reply-shell:focus-within { background:var(--color-gray-100); box-shadow:0 0 0 3px rgb(107 159 61 / .28); }
    /* Only the visible field: the photo/video pickers are inputs in here too. */
    .reply-shell input[type="text"] { flex:1; min-width:0; background:transparent; border:0; outline:none;
        box-shadow:none; font-size:.875rem; color:var(--color-gray-900); }

    /* The wall's shell only: the field gets a line to itself and the tools sit
       under it. They used to ride inside the pill beside the text, which on a
       phone left maybe four characters of typing room once photo / video /
       record / emoji / send had taken their share. A group reply carries a
       shorter row and a 4rem cap (see .post-reply-form), so it keeps one line.
       Nothing moved out of either form — the delegated handlers all still find
       their buttons where they expect them. */
    .wall-comment-form .reply-shell { flex-wrap:wrap; gap:.25rem; flex:1 1 100%;
        border-radius:1.15rem; padding:.3rem .35rem; }
    .wall-comment-form .reply-shell input[type="text"] { flex:1 1 100%; line-height:1.45; padding:.35rem .45rem; }
    /* Send closes the tool row on the right, where the thumb already is —
       unless a reply's ✕ is riding along, and then it leads the pair. */
    .wall-comment-form .reply-shell .reply-send { margin-left:auto; }

    /* A discussion reply gets the same two-row shape on a phone, for the same
       reason: photo + emoji + send take a fixed 6rem out of a 360px screen,
       and what was left of "Sumagot ka…" was four characters wedged between
       buttons. The field takes the first line, the tools the second. Desktop
       keeps the single pill, where there is room for both. */
    @media (max-width:640px) {
        .post-reply-form .reply-shell { flex-wrap:wrap; gap:.25rem; border-radius:1.15rem; padding:.3rem .35rem; }
        .post-reply-form .reply-shell input[type="text"] { flex:1 1 100%; line-height:1.45; padding:.35rem .45rem; }
        .post-reply-form .reply-shell .reply-send { margin-left:auto; }
    }
    .reply-send { width:2.25rem; height:2.25rem; flex-shrink:0; border:0; border-radius:9999px;
        display:inline-flex; align-items:center; justify-content:center; background:var(--color-brand-600); color:#fff;
        cursor:pointer; transition: background-color var(--dur) var(--ease-house), transform .15s var(--ease-house); }
    .reply-send:hover { background:var(--color-brand-700); }
    .reply-send:active { transform:scale(.9); }
    .emoji-btn { width:2.25rem; height:2.25rem; flex-shrink:0; border:0; background:transparent; border-radius:9999px;
        display:inline-flex; align-items:center; justify-content:center; color:var(--color-gray-400); cursor:pointer;
        transition: background-color .15s var(--ease-house), color .15s var(--ease-house); }
    .emoji-btn:hover { background:var(--color-gray-100); color:var(--color-gray-600); }

    /* --- Post card: delete reveal, replies label, thread rail --- */
    .group-post { position:relative; }
    @media (hover:hover) {
        .group-post .post-delete-btn { opacity:0; transition:opacity .15s var(--ease-house); }
        .group-post:hover .post-delete-btn, .group-post:focus-within .post-delete-btn { opacity:1; }
    }
    .replies-label { font-size:.7rem; font-weight:800; text-transform:uppercase; letter-spacing:.06em;
        color:var(--color-gray-400); margin-bottom:.375rem; }
    .post-replies { position:relative; }
    .post-replies::before { content:''; position:absolute; left:.8125rem; top:.25rem; bottom:.25rem;
        width:2px; border-radius:2px; background:var(--color-gray-200); opacity:.6; }
    .post-replies > * { position:relative; }

    /* --- Liveliness --- */
    .post-enter { animation: postIn .4s var(--ease-house); }
    .post-enter::after { content:''; position:absolute; inset:0; border-radius:inherit; pointer-events:none;
        background:var(--color-brand-50); opacity:0; animation: postFlash 1.2s var(--ease-house); }
    @keyframes postIn { from{opacity:0; transform:translateY(-8px) scale(.985)} to{opacity:1; transform:none} }
    @keyframes postFlash { 0%,25%{opacity:.55} 100%{opacity:0} }
    @keyframes popIn { from{opacity:0; transform:scale(.9) translateY(4px)} to{opacity:1; transform:none} }
    .group-post.is-removing { opacity:0; transform:scale(.98); overflow:hidden;
        transition: opacity var(--dur) var(--ease-house), transform var(--dur) var(--ease-house),
            max-height .32s var(--ease-house), margin .32s var(--ease-house), padding .32s var(--ease-house); }
    #postsWrap { transition: opacity var(--dur) var(--ease-house); }
    .is-refetching { opacity:.55; pointer-events:none; }
    #loadMoreBtn .dot { display:inline-block; width:.3rem; height:.3rem; border-radius:9999px; background:currentColor;
        margin:0 .1rem; animation: dotBounce .9s var(--ease-house) infinite; }
    #loadMoreBtn .dot:nth-child(2) { animation-delay:.12s; } #loadMoreBtn .dot:nth-child(3) { animation-delay:.24s; }
    @keyframes dotBounce { 0%,100%{transform:none; opacity:.5} 40%{transform:translateY(-4px); opacity:1} }
    /* --- Where a picture comes from ---
       A four-row sheet hung off the photo button. Fixed rather than absolute:
       a comment form sits inside cards that clip, and a menu that is clipped
       by its own thread is a menu nobody can use. */
    .attach-menu { position:fixed; z-index:90; min-width:15rem; padding:.3rem;
        background:var(--color-white); border:1px solid var(--color-gray-200);
        border-radius:.9rem; box-shadow:var(--shadow-card-lg);
        opacity:0; transform:translateY(.35rem);
        transition:opacity var(--dur) var(--ease-house), transform var(--dur) var(--ease-house); }
    .attach-menu.is-in { opacity:1; transform:none; }
    .attach-menu-row { display:flex; align-items:center; gap:.6rem; width:100%; text-align:left;
        padding:.5rem .6rem; border-radius:.65rem; background:none; border:0; cursor:pointer; }
    .attach-menu-row:hover { background:var(--color-gray-50); }
    .attach-menu-row svg { width:1.15rem; height:1.15rem; flex:none; color:var(--color-brand-600); }
    .attach-menu-row b { display:block; font-size:.8125rem; font-weight:600; color:var(--color-gray-900); }
    .attach-menu-row i { display:block; font-style:normal; font-size:.6875rem; color:var(--color-gray-500); }
    html.dark .attach-menu { background:#1b1f27; border-color:#2b3138; }
    html.dark .attach-menu-row:hover { background:#232833; }
    @media (prefers-reduced-motion: reduce) { .attach-menu { transition:none; } }

    /* The assistant's own colour. Not a badge: the name IS the signal, and a
       thread reads faster when the only thing that changed is its colour. */
    .author-ai { color:var(--color-brand-700); }
    html.dark .author-ai { color:var(--color-brand-300); }
    .author-ai:hover { text-decoration:underline; }

    /* Both ends of a long room, in the corner the write button used to hold.
       Same shape as the board's own jump stack, so the two read as one app. */
    /* The chat button keeps the floor; the jumps stack on top of it.
     *
     * Both are the same circle now, so the stack reads as one column — and
     * the corner nearest the thumb belongs to the button that is on every
     * page, not to the pair that only appear while a discussion is being
     * scrolled. The jumps clear the launcher by its own height and a gap. */
    .disc-jumps { position:fixed; right:1rem; bottom:calc(1rem + 3.6rem); z-index:30;
        display:flex; flex-direction:column; gap:.45rem;
        transition: opacity var(--dur) var(--ease-house), transform var(--dur) var(--ease-house); }
    .disc-jumps button { width:2.6rem; height:2.6rem; border-radius:9999px; border:1px solid var(--color-gray-200);
        background:var(--color-white); color:var(--color-gray-600); display:inline-flex;
        align-items:center; justify-content:center; box-shadow:var(--shadow-card-lg); cursor:pointer; }
    .disc-jumps button svg { width:1.2rem; height:1.2rem; }
    .disc-jumps button:active { transform:scale(.92); }
    .disc-jumps.is-hidden { opacity:0; transform:translateY(.5rem); pointer-events:none; }
    /* On phones the messenger launcher owns the same corner (its dock rides at
       4.5rem above the tab bar) — the stack steps up one storey so both stay
       tappable instead of sitting on the same spot. */
    @media (max-width:767px) {
        .disc-jumps { bottom:calc(3.5rem + 3.6rem + env(safe-area-inset-bottom, 0px)); }
    }
    /* Where the messenger sits in the page's own nav there is no floating
       launcher to clear, so the jumps drop to the floor themselves. */
    html:has(#msgrSeat) .disc-jumps { bottom:1rem; }
    @media (max-width:767px) {
        html:has(#msgrSeat) .disc-jumps { bottom:calc(3.5rem + env(safe-area-inset-bottom, 0px)); }
    }
    @media (prefers-reduced-motion: reduce) { .disc-jumps { transition:none; } }

    /* --- Join gate: invitation card, live melt, animated reply-form gate --- */
    #joinPrompt { border:1.5px dashed var(--color-brand-300); background:var(--color-brand-50); border-radius:1rem;
        overflow:hidden; transition: max-height .32s var(--ease-house), opacity var(--dur) var(--ease-house),
            margin .32s var(--ease-house), padding .32s var(--ease-house); }
    html.dark #joinPrompt { background:rgb(74 124 42 / .1); border-color:rgb(107 159 61 / .45); }
    #joinPrompt.gate-out { max-height:0 !important; opacity:0; margin-bottom:0; padding-top:0; padding-bottom:0; }
    #composerCard.is-entering { animation: postIn .32s var(--ease-house), ringPulse 1.2s var(--ease-house) .2s; }
    @keyframes ringPulse { 0%,100%{box-shadow:var(--shadow-card)} 40%{box-shadow:0 0 0 4px rgb(107 159 61 / .35), var(--shadow-card)} }
    [data-group-member="0"] .post-reply-form { opacity:0; max-height:0; overflow:hidden; margin-top:0; pointer-events:none; }
    /* 24rem, not 4: this cap exists only to give the gate's collapse
       somewhere to animate FROM. At 4rem it clipped any form tall enough to
       wrap — one with a "replying to @Name" pill over it — and since the
       form's overflow is visible, the clipped part sat on top of whatever
       came after it. */
    .post-reply-form { transition: opacity var(--dur) var(--ease-house), max-height var(--dur) var(--ease-house); max-height:24rem; }

    /* --- Comment threads (wall + groups): nested replies + photo chips --- */
    .wall-replies, .reply-thread { margin-left:2.1rem; display:flex; flex-direction:column; gap:.375rem; }
    .wall-replies:not(:empty), .reply-thread:not(:empty) { margin-top:.375rem; }
    .wall-reply-form { animation: replyFormIn var(--dur) var(--ease-house); }
    @keyframes replyFormIn { from { opacity:0; transform:translateY(-4px); } to { opacity:1; transform:none; } }
    .js-comment-chip { margin-bottom:0; padding:.25rem .25rem .25rem .625rem; }
    .js-chip-clear { border:none; background:transparent; color:var(--color-gray-400);
        font-size:.8rem; cursor:pointer; padding:.25rem .45rem; border-radius:9999px; }
    .js-chip-clear:hover { color:#ef4444; }
    .reply-link { border:none; background:transparent; font-size:.7rem; font-weight:700;
        color:var(--color-gray-400); cursor:pointer; padding:.2rem .4rem; border-radius:.5rem; }
    .reply-link:hover { color:var(--color-brand-700); }
    /* Short frame, whole picture inside it.
     *
     * The cap was on the PICTURE while its frame kept the 4:3 shape it was
     * given, so a photo that did not happen to be 4:3 was drawn short inside
     * a taller box and the rest of the box showed through underneath — which
     * reads exactly like a picture cut off at the bottom. Capping the frame
     * instead lets the picture fill it edge to edge: a deliberate close crop,
     * which is what the lean-in on hover and the lightbox on tap are for. */
    .reply-media { max-height:12rem; }
    .reply-media img { max-height:none; }

    /* --- Long threads fold: the first two entries stay, the rest slide behind
       a toggle. grid-template-rows 0fr → 1fr animates to the content's real
       height; a max-height would have to be guessed, and a comment carrying a
       photo makes that guess either a clip or a crawl. --- */
    .thread-fold { display:grid; grid-template-rows:0fr;
        transition: grid-template-rows var(--dur) var(--ease-house); }
    .thread-fold > * { overflow:hidden; min-height:0; display:flex; flex-direction:column; gap:.375rem; }
    /* Folded entries stop being direct children, so the zone's own spacing no
       longer reaches them — the wrapper has to reproduce it. */
    .cp-replies > .thread-fold > * { gap:.75rem; }
    .thread-fold.is-open { grid-template-rows:1fr; }
    /* align-self keeps the hit area (and the hover underline) to the words, in
       a reply zone that is itself a stretching column. */
    .thread-toggle { display:inline-flex; align-self:flex-start; align-items:center; gap:.25rem; border:0;
        background:transparent; padding:.1rem 0; font-size:.75rem; font-weight:700;
        color:var(--color-brand-700); cursor:pointer; }
    .thread-toggle:hover { color:var(--color-brand-800); text-decoration:underline; }
    .thread-toggle .th-chev { width:.75rem; height:.75rem; transition: transform var(--dur) var(--ease-house); }
    .thread-toggle[aria-expanded="true"] .th-chev { transform: rotate(180deg); }

    /* --- Comment action row: reactions stay, the rest hide behind one ⋯ ---
       Four reaction pills already fill the row, so adding Reply and Delete
       pushed the reactions onto a line of their own on a phone. Reacting is
       the frequent tap, so it keeps the row; the two rare actions move into a
       sheet opened from the corner. --- */
    /* Shown, and wrapping if it must — the way a discussion's replies do.
       Reply used to disappear behind a ⋯ below 640px. */
    .wc-actions { display:flex; align-items:center; flex-wrap:wrap; gap:.2rem .5rem; }

    /* The ⋯ action sheet's rows went with the ⋯ itself. */

    /* --- Generic centered modal (comments "view all", etc.) --- */
    .plaza-modal { position:fixed; inset:0; z-index:120; display:flex; align-items:flex-end; justify-content:center;
        padding:0; opacity:0; transition:opacity .24s var(--ease-house); }
    @media (min-width:640px) { .plaza-modal { align-items:center; padding:1.5rem; } }
    .plaza-modal.is-open { opacity:1; }
    .plaza-modal.hidden { display:none; }
    .plaza-modal-backdrop { position:absolute; inset:0; background:rgb(10 14 20 / .55); }
    .plaza-modal-card { position:relative; width:100%; max-width:34rem; max-height:88vh; display:flex; flex-direction:column;
        background:var(--color-white); border-radius:1.1rem 1.1rem 0 0; box-shadow:0 20px 60px rgb(0 0 0 / .3);
        transform:translateY(24px); transition:transform .28s var(--ease-house); overflow:hidden; }
    @media (min-width:640px) { .plaza-modal-card { border-radius:1.1rem; } }
    .plaza-modal.is-open .plaza-modal-card { transform:none; }
    html.dark .plaza-modal-card { background:#151b12; }
    .plaza-modal-head { display:flex; align-items:center; justify-content:space-between; padding:.9rem 1.1rem;
        border-bottom:1px solid var(--color-gray-100); flex-shrink:0; }
    html.dark .plaza-modal-head { border-color:#24331a; }
    .plaza-modal-body { padding:.9rem 1.1rem; overflow-y:auto; flex:1; }
    .plaza-modal-foot { border-top:1px solid var(--color-gray-100); padding:.75rem 1.1rem; flex-shrink:0; }
    html.dark .plaza-modal-foot { border-color:#24331a; }
    @media (prefers-reduced-motion: reduce) { .plaza-modal, .plaza-modal-card { transition:none; } }

    /* --- Empty states --- */
    .empty-tile { width:4.5rem; height:4.5rem; margin:0 auto .75rem; border-radius:9999px;
        background:var(--color-brand-50); display:flex; align-items:center; justify-content:center; font-size:2rem; }
    html.dark .empty-tile { background:rgb(74 124 42 / .15); }

    /* --- Section accent: the hairline the app's modern headers wear, in
       community green, drifting on the shared gradSweep tide (layout). Any
       card or header block takes the class; popovers all live on <body>, so
       the overflow clip costs nothing. --- */
    /* No overflow:hidden — it was there to keep the accent line inside the
       card's rounded corners, and it also cut the status cloud in half, since
       the cloud hangs above the card by design. The line takes the card's own
       top corners instead, which is all it ever needed. */
    .plaza-accent { position:relative; }
    /* The card's whole shape with only its top three pixels painted, for the
       same reason .fp-card::before is: a browser shrinks a corner radius that
       cannot fit its box, so a 3px bar wearing a 16px radius came back with a
       3px corner laid across the card's own curve. */
    .plaza-accent::before { content:''; position:absolute; inset:0; border-radius:inherit;
        pointer-events:none;
        background-image:linear-gradient(90deg, #3d6823, #6b9f3d 45%, #a8cc7e 75%, transparent);
        background-repeat:no-repeat; background-size:220% 3px; background-position:left top;
        animation:fpStripSweep 12s ease-in-out infinite alternate; }
    html.dark .plaza-accent::before { opacity:.8; }
    @media (prefers-reduced-motion: reduce) { .plaza-accent::before { animation:none; } }

    /* ---- The invitation at the foot of a card ----

       "Join this discussion", "Take a look inside", "Read more": one button
       across the card, and the one thing each card is for.

       They were `display: block`, which threw away the flex centring .btn
       gives its label — so the words sat against the top padding of a
       2.25rem box with the slack underneath them, reading as a button whose
       text had slipped. Flex puts them back in the middle of it.

       The green is the one the discussions' own cards wear: a gradient that
       drifts on the app's shared tide rather than a flat fill. The oversize
       is what gives it room to move; the delay lets a column of them breathe
       out of step instead of pulsing in lockstep. */
    .fd-open, .fa-read {
        display: flex; align-items: center; justify-content: center;
        width: 100%; text-align: center; min-height: 2.5rem; padding-block: .55rem;
        border: 0; color: #fff;
        background-image: linear-gradient(120deg, #2f5219, #4a7c2a 28%, #6b9f3d 52%, #4a7c2a 76%, #2f5219);
        background-size: 220% 100%;
        animation: gradSweep var(--sw-t, 11s) ease-in-out infinite alternate;
        animation-delay: var(--sw-d, 0s);
    }
    .fd-open:hover, .fa-read:hover { filter: brightness(1.06); color: #fff; }
    @media (prefers-reduced-motion: reduce) { .fd-open, .fa-read { animation: none; } }

    /* --- Post-photo skeleton: while a wall/feed picture decodes, its box
       shimmers instead of the layout jumping open around a half-arrived
       image (gallery's sweep, aimed at the plaza). Opt-in via .media-skel on
       .post-media plus the is-loaded hooks on the <img>; the box holds a
       likely photo shape until the real one takes over. --- */
    /* The box already has its shape (see .post-media), so the shimmer only
       has to fill it while the picture decodes. */
    .media-skel:not(:has(img.is-loaded)):not(.is-gone) { min-height:6rem; }
    .media-skel:not(:has(img.is-loaded)) img { position:absolute; inset:0; }
    /* The shorthand here used to name only opacity, which does not add a
       transition -- it replaces the one .post-media gave the picture. So the
       hover lean-in below jumped straight to 1.04 with nothing in between,
       on every wall photo and every one the dashboard borrows. Both live in
       one list now. */
    .media-skel img { opacity:0; transition:opacity .28s ease, transform .4s cubic-bezier(.22,1,.36,1); }
    .media-skel img.is-loaded { opacity:1; }
    .media-skel::before { content:''; position:absolute; inset:0; border-radius:.75rem; pointer-events:none;
        background:linear-gradient(100deg, rgba(255,255,255,0) 20%, rgba(255,255,255,.55) 50%, rgba(255,255,255,0) 80%), var(--color-gray-100);
        background-size:220% 100%, auto; animation:mediaSkelSweep 1.15s linear infinite; }
    html.dark .media-skel::before {
        background:linear-gradient(100deg, rgba(255,255,255,0) 20%, rgba(255,255,255,.09) 50%, rgba(255,255,255,0) 80%), rgb(255 255 255 / .05);
        background-size:220% 100%, auto; }
    @keyframes mediaSkelSweep { from { background-position:220% 0, 0 0; } to { background-position:-220% 0, 0 0; } }
    .media-skel:has(img.is-loaded)::before { display:none; }
    /* A broken picture is not "still loading" — the box goes away, the words stay. */
    .media-skel.is-gone { display:none; }
    /* Reduced motion keeps a loader, just a still one — not a blank square. */
    @media (prefers-reduced-motion: reduce) {
        .media-skel::before { animation:none;
            background:var(--color-gray-100); }
        html.dark .media-skel::before { background:rgb(255 255 255 / .06); }
    }

    /* --- Thumb-size targets on touch: the composer tools grow to 44px, the
       corner ⋯ grows with them, and the small text actions (react pills,
       Reply, thread toggles) keep their look while their hit area quietly
       spreads past 44px via an invisible pseudo layer. --- */
    .react-btn, .reply-link, .thread-toggle { position:relative; }
    @media (pointer: coarse) {
        .wall-act { min-width:2.75rem; min-height:2.75rem; justify-content:center; }
        .emoji-btn, .reply-send { width:2.75rem; height:2.75rem; }
        .react-btn::after { content:''; position:absolute; inset:-.45rem 0; }
        .reply-link::after, .thread-toggle::after { content:''; position:absolute; inset:-.55rem -.25rem; }
    }

    {{-- Lightbox styles live inside community/partials/lightbox-js so pages
         outside the plaza (e.g. schedule activities) can use it standalone. --}}

    /* --- Read-more clamp: long bodies collapse to a few lines and expand like
       an accordion, so cards in a row keep a consistent height. --- */
    .plaza-clamp-body {
        overflow: hidden;
        max-height: var(--clamp-h, 5rem);
        transition: max-height var(--dur) var(--ease-house);
    }
    .plaza-clamp-toggle {
        margin-top: .35rem; font-size: .75rem; font-weight: 600; line-height: 1;
        color: var(--color-brand-700); cursor: pointer; background: none; border: 0; padding: 0;
    }
    .plaza-clamp-toggle:hover { text-decoration: underline; }

    /* --- Read-only "cloud" status floating over a member's avatar, shown
       wherever members appear (wall, members, co-farmers, discussions). --- */
    /* NOT IN USE: what the poster had on their mind, above their name.
       The owner asked for the bubble over the photo instead (the composer's
       shape), which is what the cards draw now. Kept because it is the
       fallback shape for any card too tight to hang a cloud over.

       What the poster had on their mind, above their name.
       In the flow rather than floating over the face: floating, it met the
       top of the card — first overlapping the border, then being cut in
       half by the clip that kept the coloured edge inside its corners. Same
       pill the composer wears, one size down, and read-only. */
    .fp-mind { display: inline-flex; align-items: center; max-width: 100%; margin-bottom: .25rem;
        padding: .2rem .55rem; border-radius: 999px;
        border: 1px solid var(--color-gray-200); background: var(--color-white);
        font-size: .68rem; font-weight: 700; line-height: 1.3; color: var(--color-gray-600);
        box-shadow: 0 4px 12px -8px rgb(0 0 0 / .4);
        overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    html.dark .fp-mind { background: #232a1c; border-color: #3a4a2c; color: #dbe6cf; }

    /* A discussion dealt into the wall. It wears the post card's shape, so
       only what differs is described here: a cover with the room's own face
       standing on it, and the last thing anybody said in there. */
    .fd-top { position: relative; }
    /* Joined, in green. On a post the Follow pill goes quiet grey once it is
       on, because following is a private preference; being in a room is a
       fact about you that the card is worth showing. */
    .fd-card .fp-follow.is-on { color: #fff; background: var(--color-brand-600);
        border-color: var(--color-brand-600); }
    .fd-card .fp-follow.is-on:hover { background: var(--color-brand-700); }
    /* The card's edge, over the cover.
       .fp-card::before draws a hairline at the top of every post — but the
       cover here is a positioned element further down the document, so the
       photograph painted straight over it. Raised above the picture, given
       a little more height, and painted in the house green rather than the
       per-post hue: a three-pixel blue line against a photograph of the sky
       is a line nobody can see. It drifts on the same gradSweep tide the
       rest of the community's gradients ride. */
    /* Two classes, because .fp-hue-N::before is written further down this
       file at the same specificity and would otherwise keep the per-post
       colour — which is how this first came out orange. */
    .fd-card.fp-card::before { z-index: 2;
        background-image: linear-gradient(120deg, #2f5219, #6b9f3d 28%, #b8d38e 48%, #4a7c2a 72%, #2f5219);
        background-size: 220% 5px; animation: fpStripSweep 12s ease-in-out infinite alternate; }
    /* Its own tide, because the shared one drifts on both axes: with a strip
       five pixels tall in a box the height of a card, a vertical drift would
       walk the green from the top of the post to the bottom of it. */
    @keyframes fpStripSweep { from { background-position: 0% top; } to { background-position: 100% top; } }
    @media (prefers-reduced-motion: reduce) { .fd-card.fp-card::before { animation: none; } }
    .fd-banner { position: relative; height: 8.5rem; overflow: hidden;
        border-top-left-radius: inherit; border-top-right-radius: inherit; }
    .fd-banner img { width: 100%; height: 100%; object-fit: cover; display: block; }
    /* A foot under the cover, so a white face and a white word sit on
       something rather than on whatever the photograph happens to be. */
    .fd-banner::after { content: ''; position: absolute; inset: auto 0 0 0; height: 60%;
        background: linear-gradient(to top, rgb(0 0 0 / .45), transparent); }
    /* A little more room under the letters than over them: DISCUSSION is
       all caps with no descenders, so an evenly padded pill reads as though
       the word is sitting high in it. */
    .fd-kicker { position: absolute; top: .6rem; left: .75rem; z-index: 1;
        display: inline-flex; align-items: center; line-height: 1;
        padding: .38rem .6rem .26rem; border-radius: 999px;
        background: rgb(255 255 255 / .92); color: var(--color-brand-700);
        font-size: .62rem; font-weight: 800; letter-spacing: .05em; text-transform: uppercase;
        box-shadow: 0 4px 12px -6px rgb(0 0 0 / .5); }
    /* The room's face, half on the cover and half off it — the shape a
       reader already knows a group by. */
    .fd-face { position: absolute; left: 1rem; bottom: -1.5rem; z-index: 1;
        display: flex; align-items: center; justify-content: center;
        width: 4.25rem; height: 4.25rem; border-radius: 1.1rem; overflow: hidden;
        border: 3px solid var(--color-white); background: var(--color-white);
        font-family: var(--font-heading); font-weight: 800; font-size: 1.25rem; color: #fff;
        box-shadow: 0 10px 24px -14px rgb(0 0 0 / .8); text-decoration: none; }
    .fd-face img { width: 100%; height: 100%; object-fit: cover; }
    .fd-body { padding: 1rem; padding-top: 2rem; }
    .fd-head { display: flex; align-items: flex-start; gap: .75rem; }
    .fd-name { display: block; font-family: var(--font-heading); font-weight: 800;
        font-size: 1.02rem; line-height: 1.25; color: var(--color-gray-900); text-decoration: none; }
    .fd-name:hover { color: var(--color-brand-700); }
    .fd-meta { margin-top: .15rem; font-size: .72rem; color: var(--color-gray-400); }
    .fd-desc { margin-top: .5rem; font-size: .82rem; line-height: 1.5; color: var(--color-gray-500);
        display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }

    /* The last topic: the part of the card that earns the tap. Its own well,
       so it reads as a thing said in there rather than more of the blurb. */
    .fd-topic { display: block; margin-top: .75rem; padding: .65rem .75rem;
        border: 1px solid var(--color-gray-200); border-left: 3px solid var(--color-brand-500);
        border-radius: .7rem; background: var(--color-gray-50); text-decoration: none;
        transition: background var(--dur) var(--ease-house), border-color var(--dur) var(--ease-house); }
    .fd-topic:hover { background: var(--color-brand-50); border-color: var(--color-brand-200);
        border-left-color: var(--color-brand-600); }
    .fd-topic-tag { display: block; font-size: .6rem; font-weight: 800; letter-spacing: .06em;
        text-transform: uppercase; color: var(--color-brand-700); }
    .fd-topic-title { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;
        overflow: hidden; margin-top: .15rem; font-size: .85rem; font-weight: 700; line-height: 1.35;
        color: var(--color-gray-900); }
    .fd-topic-meta { display: flex; align-items: center; flex-wrap: wrap; gap: .35rem;
        margin-top: .4rem; font-size: .7rem; color: var(--color-gray-500); }
    .fd-topic-meta .avatar { width: 1.15rem; height: 1.15rem; font-size: .5rem; }
    .fd-topic-meta b { color: var(--color-gray-700); font-weight: 700; }
    .fd-open { margin-top: .9rem; }

    /* ---- An article from the tech blog, on the wall ----
       Named but never described: these classes had no rules anywhere in the
       app, so the card was browser defaults in a rounded box. */
    /* Inset, not full width: this is a trailer for something that lives on
       another page, and a photograph running to the card's edges reads as
       something somebody on this wall shared. */
    .fa-cover { position: relative; display: block; margin: .85rem .85rem 0;
        aspect-ratio: 16 / 10; overflow: hidden; border-radius: .8rem;
        background: var(--color-gray-100); }
    .fa-cover img { width: 100%; height: 100%; object-fit: cover; display: block; }
    .fa-kicker { position: absolute; top: .6rem; left: .6rem;
        display: inline-flex; align-items: center; line-height: 1;
        padding: .38rem .6rem .26rem; border-radius: 999px;
        background: rgb(255 255 255 / .92); color: var(--color-brand-700);
        font-size: .62rem; font-weight: 800; letter-spacing: .05em; text-transform: uppercase;
        box-shadow: 0 4px 12px -6px rgb(0 0 0 / .5); }
    /* No cover to sit on: the same pill, in the flow. */
    .fa-kicker-flat { position: static; box-shadow: none; background: var(--color-brand-50);
        margin-bottom: .35rem; }
    .fa-body { padding: 1rem; }
    .fa-title { display: block; font-family: var(--font-heading); font-weight: 800;
        font-size: 1.02rem; line-height: 1.3; color: var(--color-gray-900); text-decoration: none; }
    .fa-title:hover { color: var(--color-brand-700); }
    .fa-by { margin-top: .2rem; font-size: .72rem; color: var(--color-gray-400); }
    /* Longer than the old one line, and cut where the card ends rather than
       where the writer's sentence did. */
    .fa-excerpt { display: -webkit-box; -webkit-line-clamp: 4; -webkit-box-orient: vertical;
        overflow: hidden; margin-top: .55rem; font-size: .85rem; line-height: 1.55;
        color: var(--color-gray-600); }
    .fa-read { margin-top: .95rem; }
    /* .fa-foot and .fa-views are gone with the row they dressed. */

    /* ---- A member card: cover, the face over it, and why they matter ----
       Drawn by the directory, by its pager and by My Co-Farmers, so the
       rules live here rather than in any one of them. */
    /* --- A member is a band ---
       The same shape the wall's posts, the rooms and the blog now read in:
       full width, square where the screen cuts it, its own colour along the
       top and the bottom. A person is not a tile in a catalogue.
       The strip colours come from the same six the cover drifts through, so
       a member's band and their cover are the same person's colour. */
    .mc-card { overflow: visible; position: relative; border-radius: 0;
        border-left: 0; border-right: 0;
        margin-left: calc(var(--plaza-gutter, 1rem) * -1);
        margin-right: calc(var(--plaza-gutter, 1rem) * -1);
        --mc-a: #2f5219; --mc-b: #b8d38e; }
    .mc-card::before, .mc-card::after { content: ''; position: absolute; inset: 0 0 auto 0;
        height: 3px; z-index: 3; pointer-events: none;
        background: linear-gradient(90deg, var(--mc-a), var(--mc-b) 55%, transparent); }
    .mc-card::after { inset: auto 0 0 0;
        background: linear-gradient(270deg, var(--mc-a), var(--mc-b) 55%, transparent); }
    .mc-hue-0 { --mc-a: #2f5219; --mc-b: #b8d38e; }
    .mc-hue-1 { --mc-a: #1d4ed8; --mc-b: #bfdbfe; }
    .mc-hue-2 { --mc-a: #b45309; --mc-b: #fde68a; }
    .mc-hue-3 { --mc-a: #0f766e; --mc-b: #99f6e4; }
    .mc-hue-4 { --mc-a: #6d28d9; --mc-b: #ddd6fe; }
    .mc-hue-5 { --mc-a: #be185d; --mc-b: #fbcfe8; }
    /* A band that lifts on hover lifts the page with it. */
    .mc-card.card-hover:hover { transform: none; }
    /* The cover is back, and with it the air moves INSIDE the card: the face
       and its cloud now stand on the member's own field instead of hanging
       above the card in a column of empty page. What is left above is an
       ordinary gap between bands. (Adjacent card margins collapse; this top
       margin IS the gap.) */
    .mc-card { margin-top: 1.15rem; }
    .mc-name-row { display: flex; align-items: center; gap: .45rem; flex-wrap: wrap; min-width: 0; }
    /* Connect and Accept wear the composers' living green: the one deliberate
       act on the card, dressed like every other primary act in the plaza. */
    .conn-btn.conn-grad { border: 0; color: #fff;
        background-image: linear-gradient(120deg, #2f5219, #4a7c2a 28%, #6b9f3d 52%, #4a7c2a 76%, #2f5219);
        background-size: 220% 100%;
        animation: gradSweep var(--sw-t, 11s) ease-in-out infinite alternate; }
    .conn-btn.conn-grad:hover { filter: brightness(1.06); color: #fff; }
    @media (prefers-reduced-motion: reduce) { .conn-btn.conn-grad { animation: none; } }
    /* The quiet ✕ beside Follow on a co-farmer's card — its centre on the
       Follow pill's centre line (the pill self-aligns to the head's top,
       so this does too, nudged up to split the height difference). */
    .mc-x { flex: none; align-self: flex-start; margin-top: -5px; }
    .mc-x-btn { width: 2.3rem; height: 2.3rem; border: 0; border-radius: 999px; cursor: pointer;
        display: inline-flex; align-items: center; justify-content: center;
        background: transparent; color: var(--color-gray-300);
        transition: color .28s cubic-bezier(.22,1,.36,1), background .28s cubic-bezier(.22,1,.36,1); }
    .mc-x-btn svg { width: 1.05rem; height: 1.05rem; }
    .mc-x-btn:hover { color: #dc2626; background: rgb(220 38 38 / .08); }
    html.dark .mc-x-btn { color: #5d6858; }
    html.dark .mc-x-btn:hover { color: #f87171; background: rgb(248 113 113 / .12); }
    .mc-body { padding: 0 .9rem .9rem; }
    /* The face hangs over the cover, and the cloud above it hangs over the
       card — so the head of the card gets the air the cloud needs. */
    /* The face half on the cover, the name centred against it — the row's
       own height comes from the face, so a one-line name does not float
       above a photo twice its height. */
    .mc-head { display: flex; align-items: center; gap: .85rem; padding-top: .6rem; min-height: 2.6rem; }
    /* The face at a size worth meeting: 5rem, half of it standing over the
       cover. Its margin box is the visible lower half, which is what the
       name block centres itself against — name and level sit inline with
       the photo, on its midline, not hanging from its hat. */
    /* Above the card's edge strip (z-3): the face crosses the border, so
       the border must pass behind the face, not draw a line through it. */
    /* Hung from the head's top, not centred in it: the head is taller on
       My Co-Farmers (it carries the ✕ beside Follow), and under
       align-items:center that extra height pushed the face down — the same
       photo rose 30px into its band on Members and 19px here. Pinned to the
       top, the overlap is the margin and nothing else. */
    .mc-face { display: block; margin-top: -2.5rem; flex: none; position: relative; z-index: 4;
        align-self: flex-start; }
    /* The house green does the cutting-out here, not a white line: a face on
       a photograph needs a rim to stand on, and the ring every other avatar
       in the app already wears is that rim, at the width this size wants. */
    .mc-face .avatar { width: 5rem; height: 5rem; font-size: 1.6rem; --av-ring: 3.5px;
        box-shadow: 0 8px 20px -14px rgb(0 0 0 / .8); }
    .mc-who { padding-top: 0; }
    /* A card has more room than an avatar in a list, so the cloud over it
       may say more before it trails off. */
    .mc-face .status-cloud { max-width: 13rem; }
    /* A post card has the same room a member card does, and 9rem cut most
       of these off after three words. */
    .feed-post .status-cloud, .group-post .status-cloud { max-width: 13rem; }
    .mc-name { display: block; font-family: var(--font-heading); font-weight: 800; font-size: .95rem;
        line-height: 1.25; color: var(--color-gray-900); text-decoration: none;
        overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .mc-name:hover { color: var(--color-brand-700); }
    .mc-line { margin-top: .15rem; font-size: .75rem; color: var(--color-gray-500);
        overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .mc-where { display: flex; align-items: center; gap: .25rem; color: var(--color-gray-400); }
    .mc-where svg { width: .8rem; height: .8rem; flex: none; }
    .mc-mutual { margin-top: .6rem; font-size: .76rem; font-weight: 600; color: var(--color-gray-500); }
    .mc-mutual-n { display: inline-flex; align-items: center; justify-content: center;
        min-width: 1.3rem; height: 1.3rem; padding: 0 .35rem; margin-right: .25rem;
        border-radius: 999px; background: var(--color-brand-50); color: var(--color-brand-700);
        font-weight: 800; font-size: .72rem; }
    .mc-mutual-none { color: var(--color-gray-400); font-weight: 500; }
    /* The two decisions, side by side and the same size — a stretched
       badge beside a small button reads as one broken row. */
    /* The head is a row of three: face, who they are, and the one gesture
       that costs nothing. The who-block centres against the face's visible
       half; the Follow button keeps to the top corner on its own, so a
       two-line name still cannot drag it down the card. */
    .mc-follow { align-self: flex-start; }
    .mc-line { display: block; font-size: .72rem; color: var(--color-gray-500); line-height: 1.3;
        overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .mc-follow { flex: none; margin-top: .1rem; }
    .mc-bits { margin-top: .1rem; }
    .mc-acts { display: flex; align-items: center; gap: .5rem; margin-top: .75rem; }
    .mc-acts > * { flex: 1 1 0; min-width: 0; }
    .mc-acts .conn-action { justify-content: center; }
    .mc-acts .conn-action .btn { flex: 1 1 auto; }
    .mc-acts .fp-follow { justify-content: center; }

    @media (prefers-reduced-motion: reduce) {
        .reco-top { animation: none; }
    }

    /* A band across the wall, not a card on it.
       It runs to both edges the way the posts do, so the green shows only
       above and below — two rules the eye reads as "this belongs to the
       page", where four rules and rounded corners read as "this is a box
       sitting on it". */
    /* Two classes on purpose: .reco-edge is written further down this file
       and would otherwise put the card's corners and side borders back —
       which is exactly what happened when these rules moved here from the
       wall's own <style>, where they had been the later ones. A band has two
       rules, not four; four and rounded corners read as a box sitting on the
       page rather than part of it. */
    .pymk.reco-edge { position: relative;
        margin: 0 calc(var(--plaza-gutter, 1rem) * -1) 1.25rem;
        /* A shade more under the cards than over the heading: the heading's
           own line-height already holds air above its letters, so equal
           padding read as a band sitting low in its own frame. */
        padding: .85rem var(--plaza-gutter, 1rem) 1.05rem;
        border-radius: 0; border-left: 0; border-right: 0; box-shadow: none;
        /* No wash. The cards carry the colour — each one a strip of the
           person's own — and a tint behind them made the strip a second
           green competing with the first. The two rules top and bottom are
           what say "this is a band"; the page shows through between them. */
        background: none; }
    html.dark .pymk.reco-edge { background: none; }
    /* Its own colours along the top and the bottom, the way every band in the
       community carries them. */
    .pymk.reco-edge::before, .pymk.reco-edge::after { content: ''; position: absolute; inset: 0 0 auto 0;
        height: 3px; pointer-events: none; z-index: 1;
        background: linear-gradient(90deg, #2f5219, #6b9f3d 35%, #b8d38e 60%, transparent); }
    .pymk.reco-edge::after { inset: auto 0 0 0;
        background: linear-gradient(270deg, #2f5219, #6b9f3d 35%, #b8d38e 60%, transparent); }

    /* Three whole faces, and a way to the next three.
     *
     * The rail used to run off the right edge so a fourth card peeked — the
     * only hint that the row scrolled. A cut-off card is a card nobody can
     * read, so the row now holds exactly three across whatever width it has,
     * and the arrows say the rest are there. */
    .pymk-wrap { position: relative; }
    .pymk-rail { display: grid; grid-auto-flow: column;
        /* Two whole cards and the edge of a third. Three to a screen made
           each one a face and an abbreviation; two and nothing else made a
           strip that gave no sign it went on. The sliver is the sign. */
        grid-auto-columns: 41%; gap: .5rem; }
    /* No arrows. The rail shows two cards and the edge of a third, which is
       the whole of what an arrow was there to say, and a thumb does the rest
       — buttons floating over two faces were two more things on a card that
       already carries two. */
    /* The heading is the handle now, so it is a button the whole width of
       the band — a chevron on its own would be a target the size of a
       fingernail. */
    .pymk-head { display: flex; align-items: center; justify-content: space-between; gap: .75rem;
        width: 100%; margin-bottom: .5rem; padding: 0; background: none; border: 0; cursor: pointer;
        text-align: left; }
    .pymk-head h2 { font-family: var(--font-heading); font-size: .95rem; font-weight: 800; color: var(--color-gray-900); }
    .pymk-chev { width: 1.1rem; height: 1.1rem; flex: none; color: var(--color-gray-400);
        transition: transform .28s cubic-bezier(.22,1,.36,1); }
    .pymk.is-folded .pymk-chev { transform: rotate(-90deg); }
    /* Folded: the body is measured away rather than switched off, so the
       rail slides shut instead of vanishing under the heading. */
    .pymk-body { max-height: 22rem; overflow: hidden;
        transition: max-height .32s cubic-bezier(.22,1,.36,1), opacity .28s cubic-bezier(.22,1,.36,1); }
    .pymk.is-folded .pymk-body { max-height: 0; opacity: 0; }
    .pymk.is-folded { padding-bottom: .35rem; }
    @media (prefers-reduced-motion: reduce) {
        .pymk-chev, .pymk-body { transition: none; }
    }
    /* Stretch, so a card with a short reason is still as tall as its
       neighbour; and no scrollbar under it — the half-card showing past the
       edge says it scrolls, and says it without a grey stripe. */
    .pymk-rail { align-items: stretch; overflow-x: auto; padding-bottom: .1rem;
        scroll-snap-type: x mandatory; -webkit-overflow-scrolling: touch; scrollbar-width: none;
        scroll-behavior: smooth; }
    .pymk-rail > * { scroll-snap-align: start; }
    .pymk-rail::-webkit-scrollbar { display: none; }
    /* The shape of what is coming, shimmering while it comes. */
    .pymk-skel { width: auto; min-width: 0; height: 12.5rem; border-radius: .9rem;
        background: linear-gradient(100deg, var(--color-gray-100) 40%, var(--color-gray-200) 50%, var(--color-gray-100) 60%);
        background-size: 200% 100%; animation: pymkShim 1.2s linear infinite; }
    @keyframes pymkShim { to { background-position: -200% 0; } }
    .pymk-empty { font-size: .82rem; color: var(--color-gray-400); padding: .5rem 0; }
    html.dark .pymk-skel { background: linear-gradient(100deg, #1c2416 40%, #26301c 50%, #1c2416 60%); background-size: 200% 100%; }
    @media (prefers-reduced-motion: reduce) { .pymk-skel { animation: none; } }

    /* What the box to answer in is for, beside your face. Small: it is an
       instruction, not a heading, and it sits under the thing being read. */
    .reply-lead { display: flex; align-items: center; gap: .45rem; margin-top: .7rem; }
    .reply-lead .avatar { width: 1.5rem; height: 1.5rem; font-size: .55rem; flex: none; }
    .reply-lead b { display: block; font-size: .82rem; font-weight: 800; line-height: 1.25;
        color: var(--color-gray-700); }
    /* .76rem, the size the topic composer's instruction is: the same kind of
       sentence, in the same voice, should not be two sizes. */
    .reply-lead i { display: block; font-style: normal; font-size: .76rem; line-height: 1.4;
        color: var(--color-gray-400); }

    /* A reply's ✕ belongs in the corner of the field it cancels.
       In the flow it went wherever the row ended — beside Send on one screen,
       stranded on a line of its own on another. It now sits over the top
       right of the box itself, and the field's first line is padded so a long
       reply cannot run beneath it. */
    .wall-reply-form .reply-shell { position: relative; }
    .wall-reply-form .reply-shell > .js-reply-cancel { position: absolute; top: .3rem; right: .4rem; z-index: 2;
        margin: 0; width: 1.4rem; height: 1.4rem; background: var(--color-white);
        box-shadow: 0 1px 4px rgb(0 0 0 / .14); }
    .wall-reply-form .reply-shell > .js-reply-cancel svg { width: .8rem; height: .8rem; }
    html.dark .wall-reply-form .reply-shell > .js-reply-cancel { background: var(--color-gray-100); }
    /* The lane the ✕ stands in, kept clear of the words behind it. */
    .wall-reply-form .reply-shell input[type="text"] { padding-right: 2rem; }

    @media (min-width: 641px) {
        /* A discussion reply keeps one pill on a wide screen, and there the
           corner is where Send already is. Cancel rides at the end of the row
           instead — the only place it can go without landing on something. */
        .post-reply-form .reply-shell > .js-reply-cancel { position: static; width: 1.75rem; height: 1.75rem;
            background: none; box-shadow: none; }
        .post-reply-form .reply-shell input[type="text"] { padding-right: .45rem; }
    }

    /* ---- who is speaking: the small print under a name ----
       Drawn by the wall's posts and by a discussion's topics, so it lives
       here rather than in either of them. */
    .af-line { display: flex; align-items: center; flex-wrap: wrap; gap: .25rem .55rem;
        margin-top: .2rem; font-size: .72rem; line-height: 1.35; color: var(--color-gray-400); }
    .af-fact { display: inline-flex; align-items: center; gap: .2rem; }
    .af-fact b { font-weight: 700; color: var(--color-gray-600); }
    .af-mate { display: inline-flex; align-items: center; gap: .15rem; padding: .05rem .4rem;
        border-radius: 999px; background: var(--color-brand-50); color: var(--color-brand-700);
        font-size: .62rem; font-weight: 800; }
    /* Your own: a statement, not a relationship. */
    .af-mine { background: var(--color-gray-100); color: var(--color-gray-500); }
    /* Under the header, across the card. The second line sits tighter to
       the first — they are two halves of one introduction. */
    .feed-post > .af-line, .group-post > .af-line { margin-top: .35rem; }
    .feed-post > .af-line + .af-line, .group-post > .af-line + .af-line { margin-top: .6rem; }
    /* A member card draws the same introduction under the head, where the
       card's own three lines used to be. */
    .mc-body > .af-line { margin-top: .55rem; }
    .mc-body > .af-line + .af-line { margin-top: .35rem; }

    /* Follow lives in the card's own corner: it is a decision about the
       person, not a word attached to their name. The header keeps a lane
       clear for it so a long name never runs underneath. */
    /* The corner, and the corner alone. It used to drop to 2.2rem on a card
       carrying a thought bubble — but the bubble is over the face on the
       LEFT, so there was never anything on the right for it to avoid. */
    .wall-post > .fp-follow { position: absolute; top: .7rem; right: .7rem; z-index: 3; }
    .wall-post:has(> .fp-follow) header { padding-right: 5.75rem; }

    /* The eye that counts looks. Sized to sit in a line of small print,
       wherever that line is. */
    .v-eye { display: inline-flex; align-items: center; gap: .2rem; vertical-align: -.1em; }
    .v-eye svg { width: .95em; height: .95em; opacity: .75; }

    @media (prefers-reduced-motion: reduce) { .fd-topic { transition: none; } }

    /* The name and place beside a face, centred on it.
       With both lines the block is about the face's height and centring
       changes nothing; with only a name — nobody has said where they farm —
       it stops the name hanging from the top of a row twice its height. */
    .fp-head-txt { align-self: center; }

    /* ---- The post's coloured edge ----
       A line across the top of every post, a different colour per post, so a
       column of them has a rhythm and the eye can see where one ends and the
       next begins while scrolling. The green stays first among them: this is
       still the app's wall, not a paint chart. */
    /* No overflow:hidden here — the status cloud floats above the card and
       clipping the card clipped the cloud in half. The strip takes the card's
       own top corners instead, which is all the clipping it needed. */
    /* The hue is a pair of colours held on the card, so anything that wants
       to paint in a post's own colour — the top strip, and now the bottom
       one under a topic — reads them from one place. */
    .fp-card { position: relative; --fp-a: #4a7c2a; --fp-b: #8fc267; }
    /* The card's corner: what you can do to the post itself, as against
       what you can say about it (that lives in the action row). The trash
       can, and — on a card lifted out of its wall — the door back to it. */
    .fp-corner { position: absolute; top: .6rem; right: .6rem; z-index: 5;
        display: flex; align-items: center; gap: .1rem; }
    /* Both muted until looked at: neither must compete with the post. */
    .fp-del, .fp-open { flex: none;
        width: 2.1rem; height: 2.1rem; display: inline-flex; align-items: center; justify-content: center;
        border: 0; border-radius: 999px; background: transparent; color: #b6c0b0; cursor: pointer;
        transition: color .28s cubic-bezier(.22,1,.36,1), background .28s cubic-bezier(.22,1,.36,1),
            transform .28s cubic-bezier(.22,1,.36,1); }
    .fp-del svg, .fp-open svg { width: 1.05rem; height: 1.05rem; }
    .fp-del:hover, .fp-del:focus-visible { color: #dc2626; background: rgb(220 38 38 / .09); transform: scale(1.1); }
    /* The way back is a door, not a warning: it warms to the house green. */
    .fp-open:hover, .fp-open:focus-visible { color: var(--color-brand-700); background: var(--color-brand-50); transform: scale(1.1); }
    html.dark .fp-del, html.dark .fp-open { color: #5d6858; }
    html.dark .fp-del:hover, html.dark .fp-del:focus-visible { color: #f87171; background: rgb(248 113 113 / .13); }
    html.dark .fp-open:hover, html.dark .fp-open:focus-visible { color: #bfe19a; background: rgb(61 104 35 / .3); }
    @media (prefers-reduced-motion: reduce) {
        .fp-del, .fp-open { transition: none; }
        .fp-del:hover, .fp-open:hover { transform: none; }
    }
    /* A deleted post leaves the way a card should: folding shut so the
       column closes over the gap, not blinking out of it. The height it
       folds from is set inline by the handler just before this class lands. */
    .wall-post.is-leaving { overflow: hidden; pointer-events: none;
        opacity: 0; transform: translateY(-.4rem) scale(.98);
        max-height: 0 !important; margin-top: 0 !important; margin-bottom: 0 !important;
        padding-top: 0 !important; padding-bottom: 0 !important;
        transition: max-height .42s cubic-bezier(.22,1,.36,1), opacity .28s ease,
            transform .42s cubic-bezier(.22,1,.36,1), margin .42s cubic-bezier(.22,1,.36,1),
            padding .42s cubic-bezier(.22,1,.36,1); }
    @media (prefers-reduced-motion: reduce) { .wall-post.is-leaving { transition-duration: .01s; } }
    /* The strip is the card's whole shape with only its top few pixels
       painted — not a three-pixel bar wearing the card's corner radius.
       A browser shrinks a radius that cannot fit the box it is on, so an
       18px corner asked of a 3px-tall bar came back as a 3px corner: a
       square-ish stub laid across the card's own curve, poking out past it
       at both top corners. Given the full height, the radius fits, the
       background is clipped by it, and the strip ends exactly where the
       card's edge turns. Everything below the top few pixels is transparent
       and unclickable, so it covers nothing. */
    .fp-card::before { content: ''; position: absolute; inset: 0; pointer-events: none;
        border-radius: inherit;
        background-image: linear-gradient(90deg, var(--fp-a), var(--fp-b) 55%, transparent);
        background-repeat: no-repeat; background-size: 100% 3px; background-position: left top; }
    .fp-hue-1 { --fp-a: #1d4ed8; --fp-b: #7aa5f5; }
    .fp-hue-2 { --fp-a: #b45309; --fp-b: #ecc06a; }
    .fp-hue-3 { --fp-a: #0f766e; --fp-b: #6cc9bf; }
    .fp-hue-4 { --fp-a: #7c3aed; --fp-b: #b393f5; }
    .fp-hue-5 { --fp-a: #be185d; --fp-b: #f090b8; }

    /* --- A topic is a band, not a card ---
       In a discussion the page IS the list of topics, so each one runs the
       full width of the screen the way every other mobile surface in this app
       does, with its own colour drawn along the top and the bottom. The
       bottom strip is the mirror of the top: it fades from the other side, so
       a run of topics reads as bands rather than as boxes that lost their
       corners. */
    .group-post.fp-card::after { content: ''; position: absolute; inset: 0;
        pointer-events: none; border-radius: inherit;
        background-image: linear-gradient(270deg, var(--fp-a), var(--fp-b) 55%, transparent);
        background-repeat: no-repeat; background-size: 100% 3px; background-position: left bottom; }

    @media (max-width: 639px) {
        /* Out to the gutters the page holds it in by, and square where the
           screen cuts it. The gutter is the same variable the sticky section
           bar bleeds itself with. */
        #postsWrap .group-post {
            margin-left: calc(var(--plaza-gutter, 1rem) * -1);
            margin-right: calc(var(--plaza-gutter, 1rem) * -1);
            border-left: 0; border-right: 0; border-radius: 0;
        }
    }

    /* The strip's own edge: the house green, drifting.

       Two backgrounds in one box — the card's own surface clipped to the
       padding box, the gradient clipped to the border box — which is how a
       border can hold a gradient at all. It rides the shared gradSweep tide
       the hero and the covers use, slowly enough to be noticed only if you
       look. The surface layer is a solid colour, so the drift moves the green
       and leaves the fill where it is. */
    .reco-edge { position: relative; border: 1.5px solid transparent; border-radius: 1.1rem;
        background:
            linear-gradient(var(--color-white), var(--color-white)) padding-box,
            linear-gradient(120deg, #2f5219, #6b9f3d 28%, #b8d38e 48%, #4a7c2a 72%, #2f5219) border-box;
        background-size: auto, 220% 220%;
        animation: gradSweep 14s ease-in-out infinite alternate;
        box-shadow: var(--shadow-card); }
    @media (prefers-reduced-motion: reduce) { .reco-edge { animation: none; } }

    /* ---- "People you may know" card -------------------------------------
       Dealt out in a sideways rail on the wall and on the members page, so it
       is described once here. Narrow enough that a third card shows past the
       edge of a phone — which is the only thing that tells anybody the row
       scrolls — and every card the same height, so a row of them reads as a
       row rather than as a broken fence. */
    /* The card fills the column the rail deals it — three to a screen —
       rather than carrying a width of its own. */
    .reco-card { width: auto; min-width: 0; padding: 0 .5rem .6rem; border-radius: .9rem;
        display: flex; flex-direction: column; align-items: stretch; text-align: center;
        overflow: hidden; border: 1px solid var(--color-gray-100);
        transition: box-shadow .28s cubic-bezier(.22,1,.36,1), border-color .28s cubic-bezier(.22,1,.36,1); }
    .reco-card:hover { border-color: var(--color-brand-200); box-shadow: 0 10px 26px -18px rgb(0 0 0 / .6); }
    @media (prefers-reduced-motion: reduce) { .reco-card { transition: none; } }
    /* ---- The band a person is introduced on ------------------------------
       A cover of their own if they have set one; otherwise the house green,
       deep and slowly turning — the same tide everything else here rides,
       and a colour that says "this app" rather than a random tint that says
       nothing.

       ONE HEIGHT, everywhere. A member's field was 60px on a post, 100px on
       a member card and 176px on their profile, which made the same picture
       read as three different features — and at 60px a landscape photo is a
       letterbox slit you cannot recognise a farm in. The house band is 7rem
       on a phone and 9rem once there is room; a surface overrides only its
       bleed, never its depth. The narrow measure is for the suggestion tile
       in the sideways rail, which is a third of a screen wide and would be a
       portrait if it took the full one.

       The picture always FILLS the band — object-fit:cover, cropped to the
       frame, never squashed to it — and is anchored at the same point its
       owner chose on their profile. Every cover in the app is far larger
       than any band it lands in, so it is only ever scaled down: what shows
       is the file's own resolution. */
    :root { --cover-h: 7rem; --cover-h-sm: 3.5rem; }
    @media (min-width: 640px) { :root { --cover-h: 9rem; } }
    .mem-cover { display: block; overflow: hidden; height: var(--cover-h);
        background: linear-gradient(120deg, #16220f, #2f5219 32%, #3d6823 52%, #24380f 76%, #16220f);
        background-size: 260% 260%;
        animation: mcDrift 15s ease-in-out infinite alternate; }
    @keyframes mcDrift { from { background-position: 0% 50%; } to { background-position: 100% 50%; } }
    .mem-cover img { width: 100%; height: 100%; object-fit: cover; display: block; }
    /* A cover photo is the cover; nothing needs to drift under it. */
    .mem-cover:has(img) { animation: none; }
    @media (prefers-reduced-motion: reduce) { .mem-cover { animation: none; } }
    /* The suggestion tile's own measure — the one place the house depth does
       not fit, because the card it crowns is a third of a screen wide. */
    .reco-top { height: var(--cover-h-sm); margin: 0 -.5rem 0; }
    .reco-who { display: block; min-width: 0; }
    .reco-face { display: flex; justify-content: center; margin-top: -1.5rem; }
    .reco-face .avatar { width: 3rem; height: 3rem; font-size: .95rem; --av-ring: 2.5px;
        box-shadow: 0 6px 16px -10px rgb(0 0 0 / .8); }
    .reco-name { display: block; margin-top: .4rem; font-size: .8rem; font-weight: 800; line-height: 1.25;
        color: var(--color-gray-900); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    /* One line of reason. Two lines were held open so neighbouring cards
       ended level; the row is a grid now and they end level anyway. */
    .reco-why { display: block; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
        margin-top: .1rem; font-size: .68rem; font-weight: 600; line-height: 1.45;
        color: var(--color-brand-700); }
    html.dark .reco-card { border-color: #2b3a1c; }
    html.dark .reco-name { color: #e8efe1; }
    html.dark .reco-why { color: #a5c97e; }
    /* The buttons: same width, same height, at the foot of every card, and
       both of them a line with nothing inside it — the shape the wall's own
       New post wears. Green is the errand the card is for; grey is the
       lighter gesture under it, so one card does not read as two decisions
       shouting at each other. */
    .reco-acts { margin-top: auto; padding-top: .5rem; display: grid; gap: .3rem; }
    .reco-acts .conn-btn { background: transparent; border: 1.5px solid var(--color-brand-600);
        color: var(--color-brand-700); box-shadow: none; }
    .reco-acts .conn-btn:hover { background: var(--color-brand-50); }
    .reco-acts .reco-follow { background: transparent; border: 1.5px solid var(--color-gray-300);
        color: var(--color-gray-600); }
    .reco-acts .reco-follow:hover { background: var(--color-gray-100); color: var(--color-gray-800); }
    .reco-acts .reco-follow.is-on { background: transparent; border-color: var(--color-gray-300);
        color: var(--color-gray-500); }
    html.dark .reco-acts .conn-btn { border-color: #3f5626; color: #bfe19a; }
    html.dark .reco-acts .conn-btn:hover { background: rgb(61 104 35 / .25); }
    html.dark .reco-acts .reco-follow { border-color: #2b3a1c; color: #a8bd93; }
    html.dark .reco-acts .reco-follow:hover { background: rgb(255 255 255 / .06); color: #cdd8c0; }
    .reco-acts .conn-action { display: grid; gap: .35rem; }
    .reco-acts .conn-action .conn-btn,
    .reco-acts .reco-follow { width: 100%; min-height: 1.95rem; padding: .25rem .5rem;
        font-size: .72rem; font-weight: 800; border-radius: .6rem; }
    /* Accept and Decline arrive as a pair; they share the row rather than
       stacking, because they are one question with two answers. */
    .reco-acts .conn-action:has(.conn-btn + .conn-btn) { grid-template-columns: 1fr 1fr; }
    /* Already connected: the badge says so across the card and the ✕ that
       undoes it sits beside, rather than the badge being hidden and a bare
       cross left standing on its own. */
    .reco-acts .conn-action:has(.badge) { grid-template-columns: 1fr auto; align-items: center; }
    .reco-acts .badge { justify-content: center; }

    .status-cloud-wrap { position: relative; display: inline-block; }
    /* A card carrying a cloud needs air above it.
       The cloud floats above its avatar and out of the card entirely, so with
       the usual gap it lands on the bottom edge of the card above — which is
       what made a column of posts look like the tags were falling off. Only
       cards that actually carry one pay for the space. */
    /* Room for the cloud, INSIDE the card.
       As margin it was air above the card, so the bubble hung over the top
       edge and the coloured strip cut it in half — the "glitch pixels" that
       got it moved away from the photo in the first place. As padding, the
       head simply starts lower and the bubble has somewhere of its own to
       be. Only cards that actually carry one pay for it. */
    /* How much room: enough that the air above the bubble is the air under
       the last line, and then a little more.
     *
     * 2.2rem put the bubble 10px under the top edge while the acts row ended
     * 16px above the bottom one (the card's own padding plus the .4rem every
     * button in that row carries under its words) — which is a card that
     * sits high in its own frame. The bubble hangs a fixed distance under
     * the padding, so the padding is the dial. Matching the two exactly still
     * read as tight, because the bubble is a thing that floats on the edge
     * rather than a line of text sitting on the baseline: it wants a little
     * more room over it than under the words at the other end. A phone's
     * cards are tighter all round, so theirs is less. */
    .feed-post:has(.status-cloud), .group-post:has(.status-cloud) { padding-top: 2.75rem; }
    /* The wall's posts follow the room's cards to the pixel: a phone shows
       the same bubble in both places, and one of them sitting lower than the
       other is the kind of difference you cannot name but can see. */
    @media (max-width: 639px) {
        .feed-post:has(.status-cloud), .group-post:has(.status-cloud) { padding-top: 2.5rem; }
    }
    /* And the other end of the same sum: the topic card gives back the .4rem
       its buttons carry, exactly as the wall's posts do. */
    .group-post:has(.topic-acts) { padding-bottom:.35rem; }
    @media (min-width: 640px) {
        .group-post:has(.topic-acts) { padding-bottom:.6rem; }
    }
    /* Chat bubble above the avatar, with a tail pointing down at the photo. */
    .status-cloud {
        position: absolute; left: 0; right: auto; bottom: calc(100% + .3rem);
        width: max-content; max-width: 9rem;
        background: #fff; border: 1px solid var(--color-gray-200); border-radius: .7rem;
        padding: .2rem .55rem; box-shadow: 0 3px 10px rgb(0 0 0 / .14); z-index: 4; pointer-events: none; }
    .status-cloud-text { display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        text-align: left;
        font-size: .62rem; font-weight: 600; line-height: 1.3; color: #374151; }
    /* The tail: a little square of the bubble, turned on its corner.
     *
     * It was a border-triangle carrying a drop-shadow filter, and that pair
     * frays: the filter haloes the triangle's transparent side borders, and
     * its flat fill met the bubble's own 1px border in a seam — together,
     * a scatter of stray pixels under the cloud. This inherits the bubble's
     * fill and border instead, so it is the same object in every theme and
     * every variant, and there is no filter to fringe. */
    .status-cloud::after { content: ''; position: absolute; left: .9rem; bottom: -.3rem;
        width: .55rem; height: .55rem; transform: rotate(45deg);
        background: inherit; border: inherit; border-top: 0; border-left: 0;
        border-bottom-right-radius: .12rem; }
    /* One more bubble, adrift under the tail: a diamond alone is the tail of
       a SPEECH bubble, and what hangs over these faces is a thought. */
    .status-cloud::before { content: ''; position: absolute; left: .55rem; bottom: -.82rem;
        width: .33rem; height: .33rem; border-radius: 999px;
        background: inherit; border: inherit; }
    /* And it floats, the way a thought does — a slow rise and settle with the
       faintest turn in it, so the bubble reads as hanging over the face
       rather than bolted to it.
       Two things are deliberately left out. The composer's own cloud is a
       BUTTON ("what's on your mind?") and its hover lift is a transform an
       animation would win against; and the clouds are desynchronised by
       negative delays, because a column of them all rising together reads as
       a machine rather than as a dozen people thinking. */
    .status-cloud { animation: cloudFloat 4.6s ease-in-out infinite; will-change: transform; }
    @keyframes cloudFloat {
        0%   { transform: translateY(0) rotate(-.7deg); }
        50%  { transform: translateY(-3.5px) rotate(.7deg); }
        100% { transform: translateY(0) rotate(-.7deg); }
    }
    .comp-me .status-cloud, .dash-me .status-cloud { animation: none; }
    .feed-post:nth-child(3n+2) .status-cloud, .group-post:nth-child(3n+2) .status-cloud,
    .mc-card:nth-of-type(3n+2) .status-cloud { animation-delay: -1.5s; }
    .feed-post:nth-child(3n) .status-cloud, .group-post:nth-child(3n) .status-cloud,
    .mc-card:nth-of-type(3n) .status-cloud { animation-delay: -3s; }
    @media (prefers-reduced-motion: reduce) { .status-cloud { animation: none; } }
    html.dark .status-cloud { background: #232a1c; border-color: #3a4a2c; }
    html.dark .status-cloud-text { color: #dbe6cf; }

    /* --- Online presence dot on avatars --- */
    .avatar-online-wrap { position: relative; display: inline-block; line-height: 0; vertical-align: middle; }
    .avatar-online-dot { position: absolute; bottom: 0; right: 0; width: .8rem; height: .8rem;
        border-radius: 9999px; background: #22c55e; border: 2px solid #fff; box-sizing: border-box;
        box-shadow: 0 0 0 .5px rgb(0 0 0 / .06); }
    html.dark .avatar-online-dot { border-color: #171f10; }

    /* --- Profile photo album --- */
    .profile-photos-grid { display:grid; grid-template-columns:repeat(3, 1fr); gap:.5rem; }
    @media (min-width:640px) { .profile-photos-grid { grid-template-columns:repeat(4, 1fr); } }
    .profile-photo-tile { position:relative; aspect-ratio:1; border-radius:.6rem; overflow:hidden;
        background:var(--color-gray-100); }
    .profile-photo-tile img { width:100%; height:100%; object-fit:cover; cursor:pointer; display:block;
        transition:transform .3s var(--ease-house, ease); }
    .profile-photo-tile:hover img { transform:scale(1.05); }
    .profile-photo-del { position:absolute; top:.4rem; right:.4rem; width:1.85rem; height:1.85rem;
        border-radius:9999px; border:0; background:rgb(0 0 0 / .55); color:#fff; display:flex;
        align-items:center; justify-content:center; cursor:pointer; opacity:0;
        transition:opacity .15s var(--ease-house, ease), background .15s ease; }
    .profile-photo-tile:hover .profile-photo-del { opacity:1; }
    .profile-photo-del:hover { background:#ef4444; }
    @media (hover:none) { .profile-photo-del { opacity:1; } }

    /* --- Profile video album --- */
    /* The gallery picker's card, worn here: two across on a phone, a square
       clip with its words on a line of their own below — the same shape the
       wall's "from the gallery" sheet draws a video in. */
    .profile-videos-grid { display:grid; grid-template-columns:repeat(2, 1fr); gap:.6rem; }
    @media (min-width:768px) { .profile-videos-grid { grid-template-columns:repeat(3, 1fr); } }
    .profile-video-tile { position:relative; display:block; border-radius:.7rem; overflow:hidden;
        border:1px solid var(--color-gray-200); background:var(--color-white);
        transition:transform .28s cubic-bezier(.22,1,.36,1), border-color .28s cubic-bezier(.22,1,.36,1),
            box-shadow .28s cubic-bezier(.22,1,.36,1); }
    .profile-video-tile:hover { transform:translateY(-2px); border-color:#a8cc7e;
        box-shadow:0 8px 20px -12px rgb(0 0 0 / .45); }
    html.dark .profile-video-tile { background:#1c2416; border-color:#2b3a1c; }
    .pvt-shot { position:relative; display:block; aspect-ratio:1; overflow:hidden;
        background:#10160c center / cover no-repeat; }
    .pvt-shot .profile-video-el { position:absolute; inset:0; background:transparent; }
    /* The clapperboard pill the picker's clips wear, same corner. */
    .pvt-badge { z-index:2; position:absolute; left:.3rem; top:.3rem; display:inline-flex; align-items:center;
        gap:.15rem; padding:.1rem .35rem; border-radius:999px; background:rgb(17 24 39 / .72);
        color:#fff; font-size:.62rem; font-weight:800; letter-spacing:.02em; pointer-events:none; }
    .pvt-meta { display:block; padding:.45rem .6rem .55rem; }
    .pvt-name { display:block; font-size:.7rem; font-weight:700; color:var(--color-gray-700);
        overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    html.dark .pvt-name { color:#e5e9f5; }
    .pvt-sub { display:block; font-size:.62rem; color:var(--color-gray-400);
        overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    .profile-video-tile-legacy { position:relative; aspect-ratio:16/9; border-radius:.6rem; overflow:hidden;
        background:#000; }
    .profile-video-el { width:100%; height:100%; object-fit:cover; display:block; background:#000; }
    .profile-video-del { position:absolute; top:.4rem; right:.4rem; width:1.85rem; height:1.85rem;
        border-radius:9999px; border:0; background:rgb(0 0 0 / .55); color:#fff; display:flex;
        align-items:center; justify-content:center; cursor:pointer; opacity:0; z-index:2;
        transition:opacity .15s var(--ease-house, ease), background .15s ease; }
    .profile-video-tile:hover .profile-video-del { opacity:1; }
    .profile-video-del:hover { background:#ef4444; }
    @media (hover:none) { .profile-video-del { opacity:1; } }
    /* Upload-in-progress card while ffmpeg compresses the clip server-side.
       display lives on :not(.hidden) so the `hidden` utility actually hides it
       (a bare display:flex here is unlayered and would beat .hidden). */
    .profile-video-uploading { align-items:center; gap:.6rem; padding:.85rem 1rem;
        border:1px dashed var(--color-gray-300); border-radius:.7rem; color:#5b6472; font-size:.85rem;
        font-weight:600; margin-bottom:.75rem; background:var(--color-gray-50); }
    .profile-video-uploading:not(.hidden) { display:flex; }
    /* Night palette is the plaza's green-tinted dark, not the old blue. */
    html.dark .profile-video-uploading { background:#1a2213; border-color:#2b3a1c; color:#c3cdb5; }
    .profile-video-spin { width:1.1rem; height:1.1rem; border-radius:9999px; border:2px solid var(--color-brand-200);
        border-top-color:var(--color-brand-600); animation:pv-spin .7s linear infinite; flex:0 0 auto; }
    @keyframes pv-spin { to { transform:rotate(360deg); } }
    @media (prefers-reduced-motion: reduce) { .profile-video-spin { animation-duration:1.6s; } }

    /* --- "People you may know" scroller arrows --- */
    .reco-scroller { position: relative; }
    .reco-nav { position: absolute; top: 50%; transform: translateY(-50%); z-index: 3;
        width: 2rem; height: 2rem; border-radius: 9999px; background: #fff;
        border: 1px solid var(--color-gray-200); box-shadow: 0 2px 10px rgb(0 0 0 / .14);
        display: flex; align-items: center; justify-content: center; color: var(--color-gray-600);
        cursor: pointer; transition: opacity .2s var(--ease-house, ease), background .15s ease, color .15s ease; }
    .reco-nav:hover { background: var(--color-brand-50); color: var(--color-brand-700); }
    .reco-nav:active { transform: translateY(-50%) scale(.92); }
    .reco-nav-left { left: -.5rem; }
    .reco-nav-right { right: -.5rem; }
    .reco-nav[hidden] { opacity: 0; pointer-events: none; }
    html.dark .reco-nav { background: #1a2414; border-color: #2b3a1c; color: #cde0b8; }
    html.dark .reco-nav:hover { background: #24331a; color: #e5e9df; }

    /* --- The composer, as the homepage draws it ---
       The dashboard keeps .dash-me inside its own page style, so no other
       page can reuse it. This is the same idea living where the community
       can see it: your face, what's on your mind floating above it, and a
       field with room to actually write in. */
    .comp-me { position: relative; border: 0; background: none; padding: 0; cursor: pointer; max-width: 3.5rem; }
    /* The cloud is decoration everywhere else, so the shared rule turns
       pointer events off. Here it is half the button. */
    /* The same cloud a post carries — same width, same type, same tail. It
       was grown here and then read as a different component sitting above a
       column of small ones, and a wider box only truncated later, not less.
       All this rule adds is that yours can be tapped. */
    .comp-me .status-cloud { pointer-events: auto;
        transition: transform .28s cubic-bezier(.22,1,.36,1), box-shadow .28s cubic-bezier(.22,1,.36,1); }
    .comp-me:hover .status-cloud { transform: translateY(-1px);
        box-shadow: 0 6px 16px rgb(0 0 0 / .18); }
    /* Nothing said yet: a dashed invitation rather than a statement. */
    .comp-me .status-cloud.is-empty { background: var(--color-brand-50);
        border-color: var(--color-brand-300); border-style: dashed; }
    .comp-me .status-cloud.is-empty .status-cloud-text { color: var(--color-brand-700); font-weight: 700; }
    /* Room for the cloud to sit above the avatar without meeting the card. */
    .comp-row { padding-top: 1.35rem; }
    /* A box you can see yourself writing in — and one the invitation fits
       inside: at 360px the old two-row field cut its own placeholder off. */
    .comp-box { min-height: 6.5rem; font-size: .85rem; line-height: 1.55; resize: vertical; }
    .comp-box::placeholder { font-size: .82rem; }
    /* The action row wraps instead of squeezing the send button into a
       two-line sliver next to four icons on a narrow phone. */
    .comp-bar { display: flex; align-items: center; justify-content: space-between; gap: .5rem; flex-wrap: wrap; }
    /* Post is the decision, and it takes the foot of the card across its
       whole width — nothing on that line to weigh it against. */
    /* The same living green every big community button wears — the
       invitations at the foot of a card, the discussions' own buttons —
       drifting on the shared tide rather than sitting flat. */
    .comp-send { display: flex; align-items: center; justify-content: center;
        width: 100%; margin-top: .7rem; border: 0; color: #fff;
        background-image: linear-gradient(120deg, #2f5219, #4a7c2a 28%, #6b9f3d 52%, #4a7c2a 76%, #2f5219);
        background-size: 220% 100%;
        animation: gradSweep var(--sw-t, 11s) ease-in-out infinite alternate; }
    .comp-send:hover { filter: brightness(1.06); color: #fff; }
    @media (prefers-reduced-motion: reduce) { .comp-send { animation: none; } }
    @media (prefers-reduced-motion: reduce) { .comp-me .status-cloud { transition: none; } }

    /* --- Rail cards (your discussions, what's new in the blog) --- */
    .rail-row { display: flex; gap: .6rem; align-items: flex-start; padding: .5rem .35rem;
        border-radius: .6rem; border-top: 1px solid var(--color-gray-100);
        transition: background-color .28s cubic-bezier(.22,1,.36,1); }
    .rail-row:first-of-type { border-top: 0; }
    .rail-row:hover { background: var(--color-gray-50); }
    .rail-thumb { width: 2.9rem; height: 2.9rem; flex: 0 0 auto; border-radius: .6rem; overflow: hidden;
        display: flex; align-items: center; justify-content: center; font-size: 1.15rem;
        background: linear-gradient(120deg, var(--color-brand-100), var(--color-brand-50)); }
    .rail-thumb img { width: 100%; height: 100%; object-fit: cover; }
    /* Two lines of headline, then an ellipsis — a rail is a glance, not a read. */
    .rail-title { font-size: .8rem; font-weight: 600; color: var(--color-gray-900); line-height: 1.3;
        display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
    .rail-meta { display: block; font-size: .688rem; color: var(--color-gray-400); margin-top: .15rem; }
    html.dark .rail-row:hover { background: #1c2416; }
    @media (prefers-reduced-motion: reduce) { .rail-row { transition: none; } }

    /* --- Reduced motion: kill every plaza animation --- */
    @media (prefers-reduced-motion: reduce) {
        .post-enter, .post-enter::after, .attach-chip, .group-joined-tag:not(.hidden), .wall-reply-form,
        .react-btn.just-reacted .e, .react-count.tick, #composerCard.is-entering, #loadMoreBtn .dot { animation:none !important; }
        .react-btn, .group-join-btn, .btn-open, .reply-shell, .reply-send, .emoji-btn, .emoji-pop, .plaza-clamp-body,
        #postsWrap, .disc-jumps, .post-reply-form, #joinPrompt, .group-post.is-removing,
        .thread-fold, .thread-toggle .th-chev { transition:none !important; }
    }

    /* ---- The rank badge ----
       Fifty ranks, ten arcs, one chip. The arc gives the colour — a seed is
       barely there, a farmer is the house green, a legend is gold that
       drifts on the shared tide — so a glance up a comment thread reads as a
       gradient of standing without a word. Small on purpose: it lives beside
       names, and a badge that outshines the name has the relationship
       backwards. */
    .rankb { display:inline-flex; align-items:center; gap:.24rem; max-width:11.5rem;
        padding:.08rem .42rem .08rem .3rem; border-radius:999px; border:1px solid transparent;
        font-size:.62rem; font-weight:800; letter-spacing:.01em; line-height:1.25;
        text-decoration:none; vertical-align:middle; white-space:nowrap;
        transition:transform .28s cubic-bezier(.22,1,.36,1), box-shadow .28s cubic-bezier(.22,1,.36,1); }
    .rankb:hover { transform:translateY(-1px); box-shadow:0 3px 8px -4px rgb(0 0 0 / .35); }
    .rankb-e { font-size:.72rem; line-height:1; }
    /* The number never truncates; the title gives way first. */
    .rankb-lv { flex:none; font-variant-numeric:tabular-nums; }
    .rankb-t { min-width:0; overflow:hidden; text-overflow:ellipsis; opacity:.85; font-weight:700; }
    .rankb-t::before { content:'· '; }
    .rankb-big { font-size:.74rem; padding:.2rem .6rem .2rem .45rem; max-width:14rem; }
    .rankb-big .rankb-e { font-size:.95rem; }
    /* The ten arcs, seed to legend. */
    .rankb-a1 { background:#f1f3ee; border-color:#dde3d6; color:#5b6652; }
    .rankb-a2 { background:#eaf3e0; border-color:#d3e4bf; color:#4a6b2a; }
    .rankb-a3 { background:#dff0cf; border-color:#bfdc9e; color:#3d6823; }
    .rankb-a4 { background:#d3ecc0; border-color:#a9d383; color:#2f5219; }
    .rankb-a5 { background:#fdeaf1; border-color:#f6c6d8; color:#a2355f; }
    .rankb-a6 { background:#fdf3d8; border-color:#f2dc9d; color:#8a6100; }
    .rankb-a7 { background:#f4ead9; border-color:#e2cba2; color:#7c5215; }
    .rankb-a8 { background:#e2f1f0; border-color:#b8dcd9; color:#0f6b64; }
    .rankb-a9 { background:#efe9fb; border-color:#d5c6f2; color:#6534b8; }
    /* The legend arc glows: gold that drifts on the app's shared tide. */
    .rankb-a10 { color:#5c4300; border-color:#e3c25a;
        background-image:linear-gradient(110deg, #fdeeb8, #f7d878 30%, #fdf3cf 55%, #f2ce6a 80%, #fdeeb8);
        background-size:220% 100%; animation:gradSweep 10s ease-in-out infinite alternate; }
    html.dark .rankb-a1 { background:#232a1e; border-color:#39422f; color:#a9b59c; }
    html.dark .rankb-a2 { background:#25301a; border-color:#3c4d27; color:#b4cf94; }
    html.dark .rankb-a3 { background:#283a17; border-color:#456327; color:#bfe19a; }
    html.dark .rankb-a4 { background:#2c4318; border-color:#4d7328; color:#cfe8b0; }
    html.dark .rankb-a5 { background:#3c2230; border-color:#6d3a54; color:#f0a9c8; }
    html.dark .rankb-a6 { background:#3a3018; border-color:#6a5522; color:#eec155; }
    html.dark .rankb-a7 { background:#362b1b; border-color:#5f4b2c; color:#dbb377; }
    html.dark .rankb-a8 { background:#173230; border-color:#265a55; color:#7fd0c8; }
    html.dark .rankb-a9 { background:#2b2140; border-color:#4b3a72; color:#c3aaf0; }
    html.dark .rankb-a10 { color:#3a2b00; }
    @media (prefers-reduced-motion: reduce) { .rankb { transition:none; } .rankb-a10 { animation:none; } }

    /* THE PODIUM CHIP — a seat in the top twenty, worn in metal.

       The level chip beside it is a walk anyone can finish; this is a seat
       somebody else has to leave. So it is struck in metal rather than
       painted: a soft diagonal sheen across each chip, and on the three
       rarest metals that sheen drifts on the app's shared tide, which is
       what makes a diamond read as diamond from across a card.

       Same height and radius as the level chip so a name wearing both keeps
       one clean line. The number is tabular so #1 and #11 hold the same
       column, and it never wraps or truncates — it is the whole message. */
    .topb { display:inline-flex; align-items:center; gap:.2rem; flex:none;
        /* Sized to its four characters no matter what holds it. Several of
           these cards are column flexboxes, and a flex item in one is
           stretched the full width of the card unless it says otherwise. */
        width:max-content; align-self:center;
        padding:.08rem .4rem .08rem .28rem; border-radius:999px; border:1px solid transparent;
        font-size:.62rem; font-weight:900; letter-spacing:.01em; line-height:1.25;
        text-decoration:none; vertical-align:middle; white-space:nowrap;
        background-size:220% 100%;
        transition:transform .28s cubic-bezier(.22,1,.36,1), box-shadow .28s cubic-bezier(.22,1,.36,1); }
    .topb:hover { transform:translateY(-1px); box-shadow:0 3px 8px -4px rgb(0 0 0 / .35); }
    .topb-m { display:inline-flex; width:.72rem; height:.72rem; flex:none; opacity:.9; }
    .topb-m svg { width:100%; height:100%; }
    .topb-n { font-variant-numeric:tabular-nums; }
    /* What a screen reader hears and an eye never sees. Ours rather than the
       framework's: these pages build their stylesheet separately, and a
       utility that does not survive that build turns the sentence visible. */
    .topb-say, .plaza-say { position:absolute; width:1px; height:1px; padding:0; margin:-1px;
        overflow:hidden; clip:rect(0 0 0 0); clip-path:inset(50%); white-space:nowrap; border:0; }
    .topb-big { font-size:.74rem; padding:.16rem .56rem .16rem .4rem; }
    .topb-big .topb-m { width:.92rem; height:.92rem; }

    /* The six metals. The top three drift; the lower three hold still, which
       is itself part of the ladder — movement is the last thing you earn. */
    .topb-diamond { color:#0b5f73; border-color:#7dd8ee;
        background-image:linear-gradient(110deg,#e8fbff,#8fe6f7 28%,#ffffff 48%,#6ed3ea 72%,#e8fbff);
        animation:gradSweep 7s ease-in-out infinite alternate; }
    .topb-platinum { color:#45409b; border-color:#c2c5ea;
        background-image:linear-gradient(110deg,#f8f7ff,#cfd2ef 30%,#ffffff 52%,#c6c9ec 78%,#f8f7ff);
        animation:gradSweep 9s ease-in-out infinite alternate; }
    .topb-gold { color:#7a5504; border-color:#e2bd54;
        background-image:linear-gradient(110deg,#fdf3d4,#f0c453 30%,#fff9e6 55%,#eabb4c 80%,#fdf3d4);
        animation:gradSweep 11s ease-in-out infinite alternate; }
    .topb-silver { color:#4a5563; border-color:#c6ccd4;
        background-image:linear-gradient(115deg,#fbfcfd,#ccd2d9 45%,#f4f6f8); }
    .topb-bronze { color:#7a3f14; border-color:#d3a075;
        background-image:linear-gradient(115deg,#fbead9,#dc9e6d 45%,#f7e0cb); }
    .topb-nickel { color:#45564b; border-color:#bac7bf;
        background-image:linear-gradient(115deg,#f3f7f4,#bbc8c0 45%,#eaefeb); }
    /* Off the podium: the shape without the prize. Used on the board's own
       rows, where a place past the twentieth still has to be readable. */
    .topb-plain { color:var(--color-gray-500); border-color:var(--color-gray-200);
        background-image:linear-gradient(115deg,#fbfbfc,#eef0f2 45%,#f8f9fa); }
    html.dark .topb-plain { color:#9aa79c; border-color:#2f3a26;
        background-image:linear-gradient(115deg,#1c2417,#232c1d 45%,#1e2719); }

    html.dark .topb-diamond { color:#c9f5ff; border-color:#2b7e93;
        background-image:linear-gradient(110deg,#0d3b47,#1c6e83 28%,#2b8ba3 48%,#175f73 72%,#0d3b47); }
    html.dark .topb-platinum { color:#dcdbff; border-color:#4f4b96;
        background-image:linear-gradient(110deg,#25234a,#3a3673 30%,#4b4795 52%,#332f66 78%,#25234a); }
    html.dark .topb-gold { color:#ffe9ae; border-color:#8a6a1c;
        background-image:linear-gradient(110deg,#3b2f0d,#6b5416 30%,#8a6d1f 55%,#5a4712 80%,#3b2f0d); }
    html.dark .topb-silver { color:#dfe4ea; border-color:#5b6472;
        background-image:linear-gradient(115deg,#2b3038,#454c57 45%,#343a43); }
    html.dark .topb-bronze { color:#f6d3b3; border-color:#7d4f2a;
        background-image:linear-gradient(115deg,#3a2313,#65401f 45%,#452a17); }
    html.dark .topb-nickel { color:#d3e0d7; border-color:#4d5c53;
        background-image:linear-gradient(115deg,#252d28,#3b473f 45%,#2c352f); }

    @media (prefers-reduced-motion: reduce) {
        .topb { transition:none; animation:none; }
    }

    /* ---- A cover on a post card ------------------------------------------
       The band sits in the card's flow, at the top, bled back out through the
       padding to the card's own edges — and then the FACE alone is lifted
       into it. Absolutely positioned over the head instead, it took the name
       and the level chips with it and printed them across the photo: in a
       row this shallow the text sits at the avatar's own height, so anything
       that moves the avatar up must leave its neighbours behind.

       Squared at the bottom, because a band wearing four rounded corners
       reads as a shelf floating inside the card rather than its top edge. */
    .fp-cover { margin: -1rem -1rem .6rem;
        border-radius: inherit; border-bottom-left-radius: 0; border-bottom-right-radius: 0; }
    /* A topic card is padded p-3 on a phone and p-4 from sm, so its band has
       to bleed by whichever of those is in force or it stops short of the
       card's own edge by three pixels on one side. */
    .group-post .fp-cover { margin: -.75rem -.75rem .55rem; }
    @media (min-width: 640px) { .group-post .fp-cover { margin: -1rem -1rem .6rem; } }
    /* The card goes back to its plain padding: the cloud no longer needs a
       clearing above the head, because it now hangs over the photo. Written
       after the cloud's own rule, which asks for the same property at the
       same weight and does not know there is a picture. */
    .feed-post:has(.fp-cover), .group-post:has(.fp-cover) { padding-top: 1rem; }
    @media (max-width: 639px) { .group-post:has(.fp-cover) { padding-top: .75rem; } }
    /* A face worth the band it stands on.
       At avatar-md the head was a 40px disc against a 112px photograph, with
       the name and the facts line stacked beside it running twice its height
       — a small round afterthought rather than the person the card is about.
       At 3.5rem it spans both lines, which is what makes the head read as one
       block instead of a picture with writing next to it. */
    .feed-post:has(.fp-cover) > header .avatar,
    .group-post:has(.fp-cover) > header .avatar {
        width: 3.5rem; height: 3.5rem; font-size: 1.15rem; --av-ring: 3px;
        box-shadow: 0 6px 16px -11px rgb(0 0 0 / .8); }
    /* Only the first thing in the head — the face, or the wrap holding the
       face and its cloud — climbs onto the photo, and it climbs exactly half
       its own height, so the ring sits ON the edge rather than near it. The
       name keeps the band's .6rem of clearance and never leans on the photo. */
    .feed-post:has(.fp-cover) > header > :first-child,
    .group-post:has(.fp-cover) > header > :first-child { margin-top: -2.4rem; }
    /* Delete and permalink now land on somebody's field, so they carry a
       pane of the card's own surface to be read against. */
    .feed-post:has(.fp-cover) .fp-del, .feed-post:has(.fp-cover) .fp-open,
    .group-post:has(.fp-cover) .post-delete-btn {
        background: rgb(255 255 255 / .86); border-radius: 999px;
        box-shadow: 0 2px 8px -5px rgb(0 0 0 / .9); }
    html.dark .feed-post:has(.fp-cover) .fp-del, html.dark .feed-post:has(.fp-cover) .fp-open,
    html.dark .group-post:has(.fp-cover) .post-delete-btn {
        background: rgb(21 27 18 / .82); }
</style>
{{-- The podium's data rides with the podium's styles, so the two can never
     arrive on a page apart: every card built in JavaScript on a page that can
     draw this chip can also ask who is wearing one. --}}
@include('community.partials.top-badge-js')