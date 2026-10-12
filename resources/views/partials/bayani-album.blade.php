{{-- Bayani ng Lupa (2026-10-12): the anee.io album, one section with its
     artwork behind it (Anee on the left, the words and the songs on the
     right; on a phone the picture on top), a player and the seven songs.
     The files wait for a press (preload none): nobody on mobile data pays
     for a card they scrolled past. Shared by the homepage and the dashboard.
     Takes $id (unique on the page) and $h (the heading tag, h2 by default). --}}
@php
    $A = \App\Support\BayaniAlbum::class;
    $baTracks = $A::tracks();
    $baH = $h ?? 'h2';
    $baId = $id ?? 'ba';
    $art = asset($A::ART);
@endphp
@once
<style>
    .ba-c { container: ba / inline-size; }
    .ba { --ba-ease: cubic-bezier(.22,1,.36,1); position: relative; overflow: hidden; isolation: isolate; border-radius: 1.6rem; color: #f3f8ec;
        background: #0f1d08; box-shadow: 0 30px 60px -40px rgb(10 18 6 / .8); }
    .ba-art { position: relative; z-index: -2; overflow: hidden; aspect-ratio: 16 / 9.5; }
    .ba-art img { width: 100%; height: 100%; object-fit: cover; object-position: 25% 45%; transform-origin: 25% 45%; animation: baDrift 26s ease-in-out infinite alternate; }
    @keyframes baDrift { from { transform: scale(1.03); } to { transform: scale(1.1) translate(-1%, -1%); } }
    /* The words sit on the farmers' side, over a deep green that keeps them readable. */
    .ba-ink { position: absolute; left: 0; right: 0; top: 0; aspect-ratio: 16 / 9.5; z-index: -1;
        background: linear-gradient(180deg, rgb(15 29 8 / 0) 45%, rgb(15 29 8 / .8) 82%, #0f1d08 100%); }
    /* Sparks rising from the seedling Anee's hand calls up. */
    .ba-sparks { position: absolute; left: 36%; top: 6%; width: 22%; height: 60%; z-index: -1; pointer-events: none; }
    .ba-sparks i { position: absolute; bottom: 0; width: .32rem; height: .32rem; border-radius: 999px; background: #fff7c2; box-shadow: 0 0 10px 3px rgb(190 255 140 / .7);
        opacity: 0; animation: baSpark 4.2s ease-in infinite; }
    .ba-sparks i:nth-child(1) { left: 30%; animation-delay: 0s; } .ba-sparks i:nth-child(2) { left: 55%; animation-delay: .7s; }
    .ba-sparks i:nth-child(3) { left: 42%; animation-delay: 1.4s; } .ba-sparks i:nth-child(4) { left: 68%; animation-delay: 2.1s; }
    .ba-sparks i:nth-child(5) { left: 20%; animation-delay: 2.8s; } .ba-sparks i:nth-child(6) { left: 60%; animation-delay: 3.5s; }
    @keyframes baSpark { 0% { opacity: 0; transform: translate(0, 0) scale(.6); } 15% { opacity: 1; } 100% { opacity: 0; transform: translate(-10px, -9rem) scale(1.15); } }
    .ba-body { position: relative; margin-top: -3rem; padding: 0 1.1rem 1.4rem; }
    /* Wide enough: the picture fills the card, the words sit on the farmers' side. */
    @container ba (min-width: 900px) {
        .ba { min-height: 34rem; display: flex; align-items: center; }
        .ba-art { position: absolute; inset: 0; aspect-ratio: auto; }
        .ba-art img { object-position: 20% 40%; transform-origin: 25% 45%; }
        .ba-ink { inset: 0; aspect-ratio: auto; background: linear-gradient(90deg, rgb(15 29 8 / 0) 22%, rgb(15 29 8 / .55) 46%, rgb(15 29 8 / .92) 62%, #0f1d08 100%); }
        .ba-sparks { left: 33%; top: 18%; width: 18%; height: 66%; }
        .ba-body { margin: 0 0 0 auto; width: min(100%, 31rem); padding: 2.4rem 2.4rem 2.2rem; }
    }
    .ba-kick { display: inline-flex; align-items: center; gap: .45rem; padding: .3rem .7rem; border-radius: 999px; font-size: .72rem; font-weight: 800; letter-spacing: .08em;
        text-transform: uppercase; color: #3b2f00; background: #f5c518; }
    .ba-kick svg { width: .95rem; height: .95rem; }
    .ba-eq { display: inline-flex; align-items: flex-end; gap: 2px; height: .8rem; }
    .ba-eq i { width: 3px; height: 30%; border-radius: 2px; background: currentColor; }
    .ba.is-playing .ba-eq i { animation: baEq .9s ease-in-out infinite; }
    .ba.is-playing .ba-eq i:nth-child(2) { animation-delay: .2s; } .ba.is-playing .ba-eq i:nth-child(3) { animation-delay: .4s; } .ba.is-playing .ba-eq i:nth-child(4) { animation-delay: .1s; }
    @keyframes baEq { 0%, 100% { height: 30%; } 50% { height: 100%; } }
    .ba-h { margin-top: .7rem; font-family: var(--font-heading); font-weight: 800; font-size: clamp(2rem, 4.6vw, 3rem); line-height: 1.02; color: #fff;
        text-shadow: 0 2px 18px rgb(0 0 0 / .35); }
    .ba-h em { font-style: normal; color: #f5c518; }
    .ba-p { margin-top: .6rem; font-size: .95rem; line-height: 1.6; color: #cfe0bd; }
    /* The player: what is on, play and the two skips, the bar and the time. */
    .ba-now { margin-top: 1.1rem; display: flex; align-items: center; gap: .75rem; padding: .7rem; border-radius: 1.1rem; background: rgb(255 255 255 / .08);
        border: 1px solid rgb(255 255 255 / .12); backdrop-filter: blur(6px); }
    .ba-play { position: relative; flex: none; width: 3.3rem; height: 3.3rem; border-radius: 999px; display: grid; place-items: center; color: #1d3310; background: #f5c518;
        box-shadow: 0 10px 24px -10px rgb(245 197 24 / .8); transition: transform .28s var(--ba-ease); }
    .ba-play:hover { transform: scale(1.06); }
    .ba-play::after { content: ''; position: absolute; inset: -6px; border-radius: inherit; border: 2px solid rgb(245 197 24 / .6); opacity: 0; animation: baPing 2.6s ease-out infinite; }
    .ba.is-playing .ba-play::after { animation: none; }
    @keyframes baPing { 0% { opacity: .9; transform: scale(.9); } 100% { opacity: 0; transform: scale(1.35); } }
    .ba-play svg { width: 1.45rem; height: 1.45rem; }
    .ba-play .i-pause, .ba.is-playing .ba-play .i-play { display: none; }
    .ba.is-playing .ba-play .i-pause { display: block; }
    .ba-now-t { flex: 1; min-width: 0; }
    .ba-now-t small { display: block; font-size: .66rem; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; color: #a8cc7e; }
    .ba-now-t b { display: block; font-size: 1rem; font-weight: 800; color: #fff; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .ba-skip { flex: none; width: 2.3rem; height: 2.3rem; border-radius: 999px; display: grid; place-items: center; color: #e8f1de; background: rgb(255 255 255 / .08);
        transition: background-color .28s var(--ba-ease); }
    .ba-skip:hover { background: rgb(255 255 255 / .18); }
    .ba-skip svg { width: 1rem; height: 1rem; }
    .ba-bar { position: relative; margin-top: .75rem; height: 1.4rem; }
    .ba-bar input { position: absolute; inset: 0; width: 100%; opacity: 0; cursor: pointer; z-index: 2; }
    .ba-bar span { position: absolute; left: 0; right: 0; top: 50%; height: .32rem; border-radius: 999px; translate: 0 -50%; background: rgb(255 255 255 / .16); overflow: hidden; }
    .ba-bar span i { display: block; height: 100%; width: 0; border-radius: inherit; background: linear-gradient(90deg, #86b556, #f5c518); }
    .ba-times { display: flex; justify-content: space-between; font-size: .72rem; font-variant-numeric: tabular-nums; color: #a8bf95; }
    /* The songs. */
    .ba-list { margin-top: 1rem; display: grid; gap: .3rem; counter-reset: ba; }
    .ba-list li { opacity: 0; transform: translateY(8px); transition: opacity .45s var(--ba-ease), transform .45s var(--ba-ease); transition-delay: calc(var(--k) * 60ms); }
    .ba.is-in .ba-list li { opacity: 1; transform: none; }
    .ba-row { width: 100%; display: flex; align-items: center; gap: .7rem; padding: .5rem .6rem; border-radius: .9rem; text-align: left; color: #e8f1de;
        transition: background-color .28s var(--ba-ease); }
    .ba-row:hover { background: rgb(255 255 255 / .08); }
    .ba-row[aria-current="true"] { background: rgb(245 197 24 / .14); }
    .ba-n { flex: none; width: 1.6rem; text-align: center; font-size: .82rem; font-weight: 800; color: #a8cc7e; font-variant-numeric: tabular-nums; }
    .ba-t { flex: 1; min-width: 0; }
    .ba-t b { display: block; font-size: .93rem; font-weight: 700; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .ba-row[aria-current="true"] .ba-t b { color: #f5c518; }
    .ba-t small { display: inline-block; margin-top: .1rem; padding: .05rem .4rem; border-radius: 999px; font-size: .62rem; font-weight: 800; letter-spacing: .05em; text-transform: uppercase;
        color: #3b2f00; background: #f5c518; }
    .ba-d { flex: none; font-size: .78rem; color: #a8bf95; font-variant-numeric: tabular-nums; }
    .ba-bars { flex: none; display: none; align-items: flex-end; gap: 2px; width: 1rem; height: .9rem; color: #f5c518; }
    .ba-bars i { width: 3px; height: 35%; border-radius: 2px; background: currentColor; }
    .ba-row[aria-current="true"] .ba-n { display: none; }
    .ba-row[aria-current="true"] .ba-bars { display: inline-flex; margin: 0 .3rem; }
    .ba.is-playing .ba-row[aria-current="true"] .ba-bars i { animation: baEq .8s ease-in-out infinite; }
    .ba.is-playing .ba-row[aria-current="true"] .ba-bars i:nth-child(2) { animation-delay: .25s; }
    .ba.is-playing .ba-row[aria-current="true"] .ba-bars i:nth-child(3) { animation-delay: .5s; }
    @media (prefers-reduced-motion: reduce) {
        .ba-art img, .ba-sparks i, .ba-play::after, .ba.is-playing .ba-eq i, .ba.is-playing .ba-row[aria-current="true"] .ba-bars i { animation: none; }
        .ba-sparks { display: none; }
        .ba-list li { opacity: 1; transform: none; transition: none; }
        .ba-play, .ba-skip, .ba-row { transition: none; }
    }
    html.sm-still .ba-art img, html.sm-still .ba-sparks i { animation: none; }
</style>
@endonce
<div class="ba-c"><section class="ba" id="{{ $baId }}" data-album title="{{ $A::TITLE }}, the anee.io album" aria-labelledby="{{ $baId }}H" aria-describedby="{{ $baId }}P">
    <div class="ba-art">
        <img src="{{ $art }}-1280.webp" srcset="{{ $art }}-800.webp 800w, {{ $art }}-1280.webp 1280w, {{ $art }}-1920.webp 1920w"
             sizes="(min-width: 1280px) 1216px, 100vw" width="1920" height="1072" alt="{{ $A::ALT }}" title="{{ $A::TITLE }}: Anee, a hero of the land" loading="lazy" decoding="async">
        <span class="ba-sparks" aria-hidden="true"><i></i><i></i><i></i><i></i><i></i><i></i></span>
    </div>
    <div class="ba-ink" aria-hidden="true"></div>
    <div class="ba-body">
        <span class="ba-kick">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/></svg>
            The anee.io album
            <span class="ba-eq" aria-hidden="true"><i></i><i></i><i></i><i></i></span>
        </span>
        <{{ $baH }} class="ba-h" id="{{ $baId }}H">Bayani ng <em>Lupa</em></{{ $baH }}>
        <p class="ba-p" id="{{ $baId }}P">Seven songs for the heroes of the land, the Filipino farmers who feed the country. From the farmers who built anee.io, for every farmer who plants with a dream and harvests with a plan.</p>
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
        <ol class="ba-list" aria-label="The songs of {{ $A::TITLE }}">
            @foreach ($baTracks as $i => $t)
                <li style="--k: {{ $i }}"><button type="button" class="ba-row" data-ba-i="{{ $i }}" aria-current="{{ $i === 0 ? 'true' : 'false' }}" aria-label="Play {{ $t['title'] }}, {{ $t['length'] }}">
                    <span class="ba-n">{{ $i + 1 }}</span><span class="ba-bars" aria-hidden="true"><i></i><i></i><i></i></span>
                    <span class="ba-t"><b>{{ $t['title'] }}</b>@if ($t['main'])<small>Main song</small>@endif</span>
                    <span class="ba-d">{{ $t['length'] }}</span>
                </button></li>
            @endforeach
        </ol>
    </div>
    <audio preload="none" data-ba-audio></audio>
    <script type="application/json" data-ba-data>{!! json_encode(['tracks' => $baTracks, 'album' => $A::TITLE, 'art' => $art . '-800.webp'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
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
                try { navigator.mediaSession.metadata = new MediaMetadata({ title: t.title, artist: 'anee.io', album: D.album, artwork: [{ src: D.art, sizes: '800x447', type: 'image/webp' }] }); } catch (_) { /* old browser */ }
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
