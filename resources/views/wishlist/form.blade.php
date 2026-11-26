<!doctype html>
{{-- THE FIRST 50 (2026-11-26, the owner: "a wishlist form or wizard that i can market for the first 50 farmers, so
     create that (do not show any anee branding or links to the main site) ... surprise me"). App\Support\Wishlist.

     A page of its own: no layout, no app bundle, no link anywhere, no name, not in search results. One question to a
     screen, a big tap for every answer, Tagalog first with English one tap away, and the answers kept on the phone
     as they are given, so a dropped signal or a closed tab loses nothing. Nothing turns the page but the Next button.
     At the end: their place in line, a card to post, a link that moves them up, and three things to do this week. --}}
<html lang="tl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="robots" content="noindex, nofollow">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Ang Unang 50 | Ang hiling ng magsasaka</title>
    <meta name="description" content="Sabihin ang hiling mo para sa bukid. Ang unang 50 magsasaka ang mauunang makagamit.">
    <meta property="og:type" content="website">
    <meta property="og:title" content="Ang Unang 50: ano ang hiling mo para sa bukid?">
    <meta property="og:description" content="Dalawang minuto lang. Ang unang 50 magsasaka ang mauunang makagamit, at libre ang unang season.">
    <meta property="og:image" content="{{ url('/images/unang-50/share.png') }}">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:url" content="{{ url('/unang-50') }}">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="theme-color" content="#1f3d1a">
    <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'%3E%3Ctext y='.9em' font-size='90'%3E%F0%9F%8C%B1%3C/text%3E%3C/svg%3E">
    @include('partials.site-analytics')
    <style>
        @font-face { font-family: 'Display'; font-weight: 400 700; font-display: swap; src: url('/fonts/instrument-sans-latin.woff2?v=1') format('woff2'); }
        @font-face { font-family: 'Body'; font-weight: 400 800; font-display: swap; src: url('/fonts/nunito-sans-latin.woff2?v=1') format('woff2'); }
        :root {
            --ink: #1f2a1a; --soft: #5b6650; --line: #e6e0cf; --cream: #fbf7ec; --card: #ffffff;
            --green: #2d5016; --leaf: #4a7c2a; --sprout: #86b556; --mist: #eef5e4; --gold: #f5c518; --gold-dark: #c79e00;
            --ease: cubic-bezier(.22,1,.36,1);
        }
        * { box-sizing: border-box; }
        html, body { margin: 0; background: var(--cream); color: var(--ink); font-family: 'Body', ui-sans-serif, system-ui, sans-serif; -webkit-tap-highlight-color: transparent; }
        body { min-height: 100svh; }
        button, input, select, textarea { font: inherit; color: inherit; }
        .wrap { max-width: 34rem; margin: 0 auto; min-height: 100svh; display: flex; flex-direction: column; padding: 0 1rem calc(6.5rem + env(safe-area-inset-bottom)); }

        /* The bar along the top: back, how far, and the language. */
        .top { position: sticky; top: 0; z-index: 5; display: flex; align-items: center; gap: .6rem; padding: .75rem 0 .6rem; background: linear-gradient(var(--cream) 75%, rgb(251 247 236 / 0)); }
        .back { width: 2.4rem; height: 2.4rem; border-radius: 999px; border: 0; background: transparent; display: grid; place-items: center; cursor: pointer; transition: opacity .28s var(--ease), background .2s; }
        .back:active { background: var(--mist); }
        .back[hidden] { display: grid; visibility: hidden; opacity: 0; }
        .bar { flex: 1; height: .5rem; border-radius: 999px; background: var(--line); overflow: hidden; transition: opacity .28s var(--ease); }
        .bar i { display: block; height: 100%; width: 0; border-radius: 999px; background: linear-gradient(90deg, var(--leaf), var(--sprout)); transition: width .45s var(--ease); }
        .lang { display: inline-flex; padding: .2rem; border-radius: 999px; background: var(--card); border: 1px solid var(--line); }
        .lang button { border: 0; background: transparent; padding: .35rem .65rem; border-radius: 999px; font-size: .78rem; font-weight: 800; color: var(--soft); cursor: pointer; transition: background .28s var(--ease), color .28s var(--ease); }
        .lang button.is-on { background: var(--green); color: #fff; }

        /* The screens. One shows at a time; the next slides in from the side it comes from. */
        .stage { position: relative; flex: 1; }
        .step { display: none; }
        .step.is-on { display: block; animation: stepIn .42s var(--ease) both; }
        .step.is-on.from-back { animation-name: stepInBack; }
        @keyframes stepIn { from { opacity: 0; transform: translateX(28px); } }
        @keyframes stepInBack { from { opacity: 0; transform: translateX(-28px); } }
        .kicker { margin: .6rem 0 .3rem; font-size: .74rem; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; color: var(--leaf); }
        h1, h2 { font-family: 'Display', 'Body', sans-serif; margin: 0; line-height: 1.15; letter-spacing: -.01em; }
        h2 { font-size: 1.55rem; font-weight: 700; }
        .hint { margin: .45rem 0 0; color: var(--soft); font-size: .92rem; line-height: 1.5; }
        .sub { margin-top: 1.6rem; }
        .sub h3 { font-family: 'Display', 'Body', sans-serif; font-size: 1.08rem; margin: 0 0 .1rem; }

        /* The choices: big, with a picture, and they answer the finger at once. */
        .opts { display: grid; gap: .55rem; margin-top: 1rem; }
        .opts.is-grid { grid-template-columns: 1fr 1fr; }
        .opt { display: flex; align-items: center; gap: .75rem; width: 100%; min-height: 3.6rem; padding: .7rem .9rem; border-radius: 1rem; text-align: left; cursor: pointer;
            background: var(--card); border: 2px solid var(--line); font-weight: 700; font-size: .98rem; line-height: 1.3;
            transition: border-color .28s var(--ease), background .28s var(--ease), transform .18s var(--ease), box-shadow .28s var(--ease); }
        .opt:active { transform: scale(.98); }
        .opt .pic { flex: none; width: 2.3rem; height: 2.3rem; border-radius: .8rem; display: grid; place-items: center; font-size: 1.3rem; background: var(--mist); transition: transform .35s var(--ease); }
        .opt .pic.is-text { font-size: .78rem; font-weight: 800; color: var(--green); }
        .opt .tick { margin-left: auto; flex: none; width: 1.4rem; height: 1.4rem; border-radius: 999px; border: 2px solid var(--line); display: grid; place-items: center; transition: background .28s var(--ease), border-color .28s var(--ease); }
        .opt .tick svg { width: .8rem; height: .8rem; opacity: 0; transform: scale(.4); transition: opacity .25s var(--ease), transform .3s var(--ease); }
        .opt.is-on { border-color: var(--leaf); background: #f3f9ec; box-shadow: 0 6px 18px -12px rgb(45 80 22 / .6); }
        .opt.is-on .pic { transform: scale(1.1) rotate(-6deg); }
        .opt.is-on .tick { background: var(--leaf); border-color: var(--leaf); }
        .opt.is-on .tick svg { opacity: 1; transform: none; }
        .opts.is-grid .opt { flex-direction: column; align-items: flex-start; min-height: 6.2rem; }
        .opts.is-grid .opt .tick { position: absolute; top: .55rem; right: .55rem; }
        .opts.is-grid .opt { position: relative; }
        .opt.is-dim { opacity: .45; }

        .field { display: block; margin-top: 1rem; }
        .field span { display: block; font-size: .84rem; font-weight: 800; margin-bottom: .35rem; }
        .field small { font-weight: 600; color: var(--soft); }
        .input { width: 100%; min-height: 3.3rem; padding: .75rem .95rem; border-radius: 1rem; border: 2px solid var(--line); background: var(--card); font-size: 1.05rem; outline: none;
            transition: border-color .28s var(--ease), box-shadow .28s var(--ease); }
        .input:focus { border-color: var(--leaf); box-shadow: 0 0 0 4px rgb(134 181 86 / .25); }
        textarea.input { min-height: 7rem; resize: vertical; line-height: 1.5; }
        select.input { appearance: none; background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='14' height='14' viewBox='0 0 24 24' fill='none' stroke='%235b6650' stroke-width='2.5'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E"); background-repeat: no-repeat; background-position: right 1rem center; padding-right: 2.6rem; }
        .row { display: grid; grid-template-columns: 1fr 1fr; gap: .6rem; }
        .check { display: flex; gap: .7rem; align-items: flex-start; margin-top: 1.2rem; padding: .9rem; border-radius: 1rem; background: var(--card); border: 2px solid var(--line); cursor: pointer; font-size: .88rem; line-height: 1.5; transition: border-color .28s var(--ease); }
        .check input { width: 1.3rem; height: 1.3rem; margin-top: .1rem; accent-color: var(--leaf); flex: none; }
        .check.is-on { border-color: var(--leaf); }
        .note { margin-top: 1rem; padding: .8rem .95rem; border-radius: 1rem; background: var(--mist); color: var(--green); font-size: .88rem; font-weight: 700; line-height: 1.5; }

        /* The one button that turns the page, always in the same place. */
        .foot { position: fixed; left: 0; right: 0; bottom: 0; z-index: 6; padding: .8rem 1rem calc(.8rem + env(safe-area-inset-bottom)); background: linear-gradient(rgb(251 247 236 / 0), var(--cream) 30%); }
        .foot-in { max-width: 34rem; margin: 0 auto; display: flex; gap: .6rem; align-items: center; }
        .go { flex: 1; min-height: 3.5rem; border: 0; border-radius: 1.1rem; background: var(--gold); color: #1a1a1a; font-weight: 800; font-size: 1.06rem; cursor: pointer;
            box-shadow: 0 10px 24px -14px rgb(199 158 0 / .9); display: inline-flex; align-items: center; justify-content: center; gap: .5rem;
            transition: transform .18s var(--ease), opacity .28s var(--ease), background .28s var(--ease); }
        .go:active { transform: scale(.98); }
        .go[disabled] { opacity: .45; cursor: default; }
        .go svg { width: 1.1rem; height: 1.1rem; transition: transform .28s var(--ease); }
        .go:not([disabled]):hover svg { transform: translateX(3px); }
        .skip { border: 0; background: transparent; font-weight: 800; color: var(--soft); padding: .9rem .6rem; cursor: pointer; }
        .skip[hidden] { display: none; }
        .foot.is-gone { opacity: 0; transform: translateY(100%); pointer-events: none; }
        .foot { transition: opacity .3s var(--ease), transform .35s var(--ease); }
        .spin { width: 1.1rem; height: 1.1rem; border-radius: 999px; border: 2.5px solid rgb(0 0 0 / .2); border-top-color: #1a1a1a; animation: spin .7s linear infinite; }
        @keyframes spin { to { transform: rotate(360deg); } }
        .err { margin-top: .9rem; padding: .7rem .9rem; border-radius: .9rem; background: #fff1e6; color: #9a3412; font-weight: 700; font-size: .88rem; animation: stepIn .3s var(--ease) both; }
        .err[hidden] { display: none; }

        /* The first screen: a sunrise over the field, the count, and the promise. */
        .hero { margin: .2rem -1rem 0; position: relative; height: 13.5rem; overflow: hidden; border-radius: 0 0 2rem 2rem; background: linear-gradient(#ffe2a8, #fff3d6 55%, var(--cream)); }
        .hero svg { position: absolute; inset: 0; width: 100%; height: 100%; }
        .sun { animation: rise 2.4s var(--ease) both; transform-origin: center; transform-box: fill-box; }
        @keyframes rise { from { transform: translateY(40px); opacity: .2; } }
        .sprout { animation: grow 1.6s var(--ease) .9s both; transform-origin: 50% 100%; transform-box: fill-box; }
        @keyframes grow { from { transform: scaleY(0); } }
        .sway { animation: sway 5s ease-in-out infinite; transform-origin: 50% 100%; transform-box: fill-box; }
        @keyframes sway { 50% { transform: rotate(3deg); } }
        h1 { font-size: 2.35rem; font-weight: 700; margin-top: 1.1rem; }
        h1 em { font-style: normal; color: var(--leaf); }
        .lead { font-size: 1.05rem; line-height: 1.55; color: #36402e; margin: .7rem 0 0; }
        .gets { list-style: none; margin: 1.1rem 0 0; padding: 0; display: grid; gap: .5rem; }
        .gets li { display: flex; gap: .6rem; align-items: flex-start; font-weight: 700; line-height: 1.4; animation: stepIn .5s var(--ease) both; }
        .gets li:nth-child(2) { animation-delay: .08s; } .gets li:nth-child(3) { animation-delay: .16s; }
        .gets b { flex: none; width: 1.5rem; height: 1.5rem; border-radius: 999px; background: var(--leaf); color: #fff; display: grid; place-items: center; font-size: .8rem; }
        .count { display: flex; align-items: center; gap: .9rem; margin-top: 1.3rem; padding: .9rem 1rem; border-radius: 1.2rem; background: var(--card); border: 1px solid var(--line); }
        .ring { flex: none; width: 3.6rem; height: 3.6rem; position: relative; }
        .ring svg { width: 100%; height: 100%; transform: rotate(-90deg); }
        .ring circle { fill: none; stroke-width: 6; }
        .ring .bg { stroke: var(--line); }
        .ring .fg { stroke: var(--gold); stroke-linecap: round; transition: stroke-dashoffset 1.4s var(--ease) .3s; }
        .ring b { position: absolute; inset: 0; display: grid; place-items: center; font-family: 'Display', sans-serif; font-size: 1.15rem; }
        .count p { margin: 0; font-weight: 800; line-height: 1.35; }
        .count small { display: block; font-weight: 600; color: var(--soft); }
        .resume { margin-top: 1rem; display: flex; gap: .6rem; align-items: center; padding: .8rem .95rem; border-radius: 1rem; background: var(--mist); font-weight: 700; font-size: .9rem; }
        .resume[hidden] { display: none; }
        .resume button { margin-left: auto; border: 0; background: transparent; color: var(--leaf); font-weight: 800; cursor: pointer; }

        /* The last screen. */
        .done-top { text-align: center; padding-top: .8rem; }
        .place { font-family: 'Display', sans-serif; font-size: 4.4rem; line-height: 1; color: var(--green); margin: .6rem 0 .2rem; }
        .done-top p { margin: .3rem 0 0; color: var(--soft); line-height: 1.5; }
        .cardbox { margin-top: 1.2rem; border-radius: 1.4rem; overflow: hidden; box-shadow: 0 24px 40px -28px rgb(31 42 26 / .7); animation: pop .6s var(--ease) .2s both; }
        @keyframes pop { from { opacity: 0; transform: translateY(14px) scale(.96); } }
        .cardbox canvas { display: block; width: 100%; height: auto; }
        .btns { display: grid; grid-template-columns: 1fr 1fr; gap: .5rem; margin-top: .7rem; }
        .btn { min-height: 3rem; border-radius: .95rem; border: 2px solid var(--line); background: var(--card); font-weight: 800; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; gap: .4rem; text-decoration: none; color: var(--ink); font-size: .92rem; transition: transform .18s var(--ease), border-color .28s var(--ease); }
        .btn:active { transform: scale(.98); }
        .btn.is-gold { background: var(--gold); border-color: var(--gold); }
        .box { margin-top: 1.2rem; padding: 1rem; border-radius: 1.2rem; background: var(--card); border: 1px solid var(--line); }
        .box h3 { font-family: 'Display', sans-serif; margin: 0; font-size: 1.1rem; }
        .box p { margin: .35rem 0 0; color: var(--soft); line-height: 1.5; font-size: .92rem; }
        .link { display: flex; gap: .4rem; margin-top: .7rem; }
        .link input { flex: 1; min-width: 0; min-height: 2.9rem; border-radius: .85rem; border: 2px solid var(--line); padding: 0 .8rem; font-weight: 700; background: var(--cream); }
        .shares { display: grid; grid-template-columns: repeat(3, 1fr); gap: .45rem; margin-top: .55rem; }
        .tips { list-style: none; margin: .7rem 0 0; padding: 0; display: grid; gap: .6rem; }
        .tips li { display: flex; gap: .7rem; align-items: flex-start; line-height: 1.5; font-size: .93rem; }
        .tips li span { flex: none; width: 2.2rem; height: 2.2rem; border-radius: .8rem; background: var(--mist); display: grid; place-items: center; font-size: 1.2rem; }
        .again { display: block; margin: 1.2rem auto 0; border: 0; background: transparent; color: var(--soft); font-weight: 800; text-decoration: underline; cursor: pointer; }
        .leaves { position: fixed; inset: 0; pointer-events: none; z-index: 8; overflow: hidden; }
        .leaves i { position: absolute; top: -2rem; font-style: normal; font-size: 1.4rem; animation: fall linear forwards; }
        @keyframes fall { to { transform: translateY(110vh) rotate(540deg); opacity: .2; } }
        .toast { position: fixed; left: 50%; bottom: calc(5.5rem + env(safe-area-inset-bottom)); transform: translate(-50%, 1rem); opacity: 0; z-index: 9; background: var(--green); color: #fff; font-weight: 800; padding: .7rem 1.1rem; border-radius: 999px; transition: opacity .28s var(--ease), transform .28s var(--ease); pointer-events: none; }
        .toast.is-on { opacity: 1; transform: translate(-50%, 0); }
        .hp { position: absolute; left: -9999px; width: 1px; height: 1px; overflow: hidden; }

        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after { animation-duration: .001ms !important; animation-iteration-count: 1 !important; transition-duration: .001ms !important; }
        }
    </style>
</head>
<body>
<div class="wrap">
    <div class="top">
        <button type="button" class="back" id="back" aria-label="Bumalik" hidden>
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M15 19l-7-7 7-7"/></svg>
        </button>
        <div class="bar" id="bar" aria-hidden="true"><i></i></div>
        <div class="lang" role="group" aria-label="Language">
            <button type="button" data-lang="tl" class="is-on">Tagalog</button>
            <button type="button" data-lang="en">English</button>
        </div>
    </div>
    <main class="stage" id="stage" aria-live="polite"></main>
</div>
<div class="foot" id="foot">
    <div class="foot-in">
        <button type="button" class="skip" id="skip" hidden></button>
        <button type="button" class="go" id="go"></button>
    </div>
</div>
<div class="toast" id="toast" role="status"></div>
<div class="leaves" id="leaves" aria-hidden="true"></div>

<script>
(() => {
    const C = @json($choices);
    const LIMIT = @json($limit);
    let SPOTS = @json($spots);
    const OFFER = @json($offer);
    const REF = @json($ref);
    const SAVE_URL = @json(route('wishlist.save', [], false));
    const PLACES_URL = '/data/ph-locations.json';
    const KEY = 'u50.v1';
    const $ = (s, el = document) => el.querySelector(s);
    const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const tick = '<svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12.5l4.5 4.5L19 7.5"/></svg>';
    const arrow = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>';

    /* Everything said on the page, in both languages. */
    const T = {
        tl: {
            kicker: 'Para sa mga magsasakang gustong mauna', title: 'Ang <em>Unang 50</em>',
            lead: 'May binubuo kaming katulong sa bukid na nasa telepono mo. Bago namin ito ilabas, gusto muna naming marinig ang hiling mo, para ikaw mismo ang humubog nito.',
            left: (n) => n > 0 ? `${n} na puwesto na lang ang natitira` : 'Puno na ang unang 50', leftSub: (n) => n > 0 ? `sa ${LIMIT} na magsasaka` : 'Pero kasama ka pa rin sa susunod na batch',
            start: 'Ibigay ang hiling ko', minutes: 'Dalawang minuto lang', resume: 'May naiwan kang sagot.', resumeGo: 'Ituloy', restart: 'Magsimula muli',
            next: 'Susunod', skip: 'Laktawan', send: 'Ipadala ang hiling ko', sending: 'Ipinapadala…', back: 'Bumalik',
            name: 'Una sa lahat, ano ang pangalan mo?', first: 'Pangalan', last: 'Apelyido', optional: 'kung gusto mo',
            role: 'Alin ang pinakamalapit sa iyo?',
            place: 'Saan ang bukid mo?', province: 'Probinsya', town: 'Bayan o lungsod', pick: 'Piliin', placeHint: 'Para malaman namin ang panahon at lupa sa lugar mo.',
            crops: 'Ano ang tanim mo?', cropsHint: 'Piliin lahat ng tanim mo.', seasons: 'Ilang beses kayo nagtatanim sa isang taon?',
            size: 'Gaano kalaki ang sakahan mo?', lots: 'Ilang lote o pitak?', helpers: 'Sino ang katulong mo sa bukid?',
            pains: 'Ano ang pinakamabigat sa pagsasaka mo ngayon?', painsHint: 'Pumili ng hanggang tatlo.',
            wishKicker: 'Ang hiling mo', wishes: 'Kung may katulong ka sa bukid na laging nasa telepono mo, ano ang gusto mong gawin niya?', wishesHint: 'Pumili ng kahit ilan.',
            wishText: 'May iba ka pang hiling?', wishTextHint: 'Isulat dito, kahit Ilocano, Bisaya o anumang wika.', wishPh: 'Halimbawa: Sana masabihan ako kapag may bagyong darating bago ako mag-abono.',
            yield: 'Ilan ang ani mo noong huling season?', yieldHint: 'Kung hindi mo matandaan, ayos lang. Pwede mong laktawan ito.', yieldNum: 'Ani',
            phone: 'Anong telepono ang gamit mo?', signal: 'Paano ang signal sa bukid?',
            payKicker: 'Wala kang babayaran ngayon', pay: 'Kung makakatulong ito na tumaas ang ani mo, paano mo gustong magbayad?', payAmount: 'Magkano ang patas para sa isang buong season?',
            payNote: 'Tanong lang ito para malaman namin ang patas para sa magsasaka. Wala kang babayaran sa pagsagot.',
            heard: 'Saan mo nalaman ito?',
            contact: 'Paano ka namin makakausap?', contactHint: 'Dito ka namin sasabihan kung kasama ka sa unang 50.', mobile: 'Mobile number', email: 'Email', emailOpt: 'kung meron',
            via: 'Saan ka mas madaling makausap?', time: 'Anong oras ang pinakamaganda?',
            consent: 'Pumapayag akong gamitin ang mga sagot ko para mapili ang unang 50 at para makausap ako tungkol dito. Hindi ito ibebenta o ibibigay kaninuman.',
            needPhone: 'Pakilagay ang mobile number mo (09XX XXX XXXX) o email.', needConsent: 'Pakitsek ang pahintulot sa itaas.', offline: 'Walang signal ngayon. Naitabi namin ang sagot mo, subukan ulit kapag may signal.',
            thanks: (n) => `Salamat, ${n}!`, inLine: 'ang puwesto mo sa Unang 50', full: (p) => `Puno na ang unang 50, pero kasama ka sa susunod na batch. Ang puwesto mo: #${p}.`,
            again: 'Natanggap na namin ito dati. Na-update ang mga sagot mo, at nasa dati ka pa ring puwesto.',
            cardTitle: 'Ang card mo', download: 'I-download', share: 'Ibahagi',
            climb: 'Umakyat sa listahan', climbSay: `Bawat kaibigang magsasaka na sasagot gamit ang link mo, aakyat ka ng ${3} puwesto.`, friends: (n) => n ? `${n} kaibigan na ang sumali gamit ang link mo.` : '',
            copy: 'Kopyahin', copied: 'Nakopya ang link', shareText: 'Sumali ako sa Unang 50 para sa mga magsasaka. Sabihin mo rin ang hiling mo para sa bukid:',
            tipsTitle: 'Habang naghihintay', tipsSay: 'Tatlong bagay na pwede mong gawin ngayong linggo, para sa sinabi mong pinakamabigat:',
            nextTitle: 'Ano ang susunod?', nextSay: (v) => `Kokontakin ka namin${v ? ' sa ' + v : ''} sa loob ng isang linggo para sa susunod na hakbang.`, edit: 'Baguhin ang sagot ko',
            cardKicker: 'ANG UNANG 50', cardFarmer: 'Magsasaka', cardWish: 'Ang hiling ko:', of: 'ng',
        },
        en: {
            kicker: 'For farmers who want to go first', title: 'The <em>First 50</em>',
            lead: 'We are building a farm helper that lives in your phone. Before we open it, we want to hear what you wish it could do, so you shape it yourself.',
            left: (n) => n > 0 ? `${n} places left` : 'The first 50 are full', leftSub: (n) => n > 0 ? `of ${LIMIT} farmers` : 'You still join the next group',
            start: 'Give my wish', minutes: 'Just two minutes', resume: 'You left some answers.', resumeGo: 'Continue', restart: 'Start again',
            next: 'Next', skip: 'Skip', send: 'Send my wish', sending: 'Sending…', back: 'Back',
            name: 'First of all, what is your name?', first: 'First name', last: 'Last name', optional: 'if you like',
            role: 'Which one is closest to you?',
            place: 'Where is your farm?', province: 'Province', town: 'Town or city', pick: 'Choose', placeHint: 'So we know the weather and soil where you are.',
            crops: 'What do you grow?', cropsHint: 'Pick everything you grow.', seasons: 'How many times a year do you plant?',
            size: 'How big is your farm?', lots: 'How many lots or plots?', helpers: 'Who helps you on the farm?',
            pains: 'What is hardest about farming for you right now?', painsHint: 'Pick up to three.',
            wishKicker: 'Your wish', wishes: 'If you had a farm helper always in your phone, what would you want it to do?', wishesHint: 'Pick as many as you like.',
            wishText: 'Any other wish?', wishTextHint: 'Write it here, in any language.', wishPh: 'For example: Tell me when a storm is coming before I fertilize.',
            yield: 'How much did you harvest last season?', yieldHint: 'If you do not remember, that is fine. You can skip this.', yieldNum: 'Harvest',
            phone: 'What phone do you use?', signal: 'How is the signal on the farm?',
            payKicker: 'You pay nothing now', pay: 'If it helped you harvest more, how would you like to pay?', payAmount: 'What is fair for a whole season?',
            payNote: 'This is only a question, so we know what is fair for farmers. Answering costs nothing.',
            heard: 'Where did you hear about this?',
            contact: 'How can we reach you?', contactHint: 'This is where we tell you if you are in the first 50.', mobile: 'Mobile number', email: 'Email', emailOpt: 'if you have one',
            via: 'Where are you easiest to reach?', time: 'What time is best?',
            consent: 'I agree that my answers are used to choose the first 50 and to contact me about it. They are never sold or given to anyone.',
            needPhone: 'Please give your mobile number (09XX XXX XXXX) or an email.', needConsent: 'Please tick the consent box above.', offline: 'No signal right now. Your answers are kept; try again when you have signal.',
            thanks: (n) => `Thank you, ${n}!`, inLine: 'your place in the First 50', full: (p) => `The first 50 are full, but you are in the next group. Your place: #${p}.`,
            again: 'We had your answers already. They are updated, and you keep your place.',
            cardTitle: 'Your card', download: 'Download', share: 'Share',
            climb: 'Move up the list', climbSay: `For every farmer friend who answers through your link, you move up ${3} places.`, friends: (n) => n ? `${n} friend${n === 1 ? '' : 's'} joined through your link.` : '',
            copy: 'Copy', copied: 'Link copied', shareText: 'I joined the First 50 for farmers. Tell them your wish for the farm too:',
            tipsTitle: 'While you wait', tipsSay: 'Three things you can do this week, for what you said is hardest:',
            nextTitle: 'What happens next?', nextSay: (v) => `We will contact you${v ? ' on ' + v : ''} within a week for the next step.`, edit: 'Change my answers',
            cardKicker: 'THE FIRST 50', cardFarmer: 'Farmer', cardWish: 'My wish:', of: 'of',
        },
    };

    /* The questions, in order. Each says what must be answered before Next works. */
    const STEPS = [
        { id: 'intro' },
        { id: 'name', ok: (a) => (a.firstName || '').trim().length > 0 },
        { id: 'role', ok: (a) => !!a.role },
        { id: 'place', ok: (a) => !!a.province },
        { id: 'crops', ok: (a) => (a.crops || []).length > 0 && !!a.seasons },
        { id: 'size', ok: (a) => !!a.farmSize && !!a.helpers },
        { id: 'pains', ok: (a) => (a.pains || []).length > 0 },
        { id: 'wishes', ok: (a) => (a.wishes || []).length > 0 || (a.wishText || '').trim().length > 2 },
        { id: 'yield', ok: () => true, skippable: true },
        { id: 'phone', ok: (a) => !!a.phoneType && !!a.signal },
        { id: 'pay', ok: (a) => !!a.payWay },
        { id: 'heard', ok: () => true, skippable: true },
        { id: 'contact', ok: (a) => !!a.consent && (/^(63|0)?9\d{9}$/.test((a.phone || '').replace(/\D+/g, '')) || /.+@.+\..+/.test(a.email || '')) },
        { id: 'done' },
    ];
    const QN = STEPS.length - 2;   // the questions between the first screen and the last

    const DONE = STEPS.length - 1;
    const read = () => { try { return JSON.parse(localStorage.getItem(KEY) || 'null') || {}; } catch (_) { return {}; } };
    const S = Object.assign({ lang: 'tl', at: 0, a: { crops: [], pains: [], wishes: [] }, done: null }, read());
    // Back on the page with answers half given: the first screen offers to carry on from there.
    let resumeAt = 0;
    if (S.done) S.at = DONE; else { resumeAt = S.at > 0 && S.at < DONE ? S.at : 0; S.at = 0; }
    const keep = () => { try { localStorage.setItem(KEY, JSON.stringify(Object.assign({}, S, { at: S.at === 0 && resumeAt ? resumeAt : S.at }))); } catch (_) { /* a private window keeps nothing */ } };
    const A = S.a;
    const t = (k, ...x) => { const v = T[S.lang][k]; return typeof v === 'function' ? v(...x) : v; };
    const word = (q, k) => { const o = (C[q] || []).find((x) => x.k === k); return o ? o[S.lang] : ''; };

    // Where they came from (an ad, a friend's link), kept with the answers.
    const qs = new URLSearchParams(location.search);
    const UTM = {};
    ['utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term', 'fbclid'].forEach((k) => { if (qs.get(k)) UTM[k] = qs.get(k); });
    if (REF) A.ref = A.ref || REF;

    const opts = (q, field, multi, max, grid) => `<div class="opts ${grid ? 'is-grid' : ''}" data-field="${field}" data-multi="${multi ? 1 : 0}" data-max="${max || 0}">${(C[q] || []).map((o) => {
        const on = multi ? (A[field] || []).includes(o.k) : A[field] === o.k;
        const pic = o.pic ? `<span class="pic ${/^[\w₱·+.]+$/u.test(o.pic) ? 'is-text' : ''}">${esc(o.pic)}</span>` : '';
        return `<button type="button" class="opt ${on ? 'is-on' : ''}" data-k="${esc(o.k)}" aria-pressed="${on}">${pic}<span>${esc(o[S.lang])}</span><span class="tick">${tick}</span></button>`;
    }).join('')}</div>`;

    /* Each screen, drawn from the answers so far. */
    const SCREENS = {
        intro: () => `
            <div class="hero" aria-hidden="true">
                <svg viewBox="0 0 400 216" preserveAspectRatio="xMidYMax slice">
                    <circle class="sun" cx="300" cy="78" r="34" fill="#ffc53d"/>
                    <circle class="sun" cx="300" cy="78" r="52" fill="#ffc53d" opacity=".18"/>
                    <path d="M0 150 Q 100 112 200 140 T 400 128 V216 H0Z" fill="#a8cc7e"/>
                    <path d="M0 172 Q 120 140 240 166 T 400 156 V216 H0Z" fill="#6b9f3d"/>
                    <path d="M0 196 Q 140 170 280 192 T 400 186 V216 H0Z" fill="#4a7c2a"/>
                    <g stroke="#3d6823" stroke-width="2" opacity=".55" fill="none">
                        <path d="M20 205 Q 80 186 140 200"/><path d="M160 208 Q 230 188 300 202"/><path d="M300 205 Q 350 192 400 198"/>
                    </g>
                    <g class="sprout"><g class="sway">
                        <path d="M118 188 C118 170 118 158 120 146" stroke="#2d5016" stroke-width="4" stroke-linecap="round" fill="none"/>
                        <path d="M120 160 C 104 156 96 144 98 132 C 112 134 120 144 120 160Z" fill="#86b556"/>
                        <path d="M121 150 C 136 146 146 134 144 120 C 128 124 120 136 121 150Z" fill="#6b9f3d"/>
                    </g></g>
                </svg>
            </div>
            <p class="kicker">${t('kicker')}</p>
            <h1>${t('title')}</h1>
            <p class="lead">${t('lead')}</p>
            <ul class="gets">${(OFFER[S.lang] || []).map((x) => `<li><b>✓</b><span>${esc(x)}</span></li>`).join('')}</ul>
            <div class="count">
                <div class="ring"><svg viewBox="0 0 64 64"><circle class="bg" cx="32" cy="32" r="27"/><circle class="fg" id="ringFg" cx="32" cy="32" r="27" stroke-dasharray="169.6" stroke-dashoffset="169.6"/></svg><b>${Math.max(0, SPOTS)}</b></div>
                <p>${t('left', SPOTS)}<small>${t('leftSub', SPOTS)}</small></p>
            </div>
            <div class="resume" id="resume" ${resumeAt ? '' : 'hidden'}><span>${t('resume')}</span><button type="button" data-restart>${t('restart')}</button></div>`,
        name: () => `
            <h2>${t('name')}</h2>
            <label class="field"><span>${t('first')}</span><input class="input" data-in="firstName" autocomplete="given-name" value="${esc(A.firstName)}" maxlength="60"></label>
            <label class="field"><span>${t('last')} <small>(${t('optional')})</small></span><input class="input" data-in="lastName" autocomplete="family-name" value="${esc(A.lastName)}" maxlength="60"></label>`,
        role: () => `<h2>${t('role')}</h2>${opts('role', 'role')}`,
        place: () => `
            <h2>${t('place')}</h2><p class="hint">${t('placeHint')}</p>
            <label class="field"><span>${t('province')}</span><select class="input" data-in="province" id="prov"><option value="">${t('pick')}…</option></select></label>
            <label class="field"><span>${t('town')}</span><select class="input" data-in="town" id="town"><option value="">${t('pick')}…</option></select></label>`,
        crops: () => `
            <h2>${t('crops')}</h2><p class="hint">${t('cropsHint')}</p>${opts('crops', 'crops', true, 0, true)}
            <div class="sub"><h3>${t('seasons')}</h3>${opts('seasons', 'seasons', false, 0, true)}</div>`,
        size: () => `
            <h2>${t('size')}</h2>${opts('farmSize', 'farmSize')}
            <div class="sub"><h3>${t('lots')}</h3>${opts('lots', 'lots', false, 0, true)}</div>
            <div class="sub"><h3>${t('helpers')}</h3>${opts('helpers', 'helpers')}</div>`,
        pains: () => `<h2>${t('pains')}</h2><p class="hint">${t('painsHint')}</p>${opts('pains', 'pains', true, 3)}`,
        wishes: () => `
            <p class="kicker">${t('wishKicker')}</p><h2>${t('wishes')}</h2><p class="hint">${t('wishesHint')}</p>${opts('wishes', 'wishes', true)}
            <label class="field"><span>${t('wishText')} <small>${t('wishTextHint')}</small></span><textarea class="input" data-in="wishText" maxlength="600" placeholder="${esc(t('wishPh'))}">${esc(A.wishText)}</textarea></label>`,
        yield: () => `
            <h2>${t('yield')}</h2><p class="hint">${t('yieldHint')}</p>
            <label class="field"><span>${t('yieldNum')}</span><input class="input" data-in="yieldNum" inputmode="decimal" value="${esc(A.yieldNum)}" placeholder="0"></label>
            ${opts('yieldUnit', 'yieldUnit')}`,
        phone: () => `<h2>${t('phone')}</h2>${opts('phoneType', 'phoneType', false, 0, true)}<div class="sub"><h3>${t('signal')}</h3>${opts('signal', 'signal')}</div>`,
        pay: () => `
            <p class="kicker">${t('payKicker')}</p><h2>${t('pay')}</h2>${opts('payWay', 'payWay')}
            <div class="sub"><h3>${t('payAmount')}</h3>${opts('payAmount', 'payAmount', false, 0, true)}</div>
            <div class="note">${t('payNote')}</div>`,
        heard: () => `<h2>${t('heard')}</h2>${opts('heardFrom', 'heardFrom')}`,
        contact: () => `
            <h2>${t('contact')}</h2><p class="hint">${t('contactHint')}</p>
            <label class="field"><span>${t('mobile')}</span><input class="input" data-in="phone" type="tel" inputmode="tel" autocomplete="tel" placeholder="09XX XXX XXXX" value="${esc(A.phone)}" maxlength="18"></label>
            <label class="field"><span>${t('email')} <small>(${t('emailOpt')})</small></span><input class="input" data-in="email" type="email" inputmode="email" autocomplete="email" value="${esc(A.email)}" maxlength="120"></label>
            <div class="sub"><h3>${t('via')}</h3>${opts('contactVia', 'contactVia', false, 0, true)}</div>
            <div class="sub"><h3>${t('time')}</h3>${opts('contactTime', 'contactTime', false, 0, true)}</div>
            <label class="check ${A.consent ? 'is-on' : ''}"><input type="checkbox" data-in="consent" ${A.consent ? 'checked' : ''}><span>${t('consent')}</span></label>
            <label class="hp">Website <input data-in="website" tabindex="-1" autocomplete="off"></label>
            <p class="err" id="err" hidden></p>`,
        done: () => {
            const d = S.done || {};
            const link = location.origin + '/unang-50?r=' + encodeURIComponent(d.code || '');
            const full = d.spots === 0 && d.position > LIMIT;
            return `
            <div class="done-top">
                <p class="kicker">${t('cardKicker')}</p>
                <h2>${esc(t('thanks', A.firstName || ''))}</h2>
                ${full ? `<p>${esc(t('full', d.position))}</p>` : `<div class="place" id="place" data-n="${d.position || 1}">#1</div><p>${t('inLine')}</p>`}
                ${d.again ? `<p>${t('again')}</p>` : ''}
            </div>
            <div class="cardbox"><canvas id="card" width="1080" height="1350" aria-label="${esc(t('cardTitle'))}"></canvas></div>
            <div class="btns"><button type="button" class="btn" data-download>⬇ ${t('download')}</button><button type="button" class="btn is-gold" data-share-card>↗ ${t('share')}</button></div>
            <div class="box">
                <h3>${t('climb')}</h3><p>${t('climbSay')}</p>${d.friends ? `<p><b>${esc(t('friends', d.friends))}</b></p>` : ''}
                <div class="link"><input readonly value="${esc(link)}" id="mylink" aria-label="Link"><button type="button" class="btn" data-copy>${t('copy')}</button></div>
                <div class="shares">
                    <a class="btn" target="_blank" rel="noopener" href="https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(link)}">Facebook</a>
                    <a class="btn" href="fb-messenger://share/?link=${encodeURIComponent(link)}">Messenger</a>
                    <a class="btn" href="viber://forward?text=${encodeURIComponent(t('shareText') + ' ' + link)}">Viber</a>
                </div>
            </div>
            <div class="box"><h3>${t('tipsTitle')}</h3><p>${t('tipsSay')}</p><ul class="tips">${(d.tips || []).map((x) => `<li><span>${esc(x.pic)}</span><div>${esc(x.say)}</div></li>`).join('')}</ul></div>
            <div class="box"><h3>${t('nextTitle')}</h3><p>${esc(t('nextSay', A.contactVia ? word('contactVia', A.contactVia) : ''))}</p></div>
            <button type="button" class="again" data-edit>${t('edit')}</button>`;
        },
    };

    const stage = $('#stage'), go = $('#go'), skip = $('#skip'), back = $('#back'), foot = $('#foot');
    let dir = 'fwd';

    function paint() {
        const step = STEPS[S.at];
        stage.innerHTML = `<section class="step is-on ${dir === 'back' ? 'from-back' : ''}" data-step="${step.id}">${SCREENS[step.id]()}</section>`;
        document.documentElement.lang = S.lang === 'en' ? 'en' : 'tl';
        document.querySelectorAll('[data-lang]').forEach((b) => b.classList.toggle('is-on', b.dataset.lang === S.lang));
        $('#bar i').style.width = (step.id === 'intro' ? 0 : Math.min(100, Math.round((Math.min(S.at, QN) / QN) * 100))) + '%';
        $('#bar').style.opacity = step.id === 'intro' || step.id === 'done' ? '0' : '1';
        back.hidden = step.id === 'intro' || step.id === 'done';
        back.setAttribute('aria-label', t('back'));
        foot.classList.toggle('is-gone', step.id === 'done');
        skip.hidden = !step.skippable;
        skip.textContent = t('skip');
        go.innerHTML = step.id === 'intro' ? `${resumeAt ? t('resumeGo') : t('start')} ${arrow}`
            : step.id === 'contact' ? `${t('send')} ${arrow}` : `${t('next')} ${arrow}`;
        state();
        if (step.id === 'intro') requestAnimationFrame(() => requestAnimationFrame(() => {
            const fg = $('#ringFg');
            if (fg) fg.style.strokeDashoffset = String(169.6 * (1 - Math.max(0, Math.min(1, SPOTS / LIMIT))));
        }));
        if (step.id === 'place') places();
        if (step.id === 'done') finish();
        window.scrollTo({ top: 0, behavior: 'auto' });
    }
    function state() {
        const step = STEPS[S.at];
        go.disabled = !(step.id === 'intro' || (step.ok ? step.ok(A) : true));
    }

    // A tap on a choice: one answer, or several up to the limit. It never turns the page.
    stage.addEventListener('click', (e) => {
        const b = e.target.closest('.opt');
        if (b) {
            const box = b.closest('.opts');
            const field = box.dataset.field, multi = box.dataset.multi === '1', max = Number(box.dataset.max) || 0;
            if (multi) {
                const list = A[field] = A[field] || [];
                const i = list.indexOf(b.dataset.k);
                if (i >= 0) list.splice(i, 1); else if (!max || list.length < max) list.push(b.dataset.k); else return nudge(b);
                box.querySelectorAll('.opt').forEach((o) => { const on = list.includes(o.dataset.k); o.classList.toggle('is-on', on); o.setAttribute('aria-pressed', on); o.classList.toggle('is-dim', !!max && list.length >= max && !on); });
            } else {
                A[field] = A[field] === b.dataset.k && STEPS[S.at].skippable ? null : b.dataset.k;
                box.querySelectorAll('.opt').forEach((o) => { const on = o.dataset.k === A[field]; o.classList.toggle('is-on', on); o.setAttribute('aria-pressed', on); });
            }
            keep(); state(); return;
        }
        if (e.target.closest('[data-restart]')) { resumeAt = 0; S.at = 0; Object.keys(A).forEach((k) => { if (k !== 'ref') delete A[k]; }); Object.assign(A, { crops: [], pains: [], wishes: [] }); keep(); paint(); return; }
        if (e.target.closest('[data-copy]')) { copy($('#mylink').value); return; }
        if (e.target.closest('[data-download]')) { download(); return; }
        if (e.target.closest('[data-share-card]')) { shareCard(); return; }
        if (e.target.closest('[data-edit]')) { S.done = null; S.at = 1; resumeAt = 0; finish.fell = false; dir = 'back'; keep(); paint(); }
    });
    const nudge = (el) => el.animate?.([{ transform: 'translateX(0)' }, { transform: 'translateX(-5px)' }, { transform: 'translateX(5px)' }, { transform: 'translateX(0)' }], { duration: 260 });
    const onIn = (e) => {
        const f = e.target.dataset && e.target.dataset.in;
        if (!f) return;
        A[f] = e.target.type === 'checkbox' ? e.target.checked : e.target.value;
        if (f === 'consent') e.target.closest('.check').classList.toggle('is-on', e.target.checked);
        if (f === 'province') { A.town = ''; fillTowns(); }
        keep(); state();
    };
    // Both, because an older phone's list box says only "change".
    stage.addEventListener('input', onIn);
    stage.addEventListener('change', onIn);

    go.addEventListener('click', async () => {
        if (go.disabled) return;
        const step = STEPS[S.at];
        if (step.id === 'contact') return send();
        if (step.id === 'intro' && resumeAt) { S.at = resumeAt; resumeAt = 0; }
        else S.at = Math.min(S.at + 1, STEPS.length - 2);
        dir = 'fwd'; keep(); paint();
    });
    skip.addEventListener('click', () => { S.at = Math.min(S.at + 1, STEPS.length - 2); dir = 'fwd'; keep(); paint(); });
    back.addEventListener('click', () => { S.at = Math.max(0, S.at - 1); dir = 'back'; keep(); paint(); });
    document.querySelectorAll('[data-lang]').forEach((b) => b.addEventListener('click', () => { if (S.lang === b.dataset.lang) return; S.lang = b.dataset.lang; keep(); dir = 'fwd'; paint(); }));

    /* Provinces and towns, from the site's own list. */
    let PLACES = null;
    async function places() {
        if (!PLACES) {
            try { PLACES = await (await fetch(PLACES_URL)).json(); } catch (_) { PLACES = {}; }
        }
        const prov = $('#prov');
        if (!prov) return;
        prov.insertAdjacentHTML('beforeend', Object.keys(PLACES).sort().map((p) => `<option ${p === A.province ? 'selected' : ''}>${esc(p)}</option>`).join(''));
        fillTowns();
    }
    function fillTowns() {
        const town = $('#town');
        if (!town || !PLACES) return;
        const list = (PLACES[A.province] || []).slice().sort();
        town.innerHTML = `<option value="">${t('pick')}…</option>` + list.map((x) => `<option ${x === A.town ? 'selected' : ''}>${esc(x)}</option>`).join('');
        town.disabled = !list.length;
    }

    /* Sending: once, kept on the phone until the server says it has it. */
    async function send() {
        const err = $('#err');
        const digits = (A.phone || '').replace(/\D+/g, '');
        const phoneOk = /^(63|0)?9\d{9}$/.test(digits);
        if (!phoneOk && !/.+@.+\..+/.test(A.email || '')) { err.textContent = t('needPhone'); err.hidden = false; return; }
        if (!A.consent) { err.textContent = t('needConsent'); err.hidden = false; return; }
        err.hidden = true;
        go.disabled = true;
        go.innerHTML = `<span class="spin"></span> ${t('sending')}`;
        try {
            const res = await fetch(SAVE_URL, {
                method: 'POST', credentials: 'same-origin',
                headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': $('meta[name=csrf-token]').content },
                body: JSON.stringify(Object.assign({}, A, { lang: S.lang, utm: UTM, website: $('[data-in=website]')?.value || '' })),
            });
            const body = await res.json().catch(() => ({}));
            if (!res.ok || !body.success) throw Object.assign(new Error(body.message || 'Error'), { server: true });
            S.done = body.data; SPOTS = body.data.spots; S.at = STEPS.length - 1; dir = 'fwd'; keep(); paint();
        } catch (e) {
            err.textContent = e.server ? e.message : t('offline'); err.hidden = false;
            go.disabled = false; go.innerHTML = `${t('send')} ${arrow}`;
        }
    }

    /* The last screen: the number counts up, leaves fall once, and the card draws itself. */
    function finish() {
        const p = $('#place');
        if (p) {
            const n = Number(p.dataset.n) || 1, t0 = performance.now(), dur = Math.min(1200, 300 + n * 40);
            const stepN = (now) => { const k = Math.min(1, (now - t0) / dur); p.textContent = '#' + Math.max(1, Math.round(n * (1 - Math.pow(1 - k, 3)))); if (k < 1) requestAnimationFrame(stepN); };
            requestAnimationFrame(stepN);
        }
        if (!matchMedia('(prefers-reduced-motion: reduce)').matches && !finish.fell) {
            finish.fell = true;
            const box = $('#leaves');
            box.innerHTML = Array.from({ length: 18 }, () => `<i style="left:${Math.random() * 100}%;animation-duration:${2.4 + Math.random() * 2.2}s;animation-delay:${Math.random() * .8}s">${['🌱', '🍃', '🌾'][Math.floor(Math.random() * 3)]}</i>`).join('');
            setTimeout(() => { box.innerHTML = ''; }, 5600);
        }
        drawCard();
    }

    function drawCard() {
        const cv = $('#card');
        if (!cv || !cv.getContext) return;
        const g = cv.getContext('2d'), W = cv.width, H = cv.height, d = S.done || {};
        const font = (w, s, f = 'Display') => `${w} ${s}px ${f}, 'Body', system-ui, sans-serif`;
        const sky = g.createLinearGradient(0, 0, 0, H);
        sky.addColorStop(0, '#ffe2a8'); sky.addColorStop(.45, '#fff6e2'); sky.addColorStop(1, '#fbf7ec');
        g.fillStyle = sky; g.fillRect(0, 0, W, H);
        g.fillStyle = 'rgba(255,197,61,.25)'; g.beginPath(); g.arc(820, 250, 190, 0, Math.PI * 2); g.fill();
        g.fillStyle = '#ffc53d'; g.beginPath(); g.arc(820, 250, 120, 0, Math.PI * 2); g.fill();
        const hill = (y, c, a) => { g.fillStyle = c; g.beginPath(); g.moveTo(0, y); g.quadraticCurveTo(W * .3, y - a, W * .55, y - 10); g.quadraticCurveTo(W * .8, y + 10, W, y - a * .5); g.lineTo(W, H); g.lineTo(0, H); g.closePath(); g.fill(); };
        hill(980, '#a8cc7e', 90); hill(1080, '#6b9f3d', 80); hill(1190, '#4a7c2a', 70);
        // The sprout.
        g.strokeStyle = '#2d5016'; g.lineWidth = 12; g.lineCap = 'round';
        g.beginPath(); g.moveTo(190, 1080); g.bezierCurveTo(190, 1010, 192, 960, 200, 905); g.stroke();
        g.fillStyle = '#86b556'; g.beginPath(); g.moveTo(198, 975); g.bezierCurveTo(140, 960, 110, 915, 118, 870); g.bezierCurveTo(170, 878, 198, 915, 198, 975); g.fill();
        g.fillStyle = '#6b9f3d'; g.beginPath(); g.moveTo(202, 935); g.bezierCurveTo(258, 920, 292, 875, 284, 822); g.bezierCurveTo(226, 836, 200, 880, 202, 935); g.fill();
        // The words.
        g.fillStyle = '#4a7c2a'; g.font = font(800, 40, 'Body'); g.fillText(t('cardKicker'), 90, 150);
        g.fillStyle = '#2d5016'; g.font = font(700, 120); g.fillText('#' + (d.position || 1), 84, 290);
        g.fillStyle = '#1f2a1a'; g.font = font(700, 70);
        const name = [A.firstName, A.lastName].filter(Boolean).join(' ').slice(0, 26) || '';
        g.fillText(name, 90, 400);
        g.fillStyle = '#5b6650'; g.font = font(700, 38, 'Body');
        g.fillText([A.town, A.province].filter(Boolean).join(', ').slice(0, 44), 90, 460);
        const crops = (A.crops || []).map((k) => (C.crops.find((x) => x.k === k) || {}).pic || '').join('  ');
        g.font = '64px system-ui, sans-serif'; g.fillText(crops, 90, 560);
        const wish = (A.wishText || '').trim() || (A.wishes || []).map((k) => word('wishes', k)).slice(0, 2).join('. ');
        if (wish) {
            g.fillStyle = 'rgba(255,255,255,.82)';
            const bx = 90, by = 610, bw = W - 180;
            const lines = wrap(g, '“' + wish + '”', bw - 80, font(700, 42, 'Body')).slice(0, 4);
            const bh = 110 + lines.length * 56;
            round(g, bx, by, bw, bh, 36); g.fill();
            g.fillStyle = '#4a7c2a'; g.font = font(800, 32, 'Body'); g.fillText(t('cardWish'), bx + 40, by + 62);
            g.fillStyle = '#1f2a1a'; g.font = font(700, 42, 'Body');
            lines.forEach((l, i) => g.fillText(l, bx + 40, by + 122 + i * 56));
        }
        g.fillStyle = '#ffffff'; g.font = font(800, 40, 'Body');
        g.fillText(t('cardFarmer') + ' #' + (d.position || 1) + ' ' + t('of') + ' ' + LIMIT, 340, 1265);
    }
    function wrap(g, text, max, f) {
        g.font = f; const out = []; let line = '';
        for (const w of text.split(/\s+/)) { const tryL = line ? line + ' ' + w : w; if (g.measureText(tryL).width > max && line) { out.push(line); line = w; } else line = tryL; }
        if (line) out.push(line); return out;
    }
    function round(g, x, y, w, h, r) { g.beginPath(); g.moveTo(x + r, y); g.arcTo(x + w, y, x + w, y + h, r); g.arcTo(x + w, y + h, x, y + h, r); g.arcTo(x, y + h, x, y, r); g.arcTo(x, y, x + w, y, r); g.closePath(); }
    function download() {
        const cv = $('#card'); if (!cv) return;
        const a = document.createElement('a'); a.download = 'unang-50.png'; a.href = cv.toDataURL('image/png'); document.body.appendChild(a); a.click(); a.remove();
    }
    async function shareCard() {
        const cv = $('#card'), link = $('#mylink')?.value || location.href;
        try {
            const blob = await new Promise((r) => cv.toBlob(r, 'image/png'));
            const file = new File([blob], 'unang-50.png', { type: 'image/png' });
            if (navigator.canShare && navigator.canShare({ files: [file] })) { await navigator.share({ files: [file], text: t('shareText') + ' ' + link }); return; }
            if (navigator.share) { await navigator.share({ text: t('shareText'), url: link }); return; }
        } catch (e) { if (e && e.name === 'AbortError') return; }
        copy(link);
    }
    async function copy(text) {
        try { await navigator.clipboard.writeText(text); } catch (_) { const i = $('#mylink'); i?.select(); try { document.execCommand('copy'); } catch (__) {} }
        const box = $('#toast'); box.textContent = t('copied'); box.classList.add('is-on'); setTimeout(() => box.classList.remove('is-on'), 1800);
    }

    paint();
})();
</script>
</body>
</html>
