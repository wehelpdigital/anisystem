{{-- A real field, filmed (2026-10-29, the owner: "also feature this video in homepage below ... Modern Farming Wins by
     Precision"). The owner's own Anee supported palay, standing strong before harvest, beside a lodged field for
     contrast. The film plays silent and looped only while on screen; its button plays it full screen in the full
     size cut. The same films as Anee vs Now (public/videos/site/anee-standing-palay*.mp4); each address carries the
     file's time so a new cut reaches every browser. Expects $signup. --}}
@php
    $ffFilm = function (string $f) {
        $path = public_path('videos/site/' . $f);

        return asset('videos/site/' . $f) . (is_file($path) ? '?v=' . filemtime($path) : '');
    };
    $ffTick = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>';
@endphp
<section class="hp-sec ff" id="real-field" aria-labelledby="ffH">
    <div class="max-w-6xl mx-auto px-4 sm:px-6">
        <div class="ff-grid">
            <div class="ff-media reveal">
                <div class="ff-frame" data-ff>
                    <video muted loop playsinline preload="none" poster="{{ $ffFilm('anee-standing-palay.webp') }}" data-hd="{{ $ffFilm('anee-standing-palay-hd.mp4') }}" aria-label="A real Anee supported palay field, standing strong before harvest">
                        <source src="{{ $ffFilm('anee-standing-palay.mp4') }}" type="video/mp4">
                    </video>
                    <span class="ff-rec"><i aria-hidden="true"></i>Real video of the field</span>
                    <button type="button" class="ff-full" data-ff-full aria-label="Watch the field full screen">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 3h6v6M9 21H3v-6M21 3l-7 7M3 21l7-7"/></svg>
                    </button>
                    <span class="ff-cap"><img src="{{ asset('images/anee/avatar-160.jpg') }}" alt="" width="40" height="40">Anee supported, standing strong</span>
                </div>
                <figure class="ff-inset">
                    <img src="{{ asset('images/site/photos/lodged-palay-600.webp') }}" alt="A palay field lodged flat before harvest" width="600" height="450" loading="lazy">
                    <figcaption><b>Without the timing</b>Lodged flat before harvest</figcaption>
                </figure>
            </div>
            <div class="ff-copy reveal" style="--reveal-delay: .1s">
                <p class="hp-kick">Real video of the field</p>
                <h2 class="hp-h2" id="ffH">This Is What Precision <em>Looks Like.</em></h2>
                <p class="hp-p">An Anee supported palay field, filmed just before harvest. Full, heavy panicles on stems that still stand, because every bag of nitrogen went on at the stage the crop could use it, and the work moved around the rain.</p>
                <ul class="ff-pts">
                    <li style="--k: 0">{!! $ffTick !!}<span><b>Nitrogen split by stage,</b> so the stems grow strong enough to carry the grain</span></li>
                    <li style="--k: 1">{!! $ffTick !!}<span><b>Sprays and fertilizer timed to the weather,</b> never washed off by the rain</span></li>
                    <li style="--k: 2">{!! $ffTick !!}<span><b>No lodging,</b> so the harvest is cut standing, not picked up from the mud</span></li>
                </ul>
                <div class="hp-cta is-left">
                    <div class="hp-cta-row">
                        <a href="{{ $signup }}" class="btn btn-accent btn-lg hp-go">Grow a field like this <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5-5 5M6 12h12"/></svg></a>
                        <a href="{{ url('/how-it-works/anee-vs-now') }}" class="hp-alt">See Anee vs Now</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

@once
@push('head')
<style>
    .ff { position: relative; overflow: hidden; background: radial-gradient(50rem 26rem at 10% 0%, #eef6e4, transparent 60%), linear-gradient(180deg, #fbfdf8, #f2f8ea); }
    .ff-grid { display: grid; gap: 2.4rem; align-items: center; }
    @media (min-width: 960px) { .ff-grid { grid-template-columns: minmax(0, 1.15fr) minmax(0, .85fr); gap: 3.5rem; } }
    .ff-media { position: relative; padding-bottom: 2.6rem; }
    @media (min-width: 960px) { .ff-media { padding-bottom: 0; } }
    /* The film: a big rounded frame with a gold edge and a soft glow. */
    .ff-frame { position: relative; aspect-ratio: 4 / 3; border-radius: 1.8rem; overflow: hidden; background: #1a2614;
        box-shadow: 0 0 0 1px rgb(245 197 24 / .45), 0 50px 90px -40px rgb(20 33 12 / .75), 0 0 60px -20px rgb(132 204 22 / .45); }
    .ff-frame video { display: block; width: 100%; height: 100%; object-fit: cover; }
    .ff-frame::after { content: ''; position: absolute; inset: auto 0 0; height: 40%; pointer-events: none; background: linear-gradient(180deg, transparent, rgb(8 12 6 / .55)); }
    .ff-rec { position: absolute; z-index: 2; left: 1rem; top: 1rem; display: inline-flex; align-items: center; gap: .45rem; padding: .38rem .8rem .38rem .6rem; border-radius: 999px;
        font-size: .74rem; font-weight: 900; letter-spacing: .06em; text-transform: uppercase; color: #f5c518; background: rgb(10 14 7 / .72);
        backdrop-filter: blur(6px); -webkit-backdrop-filter: blur(6px); }
    .ff-rec i { width: .55rem; height: .55rem; border-radius: 999px; background: #ef4444; box-shadow: 0 0 0 3px rgb(239 68 68 / .3); animation: ffRec 1.4s ease-in-out infinite; }
    @keyframes ffRec { 50% { opacity: .35; box-shadow: 0 0 0 6px rgb(239 68 68 / 0); } }
    .ff-full { position: absolute; z-index: 2; right: 1rem; top: 1rem; display: grid; place-items: center; width: 2.6rem; height: 2.6rem; border-radius: 999px; cursor: pointer;
        color: #14210c; background: #f5c518; box-shadow: 0 0 0 5px rgb(245 197 24 / .25), 0 10px 20px -8px rgb(0 0 0 / .6); transition: transform .28s cubic-bezier(.22,1,.36,1); }
    .ff-full svg { width: 1.15rem; height: 1.15rem; }
    .ff-full:hover { transform: scale(1.08); }
    .ff-cap { position: absolute; z-index: 2; left: 1rem; bottom: 1rem; display: inline-flex; align-items: center; gap: .55rem; padding: .4rem .9rem .4rem .4rem; border-radius: 999px;
        font-size: .9rem; font-weight: 800; white-space: nowrap; color: #fff; background: rgb(10 14 7 / .6); backdrop-filter: blur(6px); -webkit-backdrop-filter: blur(6px); }
    .ff-cap img { width: 1.9rem; height: 1.9rem; border-radius: 999px; object-fit: cover; box-shadow: 0 0 0 2px #f5c518; }
    /* The lodged field, a small card over the film's corner. */
    .ff-inset { position: absolute; z-index: 3; right: -.4rem; bottom: 0; width: 38%; max-width: 13rem; margin: 0; padding: .35rem .35rem .55rem; border-radius: 1.1rem; background: #fff;
        box-shadow: 0 24px 40px -20px rgb(20 33 12 / .6), 0 0 0 1px #f3d4cf; transform: rotate(3deg); animation: ffBob 6s ease-in-out infinite; }
    @media (min-width: 960px) { .ff-inset { right: -1.6rem; bottom: -1.6rem; } }
    .ff-inset img { display: block; width: 100%; aspect-ratio: 4 / 3; object-fit: cover; border-radius: .8rem; filter: saturate(.85); }
    .ff-inset figcaption { margin-top: .4rem; padding: 0 .3rem; font-size: .72rem; line-height: 1.3; color: #6b7280; }
    .ff-inset figcaption b { display: block; font-size: .78rem; color: #b42318; }
    @keyframes ffBob { 50% { transform: rotate(2deg) translateY(-6px); } }
    .ff-pts { display: grid; gap: .65rem; margin-top: 1.3rem; padding: 0; list-style: none; }
    .ff-pts li { display: flex; gap: .65rem; align-items: flex-start; padding: .75rem .85rem; border-radius: 1rem; font-size: .93rem; line-height: 1.5; color: #374151; background: #fff;
        box-shadow: 0 0 0 1px #e3ecd9, 0 12px 26px -22px rgb(20 33 12 / .5); }
    .ff-pts li b { color: #14210c; }
    .ff-pts svg { flex: none; width: 1.35rem; height: 1.35rem; padding: .25rem; margin-top: .05rem; border-radius: 999px; color: #fff; background: #4a7c2a; }
    html.js .ff .reveal .ff-pts li { opacity: 0; transform: translateX(-10px); transition: opacity .5s cubic-bezier(.22,1,.36,1), transform .5s cubic-bezier(.22,1,.36,1);
        transition-delay: calc(.35s + var(--k) * .12s); }
    html.js .ff .reveal.is-visible .ff-pts li { opacity: 1; transform: none; }
    /* Phones and tablets: the caption goes up under the badge, clear of the lodged card, which stays inside the page. */
    @media (max-width: 959.98px) { .ff-cap { bottom: auto; top: 3.4rem; } .ff-inset { right: .5rem; } }
    @media (max-width: 520px) { .ff-cap { font-size: .78rem; top: 3.1rem; } .ff-rec { font-size: .66rem; } .ff-inset { width: 42%; } }
    @media (prefers-reduced-motion: reduce) {
        .ff-rec i, .ff-inset { animation: none; }
        html.js .ff .reveal .ff-pts li { opacity: 1; transform: none; transition: none; }
    }
</style>
@endpush
@push('scripts')
<script>
(() => {
    // The field film: plays while on screen (and only when motion is welcome); its button plays the full size cut full screen.
    const box = document.querySelector('[data-ff]');
    if (!box) return;
    const v = box.querySelector('video');
    const calm = matchMedia('(prefers-reduced-motion: reduce)').matches;
    if (!calm && 'IntersectionObserver' in window) {
        new IntersectionObserver((es) => es.forEach((e) => { if (e.isIntersecting) v.play().catch(() => {}); else if (!document.fullscreenElement) v.pause(); }), { threshold: .25 }).observe(box);
    }
    box.querySelector('[data-ff-full]').addEventListener('click', () => {
        const at = v.currentTime || 0;
        if (v.dataset.hd && !v.dataset.big) {
            v.dataset.big = '1';
            v.src = v.dataset.hd;
            v.addEventListener('loadedmetadata', () => { try { v.currentTime = at; } catch (_) {} v.play().catch(() => {}); }, { once: true });
        }
        v.controls = true;
        if (v.requestFullscreen) v.requestFullscreen().catch(() => {});
        else if (v.webkitEnterFullscreen) v.webkitEnterFullscreen();
        v.play().catch(() => {});
    });
    document.addEventListener('fullscreenchange', () => { if (!document.fullscreenElement) v.controls = false; });
    v.addEventListener('webkitendfullscreen', () => { v.controls = false; });
})();
</script>
@endpush
@endonce
