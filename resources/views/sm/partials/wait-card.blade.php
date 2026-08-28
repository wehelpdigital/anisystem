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
         * gets a full second whatever happens, and a wait that was over
         * almost before it began gets a further half so the reminder can
         * actually be read.
         *
         * These are display floors, not delays added to real work: a job
         * that genuinely takes two seconds waits no longer than it did. */
        const MIN_VISIBLE = 1000;
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
            const pick = pool[Math.floor(Math.random() * pool.length)];
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
