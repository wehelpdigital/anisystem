{{-- "Ang Plaza" — shared community design system (avatars, reactions, emoji
     popover, join morph, composer shells, liveliness). Include once per page. --}}
<style>
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

    /* --- Initials avatars: crc32(lower(name))%8 → av-h0..7. Circle = person, squircle = place. --- */
    .avatar { display:inline-flex; align-items:center; justify-content:center; flex-shrink:0;
        border-radius:9999px; background:var(--av-bg); color:var(--av-fg);
        font-family:var(--font-heading); font-weight:800; letter-spacing:.02em;
        box-shadow: inset 0 0 0 1.5px rgb(255 255 255 / .35); user-select:none; }
    .avatar-sm { width:1.75rem; height:1.75rem; font-size:.6rem; }
    .avatar-md { width:2.5rem;  height:2.5rem;  font-size:.8rem; }
    .avatar-lg { width:3.25rem; height:3.25rem; font-size:1.05rem; }
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
    html.dark .group-hero::before { opacity:.3; }
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
    .attach-chip img { width:3rem; height:3rem; object-fit:cover; border-radius:.5rem; }
    html.dark .attach-chip img { box-shadow: inset 0 0 0 1px rgb(255 255 255 / .1); }
    .post-media { position:relative; display:inline-block; margin-top:.5rem; }
    .post-media img { border-radius:.75rem; max-height:18rem; width:auto; max-width:100%; display:block; }
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
    .wall-comment-form .reply-shell .js-reply-cancel { margin-left:auto; }
    .wall-comment-form .reply-shell .js-reply-cancel ~ .reply-send { margin-left:0; }
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
    .write-fab { position:fixed; right:1rem; bottom:4.75rem; z-index:30; width:3.25rem; height:3.25rem; border:0;
        border-radius:9999px; background:var(--color-brand-600); color:#fff; display:inline-flex;
        align-items:center; justify-content:center; box-shadow:var(--shadow-card-lg); cursor:pointer;
        transition: opacity var(--dur) var(--ease-house), transform var(--dur) var(--ease-house); }
    .write-fab.is-hidden { opacity:0; transform:scale(.6); pointer-events:none; }
    /* On phones the messenger launcher owns the same corner (its dock rides at
       4.5rem above the tab bar) — the write FAB steps up one storey so both
       stay tappable instead of stacking on the same spot. */
    @media (max-width:767px) {
        .write-fab { bottom:calc(8.5rem + env(safe-area-inset-bottom, 0px)); }
    }

    /* --- Join gate: invitation card, live melt, animated reply-form gate --- */
    #joinPrompt { border:1.5px dashed var(--color-brand-300); background:var(--color-brand-50); border-radius:1rem;
        overflow:hidden; transition: max-height .32s var(--ease-house), opacity var(--dur) var(--ease-house),
            margin .32s var(--ease-house), padding .32s var(--ease-house); }
    html.dark #joinPrompt { background:rgb(74 124 42 / .1); border-color:rgb(107 159 61 / .45); }
    #joinPrompt.gate-out { max-height:0 !important; opacity:0; margin-bottom:0; padding-top:0; padding-bottom:0; }
    #composerCard.is-entering { animation: postIn .32s var(--ease-house), ringPulse 1.2s var(--ease-house) .2s; }
    @keyframes ringPulse { 0%,100%{box-shadow:var(--shadow-card)} 40%{box-shadow:0 0 0 4px rgb(107 159 61 / .35), var(--shadow-card)} }
    [data-group-member="0"] .post-reply-form { opacity:0; max-height:0; overflow:hidden; margin-top:0; pointer-events:none; }
    .post-reply-form { transition: opacity var(--dur) var(--ease-house), max-height var(--dur) var(--ease-house); max-height:4rem; }

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
    .reply-media img { max-height:12rem; }

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
    .wc-more { display:none; width:1.75rem; height:1.75rem; border:0; border-radius:9999px; background:transparent;
        align-items:center; justify-content:center; color:var(--color-gray-400); cursor:pointer; flex-shrink:0;
        transition: background-color .15s var(--ease-house), color .15s var(--ease-house); }
    .wc-more:hover { background:var(--color-gray-100); color:var(--color-gray-700); }
    @media (max-width: 640px) {
        .wc-actions { width:100%; align-items:flex-start; }
        .wc-actions > .js-wall-reply, .wc-actions > .js-comment-delete { display:none; }
        .wc-more { display:inline-flex; margin-left:auto; }
    }
    .wc-menu-item { display:flex; align-items:center; gap:.7rem; width:100%; padding:.8rem .5rem; border:0;
        background:transparent; border-radius:.6rem; font-size:.95rem; font-weight:700; text-align:left;
        color:var(--color-gray-800); cursor:pointer; transition: background-color .15s var(--ease-house); }
    .wc-menu-item:hover { background:var(--color-gray-50); }
    .wc-menu-item.is-danger { color:#dc2626; }
    .wc-menu-item.is-quiet { color:var(--color-gray-500); font-weight:600; justify-content:center; }

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
    .plaza-accent { position:relative; overflow:hidden; }
    .plaza-accent::before { content:''; position:absolute; inset:0 0 auto 0; height:3px;
        background:linear-gradient(90deg, #3d6823, #6b9f3d 45%, #a8cc7e 75%, transparent);
        background-size:220% 100%; animation:gradSweep 12s ease-in-out infinite alternate; }
    html.dark .plaza-accent::before { opacity:.8; }
    @media (prefers-reduced-motion: reduce) { .plaza-accent::before { animation:none; } }

    /* --- Post-photo skeleton: while a wall/feed picture decodes, its box
       shimmers instead of the layout jumping open around a half-arrived
       image (gallery's sweep, aimed at the plaza). Opt-in via .media-skel on
       .post-media plus the is-loaded hooks on the <img>; the box holds a
       likely photo shape until the real one takes over. --- */
    .media-skel:not(:has(img.is-loaded)):not(.is-gone) { width:min(100%, 24rem); aspect-ratio:16/10; max-height:18rem; }
    .media-skel:not(:has(img.is-loaded)) img { position:absolute; inset:0; }
    .media-skel img { opacity:0; transition:opacity .28s ease; }
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
        .wc-more { width:2.75rem; height:2.75rem; }
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
    .status-cloud-wrap { position: relative; display: inline-block; }
    /* Chat bubble above the avatar, with a tail pointing down at the photo. */
    .status-cloud {
        position: absolute; left: 0; right: auto; bottom: calc(100% + .3rem);
        width: max-content; max-width: 9rem;
        background: #fff; border: 1px solid var(--color-gray-200); border-radius: .7rem;
        padding: .2rem .55rem; box-shadow: 0 3px 10px rgb(0 0 0 / .14); z-index: 4; pointer-events: none; }
    .status-cloud-text { display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        text-align: left;
        font-size: .62rem; font-weight: 600; line-height: 1.3; color: #374151; }
    .status-cloud::after { content: ''; position: absolute; left: .85rem; bottom: -.3rem;
        width: 0; height: 0; border-left: .3rem solid transparent; border-right: .3rem solid transparent;
        border-top: .32rem solid #fff; filter: drop-shadow(0 2px 1px rgb(0 0 0 / .06)); }
    html.dark .status-cloud { background: #232a1c; border-color: #3a4a2c; }
    html.dark .status-cloud-text { color: #dbe6cf; }
    html.dark .status-cloud::after { border-top-color: #232a1c; }

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
    .profile-videos-grid { display:grid; grid-template-columns:repeat(2, 1fr); gap:.5rem; }
    @media (min-width:640px) { .profile-videos-grid { grid-template-columns:repeat(3, 1fr); } }
    .profile-video-tile { position:relative; aspect-ratio:16/9; border-radius:.6rem; overflow:hidden;
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

    /* --- Reduced motion: kill every plaza animation --- */
    @media (prefers-reduced-motion: reduce) {
        .post-enter, .post-enter::after, .attach-chip, .group-joined-tag:not(.hidden), .wall-reply-form,
        .react-btn.just-reacted .e, .react-count.tick, #composerCard.is-entering, #loadMoreBtn .dot { animation:none !important; }
        .react-btn, .group-join-btn, .btn-open, .reply-shell, .reply-send, .emoji-btn, .emoji-pop, .plaza-clamp-body,
        #postsWrap, .write-fab, .post-reply-form, #joinPrompt, .group-post.is-removing,
        .thread-fold, .thread-toggle .th-chev, .wc-more, .wc-menu-item { transition:none !important; }
    }
</style>
