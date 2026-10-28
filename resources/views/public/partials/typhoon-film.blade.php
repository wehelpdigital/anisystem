{{-- Typhoon season, days before harvest (2026-10-29, the owner: "this one is several days before harvest during strong
     typhoon season. remove the audio and describe what you see in the video. make the section look good"). Four silent
     moments from the owner's own film of an Anee supported field, crossfaded into one 18 second loop; the list beside
     it says what each moment shows and lights up in step with the film. On the homepage and on Anee vs Now. Expects
     $signup. --}}
@php
    $tfFilm = function (string $f) {
        $path = public_path('videos/site/' . $f);

        return asset('videos/site/' . $f) . (is_file($path) ? '?v=' . filemtime($path) : '');
    };
    // What each moment shows, with when it starts in the loop (the cuts cross at 4.5, 9 and 13.5 seconds).
    $tfSeen = [
        [0, 'Golden from end to end', 'Ripe panicles across the whole field, and every hill still standing.'],
        [4.75, 'Dry and ready for harvest', 'Firm, dry ground between the rows, ready for the harvester.'],
        [9.25, 'Heavy heads, strong stems', 'The heads bow under the weight of their grain, on stems that did not fall.'],
        [13.75, 'No flattened patches', 'An even field in the middle of typhoon season, ready for the harvester.'],
    ];
@endphp
<section class="tf on-dark" id="typhoon-season" aria-labelledby="tfH">
    <div class="tf-wind" aria-hidden="true"></div>
    <div class="relative max-w-6xl mx-auto px-4 sm:px-6 hp-sec" style="z-index:1">
        <div class="tf-grid">
            <div class="tf-copy">
                <div class="reveal">
                    <p class="hp-kick">Real video from typhoon season</p>
                    <h2 class="hp-h2" id="tfH">Typhoon Season. Days Before Harvest. <em>Still Standing.</em></h2>
                    <p class="hp-p">Filmed several days before harvest, in the middle of a strong typhoon season. This field followed Anee's guidance from planting to harvest. When the winds came, it was still standing: golden, even, and heavy with grain.</p>
                </div>
                {{-- The harvest that season (2026-10-29, the owner): farms around it 60 to 115 cavans a hectare, this farm 140,
                     cavans of 60 kg, fresh weight. The bars grow and the numbers count up once seen. --}}
                <div class="tf-yield reveal" data-tf-yield>
                    <p class="tf-k">The harvest that season, per hectare</p>
                    <div class="tf-yrow is-them">
                        <p><span>Most farmers this season</span><b><span data-tf-n="60">60</span> to <span data-tf-n="115">115</span> cavans</b></p>
                        <span class="tf-ybar" aria-hidden="true"><i style="--a: {{ round(60 / 160 * 100, 1) }}%; --b: {{ round(115 / 160 * 100, 1) }}%"></i></span>
                    </div>
                    <div class="tf-yrow is-anee">
                        <p><span><img src="{{ asset('images/anee/avatar-160.jpg') }}" alt="" width="40" height="40">This farm, with Anee</span><b><span data-tf-n="140">140</span> cavans</b></p>
                        <span class="tf-ybar" aria-hidden="true"><i style="--a: 0%; --b: {{ round(140 / 160 * 100, 1) }}%"></i></span>
                    </div>
                    <p class="tf-ynote">Cavans of 60 kg, fresh weight: 140 cavans is about 8.4 tons from one hectare, up to 80 cavans more than a farm at the low end, in the same typhoon season.</p>
                </div>
                <p class="tf-k reveal">What you see in the film</p>
                <ol class="tf-seen" data-tf-list>
                    @foreach ($tfSeen as $k => [$at, $t, $w])
                        <li class="reveal" data-at="{{ $at }}" style="--reveal-delay: {{ $k * 0.08 }}s">
                            <span class="tf-n">{{ $k + 1 }}</span>
                            <span class="tf-w"><b>{{ $t }}</b>{{ $w }}<i class="tf-bar" aria-hidden="true"><i></i></i></span>
                        </li>
                    @endforeach
                </ol>
            </div>
            <div class="tf-media reveal" style="--reveal-delay: .1s">
                <div class="tf-frame" data-tf>
                    <video muted loop playsinline preload="none" poster="{{ $tfFilm('anee-typhoon-palay.webp') }}" aria-label="An Anee supported palay field several days before harvest in typhoon season, golden and still standing">
                        <source src="{{ $tfFilm('anee-typhoon-palay.mp4') }}" type="video/mp4">
                    </video>
                    <span class="tf-rec"><i aria-hidden="true"></i>Real video of the field</span>
                    <button type="button" class="tf-full" data-tf-full aria-label="Watch it full screen"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 3h6v6M9 21H3v-6M21 3l-7 7M3 21l7-7"/></svg></button>
                    <div class="tf-chips">
                        <span><b aria-hidden="true">🌀</b>Strong typhoon season</span>
                        <span><b aria-hidden="true">🌾</b>Days before harvest</span>
                    </div>
                    <span class="tf-now" data-tf-now aria-hidden="true"></span>
                </div>
            </div>
        </div>
        <div class="hp-cta reveal">
            <div class="hp-cta-row">
                <a href="{{ $signup }}" class="btn btn-accent btn-lg hp-go">Keep your field standing <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5-5 5M6 12h12"/></svg></a>
            </div>
            <p class="hp-cta-note">Start free. Anee's guidance comes with Libre + Anee.</p>
        </div>
    </div>
</section>

@once
@push('head')
<style>
    /* Typhoon season: a dark band, storm blue over field green, and a wind that blows across it. */
    .tf { position: relative; isolation: isolate; overflow: hidden; color: #fff;
        background: radial-gradient(52rem 30rem at 85% 0%, rgb(56 99 145 / .55), transparent 62%), radial-gradient(40rem 26rem at 0% 100%, rgb(74 124 42 / .35), transparent 60%),
            linear-gradient(180deg, #0f1a17, #0b130c); border-top: 1px solid rgb(148 182 214 / .2); }
    .tf-wind { position: absolute; inset: -20%; z-index: 0; pointer-events: none; opacity: .5;
        background: repeating-linear-gradient(-18deg, transparent 0 46px, rgb(186 210 235 / .07) 46px 48px, transparent 48px 120px);
        animation: tfWind 7s linear infinite; }
    @keyframes tfWind { to { transform: translateX(-120px); } }
    .tf-grid { display: grid; gap: 2.4rem; align-items: center; }
    @media (min-width: 960px) { .tf-grid { grid-template-columns: minmax(0, 1.1fr) minmax(0, .9fr); gap: 3.5rem; } }
    /* The harvest that season: the farms around it as a range, this farm as one long gold bar. */
    .tf-yield { margin-top: 1.6rem; padding: 1rem 1.1rem 1.05rem; border-radius: 1.2rem; background: rgb(255 255 255 / .05); box-shadow: inset 0 0 0 1px rgb(245 197 24 / .35); }
    .tf-yield .tf-k { margin-top: 0; }
    .tf-yrow { margin-top: .8rem; }
    .tf-yrow p { display: flex; justify-content: space-between; align-items: baseline; gap: .8rem; font-size: .88rem; font-weight: 700; color: #c9d6c2; }
    .tf-yrow p span { display: inline-flex; align-items: center; gap: .4rem; }
    .tf-yrow p img { width: 1.4rem; height: 1.4rem; border-radius: 999px; object-fit: cover; box-shadow: 0 0 0 2px #f5c518; }
    .tf-yrow p b { flex: none; font-variant-numeric: tabular-nums; color: #fff; }
    .tf-yrow.is-anee p b { font-family: var(--font-heading); font-size: 1.3rem; font-weight: 800; color: #f5c518; }
    .tf-ybar { position: relative; display: block; height: .7rem; margin-top: .4rem; border-radius: 999px; background: rgb(255 255 255 / .08); overflow: hidden; }
    .tf-yrow.is-anee .tf-ybar { height: .95rem; }
    .tf-ybar i { position: absolute; top: 0; bottom: 0; left: var(--a); width: 0; border-radius: 999px; transition: width 1.3s cubic-bezier(.22,1,.36,1); }
    .tf-yrow.is-them .tf-ybar i { background: repeating-linear-gradient(135deg, #9ca3af 0 6px, #7b8491 6px 12px); }
    .tf-yrow.is-anee .tf-ybar i { background: linear-gradient(90deg, #a3e635, #f5c518); box-shadow: 0 0 16px rgb(245 197 24 / .55); transition-delay: .35s; }
    .tf-yield.is-in .tf-ybar i { width: calc(var(--b) - var(--a)); }
    .tf-ynote { margin-top: .7rem; font-size: .76rem; line-height: 1.5; color: #9fb09a; }
    .tf-k { margin-top: 1.8rem; font-size: .74rem; font-weight: 900; letter-spacing: .1em; text-transform: uppercase; color: #9cc0e0; }
    .tf-seen { display: grid; gap: .55rem; margin: .7rem 0 0; padding: 0; list-style: none; }
    .tf-seen li { display: flex; gap: .8rem; align-items: flex-start; padding: .8rem .9rem; border-radius: 1.1rem; background: rgb(255 255 255 / .04);
        box-shadow: inset 0 0 0 1px rgb(255 255 255 / .09); transition: background-color .35s cubic-bezier(.22,1,.36,1), box-shadow .35s cubic-bezier(.22,1,.36,1), transform .35s cubic-bezier(.22,1,.36,1); }
    .tf-n { flex: none; display: grid; place-items: center; width: 2rem; height: 2rem; border-radius: 999px; font-size: .85rem; font-weight: 900; color: #c8d8e8; background: rgb(255 255 255 / .08);
        transition: background-color .35s ease, color .35s ease; }
    .tf-w { flex: 1; min-width: 0; font-size: .9rem; line-height: 1.5; color: #c9d6c2; }
    .tf-w b { display: block; font-size: 1rem; color: #fff; }
    .tf-bar { display: block; height: 3px; margin-top: .55rem; border-radius: 999px; background: rgb(255 255 255 / .08); overflow: hidden; opacity: 0; transition: opacity .3s ease; }
    .tf-bar i { display: block; height: 100%; width: var(--p, 0%); border-radius: inherit; background: linear-gradient(90deg, #f5c518, #a3e635); }
    /* The moment the film is showing. */
    .tf-seen li.is-on { background: linear-gradient(100deg, rgb(245 197 24 / .14), rgb(255 255 255 / .04)); box-shadow: inset 0 0 0 1px rgb(245 197 24 / .55), 0 16px 30px -22px rgb(245 197 24 / .5);
        transform: translateX(4px); }
    .tf-seen li.is-on .tf-n { color: #14210c; background: #f5c518; }
    .tf-seen li.is-on .tf-bar { opacity: 1; }
    /* The film: tall, as it was shot, in a frame with the storm's blue edge. */
    .tf-media { display: flex; justify-content: center; }
    .tf-frame { position: relative; width: min(100%, 21rem); aspect-ratio: 9 / 16; border-radius: 2rem; overflow: hidden; background: #111;
        box-shadow: 0 0 0 1px rgb(148 182 214 / .45), 0 0 0 8px rgb(148 182 214 / .08), 0 50px 90px -40px rgb(0 0 0 / .9), 0 0 70px -20px rgb(56 99 145 / .7); }
    .tf-frame video { display: block; width: 100%; height: 100%; object-fit: cover; }
    .tf-frame::after { content: ''; position: absolute; inset: 0; pointer-events: none; background: linear-gradient(180deg, rgb(8 12 6 / .35), transparent 22%, transparent 70%, rgb(8 12 6 / .6)); }
    .tf-rec { position: absolute; z-index: 2; left: .9rem; top: .9rem; display: inline-flex; align-items: center; gap: .4rem; padding: .35rem .7rem .35rem .55rem; border-radius: 999px;
        font-size: .68rem; font-weight: 900; letter-spacing: .06em; text-transform: uppercase; color: #f5c518; background: rgb(10 14 7 / .7); backdrop-filter: blur(6px); -webkit-backdrop-filter: blur(6px); }
    .tf-rec i { width: .5rem; height: .5rem; border-radius: 999px; background: #ef4444; animation: tfRec 1.4s ease-in-out infinite; }
    @keyframes tfRec { 50% { opacity: .3; } }
    .tf-full { position: absolute; z-index: 2; right: .9rem; top: .8rem; display: grid; place-items: center; width: 2.4rem; height: 2.4rem; border-radius: 999px; cursor: pointer;
        color: #14210c; background: #f5c518; box-shadow: 0 0 0 5px rgb(245 197 24 / .22); transition: transform .28s cubic-bezier(.22,1,.36,1); }
    .tf-full svg { width: 1.05rem; height: 1.05rem; }
    .tf-full:hover { transform: scale(1.08); }
    .tf-chips { position: absolute; z-index: 2; left: .9rem; right: .9rem; bottom: 1.3rem; display: flex; flex-wrap: wrap; gap: .4rem; }
    .tf-chips span { display: inline-flex; align-items: center; gap: .35rem; padding: .38rem .7rem; border-radius: 999px; font-size: .76rem; font-weight: 800; color: #fff;
        background: rgb(10 14 7 / .62); backdrop-filter: blur(6px); -webkit-backdrop-filter: blur(6px); box-shadow: inset 0 0 0 1px rgb(255 255 255 / .2); }
    .tf-chips b { font-weight: 400; }
    /* The loop's progress, along the bottom edge. */
    .tf-now { position: absolute; z-index: 3; left: 0; bottom: 0; height: 4px; width: var(--p, 0%); background: linear-gradient(90deg, #f5c518, #a3e635); }
    @media (max-width: 959.98px) { .tf-media { order: -1; } .tf-frame { width: min(100%, 19rem); } }
    @media (prefers-reduced-motion: reduce) {
        .tf-wind, .tf-rec i { animation: none; }
        .tf-seen li { transition: none; transform: none !important; }
        .tf-ybar i { transition: none; }
    }
</style>
@endpush
@push('scripts')
<script>
(() => {
    // The typhoon film: plays while on screen; the list lights the moment it is showing, its bar filling as it plays.
    const box = document.querySelector('[data-tf]');
    if (!box) return;
    const v = box.querySelector('video'), now = box.querySelector('[data-tf-now]');
    const items = [...document.querySelectorAll('[data-tf-list] li')];
    const starts = items.map((li) => Number(li.dataset.at));
    const calm = matchMedia('(prefers-reduced-motion: reduce)').matches;
    let last = -1;
    const paint = () => {
        const t = v.currentTime || 0, d = v.duration || 18.5;
        let k = 0;
        starts.forEach((s, i) => { if (t >= s) k = i; });
        if (k !== last) { items.forEach((li, i) => li.classList.toggle('is-on', i === k)); last = k; }
        const end = k + 1 < starts.length ? starts[k + 1] : d;
        items[k].querySelector('.tf-bar i').style.setProperty('--p', Math.min(100, (t - starts[k]) / (end - starts[k]) * 100) + '%');
        now.style.setProperty('--p', (t / d * 100) + '%');
    };
    let raf = 0;
    const loop = () => { paint(); raf = requestAnimationFrame(loop); };
    v.addEventListener('play', () => { cancelAnimationFrame(raf); raf = requestAnimationFrame(loop); });
    v.addEventListener('pause', () => cancelAnimationFrame(raf));
    if (!calm && 'IntersectionObserver' in window) {
        new IntersectionObserver((es) => es.forEach((e) => { if (e.isIntersecting) v.play().catch(() => {}); else if (!document.fullscreenElement) v.pause(); }), { threshold: .3 }).observe(box);
    } else {
        items[0] && items[0].classList.add('is-on');
    }
    // The harvest bars grow and the cavans count up, once, when seen.
    const yb = document.querySelector('[data-tf-yield]');
    if (yb) {
        const nums = [...yb.querySelectorAll('[data-tf-n]')];
        const go = () => {
            yb.classList.add('is-in');
            if (calm) return;
            const t0 = performance.now();
            const step = (now) => {
                const k = Math.min(1, (now - t0) / 1300), e = 1 - Math.pow(1 - k, 3);
                nums.forEach((n) => { n.textContent = Math.round(Number(n.dataset.tfN) * e); });
                if (k < 1) requestAnimationFrame(step);
            };
            requestAnimationFrame(step);
        };
        if ('IntersectionObserver' in window && !calm) {
            const io = new IntersectionObserver((es) => { if (es.some((e) => e.isIntersecting)) { go(); io.disconnect(); } }, { threshold: .4 });
            io.observe(yb);
        } else go();
    }
    // A tap on a moment jumps the film to it.
    items.forEach((li, i) => li.addEventListener('click', () => { try { v.currentTime = starts[i] + .05; } catch (_) {} v.play().catch(() => {}); }));
    box.querySelector('[data-tf-full]').addEventListener('click', () => {
        v.controls = true;
        if (v.requestFullscreen) v.requestFullscreen().catch(() => {}); else if (v.webkitEnterFullscreen) v.webkitEnterFullscreen();
        v.play().catch(() => {});
    });
    document.addEventListener('fullscreenchange', () => { if (!document.fullscreenElement) v.controls = false; });
    v.addEventListener('webkitendfullscreen', () => { v.controls = false; });
})();
</script>
@endpush
@endonce
