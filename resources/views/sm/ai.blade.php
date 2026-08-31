@extends(request()->boolean('partial') ? 'layouts.partial' : 'layouts.app')
{{-- On phones the full page is a chat app: tab bar steps aside (only
     layouts.app reads this — the in-shell partial keeps its pane). --}}
@section('body-class', 'hide-tabbar')

@section('title', $settings->assistantName . ' — ' . $schedule->title)
{{-- Her name AND what she is for. Every other screen in this app names the
     thing you are looking at; this one is a person, and a person who has just
     been introduced is worth introducing properly. The name is read from the
     settings so a farm that renames her keeps the rest of the sentence. --}}
@section('page-title', $settings->assistantName . ', Your Smart Agricultural Technician')
@section('page-subtitle', $schedule->title)
@section('help-key', 'ai')
@section('back', route('sm.hub', ['id' => $schedule->id]))

@push('head')
    <style>
        /* ===== AI Technician — "Field Advisor" =========================
           Everything paints with the theme vars, so html.dark's variable
           repoint restyles the page for free. Literal hex only where a
           gradient must stay identical in both modes (green sends/bubbles)
           or where a repointed var would break contrast. */

        /* Layout bones: sessions rail + chat column at full page width. */
        .ai-shell { display: grid; grid-template-columns: minmax(0, 1fr); gap: 1rem; }
        @media (min-width: 1024px) { .ai-shell { grid-template-columns: 17rem minmax(0, 1fr); } }
        .ai-sessions { display: none; }
        @media (min-width: 1024px) {
            /* The rail is the other half of the same row, so it is measured
               the same way — it was the taller of the two and the page's real
               height came from it, which is why sizing the chat alone changed
               nothing at all. */
            .ai-sessions { display: flex; flex-direction: column; gap: .3rem; height: calc(100dvh - 10.6rem - 7.1rem); min-height: min(26rem, 60dvh);
                overflow-y: auto; scrollbar-width: thin; scrollbar-color: var(--color-gray-300) transparent;
                border: 1px solid var(--color-gray-100); border-radius: 1rem; background: var(--color-white);
                padding: .6rem; box-shadow: var(--shadow-card); }
        }
        .ai-session-row { display: flex; align-items: center; gap: .25rem; border-radius: .7rem; padding: .4rem .55rem;
            font-size: .85rem; font-weight: 600; color: var(--color-gray-700); transition: background-color .15s ease; }
        .ai-session-row:hover { background: var(--color-gray-100); }
        .ai-session-row.is-active { background: var(--color-brand-50); color: var(--color-brand-800); }
        .ai-session-row .t { flex: 1; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .ai-session-row .meta { font-size: .65rem; color: var(--color-gray-400); font-weight: 600; display: block; }
        .ai-session-act { width: 1.7rem; height: 1.7rem; border-radius: .45rem; display: inline-flex; align-items: center;
            justify-content: center; color: var(--color-gray-400); flex-shrink: 0; opacity: 0; transition: opacity .15s ease; }
        .ai-session-row:hover .ai-session-act, .ai-session-row.is-active .ai-session-act { opacity: 1; }
        .ai-session-act:hover { background: var(--color-gray-200); color: var(--color-gray-700); }
        .ai-session-rename { width: 100%; font-size: .85rem; padding: .2rem .4rem; border-radius: .45rem;
            border: 1px solid var(--color-brand-400); background: var(--color-white); color: var(--color-gray-900); outline: none; }

        /* Two numbers, named: what the page itself takes (app bar, main's
           paddings, the gap the footer holds itself away by) and what the
           footer takes. It was one number that guessed — and the guess was a
           hundred pixels short of the footer, so on a desktop the page opened
           with a scrollbar before a word was said. The footer is shorter where
           its links sit on fewer rows, and gone on a phone. */
        .aichat { --ai-chrome: 10.6rem; --ai-foot: 7.1rem;
            display: flex; flex-direction: column;
            height: calc(100dvh - var(--ai-chrome) - var(--ai-foot));
            min-height: min(26rem, 60dvh); width: 100%; }
        @media (max-width: 1023px) { .aichat { --ai-foot: 5.6rem; } }
        /* Mobile: clear the fixed bottom tab bar so the composer + hint stay visible. */
        @media (max-width: 767px) { .aichat { --ai-chrome: 13.5rem; --ai-foot: 0rem; min-height: min(22rem, 55dvh); } }
        /* Inside the shell the page bobbed a little: the pane's height was a
           guess and the footer sat underneath. While the AI module is the one
           showing, the document locks, the footer steps aside, and the pane
           is sized to the chrome that is actually above it (app bar + main's
           top padding + the toolbar row). Tuned by measurement. */
        html.sm-ai-open body { overflow: hidden; }
        html.sm-ai-open footer { display: none; }
        html.sm-ai-open .aichat { --ai-chrome: 9.4rem; --ai-foot: 0rem; min-height: 0; }
        @media (min-width: 768px) { html.sm-ai-open .aichat { --ai-chrome: 10.4rem; } }
        /* Full-page mode only (the body class never reaches the shell's
           partial): the chat runs to the viewport's true bottom — measured,
           the composer sat 87px adrift. 8.1rem = app bar + main's top
           padding + this page's own crumb row. */
        @media (max-width: 767px) {
            /* The chip row above this page is gone, so the chat takes the
               pixels it used to cost: 5.4rem is the app bar and main's top
               padding, measured. */
            body.hide-tabbar .aichat { --ai-chrome: 5.4rem; --ai-foot: 0rem; margin-bottom: -1rem; }
            body.hide-tabbar .aichat-composer { padding-bottom: calc(.3rem + env(safe-area-inset-bottom)); }
            body.hide-tabbar footer { display: none; }
        }
        .aichat-thread { flex: 1 1 auto; overflow-y: auto; padding: .5rem .25rem 1.25rem; scroll-behavior: smooth; display: flex; flex-direction: column; scrollbar-width: thin; scrollbar-color: var(--color-gray-300) transparent; }
        .aichat-thread::-webkit-scrollbar { width: 6px; }
        .aichat-thread::-webkit-scrollbar-track { background: transparent; }
        .aichat-thread::-webkit-scrollbar-thumb { background: var(--color-gray-300); border-radius: 999px; }
        /* Welcome hero centers; a short conversation grows up from the composer. */
        #aiWelcome { margin: auto 0; }
        .aimsg:first-child, .aichat-day:first-child { margin-top: auto; }

        .aichat-day { display: flex; align-items: center; gap: .75rem; margin: .75rem 0; text-align: center; }
        .aichat-day::before, .aichat-day::after { content: ""; flex: 1; height: 1px; background: var(--color-gray-200); }
        .aichat-day span { font-size: .69rem; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; color: var(--color-gray-400); }

        /* ===== Masthead: the app's drifting header green (messenger language,
               gradSweep tide from the layout). Literal hex — the gradient must
               stay identical in both modes so the white text always holds. ===== */
        .ai-head { display: flex; align-items: center; justify-content: space-between; gap: .75rem; padding: .7rem .9rem; margin-bottom: .75rem; border-radius: 1.25rem; color: #fff;
            background: linear-gradient(120deg, #3d6823, #6b9f3d 35%, #4a7c2a 60%, #2f5219 85%, #3d6823);
            background-size: 240% 240%; animation: gradSweep 12s ease-in-out infinite alternate;
            box-shadow: 0 4px 16px -6px rgb(45 80 22 / .45); }
        .ai-avatar { position: relative; flex-shrink: 0; }
        .ai-avatar .aimsg-face { width: 2.75rem; height: 2.75rem; box-shadow: 0 0 0 2px rgb(255 255 255 / .9); background: rgb(255 255 255 / .16); color: #fff; }
        /* Online, and she always is. Brighter than the brand green on
           purpose: this sits on a dark green header, and the app's own green
           disappears into it. */
        .ai-avatar::after { content: ""; position: absolute; right: -1px; bottom: -1px; width: .75rem; height: .75rem; border-radius: 999px; background: #7ee06a; border: 2.5px solid #3d6823; }
        .ai-head-name { font-family: var(--font-heading); font-weight: 700; font-size: 1.02rem; line-height: 1.15; color: #fff; }
        .ai-role { display: inline-flex; align-items: center; margin-top: .2rem; padding: .1rem .55rem; border-radius: 999px; font-size: .68rem; font-weight: 800; letter-spacing: .05em; text-transform: uppercase; white-space: nowrap; color: #fff; background: rgb(255 255 255 / .18); }
        .ai-credits { display: inline-flex; align-items: center; gap: .35rem; min-height: 2.1rem; padding: .25rem .7rem; border-radius: 999px; background: rgb(255 255 255 / .18); color: #fff; font-weight: 800; font-size: .8rem; font-variant-numeric: tabular-nums; transition: background .15s ease; }
        .ai-credits:hover { background: rgb(255 255 255 / .28); }
        .ai-credits svg { color: var(--color-accent-400); }
        .ai-credits:focus-visible, .aisuggest:focus-visible, #aiSendBtn:focus-visible, .ai-cam:focus-visible, .ai-sq:focus-visible { outline: 2px solid var(--color-accent-400); outline-offset: 2px; }
        .ai-sq { width: 2.5rem; height: 2.5rem; min-height: 2.5rem; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: .8rem; background: rgb(255 255 255 / .18); color: #fff; transition: background .15s ease, transform .15s ease; }
        /* The white-on-green square, re-inked for a white page: the shell's
           pane has no green masthead left to sit on. */
        .aichat > div > .ai-sq { background: var(--color-gray-100); color: var(--color-gray-600); border: 1px solid var(--color-gray-200); }
        .aichat > div > .ai-sq:hover { background: var(--color-brand-50); color: var(--color-brand-700); }
        .ai-sq:hover { background: rgb(255 255 255 / .28); }
        .ai-sq:active { transform: scale(.94); }
        /* A linked chat marks its button: white pill, brand icon (JS toggles
           the utility class). */
        .ai-sq.text-brand-700 { background: #fff; }
        /* Narrow phones: let the controls wrap under the full-width name row. */
        @media (max-width: 479px) {
            .ai-head { flex-wrap: wrap; row-gap: .5rem; padding: .6rem .75rem; }
            .ai-head > div:last-child { margin-left: auto; }
        }

        /* ===== Welcome hero ===== */
        /* The size the floating technician's welcome is.

           This one was a poster: a 3.5rem face, a heading at 1.15rem that
           wrapped onto two lines, a paragraph, a row of capability chips and
           an overline — and a chat that opened with a scrollbar because the
           greeting alone was taller than the screen. The panel that hangs off
           the floating button says the same thing in a third of the height,
           and people like it, so this is that: a 3rem face, a line of type at
           the panel's size, one grey sentence, and the questions. */
        /* Her whole introduction, on two lines if it needs them.
           The app bar's title is a single truncating line everywhere else,
           which is right for "Workers" and wrong for a sentence — on a phone
           it cut to "Anee, Your Smart …", losing the half that says what she
           is for. Scoped to this page and clamped to two lines at a smaller
           size, so the bar keeps the height every sticky offset below it is
           measured against. */
        #appPageTitle {
            white-space: normal; overflow: hidden; text-overflow: clip;
            font-size: .8rem; line-height: 1.15;
            display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;
        }
        @media (min-width: 768px) { #appPageTitle { font-size: 1rem; -webkit-line-clamp: 1; } }

        .ai-hello { margin-block: auto; text-align: center; padding: 1.6rem .75rem .9rem; border-radius: 1.5rem; background: radial-gradient(120% 90% at 50% 0%, var(--color-brand-50) 0%, transparent 70%); }
        .ai-hello .aimsg-face { width: 3rem; height: 3rem; background: linear-gradient(150deg, #6b9f3d, #3d6823); color: #fff; box-shadow: 0 0 0 3px var(--color-white), 0 0 0 5px var(--color-brand-200), 0 10px 24px -8px rgb(74 124 42 / .45); animation: aiFloatIdle 5s ease-in-out infinite; }
        .ai-hello h2 { font-size: .95rem; font-weight: 700; margin-top: .5rem; color: var(--color-gray-800); }
        .ai-hello .sub { font-size: .8rem; color: var(--color-gray-500); margin-top: .15rem; max-width: 22rem; margin-inline: auto; line-height: 1.45; }
        /* How to ask.
           Left-aligned inside a centred panel on purpose: it is the one
           thing here meant to be READ rather than glanced at, and centred
           prose of this length is read by nobody. */
        .ai-howto { max-width: 26rem; margin: .9rem auto 0; padding: .75rem .9rem;
            text-align: left; border-radius: .9rem;
            border: 1px solid var(--color-brand-200, #d7e8c4); background: var(--color-brand-50, #f2f8ec); }
        .ai-howto-h { display: flex; gap: .4rem; align-items: flex-start;
            font-size: .78rem; font-weight: 800; line-height: 1.35;
            color: var(--color-brand-800, #2f5219); }
        .ai-howto-h svg { width: .95rem; height: .95rem; flex: none; margin-top: .08rem; }
        .ai-howto-b { font-size: .74rem; line-height: 1.55; color: var(--color-gray-600); margin-top: .4rem; }
        .ai-howto-lbl { font-size: .64rem; font-weight: 800; letter-spacing: .06em;
            text-transform: uppercase; color: var(--color-brand-700, #3d6823);
            margin-top: .5rem; padding-top: .45rem;
            border-top: 1px dashed var(--color-brand-200, #d7e8c4); }
        .ai-howto-eg { font-size: .72rem; line-height: 1.5; color: var(--color-gray-500);
            margin-top: .25rem; }
        .ai-howto-eg b { color: var(--color-brand-800, #2f5219); font-weight: 800; }
        html.dark .ai-howto { background: rgb(107 159 61 / .12); border-color: #2b3a1c; }
        html.dark .ai-howto-h, html.dark .ai-howto-eg b { color: #a5c97e; }
        html.dark .ai-howto-b, html.dark .ai-howto-eg { color: #b7c2ad; }

        /* What she can see. Two switches, off-looking until they are on,
           sitting where the question is typed rather than behind a menu —
           the point of them is that you notice them. */
        .ai-sees { display: flex; gap: .35rem; flex-wrap: wrap; padding: 0 .1rem .35rem; }
        .ai-see { display: inline-flex; align-items: center; gap: .3rem; cursor: pointer;
            padding: .22rem .55rem; border-radius: 999px; font-size: .68rem; font-weight: 700;
            border: 1px solid var(--color-gray-200); background: var(--color-white);
            color: var(--color-gray-500);
            transition: background .28s cubic-bezier(.22,1,.36,1), color .28s cubic-bezier(.22,1,.36,1),
                border-color .28s cubic-bezier(.22,1,.36,1); }
        .ai-see svg { width: .8rem; height: .8rem; flex: none; }
        .ai-see:hover { border-color: #a8cc7e; }
        .ai-see-cost { font-size: .62rem; font-weight: 800; padding: 0 .3rem;
            border-radius: 999px; background: rgb(245 197 24 / .22); color: #8a6100; }
        html.dark .ai-see-cost { background: rgb(245 197 24 / .18); color: var(--color-accent-400, #f5c518); }
        .ai-see.is-on { border-color: var(--color-brand-500, #4a7c2a);
            background: var(--color-brand-50, #f2f8ec); color: var(--color-brand-800, #2f5219); }
        html.dark .ai-see { background: #151b12; border-color: #2b3a1c; color: #9aa694; }
        html.dark .ai-see.is-on { background: rgb(107 159 61 / .18); border-color: #6b9f3d; color: #a5c97e; }
        @media (prefers-reduced-motion: reduce) { .ai-see { transition: none; } }
        @keyframes aiFloatIdle { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-5px); } }

        /* ===== Suggestion cards ===== */
        .aisuggest { display: flex; align-items: center; gap: .55rem; width: 100%; min-height: 2.4rem; padding: .5rem .7rem; text-align: left; border: 1px solid var(--color-gray-200); border-radius: .9rem; background: var(--color-white); box-shadow: var(--shadow-card); font-size: .85rem; font-weight: 700; color: var(--color-gray-800); cursor: pointer; transition: transform .18s cubic-bezier(.22,1,.36,1), border-color .18s ease, box-shadow .18s ease; animation: aiRise .3s ease both; }
        .aisuggest:nth-child(2) { animation-delay: .06s; }
        .aisuggest:nth-child(3) { animation-delay: .12s; }
        .aisuggest:hover { transform: translateY(-1px); border-color: var(--color-brand-300); box-shadow: var(--shadow-card-lg); }
        .aisuggest:active { transform: scale(.98); }
        .aisuggest .ic { width: 1.85rem; height: 1.85rem; border-radius: .6rem; flex-shrink: 0; display: flex; align-items: center; justify-content: center; background: var(--color-brand-50); color: var(--color-brand-700); }
        .aisuggest .t { flex: 1 1 auto; min-width: 0; }
        .aisuggest .go { margin-left: auto; flex-shrink: 0; color: var(--color-gray-400); transition: transform .18s ease, color .18s ease; }
        .aisuggest:hover .go { transform: translateX(3px); color: var(--color-brand-600); }

        /* ===== Turns. Only NEW messages animate in — server-rendered history
               arrives settled, it does not cascade on load. ===== */
        .aimsg { display: flex; gap: .65rem; margin-bottom: 1rem; align-items: flex-end; }
        .aimsg.is-new { animation: aiRise .28s cubic-bezier(.22,1,.36,1) both; }
        .aimsg.me { flex-direction: row-reverse; }
        @keyframes aiRise { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: none; } }
        .aimsg-face {
            width: 2.4rem; height: 2.4rem; border-radius: 999px; flex-shrink: 0; overflow: hidden;
            display: flex; align-items: center; justify-content: center; margin-bottom: .1rem;
            background: var(--color-brand-50); color: var(--color-brand-700); font-size: .78rem; font-weight: 800;
        }
        .aimsg-face img { width: 100%; height: 100%; object-fit: cover; }
        .aimsg.me .aimsg-face { background: var(--color-brand-600); color: #fff; }
        /* The in-thread assistant face carries the masthead ring — same character. */
        .aimsg:not(.me) .aimsg-face { box-shadow: 0 0 0 2px var(--color-white), 0 0 0 3px var(--color-brand-200); }

        /* Assistant "writes" on a sheet; the user "sends" a green slip. */
        .aibubble { max-width: min(82%, 34rem); padding: .6rem .85rem; font-size: .92rem; line-height: 1.55; background: var(--color-white); border: 1px solid var(--color-gray-100); border-radius: 1.15rem 1.15rem 1.15rem .35rem; box-shadow: 0 1px 2px rgb(26 26 26 / .06), 0 3px 10px -4px rgb(26 26 26 / .08); }
        /* Literal hex: var(--color-brand-700) repoints to a bright green in dark
           mode and would sink the white text. */
        .aimsg.me .aibubble { background: linear-gradient(135deg, #4a7c2a, #3d6823); border-color: transparent; color: #fff; border-radius: 1.15rem 1.15rem .35rem 1.15rem; box-shadow: 0 3px 12px -4px rgb(45 80 22 / .45); }
        .aibubble p { margin: .4rem 0; } .aibubble p:first-child { margin-top: 0; } .aibubble p:last-child { margin-bottom: 0; }
        .aibubble ul { list-style: disc; padding-left: 1.25rem; margin: .4rem 0; }
        .aibubble ol { list-style: decimal; padding-left: 1.4rem; margin: .4rem 0; }
        .aibubble li { margin: .2rem 0; }
        .aibubble strong { font-weight: 700; }
        .aibubble img { max-width: 100%; max-height: 260px; border-radius: .6rem; margin-top: .4rem; }
        /* If an answer ever carries a table, it scrolls inside the bubble —
           never the page. */
        .aibubble table { display: block; max-width: 100%; overflow-x: auto; border-collapse: collapse; font-size: .9em; margin: .4rem 0; }
        .aibubble th, .aibubble td { border: 1px solid var(--color-gray-200); padding: .3rem .55rem; text-align: left; }
        /* A whispered clock, not a shout. */
        .ai-when { display: block; font-size: .66rem; font-weight: 600; opacity: .55; margin-top: .3rem; text-align: right; font-variant-numeric: tabular-nums; }
        .aibubble-cost { display: inline-flex; align-items: center; gap: .3rem; margin-top: .55rem; padding: .12rem .55rem; border-radius: 999px; font-size: .69rem; font-weight: 800; font-variant-numeric: tabular-nums; color: #8a6100; background: rgb(245 197 24 / .15); }
        .aibubble-cost::before { content: ""; width: .4rem; height: .4rem; border-radius: 999px; background: var(--color-accent-500); }
        .aimsg.me .aibubble-cost { background: rgb(255 255 255 / .2); color: #fff; }
        .aimsg.me .aibubble-cost::before { background: #fff; }

        /* ===== Typing dots ===== */
        .aidots { display: inline-flex; gap: .25rem; align-items: center; height: 1.2rem; }
        .aidots i { width: .42rem; height: .42rem; border-radius: 999px; background: var(--color-brand-500); opacity: .35; animation: aidot .9s cubic-bezier(.4,0,.2,1) infinite; }
        .aidots i:nth-child(2) { animation-delay: .15s; } .aidots i:nth-child(3) { animation-delay: .3s; }
        @keyframes aidot { 0%, 60%, 100% { opacity: .3; transform: translateY(0); } 30% { opacity: 1; transform: translateY(-3px); } }
        @keyframes ai-spin { to { transform: rotate(360deg); } }

        /* ===== Composer dock ===== */
        .aichat-composer { flex-shrink: 0; padding: .6rem 0 .1rem; background: linear-gradient(to top, var(--color-gray-50) 70%, transparent); }
        .aichat-box { display: flex; align-items: flex-end; gap: .4rem; padding: .45rem; border-radius: 1.35rem; background: var(--color-white); border: 1.5px solid var(--color-gray-200); box-shadow: var(--shadow-card-lg); transition: border-color .15s ease, box-shadow .15s ease; }
        .aichat-box:focus-within { border-color: var(--color-brand-500); box-shadow: 0 0 0 3px rgb(107 159 61 / .18), var(--shadow-card-lg); }
        .ai-cam { width: 2.75rem; height: 2.75rem; border-radius: 1rem; display: flex; align-items: center; justify-content: center; flex-shrink: 0; background: var(--color-brand-50); color: var(--color-brand-700); cursor: pointer; transition: background .15s ease; }
        .ai-cam:hover { background: var(--color-brand-100); }
        /* No scrollbar while the box is still growing — the autosize handler
           flips this to auto only once the text passes the max height. */
        #aiText { resize: none; max-height: 8rem; overflow-y: hidden; font-size: 1rem; }
        #aiSendBtn { width: 2.9rem; height: 2.9rem; border-radius: 999px; background: linear-gradient(140deg, #6b9f3d, #3d6823); box-shadow: 0 4px 12px -3px rgb(45 80 22 / .5); transition: transform .15s ease; }
        #aiSendBtn:hover:not(:disabled) { transform: scale(1.06); }
        #aiSendBtn:active:not(:disabled) { transform: scale(.94); }
        .ai-hint { text-align: center; font-size: .72rem; font-weight: 600; color: var(--color-gray-500); margin-top: .4rem; }

        /* ===== Attached photo chips: one thumbnail per photo, each with its
               own remove. A chip mid-upload wears a spinner instead of ✕. ===== */
        #aiPhotoChips { display: flex; flex-wrap: wrap; gap: .45rem; margin-bottom: .45rem; }
        #aiPhotoChips:empty { display: none; }
        /* Says so while a photo is on the wire - a busy chip alone was easy
           to read as a broken thumbnail rather than work in progress. */
        .ai-busyline { display: flex; align-items: center; gap: .4rem; margin-bottom: .45rem;
            font-size: .72rem; font-weight: 700; color: var(--color-brand-700); }
        .ai-busyline.hidden { display: none; }
        .ai-busyline .sp { width: .8rem; height: .8rem; border-radius: 999px; flex-shrink: 0;
            border: 2px solid var(--color-brand-200); border-top-color: var(--color-brand-600);
            animation: aiBusySpin .8s linear infinite; }
        @keyframes aiBusySpin { to { transform: rotate(360deg); } }
        /* The chip shimmers under its picture while the copy is in flight -
           gallery images can take a moment to even paint. */
        .ai-chip.is-busy { background: linear-gradient(100deg, var(--color-gray-100) 40%, var(--color-gray-200) 50%, var(--color-gray-100) 60%);
            background-size: 200% 100%; animation: aiChipShimmer 1.2s linear infinite; }
        @keyframes aiChipShimmer { to { background-position: -200% 0; } }
        .ai-chip { position: relative; width: 3.4rem; height: 3.4rem; border-radius: .75rem; overflow: hidden;
            box-shadow: 0 0 0 2px var(--color-brand-200); background: var(--color-gray-100);
            animation: aiChipIn .28s cubic-bezier(.22,1,.36,1) both; }
        .ai-chip img { width: 100%; height: 100%; object-fit: cover; display: block; }
        .ai-chip .x { position: absolute; top: .15rem; right: .15rem; width: 1.2rem; height: 1.2rem;
            border-radius: 999px; display: flex; align-items: center; justify-content: center;
            background: rgb(17 24 39 / .72); color: #fff; font-size: .62rem; font-weight: 800; line-height: 1;
            transition: transform .15s ease, background-color .15s ease; }
        .ai-chip .x:hover { background: #b91c1c; transform: scale(1.1); }
        .ai-chip .st { position: absolute; inset: 0; display: none; align-items: center; justify-content: center;
            background: rgb(255 255 255 / .55); color: var(--color-brand-700); }
        .ai-chip.is-busy .st { display: flex; }
        .ai-chip.is-busy .x { display: none; }
        html.dark .ai-chip .st { background: rgb(0 0 0 / .45); color: #fff; }
        @keyframes aiChipIn { from { opacity: 0; transform: scale(.8); } to { opacity: 1; transform: none; } }

        /* ===== Photos inside a bubble: one keeps its natural shape, two or
               more settle into a tidy square grid. ===== */
        /* A photo that used to be here. */
        .ai-shot-gone { display: inline-flex; align-items: center; gap: .4rem;
            padding: .5rem .7rem; border-radius: .6rem; margin-top: .4rem;
            border: 1px dashed var(--color-gray-300); background: var(--color-gray-50);
            font-size: .74rem; font-weight: 700; color: var(--color-gray-400); }
        .ai-shot-gone svg { width: .95rem; height: .95rem; }
        html.dark .ai-shot-gone { border-color: #2b3a1c; background: #151b12; color: #8ea37a; }
        .ai-shots { display: grid; gap: .35rem; margin-top: .4rem; }
        .ai-shots img { margin-top: 0; }
        .ai-shots.is-multi { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .ai-shots.is-multi img { width: 100%; aspect-ratio: 1; object-fit: cover; max-height: none; }

        /* ===== The attach chooser's doors (house sheet rows) ===== */
        .ai-attach-opt { display: flex; align-items: center; gap: .75rem; width: 100%; padding: .7rem .8rem;
            border-radius: .9rem; text-align: left; font-size: .95rem; font-weight: 700; color: var(--color-gray-800);
            transition: background-color .15s ease; }
        .ai-attach-opt:hover { background: var(--color-gray-100); }
        .ai-attach-opt .ic { width: 2.4rem; height: 2.4rem; border-radius: .8rem; flex-shrink: 0; display: flex;
            align-items: center; justify-content: center; background: var(--color-brand-50); color: var(--color-brand-700); }
        .ai-attach-opt .sub { display: block; font-size: .72rem; font-weight: 600; color: var(--color-gray-400); }

        /* Out-of-credits purchase card, rendered as an assistant turn. */
        .aibubble.is-buy { border-color: rgb(245 197 24 / .4); background: linear-gradient(115deg, rgb(245 197 24 / .14), rgb(245 197 24 / .04)), var(--color-white); }
        .ai-buyc { display: flex; gap: .8rem; align-items: flex-start; }
        .ai-buyc .ico { width: 2.4rem; height: 2.4rem; border-radius: .85rem; flex-shrink: 0; display: flex; align-items: center; justify-content: center; background: var(--color-accent-500); color: #1a1a1a; }
        .ai-buyc h3 { font-family: var(--font-heading); font-size: 1rem; font-weight: 700; color: var(--color-gray-900); }
        .ai-buyc p { font-size: .88rem; color: var(--color-gray-600); }

        /* ===== Notices: warm field notes (accent = money/attention) ===== */
        .ai-note { display: flex; gap: .8rem; align-items: flex-start; margin-bottom: .75rem; padding: 1rem 1.1rem; border-radius: 1.25rem; border: 1px solid rgb(245 197 24 / .4); background: linear-gradient(115deg, rgb(245 197 24 / .14), rgb(245 197 24 / .04)), var(--color-white); }
        .ai-note.hidden { display: none; }
        /* Literal ink: var(--color-ink) flips near-white in dark mode. */
        .ai-note .ico { width: 2.4rem; height: 2.4rem; border-radius: .85rem; flex-shrink: 0; display: flex; align-items: center; justify-content: center; background: var(--color-accent-500); color: #1a1a1a; }
        .ai-note h3 { font-family: var(--font-heading); font-size: 1rem; font-weight: 700; color: var(--color-gray-900); }
        .ai-note p { font-size: .85rem; color: var(--color-gray-600); margin-top: .15rem; }

        /* ===== Dark mode: the var repoint does 90%; a few nudges finish it ===== */
        html.dark .aibubble { box-shadow: 0 1px 2px rgb(0 0 0 / .35), 0 3px 10px -4px rgb(0 0 0 / .4); }
        html.dark .aibubble-cost { color: var(--color-accent-400); }
        html.dark .aimsg.me .aibubble-cost { color: #fff; }
        html.dark .aichat-box:focus-within { box-shadow: 0 0 0 3px rgb(124 184 79 / .22), var(--shadow-card-lg); }
        html.dark .ai-note { border-color: rgb(245 197 24 / .25); background: linear-gradient(115deg, rgb(245 197 24 / .10), rgb(245 197 24 / .03)), var(--color-white); }
        html.dark .aibubble.is-buy { border-color: rgb(245 197 24 / .25); background: linear-gradient(115deg, rgb(245 197 24 / .10), rgb(245 197 24 / .03)), var(--color-white); }
        html.dark .ai-hello .aimsg-face { box-shadow: 0 0 0 3px var(--color-white), 0 0 0 5px var(--color-brand-200), 0 10px 24px -8px rgb(0 0 0 / .6); }

        @media (prefers-reduced-motion: reduce) {
            .ai-head, .aimsg.is-new, .aisuggest, .ai-hello .aimsg-face, .ai-chip { animation: none; }
            .aisuggest, .aisuggest .go, .aichat-box, #aiSendBtn, .ai-credits, .ai-sq, .ai-attach-opt, .ai-chip .x { transition: none; }
            /* Slowed, not stopped — the pulse is the message that work is happening. */
            .aidots i { animation-duration: 1.8s; }
            .ai-busyline .sp { animation-duration: 1.6s; }
            .ai-chip.is-busy { animation: none; }
            [style*="ai-spin"] { animation-duration: 1.6s !important; }
        }
    </style>
@endpush

@section('content')
{{-- Anee's own faces, for the shortcodes she writes. --}}
@include('partials.anee-emoji')
@php
    // Super admins ride free — the wallet row hides for them (view-side check,
    // same pattern the floating assistant already uses).
    $aiUnlimited = app(\App\Services\AiCreditService::class)->unlimited((int) auth()->id());
    // The menu's "attach to a task" picker, rendered with the page.
    $aiPageTasks = \App\Models\AsScheduleActivity::query()
        ->where('croppingScheduleId', $schedule->id)
        ->orderByDesc('targetDate')
        ->limit(30)
        ->get(['id', 'activityTitle', 'targetDate']);
    // The real per-photo price, so the hint stays honest when several photos
    // ride on one question.
    $aiPerPhoto = (float) ($settings->creditsPerImage ?? 0);
    $aiPerPhotoTxt = rtrim(rtrim(number_format($aiPerPhoto, 2), '0'), '.');
@endphp
{{-- No module chips over the chat.

     Every other module page wears the row of chips, and inside the Activities
     shell it is hidden already (#moduleHost .module-chip-nav) because the
     shell has its own toolbar. This page is a chat wherever it is opened: the
     row cost it a third of the first screen and the thread opened scrolled.
     The way back to the other modules is the arrow in the masthead, which
     goes to the hub. --}}

<div class="ai-shell">
    {{-- Chat sessions rail (desktop; phones use the history sheet) --}}
    <aside class="ai-sessions" id="aiSessions">
        <div class="flex items-center justify-between px-1 pb-1">
            <span class="text-xs font-bold text-gray-400 uppercase tracking-wide">Chats</span>
            <button type="button" id="aiSideNewBtn" class="icon-btn" title="New chat">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            </button>
        </div>
        @forelse ($conversations as $c)
            @include('sm.partials.ai-session-row', [
                'id' => $c->id,
                'href' => route('sm.ai', ['id' => $schedule->id, 'c' => $c->id]),
                'title' => $c->title,
                'when' => $c->updated_at?->diffForHumans(),
                'link' => $c->link_label,
                'active' => $conversation && $conversation->id === $c->id,
            ])
        @empty
            <p class="text-xs text-gray-400 px-1 py-2" data-sessions-empty>No chats yet — ask your first question and it names itself.</p>
        @endforelse
        {{-- The row the page clones when an answer starts a new chat. Same
             partial as the rows above, so the two can never drift. --}}
        <template id="aiSessionRowTpl">@include('sm.partials.ai-session-row', [
            'id' => '__ID__',
            'href' => route('sm.ai', ['id' => $schedule->id]) . '&c=__ID__',
            'title' => '',
            'when' => 'just now',
            'link' => null,
            'active' => false,
        ])</template>
    </aside>

<div class="aichat">
    {{-- The green masthead is gone on the owner's ask: its jobs live in the
         aiMenuSheet behind one square button — beside the bell in full-page
         mode, a slim row of its own inside the shell where no app bar exists. --}}
    @if (request()->boolean('partial'))
        {{-- Born hidden; the script seats it on the shell's toolbar — the
             same line as the hamburger, per the owner — and the module-shown
             event keeps it there only while this module holds the stage. --}}
        <button type="button" class="btn btn-white btn-sm hidden" id="aiMenuBtn" title="AI options" aria-label="AI options" aria-haspopup="dialog" style="margin-left:auto">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2m0 0a7 7 0 017 7v3a3 3 0 01-3 3H8a3 3 0 01-3-3v-3a7 7 0 017-7zM9 12h.01M15 12h.01M9.5 17h5"/></svg>
        </button>
    @endif

    {{-- Chip: the day/activity this chat is pinned to (kept in the AI's focus). --}}
    <div id="aiLinkChip" class="{{ $conversation && $conversation->link_label ? 'flex' : 'hidden' }} items-center gap-2 -mt-1 mb-2 text-sm">
        <span class="inline-flex items-center gap-1.5 rounded-full bg-brand-50 border border-brand-100 text-brand-800 font-semibold px-3 py-1">
            <span id="aiLinkChipText">{{ $conversation?->link_label }}</span>
            <button type="button" id="aiLinkChipClear" class="text-brand-500 hover:text-red-600 font-bold" aria-label="Remove link">✕</button>
        </span>
        <span class="text-xs text-gray-400">This chat is focused here.</span>
    </div>

    @unless ($settings->isUsable())
        <div class="ai-note">
            <span class="ico">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M18.36 6.64a9 9 0 11-12.73 0M12 2v10"/></svg>
            </span>
            <div>
                <h3>{{ $settings->assistantName }} is not switched on yet</h3>
                <p>It will appear here as soon as it is configured.</p>
            </div>
        </div>
    @endunless

    <div class="ai-note {{ ($balance > 0 || $aiUnlimited) ? 'hidden' : '' }}" id="aiNoCredits">
        <span class="ico">
            <svg class="w-5 h-5" viewBox="0 0 20 20" fill="currentColor"><path d="M10 2a8 8 0 100 16 8 8 0 000-16zm.75 4.5v.63a2.5 2.5 0 01.2 4.84v.78a.75.75 0 01-1.5 0v-.75a2.6 2.6 0 01-1.83-1.1.75.75 0 011.24-.84c.24.35.63.57 1.09.57.6 0 1.05-.36 1.05-.83 0-.44-.3-.7-1.2-.95-1.13-.32-2.05-.8-2.05-2.05a2.2 2.2 0 011.5-2.03V6.5a.75.75 0 011.5 0z"/></svg>
        </span>
        <div>
            <h3>You have no AI Credits left</h3>
            <p>A question costs about 4 credits{{ $aiPerPhoto > 0 ? ', plus ' . $aiPerPhotoTxt . ' for each photo' : '' }}.</p>
            <a href="{{ route('ai.credits') }}" class="btn btn-accent btn-sm mt-2">Get AI Credits</a>
        </div>
    </div>

    {{-- Thread. History renders settled (no entrance cascade); day separators
         and whispered timestamps keep a long thread readable. --}}
    <div class="aichat-thread" id="aiThread">
        @php $aiPrevDay = null; @endphp
        @forelse ($messages as $m)
            @php
                $aiDay = $m->created_at?->isToday() ? 'Today' : ($m->created_at?->isYesterday() ? 'Yesterday' : $m->created_at?->format('M j, Y'));
            @endphp
            @if ($aiDay && $aiDay !== $aiPrevDay)
                <div class="aichat-day"><span>{{ $aiDay }}</span></div>
                @php $aiPrevDay = $aiDay; @endphp
            @endif
            <div class="aimsg {{ $m->role === 'user' ? 'me' : '' }}">
                <span class="aimsg-face">
                    @if ($m->role === 'user')
                        {!! \App\Support\ChatFace::mine() !!}
                    @else
                        <img data-ai-face src="{{ $settings->faceUrl() }}" alt="">
                    @endif
                </span>
                <div class="aibubble">
                    {!! \App\Support\AiMarkdown::toHtml($m->content) !!}
                    @php
                        // Every photo on the turn — the new column when it is
                        // there, the legacy single path for older rows.
                        $mShots = array_values(array_filter((array) ($m->imagePaths ?: ($m->imagePath ? [$m->imagePath] : []))));
                    @endphp
                    @if ($mShots)
                        <div class="ai-shots {{ count($mShots) > 1 ? 'is-multi' : '' }}">
                            @foreach ($mShots as $mShot)
                                @if (! empty($aiGone[$mShot]))
                                    {{-- Taken out of the Gallery, which is the
                                         same file. The turn keeps its place and
                                         says so. --}}
                                    <span class="ai-shot-gone">
                                        <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 3l18 18M8.5 8.5A2 2 0 0011 11m10 6V7a2 2 0 00-2-2H9m-4 0a2 2 0 00-2 2v10a2 2 0 002 2h10"/></svg>
                                        Photo deleted
                                    </span>
                                @else
                                    <img src="{{ \App\Support\MediaStore::url($mShot) }}" alt="Attached photo">
                                @endif
                            @endforeach
                        </div>
                    @endif
                    @if ($m->role === 'assistant' && (float) $m->creditsCharged > 0 && ! $aiUnlimited)
                        <p class="aibubble-cost">{{ rtrim(rtrim(number_format((float) $m->creditsCharged, 2), '0'), '.') }} credits</p>
                    @endif
                    @if ($m->created_at)
                        <time class="ai-when" datetime="{{ $m->created_at->toIso8601String() }}">{{ $m->created_at->format('g:i A') }}</time>
                    @endif
                </div>
            </div>
        @empty
            <div class="ai-hello" id="aiWelcome">
                <span class="aimsg-face mx-auto">
                        <img data-ai-face src="{{ $settings->faceUrl() }}" alt="">
                </span>
                {{-- She says who she is, then how to get a good answer out
                     of her. The second part is not decoration: a vague
                     question costs the same as a good one and comes back
                     needing three more, so telling a farmer this before they
                     type is worth more than any feature on the page. --}}
                <h2>Hi, I'm {{ $settings->assistantName }}</h2>
                @include('partials.anee-hello-video')

                {{-- Four short lines. It was three paragraphs, and an
                     instruction you have to scroll to finish is an
                     instruction nobody reads. The example does most of the
                     teaching, so it is what the space goes to. --}}
                <div class="ai-howto">
                    <p class="ai-howto-h">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
                        The more you tell me, the better I answer
                    </p>
                    <p class="ai-howto-b">Crop and age, what you did, what you see.</p>
                    {{-- Labelled, because "Not / Try" on its own reads as a
                         rule until you have understood it is a worked pair. --}}
                    <p class="ai-howto-lbl">For example</p>
                    <p class="ai-howto-eg"><b>Not</b> "my rice is sick"<br>
                        <b>Try</b> "RC222 ang tanim ko, medyo naninilaw yung mga gilid na dahon at ang paninilaw ay nasa bandang gilid ng dahon. Kaka lagay ko lamang ng urea 10 days ago. Sobrang maulan kasi. Anong problema?"</p>
                </div>
            </div>
        @endforelse
    </div>

    {{-- Composer --}}
    <div class="aichat-composer">
        {{-- One chip per attached photo, each with its own remove. --}}
        <div id="aiPhotoChips" aria-label="Attached photos" aria-live="polite"></div>
        <div id="aiAttachBusy" class="ai-busyline hidden" role="status"><span class="sp" aria-hidden="true"></span><span class="tx">Attaching photo…</span></div>
        {{-- What the question is allowed to carry.
             Both off by default, and both visible: these are the two things
             that quietly move an answer, and a farmer who cannot see them
             cannot tell why the same question answered differently twice. --}}
        <div class="ai-sees" role="group" aria-label="What {{ $settings->assistantName }} can see">
            @php
                // What the season would add to a question, priced the same
                // way the composer prices everything else.
                $planCredits = $aiUnlimited ?? false ? 0 : round(($planTokens ?? 0) / 1000 * (float) $settings->creditsPerInputK, 2);
            @endphp
            <button type="button" class="ai-see" id="aiUsePlan" aria-pressed="false"
                    data-plan-tokens="{{ (int) ($planTokens ?? 0) }}"
                    title="Sends this season — its crop, its lots and its activities — in front of the question">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 20l-5.4 1.8 1.8-5.4M9 20l11-11a2.83 2.83 0 10-4-4L5 16l4 4z"/></svg>
                This season's plan
                {{-- The price is on the switch, not in a footnote. Turning
                     this on can cost more than the question does. --}}
                @if ($planCredits > 0)
                    <span class="ai-see-cost">+{{ rtrim(rtrim(number_format($planCredits, 2), '0'), '.') }}</span>
                @endif
            </button>
            <button type="button" class="ai-see is-on" id="aiUseMemory" aria-pressed="true"
                    title="Let her read the earlier messages in this chat">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg>
                This chat so far
            </button>
        </div>
        <div class="aichat-box">
            <button type="button" class="ai-cam shrink-0" id="aiAttachBtn" title="Add photos" aria-label="Add photos" aria-haspopup="dialog">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            </button>
            <input type="file" id="aiPhotoFiles" accept="image/*" multiple class="hidden">
            <input type="file" id="aiPhotoCam" accept="image/*" capture="environment" class="hidden">
            <textarea id="aiText" rows="1" class="form-textarea border-0! shadow-none! focus:ring-0! p-2 grow bg-transparent!"
                      maxlength="4000" placeholder="Ask about your crop…" {{ $settings->isUsable() ? '' : 'disabled' }}></textarea>
            <button type="button" class="rounded-full text-white flex items-center justify-center shrink-0 disabled:opacity-40" id="aiSendBtn" {{ $settings->isUsable() ? '' : 'disabled' }} aria-label="Send">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14m0 0l-6-6m6 6l-6 6"/></svg>
            </button>
        </div>
        @php $aiHintIdle = '≈ 4 credits per answer' . ($aiPerPhoto > 0 ? ' · +' . $aiPerPhotoTxt . ' per photo' : ''); @endphp
        <p class="ai-hint" id="aiHint" data-idle="{{ $aiHintIdle }}">{{ $aiHintIdle }}</p>
                {{-- What is left, under the button that spends it. An account that
                     rides free shows the sign for it rather than a number that
                     never moves. --}}
                <span class="ai-bal" data-ai-bal>@if ($aiUnlimited)<b title="Unlimited">&#8734;</b>@else<b>{{ rtrim(rtrim(number_format($balance, 2), '0'), '.') }}</b> left @endif</span>
    </div>
</div>
</div>
@endsection

@push('sheets')
@php
    use Illuminate\Support\Carbon as AiCarbon;
    $aiDays = $schedule->activities
        ->filter(fn ($a) => $a->targetDate)
        ->map(fn ($a) => AiCarbon::parse($a->targetDate)->format('Y-m-d'))
        ->unique()->sort()->values();
    $aiActs = $schedule->activities
        ->filter(fn ($a) => $a->targetDate)
        ->sortBy(fn ($a) => AiCarbon::parse($a->targetDate)->format('Y-m-d') . str_pad((string) (int) $a->sequenceOrder, 6, '0', STR_PAD_LEFT))
        ->map(fn ($a) => [
            'id' => $a->id,
            'title' => $a->activityTitle,
            'date' => AiCarbon::parse($a->targetDate)->format('M j, Y'),
        ])->values();
@endphp
{{-- The attach chooser: every way a photo can arrive, behind one button
     (the messenger's + chooser, spoken in the house sheet language). This
     page carries the season picker, so the gallery door is real here. --}}
<div class="sheet hidden" id="aiAttachSheet" style="--sheet-width:22rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title">Add photos</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body space-y-1">
        <button type="button" class="ai-attach-opt" id="aiAttachUpload">
            <span class="ic"><svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg></span>
            <span>Upload photos<span class="sub">Pick one or several from your device</span></span>
        </button>
        <button type="button" class="ai-attach-opt" id="aiAttachCamera">
            <span class="ic"><svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg></span>
            <span>Take a photo<span class="sub">Point the camera at the problem</span></span>
        </button>
        <button type="button" class="ai-attach-opt hidden" id="aiAttachGallery">
            <span class="ic"><svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 7h3l2-3h6l2 3h3v13H4V7z"/><path stroke-linecap="round" stroke-linejoin="round" d="M8 13l2.5-2.5L14 14l2-2 2 2"/></svg></span>
            <span>From the gallery<span class="sub">A photo this season already keeps</span></span>
        </button>
    </div>
</div>
@include('sm.partials.media-picker')
<div class="sheet hidden" id="aiLinkSheet" style="--sheet-width:26rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title">Link this chat</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body space-y-4">
        <p class="text-sm text-gray-600">Pin this conversation to a day or a specific activity of <strong>{{ $schedule->title }}</strong>. The AI keeps that in focus when it answers.</p>
        <div>
            <label class="form-label" for="aiLinkDate">A day</label>
            <div class="flex gap-2">
                <select id="aiLinkDate" class="form-select grow">
                    <option value="">Choose a day…</option>
                    @foreach ($aiDays as $d)
                        <option value="{{ $d }}">{{ AiCarbon::parse($d)->format('D, M j, Y') }}</option>
                    @endforeach
                </select>
                <button type="button" id="aiLinkDayBtn" class="btn btn-primary shrink-0">Pin day</button>
            </div>
        </div>
        <div>
            <label class="form-label" for="aiLinkActivity">An activity</label>
            <div class="flex gap-2">
                <select id="aiLinkActivity" class="form-select grow">
                    <option value="">Choose an activity…</option>
                    @foreach ($aiActs as $a)
                        <option value="{{ $a['id'] }}">{{ $a['date'] }} — {{ \Illuminate\Support\Str::limit($a['title'], 44) }}</option>
                    @endforeach
                </select>
                <button type="button" id="aiLinkActBtn" class="btn btn-primary shrink-0">Pin activity</button>
            </div>
        </div>
        @if ($aiDays->isEmpty())
            <p class="text-sm text-gray-400">This plan has no dated activities yet.</p>
        @endif
    </div>
    <div class="sheet-footer">
        <button type="button" id="aiLinkRemoveBtn" class="btn btn-danger-outline mr-auto">Remove link</button>
        <button type="button" class="btn btn-ghost" data-sheet-close>Close</button>
    </div>
</div>
<div class="sheet hidden" id="aiHistorySheet" style="--sheet-width:26rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title">Past questions</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body space-y-1" id="aiHistorySheetBody">
        <button type="button" class="w-full flex items-center gap-3 rounded-xl px-3 py-3 text-left font-semibold text-brand-700 hover:bg-gray-50" id="aiNewFromSheet">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            Start a new question
        </button>
        @foreach ($conversations as $c)
            @include('sm.partials.ai-session-sheet-row', [
                'id' => $c->id,
                'href' => route('sm.ai', ['id' => $schedule->id, 'c' => $c->id]),
                'title' => $c->title,
                'when' => $c->updated_at?->diffForHumans(),
                'link' => $c->link_label,
                'active' => $conversation && $conversation->id === $c->id,
            ])
        @endforeach
        @if ($conversations->isEmpty())
            <p class="text-sm text-gray-500 text-center py-6" data-sessions-empty>No questions yet for this plan.</p>
        @endif
        <template id="aiSessionSheetRowTpl">@include('sm.partials.ai-session-sheet-row', [
            'id' => '__ID__',
            'href' => route('sm.ai', ['id' => $schedule->id]) . '&c=__ID__',
            'title' => '',
            'when' => 'just now',
            'link' => null,
            'active' => false,
        ])</template>
    </div>
</div>

{{-- The masthead's jobs, one sheet behind the square button. The rows keep
     the ids the page's handlers already bind — no second wiring. --}}
<div class="sheet hidden" id="aiMenuSheet" style="--sheet-width:20rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title">{{ $settings->assistantName }}</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body space-y-1">
        <button type="button" class="ai-attach-opt" id="aiNewChatBtn">
            <span class="ic"><svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg></span>
            <span>New session<span class="sub">Start a fresh question</span></span>
        </button>
        <button type="button" class="ai-attach-opt" id="aiHistoryBtn">
            <span class="ic"><svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 2m6-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></span>
            <span>Recent chats<span class="sub">Pick up an earlier question</span></span>
        </button>
        <button type="button" class="ai-attach-opt" id="aiLinkBtn">
            <span class="ic"><svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.83 10.17a4 4 0 010 5.66l-3 3a4 4 0 11-5.66-5.66l1.5-1.5m6.33-1.83a4 4 0 000-5.66l-1.5-1.5"/></svg></span>
            <span>Link<span class="sub">Tie this chat to a day or activity</span></span>
        </button>
        <button type="button" class="ai-attach-opt" id="aiMenuToTask">
            <span class="ic"><svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg></span>
            <span>Attach to a task<span class="sub">File this chat onto a task, in the notebook</span></span>
        </button>
        <button type="button" class="ai-attach-opt" id="aiMenuToNote">
            <span class="ic"><svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.4-9.4a2 2 0 112.8 2.8L11 15l-4 1 1-4 8.6-8.4z"/></svg></span>
            <span>Save as a new note<span class="sub">The whole conversation, into the notebook</span></span>
        </button>
        @unless ($aiUnlimited)
            <a href="{{ route('ai.credits') }}" class="ai-attach-opt">
                <span class="ic"><svg class="w-5 h-5" viewBox="0 0 20 20" fill="currentColor"><path d="M10 2a8 8 0 100 16 8 8 0 000-16zm.75 4.5v.63a2.5 2.5 0 01.2 4.84v.78a.75.75 0 01-1.5 0v-.75a2.6 2.6 0 01-1.83-1.1.75.75 0 011.24-.84c.24.35.63.57 1.09.57.6 0 1.05-.36 1.05-.83 0-.44-.3-.7-1.2-.95-1.13-.32-2.05-.8-2.05-2.05a2.2 2.2 0 011.5-2.03V6.5a.75.75 0 011.5 0z"/></svg></span>
                <span>AI credits<span class="sub"><span id="aiBalance">{{ rtrim(rtrim(number_format($balance, 2), '0'), '.') }}</span> left — top up here</span></span>
            </a>
        @endunless
    </div>
</div>
@endpush

@include('partials.ai-attach-task')

<div class="sheet hidden" id="aiTaskSheet" style="--sheet-width:22rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title">Which task?</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body space-y-1">
        @forelse ($aiPageTasks as $t)
            <button type="button" class="ai-attach-opt" data-ai-task="{{ $t->id }}">
                <span class="ic"><svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></span>
                <span class="min-w-0">{{ \Illuminate\Support\Str::limit($t->activityTitle ?: 'Task', 40) }}<span class="sub">{{ $t->targetDate ? \Illuminate\Support\Carbon::parse($t->targetDate)->format('M j, Y') : 'no set date' }}</span></span>
            </button>
        @empty
            <p class="text-sm text-gray-500 text-center py-6">No tasks on this schedule yet.</p>
        @endforelse
    </div>
</div>

<div class="sheet hidden" id="aiNoteSheet" style="--sheet-width:22rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title" id="aiNoteHeading">Save this chat as a note</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body space-y-3">
        <div>
            <label class="form-label" for="aiNoteTitle">Title</label>
            <input type="text" id="aiNoteTitle" class="form-input" maxlength="180" placeholder="Name this note">
        </div>
        <div>
            <label class="form-label" for="aiNoteDesc">Description <span class="text-gray-400 font-normal">(optional)</span></label>
            <textarea id="aiNoteDesc" class="form-textarea" rows="3" maxlength="2000" placeholder="Why this chat is worth keeping…"></textarea>
        </div>
        <p class="text-xs text-gray-400">The whole conversation is attached underneath.</p>
        <button type="button" id="aiNoteSave" class="btn btn-primary w-full">Save to the notebook</button>
    </div>
</div>

@unless (request()->boolean('partial'))
@push('appbar-actions')
<button type="button" id="aiMenuBtn"
        class="flex items-center justify-center w-9 h-9 md:w-10 md:h-10 rounded-full text-gray-500 hover:bg-gray-100 transition overflow-hidden"
        title="{{ $settings->assistantName }} options" aria-label="{{ $settings->assistantName }} options" aria-haspopup="dialog">
        {{-- Three dots, not her face. A portrait in a toolbar reads as "who"
             and this button is "what can I do" — and the face is already on
             every one of her answers below it. --}}
        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="5" r="1.9"/><circle cx="12" cy="12" r="1.9"/><circle cx="12" cy="19" r="1.9"/></svg>
</button>
@endpush
@endunless

@push('scripts')
<script>
(() => {
const __init = () => {
    const SCHEDULE_ID = @json($schedule->id);
    const URLS = {
        ask: @json(route('ai.ask')),
        photo: @json(route('ai.photo')),
        attach: @json(route('ai.photo.existing')),
        newConvo: @json(route('ai.conversation.new')),
        delConvo: (id) => @json(route('ai.conversation.delete')) + '?id=' + id,
        page: @json(route('sm.ai', ['id' => $schedule->id])),
        credits: @json(route('ai.credits')),
        toNote: @json(route('ai.conversation.note')),
        rename: @json(route('ai.conversation.rename')),
        link: @json(route('ai.conversation.link')),
    };
    const COIN = '<svg class="w-5 h-5" viewBox="0 0 20 20" fill="currentColor"><path d="M10 2a8 8 0 100 16 8 8 0 000-16zm.75 4.5v.63a2.5 2.5 0 01.2 4.84v.78a.75.75 0 01-1.5 0v-.75a2.6 2.6 0 01-1.83-1.1.75.75 0 011.24-.84c.24.35.63.57 1.09.57.6 0 1.05-.36 1.05-.83 0-.44-.3-.7-1.2-.95-1.13-.32-2.05-.8-2.05-2.05a2.2 2.2 0 011.5-2.03V6.5a.75.75 0 011.5 0z"/></svg>';
    const buyCard = (msg) => `<div class="ai-buyc"><span class="ico">${COIN}</span><div><h3>You're out of AI Credits</h3><p>${escapeHtml(msg)}</p><a class="btn btn-accent btn-sm mt-2" href="${escapeHtml(URLS.credits)}">Purchase AI credits</a></div></div>`;
    const AVATAR = @json($settings->faceUrl());
    const MY_FACE = @json(\App\Support\ChatFace::mine());
    const BOT = '<svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2m0 0a7 7 0 017 7v3a3 3 0 01-3 3H8a3 3 0 01-3-3v-3a7 7 0 017-7zM9 12h.01M15 12h.01M9.5 17h5"/></svg>';

    const UNLIMITED = @json((bool) $aiUnlimited);
    /* The bill, quoted before it is run up: the server's own pre-flight
       formula, mirrored, repriced on every keystroke and every chip. */
    @php
        // Precomputed: @json splits on commas (value, flags, depth) and an
        // inline array literal compiles to truncated, unparseable PHP.
        $aiPriceCard = ['inK' => (float) $settings->creditsPerInputK, 'outK' => (float) $settings->creditsPerOutputK, 'img' => (float) $settings->creditsPerImage, 'halfOut' => (int) $settings->maxOutputTokens / 2];
    @endphp
    const PRICE = @json($aiPriceCard);
    function sayEstimate() {
        const hint = byId('aiHint');
        if (!hint) return;
        const msg = (input?.value || '').trim();
        const shots = chips ? chips.children.length : 0;
        if (!msg && !shots) { hint.textContent = hint.dataset.idle || ''; return; }
        /* The season rides in front of the question when the switch is on,
         * and the model is billed for every word of it — so the quote has to
         * carry it too, or the number under the box is a number for a
         * different question than the one about to be sent. */
        const planOn = byId('aiUsePlan')?.getAttribute('aria-pressed') === 'true';
        const planTin = planOn ? Number(byId('aiUsePlan')?.dataset.planTokens || 0) : 0;
        /* What a question weighs before its own words: the house prompt
           and the persona, measured server-side from the text actually
           sent, plus room for the turns before it. Not a number typed
           in here — there are four of these composers, and four copies
           of one constant is four chances to disagree with the wall. */
        const OVERHEAD = @json(\App\Services\AiCreditService::overheadTokens());
        const tin = Math.ceil(msg.length / 4) + OVERHEAD + planTin;
        const cost = Math.max(.01, Math.round((tin / 1000 * PRICE.inK + PRICE.halfOut / 1000 * PRICE.outK + shots * PRICE.img) * 100) / 100);
        hint.textContent = planOn
            ? `≈ ${cost} credits — the season is attached`
            : `≈ ${cost} credits for this question`;
    }

    const CAN_ASK = @json((bool) $settings->isUsable());

    let conversationId = @json($conversation->id ?? null);
    // Up to six photos ride on one question; each chip carries its stored
    // path once its upload lands, and send waits for the stragglers.
    const MAX_PHOTOS = 6;
    let uploadsBusy = 0;
    let busy = false;

    const byId = (id) => document.getElementById(id);
    const thread = byId('aiThread');
    const face = (me) => me ? MY_FACE : (AVATAR ? `<img data-ai-face src="${escapeHtml(AVATAR)}" alt="">` : BOT);
    const nowStamp = () => new Date().toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' });

    /** Light markdown -> safe HTML (escape-first allow-list). */
    function render(text) {
        const esc = escapeHtml(text || '');
        const lines = esc.split(/\r?\n/); let html = ''; let list = null;
        const close = () => { if (list) { html += `</${list}>`; list = null; } };
        const inline = (s) => window.aneeEmoji(s.replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>').replace(/(^|\s)\*([^*]+)\*(?=\s|$|[.,;:!?])/g, '$1<em>$2</em>'));
        for (const raw of lines) {
            const line = raw.trim();
            if (!line) { close(); continue; }
            // A rule between sections. Before the bullet check, though it could
            // not be taken for one: a bullet needs a space after its mark.
            if (/^(?:-{3,}|\*{3,}|_{3,})$/.test(line)) { close(); html += '<hr class="ai-rule">'; continue; }
            const b = line.match(/^[-*•]\s+(.*)$/), n = line.match(/^\d+[.)]\s+(.*)$/);
            if (b) { if (list !== 'ul') { close(); html += '<ul>'; list = 'ul'; } html += '<li>' + inline(b[1]) + '</li>'; }
            else if (n) { if (list !== 'ol') { close(); html += '<ol>'; list = 'ol'; } html += '<li>' + inline(n[1]) + '</li>'; }
            else { close(); html += '<p>' + inline(line) + '</p>'; }
        }
        close(); return html || '<p></p>';
    }

    function scrollDown() { thread.scrollTop = thread.scrollHeight; }

    // New turns wear .is-new so only they animate in — history stays settled.
    // `images` is a list of URLs: one renders naturally, two or more grid up.
    function addTurn(me, html, images, cost, stamped) {
        byId('aiWelcome')?.remove();
        const shots = (images || []).filter(Boolean);
        const shotsHtml = shots.length ? `<div class="ai-shots${shots.length > 1 ? ' is-multi' : ''}">${shots.map((u) => `<img src="${escapeHtml(u)}" alt="Attached photo">`).join('')}</div>` : '';
        const el = document.createElement('div');
        el.className = 'aimsg is-new' + (me ? ' me' : '');
        el.innerHTML = `<span class="aimsg-face">${face(me)}</span><div class="aibubble">${html}${shotsHtml}${cost ? `<p class="aibubble-cost">${escapeHtml(cost)}</p>` : ''}${stamped ? `<time class="ai-when">${escapeHtml(nowStamp())}</time>` : ''}</div>`;
        thread.appendChild(el);
        scrollDown();
        return el;
    }

    function setBalance(v) {
        document.querySelectorAll('[data-ai-bal] b').forEach((el) => {
            if (el.textContent.trim() === '\u221E') return;
            el.textContent = (Math.round(Number(v) * 100) / 100).toString();
        });
        const balEl = byId('aiBalance');
        if (balEl) balEl.textContent = String(Math.round(v * 100) / 100);
        // Accounts that ride free never see the empty-wallet note.
        byId('aiNoCredits')?.classList.toggle('hidden', UNLIMITED || v > 0);
    }

    // The send button and the hint line both say what is happening.
    const SPIN = '<svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" aria-hidden="true" style="animation:ai-spin .7s linear infinite"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2.4" stroke-opacity=".3"/><path d="M21 12a9 9 0 0 0-9-9" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"/></svg>';
    const sendIdleHtml = byId('aiSendBtn') ? byId('aiSendBtn').innerHTML : '';
    // Send stays down while any photo upload is still in flight.
    function updateSend() {
        const btn = byId('aiSendBtn');
        if (btn) btn.disabled = !CAN_ASK || busy || uploadsBusy > 0;
        const line = byId('aiAttachBusy');
        if (line) {
            line.classList.toggle('hidden', uploadsBusy === 0);
            line.querySelector('.tx').textContent = uploadsBusy > 1 ? `Attaching ${uploadsBusy} photos…` : 'Attaching photo…';
        }
    }
    function setSending(on) {
        const btn = byId('aiSendBtn');
        if (!btn) return;
        updateSend();
        btn.innerHTML = on ? SPIN : sendIdleHtml;
        btn.setAttribute('aria-label', on ? 'Sending' : 'Send');
        const hint = byId('aiHint');
        if (hint) hint.textContent = on ? 'Asking the technician…' : (hint.dataset.idle || '');
    }

    const input = byId('aiText');
    // The bar only shows once the box has stopped growing — while it still
    // fits, the height IS the scroll.
    input?.addEventListener('input', () => { input.style.height = 'auto'; input.style.overflowY = input.scrollHeight > 112 ? 'auto' : 'hidden'; input.style.height = Math.min(input.scrollHeight, 112) + 'px';  sayEstimate(); });
    input?.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' && !e.shiftKey && window.matchMedia('(min-width: 768px)').matches) { e.preventDefault(); send(); }
    });
    /* The button reads short so three of them fit above the composer; the
     * question it fills in is the long, specific one that gets a good answer. */
    document.querySelectorAll('.js-suggest').forEach((b) => b.addEventListener('click', () => { input.value = (b.dataset.q || b.querySelector('.t')?.textContent || b.textContent).trim(); input.dispatchEvent(new Event('input')); input.focus(); }));

    /* A question carried in from somewhere else — the tip of the day's "ask
     * about this", say. Typed into the box and left there: sending is the
     * reader's move, and a question that sends itself spends credits nobody
     * agreed to spend. */
    (function askFromQuery() {
        let q = '';
        try { q = new URLSearchParams(location.search).get('q') || ''; } catch (_) { return; }
        if (!q || !input) return;
        input.value = q;
        input.dispatchEvent(new Event('input'));
        // Read from the top: the reader is checking what is about to be asked,
        // not carrying on from the end of it. Said twice, because the box is
        // still being laid out the first time.
        input.scrollTop = 0;
        setTimeout(() => { input.dispatchEvent(new Event('input')); input.scrollTop = 0; }, 200);
        window.smFocus?.(input, { delay: 240 });
        // Focus scrolls a textarea to its caret, which is at the end of what
        // was just put in it — so the top is put back afterwards. The caret
        // stays where it is: the first keystroke brings the view back down to
        // it, which is what somebody carrying on typing expects.
        setTimeout(() => { input.scrollTop = 0; }, 340);
    })();


    /* ---- Photos: chips under the composer, one per attachment ---- */
    const chips = byId('aiPhotoChips');
    const CHIP_SPIN = '<svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" aria-hidden="true" style="animation:ai-spin .7s linear infinite"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2.6" stroke-opacity=".3"/><path d="M21 12a9 9 0 0 0-9-9" stroke="currentColor" stroke-width="2.6" stroke-linecap="round"/></svg>';

    function attachedPaths() { return [...chips.querySelectorAll('.ai-chip[data-path]')].map((c) => c.dataset.path); }
    function attachedUrls() { return [...chips.querySelectorAll('.ai-chip[data-path] img')].map((i) => i.src); }
    function attachedScheds() {
        return [...chips.querySelectorAll('.ai-chip[data-path]')].map((c) => c.dataset.sched ? parseInt(c.dataset.sched, 10) : null);
    }
    function roomForAnother() {
        if (chips.children.length < MAX_PHOTOS) return true;
        toast('Up to ' + MAX_PHOTOS + ' photos per question — remove one to add another.', 'error');
        return false;
    }
    function addChip(previewUrl) {
        const el = document.createElement('div');
        el.className = 'ai-chip is-busy';
        el.innerHTML = `<img src="${escapeHtml(previewUrl)}" alt="">`
            + `<span class="st">${CHIP_SPIN}</span>`
            + '<button type="button" class="x" aria-label="Remove photo">✕</button>';
        chips.appendChild(el);
        sayEstimate();
        return el;
    }
    function dropChip(el) {
        if (el._blob) { try { URL.revokeObjectURL(el._blob); } catch (e) {} }
        el.remove();
        sayEstimate();
    }
    function clearPhotos() { [...chips.children].forEach(dropChip); }
    chips?.addEventListener('click', (e) => {
        const x = e.target.closest('.ai-chip .x');
        if (x) dropChip(x.closest('.ai-chip'));
    });

    // Uploads run one call per file; the chip spins until its path lands.
    function uploadOne(file) {
        if (!file || !(file.type || '').startsWith('image/')) return;
        if (!roomForAnother()) return;
        const preview = URL.createObjectURL(file);
        const chip = addChip(preview);
        chip._blob = preview;
        uploadsBusy++; updateSend();
        const form = new FormData();
        form.append('image', file);
        // Which season this photo belongs to, so it lands in that
        // gallery rather than the global one.
        try { form.append('scheduleId', String(SCHEDULE_ID)); } catch (_) {}
        api(URLS.photo, { method: 'POST', body: form })
            .then((res) => { chip.dataset.path = res.data.path; chip.classList.remove('is-busy'); })
            .catch((err) => { toast(err.message, 'error'); dropChip(chip); })
            .finally(() => { uploadsBusy--; updateSend(); });
    }
    byId('aiPhotoFiles')?.addEventListener('change', (e) => { [...(e.target.files || [])].forEach(uploadOne); e.target.value = ''; });
    byId('aiPhotoCam')?.addEventListener('change', (e) => { [...(e.target.files || [])].forEach(uploadOne); e.target.value = ''; });

    // A gallery pick is already hosted here — the server keeps its own copy.
    function attachFromGallery(item, gallerySid) {
        // A reference, not a copy: the server already hosts this picture and
        // the ask endpoint honours the picker's own list — so the chip lands
        // done, instantly. Copying at attach time (a download plus a
        // re-upload before a word was typed) was the slowness.
        if (!item || !item.url || !item.path || !roomForAnother()) return;
        const chip = addChip(item.url);
        chip.dataset.path = item.path;
        chip.dataset.sched = String(gallerySid || '');
        chip.classList.remove('is-busy');
        sayEstimate();
    }

    /* ---- The attach chooser (house sheet). This page always has its
            schedule, so the gallery door is live whenever the season
            picker travelled with the page. ---- */
    const canGallery = () => typeof window.smPickMedia === 'function' && SCHEDULE_ID > 0;
    byId('aiAttachBtn')?.addEventListener('click', () => {
        byId('aiAttachGallery')?.classList.toggle('hidden', !canGallery());
        openSheet('aiAttachSheet');
    });
    byId('aiAttachUpload')?.addEventListener('click', () => { closeSheet('aiAttachSheet'); byId('aiPhotoFiles')?.click(); });
    byId('aiAttachCamera')?.addEventListener('click', () => { closeSheet('aiAttachSheet'); byId('aiPhotoCam')?.click(); });
    byId('aiAttachGallery')?.addEventListener('click', () => {
        closeSheet('aiAttachSheet');
        if (!canGallery()) return;
        window.smPickMedia({
            scheduleId: SCHEDULE_ID,
            kinds: 'image',
            title: 'Attach from the gallery',
            // Several at once - the question can carry what room remains.
            multiple: true,
            max: MAX_PHOTOS - chips.children.length,
            onPick: (item) => attachFromGallery(item, SCHEDULE_ID),
        });
    });

    /* Two switches, and nothing hidden behind them: each says out loud what
       the next question will carry. Off by default for the plan, because a
       general question deserves a general answer; on for the chat, because a
       chat with no memory is not a chat. */
    document.addEventListener('click', (e) => {
        const t = e.target.closest('#aiUsePlan, #aiUseMemory');
        if (!t) return;
        const on = t.getAttribute('aria-pressed') !== 'true';
        t.setAttribute('aria-pressed', on ? 'true' : 'false');
        t.classList.toggle('is-on', on);
        if (t.id === 'aiUsePlan') {
            sayEstimate();
            toast(on ? 'She will read this season\u2019s plan — it is added to the question.'
                     : 'She will answer without your plan.');
        } else {
            toast(on ? 'She will read the rest of this chat.'
                     : 'She will answer this question on its own.');
        }
    });

    async function send() {
        if (busy) return;
        if (uploadsBusy > 0) { toast('Wait a moment — a photo is still uploading.', 'error'); return; }
        const message = input.value.trim();
        if (!message) { toast('Type a question first.', 'error'); return; }
        busy = true; setSending(true);
        const myPaths = attachedPaths();
        const myScheds = attachedScheds();
        addTurn(true, '<p>' + escapeHtml(message).replace(/\r?\n/g, '<br>') + '</p>', attachedUrls(), null, true);
        input.value = ''; input.style.height = 'auto'; sayEstimate();
        const thinking = addTurn(false, '<span class="aidots"><i></i><i></i><i></i></span>');
        try {
            const res = await api(URLS.ask, { method: 'POST', body: {
                message, conversationId, imagePaths: myPaths, imageScheduleIds: myScheds,
                scheduleId: SCHEDULE_ID,
                // Asked for, or not sent. scheduleId still travels because it
                // says where the chat LIVES; usePlan says whether she reads it.
                // The whole season, which is what the switch is called and
                // what its price is quoted for — not the one-line label it
                // used to send under the same name.
                attachPlan: byId('aiUsePlan')?.getAttribute('aria-pressed') === 'true' ? 1 : 0,
                forget: byId('aiUseMemory')?.getAttribute('aria-pressed') === 'true' ? 0 : 1,
            } });
            conversationId = res.data.conversationId;
            noteSession(res.data);
            // Chips leave the moment the send is known good - before any
            // templating that could throw and strand them in the composer.
            clearPhotos();
            const costLine = UNLIMITED ? '' : `<p class="aibubble-cost">${escapeHtml(String(Math.round(res.data.answer.creditsCharged * 100) / 100))} credits</p>`;
            thinking.querySelector('.aibubble').innerHTML = render(res.data.answer.content) + costLine + `<time class="ai-when">${escapeHtml(nowStamp())}</time>`;
            setBalance(res.data.balance); scrollDown();
        } catch (err) {
            thinking.remove();
            if (err.data && err.data.outOfCredits) {
                setBalance(err.data.balance || 0);
                addTurn(false, buyCard(err.message)).querySelector('.aibubble').classList.add('is-buy');
            } else { addTurn(false, '<p>' + escapeHtml(err.message) + '</p>'); }
            // Kept on purpose - a retry should not re-pick its photos. Said
            // out loud, so a failed send never reads as "sent but not cleared".
            if (chips.children.length) toast('Your photos are still attached, ready for the retry.');
            input.value = message; input.dispatchEvent(new Event('input'));
        } finally { busy = false; setSending(false); input.focus(); }
    }
    byId('aiSendBtn')?.addEventListener('click', send);

    /* The chat is a session the moment it is answered.
     *
     * The server has already written the row — it is what the answer is
     * attached to — but the two lists on this page were drawn when the page
     * was, so a chat you had just started was nowhere until a reload, and
     * "get back to it" meant knowing it was there. Both lists learn about it
     * from the answer itself: the row it clones is the same partial the page
     * rendered, so nothing can drift.
     */
    function noteSession(data) {
        const id = data && data.conversationId;
        if (!id) return;
        const title = data.conversationTitle || 'New question';

        [['aiSessions', 'aiSessionRowTpl', '[data-convo-row]'],
         ['aiHistorySheetBody', 'aiSessionSheetRowTpl', '[data-convo-sheet-row]']].forEach(([hostId, tplId, sel]) => {
            const host = byId(hostId);
            const tpl = byId(tplId);
            if (!host || !tpl) return;

            let row = host.querySelector(`${sel.slice(0, -1)}="${id}"]`);
            if (!row) {
                host.querySelector('[data-sessions-empty]')?.remove();
                const frag = document.createElement('div');
                frag.innerHTML = tpl.innerHTML.split('__ID__').join(String(id));
                row = frag.firstElementChild;
                // Under the list's own head (the rail's title row, the sheet's
                // "start a new question"), which is where the newest belongs.
                const first = host.querySelector(sel);
                if (first) host.insertBefore(row, first);
                else host.appendChild(row);
                window.animateIn?.(row);
            }
            const t = row.querySelector('[data-session-title]');
            if (t) t.textContent = title;
            const w = row.querySelector('[data-session-when]');
            if (w) w.textContent = 'just now';
            // Only one chat is the one on screen.
            host.querySelectorAll(sel).forEach((r) => {
                const on = r === row;
                r.classList.toggle('is-active', on && r.hasAttribute('data-convo-row'));
                const link = r.querySelector('.js-ai-convo');
                if (link && r.hasAttribute('data-convo-sheet-row')) {
                    link.classList.toggle('bg-brand-50', on);
                    link.classList.toggle('text-brand-700', on);
                }
            });
        });
    }

    /* Link this chat to a day or an activity of the schedule. */
    byId('aiLinkBtn')?.addEventListener('click', () => openSheet('aiLinkSheet'));
    function updateLinkChip(label) {
        const chip = byId('aiLinkChip');
        const btn = byId('aiLinkBtn');
        if (label) {
            byId('aiLinkChipText').textContent = label;
            chip.classList.remove('hidden'); chip.classList.add('flex');
            btn?.classList.add('text-brand-700');
        } else {
            chip.classList.add('hidden'); chip.classList.remove('flex');
            btn?.classList.remove('text-brand-700');
        }
    }
    async function ensureConversation() {
        if (conversationId) return conversationId;
        const res = await api(URLS.newConvo, { method: 'POST', body: { scheduleId: SCHEDULE_ID } });
        conversationId = res.data.conversationId;
        return conversationId;
    }
    async function saveLink(linkType, extra) {
        try {
            await ensureConversation();
            const res = await api(URLS.link, { method: 'POST', body: Object.assign({ conversationId, linkType }, extra || {}) });
            updateLinkChip(res.data.linkLabel || '');
            toast(res.message);
            closeSheet('aiLinkSheet');
        } catch (err) { toast(err.message, 'error'); }
    }
    byId('aiLinkDayBtn')?.addEventListener('click', () => {
        const d = byId('aiLinkDate').value;
        if (!d) { toast('Choose a day.', 'error'); return; }
        saveLink('date', { linkedDate: d });
    });
    byId('aiLinkActBtn')?.addEventListener('click', () => {
        const a = byId('aiLinkActivity').value;
        if (!a) { toast('Choose an activity.', 'error'); return; }
        saveLink('activity', { linkedActivityId: a });
    });
    byId('aiLinkRemoveBtn')?.addEventListener('click', () => saveLink('none'));
    byId('aiLinkChipClear')?.addEventListener('click', () => saveLink('none'));

    /* history + conversations */
    byId('aiHistoryBtn')?.addEventListener('click', () => openSheet('aiHistorySheet'));

    /* Filing this chat into the notebook — plain, or onto a task. */
    let aiPendingTaskId = null;
    function aiFileAway(activityId) {
        if (!conversationId) { toast('Nothing to save yet — ask something first, or open an old chat.', 'error'); return; }
        aiPendingTaskId = activityId || null;
        const head = byId('aiNoteHeading');
        if (head) head.textContent = aiPendingTaskId ? 'Attach this chat to the task' : 'Save this chat as a note';
        byId('aiNoteTitle').value = '';
        byId('aiNoteDesc').value = '';
        openSheet('aiNoteSheet');
        window.smFocus?.(byId('aiNoteTitle'), { delay: 120 });
    }
    byId('aiMenuToNote')?.addEventListener('click', () => { window.closeSheet?.('aiMenuSheet'); aiFileAway(null); });
    /* Onto a day, or onto a task on it.
     *
     * The flat list of this season's tasks is gone: it offered every job of
     * the whole season at once and had no way to say "just this day". The
     * shared sheet asks the day first and then what on it, and the season is
     * not asked at all because this page is standing in one. */
    byId('aiMenuToTask')?.addEventListener('click', () => {
        window.closeSheet?.('aiMenuSheet');
        if (!conversationId) { toast('Nothing to save yet — ask something first, or open an old chat.', 'error'); return; }
        window.aiAttachOpen?.({
            askSchedule: false,
            scheduleId: SCHEDULE_ID,
            save: async (a) => {
                const res = await api(URLS.toNote, { method: 'POST', body: {
                    conversationId,
                    scheduleId: a.scheduleId,
                    activityId: a.activityId,
                    noteDate: a.date,
                    title: a.title,
                    description: a.description,
                } });
                toast(res.message || 'Kept in the notebook.');
            },
        });
    });
    document.addEventListener('click', (e) => {
        const b = e.target.closest('[data-ai-task]');
        if (!b) return;
        window.closeSheet?.('aiTaskSheet');
        aiFileAway(parseInt(b.dataset.aiTask, 10));
    });
    byId('aiNoteSave')?.addEventListener('click', async () => {
        const btn = byId('aiNoteSave');
        const was = btn.textContent;
        btn.disabled = true; btn.textContent = 'Saving…';
        try {
            const res = await api(URLS.toNote, { method: 'POST', body: {
                conversationId,
                scheduleId: SCHEDULE_ID,
                activityId: aiPendingTaskId,
                title: byId('aiNoteTitle').value.trim(),
                description: byId('aiNoteDesc').value.trim(),
            } });
            window.closeSheet?.('aiNoteSheet');
            toast(res.message || 'Saved.');
            // Filed away means finished with: on to a fresh session.
            startNew();
        } catch (err) { toast(err.message, 'error'); }
        finally { btn.disabled = false; btn.textContent = was; }
    });

    /* The square button beside the bell — or, in the shell, seated on the
       toolbar beside the hamburger. Relocated by hand because the pane's
       markup cannot reach the shell's row; margin-left:auto keeps it at the
       right edge without asking the row to wrap. */
    const IN_SHELL = @json(request()->boolean('partial'));
    if (IN_SHELL) {
        const btn = byId('aiMenuBtn');
        const bar = document.getElementById('actToolbar');
        if (btn && bar) {
            bar.querySelector('#aiMenuBtn')?.remove();   // a re-fetched pane must not double it
            bar.appendChild(btn);
            btn.classList.remove('hidden');
        }
        // While this module holds the stage: the button shows, the page
        // holds still. Both undone the moment another module takes over.
        document.documentElement.classList.add('sm-ai-open');
        document.addEventListener('sm:module-shown', (e) => {
            const mine = (e.detail && e.detail.key) === 'ai';
            btn?.classList.toggle('hidden', !mine);
            document.documentElement.classList.toggle('sm-ai-open', mine);
        });
    }
    byId('aiMenuBtn')?.addEventListener('click', () => openSheet('aiMenuSheet'));
    ['aiNewChatBtn', 'aiHistoryBtn', 'aiLinkBtn'].forEach((id) =>
        byId(id)?.addEventListener('click', () => window.closeSheet?.('aiMenuSheet')));
    async function startNew() {
        try { const res = await api(URLS.newConvo, { method: 'POST', body: { scheduleId: SCHEDULE_ID } }); location.href = URLS.page + '&c=' + res.data.conversationId; }
        catch (err) { toast(err.message, 'error'); }
    }
    byId('aiNewChatBtn')?.addEventListener('click', startNew);
    byId('aiNewFromSheet')?.addEventListener('click', startNew);
    byId('aiSideNewBtn')?.addEventListener('click', startNew);

    // Inline rename in the sessions rail.
    document.addEventListener('click', (e) => {
        const btn = e.target.closest('.js-side-rename');
        if (!btn) return;
        e.preventDefault();
        const row = btn.closest('.ai-session-row');
        const titleEl = row.querySelector('[data-session-title]');
        if (!titleEl || row.querySelector('.ai-session-rename')) return;
        const input = document.createElement('input');
        input.type = 'text';
        input.maxLength = 60;
        input.value = titleEl.textContent.trim();
        input.className = 'ai-session-rename';
        titleEl.style.display = 'none';
        titleEl.after(input);
        input.focus();
        input.select();
        let done = false;
        const finish = async (save) => {
            if (done) return;
            done = true;
            const next = input.value.trim();
            input.remove();
            titleEl.style.display = '';
            if (!save || !next || next === titleEl.textContent.trim()) return;
            try {
                const res = await api(URLS.rename, { method: 'POST', body: { id: btn.dataset.id, title: next } });
                titleEl.textContent = res.data.title;
                toast('Chat renamed.');
            } catch (err) { toast(err.message, 'error'); }
        };
        input.addEventListener('keydown', (ev) => {
            if (ev.key === 'Enter') finish(true);
            if (ev.key === 'Escape') finish(false);
        });
        input.addEventListener('blur', () => finish(true));
    });

    // Inside the SPA these are real links; let them navigate the shell.
    document.addEventListener('click', async (e) => {
        const del = e.target.closest('.js-ai-del');
        if (del) {
            e.preventDefault();
            const ok = await confirmAction({ title: 'Delete this conversation?', message: 'Its questions and answers are removed.', detail: UNLIMITED ? '' : 'Credits already spent are not refunded.', confirmText: 'Delete' });
            if (!ok) return;
            try { await api(URLS.delConvo(del.dataset.id), { method: 'DELETE' }); del.closest('.ai-session-row, .flex').remove(); if (String(del.dataset.id) === String(conversationId)) location.href = URLS.page; }
            catch (err) { toast(err.message, 'error'); }
        }
    });

    // Only auto-scroll when there is a conversation; keep the welcome hero in view.
    if (!byId('aiWelcome')) scrollDown();
};
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', __init, { once: true });
    else __init();
})();
</script>
@endpush
