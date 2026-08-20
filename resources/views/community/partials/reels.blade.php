@once
{{-- Reels: sixty seconds, filling the phone.

     Three things live here, because they are one feature and splitting them
     would mean three files that only ever appear together:

       the rail     — a strip of covers at the top of the wall
       the viewer   — full screen, one reel at a time, swipe for the next
       the studio   — film or pick, then trim, filter, caption and score it

     A reel is a wall post underneath (see the migration), so the viewer's
     react and comment buttons speak to the same endpoints every other post
     uses. Nothing about reacting or commenting is written twice. --}}

{{-- ------------------------------------------------------------ the rail --}}
<section class="rl-rail-wrap" id="rlRailWrap" aria-label="Reels" hidden>
    <div class="rl-rail-head">
        <h2>Stories</h2>
        {{-- Beside the word, not across the row from it: the button makes the
             thing the word names, and they belong together. --}}
        <button type="button" class="rl-new" id="rlNew">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14m-7-7h14"/></svg>
            Make one
        </button>
    </div>
    <div class="rl-rail" id="rlRail"></div>
</section>

{{-- ---------------------------------------------------------- the viewer --}}
<div class="rl-viewer hidden" id="rlViewer" role="dialog" aria-modal="true" aria-label="Stories" data-cv-skip>
    <button type="button" class="rl-x" id="rlClose" aria-label="Close">✕</button>
    <div class="rl-deck" id="rlDeck"></div>
</div>

{{-- ---------------------------------------------------------- the studio --}}
<div class="rl-studio hidden" id="rlStudio" role="dialog" aria-modal="true" aria-label="Make a story">
    <div class="rl-bar">
        <button type="button" class="rl-icon" id="rlCancel" aria-label="Cancel">✕</button>
        <span class="rl-title" id="rlStep">Make a story</span>
        <button type="button" class="btn btn-primary btn-sm hidden" id="rlPost">Post</button>
    </div>

    {{-- Step one: where the video comes from. --}}
    <div class="rl-pick" id="rlPick">
        <div class="rl-pick-inner">
            <p class="rl-pick-lead">A minute, no more — and it fills the screen.</p>
            <button type="button" class="rl-source" id="rlRecord">
                <span class="rl-source-ico">🎥</span>
                <span><b>Record now</b><i>Film it where you are standing</i></span>
            </button>
            <button type="button" class="rl-source" id="rlUpload">
                <span class="rl-source-ico">⬆️</span>
                <span><b>Upload a video</b><i>Something already on this phone</i></span>
            </button>
            <button type="button" class="rl-source" id="rlFromGallery">
                <span class="rl-source-ico">🖼️</span>
                <span><b>From your gallery</b><i>A clip one of your seasons kept</i></span>
            </button>
            <input type="file" id="rlFile" accept="video/*" class="hidden">
            <input type="file" id="rlFileCam" accept="video/*" capture="environment" class="hidden">
        </div>
    </div>

    {{-- Step two: the editor. Picture on top, tools under it — the shape
         every reel editor uses, because your thumb is at the bottom and your
         eyes are at the top. --}}
    <div class="rl-edit hidden" id="rlEdit">
        <div class="rl-stage" id="rlStage">
            {{-- The reel itself: this box IS what gets posted, backdrop
                 and all, so everything in the editor is measured against it
                 rather than against the black around it. --}}
            <div class="rl-reel" id="rlReel">
                {{-- The picture, as placed: pinched, turned, and cropped by
                     the reel's own edges. --}}
                <div class="rl-shot" id="rlShot">
                    <video id="rlPreview" playsinline muted loop data-cv-skip></video>
                </div>
                {{-- What has been stuck on: words, shapes and pictures,
                     dragged where they are wanted. Outside the transform, so
                     a tilted picture does not tilt the words with it. --}}
                <div class="rl-layer" id="rlLayer"></div>
            </div>
            {{-- Heard while it is being made. It starts silent because a
                 browser will not autoplay sound, and a farmer who wants to
                 hear what they are cutting taps once. --}}
            <button type="button" class="rl-ear" id="rlEar" aria-pressed="false" aria-label="Sound on">🔇</button>
            {{-- Words are edited over the picture, where you can see them
                 land, rather than in a field somewhere below it. --}}
            <div class="rl-textedit" id="rlTextEdit" hidden>
                <input type="text" id="rlTextInput" maxlength="120" placeholder="Type your words…" autocomplete="off">
                <div class="rl-fonts" id="rlTextFonts"></div>
                <div class="rl-inks" id="rlTextInks"></div>
                <label class="rl-size">
                    <span>Size</span>
                    <input type="range" id="rlTextSize" min=".6" max="2.2" step=".05" value="1">
                </label>
                <div class="rl-textedit-acts">
                    <button type="button" id="rlTextDrop" class="btn btn-white btn-sm">Remove</button>
                    <button type="button" id="rlTextDone" class="btn btn-primary btn-sm">Done</button>
                </div>
            </div>
        </div>

        <div class="rl-tools">
            {{-- The clip itself: real frames, and a handle at each end. --}}
            <div class="rl-timeline" id="rlTimeline">
                <div class="rl-film" id="rlFilm"></div>
                <div class="rl-window" id="rlWindow">
                    <span class="rl-handle rl-handle-a" data-rl-handle="a" role="slider" aria-label="Start"></span>
                    {{-- The middle is a handle too: dragging it moves the
                         whole kept part along the clip, keeping its length,
                         which is how you find the right ten seconds rather
                         than rebuilding them end by end. --}}
                    <span class="rl-window-grab" data-rl-handle="mid" aria-label="Move the kept part"></span>
                    <span class="rl-handle rl-handle-b" data-rl-handle="b" role="slider" aria-label="End"></span>
                </div>
                <div class="rl-playhead" id="rlPlayhead"></div>
            </div>
            <div class="rl-timeline-say">
                <span id="rlTrimSay">0.0s → 15.0s</span>
                <span id="rlTrimLen">15s</span>
            </div>

            {{-- The music, as its own strip under the clip. --}}
            <div class="rl-track" id="rlTrack">
                <button type="button" class="rl-track-main" id="rlMusicBtn">
                    <span class="rl-track-ic">🎵</span>
                    <span class="rl-track-txt"><b id="rlMusicName">Add music</b><i id="rlMusicSub">Free to use, or a file from this phone</i></span>
                </button>
                <button type="button" class="rl-track-play hidden" id="rlMusicPlay" aria-label="Hear it">▶</button>
                <button type="button" class="rl-track-x hidden" id="rlMusicX" aria-label="Remove the music">✕</button>
            </div>
            <audio id="rlMusicAudio" preload="none"></audio>

            {{-- One row of things to do, the way an editor lays them out. --}}
            <div class="rl-acts">
                <button type="button" class="rl-act" id="rlAddBtn">
                    <span>➕</span><i>Add</i>
                </button>
                <button type="button" class="rl-act" id="rlFrameBtn">
                    <span>🖼️</span><i>Frame</i>
                </button>
                <button type="button" class="rl-act" id="rlSoundBtn">
                    <span>🎚️</span><i>Sound</i>
                </button>
                <button type="button" class="rl-act" id="rlLooksBtn">
                    <span>🎨</span><i>Look</i>
                </button>
                <button type="button" class="rl-act" id="rlSayBtn">
                    <span>💬</span><i>Caption</i>
                </button>
            </div>

            {{-- Panels, one at a time, under the row that opens them. --}}
            {{-- How the picture sits in the reel, for thumbs that would
                 rather use a slider and for mice that cannot pinch at all. --}}
            <div class="rl-panel hidden" id="rlFramePanel">
                <label class="rl-slider">
                    <span>Size</span>
                    <input type="range" id="rlFrameScale" min=".4" max="2" step=".01" value="1">
                    <b id="rlFrameScaleSay">100%</b>
                </label>
                <label class="rl-slider">
                    <span>Turn</span>
                    <input type="range" id="rlFrameRot" min="-45" max="45" step="1" value="0">
                    <b id="rlFrameRotSay">0°</b>
                </label>
                <p class="rl-hint">Pinch and twist the picture to place it by hand.</p>
                <p class="rl-swatch-lbl">Backdrop</p>
                <div class="rl-swatches" id="rlBackdrops"></div>
                <button type="button" class="rl-reset" id="rlFrameReset">Put it back</button>
            </div>

            {{-- Two sounds, and how loud each of them is. --}}
            <div class="rl-panel hidden" id="rlSoundPanel">
                <label class="rl-slider">
                    <span>Music</span>
                    <input type="range" id="rlVolMusic" min="0" max="1" step=".05" value=".85">
                    <b id="rlVolMusicSay">85%</b>
                </label>
                <label class="rl-slider">
                    <span>Original</span>
                    <input type="range" id="rlVolOwn" min="0" max="1" step=".05" value="0">
                    <b id="rlVolOwnSay">off</b>
                </label>
                <p class="rl-hint">Choosing music used to throw away what the camera heard. Now both are kept, at whatever balance you set.</p>
            </div>

            <div class="rl-panel hidden" id="rlLooksPanel">
                <div class="rl-looks" id="rlLooks"></div>
            </div>
            <div class="rl-panel hidden" id="rlSayPanel">
                <textarea id="rlCaption" class="form-textarea" rows="2" maxlength="2000" placeholder="Say something about it…"></textarea>
            </div>
        </div>
        <input type="file" id="rlAudio" accept="audio/*" class="hidden">
        <input type="file" id="rlSticker" accept="image/*" class="hidden">
    </div>

    <div class="rl-busy hidden" id="rlBusy">
        <div class="rl-spin"></div>
        <p>Preparing your reel…</p>
        <small>Trimming, filling the screen, and making it small enough to travel.</small>
    </div>
</div>

{{-- What can be stuck onto a reel. --}}
<div class="sheet hidden" id="rlAddSheet" style="--sheet-width:22rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title">Add to your story</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body space-y-1">
        <button type="button" class="rl-opt" data-rl-add-text>
            <span class="rl-opt-ic">🅣</span>
            <span class="rl-opt-txt"><b>Words</b><i>Pick a font, a colour and a size — then drag them where you want</i></span>
        </button>
        <button type="button" class="rl-opt" data-rl-add-image>
            <span class="rl-opt-ic">🖼️</span>
            <span class="rl-opt-txt"><b>A picture</b><i>Stick a photo or a logo onto the clip</i></span>
        </button>
        <button type="button" class="rl-opt" data-rl-add="arrow">
            <span class="rl-opt-ic">➔</span>
            <span class="rl-opt-txt"><b>An arrow</b><i>Point at the thing you are talking about</i></span>
        </button>
        <button type="button" class="rl-opt" data-rl-add="line">
            <span class="rl-opt-ic">╱</span>
            <span class="rl-opt-txt"><b>A line</b><i>Underline it, or rule it off</i></span>
        </button>
        <button type="button" class="rl-opt" data-rl-add="rect">
            <span class="rl-opt-ic">▭</span>
            <span class="rl-opt-txt"><b>A box</b><i>Ring the part that matters</i></span>
        </button>
        <button type="button" class="rl-opt" data-rl-add="ellipse">
            <span class="rl-opt-ic">◯</span>
            <span class="rl-opt-txt"><b>A circle</b><i>Same, but round</i></span>
        </button>
    </div>
</div>

{{-- The music picker, its own sheet so the studio stays uncluttered. --}}
<div class="sheet hidden" id="rlMusicSheet" style="--sheet-width:24rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title">Music</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body" id="rlMusicBody">
        {{-- Openly-licensed music, searched live. The library folder still
             comes first when the owner has put anything in it. --}}
        <form class="rl-find" id="rlMusicFind" onsubmit="return false">
            <input type="search" id="rlMusicQ" class="form-input" placeholder="Search free music — kundiman, acoustic, drums…" autocomplete="off">
            <button type="submit" class="btn btn-primary btn-sm shrink-0">Search</button>
        </form>
        <div id="rlMusicList" class="space-y-1"></div>
        <p class="rl-find-head">Free to use, from Openverse</p>
        <div id="rlMusicFound" class="space-y-1"></div>
    </div>
</div>

<style>
    /* ---------------------------------------------------------- the rail */
    /* A band, like the composer and the suggestions under it: the wall is a
       column of bands and a rail with side gutters between two of them reads
       as something that fell in from another page. */
    .rl-rail-wrap { margin: 0 calc(var(--plaza-gutter, 1rem) * -1) 1.25rem;
        padding: .85rem var(--plaza-gutter, 1rem) .75rem; }
    .rl-rail-wrap[hidden] { display: none; }
    .rl-rail-head { display: flex; align-items: center; gap: .55rem; margin-bottom: .5rem; }
    .rl-rail-head h2 { font-family: var(--font-heading); font-size: .95rem; font-weight: 800; color: var(--color-gray-900); }
    .rl-new { display: inline-flex; align-items: center; gap: .3rem; padding: .3rem .6rem; border-radius: 999px;
        border: 1px solid var(--color-brand-100); background: var(--color-brand-50); color: var(--color-brand-700);
        font-size: .75rem; font-weight: 800; cursor: pointer; }
    .rl-new svg { width: .9rem; height: .9rem; }
    /* No scrollbar under it — the half-tile showing past the edge says it
       scrolls, and says it without a grey stripe. The rail runs off the
       right so that half-tile is there to see. */
    .rl-rail { display: flex; gap: .5rem; overflow-x: auto; padding-bottom: .1rem;
        margin-right: calc(var(--plaza-gutter, 1rem) * -1); padding-right: var(--plaza-gutter, 1rem);
        scroll-snap-type: x proximity; scrollbar-width: none; }
    .rl-rail::-webkit-scrollbar { display: none; }
    .rl-tile { position: relative; flex: none; width: 7.5rem; aspect-ratio: 9 / 16; border-radius: .85rem;
        overflow: hidden; border: 0; padding: 0; cursor: pointer; background: #111; scroll-snap-align: start;
        transition: transform .28s cubic-bezier(.22,1,.36,1); }
    .rl-tile:hover { transform: translateY(-2px); }
    .rl-tile img, .rl-tile video { width: 100%; height: 100%; object-fit: cover; display: block; }
    .rl-tile-grad { position: absolute; inset: auto 0 0 0; height: 60%;
        background: linear-gradient(to top, rgb(0 0 0 / .78), transparent); }
    .rl-tile-who { position: absolute; left: .4rem; right: .4rem; bottom: .4rem; color: #fff; text-align: left; }
    .rl-tile-who b { display: block; font-size: .68rem; font-weight: 800; overflow: hidden;
        text-overflow: ellipsis; white-space: nowrap; }
    .rl-tile-who i { font-style: normal; font-size: .6rem; opacity: .8; }

    /* -------------------------------------------------------- the viewer */
    .rl-viewer { position: fixed; inset: 0; z-index: 330; background: #000;
        animation: rlIn .28s cubic-bezier(.22,1,.36,1); }
    .rl-viewer.hidden { display: none; }
    @keyframes rlIn { from { opacity: 0; } }
    .rl-x { position: absolute; top: calc(env(safe-area-inset-top) + .7rem); right: .8rem; z-index: 3;
        width: 2.4rem; height: 2.4rem; border-radius: 999px; border: 0; cursor: pointer;
        background: rgb(255 255 255 / .16); color: #fff; font-size: 1.05rem; }
    /* One reel per screen, snapped, scrolled with a thumb. */
    .rl-deck { height: 100%; overflow-y: auto; scroll-snap-type: y mandatory; }
    .rl-slide { position: relative; height: 100%; scroll-snap-align: start; display: flex;
        align-items: center; justify-content: center; }
    .rl-slide video { width: 100%; height: 100%; object-fit: contain; background: #000; }
    .rl-meta { position: absolute; left: 0; right: 4.5rem; bottom: 0; padding: 1rem 1rem calc(1.4rem + env(safe-area-inset-bottom));
        background: linear-gradient(to top, rgb(0 0 0 / .8), transparent); color: #fff; }
    .rl-meta-who { display: flex; align-items: center; gap: .5rem; font-weight: 800; font-size: .9rem; }
    .rl-meta-who span { width: 2rem; height: 2rem; border-radius: 999px; overflow: hidden; background: rgb(255 255 255 / .2);
        display: inline-flex; align-items: center; justify-content: center; font-size: .7rem; }
    .rl-meta-who img { width: 100%; height: 100%; object-fit: cover; }
    .rl-meta p { margin-top: .4rem; font-size: .88rem; line-height: 1.5; }
    .rl-meta small { display: block; margin-top: .3rem; font-size: .72rem; opacity: .75; }
    .rl-sound { position: absolute; left: .8rem; top: calc(env(safe-area-inset-top) + .7rem); z-index: 3;
        width: 2.4rem; height: 2.4rem; border-radius: 999px; border: 0; cursor: pointer;
        background: rgb(0 0 0 / .45); color: #fff; font-size: 1rem; }
    .rl-views { display: flex; flex-direction: column; align-items: center; gap: .1rem; color: #fff; }
    .rl-views svg { width: 1.5rem; height: 1.5rem; }
    .rl-views b { font-size: .7rem; font-weight: 800; }
    .rl-side { position: absolute; right: .6rem; bottom: calc(2rem + env(safe-area-inset-bottom)); z-index: 2;
        display: flex; flex-direction: column; gap: .9rem; align-items: center; }
    .rl-side button { width: 2.9rem; height: 2.9rem; border-radius: 999px; border: 0; cursor: pointer;
        background: rgb(255 255 255 / .16); color: #fff; display: inline-flex; flex-direction: column;
        align-items: center; justify-content: center; gap: .05rem; }
    .rl-side button svg { width: 1.3rem; height: 1.3rem; }
    .rl-side b { font-size: .62rem; font-weight: 800; }

    /* -------------------------------------------------------- the studio */
    /* A sheet is born at z-50 and this studio stands at z-335, so the music
       picker opened underneath it and looked like a button that did nothing.
       The layer is lifted for exactly as long as the studio is borrowing it
       — the messenger's own trick, aimed here. */
    html.rl-picking .sheet-backdrop.is-open { z-index: 336; }
    html.rl-picking #rlMusicSheet, html.rl-picking #smMediaPickerSheet { z-index: 337; }
    .rl-studio { position: fixed; inset: 0; z-index: 335; display: flex; flex-direction: column;
        background: #0b0f0a; color: #fff;
        padding-top: env(safe-area-inset-top); padding-bottom: env(safe-area-inset-bottom); }
    .rl-studio.hidden { display: none; }
    .rl-bar { display: flex; align-items: center; justify-content: space-between; gap: .5rem;
        padding: .55rem .7rem; border-bottom: 1px solid rgb(255 255 255 / .08); flex: none; }
    /* Centred between the two buttons, and it stays centred when Post is
       hidden — space-between alone shoved the title against the right edge
       on the first step, where there is no Post yet. */
    .rl-title { flex: 1 1 auto; text-align: center; font-weight: 700; font-size: .95rem; }
    /* The counterweight for the ✕ on steps that have no Post button. */
    .rl-bar .btn.hidden { display: block; visibility: hidden; }
    .rl-icon { width: 2.25rem; height: 2.25rem; border-radius: 999px; border: 0; cursor: pointer;
        background: rgb(255 255 255 / .12); color: #fff; }

    .rl-pick { flex: 1 1 auto; display: flex; align-items: center; justify-content: center; padding: 1rem; }
    .rl-pick.hidden { display: none; }
    .rl-pick-inner { width: 100%; max-width: 22rem; display: flex; flex-direction: column; gap: .6rem; }
    .rl-pick-lead { text-align: center; font-size: .85rem; color: rgb(255 255 255 / .6); margin-bottom: .4rem; }
    .rl-source { display: flex; align-items: center; gap: .8rem; padding: .8rem .9rem; border-radius: .9rem;
        border: 1px solid rgb(255 255 255 / .14); background: rgb(255 255 255 / .06); color: #fff;
        cursor: pointer; text-align: left; }
    .rl-source:hover { background: rgb(255 255 255 / .12); }
    .rl-source-ico { font-size: 1.4rem; }
    .rl-source b { display: block; font-size: .9rem; }
    .rl-source i { font-style: normal; font-size: .74rem; color: rgb(255 255 255 / .55); }

    .rl-edit { flex: 1 1 auto; min-height: 0; display: flex; flex-direction: column; }
    .rl-edit.hidden { display: none; }
    .rl-stage { position: relative; flex: 1 1 auto; min-height: 0; overflow: hidden; display: flex;
        align-items: center; justify-content: center; background: #000; }
    /* Height governs, width follows. Sized the other way round — 100% width
       at 9:16 — a phone-shaped clip wanted to be taller than the screen, so
       it overflowed the stage and stood on top of the controls below it. */
    /* The reel: a 9:16 box, as tall as the stage allows and no wider than
       it. Height governs, width follows — sized the other way round, a
       phone-shaped clip wanted to be taller than the screen and stood on top
       of the controls below it. */
    .rl-reel { position: relative; height: 100%; aspect-ratio: 9 / 16; max-width: 100%;
        overflow: hidden; background: #000; }
    .rl-shot { position: absolute; inset: 0; transform-origin: 50% 50%;
        display: flex; align-items: center; justify-content: center; }
    .rl-stage video { width: 100%; height: 100%; object-fit: cover; background: transparent; display: block; }

    /* Sound, while it is being cut. */
    .rl-ear { position: absolute; top: .6rem; right: .6rem; z-index: 3;
        width: 2.2rem; height: 2.2rem; border-radius: 999px; border: 0; cursor: pointer;
        background: rgb(0 0 0 / .5); color: #fff; font-size: .95rem; line-height: 1;
        backdrop-filter: blur(4px); }
    .rl-ear[aria-pressed="true"] { background: rgb(74 124 42 / .9); }
    .rl-overlay-text { position: absolute; left: 0; right: 0; bottom: 12%; text-align: center; padding: 0 1rem;
        font-weight: 800; font-size: 1rem; color: #fff; pointer-events: none; }
    .rl-overlay-text span { background: rgb(0 0 0 / .45); padding: .25rem .5rem; border-radius: .3rem; }
    .rl-tools { flex: none; padding: .55rem .7rem .8rem; display: flex; flex-direction: column; gap: .55rem;
        max-height: 52vh; overflow-y: auto; border-top: 1px solid rgb(255 255 255 / .08); }

    /* ---- the timeline ---- */
    .rl-timeline { position: relative; height: 3.1rem; border-radius: .5rem; overflow: hidden;
        background: rgb(255 255 255 / .06); touch-action: none; }
    .rl-film { position: absolute; inset: 0; display: flex; }
    .rl-film img { flex: 1 1 0; min-width: 0; height: 100%; object-fit: cover; opacity: .75; }
    .rl-film-bare { background: repeating-linear-gradient(90deg, rgb(255 255 255 / .08) 0 8px, transparent 8px 16px); }
    /* What is kept: bright inside, dimmed either side, a handle at each end. */
    .rl-window { position: absolute; top: 0; bottom: 0; border: 2px solid #fff; border-radius: .4rem;
        box-shadow: 0 0 0 9999px rgb(0 0 0 / .55); }
    .rl-handle { position: absolute; top: 50%; width: 1.35rem; height: 2.4rem; transform: translateY(-50%);
        background: #fff; border-radius: .35rem; cursor: ew-resize; touch-action: none;
        box-shadow: 0 2px 8px rgb(0 0 0 / .5); }
    .rl-handle::after { content: ''; position: absolute; left: 50%; top: 50%; width: 2px; height: 45%;
        transform: translate(-50%, -50%); background: rgb(0 0 0 / .35); border-radius: 2px; }
    .rl-handle-a { left: -.7rem; }
    .rl-handle-b { right: -.7rem; }
    .rl-playhead { position: absolute; top: 0; bottom: 0; width: 2px; background: #fff; opacity: .8;
        pointer-events: none; }
    .rl-timeline-say { display: flex; justify-content: space-between; font-size: .7rem; font-weight: 700;
        color: rgb(255 255 255 / .6); font-variant-numeric: tabular-nums; }

    /* ---- the music strip ---- */
    .rl-track { display: flex; align-items: center; gap: .4rem; padding: .4rem .5rem; border-radius: .7rem;
        border: 1px solid rgb(255 255 255 / .16); background: rgb(255 255 255 / .06); }
    .rl-track-main { display: flex; align-items: center; gap: .55rem; flex: 1 1 auto; min-width: 0;
        background: none; border: 0; color: #fff; text-align: left; cursor: pointer; }
    .rl-track-ic { flex: none; font-size: 1rem; }
    .rl-track-txt { display: flex; flex-direction: column; min-width: 0; }
    .rl-track-txt b { font-size: .8rem; font-weight: 700; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .rl-track-txt i { font-style: normal; font-size: .66rem; color: rgb(255 255 255 / .55);
        overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .rl-track-play, .rl-track-x { flex: none; width: 1.9rem; height: 1.9rem; border-radius: 999px; border: 0;
        background: rgb(255 255 255 / .12); color: #fff; cursor: pointer; font-size: .75rem; }
    .rl-track-play.hidden, .rl-track-x.hidden { display: none; }

    /* ---- the row of tools ---- */
    .rl-acts { display: flex; gap: .4rem; }
    .rl-act { flex: 1 1 0; display: flex; flex-direction: column; align-items: center; gap: .1rem;
        padding: .45rem .3rem; border-radius: .6rem; border: 1px solid rgb(255 255 255 / .14);
        background: rgb(255 255 255 / .06); color: #fff; cursor: pointer; }
    .rl-act span { font-size: 1rem; line-height: 1; }
    .rl-act i { font-style: normal; font-size: .68rem; font-weight: 700; color: rgb(255 255 255 / .7); }
    .rl-panel { padding-top: .1rem; }
    .rl-panel.hidden { display: none; }

    /* ---- what is stuck on the picture ---- */
    .rl-layer { position: absolute; inset: 0; z-index: 2; touch-action: none; }
    .rl-ov { position: absolute; cursor: grab; user-select: none; }
    .rl-ov-text { font-size: 1.15rem; font-weight: 800; text-align: center; max-width: 86%;
        white-space: pre-wrap;
        text-shadow: 0 2px 8px rgb(0 0 0 / .65), 0 0 2px rgb(0 0 0 / .5); }
    .rl-ov-img img { max-width: 8rem; max-height: 8rem; display: block; }
    /* Shapes are drawn as SVG, so a line is a line at any size and an arrow
       keeps its head. */
    .rl-ov-shape svg { display: block; overflow: visible; }

    /* The chosen thing wears its handles: one to turn and size it, one to
       take it away. Both are outside the box, so neither covers what it is
       attached to. */
    .rl-ov.is-picked { outline: 1.5px dashed rgb(255 255 255 / .85); outline-offset: 4px; }
    .rl-grip { position: absolute; width: 1.6rem; height: 1.6rem; border-radius: 999px;
        display: flex; align-items: center; justify-content: center;
        background: #fff; color: #111; font-size: .72rem; line-height: 1; cursor: pointer;
        box-shadow: 0 2px 8px rgb(0 0 0 / .45); z-index: 3; touch-action: none; }
    .rl-grip-turn { right: -.8rem; bottom: -.8rem; cursor: nwse-resize; }
    .rl-grip-drop { left: -.8rem; top: -.8rem; background: #ef4444; color: #fff; }

    /* ---- sliders and swatches, in the panels under the row ---- */
    .rl-slider { display: flex; align-items: center; gap: .55rem; padding: .25rem 0; }
    .rl-slider > span { flex: none; width: 3.6rem; font-size: .74rem; font-weight: 700;
        color: rgb(255 255 255 / .7); }
    .rl-slider input[type=range] { flex: 1 1 auto; min-width: 0; accent-color: #86b556; }
    .rl-slider b { flex: none; width: 2.8rem; text-align: right; font-size: .72rem;
        font-variant-numeric: tabular-nums; color: rgb(255 255 255 / .85); }
    .rl-hint { font-size: .68rem; line-height: 1.45; color: rgb(255 255 255 / .45); padding: .15rem 0 .25rem; }
    .rl-swatch-lbl { font-size: .68rem; font-weight: 800; letter-spacing: .05em; text-transform: uppercase;
        color: rgb(255 255 255 / .5); padding-top: .25rem; }
    .rl-swatches { display: flex; flex-wrap: wrap; gap: .4rem; padding: .35rem 0 .15rem; }
    .rl-swatch { width: 1.8rem; height: 1.8rem; border-radius: .5rem; border: 2px solid transparent;
        cursor: pointer; padding: 0; }
    .rl-swatch.is-on { border-color: #fff; box-shadow: 0 0 0 2px rgb(0 0 0 / .5); }
    .rl-reset { align-self: flex-start; margin-top: .3rem; padding: .3rem .6rem; border-radius: .5rem;
        border: 1px solid rgb(255 255 255 / .18); background: transparent; color: rgb(255 255 255 / .8);
        font-size: .72rem; font-weight: 700; cursor: pointer; }
    .rl-reset:hover { background: rgb(255 255 255 / .08); }
    .rl-panel { padding-top: .1rem; display: flex; flex-direction: column; }

    /* The middle of the kept window: drag it to move the whole cut. */
    .rl-window-grab { position: absolute; inset: 0 1.1rem; cursor: grab; touch-action: none; }
    .rl-window-grab:active { cursor: grabbing; }
    .rl-textedit { position: absolute; left: .6rem; right: .6rem; bottom: .6rem; z-index: 4;
        padding: .6rem; border-radius: .8rem; background: rgb(17 17 17 / .92);
        display: flex; flex-direction: column; gap: .45rem; }
    .rl-textedit[hidden] { display: none; }
    .rl-textedit input[type=text] { width: 100%; padding: .45rem .6rem; border-radius: .5rem;
        border: 1px solid rgb(255 255 255 / .2); background: rgb(255 255 255 / .08); color: #fff; }
    .rl-fonts { display: flex; gap: .3rem; flex-wrap: wrap; }
    .rl-font { padding: .25rem .55rem; border-radius: 999px; border: 1px solid rgb(255 255 255 / .2);
        background: transparent; color: #fff; font-size: .74rem; cursor: pointer; }
    .rl-font.is-on { background: #fff; color: #111; }
    .rl-inks { display: flex; gap: .35rem; }
    .rl-ink { width: 1.4rem; height: 1.4rem; border-radius: 999px; border: 2px solid rgb(255 255 255 / .35);
        cursor: pointer; padding: 0; }
    .rl-ink.is-on { border-color: #fff; transform: scale(1.12); }
    .rl-size { display: flex; align-items: center; gap: .5rem; color: rgb(255 255 255 / .7); font-size: .72rem; }
    .rl-size input { flex: 1; }
    .rl-textedit-acts { display: flex; gap: .4rem; justify-content: flex-end; }
    .rl-trim { display: flex; align-items: center; gap: .5rem; }
    .rl-trim input[type=range] { flex: 1; }
    .rl-lbl { font-size: .74rem; font-weight: 700; color: rgb(255 255 255 / .6); min-width: 3.2rem; }
    .rl-time { font-size: .74rem; font-weight: 700; min-width: 3rem; text-align: right; font-variant-numeric: tabular-nums; }
    /* They wrap. Seven filters in a sideways scroller means the last three
       are a rumour — and it put a scrollbar under them to say so. */
    .rl-looks { display: flex; gap: .35rem; flex-wrap: wrap; }
    .rl-look { flex: none; padding: .3rem .65rem; border-radius: 999px; border: 1px solid rgb(255 255 255 / .18);
        background: rgb(255 255 255 / .06); color: rgb(255 255 255 / .75); font-size: .74rem;
        font-weight: 700; cursor: pointer; }
    .rl-look.is-on { background: #fff; color: #111; border-color: #fff; }
    /* The music sheet's own rows. They wore .ai-attach-opt, which is declared
       inside the AI module's page and nowhere else, so here they arrived as
       bare markup. */
    .rl-find { display: flex; gap: .5rem; align-items: center; margin-bottom: .6rem; }
    .rl-find-head { margin: .8rem 0 .35rem; font-size: .68rem; font-weight: 800; letter-spacing: .04em;
        text-transform: uppercase; color: var(--color-gray-400); }
    .rl-opt { display: flex; align-items: center; gap: .65rem; width: 100%; text-align: left;
        padding: .55rem .6rem; border-radius: .7rem; background: var(--color-gray-50);
        border: 1px solid transparent; cursor: pointer;
        transition: background .28s cubic-bezier(.22,1,.36,1), border-color .28s cubic-bezier(.22,1,.36,1); }
    .rl-opt:hover { background: var(--color-white); border-color: var(--color-brand-200); }
    .rl-opt-ic { flex: none; width: 2.1rem; height: 2.1rem; border-radius: .6rem; display: flex;
        align-items: center; justify-content: center; background: var(--color-brand-50); font-size: .95rem; }
    .rl-opt-txt { display: flex; flex-direction: column; min-width: 0; flex: 1 1 auto; }
    .rl-opt-txt b { font-size: .82rem; font-weight: 700; color: var(--color-gray-900); line-height: 1.25;
        overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .rl-opt-txt i { font-style: normal; font-size: .68rem; color: var(--color-gray-500);
        overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .rl-opt-lic { flex: none; font-size: .6rem; font-weight: 800; letter-spacing: .02em;
        padding: .1rem .35rem; border-radius: 999px; background: var(--color-gray-200); color: var(--color-gray-600); }
    html.dark .rl-opt { background: rgb(255 255 255 / .04); }
    html.dark .rl-opt:hover { background: rgb(255 255 255 / .08); }
    html.dark .rl-opt-txt b { color: #e8efe1; }
    html.dark .rl-opt-lic { background: rgb(255 255 255 / .1); color: #cdd8c0; }
    @media (prefers-reduced-motion: reduce) { .rl-opt { transition: none; } }

    .rl-music { display: flex; align-items: center; gap: .5rem; padding: .55rem .7rem; border-radius: .7rem;
        border: 1px solid rgb(255 255 255 / .16); background: rgb(255 255 255 / .06); color: #fff;
        font-size: .82rem; font-weight: 700; cursor: pointer; }
    .rl-studio .form-input, .rl-studio .form-textarea {
        background: rgb(255 255 255 / .1); border-color: rgb(255 255 255 / .18); color: #fff; }
    .rl-studio .form-input::placeholder, .rl-studio .form-textarea::placeholder { color: rgb(255 255 255 / .45); }

    .rl-busy { position: absolute; inset: 0; z-index: 4; display: flex; flex-direction: column;
        align-items: center; justify-content: center; gap: .5rem; text-align: center; padding: 1rem;
        background: rgb(11 15 10 / .92); }
    .rl-busy.hidden { display: none; }
    .rl-busy p { font-weight: 700; }
    .rl-busy small { color: rgb(255 255 255 / .55); max-width: 20rem; }
    .rl-spin { width: 2.4rem; height: 2.4rem; border-radius: 999px; border: 3px solid rgb(255 255 255 / .25);
        border-top-color: #fff; animation: rlSpin .8s linear infinite; }
    @keyframes rlSpin { to { transform: rotate(360deg); } }

    @media (prefers-reduced-motion: reduce) {
        .rl-viewer { animation: none; }
        .rl-tile { transition: none; }
        /* Slowed, not stopped: an encode takes real seconds and a still
           spinner reads as a crash. */
        .rl-spin { animation-duration: 2.4s; }
    }
</style>

<script>
(function reels() {
    if (window.__reelsBooted) return;
    window.__reelsBooted = true;

    const URLS = {
        feed: @json(route('community.reels.feed')),
        store: @json(route('community.reels.store')),
        music: @json(route('community.reels.music')),
        musicSearch: @json(route('community.reels.music.search')),
        musicGrab: @json(route('community.reels.music.grab')),
    };
    const LOOKS = ['none', 'warm', 'cool', 'bright', 'punch', 'mono', 'faded'];
    const MAX = 60;
    const $ = (id) => document.getElementById(id);
    const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content || '';
    const esc = (s) => (window.escapeHtml ? window.escapeHtml(s) : String(s ?? ''));

    let reels = [];

    /* ------------------------------------------------------------ rail */
    async function loadRail() {
        const rail = $('rlRail');
        const wrap = $('rlRailWrap');
        if (!rail || !wrap) return;
        /* Shown before the fetch, not after it.
         *
         * The rail carries "Make one", so hiding it until reels arrive means a
         * farmer on a bad connection cannot post one either — the feature
         * disappears exactly when the network is worst. It appears empty and
         * fills in. */
        wrap.hidden = false;
        try {
            const r = await fetch(URLS.feed, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
            const items = ((await r.json()).data || {}).items || [];
            reels = items;
            rail.innerHTML = items.map((it, i) => `
                <button type="button" class="rl-tile" data-reel="${i}" aria-label="Play reel by ${esc(it.author.name)}">
                    ${it.poster
                        ? `<img src="${esc(it.poster)}" alt="" loading="lazy">`
                        /* No cover on the row — an older reel, or one posted
                           where the server could not make one. The clip's own
                           first frame stands in, which beats a black tile. */
                        : `<video src="${esc(it.video)}#t=0.3" muted playsinline preload="metadata"></video>`}
                    <span class="rl-tile-grad"></span>
                    <span class="rl-tile-who"><b>${esc(it.author.name)}</b><i>${it.seconds}s</i></span>
                </button>`).join('');
            if (!items.length) {
                rail.innerHTML = '<p style="font-size:.8rem;color:var(--color-gray-400);padding:.5rem 0">'
                    + 'Wala pang stories — ikaw ang mauna.</p>';
            }
        } catch (_) {
            // The covers could not be fetched; making one still can be.
            rail.innerHTML = '<p style="font-size:.8rem;color:var(--color-gray-400);padding:.5rem 0">'
                + 'Stories could not load just now.</p>';
        }
    }

    /* ---------------------------------------------------------- viewer */
    function openViewer(index) {
        const deck = $('rlDeck');
        deck.innerHTML = reels.map((it) => `
            <div class="rl-slide" data-post="${it.id}">
                <video src="${esc(it.video)}" ${it.poster ? `poster="${esc(it.poster)}"` : ''}
                       playsinline loop preload="metadata" muted></video>
                <button type="button" class="rl-sound" data-rl-sound aria-label="Sound on">
                    <span class="rl-sound-on" hidden>\ud83d\udd0a</span><span class="rl-sound-off">\ud83d\udd07</span>
                </button>
                <div class="rl-side">
                    <button type="button" data-rl-react="${it.id}" aria-label="Like">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><path stroke-linecap="round" stroke-linejoin="round" d="M4.3 12.2l7.7 7.7 7.7-7.7a4.5 4.5 0 10-6.4-6.4L12 6.9l-1.3-1.1a4.5 4.5 0 10-6.4 6.4z"/></svg>
                    </button>
                    <button type="button" class="js-open-comments" data-post-id="${it.id}" aria-label="Comments">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><path stroke-linecap="round" stroke-linejoin="round" d="M8 12h8m-8-4h5m-6 12V6a2 2 0 012-2h12a2 2 0 012 2v8a2 2 0 01-2 2H8l-3 4z"/></svg>
                        <b>${it.comments}</b>
                    </button>
                    <button type="button" class="js-share" data-post-id="${it.id}" aria-label="Share">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><path stroke-linecap="round" stroke-linejoin="round" d="M4 12v7a2 2 0 002 2h12a2 2 0 002-2v-7M16 6l-4-4-4 4M12 2v14"/></svg>
                    </button>
                    <span class="rl-views" title="Views">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><path stroke-linecap="round" stroke-linejoin="round" d="M2.5 12S6 5.5 12 5.5 21.5 12 21.5 12 18 18.5 12 18.5 2.5 12 2.5 12z"/><circle cx="12" cy="12" r="3"/></svg>
                        <b data-view-count="post:${it.id}">${it.views}</b>
                    </span>
                    <button type="button" class="js-bookmark" data-post-id="${it.id}" aria-label="Save">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><path stroke-linecap="round" stroke-linejoin="round" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-4-7 4V5z"/></svg>
                    </button>
                    ${it.mine ? `<button type="button" data-rl-del="${it.id}" aria-label="Delete this story">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.9 12.1a2 2 0 01-2 1.9H7.9a2 2 0 01-2-1.9L5 7m3 0V5a2 2 0 012-2h4a2 2 0 012 2v2m-11 0h16"/></svg>
                    </button>` : ''}
                </div>
                <div class="rl-meta">
                    <span class="rl-meta-who">
                        <span>${it.author.avatar ? `<img src="${esc(it.author.avatar)}" alt="">` : esc(it.author.initials)}</span>
                        ${esc(it.author.name)}
                    </span>
                    ${it.caption ? `<p>${esc(it.caption)}</p>` : ''}
                    ${it.audio ? `<small>🎵 ${esc(it.audio)}</small>` : ''}
                </div>
            </div>`).join('');
        $('rlViewer').classList.remove('hidden');
        document.documentElement.classList.add('overlay-open');
        applySound(deck);
        const slides = deck.querySelectorAll('.rl-slide');
        slides[index]?.scrollIntoView();
        // Opening one is looking at it; a tile in the rail is not.
        const first = reels[index];
        if (first) window.smCountView?.('post', first.id);
        watchSlides(deck);
    }

    /* Sound is off until somebody asks for it — no phone will autoplay a
       video with sound, and a reel that refuses to start looks broken rather
       than quiet. Asked once, it stays on for the rest of the session. */
    let soundOn = false;
    function applySound(scope) {
        (scope || document).querySelectorAll('.rl-slide video').forEach((v) => { v.muted = !soundOn; });
        (scope || document).querySelectorAll('[data-rl-sound]').forEach((b) => {
            b.querySelector('.rl-sound-on').hidden = !soundOn;
            b.querySelector('.rl-sound-off').hidden = soundOn;
        });
    }
    document.addEventListener('click', (e) => {
        if (!e.target.closest('[data-rl-sound]')) return;
        soundOn = !soundOn;
        applySound();
        // Turning it on mid-reel should be audible at once, not next slide.
        const live = document.querySelector('.rl-slide video:not([paused])');
        live?.play?.().catch(() => {});
    });

    /* Taking down your own reel, from where you are watching it. */
    document.addEventListener('click', async (e) => {
        const btn = e.target.closest('[data-rl-del]');
        if (!btn || btn.dataset.busy) return;
        const ok = window.confirmAction
            ? await window.confirmAction({
                title: 'Delete this story?',
                message: 'It comes off the wall and out of the rail for everyone.',
                confirmText: 'Delete',
            })
            : true;
        if (!ok) return;
        btn.dataset.busy = '1';
        try {
            const r = await fetch('{{ url('/app/community/wall') }}/' + btn.getAttribute('data-rl-del'), {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': csrf(), Accept: 'application/json' },
                credentials: 'same-origin',
            });
            const j = await r.json();
            if (!j.success) throw new Error(j.message || 'Could not delete that.');
            window.toast?.('Story deleted.');
            closeViewer();
            loadRail();
        } catch (err) {
            window.toast?.(err.message, 'error');
        } finally { delete btn.dataset.busy; }
    });

    /* Only the reel on screen plays. Anything else is four videos fighting
       over one radio, which on a farm connection means none of them play. */
    function watchSlides(deck) {
        const obs = new IntersectionObserver((entries) => {
            entries.forEach((en) => {
                const v = en.target.querySelector('video');
                if (!v) return;
                if (en.isIntersecting && en.intersectionRatio > 0.6) {
                    v.play().catch(() => {});
                    const id = en.target.getAttribute('data-post');
                    if (id) window.smCountView?.('post', id);
                }
                else v.pause();
            });
        }, { root: deck, threshold: [0, 0.6, 1] });
        deck.querySelectorAll('.rl-slide').forEach((s) => obs.observe(s));
        deck.__obs = obs;
    }

    function closeViewer() {
        const deck = $('rlDeck');
        deck.__obs?.disconnect();
        deck.querySelectorAll('video').forEach((v) => v.pause());
        deck.innerHTML = '';
        $('rlViewer').classList.add('hidden');
        document.documentElement.classList.remove('overlay-open');
    }

    document.addEventListener('click', (e) => {
        const tile = e.target.closest('[data-reel]');
        if (tile) { openViewer(parseInt(tile.dataset.reel, 10)); return; }
        if (e.target.closest('#rlClose')) closeViewer();
    });
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && !$('rlViewer').classList.contains('hidden')) closeViewer();
    });

    // Reacting rides the same endpoint every post uses.
    document.addEventListener('click', async (e) => {
        const btn = e.target.closest('[data-rl-react]');
        if (!btn) return;
        try {
            await fetch(@json(route('community.react')), {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf(), Accept: 'application/json', 'Content-Type': 'application/json' },
                credentials: 'same-origin',
                body: JSON.stringify({ type: 'wallpost', id: btn.dataset.rlReact, reaction: 'like' }),
            });
            btn.style.background = 'rgb(239 68 68 / .85)';
        } catch (_) { window.toast?.('Could not react just now.', 'error'); }
    });

    /* ---------------------------------------------------------- studio */
    let chosen = null, look = 'none', audioFile = null, audioName = null, media = null;
    // What the strip is showing, so nothing else has to guess at it.
    let audioTitle = '', audioUrl = '';

    /* The one place the chosen track is set.
     *
     * A fetched track kept being renamed by whatever touched the label next;
     * this holds the name, the file and the sound to preview together, so
     * they cannot disagree. */
    function setTrack({ file = null, name = null, title = '', url = '' } = {}) {
        audioFile = file;
        audioName = name;
        audioTitle = title;
        audioUrl = url;
        const on = !!(file || name);
        $('rlMusicName').textContent = on ? (title || 'Music added') : 'Add music';
        $('rlMusicSub').textContent = on
            ? 'Tap to change it'
            : 'Free to use, or a file from this phone';
        $('rlMusicPlay').classList.toggle('hidden', !on || !url);
        $('rlMusicX').classList.toggle('hidden', !on);
        stopTrack();
    }

    function stopTrack() {
        const a = $('rlMusicAudio');
        if (!a) return;
        a.pause();
        $('rlMusicPlay').textContent = '\u25b6';
    }

    /* Hearing it before it is under a reel: the whole reason to choose one. */
    $('rlMusicPlay')?.addEventListener('click', () => {
        const a = $('rlMusicAudio');
        if (!a || !audioUrl) return;
        if (a.paused) {
            if (a.src !== audioUrl) a.src = audioUrl;
            a.play().then(() => { $('rlMusicPlay').textContent = '\u23f8'; }).catch(() => {});
        } else { stopTrack(); }
    });
    $('rlMusicAudio')?.addEventListener('ended', stopTrack);
    $('rlMusicX')?.addEventListener('click', (e) => { e.stopPropagation(); setTrack(); });

    function studio(on) {
        $('rlStudio').classList.toggle('hidden', !on);
        document.documentElement.classList.toggle('overlay-open', on);
        if (!on) {
            $('rlPreview').pause();
            $('rlPreview').removeAttribute('src');
            chosen = null; look = 'none';
            overlays = [];
            picked = null;
            frame = { scale: 1, rotate: 0, bg: '#000000' };
            mix = { music: 0.85, own: 0 };
            earOn(false);
            paintFrame();
            setTrack();
            drawOverlays();
            $('rlPick').classList.remove('hidden');
            $('rlEdit').classList.add('hidden');
            $('rlPost').classList.add('hidden');
            $('rlBusy').classList.add('hidden');
            $('rlStep').textContent = 'Make a story';
        }
    }

    $('rlNew')?.addEventListener('click', () => studio(true));
    $('rlCancel')?.addEventListener('click', () => studio(false));
    $('rlRecord')?.addEventListener('click', () => $('rlFileCam').click());
    $('rlUpload')?.addEventListener('click', () => $('rlFile').click());

    $('rlFromGallery')?.addEventListener('click', () => {
        if (typeof window.smPickMedia !== 'function') { window.toast?.('The gallery is not available here.', 'error'); return; }
        liftSheets(true);   // the picker is a sheet too, and this studio is above sheets
        window.smPickMedia({
            allSchedules: true,
            kinds: 'video',
            title: 'Pick a clip',
            onPick: async (item) => {
                // A gallery clip has to become a real file to be re-encoded;
                // it is already ours, so fetching it back is safe.
                try {
                    const r = await fetch(item.url, { credentials: 'same-origin' });
                    const blob = await r.blob();
                    takeVideo(new File([blob], 'clip.mp4', { type: blob.type || 'video/mp4' }));
                } catch (_) { window.toast?.('That clip could not be opened.', 'error'); }
            },
        });
    });

    [$('rlFile'), $('rlFileCam')].forEach((input) => input?.addEventListener('change', (e) => {
        const f = e.target.files && e.target.files[0];
        if (f) takeVideo(f);
        e.target.value = '';
    }));

    /* ---- the clip, and the window kept from it ---------------------- */
    let clipDur = 0;            // the whole clip, seconds
    let trimA = 0, trimB = 15;  // the window, seconds

    function takeVideo(file) {
        chosen = file;
        const url = URL.createObjectURL(file);
        const v = $('rlPreview');
        v.src = url;
        v.onloadedmetadata = () => {
            clipDur = Math.max(1, Math.min(v.duration || MAX, 3600));
            trimA = 0;
            trimB = Math.min(MAX, clipDur);
            drawTrim();
            filmstrip(v);
            v.currentTime = 0;
            v.play().catch(() => {});
        };
        // The clip loops inside the window, not past the end of it — what you
        // watch here is what gets posted.
        v.ontimeupdate = () => {
            if (!clipDur) return;
            if (v.currentTime >= trimB - 0.05 || v.currentTime < trimA - 0.05) v.currentTime = trimA;
            const p = ((v.currentTime - trimA) / Math.max(0.1, trimB - trimA)) * 100;
            $('rlPlayhead').style.left = Math.max(0, Math.min(100, p)) + '%';
        };
        $('rlPick').classList.add('hidden');
        $('rlEdit').classList.remove('hidden');
        $('rlPost').classList.remove('hidden');
        $('rlStep').textContent = 'Make it yours';
        paintLooks();
    }

    /* Real frames under the clip, so the handles have something to aim at.
     * Drawn in the browser from the file already in hand — no upload, no
     * round trip, and the strip is the clip rather than a grey bar. */
    async function filmstrip(v) {
        const film = $('rlFilm');
        film.innerHTML = '';
        const shots = 8;
        const c = document.createElement('canvas');
        c.width = 90; c.height = 160;
        const ctx = c.getContext('2d');
        const grab = document.createElement('video');
        grab.src = v.src; grab.muted = true; grab.playsInline = true;
        try {
            await new Promise((ok, no) => {
                grab.onloadeddata = ok; grab.onerror = no;
                setTimeout(no, 4000);
            });
            for (let i = 0; i < shots; i++) {
                const at = (clipDur / shots) * i + 0.05;
                await new Promise((ok) => { grab.onseeked = ok; grab.currentTime = Math.min(at, clipDur - 0.05); setTimeout(ok, 900); });
                ctx.drawImage(grab, 0, 0, c.width, c.height);
                const img = document.createElement('img');
                img.src = c.toDataURL('image/jpeg', 0.6);
                film.appendChild(img);
            }
        } catch (_) {
            // No frames to be had: the strip stays a plain bar, and the
            // handles still work on it.
            film.classList.add('rl-film-bare');
        }
    }

    function drawTrim() {
        const w = $('rlWindow');
        const a = (trimA / Math.max(0.1, clipDur)) * 100;
        const b = (trimB / Math.max(0.1, clipDur)) * 100;
        w.style.left = a + '%';
        w.style.width = Math.max(2, b - a) + '%';
        $('rlTrimSay').textContent = trimA.toFixed(1) + 's \u2192 ' + trimB.toFixed(1) + 's';
        $('rlTrimLen').textContent = Math.round(trimB - trimA) + 's';
    }

    /* Dragging an end. Pointer events, so a finger and a mouse are the same
     * code, and the clip seeks as the handle moves — you choose a moment by
     * seeing it, not by reading a number. */
    (function trimHandles() {
        const bar = $('rlTimeline');
        if (!bar) return;
        let dragging = null;
        const at = (e) => {
            const r = bar.getBoundingClientRect();
            return Math.max(0, Math.min(1, (e.clientX - r.left) / Math.max(1, r.width))) * clipDur;
        };
        let grabbedAt = 0, grabbedLen = 0;
        bar.addEventListener('pointerdown', (e) => {
            const h = e.target.closest('[data-rl-handle]');
            if (!h || !clipDur) return;
            dragging = h.getAttribute('data-rl-handle');
            // Moving the whole window: remember where inside it the finger
            // landed, so it does not jump to centre under the thumb.
            grabbedAt = at(e) - trimA;
            grabbedLen = trimB - trimA;
            bar.setPointerCapture(e.pointerId);
            e.preventDefault();
        });
        bar.addEventListener('pointermove', (e) => {
            if (!dragging) return;
            const t = at(e);
            if (dragging === 'a') {
                trimA = Math.min(t, trimB - 1);
                // Sixty seconds is the rule, so the far end follows the near
                // one rather than letting the window grow past it.
                if (trimB - trimA > MAX) trimB = trimA + MAX;
                $('rlPreview').currentTime = trimA;
            } else if (dragging === 'mid') {
                // The cut keeps its length and slides along the clip.
                const want = Math.max(0, Math.min(clipDur - grabbedLen, t - grabbedAt));
                trimA = want;
                trimB = want + grabbedLen;
                $('rlPreview').currentTime = trimA;
            } else {
                trimB = Math.max(t, trimA + 1);
                if (trimB - trimA > MAX) trimA = trimB - MAX;
                $('rlPreview').currentTime = Math.max(trimA, trimB - 0.3);
            }
            drawTrim();
        });
        ['pointerup', 'pointercancel'].forEach((ev) => bar.addEventListener(ev, () => {
            if (!dragging) return;
            dragging = null;
            $('rlPreview').currentTime = trimA;
            $('rlPreview').play().catch(() => {});
        }));
    })();

    /* ---- the looks, and the panels they live in --------------------- */
    function paintLooks() {
        const host = $('rlLooks');
        host.innerHTML = LOOKS.map((l) => `<button type="button" class="rl-look${l === look ? ' is-on' : ''}" data-look="${l}">${l === 'none' ? 'Original' : l[0].toUpperCase() + l.slice(1)}</button>`).join('');
    }
    $('rlLooks')?.addEventListener('click', (e) => {
        const b = e.target.closest('[data-look]');
        if (!b) return;
        look = b.dataset.look;
        paintLooks();
        // Previewed with the browser's own filter, which is close enough to
        // choose by and free; the encoder does the real one.
        const map = { none: 'none', warm: 'saturate(1.25) sepia(.18)', cool: 'saturate(1.1) hue-rotate(-12deg)',
            bright: 'brightness(1.18)', punch: 'saturate(1.6) contrast(1.18)', mono: 'grayscale(1)',
            faded: 'saturate(.75) brightness(1.08) contrast(.92)' };
        $('rlPreview').style.filter = map[look] || 'none';
    });

    function panel(which) {
        ['rlLooksPanel', 'rlSayPanel', 'rlFramePanel', 'rlSoundPanel'].forEach((id) => {
            const el = $(id);
            if (el) el.classList.toggle('hidden', id !== which || !el.classList.contains('hidden'));
        });
    }
    $('rlLooksBtn')?.addEventListener('click', () => panel('rlLooksPanel'));
    $('rlSayBtn')?.addEventListener('click', () => panel('rlSayPanel'));
    $('rlFrameBtn')?.addEventListener('click', () => { paintBackdrops(); paintFrame(); panel('rlFramePanel'); });
    $('rlSoundBtn')?.addEventListener('click', () => { paintMix(); panel('rlSoundPanel'); });

    /* ---- two sounds, and how loud each is -------------------------- */
    function paintMix() {
        const m = $('rlVolMusic'), o = $('rlVolOwn');
        if (m) m.value = String(mix.music);
        if (o) o.value = String(mix.own);
        const ms = $('rlVolMusicSay'), os = $('rlVolOwnSay');
        if (ms) ms.textContent = mix.music <= 0 ? 'off' : Math.round(mix.music * 100) + '%';
        if (os) os.textContent = mix.own <= 0 ? 'off' : Math.round(mix.own * 100) + '%';
        // What is being heard right now follows what is being set.
        const v = $('rlPreview'), a = $('rlMusicAudio');
        if (v && !v.muted) v.volume = mix.own;
        if (a) a.volume = mix.music;
    }
    $('rlVolMusic')?.addEventListener('input', (e) => { mix.music = parseFloat(e.target.value); paintMix(); });
    $('rlVolOwn')?.addEventListener('input', (e) => {
        mix.own = parseFloat(e.target.value);
        // Turning the original up is asking to hear it.
        const v = $('rlPreview');
        if (v && mix.own > 0 && v.muted) earOn(true);
        paintMix();
    });

    /* Hearing the clip while cutting it.
     *
     * It starts muted because a browser will not autoplay sound; one tap
     * turns it on, and the chosen music plays alongside at the balance the
     * sliders are set to -- which is the only way to judge that balance. */
    function earOn(on) {
        const v = $('rlPreview'), a = $('rlMusicAudio'), btn = $('rlEar');
        if (!v) return;
        v.muted = !on;
        v.volume = mix.own;
        if (btn) {
            btn.setAttribute('aria-pressed', on ? 'true' : 'false');
            btn.textContent = on ? '🔊' : '🔇';
        }
        if (!a) return;
        if (on && audioUrl) {
            if (a.src !== audioUrl) a.src = audioUrl;
            a.loop = true;
            a.volume = mix.music;
            a.play().catch(() => {});
        } else {
            a.pause();
        }
    }
    $('rlEar')?.addEventListener('click', () => earOn($('rlPreview')?.muted !== false));

    /* ---- things stuck on the picture -------------------------------- */
    /* Fonts a phone actually has, named for what they look like rather than
       for what they are called. `draw` is the same stack without the CSS
       variable, because a canvas cannot read one. */
    /* Single quotes in `css` on purpose: this string is written into a
       style="" attribute, and a family name in double quotes ends the
       attribute early -- which silently dropped the font AND the colour that
       followed it. `draw` is a JS string for the canvas, where quotes are
       only quotes. */
    const FONTS = [
        { key: 'sans', name: 'Plain', css: 'system-ui, sans-serif', draw: '600 %spx system-ui, sans-serif' },
        { key: 'head', name: 'Heading', css: 'var(--font-heading), sans-serif', draw: '800 %spx "Instrument Sans", system-ui, sans-serif' },
        { key: 'serif', name: 'Serif', css: "Georgia, 'Times New Roman', serif", draw: '700 %spx Georgia, "Times New Roman", serif' },
        { key: 'slab', name: 'Poster', css: "Rockwell, 'Courier New', Georgia, serif", draw: '800 %spx Rockwell, "Courier New", Georgia, serif' },
        { key: 'mono', name: 'Typewriter', css: "ui-monospace, 'Courier New', monospace", draw: '700 %spx "Courier New", ui-monospace, monospace' },
        { key: 'round', name: 'Soft', css: "'Trebuchet MS', Verdana, sans-serif", draw: '700 %spx "Trebuchet MS", Verdana, sans-serif' },
        { key: 'wide', name: 'Wide', css: "'Arial Black', Impact, sans-serif", draw: '900 %spx "Arial Black", Impact, sans-serif' },
        { key: 'script', name: 'Handwriting', css: "'Segoe Script', 'Brush Script MT', cursive", draw: '600 %spx "Segoe Script", "Brush Script MT", cursive' },
    ];
    const INKS = ['#ffffff', '#111111', '#ffd54a', '#7cf07c', '#8fd0ff', '#ff8fb3', '#ff5252', '#4a7c2a'];
    // What the leftover space around a placed picture is filled with.
    const BACKDROPS = ['#000000', '#ffffff', '#2d5016', '#4a7c2a', '#f5c518', '#1d4ed8', '#7c3aed', '#be185d', '#111827'];

    let overlays = [];   // { kind:'text'|'image'|'shape', ... }
    let picked = null;   // which one wears the handles

    /* Where the picture sits inside the reel, and what fills the rest.
     * Sent to the encoder as-is; the preview applies the same numbers as a
     * CSS transform, so the two cannot drift. */
    let frame = { scale: 1, rotate: 0, bg: '#000000' };
    // How loud each sound is. The original starts at nothing, which is what
    // choosing music has always meant here -- but now it can be turned up.
    let mix = { music: 0.85, own: 0 };

    $('rlAddBtn')?.addEventListener('click', () => {
        liftSheets(true);
        window.openSheet?.('rlAddSheet');
    });
    document.addEventListener('click', (e) => {
        if (e.target.closest('[data-rl-add-text]')) {
            window.closeSheet?.('rlAddSheet');
            addText();
            return;
        }
        if (e.target.closest('[data-rl-add-image]')) {
            window.closeSheet?.('rlAddSheet');
            $('rlSticker').click();
            return;
        }
        const shape = e.target.closest('[data-rl-add]');
        if (shape) {
            window.closeSheet?.('rlAddSheet');
            addShape(shape.getAttribute('data-rl-add'));
        }
    });

    function addText() {
        const o = { kind: 'text', text: '', font: 'sans', ink: '#ffffff', size: 1, rot: 0, x: 50, y: 78 };
        overlays.push(o);
        picked = overlays.length - 1;
        drawOverlays();
        openTextEditor(overlays.length - 1);
    }

    /* A shape, dropped in the middle for the thumb to take somewhere.
     *
     * Arrows and lines start lying flat and get turned by the same handle
     * that sizes them, which is how you point one at something. */
    function addShape(shape) {
        overlays.push({
            kind: 'shape', shape: shape || 'rect',
            ink: '#ffd54a', size: 1, rot: shape === 'arrow' ? -20 : 0, x: 50, y: 50,
        });
        picked = overlays.length - 1;
        drawOverlays();
        openShapeEditor(picked);
    }

    function openTextEditor(i) {
        const o = overlays[i];
        if (!o || o.kind !== 'text') return;
        const box = $('rlTextEdit');
        box.hidden = false;
        box.dataset.at = String(i);
        box.dataset.kind = 'text';
        $('rlTextInput').classList.remove('hidden');
        $('rlTextInput').value = o.text;
        $('rlTextFonts').innerHTML = FONTS.map((f) => `<button type="button" class="rl-font${f.key === o.font ? ' is-on' : ''}" data-font="${f.key}" style="font-family:${f.css}">${f.name}</button>`).join('');
        $('rlTextInks').innerHTML = INKS.map((c) => `<button type="button" class="rl-ink${c === o.ink ? ' is-on' : ''}" data-ink="${c}" style="background:${c}" aria-label="Colour"></button>`).join('');
        $('rlTextSize').value = String(o.size);
        $('rlTextInput').focus();
    }

    document.addEventListener('input', (e) => {
        const box = $('rlTextEdit');
        if (!box || box.hidden) return;
        const o = overlays[parseInt(box.dataset.at, 10)];
        if (!o) return;
        if (e.target.id === 'rlTextInput') o.text = e.target.value;
        if (e.target.id === 'rlTextSize') o.size = parseFloat(e.target.value);
        drawOverlays();
    });
    document.addEventListener('click', (e) => {
        const box = $('rlTextEdit');
        if (!box || box.hidden) return;
        const o = overlays[parseInt(box.dataset.at, 10)];
        const f = e.target.closest('[data-font]');
        const c = e.target.closest('[data-ink]');
        const reopen = (i) => (box.dataset.kind === 'shape' ? openShapeEditor(i) : openTextEditor(i));
        if (f && o) { o.font = f.dataset.font; reopen(parseInt(box.dataset.at, 10)); drawOverlays(); }
        if (c && o) { o.ink = c.dataset.ink; reopen(parseInt(box.dataset.at, 10)); drawOverlays(); }
        if (e.target.closest('#rlTextDone')) { box.hidden = true; }
        if (e.target.closest('#rlTextDrop')) {
            overlays.splice(parseInt(box.dataset.at, 10), 1);
            box.hidden = true;
            drawOverlays();
        }
    });

    $('rlSticker')?.addEventListener('change', (e) => {
        const f = e.target.files && e.target.files[0];
        e.target.value = '';
        if (!f) return;
        overlays.push({ kind: 'image', file: f, url: URL.createObjectURL(f), x: 50, y: 45, size: 1 });
        drawOverlays();
    });

    /* The shapes, as SVG.
     *
     * Drawn rather than boxed with CSS: a line stays a line at any size, an
     * arrow keeps its head, and the same path maths draws them again on the
     * canvas that goes to the encoder. Each is 200x200 in its own units and
     * scaled by the object's size. */
    const SHAPE_BOX = 200;

    function shapeSvg(o) {
        const ink = esc(o.ink);
        const w = 6;
        const body = {
            rect: `<rect x="14" y="44" width="172" height="112" rx="10" fill="none" stroke="${ink}" stroke-width="${w}"/>`,
            ellipse: `<ellipse cx="100" cy="100" rx="86" ry="58" fill="none" stroke="${ink}" stroke-width="${w}"/>`,
            line: `<line x1="14" y1="100" x2="186" y2="100" stroke="${ink}" stroke-width="${w}" stroke-linecap="round"/>`,
            arrow: `<line x1="16" y1="100" x2="160" y2="100" stroke="${ink}" stroke-width="${w}" stroke-linecap="round"/>`
                + `<path d="M186 100 L150 78 L150 122 Z" fill="${ink}"/>`,
        }[o.shape] || '';
        return `<svg viewBox="0 0 ${SHAPE_BOX} ${SHAPE_BOX}" width="${SHAPE_BOX}" height="${SHAPE_BOX}"`
            + ` style="filter:drop-shadow(0 2px 6px rgb(0 0 0 / .55))">${body}</svg>`;
    }

    const gripsHtml = () => '<span class="rl-grip rl-grip-drop" data-grip="drop" aria-label="Remove">✕</span>'
        + '<span class="rl-grip rl-grip-turn" data-grip="turn" aria-label="Turn and size">⤡</span>';

    function drawOverlays() {
        const layer = $('rlLayer');
        if (!layer) return;
        layer.innerHTML = overlays.map((o, i) => {
            const on = i === picked ? ' is-picked' : '';
            const grips = i === picked ? gripsHtml() : '';
            const at = `left:${o.x}%;top:${o.y}%;`;
            const turn = `transform:translate(-50%,-50%) rotate(${o.rot || 0}deg) scale(${o.size});`;
            if (o.kind === 'image') {
                return `<span class="rl-ov rl-ov-img${on}" data-ov="${i}" style="${at}${turn}"><img src="${esc(o.url)}" alt="">${grips}</span>`;
            }
            if (o.kind === 'shape') {
                return `<span class="rl-ov rl-ov-shape${on}" data-ov="${i}" style="${at}${turn}">${shapeSvg(o)}${grips}</span>`;
            }
            const font = (FONTS.find((f) => f.key === o.font) || FONTS[0]).css;
            return `<span class="rl-ov rl-ov-text${on}" data-ov="${i}" style="${at}${turn}font-family:${font};color:${esc(o.ink)}">${esc(o.text || 'Your words')}${grips}</span>`;
        }).join('');
    }

    /* The shape editor: the same strip of colours the words use, and a size
       slider, because a shape has no text to type. */
    function openShapeEditor(i) {
        const o = overlays[i];
        if (!o || o.kind !== 'shape') return;
        const box = $('rlTextEdit');
        box.hidden = false;
        box.dataset.at = String(i);
        box.dataset.kind = 'shape';
        $('rlTextInput').classList.add('hidden');
        $('rlTextFonts').innerHTML = '';
        $('rlTextInks').innerHTML = INKS.map((c) => `<button type="button" class="rl-ink${c === o.ink ? ' is-on' : ''}" data-ink="${c}" style="background:${c}" aria-label="Colour"></button>`).join('');
        $('rlTextSize').value = String(o.size);
    }

    /* Hands on the objects.
     *
     * Three things happen on this layer and they share one pointer: dragging
     * something to a place, turning and sizing it by its corner handle, and
     * taking it off. The handles belong to whichever object is chosen, so
     * nothing is covered by a control for something else. */
    (function handleOverlays() {
        const layer = $('rlLayer');
        if (!layer) return;
        let mode = null, at = null, from = null;

        const pct = (e) => {
            const r = layer.getBoundingClientRect();
            return {
                x: ((e.clientX - r.left) / Math.max(1, r.width)) * 100,
                y: ((e.clientY - r.top) / Math.max(1, r.height)) * 100,
                r,
            };
        };

        layer.addEventListener('pointerdown', (e) => {
            const grip = e.target.closest('[data-grip]');
            const el = e.target.closest('[data-ov]');
            if (!el) { picked = null; drawOverlays(); return; }
            at = parseInt(el.getAttribute('data-ov'), 10);
            picked = at;
            const o = overlays[at];
            if (!o) return;
            if (grip && grip.getAttribute('data-grip') === 'drop') {
                overlays.splice(at, 1);
                picked = null;
                at = null;
                $('rlTextEdit').hidden = true;
                drawOverlays();
                return;
            }
            mode = grip ? 'turn' : 'move';
            const p = pct(e);
            /* Turning: the angle and the distance from the object's own
               middle, remembered, so both follow the finger from wherever it
               happened to grab. */
            from = {
                a: Math.atan2(p.y - o.y, p.x - o.x),
                d: Math.hypot((p.x - o.x) * p.r.width / 100, (p.y - o.y) * p.r.height / 100),
                rot: o.rot || 0,
                size: o.size,
            };
            layer.setPointerCapture(e.pointerId);
            drawOverlays();
            e.preventDefault();
        });

        layer.addEventListener('pointermove', (e) => {
            if (mode === null || at === null) return;
            const o = overlays[at];
            if (!o) return;
            const p = pct(e);
            if (mode === 'move') {
                o.x = Math.max(3, Math.min(97, p.x));
                o.y = Math.max(4, Math.min(96, p.y));
            } else {
                const a = Math.atan2(p.y - o.y, p.x - o.x);
                const d = Math.hypot((p.x - o.x) * p.r.width / 100, (p.y - o.y) * p.r.height / 100);
                o.rot = Math.round(from.rot + (a - from.a) * 180 / Math.PI);
                o.size = Math.max(0.3, Math.min(4, from.size * (d / Math.max(8, from.d))));
                if ($('rlTextEdit') && !$('rlTextEdit').hidden) $('rlTextSize').value = String(o.size);
            }
            drawOverlays();
        });

        ['pointerup', 'pointercancel'].forEach((ev) => layer.addEventListener(ev, () => { mode = null; }));

        // A tap on what is already chosen opens it for editing.
        layer.addEventListener('click', (e) => {
            if (e.target.closest('[data-grip]')) return;
            const el = e.target.closest('[data-ov]');
            if (!el) return;
            const i = parseInt(el.getAttribute('data-ov'), 10);
            const o = overlays[i];
            if (!o) return;
            if (o.kind === 'text') openTextEditor(i);
            else if (o.kind === 'shape') openShapeEditor(i);
        });
    })();

    /* Placing the picture itself.
     *
     * Two fingers on the reel pinch it bigger or smaller and twist it round;
     * the sliders in the Frame panel do the same for a mouse, which has no
     * second finger. Whatever is left over takes the backdrop colour. */
    (function placeFrame() {
        const reel = $('rlReel');
        if (!reel) return;
        const touches = new Map();
        let start = null;

        const spread = () => {
            const [a, b] = [...touches.values()];
            return { d: Math.hypot(b.x - a.x, b.y - a.y), a: Math.atan2(b.y - a.y, b.x - a.x) };
        };

        reel.addEventListener('pointerdown', (e) => {
            // A finger that came for an object is not placing the picture.
            if (e.target.closest('[data-ov]')) return;
            touches.set(e.pointerId, { x: e.clientX, y: e.clientY });
            if (touches.size === 2) {
                const g = spread();
                start = { d: g.d, a: g.a, scale: frame.scale, rot: frame.rotate };
            }
        });
        reel.addEventListener('pointermove', (e) => {
            if (!touches.has(e.pointerId)) return;
            touches.set(e.pointerId, { x: e.clientX, y: e.clientY });
            if (touches.size !== 2 || !start) return;
            const g = spread();
            frame.scale = Math.max(0.4, Math.min(2, start.scale * (g.d / Math.max(20, start.d))));
            frame.rotate = Math.max(-45, Math.min(45, Math.round(start.rot + (g.a - start.a) * 180 / Math.PI)));
            paintFrame();
            e.preventDefault();
        });
        ['pointerup', 'pointercancel', 'pointerleave'].forEach((ev) => reel.addEventListener(ev, (e) => {
            touches.delete(e.pointerId);
            if (touches.size < 2) start = null;
        }));
    })();

    /* The one place the frame is shown, so the preview and what is sent can
       never disagree. */
    function paintFrame() {
        const shot = $('rlShot');
        if (shot) shot.style.transform = `scale(${frame.scale}) rotate(${frame.rotate}deg)`;
        const reel = $('rlReel');
        if (reel) reel.style.background = frame.bg;
        const sc = $('rlFrameScale'), rt = $('rlFrameRot');
        if (sc) sc.value = String(frame.scale);
        if (rt) rt.value = String(frame.rotate);
        const scSay = $('rlFrameScaleSay'), rtSay = $('rlFrameRotSay');
        if (scSay) scSay.textContent = Math.round(frame.scale * 100) + '%';
        if (rtSay) rtSay.textContent = frame.rotate + '\u00b0';
        const swatches = $('rlBackdrops');
        if (swatches) {
            swatches.querySelectorAll('[data-bg]').forEach((b) => {
                b.classList.toggle('is-on', b.getAttribute('data-bg') === frame.bg);
            });
        }
    }

    function paintBackdrops() {
        const host = $('rlBackdrops');
        if (!host || host.childElementCount) return;
        host.innerHTML = BACKDROPS.map((c) =>
            `<button type="button" class="rl-swatch" data-bg="${c}" style="background:${c}" aria-label="Backdrop"></button>`).join('');
    }

    $('rlBackdrops')?.addEventListener('click', (e) => {
        const b = e.target.closest('[data-bg]');
        if (!b) return;
        frame.bg = b.getAttribute('data-bg');
        paintFrame();
    });
    $('rlFrameScale')?.addEventListener('input', (e) => { frame.scale = parseFloat(e.target.value); paintFrame(); });
    $('rlFrameRot')?.addEventListener('input', (e) => { frame.rotate = parseInt(e.target.value, 10); paintFrame(); });
    $('rlFrameReset')?.addEventListener('click', () => { frame = { scale: 1, rotate: 0, bg: frame.bg }; paintFrame(); });

    /* Lift the sheet layer over the studio while a sheet of its own is open,
       and drop it again when that sheet closes. */
    function liftSheets(on) {
        document.documentElement.classList.toggle('rl-picking', !!on);
    }
    document.addEventListener('sm:sheet-closed', (e) => {
        const id = e.detail && e.detail.id;
        if (!id || id === 'rlMusicSheet' || id === 'smMediaPickerSheet') setTimeout(() => liftSheets(false), 0);
    });

    $('rlMusicBtn')?.addEventListener('click', async () => {
        liftSheets(true);
        window.openSheet?.('rlMusicSheet');
        const list = $('rlMusicList');
        list.innerHTML = '<p class="text-sm text-gray-400 text-center py-4">Loading…</p>';
        let items = [];
        try {
            const r = await fetch(URLS.music, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
            items = ((await r.json()).data || {}).items || [];
        } catch (_) {}
        list.innerHTML = row('📱', 'Sound from this phone', 'Pick an audio file off this device', 'data-rl-own')
            + row('🔇', 'Keep the original sound', 'Whatever the camera heard', 'data-rl-nomusic')
            + items.map((m) => row('🎵', m.title, 'From your library', `data-rl-track="${esc(m.name)}"`)).join('');
        // And what the world has to offer, on the first open.
        findMusic('');
    });

    /* One row shape for every kind of choice in this sheet. */
    function row(ic, title, sub, attrs, lic) {
        return `<button type="button" class="rl-opt" ${attrs}>`
            + `<span class="rl-opt-ic">${ic}</span>`
            + `<span class="rl-opt-txt"><b>${esc(title)}</b><i>${esc(sub || '')}</i></span>`
            + (lic ? `<span class="rl-opt-lic">${esc(lic)}</span>` : '')
            + '</button>';
    }

    /* Openly-licensed music, searched through our own server (see the
       controller for why). Every result carries its licence, because free
       here means "free under a licence" and the two are not the same. */
    let findingMusic = false;
    async function findMusic(q) {
        if (findingMusic) return;
        findingMusic = true;
        const found = $('rlMusicFound');
        if (found) found.innerHTML = '<p class="text-xs text-gray-400 px-1 py-2">Looking…</p>';
        try {
            const r = await fetch(URLS.musicSearch + '?q=' + encodeURIComponent(q || ''), {
                headers: { Accept: 'application/json' }, credentials: 'same-origin',
            });
            const d = (await r.json()).data || {};
            const tracks = d.items || [];
            if (found) {
                found.innerHTML = tracks.length
                    ? tracks.map((t) => row('🎼', t.title,
                        (t.by ? t.by + ' · ' : '') + (t.seconds ? t.seconds + 's' : ''),
                        `data-rl-web="${esc(t.url)}" data-rl-web-title="${esc(t.title)}"`, t.licence)).join('')
                    : '<p class="text-xs text-gray-400 px-1 py-2">Nothing found for that. Try another word.</p>';
            }
        } catch (_) {
            if (found) found.innerHTML = '<p class="text-xs text-gray-400 px-1 py-2">The music search could not be reached.</p>';
        } finally { findingMusic = false; }
    }
    $('rlMusicFind')?.addEventListener('submit', () => findMusic($('rlMusicQ').value.trim()));

    /* A track from the web has to be fetched before it can be encoded; the
       server keeps it and hands back a name the encoder already understands. */
    document.addEventListener('click', async (e) => {
        const web = e.target.closest('[data-rl-web]');
        if (!web || web.dataset.busy) return;
        web.dataset.busy = '1';
        const was = web.innerHTML;
        web.innerHTML = '<span class="rl-opt-ic">⏳</span><span class="rl-opt-txt"><b>Fetching…</b></span>';
        try {
            const r = await fetch(URLS.musicGrab, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf(), Accept: 'application/json', 'Content-Type': 'application/json' },
                credentials: 'same-origin',
                body: JSON.stringify({ url: web.getAttribute('data-rl-web') }),
            });
            const j = await r.json();
            if (!j.success) throw new Error(j.message || 'That track could not be fetched.');
            // Kept as fetched: the name the server stored it under, and the
            // title the farmer picked it by, together.
            setTrack({
                name: j.data.name,
                title: web.getAttribute('data-rl-web-title') || 'Music added',
                url: '{{ asset('storage/reel-music') }}/' + j.data.name,
            });
            window.closeSheet?.('rlMusicSheet');
        } catch (err) {
            window.toast?.(err.message, 'error');
            web.innerHTML = was;
        } finally { delete web.dataset.busy; }
    });

    document.addEventListener('click', (e) => {
        if (e.target.closest('[data-rl-own]')) { window.closeSheet?.('rlMusicSheet'); $('rlAudio').click(); return; }
        if (e.target.closest('[data-rl-nomusic]')) {
            setTrack();
            window.closeSheet?.('rlMusicSheet');
            return;
        }
        const track = e.target.closest('[data-rl-track]');
        if (track) {
            setTrack({
                name: track.dataset.rlTrack,
                title: (track.querySelector('b')?.textContent || track.textContent).trim(),
                url: '{{ asset('storage/reel-music') }}/' + track.dataset.rlTrack,
            });
            window.closeSheet?.('rlMusicSheet');
        }
    });
    $('rlAudio')?.addEventListener('change', (e) => {
        const own = e.target.files && e.target.files[0];
        if (own) setTrack({ file: own, title: own.name, url: URL.createObjectURL(own) });
        e.target.value = '';
    });

    /* Everything stuck on, drawn once at the size it will be posted at.
     *
     * The reel is 1080x1920; the editor showed it a few hundred pixels tall.
     * Every object holds its place as a percentage and its size as a
     * multiple, so both mean the same thing at either size — and this draws
     * them with the very fonts the farmer picked, turned by the very angles
     * they turned them to. */
    const SHEET_W = 1080, SHEET_H = 1920;

    async function overlaySheet() {
        const live = overlays.filter((o) => o.kind !== 'text' || (o.text || '').trim() !== '');
        if (!live.length) return null;

        const c = document.createElement('canvas');
        c.width = SHEET_W; c.height = SHEET_H;
        const ctx = c.getContext('2d');

        for (const o of live) {
            ctx.save();
            ctx.translate((o.x / 100) * SHEET_W, (o.y / 100) * SHEET_H);
            ctx.rotate(((o.rot || 0) * Math.PI) / 180);

            if (o.kind === 'text') {
                // 4.2% of the height is what "size 1" looked like on screen.
                const px = Math.round(SHEET_H * 0.042 * (o.size || 1));
                const font = FONTS.find((f) => f.key === o.font) || FONTS[0];
                ctx.font = font.draw.replace('%s', String(px));
                ctx.textAlign = 'center';
                ctx.textBaseline = 'middle';
                ctx.shadowColor = 'rgba(0,0,0,.65)';
                ctx.shadowBlur = px * 0.25;
                ctx.shadowOffsetY = px * 0.04;
                ctx.fillStyle = o.ink || '#ffffff';
                // Wrapped the way the box wrapped it: at the reel's width.
                const lines = wrapLine(ctx, (o.text || '').trim(), SHEET_W * 0.86);
                const step = px * 1.18;
                lines.forEach((line, i) => {
                    ctx.fillText(line, 0, (i - (lines.length - 1) / 2) * step);
                });
            } else if (o.kind === 'shape') {
                drawShape(ctx, o);
            } else if (o.kind === 'image' && o.url) {
                const img = await loadImage(o.url);
                if (img) {
                    // The same 8rem cap the preview draws it under, in the
                    // reel's own units.
                    const cap = SHEET_W * 0.42 * (o.size || 1);
                    const k = Math.min(cap / img.width, cap / img.height);
                    ctx.drawImage(img, (-img.width * k) / 2, (-img.height * k) / 2, img.width * k, img.height * k);
                }
            }
            ctx.restore();
        }

        return await new Promise((ok) => c.toBlob(ok, 'image/png'));
    }

    function wrapLine(ctx, text, max) {
        const words = text.split(/\s+/);
        const out = [];
        let line = '';
        words.forEach((w) => {
            const test = line ? line + ' ' + w : w;
            if (ctx.measureText(test).width > max && line) { out.push(line); line = w; }
            else line = test;
        });
        if (line) out.push(line);
        return out.slice(0, 6);
    }

    function loadImage(url) {
        return new Promise((ok) => {
            const img = new Image();
            img.onload = () => ok(img);
            img.onerror = () => ok(null);
            img.src = url;
        });
    }

    /* The same shapes the SVG draws, in canvas terms. Kept beside each other
       on purpose: if one changes, the other is right there. */
    function drawShape(ctx, o) {
        const k = (SHEET_W * 0.5 * (o.size || 1)) / SHAPE_BOX;   // box units -> reel pixels
        ctx.scale(k, k);
        ctx.translate(-SHAPE_BOX / 2, -SHAPE_BOX / 2);
        ctx.strokeStyle = o.ink || '#ffd54a';
        ctx.fillStyle = o.ink || '#ffd54a';
        ctx.lineWidth = 6;
        ctx.lineCap = 'round';
        ctx.shadowColor = 'rgba(0,0,0,.55)';
        ctx.shadowBlur = 6;
        ctx.shadowOffsetY = 2;
        if (o.shape === 'rect') {
            ctx.beginPath();
            if (ctx.roundRect) ctx.roundRect(14, 44, 172, 112, 10);
            else ctx.rect(14, 44, 172, 112);
            ctx.stroke();
        } else if (o.shape === 'ellipse') {
            ctx.beginPath();
            ctx.ellipse(100, 100, 86, 58, 0, 0, Math.PI * 2);
            ctx.stroke();
        } else if (o.shape === 'line') {
            ctx.beginPath(); ctx.moveTo(14, 100); ctx.lineTo(186, 100); ctx.stroke();
        } else {
            ctx.beginPath(); ctx.moveTo(16, 100); ctx.lineTo(160, 100); ctx.stroke();
            ctx.beginPath(); ctx.moveTo(186, 100); ctx.lineTo(150, 78); ctx.lineTo(150, 122); ctx.closePath(); ctx.fill();
        }
    }

    /* A cover frame, drawn from the preview at the start of the window. */
    async function coverShot() {
        const v = $('rlPreview');
        if (!v || !v.videoWidth) return null;
        try {
            const c = document.createElement('canvas');
            // Portrait, at the shape a reel is posted in.
            c.width = 720; c.height = 1280;
            const ctx = c.getContext('2d');
            // Cover-fit the frame into the portrait box, as the encoder does.
            const scale = Math.max(c.width / v.videoWidth, c.height / v.videoHeight);
            const w = v.videoWidth * scale, h = v.videoHeight * scale;
            ctx.drawImage(v, (c.width - w) / 2, (c.height - h) / 2, w, h);
            return await new Promise((ok) => c.toBlob(ok, 'image/jpeg', 0.8));
        } catch (_) {
            return null;
        }
    }

    $('rlPost')?.addEventListener('click', async () => {
        if (!chosen) return;
        $('rlBusy').classList.remove('hidden');
        const fd = new FormData();
        fd.append('video', chosen);
        fd.append('caption', $('rlCaption').value.trim());
        fd.append('start', trimA.toFixed(2));
        fd.append('duration', Math.max(1, Math.round(trimB - trimA)));
        fd.append('look', look);
        // Where the picture was put, and what fills the rest of the reel.
        fd.append('frame', JSON.stringify(frame));
        /* Everything stuck on, as one drawn sheet. The words still travel as
           description too — an encoder that cannot take a sheet can at least
           burn the text — but the sheet is what gets used when it arrives. */
        fd.append('overlays', JSON.stringify(overlays
            .filter((o) => o.kind === 'text' && (o.text || '').trim() !== '')
            .map((o) => ({ text: o.text.trim(), font: o.font, ink: o.ink, size: o.size, x: o.x, y: o.y }))));
        const sheet = await overlaySheet();
        if (sheet) fd.append('overlaySheet', sheet, 'sheet.png');
        if (audioFile) fd.append('audio', audioFile);
        if (audioName) fd.append('audioName', audioName);
        // How loud each sound is. Music no longer throws the original away.
        fd.append('musicVolume', String(mix.music));
        fd.append('originalVolume', String(mix.own));
        // A cover taken here, from the frame the farmer left it on. The server
        // makes its own when it can; this is what a reel shows when it could
        // not — and a reel with no picture in the rail is a black tile.
        const cover = await coverShot();
        if (cover) fd.append('poster', cover, 'cover.jpg');
        try {
            const r = await fetch(URLS.store, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf(), Accept: 'application/json' },
                credentials: 'same-origin',
                body: fd,
            });
            const j = await r.json();
            if (!r.ok || j.success === false) throw new Error(j.message || 'Could not post that.');
            studio(false);
            window.toast?.('Story posted.');
            loadRail();
        } catch (err) {
            $('rlBusy').classList.add('hidden');
            window.toast?.(err.message, 'error');
        }
    });

    loadRail();
})();
</script>
@endonce
