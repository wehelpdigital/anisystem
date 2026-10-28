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
                    <button type="button" class="ff-inset-open" data-ff-lodge aria-haspopup="dialog" aria-label="See the lodged field larger">
                        <span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 3h6v6M9 21H3v-6M21 3l-7 7M3 21l7-7"/></svg></span>
                    </button>
                    <figcaption><b>Traditional Farming</b>Lodged flat before harvest</figcaption>
                </figure>
            </div>
            <div class="ff-copy reveal" style="--reveal-delay: .1s">
                <p class="hp-kick">Real video of the field</p>
                <h2 class="hp-h2" id="ffH">This Is What Precision <em>Looks Like.</em></h2>
                <p class="hp-p">An Anee supported palay field, filmed just before harvest. It followed Anee's guidance through the season, and it shows: an even, healthy field, its stems still standing.</p>
                <ul class="ff-pts">
                    <li style="--k: 0">{!! $ffTick !!}<span><b>Strong stems</b> that carry the grain without falling</span></li>
                    <li style="--k: 1">{!! $ffTick !!}<span><b>An even, healthy field</b> from one end to the other</span></li>
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

    {{-- The lodged field, big (2026-10-29, the owner: "an expand button that when clicked shows the lodging in a lightbox").
         The script moves it to the body, out of the column's fade in transform. --}}
    <div class="ff-lb" id="ffLodge" role="dialog" aria-modal="true" aria-labelledby="ffLodgeH" hidden>
        <div class="ff-lb-bg" data-ff-close></div>
        <figure class="ff-lb-box">
            <button type="button" class="ff-lb-x" data-ff-close aria-label="Close"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"/></svg></button>
            <img src="{{ asset('images/site/photos/lodged-palay-600.webp') }}" srcset="{{ asset('images/site/photos/lodged-palay-600.webp') }} 600w, {{ asset('images/site/photos/lodged-palay.webp') }} 1000w" sizes="(min-width: 900px) 56rem, 100vw" alt="A palay field lodged flat before harvest, the stems fallen into the mud" width="1000" height="750" loading="lazy">
            <figcaption>
                <span class="ff-lb-tag">Traditional Farming</span>
                <b id="ffLodgeH">Lodged flat before harvest</b>
                <p>When the stems fall, the grain lies in the mud: harder to cut, slower to dry, and much of it lost before it ever reaches the sack.</p>
            </figcaption>
        </figure>
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
    /* Its expand button: the whole photo opens it, the round icon in its corner says so. */
    .ff-inset-open { position: absolute; left: .35rem; right: .35rem; top: .35rem; aspect-ratio: 4 / 3; display: flex; align-items: flex-start; justify-content: flex-end; padding: .4rem;
        border-radius: .8rem; cursor: zoom-in; background: transparent; transition: background-color .28s cubic-bezier(.22,1,.36,1); }
    .ff-inset-open span { display: grid; place-items: center; width: 1.9rem; height: 1.9rem; border-radius: 999px; color: #14210c; background: #f5c518;
        box-shadow: 0 0 0 4px rgb(245 197 24 / .28), 0 8px 16px -6px rgb(0 0 0 / .6); transition: transform .28s cubic-bezier(.22,1,.36,1); }
    .ff-inset-open svg { width: .9rem; height: .9rem; }
    .ff-inset-open:hover { background: rgb(255 255 255 / .1); }
    .ff-inset-open:hover span, .ff-inset-open:focus-visible span { transform: scale(1.12); }
    .ff-inset-open:focus-visible { outline: 3px solid #f5c518; outline-offset: 2px; }
    /* The lightbox. */
    .ff-lb { position: fixed; inset: 0; z-index: 300; display: grid; place-items: center; padding: 1rem; }
    .ff-lb[hidden] { display: none; }
    .ff-lb-bg { position: absolute; inset: 0; background: rgb(6 10 4 / .84); backdrop-filter: blur(6px); -webkit-backdrop-filter: blur(6px); opacity: 0;
        transition: opacity .3s cubic-bezier(.22,1,.36,1); }
    .ff-lb-box { position: relative; width: min(56rem, 100%); max-height: calc(100vh - 2rem); margin: 0; overflow: auto; border-radius: 1.6rem; background: #14100d;
        box-shadow: 0 50px 100px -30px rgb(0 0 0 / .9), inset 0 0 0 1px rgb(248 113 113 / .3); opacity: 0; transform: translateY(18px) scale(.96);
        transition: opacity .32s cubic-bezier(.22,1,.36,1), transform .32s cubic-bezier(.22,1,.36,1); }
    .ff-lb.is-on .ff-lb-bg, .ff-lb.is-on .ff-lb-box { opacity: 1; transform: none; }
    .ff-lb-box img { display: block; width: 100%; max-height: calc(100vh - 12rem); aspect-ratio: 4 / 3; object-fit: contain; background: #000; }
    .ff-lb-box figcaption { padding: 1rem 1.25rem 1.25rem; color: #fff; }
    .ff-lb-tag { display: inline-flex; padding: .25rem .65rem; border-radius: 999px; font-size: .68rem; font-weight: 900; letter-spacing: .08em; text-transform: uppercase;
        color: #fff; background: #b42318; }
    .ff-lb-box b { display: block; margin-top: .5rem; font-family: var(--font-heading); font-size: 1.2rem; font-weight: 800; line-height: 1.25; }
    .ff-lb-box p { margin-top: .3rem; font-size: .9rem; line-height: 1.55; color: #d6d3d1; }
    .ff-lb-x { position: absolute; z-index: 3; top: .8rem; right: .8rem; display: grid; place-items: center; width: 2.5rem; height: 2.5rem; border-radius: 999px; cursor: pointer;
        color: #fff; background: rgb(10 14 7 / .7); box-shadow: inset 0 0 0 1px rgb(255 255 255 / .25); transition: transform .28s cubic-bezier(.22,1,.36,1); }
    .ff-lb-x:hover { transform: scale(1.08); }
    .ff-lb-x svg { width: 1.2rem; height: 1.2rem; }
    html.ff-lock { overflow: hidden; }
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
        .ff-lb-bg, .ff-lb-box, .ff-inset-open, .ff-inset-open span { transition: none !important; }
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
    // The lodged field, big: the photo's button opens it; the x, the backdrop or Escape closes it. The media column fades
    // in on a transform, which would hold a fixed box inside it, so the lightbox lives on the body.
    const lb = document.getElementById('ffLodge'), lbOpen = document.querySelector('[data-ff-lodge]');
    if (lb && lbOpen) {
        document.body.appendChild(lb);
        const shut = () => {
            if (lb.hidden) return;
            lb.classList.remove('is-on'); document.documentElement.classList.remove('ff-lock');
            setTimeout(() => { lb.hidden = true; }, calm ? 0 : 320);
            lbOpen.focus({ preventScroll: true });
        };
        lbOpen.addEventListener('click', () => {
            lb.hidden = false; document.documentElement.classList.add('ff-lock');
            requestAnimationFrame(() => requestAnimationFrame(() => lb.classList.add('is-on')));
            lb.querySelector('.ff-lb-x').focus({ preventScroll: true });
        });
        lb.addEventListener('click', (e) => { if (e.target.closest('[data-ff-close]')) shut(); });
        document.addEventListener('keydown', (e) => { if (e.key === 'Escape') shut(); });
    }
})();
</script>
@endpush
@endonce
