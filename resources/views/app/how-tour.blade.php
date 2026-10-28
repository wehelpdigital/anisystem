{{-- How anee.io works, the dashboard's tour (2026-10-28, the owner: "rewrite it entirely, how it looks but i still
     wanna make it great, interactive, animated, with graphs, graphics, and make it persuasive").

     A story in partials/story-modal's shell, fetched the first time the card opens (route how.tour) and drawn
     once. Six scenes: the season ring, the steps one at a time with their tools (tap one for its screen, what
     it does and a door into it), what Anee reads before she answers (counted from the shelves), a day with the
     app, what each plan opens, and where to start. The words and the tools come from App\Support\HowItWorks,
     the list the public /how-it-works page tells too. The script is in app.partials.how-card. --}}
@php
    use App\Support\HowItWorks;
    use Illuminate\Support\Facades\Route;

    $htStages = HowItWorks::stages();
    $htFace = fn (string $f) => \App\Support\AneeEmoji::url($f);
    // A tool's door inside the app, the way the public tour's app mode finds it.
    $htLink = function (array $it) {
        [$r, $param] = array_pad(explode(':', (string) ($it['app'] ?? ''), 2), 2, null);
        if ($r && Route::has($r)) {
            return [$param ? route($r, explode(',', $param)) : route($r), $r === 'sm.index' ? 'Find it in your schedules' : 'Open it now'];
        }
        if (! empty($it['url'])) {
            return [url($it['url']), 'Read more about it'];
        }

        return null;
    };
    $htTools = [];
    $htPlans = [];
    // Each plan's own tools, by key, for the plans scene (a plan opens to show them).
    $htOwn = [];
    foreach ($htStages as $si => $st) {
        foreach ($st['items'] as $it) {
            if (! $it) {
                continue;
            }
            $shot = HowItWorks::shot($it['key']);
            $link = $htLink($it);
            $htTools[$it['key']] = ['name' => $it['name'], 'short' => $it['short'], 'what' => HowItWorks::plain($it['what']), 'gets' => array_map([HowItWorks::class, 'plain'], $it['gets']),
                'anee' => (bool) $it['anee'], 'icon' => asset('images/' . $it['icon']), 'shot' => $shot[0] ?? null, 'plan' => $it['plan']['says'] ?? '', 'more' => $it['plan']['more'] ?? null,
                'href' => $link[0] ?? null, 'go' => $link[1] ?? null, 'step' => $si];
            $htPlans[$it['plan']['tier'] ?? 'libre'] = ($htPlans[$it['plan']['tier'] ?? 'libre'] ?? 0) + 1;
            $htOwn[$it['plan']['tier'] ?? 'libre'][] = $it['key'];
        }
    }
    $htCount = count($htTools);
    // What each plan opens, counted up the ladder: a plan has its own tools and every one below it.
    $htLadder = [];
    $htSum = 0;
    foreach (array_filter(config('tiers'), fn ($t, $k) => $k !== 'admin', ARRAY_FILTER_USE_BOTH) as $k => $t) {
        $htSum += $htPlans[$k] ?? 0;
        $htLadder[] = ['name' => $t['name'], 'n' => $htSum, 'own' => $htPlans[$k] ?? 0, 'keys' => $htOwn[$k] ?? []];
    }
    $htFree = $htLadder[0]['n'] ?? 0;
    $htMaxStep = max(array_map(fn ($s) => count(array_filter($s['items'])), $htStages));
    // What Anee reads before she answers, counted from the shelves once a day (the numbers known on 2026-10-28 if a count fails).
    $htFacts = \Illuminate\Support\Facades\Cache::remember('how-tour-facts:v1', 86400, function () {
        $n = function (string $table, int $fallback) {
            try {
                return \Illuminate\Support\Facades\Schema::hasTable($table) ? (DB::table($table)->count() ?: $fallback) : $fallback;
            } catch (\Throwable $e) {
                return $fallback;
            }
        };

        return ['problems' => $n('as_crop_problems', 229), 'weeds' => $n('as_weeds', 113), 'towns' => $n('as_hazard_towns', 1648), 'products' => $n('as_products', 7346),
            'prices' => $n('as_market_series', 7638)];
    });
    $htDay = [
        ['🌅', 'Early morning', "Today's plan arrives", "Each lot's work for the day, the weather and the cash to prepare, in the app and by email to you and your workers.", 'wave'],
        ['✅', 'In the field', 'Tick the work done', 'Your crew sees their own tasks. Tick one done and the fertilizer or seed it used comes off the stock by itself.', 'thumbsup'],
        ['🌧️', 'When the weather turns', 'The plan moves with it', 'Rain on a spray day? Realign by Anee moves the work to the next good day, and the days after it follow.', 'thinking'],
        ['📸', 'Something looks wrong', 'A photo, then an answer', 'Send Anee a photo of the leaf. She knows the lot, its stage and its weather, and says what it is and what to do.', 'concerned'],
        ['🌙', 'Evening', 'The season adds itself up', 'Notes, costs and harvests fill the reports on their own: what it cost, what it made and what to do better.', 'delighted'],
    ];
    $htDoors = array_values(array_filter([
        isset($htTools['globalLots']) ? ['globalLots', '📍', 'Add your first lot', 'Its place, soil and water, once. Every analysis reads it.'] : null,
        isset($htTools['when']) ? ['when', '🗓️', 'Find your best planting week', 'Twenty years of your town\'s weather, read for your crop.'] : null,
        isset($htTools['auto']) ? ['auto', 'anee', 'Let Anee run the season', 'She plans it, then tells you each morning what to do.'] : null,
    ]));
    $htGlyph = fn (string $d, $w = 2) => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="' . $w . '" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="' . $d . '"/></svg>';
@endphp
<style>
    .hwt { --e: cubic-bezier(.22,1,.36,1); }
    /* ---------- 1. the season ring ---------- */
    .hwt-hero { display: grid; gap: 2rem; align-items: center; }
    @media (min-width: 900px) { .hwt-hero { grid-template-columns: minmax(0, 1.05fr) minmax(0, .95fr); gap: 3rem; } }
    .hwt-stats { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: .5rem; margin-top: 1.3rem; max-width: 28rem; }
    .hwt-stat { min-width: 0; padding: .7rem .75rem; border-radius: 1rem; background: rgb(255 255 255 / .06); box-shadow: inset 0 0 0 1px rgb(255 255 255 / .09); }
    .hwt-stat b { display: block; font-family: var(--font-heading); font-size: 1.7rem; font-weight: 800; line-height: 1; color: #fff; font-variant-numeric: tabular-nums; }
    .hwt-stat span { display: block; margin-top: .3rem; font-size: .74rem; font-weight: 700; color: var(--mute); }
    .hwt-stat.is-gold b { color: var(--gold); }
    .hwt-ring { position: relative; width: min(100%, 27rem); margin: 0 auto; aspect-ratio: 1; }
    /* On a phone the step words reach past the circle (Learn, Plant): the ring is drawn smaller so they keep a margin
       from the screen's edges (2026-10-28, the owner). */
    @media (max-width: 639.98px) { .hwt-ring { width: min(80%, 21rem); } }
    .hwt-ring svg { width: 100%; height: 100%; overflow: visible; }
    .hwt-ring .rg-track { fill: none; stroke: rgb(255 255 255 / .08); stroke-width: 2; }
    .hwt-ring .rg-arc { fill: none; stroke: url(#hwtArc); stroke-width: 3; stroke-linecap: round; stroke-dasharray: 1; stroke-dashoffset: 1; transform: rotate(-90deg); transform-origin: 200px 200px; }
    .stm-scene.is-in .hwt-ring .rg-arc { animation: hwtDraw 2.4s .3s var(--e) forwards; }
    @keyframes hwtDraw { to { stroke-dashoffset: 0; } }
    .hwt-ring .rg-node { opacity: 0; transform-box: fill-box; transform-origin: center; transform: scale(.4); }
    .stm-scene.is-in .hwt-ring .rg-node { animation: hwtPop .55s var(--e) forwards; animation-delay: calc(.35s + var(--k) * .28s); }
    @keyframes hwtPop { to { opacity: 1; transform: none; } }
    .hwt-ring .rg-node circle { fill: #15240e; stroke: rgb(168 204 126 / .55); stroke-width: 1.5; }
    .hwt-ring .rg-node.is-auto circle { stroke: var(--gold); }
    .hwt-ring .rg-node text { font: 800 11px var(--font-heading, sans-serif); fill: #dfe9d4; text-anchor: middle; }
    .hwt-ring .rg-node .rg-n { font-size: 8.5px; fill: var(--gold); }
    .hwt-ring .rg-orbit { transform-origin: 200px 200px; animation: hwtSpin 14s linear infinite; }
    .hwt-ring .rg-orbit circle { fill: var(--gold); filter: drop-shadow(0 0 6px rgb(245 197 24 / .9)); }
    @keyframes hwtSpin { to { transform: rotate(360deg); } }
    .hwt-ring .rg-me { position: absolute; left: 50%; top: 50%; width: 28%; aspect-ratio: 1; transform: translate(-50%, -50%); border-radius: 999px; padding: 4px;
        background: linear-gradient(135deg, #f5c518, #6aa33a); box-shadow: 0 0 0 10px rgb(245 197 24 / .08), 0 0 60px rgb(245 197 24 / .25); }
    .hwt-ring .rg-me img { width: 100%; height: 100%; border-radius: 999px; object-fit: cover; display: block; }
    .hwt-ring .rg-me::after { content: ''; position: absolute; inset: -14px; border-radius: 999px; border: 2px solid rgb(245 197 24 / .35); animation: hwtBreathe 3.2s ease-in-out infinite; }
    @keyframes hwtBreathe { 50% { transform: scale(1.08); opacity: .4; } }

    /* ---------- 2. the steps ---------- */
    .hwt-steps { display: grid; gap: 1.2rem; margin-top: 1.4rem; }
    @media (min-width: 1000px) { .hwt-steps { grid-template-columns: 17rem minmax(0, 1fr); align-items: start; gap: 1.6rem; } }
    .hwt-rail { display: flex; gap: .45rem; overflow-x: auto; scroll-snap-type: x mandatory; scroll-padding-inline: 1rem; padding: .2rem .1rem .5rem; margin: 0 -1rem; padding-left: 1rem; padding-right: 1rem; scrollbar-width: none; }
    .hwt-rail::-webkit-scrollbar { display: none; }
    .hwt-railbox { position: relative; min-width: 0; }
    /* Above the steps, never over them (2026-10-28, the owner: "remove the circle of the arrow telling to swipe ... move
       it up the tabs do not overlap it in the tabs"): a line that says they swipe, its arrows nudging the way to go. */
    .hwt-swipe { display: flex; align-items: center; justify-content: flex-end; gap: .1rem; margin: 0 0 .35rem; font-size: .7rem; font-weight: 800; color: var(--gold); transition: opacity .28s var(--e); }
    .hwt-swipe svg { width: .95rem; height: .95rem; animation: hwtSwipe 1.3s var(--e) infinite; }
    .hwt-swipe svg:first-of-type { margin-left: .2rem; }
    .hwt-swipe svg + svg { margin-left: -.55rem; opacity: .55; animation-delay: .14s; }
    @keyframes hwtSwipe { 0%, 100% { transform: translateX(0); } 50% { transform: translateX(5px); } }
    .hwt-railbox.is-end .hwt-swipe { opacity: .35; }
    @media (min-width: 1000px) { .hwt-swipe { display: none; } }
    @media (min-width: 1000px) { .hwt-rail { flex-direction: column; overflow: visible; margin: 0; padding: 0; position: sticky; top: 1rem; } }
    .hwt-r { flex: none; scroll-snap-align: start; display: grid; grid-template-columns: auto minmax(0, 1fr); gap: .1rem .6rem; align-items: center; min-width: 9.5rem; padding: .6rem .75rem;
        border-radius: 1rem; text-align: left; color: var(--ink); background: rgb(255 255 255 / .05); box-shadow: inset 0 0 0 1px rgb(255 255 255 / .08);
        transition: background-color .28s var(--e), box-shadow .28s var(--e), transform .28s var(--e); }
    .hwt-r:hover { background: rgb(255 255 255 / .09); }
    .hwt-r[aria-selected="true"] { background: rgb(245 197 24 / .14); box-shadow: inset 0 0 0 1.5px rgb(245 197 24 / .7); }
    .hwt-r i { grid-row: span 2; width: 2.3rem; height: 2.3rem; border-radius: .8rem; display: grid; place-items: center; color: var(--leaf); background: rgb(168 204 126 / .12); }
    .hwt-r[aria-selected="true"] i { color: #1d330d; background: var(--gold); }
    .hwt-r i svg { width: 1.15rem; height: 1.15rem; }
    .hwt-r b { font-size: .86rem; font-weight: 800; line-height: 1.2; color: #fff; }
    .hwt-r small { font-size: .68rem; font-weight: 700; color: var(--mute); }
    /* How many tools a step has: a bar, so the busy steps show. */
    .hwt-r em { grid-column: 2; display: flex; align-items: center; gap: .4rem; font-style: normal; font-size: .62rem; font-weight: 800; color: var(--mute); }
    .hwt-r em span { flex: 1; height: 4px; border-radius: 999px; background: rgb(255 255 255 / .08); overflow: hidden; }
    .hwt-r em span::before { content: ''; display: block; height: 100%; width: calc(var(--v) * 100%); border-radius: inherit; background: var(--leaf); }
    .hwt-r[aria-selected="true"] em span::before { background: var(--gold); }
    @media (max-width: 999px) { .hwt-r em { display: none; } }
    .hwt-panel { min-width: 0; }
    .hwt-panel[hidden] { display: none; }
    .hwt-panel.is-in { animation: hwtPanel .45s var(--e) both; }
    @keyframes hwtPanel { from { opacity: 0; transform: translateX(14px); } to { opacity: 1; transform: none; } }
    .hwt-ph { display: flex; gap: .9rem; align-items: flex-start; padding: 1rem; border-radius: 1.25rem; background: linear-gradient(135deg, rgb(255 255 255 / .08), rgb(255 255 255 / .03)); box-shadow: inset 0 0 0 1px rgb(255 255 255 / .09); }
    .hwt-ph img { flex: none; width: 3.4rem; height: 3.4rem; object-fit: contain; animation: hwtBob 3.6s ease-in-out infinite; }
    @keyframes hwtBob { 50% { transform: translateY(-4px) rotate(-3deg); } }
    .hwt-ph span { font-size: .68rem; font-weight: 900; letter-spacing: .12em; text-transform: uppercase; color: var(--gold); }
    .hwt-ph h3 { margin-top: .15rem; font-family: var(--font-heading); font-size: 1.3rem; font-weight: 800; line-height: 1.2; color: #fff; }
    .hwt-ph p { margin-top: .3rem; font-size: .88rem; line-height: 1.55; color: var(--mute); }
    .hwt-tools { display: grid; grid-template-columns: repeat(auto-fill, minmax(min(100%, 13.5rem), 1fr)); gap: .55rem; margin-top: .8rem; }
    .hwt-t { position: relative; display: flex; gap: .65rem; align-items: flex-start; padding: .75rem; border-radius: 1rem; text-align: left; color: var(--ink);
        background: rgb(255 255 255 / .045); box-shadow: inset 0 0 0 1px rgb(255 255 255 / .08); transition: transform .28s var(--e), background-color .28s var(--e), box-shadow .28s var(--e); }
    .hwt-t:hover { transform: translateY(-2px); background: rgb(255 255 255 / .08); box-shadow: inset 0 0 0 1px rgb(168 204 126 / .4), 0 14px 28px -18px rgb(0 0 0 / .8); }
    .hwt-panel.is-in .hwt-t { animation: hwtPop .45s var(--e) both; animation-delay: calc(var(--k) * 35ms + .08s); }
    .hwt-t > img { flex: none; width: 2.5rem; height: 2.5rem; padding: .3rem; border-radius: .8rem; object-fit: contain; background: #f4f8ee; }
    .hwt-t b { display: block; font-size: .86rem; font-weight: 800; line-height: 1.25; color: #fff; }
    .hwt-t small { display: block; margin-top: .15rem; font-size: .74rem; line-height: 1.4; color: var(--mute); }
    .hwt-tags { display: flex; flex-wrap: wrap; gap: .25rem; margin-top: .4rem; }
    .hwt-tags span { padding: .08rem .45rem; border-radius: 999px; font-size: .6rem; font-weight: 800; color: #cfe3b9; background: rgb(168 204 126 / .14); }
    .hwt-tags span.is-anee { color: #1d330d; background: var(--gold); }

    /* A tool, opened: its screen, what it does, the door. */
    .hwt-sheet { position: absolute; inset: 0; z-index: 8; display: flex; justify-content: flex-end; align-items: flex-end; }
    .hwt-sheet[hidden] { display: none; }
    .hwt-sheet-bg { position: absolute; inset: 0; background: rgb(5 10 3 / .6); opacity: 0; transition: opacity .3s var(--e); }
    .hwt-sheet.is-on .hwt-sheet-bg { opacity: 1; }
    .hwt-sheet-in { position: relative; width: 100%; max-height: 88%; overflow-y: auto; padding: 1.1rem 1rem calc(1.2rem + env(safe-area-inset-bottom, 0px)); border-radius: 1.5rem 1.5rem 0 0;
        background: #142010; box-shadow: 0 -20px 50px -20px rgb(0 0 0 / .8), inset 0 1px 0 rgb(255 255 255 / .08); transform: translateY(100%); transition: transform .42s var(--e); will-change: transform; overscroll-behavior: contain; }
    @media (min-width: 900px) { .hwt-sheet { align-items: stretch; } .hwt-sheet-in { width: 30rem; max-height: none; border-radius: 1.5rem 0 0 1.5rem; transform: translateX(100%); } }
    .hwt-sheet.is-on .hwt-sheet-in { transform: none; }
    .hwt-sheet-x { position: absolute; right: .8rem; top: .8rem; width: 2.4rem; height: 2.4rem; border-radius: .85rem; display: grid; place-items: center; color: #fff; background: rgb(255 255 255 / .08); }
    .hwt-sheet-x svg { width: 1.1rem; height: 1.1rem; }
    .hwt-shot { display: grid; place-items: center; margin: .2rem 0 1rem; }
    .hwt-shot div { width: min(15rem, 70%); padding: .45rem; border-radius: 1.6rem; background: #0a1206; box-shadow: 0 22px 40px -22px rgb(0 0 0 / .9), inset 0 0 0 1px rgb(255 255 255 / .1); }
    .hwt-shot img { display: block; width: 100%; height: auto; aspect-ratio: 540 / 1169; object-fit: cover; border-radius: 1.2rem; background: #0f1a0b; }
    .hwt-sh-h { display: flex; gap: .7rem; align-items: center; padding-right: 2.6rem; }
    .hwt-sh-h img { flex: none; width: 2.8rem; height: 2.8rem; padding: .35rem; border-radius: .9rem; object-fit: contain; background: #f4f8ee; }
    .hwt-sh-h b { display: block; font-family: var(--font-heading); font-size: 1.15rem; font-weight: 800; line-height: 1.2; color: #fff; }
    .hwt-sh-h small { display: block; margin-top: .1rem; font-size: .76rem; font-weight: 700; color: var(--leaf); }
    .hwt-sh-p { margin-top: .8rem; font-size: .9rem; line-height: 1.6; color: var(--ink); }
    .hwt-sh-g { display: grid; gap: .4rem; margin-top: .8rem; }
    .hwt-sh-g div { display: flex; gap: .5rem; align-items: flex-start; font-size: .84rem; line-height: 1.45; color: var(--mute); }
    .hwt-sh-g div::before { content: '✓'; flex: none; width: 1.2rem; height: 1.2rem; border-radius: 999px; display: grid; place-items: center; font-size: .7rem; font-weight: 900; color: #1d330d; background: var(--leaf); }
    .hwt-sh-plan { margin-top: .9rem; font-size: .76rem; font-weight: 700; color: var(--mute); }
    .hwt-sh-go { display: inline-flex; align-items: center; gap: .45rem; margin-top: 1rem; padding: .75rem 1.2rem; border-radius: 999px; font-weight: 800; font-size: .92rem; color: #1d330d; background: var(--gold); }
    .hwt-sh-go svg { width: 1rem; height: 1rem; }

    /* ---------- 3. what Anee reads ---------- */
    .hwt-facts { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .6rem; margin-top: 1.5rem; }
    @media (min-width: 900px) { .hwt-facts { grid-template-columns: repeat(3, minmax(0, 1fr)); gap: .8rem; } }
    .hwt-f { position: relative; overflow: hidden; padding: 1rem; border-radius: 1.2rem; background: rgb(255 255 255 / .05); box-shadow: inset 0 0 0 1px rgb(255 255 255 / .08); }
    .hwt-f::after { content: ''; position: absolute; right: -2.5rem; top: -2.5rem; width: 6rem; height: 6rem; border-radius: 999px; background: radial-gradient(circle, rgb(168 204 126 / .18), transparent 70%); }
    .hwt-f i { font-style: normal; font-size: 1.4rem; }
    .hwt-f b { display: block; margin-top: .35rem; font-family: var(--font-heading); font-size: clamp(1.5rem, 4vw, 2.1rem); font-weight: 800; line-height: 1; color: #fff; font-variant-numeric: tabular-nums; }
    .hwt-f b small { font-size: .55em; color: var(--gold); }
    .hwt-f span { display: block; margin-top: .35rem; font-size: .8rem; font-weight: 700; line-height: 1.4; color: var(--mute); }
    .hwt-flow { display: grid; gap: .5rem; margin-top: 1.2rem; padding: 1rem; border-radius: 1.2rem; background: rgb(245 197 24 / .08); box-shadow: inset 0 0 0 1px rgb(245 197 24 / .3); }
    @media (min-width: 900px) { .hwt-flow { grid-template-columns: 1fr auto 1fr auto 1fr; align-items: center; } }
    .hwt-flow > div { display: flex; gap: .6rem; align-items: center; font-size: .84rem; line-height: 1.4; color: var(--ink); }
    .hwt-flow > div img { flex: none; width: 2.3rem; height: 2.3rem; }
    .hwt-flow > div span:first-child { flex: none; font-size: 1.4rem; }
    .hwt-flow > i { justify-self: center; width: 2rem; height: 2px; background: linear-gradient(90deg, transparent, var(--gold)); position: relative; }
    .hwt-flow > i::after { content: ''; position: absolute; top: -3px; right: 0; width: 8px; height: 8px; border-radius: 999px; background: var(--gold); animation: hwtFlow 1.6s var(--e) infinite; }
    @keyframes hwtFlow { from { transform: translateX(-1.6rem); opacity: 0; } 40% { opacity: 1; } to { transform: none; opacity: 0; } }
    @media (max-width: 899px) { .hwt-flow > i { width: 2px; height: 1.2rem; background: linear-gradient(180deg, transparent, var(--gold)); } .hwt-flow > i::after { right: -3px; top: auto; bottom: 0; animation: none; } }

    /* ---------- 4. a day ---------- */
    .hwt-day { position: relative; display: grid; gap: .9rem; margin-top: 1.6rem; padding-left: 1.6rem; }
    .hwt-day::before { content: ''; position: absolute; left: .55rem; top: .4rem; bottom: .4rem; width: 2px; border-radius: 2px; background: linear-gradient(180deg, var(--gold), var(--leaf), rgb(168 204 126 / .15));
        transform-origin: top; transform: scaleY(0); transition: transform 1.6s var(--e) .2s; }
    .stm-scene.is-in .hwt-day::before { transform: none; }
    @media (min-width: 900px) { .hwt-day { grid-template-columns: repeat(5, minmax(0, 1fr)); padding-left: 0; padding-top: 1.6rem; gap: .8rem; }
        .hwt-day::before { left: .4rem; right: .4rem; top: .5rem; bottom: auto; width: auto; height: 2px; background: linear-gradient(90deg, var(--gold), var(--leaf), rgb(168 204 126 / .15)); transform-origin: left; transform: scaleX(0); }
        .stm-scene.is-in .hwt-day::before { transform: none; } }
    .hwt-d { position: relative; padding: .85rem; border-radius: 1.1rem; background: rgb(255 255 255 / .05); box-shadow: inset 0 0 0 1px rgb(255 255 255 / .08); }
    .hwt-d::before { content: ''; position: absolute; left: -1.38rem; top: 1rem; width: .75rem; height: .75rem; border-radius: 999px; background: var(--gold); box-shadow: 0 0 0 4px rgb(245 197 24 / .18); }
    @media (min-width: 900px) { .hwt-d::before { left: 1rem; top: -1.48rem; } }
    .hwt-d-h { display: flex; align-items: center; gap: .5rem; }
    .hwt-d-h img { flex: none; width: 2.2rem; height: 2.2rem; }
    .hwt-d-h small { display: block; font-size: .64rem; font-weight: 900; letter-spacing: .08em; text-transform: uppercase; color: var(--gold); }
    .hwt-d-h b { display: block; font-size: .9rem; font-weight: 800; line-height: 1.25; color: #fff; }
    .hwt-d p { margin-top: .45rem; font-size: .8rem; line-height: 1.5; color: var(--mute); }

    /* ---------- 5. what each plan opens ---------- */
    .hwt-plans { display: grid; gap: .6rem; margin-top: 1.6rem; max-width: 46rem; }
    .hwt-plw { border-radius: 1.1rem; background: rgb(255 255 255 / .035); box-shadow: inset 0 0 0 1px rgb(255 255 255 / .07); transition: background-color .3s var(--e), box-shadow .3s var(--e); }
    .hwt-plw.is-open { background: rgb(255 255 255 / .06); box-shadow: inset 0 0 0 1px color-mix(in srgb, var(--c) 55%, transparent); }
    .hwt-pl { display: grid; grid-template-columns: 7.5rem minmax(0, 1fr) 4.2rem 1.1rem; gap: .7rem; align-items: center; width: 100%; padding: .7rem .8rem; border-radius: 1.1rem; text-align: left; color: inherit; cursor: pointer; }
    @media (max-width: 520px) { .hwt-pl { grid-template-columns: minmax(0, 1fr) 4rem 1.1rem; } .hwt-pl > span { grid-column: 1 / -1; } }
    .hwt-pl > span { font-size: .86rem; font-weight: 800; color: #fff; }
    .hwt-pl > div { height: 1.6rem; border-radius: .5rem; background: rgb(255 255 255 / .06); overflow: hidden; }
    .hwt-pl > div i { display: block; height: 100%; border-radius: .5rem; background: var(--c); }
    .hwt-pl > b { font-size: .86rem; font-weight: 800; color: #fff; text-align: right; font-variant-numeric: tabular-nums; }
    .hwt-pl > b small { display: block; font-size: .64rem; font-weight: 700; color: var(--mute); }
    .hwt-pl-v { width: 1.1rem; height: 1.1rem; color: var(--mute); transition: transform .3s var(--e), color .3s var(--e); }
    .hwt-plw.is-open .hwt-pl-v { transform: rotate(180deg); color: var(--c); }
    /* A plan opened: its tools, grown open (grid rows, so the height eases with no measuring). */
    /* Closed, a plan's list is not drawn at all (hidden): a hundred and fifty tools clipped to nothing still cost the scroll
       its frames. It is shown, then grown open; closed, it folds, then hides. */
    .hwt-plb { display: grid; grid-template-rows: 0fr; transition: grid-template-rows .45s var(--e); }
    .hwt-plb[hidden] { display: none; }
    .hwt-plw.is-open .hwt-plb { grid-template-rows: 1fr; }
    .hwt-plb > div { min-height: 0; overflow: hidden; }
    .hwt-plb-in { padding: .1rem .8rem .9rem; }
    .hwt-plb-h { display: flex; align-items: center; gap: .45rem; margin: .7rem 0 .45rem; font-size: .68rem; font-weight: 900; letter-spacing: .1em; text-transform: uppercase; color: var(--mute); }
    .hwt-plb-h:first-child { margin-top: .2rem; color: var(--c); }
    .hwt-plb-h em { font-style: normal; padding: .05rem .45rem; border-radius: 999px; font-size: .64rem; letter-spacing: 0; color: #0d1609; background: var(--c); }
    .hwt-plb-g { display: grid; grid-template-columns: repeat(auto-fill, minmax(10.5rem, 1fr)); gap: .4rem; }
    @media (max-width: 420px) { .hwt-plb-g { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
    .hwt-pt { display: flex; align-items: center; gap: .5rem; min-width: 0; padding: .45rem .55rem; border-radius: .8rem; text-align: left; color: var(--ink); background: rgb(255 255 255 / .05);
        box-shadow: inset 0 0 0 1px rgb(255 255 255 / .07); transition: transform .28s var(--e), background-color .28s var(--e), box-shadow .28s var(--e); }
    .hwt-pt:hover { transform: translateY(-2px); background: rgb(255 255 255 / .09); box-shadow: inset 0 0 0 1px color-mix(in srgb, var(--c) 60%, transparent); }
    .hwt-pt.is-new { box-shadow: inset 0 0 0 1px color-mix(in srgb, var(--c) 40%, transparent); }
    .hwt-pt img { flex: none; width: 1.75rem; height: 1.75rem; padding: .2rem; border-radius: .55rem; object-fit: contain; background: #f4f8ee; }
    .hwt-pt span { flex: 1; min-width: 0; font-size: .74rem; font-weight: 800; line-height: 1.25; color: #fff; }
    .hwt-pt i { flex: none; font-style: normal; font-size: .7rem; }
    .hwt-plw.is-open .hwt-pt { animation: hwtPop .4s var(--e) both; animation-delay: calc(min(var(--k), 18) * 22ms + .1s); }
    @media (prefers-reduced-motion: reduce) { .hwt-plb, .hwt-pl-v, .hwt-pt { transition: none !important; } .hwt-plw.is-open .hwt-pt { animation: none !important; } }

    /* ---------- 6. where to start ---------- */
    .hwt-doors { display: grid; gap: .7rem; margin-top: 1.6rem; }
    @media (min-width: 900px) { .hwt-doors { grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 1rem; } }
    .hwt-door { position: relative; overflow: hidden; display: grid; gap: .3rem; padding: 1.15rem; border-radius: 1.3rem; color: var(--ink); background: rgb(255 255 255 / .05);
        box-shadow: inset 0 0 0 1px rgb(255 255 255 / .09); transition: transform .3s var(--e), box-shadow .3s var(--e), background-color .3s var(--e); }
    .hwt-door:hover { transform: translateY(-3px); background: rgb(255 255 255 / .08); box-shadow: inset 0 0 0 1px rgb(245 197 24 / .55), 0 20px 40px -24px rgb(0 0 0 / .9); }
    .hwt-door > i { font-style: normal; font-size: 1.8rem; }
    .hwt-door > i.is-face { display: block; width: 3.4rem; height: 3.4rem; margin: -.3rem 0 0 -.25rem; }
    .hwt-door > i.is-face img { width: 100%; height: 100%; object-fit: contain; filter: drop-shadow(0 6px 10px rgb(0 0 0 / .35)); transform-origin: 50% 90%; transition: transform .28s var(--e); }
    .hwt-door:hover > i.is-face img { transform: rotate(-6deg) scale(1.06); }
    .hwt-door b { font-family: var(--font-heading); font-size: 1.1rem; font-weight: 800; color: #fff; }
    .hwt-door span { font-size: .84rem; line-height: 1.5; color: var(--mute); }
    .hwt-door em { margin-top: .4rem; display: inline-flex; align-items: center; gap: .35rem; font-style: normal; font-size: .82rem; font-weight: 800; color: var(--gold); }
    .hwt-door em svg { width: .95rem; height: .95rem; transition: transform .28s var(--e); }
    .hwt-door:hover em svg { transform: translateX(4px); }
    .hwt-door.is-gold { background: linear-gradient(135deg, rgb(245 197 24 / .2), rgb(245 197 24 / .06)); box-shadow: inset 0 0 0 1px rgb(245 197 24 / .5); }

    @media (prefers-reduced-motion: reduce) {
        .hwt-ring .rg-arc { stroke-dashoffset: 0; animation: none !important; }
        .hwt-ring .rg-node { opacity: 1; transform: none; animation: none !important; }
        .hwt-ring .rg-orbit, .hwt-ring .rg-me::after, .hwt-ph img, .hwt-flow > i::after, .hwt-swipe svg { animation: none !important; }
        .hwt-panel.is-in, .hwt-panel.is-in .hwt-t { animation: none !important; }
        .hwt-day::before { transform: none !important; transition: none !important; }
        .hwt-sheet-in, .hwt-sheet-bg { transition: none !important; }
    }
</style>

<div class="hwt">
    {{-- 1. The season ring --}}
    <section class="stm-scene" data-title="The season">
        <div class="stm-wrap hwt-hero">
            <div>
                <span class="stm-kick" data-in>How anee.io works</span>
                <h2 class="stm-h" data-in style="--d: 80ms">Your whole season in one app, with <em>Anee</em> beside you at every step.</h2>
                <p class="stm-p" data-in style="--d: 160ms">From the first plan to the last sack. Every tool reads what you already told it about your farm, and Anee, your smart farm technician, reads it all before she answers.</p>
                <div class="hwt-stats" data-in style="--d: 240ms">
                    <div class="hwt-stat"><b data-count="{{ count($htStages) }}">{{ count($htStages) }}</b><span>steps in a season</span></div>
                    <div class="hwt-stat"><b data-count="{{ $htCount }}">{{ $htCount }}</b><span>tools that work together</span></div>
                    <div class="hwt-stat is-gold"><b data-count="{{ $htFree }}">{{ $htFree }}</b><span>free on every plan</span></div>
                </div>
                <button type="button" class="stm-next" data-stm-next data-in style="--d: 320ms">See the season, step by step
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14m-6-6l6 6 6-6"/></svg></button>
            </div>
            <div class="hwt-ring" aria-hidden="true">
                <svg viewBox="0 0 400 400">
                    <defs><linearGradient id="hwtArc" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#a8cc7e"/><stop offset="1" stop-color="#f5c518"/></linearGradient></defs>
                    <circle class="rg-track" cx="200" cy="200" r="150"/>
                    <circle class="rg-arc" cx="200" cy="200" r="150" pathLength="1"/>
                    <g class="rg-orbit"><circle cx="200" cy="50" r="5"/></g>
                    @foreach ($htStages as $k => $s)
                        @php $a = deg2rad(-90 + $k * 360 / count($htStages)); $x = 200 + 150 * cos($a); $y = 200 + 150 * sin($a); @endphp
                        <g class="rg-node{{ $s['key'] === 'auto' ? ' is-auto' : '' }}" style="--k: {{ $k }}">
                            <circle cx="{{ round($x, 1) }}" cy="{{ round($y, 1) }}" r="25"/>
                            <g transform="translate({{ round($x - 11, 1) }} {{ round($y - 16, 1) }}) scale(.92)" fill="none" stroke="{{ $s['key'] === 'auto' ? '#f5c518' : '#a8cc7e' }}" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="{{ $s['glyph'] }}"/></g>
                            <text class="rg-n" x="{{ round($x, 1) }}" y="{{ round($y + 17, 1) }}">{{ $k + 1 }}</text>
                            <text x="{{ round($x + ($x > 205 ? 34 : ($x < 195 ? -34 : 0)), 1) }}" y="{{ round($y + ($y > 205 ? 42 : ($y < 195 ? -32 : 4)), 1) }}" style="text-anchor: {{ $x > 205 ? 'start' : ($x < 195 ? 'end' : 'middle') }}">{{ $s['word'] }}</text>
                        </g>
                    @endforeach
                </svg>
                <span class="rg-me"><img src="{{ asset('images/anee/avatar-512.jpg') }}" alt=""></span>
            </div>
        </div>
    </section>

    {{-- 2. The steps, one at a time --}}
    <section class="stm-scene" data-title="The steps">
        <div class="stm-wrap">
            <span class="stm-kick" data-in>{{ HowItWorks::stepWord(true) }} steps, one season</span>
            <h2 class="stm-h2" data-in style="--d: 80ms">Tap a step, then any tool, to see what it does for your farm.</h2>
            <div class="hwt-steps" data-in style="--d: 160ms">
                {{-- On a phone the steps scroll sideways: arrows at the edge say so until the last one is in view (2026-10-28, the owner). --}}
                <div class="hwt-railbox">
                <p class="hwt-swipe" aria-hidden="true">Swipe for all {{ count($htStages) }} steps<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6"><path stroke-linecap="round" stroke-linejoin="round" d="M9 6l6 6-6 6"/></svg><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6"><path stroke-linecap="round" stroke-linejoin="round" d="M9 6l6 6-6 6"/></svg></p>
                <div class="hwt-rail" role="tablist" aria-label="The steps of a season">
                    @foreach ($htStages as $k => $s)
                        @php $n = count(array_filter($s['items'])); @endphp
                        <button type="button" class="hwt-r" role="tab" id="hwtTab{{ $k }}" aria-controls="hwtPanel{{ $k }}" aria-selected="{{ $k === 0 ? 'true' : 'false' }}" data-hwt-step="{{ $k }}">
                            <i>{!! $htGlyph($s['glyph']) !!}</i>
                            <b>{{ $k + 1 }}. {{ $s['title'] }}</b>
                            <small>{{ $s['when'] }}</small>
                            <em><span style="--v: {{ round($n / max(1, $htMaxStep), 3) }}"></span>{{ $n }} {{ $n === 1 ? 'tool' : 'tools' }}</em>
                        </button>
                    @endforeach
                </div>
                </div>
                <div>
                    @foreach ($htStages as $k => $s)
                        <div class="hwt-panel" id="hwtPanel{{ $k }}" role="tabpanel" aria-labelledby="hwtTab{{ $k }}" @if ($k !== 0) hidden @endif>
                            <div class="hwt-ph">
                                <img src="{{ $htFace($s['face'] ?? 'happy') }}" alt="">
                                <div><span>Step {{ $k + 1 }} · {{ $s['when'] }}</span><h3>{{ $s['title'] }}</h3><p>{{ HowItWorks::plain($s['lede']) }}</p></div>
                            </div>
                            <div class="hwt-tools">
                                @foreach (array_values(array_filter($s['items'])) as $i => $it)
                                    <button type="button" class="hwt-t" data-hwt-tool="{{ $it['key'] }}" style="--k: {{ $i }}">
                                        <img src="{{ asset('images/' . $it['icon']) }}" alt="" loading="lazy">
                                        <span><b>{{ $it['name'] }}</b><small>{{ HowItWorks::plain($it['short']) }}</small>
                                            <span class="hwt-tags">@if ($it['anee'])<span class="is-anee">Anee</span>@endif<span>{{ $it['plan']['name'] ?? '' }}</span></span></span>
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    {{-- 3. What Anee reads before she answers --}}
    <section class="stm-scene" data-title="What Anee reads">
        <div class="stm-wrap">
            <span class="stm-kick" data-in>Why her answers fit your field</span>
            <h2 class="stm-h2" data-in style="--d: 80ms">Before Anee answers, she reads your farm, then the shelves the app keeps for her.</h2>
            <p class="stm-p" data-in style="--d: 140ms">Not a guess from the internet: your lot, its stage and its weather, set against records the app has gathered and checked.</p>
            <div class="hwt-facts">
                <div class="hwt-f" data-in style="--d: 180ms"><i>🌀</i><b data-count="20">20</b><span>years of storms, floods, dry spells and rain for each place</span></div>
                <div class="hwt-f" data-in style="--d: 240ms"><i>🏘️</i><b data-count="{{ $htFacts['towns'] }}">{{ number_format($htFacts['towns']) }}</b><span>towns with their own hazard history</span></div>
                <div class="hwt-f" data-in style="--d: 300ms"><i>🐛</i><b data-count="{{ $htFacts['problems'] }}">{{ number_format($htFacts['problems']) }}</b><span>pests and diseases, each with its signs, prevention and cure</span></div>
                <div class="hwt-f" data-in style="--d: 360ms"><i>🌿</i><b data-count="{{ $htFacts['weeds'] }}">{{ number_format($htFacts['weeds']) }}</b><span>weeds, with the active ingredients that control them</span></div>
                <div class="hwt-f" data-in style="--d: 420ms"><i>🧴</i><b data-count="{{ $htFacts['products'] }}">{{ number_format($htFacts['products']) }}</b><span>registered farm products, Philippine companies first</span></div>
                <div class="hwt-f" data-in style="--d: 480ms"><i>💰</i><b data-count="{{ $htFacts['prices'] }}">{{ number_format($htFacts['prices']) }}</b><span>farmgate price records, to time the harvest and the sale</span></div>
            </div>
            <div class="hwt-flow" data-in style="--d: 560ms">
                <div><span>🌾</span><span><b>Your farm:</b> the lot, its soil and water, its stage today</span></div>
                <i aria-hidden="true"></i>
                <div><img src="{{ $htFace('thinking') }}" alt=""><span><b>Anee</b> weighs it with the weather and the shelves</span></div>
                <i aria-hidden="true"></i>
                <div><span>✅</span><span><b>An answer for your field,</b> not for farms in general</span></div>
            </div>
        </div>
    </section>

    {{-- 4. A day with anee.io --}}
    <section class="stm-scene" data-title="A day">
        <div class="stm-wrap">
            <span class="stm-kick" data-in>A day with anee.io</span>
            <h2 class="stm-h2" data-in style="--d: 80ms">Less to remember, less to worry about. More time in the field with your crop.</h2>
            <div class="hwt-day">
                @foreach ($htDay as $i => [$emo, $when, $title, $line, $face])
                    <div class="hwt-d" data-in style="--d: {{ 200 + $i * 160 }}ms">
                        <div class="hwt-d-h"><img src="{{ $htFace($face) }}" alt=""><div><small>{{ $emo }} {{ $when }}</small><b>{{ $title }}</b></div></div>
                        <p>{{ $line }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- 5. What each plan opens --}}
    <section class="stm-scene" data-title="The plans">
        <div class="stm-wrap">
            <span class="stm-kick" data-in>Start free, grow when you are ready</span>
            <h2 class="stm-h2" data-in style="--d: 80ms">{{ $htFree }} of the {{ $htCount }} tools are free on every plan.</h2>
            <p class="stm-p" data-in style="--d: 140ms">Each plan keeps every tool of the plans before it and adds its own. Open a plan to see its tools, and tap one to see what it does.</p>
            {{-- Each plan opens to its tools (2026-10-28, the owner: "show all the free tools if you expand libre, then show the
                 all tools that encompasses libre + anee and so on ... expandable contractable ... clickable as well to show
                 the purpose of each in a modal"): what it adds first, then what it keeps from the plans before; a tool opens
                 its sheet, as in the steps. --}}
            <div class="hwt-plans">
                @foreach ($htLadder as $i => $pl)
                    @php $plC = ['#a8cc7e', '#7fb24f', '#5a8f2e', '#f5c518'][$i] ?? '#a8cc7e'; @endphp
                    <div class="hwt-plw" data-in style="--d: {{ 200 + $i * 120 }}ms; --c: {{ $plC }}">
                        <button type="button" class="hwt-pl" aria-expanded="false" aria-controls="hwtPl{{ $i }}" data-hwt-plan>
                            <span>{{ $pl['name'] }}</span>
                            <div><i data-bar style="--v: {{ round($pl['n'] / max(1, $htCount), 3) }}; --d: {{ 300 + $i * 150 }}ms"></i></div>
                            <b><span data-count="{{ $pl['n'] }}">{{ $pl['n'] }}</span><small>of {{ $htCount }} tools</small></b>
                            <svg class="hwt-pl-v" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/></svg>
                        </button>
                        <div class="hwt-plb" id="hwtPl{{ $i }}" role="region" aria-label="{{ $pl['name'] }} tools" hidden><div><div class="hwt-plb-in">
                            @foreach (array_reverse(array_slice($htLadder, 0, $i + 1), true) as $j => $from)
                                @if ($from['keys'])
                                    <p class="hwt-plb-h">{{ $j === $i ? ($i === 0 ? 'Free on every plan' : 'Added with ' . $from['name']) : 'Kept from ' . $from['name'] }} <em>{{ count($from['keys']) }}</em></p>
                                    <div class="hwt-plb-g">
                                        @foreach ($from['keys'] as $k => $key)
                                            @php $t = $htTools[$key]; @endphp
                                            <button type="button" class="hwt-pt{{ $j === $i ? ' is-new' : '' }}" data-hwt-tool="{{ $key }}" style="--k: {{ $k }}">
                                                <img src="{{ $t['icon'] }}" alt="" loading="lazy" width="28" height="28"><span>{{ $t['name'] }}</span>@if ($t['anee'])<i title="Anee does the work">✨</i>@endif
                                            </button>
                                        @endforeach
                                    </div>
                                @endif
                            @endforeach
                        </div></div></div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- 6. Where to start --}}
    <section class="stm-scene" data-title="Start">
        <div class="stm-wrap">
            <span class="stm-kick" data-in>Start where you are</span>
            <h2 class="stm-h2" data-in style="--d: 80ms">One step today. Anee keeps up with the rest.</h2>
            <div class="hwt-doors">
                @foreach ($htDoors as $i => [$key, $emo, $title, $line])
                    @php $t = $htTools[$key]; @endphp
                    @if ($t['href'])
                        <a class="hwt-door{{ $key === 'auto' ? ' is-gold' : '' }}" href="{{ $key === 'auto' ? route('auto.index') : $t['href'] }}" @if ($key === 'auto') data-hwt-auto @endif data-in style="--d: {{ 160 + $i * 120 }}ms">
                            {{-- Anee Auto's door wears Anee herself (2026-10-28, the owner: "change the robot icon to anee's icon"). --}}
                            @if ($emo === 'anee')<i class="is-face"><img src="{{ $htFace('salute') }}" alt="" width="56" height="56" loading="lazy"></i>@else<i>{{ $emo }}</i>@endif<b>{{ $title }}</b><span>{{ $line }}</span>
                            <em>{{ $key === 'auto' ? 'See Anee Auto' : $t['go'] }} <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14m-6-6l6 6-6 6"/></svg></em>
                        </a>
                    @endif
                @endforeach
            </div>
            <p class="stm-fine" data-in style="--d: 520ms">Anee is a smart farm technician. Her advice is a guide: the weather, the field and the work done decide the harvest.</p>
        </div>
    </section>
</div>

<div class="hwt-sheet" data-hwt-sheet hidden>
    <div class="hwt-sheet-bg" data-hwt-close></div>
    <div class="hwt-sheet-in" role="dialog" aria-modal="true" aria-label="About this tool">
        <button type="button" class="hwt-sheet-x" data-hwt-close aria-label="Close"><svg fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24"><path stroke-linecap="round" d="M6 6l12 12M18 6L6 18"/></svg></button>
        <div data-hwt-body></div>
    </div>
</div>
<script type="application/json" data-hwt-data>@json($htTools)</script>
