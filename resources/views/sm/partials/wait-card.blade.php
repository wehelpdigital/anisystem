@once
{{-- Inline, not pushed. @push('head') is emitted where @stack('head') sits,
     and this card is included from inside the layout — below that point — so
     the rules arrived after the stack had been written and every one of them
     was silently dropped, which is why ten scenes once rendered at once and
     full size. A <style> in the body is valid HTML and cannot be out-ordered. --}}
<style>
.bv-card { display: flex; flex-direction: column; align-items: center; gap: .7rem;
    padding: 1.5rem 1.9rem 1.35rem; border-radius: 1rem; background: var(--color-white, #fff);
    border: 1px solid var(--color-gray-200, #e5e7eb);
    box-shadow: 0 20px 45px -30px rgb(15 23 42 / .6); }
.bv-text { font-size: .9rem; font-weight: 800; color: var(--color-gray-800, #1f2937);
    text-align: center; max-width: 15rem; }
html.dark #boardVeil { background: #10160c; }
html.dark .bv-card { background: #151b12; border-color: #2b3a1c; }
html.dark .bv-text { color: #e8efe1; }

/* ---- The scenes ---------------------------------------------------
   A wait is a few seconds of somebody's attention, and this app spends
   it on something worth knowing rather than a joke. Each reminder gets
   a small scene: layered, several things moving at once and at
   different speeds, drawn the way the home screen's sky is.

   All of them are in the card and all but one are display:none — a
   hidden element's animations do not run, so the sleeping ones cost
   nothing and swapping scenes is a class toggle, not a build. */
.bv-scene { display: block; width: 3.5rem; height: 3.5rem; }
.bv-s { display: none; }
.bv-s.is-on { display: block; }
.bv-s svg { width: 3.5rem; height: 3.5rem; display: block; }

@keyframes bvSpin { to { transform: rotate(360deg); } }
@keyframes bvBreathe { 0%, 100% { transform: scale(1); } 50% { transform: scale(1.09); } }
@keyframes bvFall { 0% { opacity: 0; transform: translateY(-4px); } 25% { opacity: 1; } 100% { opacity: 0; transform: translateY(8px); } }
@keyframes bvSway { 0%, 100% { transform: rotate(-6deg); } 50% { transform: rotate(6deg); } }

/* Rain, and the coat you should already be wearing. The cloud sags under
   the weight, the drops fall in turn, and the hood shrugs against it. */
.bv-rain .rn-cloud { animation: bvSag 2.4s ease-in-out infinite; }
.bv-rain .rn-drop { animation: bvFall 1.1s linear infinite; }
.bv-rain .rn-2 { animation-delay: .36s; }
.bv-rain .rn-3 { animation-delay: .72s; }
.bv-rain .rn-coat { transform-origin: 28px 46px; animation: bvShrug 3.2s ease-in-out infinite; }
@keyframes bvSag { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(1.5px); } }
@keyframes bvShrug { 0%, 100% { transform: rotate(-2.5deg); } 50% { transform: rotate(2.5deg); } }

/* Noon. The rays turn, the sun breathes, and the heat comes off the ground
   in two lines that rise and go. */
.bv-sun .sn-rays { transform-origin: 28px 24px; animation: bvSpin 11s linear infinite; }
.bv-sun .sn-core { transform-origin: 28px 24px; animation: bvBreathe 2.8s ease-in-out infinite; }
.bv-sun .sn-heat-1 { animation: bvHeat 2.6s ease-in-out infinite; }
.bv-sun .sn-heat-2 { animation: bvHeat 2.6s ease-in-out infinite .9s; }
@keyframes bvHeat {
    0% { opacity: 0; transform: translateY(2px); }
    40% { opacity: .8; }
    100% { opacity: 0; transform: translateY(-5px); }
}

/* A glass filling, and one drop landing in it. The water rises from the
   bottom, holds, and goes down again — a glass you keep coming back to. */
.bv-water .wg-fill { transform-origin: 28px 45px; animation: bvFill 3.4s ease-in-out infinite; }
.bv-water .wg-drop { animation: bvFall 1.7s ease-in infinite; }
.bv-water .wg-shine { animation: bvShine 3.4s ease-in-out infinite; }
@keyframes bvFill {
    0%, 12% { transform: scaleY(.18); }
    45%, 72% { transform: scaleY(1); }
    100% { transform: scaleY(.18); }
}
@keyframes bvShine { 0%, 30% { opacity: 0; } 55% { opacity: .85; } 100% { opacity: 0; } }

/* One capsule, turning over slowly, with a light running across it. */
.bv-vitamin .vt-cap { transform-origin: 28px 28px; animation: bvTurn 4.2s ease-in-out infinite; }
.bv-vitamin .vt-shine { animation: bvSweep 4.2s ease-in-out infinite; }
@keyframes bvTurn { 0%, 100% { transform: rotate(-38deg); } 50% { transform: rotate(-22deg); } }
@keyframes bvSweep {
    0%, 20% { opacity: 0; transform: translateX(-9px); }
    45% { opacity: .9; }
    70%, 100% { opacity: 0; transform: translateX(9px); }
}

/* The wand, and the mist going where the wind takes it — away from you if
   you stood the right way round. Three puffs, each on its own clock. */
.bv-spray .sp-wand { transform-origin: 15px 40px; animation: bvAim 3.4s ease-in-out infinite; }
.bv-spray .sp-mist { animation: bvMist 1.8s ease-out infinite; }
.bv-spray .sp-m2 { animation-delay: .6s; }
.bv-spray .sp-m3 { animation-delay: 1.2s; }
@keyframes bvAim { 0%, 100% { transform: rotate(-3deg); } 50% { transform: rotate(3deg); } }
@keyframes bvMist {
    0% { opacity: 0; transform: translate(0, 0) scale(.5); }
    25% { opacity: .75; }
    100% { opacity: 0; transform: translate(13px, -7px) scale(1.5); }
}

/* Two boots by the door, one of them tipping the way a boot does when you
   knock the mud off it. */
.bv-boots .bt-left { transform-origin: 21px 44px; animation: bvKnock 2.8s ease-in-out infinite; }
.bv-boots .bt-right { transform-origin: 37px 44px; animation: bvKnock 2.8s ease-in-out infinite 1.4s; }
.bv-boots .bt-mud { animation: bvFall 2.8s ease-in infinite .5s; }
@keyframes bvKnock { 0%, 70%, 100% { transform: rotate(0); } 80% { transform: rotate(-9deg); } 90% { transform: rotate(3deg); } }

/* The blade on the stone, and the spark that says it is taking. */
.bv-tools .tl-blade { transform-origin: 40px 36px; animation: bvHone 1.6s ease-in-out infinite; }
.bv-tools .tl-spark { animation: bvSpark 1.6s ease-out infinite .35s; }
@keyframes bvHone { 0%, 100% { transform: translate(0, 0); } 50% { transform: translate(-5px, 2.5px); } }
@keyframes bvSpark { 0%, 40% { opacity: 0; transform: scale(.4); } 55% { opacity: 1; transform: scale(1.15); } 100% { opacity: 0; transform: scale(1.6); } }

/* The kit, with the lid lifting a little and the cross keeping time — the
   one thing in the shed you want to find without looking. */
.bv-firstaid .fa-lid { transform-origin: 14px 22px; animation: bvLid 3.6s ease-in-out infinite; }
.bv-firstaid .fa-cross { transform-origin: 28px 34px; animation: bvPulse 1.8s ease-in-out infinite; }
@keyframes bvLid { 0%, 60%, 100% { transform: rotate(0); } 75%, 85% { transform: rotate(-9deg); } }
@keyframes bvPulse { 0%, 100% { transform: scale(1); opacity: .9; } 50% { transform: scale(1.14); opacity: 1; } }

/* A line being written, and rubbed out, and written again — which is what
   a farm record actually looks like. */
.bv-notebook .nb-pencil { animation: bvWrite 3.4s ease-in-out infinite; }
.bv-notebook .nb-ink { stroke-dasharray: 20; stroke-dashoffset: 20; animation: bvInk 3.4s ease-in-out infinite; }
@keyframes bvWrite { 0%, 100% { transform: translate(-7px, 0); } 55%, 75% { transform: translate(7px, 0); } }
@keyframes bvInk { 0% { stroke-dashoffset: 20; } 55%, 78% { stroke-dashoffset: 0; } 92%, 100% { stroke-dashoffset: 20; } }

/* The stem draws itself, then the leaves open. */
.bv-seedling .sd-stem { stroke-dasharray: 24; stroke-dashoffset: 24; animation: bvStem 2.2s ease-in-out infinite; }
.bv-seedling .sd-leaf-l { transform-origin: 28px 28px; animation: bvLeaf 2.2s ease-in-out infinite .5s; }
.bv-seedling .sd-leaf-r { transform-origin: 28px 24px; animation: bvLeaf 2.2s ease-in-out infinite .8s; }
@keyframes bvStem { 0% { stroke-dashoffset: 24; } 45%, 100% { stroke-dashoffset: 0; } }
@keyframes bvLeaf { 0%, 20% { transform: scale(0); } 60%, 100% { transform: scale(1); } }

/* Wheels turn, the body rides the ruts, the exhaust goes up. */
.bv-tractor .tr-wheel-big { transform-origin: 19px 38px; animation: bvSpin 1.6s linear infinite; }
.bv-tractor .tr-wheel-small { transform-origin: 39px 40px; animation: bvSpin 1.1s linear infinite; }
.bv-tractor .tr-body { animation: bvBump .5s ease-in-out infinite alternate; }
.bv-tractor .tr-puff-1 { animation: bvPuff 1.4s ease-out infinite; }
.bv-tractor .tr-puff-2 { animation: bvPuff 1.4s ease-out infinite .5s; }
@keyframes bvBump { from { transform: translateY(0); } to { transform: translateY(-1.2px); } }
@keyframes bvPuff { 0% { opacity: .9; transform: translateY(3px); } 100% { opacity: 0; transform: translateY(-6px); } }

/* Five more minutes: the head dips, and the nose works. */
.bv-carabao .cb-head { transform-origin: 28px 14px; animation: bvNod 2.6s ease-in-out infinite; }
.bv-carabao .cb-nose { animation: bvBreathe 1.6s ease-in-out infinite; transform-origin: 28px 37px; }
@keyframes bvNod { 0%, 100% { transform: rotate(-4deg) translateY(0); } 50% { transform: rotate(4deg) translateY(1.5px); } }

/* A full head of rice, bending the way a full one does, over grain drying
   underneath it. */
.bv-rice .rc-stalk { transform-origin: 28px 48px; animation: bvSway 3s ease-in-out infinite; }
.bv-rice .rc-grain { animation: bvBreathe 2.4s ease-in-out infinite; transform-origin: 28px 46px; }

/* The can tips, and the water comes. */
.bv-watering .wt-can { transform-origin: 34px 34px; animation: bvTip 2.4s ease-in-out infinite; }
.bv-watering .wt-drop { animation: bvFall 1s linear infinite; }
.bv-watering .wt-2 { animation-delay: .4s; }
@keyframes bvTip { 0%, 100% { transform: rotate(0); } 45%, 65% { transform: rotate(-16deg); } }

/* One bee, going about its business — the wings too fast to see. */
.bv-bee .be-fly { animation: bvHover 2.6s ease-in-out infinite; }
.bv-bee .be-wing-l { transform-origin: 27px 23px; animation: bvFlap .18s ease-in-out infinite alternate; }
.bv-bee .be-wing-r { transform-origin: 29px 23px; animation: bvFlap .18s ease-in-out infinite alternate-reverse; }
@keyframes bvHover { 0%, 100% { transform: translate(-5px, 2px); } 25% { transform: translate(0, -3px); } 50% { transform: translate(5px, 2px); } 75% { transform: translate(0, -2px); } }
@keyframes bvFlap { from { transform: scaleY(1); } to { transform: scaleY(.35); } }

/* The end of the day: the moon rides, a cloud crosses it, the stars come
   and go out of step, and the sleep floats off. */
.bv-moon .mn-body { transform-origin: 30px 26px; animation: bvRide 5s ease-in-out infinite; }
.bv-moon .mn-cloud { animation: bvDrift 9s linear infinite; }
.bv-moon .mn-star { animation: bvTwinkle 2s ease-in-out infinite; }
.bv-moon .mn-s2 { animation-delay: .6s; }
.bv-moon .mn-s3 { animation-delay: 1.2s; }
.bv-moon .mn-z1 { animation: bvZ 3.6s ease-out infinite; }
.bv-moon .mn-z2 { animation: bvZ 3.6s ease-out infinite 1.2s; }
@keyframes bvRide { 0%, 100% { transform: translateY(1.5px) rotate(-4deg); } 50% { transform: translateY(-1.5px) rotate(4deg); } }
@keyframes bvTwinkle { 0%, 100% { opacity: .25; transform: scale(.8); } 50% { opacity: 1; transform: scale(1.2); } }
@keyframes bvDrift { 0% { transform: translateX(-16px); opacity: 0; } 18%, 78% { opacity: .9; } 100% { transform: translateX(18px); opacity: 0; } }
@keyframes bvZ { 0% { opacity: 0; transform: translate(0, 0) scale(.6); } 30% { opacity: 1; } 100% { opacity: 0; transform: translate(5px, -9px) scale(1.1); } }

/* Nobody who has asked for stillness gets a farm jumping at them: the
   scene holds its finished pose and only the card's fade remains. */
@media (prefers-reduced-motion: reduce) {
    #boardVeil { transition: none; }
    .bv-s * { animation: none !important; }
    .bv-seedling .sd-stem { stroke-dashoffset: 0; }
    .bv-notebook .nb-ink { stroke-dashoffset: 0; }
    .bv-water .wg-fill { transform: scaleY(.72); }
}

    /* The second line: quieter, narrower, and never the same weight as the
       first — it is the reason, and the reason is what makes a reminder
       something other than nagging. */
    .bv-sub { margin-top: -.25rem; font-size: .78rem; line-height: 1.5;
        color: var(--color-gray-500, #6b7280); text-align: center; max-width: 15rem; }
    .bv-sub:empty { display: none; }
    html.dark .bv-sub { color: #a8bd93; }

    /* ---- the shared motions, for the forty scenes below ----------------
       The first sixteen scenes each rolled their own keyframes, which was
       fine for sixteen. Forty more doing the same would be four hundred
       lines of very nearly one thing, so these are named once and a scene
       just tags the part that moves. */
    @keyframes bvSway  { 0%, 100% { transform: rotate(-5deg); } 50% { transform: rotate(5deg); } }
    @keyframes bvBob   { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-2.5px); } }
    @keyframes bvPulse { 0%, 100% { transform: scale(1); opacity: 1; } 50% { transform: scale(1.14); opacity: .78; } }
    @keyframes bvSpin  { to { transform: rotate(360deg); } }
    @keyframes bvFall  { 0% { transform: translateY(-7px); opacity: 0; } 25% { opacity: 1; } 100% { transform: translateY(11px); opacity: 0; } }
    @keyframes bvRise  { 0% { transform: translateY(5px) scale(.7); opacity: 0; } 40% { opacity: .9; } 100% { transform: translateY(-11px) scale(1.18); opacity: 0; } }
    @keyframes bvBlink { 0%, 100% { opacity: 1; } 50% { opacity: .22; } }
    @keyframes bvSlide { 0%, 100% { transform: translateX(-2.5px); } 50% { transform: translateX(2.5px); } }
    @keyframes bvGrow  { 0%, 100% { transform: scaleY(.9); } 50% { transform: scaleY(1.07); } }
    @keyframes bvFlow  { to { stroke-dashoffset: -14; } }
    @keyframes bvTick  { 0%, 25% { stroke-dashoffset: 40; } 45%, 100% { stroke-dashoffset: 0; } }

    .bv-sway   { animation: bvSway 3.4s ease-in-out infinite; }
    .bv-sway2  { animation: bvSway 4.1s ease-in-out infinite .4s; }
    .bv-bob    { animation: bvBob 2.6s ease-in-out infinite; }
    .bv-bob2   { animation: bvBob 3.1s ease-in-out infinite .45s; }
    .bv-pulse  { animation: bvPulse 2.4s ease-in-out infinite; }
    .bv-pulse2 { animation: bvPulse 2.4s ease-in-out infinite .8s; }
    .bv-spin   { animation: bvSpin 9s linear infinite; }
    .bv-spin2  { animation: bvSpin 22s linear infinite; }
    .bv-fall   { animation: bvFall 1.7s linear infinite; }
    .bv-fall2  { animation: bvFall 1.7s linear infinite .5s; }
    .bv-fall3  { animation: bvFall 1.7s linear infinite 1s; }
    .bv-rise   { animation: bvRise 2.6s ease-out infinite; }
    .bv-rise2  { animation: bvRise 2.6s ease-out infinite .8s; }
    .bv-blink  { animation: bvBlink 2.2s ease-in-out infinite; }
    .bv-slide  { animation: bvSlide 2.8s ease-in-out infinite; }
    .bv-grow   { animation: bvGrow 2.8s ease-in-out infinite; transform-box: fill-box; }
    .bv-flow   { stroke-dasharray: 5 4; animation: bvFlow 1.3s linear infinite; }
    .bv-tick   { stroke-dasharray: 40; animation: bvTick 3.4s ease-in-out infinite; }
    </style>
@endonce

{{-- What a wait looks like: a reminder worth the few seconds, and a small
     scene beside it, drawn rather than spun.

     Expects: $waitPool — rows of ['line' => …, 'sub' => …, 'scene' => …] from
     AsLoadingLine::pool(). The first is drawn server-side so the very first
     paint already says something; the rest ride along so the browser can
     re-roll without another round trip.

     Every scene is in the DOM and all but one is display:none. A hidden
     element's animations do not run, so the sleeping ones cost nothing, and
     switching lines is a class toggle rather than a build. --}}
@php
    // A caller that has not thought about it gets a pool anyway: this card is
    // wanted on every screen that waits, and making each of them remember to
    // fetch one is how half of them end up without it.
    $waitPool = $waitPool ?? \App\Models\AsLoadingLine::pool();
    $waitFirst = $waitPool[0];
    $waitScene = in_array($waitFirst['scene'], \App\Models\AsLoadingLine::SCENES, true)
        ? $waitFirst['scene']
        : 'seedling';
@endphp
<div class="bv-card">
    <span class="bv-scene" aria-hidden="true">
        {{-- Rain, and the coat. --}}
        <span class="bv-s bv-rain {{ $waitScene === 'rain' ? 'is-on' : '' }}" data-scene="rain">
            <svg viewBox="0 0 56 56" fill="none">
                <path class="rn-cloud" d="M15 24a6.5 6.5 0 0 1 1-12.9A9.5 9.5 0 0 1 34 12a6 6 0 0 1 1 12H15z" fill="#cfd8e3" stroke="#9aa8bb" stroke-width="2"/>
                <path class="rn-drop rn-1" d="M17 28v4" stroke="#5b9bd5" stroke-width="2.6" stroke-linecap="round"/>
                <path class="rn-drop rn-2" d="M27 28v4" stroke="#5b9bd5" stroke-width="2.6" stroke-linecap="round"/>
                <path class="rn-drop rn-3" d="M37 28v4" stroke="#5b9bd5" stroke-width="2.6" stroke-linecap="round"/>
                <g class="rn-coat">
                    {{-- Hood, face and shoulders. Without the opening the
                         shape is a bell; with it, it is somebody who dressed
                         for the weather, which is the whole of the advice. --}}
                    <path d="M28 31c5.5 0 9 3.6 9 8V48H19v-9c0-4.4 3.5-8 9-8z" fill="#f0b429" stroke="#c98a12" stroke-width="2" stroke-linejoin="round"/>
                    <ellipse cx="28" cy="38.5" rx="4.4" ry="5" fill="#f6d9a0" stroke="#c98a12" stroke-width="1.4"/>
                    <path d="M20 48h16" stroke="#c98a12" stroke-width="2" stroke-linecap="round"/>
                </g>
            </svg>
    
        {{-- Forty more, so a wait stops looking like one animation with the
             words changed. Same palette, shared motions. --}}
        <span class="bv-s bv-hat {{ $waitScene === 'hat' ? 'is-on' : '' }}" data-scene="hat">
            <svg viewBox="0 0 56 56" fill="none">
    <ellipse class="bv-sway" cx="28" cy="34" rx="22" ry="7" fill="#f0b429" stroke="#c98a12" stroke-width="2" style="transform-origin:28px 34px"/>
    <path class="bv-sway" d="M17 34c0-9 4-15 11-15s11 6 11 15z" fill="#fde047" stroke="#c98a12" stroke-width="2" style="transform-origin:28px 34px"/>
    <path class="bv-sway" d="M17 30h22" stroke="#c98a12" stroke-width="2" style="transform-origin:28px 34px"/>
    <circle class="bv-pulse" cx="45" cy="14" r="4" fill="#f0b429" style="transform-origin:45px 14px"/>
            </svg>
        </span>
        <span class="bv-s bv-gloves {{ $waitScene === 'gloves' ? 'is-on' : '' }}" data-scene="gloves">
            <svg viewBox="0 0 56 56" fill="none">
    <path class="bv-sway" d="M18 46V26a3 3 0 0 1 6 0v-4a3 3 0 0 1 6 0v-2a3 3 0 0 1 6 0v6a3 3 0 0 1 5 2v12a6 6 0 0 1-6 6z"
          fill="#8fc96a" stroke="#4a7c2a" stroke-width="2" stroke-linejoin="round" style="transform-origin:28px 46px"/>
    <path class="bv-sway" d="M18 36h23" stroke="#4a7c2a" stroke-width="2" style="transform-origin:28px 46px"/>
            </svg>
        </span>
        <span class="bv-s bv-mask {{ $waitScene === 'mask' ? 'is-on' : '' }}" data-scene="mask">
            <svg viewBox="0 0 56 56" fill="none">
    <path d="M12 22c8-4 24-4 32 0v10c0 8-7 14-16 14s-16-6-16-14z" fill="#cfd8e3" stroke="#5b6779" stroke-width="2" stroke-linejoin="round"/>
    <path d="M12 26H6M44 26h6" stroke="#5b6779" stroke-width="2.4" stroke-linecap="round"/>
    <circle cx="28" cy="34" r="6" fill="#8b98ab" stroke="#5b6779" stroke-width="2"/>
    <path class="bv-blink" d="M25 34h6M28 31v6" stroke="#cfd8e3" stroke-width="2" stroke-linecap="round"/>
            </svg>
        </span>
        <span class="bv-s bv-goggles {{ $waitScene === 'goggles' ? 'is-on' : '' }}" data-scene="goggles">
            <svg viewBox="0 0 56 56" fill="none">
    <rect x="8" y="22" width="40" height="14" rx="7" fill="#7dd3fc" opacity=".55" stroke="#5b6779" stroke-width="2"/>
    <path d="M28 22v14" stroke="#5b6779" stroke-width="2"/>
    <path d="M8 26H3M48 26h5" stroke="#5b6779" stroke-width="2.4" stroke-linecap="round"/>
    <path class="bv-slide" d="M14 26l4 4" stroke="#fff" stroke-width="2.4" stroke-linecap="round" opacity=".9"/>
            </svg>
        </span>
        <span class="bv-s bv-helmet {{ $waitScene === 'helmet' ? 'is-on' : '' }}" data-scene="helmet">
            <svg viewBox="0 0 56 56" fill="none">
    <path class="bv-bob" d="M12 34a16 16 0 0 1 32 0v6H12z" fill="#e11d48" stroke="#9f1239" stroke-width="2" stroke-linejoin="round"/>
    <path class="bv-bob" d="M20 34c0-6 3.5-10 8-10s8 4 8 10z" fill="#cfd8e3" opacity=".8"/>
    <path class="bv-bob" d="M12 40h32v3a3 3 0 0 1-3 3H15a3 3 0 0 1-3-3z" fill="#9f1239"/>
            </svg>
        </span>
        <span class="bv-s bv-ear {{ $waitScene === 'ear' ? 'is-on' : '' }}" data-scene="ear">
            <svg viewBox="0 0 56 56" fill="none">
    <path d="M20 44V26a8 8 0 0 1 16 0v18" stroke="#5b6779" stroke-width="2.6" fill="none" stroke-linecap="round"/>
    <rect class="bv-bob" x="12" y="26" width="10" height="16" rx="4" fill="#f0b429" stroke="#c98a12" stroke-width="2"/>
    <rect class="bv-bob" x="34" y="26" width="10" height="16" rx="4" fill="#f0b429" stroke="#c98a12" stroke-width="2"/>
    <path class="bv-blink" d="M46 20c3 3 3 9 0 12M10 20c-3 3-3 9 0 12" stroke="#8b98ab" stroke-width="2" stroke-linecap="round"/>
            </svg>
        </span>
        <span class="bv-s bv-soap {{ $waitScene === 'soap' ? 'is-on' : '' }}" data-scene="soap">
            <svg viewBox="0 0 56 56" fill="none">
    <path d="M14 32c0-4 6-6 14-6s14 2 14 6v8a6 6 0 0 1-6 6H20a6 6 0 0 1-6-6z" fill="#c98c4a" stroke="#8a5a2b" stroke-width="2" stroke-linejoin="round"/>
    <circle class="bv-rise" cx="20" cy="24" r="3" fill="#7dd3fc" opacity=".85" style="transform-origin:20px 24px"/>
    <circle class="bv-rise2" cx="29" cy="20" r="4" fill="#7dd3fc" opacity=".8" style="transform-origin:29px 20px"/>
    <circle class="bv-rise" cx="37" cy="24" r="2.6" fill="#7dd3fc" opacity=".9" style="transform-origin:37px 24px"/>
            </svg>
        </span>
        <span class="bv-s bv-bottle {{ $waitScene === 'bottle' ? 'is-on' : '' }}" data-scene="bottle">
            <svg viewBox="0 0 56 56" fill="none">
    <path d="M23 14h10v5l4 5v20a4 4 0 0 1-4 4H23a4 4 0 0 1-4-4V24l4-5z" fill="#7dd3fc" opacity=".55" stroke="#5b9bd5" stroke-width="2" stroke-linejoin="round"/>
    <rect x="23" y="10" width="10" height="5" rx="2" fill="#5b9bd5"/>
    <path class="bv-grow" d="M20 32h16v10a4 4 0 0 1-4 4h-8a4 4 0 0 1-4-4z" fill="#5b9bd5" opacity=".7" style="transform-origin:28px 46px"/>
            </svg>
        </span>
        <span class="bv-s bv-shade {{ $waitScene === 'shade' ? 'is-on' : '' }}" data-scene="shade">
            <svg viewBox="0 0 56 56" fill="none">
    <circle class="bv-pulse" cx="45" cy="12" r="6" fill="#f0b429" style="transform-origin:45px 12px"/>
    <path d="M20 46V28" stroke="#8a5a2b" stroke-width="3" stroke-linecap="round"/>
    <g class="bv-sway" style="transform-origin:20px 28px">
        <circle cx="14" cy="24" r="9" fill="#6aa84f"/>
        <circle cx="25" cy="22" r="10" fill="#4a7c2a"/>
        <circle cx="21" cy="15" r="8" fill="#8fc96a"/>
    </g>
    <ellipse cx="20" cy="47" rx="12" ry="3" fill="#5b6779" opacity=".2"/>
            </svg>
        </span>
        <span class="bv-s bv-nap {{ $waitScene === 'nap' ? 'is-on' : '' }}" data-scene="nap">
            <svg viewBox="0 0 56 56" fill="none">
    <path d="M6 20v26M50 20v26" stroke="#8a5a2b" stroke-width="3" stroke-linecap="round"/>
    <path class="bv-sway" d="M6 24c8 14 36 14 44 0" stroke="#f0b429" stroke-width="4" fill="none" stroke-linecap="round" style="transform-origin:28px 24px"/>
    <circle class="bv-sway" cx="28" cy="30" r="4" fill="#c98c4a" style="transform-origin:28px 24px"/>
    <text class="bv-rise" x="36" y="18" font-size="10" font-weight="800" fill="#8b98ab" style="transform-origin:36px 18px">z</text>
            </svg>
        </span>
        <span class="bv-s bv-phone {{ $waitScene === 'phone' ? 'is-on' : '' }}" data-scene="phone">
            <svg viewBox="0 0 56 56" fill="none">
    <rect x="18" y="10" width="20" height="36" rx="4" fill="#cfd8e3" stroke="#5b6779" stroke-width="2"/>
    <rect x="21" y="15" width="14" height="24" rx="1.5" fill="#8fc96a" opacity=".55"/>
    <circle cx="28" cy="43" r="1.6" fill="#5b6779"/>
    <path class="bv-blink" d="M42 18c4 5 4 15 0 20" stroke="#4a7c2a" stroke-width="2.2" fill="none" stroke-linecap="round"/>
    <path class="bv-blink" d="M46 13c6 8 6 22 0 30" stroke="#6aa84f" stroke-width="2" fill="none" stroke-linecap="round" opacity=".7"/>
            </svg>
        </span>
        <span class="bv-s bv-torch {{ $waitScene === 'torch' ? 'is-on' : '' }}" data-scene="torch">
            <svg viewBox="0 0 56 56" fill="none">
    <rect class="bv-sway" x="10" y="24" width="16" height="9" rx="2.5" fill="#5b6779" style="transform-origin:12px 28px"/>
    <path class="bv-sway" d="M26 22l8-4v22l-8-4z" fill="#8b98ab" style="transform-origin:12px 28px"/>
    <path class="bv-sway" d="M34 20l16-6v30l-16-6z" fill="#fde047" opacity=".55" style="transform-origin:12px 28px"/>
    <circle class="bv-pulse" cx="34" cy="28" r="3" fill="#f0b429" style="transform-origin:34px 28px"/>
            </svg>
        </span>
        <span class="bv-s bv-sack {{ $waitScene === 'sack' ? 'is-on' : '' }}" data-scene="sack">
            <svg viewBox="0 0 56 56" fill="none">
    <path class="bv-bob" d="M18 22c0-4 3-6 10-6s10 2 10 6l4 20a4 4 0 0 1-4 5H18a4 4 0 0 1-4-5z"
          fill="#c98c4a" stroke="#8a5a2b" stroke-width="2" stroke-linejoin="round" style="transform-origin:28px 47px"/>
    <path class="bv-bob" d="M20 22c4-3 12-3 16 0" stroke="#8a5a2b" stroke-width="2" fill="none" style="transform-origin:28px 47px"/>
    <path class="bv-bob" d="M22 34h12M22 39h9" stroke="#8a5a2b" stroke-width="1.8" stroke-linecap="round" opacity=".6" style="transform-origin:28px 47px"/>
            </svg>
        </span>
        <span class="bv-s bv-back {{ $waitScene === 'back' ? 'is-on' : '' }}" data-scene="back">
            <svg viewBox="0 0 56 56" fill="none">
    <circle cx="20" cy="14" r="5" fill="#c98c4a"/>
    <path d="M20 19c-4 0-7 3-7 7v6l-5 12h5l4-9 4 9h5l-3-12v-6c0-4-3-7-7-7z" fill="#4a7c2a"/>
    <rect class="bv-bob" x="30" y="16" width="18" height="12" rx="2" fill="#c98c4a" stroke="#8a5a2b" stroke-width="2" style="transform-origin:39px 28px"/>
    <path class="bv-blink" d="M14 26c-3 2-4 5-4 8" stroke="#e11d48" stroke-width="2.4" fill="none" stroke-linecap="round"/>
            </svg>
        </span>
        <span class="bv-s bv-cart {{ $waitScene === 'cart' ? 'is-on' : '' }}" data-scene="cart">
            <svg viewBox="0 0 56 56" fill="none">
    <path class="bv-slide" d="M10 20h6l6 18h20" stroke="#5b6779" stroke-width="2.6" fill="none" stroke-linecap="round" stroke-linejoin="round"/>
    <path class="bv-slide" d="M18 24h26l-3 12H21z" fill="#c98c4a" stroke="#8a5a2b" stroke-width="2" stroke-linejoin="round"/>
    <g class="bv-spin" style="transform-origin:26px 44px"><circle cx="26" cy="44" r="5" fill="none" stroke="#5b6779" stroke-width="2.4"/><path d="M26 40v8" stroke="#5b6779" stroke-width="1.6"/></g>
    <g class="bv-spin" style="transform-origin:40px 44px"><circle cx="40" cy="44" r="5" fill="none" stroke="#5b6779" stroke-width="2.4"/><path d="M40 40v8" stroke="#5b6779" stroke-width="1.6"/></g>
            </svg>
        </span>
        <span class="bv-s bv-rope {{ $waitScene === 'rope' ? 'is-on' : '' }}" data-scene="rope">
            <svg viewBox="0 0 56 56" fill="none">
    <circle cx="28" cy="30" r="15" fill="none" stroke="#c98c4a" stroke-width="4"/>
    <circle class="bv-spin" cx="28" cy="30" r="10" fill="none" stroke="#8a5a2b" stroke-width="4" stroke-dasharray="5 4" style="transform-origin:28px 30px"/>
    <circle cx="28" cy="30" r="5" fill="none" stroke="#c98c4a" stroke-width="3.4"/>
    <path class="bv-sway" d="M43 30c4 2 6 6 5 12" stroke="#c98c4a" stroke-width="3.4" fill="none" stroke-linecap="round" style="transform-origin:43px 30px"/>
            </svg>
        </span>
        <span class="bv-s bv-bolo {{ $waitScene === 'bolo' ? 'is-on' : '' }}" data-scene="bolo">
            <svg viewBox="0 0 56 56" fill="none">
    <path d="M14 42l22-24c4-4 9-4 11 0L26 44z" fill="#cfd8e3" stroke="#5b6779" stroke-width="2" stroke-linejoin="round"/>
    <path d="M14 42l-6 6 8-2z" fill="#8a5a2b"/>
    <rect x="34" y="10" width="14" height="7" rx="3" transform="rotate(45 41 13)" fill="#8a5a2b"/>
    <path class="bv-blink" d="M40 20l4-4" stroke="#fff" stroke-width="3" stroke-linecap="round"/>
            </svg>
        </span>
        <span class="bv-s bv-sharpen {{ $waitScene === 'sharpen' ? 'is-on' : '' }}" data-scene="sharpen">
            <svg viewBox="0 0 56 56" fill="none">
    <rect x="8" y="34" width="40" height="9" rx="4" fill="#8b98ab" stroke="#5b6779" stroke-width="2"/>
    <g class="bv-slide" style="transform-origin:28px 30px">
        <path d="M14 30l24-14 4 6-24 14z" fill="#cfd8e3" stroke="#5b6779" stroke-width="2" stroke-linejoin="round"/>
        <rect x="38" y="12" width="12" height="6" rx="3" transform="rotate(-30 44 15)" fill="#8a5a2b"/>
    </g>
    <path class="bv-blink" d="M20 28l-3 3M30 22l-3 3" stroke="#fde047" stroke-width="2.4" stroke-linecap="round"/>
            </svg>
        </span>
        <span class="bv-s bv-ladder {{ $waitScene === 'ladder' ? 'is-on' : '' }}" data-scene="ladder">
            <svg viewBox="0 0 56 56" fill="none">
    <path class="bv-sway" d="M18 48L24 8M38 48L32 8" stroke="#c98c4a" stroke-width="3.4" stroke-linecap="round" style="transform-origin:28px 48px"/>
    <path class="bv-sway" d="M22 40h12M23 32h10M24.5 24h8M26 16h6" stroke="#8a5a2b" stroke-width="3" stroke-linecap="round" style="transform-origin:28px 48px"/>
            </svg>
        </span>
        <span class="bv-s bv-bucket {{ $waitScene === 'bucket' ? 'is-on' : '' }}" data-scene="bucket">
            <svg viewBox="0 0 56 56" fill="none">
    <path class="bv-sway" d="M14 24h28l-4 22H18z" fill="#cfd8e3" stroke="#5b6779" stroke-width="2" stroke-linejoin="round" style="transform-origin:28px 20px"/>
    <path class="bv-sway" d="M17 20a11 11 0 0 1 22 0" stroke="#5b6779" stroke-width="2.4" fill="none" style="transform-origin:28px 20px"/>
    <path class="bv-grow" d="M16 34h24l-2 12H18z" fill="#5b9bd5" opacity=".65" style="transform-origin:28px 46px"/>
            </svg>
        </span>
        <span class="bv-s bv-hose {{ $waitScene === 'hose' ? 'is-on' : '' }}" data-scene="hose">
            <svg viewBox="0 0 56 56" fill="none">
    <path d="M8 46c0-14 10-22 22-22" stroke="#4a7c2a" stroke-width="4" fill="none" stroke-linecap="round"/>
    <rect x="30" y="20" width="12" height="8" rx="2" transform="rotate(-12 36 24)" fill="#5b6779"/>
    <path class="bv-fall" d="M44 20l6-4" stroke="#7dd3fc" stroke-width="2.6" stroke-linecap="round"/>
    <path class="bv-fall2" d="M45 24l7-1" stroke="#7dd3fc" stroke-width="2.6" stroke-linecap="round"/>
    <path class="bv-fall3" d="M44 28l6 3" stroke="#7dd3fc" stroke-width="2.6" stroke-linecap="round"/>
            </svg>
        </span>
        <span class="bv-s bv-pump {{ $waitScene === 'pump' ? 'is-on' : '' }}" data-scene="pump">
            <svg viewBox="0 0 56 56" fill="none">
    <rect x="14" y="26" width="20" height="18" rx="3" fill="#8b98ab" stroke="#5b6779" stroke-width="2"/>
    <path class="bv-slide" d="M24 26V14h14" stroke="#5b6779" stroke-width="3.4" fill="none" stroke-linecap="round" style="transform-origin:24px 26px"/>
    <path d="M34 34h12" stroke="#5b6779" stroke-width="3.4" stroke-linecap="round"/>
    <path class="bv-fall" d="M46 36v6" stroke="#5b9bd5" stroke-width="2.8" stroke-linecap="round"/>
    <path class="bv-fall2" d="M42 38v5" stroke="#7dd3fc" stroke-width="2.4" stroke-linecap="round"/>
            </svg>
        </span>
        <span class="bv-s bv-canal {{ $waitScene === 'canal' ? 'is-on' : '' }}" data-scene="canal">
            <svg viewBox="0 0 56 56" fill="none">
    <path d="M6 22h8v22H6zM42 22h8v22h-8z" fill="#8b98ab" stroke="#5b6779" stroke-width="2"/>
    <path class="bv-flow" d="M14 28h28M14 34h28M14 40h28" stroke="#5b9bd5" stroke-width="3" stroke-linecap="round"/>
    <path class="bv-bob" d="M4 48c4-2 8 2 12 0s8 2 12 0 8 2 12 0 8 2 12 0" stroke="#7dd3fc" stroke-width="2.4" fill="none" stroke-linecap="round"/>
            </svg>
        </span>
        <span class="bv-s bv-flood {{ $waitScene === 'flood' ? 'is-on' : '' }}" data-scene="flood">
            <svg viewBox="0 0 56 56" fill="none">
    <path d="M14 40V24h6v16M36 40V20h6v20" stroke="#8a5a2b" stroke-width="2.6" fill="none" stroke-linecap="round"/>
    <path class="bv-sway" d="M20 26h16v14H20z" fill="#c98c4a" opacity=".5" style="transform-origin:28px 40px"/>
    <path class="bv-bob" d="M2 38c5-3 9 3 14 0s9 3 14 0 9 3 14 0 9 3 12 0" stroke="#5b9bd5" stroke-width="2.6" fill="none" stroke-linecap="round"/>
    <path class="bv-bob2" d="M2 45c5-3 9 3 14 0s9 3 14 0 9 3 14 0 9 3 12 0" stroke="#7dd3fc" stroke-width="2.6" fill="none" stroke-linecap="round"/>
            </svg>
        </span>
        <span class="bv-s bv-soil {{ $waitScene === 'soil' ? 'is-on' : '' }}" data-scene="soil">
            <svg viewBox="0 0 56 56" fill="none">
    <path d="M6 44h44" stroke="#8a5a2b" stroke-width="2.6" stroke-linecap="round" opacity=".6"/>
    <path class="bv-sway" d="M16 30c4-4 10-4 14 0 3 3 2 8-3 8h-8c-5 0-6-5-3-8z" fill="#c98c4a" stroke="#8a5a2b" stroke-width="2" style="transform-origin:23px 26px"/>
    <circle class="bv-fall" cx="26" cy="40" r="2" fill="#8a5a2b"/>
    <circle class="bv-fall2" cx="32" cy="40" r="1.6" fill="#c98c4a"/>
    <circle class="bv-fall3" cx="20" cy="40" r="1.8" fill="#8a5a2b"/>
            </svg>
        </span>
        <span class="bv-s bv-compost {{ $waitScene === 'compost' ? 'is-on' : '' }}" data-scene="compost">
            <svg viewBox="0 0 56 56" fill="none">
    <path d="M8 44c2-12 10-18 20-18s18 6 20 18z" fill="#8a5a2b" stroke="#5f3d1a" stroke-width="2" stroke-linejoin="round"/>
    <path d="M18 40c3-6 6-8 10-8s7 2 10 8z" fill="#c98c4a" opacity=".7"/>
    <path class="bv-rise" d="M22 24c0-4 4-4 4-8" stroke="#8b98ab" stroke-width="2.2" fill="none" stroke-linecap="round" style="transform-origin:24px 20px"/>
    <path class="bv-rise2" d="M32 22c0-4 4-4 4-8" stroke="#8b98ab" stroke-width="2.2" fill="none" stroke-linecap="round" style="transform-origin:34px 18px"/>
            </svg>
        </span>
        <span class="bv-s bv-fertbag {{ $waitScene === 'fertbag' ? 'is-on' : '' }}" data-scene="fertbag">
            <svg viewBox="0 0 56 56" fill="none">
    <path class="bv-bob" d="M16 18h24l3 26a4 4 0 0 1-4 4H17a4 4 0 0 1-4-4z" fill="#cfd8e3" stroke="#5b6779" stroke-width="2" stroke-linejoin="round" style="transform-origin:28px 48px"/>
    <path class="bv-bob" d="M18 18c4-4 16-4 20 0" stroke="#5b6779" stroke-width="2" fill="none" style="transform-origin:28px 48px"/>
    <text class="bv-bob" x="28" y="38" font-size="12" font-weight="800" text-anchor="middle" fill="#4a7c2a" style="transform-origin:28px 48px">N</text>
            </svg>
        </span>
        <span class="bv-s bv-granule {{ $waitScene === 'granule' ? 'is-on' : '' }}" data-scene="granule">
            <svg viewBox="0 0 56 56" fill="none">
    <path d="M12 20c6-4 12-4 17 0l-3 12H15z" fill="#f0b429" opacity=".85" stroke="#c98a12" stroke-width="2" stroke-linejoin="round"/>
    <circle class="bv-fall" cx="32" cy="26" r="2.2" fill="#f0b429"/>
    <circle class="bv-fall2" cx="38" cy="30" r="2" fill="#c99a2f"/>
    <circle class="bv-fall3" cx="44" cy="26" r="2.2" fill="#f0b429"/>
    <path d="M8 46h40" stroke="#8a5a2b" stroke-width="2.6" stroke-linecap="round" opacity=".55"/>
            </svg>
        </span>
        <span class="bv-s bv-sprout {{ $waitScene === 'sprout' ? 'is-on' : '' }}" data-scene="sprout">
            <svg viewBox="0 0 56 56" fill="none">
    <path d="M8 44h40" stroke="#8a5a2b" stroke-width="2.6" stroke-linecap="round" opacity=".55"/>
    <ellipse cx="28" cy="40" rx="7" ry="5" fill="#c98c4a" stroke="#8a5a2b" stroke-width="2"/>
    <path class="bv-grow" d="M28 40V26" stroke="#4a7c2a" stroke-width="2.6" stroke-linecap="round" style="transform-origin:28px 40px"/>
    <path class="bv-sway" d="M28 30c-7-1-9-6-7-10 5-1 9 3 7 10z" fill="#6aa84f" style="transform-origin:28px 32px"/>
    <path class="bv-sway2" d="M28 32c7-1 9-6 7-10-5-1-9 3-7 10z" fill="#8fc96a" style="transform-origin:28px 32px"/>
            </svg>
        </span>
        <span class="bv-s bv-nursery {{ $waitScene === 'nursery' ? 'is-on' : '' }}" data-scene="nursery">
            <svg viewBox="0 0 56 56" fill="none">
    <rect x="8" y="32" width="40" height="14" rx="3" fill="#c98c4a" stroke="#8a5a2b" stroke-width="2"/>
    <path d="M18 32v14M28 32v14M38 32v14" stroke="#8a5a2b" stroke-width="1.6" opacity=".6"/>
    <g class="bv-sway" style="transform-origin:13px 32px"><path d="M13 32V24" stroke="#4a7c2a" stroke-width="2.2" stroke-linecap="round"/><path d="M13 26c-4 0-5-3-4-5 3 0 5 2 4 5z" fill="#6aa84f"/></g>
    <g class="bv-sway2" style="transform-origin:28px 32px"><path d="M28 32V22" stroke="#4a7c2a" stroke-width="2.2" stroke-linecap="round"/><path d="M28 24c-4 0-5-3-4-5 3 0 5 2 4 5z" fill="#8fc96a"/></g>
    <g class="bv-sway" style="transform-origin:43px 32px"><path d="M43 32V25" stroke="#4a7c2a" stroke-width="2.2" stroke-linecap="round"/><path d="M43 27c-4 0-5-3-4-5 3 0 5 2 4 5z" fill="#6aa84f"/></g>
            </svg>
        </span>
        <span class="bv-s bv-transplant {{ $waitScene === 'transplant' ? 'is-on' : '' }}" data-scene="transplant">
            <svg viewBox="0 0 56 56" fill="none">
    <path d="M6 46h44" stroke="#8a5a2b" stroke-width="2.6" stroke-linecap="round" opacity=".55"/>
    <path class="bv-bob" d="M28 42V30" stroke="#4a7c2a" stroke-width="2.4" stroke-linecap="round" style="transform-origin:28px 42px"/>
    <path class="bv-bob" d="M28 32c-6-1-8-5-6-9 4-1 8 3 6 9z" fill="#6aa84f" style="transform-origin:28px 42px"/>
    <path class="bv-bob" d="M28 32c6-1 8-5 6-9-4-1-8 3-6 9z" fill="#8fc96a" style="transform-origin:28px 42px"/>
    <path class="bv-sway" d="M40 26c4 0 6 3 6 6s-3 5-6 4" stroke="#c98c4a" stroke-width="3" fill="none" stroke-linecap="round" style="transform-origin:46px 32px"/>
    <path class="bv-sway" d="M16 26c-4 0-6 3-6 6s3 5 6 4" stroke="#c98c4a" stroke-width="3" fill="none" stroke-linecap="round" style="transform-origin:10px 32px"/>
            </svg>
        </span>
        <span class="bv-s bv-weeds {{ $waitScene === 'weeds' ? 'is-on' : '' }}" data-scene="weeds">
            <svg viewBox="0 0 56 56" fill="none">
    <path d="M6 44h44" stroke="#8a5a2b" stroke-width="2.6" stroke-linecap="round" opacity=".55"/>
    <g class="bv-sway" style="transform-origin:18px 44px">
        <path d="M18 44c-2-8 1-11 4-13M18 44c2-7-1-10-4-12" stroke="#9a8f52" stroke-width="2.4" fill="none" stroke-linecap="round"/>
    </g>
    <g class="bv-bob" style="transform-origin:38px 20px">
        <path d="M38 44c-2-9 1-12 4-14" stroke="#b0a45e" stroke-width="2.4" fill="none" stroke-linecap="round"/>
        <path class="bv-sway" d="M34 22c4-3 7-3 9 0" stroke="#c98c4a" stroke-width="3.2" fill="none" stroke-linecap="round" style="transform-origin:38px 22px"/>
    </g>
            </svg>
        </span>
        <span class="bv-s bv-pest {{ $waitScene === 'pest' ? 'is-on' : '' }}" data-scene="pest">
            <svg viewBox="0 0 56 56" fill="none">
    <path class="bv-sway" d="M30 40c-12-2-16-10-13-18 9-2 16 6 13 18z" fill="#6aa84f" style="transform-origin:30px 42px"/>
    <path class="bv-sway" d="M30 40c-4-8-8-12-13-14" stroke="#4a7c2a" stroke-width="1.6" fill="none" style="transform-origin:30px 42px"/>
    <g class="bv-slide">
        <circle cx="34" cy="26" r="3.4" fill="#84cc16"/><circle cx="39" cy="25" r="3.6" fill="#65a30d"/>
        <circle cx="44" cy="25" r="3.4" fill="#84cc16"/>
        <circle cx="46" cy="23" r="1" fill="#1a2e05"/>
    </g>
            </svg>
        </span>
        <span class="bv-s bv-spider {{ $waitScene === 'spider' ? 'is-on' : '' }}" data-scene="spider">
            <svg viewBox="0 0 56 56" fill="none">
    <path d="M28 6v10M12 14l6 8M44 14l-6 8M6 30h10M50 30h-10M12 44l7-7M44 44l-7-7"
          stroke="#8b98ab" stroke-width="1.6" opacity=".55"/>
    <g class="bv-bob">
        <ellipse cx="28" cy="30" rx="7" ry="8" fill="#3f3f46"/>
        <circle cx="28" cy="21" r="4" fill="#52525b"/>
        <path d="M21 26l-8-4M21 32l-9 3M35 26l8-4M35 32l9 3" stroke="#3f3f46" stroke-width="2" stroke-linecap="round"/>
        <circle cx="26" cy="20" r="1" fill="#fde047"/><circle cx="30" cy="20" r="1" fill="#fde047"/>
    </g>
            </svg>
        </span>
        <span class="bv-s bv-bird {{ $waitScene === 'bird' ? 'is-on' : '' }}" data-scene="bird">
            <svg viewBox="0 0 56 56" fill="none">
    <path d="M28 46V22" stroke="#8a5a2b" stroke-width="3" stroke-linecap="round"/>
    <path d="M14 26h28" stroke="#8a5a2b" stroke-width="3" stroke-linecap="round"/>
    <path class="bv-sway" d="M18 26l-4 12h28l-4-12z" fill="#f0b429" opacity=".8" stroke="#c98a12" stroke-width="2" style="transform-origin:28px 26px"/>
    <circle cx="28" cy="16" r="6" fill="#c98c4a" stroke="#8a5a2b" stroke-width="2"/>
    <g class="bv-slide"><path d="M44 12c-4 0-6 2-6 4 3-1 5-1 6-4z" fill="#5b6779"/></g>
            </svg>
        </span>
        <span class="bv-s bv-snail {{ $waitScene === 'snail' ? 'is-on' : '' }}" data-scene="snail">
            <svg viewBox="0 0 56 56" fill="none">
    <path d="M6 46h44" stroke="#8a5a2b" stroke-width="2.6" stroke-linecap="round" opacity=".55"/>
    <g class="bv-slide">
        <path d="M14 44c0-4 3-6 7-6h6v6z" fill="#fde047" stroke="#c98a12" stroke-width="2" stroke-linejoin="round"/>
        <path d="M14 40c-3 0-4-2-4-4" stroke="#c98a12" stroke-width="2" fill="none" stroke-linecap="round"/>
        <circle cx="10" cy="34" r="1.6" fill="#7c2d12"/>
        <circle cx="32" cy="34" r="10" fill="#e11d48" opacity=".8" stroke="#9f1239" stroke-width="2"/>
        <path d="M32 34a5 5 0 1 0 4 2" stroke="#9f1239" stroke-width="2" fill="none"/>
    </g>
            </svg>
        </span>
        <span class="bv-s bv-mosquito {{ $waitScene === 'mosquito' ? 'is-on' : '' }}" data-scene="mosquito">
            <svg viewBox="0 0 56 56" fill="none">
    <g class="bv-slide">
        <ellipse cx="28" cy="30" rx="4" ry="8" fill="#3f3f46" transform="rotate(-20 28 30)"/>
        <circle cx="24" cy="22" r="3.4" fill="#52525b"/>
        <path d="M22 20l-6-6" stroke="#3f3f46" stroke-width="2" stroke-linecap="round"/>
        <path class="bv-blink" d="M30 24c8-6 14-4 16 0-6 3-12 3-16 0z" fill="#cfd8e3" opacity=".7"/>
        <path class="bv-blink" d="M30 28c8-2 14 2 15 6-6 1-12-2-15-6z" fill="#cfd8e3" opacity=".55"/>
        <path d="M31 36l6 8M29 38l2 8" stroke="#3f3f46" stroke-width="1.6" stroke-linecap="round"/>
    </g>
            </svg>
        </span>
        <span class="bv-s bv-net {{ $waitScene === 'net' ? 'is-on' : '' }}" data-scene="net">
            <svg viewBox="0 0 56 56" fill="none">
    <path d="M28 6l20 8v6H8v-6z" fill="#cfd8e3" opacity=".8" stroke="#5b6779" stroke-width="2" stroke-linejoin="round"/>
    <path class="bv-sway" d="M12 20c-2 12 0 22 4 28M44 20c2 12 0 22-4 28" stroke="#8b98ab" stroke-width="2" fill="none" style="transform-origin:28px 20px"/>
    <path class="bv-sway" d="M14 28h28M15 36h26M17 44h22" stroke="#cfd8e3" stroke-width="1.6" style="transform-origin:28px 20px"/>
    <rect x="18" y="40" width="20" height="8" rx="2" fill="#8fc96a" opacity=".7"/>
            </svg>
        </span>
        <span class="bv-s bv-mouse {{ $waitScene === 'mouse' ? 'is-on' : '' }}" data-scene="mouse">
            <svg viewBox="0 0 56 56" fill="none">
    <path d="M6 46h44" stroke="#8a5a2b" stroke-width="2.6" stroke-linecap="round" opacity=".55"/>
    <g class="bv-slide">
        <ellipse cx="26" cy="38" rx="12" ry="7" fill="#8b98ab"/>
        <circle cx="38" cy="34" r="5" fill="#5b6779"/>
        <circle cx="35" cy="29" r="3" fill="#8b98ab"/>
        <circle cx="40" cy="33" r="1.2" fill="#111827"/>
        <path class="bv-sway" d="M14 38c-6 0-8-4-6-8" stroke="#8b98ab" stroke-width="2.2" fill="none" stroke-linecap="round" style="transform-origin:14px 38px"/>
    </g>
            </svg>
        </span>
        <span class="bv-s bv-thermometer {{ $waitScene === 'thermometer' ? 'is-on' : '' }}" data-scene="thermometer">
            <svg viewBox="0 0 56 56" fill="none">
    <rect x="24" y="8" width="8" height="28" rx="4" fill="#cfd8e3" stroke="#5b6779" stroke-width="2"/>
    <circle cx="28" cy="40" r="7" fill="#e11d48" stroke="#9f1239" stroke-width="2"/>
    <path class="bv-grow" d="M28 36V18" stroke="#e11d48" stroke-width="4" stroke-linecap="round" style="transform-origin:28px 40px"/>
    <path d="M34 14h6M34 20h4M34 26h6" stroke="#5b6779" stroke-width="1.8" stroke-linecap="round"/>
            </svg>
        </span>
        <span class="bv-s bv-pills {{ $waitScene === 'pills' ? 'is-on' : '' }}" data-scene="pills">
            <svg viewBox="0 0 56 56" fill="none">
    <g class="bv-bob">
        <rect x="10" y="24" width="20" height="11" rx="5.5" transform="rotate(-20 20 30)" fill="#e11d48" opacity=".85" stroke="#9f1239" stroke-width="2"/>
        <path d="M14 33l12-6" stroke="#9f1239" stroke-width="2"/>
    </g>
    <g class="bv-bob2">
        <circle cx="38" cy="34" r="8" fill="#7dd3fc" stroke="#5b9bd5" stroke-width="2"/>
        <path d="M34 34h8" stroke="#5b9bd5" stroke-width="2"/>
    </g>
            </svg>
        </span>
        <span class="bv-s bv-stetho {{ $waitScene === 'stetho' ? 'is-on' : '' }}" data-scene="stetho">
            <svg viewBox="0 0 56 56" fill="none">
    <path d="M18 10v10a8 8 0 0 0 16 0V10" stroke="#5b6779" stroke-width="2.6" fill="none" stroke-linecap="round"/>
    <circle cx="18" cy="9" r="2.6" fill="#5b6779"/><circle cx="34" cy="9" r="2.6" fill="#5b6779"/>
    <path d="M26 28v8a8 8 0 0 0 14 5" stroke="#5b6779" stroke-width="2.6" fill="none" stroke-linecap="round"/>
    <circle class="bv-pulse" cx="42" cy="42" r="6" fill="#8b98ab" stroke="#5b6779" stroke-width="2" style="transform-origin:42px 42px"/>
            </svg>
        </span>
        <span class="bv-s bv-eye {{ $waitScene === 'eye' ? 'is-on' : '' }}" data-scene="eye">
            <svg viewBox="0 0 56 56" fill="none">
    <path d="M6 30c8-10 16-14 22-14s14 4 22 14c-8 10-16 14-22 14s-14-4-22-14z"
          fill="none" stroke="#5b6779" stroke-width="2.4" stroke-linejoin="round"/>
    <circle class="bv-slide" cx="28" cy="30" r="7" fill="#5b9bd5"/>
    <circle class="bv-slide" cx="28" cy="30" r="3" fill="#111827"/>
    <circle class="bv-blink" cx="25" cy="27" r="1.6" fill="#fff"/>
            </svg>
        </span>
        <span class="bv-s bv-dry {{ $waitScene === 'dry' ? 'is-on' : '' }}" data-scene="dry">
            <svg viewBox="0 0 56 56" fill="none">
    <circle class="bv-spin" cx="44" cy="12" r="5" fill="#f0b429" style="transform-origin:44px 12px"/>
    <path class="bv-spin" d="M44 4v3M44 17v3M36 12h3M49 12h3M38.5 6.5l2 2M47.5 15.5l2 2M49.5 6.5l-2 2M40.5 15.5l-2 2"
          stroke="#f0b429" stroke-width="1.8" stroke-linecap="round" style="transform-origin:44px 12px"/>
    <path class="bv-sway" d="M6 44l6-14h28l6 14z" fill="#cfd8e3" opacity=".7" stroke="#5b6779" stroke-width="2" stroke-linejoin="round" style="transform-origin:28px 44px"/>
    <path class="bv-sway" d="M14 38h24M16 34h20" stroke="#f0b429" stroke-width="2.6" stroke-linecap="round" style="transform-origin:28px 44px"/>
            </svg>
        </span>
        <span class="bv-s bv-store {{ $waitScene === 'store' ? 'is-on' : '' }}" data-scene="store">
            <svg viewBox="0 0 56 56" fill="none">
    <path d="M8 24L28 10l20 14v22H8z" fill="#c98c4a" stroke="#8a5a2b" stroke-width="2" stroke-linejoin="round"/>
    <rect x="22" y="30" width="12" height="16" rx="1.5" fill="#8a5a2b"/>
    <path class="bv-bob" d="M14 46v-8h5v8M42 46v-8h-5v8" fill="#f0b429" opacity=".8"/>
    <path class="bv-pulse" d="M28 16l4 5h-8z" fill="#6aa84f" style="transform-origin:28px 19px"/>
            </svg>
        </span>
        <span class="bv-s bv-weevil {{ $waitScene === 'weevil' ? 'is-on' : '' }}" data-scene="weevil">
            <svg viewBox="0 0 56 56" fill="none">
    <ellipse cx="28" cy="34" rx="16" ry="11" fill="#f0b429" opacity=".55" stroke="#c98a12" stroke-width="2"/>
    <path d="M18 30c6-3 14-3 20 0" stroke="#c98a12" stroke-width="1.8" fill="none"/>
    <g class="bv-slide">
        <ellipse cx="30" cy="34" rx="5" ry="3" fill="#57534e"/>
        <circle cx="35" cy="33" r="2.4" fill="#44403c"/>
        <path d="M37 32l4-3" stroke="#44403c" stroke-width="1.6" stroke-linecap="round"/>
        <path d="M26 32l-3-2M26 36l-3 2" stroke="#57534e" stroke-width="1.4" stroke-linecap="round"/>
    </g>
            </svg>
        </span>
        <span class="bv-s bv-label {{ $waitScene === 'label' ? 'is-on' : '' }}" data-scene="label">
            <svg viewBox="0 0 56 56" fill="none">
    <path d="M14 14h20l12 12v20a4 4 0 0 1-4 4H14a4 4 0 0 1-4-4V18a4 4 0 0 1 4-4z" fill="#fff" stroke="#5b6779" stroke-width="2" stroke-linejoin="round"/>
    <path d="M34 14v12h12" stroke="#5b6779" stroke-width="2" fill="none" stroke-linejoin="round"/>
    <path class="bv-tick" d="M17 32h20M17 38h14M17 44h9" stroke="#4a7c2a" stroke-width="2.4" stroke-linecap="round"/>
            </svg>
        </span>
        <span class="bv-s bv-scale {{ $waitScene === 'scale' ? 'is-on' : '' }}" data-scene="scale">
            <svg viewBox="0 0 56 56" fill="none">
    <path d="M28 14v26M16 46h24" stroke="#5b6779" stroke-width="3" stroke-linecap="round"/>
    <path d="M10 20h36" stroke="#5b6779" stroke-width="2.6" stroke-linecap="round"/>
    <circle cx="28" cy="14" r="3" fill="#5b6779"/>
    <path class="bv-bob" d="M4 20l6 12h-12z" transform="translate(10 0)" fill="#cfd8e3" stroke="#5b6779" stroke-width="2" style="transform-origin:10px 26px"/>
    <path class="bv-bob2" d="M40 20l6 12h-12z" fill="#f0b429" opacity=".8" stroke="#c98a12" stroke-width="2" style="transform-origin:46px 26px"/>
            </svg>
        </span>
        <span class="bv-s bv-basket {{ $waitScene === 'basket' ? 'is-on' : '' }}" data-scene="basket">
            <svg viewBox="0 0 56 56" fill="none">
    <path class="bv-bob" d="M10 26h36l-4 20H14z" fill="#c98c4a" stroke="#8a5a2b" stroke-width="2" stroke-linejoin="round" style="transform-origin:28px 46px"/>
    <path class="bv-bob" d="M16 26v20M28 26v20M40 26v20" stroke="#8a5a2b" stroke-width="1.6" opacity=".6" style="transform-origin:28px 46px"/>
    <circle class="bv-bob" cx="20" cy="22" r="5" fill="#e11d48" style="transform-origin:28px 46px"/>
    <circle class="bv-bob" cx="30" cy="20" r="6" fill="#f0b429" style="transform-origin:28px 46px"/>
    <circle class="bv-bob" cx="40" cy="22" r="5" fill="#6aa84f" style="transform-origin:28px 46px"/>
            </svg>
        </span>
        <span class="bv-s bv-clock {{ $waitScene === 'clock' ? 'is-on' : '' }}" data-scene="clock">
            <svg viewBox="0 0 56 56" fill="none">
    <circle cx="28" cy="30" r="17" fill="#fff" stroke="#5b6779" stroke-width="2.6"/>
    <path d="M28 17v3M28 40v3M15 30h3M38 30h3" stroke="#5b6779" stroke-width="2" stroke-linecap="round"/>
    <path class="bv-spin" d="M28 30V20" stroke="#5b6779" stroke-width="2.6" stroke-linecap="round" style="transform-origin:28px 30px"/>
    <path class="bv-spin2" d="M28 30h8" stroke="#4a7c2a" stroke-width="2.6" stroke-linecap="round" style="transform-origin:28px 30px"/>
    <circle cx="28" cy="30" r="2" fill="#5b6779"/>
            </svg>
        </span>
        <span class="bv-s bv-money {{ $waitScene === 'money' ? 'is-on' : '' }}" data-scene="money">
            <svg viewBox="0 0 56 56" fill="none">
    <g class="bv-bob"><circle cx="20" cy="34" r="10" fill="#f0b429" stroke="#c98a12" stroke-width="2"/>
        <text x="20" y="39" font-size="13" font-weight="800" text-anchor="middle" fill="#8a6a10">P</text></g>
    <g class="bv-bob2"><circle cx="36" cy="28" r="9" fill="#fde047" stroke="#c98a12" stroke-width="2"/>
        <text x="36" y="33" font-size="12" font-weight="800" text-anchor="middle" fill="#8a6a10">P</text></g>
    <path class="bv-rise" d="M44 18v-6" stroke="#4a7c2a" stroke-width="2.4" stroke-linecap="round" style="transform-origin:44px 15px"/>
            </svg>
        </span>
        <span class="bv-s bv-lightning {{ $waitScene === 'lightning' ? 'is-on' : '' }}" data-scene="lightning">
            <svg viewBox="0 0 56 56" fill="none">
    <path class="bv-sway" d="M12 26a7 7 0 0 1 1-13.9A10 10 0 0 1 33 13a6.5 6.5 0 0 1 1 13z" fill="#8b98ab" stroke="#5b6779" stroke-width="2" style="transform-origin:24px 20px"/>
    <path class="bv-blink" d="M26 28l-6 12h6l-4 10 14-16h-7l4-6z" fill="#fde047" stroke="#c98a12" stroke-width="1.6" stroke-linejoin="round"/>
    <path d="M42 44V26" stroke="#8a5a2b" stroke-width="2.6" stroke-linecap="round"/>
    <circle cx="42" cy="22" r="6" fill="#4a7c2a" opacity=".7"/>
            </svg>
        </span>
        <span class="bv-s bv-windy {{ $waitScene === 'windy' ? 'is-on' : '' }}" data-scene="windy">
            <svg viewBox="0 0 56 56" fill="none">
    <path class="bv-slide" d="M6 20h22a5 5 0 1 0-5-5" stroke="#5b9bd5" stroke-width="2.6" fill="none" stroke-linecap="round"/>
    <path class="bv-slide" d="M6 30h30a6 6 0 1 1-6 6" stroke="#7dd3fc" stroke-width="2.6" fill="none" stroke-linecap="round"/>
    <path class="bv-slide" d="M6 40h16a4 4 0 1 0-4 4" stroke="#5b9bd5" stroke-width="2.4" fill="none" stroke-linecap="round" opacity=".7"/>
            </svg>
        </span>
        <span class="bv-s bv-tarp {{ $waitScene === 'tarp' ? 'is-on' : '' }}" data-scene="tarp">
            <svg viewBox="0 0 56 56" fill="none">
    <path d="M8 18h40" stroke="#8a5a2b" stroke-width="2.6" stroke-linecap="round"/>
    <path class="bv-sway" d="M10 18c6 14 30 14 36 0v22H10z" fill="#5b9bd5" opacity=".5" stroke="#5b9bd5" stroke-width="2" stroke-linejoin="round" style="transform-origin:28px 18px"/>
    <circle cx="12" cy="18" r="2" fill="#5b6779"/><circle cx="28" cy="18" r="2" fill="#5b6779"/><circle cx="44" cy="18" r="2" fill="#5b6779"/>
    <path class="bv-fall" d="M18 8v5" stroke="#7dd3fc" stroke-width="2.4" stroke-linecap="round"/>
    <path class="bv-fall2" d="M36 8v5" stroke="#7dd3fc" stroke-width="2.4" stroke-linecap="round"/>
            </svg>
        </span>
        <span class="bv-s bv-roof {{ $waitScene === 'roof' ? 'is-on' : '' }}" data-scene="roof">
            <svg viewBox="0 0 56 56" fill="none">
    <path d="M6 32L28 14l22 18" stroke="#5b6779" stroke-width="2.6" fill="none" stroke-linejoin="round"/>
    <path class="bv-sway" d="M14 32h28v14H14z" fill="#cfd8e3" stroke="#5b6779" stroke-width="2" style="transform-origin:28px 46px"/>
    <path d="M20 32v14M28 32v14M36 32v14" stroke="#5b6779" stroke-width="1.6" opacity=".6"/>
    <path class="bv-bob" d="M40 20l8-4v8z" fill="#8b98ab" stroke="#5b6779" stroke-width="2" stroke-linejoin="round" style="transform-origin:44px 20px"/>
            </svg>
        </span>
        <span class="bv-s bv-calendar {{ $waitScene === 'calendar' ? 'is-on' : '' }}" data-scene="calendar">
            <svg viewBox="0 0 56 56" fill="none">
    <rect x="10" y="14" width="36" height="32" rx="4" fill="#fff" stroke="#5b6779" stroke-width="2.4"/>
    <path d="M18 10v8M38 10v8M10 24h36" stroke="#5b6779" stroke-width="2.4" stroke-linecap="round"/>
    <circle class="bv-pulse" cx="20" cy="32" r="2.6" fill="#6aa84f" style="transform-origin:20px 32px"/>
    <circle class="bv-pulse2" cx="28" cy="32" r="2.6" fill="#f0b429" style="transform-origin:28px 32px"/>
    <circle class="bv-pulse" cx="36" cy="32" r="2.6" fill="#5b9bd5" style="transform-origin:36px 32px"/>
    <path class="bv-tick" d="M18 40l4 4 8-8" stroke="#4a7c2a" stroke-width="2.6" fill="none" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </span>
    </span>
        {{-- Noon, and the heat off the ground. --}}
        <span class="bv-s bv-sun {{ $waitScene === 'sun' ? 'is-on' : '' }}" data-scene="sun">
            <svg viewBox="0 0 56 56" fill="none">
                <g class="sn-rays">
                    <path d="M28 4v5M28 39v5M8 24h5M43 24h5M13.6 9.6l3.6 3.6M38.8 34.8l3.6 3.6M42.4 9.6l-3.6 3.6M17.2 34.8l-3.6 3.6"
                          stroke="#f0b429" stroke-width="2.8" stroke-linecap="round"/>
                </g>
                <circle class="sn-core" cx="28" cy="24" r="8" fill="#fbbf24" stroke="#e09b13" stroke-width="2"/>
                <path class="sn-heat sn-heat-1" d="M18 50c2-2 2-4 0-6" stroke="#e6b06a" stroke-width="2" stroke-linecap="round"/>
                <path class="sn-heat sn-heat-2" d="M36 50c2-2 2-4 0-6" stroke="#e6b06a" stroke-width="2" stroke-linecap="round"/>
                <path d="M10 52h36" stroke="#c9a06a" stroke-width="2.6" stroke-linecap="round"/>
            </svg>
        </span>
        {{-- A glass, filling. --}}
        <span class="bv-s bv-water {{ $waitScene === 'water' ? 'is-on' : '' }}" data-scene="water">
            <svg viewBox="0 0 56 56" fill="none">
                <path class="wg-drop" d="M28 8c2 3 3 4.4 3 6a3 3 0 0 1-6 0c0-1.6 1-3 3-6z" fill="#5b9bd5"/>
                {{-- A straight glass on purpose: the water inside is a plain
                     rectangle scaled from its foot, which needs no clip path
                     — and a clip path would collide with itself, since three
                     of these cards can share one page. --}}
                <rect class="wg-fill" x="18.5" y="21" width="19" height="24" rx="1.5" fill="#5b9bd5" opacity=".55"/>
                <rect x="17" y="20" width="22" height="26" rx="3" stroke="#7aa7cf" stroke-width="2.4"/>
                <path class="wg-shine" d="M21.5 24.5v10" stroke="#eaf3fb" stroke-width="2.2" stroke-linecap="round"/>
            </svg>
        </span>
        {{-- One capsule. --}}
        <span class="bv-s bv-vitamin {{ $waitScene === 'vitamin' ? 'is-on' : '' }}" data-scene="vitamin">
            <svg viewBox="0 0 56 56" fill="none">
                <g class="vt-cap">
                    <path d="M16 28a7 7 0 0 1 7-7h5v14h-5a7 7 0 0 1-7-7z" fill="#f0b429" stroke="#c98a12" stroke-width="2"/>
                    <path d="M28 21h5a7 7 0 0 1 0 14h-5z" fill="#fdfdfb" stroke="#c8ceda" stroke-width="2"/>
                    <path class="vt-shine" d="M22 24.5v7" stroke="#fff6de" stroke-width="2.2" stroke-linecap="round"/>
                </g>
            </svg>
        </span>
        {{-- The wand, and the mist. --}}
        <span class="bv-s bv-spray {{ $waitScene === 'spray' ? 'is-on' : '' }}" data-scene="spray">
            <svg viewBox="0 0 56 56" fill="none">
                <g class="sp-wand">
                    <path d="M10 46l14-10" stroke="#4a7c2a" stroke-width="3.4" stroke-linecap="round"/>
                    <path d="M23 38l6-4.4" stroke="#8a5a2b" stroke-width="4.4" stroke-linecap="round"/>
                </g>
                <g fill="#9fc0dc">
                    <circle class="sp-mist sp-m1" cx="32" cy="31" r="2.6"/>
                    <circle class="sp-mist sp-m2" cx="34" cy="34" r="2"/>
                    <circle class="sp-mist sp-m3" cx="35" cy="28" r="1.7"/>
                </g>
                {{-- The leaf it is meant for, so the scene is a job and not
                     just a jet of something. --}}
                <path d="M46 16c0 6-4 10-10 10 0-6 4-10 10-10z" fill="#8fc267" stroke="#4a7c2a" stroke-width="1.8" stroke-linejoin="round"/>
            </svg>
        </span>
        {{-- Boots by the door. --}}
        <span class="bv-s bv-boots {{ $waitScene === 'boots' ? 'is-on' : '' }}" data-scene="boots">
            <svg viewBox="0 0 56 56" fill="none">
                <g class="bt-left">
                    <path d="M15 16h9v18l5 3v7H15z" fill="#4a7c2a" stroke="#2f5219" stroke-width="2" stroke-linejoin="round"/>
                    <path d="M15 38h14" stroke="#2f5219" stroke-width="1.8"/>
                </g>
                <g class="bt-right">
                    <path d="M32 16h9v18l5 3v7H32z" fill="#6b9f3d" stroke="#2f5219" stroke-width="2" stroke-linejoin="round"/>
                    <path d="M32 38h14" stroke="#2f5219" stroke-width="1.8"/>
                </g>
                <circle class="bt-mud" cx="28" cy="47" r="1.6" fill="#8a5a2b"/>
            </svg>
        </span>
        {{-- The blade on the stone. --}}
        <span class="bv-s bv-tools {{ $waitScene === 'tools' ? 'is-on' : '' }}" data-scene="tools">
            <svg viewBox="0 0 56 56" fill="none">
                <rect x="10" y="38" width="34" height="7" rx="3.5" fill="#b9b2a6" stroke="#8d867a" stroke-width="2"/>
                <g class="tl-blade">
                    <path d="M20 34l20-16c3 6 2 12-3 16z" fill="#dfe5ec" stroke="#8e9aab" stroke-width="2" stroke-linejoin="round"/>
                    <path d="M18 35l4-3" stroke="#8a5a2b" stroke-width="5" stroke-linecap="round"/>
                </g>
                <path class="tl-spark" d="M33 30l1.6 3.4L38 35l-3.4 1.6L33 40l-1.6-3.4L28 35l3.4-1.6z" fill="#f7d774"/>
            </svg>
        </span>
        {{-- The kit. --}}
        <span class="bv-s bv-firstaid {{ $waitScene === 'firstaid' ? 'is-on' : '' }}" data-scene="firstaid">
            <svg viewBox="0 0 56 56" fill="none">
                <path class="fa-lid" d="M12 22h32a2 2 0 0 1 2 2v3H10v-3a2 2 0 0 1 2-2z" fill="#c8ceda" stroke="#8e9aab" stroke-width="2" stroke-linejoin="round"/>
                <path d="M23 22v-3a2 2 0 0 1 2-2h6a2 2 0 0 1 2 2v3" stroke="#8e9aab" stroke-width="2" stroke-linejoin="round"/>
                <rect x="10" y="27" width="36" height="19" rx="3" fill="#f4f6f9" stroke="#8e9aab" stroke-width="2"/>
                <path class="fa-cross" d="M28 30v9M23.5 34.5h9" stroke="#d64545" stroke-width="3.4" stroke-linecap="round"/>
            </svg>
        </span>
        {{-- The record. --}}
        <span class="bv-s bv-notebook {{ $waitScene === 'notebook' ? 'is-on' : '' }}" data-scene="notebook">
            <svg viewBox="0 0 56 56" fill="none">
                <rect x="12" y="10" width="32" height="36" rx="3" fill="#fdfdfb" stroke="#c8ceda" stroke-width="2"/>
                <path d="M18 10v36" stroke="#c8ceda" stroke-width="2"/>
                <path d="M23 20h16M23 26h16" stroke="#dfe3ea" stroke-width="2" stroke-linecap="round"/>
                <path class="nb-ink" d="M23 33h16" stroke="#4a7c2a" stroke-width="2.4" stroke-linecap="round"/>
                <g class="nb-pencil">
                    <path d="M36 30l5-5 3 3-5 5z" fill="#f0b429" stroke="#c98a12" stroke-width="1.6" stroke-linejoin="round"/>
                    <path d="M36 30l-1.6 4.6 4.6-1.6z" fill="#8a5a2b"/>
                </g>
            </svg>
        </span>
        {{-- The seedbed. --}}
        <span class="bv-s bv-seedling {{ $waitScene === 'seedling' ? 'is-on' : '' }}" data-scene="seedling">
            <svg viewBox="0 0 56 56" fill="none">
                <path d="M8 44h40" stroke="#8a5a2b" stroke-width="3" stroke-linecap="round"/>
                <path class="sd-stem" d="M28 44V22" stroke="#4a7c2a" stroke-width="3" stroke-linecap="round"/>
                <path class="sd-leaf sd-leaf-l" d="M28 30c-8 0-11-4-11-8 5-1 11 2 11 8z" fill="#6b9f3d"/>
                <path class="sd-leaf sd-leaf-r" d="M28 26c8 0 11-4 11-8-5-1-11 2-11 8z" fill="#8fc267"/>
            </svg>
        </span>
        {{-- The machine. --}}
        <span class="bv-s bv-tractor {{ $waitScene === 'tractor' ? 'is-on' : '' }}" data-scene="tractor">
            <svg viewBox="0 0 56 56" fill="none">
                <g class="tr-body">
                    <path d="M14 34V24h9l3 8h10v2" stroke="#4a7c2a" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M22 24v-6h6v6" stroke="#4a7c2a" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
                    <path class="tr-puff tr-puff-1" d="M17 18v-3" stroke="#b7c4a8" stroke-width="3" stroke-linecap="round"/>
                    <path class="tr-puff tr-puff-2" d="M17 12V9" stroke="#b7c4a8" stroke-width="3" stroke-linecap="round"/>
                </g>
                <g class="tr-wheel tr-wheel-big">
                    <circle cx="19" cy="38" r="8" stroke="#3d6823" stroke-width="3"/>
                    <path d="M19 32v12M13 38h12" stroke="#3d6823" stroke-width="2"/>
                </g>
                <g class="tr-wheel tr-wheel-small">
                    <circle cx="39" cy="40" r="5" stroke="#3d6823" stroke-width="3"/>
                    <path d="M39 36v8M35 40h8" stroke="#3d6823" stroke-width="2"/>
                </g>
            </svg>
        </span>
        {{-- Five more minutes. --}}
        <span class="bv-s bv-carabao {{ $waitScene === 'carabao' ? 'is-on' : '' }}" data-scene="carabao">
            <svg viewBox="0 0 56 56" fill="none">
                <g class="cb-head">
                    <path d="M20 18C13 20 7 18 4 11" stroke="#7c828b" stroke-width="4" stroke-linecap="round" fill="none"/>
                    <path d="M36 18c7 2 13 0 16-7" stroke="#7c828b" stroke-width="4" stroke-linecap="round" fill="none"/>
                    <path d="M20 16h16a6 6 0 0 1 6 6v6c0 8-6 13-14 13s-14-5-14-13v-6a6 6 0 0 1 6-6z" fill="#8b8f95" stroke="#5f636a" stroke-width="2"/>
                    <circle cx="23" cy="26" r="2" fill="#33383f"/>
                    <circle cx="33" cy="26" r="2" fill="#33383f"/>
                    <ellipse cx="28" cy="35" rx="8" ry="5.5" fill="#a7acb3" stroke="#5f636a" stroke-width="1.6"/>
                    <g class="cb-nose">
                        <circle cx="25" cy="35" r="1.3" fill="#4a4f56"/>
                        <circle cx="31" cy="35" r="1.3" fill="#4a4f56"/>
                    </g>
                </g>
            </svg>
        </span>
        {{-- The head, and the grain drying under it. --}}
        <span class="bv-s bv-rice {{ $waitScene === 'rice' ? 'is-on' : '' }}" data-scene="rice">
            <svg viewBox="0 0 56 56" fill="none">
                <g class="rc-stalk">
                    <path d="M28 44c0-14 2-22 6-28" stroke="#6b9f3d" stroke-width="3" stroke-linecap="round"/>
                    <g fill="#d9b23c">
                        <ellipse cx="36" cy="14" rx="2.6" ry="4.4" transform="rotate(24 36 14)"/>
                        <ellipse cx="31" cy="20" rx="2.6" ry="4.4" transform="rotate(24 31 20)"/>
                        <ellipse cx="39" cy="21" rx="2.6" ry="4.4" transform="rotate(24 39 21)"/>
                        <ellipse cx="33" cy="28" rx="2.6" ry="4.4" transform="rotate(24 33 28)"/>
                    </g>
                </g>
                <g class="rc-grain" fill="#e2c46b">
                    <ellipse cx="18" cy="46" rx="7" ry="3"/>
                    <ellipse cx="30" cy="48" rx="9" ry="3.2"/>
                    <ellipse cx="41" cy="46" rx="6" ry="2.6"/>
                </g>
                <path d="M8 52h40" stroke="#c9a06a" stroke-width="2.6" stroke-linecap="round"/>
            </svg>
        </span>
        {{-- The can tips. --}}
        <span class="bv-s bv-watering {{ $waitScene === 'watering' ? 'is-on' : '' }}" data-scene="watering">
            <svg viewBox="0 0 56 56" fill="none">
                <g class="wt-can">
                    <path d="M20 22h16v14a4 4 0 0 1-4 4H24a4 4 0 0 1-4-4V22z" fill="#8fc267" stroke="#3d6823" stroke-width="2"/>
                    <path d="M24 22v-4h8v4" stroke="#3d6823" stroke-width="2.5" stroke-linecap="round"/>
                    <path d="M20 26l-9 4" stroke="#3d6823" stroke-width="3" stroke-linecap="round"/>
                </g>
                <path class="wt-drop wt-1" d="M10 34v4" stroke="#5b9bd5" stroke-width="3" stroke-linecap="round"/>
                <path class="wt-drop wt-2" d="M14 36v4" stroke="#5b9bd5" stroke-width="3" stroke-linecap="round"/>
            </svg>
        </span>
        {{-- One bee. --}}
        <span class="bv-s bv-bee {{ $waitScene === 'bee' ? 'is-on' : '' }}" data-scene="bee">
            <svg viewBox="0 0 56 56" fill="none">
                <g class="be-fly">
                    <ellipse class="be-wing be-wing-l" cx="21" cy="20" rx="7" ry="4" transform="rotate(-22 21 20)" fill="#dbeafe" stroke="#8fb3d4" stroke-width="1.5"/>
                    <ellipse class="be-wing be-wing-r" cx="35" cy="20" rx="7" ry="4" transform="rotate(22 35 20)" fill="#dbeafe" stroke="#8fb3d4" stroke-width="1.5"/>
                    <ellipse cx="28" cy="33" rx="10" ry="7.5" fill="#f0b429" stroke="#8a5a2b" stroke-width="2"/>
                    <path d="M25 26.4v13.2M30.5 26.4v13.2" stroke="#8a5a2b" stroke-width="2.6"/>
                    <path d="M23 25l-2-4M33 25l2-4" stroke="#8a5a2b" stroke-width="1.8" stroke-linecap="round"/>
                </g>
            </svg>
        </span>
        {{-- The end of the day. --}}
        <span class="bv-s bv-moon {{ $waitScene === 'moon' ? 'is-on' : '' }}" data-scene="moon">
            <svg viewBox="0 0 56 56" fill="none">
                <path class="mn-body" d="M34 8a16 16 0 1 0 11 25A19 19 0 0 1 34 8z" fill="#e8edf5" stroke="#a9b4c6" stroke-width="2"/>
                <g fill="#f0b429">
                    <circle class="mn-star mn-s1" cx="13" cy="14" r="2"/>
                    <circle class="mn-star mn-s2" cx="46" cy="17" r="1.6"/>
                    <circle class="mn-star mn-s3" cx="16" cy="40" r="1.6"/>
                </g>
                <path class="mn-cloud" d="M16 34a4.5 4.5 0 0 1 .8-8.9A6.6 6.6 0 0 1 29 26a4.2 4.2 0 0 1 .7 8H16z" fill="#c3cbd9" opacity=".9"/>
                <g fill="#8e9aab" font-family="system-ui, sans-serif" font-weight="800">
                    <text class="mn-z mn-z1" x="40" y="46" font-size="9">z</text>
                    <text class="mn-z mn-z2" x="46" y="40" font-size="7">z</text>
                </g>
            </svg>
        </span>
    </span>
    <span class="bv-text" data-wait-line>{{ $waitFirst['line'] }}</span>
    {{-- The reason. A reminder with no reason behind it is nagging; with one
         it is somebody who knows something telling you why. --}}
    <span class="bv-sub" data-wait-sub>{{ $waitFirst['sub'] ?? '' }}</span>
</div>
@once
    {{-- Inline for the same reason as the styles above. --}}
    <script>
    /* One reminder per wait, drawn at random. The pool travels with the page,
       so re-rolling costs nothing and a farmer opening the same board twice
       in a morning is not read the same line twice. */
    (function () {
        window.WAIT_LINES = @json($waitPool);

        /* How long a wait is allowed to be over.
         *
         * Two problems, one answer. A screen that finishes in eighty
         * milliseconds still flashes a card nobody can read, and a screen
         * that finishes at the exact moment the last widget is still
         * settling shows the finished page mid-arrangement. So every wait
         * gets a floor whatever happens, and a wait that was over almost
         * before it began gets a further half so the reminder can actually
         * be read.
         *
         * The floor is four seconds because the card is not decoration — it
         * is a sentence and a subtitle about not spraying into the wind, and
         * two seconds was long enough to notice one and not long enough to
         * finish it. A reminder nobody can read is a spinner with extra
         * steps.
         *
         * These are display floors, not delays added to real work: a job
         * that genuinely takes four seconds waits no longer than it did. */
        const MIN_VISIBLE = 4000;
        const TOO_QUICK = 350;
        const EXTRA_FOR_QUICK = 500;

        /** Stamp the moment a card went up. */
        window.waitCardShown = function (host) {
            const card = (host && host.querySelector) ? (host.querySelector('.bv-card') || host) : host;
            if (card) card.__shownAt = performance.now();
            return card;
        };

        /**
         * Do the hiding, but not before the reminder has had its moment.
         * Callers pass what they would have run immediately.
         */
        window.waitCardRelease = function (host, done) {
            const card = (host && host.querySelector) ? (host.querySelector('.bv-card') || host) : host;
            const shownAt = (card && card.__shownAt) || 0;
            const seen = shownAt ? performance.now() - shownAt : MIN_VISIBLE;
            const hold = Math.max(0, MIN_VISIBLE - seen) + (seen < TOO_QUICK ? EXTRA_FOR_QUICK : 0);
            if (hold <= 0) { done(); return; }
            setTimeout(done, hold);
        };

        window.rollWaitLine = function (host) {
            const card = host || document.querySelector('.bv-card');
            const pool = window.WAIT_LINES || [];
            if (!card) return;
            card.__shownAt = performance.now();
            if (pool.length < 2) return;

            /* Never the same picture twice running.
             *
             * Random with replacement over a pool of thirty will show you the
             * same drawing back to back about once in thirty waits, and that
             * is the one time anybody notices — a wait that ends and starts
             * again on the same picture reads as the app not having done
             * anything. Ten attempts is plenty; the eleventh is not worth a
             * loop that could spin on a one-scene pool.
             */
            const showing = (card.querySelector('.bv-s.is-on') || {}).dataset;
            const was = showing ? showing.scene : null;
            let pick = pool[Math.floor(Math.random() * pool.length)];
            for (let i = 0; i < 10 && pick.scene === was; i++) {
                pick = pool[Math.floor(Math.random() * pool.length)];
            }
            const text = card.querySelector('[data-wait-line]');
            if (text) text.textContent = pick.line;
            const sub = card.querySelector('[data-wait-sub]');
            if (sub) sub.textContent = pick.sub || '';
            let scene = card.querySelector('.bv-s[data-scene="' + pick.scene + '"]');
            // A line added from the admin naming a scene this build does not
            // know about still shows — it just grows a seedling.
            if (!scene) scene = card.querySelector('.bv-s[data-scene="seedling"]');
            card.querySelectorAll('.bv-s').forEach((s) => s.classList.toggle('is-on', s === scene));
        };

        // Cards that are on the page from the first paint (the board's own
        // veil) were never "shown" by anything — stamp them now so their
        // floor is measured from when the reader could first see them.
        document.querySelectorAll('.bv-card').forEach((c) => { c.__shownAt = performance.now(); });
    })();
    </script>
@endonce
