{{-- Messenger-style dock: a launcher that opens a conversation list and sticky
     chat windows. Global — include once in the app layout for logged-in users.
     Exposes window.plazaOpenDm(userId, name).

     The launcher floats bottom-right, except where the page offers it a chair:
     the community nav renders #msgrSeat and the launcher moves into that row
     instead (see the seating step in the script below). --}}
<div id="msgrDock" aria-live="polite">
    <div class="msgr-panel hidden" id="msgrPanel">
        <div class="msgr-panel-head">
            <span class="msgr-panel-title">Messages</span>
            <button type="button" class="msgr-x" data-msgr-close-panel aria-label="Close">✕</button>
        </div>
        <div class="msgr-panel-body" id="msgrThreads">
            {{-- Skeleton rows in the shape of the list they become — the JS
                 paints the same ones on every reopen while the fetch is out. --}}
            <div class="msgr-skel"><span class="msgr-skel-av"></span><span class="msgr-skel-mid"><span class="msgr-skel-line w1"></span><span class="msgr-skel-line w2"></span></span></div>
            <div class="msgr-skel"><span class="msgr-skel-av"></span><span class="msgr-skel-mid"><span class="msgr-skel-line w1"></span><span class="msgr-skel-line w2"></span></span></div>
            <div class="msgr-skel"><span class="msgr-skel-av"></span><span class="msgr-skel-mid"><span class="msgr-skel-line w1"></span><span class="msgr-skel-line w2"></span></span></div>
        </div>
    </div>
    <div class="msgr-windows" id="msgrWindows"></div>
    <button type="button" class="msgr-launcher" id="msgrLauncher" aria-label="Messages">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.9 9.9 0 01-4.29-.94L3 20l1.05-3.15A7.6 7.6 0 013 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
        {{-- A word only when the button sits in a row of worded buttons; the
             floating circle stays a circle. --}}
        <span class="msgr-launcher-word">Chat</span>
        <span class="msgr-badge hidden" id="msgrBadge">0</span>
    </button>
</div>

{{-- Forward-a-message picker (shared by the dock's per-bubble Forward action) --}}
<div class="sheet hidden" id="msgrForwardSheet" style="--sheet-width:28rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title">Forward message</h3>
        <button data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body">
        <p class="text-sm text-gray-500 mb-2" id="msgrForwardPreview"></p>
        <label class="form-label">Send to a co-farmer</label>
        <div id="msgrForwardList" class="space-y-1 max-h-72 overflow-y-auto rounded-xl border border-gray-100 p-1">
            <p class="text-sm text-gray-400 px-2 py-3 text-center">Loading…</p>
        </div>
    </div>
</div>

<style>
    #msgrDock { position:fixed; right:1rem; bottom:1rem; z-index:90; display:flex; align-items:flex-end; gap:.75rem; }
    /* Clear the mobile bottom tab bar (visible < md) so the launcher doesn't sit on the Account button. */
    @media (max-width:767px) { #msgrDock { bottom:calc(4.5rem + env(safe-area-inset-bottom, 0px)); } }
    /* The white ring is what keeps the launcher legible when a green CTA sits
       right behind it — a green-on-green shadow alone just blended in. */
    .msgr-launcher { position:relative; width:3.25rem; height:3.25rem; border-radius:9999px; border:0;
        background:var(--color-brand-600); color:#fff; cursor:pointer;
        box-shadow:0 0 0 2px #fff, 0 0 0 4px rgb(61 104 35 / .22), 0 10px 26px rgb(0 0 0 / .32);
        display:flex; align-items:center; justify-content:center; transition:transform .15s ease, background .2s ease, box-shadow .2s ease; }
    .msgr-launcher:hover { background:var(--color-brand-700);
        box-shadow:0 0 0 2px #fff, 0 0 0 4px rgb(61 104 35 / .3), 0 12px 30px rgb(0 0 0 / .38); }
    html.dark .msgr-launcher { box-shadow:0 0 0 2px #151b12, 0 0 0 4px rgb(255 255 255 / .16), 0 10px 26px rgb(0 0 0 / .6); }
    html.dark .msgr-launcher:hover { box-shadow:0 0 0 2px #151b12, 0 0 0 4px rgb(255 255 255 / .24), 0 12px 30px rgb(0 0 0 / .65); }
    .msgr-launcher:active { transform:scale(.94); }
    .msgr-launcher-word { display:none; }
    .msgr-badge { position:absolute; top:-2px; right:-2px; min-width:1.15rem; height:1.15rem; padding:0 .3rem;
        border-radius:9999px; background:#ef4444; color:#fff; font-size:.65rem; font-weight:800;
        display:inline-flex; align-items:center; justify-content:center; }
    .msgr-badge.hidden { display:none; }

    /* ---- Seated launcher (community nav row) -----------------------------
       Sat in a row of btn-white controls it has to read as one of them, so
       the circle's size, ring and green are all unwound here rather than the
       button being duplicated — one button means one set of handlers and one
       unread count. The house button values are written out because this
       sheet is parsed after app.css and would otherwise win by order. */
    /* If the seat exists, the floating copy must never be what you see — not
       even for the frame before the script moves it, and not at all if that
       script dies first (an unseated launcher on such a page has no click
       handler yet, so it would only be a button that does nothing). */
    html:has(#msgrSeat) #msgrDock > .msgr-launcher { display:none; }
    .msgr-launcher.is-seated { width:auto; height:auto; min-height:2.25rem; padding:.375rem .75rem;
        border-radius:.5rem; gap:.4rem; font-size:.875rem; font-weight:600; flex-shrink:0;
        background:var(--color-white, #fff); color:var(--color-gray-800, #1f2937);
        border:1px solid var(--color-gray-200, #e5e7eb);
        box-shadow:0 1px 2px rgb(0 0 0 / .05);
        /* The float's own quicker easing reads as a different control next to
           the hamburger; in the row it settles on the house curve. */
        transition:background-color .28s cubic-bezier(.22,1,.36,1), border-color .28s cubic-bezier(.22,1,.36,1), box-shadow .28s cubic-bezier(.22,1,.36,1), transform .28s cubic-bezier(.22,1,.36,1); }
    .msgr-launcher.is-seated:hover { background:var(--color-gray-50, #f9fafb); box-shadow:0 1px 2px rgb(0 0 0 / .05); }
    .msgr-launcher.is-seated svg { width:1.05rem; height:1.05rem; }
    .msgr-launcher.is-seated .msgr-launcher-word { display:inline; }
    .msgr-launcher.is-seated .msgr-badge { top:-.35rem; right:-.35rem; }
    /* Colour comes from the ramp above, which dark mode already flips, so the
       seated button lands on the same grey as the hamburger beside it; only
       the shadow needs a darker night value. */
    html.dark .msgr-launcher.is-seated,
    html.dark .msgr-launcher.is-seated:hover { box-shadow:0 1px 2px rgb(0 0 0 / .4); }
    /* Waiting messages: the button goes green and pulses a ring while the
       count blinks, so the row itself says "someone is talking to you".
       Under reduced motion the pulse stops and the number stays — the news
       is in the badge, never only in the movement. */
    .msgr-launcher.is-seated.has-unread { background:var(--color-brand-600); color:#fff;
        border-color:var(--color-brand-600); animation:msgrSeatPulse 1.4s cubic-bezier(.22,1,.36,1) infinite; }
    html.dark .msgr-launcher.is-seated.has-unread { background:var(--color-brand-600); color:#fff;
        border-color:var(--color-brand-600); }
    .msgr-launcher.is-seated.has-unread:hover { background:var(--color-brand-700); }
    .msgr-launcher.is-seated.has-unread .msgr-badge { animation:msgrSeatBlink 1.4s cubic-bezier(.22,1,.36,1) infinite; }
    @keyframes msgrSeatPulse { 0%, 100% { box-shadow:0 0 0 0 rgb(74 124 42 / .55); } 60% { box-shadow:0 0 0 .5rem rgb(74 124 42 / 0); } }
    @keyframes msgrSeatBlink { 0%, 100% { opacity:1; } 50% { opacity:.25; } }
    @media (prefers-reduced-motion: reduce) {
        .msgr-launcher.is-seated { transition:none; }
        .msgr-launcher.is-seated.has-unread { animation:none; }
        .msgr-launcher.is-seated.has-unread .msgr-badge { animation:none; opacity:1; }
    }
    /* A FIXED box, not one that grows to its contents.
       The panel opens onto skeleton rows and then swaps in the real threads,
       which is a height change — and because the panel is pinned by its
       bottom edge, a height change moves its TOP. That was the jump: the
       animation was smooth, the box under it was resizing. Now the box is one
       size from the first frame and the list scrolls inside it. */
    .msgr-panel { width:20rem; max-width:calc(100vw - 2rem); height:28rem; max-height:calc(100vh - 6rem);
        display:flex; flex-direction:column; background:#fff; border-radius:1rem;
        box-shadow:0 16px 48px rgb(0 0 0 / .22); overflow:hidden; display:flex; flex-direction:column;
        animation:msgrIn .28s var(--ease-house, cubic-bezier(.22,1,.36,1)); }
    .msgr-panel.hidden { display:none; }
    @keyframes msgrIn { from { opacity:0; transform:translateY(12px); } to { opacity:1; transform:none; } }
    .msgr-panel-head { display:flex; align-items:center; justify-content:space-between; padding:.7rem .7rem .7rem 1rem; }
    .msgr-panel-title { color:#fff; font-weight:800; font-size:1rem; letter-spacing:.01em; }
    /* min-height so one lone thread still reads as a panel — without it the
       list hugged a single row and the whole thing sat as a sliver on the
       bottom edge, close button and all. The padding keeps the last row off
       the rounded corner. */
    /* min-height:0 is what lets a flex child actually scroll instead of
       pushing its parent taller. */
    .msgr-panel-body { flex:1 1 auto; min-height:0; overflow-y:auto; padding-bottom:.75rem; }
    .msgr-panel-head { flex:none; }
    /* Skeleton rows in the exact shape of the threads they become; the
       shimmer parks under reduced motion but the shapes still say "loading". */
    .msgr-skel { display:flex; align-items:center; gap:.65rem; padding:.6rem 1rem; }
    .msgr-skel + .msgr-skel { border-top:1px solid #f6f7f8; }
    .msgr-skel-av { width:2.6rem; height:2.6rem; border-radius:9999px; flex-shrink:0; }
    .msgr-skel-mid { flex:1; min-width:0; display:flex; flex-direction:column; gap:.45rem; }
    .msgr-skel-line { height:.55rem; border-radius:9999px; }
    .msgr-skel-line.w1 { width:55%; } .msgr-skel-line.w2 { width:82%; }
    .msgr-skel-av, .msgr-skel-line { background:linear-gradient(100deg, #eef0f2 40%, #f7f8f9 50%, #eef0f2 60%);
        background-size:200% 100%; animation:msgrShimmer 1.2s linear infinite; }
    @keyframes msgrShimmer { to { background-position:-200% 0; } }
    html.dark .msgr-skel + .msgr-skel { border-color:#232a1c; }
    html.dark .msgr-skel-av, html.dark .msgr-skel-line { background:linear-gradient(100deg, #232a1c 40%, #2c3620 50%, #232a1c 60%);
        background-size:200% 100%; animation:msgrShimmer 1.2s linear infinite; }
    @media (prefers-reduced-motion: reduce) { .msgr-skel-av, .msgr-skel-line { animation:none; } }
    /* Two-line Messenger rows: name + when up top, preview + unread below,
       everything truncating so one long name never bends the column. */
    .msgr-thread { display:flex; align-items:center; gap:.65rem; padding:.6rem 1rem; cursor:pointer;
        transition:background-color .28s var(--ease-house, cubic-bezier(.22,1,.36,1)); }
    .msgr-thread + .msgr-thread { border-top:1px solid #f6f7f8; }
    .msgr-thread:hover { background:var(--color-brand-50); }
    .msgr-thread .avatar { width:2.6rem; height:2.6rem; font-size:.78rem; flex-shrink:0; }
    .msgr-thread-mid { min-width:0; flex:1; display:flex; flex-direction:column; gap:.1rem; }
    .msgr-thread-top, .msgr-thread-row2 { display:flex; align-items:center; gap:.5rem; }
    .msgr-thread-name { font-weight:600; font-size:.88rem; color:#1f2937; min-width:0; flex:1;
        overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    .msgr-thread-at { font-size:.68rem; color:#9ca3af; flex-shrink:0; }
    .msgr-thread-last { font-size:.78rem; color:#6b7280; min-width:0; flex:1;
        overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    /* Unread is a state the whole row wears, not just a pill in the corner. */
    .msgr-thread.is-unread .msgr-thread-name, .msgr-thread.is-unread .msgr-thread-last { color:#111827; font-weight:700; }
    .msgr-thread-unread { min-width:1.1rem; height:1.1rem; padding:0 .3rem; border-radius:9999px; flex-shrink:0;
        background:var(--color-brand-600); color:#fff; font-size:.62rem; font-weight:800; display:inline-flex; align-items:center; justify-content:center; }
    .msgr-empty { padding:2rem 1.25rem; text-align:center; color:#9ca3af; }
    .msgr-empty svg { width:2.4rem; height:2.4rem; margin:0 auto .5rem; opacity:.45; }
    .msgr-empty-t { font-weight:700; font-size:.9rem; color:#6b7280; }
    .msgr-empty-s { font-size:.78rem; margin-top:.15rem; }
    .msgr-windows { display:flex; align-items:flex-end; gap:.75rem; }
    .msgr-window { width:24rem; max-width:calc(100vw - 2rem); height:33rem; background:#fff; border-radius:1rem 1rem 0 0;
        box-shadow:0 16px 48px rgb(0 0 0 / .22); display:flex; flex-direction:column; overflow:hidden;
        animation:msgrIn .28s var(--ease-house, cubic-bezier(.22,1,.36,1)); }
    /* The green the app's modern headers wear, kept slowly on the move so an
       open conversation reads as live. The thread list wears the same one so
       the whole dock speaks a single header language. Reduced motion parks
       it on frame one. */
    .msgr-window-head, .msgr-panel-head { color:#fff;
        background:linear-gradient(120deg, #3d6823, #6b9f3d 35%, #4a7c2a 60%, #2f5219 85%, #3d6823);
        background-size:240% 240%; animation:msgrHeadDrift 12s ease-in-out infinite alternate; }
    .msgr-window-head { display:flex; align-items:center; gap:.5rem; padding:.55rem .75rem; }
    @keyframes msgrHeadDrift { from { background-position:0% 35%; } to { background-position:100% 65%; } }
    .msgr-window-head .avatar { width:2.1rem; height:2.1rem; font-size:.7rem; box-shadow:none; }
    .msgr-window-head a { color:#fff; font-weight:700; font-size:.98rem; text-decoration:none; }
    .msgr-window-body { flex:1; overflow-y:auto; padding:.75rem; display:flex; flex-direction:column; gap:.35rem; background:#fafbfc; }
    .msgr-bubble { max-width:82%; padding:.5rem .8rem; border-radius:1rem; font-size:.95rem; line-height:1.4; word-wrap:break-word; }
    .msgr-bubble.them { align-self:flex-start; background:#eceff2; color:#1f2937; border-bottom-left-radius:.3rem; }
    .msgr-bubble.me { align-self:flex-end; background:var(--color-brand-600); color:#fff; border-bottom-right-radius:.3rem; }
    .msgr-window-foot { padding:.5rem; border-top:1px solid #f1f3f5; display:flex; gap:.4rem; }
    .msgr-window-foot input { flex:1 1 0; min-width:0; border:1px solid #e5e7eb; border-radius:9999px; padding:.5rem .9rem; font-size:.92rem; outline:none; }
    .msgr-window-foot input:focus { border-color:var(--color-brand-400); }
    .msgr-send { border:0; background:var(--color-brand-600); color:#fff; border-radius:9999px; width:2.25rem; height:2.25rem; cursor:pointer; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
    .msgr-window-foot-wrap { border-top:1px solid #f1f3f5; }
    html.dark .msgr-window-foot-wrap { border-color:#2b3a1c; }
    .msgr-window-foot { border-top:0; }
    .msgr-icon { border:0; background:transparent; color:#6b7280; cursor:pointer; width:2.1rem; height:2.1rem; border-radius:9999px; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
    .msgr-icon:hover { background:#f1f3f5; color:#374151; }
    html.dark .msgr-icon { color:#9fb389; }
    html.dark .msgr-icon:hover { background:rgb(255 255 255 / .07); color:#e5e9df; }
    /* A strip, not a slot: several photos can wait here at once, and it
       scrolls sideways rather than growing the composer downward. */
    .msgr-attach { display:flex; align-items:center; gap:.4rem; padding:.5rem .5rem 0; font-size:.78rem; color:#6b7280;
        overflow-x:auto; overflow-y:hidden; scrollbar-width:none; }
    .msgr-attach::-webkit-scrollbar { display:none; }
    .msgr-attach.hidden { display:none; }
    .msgr-att { position:relative; flex:none; width:2.75rem; height:2.75rem; border-radius:.5rem; overflow:hidden;
        animation:msgrAttIn .28s cubic-bezier(.22,1,.36,1); }
    @keyframes msgrAttIn { from { opacity:0; transform:scale(.8); } }
    @media (prefers-reduced-motion:reduce) { .msgr-att { animation:none; } }
    .msgr-att img { width:100%; height:100%; object-fit:cover; display:block; }
    .msgr-att-clip { width:100%; height:100%; display:flex; align-items:center; justify-content:center;
        background:var(--color-brand-50, #eef4e6); font-size:1.05rem; }
    /* The remove button sits on the corner of its own thumbnail, so with four
       photos queued it is still obvious which one goes. */
    .msgr-att-x { position:absolute; top:1px; right:1px; width:1.05rem; height:1.05rem; border:0; padding:0;
        border-radius:999px; background:rgb(0 0 0 / .58); color:#fff; font-size:.6rem; line-height:1; cursor:pointer; }
    .msgr-att-count { flex:none; font-weight:700; padding-left:.15rem; }
    .msgr-attach-thumb { width:2.75rem; height:2.75rem; object-fit:cover; border-radius:.5rem; }
    .msgr-attach-thumb.hidden, .msgr-attach-clip.hidden { display:none; }
    .msgr-attach-clip { width:2.75rem; height:2.75rem; border-radius:.5rem; background:var(--color-brand-50, #eef4e6);
        display:flex; align-items:center; justify-content:center; font-size:1.1rem; flex-shrink:0; }
    .msgr-attach-name { min-width:0; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; font-weight:600; }
    .msgr-attach-x { border:0; background:transparent; color:#9ca3af; cursor:pointer; font-size:.9rem; margin-left:auto; flex-shrink:0; }
    .msgr-bubble img { max-width:100%; border-radius:.65rem; margin-top:.15rem; display:block; cursor:pointer; }
    .msgr-bubble video { max-width:100%; max-height:16rem; border-radius:.65rem; margin-top:.15rem; display:block; background:#000; }
    /* A clip mid-upload: a quiet dark tile instead of a player that cannot play yet. */
    .msgr-vid-pending { display:flex; align-items:center; justify-content:center; width:11rem; max-width:100%;
        height:6.5rem; border-radius:.65rem; margin-top:.15rem; background:rgb(0 0 0 / .35); font-size:1.4rem; }
    /* ONE + beside the field opens the media chooser, so attach options never
       eat the room the sentence needs. */
    .msgr-plus-wrap { position:relative; flex-shrink:0; }
    .msgr-plus svg { transition:transform .28s var(--ease-house, cubic-bezier(.22,1,.36,1)); }
    .msgr-plus.is-open { background:var(--color-brand-50, #eef4e6); color:var(--color-brand-700, #3d6823); }
    .msgr-plus.is-open svg { transform:rotate(45deg); }
    .msgr-plus-menu { position:absolute; bottom:calc(100% + .55rem); left:0; z-index:5; min-width:13.5rem;
        background:#fff; border:1px solid #e5e7eb; border-radius:.9rem; padding:.35rem;
        box-shadow:0 14px 36px -10px rgb(0 0 0 / .35); transform-origin:bottom left;
        animation:msgrMenuIn .28s var(--ease-house, cubic-bezier(.22,1,.36,1)); }
    .msgr-plus-menu[hidden] { display:none; }
    @keyframes msgrMenuIn { from { opacity:0; transform:translateY(8px) scale(.92); } to { opacity:1; transform:none; } }
    .msgr-plus-opt { display:flex; align-items:center; gap:.6rem; width:100%; border:0; background:transparent;
        cursor:pointer; padding:.5rem .6rem; border-radius:.6rem; font-size:.85rem; font-weight:600; color:#374151;
        text-align:left; transition:background-color .28s var(--ease-house, cubic-bezier(.22,1,.36,1)); }
    .msgr-plus-opt:hover { background:var(--color-brand-50, #eef4e6); }
    .msgr-plus-opt.hidden { display:none; }
    .msgr-plus-ico { width:2rem; height:2rem; border-radius:.55rem; flex-shrink:0; display:flex; align-items:center;
        justify-content:center; background:var(--color-brand-50, #eef4e6); color:var(--color-brand-700, #3d6823); }
    .msgr-plus-ico svg { width:1.1rem; height:1.1rem; }
    /* Recording strip: mirrors the team chat's, because it is the same act. */
    .msgr-recbar { display:flex; align-items:center; gap:.5rem; margin:.5rem .5rem 0; padding:.4rem .6rem;
        border-radius:.6rem; background:#fef2f2; border:1px solid #fecaca; font-size:.78rem; font-weight:700; color:#b91c1c; }
    .msgr-recbar.hidden { display:none; }
    .msgr-recdot { width:.55rem; height:.55rem; border-radius:9999px; background:#dc2626;
        animation:msgrRecPulse 1.1s ease-in-out infinite; }
    @keyframes msgrRecPulse { 0%, 100% { opacity:1; } 50% { opacity:.25; } }
    .msgr-rec-stop { margin-left:auto; border:0; padding:.15rem .6rem; border-radius:9999px; background:#dc2626;
        color:#fff; font-size:.72rem; font-weight:800; cursor:pointer; }
    /* While the gallery picker is borrowed, lift the app sheet layer over the
       dock (sheets are born at z-50, the phone conversation sits at z-96) —
       the draw pad's picking lift, aimed at this dock instead. */
    html.msgr-picking .sheet-backdrop.is-open { z-index:97; }
    html.msgr-picking #smMediaPickerSheet { z-index:98; }
    html.dark .msgr-plus-menu { background:#151b12; border-color:#2b3a1c; }
    html.dark .msgr-plus-opt { color:#dbe6cf; }
    html.dark .msgr-plus-opt:hover { background:rgb(255 255 255 / .07); }
    html.dark .msgr-plus-ico { background:rgb(255 255 255 / .08); color:#a7c98a; }
    html.dark .msgr-plus.is-open { background:rgb(255 255 255 / .08); color:#a7c98a; }
    html.dark .msgr-attach-clip { background:rgb(255 255 255 / .08); }
    html.dark .msgr-recbar { background:rgb(153 27 27 / .2); border-color:rgb(153 27 27 / .5); color:#fca5a5; }
    @media (prefers-reduced-motion: reduce) {
        .msgr-plus-menu { animation:none; }
        .msgr-plus svg, .msgr-plus-opt { transition:none; }
        .msgr-recdot { animation:none; }
    }
    /* Quoted reply inside a bubble (FB Messenger style). */
    .msgr-quote { font-size:.78rem; opacity:.8; padding:.25rem .5rem; margin:-.1rem -.2rem .3rem; border-left:2px solid currentColor;
        border-radius:.35rem; background:rgb(0 0 0 / .06); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:100%; }
    .msgr-bubble.me .msgr-quote { background:rgb(255 255 255 / .18); }
    /* Per-bubble hover actions (reply / forward). */
    .msgr-row { display:flex; align-items:center; gap:.25rem; max-width:100%; }
    .msgr-row.me { align-self:flex-end; flex-direction:row-reverse; }
    .msgr-row.them { align-self:flex-start; }
    .msgr-row .msgr-bubble { max-width:100%; }
    .msgr-acts { display:flex; gap:.2rem; opacity:0; transition:opacity .15s ease; flex-shrink:0; }
    .msgr-row:hover .msgr-acts, .msgr-row:focus-within .msgr-acts { opacity:1; }
    .msgr-act { border:0; background:#eef1f4; color:#6b7280; cursor:pointer; width:1.6rem; height:1.6rem; border-radius:9999px;
        display:inline-flex; align-items:center; justify-content:center; padding:0; transition:background .15s ease, color .15s ease, transform .15s ease; }
    .msgr-act svg { width:.85rem; height:.85rem; }
    .msgr-act:hover { background:var(--color-brand-100, #dcfce7); color:var(--color-brand-700, #15803d); transform:translateY(-1px); }
    .msgr-act:active { transform:none; }
    html.dark .msgr-act { background:rgb(255 255 255 / .07); color:#9aa69a; }
    html.dark .msgr-act:hover { background:rgb(255 255 255 / .14); color:#e5e9df; }
    @media (prefers-reduced-motion: reduce) { .msgr-act:hover { transform:none; } }
    /* Reply preview above the composer. */
    .msgr-reply-bar { display:none; align-items:center; gap:.5rem; padding:.4rem .6rem; background:#f1f3f5; border-top:1px solid #e5e7eb; font-size:.8rem; }
    .msgr-reply-bar.show { display:flex; }
    .msgr-reply-bar .rb-body { min-width:0; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; color:#4b5563; }
    .msgr-reply-bar .rb-x { margin-left:auto; border:0; background:transparent; color:#9ca3af; cursor:pointer; flex-shrink:0; }
    html.dark .msgr-reply-bar { background:#1a2213; border-color:#2b3a1c; }
    html.dark .msgr-reply-bar .rb-body { color:#c3cdb5; }
    html.dark .msgr-quote { background:rgb(255 255 255 / .09); }
    /* New message pops in (house easing); sent bubbles grow from the composer side. */
    @keyframes msgrBubbleIn { from { opacity:0; transform:translateY(9px) scale(.9); } to { opacity:1; transform:none; } }
    .msgr-bubble-in { animation:msgrBubbleIn .28s cubic-bezier(.22,1,.36,1) both; }
    .msgr-bubble.me.msgr-bubble-in { transform-origin:bottom right; }
    .msgr-bubble.them.msgr-bubble-in { transform-origin:bottom left; }
    @keyframes msgrSendPop { 0%{transform:scale(1);} 45%{transform:scale(.8);} 100%{transform:scale(1);} }
    .msgr-send.is-sending { animation:msgrSendPop .3s cubic-bezier(.22,1,.36,1); }
    /* Optimistic bubble: dimmed while its photo/message uploads. */
    .msgr-bubble.is-pending { opacity:.82; }
    .msgr-uploading { display:flex; align-items:center; gap:.4rem; margin-top:.3rem; font-size:.72rem; font-weight:600; opacity:.9; }
    .msgr-bubble.me .msgr-uploading { color:rgb(255 255 255 / .92); }
    .msgr-spin { width:.82rem; height:.82rem; border-radius:9999px; border:2px solid currentColor; border-top-color:transparent; display:inline-block; animation:msgrSpin .6s linear infinite; }
    @keyframes msgrSpin { to { transform:rotate(360deg); } }
    @media (prefers-reduced-motion: reduce) { .msgr-bubble-in, .msgr-send.is-sending { animation:none; } .msgr-spin { animation-duration:1.4s; } }
    /* The classic three-dot bubble at the thread's foot while the other side
       types. Enters like any incoming bubble; the dots park under reduced
       motion but the bubble still says what it means. */
    .msgr-typing { display:flex; align-items:center; gap:.3rem; align-self:flex-start; flex-shrink:0;
        padding:.6rem .8rem; border-radius:1rem; border-bottom-left-radius:.3rem; background:#eceff2;
        animation:msgrBubbleIn .28s var(--ease-house, cubic-bezier(.22,1,.36,1)) both; transform-origin:bottom left; }
    .msgr-typing i { width:.42rem; height:.42rem; border-radius:9999px; background:#9ca3af; display:block;
        animation:msgrTypingDot 1.2s ease-in-out infinite; }
    .msgr-typing i:nth-child(2) { animation-delay:.15s; }
    .msgr-typing i:nth-child(3) { animation-delay:.3s; }
    @keyframes msgrTypingDot { 0%, 60%, 100% { transform:none; opacity:.45; } 30% { transform:translateY(-3px); opacity:1; } }
    html.dark .msgr-typing { background:#232a1c; }
    html.dark .msgr-typing i { background:#9aa69a; }
    @media (prefers-reduced-motion: reduce) {
        .msgr-typing { animation:none; }
        .msgr-typing i { animation:none; opacity:.7; }
    }
    .msgr-off { padding:1rem; text-align:center; font-size:.8rem; color:#9ca3af; }
    /* A close you can actually see and hit: solid white on the green header,
       with a real (2.1rem) target so a near-miss never lands on the profile
       link stretched beside it. */
    .msgr-x { border:0; background:transparent; color:#9ca3af; cursor:pointer; font-size:1rem; line-height:1;
        width:2.1rem; height:2.1rem; border-radius:9999px; flex-shrink:0;
        display:inline-flex; align-items:center; justify-content:center;
        transition:background-color .28s var(--ease-house, cubic-bezier(.22,1,.36,1)); }
    .msgr-panel-head .msgr-x, .msgr-window-head .msgr-x { color:#fff; }
    .msgr-panel-head .msgr-x:hover, .msgr-window-head .msgr-x:hover { background:rgb(255 255 255 / .2); }
    @media (prefers-reduced-motion: reduce) { .msgr-x { transition:none; } }
    html.dark .msgr-panel, html.dark .msgr-window { background:#151b12; }
    html.dark .msgr-window-body { background:#10160c; }
    html.dark .msgr-thread-name { color:#e5e9df; }
    html.dark .msgr-thread + .msgr-thread { border-color:#232a1c; }
    html.dark .msgr-thread:hover { background:rgb(255 255 255 / .06); }
    html.dark .msgr-thread-last { color:#9aa69a; }
    html.dark .msgr-thread.is-unread .msgr-thread-name, html.dark .msgr-thread.is-unread .msgr-thread-last { color:#f2f6ec; }
    html.dark .msgr-empty-t { color:#c3cdb5; }
    html.dark .msgr-bubble.them { background:#232a1c; color:#dbe6cf; }
    html.dark .msgr-window-foot input { background:#10160c; border-color:#2b3a1c; color:#e5e9df; }
    /* On a phone one conversation owns the bottom of the screen.
       The row that holds the windows must NOT be display:none here: the
       windows are appended INTO it, and a display:none parent takes its
       whole subtree out of the box tree — a position:fixed child of a hidden
       parent renders nothing at all. That is the entire "messaging does not
       work on my phone": the window was being built and then never drawn.
       `display:contents` gets the row out of the way without taking its
       children with it.

       Why the sheet was STILL not full width: the base rule's
       max-width:calc(100vw - 2rem) survived into this query, and with
       left/right both 0 the width resolves to that cap — a 2rem gap down the
       right edge on every phone. And the old 480px cutoff missed larger
       phones and landscape entirely, which kept the floating 24rem window.
       767px is the breakpoint the dock itself already uses. */
    @media (max-width:767px) {
        .msgr-windows { display:contents; }
        .msgr-window { position:fixed; inset:auto 0 0 0; width:auto; max-width:none;
            /* dvh so the URL bar collapsing doesn't hide the composer; vh is
               the fallback for browsers without it. */
            height:82vh; height:82dvh;
            border-radius:1.1rem 1.1rem 0 0; z-index:96;
            /* Arrives like a sheet: up from the bottom edge, house easing. */
            animation:msgrSheetIn .28s var(--ease-house, cubic-bezier(.22,1,.36,1)); }
        .msgr-window-foot-wrap { padding-bottom:env(safe-area-inset-bottom, 0px); }
        /* Two open at once would stack on the same spot; the newest wins. */
        .msgr-window:not(:last-child) { display:none; }
        /* The thread list becomes a bottom sheet too — full width, up from
           the edge — and sits one layer under a conversation (95 vs 96) so
           tapping a thread visibly hands over to the chat. */
        .msgr-panel { position:fixed; inset:auto 0 0 0; width:auto; max-width:none;
            /* Same reasoning on a phone, where the sheet is pinned harder
               still: one height, decided before the first frame. */
            height:76vh; height:76dvh; max-height:none;
            border-radius:1.1rem 1.1rem 0 0; z-index:95;
            animation:msgrSheetIn .28s var(--ease-house, cubic-bezier(.22,1,.36,1)); }
        .msgr-panel-body { padding-bottom:calc(.75rem + env(safe-area-inset-bottom, 0px)); }
        /* On a phone the exit is the sheet's: back down over the edge. */
        .msgr-window.is-closing, .msgr-panel.is-closing { animation:msgrSheetOut .28s var(--ease-house, cubic-bezier(.22,1,.36,1)) both; }
    }
    @keyframes msgrSheetIn { from { transform:translateY(100%); } to { transform:none; } }
    @keyframes msgrSheetOut { from { transform:none; } to { transform:translateY(100%); } }
    /* Closing is the entrance run backwards; the node leaves the DOM after.
       pointer-events off so a dying window can't swallow one more tap. The
       fade-out is desktop-only BY MEDIA QUERY: this rule sits later in the
       sheet than the phone's slide-down, and at equal specificity it would
       win there too. */
    @keyframes msgrOut { from { opacity:1; transform:none; } to { opacity:0; transform:translateY(12px); } }
    .msgr-window.is-closing, .msgr-panel.is-closing { pointer-events:none; }
    @media (min-width:768px) {
        .msgr-window.is-closing, .msgr-panel.is-closing { animation:msgrOut .22s var(--ease-house, cubic-bezier(.22,1,.36,1)) both; }
    }
    @media (prefers-reduced-motion: reduce) {
        .msgr-window, .msgr-panel { animation:none; }
        .msgr-window.is-closing, .msgr-panel.is-closing { animation:none; }
        .msgr-window-head, .msgr-panel-head { animation:none; }
        .msgr-thread { transition:none; }
    }
</style>

<script>
(function () {
    if (window.__plazaMessengerBound) return;
    window.__plazaMessengerBound = true;

    const CSRF = () => document.querySelector('meta[name=csrf-token]')?.content || '';
    const dock = document.getElementById('msgrDock');
    const panel = document.getElementById('msgrPanel');
    const launcher = document.getElementById('msgrLauncher');
    const badge = document.getElementById('msgrBadge');
    const windowsWrap = document.getElementById('msgrWindows');
    const openWins = {};   // userId -> element
    const esc = (s) => { const d = document.createElement('div'); d.textContent = s == null ? '' : s; return d.innerHTML; };

    /* Where a page offers a chair, take it. The community nav renders an empty
       #msgrSeat right after its hamburger; moving the node (rather than
       rendering a second button there) keeps one click handler, one badge and
       one unread poll. Pages without a seat — the dashboard — keep the float.
       The panel and the chat windows stay in the fixed dock either way: the
       seat is for the launcher alone. */
    const seat = document.getElementById('msgrSeat');
    if (seat) { seat.appendChild(launcher); launcher.classList.add('is-seated'); }

    function setBadge(n) {
        if (n > 0) { badge.textContent = n > 99 ? '99+' : n; badge.classList.remove('hidden'); }
        else badge.classList.add('hidden');
        // Seated, this is what makes the button blink; floating, it is inert.
        launcher.classList.toggle('has-unread', n > 0);
        launcher.setAttribute('aria-label', n > 0 ? `Messages, ${n} unread` : 'Messages');
    }
    async function refreshBadge() {
        try {
            const r = await fetch(@json(route('community.messages.unread')), { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
            const d = await r.json(); setBadge(d.data.unread);
        } catch (_) {}
    }

    // Live delivery: pull messages newer than what we've seen and surface them.
    let lastSeenId = 0;
    let synced = false;
    async function livePoll() {
        try {
            // The poll names the open windows so the server can answer "which
            // of these people are mid-sentence to me" — a cache can be asked
            // about a key but never scanned.
            const openIds = Object.keys(openWins).join(',');
            const r = await fetch(@json(route('community.messages.poll')) + '?after=' + lastSeenId + (openIds ? '&typingFrom=' + openIds : ''), { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
            const d = await r.json();
            setBadge(d.data.unread);
            const typing = (d.data.typing || []).map(Number);
            for (const uid of Object.keys(openWins)) showTyping(openWins[uid], typing.includes(parseInt(uid, 10)));
            if (!synced) { lastSeenId = d.data.maxId || 0; synced = true; return; }  // first sync: adopt maxId, don't replay history
            for (const m of (d.data.incoming || [])) {
                if (m.id > lastSeenId) lastSeenId = m.id;
                if (openWins[m.senderId]) {
                    // Window already open → append the new bubble live + mark read.
                    appendBubble(openWins[m.senderId].querySelector('.msgr-window-body'), { id: m.id, body: m.body, image: m.image, video: m.video, poster: m.poster, mine: false, replyTo: m.replyTo }, true);
                    fetch(`/app/community/messages/${m.senderId}`, { headers: { Accept: 'application/json' }, credentials: 'same-origin' }).then(refreshBadge).catch(() => {});
                } else {
                    // Otherwise pop a chat window open (FB-style), which loads the thread.
                    openWindow(m.senderId, m.senderName);
                }
            }
        } catch (_) {}
    }

    // The loading state is the list's own silhouette, not a word — the panel
    // opens onto something that already looks like messages on their way.
    const skeletonRows = () => Array.from({ length: 4 }, () =>
        '<div class="msgr-skel"><span class="msgr-skel-av"></span><span class="msgr-skel-mid"><span class="msgr-skel-line w1"></span><span class="msgr-skel-line w2"></span></span></div>'
    ).join('');

    async function loadThreads() {
        const box = document.getElementById('msgrThreads');
        box.innerHTML = skeletonRows();
        try {
            const r = await fetch(@json(route('community.messages.threads')), { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
            const d = await r.json();
            setBadge(d.data.unread);
            const threads = d.data.threads || [];
            if (!threads.length) {
                box.innerHTML = `<div class="msgr-empty">
                    <svg fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.9 9.9 0 01-4.29-.94L3 20l1.05-3.15A7.6 7.6 0 013 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                    <p class="msgr-empty-t">No messages yet</p>
                    <p class="msgr-empty-s">Open a co-farmer's profile and tap Message to start a conversation.</p>
                </div>`;
                return;
            }
            box.innerHTML = threads.map((t) => {
                const av = t.avatar ? `<span class="avatar overflow-hidden"><img src="${esc(t.avatar)}" class="w-full h-full object-cover" alt=""></span>`
                    : `<span class="avatar av-h${t.userId % 8}">${esc(t.initials || '?')}</span>`;
                return `<div class="msgr-thread${t.unread ? ' is-unread' : ''}" data-user="${t.userId}" data-name="${esc(t.name)}">
                    ${av}
                    <span class="msgr-thread-mid">
                        <span class="msgr-thread-top"><span class="msgr-thread-name">${esc(t.name)}</span><span class="msgr-thread-at">${esc(t.lastAt || '')}</span></span>
                        <span class="msgr-thread-row2"><span class="msgr-thread-last">${esc(t.lastBody)}</span>${t.unread ? `<span class="msgr-thread-unread">${t.unread > 9 ? '9+' : t.unread}</span>` : ''}</span>
                    </span>
                </div>`;
            }).join('');
        } catch (_) {
            // A dead end that offers the way back — the error never has to
            // stick until the next open.
            box.innerHTML = `<div class="msgr-empty">
                <p class="msgr-empty-t">Couldn't load your messages.</p>
                <p class="msgr-empty-s"><button type="button" class="msgr-retry" style="color:var(--color-brand-600);text-decoration:underline;cursor:pointer;background:none;border:0;font:inherit">Try again</button></p>
            </div>`;
            box.querySelector('.msgr-retry')?.addEventListener('click', () => loadThreads());
        }
    }

    // Exit = the entrance run backwards; the node is dealt with only after the
    // animation finishes. The timeout is the guard for reduced motion (where
    // animation:none means animationend never fires) and for a dropped event.
    function closeAnimated(el, done) {
        if (el.classList.contains('is-closing')) return;
        el.classList.add('is-closing');
        let gone = false;
        const finish = () => {
            if (gone) return; gone = true;
            el.removeEventListener('animationend', finish);
            // Reopened mid-exit (is-closing cleared) → this close is void.
            if (el.classList.contains('is-closing')) done();
        };
        el.addEventListener('animationend', finish);
        setTimeout(finish, 400);
    }

    function togglePanel(show) {
        const on = show ?? (panel.classList.contains('hidden') || panel.classList.contains('is-closing'));
        if (on) {
            panel.classList.remove('is-closing');
            panel.classList.remove('hidden');
            loadThreads();
        } else if (!panel.classList.contains('hidden')) {
            closeAnimated(panel, () => { panel.classList.add('hidden'); panel.classList.remove('is-closing'); });
        }
    }
    launcher.addEventListener('click', () => togglePanel());
    dock.addEventListener('click', (e) => {
        if (e.target.closest('[data-msgr-close-panel]')) { togglePanel(false); return; }
        const t = e.target.closest('.msgr-thread');
        if (t) { openWindow(parseInt(t.dataset.user, 10), t.dataset.name); togglePanel(false); }
    });

    // ---- media chooser support -------------------------------------------
    const MAX_VIDEO_BYTES = 300 * 1024 * 1024;   // the server's clip ceiling
    // Recording needs both halves; phones and desktops that lack either get
    // the option hidden rather than a button that apologises when tapped.
    const canRecord = !!(navigator.mediaDevices && navigator.mediaDevices.getUserMedia && window.MediaRecorder);
    // The season gallery picker is schedule-scoped and only some pages carry
    // it, so "share from gallery" appears only where both it and a schedule
    // to point it at exist.
    function galleryScheduleId() {
        const el = document.querySelector('[data-schedule-id]');
        const v = el ? parseInt(el.getAttribute('data-schedule-id'), 10) : 0;
        if (v) return v;
        const qs = new URLSearchParams(location.search);
        const q = parseInt(qs.get('scheduleId') || qs.get('id') || '', 10);
        return /^\/app\/sm-/.test(location.pathname) && q ? q : 0;
    }
    /* The gallery door used to appear only on pages that carried a season,
       which meant never in the community — exactly where somebody is most
       likely to be reaching for a photo they already have. With no season on
       the page it opens across every season instead of hiding. */
    const canGallery = () => typeof window.smPickMedia === 'function';

    /* Record a clip in place — the team chat's recorder, pointed at a DM.
       No preview pane on purpose: the person is filming the field, not the
       phone; the chip under the composer is where the result lands. */
    let recorder = null, recChunks = [], recStream = null, recWin = null, recTimer = null;
    const bestRecMime = () => ['video/webm;codecs=vp9,opus', 'video/webm;codecs=vp8,opus', 'video/webm', 'video/mp4']
        .find((t) => MediaRecorder.isTypeSupported(t)) || '';
    async function startRec(win, onClip) {
        if (recorder) { stopRec(); return; }
        try {
            recStream = await navigator.mediaDevices.getUserMedia({ audio: true,
                video: { facingMode: { ideal: 'environment' }, width: { ideal: 1280 }, height: { ideal: 720 } } });
        } catch (_) {
            if (window.toast) toast('Camera or microphone blocked. Allow it for this site.', 'error');
            return;
        }
        recWin = win; recChunks = [];
        const mime = bestRecMime();
        try { recorder = new MediaRecorder(recStream, mime ? { mimeType: mime } : undefined); }
        catch (_) { recorder = new MediaRecorder(recStream); }
        recorder.ondataavailable = (e) => { if (e.data && e.data.size) recChunks.push(e.data); };
        recorder.onstop = () => {
            const type = recorder.mimeType || 'video/webm';
            const blob = new Blob(recChunks, { type });
            recStream.getTracks().forEach((t) => t.stop());
            const forWin = recWin;
            recorder = null; recStream = null; recChunks = []; recWin = null;
            clearTimeout(recTimer);
            forWin?.querySelector('.msgr-recbar')?.classList.add('hidden');
            // A window shut mid-recording has nowhere to put the clip.
            if (blob.size && forWin && document.body.contains(forWin)) {
                onClip(new File([blob], 'clip.' + (type.includes('mp4') ? 'mp4' : 'webm'), { type }));
            }
        };
        recorder.start();
        win.querySelector('.msgr-recbar').classList.remove('hidden');
        // Never leave one running — five minutes outruns any DM-worthy clip.
        recTimer = setTimeout(stopRec, 5 * 60 * 1000);
    }
    function stopRec() { try { if (recorder) recorder.stop(); } catch (_) { /* already stopped */ } }

    /* Borrow the season media picker. It opens in the app's sheet layer
       (z-50), underneath this dock, so the layer is lifted for exactly as
       long as it is borrowed — the draw pad's picking pattern, aimed here. */
    function pickFromGallery(attachPick) {
        const sid = galleryScheduleId();
        if (typeof window.smPickMedia !== 'function') return;
        const root = document.documentElement;
        root.classList.add('msgr-picking');
        const onClosed = (e) => {
            if (e.detail && e.detail.id !== 'smMediaPickerSheet') return;
            document.removeEventListener('sm:sheet-closed', onClosed);
            // Next tick, so a pick (which closes the sheet first) still lands
            // while the layer is lifted.
            setTimeout(() => root.classList.remove('msgr-picking'), 0);
        };
        document.addEventListener('sm:sheet-closed', onClosed);
        window.smPickMedia({
            // Inside a season, that season. Anywhere else, everything.
            scheduleId: sid,
            allSchedules: !sid,
            // Several at a time: the picker fires onPick once per chosen item
            // and the composer's queue takes them all.
            multiple: true,
            title: sid ? 'Share from the gallery' : 'Share from your gallery',
            onPick: (item) => attachPick(
                { pick: item, kind: item.type === 'video' ? 'video' : 'image', url: item.url, poster: item.posterUrl || null },
                item.title || 'From the gallery'
            ),
        });
    }

    // One closer for every window's chooser: a tap anywhere else puts it away.
    document.addEventListener('click', (e) => {
        document.querySelectorAll('.msgr-plus-menu:not([hidden])').forEach((menu) => {
            const wrap = menu.closest('.msgr-plus-wrap');
            if (wrap && wrap.contains(e.target)) return;
            menu.hidden = true;
            const btn = wrap && wrap.querySelector('.js-msgr-plus');
            if (btn) { btn.classList.remove('is-open'); btn.setAttribute('aria-expanded', 'false'); }
        });
    });
    document.addEventListener('keydown', (e) => {
        if (e.key !== 'Escape') return;
        document.querySelectorAll('.msgr-plus-menu:not([hidden])').forEach((menu) => {
            menu.hidden = true;
            const btn = menu.closest('.msgr-plus-wrap')?.querySelector('.js-msgr-plus');
            if (btn) { btn.classList.remove('is-open'); btn.setAttribute('aria-expanded', 'false'); }
        });
    });

    // A clip plays where it sits; metadata-only preload keeps data bills sane
    // and still paints a first frame where there is no poster.
    function buildVideo(m, bodyEl) {
        const v = document.createElement('video');
        v.src = m.video; v.controls = true; v.playsInline = true; v.preload = 'metadata';
        if (m.poster) v.poster = m.poster;
        v.addEventListener('loadedmetadata', () => { bodyEl.scrollTop = bodyEl.scrollHeight; });
        return v;
    }

    async function openWindow(userId, name) {
        if (openWins[userId]) { openWins[userId].querySelector('.msgr-window-foot input')?.focus(); return; }
        const win = document.createElement('div');
        win.className = 'msgr-window'; win.dataset.user = userId;
        win.innerHTML = `<div class="msgr-window-head">
                <span class="avatar av-h${userId % 8}">?</span>
                <a href="/app/community/members/${userId}" class="grow truncate">${esc(name || 'Member')}</a>
                <button type="button" class="msgr-x" data-close aria-label="Close">✕</button>
            </div>
            <div class="msgr-window-body"><p class="text-xs text-gray-400 text-center py-4">Loading…</p></div>
            <div class="msgr-window-foot-wrap emoji-scope">
                <div class="msgr-reply-bar">
                    <svg class="w-4 h-4 shrink-0" style="color:var(--color-brand-600)" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h10a5 5 0 015 5v2m-15-7l4-4m-4 4l4 4"/></svg>
                    <span class="rb-body"></span>
                    <button type="button" class="rb-x" aria-label="Cancel reply">✕</button>
                </div>
                <div class="msgr-attach hidden"></div>
                <div class="msgr-recbar hidden"><span class="msgr-recdot"></span><span class="msgr-rec-what">Recording video…</span><button type="button" class="msgr-rec-stop">Stop</button></div>
                <div class="msgr-window-foot">
                    <div class="msgr-plus-wrap">
                        <button type="button" class="msgr-icon msgr-plus js-msgr-plus" aria-label="Add a photo or video" aria-expanded="false" title="Add media"><svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/></svg></button>
                        <div class="msgr-plus-menu" hidden>
                            <button type="button" class="msgr-plus-opt" data-msgr-add="upload"><span class="msgr-plus-ico"><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg></span>Upload photo or video</button>
                            <button type="button" class="msgr-plus-opt" data-msgr-add="camera"><span class="msgr-plus-ico"><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg></span>Take a photo</button>
                            <button type="button" class="msgr-plus-opt" data-msgr-add="record"><span class="msgr-plus-ico"><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10l4.55-2.28A1 1 0 0121 8.62v6.76a1 1 0 01-1.45.9L15 14M5 6h8a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2z"/></svg></span>Record a video</button>
                            <button type="button" class="msgr-plus-opt" data-msgr-add="gallery"><span class="msgr-plus-ico"><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 7h3l2-3h6l2 3h3v13H4V7z"/><path stroke-linecap="round" stroke-linejoin="round" d="M8 13l2.5-2.5L14 14l2-2 2 2"/></svg></span>Share from gallery</button>
                        </div>
                    </div>
                    <input type="file" class="js-msgr-file hidden" accept="image/*,video/*" multiple>
                    <input type="file" class="js-msgr-cam hidden" accept="image/*" capture="environment">
                    <button type="button" class="msgr-icon js-emoji-btn" aria-label="Add an emoji" title="Emoji"><svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></button>
                    <input type="text" placeholder="Aa" maxlength="5000">
                    <button type="button" class="msgr-send" aria-label="Send"><svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14m0 0l-6-6m6 6l-6 6"/></svg></button>
                </div>
            </div>`;
        windowsWrap.appendChild(win);
        openWins[userId] = win;

        const input = win.querySelector('input[type="text"]');
        const bodyEl = win.querySelector('.msgr-window-body');
        const fileInput = win.querySelector('.js-msgr-file');
        const camInput = win.querySelector('.js-msgr-cam');
        const attach = win.querySelector('.msgr-attach');
        /* Several at once.
         *
         * Picking six photos and sending them one at a time is six trips
         * through the chooser, and nobody does that — they give up and send
         * one. The queue below holds them all; sending walks it. */
        win._atts = [];
        const MAX_ATTS = 10;
        const drawAttach = () => {
            attach.innerHTML = '';
            attach.classList.toggle('hidden', !win._atts.length);
            win._atts.forEach((att, i) => {
                const cell = document.createElement('div');
                cell.className = 'msgr-att';
                cell.title = att.label || '';
                cell.innerHTML = att.kind === 'image'
                    ? `<img src="${esc(att.url)}" alt="">`
                    : (att.poster ? `<img src="${esc(att.poster)}" alt="">` : '<span class="msgr-att-clip">🎬</span>')
                    ;
                const x = document.createElement('button');
                x.type = 'button'; x.className = 'msgr-att-x'; x.setAttribute('aria-label', 'Remove');
                x.textContent = '✕';
                x.addEventListener('click', () => dropAttach(i));
                cell.appendChild(x);
                attach.appendChild(cell);
            });
            if (win._atts.length > 1) {
                const n = document.createElement('span');
                n.className = 'msgr-att-count';
                n.textContent = win._atts.length + ' to send';
                attach.appendChild(n);
            }
        };
        // Only a freshly-chosen file owns an object URL; a gallery pick's URL
        // is the stored one and must not be revoked.
        const freeAtt = (att) => { if (att && att.file && att.url) { try { URL.revokeObjectURL(att.url); } catch (e) {} } };
        const dropAttach = (i) => { freeAtt(win._atts[i]); win._atts.splice(i, 1); drawAttach(); };
        const clearAttach = () => {
            win._atts.forEach(freeAtt);
            win._atts = [];
            fileInput.value = ''; camInput.value = '';
            drawAttach();
        };
        // Every door adds to the same queue — two from the gallery, one from
        // the camera and one recorded all wait together.
        const setAttach = (att, label) => {
            if (win._atts.length >= MAX_ATTS) {
                if (window.toast) toast(`That is ${MAX_ATTS} already — send these first.`, 'error');
                freeAtt(att);
                return;
            }
            att.label = label || (att.kind === 'video' ? 'Video' : 'Photo');
            win._atts.push(att);
            drawAttach();
        };
        const attachFile = (f) => {
            if (!f) return;
            const kind = (f.type || '').startsWith('video/') ? 'video' : 'image';
            if (kind === 'video' && f.size > MAX_VIDEO_BYTES) {
                if (window.toast) toast('That video is over 300 MB — pick or record a shorter one.', 'error');
                return;
            }
            setAttach({ file: f, kind, url: URL.createObjectURL(f) }, f.name);
        };
        const attachMany = (list) => { Array.from(list || []).forEach(attachFile); };
        fileInput.addEventListener('change', () => { attachMany(fileInput.files); fileInput.value = ''; });
        camInput.addEventListener('change', () => { attachFile(camInput.files[0]); camInput.value = ''; });
        win.querySelector('.rb-x')?.addEventListener('click', () => clearReply(win));
        win.querySelector('[data-close]').addEventListener('click', (e) => {
            // A close is a close: stop the tap dead so it can never reach the
            // profile link stretched across the header behind it — that link
            // is how "closing the chat" was turning into a page navigation.
            e.preventDefault(); e.stopPropagation();
            if (recWin === win) stopRec();   // never leave a camera running behind a closed chat
            clearAttach();
            delete openWins[userId];         // a message landing mid-exit opens a fresh window
            closeAnimated(win, () => win.remove());
        });

        // The + chooser: every way media can arrive, behind one button, so
        // the typing field keeps its width.
        const plusBtn = win.querySelector('.js-msgr-plus');
        const plusMenu = win.querySelector('.msgr-plus-menu');
        const toggleMenu = (open) => {
            const on = open ?? plusMenu.hidden;
            plusMenu.hidden = !on;
            plusBtn.classList.toggle('is-open', on);
            plusBtn.setAttribute('aria-expanded', on ? 'true' : 'false');
        };
        // Doors that cannot open here stay off the menu — no dead buttons.
        if (!canRecord) plusMenu.querySelector('[data-msgr-add="record"]').classList.add('hidden');
        if (!canGallery()) plusMenu.querySelector('[data-msgr-add="gallery"]').classList.add('hidden');
        plusBtn.addEventListener('click', () => toggleMenu());
        plusMenu.addEventListener('click', (e) => {
            const opt = e.target.closest('[data-msgr-add]');
            if (!opt) return;
            toggleMenu(false);
            const how = opt.getAttribute('data-msgr-add');
            if (how === 'upload') fileInput.click();
            else if (how === 'camera') camInput.click();
            else if (how === 'record') startRec(win, attachFile);
            else if (how === 'gallery') pickFromGallery(setAttach);
        });
        win.querySelector('.msgr-rec-stop').addEventListener('click', stopRec);
        const sendBtn = win.querySelector('.msgr-send');
        /* One message per attachment, in the order they were picked.
         *
         * That is what every messenger does with a multi-pick, and it is also
         * what this thread can carry: a message holds one photo or one clip.
         * The words ride with the first, so a caption still reads as a caption
         * rather than arriving after four silent photos. Each is uploaded on
         * its own so a failure loses one, not the batch. */
        const doSend = async () => {
            const text = input.value.trim();
            const atts = (win._atts || []).slice();
            if (!text && !atts.length) return;
            sendBtn.classList.remove('is-sending'); void sendBtn.offsetWidth; sendBtn.classList.add('is-sending');
            input.value = '';
            const replyMeta = win._reply ? { body: win._reply.body || '📷 Photo', mine: false } : null;
            const reply = win._reply && win._reply.id ? win._reply.id : null;
            // Taken before clearAttach frees them, because these are what the
            // uploads carry — the composer's own URLs go with the strip.
            const queue = atts.length ? atts.map((att, i) => ({
                att,
                // The optimistic bubble needs a URL of its own: a fresh file
                // gets an independent object URL (clearAttach revokes the
                // composer's), a gallery pick keeps the stored one it will
                // render with anyway.
                media: { kind: att.kind, url: att.file ? URL.createObjectURL(att.file) : att.url, local: !!att.file },
                text: i === 0 ? text : '',
            })) : [{ att: null, media: null, text }];
            clearAttach();
            clearReply(win);

            // Every bubble appears at once, in order, each with its own loader:
            // a five-photo send should look like five photos immediately.
            const pendings = queue.map((q, i) => appendPendingBubble(bodyEl, {
                text: q.text, media: q.media, replyTo: i === 0 ? replyMeta : null,
            }));

            // Sequential, not parallel: the order the bubbles show is the
            // order they must land in, and phones uploading six videos at once
            // finish none of them.
            for (let i = 0; i < queue.length; i++) {
                const q = queue[i];
                const fd = new FormData();
                if (q.text) fd.append('body', q.text);
                if (q.att && q.att.file) fd.append(q.att.kind === 'video' ? 'video' : 'image', q.att.file, q.att.file.name || (q.att.kind === 'video' ? 'clip' : 'photo'));
                if (q.att && q.att.pick) fd.append('galleryPath', q.att.pick.path);
                if (reply && i === 0) fd.append('replyToId', reply);
                try {
                    const r = await fetch(`/app/community/messages/${userId}`, { method: 'POST',
                        headers: { 'X-CSRF-TOKEN': CSRF(), Accept: 'application/json' }, body: fd });
                    const d = await r.json();
                    if (d.success) { finalizePending(bodyEl, pendings[i], { id: d.data.id, body: d.data.body, image: d.data.image, video: d.data.video, poster: d.data.poster, replyTo: d.data.replyTo }); }
                    else { failPending(pendings[i], d.message); if (q.text && !input.value) input.value = q.text; }
                } catch (_) { failPending(pendings[i], 'Network error — try again.'); if (q.text && !input.value) input.value = q.text; }
            }
        };
        win.querySelector('.msgr-send').addEventListener('click', doSend);
        input.addEventListener('keydown', (e) => { if (e.key === 'Enter') { e.preventDefault(); doSend(); } });

        // Typing pings: one every 2.5s while keys actually move. The server
        // flag lives four seconds, so a live typist keeps it renewed and
        // going quiet simply lets it lapse — no "stopped typing" traffic.
        let lastTypingPing = 0;
        input.addEventListener('input', () => {
            if (!input.value) return;   // clearing the field is not typing
            const now = Date.now();
            if (now - lastTypingPing < 2500) return;
            lastTypingPing = now;
            fetch(`/app/community/messages/${userId}/typing`, { method: 'POST',
                headers: { 'X-CSRF-TOKEN': CSRF(), Accept: 'application/json' }, credentials: 'same-origin' }).catch(() => {});
        });

        // Load the thread with retries so a transient hiccup never leaves a
        // dead "Could not load" — it keeps trying, then offers a Retry button.
        async function loadThread(attempt) {
            attempt = attempt || 1;
            try {
                const r = await fetch(`/app/community/messages/${userId}`, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
                if (!r.ok) throw new Error('http ' + r.status);
                const d = await r.json();
                if (!d || !d.data) throw new Error('bad payload');
                const head = win.querySelector('.msgr-window-head');
                if (d.data.user.avatar) head.querySelector('.avatar').outerHTML = `<span class="avatar overflow-hidden"><img src="${esc(d.data.user.avatar)}" class="w-full h-full object-cover" alt=""></span>`;
                else head.querySelector('.avatar').textContent = d.data.user.initials || '?';
                head.querySelector('a').textContent = d.data.user.name;
                bodyEl.innerHTML = '';
                (d.data.messages || []).forEach((m) => appendBubble(bodyEl, m));
                if (!d.data.canMessage) {
                    win.querySelector('.msgr-window-foot-wrap').outerHTML = '<div class="msgr-off">This member has turned off messages.</div>';
                }
                (d.data.messages || []).forEach((m) => { if (m.id > lastSeenId) lastSeenId = m.id; });
                refreshBadge();
            } catch (_) {
                if (attempt < 4) { setTimeout(() => loadThread(attempt + 1), attempt * 700); return; }
                bodyEl.innerHTML = '<p class="text-xs text-gray-400 text-center py-4">Couldn\'t load the chat. <button type="button" class="msgr-retry" style="color:var(--color-brand-600);text-decoration:underline;cursor:pointer">Retry</button></p>';
                bodyEl.querySelector('.msgr-retry')?.addEventListener('click', () => { bodyEl.innerHTML = '<p class="text-xs text-gray-400 text-center py-4">Loading…</p>'; loadThread(1); });
            }
        }
        loadThread(1);
    }

    function appendBubble(bodyEl, m, animate) {
        const row = document.createElement('div');
        row.className = 'msgr-row ' + (m.mine ? 'me' : 'them');

        const b = document.createElement('div');
        b.className = 'msgr-bubble ' + (m.mine ? 'me' : 'them') + (animate ? ' msgr-bubble-in' : '');
        if (m.id) b.dataset.msgId = m.id;
        if (m.body) b.dataset.msgBody = m.body;

        // Quoted reply (FB Messenger style).
        if (m.replyTo && m.replyTo.body) {
            const q = document.createElement('div');
            q.className = 'msgr-quote';
            // The id turns the quote from a label into a way back: the shared
            // chat-media script walks the thread to it and lights it up.
            if (m.replyTo.id) q.setAttribute('data-reply-to', m.replyTo.id);
            q.textContent = (m.replyTo.mine ? 'You: ' : '') + m.replyTo.body;
            b.appendChild(q);
        }
        if (m.body) { const t = document.createElement('div'); t.textContent = m.body; b.appendChild(t); }
        if (m.image) {
            const img = document.createElement('img');
            img.src = m.image; img.alt = ''; img.loading = 'lazy';
            img.setAttribute('data-lightbox', '');
            img.addEventListener('load', () => { bodyEl.scrollTop = bodyEl.scrollHeight; });
            b.appendChild(img);
        }
        if (m.video) {
            b.dataset.msgKind = 'video';   // so a reply to it can say "🎬 Video"
            b.appendChild(buildVideo(m, bodyEl));
        }
        row.appendChild(b);

        addBubbleActs(row, m);

        bodyEl.appendChild(row);
        // The typing dots always sit at the foot — a message landing while
        // they show would otherwise leave them stranded mid-thread.
        const ty = bodyEl.querySelector('.msgr-typing');
        if (ty) bodyEl.appendChild(ty);
        bodyEl.scrollTop = bodyEl.scrollHeight;
    }

    /* Typing dots for one window. The poll asserts the state each beat; the
       timer is the quiet expiry for when the last "true" beat never gets its
       "false" (window closed on their side, poll hiccup). */
    function showTyping(win, on) {
        const bodyEl = win.querySelector('.msgr-window-body');
        if (!bodyEl) return;
        let t = bodyEl.querySelector('.msgr-typing');
        if (on) {
            clearTimeout(win._typingHide);
            win._typingHide = setTimeout(() => showTyping(win, false), 7000);
            if (!t) {
                t = document.createElement('div');
                t.className = 'msgr-typing';
                t.setAttribute('aria-label', 'Typing…');
                t.innerHTML = '<i></i><i></i><i></i>';
                bodyEl.appendChild(t);
                bodyEl.scrollTop = bodyEl.scrollHeight;
            }
        } else if (t) {
            clearTimeout(win._typingHide);
            t.remove();
        }
    }

    // Per-bubble hover actions (reply needs an id, forward needs text). Shared
    // by fully-rendered bubbles and finalized optimistic ones.
    const ICON_REPLY = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 15L3 9m0 0l6-6M3 9h11a6 6 0 010 12h-2"/></svg>';
    const ICON_FWD = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 15l6-6m0 0l-6-6m6 6H10a6 6 0 000 12h2"/></svg>';
    function addBubbleActs(row, m) {
        if (row.querySelector('.msgr-acts')) return;
        const actsHtml =
            (m.id ? '<button type="button" class="msgr-act" data-msgr-act="reply" aria-label="Reply" title="Reply">' + ICON_REPLY + '</button>' : '') +
            // A photo or a clip is worth passing on even with nothing typed
            // over it — the old rule offered Forward only for words.
            ((m.body || m.image || m.video) && m.id
                ? '<button type="button" class="msgr-act" data-msgr-act="forward" aria-label="Forward" title="Forward">' + ICON_FWD + '</button>'
                : '');
        if (!actsHtml) return;
        const acts = document.createElement('div');
        acts.className = 'msgr-acts';
        acts.innerHTML = actsHtml;
        row.appendChild(acts);
    }

    // Optimistic outgoing bubble shown immediately, with an "uploading" loader
    // while the request (and any photo) is in flight. Animates in like a real
    // one; finalized in place on success, removed on failure.
    function appendPendingBubble(bodyEl, m) {
        const row = document.createElement('div');
        row.className = 'msgr-row me';
        const b = document.createElement('div');
        b.className = 'msgr-bubble me msgr-bubble-in is-pending';
        if (m.replyTo && m.replyTo.body) {
            const q = document.createElement('div');
            q.className = 'msgr-quote';
            // The id turns the quote from a label into a way back: the shared
            // chat-media script walks the thread to it and lights it up.
            if (m.replyTo.id) q.setAttribute('data-reply-to', m.replyTo.id);
            q.textContent = (m.replyTo.mine ? 'You: ' : '') + m.replyTo.body;
            b.appendChild(q);
        }
        if (m.text) { const t = document.createElement('div'); t.textContent = m.text; b.appendChild(t); }
        let mediaEl = null;
        if (m.media && m.media.kind === 'image') {
            mediaEl = document.createElement('img'); mediaEl.src = m.media.url; mediaEl.alt = '';
            b.appendChild(mediaEl);
        } else if (m.media && m.media.kind === 'video') {
            // A tile, not a player: a half-uploaded clip cannot play yet.
            mediaEl = document.createElement('div'); mediaEl.className = 'msgr-vid-pending'; mediaEl.textContent = '🎬';
            b.appendChild(mediaEl);
        }
        const load = document.createElement('div');
        load.className = 'msgr-uploading';
        load.innerHTML = '<span class="msgr-spin"></span><span>' + (m.media ? (m.media.kind === 'video' ? 'Sending video…' : 'Sending photo…') : 'Sending…') + '</span>';
        b.appendChild(load);
        row.appendChild(b);
        bodyEl.appendChild(row);
        bodyEl.scrollTop = bodyEl.scrollHeight;
        return { row, b, load, mediaEl, localUrl: (m.media && m.media.local) ? m.media.url : null };
    }
    function finalizePending(bodyEl, p, m) {
        p.load?.remove();
        p.b.classList.remove('is-pending');
        if (m.id) p.b.dataset.msgId = m.id;
        if (m.body) p.b.dataset.msgBody = m.body;
        if (m.video) {
            // The placeholder tile hands over to the real player.
            p.b.dataset.msgKind = 'video';
            if (p.mediaEl) p.mediaEl.remove();
            p.b.appendChild(buildVideo(m, bodyEl));
        } else if (m.image) {
            let img = (p.mediaEl && p.mediaEl.tagName === 'IMG') ? p.mediaEl : null;
            if (!img) { img = document.createElement('img'); img.alt = ''; p.b.appendChild(img); }
            img.setAttribute('data-lightbox', '');
            img.src = m.image;                      // swap local preview → stored URL
            img.addEventListener('load', () => { bodyEl.scrollTop = bodyEl.scrollHeight; });
        }
        if (p.localUrl) { try { URL.revokeObjectURL(p.localUrl); } catch (_) {} }
        addBubbleActs(p.row, m);
    }
    function failPending(p, msg) {
        if (p.localUrl) { try { URL.revokeObjectURL(p.localUrl); } catch (_) {} }
        p.row.remove();
        if (window.toast) toast(msg || 'Could not send.', 'error');
    }

    // Reply state lives on the window node (win._reply = {id, body}).
    function startReply(win, id, body) {
        if (!win || !id) return;
        win._reply = { id: parseInt(id, 10), body: body || '' };
        const bar = win.querySelector('.msgr-reply-bar');
        bar.querySelector('.rb-body').textContent = body || '📷 Photo';
        bar.classList.add('show');
        win.querySelector('input[type="text"]')?.focus();
    }
    function clearReply(win) {
        if (!win) return;
        win._reply = null;
        const bar = win.querySelector('.msgr-reply-bar');
        if (bar) { bar.classList.remove('show'); bar.querySelector('.rb-body').textContent = ''; }
    }

    // Forward: pick a co-farmer and send them the message body.
    let forwardBody = null, forwardCache = null, forwardId = null, forwardKind = null;
    function openForward(body, id, kind) {
        if (!body && !id) { if (window.toast) toast('Nothing to forward.', 'error'); return; }
        forwardBody = body || '';
        // The id is what lets the server re-send the attachment without the
        // phone uploading it again.
        forwardId = id || null;
        forwardKind = kind || null;
        const prev = document.getElementById('msgrForwardPreview');
        if (prev) {
            const label = forwardBody
                ? (forwardBody.length > 90 ? forwardBody.slice(0, 90) + '…' : forwardBody)
                : (kind === 'video' ? '🎬 Video' : '🖼️ Photo');
            prev.textContent = label;
        }
        if (window.openSheet) window.openSheet('msgrForwardSheet');
        loadForwardList();
    }
    async function loadForwardList() {
        const box = document.getElementById('msgrForwardList');
        if (!box) return;
        if (forwardCache) { renderForwardList(forwardCache); return; }
        try {
            const r = await fetch(@json(route('community.cofarmers.list')), { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
            const d = await r.json();
            forwardCache = (d.data && d.data.items) || [];
            renderForwardList(forwardCache);
        } catch (_) {
            box.innerHTML = '<p class="text-sm text-red-500 px-2 py-3 text-center">Could not load co-farmers.</p>';
        }
    }
    function renderForwardList(items) {
        const box = document.getElementById('msgrForwardList');
        if (!items.length) { box.innerHTML = '<p class="text-sm text-gray-400 px-2 py-3 text-center">No co-farmers yet.</p>'; return; }
        box.innerHTML = items.map((u) => {
            const av = u.avatar
                ? `<img src="${esc(u.avatar)}" alt="" class="w-8 h-8 rounded-full object-cover shrink-0">`
                : `<span class="w-8 h-8 rounded-full bg-brand-100 text-brand-700 text-xs font-bold flex items-center justify-center shrink-0">${esc(u.initials || '?')}</span>`;
            const btn = u.allowMessages
                ? `<button type="button" class="btn btn-white btn-sm shrink-0" data-msgr-fwd="${u.id}">Send</button>`
                : '<span class="text-xs text-gray-400 shrink-0">Messages off</span>';
            return `<div class="flex items-center gap-2 px-2 py-1.5 rounded-lg hover:bg-gray-50">${av}<span class="grow min-w-0 truncate text-sm font-semibold text-gray-800">${esc(u.name)}</span>${btn}</div>`;
        }).join('');
    }

    // Delegated actions for bubble reply/forward + forward-sheet sends.
    document.addEventListener('click', async (e) => {
        const act = e.target.closest('[data-msgr-act]');
        if (act) {
            const bubble = act.closest('.msgr-row')?.querySelector('.msgr-bubble');
            const win = act.closest('.msgr-window');
            if (!bubble) return;
            if (act.getAttribute('data-msgr-act') === 'reply') {
                // A wordless bubble is quoted by what it holds — clip or photo.
                startReply(win, bubble.dataset.msgId, bubble.dataset.msgBody || (bubble.dataset.msgKind === 'video' ? '🎬 Video' : '📷 Photo'));
            } else {
                openForward(bubble.dataset.msgBody || '', bubble.dataset.msgId || null, bubble.dataset.msgKind || null);
            }
            return;
        }
        const fwd = e.target.closest('[data-msgr-fwd]');
        if (fwd) {
            const userId = fwd.getAttribute('data-msgr-fwd');
            if (!userId || (!forwardBody && !forwardId)) return;
            fwd.disabled = true; const orig = fwd.textContent; fwd.textContent = 'Sending…';
            try {
                const fd = new FormData();
                fd.append('body', forwardBody);
                if (forwardId) fd.append('forwardOfId', forwardId);
                const r = await fetch(`/app/community/messages/${userId}`, { method: 'POST', headers: { 'X-CSRF-TOKEN': CSRF(), Accept: 'application/json' }, body: fd });
                const d = await r.json();
                if (d.success) { fwd.textContent = 'Sent ✓'; if (window.toast) toast('Message forwarded.'); }
                else { fwd.disabled = false; fwd.textContent = orig; if (window.toast) toast(d.message || 'Could not send.', 'error'); }
            } catch (_) { fwd.disabled = false; fwd.textContent = orig; if (window.toast) toast('Network error.', 'error'); }
        }
    });

    // Public: open a DM window (used by the bell deep-link + programmatic callers).
    window.plazaOpenDm = (userId, name) => openWindow(parseInt(userId, 10), name);

    // Any "Message" button anywhere on the page (profile, member cards…) opens
    // the dock. Data-attribute + delegation avoids fragile inline onclick.
    document.addEventListener('click', (e) => {
        const b = e.target.closest('.js-open-dm');
        if (!b) return;
        e.preventDefault();
        openWindow(parseInt(b.dataset.dmUser, 10), b.dataset.dmName || 'Member');
    });

    // Deep-link: /app/community?dm=<userId> opens that thread (from the bell).
    const dm = new URLSearchParams(location.search).get('dm');
    if (dm && /^\d+$/.test(dm)) openWindow(parseInt(dm, 10), 'Member');

    /* The dock's own line.
     *
     * Sending a DM already broadcasts UserNotified on the recipient's
     * private user.{id} channel — that is how their bell rings the instant a
     * message lands. The dock never subscribed to it, so the bell announced
     * a message the chat window would not show for up to eight more seconds.
     * It listens now and pulls immediately; the poll stays as the floor,
     * because a dropped broadcast should cost a few seconds, not the
     * message. */
    const ME = {{ (int) auth()->id() }};
    try {
        window.Echo?.private('user.' + ME).listen('.notify', (p) => {
            if (!p || (p.type && p.type !== 'message')) return;
            livePoll();
        });
    } catch (_) { /* no realtime here — the poll covers it */ }

    // The poll quickens to a 3s beat while a conversation is open — typing
    // dots need a faster ear than badge counting — and relaxes back to 8s
    // when none is. Self-scheduling, so the next wait always sees the
    // current state and a slow request never stacks another behind it.
    livePoll();
    (function pollBeat() {
        setTimeout(async () => {
            await livePoll();
            pollBeat();
        }, Object.keys(openWins).length ? 3000 : 8000);
    })();
})();
</script>

{{-- The dock offers "from your gallery", so it carries the sheet. @once. --}}
@include('sm.partials.media-picker')
@include('community.partials.chat-media-js')
