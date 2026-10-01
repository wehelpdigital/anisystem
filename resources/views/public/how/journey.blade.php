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

     The phone at the top plays a short film: someone logs in, and today's
     dashboard comes up card by card (the day's activities, the weather,
     Anee's tip, the community), each note around the phone lighting up as
     its card arrives.

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
    .hw-scr { position: relative; height: 100%; overflow: hidden; border-radius: 2.05rem; background: #f2f6ec; color: #14210c; text-align: left; }
    /* Two scenes on the one screen: the login, then today's dashboard. */
    .hw-scene { position: absolute; inset: 0; display: flex; flex-direction: column; opacity: 0; transform: translateX(18px); pointer-events: none;
        transition: opacity .5s var(--ease), transform .6s var(--ease); }
    .hw-scene.is-on { opacity: 1; transform: none; }
    .hw-scene.is-gone { opacity: 0; transform: translateX(-18px); }
    .s-login { justify-content: center; padding: 1.3rem 1.05rem; background: radial-gradient(14rem 10rem at 50% 0%, #e2efd2, transparent 70%), #f7faf3; }
    .hw-lg-mark { display: block; width: 3.1rem; height: auto; margin: 0 auto .55rem; }
    .hw-lg-h { display: block; text-align: center; font-family: var(--font-heading); font-size: 1.05rem; font-weight: 800; }
    .hw-lg-s { display: block; margin-bottom: .85rem; text-align: center; font-size: .66rem; color: #6b7280; }
    .hw-fld { position: relative; display: block; margin-top: .5rem; padding: .45rem .65rem .5rem; border-radius: .7rem; background: #fff; border: 1.5px solid #dfe7d4;
        transition: border-color .3s var(--ease), box-shadow .3s var(--ease); }
    .hw-fld i { display: block; font-style: normal; font-size: .54rem; font-weight: 800; letter-spacing: .06em; text-transform: uppercase; color: #6b7280; }
    .hw-fld span { font-size: .74rem; font-weight: 700; color: #14210c; }
    .hw-fld em { display: inline-block; width: 1.5px; height: .85em; margin-left: 1px; vertical-align: -.1em; background: #4a7c2a; opacity: 0; }
    .hw-fld.is-focus { border-color: #6b9f3d; box-shadow: 0 0 0 3px rgb(107 159 61 / .16); }
    .hw-fld.is-focus em { opacity: 1; animation: hwCaret 1s steps(1) infinite; }
    @keyframes hwCaret { 50% { opacity: 0; } }
    .hw-lg-btn { display: flex; align-items: center; justify-content: center; gap: .4rem; margin-top: .85rem; padding: .6rem; border-radius: .75rem;
        background: #f5c518; color: #3b2f00; font-size: .78rem; font-weight: 800; transition: transform .2s var(--ease), filter .2s var(--ease); }
    .hw-lg-btn.is-press { transform: scale(.95); filter: brightness(.94); }
    .hw-lg-btn i { display: none; width: .8rem; height: .8rem; border-radius: 999px; border: 2px solid rgb(59 47 0 / .25); border-top-color: #3b2f00; animation: hwSpin .7s linear infinite; }
    .hw-lg-btn.is-busy i { display: inline-block; }
    .hw-lg-f { margin-top: .8rem; text-align: center; font-size: .62rem; font-weight: 700; color: #4a7c2a; }
    /* A fingertip: where the next tap lands. */
    .hw-tap { position: absolute; z-index: 5; width: 1.8rem; height: 1.8rem; margin: -.9rem 0 0 -.9rem; border-radius: 999px; pointer-events: none;
        background: rgb(20 33 12 / .16); box-shadow: 0 0 0 2px rgb(255 255 255 / .8); opacity: 0; }
    .hw-tap.is-tap { animation: hwTap .55s var(--ease); }
    @keyframes hwTap { 0% { opacity: 0; transform: scale(.4); } 30% { opacity: 1; transform: scale(1); } 100% { opacity: 0; transform: scale(1.6); } }
    .hw-dtop { flex: none; display: flex; align-items: center; gap: .45rem; padding: .95rem .8rem .6rem; background: #fff; border-bottom: 1px solid #e6eddf; }
    .hw-dtop img { width: 1.6rem; height: auto; }
    .hw-dtop b { display: block; font-family: var(--font-heading); font-size: .82rem; line-height: 1.1; }
    .hw-dtop small { display: block; font-size: .56rem; color: #6b7280; }
    .hw-dtop .av { margin-left: auto; width: 1.6rem; height: 1.6rem; border-radius: 999px; display: grid; place-items: center; background: #4a7c2a; color: #fff; font-size: .6rem; font-weight: 900; }
    .hw-dscroll { position: relative; flex: 1; min-height: 0; overflow: hidden; }
    .hw-dlist { display: flex; flex-direction: column; gap: .45rem; padding: .6rem; transition: transform 1.2s var(--ease); }
    .hw-hi, .hw-card { opacity: 0; transform: translateY(14px) scale(.97); transition: opacity .5s var(--ease), transform .6s var(--ease), border-color .4s var(--ease), box-shadow .4s var(--ease); }
    .hw-hi.is-in, .hw-card.is-in { opacity: 1; transform: none; }
    .hw-hi small { display: block; font-size: .55rem; font-weight: 800; letter-spacing: .06em; text-transform: uppercase; color: #6b8f4a; }
    .hw-hi b { display: block; font-family: var(--font-heading); font-size: .92rem; font-weight: 800; }
    .hw-card { padding: .5rem .6rem; border-radius: .8rem; background: #fff; border: 1px solid #e6eddf; box-shadow: 0 1px 2px rgb(0 0 0 / .04); }
    .hw-card.is-hot { border-color: #f5c518; box-shadow: 0 0 0 2px rgb(245 197 24 / .3); }
    .hw-card h6 { display: flex; align-items: center; gap: .3rem; margin-bottom: .3rem; font-size: .58rem; font-weight: 900; letter-spacing: .06em; text-transform: uppercase; color: #2f5219; }
    .hw-task { display: flex; align-items: center; gap: .45rem; padding: .22rem 0; }
    .hw-task + .hw-task { border-top: 1px dashed #e8eee0; }
    .hw-chk { flex: none; width: .95rem; height: .95rem; border-radius: .3rem; border: 1.5px solid #b9d39b; display: grid; place-items: center;
        transition: background-color .3s var(--ease), border-color .3s var(--ease); }
    .hw-chk svg { width: .62rem; height: .62rem; color: #fff; stroke-dasharray: 24; stroke-dashoffset: 24; transition: stroke-dashoffset .35s var(--ease) .1s; }
    .hw-task.is-done .hw-chk { background: #4a7c2a; border-color: #4a7c2a; }
    .hw-task.is-done .hw-chk svg { stroke-dashoffset: 0; }
    .hw-task b { display: block; font-size: .66rem; font-weight: 800; line-height: 1.2; transition: color .3s var(--ease); }
    .hw-task small { display: block; font-size: .56rem; color: #6b7280; }
    .hw-task.is-done b { color: #9ca3af; text-decoration: line-through; }
    .hw-wx { display: flex; align-items: center; gap: .5rem; }
    .hw-wx .e { font-size: 1.5rem; line-height: 1; }
    .hw-wx b { display: block; font-family: var(--font-heading); font-size: 1.25rem; font-weight: 800; line-height: 1; }
    .hw-wx small { display: block; font-size: .58rem; color: #4b5563; }
    .hw-rain { display: inline-block; margin-top: .35rem; padding: .14rem .45rem; border-radius: 999px; background: #e0ecfd; color: #1e3a8a; font-size: .56rem; font-weight: 800; }
    .hw-tipc { display: flex; align-items: flex-start; gap: .45rem; }
    .hw-tipc img { flex: none; width: 1.45rem; height: 1.45rem; border-radius: 999px; object-fit: cover; }
    .hw-tipc p { font-size: .62rem; line-height: 1.45; }
    .hw-com { display: flex; align-items: center; gap: .45rem; }
    .hw-avs { display: flex; }
    .hw-avs i { width: 1.2rem; height: 1.2rem; margin-left: -.35rem; border-radius: 999px; border: 2px solid #fff; display: grid; place-items: center;
        font-style: normal; font-size: .48rem; font-weight: 900; color: #fff; background: hsl(var(--h) 45% 42%); }
    .hw-avs i:first-child { margin-left: 0; }
    .hw-com p { font-size: .62rem; line-height: 1.35; }
    .hw-post { margin-top: .4rem; padding: .38rem .5rem; border-radius: .55rem; background: #f6f9f2; font-size: .58rem; line-height: 1.4; color: #374151; }
    .hw-post b { font-weight: 800; color: #14210c; }
    .hw-dnav { flex: none; display: grid; grid-template-columns: repeat(4, 1fr); padding: .4rem .3rem .65rem; background: #fff; border-top: 1px solid #e6eddf; }
    .hw-dnav span { display: flex; flex-direction: column; align-items: center; gap: .1rem; font-size: .48rem; font-weight: 700; color: #9ca3af; }
    .hw-dnav span.on { color: #4a7c2a; }
    .hw-dnav svg { width: .95rem; height: .95rem; }

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
        .hw-hub { position: relative; left: auto; top: auto; grid-column: 2; grid-row: 2 / span var(--rows); align-self: center; justify-self: center; }
        .hw-num { width: 2.1rem; height: 2.1rem; font-size: .95rem; right: .1rem; top: .1rem; }
        .hw-item { grid-row: var(--r); align-self: center; width: 19.5rem; max-width: 100%; }
        .hw-item.l { grid-column: 1; justify-self: end; margin-right: var(--nudge); }
        .hw-item.r { grid-column: 3; justify-self: start; margin-left: var(--nudge); }
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
                <div class="hw-scr">
                    {{-- Scene one: logging in. --}}
                    <div class="hw-scene s-login is-on">
                        <img class="hw-lg-mark" src="{{ asset('images/logo-mark.png') }}?v=anee" alt="">
                        <b class="hw-lg-h">Welcome back</b>
                        <small class="hw-lg-s">Log in to your farm</small>
                        <span class="hw-fld" data-f="email"><i>Email</i><span></span><em></em></span>
                        <span class="hw-fld" data-f="pass"><i>Password</i><span></span><em></em></span>
                        <span class="hw-lg-btn"><i></i><b>Log In</b></span>
                        <span class="hw-lg-f">New here? Start free</span>
                    </div>
                    {{-- Scene two: today, on the dashboard. --}}
                    <div class="hw-scene s-dash">
                        <div class="hw-dtop">
                            <img src="{{ asset('images/logo-mark.png') }}?v=anee" alt="">
                            <div><b>Dashboard</b><small>Your farm at a glance</small></div>
                            <span class="av">{{ mb_substr($hwPhone['name'], 0, 1) }}</span>
                        </div>
                        <div class="hw-dscroll"><div class="hw-dlist">
                            <div class="hw-hi"><small>{{ now(config('app.timezone'))->format('l, F j') }}</small><b>{{ $hwPhone['hello'] }}, {{ $hwPhone['name'] }}!</b></div>
                            <div class="hw-card">
                                <h6>📋 Today's activities</h6>
                                @foreach ($hwPhone['tasks'] as [$tt, $tl])
                                    <div class="hw-task"><span class="hw-chk"><svg fill="none" stroke="currentColor" stroke-width="3.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg></span><div><b>{{ $tt }}</b><small>{{ $tl }}</small></div></div>
                                @endforeach
                            </div>
                            <div class="hw-card">
                                <h6>🌤️ Weather today</h6>
                                <div class="hw-wx"><span class="e">⛅</span><div><b>{{ $hwPhone['temp'] }}</b><small>{{ $hwPhone['sky'] }}</small></div></div>
                                <span class="hw-rain">🌧️ {{ $hwPhone['rain'] }}</span>
                            </div>
                            <div class="hw-card">
                                <h6>💡 Anee's tip for today</h6>
                                <div class="hw-tipc"><img src="{{ asset('images/anee/avatar-160.jpg') }}" alt=""><p>{{ $hwPhone['tip'] }}</p></div>
                            </div>
                            <div class="hw-card">
                                <h6>💬 Today in the community</h6>
                                <div class="hw-com">
                                    <span class="hw-avs">@foreach ($hwPhone['faces'] as [$fi, $fh])<i style="--h: {{ $fh }}">{{ $fi }}</i>@endforeach</span>
                                    <p><b>{{ $hwPhone['posts'] }} new posts</b> from farmers near you</p>
                                </div>
                                <div class="hw-post"><b>{{ $hwPhone['post'][0] }}:</b> {{ $hwPhone['post'][1] }}</div>
                            </div>
                        </div></div>
                        <div class="hw-dnav">
                            <span class="on"><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 11l9-8 9 8M5 10v10h5v-6h4v6h5V10"/></svg>Home</span>
                            <span><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="16" rx="2"/><path stroke-linecap="round" d="M8 3v4M16 3v4M3 10h18"/></svg>Schedules</span>
                            <span><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-1a4 4 0 00-4-4h-1M9 11a4 4 0 100-8 4 4 0 000 8zm8 0a3 3 0 100-6M2 20v-1a5 5 0 015-5h4a5 5 0 015 5v1H2z"/></svg>Community</span>
                            <span><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16l-1.5 11a2 2 0 01-2 1.7h-9a2 2 0 01-2-1.7L4 7zm4 0V5a4 4 0 018 0v2"/></svg>Shop</span>
                        </div>
                    </div>
                    <span class="hw-tap"></span>
                </div>
            </div>
        </div>
        <p class="sr-only">A phone shows someone logging in to anee.io, then today's dashboard: the day's activities on each lot, today's weather, Anee's tip for the day, and what is new in the community.</p>
    </section>

    <div class="hw-steps">
        <div class="hw-drop" aria-hidden="true"><svg></svg></div>
        <div class="hw-rail" aria-hidden="true"><i class="hw-rail-base"></i><i class="hw-rail-fill"></i><i class="hw-rail-tip"></i></div>

        @foreach ($hwStages as $n => $st)
            @php $rows = (int) ceil(count($st['items']) / 2); @endphp
            <section class="hw-stage" id="hw-step-{{ $st['key'] }}" data-stage="{{ $st['key'] }}" data-pattern="{{ $st['pattern'] }}" style="--rows: {{ $rows }}" aria-labelledby="hw-h-{{ $st['key'] }}">
                <svg class="hw-svg" aria-hidden="true"></svg>
                <header class="hw-head">
                    <p class="hw-step">Step {{ $n + 1 }} <span>{{ $st['when'] }}</span></p>
                    <{{ $hwHead }} class="hw-h2" id="hw-h-{{ $st['key'] }}">{{ $st['title'] }}</{{ $hwHead }}>
                    <p class="hw-sub">{{ $st['lede'] }}</p>
                </header>
                <div class="hw-hub" aria-hidden="true">
                    <i class="hw-ring"></i><i class="hw-ring"></i>
                    <span class="hw-hub-core"><img src="{{ asset('images/anee/emoji/' . $st['face'] . '.png') }}" alt="" loading="lazy"></span>
                    <span class="hw-num">{{ $n + 1 }}</span>
                </div>
                <div class="hw-say">
                    <img src="{{ asset('images/anee/avatar-160.jpg') }}" alt="" loading="lazy">
                    <div><small>Anee</small><p>{!! $hwWords($st['say']) !!}</p></div>
                </div>
                @foreach ($st['items'] as $i => $it)
                    @php
                        $row = intdiv($i, 2);
                        $t = $rows > 1 ? (($row + .5) / $rows - .5) * 2 : 0;
                        $nudge = round((1 - $t * $t) * 2.6, 2);
                        $link = $hwLink($it);
                        $uid = 'hw-' . $st['key'] . '-' . $it['key'];
                    @endphp
                    <div class="hw-item {{ $i % 2 ? 'r' : 'l' }}" style="--i: {{ $i }}; --r: {{ $row + 2 }}; --nudge: {{ $nudge }}rem">
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
            </section>
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

    /* ---- the phone: a login, then today's dashboard card by card, round
       and round while it is on screen. Each card lights its note. ---- */
    function phone(root) {
        const scr = root.querySelector('.hw-scr');
        if (!scr) return;
        const login = scr.querySelector('.s-login'), dash = scr.querySelector('.s-dash');
        const email = scr.querySelector('[data-f="email"]'), pass = scr.querySelector('[data-f="pass"]');
        const btn = scr.querySelector('.hw-lg-btn'), tap = scr.querySelector('.hw-tap');
        const view = scr.querySelector('.hw-dscroll'), list = scr.querySelector('.hw-dlist'), hi = scr.querySelector('.hw-hi');
        const cards = [...scr.querySelectorAll('.hw-card')], tasks = [...scr.querySelectorAll('.hw-task')];
        const data = JSON.parse(root.querySelector('[data-hw-phone]').textContent || '{}');
        const ping = (k) => root._hwPing && root._hwPing(k);
        if (still()) {
            login.classList.remove('is-on'); dash.classList.add('is-on');
            hi.classList.add('is-in'); cards.forEach((c) => c.classList.add('is-in'));
            if (tasks[0]) tasks[0].classList.add('is-done');
            ping(0);
            return;
        }
        let seen = false;
        new IntersectionObserver((es) => { seen = es.some((e) => e.isIntersecting); }).observe(root.querySelector('.hw-phone'));
        const until = async () => { while (!seen || document.hidden) await sleep(400); };
        const tapAt = (el) => {
            const sr = scr.getBoundingClientRect(), r = el.getBoundingClientRect();
            tap.style.left = (r.left - sr.left + r.width * .5) + 'px';
            tap.style.top = (r.top - sr.top + r.height * .55) + 'px';
            tap.classList.remove('is-tap'); void tap.offsetWidth; tap.classList.add('is-tap');
        };
        const type = async (fld, text, ms) => {
            const out = fld.querySelector('span');
            for (const ch of text) { await until(); out.textContent += ch; await sleep(ms); }
        };
        const reset = () => {
            [email, pass].forEach((f) => { f.classList.remove('is-focus'); f.querySelector('span').textContent = ''; });
            btn.classList.remove('is-press', 'is-busy'); btn.querySelector('b').textContent = 'Log In';
            dash.classList.remove('is-on', 'is-gone'); login.classList.remove('is-gone'); login.classList.add('is-on');
            hi.classList.remove('is-in'); cards.forEach((c) => c.classList.remove('is-in', 'is-hot'));
            tasks.forEach((t) => t.classList.remove('is-done'));
            list.style.transform = '';
            ping(-1);
        };
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
                btn.classList.remove('is-press'); btn.classList.add('is-busy'); btn.querySelector('b').textContent = 'Logging in'; await sleep(1100);
                login.classList.remove('is-on'); login.classList.add('is-gone'); dash.classList.add('is-on'); await sleep(450);
                hi.classList.add('is-in'); await sleep(500);
                for (let k = 0; k < cards.length; k++) {
                    await until();
                    cards.forEach((c) => c.classList.remove('is-hot'));
                    // The list rolls up when the next card would land below the screen.
                    const over = cards[k].offsetTop + cards[k].offsetHeight - (view.clientHeight - 8);
                    if (over > 0) list.style.transform = 'translateY(' + (-over) + 'px)';
                    cards[k].classList.add('is-in', 'is-hot');
                    ping(k);
                    if (k === 0) {
                        await sleep(1000); tasks[0] && tasks[0].classList.add('is-done');
                        await sleep(800); tasks[1] && tasks[1].classList.add('is-done');
                        await sleep(700);
                    } else {
                        await sleep(1900);
                    }
                }
                cards.forEach((c) => c.classList.remove('is-hot'));
                await sleep(2200);
                dash.classList.remove('is-on'); dash.classList.add('is-gone'); ping(-1);
                await sleep(800);
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
        const routes = [...st.querySelectorAll('.hw-item')].map((it, i) => {
            const base = make('path', { class: 'hw-arc', pathLength: 1 }), flow = make('path', { class: 'hw-flow', pathLength: 1 }), dest = make('circle', { class: 'hw-dest', r: 3.5 });
            [base, flow, dest].forEach((n) => n.style.setProperty('--i', i));
            gArcs.append(base, flow, dest);
            it._hw = { base, flow, dest };
            return { it, chip: it.querySelector('.hw-chip'), base, flow, dest };
        });
        let dotsW = 0;
        const draw = () => {
            const W = st.offsetWidth, H = st.offsetHeight;
            if (!W) return;
            svg.setAttribute('width', W); svg.setAttribute('height', H); svg.setAttribute('viewBox', `0 0 ${W} ${H}`);
            const [hx, hy] = hubAt(hub);
            routes.forEach((r) => {
                // Layout boxes (offset*), not screen boxes: the entrance and
                // the float move a chip, never where its route ends.
                const bl = r.it.offsetLeft, bw = r.it.offsetWidth;
                const ay = r.it.offsetTop + r.chip.offsetHeight / 2;
                const ax = bl + bw / 2 >= hx ? bl : bl + bw;
                const dx = ax - hx, dy = ay - hy;
                const d = Math.abs(dx) > 80
                    ? `M ${hx} ${hy} C ${hx + dx * 0.5} ${hy}, ${hx + dx * 0.5} ${ay}, ${ax} ${ay}`
                    : `M ${hx} ${hy} C ${hx} ${hy + dy * 0.8}, ${hx + dx * 0.15} ${ay}, ${ax} ${ay}`;
                r.base.setAttribute('d', d); r.flow.setAttribute('d', d);
                r.dest.setAttribute('cx', ax); r.dest.setAttribute('cy', ay);
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
                    dots(st, gDots, ...hubAt(hub), st.offsetWidth, st.offsetHeight);
                    st.getBoundingClientRect();
                    requestAnimationFrame(() => st.classList.add('is-in'));
                    // Once everything has landed, the routes are measured again from where it all rests.
                    setTimeout(draw, 1500);
                }
            });
        }, { threshold: 0.12 }).observe(st);
    }
    const hubAt = (hub) => [hub.offsetLeft + hub.offsetWidth / 2, hub.offsetTop + hub.offsetHeight / 2];

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
