@once
{{-- THE WEATHER AS A PLACE, NOT AS A PICTURE OF THE WEATHER.

     The scene book next door draws ICONS — a cloud with three drops under it,
     forty pixels wide, meant to sit beside a number. Scattering three of
     those across a card gives you three little pictures of rain on a card,
     which is not the same thing as a card that is raining.

     This is the other thing. It fills whatever box it is dropped into and
     behaves like sky: rain actually falls the height of the card, the sun
     sits off the top corner with its rays reaching across, clouds hang over
     the top edge with only their undersides showing — the way you see cloud
     from under it. Nothing here is centred, framed or complete, because
     weather seen from inside it never is.

     Usage:  container needs position:relative and overflow:hidden.
             el.innerHTML = window.wxAtmosphere('rain');

     Every piece carries its own alpha rather than the layer carrying one, so
     rain can be present while a sun's glow stays faint. Turn the whole thing
     down with opacity on the container if a card needs it quieter. --}}
<style>
    .atm { position: absolute; inset: 0; overflow: hidden; pointer-events: none;
        /* One palette, so a piece used by four kinds of weather agrees with
           itself in all four. */
        --atm-cloud: rgb(148 163 184 / .3);
        --atm-cloud-dark: rgb(100 116 139 / .38);
        --atm-rain: rgb(56 189 248 / .6);
        --atm-sun: rgb(251 191 36 / .55);
        --atm-ray: rgb(251 191 36 / .18);
        --atm-moon: rgb(203 213 225 / .6);
        --atm-star: rgb(253 224 71 / .8);
        --atm-fog: rgb(148 163 184 / .34);
        --atm-snow: rgb(255 255 255 / .85);
        --atm-heat: rgb(249 115 22 / .18);
        --atm-wind: rgb(125 211 252 / .5); }
    html.dark .atm {
        --atm-cloud: rgb(148 163 184 / .3);
        --atm-cloud-dark: rgb(71 85 105 / .55);
        --atm-rain: rgb(125 211 252 / .5);
        --atm-sun: rgb(251 191 36 / .42);
        --atm-ray: rgb(251 191 36 / .13);
        --atm-moon: rgb(226 232 240 / .5);
        --atm-star: rgb(253 224 71 / .7);
        --atm-fog: rgb(148 163 184 / .26);
        --atm-snow: rgb(255 255 255 / .7);
        --atm-heat: rgb(249 115 22 / .16);
        --atm-wind: rgb(125 211 252 / .4); }

    /* ---- cloud, hanging over the top edge ------------------------------
       Anchored ABOVE the box so only the underside shows. A cloud you can
       see the top of is a cloud you are flying over, and nobody reading this
       card is. Each one is three overlapping ellipses; that is the whole
       trick, and it is why they read as cloud rather than as lozenges. */
    .atm-cloud { position: absolute; top: var(--t, -3.2rem); left: var(--x, 0);
        width: var(--w, 12rem); height: var(--h, 5.4rem);
        animation: atmDrift var(--d, 34s) ease-in-out infinite alternate; }
    .atm-cloud i { position: absolute; bottom: 0; border-radius: 50%;
        background: var(--atm-cloud); filter: blur(1px); }
    .atm-cloud.is-dark i { background: var(--atm-cloud-dark); }
    .atm-cloud { opacity: var(--a, 1); }
    .atm-cloud i:nth-child(1) { left: 0; width: 62%; height: 78%; }
    .atm-cloud i:nth-child(2) { left: 26%; width: 58%; height: 100%; }
    .atm-cloud i:nth-child(3) { left: 58%; width: 46%; height: 68%; }
    /* Two more shapes, so a row of them stops being a row of one shape. */
    .atm-cloud.lay-b i:nth-child(1) { left: 2%; width: 48%; height: 92%; }
    .atm-cloud.lay-b i:nth-child(2) { left: 34%; width: 44%; height: 64%; }
    .atm-cloud.lay-b i:nth-child(3) { left: 62%; width: 40%; height: 84%; }
    .atm-cloud.lay-c i:nth-child(1) { left: 0; width: 40%; height: 62%; }
    .atm-cloud.lay-c i:nth-child(2) { left: 20%; width: 62%; height: 100%; }
    .atm-cloud.lay-c i:nth-child(3) { left: 66%; width: 36%; height: 54%; }
    @keyframes atmDrift { from { transform: translateX(-4%); } to { transform: translateX(4%); } }

    /* ---- rain, falling the whole height --------------------------------
       Streaks, not drops: at this scale a falling circle is a dot that
       stutters, and a short line reads as rain the moment it moves. The
       layer is skewed so every streak leans the same way, which is cheaper
       than rotating each one and looks the same. */
    .atm-rainfall { position: absolute; inset: -20% -10%; transform: skewX(-9deg); }
    .atm-rainfall i { position: absolute; top: 0; width: 2px; height: 20%;
        border-radius: 2px;
        background: linear-gradient(to bottom, transparent, var(--atm-rain));
        animation: atmFall linear infinite; }
    .atm-rainfall.is-heavy i { width: 2.5px; height: 26%; }
    .atm-rainfall.is-fine i { width: 1.5px; height: 11%; opacity: .7; }
    @keyframes atmFall {
        from { transform: translateY(-120%); }
        to { transform: translateY(760%); }
    }

    /* ---- the sun, off the corner ---------------------------------------
       Off the card on two sides on purpose. A whole sun inside the frame is
       a drawing of the sun; a sun leaving the corner is the light in the
       room. The fan turns once every three-quarters of a minute, which is
       slow enough that you never catch it moving. */
    .atm-sunbox { position: absolute; top: -6.5rem; right: -6.5rem;
        width: 15rem; height: 15rem; }
    .atm-sunbox.is-left { right: auto; left: -6.5rem; }
    .atm-disc { position: absolute; inset: 36%; border-radius: 50%;
        background: radial-gradient(circle at 50% 50%,
            var(--atm-sun) 0 42%, rgb(251 191 36 / .22) 62%, transparent 78%);
        animation: atmBreathe 7s ease-in-out infinite; }
    .atm-fan { position: absolute; inset: 0;
        background: repeating-conic-gradient(from 0deg at 50% 50%,
            var(--atm-ray) 0deg 3.2deg, transparent 3.2deg 15deg);
        -webkit-mask-image: radial-gradient(circle at 50% 50%, #000 22%, rgb(0 0 0 / .55) 44%, transparent 68%);
        mask-image: radial-gradient(circle at 50% 50%, #000 22%, rgb(0 0 0 / .55) 44%, transparent 68%);
        animation: atmSpin 46s linear infinite; }
    @keyframes atmSpin { to { transform: rotate(360deg); } }
    @keyframes atmBreathe { 0%, 100% { transform: scale(1); } 50% { transform: scale(1.06); } }

    /* ---- the moon, and what is out with it ----------------------------- */
    .atm-moonbox { position: absolute; top: 2.6rem; right: 1.4rem;
        width: 4.4rem; height: 4.4rem; color: var(--atm-moon);
        animation: atmBreathe 9s ease-in-out infinite; }
    .atm-moonbox svg { width: 100%; height: 100%; display: block; }
    .atm-star { position: absolute; width: 3px; height: 3px; border-radius: 50%;
        background: var(--atm-star);
        animation: atmTwinkle var(--d, 3s) ease-in-out infinite var(--dl, 0s); }
    @keyframes atmTwinkle { 0%, 100% { opacity: .25; transform: scale(.7); } 50% { opacity: 1; transform: scale(1.25); } }

    /* ---- fog: bands, not a wash ---------------------------------------
       A flat grey overlay is a dirty screen. Fog is layered, and it moves
       past you at different speeds depending on how far off it is. */
    .atm-fogband { position: absolute; left: -30%; width: 160%;
        height: var(--h, 2.4rem); top: var(--t, 20%); border-radius: 50%;
        background: var(--atm-fog); filter: blur(9px);
        animation: atmRoll var(--d, 26s) linear infinite; }
    @keyframes atmRoll { from { transform: translateX(-18%); } to { transform: translateX(18%); } }

    /* ---- wind: what it does to the air, since it cannot be seen -------- */
    .atm-gust { position: absolute; left: -25%; width: 150%; height: 2px;
        top: var(--t, 30%); border-radius: 2px;
        background: linear-gradient(to right, transparent, var(--atm-wind) 35%, var(--atm-wind) 55%, transparent);
        opacity: .8;
        animation: atmGust var(--d, 5s) ease-in-out infinite var(--dl, 0s); }
    @keyframes atmGust {
        0% { transform: translateX(-40%) scaleX(.6); opacity: 0; }
        25% { opacity: .9; }
        100% { transform: translateX(40%) scaleX(1); opacity: 0; }
    }

    /* ---- snow ---------------------------------------------------------- */
    .atm-flake { position: absolute; top: -8%; border-radius: 50%;
        background: var(--atm-snow);
        animation: atmDown var(--d, 9s) linear infinite var(--dl, 0s),
                   atmSway var(--s, 4s) ease-in-out infinite alternate; }
    @keyframes atmDown { to { transform: translateY(1200%); } }
    @keyframes atmSway { from { margin-left: -.6rem; } to { margin-left: .6rem; } }

    /* ---- heat: the air itself going up --------------------------------- */
    .atm-shimmer { position: absolute; bottom: -10%; width: var(--w, 2.5rem);
        left: var(--x, 20%); height: 60%; border-radius: 999px;
        background: linear-gradient(to top, var(--atm-heat), transparent);
        filter: blur(7px);
        animation: atmRise var(--d, 6s) ease-in-out infinite var(--dl, 0s); }
    @keyframes atmRise {
        0%, 100% { transform: translateY(12%) scaleY(.9); opacity: .55; }
        50% { transform: translateY(-8%) scaleY(1.08); opacity: 1; }
    }

    /* ---- lightning: the whole card, for a sixteenth of a second -------- */
    .atm-flash { position: absolute; inset: 0;
        background: radial-gradient(ellipse at 68% 8%, rgb(255 255 255 / .85), transparent 62%);
        opacity: 0; animation: atmFlash 7s linear infinite; }
    html.dark .atm-flash { background: radial-gradient(ellipse at 68% 8%, rgb(226 232 240 / .55), transparent 62%); }
    @keyframes atmFlash {
        0%, 88%, 100% { opacity: 0; }
        89% { opacity: 1; }
        90.5% { opacity: .1; }
        92% { opacity: .8; }
        94% { opacity: 0; }
    }
    .atm-bolt { position: absolute; top: 2.2rem; right: 22%; width: 1.4rem;
        color: rgb(250 204 21 / .85); opacity: 0;
        animation: atmFlash 7s linear infinite; }
    .atm-bolt svg { width: 100%; height: auto; display: block; }

    /* Everything stops for anybody who has asked the system to stop things.
       An atmosphere that does not move is a still photograph of the sky,
       which is a perfectly good thing for a card to have. */
    @media (prefers-reduced-motion: reduce) {
        .atm *, .atm { animation: none !important; }
        .atm-flash, .atm-bolt { opacity: .35; }
    }
</style>

<script>
(function () {
    'use strict';
    if (window.wxAtmosphere) return;

    /* Deterministic scatter. Math.random would give a different sky on every
       render of the same weather, and a card that rearranges itself when you
       come back to it looks like it is loading something. */
    const spread = (n, seed) => {
        const out = [];
        for (let i = 0; i < n; i++) {
            // A golden-angle walk: even coverage without a grid's regularity.
            out.push(((i * 0.6180339887 + seed) % 1));
        }
        return out;
    };

    const r2 = (v) => Math.round(v * 1000) / 1000;

    /** Cloud hanging over the top edge, showing only its underside. */
    function clouds(n, dark) {
        let s = '';
        const at = spread(n, 0.11);
        at.forEach((p, i) => {
            /* Nothing here divides evenly into anything else. Widths off a
               7-step cycle, depths off a 5, layouts off a 3 and alphas off a
               4 — so the pattern that repeats is 420 clouds long, and no card
               is that wide. Even intervals of even sizes read as a border. */
            const w = 7 + ((i * 3) % 7) * 1.5;           // rem
            const h = w * (0.42 + ((i * 2) % 5) * 0.03);
            // How far it reaches INTO the card, which is the number that
            // matters — the rest of the cloud is above the edge and unseen.
            // Derived rather than guessed at, because a taller cloud with the
            // same offset hangs further down and the two drifted apart.
            const vis = 0.9 + ((i * 2) % 5) * 0.45;
            const t = -(h - vis);
            const d = 24 + ((i * 5) % 6) * 5;
            const lay = ['', ' lay-b', ' lay-c'][i % 3];
            const a = r2(0.68 + ((i * 3) % 4) * 0.11);
            s += `<span class="atm-cloud${dark ? ' is-dark' : ''}${lay}"
                style="--x:${r2(p * 104 - 12)}%;--w:${r2(w)}rem;--h:${r2(h)}rem;`
                + `--t:${r2(t)}rem;--d:${d}s;--a:${a}">
                <i></i><i></i><i></i></span>`;
        });

        return s;
    }

    /** Rain the height of the whole box. */
    function rainfall(n, kind) {
        let s = `<span class="atm-rainfall${kind ? ' is-' + kind : ''}">`;
        const at = spread(n, 0.37);
        at.forEach((p, i) => {
            const dur = (kind === 'heavy' ? 0.55 : kind === 'fine' ? 1.5 : 0.95) + (i % 5) * 0.09;
            const delay = r2((i % 11) * (dur / 11));
            s += `<i style="left:${r2(p * 100)}%;animation-duration:${r2(dur)}s;animation-delay:${delay}s"></i>`;
        });

        return s + '</span>';
    }

    const sun = (left) => `<span class="atm-sunbox${left ? ' is-left' : ''}">
        <span class="atm-fan"></span><span class="atm-disc"></span></span>`;

    const moon = () => `<span class="atm-moonbox">
        <svg viewBox="0 0 100 100" fill="none" aria-hidden="true">
            <path d="M62 14a38 38 0 1 0 26 60A44 44 0 0 1 62 14z" fill="currentColor"/>
        </svg></span>`;

    function stars(n) {
        let s = '';
        const at = spread(n, 0.71);
        at.forEach((p, i) => {
            const top = 6 + ((i * 37) % 62);
            s += `<i class="atm-star" style="left:${r2(p * 94 + 2)}%;top:${top}%;`
                + `--d:${2.4 + (i % 4) * 0.7}s;--dl:${r2((i % 6) * 0.4)}s"></i>`;
        });

        return s;
    }

    function fog() {
        let s = '';
        [[14, 2.4, 24], [38, 3.2, 31], [62, 2.8, 27], [84, 3.6, 35]].forEach(([t, h, d]) => {
            s += `<span class="atm-fogband" style="--t:${t}%;--h:${h}rem;--d:${d}s"></span>`;
        });

        return s;
    }

    function gusts(n) {
        let s = '';
        for (let i = 0; i < n; i++) {
            s += `<span class="atm-gust" style="--t:${18 + i * 17}%;`
                + `--d:${4.2 + (i % 3) * 1.3}s;--dl:${r2(i * 0.7)}s"></span>`;
        }

        return s;
    }

    function snow(n) {
        let s = '';
        const at = spread(n, 0.29);
        at.forEach((p, i) => {
            const sz = 3 + (i % 3);
            s += `<i class="atm-flake" style="left:${r2(p * 100)}%;width:${sz}px;height:${sz}px;`
                + `--d:${8 + (i % 5) * 1.6}s;--dl:${r2((i % 7) * 1.1)}s;--s:${3 + (i % 3)}s"></i>`;
        });

        return s;
    }

    function heat(n) {
        let s = '';
        for (let i = 0; i < n; i++) {
            s += `<span class="atm-shimmer" style="--x:${8 + i * 22}%;--w:${2 + (i % 3) * 0.8}rem;`
                + `--d:${5 + (i % 3) * 1.4}s;--dl:${r2(i * 0.8)}s"></span>`;
        }

        return s;
    }

    const BOLT = `<span class="atm-bolt"><svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
        <path d="M13.5 2 5 14h5.5L9 22l9-13h-5.6z"/></svg></span>`;

    /* Each sky, as the place it is. Order matters: what is drawn last is
       nearest, and rain has to fall in FRONT of the cloud it comes from. */
    const SKY = {
        clear: () => sun(),
        clear_night: () => stars(16) + moon(),
        partly: () => sun() + clouds(2),
        partly_night: () => stars(12) + moon() + clouds(2),
        cloudy: () => clouds(5),
        fog: () => clouds(2) + fog(),
        drizzle: () => clouds(3) + rainfall(22, 'fine'),
        rain: () => clouds(4) + rainfall(34),
        heavy_rain: () => clouds(5, true) + rainfall(52, 'heavy'),
        showers: () => sun() + clouds(3) + rainfall(26),
        showers_night: () => stars(10) + moon() + clouds(3) + rainfall(26),
        storm: () => clouds(5, true) + rainfall(44, 'heavy') + BOLT + '<span class="atm-flash"></span>',
        snow: () => clouds(3) + snow(22),
        hot: () => sun() + heat(4),
        windy: () => clouds(2) + gusts(4),
    };

    /**
     * The weather, as the inside of a box.
     *
     * @param key one of the sky keys the scene book uses
     * @returns HTML for a container that is position:relative, overflow:hidden
     */
    window.wxAtmosphere = function (key) {
        const build = SKY[key] || SKY.cloudy;

        return `<span class="atm atm-${key}">${build()}</span>`;
    };
})();
</script>
@endonce
