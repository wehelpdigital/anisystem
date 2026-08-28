@once
{{-- ============================================================
     The sky, drawn.

     Fourteen kinds of weather as small animated scenes, and eight colour
     families for the panel behind them. Include once per page; everything
     here is inert until something asks for it.

     Two ways to use it:
       Blade   @include('partials.weather-sky', ['key' => 'rain', 'size' => 44])
       JS      window.wxSky('rain')            -> markup for one scene
               window.wxHue('rain')            -> the panel class for its colour
               window.WX_SKIES['rain']         -> {label, tagalog, scene, hue, advice}

     Inline <style>, not @push('head'): this is included from inside the
     layout on some screens, below where the head stack is written, and a
     pushed rule would be silently dropped. A <style> in the body is valid
     and cannot be out-ordered.
     ============================================================ --}}
<style>
    /* ---- the panel behind the sky --------------------------------------
       A tint, not a paint: a fifth of an alpha at most, so the numbers on
       top stay the loudest thing in the box. It drifts on the same
       gradSweep the rest of the app uses, so a forecast panel breathes the
       way the day headers do rather than sitting there. */
    .wx-sky-bg { position: relative; border-radius: 1rem; overflow: hidden;
        background-size: 260% 260%; animation: gradSweep 18s ease-in-out infinite alternate; }
    @media (prefers-reduced-motion: reduce) { .wx-sky-bg { animation: none; } }

    .wx-hue-sun   { background-image: linear-gradient(135deg, rgb(251 191 36 / .20), rgb(253 224 71 / .10) 40%, rgb(240 180 41 / .18)); }
    .wx-hue-sky   { background-image: linear-gradient(135deg, rgb(125 211 252 / .22), rgb(191 219 254 / .12) 45%, rgb(56 189 248 / .16)); }
    .wx-hue-grey  { background-image: linear-gradient(135deg, rgb(148 163 184 / .20), rgb(203 213 225 / .12) 45%, rgb(100 116 139 / .16)); }
    .wx-hue-rain  { background-image: linear-gradient(135deg, rgb(59 130 246 / .20), rgb(147 197 253 / .12) 42%, rgb(37 99 235 / .18)); }
    .wx-hue-storm { background-image: linear-gradient(135deg, rgb(30 64 175 / .26), rgb(100 116 139 / .16) 45%, rgb(30 41 59 / .24)); }
    .wx-hue-night { background-image: linear-gradient(135deg, rgb(30 41 59 / .24), rgb(71 85 105 / .14) 45%, rgb(15 23 42 / .22)); }
    .wx-hue-heat  { background-image: linear-gradient(135deg, rgb(249 115 22 / .22), rgb(251 191 36 / .14) 45%, rgb(234 88 12 / .20)); }
    .wx-hue-wind  { background-image: linear-gradient(135deg, rgb(45 212 191 / .20), rgb(186 230 253 / .12) 45%, rgb(13 148 136 / .16)); }

    /* ---- the scenes ---------------------------------------------------
       Layered on purpose: a sun that only rotates is a spinner. Every one
       of these has two or three things moving at different speeds, which
       is what makes a drawing read as weather rather than as an icon. */
    .wx-sky { display: block; }
    .wx-sky svg { width: 100%; height: 100%; display: block; overflow: visible; }

    @keyframes wxSpin { to { transform: rotate(360deg); } }
    @keyframes wxBreathe { 0%, 100% { transform: scale(1); } 50% { transform: scale(1.07); } }
    @keyframes wxDrift { 0% { transform: translateX(-14px); opacity: 0; } 15%, 80% { opacity: 1; } 100% { transform: translateX(16px); opacity: 0; } }
    @keyframes wxFall { 0% { opacity: 0; transform: translateY(-5px); } 25% { opacity: 1; } 100% { opacity: 0; transform: translateY(11px); } }
    @keyframes wxTwinkle { 0%, 100% { opacity: .25; transform: scale(.75); } 50% { opacity: 1; transform: scale(1.2); } }

    /* Clear: rays turn, the disc breathes. */
    .wx-clear .wx-rays { transform-origin: 28px 28px; animation: wxSpin 14s linear infinite; }
    .wx-clear .wx-core { transform-origin: 28px 28px; animation: wxBreathe 3s ease-in-out infinite; }

    /* Clear night: the moon rides, the stars come and go out of step. */
    .wx-clear_night .wx-moon { transform-origin: 30px 28px; animation: wxRide 6s ease-in-out infinite; }
    .wx-clear_night .wx-star { animation: wxTwinkle 2.4s ease-in-out infinite; }
    .wx-clear_night .wx-st2 { animation-delay: .7s; }
    .wx-clear_night .wx-st3 { animation-delay: 1.4s; }
    @keyframes wxRide { 0%, 100% { transform: translateY(1.5px) rotate(-4deg); } 50% { transform: translateY(-1.5px) rotate(4deg); } }

    /* Half and half: the sun turns behind a cloud that will not quite move off. */
    .wx-partly .wx-rays, .wx-partly_night .wx-moon { transform-origin: 22px 22px; animation: wxSpin 16s linear infinite; }
    .wx-partly_night .wx-moon { animation: wxRide 6s ease-in-out infinite; transform-origin: 22px 22px; }
    .wx-partly .wx-cloud, .wx-partly_night .wx-cloud { animation: wxNudge 5s ease-in-out infinite; }
    @keyframes wxNudge { 0%, 100% { transform: translateX(-1.5px); } 50% { transform: translateX(2.5px); } }

    /* Overcast: two banks of cloud crossing at different speeds. The
       difference in speed is the whole of the depth. */
    .wx-cloudy .wx-back { animation: wxDrift 13s linear infinite; }
    .wx-cloudy .wx-front { animation: wxDrift 9s linear infinite 1.5s; }

    /* Fog: three bands sliding over one another. */
    .wx-fog .wx-band { animation: wxBand 6s ease-in-out infinite; }
    .wx-fog .wx-b2 { animation-delay: .9s; }
    .wx-fog .wx-b3 { animation-delay: 1.8s; }
    @keyframes wxBand {
        0%, 100% { transform: translateX(-4px); opacity: .35; }
        50% { transform: translateX(4px); opacity: .95; }
    }

    /* Rain, in three weights. Drizzle is fine and slow, rain is straight,
       heavy rain is fast and slanted with a puddle taking it. */
    .wx-drizzle .wx-cloud, .wx-rain .wx-cloud, .wx-heavy_rain .wx-cloud, .wx-showers .wx-cloud { animation: wxSag 3s ease-in-out infinite; }
    @keyframes wxSag { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(1.5px); } }
    .wx-drizzle .wx-drop { animation: wxFall 2.2s linear infinite; }
    .wx-rain .wx-drop { animation: wxFall 1.2s linear infinite; }
    .wx-heavy_rain .wx-drop { animation: wxSlant .8s linear infinite; }
    .wx-showers .wx-drop { animation: wxFall 1.4s linear infinite; }
    .wx-d2 { animation-delay: .3s !important; }
    .wx-d3 { animation-delay: .6s !important; }
    .wx-d4 { animation-delay: .9s !important; }
    @keyframes wxSlant {
        0% { opacity: 0; transform: translate(2px, -5px); }
        25% { opacity: 1; }
        100% { opacity: 0; transform: translate(-4px, 11px); }
    }
    .wx-heavy_rain .wx-ripple { transform-origin: 28px 48px; animation: wxRipple 1.6s ease-out infinite; }
    @keyframes wxRipple { 0% { opacity: .8; transform: scale(.2); } 100% { opacity: 0; transform: scale(1.5); } }

    /* Showers: the sun is still there behind it, which is the whole
       character of a shower. */
    .wx-showers .wx-rays { transform-origin: 19px 19px; animation: wxSpin 16s linear infinite; }

    /* The storm: the flash is short, sudden and rare, the way one is. */
    .wx-storm .wx-cloud { animation: wxSag 2.4s ease-in-out infinite; }
    .wx-storm .wx-bolt { animation: wxFlash 3.2s steps(1, end) infinite; }
    .wx-storm .wx-drop { animation: wxSlant 1s linear infinite; }
    @keyframes wxFlash {
        0%, 8% { opacity: 1; }
        9%, 14% { opacity: .15; }
        15%, 20% { opacity: 1; }
        21%, 100% { opacity: .15; }
    }

    /* Snow: three flakes, drifting rather than falling. */
    .wx-snow .wx-flake { animation: wxFlake 3.4s linear infinite; }
    @keyframes wxFlake {
        0% { opacity: 0; transform: translate(0, -5px) rotate(0); }
        20% { opacity: 1; }
        100% { opacity: 0; transform: translate(4px, 12px) rotate(180deg); }
    }

    /* Heat: the sun is bigger and lower, and the ground gives it back in
       lines that rise and go. */
    .wx-hot .wx-rays { transform-origin: 28px 22px; animation: wxSpin 9s linear infinite; }
    .wx-hot .wx-core { transform-origin: 28px 22px; animation: wxBreathe 2.2s ease-in-out infinite; }
    .wx-hot .wx-shimmer { animation: wxShimmer 2.6s ease-in-out infinite; }
    .wx-hot .wx-sh2 { animation-delay: .8s; }
    .wx-hot .wx-sh3 { animation-delay: 1.6s; }
    @keyframes wxShimmer {
        0% { opacity: 0; transform: translateY(3px); }
        40% { opacity: .9; }
        100% { opacity: 0; transform: translateY(-6px); }
    }

    /* Wind: the gusts cross and the grass leans away from them. */
    .wx-windy .wx-gust { animation: wxGust 2.4s ease-in-out infinite; }
    .wx-windy .wx-g2 { animation-delay: .5s; }
    .wx-windy .wx-g3 { animation-delay: 1s; }
    .wx-windy .wx-grass { transform-origin: 28px 48px; animation: wxLean 2.4s ease-in-out infinite; }
    @keyframes wxGust {
        0% { opacity: 0; transform: translateX(-12px); }
        30% { opacity: 1; }
        100% { opacity: 0; transform: translateX(14px); }
    }
    @keyframes wxLean { 0%, 100% { transform: rotate(-4deg); } 55% { transform: rotate(11deg); } }

    @media (prefers-reduced-motion: reduce) { .wx-sky * { animation: none !important; } }
</style>

<script>
(function () {
    if (window.wxSky) return;

    /* Every sky the table knows, handed to the browser once so a forecast
       can be drawn without another round trip. */
    window.WX_SKIES = @json(\App\Models\AsWeatherScene::map());

    const S = {};

    S.clear = `<g class="wx-rays"><path d="M28 4v6M28 46v6M4 28h6M46 28h6M11 11l4.2 4.2M40.8 40.8L45 45M45 11l-4.2 4.2M15.2 40.8L11 45" stroke="#f0b429" stroke-width="3" stroke-linecap="round"/></g>
        <circle class="wx-core" cx="28" cy="28" r="10" fill="#fbbf24" stroke="#e09b13" stroke-width="2"/>`;

    S.clear_night = `<path class="wx-moon" d="M34 8a16 16 0 1 0 11 25A19 19 0 0 1 34 8z" fill="#e8edf5" stroke="#a9b4c6" stroke-width="2"/>
        <g fill="#f0b429"><circle class="wx-star wx-st1" cx="13" cy="15" r="2"/><circle class="wx-star wx-st2" cx="46" cy="18" r="1.6"/><circle class="wx-star wx-st3" cx="17" cy="42" r="1.6"/></g>`;

    S.partly = `<g class="wx-rays"><path d="M22 4v5M22 35v5M5 22h5M34 22h5M10 10l3.5 3.5M30.5 30.5L34 34M34 10l-3.5 3.5M13.5 30.5L10 34" stroke="#f0b429" stroke-width="2.6" stroke-linecap="round"/></g>
        <circle cx="22" cy="22" r="8" fill="#fbbf24" stroke="#e09b13" stroke-width="2"/>
        <path class="wx-cloud" d="M22 44a7 7 0 0 1 1-13.9A10 10 0 0 1 42 32a6.5 6.5 0 0 1 1 12H22z" fill="#e6ecf4" stroke="#9aa8bb" stroke-width="2"/>`;

    S.partly_night = `<path class="wx-moon" d="M26 8a13 13 0 1 0 9 20A15 15 0 0 1 26 8z" fill="#e8edf5" stroke="#a9b4c6" stroke-width="2"/>
        <circle class="wx-star" cx="12" cy="14" r="1.7" fill="#f0b429"/>
        <path class="wx-cloud" d="M20 46a7 7 0 0 1 1-13.9A10 10 0 0 1 40 34a6.5 6.5 0 0 1 1 12H20z" fill="#cfd8e3" stroke="#8b98ab" stroke-width="2"/>`;

    S.cloudy = `<path class="wx-back" d="M14 30a6 6 0 0 1 .9-11.9A8.6 8.6 0 0 1 31 20a5.6 5.6 0 0 1 .9 10H14z" fill="#dbe2ec" stroke="#a9b4c6" stroke-width="1.8"/>
        <path class="wx-front" d="M20 44a7 7 0 0 1 1-13.9A10 10 0 0 1 40 32a6.5 6.5 0 0 1 1 12H20z" fill="#eff3f8" stroke="#94a3b8" stroke-width="2"/>`;

    S.fog = `<path d="M20 30a7 7 0 0 1 1-13.9A10 10 0 0 1 40 18a6.5 6.5 0 0 1 1 12H20z" fill="#dfe5ee" stroke="#a9b4c6" stroke-width="2"/>
        <g stroke="#a9b4c6" stroke-width="3" stroke-linecap="round">
            <path class="wx-band wx-b1" d="M12 37h32"/><path class="wx-band wx-b2" d="M16 43h28"/><path class="wx-band wx-b3" d="M13 49h30"/>
        </g>`;

    S.drizzle = `<path class="wx-cloud" d="M18 30a7 7 0 0 1 1-13.9A10 10 0 0 1 38 18a6.5 6.5 0 0 1 1 12H18z" fill="#cfd8e3" stroke="#9aa8bb" stroke-width="2"/>
        <g stroke="#7fb3e0" stroke-width="2.2" stroke-linecap="round">
            <path class="wx-drop wx-d1" d="M20 35v3"/><path class="wx-drop wx-d2" d="M28 35v3"/><path class="wx-drop wx-d3" d="M36 35v3"/>
        </g>`;

    S.rain = `<path class="wx-cloud" d="M17 29a7 7 0 0 1 1-13.9A10 10 0 0 1 37 17a6.5 6.5 0 0 1 1 12H17z" fill="#cfd8e3" stroke="#9aa8bb" stroke-width="2"/>
        <g stroke="#5b9bd5" stroke-width="3" stroke-linecap="round">
            <path class="wx-drop wx-d1" d="M19 34v5"/><path class="wx-drop wx-d2" d="M27 34v5"/><path class="wx-drop wx-d3" d="M35 34v5"/>
        </g>`;

    S.heavy_rain = `<path class="wx-cloud" d="M16 27a7.5 7.5 0 0 1 1-14.9A10.6 10.6 0 0 1 37 14a7 7 0 0 1 1 13H16z" fill="#b8c4d4" stroke="#7d8ba1" stroke-width="2"/>
        <g stroke="#3b82f6" stroke-width="3" stroke-linecap="round">
            <path class="wx-drop wx-d1" d="M18 32v6"/><path class="wx-drop wx-d2" d="M26 32v6"/><path class="wx-drop wx-d3" d="M34 32v6"/><path class="wx-drop wx-d4" d="M42 32v6"/>
        </g>
        <ellipse class="wx-ripple" cx="28" cy="48" rx="9" ry="2.6" fill="none" stroke="#5b9bd5" stroke-width="2"/>`;

    S.showers = `<g class="wx-rays"><path d="M19 3v4M19 31v4M3 19h4M31 19h4M8 8l2.8 2.8M27.2 27.2L30 30M30 8l-2.8 2.8M10.8 27.2L8 30" stroke="#f0b429" stroke-width="2.4" stroke-linecap="round"/></g>
        <circle cx="19" cy="19" r="6.5" fill="#fbbf24" stroke="#e09b13" stroke-width="1.8"/>
        <path class="wx-cloud" d="M22 36a7 7 0 0 1 1-13.9A10 10 0 0 1 42 24a6.5 6.5 0 0 1 1 12H22z" fill="#dfe5ee" stroke="#9aa8bb" stroke-width="2"/>
        <g stroke="#5b9bd5" stroke-width="2.8" stroke-linecap="round">
            <path class="wx-drop wx-d1" d="M25 41v4"/><path class="wx-drop wx-d2" d="M33 41v4"/><path class="wx-drop wx-d3" d="M41 41v4"/>
        </g>`;

    S.storm = `<path class="wx-cloud" d="M16 27a7.5 7.5 0 0 1 1-14.9A10.6 10.6 0 0 1 37 14a7 7 0 0 1 1 13H16z" fill="#9aa8bb" stroke="#64748b" stroke-width="2"/>
        <path class="wx-bolt" d="M28 30l-6 10h5l-3 10 10-13h-5l4-7z" fill="#f7c948" stroke="#d99e0b" stroke-width="1.6" stroke-linejoin="round"/>
        <g stroke="#3b82f6" stroke-width="2.6" stroke-linecap="round">
            <path class="wx-drop wx-d1" d="M18 32v5"/><path class="wx-drop wx-d3" d="M40 32v5"/>
        </g>`;

    S.snow = `<path d="M18 28a7 7 0 0 1 1-13.9A10 10 0 0 1 38 16a6.5 6.5 0 0 1 1 12H18z" fill="#e6ecf4" stroke="#9aa8bb" stroke-width="2"/>
        <g stroke="#7fb3e0" stroke-width="2" stroke-linecap="round">
            <g class="wx-flake wx-d1"><path d="M20 36v6M17 39h6M18 37l4 4M22 37l-4 4"/></g>
            <g class="wx-flake wx-d2"><path d="M28 36v6M25 39h6M26 37l4 4M30 37l-4 4"/></g>
            <g class="wx-flake wx-d3"><path d="M36 36v6M33 39h6M34 37l4 4M38 37l-4 4"/></g>
        </g>`;

    S.hot = `<g class="wx-rays"><path d="M28 2v5M8 22h5M43 22h5M13 7l3.5 3.5M39.5 7L36 10.5" stroke="#f97316" stroke-width="3" stroke-linecap="round"/></g>
        <circle class="wx-core" cx="28" cy="22" r="11" fill="#fb923c" stroke="#ea580c" stroke-width="2"/>
        <g stroke="#ea580c" stroke-width="2.2" stroke-linecap="round" fill="none">
            <path class="wx-shimmer wx-sh1" d="M16 52c2.5-2.5 2.5-5 0-7.5"/>
            <path class="wx-shimmer wx-sh2" d="M28 52c2.5-2.5 2.5-5 0-7.5"/>
            <path class="wx-shimmer wx-sh3" d="M40 52c2.5-2.5 2.5-5 0-7.5"/>
        </g>`;

    S.windy = `<g stroke="#0d9488" stroke-width="3" stroke-linecap="round" fill="none">
            <path class="wx-gust wx-g1" d="M8 16h22a4 4 0 1 0-4-4"/>
            <path class="wx-gust wx-g2" d="M8 26h30a4.5 4.5 0 1 1-4.5 4.5"/>
            <path class="wx-gust wx-g3" d="M8 36h18a3.5 3.5 0 1 0-3.5-3.5"/>
        </g>
        <g class="wx-grass" stroke="#4a7c2a" stroke-width="2.6" stroke-linecap="round" fill="none">
            <path d="M22 50c0-6 2-9 6-11"/><path d="M28 50c0-7 3-10 8-12"/><path d="M34 50c0-5 2-8 6-10"/>
        </g>
        <path d="M12 52h34" stroke="#8a5a2b" stroke-width="2.6" stroke-linecap="round"/>`;

    /** The markup for one sky. An unknown key draws cloud rather than nothing. */
    window.wxSky = function (key, size) {
        const meta = (window.WX_SKIES || {})[key];
        const scene = (meta && S[meta.scene]) ? meta.scene : (S[key] ? key : 'cloudy');
        const px = size ? `width:${size}px;height:${size}px;` : '';
        return `<span class="wx-sky wx-${scene}" style="${px}" aria-hidden="true"><svg viewBox="0 0 56 56" fill="none">${S[scene]}</svg></span>`;
    };

    /** The panel class for a sky's colour. */
    window.wxHue = function (key) {
        const meta = (window.WX_SKIES || {})[key];
        return 'wx-sky-bg wx-hue-' + ((meta && meta.hue) || 'grey');
    };

    /** What this sky means for the work, or an empty string. */
    window.wxAdvice = function (key) {
        const meta = (window.WX_SKIES || {})[key];
        return (meta && meta.advice) || '';
    };

    /** What to call it — Tagalog first where there is one. */
    window.wxName = function (key, tagalog) {
        const meta = (window.WX_SKIES || {})[key];
        if (!meta) return '';
        return (tagalog && meta.tagalog) ? meta.tagalog : meta.label;
    };

    /* Anything that only knows the key can say so in the markup and have it
       filled in here, which saves repeating fourteen drawings in Blade:
           <span data-wx-sky="rain" data-wx-size="40"></span> */
    window.wxPaintSkies = function (root) {
        (root || document).querySelectorAll('[data-wx-sky]').forEach((el) => {
            if (el.dataset.wxPainted) return;
            el.dataset.wxPainted = '1';
            el.innerHTML = window.wxSky(el.getAttribute('data-wx-sky'), Number(el.getAttribute('data-wx-size')) || 0);
        });
    };
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => window.wxPaintSkies());
    } else {
        window.wxPaintSkies();
    }

    /* Which sky a WMO code is — the same rules as AsWeatherScene::keyFor(),
       because the dashboard has the codes in hand and should not have to ask
       the server what they mean. */
    window.wxKeyFor = function (code, night, tempC, windKph) {
        code = Number(code) || 0;
        let base;
        if (code === 0 || code === 1) base = night ? 'clear_night' : 'clear';
        else if (code === 2) base = night ? 'partly_night' : 'partly';
        else if (code === 3) base = 'cloudy';
        else if ([45, 48].includes(code)) base = 'fog';
        else if ([51, 53, 55, 56, 57].includes(code)) base = 'drizzle';
        else if ([61, 63].includes(code)) base = 'rain';
        else if ([65, 66, 67].includes(code)) base = 'heavy_rain';
        else if ([80, 81, 82].includes(code)) base = 'showers';
        else if ([71, 73, 75, 77, 85, 86].includes(code)) base = 'snow';
        else if ([95, 96, 99].includes(code)) base = 'storm';
        else base = 'cloudy';

        if (['storm', 'heavy_rain', 'rain', 'showers', 'drizzle', 'snow', 'fog'].includes(base)) return base;
        if (windKph != null && windKph >= 38) return 'windy';
        if (tempC != null && tempC >= 34 && !night) return 'hot';
        return base;
    };
})();
</script>
@endonce
