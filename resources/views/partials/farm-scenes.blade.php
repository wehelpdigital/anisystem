@once
@php
    // The books come from here, not from whoever included the partial. Six
    // screens use these scenes and asking each of them to remember to pass
    // two arrays is six chances to forget one — and a forgotten one is a
    // page of unlabelled drawings that nobody can tell apart.
    $fsActs = \App\Models\AsActivityScene::map();
    $fsCrops = \App\Models\AsCropScene::map();
@endphp
{{-- THE FARM'S BOOK OF LITTLE MOVING PICTURES.

     Two families of drawing, both animated, both drawn from the tables the
     migration seeds:

       • the day's work  — a plough, a knapsack sprayer, a hand scattering
         granules, a sickle. What KIND of day this is, said before you have
         opened the board.

       • the crop        — a plant at the point of its season. Fifteen plant
         shapes across six bands, generated rather than hand-drawn, because
         eighty-five crops × six stages of hand-drawing is five hundred
         pictures that would mostly agree with each other. A mango and a
         santol are the same silhouette; what differs is the family and how
         far through it is.

     Every scene is one inline SVG string with animated classes. No sprite
     sheet, no external file: the CSP on this app forbids remote assets and a
     drawing that has to be fetched is a drawing that arrives after the
     card it belongs to.

     Painted rather than server-rendered. Blade leaves a slot —
     <span class="fs-slot" data-fs-crop="grain" data-fs-band="4"></span> —
     and fsPaint fills it. One generator, one place to fix a leaf, and Blade
     never has to know how to draw. The weather scenes work exactly this way
     and this follows them deliberately. --}}
<style>
    /* ---- the ground everything stands on -------------------------------- */
    .fs-scene { display: block; overflow: visible; }
    .fs-slot { display: inline-flex; align-items: center; justify-content: center;
        flex: 0 0 auto; line-height: 0; }

    /* A tinted, drifting pane behind a scene, so the picture sits in weather
       rather than on white. The same gradSweep the day headers and the
       forecast panels ride. */
    .fs-pane { position: relative; border-radius: 1rem; background-size: 240% 240%;
        animation: fsSweep 18s ease-in-out infinite alternate; }
    @keyframes fsSweep { from { background-position: 0% 50%; } to { background-position: 100% 50%; } }

    .fs-hue-leaf   { background-image: linear-gradient(135deg, rgb(107 159 61 / .20), rgb(168 204 126 / .12) 45%, rgb(74 124 42 / .18)); }
    .fs-hue-sprout { background-image: linear-gradient(135deg, rgb(143 194 103 / .20), rgb(205 232 172 / .12) 45%, rgb(107 159 61 / .18)); }
    .fs-hue-soil   { background-image: linear-gradient(135deg, rgb(138 90 43 / .18), rgb(190 145 96 / .11) 45%, rgb(105 66 30 / .18)); }
    .fs-hue-water  { background-image: linear-gradient(135deg, rgb(13 148 136 / .18), rgb(56 189 248 / .12) 45%, rgb(30 64 175 / .18)); }
    .fs-hue-sky    { background-image: linear-gradient(135deg, rgb(56 189 248 / .20), rgb(147 197 253 / .12) 45%, rgb(37 99 235 / .16)); }
    .fs-hue-sun    { background-image: linear-gradient(135deg, rgb(251 191 36 / .22), rgb(253 224 71 / .12) 45%, rgb(240 180 41 / .18)); }
    .fs-hue-bloom  { background-image: linear-gradient(135deg, rgb(244 114 182 / .18), rgb(253 224 71 / .12) 45%, rgb(217 119 6 / .16)); }
    .fs-hue-fill   { background-image: linear-gradient(135deg, rgb(249 168 37 / .22), rgb(251 191 36 / .12) 45%, rgb(217 130 20 / .18)); }
    .fs-hue-gold   { background-image: linear-gradient(135deg, rgb(180 83 9 / .22), rgb(217 178 60 / .12) 45%, rgb(120 53 15 / .18)); }
    .fs-hue-amber  { background-image: linear-gradient(135deg, rgb(217 119 6 / .20), rgb(251 191 36 / .12) 45%, rgb(146 64 14 / .16)); }
    .fs-hue-rose   { background-image: linear-gradient(135deg, rgb(225 29 72 / .16), rgb(251 113 133 / .10) 45%, rgb(159 18 57 / .16)); }
    .fs-hue-violet { background-image: linear-gradient(135deg, rgb(124 58 237 / .16), rgb(167 139 250 / .10) 45%, rgb(76 29 149 / .16)); }
    .fs-hue-slate  { background-image: linear-gradient(135deg, rgb(71 85 105 / .16), rgb(148 163 184 / .10) 45%, rgb(30 41 59 / .16)); }

    /* After dark the same twelve, lifted: a fifth of an alpha is invisible
       against a dark card, which is the mistake the growth bands made first
       time round. */
    html.dark .fs-hue-leaf   { background-image: linear-gradient(135deg, rgb(107 159 61 / .32), rgb(143 194 103 / .18) 45%, rgb(74 124 42 / .28)); }
    html.dark .fs-hue-sprout { background-image: linear-gradient(135deg, rgb(143 194 103 / .28), rgb(190 220 150 / .16) 45%, rgb(107 159 61 / .26)); }
    html.dark .fs-hue-soil   { background-image: linear-gradient(135deg, rgb(138 90 43 / .32), rgb(180 130 80 / .18) 45%, rgb(110 70 32 / .30)); }
    html.dark .fs-hue-water  { background-image: linear-gradient(135deg, rgb(13 148 136 / .30), rgb(56 189 248 / .18) 45%, rgb(30 64 175 / .28)); }
    html.dark .fs-hue-sky    { background-image: linear-gradient(135deg, rgb(56 189 248 / .30), rgb(147 197 253 / .16) 45%, rgb(37 99 235 / .26)); }
    html.dark .fs-hue-sun    { background-image: linear-gradient(135deg, rgb(251 191 36 / .30), rgb(253 224 71 / .16) 45%, rgb(240 180 41 / .26)); }
    html.dark .fs-hue-bloom  { background-image: linear-gradient(135deg, rgb(244 114 182 / .28), rgb(253 224 71 / .16) 45%, rgb(217 119 6 / .26)); }
    html.dark .fs-hue-fill   { background-image: linear-gradient(135deg, rgb(249 168 37 / .30), rgb(251 191 36 / .16) 45%, rgb(217 130 20 / .26)); }
    html.dark .fs-hue-gold   { background-image: linear-gradient(135deg, rgb(217 119 6 / .32), rgb(217 178 60 / .16) 45%, rgb(146 64 14 / .28)); }
    html.dark .fs-hue-amber  { background-image: linear-gradient(135deg, rgb(217 119 6 / .30), rgb(251 191 36 / .16) 45%, rgb(146 64 14 / .26)); }
    html.dark .fs-hue-rose   { background-image: linear-gradient(135deg, rgb(225 29 72 / .28), rgb(251 113 133 / .16) 45%, rgb(159 18 57 / .26)); }
    html.dark .fs-hue-violet { background-image: linear-gradient(135deg, rgb(139 92 246 / .28), rgb(167 139 250 / .16) 45%, rgb(76 29 149 / .26)); }
    html.dark .fs-hue-slate  { background-image: linear-gradient(135deg, rgb(100 116 139 / .28), rgb(148 163 184 / .16) 45%, rgb(30 41 59 / .30)); }

    /* ---- the shared motions --------------------------------------------
       Long, slow, offset. A card can carry five of these at once and the
       whole point is that they read as a field breathing rather than as
       five things demanding attention. */
    @keyframes fsSway   { 0%,100% { transform: rotate(-3deg); } 50% { transform: rotate(3deg); } }
    @keyframes fsSwayHard { 0%,100% { transform: rotate(-7deg); } 50% { transform: rotate(7deg); } }
    @keyframes fsBob    { 0%,100% { transform: translateY(0); } 50% { transform: translateY(-1.6px); } }
    @keyframes fsPulse  { 0%,100% { transform: scale(1); opacity: 1; } 50% { transform: scale(1.12); opacity: .82; } }
    @keyframes fsSpin   { to { transform: rotate(360deg); } }
    @keyframes fsFall   { 0% { transform: translateY(-6px); opacity: 0; } 25% { opacity: 1; } 100% { transform: translateY(9px); opacity: 0; } }
    @keyframes fsMist   { 0% { transform: translate(0,0) scale(.6); opacity: 0; } 30% { opacity: .85; } 100% { transform: translate(11px,4px) scale(1.25); opacity: 0; } }
    @keyframes fsRoll   { 0%,100% { transform: translateX(-1.5px); } 50% { transform: translateX(1.5px); } }
    @keyframes fsHunt   { 0%,100% { transform: translate(0,0); } 35% { transform: translate(6px,-3px); } 70% { transform: translate(-4px,2px); } }
    @keyframes fsTick   { 0%, 30% { stroke-dashoffset: 12; } 45%, 100% { stroke-dashoffset: 0; } }
    @keyframes fsFlow   { to { stroke-dashoffset: -16; } }
    @keyframes fsRise   { 0% { transform: translateY(3px) scale(.75); opacity: 0; } 40% { opacity: .9; } 100% { transform: translateY(-9px) scale(1.1); opacity: 0; } }
    @keyframes fsCut    { 0%,100% { transform: rotate(-14deg); } 45% { transform: rotate(10deg); } }
    @keyframes fsWalk   { 0%,100% { transform: translateY(0); } 50% { transform: translateY(-1.2px); } }

    .fs-sway   { animation: fsSway 4.2s ease-in-out infinite; }
    .fs-sway2  { animation: fsSway 5.1s ease-in-out infinite .4s; }
    .fs-sway3  { animation: fsSwayHard 3.6s ease-in-out infinite .8s; }
    .fs-bob    { animation: fsBob 3.4s ease-in-out infinite; }
    .fs-bob2   { animation: fsBob 4s ease-in-out infinite .5s; }
    .fs-pulse  { animation: fsPulse 3s ease-in-out infinite; }
    .fs-spin   { animation: fsSpin 18s linear infinite; }
    .fs-fall   { animation: fsFall 1.9s linear infinite; }
    .fs-fall2  { animation: fsFall 1.9s linear infinite .55s; }
    .fs-fall3  { animation: fsFall 1.9s linear infinite 1.1s; }
    .fs-mist   { animation: fsMist 1.8s ease-out infinite; }
    .fs-mist2  { animation: fsMist 1.8s ease-out infinite .45s; }
    .fs-mist3  { animation: fsMist 1.8s ease-out infinite .9s; }
    .fs-roll   { animation: fsRoll 2.6s ease-in-out infinite; }
    .fs-hunt   { animation: fsHunt 4.5s ease-in-out infinite; }
    .fs-tick   { stroke-dasharray: 12; animation: fsTick 3.2s ease-in-out infinite; }
    .fs-tick2  { stroke-dasharray: 12; animation: fsTick 3.2s ease-in-out infinite .7s; }
    .fs-flow   { stroke-dasharray: 5 4; animation: fsFlow 1.4s linear infinite; }
    .fs-rise   { animation: fsRise 2.6s ease-out infinite; }
    .fs-rise2  { animation: fsRise 2.6s ease-out infinite .7s; }
    .fs-rise3  { animation: fsRise 2.6s ease-out infinite 1.4s; }
    .fs-cut    { animation: fsCut 2.4s ease-in-out infinite; }
    .fs-walk   { animation: fsWalk 1.5s ease-in-out infinite; }
    .fs-walk2  { animation: fsWalk 1.5s ease-in-out infinite .35s; }
    .fs-walk3  { animation: fsWalk 1.5s ease-in-out infinite .7s; }

    /* Everything stops for anybody who has asked the system to stop things. */
    @media (prefers-reduced-motion: reduce) {
        .fs-scene *, .fs-pane { animation: none !important; }
    }
</style>

<script>
(function () {
    'use strict';

    /* The books, as the tables have them. Labels and advice come from the
       database; the drawings are code, because a drawing that moves cannot
       live in a varchar. */
    window.FS_ACTS = @json($fsActs ?? []);
    window.FS_CROPS = @json($fsCrops ?? []);
    /* Which crop is which plant. The same table the model holds — screens
       that render their lots in JavaScript need to answer this question in
       the browser, and a second hand-kept copy would drift. */
    window.FS_FAMILY = @json(\App\Models\AsCropScene::OF_CROP);
    window.fsFamilyOf = function (crop) {
        return (window.FS_FAMILY || {})[crop] || 'mixed';
    };

    /* ------------------------------------------------------------------ *
     * THE DAY'S WORK
     *
     * Eighteen scenes, one 48×48 box each. Each is what somebody standing
     * in the field would be holding.
     * ------------------------------------------------------------------ */
    const GROUND = '<path d="M4 40h40" stroke="#a3865a" stroke-width="2.4" stroke-linecap="round" opacity=".55"/>';
    const A = {};

    // Land preparation: a share turning a furrow, the soil folding over.
    A.plough = `${GROUND}
        <g class="fs-roll">
            <path d="M14 38l6-14 4 2-4 12z" fill="#8b98ab" stroke="#5b6779" stroke-width="1.6" stroke-linejoin="round"/>
            <path d="M20 24l10-6" stroke="#5b6779" stroke-width="2.4" stroke-linecap="round"/>
            <circle cx="33" cy="16" r="3" fill="#c2410c"/>
        </g>
        <path class="fs-sway" d="M26 38c3-4 7-4 10 0" fill="none" stroke="#8a5a2b" stroke-width="2.6" stroke-linecap="round" style="transform-origin:26px 38px"/>
        <path class="fs-sway2" d="M32 38c2-5 6-6 9-3" fill="none" stroke="#a06a34" stroke-width="2.2" stroke-linecap="round" style="transform-origin:32px 38px"/>`;

    // Seed treatment: a tray of seed and the dust settling into it.
    A.seedbed = `${GROUND}
        <rect x="11" y="28" width="26" height="10" rx="2" fill="#c9a227" opacity=".28" stroke="#9a7b1f" stroke-width="1.8"/>
        <circle cx="17" cy="33" r="1.9" fill="#d9a441"/><circle cx="24" cy="34" r="1.9" fill="#d9a441"/><circle cx="31" cy="33" r="1.9" fill="#d9a441"/>
        <circle class="fs-fall" cx="17" cy="22" r="1.3" fill="#7dd3fc"/>
        <circle class="fs-fall2" cx="24" cy="22" r="1.3" fill="#7dd3fc"/>
        <circle class="fs-fall3" cx="31" cy="22" r="1.3" fill="#7dd3fc"/>`;

    // Planting: a hand setting a seedling into the hole it has just made.
    A.planting = `${GROUND}
        <path d="M22 40c-2-3-2-7 0-10" fill="none" stroke="#6f9b4c" stroke-width="2.2" stroke-linecap="round"/>
        <g class="fs-bob" style="transform-origin:22px 26px">
            <path d="M22 30c-5-1-7-5-6-8 4-1 7 2 6 8z" fill="#6aa84f"/>
            <path d="M22 30c5-1 7-5 6-8-4-1-7 2-6 8z" fill="#8fc96a"/>
        </g>
        <path class="fs-sway" d="M32 24c3 0 5 2 5 5s-3 4-5 3" fill="none" stroke="#c98c4a" stroke-width="2.4" stroke-linecap="round" style="transform-origin:37px 27px"/>
        <path d="M14 40c1-3 3-4 5-4" fill="none" stroke="#8a5a2b" stroke-width="2.2" stroke-linecap="round" opacity=".7"/>`;

    // Foliar spray: the knapsack and the fan off the nozzle.
    A.sprayer = `${GROUND}
        <rect x="10" y="18" width="11" height="16" rx="3" fill="#4a7c2a" opacity=".85" stroke="#3d6823" stroke-width="1.6"/>
        <path d="M21 24h6l9-6" fill="none" stroke="#5b6779" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
        <circle cx="36" cy="18" r="2.2" fill="#8b98ab"/>
        <circle class="fs-mist" cx="37" cy="19" r="1.5" fill="#7dd3fc"/>
        <circle class="fs-mist2" cx="37" cy="21" r="1.2" fill="#7dd3fc"/>
        <circle class="fs-mist3" cx="37" cy="17" r="1.2" fill="#a5f3fc"/>
        <path class="fs-sway" d="M16 40c0-4 2-6 4-7" fill="none" stroke="#6aa84f" stroke-width="2.2" stroke-linecap="round" style="transform-origin:16px 40px"/>`;

    // Granular fertiliser: a hand casting, the grains arcing out.
    A.granular = `${GROUND}
        <path d="M12 22c4-3 8-3 11 0l-2 9H14z" fill="#d9a441" opacity=".8" stroke="#a97c22" stroke-width="1.6" stroke-linejoin="round"/>
        <g class="fs-hunt">
            <circle cx="27" cy="24" r="1.5" fill="#e0b23f"/>
            <circle cx="32" cy="27" r="1.3" fill="#c99a2f"/>
            <circle cx="36" cy="31" r="1.5" fill="#e0b23f"/>
        </g>
        <circle class="fs-fall" cx="30" cy="30" r="1.2" fill="#d9a441"/>
        <circle class="fs-fall2" cx="35" cy="30" r="1.2" fill="#c99a2f"/>`;

    // Herbicide: the weed going over under the boom.
    A.herbicide = `${GROUND}
        <path d="M10 20h24" stroke="#7c3aed" stroke-width="2.4" stroke-linecap="round"/>
        <path d="M14 20v4M22 20v4M30 20v4" stroke="#7c3aed" stroke-width="2" stroke-linecap="round" opacity=".7"/>
        <circle class="fs-mist" cx="14" cy="25" r="1.4" fill="#c4b5fd"/>
        <circle class="fs-mist2" cx="22" cy="25" r="1.4" fill="#c4b5fd"/>
        <circle class="fs-mist3" cx="30" cy="25" r="1.4" fill="#ddd6fe"/>
        <path class="fs-sway3" d="M20 40c-1-6 2-8 5-9" fill="none" stroke="#9a8f52" stroke-width="2.4" stroke-linecap="round" style="transform-origin:20px 40px"/>
        <path class="fs-sway3" d="M28 40c1-5-1-7-4-8" fill="none" stroke="#b0a45e" stroke-width="2.2" stroke-linecap="round" style="transform-origin:28px 40px"/>`;

    // Pesticide: the cone of spray, and what it is aimed at.
    A.pesticide = `${GROUND}
        <path class="fs-sway" d="M24 40V26" stroke="#6f9b4c" stroke-width="2.4" stroke-linecap="round" style="transform-origin:24px 40px"/>
        <path class="fs-sway" d="M24 30c-6-1-8-5-7-8 4-1 8 2 7 8z" fill="#6aa84f" style="transform-origin:24px 32px"/>
        <g class="fs-bob">
            <ellipse cx="32" cy="24" rx="3.4" ry="2.6" fill="#e11d48" opacity=".9"/>
            <circle cx="34.6" cy="22.4" r="1" fill="#3f1020"/>
            <path d="M30 22l-2-2M34 21.6l2-2.4" stroke="#3f1020" stroke-width="1.2" stroke-linecap="round"/>
        </g>
        <circle class="fs-mist" cx="12" cy="20" r="1.5" fill="#fda4af"/>
        <circle class="fs-mist2" cx="12" cy="23" r="1.3" fill="#fda4af"/>`;

    // Fungicide: the leaf, its spots, and the film going over the top.
    A.fungicide = `${GROUND}
        <path class="fs-sway" d="M24 40V22" stroke="#6f9b4c" stroke-width="2.2" stroke-linecap="round" style="transform-origin:24px 40px"/>
        <g class="fs-sway" style="transform-origin:24px 24px">
            <path d="M24 26c-8-1-11-7-9-12 6-1 11 4 9 12z" fill="#5f9c46"/>
            <circle cx="18" cy="19" r="1.6" fill="#a16207" opacity=".8"/>
            <circle cx="21.5" cy="23" r="1.2" fill="#a16207" opacity=".65"/>
        </g>
        <circle class="fs-mist" cx="32" cy="18" r="1.6" fill="#7dd3fc"/>
        <circle class="fs-mist2" cx="33" cy="22" r="1.3" fill="#bae6fd"/>
        <circle class="fs-mist3" cx="31" cy="15" r="1.3" fill="#7dd3fc"/>`;

    // Microbial: a live culture, rising in the flask.
    A.microbe = `${GROUND}
        <path d="M20 14h8v6l6 14a3 3 0 01-2.8 4H16.8A3 3 0 0114 34l6-14z"
              fill="#7c3aed" opacity=".2" stroke="#6d28d9" stroke-width="1.8" stroke-linejoin="round"/>
        <circle class="fs-rise" cx="21" cy="32" r="1.6" fill="#a78bfa"/>
        <circle class="fs-rise2" cx="25" cy="33" r="1.2" fill="#c4b5fd"/>
        <circle class="fs-rise3" cx="28" cy="32" r="1.5" fill="#a78bfa"/>
        <path d="M17 34h14" stroke="#6d28d9" stroke-width="1.6" opacity=".5"/>`;

    // Irrigation: the gate open and the water running in.
    A.water = `<path d="M4 38h40" stroke="#a3865a" stroke-width="2.4" stroke-linecap="round" opacity=".45"/>
        <rect x="10" y="14" width="5" height="20" rx="1.5" fill="#8b98ab"/>
        <rect x="33" y="14" width="5" height="20" rx="1.5" fill="#8b98ab"/>
        <path class="fs-flow" d="M15 22h18M15 27h18M15 32h18" stroke="#38bdf8" stroke-width="2.6" stroke-linecap="round"/>
        <path class="fs-bob" d="M6 38c3-2 6 2 9 0s6 2 9 0 6 2 9 0 6 2 9 0" fill="none" stroke="#0ea5e9" stroke-width="2" stroke-linecap="round" opacity=".7"/>`;

    // Harvest: the sickle and what is left standing.
    A.harvest = `${GROUND}
        <g class="fs-sway"><path d="M15 40V24M19 40V26M23 40V25" stroke="#d9a441" stroke-width="2.2" stroke-linecap="round" style="transform-origin:19px 40px"/></g>
        <g class="fs-cut" style="transform-origin:36px 34px">
            <path d="M36 34c-6-2-10-6-9-11 6 0 10 5 9 11z" fill="none" stroke="#94a3b8" stroke-width="2.6" stroke-linecap="round"/>
            <path d="M36 34l3 5" stroke="#8a5a2b" stroke-width="3" stroke-linecap="round"/>
        </g>
        <circle class="fs-pulse" cx="29" cy="20" r="1.6" fill="#fde047"/>`;

    // Monitoring: the glass moving over the leaf, the way a walk works.
    A.scout = `${GROUND}
        <path class="fs-sway" d="M18 40c-1-8 2-12 6-14" fill="none" stroke="#6f9b4c" stroke-width="2.4" stroke-linecap="round" style="transform-origin:18px 40px"/>
        <path class="fs-sway" d="M22 30c-6-2-7-7-5-10 5 0 7 5 5 10z" fill="#6aa84f" style="transform-origin:22px 32px"/>
        <g class="fs-hunt">
            <circle cx="30" cy="22" r="7" fill="rgb(125 211 252 / .25)" stroke="#5b6779" stroke-width="2.2"/>
            <path d="M35 27l5 6" stroke="#5b6779" stroke-width="3" stroke-linecap="round"/>
        </g>`;

    // Equipment preparation: the box and the spanner over it.
    A.toolbox = `${GROUND}
        <rect x="10" y="24" width="28" height="14" rx="2.4" fill="#64748b" opacity=".28" stroke="#475569" stroke-width="1.8"/>
        <path d="M18 24v-3a2 2 0 012-2h8a2 2 0 012 2v3" fill="none" stroke="#475569" stroke-width="2" stroke-linecap="round"/>
        <path d="M10 30h28" stroke="#475569" stroke-width="1.6" opacity=".6"/>
        <g class="fs-spin" style="transform-origin:24px 31px">
            <path d="M24 26.5a4.5 4.5 0 100 9 4.5 4.5 0 000-9zm0 3a1.5 1.5 0 110 3 1.5 1.5 0 010-3z" fill="#94a3b8"/>
        </g>`;

    // Worker checklist: the crew, walking.
    A.crew = `${GROUND}
        <g class="fs-walk"><circle cx="15" cy="24" r="3.4" fill="#d9a441"/><path d="M15 28c-3 0-5 2-5 5v5h10v-5c0-3-2-5-5-5z" fill="#4a7c2a"/></g>
        <g class="fs-walk2"><circle cx="24" cy="22" r="3.4" fill="#c98c4a"/><path d="M24 26c-3 0-5 2-5 5v7h10v-7c0-3-2-5-5-5z" fill="#6aa84f"/></g>
        <g class="fs-walk3"><circle cx="33" cy="24" r="3.4" fill="#b07a3c"/><path d="M33 28c-3 0-5 2-5 5v5h10v-5c0-3-2-5-5-5z" fill="#3d6823"/></g>`;

    // Reminder checklist: the board and the ticks landing on it.
    A.checklist = `<rect x="12" y="8" width="24" height="32" rx="3" fill="#e2e8f0" opacity=".35" stroke="#64748b" stroke-width="1.8"/>
        <rect x="19" y="5" width="10" height="6" rx="2" fill="#94a3b8"/>
        <path class="fs-tick" d="M17 19l3 3 6-6" fill="none" stroke="#4a7c2a" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/>
        <path class="fs-tick2" d="M17 29l3 3 6-6" fill="none" stroke="#4a7c2a" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/>
        <path d="M29 20h5M29 30h5" stroke="#94a3b8" stroke-width="2" stroke-linecap="round" opacity=".7"/>`;

    // Service: somebody arriving to do a job for you.
    A.service = `${GROUND}
        <path d="M9 34V22h14l5 6h9v6z" fill="#64748b" opacity=".3" stroke="#475569" stroke-width="1.8" stroke-linejoin="round"/>
        <rect x="12" y="24" width="7" height="5" rx="1" fill="#7dd3fc" opacity=".8"/>
        <g class="fs-spin" style="transform-origin:16px 36px"><circle cx="16" cy="36" r="3.4" fill="none" stroke="#334155" stroke-width="2.4"/><path d="M16 33v6" stroke="#334155" stroke-width="1.4"/></g>
        <g class="fs-spin" style="transform-origin:32px 36px"><circle cx="32" cy="36" r="3.4" fill="none" stroke="#334155" stroke-width="2.4"/><path d="M32 33v6" stroke="#334155" stroke-width="1.4"/></g>`;

    // A quiet day: the calendar, and one leaf on it that is doing fine.
    A.quiet = `<rect x="8" y="11" width="32" height="29" rx="4" fill="rgb(125 211 252 / .18)" stroke="#5b6779" stroke-width="2"/>
        <path d="M16 7v8M32 7v8M8 20h32" stroke="#5b6779" stroke-width="2" stroke-linecap="round"/>
        <g class="fs-sway" style="transform-origin:24px 36px">
            <path d="M24 36V29" stroke="#6f9b4c" stroke-width="2.2" stroke-linecap="round"/>
            <path d="M24 31c-5-1-6-4-5-7 4 0 6 3 5 7z" fill="#6aa84f"/>
            <path d="M24 31c5-1 6-4 5-7-4 0-6 3-5 7z" fill="#8fc96a"/>
        </g>`;

    // Something on the board, kind unknown.
    A.mixed = `<rect x="8" y="11" width="32" height="29" rx="4" fill="rgb(107 159 61 / .16)" stroke="#5b6779" stroke-width="2"/>
        <path d="M16 7v8M32 7v8M8 20h32" stroke="#5b6779" stroke-width="2" stroke-linecap="round"/>
        <circle class="fs-pulse" cx="17" cy="27" r="2.4" fill="#6aa84f"/>
        <circle class="fs-bob" cx="24" cy="33" r="2.4" fill="#d9a441"/>
        <circle class="fs-bob2" cx="31" cy="27" r="2.4" fill="#38bdf8"/>`;

    /* ------------------------------------------------------------------ *
     * THE CROP
     *
     * Generated, not drawn. A family names the silhouette, a band names how
     * far through the season it is, and the two together decide what is on
     * the page: how tall, how many leaves, whether there are flowers on it,
     * whether the thing you harvest is above the line or under it.
     * ------------------------------------------------------------------ */

    // The recipe for each silhouette. The PHP model holds the same table —
    // it decides which crop is which family; this decides how to draw one.
    const FAM = {
        grain:    { leaf: 'blade', stem: 'tuft',  fruit: 'panicle', below: false, leafHue: '#6aa84f', stemHue: '#7fae55', fruitHue: '#d9a441' },
        corn:     { leaf: 'blade', stem: 'stalk', fruit: 'cob',     below: false, leafHue: '#5f9c46', stemHue: '#6f9b4c', fruitHue: '#e8b93f' },
        cane:     { leaf: 'blade', stem: 'cane',  fruit: 'none',    below: false, leafHue: '#67a24b', stemHue: '#a3823f', fruitHue: '#cfa74a' },
        legume:   { leaf: 'trifoliate', stem: 'bush', fruit: 'pod', below: false, leafHue: '#5aa04a', stemHue: '#6f9b4c', fruitHue: '#8cc152' },
        root:     { leaf: 'broad', stem: 'tuft',  fruit: 'root',    below: true,  leafHue: '#4f9a45', stemHue: '#6f9b4c', fruitHue: '#c9752f' },
        leafy:    { leaf: 'broad', stem: 'none',  fruit: 'head',    below: false, leafHue: '#63ab4c', stemHue: '#7cb45c', fruitHue: '#8fc96a' },
        vine:     { leaf: 'lobed', stem: 'vine',  fruit: 'hanging', below: false, leafHue: '#569b45', stemHue: '#6f9b4c', fruitHue: '#4f9e3f' },
        fruitveg: { leaf: 'broad', stem: 'bush',  fruit: 'berry',   below: false, leafHue: '#4f9a45', stemHue: '#6f9b4c', fruitHue: '#d4483f' },
        bulb:     { leaf: 'needle', stem: 'none', fruit: 'bulb',    below: true,  leafHue: '#5fa64f', stemHue: '#7cb45c', fruitHue: '#d8c39a' },
        banana:   { leaf: 'frond', stem: 'trunk', fruit: 'bunch',   below: false, leafHue: '#4f9a45', stemHue: '#8a9a5b', fruitHue: '#e0c04a' },
        palm:     { leaf: 'frond', stem: 'trunk', fruit: 'nut',     below: false, leafHue: '#4c9b52', stemHue: '#9a7b4f', fruitHue: '#8d6a3f' },
        tree:     { leaf: 'broad', stem: 'trunk', fruit: 'globe',   below: false, leafHue: '#417f3c', stemHue: '#8a6a45', fruitHue: '#e0912f' },
        shrub:    { leaf: 'broad', stem: 'bush',  fruit: 'berry',   below: false, leafHue: '#3f8c46', stemHue: '#6e8a4c', fruitHue: '#b6482f' },
        spiky:    { leaf: 'sword', stem: 'none',  fruit: 'globe',   below: false, leafHue: '#5f9a4a', stemHue: '#7cb45c', fruitHue: '#e0a635' },
        mixed:    { leaf: 'broad', stem: 'tuft',  fruit: 'berry',   below: false, leafHue: '#5a9f4b', stemHue: '#7cb45c', fruitHue: '#c9723a' },
    };

    // How tall the plant stands in each band, as a share of the box. A seed
    // is not a small plant, it is a mound with nothing above it, so band 0
    // barely rises at all.
    const TALL = [0.06, 0.26, 0.60, 0.74, 0.84, 0.92];
    const GY = 40;                     // the ground line, in the 48-box

    const n = (v) => Math.round(v * 100) / 100;

    /** A grass blade curving away from a point. */
    function blade(x, y, len, dir, hue, cls) {
        const tipX = n(x + dir * len * 0.72), tipY = n(y - len);
        const cx = n(x + dir * len * 0.15), cy = n(y - len * 0.72);
        return `<path class="${cls}" d="M${n(x)} ${n(y)}Q${cx} ${cy} ${tipX} ${tipY}"
            fill="none" stroke="${hue}" stroke-width="2.1" stroke-linecap="round"
            style="transform-origin:${n(x)}px ${n(y)}px"/>`;
    }

    /** A broad leaf: a teardrop hanging off a point. */
    function broad(x, y, len, dir, hue, cls) {
        const ex = n(x + dir * len), ey = n(y - len * 0.42);
        return `<path class="${cls}" d="M${n(x)} ${n(y)}C${n(x + dir * len * 0.35)} ${n(y - len * 0.85)} ${n(ex)} ${n(ey - len * 0.5)} ${ex} ${ey}
            C${n(x + dir * len * 0.6)} ${n(ey + len * 0.3)} ${n(x + dir * len * 0.2)} ${n(y + 1)} ${n(x)} ${n(y)}z"
            fill="${hue}" style="transform-origin:${n(x)}px ${n(y)}px"/>`;
    }

    /** Three little leaves on one stalk — what a bean wears. */
    function trifoliate(x, y, len, dir, hue, cls) {
        const s = len * 0.42;
        return `<g class="${cls}" style="transform-origin:${n(x)}px ${n(y)}px">
            <path d="M${n(x)} ${n(y)}l${n(dir * len * 0.6)} ${n(-len * 0.5)}" stroke="${hue}" stroke-width="1.6" stroke-linecap="round" fill="none"/>
            <ellipse cx="${n(x + dir * len * 0.62)}" cy="${n(y - len * 0.62)}" rx="${n(s * 0.6)}" ry="${n(s * 0.42)}" fill="${hue}" transform="rotate(${dir * -28} ${n(x + dir * len * 0.62)} ${n(y - len * 0.62)})"/>
            <ellipse cx="${n(x + dir * len * 0.9)}" cy="${n(y - len * 0.36)}" rx="${n(s * 0.55)}" ry="${n(s * 0.4)}" fill="${hue}" transform="rotate(${dir * 12} ${n(x + dir * len * 0.9)} ${n(y - len * 0.36)})"/>
            <ellipse cx="${n(x + dir * len * 0.3)}" cy="${n(y - len * 0.78)}" rx="${n(s * 0.5)}" ry="${n(s * 0.36)}" fill="${hue}" opacity=".88" transform="rotate(${dir * -52} ${n(x + dir * len * 0.3)} ${n(y - len * 0.78)})"/>
        </g>`;
    }

    /** A cut-edged leaf — squash, melon, cucumber. */
    function lobed(x, y, len, dir, hue, cls) {
        const cx = n(x + dir * len * 0.7), cy = n(y - len * 0.35), r = n(len * 0.42);
        return `<path class="${cls}" d="M${cx} ${n(cy - r)}a${r} ${r} 0 1 0 .1 0z M${n(x)} ${n(y)}L${cx} ${cy}"
            fill="${hue}" stroke="${hue}" stroke-width="1.4" stroke-linecap="round"
            style="transform-origin:${n(x)}px ${n(y)}px"/>`;
    }

    /** A palm or banana frond: a spine with a fringe. */
    function frond(x, y, len, ang, hue, cls) {
        const rad = ang * Math.PI / 180;
        const ex = n(x + Math.cos(rad) * len), ey = n(y + Math.sin(rad) * len);
        const mx = n(x + Math.cos(rad) * len * 0.5 - Math.sin(rad) * len * 0.22);
        const my = n(y + Math.sin(rad) * len * 0.5 + Math.cos(rad) * len * 0.22);
        return `<path class="${cls}" d="M${n(x)} ${n(y)}Q${mx} ${my} ${ex} ${ey}"
            fill="none" stroke="${hue}" stroke-width="${n(len * 0.28)}" stroke-linecap="round" opacity=".92"
            style="transform-origin:${n(x)}px ${n(y)}px"/>`;
    }

    /** An onion's tube, or a pineapple's sword. */
    function spear(x, y, len, dir, hue, cls, w) {
        return `<path class="${cls}" d="M${n(x)} ${n(y)}Q${n(x + dir * len * 0.2)} ${n(y - len * 0.6)} ${n(x + dir * len * 0.55)} ${n(y - len)}"
            fill="none" stroke="${hue}" stroke-width="${w || 2.4}" stroke-linecap="round"
            style="transform-origin:${n(x)}px ${n(y)}px"/>`;
    }

    const LEAFERS = { blade, broad, trifoliate, lobed, sword: spear, needle: spear };

    /** What the plant is carrying, if it is carrying anything yet. */
    /** Two shades of a fruit colour: filling, then ready. */
    function deepen(hex, amt) {
        const m = /^#([0-9a-f]{6})$/i.exec(hex);
        if (!m) return hex;
        const v = parseInt(m[1], 16);
        const ch = [(v >> 16) & 255, (v >> 8) & 255, v & 255]
            .map((c) => Math.max(0, Math.min(255, Math.round(c * amt))));
        return '#' + ch.map((c) => c.toString(16).padStart(2, '0')).join('');
    }

    function fruiting(f, band, topY, cfg) {
        // Nothing before flowering; a blossom at band 3, a fruit that is
        // filling at 4, and the same fruit bigger and deeper at 5. Filling
        // and ready are the one distinction on this strip that anybody acts
        // on, so they cannot be the same drawing.
        if (band < 3) return '';
        const ripe = band >= 4;
        const done = band >= 5;
        const size = done ? 1.24 : 1;
        const hue = done ? deepen(cfg.fruitHue, 0.86) : (ripe ? cfg.fruitHue : '#f7e08a');
        const g = (s) => `<g class="fs-bob">${s}</g>`;

        switch (f) {
            case 'panicle':
                return g(`<path d="M24 ${n(topY)}q${done ? 6 : 4} ${done ? 3 : 2} ${done ? 6.5 : 5} ${done ? 9 : 7}" fill="none" stroke="${hue}" stroke-width="${n(2.4 * size)}" stroke-linecap="round"/>
                    <path d="M25 ${n(topY + 2)}l3 1M26 ${n(topY + 5)}l3 1M27 ${n(topY + 8)}l2.5 1"
                          stroke="${hue}" stroke-width="1.6" stroke-linecap="round"/>`);
            case 'cob':
                return g(`<ellipse cx="29" cy="${n(topY + 10)}" rx="${n(3.1 * size)}" ry="${n(5.2 * size)}" fill="${hue}" transform="rotate(18 29 ${n(topY + 10)})"/>
                    <path d="M29 ${n(topY + 5)}q3-3 5-2" fill="none" stroke="#e07a3f" stroke-width="1.8" stroke-linecap="round"/>`);
            case 'pod':
                return g(`<path d="M20 ${n(topY + 8)}q-2 5 0 8M25 ${n(topY + 9)}q2 5 0 8" fill="none"
                          stroke="${hue}" stroke-width="${n(3.2 * size)}" stroke-linecap="round"/>`);
            case 'berry':
                return g(`<circle cx="19" cy="${n(topY + 9)}" r="${n((ripe ? 2.8 : 2) * size)}" fill="${hue}"/>
                    <circle cx="29" cy="${n(topY + 12)}" r="${n((ripe ? 2.6 : 1.9) * size)}" fill="${hue}" opacity=".9"/>`);
            case 'globe':
                return g(`<circle cx="19" cy="${n(topY + 9)}" r="${n((ripe ? 3.4 : 2.4) * size)}" fill="${hue}"/>
                    <circle cx="30" cy="${n(topY + 6)}" r="${n((ripe ? 3 : 2.1) * size)}" fill="${hue}" opacity=".92"/>`);
            case 'hanging':
                return g(`<ellipse cx="30" cy="${n(topY + 13)}" rx="${n((ripe ? 3.2 : 2.2) * size)}" ry="${n((ripe ? 5.4 : 3.6) * size)}" fill="${hue}"/>
                    <path d="M30 ${n(topY + 8)}v${ripe ? 1.8 : 1.4}" stroke="#6f9b4c" stroke-width="1.6" stroke-linecap="round"/>`);
            case 'bunch':
                return g(`<path d="M27 ${n(topY + 8)}q5 1 5 3M27 ${n(topY + 11)}q5.5 1 5.5 3M27.5 ${n(topY + 14)}q5 1 4.5 3"
                          fill="none" stroke="${hue}" stroke-width="2.6" stroke-linecap="round"/>`);
            case 'nut':
                return g(`<circle cx="20" cy="${n(topY + 4)}" r="${n(2.6 * size)}" fill="${hue}"/>
                    <circle cx="27" cy="${n(topY + 5)}" r="${n(2.4 * size)}" fill="${hue}" opacity=".9"/>`);
            case 'head':
                // Down in the middle of the rosette, which is where a
                // cabbage's heart actually is.
                return g(`<circle cx="24" cy="${n(GY - 6 - (ripe ? 2 : 0))}" r="${n((ripe ? 5.2 : 3.9) * size)}" fill="${hue}" opacity=".95"/>`);
            default:
                return '';
        }
    }

    /** The part under the line: a root swelling, a bulb filling. */
    function underground(kind, band, cfg) {
        const grow = [0.2, 0.35, 0.55, 0.72, 0.9, 1][band];
        const c = band >= 4 ? cfg.fruitHue : '#c9a883';
        if (kind === 'root') {
            const rx = n(3 + 4.4 * grow), ry = n(2.4 + 3.4 * grow);
            return `<g class="fs-bob2">
                <ellipse cx="24" cy="${n(GY + ry + 0.5)}" rx="${rx}" ry="${ry}" fill="${c}"/>
                <path d="M${n(24 - rx)} ${n(GY + ry)}q-3 2-4 4M${n(24 + rx)} ${n(GY + ry)}q3 2 4 4"
                      fill="none" stroke="${c}" stroke-width="1.4" stroke-linecap="round" opacity=".7"/>
            </g>`;
        }
        const r = n(2.4 + 3.6 * grow);
        return `<g class="fs-bob2">
            <ellipse cx="24" cy="${n(GY + r * 0.8)}" rx="${r}" ry="${n(r * 0.92)}" fill="${c}"/>
            <path d="M24 ${n(GY + r * 1.7)}v3" stroke="${c}" stroke-width="1.4" stroke-linecap="round" opacity=".65"/>
        </g>`;
    }

    /** Which weather is over the field at this point of the season. */
    function overhead(band) {
        if (band <= 1) {
            // Early: rain, because that is what a young crop is waiting for.
            return `<circle class="fs-fall" cx="12" cy="12" r="1.4" fill="#7dd3fc"/>
                <circle class="fs-fall2" cx="17" cy="10" r="1.2" fill="#7dd3fc"/>`;
        }
        if (band >= 4) {
            /* Late: the sun that ripens and the sun that has to be worked
               under. Small, and pushed into the corner — it belongs behind
               the crop, not on top of it, and a tree's canopy reaches
               further up this box than anything else does. At harvest it
               brings the glint that says the difference between filling and
               ready. */
            const ripe = band >= 5;
            return `<circle cx="40" cy="9" r="3.2" fill="#fbbf24" opacity=".85"/>
                <g class="fs-spin" style="transform-origin:40px 9px">
                    <path d="M40 3.4v1.8M40 12.8v1.8M34.4 9h1.8M43.8 9h1.8M36.1 5.1l1.3 1.3M42.6 11.6l1.3 1.3M43.9 5.1l-1.3 1.3M37.4 11.6l-1.3 1.3"
                          stroke="#f59e0b" stroke-width="1.5" stroke-linecap="round" opacity=".8"/>
                </g>` + (ripe
                    ? `<path class="fs-pulse" d="M9 9l1 3 3 1-3 1-1 3-1-3-3-1 3-1z" fill="#fde047" style="transform-origin:9px 13px"/>`
                    : '');
        }
        if (band === 3) {
            /* Flowering: a blossom, not a bee. A bee at thirty-four pixels
               is a yellow-and-black smudge; a five-petalled flower is
               unmistakably a flower at any size on any screen. */
            return `<g class="fs-pulse" style="transform-origin:37px 12px">
                <g fill="#f9a8d4">
                    <ellipse cx="37" cy="8.6" rx="1.7" ry="2.6"/>
                    <ellipse cx="40.2" cy="11" rx="2.6" ry="1.7"/>
                    <ellipse cx="38.8" cy="14.8" rx="1.9" ry="2.5" transform="rotate(35 38.8 14.8)"/>
                    <ellipse cx="35.2" cy="14.8" rx="1.9" ry="2.5" transform="rotate(-35 35.2 14.8)"/>
                    <ellipse cx="33.8" cy="11" rx="2.6" ry="1.7"/>
                </g>
                <circle cx="37" cy="12" r="1.7" fill="#fbbf24"/>
            </g>`;
        }
        return '';
    }

    /**
     * One crop, at one point of its season.
     *
     * @param family one of FAM
     * @param band   0..5, seed to harvest
     */
    function plant(family, band) {
        const cfg = FAM[family] || FAM.mixed;
        band = Math.max(0, Math.min(5, band | 0));
        const h = (GY - 4) * TALL[band];
        const topY = n(GY - h);
        const leafer = LEAFERS[cfg.leaf] || broad;
        let s = '';

        // The ground, and the mound a seed sits in before there is anything
        // to see above it.
        s += `<path d="M5 ${GY}h38" stroke="#a3865a" stroke-width="2.4" stroke-linecap="round" opacity=".55"/>`;
        if (band === 0) {
            s += `<path d="M17 ${GY}q7-5 14 0z" fill="#a3865a" opacity=".45"/>`;
            s += `<ellipse cx="24" cy="${GY - 2}" rx="2.4" ry="1.8" fill="${cfg.fruitHue}" class="fs-pulse" style="transform-origin:24px ${GY - 2}px"/>`;
        }

        if (cfg.below) s += underground(cfg.fruit, band, cfg);

        // The stem, whatever kind it is.
        if (band > 0) {
            switch (cfg.stem) {
                case 'stalk':
                    s += `<path class="fs-sway" d="M24 ${GY}V${topY}" stroke="${cfg.stemHue}" stroke-width="3" stroke-linecap="round" style="transform-origin:24px ${GY}px"/>`;
                    if (band >= 3) s += `<path class="fs-sway" d="M24 ${topY}q1-4 4-5" fill="none" stroke="${cfg.fruitHue}" stroke-width="2" stroke-linecap="round" style="transform-origin:24px ${topY}px"/>`;
                    break;
                case 'cane':
                    [18, 24, 30].forEach((x, i) => {
                        const hh = h * (i === 1 ? 1 : 0.82);
                        s += `<path class="fs-sway${i ? (i === 1 ? '' : '2') : '2'}" d="M${x} ${GY}V${n(GY - hh)}" stroke="${cfg.stemHue}" stroke-width="2.8" stroke-linecap="round" style="transform-origin:${x}px ${GY}px"/>`;
                        for (let k = 1; k < 4; k++) {
                            const y = n(GY - hh * k / 4);
                            s += `<path d="M${x - 1.6} ${y}h3.2" stroke="#7a5f2c" stroke-width="1.3" opacity=".65"/>`;
                        }
                    });
                    break;
                case 'trunk':
                    // Wider at the foot than at the crown, which is the one
                    // thing that stops a trunk reading as a drawn line.
                    s += `<path d="M${n(24 - 1.4 - h * 0.045)} ${GY}L${n(24 - 1.1)} ${topY}h2.2L${n(24 + 1.4 + h * 0.045)} ${GY}z"
                          fill="${cfg.stemHue}"/>`;
                    break;
                case 'vine':
                    s += `<path d="M13 ${GY}V${n(GY - h - 2)}M35 ${GY}V${n(GY - h - 2)}" stroke="#a3823f" stroke-width="2.2" stroke-linecap="round" opacity=".8"/>`;
                    s += `<path d="M13 ${n(GY - h - 2)}h22" stroke="#a3823f" stroke-width="2" stroke-linecap="round" opacity=".8"/>`;
                    s += `<path class="fs-sway" d="M17 ${GY}q2-${n(h * 0.5)} 6-${n(h * 0.7)}t8-${n(h * 0.3)}" fill="none" stroke="${cfg.stemHue}" stroke-width="2.2" stroke-linecap="round" style="transform-origin:17px ${GY}px"/>`;
                    break;
                case 'bush':
                    s += `<path class="fs-sway" d="M24 ${GY}V${n(topY + h * 0.15)}" stroke="${cfg.stemHue}" stroke-width="2.4" stroke-linecap="round" style="transform-origin:24px ${GY}px"/>`;
                    s += `<path class="fs-sway" d="M24 ${n(GY - h * 0.45)}l-4 -${n(h * 0.22)}M24 ${n(GY - h * 0.6)}l4 -${n(h * 0.2)}" stroke="${cfg.stemHue}" stroke-width="1.8" stroke-linecap="round" style="transform-origin:24px ${GY}px"/>`;
                    break;
                case 'tuft':
                    s += `<path class="fs-sway" d="M24 ${GY}V${n(GY - h * 0.4)}" stroke="${cfg.stemHue}" stroke-width="2.2" stroke-linecap="round" style="transform-origin:24px ${GY}px"/>`;
                    break;
            }
        }

        // The leaves. How many, and how big, is the band's doing.
        if (band > 0) {
            const many = [0, 2, 4, 5, 5, 4][band];
            if (cfg.leaf === 'frond') {
                // A crown: fronds fanning out from the top of the trunk.
                const angs = [200, 225, 250, 290, 315, 340];
                angs.slice(0, Math.max(3, many + 1)).forEach((a, i) => {
                    s += frond(24, topY, h * (0.42 + (i % 2) * 0.1), a, cfg.leafHue, i % 2 ? 'fs-sway2' : 'fs-sway');
                });
            } else if (cfg.stem === 'trunk') {
                // A canopy: a soft cloud of leaf with the branches inside it.
                // Was 6 + h*.28, which made a seedling's canopy nearly as
                // wide as a mature one's — the constant did most of the work
                // and the growing did almost none.
                const r = n(2.5 + h * 0.36);
                s += `<g class="fs-sway" style="transform-origin:24px ${topY}px">
                    <circle cx="${n(24 - r * 0.55)}" cy="${n(topY + 2)}" r="${n(r * 0.72)}" fill="${cfg.leafHue}" opacity=".92"/>
                    <circle cx="${n(24 + r * 0.55)}" cy="${n(topY + 1)}" r="${n(r * 0.68)}" fill="${cfg.leafHue}" opacity=".85"/>
                    <circle cx="24" cy="${n(topY - r * 0.32)}" r="${n(r * 0.78)}" fill="${cfg.leafHue}"/>
                </g>`;
            } else if (cfg.stem === 'none') {
                // A rosette straight out of the ground: leafy, onion, pineapple.
                const spread = cfg.leaf === 'needle' ? 3.4 : (cfg.leaf === 'sword' ? 5 : 6);
                /* A blade goes UP its own length; a broad leaf goes SIDEWAYS
                   its own length. Given the same number they are not the
                   same size at all, and a cabbage was being drawn a third
                   wider than the box it sits in. */
                const wide = cfg.leaf === 'broad' || cfg.leaf === 'lobed';
                for (let i = 0; i < many + 1; i++) {
                    const dir = i % 2 ? 1 : -1;
                    const step = Math.floor(i / 2);
                    const len = wide
                        ? Math.min(15, h * 0.52) * (1 - step * 0.14)
                        : h * (1 - step * 0.16);
                    const x = 24 + dir * step * spread * 0.6;
                    const cls = i % 3 === 0 ? 'fs-sway' : (i % 3 === 1 ? 'fs-sway2' : 'fs-sway3');
                    s += leafer(x, GY, Math.max(3, len), dir, cfg.leafHue, cls,
                        cfg.leaf === 'needle' ? 2.6 : 3.2);
                }
            } else {
                // Everything else hangs its leaves off the stem it has.
                for (let i = 0; i < many; i++) {
                    const dir = i % 2 ? 1 : -1;
                    const up = 0.22 + (i / Math.max(1, many)) * 0.6;
                    const len = h * (0.5 - up * 0.22);
                    const y = n(GY - h * up);
                    const cls = i % 3 === 0 ? 'fs-sway' : (i % 3 === 1 ? 'fs-sway2' : 'fs-sway3');
                    s += leafer(cfg.stem === 'vine' ? 24 + dir * 3 : 24, y, Math.max(3.2, len), dir, cfg.leafHue, cls, 2.6);
                }
            }
        }

        if (! cfg.below || cfg.fruit === 'head') s += fruiting(cfg.fruit, band, topY, cfg);
        s += overhead(band);

        return s;
    }

    /* ------------------------------------------------------------------ *
     * PAINTING
     * ------------------------------------------------------------------ */

    const wrap = (inner, size, title) =>
        `<svg class="fs-scene" width="${size}" height="${size}" viewBox="0 0 48 48"
              role="img" aria-label="${String(title || '').replace(/"/g, '&quot;')}">${inner}</svg>`;

    /** One kind of working day. */
    window.fsAct = function (key, size, title) {
        const row = (window.FS_ACTS || {})[key] || {};
        const inner = A[row.scene] || A[key] || A.mixed;
        return wrap(inner, size || 36, title || row.label || 'Today');
    };

    /** One crop at one point of its season. */
    window.fsCrop = function (family, band, size, title) {
        return wrap(plant(family, band), size || 36, title || window.fsCropName(family, band));
    };

    /** What the table calls this band of this family. */
    window.fsCropName = function (family, band) {
        const row = (window.FS_CROPS || {})[family + ':' + band] || (window.FS_CROPS || {})['mixed:' + band];
        return row ? row.label : 'Growing';
    };
    window.fsCropSays = function (family, band) {
        const row = (window.FS_CROPS || {})[family + ':' + band] || (window.FS_CROPS || {})['mixed:' + band];
        return row ? row.blurb : '';
    };
    window.fsCropHue = function (family, band) {
        const row = (window.FS_CROPS || {})[family + ':' + band];
        return 'fs-hue-' + (row ? row.hue : 'leaf');
    };
    window.fsActSays = function (key) {
        const row = (window.FS_ACTS || {})[key];
        return row ? row.blurb : '';
    };
    window.fsActName = function (key) {
        const row = (window.FS_ACTS || {})[key];
        return row ? row.label : 'On the board';
    };
    window.fsActHue = function (key) {
        const row = (window.FS_ACTS || {})[key];
        return 'fs-hue-' + (row ? row.hue : 'leaf');
    };

    /**
     * Fill every empty slot under a root.
     *
     * Idempotent by the `fs-done` flag, because these live inside lists that
     * re-render — a slot painted twice would stack two SVGs in one span and
     * the second would never be seen anyway.
     */
    window.fsPaint = function (root) {
        (root || document).querySelectorAll('.fs-slot:not([data-fs-done])').forEach((el) => {
            const size = parseInt(el.getAttribute('data-fs-size') || '36', 10);
            const crop = el.getAttribute('data-fs-crop');
            if (crop) {
                const band = parseInt(el.getAttribute('data-fs-band') || '0', 10);
                el.innerHTML = window.fsCrop(crop, band, size);
            } else {
                el.innerHTML = window.fsAct(el.getAttribute('data-fs-act') || 'mixed', size);
            }
            el.setAttribute('data-fs-done', '1');
        });
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => window.fsPaint());
    } else {
        window.fsPaint();
    }
})();
</script>
@endonce
