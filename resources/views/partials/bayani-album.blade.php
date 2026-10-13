{{-- Bayani ng Lupa (2026-10-12; redrawn 2026-10-13 around the album's own
     cover, the owner's square photo of a farmer band playing on a bamboo
     stage in a palayan): the cover on the left like a record sleeve, its
     vinyl sliding out and turning while a song plays; the words, the player,
     the songs and Listen on beside it; the whole card washed in the cover's
     own colours (a tiny blurred copy behind a dark glaze), so no part of it
     sits on flat black. The files wait for a press (preload none). Shared by
     the homepage and the dashboard. Takes $id (unique on the page) and $h
     (the heading tag); $compact true shows three songs, the rest a tap away. --}}
@php
    $A = \App\Support\BayaniAlbum::class;
    $baTracks = $A::tracks();
    $baH = $h ?? 'h2';
    $baId = $id ?? 'ba';
    $cover = asset($A::COVER);
    $baCompact = (bool) ($compact ?? false);
    $baFirst = $baCompact ? 3 : count($baTracks);
@endphp
@once
<style>
    .ba-c { container: ba / inline-size; }
    .ba { --ba-ease: cubic-bezier(.22,1,.36,1); position: relative; overflow: hidden; isolation: isolate; border-radius: 1.8rem; color: #f6f1e4;
        background: #1d2412; box-shadow: 0 40px 80px -46px rgb(20 26 10 / .9), inset 0 0 0 1px rgb(255 255 255 / .08); }
    /* The wash: the cover itself, blurred to colour, under a warm dark glaze. */
    .ba-wash { position: absolute; inset: -12%; z-index: -2; background-size: cover; background-position: center; filter: blur(34px) saturate(1.6);
        transform: scale(1.05); animation: baWash 30s ease-in-out infinite alternate; }
    @keyframes baWash { to { transform: scale(1.15) rotate(2deg); } }
    .ba-glaze { position: absolute; inset: 0; z-index: -1;
        background: radial-gradient(70% 90% at 0% 0%, rgb(245 197 24 / .16), transparent 60%), linear-gradient(110deg, rgb(18 24 10 / .38) 0%, rgb(18 24 10 / .68) 45%, rgb(14 18 8 / .84) 100%); }
    .ba-grain { position: absolute; inset: 0; z-index: -1; opacity: .5; pointer-events: none;
        background-image: radial-gradient(rgb(255 255 255 / .05) 1px, transparent 1.4px); background-size: 4px 4px; }
    /* The body's three parts (head, player, songs) are the card's own grid
       items, placed by area: in rows beside the cover on the dashboard, and
       (compact, the homepage, 2026-10-13: "smaller but wider") the cover,
       the head and player, and the songs side by side. */
    .ba-body { display: contents; }
    .ba-in { display: grid; gap: 1.1rem 2.2rem; padding: 1.3rem; grid-template-areas: "cover" "head" "player" "songs"; }
    .ba-sleeve { grid-area: cover; } .ba-head { grid-area: head; min-width: 0; } .ba-player { grid-area: player; min-width: 0; } .ba-songs { grid-area: songs; min-width: 0; }
    @container ba (min-width: 760px) {
        .ba-in { grid-template-columns: minmax(0, .9fr) minmax(0, 1.1fr); grid-template-areas: "cover head" "cover player" "cover songs"; align-content: center; padding: 2.2rem; }
    }
    /* Compact: smaller and wider. */
    .ba.is-compact .ba-in { grid-template-columns: 6.5rem minmax(0, 1fr); grid-template-areas: "cover head" "player player" "songs songs"; gap: 1rem 1.1rem; padding: 1.1rem; align-items: center; }
    @container ba (min-width: 620px) {
        .ba.is-compact .ba-in { grid-template-columns: 11rem minmax(0, 1fr); grid-template-areas: "cover head" "cover player" "songs songs"; padding: 1.5rem; gap: .9rem 1.6rem; }
    }
    @container ba (min-width: 980px) {
        .ba.is-compact .ba-in { grid-template-columns: 12.5rem minmax(0, 1fr) minmax(0, 1fr); grid-template-areas: "cover head songs" "cover player songs"; align-items: center; padding: 1.6rem 1.9rem; gap: .6rem 2rem; }
    }
    .ba.is-compact .ba-sleeve { width: 100%; padding-right: 0; margin: 0; }
    .ba.is-compact .ba-disc { display: none; }
    .ba.is-compact .ba-cover { border-radius: 1rem; transform: none; }
    .ba.is-compact .ba-cover-tag { left: .45rem; bottom: .45rem; padding: .2rem .5rem; font-size: .6rem; }
    @container ba (max-width: 619.98px) { .ba.is-compact .ba-cover-tag { display: none; } .ba.is-compact .ba-cover { border-radius: .8rem; } }
    .ba.is-compact .ba-h { margin-top: .3rem; font-size: clamp(1.6rem, 3vw, 2.2rem); }
    .ba.is-compact .ba-p { margin-top: .4rem; font-size: .86rem; line-height: 1.5; }
    .ba.is-compact .ba-meta { margin-top: .5rem; }
    .ba.is-compact .ba-meta span { font-size: .68rem; padding: .16rem .5rem; }
    .ba.is-compact .ba-now { margin-top: 0; padding: .5rem; gap: .6rem; }
    .ba.is-compact .ba-play { width: 2.7rem; height: 2.7rem; }
    .ba.is-compact .ba-play svg { width: 1.15rem; height: 1.15rem; }
    .ba.is-compact .ba-now-t b { font-size: .9rem; }
    .ba.is-compact .ba-bar { margin-top: .45rem; }
    .ba.is-compact .ba-list { margin-top: 0; }
    .ba.is-compact .ba-row { padding: .36rem .55rem; }
    .ba.is-compact .ba-t b { font-size: .86rem; }
    .ba.is-compact .ba-listen { margin-top: .6rem; padding-top: .6rem; }
    .ba.is-compact .ba-listen a { width: 2rem; height: 2rem; }
    .ba.is-compact .ba-listen svg { width: .9rem; height: .9rem; }
    @container ba (max-width: 619.98px) { .ba.is-compact .ba-p, .ba.is-compact .ba-meta { display: none; } .ba.is-compact .ba-h { font-size: 1.7rem; } }

    /* The sleeve and its record. */
    .ba-sleeve { position: relative; width: min(100%, 26rem); margin: 0 auto; padding-right: 0; align-self: center; }
    @container ba (min-width: 760px) { .ba-sleeve { margin: 0; padding-right: 12%; } }
    .ba-disc { position: absolute; z-index: 0; top: 4%; right: 2%; width: 88%; aspect-ratio: 1; border-radius: 999px;
        background: radial-gradient(circle at 50% 50%, #f5c518 0 9%, #1a1a1a 9.5% 11%, transparent 11.5%),
            repeating-radial-gradient(circle at 50% 50%, #161616 0 1.2px, #232323 1.6px 3px), #111;
        box-shadow: 0 20px 40px -18px rgb(0 0 0 / .9), inset 0 0 0 2px rgb(255 255 255 / .05);
        transform: translateX(-18%); transition: transform .9s var(--ba-ease); }
    .ba-disc::after { content: ''; position: absolute; inset: 0; border-radius: inherit; background: conic-gradient(from 30deg, transparent 0 20%, rgb(255 255 255 / .1) 25%, transparent 32% 70%, rgb(255 255 255 / .07) 75%, transparent 82%); }
    .ba.is-in .ba-disc { transform: translateX(0); }
    @container ba (max-width: 759.98px) { .ba-disc { display: none; } }
    .ba.is-playing .ba-disc { animation: baSpin 4s linear infinite; }
    @keyframes baSpin { from { rotate: 0deg; } to { rotate: 360deg; } }
    .ba-cover { position: relative; z-index: 1; display: block; width: 100%; aspect-ratio: 1; border-radius: 1.2rem; overflow: hidden;
        box-shadow: 0 30px 60px -26px rgb(0 0 0 / .85), 0 0 0 1px rgb(255 255 255 / .12); transform: rotate(-1.5deg); transition: transform .5s var(--ba-ease); }
    .ba-cover img { display: block; width: 100%; height: 100%; object-fit: cover; }
    .ba-sleeve:hover .ba-cover { transform: rotate(0deg) translateY(-4px); }
    .ba-cover-tag { position: absolute; left: .8rem; bottom: .8rem; display: inline-flex; align-items: center; gap: .35rem; padding: .3rem .65rem; border-radius: 999px;
        font-size: .68rem; font-weight: 900; letter-spacing: .06em; text-transform: uppercase; color: #3b2f00; background: #f5c518; box-shadow: 0 8px 18px -10px rgb(0 0 0 / .7); }
    .ba-cover-tag svg { width: .85rem; height: .85rem; }

    /* The words. */
    .ba-kick { display: inline-flex; align-items: center; gap: .45rem; font-size: .72rem; font-weight: 900; letter-spacing: .12em; text-transform: uppercase; color: #f5c518; }
    .ba-eq { display: inline-flex; align-items: flex-end; gap: 2px; height: .8rem; }
    .ba-eq i { width: 3px; height: 30%; border-radius: 2px; background: currentColor; }
    .ba.is-playing .ba-eq i { animation: baEq .9s ease-in-out infinite; }
    .ba.is-playing .ba-eq i:nth-child(2) { animation-delay: .2s; } .ba.is-playing .ba-eq i:nth-child(3) { animation-delay: .4s; } .ba.is-playing .ba-eq i:nth-child(4) { animation-delay: .1s; }
    @keyframes baEq { 0%, 100% { height: 30%; } 50% { height: 100%; } }
    .ba-h { margin-top: .5rem; font-family: var(--font-heading); font-weight: 800; font-size: clamp(2rem, 4.4vw, 3.1rem); line-height: 1; letter-spacing: -.02em; color: #fff; }
    .ba-h em { font-style: normal; color: #f5c518; }
    .ba-p { margin-top: .7rem; max-width: 34rem; font-size: .95rem; line-height: 1.6; color: #ddd6c2; }
    .ba-meta { margin-top: .7rem; display: flex; flex-wrap: wrap; gap: .4rem; }
    .ba-meta span { padding: .22rem .6rem; border-radius: 999px; font-size: .72rem; font-weight: 800; color: #f1ead4; background: rgb(255 255 255 / .09); box-shadow: inset 0 0 0 1px rgb(255 255 255 / .12); }

    /* The player. */
    .ba-now { margin-top: 1.1rem; display: flex; align-items: center; gap: .75rem; padding: .65rem; border-radius: 1.1rem; background: rgb(255 255 255 / .08);
        box-shadow: inset 0 0 0 1px rgb(255 255 255 / .12); backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px); }
    .ba-play { position: relative; flex: none; width: 3.2rem; height: 3.2rem; border-radius: 999px; display: grid; place-items: center; color: #2a2100; background: #f5c518;
        box-shadow: 0 10px 24px -10px rgb(245 197 24 / .8); transition: transform .28s var(--ba-ease); }
    .ba-play:hover { transform: scale(1.06); }
    .ba-play::after { content: ''; position: absolute; inset: -6px; border-radius: inherit; border: 2px solid rgb(245 197 24 / .6); opacity: 0; animation: baPing 2.6s ease-out infinite; }
    .ba.is-playing .ba-play::after { animation: none; }
    @keyframes baPing { 0% { opacity: .9; transform: scale(.9); } 100% { opacity: 0; transform: scale(1.35); } }
    .ba-play svg { width: 1.4rem; height: 1.4rem; }
    .ba-play .i-pause, .ba.is-playing .ba-play .i-play { display: none; }
    .ba.is-playing .ba-play .i-pause { display: block; }
    .ba-now-t { flex: 1; min-width: 0; }
    .ba-now-t small { display: block; font-size: .64rem; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; color: #c9b98a; }
    .ba-now-t b { display: block; font-size: .98rem; font-weight: 800; color: #fff; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .ba-skip { flex: none; width: 2.2rem; height: 2.2rem; border-radius: 999px; display: grid; place-items: center; color: #f1ead4; background: rgb(255 255 255 / .08);
        transition: background-color .28s var(--ba-ease); }
    .ba-skip:hover { background: rgb(255 255 255 / .18); }
    .ba-skip svg { width: .95rem; height: .95rem; }
    .ba-bar { position: relative; margin-top: .7rem; height: 1.3rem; }
    .ba-bar input { position: absolute; inset: 0; width: 100%; opacity: 0; cursor: pointer; z-index: 2; }
    .ba-bar span { position: absolute; left: 0; right: 0; top: 50%; height: .3rem; border-radius: 999px; translate: 0 -50%; background: rgb(255 255 255 / .16); overflow: hidden; }
    .ba-bar span i { display: block; height: 100%; width: 0; border-radius: inherit; background: linear-gradient(90deg, #8fbf5a, #f5c518); }
    .ba-times { display: flex; justify-content: space-between; font-size: .7rem; font-variant-numeric: tabular-nums; color: #c3b998; }

    /* The songs. */
    .ba-list { margin-top: .8rem; display: grid; gap: .15rem; }
    .ba-list li { opacity: 0; transform: translateY(8px); transition: opacity .45s var(--ba-ease), transform .45s var(--ba-ease); transition-delay: calc(var(--k) * 60ms); }
    .ba.is-in .ba-list li { opacity: 1; transform: none; }
    .ba-row { width: 100%; display: flex; align-items: center; gap: .7rem; padding: .45rem .6rem; border-radius: .85rem; text-align: left; color: #f1ead4;
        transition: background-color .28s var(--ba-ease); }
    .ba-row:hover { background: rgb(255 255 255 / .08); }
    .ba-row[aria-current="true"] { background: rgb(245 197 24 / .15); box-shadow: inset 0 0 0 1px rgb(245 197 24 / .3); }
    .ba-n { flex: none; width: 1.5rem; text-align: center; font-size: .8rem; font-weight: 800; color: #c9b98a; font-variant-numeric: tabular-nums; }
    .ba-t { flex: 1; min-width: 0; }
    .ba-t b { display: block; font-size: .9rem; font-weight: 700; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .ba-row[aria-current="true"] .ba-t b { color: #f5c518; }
    .ba-t small { display: inline-block; margin-top: .1rem; padding: .04rem .4rem; border-radius: 999px; font-size: .6rem; font-weight: 800; letter-spacing: .05em; text-transform: uppercase;
        color: #3b2f00; background: #f5c518; }
    .ba-d { flex: none; font-size: .76rem; color: #c3b998; font-variant-numeric: tabular-nums; }
    .ba-bars { flex: none; display: none; align-items: flex-end; gap: 2px; width: 1rem; height: .9rem; color: #f5c518; }
    .ba-bars i { width: 3px; height: 35%; border-radius: 2px; background: currentColor; }
    .ba-row[aria-current="true"] .ba-n { display: none; }
    .ba-row[aria-current="true"] .ba-bars { display: inline-flex; margin: 0 .25rem; }
    .ba.is-playing .ba-row[aria-current="true"] .ba-bars i { animation: baEq .8s ease-in-out infinite; }
    .ba.is-playing .ba-row[aria-current="true"] .ba-bars i:nth-child(2) { animation-delay: .25s; }
    .ba.is-playing .ba-row[aria-current="true"] .ba-bars i:nth-child(3) { animation-delay: .5s; }
    .ba-more { display: grid; grid-template-rows: 0fr; opacity: 0; transition: grid-template-rows .28s var(--ba-ease), opacity .28s var(--ba-ease); }
    .ba-more > div { min-height: 0; overflow: hidden; }
    .ba-more .ba-list { margin-top: .15rem; }
    .ba.is-all .ba-more { grid-template-rows: 1fr; opacity: 1; }
    .ba-all { margin-top: .4rem; display: inline-flex; align-items: center; gap: .35rem; padding: .32rem .75rem; border-radius: 999px; font-size: .78rem; font-weight: 800;
        color: #f1ead4; background: rgb(255 255 255 / .08); transition: background-color .28s var(--ba-ease); }
    .ba-all:hover { background: rgb(255 255 255 / .16); }
    .ba-all svg { width: .9rem; height: .9rem; transition: transform .28s var(--ba-ease); }
    .ba.is-all .ba-all svg { transform: rotate(180deg); }

    /* Listen on. */
    .ba-listen { margin-top: 1.1rem; padding-top: 1rem; border-top: 1px solid rgb(255 255 255 / .1); display: flex; flex-wrap: wrap; align-items: center; gap: .45rem; }
    .ba-listen > span { margin-right: .3rem; font-size: .68rem; font-weight: 900; letter-spacing: .1em; text-transform: uppercase; color: #c9b98a; }
    .ba-listen a { display: grid; place-items: center; width: 2.25rem; height: 2.25rem; border-radius: 999px; color: #f6f1e4; background: rgb(255 255 255 / .1);
        box-shadow: inset 0 0 0 1px rgb(255 255 255 / .14); transition: background-color .28s var(--ba-ease), transform .28s var(--ba-ease); }
    .ba-listen a:hover, .ba-listen a:focus-visible { background: var(--c); transform: translateY(-2px); }
    .ba-listen svg { width: 1rem; height: 1rem; fill: currentColor; }

    @media (prefers-reduced-motion: reduce) {
        .ba-wash, .ba-play::after, .ba.is-playing .ba-eq i, .ba.is-playing .ba-disc, .ba.is-playing .ba-row[aria-current="true"] .ba-bars i { animation: none; }
        .ba-list li { opacity: 1; transform: none; transition: none; }
        .ba-disc { transform: none; transition: none; }
        .ba-play, .ba-skip, .ba-row, .ba-more, .ba-all svg, .ba-listen a, .ba-cover { transition: none; }
    }
    html.sm-still .ba-wash, html.sm-still .ba.is-playing .ba-disc { animation: none; }
</style>
@endonce
<div class="ba-c"><section class="ba{{ $baCompact ? ' is-compact' : '' }}" id="{{ $baId }}" data-album aria-labelledby="{{ $baId }}H" aria-describedby="{{ $baId }}P">
    <span class="ba-wash" aria-hidden="true" style="background-image: url('{{ $cover }}-bg.webp')"></span>
    <span class="ba-glaze" aria-hidden="true"></span>
    <span class="ba-grain" aria-hidden="true"></span>
    <div class="ba-in">
        <div class="ba-sleeve">
            <span class="ba-disc" aria-hidden="true"></span>
            <span class="ba-cover">
                <img src="{{ $cover }}-800.webp" srcset="{{ $cover }}-480.webp 480w, {{ $cover }}-800.webp 800w, {{ $cover }}-1200.webp 1200w"
                     sizes="(min-width: 1024px) 26rem, 90vw" width="1200" height="1200" alt="{{ $A::ALT }}" title="{{ $A::TITLE }}, the anee.io album" loading="lazy" decoding="async">
                <span class="ba-cover-tag"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/></svg>{{ count($baTracks) }} songs</span>
            </span>
        </div>
        <div class="ba-body">
            <div class="ba-head">
            <span class="ba-kick">The anee.io album <span class="ba-eq" aria-hidden="true"><i></i><i></i><i></i><i></i></span></span>
            <{{ $baH }} class="ba-h" id="{{ $baId }}H">Bayani ng <em>Lupa</em></{{ $baH }}>
            <p class="ba-p" id="{{ $baId }}P">Seven songs for the heroes of the land, the Filipino farmers who feed the country.@unless ($baCompact) From the farmers who built anee.io, for every farmer who plants with a dream and harvests with a plan.@endunless</p>
            <div class="ba-meta"><span>{{ count($baTracks) }} songs</span><span>{{ intdiv(array_sum(array_column($baTracks, 'seconds')), 60) }} minutes</span><span>Free to play</span></div>
            </div>
            <div class="ba-player">
            <div class="ba-now">
                <button type="button" class="ba-play" data-ba-play aria-label="Play {{ $baTracks[0]['title'] }}">
                    <svg class="i-play" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M8 5.14v13.72L19 12 8 5.14z"/></svg>
                    <svg class="i-pause" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M7 5h3.2v14H7zM13.8 5H17v14h-3.2z"/></svg>
                </button>
                <span class="ba-now-t"><small>Now playing</small><b data-ba-now>{{ $baTracks[0]['title'] }}</b></span>
                <button type="button" class="ba-skip" data-ba-skip="-1" aria-label="The song before"><svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M6 5h2.4v14H6zM20 5.5v13L9.5 12 20 5.5z"/></svg></button>
                <button type="button" class="ba-skip" data-ba-skip="1" aria-label="The next song"><svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M15.6 5H18v14h-2.4zM4 5.5v13L14.5 12 4 5.5z"/></svg></button>
            </div>
            <div class="ba-bar"><input type="range" min="0" max="1000" value="0" step="1" aria-label="Move through the song" data-ba-seek><span><i data-ba-fill></i></span></div>
            <div class="ba-times"><span data-ba-cur>0:00</span><span data-ba-dur>{{ $baTracks[0]['length'] }}</span></div>
            </div>
            <div class="ba-songs">
            @php
                $baRow = fn ($i, $t) => '<li style="--k: ' . $i . '"><button type="button" class="ba-row" data-ba-i="' . $i . '" aria-current="' . ($i === 0 ? 'true' : 'false') . '" aria-label="Play ' . e($t['title']) . ', ' . e($t['length']) . '">'
                    . '<span class="ba-n">' . ($i + 1) . '</span><span class="ba-bars" aria-hidden="true"><i></i><i></i><i></i></span>'
                    . '<span class="ba-t"><b>' . e($t['title']) . '</b>' . ($t['main'] ? '<small>Main song</small>' : '') . '</span>'
                    . '<span class="ba-d">' . e($t['length']) . '</span></button></li>';
            @endphp
            <ol class="ba-list" aria-label="The songs of {{ $A::TITLE }}">
                @foreach (array_slice($baTracks, 0, $baFirst) as $i => $t){!! $baRow($i, $t) !!}@endforeach
            </ol>
            @if ($baFirst < count($baTracks))
                <div class="ba-more" id="{{ $baId }}More"><div>
                    <ol class="ba-list" start="{{ $baFirst + 1 }}" aria-label="More songs of {{ $A::TITLE }}">
                        @foreach (array_slice($baTracks, $baFirst, null, true) as $i => $t){!! $baRow($i, $t) !!}@endforeach
                    </ol>
                </div></div>
                <button type="button" class="ba-all" data-ba-all aria-expanded="false" aria-controls="{{ $baId }}More">
                    <span>All {{ count($baTracks) }} songs</span>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/></svg>
                </button>
            @endif
            <div class="ba-listen">
                <span>Listen on</span>
                @foreach ($A::LISTEN as $baSvc => [$baName, $baUrl, $baColor, $baPath])
                    <a href="{{ $baUrl }}" target="_blank" rel="noopener" style="--c: {{ $baColor }}" aria-label="{{ $A::TITLE }} on {{ $baName }}" title="{{ $baName }}">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="{{ $baPath }}"/></svg>
                    </a>
                @endforeach
            </div>
            </div>
        </div>
    </div>
    <audio preload="none" data-ba-audio></audio>
    <script type="application/json" data-ba-data>{!! json_encode(['tracks' => $baTracks, 'album' => $A::TITLE, 'art' => $cover . '-480.webp'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
</section></div>
@once
<script>
(() => {
    /* The album player: one song at a time, the next one on its own when a
       song ends, any song from the list, a bar to drag. Playing it pauses any
       other sound on the page (the Meet Anee film), and the phone's lock
       screen shows the song. */
    const fmt = (t) => (Number.isFinite(t) ? Math.floor(t / 60) + ':' + String(Math.floor(t % 60)).padStart(2, '0') : '0:00');
    const wire = (root) => {
        if (root.dataset.baWired) return;
        root.dataset.baWired = '1';
        const D = JSON.parse(root.querySelector('[data-ba-data]').textContent);
        const a = root.querySelector('[data-ba-audio]'), seek = root.querySelector('[data-ba-seek]'), fill = root.querySelector('[data-ba-fill]');
        const cur = root.querySelector('[data-ba-cur]'), dur = root.querySelector('[data-ba-dur]'), now = root.querySelector('[data-ba-now]'), play = root.querySelector('[data-ba-play]');
        const rows = [...root.querySelectorAll('[data-ba-i]')];
        let i = 0, loaded = -1;
        const mark = () => {
            const t = D.tracks[i];
            rows.forEach((r) => r.setAttribute('aria-current', String(Number(r.dataset.baI) === i)));
            now.textContent = t.title; dur.textContent = t.length;
            play.setAttribute('aria-label', (a.paused ? 'Play ' : 'Pause ') + t.title);
            if ('mediaSession' in navigator) {
                try { navigator.mediaSession.metadata = new MediaMetadata({ title: t.title, artist: 'anee.io', album: D.album, artwork: [{ src: D.art, sizes: '480x480', type: 'image/webp' }] }); } catch (_) { /* old browser */ }
            }
        };
        const load = (k) => { i = (k + D.tracks.length) % D.tracks.length; if (loaded !== i) { a.src = D.tracks[i].src; loaded = i; fill.style.width = '0'; seek.value = 0; cur.textContent = '0:00'; } mark(); };
        const go = (k) => { load(k); a.play().catch(() => {}); };
        const quietOthers = () => document.querySelectorAll('audio, video').forEach((m) => { if (m !== a && !m.paused && !m.muted) m.pause(); });
        play.addEventListener('click', () => { if (loaded < 0) load(i); if (a.paused) a.play().catch(() => {}); else a.pause(); });
        root.querySelectorAll('[data-ba-skip]').forEach((b) => b.addEventListener('click', () => go(i + Number(b.dataset.baSkip))));
        rows.forEach((r) => r.addEventListener('click', () => { const k = Number(r.dataset.baI); if (k === i && loaded === i) { if (a.paused) a.play().catch(() => {}); else a.pause(); } else go(k); }));
        a.addEventListener('play', () => { quietOthers(); root.classList.add('is-playing'); mark(); document.dispatchEvent(new CustomEvent('anee:music-play')); });
        a.addEventListener('pause', () => { root.classList.remove('is-playing'); mark(); });
        a.addEventListener('timeupdate', () => {
            if (!a.duration) return;
            const r = a.currentTime / a.duration;
            fill.style.width = (r * 100) + '%'; seek.value = Math.round(r * 1000); cur.textContent = fmt(a.currentTime);
        });
        a.addEventListener('loadedmetadata', () => { dur.textContent = fmt(a.duration); });
        a.addEventListener('ended', () => { if (i < D.tracks.length - 1) go(i + 1); else { root.classList.remove('is-playing'); load(0); } });
        seek.addEventListener('input', () => {
            if (loaded < 0) load(i);
            if (!a.duration) { a.preload = 'metadata'; a.load(); return; }
            a.currentTime = seek.value / 1000 * a.duration;
        });
        if ('mediaSession' in navigator) {
            try {
                navigator.mediaSession.setActionHandler('nexttrack', () => go(i + 1));
                navigator.mediaSession.setActionHandler('previoustrack', () => go(i - 1));
            } catch (_) { /* not supported */ }
        }
        const allBtn = root.querySelector('[data-ba-all]');
        const unfold = (open) => {
            if (!allBtn) return;
            root.classList.toggle('is-all', open);
            allBtn.setAttribute('aria-expanded', String(open));
            allBtn.querySelector('span').textContent = open ? 'Fewer songs' : 'All ' + D.tracks.length + ' songs';
        };
        allBtn?.addEventListener('click', () => unfold(!root.classList.contains('is-all')));
        // A song that plays from the folded part (the next one, or a skip) opens it.
        a.addEventListener('play', () => { if (allBtn && rows[i] && rows[i].closest('.ba-more')) unfold(true); });
        // The songs rise in once the section is reached.
        if ('IntersectionObserver' in window) {
            const io = new IntersectionObserver((es) => es.forEach((e) => { if (e.isIntersecting) { root.classList.add('is-in'); io.disconnect(); } }), { threshold: .2 });
            io.observe(root);
        } else root.classList.add('is-in');
    };
    const all = () => document.querySelectorAll('[data-album]').forEach(wire);
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', all); else all();
})();
</script>
@endonce
