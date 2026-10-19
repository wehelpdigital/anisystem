{{-- ================================================================
     Global and Quick Tools — the cross-season doors (notes, analyses,
     gallery, contacts) and the quick add tools, folded behind one
     remembered heading. Lived on the schedules page; lives on the
     dashboard now. Expects: $allSchedules (id, title) for the quick
     tools' schedule picker. Brings its own CSS, sheets and fold JS.
     ================================================================ --}}
@once
@push('head')
<style>
        /* ---- the three quick doors, one to a row ------------------------
           Shaped like the Hub's module tiles, because they do the same job:
           a tinted icon you recognise before you read, a name, and a line
           saying why you would tap it. Across a phone in a single strip
           there was room for none of that. ---- */
        /* --- Global and Quick Tools: one heading over the four doors ---
           Folds with a grid row rather than max-height, so the panel is
           exactly as tall as what is in it and the animation has a real end
           to reach. */
        .qa-panel { border: 1px solid var(--color-gray-200); border-radius: 1rem;
            background: var(--color-white); overflow: hidden; }
        /* The head wears the house green -- the same deep, slowly drifting
           band the app's green buttons and the tip card wear -- so the panel
           stands out from the white tiles and cards around it; it sat there
           in white on white and was easy to scroll past. White words on it,
           the icon in a pale bubble, no line along the foot (the list below
           keeps its own breathing room instead, so Global Notes does not
           sit on the band). By night the band goes a shade deeper and the
           words stay light. */
        .qa-panel-head { display: flex; align-items: center; gap: .7rem; width: 100%;
            text-align: left; padding: .7rem .8rem; cursor: pointer; border: 0; color: #fff;
            --sw-1: #2f5219; --sw-2: #4a7c2a; --sw-3: #6b9f3d;
            background-image: linear-gradient(120deg, var(--sw-1), var(--sw-2) 28%, var(--sw-3) 52%, var(--sw-2) 76%, var(--sw-1));
            background-size: 220% 100%; animation: gradSweep 11s ease-in-out infinite alternate;
            transition: filter .28s cubic-bezier(.22,1,.36,1); }
        .qa-panel-head:hover { filter: brightness(1.06); }
        .qa-panel-ico { width: 2.4rem; height: 2.4rem; border-radius: .7rem; flex: none;
            display: inline-flex; align-items: center; justify-content: center;
            /* No tile behind the icon (the owner's call): the toolbox sits
               straight on the green band. */
            background: transparent; color: #cfe6b8; box-shadow: none; }
        .qa-panel-ico svg { width: 1.25rem; height: 1.25rem; }
        .qa-panel-txt { min-width: 0; flex: 1 1 auto; }
        .qa-panel-txt b { display: block; font-size: .9rem; font-weight: 800; color: #fff; }
        /* Wraps like the Stash card's line under it, so the two folded heads
           stand the same height (the owner's call, 2026-10-07). */
        .qa-panel-txt i { display: block; font-style: normal; font-size: .75rem; color: rgb(255 255 255 / .82); }
        .qa-panel-chev { width: 1.1rem; height: 1.1rem; flex: none; color: rgb(255 255 255 / .9);
            transition: transform .28s cubic-bezier(.22,1,.36,1); }
        .qa-panel { border-color: var(--color-brand-200); }
        html.dark .qa-panel { border-color: #2b3a1c; }
        html.dark .qa-panel-head { --sw-1: #1f3512; --sw-2: #2f5219; --sw-3: #4a7c2a; color: #e8efe1; }
        html.dark .qa-panel-txt b { color: #f1f6ea; }
        html.dark .qa-panel-txt i { color: rgb(232 239 225 / .78); }
        .qa-panel.is-folded .qa-panel-chev { transform: rotate(-90deg); }
        .qa-panel-fold { display: grid; grid-template-rows: 1fr; opacity: 1;
            transition: grid-template-rows .42s cubic-bezier(.22,1,.36,1), opacity .32s ease; }
        .qa-panel.is-folded .qa-panel-fold { grid-template-rows: 0fr; opacity: 0; }
        .qa-panel-fold > div { min-height: 0; overflow: hidden; }
        .qa-panel .qa-stack { padding: .55rem; }
        /* Inside the panel the tiles are rows of a list, not cards on a page. */
        .qa-panel .qa-tile { border-color: var(--color-gray-100); }
        @media (prefers-reduced-motion: reduce) {
            .qa-panel-fold, .qa-panel-chev { transition: none; }
            .qa-panel-head { animation: none; }
        }

        .qa-stack { display: grid; gap: .5rem; }
        .qa-tile { display: flex; align-items: center; gap: .7rem; width: 100%; text-align: left;
            padding: .7rem .8rem; border-radius: .9rem; cursor: pointer; text-decoration: none;
            background: var(--color-white); border: 1px solid var(--color-gray-200);
            transition: transform .28s cubic-bezier(.22,1,.36,1), box-shadow .28s cubic-bezier(.22,1,.36,1),
                border-color .28s cubic-bezier(.22,1,.36,1); }
        @media (hover: hover) {.qa-tile:hover { transform: translateY(-1px); box-shadow: 0 10px 24px -16px rgb(0 0 0 / .5); } }
        .qa-tile .qa-ico { width: 2.6rem; height: 2.6rem; border-radius: .75rem; flex: none;
            display: inline-flex; align-items: center; justify-content: center; }
        .qa-tile .qa-ico svg { width: 1.3rem; height: 1.3rem; }
        .qa-tile .qa-txt { display: flex; flex-direction: column; gap: .1rem; min-width: 0; flex: 1 1 auto; }
        .qa-tile .qa-txt b { font-size: .88rem; font-weight: 800; color: var(--color-gray-900); line-height: 1.25; }
        .qa-tile .qa-txt i { font-style: normal; font-size: .72rem; font-weight: 500; line-height: 1.4;
            color: var(--color-gray-500); }
        /* The Anee tag (2026-10-12): on the tools Anee runs, and a lighter
           "+ Anee" on the ones where she can be asked inside. */
        .qa-anee { display: inline-flex; align-items: center; gap: .22rem; margin-left: .4rem; padding: .08rem .45rem .08rem .12rem; border-radius: 999px;
            font-size: .62rem; font-weight: 800; letter-spacing: .03em; line-height: 1.2; vertical-align: .12em; white-space: nowrap;
            color: #4d3800; background: linear-gradient(115deg, #fde68a, #f5c518); box-shadow: 0 2px 6px -3px rgb(180 130 0 / .7);
            position: relative; overflow: hidden; }
        .qa-anee img { width: .95rem; height: .95rem; border-radius: 999px; background: #fff; object-fit: cover; flex: none; }
        .qa-anee::after { content: ''; position: absolute; inset: 0; background: linear-gradient(100deg, transparent 35%, rgb(255 255 255 / .65) 50%, transparent 65%);
            transform: translateX(-120%); animation: qaAneeShine 4.5s ease-in-out infinite; animation-delay: var(--qa-d, 0s); }
        @keyframes qaAneeShine { 0%, 70% { transform: translateX(-120%); } 85%, 100% { transform: translateX(120%); } }
        .qa-anee.is-soft { color: #7a5600; background: #fff8dc; box-shadow: inset 0 0 0 1px #f2d16b; }
        .qa-anee.is-soft::after { display: none; }
        html.dark .qa-anee.is-soft { color: #f5d34a; background: rgb(245 197 24 / .1); box-shadow: inset 0 0 0 1px rgb(245 197 24 / .45); }
        @media (prefers-reduced-motion: reduce) { .qa-anee::after { animation: none; display: none; } }
        /* The three groups (2026-10-12): a heading that folds its tiles away, smoothly, and remembers. */
        .qa-sub { display: grid; }
        .qa-group { display: flex; flex-wrap: wrap; align-items: center; gap: .2rem .5rem; width: 100%; padding: .7rem .45rem .55rem; border-radius: .8rem;
            text-align: left; background: none; border: 0; cursor: pointer; animation: qaGroupIn .4s cubic-bezier(.22,1,.36,1) both;
            transition: background-color .28s cubic-bezier(.22,1,.36,1); }
        .qa-group.is-first { padding-top: .35rem; }
        @media (hover: hover) { .qa-group:hover { background: var(--color-gray-50); } html.dark .qa-group:hover { background: #1a2114; } }
        .qa-group:focus-visible { outline: 2px solid var(--color-brand-500); outline-offset: 2px; }
        .qa-group-chev { margin-left: auto; width: 1rem; height: 1rem; flex: none; color: var(--color-gray-400); transition: transform .38s cubic-bezier(.22,1,.36,1); }
        .qa-sub.is-folded .qa-group-chev { transform: rotate(-90deg); }
        .qa-sub-fold { display: grid; grid-template-rows: 1fr; opacity: 1; transition: grid-template-rows .4s cubic-bezier(.22,1,.36,1), opacity .3s ease; }
        .qa-sub.is-folded .qa-sub-fold { grid-template-rows: 0fr; opacity: 0; }
        /* A little room inside the fold, so a tile's hover lift is not clipped. */
        .qa-sub-in { min-height: 0; overflow: hidden; display: grid; gap: .5rem; padding: .15rem .2rem .3rem; margin: 0 -.2rem; }
        .qa-sub-in { transition: padding .4s cubic-bezier(.22,1,.36,1); }
        .qa-sub.is-folded .qa-sub-in { padding-top: 0; padding-bottom: 0; visibility: hidden; transition: padding .4s cubic-bezier(.22,1,.36,1), visibility 0s linear .4s; }
        @media (prefers-reduced-motion: reduce) { .qa-sub-fold, .qa-group-chev, .qa-group { transition: none; } }
        .qa-group .qa-anee { margin-left: 0; vertical-align: 0; }
        .qa-group b + .qa-anee { margin-left: .1rem; }
        /* A tool with Anee inside wears a small + Anee tag after its name. */
        .qa-txt .qa-anee.is-mini { margin-left: .35rem; padding: .02rem .38rem .02rem .1rem; font-size: .6rem; vertical-align: .08rem; }
        .qa-txt .qa-anee.is-mini img { width: .8rem; height: .8rem; }
        .qa-group b { font-size: .72rem; font-weight: 800; letter-spacing: .07em; text-transform: uppercase; color: var(--color-gray-600); }
        .qa-group i { flex-basis: 100%; font-style: normal; font-size: .72rem; line-height: 1.4; color: var(--color-gray-400); }
        .qa-group-dot { display: inline-grid; place-items: center; width: 1.35rem; height: 1.35rem; border-radius: 999px; color: var(--color-gray-500); background: var(--color-gray-100); }
        .qa-group-dot svg { width: .8rem; height: .8rem; }
        html.dark .qa-group b { color: #b7c9a3; } html.dark .qa-group i { color: #7f9270; }
        html.dark .qa-group-dot { background: #22301a; color: #a5c97e; }
        @keyframes qaGroupIn { from { opacity: 0; transform: translateY(4px); } }
        @media (prefers-reduced-motion: reduce) { .qa-group { animation: none; } }
        .qa-go { width: .95rem; height: .95rem; flex: none; color: var(--color-gray-300);
            transition: transform .28s cubic-bezier(.22,1,.36,1), color .28s cubic-bezier(.22,1,.36,1); }
        @media (hover: hover) {.qa-tile:hover .qa-go { transform: translateX(2px); } }
        @media (hover: hover) {.qa-notes:hover { border-color: #f0dcae; } .qa-notes:hover .qa-go { color: #b45309; } }
        /* Global Lots: how many lots are due for their six month check. */
        .qa-lots-old { display: inline-flex; align-items: center; gap: .2rem; margin-left: .4rem; padding: .05rem .45rem; border-radius: 999px; vertical-align: .1em;
            font-size: .68rem; font-weight: 800; color: #92400e; background: #fef3c7; }
        .qa-lots-old svg { width: .75rem; height: .75rem; }
        html.dark .qa-lots-old { color: #fcd34d; background: #3a2a0c; }
        @media (hover: hover) {.qa-gallery:hover { border-color: #c7dbf5; } .qa-gallery:hover .qa-go { color: #1d4ed8; } }
        @media (hover: hover) {.qa-cap:hover { border-color: #cfe3b8; } .qa-cap:hover .qa-go { color: #3d6823; } }
        @media (hover: hover) {.qa-rec:hover { border-color: #f3c4c4; } .qa-rec:hover .qa-go { color: #b91c1c; } }
        /* A tile is a door, not a choice: nothing lingers on it after a click
           or a tap. Keyboard users still see where they are. */
        .qa-tile:focus { outline: none; }
        .qa-tile:focus:not(:focus-visible) { box-shadow: none; transform: none; }
        .qa-tile:focus-visible { outline: 2px solid var(--color-brand-500); outline-offset: 2px; }
        html.dark .qa-tile { background: #151b12; border-color: #2b3a1c; }
        html.dark .qa-tile .qa-txt b { color: #e8efe1; }
        html.dark .qa-tile .qa-txt i { color: #93a684; }
        @media (prefers-reduced-motion: reduce) { .qa-tile, .qa-go { transition: none; } }

        /* The hues the three doors are told apart by, and the icon plate
           they sit on. Kept out of .qa-tile so the colour of a door and the
           shape of one stay separate things. */
        .qa-ico { border-radius: .75rem; }
        .qa-notes .qa-ico { background: #fdf6e6; color: #b45309; }
        .qa-gallery .qa-ico { background: #eaf1fd; color: #1d4ed8; }
        .qa-cap .qa-ico { background: #eef6e6; color: #3d6823; }
        .qa-wtp .qa-ico { background: #eef2fd; color: #4c65e0; }
        .qa-wtp:hover { border-color: #c7d2f5; } .qa-wtp:hover .qa-go { color: #4c65e0; }
        html.dark .qa-wtp .qa-ico { background: rgb(76 101 224 / .22); color: #a5b6f2; }
        .qa-rec .qa-ico { background: #fdecec; color: #b91c1c; }
        .qa-build .qa-ico { background: #fff4e5; color: #c2410c; }
        .qa-build:hover { border-color: #cfe3bd; } .qa-build:hover .qa-go { color: #3d6823; }
        html.dark .qa-build .qa-ico { background: rgb(194 65 12 / .22); color: #fdba74; }
        /* The phonebook had no plate of its own and sat bare in the list. */
        .qa-contacts .qa-ico { background: #fdeef4; color: #be185d; }
        @media (hover: hover) {.qa-contacts:hover { border-color: #f5c9dc; } .qa-contacts:hover .qa-go { color: #be185d; } }
        html.dark .qa-contacts .qa-ico { background: rgb(190 24 93 / .22); color: #f9a8d4; }
        .qa-cmp .qa-ico { background: #f3effd; color: #6d28d9; }
        @media (hover: hover) {.qa-cmp:hover { border-color: #d9ccf7; } .qa-cmp:hover .qa-go { color: #6d28d9; } }
        html.dark .qa-cmp .qa-ico { background: rgb(109 40 217 / .24); color: #c4b5fd; }
        .qa-maps .qa-ico { background: #eef6e6; color: #3d6823; }
        @media (hover: hover) {.qa-maps:hover { border-color: #cfe3b8; } .qa-maps:hover .qa-go { color: #3d6823; } }
        html.dark .qa-maps .qa-ico { background: rgb(107 159 61 / .2); }
        .qa-draw .qa-ico { background: #fff4e5; color: #c2410c; }
        @media (hover: hover) {.qa-draw:hover { border-color: #f5d3b3; } .qa-draw:hover .qa-go { color: #c2410c; } }
        html.dark .qa-draw .qa-ico { background: rgb(194 65 12 / .2); }
        .qa-tags .qa-ico { background: #e7f6f1; color: #0f766e; }
        .qa-workers .qa-ico { background: #eaf4fb; color: #1e4b6b; }
        @media (hover: hover) {.qa-workers:hover { border-color: #c6e0f2; } .qa-workers:hover .qa-go { color: #1e4b6b; } }
        html.dark .qa-workers .qa-ico { background: rgb(30 75 107 / .3); }
        @media (hover: hover) {.qa-tags:hover { border-color: #bfe5d8; } .qa-tags:hover .qa-go { color: #0f766e; } }
        html.dark .qa-tags .qa-ico { background: rgb(15 118 110 / .24); color: #7dd3c0; }
        html.dark .qa-notes .qa-ico { background: rgb(180 83 9 / .18); color: #e0b457; }
        html.dark .qa-gallery .qa-ico { background: rgb(29 78 216 / .22); color: #9fc0f5; }
        html.dark .qa-cap .qa-ico { background: rgb(61 104 35 / .25); color: #a5c97e; }
        html.dark .qa-rec .qa-ico { background: rgb(185 28 28 / .2); color: #f0a3a3; }
</style>
@endpush
@endonce

        {{-- Three doors, one to a row. They were a squeezed strip of three
             across a phone, where every word fell to an ellipsis and none of
             them said what the thing was for. Given a row each they can be
             what the Hub's tiles are: an icon you recognise, a name, and a
             line saying why you would tap it. --}}
        {{-- Folded behind one heading: this page is a list of seasons, and
             four full-width doors above it pushed the list off a phone
             screen. The choice is remembered per farm, because whether these
             are useful depends on how somebody works, not on which visit it
             is. --}}
        <section class="qa-panel" id="globalTools">
            <button type="button" class="qa-panel-head" id="globalToolsHead" aria-expanded="true" aria-controls="globalToolsBody">
                <span class="qa-panel-ico"><img src="{{ asset('images/icons/tool-box.png') }}" alt="" style="width:1.5rem;height:1.5rem;object-fit:contain"></span>
                <span class="qa-panel-txt">
                    <b>Global and Quick Tools</b>
                    <i>Planting analyses, satellite, NPK Plus, notes, maps and more, in one place.</i>
                </span>
                <svg class="qa-panel-chev" fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
            </button>
            <div class="qa-panel-fold" id="globalToolsBody">
              <div>
        <div class="qa-stack">
            {{-- Its twin: the notes hub gathers the words, this gathers the
                 pictures. Looking for a photo is remembering a picture, not a
                 season, so it does not ask which one first. --}}
            {{-- On Libre these two stay in the row, wearing a lock where the
                 chevron sat — the tap opens the upgrade sheet, not the page. --}}
            @php
                // The Anee tag: "Anee" where she does the work, "+ Anee" where she can be asked inside.
                $qAneeFace = \App\Models\AiSetting::current()->faceUrl();
                $qAnee = fn (bool $soft = false, int $i = 0) => '<span class="qa-anee' . ($soft ? ' is-soft' : '') . '" style="--qa-d:' . ($i * 0.35) . 's" title="'
                    . ($soft ? 'You can ask Anee inside this tool' : 'Anee, the smart farm technician, does this for you') . '"><img src="' . e($qAneeFace) . '" alt="">' . ($soft ? '+ Anee' : 'Anee') . '</span>';
                // The small tag on a tool that has Anee inside.
                $qPlus = '<span class="qa-anee is-soft is-mini" title="You can ask Anee inside this tool"><img src="' . e($qAneeFace) . '" alt="">+ Anee</span>';
                // The gold Anee tag after each tool she runs herself (2026-10-15, the owner), its shine taking turns down the list.
                $qMine = fn (int $i) => '<span class="qa-anee is-mini" style="--qa-d:' . ($i * 0.45) . 's" title="Anee, the smart farm technician, does this for you"><img src="' . e($qAneeFace) . '" alt="">Anee</span>';
            @endphp
            @php $qWtpLocked = ! \App\Support\Tier::farmCan('aiAnalyses'); $qWtpRung = \App\Support\Tier::farmUnlocksAt('aiAnalyses'); $qWtpWith = \App\Support\Tier::withPlan($qWtpRung); @endphp
            {{-- Grouped by Anee (2026-10-12): what she does for you, what has her inside, and the rest. --}}
            <div class="qa-sub" data-sub="anee">
            <button type="button" class="qa-group is-first" aria-expanded="true">
                <span class="qa-group-dot" aria-hidden="true"><svg fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 3.5l1.7 4.4 4.4 1.7-4.4 1.7L11 15.7l-1.7-4.4-4.4-1.7 4.4-1.7zM18 14.5l.8 2 2 .8-2 .8-.8 2-.8-2-2-.8 2-.8z"/></svg></span>
                <b>Anee does it for you{{ \App\Support\Tagalog::flag('anee-does-it') }}</b>
                {!! $qAnee(false, 9) !!}
                <svg class="qa-group-chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/></svg>
                <i>She reads your farm and writes it for you. Uses credits.</i>
            </button>
            <div class="qa-sub-fold"><div class="qa-sub-in">
            <a href="{{ route('wtp.page') }}" class="qa-tile qa-wtp"
               @if ($qWtpLocked) data-tier-lock="{{ $qWtpRung }}" data-lock-say="The When to Plant analysis comes with {{ $qWtpWith }}. Anee reads your town's climate and the El Niño or La Niña outlook to find your safest time to plant." @endif>
                <span class="qa-ico"><img src="{{ asset('images/appointment.png') }}" alt="" style="width:1.4rem;height:1.4rem;object-fit:contain"></span>
                <span class="qa-txt {{ $qWtpLocked ? 'tl-dim' : '' }}">
                    <b>When to Plant Analysis{{ \App\Support\Tagalog::flag('when-to-plant') }}{!! $qMine(0) !!}</b>
                    <i>Find the best time to plant your crop, based on your location, past weather, climate and the forecast.</i>
                </span>
                @if ($qWtpLocked)
                    <span class="tl-lock"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="4" y="10.5" width="16" height="10" rx="2.5"/><path d="M8 10.5V7a4 4 0 0 1 8 0v3.5"/></svg></span>
                @else
                    <svg class="qa-go" fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                @endif
            </a>
            <a href="{{ route('whatp.page') }}" class="qa-tile qa-wtp"
               @if ($qWtpLocked) data-tier-lock="{{ $qWtpRung }}" data-lock-say="The What to Plant analysis comes with {{ $qWtpWith }}. Anee looks at your location, the season's forecast and your soil to suggest the best crop." @endif>
                <span class="qa-ico"><img src="{{ asset('images/plant.png') }}" alt="" style="width:1.4rem;height:1.4rem;object-fit:contain"></span>
                <span class="qa-txt {{ $qWtpLocked ? 'tl-dim' : '' }}">
                    <b>What to Plant Analysis{{ \App\Support\Tagalog::flag('what-to-plant') }}{!! $qMine(1) !!}</b>
                    <i>Find the best crop to plant in your area, based on climate, the forecast, past weather, season, soil and water supply.</i>
                </span>
                @if ($qWtpLocked)
                    <span class="tl-lock"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="4" y="10.5" width="16" height="10" rx="2.5"/><path d="M8 10.5V7a4 4 0 0 1 8 0v3.5"/></svg></span>
                @else
                    <svg class="qa-go" fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                @endif
            </a>
            {{-- The third: which variety, searched on the web and ranked by
                 the farmer's own priorities. --}}
            <a href="{{ route('vary.page') }}" class="qa-tile qa-wtp"
               @if ($qWtpLocked) data-tier-lock="{{ $qWtpRung }}" data-lock-say="Variety Ranking comes with {{ $qWtpWith }}. Anee studies the newest {{ \App\Support\Region::ph() ? 'Philippine' : 'local' }} varieties and hybrids and ranks them for your soil, weather and priorities." @endif>
                <span class="qa-ico"><img src="{{ asset('images/icons/biotechnology.png') }}" alt="" style="width:1.4rem;height:1.4rem;object-fit:contain"></span>
                <span class="qa-txt {{ $qWtpLocked ? 'tl-dim' : '' }}">
                    <b>Variety Ranking{{ \App\Support\Tagalog::flag('variety-ranking') }}{!! $qMine(2) !!}</b>
                    <i>Not sure which variety to plant? Anee finds and ranks the best varieties for your location, weather, climate, season, soil and water supply.</i>
                </span>
                @if ($qWtpLocked)
                    <span class="tl-lock"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="4" y="10.5" width="16" height="10" rx="2.5"/><path d="M8 10.5V7a4 4 0 0 1 8 0v3.5"/></svg></span>
                @else
                    <svg class="qa-go" fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                @endif
            </a>
            {{-- The fourth: the whole season written out by growth stage --
                 bags, sprays, water, watch-list -- for one field. --}}
            <a href="{{ route('proto.page') }}" class="qa-tile qa-wtp"
               @if ($qWtpLocked) data-tier-lock="{{ $qWtpRung }}" data-lock-say="The Crop Protocol Analysis comes with {{ $qWtpWith }}. Anee writes your season stage by stage: which fertilizer and when, the sprays to have ready, the water, and the pests and weeds to watch." @endif>
                <span class="qa-ico"><img src="{{ asset('images/icons/checklist.png') }}" alt="" style="width:1.4rem;height:1.4rem;object-fit:contain"></span>
                <span class="qa-txt {{ $qWtpLocked ? 'tl-dim' : '' }}">
                    <b>Crop Protocol Analysis{{ \App\Support\Tagalog::flag('crop-protocol') }}{!! $qMine(3) !!}</b>
                    <i>Get a recommended protocol for your crop variety, stage by stage, based on your location, weather, soil, climate and water supply.</i>
                </span>
                @if ($qWtpLocked)
                    <span class="tl-lock"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="4" y="10.5" width="16" height="10" rx="2.5"/><path d="M8 10.5V7a4 4 0 0 1 8 0v3.5"/></svg></span>
                @else
                    <svg class="qa-go" fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                @endif
            </a>

            {{-- Satellite Analysis (2026-10-07): a drawn field read from
                 Sentinel-2 and Sentinel-1, then by Anee. --}}
            <a href="{{ route('sat.page') }}" class="qa-tile qa-wtp"
               @if ($qWtpLocked) data-tier-lock="{{ $qWtpRung }}" data-lock-say="Satellite Analysis comes with {{ $qWtpWith }}. Draw your field and Anee reads the newest satellite pictures and radar for crop health, weak spots and threats." @endif>
                <span class="qa-ico"><img src="{{ asset('images/icons/satellite.svg') }}" alt="" style="width:1.5rem;height:1.5rem;object-fit:contain"></span>
                <span class="qa-txt {{ $qWtpLocked ? 'tl-dim' : '' }}">
                    <b>Satellite Analysis{{ \App\Support\Tagalog::flag('satellite-analysis') }}{!! $qMine(4) !!}</b>
                    <i>Draw your field and see its health from space: greenness, weak spots, radar through clouds, threats and soil.</i>
                </span>
                @if ($qWtpLocked)
                    <span class="tl-lock"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="4" y="10.5" width="16" height="10" rx="2.5"/><path d="M8 10.5V7a4 4 0 0 1 8 0v3.5"/></svg></span>
                @else
                    <svg class="qa-go" fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                @endif
            </a>

            {{-- Land Prep (2026-10-14, the owner): Anee plans the land preparation
                 of every lot from PhilRice practice, the soil, the weather and the
                 crew, so it is hers to do. --}}
            @php $qLpRoute = \Illuminate\Support\Facades\Route::has('landprep.page') ? route('landprep.page') : route('fh.page', 'landprep'); @endphp
            <a href="{{ $qLpRoute }}" class="qa-tile qa-wtp"
               @if ($qWtpLocked) data-tier-lock="{{ $qWtpRung }}" data-lock-say="The Land Prep Planner comes with {{ $qWtpWith }}. Anee plans every step of getting your lots ready, dated back from planting, around the weather and your crew." @endif>
                <span class="qa-ico"><img src="{{ asset('images/icons/land-prep.svg') }}" alt="" style="width:1.5rem;height:1.5rem;object-fit:contain"></span>
                <span class="qa-txt {{ $qWtpLocked ? 'tl-dim' : '' }}">
                    <b>Land Prep Planner{{ \App\Support\Tagalog::flag('land-prep-planner') }}{!! $qMine(5) !!}</b>
                    <i>Every step of getting your lots ready, dated back from planting day, fitted to your soil, the weather and your workers.</i>
                </span>
                @if ($qWtpLocked)
                    <span class="tl-lock"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="4" y="10.5" width="16" height="10" rx="2.5"/><path d="M8 10.5V7a4 4 0 0 1 8 0v3.5"/></svg></span>
                @else
                    <svg class="qa-go" fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                @endif
            </a>

            </div></div></div>
            <div class="qa-sub" data-sub="inside">
            <button type="button" class="qa-group" aria-expanded="true">
                <span class="qa-group-dot" aria-hidden="true"><svg fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14.5 5.2a4.2 4.2 0 00-5.3 5.3L4 15.7 7.3 19l5.2-5.2a4.2 4.2 0 005.3-5.3l-2.6 2.6-2.4-.7-.7-2.4z"/></svg></span>
                <b>Your tools</b>
                {!! $qAnee(true) !!}
                <svg class="qa-group-chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/></svg>
                <i>Calculators, checkers and finders that read your farm. The ones marked + Anee can ask her too.</i>
            </button>
            <div class="qa-sub-fold"><div class="qa-sub-in">
            {{-- Satellite Weather (2026-10-07): clouds, rain and typhoon
                 tracks over the farm; a week pass in credits since 2026-10-12
                 (App\Support\SkyPass), Anee's reading priced on its own. --}}
            {{-- From Libre + Anee up (2026-10-17, the owner). --}}
            @php $qSkyLocked = ! \App\Support\Tier::farmCan('skyWeather'); $qSkyRung = \App\Support\Tier::farmUnlocksAt('skyWeather'); @endphp
            <a href="{{ route('sky.page') }}" class="qa-tile qa-wtp"
               @if ($qSkyLocked) data-tier-lock="{{ $qSkyRung }}" data-lock-say="Satellite Weather comes with {{ \App\Support\Tier::withPlan($qSkyRung) }}. Watch the clouds, rain and typhoon paths over your farm and ask Anee what they mean for your lot." @endif>
                <span class="qa-ico"><img src="{{ asset('images/icons/storm.svg') }}" alt="" style="width:1.5rem;height:1.5rem;object-fit:contain"></span>
                <span class="qa-txt {{ $qSkyLocked ? 'tl-dim' : '' }}">
                    <b>Satellite Weather{{ \App\Support\Tagalog::flag('satellite-weather') }}{!! $qPlus !!}</b>
                    <i>Watch clouds, rain and typhoon paths over your farm, see where the rain is headed, and ask Anee what it means for your lot. {{ \App\Support\SkyPass::price() }} credits a week.</i>
                </span>
                @if ($qSkyLocked)
                    <span class="tl-lock"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="4" y="10.5" width="16" height="10" rx="2.5"/><path d="M8 10.5V7a4 4 0 0 1 8 0v3.5"/></svg></span>
                @else
                    <svg class="qa-go" fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                @endif
            </a>
            {{-- Compare Reports: two saved reports side by side, any season
                 against any other. Gated as CompareController::gate() gates
                 it -- the farm's plan first (reportsAll), then a worker's
                 Reports grant. A worker the farm keeps out of Reports does
                 not see a door that only refuses. --}}
            @if (\App\Support\WorkerContext::canView() && \App\Support\WorkerContext::canUseModule('reports'))
                @php $qCmpLocked = ! \App\Support\Tier::farmCan('reportsAll'); $qCmpRung = \App\Support\Tier::farmUnlocksAt('reportsAll'); @endphp
                <a href="{{ route('compare.page') }}" class="qa-tile qa-cmp"
                   @if ($qCmpLocked) data-tier-lock="{{ $qCmpRung }}" data-lock-say="{{ \App\Support\Tier::say($qCmpRung, 'Compare Reports comes with {plan}. See any two saved reports side by side, like this season against the last.') }}" @endif>
                    <span class="qa-ico"><img src="{{ asset('images/icons/ab-testing.png') }}" alt="" style="width:1.4rem;height:1.4rem;object-fit:contain"></span>
                    <span class="qa-txt {{ $qCmpLocked ? 'tl-dim' : '' }}">
                        <b>Compare Reports{{ \App\Support\Tagalog::flag('compare-reports') }}{!! $qPlus !!}</b>
                        <i>Put two reports of the same kind side by side, see what changed, and let Anee explain it.</i>
                    </span>
                    @if ($qCmpLocked)
                        <span class="tl-lock"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="4" y="10.5" width="16" height="10" rx="2.5"/><path d="M8 10.5V7a4 4 0 0 1 8 0v3.5"/></svg></span>
                    @else
                        <svg class="qa-go" fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                    @endif
                </a>
            @endif

        <a href="{{ route('pb.page') }}" class="qa-tile qa-build">
            <span class="qa-ico"><img src="{{ asset('images/icons/bricks.png') }}" alt="" style="width:1.4rem;height:1.4rem;object-fit:contain"></span>
            <span class="qa-txt">
                <b>Protocol Builder{{ \App\Support\Tagalog::flag('protocol-builder') }}{!! $qPlus !!}</b>
                <i>Write your own protocol ahead of time and save it. Turn it into a new cropping schedule later, or ask Anee to check it for problems and strong points.</i>
            </span>
            <svg class="qa-go" fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
        </a>
        {{-- NPK Plus (2026-10-07): the free fertilizer calculator; Anee's
             reading of a plan is the paid step inside it. --}}
        {{-- From Libre + Anee up (2026-10-17, the owner). --}}
        @php $qNpkLocked = ! \App\Support\Tier::farmCan('npkPlus'); $qNpkRung = \App\Support\Tier::farmUnlocksAt('npkPlus'); @endphp
        <a href="{{ route('npk.page') }}" class="qa-tile qa-build"
           @if ($qNpkLocked) data-tier-lock="{{ $qNpkRung }}" data-lock-say="The NPK Plus calculator comes with {{ \App\Support\Tier::withPlan($qNpkRung) }}. Add up every nutrient in your fertilizer plan and see if it is enough for your crop and the yield you want." @endif>
            <span class="qa-ico"><img src="{{ asset('images/icons/npk.svg') }}" alt="" style="width:1.5rem;height:1.5rem;object-fit:contain"></span>
            <span class="qa-txt {{ $qNpkLocked ? 'tl-dim' : '' }}">
                <b>NPK Plus Calculator{{ \App\Support\Tagalog::flag('npk-plus') }}{!! $qPlus !!}</b>
                <i>Add up every nutrient in your fertilizer plan, as the element and the oxide, and see if it is enough for your crop and what yield it can feed.</i>
            </span>
            @if ($qNpkLocked)
                <span class="tl-lock"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="4" y="10.5" width="16" height="10" rx="2.5"/><path d="M8 10.5V7a4 4 0 0 1 8 0v3.5"/></svg></span>
            @else
                <svg class="qa-go" fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
            @endif
        </a>
        {{-- The LCC Checker (2026-10-14): the leaf color chart read free from
             the farmer's own chart, or by Anee from leaf photos for credits. --}}
        <a href="{{ route('lcc.page') }}" class="qa-tile qa-build">
            <span class="qa-ico"><img src="{{ asset('images/icons/leaf-color.svg') }}" alt="" style="width:1.5rem;height:1.5rem;object-fit:contain"></span>
            <span class="qa-txt">
                <b>LCC Checker{{ \App\Support\Tagalog::flag('lcc-checker') }}{!! $qPlus !!}</b>
                <i>Rice only. Is it time for nitrogen? Photograph your rice leaves and Anee reads their color, or tap your own leaf color chart, and see how much urea your area needs.</i>
            </span>
            <svg class="qa-go" fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
        </a>
        {{-- Benta o Bilad (2026-10-19, the owner): sell the harvest wet, or dry it first? --}}
        <a href="{{ route('bilad.page') }}" class="qa-tile qa-build">
            <span class="qa-ico"><img src="{{ asset('images/icons/benta-bilad.svg') }}" alt="" style="width:1.5rem;height:1.5rem;object-fit:contain"></span>
            <span class="qa-txt">
                <b>Benta o Bilad Calculator{{ \App\Support\Tagalog::flag('benta-o-bilad') }}</b>
                <i>Sell your palay wet, or dry it first? Put in your sacks, the harvester, the drying and both prices, and see which leaves you more money.</i>
            </span>
            <svg class="qa-go" fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
        </a>
        {{-- Seed Count and Distance (2026-10-19, the owner): the seeds for a spacing, or the spacing for the seeds. --}}
        <a href="{{ route('seedcalc.page') }}" class="qa-tile qa-build">
            <span class="qa-ico"><img src="{{ asset('images/icons/seed-count.svg') }}" alt="" style="width:1.5rem;height:1.5rem;object-fit:contain"></span>
            <span class="qa-txt">
                <b>Seed Count and Distance Calculator{{ \App\Support\Tagalog::flag('seed-count-distance') }}</b>
                <i>Play with the distance between rows and plants, and see the seeds you need, the plants that will stand and the harvest they can carry. Or start from your seeds and see the spacing.</i>
            </span>
            <svg class="qa-go" fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
        </a>
        {{-- How to Plant (2026-10-19, the owner): how traditional farmers plant each crop, step by step, and into the Protocol Builder. --}}
        @if (\Illuminate\Support\Facades\Route::has('htp.page'))
        <a href="{{ route('htp.page') }}" class="qa-tile qa-build">
            <span class="qa-ico"><img src="{{ asset('images/icons/how-to-plant.svg') }}" alt="" style="width:1.5rem;height:1.5rem;object-fit:contain"></span>
            <span class="qa-txt">
                <b>How to Plant{{ \App\Support\Tagalog::flag('how-to-plant') }}</b>
                <i>How traditional farmers plant every crop, step by step with the days, a video where there is one, and what to change for a higher yield. Send it to your Protocol Builder.</i>
            </span>
            <svg class="qa-go" fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
        </a>
        @endif

            {{-- The farm tools, in the same group (2026-10-14, the owner). --}}
        {{-- Field helpers (2026-10-07): the public site's weed control helper
             and pest and disease finders, as small modules of their own. --}}
        @php $qFhLocked = \App\Support\FieldPack::lockedHere(); @endphp
        @foreach (\App\Http\Controllers\FieldHelperController::TOOLS as $fhKey => [$fhName, $fhLead, $fhIcon])
            @continue($fhKey === 'landprep')
            <a href="{{ route('fh.page', $fhKey) }}" class="qa-tile qa-build"
               @if ($qFhLocked) data-field-pack @endif>
                <span class="qa-ico"><img src="{{ asset('images/icons/' . $fhIcon . '.svg') }}" alt="" style="width:1.5rem;height:1.5rem;object-fit:contain"></span>
                <span class="qa-txt {{ $qFhLocked ? 'tl-dim' : '' }}">
                    <b>{{ $fhName }}{{ \App\Support\Tagalog::flag(['weeds' => 'weed-control-helper', 'pests' => 'pest-finder', 'diseases' => 'disease-finder', 'nutrients' => 'nutrient-deficiency-finder', 'compat' => 'compatibility-checker', 'varieties' => 'variety-finder'][$fhKey] ?? '') }}</b>
                    <i>{{ $fhLead }}.</i>
                </span>
                @if ($qFhLocked)
                    <span class="tl-lock"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="4" y="10.5" width="16" height="10" rx="2.5"/><path d="M8 10.5V7a4 4 0 0 1 8 0v3.5"/></svg></span>
                @else
                    <svg class="qa-go" fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                @endif
            </a>
        @endforeach
            </div></div></div>
            {{-- The farm's own records (2026-10-16, the owner): what is kept, not
                 what is worked out. Notes, pictures, maps and drawings, the lots,
                 the crew, the phonebook and the tags, gathered from every season,
                 and the three quick doors that add to them. --}}
            <div class="qa-sub" data-sub="records">
            <button type="button" class="qa-group" aria-expanded="true">
                <span class="qa-group-dot" aria-hidden="true"><svg fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 7.5A1.5 1.5 0 015.5 6h4l2 2h7A1.5 1.5 0 0120 9.5v8a1.5 1.5 0 01-1.5 1.5h-13A1.5 1.5 0 014 17.5z"/></svg></span>
                <b>Your farm records</b>
                <svg class="qa-group-chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/></svg>
                <i>Notes, pictures, maps, lots, workers and contacts from every season, kept in one place.</i>
            </button>
            <div class="qa-sub-fold"><div class="qa-sub-in">
            <a href="{{ route('notes.hub') }}" class="qa-tile qa-notes">
                <span class="qa-ico"><img src="{{ asset('images/sticky-note.png') }}" alt="" style="width:1.4rem;height:1.4rem;object-fit:contain"></span>
                <span class="qa-txt">
                    <b>Global Notes{{ \App\Support\Tagalog::flag('global-notes') }}</b>
                    <i>Every note from all your seasons, in one place.</i>
                </span>
                <svg class="qa-go" fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
            </a>
            <a href="{{ route('gallery.hub') }}" class="qa-tile qa-gallery">
                <span class="qa-ico"><img src="{{ asset('images/gallery.png') }}" alt="" style="width:1.4rem;height:1.4rem;object-fit:contain"></span>
                <span class="qa-txt">
                    <b>Global Gallery{{ \App\Support\Tagalog::flag('gallery') }}</b>
                    <i>Every photo, drawing and saved map, in one place.</i>
                </span>
                <svg class="qa-go" fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
            </a>
            {{-- Maps and Draw left the seasons (2026-09-30): a farm's fields do
                 not change with the season, so the maps and drawings of them
                 are the grower's own, and any season can use them. --}}
            {{-- Global Workers (2026-10-12): the farm's own roster, added to any season. --}}
            @php $qGwLocked = ! \App\Support\Tier::farmCan('workers') || \App\Support\WorkerContext::inWorkerContext(); $qGwRung = \App\Support\Tier::farmUnlocksAt('workers'); @endphp
            @unless (\App\Support\WorkerContext::inWorkerContext())
            <a href="{{ route('workers.global') }}" class="qa-tile qa-workers"
               @if ($qGwLocked) data-tier-lock="{{ $qGwRung }}" data-lock-say="Workers come with {{ \App\Support\Tier::withPlan($qGwRung) }}. Keep your crew once and add them to any season, with their pay, skills and days off." @endif>
                <span class="qa-ico"><img src="{{ asset('images/friends.png') }}" alt="" style="width:1.4rem;height:1.4rem;object-fit:contain"></span>
                <span class="qa-txt {{ $qGwLocked ? 'tl-dim' : '' }}">
                    <b>Global Workers{{ \App\Support\Tagalog::flag('workers') }}</b>
                    <i>Your crew in one list: their pay, skills and days off, added to any season in a tap.</i>
                </span>
                @if ($qGwLocked)
                    <span class="tl-lock"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="4" y="10.5" width="16" height="10" rx="2.5"/><path d="M8 10.5V7a4 4 0 0 1 8 0v3.5"/></svg></span>
                @else
                    <svg class="qa-go" fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                @endif
            </a>
            @endunless
            {{-- Global Lots (2026-10-16, the owner): each lot answered once (its place,
                 size, soil, water and last season) for every analysis. A clock and a
                 count when a lot's answers are six months old. --}}
            @php $qLotsOld = \App\Support\FarmLots::staleCount(\App\Support\WorkerContext::effectiveOwnerId()); @endphp
            <a href="{{ route('farmlots.index') }}" class="qa-tile qa-lots">
                <span class="qa-ico"><img src="{{ asset('images/treasure-map.png') }}" alt="" style="width:1.4rem;height:1.4rem;object-fit:contain"></span>
                <span class="qa-txt">
                    <b>Global Lots{{ \App\Support\Tagalog::flag('global-lots') }}@if ($qLotsOld)<span class="qa-lots-old" title="{{ $qLotsOld }} {{ $qLotsOld === 1 ? 'lot is' : 'lots are' }} due for a check"><svg fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="8.5"/><path stroke-linecap="round" d="M12 7.5V12l3 2"/></svg>{{ $qLotsOld }}</span>@endif</b>
                    <i>Each lot once: where it is, its size, its soil and water. Every analysis reads it from here.</i>
                </span>
                <svg class="qa-go" fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
            </a>
            {{-- Maps leave Libre (2026-10-12): the tile stays, wearing a lock. An
                 Anee tool since 2026-10-14 (the owner), among the + Anee tools. --}}
            @php $qMapLocked = ! \App\Support\Tier::farmCan('maps'); $qMapRung = \App\Support\Tier::farmUnlocksAt('maps'); @endphp
            <a href="{{ route('maps.page') }}" class="qa-tile qa-maps"
               @if ($qMapLocked) data-tier-lock="{{ $qMapRung }}" data-lock-say="Maps come with {{ \App\Support\Tier::withPlan($qMapRung) }}. Draw your lots on the satellite map, measure them and pin what matters." @endif>
                <span class="qa-ico"><img src="{{ asset('images/location-marker.png') }}" alt="" style="width:1.4rem;height:1.4rem;object-fit:contain"></span>
                <span class="qa-txt {{ $qMapLocked ? 'tl-dim' : '' }}">
                    <b>Maps{{ \App\Support\Tagalog::flag('maps') }}{!! $qPlus !!}</b>
                    <i>Draw, measure and pin your fields. Save a map and attach it to your lots, activities or notes.</i>
                </span>
                @if ($qMapLocked)
                    <span class="tl-lock"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="4" y="10.5" width="16" height="10" rx="2.5"/><path d="M8 10.5V7a4 4 0 0 1 8 0v3.5"/></svg></span>
                @else
                    <svg class="qa-go" fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                @endif
            </a>

            <a href="{{ route('draw.page') }}" class="qa-tile qa-draw">
                <span class="qa-ico"><img src="{{ asset('images/writting.png') }}" alt="" style="width:1.4rem;height:1.4rem;object-fit:contain"></span>
                <span class="qa-txt">
                    <b>Draw{{ \App\Support\Tagalog::flag('draw') }}</b>
                    <i>Sketch a field layout, a plan, or anything that helps you plan your farm work.</i>
                </span>
                <svg class="qa-go" fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
            </a>
            {{-- The farm's phonebook — every tier, no locks. Workers, tractor
                 rentals, harvesters, buyers: tagged, searchable, one tap away. --}}
            <a href="{{ route('contacts.page') }}" class="qa-tile qa-contacts">
                <span class="qa-ico"><img src="{{ asset('images/list.png') }}" alt="" style="width:1.4rem;height:1.4rem;object-fit:contain"></span>
                <span class="qa-txt">
                    <b>Contact List{{ \App\Support\Tagalog::flag('contact-list') }}</b>
                    <i>Your farm phonebook: workers, traders, buyers and everyone your farm works with.</i>
                </span>
                <svg class="qa-go" fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
            </a>
            {{-- Every tag in every season and every tool, and what wears it.
                 Open to every plan and every login, as the page is: a worker
                 standing in somebody else's farm is shown their own tags
                 there, and the page says so. --}}
            <a href="{{ route('tags.global') }}" class="qa-tile qa-tags">
                <span class="qa-ico"><img src="{{ asset('images/label.png') }}" alt="" style="width:1.4rem;height:1.4rem;object-fit:contain"></span>
                <span class="qa-txt">
                    <b>Global Tags{{ \App\Support\Tagalog::flag('tags') }}</b>
                    <i>All your tags from every season and tool, and what each one is on.</i>
                </span>
                <svg class="qa-go" fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
            </a>
            @php
                /* The camera and the recorder are modules the owner grants,
                   and the Hub has always drawn these two tiles only for
                   somebody who holds them. This list did not, so a worker
                   with no recorder still had a Quick Record button here — a
                   door with nothing behind it. */
                $qMayCamera = \App\Support\WorkerContext::canUseModule('camera');
                $qMayVideo = \App\Support\WorkerContext::canUseModule('video');
            @endphp
            @if ($allSchedules->isNotEmpty() && $qMayCamera)
                <button type="button" id="quickCaptureBtn" class="qa-tile qa-cap">
                    <span class="qa-ico"><img src="{{ asset('images/camera.png') }}" alt="" style="width:1.4rem;height:1.4rem;object-fit:contain"></span>
                    <span class="qa-txt">
                        <b>Quick Capture{{ \App\Support\Tagalog::flag('quick-capture') }}</b>
                        <i>Take a photo of what is in front of you and save it to a season now.</i>
                    </span>
                    <svg class="qa-go" fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                </button>
            @endif
            @php
                /* The tier's own wall on video, judged by the FARM being
                   worked: a worker rides the owner's plan both ways — they
                   get what the farm bought, and nothing it did not. */
                $qVidLocked = ! \App\Support\Tier::farmCan('videoRecording');
                $qVidRung = \App\Support\Tier::farmUnlocksAt('videoRecording');
            @endphp
            @if ($allSchedules->isNotEmpty() && $qMayVideo)
                <button type="button" id="quickRecordBtn" class="qa-tile qa-rec"
                        @if ($qVidLocked) data-tier-lock="{{ $qVidRung }}" data-lock-say="{{ \App\Support\Tier::say($qVidRung, 'Video recording comes with {plan}. Photos and voice notes work on every plan.') }}" @endif>
                    <span class="qa-ico"><img src="{{ asset('images/video-camera-b.png') }}" alt="" style="width:1.4rem;height:1.4rem;object-fit:contain"></span>
                    <span class="qa-txt {{ $qVidLocked ? 'tl-dim' : '' }}">
                        <b>Quick Record{{ \App\Support\Tagalog::flag('quick-record') }}</b>
                        <i>Record a video when a photo is not enough, and explain what you see as you film.</i>
                    </span>
                    @if ($qVidLocked)
                        <span class="tl-lock"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="4" y="10.5" width="16" height="10" rx="2.5"/><path d="M8 10.5V7a4 4 0 0 1 8 0v3.5"/></svg></span>
                    @else
                        <svg class="qa-go" fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                    @endif
                </button>
            @endif
            {{-- The microphone has its own switch. This tile used to live
                 inside the video block above, so it came and went with a
                 permission that was never about speaking. --}}
            @if ($allSchedules->isNotEmpty() && \App\Support\WorkerContext::canUseModule('voice'))
                <button type="button" id="quickVoiceBtn" class="qa-tile qa-rec">
                    <span class="qa-ico"><img src="{{ asset('images/voice-recorder.png') }}" alt="" style="width:1.4rem;height:1.4rem;object-fit:contain"></span>
                    <span class="qa-txt">
                        <b>Quick Voice{{ \App\Support\Tagalog::flag('quick-voice') }}</b>
                        <i>Say what you see and save it as a note. Faster than typing in the field.</i>
                    </span>
                    <svg class="qa-go" fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                </button>
            @endif
            </div></div></div>
        </div>
              </div>
            </div>
        </section>

    {{-- The season tag picker (which season is chosen in the sheet) and the
         member's own picker (voice notes are global) come along for the
         three quick doors. --}}
    @include('sm.partials.tag-picker', ['schedule' => null])
    @include('partials.user-tags')
    @if (\App\Support\WorkerContext::canUseModule('camera'))
        @include('sm.partials.quick-capture', ['allSchedules' => $allSchedules])
    @endif
    @if (\App\Support\WorkerContext::canUseModule('video'))
        @include('sm.partials.quick-record', ['allSchedules' => $allSchedules])
    @endif
    @if (\App\Support\WorkerContext::canUseModule('voice'))
        @include('sm.partials.quick-voice', ['allSchedules' => $allSchedules])
    @endif
    {{-- The field helpers pack's offer, for the locked helper tiles (2026-10-14). --}}
    @if (\App\Support\FieldPack::lockedHere())
        @include('partials.field-pack-offer')
    @endif
    {{-- Quick Record borrows the shared recorder, so the panel needs it. --}}
    @include('community.partials.video-js')

    {{-- Every tool's "Preview" (2026-10-17, the owner: "a play icon that says preview that when
         clicked shows a modal with a video how to use each ... clickable even if the module is
         locked"). The video is the tool's tutorial card (config/tutorials.php; the owner sends the
         clips one by one, a placeholder plays until then), found by the tile's link through the
         tutorials' route map. --}}
    @php
        $qTut = [];
        foreach ((array) config('tutorials.routes', []) as $qRoute => $qKey) {
            if (! \Illuminate\Support\Facades\Route::has($qRoute)) {
                continue;
            }
            $qParams = str_contains($qKey, '{tool}') ? array_keys(\App\Http\Controllers\FieldHelperController::TOOLS) : [null];
            foreach ($qParams as $qP) {
                try {
                    $qTut[parse_url($qP === null ? route($qRoute) : route($qRoute, $qP), PHP_URL_PATH)] = str_replace('{tool}', (string) $qP, $qKey);
                } catch (\Throwable $e) {
                    // A route that needs more than it is given (one protocol's editor) has no tile.
                }
            }
        }
        $qTut += ['#quickCaptureBtn' => 'quick.capture', '#quickRecordBtn' => 'quick.record', '#quickVoiceBtn' => 'quick.voice'];
        $qTutPages = (array) config('tutorials.pages', []);
        $qTut = array_filter($qTut, fn ($k) => isset($qTutPages[$k]));
    @endphp
    @include('partials.tutorial-offer', ['keys' => array_values(array_unique($qTut)), 'auto' => null])
    <style>
        .qa-prev { align-self: flex-start; display: inline-flex; align-items: center; gap: .32rem; margin-top: .42rem; padding: .16rem .62rem .16rem .18rem;
            border-radius: 999px; font-size: .68rem; font-weight: 800; font-style: normal; letter-spacing: .02em; line-height: 1.4; color: #2d5016; background: #eef6e4;
            border: 1px solid #d3e6bf; cursor: pointer; -webkit-tap-highlight-color: transparent;
            transition: background-color .28s cubic-bezier(.22,1,.36,1), border-color .28s cubic-bezier(.22,1,.36,1), transform .28s cubic-bezier(.22,1,.36,1); }
        .qa-prev i { display: grid; place-items: center; width: 1.3rem; height: 1.3rem; border-radius: 999px; color: #3b2f00; background: #f5c518;
            box-shadow: 0 4px 10px -6px rgb(180 140 0 / .9); }
        .qa-prev svg { width: .62rem; height: .62rem; margin-left: 1px; }
        .qa-prev:hover { background: #e2efd3; border-color: #bcd9a0; }
        .qa-prev:active { transform: scale(.96); }
        .qa-prev:focus-visible { outline: 2px solid #4a7c2a; outline-offset: 2px; }
        html.dark .qa-prev { color: #cfe6b8; background: #1f2b17; border-color: #34481f; }
        html.dark .qa-prev:hover { background: #26351c; }
        /* A locked tile stays dim, its Preview does not: it is the one thing that works there. */
        .qa-tile .qa-txt.tl-dim { opacity: 1 !important; }
        .qa-tile .qa-txt.tl-dim > :not(.qa-prev) { opacity: .62; }
        @media (prefers-reduced-motion: reduce) { .qa-prev { transition: none; } }
    </style>
    <script>
    (() => {
        const MAP = @json($qTut);
        const PLAY = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M8 5.5v13l11-6.5z" fill="currentColor"/></svg>';
        const items = () => (window.ANEE_TUTORIALS && window.ANEE_TUTORIALS.items) || {};
        const keyOf = (t) => {
            if (t.id && MAP['#' + t.id]) return MAP['#' + t.id];
            const h = t.getAttribute('href');
            if (!h) return null;
            try { return MAP[new URL(h, location.href).pathname] || null; } catch (_) { return null; }
        };
        function dress() {
            document.querySelectorAll('#globalTools .qa-tile').forEach((t) => {
                if (t.querySelector('.qa-prev')) return;
                const key = keyOf(t);
                if (!key || !items()[key]) return;
                const name = (t.querySelector('.qa-txt b') || {}).textContent || 'this tool';
                const b = document.createElement('span');
                b.className = 'qa-prev';
                b.setAttribute('role', 'button');
                b.tabIndex = 0;
                b.dataset.tutPreview = key;
                b.setAttribute('aria-label', 'Preview: a video on how to use ' + name.trim());
                b.innerHTML = '<i>' + PLAY + '</i>Preview';
                (t.querySelector('.qa-txt') || t).appendChild(b);
            });
        }
        /* Caught on the window, before the page's own capture handlers (a locked tile's upgrade
           sheet, the field helpers' offer) and before the tile's link: Preview only plays. */
        const hit = (e) => e.target.closest && e.target.closest('[data-tut-preview]');
        window.addEventListener('click', (e) => {
            const b = hit(e);
            if (!b) return;
            e.preventDefault();
            e.stopImmediatePropagation();
            window.aneeTutorial && window.aneeTutorial.preview(b.dataset.tutPreview);
        }, true);
        window.addEventListener('keydown', (e) => {
            const b = (e.key === 'Enter' || e.key === ' ') && hit(e);
            if (!b) return;
            e.preventDefault();
            e.stopImmediatePropagation();
            window.aneeTutorial && window.aneeTutorial.preview(b.dataset.tutPreview);
        }, true);
        if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', dress, { once: true }); else dress();
    })();
    </script>

    {{-- Whether the tools panel is open. Kept per farm beside the folds this
         page already remembers, so a worker standing in somebody else's farm
         does not inherit the owner's choice. --}}
    <script>
    (function globalToolsFold() {
        const panel = document.getElementById('globalTools');
        const head = document.getElementById('globalToolsHead');
        if (!panel || !head) return;

        const KEY = 'smToolsFolded:' + @json(\App\Support\WorkerContext::effectiveOwnerId());
        const paint = (folded) => {
            panel.classList.toggle('is-folded', folded);
            head.setAttribute('aria-expanded', folded ? 'false' : 'true');
        };

        // Painted before the first frame where possible; the class only
        // changes a grid row, so there is nothing to flash.
        let folded = false;
        try { folded = localStorage.getItem(KEY) === '1'; } catch (_) {}
        paint(folded);

        head.addEventListener('click', () => {
            folded = !panel.classList.contains('is-folded');
            paint(folded);
            try { localStorage.setItem(KEY, folded ? '1' : '0'); } catch (_) {}
        });

        // Each group folds on its own heading, and is remembered the same way.
        panel.querySelectorAll('.qa-sub').forEach((sub) => {
            const btn = sub.querySelector('.qa-group');
            const subKey = KEY + ':' + sub.dataset.sub;
            const set = (f) => { sub.classList.toggle('is-folded', f); btn.setAttribute('aria-expanded', f ? 'false' : 'true'); };
            try { set(localStorage.getItem(subKey) === '1'); } catch (_) { set(false); }
            btn.addEventListener('click', () => {
                const f = !sub.classList.contains('is-folded');
                set(f);
                try { localStorage.setItem(subKey, f ? '1' : '0'); } catch (_) {}
            });
        });

        // A tile opens a module; it is never "selected". Drop the focus a
        // press leaves on it, and again when the page comes back from the
        // module (a cached page returns with the same tile still focused).
        const dropFocus = () => { const a = document.activeElement; if (a && a.classList && a.classList.contains('qa-tile')) a.blur(); };
        panel.addEventListener('pointerup', (e) => { if (e.target.closest('.qa-tile')) setTimeout(dropFocus, 0); });
        panel.addEventListener('click', (e) => { if (e.target.closest('.qa-tile')) setTimeout(dropFocus, 0); });
        window.addEventListener('pageshow', dropFocus);
    })();
    </script>
