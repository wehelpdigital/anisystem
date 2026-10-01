{{-- How anee.io works: the season in six steps, Anee at every one
     (App\Support\HowItWorks). One partial, two homes: the public page
     /how-it-works ($hwMode 'site') and the full screen tour behind the
     dashboard's card ($hwMode 'app', inside a scrolling modal).

     The picture borrows the owner's favourite piece of another site (a dot
     matrix map whose hub sends drawn routes, with pulses running along
     them, to the places it serves): every step is a field of dots, Anee is
     the hub in the middle of it, and each tool she works with floats at the
     end of a route. A rail runs down the page from the phone at the top and
     fills as you scroll; each hub lights up with Anee's face as it reaches
     her. Tap a tool and it opens to say what it does.

     The phone at the top plays a short film of the real app: the login page,
     then the dashboard as it looks inside (the greeting, the tiles, the tip
     of the day, today's work on a season and its weather, the news feed),
     scrolled the way a thumb would, each note around the phone lighting up
     as its part comes into view. It is drawn at a real phone's width (390px)
     and scaled to the frame, so its sizes are the app's own.

     The script mounts on a root (window.HowItWorks.mount(root, {scroller})),
     so the same code runs on the page (window scroll) and in the modal (the
     modal's own scroll), and sizes nothing until it is on screen. --}}
@php
    $hwMode = $hwMode ?? 'site';
    $hwStages = \App\Support\HowItWorks::stages();
    $hwPhone = \App\Support\HowItWorks::phone();
    $hwPings = \App\Support\HowItWorks::pings();
    $hwPh = \App\Support\Region::ph();
    $hwLink = function (array $it) use ($hwMode, $hwPh) {
        if ($it['url']) {
            return [url($it['url']), 'Read the guides'];
        }
        if ($hwMode === 'app') {
            if ($it['app'] && \Illuminate\Support\Facades\Route::has($it['app'])) {
                return [route($it['app']), $it['app'] === 'sm.index' ? 'Find it in your schedules' : 'Open it now'];
            }

            return null;
        }
        if ($it['page'] && $hwPh) {
            return [\App\Support\SitePages::url('features', $it['page']), 'Read more about it'];
        }

        return null;
    };
    // Anee's line, a word at a time: every word is there from the start (so
    // nothing jumps and a reader hears it whole); they are only shown in turn.
    $hwTop = $hwMode === 'site' ? 'h1' : 'h2';
    $hwHead = $hwMode === 'site' ? 'h2' : 'h3';
    $hwWords = fn (string $s) => collect(preg_split('/\s+/u', trim($s)))->map(fn ($w, $k) => '<span class="hw-w" style="--k:' . $k . '">' . e($w) . '</span>')->implode(' ');
@endphp

@once
@push('head')
<style>
    .hw { --ease: cubic-bezier(.22,1,.36,1); --ink: #eef4e6; --mute: #b9caa8; --soft: #8ea47a; --acc: #f5c518; --leaf: #a8cc7e;
        --pad: 1rem; --rail-x: 2rem; --hub: 2.9rem; --gut: calc(var(--rail-x) - var(--pad) + var(--hub) / 2 + .75rem);
        position: relative; color: var(--ink); background: #0d1609; overflow-x: clip; text-align: left; }
    @media (min-width: 640px) { .hw { --pad: 1.5rem; --rail-x: 2.6rem; } }
    @media (min-width: 1024px) { .hw { --pad: 2rem; --rail-x: 50%; --hub: 7.5rem; } }
    .hw *, .hw *::before, .hw *::after { box-sizing: border-box; }
    .hw img { max-width: none; }

    /* ---------- the top: Anee on the phone ---------- */
    .hw-hero { position: relative; isolation: isolate; padding: 3.2rem var(--pad) 0; text-align: center;
        background: radial-gradient(60rem 32rem at 50% -8%, rgb(134 181 86 / .3), transparent 64%), linear-gradient(180deg, #14250d 0%, #0d1609 100%); }
    .hw-hero::before { content: ''; position: absolute; inset: 0; z-index: -1; pointer-events: none;
        background-image: radial-gradient(rgb(255 255 255 / .14) 1.2px, transparent 1.7px); background-size: 22px 22px;
        -webkit-mask-image: radial-gradient(ellipse 70% 62% at 50% 46%, #000 25%, transparent 78%); mask-image: radial-gradient(ellipse 70% 62% at 50% 46%, #000 25%, transparent 78%); }
    .hw-hero::after { content: ''; position: absolute; left: 50%; top: 52%; width: 44rem; height: 44rem; margin: -22rem 0 0 -22rem; z-index: -1; border-radius: 999px; pointer-events: none;
        background: radial-gradient(closest-side, rgb(168 204 126 / .2), transparent 70%); animation: hwGlow 9s ease-in-out infinite; }
    @keyframes hwGlow { 50% { transform: scale(1.12); opacity: .7; } }
    .hw-kick { display: inline-flex; align-items: center; gap: .6rem; font-size: .72rem; font-weight: 900; letter-spacing: .16em; text-transform: uppercase; color: var(--leaf); }
    .hw-kick::before { content: ''; width: 1.6rem; height: 2px; border-radius: 2px; background: currentColor; }
    .hw-h1 { margin: .9rem auto 0; max-width: 48rem; font-family: var(--font-heading); font-weight: 800; color: #fff;
        font-size: clamp(2.05rem, 5.6vw, 3.75rem); line-height: 1.04; letter-spacing: -.02em; text-wrap: balance; }
    .hw-h1 em { font-style: normal; color: var(--acc); }
    .hw-lede { margin: 1.1rem auto 0; max-width: 40rem; font-size: 1.04rem; line-height: 1.65; color: var(--mute); }
    .hw-btns { margin-top: 1.6rem; display: flex; flex-wrap: wrap; gap: .6rem; justify-content: center; }
    .hw-ghost { display: inline-flex; align-items: center; gap: .45rem; padding: .78rem 1.25rem; border-radius: .9rem; font-weight: 800; color: #fff;
        border: 1.5px solid rgb(255 255 255 / .3); background: rgb(255 255 255 / .04); transition: background .28s var(--ease), border-color .28s var(--ease); }
    .hw-ghost:hover { background: rgb(255 255 255 / .1); border-color: rgb(255 255 255 / .5); }
    .hw-ghost svg { width: 1.05rem; height: 1.05rem; transition: transform .28s var(--ease); }
    .hw-ghost:hover svg { transform: translateY(2px); }
    .hw.is-ready .hw-copy > * { animation: hwUp .8s var(--ease) both; }
    .hw.is-ready .hw-copy > :nth-child(2) { animation-delay: .08s; } .hw.is-ready .hw-copy > :nth-child(3) { animation-delay: .16s; } .hw.is-ready .hw-copy > :nth-child(4) { animation-delay: .24s; }
    @keyframes hwUp { from { opacity: 0; transform: translateY(16px); } to { opacity: 1; transform: none; } }

    .hw-show { position: relative; margin: 2.4rem auto 0; width: 100%; max-width: 64rem; height: 35.5rem; }
    @media (min-width: 1024px) { .hw-show { height: 33rem; } }
    .hw-show-svg { position: absolute; inset: 0; width: 100%; height: 100%; overflow: visible; pointer-events: none; }
    .hw-phone { position: absolute; left: 50%; top: 0; z-index: 2; width: 15.5rem; height: 30rem; margin-left: -7.75rem; padding: .5rem; border-radius: 2.5rem;
        background: linear-gradient(160deg, #262d22, #0a0d08 70%);
        box-shadow: 0 50px 90px -40px rgb(0 0 0 / .9), 0 0 0 1px rgb(255 255 255 / .1) inset, 0 0 0 7px rgb(168 204 126 / .07), 0 0 60px -10px rgb(168 204 126 / .35); }
    @media (min-width: 1024px) { .hw-phone { width: 16.5rem; height: 32rem; margin-left: -8.25rem; } }
    .hw.is-ready .hw-phone { animation: hwPhone 1s .2s var(--ease) both; }
    @keyframes hwPhone { from { opacity: 0; transform: translateY(40px) scale(.96); } to { opacity: 1; transform: none; } }
    .hw-scr { position: relative; height: 100%; overflow: hidden; border-radius: 2.05rem; background: #f9fafb; color: #1f2937; text-align: left; }
    .hwr { position: absolute; left: 0; top: 0; width: 390px; height: calc(100% / var(--sc, .6)); transform: scale(var(--sc, .6)); transform-origin: 0 0;
        font-family: var(--font-sans); font-size: 16px; line-height: 1.5; }
    .hwr * { box-sizing: border-box; }
    .hw-scene { position: absolute; inset: 0; display: flex; flex-direction: column; opacity: 0; transform: translateX(26px); pointer-events: none;
        transition: opacity .5s var(--ease), transform .6s var(--ease); }
    .hw-scene.is-on { opacity: 1; transform: none; }
    .hw-scene.is-gone { opacity: 0; transform: translateX(-26px); }
    /* The fingertip: where the next tap lands. */
    .hw-tap { position: absolute; z-index: 9; width: 44px; height: 44px; margin: -22px 0 0 -22px; border-radius: 999px; pointer-events: none;
        background: rgb(20 33 12 / .16); box-shadow: 0 0 0 3px rgb(255 255 255 / .85); opacity: 0; }
    .hw-tap.is-tap { animation: hwTap .55s var(--ease); }
    @keyframes hwTap { 0% { opacity: 0; transform: scale(.4); } 30% { opacity: 1; transform: scale(1); } 100% { opacity: 0; transform: scale(1.6); } }

    /* -- the login page, as anee.io draws it -- */
    .s-login { background: #f9fafb; }
    .hwr-pub { flex: none; display: flex; align-items: center; justify-content: space-between; height: 64px; padding: 0 16px; background: #fff; border-bottom: 1px solid #f3f4f6; }
    .hwr-pub img { height: 28px; width: auto; transform: translateY(-9%); }
    .hwr-burger { display: grid; gap: 5px; width: 22px; }
    .hwr-burger i { height: 2px; border-radius: 2px; background: #374151; }
    .hwr-login { padding: 48px 16px 0; }
    .hwr-login h3 { text-align: center; font-family: var(--font-heading); font-size: 24px; font-weight: 700; color: #1a1a1a; }
    .hwr-login > p { margin-top: 4px; text-align: center; font-size: 14px; color: #4b5563; }
    .hwr-card { margin-top: 24px; padding: 16px; border-radius: 16px; background: #fff; box-shadow: 0 4px 6px -2px rgb(26 26 26 / .05), 0 12px 32px -8px rgb(26 26 26 / .14); }
    .hwr-lab { display: flex; justify-content: space-between; align-items: baseline; margin: 0 0 6px; font-size: 14px; font-weight: 600; color: #1f2937; }
    .hwr-lab a { font-size: 12px; font-weight: 600; color: #3d6823; }
    .hwr-in { position: relative; display: flex; align-items: center; height: 44px; margin-bottom: 16px; padding: 0 16px; border-radius: 12px; background: #fff; border: 1px solid #d1d5db;
        font-size: 16px; color: #1a1a1a; transition: border-color .3s var(--ease), box-shadow .3s var(--ease); }
    .hwr-in .ph { color: #9ca3af; }
    .hwr-in.has .ph { display: none; }
    .hwr-in em { display: inline-block; width: 2px; height: 20px; margin-left: 1px; background: #4a7c2a; opacity: 0; }
    .hwr-in.is-focus { border-color: #6b9f3d; box-shadow: 0 0 0 4px rgb(107 159 61 / .18); }
    .hwr-in.is-focus em { opacity: 1; animation: hwCaret 1s steps(1) infinite; }
    @keyframes hwCaret { 50% { opacity: 0; } }
    .hwr-keep { display: flex; align-items: center; gap: 10px; font-size: 14px; color: #374151; }
    .hwr-keep i { width: 18px; height: 18px; border-radius: 4px; display: grid; place-items: center; background: #2563eb; color: #fff; font-style: normal; font-size: 12px; font-weight: 900; }
    .hwr-btn { display: flex; align-items: center; justify-content: center; gap: 10px; height: 54px; margin-top: 18px; border-radius: 14px; background: #f5c518;
        font-size: 18px; font-weight: 500; color: #1a1a1a; transition: transform .2s var(--ease), filter .2s var(--ease); }
    .hwr-btn.is-press { transform: scale(.97); filter: brightness(.94); }
    .hwr-btn i { display: none; width: 18px; height: 18px; border-radius: 999px; border: 2.5px solid rgb(26 26 26 / .2); border-top-color: #1a1a1a; animation: hwSpin .7s linear infinite; }
    .hwr-btn.is-busy i { display: inline-block; }
    .hwr-sign { margin-top: 20px; text-align: center; font-size: 14px; color: #374151; }
    .hwr-sign b { color: #3d6823; }

    /* -- the dashboard, as the app draws it -- */
    .s-dash { background: #f9fafb; }
    .hwr-top { flex: none; display: flex; align-items: center; gap: 12px; height: 57px; padding: 0 16px; background: #fff; border-bottom: 1px solid #e5e7eb; }
    .hwr-top > img { height: 28px; width: auto; }
    .hwr-top b { display: block; font-size: 16px; font-weight: 700; line-height: 1.25; color: #111827; }
    .hwr-top small { display: block; font-size: 12px; color: #6b7280; line-height: 1.3; }
    .hwr-top .r { margin-left: auto; display: flex; align-items: center; gap: 4px; }
    .hwr-help { width: 36px; height: 36px; border-radius: 999px; display: grid; place-items: center; background: #eef6e5; color: #4a7c2a; font-weight: 800; font-size: 15px; box-shadow: inset 0 0 0 1.5px #cfe3b8; }
    .hwr-bell { position: relative; width: 40px; height: 40px; display: grid; place-items: center; color: #6b7280; }
    .hwr-bell svg { width: 22px; height: 22px; }
    .hwr-bell em { position: absolute; top: 3px; right: 3px; min-width: 17px; height: 17px; padding: 0 4px; border-radius: 999px; display: grid; place-items: center;
        background: #ef4444; color: #fff; font-style: normal; font-size: 10px; font-weight: 800; box-shadow: 0 0 0 2px #fff; }
    .hwr-av { width: 40px; height: 40px; border-radius: 999px; display: grid; place-items: center; background: #4a7c2a; color: #fff; font-size: 15px; font-weight: 800; }
    .hwr-view { position: relative; flex: 1; min-height: 0; overflow: hidden; }
    .hwr-list { display: flex; flex-direction: column; gap: 16px; padding: 16px; transition: transform 1.1s var(--ease); }
    .s-dash.is-on .hwr-list > * { animation: hwrUp .55s var(--ease) both; animation-delay: calc(var(--n, 0) * 70ms + 150ms); }
    @keyframes hwrUp { from { opacity: 0; transform: translateY(12px); } to { opacity: 1; transform: none; } }
    .hwr-list > [data-s] { transition: box-shadow .45s var(--ease); }
    .hwr-list > [data-s].is-hot { box-shadow: 0 0 0 3px rgb(245 197 24 / .85), 0 18px 34px -20px rgb(20 33 12 / .5); }
    .hwr-hero { display: grid; grid-template-columns: auto minmax(0, 1fr); gap: 9px 15px; align-items: center; padding: 20px 21px; border-radius: 18px;
        border: 1px solid #cfe0b8; background: linear-gradient(118deg, #f2f8ec, #e2f0d2 22%, #cfe6b6 46%, #e8f4dc 66%, #dceecb 84%, #f2f8ec); }
    .hwr-sky { grid-row: 1 / span 2; width: 54px; height: 54px; border-radius: 999px; overflow: hidden; align-self: center; }
    .hwr-sky svg { width: 100%; height: 100%; display: block; }
    .hwr-hero b { font-family: var(--font-heading); font-size: 21px; font-weight: 800; line-height: 1.2; letter-spacing: -.01em; color: #24380f; }
    .hwr-hero p { margin-top: 3px; font-size: 13px; color: #4c6b33; }
    .hwr-chips { grid-column: 2; display: flex; flex-wrap: wrap; gap: 6px; }
    .hwr-chips span { display: inline-flex; align-items: center; gap: 5px; padding: 5px 12px; border-radius: 999px; font-size: 12px; font-weight: 700;
        background: #f0f7e8; border: 1px solid #cfe3b8; color: #3d6823; }
    .hwr-chips .rank { background: #fff; border-color: #dfe8d4; color: #4b5563; }
    .hwr-stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; }
    .hwr-stats div { display: flex; flex-direction: column; align-items: center; justify-content: center; min-height: 76px; padding: 10px 6px; border-radius: 16px; text-align: center;
        background: #fff; border: 1px solid #e5e7eb; }
    .hwr-stats b { font-size: 22px; font-weight: 800; line-height: 1.1; color: #111827; }
    .hwr-stats b.w { font-size: 15px; }
    .hwr-stats i { margin-top: 3px; font-style: normal; font-size: 10px; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; color: #9ca3af; }
    .hwr-stats .lead { background: #f0f7e8; border-color: #cfe3b8; }
    .hwr-stats .lead b { color: #3d6823; } .hwr-stats .lead i { color: #6b9f3d; }
    .hwr-tip { position: relative; padding: 15px 17px; border-radius: 16px; color: #e8efe1; background: linear-gradient(135deg, #10160c 0%, #1c2416 55%, #24301a 100%);
        outline: 2px dashed #86b556; outline-offset: -5px; }
    .hwr-tip .h { display: flex; align-items: center; gap: 10px; }
    .hwr-tip .h > span { width: 36px; height: 36px; border-radius: 10px; display: grid; place-items: center; background: rgb(255 255 255 / .08); }
    .hwr-tip .h img { width: 22px; height: 22px; object-fit: contain; }
    .hwr-tip small { display: block; font-size: 11px; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; color: #a8cc7e; }
    .hwr-tip .h b { display: block; font-size: 14px; font-weight: 700; color: #fff; }
    .hwr-tip p { margin-top: 9px; font-size: 15px; line-height: 1.55; }
    .hwr-h { display: flex; align-items: center; justify-content: space-between; gap: 10px; margin-bottom: -4px; padding: 0 4px; font-size: 16px; font-weight: 700; color: #111827; }
    .hwr-h span { display: inline-flex; align-items: center; gap: 8px; }
    .hwr-h a { font-size: 14px; font-weight: 700; color: #3d6823; }
    .hwr-sched { border-radius: 16px; background: #fff; border: 1px solid #e5e7eb; overflow: hidden; }
    .hwr-cover { display: flex; align-items: center; gap: 8px; min-height: 58px; padding: 9px 16px; background: linear-gradient(120deg, #f4e9dc, #dfc9ac 42%, #cbb08c 68%, #ecdfcd); }
    .hwr-cover span { font-size: 22px; }
    .hwr-cover b { flex: 1; font-size: 16px; font-weight: 700; color: #111827; }
    .hwr-cover svg { width: 18px; height: 18px; color: #6b5a42; }
    .hwr-sb { display: flex; flex-direction: column; gap: 12px; padding: 16px; }
    .hwr-day { padding: 10px 10px 10px; border-radius: 14px; background: #f0f7e8; }
    .hwr-dh { display: flex; align-items: center; gap: 10px; margin-bottom: 8px; }
    .hwr-when { display: flex; flex-direction: column; align-items: center; min-width: 48px; padding: 4px 6px; border-radius: 9px; background: #4a7c2a; }
    .hwr-when b { font-size: 13px; font-weight: 800; line-height: 1.1; color: #fff; }
    .hwr-when i { font-style: normal; font-size: 9px; font-weight: 700; color: rgb(255 255 255 / .85); }
    .hwr-dt b { display: block; font-size: 13px; font-weight: 800; color: #1f2937; line-height: 1.2; }
    .hwr-dt i { display: block; font-style: normal; font-size: 11px; color: #6b7280; }
    .hwr-rail { overflow: hidden; border-radius: 11px; }
    .hwr-track { display: flex; transition: transform .55s var(--ease); }
    .hwr-task { flex: 0 0 100%; display: flex; flex-direction: column; gap: 4px; padding: 10px 11px 10px; border-radius: 11px; background: #fff; border: 1px solid #e5e7eb;
        border-left: 3px solid var(--p); }
    .hwr-task .t { display: flex; align-items: center; gap: 6px; }
    .hwr-task .ty { font-size: 10px; font-weight: 800; letter-spacing: .04em; text-transform: uppercase; color: #9ca3af; }
    .hwr-task .pr { margin-left: auto; padding: 2px 6px; border-radius: 999px; font-size: 9px; font-weight: 800; text-transform: uppercase; letter-spacing: .04em;
        color: var(--p); background: color-mix(in srgb, var(--p) 18%, transparent); }
    .hwr-task .n { font-size: 13.5px; font-weight: 700; line-height: 1.35; color: #111827; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .hwr-task .f { display: flex; flex-wrap: wrap; gap: 2px 10px; font-size: 11px; font-weight: 600; color: #6b7280; }
    .hwr-task .f span { display: inline-flex; align-items: center; gap: 3px; }
    .hwr-task .f svg { width: 11px; height: 11px; }
    .hwr-task.is-done { border-left-color: #4a7c2a; }
    .hwr-task.is-done .n { color: #9ca3af; text-decoration: line-through; }
    .hwr-task .ok { display: none; margin-left: 6px; padding: 2px 6px; border-radius: 999px; background: #e4f0d6; color: #3d6823; font-size: 9px; font-weight: 800; text-transform: uppercase; }
    .hwr-task.is-done .ok { display: inline-block; }
    .hwr-dots { display: flex; justify-content: center; gap: 5px; margin-top: 8px; }
    .hwr-dots i { width: 6px; height: 6px; border-radius: 999px; background: #c9dcb4; transition: width .3s var(--ease), background-color .3s var(--ease); }
    .hwr-dots i.on { width: 16px; background: #4a7c2a; }
    .hwr-wx { padding: 10px 11px 9px; border-radius: 14px; border: 1px solid #e5e7eb; background: #fff; }
    .hwr-wx .pl { font-size: 11px; font-weight: 700; color: #4b5563; }
    .hwr-wx .pl b { font-weight: 800; color: #1f2937; }
    .hwr-wx .row { display: grid; grid-template-columns: repeat(5, 1fr); gap: 4px; margin-top: 8px; text-align: center; }
    .hwr-wx .row span { display: flex; flex-direction: column; align-items: center; gap: 1px; padding: 5px 0; border-radius: 9px; font-size: 10px; font-weight: 700; color: #6b7280; }
    .hwr-wx .row span:first-child { background: #f0f7e8; color: #3d6823; }
    .hwr-wx .row em { font-style: normal; font-size: 20px; line-height: 1.2; }
    .hwr-wx .row b { font-size: 12px; font-weight: 800; color: #1f2937; }
    .hwr-wx .ad { margin-top: 8px; padding-top: 7px; border-top: 1px solid #f1f4ee; font-size: 10.5px; line-height: 1.5; color: #4b5563; }
    .hwr-open { display: flex; align-items: center; justify-content: center; height: 44px; border-radius: 12px; background: linear-gradient(135deg, #4a7c2a, #3d6823); color: #fff; font-size: 14px; font-weight: 700; }
    .hwr-post { border-radius: 16px; background: #fff; border: 1px solid #e5e7eb; overflow: hidden; }
    .hwr-post .who { display: flex; align-items: center; gap: 10px; padding: 12px 14px 8px; }
    .hwr-post .who > i { width: 40px; height: 40px; border-radius: 999px; display: grid; place-items: center; font-style: normal; font-size: 14px; font-weight: 800; color: #fff; background: hsl(var(--h) 45% 42%); }
    .hwr-post .who b { display: block; font-size: 14px; font-weight: 700; color: #111827; }
    .hwr-post .who small { display: block; font-size: 12px; color: #9ca3af; }
    .hwr-post p { padding: 0 14px 10px; font-size: 14px; line-height: 1.5; color: #1f2937; }
    .hwr-post .pic { height: 150px; background: #9bbf72 center / cover no-repeat; }
    .hwr-post .re { display: flex; gap: 16px; padding: 10px 14px; font-size: 13px; font-weight: 700; color: #6b7280; }
    .hwr-nav { flex: none; display: grid; grid-template-columns: repeat(4, 1fr); padding: 8px 6px 12px; background: #fff; border-top: 1px solid #e5e7eb; }
    .hwr-nav span { display: flex; flex-direction: column; align-items: center; gap: 3px; font-size: 11px; font-weight: 600; color: #6b7280; }
    .hwr-nav span.on { color: #3d6823; }
    .hwr-nav svg { width: 22px; height: 22px; }

    /* The notes around the phone: four at its sides on a wide screen, one at a time under it on a phone. */
    .hw-ping { position: absolute; z-index: 3; }
    .hw-ping-in { display: flex; align-items: center; gap: .6rem; padding: .62rem .85rem .62rem .62rem; border-radius: 1rem; background: rgb(255 255 255 / .97); color: #14210c;
        box-shadow: 0 22px 44px -22px rgb(0 0 0 / .75); text-align: left; white-space: nowrap; transition: box-shadow .4s var(--ease); }
    /* The note whose card has just come up on the phone. */
    .hw-ping.is-hot .hw-ping-in { box-shadow: 0 0 0 3px rgb(245 197 24 / .8), 0 22px 44px -22px rgb(0 0 0 / .75); }
    .hw-ping-in > span { width: 2.1rem; height: 2.1rem; border-radius: .75rem; display: grid; place-items: center; background: #eef6e5; font-size: 1.05rem; }
    .hw-ping b { display: block; font-size: .8rem; line-height: 1.2; }
    .hw-ping small { display: block; font-size: .7rem; color: #5b6b4c; }
    .hw-ping { opacity: 0; transform: translateY(10px) scale(.95); transition: opacity .6s var(--ease), transform .6s var(--ease); }
    @media (max-width: 1023.98px) {
        .hw-ping { left: 50%; top: 31rem; translate: -50% 0; }
        .hw.is-ready .hw-ping.is-on { opacity: 1; transform: none; transition-delay: .2s; }
    }
    @media (min-width: 1024px) {
        .hw-ping:nth-of-type(1) { right: calc(50% + 11rem); top: 3.5rem; }
        .hw-ping:nth-of-type(2) { right: calc(50% + 13rem); top: 18rem; }
        .hw-ping:nth-of-type(3) { left: calc(50% + 11.5rem); top: 7rem; }
        .hw-ping:nth-of-type(4) { left: calc(50% + 12.5rem); top: 21.5rem; }
        .hw.is-ready .hw-ping { opacity: 1; transform: none;
            transition: opacity .6s var(--ease) calc(1s + var(--k) * .18s), transform .6s var(--ease) calc(1s + var(--k) * .18s), scale .45s var(--ease); }
        .hw-ping.is-hot { scale: 1.06; }
        .hw.is-ready .hw-ping .hw-ping-in { animation: hwBob 6s ease-in-out infinite; animation-delay: calc(var(--k) * -1.4s); }
    }
    @keyframes hwBob { 50% { transform: translateY(-6px); } }

    /* ---------- the steps and the rail down them ---------- */
    .hw-steps { position: relative; max-width: 76rem; margin: 0 auto; padding: 0 var(--pad) 4rem; }
    .hw-drop { position: relative; height: 4.5rem; }
    .hw-drop svg { position: absolute; inset: 0; width: 100%; height: 100%; overflow: visible; }
    .hw-rail { position: absolute; top: 4.5rem; left: var(--rail-x); width: 2px; margin-left: -1px; height: 0; pointer-events: none; }
    .hw-rail-base { position: absolute; inset: 0; background: radial-gradient(circle, rgb(255 255 255 / .3) 1px, transparent 1.4px) center top / 2px 9px repeat-y; }
    .hw-rail-fill { position: absolute; inset: 0; border-radius: 2px; transform-origin: top; transform: scaleY(0);
        background: linear-gradient(180deg, var(--acc), var(--leaf) 12%, #6b9f3d); box-shadow: 0 0 14px rgb(168 204 126 / .55); }
    .hw-rail-tip { position: absolute; left: 50%; top: 0; width: .85rem; height: .85rem; margin: -.42rem 0 0 -.42rem; border-radius: 999px; background: var(--acc);
        box-shadow: 0 0 0 4px rgb(245 197 24 / .2), 0 0 20px 5px rgb(245 197 24 / .5); }
    .hw-line { fill: none; stroke: rgb(255 255 255 / .3); stroke-width: 2; stroke-dasharray: .002 .012; stroke-linecap: round; }
    .hw-line-flow { fill: none; stroke: var(--acc); stroke-width: 2.4; stroke-linecap: round; stroke-dasharray: .12 .88; animation: hwRun 2.2s linear infinite; }

    .hw-stage { position: relative; isolation: isolate; scroll-margin-top: 6rem; display: grid; grid-template-columns: minmax(0, 1fr); row-gap: .8rem;
        padding: 2.4rem 0 2.6rem var(--gut); }
    .hw-svg { position: absolute; left: 0; top: 0; z-index: -1; overflow: visible; pointer-events: none; }
    .hw-head { min-height: var(--hub); display: flex; flex-direction: column; justify-content: center; }
    .hw-step { display: flex; flex-wrap: wrap; align-items: center; gap: .15rem .5rem; font-size: .7rem; font-weight: 900; letter-spacing: .15em; text-transform: uppercase; color: var(--acc); }
    .hw-step span { color: var(--soft); }
    .hw-h2 { margin-top: .35rem; font-family: var(--font-heading); font-weight: 800; color: #fff; font-size: clamp(1.55rem, 3.4vw, 2.45rem); line-height: 1.08; letter-spacing: -.01em; }
    .hw-sub { margin-top: .5rem; color: var(--mute); font-size: .96rem; line-height: 1.6; }

    /* Anee at the step: her face in the middle of the field, the step's number on her shoulder. */
    .hw-hub { position: absolute; left: calc(var(--rail-x) - var(--pad) - var(--hub) / 2); top: 2.35rem; width: var(--hub); height: var(--hub); z-index: 2; }
    .hw-hub-core { position: absolute; inset: 0; overflow: hidden; border-radius: 999px; background: #1a2c12; border: 2px solid rgb(168 204 126 / .35);
        transition: border-color .6s var(--ease), box-shadow .6s var(--ease); }
    .hw-hub-core img { width: 100%; height: 100%; object-fit: cover; opacity: .45; filter: grayscale(1) brightness(.7); transform: scale(.92);
        transition: opacity .6s var(--ease), filter .6s var(--ease), transform .6s var(--ease); }
    .hw-stage.is-lit .hw-hub-core { border-color: var(--acc); box-shadow: 0 0 0 5px rgb(245 197 24 / .14), 0 0 36px 4px rgb(168 204 126 / .35); }
    .hw-stage.is-lit .hw-hub-core img { opacity: 1; filter: none; transform: none; }
    .hw-ring { position: absolute; inset: 0; border-radius: 999px; border: 1.5px solid var(--leaf); opacity: 0; pointer-events: none; }
    .hw-num { position: absolute; right: -.35rem; top: -.3rem; z-index: 1; width: 1.45rem; height: 1.45rem; border-radius: 999px; display: grid; place-items: center;
        background: var(--acc); color: #3b2f00; font-size: .72rem; font-weight: 900; box-shadow: 0 0 0 3px #0d1609; }

    .hw-say { position: relative; display: flex; gap: .7rem; align-items: flex-start; padding: .8rem .95rem; border-radius: 1.1rem;
        background: rgb(255 255 255 / .97); color: #1f2a17; box-shadow: 0 22px 46px -28px rgb(0 0 0 / .95); }
    .hw-say::before { content: ''; position: absolute; left: 1.1rem; top: -.4rem; width: .9rem; height: .9rem; background: inherit; transform: rotate(45deg); border-radius: .15rem; }
    .hw-say img { position: relative; flex: none; width: 2.2rem; height: 2.2rem; border-radius: 999px; object-fit: cover; }
    .hw-say small { display: block; margin-bottom: .1rem; font-size: .64rem; font-weight: 900; letter-spacing: .1em; text-transform: uppercase; color: #4a7c2a; }
    .hw-say p { position: relative; font-size: .9rem; line-height: 1.55; }

    /* A tool: a chip that opens. The float is on an inner layer, so the
       chip's own box (where its route ends) never moves. */
    .hw-item { width: 100%; max-width: 30rem; }
    /* On a phone the field is no box at all: its hub and tools sit in the step's own grid. */
    .hw-field { display: contents; }
    .hw-float { will-change: transform; }
    .hw-chip { position: relative; display: flex; align-items: center; gap: .75rem; width: 100%; padding: .7rem .75rem .7rem .7rem; border-radius: 1.1rem; text-align: left;
        color: var(--ink); background: rgb(25 40 18 / .94); border: 1px solid rgb(255 255 255 / .12); box-shadow: 0 18px 40px -26px rgb(0 0 0 / .9);
        transition: background .28s var(--ease), border-color .28s var(--ease), border-radius .28s var(--ease), transform .28s var(--ease); cursor: pointer; }
    .hw-chip:hover { border-color: rgb(168 204 126 / .55); background: rgb(32 50 23 / .97); }
    .hw-chip:focus-visible { outline: 2px solid var(--acc); outline-offset: 3px; }
    .hw-ico { flex: none; width: 2.6rem; height: 2.6rem; border-radius: .85rem; display: grid; place-items: center; background: #fff; overflow: hidden; }
    .hw-ico img { width: 1.6rem; height: 1.6rem; object-fit: contain; }
    .hw-ico.is-face img { width: 100%; height: 100%; object-fit: cover; }
    .hw-txt { flex: 1; min-width: 0; }
    .hw-txt b { display: block; font-family: var(--font-heading); font-size: .95rem; line-height: 1.2; color: #fff; }
    .hw-txt small { display: block; margin-top: .15rem; font-size: .78rem; line-height: 1.35; color: var(--mute); }
    .hw-by { position: absolute; top: -.6rem; right: .8rem; display: inline-flex; align-items: center; gap: .25rem; padding: .12rem .45rem .12rem .14rem; border-radius: 999px;
        background: var(--acc); color: #3b2f00; font-size: .6rem; font-weight: 900; letter-spacing: .05em; text-transform: uppercase; box-shadow: 0 6px 14px -6px rgb(245 197 24 / .8); }
    .hw-by img { width: .95rem; height: .95rem; border-radius: 999px; object-fit: cover; }
    .hw-plus { position: relative; flex: none; width: 1.65rem; height: 1.65rem; border-radius: 999px; background: rgb(255 255 255 / .08); transition: background .28s var(--ease); }
    .hw-plus::before, .hw-plus::after { content: ''; position: absolute; left: 50%; top: 50%; width: .62rem; height: 2px; margin: -1px 0 0 -.31rem; border-radius: 2px;
        background: var(--leaf); transition: transform .32s var(--ease), background .28s var(--ease); }
    .hw-plus::after { transform: rotate(90deg); }
    .hw-item.is-open .hw-chip { border-color: rgb(245 197 24 / .6); border-bottom-left-radius: .45rem; border-bottom-right-radius: .45rem; }
    .hw-item.is-open .hw-plus { background: rgb(245 197 24 / .18); }
    .hw-item.is-open .hw-plus::before, .hw-item.is-open .hw-plus::after { background: var(--acc); }
    .hw-item.is-open .hw-plus::after { transform: rotate(0); }
    .hw-more { display: grid; grid-template-rows: 0fr; transition: grid-template-rows .38s var(--ease); }
    .hw-more > div { min-height: 0; overflow: hidden; }
    .hw-item.is-open .hw-more { grid-template-rows: 1fr; }
    .hw-more-in { margin-top: .3rem; padding: .9rem 1rem 1rem; border-radius: .45rem .45rem 1.1rem 1.1rem; background: rgb(25 40 18 / .96); border: 1px solid rgb(255 255 255 / .1);
        opacity: 0; transform: translateY(-6px); transition: opacity .3s var(--ease), transform .38s var(--ease); }
    .hw-item.is-open .hw-more-in { opacity: 1; transform: none; transition-delay: .06s; }
    .hw-more-in p { font-size: .87rem; line-height: 1.6; color: #d9e5cd; }
    .hw-gets { margin-top: .65rem; display: grid; gap: .35rem; }
    .hw-gets li { display: flex; gap: .5rem; font-size: .83rem; line-height: 1.45; color: var(--ink); }
    .hw-gets svg { flex: none; width: 1rem; height: 1rem; margin-top: .12rem; color: var(--leaf); }
    .hw-link { margin-top: .8rem; display: inline-flex; align-items: center; gap: .35rem; font-size: .83rem; font-weight: 800; color: var(--acc); }
    .hw-link svg { width: .95rem; height: .95rem; transition: transform .28s var(--ease); }
    .hw-link:hover svg { transform: translateX(3px); }

    /* The routes and the field of dots (drawn by the script). */
    .hw-arc { fill: none; stroke: rgb(255 255 255 / .2); stroke-width: 1.3; stroke-dasharray: 1; stroke-dashoffset: 1; transition: stroke .4s var(--ease); }
    .hw-arc.is-on { stroke: rgb(245 197 24 / .65); }
    .hw-flow { fill: none; stroke: var(--leaf); stroke-width: 2.3; stroke-linecap: round; stroke-dasharray: .05 .95; opacity: 0; transition: stroke .4s var(--ease); }
    .hw-flow.is-on { stroke: var(--acc); }
    .hw-dest { fill: var(--leaf); transform: scale(0); transform-box: fill-box; transform-origin: center; transition: fill .4s var(--ease); }
    .hw-dest.is-on { fill: var(--acc); }
    /* A ring that flares where a streak lands, once, as the burst arrives. */
    .hw-spark { fill: none; stroke: var(--acc); stroke-width: 1.6; opacity: 0; transform-box: fill-box; transform-origin: center; }
    .hw .is-in .hw-spark { animation: hwSpark 1s var(--ease) forwards; animation-delay: calc(1.15s + var(--i) * .05s); }
    @keyframes hwSpark { 0% { opacity: .95; transform: scale(.2); } 100% { opacity: 0; transform: scale(2.6); } }
    .hw-dot { fill: #fff; opacity: 0; transition: opacity .8s ease; transition-delay: var(--d); }
    .hw-dot.k { fill: var(--leaf); }
    .hw-dot.t { fill: var(--acc); }
    .hw-dot.g { fill: var(--leaf); transform: scale(.15); transform-box: fill-box; transform-origin: center; transition: opacity .7s ease, transform 1s var(--ease); transition-delay: var(--d); }
    .hw-dot.h { fill: var(--leaf); transition: opacity .7s ease, fill 1.4s ease; transition-delay: var(--d), calc(var(--d) + .9s); }
    .hw-dot.p { fill: #f59e0b; }

    /* Entrances: once, when a step first comes into view. */
    .hw.is-ready .hw-head, .hw.is-ready .hw-say { opacity: 0; transform: translateY(14px); transition: opacity .7s var(--ease), transform .7s var(--ease); }
    .hw.is-ready .hw-say { transition-delay: .25s; }
    .hw.is-ready .hw-hub { opacity: 0; transform: scale(.6); transition: opacity .6s var(--ease), transform .7s var(--ease); transition-delay: .1s; }
    .hw.is-ready .hw-item { opacity: 0; transform: translateY(16px) scale(.96); transition: opacity .6s var(--ease), transform .7s var(--ease); transition-delay: calc(.55s + var(--i) * .07s); }
    .hw.is-ready .hw-w { opacity: .08; transition: opacity .4s ease; transition-delay: calc(.6s + var(--k) * 45ms); }
    .hw-stage.is-in .hw-head, .hw-stage.is-in .hw-say, .hw-stage.is-in .hw-hub, .hw-stage.is-in .hw-item { opacity: 1; transform: none; }
    .hw-stage.is-in .hw-w { opacity: 1; }
    .hw-stage.is-in .hw-dot { opacity: var(--o); }
    .hw-stage.is-in .hw-dot.g { transform: none; }
    .hw-stage.is-in .hw-dot.h { fill: var(--acc); }
    .hw .is-in .hw-arc { animation: hwDraw 1.1s var(--ease) forwards; animation-delay: calc(.3s + var(--i) * .07s); }
    .hw .is-in .hw-dest { animation: hwPop .45s var(--ease) forwards; animation-delay: calc(1s + var(--i) * .07s); }
    .hw .is-in .hw-flow { animation: hwFade .6s ease forwards, hwRun 2.8s linear infinite; animation-delay: calc(1.25s + var(--i) * .07s), calc(1.25s + var(--i) * .31s); }
    @keyframes hwDraw { to { stroke-dashoffset: 0; } }
    @keyframes hwPop { to { transform: scale(1); } }
    @keyframes hwFade { to { opacity: .9; } }
    @keyframes hwRun { from { stroke-dashoffset: 0; } to { stroke-dashoffset: -1; } }
    .hw-stage.is-in .hw-float { animation: hwBob 6.5s ease-in-out infinite; animation-delay: calc(var(--i) * -.9s); }
    .hw-stage.is-in.is-lit .hw-ring { animation: hwRing 2.8s ease-out infinite; }
    .hw-stage.is-in.is-lit .hw-ring + .hw-ring { animation-delay: 1.4s; }
    .hw-stage.is-in .hw-dot.p { animation: hwPest 3.4s ease-in-out infinite; animation-delay: var(--pd, 0s); }
    @keyframes hwRing { 0% { opacity: .75; transform: scale(.95); } 75%, 100% { opacity: 0; transform: scale(1.85); } }
    @keyframes hwPest { 0%, 100% { fill: #f59e0b; opacity: .95; } 40% { fill: #ef4444; opacity: .55; } 70% { fill: #a8cc7e; opacity: .9; } }
    /* What keeps moving does so only while its step is on screen (after the
       shorthands above, and one class stronger, or they would win). */
    .hw-stage.is-in:not(.is-live) .hw-flow, .hw-stage.is-in:not(.is-live) .hw-float,
    .hw-stage.is-in:not(.is-live) .hw-ring, .hw-stage.is-in:not(.is-live) .hw-dot.p { animation-play-state: paused; }

    /* ---------- between steps 2 and 3: asking Anee in her chat ---------- */
    .hw-band { position: relative; isolation: isolate; scroll-margin-top: 6rem; display: grid; grid-template-columns: minmax(0, 1fr); row-gap: 1.3rem;
        padding: 2.6rem 0 3rem var(--gut); }
    .hw-band-svg { position: absolute; left: 0; top: 0; z-index: -1; width: 100%; height: 100%; overflow: visible; pointer-events: none; }
    .hw-band-phone { display: flex; justify-content: center; }
    .hw-phone2 { position: relative; width: 15.5rem; height: 30rem; padding: .5rem; border-radius: 2.5rem; background: linear-gradient(160deg, #262d22, #0a0d08 70%);
        box-shadow: 0 50px 90px -40px rgb(0 0 0 / .9), 0 0 0 1px rgb(255 255 255 / .1) inset, 0 0 0 7px rgb(168 204 126 / .07), 0 0 60px -10px rgb(168 204 126 / .35); }
    .hw-scr2 { position: relative; height: 100%; overflow: hidden; border-radius: 2.05rem; background: #f6f7f4; color: #1f2937; text-align: left; }
    .hw-band-notes { display: flex; flex-wrap: wrap; justify-content: center; gap: .55rem; }
    .hw-note { display: flex; align-items: center; gap: .55rem; padding: .55rem .85rem .55rem .55rem; border-radius: 1rem; background: rgb(255 255 255 / .97); color: #14210c;
        box-shadow: 0 22px 44px -22px rgb(0 0 0 / .75); opacity: .5; transform: scale(.97);
        transition: opacity .4s var(--ease), transform .4s var(--ease), box-shadow .4s var(--ease); }
    .hw-note > span { width: 2rem; height: 2rem; border-radius: .7rem; display: grid; place-items: center; background: #eef6e5; font-size: 1rem; }
    .hw-note b { display: block; font-size: .8rem; line-height: 1.2; }
    .hw-note small { display: block; font-size: .7rem; color: #5b6b4c; }
    .hw-note.is-read { opacity: 1; transform: none; }
    .hw-note.is-hot { opacity: 1; transform: scale(1.05); box-shadow: 0 0 0 3px rgb(245 197 24 / .8), 0 22px 44px -22px rgb(0 0 0 / .75); }
    /* The chat, as the app draws it (inside the 390px .hwr). */
    .s-chat { background: #f6f7f4; }
    .hwc-top .back { flex: none; width: 24px; height: 24px; color: #4b5563; }
    .hwc-top .t { min-width: 0; }
    .hwc-top .t b { max-width: 190px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .hwc-top .kebab { display: grid; gap: 3px; padding: 0 8px; }
    .hwc-top .kebab i { width: 4px; height: 4px; border-radius: 999px; background: #4b5563; }
    .hwc-body { position: relative; flex: 1; min-height: 0; overflow: hidden; }
    .hwc-list { display: flex; flex-direction: column; padding: 16px; transition: transform .7s var(--ease); }
    .hwc-day { display: flex; justify-content: center; margin-bottom: 14px; }
    .hwc-day span { padding: 3px 12px; border-radius: 999px; background: #e9ede4; color: #6b7280; font-size: 11px; font-weight: 700; }
    .hwc-msg { display: none; gap: 10px; align-items: flex-end; margin-bottom: 16px; opacity: 0; transform: translateY(10px); transition: opacity .45s var(--ease), transform .5s var(--ease); }
    .hwc-msg.is-shown { display: flex; }
    .hwc-msg.is-in { opacity: 1; transform: none; }
    .hwc-msg.me { flex-direction: row-reverse; }
    .hwc-face { flex: none; width: 38px; height: 38px; border-radius: 999px; overflow: hidden; display: grid; place-items: center; background: #4a7c2a; color: #fff; font-size: 12px; font-weight: 800; }
    .hwc-msg:not(.me) .hwc-face { background: #f3f8ec; box-shadow: 0 0 0 2px #fff, 0 0 0 3px #c9e0ad; }
    .hwc-face img { width: 100%; height: 100%; object-fit: cover; }
    .hwc-b { max-width: 82%; padding: 10px 14px; font-size: 14.5px; line-height: 1.55; color: #1f2937; background: #fff; border: 1px solid #f3f4f6;
        border-radius: 18px 18px 18px 6px; box-shadow: 0 1px 2px rgb(26 26 26 / .06), 0 3px 10px -4px rgb(26 26 26 / .08); }
    .hwc-msg.me .hwc-b { color: #fff; background: linear-gradient(135deg, #4a7c2a, #3d6823); border-color: transparent; border-radius: 18px 18px 6px 18px; box-shadow: 0 3px 12px -4px rgb(45 80 22 / .45); }
    .hwc-pic { position: relative; display: block; overflow: hidden; margin-bottom: 7px; border-radius: 10px; }
    .hwc-pic img { display: block; width: 100%; height: 190px; object-fit: cover; object-position: 50% 62%; }
    /* While Anee studies the photo, a light sweeps down it. */
    .hwc-pic::after { content: ''; position: absolute; left: 0; right: 0; top: -30%; height: 30%; opacity: 0;
        background: linear-gradient(180deg, rgb(245 197 24 / 0), rgb(245 197 24 / .55) 80%, rgb(255 255 255 / .9)); box-shadow: 0 0 18px rgb(245 197 24 / .6); }
    .hwc-msg.is-scanning .hwc-pic::after { opacity: 1; animation: hwcScan 1.2s ease-in-out infinite; }
    .hwc-msg.is-scanning .hwc-pic { box-shadow: 0 0 0 2px #f5c518; }
    @keyframes hwcScan { from { top: -30%; } to { top: 100%; } }
    .hwc-b time { display: block; margin-top: 4px; text-align: right; font-size: 10.5px; font-weight: 600; opacity: .55; }
    .hwc-b p { margin: 0 0 6px; }
    .hwc-b ul { list-style: disc; margin: 6px 0; padding-left: 20px; }
    .hwc-b li { margin: 3px 0; }
    .hwc-b .cost { display: inline-flex; align-items: center; gap: 5px; margin-top: 8px; padding: 2px 9px; border-radius: 999px; font-size: 11px; font-weight: 800; color: #8a6100; background: rgb(245 197 24 / .15); }
    .hwc-b .cost::before { content: ''; width: 6px; height: 6px; border-radius: 999px; background: #f5c518; }
    /* Anee's three steps before she answers: waiting, working, done. */
    .hwc-steps { display: grid; gap: 8px; margin: 2px 0; padding: 0; list-style: none; font-size: 13.5px; color: #9ca3af; }
    .hwc-steps li { display: flex; align-items: center; gap: 9px; transition: color .3s var(--ease); }
    .hwc-steps li i { position: relative; flex: none; width: 17px; height: 17px; border-radius: 999px; border: 2px solid #d1d5db;
        transition: border-color .3s var(--ease), background-color .3s var(--ease); }
    .hwc-steps li.on { color: #1f2937; font-weight: 700; }
    .hwc-steps li.on i { border-color: #d7e8c3; border-top-color: #4a7c2a; animation: hwSpin .8s linear infinite; }
    .hwc-steps li.done { color: #3d6823; }
    .hwc-steps li.done i { border-color: #4a7c2a; background: #4a7c2a; }
    .hwc-steps li.done i::after { content: ''; position: absolute; left: 4px; top: 1px; width: 4px; height: 8px; border: solid #fff; border-width: 0 2px 2px 0; transform: rotate(45deg); }
    .hwc-comp { flex: none; margin: 8px 14px 4px; padding: 6px; border-radius: 26px; background: #fff; box-shadow: 0 6px 20px -10px rgb(26 26 26 / .3), 0 0 0 1px #eef0ea; }
    .hwc-shot { display: none; position: relative; width: 56px; height: 56px; margin: 4px 0 6px 6px; }
    .hwc-shot img { width: 100%; height: 100%; border-radius: 10px; object-fit: cover; }
    .hwc-shot i { position: absolute; top: -6px; right: -6px; width: 20px; height: 20px; border-radius: 999px; display: grid; place-items: center; background: #374151; color: #fff; font-size: 12px; font-style: normal; }
    .hwc-comp.has-shot .hwc-shot { display: block; animation: hwrUp .4s var(--ease) both; }
    .hwc-row { display: flex; align-items: center; gap: 8px; }
    .hwc-row .cam { flex: none; width: 38px; height: 38px; border-radius: 999px; display: grid; place-items: center; background: #eef6e5; color: #3d6823; }
    .hwc-row .cam svg { width: 20px; height: 20px; }
    .hwc-row .in { flex: 1; min-width: 0; max-height: 4.1em; overflow: hidden; font-size: 15px; line-height: 1.35; color: #1f2937; }
    .hwc-row .in .ph { color: #9ca3af; }
    .hwc-row .in.has .ph { display: none; }
    .hwc-row .in em { display: inline-block; width: 2px; height: 18px; margin-left: 1px; vertical-align: -3px; background: #4a7c2a; opacity: 0; }
    .hwc-row .in.is-focus em { opacity: 1; animation: hwCaret 1s steps(1) infinite; }
    .hwc-row .go { flex: none; width: 40px; height: 40px; border-radius: 999px; display: grid; place-items: center; color: #fff; background: linear-gradient(135deg, #4a7c2a, #3d6823);
        transition: transform .2s var(--ease); }
    .hwc-row .go svg { width: 18px; height: 18px; }
    .hwc-row .go.is-press { transform: scale(.88); }
    .hwc-credits { flex: none; padding: 2px 0 12px; text-align: center; font-size: 11px; color: #6b7280; }
    .hwc-credits i { display: inline-block; width: 8px; height: 8px; border-radius: 999px; background: #f5c518; }
    @media (min-width: 1024px) {
        .hw-band { grid-template-columns: minmax(0, 1fr) 18rem minmax(0, 1fr); column-gap: 2.5rem; align-items: center; padding: 4.5rem 0; }
        .hw-band-copy { grid-column: 1; grid-row: 1; justify-self: end; max-width: 27rem; text-align: right; }
        .hw-band-copy .hw-step { justify-content: flex-end; }
        .hw-band-phone { grid-column: 2; grid-row: 1; }
        .hw-phone2 { width: 16.5rem; height: 32rem; }
        .hw-band-notes { grid-column: 3; grid-row: 1; justify-self: start; flex-direction: column; align-items: flex-start; gap: 2.2rem; }
        .hw-note:nth-child(2) { margin-left: 2.4rem; }
    }

    /* The end of the rail: the season comes round again. */
    .hw-end { position: relative; padding: 1rem 0 0 var(--gut); }
    .hw-loop { position: absolute; left: calc(var(--rail-x) - var(--pad) - var(--hub) / 2); top: .6rem; width: var(--hub); height: var(--hub); border-radius: 999px; display: grid; place-items: center;
        background: #1a2c12; border: 2px solid var(--acc); color: var(--acc); box-shadow: 0 0 30px rgb(245 197 24 / .3); }
    .hw-loop svg { width: 52%; height: 52%; animation: hwSpin 6s linear infinite; }
    @keyframes hwSpin { to { transform: rotate(-360deg); } }
    .hw-end .hw-h2 { margin-top: .4rem; }
    .hw-end > p:not(.hw-step) { margin-top: .6rem; max-width: 38rem; color: var(--mute); line-height: 1.65; }
    .hw-end .hw-btns { justify-content: flex-start; }

    @media (min-width: 1024px) {
        .hw-stage { grid-template-columns: minmax(0, 1fr) 12rem minmax(0, 1fr); column-gap: 1.5rem; row-gap: 1.15rem; padding: 4.5rem 0 4.5rem; }
        .hw-head { grid-column: 1; grid-row: 1; justify-self: end; max-width: 27rem; min-height: 0; text-align: right; padding-bottom: 1.5rem; }
        .hw-step { justify-content: flex-end; }
        .hw-say { grid-column: 3; grid-row: 1; justify-self: start; align-self: center; max-width: 25rem; margin-bottom: 1.5rem; }
        .hw-say::before { left: -.4rem; top: 1.2rem; }
        /* The field: Anee in the middle and her tools blown out around her
           (placed by the script); until it has, a tidy wrap. */
        .hw-field { grid-column: 1 / -1; grid-row: 2; position: relative; display: flex; flex-wrap: wrap; align-items: center; justify-content: center;
            gap: 1rem; min-height: var(--fh); }
        .hw-field .hw-hub { position: relative; left: auto; top: auto; flex: none; }
        .hw-field .hw-item { width: auto; max-width: 17.5rem; }
        .hw-txt small { display: none; }
        .hw-field.is-scatter { display: block; height: var(--fh); }
        .hw-field.is-scatter .hw-hub { position: absolute; left: 50%; top: 50%; margin: calc(var(--hub) / -2) 0 0 calc(var(--hub) / -2); }
        .hw-field.is-scatter .hw-item { position: absolute; left: 0; top: 0; }
        .hw-field.is-scatter .hw-item.is-open { z-index: 8; }
        /* What a tool does opens as a card beside it, over whatever is under it. */
        .hw-field.is-scatter .hw-more { position: absolute; left: 0; top: calc(100% + .45rem); width: 22rem; }
        .hw-field.is-scatter .hw-item.up .hw-more { top: auto; bottom: calc(100% + .45rem); }
        .hw-field.is-scatter .hw-item.end .hw-more { left: auto; right: 0; }
        .hw-field.is-scatter .hw-item.is-open .hw-chip { border-radius: 1.1rem; }
        .hw-field.is-scatter .hw-more-in { margin-top: 0; border-radius: 1.1rem; border-color: rgb(245 197 24 / .35); box-shadow: 0 30px 60px -24px rgb(0 0 0 / .9); }
        /* The entrance: every tool bursts out of Anee to its place. */
        .hw.is-ready .hw-field.is-scatter .hw-item { transform: translate(var(--fx, 0px), var(--fy, 0px)) scale(.3);
            transition: opacity .5s var(--ease), transform 1s var(--ease); transition-delay: calc(.35s + var(--i) * .05s); }
        .hw-stage.is-in .hw-field.is-scatter .hw-item { transform: none; }
        .hw-num { width: 2.1rem; height: 2.1rem; font-size: .95rem; right: .1rem; top: .1rem; }
        .hw-end { padding: 3rem 0 0; text-align: center; }
        .hw-end > p:not(.hw-step) { margin-left: auto; margin-right: auto; }
        .hw-end .hw-step { justify-content: center; }
        .hw-end .hw-btns { justify-content: center; }
        .hw-loop { position: relative; left: auto; top: auto; margin: 0 auto 1rem; width: 4rem; height: 4rem; }
    }

    @media (prefers-reduced-motion: reduce) {
        .hw *, .hw *::before, .hw *::after { animation: none !important; transition: none !important; }
        .hw .hw-head, .hw .hw-say, .hw .hw-hub, .hw .hw-item, .hw .hw-w, .hw .hw-ping, .hw .hw-copy > * { opacity: 1 !important; transform: none !important; }
        .hw .hw-arc { stroke-dashoffset: 0; }
        .hw .hw-dest { transform: none; }
        .hw .hw-spark { display: none; }
        .hw .hw-dot { opacity: var(--o); }
        .hw .hw-dot.g { transform: none; }
        .hw .hw-flow, .hw .hw-line-flow { display: none; }
    }
</style>
@endpush
@endonce

<div class="hw" data-hw data-hw-mode="{{ $hwMode }}">
    {{-- The top: Anee on the phone, the start of every step below. --}}
    <section class="hw-hero">
        <div class="hw-copy">
            <p class="hw-kick">How it works</p>
            <{{ $hwTop }} class="hw-h1">From the first plan to the last sack, <em>Anee is with you</em></{{ $hwTop }}>
            <p class="hw-lede">anee.io follows your season the way a farm lives it: plan, plant, grow, protect, harvest, then look back. Every step has its tools, and Anee, the AI farm technician, works beside you at each one.</p>
            <div class="hw-btns">
                @if ($hwMode === 'site')
                    <a href="{{ route('signup') }}" class="btn btn-accent btn-lg">Start free</a>
                @endif
                <button type="button" class="hw-ghost" data-hw-go="plan">See the six steps
                    <svg fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14m0 0l-6-6m6 6l6-6"/></svg>
                </button>
            </div>
        </div>
        <div class="hw-show" aria-hidden="true">
            <svg class="hw-show-svg"></svg>
            @foreach ($hwPings as $k => [$emo, $t, $s])
                <div class="hw-ping" style="--k: {{ $k }}"><div class="hw-ping-in"><span>{{ $emo }}</span><div><b>{{ $t }}</b><small>{{ $s }}</small></div></div></div>
            @endforeach
            <div class="hw-phone">
                <div class="hw-scr"><div class="hwr">
                    {{-- Scene one: the login page. --}}
                    <div class="hw-scene s-login is-on">
                        <div class="hwr-pub"><img src="{{ asset('images/logo.png') }}?v=anee" alt=""><span class="hwr-burger"><i></i><i></i><i></i></span></div>
                        <div class="hwr-login">
                            <h3>Welcome back</h3>
                            <p>Log in to manage your farm.</p>
                            <div class="hwr-card">
                                <div class="hwr-lab">Email address</div>
                                <span class="hwr-in" data-f="email"><span class="ph">you@example.com</span><span class="v"></span><em></em></span>
                                <div class="hwr-lab">Password <a>Forgot password?</a></div>
                                <span class="hwr-in" data-f="pass"><span class="ph">••••••••</span><span class="v"></span><em></em></span>
                                <span class="hwr-keep"><i>✓</i>Keep me logged in</span>
                                <span class="hwr-btn"><i></i><b>Log In</b></span>
                            </div>
                            <p class="hwr-sign">No account yet? <b>Sign up free</b></p>
                        </div>
                    </div>
                    {{-- Scene two: the dashboard. --}}
                    <div class="hw-scene s-dash">
                        <div class="hwr-top">
                            <img src="{{ asset('images/logo-mark.png') }}?v=anee" alt="">
                            <div><b>Dashboard</b><small>Your farm at a glance</small></div>
                            <span class="r">
                                <span class="hwr-help">?</span>
                                <span class="hwr-bell"><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.4-1.4A2 2 0 0118 14.2V11a6 6 0 00-4-5.7V5a2 2 0 10-4 0v.3A6 6 0 006 11v3.2c0 .5-.2 1-.6 1.4L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg><em>1</em></span>
                                <span class="hwr-av">{{ $hwPhone['initials'] }}</span>
                            </span>
                        </div>
                        <div class="hwr-view"><div class="hwr-list">
                            <section class="hwr-hero" data-s="hero" style="--n: 0">
                                <span class="hwr-sky"><svg viewBox="0 0 56 56" aria-hidden="true"><defs><linearGradient id="hwSkyG" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#bfe0ff"/><stop offset="1" stop-color="#fff3c4"/></linearGradient></defs><rect width="56" height="56" fill="url(#hwSkyG)"/><g class="hw-sun"><circle cx="28" cy="30" r="9" fill="#fbbf24"/><g stroke="#fbbf24" stroke-width="2.4" stroke-linecap="round"><path d="M28 14v4M28 42v4M12 30h4M40 30h4M17 19l3 3M36 38l3 3M17 41l3-3M36 22l3-3"/></g></g><path d="M0 44c10-6 22-7 32-3s18 3 24 0v15H0z" fill="#86b556"/><path d="M0 48c12-3 24-3 34 0s16 2 22 0v8H0z" fill="#6b9f3d"/></svg></span>
                                <div><b>{{ $hwPhone['hello'] }}, {{ $hwPhone['name'] }}</b><p>Today is {{ now(config('app.timezone'))->format('F jS, Y') }}. You have 1 active cropping schedule.</p></div>
                                <div class="hwr-chips"><span class="rank">🌱 Lv 3 · New Member</span><span>{{ $hwPhone['daysLeft'] }} days left</span></div>
                            </section>
                            <div class="hwr-stats" style="--n: 1">
                                <div class="lead"><b>1</b><i>Schedule</i></div>
                                <div><b class="w">{{ $hwPhone['plan'] }}</b><i>Active plan</i></div>
                                <div><b>{{ $hwPhone['daysLeft'] }}</b><i>Days left</i></div>
                            </div>
                            <section class="hwr-tip" data-s="tip" data-ping="2" style="--n: 2">
                                <div class="h"><span><img src="{{ asset('images/idea.png') }}" alt=""></span><div><small>Tip of the day</small><b>{{ $hwPhone['tipTitle'] }}</b></div></div>
                                <p>{{ $hwPhone['tip'] }}</p>
                            </section>
                            <div class="hwr-h" style="--n: 3"><span>📅 My Cropping Schedules</span><a>View all</a></div>
                            <section class="hwr-sched" data-s="tasks" data-ping="0" style="--n: 4">
                                <div class="hwr-cover"><span>{{ $hwPhone['crops'] }}</span><b>{{ $hwPhone['season'] }}</b><svg fill="none" stroke="currentColor" stroke-width="2.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg></div>
                                <div class="hwr-sb">
                                    <div class="hwr-day">
                                        <div class="hwr-dh">
                                            <span class="hwr-when"><b>Today</b><i>{{ now(config('app.timezone'))->format('M j') }}</i></span>
                                            <span class="hwr-dt"><b>{{ count($hwPhone['tasks']) }} tasks</b><i>due today</i></span>
                                        </div>
                                        <div class="hwr-rail"><div class="hwr-track">
                                            @foreach ($hwPhone['tasks'] as [$ty, $pr, $tn, $tl, $tw, $th])
                                                <div class="hwr-task" style="--p: {{ ['high' => '#f46a6a', 'medium' => '#f1b44c', 'low' => '#94a3b8'][$pr] }}">
                                                    <span class="t"><span class="ty">{{ $ty }}</span><span class="ok">Done</span><span class="pr">{{ ucfirst($pr) }}</span></span>
                                                    <span class="n">{{ $tn }}</span>
                                                    <span class="f">
                                                        <span><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 20l-5-2V6l5 2m0 12l6-2m-6 2V8m6 10l5 2V8l-5-2m0 12V6M9 8l6-2"/></svg>{{ $tl }}</span>
                                                        <span><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-1a4 4 0 00-4-4h-1M9 11a4 4 0 100-8 4 4 0 000 8zm8 0a3 3 0 100-6M2 20v-1a5 5 0 015-5h4a5 5 0 015 5v1H2z"/></svg>{{ $tw }}</span>
                                                        @if ($th)<span><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>{{ $th }}</span>@endif
                                                    </span>
                                                </div>
                                            @endforeach
                                        </div></div>
                                        <div class="hwr-dots">@foreach ($hwPhone['tasks'] as $t)<i class="{{ $loop->first ? 'on' : '' }}"></i>@endforeach</div>
                                    </div>
                                    <div class="hwr-wx" data-s="wx" data-ping="1">
                                        <div class="pl">📍 <b>{{ $hwPhone['place'] }}</b> · this morning</div>
                                        <div class="row">@foreach ($hwPhone['days'] as [$dn, $de, $dh, $dl])<span>{{ $dn }}<em>{{ $de }}</em><b>{{ $dh }}°</b>{{ $dl }}°</span>@endforeach</div>
                                        <div class="ad">🌧️ {{ $hwPhone['advice'] }}</div>
                                    </div>
                                    <span class="hwr-open">Open schedule</span>
                                </div>
                            </section>
                            <div class="hwr-h" style="--n: 5"><span>📰 News Feed</span><a>See more</a></div>
                            @php [$pn, $pi, $phue, $pt, $ptext, $plikes, $pcom] = $hwPhone['post']; @endphp
                            <section class="hwr-post" data-s="post" data-ping="3" style="--n: 6">
                                <div class="who"><i style="--h: {{ $phue }}">{{ $pi }}</i><div><b>{{ $pn }}</b><small>{{ $pt }} · near you</small></div></div>
                                <p>{{ $ptext }}</p>
                                <div class="pic" style="background-image: url('{{ asset('images/site/corn-rows.jpg') }}')"></div>
                                <div class="re"><span>👍 {{ $plikes }}</span><span>💬 {{ $pcom }}</span><span>↗ Share</span></div>
                            </section>
                        </div></div>
                        <div class="hwr-nav">
                            <span class="on"><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 11l9-8 9 8M5 10v10h5v-6h4v6h5V10"/></svg>Home</span>
                            <span><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="16" rx="2"/><path stroke-linecap="round" d="M8 3v4M16 3v4M3 10h18M8 14h2M14 14h2M8 17h2"/></svg>Schedules</span>
                            <span><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-1a4 4 0 00-4-4h-1M9 11a4 4 0 100-8 4 4 0 000 8zm8 0a3 3 0 100-6M2 20v-1a5 5 0 015-5h4a5 5 0 015 5v1H2z"/></svg>Community</span>
                            <span><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 9l1.5-5h13L20 9M4 9h16M4 9v10a1 1 0 001 1h14a1 1 0 001-1V9M9 20v-6h6v6"/></svg>Shop</span>
                        </div>
                    </div>
                    <span class="hw-tap"></span>
                </div></div>
            </div>
        </div>
        <p class="sr-only">A phone shows someone logging in to anee.io, then the dashboard as it looks inside the app: the morning greeting, the tip of the day, today's activities on a season with the weather for the farm, and a post in the news feed.</p>
    </section>

    <div class="hw-steps">
        <div class="hw-drop" aria-hidden="true"><svg></svg></div>
        <div class="hw-rail" aria-hidden="true"><i class="hw-rail-base"></i><i class="hw-rail-fill"></i><i class="hw-rail-tip"></i></div>

        @foreach ($hwStages as $n => $st)
            <section class="hw-stage" id="hw-step-{{ $st['key'] }}" data-stage="{{ $st['key'] }}" data-pattern="{{ $st['pattern'] }}" aria-labelledby="hw-h-{{ $st['key'] }}">
                <svg class="hw-svg" aria-hidden="true"></svg>
                <header class="hw-head">
                    <p class="hw-step">Step {{ $n + 1 }} <span>{{ $st['when'] }}</span></p>
                    <{{ $hwHead }} class="hw-h2" id="hw-h-{{ $st['key'] }}">{{ $st['title'] }}</{{ $hwHead }}>
                    <p class="hw-sub">{{ $st['lede'] }}</p>
                </header>
                @if (! empty($st['say']))
                <div class="hw-say">
                    <img src="{{ asset('images/anee/avatar-160.jpg') }}" alt="" loading="lazy">
                    <div><small>Anee</small><p>{!! $hwWords($st['say']) !!}</p></div>
                </div>
                @endif
                {{-- Anee and her tools. On a desk the script scatters the tools
                     around her (.is-scatter); a phone lists them down the rail. --}}
                <div class="hw-field" style="--fh: {{ 26 + count($st['items']) * 1.8 }}rem">
                <div class="hw-hub" aria-hidden="true">
                    <i class="hw-ring"></i><i class="hw-ring"></i>
                    <span class="hw-hub-core"><img src="{{ asset('images/anee/emoji/' . $st['face'] . '.png') }}" alt="" loading="lazy"></span>
                    <span class="hw-num">{{ $n + 1 }}</span>
                </div>
                @foreach ($st['items'] as $i => $it)
                    @php
                        $link = $hwLink($it);
                        $uid = 'hw-' . $st['key'] . '-' . $it['key'];
                    @endphp
                    <div class="hw-item" style="--i: {{ $i }}" @if ($it['nudge']) data-nudge="{{ implode(',', $it['nudge']) }}" @endif>
                        <div class="hw-float">
                            <button type="button" class="hw-chip" id="{{ $uid }}-b" aria-expanded="false" aria-controls="{{ $uid }}">
                                <span class="hw-ico {{ str_starts_with($it['icon'], 'anee/') ? 'is-face' : '' }}"><img src="{{ asset('images/' . $it['icon']) }}" alt="" loading="lazy"></span>
                                <span class="hw-txt"><b>{{ $it['name'] }}</b><small>{{ $it['short'] }}</small></span>
                                @if ($it['anee'])<span class="hw-by"><img src="{{ asset('images/anee/avatar-160.jpg') }}" alt="">Anee</span>@endif
                                <i class="hw-plus" aria-hidden="true"></i>
                            </button>
                            <div class="hw-more" id="{{ $uid }}" role="region" aria-labelledby="{{ $uid }}-b">
                                <div><div class="hw-more-in">
                                    <p>{{ $it['what'] }}</p>
                                    <ul class="hw-gets">
                                        @foreach ($it['gets'] as $g)
                                            <li><svg fill="none" stroke="currentColor" stroke-width="2.6" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>{{ $g }}</li>
                                        @endforeach
                                    </ul>
                                    @if ($link)
                                        <a class="hw-link" href="{{ $link[0] }}">{{ $link[1] }}
                                            <svg fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14M13 6l6 6-6 6"/></svg></a>
                                    @endif
                                </div></div>
                            </div>
                        </div>
                    </div>
                @endforeach
                </div>
            </section>
            {{-- Between steps 2 and 3: the second phone, asking Anee about a crop. --}}
            @if ($n === 1)
                @include('public.how.chat-band')
            @endif
        @endforeach

        <div class="hw-end">
            <span class="hw-loop" aria-hidden="true"><svg fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h5M20 20v-5h-5M5.1 15a7.5 7.5 0 0013.4 1.5M18.9 9A7.5 7.5 0 005.5 7.5"/></svg></span>
            <p class="hw-step">And then <span>season after season</span></p>
            <{{ $hwHead }} class="hw-h2">The next season starts smarter</{{ $hwHead }}>
            <p>Your best lot becomes next season's protocol, Compare Reports shows what changed, and Anee reads it all with you. Every season you record makes the next one easier to plan.</p>
            <div class="hw-btns">
                <button type="button" class="hw-ghost" data-hw-go="plan">Back to step 1
                    <svg fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24" aria-hidden="true" style="transform: rotate(180deg)"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14m0 0l-6-6m6 6l6-6"/></svg>
                </button>
                @if ($hwMode === 'app')
                    <a href="{{ route('sm.index') }}" class="btn btn-accent">Open my schedules</a>
                @else
                    <a href="{{ route('signup') }}" class="btn btn-accent">Start your first season</a>
                @endif
            </div>
        </div>
    </div>

    <script type="application/json" data-hw-phone>@json(['email' => $hwPhone['email']])</script>
</div>

@once
@push('scripts')
<script>
(() => {
    if (window.HowItWorks) return;
    const NS = 'http://www.w3.org/2000/svg';
    const still = () => window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const wide = () => window.matchMedia('(min-width: 1024px)').matches;
    const make = (tag, attrs = {}) => { const n = document.createElementNS(NS, tag); for (const k in attrs) n.setAttribute(k, attrs[k]); return n; };
    const sleep = (ms) => new Promise((r) => setTimeout(r, ms));

    /* ---- the phone: the login page, then the dashboard scrolled the way a
       thumb would, round and round while it is on screen. Each part that
       comes into view lights its note. ---- */
    function phone(root) {
        const scr = root.querySelector('.hw-scr'), app = root.querySelector('.hwr');
        if (!scr || !app) return;
        const fit = () => { if (scr.clientWidth) app.style.setProperty('--sc', scr.clientWidth / 390); };
        fit();
        new ResizeObserver(fit).observe(scr);
        const q = (sel) => app.querySelector(sel), qa = (sel) => [...app.querySelectorAll(sel)];
        const login = q('.s-login'), dash = q('.s-dash');
        const email = q('[data-f="email"]'), pass = q('[data-f="pass"]'), btn = q('.hwr-btn'), tap = q('.hw-tap');
        const view = q('.hwr-view'), list = q('.hwr-list');
        const track = q('.hwr-track'), tasks = qa('.hwr-task'), dots = qa('.hwr-dots i');
        const parts = qa('[data-s]');
        const data = JSON.parse(root.querySelector('[data-hw-phone]').textContent || '{}');
        const ping = (k) => root._hwPing && root._hwPing(k);
        const sc = () => parseFloat(app.style.getPropertyValue('--sc')) || .6;
        if (still()) {
            login.classList.remove('is-on'); dash.classList.add('is-on');
            ping(0);
            return;
        }
        let seen = false;
        new IntersectionObserver((es) => { seen = es.some((e) => e.isIntersecting); }).observe(root.querySelector('.hw-phone'));
        const until = async () => { while (!seen || document.hidden) await sleep(400); };
        // Where a tap lands, in the app's own (unscaled) pixels.
        const tapAt = (el) => {
            const a = app.getBoundingClientRect(), r = el.getBoundingClientRect(), k = sc();
            tap.style.left = ((r.left - a.left) / k + (r.width / k) * .5) + 'px';
            tap.style.top = ((r.top - a.top) / k + (r.height / k) * .55) + 'px';
            tap.classList.remove('is-tap'); void tap.offsetWidth; tap.classList.add('is-tap');
        };
        const type = async (fld, text, ms) => {
            const out = fld.querySelector('.v');
            fld.classList.add('has');
            for (const ch of text) { await until(); out.textContent += ch; await sleep(ms); }
        };
        // The list rolls so a part sits near the top of the screen, never past
        // the end. Measured against the list itself, in the app's pixels: a
        // part's offsetTop counts from whichever card holds it.
        const scrollTo = (el) => {
            const max = Math.max(0, list.offsetHeight - view.clientHeight);
            const at = (el.getBoundingClientRect().top - list.getBoundingClientRect().top) / sc();
            const y = Math.min(max, Math.max(0, at - 12));
            list.style.transform = 'translateY(' + (-y) + 'px)';
        };
        const slide = (i) => {
            track.style.transform = 'translateX(' + (-100 * i) + '%)';
            dots.forEach((d, k) => d.classList.toggle('on', k === i));
        };
        const hot = (el) => parts.forEach((p) => p.classList.toggle('is-hot', p === el));
        const reset = () => {
            [email, pass].forEach((f) => { f.classList.remove('is-focus', 'has'); f.querySelector('.v').textContent = ''; });
            btn.classList.remove('is-press', 'is-busy');
            dash.classList.remove('is-on', 'is-gone'); login.classList.remove('is-gone'); login.classList.add('is-on');
            list.style.transform = ''; slide(0); tasks.forEach((t) => t.classList.remove('is-done')); hot(null);
            ping(-1);
        };
        const part = (s) => parts.find((p) => p.dataset.s === s);
        (async () => {
            await sleep(800);
            for (;;) {
                reset();
                await sleep(1000);
                await until(); tapAt(email); email.classList.add('is-focus'); await sleep(350);
                await type(email, data.email || 'juan@bukid.ph', 65); await sleep(300);
                email.classList.remove('is-focus'); tapAt(pass); pass.classList.add('is-focus'); await sleep(300);
                await type(pass, '••••••••', 80); await sleep(350);
                pass.classList.remove('is-focus'); await until(); tapAt(btn); btn.classList.add('is-press'); await sleep(180);
                btn.classList.remove('is-press'); btn.classList.add('is-busy'); await sleep(1100);
                login.classList.remove('is-on'); login.classList.add('is-gone'); dash.classList.add('is-on');
                await sleep(2000);
                // The tip of the day.
                await until(); scrollTo(part('tip')); hot(part('tip')); ping(2); await sleep(2300);
                // Today's work on the season: the tasks slide by, two get done.
                await until(); scrollTo(part('tasks')); hot(part('tasks')); ping(0); await sleep(1300);
                tasks[0] && tasks[0].classList.add('is-done'); await sleep(900);
                slide(1); await sleep(1000); tasks[1] && tasks[1].classList.add('is-done'); await sleep(900);
                slide(2); await sleep(1300);
                // The weather for the farm.
                await until(); scrollTo(part('wx')); hot(part('wx')); ping(1); await sleep(2300);
                // And the news feed.
                await until(); scrollTo(part('post')); hot(part('post')); ping(3); await sleep(2600);
                hot(null);
                await sleep(1200);
                dash.classList.remove('is-on'); dash.classList.add('is-gone'); ping(-1);
                await sleep(800);
            }
        })();
    }

    /* ---- between steps 2 and 3: a grower asks Anee about a crop in the
       real chat. A photo, the question typed, sent; Anee reads the lot, the
       weather and the photo (each note lights up), then answers. ---- */
    function chatFilm(root) {
        const band = root.querySelector('.hw-band');
        if (!band) return;
        const scr = band.querySelector('.hw-scr2'), app = band.querySelector('.hwr');
        const fit = () => { if (scr.clientWidth) app.style.setProperty('--sc', scr.clientWidth / 390); };
        fit();
        new ResizeObserver(fit).observe(scr);
        const q = (sel) => band.querySelector(sel);
        const ask = q('[data-c="ask"]'), wait = q('[data-c="wait"]'), answer = q('[data-c="answer"]');
        const comp = q('.hwc-comp'), input = q('.hwc-row .in'), cam = q('.hwc-row .cam'), go = q('.hwc-row .go');
        const list = q('.hwc-list'), body = q('.hwc-body'), tap = q('.hw-tap'), rows = [...wait.querySelectorAll('.hwc-steps li')];
        const notes = [...band.querySelectorAll('.hw-note')];
        const data = JSON.parse(q('[data-hw-chat]').textContent || '{}');
        const sc = () => parseFloat(app.style.getPropertyValue('--sc')) || .6;
        // The notes beside the phone, and their lines on a desk.
        const svg = q('.hw-band-svg'), phoneEl = q('.hw-phone2');
        const lines = () => {
            svg.textContent = '';
            if (!wide()) return;
            const b = band.getBoundingClientRect(), p = phoneEl.getBoundingClientRect();
            svg.setAttribute('viewBox', `0 0 ${b.width} ${b.height}`);
            notes.forEach((n, i) => {
                const r = n.getBoundingClientRect();
                const sx = p.right - b.left, sy = p.top - b.top + p.height * (.3 + i * .2);
                const ex = r.left - b.left, ey = r.top - b.top + r.height / 2, dx = ex - sx;
                const d = `M ${sx} ${sy} C ${sx + dx * .5} ${sy}, ${sx + dx * .5} ${ey}, ${ex} ${ey}`;
                const base = make('path', { d, class: 'hw-arc', pathLength: 1 }), flow = make('path', { d, class: 'hw-flow', pathLength: 1 }), dot = make('circle', { cx: ex, cy: ey, r: 3.5, class: 'hw-dest' });
                [base, flow, dot].forEach((x) => x.style.setProperty('--i', i + 2));
                svg.append(base, flow, dot);
                n._arc = [base, flow, dot];
                if (n.classList.contains('is-hot')) n._arc.forEach((x) => x.classList.add('is-on'));
            });
        };
        new ResizeObserver(() => requestAnimationFrame(lines)).observe(band);
        const note = (k) => notes.forEach((n, i) => {
            n.classList.toggle('is-hot', i === k);
            if (k === 99 || i < k) n.classList.add('is-read');
            (n._arc || []).forEach((x) => x.classList.toggle('is-on', i === k));
        });
        const show = (m) => { m.classList.add('is-shown'); void m.offsetWidth; m.classList.add('is-in'); };
        const hide = (m) => m.classList.remove('is-in', 'is-shown');
        const roll = (y) => { const max = Math.max(0, list.offsetHeight - body.clientHeight); list.style.transform = 'translateY(' + (-Math.min(max, Math.max(0, y))) + 'px)'; };
        const toEnd = () => roll(Infinity);
        const toTop = (m) => roll((m.getBoundingClientRect().top - list.getBoundingClientRect().top) / sc() - 14);
        if (still()) {
            show(ask); show(answer); toEnd(); notes.forEach((n) => n.classList.add('is-read'));
            return;
        }
        let seen = false;
        new IntersectionObserver((es) => {
            seen = es.some((e) => e.isIntersecting);
            if (seen) band.classList.add('is-in');
        }, { threshold: .2 }).observe(band);
        const until = async () => { while (!seen || document.hidden) await sleep(400); };
        const tapAt = (el) => {
            const a = app.getBoundingClientRect(), r = el.getBoundingClientRect(), k = sc();
            tap.style.left = ((r.left - a.left) / k + (r.width / k) * .5) + 'px';
            tap.style.top = ((r.top - a.top) / k + (r.height / k) * .5) + 'px';
            tap.classList.remove('is-tap'); void tap.offsetWidth; tap.classList.add('is-tap');
        };
        const out = input.querySelector('.v');
        const reset = () => {
            [ask, wait, answer].forEach(hide);
            comp.classList.remove('has-shot'); input.classList.remove('has', 'is-focus'); out.textContent = '';
            list.style.transform = ''; rows.forEach((r) => r.classList.remove('on', 'done')); ask.classList.remove('is-scanning');
            notes.forEach((n) => { n.classList.remove('is-hot', 'is-read'); (n._arc || []).forEach((x) => x.classList.remove('is-on')); });
        };
        (async () => {
            await sleep(600);
            for (;;) {
                reset();
                await sleep(900);
                await until(); tapAt(cam); await sleep(380); comp.classList.add('has-shot'); await sleep(800);
                await until(); tapAt(input); input.classList.add('is-focus', 'has'); await sleep(250);
                for (const ch of (data.question || '')) { await until(); out.textContent += ch; await sleep(38); }
                await sleep(450);
                input.classList.remove('is-focus'); tapAt(go); go.classList.add('is-press'); await sleep(160); go.classList.remove('is-press');
                show(ask); comp.classList.remove('has-shot'); out.textContent = ''; input.classList.remove('has');
                toEnd(); await sleep(800);
                show(wait); toEnd();
                // Deeply analyzing the photo (a light sweeps it), checking the
                // related data, providing the answer: each ticked in turn.
                for (let k = 0; k < rows.length; k++) {
                    await until();
                    rows.forEach((r, i) => { r.classList.toggle('on', i === k); r.classList.toggle('done', i < k); });
                    ask.classList.toggle('is-scanning', k === 0);
                    note(k);
                    await sleep(k === 0 ? 2000 : 1500);
                }
                rows.forEach((r) => { r.classList.remove('on'); r.classList.add('done'); });
                ask.classList.remove('is-scanning');
                note(99);
                await sleep(500);
                hide(wait); show(answer); toTop(answer); await sleep(2800);
                await until(); toEnd(); await sleep(3400);
                [ask, answer].forEach((m) => m.classList.remove('is-in'));
                await sleep(600);
            }
        })();
    }

    /* ---- the notes around the phone: routes from the phone on a wide screen, one at a time on a phone ---- */
    function pings(root) {
        const show = root.querySelector('.hw-show'), svg = root.querySelector('.hw-show-svg'), ph = root.querySelector('.hw-phone');
        const list = [...root.querySelectorAll('.hw-ping')];
        if (!show || !list.length) return;
        const draw = () => {
            svg.textContent = '';
            if (!wide()) return;
            const s = show.getBoundingClientRect(), p = ph.getBoundingClientRect();
            svg.setAttribute('viewBox', `0 0 ${s.width} ${s.height}`);
            list.forEach((g, i) => {
                const r = g.getBoundingClientRect();
                const left = r.left + r.width / 2 < p.left + p.width / 2;
                const sx = (left ? p.left : p.right) - s.left, sy = p.top - s.top + p.height * (0.28 + (i % 2) * 0.36);
                const ex = (left ? r.right : r.left) - s.left, ey = r.top - s.top + r.height / 2;
                const dx = ex - sx, d = `M ${sx} ${sy} C ${sx + dx * 0.5} ${sy}, ${sx + dx * 0.5} ${ey}, ${ex} ${ey}`;
                const base = make('path', { d, class: 'hw-arc', pathLength: 1 }), flow = make('path', { d, class: 'hw-flow', pathLength: 1 }), dot = make('circle', { cx: ex, cy: ey, r: 3.5, class: 'hw-dest' });
                [base, flow, dot].forEach((n) => n.style.setProperty('--i', i + 4));
                svg.append(base, flow, dot);
                g._arc = [base, flow, dot];
                if (g.classList.contains('is-hot')) g._arc.forEach((n) => n.classList.add('is-on'));
            });
            // The hero's routes play as soon as they are drawn.
            show.classList.add('is-in');
        };
        new ResizeObserver(() => requestAnimationFrame(draw)).observe(show);
        // The phone lights the note of the card it has just shown (-1: none):
        // on a desk the note glows and its route turns gold; on a phone it is
        // the one note under the phone.
        root._hwPing = (k) => list.forEach((g, i) => {
            const on = i === k;
            g.classList.toggle('is-hot', on);
            g.classList.toggle('is-on', on);
            (g._arc || []).forEach((n) => n.classList.toggle('is-on', on));
        });
    }

    /* ---- a step's field: the dots, the routes from Anee to each tool ---- */
    function field(st) {
        const svg = st.querySelector('.hw-svg'), hub = st.querySelector('.hw-hub');
        const gDots = make('g'), gArcs = make('g');
        svg.append(gDots, gArcs);
        const fieldEl = st.querySelector('.hw-field'), items = [...st.querySelectorAll('.hw-item')];
        // On a desk the tools burst out of Anee like a firework: spaced evenly
        // all the way round her, long streaks and short ones taking turns so
        // no side is heavier than another, nudged apart only if two would
        // touch, and kept inside the field. The same step lands the same way
        // every time (the little jitter is seeded by its name).
        let laid = '';
        const scatter = () => {
            const on = wide();
            const key = on + ':' + fieldEl.clientWidth;
            if (key === laid) return;
            laid = key;
            fieldEl.classList.toggle('is-scatter', on);
            if (!on) { items.forEach((it) => { it.style.left = it.style.top = ''; it.classList.remove('up', 'end'); }); return; }
            const W = fieldEl.clientWidth, H = fieldEl.clientHeight, n = items.length;
            if (!W || !H || !n) return;
            const cx = W / 2, cy = H / 2, hr = hub.offsetWidth / 2 + 28, pad = 18;
            const box = items.map((it) => ({ w: it.offsetWidth, h: it.querySelector('.hw-chip').offsetHeight }));
            let seed = [...(st.dataset.stage || 'x')].reduce((a, c) => (a * 31 + c.charCodeAt(0)) % 233280, 7);
            const rnd = () => { seed = (seed * 9301 + 49297) % 233280; return seed / 233280; };
            const p = box.map((b, i) => {
                const a = -Math.PI / 2 + (i + .5) * (2 * Math.PI / n) + (rnd() - .5) * .08;
                const k = i % 2 ? .6 + rnd() * .06 : .93 + rnd() * .07;
                return { x: cx + Math.cos(a) * (cx - b.w / 2 - 6) * k, y: cy + Math.sin(a) * (cy - b.h / 2 - 6) * k };
            });
            for (let round = 0; round < 160; round++) {
                let moved = false;
                for (let i = 0; i < n; i++) {
                    for (let j = i + 1; j < n; j++) {
                        const ox = (box[i].w + box[j].w) / 2 + pad - Math.abs(p[i].x - p[j].x);
                        const oy = (box[i].h + box[j].h) / 2 + pad - Math.abs(p[i].y - p[j].y);
                        if (ox > 0 && oy > 0) {
                            moved = true;
                            if (oy < ox) { const d = (p[i].y < p[j].y ? -1 : 1) * oy / 2; p[i].y += d; p[j].y -= d; }
                            else { const d = (p[i].x < p[j].x ? -1 : 1) * ox / 2; p[i].x += d; p[j].x -= d; }
                        }
                    }
                }
                for (let i = 0; i < n; i++) {
                    const ox = box[i].w / 2 + hr - Math.abs(p[i].x - cx), oy = box[i].h / 2 + hr - Math.abs(p[i].y - cy);
                    if (ox > 0 && oy > 0) {
                        moved = true;
                        if (oy < ox) p[i].y += (p[i].y < cy ? -1 : 1) * oy; else p[i].x += (p[i].x < cx ? -1 : 1) * ox;
                    }
                    p[i].x = Math.min(W - box[i].w / 2, Math.max(box[i].w / 2, p[i].x));
                    p[i].y = Math.min(H - box[i].h / 2, Math.max(box[i].h / 2, p[i].y));
                }
                if (!moved) break;
            }
            // A tool the owner placed by hand moves off its burst spot by its
            // own nudge (rem, right and down), still kept inside the field.
            const rem = parseFloat(getComputedStyle(document.documentElement).fontSize) || 16;
            items.forEach((it, i) => {
                if (!it.dataset.nudge) return;
                const [nx, ny] = it.dataset.nudge.split(',').map(Number);
                p[i].x = Math.min(W - box[i].w / 2, Math.max(box[i].w / 2, p[i].x + nx * rem));
                p[i].y = Math.min(H - box[i].h / 2, Math.max(box[i].h / 2, p[i].y + ny * rem));
            });
            items.forEach((it, i) => {
                it.style.left = (p[i].x - box[i].w / 2) + 'px';
                it.style.top = (p[i].y - box[i].h / 2) + 'px';
                it.style.setProperty('--fx', (cx - p[i].x) + 'px');
                it.style.setProperty('--fy', (cy - p[i].y) + 'px');
                it.classList.toggle('up', p[i].y > H * .55);
                it.classList.toggle('end', p[i].x > W * .6);
            });
        };
        // Where an element sits in the step, from layout boxes (no transform counts).
        const off = (el) => { let x = 0, y = 0, e = el; while (e && e !== st) { x += e.offsetLeft; y += e.offsetTop; e = e.offsetParent; } return [x, y]; };
        const hubC = () => { const [x, y] = off(hub); return [x + hub.offsetWidth / 2, y + hub.offsetHeight / 2]; };
        if (document.fonts && document.fonts.ready) document.fonts.ready.then(() => { laid = ''; requestAnimationFrame(() => draw()); });
        const routes = items.map((it, i) => {
            const base = make('path', { class: 'hw-arc', pathLength: 1 }), flow = make('path', { class: 'hw-flow', pathLength: 1 }), dest = make('circle', { class: 'hw-dest', r: 3.5 });
            const spark = make('circle', { class: 'hw-spark', r: 9 });
            [base, flow, dest, spark].forEach((n) => n.style.setProperty('--i', i));
            gArcs.append(base, flow, dest, spark);
            it._hw = { base, flow, dest };
            return { it, chip: it.querySelector('.hw-chip'), base, flow, dest, spark };
        });
        let dotsW = 0;
        const draw = () => {
            const W = st.offsetWidth, H = st.offsetHeight;
            if (!W) return;
            scatter();
            svg.setAttribute('width', W); svg.setAttribute('height', H); svg.setAttribute('viewBox', `0 0 ${W} ${H}`);
            const [hx, hy] = hubC();
            routes.forEach((r) => {
                // Layout boxes (offset*), not screen boxes: the entrance and
                // the float move a chip, never where its route ends.
                const [bl, bt] = off(r.it), bw = r.it.offsetWidth, ch = r.chip.offsetHeight;
                const mx = bl + bw / 2, my = bt + ch / 2;
                let ax, ay, d;
                if (wide()) {
                    // A firework streak: from the edge of Anee's circle straight
                    // out to where the line toward her meets the chip's edge,
                    // with the slightest swirl.
                    const vx = hx - mx, vy = hy - my;
                    const t = Math.min(Math.abs(vx) > .01 ? (bw / 2) / Math.abs(vx) : Infinity, Math.abs(vy) > .01 ? (ch / 2) / Math.abs(vy) : Infinity, 1);
                    ax = mx + vx * t; ay = my + vy * t;
                    const len = Math.hypot(ax - hx, ay - hy) || 1, ux = (ax - hx) / len, uy = (ay - hy) / len;
                    const rim = hub.offsetWidth / 2 + 6, sx = hx + ux * rim, sy = hy + uy * rim, bow = (len - rim) * .07;
                    d = `M ${sx} ${sy} Q ${(sx + ax) / 2 - uy * bow} ${(sy + ay) / 2 + ux * bow} ${ax} ${ay}`;
                } else {
                    ax = mx >= hx ? bl : bl + bw; ay = my;
                    const dx = ax - hx, dy = ay - hy;
                    d = Math.abs(dx) > 80
                        ? `M ${hx} ${hy} C ${hx + dx * 0.5} ${hy}, ${hx + dx * 0.5} ${ay}, ${ax} ${ay}`
                        : `M ${hx} ${hy} C ${hx} ${hy + dy * 0.8}, ${hx + dx * 0.15} ${ay}, ${ax} ${ay}`;
                }
                r.base.setAttribute('d', d); r.flow.setAttribute('d', d);
                r.dest.setAttribute('cx', ax); r.dest.setAttribute('cy', ay);
                r.spark.setAttribute('cx', ax); r.spark.setAttribute('cy', ay);
            });
            if (st.classList.contains('is-in') && Math.abs(W - dotsW) > 2) { dotsW = W; dots(st, gDots, hx, hy, W, H); }
        };
        st._hwDraw = draw;
        new ResizeObserver(() => requestAnimationFrame(draw)).observe(st);
        let first = true;
        new IntersectionObserver((es) => {
            es.forEach((e) => {
                st.classList.toggle('is-live', e.isIntersecting);
                if (e.isIntersecting && first) {
                    first = false;
                    draw();
                    dotsW = st.offsetWidth;
                    dots(st, gDots, ...hubC(), st.offsetWidth, st.offsetHeight);
                    st.getBoundingClientRect();
                    requestAnimationFrame(() => st.classList.add('is-in'));
                    // Once everything has landed, the routes are measured again from where it all rests.
                    setTimeout(draw, 1500);
                }
            });
        }, { threshold: 0.12 }).observe(st);
    }

    /* Each step's field of dots tells its part of the season: lots marked
       out, rows sown left to right, sprouts growing out from Anee, a pest
       that comes and goes, rows ripening from the bottom, bars rising. */
    function dots(st, g, hx, hy, W, H) {
        g.textContent = '';
        const pat = st.dataset.pattern, small = W < 700;
        const S = small ? 22 : 28, R = small ? Math.max(W * 1.1, 360) : Math.max(W * 0.5, 470);
        const cols = Math.floor(W / S), rows = Math.floor(H / S);
        const ox = (W - (cols - 1) * S) / 2, oy = S / 2;
        const lots = small ? [[0.3, 0.02, 0.75, 0.1], [0.55, 0.13, 0.98, 0.2]] : [[0.05, 0.24, 0.22, 0.44], [0.76, 0.18, 0.95, 0.36], [0.68, 0.64, 0.92, 0.84]];
        const pest = small ? { x: W * 0.8, y: Math.min(H * 0.12, 160) } : { x: W * 0.83, y: H * 0.4 };
        const frag = document.createDocumentFragment();
        for (let r = 0; r < rows; r++) {
            for (let c = 0; c < cols; c++) {
                const x = ox + c * S, y = oy + r * S;
                const dist = Math.hypot((x - hx) / (small ? 1 : 1.35), y - hy);
                const f = 1 - dist / R;
                if (f <= 0.04) continue;
                let o = 0.05 + 0.32 * f * f, d = r * 0.02, cls = '', pd = '';
                if (pat === 'plan') {
                    if (lots.some(([a, b, e, z]) => x >= a * W && x <= e * W && y >= b * H && y <= z * H)) { cls = 'k'; o = Math.min(0.95, o + 0.5); d = 0.8 + (c % 6) * 0.04; }
                } else if (pat === 'plant') {
                    if (r % 2) continue; cls = 'k'; o = Math.min(0.9, o * 1.7); d = (x / W) * 1 + r * 0.012;
                } else if (pat === 'grow') {
                    if (r % 2) continue; cls = 'g'; o = Math.min(0.95, o * 1.8); d = (dist / R) * 1.2;
                } else if (pat === 'protect') {
                    d = (dist / R) * 0.8;
                    if (Math.hypot(x - pest.x, y - pest.y) < S * 1.7) { cls = 'p'; o = 0.95; pd = (-(c * 0.37 + r * 0.53) % 3).toFixed(2) + 's'; }
                } else if (pat === 'harvest') {
                    if (r % 2) continue; cls = 'h'; o = Math.min(0.95, o * 1.9); d = (1 - y / H) * 1.2 + (c % 3) * 0.05;
                } else if (pat === 'reports') {
                    const bh = H * (0.16 + 0.55 * (0.5 + 0.5 * Math.sin(c * 0.9)) * (0.45 + 0.55 * c / Math.max(1, cols)));
                    if (y > H - bh) { cls = y - S <= H - bh ? 't' : 'k'; o = Math.min(0.95, o + 0.45); d = c * 0.035 + ((H - y) / H) * 0.9; }
                }
                const n = make('circle', { cx: x.toFixed(1), cy: y.toFixed(1), r: small ? 2 : 2.4, class: 'hw-dot ' + cls });
                n.style.cssText = `--o:${o.toFixed(3)};--d:${d.toFixed(2)}s` + (pd ? `;--pd:${pd}` : '');
                frag.appendChild(n);
            }
        }
        g.appendChild(frag);
    }

    /* ---- the rail: it fills to the middle of the screen, and lights each Anee it reaches ---- */
    function rail(root, scroller, stages) {
        const steps = root.querySelector('.hw-steps'), railEl = root.querySelector('.hw-rail');
        const fill = root.querySelector('.hw-rail-fill'), tip = root.querySelector('.hw-rail-tip'), loop = root.querySelector('.hw-loop');
        const drop = root.querySelector('.hw-drop svg'), ph = root.querySelector('.hw-phone'), show = root.querySelector('.hw-show');
        let len = 0, hubs = [];
        const update = () => {
            const top = railEl.getBoundingClientRect().top;
            // The tip runs at 70% of the screen, so Anee lights up while her step is still in full view.
            const p = Math.max(0, Math.min(len, window.innerHeight * 0.7 - top));
            fill.style.transform = `scaleY(${len ? p / len : 0})`;
            tip.style.transform = `translateY(${p}px)`;
            stages.forEach((st, i) => st.classList.toggle('is-lit', p >= hubs[i] - 4));
        };
        const measure = () => {
            const rr = railEl.getBoundingClientRect(), lr = loop.getBoundingClientRect();
            len = Math.max(0, lr.top + lr.height / 2 - rr.top);
            railEl.style.height = len + 'px';
            hubs = stages.map((st) => { const h = st.querySelector('.hw-hub').getBoundingClientRect(); return h.top + h.height / 2 - rr.top; });
            // From the phone down into the rail: straight on a wide screen, a curve to the left rail on a phone.
            const d0 = drop.getBoundingClientRect(), p = ph.getBoundingClientRect();
            const from = wide() ? p.bottom : show.getBoundingClientRect().bottom;
            const sx = p.left + p.width / 2 - d0.left, ex = rr.left + rr.width / 2 - d0.left, H = d0.height;
            const d = `M ${sx} ${-(d0.top - from)} C ${sx} ${H * 0.55}, ${ex} ${H * 0.35}, ${ex} ${H}`;
            drop.innerHTML = `<path class="hw-line" pathLength="1" d="${d}"/><path class="hw-line-flow" pathLength="1" d="${d}"/>`;
            update();
        };
        let raf = 0;
        scroller.addEventListener('scroll', () => { if (!raf) raf = requestAnimationFrame(() => { raf = 0; update(); }); }, { passive: true });
        window.addEventListener('resize', () => requestAnimationFrame(measure));
        new ResizeObserver(() => requestAnimationFrame(measure)).observe(steps);
        measure();
    }

    function mount(root, opts = {}) {
        if (!root || root._hwMounted) return;
        root._hwMounted = true;
        const scroller = opts.scroller || window;
        const stages = [...root.querySelectorAll('.hw-steps .hw-stage')];
        root.classList.add('is-ready');
        pings(root);
        phone(root);
        chatFilm(root);
        stages.forEach(field);
        rail(root, scroller, stages);
        if (still()) stages.forEach((st) => st.classList.add('is-in'));

        root.addEventListener('click', (e) => {
            const go = e.target.closest('[data-hw-go]');
            if (go) {
                const to = root.querySelector('#hw-step-' + go.dataset.hwGo);
                if (to) to.scrollIntoView({ behavior: still() ? 'auto' : 'smooth', block: 'start' });
                return;
            }
            const chip = e.target.closest('.hw-chip');
            if (!chip) return;
            const item = chip.closest('.hw-item'), st = item.closest('.hw-stage');
            const open = !item.classList.contains('is-open');
            st.querySelectorAll('.hw-item.is-open').forEach((x) => { if (x !== item) toggle(x, false); });
            toggle(item, open);
            if (open && !wide()) {
                setTimeout(() => {
                    const r = item.getBoundingClientRect();
                    const over = r.bottom - (window.innerHeight - 16);
                    if (over > 0) scroller.scrollBy({ top: Math.min(over, r.top - 90), behavior: still() ? 'auto' : 'smooth' });
                }, 400);
            }
        });
    }
    function toggle(item, on) {
        item.classList.toggle('is-open', on);
        item.querySelector('.hw-chip').setAttribute('aria-expanded', on ? 'true' : 'false');
        if (item._hw) ['base', 'flow', 'dest'].forEach((k) => item._hw[k].classList.toggle('is-on', on));
    }

    window.HowItWorks = { mount };
    // The page mounts itself; the dashboard's modal mounts its copy when it first opens.
    const boot = () => document.querySelectorAll('[data-hw][data-hw-mode="site"]').forEach((r) => mount(r));
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot); else boot();
})();
</script>
@endpush
@endonce
